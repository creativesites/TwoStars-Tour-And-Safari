<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class BookingStepsWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-booking-steps';
	}

	public function get_title(): string
	{
		return __('Booking Steps', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-numbers';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Steps', 'twostars-elementor-widgets')]);
		$this->add_control('heading', ['label' => __('Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('How Booking Works', 'twostars-elementor-widgets')]);
		$this->add_control('intro', ['label' => __('Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('The goal is to make the first step feel clear. The call is used to align on route, timing, and fit before we move deeper into pricing and itinerary detail.', 'twostars-elementor-widgets')]);

		$repeater = new Repeater();
		$repeater->add_control('step_number', ['label' => __('Step Number', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => '01']);
		$repeater->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Schedule a Call', 'twostars-elementor-widgets')]);
		$repeater->add_control('description', ['label' => __('Description', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Choose a planning slot that works for your calendar.', 'twostars-elementor-widgets')]);

		$this->add_control(
			'steps',
			[
				'label' => __('Items', 'twostars-elementor-widgets'),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'title_field' => '{{{ step_number }}} {{{ title }}}',
				'default' => [
					['step_number' => '01', 'title' => __('Schedule a Call', 'twostars-elementor-widgets'), 'description' => __('Choose a planning slot that works for your calendar and share a few trip basics in advance.', 'twostars-elementor-widgets')],
					['step_number' => '02', 'title' => __('Refine the Route', 'twostars-elementor-widgets'), 'description' => __('We use the call to narrow regions, pacing, lodge level, and the travel style that fits best.', 'twostars-elementor-widgets')],
					['step_number' => '03', 'title' => __('Move to Proposal', 'twostars-elementor-widgets'), 'description' => __('After alignment, we shape the itinerary and next booking step with far more accuracy.', 'twostars-elementor-widgets')],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-booking-steps" data-twostars-animate="fade-in-up">
			<div class="twostars-booking-steps__header">
				<h2 class="twostars-booking-steps__title"><?php echo esc_html((string) ($settings['heading'] ?? '')); ?></h2>
				<p class="twostars-booking-steps__intro"><?php echo esc_html((string) ($settings['intro'] ?? '')); ?></p>
			</div>
			<div class="twostars-booking-steps__grid">
				<?php foreach (($settings['steps'] ?? []) as $index => $item) : ?>
					<article class="twostars-booking-steps__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.12, 2)); ?>s;">
						<p class="twostars-booking-steps__number"><?php echo esc_html((string) ($item['step_number'] ?? '')); ?></p>
						<h3><?php echo esc_html((string) ($item['title'] ?? '')); ?></h3>
						<p><?php echo esc_html((string) ($item['description'] ?? '')); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
