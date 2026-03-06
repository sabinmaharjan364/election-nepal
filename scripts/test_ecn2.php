<?php
/**
 * Test with full browser headers + session init
 */
$cookieJar = sys_get_temp_dir() . '/ecn_test.txt';

function curlGet(string $url, string $cookieJar, array $extraHeaders = []): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR      => $cookieJar,
        CURLOPT_COOKIEFILE     => $cookieJar,
        CURLOPT_ENCODING       => 'gzip, deflate, br',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER     => array_merge([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9,ne;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Cache-Control: no-cache',
        ], $extraHeaders),
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'error' => $err];
}

// Step 1: Init session
echo "Step 1: Init session on homepage...\n";
$r = curlGet('https://result.election.gov.np/', $cookieJar);
echo "HTTP {$r['code']} | " . strlen($r['body']) . " bytes | Error: {$r['error']}\n";

sleep(1);

// Step 2: Fetch party data with XHR headers
echo "\nStep 2: Fetch party data...\n";
$r2 = curlGet(
    'https://result.election.gov.np/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRPartyTop5.txt',
    $cookieJar,
    [
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Referer: https://result.election.gov.np/',
        'X-Requested-With: XMLHttpRequest',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin',
    ]
);
echo "HTTP {$r2['code']} | " . strlen($r2['body']) . " bytes | Error: {$r2['error']}\n";
if ($r2['body']) {
    echo "First 1000 chars:\n" . substr($r2['body'], 0, 1000) . "\n";
}
