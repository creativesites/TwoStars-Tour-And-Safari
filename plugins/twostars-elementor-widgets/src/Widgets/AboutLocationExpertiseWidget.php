<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class AboutLocationExpertiseWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-about-location-expertise';
	}

	public function get_title(): string
	{
		return __('About Location Expertise', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-google-maps';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('section_title', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Why Walvis Bay & Swakopmund Matter', 'twostars-elementor-widgets')]);
		$this->add_control(
			'body_content',
			[
				'label'   => __('Body Content', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => '',
			]
		);
		$this->add_control(
			'enable_highlights',
			[
				'label'        => __('Enable Highlights', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

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
		$repeater->add_control('text', ['label' => __('Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Direct access to Walvis Bay Lagoon', 'twostars-elementor-widgets')]);
		$this->add_control(
			'highlights',
			[
				'label'       => __('Highlights', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ text }}}',
				'condition'   => ['enable_highlights' => 'yes'],
				'default'     => [
					['text' => __('Direct access to Walvis Bay Lagoon', 'twostars-elementor-widgets'), 'icon_name' => 'waves'],
					['text' => __('Short transfer to Swakopmund', 'twostars-elementor-widgets'), 'icon_name' => 'route'],
					['text' => __('Coastal pickup convenience', 'twostars-elementor-widgets'), 'icon_name' => 'map-pinned'],
					['text' => __('Gateway to Sandwich Harbour and Spitzkoppe', 'twostars-elementor-widgets'), 'icon_name' => 'mountain'],
				],
			]
		);

		$this->add_control(
			'visual_type',
			[
				'label'   => __('Visual Type', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'image' => __('Image', 'twostars-elementor-widgets'),
					'embed' => __('Map Embed', 'twostars-elementor-widgets'),
				],
				'default' => 'image',
			]
		);
		$this->add_control('image', ['label' => __('Image', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA, 'condition' => ['visual_type' => 'image']]);
		$this->add_control('secondary_image', ['label' => __('Secondary Image', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA, 'condition' => ['visual_type' => 'image']]);
		$this->add_control('map_embed', ['label' => __('Map Embed Code', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'condition' => ['visual_type' => 'embed']]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$body_content = trim((string) ($settings['body_content'] ?? ''));
		$visual_type = ($settings['visual_type'] ?? 'image') === 'embed' ? 'embed' : 'image';
		$map_embed = trim((string) ($settings['map_embed'] ?? ''));
		$image_id = ! empty($settings['image']['id']) ? absint($settings['image']['id']) : 0;
		$secondary_image_id = ! empty($settings['secondary_image']['id']) ? absint($settings['secondary_image']['id']) : 0;
		$secondary_image_url = $secondary_image_id > 0
			? wp_get_attachment_image_url($secondary_image_id, 'large')
			: content_url('uploads/2026/03/WhatsApp-Image-2026-03-27-at-5.55.41-AM-2-1.jpeg');
		$allowed_embed = [
			'iframe' => [
				'src'             => [],
				'width'           => [],
				'height'          => [],
				'style'           => [],
				'allow'           => [],
				'loading'         => [],
				'referrerpolicy'  => [],
				'allowfullscreen' => [],
			],
		];
		?>
		<section class="twostars-about-location">
			<div class="twostars-about-location__grid">
				<div class="twostars-about-location__content">
					<?php if (! empty($settings['section_title'])) : ?>
						<h2 class="twostars-about-location__title" data-twostars-animate="fade-in-left"><?php echo esc_html((string) $settings['section_title']); ?></h2>
					<?php endif; ?>
					<?php if ($body_content !== '') : ?>
						<div class="twostars-about-location__body" data-twostars-animate="fade-in-left" style="--twostars-delay:0.08s;">
							<?php echo wp_kses_post($body_content); ?>
						</div>
					<?php endif; ?>
					<?php if (($settings['enable_highlights'] ?? 'yes') === 'yes' && ! empty($settings['highlights']) && is_array($settings['highlights'])) : ?>
						<div class="twostars-about-location__highlights" data-twostars-animate="fade-in-left" style="--twostars-delay:0.16s;">
							<?php foreach ($settings['highlights'] as $item) : ?>
								<div class="twostars-about-location__highlight">
									<span class="twostars-about-location__highlight-icon" aria-hidden="true">
										<?php echo $this->render_lucide_icon($this->resolve_highlight_icon_name($item), 'twostars-about-location__highlight-svg'); ?>
									</span>
									<span><?php echo esc_html((string) ($item['text'] ?? '')); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="twostars-about-location__visual" data-twostars-animate="fade-in-right">
					<div class="twostars-about-location__visual-frame">
						<?php if ($visual_type === 'embed' && $map_embed !== '') : ?>
							<div class="twostars-about-location__embed"><?php echo wp_kses($map_embed, $allowed_embed); ?></div>
						<?php else : ?>
							<div class="twostars-about-location__mosaic">
								<div class="twostars-about-location__tile twostars-about-location__tile--large">
									<?php if ($image_id > 0) : ?>
										<?php echo wp_get_attachment_image($image_id, 'large', false, ['class' => 'twostars-about-location__image', 'loading' => 'lazy']); ?>
									<?php else : ?>
										<div class="twostars-about-location__image twostars-about-location__image--placeholder"></div>
									<?php endif; ?>
								</div>
								<div class="twostars-about-location__tile twostars-about-location__tile--stack-top">
									<?php if ($secondary_image_id > 0) : ?>
										<?php echo wp_get_attachment_image($secondary_image_id, 'medium_large', false, ['class' => 'twostars-about-location__image', 'loading' => 'lazy']); ?>
									<?php elseif ($secondary_image_url !== '') : ?>
										<img class="twostars-about-location__image" src="<?php echo esc_url($secondary_image_url); ?>" alt="" loading="lazy">
									<?php elseif ($image_id > 0) : ?>
										<?php echo wp_get_attachment_image($image_id, 'medium_large', false, ['class' => 'twostars-about-location__image', 'loading' => 'lazy']); ?>
									<?php else : ?>
										<div class="twostars-about-location__image twostars-about-location__image--placeholder"></div>
									<?php endif; ?>
								</div>
								<div class="twostars-about-location__tile twostars-about-location__tile--stack-bottom">
									<?php if ($image_id > 0) : ?>
										<?php echo wp_get_attachment_image($image_id, 'medium_large', false, ['class' => 'twostars-about-location__image', 'loading' => 'lazy']); ?>
									<?php else : ?>
										<div class="twostars-about-location__image twostars-about-location__image--placeholder"></div>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
					<div class="twostars-about-location__accent" aria-hidden="true"></div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private function resolve_highlight_icon_name(array $item): string
	{
		$icon_name = (string) ($item['icon_name'] ?? '');
		$text = strtolower((string) ($item['text'] ?? ''));

		if (str_contains($text, 'lagoon') || str_contains($text, 'water')) {
			return 'waves';
		}

		if (str_contains($text, 'transfer') || str_contains($text, 'route') || str_contains($text, 'short')) {
			return 'route';
		}

		if (str_contains($text, 'pickup') || str_contains($text, 'coastal')) {
			return 'map-pinned';
		}

		if (str_contains($text, 'sandwich harbour') || str_contains($text, 'spitzkoppe') || str_contains($text, 'gateway')) {
			return 'mountain';
		}

		if ($icon_name !== '') {
			return $icon_name;
		}

		return 'check-circle';
	}
}
