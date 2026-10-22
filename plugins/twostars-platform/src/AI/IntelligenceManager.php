<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Visitors\VisitorProfileManager;

final class IntelligenceManager implements Service
{
	public function register(): void
	{
		add_action('wp_enqueue_scripts', [$this, 'register_frontend_script']);
	}

	public function register_frontend_script(): void
	{
		$ai_enabled = GeminiConfig::is_enabled();
		$post_id    = is_singular() ? (int) get_the_ID() : 0;
		$page_type  = $this->get_page_type();

		wp_register_style(
			'twostars-platform-intelligence',
			TWOSTARS_PLATFORM_URL . 'assets/css/intelligence.css',
			[],
			TWOSTARS_PLATFORM_VERSION
		);

		wp_register_script(
			'twostars-platform-intelligence',
			TWOSTARS_PLATFORM_URL . 'assets/js/intelligence.js',
			[],
			TWOSTARS_PLATFORM_VERSION,
			true
		);

		wp_localize_script(
			'twostars-platform-intelligence',
			'twoStarsPlatform',
			[
				'restUrl'        => esc_url_raw(rest_url('twostars/v1/intelligence/event')),
				'visitorActionUrl' => esc_url_raw(rest_url('twostars/v1/visitor/action')),
				'visitorIdentifyUrl' => esc_url_raw(rest_url('twostars/v1/visitor/identify')),
				'chatUrl'        => esc_url_raw(rest_url('twostars/v1/chat')),
				'nonce'          => wp_create_nonce('wp_rest'),
				'aiEnabled'      => $ai_enabled,
				'flashModel'     => GeminiConfig::get_flash_model(),
				'reasoningModel' => GeminiConfig::get_reasoning_model(),
				'contactUrl'     => esc_url(home_url('/contact/')),
				'bookingUrl'     => esc_url(home_url('/booking/')),
				'crmLeadUrl'     => esc_url_raw(rest_url('twostars/v1/crm/lead')),
				'pageType'       => $page_type,
				'postId'         => $post_id,
				'pageContext'    => ContextStore::get_page_context($page_type, $post_id),
				'visitorConfig'  => VisitorProfileManager::get_public_config(),
			]
		);

		wp_enqueue_style('twostars-platform-intelligence');
		wp_enqueue_script('twostars-platform-intelligence');
	}

	private function get_page_type(): string
	{
		if (is_singular('tour')) {
			return 'tour';
		}

		if (is_singular('destination')) {
			return 'destination';
		}

		if (is_singular('faq')) {
			return 'faq';
		}

		if (is_singular('testimonial')) {
			return 'testimonial';
		}

		if (is_singular('post')) {
			return 'post';
		}

		if (is_page('booking')) {
			return 'booking';
		}

		if (is_page('contact')) {
			return 'contact';
		}

		if (is_front_page()) {
			return 'home';
		}

		if (is_home() || is_archive()) {
			return 'archive';
		}

		return 'generic';
	}
}
