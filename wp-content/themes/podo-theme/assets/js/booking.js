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

  function showError(message) {
    errorBox.textContent = message;
    errorBox.classList.add('is-visible');
  }

  /* ---- Маска телефону: +380 XX XXX XX XX ---- */
  function stripUaPrefix(d) {
    if (d.indexOf('380') === 0) { return d.slice(3); }
    if (d.indexOf('80') === 0 && d.length >= 11) { return d.slice(2); }
    if (d.indexOf('0') === 0) { return d.slice(1); }
    return d;
  }

  function formatUaPhone(raw) {
    var d = String(raw).replace(/\D/g, '');
    // залишки префікса при стиранні ("+38", "+3")
    if (d === '3' || d === '38' || d === '380') {
      d = '';
    } else {
      d = stripUaPrefix(d);
      // подвійний префікс: вставили "0..."/"380..." після автопідставленого "+380 "
      if (d.length > 9) {
        d = stripUaPrefix(d);
      }
    }
    d = d.slice(0, 9);
    var out = '+380';
    if (d.length > 0) { out += ' ' + d.slice(0, 2); }
    if (d.length > 2) { out += ' ' + d.slice(2, 5); }
    if (d.length > 5) { out += ' ' + d.slice(5, 7); }
    if (d.length > 7) { out += ' ' + d.slice(7, 9); }
    return out;
  }

  phoneInput.addEventListener('focus', function () {
    if (!phoneInput.value.trim()) {
      phoneInput.value = '+380 ';
    }
  });

  phoneInput.addEventListener('blur', function () {
    var digits = phoneInput.value.replace(/\D/g, '');
    if (digits === '' || digits === '380') {
      phoneInput.value = '';
    }
  });

  phoneInput.addEventListener('input', function () {
    // цифр перед кареткою у "сирому" значенні — щоб не стрибала каретка при редагуванні всередині
    var caretDigits = phoneInput.value.slice(0, phoneInput.selectionStart || 0).replace(/\D/g, '').length;
    var formatted = formatUaPhone(phoneInput.value);
    if (formatted === phoneInput.value) {
      return;
    }
    phoneInput.value = formatted;
    var pos = 0;
    var seen = 0;
    while (pos < formatted.length && seen < caretDigits) {
      if (/\d/.test(formatted.charAt(pos))) { seen++; }
      pos++;
    }
    try { phoneInput.setSelectionRange(pos, pos); } catch (err) { /* type=tel у старих браузерах */ }
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
