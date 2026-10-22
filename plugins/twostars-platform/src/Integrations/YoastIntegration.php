<?php
declare(strict_types=1);

namespace TwoStars\Platform\Integrations;

use TwoStars\Platform\Support\Service;

final class YoastIntegration implements Service
{
	public function register(): void
	{
		add_filter('twostars_platform/yoast_available', [$this, 'is_available']);
	}

	public function is_available(bool $available = false): bool
	{
		return defined('WPSEO_VERSION');
	}
}
