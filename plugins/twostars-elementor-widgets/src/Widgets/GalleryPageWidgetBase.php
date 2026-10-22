<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use WP_Post;

abstract class GalleryPageWidgetBase extends BaseWidget
{
	protected function register_page_source_controls(): void
	{
		$this->start_controls_section(
			'page_source_section',
			[
				'label' => __('Page Source', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'page_source_mode',
			[
				'label'   => __('Source Mode', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'current' => __('Current Page', 'twostars-elementor-widgets'),
					'gallery' => __('Gallery Page', 'twostars-elementor-widgets'),
				],
				'default' => 'gallery',
			]
		);

		$this->end_controls_section();
	}

	protected function resolve_page_post(): ?WP_Post
	{
		$settings = $this->get_settings_for_display();
		$current  = null;

		if (is_page()) {
			$post = get_post(get_the_ID());

			if ($post instanceof WP_Post && $post->post_type === 'page') {
				$current = $post;
			}
		}

		if (($settings['page_source_mode'] ?? 'gallery') === 'current') {
			return $current;
		}

		$page = get_page_by_path('gallery', OBJECT, 'page');

		if ($page instanceof WP_Post) {
			return $page;
		}

		return $current instanceof WP_Post ? $current : null;
	}
}
