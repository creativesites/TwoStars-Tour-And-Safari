<?php
declare(strict_types=1);

get_header();
?>
<section class="site-shell site-stack">
	<?php while (have_posts()) : ?>
		<?php the_post(); ?>
		<article <?php post_class('story-layout'); ?>>
			<header class="story-hero">
				<p class="eyebrow"><?php esc_html_e('Guest Story', 'twostars-theme'); ?></p>
				<h1><?php the_title(); ?></h1>
			</header>

			<div class="content-card content-card--single content-card--quote">
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</section>
<?php
get_footer();
