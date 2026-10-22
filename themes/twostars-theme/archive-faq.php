<?php
/**
 * The template for displaying FAQ archives
 *
 * @package twostars-theme
 */

get_header();

?>

<main id="primary" class="site-main">
	<section class="twostars-faq-hero">
		<div class="twostars-faq-hero__media" aria-hidden="true">
			<div class="twostars-faq-hero__image twostars-faq-hero__image--placeholder"></div>
		</div>
		<div class="twostars-faq-hero__overlay"></div>
		<div class="twostars-faq-hero__content">
			<p class="twostars-faq-hero__subtitle" data-twostars-animate="fade-in-up"><?php esc_html_e('Help & Guidance', 'twostars-theme'); ?></p>
			<h1 class="twostars-faq-hero__title" data-twostars-animate="fade-in-up" style="--twostars-delay:0.12s;"><?php post_type_archive_title(); ?></h1>
			<?php if (get_the_archive_description()) : ?>
				<div class="twostars-faq-hero__description" data-twostars-animate="fade-in-up" style="--twostars-delay:0.24s;"><?php the_archive_description(); ?></div>
			<?php endif; ?>
		</div>
	</section>

	<div class="site-shell">
		<section class="twostars-faq-accordion" data-twostars-animate="fade-in-up">
			<div class="twostars-faq-accordion__items">
				<?php if (have_posts()) : ?>
					<?php 
					$index = 0; 
					while (have_posts()) : 
						the_post(); 
					?>
						<details 
							class="twostars-faq-accordion__item" 
							data-twostars-animate="fade-in-up" 
							style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 6) * 0.08, 2)); ?>s;"
							<?php echo $index === 0 ? 'open' : ''; ?>
						>
							<summary class="twostars-faq-accordion__question">
								<span><?php the_title(); ?></span>
							</summary>
							<div class="twostars-faq-accordion__answer">
								<?php the_content(); ?>
							</div>
						</details>
					<?php 
						$index++; 
					endwhile; 
					?>
				<?php else : ?>
					<p><?php esc_html_e('No FAQs found.', 'twostars-theme'); ?></p>
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
