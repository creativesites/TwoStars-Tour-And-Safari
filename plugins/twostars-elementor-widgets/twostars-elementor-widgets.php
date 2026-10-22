<?php
/**
 * Plugin Name: Two Stars Elementor Widgets
 * Description: Custom Elementor widgets for tours, bookings, testimonials, and social proof.
 * Version: 0.3.5
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Codex
 * Text Domain: twostars-elementor-widgets
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

define('TWOSTARS_ELEMENTOR_WIDGETS_VERSION', '0.3.5');
define('TWOSTARS_ELEMENTOR_WIDGETS_FILE', __FILE__);
define('TWOSTARS_ELEMENTOR_WIDGETS_PATH', plugin_dir_path(__FILE__));
define('TWOSTARS_ELEMENTOR_WIDGETS_URL', plugin_dir_url(__FILE__));

require_once TWOSTARS_ELEMENTOR_WIDGETS_PATH . 'src/Support/Autoloader.php';

\TwoStars\ElementorWidgets\Support\Autoloader::register();

add_action(
	'plugins_loaded',
	static function (): void {
		\TwoStars\ElementorWidgets\Plugin::boot();
	}
);
