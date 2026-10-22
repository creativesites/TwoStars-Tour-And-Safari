<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

use TwoStars\Platform\Support\Service;

final class SemanticSearch implements Service
{
	private const SCHEMA_OPTION = 'twostars_ai_embeddings_schema_version';
	private const SCHEMA_VERSION = '1';

	public function register(): void
	{
		add_action('init', [self::class, 'maybe_install']);
		add_action('save_post_tour', [$this, 'sync_tour_record'], 20, 1);
		add_action('save_post_destination', [$this, 'sync_destination_record'], 20, 1);
		add_action('save_post_faq', [$this, 'sync_faq_record'], 20, 1);
		add_action('save_post_post', [$this, 'sync_post_record'], 20, 1);
		add_action('save_post_testimonial', [$this, 'sync_testimonial_record'], 20, 1);
		add_action('deleted_post', [$this, 'delete_post_records']);
	}

	public static function maybe_install(): void
	{
		if (get_option(self::SCHEMA_OPTION) === self::SCHEMA_VERSION) {
			return;
		}

		self::install();
		update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION);
	}

	public static function install(): void
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				entity_type varchar(30) NOT NULL,
				post_id bigint(20) unsigned NOT NULL,
				semantic_text longtext NOT NULL,
				embedding longtext NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY entity_post (entity_type, post_id),
				KEY post_id (post_id)
			) {$charset_collate};"
		);
	}

	public static function get_table_name(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'twostars_ai_embeddings';
	}

	public function sync_tour_record(int $post_id): void
	{
		$this->sync_entity_record('tour', $post_id);
	}

	public function sync_destination_record(int $post_id): void
	{
		$this->sync_entity_record('destination', $post_id);
	}

	public function sync_faq_record(int $post_id): void
	{
		$this->sync_entity_record('faq', $post_id);
	}

	public function sync_post_record(int $post_id): void
	{
		$this->sync_entity_record('post', $post_id);
	}

	public function sync_testimonial_record(int $post_id): void
	{
		$this->sync_entity_record('testimonial', $post_id);
	}

	public function delete_post_records(int $post_id): void
	{
		global $wpdb;

		$wpdb->delete(
			self::get_table_name(),
			['post_id' => absint($post_id)]
		);
	}

	/**
	 * @param array<string, mixed> $chat_context
	 * @return array<string, mixed>
	 */
	public static function search(string $query, array $chat_context = [], int $limit = 4): array
	{
		$records = [];
		$search_pool = self::get_search_pool($chat_context, $query);
		$query_tokens = self::tokenize($query);
		$query_embedding = GeminiConfig::is_enabled() ? GeminiClient::generate_embedding($query) : [];
		$query_embedding = is_array($query_embedding) ? $query_embedding : [];
		$stored_embeddings = self::get_stored_embeddings();
		$page_post_id = absint($chat_context['post_id'] ?? 0);
		$visited_tours = array_map('absint', (array) ($chat_context['visited_tours'] ?? []));

		foreach ($search_pool as $record) {
			if (! is_array($record)) {
				continue;
			}

			$key = self::record_key($record);
			$lexical_score = self::lexical_score($record, $query_tokens);
			$semantic_score = 0.0;

			if ($query_embedding !== [] && isset($stored_embeddings[$key])) {
				$semantic_score = self::cosine_similarity($query_embedding, $stored_embeddings[$key]);
			}

			$context_boost = 0.0;

			if ((int) ($record['post_id'] ?? 0) === $page_post_id) {
				$context_boost += 0.12;
			}

			if (in_array((int) ($record['post_id'] ?? 0), $visited_tours, true)) {
				$context_boost += 0.08;
			}

			$hybrid_score = ($semantic_score * 0.65) + (self::normalize_lexical_score($lexical_score) * 0.35) + $context_boost;

			if ($hybrid_score <= 0.05) {
				continue;
			}

			$record['_semantic_score'] = round($semantic_score, 4);
			$record['_lexical_score'] = $lexical_score;
			$record['_hybrid_score'] = round($hybrid_score, 4);
			$records[] = $record;
		}

		usort(
			$records,
			static fn(array $left, array $right): int => (($right['_hybrid_score'] ?? 0) <=> ($left['_hybrid_score'] ?? 0))
		);

		return [
			'query' => $query,
			'records' => array_slice($records, 0, max(1, $limit)),
		];
	}

	/**
	 * @param array<int, int> $tour_ids
	 * @return array<int, array<string, mixed>>
	 */
	public static function find_similar_tours(array $tour_ids, int $limit = 3): array
	{
		$tour_ids = array_values(array_filter(array_map('absint', $tour_ids)));

		if ($tour_ids === []) {
			return [];
		}

		$manifest = ContextStore::get_manifest();
		$tours = array_values(array_filter((array) ($manifest['tours'] ?? []), static fn($record): bool => is_array($record)));
		$query_terms = [];

		foreach ($tours as $tour) {
			if (! in_array((int) ($tour['post_id'] ?? 0), $tour_ids, true)) {
				continue;
			}

			$query_terms[] = (string) ($tour['title'] ?? '');
			$query_terms[] = implode(' ', (array) ($tour['linked_destination_titles'] ?? []));
			$query_terms[] = implode(' ', (array) ($tour['taxonomies']['tour_activity'] ?? []));
		}

		if ($query_terms === []) {
			return [];
		}

		$result = self::search(implode(' ', $query_terms), ['visited_tours' => $tour_ids], $limit + count($tour_ids));

		return array_values(
			array_filter(
				(array) ($result['records'] ?? []),
				static fn(array $record): bool => ! in_array((int) ($record['post_id'] ?? 0), $tour_ids, true)
			)
		);
	}

	private function sync_entity_record(string $entity_type, int $post_id): void
	{
		global $wpdb;

		$post_id = absint($post_id);

		if ($post_id <= 0 || wp_is_post_revision($post_id)) {
			return;
		}

		$record = ContextStore::get_entity_record($entity_type, $post_id);

		if (! is_array($record)) {
			$wpdb->delete(
				self::get_table_name(),
				[
					'entity_type' => $entity_type,
					'post_id'     => $post_id,
				]
			);
			return;
		}

		$semantic_text = self::build_semantic_text($record);
		$embedding = GeminiConfig::is_enabled() ? GeminiClient::generate_embedding($semantic_text) : [];
		$embedding = is_array($embedding) ? $embedding : [];

		$wpdb->replace(
			self::get_table_name(),
			[
				'entity_type'   => $entity_type,
				'post_id'       => $post_id,
				'semantic_text' => $semantic_text,
				'embedding'     => $embedding !== [] ? wp_json_encode($embedding) : null,
				'updated_at'    => current_time('mysql'),
			]
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_search_pool(array $chat_context = [], string $query = ''): array
	{
		$manifest = ContextStore::get_manifest();
		$allowed_types = self::get_allowed_entity_types($chat_context, $query);

		$records = array_merge(
			(array) ($manifest['tours'] ?? []),
			(array) ($manifest['destinations'] ?? []),
			(array) ($manifest['faqs'] ?? []),
			(array) ($manifest['posts'] ?? []),
			(array) ($manifest['testimonials'] ?? [])
		);

		return array_values(
			array_filter(
				$records,
				static fn(array $record): bool => in_array((string) ($record['entity_type'] ?? ''), $allowed_types, true)
			)
		);
	}

	/**
	 * @return array<string, array<int, float>>
	 */
	private static function get_stored_embeddings(): array
	{
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT entity_type, post_id, embedding FROM " . self::get_table_name() . " WHERE embedding IS NOT NULL AND embedding != ''",
			ARRAY_A
		);

		if (! is_array($rows)) {
			return [];
		}

		$embeddings = [];

		foreach ($rows as $row) {
			if (! is_array($row)) {
				continue;
			}

			$key = (string) ($row['entity_type'] ?? '') . ':' . (int) ($row['post_id'] ?? 0);
			$decoded = json_decode((string) ($row['embedding'] ?? ''), true);

			if ($key === ':' || ! is_array($decoded)) {
				continue;
			}

			$embeddings[$key] = array_map('floatval', $decoded);
		}

		return $embeddings;
	}

	/**
	 * @param array<string, mixed> $record
	 */
	private static function record_key(array $record): string
	{
		return (string) ($record['entity_type'] ?? '') . ':' . (int) ($record['post_id'] ?? 0);
	}

	/**
	 * @param array<string, mixed> $record
	 */
	private static function build_semantic_text(array $record): string
	{
		$parts = array_filter(
			[
				str_repeat((string) ($record['title'] ?? ''), 2),
				(string) ($record['summary'] ?? ''),
				(string) ($record['answer'] ?? ''),
				(string) ($record['excerpt'] ?? ''),
				(string) ($record['duration'] ?? ''),
				(string) ($record['pickup_location'] ?? ''),
				(string) ($record['best_season'] ?? ''),
				(string) ($record['availability_label'] ?? ''),
				implode(', ', (array) ($record['includes'] ?? [])),
				implode(', ', (array) ($record['linked_destination_titles'] ?? [])),
				implode(', ', (array) ($record['related_tour_titles'] ?? [])),
				implode(', ', (array) (($record['taxonomies']['tour_type'] ?? []))),
				implode(', ', (array) (($record['taxonomies']['tour_region'] ?? []))),
				implode(', ', (array) (($record['taxonomies']['tour_activity'] ?? []))),
				implode(', ', (array) ($record['categories'] ?? [])),
				(string) ($record['focus_topic'] ?? ''),
			],
			static fn(string $value): bool => trim($value) !== ''
		);

		return implode("\n", $parts);
	}

	/**
	 * @param array<int, string> $tokens
	 * @param array<string, mixed> $record
	 */
	private static function lexical_score(array $record, array $tokens): int
	{
		if ($tokens === []) {
			return 0;
		}

		$haystack = strtolower(self::build_semantic_text($record));
		$score = 0;

		foreach ($tokens as $token) {
			if (str_contains($haystack, $token)) {
				$score++;
			}
		}

		return $score;
	}

	private static function normalize_lexical_score(int $score): float
	{
		return max(0.0, min(1.0, $score / 8));
	}

	/**
	 * @param array<string, mixed> $chat_context
	 * @return array<int, string>
	 */
	private static function get_allowed_entity_types(array $chat_context, string $query): array
	{
		$page_type = (string) ($chat_context['page_type'] ?? '');
		$query     = strtolower($query);
		$types     = ['tour', 'destination', 'faq'];

		if ($page_type === 'post' || str_contains($query, 'blog') || str_contains($query, 'article')) {
			$types[] = 'post';
		}

		if ($page_type === 'testimonial' || str_contains($query, 'review') || str_contains($query, 'testimonial')) {
			$types[] = 'testimonial';
		}

		return array_values(array_unique($types));
	}

	/**
	 * @return array<int, string>
	 */
	private static function tokenize(string $value): array
	{
		$value = strtolower(wp_strip_all_tags($value));
		$parts = preg_split('/[^a-z0-9]+/', $value) ?: [];

		return array_values(
			array_filter(
				array_map('trim', $parts),
				static fn(string $token): bool => strlen($token) >= 3
			)
		);
	}

	/**
	 * @param array<int, float> $left
	 * @param array<int, float> $right
	 */
	private static function cosine_similarity(array $left, array $right): float
	{
		$length = min(count($left), count($right));

		if ($length === 0) {
			return 0.0;
		}

		$dot = 0.0;
		$left_magnitude = 0.0;
		$right_magnitude = 0.0;

		for ($index = 0; $index < $length; $index++) {
			$dot += $left[$index] * $right[$index];
			$left_magnitude += $left[$index] * $left[$index];
			$right_magnitude += $right[$index] * $right[$index];
		}

		if ($left_magnitude <= 0.0 || $right_magnitude <= 0.0) {
			return 0.0;
		}

		return $dot / (sqrt($left_magnitude) * sqrt($right_magnitude));
	}
}
