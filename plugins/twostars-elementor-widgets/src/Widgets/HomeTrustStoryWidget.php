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
	private const DEFAULT_BODY = 'From Sandwich Harbour 4x4 tours to flamingo mornings and inland desert days, every trip is paced for photography.';
	private const DEFAULT_NOTE_LABEL = 'Service Standard';
	private const DEFAULT_NOTE_TITLE = 'Professionally Registered. Expertly Guided.';
	private const DEFAULT_NOTE_BODY = 'Your safety is our priority. As a registered NTB member (TSO01736), we maintain high standards for vehicle safety, insurance, and guide certification. We collect guests from Walvis Bay hotels, Swakopmund hotels and the Walvis Bay cruise port.';
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
		$this->add_control('main_image', ['label' => __('Main Photo', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA]);
		$this->add_control('inset_image', ['label' => __('Inset Photo', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA]);
		$this->add_control('chip_number', ['label' => __('Chip Number', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => '4']);
		$this->add_control('chip_text', ['label' => __('Chip Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('guests per vehicle, plus your guide. Always.', 'twostars-elementor-widgets')]);
		$this->add_control('pill_text', ['label' => __('Price Pill', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('N$2,600 per adult · Free cancellation', 'twostars-elementor-widgets')]);
		$this->add_control('footnote', ['label' => __('Footnote', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Registered with the Namibia Tourism Board · #TSO01736', 'twostars-elementor-widgets')]);
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
		$settings   = $this->get_settings_for_display();
		$main_id    = absint($settings['main_image']['id'] ?? ($settings['support_image']['id'] ?? 0));
		$inset_id   = absint($settings['inset_image']['id'] ?? 0);
		$pillars    = array_slice(
			array_values(
				array_filter(
					(array) ($settings['pillars'] ?? []),
					static fn($pillar): bool => is_array($pillar) && (! empty($pillar['title']) || ! empty($pillar['copy']))
				)
			),
			0,
			4
		);
		$icons      = self::pillar_icons();
		$title_html = nl2br(esc_html((string) ($settings['title'] ?? '')));
		?>
		<section class="ts-story">
			<span class="ts-story__glow" aria-hidden="true"></span>
			<div class="ts-story__inner">
				<div class="ts-story__copy" data-twostars-animate="fade-in-left">
					<?php if (! empty($settings['eyebrow'])) : ?>
						<p class="ts-story__eyebrow"><span aria-hidden="true"></span><?php echo esc_html((string) $settings['eyebrow']); ?></p>
					<?php endif; ?>
					<?php if (! empty($settings['title'])) : ?>
						<h2 class="ts-story__title"><?php echo wp_kses($title_html, ['br' => []]); ?></h2>
					<?php endif; ?>
					<?php if (! empty($settings['lead'])) : ?>
						<p class="ts-story__lead"><?php echo esc_html((string) $settings['lead']); ?></p>
					<?php endif; ?>

					<?php if ($pillars !== []) : ?>
						<ul class="ts-story__pillars">
							<?php foreach ($pillars as $index => $pillar) : ?>
								<li class="ts-story__pillar">
									<span class="ts-story__icon" aria-hidden="true"><?php echo $icons[$index % count($icons)]; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<span class="ts-story__pillar-text">
										<?php if (! empty($pillar['title'])) : ?><strong><?php echo esc_html((string) $pillar['title']); ?></strong><?php endif; ?>
										<?php if (! empty($pillar['copy'])) : ?><span><?php echo esc_html((string) $pillar['copy']); ?></span><?php endif; ?>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<div class="ts-story__actions">
						<?php if (! empty($settings['primary_cta_text']) && ! empty($settings['primary_cta_link']['url'])) : ?>
							<a class="ts-story__button ts-story__button--primary" <?php echo $this->link_attributes((array) $settings['primary_cta_link']); ?>><?php echo esc_html((string) $settings['primary_cta_text']); ?></a>
						<?php endif; ?>
						<?php if (! empty($settings['secondary_cta_text']) && ! empty($settings['secondary_cta_link']['url'])) : ?>
							<a class="ts-story__button ts-story__button--ghost" <?php echo $this->link_attributes((array) $settings['secondary_cta_link']); ?> data-twostars-event="whatsapp_click"><?php echo esc_html((string) $settings['secondary_cta_text']); ?></a>
						<?php endif; ?>
					</div>
					<?php $ntb_url = (string) ($settings['ntb_logo']['url'] ?? ''); ?>
					<?php if ($ntb_url !== '' || ! empty($settings['footnote'])) : ?>
						<div class="ts-story__ntb">
							<?php if ($ntb_url !== '') : ?>
								<img class="ts-story__ntb-logo" src="<?php echo esc_url($ntb_url); ?>" alt="<?php esc_attr_e('Namibia Tourism Board', 'twostars-elementor-widgets'); ?>" width="72" height="44" loading="lazy" decoding="async">
							<?php endif; ?>
							<p class="ts-story__footnote"><?php echo esc_html((string) ($settings['footnote'] ?? '')); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<div class="ts-story__visual" data-twostars-animate="fade-in-right" style="--twostars-delay:0.12s;">
					<div class="ts-story__photo">
						<?php if ($main_id > 0) : ?>
							<?php echo wp_get_attachment_image($main_id, 'large', false, ['loading' => 'lazy', 'decoding' => 'async']); ?>
						<?php endif; ?>
					</div>
					<?php if (! empty($settings['pill_text'])) : ?>
						<p class="ts-story__pill"><?php echo esc_html((string) $settings['pill_text']); ?></p>
					<?php endif; ?>
					<?php if (! empty($settings['chip_number'])) : ?>
						<div class="ts-story__chip">
							<span class="ts-story__chip-number"><?php echo esc_html((string) $settings['chip_number']); ?></span>
							<span class="ts-story__chip-text"><?php echo esc_html((string) ($settings['chip_text'] ?? '')); ?></span>
						</div>
					<?php endif; ?>
					<?php if ($inset_id > 0) : ?>
						<div class="ts-story__inset">
							<?php echo wp_get_attachment_image($inset_id, 'medium_large', false, ['loading' => 'lazy', 'decoding' => 'async']); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * @return string[]
	 */
	private static function pillar_icons(): array
	{
		$open = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">';

		return [
			$open . '<path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/></svg>',
			$open . '<circle cx="9" cy="8" r="3"/><path d="M3 20v-1a5 5 0 0 1 5-5h2a5 5 0 0 1 5 5v1"/><circle cx="17" cy="9" r="2.4"/><path d="M17.5 14.2A4 4 0 0 1 21 18v2"/></svg>',
			$open . '<path d="M4 8h3l1.6-2.4h6.8L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3.4"/></svg>',
			$open . '<circle cx="12" cy="5" r="2"/><path d="M12 7v13M6 12H4a8 8 0 0 0 16 0h-2M8.5 11h7"/></svg>',
		];
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
