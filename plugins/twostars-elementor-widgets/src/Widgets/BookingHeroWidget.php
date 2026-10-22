<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;

final class BookingHeroWidget extends BookingPageWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-booking-hero';
	}

	public function get_title(): string
	{
		return __('Booking Hero', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-calendar';
	}

	protected function register_controls(): void
	{
		$this->register_page_source_controls();

		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control('background_image', ['label' => __('Background Image', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA]);
		$this->add_control('label', ['label' => __('Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Reserve Your Planning Call', 'twostars-elementor-widgets')]);
		$this->add_control('panel_title', ['label' => __('Panel Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Private safari planning starts here', 'twostars-elementor-widgets')]);
		$this->add_control('panel_text', ['label' => __('Panel Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Choose a time that works for you and we will use the call to narrow dates, route shape, comfort level, and the right next step for your Namibia trip.', 'twostars-elementor-widgets')]);
		$this->add_control('cta_text', ['label' => __('CTA Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Open Schedule', 'twostars-elementor-widgets')]);
		$this->add_control('cta_link', ['label' => __('CTA Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => '#booking-schedule']]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$page = $this->resolve_page_post();
		$settings = $this->get_settings_for_display();

		if (! $page) {
			return;
		}

		$image_id = ! empty($settings['background_image']['id']) ? absint($settings['background_image']['id']) : get_post_thumbnail_id($page->ID);
		?>
		<section class="twostars-booking-hero">
			<div class="twostars-booking-hero__media">
				<?php if ($image_id > 0) : ?>
					<?php echo wp_get_attachment_image($image_id, 'full', false, ['class' => 'twostars-booking-hero__image', 'loading' => 'eager']); ?>
				<?php else : ?>
					<div class="twostars-booking-hero__image twostars-booking-hero__image--placeholder"></div>
				<?php endif; ?>
			</div>
			<div class="twostars-booking-hero__overlay"></div>
			<div class="twostars-booking-hero__content">
				<div class="twostars-booking-hero__copy">
					<p class="twostars-booking-hero__label" data-twostars-animate="fade-in-up"><?php echo esc_html((string) ($settings['label'] ?? '')); ?></p>
					<h1 class="twostars-booking-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.12s;"><?php echo esc_html(get_the_title($page)); ?></h1>
					<p class="twostars-booking-hero__excerpt" data-twostars-animate="fade-in-up" style="--twostars-delay:0.24s;"><?php echo esc_html(get_the_excerpt($page)); ?></p>
					<?php if (! empty($settings['cta_text']) && ! empty($settings['cta_link']['url'])) : ?>
						<div data-twostars-animate="fade-in-up" style="--twostars-delay:0.36s;">
							<a class="twostars-safari-button twostars-safari-button--primary" <?php echo $this->link_attributes((array) $settings['cta_link']); ?>>
								<?php echo esc_html((string) $settings['cta_text']); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
				<aside class="twostars-booking-hero__panel" data-twostars-animate="fade-in-up" style="--twostars-delay:0.3s;">
					<h2><?php echo esc_html((string) ($settings['panel_title'] ?? '')); ?></h2>
					<p><?php echo esc_html((string) ($settings['panel_text'] ?? '')); ?></p>
				</aside>
			</div>
		</section>
		<?php
	}
}
