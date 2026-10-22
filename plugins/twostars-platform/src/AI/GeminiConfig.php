<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

final class GeminiConfig
{
	private const OPTION_NAME = 'twostars_platform_settings';
	private const DEFAULT_FLASH_MODEL = 'gemini-3-flash-preview';
	private const DEFAULT_REASONING_MODEL = 'gemini-3-pro-preview';
	private const DEFAULT_EMBEDDING_MODEL = 'text-embedding-004';

	/**
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array
	{
		$settings = get_option(self::OPTION_NAME, []);

		return is_array($settings) ? $settings : [];
	}

	public static function get_api_key(): string
	{
		$settings = self::get_settings();

		return sanitize_text_field((string) ($settings['gemini_api_key'] ?? ''));
	}

	public static function get_flash_model(): string
	{
		$settings = self::get_settings();
		$model    = sanitize_text_field((string) ($settings['gemini_flash_model'] ?? ''));

		return $model !== '' ? $model : self::DEFAULT_FLASH_MODEL;
	}

	public static function get_reasoning_model(): string
	{
		$settings = self::get_settings();
		$model    = sanitize_text_field((string) ($settings['gemini_reasoning_model'] ?? ''));

		return $model !== '' ? $model : self::DEFAULT_REASONING_MODEL;
	}

	public static function is_enabled(): bool
	{
		$settings = self::get_settings();

		return ! empty($settings['ai_assistant_enabled']) && self::get_api_key() !== '';
	}

	public static function get_embedding_model(): string
	{
		$settings = self::get_settings();
		$model = sanitize_text_field((string) ($settings['gemini_embedding_model'] ?? ''));

		return $model !== '' ? $model : self::DEFAULT_EMBEDDING_MODEL;
	}
}
