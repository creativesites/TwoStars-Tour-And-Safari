<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

function twostars_theme_body_classes(array $classes): array
{
	$classes[] = 'twostars-site';

	if (class_exists('\Elementor\Plugin')) {
		$classes[] = 'twostars-elementor-ready';
	}

	return $classes;
}

add_filter('body_class', 'twostars_theme_body_classes');
