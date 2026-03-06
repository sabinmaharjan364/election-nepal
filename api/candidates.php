<?php
/**
 * GET /api/candidates.php
 *
 * Returns a flat list of ALL candidates across every constituency cache file.
 * Used by the frontend search modal to search candidates by name/party.
 *
 * Response shape:
 *   { fetched_at, stale, data: CandidateSearchResult[] }
 *
 * Each item:
 *   { candidate_id, name, name_np, party, party_abbr, party_color,
 *     image_url, votes, percentage, status,
 *     constituency_id, constituency_name, constituency_np, province }
 */
require_once __DIR__ . '/lib/response.php';

$dir = CACHE_DIR . '/constituencies';

if (!is_dir($dir)) {
    sendError(503, 'No constituency data available. Run the fetcher first.');
}

$results    = [];
$latestAge  = 0;
$anyStale   = false;

foreach (glob($dir . '/*.json') as $file) {
    $basename = basename($file, '.json');
    if ($basename === 'index') continue;  // skip the index file

    $cache = readCache($file);
    if (!$cache || empty($cache['data'])) continue;

    $age = time() - ($cache['fetched_at'] ?? 0);
    if ($age > $latestAge) $latestAge = $age;
    if ($age > ($cache['ttl'] ?? CACHE_TTL)) $anyStale = true;

    $c = $cache['data'];  // Constituency data

    foreach ($c['candidates'] ?? [] as $cand) {
        $results[] = [
            'candidate_id'       => (int) $cand['id'],
            'name'               => $cand['name'] ?? '',
            'name_np'            => $cand['name_np'] ?? '',
            'party'              => $cand['party'] ?? '',
            'party_abbr'         => $cand['party_abbr'] ?? '',
            'party_color'        => $cand['party_color'] ?? '#6b7280',
            'image_url'          => $cand['image_url'] ?? null,
            'votes'              => (int) ($cand['votes'] ?? 0),
            'percentage'         => (float) ($cand['percentage'] ?? 0),
            'status'             => $cand['status'] ?? 'trailing',
            'constituency_id'    => (int) ($c['id'] ?? 0),
            'constituency_name'  => $c['name'] ?? '',
            'constituency_np'    => $c['name_np'] ?? '',
            'province'           => $c['province'] ?? '',
        ];
    }
}

// Sort by votes descending so top candidates surface first
usort($results, fn($a, $b) => $b['votes'] <=> $a['votes']);

header('Cache-Control: public, max-age=30');

echo json_encode([
    'fetched_at' => time() - $latestAge,
    'stale'      => $anyStale,
    'data'       => $results,
], JSON_UNESCAPED_UNICODE);
