<?php
declare(strict_types=1);

namespace TwoStars\Platform\CRM;

use TwoStars\Platform\Support\Service;

final class CoreCRM implements Service
{
	private const SCHEMA_OPTION = 'twostars_crm_core_schema_version';
	private const SCHEMA_VERSION = '1';
	private const BACKFILL_OPTION = 'twostars_crm_core_backfill_version';

	public function register(): void
	{
		add_action('init', [self::class, 'maybe_install']);
		add_action('init', [self::class, 'maybe_backfill'], 20);
		add_action('trashed_post', [self::class, 'handle_record_removed']);
		add_action('before_delete_post', [self::class, 'handle_record_removed']);
		add_action('untrashed_post', [self::class, 'handle_record_restored']);
	}

	public static function maybe_install(): void
	{
		if (get_option(self::SCHEMA_OPTION) === self::SCHEMA_VERSION) {
			return;
		}

		self::install();
		update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION);
	}

	public static function maybe_backfill(): void
	{
		if (get_option(self::BACKFILL_OPTION) === self::SCHEMA_VERSION) {
			return;
		}

		self::backfill_existing_records();
		update_option(self::BACKFILL_OPTION, self::SCHEMA_VERSION);
	}

	public static function install(): void
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate   = $wpdb->get_charset_collate();
		$contacts_table    = self::get_contacts_table_name();
		$opportunities_tab = self::get_opportunities_table_name();

		dbDelta(
			"CREATE TABLE {$contacts_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				full_name varchar(191) NULL,
				primary_email varchar(191) NULL,
				normalized_email varchar(191) NULL,
				primary_phone varchar(60) NULL,
				normalized_phone varchar(60) NULL,
				country varchar(100) NULL,
				visitor_profile_id bigint(20) unsigned NOT NULL DEFAULT 0,
				latest_source varchar(40) NULL,
				lead_count int unsigned NOT NULL DEFAULT 0,
				booking_count int unsigned NOT NULL DEFAULT 0,
				first_recorded_at datetime NOT NULL,
				last_recorded_at datetime NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY visitor_profile_id (visitor_profile_id),
				KEY normalized_email (normalized_email),
				KEY normalized_phone (normalized_phone),
				KEY last_recorded_at (last_recorded_at)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$opportunities_tab} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				contact_id bigint(20) unsigned NOT NULL,
				source_type varchar(20) NOT NULL,
				source_record_id bigint(20) unsigned NOT NULL,
				lead_id bigint(20) unsigned NOT NULL DEFAULT 0,
				booking_id bigint(20) unsigned NOT NULL DEFAULT 0,
				visitor_profile_id bigint(20) unsigned NOT NULL DEFAULT 0,
				linked_tour_id bigint(20) unsigned NOT NULL DEFAULT 0,
				source_channel varchar(40) NULL,
				pipeline_stage varchar(40) NOT NULL DEFAULT 'new',
				priority varchar(20) NOT NULL DEFAULT 'medium',
				assignee varchar(120) NULL,
				guest_count int unsigned NOT NULL DEFAULT 0,
				travel_dates varchar(191) NULL,
				origin_url text NULL,
				latest_message longtext NULL,
				internal_summary longtext NULL,
				next_action longtext NULL,
				next_due_date date NULL,
				last_contacted_at datetime NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY source_record (source_type, source_record_id),
				KEY contact_id (contact_id),
				KEY lead_id (lead_id),
				KEY booking_id (booking_id),
				KEY visitor_profile_id (visitor_profile_id),
				KEY linked_tour_id (linked_tour_id),
				KEY pipeline_stage (pipeline_stage),
				KEY next_due_date (next_due_date)
			) {$charset_collate};"
		);
	}

	public static function get_contacts_table_name(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'twostars_crm_contacts';
	}

	public static function get_opportunities_table_name(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'twostars_crm_opportunities';
	}

	public static function sync_lead(int $lead_id): void
	{
		if ($lead_id <= 0 || get_post_type($lead_id) !== 'lead') {
			return;
		}

		$post = get_post($lead_id);

		if (! $post instanceof \WP_Post) {
			return;
		}

		$visitor_profile_id = (int) get_post_meta($lead_id, '_twostars_lead_visitor_profile_id', true);
		$email              = (string) get_post_meta($lead_id, '_twostars_lead_email', true);
		$phone              = (string) get_post_meta($lead_id, '_twostars_lead_phone', true);
		$contact_id         = self::upsert_contact(
			[
				'full_name'          => (string) get_post_meta($lead_id, '_twostars_lead_full_name', true),
				'email'              => $email,
				'phone'              => $phone,
				'country'            => (string) get_post_meta($lead_id, '_twostars_lead_country', true),
				'visitor_profile_id' => $visitor_profile_id,
				'source'             => (string) get_post_meta($lead_id, '_twostars_lead_source', true),
				'recorded_at'        => (string) $post->post_date,
			]
		);

		if ($contact_id <= 0) {
			return;
		}

		$existing = self::get_opportunity_by_source('lead', $lead_id);
		$booking_id = (int) ($existing['booking_id'] ?? 0);

		self::upsert_opportunity(
			$existing,
			[
				'contact_id'         => $contact_id,
				'source_type'        => 'lead',
				'source_record_id'   => $lead_id,
				'lead_id'            => $lead_id,
				'booking_id'         => $booking_id,
				'visitor_profile_id' => $visitor_profile_id,
				'linked_tour_id'     => (int) get_post_meta($lead_id, '_twostars_lead_tour_interest', true),
				'source_channel'     => (string) get_post_meta($lead_id, '_twostars_lead_source', true),
				'pipeline_stage'     => $booking_id > 0 ? (string) ($existing['pipeline_stage'] ?? 'booking_pending') : self::normalize_stage((string) get_post_meta($lead_id, '_twostars_lead_status', true), 'lead'),
				'priority'           => self::normalize_priority((string) get_post_meta($lead_id, '_twostars_lead_priority', true)),
				'assignee'           => (string) get_post_meta($lead_id, '_twostars_lead_assigned_to', true),
				'guest_count'        => (int) get_post_meta($lead_id, '_twostars_lead_guest_count', true),
				'travel_dates'       => (string) get_post_meta($lead_id, '_twostars_lead_travel_dates', true),
				'origin_url'         => (string) get_post_meta($lead_id, '_twostars_lead_origin_url', true),
				'latest_message'     => (string) get_post_meta($lead_id, '_twostars_lead_message', true),
				'internal_summary'   => (string) get_post_meta($lead_id, '_twostars_lead_internal_notes', true),
				'next_action'        => (string) get_post_meta($lead_id, '_twostars_lead_next_action', true),
				'next_due_date'      => self::normalize_date((string) get_post_meta($lead_id, '_twostars_lead_follow_up_date', true)),
				'last_contacted_at'  => self::normalize_datetime((string) get_post_meta($lead_id, '_twostars_lead_last_contacted_at', true)),
				'created_at'         => (string) $post->post_date,
			]
		);

		self::refresh_contact_rollups($contact_id);
	}

	public static function sync_booking(int $booking_id): void
	{
		if ($booking_id <= 0 || get_post_type($booking_id) !== 'booking') {
			return;
		}

		$post = get_post($booking_id);

		if (! $post instanceof \WP_Post) {
			return;
		}

		$visitor_profile_id = (int) get_post_meta($booking_id, '_twostars_booking_visitor_profile_id', true);
		$email              = (string) get_post_meta($booking_id, '_twostars_booking_email', true);
		$phone              = (string) get_post_meta($booking_id, '_twostars_booking_phone', true);
		$contact_id         = self::upsert_contact(
			[
				'full_name'          => (string) get_post_meta($booking_id, '_twostars_booking_full_name', true),
				'email'              => $email,
				'phone'              => $phone,
				'country'            => (string) get_post_meta($booking_id, '_twostars_booking_country', true),
				'visitor_profile_id' => $visitor_profile_id,
				'source'             => 'booking',
				'recorded_at'        => (string) $post->post_date,
			]
		);

		if ($contact_id <= 0) {
			return;
		}

		$linked_lead_id = (int) get_post_meta($booking_id, '_twostars_booking_lead_id', true);
		$existing       = $linked_lead_id > 0 ? self::get_opportunity_by_source('lead', $linked_lead_id) : [];

		if ($existing === []) {
			$existing = self::get_opportunity_by_source('booking', $booking_id);
		}

		self::upsert_opportunity(
			$existing,
			[
				'contact_id'         => $contact_id,
				'source_type'        => $existing === [] ? 'booking' : (string) ($existing['source_type'] ?? 'booking'),
				'source_record_id'   => $existing === [] ? $booking_id : (int) ($existing['source_record_id'] ?? $booking_id),
				'lead_id'            => $linked_lead_id,
				'booking_id'         => $booking_id,
				'visitor_profile_id' => $visitor_profile_id,
				'linked_tour_id'     => (int) get_post_meta($booking_id, '_twostars_booking_tour_id', true),
				'source_channel'     => (string) ($existing['source_channel'] ?? 'website'),
				'pipeline_stage'     => self::normalize_stage((string) get_post_meta($booking_id, '_twostars_booking_status', true), 'booking'),
				'priority'           => self::normalize_priority((string) get_post_meta($booking_id, '_twostars_booking_priority', true)),
				'assignee'           => (string) get_post_meta($booking_id, '_twostars_booking_assigned_to', true),
				'guest_count'        => (int) get_post_meta($booking_id, '_twostars_booking_guest_count', true),
				'travel_dates'       => trim((string) get_post_meta($booking_id, '_twostars_booking_date', true) . ' ' . (string) get_post_meta($booking_id, '_twostars_booking_time', true)),
				'origin_url'         => (string) get_post_meta($booking_id, '_twostars_booking_origin_url', true),
				'latest_message'     => (string) get_post_meta($booking_id, '_twostars_booking_notes', true),
				'internal_summary'   => (string) get_post_meta($booking_id, '_twostars_booking_internal_notes', true),
				'next_action'        => (string) get_post_meta($booking_id, '_twostars_booking_next_action', true),
				'next_due_date'      => self::normalize_date((string) get_post_meta($booking_id, '_twostars_booking_confirmation_deadline', true)),
				'last_contacted_at'  => self::normalize_datetime((string) get_post_meta($booking_id, '_twostars_booking_last_contacted_at', true)),
				'created_at'         => (string) $post->post_date,
			]
		);

		self::refresh_contact_rollups($contact_id);
	}

	/**
	 * @return array<string, int>
	 */
	public static function get_dashboard_summary(): array
	{
		global $wpdb;

		$contacts_table      = self::get_contacts_table_name();
		$opportunities_table = self::get_opportunities_table_name();
		$today               = wp_date('Y-m-d');

		return [
			'contacts'           => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$contacts_table}"),
			'opportunities'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$opportunities_table}"),
			'booking_pipeline'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$opportunities_table} WHERE booking_id > 0 AND pipeline_stage NOT IN ('booking_cancelled', 'booking_completed')"),
			'overdue'            => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$opportunities_table} WHERE next_due_date IS NOT NULL AND next_due_date < %s AND pipeline_stage NOT IN ('won', 'lost', 'booking_cancelled', 'booking_completed')", $today)),
			'connected_visitors' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$contacts_table} WHERE visitor_profile_id > 0"),
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_recent_opportunities(int $limit = 8): array
	{
		global $wpdb;

		$limit               = max(1, min(25, $limit));
		$contacts_table      = self::get_contacts_table_name();
		$opportunities_table = self::get_opportunities_table_name();
		$sql                 = $wpdb->prepare(
			"SELECT opportunities.*, contacts.full_name AS contact_name, contacts.primary_email, contacts.primary_phone
			FROM {$opportunities_table} AS opportunities
			LEFT JOIN {$contacts_table} AS contacts ON contacts.id = opportunities.contact_id
			ORDER BY opportunities.updated_at DESC
			LIMIT %d",
			$limit
		);
		$rows                = $wpdb->get_results($sql, ARRAY_A);

		return is_array($rows) ? $rows : [];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_recent_contacts(int $limit = 8): array
	{
		global $wpdb;

		$limit          = max(1, min(25, $limit));
		$contacts_table = self::get_contacts_table_name();
		$sql            = $wpdb->prepare(
			"SELECT * FROM {$contacts_table} ORDER BY last_recorded_at DESC LIMIT %d",
			$limit
		);
		$rows           = $wpdb->get_results($sql, ARRAY_A);

		return is_array($rows) ? $rows : [];
	}

	/**
	 * @param array<int, string> $stages
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_opportunities_by_stages(array $stages, int $limit = 6): array
	{
		global $wpdb;

		$limit               = max(1, min(24, $limit));
		$contacts_table      = self::get_contacts_table_name();
		$opportunities_table = self::get_opportunities_table_name();
		$sanitized_stages    = self::sanitize_stage_list($stages);

		if ($sanitized_stages === []) {
			return [];
		}

		$quoted = "'" . implode("','", array_map('esc_sql', $sanitized_stages)) . "'";
		$sql    = $wpdb->prepare(
			"SELECT opportunities.*, contacts.full_name AS contact_name, contacts.primary_email, contacts.primary_phone, contacts.country
			FROM {$opportunities_table} AS opportunities
			LEFT JOIN {$contacts_table} AS contacts ON contacts.id = opportunities.contact_id
			WHERE opportunities.pipeline_stage IN ({$quoted})
			ORDER BY
				CASE
					WHEN opportunities.next_due_date IS NOT NULL AND opportunities.next_due_date < %s THEN 0
					ELSE 1
				END ASC,
				opportunities.updated_at DESC
			LIMIT %d",
			wp_date('Y-m-d'),
			$limit
		);
		$rows   = $wpdb->get_results($sql, ARRAY_A);

		return is_array($rows) ? $rows : [];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_priority_queue(int $limit = 6): array
	{
		global $wpdb;

		$limit               = max(1, min(24, $limit));
		$contacts_table      = self::get_contacts_table_name();
		$opportunities_table = self::get_opportunities_table_name();
		$open_stages         = "'" . implode("','", array_map('esc_sql', self::get_open_stage_list())) . "'";
		$sql                 = $wpdb->prepare(
			"SELECT opportunities.*, contacts.full_name AS contact_name, contacts.primary_email, contacts.primary_phone, contacts.country
			FROM {$opportunities_table} AS opportunities
			LEFT JOIN {$contacts_table} AS contacts ON contacts.id = opportunities.contact_id
			WHERE opportunities.pipeline_stage IN ({$open_stages})
			ORDER BY
				CASE
					WHEN opportunities.next_due_date IS NOT NULL AND opportunities.next_due_date < %s THEN 0
					WHEN opportunities.priority = 'urgent' THEN 1
					WHEN opportunities.priority = 'high' THEN 2
					ELSE 3
				END ASC,
				COALESCE(opportunities.next_due_date, '9999-12-31') ASC,
				opportunities.updated_at DESC
			LIMIT %d",
			wp_date('Y-m-d'),
			$limit
		);
		$rows                = $wpdb->get_results($sql, ARRAY_A);

		return is_array($rows) ? $rows : [];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_top_markets(int $limit = 6): array
	{
		global $wpdb;

		$limit          = max(1, min(12, $limit));
		$contacts_table = self::get_contacts_table_name();
		$sql            = $wpdb->prepare(
			"SELECT country, COUNT(*) AS total
			FROM {$contacts_table}
			WHERE country IS NOT NULL AND country <> ''
			GROUP BY country
			ORDER BY total DESC, country ASC
			LIMIT %d",
			$limit
		);
		$rows           = $wpdb->get_results($sql, ARRAY_A);

		return is_array($rows) ? $rows : [];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_pipeline_breakdown(): array
	{
		global $wpdb;

		$opportunities_table = self::get_opportunities_table_name();
		$rows                = $wpdb->get_results(
			"SELECT pipeline_stage, COUNT(*) AS total
			FROM {$opportunities_table}
			GROUP BY pipeline_stage
			ORDER BY total DESC, pipeline_stage ASC
			LIMIT 8",
			ARRAY_A
		);

		return is_array($rows) ? $rows : [];
	}

	public static function get_stage_label(string $stage): string
	{
		$stage = trim($stage);

		if ($stage === '') {
			return __('New', 'twostars-platform');
		}

		$labels = [
			'new'                 => __('New', 'twostars-platform'),
			'contacted'           => __('Contacted', 'twostars-platform'),
			'follow_up_scheduled' => __('Follow Up Scheduled', 'twostars-platform'),
			'qualified'           => __('Qualified', 'twostars-platform'),
			'quote_sent'          => __('Quote Sent', 'twostars-platform'),
			'booking_pending'     => __('Booking Pending', 'twostars-platform'),
			'won'                 => __('Won', 'twostars-platform'),
			'lost'                => __('Lost', 'twostars-platform'),
			'booking_contacted'   => __('Booking Contacted', 'twostars-platform'),
			'booking_confirmed'   => __('Booking Confirmed', 'twostars-platform'),
			'booking_completed'   => __('Booking Completed', 'twostars-platform'),
			'booking_cancelled'   => __('Booking Cancelled', 'twostars-platform'),
		];

		return (string) ($labels[$stage] ?? ucwords(str_replace('_', ' ', $stage)));
	}

	public static function handle_record_removed(int $post_id): void
	{
		$post_type = get_post_type($post_id);

		if (! in_array($post_type, ['lead', 'booking'], true)) {
			return;
		}

		self::detach_record((string) $post_type, $post_id);
	}

	public static function handle_record_restored(int $post_id): void
	{
		$post_type = get_post_type($post_id);

		if ($post_type === 'lead') {
			self::sync_lead($post_id);

			return;
		}

		if ($post_type === 'booking') {
			self::sync_booking($post_id);
		}
	}

	private static function backfill_existing_records(): void
	{
		$lead_ids = get_posts(
			[
				'post_type'      => 'lead',
				'post_status'    => ['private', 'publish', 'draft'],
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'ASC',
			]
		);

		foreach ($lead_ids as $lead_id) {
			self::sync_lead((int) $lead_id);
		}

		$booking_ids = get_posts(
			[
				'post_type'      => 'booking',
				'post_status'    => ['private', 'publish', 'draft'],
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'ASC',
			]
		);

		foreach ($booking_ids as $booking_id) {
			self::sync_booking((int) $booking_id);
		}
	}

	private static function detach_record(string $post_type, int $post_id): void
	{
		global $wpdb;

		if ($post_id <= 0) {
			return;
		}

		$opportunities_table = self::get_opportunities_table_name();
		$column              = $post_type === 'booking' ? 'booking_id' : 'lead_id';
		$rows                = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$opportunities_table} WHERE {$column} = %d",
				$post_id
			),
			ARRAY_A
		);

		if (! is_array($rows) || $rows === []) {
			return;
		}

		foreach ($rows as $row) {
			$contact_id = (int) ($row['contact_id'] ?? 0);

			if ($post_type === 'lead') {
				self::detach_lead_from_opportunity($row);
			} else {
				self::detach_booking_from_opportunity($row);
			}

			self::refresh_contact_rollups($contact_id);
			self::delete_contact_if_orphaned($contact_id);
		}
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function detach_lead_from_opportunity(array $row): void
	{
		global $wpdb;

		$opportunities_table = self::get_opportunities_table_name();
		$booking_id          = (int) ($row['booking_id'] ?? 0);
		$current_id          = (int) ($row['id'] ?? 0);

		if ($booking_id > 0 && self::record_is_active('booking', $booking_id)) {
			$existing_booking_row = self::get_opportunity_by_source('booking', $booking_id);

			if ($existing_booking_row !== [] && (int) ($existing_booking_row['id'] ?? 0) !== $current_id) {
				$wpdb->delete($opportunities_table, ['id' => $current_id]);
				self::sync_booking($booking_id);

				return;
			}

			$wpdb->update(
				$opportunities_table,
				[
					'source_type'      => 'booking',
					'source_record_id' => $booking_id,
					'lead_id'          => 0,
					'pipeline_stage'   => self::normalize_stage((string) get_post_meta($booking_id, '_twostars_booking_status', true), 'booking'),
					'source_channel'   => self::nullable_text((string) ($row['source_channel'] ?: 'website')),
					'updated_at'       => current_time('mysql'),
				],
				['id' => $current_id]
			);

			return;
		}

		$wpdb->delete($opportunities_table, ['id' => $current_id]);
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function detach_booking_from_opportunity(array $row): void
	{
		global $wpdb;

		$opportunities_table = self::get_opportunities_table_name();
		$lead_id             = (int) ($row['lead_id'] ?? 0);
		$current_id          = (int) ($row['id'] ?? 0);

		if ($lead_id > 0 && self::record_is_active('lead', $lead_id)) {
			$existing_lead_row = self::get_opportunity_by_source('lead', $lead_id);

			if ($existing_lead_row !== [] && (int) ($existing_lead_row['id'] ?? 0) !== $current_id) {
				$wpdb->delete($opportunities_table, ['id' => $current_id]);
				self::sync_lead($lead_id);

				return;
			}

			$wpdb->update(
				$opportunities_table,
				[
					'source_type'      => 'lead',
					'source_record_id' => $lead_id,
					'booking_id'       => 0,
					'pipeline_stage'   => self::normalize_stage((string) get_post_meta($lead_id, '_twostars_lead_status', true), 'lead'),
					'source_channel'   => self::nullable_text((string) get_post_meta($lead_id, '_twostars_lead_source', true)),
					'updated_at'       => current_time('mysql'),
				],
				['id' => $current_id]
			);

			return;
		}

		$wpdb->delete($opportunities_table, ['id' => $current_id]);
	}

	private static function upsert_contact(array $data): int
	{
		global $wpdb;

		$contacts_table = self::get_contacts_table_name();
		$email          = self::normalize_email((string) ($data['email'] ?? ''));
		$phone          = self::normalize_phone((string) ($data['phone'] ?? ''));
		$visitor_id     = (int) ($data['visitor_profile_id'] ?? 0);
		$recorded_at    = self::normalize_datetime((string) ($data['recorded_at'] ?? '')) ?: current_time('mysql');
		$existing       = self::find_contact($visitor_id, $email, $phone);

		$record = [
			'full_name'          => self::nullable_text((string) ($data['full_name'] ?? '')),
			'primary_email'      => self::nullable_text((string) ($data['email'] ?? '')),
			'normalized_email'   => self::nullable_text($email),
			'primary_phone'      => self::nullable_text((string) ($data['phone'] ?? '')),
			'normalized_phone'   => self::nullable_text($phone),
			'country'            => self::nullable_text((string) ($data['country'] ?? '')),
			'visitor_profile_id' => $visitor_id,
			'latest_source'      => self::nullable_text((string) ($data['source'] ?? '')),
			'last_recorded_at'   => $recorded_at,
			'updated_at'         => current_time('mysql'),
		];

		if ($existing !== []) {
			$record['full_name']        = $record['full_name'] ?: (string) ($existing['full_name'] ?? '');
			$record['primary_email']    = $record['primary_email'] ?: (string) ($existing['primary_email'] ?? '');
			$record['normalized_email'] = $record['normalized_email'] ?: (string) ($existing['normalized_email'] ?? '');
			$record['primary_phone']    = $record['primary_phone'] ?: (string) ($existing['primary_phone'] ?? '');
			$record['normalized_phone'] = $record['normalized_phone'] ?: (string) ($existing['normalized_phone'] ?? '');
			$record['country']          = $record['country'] ?: (string) ($existing['country'] ?? '');
			$record['visitor_profile_id'] = $visitor_id > 0 ? $visitor_id : (int) ($existing['visitor_profile_id'] ?? 0);
			$record['latest_source']      = $record['latest_source'] ?: (string) ($existing['latest_source'] ?? '');
			$wpdb->update($contacts_table, $record, ['id' => (int) $existing['id']]);

			return (int) $existing['id'];
		}

		$record['lead_count']        = 0;
		$record['booking_count']     = 0;
		$record['first_recorded_at'] = $recorded_at;
		$record['created_at']        = current_time('mysql');

		$inserted = $wpdb->insert($contacts_table, $record);

		return $inserted === false ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * @param array<string, mixed> $existing
	 * @param array<string, mixed> $record
	 */
	private static function upsert_opportunity(array $existing, array $record): void
	{
		global $wpdb;

		$opportunities_table = self::get_opportunities_table_name();
		$payload             = [
			'contact_id'         => (int) ($record['contact_id'] ?? 0),
			'source_type'        => (string) ($record['source_type'] ?? 'lead'),
			'source_record_id'   => (int) ($record['source_record_id'] ?? 0),
			'lead_id'            => (int) ($record['lead_id'] ?? 0),
			'booking_id'         => (int) ($record['booking_id'] ?? 0),
			'visitor_profile_id' => (int) ($record['visitor_profile_id'] ?? 0),
			'linked_tour_id'     => (int) ($record['linked_tour_id'] ?? 0),
			'source_channel'     => self::nullable_text((string) ($record['source_channel'] ?? '')),
			'pipeline_stage'     => self::normalize_stage((string) ($record['pipeline_stage'] ?? 'new'), 'lead'),
			'priority'           => self::normalize_priority((string) ($record['priority'] ?? 'medium')),
			'assignee'           => self::nullable_text((string) ($record['assignee'] ?? '')),
			'guest_count'        => max(0, (int) ($record['guest_count'] ?? 0)),
			'travel_dates'       => self::nullable_text((string) ($record['travel_dates'] ?? '')),
			'origin_url'         => self::nullable_text((string) ($record['origin_url'] ?? '')),
			'latest_message'     => self::nullable_text((string) ($record['latest_message'] ?? '')),
			'internal_summary'   => self::nullable_text((string) ($record['internal_summary'] ?? '')),
			'next_action'        => self::nullable_text((string) ($record['next_action'] ?? '')),
			'next_due_date'      => self::normalize_date((string) ($record['next_due_date'] ?? '')),
			'last_contacted_at'  => self::normalize_datetime((string) ($record['last_contacted_at'] ?? '')),
			'updated_at'         => current_time('mysql'),
		];

		if ($existing !== []) {
			$payload['lead_id']      = $payload['lead_id'] > 0 ? $payload['lead_id'] : (int) ($existing['lead_id'] ?? 0);
			$payload['booking_id']   = $payload['booking_id'] > 0 ? $payload['booking_id'] : (int) ($existing['booking_id'] ?? 0);
			$payload['source_type']  = (string) ($existing['source_type'] ?? $payload['source_type']);
			$payload['source_record_id'] = (int) ($existing['source_record_id'] ?? $payload['source_record_id']);
			$wpdb->update($opportunities_table, $payload, ['id' => (int) $existing['id']]);

			return;
		}

		$payload['created_at'] = self::normalize_datetime((string) ($record['created_at'] ?? '')) ?: current_time('mysql');
		$wpdb->insert($opportunities_table, $payload);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function find_contact(int $visitor_profile_id, string $normalized_email, string $normalized_phone): array
	{
		global $wpdb;

		$contacts_table = self::get_contacts_table_name();

		if ($visitor_profile_id > 0) {
			$row = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM {$contacts_table} WHERE visitor_profile_id = %d ORDER BY id ASC LIMIT 1", $visitor_profile_id),
				ARRAY_A
			);

			if (is_array($row)) {
				return $row;
			}
		}

		if ($normalized_email !== '') {
			$row = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM {$contacts_table} WHERE normalized_email = %s ORDER BY id ASC LIMIT 1", $normalized_email),
				ARRAY_A
			);

			if (is_array($row)) {
				return $row;
			}
		}

		if ($normalized_phone !== '') {
			$row = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM {$contacts_table} WHERE normalized_phone = %s ORDER BY id ASC LIMIT 1", $normalized_phone),
				ARRAY_A
			);

			if (is_array($row)) {
				return $row;
			}
		}

		return [];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function get_opportunity_by_source(string $source_type, int $source_record_id): array
	{
		global $wpdb;

		if ($source_record_id <= 0) {
			return [];
		}

		$table = self::get_opportunities_table_name();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE source_type = %s AND source_record_id = %d LIMIT 1",
				$source_type,
				$source_record_id
			),
			ARRAY_A
		);

		return is_array($row) ? $row : [];
	}

	private static function refresh_contact_rollups(int $contact_id): void
	{
		global $wpdb;

		if ($contact_id <= 0) {
			return;
		}

		$contacts_table      = self::get_contacts_table_name();
		$opportunities_table = self::get_opportunities_table_name();
		$counts              = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM(CASE WHEN lead_id > 0 THEN 1 ELSE 0 END) AS lead_count,
					SUM(CASE WHEN booking_id > 0 THEN 1 ELSE 0 END) AS booking_count
				FROM {$opportunities_table}
				WHERE contact_id = %d",
				$contact_id
			),
			ARRAY_A
		);

		if (! is_array($counts)) {
			return;
		}

		$wpdb->update(
			$contacts_table,
			[
				'lead_count'    => (int) ($counts['lead_count'] ?? 0),
				'booking_count' => (int) ($counts['booking_count'] ?? 0),
				'updated_at'    => current_time('mysql'),
			],
			['id' => $contact_id]
		);
	}

	private static function delete_contact_if_orphaned(int $contact_id): void
	{
		global $wpdb;

		if ($contact_id <= 0) {
			return;
		}

		$contacts_table      = self::get_contacts_table_name();
		$opportunities_table = self::get_opportunities_table_name();
		$total               = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$opportunities_table} WHERE contact_id = %d",
				$contact_id
			)
		);

		if ($total > 0) {
			return;
		}

		$wpdb->delete($contacts_table, ['id' => $contact_id]);
	}

	/**
	 * @param array<int, string> $stages
	 * @return array<int, string>
	 */
	private static function sanitize_stage_list(array $stages): array
	{
		return array_values(
			array_filter(
				array_map(
					static fn(string $stage): string => sanitize_key($stage),
					$stages
				),
				static fn(string $stage): bool => $stage !== ''
			)
		);
	}

	/**
	 * @return array<int, string>
	 */
	private static function get_open_stage_list(): array
	{
		return [
			'new',
			'contacted',
			'follow_up_scheduled',
			'qualified',
			'quote_sent',
			'booking_pending',
			'booking_contacted',
			'booking_confirmed',
		];
	}

	private static function normalize_stage(string $stage, string $context): string
	{
		$stage = sanitize_key($stage);

		if ($stage === '') {
			return $context === 'booking' ? 'booking_pending' : 'new';
		}

		if ($context === 'booking' && ! str_starts_with($stage, 'booking_')) {
			return 'booking_' . $stage;
		}

		return $stage;
	}

	private static function normalize_priority(string $priority): string
	{
		$priority = sanitize_key($priority);
		$allowed  = ['low', 'medium', 'high', 'urgent'];

		return in_array($priority, $allowed, true) ? $priority : 'medium';
	}

	private static function record_is_active(string $post_type, int $post_id): bool
	{
		if ($post_id <= 0 || get_post_type($post_id) !== $post_type) {
			return false;
		}

		$status = get_post_status($post_id);

		return is_string($status) && $status !== '' && $status !== 'trash' && $status !== 'auto-draft';
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

	private static function normalize_date(string $date): ?string
	{
		$date = trim($date);

		if ($date === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return null;
		}

		return $date;
	}

	private static function normalize_datetime(string $datetime): ?string
	{
		$datetime = trim($datetime);

		if ($datetime === '') {
			return null;
		}

		$timestamp = strtotime($datetime);

		return $timestamp ? wp_date('Y-m-d H:i:s', $timestamp) : null;
	}

	private static function nullable_text(string $value): ?string
	{
		$value = trim($value);

		return $value === '' ? null : $value;
	}
}
