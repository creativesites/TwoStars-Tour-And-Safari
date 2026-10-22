<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class AboutSectionWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-about-section';
	}

	public function get_title(): string
	{
		return __('Safari About Section', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-image-rollover';
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
			'label',
			[
				'label'   => __('Section Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('OUR STORY', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => __('Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Adventure Redefined', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'lead_text',
			[
				'label'   => __('Lead Paragraph', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Two Stars creates vibrant Namibia adventures that balance big scenery, social moments, and practical local know-how.', 'twostars-elementor-widgets'),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'paragraph',
			[
				'label'   => __('Paragraph', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('From desert camps and wildlife routes to coastal detours and timing on the ground, each itinerary is shaped to feel exciting without becoming chaotic.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'body_paragraphs',
			[
				'label'       => __('Body Paragraphs', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ paragraph }}}',
				'default'     => [
					[
						'paragraph' => __('Our approach is simple: strong logistics, well-paced travel days, and enough flexibility for the trip to feel personal and spontaneous.', 'twostars-elementor-widgets'),
					],
					[
						'paragraph' => __('That balance is what lets the website feel energetic and welcoming while still showing real travel expertise.', 'twostars-elementor-widgets'),
					],
				],
			]
		);

		$this->add_control(
			'image_primary',
			[
				'label' => __('Primary Image', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::MEDIA,
			]
		);

		$this->add_control(
			'image_secondary',
			[
				'label' => __('Secondary Image', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::MEDIA,
			]
		);

		$this->add_control(
			'cta_text',
			[
				'label'   => __('CTA Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Discover Our Story', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'cta_link',
			[
				'label'       => __('CTA Link', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::URL,
				'placeholder' => home_url('/about/'),
				'default'     => [
					'url' => home_url('/about/'),
				],
			]
		);

		$stats_repeater = new Repeater();

		$stats_repeater->add_control(
			'number',
			[
				'label'   => __('Number', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('10+', 'twostars-elementor-widgets'),
			]
		);

		$stats_repeater->add_control(
			'stat_label',
			[
				'label'   => __('Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Years Experience', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'stats',
			[
				'label'       => __('Stats Counter', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $stats_repeater->get_controls(),
				'title_field' => '{{{ stat_label }}}',
				'default'     => [
					[
						'number'     => '15+',
						'stat_label' => __('Years Exploring', 'twostars-elementor-widgets'),
					],
					[
						'number'     => '250+',
						'stat_label' => __('Tours Led', 'twostars-elementor-widgets'),
					],
					[
						'number'     => '1000+',
						'stat_label' => __('Happy Travelers', 'twostars-elementor-widgets'),
					],
				],
			]
		);

		$this->add_control(
			'background_color',
			[
				'label'   => __('Background Color', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::COLOR,
				'default' => '#F7EDE2',
			]
		);

		$this->add_control(
			'enable_pattern',
			[
				'label'        => __('Show Pattern Overlay', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Show', 'twostars-elementor-widgets'),
				'label_off'    => __('Hide', 'twostars-elementor-widgets'),
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
				'--twostars-about-bg' => $settings['background_color'] ?? '#F5F1E8',
			]
		);
		?>
		<section class="twostars-about" <?php if ($inline_style !== '') : ?>style="<?php echo esc_attr($inline_style); ?>"<?php endif; ?>>
			<div class="twostars-about__decoration twostars-about__decoration--stripe" aria-hidden="true"></div>
			<div class="twostars-about__decoration twostars-about__decoration--star-lt" aria-hidden="true">★</div>
			<div class="twostars-about__decoration twostars-about__decoration--star-rb" aria-hidden="true">★</div>

			<?php if (($settings['enable_pattern'] ?? 'yes') === 'yes') : ?>
				<div class="twostars-about__pattern" aria-hidden="true"></div>
			<?php endif; ?>

			<div class="twostars-about__grid">
				<div class="twostars-about__content" data-twostars-animate="fade-in-left">
					<?php if (! empty($settings['label'])) : ?>
						<p class="twostars-about__label"><?php echo esc_html((string) $settings['label']); ?></p>
					<?php endif; ?>

					<?php if (! empty($settings['title'])) : ?>
						<h2 class="twostars-about__title"><?php echo esc_html((string) $settings['title']); ?></h2>
					<?php endif; ?>

					<?php if (! empty($settings['lead_text'])) : ?>
						<p class="twostars-about__lead"><?php echo $this->format_text((string) $settings['lead_text']); ?></p>
					<?php endif; ?>

					<?php foreach (($settings['body_paragraphs'] ?? []) as $item) : ?>
						<?php if (empty($item['paragraph'])) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<p class="twostars-about__body"><?php echo $this->format_text((string) $item['paragraph']); ?></p>
					<?php endforeach; ?>

					<?php if (! empty($settings['cta_text']) && ! empty($settings['cta_link']['url'])) : ?>
						<a class="twostars-safari-button twostars-safari-button--primary" <?php echo $this->link_attributes((array) $settings['cta_link']); ?>>
							<?php echo esc_html((string) $settings['cta_text']); ?>
						</a>
					<?php endif; ?>

					<?php if (! empty($settings['stats'])) : ?>
						<div class="twostars-about__stats">
							<?php foreach ((array) $settings['stats'] as $stat) : ?>
								<?php if (empty($stat['number']) && empty($stat['stat_label'])) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<div class="twostars-about__stat">
									<p class="twostars-about__stat-number"><?php echo esc_html((string) ($stat['number'] ?? '')); ?></p>
									<p class="twostars-about__stat-label"><?php echo esc_html((string) ($stat['stat_label'] ?? '')); ?></p>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="twostars-about__media" data-twostars-animate="fade-in-right" style="--twostars-delay:0.2s;">
					<?php if (! empty($settings['image_primary']['id'])) : ?>
						<div class="twostars-about__image twostars-about__image--primary">
							<?php echo wp_get_attachment_image(absint($settings['image_primary']['id']), 'large', false, ['loading' => 'lazy']); ?>
						</div>
					<?php endif; ?>
					<?php if (! empty($settings['image_secondary']['id'])) : ?>
						<div class="twostars-about__image twostars-about__image--secondary">
							<?php echo wp_get_attachment_image(absint($settings['image_secondary']['id']), 'large', false, ['loading' => 'lazy']); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
