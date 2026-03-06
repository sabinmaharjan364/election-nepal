<?php
/**
 * GET /api/constituency.php?id=1
 *
 * Returns results for a single constituency (candidates, vote counts, winner).
 * ?id is required. Use /api/constituencies.php for the full index.
 */
require_once __DIR__ . '/lib/response.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    sendError(400, 'Missing or invalid ?id parameter. Must be a positive integer.');
}

$cacheFile = CACHE_DIR . '/constituencies/' . $id . '.json';

sendCached($cacheFile);
