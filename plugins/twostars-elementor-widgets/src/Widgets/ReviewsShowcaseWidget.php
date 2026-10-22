<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;

final class ReviewsShowcaseWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-reviews-showcase';
	}

	public function get_title(): string
	{
		return __('Reviews Showcase', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-testimonial-carousel';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('eyebrow', ['label' => __('Eyebrow', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Guest Reviews', 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('What guests say after a day with Two Stars', 'twostars-elementor-widgets')]);
		$this->add_control('intro', ['label' => __('Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('RECENT FEEDBACK FROM WALVIS BAY, SANDWICH HARBOUR, SWAKOPMUND, AND SPITZKOPPE TOURS', 'twostars-elementor-widgets')]);
		$this->add_control('auto_limit', ['label' => __('Reviews To Show', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 6, 'min' => 1, 'max' => 20]);
		$this->add_control('bg_image', [
			'label' => __('Background Image', 'twostars-elementor-widgets'),
			'type' => Controls_Manager::MEDIA,
			'default' => [
				'url' => content_url('uploads/2026/03/WhatsApp-Image-2026-03-27-at-5.55.41-AM-2-1.jpeg'),
			],
		]);
		$this->add_control(
			'show_grid',
			[
				'label' => __('Show Reviews Grid', 'twostars-elementor-widgets'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Yes', 'twostars-elementor-widgets'),
				'label_off' => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default' => '',
			]
		);
		$this->add_control('grid_title', ['label' => __('Grid Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('More guest feedback', 'twostars-elementor-widgets'), 'condition' => ['show_grid' => 'yes']]);
		$this->add_control('leave_review_label', ['label' => __('Leave Review Button', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Leave a Review', 'twostars-elementor-widgets')]);
		$this->add_control('view_all_label', ['label' => __('Secondary Button Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Read More Reviews', 'twostars-elementor-widgets')]);
		$this->add_control('view_all_link', ['label' => __('Secondary Button Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/reviews/')]]);
		$this->add_control('leave_review_heading', ['label' => __('Review Form Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Share your Two Stars experience', 'twostars-elementor-widgets')]);
		$this->add_control('leave_review_copy', ['label' => __('Review Form Copy', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Tell future guests what stood out, from the route and scenic stops to the pace, pickup, and overall feel of the day.', 'twostars-elementor-widgets')]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$reviews  = $this->resolve_reviews($settings);
		$average  = $this->calculate_average_rating($reviews);
		$tour_options = TourContent::get_tour_options();
		?>
		<section
			class="twostars-reviews-showcase"
			data-twostars-reviews
			data-twostars-testimonials
			data-autoplay="true"
			data-duration="6500"
			data-show-dots="true"
		>
			<div class="twostars-reviews-showcase__header" data-twostars-animate="fade-in-up">
				<div class="twostars-reviews-showcase__heading">
					<?php if (! empty($settings['eyebrow'])) : ?><p class="twostars-home-section-eyebrow"><?php echo esc_html((string) $settings['eyebrow']); ?></p><?php endif; ?>
					<?php if (! empty($settings['title'])) : ?><h2 class="twostars-home-section-title"><?php echo nl2br(esc_html((string) $settings['title'])); ?></h2><?php endif; ?>
					<?php if (! empty($settings['intro'])) : ?><p class="twostars-reviews-showcase__intro"><?php echo esc_html((string) $settings['intro']); ?></p><?php endif; ?>
				</div>
				<div class="twostars-reviews-showcase__summary">
					<strong><?php echo esc_html(number_format_i18n($average, 1)); ?></strong>
					<span><?php echo esc_html(sprintf(_n('%d guest review', '%d guest reviews', count($reviews), 'twostars-elementor-widgets'), count($reviews))); ?></span>
				</div>
			</div>

			<div class="twostars-reviews-showcase__slides">
				<?php foreach ($reviews as $index => $review) : ?>
					<article
						class="twostars-reviews-showcase__slide<?php echo $index === 0 ? ' is-active' : ''; ?>"
						data-slide-index="<?php echo esc_attr((string) $index); ?>"
						aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>"
						style="background-image: url('<?php echo esc_url((string) ($settings['bg_image']['url'] ?? '')); ?>');"
					>
						<div class="twostars-reviews-showcase__slide-overlay"></div>
						<div class="twostars-reviews-showcase__content">
							<div class="twostars-reviews-showcase__quote-icon" aria-hidden="true">&#8221;</div>
							<p class="twostars-reviews-showcase__rating"><?php echo esc_html($this->format_rating_stars((int) ($review['rating'] ?? 5))); ?></p>
							<blockquote class="twostars-reviews-showcase__quote"><?php echo esc_html((string) $review['quote']); ?></blockquote>
							<div class="twostars-reviews-showcase__guest">
								<?php if (! empty($review['author'])) : ?><strong><?php echo esc_html((string) $review['author']); ?></strong><?php endif; ?>
								<?php if (! empty($review['meta'])) : ?><span><?php echo esc_html((string) $review['meta']); ?></span><?php endif; ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
				<?php if (count($reviews) > 1) : ?>
					<div class="twostars-reviews-showcase__arrows" aria-label="<?php esc_attr_e('Review slider controls', 'twostars-elementor-widgets'); ?>">
						<button type="button" class="twostars-reviews-showcase__arrow twostars-reviews-showcase__arrow--prev" data-testimonial-prev aria-label="<?php esc_attr_e('Show previous review', 'twostars-elementor-widgets'); ?>">
							<span aria-hidden="true">←</span>
						</button>
						<button type="button" class="twostars-reviews-showcase__arrow twostars-reviews-showcase__arrow--next" data-testimonial-next aria-label="<?php esc_attr_e('Show next review', 'twostars-elementor-widgets'); ?>">
							<span aria-hidden="true">→</span>
						</button>
					</div>
				<?php endif; ?>
			</div>

			<div class="twostars-reviews-showcase__footer" data-twostars-animate="fade-in-up" style="--twostars-delay:0.1s;">
				<div class="twostars-testimonial-block__dots" aria-label="<?php esc_attr_e('Review navigation', 'twostars-elementor-widgets'); ?>">
					<?php foreach ($reviews as $index => $review) : ?>
						<button
							type="button"
							class="twostars-testimonial-block__dot<?php echo $index === 0 ? ' is-active' : ''; ?>"
							data-dot-index="<?php echo esc_attr((string) $index); ?>"
							aria-label="<?php echo esc_attr(sprintf(__('Show review %d', 'twostars-elementor-widgets'), $index + 1)); ?>"
						></button>
					<?php endforeach; ?>
				</div>
				<div class="twostars-reviews-showcase__actions">
					<?php if (! empty($settings['view_all_label']) && ! empty($settings['view_all_link']['url'])) : ?>
						<a class="twostars-safari-button twostars-safari-button--ghost" <?php echo $this->link_attributes((array) $settings['view_all_link']); ?>>
							<?php echo esc_html((string) $settings['view_all_label']); ?>
						</a>
					<?php endif; ?>
					<button type="button" class="twostars-safari-button twostars-safari-button--primary" data-twostars-review-open>
						<?php echo esc_html((string) ($settings['leave_review_label'] ?? __('Leave a Review', 'twostars-elementor-widgets'))); ?>
					</button>
				</div>
			</div>

			<?php if (($settings['show_grid'] ?? '') === 'yes' && $reviews !== []) : ?>
				<div class="twostars-reviews-showcase__grid-wrap" data-twostars-animate="fade-in-up" style="--twostars-delay:0.18s;">
					<?php if (! empty($settings['grid_title'])) : ?>
						<div class="twostars-reviews-showcase__grid-head">
							<h3 class="twostars-reviews-showcase__grid-title"><?php echo esc_html((string) $settings['grid_title']); ?></h3>
						</div>
					<?php endif; ?>
					<div class="twostars-reviews-showcase__grid">
						<?php foreach ($reviews as $review) : ?>
							<article class="twostars-reviews-showcase__card">
								<div class="twostars-reviews-showcase__card-body">
									<p class="twostars-reviews-showcase__card-rating"><?php echo esc_html($this->format_rating_stars((int) ($review['rating'] ?? 5))); ?></p>
									<blockquote class="twostars-reviews-showcase__card-quote"><?php echo esc_html((string) $review['quote']); ?></blockquote>
									<div class="twostars-reviews-showcase__card-guest">
										<?php if (! empty($review['author'])) : ?><strong><?php echo esc_html((string) $review['author']); ?></strong><?php endif; ?>
										<?php if (! empty($review['meta'])) : ?><span><?php echo esc_html((string) $review['meta']); ?></span><?php endif; ?>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="twostars-reviews-showcase__modal" hidden data-twostars-review-modal>
				<div class="twostars-reviews-showcase__modal-backdrop" data-twostars-review-close></div>
				<div class="twostars-reviews-showcase__modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($this->widget_dom_id('review-title')); ?>">
					<button type="button" class="twostars-reviews-showcase__modal-close" data-twostars-review-close aria-label="<?php esc_attr_e('Close review form', 'twostars-elementor-widgets'); ?>">&times;</button>
					<div class="twostars-reviews-showcase__modal-head">
						<p class="twostars-reviews-showcase__modal-eyebrow"><?php esc_html_e('Leave a Review', 'twostars-elementor-widgets'); ?></p>
						<h3 id="<?php echo esc_attr($this->widget_dom_id('review-title')); ?>"><?php echo esc_html((string) ($settings['leave_review_heading'] ?? '')); ?></h3>
						<p><?php echo esc_html((string) ($settings['leave_review_copy'] ?? '')); ?></p>
					</div>
					<form class="twostars-reviews-showcase__review-form" data-twostars-review-form>
						<input type="hidden" name="source" value="website">
						<input type="hidden" name="website" value="">
						<div class="twostars-reviews-showcase__review-grid">
							<label class="twostars-reviews-showcase__field">
								<span><?php esc_html_e('Your Name', 'twostars-elementor-widgets'); ?></span>
								<input type="text" name="full_name" required>
							</label>
							<label class="twostars-reviews-showcase__field">
								<span><?php esc_html_e('Email (Optional)', 'twostars-elementor-widgets'); ?></span>
								<input type="email" name="email">
							</label>
							<label class="twostars-reviews-showcase__field">
								<span><?php esc_html_e('Tour', 'twostars-elementor-widgets'); ?></span>
								<select name="tour_id">
									<?php foreach ($tour_options as $option_value => $option_label) : ?>
										<option value="<?php echo esc_attr((string) $option_value); ?>"><?php echo esc_html((string) $option_label); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="twostars-reviews-showcase__field">
								<span><?php esc_html_e('Guest Location', 'twostars-elementor-widgets'); ?></span>
								<input type="text" name="guest_location" placeholder="<?php esc_attr_e('City or Country', 'twostars-elementor-widgets'); ?>">
							</label>
							<label class="twostars-reviews-showcase__field twostars-reviews-showcase__field--wide">
								<span><?php esc_html_e('How was your experience?', 'twostars-elementor-widgets'); ?></span>
								<div class="twostars-reviews-showcase__star-picker" data-twostars-star-picker>
									<input type="hidden" name="rating" value="5">
									<?php for ($star = 1; $star <= 5; $star++) : ?>
										<button type="button" class="twostars-reviews-showcase__star is-active" data-rating="<?php echo esc_attr((string) $star); ?>" aria-label="<?php echo esc_attr(sprintf(__('Rate %d stars', 'twostars-elementor-widgets'), $star)); ?>">★</button>
									<?php endfor; ?>
								</div>
							</label>
							<label class="twostars-reviews-showcase__field twostars-reviews-showcase__field--wide">
								<span><?php esc_html_e('Your Review', 'twostars-elementor-widgets'); ?></span>
								<textarea name="review" rows="5" required placeholder="<?php esc_attr_e('Share what stood out about the route, the guiding, the scenery, or the overall feel of the day.', 'twostars-elementor-widgets'); ?>"></textarea>
							</label>
							<label class="twostars-reviews-showcase__field twostars-reviews-showcase__field--wide">
								<span><?php esc_html_e('Trip Label (Optional)', 'twostars-elementor-widgets'); ?></span>
								<input type="text" name="trip_label" placeholder="<?php esc_attr_e('Example: Sandwich Harbour 4x4 Adventure', 'twostars-elementor-widgets'); ?>">
							</label>
						</div>
						<label class="twostars-reviews-showcase__consent">
							<input type="checkbox" name="review_consent" value="1" required>
							<span><?php esc_html_e('I agree that Two Stars may publish my feedback on the website.', 'twostars-elementor-widgets'); ?></span>
						</label>
						<div class="twostars-reviews-showcase__review-actions">
							<button type="submit" class="twostars-reviews-showcase__review-submit"><?php echo esc_html((string) ($settings['leave_review_label'] ?? __('Leave a Review', 'twostars-elementor-widgets'))); ?></button>
							<p class="twostars-reviews-showcase__review-status" data-twostars-review-status></p>
						</div>
					</form>
					<div class="twostars-reviews-showcase__thankyou" hidden data-twostars-review-thankyou>
						<p class="twostars-reviews-showcase__modal-eyebrow"><?php esc_html_e('Thank You', 'twostars-elementor-widgets'); ?></p>
						<h3><?php esc_html_e('Your review has been sent to the Two Stars team.', 'twostars-elementor-widgets'); ?></h3>
						<p><?php esc_html_e('We truly appreciate you taking the time to share your experience with us.', 'twostars-elementor-widgets'); ?></p>
					</div>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<int, array<string, string|int>>
	 */
	private function resolve_reviews(array $settings): array
	{
		$limit = max(1, (int) ($settings['auto_limit'] ?? 6));
		$posts = TourContent::get_reviews(['posts_per_page' => $limit]);

		if ($posts === []) {
			return [$this->fallback_review()];
		}

		$reviews = [];

		foreach ($posts as $post) {
			$quote = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 34);
			$trip = (string) get_post_meta($post->ID, '_twostars_review_trip_label', true);
			$location = (string) get_post_meta($post->ID, '_twostars_review_guest_location', true);
			$meta = implode(' • ', array_filter([$trip, $location]));

			$reviews[] = [
				'quote'  => $quote,
				'author' => $post->post_title,
				'meta'   => $meta,
				'rating' => (int) get_post_meta($post->ID, '_twostars_review_rating', true) ?: 5,
			];
		}

		return $reviews !== [] ? $reviews : [$this->fallback_review()];
	}

	/**
	 * @return array<string, string|int>
	 */
	private function fallback_review(): array
	{
		return [
			'quote'  => __('Everything felt easy from pickup to the final stop. We saw the lagoon, the dunes, and the coastline without ever feeling rushed.', 'twostars-elementor-widgets'),
			'author' => __('Two Stars Guest', 'twostars-elementor-widgets'),
			'meta'   => __('Walvis Bay and Sandwich Harbour route', 'twostars-elementor-widgets'),
			'rating' => 5,
		];
	}

	/**
	 * @param array<int, array<string, string|int>> $reviews
	 */
	private function calculate_average_rating(array $reviews): float
	{
		$ratings = array_map(static fn(array $review): int => max(1, min(5, (int) ($review['rating'] ?? 5))), $reviews);

		if ($ratings === []) {
			return 5.0;
		}

		return array_sum($ratings) / count($ratings);
	}

	private function format_rating_stars(int $rating): string
	{
		$rating = max(1, min(5, $rating));

		return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
	}
}
