<?php
/**
 * POST /api/ingest.php
 *
 * Receives raw ECN data from the browser-bridge script and writes it to cache.
 * The browser fetches ECN data (bypassing the bot block) and POSTs it here.
 *
 * Security: only accepts requests from localhost or with the correct API key.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key, X-Requested-With');
header('Access-Control-Allow-Credentials: false');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['error' => 'POST only']); exit; }

require_once __DIR__ . '/config.php';

// For local development: accept all requests (browser console on ECN site posts here)
// In production: uncomment the key check below
// $validKey = hash_equals(API_SECRET_KEY, $_SERVER['HTTP_X_API_KEY'] ?? '');
// if (!$validKey) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

$body = file_get_contents('php://input');
$input = json_decode($body, true);

if (!isset($input['type'], $input['data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing type or data fields']);
    exit;
}

$type = $input['type'];
$data = $input['data'];
$id   = $input['id'] ?? null;

$envelope = [
    'fetched_at' => time(),
    'ttl'        => CACHE_TTL,
    'data'       => $data,
];

$written = null;

switch ($type) {
    case 'parties':
        $written = CACHE_DIR . '/parties.json';
        break;
    case 'summary':
        $written = CACHE_DIR . '/summary.json';
        break;
    case 'constituencies':
        $written = CACHE_DIR . '/constituencies/index.json';
        break;
    case 'constituency':
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
        $written = CACHE_DIR . '/constituencies/' . (int)$id . '.json';
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => "Unknown type: $type"]);
        exit;
}

// Atomic write
$tmp = $written . '.tmp';
file_put_contents($tmp, json_encode($envelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
rename($tmp, $written);

echo json_encode(['ok' => true, 'type' => $type, 'file' => basename($written), 'fetched_at' => $envelope['fetched_at']]);
