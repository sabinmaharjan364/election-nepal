<?php
/**
 * cron.php — Background data fetcher (Hamro Patro source)
 *
 * Run every 60 seconds:
 *   * * * * * php /path/to/election/fetcher/cron.php >> /tmp/election_fetch.log 2>&1
 *
 * Or manually:  php fetcher/cron.php
 *
 * Fetches live 2082 election data from Hamro Patro public API and writes
 * to cache/ for the Next.js dashboard to read.
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/HamroPatroFetcher.php';
require_once __DIR__ . '/HamroPatroNormalizer.php';

$log = fn(string $msg) => fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n");

$fetcher = new HamroPatroFetcher();
$cacheDir = CACHE_DIR;
$constDir = $cacheDir . '/constituencies';

if (!is_dir($constDir)) {
    mkdir($constDir, 0755, true);
}

// ─── 1. Fetch raw data ────────────────────────────────────────────────────────
$log('Fetching from Hamro Patro...');
$raw = $fetcher->fetch();

if (!$raw) {
    $log('Fetch failed — exiting.');
    exit(1);
}

$log('Got ' . count($raw['areas']) . ' areas from Hamro Patro.');
$normalizer = new HamroPatroNormalizer($raw);
$now = time();
$ttl = CACHE_TTL;

// ─── 2. Parties ───────────────────────────────────────────────────────────────
$parties = $normalizer->parties();
file_put_contents(
    $cacheDir . '/parties.json',
    json_encode(['fetched_at' => $now, 'ttl' => $ttl, 'data' => $parties], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
$log('Parties cached (' . count($parties) . ' parties).');

// ─── 3. Summary ───────────────────────────────────────────────────────────────
$summary = $normalizer->summary($parties);
file_put_contents(
    $cacheDir . '/summary.json',
    json_encode(['fetched_at' => $now, 'ttl' => $ttl, 'data' => $summary], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
$log("Summary: {$summary['results_declared']} declared, {$summary['counting_in_progress']} counting.");

// ─── 4. Constituency index ────────────────────────────────────────────────────
$index = $normalizer->constituenciesIndex();
$tmp   = $constDir . '/index.json.tmp';
$dest  = $constDir . '/index.json';
file_put_contents($tmp, json_encode(['fetched_at' => $now, 'ttl' => $ttl, 'data' => $index], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
rename($tmp, $dest);
$log('Constituency index cached (' . count($index) . ' constituencies).');

// ─── 5. Per-constituency detail files ─────────────────────────────────────────
$written = 0;
foreach ($raw['areas'] as $entry) {
    $id   = (int) ($entry['areaId'] ?? $entry['area']['id'] ?? 0);
    if (!$id) continue;

    $detail = $normalizer->constituency($entry);
    $tmp    = $constDir . "/{$id}.json.tmp";
    $dest   = $constDir . "/{$id}.json";
    file_put_contents($tmp, json_encode(['fetched_at' => $now, 'ttl' => $ttl, 'data' => $detail], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    rename($tmp, $dest);
    $written++;
}
$log("Constituency detail files written: $written.");

$log('Fetch cycle complete. Leading: ' . ($parties[0]['party_name_en'] ?? '?') .
     ' (' . ($parties[0]['seats_won'] ?? 0) . ' won + ' . ($parties[0]['seats_leading'] ?? 0) . ' leading).');
