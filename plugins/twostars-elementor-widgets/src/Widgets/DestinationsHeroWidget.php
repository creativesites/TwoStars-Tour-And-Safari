<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class DestinationsHeroWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-destinations-hero';
	}

	public function get_title(): string
	{
		return __('Destinations Hero', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-archive-posts';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control('background_image', ['label' => __('Background Image', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA]);
		$this->add_control('subtitle', ['label' => __('Eyebrow Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Destinations & Stops', 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __("Places That Shape\nEvery Two Stars Day", 'twostars-elementor-widgets')]);
		$this->add_control('highlight_phrase', ['label' => __('Highlight Phrase', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Shape', 'twostars-elementor-widgets')]);
		$this->add_control('description', ['label' => __('Excerpt', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('From flamingo-rich lagoon edges and pink salt pans to dune viewpoints, town landmarks, and the granite drama of Spitzkoppe, these are the real stops behind the current Two Stars tours.', 'twostars-elementor-widgets')]);
		$this->add_control('cta_text', ['label' => __('Primary CTA Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Explore Tours', 'twostars-elementor-widgets')]);
		$this->add_control('cta_link', ['label' => __('Primary CTA Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/tours/')]]);
		$this->add_control('secondary_cta_text', ['label' => __('Secondary CTA Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Book Now', 'twostars-elementor-widgets')]);
		$this->add_control('secondary_cta_link', ['label' => __('Secondary CTA Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/booking/')]]);
		$this->add_control(
			'overlay_opacity',
			[
				'label'      => __('Overlay Opacity', 'twostars-elementor-widgets'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['%'],
				'range'      => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'    => [
					'unit' => '%',
					'size' => 54,
				],
			]
		);

		$repeater = new Repeater();
		$repeater->add_control('value', ['label' => __('Stat Value', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('6', 'twostars-elementor-widgets')]);
		$repeater->add_control('label', ['label' => __('Stat Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Destinations', 'twostars-elementor-widgets')]);
		$this->add_control(
			'stats',
			[
				'label'       => __('Stats', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ value }}}',
				'default'     => [
					['value' => __('6', 'twostars-elementor-widgets'), 'label' => __("Real\nDestinations", 'twostars-elementor-widgets')],
					['value' => __('5', 'twostars-elementor-widgets'), 'label' => __("Current\nTours", 'twostars-elementor-widgets')],
					['value' => __('Walvis Bay', 'twostars-elementor-widgets'), 'label' => __("Operating\nBase", 'twostars-elementor-widgets')],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$image_id = absint($settings['background_image']['id'] ?? 0);
		$title = trim((string) ($settings['title'] ?? ''));
		$highlight_phrase = trim((string) ($settings['highlight_phrase'] ?? ''));
		$description = trim((string) ($settings['description'] ?? ''));
		$stats = is_array($settings['stats'] ?? null) ? $settings['stats'] : [];
		$overlay_size = isset($settings['overlay_opacity']['size']) ? (float) $settings['overlay_opacity']['size'] : 54.0;
		$overlay_alpha = max(0.0, min(1.0, $overlay_size / 100));
		?>
		<section class="twostars-destinations-hero" style="<?php echo esc_attr($this->build_inline_vars(['--twostars-destinations-overlay-opacity' => (string) $overlay_alpha])); ?>">
			<div class="twostars-destinations-hero__media" aria-hidden="true">
				<?php if ($image_id > 0) : ?>
					<?php echo wp_get_attachment_image($image_id, 'full', false, ['class' => 'twostars-destinations-hero__image', 'loading' => 'eager']); ?>
				<?php else : ?>
					<div class="twostars-destinations-hero__image twostars-destinations-hero__image--placeholder"></div>
				<?php endif; ?>
			</div>
			<div class="twostars-destinations-hero__grain"></div>
			<div class="twostars-destinations-hero__overlay"></div>
			<div class="twostars-destinations-hero__content">
				<div class="twostars-destinations-hero__left">
					<p class="twostars-destinations-hero__subtitle" data-twostars-animate="fade-in-up"><?php echo esc_html((string) ($settings['subtitle'] ?? '')); ?></p>
					<h1 class="twostars-destinations-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.15s;"><?php echo $this->highlight_title($title, $highlight_phrase); ?></h1>
					<div class="twostars-destinations-hero__actions" data-twostars-animate="fade-in-up" style="--twostars-delay:0.3s;">
						<?php if (! empty($settings['cta_text']) && ! empty($settings['cta_link']['url'])) : ?>
							<a class="twostars-destinations-hero__primary" <?php echo $this->link_attributes((array) $settings['cta_link']); ?>>
								<?php echo esc_html((string) $settings['cta_text']); ?>
							</a>
						<?php endif; ?>
						<?php if (! empty($settings['secondary_cta_text']) && ! empty($settings['secondary_cta_link']['url'])) : ?>
							<a class="twostars-destinations-hero__secondary" <?php echo $this->link_attributes((array) $settings['secondary_cta_link']); ?>>
								<?php echo esc_html((string) $settings['secondary_cta_text']); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
				<div class="twostars-destinations-hero__right" data-twostars-animate="fade-in-up" style="--twostars-delay:0.42s;">
					<p class="twostars-destinations-hero__description"><?php echo esc_html($description); ?></p>
					<?php if ($stats !== []) : ?>
						<div class="twostars-destinations-hero__stats">
							<?php foreach ($stats as $item) : ?>
								<div class="twostars-destinations-hero__stat">
									<strong class="twostars-destinations-hero__stat-value"><?php echo esc_html((string) ($item['value'] ?? '')); ?></strong>
									<span class="twostars-destinations-hero__stat-label"><?php echo nl2br(esc_html((string) ($item['label'] ?? ''))); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="twostars-destinations-hero__scroll" data-twostars-animate="fade-in-up" style="--twostars-delay:0.54s;" aria-hidden="true">
				<span class="twostars-destinations-hero__scroll-line"></span>
			</div>
		</section>
		<?php
	}

	private function highlight_title(string $title, string $highlight_phrase): string
	{
		$title = trim($title);
		$highlight_phrase = trim($highlight_phrase);

		if ($title === '') {
			return '';
		}

		if ($highlight_phrase === '' || stripos($title, $highlight_phrase) === false) {
			return nl2br(esc_html($title));
		}

		$offset = (int) stripos($title, $highlight_phrase);
		$before = substr($title, 0, $offset);
		$match = substr($title, $offset, strlen($highlight_phrase));
		$after = substr($title, $offset + strlen($highlight_phrase));

		return nl2br(esc_html($before))
			. '<em>' . esc_html($match) . '</em>'
			. nl2br(esc_html($after));
	}
}
