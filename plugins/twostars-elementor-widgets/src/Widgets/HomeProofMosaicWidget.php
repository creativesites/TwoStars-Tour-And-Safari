<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use TwoStars\ElementorWidgets\Support\TourContent;

final class HomeProofMosaicWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-home-proof-mosaic';
	}

	public function get_title(): string
	{
		return __('Homepage Reviews', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-testimonial-carousel';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('eyebrow', ['label' => __('Eyebrow', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Guest Reviews', 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Real guest feedback that helps new travelers book with confidence.', 'twostars-elementor-widgets')]);
		$this->add_control('use_latest_testimonial', ['label' => __('Use Latest Testimonial', 'twostars-elementor-widgets'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes']);
		$this->add_control('quote_label', ['label' => __('Quote Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Featured Review', 'twostars-elementor-widgets')]);
		$this->add_control('quote', ['label' => __('Manual Quote', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Everything felt easy from pickup to the final stop. We saw the lagoon, the dunes, and the coastline without ever feeling rushed.', 'twostars-elementor-widgets'), 'condition' => ['use_latest_testimonial!' => 'yes']]);
		$this->add_control('author', ['label' => __('Manual Author', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Guest Feedback', 'twostars-elementor-widgets'), 'condition' => ['use_latest_testimonial!' => 'yes']]);
		$this->add_control('meta', ['label' => __('Manual Meta', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Two Stars Travelers', 'twostars-elementor-widgets'), 'condition' => ['use_latest_testimonial!' => 'yes']]);
		$this->add_control(
			'quote_image',
			[
				'label' => __('Quote Image', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::MEDIA,
			]
		);
		$this->add_control('quote_kicker', ['label' => __('Quote Kicker', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Local pickup, clear communication, scenic pacing, and memorable route moments.', 'twostars-elementor-widgets')]);
		$this->add_control('leave_review_label', ['label' => __('Leave Review Button', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Leave a Review', 'twostars-elementor-widgets')]);
		$this->add_control('leave_review_heading', ['label' => __('Review Form Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Share your Two Stars experience', 'twostars-elementor-widgets')]);
		$this->add_control('leave_review_copy', ['label' => __('Review Form Copy', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Tell future guests what stood out about your day, from the route and scenic stops to the pace, pickup, and overall experience.', 'twostars-elementor-widgets')]);

		$repeater = new Repeater();
		$repeater->add_control('value', ['label' => __('Value', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Tailored', 'twostars-elementor-widgets')]);
		$repeater->add_control('label', ['label' => __('Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Planning Model', 'twostars-elementor-widgets')]);

		$this->add_control(
			'stats',
			[
				'label'       => __('Stats', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => [
					['value' => __('Private', 'twostars-elementor-widgets'), 'label' => __('Travel Style', 'twostars-elementor-widgets')],
					['value' => __('Direct', 'twostars-elementor-widgets'), 'label' => __('Planning Access', 'twostars-elementor-widgets')],
					['value' => __('Premium', 'twostars-elementor-widgets'), 'label' => __('Guest Expectation', 'twostars-elementor-widgets')],
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings          = $this->get_settings_for_display();
		$reviews           = $this->resolve_reviews($settings);
		$featured_review   = $reviews[0] ?? $this->fallback_review($settings);
		$supporting_reviews = array_slice($reviews, 1, 3);
		$quote_image       = $this->resolve_quote_image($settings, $featured_review);
		$quote_style       = $quote_image !== '' ? ' style="--twostars-proof-image:url(\'' . esc_url($quote_image) . '\');"' : '';
		$header_eyebrow    = $this->resolve_display_copy((string) ($settings['eyebrow'] ?? ''), __('Proof And Assurance', 'twostars-elementor-widgets'), __('Guest Reviews', 'twostars-elementor-widgets'));
		$header_title      = $this->resolve_display_copy((string) ($settings['title'] ?? ''), __('A calm, capable day on the coast makes booking feel easy.', 'twostars-elementor-widgets'), __('Real guest feedback that helps new travelers book with confidence.', 'twostars-elementor-widgets'));
		$quote_label       = $this->resolve_display_copy((string) ($settings['quote_label'] ?? ''), __('Guest Perspective', 'twostars-elementor-widgets'), __('Featured Review', 'twostars-elementor-widgets'));
		$average_rating    = $this->calculate_average_rating($reviews);
		$review_count      = count($reviews);
		$tour_options      = TourContent::get_tour_options();
		?>
		<section class="twostars-home-proof" data-twostars-reviews>
			<div class="twostars-home-proof__header" data-twostars-animate="fade-in-up">
				<div class="twostars-home-proof__heading">
					<?php if ($header_eyebrow !== '') : ?><p class="twostars-home-section-eyebrow"><?php echo esc_html($header_eyebrow); ?></p><?php endif; ?>
					<?php if ($header_title !== '') : ?><h2 class="twostars-home-section-title"><?php echo nl2br(esc_html($header_title)); ?></h2><?php endif; ?>
				</div>
				<div class="twostars-home-proof__header-actions">
					<button type="button" class="twostars-home-proof__review-button" data-twostars-review-open>
						<?php echo esc_html((string) ($settings['leave_review_label'] ?? __('Leave a Review', 'twostars-elementor-widgets'))); ?>
					</button>
				</div>
			</div>

			<div class="twostars-home-proof__grid">
				<article class="twostars-home-proof__quote" data-twostars-animate="fade-in-left"<?php echo $quote_style; ?>>
					<div class="twostars-home-proof__quote-media" aria-hidden="true">
						<div class="twostars-home-proof__quote-glow"></div>
					</div>
					<div class="twostars-home-proof__quote-content">
						<?php if ($quote_label !== '') : ?><p class="twostars-home-proof__quote-label"><?php echo esc_html($quote_label); ?></p><?php endif; ?>
						<div class="twostars-home-proof__rating"><?php echo esc_html($this->format_rating_stars((int) ($featured_review['rating'] ?? 5))); ?></div>
						<?php if (! empty($featured_review['quote'])) : ?><blockquote><?php echo esc_html((string) $featured_review['quote']); ?></blockquote><?php endif; ?>
						<?php if (! empty($settings['quote_kicker'])) : ?><p class="twostars-home-proof__quote-kicker"><?php echo esc_html((string) $settings['quote_kicker']); ?></p><?php endif; ?>
						<div class="twostars-home-proof__person">
							<?php if (! empty($featured_review['author'])) : ?><p class="twostars-home-proof__author"><?php echo esc_html((string) $featured_review['author']); ?></p><?php endif; ?>
							<?php if (! empty($featured_review['meta'])) : ?><p class="twostars-home-proof__meta"><?php echo esc_html((string) $featured_review['meta']); ?></p><?php endif; ?>
						</div>
					</div>
				</article>

				<div class="twostars-home-proof__rail" data-twostars-animate="fade-in-right" style="--twostars-delay:0.15s;">
					<div class="twostars-home-proof__summary-card">
						<p class="twostars-home-proof__summary-label"><?php esc_html_e('Guest Rating Snapshot', 'twostars-elementor-widgets'); ?></p>
						<div class="twostars-home-proof__summary-value"><?php echo esc_html(number_format_i18n($average_rating, 1)); ?></div>
						<p class="twostars-home-proof__summary-stars"><?php echo esc_html($this->format_rating_stars((int) round($average_rating))); ?></p>
						<p class="twostars-home-proof__summary-meta">
							<?php
							echo esc_html(
								sprintf(
									_n('%d approved review', '%d approved reviews', max(1, $review_count), 'twostars-elementor-widgets'),
									max(1, $review_count)
								)
							);
							?>
						</p>
					</div>

					<?php foreach ($supporting_reviews as $index => $review) : ?>
						<article class="twostars-home-proof__mini-review" style="<?php echo esc_attr($this->build_inline_vars(['--twostars-delay' => (0.1 + ($index * 0.08)) . 's'])); ?>">
							<div class="twostars-home-proof__mini-rating"><?php echo esc_html($this->format_rating_stars((int) ($review['rating'] ?? 5))); ?></div>
							<?php if (! empty($review['quote'])) : ?><p class="twostars-home-proof__mini-quote"><?php echo esc_html(wp_trim_words((string) $review['quote'], 20, '...')); ?></p><?php endif; ?>
							<div class="twostars-home-proof__mini-meta">
								<?php if (! empty($review['author'])) : ?><strong><?php echo esc_html((string) $review['author']); ?></strong><?php endif; ?>
								<?php if (! empty($review['meta'])) : ?><span><?php echo esc_html((string) $review['meta']); ?></span><?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>

					<div class="twostars-home-proof__stats">
						<?php foreach ((array) ($settings['stats'] ?? []) as $stat) : ?>
							<?php if (empty($stat['value']) && empty($stat['label'])) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<div class="twostars-home-proof__stat">
								<?php if (! empty($stat['value'])) : ?><strong><?php echo esc_html((string) $stat['value']); ?></strong><?php endif; ?>
								<?php if (! empty($stat['label'])) : ?><span><?php echo esc_html((string) $stat['label']); ?></span><?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="twostars-home-proof__modal" hidden data-twostars-review-modal>
				<div class="twostars-home-proof__modal-backdrop" data-twostars-review-close></div>
				<div class="twostars-home-proof__modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($this->widget_dom_id('review-title')); ?>">
					<button type="button" class="twostars-home-proof__modal-close" data-twostars-review-close aria-label="<?php esc_attr_e('Close review form', 'twostars-elementor-widgets'); ?>">&times;</button>
					<div class="twostars-home-proof__modal-head">
						<p class="twostars-home-proof__modal-eyebrow"><?php esc_html_e('Leave a Review', 'twostars-elementor-widgets'); ?></p>
						<h3 id="<?php echo esc_attr($this->widget_dom_id('review-title')); ?>"><?php echo esc_html((string) ($settings['leave_review_heading'] ?? '')); ?></h3>
						<p><?php echo esc_html((string) ($settings['leave_review_copy'] ?? '')); ?></p>
					</div>
					<form class="twostars-home-proof__review-form" data-twostars-review-form>
						<input type="hidden" name="source" value="website">
						<input type="hidden" name="website" value="">
						<div class="twostars-home-proof__review-grid">
							<label class="twostars-home-proof__field">
								<span><?php esc_html_e('Your Name', 'twostars-elementor-widgets'); ?></span>
								<input type="text" name="full_name" required>
							</label>
							<label class="twostars-home-proof__field">
								<span><?php esc_html_e('Email (Optional)', 'twostars-elementor-widgets'); ?></span>
								<input type="email" name="email">
							</label>
							<label class="twostars-home-proof__field">
								<span><?php esc_html_e('Tour', 'twostars-elementor-widgets'); ?></span>
								<select name="tour_id">
									<?php foreach ($tour_options as $option_value => $option_label) : ?>
										<option value="<?php echo esc_attr((string) $option_value); ?>"><?php echo esc_html((string) $option_label); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="twostars-home-proof__field">
								<span><?php esc_html_e('Guest Location', 'twostars-elementor-widgets'); ?></span>
								<input type="text" name="guest_location" placeholder="<?php esc_attr_e('City or Country', 'twostars-elementor-widgets'); ?>">
							</label>
							<label class="twostars-home-proof__field twostars-home-proof__field--wide">
								<span><?php esc_html_e('How was your experience?', 'twostars-elementor-widgets'); ?></span>
								<div class="twostars-home-proof__star-picker" data-twostars-star-picker>
									<input type="hidden" name="rating" value="5">
									<?php for ($star = 1; $star <= 5; $star++) : ?>
										<button type="button" class="twostars-home-proof__star<?php echo $star <= 5 ? ' is-active' : ''; ?>" data-rating="<?php echo esc_attr((string) $star); ?>" aria-label="<?php echo esc_attr(sprintf(__('Rate %d stars', 'twostars-elementor-widgets'), $star)); ?>">★</button>
									<?php endfor; ?>
								</div>
							</label>
							<label class="twostars-home-proof__field twostars-home-proof__field--wide">
								<span><?php esc_html_e('Your Review', 'twostars-elementor-widgets'); ?></span>
								<textarea name="review" rows="5" required placeholder="<?php esc_attr_e('Share what stood out about the route, guiding, pickup, scenery, or overall experience.', 'twostars-elementor-widgets'); ?>"></textarea>
							</label>
							<label class="twostars-home-proof__field twostars-home-proof__field--wide">
								<span><?php esc_html_e('Trip Label (Optional)', 'twostars-elementor-widgets'); ?></span>
								<input type="text" name="trip_label" placeholder="<?php esc_attr_e('Example: Sandwich Harbour 4x4 Adventure', 'twostars-elementor-widgets'); ?>">
							</label>
						</div>
						<label class="twostars-home-proof__consent">
							<input type="checkbox" name="review_consent" value="1" required>
							<span><?php esc_html_e('I agree that Two Stars may review and publish my feedback on the website once approved.', 'twostars-elementor-widgets'); ?></span>
						</label>
						<div class="twostars-home-proof__review-actions">
							<button type="submit" class="twostars-home-proof__review-submit"><?php echo esc_html((string) ($settings['leave_review_label'] ?? __('Leave a Review', 'twostars-elementor-widgets'))); ?></button>
							<p class="twostars-home-proof__review-status" data-twostars-review-status></p>
						</div>
					</form>
					<div class="twostars-home-proof__thankyou" hidden data-twostars-review-thankyou>
						<p class="twostars-home-proof__modal-eyebrow"><?php esc_html_e('Thank You', 'twostars-elementor-widgets'); ?></p>
						<h3><?php esc_html_e('Your review has been sent to the Two Stars team.', 'twostars-elementor-widgets'); ?></h3>
						<p><?php esc_html_e('We review each submission before it goes live so the feedback on the site stays clear, genuine, and helpful to future guests.', 'twostars-elementor-widgets'); ?></p>
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
		if (($settings['use_latest_testimonial'] ?? 'yes') !== 'yes') {
			return [$this->fallback_review($settings)];
		}

		$posts = TourContent::get_reviews(['posts_per_page' => 4]);

		if ($posts === []) {
			return [$this->fallback_review($settings)];
		}

		usort(
			$posts,
			static function (\WP_Post $left, \WP_Post $right): int {
				$left_featured = (string) get_post_meta($left->ID, '_twostars_review_featured', true) === 'featured' ? 1 : 0;
				$right_featured = (string) get_post_meta($right->ID, '_twostars_review_featured', true) === 'featured' ? 1 : 0;

				if ($left_featured !== $right_featured) {
					return $right_featured <=> $left_featured;
				}

				return strtotime((string) $right->post_date) <=> strtotime((string) $left->post_date);
			}
		);

		$reviews = [];

		foreach ($posts as $post) {
			$quote = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 32);
			$author = $post->post_title;
			$rating = (int) get_post_meta($post->ID, '_twostars_review_rating', true);
			$trip = (string) get_post_meta($post->ID, '_twostars_review_trip_label', true);
			$location = (string) get_post_meta($post->ID, '_twostars_review_guest_location', true);
			$meta = implode(' • ', array_filter([$trip, $location]));

			$reviews[] = [
				'quote'  => $quote !== '' ? $quote : (string) ($settings['quote'] ?? ''),
				'author' => $author !== '' ? $author : (string) ($settings['author'] ?? ''),
				'meta'   => $meta !== '' ? $meta : (string) ($settings['meta'] ?? ''),
				'image'  => get_the_post_thumbnail_url($post, 'large') ?: '',
				'rating' => $rating > 0 ? $rating : 5,
			];
		}

		return $reviews !== [] ? $reviews : [$this->fallback_review($settings)];
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<string, string|int>
	 */
	private function fallback_review(array $settings): array
	{
		return [
			'quote'  => (string) ($settings['quote'] ?? __('Everything felt easy from pickup to the final stop. We saw the lagoon, the dunes, and the coastline without ever feeling rushed.', 'twostars-elementor-widgets')),
			'author' => (string) ($settings['author'] ?? __('Two Stars Guest', 'twostars-elementor-widgets')),
			'meta'   => (string) ($settings['meta'] ?? __('Walvis Bay and Sandwich Harbour route', 'twostars-elementor-widgets')),
			'image'  => '',
			'rating' => 5,
		];
	}

	/**
	 * @param array<string, mixed> $settings
	 * @param array<string, string|int> $testimonial
	 */
	private function resolve_quote_image(array $settings, array $testimonial): string
	{
		$image = (string) (($settings['quote_image']['url'] ?? '') ?: '');

		if ($image !== '') {
			return $image;
		}

		if (! empty($testimonial['image'])) {
			return (string) $testimonial['image'];
		}

		return content_url('uploads/2026/03/WhatsApp-Image-2026-03-27-at-5.55.41-AM-2-1.jpeg');
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

	private function resolve_display_copy(string $value, string $legacy, string $replacement): string
	{
		$value = trim($value);

		if ($value === '' || $value === $legacy) {
			return $replacement;
		}

		return $value;
	}
}
