<?php
declare(strict_types=1);

namespace TwoStars\Platform\PostTypes;

use TwoStars\Platform\Support\Service;

final class ContentTypes implements Service
{
	public function register(): void
	{
		add_action('init', [$this, 'register_post_types']);
	}

	public function register_post_types(): void
	{
		register_post_type(
			'tour',
			[
				'labels' => [
					'name'          => __('Tours', 'twostars-platform'),
					'singular_name' => __('Tour', 'twostars-platform'),
				],
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-palmtree',
				'rewrite'      => ['slug' => 'tours'],
				'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
			]
		);

		register_post_type(
			'destination',
			[
				'labels' => [
					'name'          => __('Destinations', 'twostars-platform'),
					'singular_name' => __('Destination', 'twostars-platform'),
				],
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-location-alt',
				'rewrite'      => ['slug' => 'destinations'],
				'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
			]
		);

		register_post_type(
			'testimonial',
			[
				'labels' => [
					'name'          => __('Reviews', 'twostars-platform'),
					'singular_name' => __('Review', 'twostars-platform'),
				],
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-format-quote',
				'rewrite'      => ['slug' => 'reviews'],
				'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
			]
		);

		register_post_type(
			'faq',
			[
				'labels' => [
					'name'          => __('FAQs', 'twostars-platform'),
					'singular_name' => __('FAQ', 'twostars-platform'),
				],
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-editor-help',
				'rewrite'      => ['slug' => 'faq'],
				'supports'     => ['title', 'editor', 'revisions'],
			]
		);

		register_post_type(
			'team_member',
			[
				'labels' => [
					'name'          => __('Team Members', 'twostars-platform'),
					'singular_name' => __('Team Member', 'twostars-platform'),
				],
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-groups',
				'rewrite'      => ['slug' => 'team'],
				'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
			]
		);

		register_post_type(
			'lead',
			[
				'labels' => [
					'name'          => __('Leads', 'twostars-platform'),
					'singular_name' => __('Lead', 'twostars-platform'),
				],
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'menu_icon'           => 'dashicons-id',
				'supports'            => [],
			]
		);

		register_post_type(
			'booking',
			[
				'labels' => [
					'name'          => __('Bookings', 'twostars-platform'),
					'singular_name' => __('Booking', 'twostars-platform'),
				],
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'menu_icon'           => 'dashicons-calendar-alt',
				'supports'            => ['title'],
			]
		);
	}
}
