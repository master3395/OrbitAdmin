/* OrbitAdmin frontend behaviours: sidebar toggle, theme toggle, command palette. */
(function () {
  'use strict';

  const root = document.documentElement;
  const STORAGE_KEY = 'orbit:theme';

  function applyTheme(theme) {
    if (theme === 'light') {
      root.setAttribute('data-orbit-theme', 'light');
    } else {
      root.removeAttribute('data-orbit-theme');
    }
  }

  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored === 'light' || stored === 'dark') {
    applyTheme(stored);
  }

  document.addEventListener('DOMContentLoaded', function () {
    setupSidebar();
    setupThemeToggle();
    setupPalette();
    setupAutoFlash();
    setupConfirms();
  });

  function setupSidebar() {
    const toggle = document.querySelector('[data-orbit-toggle-sidebar]');
    const sidebar = document.querySelector('.orbit-sidebar');
    if (!toggle || !sidebar) return;
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (window.innerWidth > 980) return;
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  function setupThemeToggle() {
    const btn = document.querySelector('[data-orbit-theme-toggle]');
    if (!btn) return;
    btn.addEventListener('click', function () {
      const next = root.getAttribute('data-orbit-theme') === 'light' ? 'dark' : 'light';
      applyTheme(next);
      localStorage.setItem(STORAGE_KEY, next);
    });
  }

  function setupPalette() {
    const palette = document.querySelector('.orbit-palette');
    const input = palette ? palette.querySelector('.orbit-palette-input') : null;
    const list  = palette ? palette.querySelector('.orbit-palette-results') : null;
    if (!palette || !input || !list) return;

    const open = () => { palette.classList.add('open'); input.value = ''; renderItems(''); setTimeout(() => input.focus(), 10); };
    const close = () => palette.classList.remove('open');
    const items = Array.from(list.querySelectorAll('a')).map(a => ({ el: a, label: a.textContent.trim().toLowerCase() }));

    function renderItems(q) {
      const term = q.trim().toLowerCase();
      let firstShown = null;
      items.forEach(item => {
        const match = term === '' || item.label.indexOf(term) !== -1;
        item.el.style.display = match ? '' : 'none';
        item.el.classList.remove('active');
        if (match && firstShown === null) firstShown = item.el;
      });
      if (firstShown) firstShown.classList.add('active');
    }

    document.querySelectorAll('[data-orbit-palette-open]').forEach(t => t.addEventListener('click', open));
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); open(); }
      if (e.key === 'Escape' && palette.classList.contains('open')) close();
      if (e.key === 'Enter' && palette.classList.contains('open')) {
        const active = list.querySelector('a.active');
        if (active) active.click();
      }
      if (palette.classList.contains('open') && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
        e.preventDefault();
        const visible = items.map(i => i.el).filter(el => el.style.display !== 'none');
        if (!visible.length) return;
        const idx = visible.findIndex(el => el.classList.contains('active'));
        visible.forEach(el => el.classList.remove('active'));
        const next = e.key === 'ArrowDown' ? Math.min(idx + 1, visible.length - 1) : Math.max(idx - 1, 0);
        visible[Math.max(next, 0)].classList.add('active');
      }
    });
    palette.addEventListener('click', function (e) {
      if (e.target === palette) close();
    });
    input.addEventListener('input', e => renderItems(e.target.value));
  }

  function setupAutoFlash() {
    document.querySelectorAll('.orbit-alert[data-orbit-auto-dismiss]').forEach(el => {
      setTimeout(() => { el.style.transition = 'opacity .35s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 380); }, 4500);
    });
  }

  function setupConfirms() {
    document.querySelectorAll('form[data-confirm]').forEach(form => {
      form.addEventListener('submit', function (e) {
        const msg = form.getAttribute('data-confirm') || 'Are you sure?';
        if (!confirm(msg)) { e.preventDefault(); }
      });
    });
  }
})();
