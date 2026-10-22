<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;

final class SectionHeaderWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-section-header';
	}

	public function get_title(): string
	{
		return __('Adventure Section Header', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-heading';
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
			'subtitle',
			[
				'label'   => __('Subtitle', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Adventure Highlights', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => __('Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Discover the tours, stories, and places that turn every trip into an experience.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'description',
			[
				'label'   => __('Description', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Built for energetic travelers who want vivid scenery, strong local guidance, and comfortable adventure from start to finish.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'   => __('Title Color', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::COLOR,
				'default' => '#1B3A6B',
			]
		);

		$this->add_control(
			'enable_animation',
			[
				'label'        => __('Enable Animation', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings     = $this->get_settings_for_display();
		$inline_style = $this->build_inline_vars(
			[
				'--twostars-section-title-color' => $settings['text_color'] ?? '#5C4033',
			]
		);
		$animate      = ($settings['enable_animation'] ?? 'yes') === 'yes';
		?>
		<header
			class="twostars-section-header"
			<?php if ($animate) : ?>
				data-twostars-animate="fade-in-up"
			<?php endif; ?>
			<?php if ($inline_style !== '') : ?>
				style="<?php echo esc_attr($inline_style); ?>"
			<?php endif; ?>
		>
			<?php if (! empty($settings['subtitle'])) : ?>
				<p class="twostars-section-header__subtitle"><?php echo esc_html((string) $settings['subtitle']); ?></p>
			<?php endif; ?>
			<?php if (! empty($settings['title'])) : ?>
				<h2 class="twostars-section-header__title"><?php echo nl2br(esc_html((string) $settings['title'])); ?></h2>
				<div class="twostars-section-header__underline" aria-hidden="true"></div>
			<?php endif; ?>
			<?php if (! empty($settings['description'])) : ?>
				<p class="twostars-section-header__description"><?php echo nl2br(esc_html((string) $settings['description'])); ?></p>
			<?php endif; ?>
		</header>
		<?php
	}
}
