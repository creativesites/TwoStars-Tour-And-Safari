<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\Platform\Integrations\CalendlyIntegration;

final class BookingCalendlyWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-booking-calendly';
	}

	public function get_title(): string
	{
		return __('Booking Calendly Embed', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-form-horizontal';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Calendly', 'twostars-elementor-widgets')]);
		$this->add_control('anchor_id', ['label' => __('Anchor ID', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => 'booking-schedule']);
		$this->add_control('heading', ['label' => __('Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Choose Your Planning Time', 'twostars-elementor-widgets')]);
		$this->add_control('intro', ['label' => __('Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Use the live schedule below to reserve a planning conversation. Pick the time that suits you and we will take it from there.', 'twostars-elementor-widgets')]);
		$this->add_control('event_url', ['label' => __('Calendly Event URL', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => 'https://calendly.com/creativesites263/30min'], 'placeholder' => 'https://calendly.com/...']);
		$this->add_control('min_height', ['label' => __('Embed Height', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 700, 'min' => 500, 'max' => 1200, 'step' => 10]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$url = trim((string) ($settings['event_url']['url'] ?? ''));

		if ($url === '' && class_exists(CalendlyIntegration::class)) {
			$url = CalendlyIntegration::get_default_event_url();
		}

		if ($url === '') {
			return;
		}

		$this->enqueue_calendly_assets();

		$anchor = sanitize_html_class((string) ($settings['anchor_id'] ?? 'booking-schedule'));
		$height = max(500, absint($settings['min_height'] ?? 700));
		?>
		<section id="<?php echo esc_attr($anchor); ?>" class="twostars-booking-calendly" data-twostars-animate="fade-in-up">
			<div class="twostars-booking-calendly__header">
				<h2 class="twostars-booking-calendly__title"><?php echo esc_html((string) ($settings['heading'] ?? '')); ?></h2>
				<p class="twostars-booking-calendly__intro"><?php echo esc_html((string) ($settings['intro'] ?? '')); ?></p>
			</div>
			<div class="twostars-booking-calendly__frame">
				<div class="calendly-inline-widget" data-url="<?php echo esc_url($url); ?>" style="min-width:320px;height:<?php echo esc_attr((string) $height); ?>px;"></div>
			</div>
		</section>
		<?php
	}

	private function enqueue_calendly_assets(): void
	{
		if (! wp_style_is('twostars-calendly-widget', 'registered')) {
			wp_register_style('twostars-calendly-widget', 'https://assets.calendly.com/assets/external/widget.css', [], null);
		}

		if (! wp_script_is('twostars-calendly-widget', 'registered')) {
			wp_register_script('twostars-calendly-widget', 'https://assets.calendly.com/assets/external/widget.js', [], null, true);
		}

		wp_enqueue_style('twostars-calendly-widget');
		wp_enqueue_script('twostars-calendly-widget');
	}
}
