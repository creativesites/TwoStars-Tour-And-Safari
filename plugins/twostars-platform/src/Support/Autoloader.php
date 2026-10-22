<?php
declare(strict_types=1);

namespace TwoStars\Platform\Support;

if (! defined('ABSPATH')) {
	exit;
}

final class Autoloader
{
	public static function register(): void
	{
		spl_autoload_register([self::class, 'autoload']);
	}

	private static function autoload(string $class): void
	{
		$prefix = 'TwoStars\\Platform\\';

		if (strpos($class, $prefix) !== 0) {
			return;
		}

		$relative_class = substr($class, strlen($prefix));
		$relative_path  = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
		$file           = TWOSTARS_PLATFORM_PATH . 'src/' . $relative_path;

		if (file_exists($file)) {
			require_once $file;
		}
	}
}
