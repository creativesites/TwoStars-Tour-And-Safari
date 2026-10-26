<?php
declare(strict_types=1);

$blog_page = twostars_theme_get_blog_page();

get_header();
?>
<section class="twostars-editorial-archive twostars-editorial-archive--blog">
	<div class="site-shell">
		<header class="twostars-editorial-archive__hero">
			<p class="twostars-editorial-archive__eyebrow"><?php echo esc_html(twostars_theme_get_archive_eyebrow_text('post')); ?></p>
			<h1 class="twostars-editorial-archive__title">
				<?php echo esc_html($blog_page ? get_the_title($blog_page) : twostars_theme_get_archive_title_text('post')); ?>
			</h1>
			<p class="twostars-editorial-archive__description">
				<?php echo esc_html($blog_page ? twostars_theme_get_post_excerpt($blog_page, 34) : twostars_theme_get_archive_description_text('post')); ?>
			</p>
		</header>

		<?php if (have_posts()) : ?>
			<div class="twostars-editorial-grid">
				<?php while (have_posts()) : ?>
					<?php the_post(); ?>
					<?php $card_meta = twostars_theme_get_post_card_meta(get_post()); ?>
					<article <?php post_class('twostars-editorial-card'); ?>>
						<?php if (has_post_thumbnail()) : ?>
							<a class="twostars-editorial-card__media" href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail('large'); ?>
							</a>
						<?php endif; ?>

						<div class="twostars-editorial-card__body">
							<p class="twostars-editorial-card__type"><?php esc_html_e('Guide', 'twostars-theme'); ?></p>
							<h2 class="twostars-editorial-card__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>

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

			<div class="twostars-editorial-pagination">
				<?php
				the_posts_pagination(
					[
						'prev_text' => __('Previous', 'twostars-theme'),
						'next_text' => __('Next', 'twostars-theme'),
					]
				);
				?>
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
