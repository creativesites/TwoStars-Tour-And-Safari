<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class HomeLuxuryHeroWidget extends BaseWidget
{
	private const DEFAULT_EYEBROW = 'NTB Registered Day Tours · Walvis Bay & Swakopmund';
	private const DEFAULT_TITLE = 'Where the Ocean Kisses the Dunes';
	private const DEFAULT_HIGHLIGHT = 'Ocean';
	private const DEFAULT_BODY = 'Experience Namibia\'s coast with Two Stars (NTB #TSO01736). We lead professional day tours departing from Walvis Bay and Swakopmund — from the iconic dunes of Sandwich Harbour to marine cruises and private desert explorations.';
	private const DEFAULT_PRIMARY_CTA = 'Explore Tours';
	private const DEFAULT_SECONDARY_CTA = 'Book Your Adventure';
	private const DEFAULT_SLIDER_TITLES = [
		'Where the Ocean Kisses the Dunes',
		'Adventure Beyond the Horizon',
		'Your Gateway to the Namib Coast',
	];
	private const DEFAULT_PLANNING_LABEL = 'Need Help Planning?';
	private const DEFAULT_PLANNING_COPY = 'Unsure which adventure fits your schedule? Ask about tide-dependent Sandwich Harbour times, private departures, or specific pickup points at the Walvis Bay Port and Waterfront.';
	private const DEFAULT_PLANNING_BUTTON = 'Ask the Assistant';
	private const DEFAULT_SERVICE_PILLS = [
		'Sandwich Harbour 4x4',
		'Flamingo Lagoon',
		'Pelican Point Kayaking',
		'Walvis Bay Boat Cruises',
		'Dune 7 & Quad Biking',
		'Spitzkoppe Day Tours',
	];
	private const DEFAULT_FACTS = [
		[
			'label' => 'Operating Base',
			'value' => 'Walvis Bay Waterfront (Boat Hub)',
		],
		[
			'label' => 'Pickups',
			'value' => 'Hotel, Vessel & Waterfront Support',
		],
		[
			'label' => 'Tour Style',
			'value' => 'Registered Half-Day, Full-Day & Private',
		],
	];
	private const DEFAULT_PANEL_FACTS = [
		[
			'label' => 'Compliance',
			'value' => 'Fully Registered (NTB #TSO01736)',
		],
		[
			'label' => 'Pickups',
			'value' => 'Walvis Bay & Swakopmund',
		],
		[
			'label' => 'Flexibility',
			'value' => 'Private Timing & Group Options',
		],
	];

	public function get_name(): string
	{
		return 'twostars-home-luxury-hero';
	}

	public function get_title(): string
	{
		return __('Homepage Adventure Hero', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-banner';
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
			'eyebrow',
			[
				'label'   => __('Eyebrow', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __(self::DEFAULT_EYEBROW, 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => __('Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __(self::DEFAULT_TITLE, 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'highlight',
			[
				'label'   => __('Highlight Word', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __(self::DEFAULT_HIGHLIGHT, 'twostars-elementor-widgets'),
			]
		);

		$slider_repeater = new Repeater();

		$slider_repeater->add_control(
			'title',
			[
				'label'   => __('Slide Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __(self::DEFAULT_TITLE, 'twostars-elementor-widgets'),
			]
		);

		$slider_repeater->add_control(
			'image',
			[
				'label' => __('Slide Background Image', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::MEDIA,
			]
		);

		$this->add_control(
			'slider_titles',
			[
				'label'       => __('Hero Slider Titles', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $slider_repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array_map(
					static fn(string $title): array => ['title' => __($title, 'twostars-elementor-widgets')],
					self::DEFAULT_SLIDER_TITLES
				),
			]
		);

		$this->add_control(
			'body',
			[
				'label'   => __('Body Copy', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __(self::DEFAULT_BODY, 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'primary_cta_text',
			[
				'label'   => __('Primary CTA Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __(self::DEFAULT_PRIMARY_CTA, 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'primary_cta_link',
			[
				'label'   => __('Primary CTA Link', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::URL,
				'default' => [
					'url' => home_url('/tours/'),
				],
			]
		);

		$this->add_control(
			'secondary_cta_text',
			[
				'label'   => __('Secondary CTA Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __(self::DEFAULT_SECONDARY_CTA, 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'secondary_cta_link',
			[
				'label'   => __('Secondary CTA Link', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::URL,
				'default' => [
					'url' => home_url('/booking/'),
				],
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'label',
			[
				'label'   => __('Fact Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Tour Type', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'value',
			[
				'label'   => __('Fact Value', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Half-day, full-day & private', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'facts',
			[
				'label'       => __('Quick Facts', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => array_map(
					static fn(array $fact): array => [
						'label' => __($fact['label'], 'twostars-elementor-widgets'),
						'value' => __($fact['value'], 'twostars-elementor-widgets'),
					],
					self::DEFAULT_FACTS
				),
			]
		);

		$this->add_control(
			'concierge_label',
			[
				'label'   => __('Planning Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __(self::DEFAULT_PLANNING_LABEL, 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'concierge_copy',
			[
				'label'   => __('Planning Copy', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __(self::DEFAULT_PLANNING_COPY, 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'concierge_button_label',
			[
				'label'   => __('Planning Button Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __(self::DEFAULT_PLANNING_BUTTON, 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'concierge_fallback_link',
			[
				'label'   => __('Planning Fallback Link', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::URL,
				'default' => [
					'url' => home_url('/contact/'),
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->normalize_service_copy($this->get_settings_for_display());
		$image_id = absint($settings['background_image']['id'] ?? 0);
		$facts         = array_values(
			array_filter(
				(array) ($settings['facts'] ?? []),
				static fn(array $fact): bool => ! empty($fact['label']) || ! empty($fact['value'])
			)
		);
		$hero_facts    = array_slice($facts, 0, 3);
		$panel_facts   = self::DEFAULT_PANEL_FACTS;
		$slider_slides = $this->resolve_slider_slides($settings, $image_id);
		$service_pills = self::DEFAULT_SERVICE_PILLS;
		?>
		<section class="twostars-home-hero">
			<div class="twostars-home-hero__ambient" aria-hidden="true"></div>

			<div class="twostars-home-hero__stack">
				<?php if ($slider_slides !== []) : ?>
					<div class="twostars-home-hero__slider" data-twostars-home-hero-slider data-autoplay="true" data-duration="5200" data-twostars-animate="fade-in-up">
						<div class="twostars-home-hero__slides">
							<?php foreach ($slider_slides as $index => $slide) : ?>
								<div
									class="twostars-home-hero__slide<?php echo $index === 0 ? ' is-active' : ''; ?>"
									data-home-hero-slide-index="<?php echo esc_attr((string) $index); ?>"
									aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>"
								>
									<div
										class="twostars-home-hero__slide-media<?php echo $slide['image_url'] === '' ? ' twostars-home-hero__slide-media--fallback' : ''; ?>"
										<?php if ($slide['image_url'] !== '') : ?>
											style="background-image: url('<?php echo esc_url($slide['image_url']); ?>');"
										<?php endif; ?>
										aria-hidden="true"
									></div>
									<div class="twostars-home-hero__slide-overlay" aria-hidden="true"></div>
									<div class="twostars-home-hero__slide-content">
										<div class="twostars-home-hero__slide-title" role="heading" aria-level="1"><?php echo esc_html($slide['title']); ?></div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>

						<?php if (count($slider_slides) > 1) : ?>
							<div class="twostars-home-hero__slider-arrows" aria-label="<?php esc_attr_e('Hero slide controls', 'twostars-elementor-widgets'); ?>">
								<button type="button" class="twostars-home-hero__slider-arrow twostars-home-hero__slider-arrow--prev" data-home-hero-prev aria-label="<?php esc_attr_e('Previous slide', 'twostars-elementor-widgets'); ?>">
									<span aria-hidden="true">&larr;</span>
								</button>
								<button type="button" class="twostars-home-hero__slider-arrow twostars-home-hero__slider-arrow--next" data-home-hero-next aria-label="<?php esc_attr_e('Next slide', 'twostars-elementor-widgets'); ?>">
									<span aria-hidden="true">&rarr;</span>
								</button>
							</div>
						<?php endif; ?>

						<?php if (count($slider_slides) > 1) : ?>
							<div class="twostars-home-hero__slider-dots" aria-label="<?php esc_attr_e('Hero titles', 'twostars-elementor-widgets'); ?>">
								<?php foreach ($slider_slides as $index => $slide) : ?>
									<button
										type="button"
										class="twostars-home-hero__slider-dot<?php echo $index === 0 ? ' is-active' : ''; ?>"
										data-home-hero-dot-index="<?php echo esc_attr((string) $index); ?>"
										aria-label="<?php echo esc_attr($slide['title']); ?>"
									></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="twostars-home-hero__frame">
					<div class="twostars-home-hero__content">
						<div class="twostars-home-hero__content-inner">
						<?php if (! empty($settings['body'])) : ?>
							<p class="twostars-home-hero__body" data-twostars-animate="fade-in-up" style="--twostars-delay:0.18s;"><?php echo esc_html((string) $settings['body']); ?></p>
						<?php endif; ?>

						<div class="twostars-home-hero__services" data-twostars-animate="fade-in-up" style="--twostars-delay:0.28s;">
							<p class="twostars-home-hero__services-label"><?php esc_html_e('Popular Experiences', 'twostars-elementor-widgets'); ?></p>
							<div class="twostars-home-hero__service-list">
								<?php foreach ($service_pills as $pill) : ?>
									<span class="twostars-home-hero__service-pill"><?php echo esc_html($pill); ?></span>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="twostars-home-hero__actions" data-twostars-animate="fade-in-up" style="--twostars-delay:0.38s;">
							<?php if (! empty($settings['primary_cta_text']) && ! empty($settings['primary_cta_link']['url'])) : ?>
								<a class="twostars-safari-button twostars-safari-button--primary" <?php echo $this->link_attributes((array) $settings['primary_cta_link']); ?>>
									<?php echo esc_html((string) $settings['primary_cta_text']); ?>
								</a>
							<?php endif; ?>

							<?php if (! empty($settings['secondary_cta_text']) && ! empty($settings['secondary_cta_link']['url'])) : ?>
								<a class="twostars-safari-button twostars-safari-button--ghost" <?php echo $this->link_attributes((array) $settings['secondary_cta_link']); ?>>
									<?php echo esc_html((string) $settings['secondary_cta_text']); ?>
								</a>
							<?php endif; ?>
						</div>

						<?php if ($hero_facts !== []) : ?>
							<div class="twostars-home-hero__summary" data-twostars-animate="fade-in-up" style="--twostars-delay:0.5s;">
								<?php foreach ($hero_facts as $fact) : ?>
									<div class="twostars-home-hero__summary-item">
										<?php if (! empty($fact['label'])) : ?>
											<span><?php echo esc_html((string) $fact['label']); ?></span>
										<?php endif; ?>
										<?php if (! empty($fact['value'])) : ?>
											<strong><?php echo esc_html((string) $fact['value']); ?></strong>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						</div>
					</div>

					<div class="twostars-home-hero__rail">
						<div class="twostars-home-hero__brand-chip" data-twostars-animate="fade-in-right" style="--twostars-delay:0.12s;">
							<span class="twostars-home-hero__brand-stars" aria-hidden="true">★ ★</span>
							<span class="twostars-home-hero__brand-text"><?php esc_html_e('Two Stars Tour & Safari', 'twostars-elementor-widgets'); ?></span>
						</div>

						<aside
							class="twostars-home-hero__panel"
							data-twostars-home-hero-panel
							data-default-copy="<?php echo esc_attr((string) ($settings['concierge_copy'] ?? '')); ?>"
							data-default-label="<?php echo esc_attr((string) ($settings['concierge_button_label'] ?? '')); ?>"
							data-fallback-url="<?php echo esc_url((string) ($settings['concierge_fallback_link']['url'] ?? home_url('/contact/'))); ?>"
							data-twostars-animate="fade-in-right"
							style="--twostars-delay:0.2s;"
						>
							<p class="twostars-home-hero__panel-kicker"><?php esc_html_e('Choose Your Tour', 'twostars-elementor-widgets'); ?></p>
							<?php if (! empty($settings['concierge_label'])) : ?>
								<p class="twostars-home-hero__panel-label"><?php echo esc_html((string) $settings['concierge_label']); ?></p>
							<?php endif; ?>
							<p class="twostars-home-hero__panel-copy" data-twostars-home-hero-copy><?php echo esc_html((string) ($settings['concierge_copy'] ?? '')); ?></p>
							<button type="button" class="twostars-safari-button twostars-safari-button--ghost twostars-home-hero__panel-button" data-twostars-assistant-trigger>
								<?php echo esc_html((string) ($settings['concierge_button_label'] ?? __(self::DEFAULT_PLANNING_BUTTON, 'twostars-elementor-widgets'))); ?>
							</button>

							<?php if ($panel_facts !== []) : ?>
								<div class="twostars-home-hero__panel-stats">
									<?php foreach ($panel_facts as $fact) : ?>
										<div class="twostars-home-hero__panel-stat">
											<?php if (! empty($fact['label'])) : ?>
												<span><?php echo esc_html((string) $fact['label']); ?></span>
											<?php endif; ?>
											<?php if (! empty($fact['value'])) : ?>
												<strong><?php echo esc_html((string) $fact['value']); ?></strong>
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</aside>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Keep legacy saved instances aligned with the new Two Stars service positioning.
	 *
	 * Elementor stores widget settings in the page content, so updating control defaults
	 * alone will not refresh existing homepage instances. This mapper only replaces
	 * clearly legacy luxury/route wording with the new services-led copy.
	 *
	 * @param array<string, mixed> $settings
	 * @return array<string, mixed>
	 */
	private function normalize_service_copy(array $settings): array
	{
		$settings['eyebrow'] = $this->normalize_service_field(
			(string) ($settings['eyebrow'] ?? ''),
			self::DEFAULT_EYEBROW,
			['luxury', 'tailored', 'curated', 'private journey', 'bespoke']
		);
		$settings['title'] = $this->normalize_service_field(
			(string) ($settings['title'] ?? ''),
			self::DEFAULT_TITLE,
			['luxury', 'private journey', 'elevated', 'crafted', 'bespoke', 'itinerary', 'hidden wonders', 'coastal', 'wildlife']
		);
		$settings['highlight'] = $this->normalize_service_field(
			(string) ($settings['highlight'] ?? ''),
			self::DEFAULT_HIGHLIGHT,
			['journey', 'luxury', 'escape']
		);
		$settings['body'] = $this->normalize_service_field(
			(string) ($settings['body'] ?? ''),
			self::DEFAULT_BODY,
			['luxury', 'bespoke', 'tailored', 'private journey', 'curated', 'campfire', 'itinerary']
		);
		$settings['primary_cta_text'] = $this->normalize_service_field(
			(string) ($settings['primary_cta_text'] ?? ''),
			self::DEFAULT_PRIMARY_CTA,
			['journey', 'concierge', 'itinerary']
		);
		$settings['secondary_cta_text'] = $this->normalize_service_field(
			(string) ($settings['secondary_cta_text'] ?? ''),
			self::DEFAULT_SECONDARY_CTA,
			['concierge', 'journey designer', 'tailored route']
		);
		$settings['concierge_label'] = $this->normalize_service_field(
			(string) ($settings['concierge_label'] ?? ''),
			self::DEFAULT_PLANNING_LABEL,
			['concierge', 'private planning', 'itinerary']
		);
		$settings['concierge_copy'] = $this->normalize_service_field(
			(string) ($settings['concierge_copy'] ?? ''),
			self::DEFAULT_PLANNING_COPY,
			['concierge', 'route', 'safari style', 'regions', 'season', 'itinerary', 'bespoke']
		);
		$settings['concierge_button_label'] = $this->normalize_service_field(
			(string) ($settings['concierge_button_label'] ?? ''),
			self::DEFAULT_PLANNING_BUTTON,
			['concierge', 'route advice', 'journey']
		);
		$settings['facts'] = $this->normalize_service_facts((array) ($settings['facts'] ?? []));

		return $settings;
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<int, array{title: string, image_url: string}>
	 */
	private function resolve_slider_slides(array $settings, int $fallback_image_id): array
	{
		$slides = [];
		$fallback_image_url = $fallback_image_id > 0 ? (string) wp_get_attachment_image_url($fallback_image_id, 'full') : '';

		foreach ((array) ($settings['slider_titles'] ?? []) as $slide) {
			$title = trim((string) ($slide['title'] ?? ''));
			$image_id = absint($slide['image']['id'] ?? 0);
			$image_url = $image_id > 0 ? (string) wp_get_attachment_image_url($image_id, 'full') : $fallback_image_url;

			if ($title !== '') {
				$slides[] = [
					'title' => $title,
					'image_url' => $image_url,
				];
			}
		}

		if ($slides !== []) {
			return array_slice($slides, 0, 5);
		}

		$fallback_title = trim((string) ($settings['title'] ?? ''));
		$defaults = self::DEFAULT_SLIDER_TITLES;

		if ($fallback_title !== '') {
			$defaults[0] = $fallback_title;
		}

		return array_map(
			static fn(string $title): array => [
				'title' => $title,
				'image_url' => $fallback_image_url,
			],
			$defaults
		);
	}

	/**
	 * @param string[] $legacy_terms
	 */
	private function normalize_service_field(string $value, string $replacement, array $legacy_terms): string
	{
		$normalized = strtolower(trim($value));

		if ($normalized === '') {
			return $value;
		}

		foreach ($legacy_terms as $term) {
			if (str_contains($normalized, $term)) {
				return $replacement;
			}
		}

		return $value;
	}

	/**
	 * @param array<int, array<string, mixed>> $facts
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_service_facts(array $facts): array
	{
		if ($facts === []) {
			return $facts;
		}

		$legacy_matches = 0;

		foreach ($facts as $fact) {
			$combined = strtolower(trim(((string) ($fact['label'] ?? '')) . ' ' . ((string) ($fact['value'] ?? ''))));

			if ($combined === '') {
				continue;
			}

			if (
				str_contains($combined, 'route') ||
				str_contains($combined, 'journey') ||
				str_contains($combined, 'bespoke') ||
				str_contains($combined, 'itinerary') ||
				str_contains($combined, 'concierge')
			) {
				$legacy_matches++;
			}
		}

		if ($legacy_matches < max(1, count($facts) - 1)) {
			return $facts;
		}

		return array_map(
			static fn(array $fact): array => [
				'label' => __($fact['label'], 'twostars-elementor-widgets'),
				'value' => __($fact['value'], 'twostars-elementor-widgets'),
			],
			self::DEFAULT_FACTS
		);
	}
}
