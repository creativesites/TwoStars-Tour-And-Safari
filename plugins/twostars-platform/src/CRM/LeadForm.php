<?php
declare(strict_types=1);

namespace TwoStars\Platform\CRM;

use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Support\TourData;

final class LeadForm implements Service
{
	public function register(): void
	{
		add_action('wp_enqueue_scripts', [$this, 'register_assets']);
		add_shortcode('twostars_lead_form', [$this, 'render_shortcode']);
	}

	public function register_assets(): void
	{
		wp_register_style(
			'twostars-platform-lead-form',
			TWOSTARS_PLATFORM_URL . 'assets/css/lead-form.css',
			[],
			TWOSTARS_PLATFORM_VERSION
		);

		wp_register_script(
			'twostars-platform-lead-form',
			TWOSTARS_PLATFORM_URL . 'assets/js/lead-form.js',
			[],
			TWOSTARS_PLATFORM_VERSION,
			true
		);
	}

	/**
	 * @param array<string, mixed> $atts
	 */
	public function render_shortcode(array $atts = []): string
	{
		return self::render_form($atts);
	}

	/**
	 * @param array<string, mixed> $atts
	 */
	public static function render_form(array $atts = []): string
	{
		wp_enqueue_style('twostars-platform-lead-form');
		wp_enqueue_script('twostars-platform-lead-form');

		wp_localize_script(
			'twostars-platform-lead-form',
			'twoStarsCrm',
			[
				'restUrl'            => esc_url_raw(rest_url('twostars/v1/crm/lead')),
				'defaultSource'      => 'website',
				'errorMessage'       => __('Something went wrong while sending your request. Please try again.', 'twostars-platform'),
				'successMessage'     => __('Thanks. Your inquiry has been received and the team can follow up now.', 'twostars-platform'),
				'minSubmitDelay'     => 3,
			]
		);

		$atts = shortcode_atts(
			[
				'title'       => __('Plan Your Safari', 'twostars-platform'),
				'description' => __('Tell us where you want to go, when you want to travel, and how we should reach you.', 'twostars-platform'),
				'source'      => 'website',
				'tour_id'     => 0,
			],
			$atts,
			'twostars_lead_form'
		);

		$selected_tour_id = absint($atts['tour_id']);

		if ($selected_tour_id === 0 && is_singular('tour')) {
			$selected_tour_id = get_the_ID();
		}

		ob_start();
		?>
		<form class="twostars-lead-form" data-twostars-lead-form>
			<div class="twostars-lead-form__header">
				<h2><?php echo esc_html((string) $atts['title']); ?></h2>
				<p><?php echo esc_html((string) $atts['description']); ?></p>
			</div>

			<div class="twostars-lead-form__grid">
				<label>
					<span><?php esc_html_e('Full Name', 'twostars-platform'); ?></span>
					<input type="text" name="full_name" required>
				</label>

				<label>
					<span><?php esc_html_e('Email', 'twostars-platform'); ?></span>
					<input type="email" name="email">
				</label>

				<label>
					<span><?php esc_html_e('Phone', 'twostars-platform'); ?></span>
					<input type="text" name="phone">
				</label>

				<label>
					<span><?php esc_html_e('Country', 'twostars-platform'); ?></span>
					<input type="text" name="country">
				</label>

				<label>
					<span><?php esc_html_e('Tour Interest', 'twostars-platform'); ?></span>
					<select name="tour_interest">
						<option value=""><?php esc_html_e('Select a tour', 'twostars-platform'); ?></option>
						<?php foreach (TourData::get_tour_options() as $tour_id => $tour_title) : ?>
							<option value="<?php echo esc_attr($tour_id); ?>" <?php selected((int) $tour_id, $selected_tour_id); ?>>
								<?php echo esc_html($tour_title); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>

				<label>
					<span><?php esc_html_e('Guest Count', 'twostars-platform'); ?></span>
					<input type="number" name="guest_count" min="1" step="1">
				</label>

				<label class="twostars-lead-form__full">
					<span><?php esc_html_e('Preferred Travel Dates', 'twostars-platform'); ?></span>
					<input type="text" name="travel_dates" placeholder="<?php esc_attr_e('Example: August 2026 or 12-18 August', 'twostars-platform'); ?>">
				</label>

				<label class="twostars-lead-form__full">
					<span><?php esc_html_e('Message', 'twostars-platform'); ?></span>
					<textarea name="message" rows="5" placeholder="<?php esc_attr_e('Share your itinerary ideas, budget range, group details, or questions.', 'twostars-platform'); ?>"></textarea>
				</label>
			</div>

			<label class="twostars-lead-form__consent">
				<input type="checkbox" name="contact_consent" value="1" required>
				<span><?php esc_html_e('I agree to be contacted about my Two Stars tour inquiry.', 'twostars-platform'); ?></span>
			</label>

			<input type="hidden" name="source" value="<?php echo esc_attr((string) $atts['source']); ?>">
			<input type="hidden" name="origin_url" value="<?php echo esc_url(home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''))); ?>">
			<input type="hidden" name="form_started_at" value="<?php echo esc_attr((string) time()); ?>">

			<div class="twostars-lead-form__actions">
				<button type="submit" class="tour-booking-button">
					<?php esc_html_e('Send Inquiry', 'twostars-platform'); ?>
				</button>
				<p class="twostars-lead-form__status" data-twostars-lead-form-status aria-live="polite"></p>
			</div>
		</form>
		<?php

		return (string) ob_get_clean();
	}
}
