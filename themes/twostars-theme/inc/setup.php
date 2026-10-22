<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

function twostars_theme_setup(): void
{
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('custom-logo');
	add_theme_support(
		'html5',
		[
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'search-form',
			'script',
			'style',
		]
	);
	add_theme_support(
		'custom-background',
		[
			'default-color' => 'f8f3ea',
		]
	);
	add_theme_support('responsive-embeds');
	add_theme_support('align-wide');

	register_nav_menus(
		[
			'primary' => __('Primary Navigation', 'twostars-theme'),
			'footer'  => __('Footer Navigation', 'twostars-theme'),
		]
	);
}

add_action('after_setup_theme', 'twostars_theme_setup');

function twostars_theme_register_custom_rewrite_rules(): void
{
	$page = get_page_by_path('booking');

	if (! $page instanceof WP_Post) {
		return;
	}

	add_rewrite_rule('^booking/?$', 'index.php?page_id=' . (int) $page->ID, 'top');
}

add_action('init', 'twostars_theme_register_custom_rewrite_rules', 9);

function twostars_theme_maybe_flush_rewrite_rules(): void
{
	$version = (string) get_option('twostars_theme_rewrite_version', '');

	if ($version === 'booking-v1') {
		return;
	}

	twostars_theme_register_custom_rewrite_rules();
	flush_rewrite_rules(false);
	update_option('twostars_theme_rewrite_version', 'booking-v1', false);
}

add_action('init', 'twostars_theme_maybe_flush_rewrite_rules', 20);

function twostars_theme_is_named_request(string $slug): bool
{
	$request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
	$request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
	$home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);

	$request_path = trim($request_path, '/');
	$home_path = trim($home_path, '/');

	if ($home_path !== '' && str_starts_with($request_path, $home_path . '/')) {
		$request_path = substr($request_path, strlen($home_path) + 1);
	}

	return $request_path === trim($slug, '/');
}

function twostars_theme_boot_virtual_booking_page(): void
{
	if (! twostars_theme_is_named_request('booking')) {
		return;
	}

	$page = get_page_by_path('booking');

	if (! $page instanceof WP_Post) {
		return;
	}

	global $wp_query, $post;

	$post = $page;

	if ($wp_query instanceof WP_Query) {
		$wp_query->is_404 = false;
		$wp_query->is_page = true;
		$wp_query->is_singular = true;
		$wp_query->queried_object = $page;
		$wp_query->queried_object_id = (int) $page->ID;
		$wp_query->post = $page;
		$wp_query->posts = [$page];
		$wp_query->post_count = 1;
	}

	status_header(200);
}

add_action('wp', 'twostars_theme_boot_virtual_booking_page', 0);

function twostars_theme_force_booking_page_request(array $query_vars): array
{
	if (is_admin()) {
		return $query_vars;
	}

	$request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
	$request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
	$home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);

	$request_path = trim($request_path, '/');
	$home_path = trim($home_path, '/');

	if ($home_path !== '' && str_starts_with($request_path, $home_path . '/')) {
		$request_path = substr($request_path, strlen($home_path) + 1);
	}

	if ($request_path !== 'booking') {
		return $query_vars;
	}

	$page = get_page_by_path('booking');

	if (! $page instanceof WP_Post) {
		return $query_vars;
	}

	$query_vars['page_id'] = (int) $page->ID;
	unset($query_vars['error'], $query_vars['name'], $query_vars['pagename']);

	return $query_vars;
}

add_filter('request', 'twostars_theme_force_booking_page_request', 0);

function twostars_theme_override_page_template(string $template): string
{
	if (is_page('destinations')) {
		$override = locate_template('page-destinations.php');

		if ($override !== '') {
			return $override;
		}
	}

	if (is_page('booking') || twostars_theme_is_named_request('booking')) {
		$override = locate_template('page-booking.php');

		if ($override !== '') {
			return $override;
		}
	}

	return $template;
}

add_filter('template_include', 'twostars_theme_override_page_template', 20);
