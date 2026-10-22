<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use TwoStars\ElementorWidgets\Support\TourContent;
use WP_Post;

final class TestimonialBlockWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-testimonial-block';
	}

	public function get_title(): string
	{
		return __('Traveler Stories Block', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-testimonial';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Testimonials', 'twostars-elementor-widgets'),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'content',
			[
				'label'   => __('Testimonial Content', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('The whole trip felt alive from day one. Great energy, smooth planning, and exactly the kind of adventure we hoped for.', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'author_name',
			[
				'label'   => __('Author Name', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Sarah Johnson', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'author_role',
			[
				'label'   => __('Author Role/Location', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('London, United Kingdom', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'author_photo',
			[
				'label'   => __('Author Photo', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => '',
				],
			]
		);

		$repeater->add_control(
			'rating',
			[
				'label'   => __('Rating (1-5)', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 5,
				'step'    => 1,
				'default' => 5,
			]
		);

		$this->add_control(
			'content_source',
			[
				'label'   => __('Content Source', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'manual'       => __('Manual items', 'twostars-elementor-widgets'),
					'testimonials' => __('Auto: testimonials', 'twostars-elementor-widgets'),
				],
				'default' => 'manual',
			]
		);

		$this->add_control(
			'auto_limit',
			[
				'label'     => __('Auto Item Limit', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 12,
				'condition' => [
					'content_source!' => 'manual',
				],
			]
		);

		$this->add_control(
			'testimonials',
			[
				'label'       => __('Items', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ author }}}',
				'default'     => [
					[
						'content'     => __('The route felt personal, fun, and easy to follow. Every stop gave us something memorable to photograph and talk about later.', 'twostars-elementor-widgets'),
						'author_name' => __('Rachel T.', 'twostars-elementor-widgets'),
						'author_role' => __('Cape Town, South Africa', 'twostars-elementor-widgets'),
						'rating'      => 5,
					],
					[
						'content'     => __('Strong communication, smooth logistics, and the right mix of comfort and adventure all the way through Namibia.', 'twostars-elementor-widgets'),
						'author_name' => __('James and Olivia', 'twostars-elementor-widgets'),
						'author_role' => __('New York, United States', 'twostars-elementor-widgets'),
						'rating'      => 5,
					],
				],
				'condition'   => [
					'content_source' => 'manual',
				],
			]
		);

		$this->add_control(
			'enable_slider',
			[
				'label'        => __('Enable Slider', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'auto_rotate_duration',
			[
				'label'   => __('Auto Rotate Duration (ms)', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::NUMBER,
				'default' => 6000,
				'min'     => 2000,
				'step'    => 500,
			]
		);

		$this->add_control(
			'show_navigation_dots',
			[
				'label'        => __('Show Navigation Dots', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings       = $this->get_settings_for_display();
		$testimonials   = $this->resolve_testimonials($settings);
		$enable_slider  = ($settings['enable_slider'] ?? 'yes') === 'yes' && count($testimonials) > 1;
		$show_nav_dots  = ($settings['show_navigation_dots'] ?? 'yes') === 'yes';
		?>
		<section class="twostars-testimonial-section">
			<div class="twostars-testimonial-section__decoration twostars-testimonial-section__decoration--star-lt" aria-hidden="true">★</div>
			<div class="twostars-testimonial-section__decoration twostars-testimonial-section__decoration--star-rb" aria-hidden="true">★</div>

			<section
				class="twostars-testimonial-block"
				data-testimonial
				data-twostars-testimonials
				data-autoplay="<?php echo $enable_slider ? 'true' : 'false'; ?>"
				data-duration="<?php echo esc_attr((string) absint($settings['auto_rotate_duration'] ?? 6000)); ?>"
				data-show-dots="<?php echo $show_nav_dots ? 'true' : 'false'; ?>"
				data-twostars-animate="fade-in-up"
			>
				<div class="twostars-testimonial-block__slides">
					<?php foreach ($testimonials as $index => $item) : ?>
						<article
							class="twostars-testimonial-block__item<?php echo $index === 0 ? ' is-active' : ''; ?>"
							data-slide-index="<?php echo esc_attr((string) $index); ?>"
							aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>"
						>
							<div class="twostars-testimonial-block__rating">
								<?php
								$rating = absint($item['rating'] ?? 5);
								for ($i = 1; $i <= 5; $i++) {
									$class = $i <= $rating ? 'fas fa-star' : 'far fa-star';
									echo '<i class="' . esc_attr($class) . '" aria-hidden="true"></i>';
								}
								?>
							</div>

							<?php if (! empty($item['content'])) : ?>
								<blockquote class="twostars-testimonial-block__quote"><?php echo $this->format_text((string) $item['content']); ?></blockquote>
							<?php endif; ?>

							<div class="twostars-testimonial-block__author">
								<div class="twostars-testimonial-block__author-photo">
									<?php if (! empty($item['author_photo']['id'])) : ?>
										<?php echo wp_get_attachment_image(absint($item['author_photo']['id']), 'thumbnail', false, ['loading' => 'lazy']); ?>
									<?php else : ?>
										<div class="twostars-testimonial-block__author-placeholder"></div>
									<?php endif; ?>
								</div>
								<div class="twostars-testimonial-block__author-info">
									<?php if (! empty($item['author_name'])) : ?>
										<cite class="twostars-testimonial-block__author-name"><?php echo esc_html((string) $item['author_name']); ?></cite>
									<?php endif; ?>
									<?php if (! empty($item['author_role'])) : ?>
										<span class="twostars-testimonial-block__author-role"><?php echo esc_html((string) $item['author_role']); ?></span>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<?php if ($enable_slider && $show_nav_dots) : ?>
					<div class="twostars-testimonial-block__dots" aria-label="<?php esc_attr_e('Testimonial navigation', 'twostars-elementor-widgets'); ?>">
						<?php foreach ($testimonials as $index => $item) : ?>
							<button
								type="button"
								class="twostars-testimonial-block__dot<?php echo $index === 0 ? ' is-active' : ''; ?>"
								data-dot-index="<?php echo esc_attr((string) $index); ?>"
								aria-label="<?php echo esc_attr(sprintf(__('Show testimonial %d', 'twostars-elementor-widgets'), $index + 1)); ?>"
							></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<int, array<string, string>>
	 */
	private function resolve_testimonials(array $settings): array
	{
		if (($settings['content_source'] ?? 'manual') !== 'testimonials') {
			return array_values((array) ($settings['testimonials'] ?? []));
		}

		$limit = max(1, (int) ($settings['auto_limit'] ?? 3));

		return array_map(
			static function (WP_Post $post): array {
				return [
					'content'      => has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 28),
					'author_name'  => $post->post_title,
					'author_role'  => (string) get_post_meta($post->ID, 'testimonial_meta', true),
					'author_photo' => ['id' => get_post_thumbnail_id($post->ID)],
					'rating'       => (int) get_post_meta($post->ID, 'testimonial_rating', true) ?: 5,
				];
			},
			array_slice(TourContent::get_testimonials(['posts_per_page' => $limit]), 0, $limit)
		);
	}
}
