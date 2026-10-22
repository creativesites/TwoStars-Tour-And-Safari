<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

use TwoStars\Platform\Support\Service;

final class ConversationMemory implements Service
{
	private const SCHEMA_OPTION = 'twostars_ai_chat_schema_version';
	private const SCHEMA_VERSION = '3';

	public function register(): void
	{
		add_action('init', [self::class, 'maybe_install']);
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

		$table = self::get_messages_table_name();
		$summary_table = self::get_summaries_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				session_key varchar(64) NOT NULL,
				source_session_key varchar(64) NULL,
				visitor_profile_id bigint(20) unsigned NOT NULL DEFAULT 0,
				role varchar(20) NOT NULL,
				content longtext NOT NULL,
				metadata longtext NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY session_key (session_key),
				KEY source_session_key (source_session_key),
				KEY visitor_profile_id (visitor_profile_id),
				KEY role (role)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$summary_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				session_key varchar(64) NOT NULL,
				source_session_key varchar(64) NULL,
				visitor_profile_id bigint(20) unsigned NOT NULL DEFAULT 0,
				summary longtext NOT NULL,
				mentioned_tours longtext NULL,
				preferences longtext NULL,
				message_count int unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY session_key (session_key),
				KEY source_session_key (source_session_key),
				KEY visitor_profile_id (visitor_profile_id)
			) {$charset_collate};"
		);
	}

	public static function get_messages_table_name(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'twostars_ai_chat_messages';
	}

	public static function get_summaries_table_name(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'twostars_ai_chat_summaries';
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public static function store_message(string $session_key, string $role, string $content, array $metadata = [], string $source_session_key = '', int $visitor_profile_id = 0): void
	{
		global $wpdb;

		if ($session_key === '' || $content === '') {
			return;
		}

		$wpdb->insert(
			self::get_messages_table_name(),
			[
				'session_key'         => sanitize_text_field($session_key),
				'source_session_key'  => sanitize_text_field($source_session_key),
				'visitor_profile_id'  => absint($visitor_profile_id),
				'role'                => sanitize_key($role),
				'content'             => wp_kses_post($content),
				'metadata'            => $metadata !== [] ? wp_json_encode($metadata) : null,
				'created_at'          => current_time('mysql'),
			]
		);

		if ($role === 'assistant') {
			self::maybe_refresh_summary($session_key);
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_history(string $session_key, int $limit = 8): array
	{
		global $wpdb;

		if ($session_key === '') {
			return [];
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT role, content, metadata, created_at
				FROM " . self::get_messages_table_name() . "
				WHERE session_key = %s
				ORDER BY created_at DESC, id DESC
				LIMIT %d",
				$session_key,
				max(1, $limit)
			),
			ARRAY_A
		);

		if (! is_array($rows)) {
			return [];
		}

		$history = [];

		foreach (array_reverse($rows) as $row) {
			if (! is_array($row)) {
				continue;
			}

			$history[] = [
				'role'       => (string) ($row['role'] ?? 'user'),
				'content'    => trim((string) ($row['content'] ?? '')),
				'metadata'   => self::decode_metadata((string) ($row['metadata'] ?? '')),
				'created_at' => (string) ($row['created_at'] ?? ''),
			];
		}

		return array_values(
			array_filter(
				$history,
				static fn(array $message): bool => $message['content'] !== ''
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_context(string $session_key, int $limit = 8): array
	{
		$history = self::get_history($session_key, $limit);
		$summary_record = self::get_summary_record($session_key);
		$tour_titles = (array) ($summary_record['mentioned_tours'] ?? []);
		$preferences = (array) ($summary_record['preferences'] ?? []);

		foreach ($history as $message) {
			$content = (string) ($message['content'] ?? '');
			$metadata = (array) ($message['metadata'] ?? []);

			foreach ((array) ($metadata['tours_mentioned'] ?? []) as $tour_title) {
				if (is_string($tour_title) && $tour_title !== '') {
					$tour_titles[] = $tour_title;
				}
			}

			if ((string) ($message['role'] ?? '') !== 'user') {
				continue;
			}

			if (preg_match('/\b(prefer|want|looking for|need|budget|dates?)\b/i', $content)) {
				$preferences[] = $content;
			}
		}

			return [
				'history'          => $history,
				'message_count'    => count($history),
				'summary'          => (string) ($summary_record['summary'] ?? ''),
				'mentioned_tours'  => array_values(array_unique($tour_titles)),
				'preferences'      => array_slice(array_values(array_unique($preferences)), 0, 5),
			];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_threads_for_context(string $source_session_key = '', int $visitor_profile_id = 0, int $limit = 20): array
	{
		global $wpdb;

		$limit = max(1, min(50, $limit));
		$messages_table = self::get_messages_table_name();
		$where = '';
		$args = [];

		if ($visitor_profile_id > 0) {
			$where = 'visitor_profile_id = %d';
			$args[] = $visitor_profile_id;
		} elseif ($source_session_key !== '') {
			$where = 'source_session_key = %s';
			$args[] = sanitize_text_field($source_session_key);
		} else {
			return [];
		}

		$sql = $wpdb->prepare(
			"SELECT session_key, source_session_key, visitor_profile_id, MAX(created_at) AS updated_at, COUNT(*) AS message_count
			FROM {$messages_table}
			WHERE {$where}
			GROUP BY session_key, source_session_key, visitor_profile_id
			ORDER BY updated_at DESC
			LIMIT %d",
			...array_merge($args, [$limit])
		);

		$rows = $wpdb->get_results($sql, ARRAY_A);

		if (! is_array($rows)) {
			return [];
		}

		$threads = [];

		foreach ($rows as $row) {
			if (! is_array($row)) {
				continue;
			}

			$conversation_key = (string) ($row['session_key'] ?? '');

			if ($conversation_key === '') {
				continue;
			}

			$history = self::get_history($conversation_key, 12);
			$summary = self::get_summary_record($conversation_key);
			$threads[] = [
				'id'                => $conversation_key,
				'conversation_key'  => $conversation_key,
				'source_session_key'=> (string) ($row['source_session_key'] ?? ''),
				'visitor_profile_id'=> (int) ($row['visitor_profile_id'] ?? 0),
				'title'             => self::build_thread_title($history, $summary),
				'preview'           => self::build_thread_preview($history),
				'updated_at'        => (string) ($row['updated_at'] ?? ''),
				'message_count'     => (int) ($row['message_count'] ?? count($history)),
				'history'           => $history,
			];
		}

		return $threads;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_thread_detail(string $conversation_key, int $limit = 80): array
	{
		global $wpdb;

		$conversation_key = sanitize_text_field($conversation_key);

		if ($conversation_key === '') {
			return [];
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT session_key, source_session_key, visitor_profile_id, MIN(created_at) AS started_at, MAX(created_at) AS updated_at, COUNT(*) AS message_count
				FROM " . self::get_messages_table_name() . "
				WHERE session_key = %s
				GROUP BY session_key, source_session_key, visitor_profile_id
				LIMIT 1",
				$conversation_key
			),
			ARRAY_A
		);

		if (! is_array($row)) {
			return [];
		}

		$history = self::get_history($conversation_key, max(1, min(200, $limit)));
		$summary = self::get_summary_record($conversation_key);

		return [
			'id'                 => $conversation_key,
			'conversation_key'   => $conversation_key,
			'source_session_key' => (string) ($row['source_session_key'] ?? ''),
			'visitor_profile_id' => (int) ($row['visitor_profile_id'] ?? 0),
			'title'              => self::build_thread_title($history, $summary),
			'preview'            => self::build_thread_preview($history),
			'started_at'         => (string) ($row['started_at'] ?? ''),
			'updated_at'         => (string) ($row['updated_at'] ?? ''),
			'message_count'      => (int) ($row['message_count'] ?? count($history)),
			'summary'            => (string) ($summary['summary'] ?? ''),
			'history'            => $history,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_summary_record(string $session_key): array
	{
		global $wpdb;

		if ($session_key === '') {
			return [];
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT summary, source_session_key, visitor_profile_id, mentioned_tours, preferences, message_count, updated_at
				FROM " . self::get_summaries_table_name() . "
				WHERE session_key = %s",
				$session_key
			),
			ARRAY_A
		);

		if (! is_array($row)) {
			return [];
		}

		return [
			'summary'         => (string) ($row['summary'] ?? ''),
			'source_session_key' => (string) ($row['source_session_key'] ?? ''),
			'visitor_profile_id' => (int) ($row['visitor_profile_id'] ?? 0),
			'mentioned_tours' => self::decode_list((string) ($row['mentioned_tours'] ?? '')),
			'preferences'     => self::decode_list((string) ($row['preferences'] ?? '')),
			'message_count'   => (int) ($row['message_count'] ?? 0),
			'updated_at'      => (string) ($row['updated_at'] ?? ''),
		];
	}

	private static function maybe_refresh_summary(string $session_key): void
	{
		global $wpdb;

		$history = self::get_history($session_key, 10);
		$message_count = count($history);
		$thread_detail = self::get_thread_detail($session_key, 10);

		if ($message_count < 4) {
			return;
		}

		$existing = self::get_summary_record($session_key);

		if ((int) ($existing['message_count'] ?? 0) >= $message_count) {
			return;
		}

		if ($message_count < 8 || $message_count % 4 !== 0) {
			return;
		}

		$summary_payload = self::build_summary_payload($history);

		$wpdb->replace(
			self::get_summaries_table_name(),
			[
				'session_key'         => sanitize_text_field($session_key),
				'source_session_key'  => sanitize_text_field((string) ($thread_detail['source_session_key'] ?? '')),
				'visitor_profile_id'  => absint((int) ($thread_detail['visitor_profile_id'] ?? 0)),
				'summary'             => $summary_payload['summary'],
				'mentioned_tours'     => wp_json_encode($summary_payload['mentioned_tours']),
				'preferences'         => wp_json_encode($summary_payload['preferences']),
				'message_count'       => $message_count,
				'updated_at'          => current_time('mysql'),
			]
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $history
	 * @param array<string, mixed> $summary
	 */
	private static function build_thread_title(array $history, array $summary): string
	{
		foreach ($history as $message) {
			if ((string) ($message['role'] ?? '') !== 'user') {
				continue;
			}

			$content = trim((string) ($message['content'] ?? ''));

			if ($content !== '') {
				return wp_trim_words($content, 8, '');
			}
		}

		$summary_text = trim((string) ($summary['summary'] ?? ''));

		if ($summary_text !== '') {
			return wp_trim_words($summary_text, 8, '');
		}

		return __('Two Stars planning', 'twostars-platform');
	}

	/**
	 * @param array<int, array<string, mixed>> $history
	 */
	private static function build_thread_preview(array $history): string
	{
		for ($index = count($history) - 1; $index >= 0; $index--) {
			$content = trim((string) ($history[$index]['content'] ?? ''));

			if ($content !== '') {
				return wp_trim_words($content, 16, '...');
			}
		}

		return '';
	}

	/**
	 * @param array<int, array<string, mixed>> $history
	 * @return array<string, mixed>
	 */
	private static function build_summary_payload(array $history): array
	{
		$mentioned_tours = [];
		$preferences = [];
		$transcript = [];

		foreach ($history as $message) {
			$role = (string) ($message['role'] ?? 'user');
			$content = trim((string) ($message['content'] ?? ''));
			$metadata = (array) ($message['metadata'] ?? []);

			if ($content === '') {
				continue;
			}

			$transcript[] = ucfirst($role) . ': ' . $content;

			foreach ((array) ($metadata['tours_mentioned'] ?? []) as $tour_title) {
				if (is_string($tour_title) && $tour_title !== '') {
					$mentioned_tours[] = $tour_title;
				}
			}

			if ($role === 'user' && preg_match('/\b(prefer|want|looking for|need|budget|dates?)\b/i', $content)) {
				$preferences[] = $content;
			}
		}

		$mentioned_tours = array_values(array_unique($mentioned_tours));
		$preferences = array_slice(array_values(array_unique($preferences)), 0, 5);
		$summary = self::build_fallback_summary($mentioned_tours, $preferences, $history);

		if (GeminiConfig::is_enabled() && count($transcript) >= 6) {
			$prompt = "Summarize this Two Stars tour planning conversation in 3 short sentences. " .
				"Capture tours or destinations mentioned, visitor preferences, pickup or private-tour needs, and the immediate next planning need.\n\n" .
				implode("\n", $transcript);
			$generated = GeminiClient::generate_reasoned_text($prompt);

			if (is_string($generated) && trim($generated) !== '') {
				$summary = trim($generated);
			}
		}

		return [
			'summary'         => $summary,
			'mentioned_tours' => $mentioned_tours,
			'preferences'     => $preferences,
		];
	}

	/**
	 * @param array<int, string> $mentioned_tours
	 * @param array<int, string> $preferences
	 * @param array<int, array<string, mixed>> $history
	 */
	private static function build_fallback_summary(array $mentioned_tours, array $preferences, array $history): string
	{
		$parts = [];

		if ($mentioned_tours !== []) {
			$parts[] = 'Discussed tours: ' . implode(', ', array_slice($mentioned_tours, 0, 3)) . '.';
		}

		if ($preferences !== []) {
			$parts[] = 'Visitor preferences: ' . implode(' ', array_slice($preferences, 0, 2));
		}

		if ($parts === [] && $history !== []) {
			$last_user_message = '';

			foreach (array_reverse($history) as $message) {
				if ((string) ($message['role'] ?? '') === 'user') {
					$last_user_message = trim((string) ($message['content'] ?? ''));
					break;
				}
			}

			if ($last_user_message !== '') {
				$parts[] = 'Latest visitor need: ' . $last_user_message;
			}
		}

		return implode(' ', $parts);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function decode_metadata(string $raw): array
	{
		if ($raw === '') {
			return [];
		}

		$decoded = json_decode($raw, true);

		return is_array($decoded) ? $decoded : [];
	}

	/**
	 * @return array<int, string>
	 */
	private static function decode_list(string $raw): array
	{
		if ($raw === '') {
			return [];
		}

		$decoded = json_decode($raw, true);

		if (! is_array($decoded)) {
			return [];
		}

		return array_values(
			array_filter(
				array_map(
					static fn($value): string => is_scalar($value) ? trim((string) $value) : '',
					$decoded
				),
				static fn(string $value): bool => $value !== ''
			)
		);
	}
}
