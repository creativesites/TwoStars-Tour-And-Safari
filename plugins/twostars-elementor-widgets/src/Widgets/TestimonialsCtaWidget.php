<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;

final class TestimonialsCtaWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-testimonials-cta';
	}

	public function get_title(): string
	{
		return __('Testimonials CTA Strip', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-call-to-action';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('subtitle', ['label' => __('Subtitle', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Ready for your own route?', 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Use these stories as a starting point, then let us design the version that fits your dates and travel style.', 'twostars-elementor-widgets')]);
		$this->add_control('cta_text', ['label' => __('CTA Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Start Booking', 'twostars-elementor-widgets')]);
		$this->add_control('cta_link', ['label' => __('CTA Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/booking/')]]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-testimonials-cta" data-twostars-animate="fade-in-up">
			<div class="twostars-testimonials-cta__content">
				<p class="twostars-testimonials-cta__subtitle"><?php echo esc_html((string) ($settings['subtitle'] ?? '')); ?></p>
				<h2 class="twostars-testimonials-cta__title"><?php echo nl2br(esc_html((string) ($settings['title'] ?? ''))); ?></h2>
			</div>
			<?php if (! empty($settings['cta_text']) && ! empty($settings['cta_link']['url'])) : ?>
				<a class="twostars-safari-button twostars-safari-button--primary" <?php echo $this->link_attributes((array) $settings['cta_link']); ?>>
					<?php echo esc_html((string) $settings['cta_text']); ?>
				</a>
			<?php endif; ?>
		</section>
		<?php
	}
}
