<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;
use TwoStars\Platform\Support\TourData;

final class TestimonialsGridWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-testimonials-grid';
	}

	public function get_title(): string
	{
		return __('Testimonials Grid', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-posts-grid';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('heading', ['label' => __('Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Explore Our Tours', 'twostars-elementor-widgets')]);
		$this->add_control('posts_to_show', ['label' => __('Tours To Show', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 24]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$items = TourContent::get_tours(['posts_per_page' => absint($settings['posts_to_show'] ?? 3)]);

		if ($items === []) {
			return;
		}
		?>
		<section class="twostars-testimonials-grid" data-twostars-animate="fade-in-up">
			<h2 class="twostars-testimonials-grid__title"><?php echo esc_html((string) ($settings['heading'] ?? '')); ?></h2>
			<div class="twostars-tours-grid">
				<?php foreach ($items as $index => $item) : ?>
					<?php
					$image_id          = get_post_thumbnail_id($item->ID);
					$summary_items     = class_exists(TourData::class) ? TourData::get_summary_items($item->ID) : [];
					$availability      = class_exists(TourData::class) ? TourData::get_availability_label($item->ID) : '';
					$destination_ids   = TourContent::get_linked_destination_ids($item->ID);
					$booking_url       = class_exists(TourData::class) ? TourData::get_booking_url($item->ID) : home_url('/booking/');
					$booking_label     = class_exists(TourData::class) ? TourData::get_booking_label($item->ID) : __('Book This Tour', 'twostars-elementor-widgets');
					?>
					<article
						class="twostars-tours-grid__card"
						data-twostars-animate="fade-in-up"
						style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.14, 2)); ?>s;"
					>
						<div class="twostars-tours-grid__media">
							<?php if ($image_id > 0) : ?>
								<?php echo wp_get_attachment_image($image_id, 'large', false, ['loading' => 'lazy']); ?>
							<?php else : ?>
								<div class="twostars-tours-grid__placeholder"></div>
							<?php endif; ?>
							<div class="twostars-tours-grid__media-overlay"></div>
							<?php if ($availability !== '') : ?>
								<span class="twostars-tours-grid__badge"><?php echo esc_html($availability); ?></span>
							<?php endif; ?>
						</div>
						<div class="twostars-tours-grid__body">
							<p class="twostars-tours-grid__eyebrow"><?php esc_html_e('Two Stars Tour', 'twostars-elementor-widgets'); ?></p>
							<h3 class="twostars-tours-grid__title">
								<a href="<?php echo esc_url(get_permalink($item)); ?>"><?php echo esc_html(get_the_title($item)); ?></a>
							</h3>
							
							<?php if ($destination_ids !== []) : ?>
								<div class="twostars-tours-grid__destinations">
									<?php foreach (array_slice($destination_ids, 0, 3) as $destination_id) : ?>
										<?php $destination = get_post($destination_id); ?>
										<?php if ($destination instanceof \WP_Post) : ?>
											<a class="twostars-tours-grid__destination-chip" href="<?php echo esc_url(get_permalink($destination)); ?>">
												<?php echo esc_html($destination->post_title); ?>
											</a>
										<?php endif; ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ($summary_items !== []) : ?>
								<ul class="twostars-tours-grid__meta">
									<?php foreach (array_slice($summary_items, 0, 4) as $summary) : ?>
										<li>
											<span><?php echo esc_html($summary['label']); ?></span>
											<strong><?php echo esc_html($summary['value']); ?></strong>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>

							<div class="twostars-tours-grid__actions">
								<a class="twostars-safari-button twostars-safari-button--primary" href="<?php echo esc_url(get_permalink($item)); ?>">
									<?php esc_html_e('Explore Tour', 'twostars-elementor-widgets'); ?>
								</a>
								<a class="twostars-tours-grid__secondary-link" href="<?php echo esc_url($booking_url); ?>"<?php echo str_contains($booking_url, 'calendly.com/') ? ' target="_blank" rel="noopener"' : ''; ?>>
									<?php echo esc_html($booking_label); ?>
								</a>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
