<?php
declare(strict_types=1);

namespace TwoStars\Platform\Rest;

use TwoStars\Platform\AI\ConversationMemory;
use TwoStars\Platform\AI\GeminiClient;
use TwoStars\Platform\AI\GeminiConfig;
use TwoStars\Platform\AI\ContextStore;
use TwoStars\Platform\AI\WebsiteIntelligence;
use TwoStars\Platform\Booking\BookingManager;
use TwoStars\Platform\CRM\LeadManager;
use TwoStars\Platform\Reviews\ReviewManager;
use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Visitors\VisitorProfileManager;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class Routes implements Service
{
	public function register(): void
	{
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function register_routes(): void
	{
		register_rest_route(
			'twostars/v1',
			'/chat',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'handle_chat'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/chat/history',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'handle_chat_history'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/chat/thread',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'handle_chat_thread'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/intelligence/event',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'handle_event'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/visitor/identify',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'handle_identify_visitor'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/visitor/action',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'handle_visitor_action'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/crm/lead',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'handle_create_lead'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/reviews/submit',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'handle_submit_review'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/booking/availability',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'handle_booking_availability'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/booking/create',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'handle_create_booking'],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'twostars/v1',
			'/admin/visitors/list',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'handle_admin_visitors_list'],
				'permission_callback' => static fn() => current_user_can('edit_posts'),
			]
		);

		register_rest_route(
			'twostars/v1',
			'/admin/visitors/summary',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'handle_admin_visitors_summary'],
				'permission_callback' => static fn() => current_user_can('edit_posts'),
			]
		);

		register_rest_route(
			'twostars/v1',
			'/admin/visitors/map',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'handle_admin_visitors_map'],
				'permission_callback' => static fn() => current_user_can('edit_posts'),
			]
		);

		register_rest_route(
			'twostars/v1',
			'/admin/visitors/profile/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'handle_admin_visitors_profile'],
				'permission_callback' => static fn() => current_user_can('edit_posts'),
			]
		);
	}

	public function handle_chat(WP_REST_Request $request): WP_REST_Response
	{
		$message = sanitize_textarea_field((string) $request->get_param('message'));

		if (! $this->allow_chat_request()) {
			return new WP_REST_Response(
				[
					'success' => false,
					'reply'   => __('You are sending messages quickly. Please wait a moment, or message the team on WhatsApp for a faster answer.', 'twostars-platform'),
				],
				429
			);
		}

		$message      = mb_substr($message, 0, 1200);
		$chat_context = $this->build_chat_context($request);
		$grounding    = ContextStore::get_grounding_pack($message, $chat_context);
		$reply        = $this->get_fallback_chat_reply($message, $chat_context, $grounding);
		$handoff      = $this->should_suggest_handoff($message, $chat_context);
		$fact_reply   = \TwoStars\Platform\AI\FactAnswers::reply($message);

		if ($fact_reply !== null) {
			$reply   = $fact_reply;
			$handoff = false;
		}

		if (GeminiConfig::is_enabled()) {
			$gemini_reply = GeminiClient::generate_chat_reply($message, $chat_context, $grounding);

			if (! is_wp_error($gemini_reply)) {
				$reply = $this->enrich_chat_ui_blocks($gemini_reply, $grounding);
			} elseif ($handoff && $fact_reply === null) {
				$reply = __('This sounds like a real planning or booking inquiry. I can hand your details to the team so they can follow up with pricing, availability, or a tailored route.', 'twostars-platform');
			}
		}

		$conversation_key = (string) ($chat_context['conversation_key'] ?? '');

		if ($conversation_key !== '') {
			ConversationMemory::store_message(
				$conversation_key,
				'user',
				$message,
				[
					'page_type' => (string) ($chat_context['page_type'] ?? ''),
					'post_id'   => (int) ($chat_context['post_id'] ?? 0),
				],
				(string) ($chat_context['session_key'] ?? ''),
				(int) (($chat_context['visitor']['id'] ?? 0))
			);

			ConversationMemory::store_message(
				$conversation_key,
				'assistant',
				$this->strip_ui_blocks($reply),
				[
					'tours_mentioned'   => $this->extract_grounding_titles($grounding),
					'handoff_suggested' => $handoff,
				],
				(string) ($chat_context['session_key'] ?? ''),
				(int) (($chat_context['visitor']['id'] ?? 0))
			);
		}

		return new WP_REST_Response(
			[
				'success'           => true,
				'message'           => $message,
				'reply'             => $reply,
				'handoff_suggested' => $handoff,
				'persona'           => (string) ($chat_context['current_persona'] ?? 'general_browser'),
				'conversation_key'  => $conversation_key,
				'history_count'     => (int) ($chat_context['conversation_message_count'] ?? 0) + 2,
			]
		);
	}

	/**
	 * Per-visitor limit on chat requests: 20 messages per 10 minutes. The endpoint is public and
	 * calls a paid AI API, so it must not be an open proxy.
	 */
	private function allow_chat_request(): bool
	{
		$ip  = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
		$key = 'twostars_chat_rl_' . md5($ip);
		$hit = (int) get_transient($key);

		if ($hit >= 20) {
			return false;
		}

		set_transient($key, $hit + 1, 10 * MINUTE_IN_SECONDS);

		return true;
	}

	public function handle_chat_history(WP_REST_Request $request): WP_REST_Response
	{
		$session_key = sanitize_text_field((string) $request->get_param('session_key'));
		$visitor_profile_id = absint($request->get_param('visitor_profile_id'));
		$threads = ConversationMemory::get_threads_for_context($session_key, $visitor_profile_id, 20);

		return new WP_REST_Response(
			[
				'success' => true,
				'threads' => $threads,
			]
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_chat_thread(WP_REST_Request $request)
	{
		$conversation_key = sanitize_text_field((string) $request->get_param('conversation_key'));
		$thread = ConversationMemory::get_thread_detail($conversation_key, 120);

		if ($thread === []) {
			return new WP_Error(
				'twostars_chat_thread_not_found',
				__('That conversation could not be found.', 'twostars-platform'),
				['status' => 404]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'thread'  => $thread,
			]
		);
	}

	public function handle_submit_review(WP_REST_Request $request): WP_REST_Response
	{
		$result = ReviewManager::submit_review((array) $request->get_json_params());

		if (is_wp_error($result)) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => $result->get_error_message(),
				],
				(int) ($result->get_error_data()['status'] ?? 400)
			);
		}

		return new WP_REST_Response($result, 200);
	}

	private function should_suggest_handoff(string $message, array $chat_context = []): bool
	{
		$lower = strtolower($message);
		$persona = (string) ($chat_context['current_persona'] ?? '');

		if ($persona === 'ready_to_book') {
			return true;
		}

		foreach (['book', 'booking', 'quote', 'price', 'cost', 'availability', 'custom', 'itinerary', 'contact', 'whatsapp', 'email'] as $trigger) {
			if (str_contains($lower, $trigger)) {
				return true;
			}
		}

		return false;
	}

	private function get_fallback_chat_reply(string $message, array $chat_context = [], array $grounding = []): string
	{
		$lower = strtolower($message);
		$persona = (string) ($chat_context['current_persona'] ?? 'general_browser');
		$page_context = (array) ($chat_context['page_context'] ?? []);
		$records = (array) ($grounding['records'] ?? []);
		$current_title = (string) ($page_context['title'] ?? '');

		if ($persona === 'comparison_shopper' && count((array) ($chat_context['visited_tour_titles'] ?? [])) >= 2) {
			$titles = array_values((array) $chat_context['visited_tour_titles']);

			return sprintf(
				__('You have been comparing %1$s and %2$s. I can break down route feel, pacing, key stops, pickups, and who each one suits best.', 'twostars-platform'),
				$titles[0],
				$titles[1]
			);
		}

		if ($persona === 'ready_to_book' && $current_title !== '') {
			return sprintf(
				__('You look close to a booking decision on %s. I can help clarify what is included, pickup practicality, private options, and the best next step.', 'twostars-platform'),
				$current_title
			);
		}

		if (str_contains($lower, 'sandwich harbour') || str_contains($lower, '4x4')) {
			return __('The Sandwich Harbour 4x4 Adventure is the strongest fit if you want dune driving, lagoon stops, the pink salt lake, and scenic photo moments in one outing.', 'twostars-platform');
		}

		if (str_contains($lower, 'flamingo') || str_contains($lower, 'pink lake') || str_contains($lower, 'pink salt lake') || str_contains($lower, 'bird')) {
			return __('The Flamingo and Pink Lake tour is the shorter, more relaxed option if birdlife, scenery, and photography are the main priority.', 'twostars-platform');
		}

		if (str_contains($lower, 'swakopmund') || str_contains($lower, 'dune 7')) {
			return __('The Swakopmund City Tour + Dune 7 combines coastal landmarks with a desert stop, so it works well if you want a lighter mix of town history and sand-slope scenery.', 'twostars-platform');
		}

		if (str_contains($lower, 'spitzkoppe')) {
			return __('Spitzkoppe is the full-day option for dramatic granite formations, wide-open scenery, and a slower scenic pace away from the coast.', 'twostars-platform');
		}

		if (str_contains($lower, 'private')) {
			return __('Private tours are the best fit if you want a dedicated vehicle, flexible timing, and a more personalized pace.', 'twostars-platform');
		}

		if (str_contains($lower, 'best time') || str_contains($lower, 'season') || str_contains($lower, 'when')) {
			return __('Travel timing depends on what matters most to you, whether that is dune scenery, lagoon birdlife, comfortable weather, or photography conditions. The team can help narrow this down around your dates.', 'twostars-platform');
		}

		if ($this->should_suggest_handoff($message, $chat_context)) {
			return __('This sounds like a real planning or booking inquiry. I can hand your details to the team so they can follow up with route fit, pickup planning, availability, or a private option.', 'twostars-platform');
		}

		if ($current_title !== '' && $records !== []) {
			return sprintf(
				__('I can help with %s using the tours and destination details already on this site.', 'twostars-platform'),
				$current_title
			);
		}

		return __('I can help with current Two Stars tours, destination stops, pickups from Walvis Bay or Swakopmund, private options, travel timing, and the next booking step.', 'twostars-platform');
	}

	/**
	 * @return array<string, mixed>
	 */
	private function build_chat_context(WP_REST_Request $request): array
	{
		$session_key  = sanitize_text_field((string) $request->get_param('session_key'));
		$conversation_key = sanitize_text_field((string) $request->get_param('conversation_key'));
		$page_type    = sanitize_text_field((string) $request->get_param('page_type'));
		$post_id      = absint($request->get_param('post_id'));
		$page_context = $request->get_param('page_context');
		$page_context = is_array($page_context) ? $page_context : [];
		$client_persona = sanitize_text_field((string) $request->get_param('current_persona'));
		$session_context = WebsiteIntelligence::get_session_context($session_key);
		$conversation_context = ConversationMemory::get_context($conversation_key !== '' ? $conversation_key : $session_key);

		if ($page_context === [] && $page_type !== '' && $post_id > 0) {
			$page_context = ContextStore::get_page_context($page_type, $post_id);
		}

		return [
			'session_key'          => $session_key,
			'conversation_key'     => $conversation_key !== '' ? $conversation_key : $session_key,
			'page_type'            => $page_type,
			'post_id'              => $post_id,
			'page_context'         => $page_context,
			'current_persona'      => $client_persona !== '' ? $client_persona : (string) ($session_context['current_persona'] ?? 'general_browser'),
			'visited_tours'        => (array) ($session_context['visited_tours'] ?? []),
			'visited_destinations' => (array) ($session_context['visited_destinations'] ?? []),
			'visited_tour_titles'  => (array) ($session_context['visited_tour_titles'] ?? []),
			'visited_destination_titles' => (array) ($session_context['visited_destination_titles'] ?? []),
			'stage'                => (string) ($session_context['stage'] ?? ''),
			'journey_stage'        => (string) ($session_context['journey_stage'] ?? ''),
			'intent_score'         => (int) ($session_context['intent_score'] ?? 0),
			'intent_vector'        => (array) ($session_context['intent_vector'] ?? []),
			'signal_counts'        => (array) ($session_context['signal_counts'] ?? []),
			'last_recommendation'  => (string) ($session_context['last_recommendation'] ?? sanitize_text_field((string) $request->get_param('recommendation_key'))),
			'visitor'              => (array) ($session_context['visitor'] ?? []),
			'conversation_history' => (array) ($conversation_context['history'] ?? []),
			'conversation_message_count' => (int) ($conversation_context['message_count'] ?? 0),
			'conversation_summary' => (string) ($conversation_context['summary'] ?? ''),
			'conversation_tours'   => (array) ($conversation_context['mentioned_tours'] ?? []),
			'conversation_preferences' => (array) ($conversation_context['preferences'] ?? []),
		];
	}

	/**
	 * @param array<string, mixed> $grounding
	 * @return array<int, string>
	 */
	private function extract_grounding_titles(array $grounding): array
	{
		$titles = [];

		foreach ((array) ($grounding['records'] ?? []) as $record) {
			if (! is_array($record)) {
				continue;
			}

			$title = (string) ($record['title'] ?? '');

			if ($title !== '') {
				$titles[] = $title;
			}
		}

		return array_values(array_unique($titles));
	}

	private function strip_ui_blocks(string $reply): string
	{
		return trim((string) preg_replace('/---ui-component---\s*[\s\S]*?\s*---end-ui---/', '', $reply));
	}

	/**
	 * @param array<string, mixed> $grounding
	 */
	private function enrich_chat_ui_blocks(string $reply, array $grounding): string
	{
		return (string) preg_replace_callback(
			'/---ui-component---\s*([\s\S]*?)\s*---end-ui---/',
			function (array $matches) use ($grounding): string {
				$decoded = json_decode((string) ($matches[1] ?? ''), true);

				if (! is_array($decoded)) {
					return (string) $matches[0];
				}

				$component = (string) ($decoded['component'] ?? '');
				$props = is_array($decoded['props'] ?? null) ? $decoded['props'] : [];

				if ($component === 'tour-highlight-card' && empty($props['image_url'])) {
					$image_url = $this->find_component_image_url($props, $grounding);

					if ($image_url !== '') {
						$decoded['props']['image_url'] = $image_url;
					}
				}

				return "---ui-component---\n" . wp_json_encode($decoded) . "\n---end-ui---";
			},
			$reply
		);
	}

	/**
	 * @param array<string, mixed> $props
	 * @param array<string, mixed> $grounding
	 */
	private function find_component_image_url(array $props, array $grounding): string
	{
		$title = strtolower(trim((string) ($props['title'] ?? '')));
		$cta_link = (string) ($props['cta_link'] ?? '');

		foreach ((array) ($grounding['records'] ?? []) as $record) {
			if (! is_array($record)) {
				continue;
			}

			$record_title = strtolower(trim((string) ($record['title'] ?? '')));
			$record_url = (string) ($record['url'] ?? '');
			$image_url = (string) ($record['image_url'] ?? '');

			if ($image_url === '') {
				continue;
			}

			if (($title !== '' && $record_title === $title) || ($cta_link !== '' && $record_url === $cta_link)) {
				return $image_url;
			}
		}

		return '';
	}

	public function handle_event(WP_REST_Request $request): WP_REST_Response
	{
		return new WP_REST_Response(WebsiteIntelligence::record_event($request));
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_identify_visitor(WP_REST_Request $request)
	{
		$result = VisitorProfileManager::identify(
			[
				'session_key'   => $request->get_param('session_key'),
				'page_url'      => $request->get_param('page_url'),
				'consent_state' => $request->get_param('consent_state'),
				'fingerprint'   => $request->get_param('fingerprint'),
				'visitor_api'   => $request->get_param('visitor_api'),
			]
		);

		if (is_wp_error($result)) {
			return $result;
		}

		return new WP_REST_Response($result);
	}

	public function handle_visitor_action(WP_REST_Request $request): WP_REST_Response
	{
		return new WP_REST_Response(WebsiteIntelligence::record_event($request));
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_create_lead(WP_REST_Request $request)
	{
		$validation_error = LeadManager::validate_public_submission($request);

		if ($validation_error instanceof WP_Error) {
			return $validation_error;
		}

		$lead_id = LeadManager::create_lead(
			[
				'full_name'       => $request->get_param('full_name') ?? $request->get_param('name'),
				'email'           => $request->get_param('email'),
				'phone'           => $request->get_param('phone'),
				'country'         => $request->get_param('country'),
				'source'          => $request->get_param('source'),
				'tour_interest'   => $request->get_param('tour_interest') ?? $request->get_param('tour_id'),
				'guest_count'     => $request->get_param('guest_count'),
				'travel_dates'    => $request->get_param('travel_dates'),
				'message'         => $request->get_param('message'),
				'internal_notes'  => $request->get_param('internal_notes'),
				'follow_up_date'  => $request->get_param('follow_up_date'),
				'origin_url'      => $request->get_param('origin_url'),
				'contact_consent' => $request->get_param('contact_consent'),
				'session_key'     => $request->get_param('session_key'),
				'visitor_profile_id' => $request->get_param('visitor_profile_id'),
			]
		);

		if (is_wp_error($lead_id)) {
			return $lead_id;
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'lead_id' => $lead_id,
				'status'  => __('Lead captured successfully.', 'twostars-platform'),
			],
			201
		);
	}

	public function handle_booking_availability(WP_REST_Request $request): WP_REST_Response
	{
		$tour_id = absint($request->get_param('tour_id'));
		$days = absint($request->get_param('days'));
		$start = sanitize_text_field((string) ($request->get_param('start') ?: $request->get_param('start_date')));

		return new WP_REST_Response(
			[
				'success' => true,
				'dates'   => BookingManager::get_availability_window($tour_id, $days > 0 ? $days : 18, $start),
			]
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_create_booking(WP_REST_Request $request)
	{
		$validation_error = LeadManager::validate_public_submission($request);

		if ($validation_error instanceof WP_Error) {
			return $validation_error;
		}

		$booking = BookingManager::create_booking(
			[
				'full_name'       => $request->get_param('full_name') ?? $request->get_param('name'),
				'email'           => $request->get_param('email'),
				'phone'           => $request->get_param('phone'),
				'country'         => $request->get_param('country'),
				'tour_id'         => $request->get_param('tour_id'),
				'guest_count'     => $request->get_param('guest_count'),
				'date'            => $request->get_param('date'),
				'time'            => $request->get_param('time'),
				'pickup_location' => $request->get_param('pickup_location'),
				'notes'           => $request->get_param('notes') ?? $request->get_param('message'),
				'origin_url'      => $request->get_param('origin_url'),
				'session_key'     => $request->get_param('session_key'),
				'visitor_profile_id' => $request->get_param('visitor_profile_id'),
			]
		);

		if (is_wp_error($booking)) {
			return $booking;
		}

		return new WP_REST_Response(
			[
				'success'          => true,
				'booking_id'       => (int) ($booking['booking_id'] ?? 0),
				'lead_id'          => (int) ($booking['lead_id'] ?? 0),
				'admin_notified'   => ! empty($booking['admin_notified']),
				'status'           => (string) ($booking['booking_message'] ?? __('Booking saved successfully.', 'twostars-platform')),
			],
			201
		);
	}

	public function handle_admin_visitors_list(WP_REST_Request $request): WP_REST_Response
	{
		return new WP_REST_Response(
			[
				'success'  => true,
				'visitors' => VisitorProfileManager::get_visitors(
					[
						'country' => $request->get_param('country'),
						'city'    => $request->get_param('city'),
						'status'  => $request->get_param('status'),
						'type'    => $request->get_param('type'),
						'linked'  => $request->get_param('linked'),
					]
				),
			]
		);
	}

	public function handle_admin_visitors_summary(WP_REST_Request $request): WP_REST_Response
	{
		return new WP_REST_Response(
			[
				'success' => true,
				'summary' => VisitorProfileManager::get_dashboard_summary(),
			]
		);
	}

	public function handle_admin_visitors_map(WP_REST_Request $request): WP_REST_Response
	{
		return new WP_REST_Response(
			[
				'success' => true,
				'points'  => VisitorProfileManager::get_map_points(
					[
						'country' => $request->get_param('country'),
						'city'    => $request->get_param('city'),
						'status'  => $request->get_param('status'),
						'type'    => $request->get_param('type'),
						'linked'  => $request->get_param('linked'),
					]
				),
			]
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_admin_visitors_profile(WP_REST_Request $request)
	{
		$profile = VisitorProfileManager::get_profile_detail(absint($request['id']));

		if (! $profile) {
			return new WP_Error(
				'twostars_visitor_not_found',
				__('That visitor profile could not be found.', 'twostars-platform'),
				['status' => 404]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'profile' => $profile,
			]
		);
	}
}
