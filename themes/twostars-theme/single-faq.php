<?php
declare(strict_types=1);

get_header();
?>
<section class="site-shell site-stack">
	<?php while (have_posts()) : ?>
		<?php the_post(); ?>
		<article <?php post_class('content-card content-card--single faq-card'); ?>>
			<p class="eyebrow"><?php esc_html_e('FAQ', 'twostars-theme'); ?></p>
			<h1 class="content-card__title"><?php the_title(); ?></h1>
			<div class="content-card__body">
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</section>
<?php
get_footer();
