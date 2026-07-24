/**
 * Luxury Villa Theme Core — front-end behaviour.
 * Mobile drawer, taxonomy mega menu, cache-safe inquiry submit with analytics.
 * No dependencies. Enqueued in footer by inc/conversion/inquiry-frontend.php.
 */
(function () {
  'use strict';

  // ── Conversion event tracking (measurement) ───────────────────────
  // Fires GA4 (gtag) + dataLayer events for the key direct-booking signals so
  // every conversion path is measurable. Delegated at the document level, so it
  // covers every current and future WhatsApp / phone link with no per-template
  // wiring. gtag is provided by Site Kit / GA4; the dataLayer push covers GTM.
  function lvcTrack(name, params) {
    try {
      if (window.gtag) { window.gtag('event', name, params || {}); }
      if (window.dataLayer) { window.dataLayer.push(Object.assign({ event: name }, params || {})); }
    } catch (_) {}
  }
  document.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    if (!a) { return; }
    var href = a.getAttribute('href') || '';
    if (/(^|\/\/)(wa\.me|wa\.link|api\.whatsapp\.com)\//i.test(href) || /[?&]phone=\d/i.test(href)) {
      lvcTrack('whatsapp_click', { link_url: href, location: a.getAttribute('data-lvc-loc') || 'link' });
    } else if (/^tel:/i.test(href)) {
      lvcTrack('call_click', { phone: href.replace(/^tel:/i, '') });
    }
  }, true);


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

  // ── Photo slider ───────────────────────────────────────────────────────
  // CSS scroll-snap does the scrolling; JS only drives the arrows and the
  // counter. No carousel library: the markup stays usable (and scrollable by
  // touch, trackpad and keyboard) even if this script never runs.
  Array.prototype.forEach.call(document.querySelectorAll('[data-lvc-slider]'), function (slider) {
    var track = slider.querySelector('[data-lvc-slider-track]');
    var prev = slider.querySelector('[data-lvc-slider-prev]');
    var next = slider.querySelector('[data-lvc-slider-next]');
    var current = slider.querySelector('[data-lvc-slider-current]');
    if (!track) { return; }

    var slides = track.children;

    var step = function () {
      return slides.length ? slides[0].getBoundingClientRect().width : track.clientWidth;
    };

    var index = function () {
      var s = step();
      return s ? Math.round(track.scrollLeft / s) : 0;
    };

    var sync = function () {
      var i = index();
      if (current) { current.textContent = String(Math.min(i + 1, slides.length)); }
      if (prev) { prev.disabled = track.scrollLeft <= 1; }
      if (next) { next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 1; }
    };

    var go = function (dir) {
      track.scrollBy({ left: dir * step(), behavior: 'smooth' });
    };

    if (prev) { prev.addEventListener('click', function () { go(-1); }); }
    if (next) { next.addEventListener('click', function () { go(1); }); }
    track.addEventListener('scroll', function () {
      window.clearTimeout(track._lvcT);
      track._lvcT = window.setTimeout(sync, 80);
    }, { passive: true });
    track.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); go(1); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); go(-1); }
    });

    sync();
  });

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
                lvcTrack('generate_lead', { form: 'inquiry', property: prop });
                lvcTrack('inquiry_submit', { form: 'inquiry', property: prop });
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
