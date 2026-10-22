<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class AboutHeroWidget extends AboutPageWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-about-hero';
	}

	public function get_title(): string
	{
		return __('About Hero', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-header';
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

		$this->add_control(
			'background_image',
			[
				'label' => __('Background Image', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::MEDIA,
			]
		);

		$this->add_control(
			'eyebrow_text',
			[
				'label'   => __('Eyebrow Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Walvis Bay & Swakopmund', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'headline_text',
			[
				'label'   => __('Headline Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Where the Desert Meets the Ocean', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'highlight_phrase',
			[
				'label'   => __('Highlight Phrase', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Desert', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'excerpt_text',
			[
				'label'   => __('Excerpt Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Discover the wonders of Namibia’s coast with local experts who know every dune, every flamingo gathering, and every breathtaking view worth stopping for.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'primary_cta_text',
			[
				'label'   => __('Primary CTA Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Our Story', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'primary_cta_link',
			[
				'label'   => __('Primary CTA Link', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::URL,
				'default' => ['url' => home_url('/about/#about-story')],
			]
		);

		$this->add_control(
			'secondary_cta_text',
			[
				'label'   => __('Secondary CTA Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Explore Tours', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'secondary_cta_link',
			[
				'label'   => __('Secondary CTA Link', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::URL,
				'default' => ['url' => home_url('/tours/')],
			]
		);

		$this->add_control(
			'enable_parallax',
			[
				'label'        => __('Enable Parallax', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

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
					'size' => 50,
				],
			]
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'value',
			[
				'label'   => __('Stat Value', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('5', 'twostars-elementor-widgets'),
			]
		);
		$repeater->add_control(
			'label',
			[
				'label'   => __('Stat Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Current Tours', 'twostars-elementor-widgets'),
			]
		);
		$this->add_control(
			'stats',
			[
				'label'       => __('Stats', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ value }}}',
				'default'     => [
					['value' => __('5', 'twostars-elementor-widgets'), 'label' => __('Current Tours', 'twostars-elementor-widgets')],
					['value' => __('2', 'twostars-elementor-widgets'), 'label' => __('Coastal Pickup Zones', 'twostars-elementor-widgets')],
					['value' => __('Private', 'twostars-elementor-widgets'), 'label' => __('Flexible Option', 'twostars-elementor-widgets')],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$page = $this->resolve_page_post();
		$settings = $this->get_settings_for_display();

		if (! $page) {
			return;
		}

		$image_id = ! empty($settings['background_image']['id']) ? absint($settings['background_image']['id']) : ($page ? get_post_thumbnail_id($page->ID) : 0);
		$title = trim((string) ($settings['headline_text'] ?? ''));
		$excerpt = trim((string) ($settings['excerpt_text'] ?? ($settings['subheadline_text'] ?? '')));
		$eyebrow = trim((string) ($settings['eyebrow_text'] ?? ''));
		$highlight_phrase = trim((string) ($settings['highlight_phrase'] ?? ''));
		$stats = is_array($settings['stats'] ?? null) ? $settings['stats'] : [];

		if ($title === '' && $page) {
			$title = get_the_title($page);
		}

		if ($excerpt === '' && $page) {
			$excerpt = get_the_excerpt($page);
		}

		$overlay_size = isset($settings['overlay_opacity']['size']) ? (float) $settings['overlay_opacity']['size'] : 50.0;
		$overlay_alpha = max(0.0, min(1.0, $overlay_size / 100));
		$section_classes = ['twostars-about-page-hero'];

		if (($settings['enable_parallax'] ?? '') === 'yes') {
			$section_classes[] = 'twostars-about-page-hero--parallax';
		}
		?>
		<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>" style="<?php echo esc_attr($this->build_inline_vars(['--twostars-about-overlay-opacity' => (string) $overlay_alpha])); ?>">
			<div class="twostars-about-page-hero__media">
				<?php if ($image_id > 0) : ?>
					<?php echo wp_get_attachment_image($image_id, 'full', false, ['class' => 'twostars-about-page-hero__image', 'loading' => 'eager']); ?>
				<?php else : ?>
					<div class="twostars-about-page-hero__image twostars-about-page-hero__image--placeholder"></div>
				<?php endif; ?>
			</div>
			<div class="twostars-about-page-hero__grain"></div>
			<div class="twostars-about-page-hero__overlay"></div>
			<div class="twostars-about-page-hero__content">
				<div class="twostars-about-page-hero__left">
					<?php if ($eyebrow !== '') : ?>
						<div class="twostars-about-page-hero__eyebrow" data-twostars-animate="fade-in-up">
							<span class="twostars-about-page-hero__eyebrow-line" aria-hidden="true"></span>
							<span class="twostars-about-page-hero__eyebrow-text"><?php echo esc_html($eyebrow); ?></span>
						</div>
					<?php endif; ?>
					<?php if ($title !== '') : ?>
						<h1 class="twostars-about-page-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.12s;"><?php echo $this->highlight_title($title, $highlight_phrase); ?></h1>
					<?php endif; ?>
					<div class="twostars-about-page-hero__actions" data-twostars-animate="fade-in-up" style="--twostars-delay:0.24s;">
						<?php if (! empty($settings['primary_cta_text']) && ! empty($settings['primary_cta_link']['url'])) : ?>
							<a class="twostars-about-page-hero__primary" <?php echo $this->link_attributes((array) $settings['primary_cta_link']); ?>>
								<span><?php echo esc_html((string) $settings['primary_cta_text']); ?></span>
							</a>
						<?php endif; ?>
						<?php if (! empty($settings['secondary_cta_text']) && ! empty($settings['secondary_cta_link']['url'])) : ?>
							<a class="twostars-about-page-hero__secondary" <?php echo $this->link_attributes((array) $settings['secondary_cta_link']); ?>>
								<?php echo esc_html((string) $settings['secondary_cta_text']); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
				<div class="twostars-about-page-hero__right" data-twostars-animate="fade-in-up" style="--twostars-delay:0.32s;">
					<?php if ($excerpt !== '') : ?>
						<p class="twostars-about-page-hero__excerpt"><?php echo esc_html($excerpt); ?></p>
					<?php endif; ?>
					<?php if ($stats !== []) : ?>
						<div class="twostars-about-page-hero__stats">
							<?php foreach ($stats as $item) : ?>
								<div class="twostars-about-page-hero__stat">
									<strong class="twostars-about-page-hero__stat-value"><?php echo esc_html((string) ($item['value'] ?? '')); ?></strong>
									<span class="twostars-about-page-hero__stat-label"><?php echo esc_html((string) ($item['label'] ?? '')); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="twostars-about-page-hero__scroll" data-twostars-animate="fade-in-up" style="--twostars-delay:0.44s;" aria-hidden="true">
				<span class="twostars-about-page-hero__scroll-line"></span>
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
