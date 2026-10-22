<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;
use TwoStars\Platform\Support\TourData;

final class ToursGridWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-tours-grid';
	}

	public function get_title(): string
	{
		return __('Tours Grid', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-posts-grid';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Grid Settings', 'twostars-elementor-widgets')]);

		$this->add_control('section_label', ['label' => __('Section Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Current Collection', 'twostars-elementor-widgets')]);
		$this->add_control('section_title', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Browse All Tours', 'twostars-elementor-widgets')]);
		$this->add_control('section_intro', ['label' => __('Section Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Compare the current Two Stars tours and open the route that fits your day best.', 'twostars-elementor-widgets')]);
		$this->add_control('group_id', ['label' => __('Filter Group ID', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => 'tours-main']);
		$this->add_control('posts_per_page', ['label' => __('Tours To Show', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 12, 'min' => 1, 'max' => 50]);
		$this->add_control(
			'empty_state_text',
			[
				'label'   => __('Empty State Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('No tours match that combination yet. Reach out directly and we will guide you to the closest fit or a private option.', 'twostars-elementor-widgets'),
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$group_id = sanitize_title((string) ($settings['group_id'] ?? 'tours-main'));
		$tours    = TourContent::get_tours(['posts_per_page' => absint($settings['posts_per_page'] ?? 12)]);
		?>
		<section class="twostars-tours-grid-section" id="tours-collection">
			<div class="twostars-tours-grid-section__header" data-twostars-animate="fade-in-up">
				<?php if (! empty($settings['section_label'])) : ?>
					<p class="twostars-tours-grid-section__label"><?php echo esc_html((string) $settings['section_label']); ?></p>
				<?php endif; ?>
				<?php if (! empty($settings['section_title'])) : ?>
					<h2 class="twostars-tours-grid-section__title"><?php echo esc_html((string) $settings['section_title']); ?></h2>
				<?php endif; ?>
				<?php if (! empty($settings['section_intro'])) : ?>
					<p class="twostars-tours-grid-section__intro"><?php echo esc_html((string) $settings['section_intro']); ?></p>
				<?php endif; ?>
			</div>
			<div class="twostars-tours-grid" data-twostars-tour-grid data-filter-group="<?php echo esc_attr($group_id); ?>">
				<?php foreach ($tours as $index => $tour) : ?>
					<?php
					$summary_items           = class_exists(TourData::class) ? TourData::get_summary_items($tour->ID) : [];
					$availability            = class_exists(TourData::class) ? TourData::get_availability_label($tour->ID) : '';
					$destination_ids         = TourContent::get_linked_destination_ids($tour->ID);
					$destination_chips       = $this->get_destination_chips($destination_ids);
					$image_id                = get_post_thumbnail_id($tour->ID);
					$booking_url             = class_exists(TourData::class) ? TourData::get_booking_url($tour->ID) : home_url('/booking/');
					$booking_label           = class_exists(TourData::class) ? TourData::get_booking_label($tour->ID) : __('Book This Tour', 'twostars-elementor-widgets');
					?>
					<article
						class="twostars-tours-grid__card"
						data-twostars-tour-card
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
							<p class="twostars-tours-grid__eyebrow"><?php esc_html_e('Current Tour', 'twostars-elementor-widgets'); ?></p>
							<h3 class="twostars-tours-grid__title">
								<a href="<?php echo esc_url(get_permalink($tour)); ?>"><?php echo esc_html(get_the_title($tour)); ?></a>
							</h3>
							<p class="twostars-tours-grid__excerpt"><?php echo esc_html(get_the_excerpt($tour)); ?></p>
							<?php if ($destination_chips !== []) : ?>
								<div class="twostars-tours-grid__destinations">
									<?php foreach ($destination_chips as $chip) : ?>
										<a class="twostars-tours-grid__destination-chip" href="<?php echo esc_url($chip['url']); ?>">
											<?php echo esc_html($chip['label']); ?>
										</a>
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
			<div class="twostars-tours-grid__empty" data-twostars-tour-empty data-filter-group="<?php echo esc_attr($group_id); ?>" hidden>
				<p><?php echo esc_html((string) ($settings['empty_state_text'] ?? '')); ?></p>
			</div>
		</section>
		<?php
	}

	/**
	 * @param int[] $destination_ids
	 * @return array<int, array{label:string,url:string}>
	 */
	private function get_destination_chips(array $destination_ids): array
	{
		$chips = [];

		foreach (array_slice($destination_ids, 0, 3) as $destination_id) {
			$destination = get_post($destination_id);

			if (! $destination instanceof \WP_Post) {
				continue;
			}

			$chips[] = [
				'label' => $destination->post_title,
				'url'   => get_permalink($destination),
			];
		}

		return $chips;
	}
}
