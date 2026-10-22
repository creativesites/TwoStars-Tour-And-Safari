/**
 * Two Stars Gallery — vanilla JS module
 *
 * Features:
 *   - CSS column masonry (masonry layout) or uniform grid
 *   - IntersectionObserver-based item entrance animation with stagger
 *   - Category filter bar (show/hide items by data-category)
 *   - Load-more button + scroll sentinel for infinite-scroll feel
 *   - Lightbox with keyboard nav, swipe support, and item counter
 *   - Video items play inline; open full video in lightbox
 *
 * Loaded only when [data-twostars-gallery-page] is present.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── Boot ──────────────────────────────────────────────────────────

    function initGalleries() {
        document.querySelectorAll('[data-twostars-gallery-page]').forEach(initGallery);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGalleries);
    } else {
        initGalleries();
    }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/twostars-gallery.default',
            function (scope) { initGallery(scope[0]); }
        );
    }

    // ── Per-gallery initialisation ────────────────────────────────────

    function initGallery(section) {
        var lightboxEnabled  = section.hasAttribute('data-gallery-lightbox');
        var layoutStyle      = section.getAttribute('data-gallery-layout') || 'masonry';
        var grid             = section.querySelector('[data-gallery-grid]');
        var lightbox         = section.querySelector('[data-gallery-lightbox-modal]');
        var footer           = section.querySelector('[data-gallery-footer]');
        var loadMoreBtn      = section.querySelector('[data-gallery-load-more]');
        var countEl          = section.querySelector('[data-gallery-count]');
        var sentinel         = section.querySelector('[data-gallery-sentinel]');
        var filterBar        = section.querySelector('[data-gallery-filters]');

        if (!grid) return;

        // ── Pagination state ───────────────────────────────────────────
        var limit       = parseInt(section.getAttribute('data-gallery-limit') || '12', 10);
        var totalItems  = parseInt(section.getAttribute('data-gallery-total') || '0', 10);
        var allItemsHtml = [];

        try {
            allItemsHtml = JSON.parse(section.getAttribute('data-gallery-items') || '[]');
        } catch (e) {
            // silently ignore parse errors
        }

        // Items already in the DOM from PHP render
        var domItems     = Array.from(grid.querySelectorAll('[data-gallery-item]'));
        var currentIndex = domItems.length; // PHP already rendered the first batch
        var isLoading    = false;

        // Active category filter
        var activeFilter = 'all';

        // ── Masonry columns ────────────────────────────────────────────
        var msnryCols = [];

        function getDesiredColCount() {
            var style = window.getComputedStyle(grid);
            var w = window.innerWidth;
            if (w <= 640)  return parseInt(style.getPropertyValue('--gallery-cols-mobile').trim())  || 1;
            if (w <= 1024) return parseInt(style.getPropertyValue('--gallery-cols-tablet').trim()) || 2;
            return parseInt(style.getPropertyValue('--gallery-cols').trim()) || 4;
        }

        function setupMasonry() {
            if (layoutStyle !== 'masonry') return;

            var desired = getDesiredColCount();
            if (msnryCols.length === desired) return; // nothing changed

            // Collect all current items from columns back to a flat list
            var existing = Array.from(grid.querySelectorAll('[data-gallery-item]'));

            // Rebuild columns
            grid.innerHTML = '';
            msnryCols = [];
            for (var i = 0; i < desired; i++) {
                var col = document.createElement('div');
                col.className = 'twostars-gallery-page__col';
                msnryCols.push(col);
                grid.appendChild(col);
            }

            // Re-distribute items round-robin
            existing.forEach(function (item, idx) {
                msnryCols[idx % desired].appendChild(item);
            });
        }

        if (layoutStyle === 'masonry') {
            setupMasonry();

            var resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(setupMasonry, 180);
            });
        }

        // ── Item entrance animation ────────────────────────────────────
        // Uses local IntersectionObserver; items start at opacity:0 via CSS.

        var itemObserver = null;

        if (!reduceMotion && typeof IntersectionObserver !== 'undefined') {
            itemObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        itemObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        }

        function observeItem(item) {
            if (reduceMotion) {
                item.classList.add('is-visible');
                return;
            }
            if (itemObserver) {
                itemObserver.observe(item);
            } else {
                item.classList.add('is-visible');
            }
        }

        // Observe the initial PHP-rendered items
        domItems.forEach(observeItem);

        // ── Append a newly created item element ────────────────────────
        function appendItem(itemEl) {
            if (layoutStyle === 'masonry' && msnryCols.length) {
                msnryCols[domItems.length % msnryCols.length].appendChild(itemEl);
            } else {
                grid.appendChild(itemEl);
            }

            domItems.push(itemEl);
            bindLightboxEvent(itemEl);

            // Trigger entrance animation: item starts invisible (CSS), add is-visible after paint
            if (reduceMotion) {
                itemEl.classList.add('is-visible');
            } else {
                // Clear stagger delay for load-more items (they're already in view)
                itemEl.style.setProperty('--twostars-delay', '0s');
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        itemEl.classList.add('is-visible');
                    });
                });
            }

            // Re-play inline videos
            var video = itemEl.querySelector('video');
            if (video && video.hasAttribute('autoplay')) {
                var promise = video.play();
                if (promise && typeof promise.catch === 'function') {
                    promise.catch(function () {});
                }
            }
        }

        // ── Load more ──────────────────────────────────────────────────
        function appendChunk() {
            if (isLoading || currentIndex >= allItemsHtml.length) return;
            isLoading = true;

            if (loadMoreBtn) loadMoreBtn.classList.add('is-loading');

            var chunk = allItemsHtml.slice(currentIndex, currentIndex + limit);

            chunk.forEach(function (htmlStr) {
                var wrapper = document.createElement('div');
                wrapper.innerHTML = htmlStr.trim();
                var itemEl = wrapper.firstElementChild;
                if (!itemEl) return;

                // Apply category filter state
                if (activeFilter !== 'all') {
                    var cat = itemEl.getAttribute('data-category') || '';
                    if (cat !== activeFilter) {
                        itemEl.setAttribute('data-gallery-hidden', '');
                    }
                }

                appendItem(itemEl);
            });

            currentIndex += chunk.length;

            // Update count label
            updateCountLabel();

            // Remove footer if all items are loaded
            if (currentIndex >= allItemsHtml.length && footer) {
                footer.setAttribute('hidden', '');
            }

            if (loadMoreBtn) loadMoreBtn.classList.remove('is-loading');

            setTimeout(function () {
                isLoading = false;
                checkSentinel();
            }, 120);
        }

        // Load-more button click
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function () {
                appendChunk();
            });
        }

        // Scroll sentinel: auto-load when sentinel enters viewport
        if (sentinel && typeof IntersectionObserver !== 'undefined') {
            var sentinelObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting && !isLoading) {
                        appendChunk();
                    }
                });
            }, { rootMargin: '0px 0px 600px 0px' });
            sentinelObserver.observe(sentinel);
        }

        function checkSentinel() {
            if (!sentinel || currentIndex >= allItemsHtml.length) return;
            var rect = sentinel.getBoundingClientRect();
            if (rect.top < window.innerHeight + 600) appendChunk();
        }

        // ── Count label ────────────────────────────────────────────────
        function updateCountLabel() {
            if (!countEl) return;
            var shownNow = Math.min(currentIndex, totalItems || allItemsHtml.length);
            countEl.textContent = shownNow + ' of ' + (totalItems || allItemsHtml.length) + ' photos';
        }

        // ── Category filter ────────────────────────────────────────────
        if (filterBar) {
            filterBar.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-gallery-filter]');
                if (!btn) return;

                var newFilter = btn.getAttribute('data-gallery-filter') || 'all';
                if (newFilter === activeFilter) return;

                activeFilter = newFilter;

                // Update button states
                filterBar.querySelectorAll('[data-gallery-filter]').forEach(function (b) {
                    var isActive = b.getAttribute('data-gallery-filter') === activeFilter;
                    b.classList.toggle('is-active', isActive);
                    b.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                // Show/hide items
                domItems.forEach(function (item) {
                    if (activeFilter === 'all') {
                        item.removeAttribute('data-gallery-hidden');
                    } else {
                        var cat = item.getAttribute('data-category') || '';
                        if (cat === activeFilter) {
                            item.removeAttribute('data-gallery-hidden');
                        } else {
                            item.setAttribute('data-gallery-hidden', '');
                        }
                    }
                });

                // If masonry, re-balance columns after filtering
                if (layoutStyle === 'masonry' && msnryCols.length) {
                    var visible = domItems.filter(function (i) {
                        return !i.hasAttribute('data-gallery-hidden');
                    });
                    // Re-distribute visible items into columns
                    msnryCols.forEach(function (col) { col.innerHTML = ''; });
                    visible.forEach(function (item, idx) {
                        msnryCols[idx % msnryCols.length].appendChild(item);
                    });
                    // Append hidden items to first col so they remain in DOM
                    domItems.filter(function (i) {
                        return i.hasAttribute('data-gallery-hidden');
                    }).forEach(function (item) {
                        msnryCols[0].appendChild(item);
                    });
                }
            });
        }

        // ── Lightbox ────────────────────────────────────────────────────
        var lightboxMedia   = lightbox ? lightbox.querySelector('[data-gallery-lightbox-media]')   : null;
        var lightboxCaption = lightbox ? lightbox.querySelector('[data-gallery-lightbox-caption]') : null;
        var lightboxCounter = lightbox ? lightbox.querySelector('[data-gallery-lightbox-counter]') : null;
        var closeBtns       = lightbox ? lightbox.querySelectorAll('[data-gallery-lightbox-close]') : [];
        var prevBtn         = lightbox ? lightbox.querySelector('[data-gallery-lightbox-prev]') : null;
        var nextBtn         = lightbox ? lightbox.querySelector('[data-gallery-lightbox-next]') : null;
        var currentLbIndex  = 0;
        var visibleItems    = [];

        function bindLightboxEvent(item) {
            if (!lightboxEnabled || !lightbox) return;
            if (!item.classList.contains('twostars-gallery-page__item--lightbox') &&
                item.getAttribute('data-item-type') !== 'video') return;

            // Prevent double-binding when Elementor re-renders
            if (item._galleryBound) return;
            item._galleryBound = true;

            item.addEventListener('click', function () { openLightbox(item); });
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openLightbox(item);
                }
            });
        }

        domItems.forEach(bindLightboxEvent);

        function getVisibleItems() {
            return domItems.filter(function (i) {
                return !i.hasAttribute('data-gallery-hidden');
            });
        }

        function openLightbox(item) {
            visibleItems    = getVisibleItems();
            currentLbIndex  = visibleItems.indexOf(item);
            if (currentLbIndex === -1) {
                visibleItems   = domItems.slice();
                currentLbIndex = domItems.indexOf(item);
            }
            renderMedia(visibleItems[currentLbIndex]);
            lightbox.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                var closeBtn = lightbox.querySelector('[data-gallery-lightbox-close]');
                if (closeBtn) closeBtn.focus();
            }, 50);
        }

        function closeLightbox() {
            lightbox.setAttribute('hidden', '');
            document.body.style.overflow = '';
            var video = lightboxMedia ? lightboxMedia.querySelector('video') : null;
            if (video) video.pause();
        }

        function renderMedia(item) {
            if (!item || !lightboxMedia) return;
            lightboxMedia.innerHTML = '';

            var caption  = item.getAttribute('data-caption') || '';
            var isVideo  = item.getAttribute('data-item-type') === 'video';

            if (isVideo) {
                var videoUrl    = item.getAttribute('data-video-url') || '';
                var inlineVideo = item.querySelector('video');
                if (inlineVideo && inlineVideo.src) videoUrl = inlineVideo.src;

                var vid = document.createElement('video');
                vid.src       = videoUrl;
                vid.controls  = true;
                vid.autoplay  = true;
                vid.playsInline = true;
                lightboxMedia.appendChild(vid);
            } else {
                var fullUrl = item.getAttribute('data-full-url') || '';
                var thumb   = item.querySelector('img');
                var img     = document.createElement('img');
                img.src = fullUrl || (thumb ? (thumb.getAttribute('src') || '') : '');
                img.alt = caption;
                lightboxMedia.appendChild(img);
            }

            if (lightboxCaption) {
                lightboxCaption.textContent = caption;
            }

            if (lightboxCounter) {
                lightboxCounter.textContent =
                    (currentLbIndex + 1) + ' / ' + visibleItems.length;
            }
        }

        function navigate(direction) {
            var vid = lightboxMedia ? lightboxMedia.querySelector('video') : null;
            if (vid) vid.pause();

            currentLbIndex = (currentLbIndex + direction + visibleItems.length) % visibleItems.length;
            renderMedia(visibleItems[currentLbIndex]);
        }

        if (lightboxEnabled && lightbox) {
            closeBtns.forEach(function (btn) {
                btn.addEventListener('click', closeLightbox);
            });

            if (prevBtn) prevBtn.addEventListener('click', function () { navigate(-1); });
            if (nextBtn) nextBtn.addEventListener('click', function () { navigate(1); });

            document.addEventListener('keydown', function (e) {
                if (!lightbox || lightbox.hasAttribute('hidden')) return;
                if (e.key === 'Escape')     { closeLightbox(); }
                if (e.key === 'ArrowLeft')  { navigate(-1); }
                if (e.key === 'ArrowRight') { navigate(1); }
            });

            // Touch swipe support
            var touchStartX = 0;
            var touchStartY = 0;

            lightbox.addEventListener('touchstart', function (e) {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });

            lightbox.addEventListener('touchend', function (e) {
                var dx = e.changedTouches[0].screenX - touchStartX;
                var dy = e.changedTouches[0].screenY - touchStartY;
                // Only navigate if horizontal swipe is dominant
                if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.5) {
                    navigate(dx < 0 ? 1 : -1);
                }
            }, { passive: true });
        }
    }

    // ── Reviews pagination (kept from original) ───────────────────────

    function initReviewsPagination() {
        var section = document.querySelector('.twostars-gallery-reviews');
        if (!section) return;

        var grid        = section.querySelector('#reviews-grid');
        var loadMoreBtn = section.querySelector('#load-more-reviews');
        if (!grid || !loadMoreBtn) return;

        var allReviews   = (window.twostarsReviewsData && window.twostarsReviewsData.allReviews) ? window.twostarsReviewsData.allReviews : [];
        var currentIndex = 0;
        var limit        = 6;

        function escapeHtml(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function createReviewCard(review, index) {
            var delay = (currentIndex + index) * 0.08;
            var card  = document.createElement('article');
            card.className = 'twostars-gallery-reviews__card';
            card.style.setProperty('--twostars-delay', delay + 's');
            card.setAttribute('data-twostars-animate', 'fade-in-up');

            var stars    = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
            var metaHtml = review.meta ? '<span class="twostars-gallery-reviews__card-meta">' + escapeHtml(review.meta) + '</span>' : '';

            card.innerHTML =
                '<p class="twostars-gallery-reviews__card-rating" aria-label="' + review.rating + ' stars">' + stars + '</p>' +
                '<blockquote class="twostars-gallery-reviews__card-quote">' + escapeHtml(review.quote) + '</blockquote>' +
                '<div class="twostars-gallery-reviews__card-guest">' +
                    '<strong class="twostars-gallery-reviews__card-name">' + escapeHtml(review.author) + '</strong>' +
                    metaHtml +
                '</div>';

            return card;
        }

        function loadMore() {
            var chunk = allReviews.slice(currentIndex, currentIndex + limit);
            if (!chunk.length) { loadMoreBtn.style.display = 'none'; return; }

            chunk.forEach(function (review, i) {
                grid.appendChild(createReviewCard(review, i));
            });

            currentIndex += chunk.length;
            if (currentIndex >= allReviews.length) loadMoreBtn.style.display = 'none';
        }

        loadMoreBtn.addEventListener('click', function () {
            loadMoreBtn.classList.add('is-loading');
            setTimeout(function () {
                loadMore();
                loadMoreBtn.classList.remove('is-loading');
            }, 300);
        });
    }

    document.addEventListener('DOMContentLoaded', initReviewsPagination);

    if (window.elementorFrontend) {
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/twostars-gallery-reviews.default',
            function () { initReviewsPagination(); }
        );
    }

}());
