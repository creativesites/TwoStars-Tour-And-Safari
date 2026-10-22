<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class AboutStatsWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-about-stats';
	}

	public function get_title(): string
	{
		return __('About Stats Strip', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-counter';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Stats', 'twostars-elementor-widgets')]);
		$repeater = new Repeater();
		$repeater->add_control('value', ['label' => __('Value', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Tailor-Made', 'twostars-elementor-widgets')]);
		$repeater->add_control('label', ['label' => __('Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Planning Style', 'twostars-elementor-widgets')]);
		$this->add_control(
			'stats',
			[
				'label' => __('Items', 'twostars-elementor-widgets'),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'title_field' => '{{{ value }}}',
				'default' => [
					['value' => __('Tailor-Made', 'twostars-elementor-widgets'), 'label' => __('Planning Style', 'twostars-elementor-widgets')],
					['value' => __('Namibia-Focused', 'twostars-elementor-widgets'), 'label' => __('Destination Depth', 'twostars-elementor-widgets')],
					['value' => __('Private Routes', 'twostars-elementor-widgets'), 'label' => __('Travel Format', 'twostars-elementor-widgets')],
					['value' => __('Premium Pacing', 'twostars-elementor-widgets'), 'label' => __('Guest Experience', 'twostars-elementor-widgets')],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-about-stats" data-twostars-animate="fade-in-up">
			<div class="twostars-about-stats__grid">
				<?php foreach (($settings['stats'] ?? []) as $item) : ?>
					<div class="twostars-about-stats__item">
						<strong><?php echo esc_html((string) ($item['value'] ?? '')); ?></strong>
						<span><?php echo esc_html((string) ($item['label'] ?? '')); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
