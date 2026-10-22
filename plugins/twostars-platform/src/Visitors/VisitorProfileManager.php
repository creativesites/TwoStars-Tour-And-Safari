<?php
declare(strict_types=1);

namespace TwoStars\Platform\Visitors;

use TwoStars\Platform\AI\ConversationMemory;
use TwoStars\Platform\AI\WebsiteIntelligence;
use TwoStars\Platform\Support\Service;
use WP_Error;

final class VisitorProfileManager implements Service
{
	private const SCHEMA_OPTION  = 'twostars_visitor_schema_version';
	private const SCHEMA_VERSION = '2';

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

		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::get_table_name();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				fingerprint_visitor_id varchar(191) NOT NULL,
				fingerprint_event_id varchar(191) NULL,
				fingerprint_request_id varchar(191) NULL,
				session_key varchar(64) NULL,
				consent_status varchar(20) NOT NULL DEFAULT 'granted',
				consented_at datetime NULL,
				first_seen_at datetime NOT NULL,
				last_seen_at datetime NOT NULL,
				is_returning tinyint(1) NOT NULL DEFAULT 0,
				location_source varchar(20) NULL,
				country_code varchar(8) NULL,
				country_name varchar(100) NULL,
				region varchar(120) NULL,
				city varchar(120) NULL,
				city_lat decimal(10,6) NULL,
				city_lng decimal(10,6) NULL,
				currencies longtext NULL,
				languages longtext NULL,
				browser varchar(100) NULL,
				browser_version varchar(30) NULL,
				device_brand varchar(100) NULL,
				device_model varchar(100) NULL,
				device_family varchar(100) NULL,
				os varchar(100) NULL,
				os_version varchar(30) NULL,
				latest_persona varchar(50) NULL,
				latest_stage varchar(50) NULL,
				session_count int unsigned NOT NULL DEFAULT 0,
				page_view_count int unsigned NOT NULL DEFAULT 0,
				tour_view_count int unsigned NOT NULL DEFAULT 0,
				destination_view_count int unsigned NOT NULL DEFAULT 0,
				ai_open_count int unsigned NOT NULL DEFAULT 0,
				ai_message_count int unsigned NOT NULL DEFAULT 0,
				booking_click_count int unsigned NOT NULL DEFAULT 0,
				booking_start_count int unsigned NOT NULL DEFAULT 0,
				booking_complete_count int unsigned NOT NULL DEFAULT 0,
				whatsapp_click_count int unsigned NOT NULL DEFAULT 0,
				high_intent_count int unsigned NOT NULL DEFAULT 0,
				visited_tours longtext NULL,
				visited_destinations longtext NULL,
				lead_ids longtext NULL,
				booking_ids longtext NULL,
				last_origin_url text NULL,
				internal_status varchar(50) NOT NULL DEFAULT 'new',
				internal_notes longtext NULL,
				raw_visitor_api longtext NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY fingerprint_visitor_id (fingerprint_visitor_id),
				KEY last_seen_at (last_seen_at),
				KEY country_code (country_code),
				KEY city (city)
			) {$charset_collate};"
		);
	}

	public static function get_table_name(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'twostars_visitor_profiles';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_public_config(): array
	{
		$settings = self::get_settings();

		return [
			'enabled'                => self::is_tracking_enabled(),
			'personalizationEnabled' => self::is_personalization_enabled(),
			'consentRequired'        => true,
			'consentState'           => 'unknown',
			'consentCopy'            => [
				'title'       => (string) ($settings['visitor_consent_title'] ?? __('Personalized trip planning', 'twostars-platform')),
				'description' => (string) ($settings['visitor_consent_description'] ?? __('Allow Two Stars to remember your planning journey, tailor route suggestions, and show the right guidance for your location and device.', 'twostars-platform')),
				'acceptLabel' => (string) ($settings['visitor_consent_accept_label'] ?? __('Allow personalization', 'twostars-platform')),
				'declineLabel' => (string) ($settings['visitor_consent_decline_label'] ?? __('No thanks', 'twostars-platform')),
			],
			'visitorApiProjectId'    => sanitize_text_field((string) ($settings['visitorapi_project_id'] ?? '')),
			'fingerprintPublicKey'   => sanitize_text_field((string) ($settings['fingerprint_public_key'] ?? '')),
			'fingerprintRegion'      => sanitize_text_field((string) ($settings['fingerprint_region'] ?? 'ap')) ?: 'ap',
			'fingerprintAgentUrl'    => sanitize_text_field((string) ($settings['fingerprint_agent_url'] ?? 'https://fpjscdn.net/v4/16ovVe3T8gJzgsEJaXaS')),
		];
	}

	public static function is_tracking_enabled(): bool
	{
		$settings = self::get_settings();

		return ! empty($settings['visitor_tracking_enabled']);
	}

	public static function is_personalization_enabled(): bool
	{
		$settings = self::get_settings();

		return ! empty($settings['visitor_personalization_enabled']) && self::is_tracking_enabled();
	}

	public static function get_retention_days(): int
	{
		$settings = self::get_settings();

		return max(30, absint($settings['visitor_retention_days'] ?? 180));
	}

	public static function record_session_consent(string $session_key, string $consent_state): void
	{
		global $wpdb;

		$session_key = sanitize_text_field($session_key);
		$consent_state = in_array($consent_state, ['granted', 'denied'], true) ? $consent_state : 'unknown';

		if ($session_key === '') {
			return;
		}

		$wpdb->update(
			WebsiteIntelligence::get_sessions_table_name(),
			[
				'visitor_consent_state'      => $consent_state,
				'visitor_consent_updated_at' => current_time('mysql'),
			],
			['session_key' => $session_key]
		);
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>|WP_Error
	 */
	public static function identify(array $payload)
	{
		if (! self::is_tracking_enabled()) {
			return [
				'success' => false,
				'message' => __('Visitor tracking is disabled.', 'twostars-platform'),
			];
		}

		global $wpdb;

		$session_key      = sanitize_text_field((string) ($payload['session_key'] ?? ''));
		$page_url         = esc_url_raw((string) ($payload['page_url'] ?? ''));
		$fingerprint      = is_array($payload['fingerprint'] ?? null) ? (array) $payload['fingerprint'] : [];
		$visitor_api      = is_array($payload['visitor_api'] ?? null) ? (array) $payload['visitor_api'] : [];
		$visitor_id       = sanitize_text_field((string) ($fingerprint['visitor_id'] ?? $fingerprint['visitorId'] ?? ''));
		$event_id         = sanitize_text_field((string) ($fingerprint['event_id'] ?? $fingerprint['eventId'] ?? ''));
		$request_id       = sanitize_text_field((string) ($fingerprint['request_id'] ?? $fingerprint['requestId'] ?? ''));
		$consent_state    = sanitize_text_field((string) ($payload['consent_state'] ?? 'granted'));
		$consent_state    = in_array($consent_state, ['granted', 'denied'], true) ? $consent_state : 'granted';

		self::record_session_consent($session_key, $consent_state);

		if ($consent_state !== 'granted') {
			return [
				'success'       => true,
				'consent_state' => 'denied',
			];
		}

		if ($visitor_id === '') {
			return new WP_Error(
				'twostars_missing_visitor_id',
				__('A Fingerprint visitor ID is required to create a visitor profile.', 'twostars-platform'),
				['status' => 400]
			);
		}

		$table      = self::get_table_name();
		$existing   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE fingerprint_visitor_id = %s", $visitor_id), ARRAY_A);
		$existing   = is_array($existing) ? $existing : [];
		$fingerprint_location = self::normalize_fingerprint_payload($fingerprint);
		$visitorapi_location  = self::normalize_visitor_api_payload($visitor_api);
		$normalized = self::merge_location_payloads($fingerprint_location, $visitorapi_location);
		$now        = current_time('mysql');

		$record = [
			'fingerprint_visitor_id' => $visitor_id,
			'fingerprint_event_id'   => $event_id !== '' ? $event_id : (string) ($existing['fingerprint_event_id'] ?? ''),
			'fingerprint_request_id' => $request_id !== '' ? $request_id : (string) ($existing['fingerprint_request_id'] ?? ''),
			'session_key'            => $session_key !== '' ? $session_key : (string) ($existing['session_key'] ?? ''),
			'consent_status'         => 'granted',
			'consented_at'           => (string) ($existing['consented_at'] ?? $now),
			'first_seen_at'          => (string) ($existing['first_seen_at'] ?? $now),
			'last_seen_at'           => $now,
			'is_returning'           => ! empty($existing) ? 1 : 0,
			'location_source'        => $normalized['location_source'] !== '' ? $normalized['location_source'] : (string) ($existing['location_source'] ?? ''),
			'country_code'           => $normalized['country_code'],
			'country_name'           => $normalized['country_name'],
			'region'                 => $normalized['region'],
			'city'                   => $normalized['city'],
			'city_lat'               => $normalized['city_lat'],
			'city_lng'               => $normalized['city_lng'],
			'currencies'             => wp_json_encode($normalized['currencies']),
			'languages'              => wp_json_encode($normalized['languages']),
			'browser'                => $normalized['browser'],
			'browser_version'        => $normalized['browser_version'],
			'device_brand'           => $normalized['device_brand'],
			'device_model'           => $normalized['device_model'],
			'device_family'          => $normalized['device_family'],
			'os'                     => $normalized['os'],
			'os_version'             => $normalized['os_version'],
			'last_origin_url'        => $page_url !== '' ? $page_url : (string) ($existing['last_origin_url'] ?? ''),
			'raw_visitor_api'        => $visitor_api !== [] ? wp_json_encode($visitor_api) : (string) ($existing['raw_visitor_api'] ?? ''),
		];

		if (! empty($existing['id'])) {
			$updated = $wpdb->update($table, $record, ['id' => (int) $existing['id']]);
			if ($updated === false) {
				return new WP_Error(
					'twostars_visitor_update_failed',
					__('The visitor profile could not be updated.', 'twostars-platform'),
					['status' => 500]
				);
			}
			$profile_id = (int) $existing['id'];
		} else {
			$record = array_merge(
				[
					'latest_persona'       => 'general_browser',
					'latest_stage'         => 'browsing',
					'session_count'        => 0,
					'page_view_count'      => 0,
					'tour_view_count'      => 0,
					'destination_view_count' => 0,
					'ai_open_count'        => 0,
					'ai_message_count'     => 0,
					'booking_click_count'  => 0,
					'booking_start_count'  => 0,
					'booking_complete_count' => 0,
					'whatsapp_click_count' => 0,
					'high_intent_count'    => 0,
					'visited_tours'        => wp_json_encode([]),
					'visited_destinations' => wp_json_encode([]),
					'lead_ids'             => wp_json_encode([]),
					'booking_ids'          => wp_json_encode([]),
				],
				$record
			);
			$inserted = $wpdb->insert($table, $record);
			if ($inserted === false) {
				return new WP_Error(
					'twostars_visitor_insert_failed',
					__('The visitor profile could not be created.', 'twostars-platform'),
					['status' => 500]
				);
			}
			$profile_id = (int) $wpdb->insert_id;
		}

		self::link_session_to_profile($session_key, $profile_id);
		self::refresh_profile_summary($profile_id);

		return [
			'success'            => true,
			'consent_state'      => 'granted',
			'visitor_profile_id' => $profile_id,
			'visitor'            => self::get_profile_context_by_id($profile_id),
		];
	}

	public static function link_session_to_profile(string $session_key, int $profile_id): void
	{
		global $wpdb;

		$session_key = sanitize_text_field($session_key);
		$profile_id  = absint($profile_id);

		if ($session_key === '' || $profile_id <= 0) {
			return;
		}

		$wpdb->update(
			WebsiteIntelligence::get_sessions_table_name(),
			[
				'visitor_profile_id'        => $profile_id,
				'visitor_consent_state'     => 'granted',
				'visitor_consent_updated_at' => current_time('mysql'),
			],
			['session_key' => $session_key]
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE " . WebsiteIntelligence::get_events_table_name() . " SET visitor_profile_id = %d WHERE session_key = %s",
				$profile_id,
				$session_key
			)
		);
	}

	public static function sync_profile_after_event(string $session_key): void
	{
		$profile = self::get_profile_by_session_key($session_key);

		if ($profile === null) {
			return;
		}

		self::refresh_profile_summary((int) $profile['id']);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_session_visitor_context(string $session_key): array
	{
		$profile = self::get_profile_by_session_key($session_key);

		if ($profile === null) {
			return [];
		}

		return self::build_profile_context($profile);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_profile_by_session_key(string $session_key): ?array
	{
		global $wpdb;

		$session_key = sanitize_text_field($session_key);

		if ($session_key === '') {
			return null;
		}

		$table = self::get_table_name();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT profiles.*
				FROM {$table} AS profiles
				INNER JOIN " . WebsiteIntelligence::get_sessions_table_name() . " AS sessions
					ON sessions.visitor_profile_id = profiles.id
				WHERE sessions.session_key = %s
				LIMIT 1",
				$session_key
			),
			ARRAY_A
		);

		return is_array($row) ? $row : null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_profile_by_id(int $profile_id): ?array
	{
		global $wpdb;

		if ($profile_id <= 0) {
			return null;
		}

		$table = self::get_table_name();
		$row   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $profile_id), ARRAY_A);

		return is_array($row) ? $row : null;
	}

	public static function get_profile_context_by_id(int $profile_id): array
	{
		$profile = self::get_profile_by_id($profile_id);

		return $profile ? self::build_profile_context($profile) : [];
	}

	public static function link_profile_to_record(int $profile_id, string $post_type, int $record_id): void
	{
		$profile_id = absint($profile_id);
		$record_id  = absint($record_id);

		if ($profile_id <= 0 || $record_id <= 0) {
			return;
		}

		if ($post_type === 'lead') {
			update_post_meta($record_id, '_twostars_lead_visitor_profile_id', $profile_id);
		}

		if ($post_type === 'booking') {
			update_post_meta($record_id, '_twostars_booking_visitor_profile_id', $profile_id);
		}

		self::refresh_profile_summary($profile_id);
	}

	public static function maybe_link_records_by_contact(int $profile_id, string $email = '', string $phone = ''): void
	{
		$profile_id = absint($profile_id);

		if ($profile_id <= 0) {
			return;
		}

		$email_normalized = self::normalize_email($email);
		$phone_normalized = self::normalize_phone($phone);

		foreach (self::find_matching_record_ids('lead', $email_normalized, $phone_normalized) as $lead_id) {
			update_post_meta($lead_id, '_twostars_lead_visitor_profile_id', $profile_id);
		}

		foreach (self::find_matching_record_ids('booking', $email_normalized, $phone_normalized) as $booking_id) {
			update_post_meta($booking_id, '_twostars_booking_visitor_profile_id', $profile_id);
		}

		self::refresh_profile_summary($profile_id);
	}

	public static function refresh_profile_summary(int $profile_id): void
	{
		global $wpdb;

		$profile_id = absint($profile_id);

		if ($profile_id <= 0) {
			return;
		}

		$sessions_table = WebsiteIntelligence::get_sessions_table_name();
		$events_table   = WebsiteIntelligence::get_events_table_name();
		$table          = self::get_table_name();

		$session_rows = $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM {$sessions_table} WHERE visitor_profile_id = %d ORDER BY last_seen_at DESC", $profile_id),
			ARRAY_A
		);

		$event_rows = $wpdb->get_results(
			$wpdb->prepare("SELECT event_name FROM {$events_table} WHERE visitor_profile_id = %d", $profile_id),
			ARRAY_A
		);

		$visited_tours        = [];
		$visited_destinations = [];
		$session_keys         = [];
		$latest_persona       = 'general_browser';
		$latest_stage         = 'browsing';
		$page_view_count      = 0;
		$high_intent_count    = 0;

		foreach ((array) $session_rows as $index => $row) {
			$session_keys[] = (string) ($row['session_key'] ?? '');
			$page_view_count += (int) ($row['page_views'] ?? 0);
			$visited_tours = array_values(array_unique(array_merge($visited_tours, self::decode_id_list((string) ($row['visited_tours'] ?? '')))));
			$visited_destinations = array_values(array_unique(array_merge($visited_destinations, self::decode_id_list((string) ($row['visited_destinations'] ?? '')))));

			if ((int) ($row['intent_score'] ?? 0) >= WebsiteIntelligence::get_config()['high_intent_score']) {
				$high_intent_count++;
			}

			if ($index === 0) {
				$latest_persona = (string) ($row['current_persona'] ?? 'general_browser');
				$latest_stage   = WebsiteIntelligence::get_stage((int) ($row['intent_score'] ?? 0));
			}
		}

		$event_counts = [
			'ai_open_count'         => 0,
			'ai_message_count'      => 0,
			'booking_click_count'   => 0,
			'booking_start_count'   => 0,
			'booking_complete_count' => 0,
			'whatsapp_click_count'  => 0,
		];

		foreach ((array) $event_rows as $row) {
			$event_name = (string) ($row['event_name'] ?? '');

			if ($event_name === 'chat_opened') {
				$event_counts['ai_open_count']++;
			} elseif ($event_name === 'chat_message') {
				$event_counts['ai_message_count']++;
			} elseif ($event_name === 'booking_click') {
				$event_counts['booking_click_count']++;
			} elseif ($event_name === 'booking_started') {
				$event_counts['booking_start_count']++;
			} elseif ($event_name === 'booking_completed') {
				$event_counts['booking_complete_count']++;
			} elseif ($event_name === 'whatsapp_click') {
				$event_counts['whatsapp_click_count']++;
			}
		}

		$lead_ids = self::get_linked_post_ids('lead', '_twostars_lead_visitor_profile_id', $profile_id);
		$booking_ids = self::get_linked_post_ids('booking', '_twostars_booking_visitor_profile_id', $profile_id);

		$wpdb->update(
			$table,
			[
				'latest_persona'        => $latest_persona,
				'latest_stage'          => $latest_stage,
				'session_count'         => count(array_filter($session_keys)),
				'page_view_count'       => $page_view_count,
				'tour_view_count'       => count($visited_tours),
				'destination_view_count' => count($visited_destinations),
				'ai_open_count'         => $event_counts['ai_open_count'],
				'ai_message_count'      => $event_counts['ai_message_count'],
				'booking_click_count'   => $event_counts['booking_click_count'],
				'booking_start_count'   => $event_counts['booking_start_count'],
				'booking_complete_count' => $event_counts['booking_complete_count'],
				'whatsapp_click_count'  => $event_counts['whatsapp_click_count'],
				'high_intent_count'     => $high_intent_count,
				'visited_tours'         => wp_json_encode($visited_tours),
				'visited_destinations'  => wp_json_encode($visited_destinations),
				'lead_ids'              => wp_json_encode($lead_ids),
				'booking_ids'           => wp_json_encode($booking_ids),
			],
			['id' => $profile_id]
		);
	}

	public static function count_profiles(): int
	{
		global $wpdb;

		return (int) $wpdb->get_var("SELECT COUNT(*) FROM " . self::get_table_name());
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_visitors(array $filters = []): array
	{
		global $wpdb;

		$table = self::get_table_name();
		$sql   = "SELECT * FROM {$table} WHERE 1=1";
		$args  = [];

		$country = sanitize_text_field((string) ($filters['country'] ?? ''));
		$city    = sanitize_text_field((string) ($filters['city'] ?? ''));
		$status  = sanitize_text_field((string) ($filters['status'] ?? ''));
		$type    = sanitize_text_field((string) ($filters['type'] ?? ''));
		$linked  = sanitize_text_field((string) ($filters['linked'] ?? ''));

		if ($country !== '') {
			$sql   .= " AND country_code = %s";
			$args[] = strtoupper($country);
		}

		if ($city !== '') {
			$sql   .= " AND city LIKE %s";
			$args[] = '%' . $wpdb->esc_like($city) . '%';
		}

		if ($status !== '') {
			$sql   .= " AND latest_stage = %s";
			$args[] = $status;
		}

		if ($type === 'returning') {
			$sql .= " AND is_returning = 1";
		} elseif ($type === 'new') {
			$sql .= " AND is_returning = 0";
		}

		if ($linked === 'yes') {
			$sql .= " AND ((lead_ids IS NOT NULL AND lead_ids != '[]' AND lead_ids != '') OR (booking_ids IS NOT NULL AND booking_ids != '[]' AND booking_ids != ''))";
		} elseif ($linked === 'no') {
			$sql .= " AND ((lead_ids IS NULL OR lead_ids = '[]' OR lead_ids = '') AND (booking_ids IS NULL OR booking_ids = '[]' OR booking_ids = ''))";
		}

		$sql .= " ORDER BY last_seen_at DESC LIMIT 300";

		$rows = $args !== [] ? $wpdb->get_results($wpdb->prepare($sql, ...$args), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);

		return is_array($rows) ? $rows : [];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_dashboard_summary(): array
	{
		global $wpdb;

		$table = self::get_table_name();

		$total      = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
		$returning  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_returning = 1");
		$ai_assists = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE ai_open_count > 0 OR ai_message_count > 0");
		$high_intent_without_booking = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE high_intent_count > 0 AND (booking_ids IS NULL OR booking_ids = '' OR booking_ids = '[]')");
		$mapped_profiles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE city_lat IS NOT NULL AND city_lng IS NOT NULL");
		$visitorapi_profiles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE location_source = 'visitorapi'");
		$fingerprint_profiles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE location_source = 'fingerprint'");
		$top_countries = $wpdb->get_results(
			"SELECT country_name, country_code, COUNT(*) AS total
			FROM {$table}
			WHERE country_name IS NOT NULL AND country_name != ''
			GROUP BY country_name, country_code
			ORDER BY total DESC
			LIMIT 6",
			ARRAY_A
		);
		$top_cities = $wpdb->get_results(
			"SELECT city, country_name, COUNT(*) AS total
			FROM {$table}
			WHERE city IS NOT NULL AND city != ''
			GROUP BY city, country_name
			ORDER BY total DESC
			LIMIT 6",
			ARRAY_A
		);

		return [
			'total'                     => $total,
			'returning'                 => $returning,
			'new'                       => max(0, $total - $returning),
			'ai_assisted'               => $ai_assists,
			'high_intent_without_booking' => $high_intent_without_booking,
			'mapped_profiles'           => $mapped_profiles,
			'visitorapi_profiles'       => $visitorapi_profiles,
			'fingerprint_profiles'      => $fingerprint_profiles,
			'top_countries'             => is_array($top_countries) ? $top_countries : [],
			'top_cities'                => is_array($top_cities) ? $top_cities : [],
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_map_points(array $filters = []): array
	{
		$rows   = self::get_visitors($filters);
		$points = [];

		foreach ($rows as $row) {
			$location = self::resolve_best_location_data($row);
			$lat = isset($location['lat']) ? (float) $location['lat'] : 0.0;
			$lng = isset($location['lng']) ? (float) $location['lng'] : 0.0;

			if (! self::has_valid_coordinates($lat, $lng)) {
				continue;
			}

			$points[] = [
				'id'          => (int) ($row['id'] ?? 0),
				'lat'         => $lat,
				'lng'         => $lng,
				'city'        => (string) ($location['city'] ?? ''),
				'countryName' => (string) ($location['country_name'] ?? ''),
				'countryCode' => (string) ($row['country_code'] ?? ''),
				'locationSource' => (string) ($location['source'] ?? ($row['location_source'] ?? '')),
				'lastSeenAt'  => (string) ($row['last_seen_at'] ?? ''),
				'latestStage' => (string) ($row['latest_stage'] ?? ''),
				'returning'   => ! empty($row['is_returning']),
				'detailUrl'   => admin_url('admin.php?page=twostars-manager-visitor-detail&visitor_id=' . absint($row['id'] ?? 0)),
			];
		}

		return $points;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_profile_detail(int $profile_id): ?array
	{
		global $wpdb;

		$profile = self::get_profile_by_id($profile_id);

		if (! $profile) {
			return null;
		}

		$sessions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT session_key, started_at, last_seen_at, first_url, last_url, intent_score, current_persona, page_views, visited_tours, visited_destinations
				FROM " . WebsiteIntelligence::get_sessions_table_name() . " WHERE visitor_profile_id = %d ORDER BY last_seen_at DESC",
				$profile_id
			),
			ARRAY_A
		);

		$events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT created_at, session_key, event_name, page_type, page_url, score_delta, payload
				FROM " . WebsiteIntelligence::get_events_table_name() . " WHERE visitor_profile_id = %d ORDER BY created_at DESC LIMIT 150",
				$profile_id
			),
			ARRAY_A
		);

		$lead_ids    = self::decode_id_list((string) ($profile['lead_ids'] ?? ''));
		$booking_ids = self::decode_id_list((string) ($profile['booking_ids'] ?? ''));

		return [
			'profile'   => $profile,
			'context'   => self::build_profile_context($profile),
			'sessions'  => is_array($sessions) ? $sessions : [],
			'events'    => is_array($events) ? $events : [],
			'conversations' => ConversationMemory::get_threads_for_context('', $profile_id, 20),
			'leads'     => self::get_linked_posts($lead_ids),
			'bookings'  => self::get_linked_posts($booking_ids),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function build_profile_context(array $profile): array
	{
		$location = self::resolve_best_location_data($profile);
		$visited_tours        = self::decode_id_list((string) ($profile['visited_tours'] ?? ''));
		$visited_destinations = self::decode_id_list((string) ($profile['visited_destinations'] ?? ''));
		$lead_ids             = self::decode_id_list((string) ($profile['lead_ids'] ?? ''));
		$booking_ids          = self::decode_id_list((string) ($profile['booking_ids'] ?? ''));

		return [
			'id'                   => (int) ($profile['id'] ?? 0),
			'fingerprint_visitor_id' => (string) ($profile['fingerprint_visitor_id'] ?? ''),
			'fingerprint_event_id' => (string) ($profile['fingerprint_event_id'] ?? ''),
			'country_code'         => (string) ($profile['country_code'] ?? ''),
			'country_name'         => (string) ($location['country_name'] ?? ($profile['country_name'] ?? '')),
			'location_source'      => (string) ($location['source'] ?? ($profile['location_source'] ?? '')),
			'region'               => (string) ($location['region'] ?? ($profile['region'] ?? '')),
			'city'                 => (string) ($location['city'] ?? ($profile['city'] ?? '')),
			'city_lat'             => isset($location['lat']) ? (float) $location['lat'] : 0.0,
			'city_lng'             => isset($location['lng']) ? (float) $location['lng'] : 0.0,
			'location_label'       => self::build_location_label((string) ($location['city'] ?? ($profile['city'] ?? '')), (string) ($location['country_name'] ?? ($profile['country_name'] ?? ''))),
			'currencies'           => self::decode_string_list((string) ($profile['currencies'] ?? '')),
			'languages'            => self::decode_string_list((string) ($profile['languages'] ?? '')),
			'browser'              => (string) ($profile['browser'] ?? ''),
			'browser_version'      => (string) ($profile['browser_version'] ?? ''),
			'device_brand'         => (string) ($profile['device_brand'] ?? ''),
			'device_model'         => (string) ($profile['device_model'] ?? ''),
			'device_family'        => (string) ($profile['device_family'] ?? ''),
			'os'                   => (string) ($profile['os'] ?? ''),
			'os_version'           => (string) ($profile['os_version'] ?? ''),
			'latest_persona'       => (string) ($profile['latest_persona'] ?? 'general_browser'),
			'latest_stage'         => (string) ($profile['latest_stage'] ?? 'browsing'),
			'session_count'        => (int) ($profile['session_count'] ?? 0),
			'page_view_count'      => (int) ($profile['page_view_count'] ?? 0),
			'tour_view_count'      => (int) ($profile['tour_view_count'] ?? 0),
			'destination_view_count' => (int) ($profile['destination_view_count'] ?? 0),
			'ai_open_count'        => (int) ($profile['ai_open_count'] ?? 0),
			'ai_message_count'     => (int) ($profile['ai_message_count'] ?? 0),
			'booking_click_count'  => (int) ($profile['booking_click_count'] ?? 0),
			'booking_start_count'  => (int) ($profile['booking_start_count'] ?? 0),
			'booking_complete_count' => (int) ($profile['booking_complete_count'] ?? 0),
			'whatsapp_click_count' => (int) ($profile['whatsapp_click_count'] ?? 0),
			'high_intent_count'    => (int) ($profile['high_intent_count'] ?? 0),
			'visited_tours'        => $visited_tours,
			'visited_destinations' => $visited_destinations,
			'lead_ids'             => $lead_ids,
			'booking_ids'          => $booking_ids,
			'is_returning'         => ! empty($profile['is_returning']),
			'last_seen_at'         => (string) ($profile['last_seen_at'] ?? ''),
			'first_seen_at'        => (string) ($profile['first_seen_at'] ?? ''),
			'internal_status'      => (string) ($profile['internal_status'] ?? 'new'),
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	private static function normalize_visitor_api_payload(array $payload): array
	{
		$coordinates = self::extract_coordinates_from_payload($payload);
		$lat = $coordinates['lat'];
		$lng = $coordinates['lng'];

		return [
			'location_source' => self::has_valid_coordinates($lat, $lng) || ! empty($payload['countryName']) ? 'visitorapi' : '',
			'country_code'    => strtoupper(sanitize_text_field((string) ($payload['countryCode'] ?? ''))),
			'country_name'    => sanitize_text_field((string) ($payload['countryName'] ?? '')),
			'region'          => sanitize_text_field((string) ($payload['region'] ?? '')),
			'city'            => sanitize_text_field((string) ($payload['city'] ?? '')),
			'city_lat'        => self::has_valid_coordinates($lat, $lng) ? $lat : null,
			'city_lng'        => self::has_valid_coordinates($lat, $lng) ? $lng : null,
			'currencies'      => self::normalize_string_array($payload['currencies'] ?? []),
			'languages'       => self::normalize_string_array($payload['languages'] ?? []),
			'browser'         => sanitize_text_field((string) ($payload['browser'] ?? '')),
			'browser_version' => sanitize_text_field((string) ($payload['browserVersion'] ?? '')),
			'device_brand'    => sanitize_text_field((string) ($payload['deviceBrand'] ?? '')),
			'device_model'    => sanitize_text_field((string) ($payload['deviceModel'] ?? '')),
			'device_family'   => sanitize_text_field((string) ($payload['deviceFamily'] ?? '')),
			'os'              => sanitize_text_field((string) ($payload['os'] ?? '')),
			'os_version'      => sanitize_text_field((string) ($payload['osVersion'] ?? '')),
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	private static function normalize_fingerprint_payload(array $payload): array
	{
		$ip_location = [];
		$raw_location = $payload['ipLocation'] ?? $payload['ip_location'] ?? null;

		if (is_array($raw_location)) {
			$ip_location = $raw_location;
		}

		$subdivision = '';
		if (! empty($ip_location['subdivisions'][0]['name'])) {
			$subdivision = sanitize_text_field((string) $ip_location['subdivisions'][0]['name']);
		}

		$country_code = '';
		$country_name = '';
		if (! empty($ip_location['country']) && is_array($ip_location['country'])) {
			$country_code = strtoupper(sanitize_text_field((string) ($ip_location['country']['code'] ?? '')));
			$country_name = sanitize_text_field((string) ($ip_location['country']['name'] ?? ''));
		}

		$city = '';
		if (! empty($ip_location['city']) && is_array($ip_location['city'])) {
			$city = sanitize_text_field((string) ($ip_location['city']['name'] ?? ''));
		}

		$lat = isset($ip_location['latitude']) && is_numeric($ip_location['latitude']) ? (float) $ip_location['latitude'] : 0.0;
		$lng = isset($ip_location['longitude']) && is_numeric($ip_location['longitude']) ? (float) $ip_location['longitude'] : 0.0;

		return [
			'location_source' => ($country_name !== '' || $lat !== 0.0 || $lng !== 0.0) ? 'fingerprint' : '',
			'country_code'    => $country_code,
			'country_name'    => $country_name,
			'region'          => $subdivision,
			'city'            => $city,
			'city_lat'        => $lat ?: null,
			'city_lng'        => $lng ?: null,
			'currencies'      => [],
			'languages'       => [],
			'browser'         => sanitize_text_field((string) ($payload['browserName'] ?? $payload['browser_name'] ?? '')),
			'browser_version' => sanitize_text_field((string) ($payload['browserVersion'] ?? $payload['browser_version'] ?? '')),
			'device_brand'    => '',
			'device_model'    => sanitize_text_field((string) ($payload['device'] ?? '')),
			'device_family'   => sanitize_text_field((string) ($payload['device'] ?? '')),
			'os'              => sanitize_text_field((string) ($payload['os'] ?? '')),
			'os_version'      => sanitize_text_field((string) ($payload['osVersion'] ?? $payload['os_version'] ?? '')),
		];
	}

	/**
	 * @param array<string, mixed> $fingerprint
	 * @param array<string, mixed> $visitorapi
	 * @return array<string, mixed>
	 */
	private static function merge_location_payloads(array $fingerprint, array $visitorapi): array
	{
		$visitorapi_has_coordinates = self::has_valid_coordinates((float) ($visitorapi['city_lat'] ?? 0), (float) ($visitorapi['city_lng'] ?? 0));
		$fingerprint_has_coordinates = self::has_valid_coordinates((float) ($fingerprint['city_lat'] ?? 0), (float) ($fingerprint['city_lng'] ?? 0));
		$visitorapi_has_location = $visitorapi_has_coordinates || (string) ($visitorapi['city'] ?? '') !== '' || (string) ($visitorapi['country_name'] ?? '') !== '';
		$use_visitorapi = $visitorapi_has_coordinates || ($visitorapi_has_location && ! $fingerprint_has_coordinates);

		$base = $use_visitorapi ? $visitorapi : $fingerprint;
		$fallback = $use_visitorapi ? $fingerprint : $visitorapi;

		return [
			'location_source' => (string) ($base['location_source'] ?? $fallback['location_source'] ?? ''),
			'country_code'    => (string) (($base['country_code'] ?? '') !== '' ? $base['country_code'] : ($fallback['country_code'] ?? '')),
			'country_name'    => (string) (($base['country_name'] ?? '') !== '' ? $base['country_name'] : ($fallback['country_name'] ?? '')),
			'region'          => (string) (($base['region'] ?? '') !== '' ? $base['region'] : ($fallback['region'] ?? '')),
			'city'            => (string) (($base['city'] ?? '') !== '' ? $base['city'] : ($fallback['city'] ?? '')),
			'city_lat'        => self::has_valid_coordinates((float) ($base['city_lat'] ?? 0), (float) ($base['city_lng'] ?? 0)) ? $base['city_lat'] : ($fallback['city_lat'] ?? null),
			'city_lng'        => self::has_valid_coordinates((float) ($base['city_lat'] ?? 0), (float) ($base['city_lng'] ?? 0)) ? $base['city_lng'] : ($fallback['city_lng'] ?? null),
			'currencies'      => $visitorapi['currencies'] ?? [],
			'languages'       => $visitorapi['languages'] ?? [],
			'browser'         => (string) (($fingerprint['browser'] ?? '') !== '' ? $fingerprint['browser'] : ($visitorapi['browser'] ?? '')),
			'browser_version' => (string) (($fingerprint['browser_version'] ?? '') !== '' ? $fingerprint['browser_version'] : ($visitorapi['browser_version'] ?? '')),
			'device_brand'    => (string) ($visitorapi['device_brand'] ?? ''),
			'device_model'    => (string) (($visitorapi['device_model'] ?? '') !== '' ? $visitorapi['device_model'] : ($fingerprint['device_model'] ?? '')),
			'device_family'   => (string) (($visitorapi['device_family'] ?? '') !== '' ? $visitorapi['device_family'] : ($fingerprint['device_family'] ?? '')),
			'os'              => (string) (($fingerprint['os'] ?? '') !== '' ? $fingerprint['os'] : ($visitorapi['os'] ?? '')),
			'os_version'      => (string) (($fingerprint['os_version'] ?? '') !== '' ? $fingerprint['os_version'] : ($visitorapi['os_version'] ?? '')),
		];
	}

	/**
	 * @param array<string, mixed> $profile
	 * @return array<string, mixed>
	 */
	private static function resolve_best_location_data(array $profile): array
	{
		$stored_lat = isset($profile['city_lat']) ? (float) $profile['city_lat'] : 0.0;
		$stored_lng = isset($profile['city_lng']) ? (float) $profile['city_lng'] : 0.0;
		$stored = [
			'source'       => (string) ($profile['location_source'] ?? ''),
			'city'         => (string) ($profile['city'] ?? ''),
			'country_name' => (string) ($profile['country_name'] ?? ''),
			'region'       => (string) ($profile['region'] ?? ''),
			'lat'          => self::has_valid_coordinates($stored_lat, $stored_lng) ? $stored_lat : 0.0,
			'lng'          => self::has_valid_coordinates($stored_lat, $stored_lng) ? $stored_lng : 0.0,
		];
		$raw_visitor_api = json_decode((string) ($profile['raw_visitor_api'] ?? ''), true);
		$visitorapi = is_array($raw_visitor_api) ? self::normalize_visitor_api_payload($raw_visitor_api) : [];
		$visitorapi_lat = isset($visitorapi['city_lat']) ? (float) $visitorapi['city_lat'] : 0.0;
		$visitorapi_lng = isset($visitorapi['city_lng']) ? (float) $visitorapi['city_lng'] : 0.0;

		if (self::has_valid_coordinates($visitorapi_lat, $visitorapi_lng)) {
			return [
				'source'       => 'visitorapi',
				'city'         => (string) ($visitorapi['city'] ?? $stored['city']),
				'country_name' => (string) ($visitorapi['country_name'] ?? $stored['country_name']),
				'region'       => (string) ($visitorapi['region'] ?? $stored['region']),
				'lat'          => $visitorapi_lat,
				'lng'          => $visitorapi_lng,
			];
		}

		return [
			'source'       => $stored['source'],
			'city'         => $stored['city'],
			'country_name' => $stored['country_name'],
			'region'       => $stored['region'],
			'lat'          => $stored['lat'],
			'lng'          => $stored['lng'],
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array{lat: float, lng: float}
	 */
	private static function extract_coordinates_from_payload(array $payload): array
	{
		$lat = 0.0;
		$lng = 0.0;
		$city_lat_long = sanitize_text_field((string) ($payload['cityLatLong'] ?? $payload['city_lat_long'] ?? ''));

		if ($city_lat_long !== '' && str_contains($city_lat_long, ',')) {
			[$raw_lat, $raw_lng] = array_map('trim', explode(',', $city_lat_long, 2));
			$lat = is_numeric($raw_lat) ? (float) $raw_lat : 0.0;
			$lng = is_numeric($raw_lng) ? (float) $raw_lng : 0.0;
		}

		if (! self::has_valid_coordinates($lat, $lng)) {
			$raw_lat = $payload['cityLatitude'] ?? $payload['city_latitude'] ?? $payload['latitude'] ?? $payload['lat'] ?? null;
			$raw_lng = $payload['cityLongitude'] ?? $payload['city_longitude'] ?? $payload['longitude'] ?? $payload['lng'] ?? null;
			$lat = is_numeric($raw_lat) ? (float) $raw_lat : 0.0;
			$lng = is_numeric($raw_lng) ? (float) $raw_lng : 0.0;
		}

		return [
			'lat' => self::has_valid_coordinates($lat, $lng) ? $lat : 0.0,
			'lng' => self::has_valid_coordinates($lat, $lng) ? $lng : 0.0,
		];
	}

	private static function has_valid_coordinates(float $lat, float $lng): bool
	{
		return $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0 && ! ($lat === 0.0 && $lng === 0.0);
	}

	private static function build_location_label(string $city, string $country_name): string
	{
		return trim($city . ', ' . $country_name, ', ');
	}

	/**
	 * @param mixed $value
	 * @return array<int, string>
	 */
	private static function normalize_string_array(mixed $value): array
	{
		if (! is_array($value)) {
			return [];
		}

		return array_values(
			array_filter(
				array_map(
					static fn($item): string => sanitize_text_field((string) $item),
					$value
				),
				static fn(string $item): bool => $item !== ''
			)
		);
	}

	/**
	 * @return array<int, string>
	 */
	private static function decode_string_list(string $value): array
	{
		if ($value === '') {
			return [];
		}

		$decoded = json_decode($value, true);

		if (! is_array($decoded)) {
			return [];
		}

		return array_values(
			array_filter(
				array_map(
					static fn($item): string => is_scalar($item) ? (string) $item : '',
					$decoded
				),
				static fn(string $item): bool => $item !== ''
			)
		);
	}

	/**
	 * @return array<int, int>
	 */
	private static function decode_id_list(string $value): array
	{
		if ($value === '') {
			return [];
		}

		$decoded = json_decode($value, true);

		if (! is_array($decoded)) {
			return [];
		}

		return array_values(
			array_filter(
				array_map('absint', $decoded),
				static fn(int $id): bool => $id > 0
			)
		);
	}

	private static function normalize_email(string $email): string
	{
		return strtolower(trim(sanitize_email($email)));
	}

	private static function normalize_phone(string $phone): string
	{
		$phone = preg_replace('/[^0-9+]/', '', trim($phone));

		return is_string($phone) ? $phone : '';
	}

	/**
	 * @return array<int, int>
	 */
	private static function find_matching_record_ids(string $post_type, string $email_normalized, string $phone_normalized): array
	{
		$meta_query = ['relation' => 'OR'];

		if ($email_normalized !== '') {
			$meta_query[] = [
				'key'   => $post_type === 'lead' ? '_twostars_lead_email_normalized' : '_twostars_booking_email_normalized',
				'value' => $email_normalized,
			];
			$meta_query[] = [
				'key'   => $post_type === 'lead' ? '_twostars_lead_email' : '_twostars_booking_email',
				'value' => $email_normalized,
			];
		}

		if ($phone_normalized !== '') {
			$meta_query[] = [
				'key'   => $post_type === 'lead' ? '_twostars_lead_phone_normalized' : '_twostars_booking_phone_normalized',
				'value' => $phone_normalized,
			];
			$meta_query[] = [
				'key'   => $post_type === 'lead' ? '_twostars_lead_phone' : '_twostars_booking_phone',
				'value' => $phone_normalized,
			];
		}

		if (count($meta_query) === 1) {
			return [];
		}

		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => ['private', 'publish', 'draft'],
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'meta_query'     => $meta_query,
			]
		);

		return array_values(array_filter(array_map('absint', $posts)));
	}

	/**
	 * @return array<int, int>
	 */
	private static function get_linked_post_ids(string $post_type, string $meta_key, int $profile_id): array
	{
		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => ['private', 'publish', 'draft'],
				'posts_per_page' => 250,
				'fields'         => 'ids',
				'meta_key'       => $meta_key,
				'meta_value'     => $profile_id,
			]
		);

		return array_values(array_filter(array_map('absint', $posts)));
	}

	/**
	 * @param array<int, int> $post_ids
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_linked_posts(array $post_ids): array
	{
		if ($post_ids === []) {
			return [];
		}

		$posts = get_posts(
			[
				'post_type'      => ['lead', 'booking'],
				'post_status'    => ['private', 'publish', 'draft'],
				'post__in'       => $post_ids,
				'posts_per_page' => count($post_ids),
				'orderby'        => 'post__in',
			]
		);

		return array_map(
			static function ($post): array {
				return [
					'id'        => (int) $post->ID,
					'post_type' => (string) $post->post_type,
					'title'     => (string) $post->post_title,
					'edit_url'  => admin_url('admin.php?page=' . ($post->post_type === 'lead' ? 'twostars-manager-lead-edit' : 'twostars-manager-booking-edit') . '&post_id=' . (int) $post->ID),
				];
			},
			$posts
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function get_settings(): array
	{
		$settings = get_option('twostars_platform_settings', []);
		$settings = is_array($settings) ? $settings : [];

		return array_merge(
			[
				'visitor_tracking_enabled' => true,
				'visitor_personalization_enabled' => true,
				'visitorapi_project_id' => 'bkqnE1FI519hOxf2VIa0',
				'fingerprint_public_key' => '16ovVe3T8gJzgsEJaXaS',
				'fingerprint_region' => 'ap',
				'fingerprint_agent_url' => 'https://fpjscdn.net/v4/16ovVe3T8gJzgsEJaXaS',
				'visitor_retention_days' => 180,
				'visitor_consent_title' => __('Personalized trip planning', 'twostars-platform'),
				'visitor_consent_description' => __('Allow Two Stars to remember your planning journey, tailor route suggestions, and show the right guidance for your location and device.', 'twostars-platform'),
				'visitor_consent_accept_label' => __('Allow personalization', 'twostars-platform'),
				'visitor_consent_decline_label' => __('No thanks', 'twostars-platform'),
			],
			$settings
		);
	}
}
