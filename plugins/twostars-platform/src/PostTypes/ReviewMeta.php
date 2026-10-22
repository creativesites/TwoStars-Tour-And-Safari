<?php
declare(strict_types=1);

namespace TwoStars\Platform\PostTypes;

use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Support\TourData;

final class ReviewMeta implements Service
{
	private const NONCE_ACTION = 'twostars_platform_save_review_meta';
	private const NONCE_NAME = 'twostars_platform_review_meta_nonce';

	public function register(): void
	{
		add_action('init', [$this, 'register_meta']);
		add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
		add_action('save_post_testimonial', [$this, 'save_meta']);
		add_filter('manage_testimonial_posts_columns', [$this, 'register_columns']);
		add_action('manage_testimonial_posts_custom_column', [$this, 'render_column'], 10, 2);
	}

	public function register_meta(): void
	{
		foreach (self::get_manager_field_definitions() as $meta_key => $definition) {
			register_post_meta(
				'testimonial',
				$meta_key,
				[
					'type'              => (string) ($definition['meta_type'] ?? 'string'),
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => fn($value) => $this->sanitize_value($meta_key, $value),
					'auth_callback'     => static fn() => current_user_can('edit_posts'),
				]
			);
		}
	}

	public function register_meta_boxes(): void
	{
		add_meta_box(
			'twostars-review-details',
			__('Review Details', 'twostars-platform'),
			[$this, 'render_details_meta_box'],
			'testimonial',
			'normal',
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
		<div class="twostars-admin-fields twostars-admin-fields--review">
			<?php foreach (self::get_manager_field_definitions() as $meta_key => $definition) : ?>
				<?php $this->render_field((int) $post->ID, $meta_key, $definition); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

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
		$columns['review_rating']   = __('Rating', 'twostars-platform');
		$columns['review_trip']     = __('Trip', 'twostars-platform');
		$columns['review_location'] = __('Location', 'twostars-platform');

		return $columns;
	}

	public function render_column(string $column, int $post_id): void
	{
		if ($column === 'review_rating') {
			$rating = (int) get_post_meta($post_id, '_twostars_review_rating', true);
			echo $rating > 0 ? esc_html(str_repeat('★', $rating)) : '&mdash;';
			return;
		}

		if ($column === 'review_trip') {
			$trip = (string) get_post_meta($post_id, '_twostars_review_trip_label', true);
			echo $trip !== '' ? esc_html($trip) : '&mdash;';
			return;
		}

		if ($column === 'review_location') {
			$location = (string) get_post_meta($post_id, '_twostars_review_guest_location', true);
			echo $location !== '' ? esc_html($location) : '&mdash;';
		}
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_manager_field_definitions(): array
	{
		return [
			'_twostars_review_rating' => [
				'label'   => __('Rating', 'twostars-platform'),
				'type'    => 'select',
				'meta_type' => 'integer',
				'choices' => [
					'5' => __('5 Stars', 'twostars-platform'),
					'4' => __('4 Stars', 'twostars-platform'),
					'3' => __('3 Stars', 'twostars-platform'),
					'2' => __('2 Stars', 'twostars-platform'),
					'1' => __('1 Star', 'twostars-platform'),
				],
			],
			'_twostars_review_guest_location' => [
				'label' => __('Guest Location', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_review_trip_label' => [
				'label' => __('Trip / Route Label', 'twostars-platform'),
				'type'  => 'text',
			],
			'_twostars_review_tour_id' => [
				'label'   => __('Linked Tour', 'twostars-platform'),
				'type'    => 'select',
				'meta_type' => 'integer',
				'choices' => ['' => __('No linked tour', 'twostars-platform')] + TourData::get_tour_options(),
			],
			'_twostars_review_source' => [
				'label'   => __('Source', 'twostars-platform'),
				'type'    => 'select',
				'choices' => [
					'website'  => __('Website Review Form', 'twostars-platform'),
					'manual'   => __('Added by Team', 'twostars-platform'),
					'google'   => __('Google Review', 'twostars-platform'),
					'whatsapp' => __('WhatsApp Message', 'twostars-platform'),
				],
			],
			'_twostars_review_featured' => [
				'label'   => __('Homepage Priority', 'twostars-platform'),
				'type'    => 'select',
				'choices' => [
					'standard' => __('Standard', 'twostars-platform'),
					'featured' => __('Featured', 'twostars-platform'),
				],
			],
		];
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
		<p class="twostars-admin-field">
			<label for="<?php echo esc_attr($field_id); ?>"><strong><?php echo esc_html((string) $definition['label']); ?></strong></label><br>
			<?php if ($type === 'select') : ?>
				<select id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_id); ?>" style="width: 100%;">
					<option value=""><?php esc_html_e('Select an option', 'twostars-platform'); ?></option>
					<?php foreach ((array) ($definition['choices'] ?? []) as $option_value => $option_label) : ?>
						<option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected((string) $value, (string) $option_value); ?>>
							<?php echo esc_html((string) $option_label); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input
					id="<?php echo esc_attr($field_id); ?>"
					name="<?php echo esc_attr($field_id); ?>"
					type="text"
					value="<?php echo esc_attr((string) $value); ?>"
					style="width: 100%;"
				>
			<?php endif; ?>
		</p>
		<?php
	}

	private function sanitize_value(string $meta_key, mixed $value): mixed
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

		return match ($meta_key) {
			'_twostars_review_rating'  => max(1, min(5, absint($value))),
			'_twostars_review_tour_id' => absint($value),
			default                    => sanitize_text_field((string) $value),
		};
	}
}
