<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;

final class ContactHeroWidget extends ContactPageWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-contact-hero';
	}

	public function get_title(): string
	{
		return __('Contact Hero', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-banner';
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
		$this->add_control('label', ['label' => __('Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Contact Two Stars', 'twostars-elementor-widgets')]);
		$this->add_control('highlight_text', ['label' => __('Highlight Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Response within 24 hours', 'twostars-elementor-widgets')]);
		$this->add_control('note_text', ['label' => __('Support Note', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Share your dates, route ideas, or level of comfort you want and we will shape the right next step from there.', 'twostars-elementor-widgets')]);
		$this->end_controls_section();

		$this->start_controls_section(
			'concierge_section',
			[
				'label' => __('Concierge Profile', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'show_concierge',
			[
				'label'        => __('Show Concierge Profile', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'concierge_image',
			[
				'label'     => __('Concierge Photo', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::MEDIA,
				'condition' => ['show_concierge' => 'yes'],
			]
		);

		$this->add_control(
			'concierge_name',
			[
				'label'     => __('Name', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXT,
				'default'   => __('Sarah', 'twostars-elementor-widgets'),
				'condition' => ['show_concierge' => 'yes'],
			]
		);

		$this->add_control(
			'concierge_role',
			[
				'label'     => __('Role', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXT,
				'default'   => __('Senior Safari Designer', 'twostars-elementor-widgets'),
				'condition' => ['show_concierge' => 'yes'],
			]
		);

		$this->add_control(
			'concierge_availability',
			[
				'label'     => __('Availability Status', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXT,
				'default'   => __('Available for consultations', 'twostars-elementor-widgets'),
				'condition' => ['show_concierge' => 'yes'],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$page     = $this->resolve_page_post();
		$settings = $this->get_settings_for_display();

		if (! $page) {
			return;
		}

		$image_id = ! empty($settings['background_image']['id']) ? absint($settings['background_image']['id']) : get_post_thumbnail_id($page->ID);
		$excerpt  = trim((string) $page->post_excerpt);
		?>
		<section class="twostars-contact-hero">
			<div class="twostars-contact-hero__media">
				<?php if ($image_id > 0) : ?>
					<?php echo wp_get_attachment_image($image_id, 'full', false, ['class' => 'twostars-contact-hero__image', 'loading' => 'eager']); ?>
				<?php else : ?>
					<div class="twostars-contact-hero__image twostars-contact-hero__image--placeholder"></div>
				<?php endif; ?>
			</div>
			<div class="twostars-contact-hero__overlay"></div>
			<div class="twostars-contact-hero__content">
				<div class="twostars-contact-hero__copy">
					<p class="twostars-contact-hero__label" data-twostars-animate="fade-in-up"><?php echo esc_html((string) ($settings['label'] ?? '')); ?></p>
					<h1 class="twostars-contact-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.12s;"><?php echo esc_html(get_the_title($page)); ?></h1>
					<?php if ($excerpt !== '') : ?>
						<p class="twostars-contact-hero__excerpt" data-twostars-animate="fade-in-up" style="--twostars-delay:0.24s;"><?php echo esc_html($excerpt); ?></p>
					<?php endif; ?>

					<?php if (($settings['show_concierge'] ?? 'yes') === 'yes') : ?>
						<div class="twostars-contact-hero__concierge" data-twostars-animate="fade-in-up" style="--twostars-delay:0.36s;">
							<div class="twostars-contact-hero__concierge-avatar">
								<?php if (! empty($settings['concierge_image']['id'])) : ?>
									<?php echo wp_get_attachment_image($settings['concierge_image']['id'], [80, 80]); ?>
								<?php else : ?>
									<div class="twostars-contact-hero__concierge-placeholder"></div>
								<?php endif; ?>
							</div>
							<div class="twostars-contact-hero__concierge-info">
								<p class="twostars-contact-hero__concierge-name"><?php echo esc_html((string) $settings['concierge_name']); ?></p>
								<p class="twostars-contact-hero__concierge-role"><?php echo esc_html((string) $settings['concierge_role']); ?></p>
								<?php if (! empty($settings['concierge_availability'])) : ?>
									<p class="twostars-contact-hero__concierge-status">
										<span class="twostars-contact-hero__status-dot"></span>
										<?php echo esc_html((string) $settings['concierge_availability']); ?>
									</p>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
				<aside class="twostars-contact-hero__panel" data-twostars-animate="fade-in-up" style="--twostars-delay:0.48s;">
					<?php if (! empty($settings['highlight_text'])) : ?>
						<p class="twostars-contact-hero__highlight"><?php echo esc_html((string) $settings['highlight_text']); ?></p>
					<?php endif; ?>
					<?php if (! empty($settings['note_text'])) : ?>
						<p class="twostars-contact-hero__note"><?php echo esc_html((string) $settings['note_text']); ?></p>
					<?php endif; ?>
				</aside>
			</div>
		</section>
		<?php
	}
}
