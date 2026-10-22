<?php
/**
 * The template for displaying tour archives
 *
 * @package twostars-theme
 */

get_header();

use TwoStars\Platform\Support\TourData;

?>

<main id="primary" class="site-main">
	<?php
	$archive_copy = twostars_theme_get_archive_copy('tour');
	$hero_image_url = '';
	$hero_tours = twostars_theme_get_posts_by_type('tour', 1);
	if ($hero_tours !== []) {
		$hero_image_id = get_post_thumbnail_id($hero_tours[0]->ID);
		$hero_image_url = $hero_image_id > 0 ? (string) wp_get_attachment_image_url($hero_image_id, 'full') : '';
	}
	?>
	<section class="twostars-tours-hero">
		<div class="twostars-tours-hero__media" aria-hidden="true"<?php if ($hero_image_url !== '') : ?> style="background-image:url('<?php echo esc_url($hero_image_url); ?>');"<?php endif; ?>>
			<div class="twostars-tours-hero__image<?php echo $hero_image_url === '' ? ' twostars-tours-hero__image--placeholder' : ''; ?>"></div>
		</div>
		<div class="twostars-tours-hero__overlay"></div>
		<div class="twostars-tours-hero__content">
			<p class="twostars-tours-hero__subtitle" data-twostars-animate="fade-in-up"><?php echo esc_html((string) $archive_copy['eyebrow']); ?></p>
			<h1 class="twostars-tours-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.15s;"><?php echo esc_html((string) $archive_copy['title']); ?></h1>
			<div class="twostars-tours-hero__description" data-twostars-animate="fade-in-up" style="--twostars-delay:0.3s;">
				<p><?php echo esc_html((string) $archive_copy['description']); ?></p>
			</div>
		</div>
	</section>

	<div class="site-shell">
		<section class="twostars-tours-grid-section">
			<header class="twostars-editorial-section-head">
				<div>
					<p class="twostars-editorial-section-head__label"><?php esc_html_e('Current Collection', 'twostars-theme'); ?></p>
					<h2 class="twostars-editorial-section-head__title"><?php esc_html_e('Choose your tour', 'twostars-theme'); ?></h2>
				</div>
			</header>
			<div class="twostars-tours-grid" data-twostars-tour-grid data-filter-group="tours-archive">
				<?php if (have_posts()) : ?>
					<?php 
					$index = 0; 
					while (have_posts()) : 
						the_post(); 
						$tour_id = get_the_ID();
						
						$summary_items = class_exists(TourData::class) ? TourData::get_summary_items($tour_id) : [];
						$availability  = class_exists(TourData::class) ? TourData::get_availability_label($tour_id) : '';
						$tour_meta = class_exists(TourData::class) ? TourData::get_tour_meta($tour_id) : [];
						$linked_titles = [];
						foreach ((array) ($tour_meta['linked_destinations'] ?? []) as $linked_id) {
							$linked_post = get_post((int) $linked_id);
							if ($linked_post instanceof WP_Post && $linked_post->post_status === 'publish') {
								$linked_titles[] = get_the_title($linked_post);
							}
						}
						
						$image_id = get_post_thumbnail_id($tour_id);
					?>
						<article
							class="twostars-tours-grid__card"
							data-twostars-tour-card
							data-twostars-animate="fade-in-up"
							style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.14, 2)); ?>s;"
						>
							<div class="twostars-tours-grid__media">
								<?php if ($image_id > 0) : ?>
									<?php echo wp_get_attachment_image($image_id, 'large', false, ['loading' => 'lazy']); ?>
								<?php else : ?>
									<div class="twostars-tours-grid__placeholder"></div>
								<?php endif; ?>
								
								<?php if ($availability !== '') : ?>
									<span class="twostars-tours-grid__badge"><?php echo esc_html($availability); ?></span>
								<?php endif; ?>
							</div>

							<div class="twostars-tours-grid__body">
								<div class="twostars-tours-grid__terms">
									<?php foreach (array_slice($linked_titles, 0, 3) as $label) : ?>
										<span class="twostars-tours-grid__term"><?php echo esc_html($label); ?></span>
									<?php endforeach; ?>
								</div>

								<h3 class="twostars-tours-grid__title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h3>
								
								<p class="twostars-tours-grid__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>

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
									<a class="twostars-safari-button twostars-safari-button--primary" href="<?php the_permalink(); ?>">
										<?php esc_html_e('Explore Tour', 'twostars-theme'); ?>
									</a>
								</div>
							</div>
						</article>
					<?php 
						$index++; 
					endwhile; 
					?>
				<?php else : ?>
					<div class="twostars-tours-grid__empty">
						<p><?php esc_html_e('No tours are available yet.', 'twostars-theme'); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<div class="twostars-editorial-pagination">
				<?php the_posts_pagination(['prev_text' => __('Previous', 'twostars-theme'), 'next_text' => __('Next', 'twostars-theme')]); ?>
			</div>
		</section>
	</div>
</main>

<?php
get_footer();
