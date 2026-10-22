<?php
declare(strict_types=1);

namespace TwoStars\Platform\Integrations;

use TwoStars\Platform\Support\Service;

final class SocialFeedManager implements Service
{
	public function register(): void
	{
		add_filter('twostars_platform/social_feed_items', [$this, 'get_feed_items']);
	}

	/**
	 * @param array<int, array<string, string>> $items
	 * @return array<int, array<string, string>>
	 */
	public function get_feed_items(array $items = []): array
	{
		return array_merge(
			$items,
			[
			[
				'title' => __('Desert sunrise drive', 'twostars-platform'),
				'text'  => __('Starter feed item for the future social sync layer.', 'twostars-platform'),
				'url'   => home_url('/'),
			],
			]
		);
	}
}
