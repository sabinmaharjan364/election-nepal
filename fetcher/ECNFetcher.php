<?php
require_once __DIR__ . '/../api/config.php';

/**
 * ECNFetcher
 *
 * Handles session-aware HTTP requests to result.election.gov.np.
 *
 * The ECN portal is an ASP.NET application. Its ASHX handlers often require:
 *   1. A valid ASP.NET_SessionId cookie  (obtained by visiting the homepage)
 *   2. A Referer header matching the origin domain
 *   3. Occasionally an anti-CSRF token in the request body
 *
 * Strategy:
 *   - On first run, call initSession() to GET the homepage and populate the
 *     cookie jar file with session cookies.
 *   - On every subsequent fetch(), send those cookies automatically via the
 *     shared cookie jar.
 *   - If a response returns 401/403 or appears to be an HTML error page,
 *     re-run initSession() and retry once.
 */
class ECNFetcher
{
    private string $baseUrl;
    private string $cookieJar;

    public function __construct()
    {
        $this->baseUrl   = ECN_BASE_URL;
        $this->cookieJar = COOKIE_JAR;
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Visit the homepage to capture session cookies.
     * Call this before the first fetch() or when the session expires.
     */
    public function initSession(): void
    {
        $this->request($this->baseUrl . '/', [], true);
    }

    /**
     * Fetch a specific endpoint and return the decoded JSON data.
     *
     * @param  string $path   e.g. '/SecureJson.ashx?type=summary'
     * @param  array  $params Additional query params to append
     * @return array  Decoded JSON response
     * @throws RuntimeException on HTTP or parse errors
     */
    public function fetch(string $path, array $params = []): array
    {
        $url = $this->baseUrl . $path;
        if (!empty($params)) {
            $url .= (str_contains($path, '?') ? '&' : '?') . http_build_query($params);
        }

        $raw      = $this->request($url);
        $httpCode = $raw['code'];
        $body     = $raw['body'];

        // Session may have expired — reinitialise and retry once
        if ($httpCode === 401 || $httpCode === 403 || $this->looksLikeHtml($body)) {
            $this->initSession();
            $raw      = $this->request($url);
            $httpCode = $raw['code'];
            $body     = $raw['body'];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException("ECN returned HTTP {$httpCode} for {$url}");
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('ECN response is not valid JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Core cURL request with cookie jar and ECN-compatible headers.
     */
    private function request(string $url, array $extraHeaders = [], bool $isInit = false): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,

            // Persist cookies across requests (session maintenance)
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_COOKIEFILE     => $this->cookieJar,

            CURLOPT_HTTPHEADER     => array_merge([
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120',
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.9',
                'Referer: ' . ECN_REFERER,
                'X-Requested-With: XMLHttpRequest',  // signals AJAX to ASP.NET
            ], $extraHeaders),

            // SSL verification — set to true in production if the cert is valid
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $body  = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("cURL error: {$error}");
        }

        return ['code' => $code, 'body' => $body];
    }

    /**
     * Detect if the response is an HTML page (session expired / error page)
     * rather than the expected JSON payload.
     */
    private function looksLikeHtml(string $body): bool
    {
        $trimmed = ltrim($body);
        return str_starts_with($trimmed, '<') || str_contains(strtolower($body), '<!doctype');
    }
}
