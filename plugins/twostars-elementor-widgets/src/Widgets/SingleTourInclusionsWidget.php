<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\Platform\Support\TourData;

final class SingleTourInclusionsWidget extends SingleTourWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-single-tour-inclusions';
	}

	public function get_title(): string
	{
		return __('Single Tour Inclusions', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-check-circle';
	}

	protected function register_controls(): void
	{
		$this->register_tour_source_controls();

		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Headings', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'includes_heading',
			[
				'label'   => __('Includes Heading', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('What Is Included', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'excludes_heading',
			[
				'label'   => __('Excludes Heading', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('What To Budget Separately', 'twostars-elementor-widgets'),
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$tour = $this->resolve_tour_post();
		$settings = $this->get_settings_for_display();

		if (! $tour || ! class_exists(TourData::class)) {
			return;
		}

		$meta          = TourData::get_tour_meta($tour->ID);
		$includes      = $this->split_lines((string) ($meta['includes'] ?? ''));
		$excludes      = $this->split_lines((string) ($meta['excludes'] ?? ''));

		if ($includes === [] && $excludes === []) {
			return;
		}
		?>
		<section class="twostars-single-tour-inclusions" data-twostars-animate="fade-in-up">
			<div class="twostars-single-tour-inclusions__grid">
				<?php if ($includes !== []) : ?>
					<section class="twostars-single-tour-inclusions__panel">
						<h2><?php echo esc_html((string) ($settings['includes_heading'] ?? '')); ?></h2>
						<ul>
							<?php foreach ($includes as $item) : ?>
								<li><?php echo esc_html($item); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>
				<?php if ($excludes !== []) : ?>
					<section class="twostars-single-tour-inclusions__panel twostars-single-tour-inclusions__panel--secondary">
						<h2><?php echo esc_html((string) ($settings['excludes_heading'] ?? '')); ?></h2>
						<ul>
							<?php foreach ($excludes as $item) : ?>
								<li><?php echo esc_html($item); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}
