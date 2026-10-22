<?php
/**
 * The template for displaying testimonial archives
 *
 * @package twostars-theme
 */

get_header();

?>

<main id="primary" class="site-main">
	<section class="twostars-testimonials-hero">
		<div class="twostars-testimonials-hero__media" aria-hidden="true">
			<div class="twostars-testimonials-hero__image twostars-testimonials-hero__image--placeholder"></div>
		</div>
		<div class="twostars-testimonials-hero__overlay"></div>
		<div class="twostars-testimonials-hero__content">
			<p class="twostars-testimonials-hero__subtitle" data-twostars-animate="fade-in-up"><?php esc_html_e('Client Stories', 'twostars-theme'); ?></p>
			<h1 class="twostars-testimonials-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.12s;"><?php post_type_archive_title(); ?></h1>
			<?php if (get_the_archive_description()) : ?>
				<div class="twostars-testimonials-hero__description" data-twostars-animate="fade-in-up" style="--twostars-delay:0.24s;"><?php the_archive_description(); ?></div>
			<?php endif; ?>
		</div>
	</section>

	<div class="site-shell">
		<section class="twostars-testimonials-grid" data-twostars-animate="fade-in-up">
			<div class="twostars-testimonials-grid__cards">
				<?php if (have_posts()) : ?>
					<?php 
					$index = 0; 
					while (have_posts()) : 
						the_post(); 
						$image_id = get_post_thumbnail_id();
					?>
						<article 
							class="twostars-testimonials-grid__card" 
							data-twostars-animate="fade-in-up" 
							style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.12, 2)); ?>s;"
						>
							<?php if ($image_id > 0) : ?>
								<div class="twostars-testimonials-grid__media">
									<?php echo wp_get_attachment_image($image_id, 'medium_large', false, ['loading' => 'lazy']); ?>
								</div>
							<?php endif; ?>

							<blockquote class="twostars-testimonials-grid__quote">
								<?php echo esc_html(get_the_excerpt()); ?>
							</blockquote>
							
							<h3 class="twostars-testimonials-grid__author"><?php the_title(); ?></h3>
							
							<?php if (trim((string) get_the_content()) !== '') : ?>
								<p class="twostars-testimonials-grid__meta">
									<?php echo esc_html(wp_trim_words(wp_strip_all_tags(get_the_content()), 18, '...')); ?>
								</p>
							<?php endif; ?>
						</article>
					<?php 
						$index++; 
					endwhile; 
					?>
				<?php else : ?>
					<p><?php esc_html_e('No testimonials found.', 'twostars-theme'); ?></p>
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
