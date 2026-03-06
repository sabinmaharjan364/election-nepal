<?php
/**
 * refresh.php — On-demand fetch trigger
 *
 * Called by the Next.js dashboard every 60 seconds via browser setInterval.
 * Runs the same fetch logic as cron.php and returns a JSON status.
 *
 * GET /api/refresh.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Suppress all cron.php output (log lines written to STDOUT)
ob_start();
require_once __DIR__ . '/../fetcher/cron.php';
ob_end_clean();

echo json_encode(['status' => 'ok', 'fetched_at' => time()]);
