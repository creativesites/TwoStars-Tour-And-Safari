(function () {
  var config = window.twoStarsPlatform || {};
  var visitorConfig = config.visitorConfig || {};
  var thresholdsSent = {};
  var promptShown = false;
  var promptRoot;
  var consentRoot;
  var visitorRibbon;
  var latestRecommendation = null;
  var sessionState = {
    session_key: '',
    visitor_profile_id: 0,
    consent_state: 'unknown',
    visitor: {},
    current_persona: 'general_browser',
    journey_stage: 'discovery',
    visited_tours: [],
    visited_destinations: [],
    visited_tour_titles: [],
    visited_destination_titles: [],
    last_recommendation: '',
    stage: 'browsing'
  };
  var VISITOR_CONSENT_KEY = 'twostars_visitor_consent';
  var VISITOR_PROFILE_KEY = 'twostars_visitor_profile';
  var VISITOR_RIBBON_DISMISS_KEY = 'twostars_visitor_ribbon_dismissed';

  function getSessionKey() {
    var key = window.sessionStorage.getItem('twostars_session_key');

    if (!key) {
      key = 'ts_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
      window.sessionStorage.setItem('twostars_session_key', key);
    }

    sessionState.session_key = key;

    return key;
  }

  function getBasePayload() {
    return {
      session_key: getSessionKey(),
      page_type: config.pageType || 'generic',
      page_url: window.location.href,
      payload: {
        post_id: Number(config.postId || 0),
        referrer: document.referrer || '',
        device_type: window.innerWidth < 768 ? 'mobile' : 'desktop',
        page_title: document.title || '',
        page_context: config.pageContext || {},
        consent_state: sessionState.consent_state || getConsentState(),
        visitor_profile_id: Number(sessionState.visitor_profile_id || 0)
      }
    };
  }

  function mergeSessionState(result) {
    if (!result || typeof result !== 'object') {
      return;
    }

    if (result.session_key) {
      sessionState.session_key = result.session_key;
    }

    if (result.persona) {
      sessionState.current_persona = result.persona;
    }

    if (result.stage) {
      sessionState.stage = result.stage;
    }

    if (result.journey_stage) {
      sessionState.journey_stage = result.journey_stage;
    }

    if (Array.isArray(result.visited_tours)) {
      sessionState.visited_tours = result.visited_tours;
    }

    if (Array.isArray(result.visited_destinations)) {
      sessionState.visited_destinations = result.visited_destinations;
    }

    if (Array.isArray(result.visited_tour_titles)) {
      sessionState.visited_tour_titles = result.visited_tour_titles;
    }

    if (Array.isArray(result.visited_destination_titles)) {
      sessionState.visited_destination_titles = result.visited_destination_titles;
    }

    if (result.last_recommendation) {
      sessionState.last_recommendation = result.last_recommendation;
    }

    if (result.visitor_profile_id) {
      sessionState.visitor_profile_id = Number(result.visitor_profile_id || 0);
    }

    if (result.consent_state) {
      sessionState.consent_state = result.consent_state;
    }

    if (result.visitor && typeof result.visitor === 'object') {
      sessionState.visitor = result.visitor;
      persistVisitorProfile(result.visitor);
      maybeRenderVisitorRibbon(result.visitor);
    }

    if (result.recommendation && typeof result.recommendation === 'object') {
      latestRecommendation = result.recommendation;
      window.dispatchEvent(new CustomEvent('twostars:chatRecommendation', {
        detail: latestRecommendation
      }));
    }

    window.dispatchEvent(new CustomEvent('twostars:intelligenceState', {
      detail: Object.assign({}, sessionState)
    }));
  }

  function getConsentState() {
    return window.localStorage.getItem(VISITOR_CONSENT_KEY) || 'unknown';
  }

  function setConsentState(state) {
    window.localStorage.setItem(VISITOR_CONSENT_KEY, state);
    sessionState.consent_state = state;
  }

  function persistVisitorProfile(visitor) {
    if (!visitor || typeof visitor !== 'object') {
      return;
    }

    window.localStorage.setItem(VISITOR_PROFILE_KEY, JSON.stringify(visitor));
  }

  function loadPersistedVisitorProfile() {
    try {
      var raw = window.localStorage.getItem(VISITOR_PROFILE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      return null;
    }
  }

  function createVisitorApiLoader(projectId) {
    return new Promise(function (resolve, reject) {
      if (!projectId) {
        reject(new Error('Missing VisitorAPI project ID.'));
        return;
      }

      var VisitorAPI = function (pid, success, failure) {
        var request = new XMLHttpRequest();

        request.onreadystatechange = function () {
          if (request.readyState !== XMLHttpRequest.DONE) {
            return;
          }

          try {
            var payload = JSON.parse(request.responseText);

            if (payload.status === 200) {
              success(payload.data);
              return;
            }

            failure(payload.status, payload.result);
          } catch (error) {
            failure(500, 'Invalid VisitorAPI response.');
          }
        };

        request.open('GET', 'https://api.visitorapi.com/api/?pid=' + encodeURIComponent(pid));
        request.send(null);
      };

      VisitorAPI(
        projectId,
        function (data) { resolve(data || {}); },
        function (errorCode, errorMessage) { reject(new Error(String(errorCode) + ': ' + String(errorMessage || 'VisitorAPI failed.'))); }
      );
    });
  }

  function loadFingerprintAgent() {
    if (!visitorConfig.fingerprintAgentUrl || !visitorConfig.fingerprintPublicKey) {
      return Promise.resolve(null);
    }

    return import(visitorConfig.fingerprintAgentUrl)
      .then(function (Fingerprint) {
        return Fingerprint.start({
          region: visitorConfig.fingerprintRegion || 'ap'
        });
      })
      .then(function (fp) {
        return fp.get({ extendedResult: true });
      })
      .then(function (result) {
        return {
          event_id: result.event_id || '',
          visitor_id: result.visitor_id || '',
          request_id: result.request_id || result.requestId || '',
          ipLocation: result.ipLocation || result.ip_location || null,
          browserName: result.browserName || '',
          browserVersion: result.browserVersion || '',
          os: result.os || '',
          osVersion: result.osVersion || '',
          device: result.device || ''
        };
      })
      .catch(function () {
        return null;
      });
  }

  async function identifyVisitor() {
    if (!visitorConfig.enabled || getConsentState() !== 'granted') {
      return null;
    }

    try {
      var fingerprintResult = await loadFingerprintAgent();
      var visitorApiResult = {};

      try {
        visitorApiResult = await createVisitorApiLoader(visitorConfig.visitorApiProjectId || '');
      } catch (error) {
        visitorApiResult = {};
      }

      if (!fingerprintResult || !fingerprintResult.visitor_id) {
        return null;
      }

      var response = await fetch(config.visitorIdentifyUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          session_key: getSessionKey(),
          page_url: window.location.href,
          consent_state: 'granted',
          fingerprint: fingerprintResult,
          visitor_api: visitorApiResult
        })
      });

      if (!response.ok) {
        return null;
      }

      var result = await response.json();
      mergeSessionState(result);
      return result;
    } catch (error) {
      return null;
    }
  }

  function openAssistantSurface() {
    var toggle = document.querySelector('[data-twostars-chatbot-toggle]');

    if (toggle) {
      if (toggle.getAttribute('aria-expanded') !== 'true') {
        toggle.click();
      }

      sendEvent('visitor_ribbon_resume_click', { action_type: 'open_assistant' });
      return;
    }

    var textarea = document.querySelector('[data-twostars-chatbot] textarea[name="message"]');

    if (textarea) {
      if (typeof textarea.scrollIntoView === 'function') {
        textarea.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }

      textarea.focus();
      sendEvent('visitor_ribbon_resume_click', { action_type: 'focus_assistant' });
      return;
    }

    sendEvent('visitor_ribbon_resume_click', { action_type: 'fallback_booking' });
    window.location.href = '/booking/#booking-app';
  }

  function scrollToBookingApp() {
    var bookingApp = document.querySelector('[data-booking-app], #booking-app, .twostars-booking-page');

    if (bookingApp) {
      if (typeof bookingApp.scrollIntoView === 'function') {
        bookingApp.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }

      sendEvent('visitor_ribbon_resume_click', { action_type: 'scroll_booking' });
      return;
    }

    sendEvent('visitor_ribbon_resume_click', { action_type: 'visit_booking' });
    window.location.href = '/booking/#booking-app';
  }

  function buildVisitorRibbonState(visitor) {
    var country = visitor.country_name || '';
    var city = visitor.city || '';

    if (visitor.is_returning) {
      if (config.pageType === 'booking') {
        return {
          copy: 'Ready to continue? Pick up your route, pickup details, and booking step from here.',
          actionLabel: 'Resume booking',
          actionType: 'booking'
        };
      }

      if (config.pageType === 'tours') {
        return {
          copy: 'Ready to continue? Open the guide to compare tours again and narrow the best fit faster.',
          actionLabel: 'Continue comparing',
          actionType: 'assistant'
        };
      }

      if (config.pageType === 'destinations') {
        return {
          copy: 'Ready to continue? Open the guide to compare stops again and shape the right coastal route.',
          actionLabel: 'Continue planning',
          actionType: 'assistant'
        };
      }

      return {
        copy: 'Ready to continue? The guide can help you pick up your planning and move to the best next step.',
        actionLabel: 'Continue planning',
        actionType: 'assistant'
      };
    }

    if (country && (config.pageType === 'booking' || config.pageType === 'tours')) {
      return {
        copy: 'Planning from ' + country + '? Use the guide to narrow the right coastal route, pickup plan, and next booking step.',
        actionLabel: 'Ask the guide',
        actionType: 'assistant'
      };
    }

    if (city && config.pageType === 'destinations') {
      return {
        copy: 'Planning from ' + city + '? Use the guide to compare destination stops and narrow the route that fits your pace best.',
        actionLabel: 'Compare stops',
        actionType: 'assistant'
      };
    }

    if (country) {
      return {
        copy: 'Planning your Namibia coast trip from ' + country + '? The guide can help narrow the best Two Stars route around your pace and interests.',
        actionLabel: 'Start planning',
        actionType: 'assistant'
      };
    }

    return null;
  }

  function maybeRenderVisitorRibbon(visitor) {
    if (!visitorConfig.personalizationEnabled || !visitor || typeof visitor !== 'object') {
      return;
    }

    if (window.sessionStorage.getItem(VISITOR_RIBBON_DISMISS_KEY) === '1') {
      return;
    }

    var ribbonState = buildVisitorRibbonState(visitor);

    if (!ribbonState || !ribbonState.copy) {
      return;
    }

    if (!visitorRibbon) {
      visitorRibbon = document.createElement('aside');
      visitorRibbon.className = 'twostars-visitor-ribbon';
      visitorRibbon.innerHTML = [
        '<div class="twostars-visitor-ribbon__inner">',
        '  <div class="twostars-visitor-ribbon__copy">',
        '    <span class="twostars-visitor-ribbon__eyebrow">Planning help</span>',
        '    <p class="twostars-visitor-ribbon__text"></p>',
        '  </div>',
        '  <div class="twostars-visitor-ribbon__actions">',
        '    <button type="button" class="twostars-visitor-ribbon__action"></button>',
        '    <button type="button" class="twostars-visitor-ribbon__close" aria-label="Dismiss planning help">&times;</button>',
        '  </div>',
        '</div>'
      ].join('');

      visitorRibbon.querySelector('.twostars-visitor-ribbon__close').addEventListener('click', function () {
        window.sessionStorage.setItem(VISITOR_RIBBON_DISMISS_KEY, '1');
        visitorRibbon.remove();
        visitorRibbon = null;
      });

      var anchor = document.querySelector('main, .site-main, #content, body');
      if (anchor) {
        anchor.insertAdjacentElement('afterbegin', visitorRibbon);
      }
    }

    var textNode = visitorRibbon.querySelector('.twostars-visitor-ribbon__text');
    var actionNode = visitorRibbon.querySelector('.twostars-visitor-ribbon__action');
    if (textNode) {
      textNode.textContent = ribbonState.copy;
    }

    if (actionNode) {
      actionNode.textContent = ribbonState.actionLabel || 'Continue';
      actionNode.onclick = function () {
        if (ribbonState.actionType === 'booking') {
          scrollToBookingApp();
          return;
        }

        openAssistantSurface();
      };
    }
  }

  function ensureConsentGate() {
    if (!visitorConfig.enabled || getConsentState() !== 'unknown' || consentRoot) {
      return;
    }

    consentRoot = document.createElement('aside');
    consentRoot.className = 'twostars-visitor-consent';
    consentRoot.innerHTML = [
      '<div class="twostars-visitor-consent__inner">',
      '  <div class="twostars-visitor-consent__copy">',
      '    <p class="twostars-visitor-consent__title">' + escapeHtml(visitorConfig.consentCopy && visitorConfig.consentCopy.title || 'Personalized trip planning') + '</p>',
      '    <p class="twostars-visitor-consent__text">' + escapeHtml(visitorConfig.consentCopy && visitorConfig.consentCopy.description || '') + '</p>',
      '  </div>',
      '  <div class="twostars-visitor-consent__actions">',
      '    <button type="button" class="twostars-visitor-consent__accept">' + escapeHtml(visitorConfig.consentCopy && visitorConfig.consentCopy.acceptLabel || 'Allow personalization') + '</button>',
      '    <button type="button" class="twostars-visitor-consent__decline">' + escapeHtml(visitorConfig.consentCopy && visitorConfig.consentCopy.declineLabel || 'No thanks') + '</button>',
      '  </div>',
      '</div>'
    ].join('');

    consentRoot.querySelector('.twostars-visitor-consent__accept').addEventListener('click', function () {
      setConsentState('granted');
      sendEvent('visitor_consent_granted', { consent_state: 'granted' });
      identifyVisitor();
      consentRoot.remove();
      consentRoot = null;
    });

    consentRoot.querySelector('.twostars-visitor-consent__decline').addEventListener('click', function () {
      setConsentState('denied');
      sendEvent('visitor_consent_denied', { consent_state: 'denied' });
      consentRoot.remove();
      consentRoot = null;
    });

    document.body.appendChild(consentRoot);
  }

  async function sendEvent(eventName, payload) {
    try {
      var response = await fetch(config.visitorActionUrl || config.restUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(Object.assign(getBasePayload(), {
          event: eventName,
          payload: Object.assign({}, getBasePayload().payload, payload || {})
        }))
      });

      if (!response.ok) {
        return null;
      }

      var result = await response.json();
      mergeSessionState(result);
      return result;
    } catch (error) {
      return null;
    }
  }

  function sendTrackedOnce(cacheKey, eventName, payload) {
    if (thresholdsSent[cacheKey]) {
      return;
    }

    thresholdsSent[cacheKey] = true;
    sendEvent(eventName, payload || {});
  }

  function buildChatPayload(message) {
    return {
      message: message,
      session_key: getSessionKey(),
      page_type: config.pageType || 'generic',
      post_id: Number(config.postId || 0),
      current_persona: sessionState.current_persona || 'general_browser',
      visited_tours: sessionState.visited_tours || [],
      visited_destinations: sessionState.visited_destinations || [],
      recommendation_key: sessionState.last_recommendation || '',
      page_context: config.pageContext || {},
      visitor_profile_id: Number(sessionState.visitor_profile_id || 0),
      consent_state: sessionState.consent_state || getConsentState()
    };
  }

  function parseUiBlocks(rawText) {
    var text = rawText || '';
    var components = [];
    var pattern = /---ui-component---\s*([\s\S]*?)\s*---end-ui---/g;
    var match;

    while ((match = pattern.exec(text)) !== null) {
      try {
        components.push(JSON.parse(match[1]));
      } catch (error) {
      }
    }

    components = components.filter(function (component) {
      return component && component.component !== 'lead-capture-mini';
    });

    return {
      text: text.replace(pattern, '').replace(/\n{3,}/g, '\n\n').trim(),
      components: components
    };
  }

  function createElement(tagName, className, text) {
    var element = document.createElement(tagName);

    if (className) {
      element.className = className;
    }

    if (typeof text === 'string') {
      element.textContent = text;
    }

    return element;
  }

  function renderMetaList(items) {
    var list = createElement('div', 'twostars-genui__meta');

    items.filter(Boolean).forEach(function (item) {
      list.appendChild(createElement('span', 'twostars-genui__pill', item));
    });

    return list;
  }

  function renderTourHighlightCard(props) {
    var card = createElement('section', 'twostars-genui twostars-genui--card');

    if (props.image_url || props.image) {
      var media = createElement('div', 'twostars-genui__media');
      var image = document.createElement('img');
      image.className = 'twostars-genui__image';
      image.src = props.image_url || props.image;
      image.alt = props.title || 'Suggested route';
      image.loading = 'lazy';
      media.appendChild(image);
      card.appendChild(media);
    }

    var body = createElement('div', 'twostars-genui__body');
    body.appendChild(createElement('h4', 'twostars-genui__title', props.title || 'Suggested Tour'));

    var meta = renderMetaList([props.duration, props.price, props.availability]);
    if (meta.childNodes.length) {
      body.appendChild(meta);
    }

    if (props.summary) {
      body.appendChild(createElement('p', 'twostars-genui__copy', props.summary));
    }

    if (props.cta_link) {
      var action = createElement('a', 'twostars-genui__cta', props.cta_label || 'View Tour');
      action.href = props.cta_link;
      body.appendChild(action);
    }

    card.appendChild(body);
    return card;
  }

  function renderComparisonTable(props) {
    var wrapper = createElement('section', 'twostars-genui twostars-genui--table');
    if (props.title) {
      wrapper.appendChild(createElement('h4', 'twostars-genui__title', props.title));
    }

    var table = createElement('table', 'twostars-genui__table');
    var thead = createElement('thead', '');
    var headRow = createElement('tr', '');

    (props.headers || []).forEach(function (header) {
      headRow.appendChild(createElement('th', '', header));
    });

    thead.appendChild(headRow);
    table.appendChild(thead);

    var tbody = createElement('tbody', '');
    (props.rows || []).forEach(function (row) {
      var tr = createElement('tr', '');
      tr.appendChild(createElement('th', '', row.label || ''));

      (row.values || []).forEach(function (value) {
        tr.appendChild(createElement('td', '', value));
      });

      tbody.appendChild(tr);
    });

    table.appendChild(tbody);
    wrapper.appendChild(table);
    return wrapper;
  }

  function renderItineraryTimeline(props) {
    var wrapper = createElement('section', 'twostars-genui twostars-genui--timeline');
    if (props.title) {
      wrapper.appendChild(createElement('h4', 'twostars-genui__title', props.title));
    }

    var list = createElement('div', 'twostars-genui__timeline');
    (props.items || props.days || []).forEach(function (item) {
      var row = createElement('div', 'twostars-genui__timeline-item');
      row.appendChild(createElement('span', 'twostars-genui__timeline-day', 'Day ' + (item.day || '?')));
      var body = createElement('div', 'twostars-genui__timeline-body');
      body.appendChild(createElement('strong', '', item.label || item.title || 'Stop'));
      if (item.desc || item.activity) {
        body.appendChild(createElement('p', '', item.desc || item.activity));
      }
      row.appendChild(body);
      list.appendChild(row);
    });

    wrapper.appendChild(list);
    return wrapper;
  }

  function submitMiniLeadForm(form, statusNode) {
    var leadUrl = config.crmLeadUrl || '';

    if (!leadUrl) {
      statusNode.textContent = 'Lead capture is unavailable right now.';
      return;
    }

    var formData = new FormData(form);
    var payload = {};

    formData.forEach(function (value, key) {
      payload[key] = value;
    });

    if (!payload.email && !payload.phone) {
      statusNode.textContent = 'Enter an email address or phone number.';
      return;
    }

    statusNode.textContent = 'Sending...';

    fetch(leadUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    }).then(function (response) {
      return response.json().then(function (result) {
        return {
          ok: response.ok,
          result: result
        };
      });
    }).then(function (response) {
      if (!response.ok) {
        throw new Error(response.result.message || 'Lead capture failed.');
      }

      form.reset();
      statusNode.textContent = response.result.status || 'Thanks. Your inquiry has been received.';
      window.dispatchEvent(new CustomEvent('twostars:leadCaptured', { detail: response.result }));
    }).catch(function (error) {
      statusNode.textContent = error.message || 'Lead capture failed.';
    });
  }

  function renderLeadCaptureMini(props) {
    var wrapper = createElement('section', 'twostars-genui twostars-genui--lead');
    wrapper.appendChild(createElement('h4', 'twostars-genui__title', props.title || 'Request a Quote'));

    if (props.context) {
      wrapper.appendChild(createElement('p', 'twostars-genui__copy', props.context));
    }

    var form = createElement('form', 'twostars-genui__lead-form');

    [
      { name: 'full_name', type: 'text', placeholder: 'Full name' },
      { name: 'email', type: 'email', placeholder: 'Email address' },
      { name: 'phone', type: 'text', placeholder: 'Phone (optional)' },
      { name: 'travel_dates', type: 'text', placeholder: props.travel_dates || 'Travel dates' },
      { name: 'guest_count', type: 'number', placeholder: props.guest_count || 'Guests' }
    ].forEach(function (field) {
      var input = createElement('input', 'twostars-genui__input');
      input.name = field.name;
      input.type = field.type;
      input.placeholder = field.placeholder;
      if (field.name === 'guest_count') {
        input.min = '1';
        input.step = '1';
      }
      form.appendChild(input);
    });

    var textarea = createElement('textarea', 'twostars-genui__textarea');
    textarea.name = 'message';
    textarea.rows = 3;
    textarea.placeholder = props.message || 'Share your route, dates, or quote request';
    form.appendChild(textarea);

    var consentLabel = createElement('label', 'twostars-genui__consent');
    var consent = createElement('input', '');
    consent.type = 'checkbox';
    consent.name = 'contact_consent';
    consent.value = '1';
    consent.required = true;
    consentLabel.appendChild(consent);
    consentLabel.appendChild(createElement('span', '', 'I agree to be contacted about this Two Stars tour inquiry.'));
    form.appendChild(consentLabel);

    [
      ['source', 'chatbot'],
      ['origin_url', window.location.href],
      ['form_started_at', Math.floor(Date.now() / 1000).toString()],
      ['session_key', getSessionKey()],
      ['visitor_profile_id', String(sessionState.visitor_profile_id || 0)]
    ].forEach(function (pair) {
      var hidden = createElement('input', '');
      hidden.type = 'hidden';
      hidden.name = pair[0];
      hidden.value = pair[1];
      form.appendChild(hidden);
    });

    if (props.tour_id) {
      var tourId = createElement('input', '');
      tourId.type = 'hidden';
      tourId.name = 'tour_interest';
      tourId.value = String(props.tour_id);
      form.appendChild(tourId);
    }

    var actions = createElement('div', 'twostars-genui__actions');
    var submit = createElement('button', 'twostars-genui__cta', props.submit_label || 'Send Inquiry');
    submit.type = 'submit';
    actions.appendChild(submit);
    form.appendChild(actions);

    var status = createElement('p', 'twostars-genui__status', '');
    form.appendChild(status);

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      submitMiniLeadForm(form, status);
    });

    wrapper.appendChild(form);
    return wrapper;
  }

  function renderComponent(componentData) {
    if (!componentData || typeof componentData !== 'object') {
      return null;
    }

    var props = componentData.props || {};

    if (componentData.component === 'tour-highlight-card') {
      return renderTourHighlightCard(props);
    }

    if (componentData.component === 'comparison-table') {
      return renderComparisonTable(props);
    }

    if (componentData.component === 'itinerary-timeline') {
      return renderItineraryTimeline(props);
    }

    if (componentData.component === 'lead-capture-mini') {
      return renderLeadCaptureMini(props);
    }

    return null;
  }

  function renderComponents(container, components) {
    container.innerHTML = '';

    (components || []).forEach(function (componentData) {
      var node = renderComponent(componentData);

      if (node) {
        container.appendChild(node);
      }
    });
  }

  function ensurePrompt() {
    if (promptRoot) {
      return promptRoot;
    }

    promptRoot = document.createElement('aside');
    promptRoot.className = 'twostars-intelligence-prompt';
    promptRoot.hidden = true;
    promptRoot.innerHTML = [
      '<div class="twostars-intelligence-prompt__header">',
      '  <div>',
      '    <p class="twostars-intelligence-prompt__title"></p>',
      '    <p class="twostars-intelligence-prompt__message"></p>',
      '  </div>',
      '  <button type="button" class="twostars-intelligence-prompt__close" aria-label="Close">×</button>',
      '</div>',
      '<div class="twostars-intelligence-prompt__actions"></div>',
      '<div class="twostars-intelligence-prompt__ask" hidden>',
      '  <form>',
      '    <input type="text" name="message" placeholder="Ask about routes, pickups, or planning">',
      '    <button type="submit">Ask the guide</button>',
      '  </form>',
      '  <p class="twostars-intelligence-prompt__reply"></p>',
      '  <div class="twostars-intelligence-prompt__components"></div>',
      '</div>'
    ].join('');

    promptRoot.querySelector('.twostars-intelligence-prompt__close').addEventListener('click', function () {
      promptRoot.hidden = true;
      sendEvent('prompt_dismissed');
    });

    promptRoot.querySelector('.twostars-intelligence-prompt__ask form').addEventListener('submit', async function (event) {
      event.preventDefault();

      var input = event.currentTarget.querySelector('input[name="message"]');
      var reply = promptRoot.querySelector('.twostars-intelligence-prompt__reply');
      var components = promptRoot.querySelector('.twostars-intelligence-prompt__components');

      if (!input.value.trim()) {
        return;
      }

      reply.textContent = 'Thinking...';
      components.innerHTML = '';
      await sendEvent('chat_opened');

      try {
        var response = await fetch(config.chatUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(buildChatPayload(input.value.trim()))
        });
        var result = await response.json();

        if (!response.ok) {
          throw new Error(result.message || 'The guide is unavailable right now.');
        }

        var parsed = parseUiBlocks(result.reply || '');
        reply.textContent = parsed.text || '';
        renderComponents(components, parsed.components);
        sendEvent('chat_message', { message: input.value.trim() });
      } catch (error) {
        reply.textContent = 'The guide is unavailable right now.';
      }
    });

    document.body.appendChild(promptRoot);

    return promptRoot;
  }

  function showRecommendation(recommendation) {
    if (!recommendation || promptShown) {
      return;
    }

    latestRecommendation = recommendation;
    window.dispatchEvent(new CustomEvent('twostars:chatRecommendation', {
      detail: recommendation
    }));

    if (document.querySelector('[data-twostars-chatbot-float]')) {
      promptShown = true;
      return;
    }

    var root = ensurePrompt();
    var title = root.querySelector('.twostars-intelligence-prompt__title');
    var message = root.querySelector('.twostars-intelligence-prompt__message');
    var actions = root.querySelector('.twostars-intelligence-prompt__actions');
    var ask = root.querySelector('.twostars-intelligence-prompt__ask');
    var reply = root.querySelector('.twostars-intelligence-prompt__reply');
    var components = root.querySelector('.twostars-intelligence-prompt__components');

    title.textContent = recommendation.title || '';
    message.textContent = recommendation.message || '';
    actions.innerHTML = '';
    ask.hidden = true;
    reply.textContent = '';
    components.innerHTML = '';
    root.__recommendation = recommendation;

    if (recommendation.primary) {
      if (recommendation.primary.action === 'open_assistant') {
        var assistantButton = document.createElement('button');
        assistantButton.type = 'button';
        assistantButton.className = 'twostars-intelligence-prompt__primary';
        assistantButton.textContent = recommendation.primary.label || 'Ask the guide';
        assistantButton.addEventListener('click', function () {
          ask.hidden = false;
          reply.textContent = recommendation.assistant_intro || '';
          sendEvent('prompt_primary_click');
        });
        actions.appendChild(assistantButton);
      } else if (recommendation.primary.url) {
        var primaryLink = document.createElement('a');
        primaryLink.className = 'twostars-intelligence-prompt__primary';
        primaryLink.href = recommendation.primary.url;
        primaryLink.textContent = recommendation.primary.label || 'Continue';
        actions.appendChild(primaryLink);
      }
    }

    if (recommendation.secondary && recommendation.secondary.url) {
      var secondaryLink = document.createElement('a');
      secondaryLink.className = 'twostars-intelligence-prompt__secondary';
      secondaryLink.href = recommendation.secondary.url;
      secondaryLink.textContent = recommendation.secondary.label || 'Learn more';
      actions.appendChild(secondaryLink);
    }

    root.hidden = false;
    promptShown = true;
    sendEvent('prompt_shown', { recommendation: recommendation.key || '' });
  }

  function trackScroll() {
    var depth = ((window.scrollY + window.innerHeight) / document.documentElement.scrollHeight) * 100;

    if (depth >= 50 && !thresholdsSent.scroll50) {
      thresholdsSent.scroll50 = true;
      sendEvent('scroll_50').then(function (result) {
        if (result && result.recommendation) {
          showRecommendation(result.recommendation);
        }
      });
    }

    if (depth >= 80 && !thresholdsSent.scroll80) {
      thresholdsSent.scroll80 = true;
      sendEvent('scroll_80').then(function (result) {
        if (result && result.recommendation) {
          showRecommendation(result.recommendation);
        }
      });
    }
  }

  function matchesInterestTarget(target, patterns) {
    if (!target) {
      return false;
    }

    var text = (target.textContent || '').toLowerCase();
    var href = (target.getAttribute && target.getAttribute('href') || '').toLowerCase();
    var dataTarget = (target.getAttribute && target.getAttribute('data-target') || '').toLowerCase();

    return patterns.some(function (pattern) {
      return text.indexOf(pattern) !== -1 || href.indexOf(pattern) !== -1 || dataTarget.indexOf(pattern) !== -1;
    });
  }

  function matchesField(target, patterns) {
    if (!target) {
      return false;
    }

    var text = [
      target.name || '',
      target.id || '',
      target.placeholder || '',
      target.getAttribute && target.getAttribute('aria-label') || ''
    ].join(' ').toLowerCase();

    return patterns.some(function (pattern) {
      return text.indexOf(pattern) !== -1;
    });
  }

  function setupReviewReadTracking() {
    if (typeof window.IntersectionObserver !== 'function') {
      return;
    }

    var reviewRoot = document.querySelector('[data-testimonial], .testimonial, .twostars-testimonial, .reviews, .testimonials');

    if (!reviewRoot) {
      return;
    }

    var timer = 0;
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting || thresholdsSent.reviewRead) {
          if (timer) {
            window.clearTimeout(timer);
            timer = 0;
          }
          return;
        }

        timer = window.setTimeout(function () {
          sendTrackedOnce('reviewRead', 'review_read');
        }, 10000);
      });
    }, { threshold: 0.55 });

    observer.observe(reviewRoot);
  }

  function setupFieldSignalTracking() {
    document.addEventListener('focusin', function (event) {
      var target = event.target;

      if (!target || !target.matches) {
        return;
      }

      if (target.matches('input, select') && matchesField(target, ['date', 'travel', 'departure', 'arrival', 'checkin', 'checkout'])) {
        sendTrackedOnce('dateSelectorOpen', 'date_selector_open', {
          field: target.name || target.id || ''
        });
      }
    });

    document.addEventListener('change', function (event) {
      var target = event.target;

      if (!target || !target.matches || !target.matches('input, select')) {
        return;
      }

      if (matchesField(target, ['guest', 'traveler', 'traveller', 'group', 'adult', 'people'])) {
        sendTrackedOnce('groupSizeAdjust', 'group_size_adjust', {
          field: target.name || target.id || '',
          guest_count: Number(target.value || 0)
        });
      }
    });
  }

  document.addEventListener('click', function (event) {
    var eventTarget = event.target;

    if (!eventTarget || typeof eventTarget.closest !== 'function') {
      return;
    }

    var target = eventTarget.closest('a, button, [role="button"], summary');

    if (matchesInterestTarget(target, ['pricing', 'price', 'cost', 'rate'])) {
      sendEvent('pricing_interest', {
        label: (target.textContent || '').trim()
      });
    }

    if (matchesInterestTarget(target, ['inclusion', 'includes', 'exclude', 'excludes'])) {
      sendEvent('inclusion_interest', {
        label: (target.textContent || '').trim()
      });
    }

    if (matchesInterestTarget(target, ['gallery', 'photos', 'images', 'view photos', 'view gallery'])) {
      sendTrackedOnce('galleryView', 'gallery_view', {
        label: (target.textContent || '').trim()
      });
    }

    if (matchesInterestTarget(target, ['availability', 'check dates', 'check availability'])) {
      sendTrackedOnce('availabilityCheck', 'availability_check', {
        label: (target.textContent || '').trim()
      });
    }

    if (matchesInterestTarget(target, ['add to compare', 'save comparison'])) {
      sendTrackedOnce('comparisonAdd', 'comparison_add', {
        label: (target.textContent || '').trim()
      });
    }

    if (matchesInterestTarget(target, ['comparison', 'compare tours', 'compare safari', 'view comparison'])) {
      sendTrackedOnce('comparisonView', 'comparison_view', {
        label: (target.textContent || '').trim()
      });
    }

    if (target && target.tagName === 'A') {
      var href = (target.getAttribute('href') || '').toLowerCase();

      if (href.indexOf('/tours/') !== -1) {
        sendEvent('tour_card_click', {
          label: (target.textContent || '').trim(),
          href: href
        });
      }

      if (href.indexOf('/destinations/') !== -1) {
        sendEvent('destination_card_click', {
          label: (target.textContent || '').trim(),
          href: href
        });
      }

      if (href.indexOf('wa.me') !== -1 || href.indexOf('whatsapp') !== -1) {
        sendTrackedOnce('whatsappClick', 'whatsapp_click', {
          href: href
        });
      }
    }

    if (target && typeof target.matches === 'function' && target.matches('summary, .faq-question, .elementor-tab-title, [data-faq-toggle]')) {
      sendTrackedOnce('faqExpand', 'faq_expand', {
        label: (target.textContent || '').trim()
      });
    }

    var trigger = eventTarget.closest('.tour-booking-button, .twostars-widget-button');

    if (trigger) {
      sendEvent('booking_click', { href: trigger.getAttribute('href') || '' });
    }

    var heroTrigger = eventTarget.closest('.twostars-hero a, .twostars-hero button, .hero a, .hero button, [data-hero-cta]');
    if (heroTrigger) {
      sendTrackedOnce('heroCtaClick', 'hero_cta_click', {
        label: (heroTrigger.textContent || '').trim(),
        href: heroTrigger.getAttribute('href') || ''
      });
    }
  });

  window.addEventListener('twostars:leadCaptured', function () {
    sendEvent('lead_created');
  });

  window.addEventListener('twostars:bookingCompleted', function () {
    sendEvent('booking_completed');
  });

  function trackLeadFormFocus(event) {
    if (event.target && typeof event.target.closest === 'function' && event.target.closest('[data-twostars-lead-form]')) {
      sendEvent('lead_form_open');
      document.removeEventListener('focusin', trackLeadFormFocus);
    }
  }

  document.addEventListener('focusin', trackLeadFormFocus);
  setupFieldSignalTracking();
  setupReviewReadTracking();

  document.addEventListener('focusin', function (event) {
    var target = event.target;

    if (target && target.closest && target.closest('[data-booking-app], #booking-app, .twostars-booking-page')) {
      sendTrackedOnce('bookingStarted', 'booking_started');
    }
  });

  window.twoStarsGenUi = {
    buildChatPayload: buildChatPayload,
    parseUiBlocks: parseUiBlocks,
    renderComponent: renderComponent,
    renderComponents: renderComponents,
    getLatestRecommendation: function () {
      return latestRecommendation ? Object.assign({}, latestRecommendation) : null;
    },
    getSessionState: function () {
      return Object.assign({}, sessionState);
    }
  };

  var persistedVisitor = loadPersistedVisitorProfile();
  if (persistedVisitor) {
    sessionState.visitor = persistedVisitor;
    if (persistedVisitor.id) {
      sessionState.visitor_profile_id = Number(persistedVisitor.id || 0);
    }
    maybeRenderVisitorRibbon(persistedVisitor);
  }

  sessionState.consent_state = getConsentState();
  ensureConsentGate();

  if (sessionState.consent_state === 'granted') {
    identifyVisitor();
  }

  sendEvent('page_view').then(function (result) {
    if (result && result.recommendation) {
      showRecommendation(result.recommendation);
    }
  });

  window.addEventListener('scroll', trackScroll, { passive: true });
})();
