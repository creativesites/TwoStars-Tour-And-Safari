/*
 * Consent banner and analytics loader.
 * GA4 and the Meta Pixel are only loaded after the visitor accepts. Choice is kept for 180 days.
 * Exposes window.twostarsTrack(name, params) which the site's click handlers and forms call.
 */
(function () {
  'use strict';

  var cfg = window.twostarsTracking || {};
  var COOKIE = 'ts_consent';
  var loaded = false;

  function readConsent() {
    var match = document.cookie.match(new RegExp('(?:^|; )' + COOKIE + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : '';
  }

  function writeConsent(value) {
    var expires = new Date(Date.now() + 180 * 864e5).toUTCString();
    document.cookie = COOKIE + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/; SameSite=Lax';
  }

  function loadScript(src) {
    var s = document.createElement('script');
    s.async = true;
    s.src = src;
    document.head.appendChild(s);
  }

  function loadTrackers() {
    if (loaded) {
      return;
    }

    loaded = true;
    window.dataLayer = window.dataLayer || [];

    if (cfg.ga4) {
      window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
      window.gtag('js', new Date());
      window.gtag('config', cfg.ga4, { anonymize_ip: true });
      loadScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.ga4));
    }

    if (cfg.pixel) {
      /* Standard Meta Pixel bootstrap */
      (function (f, b, e, v, n, t, s) {
        if (f.fbq) { return; }
        n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
        if (!f._fbq) { f._fbq = n; }
        n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
        t = b.createElement(e); t.async = true; t.src = v;
        s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
      })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
      window.fbq('init', cfg.pixel);
      window.fbq('track', 'PageView');
    }
  }

  var PIXEL_MAP = {
    book_now_click: ['track', 'InitiateCheckout'],
    whatsapp_click: ['trackCustom', 'WhatsAppClick'],
    form_submit: ['track', 'Lead'],
    booking_complete: ['track', 'Purchase']
  };

  window.twostarsTrack = function (name, params) {
    params = params || {};
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(Object.assign({ event: name }, params));

    if (typeof window.gtag === 'function') {
      window.gtag('event', name, params);
    }

    if (typeof window.fbq === 'function' && PIXEL_MAP[name]) {
      window.fbq(PIXEL_MAP[name][0], PIXEL_MAP[name][1]);
    }
  };

  window.addEventListener('twostars:leadCaptured', function () {
    window.twostarsTrack('form_submit', { page_path: window.location.pathname });
  });

  function showBanner() {
    var bar = document.createElement('div');
    bar.className = 'twostars-consent';
    bar.setAttribute('role', 'dialog');
    bar.setAttribute('aria-label', 'Cookie choices');
    bar.innerHTML =
      '<p>We use cookies for anonymous visitor statistics and to measure our adverts. ' +
      '<a href="' + (cfg.privacyUrl || '/privacy/') + '">Privacy</a></p>' +
      '<div class="twostars-consent__actions">' +
      '<button type="button" data-consent="denied">No thanks</button>' +
      '<button type="button" class="twostars-consent__accept" data-consent="granted">Accept</button>' +
      '</div>';

    bar.addEventListener('click', function (event) {
      var button = event.target.closest('[data-consent]');

      if (!button) {
        return;
      }

      var choice = button.getAttribute('data-consent');
      writeConsent(choice);
      bar.remove();

      if (choice === 'granted') {
        loadTrackers();
      }
    });

    document.body.appendChild(bar);
  }

  function init() {
    var consent = readConsent();

    if (consent === 'granted') {
      loadTrackers();
      return;
    }

    if (consent === '' && (cfg.ga4 || cfg.pixel)) {
      showBanner();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
