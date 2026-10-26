(function () {
  var config = window.twostarsBookingPage;

  if (!config) {
    return;
  }

  function bySelector(selector, scope) {
    return (scope || document).querySelector(selector);
  }

  function all(selector, scope) {
    return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function parseDate(value) {
    var parts = String(value || '').split('-');
    if (parts.length !== 3) {
      return null;
    }

    return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
  }

  function formatMonth(date) {
    return date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
  }

  function formatMonthKey(date) {
    return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-01';
  }

  function createPlaceholder(message) {
    return '<div class="twostars-booking-app__placeholder">' + escapeHtml(message) + '</div>';
  }

  function getUrlSelectedTourId() {
    var search = new URLSearchParams(window.location.search || '');
    var raw = search.get('tour');

    if (!raw) {
      return 0;
    }

    if (/^\d+$/.test(raw)) {
      return Number(raw);
    }

    var normalized = raw.toLowerCase().trim();
    var match = (config.tours || []).find(function (tour) {
      if (String(tour.id) === normalized) {
        return true;
      }

      var slug = String((tour.url || '').split('/').filter(Boolean).pop() || '').toLowerCase();
      return slug === normalized;
    });

    return match ? Number(match.id) : 0;
  }

  function initBookingPage() {
    var form = bySelector('[data-booking-form]');
    var datesHost = bySelector('[data-booking-dates]');
    var timesHost = bySelector('[data-booking-times]');
    var selectionHost = bySelector('[data-booking-selection]');
    var pickupSelect = bySelector('[data-booking-pickup-select]');
    var countrySelect = bySelector('[data-booking-country]');
    var countrySearch = bySelector('[data-country-search]');
    var status = bySelector('[data-booking-status]');
    var submitButton = bySelector('[data-booking-submit]');
    var monthLabel = bySelector('[data-booking-month-label]');
    var prevMonthButton = bySelector('[data-booking-prev-month]');
    var nextMonthButton = bySelector('[data-booking-next-month]');
    var successPanel = bySelector('[data-booking-success]');
    var successText = bySelector('[data-booking-success-text]');
    var successMeta = bySelector('[data-booking-success-meta]');
    var resetButton = bySelector('[data-booking-reset]');
    var stage = bySelector('[data-booking-form-stage]');
    var tourCards = all('[data-tour-card]');
    var errorNodes = all('[data-field-error]');

    if (!form || !datesHost || !timesHost || !selectionHost || !status || !submitButton || !monthLabel || !tourCards.length) {
      return;
    }

    var state = {
      selectedTourId: Number(getUrlSelectedTourId() || config.selectedTourId || 0),
      selectedDate: '',
      selectedTime: '',
      availability: [],
      displayMonth: (function () {
        var parsed = parseDate(config.today);
        return parsed ? new Date(parsed.getFullYear(), parsed.getMonth(), 1) : new Date();
      })()
    };
    var intelligenceState = window.twoStarsGenUi && typeof window.twoStarsGenUi.getSessionState === 'function'
      ? window.twoStarsGenUi.getSessionState()
      : null;

    function getIntelligenceState() {
      return window.twoStarsGenUi && typeof window.twoStarsGenUi.getSessionState === 'function'
        ? window.twoStarsGenUi.getSessionState()
        : intelligenceState;
    }

    form.elements.form_started_at.value = String(config.startedAt || Math.floor(Date.now() / 1000));

    function getTour(tourId) {
      return (config.tours || []).find(function (tour) {
        return Number(tour.id) === Number(tourId);
      }) || null;
    }

    function clearErrors() {
      errorNodes.forEach(function (node) {
        node.textContent = '';
      });

      all('.is-invalid', form).forEach(function (field) {
        field.classList.remove('is-invalid');
      });
    }

    function setFieldError(name, message) {
      var input = form.elements[name];
      var error = bySelector('[data-field-error="' + name + '"]', form);

      if (input && input.classList) {
        input.classList.add('is-invalid');
      }

      if (input && input.closest && input.closest('.twostars-booking-country')) {
        input.closest('.twostars-booking-country').classList.add('is-invalid');
      }

      if (error) {
        error.textContent = message;
      }
    }

    function setStatus(message, tone) {
      status.textContent = message;
      status.classList.remove('is-error', 'is-success');

      if (tone === 'error') {
        status.classList.add('is-error');
      }

      if (tone === 'success') {
        status.classList.add('is-success');
      }
    }

    function populatePickupOptions() {
      pickupSelect.innerHTML = '<option value="">' + escapeHtml('Select pickup area') + '</option>';

      (config.pickupOptions || []).forEach(function (optionText) {
        var option = document.createElement('option');
        option.value = optionText;
        option.textContent = optionText;
        pickupSelect.appendChild(option);
      });
    }

    function renderCountryOptions(query) {
      if (!countrySelect) {
        return;
      }

      var currentValue = countrySelect.value || '';
      var filter = String(query || '').toLowerCase().trim();
      var countries = (config.countries || []).filter(function (country) {
        return !filter || String(country).toLowerCase().indexOf(filter) !== -1;
      });

      countrySelect.innerHTML = '<option value="">' + escapeHtml('Select country') + '</option>' +
        countries.map(function (country) {
          var selected = country === currentValue ? ' selected' : '';
          return '<option value="' + escapeHtml(country) + '"' + selected + '>' + escapeHtml(country) + '</option>';
        }).join('');

      if (currentValue && countries.indexOf(currentValue) === -1) {
        var fallback = document.createElement('option');
        fallback.value = currentValue;
        fallback.textContent = currentValue;
        fallback.selected = true;
        countrySelect.appendChild(fallback);
      }
    }

    function syncVisitorPrefill() {
      var currentIntelligenceState = getIntelligenceState();

      if (!currentIntelligenceState || !currentIntelligenceState.visitor || !countrySelect) {
        return;
      }

      var visitorCountry = String(currentIntelligenceState.visitor.country_name || '').trim();

      if (visitorCountry && !countrySelect.value) {
        countrySelect.value = visitorCountry;
      }
    }

    function updateSelectionPanel() {
      var tour = getTour(state.selectedTourId);
      var selectedDate = state.availability.find(function (item) {
        return item.date === state.selectedDate;
      });
      var selectedTime = selectedDate && selectedDate.slots
        ? selectedDate.slots.find(function (slot) { return slot.value === state.selectedTime; })
        : null;

      if (!tour) {
        selectionHost.innerHTML = '' +
          '<p class="twostars-booking-app__selection-title">Choose a tour to begin.</p>' +
          '<p class="twostars-booking-app__selection-text">The route summary, key highlights, and selected date and time will appear here.</p>';
        return;
      }

      var chips = [];

      if (tour.duration) {
        chips.push('<span class="twostars-booking-app__selection-chip">' + escapeHtml(tour.duration) + '</span>');
      }

      if (tour.pickup) {
        chips.push('<span class="twostars-booking-app__selection-chip">' + escapeHtml(tour.pickup) + '</span>');
      }

      if (state.selectedDate) {
        chips.push('<span class="twostars-booking-app__selection-chip">' + escapeHtml(selectedDate ? selectedDate.label : state.selectedDate) + '</span>');
      }

      if (state.selectedTime) {
        chips.push('<span class="twostars-booking-app__selection-chip">' + escapeHtml(selectedTime ? selectedTime.label : state.selectedTime) + '</span>');
      }

      var highlights = (tour.highlights || []).map(function (item) {
        return '<span class="twostars-booking-app__story-chip">' + escapeHtml(item) + '</span>';
      }).join('');

      selectionHost.innerHTML = '' +
        '<p class="twostars-booking-app__selection-title">' + escapeHtml(tour.title) + '</p>' +
        '<p class="twostars-booking-app__selection-text">' + escapeHtml(tour.excerpt) + '</p>' +
        (highlights ? '<div class="twostars-booking-app__selection-story">' + highlights + '</div>' : '') +
        '<div class="twostars-booking-app__selection-meta">' + chips.join('') + '</div>';
    }

    function syncFormFields() {
      form.elements.tour_id.value = state.selectedTourId ? String(state.selectedTourId) : '';
      form.elements.date.value = state.selectedDate;
      form.elements.time.value = state.selectedTime;
      form.elements.origin_url.value = window.location.origin + window.location.pathname;
      submitButton.disabled = !(state.selectedTourId && state.selectedDate && state.selectedTime);
    }

    function renderTimes() {
      var selected = state.availability.find(function (item) {
        return item.date === state.selectedDate;
      });

      if (!selected || !selected.slots || !selected.slots.length) {
        timesHost.innerHTML = createPlaceholder(config.messages.noTimes);
        state.selectedTime = '';
        syncFormFields();
        updateSelectionPanel();
        return;
      }

      timesHost.innerHTML = selected.slots.map(function (slot) {
        var isSelected = slot.value === state.selectedTime;
        return '' +
          '<button type="button" class="twostars-booking-time' + (isSelected ? ' is-selected' : '') + '" data-booking-time="' + escapeHtml(slot.value) + '">' +
            '<span class="twostars-booking-time__label">' + escapeHtml(slot.label) + '</span>' +
            '<span class="twostars-booking-time__hint">Open slot</span>' +
          '</button>';
      }).join('');

      all('[data-booking-time]', timesHost).forEach(function (button) {
        button.addEventListener('click', function () {
          state.selectedTime = button.getAttribute('data-booking-time') || '';
          renderTimes();
          syncFormFields();
          updateSelectionPanel();
          setStatus('Your date and time are set. Add your details below to send the request.', '');
        });
      });
    }

    function renderCalendar() {
      var month = state.displayMonth;
      var monthKey = month.getFullYear() + '-' + String(month.getMonth() + 1).padStart(2, '0');
      var availabilityMap = {};

      state.availability.forEach(function (item) {
        availabilityMap[item.date] = item;
      });

      monthLabel.textContent = formatMonth(month);

      var firstDay = new Date(month.getFullYear(), month.getMonth(), 1);
      var lastDay = new Date(month.getFullYear(), month.getMonth() + 1, 0);
      var startOffset = (firstDay.getDay() + 6) % 7;
      var cells = [];

      for (var i = 0; i < startOffset; i += 1) {
        cells.push('<div class="twostars-booking-date twostars-booking-date--empty" aria-hidden="true"></div>');
      }

      for (var day = 1; day <= lastDay.getDate(); day += 1) {
        var dateString = monthKey + '-' + String(day).padStart(2, '0');
        var item = availabilityMap[dateString];
        var isSelected = state.selectedDate === dateString;

        if (!item) {
          cells.push(
            '<button type="button" class="twostars-booking-date is-disabled" disabled>' +
              '<span class="twostars-booking-date__number">' + day + '</span>' +
              '<span class="twostars-booking-date__count">Closed</span>' +
            '</button>'
          );
          continue;
        }

        cells.push(
          '<button type="button" class="twostars-booking-date' + (isSelected ? ' is-selected' : '') + '" data-booking-date="' + escapeHtml(item.date) + '">' +
            '<span class="twostars-booking-date__day">' + escapeHtml(item.day) + '</span>' +
            '<span class="twostars-booking-date__number">' + escapeHtml(item.dayNumber) + '</span>' +
            '<span class="twostars-booking-date__count">' + escapeHtml(String(item.available_count)) + ' open</span>' +
          '</button>'
        );
      }

      datesHost.innerHTML = cells.join('');

      all('[data-booking-date]', datesHost).forEach(function (button) {
        button.addEventListener('click', function () {
          state.selectedDate = button.getAttribute('data-booking-date') || '';
          state.selectedTime = '';
          renderCalendar();
          renderTimes();
          syncFormFields();
          updateSelectionPanel();
          setStatus('Choose one of the open times for this date.', '');
        });
      });

      renderTimes();
    }

    function fetchAvailability() {
      if (!state.selectedTourId) {
        return;
      }

      datesHost.innerHTML = createPlaceholder(config.messages.loadingDates);
      timesHost.innerHTML = createPlaceholder(config.messages.loadingTimes);
      monthLabel.textContent = formatMonth(state.displayMonth);

      fetch(config.availabilityUrl + '?tour_id=' + encodeURIComponent(state.selectedTourId) + '&days=42&start=' + encodeURIComponent(formatMonthKey(state.displayMonth)), {
        credentials: 'same-origin'
      })
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          state.availability = Array.isArray(payload.dates) ? payload.dates : [];

          if (!state.selectedDate || !state.availability.some(function (item) { return item.date === state.selectedDate; })) {
            var currentMonthKey = state.displayMonth.getFullYear() + '-' + String(state.displayMonth.getMonth() + 1).padStart(2, '0');
            var firstOpen = state.availability.find(function (item) {
              return String(item.date || '').indexOf(currentMonthKey + '-') === 0;
            });
            state.selectedDate = firstOpen ? firstOpen.date : '';
            state.selectedTime = '';
          }

          renderCalendar();
          syncFormFields();
          updateSelectionPanel();

          if (state.availability.length) {
            setStatus(config.messages.calendarHint, '');
          } else {
            setStatus(config.messages.noDates, 'error');
          }
        })
        .catch(function () {
          datesHost.innerHTML = createPlaceholder(config.messages.noDates);
          timesHost.innerHTML = createPlaceholder(config.messages.noTimes);
          setStatus(config.messages.error, 'error');
        });
    }

    function selectTour(tourId) {
      state.selectedTourId = Number(tourId || 0);
      state.selectedDate = '';
      state.selectedTime = '';
      state.displayMonth = (function () {
        var today = parseDate(config.today);
        return today ? new Date(today.getFullYear(), today.getMonth(), 1) : new Date();
      })();

      tourCards.forEach(function (card) {
        card.classList.toggle('is-selected', Number(card.getAttribute('data-tour-id')) === state.selectedTourId);
      });

      var tour = getTour(state.selectedTourId);
      if (tour && tour.pickup && pickupSelect) {
        var hasMatch = Array.prototype.some.call(pickupSelect.options, function (option) {
          return option.value === tour.pickup;
        });

        if (hasMatch) {
          pickupSelect.value = tour.pickup;
        }
      }

      if (stage) {
        stage.classList.remove('is-complete');
      }

      if (successPanel) {
        successPanel.hidden = true;
      }

      syncFormFields();
      updateSelectionPanel();
      fetchAvailability();
    }

    function validateForm() {
      var isValid = true;
      var email = String(form.elements.email.value || '').trim();
      var phone = String(form.elements.phone.value || '').trim();
      var guestCount = Number(form.elements.guest_count.value || 0);

      clearErrors();

      if (!state.selectedTourId) {
        setStatus('Choose a tour before sending the booking.', 'error');
        isValid = false;
      }

      if (!state.selectedDate || !state.selectedTime) {
        setStatus('Choose an available date and time before sending the booking.', 'error');
        isValid = false;
      }

      if (!String(form.elements.full_name.value || '').trim()) {
        setFieldError('full_name', 'Please enter the guest name.');
        isValid = false;
      }

      if (!email && !phone) {
        setFieldError('email', 'Add an email or phone number.');
        setFieldError('phone', 'Add a phone or email address.');
        isValid = false;
      }

      if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setFieldError('email', 'Enter a valid email address.');
        isValid = false;
      }

      if (!guestCount || guestCount < 1) {
        setFieldError('guest_count', 'Guest count must be at least 1.');
        isValid = false;
      }

      if (!String(form.elements.pickup_location.value || '').trim()) {
        setFieldError('pickup_location', 'Select a pickup area.');
        isValid = false;
      }

      if (!String(form.elements.country.value || '').trim()) {
        setFieldError('country', 'Select your country.');
        isValid = false;
      }

      return isValid;
    }

    function showSuccess(payload) {
      var tour = getTour(state.selectedTourId);
      var meta = [];

      if (tour) {
        meta.push('<span class="twostars-booking-success__chip">' + escapeHtml(tour.title) + '</span>');
      }

      if (state.selectedDate) {
        meta.push('<span class="twostars-booking-success__chip">' + escapeHtml(state.selectedDate) + '</span>');
      }

      if (state.selectedTime) {
        meta.push('<span class="twostars-booking-success__chip">' + escapeHtml(state.selectedTime) + '</span>');
      }

      if (payload && payload.booking_id) {
        meta.push('<span class="twostars-booking-success__chip">Booking #' + escapeHtml(String(payload.booking_id)) + '</span>');
      }

      if (successText) {
        successText.textContent = payload && payload.status
          ? payload.status
          : config.messages.success;
      }

      if (successMeta) {
        successMeta.innerHTML = meta.join('');
      }

      if (stage) {
        stage.classList.add('is-complete');
      }

      if (successPanel) {
        successPanel.hidden = false;
      }

      setStatus(config.messages.success, 'success');

      if (typeof window.twostarsTrack === 'function') {
        window.twostarsTrack('booking_complete', { page_path: window.location.pathname });
      }
    }

    function resetSuccessState() {
      if (stage) {
        stage.classList.remove('is-complete');
      }

      if (successPanel) {
        successPanel.hidden = true;
      }

      form.reset();
      form.elements.form_started_at.value = String(Math.floor(Date.now() / 1000));
      if (pickupSelect) {
        pickupSelect.selectedIndex = 0;
      }
      if (countrySearch) {
        countrySearch.value = '';
      }
      renderCountryOptions('');
      clearErrors();
      syncFormFields();
      setStatus('Choose a tour, date, and time to activate booking.', '');
    }

    populatePickupOptions();
    renderCountryOptions('');

    if (countrySearch) {
      countrySearch.addEventListener('input', function () {
        renderCountryOptions(countrySearch.value);
      });
    }

    if (countrySelect) {
      countrySelect.addEventListener('change', function () {
        if (countrySelect.closest('.twostars-booking-country')) {
          countrySelect.closest('.twostars-booking-country').classList.remove('is-invalid');
        }
      });
    }

    tourCards.forEach(function (card) {
      card.addEventListener('click', function () {
        selectTour(card.getAttribute('data-tour-id'));
      });
    });

    if (prevMonthButton) {
      prevMonthButton.addEventListener('click', function () {
        state.displayMonth = new Date(state.displayMonth.getFullYear(), state.displayMonth.getMonth() - 1, 1);
        state.selectedDate = '';
        state.selectedTime = '';
        fetchAvailability();
      });
    }

    if (nextMonthButton) {
      nextMonthButton.addEventListener('click', function () {
        state.displayMonth = new Date(state.displayMonth.getFullYear(), state.displayMonth.getMonth() + 1, 1);
        state.selectedDate = '';
        state.selectedTime = '';
        fetchAvailability();
      });
    }

    if (resetButton) {
      resetButton.addEventListener('click', function () {
        resetSuccessState();
        fetchAvailability();
      });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      if (!validateForm()) {
        return;
      }

      submitButton.disabled = true;
      setStatus(config.messages.submitting, '');

      var formData = new FormData(form);
      formData.set('tour_id', String(state.selectedTourId));
      formData.set('date', state.selectedDate);
      formData.set('time', state.selectedTime);
      formData.set('contact_consent', form.querySelector('[name="contact_consent"]').checked ? '1' : '');
      var currentIntelligenceState = getIntelligenceState();
      if (currentIntelligenceState) {
        formData.set('session_key', currentIntelligenceState.session_key || '');
        formData.set('visitor_profile_id', String(currentIntelligenceState.visitor_profile_id || 0));
      }

      fetch(config.submitUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: new URLSearchParams(formData).toString()
      })
        .then(function (response) {
          return response.json().then(function (payload) {
            return { ok: response.ok, payload: payload };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.payload || result.payload.success !== true) {
            throw new Error((result.payload && (result.payload.message || result.payload.code)) || config.messages.error);
          }

          showSuccess(result.payload);
          window.dispatchEvent(new CustomEvent('twostars:bookingCompleted', { detail: result.payload }));
          form.reset();
          form.elements.form_started_at.value = String(Math.floor(Date.now() / 1000));
          renderCountryOptions('');
        })
        .catch(function (error) {
          setStatus(error && error.message ? error.message : config.messages.error, 'error');
        })
        .finally(function () {
          syncFormFields();
        });
    });

    selectTour(state.selectedTourId || tourCards[0].getAttribute('data-tour-id'));
    syncVisitorPrefill();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBookingPage);
  } else {
    initBookingPage();
  }
})();
