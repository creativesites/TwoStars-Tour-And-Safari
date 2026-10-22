<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class GalleryWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-gallery';
	}

	public function get_title(): string
	{
		return __('Safari Image Gallery', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-gallery-masonry';
	}

	public function get_script_depends(): array
	{
		return ['twostars-elementor-widgets', 'twostars-elementor-widget-gallery'];
	}

	protected function register_controls(): void
	{
		// ── Section Header ──────────────────────────────────────────────
		$this->start_controls_section('header_section', ['label' => __('Section Header', 'twostars-elementor-widgets')]);

		$this->add_control('section_eyebrow', [
			'label'   => __('Eyebrow', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Our Gallery', 'twostars-elementor-widgets'),
		]);

		$this->add_control('section_title', [
			'label'   => __('Title', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Moments from the Field', 'twostars-elementor-widgets'),
		]);

		$this->add_control('section_subtitle', [
			'label'   => __('Subtitle', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => __('Coastal dunes, flamingo lagoons, starlit desert camps — captured across our five signature routes.', 'twostars-elementor-widgets'),
		]);

		$this->add_control('section_background', [
			'label'   => __('Background', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'white'  => __('White', 'twostars-elementor-widgets'),
				'cream'  => __('Desert Cream', 'twostars-elementor-widgets'),
				'navy'   => __('Navy', 'twostars-elementor-widgets'),
			],
			'default' => 'cream',
		]);

		$this->end_controls_section();

		// ── Gallery Images ──────────────────────────────────────────────
		$this->start_controls_section('gallery_section', ['label' => __('Gallery Images', 'twostars-elementor-widgets')]);

		$this->add_control('image_source', [
			'label'   => __('Image Source', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'elementor'      => __('Elementor Gallery', 'twostars-elementor-widgets'),
				'twostars_admin' => __('Two Stars Admin Gallery', 'twostars-elementor-widgets'),
			],
			'default'     => 'elementor',
			'description' => __('Choose whether this gallery uses images managed in Elementor or the shared gallery library inside Two Stars Admin.', 'twostars-elementor-widgets'),
		]);

		$this->add_control('images', [
			'label'   => __('Gallery Images', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::GALLERY,
			'default' => [],
			'condition' => ['image_source' => 'elementor'],
		]);

		$this->add_control('twostars_admin_gallery_state', [
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => $this->get_admin_gallery_library_control_markup(),
			'content_classes' => 'twostars-gallery-widget__library-state',
			'condition'       => ['image_source' => 'twostars_admin'],
		]);

		$this->add_control('layout_style', [
			'label'   => __('Layout Style', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::SELECT,
			'options' => [
				'masonry' => __('Masonry (variable height)', 'twostars-elementor-widgets'),
				'grid'    => __('Uniform Grid', 'twostars-elementor-widgets'),
			],
			'default' => 'masonry',
		]);

		$this->add_control('desktop_columns', [
			'label'   => __('Desktop Columns', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::SELECT,
			'options' => ['3' => '3', '4' => '4', '5' => '5'],
			'default' => '4',
		]);

		$this->add_control('tablet_columns', [
			'label'   => __('Tablet Columns', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::SELECT,
			'options' => ['1' => '1', '2' => '2', '3' => '3'],
			'default' => '2',
		]);

		$this->add_control('mobile_columns', [
			'label'   => __('Mobile Columns', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::SELECT,
			'options' => ['1' => '1', '2' => '2'],
			'default' => '1',
		]);

		$this->add_control('gap_size', [
			'label'      => __('Gap Size', 'twostars-elementor-widgets'),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => ['px'],
			'range'      => ['px' => ['min' => 4, 'max' => 48]],
			'default'    => ['unit' => 'px', 'size' => 14],
		]);

		$this->add_control('enable_lightbox', [
			'label'        => __('Enable Lightbox', 'twostars-elementor-widgets'),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __('Yes', 'twostars-elementor-widgets'),
			'label_off'    => __('No', 'twostars-elementor-widgets'),
			'return_value' => 'yes',
			'default'      => 'yes',
		]);

		$this->add_control('enable_hover_zoom', [
			'label'        => __('Enable Hover Zoom', 'twostars-elementor-widgets'),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __('Yes', 'twostars-elementor-widgets'),
			'label_off'    => __('No', 'twostars-elementor-widgets'),
			'return_value' => 'yes',
			'default'      => 'yes',
		]);

		$this->add_control('enable_filters', [
			'label'        => __('Enable Category Filters', 'twostars-elementor-widgets'),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __('Yes', 'twostars-elementor-widgets'),
			'label_off'    => __('No', 'twostars-elementor-widgets'),
			'return_value' => 'yes',
			'default'      => 'no',
		]);

		$this->add_control('filter_all_label', [
			'label'     => __('Filter "All" Label', 'twostars-elementor-widgets'),
			'type'      => Controls_Manager::TEXT,
			'default'   => __('All', 'twostars-elementor-widgets'),
			'condition' => ['enable_filters' => 'yes'],
		]);

		$this->end_controls_section();

		// ── Filter Categories ───────────────────────────────────────────
		$this->start_controls_section('filters_section', [
			'label'     => __('Filter Categories', 'twostars-elementor-widgets'),
			'condition' => ['enable_filters' => 'yes'],
		]);

		$cats_repeater = new Repeater();

		$cats_repeater->add_control('cat_label', [
			'label'   => __('Label', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		]);

		$cats_repeater->add_control('cat_value', [
			'label'       => __('Slug / Value', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'description' => __('Lowercase, no spaces. e.g. "dunes", "wildlife". Must match Category Slug set on images.', 'twostars-elementor-widgets'),
		]);

		$this->add_control('filter_categories', [
			'label'       => __('Categories', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $cats_repeater->get_controls(),
			'default'     => [],
			'title_field' => '{{{ cat_label }}}',
		]);

		$this->end_controls_section();

		// ── Pagination ──────────────────────────────────────────────────
		$this->start_controls_section('pagination_section', ['label' => __('Pagination', 'twostars-elementor-widgets')]);

		$this->add_control('images_per_page', [
			'label'   => __('Images Per Page', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::NUMBER,
			'default' => 12,
			'min'     => 1,
			'max'     => 100,
		]);

		$this->add_control('load_more_text', [
			'label'   => __('Load More Button Text', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Load More', 'twostars-elementor-widgets'),
		]);

		$this->end_controls_section();

		// ── Image Metadata (captions / categories) ───────────────────────
		$this->start_controls_section('metadata_section', ['label' => __('Image Captions & Categories', 'twostars-elementor-widgets')]);

		$meta_repeater = new Repeater();

		$meta_repeater->add_control('img_index', [
			'label'       => __('Image Position (1-based)', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 1,
			'min'         => 1,
			'description' => __('Matches the position of the image in the gallery above.', 'twostars-elementor-widgets'),
		]);

		$meta_repeater->add_control('img_caption', [
			'label'   => __('Caption', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		]);

		$meta_repeater->add_control('img_category', [
			'label'       => __('Category Slug', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'description' => __('Must match a Category Slug defined in the Filter Categories section.', 'twostars-elementor-widgets'),
		]);

		$this->add_control('image_meta', [
			'label'       => __('Image Metadata', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $meta_repeater->get_controls(),
			'title_field' => 'Image #{{{ img_index }}}',
			'default'     => [],
		]);

		$this->end_controls_section();

		// ── Gallery Videos ──────────────────────────────────────────────
		$this->start_controls_section('video_section', [
			'label'     => __('Gallery Videos', 'twostars-elementor-widgets'),
			'condition' => ['image_source' => 'elementor'],
		]);

		$video_repeater = new Repeater();

		$video_repeater->add_control('video_url', [
			'label'       => __('Video File URL', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'description' => __('Use a direct video file URL such as MP4.', 'twostars-elementor-widgets'),
		]);

		$video_repeater->add_control('video_poster', [
			'label' => __('Video Poster Image', 'twostars-elementor-widgets'),
			'type'  => Controls_Manager::MEDIA,
		]);

		$video_repeater->add_control('video_caption', [
			'label'   => __('Video Caption', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Two Stars — On the road', 'twostars-elementor-widgets'),
		]);

		$video_repeater->add_control('video_category', [
			'label'       => __('Video Category Slug', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::TEXT,
			'default'     => '',
			'description' => __('Assign this video to a filter category.', 'twostars-elementor-widgets'),
		]);

		$video_repeater->add_control('video_position', [
			'label'       => __('Video Position in Grid', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 3,
			'min'         => 1,
			'description' => __('1 inserts before the first image, 2 before the second image, and so on.', 'twostars-elementor-widgets'),
		]);

		$video_repeater->add_control('video_autoplay', [
			'label'        => __('Autoplay Inline', 'twostars-elementor-widgets'),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __('Yes', 'twostars-elementor-widgets'),
			'label_off'    => __('No', 'twostars-elementor-widgets'),
			'return_value' => 'yes',
			'default'      => 'yes',
		]);

		$video_repeater->add_control('video_muted', [
			'label'        => __('Mute Video', 'twostars-elementor-widgets'),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __('Yes', 'twostars-elementor-widgets'),
			'label_off'    => __('No', 'twostars-elementor-widgets'),
			'return_value' => 'yes',
			'default'      => 'yes',
		]);

		$video_repeater->add_control('video_loop', [
			'label'        => __('Loop Video', 'twostars-elementor-widgets'),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __('Yes', 'twostars-elementor-widgets'),
			'label_off'    => __('No', 'twostars-elementor-widgets'),
			'return_value' => 'yes',
			'default'      => 'yes',
		]);

		$this->add_control('videos', [
			'label'       => __('Videos', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $video_repeater->get_controls(),
			'default'     => [],
			'title_field' => '{{{ video_caption || video_url || "Video item" }}}',
		]);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings        = $this->get_settings_for_display();
		$image_source    = ($settings['image_source'] ?? 'elementor') === 'twostars_admin' ? 'twostars_admin' : 'elementor';
		$images          = $image_source === 'twostars_admin'
			? $this->get_admin_gallery_images()
			: (is_array($settings['images'] ?? null) ? $settings['images'] : []);
		$bg_map          = ['white' => '#FFFFFF', 'cream' => '#F5EFE6', 'navy' => '#1B3A6B'];
		$bg              = $bg_map[$settings['section_background'] ?? 'cream'] ?? '#F5EFE6';
		$enable_lightbox = ($settings['enable_lightbox'] ?? 'yes') === 'yes';
		$enable_zoom     = ($settings['enable_hover_zoom'] ?? 'yes') === 'yes';
		$enable_filters  = ($settings['enable_filters'] ?? 'no') === 'yes';
		$layout_style    = in_array($settings['layout_style'] ?? 'masonry', ['masonry', 'grid'], true)
			? ($settings['layout_style'] ?? 'masonry')
			: 'masonry';
		$text_color      = ($settings['section_background'] ?? 'cream') === 'navy' ? '#FBF7F0' : '#101C2E';

		$filter_categories = is_array($settings['filter_categories'] ?? null) ? $settings['filter_categories'] : [];
		$show_filter_bar   = $enable_filters && ! empty($filter_categories);

		// Build metadata map keyed by 1-based image position
		$meta_map = [];
		foreach (is_array($settings['image_meta'] ?? null) ? $settings['image_meta'] : [] as $m) {
			$idx = max(1, (int) ($m['img_index'] ?? 1));
			$meta_map[$idx] = [
				'caption'  => (string) ($m['img_caption'] ?? ''),
				'category' => (string) ($m['img_category'] ?? ''),
			];
		}

		// Build items list (images + videos)
		$items = [];
		foreach ($images as $pos => $image) {
			$items[] = [
				'type'         => 'image',
				'image'        => $image,
				'original_pos' => $pos + 1,
				'sort_index'   => (($pos + 1) * 100) + 50,
			];
		}

		$video_items = $image_source === 'twostars_admin'
			? $this->get_admin_gallery_videos()
			: (is_array($settings['videos'] ?? null) ? $settings['videos'] : []);

		if ($image_source !== 'twostars_admin') {
			// Backward compatibility for existing widgets configured before repeatable videos.
			$legacy_video_enabled = ($settings['video_enabled'] ?? '') === 'yes';
			if ($legacy_video_enabled && ! empty($settings['video_url'])) {
				$video_items[] = [
					'video_url'      => (string) ($settings['video_url'] ?? ''),
					'video_poster'   => is_array($settings['video_poster'] ?? null) ? $settings['video_poster'] : [],
					'video_caption'  => (string) ($settings['video_caption'] ?? ''),
					'video_category' => (string) ($settings['video_category'] ?? ''),
					'video_position' => max(1, (int) ($settings['video_position'] ?? 3)),
					'video_autoplay' => (string) ($settings['video_autoplay'] ?? 'yes'),
					'video_muted'    => (string) ($settings['video_muted'] ?? 'yes'),
					'video_loop'     => (string) ($settings['video_loop'] ?? 'yes'),
				];
			}
		}

		$video_offset = 0;
		foreach ($video_items as $video) {
			$video_url = trim((string) ($video['video_url'] ?? ''));

			if ($video_url === '') {
				continue;
			}

			$video_position = max(1, (int) ($video['video_position'] ?? 1));

			$items[] = [
				'type'       => 'video',
				'url'        => $video_url,
				'poster'     => ! empty($video['video_poster']['url']) ? (string) $video['video_poster']['url'] : '',
				'caption'    => (string) ($video['video_caption'] ?? ''),
				'category'   => (string) ($video['video_category'] ?? ''),
				'autoplay'   => (string) ($video['video_autoplay'] ?? 'yes'),
				'muted'      => (string) ($video['video_muted'] ?? 'yes'),
				'loop'       => (string) ($video['video_loop'] ?? 'yes'),
				'sort_index' => ($video_position * 100) + $video_offset,
			];

			$video_offset++;
		}

		usort(
			$items,
			static function (array $left, array $right): int {
				return ((int) ($left['sort_index'] ?? 0)) <=> ((int) ($right['sort_index'] ?? 0));
			}
		);

		$images_per_page = max(1, (int) ($settings['images_per_page'] ?? 12));
		$total_items     = count($items);

		$inline_style = $this->build_inline_vars([
			'--gallery-cols'        => max(3, ! empty($settings['desktop_columns']) ? (int) $settings['desktop_columns'] : 4),
			'--gallery-cols-tablet' => ! empty($settings['tablet_columns']) ? (int) $settings['tablet_columns'] : 2,
			'--gallery-cols-mobile' => ! empty($settings['mobile_columns']) ? (int) $settings['mobile_columns'] : 1,
			'--gallery-gap'         => ((int) ($settings['gap_size']['size'] ?? 14)) . 'px',
			'--gallery-bg'          => $bg,
			'--gallery-text'        => $text_color,
		]);

		$widget_id  = $this->widget_dom_id();
		$bg_class   = 'twostars-gallery-page--bg-' . esc_attr((string) ($settings['section_background'] ?? 'cream'));
		$layout_cls = 'twostars-gallery-page--layout-' . esc_attr($layout_style);

		// Pre-render all items to HTML (for JSON serialisation + initial PHP render)
		$items_html = [];
		foreach ($items as $index => $item) {
			ob_start();
			$item_pos      = $index + 1;
			$stagger_delay = number_format(($index % $images_per_page) * 0.065, 3);

			if ($item['type'] === 'video') :
				$video_category = esc_attr((string) ($item['category'] ?? ''));
				?>
				<div
					class="twostars-gallery-page__item twostars-gallery-page__item--video"
					data-gallery-item
					data-item-type="video"
					data-video-url="<?php echo esc_attr($item['url'] ?? ''); ?>"
					data-caption="<?php echo esc_attr((string) ($item['caption'] ?? '')); ?>"
					<?php if ($video_category !== '') : ?>data-category="<?php echo $video_category; ?>"<?php endif; ?>
					style="--twostars-delay:<?php echo esc_attr($stagger_delay); ?>s;"
					tabindex="0"
					role="button"
					aria-label="<?php esc_attr_e('Play video', 'twostars-elementor-widgets'); ?>"
				>
					<?php if (($item['autoplay'] ?? '') === 'yes') : ?>
						<video
							class="twostars-gallery-page__img"
							src="<?php echo esc_url($item['url']); ?>"
							<?php if (! empty($item['poster'])) : ?>poster="<?php echo esc_url($item['poster']); ?>"<?php endif; ?>
							<?php echo ($item['muted'] ?? '') === 'yes' ? 'muted playsinline' : 'playsinline'; ?>
							<?php echo ($item['loop'] ?? '') === 'yes' ? 'loop' : ''; ?>
							autoplay
						></video>
					<?php elseif (! empty($item['poster'])) : ?>
						<img class="twostars-gallery-page__img" src="<?php echo esc_url($item['poster']); ?>" alt="<?php echo esc_attr((string) ($item['caption'] ?? '')); ?>" loading="lazy">
					<?php else : ?>
						<div class="twostars-gallery-page__img-placeholder"></div>
					<?php endif; ?>
					<div class="twostars-gallery-page__overlay">
						<span class="twostars-gallery-page__play-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
						</span>
						<?php if (! empty($item['caption'])) : ?>
							<span class="twostars-gallery-page__caption"><?php echo esc_html((string) $item['caption']); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php else :
				$image        = $item['image'];
				$orig_pos     = $item['original_pos'];
				$img_meta     = $meta_map[$orig_pos] ?? ['caption' => '', 'category' => ''];
				$caption      = $img_meta['caption'] ?? '';
				$category     = $img_meta['category'] ?? '';

				$item_class = 'twostars-gallery-page__item';
				if ($enable_zoom) {
					$item_class .= ' twostars-gallery-page__item--zoom';
				}
				if ($enable_lightbox) {
					$item_class .= ' twostars-gallery-page__item--lightbox';
				}
				?>
				<div
					class="<?php echo esc_attr($item_class); ?>"
					data-gallery-item
					data-item-type="image"
					data-full-url="<?php echo esc_url((string) ($image['url'] ?? '')); ?>"
					data-caption="<?php echo esc_attr($caption); ?>"
					<?php if ($category !== '') : ?>data-category="<?php echo esc_attr($category); ?>"<?php endif; ?>
					style="--twostars-delay:<?php echo esc_attr($stagger_delay); ?>s;"
					tabindex="0"
					role="button"
					aria-label="<?php echo esc_attr(sprintf(__('View image %d', 'twostars-elementor-widgets'), $orig_pos)); ?>"
				>
					<?php echo wp_get_attachment_image(
						absint($image['id'] ?? 0),
						'large',
						false,
						['class' => 'twostars-gallery-page__img', 'loading' => 'lazy']
					); ?>
					<div class="twostars-gallery-page__overlay">
						<?php if ($enable_lightbox) : ?>
							<span class="twostars-gallery-page__zoom-icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v6M8 11h6"/></svg>
							</span>
						<?php endif; ?>
						<?php if ($caption !== '') : ?>
							<span class="twostars-gallery-page__caption"><?php echo esc_html($caption); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endif;
			$items_html[] = ob_get_clean();
		}

		$json_items       = wp_json_encode(array_map('trim', $items_html));
		$load_more_text   = esc_html((string) ($settings['load_more_text'] ?? __('Load More', 'twostars-elementor-widgets')));
		$has_more         = $total_items > $images_per_page;
		$initial_shown    = min($images_per_page, $total_items);
		?>
		<section
			class="twostars-gallery-page <?php echo $bg_class; ?> <?php echo $layout_cls; ?>"
			<?php if ($inline_style !== '') : ?>style="<?php echo esc_attr($inline_style); ?>"<?php endif; ?>
			id="<?php echo esc_attr($widget_id); ?>"
			data-twostars-gallery-page
			data-gallery-layout="<?php echo esc_attr($layout_style); ?>"
			<?php if ($enable_lightbox) : ?>data-gallery-lightbox<?php endif; ?>
			data-gallery-items="<?php echo esc_attr($json_items !== false ? $json_items : '[]'); ?>"
			data-gallery-limit="<?php echo esc_attr((string) $images_per_page); ?>"
			data-gallery-total="<?php echo esc_attr((string) $total_items); ?>"
		>
			<!-- Section header -->
			<?php if (! empty($settings['section_eyebrow']) || ! empty($settings['section_title']) || ! empty($settings['section_subtitle'])) : ?>
				<div class="twostars-gallery-page__header" data-twostars-animate="fade-in-up">
					<?php if (! empty($settings['section_eyebrow'])) : ?>
						<p class="twostars-gallery-page__eyebrow"><?php echo esc_html((string) $settings['section_eyebrow']); ?></p>
					<?php endif; ?>
					<?php if (! empty($settings['section_title'])) : ?>
						<h2 class="twostars-gallery-page__title"><?php echo esc_html((string) $settings['section_title']); ?></h2>
					<?php endif; ?>
					<?php if (! empty($settings['section_subtitle'])) : ?>
						<p class="twostars-gallery-page__subtitle"><?php echo esc_html((string) $settings['section_subtitle']); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Category filter bar -->
			<?php if ($show_filter_bar) : ?>
				<div class="twostars-gallery-page__filters" data-gallery-filters role="tablist" aria-label="<?php esc_attr_e('Filter gallery by category', 'twostars-elementor-widgets'); ?>">
					<button
						type="button"
						class="twostars-gallery-page__filter is-active"
						data-gallery-filter="all"
						role="tab"
						aria-selected="true"
					><?php echo esc_html((string) ($settings['filter_all_label'] ?? __('All', 'twostars-elementor-widgets'))); ?></button>
					<?php foreach ($filter_categories as $cat) :
						if (empty($cat['cat_value'])) continue; ?>
						<button
							type="button"
							class="twostars-gallery-page__filter"
							data-gallery-filter="<?php echo esc_attr((string) ($cat['cat_value'] ?? '')); ?>"
							role="tab"
							aria-selected="false"
						><?php echo esc_html((string) ($cat['cat_label'] ?? $cat['cat_value'] ?? '')); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Grid -->
			<div class="twostars-gallery-page__grid" data-gallery-grid>
				<?php echo implode('', array_slice($items_html, 0, $images_per_page)); ?>
			</div>

			<!-- Load more -->
			<?php if ($has_more) : ?>
				<div class="twostars-gallery-page__footer" data-gallery-footer>
					<button type="button" class="twostars-gallery-page__load-more" data-gallery-load-more>
						<span class="twostars-gallery-page__load-more-label"><?php echo $load_more_text; ?></span>
						<svg class="twostars-gallery-page__load-more-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
					</button>
					<p class="twostars-gallery-page__image-count" data-gallery-count>
						<?php echo esc_html(sprintf(
							/* translators: 1: shown count, 2: total count */
							__('%1$d of %2$d items', 'twostars-elementor-widgets'),
							$initial_shown,
							$total_items
						)); ?>
					</p>
					<div class="twostars-gallery-page__sentinel" data-gallery-sentinel aria-hidden="true"></div>
				</div>
			<?php endif; ?>

			<!-- Lightbox modal -->
			<?php if ($enable_lightbox) : ?>
				<div
					class="twostars-gallery-page__lightbox"
					hidden
					data-gallery-lightbox-modal
					aria-modal="true"
					role="dialog"
					aria-label="<?php esc_attr_e('Gallery lightbox', 'twostars-elementor-widgets'); ?>"
				>
					<div class="twostars-gallery-page__lightbox-backdrop" data-gallery-lightbox-close></div>
					<div class="twostars-gallery-page__lightbox-inner">
						<span class="twostars-gallery-page__lightbox-counter" data-gallery-lightbox-counter aria-live="polite"></span>
						<button
							type="button"
							class="twostars-gallery-page__lightbox-close"
							data-gallery-lightbox-close
							aria-label="<?php esc_attr_e('Close lightbox', 'twostars-elementor-widgets'); ?>"
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
						</button>
						<button
							type="button"
							class="twostars-gallery-page__lightbox-prev"
							data-gallery-lightbox-prev
							aria-label="<?php esc_attr_e('Previous', 'twostars-elementor-widgets'); ?>"
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
						</button>
						<button
							type="button"
							class="twostars-gallery-page__lightbox-next"
							data-gallery-lightbox-next
							aria-label="<?php esc_attr_e('Next', 'twostars-elementor-widgets'); ?>"
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
						</button>
						<div class="twostars-gallery-page__lightbox-media" data-gallery-lightbox-media></div>
						<p class="twostars-gallery-page__lightbox-caption" data-gallery-lightbox-caption></p>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * @return array<int, array{id:int, url:string}>
	 */
	private function get_admin_gallery_images(): array
	{
		$image_ids = get_option('twostars_gallery_library_images', []);

		if (is_string($image_ids)) {
			$image_ids = explode(',', $image_ids);
		}

		$normalized_ids = array_values(array_unique(array_filter(array_map('absint', is_array($image_ids) ? $image_ids : []))));
		$images         = [];

		foreach ($normalized_ids as $image_id) {
			$image_url = wp_get_attachment_url($image_id);

			if (! is_string($image_url) || $image_url === '') {
				continue;
			}

			$images[] = [
				'id'  => $image_id,
				'url' => $image_url,
			];
		}

		return $images;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function get_admin_gallery_videos(): array
	{
		$videos = get_option('twostars_gallery_library_videos', []);

		if (is_string($videos)) {
			$decoded = json_decode($videos, true);
			$videos  = is_array($decoded) ? $decoded : [];
		}

		$normalized = [];

		foreach (is_array($videos) ? $videos : [] as $index => $video) {
			if (! is_array($video)) {
				continue;
			}

			$video_url = esc_url_raw((string) ($video['video_url'] ?? ''));

			if ($video_url === '') {
				continue;
			}

			$normalized[] = [
				'video_url'      => $video_url,
				'video_caption'  => sanitize_text_field((string) ($video['video_caption'] ?? '')),
				'video_category' => sanitize_title((string) ($video['video_category'] ?? '')),
				'video_position' => max(1, absint($video['video_position'] ?? ($index + 1))),
				'video_autoplay' => (($video['video_autoplay'] ?? 'yes') === 'yes') ? 'yes' : '',
				'video_muted'    => (($video['video_muted'] ?? 'yes') === 'yes') ? 'yes' : '',
				'video_loop'     => (($video['video_loop'] ?? 'yes') === 'yes') ? 'yes' : '',
				'video_poster'   => [],
			];
		}

		return $normalized;
	}

	private function get_admin_gallery_library_control_markup(): string
	{
		$images       = $this->get_admin_gallery_images();
		$videos       = $this->get_admin_gallery_videos();
		$manage_url   = admin_url('admin.php?page=twostars-manager-gallery-library');
		$image_count  = count($images);
		$video_count  = count($videos);
		$preview_html = '';

		if (! empty($images)) {
			$preview_html .= '<div style="display:grid;gap:12px;">';
			$preview_html .= '<div style="font-weight:600;color:#101c2e;">' . esc_html(
				sprintf(
					/* translators: %d: number of library images */
					_n('%d image currently selected in Two Stars Admin.', '%d images currently selected in Two Stars Admin.', $image_count, 'twostars-elementor-widgets'),
					$image_count
				)
			) . '</div>';
			$preview_html .= '<div style="display:flex;flex-wrap:wrap;gap:8px;">';

			foreach (array_slice($images, 0, 10) as $image) {
				$thumb_url = wp_get_attachment_image_url((int) ($image['id'] ?? 0), 'thumbnail');
				$full_url  = is_string($thumb_url) && $thumb_url !== '' ? $thumb_url : (string) ($image['url'] ?? '');

				if ($full_url === '') {
					continue;
				}

				$preview_html .= sprintf(
					'<span style="display:inline-flex;width:56px;height:56px;overflow:hidden;border-radius:14px;border:1px solid rgba(16,28,46,0.12);box-shadow:0 10px 24px rgba(16,28,46,0.08);"><img src="%s" alt="" style="width:100%%;height:100%%;object-fit:cover;"></span>',
					esc_url($full_url)
				);
			}

			$preview_html .= '</div>';

			if ($image_count > 10) {
				$preview_html .= '<div style="font-size:11px;line-height:1.5;color:#5f6b7a;">' . esc_html(
					sprintf(
						/* translators: %d: additional images not shown */
						__('Plus %d more images in the shared library.', 'twostars-elementor-widgets'),
						$image_count - 10
					)
				) . '</div>';
			}

			$preview_html .= '</div>';
		} else {
			$preview_html .= '<div style="display:grid;gap:8px;">';
			$preview_html .= '<div style="font-weight:600;color:#101c2e;">' . esc_html__('No images selected in Two Stars Admin yet.', 'twostars-elementor-widgets') . '</div>';
			$preview_html .= '<div style="font-size:11px;line-height:1.5;color:#5f6b7a;">' . esc_html__('Add gallery images in the shared library to populate this widget.', 'twostars-elementor-widgets') . '</div>';
			$preview_html .= '</div>';
		}

		$preview_html .= '<div style="display:grid;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid rgba(16,28,46,0.08);">';
		$preview_html .= '<div style="font-weight:600;color:#101c2e;">' . esc_html(
			sprintf(
				/* translators: %d: number of shared videos */
				_n('%d shared video currently selected.', '%d shared videos currently selected.', $video_count, 'twostars-elementor-widgets'),
				$video_count
			)
		) . '</div>';

		if ($video_count > 0) {
			$preview_html .= '<div style="display:grid;gap:6px;">';

			foreach (array_slice($videos, 0, 4) as $video) {
				$label = trim((string) ($video['video_caption'] ?? ''));
				if ($label === '') {
					$label = (string) ($video['video_url'] ?? '');
				}

				$preview_html .= '<div style="font-size:11px;line-height:1.5;color:#5f6b7a;">' . esc_html($label) . '</div>';
			}

			if ($video_count > 4) {
				$preview_html .= '<div style="font-size:11px;line-height:1.5;color:#5f6b7a;">' . esc_html(
					sprintf(
						/* translators: %d: additional videos not shown */
						__('Plus %d more shared videos.', 'twostars-elementor-widgets'),
						$video_count - 4
					)
				) . '</div>';
			}

			$preview_html .= '</div>';
		} else {
			$preview_html .= '<div style="font-size:11px;line-height:1.5;color:#5f6b7a;">' . esc_html__('Add WordPress-uploaded videos or direct video URLs in Two Stars Admin to include them in this shared gallery.', 'twostars-elementor-widgets') . '</div>';
		}

		$preview_html .= '</div>';

		$preview_html .= sprintf(
			'<div style="margin-top:12px;"><a href="%s" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:999px;background:#101c2e;color:#fff;text-decoration:none;font-weight:600;">%s</a></div>',
			esc_url($manage_url),
			esc_html__('Manage Gallery Library', 'twostars-elementor-widgets')
		);

		return $preview_html;
	}
}
