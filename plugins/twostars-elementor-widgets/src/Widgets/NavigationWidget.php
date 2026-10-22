<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class NavigationWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-navigation';
	}

	public function get_title(): string
	{
		return __('Adventure Navigation', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-nav-menu';
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
			'logo_text',
			[
				'label'   => __('Logo Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('TWO STARS', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'logo_tagline',
			[
				'label'   => __('Logo Tagline', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Adventure Beyond the Horizon', 'twostars-elementor-widgets'),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'label',
			[
				'label'   => __('Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Home', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'link',
			[
				'label'       => __('Link', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::URL,
				'placeholder' => home_url('/'),
				'default'     => [
					'url' => home_url('/'),
				],
			]
		);

		$this->add_control(
			'menu_items',
			[
				'label'       => __('Menu Items', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => [
					[
						'label' => __('Home', 'twostars-elementor-widgets'),
						'link'  => ['url' => home_url('/')],
					],
					[
						'label' => __('Tours', 'twostars-elementor-widgets'),
						'link'  => ['url' => home_url('/tours/')],
					],
					[
						'label' => __('Destinations', 'twostars-elementor-widgets'),
						'link'  => ['url' => home_url('/destinations/')],
					],
					[
						'label' => __('About', 'twostars-elementor-widgets'),
						'link'  => ['url' => home_url('/about/')],
					],
					[
						'label' => __('Contact', 'twostars-elementor-widgets'),
						'link'  => ['url' => home_url('/contact/')],
					],
				],
			]
		);

		$this->add_control(
			'scroll_threshold',
			[
				'label'   => __('Scroll Threshold', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::NUMBER,
				'default' => 100,
				'min'     => 0,
			]
		);

		$this->add_control(
			'cta_text',
			[
				'label'   => __('CTA Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Book Now', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'cta_link',
			[
				'label'       => __('CTA Link', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::URL,
				'placeholder' => home_url('/booking/'),
				'default'     => [
					'url' => home_url('/booking/'),
				],
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'   => __('Text Color', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::COLOR,
				'default' => '#1B3A6B',
			]
		);

		$this->add_control(
			'accent_color',
			[
				'label'   => __('Accent Color', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::COLOR,
				'default' => '#FF6B35',
			]
		);

		$this->add_control(
			'brand_color',
			[
				'label'   => __('Brand Color', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::COLOR,
				'default' => '#D4A373',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings     = $this->get_settings_for_display();
		$menu_id      = $this->widget_dom_id('menu');
		$inline_style = $this->build_inline_vars(
			[
				'--twostars-nav-text'   => $settings['text_color'] ?? '#1B3A6B',
				'--twostars-nav-accent' => $settings['accent_color'] ?? '#FF6B35',
				'--twostars-nav-brand'  => $settings['brand_color'] ?? '#D4A373',
			]
		);
		?>
		<nav
			class="twostars-safari-nav"
			data-twostars-nav
			data-threshold="<?php echo esc_attr((string) absint($settings['scroll_threshold'] ?? 100)); ?>"
			<?php if ($inline_style !== '') : ?>
				style="<?php echo esc_attr($inline_style); ?>"
			<?php endif; ?>
		>
			<div class="twostars-safari-nav__inner">
				<a class="twostars-safari-nav__brand" href="<?php echo esc_url(home_url('/')); ?>">
					<span class="twostars-safari-nav__brand-mark" aria-hidden="true">
						<span class="twostars-safari-nav__brand-star">★</span>
						<span class="twostars-safari-nav__brand-star">★</span>
					</span>
					<span class="twostars-safari-nav__brand-copy">
						<span class="twostars-safari-nav__brand-title"><?php echo esc_html((string) ($settings['logo_text'] ?? '')); ?></span>
						<?php if (! empty($settings['logo_tagline'])) : ?>
							<span class="twostars-safari-nav__brand-tagline"><?php echo esc_html((string) $settings['logo_tagline']); ?></span>
						<?php endif; ?>
					</span>
				</a>

				<button
					class="twostars-safari-nav__toggle"
					type="button"
					aria-expanded="false"
					aria-controls="<?php echo esc_attr($menu_id); ?>"
					aria-label="<?php esc_attr_e('Toggle navigation', 'twostars-elementor-widgets'); ?>"
				>
					<span></span>
					<span></span>
					<span></span>
				</button>

				<div class="twostars-safari-nav__menu" id="<?php echo esc_attr($menu_id); ?>">
					<ul class="twostars-safari-nav__list" aria-label="<?php esc_attr_e('Main navigation', 'twostars-elementor-widgets'); ?>">
						<?php foreach (($settings['menu_items'] ?? []) as $item) : ?>
							<?php if (empty($item['label'])) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<li class="twostars-safari-nav__item">
								<a class="twostars-safari-nav__link" <?php echo $this->link_attributes((array) ($item['link'] ?? [])); ?>>
									<?php echo esc_html((string) $item['label']); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if (! empty($settings['cta_text']) && ! empty($settings['cta_link']['url'])) : ?>
						<a class="twostars-safari-nav__cta twostars-safari-button twostars-safari-button--primary" <?php echo $this->link_attributes((array) $settings['cta_link']); ?>>
							<?php echo esc_html((string) $settings['cta_text']); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</nav>
		<?php
	}
}
