<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<?php if (! function_exists('elementor_theme_do_location') || ! elementor_theme_do_location('header')) : ?>
		<?php $custom_logo_id = (int) get_theme_mod('custom_logo'); ?>
		<header class="site-header">
			<div class="site-shell site-shell--header">
				<div class="site-branding">
					<a class="site-branding__link" href="<?php echo esc_url(home_url('/')); ?>">
						<?php if ($custom_logo_id > 0) : ?>
							<span class="site-branding__logo">
								<?php echo wp_get_attachment_image($custom_logo_id, 'full', false, ['class' => 'site-branding__image']); ?>
							</span>
						<?php else : ?>
							<span class="site-branding__mark" aria-hidden="true">
								<span class="site-branding__star">★</span>
								<span class="site-branding__star">★</span>
							</span>
							<span class="site-branding__copy">
								<span class="site-title"><?php bloginfo('name'); ?></span>
								<span class="site-branding__eyebrow"><?php esc_html_e('Adventure Beyond the Horizon', 'twostars-theme'); ?></span>
							</span>
						<?php endif; ?>
					</a>
				</div>

				<button
					class="site-nav-toggle"
					type="button"
					aria-expanded="false"
					aria-controls="site-navigation-panel"
					data-site-nav-toggle
				>
					<span class="site-nav-toggle__label"><?php esc_html_e('Menu', 'twostars-theme'); ?></span>
					<span class="site-nav-toggle__icon" aria-hidden="true">
						<span></span>
						<span></span>
						<span></span>
					</span>
				</button>

				<div class="site-nav-panel" id="site-navigation-panel" data-site-nav-panel>
					<nav class="site-navigation" aria-label="<?php esc_attr_e('Primary Navigation', 'twostars-theme'); ?>">
						<?php wp_nav_menu(twostars_theme_get_menu_args('site-menu')); ?>
					</nav>
					<div class="site-header__actions">
						<a class="site-header__button" href="<?php echo esc_url(home_url('/tours/')); ?>" data-twostars-event="book_now_click">
							<?php esc_html_e('Book Now', 'twostars-theme'); ?>
						</a>
					</div>
				</div>
			</div>
		</header>
	<?php endif; ?>

	<main id="content" class="site-content">
