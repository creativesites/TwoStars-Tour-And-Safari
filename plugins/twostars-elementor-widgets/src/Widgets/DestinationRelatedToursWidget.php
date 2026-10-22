<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;
use TwoStars\Platform\Support\TourData;

final class DestinationRelatedToursWidget extends SingleDestinationWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-destination-related-tours';
	}

	public function get_title(): string
	{
		return __('Destination Related Tours', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-post-list';
	}

	protected function register_controls(): void
	{
		$this->register_destination_source_controls();
		$this->start_controls_section('content_section', ['label' => __('Display', 'twostars-elementor-widgets')]);
		$this->add_control('heading', ['label' => __('Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Tours That Use This Region', 'twostars-elementor-widgets')]);
		$this->add_control('posts_to_show', ['label' => __('Tours To Show', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 6]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$destination = $this->resolve_destination_post();
		$settings = $this->get_settings_for_display();

		if (! $destination) {
			return;
		}

		$tours = TourContent::get_destination_related_tours($destination->ID, absint($settings['posts_to_show'] ?? 3));

		if ($tours === []) {
			return;
		}
		?>
		<section class="twostars-destination-related-tours" data-twostars-animate="fade-in-up">
			<?php if (! empty($settings['heading'])) : ?>
				<h2><?php echo esc_html((string) $settings['heading']); ?></h2>
			<?php endif; ?>
			<div class="twostars-tours-grid">
				<?php foreach ($tours as $index => $tour) : ?>
					<?php
					$image_id          = get_post_thumbnail_id($tour->ID);
					$summary_items     = class_exists(TourData::class) ? TourData::get_summary_items($tour->ID) : [];
					$availability      = class_exists(TourData::class) ? TourData::get_availability_label($tour->ID) : '';
					$destination_ids   = TourContent::get_linked_destination_ids($tour->ID);
					$booking_url       = class_exists(TourData::class) ? TourData::get_booking_url($tour->ID) : home_url('/booking/');
					$booking_label     = class_exists(TourData::class) ? TourData::get_booking_label($tour->ID) : __('Book This Tour', 'twostars-elementor-widgets');
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
							<p class="twostars-tours-grid__eyebrow"><?php esc_html_e('Regional Tour', 'twostars-elementor-widgets'); ?></p>
							<h3 class="twostars-tours-grid__title">
								<a href="<?php echo esc_url(get_permalink($tour)); ?>"><?php echo esc_html(get_the_title($tour)); ?></a>
							</h3>
							<p class="twostars-tours-grid__excerpt"><?php echo esc_html(get_the_excerpt($tour)); ?></p>
							
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
									<?php foreach (array_slice($summary_items, 0, 4) as $item) : ?>
										<li>
											<span><?php echo esc_html($item['label']); ?></span>
											<strong><?php echo esc_html($item['value']); ?></strong>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>

							<div class="twostars-tours-grid__actions">
								<a class="twostars-safari-button twostars-safari-button--primary" href="<?php echo esc_url(get_permalink($tour)); ?>">
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
