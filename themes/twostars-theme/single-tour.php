<?php
declare(strict_types=1);

use TwoStars\Platform\Support\TourData;

get_header();

$twostars_fx = static function (float $nad): string {
	$parts = [];

	foreach (['EUR' => '€', 'USD' => 'US$'] as $code => $symbol) {
		$rate = (float) get_option('twostars_fx_nad_per_' . strtolower($code), 0);

		if ($rate > 0) {
			$parts[] = '≈ ' . $symbol . number_format($nad / $rate, 0, '.', ',');
		}
	}

	return implode(' · ', $parts);
};
?>
<section class="twostars-tour-entry twostars-tour-page">
	<?php while (have_posts()) : ?>
		<?php
		the_post();

		$tour_id        = get_the_ID();
		$meta           = TourData::get_tour_meta($tour_id);
		$price          = is_numeric($meta['price_from']) && (float) $meta['price_from'] > 0 ? (float) $meta['price_from'] : 0.0;
		$whatsapp_url   = twostars_theme_get_whatsapp_url();
		$hook           = $meta['hook'] !== '' ? $meta['hook'] : wp_strip_all_tags(get_the_excerpt());
		$gallery_ids    = twostars_theme_get_tour_gallery_ids($tour_id);
		$hero_url       = $gallery_ids !== [] ? (string) wp_get_attachment_image_url($gallery_ids[0], 'full') : '';
		$itinerary      = TourData::rows($meta['itinerary'], 3);
		$includes       = TourData::lines($meta['includes']);
		$excludes       = TourData::lines($meta['excludes']);
		$bring          = TourData::lines($meta['what_to_bring']);
		$notes          = TourData::lines($meta['notes']);
		$faqs           = array_values(array_filter(TourData::rows($meta['faqs'], 2), static fn(array $r): bool => $r[0] !== '' && $r[1] !== ''));
		$meeting_lines  = TourData::lines($meta['meeting_point']);
		$cancellation   = $meta['cancellation'] !== '' ? $meta['cancellation'] : '';
		$other_tours    = array_slice(
			array_values(array_filter(twostars_theme_get_posts_by_type('tour', 6), static fn(WP_Post $p): bool => $p->ID !== $tour_id)),
			0,
			2
		);

		$facts = [];
		if ($price > 0) {
			$facts[] = ['label' => __('From', 'twostars-theme'), 'value' => TourData::format_nad($price), 'sub' => $twostars_fx($price)];
		}
		if ($meta['duration'] !== '') {
			$facts[] = ['label' => __('Duration', 'twostars-theme'), 'value' => $meta['duration']];
		}
		if ($meta['group_size'] !== '') {
			$facts[] = ['label' => __('Group size', 'twostars-theme'), 'value' => $meta['group_size']];
		}
		if ($meta['departure_times'] !== '') {
			$facts[] = ['label' => __('Departs', 'twostars-theme'), 'value' => $meta['departure_times']];
		}
		if ($meta['pickup_location'] !== '') {
			$facts[] = ['label' => __('Pickup', 'twostars-theme'), 'value' => $meta['pickup_location']];
		}
		if ($cancellation !== '') {
			$facts[] = ['label' => __('Cancellation', 'twostars-theme'), 'value' => $cancellation];
		}
		?>
		<article <?php post_class('twostars-tour-page__article'); ?>>
			<header class="twostars-tour-page__hero"<?php echo $hero_url !== '' ? ' style="--twostars-tour-entry-image:url(\'' . esc_url($hero_url) . '\');"' : ''; ?>>
				<div class="twostars-tour-entry__masthead-media" aria-hidden="true"></div>
				<div class="twostars-tour-entry__masthead-overlay" aria-hidden="true"></div>
				<div class="site-shell twostars-tour-page__hero-copy">
					<nav class="twostars-tour-page__crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'twostars-theme'); ?>">
						<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'twostars-theme'); ?></a>
						<span aria-hidden="true">/</span>
						<a href="<?php echo esc_url(home_url('/tours/')); ?>"><?php esc_html_e('Tours', 'twostars-theme'); ?></a>
					</nav>
					<h1 class="twostars-tour-entry__masthead-title"><?php the_title(); ?></h1>
					<?php if ($hook !== '') : ?>
						<p class="twostars-tour-entry__masthead-excerpt"><?php echo esc_html($hook); ?></p>
					<?php endif; ?>
					<div class="twostars-tour-entry__masthead-actions">
						<a class="twostars-tour-entry__button twostars-tour-entry__button--primary" href="#book" data-twostars-event="book_now_click"><?php esc_html_e('Book now', 'twostars-theme'); ?></a>
						<a class="twostars-tour-entry__button twostars-tour-entry__button--secondary" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" data-twostars-event="whatsapp_click"><?php esc_html_e('Ask on WhatsApp', 'twostars-theme'); ?></a>
					</div>
				</div>
			</header>

			<?php if ($facts !== []) : ?>
				<div class="twostars-tour-page__facts-wrap">
					<div class="site-shell">
						<dl class="twostars-tour-page__facts">
							<?php foreach ($facts as $fact) : ?>
								<div class="twostars-tour-page__fact">
									<dt><?php echo esc_html($fact['label']); ?></dt>
									<dd>
										<?php echo esc_html($fact['value']); ?>
										<?php if (! empty($fact['sub'])) : ?><small><?php echo esc_html($fact['sub']); ?></small><?php endif; ?>
									</dd>
								</div>
							<?php endforeach; ?>
						</dl>
					</div>
				</div>
			<?php endif; ?>

			<div class="site-shell">
				<div class="twostars-tour-page__layout">
					<?php if ($gallery_ids !== []) : ?>
						<section class="twostars-tour-page__gallery twostars-tour-entry__gallery" data-twostars-lightbox-gallery aria-label="<?php esc_attr_e('Tour photos', 'twostars-theme'); ?>">
							<div class="twostars-tour-entry__gallery-grid<?php echo count($gallery_ids) === 1 ? ' twostars-tour-entry__gallery-grid--single' : ''; ?>">
								<?php foreach (array_slice($gallery_ids, 0, 12) as $index => $image_id) : ?>
									<?php
									$alt   = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
									$label = $alt !== '' ? $alt : get_the_title($image_id);
									?>
									<figure class="twostars-tour-entry__gallery-item twostars-tour-entry__gallery-item--<?php echo esc_attr((string) ($index + 1)); ?>">
										<button class="twostars-gallery-tile" type="button" data-twostars-lightbox-item
											data-twostars-lightbox-src="<?php echo esc_url((string) wp_get_attachment_image_url($image_id, 'full')); ?>"
											data-twostars-lightbox-alt="<?php echo esc_attr($label); ?>"
											data-twostars-lightbox-caption="<?php echo esc_attr($label); ?>"
											aria-label="<?php echo esc_attr(sprintf(__('Open image %d in gallery', 'twostars-theme'), $index + 1)); ?>">
											<?php echo wp_get_attachment_image($image_id, 'large', false, $index === 0 ? ['fetchpriority' => 'high'] : ['loading' => 'lazy']); ?>
										</button>
									</figure>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endif; ?>

					<aside class="twostars-tour-page__aside" id="book" aria-label="<?php esc_attr_e('Book this tour', 'twostars-theme'); ?>">
						<div class="twostars-tour-page__book-card">
							<?php if ($price > 0) : ?>
								<p class="twostars-tour-page__book-price">
									<span><?php esc_html_e('From', 'twostars-theme'); ?></span>
									<strong><?php echo esc_html(TourData::format_nad($price)); ?></strong>
									<?php esc_html_e('per adult', 'twostars-theme'); ?>
								</p>
								<?php if ($twostars_fx($price) !== '') : ?><p class="twostars-tour-page__book-fx"><?php echo esc_html($twostars_fx($price)); ?></p><?php endif; ?>
							<?php else : ?>
								<p class="twostars-tour-page__book-price"><strong><?php esc_html_e('Price on request', 'twostars-theme'); ?></strong></p>
							<?php endif; ?>
							<?php if ($meta['price_note'] !== '') : ?><p class="twostars-tour-page__book-note"><?php echo esc_html($meta['price_note']); ?></p><?php endif; ?>

							<?php if ($meta['bokun_url'] !== '') : ?>
								<iframe class="twostars-tour-page__bokun" src="<?php echo esc_url($meta['bokun_url']); ?>" title="<?php esc_attr_e('Online booking', 'twostars-theme'); ?>" loading="lazy" height="640"></iframe>
							<?php else : ?>
								<?php /* [PLACEHOLDER: Bokun booking widget — paste the experience widget URL into "Bokun Booking Widget URL" on this tour] */ ?>
								<div class="twostars-tour-page__book-fallback">
									<p><?php esc_html_e('Online booking opens soon. Send us your preferred date and group size and we will confirm your seats.', 'twostars-theme'); ?></p>
									<a class="twostars-tour-entry__button twostars-tour-entry__button--primary" href="<?php echo esc_url(TourData::get_booking_url($tour_id)); ?>" data-twostars-event="book_now_click"><?php esc_html_e('Request this tour', 'twostars-theme'); ?></a>
								</div>
							<?php endif; ?>

							<a class="twostars-tour-page__book-wa" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" data-twostars-event="whatsapp_click"><?php esc_html_e('Questions? Chat on WhatsApp', 'twostars-theme'); ?></a>
						</div>
					</aside>

					<div class="twostars-tour-page__main">
						<section class="twostars-tour-page__section">
							<h2><?php esc_html_e('Overview', 'twostars-theme'); ?></h2>
							<div class="twostars-tour-entry__body-content"><?php the_content(); ?></div>
						</section>

						<?php if ($itinerary !== []) : ?>
							<section class="twostars-tour-page__section">
								<h2><?php esc_html_e('Itinerary', 'twostars-theme'); ?></h2>
								<ol class="twostars-tour-page__timeline">
									<?php foreach ($itinerary as [$time, $title, $text]) : ?>
										<li>
											<span class="twostars-tour-page__time"><?php echo esc_html($time); ?></span>
											<div>
												<h3><?php echo esc_html($title); ?></h3>
												<?php if ($text !== '') : ?><p><?php echo esc_html($text); ?></p><?php endif; ?>
											</div>
										</li>
									<?php endforeach; ?>
								</ol>
							</section>
						<?php endif; ?>

						<?php if ($includes !== [] || $excludes !== []) : ?>
							<section class="twostars-tour-page__section">
								<h2><?php esc_html_e('Included / not included', 'twostars-theme'); ?></h2>
								<div class="twostars-tour-page__two-col">
									<?php if ($includes !== []) : ?>
										<ul class="twostars-tour-page__list twostars-tour-page__list--yes" aria-label="<?php esc_attr_e('Included', 'twostars-theme'); ?>">
											<?php foreach ($includes as $item) : ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?>
										</ul>
									<?php endif; ?>
									<?php if ($excludes !== []) : ?>
										<ul class="twostars-tour-page__list twostars-tour-page__list--no" aria-label="<?php esc_attr_e('Not included', 'twostars-theme'); ?>">
											<?php foreach ($excludes as $item) : ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ($meeting_lines !== [] || $meta['map_embed_url'] !== '') : ?>
							<section class="twostars-tour-page__section">
								<h2><?php esc_html_e('Pickup and meeting point', 'twostars-theme'); ?></h2>
								<?php foreach ($meeting_lines as $line) : ?><p><?php echo esc_html($line); ?></p><?php endforeach; ?>
								<?php if ($meta['map_embed_url'] !== '') : ?>
									<iframe class="twostars-tour-page__map" src="<?php echo esc_url($meta['map_embed_url']); ?>" title="<?php esc_attr_e('Pickup map', 'twostars-theme'); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" height="320"></iframe>
								<?php endif; ?>
							</section>
						<?php endif; ?>

						<?php if ($bring !== []) : ?>
							<section class="twostars-tour-page__section">
								<h2><?php esc_html_e('What to bring', 'twostars-theme'); ?></h2>
								<ul class="twostars-tour-page__list">
									<?php foreach ($bring as $item) : ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?>
								</ul>
							</section>
						<?php endif; ?>

						<?php if ($notes !== []) : ?>
							<section class="twostars-tour-page__section">
								<h2><?php esc_html_e('Good to know', 'twostars-theme'); ?></h2>
								<ul class="twostars-tour-page__list">
									<?php foreach ($notes as $item) : ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?>
								</ul>
							</section>
						<?php endif; ?>

						<section class="twostars-tour-page__section">
							<h2><?php esc_html_e('Guest reviews', 'twostars-theme'); ?></h2>
							<?php /* Reviews for this tour: filled once review-to-tour links exist; falls back to the reviews page. */ ?>
							<p><a href="<?php echo esc_url(home_url('/reviews/')); ?>"><?php esc_html_e('Read what guests say about Tuli and the team', 'twostars-theme'); ?></a></p>
						</section>

						<?php if ($faqs !== []) : ?>
							<section class="twostars-tour-page__section">
								<h2><?php esc_html_e('Frequently asked questions', 'twostars-theme'); ?></h2>
								<div class="twostars-tour-page__faqs">
									<?php foreach ($faqs as [$question, $answer]) : ?>
										<details>
											<summary><?php echo esc_html($question); ?></summary>
											<p><?php echo esc_html($answer); ?></p>
										</details>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ($other_tours !== []) : ?>
							<section class="twostars-tour-page__section">
								<h2><?php esc_html_e('Related tours', 'twostars-theme'); ?></h2>
								<div class="twostars-tour-page__related">
									<?php foreach ($other_tours as $other) : ?>
										<?php
										$other_price = (float) get_post_meta($other->ID, '_twostars_tour_price_from', true);
										$other_img   = get_post_thumbnail_id($other->ID);
										?>
										<a class="twostars-tour-page__related-card" href="<?php echo esc_url(get_permalink($other)); ?>">
											<?php if ($other_img > 0) { echo wp_get_attachment_image($other_img, 'medium_large', false, ['loading' => 'lazy']); } ?>
											<span><?php echo esc_html(get_the_title($other)); ?></span>
											<?php if ($other_price > 0) : ?><small><?php echo esc_html(sprintf(__('From %s', 'twostars-theme'), TourData::format_nad($other_price))); ?></small><?php endif; ?>
										</a>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</article>
		<?php
		$schema = [
			'@context' => 'https://schema.org',
			'@graph'   => [
				[
					'@type'       => 'TouristTrip',
					'name'        => get_the_title(),
					'description' => $hook,
					'url'         => get_permalink(),
					'image'       => $hero_url !== '' ? [$hero_url] : [],
					'provider'    => ['@type' => 'TravelAgency', 'name' => 'Two Stars Tour & Safari', 'url' => home_url('/')],
					'touristType' => 'Small group',
				],
				[
					'@type'           => 'BreadcrumbList',
					'itemListElement' => [
						['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')],
						['@type' => 'ListItem', 'position' => 2, 'name' => 'Tours', 'item' => home_url('/tours/')],
						['@type' => 'ListItem', 'position' => 3, 'name' => get_the_title(), 'item' => get_permalink()],
					],
				],
			],
		];

		if ($price > 0) {
			$schema['@graph'][0]['offers'] = [
				'@type'         => 'Offer',
				'price'         => (string) $price,
				'priceCurrency' => 'NAD',
				'availability'  => 'https://schema.org/InStock',
				'url'           => get_permalink(),
			];
		}

		if ($faqs !== []) {
			$schema['@graph'][] = [
				'@type'      => 'FAQPage',
				'mainEntity' => array_map(
					static fn(array $r): array => ['@type' => 'Question', 'name' => $r[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $r[1]]],
					$faqs
				),
			];
		}
		?>
		<script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
	<?php endwhile; ?>
</section>
<?php
get_footer();
