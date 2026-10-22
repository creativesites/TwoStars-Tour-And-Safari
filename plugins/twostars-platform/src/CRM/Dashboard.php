<?php
declare(strict_types=1);

namespace TwoStars\Platform\CRM;

use TwoStars\Platform\Support\Assets;
use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Visitors\VisitorProfileManager;
use WP_Query;

final class Dashboard implements Service
{
	public function register(): void
	{
		add_action('admin_menu', [$this, 'register_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('restrict_manage_posts', [$this, 'render_filters']);
		add_action('pre_get_posts', [$this, 'apply_filters']);
		add_filter('views_edit-lead', [$this, 'add_overdue_view']);
	}

	public function register_menu(): void
	{
		add_submenu_page(
			'edit.php?post_type=lead',
			__('CRM Dashboard', 'twostars-platform'),
			__('CRM Dashboard', 'twostars-platform'),
			'edit_posts',
			'twostars-crm-dashboard',
			[$this, 'render_page']
		);
	}

	public function enqueue_assets(string $hook_suffix): void
	{
		if (! in_array($hook_suffix, ['lead_page_twostars-crm-dashboard', 'edit.php', 'post.php', 'post-new.php'], true)) {
			return;
		}

		$screen = get_current_screen();

		if (! $screen || ! in_array($screen->post_type, ['lead'], true)) {
			return;
		}

		wp_enqueue_style(
			'twostars-platform-admin-crm',
			Assets::plugin_url('assets/css/admin-crm.css'),
			[],
			Assets::plugin_version('assets/css/admin-crm.css')
		);
	}

	public function render_filters(): void
	{
		global $typenow;

		if ($typenow !== 'lead') {
			return;
		}

		$status_filter   = sanitize_text_field((string) ($_GET['twostars_lead_status'] ?? ''));
		$source_filter   = sanitize_text_field((string) ($_GET['twostars_lead_source'] ?? ''));
		$followup_filter = sanitize_text_field((string) ($_GET['twostars_lead_followup'] ?? ''));

		$this->render_select('twostars_lead_status', __('All statuses', 'twostars-platform'), LeadManager::get_status_choices(), $status_filter);
		$this->render_select('twostars_lead_source', __('All sources', 'twostars-platform'), LeadManager::get_source_choices(), $source_filter);
		$this->render_select(
			'twostars_lead_followup',
			__('All follow-ups', 'twostars-platform'),
			[
				'overdue' => __('Overdue', 'twostars-platform'),
				'today'   => __('Due today', 'twostars-platform'),
				'future'  => __('Upcoming', 'twostars-platform'),
			],
			$followup_filter
		);
	}

	/**
	 * @param \WP_Query $query
	 */
	public function apply_filters($query): void
	{
		if (! is_admin() || ! $query->is_main_query()) {
			return;
		}

		if ($query->get('post_type') !== 'lead') {
			return;
		}

		$status_filter   = sanitize_text_field((string) ($_GET['twostars_lead_status'] ?? ''));
		$source_filter   = sanitize_text_field((string) ($_GET['twostars_lead_source'] ?? ''));
		$followup_filter = sanitize_text_field((string) ($_GET['twostars_lead_followup'] ?? ''));
		$meta_query      = (array) $query->get('meta_query', []);

		if ($status_filter !== '') {
			$meta_query[] = [
				'key'   => '_twostars_lead_status',
				'value' => $status_filter,
			];
		}

		if ($source_filter !== '') {
			$meta_query[] = [
				'key'   => '_twostars_lead_source',
				'value' => $source_filter,
			];
		}

		$today = wp_date('Y-m-d');

		if ($followup_filter === 'overdue') {
			$meta_query[] = $this->get_overdue_meta_query();
		}

		if ($followup_filter === 'today') {
			$meta_query[] = [
				'key'     => '_twostars_lead_follow_up_date',
				'value'   => $today,
				'compare' => '=',
				'type'    => 'DATE',
			];
		}

		if ($followup_filter === 'future') {
			$meta_query[] = [
				'key'     => '_twostars_lead_follow_up_date',
				'value'   => $today,
				'compare' => '>',
				'type'    => 'DATE',
			];
		}

		if (! empty($meta_query)) {
			$query->set('meta_query', $meta_query);
		}
	}

	/**
	 * @param array<string, string> $views
	 * @return array<string, string>
	 */
	public function add_overdue_view(array $views): array
	{
		$count        = $this->count_overdue_leads();
		$current      = sanitize_text_field((string) ($_GET['twostars_lead_followup'] ?? '')) === 'overdue';
		$url          = add_query_arg(
			[
				'post_type'               => 'lead',
				'twostars_lead_followup'  => 'overdue',
			],
			admin_url('edit.php')
		);
		$class        = $current ? ' class="current"' : '';
		$views['twostars_overdue'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url($url),
			$class,
			esc_html__('Overdue Follow-ups', 'twostars-platform'),
			$count
		);

		return $views;
	}

	public function render_page(): void
	{
		$metrics = [
			__('Total Leads', 'twostars-platform')      => $this->count_leads(),
			__('New Leads', 'twostars-platform')        => $this->count_leads([['key' => '_twostars_lead_status', 'value' => 'new']]),
			__('Qualified', 'twostars-platform')        => $this->count_leads([['key' => '_twostars_lead_status', 'value' => 'qualified']]),
			__('Overdue', 'twostars-platform')          => $this->count_overdue_leads(),
			__('Won', 'twostars-platform')              => $this->count_leads([['key' => '_twostars_lead_status', 'value' => 'won']]),
		];
		$visitor_summary = VisitorProfileManager::get_dashboard_summary();

		$overdue_leads = $this->get_leads(
			[
				'meta_query' => [$this->get_overdue_meta_query()],
				'posts_per_page' => 8,
			]
		);

		$recent_leads = $this->get_leads(['posts_per_page' => 8]);
		?>
		<div class="wrap twostars-crm-dashboard">
			<h1><?php esc_html_e('CRM Dashboard', 'twostars-platform'); ?></h1>
			<div class="twostars-crm-cards">
				<?php foreach ($metrics as $label => $value) : ?>
					<div class="twostars-crm-card">
						<span><?php echo esc_html($label); ?></span>
						<strong><?php echo esc_html((string) $value); ?></strong>
					</div>
				<?php endforeach; ?>
				<div class="twostars-crm-card"><span><?php esc_html_e('Visitor Profiles', 'twostars-platform'); ?></span><strong><?php echo esc_html((string) ($visitor_summary['total'] ?? 0)); ?></strong></div>
				<div class="twostars-crm-card"><span><?php esc_html_e('Returning Visitors', 'twostars-platform'); ?></span><strong><?php echo esc_html((string) ($visitor_summary['returning'] ?? 0)); ?></strong></div>
				<div class="twostars-crm-card"><span><?php esc_html_e('AI Assisted Visitors', 'twostars-platform'); ?></span><strong><?php echo esc_html((string) ($visitor_summary['ai_assisted'] ?? 0)); ?></strong></div>
				<div class="twostars-crm-card"><span><?php esc_html_e('High Intent Without Booking', 'twostars-platform'); ?></span><strong><?php echo esc_html((string) ($visitor_summary['high_intent_without_booking'] ?? 0)); ?></strong></div>
			</div>

			<div class="twostars-crm-panels">
				<section class="twostars-crm-panel">
					<h2><?php esc_html_e('Overdue Follow-ups', 'twostars-platform'); ?></h2>
					<?php $this->render_lead_list($overdue_leads, true); ?>
				</section>

				<section class="twostars-crm-panel">
					<h2><?php esc_html_e('Recent Leads', 'twostars-platform'); ?></h2>
					<?php $this->render_lead_list($recent_leads, false); ?>
				</section>
			</div>

			<div class="twostars-crm-panels">
				<section class="twostars-crm-panel">
					<h2><?php esc_html_e('Visitor Markets', 'twostars-platform'); ?></h2>
					<ul class="twostars-crm-list">
						<?php foreach ((array) ($visitor_summary['top_countries'] ?? []) as $country) : ?>
							<li>
								<strong><?php echo esc_html((string) ($country['country_name'] ?? '')); ?></strong>
								<span><?php echo esc_html((string) ($country['country_code'] ?? '')); ?></span>
								<b><?php echo esc_html((string) ($country['total'] ?? 0)); ?></b>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>

				<section class="twostars-crm-panel">
					<h2><?php esc_html_e('Visitor Intelligence', 'twostars-platform'); ?></h2>
					<p><?php esc_html_e('Use the Visitors manager to see returning profiles, location clusters, AI-assisted journeys, and which high-intent visitors still need follow-up.', 'twostars-platform'); ?></p>
					<p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-visitors')); ?>"><?php esc_html_e('Open Visitors', 'twostars-platform'); ?></a></p>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array<string, string> $options
	 */
	private function render_select(string $name, string $default_label, array $options, string $selected_value): void
	{
		?>
		<select name="<?php echo esc_attr($name); ?>">
			<option value=""><?php echo esc_html($default_label); ?></option>
			<?php foreach ($options as $value => $label) : ?>
				<option value="<?php echo esc_attr($value); ?>" <?php selected($selected_value, $value); ?>>
					<?php echo esc_html($label); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * @param array<string, mixed> $args
	 * @return \WP_Post[]
	 */
	private function get_leads(array $args = []): array
	{
		$query = new WP_Query(
			array_merge(
				[
					'post_type'      => 'lead',
					'post_status'    => 'private',
					'posts_per_page' => 10,
					'orderby'        => 'date',
					'order'          => 'DESC',
				],
				$args
			)
		);

		return $query->posts;
	}

	/**
	 * @param array<int, array<string, mixed>> $meta_query
	 */
	private function count_leads(array $meta_query = []): int
	{
		$query = new WP_Query(
			[
				'post_type'      => 'lead',
				'post_status'    => 'private',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => $meta_query,
			]
		);

		return (int) $query->found_posts;
	}

	private function count_overdue_leads(): int
	{
		return $this->count_leads(
			[$this->get_overdue_meta_query()]
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_overdue_meta_query(): array
	{
		return [
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
		];
	}

	/**
	 * @param \WP_Post[] $leads
	 */
	private function render_lead_list(array $leads, bool $show_followup): void
	{
		if (empty($leads)) {
			echo '<p>' . esc_html__('Nothing to show.', 'twostars-platform') . '</p>';
			return;
		}
		?>
		<ul class="twostars-crm-list">
			<?php foreach ($leads as $lead) : ?>
				<?php
				$status   = (string) get_post_meta($lead->ID, '_twostars_lead_status', true);
				$source   = (string) get_post_meta($lead->ID, '_twostars_lead_source', true);
				$email    = (string) get_post_meta($lead->ID, '_twostars_lead_email', true);
				$followup = (string) get_post_meta($lead->ID, '_twostars_lead_follow_up_date', true);
				?>
				<li>
					<a href="<?php echo esc_url(get_edit_post_link($lead->ID) ?: ''); ?>">
						<strong><?php echo esc_html($lead->post_title); ?></strong>
					</a>
					<span><?php echo esc_html($email); ?></span>
					<span><?php echo esc_html((string) (LeadManager::get_status_choices()[$status] ?? $status)); ?></span>
					<span><?php echo esc_html((string) (LeadManager::get_source_choices()[$source] ?? $source)); ?></span>
					<?php if ($show_followup) : ?>
						<span><?php echo esc_html($followup !== '' ? $followup : ''); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}
