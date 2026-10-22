<?php
declare(strict_types=1);

use TwoStars\Platform\Support\TourData;
use TwoStars\ElementorWidgets\Support\TourContent;

get_header();
?>
<section class="twostars-tour-entry">
	<?php while (have_posts()) : ?>
		<?php
		the_post();

		$tour_id           = get_the_ID();
		$tour_post         = get_post($tour_id);
		$summary_items     = class_exists(TourData::class) ? TourData::get_summary_items($tour_id) : [];
		$tour_meta         = class_exists(TourData::class) ? TourData::get_tour_meta($tour_id) : [];
		$booking_url       = class_exists(TourData::class) ? TourData::get_booking_url($tour_id) : home_url('/contact/');
		$booking_label     = class_exists(TourData::class) ? TourData::get_booking_label($tour_id) : __('Book This Tour', 'twostars-theme');
		$is_calendly       = class_exists(TourData::class) && TourData::is_calendly_booking($tour_id);
		$booking_target    = twostars_theme_is_external_url($booking_url);
		$whatsapp_url      = twostars_theme_get_whatsapp_url();
		$excerpt           = get_the_excerpt();
		$excerpt           = $excerpt !== '' ? wp_strip_all_tags($excerpt) : twostars_theme_get_post_excerpt($tour_post instanceof WP_Post ? $tour_post : get_post(), 28);
		$featured_image_id = get_post_thumbnail_id($tour_id);
		$featured_image    = $featured_image_id > 0 ? (string) wp_get_attachment_image_url($featured_image_id, 'full') : '';
		$linked_ids        = is_array($tour_meta['linked_destinations'] ?? null) ? array_values(array_filter(array_map('absint', (array) $tour_meta['linked_destinations']))) : [];
		$linked_destinations = array_values(
			array_filter(
				array_map(static fn(int $id): ?WP_Post => get_post($id), $linked_ids),
				static fn($post): bool => $post instanceof WP_Post && $post->post_status === 'publish'
			)
		);
		$other_tours = array_values(
			array_filter(
				twostars_theme_get_posts_by_type('tour', 6),
				static fn(WP_Post $post): bool => $post->ID !== $tour_id
			)
		);
		$includes = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($tour_meta['includes'] ?? '')) ?: [])));
		$excludes = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($tour_meta['excludes'] ?? '')) ?: [])));
		$gallery_ids = twostars_theme_get_tour_gallery_ids($tour_id);
		?>
		<article <?php post_class('twostars-tour-entry__layout'); ?>>
			<header
				class="twostars-tour-entry__masthead"
				<?php if ($featured_image !== '') : ?>
					style="--twostars-tour-entry-image:url('<?php echo esc_url($featured_image); ?>');"
				<?php endif; ?>
			>
				<div class="twostars-tour-entry__masthead-media" aria-hidden="true"></div>
				<div class="twostars-tour-entry__masthead-overlay" aria-hidden="true"></div>

				<div class="site-shell twostars-tour-entry__masthead-shell">
					<div class="twostars-tour-entry__masthead-copy">
						<div class="twostars-tour-entry__eyebrow-wrap">
							<span class="twostars-tour-entry__eyebrow-line"></span>
							<p class="twostars-tour-entry__eyebrow"><?php esc_html_e('Two Stars Tour', 'twostars-theme'); ?></p>
						</div>

						<h1 class="twostars-tour-entry__masthead-title"><?php the_title(); ?></h1>
						<p class="twostars-tour-entry__masthead-excerpt"><?php echo esc_html($excerpt); ?></p>

						<div class="twostars-tour-entry__masthead-meta">
							<?php if ($summary_items !== []) : ?>
								<?php foreach (array_slice($summary_items, 0, 3) as $item) : ?>
									<span><?php echo esc_html($item['label'] . ': ' . $item['value']); ?></span>
								<?php endforeach; ?>
							<?php else : ?>
								<span><?php esc_html_e('Walvis Bay / Swakopmund departures', 'twostars-theme'); ?></span>
							<?php endif; ?>
						</div>

						<div class="twostars-tour-entry__masthead-actions">
							<a
								class="twostars-tour-entry__button twostars-tour-entry__button--primary<?php echo $is_calendly ? ' twostars-calendly-trigger' : ''; ?>"
								href="<?php echo esc_url($booking_url); ?>"
								<?php if ($is_calendly) : ?>
									data-calendly-url="<?php echo esc_url($booking_url); ?>"
								<?php endif; ?>
								<?php if ($booking_target) : ?>
									target="_blank" rel="noopener noreferrer"
								<?php endif; ?>
							>
								<?php echo esc_html((string) $booking_label); ?>
							</a>
							<a class="twostars-tour-entry__button twostars-tour-entry__button--secondary" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e('Ask On WhatsApp', 'twostars-theme'); ?>
							</a>
						</div>
					</div>

					<div class="twostars-tour-entry__masthead-panel">
						<p class="twostars-tour-entry__panel-label"><?php esc_html_e('Why Guests Book This', 'twostars-theme'); ?></p>
						<p class="twostars-tour-entry__panel-copy"><?php esc_html_e('Two Stars tours are designed around route flow, scenic rhythm, and the stops that make the day feel complete from pickup to return.', 'twostars-theme'); ?></p>

						<div class="twostars-tour-entry__panel-stats">
							<div class="twostars-tour-entry__panel-stat">
								<strong><?php echo esc_html((string) max(1, count($linked_destinations))); ?></strong>
								<span><?php esc_html_e('Key stop', 'twostars-theme'); ?></span>
							</div>
							<div class="twostars-tour-entry__panel-stat">
								<strong><?php echo esc_html($tour_meta['pickup_location'] !== '' ? (string) $tour_meta['pickup_location'] : __('Coastal', 'twostars-theme')); ?></strong>
								<span><?php esc_html_e('Departure zone', 'twostars-theme'); ?></span>
							</div>
							<div class="twostars-tour-entry__panel-stat">
								<strong><?php echo esc_html($tour_meta['availability'] !== '' ? TourData::get_availability_label($tour_id) : __('Private', 'twostars-theme')); ?></strong>
								<span><?php esc_html_e('Booking style', 'twostars-theme'); ?></span>
							</div>
						</div>

						<?php if (! empty($tour_meta['booking_notes'])) : ?>
							<p class="twostars-tour-entry__panel-note"><?php echo esc_html((string) $tour_meta['booking_notes']); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</header>

			<div class="site-shell">
				<div class="twostars-tour-entry__content">
					<div class="twostars-tour-entry__main">
						<section class="twostars-article__body twostars-tour-entry__body">
							<div class="twostars-tour-entry__body-intro">
								<p class="twostars-tour-entry__body-label"><?php esc_html_e('Tour Overview', 'twostars-theme'); ?></p>
								<h2 class="twostars-tour-entry__body-title"><?php esc_html_e('What this tour includes', 'twostars-theme'); ?></h2>
							</div>

							<div class="twostars-tour-entry__body-content">
								<?php the_content(); ?>
							</div>
						</section>

						<?php if ($gallery_ids !== []) : ?>
							<section class="twostars-tour-entry__gallery" data-twostars-lightbox-gallery>
								<div class="twostars-tour-entry__section-head">
									<div>
										<p class="twostars-tour-entry__section-label"><?php esc_html_e('Gallery', 'twostars-theme'); ?></p>
										<h2 class="twostars-tour-entry__section-title"><?php esc_html_e('Inside the tour', 'twostars-theme'); ?></h2>
									</div>
								</div>

								<div class="twostars-tour-entry__gallery-grid<?php echo count($gallery_ids) === 1 ? ' twostars-tour-entry__gallery-grid--single' : ''; ?>">
									<?php foreach (array_slice($gallery_ids, 0, 5) as $index => $image_id) : ?>
										<figure class="twostars-tour-entry__gallery-item twostars-tour-entry__gallery-item--<?php echo esc_attr((string) ($index + 1)); ?>">
											<?php
											$full_image_url = (string) wp_get_attachment_image_url($image_id, 'full');
											$image_alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
											$image_label = $image_alt !== '' ? $image_alt : get_the_title($image_id);
											?>
											<button
												class="twostars-gallery-tile"
												type="button"
												data-twostars-lightbox-item
												data-twostars-lightbox-src="<?php echo esc_url($full_image_url); ?>"
												data-twostars-lightbox-alt="<?php echo esc_attr($image_label); ?>"
												data-twostars-lightbox-caption="<?php echo esc_attr($image_label); ?>"
												aria-label="<?php echo esc_attr(sprintf(__('Open image %d in gallery', 'twostars-theme'), $index + 1)); ?>"
											>
												<?php echo wp_get_attachment_image($image_id, 'large', false, ['loading' => 'lazy']); ?>
												<span class="twostars-gallery-tile__hint"><?php esc_html_e('View', 'twostars-theme'); ?></span>
											</button>
										</figure>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ($summary_items !== []) : ?>
							<section class="twostars-tour-entry__details">
								<div class="twostars-tour-entry__section-head">
									<div>
										<p class="twostars-tour-entry__section-label"><?php esc_html_e('Trip Details', 'twostars-theme'); ?></p>
										<h2 class="twostars-tour-entry__section-title"><?php esc_html_e('Before you book', 'twostars-theme'); ?></h2>
									</div>
								</div>
								<div class="twostars-tour-entry__detail-grid">
									<?php foreach ($summary_items as $item) : ?>
										<article class="twostars-tour-entry__detail-card">
											<p class="twostars-tour-entry__detail-label"><?php echo esc_html((string) $item['label']); ?></p>
											<h3><?php echo esc_html((string) $item['value']); ?></h3>
										</article>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ($linked_destinations !== []) : ?>
							<section class="twostars-tour-entry__destinations">
								<div class="twostars-tour-entry__section-head">
									<div>
										<p class="twostars-tour-entry__section-label"><?php esc_html_e('Tour Stops', 'twostars-theme'); ?></p>
										<h2 class="twostars-tour-entry__section-title"><?php esc_html_e('The destinations that shape this route.', 'twostars-theme'); ?></h2>
									</div>
									
								</div>

								<div class="twostars-editorial-grid twostars-tour-entry__destination-grid">
									<?php foreach ($linked_destinations as $destination_post) : ?>
										<?php $destination_image = get_post_thumbnail_id($destination_post->ID); ?>
										<article class="twostars-editorial-card twostars-tour-entry__destination-card">
											<div class="twostars-editorial-card__media">
												<?php if ($destination_image > 0) : ?>
													<?php echo wp_get_attachment_image($destination_image, 'large', false, ['loading' => 'lazy']); ?>
												<?php else : ?>
													<div class="twostars-tour-entry__placeholder"></div>
												<?php endif; ?>
											</div>
											<div class="twostars-editorial-card__body">
												<p class="twostars-editorial-card__type"><?php esc_html_e('Destination Stop', 'twostars-theme'); ?></p>
												<h3 class="twostars-editorial-card__title"><?php echo esc_html(get_the_title($destination_post)); ?></h3>
												<p class="twostars-editorial-card__excerpt"><?php echo esc_html(twostars_theme_get_post_excerpt($destination_post, 18)); ?></p>
												<a class="twostars-editorial-card__link" href="<?php echo esc_url(get_permalink($destination_post)); ?>">
													<?php esc_html_e('View Destination', 'twostars-theme'); ?>
												</a>
											</div>
										</article>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ($other_tours !== []) : ?>
							<section class="twostars-tour-entry__nearby">
								<div class="twostars-tour-entry__section-head">
									<div>
										<p class="twostars-tour-entry__section-label"><?php esc_html_e('More Tours', 'twostars-theme'); ?></p>
										<h2 class="twostars-tour-entry__section-title"><?php esc_html_e('Compare this route with the rest of the Two Stars collection.', 'twostars-theme'); ?></h2>
									</div>
								</div>

								<div class="twostars-tours-grid twostars-tour-entry__nearby-grid">
									<?php foreach (array_slice($other_tours, 0, 3) as $index => $other_tour) : ?>
										<?php
										$image_id          = get_post_thumbnail_id($other_tour->ID);
										$summary_items     = class_exists(TourData::class) ? TourData::get_summary_items($other_tour->ID) : [];
										$availability      = class_exists(TourData::class) ? TourData::get_availability_label($other_tour->ID) : '';
										$destination_ids   = class_exists(TourContent::class) ? TourContent::get_linked_destination_ids($other_tour->ID) : [];
										$booking_url       = class_exists(TourData::class) ? TourData::get_booking_url($other_tour->ID) : home_url('/booking/');
										$booking_label     = class_exists(TourData::class) ? TourData::get_booking_label($other_tour->ID) : __('Book This Tour', 'twostars-theme');
										?>
										<article
											class="twostars-tours-grid__card"
											data-twostars-animate="fade-in-up"
											style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.14, 2)); ?>s;"
										>
											<div class="twostars-tours-grid__media">
												<?php if ($image_id > 0) : ?>
													<?php echo wp_get_attachment_image($image_id, 'large', false, ['loading' => 'lazy']); ?>
												<?php else : ?>
													<div class="twostars-tours-grid__placeholder"></div>
												<?php endif; ?>
												<div class="twostars-tours-grid__media-overlay"></div>
												<?php if ($availability !== '') : ?>
													<span class="twostars-tours-grid__badge"><?php echo esc_html($availability); ?></span>
												<?php endif; ?>
											</div>
											<div class="twostars-tours-grid__body">
												<p class="twostars-tours-grid__eyebrow"><?php esc_html_e('Related Tour', 'twostars-theme'); ?></p>
												<h3 class="twostars-tours-grid__title">
													<a href="<?php echo esc_url(get_permalink($other_tour)); ?>"><?php echo esc_html(get_the_title($other_tour)); ?></a>
												</h3>
												<p class="twostars-tours-grid__excerpt"><?php echo esc_html(twostars_theme_get_post_excerpt($other_tour, 18)); ?></p>
												
												<?php if ($destination_ids !== []) : ?>
													<div class="twostars-tours-grid__destinations">
														<?php foreach (array_slice($destination_ids, 0, 3) as $destination_id) : ?>
															<?php $destination = get_post($destination_id); ?>
															<?php if ($destination instanceof WP_Post) : ?>
																<a class="twostars-tours-grid__destination-chip" href="<?php echo esc_url(get_permalink($destination)); ?>">
																	<?php echo esc_html($destination->post_title); ?>
																</a>
															<?php endif; ?>
														<?php endforeach; ?>
													</div>
												<?php endif; ?>

												<?php if ($summary_items !== []) : ?>
													<ul class="twostars-tours-grid__meta">
														<?php foreach (array_slice($summary_items, 0, 4) as $item) : ?>
															<li>
																<span><?php echo esc_html($item['label']); ?></span>
																<strong><?php echo esc_html($item['value']); ?></strong>
															</li>
														<?php endforeach; ?>
													</ul>
												<?php endif; ?>

												<div class="twostars-tours-grid__actions">
													<a class="twostars-safari-button twostars-safari-button--primary" href="<?php echo esc_url(get_permalink($other_tour)); ?>">
														<?php esc_html_e('Explore Tour', 'twostars-theme'); ?>
													</a>
													<a class="twostars-tours-grid__secondary-link" href="<?php echo esc_url($booking_url); ?>"<?php echo str_contains($booking_url, 'calendly.com/') ? ' target="_blank" rel="noopener"' : ''; ?>>
														<?php echo esc_html($booking_label); ?>
													</a>
												</div>
											</div>
										</article>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>
					</div>

					<aside class="twostars-tour-entry__sidebar">
						<?php if ($summary_items !== []) : ?>
							<section class="twostars-article__sidebar-card twostars-tour-entry__sidebar-card">
								<p class="twostars-article__sidebar-label"><?php esc_html_e('Trip Snapshot', 'twostars-theme'); ?></p>
								<ul class="twostars-tour-entry__summary-list">
									<?php foreach ($summary_items as $item) : ?>
										<li>
											<span><?php echo esc_html($item['label']); ?></span>
											<strong><?php echo esc_html($item['value']); ?></strong>
										</li>
									<?php endforeach; ?>
								</ul>
							</section>
						<?php endif; ?>

						<?php if ($includes !== []) : ?>
							<section class="twostars-article__sidebar-card twostars-tour-entry__sidebar-card">
								<p class="twostars-article__sidebar-label"><?php esc_html_e('Included', 'twostars-theme'); ?></p>
								<ul class="twostars-tour-entry__bullet-list">
									<?php foreach ($includes as $item) : ?>
										<li><?php echo esc_html($item); ?></li>
									<?php endforeach; ?>
								</ul>
							</section>
						<?php endif; ?>

						<?php if ($excludes !== []) : ?>
							<section class="twostars-article__sidebar-card twostars-tour-entry__sidebar-card">
								<p class="twostars-article__sidebar-label"><?php esc_html_e('Not Included', 'twostars-theme'); ?></p>
								<ul class="twostars-tour-entry__bullet-list">
									<?php foreach ($excludes as $item) : ?>
										<li><?php echo esc_html($item); ?></li>
									<?php endforeach; ?>
								</ul>
							</section>
						<?php endif; ?>

						<section class="twostars-article__sidebar-card twostars-tour-entry__sidebar-card twostars-tour-entry__sidebar-card--cta">
							<p class="twostars-article__sidebar-label"><?php esc_html_e('Need Help Choosing?', 'twostars-theme'); ?></p>
							<h2><?php esc_html_e('We can recommend the right tour for your pace and interests.', 'twostars-theme'); ?></h2>
							<p><?php esc_html_e('Tell Two Stars whether you want Sandwich Harbour, lagoon birdlife, town landmarks, Spitzkoppe, or a private day built around your group.', 'twostars-theme'); ?></p>
							<a
								class="twostars-tour-entry__button twostars-tour-entry__button--primary<?php echo $is_calendly ? ' twostars-calendly-trigger' : ''; ?>"
								href="<?php echo esc_url($booking_url); ?>"
								<?php if ($is_calendly) : ?>
									data-calendly-url="<?php echo esc_url($booking_url); ?>"
								<?php endif; ?>
								<?php if ($booking_target) : ?>
									target="_blank" rel="noopener noreferrer"
								<?php endif; ?>
							>
								<?php echo esc_html((string) $booking_label); ?>
							</a>
						</section>
					</aside>
				</div>
			</div>
		</article>
	<?php endwhile; ?>
</section>
<?php
get_footer();
