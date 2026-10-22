(function () {
  function readMapFilters(node) {
    return {
      country: node.getAttribute('data-country') || '',
      city: node.getAttribute('data-city') || '',
      status: node.getAttribute('data-status') || '',
      type: node.getAttribute('data-type') || '',
      linked: node.getAttribute('data-linked') || ''
    };
  }

  function readInlinePoints(node) {
    var raw = node.getAttribute('data-points');

    if (!raw) {
      return null;
    }

    try {
      var parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : null;
    } catch (error) {
      return null;
    }
  }

  async function fetchMapPoints(node) {
    var config = window.twoStarsVisitorsAdmin || {};
    var inlinePoints = readInlinePoints(node);

    if (inlinePoints) {
      return inlinePoints;
    }

    var url = new URL(config.mapUrl || '', window.location.origin);
    var filters = readMapFilters(node);

    Object.keys(filters).forEach(function (key) {
      if (filters[key]) {
        url.searchParams.set(key, filters[key]);
      }
    });

    var response = await fetch(url.toString(), {
      headers: {
        'X-WP-Nonce': config.nonce || ''
      },
      credentials: 'same-origin'
    });

    if (!response.ok) {
      throw new Error('Could not load visitor map data.');
    }

    var payload = await response.json();
    return Array.isArray(payload.points) ? payload.points : [];
  }

  function buildPopup(point) {
    var wrapper = document.createElement('div');
    wrapper.className = 'twostars-visitor-map__popup';

    var title = document.createElement('strong');
    title.textContent = [point.city, point.countryName].filter(Boolean).join(', ') || point.countryCode || 'Visitor';
    wrapper.appendChild(title);

    var meta = document.createElement('p');
    meta.textContent = [
      point.returning ? 'Returning visitor' : 'New visitor',
      point.latestStage || ''
    ].filter(Boolean).join(' • ');
    wrapper.appendChild(meta);

    if (point.locationSource) {
      var source = document.createElement('p');
      source.textContent = 'Source: ' + formatLabel(point.locationSource);
      wrapper.appendChild(source);
    }

    if (point.lastSeenAt) {
      var seen = document.createElement('p');
      seen.textContent = 'Last seen: ' + point.lastSeenAt;
      wrapper.appendChild(seen);
    }

    if (point.detailUrl) {
      var link = document.createElement('a');
      link.href = point.detailUrl;
      link.textContent = 'Open profile';
      wrapper.appendChild(link);
    }

    return wrapper;
  }

  function formatLabel(value) {
    var normalized = String(value || '').toLowerCase();

    if (normalized === 'visitorapi') {
      return 'VisitorAPI';
    }

    return String(value || '')
      .replace(/_/g, ' ')
      .replace(/\b\w/g, function (character) { return character.toUpperCase(); });
  }

  async function initMap(node) {
    if (typeof window.L === 'undefined') {
      return;
    }

    try {
      var points = await fetchMapPoints(node);
      var map = window.L.map(node, {
        scrollWheelZoom: false
      }).setView([-22.9576, 14.5053], 5);

      window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      var cluster = typeof window.L.markerClusterGroup === 'function' ? window.L.markerClusterGroup() : null;
      var bounds = [];

      points.forEach(function (point) {
        if (!point || typeof point.lat !== 'number' || typeof point.lng !== 'number') {
          return;
        }

        var marker = window.L.marker([point.lat, point.lng]);
        marker.bindPopup(buildPopup(point));
        bounds.push([point.lat, point.lng]);

        if (cluster) {
          cluster.addLayer(marker);
        } else {
          marker.addTo(map);
        }
      });

      if (cluster) {
        map.addLayer(cluster);
      }

      if (bounds.length === 1) {
        map.setView(bounds[0], Number(node.getAttribute('data-zoom') || 8));
      } else if (bounds.length) {
        map.fitBounds(bounds, { padding: [40, 40] });
      } else {
        node.innerHTML = '<div class="twostars-visitor-map__empty">No visitor profiles with usable location data match these filters.</div>';
      }
    } catch (error) {
      node.innerHTML = '<div class="notice notice-error inline"><p>Visitor map data could not be loaded.</p></div>';
    }
  }

  document.querySelectorAll('[data-twostars-visitor-map]').forEach(initMap);
})();
