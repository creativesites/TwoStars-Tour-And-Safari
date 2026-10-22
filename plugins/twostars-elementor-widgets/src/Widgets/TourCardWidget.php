<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\Platform\Support\TourData;

final class TourCardWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-tour-card';
	}

	public function get_title(): string
	{
		return __('Tour Card', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-post-list';
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
			'tour_id',
			[
				'label'   => __('Tour', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_tour_options(),
				'default' => '',
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => __('Title', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::TEXT,
				'default'     => __('Sandwich Harbour 4x4 Adventure', 'twostars-elementor-widgets'),
				'placeholder' => __('Enter card title', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'description',
			[
				'label'   => __('Description', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Starter widget for featured tours, itineraries, and category showcases.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'   => __('Button Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('View Tour', 'twostars-elementor-widgets'),
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$tour_id  = absint($settings['tour_id'] ?? 0);
		$tour     = $tour_id > 0 ? get_post($tour_id) : null;
		$meta     = $tour instanceof \WP_Post ? TourData::get_tour_meta($tour_id) : [];
		$url      = $tour instanceof \WP_Post ? get_permalink($tour_id) : home_url('/tours/');
		$title    = $tour instanceof \WP_Post ? get_the_title($tour_id) : (string) ($settings['title'] ?? '');
		$text     = $tour instanceof \WP_Post ? get_the_excerpt($tour_id) : (string) ($settings['description'] ?? '');
		$summary  = $tour instanceof \WP_Post ? TourData::get_summary_items($tour_id) : [];
		?>
		<article class="twostars-widget-card">
			<p class="twostars-widget-card__eyebrow"><?php esc_html_e('Featured Tour', 'twostars-elementor-widgets'); ?></p>
			<h3 class="twostars-widget-card__title"><?php echo esc_html($title); ?></h3>
			<?php if (! empty($summary)) : ?>
				<ul class="twostars-widget-card__meta">
					<?php foreach (array_slice($summary, 0, 3) as $item) : ?>
						<li><strong><?php echo esc_html($item['label']); ?>:</strong> <?php echo esc_html($item['value']); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p class="twostars-widget-card__text"><?php echo esc_html($text); ?></p>
			<?php if ($tour instanceof \WP_Post && ! empty($meta['booking_notes'])) : ?>
				<p class="twostars-widget-card__note"><?php echo esc_html((string) $meta['booking_notes']); ?></p>
			<?php endif; ?>
			<a class="twostars-widget-button" href="<?php echo esc_url((string) $url); ?>">
				<?php echo esc_html((string) ($settings['button_text'] ?? '')); ?>
			</a>
		</article>
		<?php
	}

	/**
	 * @return array<string, string>
	 */
	private function get_tour_options(): array
	{
		if (! class_exists(TourData::class)) {
			return [];
		}

		return ['' => __('Select a tour', 'twostars-elementor-widgets')] + TourData::get_tour_options();
	}
}
