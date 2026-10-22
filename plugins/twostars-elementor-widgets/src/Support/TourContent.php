<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Support;

use TwoStars\Platform\Support\TourData;
use WP_Post;
use WP_Query;
use WP_Term;

final class TourContent
{
	/**
	 * @param array<string, mixed> $args
	 * @return WP_Post[]
	 */
	public static function get_tours(array $args = []): array
	{
		$defaults = [
			'post_type'           => 'tour',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
		];

		$query = new WP_Query(array_merge($defaults, $args));

		return is_array($query->posts) ? $query->posts : [];
	}

	/**
	 * @param array<string, mixed> $args
	 * @return WP_Post[]
	 */
	public static function get_destinations(array $args = []): array
	{
		$defaults = [
			'post_type'           => 'destination',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
		];

		$query = new WP_Query(array_merge($defaults, $args));

		return is_array($query->posts) ? $query->posts : [];
	}

	/**
	 * @param array<string, mixed> $args
	 * @return WP_Post[]
	 */
	public static function get_team_members(array $args = []): array
	{
		$defaults = [
			'post_type'           => 'team_member',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
		];

		$query = new WP_Query(array_merge($defaults, $args));

		return is_array($query->posts) ? $query->posts : [];
	}

	/**
	 * @param array<string, mixed> $args
	 * @return WP_Post[]
	 */
	public static function get_faqs(array $args = []): array
	{
		$defaults = [
			'post_type'           => 'faq',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => 'menu_order title',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
		];

		$query = new WP_Query(array_merge($defaults, $args));

		return is_array($query->posts) ? $query->posts : [];
	}

	/**
	 * @param array<string, mixed> $args
	 * @return WP_Post[]
	 */
	public static function get_testimonials(array $args = []): array
	{
		$defaults = [
			'post_type'           => 'testimonial',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => 'menu_order date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		];

		$query = new WP_Query(array_merge($defaults, $args));

		return is_array($query->posts) ? $query->posts : [];
	}

	/**
	 * @param array<string, mixed> $args
	 * @return WP_Post[]
	 */
	public static function get_reviews(array $args = []): array
	{
		$defaults = [
			'post_type'           => 'testimonial',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		];

		$query = new WP_Query(array_merge($defaults, $args));

		return is_array($query->posts) ? $query->posts : [];
	}

	/**
	 * @return array<int, WP_Term>
	 */
	public static function get_terms(string $taxonomy): array
	{
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		return is_array($terms) ? array_values(array_filter($terms, static fn($term): bool => $term instanceof WP_Term)) : [];
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_tour_options(): array
	{
		if (class_exists(TourData::class)) {
			return ['' => __('Select a tour', 'twostars-elementor-widgets')] + TourData::get_tour_options();
		}

		$options = ['' => __('Select a tour', 'twostars-elementor-widgets')];

		foreach (self::get_tours(['posts_per_page' => 100]) as $post) {
			$options[(string) $post->ID] = $post->post_title;
		}

		return $options;
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_destination_options(): array
	{
		$options = ['' => __('Select a destination', 'twostars-elementor-widgets')];

		foreach (self::get_destinations(['posts_per_page' => 100]) as $post) {
			$options[(string) $post->ID] = $post->post_title;
		}

		return $options;
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_term_map(int $post_id, string $taxonomy): array
	{
		$terms = get_the_terms($post_id, $taxonomy);

		if (! is_array($terms)) {
			return [];
		}

		$map = [];

		foreach ($terms as $term) {
			if (! $term instanceof WP_Term) {
				continue;
			}

			$map[$term->slug] = $term->name;
		}

		return $map;
	}

	/**
	 * @return WP_Post[]
	 */
	public static function get_related_tours(int $tour_id, int $limit = 3): array
	{
		$tax_query = ['relation' => 'OR'];

		foreach (['tour_type', 'tour_region'] as $taxonomy) {
			$terms = wp_get_post_terms($tour_id, $taxonomy, ['fields' => 'ids']);

			if ($terms === [] || is_wp_error($terms)) {
				continue;
			}

			$tax_query[] = [
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $terms,
			];
		}

		if (count($tax_query) === 1) {
			$linked_destination_ids = self::get_linked_destination_ids($tour_id);

			if ($linked_destination_ids === []) {
				return [];
			}

			return self::get_tours(
				[
					'post__not_in'        => [$tour_id],
					'posts_per_page'      => $limit,
					'ignore_sticky_posts' => true,
					'meta_query'          => array_merge(
						['relation' => 'OR'],
						array_map(
							static fn(int $destination_id): array => [
								'key'     => '_twostars_tour_linked_destinations',
								'value'   => '"' . $destination_id . '"',
								'compare' => 'LIKE',
							],
							$linked_destination_ids
						)
					),
				]
			);
		}

		return self::get_tours(
			[
				'post__not_in'        => [$tour_id],
				'posts_per_page'      => $limit,
				'tax_query'           => $tax_query,
				'orderby'             => 'rand',
				'ignore_sticky_posts' => true,
			]
		);
	}

	/**
	 * @return WP_Post[]
	 */
	public static function get_destination_related_tours(int $destination_id, int $limit = 3): array
	{
		return self::get_tours(
			[
				'posts_per_page'      => $limit,
				'ignore_sticky_posts' => true,
				'meta_query'          => [
					[
						'key'     => '_twostars_tour_linked_destinations',
						'value'   => '"' . $destination_id . '"',
						'compare' => 'LIKE',
					],
				],
			]
		);
	}

	/**
	 * @return int[]
	 */
	public static function get_linked_destination_ids(int $tour_id): array
	{
		$meta = get_post_meta($tour_id, '_twostars_tour_linked_destinations', true);

		if (! is_array($meta)) {
			return [];
		}

		return array_values(array_filter(array_map('absint', $meta), static fn(int $id): bool => $id > 0));
	}
}
