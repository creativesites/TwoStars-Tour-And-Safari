<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use TwoStars\Platform\Support\TourData;

final class SingleTourDetailsWidget extends SingleTourWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-single-tour-details';
	}

	public function get_title(): string
	{
		return __('Single Tour Details', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-info-box';
	}

	protected function register_controls(): void
	{
		$this->register_tour_source_controls();
	}

	protected function render(): void
	{
		$tour = $this->resolve_tour_post();

		if (! $tour || ! class_exists(TourData::class)) {
			return;
		}

		$meta  = TourData::get_tour_meta($tour->ID);
		$items = [
			__('Duration', 'twostars-elementor-widgets')        => (string) ($meta['duration'] ?? ''),
			__('Group Size', 'twostars-elementor-widgets')      => (string) ($meta['group_size'] ?? ''),
			__('Pickup', 'twostars-elementor-widgets')          => (string) ($meta['pickup_location'] ?? ''),
			__('Activity Level', 'twostars-elementor-widgets')  => ucfirst((string) ($meta['difficulty'] ?? '')),
			__('Best Season', 'twostars-elementor-widgets')     => (string) ($meta['best_season'] ?? ''),
			__('Availability', 'twostars-elementor-widgets')    => TourData::get_availability_label($tour->ID),
		];

		?>
		<section class="twostars-single-tour-details" data-twostars-animate="fade-in-up">
			<div class="twostars-single-tour-details__grid">
				<?php foreach ($items as $label => $value) : ?>
					<?php if ($value === '') : ?>
						<?php continue; ?>
					<?php endif; ?>
					<div class="twostars-single-tour-details__item">
						<span><?php echo esc_html($label); ?></span>
						<strong><?php echo esc_html($value); ?></strong>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
