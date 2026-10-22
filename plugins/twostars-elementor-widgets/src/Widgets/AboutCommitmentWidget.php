<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class AboutCommitmentWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-about-commitment';
	}

	public function get_title(): string
	{
		return __('About Commitment', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-check-circle';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Commitment', 'twostars-elementor-widgets')]);
		$this->add_control('section_title', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Our Promise to You', 'twostars-elementor-widgets')]);
		$this->add_control(
			'items_per_row',
			[
				'label'   => __('Items Per Row', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'1' => __('1', 'twostars-elementor-widgets'),
					'2' => __('2', 'twostars-elementor-widgets'),
				],
				'default' => '2',
			]
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'icon_name',
			[
				'label'   => __('Icon', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_lucide_icon_options(),
				'default' => 'shield-check',
			]
		);
		$repeater->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Safety First', 'twostars-elementor-widgets')]);
		$repeater->add_control('description', ['label' => __('Description', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Your safety and comfort stay central from pickup to return, with careful pacing and well-prepared vehicles.', 'twostars-elementor-widgets')]);
		$this->add_control(
			'commitment_items',
			[
				'label'       => __('Commitment Items', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => [
					['title' => __('Fully Registered', 'twostars-elementor-widgets'), 'description' => __('We are fully NTB registered and compliant (TSO01736), ensuring your safety and professional standards from pickup to return.', 'twostars-elementor-widgets'), 'icon_name' => 'shield-check'],
					['title' => __('Honest Planning', 'twostars-elementor-widgets'), 'description' => __('We guide guests toward the tour that genuinely fits their day, interests, and expectations.', 'twostars-elementor-widgets'), 'icon_name' => 'messages-square'],
					['title' => __('Respect for Nature', 'twostars-elementor-widgets'), 'description' => __('Wildlife, coastlines, and desert landscapes are treated with care so the experience stays responsible as well as memorable.', 'twostars-elementor-widgets'), 'icon_name' => 'leaf'],
					['title' => __('Clear Communication', 'twostars-elementor-widgets'), 'description' => __('Booking, pickups, and tour expectations stay simple and direct through the full journey.', 'twostars-elementor-widgets'), 'icon_name' => 'phone'],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$cols = ($settings['items_per_row'] ?? '2') === '1' ? '1' : '2';
		?>
		<section class="twostars-about-commitment">
			<div class="twostars-about-commitment__inner">
				<?php if (! empty($settings['section_title'])) : ?>
					<h2 class="twostars-about-commitment__title" data-twostars-animate="fade-in-up"><?php echo esc_html((string) $settings['section_title']); ?></h2>
				<?php endif; ?>
				<div class="twostars-about-commitment__grid twostars-about-commitment__grid--cols-<?php echo esc_attr($cols); ?>">
					<?php foreach (($settings['commitment_items'] ?? []) as $index => $item) : ?>
						<article class="twostars-about-commitment__item" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 4) * 0.1, 2)); ?>s;">
							<div class="twostars-about-commitment__icon" aria-hidden="true">
								<?php echo $this->render_lucide_icon($this->resolve_icon_name($item), 'twostars-about-commitment__icon-svg'); ?>
							</div>
							<div class="twostars-about-commitment__content">
								<h3><?php echo esc_html((string) ($item['title'] ?? '')); ?></h3>
								<p><?php echo esc_html((string) ($item['description'] ?? '')); ?></p>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private function resolve_icon_name(array $item): string
	{
		$icon_name = (string) ($item['icon_name'] ?? '');
		$title = strtolower((string) ($item['title'] ?? ''));

		if (str_contains($title, 'safety')) {
			return 'shield-check';
		}

		if (str_contains($title, 'planning') || str_contains($title, 'communication')) {
			return str_contains($title, 'communication') ? 'phone' : 'messages-square';
		}

		if (str_contains($title, 'nature')) {
			return 'leaf';
		}

		if ($icon_name !== '') {
			return $icon_name;
		}

		return 'check-circle';
	}
}
