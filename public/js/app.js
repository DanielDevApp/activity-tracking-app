/* Activity Tracking App: small progressive enhancements.
   The main job here is the "wake-up" handling for free hosting: when the server
   is asleep, people see a friendly "Connecting to server" screen instead of a browser error. */
(() => {
  const WAKE_URL = document.body.dataset.wakeUrl || '/wake';

  // ---------- Password visibility ----------
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-password-toggle]');
    if (!toggle) return;

    const field = toggle.closest('.field--password');
    const input = field && field.querySelector('input');
    if (!input) return;

    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    toggle.classList.toggle('is-visible', !showing);
    toggle.setAttribute('aria-pressed', String(!showing));
    toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
  });

  // ---------- Sidebar active state ----------
  document.querySelectorAll('[data-nav-item]').forEach((link) => {
    link.addEventListener('click', () => {
      document.querySelectorAll('[data-nav-item]').forEach((item) => {
        item.classList.remove('is-active');
        item.removeAttribute('aria-current');
      });
      link.classList.add('is-active');
      link.setAttribute('aria-current', 'page');
    });
  });

  // ---------- Theme toggle ----------
  document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-theme-toggle]')) return;
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem('theme', next); } catch (_) {}
  });

  // ---------- Mobile nav ----------
  const navToggle = document.querySelector('[data-nav-toggle]');
  const sidebar = document.querySelector('.sidebar');
  if (navToggle && sidebar) {
    navToggle.addEventListener('click', () => {
      const isOpen = sidebar.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
    });

    sidebar.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        sidebar.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // ---------- Server wake-up ----------
  async function ping(timeoutMs) {
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), timeoutMs);
    try {
      const res = await fetch(`${WAKE_URL}?t=${Date.now()}`, { cache: 'no-store', signal: ctrl.signal, credentials: 'omit' });
      if (!res.ok) return false;
      const data = await res.json();
      return data && data.ok === true;
    } catch (_) {
      return false;
    } finally {
      clearTimeout(timer);
    }
  }

  const overlay = document.getElementById('wake');
  const els = overlay && {
    title: document.getElementById('wake-title'),
    msg: document.getElementById('wake-msg'),
    time: document.getElementById('wake-time'),
    cancel: document.getElementById('wake-cancel'),
  };

  function show(title, msg) {
    if (!overlay) return;
    overlay.classList.remove('is-ok');
    els.title.textContent = title;
    els.msg.textContent = msg;
    overlay.hidden = false;
  }
  function hide() { if (overlay) overlay.hidden = true; }

  function progressMessage(seconds) {
    if (seconds < 5) return 'Contacting the server…';
    if (seconds < 20) return 'The server was resting to save resources and is waking up now. You don’t need to do anything.';
    if (seconds < 60) return 'Still starting up. The first visit after a quiet period can take up to a minute.';
    return 'This is taking longer than usual. We’ll keep trying. If it continues, check your internet connection.';
  }

  /**
   * Resolves true when the server answers, false if the person cancels.
   * alwaysShow: show the overlay immediately (sign-in); otherwise only if the
   * server does not answer within 1.5 seconds.
   */
  function ensureAwake(alwaysShow, title) {
    return new Promise((resolve) => {
      let cancelled = false;
      let shown = false;
      const started = Date.now();
      let ticker = null;

      const reveal = () => {
        if (shown || cancelled) return;
        shown = true;
        show(title || 'Connecting to server…', progressMessage(0));
        ticker = setInterval(() => {
          const s = Math.floor((Date.now() - started) / 1000);
          els.msg.textContent = progressMessage(s);
          els.time.textContent = `${s}s`;
        }, 1000);
      };
      const finish = (ok) => {
        clearInterval(ticker);
        clearTimeout(revealTimer);
        resolve(ok);
      };

      if (els) els.cancel.onclick = () => { cancelled = true; hide(); finish(false); };
      if (alwaysShow) reveal();
      const revealTimer = alwaysShow ? null : setTimeout(reveal, 1500);

      (async () => {
        let delay = 0;
        while (!cancelled) {
          if (delay) await new Promise((r) => setTimeout(r, delay));
          if (cancelled) return;
          if (await ping(10000)) {
            if (cancelled) return;
            if (shown && overlay) {
              overlay.classList.add('is-ok');
              els.title.textContent = 'Connected';
              els.msg.textContent = alwaysShow ? 'Signing you in…' : 'Server is ready. Continuing…';
              els.time.textContent = '';
            }
            return finish(true);
          }
          delay = 2000;
        }
      })();
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Auto-submit date pickers
    document.querySelectorAll('[data-autosubmit]').forEach((el) =>
      el.addEventListener('change', () => el.form && el.form.submit())
    );

    // Confirm destructive actions (registered before the wake handler on purpose)
    document.querySelectorAll('form[data-confirm]').forEach((form) =>
      form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
      })
    );

    // Every POST form: make sure the server is awake before sending
    document.querySelectorAll('form').forEach((form) => {
      if ((form.method || '').toLowerCase() !== 'post') return;
      const isLogin = form.dataset.wake === 'login';

      form.addEventListener('submit', async (e) => {
        if (e.defaultPrevented || form.dataset.awake === '1') return;
        e.preventDefault();

        const buttons = form.querySelectorAll('button:not([type="button"])');
        buttons.forEach((b) => (b.disabled = true));

        const ok = await ensureAwake(isLogin, isLogin ? 'Connecting to server…' : 'Reconnecting…');
        if (!ok) { buttons.forEach((b) => (b.disabled = false)); return; }

        form.dataset.awake = '1';
        form.submit();
      });
    });

    // Back/forward cache: undo any "waiting" state
    window.addEventListener('pageshow', (e) => {
      if (!e.persisted) return;
      hide();
      document.querySelectorAll('form').forEach((f) => {
        delete f.dataset.awake;
        f.querySelectorAll('button').forEach((b) => (b.disabled = false));
      });
    });

    // Sign-in page: show server state and warm the server up while the person types
    const state = document.querySelector('[data-server-state]');
    if (state) {
      const label = state.querySelector('span');
      (async () => {
        if (await ping(4000)) { state.classList.add('is-up'); label.textContent = 'Server is online'; return; }
        label.textContent = 'Server is waking up. You can already type your details.';
        while (!(await ping(10000))) await new Promise((r) => setTimeout(r, 2500));
        state.classList.add('is-up');
        label.textContent = 'Server is online';
      })();
    }

    // Today page: filter chips
    const chips = document.querySelectorAll('[data-filter]');
    if (chips.length) {
      chips.forEach((chip) =>
        chip.addEventListener('click', () => {
          const wanted = chip.dataset.filter;
          chips.forEach((c) => c.classList.toggle('is-active', c === chip));
          document.querySelectorAll('[data-item-status]').forEach((item) => {
            item.hidden = wanted !== 'all' && item.dataset.itemStatus !== wanted;
          });
        })
      );
    }

    // Update dialog
    const dialog = document.getElementById('update-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const form = document.getElementById('update-form');
    const title = document.getElementById('dlg-title');
    const remark = document.getElementById('dlg-remark');
    const hint = document.getElementById('dlg-hint');
    const radios = form.querySelectorAll('input[name="status"]');
    const current = () => form.querySelector('input[name="status"]:checked')?.value;

    const syncRequired = () => {
      const pending = current() === 'pending';
      remark.required = pending;
      hint.style.fontWeight = pending ? '700' : '400';
    };
    radios.forEach((r) => r.addEventListener('change', syncRequired));

    document.querySelectorAll('[data-update]').forEach((btn) =>
      btn.addEventListener('click', () => {
        form.action = btn.dataset.action;
        title.textContent = btn.dataset.title;
        remark.value = btn.dataset.remark || '';
        radios.forEach((r) => (r.checked = r.value === (btn.dataset.status || 'done')));
        syncRequired();
        dialog.showModal();
        remark.focus();
      })
    );

    dialog.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); });
  });
})();
