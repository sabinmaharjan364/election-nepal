<?php
/**
 * GET /api/constituencies.php
 *
 * Returns the index of all constituencies (id, name, province).
 * Used by the frontend to populate dropdowns, maps, and list views.
 */
require_once __DIR__ . '/lib/response.php';

sendCached(CACHE_DIR . '/constituencies/index.json');
