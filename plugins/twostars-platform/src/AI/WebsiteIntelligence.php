<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Visitors\VisitorProfileManager;
use WP_REST_Request;

final class WebsiteIntelligence implements Service
{
	private const SCHEMA_OPTION = 'twostars_ai_schema_version';
	private const SCHEMA_VERSION = '4';

	public function register(): void
	{
		add_action('init', [self::class, 'maybe_install']);
		add_action('admin_menu', [$this, 'register_admin_page']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
	}

	public static function maybe_install(): void
	{
		if (get_option(self::SCHEMA_OPTION) === self::SCHEMA_VERSION) {
			return;
		}

		self::install();
		update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION);
	}

	public static function install(): void
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$sessions_table  = self::get_sessions_table_name();
		$events_table    = self::get_events_table_name();

		dbDelta(
			"CREATE TABLE {$sessions_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				session_key varchar(64) NOT NULL,
				visitor_profile_id bigint(20) unsigned NOT NULL DEFAULT 0,
				started_at datetime NOT NULL,
				last_seen_at datetime NOT NULL,
				first_url text NULL,
				last_url text NULL,
				page_views int unsigned NOT NULL DEFAULT 0,
				intent_score int unsigned NOT NULL DEFAULT 0,
				prompt_count int unsigned NOT NULL DEFAULT 0,
				lead_created tinyint(1) NOT NULL DEFAULT 0,
				device_type varchar(20) NULL,
				referrer varchar(191) NULL,
				intent_vector longtext NULL,
				signal_counts longtext NULL,
				current_persona varchar(50) NULL,
				visited_tours longtext NULL,
				visited_destinations longtext NULL,
				visitor_consent_state varchar(20) NULL,
				visitor_consent_updated_at datetime NULL,
				pricing_interest_count int unsigned NOT NULL DEFAULT 0,
				inclusion_interest_count int unsigned NOT NULL DEFAULT 0,
				last_tour_id bigint(20) unsigned NOT NULL DEFAULT 0,
				last_compared_tours longtext NULL,
				tour_switch_count int unsigned NOT NULL DEFAULT 0,
				last_recommendation varchar(100) NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY session_key (session_key),
				KEY visitor_profile_id (visitor_profile_id)
			) {$charset_collate};"
		);

		dbDelta(
			"CREATE TABLE {$events_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				session_key varchar(64) NOT NULL,
				visitor_profile_id bigint(20) unsigned NOT NULL DEFAULT 0,
				event_name varchar(100) NOT NULL,
				page_type varchar(50) NULL,
				page_url text NULL,
				score_delta int NOT NULL DEFAULT 0,
				payload longtext NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY session_key (session_key),
				KEY visitor_profile_id (visitor_profile_id),
				KEY event_name (event_name)
			) {$charset_collate};"
		);
	}

	public function enqueue_admin_assets(string $hook_suffix): void
	{
		if ($hook_suffix !== 'settings_page_twostars-website-intelligence') {
			return;
		}

		wp_enqueue_style(
			'twostars-platform-admin-crm',
			TWOSTARS_PLATFORM_URL . 'assets/css/admin-crm.css',
			[],
			TWOSTARS_PLATFORM_VERSION
		);
	}

	public function register_admin_page(): void
	{
		add_submenu_page(
			'options-general.php',
			__('Website Intelligence', 'twostars-platform'),
			__('Website Intelligence', 'twostars-platform'),
			'manage_options',
			'twostars-website-intelligence',
			[$this, 'render_admin_page']
		);
	}

	public function render_admin_page(): void
	{
		global $wpdb;

		$config          = self::get_config();
		$sessions_table  = self::get_sessions_table_name();
		$session_search  = sanitize_text_field((string) ($_GET['session_search'] ?? ''));
		$stage_filter    = sanitize_text_field((string) ($_GET['stage'] ?? ''));
		$event_filter    = sanitize_text_field((string) ($_GET['event_name'] ?? ''));
		$selected_key    = sanitize_text_field((string) ($_GET['session_key'] ?? ''));
		$total_sessions  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$sessions_table}");
		$high_intent     = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sessions_table} WHERE intent_score >= %d", (int) $config['high_intent_score']));
		$lead_sessions   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$sessions_table} WHERE lead_created = 1");
		$event_totals    = $this->get_event_totals();
		$filtered_events = $this->get_filtered_events($event_filter, $selected_key);
		$filtered_sessions = $this->get_filtered_sessions($stage_filter, $session_search);
		$selected_session = $selected_key !== '' ? $this->get_session($selected_key) : null;
		$selected_timeline = $selected_key !== '' ? $this->get_session_timeline($selected_key) : [];
		?>
		<div class="wrap twostars-crm-dashboard">
			<h1><?php esc_html_e('Website Intelligence', 'twostars-platform'); ?></h1>
			<div class="twostars-crm-cards">
				<div class="twostars-crm-card"><span><?php esc_html_e('Tracked Sessions', 'twostars-platform'); ?></span><strong><?php echo esc_html((string) $total_sessions); ?></strong></div>
				<div class="twostars-crm-card"><span><?php esc_html_e('High Intent Sessions', 'twostars-platform'); ?></span><strong><?php echo esc_html((string) $high_intent); ?></strong></div>
				<div class="twostars-crm-card"><span><?php esc_html_e('Lead Sessions', 'twostars-platform'); ?></span><strong><?php echo esc_html((string) $lead_sessions); ?></strong></div>
				<div class="twostars-crm-card"><span><?php esc_html_e('Prompt Threshold', 'twostars-platform'); ?></span><strong><?php echo esc_html((string) $config['prompt_min_score']); ?></strong></div>
				<div class="twostars-crm-card"><span><?php esc_html_e('Max Prompts / Session', 'twostars-platform'); ?></span><strong><?php echo esc_html((string) $config['max_prompts_per_session']); ?></strong></div>
			</div>

			<form method="get" class="twostars-crm-panel twostars-crm-filters">
				<input type="hidden" name="page" value="twostars-website-intelligence">
				<div class="twostars-crm-filter-grid">
					<label>
						<span><?php esc_html_e('Session Search', 'twostars-platform'); ?></span>
						<input type="text" name="session_search" value="<?php echo esc_attr($session_search); ?>" placeholder="<?php esc_attr_e('Session key or URL', 'twostars-platform'); ?>">
					</label>
					<label>
						<span><?php esc_html_e('Stage', 'twostars-platform'); ?></span>
						<select name="stage">
							<option value=""><?php esc_html_e('All stages', 'twostars-platform'); ?></option>
							<option value="browsing" <?php selected($stage_filter, 'browsing'); ?>><?php esc_html_e('Browsing', 'twostars-platform'); ?></option>
							<option value="considering" <?php selected($stage_filter, 'considering'); ?>><?php esc_html_e('Considering', 'twostars-platform'); ?></option>
							<option value="high_intent" <?php selected($stage_filter, 'high_intent'); ?>><?php esc_html_e('High Intent', 'twostars-platform'); ?></option>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Event', 'twostars-platform'); ?></span>
						<select name="event_name">
							<option value=""><?php esc_html_e('All events', 'twostars-platform'); ?></option>
							<?php foreach ($event_totals as $event_total) : ?>
								<option value="<?php echo esc_attr((string) $event_total->event_name); ?>" <?php selected($event_filter, (string) $event_total->event_name); ?>>
									<?php echo esc_html((string) $event_total->event_name); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
				<p><button type="submit" class="button button-primary"><?php esc_html_e('Apply Filters', 'twostars-platform'); ?></button></p>
			</form>

			<div class="twostars-crm-panels">
				<section class="twostars-crm-panel">
					<h2><?php esc_html_e('Sessions', 'twostars-platform'); ?></h2>
					<table class="widefat striped twostars-admin-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Session', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Last URL', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Intent', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Stage', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Persona', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Prompts', 'twostars-platform'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($filtered_sessions as $session) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url(add_query_arg(['page' => 'twostars-website-intelligence', 'session_key' => (string) $session->session_key], admin_url('options-general.php'))); ?>">
											<?php echo esc_html((string) $session->session_key); ?>
										</a>
										</td>
										<td><?php echo esc_html((string) $session->last_url); ?></td>
										<td><?php echo esc_html((string) $session->intent_score); ?></td>
										<td><?php echo esc_html(self::get_stage((int) $session->intent_score)); ?></td>
										<td><?php echo esc_html(self::get_persona_label((string) ($session->current_persona ?? 'general_browser'))); ?></td>
										<td><?php echo esc_html((string) $session->prompt_count); ?></td>
									</tr>
								<?php endforeach; ?>
						</tbody>
					</table>
				</section>

				<section class="twostars-crm-panel">
					<h2><?php esc_html_e('Recent Events', 'twostars-platform'); ?></h2>
					<table class="widefat striped twostars-admin-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Time', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Session', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Event', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Page Type', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Score', 'twostars-platform'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($filtered_events as $event) : ?>
								<tr>
									<td><?php echo esc_html((string) $event->created_at); ?></td>
									<td><?php echo esc_html((string) $event->session_key); ?></td>
									<td><?php echo esc_html((string) $event->event_name); ?></td>
									<td><?php echo esc_html((string) $event->page_type); ?></td>
									<td><?php echo esc_html((string) $event->score_delta); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			</div>

			<?php if ($selected_session) : ?>
				<section class="twostars-crm-panel">
					<h2><?php esc_html_e('Session Detail', 'twostars-platform'); ?></h2>
					<div class="twostars-crm-detail-grid">
						<div><strong><?php esc_html_e('Session Key', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) $selected_session->session_key); ?></div>
						<div><strong><?php esc_html_e('Intent Score', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) $selected_session->intent_score); ?></div>
						<div><strong><?php esc_html_e('Stage', 'twostars-platform'); ?>:</strong> <?php echo esc_html(self::get_stage((int) $selected_session->intent_score)); ?></div>
						<div><strong><?php esc_html_e('Persona', 'twostars-platform'); ?>:</strong> <?php echo esc_html(self::get_persona_label((string) ($selected_session->current_persona ?? 'general_browser'))); ?></div>
						<div><strong><?php esc_html_e('Last Recommendation', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) $selected_session->last_recommendation); ?></div>
						<div><strong><?php esc_html_e('First URL', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) $selected_session->first_url); ?></div>
						<div><strong><?php esc_html_e('Last URL', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) $selected_session->last_url); ?></div>
							<div><strong><?php esc_html_e('Visited Tours', 'twostars-platform'); ?>:</strong> <?php echo esc_html(implode(', ', self::get_titles_from_ids(self::decode_id_list((string) ($selected_session->visited_tours ?? ''))))); ?></div>
							<div><strong><?php esc_html_e('Visited Destinations', 'twostars-platform'); ?>:</strong> <?php echo esc_html(implode(', ', self::get_titles_from_ids(self::decode_id_list((string) ($selected_session->visited_destinations ?? ''))))); ?></div>
							<div><strong><?php esc_html_e('Intent Vector', 'twostars-platform'); ?>:</strong> <?php echo esc_html(wp_json_encode(self::normalize_intent_vector(self::decode_assoc_json((string) ($selected_session->intent_vector ?? ''))))); ?></div>
							<div><strong><?php esc_html_e('Signal Counts', 'twostars-platform'); ?>:</strong> <?php echo esc_html(wp_json_encode(self::normalize_signal_counts(self::decode_assoc_json((string) ($selected_session->signal_counts ?? ''))))); ?></div>
							<div><strong><?php esc_html_e('Pricing Signals', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) ($selected_session->pricing_interest_count ?? 0)); ?></div>
							<div><strong><?php esc_html_e('Inclusion Signals', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) ($selected_session->inclusion_interest_count ?? 0)); ?></div>
						</div>

					<h3><?php esc_html_e('Session Timeline', 'twostars-platform'); ?></h3>
					<table class="widefat striped twostars-admin-table">
						<thead>
							<tr>
								<th><?php esc_html_e('Time', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Event', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Page Type', 'twostars-platform'); ?></th>
								<th><?php esc_html_e('Score', 'twostars-platform'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($selected_timeline as $timeline_event) : ?>
								<tr>
									<td><?php echo esc_html((string) $timeline_event->created_at); ?></td>
									<td><?php echo esc_html((string) $timeline_event->event_name); ?></td>
									<td><?php echo esc_html((string) $timeline_event->page_type); ?></td>
									<td><?php echo esc_html((string) $timeline_event->score_delta); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<h3><?php esc_html_e('Chat History', 'twostars-platform'); ?></h3>
					<?php $conversation_threads = ConversationMemory::get_threads_for_context((string) $selected_session->session_key, 0, 10); ?>
					<?php if ($conversation_threads === []) : ?>
						<p><?php esc_html_e('No stored chatbot conversation for this session yet.', 'twostars-platform'); ?></p>
					<?php else : ?>
						<div class="twostars-crm-chat-history">
							<?php foreach ($conversation_threads as $thread) : ?>
								<?php $thread_history = is_array($thread['history'] ?? null) ? $thread['history'] : []; ?>
								<article class="twostars-crm-chat-thread">
									<header class="twostars-crm-chat-thread__head">
										<div>
											<strong><?php echo esc_html((string) ($thread['title'] ?? __('Two Stars planning', 'twostars-platform'))); ?></strong>
											<p><?php echo esc_html((string) ($thread['preview'] ?? '')); ?></p>
										</div>
										<small><?php echo esc_html(sprintf(_n('%d message', '%d messages', (int) ($thread['message_count'] ?? 0), 'twostars-platform'), (int) ($thread['message_count'] ?? 0))); ?></small>
									</header>
									<div class="twostars-crm-chat-thread__messages">
										<?php foreach ($thread_history as $message) : ?>
											<?php $is_assistant = ((string) ($message['role'] ?? 'user')) === 'assistant'; ?>
											<div class="twostars-crm-chat-thread__message<?php echo $is_assistant ? ' is-assistant' : ' is-user'; ?>">
												<span class="twostars-crm-chat-thread__role"><?php echo esc_html($is_assistant ? __('Guide', 'twostars-platform') : __('Guest', 'twostars-platform')); ?></span>
												<div class="twostars-crm-chat-thread__bubble"><?php echo esc_html((string) ($message['content'] ?? '')); ?></div>
											</div>
										<?php endforeach; ?>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function record_event(WP_REST_Request $request): array
	{
		global $wpdb;

		$session_key = sanitize_text_field((string) $request->get_param('session_key'));
		$event_name  = sanitize_text_field((string) $request->get_param('event'));
		$page_type   = sanitize_text_field((string) $request->get_param('page_type'));
		$page_url    = esc_url_raw((string) $request->get_param('page_url'));
		$payload     = $request->get_param('payload');
		$payload     = is_array($payload) ? $payload : [];
		$post_id     = absint($payload['post_id'] ?? 0);
		$created_at  = current_time('mysql');

		if ($session_key === '' || $event_name === '') {
			return [
				'success' => false,
				'message' => __('Missing session or event data.', 'twostars-platform'),
			];
		}

		$score_delta    = self::get_score_delta($event_name, $page_type);
		$sessions_table = self::get_sessions_table_name();
		$events_table   = self::get_events_table_name();
		$existing       = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sessions_table} WHERE session_key = %s", $session_key), ARRAY_A);
		$session        = is_array($existing) ? $existing : [];
		$visitor_profile_id = absint($payload['visitor_profile_id'] ?? ($session['visitor_profile_id'] ?? 0));
		$page_views     = (int) ($session['page_views'] ?? 0);
		$prompt_count   = (int) ($session['prompt_count'] ?? 0);
		$lead_created   = (int) ($session['lead_created'] ?? 0);
		$intent_score   = (int) ($session['intent_score'] ?? 0) + $score_delta;
		$pricing_interest_count   = (int) ($session['pricing_interest_count'] ?? 0);
		$inclusion_interest_count = (int) ($session['inclusion_interest_count'] ?? 0);
		$last_tour_id             = absint($session['last_tour_id'] ?? 0);
		$tour_switch_count        = (int) ($session['tour_switch_count'] ?? 0);
		$visited_tours            = self::decode_id_list((string) ($session['visited_tours'] ?? ''));
		$visited_destinations     = self::decode_id_list((string) ($session['visited_destinations'] ?? ''));
		$last_compared_tours      = self::decode_id_list((string) ($session['last_compared_tours'] ?? ''));
		$intent_vector            = self::normalize_intent_vector(self::decode_assoc_json((string) ($session['intent_vector'] ?? '')));
		$signal_counts            = self::normalize_signal_counts(self::decode_assoc_json((string) ($session['signal_counts'] ?? '')));
		$recent_events            = self::get_recent_event_names($session_key, 6);
		$consent_state            = sanitize_text_field((string) ($payload['consent_state'] ?? ($session['visitor_consent_state'] ?? '')));
		$consent_updated_at       = $consent_state !== '' ? current_time('mysql') : (string) ($session['visitor_consent_updated_at'] ?? '');

		if ($event_name === 'page_view') {
			$page_views++;
		}

		if ($event_name === 'prompt_shown') {
			$prompt_count++;
		}

		if ($event_name === 'lead_created') {
			$lead_created = 1;
		}

		if (! empty($payload['visitor_profile_id'])) {
			$visitor_profile_id = absint($payload['visitor_profile_id']);
		}

		if ($event_name === 'pricing_interest') {
			$pricing_interest_count++;
		}

		if ($event_name === 'inclusion_interest') {
			$inclusion_interest_count++;
		}

		$signal_counts = self::increment_signal_count($signal_counts, $event_name);
		$intent_vector = self::apply_intent_vector_delta(
			$intent_vector,
			self::get_intent_vector_delta($event_name, $page_type, $payload)
		);
		$recent_events[] = $event_name;

		if ($event_name === 'page_view' && $page_type === 'tour' && $post_id > 0) {
			$visited_tours = self::add_id_to_list($visited_tours, $post_id);

			if ($last_tour_id > 0 && $last_tour_id !== $post_id) {
				$tour_switch_count++;
				$last_compared_tours = [$last_tour_id, $post_id];
			}

			$last_tour_id = $post_id;
		}

		if ($event_name === 'page_view' && $page_type === 'destination' && $post_id > 0) {
			$visited_destinations = self::add_id_to_list($visited_destinations, $post_id);
		}

		$session_state = [
			'intent_score'             => $intent_score,
			'intent_vector'            => $intent_vector,
			'signal_counts'            => $signal_counts,
			'prompt_count'             => $prompt_count,
			'lead_created'             => (bool) $lead_created,
			'pricing_interest_count'   => $pricing_interest_count,
				'inclusion_interest_count' => $inclusion_interest_count,
				'visited_tours'            => $visited_tours,
				'visited_destinations'     => $visited_destinations,
			'last_tour_id'             => $last_tour_id,
			'last_compared_tours'      => $last_compared_tours,
			'tour_switch_count'        => $tour_switch_count,
		];
		$session_state['current_persona'] = self::resolve_persona($session_state);
		$journey_state = JourneyOrchestrator::build_state($session_state, $recent_events);
		$intervention_state = InterventionTimingEngine::predict($session_state, $recent_events, (string) ($journey_state['stage'] ?? 'discovery'));

		$recommendation = self::build_recommendation(
			$page_type,
			$page_url,
			$session_state,
			ContextStore::get_page_context($page_type, $post_id),
			$journey_state,
			$intervention_state
		);

		if (! empty($session)) {
				$wpdb->update(
					$sessions_table,
					[
						'visitor_profile_id'   => $visitor_profile_id,
						'last_seen_at'        => $created_at,
						'last_url'            => $page_url,
						'page_views'          => $page_views,
						'intent_score'        => $intent_score,
						'intent_vector'       => wp_json_encode($intent_vector),
						'signal_counts'       => wp_json_encode($signal_counts),
						'prompt_count'        => $prompt_count,
						'lead_created'        => $lead_created,
						'current_persona'     => (string) $session_state['current_persona'],
					'visited_tours'       => wp_json_encode($visited_tours),
					'visited_destinations' => wp_json_encode($visited_destinations),
					'pricing_interest_count' => $pricing_interest_count,
					'inclusion_interest_count' => $inclusion_interest_count,
					'last_tour_id'        => $last_tour_id,
						'last_compared_tours' => wp_json_encode($last_compared_tours),
						'tour_switch_count'   => $tour_switch_count,
						'last_recommendation' => (string) ($recommendation['key'] ?? ''),
						'visitor_consent_state' => $consent_state !== '' ? $consent_state : null,
						'visitor_consent_updated_at' => $consent_updated_at !== '' ? $consent_updated_at : null,
				],
				['session_key' => $session_key]
			);
		} else {
			$wpdb->insert(
				$sessions_table,
					[
						'session_key'         => $session_key,
						'visitor_profile_id'  => $visitor_profile_id,
						'started_at'          => $created_at,
						'last_seen_at'        => $created_at,
						'first_url'           => $page_url,
						'last_url'            => $page_url,
						'page_views'          => $page_views,
						'intent_score'        => $intent_score,
						'intent_vector'       => wp_json_encode($intent_vector),
						'signal_counts'       => wp_json_encode($signal_counts),
						'prompt_count'        => $prompt_count,
						'lead_created'        => $lead_created,
						'device_type'         => sanitize_text_field((string) ($payload['device_type'] ?? '')),
					'referrer'            => sanitize_text_field((string) ($payload['referrer'] ?? '')),
					'current_persona'     => (string) $session_state['current_persona'],
					'visited_tours'       => wp_json_encode($visited_tours),
					'visited_destinations' => wp_json_encode($visited_destinations),
					'pricing_interest_count' => $pricing_interest_count,
					'inclusion_interest_count' => $inclusion_interest_count,
					'last_tour_id'        => $last_tour_id,
						'last_compared_tours' => wp_json_encode($last_compared_tours),
						'tour_switch_count'   => $tour_switch_count,
						'last_recommendation' => (string) ($recommendation['key'] ?? ''),
						'visitor_consent_state' => $consent_state !== '' ? $consent_state : null,
						'visitor_consent_updated_at' => $consent_updated_at !== '' ? $consent_updated_at : null,
				]
			);
		}

		$wpdb->insert(
			$events_table,
			[
				'session_key' => $session_key,
				'visitor_profile_id' => $visitor_profile_id,
				'event_name'  => $event_name,
				'page_type'   => $page_type,
				'page_url'    => $page_url,
				'score_delta' => $score_delta,
				'payload'     => wp_json_encode($payload),
				'created_at'  => $created_at,
			]
		);

		if ($visitor_profile_id > 0) {
			VisitorProfileManager::sync_profile_after_event($session_key);
		}

			return [
				'success'        => true,
				'session_key'    => $session_key,
				'event'          => $event_name,
				'intent_score'   => $intent_score,
			'intent_vector'  => $intent_vector,
			'signal_counts'  => $signal_counts,
			'stage'          => self::get_stage($intent_score),
			'journey_stage'  => (string) ($journey_state['stage'] ?? 'discovery'),
			'intervention'   => $intervention_state,
			'persona'        => (string) $session_state['current_persona'],
			'visited_tours'  => $visited_tours,
			'visited_destinations' => $visited_destinations,
			'visited_tour_titles' => self::get_titles_from_ids($visited_tours),
			'visited_destination_titles' => self::get_titles_from_ids($visited_destinations),
			'last_recommendation' => (string) ($recommendation['key'] ?? ''),
			'recommendation' => $recommendation,
			'visitor_profile_id' => $visitor_profile_id,
			'consent_state' => $consent_state !== '' ? $consent_state : (string) ($session['visitor_consent_state'] ?? ''),
			'visitor' => $visitor_profile_id > 0 ? VisitorProfileManager::get_session_visitor_context($session_key) : [],
		];
	}

	public static function get_sessions_table_name(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'twostars_ai_sessions';
	}

	public static function get_events_table_name(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'twostars_ai_events';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_session_context(string $session_key): array
	{
		global $wpdb;

		if ($session_key === '') {
			return [];
		}

		$sessions_table = self::get_sessions_table_name();
		$session        = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sessions_table} WHERE session_key = %s", $session_key), ARRAY_A);

		if (! is_array($session)) {
			return [];
		}

		$visited_tours        = self::decode_id_list((string) ($session['visited_tours'] ?? ''));
		$visited_destinations = self::decode_id_list((string) ($session['visited_destinations'] ?? ''));

		return [
			'session_key'               => $session_key,
			'visitor_profile_id'        => (int) ($session['visitor_profile_id'] ?? 0),
			'intent_score'              => (int) ($session['intent_score'] ?? 0),
			'intent_vector'             => self::normalize_intent_vector(self::decode_assoc_json((string) ($session['intent_vector'] ?? ''))),
			'signal_counts'             => self::normalize_signal_counts(self::decode_assoc_json((string) ($session['signal_counts'] ?? ''))),
			'stage'                     => self::get_stage((int) ($session['intent_score'] ?? 0)),
			'journey_stage'             => (string) (JourneyOrchestrator::build_state(
				[
					'intent_vector' => self::normalize_intent_vector(self::decode_assoc_json((string) ($session['intent_vector'] ?? ''))),
					'signal_counts' => self::normalize_signal_counts(self::decode_assoc_json((string) ($session['signal_counts'] ?? ''))),
					'lead_created'  => ! empty($session['lead_created']),
				]
			)['stage'] ?? 'discovery'),
			'current_persona'           => (string) ($session['current_persona'] ?? 'general_browser'),
				'visited_tours'             => $visited_tours,
				'visited_destinations'      => $visited_destinations,
				'visited_tour_titles'       => self::get_titles_from_ids($visited_tours),
			'visited_destination_titles' => self::get_titles_from_ids($visited_destinations),
			'pricing_interest_count'    => (int) ($session['pricing_interest_count'] ?? 0),
			'inclusion_interest_count'  => (int) ($session['inclusion_interest_count'] ?? 0),
			'last_recommendation'       => (string) ($session['last_recommendation'] ?? ''),
			'last_compared_tours'       => self::decode_id_list((string) ($session['last_compared_tours'] ?? '')),
			'consent_state'             => (string) ($session['visitor_consent_state'] ?? ''),
			'visitor'                   => ! empty($session['visitor_profile_id']) ? VisitorProfileManager::get_profile_context_by_id((int) $session['visitor_profile_id']) : [],
		];
	}

	/**
	 * @return array<string, int>
	 */
	public static function get_config(): array
	{
		$settings = get_option('twostars_platform_settings', []);
		$settings = is_array($settings) ? $settings : [];

		return [
			'prompt_min_score'      => max(1, absint($settings['intelligence_prompt_min_score'] ?? 4)),
			'max_prompts_per_session' => max(1, absint($settings['intelligence_max_prompts_per_session'] ?? 2)),
			'high_intent_score'     => max(2, absint($settings['intelligence_high_intent_score'] ?? 8)),
		];
	}

	/**
	 * @return array<int, object>
	 */
	private function get_event_totals(): array
	{
		global $wpdb;

		$events_table = self::get_events_table_name();

		return (array) $wpdb->get_results("SELECT event_name, COUNT(*) AS total FROM {$events_table} GROUP BY event_name ORDER BY total DESC LIMIT 8");
	}

	/**
	 * @return array<int, object>
	 */
	private function get_filtered_sessions(string $stage_filter, string $session_search): array
	{
		global $wpdb;

		$sessions_table = self::get_sessions_table_name();
		$config         = self::get_config();
		$sql            = "SELECT session_key, last_url, intent_score, prompt_count, last_recommendation, current_persona FROM {$sessions_table} WHERE 1=1";
		$args           = [];

		if ($session_search !== '') {
			$sql   .= " AND (session_key LIKE %s OR last_url LIKE %s)";
			$like   = '%' . $wpdb->esc_like($session_search) . '%';
			$args[] = $like;
			$args[] = $like;
		}

		if ($stage_filter === 'browsing') {
			$sql   .= " AND intent_score < %d";
			$args[] = $config['prompt_min_score'];
		} elseif ($stage_filter === 'considering') {
			$sql   .= " AND intent_score >= %d AND intent_score < %d";
			$args[] = $config['prompt_min_score'];
			$args[] = $config['high_intent_score'];
		} elseif ($stage_filter === 'high_intent') {
			$sql   .= " AND intent_score >= %d";
			$args[] = $config['high_intent_score'];
		}

		$sql .= " ORDER BY last_seen_at DESC LIMIT 25";

		return $args ? (array) $wpdb->get_results($wpdb->prepare($sql, ...$args)) : (array) $wpdb->get_results($sql);
	}

	/**
	 * @return array<int, object>
	 */
	private function get_filtered_events(string $event_filter, string $session_key): array
	{
		global $wpdb;

		$events_table = self::get_events_table_name();
		$sql          = "SELECT created_at, session_key, event_name, page_type, score_delta FROM {$events_table} WHERE 1=1";
		$args         = [];

		if ($event_filter !== '') {
			$sql   .= " AND event_name = %s";
			$args[] = $event_filter;
		}

		if ($session_key !== '') {
			$sql   .= " AND session_key = %s";
			$args[] = $session_key;
		}

		$sql .= " ORDER BY created_at DESC LIMIT 40";

		return $args ? (array) $wpdb->get_results($wpdb->prepare($sql, ...$args)) : (array) $wpdb->get_results($sql);
	}

	private function get_session(string $session_key): ?object
	{
		global $wpdb;

		$sessions_table = self::get_sessions_table_name();
		$session        = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sessions_table} WHERE session_key = %s", $session_key));

		return is_object($session) ? $session : null;
	}

	/**
	 * @return array<int, object>
	 */
	private function get_session_timeline(string $session_key): array
	{
		global $wpdb;

		$events_table = self::get_events_table_name();

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT created_at, event_name, page_type, score_delta FROM {$events_table} WHERE session_key = %s ORDER BY created_at DESC LIMIT 50",
				$session_key
			)
		);
	}

	/**
	 * @return array<int, string>
	 */
	private static function get_recent_event_names(string $session_key, int $limit = 6): array
	{
		global $wpdb;

		if ($session_key === '') {
			return [];
		}

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT event_name FROM " . self::get_events_table_name() . " WHERE session_key = %s ORDER BY created_at DESC, id DESC LIMIT %d",
				$session_key,
				max(1, $limit)
			)
		);

		if (! is_array($rows)) {
			return [];
		}

		return array_values(
			array_filter(
				array_map(
					static fn($value): string => is_scalar($value) ? (string) $value : '',
					$rows
				),
				static fn(string $value): bool => $value !== ''
			)
		);
	}

	private static function get_score_delta(string $event_name, string $page_type): int
	{
		$scores = [
			'page_view'         => 1,
			'scroll_50'         => 1,
			'scroll_80'         => 2,
			'booking_click'     => 4,
			'chat_opened'       => 2,
			'chat_message'      => 2,
			'lead_form_open'    => 3,
			'lead_created'      => 10,
			'pricing_interest'  => 2,
			'inclusion_interest' => 2,
			'gallery_view'      => 1,
			'review_read'       => 1,
			'faq_expand'        => 1,
			'date_selector_open' => 3,
			'group_size_adjust' => 1,
			'availability_check' => 4,
			'comparison_add'    => 2,
			'comparison_view'   => 3,
			'tour_card_click'   => 2,
			'destination_card_click' => 1,
			'whatsapp_click'    => 5,
			'hero_cta_click'    => 2,
			'booking_started'   => 6,
			'booking_completed' => 15,
			'visitor_consent_granted' => 0,
			'visitor_consent_denied' => 0,
		];

		$delta = (int) ($scores[$event_name] ?? 0);

		if ($page_type === 'tour' && $event_name === 'page_view') {
			$delta += 1;
		}

		return $delta;
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, int>
	 */
	private static function get_intent_vector_delta(string $event_name, string $page_type, array $payload): array
	{
		$deltas = [
			'page_view' => ['research_depth' => 2, 'engagement_quality' => 1],
			'scroll_50' => ['research_depth' => 3, 'engagement_quality' => 2],
			'scroll_80' => ['research_depth' => 6, 'engagement_quality' => 4],
			'booking_click' => ['purchase_intent' => 14, 'urgency' => 10, 'confidence' => 4],
			'chat_opened' => ['purchase_intent' => 5, 'engagement_quality' => 4],
			'chat_message' => ['purchase_intent' => 6, 'research_depth' => 4, 'confidence' => 3],
			'lead_form_open' => ['purchase_intent' => 10, 'urgency' => 7, 'confidence' => 5],
			'lead_created' => ['purchase_intent' => 20, 'urgency' => 15, 'confidence' => 10],
			'pricing_interest' => ['purchase_intent' => 6, 'price_sensitivity' => 10],
			'inclusion_interest' => ['research_depth' => 6, 'confidence' => 5],
			'gallery_view' => ['research_depth' => 5, 'engagement_quality' => 7],
			'review_read' => ['research_depth' => 7, 'confidence' => 6],
			'faq_expand' => ['research_depth' => 6],
			'date_selector_open' => ['purchase_intent' => 12, 'urgency' => 15],
			'group_size_adjust' => ['research_depth' => 4, 'engagement_quality' => 3],
			'availability_check' => ['purchase_intent' => 16, 'urgency' => 16, 'confidence' => 5],
			'comparison_add' => ['research_depth' => 7, 'confidence' => 5],
			'comparison_view' => ['research_depth' => 12, 'confidence' => 10],
			'tour_card_click' => ['research_depth' => 4, 'purchase_intent' => 4],
			'destination_card_click' => ['research_depth' => 3],
			'whatsapp_click' => ['purchase_intent' => 18, 'urgency' => 12, 'confidence' => 8],
			'hero_cta_click' => ['purchase_intent' => 5, 'engagement_quality' => 4],
			'booking_started' => ['purchase_intent' => 18, 'urgency' => 18, 'confidence' => 8],
			'booking_completed' => ['purchase_intent' => 25, 'urgency' => 20, 'confidence' => 12],
		];

		$delta = $deltas[$event_name] ?? [];

		if ($event_name === 'page_view' && $page_type === 'tour') {
			$delta['purchase_intent'] = (int) ($delta['purchase_intent'] ?? 0) + 2;
		}

		if ($event_name === 'group_size_adjust') {
			$guest_count = absint($payload['guest_count'] ?? 0);

			if ($guest_count >= 4) {
				$delta['purchase_intent'] = (int) ($delta['purchase_intent'] ?? 0) + 4;
			}
		}

		return $delta;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function build_recommendation(string $page_type, string $page_url, array $session_state, array $context, array $journey_state = [], array $intervention_state = []): ?array
	{
		$config = self::get_config();
		$intent_score = (int) ($session_state['intent_score'] ?? 0);
		$prompt_count = (int) ($session_state['prompt_count'] ?? 0);
		$lead_created = ! empty($session_state['lead_created']);
		$persona      = (string) ($session_state['current_persona'] ?? 'general_browser');

		if ($lead_created || $prompt_count >= $config['max_prompts_per_session'] || $intent_score < $config['prompt_min_score']) {
			return null;
		}

		if (str_contains($page_url, '/booking') || str_contains($page_url, '/contact')) {
			return null;
		}

		if (! empty($intervention_state) && empty($intervention_state['ready']) && $persona === 'general_browser') {
			return null;
		}

		$journey_stage = (string) ($journey_state['stage'] ?? 'discovery');

		if ($persona === 'ready_to_book') {
			$tour_title = (string) ($context['title'] ?? '');
			$details    = self::format_tour_context($context);

				return [
					'key'       => 'ready_to_book_assist',
					'persona'   => $persona,
					'title'     => $tour_title !== ''
					? sprintf(__('Ready to plan %s?', 'twostars-platform'), $tour_title)
					: __('Ready to plan the booking details?', 'twostars-platform'),
					'message'   => $details !== ''
						? sprintf(__('I can help with inclusions, timing, and next steps. %s', 'twostars-platform'), $details)
						: __('I can help check inclusions, timing, and the next booking step without making you leave the page.', 'twostars-platform'),
					'assistant_intro' => $tour_title !== ''
						? sprintf(__('If you want to continue with %s, I can help confirm availability, inclusions, and the best next step.', 'twostars-platform'), $tour_title)
						: __('If you want to continue planning, I can help with inclusions, timing, and the final route shape.', 'twostars-platform'),
						'primary'   => ['label' => __('Check details with the guide', 'twostars-platform'), 'action' => 'open_assistant'],
						'secondary' => ['label' => __('Contact us', 'twostars-platform'), 'url' => home_url('/contact/')],
						'journey_stage' => $journey_stage,
					];
		}

		if ($persona === 'comparison_shopper') {
			$compared_titles = self::get_titles_from_ids(self::normalize_id_list($session_state['last_compared_tours'] ?? []));
			$comparison_copy = count($compared_titles) >= 2
				? sprintf(__('Would you like a quick comparison of %1$s and %2$s?', 'twostars-platform'), $compared_titles[0], $compared_titles[1])
				: __('Would you like help continuing your tour comparison?', 'twostars-platform');

				return [
					'key'       => 'tour_comparison_help',
					'persona'   => $persona,
					'title'     => __('Need help comparing Two Stars tours?', 'twostars-platform'),
					'message'   => $comparison_copy,
					'assistant_intro' => __('I can compare tours side by side and point out the strongest fit for your pace, pickup area, and preferred highlights.', 'twostars-platform'),
						'primary'   => ['label' => __('Compare with the guide', 'twostars-platform'), 'action' => 'open_assistant'],
						'secondary' => ['label' => __('Start planning', 'twostars-platform'), 'url' => home_url('/booking/')],
						'journey_stage' => $journey_stage,
					];
		}

		if ($persona === 'researcher') {
			$destination_title = (string) ($context['title'] ?? '');

				return [
					'key'       => 'destination_research_help',
					'persona'   => $persona,
					'title'     => __('Need help choosing the right route?', 'twostars-platform'),
					'message'   => $destination_title !== ''
						? sprintf(__('I can place %s in the right route, pickup plan, and tour shortlist.', 'twostars-platform'), $destination_title)
						: __('I can narrow the best route based on timing, travel pace, pickup area, and the kind of day you want.', 'twostars-platform'),
					'assistant_intro' => __('I can narrow the strongest tour or destination based on your timing, pickup area, and the type of experience you want.', 'twostars-platform'),
						'primary'   => ['label' => __('Get route advice', 'twostars-platform'), 'action' => 'open_assistant'],
						'secondary' => ['label' => __('Browse tours', 'twostars-platform'), 'url' => home_url('/tours/')],
						'journey_stage' => $journey_stage,
					];
		}

		if ($page_type === 'tour') {
			$tour_title = (string) ($context['title'] ?? '');

				return [
					'key'       => 'tour_pricing_help',
					'persona'   => $persona,
					'title'     => $tour_title !== ''
						? sprintf(__('Want help with %s?', 'twostars-platform'), $tour_title)
						: __('Want help with pricing or availability?', 'twostars-platform'),
					'message'   => __('We can guide you on dates, group fit, and the next booking step without interrupting your browsing.', 'twostars-platform'),
					'assistant_intro' => $tour_title !== ''
						? sprintf(__('If you want to continue with %s, I can help with timing, availability, private options, and whether it fits the kind of day tour you want.', 'twostars-platform'), $tour_title)
						: __('I can help with timing, availability, and whether this tour fits the kind of day experience you want.', 'twostars-platform'),
						'primary'   => ['label' => __('Ask the guide', 'twostars-platform'), 'action' => 'open_assistant'],
						'secondary' => ['label' => __('Contact us', 'twostars-platform'), 'url' => home_url('/contact/')],
						'journey_stage' => $journey_stage,
					];
		}

		if ($page_type === 'destination') {
				return [
					'key'       => 'destination_compare',
					'persona'   => $persona,
					'title'     => __('Need help choosing the right route?', 'twostars-platform'),
					'message'   => __('The guide can help compare destinations and suggest the best next page or inquiry step.', 'twostars-platform'),
					'assistant_intro' => __('I can help turn destination browsing into a practical route shortlist.', 'twostars-platform'),
						'primary'   => ['label' => __('Ask the guide', 'twostars-platform'), 'action' => 'open_assistant'],
						'secondary' => ['label' => __('Start planning', 'twostars-platform'), 'url' => home_url('/booking/')],
						'journey_stage' => $journey_stage,
					];
			}

		return [
			'key'       => 'generic_trip_help',
			'persona'   => $persona,
			'journey_stage' => $journey_stage,
			'title'     => __('Planning a Two Stars tour?', 'twostars-platform'),
			'message'   => __('Ask the guide a question or hand your details to the team when you are ready.', 'twostars-platform'),
			'assistant_intro' => __('I can help with tours, destinations, private options, and the best next planning step.', 'twostars-platform'),
			'primary'   => ['label' => __('Ask the guide', 'twostars-platform'), 'action' => 'open_assistant'],
			'secondary' => ['label' => __('Share trip details', 'twostars-platform'), 'url' => home_url('/contact/')],
		];
	}

	/**
	 */
	private static function resolve_persona(array $session_state): string
	{
		$intent_vector             = self::normalize_intent_vector($session_state['intent_vector'] ?? []);
		$signal_counts             = self::normalize_signal_counts($session_state['signal_counts'] ?? []);
		$visited_tours            = self::normalize_id_list($session_state['visited_tours'] ?? []);
		$visited_destinations     = self::normalize_id_list($session_state['visited_destinations'] ?? []);
		$pricing_interest_count   = (int) ($session_state['pricing_interest_count'] ?? 0);
		$inclusion_interest_count = (int) ($session_state['inclusion_interest_count'] ?? 0);
		$tour_switch_count        = (int) ($session_state['tour_switch_count'] ?? 0);

		if (
			$intent_vector['purchase_intent'] >= 55
			|| $intent_vector['urgency'] >= 45
			|| $signal_counts['availability_check'] >= 1
			|| $signal_counts['date_selector_open'] >= 1
			|| ($pricing_interest_count + $inclusion_interest_count) >= 2
		) {
			return 'ready_to_book';
		}

		if (
			$signal_counts['comparison_view'] >= 1
			|| $signal_counts['comparison_add'] >= 1
			|| ($tour_switch_count >= 1 && count($visited_tours) >= 2)
		) {
			return 'comparison_shopper';
		}

		if (
			$intent_vector['research_depth'] >= 35
			&& count($visited_destinations) >= 2
			&& count($visited_tours) === 0
		) {
			return 'researcher';
		}

		if (
			count($visited_destinations) >= 3
			&& count($visited_tours) === 0
		) {
			return 'researcher';
		}

		return 'general_browser';
	}

	/**
	 * @return array<int, int>
	 */
	private static function decode_id_list(string $value): array
	{
		if ($value === '') {
			return [];
		}

		$decoded = json_decode($value, true);

		if (! is_array($decoded)) {
			return [];
		}

		return array_values(
			array_filter(
				array_map('absint', $decoded),
				static fn(int $id): bool => $id > 0
			)
		);
	}

	/**
	 * @param mixed $value
	 * @return array<int, int>
	 */
	private static function normalize_id_list($value): array
	{
		if (is_array($value)) {
			return array_values(
				array_filter(
					array_map('absint', $value),
					static fn(int $id): bool => $id > 0
				)
			);
		}

		return self::decode_id_list((string) $value);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function decode_assoc_json(string $value): array
	{
		if ($value === '') {
			return [];
		}

		$decoded = json_decode($value, true);

		return is_array($decoded) ? $decoded : [];
	}

	/**
	 * @param array<string, mixed> $vector
	 * @return array<string, int>
	 */
	private static function normalize_intent_vector(array $vector): array
	{
		$defaults = [
			'purchase_intent' => 0,
			'research_depth' => 0,
			'price_sensitivity' => 0,
			'urgency' => 0,
			'confidence' => 0,
			'engagement_quality' => 0,
		];

		foreach ($defaults as $key => $default) {
			$defaults[$key] = self::clamp_metric((int) ($vector[$key] ?? $default));
		}

		return $defaults;
	}

	/**
	 * @param array<string, mixed> $signal_counts
	 * @return array<string, int>
	 */
	private static function normalize_signal_counts(array $signal_counts): array
	{
		$defaults = array_fill_keys(self::get_tracked_signal_keys(), 0);

		foreach ($defaults as $key => $default) {
			$defaults[$key] = max(0, (int) ($signal_counts[$key] ?? $default));
		}

		return $defaults;
	}

	/**
	 * @param array<string, int> $signal_counts
	 * @return array<string, int>
	 */
	private static function increment_signal_count(array $signal_counts, string $event_name): array
	{
		$signal_counts = self::normalize_signal_counts($signal_counts);

		if (! in_array($event_name, self::get_tracked_signal_keys(), true)) {
			return $signal_counts;
		}

		$signal_counts[$event_name]++;

		return $signal_counts;
	}

	/**
	 * @param array<string, int> $current
	 * @param array<string, int> $delta
	 * @return array<string, int>
	 */
	private static function apply_intent_vector_delta(array $current, array $delta): array
	{
		$current = self::normalize_intent_vector($current);

		foreach ($delta as $metric => $amount) {
			if (! array_key_exists($metric, $current)) {
				continue;
			}

			$current[$metric] = self::clamp_metric($current[$metric] + (int) $amount);
		}

		return $current;
	}

	/**
	 * @return array<int, string>
	 */
	private static function get_tracked_signal_keys(): array
	{
		return [
			'gallery_view',
			'review_read',
			'faq_expand',
			'date_selector_open',
			'group_size_adjust',
			'availability_check',
			'comparison_add',
			'comparison_view',
		];
	}

	private static function clamp_metric(int $value): int
	{
		return max(0, min(100, $value));
	}

	/**
	 * @param array<int, int> $ids
	 * @return array<int, int>
	 */
	private static function add_id_to_list(array $ids, int $id): array
	{
		if ($id <= 0 || in_array($id, $ids, true)) {
			return $ids;
		}

		$ids[] = $id;

		return array_values($ids);
	}

	/**
	 * @param array<int, int> $ids
	 * @return array<int, string>
	 */
	private static function get_titles_from_ids(array $ids): array
	{
		$titles = [];

		foreach ($ids as $id) {
			$title = get_the_title($id);

			if (is_string($title) && $title !== '') {
				$titles[] = $title;
			}
		}

		return $titles;
	}

	private static function format_tour_context(array $context): string
	{
		$parts = [];

		if (($context['duration'] ?? '') !== '') {
			$parts[] = (string) $context['duration'];
		}

		if (($context['price_from'] ?? '') !== '') {
			$parts[] = (string) $context['price_from'];
		}

		if (($context['availability'] ?? '') !== '') {
			$parts[] = (string) $context['availability'];
		}

		return implode(' | ', $parts);
	}

	private static function get_persona_label(string $persona): string
	{
		$labels = [
			'researcher'         => __('Researcher', 'twostars-platform'),
			'comparison_shopper' => __('Comparison Shopper', 'twostars-platform'),
			'ready_to_book'      => __('Ready to Book', 'twostars-platform'),
			'general_browser'    => __('General Browser', 'twostars-platform'),
		];

		return (string) ($labels[$persona] ?? $persona);
	}

	public static function get_stage(int $intent_score): string
	{
		$config = self::get_config();

		if ($intent_score >= $config['high_intent_score']) {
			return 'high_intent';
		}

		if ($intent_score >= $config['prompt_min_score']) {
			return 'considering';
		}

		return 'browsing';
	}
}
