<?php
// ─── ECN Source ──────────────────────────────────────────────────────────────
define('ECN_BASE_URL',  'https://result.election.gov.np');
define('ECN_REFERER',   'https://result.election.gov.np/');

// Discovered via DevTools → Network tab. Update these after inspecting the site.
// Common patterns: SecureJson.ashx?type=X  or  JSONHandler.ashx?method=X
define('ECN_ENDPOINTS', [
    'summary'      => '/SecureJson.ashx?type=summary',
    'parties'      => '/SecureJson.ashx?type=party',
    'constituency' => '/SecureJson.ashx?type=constituency&id={id}',  // replace {id}
    'candidates'   => '/SecureJson.ashx?type=candidate&constituencyId={id}',
]);

// ─── Cache ───────────────────────────────────────────────────────────────────
define('CACHE_DIR',      __DIR__ . '/../cache');
define('CACHE_TTL',      60);  // seconds before data is considered stale

// ─── Session persistence ─────────────────────────────────────────────────────
define('COOKIE_JAR',     sys_get_temp_dir() . '/ecn_session.txt');

// ─── Security ────────────────────────────────────────────────────────────────
// Set a secret key; your Next.js app must send this in the X-Api-Key header.
// Generate with: php -r "echo bin2hex(random_bytes(32));"
define('API_SECRET_KEY', getenv('ELECTION_API_KEY') ?: 'change-me-in-production');
