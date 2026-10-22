<?php
declare(strict_types=1);

namespace TwoStars\Platform\Taxonomies;

use TwoStars\Platform\Support\Service;

final class TourTaxonomies implements Service
{
	public function register(): void
	{
		add_action('init', [$this, 'register_taxonomies']);
	}

	public function register_taxonomies(): void
	{
		$taxonomies = [
			'tour_type' => __('Tour Types', 'twostars-platform'),
			'tour_region' => __('Tour Regions', 'twostars-platform'),
			'tour_activity' => __('Tour Activities', 'twostars-platform'),
			'tour_duration' => __('Tour Durations', 'twostars-platform'),
		];

		foreach ($taxonomies as $taxonomy => $label) {
			register_taxonomy(
				$taxonomy,
				['tour'],
				[
					'labels' => [
						'name'          => $label,
						'singular_name' => $label,
					],
					'public'            => true,
					'show_in_rest'      => true,
					'hierarchical'      => true,
					'show_admin_column' => true,
				]
			);
		}
	}
}
