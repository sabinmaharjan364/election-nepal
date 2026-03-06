<?php
$dirs = [
    'e:/projects/php/election/cache',
    'e:/projects/php/election/cache/constituencies',
];
foreach ($dirs as $dir) {
    foreach (glob($dir . '/*.json') as $f) {
        $d = json_decode(file_get_contents($f), true);
        $d['fetched_at'] = time();
        $d['ttl'] = 300;
        file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo 'Updated: ' . basename($f) . PHP_EOL;
    }
}
echo 'Done.' . PHP_EOL;
