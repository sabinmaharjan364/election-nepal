<?php
/**
 * GET /api/summary.php
 *
 * Returns the national election summary (total seats, leading parties, etc.)
 * Served from cache — no live request to ECN on each call.
 */
require_once __DIR__ . '/lib/response.php';

sendCached(CACHE_DIR . '/summary.json');
