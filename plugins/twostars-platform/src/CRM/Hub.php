<?php
declare(strict_types=1);

namespace TwoStars\Platform\CRM;

use TwoStars\Platform\Support\Assets;
use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Visitors\VisitorProfileManager;

final class Hub implements Service
{
	public function register(): void
	{
		add_action('admin_menu', [$this, 'register_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('admin_post_twostars_crm_delete_record', [$this, 'handle_delete_record']);
	}

	public function register_menu(): void
	{
		add_submenu_page(
			'twostars-manager',
			__('CRM Hub', 'twostars-platform'),
			__('CRM Hub', 'twostars-platform'),
			'edit_posts',
			'twostars-manager-crm-hub',
			[$this, 'render_page']
		);
	}

	public function enqueue_assets(string $hook_suffix): void
	{
		if (! Assets::matches_admin_page($hook_suffix, ['twostars-manager-crm-hub'])) {
			return;
		}

		wp_enqueue_style(
			'twostars-platform-content-manager',
			Assets::plugin_url('assets/css/content-manager.css'),
			[],
			Assets::plugin_version('assets/css/content-manager.css')
		);

		wp_enqueue_style(
			'twostars-platform-admin-crm',
			Assets::plugin_url('assets/css/admin-crm.css'),
			['twostars-platform-content-manager'],
			Assets::plugin_version('assets/css/admin-crm.css')
		);

		wp_enqueue_style(
			'twostars-platform-crm-hub',
			Assets::plugin_url('assets/css/crm-hub.css'),
			['twostars-platform-admin-crm'],
			Assets::plugin_version('assets/css/crm-hub.css')
		);
	}

	public function render_page(): void
	{
		$summary          = CoreCRM::get_dashboard_summary();
		$visitor_summary  = VisitorProfileManager::get_dashboard_summary();
		$priority_queue   = CoreCRM::get_priority_queue(6);
		$fresh_stage      = ['new', 'contacted'];
		$working_stage    = ['follow_up_scheduled', 'qualified', 'quote_sent'];
		$booking_stage    = ['booking_pending', 'booking_contacted', 'booking_confirmed'];
		$closed_stage     = ['won', 'lost', 'booking_completed', 'booking_cancelled'];
		$pipeline_columns = [
			[
				'key'   => 'fresh',
				'title' => __('Fresh Enquiries', 'twostars-platform'),
				'copy'  => __('New records and first-touch follow-up.', 'twostars-platform'),
				'rows'  => CoreCRM::get_opportunities_by_stages($fresh_stage, 4),
			],
			[
				'key'   => 'working',
				'title' => __('Qualified Pipeline', 'twostars-platform'),
				'copy'  => __('Opportunities actively being shaped into trips.', 'twostars-platform'),
				'rows'  => CoreCRM::get_opportunities_by_stages($working_stage, 4),
			],
			[
				'key'   => 'booking',
				'title' => __('Booking Desk', 'twostars-platform'),
				'copy'  => __('Confirmed intent that still needs operational movement.', 'twostars-platform'),
				'rows'  => CoreCRM::get_opportunities_by_stages($booking_stage, 4),
			],
			[
				'key'   => 'closed',
				'title' => __('Closed Loop', 'twostars-platform'),
				'copy'  => __('Won, lost, completed, and cancelled outcomes.', 'twostars-platform'),
				'rows'  => CoreCRM::get_opportunities_by_stages($closed_stage, 4),
			],
		];
		$contacts         = CoreCRM::get_recent_contacts(6);
		$top_markets      = CoreCRM::get_top_markets(5);
		$metrics          = [
			[
				'label' => __('CRM Contacts', 'twostars-platform'),
				'value' => (int) ($summary['contacts'] ?? 0),
				'copy'  => __('Canonical customer records across leads, bookings, and visitor identity.', 'twostars-platform'),
			],
			[
				'label' => __('Open Opportunities', 'twostars-platform'),
				'value' => (int) ($summary['opportunities'] ?? 0),
				'copy'  => __('The synchronized commercial pipeline currently being worked.', 'twostars-platform'),
			],
			[
				'label' => __('Booking Pipeline', 'twostars-platform'),
				'value' => (int) ($summary['booking_pipeline'] ?? 0),
				'copy'  => __('Requests that moved past initial enquiry and need booking coordination.', 'twostars-platform'),
			],
			[
				'label' => __('Overdue Follow-up', 'twostars-platform'),
				'value' => (int) ($summary['overdue'] ?? 0),
				'copy'  => __('Records with due dates behind today and no closure yet.', 'twostars-platform'),
			],
			[
				'label' => __('Visitor Identity Linked', 'twostars-platform'),
				'value' => (int) ($summary['connected_visitors'] ?? 0),
				'copy'  => __('Contacts already connected to consented visitor intelligence.', 'twostars-platform'),
			],
			[
				'label' => __('AI Assisted Visitors', 'twostars-platform'),
				'value' => (int) ($visitor_summary['ai_assisted'] ?? 0),
				'copy'  => __('Journeys that already interacted with the planning assistant.', 'twostars-platform'),
			],
		];
		?>
		<div class="wrap twostars-manager twostars-crm-dashboard twostars-crm-hub">
			<?php $this->render_notice(); ?>
			<div class="twostars-manager__hero twostars-manager__hero--dashboard twostars-crm-hub__hero">
				<div class="twostars-manager__hero-main">
					<div class="twostars-manager__hero-copy">
						<p class="twostars-manager__eyebrow"><?php esc_html_e('CRM Hub', 'twostars-platform'); ?></p>
						<h1><?php esc_html_e('Connected Customer Workspace', 'twostars-platform'); ?></h1>
						<p><?php esc_html_e('This workspace is designed for real operator flow: who needs attention first, which trips are moving, where the guest came from, and what the next move should be. It sits above the legacy record editors and turns the CRM into one connected surface.', 'twostars-platform'); ?></p>
						<div class="twostars-crm-hub__hero-ribbon">
							<span class="twostars-crm-hub__hero-ribbon-pill"><?php echo esc_html(sprintf(__('Pipeline: %d active records', 'twostars-platform'), (int) ($summary['opportunities'] ?? 0))); ?></span>
							<span class="twostars-crm-hub__hero-ribbon-pill"><?php echo esc_html(sprintf(__('Visitor-linked contacts: %d', 'twostars-platform'), (int) ($summary['connected_visitors'] ?? 0))); ?></span>
							<span class="twostars-crm-hub__hero-ribbon-pill"><?php echo esc_html(sprintf(__('AI-assisted journeys: %d', 'twostars-platform'), (int) ($visitor_summary['ai_assisted'] ?? 0))); ?></span>
						</div>
					</div>
				</div>
				<div class="twostars-manager__hero-actions twostars-manager__hero-actions--stacked twostars-crm-hub__hero-actions">
					<a class="button button-primary button-hero" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-leads')); ?>"><?php esc_html_e('Open Leads', 'twostars-platform'); ?></a>
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-bookings')); ?>"><?php esc_html_e('Open Bookings', 'twostars-platform'); ?></a>
					<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-visitors')); ?>"><?php esc_html_e('Open Visitors', 'twostars-platform'); ?></a>
				</div>
				<div class="twostars-crm-hub__hero-stats">
					<div class="twostars-crm-hub__hero-stat">
						<span><?php esc_html_e('Today’s Focus', 'twostars-platform'); ?></span>
						<strong><?php echo esc_html((string) ($summary['overdue'] ?? 0)); ?></strong>
						<p><?php esc_html_e('follow-up items are currently overdue and should be resolved first.', 'twostars-platform'); ?></p>
					</div>
					<div class="twostars-crm-hub__hero-stat">
						<span><?php esc_html_e('Booking Desk', 'twostars-platform'); ?></span>
						<strong><?php echo esc_html((string) ($summary['booking_pipeline'] ?? 0)); ?></strong>
						<p><?php esc_html_e('records are already in the booking pipeline and need confirmation or ops movement.', 'twostars-platform'); ?></p>
					</div>
					<div class="twostars-crm-hub__hero-stat">
						<span><?php esc_html_e('AI Context', 'twostars-platform'); ?></span>
						<strong><?php echo esc_html((string) ($summary['connected_visitors'] ?? 0)); ?></strong>
						<p><?php esc_html_e('contacts are already linked to visitor identity and richer journey context.', 'twostars-platform'); ?></p>
					</div>
				</div>
			</div>

			<div class="twostars-crm-hub__metrics">
				<?php foreach ($metrics as $metric) : ?>
					<article class="twostars-crm-hub__metric">
						<span class="twostars-crm-hub__metric-accent" aria-hidden="true"></span>
						<span class="twostars-crm-hub__metric-label"><?php echo esc_html((string) $metric['label']); ?></span>
						<strong class="twostars-crm-hub__metric-value"><?php echo esc_html((string) $metric['value']); ?></strong>
						<p class="twostars-crm-hub__metric-copy"><?php echo esc_html((string) $metric['copy']); ?></p>
					</article>
				<?php endforeach; ?>
			</div>

			<div class="twostars-crm-hub__workspace">
				<section class="twostars-crm-hub__panel twostars-crm-hub__panel--warm">
					<div class="twostars-crm-hub__panel-head">
						<div>
							<h2><?php esc_html_e('Operator Action Center', 'twostars-platform'); ?></h2>
							<p><?php esc_html_e('This queue is ordered around urgency, due date pressure, and active pipeline movement so the team knows what to touch first.', 'twostars-platform'); ?></p>
						</div>
					</div>
					<div class="twostars-crm-hub__queue">
						<?php if ($priority_queue === []) : ?>
							<div class="twostars-crm-hub__empty"><?php esc_html_e('No synchronized opportunities are in the action queue yet.', 'twostars-platform'); ?></div>
						<?php endif; ?>
						<?php foreach ($priority_queue as $row) : ?>
							<?php $is_overdue = $this->is_overdue((string) ($row['next_due_date'] ?? '')); ?>
							<article class="twostars-crm-hub__queue-card<?php echo $is_overdue ? ' is-overdue' : ''; ?>">
								<div class="twostars-crm-hub__queue-layout">
									<div class="twostars-crm-hub__avatar" aria-hidden="true"><?php echo esc_html($this->get_initials((string) ($row['contact_name'] ?? ''))); ?></div>
									<div class="twostars-crm-hub__queue-main">
								<div class="twostars-crm-hub__queue-head">
									<div>
										<h3><?php echo esc_html((string) ($row['contact_name'] ?: __('Unknown contact', 'twostars-platform'))); ?></h3>
										<p class="twostars-crm-hub__subtle"><?php echo esc_html((string) (($row['primary_email'] ?: $row['primary_phone']) ?? '')); ?></p>
									</div>
									<div class="twostars-crm-hub__meta">
										<span class="twostars-crm-hub__pill <?php echo esc_attr($this->get_priority_class((string) ($row['priority'] ?? 'medium'))); ?>"><?php echo esc_html($this->get_priority_label((string) ($row['priority'] ?? 'medium'))); ?></span>
										<span class="twostars-crm-hub__pill <?php echo esc_attr($this->get_stage_class((string) ($row['pipeline_stage'] ?? 'new'))); ?>"><?php echo esc_html(CoreCRM::get_stage_label((string) ($row['pipeline_stage'] ?? 'new'))); ?></span>
										<?php if ($is_overdue) : ?>
											<span class="twostars-crm-hub__pill is-alert"><?php esc_html_e('Overdue', 'twostars-platform'); ?></span>
										<?php endif; ?>
									</div>
								</div>
								<p class="twostars-crm-hub__subtle"><?php echo esc_html($this->get_tour_label((int) ($row['linked_tour_id'] ?? 0))); ?><?php echo esc_html($this->format_trip_context($row)); ?></p>
								<p class="twostars-crm-hub__narrative"><?php echo esc_html($this->get_opportunity_note($row)); ?></p>
								<div class="twostars-crm-hub__meta">
									<?php if (! empty($row['country'])) : ?><span class="twostars-crm-hub__pill is-neutral"><?php echo esc_html((string) $row['country']); ?></span><?php endif; ?>
									<?php if (! empty($row['source_channel'])) : ?><span class="twostars-crm-hub__pill is-neutral"><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $row['source_channel']))); ?></span><?php endif; ?>
									<?php if (! empty($row['next_due_date'])) : ?><span class="twostars-crm-hub__pill is-neutral"><?php echo esc_html($this->get_due_label((string) $row['next_due_date'])); ?></span><?php endif; ?>
								</div>
							<div class="twostars-crm-hub__actions">
								<?php foreach ($this->get_row_actions($row) as $action) : ?>
									<a
										class="button button-small<?php echo ! empty($action['class']) ? ' ' . esc_attr((string) $action['class']) : ''; ?>"
										href="<?php echo esc_url((string) $action['url']); ?>"
										<?php if (! empty($action['confirm'])) : ?>onclick="return confirm('<?php echo esc_js((string) $action['confirm']); ?>');"<?php endif; ?>
									><?php echo esc_html((string) $action['label']); ?></a>
									<?php endforeach; ?>
								</div>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</section>

				<div class="twostars-crm-hub__side-grid">
					<section class="twostars-crm-hub__panel">
						<div class="twostars-crm-hub__panel-head">
							<div>
								<h2><?php esc_html_e('AI And Journey Signals', 'twostars-platform'); ?></h2>
								<p><?php esc_html_e('These are the fast read signals that tell the team where AI and visitor intelligence can improve follow-up quality.', 'twostars-platform'); ?></p>
							</div>
						</div>
						<ul class="twostars-crm-hub__signal-list">
							<li><div><strong><?php esc_html_e('Returning Visitors', 'twostars-platform'); ?></strong><br><span><?php esc_html_e('Profiles that came back to continue planning.', 'twostars-platform'); ?></span></div><b><?php echo esc_html((string) ($visitor_summary['returning'] ?? 0)); ?></b></li>
							<li><div><strong><?php esc_html_e('High Intent Without Booking', 'twostars-platform'); ?></strong><br><span><?php esc_html_e('Strong commercial signals without a completed booking yet.', 'twostars-platform'); ?></span></div><b><?php echo esc_html((string) ($visitor_summary['high_intent_without_booking'] ?? 0)); ?></b></li>
							<li><div><strong><?php esc_html_e('Mapped Profiles', 'twostars-platform'); ?></strong><br><span><?php esc_html_e('Visitors with location context available for market analysis.', 'twostars-platform'); ?></span></div><b><?php echo esc_html((string) ($visitor_summary['mapped_profiles'] ?? 0)); ?></b></li>
						</ul>
					</section>

					<section class="twostars-crm-hub__panel">
						<div class="twostars-crm-hub__panel-head">
							<div>
								<h2><?php esc_html_e('Top Markets', 'twostars-platform'); ?></h2>
								<p><?php esc_html_e('The strongest contact markets currently visible from the synchronized CRM contact layer.', 'twostars-platform'); ?></p>
							</div>
						</div>
						<div class="twostars-crm-hub__market-list">
							<?php if ($top_markets === []) : ?>
								<div class="twostars-crm-hub__empty"><?php esc_html_e('No country data is available on CRM contacts yet.', 'twostars-platform'); ?></div>
							<?php endif; ?>
							<?php foreach ($top_markets as $market) : ?>
								<div class="twostars-crm-hub__market-item">
									<div>
										<strong><?php echo esc_html((string) ($market['country'] ?? '')); ?></strong><br>
										<span><?php esc_html_e('CRM contacts', 'twostars-platform'); ?></span>
									</div>
									<b><?php echo esc_html((string) ($market['total'] ?? 0)); ?></b>
								</div>
							<?php endforeach; ?>
						</div>
					</section>
				</div>
			</div>

			<section class="twostars-crm-hub__panel twostars-crm-hub__pipeline">
				<div class="twostars-crm-hub__panel-head">
					<div>
						<h2><?php esc_html_e('Pipeline Board', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('A compact stage board for scanning the full flow from fresh enquiry to booking closure. This is the base surface for the deeper CRM workflow we are building next.', 'twostars-platform'); ?></p>
					</div>
				</div>
				<div class="twostars-crm-hub__pipeline-grid">
						<?php foreach ($pipeline_columns as $column) : ?>
						<section class="twostars-crm-hub__column twostars-crm-hub__column--<?php echo esc_attr((string) $column['key']); ?>">
							<div class="twostars-crm-hub__column-head">
								<div class="twostars-crm-hub__queue-head">
									<div>
										<h3><?php echo esc_html((string) $column['title']); ?></h3>
										<p><?php echo esc_html((string) $column['copy']); ?></p>
									</div>
									<span class="twostars-crm-hub__column-count"><?php echo esc_html((string) count((array) $column['rows'])); ?></span>
								</div>
							</div>
							<div class="twostars-crm-hub__column-body">
								<?php if ((array) $column['rows'] === []) : ?>
									<div class="twostars-crm-hub__empty"><?php esc_html_e('No records in this stage group yet.', 'twostars-platform'); ?></div>
								<?php endif; ?>
								<?php foreach ((array) $column['rows'] as $row) : ?>
									<?php $is_overdue = $this->is_overdue((string) ($row['next_due_date'] ?? '')); ?>
									<article class="twostars-crm-hub__board-card<?php echo $is_overdue ? ' is-overdue' : ''; ?>">
										<div class="twostars-crm-hub__board-head">
											<div class="twostars-crm-hub__avatar twostars-crm-hub__avatar--small" aria-hidden="true"><?php echo esc_html($this->get_initials((string) ($row['contact_name'] ?? ''))); ?></div>
											<div>
												<h4><?php echo esc_html((string) ($row['contact_name'] ?: __('Unknown contact', 'twostars-platform'))); ?></h4>
												<span class="twostars-crm-hub__subtle"><?php echo esc_html((string) (($row['primary_email'] ?: $row['primary_phone']) ?? '')); ?></span>
											</div>
										</div>
										<p class="twostars-crm-hub__subtle"><?php echo esc_html($this->get_tour_label((int) ($row['linked_tour_id'] ?? 0))); ?><?php echo esc_html($this->format_trip_context($row)); ?></p>
										<div class="twostars-crm-hub__meta">
											<span class="twostars-crm-hub__pill <?php echo esc_attr($this->get_stage_class((string) ($row['pipeline_stage'] ?? 'new'))); ?>"><?php echo esc_html(CoreCRM::get_stage_label((string) ($row['pipeline_stage'] ?? 'new'))); ?></span>
											<?php if (! empty($row['next_due_date'])) : ?><span class="twostars-crm-hub__pill is-neutral"><?php echo esc_html($this->get_due_label((string) $row['next_due_date'])); ?></span><?php endif; ?>
										</div>
										<footer>
											<span><?php echo esc_html($this->get_priority_label((string) ($row['priority'] ?? 'medium'))); ?></span>
											<div class="twostars-crm-hub__actions">
												<?php foreach ($this->get_row_actions($row) as $action) : ?>
													<a
														class="button button-small<?php echo ! empty($action['class']) ? ' ' . esc_attr((string) $action['class']) : ''; ?>"
														href="<?php echo esc_url((string) $action['url']); ?>"
														<?php if (! empty($action['confirm'])) : ?>onclick="return confirm('<?php echo esc_js((string) $action['confirm']); ?>');"<?php endif; ?>
													><?php echo esc_html((string) $action['label']); ?></a>
												<?php endforeach; ?>
											</div>
										</footer>
									</article>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="twostars-crm-hub__panel">
				<div class="twostars-crm-hub__panel-head">
					<div>
						<h2><?php esc_html_e('Recent CRM Contacts', 'twostars-platform'); ?></h2>
						<p><?php esc_html_e('A clean card view of the newest synchronized contacts, with quick visibility into commercial history and visitor linkage.', 'twostars-platform'); ?></p>
					</div>
				</div>
				<div class="twostars-crm-hub__contacts-grid">
					<?php if ($contacts === []) : ?>
						<div class="twostars-crm-hub__empty"><?php esc_html_e('No CRM contacts have been synchronized yet.', 'twostars-platform'); ?></div>
					<?php endif; ?>
					<?php foreach ($contacts as $contact) : ?>
						<article class="twostars-crm-hub__contact-card">
							<div class="twostars-crm-hub__contact-head">
								<div class="twostars-crm-hub__contact-identity">
									<div class="twostars-crm-hub__avatar" aria-hidden="true"><?php echo esc_html($this->get_initials((string) ($contact['full_name'] ?? ''))); ?></div>
									<div>
									<h3><?php echo esc_html((string) ($contact['full_name'] ?: __('Unknown contact', 'twostars-platform'))); ?></h3>
									<p class="twostars-crm-hub__subtle"><?php echo esc_html((string) (($contact['primary_email'] ?: $contact['primary_phone']) ?? '')); ?></p>
									</div>
								</div>
								<div class="twostars-crm-hub__contact-meta">
									<?php if (! empty($contact['country'])) : ?><span class="twostars-crm-hub__pill is-neutral"><?php echo esc_html((string) $contact['country']); ?></span><?php endif; ?>
									<span class="twostars-crm-hub__pill is-neutral"><?php echo esc_html(sprintf(__('Leads %d', 'twostars-platform'), (int) ($contact['lead_count'] ?? 0))); ?></span>
									<span class="twostars-crm-hub__pill is-neutral"><?php echo esc_html(sprintf(__('Bookings %d', 'twostars-platform'), (int) ($contact['booking_count'] ?? 0))); ?></span>
								</div>
							</div>
							<p class="twostars-crm-hub__subtle"><?php echo esc_html((int) ($contact['visitor_profile_id'] ?? 0) > 0 ? __('Visitor identity linked. This contact can be reviewed against journey intelligence and chat history.', 'twostars-platform') : __('Visitor identity not linked yet. The record still contributes to the connected CRM layer.', 'twostars-platform')); ?></p>
							<div class="twostars-crm-hub__actions">
								<?php if ((int) ($contact['visitor_profile_id'] ?? 0) > 0) : ?>
									<a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-visitor-detail&visitor_id=' . (int) $contact['visitor_profile_id'])); ?>"><?php esc_html_e('Open Visitor', 'twostars-platform'); ?></a>
								<?php endif; ?>
								<a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=twostars-manager-leads')); ?>"><?php esc_html_e('Open Leads', 'twostars-platform'); ?></a>
							</div>
							<div class="twostars-crm-hub__contact-footer">
								<span><?php echo esc_html(sprintf(__('Last active %s', 'twostars-platform'), $this->format_contact_date((string) ($contact['last_recorded_at'] ?? '')))); ?></span>
								<span><?php echo esc_html((int) ($contact['visitor_profile_id'] ?? 0) > 0 ? __('Journey context available', 'twostars-platform') : __('Commercial data only', 'twostars-platform')); ?></span>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php
	}

	public function handle_delete_record(): void
	{
		if (! current_user_can('delete_posts')) {
			wp_die(esc_html__('You do not have permission to delete CRM records.', 'twostars-platform'));
		}

		$post_id   = absint($_GET['post_id'] ?? 0);
		$post_type = sanitize_key((string) ($_GET['post_type'] ?? ''));

		if ($post_id <= 0 || ! in_array($post_type, ['lead', 'booking'], true)) {
			wp_die(esc_html__('Invalid CRM record.', 'twostars-platform'));
		}

		if (! current_user_can('delete_post', $post_id)) {
			wp_die(esc_html__('You do not have permission to delete this record.', 'twostars-platform'));
		}

		check_admin_referer('twostars_crm_delete_record_' . $post_type . '_' . $post_id);

		$redirect = wp_validate_redirect(
			(string) ($_GET['redirect_to'] ?? ''),
			admin_url('admin.php?page=twostars-manager-crm-hub')
		);

		$result = wp_trash_post($post_id);

		if ($result === false || $result === null) {
			wp_die(esc_html__('The CRM record could not be moved to trash.', 'twostars-platform'));
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'deleted'      => '1',
					'deleted_type' => $post_type,
				],
				$redirect
			)
		);
		exit;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<int, array<string, string>>
	 */
	private function get_row_actions(array $row): array
	{
		$actions = [];
		$lead_id = (int) ($row['lead_id'] ?? 0);
		$booking_id = (int) ($row['booking_id'] ?? 0);
		$visitor_profile_id = (int) ($row['visitor_profile_id'] ?? 0);

		if ($lead_id > 0) {
			$actions[] = [
				'label' => __('Lead', 'twostars-platform'),
				'url'   => admin_url('admin.php?page=twostars-manager-lead-edit&post_id=' . $lead_id),
			];
			$actions[] = [
				'label'   => __('Trash Lead', 'twostars-platform'),
				'url'     => $this->get_delete_record_url('lead', $lead_id),
				'class'   => 'twostars-crm-hub__button--danger',
				'confirm' => __('Move this lead to trash? The CRM timeline will be updated automatically.', 'twostars-platform'),
			];
		}

		if ($booking_id > 0) {
			$actions[] = [
				'label' => __('Booking', 'twostars-platform'),
				'url'   => admin_url('admin.php?page=twostars-manager-booking-edit&post_id=' . $booking_id),
			];
			$actions[] = [
				'label'   => __('Trash Booking', 'twostars-platform'),
				'url'     => $this->get_delete_record_url('booking', $booking_id),
				'class'   => 'twostars-crm-hub__button--danger',
				'confirm' => __('Move this booking to trash? The CRM timeline will be updated automatically.', 'twostars-platform'),
			];
		}

		if ($visitor_profile_id > 0) {
			$actions[] = [
				'label' => __('Visitor', 'twostars-platform'),
				'url'   => admin_url('admin.php?page=twostars-manager-visitor-detail&visitor_id=' . $visitor_profile_id),
			];
		}

		return $actions;
	}

	private function render_notice(): void
	{
		if ((string) ($_GET['deleted'] ?? '') !== '1') {
			return;
		}

		$deleted_type = sanitize_key((string) ($_GET['deleted_type'] ?? 'record'));
		$message      = $deleted_type === 'booking'
			? __('Booking moved to trash and removed from the active CRM workflow.', 'twostars-platform')
			: __('Lead moved to trash and removed from the active CRM workflow.', 'twostars-platform');
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html($message); ?></p>
		</div>
		<?php
	}

	private function get_delete_record_url(string $post_type, int $post_id): string
	{
		$url = add_query_arg(
			[
				'action'      => 'twostars_crm_delete_record',
				'post_type'   => $post_type,
				'post_id'     => $post_id,
				'redirect_to' => admin_url('admin.php?page=twostars-manager-crm-hub'),
			],
			admin_url('admin-post.php')
		);

		return wp_nonce_url($url, 'twostars_crm_delete_record_' . $post_type . '_' . $post_id);
	}

	private function get_tour_label(int $tour_id): string
	{
		if ($tour_id <= 0) {
			return __('Not linked', 'twostars-platform');
		}

		$title = get_the_title($tour_id);

		return is_string($title) && $title !== '' ? $title : __('Not linked', 'twostars-platform');
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function format_trip_context(array $row): string
	{
		$parts = [];

		if ((int) ($row['guest_count'] ?? 0) > 0) {
			$parts[] = sprintf(_n('%d guest', '%d guests', (int) $row['guest_count'], 'twostars-platform'), (int) $row['guest_count']);
		}

		if (! empty($row['travel_dates'])) {
			$parts[] = (string) $row['travel_dates'];
		}

		return $parts === [] ? '' : ' · ' . implode(' · ', $parts);
	}

	private function is_overdue(string $date): bool
	{
		return $date !== '' && $date < wp_date('Y-m-d');
	}

	private function get_priority_label(string $priority): string
	{
		$priority = sanitize_key($priority);
		$labels = [
			'low' => __('Low Priority', 'twostars-platform'),
			'medium' => __('Medium Priority', 'twostars-platform'),
			'high' => __('High Priority', 'twostars-platform'),
			'urgent' => __('Urgent', 'twostars-platform'),
		];

		return (string) ($labels[$priority] ?? __('Medium Priority', 'twostars-platform'));
	}

	private function get_priority_class(string $priority): string
	{
		$priority = sanitize_key($priority);

		return in_array($priority, ['high', 'urgent'], true) ? 'is-priority-' . $priority : 'is-neutral';
	}

	private function get_stage_class(string $stage): string
	{
		$stage = sanitize_key($stage);

		if (str_starts_with($stage, 'booking_')) {
			return 'is-stage-booking';
		}

		if (in_array($stage, ['qualified', 'quote_sent', 'won'], true)) {
			return 'is-stage-qualified';
		}

		return 'is-neutral';
	}

	private function get_initials(string $name): string
	{
		$name = trim($name);

		if ($name === '') {
			return 'TS';
		}

		$parts = preg_split('/\s+/', $name) ?: [];
		$parts = array_values(array_filter($parts));

		if ($parts === []) {
			return 'TS';
		}

		$initials = strtoupper(substr((string) $parts[0], 0, 1));

		if (count($parts) > 1) {
			$initials .= strtoupper(substr((string) $parts[count($parts) - 1], 0, 1));
		}

		return $initials;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function get_opportunity_note(array $row): string
	{
		$next_action = trim((string) ($row['next_action'] ?? ''));
		$summary = trim((string) ($row['internal_summary'] ?? ''));
		$message = trim((string) ($row['latest_message'] ?? ''));
		$source = trim((string) ($row['source_channel'] ?? ''));

		if ($next_action !== '') {
			return wp_trim_words($next_action, 18, '...');
		}

		if ($summary !== '') {
			return wp_trim_words($summary, 18, '...');
		}

		if ($message !== '') {
			return wp_trim_words($message, 18, '...');
		}

		if ($source !== '') {
			return sprintf(__('Source channel: %s. No operator summary added yet.', 'twostars-platform'), ucwords(str_replace('_', ' ', $source)));
		}

		return __('No operator note yet. Open the record and define the next commercial move.', 'twostars-platform');
	}

	private function get_due_label(string $date): string
	{
		$date = trim($date);

		if ($date === '') {
			return __('Due date not set', 'twostars-platform');
		}

		$today = wp_date('Y-m-d');

		if ($date < $today) {
			return sprintf(__('Overdue since %s', 'twostars-platform'), $date);
		}

		if ($date === $today) {
			return __('Due today', 'twostars-platform');
		}

		return sprintf(__('Due %s', 'twostars-platform'), $date);
	}

	private function format_contact_date(string $datetime): string
	{
		$datetime = trim($datetime);

		if ($datetime === '') {
			return __('recently', 'twostars-platform');
		}

		$timestamp = strtotime($datetime);

		return $timestamp ? wp_date('Y-m-d', $timestamp) : __('recently', 'twostars-platform');
	}
}
