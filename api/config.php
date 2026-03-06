<?php
// ─── ECN Source ──────────────────────────────────────────────────────────────
define('ECN_BASE_URL',  'https://result.election.gov.np');
define('ECN_REFERER',   'https://result.election.gov.np/');

// Discovered via DevTools → Network → XHR tab on result.election.gov.np
// The site serves pre-generated static JSON files via SecureJson.ashx?file=
define('ECN_ENDPOINTS', [
    // National party top results (confirmed from DevTools)
    'parties'            => '/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRPartyTop5.txt',

    // These follow the same file-serving pattern — confirm exact names in DevTools:
    'summary'            => '/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRSummary.txt',
    'constituency_index' => '/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRConstituencyList.txt',

    // Per-constituency: replace {id} with constituency number (1–165)
    'constituency'       => '/SecureJson.ashx?file=JSONFiles/Election2082/Constituency/HoR{id}.txt',
]);

// How to find more endpoints:
// 1. Open result.election.gov.np in Chrome
// 2. DevTools → Network → XHR/Fetch
// 3. Click each section on the page (party list, constituency detail, map)
// 4. Copy the file= path from each SecureJson.ashx request

// ─── Cache ───────────────────────────────────────────────────────────────────
define('CACHE_DIR',  __DIR__ . '/../cache');
define('CACHE_TTL',  60);  // seconds

// ─── Session ─────────────────────────────────────────────────────────────────
define('COOKIE_JAR', sys_get_temp_dir() . '/ecn_session.txt');

// ─── API key ─────────────────────────────────────────────────────────────────
define('API_SECRET_KEY', getenv('ELECTION_API_KEY') ?: 'change-me-in-production');
