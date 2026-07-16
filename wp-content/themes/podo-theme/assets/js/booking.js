/* Podo Theme — сабміт форми заявки без перезавантаження */
(function () {
  'use strict';

  var wrap = document.querySelector('[data-booking-form-wrap]');
  if (!wrap || typeof window.podoBooking === 'undefined') {
    return;
  }

  var form = wrap.querySelector('.booking-form');
  var errorBox = wrap.querySelector('[data-booking-error]');
  var successBox = document.querySelector('[data-booking-success]');
  var button = form.querySelector('button[type="submit"]');

  // null — скрипт капчі ще не довантажився; '' — віджет є, але не позначений
  function getCaptchaToken() {
    if (!(window.grecaptcha && typeof window.grecaptcha.getResponse === 'function')) {
      return null;
    }
    try {
      return window.grecaptcha.getResponse() || '';
    } catch (e) {
      return null;
    }
  }

  function resetCaptcha() {
    if (window.podoBooking.recaptcha && window.grecaptcha && typeof window.grecaptcha.reset === 'function') {
      try { window.grecaptcha.reset(); } catch (e) { /* віджет ще не відрендерився */ }
    }
  }

  function showError(message) {
    errorBox.textContent = message;
    errorBox.classList.add('is-visible');
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorBox.classList.remove('is-visible');

    var payload = {
      name: form.elements.name.value.trim(),
      phone: form.elements.phone.value.trim(),
      service: form.elements.service.value,
      comment: form.elements.comment.value.trim(),
      website: form.elements.website.value,
      nonce: window.podoBooking.nonce
    };

    if (!payload.name || !payload.phone) {
      showError(window.podoBooking.i18n.required);
      return;
    }

    if (window.podoBooking.recaptcha) {
      var captchaToken = getCaptchaToken();
      if (captchaToken === null) {
        showError(window.podoBooking.i18n.captchaWait);
        return;
      }
      if (!captchaToken) {
        showError(window.podoBooking.i18n.captcha);
        return;
      }
      payload.recaptcha = captchaToken;
    }

    button.disabled = true;

    fetch(window.podoBooking.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
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
        } else {
          showError((result.data && result.data.message) || window.podoBooking.i18n.error);
          resetCaptcha();
        }
      })
      .catch(function () {
        showError(window.podoBooking.i18n.error);
        resetCaptcha();
      })
      .finally(function () {
        button.disabled = false;
      });
  });
})();
