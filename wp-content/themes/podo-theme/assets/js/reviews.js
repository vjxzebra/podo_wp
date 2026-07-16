/* Podo Theme — попап відгуку: модалка, reCAPTCHA v3 (action=review), сабміт без перезавантаження */
(function () {
  'use strict';

  var modal = document.querySelector('[data-review-modal]');
  if (!modal || typeof window.podoReview === 'undefined') {
    return;
  }

  var wrap = modal.querySelector('[data-review-form-wrap]');
  var form = modal.querySelector('.review-form');
  var errorBox = modal.querySelector('[data-review-error]');
  var successBox = modal.querySelector('[data-review-success]');
  var button = form.querySelector('button[type="submit"]');
  var lastFocus = null;

  function showError(message) {
    errorBox.textContent = message;
    errorBox.classList.add('is-visible');
  }

  /* ---- Відкриття / закриття ---- */
  function openModal() {
    lastFocus = document.activeElement;
    modal.hidden = false;
    document.body.classList.add('modal-open');
    var first = wrap.hidden ? modal.querySelector('.modal-close') : form.elements.name;
    if (first) { first.focus(); }
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('modal-open');
    if (lastFocus && typeof lastFocus.focus === 'function') { lastFocus.focus(); }
  }

  Array.prototype.forEach.call(document.querySelectorAll('[data-review-open]'), function (btn) {
    btn.addEventListener('click', openModal);
  });

  modal.addEventListener('click', function (e) {
    if (e.target.closest('[data-review-close]') || e.target.classList.contains('modal-backdrop')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (modal.hidden) { return; }
    if (e.key === 'Escape') {
      closeModal();
      return;
    }
    // фокус-трап: Tab не має виходити за межі модалки
    if (e.key === 'Tab') {
      var focusable = modal.querySelectorAll('button, input, select, textarea, a[href]');
      var items = Array.prototype.filter.call(focusable, function (el) {
        return !el.disabled && el.offsetParent !== null;
      });
      if (!items.length) { return; }
      var firstEl = items[0];
      var lastEl = items[items.length - 1];
      if (e.shiftKey && document.activeElement === firstEl) {
        e.preventDefault();
        lastEl.focus();
      } else if (!e.shiftKey && document.activeElement === lastEl) {
        e.preventDefault();
        firstEl.focus();
      }
    }
  });

  /* ---- Відправлення ---- */
  function send(payload) {
    button.disabled = true;

    fetch(window.podoReview.endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': window.podoReview.restNonce
      },
      body: JSON.stringify(payload)
    })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          wrap.hidden = true;
          if (successBox) {
            successBox.hidden = false;
          }
          var closeBtn = modal.querySelector('.modal-close');
          if (closeBtn) { closeBtn.focus(); }
        } else {
          // протермінований X-WP-Nonce ядро відхиляє своїм сухим "Cookie check failed"
          var code = result.data && result.data.code;
          if (code === 'rest_cookie_invalid_nonce') {
            showError(window.podoReview.i18n.stale);
          } else {
            showError((result.data && result.data.message) || window.podoReview.i18n.error);
          }
        }
      })
      .catch(function () {
        showError(window.podoReview.i18n.error);
      })
      .finally(function () {
        button.disabled = false;
      });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorBox.classList.remove('is-visible');

    var ratingEl = form.querySelector('input[name="rating"]:checked');
    var payload = {
      name: form.elements.name.value.trim(),
      rating: ratingEl ? parseInt(ratingEl.value, 10) : 0,
      service: form.elements.service.value,
      text: form.elements.text.value.trim(),
      website: form.elements.website.value,
      nonce: window.podoReview.nonce
    };

    if (!payload.name || !payload.text) {
      showError(window.podoReview.i18n.required);
      return;
    }

    if (payload.text.length < 10) {
      showError(window.podoReview.i18n.tooShort);
      return;
    }

    if (!payload.rating) {
      showError(window.podoReview.i18n.rating);
      return;
    }

    // reCAPTCHA v3: невидима, токен отримуємо перед відправленням
    if (window.podoReview.recaptcha) {
      if (!(window.grecaptcha && typeof window.grecaptcha.execute === 'function')) {
        showError(window.podoReview.i18n.captchaWait);
        return;
      }

      button.disabled = true;
      var captchaDone = false;
      // запобіжник: невалідний ключ / підвислий запит не мають лишати кнопку заблокованою
      var captchaTimer = setTimeout(failCaptcha, 10000);

      function failCaptcha() {
        if (captchaDone) { return; }
        captchaDone = true;
        clearTimeout(captchaTimer);
        button.disabled = false;
        showError(window.podoReview.i18n.captchaFail);
      }

      try {
        window.grecaptcha.ready(function () {
          try {
            window.grecaptcha.execute(window.podoReview.recaptchaKey, { action: 'review' })
              .then(function (token) {
                if (captchaDone) { return; }
                captchaDone = true;
                clearTimeout(captchaTimer);
                payload.recaptcha = token;
                send(payload);
              })
              .catch(failCaptcha);
          } catch (err) {
            failCaptcha();
          }
        });
      } catch (err) {
        failCaptcha();
      }
      return;
    }

    send(payload);
  });
})();
