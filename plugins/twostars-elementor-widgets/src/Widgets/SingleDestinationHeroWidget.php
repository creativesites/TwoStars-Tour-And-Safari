<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;

final class SingleDestinationHeroWidget extends SingleDestinationWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-single-destination-hero';
	}

	public function get_title(): string
	{
		return __('Single Destination Hero', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-single-post';
	}

	protected function register_controls(): void
	{
		$this->register_destination_source_controls();
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('label', ['label' => __('Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Destination Guide', 'twostars-elementor-widgets')]);
		$this->add_control('planning_text', ['label' => __('Planning Note', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Use this region as a building block inside a wider route. The right result depends on season, driving logic, and what the trip needs to balance.', 'twostars-elementor-widgets')]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$destination = $this->resolve_destination_post();
		$settings = $this->get_settings_for_display();

		if (! $destination) {
			return;
		}

		$image_id = get_post_thumbnail_id($destination->ID);
		$related = TourContent::get_destination_related_tours($destination->ID, 3);
		?>
		<section class="twostars-single-destination-hero">
			<div class="twostars-single-destination-hero__media">
				<?php if ($image_id > 0) : ?>
					<?php echo wp_get_attachment_image($image_id, 'full', false, ['class' => 'twostars-single-destination-hero__image', 'loading' => 'eager']); ?>
				<?php else : ?>
					<div class="twostars-single-destination-hero__image twostars-single-destination-hero__image--placeholder"></div>
				<?php endif; ?>
			</div>
			<div class="twostars-single-destination-hero__overlay"></div>
			<div class="twostars-single-destination-hero__content">
				<div class="twostars-single-destination-hero__copy">
					<p class="twostars-single-destination-hero__label" data-twostars-animate="fade-in-up"><?php echo esc_html((string) ($settings['label'] ?? '')); ?></p>
					<h1 class="twostars-single-destination-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.12s;"><?php echo esc_html(get_the_title($destination)); ?></h1>
					<p class="twostars-single-destination-hero__excerpt" data-twostars-animate="fade-in-up" style="--twostars-delay:0.24s;"><?php echo esc_html(get_the_excerpt($destination)); ?></p>
				</div>
				<aside class="twostars-single-destination-hero__aside" data-twostars-animate="fade-in-up" style="--twostars-delay:0.3s;">
					<p class="twostars-single-destination-hero__planning"><?php echo esc_html((string) ($settings['planning_text'] ?? '')); ?></p>
					<?php if ($related !== []) : ?>
						<div class="twostars-single-destination-hero__pairs">
							<span><?php esc_html_e('Often used in these routes', 'twostars-elementor-widgets'); ?></span>
							<ul>
								<?php foreach ($related as $tour) : ?>
									<li><?php echo esc_html(get_the_title($tour)); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
					<a class="twostars-safari-button twostars-safari-button--primary twostars-safari-button--block" href="<?php echo esc_url(home_url('/booking/')); ?>">
						<?php esc_html_e('Plan Around This Region', 'twostars-elementor-widgets'); ?>
					</a>
				</aside>
			</div>
		</section>
		<?php
	}
}
