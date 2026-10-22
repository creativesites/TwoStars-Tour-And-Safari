<?php
/**
 * Plugin Name: Two Stars Platform
 * Description: Core platform plugin for tours, CRM foundations, reviews, AI features, and site management.
 * Version: 0.2.4
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Codex
 * Text Domain: twostars-platform
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

define('TWOSTARS_PLATFORM_VERSION', '0.2.4');
define('TWOSTARS_PLATFORM_FILE', __FILE__);
define('TWOSTARS_PLATFORM_PATH', plugin_dir_path(__FILE__));
define('TWOSTARS_PLATFORM_URL', plugin_dir_url(__FILE__));

require_once TWOSTARS_PLATFORM_PATH . 'src/Support/Autoloader.php';

\TwoStars\Platform\Support\Autoloader::register();

register_activation_hook(
	__FILE__,
	static function (): void {
		\TwoStars\Platform\Plugin::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		\TwoStars\Platform\Plugin::deactivate();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		\TwoStars\Platform\Plugin::boot();
	}
);
