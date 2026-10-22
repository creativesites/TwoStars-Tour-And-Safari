<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Repeater;

final class FooterWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-footer';
	}

	public function get_title(): string
	{
		return __('Adventure Footer', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-footer';
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
			'brand_title',
			[
				'label'   => __('Brand Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Two Stars Tour and Safari', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'tagline',
			[
				'label'   => __('Tagline', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Adventure Beyond the Horizon', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'description',
			[
				'label'   => __('Description', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Experience Namibia’s coast with Two Stars (NTB #TSO01736). We lead professional day tours departing from Walvis Bay and Swakopmund—from the iconic dunes of Sandwich Harbour to marine cruises and private desert explorations.', 'twostars-elementor-widgets'),
			]
		);

		$social_repeater = new Repeater();

		$social_repeater->add_control(
			'platform',
			[
				'label'   => __('Platform Name', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Instagram', 'twostars-elementor-widgets'),
			]
		);

		$social_repeater->add_control(
			'icon',
			[
				'label'   => __('Icon', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fab fa-instagram',
					'library' => 'fa-brands',
				],
			]
		);

		$social_repeater->add_control(
			'url',
			[
				'label' => __('URL', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::URL,
			]
		);

		$this->add_control(
			'social_links',
			[
				'label'       => __('Social Links', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $social_repeater->get_controls(),
				'title_field' => '{{{ platform }}}',
				'default'     => [
					[
						'platform' => __('Instagram', 'twostars-elementor-widgets'),
						'url'      => ['url' => 'https://instagram.com'],
					],
					[
						'platform' => __('Facebook', 'twostars-elementor-widgets'),
						'icon'     => [
							'value'   => 'fab fa-facebook-f',
							'library' => 'fa-brands',
						],
						'url'      => ['url' => 'https://facebook.com'],
					],
					[
						'platform' => __('WhatsApp', 'twostars-elementor-widgets'),
						'icon'     => [
							'value'   => 'fab fa-whatsapp',
							'library' => 'fa-brands',
						],
						'url'      => ['url' => 'https://wa.me/264000000000'],
					],
				],
			]
		);

		$this->add_control(
			'ntb_registration',
			[
				'label'   => __('NTB Registration No.', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('TSO01736', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'ntb_logo',
			[
				'label' => __('NTB Logo', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::MEDIA,
			]
		);

		$link_repeater = new Repeater();

		$link_repeater->add_control(
			'label',
			[
				'label'   => __('Link Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Home', 'twostars-elementor-widgets'),
			]
		);

		$link_repeater->add_control(
			'url',
			[
				'label' => __('Link URL', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::URL,
			]
		);

		$this->add_control(
			'explore_links',
			[
				'label'       => __('Explore Links', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $link_repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => [
					['label' => __('Home', 'twostars-elementor-widgets'), 'url' => ['url' => home_url('/')]],
					['label' => __('Tours', 'twostars-elementor-widgets'), 'url' => ['url' => home_url('/tours/')]],
					['label' => __('Destinations', 'twostars-elementor-widgets'), 'url' => ['url' => home_url('/destinations/')]],
					['label' => __('About', 'twostars-elementor-widgets'), 'url' => ['url' => home_url('/about/')]],
				],
			]
		);

		$this->add_control(
			'plan_links',
			[
				'label'       => __('Plan Links', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $link_repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => [
					['label' => __('Booking', 'twostars-elementor-widgets'), 'url' => ['url' => home_url('/booking/')]],
					['label' => __('Contact', 'twostars-elementor-widgets'), 'url' => ['url' => home_url('/contact/')]],
					['label' => __('FAQ', 'twostars-elementor-widgets'), 'url' => ['url' => home_url('/faq/')]],
					['label' => __('Stories', 'twostars-elementor-widgets'), 'url' => ['url' => home_url('/testimonials/')]],
				],
			]
		);

		$this->add_control(
			'cta_text',
			[
				'label'   => __('CTA Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Start Your Adventure', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'cta_link',
			[
				'label'   => __('CTA Link', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::URL,
				'default' => [
					'url' => home_url('/booking/'),
				],
			]
		);

		$this->add_control(
			'copyright_text',
			[
				'label'   => __('Copyright Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('© 2026 Two Stars Tour and Safari. Adventure tours and safari planning across Namibia.', 'twostars-elementor-widgets'),
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<footer class="twostars-footer">
			<div class="twostars-footer__grid">
				<div class="twostars-footer__brand">
					<div class="twostars-footer__brand-lockup">
						<span class="twostars-footer__brand-stars" aria-hidden="true">
							<span class="twostars-footer__brand-star">★</span>
							<span class="twostars-footer__brand-star">★</span>
						</span>
						<div>
							<p class="twostars-footer__brand-title"><?php echo esc_html((string) ($settings['brand_title'] ?? '')); ?></p>
							<p class="twostars-footer__brand-tagline"><?php echo esc_html((string) ($settings['tagline'] ?? '')); ?></p>
						</div>
					</div>
					<?php if (! empty($settings['description'])) : ?>
						<p class="twostars-footer__brand-description"><?php echo esc_html((string) $settings['description']); ?></p>
					<?php endif; ?>
					<div class="twostars-footer__social">
						<?php foreach (($settings['social_links'] ?? []) as $item) : ?>
							<?php if (empty($item['url']['url'])) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<a
								class="twostars-footer__social-link"
								<?php echo $this->link_attributes((array) $item['url']); ?>
								aria-label="<?php echo esc_attr((string) ($item['platform'] ?? __('Social link', 'twostars-elementor-widgets'))); ?>"
							>
								<?php Icons_Manager::render_icon((array) ($item['icon'] ?? []), ['aria-hidden' => 'true']); ?>
							</a>
						<?php endforeach; ?>
					</div>

					<?php if (! empty($settings['ntb_registration']) || ! empty($settings['ntb_logo']['url'])) : ?>
						<div class="twostars-footer__compliance">
							<?php if (! empty($settings['ntb_logo']['url'])) : ?>
								<img src="<?php echo esc_url($settings['ntb_logo']['url']); ?>" alt="Namibia Tourism Board" class="twostars-footer__ntb-logo">
							<?php else : ?>
								<div class="twostars-footer__compliance-badge"><?php esc_html_e('NTB REGISTERED', 'twostars-elementor-widgets'); ?></div>
							<?php endif; ?>
							<?php if (! empty($settings['ntb_registration'])) : ?>
								<span class="twostars-footer__ntb-reg"><?php echo esc_html((string) $settings['ntb_registration']); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="twostars-footer__hub">
						<span class="twostars-footer__hub-label"><?php esc_html_e('Marine Cruise Hub:', 'twostars-elementor-widgets'); ?></span>
						<span class="twostars-footer__hub-value"><?php esc_html_e('Walvis Bay Waterfront', 'twostars-elementor-widgets'); ?></span>
					</div>
				</div>

				<div class="twostars-footer__column">
					<p class="twostars-footer__column-title"><?php esc_html_e('Explore', 'twostars-elementor-widgets'); ?></p>
					<div class="twostars-footer__links">
						<?php foreach (($settings['explore_links'] ?? []) as $item) : ?>
							<?php if (empty($item['label']) || empty($item['url']['url'])) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<a class="twostars-footer__link" <?php echo $this->link_attributes((array) $item['url']); ?>><?php echo esc_html((string) $item['label']); ?></a>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="twostars-footer__column">
					<p class="twostars-footer__column-title"><?php esc_html_e('Plan', 'twostars-elementor-widgets'); ?></p>
					<div class="twostars-footer__links">
						<?php foreach (($settings['plan_links'] ?? []) as $item) : ?>
							<?php if (empty($item['label']) || empty($item['url']['url'])) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<a class="twostars-footer__link" <?php echo $this->link_attributes((array) $item['url']); ?>><?php echo esc_html((string) $item['label']); ?></a>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="twostars-footer__column twostars-footer__column--cta">
					<p class="twostars-footer__column-title"><?php esc_html_e('Ready to Go?', 'twostars-elementor-widgets'); ?></p>
					<a class="twostars-safari-button twostars-safari-button--primary twostars-footer__cta" <?php echo $this->link_attributes((array) ($settings['cta_link'] ?? [])); ?>>
						<?php echo esc_html((string) ($settings['cta_text'] ?? '')); ?>
					</a>
				</div>
			</div>

			<?php if (! empty($settings['copyright_text'])) : ?>
				<p class="twostars-footer__copyright"><?php echo esc_html((string) $settings['copyright_text']); ?></p>
			<?php endif; ?>
		</footer>
		<?php
	}
}
