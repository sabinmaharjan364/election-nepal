// ============================================================
// ECN Console Fetcher v2 — with path probing
// Paste into DevTools Console on result.election.gov.np
// ============================================================

const PHP_API = 'http://election.test/api/ingest.php';

async function push(type, data, id = null) {
  const body = { type, data };
  if (id !== null) body.id = id;
  const r = await fetch(PHP_API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  return r.json();
}

// Probe multiple candidate paths — returns first one that serves valid JSON
async function probe(candidates) {
  for (const url of candidates) {
    const r = await fetch(url, { cache: 'no-store' });
    const text = await r.text();
    const stripped = text.replace(/^\uFEFF/, '').replace(/^[^[{]*/, '').replace(/[^}\]]*$/, '');
    const isJson = r.ok && (stripped.startsWith('[') || stripped.startsWith('{'));
    console.log(`  probe ${url} → ${r.status} ${isJson ? '✓ JSON' : '✗ not JSON'}`);
    if (isJson) return { url, text: stripped };
  }
  return null;
}

async function probeAndPush(label, candidates, type, id = null) {
  console.log(`\nProbing: ${label}`);
  const found = await probe(candidates);
  if (!found) {
    console.error(`  No valid endpoint found for ${label}`);
    console.log('  Tip: Check Network tab to find the real URL the page uses');
    return null;
  }
  try {
    const data = JSON.parse(found.text);
    console.log(`  ✓ ${found.url} (${JSON.stringify(data).length} bytes)`);
    console.log(`  Keys:`, Object.keys(Array.isArray(data) ? data[0] ?? {} : data));
    const result = await push(type, data, id);
    console.log(`  Pushed to PHP:`, result);
    return data;
  } catch(e) {
    console.error(`  Parse error:`, e.message);
  }
}

// Run all fetches with fallback probing
(async () => {
  console.log('=== ECN Fetcher v2 ===');

  await probeAndPush('Party Results', [
    '/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRPartyTop5.txt',
    '/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRPartyTop10.txt',
    '/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRParty.txt',
    '/SecureJson.ashx?file=JSONFiles/Election2082/Common/PartyResult.txt',
  ], 'parties');

  await probeAndPush('National Summary', [
    '/SecureJson.ashx?file=JSONFiles/Election2082/Common/HoRSummary.txt',
    '/SecureJson.ashx?file=JSONFiles/Election2082/Common/Summary.txt',
    '/SecureJson.ashx?file=JSONFiles/Election2082/Common/NationalSummary.txt',
  ], 'summary');

  await probeAndPush('Constituency 1', [
    '/SecureJson.ashx?file=JSONFiles/Election2082/Constituency/HoR1.txt',
    '/SecureJson.ashx?file=JSONFiles/Election2082/Constituency/Constituency1.txt',
  ], 'constituency', 1);

  console.log('\n=== Done. ===');
  console.log('If all probes failed, run this to find the real paths:');
  console.log(`  copy(performance.getEntriesByType('resource').filter(r=>r.name.includes('ashx')||r.name.includes('txt')).map(r=>r.name).join('\\n'))`);
})();
