<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;
use TwoStars\Platform\Support\TourData;

final class FeaturedTourWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-featured-tour';
	}

	public function get_title(): string
	{
		return __('Featured Tour Block', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-star';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);

		$this->add_control('label', ['label' => __('Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Signature Tour', 'twostars-elementor-widgets')]);
		$this->add_control('tour_id', ['label' => __('Featured Tour', 'twostars-elementor-widgets'), 'type' => Controls_Manager::SELECT, 'options' => $this->get_tour_options(), 'default' => '']);
		$this->add_control('intro', ['label' => __('Intro Copy', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Lead with the route that best explains the Two Stars offer: a scenic Walvis Bay start, iconic coastal stops, and the drama of dune driving where the desert meets the Atlantic.', 'twostars-elementor-widgets')]);
		$this->add_control('note', ['label' => __('Supporting Note', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('This is the clearest first tour for guests who want to understand why Two Stars focuses on the Walvis Bay and Sandwich Harbour route.', 'twostars-elementor-widgets')]);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$tour_id  = absint($settings['tour_id'] ?? 0);

		if ($tour_id <= 0) {
			return;
		}

		$tour = get_post($tour_id);

		if (! $tour instanceof \WP_Post) {
			return;
		}

		$summary_items     = class_exists(TourData::class) ? TourData::get_summary_items($tour_id) : [];
		$image_id          = get_post_thumbnail_id($tour_id);
		$destination_chips = $this->get_destination_chips(TourContent::get_linked_destination_ids($tour_id));
		$booking_url       = class_exists(TourData::class) ? TourData::get_booking_url($tour_id) : home_url('/booking/');
		$booking_label     = class_exists(TourData::class) ? TourData::get_booking_label($tour_id) : __('Book This Tour', 'twostars-elementor-widgets');
		$destination_summary = implode(' • ', array_map(static fn(array $chip): string => $chip['label'], $destination_chips));
		?>
		<section class="twostars-featured-tour" data-twostars-animate="fade-in-up">
			<div class="twostars-featured-tour__shell">
				<div class="twostars-featured-tour__media">
					<?php if ($image_id > 0) : ?>
						<?php echo wp_get_attachment_image($image_id, 'large', false, ['loading' => 'lazy']); ?>
					<?php else : ?>
						<div class="twostars-featured-tour__placeholder"></div>
					<?php endif; ?>
					<div class="twostars-featured-tour__media-overlay" aria-hidden="true"></div>
					<div class="twostars-featured-tour__media-card">
						<p class="twostars-featured-tour__media-eyebrow"><?php esc_html_e('Signature Coastal Route', 'twostars-elementor-widgets'); ?></p>
						<?php if ($destination_summary !== '') : ?>
							<p class="twostars-featured-tour__media-summary"><?php echo esc_html($destination_summary); ?></p>
						<?php endif; ?>
					</div>
				</div>
				<div class="twostars-featured-tour__content">
					<div class="twostars-featured-tour__header">
						<?php if (! empty($settings['label'])) : ?>
							<p class="twostars-featured-tour__label"><?php echo esc_html((string) $settings['label']); ?></p>
						<?php endif; ?>
						<h2 class="twostars-featured-tour__title"><?php echo esc_html(get_the_title($tour)); ?></h2>
						<?php if (! empty($settings['intro'])) : ?>
							<p class="twostars-featured-tour__intro"><?php echo esc_html((string) $settings['intro']); ?></p>
						<?php endif; ?>
					</div>
					<p class="twostars-featured-tour__excerpt"><?php echo esc_html(get_the_excerpt($tour)); ?></p>
					<?php if ($destination_chips !== []) : ?>
						<div class="twostars-featured-tour__route">
							<p class="twostars-featured-tour__route-label"><?php esc_html_e('Key Stops', 'twostars-elementor-widgets'); ?></p>
							<div class="twostars-featured-tour__destinations">
								<?php foreach ($destination_chips as $chip) : ?>
									<a class="twostars-featured-tour__destination-chip" href="<?php echo esc_url($chip['url']); ?>">
										<?php echo esc_html($chip['label']); ?>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
					<?php if ($summary_items !== []) : ?>
						<ul class="twostars-featured-tour__meta">
							<?php foreach (array_slice($summary_items, 0, 4) as $item) : ?>
								<li><span><?php echo esc_html($item['label']); ?></span><strong><?php echo esc_html($item['value']); ?></strong></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if (! empty($settings['note'])) : ?>
						<div class="twostars-featured-tour__note">
							<p><?php echo esc_html((string) $settings['note']); ?></p>
						</div>
					<?php endif; ?>
					<div class="twostars-featured-tour__actions">
						<a class="twostars-safari-button twostars-safari-button--primary" href="<?php echo esc_url(get_permalink($tour)); ?>">
							<?php esc_html_e('View Signature Tour', 'twostars-elementor-widgets'); ?>
						</a>
						<a class="twostars-safari-button twostars-safari-button--ghost" href="<?php echo esc_url($booking_url); ?>"<?php echo str_contains($booking_url, 'calendly.com/') ? ' target="_blank" rel="noopener"' : ''; ?>>
							<?php echo esc_html($booking_label); ?>
						</a>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * @return array<string, string>
	 */
	private function get_tour_options(): array
	{
		$posts = get_posts(
			[
				'post_type'      => 'tour',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$options = ['' => __('Select a tour', 'twostars-elementor-widgets')];

		foreach ($posts as $post) {
			$options[(string) $post->ID] = $post->post_title;
		}

		return $options;
	}

	/**
	 * @param int[] $destination_ids
	 * @return array<int, array{label:string,url:string}>
	 */
	private function get_destination_chips(array $destination_ids): array
	{
		$chips = [];

		foreach (array_slice($destination_ids, 0, 4) as $destination_id) {
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
