<?php
/**
 * GET /api/parties.php
 *
 * Returns party-wise seat counts and vote totals.
 */
require_once __DIR__ . '/lib/response.php';

sendCached(CACHE_DIR . '/parties.json');
