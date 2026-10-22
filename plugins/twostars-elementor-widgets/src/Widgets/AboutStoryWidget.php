<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class AboutStoryWidget extends AboutPageWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-about-story';
	}

	public function get_title(): string
	{
		return __('About Story', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-text-area';
	}

	protected function register_controls(): void
	{
		$this->register_page_source_controls();

		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('section_label', ['label' => __('Section Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Our Story', 'twostars-elementor-widgets')]);
		$this->add_control('heading', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Born in Walvis Bay, Driven by Adventure', 'twostars-elementor-widgets')]);
		$this->add_control(
			'body_content',
			[
				'label'   => __('Body Content', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => '',
			]
		);
		$this->add_control(
			'image',
			[
				'label' => __('Image', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::MEDIA,
			]
		);
		$this->add_control(
			'secondary_image',
			[
				'label' => __('Secondary Image', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::MEDIA,
			]
		);
		$this->add_control(
			'image_position',
			[
				'label'   => __('Image Position', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'right' => __('Right', 'twostars-elementor-widgets'),
					'left'  => __('Left', 'twostars-elementor-widgets'),
				],
				'default' => 'right',
			]
		);
		$this->add_control(
			'enable_stats_row',
			[
				'label'        => __('Enable Stats Row', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$repeater = new Repeater();
		$repeater->add_control('value', ['label' => __('Stat Number', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Local Base', 'twostars-elementor-widgets')]);
		$repeater->add_control('label', ['label' => __('Stat Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Walvis Bay & Swakopmund', 'twostars-elementor-widgets')]);
		$this->add_control(
			'stats',
			[
				'label'       => __('Stats', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ value }}}',
				'condition'   => ['enable_stats_row' => 'yes'],
				'default'     => [
					['value' => __('Walvis Bay', 'twostars-elementor-widgets'), 'label' => __('Operating Base', 'twostars-elementor-widgets')],
					['value' => __('5 Tours', 'twostars-elementor-widgets'), 'label' => __('Current Collection', 'twostars-elementor-widgets')],
					['value' => __('Private Options', 'twostars-elementor-widgets'), 'label' => __('Available On Request', 'twostars-elementor-widgets')],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$page = $this->resolve_page_post();
		$settings = $this->get_settings_for_display();

		$body_content = trim((string) ($settings['body_content'] ?? ''));
		if ($body_content === '' && $page) {
			$body_content = wpautop($page->post_content);
		}

		$image_id = ! empty($settings['image']['id']) ? absint($settings['image']['id']) : ($page ? get_post_thumbnail_id($page->ID) : 0);
		$secondary_image_id = ! empty($settings['secondary_image']['id']) ? absint($settings['secondary_image']['id']) : 0;
		$secondary_image_url = $secondary_image_id > 0
			? wp_get_attachment_image_url($secondary_image_id, 'large')
			: content_url('uploads/2026/03/WhatsApp-Image-2026-03-27-at-6.00.45-AM-8.jpeg');
		$image_position = ($settings['image_position'] ?? 'right') === 'left' ? 'left' : 'right';
		$stats = is_array($settings['stats'] ?? null) ? $settings['stats'] : [];
		?>
		<section id="about-story" class="twostars-about-story twostars-about-story--image-<?php echo esc_attr($image_position); ?>">
			<div class="twostars-about-story__grid">
				<div class="twostars-about-story__content">
					<?php if (! empty($settings['section_label'])) : ?>
						<p class="twostars-about-story__label" data-twostars-animate="fade-in-left"><?php echo esc_html((string) $settings['section_label']); ?></p>
					<?php endif; ?>
					<?php if (! empty($settings['heading'])) : ?>
						<h2 data-twostars-animate="fade-in-left" style="--twostars-delay:0.08s;"><?php echo esc_html((string) $settings['heading']); ?></h2>
					<?php endif; ?>
					<?php if ($body_content !== '') : ?>
						<div class="twostars-about-story__body" data-twostars-animate="fade-in-left" style="--twostars-delay:0.16s;">
							<?php echo wp_kses_post($body_content); ?>
						</div>
					<?php endif; ?>
					<?php if (($settings['enable_stats_row'] ?? '') === 'yes' && $stats !== []) : ?>
						<div class="twostars-about-story__stats" data-twostars-animate="fade-in-left" style="--twostars-delay:0.24s;">
							<?php foreach ($stats as $item) : ?>
								<div class="twostars-about-story__stat">
									<strong><?php echo esc_html((string) ($item['value'] ?? '')); ?></strong>
									<span><?php echo esc_html((string) ($item['label'] ?? '')); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="twostars-about-story__visual" data-twostars-animate="<?php echo esc_attr($image_position === 'left' ? 'fade-in-left' : 'fade-in-right'); ?>">

    <div class="twostars-about-story__glow twostars-about-story__glow--orange" aria-hidden="true"></div>
    <div class="twostars-about-story__glow twostars-about-story__glow--cyan"   aria-hidden="true"></div>
    <div class="twostars-about-story__dot-grid" aria-hidden="true"></div>

    <div class="twostars-about-story__rings" aria-hidden="true">
        <span class="twostars-about-story__ring twostars-about-story__ring--pulse"></span>
        <span class="twostars-about-story__ring twostars-about-story__ring--expand"></span>
    </div>

    <div class="twostars-about-story__image-frame">
        <div class="twostars-about-story__image-stack">

            <div class="twostars-about-story__image-card twostars-about-story__image-card--primary">
                <?php if ($image_id > 0) : ?>
                    <?php echo wp_get_attachment_image($image_id, 'large', false, ['class' => 'twostars-about-story__image', 'loading' => 'lazy']); ?>
                <?php else : ?>
                    <div class="twostars-about-story__image twostars-about-story__image--placeholder"></div>
                <?php endif; ?>
                <div class="twostars-about-story__card-overlay" aria-hidden="true"></div>
            </div>

            <div class="twostars-about-story__image-card twostars-about-story__image-card--secondary">
                <?php if ($secondary_image_id > 0) : ?>
                    <?php echo wp_get_attachment_image($secondary_image_id, 'large', false, ['class' => 'twostars-about-story__image', 'loading' => 'lazy']); ?>
                <?php elseif ($secondary_image_url !== '') : ?>
                    <img class="twostars-about-story__image" src="<?php echo esc_url($secondary_image_url); ?>" alt="" loading="lazy">
                <?php elseif ($image_id > 0) : ?>
                    <?php echo wp_get_attachment_image($image_id, 'medium_large', false, ['class' => 'twostars-about-story__image', 'loading' => 'lazy']); ?>
                <?php else : ?>
                    <div class="twostars-about-story__image twostars-about-story__image--placeholder"></div>
                <?php endif; ?>
            </div>

            <div class="twostars-about-story__badge" aria-hidden="true">
                <span class="twostars-about-story__badge-dot"></span>
                <span class="twostars-about-story__badge-text">Walvis Bay, Namibia</span>
            </div>

        </div>
    </div>

    <div class="twostars-about-story__accent-lines" aria-hidden="true">
        <span></span><span></span><span></span>
    </div>

</div>
			</div>
		</section>
		<?php
	}
}
