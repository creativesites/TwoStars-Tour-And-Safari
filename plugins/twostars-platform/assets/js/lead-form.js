(function () {
  function setStatus(form, message, isError) {
    var status = form.querySelector('[data-twostars-lead-form-status]');

    if (!status) {
      return;
    }

    status.textContent = message;
    status.dataset.state = isError ? 'error' : 'success';
  }

  function serializeForm(form) {
    var data = new FormData(form);
    var payload = {};
    var sessionState = window.twoStarsGenUi && typeof window.twoStarsGenUi.getSessionState === 'function'
      ? window.twoStarsGenUi.getSessionState()
      : null;

    data.forEach(function (value, key) {
      payload[key] = value;
    });

    /* Extra fields (ship times, pickup point...) are folded into the message so no backend change is needed. */
    var extraLines = [];

    form.querySelectorAll('[name^="extra_"]').forEach(function (input) {
      var value = (input.value || '').trim();

      if (value) {
        extraLines.push((input.getAttribute('data-extra-label') || input.name) + ': ' + value);
      }

      delete payload[input.name];
    });

    if (extraLines.length) {
      payload.message = extraLines.join('\n') + (payload.message ? '\n\n' + payload.message : '');
    }

    if (!data.get('contact_consent')) {
      payload.contact_consent = '';
    }

    if (sessionState) {
      payload.session_key = sessionState.session_key || '';
      payload.visitor_profile_id = Number(sessionState.visitor_profile_id || 0);
    }

    return payload;
  }

  async function handleSubmit(event) {
    event.preventDefault();

    var form = event.currentTarget;
    var button = form.querySelector('button[type="submit"]');
    var payload = serializeForm(form);

    if (!payload.email && !payload.phone) {
      setStatus(form, 'Enter an email address or phone number.', true);
      return;
    }

    button.disabled = true;
    setStatus(form, 'Sending inquiry...', false);

    try {
      var response = await fetch(window.twoStarsCrm.restUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      var result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || window.twoStarsCrm.errorMessage);
      }

      form.reset();
      window.dispatchEvent(new CustomEvent('twostars:leadCaptured', { detail: result }));
      setStatus(form, result.status || window.twoStarsCrm.successMessage, false);
    } catch (error) {
      setStatus(form, error.message || window.twoStarsCrm.errorMessage, true);
    } finally {
      button.disabled = false;
    }
  }

  document.querySelectorAll('[data-twostars-lead-form]').forEach(function (form) {
    form.addEventListener('submit', handleSubmit);
  });
})();
