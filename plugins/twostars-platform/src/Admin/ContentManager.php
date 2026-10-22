<?php
declare(strict_types=1);

namespace TwoStars\Platform\Admin;

use TwoStars\Platform\AI\GeminiConfig;
use TwoStars\Platform\Booking\BookingManager;
use TwoStars\Platform\CRM\CoreCRM;
use TwoStars\Platform\CRM\LeadManager;
use TwoStars\Platform\PostTypes\DestinationMeta;
use TwoStars\Platform\PostTypes\ReviewMeta;
use TwoStars\Platform\PostTypes\TourMeta;
use TwoStars\Platform\Support\Assets;
use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Support\TourData;
use TwoStars\Platform\Visitors\VisitorProfileManager;
use WP_Post;

final class ContentManager implements Service
{
	public function register(): void
	{
		add_action('admin_menu', [$this, 'register_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('edit_form_after_title', [$this, 'render_editor_header']);
		add_action('admin_post_twostars_manager_save_content', [$this, 'handle_save_content']);
		add_action('admin_post_twostars_manager_save_record', [$this, 'handle_save_record']);
		add_action('admin_post_twostars_manager_save_gallery_library', [$this, 'handle_save_gallery_library']);
		add_action('admin_post_twostars_manager_update_review_status', [$this, 'handle_update_review_status']);
		add_action('admin_init', [$this, 'redirect_default_editor']);
	}

	public function register_menu(): void
	{
		add_menu_page(
			__('Two Stars Admin', 'twostars-platform'),
			__('Two Stars Admin', 'twostars-platform'),
			'edit_posts',
			'twostars-manager',
			[$this, 'render_overview_page'],
			'dashicons-layout',
			28
		);

		add_submenu_page(
			'twostars-manager',
			__('Manage Tours', 'twostars-platform'),
			__('Tours', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-tours',
			fn() => $this->render_manager_page('tour')
		);

		add_submenu_page(
			'twostars-manager',
			__('Manage Destinations', 'twostars-platform'),
			__('Destinations', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-destinations',
			fn() => $this->render_manager_page('destination')
		);

		add_submenu_page(
			'twostars-manager',
			__('Gallery Library', 'twostars-platform'),
			__('Gallery Library', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-gallery-library',
			[$this, 'render_gallery_library_page']
		);

		add_submenu_page(
			'twostars-manager',
			__('Manage Reviews', 'twostars-platform'),
			__('Reviews', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-reviews',
			[$this, 'render_reviews_page']
		);

		add_submenu_page(
			'twostars-manager',
			__('Manage Bookings', 'twostars-platform'),
			__('Bookings', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-bookings',
			[$this, 'render_bookings_page']
		);

		add_submenu_page(
			'twostars-manager',
			__('Manage Leads', 'twostars-platform'),
			__('Leads', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-leads',
			[$this, 'render_leads_page']
		);

		add_submenu_page(
			null,
			__('Create Tour', 'twostars-platform'),
			__('Create Tour', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-tour-new',
			fn() => $this->render_form_page('tour')
		);

		add_submenu_page(
			null,
			__('Edit Tour', 'twostars-platform'),
			__('Edit Tour', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-tour-edit',
			fn() => $this->render_form_page('tour', absint($_GET['post_id'] ?? 0))
		);

		add_submenu_page(
			null,
			__('Create Destination', 'twostars-platform'),
			__('Create Destination', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-destination-new',
			fn() => $this->render_form_page('destination')
		);

		add_submenu_page(
			null,
			__('Edit Destination', 'twostars-platform'),
			__('Edit Destination', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-destination-edit',
			fn() => $this->render_form_page('destination', absint($_GET['post_id'] ?? 0))
		);

		add_submenu_page(
			null,
			__('Create Review', 'twostars-platform'),
			__('Create Review', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-review-new',
			fn() => $this->render_form_page('testimonial')
		);

		add_submenu_page(
			null,
			__('Edit Review', 'twostars-platform'),
			__('Edit Review', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-review-edit',
			fn() => $this->render_form_page('testimonial', absint($_GET['post_id'] ?? 0))
		);

		add_submenu_page(
			null,
			__('Create Booking', 'twostars-platform'),
			__('Create Booking', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-booking-new',
			fn() => $this->render_record_form_page('booking')
		);

		add_submenu_page(
			null,
			__('Edit Booking', 'twostars-platform'),
			__('Edit Booking', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-booking-edit',
			fn() => $this->render_record_form_page('booking', absint($_GET['post_id'] ?? 0))
		);

		add_submenu_page(
			null,
			__('Create Lead', 'twostars-platform'),
			__('Create Lead', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-lead-new',
			fn() => $this->render_record_form_page('lead')
		);

		add_submenu_page(
			null,
			__('Edit Lead', 'twostars-platform'),
			__('Edit Lead', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-lead-edit',
			fn() => $this->render_record_form_page('lead', absint($_GET['post_id'] ?? 0))
		);
	}

	public function enqueue_assets(string $hook_suffix): void
	{
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		$is_manager_page = Assets::matches_admin_page($hook_suffix, ['twostars-manager']);
		$is_supported_editor = $screen && in_array((string) $screen->post_type, ['tour', 'destination', 'testimonial'], true)
			&& in_array($hook_suffix, ['post.php', 'post-new.php'], true);

		if (! $is_manager_page && ! $is_supported_editor) {
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

	/**
	 * @param WP_Post $post
	 */
	public function render_editor_header($post): void
	{
		if (! $post instanceof WP_Post || ! in_array($post->post_type, ['tour', 'destination', 'testimonial'], true)) {
			return;
		}

		$manager_url = admin_url('admin.php?page=' . ($post->post_type === 'tour' ? 'twostars-manager-tours' : ($post->post_type === 'testimonial' ? 'twostars-manager-reviews' : 'twostars-manager-destinations')));
		$add_new_url = $this->get_form_url($post->post_type);
		$edit_url = $this->get_form_url($post->post_type, (int) $post->ID);
		$view_url = get_permalink($post);
		$label = $post->post_type === 'tour' ? __('Tour Manager', 'twostars-platform') : ($post->post_type === 'testimonial' ? __('Review Manager', 'twostars-platform') : __('Destination Manager', 'twostars-platform'));
		$help = $post->post_type === 'tour'
			? __('Keep the tour summary, route stops, logistics, and gallery together here so the booking flow and site AI stay accurate.', 'twostars-platform')
			: ($post->post_type === 'testimonial'
				? __('Use this screen to keep review text, rating, source, and homepage social proof clean and easy to manage.', 'twostars-platform')
				: __('Use this screen to keep the destination story, gallery, and visitor context clean and easy to manage.', 'twostars-platform'));
		?>
		<div class="twostars-manager-editor-banner">
			<div class="twostars-manager-editor-banner__copy">
				<p class="twostars-manager-editor-banner__eyebrow"><?php echo esc_html($label); ?></p>
				<h2><?php echo esc_html(get_the_title($post) !== '' ? get_the_title($post) : __('New Entry', 'twostars-platform')); ?></h2>
				<p><?php echo esc_html($help); ?></p>
			</div>
			<div class="twostars-manager-editor-banner__actions">
				<a class="button button-primary" href="<?php echo esc_url($manager_url); ?>"><?php esc_html_e('Open Manager', 'twostars-platform'); ?></a>
				<a class="button" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Open Custom Editor', 'twostars-platform'); ?></a>
				<a class="button" href="<?php echo esc_url($add_new_url); ?>"><?php esc_html_e('Add Another', 'twostars-platform'); ?></a>
				<?php if ($view_url !== false) : ?>
					<a class="button" href="<?php echo esc_url((string) $view_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('View Live', 'twostars-platform'); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public function render_overview_page(): void
	{
		$tour_count = wp_count_posts('tour');
		$destination_count = wp_count_posts('destination');
		$page_count = wp_count_posts('page');
		$post_count = wp_count_posts('post');
		$review_count = wp_count_posts('testimonial');
		$booking_count = wp_count_posts('booking');
		$lead_count = wp_count_posts('lead');
		$visitor_count = VisitorProfileManager::count_profiles();
		$visitor_summary = VisitorProfileManager::get_dashboard_summary();
		$crm_summary = CoreCRM::get_dashboard_summary();
		$settings = GeminiConfig::get_settings();
		$homepage_id = get_option('show_on_front') === 'page' ? (int) get_option('page_on_front') : 0;
		$homepage = $homepage_id > 0 ? get_post($homepage_id) : null;
		$booking_page = get_page_by_path('booking');
		$whatsapp_number = sanitize_text_field((string) ($settings['whatsapp_number'] ?? ''));
		$visitor_tracking_enabled = ! empty($settings['visitor_tracking_enabled']);
		$pending_bookings = $this->count_records_by_meta_query(
			'booking',
			[
				[
					'key'   => '_twostars_booking_status',
					'value' => 'pending',
				],
			]
		);
		$overdue_leads = $this->count_records_by_meta_query(
			'lead',
			[
				'relation' => 'AND',
				[
					'key'     => '_twostars_lead_follow_up_date',
					'value'   => wp_date('Y-m-d'),
					'compare' => '<',
					'type'    => 'DATE',
				],
				[
					'key'     => '_twostars_lead_status',
					'value'   => ['won', 'lost'],
					'compare' => 'NOT IN',
				],
			]
		);
		$draft_content_count = (int) (($page_count->draft ?? 0) + ($post_count->draft ?? 0) + ($tour_count->draft ?? 0) + ($destination_count->draft ?? 0));
		$pending_reviews = (int) ($review_count->draft ?? 0);
		$total_media = array_sum(array_map('intval', (array) wp_count_attachments()));
		$menus_total = count(wp_get_nav_menus());
		$recent_content = get_posts(
			[
				'post_type'      => ['page', 'post', 'tour', 'destination'],
				'post_status'    => ['publish', 'draft', 'private'],
				'posts_per_page' => 6,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			]
		);
		$recent_enquiries = get_posts(
			[
				'post_type'      => ['booking', 'lead'],
				'post_status'    => ['private', 'publish', 'draft'],
				'posts_per_page' => 6,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);
		$management_cards = [
			[
				'eyebrow' => __('Website Content', 'twostars-platform'),
				'title' => __('Pages and Blog', 'twostars-platform'),
				'description' => __('Update the homepage, booking page, service pages, and blog posts without digging through the default WordPress menus.', 'twostars-platform'),
				'stats' => [
					sprintf(__('Pages: %d', 'twostars-platform'), (int) ($page_count->publish ?? 0)),
					sprintf(__('Posts: %d', 'twostars-platform'), (int) ($post_count->publish ?? 0)),
					sprintf(__('Drafts: %d', 'twostars-platform'), (int) (($page_count->draft ?? 0) + ($post_count->draft ?? 0))),
				],
				'actions' => array_filter(
					[
						[
							'label' => __('Open Pages', 'twostars-platform'),
							'url' => admin_url('edit.php?post_type=page'),
							'primary' => true,
						],
						$homepage instanceof WP_Post ? [
							'label' => __('Edit Homepage', 'twostars-platform'),
							'url' => (string) get_edit_post_link($homepage->ID),
						] : null,
						[
							'label' => __('Open Posts', 'twostars-platform'),
							'url' => admin_url('edit.php'),
						],
					]
				),
			],
			[
				'eyebrow' => __('Travel Products', 'twostars-platform'),
				'title' => __('Tours and Destinations', 'twostars-platform'),
				'description' => __('Manage the actual bookable experiences and destination pages that shape the public site and booking flow.', 'twostars-platform'),
				'stats' => [
					sprintf(__('Tours: %d', 'twostars-platform'), (int) ($tour_count->publish ?? 0)),
					sprintf(__('Destinations: %d', 'twostars-platform'), (int) ($destination_count->publish ?? 0)),
					sprintf(__('Drafts: %d', 'twostars-platform'), (int) (($tour_count->draft ?? 0) + ($destination_count->draft ?? 0))),
				],
				'actions' => [
					[
						'label' => __('Open Tours', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-manager-tours'),
						'primary' => true,
					],
					[
						'label' => __('Open Destinations', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-manager-destinations'),
					],
				],
			],
			[
				'eyebrow' => __('Trust and Assets', 'twostars-platform'),
				'title' => __('Reviews and Media', 'twostars-platform'),
				'description' => __('Keep social proof fresh, approve incoming reviews, and manage the image library used across tours, destinations, and pages.', 'twostars-platform'),
				'stats' => [
					sprintf(__('Published Reviews: %d', 'twostars-platform'), (int) ($review_count->publish ?? 0)),
					sprintf(__('Pending Reviews: %d', 'twostars-platform'), $pending_reviews),
					sprintf(__('Media Items: %d', 'twostars-platform'), $total_media),
				],
				'actions' => [
					[
						'label' => __('Open Reviews', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-manager-reviews'),
						'primary' => true,
					],
					[
						'label' => __('Media Library', 'twostars-platform'),
						'url' => admin_url('upload.php'),
					],
				],
			],
			[
				'eyebrow' => __('Sales and Follow-up', 'twostars-platform'),
				'title' => __('Bookings and Leads', 'twostars-platform'),
				'description' => __('Review new enquiries, confirm booking requests, and keep the team on top of overdue follow-up before opportunities go cold.', 'twostars-platform'),
				'stats' => [
					sprintf(__('Pending Bookings: %d', 'twostars-platform'), $pending_bookings),
					sprintf(__('Total Leads: %d', 'twostars-platform'), (int) (($lead_count->private ?? 0) + ($lead_count->publish ?? 0))),
					sprintf(__('Overdue Leads: %d', 'twostars-platform'), $overdue_leads),
				],
				'actions' => [
					[
						'label' => __('Open Bookings', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-manager-bookings'),
						'primary' => true,
					],
					[
						'label' => __('Open Leads', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-manager-leads'),
					],
					[
						'label' => __('CRM Dashboard', 'twostars-platform'),
						'url' => admin_url('edit.php?post_type=lead&page=twostars-crm-dashboard'),
					],
				],
			],
			[
				'eyebrow' => __('Connected CRM', 'twostars-platform'),
				'title' => __('Customers and Pipeline', 'twostars-platform'),
				'description' => __('Work from the synchronized CRM layer that ties leads, bookings, and visitor identity into one operator workflow.', 'twostars-platform'),
				'stats' => [
					sprintf(__('Contacts: %d', 'twostars-platform'), (int) ($crm_summary['contacts'] ?? 0)),
					sprintf(__('Opportunities: %d', 'twostars-platform'), (int) ($crm_summary['opportunities'] ?? 0)),
					sprintf(__('Connected Visitors: %d', 'twostars-platform'), (int) ($crm_summary['connected_visitors'] ?? 0)),
				],
				'actions' => [
					[
						'label' => __('Open CRM Hub', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-manager-crm-hub'),
						'primary' => true,
					],
					[
						'label' => __('Open Leads', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-manager-leads'),
					],
					[
						'label' => __('Open Bookings', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-manager-bookings'),
					],
				],
			],
			[
				'eyebrow' => __('Audience Insight', 'twostars-platform'),
				'title' => __('Visitors and Intent', 'twostars-platform'),
				'description' => __('See where visitors come from, which sessions are returning, and which high-intent journeys have not yet turned into bookings.', 'twostars-platform'),
				'stats' => [
					sprintf(__('Profiles: %d', 'twostars-platform'), $visitor_count),
					sprintf(__('Returning: %d', 'twostars-platform'), (int) ($visitor_summary['returning'] ?? 0)),
					sprintf(__('High Intent: %d', 'twostars-platform'), (int) ($visitor_summary['high_intent_without_booking'] ?? 0)),
				],
				'actions' => [
					[
						'label' => __('Open Visitors', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-manager-visitors'),
						'primary' => true,
					],
					[
						'label' => __('View Live Site', 'twostars-platform'),
						'url' => home_url('/'),
						'target' => '_blank',
					],
				],
			],
			[
				'eyebrow' => __('Setup and Navigation', 'twostars-platform'),
				'title' => __('Settings and Menus', 'twostars-platform'),
				'description' => __('Use this area for business details, AI setup, visitor tracking, menus, and the native WordPress tools that still matter for site administration.', 'twostars-platform'),
				'stats' => [
					sprintf(__('Menus: %d', 'twostars-platform'), $menus_total),
					GeminiConfig::is_enabled() ? __('AI Assistant Ready', 'twostars-platform') : __('AI Assistant Needs Setup', 'twostars-platform'),
					$visitor_tracking_enabled ? __('Visitor Tracking On', 'twostars-platform') : __('Visitor Tracking Off', 'twostars-platform'),
				],
				'actions' => [
					[
						'label' => __('Platform Settings', 'twostars-platform'),
						'url' => admin_url('admin.php?page=twostars-platform'),
						'primary' => true,
					],
					[
						'label' => __('Menus', 'twostars-platform'),
						'url' => admin_url('nav-menus.php'),
					],
					[
						'label' => __('WordPress Dashboard', 'twostars-platform'),
						'url' => admin_url('index.php'),
					],
				],
			],
		];
		$attention_items = [
			[
				'title' => __('Pending booking requests', 'twostars-platform'),
				'value' => $pending_bookings,
				'detail' => __('New booking requests should be confirmed quickly so availability and pickup timing stay accurate.', 'twostars-platform'),
				'url' => admin_url('admin.php?page=twostars-manager-bookings'),
				'action' => __('Open bookings', 'twostars-platform'),
				'tone' => $pending_bookings > 0 ? 'warning' : 'good',
			],
			[
				'title' => __('Overdue lead follow-ups', 'twostars-platform'),
				'value' => $overdue_leads,
				'detail' => __('These leads have a follow-up date in the past and are still not marked won or lost.', 'twostars-platform'),
				'url' => admin_url('edit.php?post_type=lead&page=twostars-crm-dashboard'),
				'action' => __('Open CRM dashboard', 'twostars-platform'),
				'tone' => $overdue_leads > 0 ? 'warning' : 'good',
			],
			[
				'title' => __('Draft content waiting for review', 'twostars-platform'),
				'value' => $draft_content_count,
				'detail' => __('Draft pages, posts, tours, and destinations still need a final check before publishing.', 'twostars-platform'),
				'url' => admin_url('edit.php?post_type=page'),
				'action' => __('Review content', 'twostars-platform'),
				'tone' => $draft_content_count > 0 ? 'neutral' : 'good',
			],
			[
				'title' => __('Pending guest reviews', 'twostars-platform'),
				'value' => $pending_reviews,
				'detail' => __('Website-submitted reviews remain in draft until someone approves or rejects them.', 'twostars-platform'),
				'url' => admin_url('admin.php?page=twostars-manager-reviews&review_status=draft'),
				'action' => __('Review submissions', 'twostars-platform'),
				'tone' => $pending_reviews > 0 ? 'neutral' : 'good',
			],
		];
		$setup_items = [
			[
				'label' => __('Homepage assigned', 'twostars-platform'),
				'status' => $homepage instanceof WP_Post ? __('Ready', 'twostars-platform') : __('Needs setup', 'twostars-platform'),
				'detail' => $homepage instanceof WP_Post ? $homepage->post_title : __('Choose a static homepage so the main site entry point is controlled.', 'twostars-platform'),
				'url' => $homepage instanceof WP_Post ? (string) get_edit_post_link($homepage->ID) : admin_url('options-reading.php'),
				'action' => $homepage instanceof WP_Post ? __('Edit homepage', 'twostars-platform') : __('Choose homepage', 'twostars-platform'),
				'tone' => $homepage instanceof WP_Post ? 'good' : 'warning',
			],
			[
				'label' => __('Booking page available', 'twostars-platform'),
				'status' => $booking_page instanceof WP_Post ? __('Ready', 'twostars-platform') : __('Missing', 'twostars-platform'),
				'detail' => $booking_page instanceof WP_Post ? $booking_page->post_title : __('The custom booking experience expects a page with the slug "booking".', 'twostars-platform'),
				'url' => $booking_page instanceof WP_Post ? (string) get_edit_post_link($booking_page->ID) : admin_url('edit.php?post_type=page'),
				'action' => $booking_page instanceof WP_Post ? __('Edit booking page', 'twostars-platform') : __('Open pages', 'twostars-platform'),
				'tone' => $booking_page instanceof WP_Post ? 'good' : 'warning',
			],
			[
				'label' => __('WhatsApp contact configured', 'twostars-platform'),
				'status' => $whatsapp_number !== '' ? __('Ready', 'twostars-platform') : __('Needs setup', 'twostars-platform'),
				'detail' => $whatsapp_number !== '' ? $whatsapp_number : __('Add the business WhatsApp number in platform settings for direct contact actions.', 'twostars-platform'),
				'url' => admin_url('admin.php?page=twostars-platform'),
				'action' => __('Open settings', 'twostars-platform'),
				'tone' => $whatsapp_number !== '' ? 'good' : 'warning',
			],
			[
				'label' => __('AI assistant configured', 'twostars-platform'),
				'status' => GeminiConfig::is_enabled() ? __('Ready', 'twostars-platform') : __('Needs setup', 'twostars-platform'),
				'detail' => GeminiConfig::is_enabled() ? GeminiConfig::get_flash_model() : __('Enable the AI assistant and add a Gemini API key before using visitor-side AI features.', 'twostars-platform'),
				'url' => admin_url('admin.php?page=twostars-platform'),
				'action' => __('Configure AI', 'twostars-platform'),
				'tone' => GeminiConfig::is_enabled() ? 'good' : 'warning',
			],
			[
				'label' => __('Visitor tracking enabled', 'twostars-platform'),
				'status' => $visitor_tracking_enabled ? __('On', 'twostars-platform') : __('Off', 'twostars-platform'),
				'detail' => $visitor_tracking_enabled ? __('Tracking and visitor enrichment are available for the intelligence views.', 'twostars-platform') : __('Turn tracking on if you want visitor profiles, map data, and intent summaries.', 'twostars-platform'),
				'url' => admin_url('admin.php?page=twostars-platform'),
				'action' => __('Review tracking settings', 'twostars-platform'),
				'tone' => $visitor_tracking_enabled ? 'good' : 'neutral',
			],
		];
		?>
		<div class="wrap twostars-manager">
			<div class="twostars-manager__hero twostars-manager__hero--dashboard">
				<div class="twostars-manager__hero-main">
					<div class="twostars-manager__hero-copy">
						<p class="twostars-manager__eyebrow"><?php esc_html_e('Two Stars Admin', 'twostars-platform'); ?></p>
						<h1><?php esc_html_e('Site Management Dashboard', 'twostars-platform'); ?></h1>
						<p><?php esc_html_e('This is the custom Two Stars workspace for everyday site management. Use it for content, enquiries, visitor insight, and key setup tasks, then jump back to the native WordPress dashboard whenever you need the standard tools.', 'twostars-platform'); ?></p>
					</div>
				</div>
				<div class="twostars-manager__hero-actions twostars-manager__hero-actions--stacked">
					<a class="button" href="<?php echo esc_url(admin_url('index.php')); ?>"><?php esc_html_e('Back to WordPress Dashboard', 'twostars-platform'); ?></a>
					<a class="button button-primary button-hero" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener"><?php esc_html_e('View Live Site', 'twostars-platform'); ?></a>
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-platform')); ?>"><?php esc_html_e('Platform Settings', 'twostars-platform'); ?></a>
				</div>
				<div class="twostars-manager__stats">
					<div class="twostars-manager__stat">
						<span class="twostars-manager__stat-value"><?php echo esc_html((string) ((int) ($page_count->publish ?? 0))); ?></span>
						<span class="twostars-manager__stat-label"><?php esc_html_e('Published Pages', 'twostars-platform'); ?></span>
					</div>
					<div class="twostars-manager__stat">
						<span class="twostars-manager__stat-value"><?php echo esc_html((string) ((int) ($tour_count->publish ?? 0))); ?></span>
						<span class="twostars-manager__stat-label"><?php esc_html_e('Published Tours', 'twostars-platform'); ?></span>
					</div>
					<div class="twostars-manager__stat">
						<span class="twostars-manager__stat-value"><?php echo esc_html((string) ((int) ($destination_count->publish ?? 0))); ?></span>
						<span class="twostars-manager__stat-label"><?php esc_html_e('Published Destinations', 'twostars-platform'); ?></span>
					</div>
					<div class="twostars-manager__stat">
						<span class="twostars-manager__stat-value"><?php echo esc_html((string) $draft_content_count); ?></span>
						<span class="twostars-manager__stat-label"><?php esc_html_e('Draft Content', 'twostars-platform'); ?></span>
					</div>
					<div class="twostars-manager__stat">
						<span class="twostars-manager__stat-value"><?php echo esc_html((string) $pending_bookings); ?></span>
						<span class="twostars-manager__stat-label"><?php esc_html_e('Pending Bookings', 'twostars-platform'); ?></span>
					</div>
					<div class="twostars-manager__stat">
						<span class="twostars-manager__stat-value"><?php echo esc_html((string) $overdue_leads); ?></span>
						<span class="twostars-manager__stat-label"><?php esc_html_e('Overdue Leads', 'twostars-platform'); ?></span>
					</div>
				</div>
			</div>

			<div class="twostars-manager__dashboard-grid">
				<?php foreach ($management_cards as $card) : ?>
					<?php $this->render_dashboard_card($card); ?>
				<?php endforeach; ?>
			</div>

			<div class="twostars-manager__dashboard-panels">
				<section class="twostars-manager-dashboard-panel">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Needs Attention', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('These are the items most likely to need action first when you log in.', 'twostars-platform'); ?></p>
					</div>
					<ul class="twostars-manager-dashboard-list">
						<?php foreach ($attention_items as $item) : ?>
							<li class="twostars-manager-dashboard-list__item">
								<div class="twostars-manager-dashboard-list__head">
									<strong><?php echo esc_html((string) $item['title']); ?></strong>
									<span class="twostars-manager-dashboard-pill twostars-manager-dashboard-pill--<?php echo esc_attr((string) $item['tone']); ?>">
										<?php echo esc_html((string) $item['value']); ?>
									</span>
								</div>
								<p><?php echo esc_html((string) $item['detail']); ?></p>
								<a href="<?php echo esc_url((string) $item['url']); ?>"><?php echo esc_html((string) $item['action']); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>

				<section class="twostars-manager-dashboard-panel">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Setup Checklist', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('A beginner-friendly check of the core pieces that keep the site working smoothly.', 'twostars-platform'); ?></p>
					</div>
					<ul class="twostars-manager-dashboard-list">
						<?php foreach ($setup_items as $item) : ?>
							<li class="twostars-manager-dashboard-list__item">
								<div class="twostars-manager-dashboard-list__head">
									<strong><?php echo esc_html((string) $item['label']); ?></strong>
									<span class="twostars-manager-dashboard-pill twostars-manager-dashboard-pill--<?php echo esc_attr((string) $item['tone']); ?>">
										<?php echo esc_html((string) $item['status']); ?>
									</span>
								</div>
								<p><?php echo esc_html((string) $item['detail']); ?></p>
								<a href="<?php echo esc_url((string) $item['url']); ?>"><?php echo esc_html((string) $item['action']); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>

				<section class="twostars-manager-dashboard-panel">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Recent Content Updates', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('The latest pages, posts, tours, and destinations updated on the site.', 'twostars-platform'); ?></p>
					</div>
					<ul class="twostars-manager-dashboard-list">
						<?php if ($recent_content === []) : ?>
							<li class="twostars-manager-dashboard-list__item">
								<p><?php esc_html_e('No recent content updates found yet.', 'twostars-platform'); ?></p>
							</li>
						<?php endif; ?>
						<?php foreach ($recent_content as $content_post) : ?>
							<?php $post_type_object = get_post_type_object($content_post->post_type); ?>
							<li class="twostars-manager-dashboard-list__item">
								<div class="twostars-manager-dashboard-list__head">
									<strong><?php echo esc_html($content_post->post_title !== '' ? $content_post->post_title : __('Untitled item', 'twostars-platform')); ?></strong>
									<span class="twostars-manager-dashboard-pill twostars-manager-dashboard-pill--neutral">
										<?php echo esc_html($post_type_object ? $post_type_object->labels->singular_name : ucfirst($content_post->post_type)); ?>
									</span>
								</div>
								<p><?php echo esc_html(sprintf(__('Last updated %s', 'twostars-platform'), wp_date('Y-m-d H:i', strtotime((string) $content_post->post_modified)))); ?></p>
								<a href="<?php echo esc_url((string) get_edit_post_link($content_post->ID)); ?>"><?php esc_html_e('Open item', 'twostars-platform'); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>

				<section class="twostars-manager-dashboard-panel">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Recent Enquiries', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('The newest leads and booking records captured by the site.', 'twostars-platform'); ?></p>
					</div>
					<ul class="twostars-manager-dashboard-list">
						<?php if ($recent_enquiries === []) : ?>
							<li class="twostars-manager-dashboard-list__item">
								<p><?php esc_html_e('No bookings or leads have been recorded yet.', 'twostars-platform'); ?></p>
							</li>
						<?php endif; ?>
						<?php foreach ($recent_enquiries as $record) : ?>
							<?php $record_is_booking = $record->post_type === 'booking'; ?>
							<?php $record_name = (string) get_post_meta($record->ID, $record_is_booking ? '_twostars_booking_full_name' : '_twostars_lead_full_name', true); ?>
							<?php $record_status = (string) get_post_meta($record->ID, $record_is_booking ? '_twostars_booking_status' : '_twostars_lead_status', true); ?>
							<?php $record_label = $record_is_booking ? __('Booking', 'twostars-platform') : __('Lead', 'twostars-platform'); ?>
							<li class="twostars-manager-dashboard-list__item">
								<div class="twostars-manager-dashboard-list__head">
									<strong><?php echo esc_html($record_name !== '' ? $record_name : $record->post_title); ?></strong>
									<span class="twostars-manager-dashboard-pill twostars-manager-dashboard-pill--neutral">
										<?php echo esc_html($record_label); ?>
									</span>
								</div>
								<p><?php echo esc_html(sprintf(__('Status: %1$s · Created %2$s', 'twostars-platform'), $record_status !== '' ? ucfirst(str_replace('_', ' ', $record_status)) : __('New', 'twostars-platform'), wp_date('Y-m-d H:i', strtotime((string) $record->post_date)))); ?></p>
								<a href="<?php echo esc_url($this->get_form_url($record->post_type, (int) $record->ID)); ?>"><?php esc_html_e('Open record', 'twostars-platform'); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			</div>
		</div>
		<?php
	}

	public function render_gallery_library_page(): void
	{
		$image_ids = $this->get_gallery_library_images();
		$videos    = $this->get_gallery_library_videos();
		?>
		<div class="wrap twostars-manager">
			<?php $this->render_notice(); ?>
			<div class="twostars-manager__hero twostars-manager__hero--compact">
				<div class="twostars-manager__hero-copy">
					<p class="twostars-manager__eyebrow"><?php esc_html_e('Gallery Library', 'twostars-platform'); ?></p>
					<h1><?php esc_html_e('Two Stars Gallery Media', 'twostars-platform'); ?></h1>
					<p><?php esc_html_e('Add, remove, and reorder shared gallery images and videos from one central Two Stars Admin workspace. The gallery Elementor widget can pull directly from this mixed-media library.', 'twostars-platform'); ?></p>
				</div>
				<div class="twostars-manager__hero-actions twostars-manager__hero-actions--stacked">
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager')); ?>"><?php esc_html_e('Back to Two Stars Admin', 'twostars-platform'); ?></a>
					<a class="button button-primary button-hero" href="<?php echo esc_url(home_url('/gallery/')); ?>" target="_blank" rel="noopener"><?php esc_html_e('View Gallery Page', 'twostars-platform'); ?></a>
				</div>
			</div>

			<form class="twostars-manager-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="twostars_manager_save_gallery_library">
				<?php wp_nonce_field('twostars_manager_save_gallery_library', 'twostars_manager_gallery_library_nonce'); ?>

				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Gallery Images', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('Choose images from the media library, drag thumbnails to reorder them, and save when the sequence is right.', 'twostars-platform'); ?></p>
					</div>
					<?php $this->render_media_field('twostars_gallery_library_images', $image_ids); ?>
				</section>

				<section class="twostars-manager-form__section">
					<div class="twostars-manager-form__section-head">
						<h2><?php esc_html_e('Gallery Videos', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('Add shared gallery videos by choosing a video from the WordPress media library or pasting a direct video URL. Reorder rows to control video sequence, and set where each video should appear in the gallery grid.', 'twostars-platform'); ?></p>
					</div>
					<?php $this->render_gallery_video_library_field('twostars_gallery_library_videos', $videos); ?>
				</section>

				<p>
					<button type="submit" class="button button-primary button-hero"><?php esc_html_e('Save Gallery Library', 'twostars-platform'); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	private function render_gallery_video_library_field(string $field_id, array $videos): void
	{
		if ($videos === []) {
			$videos = [
				[
					'video_url'      => '',
					'video_caption'  => '',
					'video_category' => '',
					'video_position' => 1,
					'video_autoplay' => 'yes',
					'video_muted'    => 'yes',
					'video_loop'     => 'yes',
				],
			];
		}

		?>
		<div class="twostars-gallery-video-library" data-twostars-gallery-video-library data-target-input="<?php echo esc_attr($field_id); ?>">
			<input type="hidden" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" value="<?php echo esc_attr((string) wp_json_encode($videos)); ?>">
			<div class="twostars-gallery-video-library__list" data-twostars-gallery-video-list>
				<?php foreach ($videos as $index => $video) : ?>
					<?php $this->render_gallery_video_library_item(is_array($video) ? $video : [], $index); ?>
				<?php endforeach; ?>
			</div>
			<div class="twostars-gallery-video-library__actions">
				<button type="button" class="button button-secondary" data-twostars-gallery-video-add><?php esc_html_e('Add Video', 'twostars-platform'); ?></button>
				<button type="button" class="button-link-delete" data-twostars-gallery-video-clear><?php esc_html_e('Clear Videos', 'twostars-platform'); ?></button>
			</div>
		</div>
		<?php
	}

	private function render_gallery_video_library_item(array $video, int $index): void
	{
		$video_url      = esc_url((string) ($video['video_url'] ?? ''));
		$video_caption  = sanitize_text_field((string) ($video['video_caption'] ?? ''));
		$video_category = sanitize_title((string) ($video['video_category'] ?? ''));
		$video_position = max(1, absint($video['video_position'] ?? ($index + 1)));
		$video_autoplay = (($video['video_autoplay'] ?? 'yes') === 'yes');
		$video_muted    = (($video['video_muted'] ?? 'yes') === 'yes');
		$video_loop     = (($video['video_loop'] ?? 'yes') === 'yes');
		?>
		<div class="twostars-gallery-video-library__item" data-twostars-gallery-video-item>
			<div class="twostars-gallery-video-library__handle" data-twostars-gallery-video-handle aria-hidden="true">::</div>
			<div class="twostars-gallery-video-library__preview" data-twostars-gallery-video-preview>
				<?php if ($video_url !== '') : ?>
					<video src="<?php echo esc_url($video_url); ?>" controls preload="metadata" playsinline></video>
				<?php else : ?>
					<div class="twostars-gallery-video-library__empty"><?php esc_html_e('Choose a WordPress video or paste a direct video URL.', 'twostars-platform'); ?></div>
				<?php endif; ?>
			</div>
			<div class="twostars-gallery-video-library__body">
				<div class="twostars-gallery-video-library__row">
					<label class="twostars-gallery-video-library__field">
						<span><?php esc_html_e('Video URL', 'twostars-platform'); ?></span>
						<input type="url" value="<?php echo esc_attr($video_url); ?>" placeholder="https://..." data-video-field="video_url">
					</label>
					<div class="twostars-gallery-video-library__inline-actions">
						<button type="button" class="button button-secondary" data-twostars-gallery-video-media><?php esc_html_e('Choose Uploaded Video', 'twostars-platform'); ?></button>
						<button type="button" class="button-link-delete" data-twostars-gallery-video-remove><?php esc_html_e('Remove', 'twostars-platform'); ?></button>
					</div>
				</div>
				<div class="twostars-gallery-video-library__grid">
					<label class="twostars-gallery-video-library__field">
						<span><?php esc_html_e('Caption', 'twostars-platform'); ?></span>
						<input type="text" value="<?php echo esc_attr($video_caption); ?>" data-video-field="video_caption">
					</label>
					<label class="twostars-gallery-video-library__field">
						<span><?php esc_html_e('Category Slug', 'twostars-platform'); ?></span>
						<input type="text" value="<?php echo esc_attr($video_category); ?>" placeholder="<?php esc_attr_e('wildlife', 'twostars-platform'); ?>" data-video-field="video_category">
					</label>
					<label class="twostars-gallery-video-library__field twostars-gallery-video-library__field--compact">
						<span><?php esc_html_e('Insert Before Image #', 'twostars-platform'); ?></span>
						<input type="number" min="1" step="1" value="<?php echo esc_attr((string) $video_position); ?>" data-video-field="video_position">
					</label>
				</div>
				<div class="twostars-gallery-video-library__toggles">
					<label><input type="checkbox" value="yes" data-video-field="video_autoplay" <?php checked($video_autoplay); ?>> <?php esc_html_e('Autoplay', 'twostars-platform'); ?></label>
					<label><input type="checkbox" value="yes" data-video-field="video_muted" <?php checked($video_muted); ?>> <?php esc_html_e('Muted', 'twostars-platform'); ?></label>
					<label><input type="checkbox" value="yes" data-video-field="video_loop" <?php checked($video_loop); ?>> <?php esc_html_e('Loop', 'twostars-platform'); ?></label>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_manager_page(string $post_type): void
	{
		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => ['publish', 'draft', 'private'],
				'posts_per_page' => 100,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			]
		);

		$is_tour = $post_type === 'tour';
		$is_review = $post_type === 'testimonial';
		$title = $is_tour
			? __('Manage Tours', 'twostars-platform')
			: ($is_review ? __('Manage Reviews', 'twostars-platform') : __('Manage Destinations', 'twostars-platform'));
		$description = $is_tour
			? __('Keep the real Two Stars tour catalog easy to edit, visually complete, and booking-ready.', 'twostars-platform')
			: ($is_review
				? __('Review incoming guest feedback, approve the strongest reviews, and keep homepage trust signals current.', 'twostars-platform')
				: __('Manage the stop pages that support the tour stories, route logic, and destination SEO.', 'twostars-platform'));
		$add_url = $this->get_form_url($post_type);
		?>
		<div class="wrap twostars-manager">
			<?php $this->render_notice(); ?>
			<div class="twostars-manager__hero twostars-manager__hero--compact">
				<div class="twostars-manager__hero-main">
					<div class="twostars-manager__hero-copy">
						<p class="twostars-manager__eyebrow"><?php echo esc_html($is_tour ? __('Tours', 'twostars-platform') : ($is_review ? __('Reviews', 'twostars-platform') : __('Destinations', 'twostars-platform'))); ?></p>
						<h1><?php echo esc_html($title); ?></h1>
						<p><?php echo esc_html($description); ?></p>
					</div>
				</div>
				<div class="twostars-manager__hero-actions">
					<a class="button button-primary button-hero" href="<?php echo esc_url($add_url); ?>">
						<?php echo esc_html($is_tour ? __('Add New Tour', 'twostars-platform') : ($is_review ? __('Add New Review', 'twostars-platform') : __('Add New Destination', 'twostars-platform'))); ?>
					</a>
				</div>
			</div>

			<div class="twostars-manager__card-grid">
				<?php foreach ($posts as $post) : ?>
					<?php $this->render_post_card($post); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	public function render_reviews_page(): void
	{
		$status_filter = sanitize_key((string) ($_GET['review_status'] ?? 'all'));
		$allowed_filters = ['all', 'draft', 'publish'];

		if (! in_array($status_filter, $allowed_filters, true)) {
			$status_filter = 'all';
		}

		$query_status = $status_filter === 'all' ? ['publish', 'draft', 'private'] : [$status_filter];
		$reviews = get_posts(
			[
				'post_type'      => 'testimonial',
				'post_status'    => $query_status,
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$counts = wp_count_posts('testimonial');
		$all_count = (int) (($counts->publish ?? 0) + ($counts->draft ?? 0) + ($counts->private ?? 0));
		$draft_count = (int) ($counts->draft ?? 0);
		$publish_count = (int) ($counts->publish ?? 0);
		$featured_count = count(
			get_posts(
				[
					'post_type'      => 'testimonial',
					'post_status'    => ['publish', 'draft', 'private'],
					'posts_per_page' => 200,
					'fields'         => 'ids',
					'meta_key'       => '_twostars_review_featured',
					'meta_value'     => 'featured',
				]
			)
		);
		$base_url = admin_url('admin.php?page=twostars-manager-reviews');
		?>
		<div class="wrap twostars-manager">
			<?php $this->render_notice(); ?>
			<div class="twostars-manager__hero twostars-manager__hero--compact">
				<div class="twostars-manager__hero-main">
					<div class="twostars-manager__hero-copy">
						<p class="twostars-manager__eyebrow"><?php esc_html_e('Reviews', 'twostars-platform'); ?></p>
						<h1><?php esc_html_e('Manage Reviews', 'twostars-platform'); ?></h1>
						<p><?php esc_html_e('Only approved reviews are published and shown on the homepage. New website submissions stay in draft until the team approves them.', 'twostars-platform'); ?></p>
					</div>
				</div>
				<div class="twostars-manager__hero-actions">
					<a class="button button-primary button-hero" href="<?php echo esc_url($this->get_form_url('testimonial')); ?>">
						<?php esc_html_e('Add New Review', 'twostars-platform'); ?>
					</a>
				</div>
				<div class="twostars-manager__stats">
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) $all_count); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Total Reviews', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) $publish_count); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Approved', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) $draft_count); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Pending Approval', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) $featured_count); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Homepage Featured', 'twostars-platform'); ?></span></div>
				</div>
			</div>

			<div class="twostars-manager__review-filters">
				<a class="twostars-manager__filter-pill<?php echo $status_filter === 'all' ? ' is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('review_status', 'all', $base_url)); ?>">
					<?php echo esc_html(sprintf(__('All (%d)', 'twostars-platform'), $all_count)); ?>
				</a>
				<a class="twostars-manager__filter-pill<?php echo $status_filter === 'draft' ? ' is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('review_status', 'draft', $base_url)); ?>">
					<?php echo esc_html(sprintf(__('Pending Approval (%d)', 'twostars-platform'), $draft_count)); ?>
				</a>
				<a class="twostars-manager__filter-pill<?php echo $status_filter === 'publish' ? ' is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('review_status', 'publish', $base_url)); ?>">
					<?php echo esc_html(sprintf(__('Approved (%d)', 'twostars-platform'), $publish_count)); ?>
				</a>
			</div>

			<section class="twostars-manager-form__section">
				<div class="twostars-manager-form__section-head">
					<h2><?php esc_html_e('Review Board', 'twostars-platform'); ?></h2>
					<p><?php esc_html_e('Approve strong feedback, spot low-quality submissions quickly, and keep the homepage social proof fresh without opening each review first.', 'twostars-platform'); ?></p>
				</div>
				<div class="twostars-manager__review-board">
					<?php if ($reviews === []) : ?>
						<p><?php esc_html_e('No reviews found for this filter.', 'twostars-platform'); ?></p>
					<?php endif; ?>
					<?php foreach ($reviews as $review) : ?>
						<?php $this->render_review_board_card($review); ?>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php
	}

	private function render_form_page(string $post_type, int $post_id = 0): void
	{
		$post = $post_id > 0 ? get_post($post_id) : null;

		if ($post_id > 0 && (! $post instanceof WP_Post || $post->post_type !== $post_type)) {
			wp_die(esc_html__('That content item could not be found.', 'twostars-platform'));
		}

		$post = $post instanceof WP_Post ? $post : null;
		$is_tour = $post_type === 'tour';
		$is_review = $post_type === 'testimonial';
		$title = $post ? $post->post_title : ($is_tour ? __('New Tour', 'twostars-platform') : ($is_review ? __('New Review', 'twostars-platform') : __('New Destination', 'twostars-platform')));
		$field_definitions = $is_tour ? TourMeta::get_manager_field_definitions() : ($is_review ? ReviewMeta::get_manager_field_definitions() : DestinationMeta::get_manager_field_definitions());
		$featured_image_id = $post ? get_post_thumbnail_id($post->ID) : 0;
		$manager_url = admin_url('admin.php?page=' . ($is_tour ? 'twostars-manager-tours' : ($is_review ? 'twostars-manager-reviews' : 'twostars-manager-destinations')));
		?>
		<div class="wrap twostars-manager">
			<?php $this->render_notice(); ?>
			<div class="twostars-manager__hero twostars-manager__hero--compact">
				<div class="twostars-manager__hero-main">
					<div class="twostars-manager__hero-copy">
					<p class="twostars-manager__eyebrow"><?php echo esc_html($is_tour ? __('Tours', 'twostars-platform') : ($is_review ? __('Reviews', 'twostars-platform') : __('Destinations', 'twostars-platform'))); ?></p>
					<h1><?php echo esc_html($title); ?></h1>
					<p><?php echo esc_html($is_tour ? __('Use this custom editor to manage tour details, booking-facing content, and image assets in one place.', 'twostars-platform') : ($is_review ? __('Use this custom editor to manage guest review copy, ratings, linked tours, and homepage-ready social proof.', 'twostars-platform') : __('Use this custom editor to manage destination storytelling, best-for guidance, and image assets in one place.', 'twostars-platform'))); ?></p>
					</div>
				</div>
				<div class="twostars-manager__hero-actions">
					<a class="button" href="<?php echo esc_url($manager_url); ?>"><?php esc_html_e('Back to Manager', 'twostars-platform'); ?></a>
					<?php if ($post instanceof WP_Post) : ?>
						<a class="button" href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"><?php esc_html_e('Open Native Editor', 'twostars-platform'); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<form class="twostars-manager-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="twostars_manager_save_content">
				<input type="hidden" name="post_type" value="<?php echo esc_attr($post_type); ?>">
				<input type="hidden" name="post_id" value="<?php echo esc_attr((string) ($post?->ID ?? 0)); ?>">
				<?php wp_nonce_field('twostars_manager_save_content', 'twostars_manager_nonce'); ?>

				<div class="twostars-manager-form__layout">
					<div class="twostars-manager-form__main">
						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('Core Content', 'twostars-platform'); ?></h2>
								<p><?php echo esc_html($is_review ? __('These fields control the reviewer name, short summary, and the review text shown on the site.', 'twostars-platform') : __('These fields control the main page title, summary, and body copy seen on the site.', 'twostars-platform')); ?></p>
							</div>
							<div class="twostars-manager-form__grid">
								<label class="twostars-manager-form__field twostars-manager-form__field--wide">
									<span><?php echo esc_html($is_review ? __('Reviewer Name', 'twostars-platform') : __('Title', 'twostars-platform')); ?></span>
									<input type="text" name="post_title" value="<?php echo esc_attr((string) ($post?->post_title ?? '')); ?>" required>
								</label>
								<label class="twostars-manager-form__field twostars-manager-form__field--wide">
									<span><?php echo esc_html($is_review ? __('Short Pull Quote / Summary', 'twostars-platform') : __('Excerpt / Short Summary', 'twostars-platform')); ?></span>
									<textarea name="post_excerpt" rows="4"><?php echo esc_textarea((string) ($post?->post_excerpt ?? '')); ?></textarea>
								</label>
							</div>
							<div class="twostars-manager-form__editor">
								<span class="twostars-manager-form__field-label"><?php echo esc_html($is_review ? __('Full Review', 'twostars-platform') : __('Main Content', 'twostars-platform')); ?></span>
								<?php
								wp_editor(
									(string) ($post?->post_content ?? ''),
									'twostars_manager_post_content',
									[
										'textarea_name' => 'post_content',
										'textarea_rows' => 14,
										'media_buttons' => true,
									]
								);
								?>
							</div>
						</section>

						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php echo esc_html($is_tour ? __('Tour Details', 'twostars-platform') : ($is_review ? __('Review Details', 'twostars-platform') : __('Destination Details', 'twostars-platform'))); ?></h2>
								<p><?php echo esc_html($is_tour ? __('Keep all operational and storytelling details for this tour in one clean form.', 'twostars-platform') : ($is_review ? __('Capture the rating, trip context, source, and homepage priority for this guest review.', 'twostars-platform') : __('Keep destination context, highlights, and supporting details in one clean form.', 'twostars-platform'))); ?></p>
							</div>
							<div class="twostars-manager-form__grid">
								<?php foreach ($field_definitions as $meta_key => $definition) : ?>
									<?php $this->render_manager_field($post?->ID ?? 0, $meta_key, $definition); ?>
								<?php endforeach; ?>
							</div>
						</section>
					</div>

					<aside class="twostars-manager-form__sidebar">
						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('Publish', 'twostars-platform'); ?></h2>
								<?php if ($is_review) : ?>
									<p><?php esc_html_e('Only published reviews appear on the homepage. Keep new submissions in draft until you approve them.', 'twostars-platform'); ?></p>
								<?php endif; ?>
							</div>
							<div class="twostars-manager-form__stack">
								<label class="twostars-manager-form__field">
									<span><?php esc_html_e('Status', 'twostars-platform'); ?></span>
									<select name="post_status">
										<?php foreach (['draft' => __('Draft', 'twostars-platform'), 'publish' => __('Published', 'twostars-platform'), 'private' => __('Private', 'twostars-platform')] as $status_value => $status_label) : ?>
											<option value="<?php echo esc_attr($status_value); ?>" <?php selected((string) ($post?->post_status ?? 'draft'), $status_value); ?>><?php echo esc_html($status_label); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label class="twostars-manager-form__field">
									<span><?php esc_html_e('Slug', 'twostars-platform'); ?></span>
									<input type="text" name="post_slug" value="<?php echo esc_attr($post instanceof WP_Post ? $post->post_name : ''); ?>">
								</label>
							</div>
						</section>

						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('Featured Image', 'twostars-platform'); ?></h2>
							</div>
							<?php $this->render_media_field('featured_image_id', $featured_image_id > 0 ? [$featured_image_id] : [], false); ?>
						</section>

						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('Actions', 'twostars-platform'); ?></h2>
							</div>
							<div class="twostars-manager-form__actions">
								<button type="submit" class="button button-primary button-hero"><?php esc_html_e('Save Content', 'twostars-platform'); ?></button>
								<?php if ($is_review && $post instanceof WP_Post && $post->post_status !== 'publish') : ?>
									<a class="button" href="<?php echo esc_url($this->get_review_status_url((int) $post->ID, 'publish')); ?>"><?php esc_html_e('Approve and Publish', 'twostars-platform'); ?></a>
								<?php endif; ?>
								<a class="button" href="<?php echo esc_url($manager_url); ?>"><?php esc_html_e('Cancel', 'twostars-platform'); ?></a>
								<?php if ($post instanceof WP_Post) : ?>
									<a class="button" href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank" rel="noopener"><?php esc_html_e('View Live Page', 'twostars-platform'); ?></a>
								<?php endif; ?>
							</div>
						</section>
					</aside>
				</div>
			</form>
		</div>
		<?php
	}

	public function render_bookings_page(): void
	{
		$this->render_record_manager_page(
			'booking',
			__('Manage Bookings', 'twostars-platform'),
			__('Review booking requests, confirm next steps, and keep the local team aligned on every incoming enquiry.', 'twostars-platform')
		);
	}

	public function render_leads_page(): void
	{
		$this->render_record_manager_page(
			'lead',
			__('Manage Leads', 'twostars-platform'),
			__('Track every enquiry, update contact status, and keep follow-up notes in one proper CRM view.', 'twostars-platform')
		);
	}

	private function render_record_manager_page(string $post_type, string $title, string $description): void
	{
		$all_records = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => ['private', 'publish', 'draft'],
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);
		$status_filter = sanitize_key((string) ($_GET['status'] ?? ''));
		$search_filter = sanitize_text_field((string) ($_GET['s'] ?? ''));
		$status_choices = $post_type === 'booking' ? BookingManager::get_status_choices() : LeadManager::get_status_choices();
		$records = $this->filter_manager_records($all_records, $post_type, $status_filter, $search_filter);
		$summary = $this->get_record_manager_summary($all_records, $post_type);
		$add_url = $this->get_form_url($post_type);
		?>
		<div class="wrap twostars-manager">
			<?php $this->render_notice(); ?>
			<div class="twostars-manager__hero twostars-manager__hero--compact">
				<div class="twostars-manager__hero-copy">
					<p class="twostars-manager__eyebrow"><?php echo esc_html($post_type === 'booking' ? __('Bookings', 'twostars-platform') : __('Leads', 'twostars-platform')); ?></p>
					<h1><?php echo esc_html($title); ?></h1>
					<p><?php echo esc_html($description); ?></p>
				</div>
				<div class="twostars-manager__hero-actions twostars-manager__hero-actions--stacked">
					<a class="button button-primary button-hero" href="<?php echo esc_url($add_url); ?>">
						<?php echo esc_html($post_type === 'booking' ? __('Add Booking', 'twostars-platform') : __('Add Lead', 'twostars-platform')); ?>
					</a>
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager')); ?>"><?php esc_html_e('Back to Site Dashboard', 'twostars-platform'); ?></a>
					<a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=lead&page=twostars-crm-dashboard')); ?>"><?php esc_html_e('Open CRM Dashboard', 'twostars-platform'); ?></a>
				</div>
				<div class="twostars-manager__stats">
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($summary['total'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Total Records', 'twostars-platform'); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($summary['active'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php echo esc_html($post_type === 'booking' ? __('Open Bookings', 'twostars-platform') : __('Open Pipeline', 'twostars-platform')); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) ($summary['needs_attention'] ?? 0)); ?></span><span class="twostars-manager__stat-label"><?php echo esc_html($post_type === 'booking' ? __('Needs Attention', 'twostars-platform') : __('Needs Follow-Up', 'twostars-platform')); ?></span></div>
					<div class="twostars-manager__stat"><span class="twostars-manager__stat-value"><?php echo esc_html((string) count($records)); ?></span><span class="twostars-manager__stat-label"><?php esc_html_e('Showing Now', 'twostars-platform'); ?></span></div>
				</div>
			</div>

			<form method="get" class="twostars-manager-form__section twostars-manager-records__filters">
				<input type="hidden" name="page" value="<?php echo esc_attr($post_type === 'booking' ? 'twostars-manager-bookings' : 'twostars-manager-leads'); ?>">
				<div class="twostars-manager-form__grid">
					<label class="twostars-manager-form__field">
						<span><?php esc_html_e('Search', 'twostars-platform'); ?></span>
						<input type="text" name="s" value="<?php echo esc_attr($search_filter); ?>" placeholder="<?php echo esc_attr($post_type === 'booking' ? __('Name, email, phone, or tour', 'twostars-platform') : __('Name, email, phone, or interest', 'twostars-platform')); ?>">
					</label>
					<label class="twostars-manager-form__field">
						<span><?php esc_html_e('Status', 'twostars-platform'); ?></span>
						<select name="status">
							<option value=""><?php esc_html_e('All statuses', 'twostars-platform'); ?></option>
							<?php foreach ($status_choices as $status_key => $status_label) : ?>
								<option value="<?php echo esc_attr((string) $status_key); ?>" <?php selected($status_filter, (string) $status_key); ?>><?php echo esc_html((string) $status_label); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
				<div class="twostars-manager-form__actions">
					<button type="submit" class="button button-primary"><?php esc_html_e('Apply Filters', 'twostars-platform'); ?></button>
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=' . ($post_type === 'booking' ? 'twostars-manager-bookings' : 'twostars-manager-leads'))); ?>"><?php esc_html_e('Reset Filters', 'twostars-platform'); ?></a>
				</div>
			</form>

			<section class="twostars-manager-form__section">
				<div class="twostars-manager-form__section-head">
					<h2><?php echo esc_html($post_type === 'booking' ? __('Booking Board', 'twostars-platform') : __('Lead Board', 'twostars-platform')); ?></h2>
					<p><?php echo esc_html($post_type === 'booking' ? __('Each booking card keeps the team focused on route, contact details, current status, and the next response step.', 'twostars-platform') : __('Each lead card highlights who asked, what they want, their follow-up state, and the quickest next action.', 'twostars-platform')); ?></p>
				</div>
				<div class="twostars-manager-records__grid">
					<?php if ($records === []) : ?>
						<div class="twostars-manager-records__empty"><?php esc_html_e('No records match the current filters yet.', 'twostars-platform'); ?></div>
					<?php endif; ?>
					<?php foreach ($records as $record) : ?>
						<?php $this->render_record_card($record); ?>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php
	}

	private function render_record_row(WP_Post $record): void
	{
		$is_booking = $record->post_type === 'booking';
		$edit_url = $this->get_form_url($record->post_type, (int) $record->ID);
		$native_url = get_edit_post_link($record->ID);
		$origin_url = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_origin_url' : '_twostars_lead_origin_url', true);
		$status = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_status' : '_twostars_lead_status', true);
		$email = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_email' : '_twostars_lead_email', true);
		$phone = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_phone' : '_twostars_lead_phone', true);
		$visitor_profile_id = (int) get_post_meta($record->ID, $is_booking ? '_twostars_booking_visitor_profile_id' : '_twostars_lead_visitor_profile_id', true);
		$tour_id = (int) get_post_meta($record->ID, $is_booking ? '_twostars_booking_tour_id' : '_twostars_lead_tour_interest', true);
		$name = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_full_name' : '_twostars_lead_full_name', true);
		$timing = $is_booking
			? trim((string) get_post_meta($record->ID, '_twostars_booking_date', true) . ' ' . (string) get_post_meta($record->ID, '_twostars_booking_time', true))
			: (string) get_post_meta($record->ID, '_twostars_lead_follow_up_date', true);
		$status_label = $is_booking
			? (BookingManager::get_status_choices()[$status] ?? ucfirst($status ?: 'pending'))
			: (LeadManager::get_status_choices()[$status] ?? ucfirst($status ?: 'new'));
		?>
		<tr>
			<td>
				<strong><?php echo esc_html($name !== '' ? $name : $record->post_title); ?></strong>
				<div class="twostars-manager-table__sub"><?php echo esc_html(wp_date('Y-m-d H:i', strtotime($record->post_date))); ?></div>
			</td>
			<td><?php echo $tour_id > 0 ? esc_html(get_the_title($tour_id)) : '&mdash;'; ?></td>
			<td>
				<div><?php echo $email !== '' ? esc_html($email) : '&mdash;'; ?></div>
				<?php if ($phone !== '') : ?>
					<div class="twostars-manager-table__sub"><?php echo esc_html($phone); ?></div>
				<?php endif; ?>
			</td>
			<td><span class="twostars-manager-badge"><?php echo esc_html((string) $status_label); ?></span></td>
			<td><?php echo $timing !== '' ? esc_html($timing) : '&mdash;'; ?></td>
			<td>
				<div class="twostars-manager-table__actions">
					<a class="button button-primary" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Open', 'twostars-platform'); ?></a>
					<?php if ($native_url !== false) : ?>
						<a class="button" href="<?php echo esc_url((string) $native_url); ?>"><?php esc_html_e('Native', 'twostars-platform'); ?></a>
					<?php endif; ?>
					<?php if ($origin_url !== '') : ?>
						<a class="button" href="<?php echo esc_url($origin_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Origin', 'twostars-platform'); ?></a>
					<?php endif; ?>
					<?php if ($visitor_profile_id > 0) : ?>
						<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-visitor-detail&visitor_id=' . $visitor_profile_id)); ?>"><?php esc_html_e('Visitor', 'twostars-platform'); ?></a>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	private function render_record_card(WP_Post $record): void
	{
		$is_booking = $record->post_type === 'booking';
		$edit_url = $this->get_form_url($record->post_type, (int) $record->ID);
		$native_url = get_edit_post_link($record->ID);
		$origin_url = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_origin_url' : '_twostars_lead_origin_url', true);
		$status = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_status' : '_twostars_lead_status', true);
		$email = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_email' : '_twostars_lead_email', true);
		$phone = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_phone' : '_twostars_lead_phone', true);
		$visitor_profile_id = (int) get_post_meta($record->ID, $is_booking ? '_twostars_booking_visitor_profile_id' : '_twostars_lead_visitor_profile_id', true);
		$tour_id = (int) get_post_meta($record->ID, $is_booking ? '_twostars_booking_tour_id' : '_twostars_lead_tour_interest', true);
		$name = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_full_name' : '_twostars_lead_full_name', true);
		$timing = $is_booking
			? trim((string) get_post_meta($record->ID, '_twostars_booking_date', true) . ' ' . (string) get_post_meta($record->ID, '_twostars_booking_time', true))
			: (string) get_post_meta($record->ID, '_twostars_lead_follow_up_date', true);
		$notes = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_internal_notes' : '_twostars_lead_internal_notes', true);
		$status_label = $this->get_record_status_label($record->post_type, $status);
		$tour_label = $tour_id > 0 ? get_the_title($tour_id) : ($is_booking ? __('Tour not set', 'twostars-platform') : __('Interest not set', 'twostars-platform'));
		$record_title = $name !== '' ? $name : $record->post_title;
		$created_at = wp_date('Y-m-d H:i', strtotime($record->post_date));
		?>
		<article class="twostars-manager-record-card">
			<div class="twostars-manager-record-card__head">
				<div>
					<p class="twostars-manager-record-card__eyebrow"><?php echo esc_html($is_booking ? __('Booking Request', 'twostars-platform') : __('Lead Enquiry', 'twostars-platform')); ?></p>
					<h3><?php echo esc_html($record_title); ?></h3>
				</div>
				<span class="twostars-manager-badge"><?php echo esc_html($status_label); ?></span>
			</div>
			<div class="twostars-manager-record-card__meta">
				<span><?php echo esc_html($tour_label); ?></span>
				<span><?php echo esc_html($created_at); ?></span>
				<?php if ($timing !== '') : ?>
					<span><?php echo esc_html($timing); ?></span>
				<?php endif; ?>
			</div>
			<div class="twostars-manager-record-card__facts">
				<div>
					<strong><?php esc_html_e('Email', 'twostars-platform'); ?></strong>
					<span><?php echo esc_html($email !== '' ? $email : __('Not provided', 'twostars-platform')); ?></span>
				</div>
				<div>
					<strong><?php esc_html_e('Phone', 'twostars-platform'); ?></strong>
					<span><?php echo esc_html($phone !== '' ? $phone : __('Not provided', 'twostars-platform')); ?></span>
				</div>
				<div>
					<strong><?php echo esc_html($is_booking ? __('Visit Timing', 'twostars-platform') : __('Follow Up', 'twostars-platform')); ?></strong>
					<span><?php echo esc_html($timing !== '' ? $timing : __('Not set', 'twostars-platform')); ?></span>
				</div>
				<div>
					<strong><?php esc_html_e('Visitor Profile', 'twostars-platform'); ?></strong>
					<span><?php echo esc_html($visitor_profile_id > 0 ? __('Linked', 'twostars-platform') : __('Not linked', 'twostars-platform')); ?></span>
				</div>
			</div>
			<p class="twostars-manager-record-card__notes"><?php echo esc_html($notes !== '' ? wp_trim_words(wp_strip_all_tags($notes), 22, '...') : ($is_booking ? __('No internal booking notes added yet.', 'twostars-platform') : __('No internal lead notes added yet.', 'twostars-platform'))); ?></p>
			<div class="twostars-manager-record-card__actions">
				<a class="button button-primary" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Open', 'twostars-platform'); ?></a>
				<?php if ($email !== '') : ?>
					<a class="button" href="<?php echo esc_url('mailto:' . $email); ?>"><?php echo esc_html($is_booking ? __('Email Guest', 'twostars-platform') : __('Email Lead', 'twostars-platform')); ?></a>
				<?php endif; ?>
				<?php if ($phone !== '') : ?>
					<a class="button" href="<?php echo esc_url('tel:' . preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php esc_html_e('Call', 'twostars-platform'); ?></a>
				<?php endif; ?>
				<?php if ($visitor_profile_id > 0) : ?>
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-visitor-detail&visitor_id=' . $visitor_profile_id)); ?>"><?php esc_html_e('Visitor', 'twostars-platform'); ?></a>
				<?php endif; ?>
				<?php if ($origin_url !== '') : ?>
					<a class="button" href="<?php echo esc_url($origin_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Origin', 'twostars-platform'); ?></a>
				<?php endif; ?>
				<?php if ($native_url !== false) : ?>
					<a class="button" href="<?php echo esc_url((string) $native_url); ?>"><?php esc_html_e('Native', 'twostars-platform'); ?></a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * @param array<int, WP_Post> $records
	 * @return array<int, WP_Post>
	 */
	private function filter_manager_records(array $records, string $post_type, string $status_filter, string $search_filter): array
	{
		return array_values(
			array_filter(
				$records,
				function (WP_Post $record) use ($post_type, $status_filter, $search_filter): bool {
					$is_booking = $post_type === 'booking';
					$status = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_status' : '_twostars_lead_status', true);

					if ($status_filter !== '' && $status !== $status_filter) {
						return false;
					}

					if ($search_filter === '') {
						return true;
					}

					$email = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_email' : '_twostars_lead_email', true);
					$phone = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_phone' : '_twostars_lead_phone', true);
					$name = (string) get_post_meta($record->ID, $is_booking ? '_twostars_booking_full_name' : '_twostars_lead_full_name', true);
					$tour_id = (int) get_post_meta($record->ID, $is_booking ? '_twostars_booking_tour_id' : '_twostars_lead_tour_interest', true);
					$tour_title = $tour_id > 0 ? (string) get_the_title($tour_id) : '';
					$haystack = strtolower(implode(' ', array_filter([$record->post_title, $name, $email, $phone, $tour_title])));

					return str_contains($haystack, strtolower($search_filter));
				}
			)
		);
	}

	/**
	 * @param array<int, WP_Post> $records
	 * @return array<string, int>
	 */
	private function get_record_manager_summary(array $records, string $post_type): array
	{
		$summary = [
			'total' => count($records),
			'active' => 0,
			'needs_attention' => 0,
		];

		foreach ($records as $record) {
			$status = (string) get_post_meta($record->ID, $post_type === 'booking' ? '_twostars_booking_status' : '_twostars_lead_status', true);

			if ($post_type === 'booking') {
				if (in_array($status, ['pending', 'contacted', 'confirmed'], true)) {
					$summary['active']++;
				}

				if (! in_array($status, ['completed', 'cancelled'], true)) {
					$summary['needs_attention']++;
				}

				continue;
			}

			if (! in_array($status, ['won', 'lost'], true)) {
				$summary['active']++;
			}

			if (! in_array($status, ['won', 'lost'], true)) {
				$summary['needs_attention']++;
			}
		}

		return $summary;
	}

	private function get_record_status_label(string $post_type, string $status): string
	{
		if ($post_type === 'booking') {
			return (string) (BookingManager::get_status_choices()[$status] ?? ucfirst($status !== '' ? $status : 'pending'));
		}

		return (string) (LeadManager::get_status_choices()[$status] ?? ucfirst($status !== '' ? $status : 'new'));
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	private function get_record_field_groups(string $post_type): array
	{
		if ($post_type === 'booking') {
			return [
				'crm' => [
					'_twostars_booking_status',
					'_twostars_booking_priority',
					'_twostars_booking_payment_status',
					'_twostars_booking_assigned_to',
					'_twostars_booking_confirmation_deadline',
					'_twostars_booking_follow_up_channel',
					'_twostars_booking_last_contacted_at',
					'_twostars_booking_next_action',
				],
				'contact' => [
					'_twostars_booking_full_name',
					'_twostars_booking_email',
					'_twostars_booking_phone',
					'_twostars_booking_country',
					'_twostars_booking_visitor_profile_id',
				],
				'journey' => [
					'_twostars_booking_tour_id',
					'_twostars_booking_guest_count',
					'_twostars_booking_date',
					'_twostars_booking_time',
					'_twostars_booking_pickup_location',
					'_twostars_booking_notes',
				],
				'internal' => [
					'_twostars_booking_internal_notes',
				],
			];
		}

		return [
			'crm' => [
				'_twostars_lead_status',
				'_twostars_lead_priority',
				'_twostars_lead_assigned_to',
				'_twostars_lead_follow_up_date',
				'_twostars_lead_follow_up_channel',
				'_twostars_lead_last_contacted_at',
				'_twostars_lead_next_action',
			],
			'contact' => [
				'_twostars_lead_full_name',
				'_twostars_lead_email',
				'_twostars_lead_phone',
				'_twostars_lead_country',
				'_twostars_lead_source',
				'_twostars_lead_contact_consent',
				'_twostars_lead_visitor_profile_id',
			],
			'journey' => [
				'_twostars_lead_tour_interest',
				'_twostars_lead_guest_count',
				'_twostars_lead_travel_dates',
				'_twostars_lead_message',
			],
			'internal' => [
				'_twostars_lead_internal_notes',
			],
		];
	}

	private function render_record_fields_by_keys(int $post_id, array $field_definitions, array $keys): void
	{
		foreach ($keys as $meta_key) {
			if (! isset($field_definitions[$meta_key])) {
				continue;
			}

			$this->render_record_field($post_id, $meta_key, $field_definitions[$meta_key]);
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_record_workspace_data(string $post_type, ?WP_Post $post): array
	{
		if (! $post instanceof WP_Post) {
			return [];
		}

		$is_booking = $post_type === 'booking';
		$status_key = $is_booking ? '_twostars_booking_status' : '_twostars_lead_status';
		$priority_key = $is_booking ? '_twostars_booking_priority' : '_twostars_lead_priority';
		$assigned_key = $is_booking ? '_twostars_booking_assigned_to' : '_twostars_lead_assigned_to';
		$email_key = $is_booking ? '_twostars_booking_email' : '_twostars_lead_email';
		$phone_key = $is_booking ? '_twostars_booking_phone' : '_twostars_lead_phone';
		$visitor_key = $is_booking ? '_twostars_booking_visitor_profile_id' : '_twostars_lead_visitor_profile_id';
		$origin_key = $is_booking ? '_twostars_booking_origin_url' : '_twostars_lead_origin_url';
		$session_key = $is_booking ? '_twostars_booking_session_key' : '_twostars_lead_session_key';
		$next_action_key = $is_booking ? '_twostars_booking_next_action' : '_twostars_lead_next_action';
		$next_due_key = $is_booking ? '_twostars_booking_confirmation_deadline' : '_twostars_lead_follow_up_date';
		$last_contacted_key = $is_booking ? '_twostars_booking_last_contacted_at' : '_twostars_lead_last_contacted_at';
		$related_key = $is_booking ? '_twostars_booking_lead_id' : '';
		$status = (string) get_post_meta($post->ID, $status_key, true);
		$priority = (string) get_post_meta($post->ID, $priority_key, true);
		$priority_label = $priority !== '' ? ucfirst($priority) : __('Medium', 'twostars-platform');
		$visitor_profile_id = (int) get_post_meta($post->ID, $visitor_key, true);
		$visitor_context = $visitor_profile_id > 0 ? VisitorProfileManager::get_profile_context_by_id($visitor_profile_id) : [];
		$visitor_url = $visitor_profile_id > 0 ? admin_url('admin.php?page=twostars-manager-visitor-detail&visitor_id=' . $visitor_profile_id) : '';
		$related_id = $related_key !== '' ? (int) get_post_meta($post->ID, $related_key, true) : 0;
		$related_type = $is_booking ? 'lead' : 'booking';

		if (! $is_booking) {
			$related_id = $this->find_booking_by_lead_id((int) $post->ID);
		}

		$related_url = $related_id > 0 ? $this->get_form_url($related_type, $related_id) : '';
		$tour_id = (int) get_post_meta($post->ID, $is_booking ? '_twostars_booking_tour_id' : '_twostars_lead_tour_interest', true);
		$tour_label = $tour_id > 0 ? (string) get_the_title($tour_id) : '';
		$country = (string) get_post_meta($post->ID, $is_booking ? '_twostars_booking_country' : '_twostars_lead_country', true);
		$source = (string) get_post_meta($post->ID, '_twostars_lead_source', true);
		$guest_count = (int) get_post_meta($post->ID, $is_booking ? '_twostars_booking_guest_count' : '_twostars_lead_guest_count', true);
		$journey_timing = $is_booking
			? trim((string) get_post_meta($post->ID, '_twostars_booking_date', true) . ' ' . (string) get_post_meta($post->ID, '_twostars_booking_time', true))
			: (string) get_post_meta($post->ID, '_twostars_lead_travel_dates', true);
		$record_name = (string) get_post_meta($post->ID, $is_booking ? '_twostars_booking_full_name' : '_twostars_lead_full_name', true);
		$system_trace = array_filter([
			(string) get_post_meta($post->ID, $session_key, true),
			(string) get_post_meta($post->ID, $origin_key, true),
		]);
		$next_due = (string) get_post_meta($post->ID, $next_due_key, true);
		$phone = (string) get_post_meta($post->ID, $phone_key, true);
		$email = (string) get_post_meta($post->ID, $email_key, true);
		$phone_digits = preg_replace('/[^0-9+]/', '', $phone);
		$whatsapp_url = is_string($phone_digits) && $phone_digits !== '' ? 'https://wa.me/' . ltrim($phone_digits, '+') : '';
		$related_label = $related_id > 0 ? (string) get_the_title($related_id) : '';
		$activity_count = count($this->get_record_activity_entries($post_type, $post->ID));
		$status_label = $this->get_record_status_label($post_type, $status);

		return [
			'status' => $status,
			'status_label' => $status_label,
			'priority_label' => $priority_label,
			'assignee' => (string) get_post_meta($post->ID, $assigned_key, true),
			'email' => $email,
			'phone' => $phone,
			'whatsapp_url' => $whatsapp_url,
			'next_due_label' => $next_due,
			'next_action' => (string) get_post_meta($post->ID, $next_action_key, true),
			'last_contacted_at' => (string) get_post_meta($post->ID, $last_contacted_key, true),
			'created_label' => wp_date('Y-m-d H:i', strtotime((string) $post->post_date)),
			'record_name' => $record_name !== '' ? $record_name : $post->post_title,
			'country' => $country,
			'source_label' => $source !== '' ? ucfirst(str_replace('_', ' ', $source)) : __('Manual Entry', 'twostars-platform'),
			'journey_label' => $journey_timing,
			'guest_count' => $guest_count,
			'visitor_url' => $visitor_url,
			'visitor_label' => (string) ($visitor_context['location_label'] ?? __('Visitor profile', 'twostars-platform')),
			'visitor_copy' => $visitor_context !== []
				? sprintf(__('Stage: %1$s. Persona: %2$s.', 'twostars-platform'), (string) ($visitor_context['latest_stage'] ?? __('Unknown', 'twostars-platform')), (string) ($visitor_context['latest_persona'] ?? __('Unknown', 'twostars-platform')))
				: __('Open the linked visitor profile for intelligence context.', 'twostars-platform'),
			'related_url' => $related_url,
			'related_label' => $related_label,
			'related_type' => $related_type,
			'related_copy' => $related_id > 0
				? ($is_booking ? __('Open the linked lead record that sits behind this booking.', 'twostars-platform') : __('Open the linked booking record created from this lead.', 'twostars-platform'))
				: '',
			'origin_url' => (string) get_post_meta($post->ID, $origin_key, true),
			'system_copy' => $system_trace !== [] ? implode(' | ', $system_trace) : '',
			'timeline' => $this->get_record_timeline($post_type, $post),
			'tour_label' => $tour_label,
			'status_tone' => $this->get_record_status_tone($post_type, $status),
			'priority_tone' => $this->get_priority_tone($priority),
			'due_tone' => $this->get_due_tone($next_due),
			'due_descriptor' => $this->format_due_descriptor($next_due, $post_type),
			'activity_count' => $activity_count,
			'native_url' => get_edit_post_link($post->ID),
		];
	}

	/**
	 * @return array<string, string>
	 */
	private function get_record_quick_actions(string $post_type): array
	{
		if ($post_type === 'booking') {
			return [
				'contacted' => __('Mark Contacted', 'twostars-platform'),
				'follow_up_scheduled' => __('Set Follow Up', 'twostars-platform'),
				'confirmed' => __('Confirm Booking', 'twostars-platform'),
				'completed' => __('Mark Completed', 'twostars-platform'),
				'cancelled' => __('Cancel Booking', 'twostars-platform'),
			];
		}

		return [
			'contacted' => __('Mark Contacted', 'twostars-platform'),
			'follow_up_scheduled' => __('Schedule Follow Up', 'twostars-platform'),
			'won' => __('Mark Won', 'twostars-platform'),
			'lost' => __('Mark Lost', 'twostars-platform'),
		];
	}

	/**
	 * @param array<string, mixed> $workspace
	 * @return array<int, string>
	 */
	private function get_record_checklist(string $post_type, array $workspace): array
	{
		$items = [];

		if ((string) ($workspace['email'] ?? '') === '' && (string) ($workspace['phone'] ?? '') === '') {
			$items[] = __('Add at least one reliable contact method before moving this record forward.', 'twostars-platform');
		}

		if ((string) ($workspace['next_due_label'] ?? '') === '') {
			$items[] = $post_type === 'booking'
				? __('Set a confirmation deadline so the team knows when this booking must be locked or released.', 'twostars-platform')
				: __('Set a follow-up date so the enquiry does not stall in the pipeline.', 'twostars-platform');
		}

		if ((string) ($workspace['next_action'] ?? '') === '') {
			$items[] = __('Write the exact next action so any team member can pick up this record without guessing.', 'twostars-platform');
		}

		if ((string) ($workspace['assignee'] ?? '') === '') {
			$items[] = __('Assign an owner so follow-up accountability is clear.', 'twostars-platform');
		}

		if ($items === []) {
			$items[] = __('The core CRM fields are covered. Save any new update note after your next contact touchpoint.', 'twostars-platform');
		}

		return $items;
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function get_record_timeline(string $post_type, WP_Post $post): array
	{
		$is_booking = $post_type === 'booking';
		$timeline = [
			[
				'time' => wp_date('Y-m-d H:i', strtotime($post->post_date)),
				'title' => __('Record Created', 'twostars-platform'),
				'body' => __('The CRM record was first created in the admin workflow.', 'twostars-platform'),
			],
		];

		$last_contacted = (string) get_post_meta($post->ID, $is_booking ? '_twostars_booking_last_contacted_at' : '_twostars_lead_last_contacted_at', true);
		if ($last_contacted !== '') {
			$timeline[] = [
				'time' => $last_contacted,
				'title' => __('Last Contacted', 'twostars-platform'),
				'body' => __('The team recorded a direct contact touchpoint for this record.', 'twostars-platform'),
			];
		}

		$next_due = (string) get_post_meta($post->ID, $is_booking ? '_twostars_booking_confirmation_deadline' : '_twostars_lead_follow_up_date', true);
		if ($next_due !== '') {
			$timeline[] = [
				'time' => $next_due,
				'title' => $is_booking ? __('Confirmation Deadline', 'twostars-platform') : __('Follow Up Scheduled', 'twostars-platform'),
				'body' => $is_booking ? __('This is the target date for confirming or resolving the booking request.', 'twostars-platform') : __('This is the next follow-up date recorded for the lead.', 'twostars-platform'),
			];
		}

		foreach ($this->get_record_activity_entries($post_type, $post->ID) as $entry) {
			$timeline[] = [
				'time' => (string) ($entry['time'] ?? ''),
				'title' => (string) ($entry['title'] ?? __('CRM Update', 'twostars-platform')),
				'body' => (string) ($entry['body'] ?? ''),
			];
		}

		usort(
			$timeline,
			static fn(array $left, array $right): int => strcmp((string) ($right['time'] ?? ''), (string) ($left['time'] ?? ''))
		);

		return array_slice($timeline, 0, 8);
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function get_record_activity_entries(string $post_type, int $post_id): array
	{
		$meta_key = $post_type === 'booking' ? '_twostars_booking_activity_log' : '_twostars_lead_activity_log';
		$raw = get_post_meta($post_id, $meta_key, true);

		if (! is_string($raw) || trim($raw) === '') {
			return [];
		}

		$decoded = json_decode($raw, true);

		if (is_array($decoded)) {
			return array_values(
				array_filter(
					array_map(
						static fn(array $entry): array => [
							'time' => sanitize_text_field((string) ($entry['time'] ?? '')),
							'title' => sanitize_text_field((string) ($entry['title'] ?? __('CRM Update', 'twostars-platform'))),
							'body' => sanitize_textarea_field((string) ($entry['body'] ?? '')),
						],
						array_filter($decoded, 'is_array')
					),
					static fn(array $entry): bool => $entry['body'] !== ''
				)
			);
		}

		return [
			[
				'time' => '',
				'title' => __('CRM Notes History', 'twostars-platform'),
				'body' => sanitize_textarea_field($raw),
			],
		];
	}

	private function append_record_activity_entry(string $post_type, int $post_id, string $title, string $body): void
	{
		$body = trim($body);

		if ($post_id <= 0 || $body === '') {
			return;
		}

		$entries = $this->get_record_activity_entries($post_type, $post_id);
		array_unshift(
			$entries,
			[
				'time' => current_time('mysql'),
				'title' => $title,
				'body' => $body,
			]
		);

		$meta_key = $post_type === 'booking' ? '_twostars_booking_activity_log' : '_twostars_lead_activity_log';
		update_post_meta($post_id, $meta_key, wp_json_encode(array_slice($entries, 0, 60)));
	}

	private function apply_record_quick_action(string $post_type, array &$values, string $quick_action): string
	{
		if ($quick_action === '') {
			return '';
		}

		$now = wp_date('Y-m-d\TH:i');
		$today = wp_date('Y-m-d');

		if ($post_type === 'booking') {
			$values['_twostars_booking_status'] = $quick_action;

			if (in_array($quick_action, ['contacted', 'confirmed', 'follow_up_scheduled'], true)) {
				$values['_twostars_booking_last_contacted_at'] = $now;
			}

			if ($quick_action === 'follow_up_scheduled' && (string) ($values['_twostars_booking_confirmation_deadline'] ?? '') === '') {
				$values['_twostars_booking_confirmation_deadline'] = wp_date('Y-m-d', strtotime('+2 days'));
			}

			if ($quick_action === 'contacted' && (string) ($values['_twostars_booking_next_action'] ?? '') === '') {
				$values['_twostars_booking_next_action'] = __('Send confirmation options and confirm guest response window.', 'twostars-platform');
			}

			if ($quick_action === 'confirmed' && (string) ($values['_twostars_booking_payment_status'] ?? '') === '') {
				$values['_twostars_booking_payment_status'] = 'pending';
			}

			if ($quick_action === 'confirmed') {
				$values['_twostars_booking_confirmation_deadline'] = $today;

				if ((string) ($values['_twostars_booking_next_action'] ?? '') === '') {
					$values['_twostars_booking_next_action'] = __('Send confirmation pack, request deposit, and hand off to operations.', 'twostars-platform');
				}
			}

			if ($quick_action === 'completed') {
				$values['_twostars_booking_confirmation_deadline'] = '';
				$values['_twostars_booking_next_action'] = __('Booking completed. Request review and archive operational notes.', 'twostars-platform');
			}

			if ($quick_action === 'cancelled') {
				$values['_twostars_booking_confirmation_deadline'] = '';
				$values['_twostars_booking_next_action'] = __('Booking cancelled. Capture reason and close the record.', 'twostars-platform');
			}

			return sprintf(__('Quick action applied: %s.', 'twostars-platform'), $this->get_record_status_label($post_type, $quick_action));
		}

		$values['_twostars_lead_status'] = $quick_action;

		if (in_array($quick_action, ['contacted', 'follow_up_scheduled'], true)) {
			$values['_twostars_lead_last_contacted_at'] = $now;
		}

		if ($quick_action === 'follow_up_scheduled' && (string) ($values['_twostars_lead_follow_up_date'] ?? '') === '') {
			$values['_twostars_lead_follow_up_date'] = wp_date('Y-m-d', strtotime('+1 day'));
		}

		if ($quick_action === 'contacted' && (string) ($values['_twostars_lead_next_action'] ?? '') === '') {
			$values['_twostars_lead_next_action'] = __('Send itinerary guidance or pricing follow-up based on the enquiry.', 'twostars-platform');
		}

		if ($quick_action === 'follow_up_scheduled' && (string) ($values['_twostars_lead_next_action'] ?? '') === '') {
			$values['_twostars_lead_next_action'] = __('Follow up on the agreed date with the next route or pricing step.', 'twostars-platform');
		}

		if ($quick_action === 'won') {
			$values['_twostars_lead_follow_up_date'] = '';
			$values['_twostars_lead_next_action'] = __('Convert the won lead into an active booking workflow and confirm trip details.', 'twostars-platform');
		}

		if ($quick_action === 'lost') {
			$values['_twostars_lead_follow_up_date'] = '';
			$values['_twostars_lead_next_action'] = __('Record the loss reason and archive the opportunity cleanly.', 'twostars-platform');
		}

		return sprintf(__('Quick action applied: %s.', 'twostars-platform'), $this->get_record_status_label($post_type, $quick_action));
	}

	private function render_record_form_page(string $post_type, int $post_id = 0): void
	{
		$post = $post_id > 0 ? get_post($post_id) : null;

		if ($post_id > 0 && (! $post instanceof WP_Post || $post->post_type !== $post_type)) {
			wp_die(esc_html__('That record could not be found.', 'twostars-platform'));
		}

		$post = $post instanceof WP_Post ? $post : null;
		$is_booking = $post_type === 'booking';
		$title = $post ? $post->post_title : ($is_booking ? __('New Booking', 'twostars-platform') : __('New Lead', 'twostars-platform'));
		$manager_url = admin_url('admin.php?page=' . ($is_booking ? 'twostars-manager-bookings' : 'twostars-manager-leads'));
		$field_definitions = $is_booking ? BookingManager::get_manager_field_definitions() : LeadManager::get_manager_field_definitions();
		$field_groups = $this->get_record_field_groups($post_type);
		$workspace = $this->get_record_workspace_data($post_type, $post);
		?>
		<div class="wrap twostars-manager">
			<?php $this->render_notice(); ?>
			<div class="twostars-manager__hero twostars-manager__hero--compact twostars-manager-record-header">
				<div class="twostars-manager__hero-copy twostars-manager-record-header__copy">
					<p class="twostars-manager__eyebrow"><?php echo esc_html($is_booking ? __('Bookings', 'twostars-platform') : __('Leads', 'twostars-platform')); ?></p>
					<h1><?php echo esc_html($title); ?></h1>
					<p><?php echo esc_html($is_booking ? __('Coordinate the guest, booking status, payment readiness, follow-up, and operational handoff from one premium CRM workspace.', 'twostars-platform') : __('Move the enquiry from first contact to qualified trip planning with clear ownership, due dates, and commercial next actions.', 'twostars-platform')); ?></p>
					<div class="twostars-manager-record-header__meta">
						<span class="twostars-manager-record-header__pill twostars-manager-record-header__pill--<?php echo esc_attr((string) ($workspace['status_tone'] ?? 'neutral')); ?>"><?php echo esc_html((string) ($workspace['status_label'] ?? __('Not set', 'twostars-platform'))); ?></span>
						<span class="twostars-manager-record-header__pill twostars-manager-record-header__pill--<?php echo esc_attr((string) ($workspace['priority_tone'] ?? 'neutral')); ?>"><?php echo esc_html((string) ($workspace['priority_label'] ?? __('Medium', 'twostars-platform'))); ?></span>
						<?php if (! empty($workspace['due_descriptor'])) : ?>
							<span class="twostars-manager-record-header__pill twostars-manager-record-header__pill--<?php echo esc_attr((string) ($workspace['due_tone'] ?? 'neutral')); ?>"><?php echo esc_html((string) $workspace['due_descriptor']); ?></span>
						<?php endif; ?>
						<?php if (! empty($workspace['tour_label'])) : ?>
							<span class="twostars-manager-record-header__pill"><?php echo esc_html((string) $workspace['tour_label']); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="twostars-manager__hero-actions twostars-manager__hero-actions--stacked twostars-manager-record-header__actions">
					<a class="button" href="<?php echo esc_url($manager_url); ?>"><?php esc_html_e('Back to Manager', 'twostars-platform'); ?></a>
					<?php if (! empty($workspace['email'])) : ?>
						<a class="button" href="<?php echo esc_url('mailto:' . (string) $workspace['email']); ?>"><?php echo esc_html($is_booking ? __('Email Guest', 'twostars-platform') : __('Email Lead', 'twostars-platform')); ?></a>
					<?php endif; ?>
					<?php if (! empty($workspace['whatsapp_url'])) : ?>
						<a class="button" href="<?php echo esc_url((string) $workspace['whatsapp_url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('WhatsApp', 'twostars-platform'); ?></a>
					<?php endif; ?>
					<?php if (! empty($workspace['visitor_url'])) : ?>
						<a class="button" href="<?php echo esc_url((string) $workspace['visitor_url']); ?>"><?php esc_html_e('Open Visitor Profile', 'twostars-platform'); ?></a>
					<?php endif; ?>
					<?php if (! empty($workspace['native_url'])) : ?>
						<a class="button" href="<?php echo esc_url((string) $workspace['native_url']); ?>"><?php esc_html_e('Open Native Editor', 'twostars-platform'); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="twostars-manager-record-overview">
				<article class="twostars-manager-record-overview__card twostars-manager-record-overview__card--profile">
					<div class="twostars-manager-record-overview__eyebrow"><?php echo esc_html($is_booking ? __('Guest Snapshot', 'twostars-platform') : __('Lead Snapshot', 'twostars-platform')); ?></div>
					<h2><?php echo esc_html((string) ($workspace['record_name'] ?? $title)); ?></h2>
					<p><?php echo esc_html($is_booking ? __('Use this view as the commercial and operational control surface for the booking request.' , 'twostars-platform') : __('Use this view as the commercial control surface for the enquiry from first contact through conversion.', 'twostars-platform')); ?></p>
					<div class="twostars-manager-record-overview__facts">
						<div><strong><?php esc_html_e('Email', 'twostars-platform'); ?></strong><span><?php echo esc_html((string) ($workspace['email'] ?: __('Not provided', 'twostars-platform'))); ?></span></div>
						<div><strong><?php esc_html_e('Phone', 'twostars-platform'); ?></strong><span><?php echo esc_html((string) ($workspace['phone'] ?: __('Not provided', 'twostars-platform'))); ?></span></div>
						<div><strong><?php esc_html_e('Country', 'twostars-platform'); ?></strong><span><?php echo esc_html((string) ($workspace['country'] ?: __('Not set', 'twostars-platform'))); ?></span></div>
						<div><strong><?php echo esc_html($is_booking ? __('Booking Created', 'twostars-platform') : __('Lead Created', 'twostars-platform')); ?></strong><span><?php echo esc_html((string) ($workspace['created_label'] ?? __('Unknown', 'twostars-platform'))); ?></span></div>
					</div>
				</article>
				<article class="twostars-manager-record-overview__card">
					<div class="twostars-manager-record-overview__eyebrow"><?php echo esc_html($is_booking ? __('Trip And Ops', 'twostars-platform') : __('Trip Intent', 'twostars-platform')); ?></div>
					<h2><?php echo esc_html((string) ($workspace['tour_label'] ?: __('Tour not linked yet', 'twostars-platform'))); ?></h2>
					<p><?php echo esc_html($is_booking ? __('Keep travel timing, guest count, confirmation dates, and next operational move visible at all times.', 'twostars-platform') : __('Keep the route, dates, and group context visible while shaping the lead into a booking-ready opportunity.', 'twostars-platform')); ?></p>
					<div class="twostars-manager-record-overview__facts">
						<div><strong><?php echo esc_html($is_booking ? __('Travel Timing', 'twostars-platform') : __('Travel Window', 'twostars-platform')); ?></strong><span><?php echo esc_html((string) ($workspace['journey_label'] ?: __('Not set', 'twostars-platform'))); ?></span></div>
						<div><strong><?php esc_html_e('Guest Count', 'twostars-platform'); ?></strong><span><?php echo esc_html((string) ((int) ($workspace['guest_count'] ?? 0) > 0 ? (int) $workspace['guest_count'] : __('Not set', 'twostars-platform'))); ?></span></div>
						<div><strong><?php echo esc_html($is_booking ? __('Deadline', 'twostars-platform') : __('Follow Up', 'twostars-platform')); ?></strong><span><?php echo esc_html((string) ($workspace['due_descriptor'] ?: __('Not set', 'twostars-platform'))); ?></span></div>
						<div><strong><?php esc_html_e('Assignee', 'twostars-platform'); ?></strong><span><?php echo esc_html((string) ($workspace['assignee'] ?: __('Unassigned', 'twostars-platform'))); ?></span></div>
					</div>
				</article>
				<article class="twostars-manager-record-overview__card twostars-manager-record-overview__card--kpis">
					<div class="twostars-manager-record-kpis">
						<div class="twostars-manager-record-kpi">
							<span><?php esc_html_e('Current Status', 'twostars-platform'); ?></span>
							<strong><?php echo esc_html((string) ($workspace['status_label'] ?? __('Not set', 'twostars-platform'))); ?></strong>
						</div>
						<div class="twostars-manager-record-kpi">
							<span><?php esc_html_e('Priority', 'twostars-platform'); ?></span>
							<strong><?php echo esc_html((string) ($workspace['priority_label'] ?? __('Medium', 'twostars-platform'))); ?></strong>
						</div>
						<div class="twostars-manager-record-kpi">
							<span><?php echo esc_html($is_booking ? __('Next Deadline', 'twostars-platform') : __('Next Follow Up', 'twostars-platform')); ?></span>
							<strong><?php echo esc_html((string) ($workspace['next_due_label'] ?? __('Not set', 'twostars-platform'))); ?></strong>
						</div>
						<div class="twostars-manager-record-kpi">
							<span><?php esc_html_e('Activity Entries', 'twostars-platform'); ?></span>
							<strong><?php echo esc_html((string) ($workspace['activity_count'] ?? 0)); ?></strong>
						</div>
					</div>
				</article>
			</div>

			<form class="twostars-manager-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="twostars_manager_save_record">
				<input type="hidden" name="post_type" value="<?php echo esc_attr($post_type); ?>">
				<input type="hidden" name="post_id" value="<?php echo esc_attr((string) ($post?->ID ?? 0)); ?>">
				<?php wp_nonce_field('twostars_manager_save_record', 'twostars_manager_record_nonce'); ?>

				<div class="twostars-manager-record-workspace">
					<div class="twostars-manager-record-workspace__main">
						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('CRM Control Center', 'twostars-platform'); ?></h2>
								<p><?php echo esc_html($is_booking ? __('Update the operational status, assignee, payment state, and next move from one compact control surface.', 'twostars-platform') : __('Control pipeline status, follow-up ownership, contact cadence, and the next commercial move from one compact control surface.', 'twostars-platform')); ?></p>
							</div>
							<div class="twostars-manager-form__grid">
								<?php $this->render_record_fields_by_keys((int) ($post?->ID ?? 0), $field_definitions, $field_groups['crm']); ?>
							</div>
						</section>

						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php echo esc_html($is_booking ? __('Guest Snapshot', 'twostars-platform') : __('Contact Snapshot', 'twostars-platform')); ?></h2>
								<p><?php echo esc_html($is_booking ? __('Keep the guest identity, direct contact details, and connected visitor profile visible while you manage the booking.', 'twostars-platform') : __('Keep the lead identity, source, consent, and direct contact details visible while you manage follow-up.', 'twostars-platform')); ?></p>
							</div>
							<div class="twostars-manager-form__grid">
								<?php $this->render_record_fields_by_keys((int) ($post?->ID ?? 0), $field_definitions, $field_groups['contact']); ?>
							</div>
						</section>

						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php echo esc_html($is_booking ? __('Route And Logistics', 'twostars-platform') : __('Trip Intent', 'twostars-platform')); ?></h2>
								<p><?php echo esc_html($is_booking ? __('Review what was booked, when the guest wants to travel, and any pickup or route notes that affect fulfillment.', 'twostars-platform') : __('Keep the requested route, travel window, group size, and the lead’s original message together for quick sales context.', 'twostars-platform')); ?></p>
							</div>
							<div class="twostars-manager-form__grid">
								<?php $this->render_record_fields_by_keys((int) ($post?->ID ?? 0), $field_definitions, $field_groups['journey']); ?>
							</div>
						</section>

						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('Follow Up Desk', 'twostars-platform'); ?></h2>
								<p><?php echo esc_html($is_booking ? __('Use internal notes and timestamped updates to keep the booking handoff clear between sales and operations.', 'twostars-platform') : __('Use internal notes and timestamped updates to keep every lead touchpoint and follow-up attempt clear for the team.', 'twostars-platform')); ?></p>
							</div>
							<div class="twostars-manager-form__grid">
								<?php $this->render_record_fields_by_keys((int) ($post?->ID ?? 0), $field_definitions, $field_groups['internal']); ?>
								<label class="twostars-manager-form__field twostars-manager-form__field--wide">
									<span><?php esc_html_e('New CRM Update', 'twostars-platform'); ?></span>
									<textarea name="twostars_record_activity_entry" rows="4" placeholder="<?php echo esc_attr($is_booking ? __('Add the latest guest call note, payment update, or operational handoff detail...', 'twostars-platform') : __('Add the latest call note, quote update, or follow-up result...', 'twostars-platform')); ?>"></textarea>
								</label>
							</div>
						</section>

						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('Connected Context', 'twostars-platform'); ?></h2>
								<p><?php esc_html_e('Use linked visitor, source, and related-record context to understand the enquiry before taking the next action.', 'twostars-platform'); ?></p>
							</div>
							<div class="twostars-manager-record-context">
								<?php if (! empty($workspace['visitor_url'])) : ?>
									<a class="twostars-manager__quick-card" href="<?php echo esc_url((string) $workspace['visitor_url']); ?>">
										<h2><?php echo esc_html((string) ($workspace['visitor_label'] ?? __('Visitor profile', 'twostars-platform'))); ?></h2>
										<p><?php echo esc_html((string) ($workspace['visitor_copy'] ?? __('Open the linked visitor profile for session, map, and chat context.', 'twostars-platform'))); ?></p>
									</a>
								<?php endif; ?>
								<?php if (! empty($workspace['related_url'])) : ?>
									<a class="twostars-manager__quick-card" href="<?php echo esc_url((string) $workspace['related_url']); ?>">
										<h2><?php echo esc_html((string) ($workspace['related_label'] ?? __('Related record', 'twostars-platform'))); ?></h2>
										<p><?php echo esc_html((string) ($workspace['related_copy'] ?? __('Open the related CRM record.', 'twostars-platform'))); ?></p>
									</a>
								<?php endif; ?>
								<?php if (! empty($workspace['origin_url'])) : ?>
									<a class="twostars-manager__quick-card" href="<?php echo esc_url((string) $workspace['origin_url']); ?>" target="_blank" rel="noopener">
										<h2><?php esc_html_e('Origin Page', 'twostars-platform'); ?></h2>
										<p><?php echo esc_html((string) ($workspace['origin_url'] ?? '')); ?></p>
									</a>
								<?php endif; ?>
								<div class="twostars-manager__quick-card">
									<h2><?php esc_html_e('System Trace', 'twostars-platform'); ?></h2>
									<p><?php echo esc_html((string) ($workspace['system_copy'] ?? __('No source trace is available for this record yet.', 'twostars-platform'))); ?></p>
								</div>
							</div>
						</section>
					</div>

					<aside class="twostars-manager-record-workspace__sidebar">
						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('Action Dock', 'twostars-platform'); ?></h2>
								<p><?php esc_html_e('Save, contact, and move the record forward from one compact operator dock.', 'twostars-platform'); ?></p>
							</div>
							<div class="twostars-manager-record-dock__actions">
								<button type="submit" class="button button-primary button-hero"><?php esc_html_e('Save Workspace', 'twostars-platform'); ?></button>
								<a class="button" href="<?php echo esc_url($manager_url); ?>"><?php esc_html_e('Cancel', 'twostars-platform'); ?></a>
								<?php if (! empty($workspace['phone'])) : ?>
									<a class="button" href="<?php echo esc_url('tel:' . preg_replace('/[^0-9+]/', '', (string) $workspace['phone'])); ?>"><?php esc_html_e('Call', 'twostars-platform'); ?></a>
								<?php endif; ?>
								<?php if (! empty($workspace['email'])) : ?>
									<a class="button" href="<?php echo esc_url('mailto:' . (string) $workspace['email']); ?>"><?php echo esc_html($is_booking ? __('Email Guest', 'twostars-platform') : __('Email Lead', 'twostars-platform')); ?></a>
								<?php endif; ?>
								<?php if (! empty($workspace['whatsapp_url'])) : ?>
									<a class="button" href="<?php echo esc_url((string) $workspace['whatsapp_url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('WhatsApp', 'twostars-platform'); ?></a>
								<?php endif; ?>
								<?php if (($post?->ID ?? 0) > 0) : ?>
									<a
										class="button twostars-manager-button--danger"
										href="<?php echo esc_url($this->get_record_delete_url($post_type, (int) $post->ID, $manager_url)); ?>"
										onclick="return confirm('<?php echo esc_js($is_booking ? __('Move this booking to trash? The CRM pipeline will update automatically.', 'twostars-platform') : __('Move this lead to trash? The CRM pipeline will update automatically.', 'twostars-platform')); ?>');"
									><?php echo esc_html($is_booking ? __('Trash Booking', 'twostars-platform') : __('Trash Lead', 'twostars-platform')); ?></a>
								<?php endif; ?>
							</div>
							<div class="twostars-manager-record-quick-actions">
								<?php foreach ($this->get_record_quick_actions($post_type) as $action_key => $action_label) : ?>
									<button type="submit" class="button" name="twostars_record_quick_action" value="<?php echo esc_attr($action_key); ?>"><?php echo esc_html($action_label); ?></button>
								<?php endforeach; ?>
							</div>
						</section>

						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('Recommended Next Steps', 'twostars-platform'); ?></h2>
							</div>
							<ul class="twostars-manager-record-checklist">
								<?php foreach ($this->get_record_checklist($post_type, $workspace) as $item) : ?>
									<li><?php echo esc_html($item); ?></li>
								<?php endforeach; ?>
							</ul>
						</section>

						<section class="twostars-manager-form__section">
							<div class="twostars-manager-form__section-head">
								<h2><?php esc_html_e('CRM Timeline', 'twostars-platform'); ?></h2>
								<p><?php esc_html_e('The latest touches, due-date changes, and operator notes recorded against this record.', 'twostars-platform'); ?></p>
							</div>
							<div class="twostars-manager-record-timeline">
								<?php foreach ((array) ($workspace['timeline'] ?? []) as $timeline_item) : ?>
									<article class="twostars-manager-record-timeline__item">
										<strong><?php echo esc_html((string) ($timeline_item['title'] ?? '')); ?></strong>
										<span><?php echo esc_html((string) ($timeline_item['time'] ?? '')); ?></span>
										<p><?php echo esc_html((string) ($timeline_item['body'] ?? '')); ?></p>
									</article>
								<?php endforeach; ?>
							</div>
						</section>
					</aside>
				</div>
			</form>
		</div>
		<?php
	}

	private function render_post_card(WP_Post $post): void
	{
		$is_tour = $post->post_type === 'tour';
		$image = get_the_post_thumbnail_url($post, 'medium_large');
		$excerpt = wp_trim_words(wp_strip_all_tags($post->post_excerpt !== '' ? $post->post_excerpt : $post->post_content), 24, '...');
		$edit_url = $this->get_form_url($post->post_type, (int) $post->ID);
		$view_url = get_permalink($post);
		$status = get_post_status_object($post->post_status);
		$meta_items = [];

		if ($is_tour && class_exists(TourData::class)) {
			$meta_items = array_values(array_map(static fn(array $item): string => (string) ($item['value'] ?? ''), TourData::get_summary_items($post->ID)));
		}

		if (! $is_tour) {
			if ($post->post_type === 'testimonial') {
				$rating = (int) get_post_meta($post->ID, '_twostars_review_rating', true);
				$trip = (string) get_post_meta($post->ID, '_twostars_review_trip_label', true);
				$location = (string) get_post_meta($post->ID, '_twostars_review_guest_location', true);

				if ($rating > 0) {
					$meta_items[] = sprintf(_n('%d Star', '%d Stars', $rating, 'twostars-platform'), $rating);
				}

				if ($trip !== '') {
					$meta_items[] = $trip;
				}

				if ($location !== '') {
					$meta_items[] = $location;
				}
			} else {
				$best_for = (string) get_post_meta($post->ID, '_twostars_destination_best_for', true);
				$region = (string) get_post_meta($post->ID, '_twostars_destination_region', true);

				if ($best_for !== '') {
					$meta_items[] = $best_for;
				}

				if ($region !== '') {
					$meta_items[] = $region;
				}
			}
		}
		?>
		<article class="twostars-manager-card">
			<div class="twostars-manager-card__media">
				<?php if (is_string($image) && $image !== '') : ?>
					<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($post->post_title); ?>">
				<?php else : ?>
					<div class="twostars-manager-card__media-placeholder"></div>
				<?php endif; ?>
				<span class="twostars-manager-card__status twostars-manager-card__status--<?php echo esc_attr($post->post_status); ?>">
					<?php echo esc_html($status ? $status->label : ucfirst($post->post_status)); ?>
				</span>
			</div>
			<div class="twostars-manager-card__body">
				<h2><?php echo esc_html($post->post_title); ?></h2>
				<p><?php echo esc_html($excerpt); ?></p>
				<?php if ($meta_items !== []) : ?>
					<div class="twostars-manager-card__chips">
						<?php foreach (array_slice(array_filter($meta_items), 0, 3) as $item) : ?>
							<span class="twostars-manager-card__chip"><?php echo esc_html($item); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="twostars-manager-card__actions">
				<a class="button button-primary" href="<?php echo esc_url((string) $edit_url); ?>"><?php esc_html_e('Edit', 'twostars-platform'); ?></a>
				<a class="button" href="<?php echo esc_url($this->get_form_url($post->post_type)); ?>"><?php esc_html_e('Add New', 'twostars-platform'); ?></a>
				<?php if ($view_url !== false) : ?>
					<a class="button" href="<?php echo esc_url((string) $view_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('View', 'twostars-platform'); ?></a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	private function render_review_row(WP_Post $review): void
	{
		$edit_url = $this->get_form_url('testimonial', (int) $review->ID);
		$rating = (int) get_post_meta($review->ID, '_twostars_review_rating', true);
		$trip = (string) get_post_meta($review->ID, '_twostars_review_trip_label', true);
		$location = (string) get_post_meta($review->ID, '_twostars_review_guest_location', true);
		$source = (string) get_post_meta($review->ID, '_twostars_review_source', true);
		$featured = (string) get_post_meta($review->ID, '_twostars_review_featured', true) === 'featured';
		$status_object = get_post_status_object($review->post_status);
		$status_label = $status_object ? $status_object->label : ucfirst($review->post_status);
		?>
		<tr>
			<td>
				<strong><?php echo esc_html($review->post_title); ?></strong>
				<div class="twostars-manager-table__sub"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($review->post_content), 18, '...')); ?></div>
			</td>
			<td><?php echo $rating > 0 ? esc_html(str_repeat('★', $rating)) : '&mdash;'; ?></td>
			<td>
				<div><?php echo $trip !== '' ? esc_html($trip) : '&mdash;'; ?></div>
				<?php if ($location !== '') : ?>
					<div class="twostars-manager-table__sub"><?php echo esc_html($location); ?></div>
				<?php endif; ?>
			</td>
			<td>
				<div><?php echo $source !== '' ? esc_html(ucfirst($source)) : '&mdash;'; ?></div>
				<?php if ($featured) : ?>
					<div class="twostars-manager-table__sub"><?php esc_html_e('Homepage Featured', 'twostars-platform'); ?></div>
				<?php endif; ?>
			</td>
			<td><span class="twostars-manager-badge"><?php echo esc_html((string) $status_label); ?></span></td>
			<td><?php echo esc_html(wp_date('Y-m-d H:i', strtotime($review->post_date))); ?></td>
			<td>
				<div class="twostars-manager-table__actions">
					<a class="button button-primary" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Open', 'twostars-platform'); ?></a>
					<?php if ($review->post_status !== 'publish') : ?>
						<a class="button" href="<?php echo esc_url($this->get_review_status_url((int) $review->ID, 'publish')); ?>"><?php esc_html_e('Approve', 'twostars-platform'); ?></a>
					<?php else : ?>
						<a class="button" href="<?php echo esc_url($this->get_review_status_url((int) $review->ID, 'draft')); ?>"><?php esc_html_e('Move To Draft', 'twostars-platform'); ?></a>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	private function render_review_board_card(WP_Post $review): void
	{
		$edit_url = $this->get_form_url('testimonial', (int) $review->ID);
		$rating = (int) get_post_meta($review->ID, '_twostars_review_rating', true);
		$trip = (string) get_post_meta($review->ID, '_twostars_review_trip_label', true);
		$location = (string) get_post_meta($review->ID, '_twostars_review_guest_location', true);
		$source = (string) get_post_meta($review->ID, '_twostars_review_source', true);
		$featured = (string) get_post_meta($review->ID, '_twostars_review_featured', true) === 'featured';
		$status_object = get_post_status_object($review->post_status);
		$status_label = $status_object ? $status_object->label : ucfirst($review->post_status);
		?>
		<article class="twostars-manager-review-card">
			<div class="twostars-manager-review-card__head">
				<div>
					<p class="twostars-manager-review-card__eyebrow"><?php echo esc_html($source !== '' ? ucfirst($source) : __('Website', 'twostars-platform')); ?></p>
					<h3><?php echo esc_html($review->post_title); ?></h3>
				</div>
				<span class="twostars-manager-badge"><?php echo esc_html((string) $status_label); ?></span>
			</div>
			<div class="twostars-manager-review-card__rating">
				<?php echo $rating > 0 ? esc_html(str_repeat('★', $rating)) : esc_html__('No rating', 'twostars-platform'); ?>
			</div>
			<p class="twostars-manager-review-card__excerpt"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($review->post_content), 28, '...')); ?></p>
			<div class="twostars-manager-review-card__meta">
				<span><?php echo esc_html($trip !== '' ? $trip : __('Trip not set', 'twostars-platform')); ?></span>
				<span><?php echo esc_html($location !== '' ? $location : __('Location not set', 'twostars-platform')); ?></span>
				<span><?php echo esc_html(wp_date('Y-m-d H:i', strtotime($review->post_date))); ?></span>
				<?php if ($featured) : ?>
					<span><?php esc_html_e('Homepage Featured', 'twostars-platform'); ?></span>
				<?php endif; ?>
			</div>
			<div class="twostars-manager-review-card__actions">
				<a class="button button-primary" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Open', 'twostars-platform'); ?></a>
				<?php if ($review->post_status !== 'publish') : ?>
					<a class="button" href="<?php echo esc_url($this->get_review_status_url((int) $review->ID, 'publish')); ?>"><?php esc_html_e('Approve', 'twostars-platform'); ?></a>
				<?php else : ?>
					<a class="button" href="<?php echo esc_url($this->get_review_status_url((int) $review->ID, 'draft')); ?>"><?php esc_html_e('Move To Draft', 'twostars-platform'); ?></a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	public function redirect_default_editor(): void
	{
		if (! is_admin() || wp_doing_ajax() || wp_doing_cron()) {
			return;
		}

		if (! current_user_can('edit_posts')) {
			return;
		}

		if (is_network_admin() || is_user_admin()) {
			return;
		}

		$script = isset($_SERVER['SCRIPT_NAME']) ? wp_unslash($_SERVER['SCRIPT_NAME']) : '';
		$script = is_string($script) ? basename($script) : '';

		if ($script === 'index.php' && ! isset($_GET['page']) && ! isset($_GET['post_type']) && ! isset($_GET['post']) && ! isset($_GET['taxonomy'])) {
			wp_safe_redirect(admin_url('admin.php?page=twostars-manager'));
			exit;
		}

		if ($script === 'post-new.php') {
			$post_type = sanitize_key((string) ($_GET['post_type'] ?? 'post'));

			if (in_array($post_type, ['tour', 'destination', 'testimonial', 'booking', 'lead'], true)) {
				wp_safe_redirect($this->get_form_url($post_type));
				exit;
			}
		}

		if ($script === 'post.php' && (string) ($_GET['action'] ?? 'edit') === 'edit') {
			$post_id = absint($_GET['post'] ?? 0);
			$post = $post_id > 0 ? get_post($post_id) : null;

			if ($post instanceof WP_Post && in_array($post->post_type, ['tour', 'destination', 'testimonial', 'booking', 'lead'], true)) {
				wp_safe_redirect($this->get_form_url($post->post_type, $post->ID));
				exit;
			}
		}
	}

	public function handle_save_content(): void
	{
		if (! current_user_can('edit_posts')) {
			wp_die(esc_html__('You do not have permission to save this content.', 'twostars-platform'));
		}

		check_admin_referer('twostars_manager_save_content', 'twostars_manager_nonce');

		$post_type = sanitize_key((string) ($_POST['post_type'] ?? ''));
		$post_id = absint($_POST['post_id'] ?? 0);

		if (! in_array($post_type, ['tour', 'destination', 'testimonial'], true)) {
			wp_die(esc_html__('Invalid content type.', 'twostars-platform'));
		}

		$postarr = [
			'post_type'    => $post_type,
			'post_title'   => sanitize_text_field((string) ($_POST['post_title'] ?? '')),
			'post_excerpt' => sanitize_textarea_field((string) ($_POST['post_excerpt'] ?? '')),
			'post_content' => wp_kses_post((string) ($_POST['post_content'] ?? '')),
			'post_status'  => sanitize_key((string) ($_POST['post_status'] ?? 'draft')),
		];

		$slug = sanitize_title((string) ($_POST['post_slug'] ?? ''));

		if ($slug !== '') {
			$postarr['post_name'] = $slug;
		}

		if ($post_id > 0) {
			$postarr['ID'] = $post_id;
			$saved_id = wp_update_post(wp_slash($postarr), true);
		} else {
			$saved_id = wp_insert_post(wp_slash($postarr), true);
		}

		if (is_wp_error($saved_id)) {
			wp_die(esc_html($saved_id->get_error_message()));
		}

		$featured_image_id = absint($_POST['featured_image_id'] ?? 0);

		if ($featured_image_id > 0) {
			set_post_thumbnail((int) $saved_id, $featured_image_id);
		} else {
			delete_post_thumbnail((int) $saved_id);
		}

		$field_definitions = $post_type === 'tour'
			? TourMeta::get_manager_field_definitions()
			: ($post_type === 'testimonial' ? ReviewMeta::get_manager_field_definitions() : DestinationMeta::get_manager_field_definitions());

		foreach ($field_definitions as $meta_key => $definition) {
			$field_name = ltrim($meta_key, '_');
			$raw_value = $_POST[$field_name] ?? null;
			$value = $this->sanitize_manager_value((string) ($definition['type'] ?? 'text'), $raw_value, $definition);

			if ($value === '' || $value === null || $value === []) {
				delete_post_meta((int) $saved_id, $meta_key);
				continue;
			}

			update_post_meta((int) $saved_id, $meta_key, $value);
		}

		$redirect = add_query_arg(
			[
				'page'    => $post_type === 'tour' ? 'twostars-manager-tour-edit' : ($post_type === 'testimonial' ? 'twostars-manager-review-edit' : 'twostars-manager-destination-edit'),
				'post_id' => (int) $saved_id,
				'saved'   => '1',
			],
			admin_url('admin.php')
		);

		wp_safe_redirect($redirect);
		exit;
	}

	public function handle_update_review_status(): void
	{
		if (! current_user_can('edit_posts')) {
			wp_die(esc_html__('You do not have permission to update this review.', 'twostars-platform'));
		}

		check_admin_referer('twostars_manager_update_review_status');

		$post_id = absint($_GET['post_id'] ?? 0);
		$status = sanitize_key((string) ($_GET['status'] ?? 'draft'));
		$post = $post_id > 0 ? get_post($post_id) : null;

		if (! $post instanceof WP_Post || $post->post_type !== 'testimonial') {
			wp_die(esc_html__('That review could not be found.', 'twostars-platform'));
		}

		if (! in_array($status, ['draft', 'publish'], true)) {
			$status = 'draft';
		}

		wp_update_post(
			[
				'ID'          => $post->ID,
				'post_status' => $status,
			]
		);

		$redirect = add_query_arg(
			[
				'page'         => 'twostars-manager-reviews',
				'saved'        => '1',
				'review_status'=> $status === 'publish' ? 'publish' : 'draft',
			],
			admin_url('admin.php')
		);

		wp_safe_redirect($redirect);
		exit;
	}

	public function handle_save_record(): void
	{
		if (! current_user_can('edit_posts')) {
			wp_die(esc_html__('You do not have permission to save this record.', 'twostars-platform'));
		}

		check_admin_referer('twostars_manager_save_record', 'twostars_manager_record_nonce');

		$post_type = sanitize_key((string) ($_POST['post_type'] ?? ''));
		$post_id = absint($_POST['post_id'] ?? 0);

		if (! in_array($post_type, ['booking', 'lead'], true)) {
			wp_die(esc_html__('Invalid record type.', 'twostars-platform'));
		}

		$field_definitions = $post_type === 'booking' ? BookingManager::get_manager_field_definitions() : LeadManager::get_manager_field_definitions();
		$values = [];
		$status_meta_key = $post_type === 'booking' ? '_twostars_booking_status' : '_twostars_lead_status';
		$due_meta_key = $post_type === 'booking' ? '_twostars_booking_confirmation_deadline' : '_twostars_lead_follow_up_date';
		$existing_status = $post_id > 0 ? (string) get_post_meta($post_id, $status_meta_key, true) : '';
		$existing_due = $post_id > 0 ? (string) get_post_meta($post_id, $due_meta_key, true) : '';
		$activity_entry = sanitize_textarea_field((string) ($_POST['twostars_record_activity_entry'] ?? ''));
		$quick_action = sanitize_key((string) ($_POST['twostars_record_quick_action'] ?? ''));

		foreach ($field_definitions as $meta_key => $definition) {
			$field_name = ltrim($meta_key, '_');
			$type = (string) ($definition['type'] ?? 'text');

			if (! array_key_exists($field_name, $_POST)) {
				if ($type === 'checkbox') {
					$values[$meta_key] = $post_id > 0 ? get_post_meta($post_id, $meta_key, true) : '';
					continue;
				}

				$values[$meta_key] = $post_id > 0 ? get_post_meta($post_id, $meta_key, true) : '';
				continue;
			}

			$values[$meta_key] = $this->sanitize_record_value($type, $_POST[$field_name] ?? null, $definition);
		}

		$auto_note = $this->apply_record_quick_action($post_type, $values, $quick_action);
		$title = $this->build_record_title($post_type, $values, $post_id);
		$postarr = [
			'post_type'   => $post_type,
			'post_status' => 'private',
			'post_title'  => $title,
		];

		if ($post_id > 0) {
			$postarr['ID'] = $post_id;
			$saved_id = wp_update_post(wp_slash($postarr), true);
		} else {
			$saved_id = wp_insert_post(wp_slash($postarr), true);
		}

		if (is_wp_error($saved_id)) {
			wp_die(esc_html($saved_id->get_error_message()));
		}

		foreach ($values as $meta_key => $value) {
			if ($value === '' || $value === null || $value === 0 || $value === false) {
				delete_post_meta((int) $saved_id, $meta_key);
				continue;
			}

			update_post_meta((int) $saved_id, $meta_key, $value);
		}

		$new_status = (string) ($values[$status_meta_key] ?? '');
		$new_due = (string) ($values[$due_meta_key] ?? '');

		if ($existing_status !== '' && $new_status !== '' && $existing_status !== $new_status) {
			$this->append_record_activity_entry(
				$post_type,
				(int) $saved_id,
				__('Status Updated', 'twostars-platform'),
				sprintf(__('Status changed from %1$s to %2$s.', 'twostars-platform'), $this->get_record_status_label($post_type, $existing_status), $this->get_record_status_label($post_type, $new_status))
			);
		}

		if ($new_due !== '' && $existing_due !== $new_due) {
			$this->append_record_activity_entry(
				$post_type,
				(int) $saved_id,
				$post_type === 'booking' ? __('Deadline Updated', 'twostars-platform') : __('Follow Up Updated', 'twostars-platform'),
				$post_type === 'booking'
					? sprintf(__('Confirmation deadline set to %s.', 'twostars-platform'), $new_due)
					: sprintf(__('Next follow-up date set to %s.', 'twostars-platform'), $new_due)
			);
		}

		if ($auto_note !== '') {
			$this->append_record_activity_entry($post_type, (int) $saved_id, __('Quick Action', 'twostars-platform'), $auto_note);
		}

		if ($activity_entry !== '') {
			$this->append_record_activity_entry($post_type, (int) $saved_id, __('CRM Update', 'twostars-platform'), $activity_entry);
		}

		if ($post_type === 'booking') {
			CoreCRM::sync_booking((int) $saved_id);
		} else {
			CoreCRM::sync_lead((int) $saved_id);
		}

		$redirect = add_query_arg(
			[
				'page'    => $post_type === 'booking' ? 'twostars-manager-booking-edit' : 'twostars-manager-lead-edit',
				'post_id' => (int) $saved_id,
				'saved'   => '1',
			],
			admin_url('admin.php')
		);

		wp_safe_redirect($redirect);
		exit;
	}

	public function handle_save_gallery_library(): void
	{
		if (! current_user_can('edit_posts')) {
			wp_die(esc_html__('You do not have permission to update the gallery library.', 'twostars-platform'));
		}

		check_admin_referer('twostars_manager_save_gallery_library', 'twostars_manager_gallery_library_nonce');

		$image_ids = $this->sanitize_manager_value(
			'gallery',
			(string) ($_POST['twostars_gallery_library_images'] ?? ''),
			[]
		);
		$videos = $this->sanitize_gallery_library_videos($_POST['twostars_gallery_library_videos'] ?? []);

		update_option('twostars_gallery_library_images', is_array($image_ids) ? $image_ids : []);
		update_option('twostars_gallery_library_videos', $videos);

		wp_safe_redirect(
			add_query_arg(
				[
					'page'  => 'twostars-manager-gallery-library',
					'saved' => '1',
				],
				admin_url('admin.php')
			)
		);
		exit;
	}

	private function render_notice(): void
	{
		if ((string) ($_GET['deleted'] ?? '') === '1') {
			$deleted_type = sanitize_key((string) ($_GET['deleted_type'] ?? 'record'));
			$message = $deleted_type === 'booking'
				? __('Booking moved to trash successfully.', 'twostars-platform')
				: __('Lead moved to trash successfully.', 'twostars-platform');
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html($message); ?></p>
			</div>
			<?php

			return;
		}

		if ((string) ($_GET['saved'] ?? '') !== '1') {
			return;
		}
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e('Content saved successfully.', 'twostars-platform'); ?></p>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $card
	 */
	private function render_dashboard_card(array $card): void
	{
		$actions = array_values(array_filter((array) ($card['actions'] ?? [])));
		?>
		<article class="twostars-manager-dashboard-card">
			<p class="twostars-manager-dashboard-card__eyebrow"><?php echo esc_html((string) ($card['eyebrow'] ?? '')); ?></p>
			<h2><?php echo esc_html((string) ($card['title'] ?? '')); ?></h2>
			<p><?php echo esc_html((string) ($card['description'] ?? '')); ?></p>
			<?php if (! empty($card['stats'])) : ?>
				<div class="twostars-manager-dashboard-card__chips">
					<?php foreach ((array) $card['stats'] as $stat) : ?>
						<span class="twostars-manager-dashboard-card__chip"><?php echo esc_html((string) $stat); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ($actions !== []) : ?>
				<div class="twostars-manager-dashboard-card__actions">
					<?php foreach ($actions as $action) : ?>
						<a
							class="button<?php echo ! empty($action['primary']) ? ' button-primary' : ''; ?>"
							href="<?php echo esc_url((string) ($action['url'] ?? '#')); ?>"
							<?php echo ! empty($action['target']) ? ' target="_blank" rel="noopener"' : ''; ?>
						>
							<?php echo esc_html((string) ($action['label'] ?? 'Open')); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</article>
		<?php
	}

	private function get_record_delete_url(string $post_type, int $post_id, string $redirect_url): string
	{
		$url = add_query_arg(
			[
				'action'      => 'twostars_crm_delete_record',
				'post_type'   => $post_type,
				'post_id'     => $post_id,
				'redirect_to' => $redirect_url,
			],
			admin_url('admin-post.php')
		);

		return wp_nonce_url($url, 'twostars_crm_delete_record_' . $post_type . '_' . $post_id);
	}

	private function render_manager_field(int $post_id, string $meta_key, array $definition): void
	{
		$value = $post_id > 0 ? get_post_meta($post_id, $meta_key, true) : '';
		$field_id = ltrim($meta_key, '_');
		$type = (string) ($definition['type'] ?? 'text');
		$wide = in_array($type, ['textarea', 'gallery'], true) ? ' twostars-manager-form__field--wide' : '';
		?>
		<label class="twostars-manager-form__field<?php echo esc_attr($wide); ?>">
			<span><?php echo esc_html((string) ($definition['label'] ?? '')); ?></span>
			<?php if ($type === 'textarea') : ?>
				<textarea name="<?php echo esc_attr($field_id); ?>" rows="5"><?php echo esc_textarea((string) $value); ?></textarea>
			<?php elseif ($type === 'select') : ?>
				<select name="<?php echo esc_attr($field_id); ?>">
					<option value=""><?php esc_html_e('Select an option', 'twostars-platform'); ?></option>
					<?php foreach ((array) ($definition['choices'] ?? []) as $option_value => $option_label) : ?>
						<option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected((string) $value, (string) $option_value); ?>><?php echo esc_html((string) $option_label); ?></option>
					<?php endforeach; ?>
				</select>
			<?php elseif ($type === 'multiselect') : ?>
				<?php $selected_values = is_array($value) ? array_map('intval', $value) : []; ?>
				<select name="<?php echo esc_attr($field_id); ?>[]" multiple size="6">
					<?php foreach ((array) ($definition['choices'] ?? []) as $option_value => $option_label) : ?>
						<option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected(in_array((int) $option_value, $selected_values, true), true); ?>><?php echo esc_html((string) $option_label); ?></option>
					<?php endforeach; ?>
				</select>
			<?php elseif ($type === 'gallery') : ?>
				<?php $this->render_media_field($field_id, is_array($value) ? $value : [], true); ?>
			<?php else : ?>
				<input
					type="<?php echo esc_attr($type === 'number' ? 'number' : 'text'); ?>"
					name="<?php echo esc_attr($field_id); ?>"
					value="<?php echo esc_attr(is_array($value) ? '' : (string) $value); ?>"
					step="<?php echo esc_attr((string) ($definition['step'] ?? '')); ?>"
				>
			<?php endif; ?>
		</label>
		<?php
	}

	private function render_record_field(int $post_id, string $meta_key, array $definition): void
	{
		$value = $post_id > 0 ? get_post_meta($post_id, $meta_key, true) : '';
		$field_id = ltrim($meta_key, '_');
		$type = (string) ($definition['type'] ?? 'text');
		$wide = in_array($type, ['textarea'], true) ? ' twostars-manager-form__field--wide' : '';
		?>
		<label class="twostars-manager-form__field<?php echo esc_attr($wide); ?>">
			<span><?php echo esc_html((string) ($definition['label'] ?? '')); ?></span>
			<?php if ($type === 'textarea') : ?>
				<textarea name="<?php echo esc_attr($field_id); ?>" rows="5"><?php echo esc_textarea((string) $value); ?></textarea>
			<?php elseif ($type === 'select') : ?>
				<select name="<?php echo esc_attr($field_id); ?>">
					<option value=""><?php esc_html_e('Select an option', 'twostars-platform'); ?></option>
					<?php foreach ((array) ($definition['choices'] ?? []) as $option_value => $option_label) : ?>
						<option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected((string) $value, (string) $option_value); ?>><?php echo esc_html((string) $option_label); ?></option>
					<?php endforeach; ?>
				</select>
			<?php elseif ($type === 'select_dynamic') : ?>
				<select name="<?php echo esc_attr($field_id); ?>">
					<option value=""><?php esc_html_e('Select a tour', 'twostars-platform'); ?></option>
					<?php foreach (TourData::get_tour_options() as $option_value => $option_label) : ?>
						<option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected((string) $value, (string) $option_value); ?>><?php echo esc_html((string) $option_label); ?></option>
					<?php endforeach; ?>
				</select>
			<?php elseif ($type === 'checkbox') : ?>
				<label class="twostars-manager-form__checkbox">
					<input type="hidden" name="<?php echo esc_attr($field_id); ?>" value="">
					<input type="checkbox" name="<?php echo esc_attr($field_id); ?>" value="1" <?php checked(! empty($value)); ?>>
					<span><?php esc_html_e('Enabled', 'twostars-platform'); ?></span>
				</label>
			<?php else : ?>
				<input
					type="<?php echo esc_attr(in_array($type, ['email', 'url', 'date', 'number', 'datetime-local'], true) ? $type : 'text'); ?>"
					name="<?php echo esc_attr($field_id); ?>"
					value="<?php echo esc_attr(is_array($value) ? '' : (string) $value); ?>"
					step="<?php echo esc_attr((string) ($definition['step'] ?? '')); ?>"
				>
			<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * @param array<int|string, mixed> $meta_query
	 */
	private function count_records_by_meta_query(string $post_type, array $meta_query): int
	{
		$query = new \WP_Query(
			[
				'post_type'      => $post_type,
				'post_status'    => ['private', 'publish', 'draft'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => $meta_query,
			]
		);

		return (int) $query->found_posts;
	}

	/**
	 * @param array<int, mixed> $image_ids
	 */
	private function render_media_field(string $field_id, array $image_ids, bool $multiple = true): void
	{
		$image_ids = array_values(array_filter(array_map('absint', $image_ids)));
		$button_label = $multiple ? __('Choose Images', 'twostars-platform') : __('Choose Image', 'twostars-platform');
		?>
		<div class="twostars-media-field" data-twostars-media-field data-target-input="<?php echo esc_attr($field_id); ?>" data-multiple="<?php echo $multiple ? '1' : '0'; ?>">
			<input type="hidden" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" value="<?php echo esc_attr(implode(',', $image_ids)); ?>">
			<div class="twostars-media-field__preview" data-twostars-media-preview>
				<?php foreach ($image_ids as $image_id) : ?>
					<?php $thumb = wp_get_attachment_image_url($image_id, 'thumbnail'); ?>
					<?php if (is_string($thumb) && $thumb !== '') : ?>
						<div class="twostars-media-field__thumb" data-attachment-id="<?php echo esc_attr((string) $image_id); ?>">
							<img src="<?php echo esc_url($thumb); ?>" alt="">
							<button type="button" class="twostars-media-field__remove" data-twostars-media-remove aria-label="<?php esc_attr_e('Remove image', 'twostars-platform'); ?>">
								<span aria-hidden="true">x</span>
							</button>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<div class="twostars-media-field__actions">
				<button type="button" class="button button-secondary" data-twostars-media-open><?php echo esc_html($button_label); ?></button>
				<button type="button" class="button-link-delete" data-twostars-media-clear><?php esc_html_e('Clear', 'twostars-platform'); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * @return array<int, int>
	 */
	private function get_gallery_library_images(): array
	{
		$value = get_option('twostars_gallery_library_images', []);

		if (is_string($value)) {
			$value = explode(',', $value);
		}

		return array_values(array_unique(array_filter(array_map('absint', is_array($value) ? $value : []))));
	}

	/**
	 * @return array<int, array<string, int|string>>
	 */
	private function get_gallery_library_videos(): array
	{
		return $this->sanitize_gallery_library_videos(get_option('twostars_gallery_library_videos', []));
	}

	/**
	 * @return array<int, array<string, int|string>>
	 */
	private function sanitize_gallery_library_videos(mixed $value): array
	{
		if (is_string($value)) {
			$decoded = json_decode(wp_unslash($value), true);
			$value   = is_array($decoded) ? $decoded : [];
		}

		$videos = [];

		foreach (is_array($value) ? $value : [] as $index => $video) {
			if (! is_array($video)) {
				continue;
			}

			$video_url = esc_url_raw((string) ($video['video_url'] ?? ''));

			if ($video_url === '') {
				continue;
			}

			$videos[] = [
				'video_url'      => $video_url,
				'video_caption'  => sanitize_text_field((string) ($video['video_caption'] ?? '')),
				'video_category' => sanitize_title((string) ($video['video_category'] ?? '')),
				'video_position' => max(1, absint($video['video_position'] ?? ($index + 1))),
				'video_autoplay' => (($video['video_autoplay'] ?? 'yes') === 'yes') ? 'yes' : '',
				'video_muted'    => (($video['video_muted'] ?? 'yes') === 'yes') ? 'yes' : '',
				'video_loop'     => (($video['video_loop'] ?? 'yes') === 'yes') ? 'yes' : '',
			];
		}

		return $videos;
	}

	private function sanitize_manager_value(string $type, mixed $value, array $definition): mixed
	{
		if ($value === null) {
			return null;
		}

		if (is_string($value)) {
			$value = trim($value);
		}

		if ($value === '') {
			return '';
		}

		return match ($type) {
			'textarea'   => sanitize_textarea_field((string) $value),
			'url'        => esc_url_raw((string) $value),
			'multiselect' => array_values(array_unique(array_filter(array_map('absint', is_array($value) ? $value : [$value])))),
			'gallery'    => array_values(array_unique(array_filter(array_map('absint', is_array($value) ? $value : explode(',', (string) $value))))),
			'select'     => in_array(sanitize_text_field((string) $value), array_keys((array) ($definition['choices'] ?? [])), true)
				? (($definition['meta_type'] ?? '') === 'integer' ? absint($value) : sanitize_text_field((string) $value))
				: '',
			'number'     => (($definition['meta_type'] ?? '') === 'integer') ? absint($value) : (is_numeric($value) ? (float) $value : ''),
			default      => sanitize_text_field((string) $value),
		};
	}

	private function sanitize_record_value(string $type, mixed $value, array $definition): mixed
	{
		if ($value === null) {
			return null;
		}

		if ($type === 'checkbox') {
			return ! empty($value) ? '1' : '';
		}

		if (is_string($value)) {
			$value = trim($value);
		}

		if ($value === '') {
			return '';
		}

		return match ($type) {
			'textarea' => sanitize_textarea_field((string) $value),
			'email' => sanitize_email((string) $value),
			'url' => esc_url_raw((string) $value),
			'select_dynamic', 'post_id', 'number' => (($definition['meta_type'] ?? '') === 'integer' || in_array($type, ['select_dynamic', 'post_id'], true)) ? absint($value) : sanitize_text_field((string) $value),
			'select' => in_array(sanitize_text_field((string) $value), array_keys((array) ($definition['choices'] ?? [])), true) ? sanitize_text_field((string) $value) : '',
			default => sanitize_text_field((string) $value),
		};
	}

	private function get_record_status_tone(string $post_type, string $status): string
	{
		$status = sanitize_key($status);

		if ($post_type === 'booking') {
			return match ($status) {
				'confirmed', 'completed' => 'good',
				'cancelled' => 'danger',
				'pending', 'contacted' => 'warm',
				default => 'neutral',
			};
		}

		return match ($status) {
			'won' => 'good',
			'lost' => 'danger',
			'qualified', 'quote_sent', 'booking_pending' => 'cool',
			'new', 'contacted', 'follow_up_scheduled' => 'warm',
			default => 'neutral',
		};
	}

	private function get_priority_tone(string $priority): string
	{
		$priority = sanitize_key($priority);

		return match ($priority) {
			'urgent', 'high' => 'danger',
			'medium' => 'warm',
			'low' => 'neutral',
			default => 'neutral',
		};
	}

	private function get_due_tone(string $date): string
	{
		$date = trim($date);

		if ($date === '') {
			return 'neutral';
		}

		$today = wp_date('Y-m-d');

		if ($date < $today) {
			return 'danger';
		}

		if ($date === $today) {
			return 'warm';
		}

		return 'cool';
	}

	private function format_due_descriptor(string $date, string $post_type): string
	{
		$date = trim($date);

		if ($date === '') {
			return '';
		}

		$label = $post_type === 'booking' ? __('Deadline', 'twostars-platform') : __('Follow Up', 'twostars-platform');
		$today = wp_date('Y-m-d');

		if ($date < $today) {
			return sprintf(__('%1$s overdue since %2$s', 'twostars-platform'), $label, $date);
		}

		if ($date === $today) {
			return sprintf(__('%s due today', 'twostars-platform'), $label);
		}

		return sprintf(__('%1$s set for %2$s', 'twostars-platform'), $label, $date);
	}

	private function build_record_title(string $post_type, array $values, int $post_id): string
	{
		if ($post_type === 'booking') {
			$name = (string) ($values['_twostars_booking_full_name'] ?? __('Unknown Guest', 'twostars-platform'));
			$tour_id = (int) ($values['_twostars_booking_tour_id'] ?? 0);
			$tour = $tour_id > 0 ? get_the_title($tour_id) : __('Tour TBD', 'twostars-platform');
			$date = (string) ($values['_twostars_booking_date'] ?? '');
			$time = (string) ($values['_twostars_booking_time'] ?? '');

			return trim(sprintf('%s | %s | %s %s', $name, $tour, $date, $time));
		}

		$name = (string) ($values['_twostars_lead_full_name'] ?? '');
		$email = (string) ($values['_twostars_lead_email'] ?? '');
		$source = (string) ($values['_twostars_lead_source'] ?? 'manual');
		$identity = $name !== '' ? $name : ($email !== '' ? $email : __('Unknown Contact', 'twostars-platform'));
		$timestamp = $post_id > 0 ? wp_date('Y-m-d H:i', strtotime((string) get_post_field('post_date', $post_id))) : wp_date('Y-m-d H:i');

		return sprintf('%s | %s | %s', $identity, ucfirst(str_replace('_', ' ', $source)), $timestamp);
	}

	private function find_booking_by_lead_id(int $lead_id): int
	{
		if ($lead_id <= 0) {
			return 0;
		}

		$records = get_posts(
			[
				'post_type'      => 'booking',
				'post_status'    => ['private', 'publish', 'draft'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => '_twostars_booking_lead_id',
						'value' => $lead_id,
					],
				],
			]
		);

		return isset($records[0]) ? (int) $records[0] : 0;
	}

	private function get_form_url(string $post_type, int $post_id = 0): string
	{
		$page = match ($post_type) {
			'tour' => $post_id > 0 ? 'twostars-manager-tour-edit' : 'twostars-manager-tour-new',
			'destination' => $post_id > 0 ? 'twostars-manager-destination-edit' : 'twostars-manager-destination-new',
			'testimonial' => $post_id > 0 ? 'twostars-manager-review-edit' : 'twostars-manager-review-new',
			'booking' => $post_id > 0 ? 'twostars-manager-booking-edit' : 'twostars-manager-booking-new',
			'lead' => $post_id > 0 ? 'twostars-manager-lead-edit' : 'twostars-manager-lead-new',
			default => 'twostars-manager',
		};

		$args = ['page' => $page];

		if ($post_id > 0) {
			$args['post_id'] = $post_id;
		}

		return add_query_arg($args, admin_url('admin.php'));
	}

	private function get_review_status_url(int $post_id, string $status): string
	{
		return wp_nonce_url(
			add_query_arg(
				[
					'action'  => 'twostars_manager_update_review_status',
					'post_id' => $post_id,
					'status'  => $status,
				],
				admin_url('admin-post.php')
			),
			'twostars_manager_update_review_status'
		);
	}
}
