<?php
declare(strict_types=1);

namespace TwoStars\Platform\Admin;

use TwoStars\Platform\AI\ConversationMemory;
use TwoStars\Platform\AI\GeminiClient;
use TwoStars\Platform\AI\GeminiConfig;
use TwoStars\Platform\AI\WebsiteIntelligence;
use TwoStars\Platform\Support\Assets;
use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Visitors\VisitorProfileManager;

final class VisitorsManager implements Service
{
	public function register(): void
	{
		add_action('admin_menu', [$this, 'register_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
	}

	public function register_menu(): void
	{
		add_submenu_page(
			'twostars-manager',
			__('Manage Visitors', 'twostars-platform'),
			__('Visitors', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-visitors',
			[$this, 'render_visitors_page']
		);

		add_submenu_page(
			null,
			__('Visitor Detail', 'twostars-platform'),
			__('Visitor Detail', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-visitor-detail',
			[$this, 'render_visitor_detail_page']
		);
	}

	public function enqueue_assets(string $hook_suffix): void
	{
		if (! Assets::matches_admin_page($hook_suffix, ['twostars-manager-visitors', 'twostars-manager-visitor-detail'])) {
			return;
		}

		wp_enqueue_style(
			'twostars-platform-visitor-manager',
			Assets::plugin_url('assets/css/visitor-manager.css'),
			['twostars-platform-content-manager'],
			Assets::plugin_version('assets/css/visitor-manager.css')
		);

		wp_enqueue_style(
			'twostars-platform-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			[],
			null
		);

		wp_enqueue_style(
			'twostars-platform-leaflet-markercluster',
			'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',
			['twostars-platform-leaflet'],
			null
		);

		wp_enqueue_style(
			'twostars-platform-leaflet-markercluster-default',
			'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',
			['twostars-platform-leaflet-markercluster'],
			null
		);

		wp_enqueue_script(
			'twostars-platform-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			[],
			null,
			true
		);

		wp_enqueue_script(
			'twostars-platform-leaflet-markercluster',
			'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',
			['twostars-platform-leaflet'],
			null,
			true
		);

		wp_enqueue_script(
			'twostars-platform-visitor-manager',
			Assets::plugin_url('assets/js/visitor-manager.js'),
			['twostars-platform-leaflet-markercluster'],
			Assets::plugin_version('assets/js/visitor-manager.js'),
			true
		);

		wp_localize_script(
			'twostars-platform-visitor-manager',
			'twoStarsVisitorsAdmin',
			[
				'nonce'      => wp_create_nonce('wp_rest'),
				'mapUrl'     => esc_url_raw(rest_url('twostars/v1/admin/visitors/map')),
				'summaryUrl' => esc_url_raw(rest_url('twostars/v1/admin/visitors/summary')),
			]
		);
	}

	public function render_visitors_page(): void
	{
		$filters = [
			'country' => sanitize_text_field((string) ($_GET['country'] ?? '')),
			'city'    => sanitize_text_field((string) ($_GET['city'] ?? '')),
			'status'  => sanitize_text_field((string) ($_GET['status'] ?? '')),
			'type'    => sanitize_text_field((string) ($_GET['type'] ?? '')),
			'linked'  => sanitize_text_field((string) ($_GET['linked'] ?? '')),
		];
		$summary = VisitorProfileManager::get_dashboard_summary();
		$visitors = VisitorProfileManager::get_visitors($filters);
		$top_cities = (array) ($summary['top_cities'] ?? []);
		$top_countries = (array) ($summary['top_countries'] ?? []);
		?>
		<div class="wrap twostars-manager twostars-visitor-manager">
			<div class="twostars-manager__hero twostars-manager__hero--compact">
				<div class="twostars-manager__hero-main">
					<p class="twostars-manager__eyebrow"><?php esc_html_e('Visitors', 'twostars-platform'); ?></p>
					<h1><?php esc_html_e('Visitor Intelligence', 'twostars-platform'); ?></h1>
					<p><?php esc_html_e('Track returning visitors, review map-based location data, understand device and journey patterns, and connect anonymous browsing with real enquiries and bookings.', 'twostars-platform'); ?></p>
				</div>
				<div class="twostars-manager__hero-actions twostars-manager__hero-actions--stacked">
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager')); ?>"><?php esc_html_e('Back to Site Dashboard', 'twostars-platform'); ?></a>
					<a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=lead&page=twostars-crm-dashboard')); ?>"><?php esc_html_e('Open CRM Dashboard', 'twostars-platform'); ?></a>
				</div>
				<div class="twostars-manager__stats">
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($summary['total'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Profiles', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($summary['returning'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Returning', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($summary['ai_assisted'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('AI Assisted', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($summary['high_intent_without_booking'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('High Intent, No Booking', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($summary['mapped_profiles'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Mappable Profiles', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($summary['visitorapi_profiles'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('VisitorAPI Locations', 'twostars-platform'); ?></span></div>
				</div>
			</div>

			<form method="get" class="twostars-manager-form__section twostars-visitor-manager__filters">
				<input type="hidden" name="page" value="twostars-manager-visitors">
				<div class="twostars-manager-form__grid">
					<label class="twostars-manager-form__field">
						<span><?php esc_html_e('Country Code', 'twostars-platform'); ?></span>
						<input type="text" name="country" value="<?php echo esc_attr($filters['country']); ?>" placeholder="NA">
					</label>
					<label class="twostars-manager-form__field">
						<span><?php esc_html_e('City', 'twostars-platform'); ?></span>
						<input type="text" name="city" value="<?php echo esc_attr($filters['city']); ?>">
					</label>
					<label class="twostars-manager-form__field">
						<span><?php esc_html_e('Journey Stage', 'twostars-platform'); ?></span>
						<select name="status">
							<option value=""><?php esc_html_e('All stages', 'twostars-platform'); ?></option>
							<option value="browsing" <?php selected($filters['status'], 'browsing'); ?>><?php esc_html_e('Browsing', 'twostars-platform'); ?></option>
							<option value="considering" <?php selected($filters['status'], 'considering'); ?>><?php esc_html_e('Considering', 'twostars-platform'); ?></option>
							<option value="high_intent" <?php selected($filters['status'], 'high_intent'); ?>><?php esc_html_e('High Intent', 'twostars-platform'); ?></option>
						</select>
					</label>
					<label class="twostars-manager-form__field">
						<span><?php esc_html_e('Visitor Type', 'twostars-platform'); ?></span>
						<select name="type">
							<option value=""><?php esc_html_e('All visitors', 'twostars-platform'); ?></option>
							<option value="new" <?php selected($filters['type'], 'new'); ?>><?php esc_html_e('New', 'twostars-platform'); ?></option>
							<option value="returning" <?php selected($filters['type'], 'returning'); ?>><?php esc_html_e('Returning', 'twostars-platform'); ?></option>
						</select>
					</label>
					<label class="twostars-manager-form__field">
						<span><?php esc_html_e('Linked CRM Records', 'twostars-platform'); ?></span>
						<select name="linked">
							<option value=""><?php esc_html_e('Any', 'twostars-platform'); ?></option>
							<option value="yes" <?php selected($filters['linked'], 'yes'); ?>><?php esc_html_e('Linked', 'twostars-platform'); ?></option>
							<option value="no" <?php selected($filters['linked'], 'no'); ?>><?php esc_html_e('Unlinked', 'twostars-platform'); ?></option>
						</select>
					</label>
				</div>
				<div class="twostars-manager-form__actions">
					<button type="submit" class="button button-primary"><?php esc_html_e('Apply Filters', 'twostars-platform'); ?></button>
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-visitors')); ?>"><?php esc_html_e('Reset Filters', 'twostars-platform'); ?></a>
				</div>
			</form>

			<div class="twostars-visitor-manager__layout">
				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Visitor Map', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('City-level map of consented visitors. VisitorAPI coordinates are preferred when available, with Fingerprint fallback only when better city coordinates are missing.', 'twostars-platform'); ?></p>
					</div>
					<div class="twostars-visitor-manager__map-meta">
						<span class="twostars-visitor-manager__meta-pill"><?php echo esc_html(sprintf(__('Mappable profiles: %d', 'twostars-platform'), (int) ($summary['mapped_profiles'] ?? 0))); ?></span>
						<span class="twostars-visitor-manager__meta-pill"><?php echo esc_html(sprintf(__('VisitorAPI points: %d', 'twostars-platform'), (int) ($summary['visitorapi_profiles'] ?? 0))); ?></span>
						<span class="twostars-visitor-manager__meta-pill"><?php echo esc_html(sprintf(__('Fingerprint fallback: %d', 'twostars-platform'), (int) ($summary['fingerprint_profiles'] ?? 0))); ?></span>
					</div>
					<div
						class="twostars-visitor-map"
						data-twostars-visitor-map
						data-country="<?php echo esc_attr($filters['country']); ?>"
						data-city="<?php echo esc_attr($filters['city']); ?>"
						data-status="<?php echo esc_attr($filters['status']); ?>"
						data-type="<?php echo esc_attr($filters['type']); ?>"
						data-linked="<?php echo esc_attr($filters['linked']); ?>"
					></div>
				</section>

				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Top Markets', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('Use these lists to spot where interest is growing and which markets are bringing back returning visitors.', 'twostars-platform'); ?></p>
					</div>
					<div class="twostars-visitor-manager__market-grid">
						<div class="twostars-visitor-manager__market-panel">
							<h3><?php esc_html_e('Top Countries', 'twostars-platform'); ?></h3>
							<div class="twostars-visitor-manager__country-list">
								<?php foreach ($top_countries as $country) : ?>
									<div class="twostars-visitor-manager__country-card">
										<strong><?php echo esc_html((string) ($country['country_name'] ?? '')); ?></strong>
										<span><?php echo esc_html((string) ($country['country_code'] ?? '')); ?></span>
										<b><?php echo esc_html((string) ($country['total'] ?? 0)); ?></b>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="twostars-visitor-manager__market-panel">
							<h3><?php esc_html_e('Top Cities', 'twostars-platform'); ?></h3>
							<div class="twostars-visitor-manager__city-list">
								<?php if ($top_cities === []) : ?>
									<p><?php esc_html_e('No city-level visitor data is available yet.', 'twostars-platform'); ?></p>
								<?php endif; ?>
								<?php foreach ($top_cities as $city) : ?>
									<div class="twostars-visitor-manager__city-card">
										<div>
											<strong><?php echo esc_html((string) ($city['city'] ?? '')); ?></strong>
											<span><?php echo esc_html((string) ($city['country_name'] ?? '')); ?></span>
										</div>
										<b><?php echo esc_html((string) ($city['total'] ?? 0)); ?></b>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</section>
			</div>

			<section class="twostars-manager-form__section">
				<div class="twostars-manager-form__section-head">
					<h2><?php esc_html_e('Visitor Profiles', 'twostars-platform'); ?></h2>
					<p><?php esc_html_e('Open any profile to review location, sessions, AI conversations, linked CRM records, and what the visitor actually explored on the site.', 'twostars-platform'); ?></p>
				</div>
				<div class="twostars-visitor-manager__cards">
					<?php if ($visitors === []) : ?>
						<p><?php esc_html_e('No visitor profiles match the current filters.', 'twostars-platform'); ?></p>
					<?php endif; ?>
					<?php foreach ($visitors as $visitor) : ?>
						<?php $this->render_visitor_row($visitor); ?>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php
	}

	public function render_visitor_detail_page(): void
	{
		$detail = VisitorProfileManager::get_profile_detail(absint($_GET['visitor_id'] ?? 0));

		if (! $detail) {
			wp_die(esc_html__('That visitor profile could not be found.', 'twostars-platform'));
		}

		$profile = (array) ($detail['profile'] ?? []);
		$context = (array) ($detail['context'] ?? []);
		$location_label = (string) ($context['location_label'] ?? $this->format_location_label($context));
		$detail_map_points = $this->get_detail_map_points($context, $profile);
		$tour_titles = $this->get_post_titles((array) ($context['visited_tours'] ?? []));
		$destination_titles = $this->get_post_titles((array) ($context['visited_destinations'] ?? []));
		$sessions = (array) ($detail['sessions'] ?? []);
		$events = (array) ($detail['events'] ?? []);
		$viewed_pages = $this->get_viewed_page_names($events);
		$conversations = (array) ($detail['conversations'] ?? []);
		$conversation_summaries = $this->get_conversation_summaries($conversations);
		$sessions_analysis = $this->get_sessions_analysis($sessions, $events, $context, (int) ($profile['id'] ?? 0));
		$events_analysis = $this->get_events_analysis($events, $context, (int) ($profile['id'] ?? 0));
		?>
		<div class="wrap twostars-manager twostars-visitor-manager">
			<div class="twostars-manager__hero twostars-manager__hero--compact">
				<div class="twostars-manager__hero-main">
					<p class="twostars-manager__eyebrow"><?php esc_html_e('Visitor Profile', 'twostars-platform'); ?></p>
					<h1><?php echo esc_html($location_label !== '' ? $location_label : __('Unknown Visitor', 'twostars-platform')); ?></h1>
					<p><?php esc_html_e('Use this profile to understand where the visitor came from, what they browsed, how they used the AI guide, and whether they turned into a lead or booking.', 'twostars-platform'); ?></p>
				</div>
				<div class="twostars-manager__hero-actions">
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-visitors')); ?>"><?php esc_html_e('Back to Visitors', 'twostars-platform'); ?></a>
					<?php if (! empty($profile['last_origin_url'])) : ?>
						<a class="button" href="<?php echo esc_url((string) $profile['last_origin_url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Open Last Page', 'twostars-platform'); ?></a>
					<?php endif; ?>
				</div>
				<div class="twostars-manager__stats">
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($context['session_count'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Sessions', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($context['page_view_count'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Page Views', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($context['ai_message_count'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('AI Messages', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($context['booking_complete_count'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Bookings', 'twostars-platform'); ?></span></div>
				</div>
			</div>

			<div class="twostars-visitor-detail__grid">
				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Profile Overview', 'twostars-platform'); ?></h2>
					</div>
					<div class="twostars-visitor-detail__facts">
						<div><strong><?php esc_html_e('Fingerprint Visitor ID', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) ($context['fingerprint_visitor_id'] ?? '')); ?></div>
						<div><strong><?php esc_html_e('Consent', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) ($profile['consent_status'] ?? '')); ?></div>
						<div><strong><?php esc_html_e('Location', 'twostars-platform'); ?>:</strong> <?php echo esc_html($location_label !== '' ? $location_label : __('Unknown', 'twostars-platform')); ?></div>
						<div><strong><?php esc_html_e('Location Source', 'twostars-platform'); ?>:</strong> <?php echo esc_html($this->format_location_source_label((string) ($context['location_source'] ?? ''))); ?></div>
						<div><strong><?php esc_html_e('Device', 'twostars-platform'); ?>:</strong> <?php echo esc_html(trim(((string) ($context['device_family'] ?? '')) . ' ' . ((string) ($context['browser'] ?? '')))); ?></div>
						<div><strong><?php esc_html_e('Stage', 'twostars-platform'); ?>:</strong> <?php echo esc_html($this->format_stage_label((string) ($context['latest_stage'] ?? ''))); ?></div>
						<div><strong><?php esc_html_e('Persona', 'twostars-platform'); ?>:</strong> <?php echo esc_html($this->format_label((string) ($context['latest_persona'] ?? ''))); ?></div>
						<div><strong><?php esc_html_e('First Seen', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) ($context['first_seen_at'] ?? '')); ?></div>
						<div><strong><?php esc_html_e('Last Seen', 'twostars-platform'); ?>:</strong> <?php echo esc_html((string) ($context['last_seen_at'] ?? '')); ?></div>
					</div>
				</section>

				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Behavior Summary', 'twostars-platform'); ?></h2>
					</div>
					<div class="twostars-visitor-detail__stats">
						<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($context['tour_view_count'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Tour Views', 'twostars-platform'); ?></span></div>
						<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($context['destination_view_count'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Destination Views', 'twostars-platform'); ?></span></div>
						<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($context['booking_click_count'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Booking Clicks', 'twostars-platform'); ?></span></div>
						<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($context['high_intent_count'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('High-Intent Signals', 'twostars-platform'); ?></span></div>
					</div>
				</section>
			</div>

			<div class="twostars-visitor-detail__grid">
				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Location Map', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('The marker below uses the best available visitor location coordinates stored for this profile.', 'twostars-platform'); ?></p>
					</div>
					<?php if ($detail_map_points !== []) : ?>
						<div
							class="twostars-visitor-map twostars-visitor-map--detail"
							data-twostars-visitor-map
							data-zoom="8"
							data-points="<?php echo esc_attr(wp_json_encode($detail_map_points)); ?>"
						></div>
					<?php else : ?>
						<div class="twostars-visitor-map__empty"><?php esc_html_e('No usable city coordinates are stored for this visitor yet.', 'twostars-platform'); ?></div>
					<?php endif; ?>
				</section>

				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Linked CRM Records', 'twostars-platform'); ?></h2>
					</div>
					<div class="twostars-visitor-detail__linked">
						<?php foreach ((array) ($detail['leads'] ?? []) as $linked) : ?>
							<a class="twostars-manager__quick-card" href="<?php echo esc_url((string) ($linked['edit_url'] ?? '')); ?>">
								<h2><?php echo esc_html((string) ($linked['title'] ?? '')); ?></h2>
								<p><?php esc_html_e('Lead record', 'twostars-platform'); ?></p>
							</a>
						<?php endforeach; ?>
						<?php foreach ((array) ($detail['bookings'] ?? []) as $linked) : ?>
							<a class="twostars-manager__quick-card" href="<?php echo esc_url((string) ($linked['edit_url'] ?? '')); ?>">
								<h2><?php echo esc_html((string) ($linked['title'] ?? '')); ?></h2>
								<p><?php esc_html_e('Booking record', 'twostars-platform'); ?></p>
							</a>
						<?php endforeach; ?>
						<?php if (empty($detail['leads']) && empty($detail['bookings'])) : ?>
							<p><?php esc_html_e('No linked leads or bookings yet.', 'twostars-platform'); ?></p>
						<?php endif; ?>
					</div>
				</section>

				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Viewed Content', 'twostars-platform'); ?></h2>
					</div>
					<div class="twostars-visitor-detail__content-groups">
						<div>
							<strong><?php esc_html_e('Tours', 'twostars-platform'); ?></strong>
							<div class="twostars-visitor-detail__chips">
								<?php foreach ($tour_titles as $title) : ?>
									<span class="twostars-visitor-detail__chip"><?php echo esc_html($title); ?></span>
								<?php endforeach; ?>
								<?php if ($tour_titles === []) : ?>
									<span class="twostars-visitor-detail__chip is-muted"><?php esc_html_e('No tours viewed yet', 'twostars-platform'); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<div>
							<strong><?php esc_html_e('Destinations', 'twostars-platform'); ?></strong>
							<div class="twostars-visitor-detail__chips">
								<?php foreach ($destination_titles as $title) : ?>
									<span class="twostars-visitor-detail__chip"><?php echo esc_html($title); ?></span>
								<?php endforeach; ?>
								<?php if ($destination_titles === []) : ?>
									<span class="twostars-visitor-detail__chip is-muted"><?php esc_html_e('No destinations viewed yet', 'twostars-platform'); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<div>
							<strong><?php esc_html_e('Viewed Pages', 'twostars-platform'); ?></strong>
							<div class="twostars-visitor-detail__chips">
								<?php foreach ($viewed_pages as $page_name) : ?>
									<span class="twostars-visitor-detail__chip"><?php echo esc_html($page_name); ?></span>
								<?php endforeach; ?>
								<?php if ($viewed_pages === []) : ?>
									<span class="twostars-visitor-detail__chip is-muted"><?php esc_html_e('No named pages captured yet', 'twostars-platform'); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</section>
			</div>

			<div class="twostars-visitor-detail__grid">
				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Session Intelligence', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('AI condenses repeat visits into the signals the site owner can actually act on.', 'twostars-platform'); ?></p>
					</div>
					<div class="twostars-visitor-detail__analysis-stack">
						<article class="twostars-visitor-detail__analysis-card">
							<span class="twostars-visitor-detail__analysis-eyebrow"><?php esc_html_e('AI Session Readout', 'twostars-platform'); ?></span>
							<div class="twostars-visitor-detail__analysis-copy">
								<?php echo wp_kses_post(wpautop(esc_html((string) ($sessions_analysis['summary'] ?? '')))); ?>
							</div>
						</article>
						<div class="twostars-visitor-detail__signal-list">
							<?php foreach ((array) ($sessions_analysis['highlights'] ?? []) as $highlight) : ?>
								<article class="twostars-visitor-detail__signal-card">
									<span><?php echo esc_html((string) ($highlight['label'] ?? '')); ?></span>
									<strong><?php echo esc_html((string) ($highlight['value'] ?? '')); ?></strong>
								</article>
							<?php endforeach; ?>
						</div>
						<div class="twostars-visitor-detail__timeline">
							<?php foreach ((array) ($sessions_analysis['cards'] ?? []) as $card) : ?>
								<article class="twostars-visitor-detail__timeline-card">
									<div class="twostars-visitor-detail__timeline-head">
										<div>
											<strong><?php echo esc_html((string) ($card['title'] ?? '')); ?></strong>
											<span><?php echo esc_html((string) ($card['subtitle'] ?? '')); ?></span>
										</div>
										<span class="twostars-manager-dashboard-pill twostars-manager-dashboard-pill--neutral"><?php echo esc_html((string) ($card['pill'] ?? '')); ?></span>
									</div>
									<p><?php echo esc_html((string) ($card['description'] ?? '')); ?></p>
									<div class="twostars-visitor-detail__timeline-meta">
										<?php foreach ((array) ($card['meta'] ?? []) as $meta) : ?>
											<span><?php echo esc_html((string) $meta); ?></span>
										<?php endforeach; ?>
									</div>
								</article>
							<?php endforeach; ?>
							<?php if (empty($sessions_analysis['cards'])) : ?>
								<div class="twostars-visitor-map__empty"><?php esc_html_e('No tracked sessions are available for this visitor yet.', 'twostars-platform'); ?></div>
							<?php endif; ?>
						</div>
					</div>
				</section>

				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Recent Action Intelligence', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('Only the meaningful visitor signals are surfaced here, with AI filtering out the low-value tracking noise.', 'twostars-platform'); ?></p>
					</div>
					<div class="twostars-visitor-detail__analysis-stack">
						<article class="twostars-visitor-detail__analysis-card">
							<span class="twostars-visitor-detail__analysis-eyebrow"><?php esc_html_e('AI Action Readout', 'twostars-platform'); ?></span>
							<div class="twostars-visitor-detail__analysis-copy">
								<?php echo wp_kses_post(wpautop(esc_html((string) ($events_analysis['summary'] ?? '')))); ?>
							</div>
						</article>
						<div class="twostars-visitor-detail__signal-list">
							<?php foreach ((array) ($events_analysis['highlights'] ?? []) as $highlight) : ?>
								<article class="twostars-visitor-detail__signal-card">
									<span><?php echo esc_html((string) ($highlight['label'] ?? '')); ?></span>
									<strong><?php echo esc_html((string) ($highlight['value'] ?? '')); ?></strong>
								</article>
							<?php endforeach; ?>
						</div>
						<div class="twostars-visitor-detail__timeline">
							<?php foreach ((array) ($events_analysis['cards'] ?? []) as $card) : ?>
								<article class="twostars-visitor-detail__timeline-card">
									<div class="twostars-visitor-detail__timeline-head">
										<div>
											<strong><?php echo esc_html((string) ($card['title'] ?? '')); ?></strong>
											<span><?php echo esc_html((string) ($card['subtitle'] ?? '')); ?></span>
										</div>
										<span class="twostars-manager-dashboard-pill twostars-manager-dashboard-pill--neutral"><?php echo esc_html((string) ($card['pill'] ?? '')); ?></span>
									</div>
									<p><?php echo esc_html((string) ($card['description'] ?? '')); ?></p>
									<div class="twostars-visitor-detail__timeline-meta">
										<?php foreach ((array) ($card['meta'] ?? []) as $meta) : ?>
											<span><?php echo esc_html((string) $meta); ?></span>
										<?php endforeach; ?>
									</div>
								</article>
							<?php endforeach; ?>
							<?php if (empty($events_analysis['cards'])) : ?>
								<div class="twostars-visitor-map__empty"><?php esc_html_e('No meaningful tracked actions are available for this visitor yet.', 'twostars-platform'); ?></div>
							<?php endif; ?>
						</div>
					</div>
				</section>
			</div>

			<section class="twostars-manager-form__section">
				<div class="twostars-manager-form__section-head">
					<h2><?php esc_html_e('Chat History', 'twostars-platform'); ?></h2>
					<p><?php esc_html_e('These are the Two Stars guide conversations tied to this visitor profile, shown as readable chat windows so you can quickly see what the guest asked and how the guide responded.', 'twostars-platform'); ?></p>
				</div>
				<div class="twostars-visitor-detail__chat-windows">
					<?php if ($conversations === []) : ?>
						<p><?php esc_html_e('No saved chatbot conversations for this visitor yet.', 'twostars-platform'); ?></p>
					<?php endif; ?>
					<?php foreach ($conversations as $conversation) : ?>
						<?php
						$history = is_array($conversation['history'] ?? null) ? $conversation['history'] : [];
						$conversation_key = (string) ($conversation['conversation_key'] ?? $conversation['id'] ?? '');
						$summary = (array) ($conversation_summaries[$conversation_key] ?? []);
						$summary_text = trim((string) ($summary['summary'] ?? ''));
						$mentioned_tours = array_slice((array) ($summary['mentioned_tours'] ?? []), 0, 4);
						$preferences = array_slice((array) ($summary['preferences'] ?? []), 0, 2);
						$started_at = (string) (($history[0]['created_at'] ?? '') ?: '');
						?>
						<article class="twostars-visitor-detail__chat-window">
							<div class="twostars-visitor-detail__chat-window-head">
								<div>
									<p class="twostars-visitor-detail__chat-eyebrow"><?php esc_html_e('Guide Conversation', 'twostars-platform'); ?></p>
									<h3><?php echo esc_html((string) ($conversation['title'] ?? __('Two Stars planning', 'twostars-platform'))); ?></h3>
									<p><?php echo esc_html($summary_text !== '' ? $summary_text : (string) ($conversation['preview'] ?? '')); ?></p>
								</div>
								<div class="twostars-visitor-detail__chat-window-meta">
									<span><?php echo esc_html((string) ($conversation['updated_at'] ?? '')); ?></span>
									<strong><?php echo esc_html(sprintf(_n('%d message', '%d messages', (int) ($conversation['message_count'] ?? 0), 'twostars-platform'), (int) ($conversation['message_count'] ?? 0))); ?></strong>
								</div>
							</div>
							<?php if ($mentioned_tours !== [] || $preferences !== []) : ?>
								<div class="twostars-visitor-detail__chat-window-context">
									<?php foreach ($mentioned_tours as $tour_title) : ?>
										<span class="twostars-visitor-detail__chat-context-chip"><?php echo esc_html($tour_title); ?></span>
									<?php endforeach; ?>
									<?php foreach ($preferences as $preference) : ?>
										<span class="twostars-visitor-detail__chat-context-chip is-soft"><?php echo esc_html(wp_trim_words((string) $preference, 8, '...')); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<div class="twostars-visitor-detail__chat-screen">
								<div class="twostars-visitor-detail__chat-thread">
									<?php foreach ($history as $message) : ?>
										<?php
										$role = (string) ($message['role'] ?? 'user');
										$is_assistant = $role === 'assistant';
										?>
										<div class="twostars-visitor-detail__chat-row<?php echo $is_assistant ? ' is-assistant' : ' is-user'; ?>">
											<span class="twostars-visitor-detail__chat-avatar"><?php echo esc_html($is_assistant ? 'TS' : 'G'); ?></span>
											<div class="twostars-visitor-detail__chat-message">
												<div class="twostars-visitor-detail__chat-message-meta">
													<strong><?php echo esc_html($is_assistant ? __('Guide', 'twostars-platform') : __('Guest', 'twostars-platform')); ?></strong>
													<span><?php echo esc_html((string) ($message['created_at'] ?? '')); ?></span>
												</div>
												<div class="twostars-visitor-detail__chat-bubble"><?php echo esc_html((string) ($message['content'] ?? '')); ?></div>
											</div>
										</div>
									<?php endforeach; ?>
									<?php if ($history === []) : ?>
										<div class="twostars-visitor-map__empty"><?php esc_html_e('No saved messages were found for this conversation.', 'twostars-platform'); ?></div>
									<?php endif; ?>
								</div>
							</div>
							<div class="twostars-visitor-detail__chat-window-foot">
								<span><?php echo esc_html($started_at !== '' ? sprintf(__('Started %s', 'twostars-platform'), $started_at) : __('Started time unavailable', 'twostars-platform')); ?></span>
								<span><?php echo esc_html((string) ($conversation['updated_at'] ?? '')); ?></span>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * @param array<int, array<string, mixed>> $sessions
	 * @param array<int, array<string, mixed>> $events
	 * @param array<string, mixed> $context
	 * @return array<string, mixed>
	 */
	private function get_sessions_analysis(array $sessions, array $events, array $context, int $profile_id): array
	{
		$total_sessions = count($sessions);
		$total_page_views = 0;
		$high_intent_sessions = 0;
		$latest_score = 0;
		$latest_persona = '';
		$session_page_map = $this->get_session_page_map($events);

		foreach ($sessions as $index => $session) {
			$score = (int) ($session['intent_score'] ?? 0);
			$total_page_views += (int) ($session['page_views'] ?? 0);

			if ($this->is_high_intent_score($score)) {
				$high_intent_sessions++;
			}

			if ($index === 0) {
				$latest_score = $score;
				$latest_persona = (string) ($session['current_persona'] ?? '');
			}
		}

		$latest_session = $sessions[0] ?? [];
		$oldest_session = $sessions[$total_sessions - 1] ?? $latest_session;
		$avg_page_views = $total_sessions > 0 ? (int) round($total_page_views / $total_sessions) : 0;
		$return_window = $this->format_return_window((string) ($oldest_session['started_at'] ?? $oldest_session['last_seen_at'] ?? ''), (string) ($latest_session['last_seen_at'] ?? ''));
		$session_rows = [];

		foreach (array_slice($sessions, 0, 4) as $index => $session) {
			$page_views = (int) ($session['page_views'] ?? 0);
			$visited_tours = $this->decode_id_list((string) ($session['visited_tours'] ?? ''));
			$visited_destinations = $this->decode_id_list((string) ($session['visited_destinations'] ?? ''));
			$stage = WebsiteIntelligence::get_stage((int) ($session['intent_score'] ?? 0));
			$page_name = (string) ($session_page_map[(string) ($session['session_key'] ?? '')] ?? '');

			$session_rows[] = [
				'title' => $index === 0 ? __('Latest session', 'twostars-platform') : sprintf(__('Earlier session %d', 'twostars-platform'), $index + 1),
				'subtitle' => $page_name !== '' ? $page_name : (string) ($session['last_seen_at'] ?? ''),
				'pill' => $this->format_stage_label($stage),
				'description' => $page_name !== ''
					? sprintf(__('Last meaningful page viewed: %s', 'twostars-platform'), $page_name)
					: $this->truncate_url((string) ($session['last_url'] ?? $session['first_url'] ?? '')),
				'meta' => array_values(array_filter([
					(string) ($session['last_seen_at'] ?? ''),
					sprintf(__('Intent %d', 'twostars-platform'), (int) ($session['intent_score'] ?? 0)),
					$this->format_label((string) ($session['current_persona'] ?? 'general_browser')),
					sprintf(_n('%d page view', '%d page views', $page_views, 'twostars-platform'), $page_views),
					count($visited_tours) > 0 ? sprintf(_n('%d tour viewed', '%d tours viewed', count($visited_tours), 'twostars-platform'), count($visited_tours)) : '',
					count($visited_destinations) > 0 ? sprintf(_n('%d destination viewed', '%d destinations viewed', count($visited_destinations), 'twostars-platform'), count($visited_destinations)) : '',
				])),
			];
		}

		$fallback_summary = $this->build_sessions_fallback_summary($sessions, $context, $avg_page_views, $high_intent_sessions, $return_window);
		$ai_input = [
			'latest_stage' => (string) ($context['latest_stage'] ?? ''),
			'latest_persona' => $latest_persona !== '' ? $latest_persona : (string) ($context['latest_persona'] ?? ''),
			'session_count' => $total_sessions,
			'high_intent_sessions' => $high_intent_sessions,
			'average_page_views' => $avg_page_views,
			'return_window' => $return_window,
			'latest_intent_score' => $latest_score,
			'sessions' => array_map(
				fn(array $session): array => [
					'last_seen_at' => (string) ($session['last_seen_at'] ?? ''),
					'page_name' => (string) ($session_page_map[(string) ($session['session_key'] ?? '')] ?? ''),
					'intent_score' => (int) ($session['intent_score'] ?? 0),
					'stage' => WebsiteIntelligence::get_stage((int) ($session['intent_score'] ?? 0)),
					'persona' => (string) ($session['current_persona'] ?? ''),
					'page_views' => (int) ($session['page_views'] ?? 0),
					'last_url' => $this->truncate_url((string) ($session['last_url'] ?? '')),
					'tours_viewed' => count($this->decode_id_list((string) ($session['visited_tours'] ?? ''))),
					'destinations_viewed' => count($this->decode_id_list((string) ($session['visited_destinations'] ?? ''))),
				],
				array_slice($sessions, 0, 6)
			),
		];

		return [
			'summary' => $this->generate_ai_profile_analysis(
				'sessions',
				$profile_id,
				$ai_input,
				$this->build_sessions_prompt($ai_input),
				$fallback_summary
			),
			'highlights' => [
				[
					'label' => __('Sessions Tracked', 'twostars-platform'),
					'value' => (string) $total_sessions,
				],
				[
					'label' => __('High-Intent Visits', 'twostars-platform'),
					'value' => (string) $high_intent_sessions,
				],
				[
					'label' => __('Average Depth', 'twostars-platform'),
					'value' => sprintf(_n('%d page per visit', '%d pages per visit', $avg_page_views, 'twostars-platform'), $avg_page_views),
				],
				[
					'label' => __('Return Pattern', 'twostars-platform'),
					'value' => $return_window,
				],
			],
			'cards' => $session_rows,
		];
	}

	/**
	 * @param array<int, array<string, mixed>> $events
	 * @param array<string, mixed> $context
	 * @return array<string, mixed>
	 */
	private function get_events_analysis(array $events, array $context, int $profile_id): array
	{
		$relevant_events = $this->get_relevant_events($events);
		$counts = $this->summarize_event_counts($relevant_events);
		$chat_touchpoints = $counts['chat_opened'] + $counts['chat_message'];
		$contact_signals = $counts['whatsapp_click'] + $counts['lead_form_open'] + $counts['lead_created'] + $counts['booking_click'] + $counts['booking_started'] + $counts['booking_completed'];
		$latest_signal = $relevant_events[0] ?? [];
		$top_signal = $this->get_top_event_signal($counts);
		$action_rows = [];

		foreach (array_slice($relevant_events, 0, 5) as $event) {
			$payload = $this->decode_event_payload((string) ($event['payload'] ?? ''));
			$score_delta = (int) ($event['score_delta'] ?? 0);
			$page_name = $this->extract_page_name_from_event($event);
			$action_rows[] = [
				'title' => $this->format_event_label((string) ($event['event_name'] ?? '')),
				'subtitle' => $page_name !== '' ? $page_name : (string) ($event['created_at'] ?? ''),
				'pill' => $score_delta > 0 ? sprintf(__('Intent +%d', 'twostars-platform'), $score_delta) : $this->format_label((string) ($event['page_type'] ?? 'activity')),
				'description' => $this->build_event_description($event, $payload, $page_name),
				'meta' => array_values(array_filter([
					(string) ($event['created_at'] ?? ''),
					! empty($event['session_key']) ? sprintf(__('Session %s', 'twostars-platform'), $this->truncate_session_key((string) $event['session_key'])) : '',
					! empty($event['page_type']) ? $this->format_label((string) $event['page_type']) : '',
					$page_name === '' ? $this->truncate_url((string) ($event['page_url'] ?? '')) : '',
				])),
			];
		}

		$fallback_summary = $this->build_events_fallback_summary($counts, $context, $relevant_events);
		$ai_input = [
			'latest_stage' => (string) ($context['latest_stage'] ?? ''),
			'latest_persona' => (string) ($context['latest_persona'] ?? ''),
			'relevant_signal_count' => count($relevant_events),
			'chat_touchpoints' => $chat_touchpoints,
			'contact_signals' => $contact_signals,
			'top_signal' => $top_signal,
			'latest_signal' => $latest_signal !== [] ? [
				'event' => (string) ($latest_signal['event_name'] ?? ''),
				'created_at' => (string) ($latest_signal['created_at'] ?? ''),
				'page_name' => $this->extract_page_name_from_event($latest_signal),
				'page_type' => (string) ($latest_signal['page_type'] ?? ''),
				'url' => $this->truncate_url((string) ($latest_signal['page_url'] ?? '')),
			] : [],
			'event_counts' => $counts,
			'recent_events' => array_map(
				fn(array $event): array => [
					'created_at' => (string) ($event['created_at'] ?? ''),
					'event' => (string) ($event['event_name'] ?? ''),
					'page_name' => $this->extract_page_name_from_event($event),
					'page_type' => (string) ($event['page_type'] ?? ''),
					'score_delta' => (int) ($event['score_delta'] ?? 0),
					'url' => $this->truncate_url((string) ($event['page_url'] ?? '')),
				],
				array_slice($relevant_events, 0, 8)
			),
		];

		return [
			'summary' => $this->generate_ai_profile_analysis(
				'actions',
				$profile_id,
				$ai_input,
				$this->build_events_prompt($ai_input),
				$fallback_summary
			),
			'highlights' => [
				[
					'label' => __('Meaningful Signals', 'twostars-platform'),
					'value' => (string) count($relevant_events),
				],
				[
					'label' => __('Strongest Signal', 'twostars-platform'),
					'value' => $top_signal,
				],
				[
					'label' => __('AI Touchpoints', 'twostars-platform'),
					'value' => (string) $chat_touchpoints,
				],
				[
					'label' => __('Contact or Booking Moves', 'twostars-platform'),
					'value' => (string) $contact_signals,
				],
			],
			'cards' => $action_rows,
		];
	}

	/**
	 * @param array<string, mixed> $input
	 */
	private function build_sessions_prompt(array $input): string
	{
		return "You are summarizing visitor session history for a safari website owner inside a WordPress admin dashboard.\n"
			. "Write exactly 2 short paragraphs in plain English, under 110 words total.\n"
			. "Focus on what the repeat-visit pattern means, how serious the planning looks, and what the owner should infer next.\n"
			. "Do not mention technical IDs, raw JSON, or generic filler. Avoid hype.\n\n"
			. "Session summary data:\n"
			. wp_json_encode($input, JSON_PRETTY_PRINT);
	}

	/**
	 * @param array<string, mixed> $input
	 */
	private function build_events_prompt(array $input): string
	{
		return "You are summarizing meaningful on-site actions for a safari website owner inside a WordPress admin dashboard.\n"
			. "Write exactly 2 short paragraphs in plain English, under 110 words total.\n"
			. "Focus only on commercially useful meaning: buying signals, research depth, AI or contact behaviour, and whether this visitor deserves attention now.\n"
			. "Ignore routine browsing noise unless it changes the interpretation.\n\n"
			. "Action summary data:\n"
			. wp_json_encode($input, JSON_PRETTY_PRINT);
	}

	/**
	 * @param array<string, mixed> $fingerprint
	 */
	private function generate_ai_profile_analysis(string $type, int $profile_id, array $fingerprint, string $prompt, string $fallback): string
	{
		if ($profile_id <= 0 || ! GeminiConfig::is_enabled()) {
			return $fallback;
		}

		$cache_key = sprintf('ts_vis_%s_%d_%s', $type, $profile_id, md5((string) wp_json_encode($fingerprint)));
		$cached = get_transient($cache_key);

		if (is_string($cached) && trim($cached) !== '') {
			return $cached;
		}

		$response = GeminiClient::generate_reasoned_text($prompt);

		if (is_wp_error($response)) {
			return $fallback;
		}

		$analysis = $this->sanitize_ai_analysis($response);

		if ($analysis === '') {
			return $fallback;
		}

		set_transient($cache_key, $analysis, 6 * HOUR_IN_SECONDS);

		return $analysis;
	}

	private function sanitize_ai_analysis(string $analysis): string
	{
		$analysis = wp_strip_all_tags($analysis);
		$analysis = preg_replace("/\n{3,}/", "\n\n", trim($analysis));

		return is_string($analysis) ? trim($analysis) : '';
	}

	/**
	 * @param array<int, array<string, mixed>> $sessions
	 * @param array<string, mixed> $context
	 */
	private function build_sessions_fallback_summary(array $sessions, array $context, int $avg_page_views, int $high_intent_sessions, string $return_window): string
	{
		$session_count = count($sessions);
		$latest_stage = $this->format_stage_label((string) ($context['latest_stage'] ?? 'browsing'));
		$latest_persona = $this->format_label((string) ($context['latest_persona'] ?? 'general_browser'));

		if ($session_count === 0) {
			return __('No tracked sessions are available yet, so there is not enough behavior to judge planning seriousness.', 'twostars-platform');
		}

		$line_one = $session_count > 1
			? sprintf(__('This visitor returned across %1$d sessions over %2$s, which points to ongoing trip research rather than a one-off browse.', 'twostars-platform'), $session_count, $return_window)
			: __('This visitor only has one tracked session so far, so the pattern is still early and may change with the next visit.', 'twostars-platform');

		$line_two = $high_intent_sessions > 0
			? sprintf(__('The latest behavior sits in the %1$s stage with a %2$s persona, and %3$d session(s) already crossed the high-intent threshold.', 'twostars-platform'), $latest_stage, $latest_persona, $high_intent_sessions)
			: sprintf(__('The latest behavior sits in the %1$s stage with a %2$s persona, with about %3$d page views per session so far.', 'twostars-platform'), $latest_stage, $latest_persona, $avg_page_views);

		return $line_one . "\n\n" . $line_two;
	}

	/**
	 * @param array<string, int> $counts
	 * @param array<string, mixed> $context
	 * @param array<int, array<string, mixed>> $events
	 */
	private function build_events_fallback_summary(array $counts, array $context, array $events): string
	{
		$total_relevant = array_sum($counts);

		if ($total_relevant === 0) {
			return __('There are no meaningful tracked actions yet beyond routine browsing, so this visitor is not showing a clear commercial signal.', 'twostars-platform');
		}

		$top_signal = $this->get_top_event_signal($counts);
		$latest_stage = $this->format_stage_label((string) ($context['latest_stage'] ?? 'browsing'));
		$contact_signals = $counts['whatsapp_click'] + $counts['lead_form_open'] + $counts['lead_created'] + $counts['booking_click'] + $counts['booking_started'] + $counts['booking_completed'];
		$research_signals = $counts['comparison_add'] + $counts['comparison_view'] + $counts['review_read'] + $counts['faq_expand'] + $counts['availability_check'] + $counts['gallery_view'] + $counts['pricing_interest'] + $counts['inclusion_interest'];
		$latest_event = $events[0] ?? [];

		$line_one = sprintf(__('The strongest action pattern is %1$s, with %2$d meaningful signals recorded while the profile currently sits in the %3$s stage.', 'twostars-platform'), $top_signal, $total_relevant, $latest_stage);
		$line_two = $contact_signals > 0
			? sprintf(__('Contact or booking-related moves appeared %1$d time(s), which makes this visitor worth attention if the CRM record is still open.', 'twostars-platform'), $contact_signals)
			: sprintf(__('Most signals still point to research activity, with %1$d research-led actions and no strong contact or booking move yet.', 'twostars-platform'), $research_signals);

		if (! empty($latest_event['event_name'])) {
			$line_two .= ' ' . sprintf(__('The latest meaningful action was %1$s on %2$s.', 'twostars-platform'), $this->format_event_label((string) $latest_event['event_name']), (string) ($latest_event['created_at'] ?? ''));
		}

		return $line_one . "\n\n" . $line_two;
	}

	private function format_return_window(string $start, string $end): string
	{
		$start_time = $start !== '' ? strtotime($start) : false;
		$end_time = $end !== '' ? strtotime($end) : false;

		if (! $start_time || ! $end_time || $end_time <= $start_time) {
			return __('a single visit window', 'twostars-platform');
		}

		$days = (int) floor(($end_time - $start_time) / DAY_IN_SECONDS);

		if ($days <= 0) {
			return __('the same day', 'twostars-platform');
		}

		return sprintf(_n('%d day', '%d days', $days, 'twostars-platform'), $days);
	}

	/**
	 * @param array<int, array<string, mixed>> $events
	 * @return array<int, array<string, mixed>>
	 */
	private function get_relevant_events(array $events): array
	{
		return array_values(
			array_filter(
				$events,
				fn(array $event): bool => $this->get_event_priority((string) ($event['event_name'] ?? '')) > 0
			)
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $events
	 * @return array<string, int>
	 */
	private function summarize_event_counts(array $events): array
	{
		$counts = array_fill_keys(array_keys($this->get_event_priority_map()), 0);

		foreach ($events as $event) {
			$name = (string) ($event['event_name'] ?? '');

			if (! array_key_exists($name, $counts)) {
				continue;
			}

			$counts[$name]++;
		}

		return $counts;
	}

	/**
	 * @return array<string, int>
	 */
	private function get_event_priority_map(): array
	{
		return [
			'booking_completed' => 100,
			'lead_created' => 95,
			'booking_started' => 90,
			'whatsapp_click' => 85,
			'availability_check' => 82,
			'booking_click' => 80,
			'lead_form_open' => 76,
			'pricing_interest' => 68,
			'comparison_view' => 64,
			'comparison_add' => 60,
			'chat_message' => 58,
			'chat_opened' => 52,
			'date_selector_open' => 50,
			'inclusion_interest' => 48,
			'review_read' => 44,
			'gallery_view' => 40,
			'faq_expand' => 36,
			'group_size_adjust' => 34,
			'tour_card_click' => 30,
			'hero_cta_click' => 28,
			'destination_card_click' => 24,
		];
	}

	private function get_event_priority(string $event_name): int
	{
		$map = $this->get_event_priority_map();

		return (int) ($map[$event_name] ?? 0);
	}

	private function get_top_event_signal(array $counts): string
	{
		$top_event = '';
		$top_count = 0;
		$priorities = $this->get_event_priority_map();

		foreach ($counts as $event_name => $count) {
			if ($count <= 0) {
				continue;
			}

			if ($count > $top_count || ($count === $top_count && ($priorities[$event_name] ?? 0) > ($priorities[$top_event] ?? 0))) {
				$top_event = (string) $event_name;
				$top_count = $count;
			}
		}

		if ($top_event === '') {
			return __('Routine research only', 'twostars-platform');
		}

		return sprintf(_n('%1$s (%2$d time)', '%1$s (%2$d times)', $top_count, 'twostars-platform'), $this->format_event_label($top_event), $top_count);
	}

	/**
	 * @param array<int, array<string, mixed>> $events
	 * @return array<int, string>
	 */
	private function get_viewed_page_names(array $events): array
	{
		$page_names = [];

		foreach ($events as $event) {
			$page_name = $this->extract_page_name_from_event($event);

			if ($page_name === '' || in_array($page_name, $page_names, true)) {
				continue;
			}

			$page_names[] = $page_name;

			if (count($page_names) >= 12) {
				break;
			}
		}

		return $page_names;
	}

	/**
	 * @param array<int, array<string, mixed>> $events
	 * @return array<string, string>
	 */
	private function get_session_page_map(array $events): array
	{
		$map = [];

		foreach ($events as $event) {
			$session_key = (string) ($event['session_key'] ?? '');

			if ($session_key === '' || isset($map[$session_key])) {
				continue;
			}

			$page_name = $this->extract_page_name_from_event($event);

			if ($page_name === '') {
				continue;
			}

			$map[$session_key] = $page_name;
		}

		return $map;
	}

	/**
	 * @param array<int, array<string, mixed>> $conversations
	 * @return array<string, array<string, mixed>>
	 */
	private function get_conversation_summaries(array $conversations): array
	{
		$summaries = [];

		foreach ($conversations as $conversation) {
			$key = sanitize_text_field((string) ($conversation['conversation_key'] ?? $conversation['id'] ?? ''));

			if ($key === '' || isset($summaries[$key])) {
				continue;
			}

			$summaries[$key] = ConversationMemory::get_summary_record($key);
		}

		return $summaries;
	}

	/**
	 * @param array<string, mixed> $event
	 */
	private function extract_page_name_from_event(array $event): string
	{
		$payload = $this->decode_event_payload((string) ($event['payload'] ?? ''));
		$page_context = is_array($payload['page_context'] ?? null) ? (array) $payload['page_context'] : [];
		$page_name = sanitize_text_field((string) ($payload['page_title'] ?? $page_context['title'] ?? ''));

		if ($page_name !== '') {
			return $page_name;
		}

		return $this->resolve_page_name_from_url((string) ($event['page_url'] ?? ''), (string) ($event['page_type'] ?? ''));
	}

	private function resolve_page_name_from_url(string $url, string $page_type = ''): string
	{
		$url = trim($url);

		if ($url === '') {
			return $page_type !== '' ? $this->format_label($page_type) : '';
		}

		$post_id = url_to_postid($url);

		if ($post_id > 0) {
			$title = get_the_title($post_id);

			if (is_string($title) && $title !== '') {
				return $title;
			}
		}

		$path = trim((string) (wp_parse_url($url, PHP_URL_PATH) ?? ''), '/');

		if ($path === '') {
			return __('Home Page', 'twostars-platform');
		}

		$page = get_page_by_path($path, OBJECT, ['page', 'post']);

		if ($page && ! empty($page->post_title)) {
			return (string) $page->post_title;
		}

		$route_labels = [
			'booking' => __('Booking Page', 'twostars-platform'),
			'contact' => __('Contact Page', 'twostars-platform'),
			'tours' => __('Tours Page', 'twostars-platform'),
			'destinations' => __('Destinations Page', 'twostars-platform'),
			'reviews' => __('Reviews Page', 'twostars-platform'),
			'faq' => __('FAQ Page', 'twostars-platform'),
			'gallery' => __('Gallery Page', 'twostars-platform'),
		];

		foreach ($route_labels as $segment => $label) {
			if (str_contains($path, $segment)) {
				return $label;
			}
		}

		$segments = array_values(array_filter(explode('/', $path)));
		$last_segment = (string) end($segments);

		return $last_segment !== '' ? $this->format_label(str_replace('-', ' ', $last_segment)) : ($page_type !== '' ? $this->format_label($page_type) : '');
	}

	/**
	 * @return array<string, mixed>
	 */
	private function decode_event_payload(string $payload): array
	{
		$decoded = json_decode($payload, true);

		return is_array($decoded) ? $decoded : [];
	}

	/**
	 * @param array<string, mixed> $event
	 * @param array<string, mixed> $payload
	 */
	private function build_event_description(array $event, array $payload, string $page_name = ''): string
	{
		$event_name = (string) ($event['event_name'] ?? '');
		$page_type = (string) ($event['page_type'] ?? '');
		$url = $this->truncate_url((string) ($event['page_url'] ?? ''));

		if ($event_name === 'group_size_adjust' && ! empty($payload['guest_count'])) {
			return sprintf(__('Adjusted planning details for %d guests.', 'twostars-platform'), (int) $payload['guest_count']);
		}

		if ($event_name === 'availability_check') {
			return __('Checked for practical availability, which is usually a strong pre-booking signal.', 'twostars-platform');
		}

		if ($event_name === 'whatsapp_click') {
			return __('Tried to move from browsing into direct contact with the team.', 'twostars-platform');
		}

		if ($event_name === 'booking_started' || $event_name === 'booking_completed') {
			return __('Moved into the booking flow rather than staying in research mode.', 'twostars-platform');
		}

		if ($page_name !== '') {
			return sprintf(__('Signal came from %1$s on a %2$s page.', 'twostars-platform'), $page_name, $page_type !== '' ? strtolower($this->format_label($page_type)) : __('tracked', 'twostars-platform'));
		}

		if ($url !== '') {
			return sprintf(__('Triggered from %1$s on a %2$s page.', 'twostars-platform'), $url, $page_type !== '' ? strtolower($this->format_label($page_type)) : __('tracked', 'twostars-platform'));
		}

		return sprintf(__('Recorded on a %s page.', 'twostars-platform'), $page_type !== '' ? strtolower($this->format_label($page_type)) : __('tracked', 'twostars-platform'));
	}

	private function format_event_label(string $event_name): string
	{
		$labels = [
			'booking_completed' => __('Booking Completed', 'twostars-platform'),
			'lead_created' => __('Lead Created', 'twostars-platform'),
			'booking_started' => __('Booking Started', 'twostars-platform'),
			'whatsapp_click' => __('WhatsApp Click', 'twostars-platform'),
			'availability_check' => __('Availability Check', 'twostars-platform'),
			'booking_click' => __('Booking Click', 'twostars-platform'),
			'lead_form_open' => __('Lead Form Opened', 'twostars-platform'),
			'pricing_interest' => __('Pricing Interest', 'twostars-platform'),
			'comparison_view' => __('Comparison Viewed', 'twostars-platform'),
			'comparison_add' => __('Added To Comparison', 'twostars-platform'),
			'chat_message' => __('Chat Message', 'twostars-platform'),
			'chat_opened' => __('Chat Opened', 'twostars-platform'),
			'date_selector_open' => __('Date Selector Opened', 'twostars-platform'),
			'inclusion_interest' => __('Inclusion Interest', 'twostars-platform'),
			'review_read' => __('Review Read', 'twostars-platform'),
			'gallery_view' => __('Gallery Viewed', 'twostars-platform'),
			'faq_expand' => __('FAQ Opened', 'twostars-platform'),
			'group_size_adjust' => __('Group Size Adjusted', 'twostars-platform'),
			'tour_card_click' => __('Tour Card Click', 'twostars-platform'),
			'hero_cta_click' => __('Hero CTA Click', 'twostars-platform'),
			'destination_card_click' => __('Destination Card Click', 'twostars-platform'),
		];

		return (string) ($labels[$event_name] ?? $this->format_label($event_name));
	}

	private function truncate_url(string $url): string
	{
		if ($url === '') {
			return '';
		}

		$parsed = wp_parse_url($url);
		$path = (string) ($parsed['path'] ?? '');
		$query = ! empty($parsed['query']) ? '?' . (string) $parsed['query'] : '';
		$display = $path !== '' ? $path . $query : $url;

		if (strlen($display) <= 54) {
			return $display;
		}

		return substr($display, 0, 51) . '...';
	}

	private function truncate_session_key(string $session_key): string
	{
		if (strlen($session_key) <= 10) {
			return $session_key;
		}

		return substr($session_key, 0, 10) . '...';
	}

	/**
	 * @return array<int, int>
	 */
	private function decode_id_list(string $value): array
	{
		$decoded = json_decode($value, true);

		if (! is_array($decoded)) {
			return [];
		}

		return array_values(array_filter(array_map('absint', $decoded)));
	}

	private function is_high_intent_score(int $score): bool
	{
		return WebsiteIntelligence::get_stage($score) === 'high_intent';
	}

	/**
	 * @param array<string, mixed> $visitor
	 */
	private function render_visitor_row(array $visitor): void
	{
		$lead_ids = json_decode((string) ($visitor['lead_ids'] ?? '[]'), true);
		$booking_ids = json_decode((string) ($visitor['booking_ids'] ?? '[]'), true);
		$links_count = (is_array($lead_ids) ? count($lead_ids) : 0) + (is_array($booking_ids) ? count($booking_ids) : 0);
		$detail_url = admin_url('admin.php?page=twostars-manager-visitor-detail&visitor_id=' . absint($visitor['id'] ?? 0));
		$location_label = $this->format_location_label($visitor);
		?>
		<article class="twostars-visitor-card">
			<div class="twostars-visitor-card__header">
				<div>
					<p class="twostars-visitor-card__eyebrow"><?php echo ! empty($visitor['is_returning']) ? esc_html__('Returning Visitor', 'twostars-platform') : esc_html__('New Visitor', 'twostars-platform'); ?></p>
					<h3><?php echo esc_html($location_label !== '' ? $location_label : (string) ($visitor['fingerprint_visitor_id'] ?? __('Unknown visitor', 'twostars-platform'))); ?></h3>
				</div>
				<span class="twostars-manager-dashboard-pill twostars-manager-dashboard-pill--neutral"><?php echo esc_html($this->format_stage_label((string) ($visitor['latest_stage'] ?? ''))); ?></span>
			</div>
			<div class="twostars-visitor-card__facts">
				<div><strong><?php esc_html_e('Profile ID', 'twostars-platform'); ?></strong><span><?php echo esc_html((string) ($visitor['fingerprint_visitor_id'] ?? '')); ?></span></div>
				<div><strong><?php esc_html_e('Location Source', 'twostars-platform'); ?></strong><span><?php echo esc_html($this->format_location_source_label((string) ($visitor['location_source'] ?? ''))); ?></span></div>
				<div><strong><?php esc_html_e('Device', 'twostars-platform'); ?></strong><span><?php echo esc_html(trim(((string) ($visitor['device_family'] ?? '')) . ' ' . ((string) ($visitor['browser'] ?? '')))); ?></span></div>
				<div><strong><?php esc_html_e('Persona', 'twostars-platform'); ?></strong><span><?php echo esc_html($this->format_label((string) ($visitor['latest_persona'] ?? ''))); ?></span></div>
				<div><strong><?php esc_html_e('CRM Links', 'twostars-platform'); ?></strong><span><?php echo esc_html((string) $links_count); ?></span></div>
				<div><strong><?php esc_html_e('Last Seen', 'twostars-platform'); ?></strong><span><?php echo esc_html((string) ($visitor['last_seen_at'] ?? '')); ?></span></div>
			</div>
			<div class="twostars-visitor-card__metrics">
				<span><?php echo esc_html(sprintf(__('Sessions: %d', 'twostars-platform'), (int) ($visitor['session_count'] ?? 0))); ?></span>
				<span><?php echo esc_html(sprintf(__('Page views: %d', 'twostars-platform'), (int) ($visitor['page_view_count'] ?? 0))); ?></span>
				<span><?php echo esc_html(sprintf(__('AI messages: %d', 'twostars-platform'), (int) ($visitor['ai_message_count'] ?? 0))); ?></span>
			</div>
			<div class="twostars-visitor-card__actions">
				<a class="button button-primary" href="<?php echo esc_url($detail_url); ?>"><?php esc_html_e('Open Profile', 'twostars-platform'); ?></a>
			</div>
		</article>
		<?php
	}

	/**
	 * @param array<string, mixed> $record
	 */
	private function format_location_label(array $record): string
	{
		return trim(((string) ($record['city'] ?? '')) . ', ' . ((string) ($record['country_name'] ?? '')), ', ');
	}

	private function format_stage_label(string $stage): string
	{
		return $this->format_label($stage !== '' ? $stage : 'unknown');
	}

	private function format_location_source_label(string $source): string
	{
		return $source !== '' ? $this->format_label($source) : __('Unknown', 'twostars-platform');
	}

	private function format_label(string $value): string
	{
		return ucwords(str_replace('_', ' ', $value));
	}

	/**
	 * @param array<int, mixed> $post_ids
	 * @return array<int, string>
	 */
	private function get_post_titles(array $post_ids): array
	{
		$titles = array_map(
			static fn($post_id): string => get_the_title((int) $post_id),
			array_filter(array_map('absint', $post_ids))
		);

		return array_values(array_filter($titles));
	}

	/**
	 * @param array<string, mixed> $context
	 * @param array<string, mixed> $profile
	 * @return array<int, array<string, mixed>>
	 */
	private function get_detail_map_points(array $context, array $profile): array
	{
		$lat = isset($context['city_lat']) ? (float) $context['city_lat'] : 0.0;
		$lng = isset($context['city_lng']) ? (float) $context['city_lng'] : 0.0;

		if ($lat === 0.0 && $lng === 0.0) {
			return [];
		}

		return [
			[
				'id' => (int) ($context['id'] ?? 0),
				'lat' => $lat,
				'lng' => $lng,
				'city' => (string) ($context['city'] ?? ''),
				'countryName' => (string) ($context['country_name'] ?? ''),
				'countryCode' => (string) ($context['country_code'] ?? ''),
				'lastSeenAt' => (string) ($context['last_seen_at'] ?? ''),
				'latestStage' => (string) ($context['latest_stage'] ?? ''),
				'returning' => ! empty($context['is_returning']),
				'locationSource' => $this->format_location_source_label((string) ($context['location_source'] ?? '')),
				'detailUrl' => admin_url('admin.php?page=twostars-manager-visitor-detail&visitor_id=' . absint($profile['id'] ?? 0)),
			],
		];
	}
}
