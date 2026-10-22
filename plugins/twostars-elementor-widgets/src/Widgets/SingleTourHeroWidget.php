<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;
use TwoStars\Platform\Support\TourData;

final class SingleTourHeroWidget extends SingleTourWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-single-tour-hero';
	}

	public function get_title(): string
	{
		return __('Single Tour Hero', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-post-title';
	}

	protected function register_controls(): void
	{
		$this->register_tour_source_controls();

		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'label',
			[
				'label'   => __('Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Namibia Tour', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'show_summary',
			[
				'label'        => __('Show Summary Facts', 'twostars-elementor-widgets'),
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
		$tour = $this->resolve_tour_post();

		if (! $tour) {
			return;
		}

		$summary_items = class_exists(TourData::class) ? TourData::get_summary_items($tour->ID) : [];
		$booking_url   = class_exists(TourData::class) ? TourData::get_booking_url($tour->ID) : get_permalink($tour);
		$booking_label = class_exists(TourData::class) ? TourData::get_booking_label($tour->ID) : __('Explore Tour', 'twostars-elementor-widgets');
		$tour_meta     = class_exists(TourData::class) ? TourData::get_tour_meta($tour->ID) : [];
		$image_id      = get_post_thumbnail_id($tour->ID);
		$terms         = TourContent::get_term_map($tour->ID, 'tour_type') + TourContent::get_term_map($tour->ID, 'tour_region');
		$settings      = $this->get_settings_for_display();
		?>
		<section class="twostars-single-tour-hero">
			<div class="twostars-single-tour-hero__media">
				<?php if ($image_id > 0) : ?>
					<?php echo wp_get_attachment_image($image_id, 'full', false, ['class' => 'twostars-single-tour-hero__image', 'loading' => 'eager']); ?>
				<?php else : ?>
					<div class="twostars-single-tour-hero__image twostars-single-tour-hero__image--placeholder"></div>
				<?php endif; ?>
			</div>
			<div class="twostars-single-tour-hero__overlay"></div>
			<div class="twostars-single-tour-hero__content">
				<div class="twostars-single-tour-hero__copy">
					<?php if (! empty($settings['label'])) : ?>
						<p class="twostars-single-tour-hero__label" data-twostars-animate="fade-in-up"><?php echo esc_html((string) $settings['label']); ?></p>
					<?php endif; ?>
					<h1 class="twostars-single-tour-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.12s;"><?php echo esc_html(get_the_title($tour)); ?></h1>
					<p class="twostars-single-tour-hero__excerpt" data-twostars-animate="fade-in-up" style="--twostars-delay:0.24s;"><?php echo esc_html(get_the_excerpt($tour)); ?></p>
					<?php if ($terms !== []) : ?>
						<div class="twostars-single-tour-hero__terms" data-twostars-animate="fade-in-up" style="--twostars-delay:0.36s;">
							<?php foreach (array_slice(array_values($terms), 0, 4) as $term_name) : ?>
								<span><?php echo esc_html($term_name); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<aside class="twostars-single-tour-hero__booking" data-twostars-animate="fade-in-up" style="--twostars-delay:0.3s;">
					<?php if (($settings['show_summary'] ?? 'yes') === 'yes' && $summary_items !== []) : ?>
						<ul class="twostars-single-tour-hero__summary">
							<?php foreach (array_slice($summary_items, 0, 5) as $item) : ?>
								<li><span><?php echo esc_html($item['label']); ?></span><strong><?php echo esc_html($item['value']); ?></strong></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<a class="twostars-safari-button twostars-safari-button--primary twostars-safari-button--block" href="<?php echo esc_url((string) $booking_url); ?>">
						<?php echo esc_html((string) $booking_label); ?>
					</a>
					<?php if (! empty($tour_meta['booking_notes'])) : ?>
						<p class="twostars-single-tour-hero__note"><?php echo esc_html((string) $tour_meta['booking_notes']); ?></p>
					<?php endif; ?>
				</aside>
			</div>
		</section>
		<?php
	}
}
