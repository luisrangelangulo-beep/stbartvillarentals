/**
 * Luxury Villa Theme Core — front-end behaviour.
 * Mobile drawer, taxonomy mega menu, cache-safe inquiry submit with analytics.
 * No dependencies. Enqueued in footer by inc/conversion/inquiry-frontend.php.
 */
(function () {
  'use strict';

  // ── Mobile nav drawer ──────────────────────────────────────────────────
  var toggle = document.querySelector('[data-lvc-drawer-toggle]');
  var drawer = document.querySelector('[data-lvc-drawer]');
  if (toggle && drawer) {
    toggle.addEventListener('click', function () {
      var closed = drawer.hasAttribute('hidden');
      if (closed) { drawer.removeAttribute('hidden'); } else { drawer.setAttribute('hidden', ''); }
      toggle.setAttribute('aria-expanded', closed ? 'true' : 'false');
    });
  }

  // ── Taxonomy mega menu ─────────────────────────────────────────────────
  // Click to open (not hover): keyboard-reachable and works on touch, where a
  // hover panel is either unreachable or fires on the tap meant to follow a link.
  var megaWrap = document.querySelector('[data-lvc-mega-wrap]');
  var megaBtn = megaWrap && megaWrap.querySelector('[data-lvc-mega-toggle]');
  if (megaWrap && megaBtn) {
    var setMega = function (open) {
      megaWrap.classList.toggle('is-open', open);
      megaBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    megaBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      setMega(megaBtn.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('click', function (e) {
      if (!megaWrap.contains(e.target)) { setMega(false); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && megaBtn.getAttribute('aria-expanded') === 'true') {
        setMega(false);
        megaBtn.focus();
      }
    });
  }

  // ── Inquiry forms ──────────────────────────────────────────────────────
  var cfg = window.LVC_INQ || {};
  var forms = document.querySelectorAll('[data-lvc-inquiry]');

  Array.prototype.forEach.call(forms, function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var status = form.querySelector('[data-inquiry-status]');
      var btn = form.querySelector('[type="submit"]');
      var setStatus = function (m) { if (status) { status.textContent = m; } };

      if (btn) { btn.disabled = true; }
      setStatus('Sending…');

      var send = function () {
        var fd = new FormData(form);
        return fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'Unexpected response. Please try again.' } }; }); })
          .then(function (j) {
            if (j && j.success) {
              setStatus((j.data && j.data.message) || 'Thank you. We will be in touch.');
              try {
                var propEl = form.querySelector('[name="property_name"]');
                var prop = propEl ? propEl.value : '';
                if (window.gtag) { window.gtag('event', 'generate_lead', { form: 'inquiry', property: prop }); }
                if (window.dataLayer) { window.dataLayer.push({ event: 'generate_lead', form_property: prop }); }
              } catch (_) {}
              form.reset();
            } else {
              setStatus((j && j.data && j.data.message) || 'Something went wrong. Please try again.');
            }
          })
          .catch(function () { setStatus('Network error. Please try again or message us on WhatsApp.'); })
          .then(function () { if (btn) { btn.disabled = false; } });
      };

      // Refresh the nonce first (cache-safe), then submit.
      if (cfg.nonceUrl) {
        fetch(cfg.nonceUrl, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (d) { if (d && d.nonce) { var n = form.querySelector('[name="_wpnonce"]'); if (n) { n.value = d.nonce; } } })
          .catch(function () {})
          .then(send);
      } else {
        send();
      }
    });
  });
})();
