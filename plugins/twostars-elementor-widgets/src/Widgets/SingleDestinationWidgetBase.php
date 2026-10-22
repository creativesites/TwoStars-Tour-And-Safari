<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;
use WP_Post;

abstract class SingleDestinationWidgetBase extends BaseWidget
{
	protected function register_destination_source_controls(): void
	{
		$this->start_controls_section(
			'destination_source_section',
			[
				'label' => __('Destination Source', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'destination_source_mode',
			[
				'label'   => __('Source Mode', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'current'  => __('Current Destination', 'twostars-elementor-widgets'),
					'specific' => __('Specific Destination', 'twostars-elementor-widgets'),
				],
				'default' => 'specific',
			]
		);

		$this->add_control(
			'destination_id',
			[
				'label'     => __('Destination', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::SELECT,
				'options'   => TourContent::get_destination_options(),
				'default'   => '',
				'condition' => [
					'destination_source_mode' => 'specific',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function resolve_destination_post(): ?WP_Post
	{
		$settings = $this->get_settings_for_display();

		if (($settings['destination_source_mode'] ?? 'specific') === 'current' && is_singular('destination')) {
			$post = get_post(get_the_ID());

			if ($post instanceof WP_Post && $post->post_type === 'destination') {
				return $post;
			}
		}

		$destination_id = absint($settings['destination_id'] ?? 0);

		if ($destination_id > 0) {
			$post = get_post($destination_id);

			if ($post instanceof WP_Post && $post->post_type === 'destination') {
				return $post;
			}
		}

		$destinations = TourContent::get_destinations(['posts_per_page' => 1]);

		return $destinations[0] ?? null;
	}
}
