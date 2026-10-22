<?php
declare(strict_types=1);

get_header();
?>
<section class="twostars-destination-entry">
	<?php while (have_posts()) : ?>
		<?php
		the_post();

		$destination_id       = get_the_ID();
		$destination_post     = get_post($destination_id);
		$destination_excerpt  = get_the_excerpt();
		$destination_excerpt  = $destination_excerpt !== ''
			? wp_strip_all_tags($destination_excerpt)
			: twostars_theme_get_post_excerpt($destination_post instanceof WP_Post ? $destination_post : get_post(), 28);
		$featured_image_id    = get_post_thumbnail_id($destination_id);
		$featured_image_url   = $featured_image_id > 0 ? (string) wp_get_attachment_image_url($featured_image_id, 'full') : '';
		$destination_meta     = twostars_theme_get_destination_meta($destination_id);
		$gallery_ids          = twostars_theme_get_destination_gallery_ids($destination_id);
		$related_tour_posts   = twostars_theme_get_destination_related_tours($destination_id, 3);
		$other_destinations   = array_values(
			array_filter(
				twostars_theme_get_posts_by_type('destination', 6),
				static fn(WP_Post $post): bool => $post->ID !== $destination_id
			)
		);
		$whatsapp_url         = twostars_theme_get_whatsapp_url();
		$booking_url          = home_url('/booking/');
		$booking_target       = twostars_theme_is_external_url($booking_url);
		$related_tour_count   = count($related_tour_posts);
		$highlight_items      = twostars_theme_split_lines((string) ($destination_meta['highlights'] ?? ''));
		$best_for_items       = twostars_theme_split_lines((string) ($destination_meta['best_for'] ?? ''));
		$destination_detail_items = [];

		if ($destination_meta['region'] !== '') {
			$destination_detail_items[] = [
				'label' => __('Region', 'twostars-theme'),
				'value' => (string) $destination_meta['region'],
			];
		}

		if ($destination_meta['visit_duration'] !== '') {
			$destination_detail_items[] = [
				'label' => __('Visit Style', 'twostars-theme'),
				'value' => (string) $destination_meta['visit_duration'],
			];
		}

		if ($best_for_items !== []) {
			$destination_detail_items[] = [
				'label' => __('Best For', 'twostars-theme'),
				'value' => $best_for_items[0],
			];
		}

		foreach ($highlight_items as $item) {
			$destination_detail_items[] = [
				'label' => __('Highlight', 'twostars-theme'),
				'value' => $item,
			];
		}

		if ($destination_detail_items === []) {
			$destination_detail_items = [
				[
					'label' => __('Route Fit', 'twostars-theme'),
					'value' => __('Works well as a scenic coastal stop from Walvis Bay or Swakopmund.', 'twostars-theme'),
				],
				[
					'label' => __('Guest Appeal', 'twostars-theme'),
					'value' => __('Strong views, clean photo moments, and a stop that adds shape to the full day.', 'twostars-theme'),
				],
			];
		}
		?>
		<article <?php post_class('twostars-destination-entry__layout'); ?>>
			<header
				class="twostars-destination-entry__masthead"
				<?php if ($featured_image_url !== '') : ?>
					style="--twostars-destination-entry-image:url('<?php echo esc_url($featured_image_url); ?>');"
				<?php endif; ?>
			>
				<div class="twostars-destination-entry__masthead-media" aria-hidden="true"></div>
				<div class="twostars-destination-entry__masthead-overlay" aria-hidden="true"></div>

				<div class="site-shell twostars-destination-entry__masthead-shell">
					<div class="twostars-destination-entry__masthead-copy">
						<div class="twostars-destination-entry__eyebrow-wrap">
							<span class="twostars-destination-entry__eyebrow-line"></span>
							<p class="twostars-destination-entry__eyebrow"><?php esc_html_e('Destination Guide', 'twostars-theme'); ?></p>
						</div>

						<h1 class="twostars-destination-entry__masthead-title"><?php the_title(); ?></h1>
						<p class="twostars-destination-entry__masthead-excerpt"><?php echo esc_html($destination_excerpt); ?></p>

						<div class="twostars-destination-entry__masthead-meta">
							<span><?php echo esc_html($destination_meta['region'] !== '' ? (string) $destination_meta['region'] : __('Walvis Bay / Swakopmund Route', 'twostars-theme')); ?></span>
							<?php if ($related_tour_count > 0) : ?>
								<span>
									<?php
									echo esc_html(
										sprintf(
											/* translators: %d: number of related tours */
											_n('%d related tour', '%d related tours', $related_tour_count, 'twostars-theme'),
											$related_tour_count
										)
									);
									?>
								</span>
							<?php else : ?>
								<span><?php esc_html_e('Scenic destination stop', 'twostars-theme'); ?></span>
							<?php endif; ?>
						</div>

						<div class="twostars-destination-entry__masthead-actions">
							<a
								class="twostars-destination-entry__button twostars-destination-entry__button--primary"
								href="<?php echo esc_url($booking_url); ?>"
								<?php if ($booking_target) : ?>
									target="_blank" rel="noopener noreferrer"
								<?php endif; ?>
							>
								<?php esc_html_e('Plan This Stop', 'twostars-theme'); ?>
							</a>
							<a class="twostars-destination-entry__button twostars-destination-entry__button--secondary" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e('Ask On WhatsApp', 'twostars-theme'); ?>
							</a>
						</div>
					</div>

					<div class="twostars-destination-entry__masthead-panel">
						<p class="twostars-destination-entry__panel-label"><?php esc_html_e('Why This Stop Works', 'twostars-theme'); ?></p>
						<p class="twostars-destination-entry__panel-copy"><?php esc_html_e('Two Stars builds routes around destinations that photograph well, flow naturally from Walvis Bay or Swakopmund, and turn a simple outing into a full coastal or desert experience.', 'twostars-theme'); ?></p>
						<div class="twostars-destination-entry__panel-stats">
							<div class="twostars-destination-entry__panel-stat">
								<strong><?php echo esc_html((string) max(1, $related_tour_count)); ?></strong>
								<span><?php esc_html_e('Suggested route', 'twostars-theme'); ?></span>
							</div>
							<div class="twostars-destination-entry__panel-stat">
								<strong><?php echo esc_html($destination_meta['visit_duration'] !== '' ? (string) $destination_meta['visit_duration'] : __('Scenic', 'twostars-theme')); ?></strong>
								<span><?php esc_html_e('Visit style', 'twostars-theme'); ?></span>
							</div>
							<div class="twostars-destination-entry__panel-stat">
								<strong><?php echo esc_html($destination_meta['best_for'] !== '' ? __('Best Fit', 'twostars-theme') : __('Photo', 'twostars-theme')); ?></strong>
								<span><?php echo esc_html($destination_meta['best_for'] !== '' ? (string) $destination_meta['best_for'] : __('Scenic focus', 'twostars-theme')); ?></span>
							</div>
						</div>
					</div>
				</div>
			</header>

			<div class="site-shell">
				<div class="twostars-article__content twostars-destination-entry__content">
					<div class="twostars-article__body twostars-destination-entry__body">
						<div class="twostars-destination-entry__body-intro">
							<p class="twostars-destination-entry__body-label"><?php esc_html_e('What To Expect Here', 'twostars-theme'); ?></p>
							<h2 class="twostars-destination-entry__body-title"><?php esc_html_e('Know the stop before you go', 'twostars-theme'); ?></h2>
						</div>

						<div class="twostars-destination-entry__body-content">
							<?php the_content(); ?>
						</div>

						<?php if ($gallery_ids !== []) : ?>
							<section class="twostars-destination-entry__gallery" data-twostars-lightbox-gallery>
								<div class="twostars-destination-entry__section-head">
									<div>
										<p class="twostars-destination-entry__section-label"><?php esc_html_e('Gallery', 'twostars-theme'); ?></p>
										<h2 class="twostars-destination-entry__section-title"><?php esc_html_e('At this destination', 'twostars-theme'); ?></h2>
									</div>
								</div>
								<div class="twostars-destination-entry__gallery-grid<?php echo count($gallery_ids) === 1 ? ' twostars-destination-entry__gallery-grid--single' : ''; ?>">
									<?php foreach (array_slice($gallery_ids, 0, 5) as $index => $image_id) : ?>
										<figure class="twostars-destination-entry__gallery-item twostars-destination-entry__gallery-item--<?php echo esc_attr((string) ($index + 1)); ?>">
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

						<?php if ($destination_detail_items !== []) : ?>
							<section class="twostars-destination-entry__details">
								<div class="twostars-destination-entry__section-head">
									<div>
										<p class="twostars-destination-entry__section-label"><?php esc_html_e('Destination Details', 'twostars-theme'); ?></p>
										<h2 class="twostars-destination-entry__section-title"><?php esc_html_e('Before you go', 'twostars-theme'); ?></h2>
									</div>
								</div>
								<div class="twostars-destination-entry__detail-grid">
									<?php foreach ($destination_detail_items as $item) : ?>
										<article class="twostars-destination-entry__detail-card">
											<p class="twostars-destination-entry__detail-label"><?php echo esc_html((string) $item['label']); ?></p>
											<h3><?php echo esc_html((string) $item['value']); ?></h3>
										</article>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>
					</div>

					<aside class="twostars-article__sidebar twostars-destination-entry__sidebar">
						<section class="twostars-article__sidebar-card twostars-destination-entry__sidebar-card">
							<p class="twostars-article__sidebar-label"><?php esc_html_e('Best For', 'twostars-theme'); ?></p>
							<ul class="twostars-destination-entry__bullet-list">
								<?php if ($best_for_items !== []) : ?>
									<?php foreach ($best_for_items as $item) : ?>
										<li><?php echo esc_html($item); ?></li>
									<?php endforeach; ?>
								<?php else : ?>
									<li><?php esc_html_e('Guests planning a scenic coastal or desert day from Walvis Bay or Swakopmund', 'twostars-theme'); ?></li>
									<li><?php esc_html_e('Photography-focused routes with natural stopping points and strong views', 'twostars-theme'); ?></li>
									<li><?php esc_html_e('Pairing one memorable place with the right Two Stars tour rather than generic sightseeing', 'twostars-theme'); ?></li>
								<?php endif; ?>
							</ul>
						</section>

						<?php if ($related_tour_posts !== []) : ?>
							<section class="twostars-article__sidebar-card twostars-destination-entry__sidebar-card">
								<p class="twostars-article__sidebar-label"><?php esc_html_e('Tours That Include This Stop', 'twostars-theme'); ?></p>
								<div class="twostars-destination-entry__sidebar-links">
									<?php foreach ($related_tour_posts as $tour_post) : ?>
										<a class="twostars-destination-entry__sidebar-link" href="<?php echo esc_url(get_permalink($tour_post)); ?>">
											<?php echo esc_html(get_the_title($tour_post)); ?>
										</a>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>

						<section class="twostars-article__sidebar-card twostars-destination-entry__sidebar-card twostars-destination-entry__sidebar-card--cta">
							<p class="twostars-article__sidebar-label"><?php esc_html_e('Need Help Choosing?', 'twostars-theme'); ?></p>
							<h2><?php esc_html_e('We can pair this destination with the right route.', 'twostars-theme'); ?></h2>
							<p><?php esc_html_e('Tell Two Stars whether you want dune driving, lagoon birdlife, a relaxed scenic outing, or a private day at your own pace.', 'twostars-theme'); ?></p>
							<a
								class="twostars-destination-entry__button twostars-destination-entry__button--primary"
								href="<?php echo esc_url($booking_url); ?>"
								<?php if ($booking_target) : ?>
									target="_blank" rel="noopener noreferrer"
								<?php endif; ?>
							>
								<?php esc_html_e('Book With Two Stars', 'twostars-theme'); ?>
							</a>
						</section>
					</aside>
				</div>

				<?php if ($related_tour_posts !== []) : ?>
					<section class="twostars-destination-entry__related">
						<div class="twostars-destination-entry__section-head">
							<div>
								<p class="twostars-destination-entry__section-label"><?php esc_html_e('Related Tours', 'twostars-theme'); ?></p>
								<h2 class="twostars-destination-entry__section-title"><?php esc_html_e('See this destination as part of the full Two Stars experience.', 'twostars-theme'); ?></h2>
							</div>
							<p class="twostars-destination-entry__section-text"><?php esc_html_e('These tours are the best fit if you want this stop woven into a stronger route, better pacing, and the kind of day that feels complete from pickup to return.', 'twostars-theme'); ?></p>
						</div>

						<div class="twostars-editorial-grid twostars-destination-entry__related-grid">
							<?php foreach ($related_tour_posts as $tour_post) : ?>
								<?php
								$tour_image = get_post_thumbnail_id($tour_post->ID);
								if (! $tour_post instanceof WP_Post) {
									continue;
								}
								?>
								<article class="twostars-editorial-card twostars-destination-entry__related-card">
									<div class="twostars-editorial-card__media">
										<?php if ($tour_image > 0) : ?>
											<?php echo wp_get_attachment_image($tour_image, 'large', false, ['loading' => 'lazy']); ?>
										<?php else : ?>
											<div class="twostars-destination-entry__placeholder"></div>
										<?php endif; ?>
									</div>
									<div class="twostars-editorial-card__body">
										<p class="twostars-editorial-card__type"><?php esc_html_e('Two Stars Tour', 'twostars-theme'); ?></p>
										<h3 class="twostars-editorial-card__title"><?php echo esc_html(get_the_title($tour_post)); ?></h3>
										<p class="twostars-editorial-card__excerpt"><?php echo esc_html(twostars_theme_get_post_excerpt($tour_post, 22)); ?></p>
										<a class="twostars-editorial-card__link" href="<?php echo esc_url(get_permalink($tour_post)); ?>">
											<?php esc_html_e('Explore Tour', 'twostars-theme'); ?>
										</a>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ($other_destinations !== []) : ?>
					<section class="twostars-destination-entry__nearby">
						<div class="twostars-destination-entry__section-head">
							<div>
								<p class="twostars-destination-entry__section-label"><?php esc_html_e('More Stops', 'twostars-theme'); ?></p>
								<h2 class="twostars-destination-entry__section-title"><?php esc_html_e('Explore more places on the Two Stars map.', 'twostars-theme'); ?></h2>
							</div>
						</div>

						<div class="twostars-editorial-grid twostars-destination-entry__nearby-grid">
							<?php foreach (array_slice($other_destinations, 0, 3) as $nearby_destination) : ?>
								<?php $nearby_image = get_post_thumbnail_id($nearby_destination->ID); ?>
								<article class="twostars-editorial-card twostars-destination-entry__nearby-card">
									<div class="twostars-editorial-card__media">
										<?php if ($nearby_image > 0) : ?>
											<?php echo wp_get_attachment_image($nearby_image, 'large', false, ['loading' => 'lazy']); ?>
										<?php else : ?>
											<div class="twostars-destination-entry__placeholder"></div>
										<?php endif; ?>
									</div>
									<div class="twostars-editorial-card__body">
										<p class="twostars-editorial-card__type"><?php esc_html_e('Destination Stop', 'twostars-theme'); ?></p>
										<h3 class="twostars-editorial-card__title"><?php echo esc_html(get_the_title($nearby_destination)); ?></h3>
										<p class="twostars-editorial-card__excerpt"><?php echo esc_html(twostars_theme_get_post_excerpt($nearby_destination, 18)); ?></p>
										<a class="twostars-editorial-card__link" href="<?php echo esc_url(get_permalink($nearby_destination)); ?>">
											<?php esc_html_e('View Destination', 'twostars-theme'); ?>
										</a>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>
			</div>
		</article>
	<?php endwhile; ?>
</section>
<?php
get_footer();
