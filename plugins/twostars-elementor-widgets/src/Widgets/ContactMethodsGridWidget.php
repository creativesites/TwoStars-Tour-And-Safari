<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Repeater;

final class ContactMethodsGridWidget extends ContactPageWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-contact-methods-grid';
	}

	public function get_title(): string
	{
		return __('Contact Methods Grid', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-gallery-grid';
	}

	protected function register_controls(): void
	{
		$this->register_page_source_controls();

		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Methods', 'twostars-elementor-widgets'),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'icon',
			[
				'label'   => __('Icon', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-envelope',
					'library' => 'fa-solid',
				],
			]
		);

		$repeater->add_control(
			'title',
			[
				'label'   => __('Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Email Inquiry', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'description',
			[
				'label'   => __('Description', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Send us your travel details and we will reply with route ideas.', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'link',
			[
				'label'   => __('Link', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::URL,
				'placeholder' => __('https://your-link.com', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'methods',
			[
				'label'       => __('Contact Methods', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => [
					[
						'title'       => __('Online Inquiry', 'twostars-elementor-widgets'),
						'description' => __('Fill out our structured form to give us the best starting context.', 'twostars-elementor-widgets'),
						'icon'        => ['value' => 'fas fa-edit', 'library' => 'fa-solid'],
					],
					[
						'title'       => __('Direct WhatsApp', 'twostars-elementor-widgets'),
						'description' => __('Chat directly with a safari specialist for quick guidance.', 'twostars-elementor-widgets'),
						'icon'        => ['value' => 'fab fa-whatsapp', 'library' => 'fa-brands'],
					],
					[
						'title'       => __('Visit Our Office', 'twostars-elementor-widgets'),
						'description' => __('Based in Windhoek, Namibia. Open Mon-Fri, 08:00 - 17:00.', 'twostars-elementor-widgets'),
						'icon'        => ['value' => 'fas fa-map-marker-alt', 'library' => 'fa-solid'],
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
		<section class="twostars-contact-methods">
			<div class="twostars-contact-methods__grid">
				<?php foreach (($settings['methods'] ?? []) as $index => $item) : ?>
					<article 
						class="twostars-contact-methods__card" 
						data-twostars-animate="fade-in-up" 
						style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.12, 2)); ?>s;"
					>
						<div class="twostars-contact-methods__icon" aria-hidden="true">
							<?php Icons_Manager::render_icon((array) $item['icon'], ['aria-hidden' => 'true']); ?>
						</div>
						<h3 class="twostars-contact-methods__title"><?php echo esc_html((string) $item['title']); ?></h3>
						<p class="twostars-contact-methods__description"><?php echo esc_html((string) $item['description']); ?></p>
						<?php if (! empty($item['link']['url'])) : ?>
							<a class="twostars-contact-methods__link" <?php echo $this->link_attributes((array) $item['link']); ?>>
								<?php esc_html_e('Learn More', 'twostars-elementor-widgets'); ?>
								<i class="fas fa-arrow-right" aria-hidden="true"></i>
							</a>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
