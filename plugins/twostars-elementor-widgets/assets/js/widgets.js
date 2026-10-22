(function () {
  var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var observer;
  var intelligenceListenersBound = false;
  var currentIntelligenceState = {};
  var latestRecommendation = null;

  function getGenUi() {
    return window.twoStarsGenUi || null;
  }

  function syncIntelligenceCache() {
    var helpers = getGenUi();

    if (!helpers) {
      return;
    }

    if (typeof helpers.getSessionState === "function") {
      currentIntelligenceState = helpers.getSessionState() || {};
    }

    if (typeof helpers.getLatestRecommendation === "function") {
      latestRecommendation = helpers.getLatestRecommendation();
    }
  }

  function getPersonaCopy(fallbackCopy) {
    var persona = currentIntelligenceState.current_persona || "general_browser";

    if (persona === "ready_to_book") {
      return "Booking-ready visitors usually want clarity on availability, pickups, inclusions, and the fastest next step.";
    }

    if (persona === "comparison_shopper") {
      return "The planning panel can help compare Sandwich Harbour, Pelican Point, kayaking, quad biking, and other day-tour options.";
    }

    if (persona === "researcher") {
      return "Use the planning panel to narrow down dunes, wildlife, marine life, camel rides, and 4x4 desert experiences.";
    }

    return fallbackCopy;
  }

  function getPersonaActionLabel(fallbackLabel) {
    var persona = currentIntelligenceState.current_persona || "general_browser";

    if (persona === "ready_to_book") {
      return "Check Availability";
    }

    if (persona === "comparison_shopper") {
      return "Compare Tours";
    }

    if (persona === "researcher") {
      return "Ask About Tours";
    }

    return fallbackLabel;
  }

  function openAssistant(fallbackUrl, actionUrl) {
    if (actionUrl) {
      window.location.href = actionUrl;
      return;
    }

    var toggle = document.querySelector("[data-twostars-chatbot-toggle]");

    if (toggle) {
      if (toggle.getAttribute("aria-expanded") !== "true") {
        toggle.click();
      }

      return;
    }

    var textarea = document.querySelector('[data-twostars-chatbot] textarea[name="message"]');

    if (textarea) {
      if (typeof textarea.scrollIntoView === "function") {
        textarea.scrollIntoView({
          behavior: reduceMotion ? "auto" : "smooth",
          block: "center"
        });
      }

      textarea.focus();
      return;
    }

    if (fallbackUrl) {
      window.location.href = fallbackUrl;
    }
  }

  function initAnimations(root) {
    var elements = root.querySelectorAll("[data-twostars-animate]");

    if (!elements.length) {
      return;
    }

    if (reduceMotion || typeof IntersectionObserver === "undefined") {
      elements.forEach(function (element) {
        element.classList.add("is-visible");
      });
      return;
    }

    if (!observer) {
      observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add("is-visible");
              observer.unobserve(entry.target);
            }
          });
        },
        {
          threshold: 0.15,
          rootMargin: "0px 0px -100px 0px"
        }
      );
    }

    elements.forEach(function (element) {
      if (!element.dataset.twostarsObserved) {
        element.dataset.twostarsObserved = "true";
        observer.observe(element);
      }
    });
  }

  function initNavigation(root) {
    root.querySelectorAll("[data-twostars-nav]").forEach(function (nav) {
      if (nav.dataset.twostarsNavReady) {
        return;
      }

      var toggle = nav.querySelector(".twostars-safari-nav__toggle");
      var menu = nav.querySelector(".twostars-safari-nav__menu");
      var threshold = parseInt(nav.dataset.threshold || "100", 10);

      function syncScrollState() {
        nav.classList.toggle("is-scrolled", window.scrollY > threshold);
      }

      if (toggle && menu) {
        toggle.addEventListener("click", function () {
          var expanded = toggle.getAttribute("aria-expanded") === "true";
          toggle.setAttribute("aria-expanded", expanded ? "false" : "true");
          nav.classList.toggle("is-open", !expanded);
        });

        menu.querySelectorAll("a").forEach(function (link) {
          link.addEventListener("click", function () {
            nav.classList.remove("is-open");
            toggle.setAttribute("aria-expanded", "false");
          });
        });
      }

      syncScrollState();
      window.addEventListener("scroll", syncScrollState, { passive: true });
      nav.dataset.twostarsNavReady = "true";
    });
  }

  function initAssistantTriggers(root) {
    root.querySelectorAll("[data-twostars-assistant-trigger]").forEach(function (button) {
      if (button.dataset.twostarsAssistantReady) {
        return;
      }

      button.addEventListener("click", function () {
        var wrapper = button.closest("[data-twostars-hero-intelligence]");
        var fallbackUrl = button.dataset.fallbackUrl || (wrapper ? wrapper.dataset.fallbackUrl : "") || "";
        var actionUrl = button.dataset.actionUrl || "";
        openAssistant(fallbackUrl, actionUrl);
      });

      button.dataset.twostarsAssistantReady = "true";
    });
  }

  function setStatus(form, message, isError) {
    var status = form.querySelector("[data-twostars-contact-form-status]");

    if (!status) {
      return;
    }

    status.textContent = message;
    status.dataset.state = isError ? "error" : "success";
  }

  function serializeForm(form) {
    var data = new FormData(form);
    var payload = {};

    data.forEach(function (value, key) {
      payload[key] = value;
    });

    if (!data.get("contact_consent")) {
      payload.contact_consent = "";
    }

    return payload;
  }

  function markFieldTouched(field) {
    field.dataset.userTouched = "true";
  }

  function initContactForms(root) {
    root.querySelectorAll("[data-twostars-contact-form]").forEach(function (form) {
      if (!form.dataset.twostarsContactFieldsReady) {
        form.querySelectorAll("input, select, textarea").forEach(function (field) {
          field.addEventListener("input", function () {
            markFieldTouched(field);
          });

          field.addEventListener("change", function () {
            markFieldTouched(field);
          });
        });

        form.dataset.twostarsContactFieldsReady = "true";
      }

      if (form.dataset.twostarsContactReady) {
        return;
      }

      form.addEventListener("submit", async function (event) {
        event.preventDefault();

        var payload = serializeForm(form);
        var button = form.querySelector('button[type="submit"]');
        var endpoint = form.dataset.endpoint || (window.twoStarsWidgets && window.twoStarsWidgets.restUrl) || "";
        var successMessage = form.dataset.successMessage || (window.twoStarsWidgets && window.twoStarsWidgets.defaultSuccessMessage) || "Thanks. Your inquiry has been received and the team can follow up now.";

        if (!payload.email && !payload.phone) {
          setStatus(form, (window.twoStarsWidgets && window.twoStarsWidgets.contactPrompt) || "Enter an email address or phone number.", true);
          return;
        }

        if (!endpoint) {
          setStatus(form, (window.twoStarsWidgets && window.twoStarsWidgets.errorMessage) || "Form endpoint is unavailable.", true);
          return;
        }

        button.disabled = true;
        setStatus(form, (window.twoStarsWidgets && window.twoStarsWidgets.sendingMessage) || "Sending inquiry...", false);

        try {
          var response = await fetch(endpoint, {
            method: "POST",
            headers: {
              "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
          });

          var result = await response.json();

          if (!response.ok) {
            throw new Error(result.message || (window.twoStarsWidgets && window.twoStarsWidgets.errorMessage) || "Something went wrong while sending your request. Please try again.");
          }

          form.reset();
          form.querySelectorAll("[data-user-touched]").forEach(function (field) {
            delete field.dataset.userTouched;
          });
          form.querySelectorAll("input, select, textarea").forEach(function (field) {
            delete field.dataset.userTouched;
          });
          setStatus(form, result.status || successMessage, false);
          window.dispatchEvent(new CustomEvent("twostars:leadCaptured", { detail: result }));
          applyContactIntelligence(document);
        } catch (error) {
          setStatus(form, error.message || (window.twoStarsWidgets && window.twoStarsWidgets.errorMessage) || "Something went wrong while sending your request. Please try again.", true);
        } finally {
          button.disabled = false;
        }
      });

      form.dataset.twostarsContactReady = "true";
    });
  }

  function initTestimonials(root) {
    root.querySelectorAll("[data-twostars-testimonials]").forEach(function (block) {
      if (block.dataset.twostarsTestimonialsReady) {
        return;
      }

      var slides = Array.prototype.slice.call(block.querySelectorAll("[data-slide-index]"));
      var dots = Array.prototype.slice.call(block.querySelectorAll("[data-dot-index]"));
      var prev = block.querySelector("[data-testimonial-prev]");
      var next = block.querySelector("[data-testimonial-next]");
      var autoplay = block.dataset.autoplay === "true" && !reduceMotion;
      var duration = parseInt(block.dataset.duration || "6000", 10);
      var current = 0;
      var timer;

      if (slides.length < 2) {
        block.dataset.twostarsTestimonialsReady = "true";
        return;
      }

      function showSlide(index) {
        current = index;

        slides.forEach(function (slide, slideIndex) {
          var active = slideIndex === current;
          slide.classList.toggle("is-active", active);
          slide.setAttribute("aria-hidden", active ? "false" : "true");
        });

        dots.forEach(function (dot, dotIndex) {
          dot.classList.toggle("is-active", dotIndex === current);
        });
      }

      function startTimer() {
        if (!autoplay) {
          return;
        }

        window.clearInterval(timer);
        timer = window.setInterval(function () {
          showSlide((current + 1) % slides.length);
        }, duration);
      }

      dots.forEach(function (dot, index) {
        dot.addEventListener("click", function () {
          showSlide(index);
          startTimer();
        });
      });

      if (prev) {
        prev.addEventListener("click", function () {
          showSlide((current - 1 + slides.length) % slides.length);
          startTimer();
        });
      }

      if (next) {
        next.addEventListener("click", function () {
          showSlide((current + 1) % slides.length);
          startTimer();
        });
      }

      showSlide(0);
      startTimer();
      block.dataset.twostarsTestimonialsReady = "true";
    });
  }

  function initHomeHeroSliders(root) {
    root.querySelectorAll("[data-twostars-home-hero-slider]").forEach(function (block) {
      if (block.dataset.twostarsHomeHeroSliderReady) {
        return;
      }

      var slides = Array.prototype.slice.call(block.querySelectorAll("[data-home-hero-slide-index]"));
      var dots = Array.prototype.slice.call(block.querySelectorAll("[data-home-hero-dot-index]"));
      var prev = block.querySelector("[data-home-hero-prev]");
      var next = block.querySelector("[data-home-hero-next]");
      var autoplay = block.dataset.autoplay !== "false" && !reduceMotion;
      var duration = parseInt(block.dataset.duration || "5200", 10);
      var current = 0;
      var timer;

      if (slides.length < 2) {
        block.dataset.twostarsHomeHeroSliderReady = "true";
        return;
      }

      function showSlide(index) {
        current = index;

        slides.forEach(function (slide, slideIndex) {
          var active = slideIndex === current;
          slide.classList.toggle("is-active", active);
          slide.setAttribute("aria-hidden", active ? "false" : "true");
        });

        dots.forEach(function (dot, dotIndex) {
          dot.classList.toggle("is-active", dotIndex === current);
        });
      }

      function startTimer() {
        if (!autoplay) {
          return;
        }

        window.clearInterval(timer);
        timer = window.setInterval(function () {
          showSlide((current + 1) % slides.length);
        }, duration);
      }

      dots.forEach(function (dot, index) {
        dot.addEventListener("click", function () {
          showSlide(index);
          startTimer();
        });
      });

      if (prev) {
        prev.addEventListener("click", function () {
          showSlide((current - 1 + slides.length) % slides.length);
          startTimer();
        });
      }

      if (next) {
        next.addEventListener("click", function () {
          showSlide((current + 1) % slides.length);
          startTimer();
        });
      }

      showSlide(0);
      startTimer();
      block.dataset.twostarsHomeHeroSliderReady = "true";
    });
  }

  function initTourFilters(root) {
    var filterBars = root.querySelectorAll("[data-twostars-filter-bar]");

    filterBars.forEach(function (bar) {
      if (bar.dataset.twostarsFiltersReady) {
        return;
      }

      var group = bar.dataset.filterGroup;
      var grid = document.querySelector('[data-twostars-tour-grid][data-filter-group="' + group + '"]');
      var emptyState = document.querySelector('[data-twostars-tour-empty][data-filter-group="' + group + '"]');
      var activeFilters = {};

      if (!grid) {
        bar.dataset.twostarsFiltersReady = "true";
        return;
      }

      function applyFilters() {
        var cards = grid.querySelectorAll("[data-twostars-tour-card]");
        var visibleCount = 0;

        cards.forEach(function (card) {
          var matches = Object.keys(activeFilters).every(function (taxonomy) {
            var value = activeFilters[taxonomy];

            if (!value) {
              return true;
            }

            var terms = (card.dataset[taxonomy.replace(/-([a-z])/g, function (_, letter) {
              return letter.toUpperCase();
            })] || "").split(/\s+/).filter(Boolean);

            return terms.indexOf(value) !== -1;
          });

          card.hidden = !matches;

          if (matches) {
            visibleCount += 1;
          }
        });

        if (emptyState) {
          emptyState.hidden = visibleCount > 0;
        }
      }

      bar.querySelectorAll("[data-taxonomy]").forEach(function (button) {
        button.addEventListener("click", function () {
          var taxonomy = button.dataset.taxonomy;
          var term = button.dataset.term || "";

          bar.querySelectorAll('[data-taxonomy="' + taxonomy + '"]').forEach(function (sibling) {
            sibling.classList.remove("is-active");
          });

          button.classList.add("is-active");
          activeFilters[taxonomy.replace(/^tour_/, "tour-")] = term;
          applyFilters();
        });
      });

      applyFilters();
      bar.dataset.twostarsFiltersReady = "true";
    });
  }

  function initReviewForms(root) {
    root.querySelectorAll("[data-twostars-reviews]").forEach(function (block) {
      if (block.dataset.twostarsReviewsReady) {
        return;
      }

      var modal = block.querySelector("[data-twostars-review-modal]");
      var openButtons = block.querySelectorAll("[data-twostars-review-open]");
      var closeButtons = block.querySelectorAll("[data-twostars-review-close]");
      var form = block.querySelector("[data-twostars-review-form]");
      var status = block.querySelector("[data-twostars-review-status]");
      var thankyou = block.querySelector("[data-twostars-review-thankyou]");

      function openModal() {
        if (!modal) {
          return;
        }

        modal.hidden = false;
        document.body.classList.add("twostars-review-open");
      }

      function closeModal() {
        if (!modal) {
          return;
        }

        modal.hidden = true;
        document.body.classList.remove("twostars-review-open");
      }

      openButtons.forEach(function (button) {
        button.addEventListener("click", openModal);
      });

      closeButtons.forEach(function (button) {
        button.addEventListener("click", closeModal);
      });

      if (modal) {
        modal.addEventListener("click", function (event) {
          if (event.target === modal) {
            closeModal();
          }
        });
      }

      block.querySelectorAll("[data-twostars-star-picker]").forEach(function (picker) {
        if (picker.dataset.twostarsStarPickerReady) {
          return;
        }

        var input = picker.querySelector('input[name="rating"]');
        var stars = Array.prototype.slice.call(picker.querySelectorAll("[data-rating]"));

        function syncStars(value) {
          stars.forEach(function (star) {
            var active = parseInt(star.dataset.rating || "0", 10) <= value;
            star.classList.toggle("is-active", active);
          });
        }

        stars.forEach(function (star) {
          star.addEventListener("click", function () {
            var value = parseInt(star.dataset.rating || "5", 10);
            input.value = String(value);
            syncStars(value);
          });
        });

        syncStars(parseInt(input.value || "5", 10));
        picker.dataset.twostarsStarPickerReady = "true";
      });

      if (form) {
        form.addEventListener("submit", async function (event) {
          event.preventDefault();

          var endpoint = (window.twoStarsWidgets && window.twoStarsWidgets.reviewUrl) || "";
          var submit = form.querySelector('button[type="submit"]');
          var payload = {};
          var formData = new FormData(form);

          formData.forEach(function (value, key) {
            payload[key] = value;
          });

          if (!payload.full_name || !payload.review || !payload.rating) {
            if (status) {
              status.textContent = "Please complete your name, rating, and review.";
              status.dataset.state = "error";
            }
            return;
          }

          if (!payload.review_consent) {
            if (status) {
              status.textContent = "Please confirm that we may use your review.";
              status.dataset.state = "error";
            }
            return;
          }

          if (!endpoint) {
            if (status) {
              status.textContent = (window.twoStarsWidgets && window.twoStarsWidgets.reviewErrorMessage) || "Review submission is unavailable right now.";
              status.dataset.state = "error";
            }
            return;
          }

          payload.review_consent = "1";

          if (window.twoStarsGenUi && typeof window.twoStarsGenUi.getSessionState === "function") {
            var sessionState = window.twoStarsGenUi.getSessionState() || {};
            payload.session_key = sessionState.session_key || "";
            payload.visitor_profile_id = sessionState.visitor_profile_id || 0;
          }

          if (submit) {
            submit.disabled = true;
          }

          if (status) {
            status.textContent = (window.twoStarsWidgets && window.twoStarsWidgets.sendingReviewMessage) || "Sending your review...";
            status.dataset.state = "pending";
          }

          try {
            var response = await fetch(endpoint, {
              method: "POST",
              headers: {
                "Content-Type": "application/json"
              },
              body: JSON.stringify(payload)
            });

            var result = await response.json();

            if (!response.ok) {
              throw new Error(result.message || (window.twoStarsWidgets && window.twoStarsWidgets.reviewErrorMessage) || "Something went wrong while sending your review. Please try again.");
            }

            form.hidden = true;
            if (thankyou) {
              thankyou.hidden = false;
            }

            if (status) {
              status.textContent = result.status || (window.twoStarsWidgets && window.twoStarsWidgets.defaultReviewSuccessMessage) || "";
              status.dataset.state = "success";
            }
          } catch (error) {
            if (status) {
              status.textContent = error.message || (window.twoStarsWidgets && window.twoStarsWidgets.reviewErrorMessage) || "Something went wrong while sending your review. Please try again.";
              status.dataset.state = "error";
            }
          } finally {
            if (submit) {
              submit.disabled = false;
            }
          }
        });
      }

      block.dataset.twostarsReviewsReady = "true";
    });
  }

  function applyHeroIntelligence(root) {
    root.querySelectorAll("[data-twostars-hero-intelligence]").forEach(function (panel) {
      var copy = panel.querySelector("[data-twostars-hero-intelligence-copy]");
      var button = panel.querySelector("[data-twostars-assistant-trigger]");
      var defaultCopy = panel.dataset.defaultCopy || "The site can adapt its guidance around day-tour research, experience comparison, and booking readiness as visitors move through the homepage.";
      var defaultLabel = panel.dataset.defaultLabel || "Plan My Tour";
      var message = latestRecommendation && latestRecommendation.message ? latestRecommendation.message : getPersonaCopy(defaultCopy);
      var label = latestRecommendation && latestRecommendation.primary && latestRecommendation.primary.label
        ? latestRecommendation.primary.label
        : getPersonaActionLabel(defaultLabel);
      var actionUrl = "";

      if (latestRecommendation && latestRecommendation.primary && latestRecommendation.primary.action !== "open_assistant" && latestRecommendation.primary.url) {
        actionUrl = latestRecommendation.primary.url;
      }

      if (copy) {
        copy.textContent = message;
      }

      if (button) {
        button.textContent = label;
        button.dataset.actionUrl = actionUrl;
        button.dataset.fallbackUrl = panel.dataset.fallbackUrl || (latestRecommendation && latestRecommendation.secondary && latestRecommendation.secondary.url) || "";
      }
    });

    root.querySelectorAll("[data-twostars-home-hero-panel]").forEach(function (panel) {
      var copy = panel.querySelector("[data-twostars-home-hero-copy]");
      var button = panel.querySelector("[data-twostars-assistant-trigger]");
      var defaultCopy = panel.dataset.defaultCopy || "The homepage can respond to tour research, experience comparison, and booking readiness so the visitor always sees a relevant next step.";
      var defaultLabel = panel.dataset.defaultLabel || "Plan My Tour";
      var message = latestRecommendation && latestRecommendation.message ? latestRecommendation.message : getPersonaCopy(defaultCopy);
      var label = latestRecommendation && latestRecommendation.primary && latestRecommendation.primary.label
        ? latestRecommendation.primary.label
        : getPersonaActionLabel(defaultLabel);
      var actionUrl = "";

      if (latestRecommendation && latestRecommendation.primary && latestRecommendation.primary.action !== "open_assistant" && latestRecommendation.primary.url) {
        actionUrl = latestRecommendation.primary.url;
      }

      if (copy) {
        copy.textContent = message;
      }

      if (button) {
        button.textContent = label;
        button.dataset.actionUrl = actionUrl;
        button.dataset.fallbackUrl = panel.dataset.fallbackUrl || (latestRecommendation && latestRecommendation.secondary && latestRecommendation.secondary.url) || "";
      }
    });
  }

  function getExperienceScore(card) {
    var score = 0;
    var tourId = parseInt(card.dataset.tourId || "0", 10);
    var destinationId = parseInt(card.dataset.destinationId || "0", 10);
    var visitedTours = currentIntelligenceState.visited_tours || [];
    var visitedDestinations = currentIntelligenceState.visited_destinations || [];
    var persona = currentIntelligenceState.current_persona || "general_browser";
    var contentType = card.dataset.contentType || "manual";

    if (tourId && visitedTours.indexOf(tourId) !== -1) {
      score += 80;
    }

    if (destinationId && visitedDestinations.indexOf(destinationId) !== -1) {
      score += 70;
    }

    if (persona === "ready_to_book" && contentType === "tour") {
      score += 15;
    }

    if (persona === "researcher" && contentType === "destination") {
      score += 15;
    }

    if (latestRecommendation && typeof latestRecommendation.key === "string") {
      if (latestRecommendation.key.indexOf("comparison") !== -1 && contentType === "tour") {
        score += 10;
      }

      if (latestRecommendation.key.indexOf("ready_to_book") !== -1 && contentType === "tour") {
        score += 12;
      }
    }

    return score;
  }

  function getExperienceBadge(card, score) {
    var tourId = parseInt(card.dataset.tourId || "0", 10);
    var destinationId = parseInt(card.dataset.destinationId || "0", 10);
    var visitedTours = currentIntelligenceState.visited_tours || [];
    var visitedDestinations = currentIntelligenceState.visited_destinations || [];
    var persona = currentIntelligenceState.current_persona || "general_browser";

    if (tourId && visitedTours.indexOf(tourId) !== -1) {
      if (persona === "ready_to_book") {
        return "Continue Planning";
      }

      if (persona === "comparison_shopper") {
        return "Worth Comparing";
      }

      return "Recently Viewed";
    }

    if (destinationId && visitedDestinations.indexOf(destinationId) !== -1) {
      if (persona === "researcher") {
        return "Matches Your Research";
      }

      return "In Your Shortlist";
    }

    if (score >= 15 && persona === "ready_to_book") {
      return "High-Intent Pick";
    }

    if (score >= 15 && persona === "researcher") {
      return "Research Lens";
    }

    return "";
  }

  function applyExperienceIntelligence(root) {
    root.querySelectorAll("[data-twostars-experience-root]").forEach(function (grid) {
      var cards = Array.prototype.slice.call(grid.querySelectorAll("[data-twostars-experience-card]"));

      cards.forEach(function (card, index) {
        if (!card.dataset.originalIndex) {
          card.dataset.originalIndex = String(index);
        }
      });

      cards.sort(function (left, right) {
        var scoreDiff = getExperienceScore(right) - getExperienceScore(left);

        if (scoreDiff !== 0) {
          return scoreDiff;
        }

        return parseInt(left.dataset.originalIndex || "0", 10) - parseInt(right.dataset.originalIndex || "0", 10);
      });

      cards.forEach(function (card) {
        var badge = card.querySelector("[data-twostars-experience-badge]");
        var score = getExperienceScore(card);
        var label = getExperienceBadge(card, score);

        grid.appendChild(card);
        card.classList.toggle("is-prioritized", score > 0);

        if (!badge) {
          return;
        }

        if (!badge.dataset.defaultLabel) {
          badge.dataset.defaultLabel = badge.textContent.trim();
        }

        if (label) {
          badge.textContent = label;
          badge.classList.add("is-visible");
        } else if (badge.dataset.defaultLabel) {
          badge.textContent = badge.dataset.defaultLabel;
          badge.classList.toggle("is-visible", badge.dataset.defaultLabel !== "");
        } else {
          badge.textContent = "";
          badge.classList.remove("is-visible");
        }
      });
    });
  }

  function getCollectionBadge(card, score) {
    var tourId = parseInt(card.dataset.tourId || "0", 10);
    var visitedTours = currentIntelligenceState.visited_tours || [];
    var persona = currentIntelligenceState.current_persona || "general_browser";

    if (tourId && visitedTours.indexOf(tourId) !== -1) {
      if (persona === "ready_to_book") {
        return "Booking Focus";
      }

      if (persona === "comparison_shopper") {
        return "Compare This Tour";
      }

      return "You Viewed This";
    }

    if (score >= 15 && persona === "ready_to_book") {
      return "Strong Fit";
    }

    return "Signature Experience";
  }

  function applyCollectionIntelligence(root) {
    root.querySelectorAll("[data-twostars-home-collection]").forEach(function (grid) {
      var cards = Array.prototype.slice.call(grid.querySelectorAll("[data-twostars-home-collection-card]"));

      cards.forEach(function (card, index) {
        if (!card.dataset.originalIndex) {
          card.dataset.originalIndex = String(index);
        }
      });

      cards.sort(function (left, right) {
        var scoreDiff = getExperienceScore(right) - getExperienceScore(left);

        if (scoreDiff !== 0) {
          return scoreDiff;
        }

        return parseInt(left.dataset.originalIndex || "0", 10) - parseInt(right.dataset.originalIndex || "0", 10);
      });

      cards.forEach(function (card) {
        var badge = card.querySelector("[data-twostars-home-collection-badge]");
        var score = getExperienceScore(card);

        grid.appendChild(card);
        card.classList.toggle("is-prioritized", score > 0);

        if (badge) {
          badge.textContent = getCollectionBadge(card, score);
        }
      });
    });

    root.querySelectorAll("[data-twostars-home-collection-strip]").forEach(function (strip) {
      var copy = strip.querySelector("[data-twostars-home-collection-copy]");
      var button = strip.querySelector("[data-twostars-assistant-trigger]");
      var defaultCopy = strip.dataset.defaultCopy || "";
      var defaultLabel = strip.dataset.defaultLabel || "Ask About Tours";

      if (copy) {
        copy.textContent = latestRecommendation && latestRecommendation.message
          ? latestRecommendation.message
          : getContactIntelligenceCopy(defaultCopy);
      }

      if (button) {
        button.textContent = latestRecommendation && latestRecommendation.primary && latestRecommendation.primary.label
          ? latestRecommendation.primary.label
          : getPersonaActionLabel(defaultLabel);
        button.dataset.actionUrl = latestRecommendation && latestRecommendation.primary && latestRecommendation.primary.action !== "open_assistant" && latestRecommendation.primary.url
          ? latestRecommendation.primary.url
          : "";
        button.dataset.fallbackUrl = strip.dataset.fallbackUrl || (latestRecommendation && latestRecommendation.secondary && latestRecommendation.secondary.url) || "";
      }
    });
  }

  function getContactIntelligenceCopy(defaultCopy) {
    if (latestRecommendation && latestRecommendation.message) {
      return latestRecommendation.message;
    }

    var persona = currentIntelligenceState.current_persona || "general_browser";

    if (persona === "ready_to_book") {
      return "Your browsing suggests strong booking intent. Submit here and the team can follow up on timing, pickups, inclusions, and the cleanest next step.";
    }

    if (persona === "comparison_shopper") {
      return "Your session looks comparison-heavy. Use the form to get a tighter recommendation between the tour options you have been weighing.";
    }

    if (persona === "researcher") {
      return "The site has picked up research patterns. Use the form to turn those interests into the right coastal, desert, or wildlife experience.";
    }

    return defaultCopy;
  }

  function getMessagePlaceholder(defaultPlaceholder) {
    var persona = currentIntelligenceState.current_persona || "general_browser";

    if (persona === "ready_to_book") {
      return "Share your dates, guest count, pickup point, and the tour you want priced or confirmed.";
    }

    if (persona === "comparison_shopper") {
      return "Tell us which tours you are comparing and what matters most for the final choice.";
    }

    if (persona === "researcher") {
      return "Tell us whether you want dunes, kayaking, marine life, birding, camel rides, or quad biking so we can suggest the right tour.";
    }

    return defaultPlaceholder;
  }

  function applyContactIntelligence(root) {
    root.querySelectorAll("[data-twostars-contact-intelligence]").forEach(function (note) {
      var copy = note.querySelector("[data-twostars-contact-intelligence-copy]");
      var defaultCopy = note.dataset.defaultCopy || "";

      if (copy) {
        copy.textContent = getContactIntelligenceCopy(defaultCopy);
      }
    });

    root.querySelectorAll('[data-twostars-contact-form][data-intelligence-enabled="true"]').forEach(function (form) {
      var tourSelect = form.querySelector('select[name="tour_interest"]');
      var messageField = form.querySelector('textarea[name="message"]');
      var visitedTours = currentIntelligenceState.visited_tours || [];

      if (tourSelect && !tourSelect.value && !tourSelect.dataset.userTouched && visitedTours.length) {
        tourSelect.value = String(visitedTours[visitedTours.length - 1]);
      }

      if (messageField) {
        if (!messageField.dataset.defaultPlaceholder) {
          messageField.dataset.defaultPlaceholder = messageField.getAttribute("placeholder") || "";
        }

        if (!messageField.dataset.userTouched) {
          messageField.setAttribute("placeholder", getMessagePlaceholder(messageField.dataset.defaultPlaceholder));
        }
      }
    });
  }

  function applyIntelligence(root) {
    syncIntelligenceCache();
    initAssistantTriggers(root);
    applyHeroIntelligence(root);
    applyExperienceIntelligence(root);
    applyCollectionIntelligence(root);
    applyContactIntelligence(root);
  }

  function bindIntelligenceEvents() {
    if (intelligenceListenersBound) {
      return;
    }

    window.addEventListener("twostars:intelligenceState", function (event) {
      currentIntelligenceState = event.detail || currentIntelligenceState;
      applyIntelligence(document);
    });

    window.addEventListener("twostars:chatRecommendation", function (event) {
      latestRecommendation = event.detail || latestRecommendation;
      applyIntelligence(document);
    });

    intelligenceListenersBound = true;
  }

  function boot(root) {
    initAnimations(root);
    initNavigation(root);
    initContactForms(root);
    initHomeHeroSliders(root);
    initTestimonials(root);
    initTourFilters(root);
    initReviewForms(root);
    applyIntelligence(root);
  }

  function onReady() {
    syncIntelligenceCache();
    bindIntelligenceEvents();
    boot(document);

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
      window.elementorFrontend.hooks.addAction("frontend/element_ready/global", function ($scope) {
        if ($scope && $scope[0]) {
          boot($scope[0]);
        }
      });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", onReady);
  } else {
    onReady();
  }
})();
