/**
 * feedback.js — review widget for the social post drafts.
 *
 * Drop this on any review page:
 *   <div data-feedback="hazardous-area"></div>
 *   <script src="/uploads/promo/feedback.js"></script>
 *
 * Renders a note box and two buttons per slug, and reads/writes through
 * /core/promo-feedback.php. Styles are injected here so the widget works on
 * any page without touching that page's CSS.
 */
(function () {
  'use strict';

  var API = '/core/promo-feedback.php';

  var css = document.createElement('style');
  css.textContent = [
    '.fbk{margin-top:20px;border:1px solid #cfe2f2;background:#f8fafb}',
    '.fbk__h{display:flex;align-items:center;justify-content:space-between;gap:12px;',
    ' flex-wrap:wrap;padding:11px 16px;background:#eaf4fb;border-bottom:1px solid #cfe2f2}',
    '.fbk__t{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:15px;',
    ' letter-spacing:.14em;text-transform:uppercase;color:#1a1a2e}',
    '.fbk__s{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:11px;',
    ' letter-spacing:.14em;text-transform:uppercase;padding:4px 10px;color:#fff;background:#8fa3af}',
    '.fbk__s.ok{background:#1a7f4b}.fbk__s.chg{background:#a9761f}',
    '.fbk__b{padding:14px 16px 16px}',
    '.fbk textarea{width:100%;min-height:84px;resize:vertical;padding:11px 13px;',
    ' border:1px solid #cfe2f2;font-family:"Barlow",system-ui,sans-serif;font-size:15px;',
    ' line-height:1.5;color:#1a1a2e;background:#fff}',
    '.fbk textarea:focus{outline:2px solid #157bba;outline-offset:1px}',
    '.fbk__r{display:flex;gap:8px;margin-top:11px;flex-wrap:wrap;align-items:center}',
    '.fbk button{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:14px;',
    ' letter-spacing:.12em;text-transform:uppercase;padding:10px 16px;border:0;cursor:pointer}',
    '.fbk .save{background:#157bba;color:#fff}',
    '.fbk .ok{background:#1a7f4b;color:#fff}',
    '.fbk .undo{background:#fff;border:1px solid #cfe2f2;color:#5a6068}',
    '.fbk button:focus-visible{outline:3px solid #a9761f;outline-offset:2px}',
    '.fbk button[disabled]{opacity:.5;cursor:default}',
    '.fbk__m{font-size:13px;color:#5a6068;margin-left:auto}',
    '.fbk__hist{margin-top:12px;border-top:1px dashed #cbd8e2;padding-top:10px;font-size:13px;color:#5a6068}',
    '.fbk__hist b{color:#1a1a2e;font-weight:600}',
    '.fbk__hist div{margin-top:5px}'
  ].join('');
  document.head.appendChild(css);

  function el(tag, cls, txt) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (txt != null) n.textContent = txt;
    return n;
  }

  function build(host, slug, state) {
    host.innerHTML = '';
    var box = el('div', 'fbk');

    var head = el('div', 'fbk__h');
    head.appendChild(el('span', 'fbk__t', 'Your feedback'));
    var badge = el('span', 'fbk__s', 'Not reviewed');
    head.appendChild(badge);
    box.appendChild(head);

    var body = el('div', 'fbk__b');
    var ta = el('textarea');
    ta.placeholder = 'What needs changing? Wording, specs, image, anything.';
    ta.setAttribute('aria-label', 'Feedback for ' + slug);
    body.appendChild(ta);

    var row = el('div', 'fbk__r');
    var save = el('button', 'save', 'Save feedback');
    var ok   = el('button', 'ok', '✓ Correct — ready to publish');
    var undo = el('button', 'undo', 'Reopen');
    var msg  = el('span', 'fbk__m', '');
    row.appendChild(save); row.appendChild(ok); row.appendChild(undo); row.appendChild(msg);
    body.appendChild(row);

    var hist = el('div', 'fbk__hist');
    body.appendChild(hist);
    box.appendChild(body);
    host.appendChild(box);

    function paint(s) {
      s = s || {};
      ta.value = s.note || '';
      var st = s.status || 'new';
      badge.className = 'fbk__s' + (st === 'approved' ? ' ok' : st === 'changes' ? ' chg' : '');
      badge.textContent = st === 'approved' ? 'Approved — ready'
                        : st === 'changes'  ? 'Changes requested'
                        : 'Not reviewed';
      msg.textContent = s.updated ? 'saved ' + s.updated : '';
      undo.style.display = st === 'approved' ? '' : 'none';
      ok.disabled = (st === 'approved');

      hist.innerHTML = '';
      var h = (s.history || []).slice(0, -1);          // last entry is the current note
      if (h.length) {
        hist.appendChild(el('b', null, 'Earlier notes'));
        h.slice().reverse().forEach(function (e) {
          hist.appendChild(el('div', null, e.at + ' — ' + e.note));
        });
      }
    }

    function post(status) {
      [save, ok, undo].forEach(function (b) { b.disabled = true; });
      msg.textContent = 'saving…';
      fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slug: slug, status: status, note: ta.value })
      })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) throw new Error(j.error || 'save failed');
        return fetch(API).then(function (r) { return r.json(); });
      })
      .then(function (j) { paint((j.data || {})[slug]); })
      .catch(function (e) { msg.textContent = 'could not save — ' + e.message; })
      .finally(function () { [save, ok, undo].forEach(function (b) { b.disabled = false; }); });
    }

    save.addEventListener('click', function () { post('changes'); });
    ok.addEventListener('click',   function () { post('approved'); });
    undo.addEventListener('click', function () { post('changes'); });

    paint(state);
  }

  var hosts = [].slice.call(document.querySelectorAll('[data-feedback]'));
  if (!hosts.length) return;

  fetch(API)
    .then(function (r) { return r.json(); })
    .then(function (j) {
      var data = j.data || {};
      hosts.forEach(function (h) { build(h, h.getAttribute('data-feedback'), data[h.getAttribute('data-feedback')]); });
    })
    .catch(function () {
      hosts.forEach(function (h) { build(h, h.getAttribute('data-feedback'), null); });
    });
})();
