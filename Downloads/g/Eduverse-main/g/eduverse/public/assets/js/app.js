(function () {
  'use strict';
  var root = document.documentElement;
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }

  // Theme and font size (preferences kept in this browser only)
  var theme = store('ev-theme') || (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  root.setAttribute('data-theme', theme);
  var size = parseInt(store('ev-font') || '100', 10);
  root.style.fontSize = size + '%';
  document.addEventListener('click', function (e) {
    var t = e.target;
    if (t.hasAttribute && t.hasAttribute('data-print')) { window.print(); }
    if (t.id === 'theme-toggle') { theme = theme === 'dark' ? 'light' : 'dark'; root.setAttribute('data-theme', theme); store('ev-theme', theme); }
    if (t.dataset && t.dataset.font) { size = Math.min(150, Math.max(80, size + 10 * parseInt(t.dataset.font, 10))); root.style.fontSize = size + '%'; store('ev-font', String(size)); }
  });

  var csrf = (document.querySelector('meta[name=csrf]') || {}).content || '';
  var base = (document.querySelector('meta[name=base]') || {}).content || '';

  // Course chat: poll for new messages. Text is inserted with textContent (no HTML injection).
  var log = document.getElementById('chat-log');
  if (log) {
    var course = log.dataset.course, last = 0, errBox = document.getElementById('chat-error');
    var poll = function () {
      fetch(base + '/chat/poll?course=' + encodeURIComponent(course) + '&after=' + last, { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error('Chat unavailable'); return r.json(); })
        .then(function (d) {
          errBox.textContent = '';
          d.messages.forEach(function (m) {
            var p = document.createElement('p'), b = document.createElement('strong');
            b.textContent = m.name + ': '; p.appendChild(b); p.appendChild(document.createTextNode(m.body));
            log.appendChild(p); last = Math.max(last, parseInt(m.id, 10));
          });
          if (d.messages.length) log.scrollTop = log.scrollHeight;
        })
        .catch(function (e) { errBox.textContent = e.message + '. Retrying...'; });
    };
    poll(); setInterval(poll, 4000);
    document.getElementById('chat-form').addEventListener('submit', function (ev) {
      ev.preventDefault();
      var input = document.getElementById('chat-input'), body = new URLSearchParams();
      body.set('course_id', course); body.set('body', input.value);
      fetch(base + '/chat/send', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf }, body: body })
        .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || 'Could not send'); }); })
        .then(function () { input.value = ''; poll(); })
        .catch(function (e) { errBox.textContent = e.message; });
    });
  }

  // Browser speech features (only shown where the browser supports them)
  var tts = document.getElementById('tts'), stt = document.getElementById('stt');
  if (tts && 'speechSynthesis' in window) {
    tts.hidden = false;
    tts.addEventListener('click', function () {
      var a = document.querySelectorAll('.answer-text'); if (!a.length) return;
      speechSynthesis.cancel(); speechSynthesis.speak(new SpeechSynthesisUtterance(a[a.length - 1].textContent));
    });
  }
  var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (stt && SR) {
    stt.hidden = false;
    stt.addEventListener('click', function () {
      var rec = new SR(); rec.lang = 'en-US';
      rec.onresult = function (ev) { document.getElementById('question').value = ev.results[0][0].transcript; };
      rec.start();
    });
  }
})();
