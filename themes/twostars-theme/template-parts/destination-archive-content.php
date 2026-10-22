<?php
/**
 * Shared destinations archive/page content.
 *
 * @package twostars-theme
 */

declare(strict_types=1);
$archive_copy      = twostars_theme_get_archive_copy('destination');
$archive_eyebrow   = (string) ($archive_copy['eyebrow'] ?? __('Places Along The Way', 'twostars-theme'));
$archive_title     = (string) ($archive_copy['title'] ?? __('Destinations & Stops', 'twostars-theme'));
$archive_text      = (string) ($archive_copy['description'] ?? '');
$destination_posts = twostars_theme_get_posts_by_type('destination', -1);

$destination_order = [
	'Sandwich Harbour',
	'Walvis Bay Lagoon',
	'Pink Salt Lake',
	'Swakopmund',
	'Dune 7',
	'Spitzkoppe',
];

$destination_order_map = array_flip($destination_order);

usort(
	$destination_posts,
	static function (WP_Post $left, WP_Post $right) use ($destination_order_map): int {
		$left_index  = $destination_order_map[$left->post_title] ?? 999;
		$right_index = $destination_order_map[$right->post_title] ?? 999;

		if ($left_index === $right_index) {
			return strcasecmp($left->post_title, $right->post_title);
		}

		return $left_index <=> $right_index;
	}
);

$hero_image_url = '';

foreach ($destination_posts as $destination_post) {
	$hero_image_id = get_post_thumbnail_id($destination_post->ID);

	if ($hero_image_id > 0) {
		$hero_image_url = (string) wp_get_attachment_image_url($hero_image_id, 'full');
		break;
	}
}

$featured_destinations = array_slice($destination_posts, 0, 3);
$destination_count     = count($destination_posts);
$tour_counts           = wp_count_posts('tour');
$tour_count            = isset($tour_counts->publish) ? (int) $tour_counts->publish : 0;
$tour_archive_url      = home_url('/tours/');
$whatsapp_url          = twostars_theme_get_whatsapp_url();
$booking_url           = home_url('/booking/');
$booking_is_external   = twostars_theme_is_external_url($booking_url);
?>

<section id="primary" class="site-main twostars-destination-archive">
	<section
		class="twostars-destination-archive__hero"
		<?php if ($hero_image_url !== '') : ?>
			style="--twostars-destination-hero-image:url('<?php echo esc_url($hero_image_url); ?>');"
		<?php endif; ?>
	>
		<div class="twostars-destination-archive__hero-media" aria-hidden="true"></div>
		<div class="twostars-destination-archive__hero-overlay" aria-hidden="true"></div>

		<div class="site-shell twostars-destination-archive__hero-shell">
			<div class="twostars-destination-archive__hero-copy">
				<div class="twostars-destination-archive__eyebrow-wrap" data-twostars-animate="fade-in-up">
					<span class="twostars-destination-archive__eyebrow-line"></span>
					<p class="twostars-destination-archive__eyebrow"><?php echo esc_html($archive_eyebrow); ?></p>
				</div>

				<h1 class="twostars-destination-archive__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.12s;">
					<?php echo esc_html($archive_title); ?>
				</h1>

				<p class="twostars-destination-archive__intro" data-twostars-animate="fade-in-up" style="--twostars-delay:0.22s;">
					<?php echo esc_html($archive_text !== '' ? $archive_text : __('Browse the places featured across the current Two Stars routes from Walvis Bay and Swakopmund.', 'twostars-theme')); ?>
				</p>

				<div class="twostars-destination-archive__hero-actions" data-twostars-animate="fade-in-up" style="--twostars-delay:0.32s;">
					<a class="twostars-destination-archive__button twostars-destination-archive__button--primary" href="#destination-grid">
						<?php esc_html_e('Explore The Stops', 'twostars-theme'); ?>
					</a>
					<a
						class="twostars-destination-archive__button twostars-destination-archive__button--secondary"
						href="<?php echo esc_url($booking_url); ?>"
						<?php if ($booking_is_external) : ?>
							target="_blank" rel="noopener noreferrer"
						<?php endif; ?>
					>
						<?php esc_html_e('Plan Your Route', 'twostars-theme'); ?>
					</a>
				</div>
			</div>

			<div class="twostars-destination-archive__hero-sidebar" data-twostars-animate="fade-in-up" style="--twostars-delay:0.18s;">
				<p class="twostars-destination-archive__sidebar-text">
					<?php esc_html_e('These are the main coastal and desert stops used across the current Two Stars tours.', 'twostars-theme'); ?>
				</p>

				<div class="twostars-destination-archive__stats">
					<div class="twostars-destination-archive__stat">
						<strong><?php echo esc_html((string) $destination_count); ?></strong>
						<span><?php esc_html_e('Destinations', 'twostars-theme'); ?></span>
					</div>
					<div class="twostars-destination-archive__stat">
						<strong><?php echo esc_html((string) $tour_count); ?></strong>
						<span><?php esc_html_e('Live Tours', 'twostars-theme'); ?></span>
					</div>
					<div class="twostars-destination-archive__stat">
						<strong><?php esc_html_e('Walvis Bay', 'twostars-theme'); ?></strong>
						<span><?php esc_html_e('Operating Base', 'twostars-theme'); ?></span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="twostars-destination-archive__story">
		<div class="site-shell">
			<div class="twostars-destination-archive__story-header">
				<div>
					<p class="twostars-destination-archive__section-label"><?php esc_html_e('Why Guests Remember These Places', 'twostars-theme'); ?></p>
					<h2 class="twostars-destination-archive__section-title"><?php esc_html_e('Stops that define the route', 'twostars-theme'); ?></h2>
				</div>

				<p class="twostars-destination-archive__section-text">
					<?php esc_html_e('Each stop adds a different part of the day, from lagoon birdlife and salt pans to dune landscapes, town landmarks, and longer inland scenery.', 'twostars-theme'); ?>
				</p>
			</div>

			<?php if ($featured_destinations !== []) : ?>
				<div class="twostars-destination-archive__story-grid">
					<?php foreach ($featured_destinations as $index => $destination_post) : ?>
						<?php
						$card_image_id = get_post_thumbnail_id($destination_post->ID);
						$related_tours = twostars_theme_get_destination_related_tours($destination_post->ID, 2);
						$card_delay    = number_format($index * 0.12, 2);
						$card_excerpt  = twostars_theme_get_post_excerpt($destination_post, 22);
						?>
						<article class="twostars-destination-archive__story-card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) $card_delay); ?>s;">
							<div class="twostars-destination-archive__story-media">
								<?php if ($card_image_id > 0) : ?>
									<?php echo wp_get_attachment_image($card_image_id, 'large', false, ['loading' => 'lazy']); ?>
								<?php else : ?>
									<div class="twostars-destination-archive__story-placeholder"></div>
								<?php endif; ?>
							</div>

							<div class="twostars-destination-archive__story-body">
								<p class="twostars-destination-archive__story-kicker"><?php esc_html_e('Featured Stop', 'twostars-theme'); ?></p>
								<h3 class="twostars-destination-archive__story-title">
									<a href="<?php echo esc_url(get_permalink($destination_post)); ?>"><?php echo esc_html(get_the_title($destination_post)); ?></a>
								</h3>
								<p class="twostars-destination-archive__story-excerpt"><?php echo esc_html($card_excerpt); ?></p>

								<?php if ($related_tours !== []) : ?>
									<div class="twostars-destination-archive__story-links">
										<span><?php esc_html_e('Featured in', 'twostars-theme'); ?></span>
										<?php foreach ($related_tours as $tour_id) : ?>
											<a href="<?php echo esc_url(get_permalink($tour_id)); ?>"><?php echo esc_html(get_the_title($tour_id)); ?></a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section id="destination-grid" class="twostars-destination-archive__collection">
		<div class="site-shell">
			<div class="twostars-destination-archive__section-head">
				<div>
					<p class="twostars-destination-archive__section-label"><?php esc_html_e('Destination Collection', 'twostars-theme'); ?></p>
					<h2 class="twostars-destination-archive__section-title"><?php esc_html_e('Browse all destinations', 'twostars-theme'); ?></h2>
				</div>
				<p class="twostars-destination-archive__section-text">
					<?php esc_html_e('Open each destination to see what it offers and which tours include it.', 'twostars-theme'); ?>
				</p>
			</div>

			<?php if ($destination_posts !== []) : ?>
				<div class="twostars-destination-archive__grid">
					<?php foreach ($destination_posts as $index => $destination_post) : ?>
						<?php
						$destination_id = $destination_post->ID;
						$image_id       = get_post_thumbnail_id($destination_id);
						$excerpt        = twostars_theme_get_post_excerpt($destination_post, 26);
						$related_tours  = twostars_theme_get_destination_related_tours($destination_id, 3);
						$grid_delay     = number_format(($index % 3) * 0.12, 2);
						?>
						<article class="twostars-destination-archive__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) $grid_delay); ?>s;">
							<div class="twostars-destination-archive__card-media">
								<?php if ($image_id > 0) : ?>
									<?php echo wp_get_attachment_image($image_id, 'large', false, ['loading' => 'lazy']); ?>
								<?php else : ?>
									<div class="twostars-destination-archive__card-placeholder"></div>
								<?php endif; ?>
								<div class="twostars-destination-archive__card-shade" aria-hidden="true"></div>
							</div>

							<div class="twostars-destination-archive__card-body">
								<p class="twostars-destination-archive__card-kicker"><?php esc_html_e('Two Stars Stop', 'twostars-theme'); ?></p>
								<h3 class="twostars-destination-archive__card-title">
									<a href="<?php echo esc_url(get_permalink($destination_post)); ?>"><?php echo esc_html(get_the_title($destination_post)); ?></a>
								</h3>
								<p class="twostars-destination-archive__card-excerpt"><?php echo esc_html($excerpt); ?></p>

								<?php if ($related_tours !== []) : ?>
									<div class="twostars-destination-archive__card-related">
										<span><?php esc_html_e('Best experienced on', 'twostars-theme'); ?></span>
										<div class="twostars-destination-archive__card-related-links">
											<?php foreach ($related_tours as $tour_id) : ?>
												<a href="<?php echo esc_url(get_permalink($tour_id)); ?>"><?php echo esc_html(get_the_title($tour_id)); ?></a>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>

								<div class="twostars-destination-archive__card-actions">
									<a class="twostars-destination-archive__card-link" href="<?php echo esc_url(get_permalink($destination_post)); ?>">
										<?php esc_html_e('View Destination', 'twostars-theme'); ?>
									</a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="twostars-destination-archive__empty">
					<p><?php esc_html_e('No destinations are available yet.', 'twostars-theme'); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="twostars-destination-archive__cta">
		<div class="site-shell twostars-destination-archive__cta-shell">
			<div class="twostars-destination-archive__cta-content" data-twostars-animate="fade-in-up">
				<p class="twostars-destination-archive__section-label"><?php esc_html_e('Ready To Build Your Route?', 'twostars-theme'); ?></p>
				<h2 class="twostars-destination-archive__cta-title"><?php esc_html_e('Need help choosing the right route?', 'twostars-theme'); ?></h2>
				<p class="twostars-destination-archive__cta-text">
					<?php esc_html_e('Tell us what kind of day you want and we will point you to the right tour.', 'twostars-theme'); ?>
				</p>

				<div class="twostars-destination-archive__cta-actions">
					<a
						class="twostars-destination-archive__button twostars-destination-archive__button--primary"
						href="<?php echo esc_url($booking_url); ?>"
						<?php if ($booking_is_external) : ?>
							target="_blank" rel="noopener noreferrer"
						<?php endif; ?>
					>
						<?php esc_html_e('Book With Two Stars', 'twostars-theme'); ?>
					</a>
					<a class="twostars-destination-archive__button twostars-destination-archive__button--secondary" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e('Chat On WhatsApp', 'twostars-theme'); ?>
					</a>
					<a class="twostars-destination-archive__text-link" href="<?php echo esc_url($tour_archive_url); ?>">
						<?php esc_html_e('Browse all tours', 'twostars-theme'); ?>
					</a>
				</div>
			</div>
		</div>
	</section>
</section>
