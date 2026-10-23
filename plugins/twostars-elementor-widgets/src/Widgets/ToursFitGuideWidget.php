<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use TwoStars\ElementorWidgets\Support\TourContent;

final class ToursFitGuideWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-tours-fit-guide';
	}

	public function get_title(): string
	{
		return __('Tours Fit Guide', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-navigator';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);

		$this->add_control('section_label', ['label' => __('Section Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Choose by travel style', 'twostars-elementor-widgets')]);
		$this->add_control('section_title', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('A Quick Way to Pick the Right Tour', 'twostars-elementor-widgets')]);
		$this->add_control('section_intro', ['label' => __('Section Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('If you already know the kind of day you want, the fastest route to booking is to match that preference to the right current tour.', 'twostars-elementor-widgets')]);

		$repeater = new Repeater();
		$repeater->add_control('image_url', ['label' => __('Card Image URL', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => '']);
		$repeater->add_control('title', ['label' => __('Card Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('For dune drama and iconic photos', 'twostars-elementor-widgets')]);
		$repeater->add_control('description', ['label' => __('Description', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Pick the route that gives you the strongest Sandwich Harbour visual payoff and the signature desert-meets-ocean experience.', 'twostars-elementor-widgets')]);
		$repeater->add_control('tour_id', ['label' => __('Linked Tour', 'twostars-elementor-widgets'), 'type' => Controls_Manager::SELECT, 'options' => TourContent::get_tour_options(), 'default' => '']);
		$repeater->add_control('button_text', ['label' => __('Button Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('See Tour', 'twostars-elementor-widgets')]);

		$this->add_control(
			'items',
			[
				'label'       => __('Guide Cards', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => [
					[
						'image_url'   => home_url('/wp-content/uploads/2026/03/WhatsApp-Image-2026-03-27-at-5.55.41-AM-2-1.jpeg'),
						'title'       => __('For Sandwich Harbour moments', 'twostars-elementor-widgets'),
						'description' => __('Choose the route built around Sandwich Harbour, lagoon flamingos, pink salt, and 4x4 dune driving.', 'twostars-elementor-widgets'),
						'tour_id'     => '284',
						'button_text' => __('See Sandwich Harbour Tour', 'twostars-elementor-widgets'),
					],
					[
						'image_url'   => home_url('/wp-content/uploads/2026/03/WhatsApp-Image-2026-03-27-at-6.00.45-AM-8.jpeg'),
						'title'       => __('For a shorter relaxed scenic outing', 'twostars-elementor-widgets'),
						'description' => __('Choose the birdlife and photography-focused route around Walvis Bay Lagoon and Pink Salt Lake.', 'twostars-elementor-widgets'),
						'tour_id'     => '285',
						'button_text' => __('See Flamingo Tour', 'twostars-elementor-widgets'),
					],
					[
						'image_url'   => home_url('/wp-content/uploads/2026/03/swakopmund.jpg'),
						'title'       => __('For town landmarks and a desert stop', 'twostars-elementor-widgets'),
						'description' => __('Choose the Swakopmund and Dune 7 combination if you want both coastal town character and sand-slope scenery.', 'twostars-elementor-widgets'),
						'tour_id'     => '286',
						'button_text' => __('See Swakopmund + Dune 7', 'twostars-elementor-widgets'),
					],
					[
						'image_url'   => home_url('/wp-content/uploads/2026/03/caption-6.jpg'),
						'title'       => __('For a longer full-day scenic escape', 'twostars-elementor-widgets'),
						'description' => __('Choose Spitzkoppe if you want a wider inland day built around granite formations and landscape scale.', 'twostars-elementor-widgets'),
						'tour_id'     => '287',
						'button_text' => __('See Spitzkoppe Tour', 'twostars-elementor-widgets'),
					],
					[
						'image_url'   => home_url('/wp-content/uploads/2026/03/WhatsApp-Image-2026-03-27-at-60045-AM-2-Picsart-AiImageEnhancer.jpeg'),
						'title'       => __('For flexibility and personal pacing', 'twostars-elementor-widgets'),
						'description' => __('Choose the private option if you want a dedicated vehicle, more control over timing, and a more tailored day.', 'twostars-elementor-widgets'),
						'tour_id'     => '288',
						'button_text' => __('See Private Tour', 'twostars-elementor-widgets'),
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
		<section class="twostars-tours-fit-guide">
			<div class="twostars-tours-fit-guide__inner">
				<div class="twostars-tours-fit-guide__header" data-twostars-animate="fade-in-up">
					<?php if (! empty($settings['section_label'])) : ?>
						<p class="twostars-tours-fit-guide__label"><?php echo esc_html((string) $settings['section_label']); ?></p>
					<?php endif; ?>
					<?php if (! empty($settings['section_title'])) : ?>
						<h2 class="twostars-tours-fit-guide__title"><?php echo esc_html((string) $settings['section_title']); ?></h2>
					<?php endif; ?>
					<?php if (! empty($settings['section_intro'])) : ?>
						<p class="twostars-tours-fit-guide__intro"><?php echo esc_html((string) $settings['section_intro']); ?></p>
					<?php endif; ?>
				</div>
				<div class="twostars-tours-fit-guide__grid">
					<?php foreach ($items as $index => $item) : ?>
						<?php $tour_id = absint($item['tour_id'] ?? 0); ?>
						<article class="twostars-tours-fit-guide__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.12, 2)); ?>s;">
							<div class="twostars-tours-fit-guide__media" aria-hidden="true">
								<?php if (! empty($item['image_url'])) : ?>
									<img src="<?php echo esc_url((string) $item['image_url']); ?>" alt="<?php echo esc_attr((string) ($item['title'] ?? '')); ?>" loading="lazy" decoding="async">
								<?php else : ?>
									<div class="twostars-tours-fit-guide__media-placeholder"></div>
								<?php endif; ?>
								<div class="twostars-tours-fit-guide__media-overlay"></div>
							</div>
							<div class="twostars-tours-fit-guide__content">
							<h3><?php echo esc_html((string) ($item['title'] ?? '')); ?></h3>
							<p><?php echo esc_html((string) ($item['description'] ?? '')); ?></p>
							<?php if ($tour_id > 0) : ?>
								<a class="twostars-tours-fit-guide__link" href="<?php echo esc_url(get_permalink($tour_id)); ?>">
									<?php echo esc_html((string) ($item['button_text'] ?? __('See Tour', 'twostars-elementor-widgets'))); ?>
								</a>
							<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
