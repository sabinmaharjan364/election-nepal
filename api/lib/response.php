<?php
/**
 * Shared API response helpers.
 * Include this at the top of every API endpoint file.
 */

require_once __DIR__ . '/../config.php';

// ─── CORS & content type ──────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');   // restrict to your Next.js domain in production
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: X-Api-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Optional API key auth ───────────────────────────────────────────────────
// Uncomment to enforce key-based auth from your Next.js backend:
//
// $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
// if (!hash_equals(API_SECRET_KEY, $key)) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

// ─── Helper functions ─────────────────────────────────────────────────────────

function readCache(string $file): ?array
{
    if (!file_exists($file)) {
        return null;
    }
    $raw = file_get_contents($file);
    return json_decode($raw, true);
}

function sendCached(string $cacheFile): void
{
    $cache = readCache($cacheFile);

    if ($cache === null) {
        http_response_code(503);
        echo json_encode(['error' => 'Data not yet available. Fetcher may not have run yet.']);
        exit;
    }

    $age = time() - ($cache['fetched_at'] ?? 0);

    header('X-Cache-Age: ' . $age);
    header('X-Fetched-At: ' . ($cache['fetched_at'] ?? 0));
    header('Cache-Control: public, max-age=30');

    echo json_encode([
        'fetched_at' => $cache['fetched_at'] ?? null,
        'stale'      => $age > ($cache['ttl'] ?? CACHE_TTL),
        'data'       => $cache['data'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
}

function sendError(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}
