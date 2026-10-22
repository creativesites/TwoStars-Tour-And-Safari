<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Support\TourData;
use WP_Post;
use WP_Term;

final class ContextStore implements Service
{
	private const CACHE_KEY = 'twostars_ai_context_manifest_v2';

	public function register(): void
	{
		add_action('save_post_tour', [$this, 'invalidate_manifest']);
		add_action('save_post_destination', [$this, 'invalidate_manifest']);
		add_action('save_post_faq', [$this, 'invalidate_manifest']);
		add_action('save_post_post', [$this, 'invalidate_manifest']);
		add_action('save_post_testimonial', [$this, 'invalidate_manifest']);
		add_action('save_post_page', [$this, 'invalidate_manifest']);
		add_action('deleted_post', [$this, 'invalidate_manifest']);
	}

	public function invalidate_manifest(): void
	{
		delete_transient(self::CACHE_KEY);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_manifest(): array
	{
		$cached = get_transient(self::CACHE_KEY);

		if (is_array($cached)) {
			return $cached;
		}

		$manifest = [
			'generated_at' => current_time('mysql'),
			'tours'        => self::build_tour_records(),
			'destinations' => self::build_destination_records(),
			'faqs'         => self::build_faq_records(),
			'posts'        => self::build_post_records(),
			'testimonials' => self::build_testimonial_records(),
		];

		set_transient(self::CACHE_KEY, $manifest, HOUR_IN_SECONDS);

		return $manifest;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_entity_record(string $entity_type, int $post_id): ?array
	{
		if ($post_id <= 0) {
			return null;
		}

		$manifest = self::get_manifest();
		$records  = $manifest[$entity_type . 's'] ?? [];

		if (! is_array($records)) {
			return null;
		}

		foreach ($records as $record) {
			if (! is_array($record)) {
				continue;
			}

			if ((int) ($record['post_id'] ?? 0) === $post_id) {
				return $record;
			}
		}

		return null;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_page_context(string $page_type, int $post_id): array
	{
		$record = self::get_entity_record($page_type, $post_id);

		if (! is_array($record)) {
			return [];
		}

		if ($page_type === 'tour') {
			return [
				'type'         => 'tour',
				'post_id'      => (int) ($record['post_id'] ?? 0),
				'title'        => (string) ($record['title'] ?? ''),
				'summary'      => (string) ($record['summary'] ?? ''),
				'url'          => (string) ($record['url'] ?? ''),
				'duration'     => (string) ($record['duration'] ?? ''),
				'pickup'       => (string) ($record['pickup_location'] ?? ''),
				'booking_url'  => (string) ($record['booking_url'] ?? ''),
				'availability' => (string) ($record['availability_label'] ?? ''),
				'linked_destination_titles' => array_slice((array) ($record['linked_destination_titles'] ?? []), 0, 4),
			];
		}

		if ($page_type === 'destination') {
			return [
				'type'        => 'destination',
				'post_id'     => (int) ($record['post_id'] ?? 0),
				'title'       => (string) ($record['title'] ?? ''),
				'summary'     => (string) ($record['summary'] ?? ''),
				'url'         => (string) ($record['url'] ?? ''),
				'relatedTourTitles' => array_slice((array) ($record['related_tour_titles'] ?? []), 0, 3),
			];
		}

		if ($page_type === 'faq') {
			return [
				'type'    => 'faq',
				'post_id' => (int) ($record['post_id'] ?? 0),
				'title'   => (string) ($record['title'] ?? ''),
				'answer'  => (string) ($record['answer'] ?? ''),
			];
		}

		if ($page_type === 'post') {
			return [
				'type'       => 'post',
				'post_id'    => (int) ($record['post_id'] ?? 0),
				'title'      => (string) ($record['title'] ?? ''),
				'summary'    => (string) ($record['summary'] ?? ''),
				'focus_topic' => (string) ($record['focus_topic'] ?? ''),
			];
		}

		if ($page_type === 'testimonial') {
			return [
				'type'    => 'testimonial',
				'post_id' => (int) ($record['post_id'] ?? 0),
				'title'   => (string) ($record['title'] ?? ''),
				'quote'   => (string) ($record['summary'] ?? ''),
			];
		}

		return [];
	}

	/**
	 * @param array<string, mixed> $chat_context
	 * @return array<string, mixed>
	 */
	public static function get_grounding_pack(string $message, array $chat_context = []): array
	{
		$manifest             = self::get_manifest();
		$visited_tours        = self::normalize_id_list($chat_context['visited_tours'] ?? []);
		$visited_destinations = self::normalize_id_list($chat_context['visited_destinations'] ?? []);
		$page_type            = (string) ($chat_context['page_type'] ?? '');
		$post_id              = absint($chat_context['post_id'] ?? 0);
		$tokens               = self::tokenize($message);
		$allowed_types        = self::get_grounding_entity_types($chat_context, $message);
		$records              = [];

		if ($page_type !== '' && $post_id > 0) {
			$page_record = self::get_entity_record($page_type, $post_id);

			if (is_array($page_record)) {
				$records[] = $page_record;
			}
		}

		foreach ($visited_tours as $tour_id) {
			$record = self::get_entity_record('tour', $tour_id);

			if (is_array($record)) {
				$records[] = $record;
			}
		}

		foreach ($visited_destinations as $destination_id) {
			$record = self::get_entity_record('destination', $destination_id);

			if (is_array($record)) {
				$records[] = $record;
			}
		}

		$search_pool = self::filter_records_by_entity_type(
			array_merge(
				(array) ($manifest['tours'] ?? []),
				(array) ($manifest['destinations'] ?? []),
				(array) ($manifest['faqs'] ?? []),
				(array) ($manifest['posts'] ?? []),
				(array) ($manifest['testimonials'] ?? [])
			),
			$allowed_types
		);

		$scored = [];

		foreach ($search_pool as $record) {
			if (! is_array($record)) {
				continue;
			}

			$score = self::score_record($record, $tokens);

			if ($score <= 0) {
				continue;
			}

			$record['_score'] = $score;
			$scored[]         = $record;
		}

		usort(
			$scored,
			static fn(array $left, array $right): int => (int) ($right['_score'] ?? 0) <=> (int) ($left['_score'] ?? 0)
		);

		$semantic_query_parts = array_filter(
			[
				$message,
				implode(' ', (array) ($chat_context['conversation_tours'] ?? [])),
				implode(' ', (array) ($chat_context['conversation_preferences'] ?? [])),
				(string) (($chat_context['page_context']['title'] ?? '')),
			],
			static fn(string $value): bool => trim($value) !== ''
		);
		$semantic_results = SemanticSearch::search(implode(' ', $semantic_query_parts), $chat_context, 4);
		$similar_tours = SemanticSearch::find_similar_tours($visited_tours, 2);

		$records = array_merge(
			$records,
			(array) ($semantic_results['records'] ?? []),
			$similar_tours,
			array_slice($scored, 0, 3)
		);
		$records = self::dedupe_records($records);
		$records = array_slice($records, 0, 5);

		return [
			'records'          => $records,
			'snippets'         => array_map([self::class, 'format_record_snippet'], $records),
			'semantic_records' => array_slice((array) ($semantic_results['records'] ?? []), 0, 4),
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_tour_records(): array
	{
		$posts = get_posts(
			[
				'post_type'      => 'tour',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$records = [];

		foreach ($posts as $post) {
			if (! $post instanceof WP_Post) {
				continue;
			}

			$meta = TourData::get_tour_meta($post->ID);

			$records[] = [
				'entity_type'       => 'tour',
				'post_id'           => $post->ID,
				'title'             => $post->post_title,
				'slug'              => $post->post_name,
				'url'               => get_permalink($post),
				'image_url'         => get_the_post_thumbnail_url($post, 'large') ?: '',
				'excerpt'           => self::extract_excerpt($post),
				'summary'           => self::extract_summary($post),
				'duration'          => (string) ($meta['duration'] ?? ''),
				'price_from'        => (float) ($meta['price_from'] ?? 0),
				'group_size'        => (string) ($meta['group_size'] ?? ''),
				'pickup_location'   => (string) ($meta['pickup_location'] ?? ''),
				'linked_destination_titles' => self::get_linked_destination_titles((array) ($meta['linked_destinations'] ?? [])),
				'availability'      => (string) ($meta['availability'] ?? ''),
				'availability_label' => TourData::get_availability_label($post->ID),
				'difficulty'        => (string) ($meta['difficulty'] ?? ''),
				'best_season'       => (string) ($meta['best_season'] ?? ''),
				'includes'          => self::split_lines((string) ($meta['includes'] ?? '')),
				'excludes'          => self::split_lines((string) ($meta['excludes'] ?? '')),
				'booking_url'       => TourData::get_booking_url($post->ID),
				'booking_label'     => TourData::get_booking_label($post->ID),
				'taxonomies'        => [
					'tour_type'     => self::get_term_names($post->ID, 'tour_type'),
					'tour_region'   => self::get_term_names($post->ID, 'tour_region'),
					'tour_activity' => self::get_term_names($post->ID, 'tour_activity'),
					'tour_duration' => self::get_term_names($post->ID, 'tour_duration'),
				],
			];
		}

		return $records;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_destination_records(): array
	{
		$posts = get_posts(
			[
				'post_type'      => 'destination',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$records = [];

		foreach ($posts as $post) {
			if (! $post instanceof WP_Post) {
				continue;
			}

			$related_titles = self::find_related_tour_titles($post);

			$records[] = [
				'entity_type'         => 'destination',
				'post_id'             => $post->ID,
				'title'               => $post->post_title,
				'slug'                => $post->post_name,
				'url'                 => get_permalink($post),
				'image_url'           => get_the_post_thumbnail_url($post, 'large') ?: '',
				'excerpt'             => self::extract_excerpt($post),
				'summary'             => self::extract_summary($post),
				'related_tour_titles' => $related_titles,
			];
		}

		return $records;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_faq_records(): array
	{
		$posts = get_posts(
			[
				'post_type'      => 'faq',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$records = [];

		foreach ($posts as $post) {
			if (! $post instanceof WP_Post) {
				continue;
			}

			$records[] = [
				'entity_type' => 'faq',
				'post_id'     => $post->ID,
				'title'       => $post->post_title,
				'slug'        => $post->post_name,
				'url'         => get_permalink($post),
				'answer'      => self::extract_summary($post, 50),
			];
		}

		return $records;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_post_records(): array
	{
		$posts = get_posts(
			[
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$records = [];

		foreach ($posts as $post) {
			if (! $post instanceof WP_Post) {
				continue;
			}

			$records[] = [
				'entity_type'      => 'post',
				'post_id'          => $post->ID,
				'title'            => $post->post_title,
				'slug'             => $post->post_name,
				'url'              => get_permalink($post),
				'excerpt'          => self::extract_excerpt($post),
				'summary'          => self::get_ai_summary($post),
				'focus_topic'      => self::get_focus_topic($post),
				'target_audience'  => (string) get_post_meta($post->ID, '_twostars_editorial_target_audience', true),
				'primary_cta'      => (string) get_post_meta($post->ID, '_twostars_editorial_primary_cta', true),
				'crm_intent'       => (string) get_post_meta($post->ID, '_twostars_editorial_crm_intent', true),
				'categories'       => self::get_term_names($post->ID, 'category'),
			];
		}

		return $records;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_testimonial_records(): array
	{
		$posts = get_posts(
			[
				'post_type'      => 'testimonial',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$records = [];

		foreach ($posts as $post) {
			if (! $post instanceof WP_Post) {
				continue;
			}

			$records[] = [
				'entity_type' => 'testimonial',
				'post_id'     => $post->ID,
				'title'       => $post->post_title,
				'slug'        => $post->post_name,
				'url'         => get_permalink($post),
				'excerpt'     => self::extract_excerpt($post),
				'summary'     => self::get_ai_summary($post),
				'crm_intent'  => (string) get_post_meta($post->ID, '_twostars_editorial_crm_intent', true),
			];
		}

		return $records;
	}

	/**
	 * @return array<int, string>
	 */
	private static function get_term_names(int $post_id, string $taxonomy): array
	{
		$terms = get_the_terms($post_id, $taxonomy);

		if (! is_array($terms)) {
			return [];
		}

		$names = [];

		foreach ($terms as $term) {
			if (! $term instanceof WP_Term) {
				continue;
			}

			$names[] = $term->name;
		}

		return $names;
	}

	/**
	 * @return array<int, string>
	 */
	private static function split_lines(string $value): array
	{
		$lines = preg_split('/\r\n|\r|\n/', $value) ?: [];

		return array_values(
			array_filter(
				array_map(
					static fn(string $line): string => trim(wp_strip_all_tags($line)),
					$lines
				),
				static fn(string $line): bool => $line !== ''
			)
		);
	}

	private static function extract_excerpt(WP_Post $post): string
	{
		if ($post->post_excerpt !== '') {
			return wp_strip_all_tags($post->post_excerpt);
		}

		return wp_trim_words(wp_strip_all_tags($post->post_content), 28, '...');
	}

	private static function extract_summary(WP_Post $post, int $word_limit = 32): string
	{
		return wp_trim_words(wp_strip_all_tags($post->post_content), $word_limit, '...');
	}

	/**
	 * @param mixed $value
	 * @return array<int, int>
	 */
	private static function normalize_id_list($value): array
	{
		if (! is_array($value)) {
			return [];
		}

		return array_values(
			array_filter(
				array_map('absint', $value),
				static fn(int $id): bool => $id > 0
			)
		);
	}

	/**
	 * @return array<int, string>
	 */
	private static function tokenize(string $message): array
	{
		$message = strtolower(wp_strip_all_tags($message));
		$parts   = preg_split('/[^a-z0-9]+/', $message) ?: [];

		return array_values(
			array_filter(
				array_map('trim', $parts),
				static fn(string $token): bool => strlen($token) >= 3
			)
		);
	}

	/**
	 * @param array<string, mixed> $record
	 * @param array<int, string> $tokens
	 */
	private static function score_record(array $record, array $tokens): int
	{
		if ($tokens === []) {
			return 0;
		}

		$haystack = strtolower(
			implode(
				' ',
				array_filter(
					[
						(string) ($record['title'] ?? ''),
						(string) ($record['summary'] ?? ''),
						(string) ($record['answer'] ?? ''),
						(string) ($record['focus_topic'] ?? ''),
						(string) ($record['target_audience'] ?? ''),
						(string) ($record['primary_cta'] ?? ''),
						implode(' ', (array) ($record['related_tour_titles'] ?? [])),
						implode(' ', (array) (($record['taxonomies']['tour_region'] ?? []))),
						implode(' ', (array) (($record['taxonomies']['tour_type'] ?? []))),
						implode(' ', (array) (($record['taxonomies']['tour_activity'] ?? []))),
						implode(' ', (array) ($record['categories'] ?? [])),
					],
					static fn(string $value): bool => $value !== ''
				)
			)
		);
		$score = 0;

		foreach ($tokens as $token) {
			if (str_contains($haystack, $token)) {
				$score++;
			}
		}

		if ($score > 0) {
			$score += self::get_entity_priority_boost((string) ($record['entity_type'] ?? ''));
		}

		return $score;
	}

	/**
	 * @param array<int, array<string, mixed>> $records
	 * @return array<int, array<string, mixed>>
	 */
	private static function dedupe_records(array $records): array
	{
		$unique = [];
		$seen   = [];

		foreach ($records as $record) {
			$key = (string) ($record['entity_type'] ?? '') . ':' . (string) ($record['post_id'] ?? 0);

			if ($key === ':' || isset($seen[$key])) {
				continue;
			}

			$seen[$key] = true;
			$unique[]   = $record;
		}

		return $unique;
	}

	/**
	 * @param array<string, mixed> $record
	 */
	private static function format_record_snippet(array $record): string
	{
		$entity_type = (string) ($record['entity_type'] ?? 'entity');
		$title       = (string) ($record['title'] ?? '');

		if ($entity_type === 'tour') {
			$parts = array_filter(
				[
					'title=' . $title,
					'url=' . (string) ($record['url'] ?? ''),
					'duration=' . (string) ($record['duration'] ?? ''),
					'pickup=' . (string) ($record['pickup_location'] ?? ''),
					'availability=' . (string) ($record['availability_label'] ?? ''),
					'booking_url=' . (string) ($record['booking_url'] ?? ''),
					'best_season=' . (string) ($record['best_season'] ?? ''),
					'summary=' . (string) ($record['summary'] ?? ''),
					'linked_destinations=' . implode(', ', (array) ($record['linked_destination_titles'] ?? [])),
					'includes=' . implode(', ', array_slice((array) ($record['includes'] ?? []), 0, 4)),
				],
				static fn(string $value): bool => $value !== ''
			);

			return 'tour{' . implode('; ', $parts) . '}';
		}

		if ($entity_type === 'destination') {
			$parts = array_filter(
				[
					'title=' . $title,
					'url=' . (string) ($record['url'] ?? ''),
					'summary=' . (string) ($record['summary'] ?? ''),
					'related_tours=' . implode(', ', (array) ($record['related_tour_titles'] ?? [])),
				],
				static fn(string $value): bool => $value !== ''
			);

			return 'destination{' . implode('; ', $parts) . '}';
		}

		if ($entity_type === 'post') {
			$parts = array_filter(
				[
					'title=' . $title,
					'focus=' . (string) ($record['focus_topic'] ?? ''),
					'summary=' . (string) ($record['summary'] ?? ''),
					'cta=' . (string) ($record['primary_cta'] ?? ''),
				],
				static fn(string $value): bool => $value !== ''
			);

			return 'post{' . implode('; ', $parts) . '}';
		}

		if ($entity_type === 'testimonial') {
			return 'testimonial{title=' . $title . '; quote=' . (string) ($record['summary'] ?? '') . '}';
		}

		return 'faq{title=' . $title . '; answer=' . (string) ($record['answer'] ?? '') . '}';
	}

	private static function get_focus_topic(WP_Post $post): string
	{
		if ($post->post_type === 'tour') {
			$tour_focus = (string) get_post_meta($post->ID, '_twostars_tour_seo_focus_topic', true);

			if ($tour_focus !== '') {
				return $tour_focus;
			}
		}

		return (string) get_post_meta($post->ID, '_twostars_editorial_seo_focus_topic', true);
	}

	private static function get_ai_summary(WP_Post $post): string
	{
		$summary = (string) get_post_meta($post->ID, '_twostars_editorial_ai_summary', true);

		if ($summary !== '') {
			return $summary;
		}

		return self::extract_summary($post, 32);
	}

	/**
	 * @return array<int, string>
	 */
	private static function find_related_tour_titles(WP_Post $destination): array
	{
		$tours = get_posts(
			[
				'post_type'      => 'tour',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
			]
		);

		$matches = [];

		foreach ($tours as $tour) {
			if (! $tour instanceof WP_Post) {
				continue;
			}

			$linked_destination_ids = TourData::get_tour_meta($tour->ID)['linked_destinations'] ?? [];

			if (in_array($destination->ID, array_map('absint', (array) $linked_destination_ids), true)) {
				$matches[] = $tour->post_title;
			}
		}

		return array_slice(array_values(array_unique($matches)), 0, 3);
	}

	/**
	 * @param array<int, int> $destination_ids
	 * @return array<int, string>
	 */
	private static function get_linked_destination_titles(array $destination_ids): array
	{
		$titles = [];

		foreach (array_values(array_filter(array_map('absint', $destination_ids))) as $destination_id) {
			$title = get_the_title($destination_id);

			if (is_string($title) && $title !== '') {
				$titles[] = $title;
			}
		}

		return array_values(array_unique($titles));
	}

	/**
	 * @param array<string, mixed> $chat_context
	 * @return array<int, string>
	 */
	private static function get_grounding_entity_types(array $chat_context, string $message): array
	{
		$page_type = (string) ($chat_context['page_type'] ?? '');
		$message   = strtolower($message);
		$types     = ['tour', 'destination', 'faq'];

		if ($page_type === 'post' || str_contains($message, 'blog') || str_contains($message, 'article')) {
			$types[] = 'post';
		}

		if ($page_type === 'testimonial' || str_contains($message, 'review') || str_contains($message, 'testimonial')) {
			$types[] = 'testimonial';
		}

		return array_values(array_unique($types));
	}

	/**
	 * @param array<int, array<string, mixed>> $records
	 * @param array<int, string> $allowed_types
	 * @return array<int, array<string, mixed>>
	 */
	private static function filter_records_by_entity_type(array $records, array $allowed_types): array
	{
		return array_values(
			array_filter(
				$records,
				static fn(array $record): bool => in_array((string) ($record['entity_type'] ?? ''), $allowed_types, true)
			)
		);
	}

	private static function get_entity_priority_boost(string $entity_type): int
	{
		switch ($entity_type) {
			case 'tour':
				return 3;

			case 'destination':
				return 2;

			case 'faq':
				return 1;

			default:
				return 0;
		}
	}
}
