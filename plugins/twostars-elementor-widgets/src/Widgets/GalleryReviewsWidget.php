<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use TwoStars\ElementorWidgets\Support\TourContent;

final class GalleryReviewsWidget extends GalleryPageWidgetBase
{
	public function get_name(): string
	{
		return 'twostars-gallery-reviews';
	}

	public function get_title(): string
	{
		return __('Gallery Reviews', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-testimonial-carousel';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);

		$this->add_control('eyebrow', [
			'label'   => __('Eyebrow', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Through The Eyes of Our Guests', 'twostars-elementor-widgets'),
		]);

		$this->add_control('title', [
			'label'   => __('Title', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => __('Moments That Stay With You', 'twostars-elementor-widgets'),
		]);

		$this->add_control('intro', [
			'label'   => __('Intro', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => __('Read unedited stories from travelers who have explored the dunes, ocean, and wildlife of Namibia with us. These are their real experiences.', 'twostars-elementor-widgets'),
		]);

		$this->add_control('auto_limit', [
			'label'   => __('Reviews To Show', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::NUMBER,
			'default' => 6,
			'min'     => 1,
			'max'     => 12,
		]);

		$this->add_control('view_all_label', [
			'label'   => __('View All Button Label', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Read All Reviews', 'twostars-elementor-widgets'),
		]);

		$this->add_control('view_all_link', [
			'label'   => __('View All Button Link', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::URL,
			'default' => ['url' => home_url('/reviews/')],
		]);

		$this->add_control('leave_review_label', [
			'label'   => __('Leave Review Button', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Leave a Review', 'twostars-elementor-widgets'),
		]);

		$this->add_control('leave_review_heading', [
			'label'   => __('Review Form Heading', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXT,
			'default' => __('Share Your Journey', 'twostars-elementor-widgets'),
		]);

		$this->add_control('leave_review_copy', [
			'label'   => __('Review Form Copy', 'twostars-elementor-widgets'),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => __('Tell future guests what made the day special — the route, the wildlife, the dunes, or the moment it all came together.', 'twostars-elementor-widgets'),
		]);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings    = $this->get_settings_for_display();
		$reviews     = $this->resolve_reviews($settings);
		$average     = $this->calculate_average_rating($reviews);
		$tour_options = TourContent::get_tour_options();
		?>
		<section class="twostars-gallery-reviews" data-twostars-animate="fade-in-up">
			<div class="twostars-gallery-reviews__inner">
				<div class="twostars-gallery-reviews__header">
					<?php if (! empty($settings['eyebrow'])) : ?>
						<p class="twostars-gallery-reviews__eyebrow"><?php echo esc_html((string) $settings['eyebrow']); ?></p>
					<?php endif; ?>
					<?php if (! empty($settings['title'])) : ?>
						<h2 class="twostars-gallery-reviews__title"><?php echo nl2br(esc_html((string) $settings['title'])); ?></h2>
					<?php endif; ?>
					<?php if (! empty($settings['intro'])) : ?>
						<p class="twostars-gallery-reviews__intro"><?php echo esc_html((string) $settings['intro']); ?></p>
					<?php endif; ?>
					<div class="twostars-gallery-reviews__summary">
						<span class="twostars-gallery-reviews__stars" aria-label="<?php echo esc_attr(number_format_i18n($average, 1) . ' out of 5 stars'); ?>">
							<?php echo esc_html(str_repeat('★', (int) round($average)) . str_repeat('☆', 5 - (int) round($average))); ?>
						</span>
						<span class="twostars-gallery-reviews__count">
							<?php echo esc_html(sprintf(
								_n('%d review', '%d reviews', count($reviews), 'twostars-elementor-widgets'),
								count($reviews)
							)); ?>
						</span>
					</div>
				</div>

				<div class="twostars-gallery-reviews__grid">
					<?php foreach ($reviews as $index => $review) : ?>
						<article class="twostars-gallery-reviews__card" style="--twostars-delay:<?php echo esc_attr(number_format($index * 0.1, 2)); ?>s;" data-twostars-animate="fade-in-up">
							<p class="twostars-gallery-reviews__card-rating" aria-label="<?php echo esc_attr((string) ($review['rating'] ?? 5)) . ' stars'; ?>">
								<?php echo esc_html($this->format_stars((int) ($review['rating'] ?? 5))); ?>
							</p>
							<blockquote class="twostars-gallery-reviews__card-quote">
								<?php echo esc_html($this->trim_quote((string) ($review['quote'] ?? ''), 160)); ?>
							</blockquote>
							<div class="twostars-gallery-reviews__card-guest">
								<?php if (! empty($review['author'])) : ?>
									<strong class="twostars-gallery-reviews__card-name"><?php echo esc_html((string) $review['author']); ?></strong>
								<?php endif; ?>
								<?php if (! empty($review['meta'])) : ?>
									<span class="twostars-gallery-reviews__card-meta"><?php echo esc_html((string) $review['meta']); ?></span>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<div class="twostars-gallery-reviews__actions">
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

			<!-- Review submission modal (shared pattern from ReviewsShowcaseWidget) -->
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
							<span><?php esc_html_e('I agree that Two Stars may review and publish my feedback on the website once approved.', 'twostars-elementor-widgets'); ?></span>
						</label>
						<div class="twostars-reviews-showcase__review-actions">
							<button type="submit" class="twostars-reviews-showcase__review-submit"><?php echo esc_html((string) ($settings['leave_review_label'] ?? __('Leave a Review', 'twostars-elementor-widgets'))); ?></button>
							<p class="twostars-reviews-showcase__review-status" data-twostars-review-status></p>
						</div>
					</form>
					<div class="twostars-reviews-showcase__thankyou" hidden data-twostars-review-thankyou>
						<p class="twostars-reviews-showcase__modal-eyebrow"><?php esc_html_e('Thank You', 'twostars-elementor-widgets'); ?></p>
						<h3><?php esc_html_e('Your review has been sent to the Two Stars team.', 'twostars-elementor-widgets'); ?></h3>
						<p><?php esc_html_e('Each review is checked before it goes live so the feedback stays genuine and helpful to future guests.', 'twostars-elementor-widgets'); ?></p>
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
			$quote    = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 34);
			$trip     = (string) get_post_meta($post->ID, '_twostars_review_trip_label', true);
			$location = (string) get_post_meta($post->ID, '_twostars_review_guest_location', true);
			$meta     = implode(' • ', array_filter([$trip, $location]));

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
			'quote'  => __('The dunes, the ocean, the flamingos — every stop felt tailored to us. Our guide knew exactly when to pause and let the ancient landscape speak for itself. An unforgettable day in Walvis Bay.', 'twostars-elementor-widgets'),
			'author' => __('Two Stars Guest', 'twostars-elementor-widgets'),
			'meta'   => __('Sandwich Harbour 4x4 Adventure', 'twostars-elementor-widgets'),
			'rating' => 5,
		];
	}

	/**
	 * @param array<int, array<string, string|int>> $reviews
	 */
	private function calculate_average_rating(array $reviews): float
	{
		$ratings = array_map(static fn(array $r): int => max(1, min(5, (int) ($r['rating'] ?? 5))), $reviews);

		if ($ratings === []) {
			return 5.0;
		}

		return array_sum($ratings) / count($ratings);
	}

	private function format_stars(int $rating): string
	{
		$rating = max(1, min(5, $rating));

		return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
	}

	private function trim_quote(string $quote, int $max): string
	{
		if (mb_strlen($quote) <= $max) {
			return $quote;
		}

		return mb_substr($quote, 0, $max - 1) . '…';
	}
}
