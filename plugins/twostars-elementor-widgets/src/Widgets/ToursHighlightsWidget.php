<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class ToursHighlightsWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-tours-highlights';
	}

	public function get_title(): string
	{
		return __('Tours Highlights', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-icon-box';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);

		$this->add_control('section_label', ['label' => __('Section Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Why Guests Book', 'twostars-elementor-widgets')]);
		$this->add_control('section_title', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Real Coastal Logistics, Real Stops, and Tours That Feel Easy to Enjoy', 'twostars-elementor-widgets')]);
		$this->add_control('section_intro', ['label' => __('Section Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Two Stars is not selling a huge list of abstract itineraries. The current collection is tight, practical, and built around the exact routes, stops, pickups, and pacing that work well from Walvis Bay and Swakopmund.', 'twostars-elementor-widgets')]);

		$repeater = new Repeater();
		$repeater->add_control(
			'icon',
			[
				'label'   => __('Icon', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-map-pin',
					'library' => 'fa-solid',
				],
			]
		);
		$repeater->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Local Departures', 'twostars-elementor-widgets')]);
		$repeater->add_control('description', ['label' => __('Description', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Tours leave from the coast, so the day starts close to the experience instead of being consumed by unnecessary transfer time.', 'twostars-elementor-widgets')]);

		$this->add_control(
			'items',
			[
				'label'       => __('Highlight Cards', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => [
					[
						'icon'        => ['value' => 'fas fa-map-pin', 'library' => 'fa-solid'],
						'title'       => __('Local Departures', 'twostars-elementor-widgets'),
						'description' => __('Walvis Bay and Swakopmund pickups keep the day efficient and coastal from the first stretch of road.', 'twostars-elementor-widgets'),
					],
					[
						'icon'        => ['value' => 'fas fa-camera', 'library' => 'fa-solid'],
						'title'       => __('Scenic Photo Rhythm', 'twostars-elementor-widgets'),
						'description' => __('Lagoon birdlife, pink salt, dunes, town landmarks, and granite scenery create natural visual variety through the day.', 'twostars-elementor-widgets'),
					],
					[
						'icon'        => ['value' => 'fas fa-wine-bottle', 'library' => 'fa-solid'],
						'title'       => __('Comfort on the Route', 'twostars-elementor-widgets'),
						'description' => __('Light snacks, refreshments, and practical pacing make the experience feel cared for instead of rushed.', 'twostars-elementor-widgets'),
					],
					[
						'icon'        => ['value' => 'fas fa-car-side', 'library' => 'fa-solid'],
						'title'       => __('Private Flexibility', 'twostars-elementor-widgets'),
						'description' => __('Guests who want a more personal day can shift to a private option with timing and route flexibility.', 'twostars-elementor-widgets'),
					],
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$items    = is_array($settings['items'] ?? null) ? $settings['items'] : [];
		?>
		<section class="twostars-tours-highlights">
			<div class="twostars-tours-highlights__inner">
				<div class="twostars-tours-highlights__header" data-twostars-animate="fade-in-up">
					<?php if (! empty($settings['section_label'])) : ?>
						<p class="twostars-tours-highlights__label"><?php echo esc_html((string) $settings['section_label']); ?></p>
					<?php endif; ?>
					<?php if (! empty($settings['section_title'])) : ?>
						<h2 class="twostars-tours-highlights__title"><?php echo esc_html((string) $settings['section_title']); ?></h2>
					<?php endif; ?>
					<?php if (! empty($settings['section_intro'])) : ?>
						<p class="twostars-tours-highlights__intro"><?php echo esc_html((string) $settings['section_intro']); ?></p>
					<?php endif; ?>
				</div>
				<div class="twostars-tours-highlights__grid">
					<?php foreach ($items as $index => $item) : ?>
						<article class="twostars-tours-highlights__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 4) * 0.1, 2)); ?>s;">
							<h3><?php echo esc_html((string) ($item['title'] ?? '')); ?></h3>
							<p><?php echo esc_html((string) ($item['description'] ?? '')); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
