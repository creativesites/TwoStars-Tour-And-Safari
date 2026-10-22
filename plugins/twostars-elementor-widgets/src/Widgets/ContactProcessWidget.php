<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class ContactProcessWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-contact-process';
	}

	public function get_title(): string
	{
		return __('Contact Process Timeline', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-navigator';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Steps', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'heading',
			[
				'label'   => __('Heading', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('From Inquiry to Safari', 'twostars-elementor-widgets'),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'step_label',
			[
				'label'   => __('Step Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Step 1', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'title',
			[
				'label'   => __('Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Connect', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'description',
			[
				'label'   => __('Description', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Reach out via form or WhatsApp to share your initial ideas.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'steps',
			[
				'label'       => __('Process Steps', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => [
					[
						'step_label'  => __('Phase 01', 'twostars-elementor-widgets'),
						'title'       => __('Connect', 'twostars-elementor-widgets'),
						'description' => __('Reach out via the inquiry form or WhatsApp. This starts the conversation around your timing and route goals.', 'twostars-elementor-widgets'),
					],
					[
						'step_label'  => __('Phase 02', 'twostars-elementor-widgets'),
						'title'       => __('Consult', 'twostars-elementor-widgets'),
						'description' => __('We align on travel style, lodge levels, and pacing to ensure the safari feels personal and practical.', 'twostars-elementor-widgets'),
					],
					[
						'step_label'  => __('Phase 03', 'twostars-elementor-widgets'),
						'title'       => __('Compose', 'twostars-elementor-widgets'),
						'description' => __('Our designers shape a detailed itinerary proposal with clear pricing and inclusion details.', 'twostars-elementor-widgets'),
					],
					[
						'step_label'  => __('Phase 04', 'twostars-elementor-widgets'),
						'title'       => __('Confirm', 'twostars-elementor-widgets'),
						'description' => __('Once you are happy with the route, we handle all logistics and lodges to finalize your booking.', 'twostars-elementor-widgets'),
					],
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-contact-process">
			<?php if (! empty($settings['heading'])) : ?>
				<h2 class="twostars-contact-process__heading" data-twostars-animate="fade-in-up">
					<?php echo esc_html((string) $settings['heading']); ?>
				</h2>
			<?php endif; ?>

			<div class="twostars-contact-process__timeline">
				<?php foreach (($settings['steps'] ?? []) as $index => $item) : ?>
					<div 
						class="twostars-contact-process__item" 
						data-twostars-animate="fade-in-up" 
						style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 4) * 0.15, 2)); ?>s;"
					>
						<div class="twostars-contact-process__node">
							<span class="twostars-contact-process__dot"></span>
							<?php if ($index < count($settings['steps']) - 1) : ?>
								<span class="twostars-contact-process__line"></span>
							<?php endif; ?>
						</div>
						<div class="twostars-contact-process__body">
							<p class="twostars-contact-process__label"><?php echo esc_html((string) $item['step_label']); ?></p>
							<h3 class="twostars-contact-process__title"><?php echo esc_html((string) $item['title']); ?></h3>
							<p class="twostars-contact-process__description"><?php echo esc_html((string) $item['description']); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
