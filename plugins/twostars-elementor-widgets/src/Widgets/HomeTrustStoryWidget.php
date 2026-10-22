<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class HomeTrustStoryWidget extends BaseWidget
{
	private const DEFAULT_EYEBROW = 'Why Travelers Book With Two Stars';
	private const DEFAULT_TITLE = 'Local Coastal Touring with a Polished, Personal Touch.';
	private const DEFAULT_LEAD = 'We specialize in authentic coastal experiences departing daily from Walvis Bay and Swakopmund. As a fully registered operator (NTB #TSO01736), Two Stars Tour & Safari balances rugged Namibian adventure with the comfort, timing, and professional guidance you expect.';
	private const DEFAULT_BODY = 'From our flagship Sandwich Harbour 4x4 excursions to relaxed marine cruises and inland desert trips, every itinerary is designed with scenic photography and effortless logistics in mind.';
	private const DEFAULT_NOTE_LABEL = 'Service Standard';
	private const DEFAULT_NOTE_TITLE = 'Professionally Registered. Expertly Guided.';
	private const DEFAULT_NOTE_BODY = 'Your safety is our priority. As a registered NTB member (TSO01736), we maintain high standards for vehicle safety, insurance, and guide certification. All boat and kayaking excursions depart from our meeting point at the Walvis Bay Waterfront, with seamless transfers available from the port or your hotel.';
	private const DEFAULT_PILLARS = [
		[
			'title' => 'Vessel-Ready Pickups',
			'copy'  => 'We provide on-time pickup and drop-off support for Walvis Bay (including the Port for cruise guests) and Swakopmund accommodations.',
		],
		[
			'title' => 'Photographer\'s Pace',
			'copy'  => 'We don\'t rush the view. Our routes are shaped around memorable stops, allowing time to capture the perfect shot of the dunes and wildlife.',
		],
		[
			'title' => 'Dune-Ready 4x4 Travel',
			'copy'  => 'Experience the coast in comfort. Our fleet is meticulously maintained and specifically equipped for the unique terrain of the Namib Desert.',
		],
		[
			'title' => 'Private Group Flexibility',
			'copy'  => 'Tailor the day to your needs. We offer private departures that allow more control over the timing, flow, and specific interests of your group.',
		],
	];

	public function get_name(): string
	{
		return 'twostars-home-trust-story';
	}

	public function get_title(): string
	{
		return __('Homepage Trust Story', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-person';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control('eyebrow', ['label' => __('Eyebrow', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __(self::DEFAULT_EYEBROW, 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __(self::DEFAULT_TITLE, 'twostars-elementor-widgets')]);
		$this->add_control('lead', ['label' => __('Lead', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __(self::DEFAULT_LEAD, 'twostars-elementor-widgets')]);
		$this->add_control('body', ['label' => __('Body', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __(self::DEFAULT_BODY, 'twostars-elementor-widgets')]);
		$this->add_control('primary_cta_text', ['label' => __('Primary CTA Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Browse Tours', 'twostars-elementor-widgets')]);
		$this->add_control('primary_cta_link', ['label' => __('Primary CTA Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/tours/')]]);
		$this->add_control('secondary_cta_text', ['label' => __('Secondary CTA Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Contact on WhatsApp', 'twostars-elementor-widgets')]);
		$this->add_control('secondary_cta_link', ['label' => __('Secondary CTA Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => 'https://wa.me/264812060111', 'is_external' => 'true']]);
		$this->add_control('support_image', ['label' => __('Support Image', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA]);
		$this->add_control('note_label', ['label' => __('Note Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __(self::DEFAULT_NOTE_LABEL, 'twostars-elementor-widgets')]);
		$this->add_control('note_title', ['label' => __('Note Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __(self::DEFAULT_NOTE_TITLE, 'twostars-elementor-widgets')]);
		$this->add_control('note_body', ['label' => __('Note Body', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __(self::DEFAULT_NOTE_BODY, 'twostars-elementor-widgets')]);
		$this->add_control('ntb_logo', ['label' => __('NTB Logo', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA]);

		$repeater = new Repeater();
		$repeater->add_control('title', ['label' => __('Pillar Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __(self::DEFAULT_PILLARS[0]['title'], 'twostars-elementor-widgets')]);
		$repeater->add_control('copy', ['label' => __('Pillar Copy', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __(self::DEFAULT_PILLARS[0]['copy'], 'twostars-elementor-widgets')]);

		$this->add_control(
			'pillars',
			[
				'label'       => __('Pillars', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array_map(
					static fn(array $pillar): array => [
						'title' => __($pillar['title'], 'twostars-elementor-widgets'),
						'copy' => __($pillar['copy'], 'twostars-elementor-widgets'),
					],
					self::DEFAULT_PILLARS
				),
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->normalize_conversion_copy($this->get_settings_for_display());
		$image_id = absint($settings['support_image']['id'] ?? 0);
		?>
		<section class="twostars-home-story">
			<div class="twostars-home-story__shell">
				<header class="twostars-home-story__header" data-twostars-animate="fade-in-up">
					<div class="twostars-home-story__header-copy">
						<?php if (! empty($settings['eyebrow'])) : ?><p class="twostars-home-story__eyebrow"><?php echo esc_html((string) $settings['eyebrow']); ?></p><?php endif; ?>
						<?php if (! empty($settings['title'])) : ?><h2 class="twostars-home-story__title"><?php echo nl2br(esc_html((string) $settings['title'])); ?></h2><?php endif; ?>
					</div>

					<div class="twostars-home-story__header-text">
						<?php if (! empty($settings['lead'])) : ?><p class="twostars-home-story__lead"><?php echo esc_html((string) $settings['lead']); ?></p><?php endif; ?>
						<?php if (! empty($settings['body'])) : ?><p class="twostars-home-story__body"><?php echo esc_html((string) $settings['body']); ?></p><?php endif; ?>

						<div class="twostars-home-story__actions">
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
					</div>
				</header>

				<div class="twostars-home-story__layout">
					<div class="twostars-home-story__visual" data-twostars-animate="fade-in-left" style="--twostars-delay:0.1s;">
						<div class="twostars-home-story__image-wrap">
							<?php if ($image_id > 0) : ?>
								<?php echo wp_get_attachment_image($image_id, 'large', false, ['class' => 'twostars-home-story__image', 'loading' => 'lazy']); ?>
							<?php else : ?>
								<div class="twostars-home-story__image twostars-home-story__image--placeholder"></div>
							<?php endif; ?>
						</div>

						<div class="twostars-home-story__note">
							<?php if (! empty($settings['ntb_logo']['url'])) : ?>
								<div class="twostars-home-story__note-compliance">
									<img src="<?php echo esc_url($settings['ntb_logo']['url']); ?>" alt="Namibia Tourism Board" class="twostars-home-story__ntb-logo">
									<span class="twostars-home-story__note-badge"><?php esc_html_e('NTB #TSO01736', 'twostars-elementor-widgets'); ?></span>
								</div>
							<?php endif; ?>
							<?php if (! empty($settings['note_label'])) : ?><p class="twostars-home-story__note-label"><?php echo esc_html((string) $settings['note_label']); ?></p><?php endif; ?>
							<?php if (! empty($settings['note_title'])) : ?><h3 class="twostars-home-story__note-title"><?php echo esc_html((string) $settings['note_title']); ?></h3><?php endif; ?>
							<?php if (! empty($settings['note_body'])) : ?><p class="twostars-home-story__note-body"><?php echo esc_html((string) $settings['note_body']); ?></p><?php endif; ?>
							<div class="twostars-home-story__note-contact">
								<span class="twostars-home-story__note-contact-label"><?php esc_html_e('Marine Cruise Hub', 'twostars-elementor-widgets'); ?></span>
								<strong class="twostars-home-story__note-contact-value"><?php esc_html_e('Walvis Bay Waterfront', 'twostars-elementor-widgets'); ?></strong>
							</div>
						</div>
					</div>

					<div class="twostars-home-story__pillars" data-twostars-animate="fade-in-right" style="--twostars-delay:0.18s;">
						<?php foreach ((array) ($settings['pillars'] ?? []) as $index => $pillar) : ?>
							<?php if (empty($pillar['title']) && empty($pillar['copy'])) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<article class="twostars-home-story__pillar">
								<div class="twostars-home-story__pillar-inner">
									<span class="twostars-home-story__pillar-index" aria-hidden="true"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
									<div class="twostars-home-story__pillar-content">
										<?php if (! empty($pillar['title'])) : ?><h3><?php echo esc_html((string) $pillar['title']); ?></h3><?php endif; ?>
										<?php if (! empty($pillar['copy'])) : ?><p><?php echo esc_html((string) $pillar['copy']); ?></p><?php endif; ?>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<string, mixed>
	 */
	private function normalize_conversion_copy(array $settings): array
	{
		$settings['eyebrow'] = $this->normalize_field(
			(string) ($settings['eyebrow'] ?? ''),
			self::DEFAULT_EYEBROW,
			['why two stars', 'trust story']
		);
		$settings['title'] = $this->normalize_field(
			(string) ($settings['title'] ?? ''),
			self::DEFAULT_TITLE,
			['brochure noise', 'confidence', 'homepage story']
		);
		$settings['lead'] = $this->normalize_field(
			(string) ($settings['lead'] ?? ''),
			self::DEFAULT_LEAD,
			['first planning conversation', 'unmistakably premium', 'warm, adventurous']
		);
		$settings['body'] = $this->normalize_field(
			(string) ($settings['body'] ?? ''),
			self::DEFAULT_BODY,
			['high-value trip', 'route design', 'local timing', 'human support before any commitment']
		);
		$settings['note_label'] = $this->normalize_field(
			(string) ($settings['note_label'] ?? ''),
			self::DEFAULT_NOTE_LABEL,
			['planning standard']
		);
		$settings['note_title'] = $this->normalize_field(
			(string) ($settings['note_title'] ?? ''),
			self::DEFAULT_NOTE_TITLE,
			['unhurried and exact']
		);
		$settings['note_body'] = $this->normalize_field(
			(string) ($settings['note_body'] ?? ''),
			self::DEFAULT_NOTE_BODY,
			['fewer generic claims', 'lodge rhythm', 'what actually suits the guest']
		);
		$settings['pillars'] = $this->normalize_pillars((array) ($settings['pillars'] ?? []));

		return $settings;
	}

	/**
	 * @param string[] $legacy_terms
	 */
	private function normalize_field(string $value, string $replacement, array $legacy_terms): string
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
	 * @param array<int, array<string, mixed>> $pillars
	 * @return array<int, array<string, string>>
	 */
	private function normalize_pillars(array $pillars): array
	{
		if ($pillars === []) {
			return array_map(
				static fn(array $pillar): array => [
					'title' => $pillar['title'],
					'copy' => $pillar['copy'],
				],
				self::DEFAULT_PILLARS
			);
		}

		$legacy_titles = [
			'route intelligence',
			'private pacing',
			'human guidance',
			'local practicality',
		];

		$legacy_matches = 0;

		foreach ($pillars as $pillar) {
			$title = strtolower(trim((string) ($pillar['title'] ?? '')));

			if (in_array($title, $legacy_titles, true)) {
				$legacy_matches++;
			}
		}

		if ($legacy_matches < max(1, count($pillars) - 1)) {
			return array_map(
				static fn(array $pillar): array => [
					'title' => (string) ($pillar['title'] ?? ''),
					'copy' => (string) ($pillar['copy'] ?? ''),
				],
				$pillars
			);
		}

		return array_map(
			static fn(array $pillar): array => [
				'title' => $pillar['title'],
				'copy' => $pillar['copy'],
			],
			self::DEFAULT_PILLARS
		);
	}
}
