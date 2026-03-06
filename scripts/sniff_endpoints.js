// ============================================================
// ECN Endpoint Sniffer — paste into DevTools console
// on result.election.gov.np BEFORE interacting with the page
// ============================================================
// After pasting, click around the page (party list, map, etc.)
// then run:  copy(JSON.stringify(window._ecnUrls, null, 2))
// and paste into a text editor to see all data URLs found.
// ============================================================

(function() {
  window._ecnUrls = [];

  function record(method, url) {
    if (!url) return;
    const s = String(url);
    if (s.match(/\.(txt|json|ashx|js\?)/i) || s.includes('SecureJson') || s.includes('JSONFiles') || s.includes('Election')) {
      if (!window._ecnUrls.includes(s)) {
        window._ecnUrls.push(s);
        console.log('%c[ECN Sniffer]', 'color:#22c55e;font-weight:bold', method, s);
      }
    }
  }

  // Patch fetch
  const _fetch = window.fetch;
  window.fetch = function(input, init) {
    record('fetch', typeof input === 'string' ? input : input?.url);
    return _fetch.apply(this, arguments);
  };

  // Patch XHR
  const _open = XMLHttpRequest.prototype.open;
  XMLHttpRequest.prototype.open = function(method, url) {
    record('XHR', url);
    return _open.apply(this, arguments);
  };

  // Patch script tag injection (some sites load JSON via <script>)
  const _createElement = document.createElement.bind(document);
  document.createElement = function(tag) {
    const el = _createElement(tag);
    if (tag.toLowerCase() === 'script') {
      const orig = Object.getOwnPropertyDescriptor(HTMLScriptElement.prototype, 'src');
      Object.defineProperty(el, 'src', {
        set(v) { record('script', v); orig.set.call(this, v); },
        get() { return orig.get.call(this); },
      });
    }
    return el;
  };

  console.log('%c[ECN Sniffer] Active!', 'background:#166534;color:#86efac;padding:4px 8px;border-radius:4px;font-weight:bold');
  console.log('Now interact with the page. URLs will appear above.');
  console.log('When done, run: copy(JSON.stringify(window._ecnUrls, null, 2))');
})();
