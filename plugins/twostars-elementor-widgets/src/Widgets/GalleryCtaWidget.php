<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class GalleryCtaWidget extends GalleryPageWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-gallery-cta';
	}

	public function get_title(): string
	{
		return __('Gallery CTA Strip', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-call-to-action';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);

		$this->add_control('title', [
			'label'   => __('Headline', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Ready to See It in Person?', 'twostars-elementor-widgets'),
		]);

		$this->add_control('subtext', [
			'label'   => __('Subtext', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => __('These photos only tell part of the story. Book your tour and experience Namibia\'s coast, dunes, and wildlife first-hand with Two Stars.', 'twostars-elementor-widgets'),
		]);

		$this->add_control('primary_cta_text', [
			'label'   => __('Primary Button Text', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Book Your Tour', 'twostars-elementor-widgets'),
		]);

		$this->add_control('primary_cta_link', [
			'label'   => __('Primary Button Link', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::URL,
			'default' => ['url' => home_url('/booking/')],
		]);

		$this->add_control('secondary_cta_text', [
			'label'   => __('Secondary Button Text', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Chat on WhatsApp', 'twostars-elementor-widgets'),
		]);

		$this->add_control('secondary_cta_link', [
			'label'   => __('Secondary Button Link', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::URL,
			'default' => ['url' => home_url('/contact/')],
		]);

		$repeater = new Repeater();
		$repeater->add_control('icon_name', [
			'label'   => __('Icon', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::SELECT,
			'options' => $this->get_lucide_icon_options(),
			'default' => 'check-circle',
		]);
		$repeater->add_control('text', [
			'label'   => __('Text', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Private group option', 'twostars-elementor-widgets'),
		]);

		$this->add_control('trust_items', [
			'label'       => __('Trust Elements', 'twostars-elementor-widgets'),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => [
				['text' => __('Private group option', 'twostars-elementor-widgets'), 'icon_name' => 'users'],
				['text' => __('Local Namibian guides', 'twostars-elementor-widgets'), 'icon_name' => 'map-pinned'],
				['text' => __('Flexible scheduling', 'twostars-elementor-widgets'), 'icon_name' => 'calendar-check'],
			],
		]);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-gallery-cta" data-twostars-animate="fade-in-up">
			<div class="twostars-gallery-cta__inner">
				<div class="twostars-gallery-cta__content">
					<h2 class="twostars-gallery-cta__title"><?php echo esc_html((string) ($settings['title'] ?? '')); ?></h2>
					<?php if (! empty($settings['subtext'])) : ?>
						<p class="twostars-gallery-cta__text"><?php echo esc_html((string) $settings['subtext']); ?></p>
					<?php endif; ?>
					<div class="twostars-gallery-cta__buttons">
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
				</div>
				<?php if (! empty($settings['trust_items']) && is_array($settings['trust_items'])) : ?>
					<div class="twostars-gallery-cta__trust">
						<?php foreach ($settings['trust_items'] as $item) : ?>
							<div class="twostars-gallery-cta__trust-item">
								<span class="twostars-gallery-cta__trust-icon" aria-hidden="true">
									<?php echo $this->render_lucide_icon((string) ($item['icon_name'] ?? 'check-circle'), 'twostars-gallery-cta__trust-svg'); ?>
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
