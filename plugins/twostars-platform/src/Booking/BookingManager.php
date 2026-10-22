<?php
declare(strict_types=1);

namespace TwoStars\Platform\Booking;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use TwoStars\Platform\CRM\CoreCRM;
use TwoStars\Platform\CRM\LeadManager;
use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Visitors\VisitorProfileManager;
use WP_Error;

final class BookingManager implements Service
{
	private const POST_TYPE = 'booking';

	public function register(): void
	{
		add_action('init', [$this, 'register_meta']);
		add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
		add_filter('manage_booking_posts_columns', [$this, 'register_columns']);
		add_action('manage_booking_posts_custom_column', [$this, 'render_column'], 10, 2);
	}

	public function register_meta(): void
	{
		foreach (self::get_manager_field_definitions() as $meta_key => $definition) {
			register_post_meta(
				self::POST_TYPE,
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
			'twostars-booking-details',
			__('Booking Details', 'twostars-platform'),
			[$this, 'render_details_meta_box'],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_details_meta_box($post): void
	{
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<?php foreach (self::get_manager_field_definitions() as $meta_key => $definition) : ?>
					<?php
					$value = get_post_meta($post->ID, $meta_key, true);

					if (is_array($value)) {
						$value = implode(', ', array_map('strval', $value));
					}
					?>
					<tr>
						<th scope="row"><?php echo esc_html((string) ($definition['label'] ?? '')); ?></th>
						<td><?php echo $value !== '' ? esc_html((string) $value) : '&mdash;'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function register_columns(array $columns): array
	{
		unset($columns['date']);

		$columns['booking_tour']    = __('Tour', 'twostars-platform');
		$columns['booking_date']    = __('Date', 'twostars-platform');
		$columns['booking_time']    = __('Time', 'twostars-platform');
		$columns['booking_contact'] = __('Contact', 'twostars-platform');
		$columns['booking_status']  = __('Status', 'twostars-platform');
		$columns['date']            = __('Created', 'twostars-platform');

		return $columns;
	}

	public function render_column(string $column, int $post_id): void
	{
		switch ($column) {
			case 'booking_tour':
				$tour_id = (int) get_post_meta($post_id, '_twostars_booking_tour_id', true);
				echo $tour_id > 0 ? esc_html(get_the_title($tour_id)) : '&mdash;';
				break;

			case 'booking_date':
				$date = (string) get_post_meta($post_id, '_twostars_booking_date', true);
				echo $date !== '' ? esc_html($this->format_date_for_display($date)) : '&mdash;';
				break;

			case 'booking_time':
				$time = (string) get_post_meta($post_id, '_twostars_booking_time', true);
				echo $time !== '' ? esc_html($this->format_time_for_display($time)) : '&mdash;';
				break;

			case 'booking_contact':
				$email = (string) get_post_meta($post_id, '_twostars_booking_email', true);
				$phone = (string) get_post_meta($post_id, '_twostars_booking_phone', true);
				echo esc_html(trim($email . ' ' . $phone));
				break;

			case 'booking_status':
				$status = (string) get_post_meta($post_id, '_twostars_booking_status', true);
				echo esc_html($this->get_status_label($status));
				break;
		}
	}

	/**
	 * @return array<int, array{date:string,label:string,day:string,dayNumber:string,month:string,available_count:int,slots:array<int,array{value:string,label:string}>}>
	 */
	public static function get_availability_window(int $tour_id, int $days = 18, string $start_date = ''): array
	{
		$days = max(7, min(45, $days));
		$timezone = wp_timezone();
		$start = $start_date !== ''
			? DateTimeImmutable::createFromFormat('Y-m-d', $start_date, $timezone)
			: new DateTimeImmutable('today', $timezone);

		if (! $start instanceof DateTimeImmutable) {
			$start = new DateTimeImmutable('today', $timezone);
		}

		$dates = [];

		for ($i = 0; $i < $days; $i++) {
			$date = $start->add(new DateInterval('P' . $i . 'D'));
			$key = $date->format('Y-m-d');
			$slots = self::get_available_slots($tour_id, $key);

			if ($slots === []) {
				continue;
			}

			$dates[] = [
				'date'            => $key,
				'label'           => wp_date('D, j M', $date->getTimestamp(), $timezone),
				'day'             => wp_date('D', $date->getTimestamp(), $timezone),
				'dayNumber'       => wp_date('j', $date->getTimestamp(), $timezone),
				'month'           => wp_date('M', $date->getTimestamp(), $timezone),
				'available_count' => count($slots),
				'slots'           => $slots,
			];
		}

		return $dates;
	}

	/**
	 * @return array<int, array{value:string,label:string}>
	 */
	public static function get_available_slots(int $tour_id, string $date): array
	{
		if ($tour_id <= 0 || get_post_type($tour_id) !== 'tour') {
			return [];
		}

		$date_object = self::parse_date($date);

		if (! $date_object instanceof DateTimeImmutable) {
			return [];
		}

		$today = new DateTimeImmutable('today', wp_timezone());

		if ($date_object < $today) {
			return [];
		}

		$slots = self::get_base_slots((int) $date_object->format('w'));
		$booked_times = self::get_booked_times($date);
		$cutoff = new DateTimeImmutable('now', wp_timezone());
		$cutoff = $cutoff->add(new DateInterval('PT90M'));
		$available = [];

		foreach ($slots as $slot) {
			if (isset($booked_times[$slot])) {
				continue;
			}

			$slot_datetime = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $slot, wp_timezone());

			if (! $slot_datetime instanceof DateTimeImmutable) {
				continue;
			}

			if ($date_object->format('Y-m-d') === $today->format('Y-m-d') && $slot_datetime <= $cutoff) {
				continue;
			}

			$available[] = [
				'value' => $slot,
				'label' => self::format_time_label($slot),
			];
		}

		return $available;
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_booking(array $payload)
	{
		$full_name = sanitize_text_field((string) ($payload['full_name'] ?? $payload['name'] ?? ''));
		$email = sanitize_email((string) ($payload['email'] ?? ''));
		$phone = sanitize_text_field((string) ($payload['phone'] ?? ''));
		$tour_id = absint($payload['tour_id'] ?? 0);
		$date = sanitize_text_field((string) ($payload['date'] ?? ''));
		$time = sanitize_text_field((string) ($payload['time'] ?? ''));
		$guest_count = max(1, absint($payload['guest_count'] ?? 1));
		$pickup_location = sanitize_text_field((string) ($payload['pickup_location'] ?? ''));
		$country = sanitize_text_field((string) ($payload['country'] ?? ''));
		$notes = sanitize_textarea_field((string) ($payload['notes'] ?? $payload['message'] ?? ''));
		$origin_url = esc_url_raw((string) ($payload['origin_url'] ?? ''));
		$session_key = sanitize_text_field((string) ($payload['session_key'] ?? ''));
		$visitor_profile_id = absint($payload['visitor_profile_id'] ?? 0);

		if ($visitor_profile_id <= 0 && $session_key !== '') {
			$profile = VisitorProfileManager::get_profile_by_session_key($session_key);
			$visitor_profile_id = (int) ($profile['id'] ?? 0);
		}

		if ($full_name === '') {
			return new WP_Error('twostars_booking_missing_name', __('Please enter the guest name for this booking.', 'twostars-platform'), ['status' => 400]);
		}

		if ($email === '' && $phone === '') {
			return new WP_Error('twostars_booking_missing_contact', __('Please add an email address or phone number so the team can confirm your booking.', 'twostars-platform'), ['status' => 400]);
		}

		if ($tour_id <= 0 || get_post_type($tour_id) !== 'tour') {
			return new WP_Error('twostars_booking_invalid_tour', __('Please choose one of the current Two Stars tours.', 'twostars-platform'), ['status' => 400]);
		}

		$available_times = array_map(
			static fn(array $slot): string => (string) ($slot['value'] ?? ''),
			self::get_available_slots($tour_id, $date)
		);

		if (! in_array($time, $available_times, true)) {
			return new WP_Error('twostars_booking_invalid_slot', __('That booking time is no longer available. Please choose another slot.', 'twostars-platform'), ['status' => 409]);
		}

		$tour_title = get_the_title($tour_id);
		$post_title = sprintf('%s | %s | %s %s', $full_name, $tour_title, $date, $time);

		$booking_id = wp_insert_post(
			[
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => $post_title,
			],
			true
		);

		if (is_wp_error($booking_id)) {
			return $booking_id;
		}

		$meta = [
			'_twostars_booking_full_name' => $full_name,
			'_twostars_booking_email' => $email,
			'_twostars_booking_email_normalized' => self::normalize_email($email),
			'_twostars_booking_phone' => $phone,
			'_twostars_booking_phone_normalized' => self::normalize_phone($phone),
			'_twostars_booking_country' => $country,
			'_twostars_booking_tour_id' => $tour_id,
			'_twostars_booking_guest_count' => $guest_count,
			'_twostars_booking_date' => $date,
			'_twostars_booking_time' => $time,
			'_twostars_booking_pickup_location' => $pickup_location,
			'_twostars_booking_notes' => $notes,
			'_twostars_booking_origin_url' => $origin_url,
			'_twostars_booking_status' => 'pending',
			'_twostars_booking_session_key' => $session_key,
			'_twostars_booking_visitor_profile_id' => $visitor_profile_id,
		];

		foreach ($meta as $meta_key => $value) {
			if ($value === '' || $value === 0) {
				continue;
			}

			update_post_meta($booking_id, $meta_key, $value);
		}

		$lead_message_parts = array_filter(
			[
				sprintf(__('Requested tour: %s', 'twostars-platform'), $tour_title),
				sprintf(__('Requested date: %s', 'twostars-platform'), self::format_date_label($date)),
				sprintf(__('Requested time: %s', 'twostars-platform'), self::format_time_label($time)),
				$pickup_location !== '' ? sprintf(__('Pickup area: %s', 'twostars-platform'), $pickup_location) : '',
				$notes,
			]
		);

		$lead_id = LeadManager::create_lead(
			[
				'full_name'       => $full_name,
				'email'           => $email,
				'phone'           => $phone,
				'country'         => $country,
				'source'          => 'website',
				'status'          => 'booking_pending',
				'tour_interest'   => $tour_id,
				'guest_count'     => $guest_count,
				'travel_dates'    => self::format_date_label($date) . ' at ' . self::format_time_label($time),
				'message'         => implode("\n", $lead_message_parts),
				'internal_notes'  => sprintf(__('Booking record #%d created from the local booking page.', 'twostars-platform'), $booking_id),
				'origin_url'      => $origin_url,
				'contact_consent' => true,
				'session_key'     => $session_key,
				'visitor_profile_id' => $visitor_profile_id,
			]
		);

		$mail_sent = self::send_admin_notification(
			$booking_id,
			[
				'full_name'       => $full_name,
				'email'           => $email,
				'phone'           => $phone,
				'country'         => $country,
				'tour_title'      => $tour_title,
				'guest_count'     => $guest_count,
				'date'            => $date,
				'time'            => $time,
				'pickup_location' => $pickup_location,
				'notes'           => $notes,
			]
		);

		update_post_meta($booking_id, '_twostars_booking_admin_email_sent', $mail_sent ? '1' : '0');
		update_post_meta($booking_id, '_twostars_booking_lead_id', is_wp_error($lead_id) ? 0 : (int) $lead_id);

		if ($visitor_profile_id > 0) {
			VisitorProfileManager::link_profile_to_record($visitor_profile_id, 'booking', $booking_id);
			VisitorProfileManager::maybe_link_records_by_contact($visitor_profile_id, $email, $phone);
		}

		CoreCRM::sync_booking((int) $booking_id);

		return [
			'booking_id'      => $booking_id,
			'lead_id'         => is_wp_error($lead_id) ? 0 : (int) $lead_id,
			'admin_notified'  => $mail_sent,
			'booking_message' => __('Your booking request has been saved. The Two Stars team will confirm the details with you shortly.', 'twostars-platform'),
		];
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_status_choices(): array
	{
		return [
			'pending'   => __('Pending', 'twostars-platform'),
			'contacted' => __('Contacted', 'twostars-platform'),
			'confirmed' => __('Confirmed', 'twostars-platform'),
			'completed' => __('Completed', 'twostars-platform'),
			'cancelled' => __('Cancelled', 'twostars-platform'),
		];
	}

	/**
	 * @return array<int, string>
	 */
	public static function get_pickup_options(): array
	{
		return [
			__('Walvis Bay', 'twostars-platform'),
			__('Swakopmund', 'twostars-platform'),
			__('Walvis Bay or Swakopmund', 'twostars-platform'),
			__('Private/custom pickup', 'twostars-platform'),
		];
	}

	private static function send_admin_notification(int $booking_id, array $data): bool
	{
		$recipient = sanitize_email((string) get_option('admin_email'));

		if ($recipient === '') {
			return false;
		}

		$edit_link = admin_url('post.php?post=' . $booking_id . '&action=edit');
		$subject = sprintf(
			/* translators: %1$s: tour title, %2$s: booking date */
			__('New Two Stars booking: %1$s on %2$s', 'twostars-platform'),
			(string) ($data['tour_title'] ?? ''),
			self::format_date_label((string) ($data['date'] ?? ''))
		);

		$lines = [
			__('A new booking has been submitted on the local Two Stars booking page.', 'twostars-platform'),
			'',
			sprintf(__('Guest: %s', 'twostars-platform'), (string) ($data['full_name'] ?? '')),
			sprintf(__('Tour: %s', 'twostars-platform'), (string) ($data['tour_title'] ?? '')),
			sprintf(__('Date: %s', 'twostars-platform'), self::format_date_label((string) ($data['date'] ?? ''))),
			sprintf(__('Time: %s', 'twostars-platform'), self::format_time_label((string) ($data['time'] ?? ''))),
			sprintf(__('Guests: %s', 'twostars-platform'), (string) ($data['guest_count'] ?? '1')),
			sprintf(__('Pickup: %s', 'twostars-platform'), (string) ($data['pickup_location'] ?? '')),
			sprintf(__('Email: %s', 'twostars-platform'), (string) ($data['email'] ?? '')),
			sprintf(__('Phone: %s', 'twostars-platform'), (string) ($data['phone'] ?? '')),
			sprintf(__('Country: %s', 'twostars-platform'), (string) ($data['country'] ?? '')),
			'',
			__('Notes:', 'twostars-platform'),
			(string) ($data['notes'] ?? __('No extra notes supplied.', 'twostars-platform')),
			'',
			sprintf(__('Open booking record: %s', 'twostars-platform'), $edit_link),
		];

		$headers = ['Content-Type: text/plain; charset=UTF-8'];

		if (! empty($data['email'])) {
			$headers[] = 'Reply-To: ' . sanitize_email((string) $data['email']);
		}

		return wp_mail($recipient, $subject, implode("\n", $lines), $headers);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_manager_field_definitions(): array
	{
		return [
			'_twostars_booking_full_name' => ['label' => __('Guest Name', 'twostars-platform')],
			'_twostars_booking_email' => ['label' => __('Email', 'twostars-platform'), 'type' => 'email'],
			'_twostars_booking_visitor_profile_id' => ['label' => __('Visitor Profile', 'twostars-platform'), 'meta_type' => 'integer', 'type' => 'number', 'step' => '1'],
			'_twostars_booking_phone' => ['label' => __('Phone', 'twostars-platform')],
			'_twostars_booking_country' => ['label' => __('Country', 'twostars-platform')],
			'_twostars_booking_tour_id' => ['label' => __('Tour', 'twostars-platform'), 'meta_type' => 'integer', 'type' => 'select_dynamic'],
			'_twostars_booking_guest_count' => ['label' => __('Guests', 'twostars-platform'), 'meta_type' => 'integer', 'type' => 'number', 'step' => '1'],
			'_twostars_booking_date' => ['label' => __('Date', 'twostars-platform'), 'type' => 'date'],
			'_twostars_booking_time' => ['label' => __('Time', 'twostars-platform')],
			'_twostars_booking_pickup_location' => ['label' => __('Pickup', 'twostars-platform'), 'type' => 'select', 'choices' => array_combine(self::get_pickup_options(), self::get_pickup_options()) ?: []],
			'_twostars_booking_notes' => ['label' => __('Notes', 'twostars-platform'), 'type' => 'textarea'],
			'_twostars_booking_origin_url' => ['label' => __('Origin URL', 'twostars-platform'), 'type' => 'url'],
			'_twostars_booking_session_key' => ['label' => __('Session Key', 'twostars-platform')],
			'_twostars_booking_status' => ['label' => __('Status', 'twostars-platform'), 'type' => 'select', 'choices' => self::get_status_choices()],
			'_twostars_booking_priority' => [
				'label' => __('Priority', 'twostars-platform'),
				'type' => 'select',
				'choices' => [
					'low' => __('Low', 'twostars-platform'),
					'medium' => __('Medium', 'twostars-platform'),
					'high' => __('High', 'twostars-platform'),
					'urgent' => __('Urgent', 'twostars-platform'),
				],
			],
			'_twostars_booking_assigned_to' => ['label' => __('Assigned To', 'twostars-platform')],
			'_twostars_booking_last_contacted_at' => ['label' => __('Last Contacted', 'twostars-platform'), 'type' => 'datetime-local'],
			'_twostars_booking_confirmation_deadline' => ['label' => __('Confirmation Deadline', 'twostars-platform'), 'type' => 'date'],
			'_twostars_booking_follow_up_channel' => [
				'label' => __('Follow Up Channel', 'twostars-platform'),
				'type' => 'select',
				'choices' => [
					'email' => __('Email', 'twostars-platform'),
					'phone' => __('Phone Call', 'twostars-platform'),
					'whatsapp' => __('WhatsApp', 'twostars-platform'),
					'manual' => __('Manual Follow Up', 'twostars-platform'),
				],
			],
			'_twostars_booking_payment_status' => [
				'label' => __('Payment Status', 'twostars-platform'),
				'type' => 'select',
				'choices' => [
					'pending' => __('Pending', 'twostars-platform'),
					'deposit_requested' => __('Deposit Requested', 'twostars-platform'),
					'deposit_paid' => __('Deposit Paid', 'twostars-platform'),
					'paid' => __('Paid In Full', 'twostars-platform'),
					'refunded' => __('Refunded', 'twostars-platform'),
				],
			],
			'_twostars_booking_next_action' => ['label' => __('Next Action', 'twostars-platform'), 'type' => 'textarea'],
			'_twostars_booking_internal_notes' => ['label' => __('Internal Notes', 'twostars-platform'), 'type' => 'textarea'],
			'_twostars_booking_activity_log' => ['label' => __('CRM Activity Log', 'twostars-platform'), 'type' => 'textarea'],
			'_twostars_booking_admin_email_sent' => ['label' => __('Admin Email Sent', 'twostars-platform'), 'type' => 'checkbox'],
			'_twostars_booking_lead_id' => ['label' => __('Linked Lead', 'twostars-platform'), 'meta_type' => 'integer', 'type' => 'number', 'step' => '1'],
		];
	}

	/**
	 * @return array<int, string>
	 */
	private static function get_base_slots(int $weekday): array
	{
		if ($weekday === 0) {
			return ['08:00', '10:00', '13:00'];
		}

		return ['07:30', '09:00', '10:30', '12:00', '13:30', '15:00', '16:30'];
	}

	/**
	 * @return array<string, bool>
	 */
	private static function get_booked_times(string $date): array
	{
		$times = [];
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT time_meta.meta_value AS booking_time, status_meta.meta_value AS booking_status
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->postmeta} AS date_meta
					ON posts.ID = date_meta.post_id
					AND date_meta.meta_key = %s
				LEFT JOIN {$wpdb->postmeta} AS time_meta
					ON posts.ID = time_meta.post_id
					AND time_meta.meta_key = %s
				LEFT JOIN {$wpdb->postmeta} AS status_meta
					ON posts.ID = status_meta.post_id
					AND status_meta.meta_key = %s
				WHERE posts.post_type = %s
					AND posts.post_status = %s
					AND date_meta.meta_value = %s",
				'_twostars_booking_date',
				'_twostars_booking_time',
				'_twostars_booking_status',
				self::POST_TYPE,
				'private',
				$date
			)
		);

		if (! is_array($rows)) {
			return $times;
		}

		foreach ($rows as $row) {
			$time = isset($row->booking_time) ? (string) $row->booking_time : '';
			$status = isset($row->booking_status) ? (string) $row->booking_status : '';

			if ($time === '' || $status === 'cancelled') {
				continue;
			}

			$times[$time] = true;
		}

		return $times;
	}

	private function sanitize_value(string $meta_key, mixed $value): mixed
	{
		return match ($meta_key) {
			'_twostars_booking_guest_count', '_twostars_booking_tour_id', '_twostars_booking_lead_id', '_twostars_booking_visitor_profile_id' => absint($value),
			'_twostars_booking_email' => sanitize_email((string) $value),
			'_twostars_booking_notes', '_twostars_booking_internal_notes', '_twostars_booking_next_action', '_twostars_booking_activity_log' => sanitize_textarea_field((string) $value),
			default => sanitize_text_field((string) $value),
		};
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

	private function get_status_label(string $status): string
	{
		$choices = self::get_status_choices();

		return (string) ($choices[$status] ?? $choices['pending']);
	}

	private function format_date_for_display(string $date): string
	{
		return self::format_date_label($date);
	}

	private function format_time_for_display(string $time): string
	{
		return self::format_time_label($time);
	}

	private static function parse_date(string $date): ?DateTimeImmutable
	{
		if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return null;
		}

		$parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date, wp_timezone());

		return $parsed instanceof DateTimeImmutable ? $parsed : null;
	}

	private static function format_date_label(string $date): string
	{
		$date_object = self::parse_date($date);

		if (! $date_object instanceof DateTimeInterface) {
			return $date;
		}

		return wp_date('l, j F Y', $date_object->getTimestamp(), wp_timezone());
	}

	private static function format_time_label(string $time): string
	{
		$timestamp = strtotime($time);

		return $timestamp ? wp_date('g:i A', $timestamp, wp_timezone()) : $time;
	}
}
