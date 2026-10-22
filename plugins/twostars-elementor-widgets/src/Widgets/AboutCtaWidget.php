<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class AboutCtaWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-about-cta';
	}

	public function get_title(): string
	{
		return __('About CTA Strip', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-call-to-action';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Headline', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Ready to Explore Namibia’s Coast?', 'twostars-elementor-widgets')]);
		$this->add_control('subtext', ['label' => __('Subtext', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Book your tour today or reach out with questions. We’re here to help you plan the right coastal or desert day adventure.', 'twostars-elementor-widgets')]);
		$this->add_control('primary_cta_text', ['label' => __('Primary Button Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Book Your Tour', 'twostars-elementor-widgets')]);
		$this->add_control('primary_cta_link', ['label' => __('Primary Button Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/booking/')]]);
		$this->add_control('secondary_cta_text', ['label' => __('Secondary Button Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Contact on WhatsApp', 'twostars-elementor-widgets')]);
		$this->add_control('secondary_cta_link', ['label' => __('Secondary Button Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/contact/')]]);

		$repeater = new Repeater();
		$repeater->add_control(
			'icon_name',
			[
				'label'   => __('Icon', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_lucide_icon_options(),
				'default' => 'check-circle',
			]
		);
		$repeater->add_control('text', ['label' => __('Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Flexible booking', 'twostars-elementor-widgets')]);
		$this->add_control(
			'trust_items',
			[
				'label'       => __('Trust Elements', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => [
					['text' => __('Flexible booking', 'twostars-elementor-widgets'), 'icon_name' => 'calendar-check'],
					['text' => __('Local expertise', 'twostars-elementor-widgets'), 'icon_name' => 'map-pinned'],
					['text' => __('Small groups', 'twostars-elementor-widgets'), 'icon_name' => 'users'],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-about-cta" data-twostars-animate="fade-in-up">
			<div class="twostars-about-cta__inner">
				<div class="twostars-about-cta__content">
					<h2 class="twostars-about-cta__title"><?php echo esc_html((string) ($settings['title'] ?? '')); ?></h2>
					<?php if (! empty($settings['subtext'])) : ?>
						<p class="twostars-about-cta__text"><?php echo esc_html((string) $settings['subtext']); ?></p>
					<?php endif; ?>
				</div>
				<div class="twostars-about-cta__actions">
					<?php if (! empty($settings['primary_cta_text']) && ! empty($settings['primary_cta_link']['url'])) : ?>
						<a class="twostars-safari-button" <?php echo $this->link_attributes((array) $settings['primary_cta_link']); ?>>
							<?php echo esc_html((string) $settings['primary_cta_text']); ?>
						</a>
					<?php endif; ?>
					<?php if (! empty($settings['secondary_cta_text']) && ! empty($settings['secondary_cta_link']['url'])) : ?>
						<a class="twostars-safari-button twostars-safari-button--ghost" <?php echo $this->link_attributes((array) $settings['secondary_cta_link']); ?>>
							<?php echo esc_html((string) $settings['secondary_cta_text']); ?>
						</a>
					<?php endif; ?>
				</div>
				<?php if (! empty($settings['trust_items']) && is_array($settings['trust_items'])) : ?>
					<div class="twostars-about-cta__trust">
						<?php foreach ($settings['trust_items'] as $item) : ?>
							<div class="twostars-about-cta__trust-item">
								<span class="twostars-about-cta__trust-icon" aria-hidden="true">
									<?php echo $this->render_lucide_icon($this->resolve_icon_name($item), 'twostars-about-cta__trust-svg'); ?>
								</span>
								<span><?php echo esc_html((string) ($item['text'] ?? '')); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private function resolve_icon_name(array $item): string
	{
		$icon_name = (string) ($item['icon_name'] ?? '');
		$text = strtolower((string) ($item['text'] ?? ''));

		if (str_contains($text, 'flexible') || str_contains($text, 'booking')) {
			return 'calendar-check';
		}

		if (str_contains($text, 'local') || str_contains($text, 'expertise')) {
			return 'map-pinned';
		}

		if (str_contains($text, 'group')) {
			return 'users';
		}

		if ($icon_name !== '') {
			return $icon_name;
		}

		return 'check-circle';
	}
}
