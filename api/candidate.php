<?php
/**
 * GET /api/candidate.php?id={candidateId}&c={constituencyId}
 *
 * Returns a single candidate's full profile, enriched with constituency context.
 *
 * ?id  = Hamro Patro candidate ID (required)
 * ?c   = constituency ID to look in (required — avoids scanning all files)
 *
 * Response shape:
 *   { fetched_at, stale, data: { candidate, constituency, rivals } }
 *
 * Where rivals = top 3 other candidates from same constituency for vote comparison.
 */
require_once __DIR__ . '/lib/response.php';

$candidateId    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$constituencyId = filter_input(INPUT_GET, 'c',  FILTER_VALIDATE_INT);

if (!$candidateId || $candidateId < 1) {
    sendError(400, 'Missing or invalid ?id parameter.');
}
if (!$constituencyId || $constituencyId < 1) {
    sendError(400, 'Missing or invalid ?c (constituency) parameter.');
}

$cacheFile = CACHE_DIR . '/constituencies/' . $constituencyId . '.json';
$cache     = readCache($cacheFile);

if ($cache === null || empty($cache['data'])) {
    sendError(503, 'Constituency data not available yet.');
}

$constituency = $cache['data'];
$candidates   = $constituency['candidates'] ?? [];

// Find the requested candidate
$candidate = null;
foreach ($candidates as $c) {
    if ((int)$c['id'] === $candidateId) {
        $candidate = $c;
        break;
    }
}

if (!$candidate) {
    sendError(404, "Candidate {$candidateId} not found in constituency {$constituencyId}.");
}

// Build rivals list: all other candidates sorted by votes desc
$rivals = array_filter($candidates, fn($c) => (int)$c['id'] !== $candidateId);
usort($rivals, fn($a, $b) => $b['votes'] <=> $a['votes']);
$rivals = array_values(array_slice($rivals, 0, 4));

// Constituency summary (stripped down)
$constituencySummary = [
    'id'                      => $constituency['id'],
    'name'                    => $constituency['name'],
    'name_np'                 => $constituency['name_np'],
    'province'                => $constituency['province'],
    'district'                => $constituency['district'] ?? '',
    'status'                  => $constituency['status'] ?? 'pending',
    'total_votes_counted'     => $constituency['total_votes_counted'],
    'total_registered_voters' => $constituency['total_registered_voters'],
    'total_cast_votes'        => $constituency['total_cast_votes'] ?? 0,
    'counting_complete'       => $constituency['counting_complete'],
];

$age   = time() - ($cache['fetched_at'] ?? 0);
$stale = $age > ($cache['ttl'] ?? CACHE_TTL);

header('Cache-Control: public, max-age=30');

echo json_encode([
    'fetched_at' => $cache['fetched_at'] ?? null,
    'stale'      => $stale,
    'data'       => [
        'candidate'    => $candidate,
        'constituency' => $constituencySummary,
        'rivals'       => $rivals,
        'total_candidates' => count($candidates),
    ],
], JSON_UNESCAPED_UNICODE);
