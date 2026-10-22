<?php
declare(strict_types=1);

get_header();
?>
<section class="twostars-editorial-archive">
	<div class="site-shell">
		<header class="twostars-editorial-archive__hero">
			<p class="twostars-editorial-archive__eyebrow"><?php echo esc_html(twostars_theme_get_archive_eyebrow_text('post')); ?></p>
			<h1 class="twostars-editorial-archive__title"><?php echo esc_html(twostars_theme_get_archive_title_text('post')); ?></h1>
			<p class="twostars-editorial-archive__description"><?php echo esc_html(twostars_theme_get_archive_description_text('post')); ?></p>
		</header>
		<?php if (have_posts()) : ?>
			<div class="twostars-editorial-grid">
				<?php while (have_posts()) : ?>
					<?php the_post(); ?>
					<?php $card_meta = twostars_theme_get_post_card_meta(get_post()); ?>
					<article <?php post_class('twostars-editorial-card'); ?>>
						<div class="twostars-editorial-card__body">
							<p class="twostars-editorial-card__type"><?php esc_html_e('Article', 'twostars-theme'); ?></p>
							<h2 class="twostars-editorial-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php if ($card_meta !== []) : ?>
								<ul class="twostars-editorial-card__meta">
									<?php foreach (array_slice($card_meta, 0, 2) as $item) : ?>
										<li><?php echo esc_html($item); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<p class="twostars-editorial-card__excerpt"><?php echo esc_html(twostars_theme_get_post_excerpt(get_post(), 32)); ?></p>
							<a class="twostars-editorial-card__link" href="<?php the_permalink(); ?>"><?php echo esc_html(twostars_theme_get_post_cta_label(get_post())); ?></a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
		<?php else : ?>
			<div class="site-stack">
				<?php get_template_part('template-parts/content', 'none'); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
