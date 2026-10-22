<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class BookingAssuranceWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-booking-assurance';
	}

	public function get_title(): string
	{
		return __('Booking Assurance Grid', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-shield-check';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Assurance', 'twostars-elementor-widgets')]);
		$this->add_control('heading', ['label' => __('Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Why This Step Works', 'twostars-elementor-widgets')]);

		$repeater = new Repeater();
		$repeater->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Clear Next Step', 'twostars-elementor-widgets')]);
		$repeater->add_control('description', ['label' => __('Description', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Booking starts with alignment, not pressure.', 'twostars-elementor-widgets')]);

		$this->add_control(
			'items',
			[
				'label' => __('Items', 'twostars-elementor-widgets'),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default' => [
					['title' => __('Clear Next Step', 'twostars-elementor-widgets'), 'description' => __('The planning call is used to remove uncertainty before you commit deeper into the itinerary.', 'twostars-elementor-widgets')],
					['title' => __('Private Guidance', 'twostars-elementor-widgets'), 'description' => __('We shape the route around your trip goals rather than forcing you into a rigid package flow.', 'twostars-elementor-widgets')],
					['title' => __('Better Accuracy', 'twostars-elementor-widgets'), 'description' => __('Once we understand dates, pacing, and comfort expectations, the proposal becomes much stronger.', 'twostars-elementor-widgets')],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-booking-assurance" data-twostars-animate="fade-in-up">
			<h2 class="twostars-booking-assurance__title"><?php echo esc_html((string) ($settings['heading'] ?? '')); ?></h2>
			<div class="twostars-booking-assurance__grid">
				<?php foreach (($settings['items'] ?? []) as $index => $item) : ?>
					<article class="twostars-booking-assurance__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.12, 2)); ?>s;">
						<h3><?php echo esc_html((string) ($item['title'] ?? '')); ?></h3>
						<p><?php echo esc_html((string) ($item['description'] ?? '')); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
