<?php
declare(strict_types=1);

namespace TwoStars\Platform\Reviews;

use TwoStars\Platform\Support\Service;
use WP_Error;

final class ReviewManager implements Service
{
	public function register(): void
	{
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>|WP_Error
	 */
	public static function submit_review(array $payload)
	{
		$full_name = sanitize_text_field((string) ($payload['full_name'] ?? ''));
		$rating = max(1, min(5, absint($payload['rating'] ?? 0)));
		$review_text = sanitize_textarea_field((string) ($payload['review'] ?? ''));
		$location = sanitize_text_field((string) ($payload['guest_location'] ?? ''));
		$trip_label = sanitize_text_field((string) ($payload['trip_label'] ?? ''));
		$tour_id = absint($payload['tour_id'] ?? 0);
		$email = sanitize_email((string) ($payload['email'] ?? ''));
		$consent = ! empty($payload['review_consent']);
		$source = sanitize_text_field((string) ($payload['source'] ?? 'website'));
		$session_key = sanitize_text_field((string) ($payload['session_key'] ?? ''));
		$visitor_profile_id = absint($payload['visitor_profile_id'] ?? 0);
		$website = sanitize_text_field((string) ($payload['website'] ?? ''));

		if ($website !== '') {
			return [
				'success' => true,
				'status'  => __('Thanks. Your review has been received and will appear once approved.', 'twostars-platform'),
			];
		}

		if ($full_name === '') {
			return new WP_Error('twostars_review_name_required', __('Please enter your name.', 'twostars-platform'), ['status' => 400]);
		}

		if ($rating < 1 || $rating > 5) {
			return new WP_Error('twostars_review_rating_required', __('Please choose a rating.', 'twostars-platform'), ['status' => 400]);
		}

		if ($review_text === '') {
			return new WP_Error('twostars_review_text_required', __('Please share a short review.', 'twostars-platform'), ['status' => 400]);
		}

		if (! $consent) {
			return new WP_Error('twostars_review_consent_required', __('Please confirm that we can use your review.', 'twostars-platform'), ['status' => 400]);
		}

		$post_id = wp_insert_post(
			wp_slash(
				[
					'post_type'    => 'testimonial',
					'post_status'  => 'draft',
					'post_title'   => $full_name,
					'post_excerpt' => wp_trim_words($review_text, 22, '...'),
					'post_content' => $review_text,
				]
			),
			true
		);

		if (is_wp_error($post_id)) {
			return $post_id;
		}

		update_post_meta($post_id, '_twostars_review_rating', $rating);
		update_post_meta($post_id, '_twostars_review_guest_location', $location);
		update_post_meta($post_id, '_twostars_review_trip_label', $trip_label);
		update_post_meta($post_id, '_twostars_review_tour_id', $tour_id);
		update_post_meta($post_id, '_twostars_review_source', $source !== '' ? $source : 'website');
		update_post_meta($post_id, '_twostars_review_featured', 'standard');
		update_post_meta($post_id, '_twostars_review_email', $email);
		update_post_meta($post_id, '_twostars_review_session_key', $session_key);
		update_post_meta($post_id, '_twostars_review_visitor_profile_id', $visitor_profile_id);
		update_post_meta($post_id, '_twostars_review_submitted_at', current_time('mysql'));

		self::notify_admin((int) $post_id, [
			'full_name' => $full_name,
			'rating'    => $rating,
			'review'    => $review_text,
			'location'  => $location,
			'trip'      => $trip_label,
			'tour_id'   => $tour_id,
			'email'     => $email,
		]);

		return [
			'success' => true,
			'post_id' => (int) $post_id,
			'status'  => __('Thank you. Your review has been received and will appear once approved by the Two Stars team.', 'twostars-platform'),
		];
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private static function notify_admin(int $post_id, array $data): void
	{
		$admin_email = get_option('admin_email');

		if (! is_string($admin_email) || $admin_email === '') {
			return;
		}

		$edit_url = admin_url('admin.php?page=twostars-manager-review-edit&post_id=' . $post_id);
		$tour_title = ! empty($data['tour_id']) ? get_the_title((int) $data['tour_id']) : '';

		$lines = [
			__('A new Two Stars website review was submitted and is waiting for approval.', 'twostars-platform'),
			'',
			sprintf(__('Name: %s', 'twostars-platform'), (string) ($data['full_name'] ?? '')),
			sprintf(__('Rating: %s/5', 'twostars-platform'), (string) ($data['rating'] ?? '')),
		];

		if (! empty($data['location'])) {
			$lines[] = sprintf(__('Location: %s', 'twostars-platform'), (string) $data['location']);
		}

		if ($tour_title !== '') {
			$lines[] = sprintf(__('Tour: %s', 'twostars-platform'), $tour_title);
		}

		if (! empty($data['trip'])) {
			$lines[] = sprintf(__('Trip Label: %s', 'twostars-platform'), (string) $data['trip']);
		}

		if (! empty($data['email'])) {
			$lines[] = sprintf(__('Email: %s', 'twostars-platform'), (string) $data['email']);
		}

		$lines[] = '';
		$lines[] = __('Review Text:', 'twostars-platform');
		$lines[] = (string) ($data['review'] ?? '');
		$lines[] = '';
		$lines[] = sprintf(__('Open in admin: %s', 'twostars-platform'), $edit_url);

		wp_mail(
			$admin_email,
			__('New Two Stars Review Submission', 'twostars-platform'),
			implode("\n", $lines)
		);
	}
}
