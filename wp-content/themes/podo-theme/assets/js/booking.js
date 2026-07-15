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
      errorBox.textContent = window.podoBooking.i18n.required;
      errorBox.classList.add('is-visible');
      return;
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
          var message = (result.data && result.data.message) || window.podoBooking.i18n.error;
          errorBox.textContent = message;
          errorBox.classList.add('is-visible');
        }
      })
      .catch(function () {
        errorBox.textContent = window.podoBooking.i18n.error;
        errorBox.classList.add('is-visible');
      })
      .finally(function () {
        button.disabled = false;
      });
  });
})();
