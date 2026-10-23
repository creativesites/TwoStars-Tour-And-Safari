(function () {
  var NUDGE_COOLDOWN_MS = 15 * 60 * 1000;

  function getHelpers() {
    return window.twoStarsGenUi || null;
  }

  function getPlatformConfig() {
    return window.twoStarsPlatform || {};
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

  function getSessionKey() {
    var helpers = getHelpers();

    if (helpers && typeof helpers.getSessionState === 'function') {
      return helpers.getSessionState().session_key || 'default';
    }

    return 'default';
  }

  function getStoragePrefix() {
    return window.twoStarsChatbot.storagePrefix || 'twostars_chat_history_';
  }

  function getCurrentConversationKey() {
    return getStoragePrefix() + getSessionKey() + '_current_conversation';
  }

  function getThreadStoreKey() {
    return getStoragePrefix() + getSessionKey() + '_threads';
  }

  function getNudgeCooldownKey() {
    return getStoragePrefix() + getSessionKey() + '_nudge_dismissed_at';
  }

  function getSessionState() {
    var helpers = getHelpers();

    if (helpers && typeof helpers.getSessionState === 'function') {
      return helpers.getSessionState();
    }

    return {};
  }

  function getVisitorProfileId() {
    var state = getSessionState();
    return parseInt(state.visitor_profile_id || '0', 10) || 0;
  }

  function getLatestRecommendation() {
    var helpers = getHelpers();

    if (helpers && typeof helpers.getLatestRecommendation === 'function') {
      return helpers.getLatestRecommendation();
    }

    return null;
  }

  function parseReply(reply) {
    var helpers = getHelpers();

    if (helpers && typeof helpers.parseUiBlocks === 'function') {
      return helpers.parseUiBlocks(reply);
    }

    return {
      text: reply || '',
      components: []
    };
  }

  function createConversationId() {
    return 'conv_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 9);
  }

  function loadThreads() {
    try {
      var stored = JSON.parse(window.localStorage.getItem(getThreadStoreKey()) || '[]');
      return Array.isArray(stored) ? stored : [];
    } catch (error) {
      return [];
    }
  }

  function saveThreads(threads) {
    window.localStorage.setItem(getThreadStoreKey(), JSON.stringify(threads));
  }

  function isMeaningfulHistory(history) {
    return (history || []).some(function (entry) {
      return entry.role === 'user' && entry.text;
    });
  }

  function getThreadTitle(history) {
    var platform = getPlatformConfig();
    var firstUser = (history || []).find(function (entry) {
      return entry.role === 'user' && entry.text;
    });

    if (firstUser && firstUser.text) {
      return firstUser.text.slice(0, 48);
    }

    if (platform.pageContext && platform.pageContext.title) {
      return platform.pageContext.title;
    }

    return 'Two Stars planning';
  }

  function getThreadPreview(history) {
    var previewEntry = (history || []).slice().reverse().find(function (entry) {
      return entry.text;
    });

    return previewEntry ? previewEntry.text.slice(0, 88) : 'No messages yet.';
  }

  function upsertThread(root, history) {
    var threads = loadThreads().filter(function (thread) {
      return thread.id !== root.__conversationId;
    });

    if (!isMeaningfulHistory(history)) {
      saveThreads(threads);
      return;
    }

    threads.push({
      id: root.__conversationId,
      title: getThreadTitle(history),
      preview: getThreadPreview(history),
      updatedAt: Date.now(),
      history: history
    });

    threads.sort(function (left, right) {
      return (right.updatedAt || 0) - (left.updatedAt || 0);
    });

    saveThreads(threads.slice(0, 20));
  }

  function setCurrentConversationId(id) {
    window.sessionStorage.setItem(getCurrentConversationKey(), id);
  }

  function getStoredCurrentConversationId() {
    return window.sessionStorage.getItem(getCurrentConversationKey()) || '';
  }

  function findThreadById(id) {
    return loadThreads().find(function (thread) {
      return thread.id === id;
    }) || null;
  }

  function normalizeRemoteHistory(history) {
    return (Array.isArray(history) ? history : []).map(function (entry) {
      return {
        role: entry && entry.role === 'assistant' ? 'bot' : 'user',
        text: entry && typeof entry.content === 'string' ? entry.content : '',
        components: []
      };
    }).filter(function (entry) {
      return entry.text;
    });
  }

  function normalizeRemoteThread(thread) {
    if (!thread) {
      return null;
    }

    return {
      id: thread.conversation_key || thread.id || '',
      title: thread.title || 'Two Stars planning',
      preview: thread.preview || '',
      updatedAt: thread.updated_at || thread.updatedAt || '',
      history: normalizeRemoteHistory(thread.history || [])
    };
  }

  function mergeThreads(localThreads, remoteThreads) {
    var map = new Map();

    (Array.isArray(localThreads) ? localThreads : []).forEach(function (thread) {
      if (thread && thread.id) {
        map.set(thread.id, thread);
      }
    });

    (Array.isArray(remoteThreads) ? remoteThreads : []).forEach(function (thread) {
      var normalized = normalizeRemoteThread(thread);

      if (!normalized || !normalized.id) {
        return;
      }

      var existing = map.get(normalized.id) || {};
      map.set(normalized.id, {
        id: normalized.id,
        title: normalized.title || existing.title || 'Two Stars planning',
        preview: normalized.preview || existing.preview || '',
        updatedAt: normalized.updatedAt || existing.updatedAt || '',
        history: normalized.history && normalized.history.length ? normalized.history : (existing.history || [])
      });
    });

    return Array.from(map.values()).sort(function (left, right) {
      return new Date(right.updatedAt || 0).getTime() - new Date(left.updatedAt || 0).getTime();
    });
  }

  function getAvailableThreads(root) {
    return mergeThreads(loadThreads(), root.__remoteThreads || []);
  }

  async function loadRemoteThreads(root) {
    var historyUrl = window.twoStarsChatbot.historyUrl || '';

    if (!historyUrl) {
      return [];
    }

    var url = new URL(historyUrl, window.location.origin);
    var sessionKey = getSessionKey();
    var visitorProfileId = getVisitorProfileId();

    if (sessionKey) {
      url.searchParams.set('session_key', sessionKey);
    }

    if (visitorProfileId > 0) {
      url.searchParams.set('visitor_profile_id', String(visitorProfileId));
    }

    try {
      var response = await fetch(url.toString(), {
        method: 'GET',
        credentials: 'same-origin'
      });
      var result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Could not load conversation history.');
      }

      root.__remoteThreads = Array.isArray(result.threads) ? result.threads : [];
      return root.__remoteThreads;
    } catch (error) {
      return root.__remoteThreads || [];
    }
  }

  async function loadRemoteThreadDetail(conversationId) {
    var threadUrl = window.twoStarsChatbot.threadUrl || '';

    if (!threadUrl || !conversationId) {
      return null;
    }

    var url = new URL(threadUrl, window.location.origin);
    url.searchParams.set('conversation_key', conversationId);

    var response = await fetch(url.toString(), {
      method: 'GET',
      credentials: 'same-origin'
    });
    var result = await response.json();

    if (!response.ok || !result.success || !result.thread) {
      throw new Error(result.message || 'Could not load that conversation.');
    }

    return normalizeRemoteThread(result.thread);
  }

  function friendlyLinkLabel(url) {
    try {
      var path = new URL(url, window.location.href).pathname.replace(/\/+$/, '');

      if (/\/booking$/.test(path)) {
        return 'Start booking';
      }

      if (/\/tours$/.test(path)) {
        return 'See all tours and prices';
      }

      if (/\/tours\/[^/]+$/.test(path)) {
        return 'View this tour';
      }
    } catch (error) {
      return 'Open link';
    }

    return 'Open link';
  }

  function renderRichText(target, text) {
    var pattern = /(https?:\/\/[^\s<]+[^\s<.,;:!?)])|(\+264[\d ]{8,13}\d)/g;
    var last = 0;
    var match;

    while ((match = pattern.exec(text)) !== null) {
      if (match.index > last) {
        target.appendChild(document.createTextNode(text.slice(last, match.index)));
      }

      var link = document.createElement('a');
      link.className = 'twostars-chatbot__link';

      if (match[1]) {
        link.href = match[1];
        link.textContent = friendlyLinkLabel(match[1]);
      } else {
        link.href = 'https://wa.me/' + match[2].replace(/\D/g, '');
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = match[2];
        link.setAttribute('data-twostars-event', 'whatsapp_click');
      }

      target.appendChild(link);
      last = match.index + match[0].length;
    }

    if (last < text.length) {
      target.appendChild(document.createTextNode(text.slice(last)));
    }
  }

  function renderEntry(container, entry) {
    if (entry.text) {
      var card = createElement('article', 'twostars-chatbot__message-card twostars-chatbot__message-card--' + entry.role);
      var avatar = createElement('div', 'twostars-chatbot__message-avatar', entry.role === 'user' ? 'You' : 'TS');
      var content = createElement('div', 'twostars-chatbot__message-content');
      var meta = createElement('div', 'twostars-chatbot__message-meta', entry.role === 'user' ? 'Guest' : 'Two Stars Guide');
      var message = createElement('div', 'twostars-chatbot__message-bubble');
      renderRichText(message, entry.text);

      card.appendChild(avatar);
      content.appendChild(meta);
      content.appendChild(message);
      card.appendChild(content);
      container.appendChild(card);
    }

    if (entry.components && entry.components.length) {
      var helpers = getHelpers();

      if (helpers && typeof helpers.renderComponents === 'function') {
        var wrapper = createElement('div', 'twostars-chatbot__component-stack');
        helpers.renderComponents(wrapper, entry.components);
        container.appendChild(wrapper);
      }
    }
  }

  function renderHistory(root, history) {
    var container = root.querySelector('[data-twostars-chatbot-messages]');
    container.innerHTML = '';

    history.forEach(function (entry) {
      renderEntry(container, entry);
    });

    container.scrollTop = container.scrollHeight;
  }

  function getInitialHistory(root) {
    var messages = root.querySelector('[data-twostars-chatbot-messages]');
    return [
      {
        role: 'bot',
        text: messages.getAttribute('data-chatbot-initial') || window.twoStarsChatbot.defaultGreeting,
        components: []
      }
    ];
  }

  function saveHistory(root, history) {
    root.__history = history;
    upsertThread(root, history);
    renderHistory(root, history);
    renderConversationList(root);
  }

  function appendEntry(root, entry) {
    var history = root.__history || [];
    history.push(entry);
    saveHistory(root, history);
  }

  function setTyping(root, active) {
    var typing = root.querySelector('[data-twostars-chatbot-typing]');

    if (typing) {
      typing.hidden = !active;
    }
  }

  function setBusy(root, active) {
    root.dataset.busy = active ? 'true' : 'false';

    var textarea = root.querySelector('textarea[name="message"]');
    var submitButton = root.querySelector('[data-twostars-chatbot-form] button[type="submit"]');
    var resetButton = root.querySelector('[data-twostars-chatbot-reset]');

    if (textarea) {
      textarea.disabled = active;
    }

    if (submitButton) {
      submitButton.disabled = active;
    }

    root.querySelectorAll('[data-chatbot-prompt]').forEach(function (button) {
      button.disabled = active;
    });

    if (resetButton) {
      resetButton.disabled = active;
    }
  }

  function getPersonaLabel(persona) {
    var labels = {
      researcher: 'Researcher',
      comparison_shopper: 'Comparing',
      ready_to_book: 'Ready to book',
      general_browser: 'Exploring'
    };

    return labels[persona] || 'Context-aware';
  }

  function renderContext(root) {
    var platform = getPlatformConfig();
    var contextRoot = root.querySelector('[data-twostars-chatbot-context]');
    var statusRoot = root.querySelector('[data-twostars-chatbot-status]');
    var state = getSessionState();
    var pageContext = platform.pageContext || {};

    if (statusRoot) {
      statusRoot.textContent = getPersonaLabel(state.current_persona || '');
    }

    if (contextRoot) {
      contextRoot.innerHTML = '';

      if (pageContext.title) {
        contextRoot.appendChild(createElement('span', 'twostars-chatbot__chip', pageContext.title));
      }

      if (pageContext.type === 'tour') {
        contextRoot.appendChild(createElement('span', 'twostars-chatbot__chip', 'Tour page'));
      } else if (pageContext.type === 'destination') {
        contextRoot.appendChild(createElement('span', 'twostars-chatbot__chip', 'Destination page'));
      }

      if (!contextRoot.children.length) {
        contextRoot.appendChild(createElement('span', 'twostars-chatbot__chip', 'Walvis Bay & Swakopmund'));
      }
    }
  }

  function getSuggestions() {
    var platform = getPlatformConfig();
    var state = getSessionState();
    var pageContext = platform.pageContext || {};
    var suggestions = [];

    if (pageContext.title) {
      suggestions.push({
        label: pageContext.type === 'tour' ? 'About this tour' : 'About this page',
        prompt: pageContext.type === 'tour'
          ? 'How much is ' + pageContext.title + ', what is included, and who is it best for?'
          : 'Can you explain the highlights, route, pickups, and ideal guest for ' + pageContext.title + '?'
      });
    }

    if (state.visited_tour_titles && state.visited_tour_titles.length >= 2) {
      suggestions.push({
        label: 'Compare tours',
        prompt: 'Compare ' + state.visited_tour_titles.slice(0, 2).join(' and ') + ' for me.'
      });
    }

    if (state.stage === 'high_intent' || state.current_persona === 'ready_to_book') {
      suggestions.push({
        label: 'Next step',
        prompt: 'Help me understand the next booking step, pickups, and what I should confirm with the team.'
      });
    }

    if (state.visited_destination_titles && state.visited_destination_titles.length) {
      suggestions.push({
        label: 'Best season',
        prompt: 'What is the best time to visit ' + state.visited_destination_titles[0] + ' and which Two Stars tour fits it best?'
      });
    }

    suggestions.push(
      {
        label: 'Prices',
        prompt: 'How much do the tours cost, and what do children pay?'
      },
      {
        label: 'Cruise day',
        prompt: 'I am on a cruise. What can I do in a port day and can you get me back to the ship?'
      },
      {
        label: 'Pickups and group size',
        prompt: 'Do you pick up from Walvis Bay and Swakopmund hotels, and how many people are in a group?'
      },
      {
        label: 'Best for photos',
        prompt: 'Which tour is best if I want Sandwich Harbour and great photo stops?'
      },
      {
        label: 'Private tour',
        prompt: 'I want a private tour with flexible timing and pickups. Can you help me shape it?'
      }
    );

    return suggestions.filter(function (item, index, items) {
      return items.findIndex(function (candidate) {
        return candidate.prompt === item.prompt;
      }) === index;
    }).slice(0, 5);
  }

  function renderSuggestions(root) {
    var rail = root.querySelector('[data-twostars-chatbot-rail]');

    if (!rail) {
      return;
    }

    rail.innerHTML = '';

    getSuggestions().forEach(function (item) {
      var button = createElement('button', 'twostars-chatbot__rail-button', item.label);
      button.type = 'button';
      button.setAttribute('data-chatbot-prompt', item.prompt);
      button.addEventListener('click', function () {
        sendMessage(root, item.prompt);
      });
      rail.appendChild(button);
    });
  }

  function buildPayload(root, message) {
    var helpers = getHelpers();
    var payload = helpers && typeof helpers.buildChatPayload === 'function'
      ? helpers.buildChatPayload(message)
      : { message: message };

    payload.conversation_key = root.__conversationId || getStoredCurrentConversationId() || getSessionKey();

    return payload;
  }

  function setConversation(root, conversationId, history) {
    root.__conversationId = conversationId;
    setCurrentConversationId(conversationId);
    saveHistory(root, history);
    renderContext(root);
    renderSuggestions(root);
  }

  function createNewConversation(root) {
    setConversation(root, createConversationId(), getInitialHistory(root));
  }

  async function sendMessage(root, message) {
    if (!message || root.dataset.busy === 'true') {
      return;
    }

    appendEntry(root, {
      role: 'user',
      text: message,
      components: []
    });

    setBusy(root, true);
    setTyping(root, true);

    try {
      var response = await fetch(window.twoStarsChatbot.restUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(buildPayload(root, message))
      });

      var result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || window.twoStarsChatbot.errorMessage);
      }

      if (result.conversation_key) {
        root.__conversationId = result.conversation_key;
        setCurrentConversationId(result.conversation_key);
      }

      var parsed = parseReply(result.reply || window.twoStarsChatbot.defaultGreeting);

      appendEntry(root, {
        role: 'bot',
        text: parsed.text,
        components: parsed.components
      });

      loadRemoteThreads(root);
      renderContext(root);
      renderSuggestions(root);
    } catch (error) {
      appendEntry(root, {
        role: 'bot',
        text: error.message || window.twoStarsChatbot.errorMessage,
        components: []
      });
    } finally {
      setTyping(root, false);
      setBusy(root, false);
    }
  }

  function resetConversation(root) {
    createNewConversation(root);
    toggleHistoryDrawer(root, false);
    var handoff = root.querySelector('[data-twostars-chatbot-handoff]');
    var textarea = root.querySelector('textarea[name="message"]');

    if (handoff) {
      handoff.hidden = true;
    }

    setBusy(root, false);

    if (textarea) {
      window.setTimeout(function () {
        textarea.focus();
      }, 40);
    }
  }

  function formatTimestamp(timestamp) {
    try {
      return new Date(timestamp).toLocaleString([], {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
      });
    } catch (error) {
      return '';
    }
  }

  function renderConversationList(root) {
    var list = root.querySelector('[data-twostars-chatbot-history-list]');
    var search = root.querySelector('[data-twostars-chatbot-history-search]');
    var query = search ? search.value.trim().toLowerCase() : '';
    var threads = getAvailableThreads(root).filter(function (thread) {
      if (!query) {
        return true;
      }

      var haystack = (thread.title + ' ' + thread.preview).toLowerCase();
      return haystack.indexOf(query) !== -1;
    });

    if (!list) {
      return;
    }

    list.innerHTML = '';

    if (!threads.length) {
      list.appendChild(createElement('p', 'twostars-chatbot__history-empty', 'No saved conversations yet.'));
      return;
    }

    threads.forEach(function (thread) {
      var button = createElement('button', 'twostars-chatbot__history-item');
      button.type = 'button';
      button.dataset.conversationId = thread.id;

      if (thread.id === root.__conversationId) {
        button.classList.add('is-active');
      }

      var title = createElement('strong', '', thread.title || 'Tour planning');
      var preview = createElement('span', '', thread.preview || '');
      var meta = createElement('small', '', formatTimestamp(thread.updatedAt));

      button.appendChild(title);
      button.appendChild(preview);
      button.appendChild(meta);
      button.addEventListener('click', async function () {
        button.disabled = true;

        try {
          var remoteThread = await loadRemoteThreadDetail(thread.id);
          var history = remoteThread && Array.isArray(remoteThread.history) && remoteThread.history.length
            ? remoteThread.history
            : (Array.isArray(thread.history) && thread.history.length ? thread.history : getInitialHistory(root));
          setConversation(root, thread.id, history);
        } catch (error) {
          setConversation(root, thread.id, Array.isArray(thread.history) && thread.history.length ? thread.history : getInitialHistory(root));
        } finally {
          button.disabled = false;
          toggleHistoryDrawer(root, false);
        }
      });
      list.appendChild(button);
    });
  }

  function toggleHistoryDrawer(root, open) {
    var drawer = root.querySelector('[data-twostars-chatbot-history-drawer]');

    if (!drawer) {
      return;
    }

    drawer.hidden = !open;

    if (open) {
      loadRemoteThreads(root).finally(function () {
        renderConversationList(root);
      });
      var search = root.querySelector('[data-twostars-chatbot-history-search]');

      if (search) {
        window.setTimeout(function () {
          search.focus();
        }, 40);
      }
    }
  }

  function dismissNudge(wrapper) {
    var nudge = wrapper.querySelector('[data-twostars-chatbot-nudge]');

    window.sessionStorage.setItem(getNudgeCooldownKey(), String(Date.now()));

    if (nudge) {
      nudge.hidden = true;
    }
  }

  function canShowNudge() {
    var dismissedAt = Number(window.sessionStorage.getItem(getNudgeCooldownKey()) || 0);

    return !dismissedAt || (Date.now() - dismissedAt) > NUDGE_COOLDOWN_MS;
  }

  function getNudgeCopy(recommendation) {
    var platform = getPlatformConfig();
    var title = platform.pageContext && platform.pageContext.title ? platform.pageContext.title : '';

    if (recommendation && recommendation.message) {
      return recommendation.message;
    }

    if (title) {
      return 'Can I help you with ' + title + '?';
    }

    return 'Need help choosing the right tour?';
  }

  function showNudge(wrapper, recommendation) {
    var nudge = wrapper.querySelector('[data-twostars-chatbot-nudge]');
    var copy = wrapper.querySelector('[data-twostars-chatbot-nudge-copy]');
    var panel = wrapper.querySelector('[data-twostars-chatbot-panel]');

    if (!nudge || !copy || !panel || !panel.hidden || !canShowNudge()) {
      return;
    }

    copy.textContent = getNudgeCopy(recommendation);
    nudge.hidden = false;
  }

  function initFloatingChatbot(wrapper) {
    var toggle = wrapper.querySelector('[data-twostars-chatbot-toggle]');
    var panel = wrapper.querySelector('[data-twostars-chatbot-panel]');
    var root = wrapper.querySelector('[data-twostars-chatbot]');
    var closeButton = wrapper.querySelector('[data-twostars-chatbot-close]');
    var nudge = wrapper.querySelector('[data-twostars-chatbot-nudge]');
    var nudgeOpen = wrapper.querySelector('[data-twostars-chatbot-nudge-open]');
    var nudgeDismiss = wrapper.querySelector('[data-twostars-chatbot-nudge-dismiss]');

    if (!toggle || !panel || !root) {
      return;
    }

    function setOpen(open) {
      panel.hidden = !open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      wrapper.classList.toggle('is-open', open);

      if (nudge) {
        nudge.hidden = true;
      }

      if (open) {
        var textarea = root.querySelector('textarea[name="message"]');

        if (textarea) {
          window.setTimeout(function () {
            textarea.focus();
          }, 60);
        }
      } else {
        var recommendation = getLatestRecommendation();

        if (recommendation) {
          showNudge(wrapper, recommendation);
        }
      }
    }

    toggle.addEventListener('click', function () {
      setOpen(panel.hidden);
    });

    if (closeButton) {
      closeButton.addEventListener('click', function () {
        setOpen(false);
      });
    }

    if (nudgeOpen) {
      nudgeOpen.addEventListener('click', function () {
        setOpen(true);
      });
    }

    if (nudgeDismiss) {
      nudgeDismiss.addEventListener('click', function () {
        dismissNudge(wrapper);
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        if (!panel.hidden) {
          setOpen(false);
        } else {
          toggleHistoryDrawer(root, false);
        }
      }
    });

    window.addEventListener('twostars:chatRecommendation', function (event) {
      showNudge(wrapper, event.detail || null);
    });

    var recommendation = getLatestRecommendation();

    if (recommendation) {
      showNudge(wrapper, recommendation);
    }
  }

  function initChatbot(root) {
    if (!root || root.dataset.twostarsChatbotReady === 'true') {
      return;
    }

    root.dataset.twostarsChatbotReady = 'true';

    var form = root.querySelector('[data-twostars-chatbot-form]');
    var textarea = form ? form.querySelector('textarea[name="message"]') : null;
    var resetButton = root.querySelector('[data-twostars-chatbot-reset]');
    var historyToggle = root.querySelector('[data-twostars-chatbot-history-toggle]');
    var historyClose = root.querySelector('[data-twostars-chatbot-history-close]');
    var historySearch = root.querySelector('[data-twostars-chatbot-history-search]');
    var storedConversationId = getStoredCurrentConversationId();
    var storedThread = storedConversationId ? findThreadById(storedConversationId) : null;

    if (storedThread && Array.isArray(storedThread.history) && storedThread.history.length) {
      setConversation(root, storedThread.id, storedThread.history);
    } else {
      createNewConversation(root);
    }

    loadRemoteThreads(root).then(function (threads) {
      var storedId = getStoredCurrentConversationId();

      if ((!storedThread || !storedThread.history || !storedThread.history.length) && storedId) {
        var remoteMatch = mergeThreads([], threads).find(function (thread) {
          return thread.id === storedId;
        });

        if (remoteMatch && remoteMatch.history && remoteMatch.history.length) {
          setConversation(root, remoteMatch.id, remoteMatch.history);
        }
      }

      renderConversationList(root);
    });

    renderContext(root);
    renderSuggestions(root);

    if (textarea) {
      textarea.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
          event.preventDefault();
          form.requestSubmit();
        }
      });
    }

    if (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();

        var input = form.querySelector('[name="message"]');
        var value = input && typeof input.value === 'string' ? input.value.trim() : '';

        if (!value) {
          return;
        }

        sendMessage(root, value);
        input.value = '';
      });
    }

    if (resetButton) {
      resetButton.addEventListener('click', function () {
        resetConversation(root);
      });
    }

    if (historyToggle) {
      historyToggle.addEventListener('click', function () {
        var drawer = root.querySelector('[data-twostars-chatbot-history-drawer]');
        toggleHistoryDrawer(root, drawer ? drawer.hidden : true);
      });
    }

    if (historyClose) {
      historyClose.addEventListener('click', function () {
        toggleHistoryDrawer(root, false);
      });
    }

    if (historySearch) {
      historySearch.addEventListener('input', function () {
        renderConversationList(root);
      });
    }

    window.addEventListener('twostars:intelligenceState', function () {
      renderContext(root);
      renderSuggestions(root);
    });
  }

  function initAllChatbots() {
    document.querySelectorAll('[data-twostars-chatbot]').forEach(function (root) {
      initChatbot(root);
    });

    document.querySelectorAll('[data-twostars-chatbot-float]').forEach(function (wrapper) {
      initFloatingChatbot(wrapper);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllChatbots);
  } else {
    initAllChatbots();
  }
})();
