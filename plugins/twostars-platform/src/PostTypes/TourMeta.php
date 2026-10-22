<?php
declare(strict_types=1);

namespace TwoStars\Platform\PostTypes;

use TwoStars\Platform\Support\Service;

final class TourMeta implements Service
{
	private const NONCE_ACTION = 'twostars_platform_save_tour_meta';
	private const NONCE_NAME = 'twostars_platform_tour_meta_nonce';

	public function register(): void
	{
		add_action('init', [$this, 'register_meta']);
		add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
		add_action('save_post_tour', [$this, 'save_meta']);
		add_filter('manage_tour_posts_columns', [$this, 'register_columns']);
		add_action('manage_tour_posts_custom_column', [$this, 'render_column'], 10, 2);
	}

	public function register_meta(): void
	{
		foreach (self::get_manager_field_definitions() as $meta_key => $definition) {
			$meta_type = (string) ($definition['meta_type'] ?? 'string');
			$show_in_rest = true;

			if ($meta_type === 'array') {
				$show_in_rest = [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type' => 'integer',
						],
					],
				];
			}

			register_post_meta(
				'tour',
				$meta_key,
				[
					'type'              => $meta_type,
					'single'            => true,
					'show_in_rest'      => $show_in_rest,
					'sanitize_callback' => fn($value) => $this->sanitize_value($meta_key, $value),
					'auth_callback'     => static fn() => current_user_can('edit_posts'),
				]
			);
		}
	}

	public function register_meta_boxes(): void
	{
		add_meta_box(
			'twostars-tour-details',
			__('Tour Details', 'twostars-platform'),
			[$this, 'render_details_meta_box'],
			'tour',
			'normal',
			'high'
		);

		add_meta_box(
			'twostars-tour-booking',
			__('Booking Mapping', 'twostars-platform'),
			[$this, 'render_booking_meta_box'],
			'tour',
			'side',
			'high'
		);
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_details_meta_box($post): void
	{
		wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
		?>
		<div class="twostars-admin-fields">
			<?php foreach ($this->get_details_fields() as $meta_key => $definition) : ?>
				<?php $this->render_field($post->ID, $meta_key, $definition); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_booking_meta_box($post): void
	{
		wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
		?>
		<div class="twostars-admin-fields">
			<p>
				<?php esc_html_e('This tour now routes into the local Two Stars booking page. Use these fields only to control the booking label and supporting note shown around the booking CTA.', 'twostars-platform'); ?>
			</p>

			<?php $definitions = self::get_manager_field_definitions(); ?>
			<?php $this->render_field($post->ID, '_twostars_tour_calendly_event_url', $definitions['_twostars_tour_calendly_event_url']); ?>
			<?php $this->render_field($post->ID, '_twostars_tour_booking_cta_label', $definitions['_twostars_tour_booking_cta_label']); ?>
			<?php $this->render_field($post->ID, '_twostars_tour_booking_notes', $definitions['_twostars_tour_booking_notes']); ?>
		</div>
		<?php
	}

	/**
	 * @param int $post_id
	 */
	public function save_meta(int $post_id): void
	{
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

		foreach (array_keys(self::get_manager_field_definitions()) as $meta_key) {
			$post_key = ltrim($meta_key, '_');
			$value    = isset($_POST[$post_key]) ? wp_unslash($_POST[$post_key]) : null;
			$value    = $this->sanitize_value($meta_key, $value);

			if ($value === '' || $value === null) {
				delete_post_meta($post_id, $meta_key);
				continue;
			}

			update_post_meta($post_id, $meta_key, $value);
		}
	}

	/**
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function register_columns(array $columns): array
	{
		$columns['tour_availability'] = __('Availability', 'twostars-platform');
		$columns['tour_booking']      = __('Calendly', 'twostars-platform');

		return $columns;
	}

	public function render_column(string $column, int $post_id): void
	{
		switch ($column) {
			case 'tour_availability':
				$value = (string) get_post_meta($post_id, '_twostars_tour_availability_status', true);
				echo $value !== '' ? esc_html($this->get_select_label('_twostars_tour_availability_status', $value)) : '&mdash;';
				break;

			case 'tour_booking':
				$event_url = (string) get_post_meta($post_id, '_twostars_tour_calendly_event_url', true);
				echo $event_url !== '' ? esc_html__('Custom URL', 'twostars-platform') : esc_html__('Default', 'twostars-platform');
				break;
		}
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_manager_field_definitions(): array
	{
		return [
			'_twostars_tour_duration' => [
				'label' => __('Duration', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_tour_price_from' => [
				'label'     => __('Price From (Optional)', 'twostars-platform'),
				'type'      => 'number',
				'step'      => '0.01',
				'meta_type' => 'number',
			],
			'_twostars_tour_group_size' => [
				'label' => __('Group Size', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_tour_pickup_location' => [
				'label' => __('Pickup Location', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_tour_gallery_images' => [
				'label'     => __('Gallery Images', 'twostars-platform'),
				'type'      => 'gallery',
				'meta_type' => 'array',
			],
			'_twostars_tour_linked_destinations' => [
				'label'     => __('Linked Destinations', 'twostars-platform'),
				'type'      => 'multiselect',
				'meta_type' => 'array',
				'choices'   => self::get_destination_choices(),
			],
			'_twostars_tour_availability_status' => [
				'label'   => __('Availability', 'twostars-platform'),
				'type'    => 'select',
				'choices' => [
					'available'  => __('Available', 'twostars-platform'),
					'limited'    => __('Limited', 'twostars-platform'),
					'on_request' => __('On Request', 'twostars-platform'),
					'sold_out'   => __('Sold Out', 'twostars-platform'),
				],
			],
			'_twostars_tour_difficulty' => [
				'label'   => __('Activity Level', 'twostars-platform'),
				'type'    => 'select',
				'choices' => [
					'easy'     => __('Easy', 'twostars-platform'),
					'moderate' => __('Moderate', 'twostars-platform'),
					'active'   => __('Active', 'twostars-platform'),
				],
			],
			'_twostars_tour_best_season' => [
				'label' => __('Best Season', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_tour_includes' => [
				'label' => __('Includes', 'twostars-platform'),
				'type'  => 'textarea',
			],
			'_twostars_tour_excludes' => [
				'label' => __('Excludes', 'twostars-platform'),
				'type'  => 'textarea',
			],
			'_twostars_tour_seo_focus_topic' => [
				'label' => __('SEO Focus Topic', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_tour_calendly_event_url' => [
				'label' => __('Legacy External Event URL', 'twostars-platform'),
				'type'  => 'url',
			],
			'_twostars_tour_booking_cta_label' => [
				'label' => __('Booking CTA Label', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_tour_booking_notes' => [
				'label' => __('Booking Notes', 'twostars-platform'),
				'type'  => 'textarea',
			],
		];
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function get_details_fields(): array
	{
		return array_intersect_key(
			self::get_manager_field_definitions(),
			array_flip(
				[
					'_twostars_tour_duration',
					'_twostars_tour_price_from',
					'_twostars_tour_group_size',
					'_twostars_tour_pickup_location',
					'_twostars_tour_gallery_images',
					'_twostars_tour_linked_destinations',
					'_twostars_tour_availability_status',
					'_twostars_tour_difficulty',
					'_twostars_tour_best_season',
					'_twostars_tour_includes',
					'_twostars_tour_excludes',
					'_twostars_tour_seo_focus_topic',
				]
			)
		);
	}

	/**
	 * @param int $post_id
	 * @param array<string, mixed> $definition
	 */
	private function render_field(int $post_id, string $meta_key, array $definition): void
	{
		$value    = get_post_meta($post_id, $meta_key, true);
		$field_id = ltrim($meta_key, '_');
		$type     = (string) ($definition['type'] ?? 'text');
		?>
		<p>
			<label for="<?php echo esc_attr($field_id); ?>"><strong><?php echo esc_html((string) $definition['label']); ?></strong></label><br>
			<?php if ($type === 'textarea') : ?>
				<textarea
					id="<?php echo esc_attr($field_id); ?>"
					name="<?php echo esc_attr($field_id); ?>"
					rows="4"
					style="width: 100%;"
				><?php echo esc_textarea((string) $value); ?></textarea>
			<?php elseif ($type === 'select') : ?>
				<select
					id="<?php echo esc_attr($field_id); ?>"
					name="<?php echo esc_attr($field_id); ?>"
					style="width: 100%;"
				>
					<option value=""><?php esc_html_e('Select an option', 'twostars-platform'); ?></option>
					<?php foreach ((array) ($definition['choices'] ?? []) as $option_value => $option_label) : ?>
						<option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected((string) $value, (string) $option_value); ?>>
							<?php echo esc_html((string) $option_label); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php elseif ($type === 'multiselect') : ?>
				<?php $selected_values = is_array($value) ? array_map('intval', $value) : []; ?>
				<select
					id="<?php echo esc_attr($field_id); ?>"
					name="<?php echo esc_attr($field_id); ?>[]"
					style="width: 100%; min-height: 140px;"
					multiple
				>
					<?php foreach ((array) ($definition['choices'] ?? []) as $option_value => $option_label) : ?>
						<option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected(in_array((int) $option_value, $selected_values, true), true); ?>>
							<?php echo esc_html((string) $option_label); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php elseif ($type === 'gallery') : ?>
				<?php $this->render_gallery_field($field_id, is_array($value) ? $value : []); ?>
			<?php else : ?>
				<input
					id="<?php echo esc_attr($field_id); ?>"
					name="<?php echo esc_attr($field_id); ?>"
					type="<?php echo esc_attr($type); ?>"
					value="<?php echo esc_attr((string) $value); ?>"
					step="<?php echo esc_attr((string) ($definition['step'] ?? '')); ?>"
					style="width: 100%;"
				>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * @param mixed $value
	 * @return int|float|string|null
	 */
	private function sanitize_value(string $meta_key, $value)
	{
		if ($value === null) {
			return null;
		}

		$definition = self::get_manager_field_definitions()[$meta_key] ?? [];
		$type       = (string) ($definition['type'] ?? 'text');

		if (is_string($value)) {
			$value = trim($value);
		}

		if ($value === '') {
			return '';
		}

		if ($type === 'textarea') {
			return sanitize_textarea_field((string) $value);
		}

		if ($type === 'url') {
			return esc_url_raw((string) $value);
		}

		if ($type === 'multiselect') {
			$values = is_array($value) ? $value : [$value];
			$values = array_map('absint', $values);
			$values = array_values(array_filter($values, static fn(int $item): bool => $item > 0));

			return array_values(array_unique($values));
		}

		if ($type === 'gallery') {
			$values = is_array($value) ? $value : explode(',', (string) $value);
			$values = array_values(array_filter(array_map('absint', $values), static fn(int $item): bool => $item > 0));

			return array_values(array_unique($values));
		}

		if ($type === 'number') {
			if (($definition['meta_type'] ?? '') === 'integer') {
				return absint($value);
			}

			return is_numeric($value) ? (float) $value : '';
		}

		if ($type === 'select') {
			$choices = array_keys((array) ($definition['choices'] ?? []));
			$value   = sanitize_text_field((string) $value);

			return in_array($value, $choices, true) ? $value : '';
		}

		return sanitize_text_field((string) $value);
	}

	private function get_select_label(string $meta_key, string $value): string
	{
		$choices = (array) ((self::get_manager_field_definitions()[$meta_key] ?? [])['choices'] ?? []);

		return (string) ($choices[$value] ?? $value);
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_destination_choices(): array
	{
		$posts = get_posts(
			[
				'post_type'      => 'destination',
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'posts_per_page' => 100,
			]
		);

		$choices = [];

		foreach ($posts as $post) {
			$choices[(string) $post->ID] = $post->post_title;
		}

		return $choices;
	}

	/**
	 * @param array<int, mixed> $image_ids
	 */
	private function render_gallery_field(string $field_id, array $image_ids): void
	{
		$image_ids = array_values(array_filter(array_map('absint', $image_ids)));
		?>
		<div class="twostars-media-field" data-twostars-media-field data-target-input="<?php echo esc_attr($field_id); ?>" data-multiple="1">
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
				<button type="button" class="button button-secondary" data-twostars-media-open><?php esc_html_e('Choose Images', 'twostars-platform'); ?></button>
				<button type="button" class="button-link-delete" data-twostars-media-clear><?php esc_html_e('Clear', 'twostars-platform'); ?></button>
			</div>
		</div>
		<?php
	}
}
