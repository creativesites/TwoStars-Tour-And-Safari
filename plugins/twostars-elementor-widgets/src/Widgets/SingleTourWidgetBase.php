<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;
use WP_Post;

abstract class SingleTourWidgetBase extends BaseWidget
{
	protected function register_tour_source_controls(): void
	{
		$this->start_controls_section(
			'tour_source_section',
			[
				'label' => __('Tour Source', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'tour_source_mode',
			[
				'label'   => __('Source Mode', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'current'  => __('Current Tour', 'twostars-elementor-widgets'),
					'specific' => __('Specific Tour', 'twostars-elementor-widgets'),
				],
				'default' => 'specific',
			]
		);

		$this->add_control(
			'tour_id',
			[
				'label'     => __('Tour', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::SELECT,
				'options'   => TourContent::get_tour_options(),
				'default'   => '',
				'condition' => [
					'tour_source_mode' => 'specific',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function resolve_tour_post(): ?WP_Post
	{
		$settings = $this->get_settings_for_display();

		if (($settings['tour_source_mode'] ?? 'specific') === 'current' && is_singular('tour')) {
			$post = get_post(get_the_ID());

			if ($post instanceof WP_Post && $post->post_type === 'tour') {
				return $post;
			}
		}

		$tour_id = absint($settings['tour_id'] ?? 0);

		if ($tour_id > 0) {
			$post = get_post($tour_id);

			if ($post instanceof WP_Post && $post->post_type === 'tour') {
				return $post;
			}
		}

		$tours = TourContent::get_tours(['posts_per_page' => 1]);

		return $tours[0] ?? null;
	}

	/**
	 * @return string[]
	 */
	protected function split_lines(string $value): array
	{
		$parts = preg_split('/\r\n|\r|\n/', trim($value)) ?: [];

		return array_values(array_filter(array_map('trim', $parts), static fn(string $item): bool => $item !== ''));
	}
}
