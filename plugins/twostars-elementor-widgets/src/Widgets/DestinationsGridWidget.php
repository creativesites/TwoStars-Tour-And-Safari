<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;

final class DestinationsGridWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-destinations-grid';
	}

	public function get_title(): string
	{
		return __('Destinations Grid', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-gallery-grid';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Grid Settings', 'twostars-elementor-widgets')]);
		$this->add_control('section_label', ['label' => __('Section Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Destination Collection', 'twostars-elementor-widgets')]);
		$this->add_control('section_title', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Explore the Stops Behind the Tours', 'twostars-elementor-widgets')]);
		$this->add_control('section_intro', ['label' => __('Section Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Open a destination to see what it offers and which tours include it.', 'twostars-elementor-widgets')]);
		$this->add_control('posts_per_page', ['label' => __('Destinations To Show', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 12, 'min' => 1, 'max' => 30]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$destinations = TourContent::get_destinations(['posts_per_page' => absint($settings['posts_per_page'] ?? 12)]);
		?>
		<section class="twostars-destinations-grid-section">
			<div class="twostars-destinations-grid-section__header" data-twostars-animate="fade-in-up">
				<?php if (! empty($settings['section_label'])) : ?>
					<p class="twostars-destinations-grid-section__label"><?php echo esc_html((string) $settings['section_label']); ?></p>
				<?php endif; ?>
				<?php if (! empty($settings['section_title'])) : ?>
					<h2 class="twostars-destinations-grid-section__title"><?php echo esc_html((string) $settings['section_title']); ?></h2>
				<?php endif; ?>
				<?php if (! empty($settings['section_intro'])) : ?>
					<p class="twostars-destinations-grid-section__intro"><?php echo esc_html((string) $settings['section_intro']); ?></p>
				<?php endif; ?>
			</div>
			<div class="twostars-destinations-grid">
				<?php foreach ($destinations as $index => $destination) : ?>
					<?php
					$image_id = get_post_thumbnail_id($destination->ID);
					$related = TourContent::get_destination_related_tours($destination->ID, 2);
					?>
					<article class="twostars-destinations-grid__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.14, 2)); ?>s;">
						<div class="twostars-destinations-grid__media">
							<?php if ($image_id > 0) : ?>
								<?php echo wp_get_attachment_image($image_id, 'large', false, ['loading' => 'lazy']); ?>
							<?php else : ?>
								<div class="twostars-destinations-grid__placeholder"></div>
							<?php endif; ?>
						</div>
						<div class="twostars-destinations-grid__body">
							<p class="twostars-destinations-grid__eyebrow"><?php esc_html_e('Destination', 'twostars-elementor-widgets'); ?></p>
							<h2><a href="<?php echo esc_url(get_permalink($destination)); ?>"><?php echo esc_html(get_the_title($destination)); ?></a></h2>
							<p><?php echo esc_html(get_the_excerpt($destination)); ?></p>
							<div class="twostars-destinations-grid__meta">
								<span><?php echo esc_html($related !== [] ? sprintf(_n('%d linked tour', '%d linked tours', count($related), 'twostars-elementor-widgets'), count($related)) : __('Scenic stop', 'twostars-elementor-widgets')); ?></span>
							</div>
							<?php if ($related !== []) : ?>
								<div class="twostars-destinations-grid__related">
									<span><?php esc_html_e('Often paired with:', 'twostars-elementor-widgets'); ?></span>
									<?php foreach ($related as $tour) : ?>
										<em><?php echo esc_html(get_the_title($tour)); ?></em>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<a class="twostars-destinations-grid__link" href="<?php echo esc_url(get_permalink($destination)); ?>">
								<?php esc_html_e('Explore Destination', 'twostars-elementor-widgets'); ?>
								<span aria-hidden="true">→</span>
							</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
