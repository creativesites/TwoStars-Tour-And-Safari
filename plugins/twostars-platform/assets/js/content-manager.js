(function ($) {
  function escapeHtml(value) {
    return $('<div />').text(value == null ? '' : String(value)).html();
  }

  function initMediaFields() {
    $('[data-twostars-media-field]').each(function () {
      var field = $(this);
      var input = $('#' + field.data('target-input'));
      var preview = field.find('[data-twostars-media-preview]');
      var multiple = String(field.data('multiple')) !== '0';
      var frame = null;

      function thumbMarkup(attachmentId, imageUrl) {
        return '' +
          '<div class="twostars-media-field__thumb" data-attachment-id="' + attachmentId + '">' +
            '<img src="' + imageUrl + '" alt="">' +
            '<button type="button" class="twostars-media-field__remove" data-twostars-media-remove aria-label="Remove image">' +
              '<span aria-hidden="true">x</span>' +
            '</button>' +
          '</div>';
      }

      function syncPreviewOrder() {
        var ids = [];

        preview.children('[data-attachment-id]').each(function () {
          var attachmentId = parseInt($(this).attr('data-attachment-id'), 10);

          if (!isNaN(attachmentId) && attachmentId > 0) {
            ids.push(attachmentId);
          }
        });

        input.val(ids.join(','));
      }

      function initSortable() {
        if (!multiple || typeof preview.sortable !== 'function') {
          return;
        }

        if (!preview.data('twostars-sortable')) {
          preview.sortable({
            items: '.twostars-media-field__thumb',
            tolerance: 'pointer',
            placeholder: 'twostars-media-field__thumb twostars-media-field__thumb--placeholder',
            update: syncPreviewOrder
          });
          preview.data('twostars-sortable', true);
        } else {
          preview.sortable('refresh');
        }
      }

      function renderSelection(attachments) {
        preview.empty();

        attachments.each(function (attachment) {
          var data = attachment.toJSON();
          var imageUrl = '';

          if (data.sizes && data.sizes.thumbnail) {
            imageUrl = data.sizes.thumbnail.url;
          } else if (data.url) {
            imageUrl = data.url;
          }

          if (imageUrl) {
            preview.append(thumbMarkup(data.id, imageUrl));
          }
        });

        syncPreviewOrder();
        initSortable();
      }

      field.find('[data-twostars-media-open]').on('click', function (event) {
        event.preventDefault();

        if (frame) {
          frame.open();
          return;
        }

        frame = wp.media({
          title: multiple ? 'Choose gallery images' : 'Choose image',
          button: { text: multiple ? 'Use selected images' : 'Use selected image' },
          multiple: multiple
        });

        frame.on('select', function () {
          renderSelection(frame.state().get('selection'));
        });

        frame.open();
      });

      field.find('[data-twostars-media-clear]').on('click', function (event) {
        event.preventDefault();
        input.val('');
        preview.empty();
      });

      preview.on('click', '[data-twostars-media-remove]', function (event) {
        event.preventDefault();
        $(this).closest('[data-attachment-id]').remove();
        syncPreviewOrder();

        if (preview.data('twostars-sortable')) {
          preview.sortable('refresh');
        }
      });

      initSortable();
      syncPreviewOrder();
    });
  }

  function initGalleryVideoLibraries() {
    $('[data-twostars-gallery-video-library]').each(function () {
      var library = $(this);
      var input = $('#' + library.data('target-input'));
      var list = library.find('[data-twostars-gallery-video-list]');
      var frame = null;
      var activeItem = null;

      function videoPreviewMarkup(url) {
        if (!url) {
          return '<div class="twostars-gallery-video-library__empty">Choose a WordPress video or paste a direct video URL.</div>';
        }

        return '<video src="' + escapeHtml(url) + '" controls preload="metadata" playsinline></video>';
      }

      function itemMarkup(item) {
        var videoUrl = escapeHtml(item.video_url || '');
        var caption = escapeHtml(item.video_caption || '');
        var category = escapeHtml(item.video_category || '');
        var position = Math.max(1, parseInt(item.video_position, 10) || 1);
        var autoplay = item.video_autoplay === 'yes' ? ' checked' : '';
        var muted = item.video_muted === 'yes' ? ' checked' : '';
        var loop = item.video_loop === 'yes' ? ' checked' : '';

        return '' +
          '<div class="twostars-gallery-video-library__item" data-twostars-gallery-video-item>' +
            '<div class="twostars-gallery-video-library__handle" data-twostars-gallery-video-handle aria-hidden="true">::</div>' +
            '<div class="twostars-gallery-video-library__preview" data-twostars-gallery-video-preview>' + videoPreviewMarkup(item.video_url || '') + '</div>' +
            '<div class="twostars-gallery-video-library__body">' +
              '<div class="twostars-gallery-video-library__row">' +
                '<label class="twostars-gallery-video-library__field">' +
                  '<span>Video URL</span>' +
                  '<input type="url" value="' + videoUrl + '" placeholder="https://..." data-video-field="video_url">' +
                '</label>' +
                '<div class="twostars-gallery-video-library__inline-actions">' +
                  '<button type="button" class="button button-secondary" data-twostars-gallery-video-media>Choose Uploaded Video</button>' +
                  '<button type="button" class="button-link-delete" data-twostars-gallery-video-remove>Remove</button>' +
                '</div>' +
              '</div>' +
              '<div class="twostars-gallery-video-library__grid">' +
                '<label class="twostars-gallery-video-library__field">' +
                  '<span>Caption</span>' +
                  '<input type="text" value="' + caption + '" data-video-field="video_caption">' +
                '</label>' +
                '<label class="twostars-gallery-video-library__field">' +
                  '<span>Category Slug</span>' +
                  '<input type="text" value="' + category + '" placeholder="wildlife" data-video-field="video_category">' +
                '</label>' +
                '<label class="twostars-gallery-video-library__field twostars-gallery-video-library__field--compact">' +
                  '<span>Insert Before Image #</span>' +
                  '<input type="number" min="1" step="1" value="' + position + '" data-video-field="video_position">' +
                '</label>' +
              '</div>' +
              '<div class="twostars-gallery-video-library__toggles">' +
                '<label><input type="checkbox" value="yes" data-video-field="video_autoplay"' + autoplay + '> Autoplay</label>' +
                '<label><input type="checkbox" value="yes" data-video-field="video_muted"' + muted + '> Muted</label>' +
                '<label><input type="checkbox" value="yes" data-video-field="video_loop"' + loop + '> Loop</label>' +
              '</div>' +
            '</div>' +
          '</div>';
      }

      function readItem(item) {
        return {
          video_url: $.trim(item.find('[data-video-field="video_url"]').val() || ''),
          video_caption: $.trim(item.find('[data-video-field="video_caption"]').val() || ''),
          video_category: $.trim(item.find('[data-video-field="video_category"]').val() || ''),
          video_position: Math.max(1, parseInt(item.find('[data-video-field="video_position"]').val(), 10) || 1),
          video_autoplay: item.find('[data-video-field="video_autoplay"]').is(':checked') ? 'yes' : '',
          video_muted: item.find('[data-video-field="video_muted"]').is(':checked') ? 'yes' : '',
          video_loop: item.find('[data-video-field="video_loop"]').is(':checked') ? 'yes' : ''
        };
      }

      function updatePreview(item) {
        item.find('[data-twostars-gallery-video-preview]').html(videoPreviewMarkup($.trim(item.find('[data-video-field="video_url"]').val() || '')));
      }

      function syncInput() {
        var items = [];

        list.find('[data-twostars-gallery-video-item]').each(function () {
          var item = readItem($(this));

          if (item.video_url) {
            items.push(item);
          }
        });

        input.val(JSON.stringify(items));
      }

      function initSortable() {
        if (typeof list.sortable !== 'function') {
          return;
        }

        if (!list.data('twostars-sortable')) {
          list.sortable({
            items: '.twostars-gallery-video-library__item',
            handle: '[data-twostars-gallery-video-handle]',
            tolerance: 'pointer',
            placeholder: 'twostars-gallery-video-library__item twostars-gallery-video-library__item--placeholder',
            update: syncInput
          });
          list.data('twostars-sortable', true);
        } else {
          list.sortable('refresh');
        }
      }

      library.on('click', '[data-twostars-gallery-video-add]', function (event) {
        event.preventDefault();
        list.append(itemMarkup({
          video_url: '',
          video_caption: '',
          video_category: '',
          video_position: list.children().length + 1,
          video_autoplay: 'yes',
          video_muted: 'yes',
          video_loop: 'yes'
        }));
        initSortable();
        syncInput();
      });

      library.on('click', '[data-twostars-gallery-video-clear]', function (event) {
        event.preventDefault();
        list.empty();
        syncInput();
      });

      list.on('click', '[data-twostars-gallery-video-remove]', function (event) {
        event.preventDefault();
        $(this).closest('[data-twostars-gallery-video-item]').remove();
        syncInput();

        if (list.data('twostars-sortable')) {
          list.sortable('refresh');
        }
      });

      list.on('input change', '[data-video-field]', function () {
        var item = $(this).closest('[data-twostars-gallery-video-item]');
        updatePreview(item);
        syncInput();
      });

      list.on('click', '[data-twostars-gallery-video-media]', function (event) {
        event.preventDefault();
        activeItem = $(this).closest('[data-twostars-gallery-video-item]');

        if (frame) {
          frame.open();
          return;
        }

        frame = wp.media({
          title: 'Choose uploaded video',
          button: { text: 'Use this video' },
          library: { type: 'video' },
          multiple: false
        });

        frame.on('select', function () {
          if (!activeItem) {
            return;
          }

          var selection = frame.state().get('selection').first();
          var data = selection ? selection.toJSON() : null;

          if (!data || !data.url) {
            return;
          }

          activeItem.find('[data-video-field="video_url"]').val(data.url);
          updatePreview(activeItem);
          syncInput();
        });

        frame.open();
      });

      list.find('[data-twostars-gallery-video-item]').each(function () {
        updatePreview($(this));
      });

      initSortable();
      syncInput();
    });
  }

  $(function () {
    initMediaFields();
    initGalleryVideoLibraries();
  });
})(jQuery);
