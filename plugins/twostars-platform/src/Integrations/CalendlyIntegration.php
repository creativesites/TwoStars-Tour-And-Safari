<?php
declare(strict_types=1);

namespace TwoStars\Platform\Integrations;

use TwoStars\Platform\Support\Service;

final class CalendlyIntegration implements Service
{
	private const OPTION_NAME = 'twostars_platform_settings';

	public function register(): void
	{
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
	}

	public function enqueue_assets(): void
	{
		if (self::is_local_booking_enabled()) {
			return;
		}

		if (! self::has_event_url()) {
			return;
		}

		wp_enqueue_style(
			'twostars-calendly-widget',
			'https://assets.calendly.com/assets/external/widget.css',
			[],
			null
		);

		wp_enqueue_script(
			'twostars-calendly-widget',
			'https://assets.calendly.com/assets/external/widget.js',
			[],
			null,
			true
		);

		wp_enqueue_script(
			'twostars-calendly-integration',
			TWOSTARS_PLATFORM_URL . 'assets/js/calendly.js',
			['twostars-calendly-widget'],
			TWOSTARS_PLATFORM_VERSION,
			true
		);

		wp_localize_script(
			'twostars-calendly-integration',
			'twoStarsCalendly',
			[
				'badge' => self::get_badge_config(),
			]
		);
	}

	public static function has_event_url(): bool
	{
		return self::get_default_event_url() !== '';
	}

	public static function get_default_event_url(): string
	{
		$settings = get_option(self::OPTION_NAME, []);

		return is_array($settings)
			? esc_url_raw((string) ($settings['calendly_event_url'] ?? ''))
			: '';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_badge_config(): array
	{
		if (self::is_local_booking_enabled()) {
			return [
				'enabled'   => false,
				'url'       => '',
				'text'      => '',
				'color'     => '',
				'textColor' => '',
				'branding'  => false,
			];
		}

		$settings = get_option(self::OPTION_NAME, []);
		$settings = is_array($settings) ? $settings : [];

		return [
			'enabled'   => ! empty($settings['calendly_badge_enabled']) && self::get_default_event_url() !== '',
			'url'       => self::get_default_event_url(),
			'text'      => (string) ($settings['calendly_badge_text'] ?? __('Schedule time with us', 'twostars-platform')),
			'color'     => (string) ($settings['calendly_badge_color'] ?? '#0069ff'),
			'textColor' => (string) ($settings['calendly_badge_text_color'] ?? '#ffffff'),
			'branding'  => ! empty($settings['calendly_badge_branding']),
		];
	}

	private static function is_local_booking_enabled(): bool
	{
		return true;
	}
}
