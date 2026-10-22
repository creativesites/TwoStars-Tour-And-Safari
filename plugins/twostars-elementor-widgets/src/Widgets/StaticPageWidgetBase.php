<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use WP_Post;

abstract class StaticPageWidgetBase extends BaseWidget
{
	protected function register_page_source_controls(string $section_label = 'Page Source'): void
	{
		$this->start_controls_section(
			'page_source_section',
			[
				'label' => __($section_label, 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'page_source_mode',
			[
				'label'   => __('Source Mode', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'current' => __('Current Page', 'twostars-elementor-widgets'),
					'static'  => $this->get_static_page_control_label(),
				],
				'default' => 'static',
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

		if (($settings['page_source_mode'] ?? 'static') === 'current') {
			return $current;
		}

		$page = get_page_by_path($this->get_static_page_slug(), OBJECT, 'page');

		if ($page instanceof WP_Post) {
			return $page;
		}

		if ($current instanceof WP_Post) {
			return $current;
		}

		$trashed_page = get_posts(
			[
				'post_type'      => 'page',
				'post_status'    => 'trash',
				'name'           => $this->get_static_page_slug() . '__trashed',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]
		);

		if ($trashed_page !== []) {
			$post = get_post((int) $trashed_page[0]);

			if ($post instanceof WP_Post) {
				return $post;
			}
		}

		return null;
	}

	abstract protected function get_static_page_slug(): string;

	abstract protected function get_static_page_control_label(): string;
}
