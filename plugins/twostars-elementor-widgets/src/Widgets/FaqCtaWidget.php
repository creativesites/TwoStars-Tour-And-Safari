<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Repeater;

final class FaqCtaWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-faq-cta';
	}

	public function get_title(): string
	{
		return __('FAQ CTA Strip', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-call-to-action';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control('subtitle', ['label' => __('Subtitle', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Still deciding?', 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Headline', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Need help choosing the right Two Stars tour?', 'twostars-elementor-widgets')]);
		$this->add_control('text', ['label' => __('Body Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('If your question is specific to pickups, private options, timing, or which tour fits best, reach out and we will guide you directly.', 'twostars-elementor-widgets')]);
		$this->add_control('primary_cta_text', ['label' => __('Primary Button Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Book With Calendly', 'twostars-elementor-widgets')]);
		$this->add_control('primary_cta_link', ['label' => __('Primary Button Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/booking/')]]);
		$this->add_control('secondary_cta_text', ['label' => __('Secondary Button Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Message on WhatsApp', 'twostars-elementor-widgets')]);
		$this->add_control('secondary_cta_link', ['label' => __('Secondary Button Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/contact/')]]);

		$repeater = new Repeater();
		$repeater->add_control(
			'icon',
			[
				'label'   => __('Icon', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-check-circle',
					'library' => 'fa-solid',
				],
			]
		);
		$repeater->add_control('text', ['label' => __('Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Walvis Bay & Swakopmund support', 'twostars-elementor-widgets')]);
		$this->add_control(
			'trust_items',
			[
				'label'       => __('Trust Items', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => [
					['text' => __('Walvis Bay & Swakopmund support', 'twostars-elementor-widgets'), 'icon' => ['value' => 'fas fa-map-pin', 'library' => 'fa-solid']],
					['text' => __('Private tours available', 'twostars-elementor-widgets'), 'icon' => ['value' => 'fas fa-car-side', 'library' => 'fa-solid']],
					['text' => __('Fast planning guidance', 'twostars-elementor-widgets'), 'icon' => ['value' => 'fas fa-comments', 'library' => 'fa-solid']],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-faq-cta" data-twostars-animate="fade-in-up">
			<div class="twostars-faq-cta__content">
				<p class="twostars-faq-cta__subtitle"><?php echo esc_html((string) ($settings['subtitle'] ?? '')); ?></p>
				<h2 class="twostars-faq-cta__title"><?php echo nl2br(esc_html((string) ($settings['title'] ?? ''))); ?></h2>
				<?php if (! empty($settings['text'])) : ?>
					<p class="twostars-faq-cta__text"><?php echo esc_html((string) $settings['text']); ?></p>
				<?php endif; ?>
				<div class="twostars-faq-cta__actions">
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
					<div class="twostars-faq-cta__trust">
						<?php foreach ($settings['trust_items'] as $item) : ?>
							<div class="twostars-faq-cta__trust-item">
								<span class="twostars-faq-cta__trust-icon" aria-hidden="true">
									<?php if (! empty($item['icon']['value'])) : ?>
										<?php Icons_Manager::render_icon((array) $item['icon'], ['aria-hidden' => 'true']); ?>
									<?php endif; ?>
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
}
