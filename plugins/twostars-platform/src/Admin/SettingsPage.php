<?php
declare(strict_types=1);

namespace TwoStars\Platform\Admin;

use TwoStars\Platform\AI\GeminiConfig;
use TwoStars\Platform\Support\Assets;
use TwoStars\Platform\Support\Service;

final class SettingsPage implements Service
{
	private const OPTION_GROUP = 'twostars_platform_settings_group';
	private const OPTION_NAME = 'twostars_platform_settings';
	private const PAGE_SLUG = 'twostars-platform';
	private const DEFAULT_VISITORAPI_PROJECT_ID = 'bkqnE1FI519hOxf2VIa0';
	private const DEFAULT_FINGERPRINT_PUBLIC_KEY = '16ovVe3T8gJzgsEJaXaS';
	private const DEFAULT_FINGERPRINT_REGION = 'ap';

	public function register(): void
	{
		add_action('admin_menu', [$this, 'register_menu']);
		add_action('admin_init', [$this, 'register_settings']);
		add_action('admin_init', [$this, 'redirect_legacy_page']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
	}

	public function register_menu(): void
	{
		add_submenu_page(
			'twostars-manager',
			__('Two Stars Platform', 'twostars-platform'),
			__('Two Stars Platform', 'twostars-platform'),
			'manage_options',
			self::PAGE_SLUG,
			[$this, 'render_page']
		);
	}

	public function redirect_legacy_page(): void
	{
		if (! is_admin() || wp_doing_ajax()) {
			return;
		}

		if (! current_user_can('manage_options')) {
			return;
		}

		$script = isset($_SERVER['SCRIPT_NAME']) ? wp_unslash($_SERVER['SCRIPT_NAME']) : '';
		$script = is_string($script) ? basename($script) : '';
		$page   = sanitize_key((string) ($_GET['page'] ?? ''));

		if ($script !== 'options-general.php' || $page !== self::PAGE_SLUG) {
			return;
		}

		wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE_SLUG));
		exit;
	}

	public function enqueue_assets(string $hook_suffix): void
	{
		if (! Assets::matches_admin_page($hook_suffix, [self::PAGE_SLUG])) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'twostars-platform-content-manager',
			Assets::plugin_url('assets/css/content-manager.css'),
			[],
			Assets::plugin_version('assets/css/content-manager.css')
		);

		wp_enqueue_script(
			'twostars-platform-content-manager',
			Assets::plugin_url('assets/js/content-manager.js'),
			['jquery', 'jquery-ui-sortable'],
			Assets::plugin_version('assets/js/content-manager.js'),
			true
		);
	}

	public function register_settings(): void
	{
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			[$this, 'sanitize_settings']
		);

		add_settings_section(
			'twostars_platform_general',
			__('General Configuration', 'twostars-platform'),
			'__return_false',
			'twostars-platform'
		);

		add_settings_section(
			'twostars_platform_ai',
			__('AI Configuration', 'twostars-platform'),
			'__return_false',
			'twostars-platform'
		);

		add_settings_section(
			'twostars_platform_visitors',
			__('Visitor Intelligence', 'twostars-platform'),
			'__return_false',
			'twostars-platform'
		);

		add_settings_field(
			'whatsapp_number',
			__('WhatsApp Number', 'twostars-platform'),
			[$this, 'render_whatsapp_field'],
			'twostars-platform',
			'twostars_platform_general'
		);

		add_settings_field(
			'calendly_event_url',
			__('Calendly Event URL', 'twostars-platform'),
			[$this, 'render_calendly_event_url_field'],
			'twostars-platform',
			'twostars_platform_general'
		);

		add_settings_field(
			'calendly_badge_enabled',
			__('Enable Calendly Badge', 'twostars-platform'),
			[$this, 'render_calendly_badge_enabled_field'],
			'twostars-platform',
			'twostars_platform_general'
		);

		add_settings_field(
			'calendly_badge_text',
			__('Calendly Badge Text', 'twostars-platform'),
			[$this, 'render_calendly_badge_text_field'],
			'twostars-platform',
			'twostars_platform_general'
		);

		add_settings_field(
			'calendly_badge_color',
			__('Calendly Badge Color', 'twostars-platform'),
			[$this, 'render_calendly_badge_color_field'],
			'twostars-platform',
			'twostars_platform_general'
		);

		add_settings_field(
			'calendly_badge_text_color',
			__('Calendly Badge Text Color', 'twostars-platform'),
			[$this, 'render_calendly_badge_text_color_field'],
			'twostars-platform',
			'twostars_platform_general'
		);

		add_settings_field(
			'calendly_badge_branding',
			__('Calendly Badge Branding', 'twostars-platform'),
			[$this, 'render_calendly_badge_branding_field'],
			'twostars-platform',
			'twostars_platform_general'
		);

		add_settings_field(
			'ai_assistant_enabled',
			__('Enable AI Assistant', 'twostars-platform'),
			[$this, 'render_ai_field'],
			'twostars-platform',
			'twostars_platform_ai'
		);

		add_settings_field(
			'chatbot_icon_id',
			__('Chatbot Icon', 'twostars-platform'),
			[$this, 'render_chatbot_icon_field'],
			'twostars-platform',
			'twostars_platform_ai'
		);

		add_settings_field(
			'gemini_api_key',
			__('Gemini API Key', 'twostars-platform'),
			[$this, 'render_gemini_api_key_field'],
			'twostars-platform',
			'twostars_platform_ai'
		);

		add_settings_field(
			'gemini_flash_model',
			__('Gemini Flash Model', 'twostars-platform'),
			[$this, 'render_gemini_flash_model_field'],
			'twostars-platform',
			'twostars_platform_ai'
		);

		add_settings_field(
			'gemini_reasoning_model',
			__('Gemini Reasoning Model', 'twostars-platform'),
			[$this, 'render_gemini_reasoning_model_field'],
			'twostars-platform',
			'twostars_platform_ai'
		);

		add_settings_field(
			'intelligence_prompt_min_score',
			__('Prompt Minimum Score', 'twostars-platform'),
			[$this, 'render_intelligence_prompt_min_score_field'],
			'twostars-platform',
			'twostars_platform_ai'
		);

		add_settings_field(
			'intelligence_max_prompts_per_session',
			__('Max Prompts Per Session', 'twostars-platform'),
			[$this, 'render_intelligence_max_prompts_field'],
			'twostars-platform',
			'twostars_platform_ai'
		);

		add_settings_field(
			'intelligence_high_intent_score',
			__('High Intent Score', 'twostars-platform'),
			[$this, 'render_intelligence_high_intent_score_field'],
			'twostars-platform',
			'twostars_platform_ai'
		);

		add_settings_field(
			'social_feed_enabled',
			__('Enable Social Feed', 'twostars-platform'),
			[$this, 'render_social_feed_field'],
			'twostars-platform',
			'twostars_platform_general'
		);

		add_settings_field(
			'visitor_tracking_enabled',
			__('Enable Visitor Tracking', 'twostars-platform'),
			[$this, 'render_visitor_tracking_enabled_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'visitor_personalization_enabled',
			__('Enable Personalization', 'twostars-platform'),
			[$this, 'render_visitor_personalization_enabled_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'visitorapi_project_id',
			__('VisitorAPI Project ID', 'twostars-platform'),
			[$this, 'render_visitorapi_project_id_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'fingerprint_public_key',
			__('Fingerprint Public Key', 'twostars-platform'),
			[$this, 'render_fingerprint_public_key_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'fingerprint_region',
			__('Fingerprint Region', 'twostars-platform'),
			[$this, 'render_fingerprint_region_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'fingerprint_agent_url',
			__('Fingerprint Agent URL', 'twostars-platform'),
			[$this, 'render_fingerprint_agent_url_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'visitor_retention_days',
			__('Visitor Retention (days)', 'twostars-platform'),
			[$this, 'render_visitor_retention_days_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'visitor_consent_title',
			__('Consent Banner Title', 'twostars-platform'),
			[$this, 'render_visitor_consent_title_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'visitor_consent_description',
			__('Consent Banner Description', 'twostars-platform'),
			[$this, 'render_visitor_consent_description_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'visitor_consent_accept_label',
			__('Consent Accept Label', 'twostars-platform'),
			[$this, 'render_visitor_consent_accept_label_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);

		add_settings_field(
			'visitor_consent_decline_label',
			__('Consent Decline Label', 'twostars-platform'),
			[$this, 'render_visitor_consent_decline_label_field'],
			'twostars-platform',
			'twostars_platform_visitors'
		);
	}

	/**
	 * @param mixed $settings
	 * @return array<string, mixed>
	 */
	public function sanitize_settings($settings): array
	{
		$settings = is_array($settings) ? $settings : [];

		return [
			'calendly_event_url'      => esc_url_raw((string) ($settings['calendly_event_url'] ?? '')),
			'calendly_badge_enabled'  => ! empty($settings['calendly_badge_enabled']),
			'calendly_badge_text'     => sanitize_text_field((string) ($settings['calendly_badge_text'] ?? 'Schedule time with us')),
			'calendly_badge_color'    => sanitize_hex_color((string) ($settings['calendly_badge_color'] ?? '#0069ff')) ?: '#0069ff',
			'calendly_badge_text_color' => sanitize_hex_color((string) ($settings['calendly_badge_text_color'] ?? '#ffffff')) ?: '#ffffff',
			'calendly_badge_branding' => ! empty($settings['calendly_badge_branding']),
			'whatsapp_number'        => sanitize_text_field((string) ($settings['whatsapp_number'] ?? '')),
			'ai_assistant_enabled'   => ! empty($settings['ai_assistant_enabled']),
			'chatbot_icon_id'        => absint($settings['chatbot_icon_id'] ?? 0),
			'gemini_api_key'         => sanitize_text_field((string) ($settings['gemini_api_key'] ?? '')),
			'gemini_flash_model'     => sanitize_text_field((string) ($settings['gemini_flash_model'] ?? GeminiConfig::get_flash_model())),
			'gemini_reasoning_model' => sanitize_text_field((string) ($settings['gemini_reasoning_model'] ?? GeminiConfig::get_reasoning_model())),
			'intelligence_prompt_min_score' => max(1, absint($settings['intelligence_prompt_min_score'] ?? 4)),
			'intelligence_max_prompts_per_session' => max(1, absint($settings['intelligence_max_prompts_per_session'] ?? 2)),
			'intelligence_high_intent_score' => max(2, absint($settings['intelligence_high_intent_score'] ?? 8)),
			'social_feed_enabled'    => ! empty($settings['social_feed_enabled']),
			'visitor_tracking_enabled' => array_key_exists('visitor_tracking_enabled', $settings) ? ! empty($settings['visitor_tracking_enabled']) : true,
			'visitor_personalization_enabled' => array_key_exists('visitor_personalization_enabled', $settings) ? ! empty($settings['visitor_personalization_enabled']) : true,
			'visitorapi_project_id' => sanitize_text_field((string) ($settings['visitorapi_project_id'] ?? self::DEFAULT_VISITORAPI_PROJECT_ID)),
			'fingerprint_public_key' => sanitize_text_field((string) ($settings['fingerprint_public_key'] ?? self::DEFAULT_FINGERPRINT_PUBLIC_KEY)),
			'fingerprint_region' => sanitize_text_field((string) ($settings['fingerprint_region'] ?? self::DEFAULT_FINGERPRINT_REGION)) ?: self::DEFAULT_FINGERPRINT_REGION,
			'fingerprint_agent_url' => esc_url_raw((string) ($settings['fingerprint_agent_url'] ?? ('https://fpjscdn.net/v4/' . self::DEFAULT_FINGERPRINT_PUBLIC_KEY))),
			'visitor_retention_days' => max(30, absint($settings['visitor_retention_days'] ?? 180)),
			'visitor_consent_title' => sanitize_text_field((string) ($settings['visitor_consent_title'] ?? __('Personalized trip planning', 'twostars-platform'))),
			'visitor_consent_description' => sanitize_textarea_field((string) ($settings['visitor_consent_description'] ?? __('Allow Two Stars to remember your planning journey, tailor route suggestions, and show the right guidance for your location and device.', 'twostars-platform'))),
			'visitor_consent_accept_label' => sanitize_text_field((string) ($settings['visitor_consent_accept_label'] ?? __('Allow personalization', 'twostars-platform'))),
			'visitor_consent_decline_label' => sanitize_text_field((string) ($settings['visitor_consent_decline_label'] ?? __('No thanks', 'twostars-platform'))),
		];
	}

	public function render_page(): void
	{
		?>
		<div class="wrap twostars-manager">
			<div class="twostars-manager__hero twostars-manager__hero--compact">
				<div class="twostars-manager__hero-copy">
					<p class="twostars-manager__eyebrow"><?php esc_html_e('Platform Settings', 'twostars-platform'); ?></p>
					<h1><?php esc_html_e('Two Stars Platform', 'twostars-platform'); ?></h1>
					<p><?php esc_html_e('Core settings for CRM, AI assistance, visitor intelligence, and front-end planning tools. This screen now lives inside Two Stars Admin so operators can manage the platform from one workspace.', 'twostars-platform'); ?></p>
				</div>
				<div class="twostars-manager__hero-actions twostars-manager__hero-actions--stacked">
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager')); ?>"><?php esc_html_e('Back to Two Stars Admin', 'twostars-platform'); ?></a>
					<a class="button button-primary button-hero" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener"><?php esc_html_e('View Live Site', 'twostars-platform'); ?></a>
				</div>
			</div>
			<div class="twostars-manager-form__section">
			<form action="options.php" method="post">
				<?php
				settings_fields(self::OPTION_GROUP);
				do_settings_sections('twostars-platform');
				submit_button();
				?>
			</form>
			</div>
		</div>
		<?php
	}

	public function render_whatsapp_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[whatsapp_number]"
			value="<?php echo esc_attr((string) ($settings['whatsapp_number'] ?? '')); ?>"
			class="regular-text"
			placeholder="+264..."
		>
		<?php
	}

	public function render_calendly_event_url_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="url"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[calendly_event_url]"
			value="<?php echo esc_attr((string) ($settings['calendly_event_url'] ?? '')); ?>"
			class="regular-text"
			placeholder="https://calendly.com/..."
		>
		<p class="description">
			<?php esc_html_e('Default Calendly event URL used for booking popups across the site.', 'twostars-platform'); ?>
		</p>
		<?php
	}

	public function render_calendly_badge_enabled_field(): void
	{
		$settings = $this->get_settings();
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr(self::OPTION_NAME); ?>[calendly_badge_enabled]"
				value="1"
				<?php checked(! empty($settings['calendly_badge_enabled'])); ?>
			>
			<?php esc_html_e('Show the floating Calendly badge widget site-wide when a default Calendly URL is set.', 'twostars-platform'); ?>
		</label>
		<?php
	}

	public function render_calendly_badge_text_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[calendly_badge_text]"
			value="<?php echo esc_attr((string) ($settings['calendly_badge_text'] ?? 'Schedule time with us')); ?>"
			class="regular-text"
		>
		<?php
	}

	public function render_calendly_badge_color_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[calendly_badge_color]"
			value="<?php echo esc_attr((string) ($settings['calendly_badge_color'] ?? '#0069ff')); ?>"
			class="regular-text"
		>
		<?php
	}

	public function render_calendly_badge_text_color_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[calendly_badge_text_color]"
			value="<?php echo esc_attr((string) ($settings['calendly_badge_text_color'] ?? '#ffffff')); ?>"
			class="regular-text"
		>
		<?php
	}

	public function render_calendly_badge_branding_field(): void
	{
		$settings = $this->get_settings();
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr(self::OPTION_NAME); ?>[calendly_badge_branding]"
				value="1"
				<?php checked(! empty($settings['calendly_badge_branding'])); ?>
			>
			<?php esc_html_e('Enable Calendly branding on the floating badge.', 'twostars-platform'); ?>
		</label>
		<?php
	}

	public function render_ai_field(): void
	{
		$settings = $this->get_settings();
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr(self::OPTION_NAME); ?>[ai_assistant_enabled]"
				value="1"
				<?php checked(! empty($settings['ai_assistant_enabled'])); ?>
			>
			<?php esc_html_e('Turn on the website intelligence and assistant scaffolding.', 'twostars-platform'); ?>
		</label>
		<?php
	}

	public function render_chatbot_icon_field(): void
	{
		$settings = $this->get_settings();
		$icon_id  = absint($settings['chatbot_icon_id'] ?? 0);
		$icon_url = $icon_id > 0 ? wp_get_attachment_image_url($icon_id, 'thumbnail') : '';
		?>
		<div class="twostars-media-field" data-twostars-media-field data-target-input="twostars-platform-chatbot-icon-id" data-multiple="0">
			<input
				type="hidden"
				id="twostars-platform-chatbot-icon-id"
				name="<?php echo esc_attr(self::OPTION_NAME); ?>[chatbot_icon_id]"
				value="<?php echo esc_attr((string) $icon_id); ?>"
			>
			<div class="twostars-media-field__preview" data-twostars-media-preview>
				<?php if (is_string($icon_url) && $icon_url !== '') : ?>
					<div class="twostars-media-field__thumb" data-attachment-id="<?php echo esc_attr((string) $icon_id); ?>">
						<img src="<?php echo esc_url($icon_url); ?>" alt="">
						<button type="button" class="twostars-media-field__remove" data-twostars-media-remove aria-label="<?php esc_attr_e('Remove image', 'twostars-platform'); ?>">
							<span aria-hidden="true">x</span>
						</button>
					</div>
				<?php endif; ?>
			</div>
			<div class="twostars-media-field__actions">
				<button type="button" class="button button-secondary" data-twostars-media-open><?php esc_html_e('Choose Icon', 'twostars-platform'); ?></button>
				<button type="button" class="button-link-delete" data-twostars-media-clear><?php esc_html_e('Clear', 'twostars-platform'); ?></button>
			</div>
		</div>
		<p class="description">
			<?php esc_html_e('Used for the floating chatbot launcher and the chat header brand mark.', 'twostars-platform'); ?>
		</p>
		<?php
	}

	public function render_gemini_api_key_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="password"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[gemini_api_key]"
			value="<?php echo esc_attr((string) ($settings['gemini_api_key'] ?? '')); ?>"
			class="regular-text"
			autocomplete="off"
		>
		<p class="description">
			<?php esc_html_e('Gemini API key used for site AI features, chatbot replies, and future SEO/intelligence features.', 'twostars-platform'); ?>
		</p>
		<?php
	}

	public function render_gemini_flash_model_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[gemini_flash_model]"
			value="<?php echo esc_attr((string) ($settings['gemini_flash_model'] ?? GeminiConfig::get_flash_model())); ?>"
			class="regular-text"
			placeholder="<?php echo esc_attr(GeminiConfig::get_flash_model()); ?>"
		>
		<p class="description">
			<?php esc_html_e('Primary fast model for most site AI interactions.', 'twostars-platform'); ?>
		</p>
		<?php
	}

	public function render_gemini_reasoning_model_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[gemini_reasoning_model]"
			value="<?php echo esc_attr((string) ($settings['gemini_reasoning_model'] ?? GeminiConfig::get_reasoning_model())); ?>"
			class="regular-text"
			placeholder="<?php echo esc_attr(GeminiConfig::get_reasoning_model()); ?>"
		>
		<p class="description">
			<?php esc_html_e('Higher-reasoning Gemini model for heavier planning, SEO, and intelligence tasks.', 'twostars-platform'); ?>
		</p>
		<?php
	}

	public function render_social_feed_field(): void
	{
		$settings = $this->get_settings();
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr(self::OPTION_NAME); ?>[social_feed_enabled]"
				value="1"
				<?php checked(! empty($settings['social_feed_enabled'])); ?>
			>
			<?php esc_html_e('Allow approved social feed content to be shown on the site.', 'twostars-platform'); ?>
		</label>
		<?php
	}

	public function render_visitor_tracking_enabled_field(): void
	{
		$settings = $this->get_settings();
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr(self::OPTION_NAME); ?>[visitor_tracking_enabled]"
				value="1"
				<?php checked(! empty($settings['visitor_tracking_enabled'])); ?>
			>
			<?php esc_html_e('Enable consent-gated visitor identity, location enrichment, and CRM visitor profiles.', 'twostars-platform'); ?>
		</label>
		<?php
	}

	public function render_visitor_personalization_enabled_field(): void
	{
		$settings = $this->get_settings();
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr(self::OPTION_NAME); ?>[visitor_personalization_enabled]"
				value="1"
				<?php checked(! empty($settings['visitor_personalization_enabled'])); ?>
			>
			<?php esc_html_e('Use visitor country, returning status, and journey context to personalize content and AI guidance.', 'twostars-platform'); ?>
		</label>
		<?php
	}

	public function render_visitorapi_project_id_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[visitorapi_project_id]"
			value="<?php echo esc_attr((string) ($settings['visitorapi_project_id'] ?? '')); ?>"
			class="regular-text"
		>
		<?php
	}

	public function render_fingerprint_public_key_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[fingerprint_public_key]"
			value="<?php echo esc_attr((string) ($settings['fingerprint_public_key'] ?? '')); ?>"
			class="regular-text"
		>
		<?php
	}

	public function render_fingerprint_region_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[fingerprint_region]"
			value="<?php echo esc_attr((string) ($settings['fingerprint_region'] ?? 'ap')); ?>"
			class="small-text"
			placeholder="ap"
		>
		<?php
	}

	public function render_fingerprint_agent_url_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="url"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[fingerprint_agent_url]"
			value="<?php echo esc_attr((string) ($settings['fingerprint_agent_url'] ?? 'https://fpjscdn.net/v4/16ovVe3T8gJzgsEJaXaS')); ?>"
			class="regular-text"
		>
		<p class="description">
			<?php esc_html_e('JavaScript agent URL used to load the Fingerprint browser agent.', 'twostars-platform'); ?>
		</p>
		<?php
	}

	public function render_visitor_retention_days_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="number"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[visitor_retention_days]"
			value="<?php echo esc_attr((string) ($settings['visitor_retention_days'] ?? 180)); ?>"
			class="small-text"
			min="30"
			step="1"
		>
		<?php
	}

	public function render_visitor_consent_title_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[visitor_consent_title]"
			value="<?php echo esc_attr((string) ($settings['visitor_consent_title'] ?? __('Personalized trip planning', 'twostars-platform'))); ?>"
			class="regular-text"
		>
		<?php
	}

	public function render_visitor_consent_description_field(): void
	{
		$settings = $this->get_settings();
		?>
		<textarea
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[visitor_consent_description]"
			rows="4"
			class="large-text"
		><?php echo esc_textarea((string) ($settings['visitor_consent_description'] ?? __('Allow Two Stars to remember your planning journey, tailor route suggestions, and show the right guidance for your location and device.', 'twostars-platform'))); ?></textarea>
		<?php
	}

	public function render_visitor_consent_accept_label_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[visitor_consent_accept_label]"
			value="<?php echo esc_attr((string) ($settings['visitor_consent_accept_label'] ?? __('Allow personalization', 'twostars-platform'))); ?>"
			class="regular-text"
		>
		<?php
	}

	public function render_visitor_consent_decline_label_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[visitor_consent_decline_label]"
			value="<?php echo esc_attr((string) ($settings['visitor_consent_decline_label'] ?? __('No thanks', 'twostars-platform'))); ?>"
			class="regular-text"
		>
		<?php
	}

	public function render_intelligence_prompt_min_score_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="number"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[intelligence_prompt_min_score]"
			value="<?php echo esc_attr((string) ($settings['intelligence_prompt_min_score'] ?? 4)); ?>"
			class="small-text"
			min="1"
			step="1"
		>
		<p class="description">
			<?php esc_html_e('Minimum intent score before the website intelligence prompt is allowed to appear.', 'twostars-platform'); ?>
		</p>
		<?php
	}

	public function render_intelligence_max_prompts_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="number"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[intelligence_max_prompts_per_session]"
			value="<?php echo esc_attr((string) ($settings['intelligence_max_prompts_per_session'] ?? 2)); ?>"
			class="small-text"
			min="1"
			step="1"
		>
		<p class="description">
			<?php esc_html_e('Maximum number of intelligence prompts shown during a single visitor session.', 'twostars-platform'); ?>
		</p>
		<?php
	}

	public function render_intelligence_high_intent_score_field(): void
	{
		$settings = $this->get_settings();
		?>
		<input
			type="number"
			name="<?php echo esc_attr(self::OPTION_NAME); ?>[intelligence_high_intent_score]"
			value="<?php echo esc_attr((string) ($settings['intelligence_high_intent_score'] ?? 8)); ?>"
			class="small-text"
			min="2"
			step="1"
		>
		<p class="description">
			<?php esc_html_e('Score threshold used to classify a session as high intent in the intelligence dashboard.', 'twostars-platform'); ?>
		</p>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_settings(): array
	{
		$settings = get_option(self::OPTION_NAME, []);
		$settings = is_array($settings) ? $settings : [];

		return array_merge(
			[
				'chatbot_icon_id' => 0,
				'visitor_tracking_enabled' => true,
				'visitor_personalization_enabled' => true,
				'visitorapi_project_id' => self::DEFAULT_VISITORAPI_PROJECT_ID,
				'fingerprint_public_key' => self::DEFAULT_FINGERPRINT_PUBLIC_KEY,
				'fingerprint_region' => self::DEFAULT_FINGERPRINT_REGION,
				'fingerprint_agent_url' => 'https://fpjscdn.net/v4/' . self::DEFAULT_FINGERPRINT_PUBLIC_KEY,
				'visitor_retention_days' => 180,
				'visitor_consent_title' => __('Personalized trip planning', 'twostars-platform'),
				'visitor_consent_description' => __('Allow Two Stars to remember your planning journey, tailor route suggestions, and show the right guidance for your location and device.', 'twostars-platform'),
				'visitor_consent_accept_label' => __('Allow personalization', 'twostars-platform'),
				'visitor_consent_decline_label' => __('No thanks', 'twostars-platform'),
			],
			$settings
		);
	}

	public static function get_setting(string $key, mixed $default = null): mixed
	{
		$settings = get_option(self::OPTION_NAME, []);
		$settings = is_array($settings) ? $settings : [];

		return array_key_exists($key, $settings) ? $settings[$key] : $default;
	}
}
