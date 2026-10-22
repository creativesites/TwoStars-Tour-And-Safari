<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;

final class SingleDestinationOverviewWidget extends SingleDestinationWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-single-destination-overview';
	}

	public function get_title(): string
	{
		return __('Single Destination Overview', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-text';
	}

	protected function register_controls(): void
	{
		$this->register_destination_source_controls();
		$this->start_controls_section('content_section', ['label' => __('Display', 'twostars-elementor-widgets')]);
		$this->add_control('heading', ['label' => __('Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Why This Region Matters', 'twostars-elementor-widgets')]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$destination = $this->resolve_destination_post();
		$settings = $this->get_settings_for_display();

		if (! $destination) {
			return;
		}
		?>
		<section class="twostars-single-destination-overview" data-twostars-animate="fade-in-up">
			<h2><?php echo esc_html((string) ($settings['heading'] ?? '')); ?></h2>
			<div class="twostars-single-destination-overview__content">
				<?php echo wpautop(wp_kses_post($destination->post_content)); ?>
			</div>
		</section>
		<?php
	}
}
