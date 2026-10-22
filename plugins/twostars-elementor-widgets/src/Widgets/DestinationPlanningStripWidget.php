<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Repeater;

final class DestinationPlanningStripWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-destination-planning-strip';
	}

	public function get_title(): string
	{
		return __('Destination Planning Strip', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-call-to-action';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('subtitle', ['label' => __('Subtitle', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Turn places into a plan', 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Found the stop that made you want to book?', 'twostars-elementor-widgets')]);
		$this->add_control('text', ['label' => __('Body Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Tell us which destination caught your attention and we will point you to the right shared tour or private option with the best route logic from Walvis Bay or Swakopmund.', 'twostars-elementor-widgets')]);
		$this->add_control('cta_text', ['label' => __('Primary CTA Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Book Now', 'twostars-elementor-widgets')]);
		$this->add_control('cta_link', ['label' => __('Primary CTA Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/booking/')]]);
		$this->add_control('secondary_cta_text', ['label' => __('Secondary CTA Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Explore Tours', 'twostars-elementor-widgets')]);
		$this->add_control('secondary_cta_link', ['label' => __('Secondary CTA Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/tours/')]]);

		$repeater = new Repeater();
		$repeater->add_control(
			'icon',
			[
				'label'   => __('Icon', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-map-pin',
					'library' => 'fa-solid',
				],
			]
		);
		$repeater->add_control('text', ['label' => __('Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Walvis Bay & Swakopmund departures', 'twostars-elementor-widgets')]);
		$this->add_control(
			'trust_items',
			[
				'label'       => __('Trust Items', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => [
					['text' => __('Walvis Bay & Swakopmund departures', 'twostars-elementor-widgets'), 'icon' => ['value' => 'fas fa-map-pin', 'library' => 'fa-solid']],
					['text' => __('Private options available', 'twostars-elementor-widgets'), 'icon' => ['value' => 'fas fa-car-side', 'library' => 'fa-solid']],
					['text' => __('Direct booking support', 'twostars-elementor-widgets'), 'icon' => ['value' => 'fas fa-comments', 'library' => 'fa-solid']],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-destination-planning-strip" data-twostars-animate="fade-in-up">
			<div class="twostars-destination-planning-strip__content">
				<p class="twostars-destination-planning-strip__subtitle"><?php echo esc_html((string) ($settings['subtitle'] ?? '')); ?></p>
				<h2 class="twostars-destination-planning-strip__title"><?php echo nl2br(esc_html((string) ($settings['title'] ?? ''))); ?></h2>
				<?php if (! empty($settings['text'])) : ?>
					<p class="twostars-destination-planning-strip__text"><?php echo esc_html((string) $settings['text']); ?></p>
				<?php endif; ?>
				<div class="twostars-destination-planning-strip__actions">
					<?php if (! empty($settings['cta_text']) && ! empty($settings['cta_link']['url'])) : ?>
						<a class="twostars-safari-button twostars-safari-button--primary" <?php echo $this->link_attributes((array) $settings['cta_link']); ?>>
							<?php echo esc_html((string) $settings['cta_text']); ?>
						</a>
					<?php endif; ?>
					<?php if (! empty($settings['secondary_cta_text']) && ! empty($settings['secondary_cta_link']['url'])) : ?>
						<a class="twostars-safari-button twostars-safari-button--ghost" <?php echo $this->link_attributes((array) $settings['secondary_cta_link']); ?>>
							<?php echo esc_html((string) $settings['secondary_cta_text']); ?>
						</a>
					<?php endif; ?>
				</div>
				<?php if (! empty($settings['trust_items']) && is_array($settings['trust_items'])) : ?>
					<div class="twostars-destination-planning-strip__trust">
						<?php foreach ($settings['trust_items'] as $item) : ?>
							<div class="twostars-destination-planning-strip__trust-item">
								<span class="twostars-destination-planning-strip__trust-icon" aria-hidden="true">
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
