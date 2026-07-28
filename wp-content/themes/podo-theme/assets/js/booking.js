/* Podo Theme — форма заявки: маска телефону, reCAPTCHA v3, сабміт без перезавантаження */
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
  var phoneInput = form.elements.phone;

  var PHONE_RE = /^\+380 \d{2} \d{3} \d{2} \d{2}$/;
  var PHONE_PREFIX = '+380 ';

  function showError(message) {
    errorBox.textContent = message;
    errorBox.classList.add('is-visible');
  }

  /* ---- Маска телефону: +380 XX XXX XX XX ---- */
  function uaSubscriberDigits(raw) {
    var value = String(raw);
    var digits = value.replace(/\D/g, '');

    // Видалений частково префікс не має перетворюватися на цифри номера.
    if (/^\s*\+(?:3(?:8(?:0)?)?)?\s*$/.test(value)) {
      return '';
    }

    if (digits.indexOf('380') === 0) {
      digits = digits.slice(3);
    } else if (digits.indexOf('80') === 0 && digits.length >= 11) {
      digits = digits.slice(2);
    }

    // Користувачі звично вводять 0 після вже видимого +380.
    if (digits.indexOf('0') === 0) {
      digits = digits.slice(1);
    }

    return digits.slice(0, 9);
  }

  function formatUaPhone(raw) {
    var d = uaSubscriberDigits(raw);
    var out = PHONE_PREFIX;
    if (d.length > 0) { out += d.slice(0, 2); }
    if (d.length > 2) { out += ' ' + d.slice(2, 5); }
    if (d.length > 5) { out += ' ' + d.slice(5, 7); }
    if (d.length > 7) { out += ' ' + d.slice(7, 9); }
    return out;
  }

  function caretPosition(formatted, digitCount) {
    if (digitCount <= 0) {
      return PHONE_PREFIX.length;
    }

    var seen = 0;
    for (var pos = PHONE_PREFIX.length; pos < formatted.length; pos++) {
      if (/\d/.test(formatted.charAt(pos))) {
        seen++;
        if (seen === digitCount) {
          return pos + 1;
        }
      }
    }
    return formatted.length;
  }

  phoneInput.addEventListener('focus', function () {
    if (!phoneInput.value.trim()) {
      phoneInput.value = PHONE_PREFIX;
      try { phoneInput.setSelectionRange(PHONE_PREFIX.length, PHONE_PREFIX.length); } catch (err) { /* type=tel у старих браузерах */ }
    }
  });

  phoneInput.addEventListener('blur', function () {
    if (uaSubscriberDigits(phoneInput.value) === '') {
      phoneInput.value = '';
    }
  });

  phoneInput.addEventListener('input', function () {
    var selectionStart = phoneInput.selectionStart === null
      ? phoneInput.value.length
      : phoneInput.selectionStart;
    var caretDigits = uaSubscriberDigits(phoneInput.value.slice(0, selectionStart)).length;
    var formatted = formatUaPhone(phoneInput.value);
    if (formatted === phoneInput.value) {
      return;
    }
    phoneInput.value = formatted;
    var pos = caretPosition(formatted, caretDigits);
    try { phoneInput.setSelectionRange(pos, pos); } catch (err) { /* type=tel у старих браузерах */ }
  });

  phoneInput.addEventListener('keydown', function (e) {
    var start = phoneInput.selectionStart;
    var end = phoneInput.selectionEnd;
    if (
      e.key === 'Backspace'
      && start !== null
      && start === end
      && start <= PHONE_PREFIX.length
    ) {
      e.preventDefault();
    }
  });

  phoneInput.addEventListener('click', function () {
    var start = phoneInput.selectionStart;
    var end = phoneInput.selectionEnd;
    if (start !== null && start === end && start < PHONE_PREFIX.length) {
      try { phoneInput.setSelectionRange(PHONE_PREFIX.length, PHONE_PREFIX.length); } catch (err) { /* type=tel у старих браузерах */ }
    }
  });

  /* ---- Відправлення ---- */
  function send(payload) {
    button.disabled = true;

    fetch(window.podoBooking.endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': window.podoBooking.restNonce
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
        } else {
          // протермінований X-WP-Nonce ядро відхиляє своїм сухим "Cookie check failed"
          var code = result.data && result.data.code;
          if (code === 'rest_cookie_invalid_nonce') {
            showError(window.podoBooking.i18n.stale);
          } else {
            showError((result.data && result.data.message) || window.podoBooking.i18n.error);
          }
        }
      })
      .catch(function () {
        showError(window.podoBooking.i18n.error);
      })
      .finally(function () {
        button.disabled = false;
      });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorBox.classList.remove('is-visible');

    // автозаповнення браузера може не кинути 'input' — нормалізуємо перед перевіркою
    if (phoneInput.value.trim() !== '') {
      phoneInput.value = formatUaPhone(phoneInput.value);
    }

    var payload = {
      name: form.elements.name.value.trim(),
      phone: phoneInput.value.trim(),
      service: form.elements.service.value,
      comment: form.elements.comment.value.trim(),
      website: form.elements.website.value,
      nonce: window.podoBooking.nonce
    };

    if (!payload.name || !payload.phone) {
      showError(window.podoBooking.i18n.required);
      return;
    }

    if (!PHONE_RE.test(payload.phone)) {
      showError(window.podoBooking.i18n.phone);
      return;
    }

    // reCAPTCHA v3: невидима, токен отримуємо перед відправленням
    if (window.podoBooking.recaptcha) {
      if (!(window.grecaptcha && typeof window.grecaptcha.execute === 'function')) {
        showError(window.podoBooking.i18n.captchaWait);
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
        showError(window.podoBooking.i18n.captchaFail);
      }

      try {
        window.grecaptcha.ready(function () {
          try {
            window.grecaptcha.execute(window.podoBooking.recaptchaKey, { action: 'booking' })
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
