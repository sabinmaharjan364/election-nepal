<?php
/**
 * Fetches live election data from Hamro Patro's public election data proxy.
 *
 * Source: election-dashboard-68fv.onrender.com/data
 * — this is a public Hamro Patro data mirror that refreshes ~every 60s.
 *
 * Data includes all actively-counting constituencies with per-candidate
 * vote counts, party names, and candidate photos.
 */
class HamroPatroFetcher
{
    const DATA_URL = 'https://election-dashboard-68fv.onrender.com/data';
    const TIMEOUT  = 20;

    public function fetch(): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::DATA_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (compatible; ElectionDashboard/1.0)',
            ],
        ]);

        $body  = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[HamroPatroFetcher] cURL error: $error");
            return null;
        }
        if ($code !== 200) {
            error_log("[HamroPatroFetcher] HTTP $code");
            return null;
        }

        $data = json_decode($body, true);
        if (!$data || empty($data['areas'])) {
            error_log("[HamroPatroFetcher] Invalid response or empty areas");
            return null;
        }

        return $data;
    }
}
