<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\Platform\Support\TourData;

final class BookingCtaWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-booking-cta';
	}

	public function get_title(): string
	{
		return __('Booking CTA', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-call-to-action';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'tour_id',
			[
				'label'   => __('Tour', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_tour_options(),
				'default' => '',
			]
		);

		$this->add_control(
			'headline',
			[
				'label'   => __('Headline', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Plan your Two Stars tour', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'   => __('Button Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Start Booking', 'twostars-elementor-widgets'),
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$tour_id  = absint($settings['tour_id'] ?? 0);
		$has_tour = $tour_id > 0 && class_exists(TourData::class);
		$url      = $has_tour ? TourData::get_booking_url($tour_id) : home_url('/contact/');
		$label    = $has_tour ? TourData::get_booking_label($tour_id) : (string) ($settings['button_text'] ?? '');
		$is_calendly = $has_tour && TourData::is_calendly_booking($tour_id);
		?>
		<section class="twostars-widget-cta">
			<h3 class="twostars-widget-cta__title"><?php echo esc_html((string) ($settings['headline'] ?? '')); ?></h3>
			<p class="twostars-widget-cta__text">
				<?php if ($has_tour) : ?>
					<?php echo esc_html(get_the_title($tour_id)); ?>
				<?php else : ?>
					<?php esc_html_e('Select a tour to use its saved Calendly booking setup and CTA.', 'twostars-elementor-widgets'); ?>
				<?php endif; ?>
			</p>
			<a
				class="twostars-widget-button<?php echo $is_calendly ? ' twostars-calendly-trigger' : ''; ?>"
				href="<?php echo esc_url((string) $url); ?>"
				<?php if ($is_calendly) : ?>
					data-calendly-url="<?php echo esc_url((string) $url); ?>"
				<?php endif; ?>
			>
				<?php echo esc_html($label); ?>
			</a>
		</section>
		<?php
	}

	/**
	 * @return array<string, string>
	 */
	private function get_tour_options(): array
	{
		if (! class_exists(TourData::class)) {
			return [];
		}

		return ['' => __('Select a tour', 'twostars-elementor-widgets')] + TourData::get_tour_options();
	}
}
