<?php
/**
 * Run this script to test ECN endpoints and dump the real data structure.
 * php scripts/test_ecn.php
 */

$endpoints = [
    'parties'  => 'https://result.election.gov.np/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRPartyTop5.txt',
    'summary'  => 'https://result.election.gov.np/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRSummary.txt',
    'const_1'  => 'https://result.election.gov.np/SecureJson.ashx?file=JSONFiles/Election2082/Constituency/HoR1.txt',
    'const_32' => 'https://result.election.gov.np/SecureJson.ashx?file=JSONFiles/Election2082/Constituency/HoR32.txt',
];

foreach ($endpoints as $name => $url) {
    echo "\n=== $name ===\n$url\n";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: application/json, */*',
            'Referer: https://result.election.gov.np/',
            'X-Requested-With: XMLHttpRequest',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo "CURL ERROR: $err\n";
        continue;
    }

    echo "HTTP $code | Size: " . strlen($body) . " bytes\n";

    $data = json_decode($body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "JSON keys: " . implode(', ', array_keys(is_array($data) ? ($data[0] ?? $data) : [])) . "\n";
        echo json_encode(array_slice(is_array($data) ? $data : [$data], 0, 2), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "NOT JSON. Raw (first 500 chars):\n" . substr($body, 0, 500) . "\n";
    }
}
