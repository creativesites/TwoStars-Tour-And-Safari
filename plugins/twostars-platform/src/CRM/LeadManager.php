<?php
declare(strict_types=1);

namespace TwoStars\Platform\CRM;

use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Support\TourData;
use TwoStars\Platform\Visitors\VisitorProfileManager;
use WP_Error;
use WP_REST_Request;

final class LeadManager implements Service
{
	private const NONCE_ACTION = 'twostars_platform_save_lead_meta';
	private const NONCE_NAME = 'twostars_platform_lead_meta_nonce';

	public function register(): void
	{
		add_action('init', [$this, 'register_meta']);
		add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
		add_action('save_post_lead', [$this, 'save_meta']);
		add_filter('manage_lead_posts_columns', [$this, 'register_columns']);
		add_action('manage_lead_posts_custom_column', [$this, 'render_column'], 10, 2);
		add_action('admin_menu', [$this, 'register_export_submenu']);
		add_action('admin_post_twostars_export_leads', [$this, 'export_leads']);
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_source_choices(): array
	{
		return [
			'website'  => __('Website Form', 'twostars-platform'),
			'chatbot'  => __('Chatbot', 'twostars-platform'),
			'whatsapp' => __('WhatsApp', 'twostars-platform'),
			'social'   => __('Social Media', 'twostars-platform'),
			'calendly' => __('Calendly', 'twostars-platform'),
			'manual'   => __('Manual Entry', 'twostars-platform'),
			'referral' => __('Referral', 'twostars-platform'),
		];
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_status_choices(): array
	{
		return [
			'new'                 => __('New', 'twostars-platform'),
			'contacted'           => __('Contacted', 'twostars-platform'),
			'follow_up_scheduled' => __('Follow Up Scheduled', 'twostars-platform'),
			'qualified'           => __('Qualified', 'twostars-platform'),
			'quote_sent'          => __('Quote Sent', 'twostars-platform'),
			'booking_pending'     => __('Booking Pending', 'twostars-platform'),
			'won'                 => __('Won', 'twostars-platform'),
			'lost'                => __('Lost', 'twostars-platform'),
		];
	}

	public function register_meta(): void
	{
		foreach (self::get_manager_field_definitions() as $meta_key => $definition) {
			register_post_meta(
				'lead',
				$meta_key,
				[
					'type'              => (string) ($definition['meta_type'] ?? 'string'),
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => fn($value) => $this->sanitize_value($meta_key, $value),
					'auth_callback'     => static fn() => current_user_can('edit_posts'),
				]
			);
		}
	}

	public function register_meta_boxes(): void
	{
		add_meta_box(
			'twostars-lead-details',
			__('Lead Details', 'twostars-platform'),
			[$this, 'render_details_meta_box'],
			'lead',
			'normal',
			'high'
		);

		add_meta_box(
			'twostars-lead-pipeline',
			__('Pipeline', 'twostars-platform'),
			[$this, 'render_pipeline_meta_box'],
			'lead',
			'side',
			'high'
		);
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_details_meta_box($post): void
	{
		wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
		?>
		<div class="twostars-admin-fields">
			<?php foreach ($this->get_details_fields() as $meta_key => $definition) : ?>
				<?php $this->render_field($post->ID, $meta_key, $definition); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_pipeline_meta_box($post): void
	{
		wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
		?>
		<div class="twostars-admin-fields">
			<?php $this->render_field($post->ID, '_twostars_lead_status', self::get_manager_field_definitions()['_twostars_lead_status']); ?>
			<?php $this->render_field($post->ID, '_twostars_lead_follow_up_date', self::get_manager_field_definitions()['_twostars_lead_follow_up_date']); ?>
			<?php $this->render_field($post->ID, '_twostars_lead_internal_notes', self::get_manager_field_definitions()['_twostars_lead_internal_notes']); ?>
		</div>
		<?php
	}

	public function register_export_submenu(): void
	{
		add_submenu_page(
			'edit.php?post_type=lead',
			__('Export Leads', 'twostars-platform'),
			__('Export Leads', 'twostars-platform'),
			'manage_options',
			'twostars-export-leads',
			[$this, 'render_export_page']
		);
	}

	public function render_export_page(): void
	{
		$url = wp_nonce_url(
			admin_url('admin-post.php?action=twostars_export_leads'),
			'twostars_export_leads'
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Export Leads', 'twostars-platform'); ?></h1>
			<p><?php esc_html_e('Download all CRM leads as a CSV file for reporting, backup, or handoff workflows.', 'twostars-platform'); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url($url); ?>">
					<?php esc_html_e('Download CSV', 'twostars-platform'); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function export_leads(): void
	{
		if (! current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to export leads.', 'twostars-platform'));
		}

		check_admin_referer('twostars_export_leads');

		$leads = get_posts(
			[
				'post_type'      => 'lead',
				'post_status'    => 'private',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		nocache_headers();
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=twostars-leads-' . gmdate('Y-m-d-His') . '.csv');

		$output = fopen('php://output', 'w');

		if (! $output) {
			exit;
		}

		fputcsv(
			$output,
			[
				'ID',
				'Created',
				'Title',
				'Status',
				'Source',
				'Name',
				'Email',
				'Phone',
				'Country',
				'Tour Interest',
				'Guest Count',
				'Travel Dates',
				'Follow Up Date',
				'Origin URL',
				'Message',
				'Internal Notes',
			]
		);

		foreach ($leads as $lead) {
			$tour_id = (int) get_post_meta($lead->ID, '_twostars_lead_tour_interest', true);
			fputcsv(
				$output,
				[
					$lead->ID,
					$lead->post_date,
					$lead->post_title,
					(string) get_post_meta($lead->ID, '_twostars_lead_status', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_source', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_full_name', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_email', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_phone', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_country', true),
					$tour_id > 0 ? get_the_title($tour_id) : '',
					(string) get_post_meta($lead->ID, '_twostars_lead_guest_count', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_travel_dates', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_follow_up_date', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_origin_url', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_message', true),
					(string) get_post_meta($lead->ID, '_twostars_lead_internal_notes', true),
				]
			);
		}

		fclose($output);
		exit;
	}

	/**
	 * @param int $post_id
	 */
	public function save_meta(int $post_id): void
	{
		if (
			! isset($_POST[self::NONCE_NAME]) ||
			! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
		) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (! current_user_can('edit_post', $post_id)) {
			return;
		}

		foreach (array_keys(self::get_manager_field_definitions()) as $meta_key) {
			$post_key = ltrim($meta_key, '_');
			$value    = isset($_POST[$post_key]) ? wp_unslash($_POST[$post_key]) : null;
			$value    = $this->sanitize_value($meta_key, $value);

			if ($value === '' || $value === null || $value === 0) {
				delete_post_meta($post_id, $meta_key);
				continue;
			}

			update_post_meta($post_id, $meta_key, $value);
		}

		$this->sync_generated_title($post_id);
		CoreCRM::sync_lead($post_id);
	}

	/**
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function register_columns(array $columns): array
	{
		unset($columns['date']);

		$columns['lead_contact']  = __('Contact', 'twostars-platform');
		$columns['lead_status']   = __('Status', 'twostars-platform');
		$columns['lead_source']   = __('Source', 'twostars-platform');
		$columns['lead_tour']     = __('Tour Interest', 'twostars-platform');
		$columns['lead_followup'] = __('Follow Up', 'twostars-platform');
		$columns['date']          = __('Created', 'twostars-platform');

		return $columns;
	}

	public function render_column(string $column, int $post_id): void
	{
		switch ($column) {
			case 'lead_contact':
				$email = (string) get_post_meta($post_id, '_twostars_lead_email', true);
				$phone = (string) get_post_meta($post_id, '_twostars_lead_phone', true);
				echo esc_html(trim($email . ' ' . $phone));
				break;

			case 'lead_status':
				$status = (string) get_post_meta($post_id, '_twostars_lead_status', true);
				echo esc_html($this->get_choice_label('_twostars_lead_status', $status));
				break;

			case 'lead_source':
				$source = (string) get_post_meta($post_id, '_twostars_lead_source', true);
				echo esc_html($this->get_choice_label('_twostars_lead_source', $source));
				break;

			case 'lead_tour':
				$tour_id = (int) get_post_meta($post_id, '_twostars_lead_tour_interest', true);
				echo $tour_id > 0 ? esc_html(get_the_title($tour_id)) : '&mdash;';
				break;

			case 'lead_followup':
				$follow_up_date = (string) get_post_meta($post_id, '_twostars_lead_follow_up_date', true);
				echo $follow_up_date !== '' ? esc_html($follow_up_date) : '&mdash;';
				break;
		}
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return int|WP_Error
	 */
	public static function create_lead(array $payload)
	{
		$name  = sanitize_text_field((string) ($payload['full_name'] ?? $payload['name'] ?? ''));
		$email = sanitize_email((string) ($payload['email'] ?? ''));
		$phone = sanitize_text_field((string) ($payload['phone'] ?? ''));
		$session_key = sanitize_text_field((string) ($payload['session_key'] ?? ''));
		$visitor_profile_id = absint($payload['visitor_profile_id'] ?? 0);

		if ($visitor_profile_id <= 0 && $session_key !== '') {
			$profile = VisitorProfileManager::get_profile_by_session_key($session_key);
			$visitor_profile_id = (int) ($profile['id'] ?? 0);
		}

		if ($email === '' && $phone === '') {
			return new WP_Error(
				'twostars_lead_missing_contact',
				__('A lead requires at least an email address or phone number.', 'twostars-platform'),
				['status' => 400]
			);
		}

		$tour_id = absint($payload['tour_interest'] ?? $payload['tour_id'] ?? 0);

		if ($tour_id > 0 && get_post_type($tour_id) !== 'tour') {
			$tour_id = 0;
		}

		$lead_id = wp_insert_post(
			[
				'post_type'   => 'lead',
				'post_status' => 'private',
				'post_title'  => self::build_generated_title(
					$name,
					$email,
					(string) ($payload['source'] ?? 'website')
				),
			],
			true
		);

		if (is_wp_error($lead_id)) {
			return $lead_id;
		}

		$meta = [
			'_twostars_lead_full_name'      => $name,
			'_twostars_lead_email'          => $email,
			'_twostars_lead_email_normalized' => self::normalize_email($email),
			'_twostars_lead_phone'          => $phone,
			'_twostars_lead_phone_normalized' => self::normalize_phone($phone),
			'_twostars_lead_country'        => sanitize_text_field((string) ($payload['country'] ?? '')),
			'_twostars_lead_source'         => sanitize_text_field((string) ($payload['source'] ?? 'website')),
			'_twostars_lead_status'         => sanitize_text_field((string) ($payload['status'] ?? 'new')),
			'_twostars_lead_tour_interest'  => $tour_id,
			'_twostars_lead_guest_count'    => absint($payload['guest_count'] ?? 0),
			'_twostars_lead_travel_dates'   => sanitize_text_field((string) ($payload['travel_dates'] ?? '')),
			'_twostars_lead_message'        => sanitize_textarea_field((string) ($payload['message'] ?? '')),
			'_twostars_lead_internal_notes' => sanitize_textarea_field((string) ($payload['internal_notes'] ?? '')),
			'_twostars_lead_follow_up_date' => sanitize_text_field((string) ($payload['follow_up_date'] ?? '')),
			'_twostars_lead_origin_url'     => esc_url_raw((string) ($payload['origin_url'] ?? '')),
			'_twostars_lead_contact_consent' => ! empty($payload['contact_consent']) ? '1' : '',
			'_twostars_lead_session_key'    => $session_key,
			'_twostars_lead_visitor_profile_id' => $visitor_profile_id,
		];

		foreach ($meta as $meta_key => $value) {
			if ($value === '' || $value === 0) {
				continue;
			}

			update_post_meta($lead_id, $meta_key, $value);
		}

		if ($visitor_profile_id > 0) {
			VisitorProfileManager::link_profile_to_record($visitor_profile_id, 'lead', $lead_id);
			VisitorProfileManager::maybe_link_records_by_contact($visitor_profile_id, $email, $phone);
		}

		CoreCRM::sync_lead((int) $lead_id);

		return $lead_id;
	}

	/**
	 * @return WP_Error|null
	 */
	public static function validate_public_submission(WP_REST_Request $request): ?WP_Error
	{
		$started_at = absint($request->get_param('form_started_at'));
		$now        = time();

		if ($started_at === 0 || ($now - $started_at) < 3 || ($now - $started_at) > DAY_IN_SECONDS) {
			return new WP_Error(
				'twostars_lead_invalid_timing',
				__('Form timing validation failed.', 'twostars-platform'),
				['status' => 400]
			);
		}

		$ip = self::get_request_ip();

		if ($ip !== '') {
			$key   = 'twostars_lead_rate_' . md5($ip);
			$count = (int) get_transient($key);

			if ($count >= 5) {
				return new WP_Error(
					'twostars_lead_rate_limited',
					__('Too many submissions from this connection. Please wait and try again.', 'twostars-platform'),
					['status' => 429]
				);
			}

			set_transient($key, $count + 1, 15 * MINUTE_IN_SECONDS);
		}

		return null;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_manager_field_definitions(): array
	{
		return [
			'_twostars_lead_full_name' => [
				'label' => __('Full Name', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_lead_email' => [
				'label' => __('Email', 'twostars-platform'),
				'type'  => 'email',
			],
			'_twostars_lead_visitor_profile_id' => [
				'label'     => __('Visitor Profile', 'twostars-platform'),
				'type'      => 'number',
				'step'      => '1',
				'meta_type' => 'integer',
			],
			'_twostars_lead_phone' => [
				'label' => __('Phone', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_lead_country' => [
				'label' => __('Country', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_lead_source' => [
				'label'   => __('Lead Source', 'twostars-platform'),
				'type'    => 'select',
				'choices' => self::get_source_choices(),
			],
			'_twostars_lead_status' => [
				'label'   => __('Status', 'twostars-platform'),
				'type'    => 'select',
				'choices' => self::get_status_choices(),
			],
			'_twostars_lead_priority' => [
				'label'   => __('Priority', 'twostars-platform'),
				'type'    => 'select',
				'choices' => [
					'low' => __('Low', 'twostars-platform'),
					'medium' => __('Medium', 'twostars-platform'),
					'high' => __('High', 'twostars-platform'),
					'urgent' => __('Urgent', 'twostars-platform'),
				],
			],
			'_twostars_lead_assigned_to' => [
				'label' => __('Assigned To', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_lead_tour_interest' => [
				'label'     => __('Tour Interest', 'twostars-platform'),
				'type'      => 'select_dynamic',
				'meta_type' => 'integer',
			],
			'_twostars_lead_guest_count' => [
				'label'     => __('Guest Count', 'twostars-platform'),
				'type'      => 'number',
				'step'      => '1',
				'meta_type' => 'integer',
			],
			'_twostars_lead_travel_dates' => [
				'label' => __('Preferred Travel Dates', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_lead_message' => [
				'label' => __('Message', 'twostars-platform'),
				'type'  => 'textarea',
			],
			'_twostars_lead_origin_url' => [
				'label' => __('Origin URL', 'twostars-platform'),
				'type'  => 'url',
			],
			'_twostars_lead_session_key' => [
				'label' => __('Session Key', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_lead_contact_consent' => [
				'label'     => __('Contact Consent', 'twostars-platform'),
				'type'      => 'checkbox',
				'meta_type' => 'boolean',
			],
			'_twostars_lead_follow_up_date' => [
				'label' => __('Follow Up Date', 'twostars-platform'),
				'type'  => 'date',
			],
			'_twostars_lead_follow_up_channel' => [
				'label'   => __('Follow Up Channel', 'twostars-platform'),
				'type'    => 'select',
				'choices' => [
					'email' => __('Email', 'twostars-platform'),
					'phone' => __('Phone Call', 'twostars-platform'),
					'whatsapp' => __('WhatsApp', 'twostars-platform'),
					'manual' => __('Manual Follow Up', 'twostars-platform'),
				],
			],
			'_twostars_lead_last_contacted_at' => [
				'label' => __('Last Contacted', 'twostars-platform'),
				'type'  => 'datetime-local',
			],
			'_twostars_lead_next_action' => [
				'label' => __('Next Action', 'twostars-platform'),
				'type'  => 'textarea',
			],
			'_twostars_lead_internal_notes' => [
				'label' => __('Internal Notes', 'twostars-platform'),
				'type'  => 'textarea',
			],
			'_twostars_lead_activity_log' => [
				'label' => __('CRM Activity Log', 'twostars-platform'),
				'type'  => 'textarea',
			],
		];
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function get_details_fields(): array
	{
		return array_intersect_key(
			self::get_manager_field_definitions(),
			array_flip(
				[
					'_twostars_lead_full_name',
					'_twostars_lead_email',
					'_twostars_lead_phone',
					'_twostars_lead_country',
					'_twostars_lead_source',
					'_twostars_lead_tour_interest',
					'_twostars_lead_guest_count',
					'_twostars_lead_travel_dates',
					'_twostars_lead_message',
					'_twostars_lead_origin_url',
					'_twostars_lead_contact_consent',
				]
			)
		);
	}

	/**
	 * @param int $post_id
	 * @param array<string, mixed> $definition
	 */
	private function render_field(int $post_id, string $meta_key, array $definition): void
	{
		$value    = get_post_meta($post_id, $meta_key, true);
		$field_id = ltrim($meta_key, '_');
		$type     = (string) ($definition['type'] ?? 'text');
		?>
		<p>
			<label for="<?php echo esc_attr($field_id); ?>"><strong><?php echo esc_html((string) $definition['label']); ?></strong></label><br>
			<?php if ($type === 'textarea') : ?>
				<textarea id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" rows="4" style="width: 100%;"><?php echo esc_textarea((string) $value); ?></textarea>
			<?php elseif ($type === 'select') : ?>
				<?php $this->render_select_control($field_id, (string) $value, (array) ($definition['choices'] ?? [])); ?>
			<?php elseif ($type === 'select_dynamic') : ?>
				<?php $this->render_select_control($field_id, (string) $value, ['' => __('Select a tour', 'twostars-platform')] + TourData::get_tour_options()); ?>
			<?php elseif ($type === 'checkbox') : ?>
				<label>
					<input id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" type="checkbox" value="1" <?php checked(! empty($value)); ?>>
					<?php esc_html_e('Visitor has consented to follow-up contact.', 'twostars-platform'); ?>
				</label>
			<?php else : ?>
				<input
					id="<?php echo esc_attr($field_id); ?>"
					name="<?php echo esc_attr($field_id); ?>"
					type="<?php echo esc_attr($type); ?>"
					value="<?php echo esc_attr((string) $value); ?>"
					step="<?php echo esc_attr((string) ($definition['step'] ?? '')); ?>"
					style="width: 100%;"
				>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * @param array<string, string> $options
	 */
	private function render_select_control(string $field_id, string $value, array $options): void
	{
		?>
		<select id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" style="width: 100%;">
			<?php foreach ($options as $option_value => $option_label) : ?>
				<option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected($value, (string) $option_value); ?>>
					<?php echo esc_html((string) $option_label); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * @param mixed $value
	 * @return bool|int|string|null
	 */
	private function sanitize_value(string $meta_key, $value)
	{
		if ($value === null) {
			return null;
		}

		$definition = self::get_manager_field_definitions()[$meta_key] ?? [];
		$type       = (string) ($definition['type'] ?? 'text');

		if ($type === 'checkbox') {
			return ! empty($value);
		}

		if (is_string($value)) {
			$value = trim($value);
		}

		if ($value === '') {
			return '';
		}

		if ($type === 'textarea') {
			return sanitize_textarea_field((string) $value);
		}

		if ($type === 'email') {
			return sanitize_email((string) $value);
		}

		if ($type === 'url') {
			return esc_url_raw((string) $value);
		}

		if ($type === 'number' || $type === 'select_dynamic') {
			return absint($value);
		}

		if ($type === 'select') {
			$choices = array_keys((array) ($definition['choices'] ?? []));
			$value   = sanitize_text_field((string) $value);

			return in_array($value, $choices, true) ? $value : '';
		}

		return sanitize_text_field((string) $value);
	}

	private function sync_generated_title(int $post_id): void
	{
		$name   = (string) get_post_meta($post_id, '_twostars_lead_full_name', true);
		$email  = (string) get_post_meta($post_id, '_twostars_lead_email', true);
		$source = (string) get_post_meta($post_id, '_twostars_lead_source', true);
		$title  = self::build_generated_title($name, $email, $source);

		remove_action('save_post_lead', [$this, 'save_meta']);
		wp_update_post(
			[
				'ID'         => $post_id,
				'post_title' => $title,
				'post_name'  => sanitize_title($title . '-' . $post_id),
			]
		);
		add_action('save_post_lead', [$this, 'save_meta']);
	}

	private static function build_generated_title(string $name, string $email, string $source): string
	{
		$identity = $name !== '' ? $name : $email;

		if ($identity === '') {
			$identity = __('Unknown Contact', 'twostars-platform');
		}

		$source_label = $source !== '' ? ucfirst(str_replace('_', ' ', $source)) : __('Website', 'twostars-platform');

		return sprintf('%s | %s | %s', $identity, $source_label, wp_date('Y-m-d H:i'));
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

	private function get_choice_label(string $meta_key, string $value): string
	{
		$choices = (array) ((self::get_manager_field_definitions()[$meta_key] ?? [])['choices'] ?? []);

		return (string) ($choices[$value] ?? $value);
	}

	private static function get_request_ip(): string
	{
		$candidates = [
			$_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
			$_SERVER['REMOTE_ADDR'] ?? '',
		];

		foreach ($candidates as $candidate) {
			$ip = trim(explode(',', (string) $candidate)[0]);

			if (filter_var($ip, FILTER_VALIDATE_IP)) {
				return $ip;
			}
		}

		return '';
	}
}
