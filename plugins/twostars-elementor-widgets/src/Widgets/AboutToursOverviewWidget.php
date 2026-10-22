<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use TwoStars\ElementorWidgets\Support\TourContent;

final class AboutToursOverviewWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-about-tours-overview';
	}

	public function get_title(): string
	{
		return __('About Tours Overview', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-posts-grid';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('section_label', ['label' => __('Section Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Our Tours', 'twostars-elementor-widgets')]);
		$this->add_control('section_title', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Explore Our Adventures', 'twostars-elementor-widgets')]);
		$this->add_control('section_subtitle', ['label' => __('Section Subtitle', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('From relaxed lagoon outings to full-day desert highlights, the current collection focuses on the places Two Stars knows best.', 'twostars-elementor-widgets')]);
		$this->add_control(
			'auto_populate_tours',
			[
				'label'        => __('Auto-populate Tours', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);
		$this->add_control('posts_to_show', ['label' => __('Tours To Show', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 5, 'min' => 1, 'max' => 12, 'condition' => ['auto_populate_tours' => 'yes']]);
		$this->add_control(
			'grid_columns',
			[
				'label'   => __('Grid Columns', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'2' => __('2 Columns', 'twostars-elementor-widgets'),
					'3' => __('3 Columns', 'twostars-elementor-widgets'),
				],
				'default' => '3',
			]
		);

		$repeater = new Repeater();
		$repeater->add_control('image', ['label' => __('Tour Image', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA]);
		$repeater->add_control('title', ['label' => __('Tour Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Tour Title', 'twostars-elementor-widgets')]);
		$repeater->add_control('description', ['label' => __('Tour Description', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Tour description goes here.', 'twostars-elementor-widgets')]);
		$repeater->add_control('link', ['label' => __('Tour Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL]);
		$this->add_control(
			'manual_cards',
			[
				'label'       => __('Manual Tour Cards', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'condition'   => ['auto_populate_tours' => ''],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$columns = in_array((string) ($settings['grid_columns'] ?? '3'), ['2', '3'], true) ? (string) $settings['grid_columns'] : '3';
		$tours = [];

		if (($settings['auto_populate_tours'] ?? 'yes') === 'yes') {
			$tours = TourContent::get_tours(['posts_per_page' => absint($settings['posts_to_show'] ?? 5)]);
		}
		?>
		<section class="twostars-about-tours">
			<div class="twostars-about-tours__header" data-twostars-animate="fade-in-up">
				<?php if (! empty($settings['section_label'])) : ?>
					<p class="twostars-about-tours__label"><?php echo esc_html((string) $settings['section_label']); ?></p>
				<?php endif; ?>
				<?php if (! empty($settings['section_title'])) : ?>
					<h2 class="twostars-about-tours__title"><?php echo esc_html((string) $settings['section_title']); ?></h2>
				<?php endif; ?>
				<?php if (! empty($settings['section_subtitle'])) : ?>
					<p class="twostars-about-tours__subtitle"><?php echo esc_html((string) $settings['section_subtitle']); ?></p>
				<?php endif; ?>
			</div>
			<div class="twostars-about-tours__grid twostars-about-tours__grid--cols-<?php echo esc_attr($columns); ?>">
				<?php if (($settings['auto_populate_tours'] ?? 'yes') === 'yes') : ?>
					<?php foreach ($tours as $index => $tour) : ?>
						<?php $image_id = get_post_thumbnail_id($tour->ID); ?>
						<article class="twostars-about-tours__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.12, 2)); ?>s;">
							<a class="twostars-about-tours__media" href="<?php echo esc_url(get_permalink($tour)); ?>">
								<?php if ($image_id > 0) : ?>
									<?php echo wp_get_attachment_image($image_id, 'large', false, ['class' => 'twostars-about-tours__image', 'loading' => 'lazy']); ?>
								<?php else : ?>
									<div class="twostars-about-tours__image twostars-about-tours__image--placeholder"></div>
								<?php endif; ?>
							</a>
							<div class="twostars-about-tours__card-body">
								<h3 class="twostars-about-tours__card-title"><?php echo esc_html(get_the_title($tour)); ?></h3>
								<p class="twostars-about-tours__card-text"><?php echo esc_html(get_the_excerpt($tour)); ?></p>
								<a class="twostars-about-tours__link" href="<?php echo esc_url(get_permalink($tour)); ?>">
									<?php esc_html_e('Learn More', 'twostars-elementor-widgets'); ?>
									<span aria-hidden="true">→</span>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach (($settings['manual_cards'] ?? []) as $index => $card) : ?>
						<article class="twostars-about-tours__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.12, 2)); ?>s;">
							<div class="twostars-about-tours__media">
								<?php if (! empty($card['image']['id'])) : ?>
									<?php echo wp_get_attachment_image(absint($card['image']['id']), 'large', false, ['class' => 'twostars-about-tours__image', 'loading' => 'lazy']); ?>
								<?php else : ?>
									<div class="twostars-about-tours__image twostars-about-tours__image--placeholder"></div>
								<?php endif; ?>
							</div>
							<div class="twostars-about-tours__card-body">
								<h3 class="twostars-about-tours__card-title"><?php echo esc_html((string) ($card['title'] ?? '')); ?></h3>
								<p class="twostars-about-tours__card-text"><?php echo esc_html((string) ($card['description'] ?? '')); ?></p>
								<?php if (! empty($card['link']['url'])) : ?>
									<a class="twostars-about-tours__link" <?php echo $this->link_attributes((array) $card['link']); ?>>
										<?php esc_html_e('Learn More', 'twostars-elementor-widgets'); ?>
										<span aria-hidden="true">→</span>
									</a>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
