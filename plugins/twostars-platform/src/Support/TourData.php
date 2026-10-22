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
		];
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
