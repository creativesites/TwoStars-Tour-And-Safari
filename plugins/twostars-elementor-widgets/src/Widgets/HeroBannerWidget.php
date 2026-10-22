<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;

final class HeroBannerWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-safari-hero';
	}

	public function get_title(): string
	{
		return __('Adventure Hero Banner', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-slider-push';
	}

	protected function register_controls(): void
	{
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
			'title',
			[
				'label'   => __('Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Adventure through Namibia with vibrant tours, wild moments, and stories worth sharing.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'emphasized_word',
			[
				'label'   => __('Highlighted Word', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Namibia', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'subtitle',
			[
				'label'   => __('Subtitle', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('ADVENTURE BEYOND THE HORIZON', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'cta_text',
			[
				'label'   => __('CTA Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Explore Tours', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'cta_link',
			[
				'label'       => __('CTA Link', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::URL,
				'placeholder' => home_url('/booking/'),
				'default'     => [
					'url' => home_url('/tours/'),
				],
			]
		);

		$this->add_control(
			'secondary_cta_text',
			[
				'label'   => __('Secondary CTA Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Watch Video', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'secondary_cta_link',
			[
				'label'       => __('Secondary CTA Link', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::URL,
				'placeholder' => '#gallery',
				'default'     => [
					'url' => '#gallery',
				],
			]
		);

		$this->add_control(
			'show_scroll_indicator',
			[
				'label'        => __('Show Scroll Indicator', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Show', 'twostars-elementor-widgets'),
				'label_off'    => __('Hide', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'intelligence_section',
			[
				'label' => __('AI Concierge', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'enable_intelligence_panel',
			[
				'label'        => __('Show Concierge Panel', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Show', 'twostars-elementor-widgets'),
				'label_off'    => __('Hide', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'intelligence_label',
			[
				'label'     => __('Panel Label', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXT,
				'default'   => __('AI Concierge', 'twostars-elementor-widgets'),
				'condition' => [
					'enable_intelligence_panel' => 'yes',
				],
			]
		);

		$this->add_control(
			'intelligence_default_copy',
			[
				'label'     => __('Default Panel Copy', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => __('The site can adapt its guidance around trip research, route comparison, and booking readiness as visitors move through the homepage.', 'twostars-elementor-widgets'),
				'condition' => [
					'enable_intelligence_panel' => 'yes',
				],
			]
		);

		$this->add_control(
			'assistant_button_label',
			[
				'label'     => __('Assistant Button Label', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXT,
				'default'   => __('Open Concierge', 'twostars-elementor-widgets'),
				'condition' => [
					'enable_intelligence_panel' => 'yes',
				],
			]
		);

		$this->add_control(
			'assistant_fallback_link',
			[
				'label'       => __('Fallback Link', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::URL,
				'placeholder' => home_url('/contact/'),
				'default'     => [
					'url' => home_url('/contact/'),
				],
				'condition'   => [
					'enable_intelligence_panel' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings  = $this->get_settings_for_display();
		$image_id  = absint($settings['background_image']['id'] ?? 0);
		$image_alt = $image_id > 0 ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '';
		?>
		<section class="twostars-safari-hero">
			<div class="twostars-safari-hero__media" aria-hidden="true">
				<?php if ($image_id > 0) : ?>
					<?php
					echo wp_get_attachment_image(
						$image_id,
						'full',
						false,
						[
							'class'   => 'twostars-safari-hero__image',
							'loading' => 'eager',
							'alt'     => $image_alt !== '' ? $image_alt : '',
						]
					);
					?>
				<?php else : ?>
					<div class="twostars-safari-hero__image twostars-safari-hero__image--placeholder"></div>
				<?php endif; ?>
			</div>

			<div class="twostars-safari-hero__overlay"></div>

			<div class="twostars-safari-hero__content">
				<p class="twostars-safari-hero__subtitle" data-twostars-animate="fade-in-up" style="--twostars-delay:0.6s;">
					<?php echo esc_html((string) ($settings['subtitle'] ?? '')); ?>
				</p>
				<h1 class="twostars-safari-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.3s;">
					<?php echo wp_kses_post($this->render_highlighted_text((string) ($settings['title'] ?? ''), (string) ($settings['emphasized_word'] ?? ''), 'twostars-safari-hero__title-highlight')); ?>
				</h1>
				<?php if ((! empty($settings['cta_text']) && ! empty($settings['cta_link']['url'])) || (! empty($settings['secondary_cta_text']) && ! empty($settings['secondary_cta_link']['url']))) : ?>
					<div class="twostars-safari-hero__actions" data-twostars-animate="fade-in-up" style="--twostars-delay:0.9s;">
						<?php if (! empty($settings['cta_text']) && ! empty($settings['cta_link']['url'])) : ?>
							<a class="twostars-safari-button twostars-safari-button--primary" <?php echo $this->link_attributes((array) $settings['cta_link']); ?>>
								<?php echo esc_html((string) $settings['cta_text']); ?>
							</a>
						<?php endif; ?>
						<?php if (! empty($settings['secondary_cta_text']) && ! empty($settings['secondary_cta_link']['url'])) : ?>
							<a class="twostars-safari-button twostars-safari-button--ghost twostars-safari-button--secondary" <?php echo $this->link_attributes((array) $settings['secondary_cta_link']); ?>>
								<?php echo esc_html((string) $settings['secondary_cta_text']); ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if (($settings['enable_intelligence_panel'] ?? 'yes') === 'yes') : ?>
					<div
						class="twostars-safari-hero__insight"
						data-twostars-hero-intelligence
						data-default-copy="<?php echo esc_attr((string) ($settings['intelligence_default_copy'] ?? '')); ?>"
						data-default-label="<?php echo esc_attr((string) ($settings['assistant_button_label'] ?? '')); ?>"
						data-fallback-url="<?php echo esc_url((string) (($settings['assistant_fallback_link']['url'] ?? home_url('/contact/')))); ?>"
						data-twostars-animate="fade-in-up"
						style="--twostars-delay:1.1s;"
					>
						<?php if (! empty($settings['intelligence_label'])) : ?>
							<p class="twostars-safari-hero__insight-label"><?php echo esc_html((string) $settings['intelligence_label']); ?></p>
						<?php endif; ?>
						<p class="twostars-safari-hero__insight-copy" data-twostars-hero-intelligence-copy>
							<?php echo esc_html((string) ($settings['intelligence_default_copy'] ?? '')); ?>
						</p>
						<div class="twostars-safari-hero__insight-actions">
							<button
								type="button"
								class="twostars-safari-button twostars-safari-button--ghost twostars-safari-button--assistant"
								data-twostars-assistant-trigger
							>
								<?php echo esc_html((string) ($settings['assistant_button_label'] ?? __('Open Concierge', 'twostars-elementor-widgets'))); ?>
							</button>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<?php if (($settings['show_scroll_indicator'] ?? '') === 'yes') : ?>
				<div class="twostars-safari-hero__scroll" data-twostars-animate="fade-in-up" style="--twostars-delay:1.2s;">
					<span class="twostars-safari-hero__scroll-mouse" aria-hidden="true">
						<span class="twostars-safari-hero__scroll-dot"></span>
					</span>
					<span class="screen-reader-text"><?php esc_html_e('Scroll to explore more', 'twostars-elementor-widgets'); ?></span>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}
}
