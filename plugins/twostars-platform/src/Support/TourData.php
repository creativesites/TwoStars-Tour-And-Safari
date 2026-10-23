<?php
declare(strict_types=1);

namespace TwoStars\Platform\Support;

final class TourData
{
	private const SETTINGS_OPTION = 'twostars_platform_settings';

	/**
	 * @return array<string, string>
	 */
	public static function get_tour_options(): array
	{
		$posts = get_posts(
			[
				'post_type'      => 'tour',
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => 100,
			]
		);

		$options = [];

		foreach ($posts as $post) {
			$options[(string) $post->ID] = $post->post_title;
		}

		return $options;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_tour_meta(int $tour_id): array
	{
		return [
			'duration'            => (string) get_post_meta($tour_id, '_twostars_tour_duration', true),
			'price_from'          => get_post_meta($tour_id, '_twostars_tour_price_from', true),
			'group_size'          => (string) get_post_meta($tour_id, '_twostars_tour_group_size', true),
			'pickup_location'     => (string) get_post_meta($tour_id, '_twostars_tour_pickup_location', true),
			'linked_destinations' => array_values(array_filter(array_map('absint', (array) get_post_meta($tour_id, '_twostars_tour_linked_destinations', true)))),
			'availability'        => (string) get_post_meta($tour_id, '_twostars_tour_availability_status', true),
			'difficulty'          => (string) get_post_meta($tour_id, '_twostars_tour_difficulty', true),
			'best_season'         => (string) get_post_meta($tour_id, '_twostars_tour_best_season', true),
			'includes'            => (string) get_post_meta($tour_id, '_twostars_tour_includes', true),
			'excludes'            => (string) get_post_meta($tour_id, '_twostars_tour_excludes', true),
			'seo_focus_topic'     => (string) get_post_meta($tour_id, '_twostars_tour_seo_focus_topic', true),
			'calendly_event_url'  => esc_url_raw((string) get_post_meta($tour_id, '_twostars_tour_calendly_event_url', true)),
			'booking_cta_label'   => (string) get_post_meta($tour_id, '_twostars_tour_booking_cta_label', true),
			'booking_notes'       => (string) get_post_meta($tour_id, '_twostars_tour_booking_notes', true),
			'hook'                => (string) get_post_meta($tour_id, '_twostars_tour_hook', true),
			'price_note'          => (string) get_post_meta($tour_id, '_twostars_tour_price_note', true),
			'cancellation'        => (string) get_post_meta($tour_id, '_twostars_tour_cancellation', true),
			'departure_times'     => (string) get_post_meta($tour_id, '_twostars_tour_departure_times', true),
			'itinerary'           => (string) get_post_meta($tour_id, '_twostars_tour_itinerary', true),
			'what_to_bring'       => (string) get_post_meta($tour_id, '_twostars_tour_what_to_bring', true),
			'notes'               => (string) get_post_meta($tour_id, '_twostars_tour_notes', true),
			'faqs'                => (string) get_post_meta($tour_id, '_twostars_tour_faqs', true),
			'meeting_point'       => (string) get_post_meta($tour_id, '_twostars_tour_meeting_point', true),
			'map_embed_url'       => esc_url_raw((string) get_post_meta($tour_id, '_twostars_tour_map_embed_url', true)),
			'bokun_url'           => esc_url_raw((string) get_post_meta($tour_id, '_twostars_tour_bokun_url', true)),
		];
	}

	/**
	 * Split a textarea meta value into trimmed non-empty lines.
	 *
	 * @return array<int, string>
	 */
	public static function lines(string $value): array
	{
		return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value) ?: [])));
	}

	/**
	 * Parse "A | B | C" lines into arrays of up to $parts trimmed cells.
	 *
	 * @return array<int, array<int, string>>
	 */
	public static function rows(string $value, int $parts): array
	{
		return array_map(
			static fn(string $line): array => array_pad(array_map('trim', explode('|', $line, $parts)), $parts, ''),
			self::lines($value)
		);
	}

	public static function get_price_label(int $tour_id): string
	{
		$price = (float) get_post_meta($tour_id, '_twostars_tour_price_from', true);

		if ($price > 0) {
			/* translators: %s: formatted NAD price */
			return sprintf(__('From %s per adult', 'twostars-platform'), self::format_nad($price));
		}

		return __('Custom quote', 'twostars-platform');
	}

	public static function format_nad(float $amount): string
	{
		return 'N$' . number_format($amount, 0, '.', ',');
	}

	public static function get_booking_url(int $tour_id): string
	{
		$url = home_url('/booking/');

		if ($tour_id > 0) {
			$url = add_query_arg('tour', $tour_id, $url);
		}

		return $url . '#booking-app';
	}

	public static function get_booking_label(int $tour_id): string
	{
		$label = (string) get_post_meta($tour_id, '_twostars_tour_booking_cta_label', true);

		return $label !== '' ? $label : __('Book This Tour', 'twostars-platform');
	}

	public static function is_calendly_booking(int $tour_id): bool
	{
		return false;
	}

	public static function get_availability_label(int $tour_id): string
	{
		$meta  = self::get_tour_meta($tour_id);
		$value = (string) ($meta['availability'] ?? '');

		$labels = [
			'available'  => __('Available', 'twostars-platform'),
			'limited'    => __('Limited', 'twostars-platform'),
			'on_request' => __('On Request', 'twostars-platform'),
			'sold_out'   => __('Sold Out', 'twostars-platform'),
		];

		return (string) ($labels[$value] ?? '');
	}

	/**
	 * @return array<int, array{label:string,value:string}>
	 */
	public static function get_summary_items(int $tour_id): array
	{
		$meta  = self::get_tour_meta($tour_id);
		$items = [];

		$price_label = self::get_price_label($tour_id);

		if ($price_label !== '') {
			$items[] = ['label' => __('Price', 'twostars-platform'), 'value' => $price_label];
		}

		if ($meta['duration'] !== '') {
			$items[] = ['label' => __('Duration', 'twostars-platform'), 'value' => (string) $meta['duration']];
		}

		if ($meta['group_size'] !== '') {
			$items[] = ['label' => __('Group Size', 'twostars-platform'), 'value' => (string) $meta['group_size']];
		}

		if ($meta['pickup_location'] !== '') {
			$items[] = ['label' => __('Pickup', 'twostars-platform'), 'value' => (string) $meta['pickup_location']];
		}

		if ($meta['best_season'] !== '') {
			$items[] = ['label' => __('Best Season', 'twostars-platform'), 'value' => (string) $meta['best_season']];
		}

		$availability = self::get_availability_label($tour_id);

		if ($availability !== '') {
			$items[] = ['label' => __('Availability', 'twostars-platform'), 'value' => $availability];
		}

		return $items;
	}
}
