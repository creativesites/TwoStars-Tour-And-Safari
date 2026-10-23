<?php
declare(strict_types=1);

namespace TwoStars\Platform\CRM;

use TwoStars\Platform\Admin\SettingsPage;
use TwoStars\Platform\Support\Service;

final class Chatbot implements Service
{
	private bool $assets_localized = false;
	private bool $shortcode_rendered = false;

	public function register(): void
	{
		add_action('wp_enqueue_scripts', [$this, 'register_assets']);
		add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_floating_assets'], 20);
		add_shortcode('twostars_chatbot', [$this, 'render_shortcode']);
		add_action('wp_footer', [$this, 'render_floating_chatbot'], 40);
	}

	public function register_assets(): void
	{
		wp_register_style(
			'twostars-platform-chatbot',
			TWOSTARS_PLATFORM_URL . 'assets/css/chatbot.css',
			[],
			TWOSTARS_PLATFORM_VERSION
		);

		wp_register_script(
			'twostars-platform-chatbot',
			TWOSTARS_PLATFORM_URL . 'assets/js/chatbot.js',
			['twostars-platform-intelligence'],
			TWOSTARS_PLATFORM_VERSION,
			true
		);
	}

	/**
	 * @param array<string, mixed> $atts
	 */
	public function render_shortcode(array $atts = []): string
	{
		$this->shortcode_rendered = true;
		$this->enqueue_assets();

		$atts = shortcode_atts(
			[
				'title'   => __('Two Stars Guide', 'twostars-platform'),
				'variant' => 'inline',
			],
			$atts,
			'twostars_chatbot'
		);

		return $this->render_chatbot_markup($atts);
	}

	public function render_floating_chatbot(): void
	{
		if (! $this->should_render_floating_chatbot()) {
			return;
		}

		$this->enqueue_assets();

		$panel_id = wp_unique_id('twostars-chatbot-panel-');
		$icon_url = $this->get_chatbot_icon_url();

		echo '<div class="twostars-chatbot-float" data-twostars-chatbot-float>';

		echo '<button type="button" class="twostars-chatbot-float__launcher" data-twostars-chatbot-toggle aria-expanded="false" aria-controls="' . esc_attr($panel_id) . '">';

		echo '<span class="twostars-chatbot-float__launcher-icon" aria-hidden="true">';
		echo '<img src="' . esc_url($icon_url) . '" alt="" loading="lazy">';
		echo '</span>';
		echo '<span class="twostars-chatbot-float__launcher-ping" aria-hidden="true"></span>';
		echo '</button>';

		echo '<div class="twostars-chatbot-float__panel" id="' . esc_attr($panel_id) . '" data-twostars-chatbot-panel hidden>';
		echo $this->render_chatbot_markup([
			'title'   => __('Two Stars Guide', 'twostars-platform'),
			'variant' => 'floating',
		]);
		echo '</div>';

		echo '<div class="twostars-chatbot-float__nudge" data-twostars-chatbot-nudge hidden>';
		echo '<p class="twostars-chatbot-float__nudge-copy" data-twostars-chatbot-nudge-copy></p>';
		echo '<div class="twostars-chatbot-float__nudge-actions">';
		echo '<button type="button" class="twostars-chatbot-float__nudge-open" data-twostars-chatbot-nudge-open>Open</button>';
		echo '<button type="button" class="twostars-chatbot-float__nudge-dismiss" data-twostars-chatbot-nudge-dismiss aria-label="Dismiss">&times;</button>';
		echo '</div>';
		echo '</div>';

		echo '</div>';
	}

	private function enqueue_assets(): void
	{
		wp_enqueue_style('twostars-platform-chatbot');
		wp_enqueue_style('twostars-platform-lead-form');
		wp_enqueue_script('twostars-platform-chatbot');
		wp_enqueue_script('twostars-platform-lead-form');

		if ($this->assets_localized) {
			return;
		}

		wp_localize_script(
			'twostars-platform-chatbot',
			'twoStarsChatbot',
			[
				'restUrl'          => esc_url_raw(rest_url('twostars/v1/chat')),
				'historyUrl'       => esc_url_raw(rest_url('twostars/v1/chat/history')),
				'threadUrl'        => esc_url_raw(rest_url('twostars/v1/chat/thread')),
				'crmLeadUrl'       => esc_url_raw(rest_url('twostars/v1/crm/lead')),
				'errorMessage'     => __('The Two Stars guide could not respond right now. You can still send your inquiry directly and the team can follow up.', 'twostars-platform'),
				'defaultGreeting'  => __('Ask about Sandwich Harbour, Flamingo and Pink Lake, Swakopmund + Dune 7, Spitzkoppe, private tours, pickups from Walvis Bay or Swakopmund, or the best route for your day.', 'twostars-platform'),
				'handoffLabel'     => __('Share my details', 'twostars-platform'),
				'typingLabel'      => __('Two Stars is preparing a reply...', 'twostars-platform'),
				'resetLabel'       => __('Clear chat', 'twostars-platform'),
				'assistantLabel'   => __('Two Stars Tour Guide', 'twostars-platform'),
				'storagePrefix'    => 'twostars_chat_history_',
			]
		);

		$this->assets_localized = true;
	}

	public function maybe_enqueue_floating_assets(): void
	{
		if (is_admin() || wp_doing_ajax() || is_feed()) {
			return;
		}

		$this->enqueue_assets();
	}

	private function should_render_floating_chatbot(): bool
	{
		if (is_admin() || wp_doing_ajax() || is_feed()) {
			return false;
		}

		if ($this->shortcode_rendered) {
			return false;
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $atts
	 */
	private function render_chatbot_markup(array $atts): string
	{
		$variant = isset($atts['variant']) && $atts['variant'] === 'floating' ? 'floating' : 'inline';
		$input_id = wp_unique_id('twostars-chatbot-message-');
		$is_floating = $variant === 'floating';
		$icon_url = $this->get_chatbot_icon_url();

		ob_start();
		?>
		<section class="twostars-chatbot twostars-chatbot--<?php echo esc_attr($variant); ?>" data-twostars-chatbot data-twostars-chatbot-variant="<?php echo esc_attr($variant); ?>">
			<header class="twostars-chatbot__header<?php echo $is_floating ? ' twostars-chatbot__header--floating' : ''; ?>">
				<div class="twostars-chatbot__header-top">
					<div class="twostars-chatbot__brand">
						<span class="twostars-chatbot__brand-mark" aria-hidden="true"><img src="<?php echo esc_url($icon_url); ?>" alt="" loading="lazy"></span>
						<div class="twostars-chatbot__brand-copy">
							<strong><?php echo esc_html((string) ($atts['title'] ?? __('Two Stars Guide', 'twostars-platform'))); ?></strong>
							<span class="twostars-chatbot__eyebrow"><span class="twostars-chatbot__online" aria-hidden="true"></span><?php esc_html_e('AI guide · Walvis Bay & Swakopmund', 'twostars-platform'); ?></span>
						</div>
					</div>
					<div class="twostars-chatbot__header-actions">
						<span class="twostars-chatbot__status-pill" data-twostars-chatbot-status><?php esc_html_e('Planning', 'twostars-platform'); ?></span>
						<?php $ts_settings = get_option('twostars_platform_settings', []); $ts_wa = preg_replace('/[^0-9]/', '', (string) (is_array($ts_settings) ? ($ts_settings['whatsapp_number'] ?? '') : '')); ?>
						<?php if ($ts_wa !== '') : ?>
							<a class="twostars-chatbot__human" href="<?php echo esc_url('https://wa.me/' . $ts_wa); ?>" target="_blank" rel="noopener noreferrer" data-twostars-event="whatsapp_click" aria-label="<?php esc_attr_e('Talk to the team on WhatsApp', 'twostars-platform'); ?>" title="<?php esc_attr_e('Talk to the team on WhatsApp', 'twostars-platform'); ?>">
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3.5a8.5 8.5 0 0 0-7.3 12.8L3.5 20.5l4.3-1.1A8.5 8.5 0 1 0 12 3.5z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"></path><path d="M9 8.8c.2 3 2.6 5.4 5.7 5.7l1-1.4-1.9-1-.8.7a3.6 3.6 0 0 1-1.9-1.9l.7-.8-1-1.9z" fill="currentColor"></path></svg>
							</a>
						<?php endif; ?>
						<button type="button" class="twostars-chatbot__new-chat" data-twostars-chatbot-reset aria-label="<?php esc_attr_e('Start a new chat', 'twostars-platform'); ?>" title="<?php esc_attr_e('Start a new chat', 'twostars-platform'); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
							</svg>
						</button>
						<button type="button" class="twostars-chatbot__history-toggle" data-twostars-chatbot-history-toggle>
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
								<path d="M12 7V12L15 14M21 12A9 9 0 1 1 18.4 5.6M21 4V10H15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
							</svg>
							<span class="screen-reader-text"><?php esc_html_e('Conversation history', 'twostars-platform'); ?></span>
						</button>
						<?php if ($is_floating) : ?>
							<button type="button" class="twostars-chatbot__close" data-twostars-chatbot-close aria-label="<?php esc_attr_e('Close guide', 'twostars-platform'); ?>">
								<span aria-hidden="true">&times;</span>
							</button>
						<?php endif; ?>
					</div>
				</div>
				<div class="twostars-chatbot__context-bar" data-twostars-chatbot-context>
					<span class="twostars-chatbot__chip"><?php esc_html_e('Walvis Bay & Swakopmund', 'twostars-platform'); ?></span>
				</div>
			</header>

			<div class="twostars-chatbot__history-drawer" data-twostars-chatbot-history-drawer hidden>
				<div class="twostars-chatbot__history-header">
					<h3><?php esc_html_e('Previous conversations', 'twostars-platform'); ?></h3>
					<button type="button" class="twostars-chatbot__history-close" data-twostars-chatbot-history-close aria-label="<?php esc_attr_e('Close conversation history', 'twostars-platform'); ?>">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<label class="twostars-chatbot__history-search">
					<span class="screen-reader-text"><?php esc_html_e('Search conversations', 'twostars-platform'); ?></span>
					<input type="search" placeholder="<?php esc_attr_e('Search conversations', 'twostars-platform'); ?>" data-twostars-chatbot-history-search>
				</label>
				<div class="twostars-chatbot__history-list" data-twostars-chatbot-history-list></div>
			</div>

			<div class="twostars-chatbot__conversation">
				<div class="twostars-chatbot__messages" role="log" aria-live="polite" aria-label="<?php esc_attr_e('Conversation', 'twostars-platform'); ?>" data-twostars-chatbot-messages data-chatbot-initial="<?php echo esc_attr(__('Hi, I’m the Two Stars guide. Ask me about tours, prices, pickups, or how to fit a cruise-port day.', 'twostars-platform')); ?>"></div>
				<div class="twostars-chatbot__typing" data-twostars-chatbot-typing hidden>
					<span class="twostars-chatbot__typing-dot"></span>
					<span class="twostars-chatbot__typing-dot"></span>
					<span class="twostars-chatbot__typing-dot"></span>
					<span><?php esc_html_e('Two Stars is preparing a reply...', 'twostars-platform'); ?></span>
				</div>
			</div>

			<div class="twostars-chatbot__rail" data-twostars-chatbot-rail>
				<button type="button" class="twostars-chatbot__rail-button" data-chatbot-prompt="<?php esc_attr_e('How much do the tours cost, and what do children pay?', 'twostars-platform'); ?>">
					<?php esc_html_e('Prices', 'twostars-platform'); ?>
				</button>
				<button type="button" class="twostars-chatbot__rail-button" data-chatbot-prompt="<?php esc_attr_e('Which tour is best if I want Sandwich Harbour and great photo stops?', 'twostars-platform'); ?>">
					<?php esc_html_e('Best for photos', 'twostars-platform'); ?>
				</button>
				<button type="button" class="twostars-chatbot__rail-button" data-chatbot-prompt="<?php esc_attr_e('I am on a cruise. What can I do in a port day and can you get me back to the ship?', 'twostars-platform'); ?>">
					<?php esc_html_e('Cruise day', 'twostars-platform'); ?>
				</button>
				<button type="button" class="twostars-chatbot__rail-button" data-chatbot-prompt="<?php esc_attr_e('Do you pick up from Walvis Bay and Swakopmund hotels, and how many people are in a group?', 'twostars-platform'); ?>">
					<?php esc_html_e('Pickups and group size', 'twostars-platform'); ?>
				</button>
				<button type="button" class="twostars-chatbot__rail-button" data-chatbot-prompt="<?php esc_attr_e('I want a private tour with flexible timing. Can you help?', 'twostars-platform'); ?>">
					<?php esc_html_e('Private tour', 'twostars-platform'); ?>
				</button>
			</div>

			<div class="twostars-chatbot__handoff twostars-chatbot__panel" data-twostars-chatbot-handoff hidden>
				<?php
				echo LeadForm::render_form(
					[
						'title'       => __('Let the team follow up', 'twostars-platform'),
						'description' => __('We can continue with route guidance, pickup planning, private options, and the right next booking step.', 'twostars-platform'),
						'source'      => 'chatbot',
					]
				);
				?>
			</div>

			<form class="twostars-chatbot__composer" data-twostars-chatbot-form>
				<div class="twostars-chatbot__composer-field">
					<label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php esc_html_e('Ask the Two Stars guide', 'twostars-platform'); ?></label>
					<textarea id="<?php echo esc_attr($input_id); ?>" name="message" rows="1" placeholder="<?php esc_attr_e('Ask about tours or prices…', 'twostars-platform'); ?>" required></textarea>
					<button type="submit" class="twostars-chatbot__send" aria-label="<?php esc_attr_e('Send message', 'twostars-platform'); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
							<path d="M4 12L20 4L15 20L11.5 13.5L4 12Z" fill="currentColor"></path>
						</svg>
					</button>
				</div>
			</form>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	private function get_chatbot_icon_url(): string
	{
		$icon_id = absint(SettingsPage::get_setting('chatbot_icon_id', 0));

		if ($icon_id > 0) {
			$icon_url = wp_get_attachment_image_url($icon_id, 'thumbnail');

			if (is_string($icon_url) && $icon_url !== '') {
				return $icon_url;
			}
		}

		return content_url('uploads/2026/03/chat-bot.png');
	}
}
