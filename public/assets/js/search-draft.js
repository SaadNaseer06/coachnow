(() => {
  const KEY = 'coachnow_search';

  const read = () => {
    try {
      return JSON.parse(sessionStorage.getItem(KEY) || '{}') || {};
    } catch {
      return {};
    }
  };

  const write = (partial) => {
    const next = { ...read(), ...partial };
    Object.keys(next).forEach((key) => {
      if (next[key] === '' || next[key] == null) delete next[key];
    });
    sessionStorage.setItem(KEY, JSON.stringify(next));
    return next;
  };

  const queryString = (extra = {}) => {
    const data = { ...read(), ...extra };
    const params = new URLSearchParams();
    ['location', 'date', 'sport', 'session', 'coach'].forEach((key) => {
      if (data[key]) params.set(key, String(data[key]));
    });
    return params.toString();
  };

  const withQuery = (url, extra = {}) => {
    const qs = queryString(extra);
    if (!qs) return url;
    const [base] = String(url).split('?');
    return base + '?' + qs;
  };

  document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a.js-search-carry');
    if (!link) return;
    event.preventDefault();
    const url = new URL(link.href, window.location.origin);
    const extra = {};
    url.searchParams.forEach((value, key) => {
      extra[key] = value;
    });
    const qs = queryString(extra);
    window.location.href = url.pathname + (qs ? `?${qs}` : '');
  });

  window.CoachNowSearch = { read, write, queryString, withQuery };
})();
