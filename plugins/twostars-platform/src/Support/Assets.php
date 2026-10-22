<?php
declare(strict_types=1);

namespace TwoStars\Platform\Support;

final class Assets
{
	public static function plugin_url(string $relative_path): string
	{
		return TWOSTARS_PLATFORM_URL . ltrim($relative_path, '/');
	}

	public static function plugin_version(string $relative_path): string
	{
		$full_path = TWOSTARS_PLATFORM_PATH . ltrim($relative_path, '/');
		$mtime     = file_exists($full_path) ? filemtime($full_path) : false;

		return $mtime ? (string) $mtime : TWOSTARS_PLATFORM_VERSION;
	}

	/**
	 * @param array<int, string> $needles
	 */
	public static function matches_admin_page(string $hook_suffix, array $needles): bool
	{
		$current_page = isset($_GET['page']) ? sanitize_key(wp_unslash((string) $_GET['page'])) : '';

		foreach ($needles as $needle) {
			$needle = sanitize_key($needle);

			if ($needle === '') {
				continue;
			}

			if ($current_page !== '' && str_starts_with($current_page, $needle)) {
				return true;
			}

			if ($hook_suffix !== '' && str_contains($hook_suffix, $needle)) {
				return true;
			}
		}

		return false;
	}
}
