<?php
declare(strict_types=1);

$archive_post_type = get_post_type() ?: 'post';

get_header();
?>
<section class="twostars-editorial-archive">
	<div class="site-shell">
		<header class="twostars-editorial-archive__hero">
			<p class="twostars-editorial-archive__eyebrow"><?php echo esc_html(twostars_theme_get_archive_eyebrow_text($archive_post_type)); ?></p>
			<h1 class="twostars-editorial-archive__title"><?php echo esc_html(twostars_theme_get_archive_title_text($archive_post_type)); ?></h1>
			<p class="twostars-editorial-archive__description"><?php echo esc_html(twostars_theme_get_archive_description_text($archive_post_type)); ?></p>
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
							<p class="twostars-editorial-card__type"><?php echo esc_html(ucwords(str_replace('_', ' ', (string) get_post_type()))); ?></p>
							<h2 class="twostars-editorial-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php if ($card_meta !== []) : ?>
								<ul class="twostars-editorial-card__meta">
									<?php foreach (array_slice($card_meta, 0, 3) as $item) : ?>
										<li><?php echo esc_html($item); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<p class="twostars-editorial-card__excerpt"><?php echo esc_html(twostars_theme_get_post_excerpt(get_post(), 30)); ?></p>
							<a class="twostars-editorial-card__link" href="<?php the_permalink(); ?>"><?php echo esc_html(twostars_theme_get_post_cta_label(get_post())); ?></a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<div class="twostars-editorial-pagination">
				<?php the_posts_pagination(['prev_text' => __('Previous', 'twostars-theme'), 'next_text' => __('Next', 'twostars-theme')]); ?>
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
