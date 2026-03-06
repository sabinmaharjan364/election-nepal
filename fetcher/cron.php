<?php
/**
 * cron.php — Background data fetcher
 *
 * Run this via system cron every 60 seconds:
 *   * * * * * php /path/to/election/fetcher/cron.php >> /var/log/ecn_fetch.log 2>&1
 *
 * Or, for dev/testing, run it manually:
 *   php fetcher/cron.php
 *
 * It will:
 *   1. Initialise the ECN session (if no cookie jar exists)
 *   2. Fetch national summary and party data
 *   3. Fetch results for all constituencies (reads the index list)
 *   4. Write everything to the cache/ directory
 */

require_once __DIR__ . '/ECNFetcher.php';
require_once __DIR__ . '/CacheWriter.php';

$fetcher = new ECNFetcher();
$cache   = new CacheWriter();

$log = fn(string $msg) => fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n");

// ─── 1. Session init (only if cookie jar is missing or stale) ─────────────────
if (!file_exists(COOKIE_JAR) || filemtime(COOKIE_JAR) < time() - 3600) {
    $log('Initialising ECN session...');
    try {
        $fetcher->initSession();
        $log('Session initialised.');
    } catch (RuntimeException $e) {
        $log('Session init failed: ' . $e->getMessage());
        exit(1);
    }
}

// ─── 2. National summary ──────────────────────────────────────────────────────
$log('Fetching summary...');
try {
    $summary = $fetcher->fetch(ECN_ENDPOINTS['summary']);
    $cache->writeSummary($summary);
    $log('Summary cached.');
} catch (RuntimeException $e) {
    $log('Summary fetch failed: ' . $e->getMessage());
}

// ─── 3. Party totals ──────────────────────────────────────────────────────────
$log('Fetching party data...');
try {
    $parties = $fetcher->fetch(ECN_ENDPOINTS['parties']);
    $cache->writeParties($parties);
    $log('Party data cached.');
} catch (RuntimeException $e) {
    $log('Party fetch failed: ' . $e->getMessage());
}

// ─── 4. Constituency index (fetch once; used to drive per-constituency loops) ─
$indexFile = CACHE_DIR . '/constituencies/index.json';

if (!$cache->isFresh($indexFile)) {
    $log('Fetching constituency index...');
    try {
        // TODO: Replace the endpoint below with the actual constituency list endpoint
        // you discover in DevTools. It often returns an array of { id, name, ... }.
        $index = $fetcher->fetch(ECN_ENDPOINTS['constituency']);
        $cache->writeConstituencyIndex($index);
        $log('Constituency index cached (' . count($index) . ' constituencies).');
    } catch (RuntimeException $e) {
        $log('Constituency index fetch failed: ' . $e->getMessage());
    }
}

// ─── 5. Per-constituency results ──────────────────────────────────────────────
if (file_exists($indexFile)) {
    $indexData     = json_decode(file_get_contents($indexFile), true);
    $constituencies = $indexData['data'] ?? [];

    foreach ($constituencies as $c) {
        $id = $c['id'] ?? $c['constituency_id'] ?? null;
        if (!$id) continue;

        $file = CACHE_DIR . '/constituencies/' . $id . '.json';

        // Skip if cache is still fresh
        if ($cache->isFresh($file)) {
            continue;
        }

        $path = str_replace('{id}', $id, ECN_ENDPOINTS['constituency']);
        try {
            $data = $fetcher->fetch($path);
            $cache->writeConstituency((int)$id, $data);
            $log("Constituency {$id} cached.");
        } catch (RuntimeException $e) {
            $log("Constituency {$id} failed: " . $e->getMessage());
        }

        // Be polite — small delay between requests to avoid hammering the server
        usleep(200_000); // 200ms
    }
}

$log('Fetch cycle complete.');
