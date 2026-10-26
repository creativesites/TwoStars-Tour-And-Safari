<?php
declare(strict_types=1);
?>
<article class="ts-legal">
	<?php while (have_posts()) : ?>
		<?php the_post(); ?>
		<header class="ts-legal__head">
			<div class="ts-wrap ts-wrap--narrow">
				<p class="ts-eyebrow"><?php esc_html_e('Two Stars Tour & Safari', 'twostars-theme'); ?></p>
				<h1><?php the_title(); ?></h1>
			</div>
		</header>
		<div class="ts-wrap ts-wrap--narrow ts-legal__body">
			<?php the_content(); ?>
		</div>
	<?php endwhile; ?>
</article>
