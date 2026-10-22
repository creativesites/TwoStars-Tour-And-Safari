<?php
declare(strict_types=1);

namespace TwoStars\Platform\Admin;

use TwoStars\Platform\Support\Assets;
use TwoStars\Platform\Support\Service;

final class EditorialWorkbench implements Service
{
	private const NONCE_ACTION = 'twostars_platform_editorial_workbench';
	private const NONCE_NAME = 'twostars_platform_editorial_nonce';

	/**
	 * @return array<int, string>
	 */
	private function supported_post_types(): array
	{
		return ['post', 'page', 'tour', 'destination', 'faq', 'testimonial', 'team_member'];
	}

	public function register(): void
	{
		add_action('init', [$this, 'register_supports']);
		add_action('init', [$this, 'register_meta']);
		add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
		add_action('save_post', [$this, 'save_meta'], 10, 2);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
		add_filter('manage_post_posts_columns', [$this, 'register_columns']);
		add_filter('manage_page_posts_columns', [$this, 'register_columns']);
		add_filter('manage_tour_posts_columns', [$this, 'register_columns']);
		add_filter('manage_destination_posts_columns', [$this, 'register_columns']);
		add_filter('manage_faq_posts_columns', [$this, 'register_columns']);
		add_filter('manage_testimonial_posts_columns', [$this, 'register_columns']);
		add_filter('manage_team_member_posts_columns', [$this, 'register_columns']);
		add_action('manage_post_posts_custom_column', [$this, 'render_column'], 10, 2);
		add_action('manage_page_posts_custom_column', [$this, 'render_column'], 10, 2);
		add_action('manage_tour_posts_custom_column', [$this, 'render_column'], 10, 2);
		add_action('manage_destination_posts_custom_column', [$this, 'render_column'], 10, 2);
		add_action('manage_faq_posts_custom_column', [$this, 'render_column'], 10, 2);
		add_action('manage_testimonial_posts_custom_column', [$this, 'render_column'], 10, 2);
		add_action('manage_team_member_posts_custom_column', [$this, 'render_column'], 10, 2);
	}

	public function register_supports(): void
	{
		add_post_type_support('page', 'excerpt');
		add_post_type_support('faq', 'excerpt');
	}

	public function register_meta(): void
	{
		foreach ($this->supported_post_types() as $post_type) {
			foreach ($this->get_field_definitions() as $meta_key => $definition) {
				register_post_meta(
					$post_type,
					$meta_key,
					[
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => fn($value) => $this->sanitize_value($definition['type'], $value),
						'auth_callback'     => static fn() => current_user_can('edit_posts'),
					]
				);
			}
		}
	}

	public function register_meta_boxes(): void
	{
		foreach ($this->supported_post_types() as $post_type) {
			add_meta_box(
				'twostars-editorial-workbench',
				__('AI, CRM and SEO Brief', 'twostars-platform'),
				[$this, 'render_brief_meta_box'],
				$post_type,
				'normal',
				'high'
			);

			add_meta_box(
				'twostars-editorial-guidance',
				__('Publishing Guidance', 'twostars-platform'),
				[$this, 'render_guidance_meta_box'],
				$post_type,
				'side',
				'high'
			);
		}
	}

	public function enqueue_assets(string $hook_suffix): void
	{
		if (! in_array($hook_suffix, ['post.php', 'post-new.php'], true)) {
			return;
		}

		$screen = function_exists('get_current_screen') ? get_current_screen() : null;

		if (! $screen || ! in_array((string) $screen->post_type, $this->supported_post_types(), true)) {
			return;
		}

		wp_enqueue_style(
			'twostars-platform-editorial',
			Assets::plugin_url('assets/css/editorial-workbench.css'),
			[],
			Assets::plugin_version('assets/css/editorial-workbench.css')
		);
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_brief_meta_box($post): void
	{
		wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
		?>
		<div class="twostars-editorial-workbench">
			<div class="twostars-editorial-workbench__intro">
				<h3><?php esc_html_e('Content That Converts, Ranks, and Grounds AI', 'twostars-platform'); ?></h3>
				<p><?php esc_html_e('Use this panel to make each page clearer for travelers, more structured for CRM follow-up, and easier for Yoast and the site AI context layer to understand.', 'twostars-platform'); ?></p>
			</div>
			<div class="twostars-editorial-workbench__grid">
				<?php foreach ($this->get_field_definitions() as $meta_key => $definition) : ?>
					<?php $this->render_field((int) $post->ID, $meta_key, $definition); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_guidance_meta_box($post): void
	{
		$yoast_available = (bool) apply_filters('twostars_platform/yoast_available', false);
		$seo_focus_topic = $this->get_effective_focus_topic((int) $post->ID, (string) $post->post_type);
		$meta_description = (string) get_post_meta((int) $post->ID, '_twostars_editorial_meta_description', true);
		$crm_intent = (string) get_post_meta((int) $post->ID, '_twostars_editorial_crm_intent', true);
		?>
		<div class="twostars-editorial-guidance">
			<p><strong><?php esc_html_e('Yoast sync', 'twostars-platform'); ?></strong><br><?php echo esc_html($yoast_available ? __('Active. Focus topic and meta description are pushed into Yoast when saved.', 'twostars-platform') : __('Yoast not detected. These fields are still stored for AI and future SEO use.', 'twostars-platform')); ?></p>
			<p><strong><?php esc_html_e('Focus topic', 'twostars-platform'); ?></strong><br><?php echo esc_html($seo_focus_topic !== '' ? $seo_focus_topic : __('Not set yet', 'twostars-platform')); ?></p>
			<p><strong><?php esc_html_e('Meta description', 'twostars-platform'); ?></strong><br><?php echo esc_html($meta_description !== '' ? wp_trim_words($meta_description, 18, '...') : __('Not set yet', 'twostars-platform')); ?></p>
			<p><strong><?php esc_html_e('CRM intent', 'twostars-platform'); ?></strong><br><?php echo esc_html($crm_intent !== '' ? $this->crm_intent_label($crm_intent) : __('Not set yet', 'twostars-platform')); ?></p>
			<hr>
			<ul class="twostars-editorial-guidance__list">
				<li><?php esc_html_e('Lead with the traveler outcome, not only the destination name.', 'twostars-platform'); ?></li>
				<li><?php esc_html_e('Keep the AI summary factual and concise. It should explain the page in one clean paragraph.', 'twostars-platform'); ?></li>
				<li><?php esc_html_e('Use the primary CTA to clarify the next conversion step for CRM follow-up.', 'twostars-platform'); ?></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	public function save_meta(int $post_id, $post): void
	{
		if (! $post instanceof \WP_Post || ! in_array((string) $post->post_type, $this->supported_post_types(), true)) {
			return;
		}

		if (
			! isset($_POST[self::NONCE_NAME]) ||
			! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
		) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (! current_user_can('edit_post', $post_id)) {
			return;
		}

		foreach ($this->get_field_definitions() as $meta_key => $definition) {
			$post_key = ltrim($meta_key, '_');
			$value = isset($_POST[$post_key]) ? wp_unslash($_POST[$post_key]) : '';
			$value = $this->sanitize_value($definition['type'], $value);

			if ($value === '') {
				delete_post_meta($post_id, $meta_key);
				continue;
			}

			update_post_meta($post_id, $meta_key, $value);
		}

		$this->sync_yoast_meta($post_id, (string) $post->post_type);
	}

	/**
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function register_columns(array $columns): array
	{
		$columns['twostars_focus'] = __('Focus Topic', 'twostars-platform');
		$columns['twostars_crm']   = __('CRM Intent', 'twostars-platform');

		return $columns;
	}

	public function render_column(string $column, int $post_id): void
	{
		if ($column === 'twostars_focus') {
			$post_type = (string) get_post_type($post_id);
			$value = $this->get_effective_focus_topic($post_id, $post_type);
			echo $value !== '' ? esc_html($value) : '&mdash;';
			return;
		}

		if ($column === 'twostars_crm') {
			$value = (string) get_post_meta($post_id, '_twostars_editorial_crm_intent', true);
			echo $value !== '' ? esc_html($this->crm_intent_label($value)) : '&mdash;';
		}
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	private function get_field_definitions(): array
	{
		return [
			'_twostars_editorial_seo_focus_topic' => [
				'label' => __('SEO Focus Topic', 'twostars-platform'),
				'type'  => 'text',
				'help'  => __('Use a concise topic or keyphrase that should anchor the content and Yoast focus field.', 'twostars-platform'),
			],
			'_twostars_editorial_meta_description' => [
				'label' => __('Meta Description', 'twostars-platform'),
				'type'  => 'textarea',
				'help'  => __('Write the 1-2 sentence search description you want Yoast to use.', 'twostars-platform'),
			],
			'_twostars_editorial_ai_summary' => [
				'label' => __('AI Summary', 'twostars-platform'),
				'type'  => 'textarea',
				'help'  => __('Short factual summary for AI grounding, internal search, and future automation.', 'twostars-platform'),
			],
			'_twostars_editorial_target_audience' => [
				'label' => __('Target Audience', 'twostars-platform'),
				'type'  => 'text',
				'help'  => __('Describe the traveler, buyer, or reader this content should resonate with.', 'twostars-platform'),
			],
			'_twostars_editorial_primary_cta' => [
				'label' => __('Primary CTA', 'twostars-platform'),
				'type'  => 'text',
				'help'  => __('Define the main next step: book a call, request itinerary, contact team, compare tours, and so on.', 'twostars-platform'),
			],
			'_twostars_editorial_crm_intent' => [
				'label' => __('CRM Intent', 'twostars-platform'),
				'type'  => 'select',
				'help'  => __('This helps segment content by where the visitor sits in the journey.', 'twostars-platform'),
			],
			'_twostars_editorial_internal_notes' => [
				'label' => __('Internal Notes', 'twostars-platform'),
				'type'  => 'textarea',
				'help'  => __('Internal context for operators, content editors, or future automation. Not shown on the frontend.', 'twostars-platform'),
			],
		];
	}

	/**
	 * @param int $post_id
	 * @param array<string, string> $definition
	 */
	private function render_field(int $post_id, string $meta_key, array $definition): void
	{
		$value = (string) get_post_meta($post_id, $meta_key, true);
		$field_name = ltrim($meta_key, '_');
		$field_id = 'twostars-field-' . $field_name;
		$type = $definition['type'] ?? 'text';
		?>
		<div class="twostars-editorial-field twostars-editorial-field--<?php echo esc_attr($type); ?>">
			<label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html((string) ($definition['label'] ?? '')); ?></label>
			<?php if ($type === 'textarea') : ?>
				<textarea id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_name); ?>" rows="4"><?php echo esc_textarea($value); ?></textarea>
			<?php elseif ($type === 'select') : ?>
				<select id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_name); ?>">
					<option value=""><?php esc_html_e('Select intent', 'twostars-platform'); ?></option>
					<?php foreach ($this->crm_intent_choices() as $choice => $label) : ?>
						<option value="<?php echo esc_attr($choice); ?>" <?php selected($value, $choice); ?>><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input id="<?php echo esc_attr($field_id); ?>" type="text" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($value); ?>">
			<?php endif; ?>
			<?php if (! empty($definition['help'])) : ?>
				<p class="description"><?php echo esc_html((string) $definition['help']); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private function sanitize_value(string $type, mixed $value): string
	{
		if ($type === 'textarea') {
			return sanitize_textarea_field((string) $value);
		}

		if ($type === 'select') {
			$value = sanitize_key((string) $value);
			return array_key_exists($value, $this->crm_intent_choices()) ? $value : '';
		}

		return sanitize_text_field((string) $value);
	}

	/**
	 * @return array<string, string>
	 */
	private function crm_intent_choices(): array
	{
		return [
			'discover'      => __('Discover', 'twostars-platform'),
			'compare'       => __('Compare', 'twostars-platform'),
			'ready_to_book' => __('Ready to Book', 'twostars-platform'),
			'trust'         => __('Trust Building', 'twostars-platform'),
			'support'       => __('Support / Questions', 'twostars-platform'),
		];
	}

	private function crm_intent_label(string $value): string
	{
		return (string) ($this->crm_intent_choices()[$value] ?? $value);
	}

	private function get_effective_focus_topic(int $post_id, string $post_type): string
	{
		if ($post_type === 'tour') {
			$tour_value = (string) get_post_meta($post_id, '_twostars_tour_seo_focus_topic', true);

			if ($tour_value !== '') {
				return $tour_value;
			}
		}

		return (string) get_post_meta($post_id, '_twostars_editorial_seo_focus_topic', true);
	}

	private function sync_yoast_meta(int $post_id, string $post_type): void
	{
		$focus_topic = $this->get_effective_focus_topic($post_id, $post_type);
		$meta_description = (string) get_post_meta($post_id, '_twostars_editorial_meta_description', true);

		if ($focus_topic !== '') {
			update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus_topic);
		}

		if ($meta_description !== '') {
			update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
		}
	}
}
