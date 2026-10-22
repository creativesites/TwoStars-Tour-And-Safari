<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;

final class FaqAccordionWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-faq-accordion';
	}

	public function get_title(): string
	{
		return __('FAQ Accordion', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-accordion';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control('section_label', ['label' => __('Section Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Planning Answers', 'twostars-elementor-widgets')]);
		$this->add_control('heading', ['label' => __('Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Frequently Asked Questions', 'twostars-elementor-widgets')]);
		$this->add_control('intro', ['label' => __('Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Use these answers to understand pickups, private options, payments, and what is included on the current Two Stars tours.', 'twostars-elementor-widgets')]);
		$this->add_control('posts_to_show', ['label' => __('FAQs To Show', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 8, 'min' => 1, 'max' => 30]);
		$this->add_control('open_first', ['label' => __('Open First Item', 'twostars-elementor-widgets'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes']);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$items = TourContent::get_faqs(['posts_per_page' => absint($settings['posts_to_show'] ?? 8)]);

		if ($items === []) {
			return;
		}
		?>
		<section class="twostars-faq-accordion" data-twostars-animate="fade-in-up">
			<div class="twostars-faq-accordion__header">
				<?php if (! empty($settings['section_label'])) : ?>
					<p class="twostars-faq-accordion__label"><?php echo esc_html((string) $settings['section_label']); ?></p>
				<?php endif; ?>
				<?php if (! empty($settings['heading'])) : ?>
					<h2 class="twostars-faq-accordion__title"><?php echo esc_html((string) $settings['heading']); ?></h2>
				<?php endif; ?>
				<?php if (! empty($settings['intro'])) : ?>
					<p class="twostars-faq-accordion__intro"><?php echo esc_html((string) $settings['intro']); ?></p>
				<?php endif; ?>
			</div>
			<div class="twostars-faq-accordion__items">
				<?php foreach ($items as $index => $item) : ?>
					<details class="twostars-faq-accordion__item" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 6) * 0.08, 2)); ?>s;" <?php echo $index === 0 && ($settings['open_first'] ?? 'yes') === 'yes' ? 'open' : ''; ?>>
						<summary class="twostars-faq-accordion__question">
							<span><?php echo esc_html(get_the_title($item)); ?></span>
						</summary>
						<div class="twostars-faq-accordion__answer">
							<?php echo wpautop(wp_kses_post($item->post_content)); ?>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
