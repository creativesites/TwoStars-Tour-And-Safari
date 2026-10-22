<?php
declare(strict_types=1);

get_header();
?>
<section class="twostars-article">
	<div class="site-shell">
		<?php while (have_posts()) : ?>
			<?php the_post(); ?>
			<?php $related_posts = twostars_theme_get_related_blog_posts(get_the_ID(), 3); ?>
			<article <?php post_class('twostars-article__layout'); ?>>
				<header class="twostars-article__hero">
					<p class="twostars-article__eyebrow"><?php esc_html_e('Journal Entry', 'twostars-theme'); ?></p>
					<h1 class="twostars-article__title"><?php the_title(); ?></h1>
					<div class="twostars-article__meta">
						<span><?php echo esc_html(get_the_date()); ?></span>
						<span><?php echo esc_html(get_the_author()); ?></span>
					</div>
					<?php if (has_excerpt()) : ?>
						<p class="twostars-article__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
					<?php endif; ?>
					<?php if (has_post_thumbnail()) : ?>
						<div class="twostars-article__media"><?php the_post_thumbnail('full'); ?></div>
					<?php endif; ?>
				</header>

				<div class="twostars-article__content">
					<div class="twostars-article__body">
						<?php the_content(); ?>
					</div>

					<aside class="twostars-article__sidebar">
						<div class="twostars-article__sidebar-card">
							<p class="twostars-article__sidebar-label"><?php esc_html_e('Primary Next Step', 'twostars-theme'); ?></p>
							<h2><?php esc_html_e('Need help turning this into a route?', 'twostars-theme'); ?></h2>
							<p><?php esc_html_e('Use the booking or contact pages if you want help applying these ideas to your actual Namibia dates and trip style.', 'twostars-theme'); ?></p>
							<a class="tour-booking-button" href="<?php echo esc_url(home_url('/booking/')); ?>"><?php esc_html_e('Start Booking', 'twostars-theme'); ?></a>
						</div>

						<?php if ($related_posts !== []) : ?>
							<div class="twostars-article__sidebar-card">
								<p class="twostars-article__sidebar-label"><?php esc_html_e('Continue Reading', 'twostars-theme'); ?></p>
								<ul class="twostars-article__related-list">
									<?php foreach ($related_posts as $related_post) : ?>
										<li>
											<a href="<?php echo esc_url(get_permalink($related_post)); ?>"><?php echo esc_html(get_the_title($related_post)); ?></a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</aside>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</section>
<?php
get_footer();
