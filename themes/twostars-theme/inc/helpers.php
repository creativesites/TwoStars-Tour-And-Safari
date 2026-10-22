<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

/**
 * @return array<string, mixed>
 */
function twostars_theme_get_platform_settings(): array
{
	$settings = get_option('twostars_platform_settings', []);

	return is_array($settings) ? $settings : [];
}

function twostars_theme_get_whatsapp_url(): string
{
	$settings = twostars_theme_get_platform_settings();
	$number   = preg_replace('/[^0-9]/', '', (string) ($settings['whatsapp_number'] ?? ''));

	if ($number === '') {
		return home_url('/contact/');
	}

	return 'https://wa.me/' . $number;
}

function twostars_theme_get_whatsapp_number(): string
{
	$settings = twostars_theme_get_platform_settings();

	return trim((string) ($settings['whatsapp_number'] ?? ''));
}

function twostars_theme_is_external_url(string $url): bool
{
	$url = trim($url);

	if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
		return false;
	}

	$url_host = (string) wp_parse_url($url, PHP_URL_HOST);
	$home_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);

	if ($url_host === '' || $home_host === '') {
		return false;
	}

	return strcasecmp($url_host, $home_host) !== 0;
}

function twostars_theme_get_calendly_url(): string
{
	if (! class_exists(\TwoStars\Platform\Integrations\CalendlyIntegration::class)) {
		return home_url('/booking/');
	}

	$url = \TwoStars\Platform\Integrations\CalendlyIntegration::get_default_event_url();

	return $url !== '' ? $url : home_url('/booking/');
}

function twostars_theme_get_booking_meta_description(): string
{
	return __('Book your Two Stars tour directly on the site with live local availability, guest details, pickup preferences, and direct team follow-up from Walvis Bay or Swakopmund.', 'twostars-theme');
}

function twostars_theme_filter_booking_meta_description(string $value = ''): string
{
	return is_page('booking') ? twostars_theme_get_booking_meta_description() : $value;
}

add_filter('wpseo_metadesc', 'twostars_theme_filter_booking_meta_description');
add_filter('wpseo_opengraph_desc', 'twostars_theme_filter_booking_meta_description');
add_filter('wpseo_twitter_description', 'twostars_theme_filter_booking_meta_description');

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function twostars_theme_filter_booking_schema_webpage(array $data): array
{
	if (! is_page('booking')) {
		return $data;
	}

	$data['description'] = twostars_theme_get_booking_meta_description();

	return $data;
}

add_filter('wpseo_schema_webpage', 'twostars_theme_filter_booking_schema_webpage');

/**
 * @return array<int, array<string, mixed>>
 */
function twostars_theme_get_booking_tours(): array
{
	if (! class_exists(\TwoStars\Platform\Support\TourData::class)) {
		return [];
	}

	$tours = get_posts(
		[
			'post_type'      => 'tour',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'orderby'        => 'date',
			'order'          => 'ASC',
		]
	);

	$order_map = [
		'Sandwich Harbour 4x4 Adventure' => 1,
		'Flamingo and Pink Lake Tour' => 2,
		'Swakopmund City Tour + Dune 7' => 3,
		'Spitzkoppe Day Tour' => 4,
		'Private Tour' => 5,
	];

	usort(
		$tours,
		static function (\WP_Post $left, \WP_Post $right) use ($order_map): int {
			$left_order = $order_map[$left->post_title] ?? 100;
			$right_order = $order_map[$right->post_title] ?? 100;

			if ($left_order === $right_order) {
				return strcmp($left->post_title, $right->post_title);
			}

			return $left_order <=> $right_order;
		}
	);

	$data = [];
	$highlight_map = [
		'Sandwich Harbour 4x4 Adventure' => [
			__('Dune driving', 'twostars-theme'),
			__('Lagoon flamingos', 'twostars-theme'),
			__('Pink Salt Lake', 'twostars-theme'),
		],
		'Flamingo and Pink Lake Tour' => [
			__('Walvis Bay Lagoon', 'twostars-theme'),
			__('Birdlife and photos', 'twostars-theme'),
			__('Relaxed coastal pacing', 'twostars-theme'),
		],
		'Swakopmund City Tour + Dune 7' => [
			__('Town landmarks', 'twostars-theme'),
			__('Coastal history', 'twostars-theme'),
			__('Dune 7 stop', 'twostars-theme'),
		],
		'Spitzkoppe Day Tour' => [
			__('Granite formations', 'twostars-theme'),
			__('Full-day escape', 'twostars-theme'),
			__('Scenic desert views', 'twostars-theme'),
		],
		'Private Tour' => [
			__('Private vehicle', 'twostars-theme'),
			__('Flexible timing', 'twostars-theme'),
			__('Personal route pace', 'twostars-theme'),
		],
	];

	foreach ($tours as $tour) {
		$meta = \TwoStars\Platform\Support\TourData::get_tour_meta($tour->ID);
		$image = get_the_post_thumbnail_url($tour, 'large');
		$summary_items = \TwoStars\Platform\Support\TourData::get_summary_items($tour->ID);

		$data[] = [
			'id'          => $tour->ID,
			'title'       => $tour->post_title,
			'excerpt'     => twostars_theme_get_post_excerpt($tour, 24),
			'image'       => $image !== false ? $image : '',
			'url'         => get_permalink($tour),
			'duration'    => (string) ($meta['duration'] ?? ''),
			'pickup'      => (string) ($meta['pickup_location'] ?? ''),
			'group_size'  => (string) ($meta['group_size'] ?? ''),
			'booking_url' => \TwoStars\Platform\Support\TourData::get_booking_url($tour->ID),
			'summary'     => array_values(array_map(static fn(array $item): string => (string) ($item['value'] ?? ''), array_slice($summary_items, 0, 3))),
			'highlights'  => $highlight_map[$tour->post_title] ?? [],
		];
	}

	return $data;
}

function twostars_theme_get_booking_prefill_tour_id(): int
{
	$raw = isset($_GET['tour']) ? wp_unslash($_GET['tour']) : '';

	if ($raw === '') {
		return 0;
	}

	if (is_numeric($raw)) {
		$tour_id = absint($raw);
		return get_post_type($tour_id) === 'tour' ? $tour_id : 0;
	}

	$post = get_page_by_path(sanitize_title((string) $raw), OBJECT, 'tour');

	return $post instanceof WP_Post ? (int) $post->ID : 0;
}

/**
 * @return array<int, string>
 */
function twostars_theme_get_country_options(): array
{
	return [
		__('Argentina', 'twostars-theme'),
		__('Australia', 'twostars-theme'),
		__('Austria', 'twostars-theme'),
		__('Belgium', 'twostars-theme'),
		__('Botswana', 'twostars-theme'),
		__('Brazil', 'twostars-theme'),
		__('Canada', 'twostars-theme'),
		__('Chile', 'twostars-theme'),
		__('China', 'twostars-theme'),
		__('Denmark', 'twostars-theme'),
		__('Finland', 'twostars-theme'),
		__('France', 'twostars-theme'),
		__('Germany', 'twostars-theme'),
		__('India', 'twostars-theme'),
		__('Ireland', 'twostars-theme'),
		__('Israel', 'twostars-theme'),
		__('Italy', 'twostars-theme'),
		__('Japan', 'twostars-theme'),
		__('Kenya', 'twostars-theme'),
		__('Lesotho', 'twostars-theme'),
		__('Luxembourg', 'twostars-theme'),
		__('Malawi', 'twostars-theme'),
		__('Mozambique', 'twostars-theme'),
		__('Namibia', 'twostars-theme'),
		__('Netherlands', 'twostars-theme'),
		__('New Zealand', 'twostars-theme'),
		__('Nigeria', 'twostars-theme'),
		__('Norway', 'twostars-theme'),
		__('Poland', 'twostars-theme'),
		__('Portugal', 'twostars-theme'),
		__('South Africa', 'twostars-theme'),
		__('Spain', 'twostars-theme'),
		__('Sweden', 'twostars-theme'),
		__('Switzerland', 'twostars-theme'),
		__('Tanzania', 'twostars-theme'),
		__('United Arab Emirates', 'twostars-theme'),
		__('United Kingdom', 'twostars-theme'),
		__('United States', 'twostars-theme'),
		__('Zambia', 'twostars-theme'),
		__('Zimbabwe', 'twostars-theme'),
	];
}

/**
 * @return WP_Post[]
 */
function twostars_theme_get_posts_by_type(string $post_type, int $posts_per_page = 3): array
{
	$posts = get_posts(
		[
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		]
	);

	return is_array($posts) ? $posts : [];
}

/**
 * @return array<string, string>
 */
function twostars_theme_get_archive_copy(?string $post_type = null): array
{
	$post_type = $post_type ?: 'post';

	$map = [
		'post' => [
			'eyebrow'     => __('Journal', 'twostars-theme'),
			'title'       => __('Two Stars Journal', 'twostars-theme'),
			'description' => __('Stories, planning guidance, and local perspective for travelers exploring Walvis Bay, Swakopmund, Sandwich Harbour, and beyond.', 'twostars-theme'),
		],
		'tour' => [
			'eyebrow'     => __('Signature Experiences', 'twostars-theme'),
			'title'       => __('Coastal & Desert Tours', 'twostars-theme'),
			'description' => __('Explore the current Two Stars collection, from Sandwich Harbour dune driving and lagoon stops to Swakopmund, Dune 7, Spitzkoppe, and private tours.', 'twostars-theme'),
		],
		'destination' => [
			'eyebrow'     => __('Places Along The Way', 'twostars-theme'),
			'title'       => __('Destinations & Stops', 'twostars-theme'),
			'description' => __('A closer look at the locations that shape the Two Stars experience, from lagoon birdlife and pink salt pans to coastal town landmarks and granite peaks.', 'twostars-theme'),
		],
		'testimonial' => [
			'eyebrow'     => __('Guest Stories', 'twostars-theme'),
			'title'       => __('Testimonials', 'twostars-theme'),
			'description' => __('First-hand reflections on the hospitality, pacing, and memorable moments that shape a day out with Two Stars.', 'twostars-theme'),
		],
		'faq' => [
			'eyebrow'     => __('Planning Answers', 'twostars-theme'),
			'title'       => __('Tour FAQ', 'twostars-theme'),
			'description' => __('Clear answers to common questions about timing, pickup, private options, booking, and choosing the right Two Stars experience.', 'twostars-theme'),
		],
	];

	return $map[$post_type] ?? [
		'eyebrow'     => __('Archive', 'twostars-theme'),
		'title'       => get_the_archive_title(),
		'description' => wp_strip_all_tags(get_the_archive_description()),
	];
}

function twostars_theme_get_archive_title_text(?string $post_type = null): string
{
	if ($post_type === null) {
		$post_type = is_home() ? 'post' : get_post_type();
	}

	$copy = twostars_theme_get_archive_copy($post_type ?: 'post');
	$title = $copy['title'] ?? '';

	return $title !== '' ? $title : get_the_archive_title();
}

function twostars_theme_get_archive_description_text(?string $post_type = null): string
{
	if ($post_type === null) {
		$post_type = is_home() ? 'post' : get_post_type();
	}

	$copy = twostars_theme_get_archive_copy($post_type ?: 'post');
	$description = $copy['description'] ?? '';

	if ($description !== '') {
		return $description;
	}

	return wp_strip_all_tags(get_the_archive_description());
}

function twostars_theme_get_archive_eyebrow_text(?string $post_type = null): string
{
	if ($post_type === null) {
		$post_type = is_home() ? 'post' : get_post_type();
	}

	$copy = twostars_theme_get_archive_copy($post_type ?: 'post');

	return (string) ($copy['eyebrow'] ?? __('Archive', 'twostars-theme'));
}

function twostars_theme_get_post_excerpt(WP_Post $post, int $word_limit = 28): string
{
	if ($post->post_excerpt !== '') {
		return wp_strip_all_tags($post->post_excerpt);
	}

	return wp_trim_words(wp_strip_all_tags((string) $post->post_content), $word_limit, '...');
}

/**
 * @return array<int, string>
 */
function twostars_theme_get_post_card_meta(WP_Post $post): array
{
	$post_type = $post->post_type;
	$items = [];

	if ($post_type === 'tour' && class_exists(\TwoStars\Platform\Support\TourData::class)) {
		$summary = \TwoStars\Platform\Support\TourData::get_summary_items($post->ID);

		foreach (array_slice($summary, 0, 3) as $item) {
			$items[] = (string) ($item['value'] ?? '');
		}
	}

	if ($post_type === 'post') {
		$items[] = get_the_date('', $post);
		$categories = get_the_category($post->ID);

		if (is_array($categories) && isset($categories[0])) {
			$items[] = (string) $categories[0]->name;
		}
	}

	if ($post_type === 'testimonial' && $post->post_content !== '') {
		$items[] = wp_strip_all_tags($post->post_content);
	}

	if ($post_type === 'destination') {
		$items[] = __('Destination Stop', 'twostars-theme');
	}

	if ($post_type === 'faq') {
		$items[] = __('Quick Planning Answer', 'twostars-theme');
	}

	return array_values(array_filter($items, static fn(string $item): bool => trim($item) !== ''));
}

function twostars_theme_get_post_cta_label(WP_Post $post): string
{
	return match ($post->post_type) {
		'tour'        => __('Explore Tour', 'twostars-theme'),
		'destination' => __('View Destination', 'twostars-theme'),
		'testimonial' => __('Read Story', 'twostars-theme'),
		'faq'         => __('Read Answer', 'twostars-theme'),
		default       => __('Read Article', 'twostars-theme'),
	};
}

/**
 * @return array<string, mixed>
 */
function twostars_theme_get_destination_meta(int $destination_id): array
{
	return [
		'region'         => (string) get_post_meta($destination_id, '_twostars_destination_region', true),
		'best_for'       => (string) get_post_meta($destination_id, '_twostars_destination_best_for', true),
		'visit_duration' => (string) get_post_meta($destination_id, '_twostars_destination_visit_duration', true),
		'highlights'     => (string) get_post_meta($destination_id, '_twostars_destination_highlights', true),
		'gallery_images' => array_values(array_filter(array_map('absint', (array) get_post_meta($destination_id, '_twostars_destination_gallery_images', true)))),
	];
}

/**
 * @return array<int, int>
 */
function twostars_theme_get_post_gallery_ids(int $post_id, string $meta_key): array
{
	$featured_image_id = get_post_thumbnail_id($post_id);
	$gallery_ids = array_values(array_filter(array_map('absint', (array) get_post_meta($post_id, $meta_key, true))));

	if ($featured_image_id > 0) {
		array_unshift($gallery_ids, $featured_image_id);
	}

	return array_values(array_unique(array_filter($gallery_ids)));
}

/**
 * @return array<int, int>
 */
function twostars_theme_get_tour_gallery_ids(int $tour_id): array
{
	return twostars_theme_get_post_gallery_ids($tour_id, '_twostars_tour_gallery_images');
}

/**
 * @return array<int, int>
 */
function twostars_theme_get_destination_gallery_ids(int $destination_id): array
{
	return twostars_theme_get_post_gallery_ids($destination_id, '_twostars_destination_gallery_images');
}

/**
 * @return array<int, string>
 */
function twostars_theme_split_lines(string $value): array
{
	return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value) ?: [])));
}

/**
 * @return WP_Post[]
 */
function twostars_theme_get_destination_related_tours(int $destination_id, int $limit = 3): array
{
	$posts = get_posts(
		[
			'post_type'      => 'tour',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'     => '_twostars_tour_linked_destinations',
					'value'   => '"' . $destination_id . '"',
					'compare' => 'LIKE',
				],
			],
		]
	);

	return is_array($posts) ? $posts : [];
}

function twostars_theme_get_blog_page(): ?WP_Post
{
	$page_id = (int) get_option('page_for_posts');

	if ($page_id <= 0) {
		return null;
	}

	$page = get_post($page_id);

	return $page instanceof WP_Post ? $page : null;
}

function twostars_theme_get_current_menu_id(string $location = 'primary'): int
{
	$locations = get_nav_menu_locations();

	if (isset($locations[$location]) && (int) $locations[$location] > 0) {
		return (int) $locations[$location];
	}

	$menus = wp_get_nav_menus();

	if (is_array($menus) && isset($menus[0]) && $menus[0] instanceof WP_Term) {
		return (int) $menus[0]->term_id;
	}

	return 0;
}

/**
 * @return array<string, mixed>
 */
function twostars_theme_get_menu_args(string $menu_class, string $location = 'primary'): array
{
	$menu_id = twostars_theme_get_current_menu_id($location);

	$args = [
		'container'   => false,
		'menu_class'  => $menu_class,
		'fallback_cb' => false,
	];

	if ($menu_id > 0) {
		$args['menu'] = $menu_id;
		return $args;
	}

	$args['theme_location'] = $location;

	return $args;
}

/**
 * @return WP_Post[]
 */
function twostars_theme_get_related_blog_posts(int $post_id, int $limit = 3): array
{
	$categories = wp_get_post_categories($post_id);

	$args = [
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'post__not_in'        => [$post_id],
		'posts_per_page'      => $limit,
		'ignore_sticky_posts' => true,
	];

	if (is_array($categories) && $categories !== []) {
		$args['category__in'] = $categories;
	}

	$posts = get_posts($args);

	return is_array($posts) ? $posts : [];
}
