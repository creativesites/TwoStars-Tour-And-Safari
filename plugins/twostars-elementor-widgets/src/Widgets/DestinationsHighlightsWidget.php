<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class DestinationsHighlightsWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-destinations-highlights';
	}

	public function get_title(): string
	{
		return __('Destinations Highlights', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-icon-box';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control('section_label', ['label' => __('Section Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Why These Places Matter', 'twostars-elementor-widgets')]);
		$this->add_control('section_title', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('The Stops That Make a Two Stars Day Feel Complete', 'twostars-elementor-widgets')]);
		$this->add_control('section_intro', ['label' => __('Section Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Two Stars tours are built around a tight coastal and desert circuit, with each destination adding a different kind of moment, from birdlife and salt pans to dune drama, town history, and granite scenery.', 'twostars-elementor-widgets')]);

		$repeater = new Repeater();
		$repeater->add_control('image_url', ['label' => __('Card Image URL', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => '']);
		$repeater->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Coastal Birdlife', 'twostars-elementor-widgets')]);
		$repeater->add_control('description', ['label' => __('Description', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Walvis Bay Lagoon adds calm water, flamingos, and a softer scenic start before the day shifts into dunes and salt pans.', 'twostars-elementor-widgets')]);

		$this->add_control(
			'items',
			[
				'label'       => __('Highlight Cards', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => [
					[
						'image_url'   => home_url('/wp-content/uploads/2026/03/WhatsApp-Image-2026-03-27-at-6.00.45-AM-9.jpeg'),
						'title'       => __('Coastal Birdlife', 'twostars-elementor-widgets'),
						'description' => __('Walvis Bay Lagoon adds flamingos, birdlife, and quiet coastal atmosphere before the route shifts into dunes and salt pans.', 'twostars-elementor-widgets'),
					],
					[
						'image_url'   => home_url('/wp-content/uploads/2026/03/WhatsApp-Image-2026-03-27-at-6.00.46-AM.jpeg'),
						'title'       => __('Dune & Ocean Contrast', 'twostars-elementor-widgets'),
						'description' => __('Sandwich Harbour and Dune 7 deliver the dramatic desert moments guests usually imagine when they picture Namibia.', 'twostars-elementor-widgets'),
					],
					[
						'image_url'   => home_url('/wp-content/uploads/2026/03/WhatsApp-Image-2026-03-27-at-5.55.41-AM-2.jpeg'),
						'title'       => __('Photography Stops', 'twostars-elementor-widgets'),
						'description' => __('Pink Salt Lake, scenic coastal roads, and granite backdrops give the tours visual rhythm and memorable photo breaks.', 'twostars-elementor-widgets'),
					],
					[
						'image_url'   => home_url('/wp-content/uploads/2026/03/WhatsApp-Image-2026-03-27-at-6.02.03-AM-2.jpeg'),
						'title'       => __('Easy Route Logic', 'twostars-elementor-widgets'),
						'description' => __('Because these places connect naturally from Walvis Bay and Swakopmund, the day feels smooth, practical, and well-paced.', 'twostars-elementor-widgets'),
					],
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$items = is_array($settings['items'] ?? null) ? $settings['items'] : [];
		?>
		<section class="twostars-destinations-highlights">
			<div class="twostars-destinations-highlights__inner">
				<div class="twostars-destinations-highlights__header" data-twostars-animate="fade-in-up">
					<?php if (! empty($settings['section_label'])) : ?>
						<p class="twostars-destinations-highlights__label"><?php echo esc_html((string) $settings['section_label']); ?></p>
					<?php endif; ?>
					<?php if (! empty($settings['section_title'])) : ?>
						<h2 class="twostars-destinations-highlights__title"><?php echo esc_html((string) $settings['section_title']); ?></h2>
					<?php endif; ?>
					<?php if (! empty($settings['section_intro'])) : ?>
						<p class="twostars-destinations-highlights__intro"><?php echo esc_html((string) $settings['section_intro']); ?></p>
					<?php endif; ?>
				</div>
				<div class="twostars-destinations-highlights__grid">
					<?php foreach ($items as $index => $item) : ?>
						<article class="twostars-destinations-highlights__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 4) * 0.1, 2)); ?>s;">
							<div class="twostars-destinations-highlights__media" aria-hidden="true">
								<?php if (! empty($item['image_url'])) : ?>
									<img src="<?php echo esc_url((string) $item['image_url']); ?>" alt="<?php echo esc_attr((string) ($item['title'] ?? '')); ?>" loading="lazy" decoding="async">
								<?php else : ?>
									<div class="twostars-destinations-highlights__media-placeholder"></div>
								<?php endif; ?>
								<div class="twostars-destinations-highlights__media-overlay"></div>
							</div>
							<div class="twostars-destinations-highlights__card-content">
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
}
