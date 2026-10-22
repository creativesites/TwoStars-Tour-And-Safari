<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;

final class TestimonialsHeroWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-testimonials-hero';
	}

	public function get_title(): string
	{
		return __('Testimonials Hero', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-blockquote';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);

		$this->add_control('background_image', [
			'label' => __('Background Image', 'twostars-elementor-widgets'),
			'type'  => Controls_Manager::MEDIA,
		]);

		$this->add_control('eyebrow_text', [
			'label'   => __('Eyebrow Text', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Guest Stories', 'twostars-elementor-widgets'),
		]);

		$this->add_control('headline_text', [
			'label'   => __('Headline Text', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('How the Journey Felt', 'twostars-elementor-widgets'),
		]);

		$this->add_control('highlight_phrase', [
			'label'   => __('Highlight Phrase', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Felt', 'twostars-elementor-widgets'),
		]);

		$this->add_control('excerpt_text', [
			'label'   => __('Excerpt Text', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => __('Warm, specific, and honest — words from real guests who joined our Walvis Bay, Sandwich Harbour, and Swakopmund routes.', 'twostars-elementor-widgets'),
		]);

		$this->add_control('primary_cta_text', [
			'label'   => __('Primary CTA Text', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Read All Reviews', 'twostars-elementor-widgets'),
		]);

		$this->add_control('primary_cta_link', [
			'label'   => __('Primary CTA Link', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::URL,
			'default' => ['url' => home_url('/gallery/')],
		]);

		$this->add_control('secondary_cta_text', [
			'label'   => __('Secondary CTA Text', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Book a Tour', 'twostars-elementor-widgets'),
		]);

		$this->add_control('secondary_cta_link', [
			'label'   => __('Secondary CTA Link', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::URL,
			'default' => ['url' => home_url('/booking/')],
		]);

		$this->add_control('enable_parallax', [
			'label'        => __('Enable Parallax', 'twostars-elementor-widgets'),
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => __('Yes', 'twostars-elementor-widgets'),
			'label_off'    => __('No', 'twostars-elementor-widgets'),
			'return_value' => 'yes',
			'default'      => '',
		]);

		$this->add_control('overlay_opacity', [
			'label'      => __('Overlay Opacity', 'twostars-elementor-widgets'),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => ['%'],
			'range'      => ['%' => ['min' => 0, 'max' => 100]],
			'default'    => ['unit' => '%', 'size' => 50],
		]);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings      = $this->get_settings_for_display();
		$image_id      = ! empty($settings['background_image']['id']) ? absint($settings['background_image']['id']) : 0;
		$title         = trim((string) ($settings['headline_text'] ?? ''));
		$excerpt       = trim((string) ($settings['excerpt_text'] ?? ''));
		$eyebrow       = trim((string) ($settings['eyebrow_text'] ?? ''));
		$highlight     = trim((string) ($settings['highlight_phrase'] ?? ''));
		$overlay_size  = isset($settings['overlay_opacity']['size']) ? (float) $settings['overlay_opacity']['size'] : 50.0;
		$overlay_alpha = max(0.0, min(1.0, $overlay_size / 100));

		$section_classes = ['twostars-testimonials-hero'];
		if (($settings['enable_parallax'] ?? '') === 'yes') {
			$section_classes[] = 'twostars-testimonials-hero--parallax';
		}
		?>
		<section
			class="<?php echo esc_attr(implode(' ', $section_classes)); ?>"
			style="<?php echo esc_attr($this->build_inline_vars(['--twostars-hero-overlay' => (string) $overlay_alpha])); ?>"
		>
			<!-- Background image -->
			<div class="twostars-testimonials-hero__media">
				<?php if ($image_id > 0) : ?>
					<?php echo wp_get_attachment_image($image_id, 'full', false, ['class' => 'twostars-testimonials-hero__image', 'loading' => 'eager']); ?>
				<?php else : ?>
					<div class="twostars-testimonials-hero__image twostars-testimonials-hero__image--placeholder"></div>
				<?php endif; ?>
			</div>

			<!-- Film grain texture -->
			<div class="twostars-testimonials-hero__grain" aria-hidden="true"></div>

			<!-- Colour overlay -->
			<div class="twostars-testimonials-hero__overlay" aria-hidden="true"></div>

			<!-- Content -->
			<div class="twostars-testimonials-hero__content">
				<div class="twostars-testimonials-hero__inner">

					<?php if ($eyebrow !== '') : ?>
						<div class="twostars-testimonials-hero__eyebrow" data-twostars-animate="fade-in-up">
							<span class="twostars-testimonials-hero__eyebrow-line" aria-hidden="true"></span>
							<span class="twostars-testimonials-hero__eyebrow-text"><?php echo esc_html($eyebrow); ?></span>
						</div>
					<?php endif; ?>

					<?php if ($title !== '') : ?>
						<h1 class="twostars-testimonials-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.12s;">
							<?php echo $this->highlight_hero_title($title, $highlight); ?>
						</h1>
					<?php endif; ?>

					<?php if ($excerpt !== '') : ?>
						<p class="twostars-testimonials-hero__excerpt" data-twostars-animate="fade-in-up" style="--twostars-delay:0.24s;">
							<?php echo esc_html($excerpt); ?>
						</p>
					<?php endif; ?>

					<div class="twostars-testimonials-hero__actions" data-twostars-animate="fade-in-up" style="--twostars-delay:0.36s;">
						<?php if (! empty($settings['primary_cta_text']) && ! empty($settings['primary_cta_link']['url'])) : ?>
							<a class="twostars-testimonials-hero__primary" <?php echo $this->link_attributes((array) $settings['primary_cta_link']); ?>>
								<span><?php echo esc_html((string) $settings['primary_cta_text']); ?></span>
							</a>
						<?php endif; ?>
						<?php if (! empty($settings['secondary_cta_text']) && ! empty($settings['secondary_cta_link']['url'])) : ?>
							<a class="twostars-testimonials-hero__secondary" <?php echo $this->link_attributes((array) $settings['secondary_cta_link']); ?>>
								<?php echo esc_html((string) $settings['secondary_cta_text']); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- Scroll indicator -->
				<div class="twostars-testimonials-hero__scroll" data-twostars-animate="fade-in-up" style="--twostars-delay:0.48s;" aria-hidden="true">
					<span class="twostars-testimonials-hero__scroll-text">Scroll</span>
					<span class="twostars-testimonials-hero__scroll-line"></span>
				</div>
			</div>
		</section>
		<?php
	}

	private function highlight_hero_title(string $title, string $highlight): string
	{
		$title     = trim($title);
		$highlight = trim($highlight);

		if ($title === '') {
			return '';
		}

		if ($highlight === '' || stripos($title, $highlight) === false) {
			return nl2br(esc_html($title));
		}

		$offset = (int) stripos($title, $highlight);
		$before = substr($title, 0, $offset);
		$match  = substr($title, $offset, strlen($highlight));
		$after  = substr($title, $offset + strlen($highlight));

		return nl2br(esc_html($before))
			. '<em class="twostars-testimonials-hero__title-em">' . esc_html($match) . '</em>'
			. nl2br(esc_html($after));
	}
}
