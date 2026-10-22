<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;

final class SingleTourOverviewWidget extends SingleTourWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-single-tour-overview';
	}

	public function get_title(): string
	{
		return __('Single Tour Overview', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-text-area';
	}

	protected function register_controls(): void
	{
		$this->register_tour_source_controls();

		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Display', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'heading',
			[
				'label'   => __('Heading', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Tour Overview', 'twostars-elementor-widgets'),
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$tour = $this->resolve_tour_post();
		$settings = $this->get_settings_for_display();

		if (! $tour) {
			return;
		}
		?>
		<section class="twostars-single-tour-overview" data-twostars-animate="fade-in-up">
			<?php if (! empty($settings['heading'])) : ?>
				<h2 class="twostars-single-tour-overview__title"><?php echo esc_html((string) $settings['heading']); ?></h2>
			<?php endif; ?>
			<div class="twostars-single-tour-overview__content">
				<?php echo wpautop(wp_kses_post($tour->post_content)); ?>
			</div>
		</section>
		<?php
	}
}
