<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}
?>
	</main>

	<?php if (! function_exists('elementor_theme_do_location') || ! elementor_theme_do_location('footer')) : ?>
		<footer class="site-footer">
			<div class="site-shell site-shell--footer">
				<div class="site-footer__brand">
					<div class="site-footer__brand-lockup">
						<span class="site-footer__brand-stars" aria-hidden="true">
							<span class="site-footer__brand-star">★</span>
							<span class="site-footer__brand-star">★</span>
						</span>
						<div>
							<p class="site-footer__eyebrow"><?php esc_html_e('Two Stars Tour and Safari', 'twostars-theme'); ?></p>
							<h3 class="site-footer__title"><?php esc_html_e('Namibia’s coast, with Tuli and her team', 'twostars-theme'); ?></h3>
						</div>
					</div>
					<p class="site-footer__text">
						<?php esc_html_e('A women-led local family business running small-group day tours from Walvis Bay and Swakopmund: unhurried photo stops, a maximum of four guests per vehicle, and pickups from hotels and the cruise port. Registered with the Namibia Tourism Board, #TSO01736.', 'twostars-theme'); ?>
					</p>
				</div>

				<div class="site-footer__nav-wrap">
					<p class="site-footer__nav-label"><?php esc_html_e('Explore', 'twostars-theme'); ?></p>
					<nav aria-label="<?php esc_attr_e('Site Navigation', 'twostars-theme'); ?>">
						<?php wp_nav_menu(twostars_theme_get_menu_args('site-menu site-menu--footer', 'footer')); ?>
					</nav>
				</div>

				<div class="site-footer__contact">
					<p class="site-footer__nav-label"><?php esc_html_e('Contact', 'twostars-theme'); ?></p>
					<a class="site-footer__contact-link" href="<?php echo esc_url(twostars_theme_get_whatsapp_url()); ?>">
						<?php echo esc_html(twostars_theme_get_whatsapp_number() !== '' ? twostars_theme_get_whatsapp_number() : __('Open Contact Options', 'twostars-theme')); ?>
					</a>
					<a class="site-footer__contact-link site-footer__contact-link--subtle" href="<?php echo esc_url(home_url('/contact/')); ?>">
						<?php esc_html_e('Visit the contact page', 'twostars-theme'); ?>
					</a>
				</div>

				<div class="site-footer__cta">
					<p class="site-footer__nav-label"><?php esc_html_e('Ready to Explore?', 'twostars-theme'); ?></p>
					<p class="site-footer__cta-text"><?php esc_html_e('Pick a tour and book online, or message us on WhatsApp and we will help you choose.', 'twostars-theme'); ?></p>
					<a class="tour-booking-button site-footer__button" href="<?php echo esc_url(home_url('/tours/')); ?>" data-twostars-event="book_now_click">
						<?php esc_html_e('See tours and prices', 'twostars-theme'); ?>
					</a>
				</div>
			</div>

			<div class="site-footer__subfooter">
				<div class="site-shell site-footer__subfooter-shell">
					<p class="site-footer__copyright">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d is year */
								__('© %d Two Stars Tour & Safari CC', 'twostars-theme'),
								(int) gmdate('Y')
							)
						);
						?>
					</p>
					<ul class="site-footer__legal-links">
						<li><a href="<?php echo esc_url(home_url('/walvis-bay-shore-excursions/')); ?>"><?php esc_html_e('Shore excursions', 'twostars-theme'); ?></a></li>
						<li><a href="<?php echo esc_url(home_url('/private-tours/')); ?>"><?php esc_html_e('Private tours', 'twostars-theme'); ?></a></li>
						<li><a href="<?php echo esc_url(home_url('/guides/')); ?>"><?php esc_html_e('Guides', 'twostars-theme'); ?></a></li>
						<?php $ts_google = twostars_theme_tracking_value('google_profile_url'); ?>
						<?php if ($ts_google !== '') : ?><li><a href="<?php echo esc_url($ts_google); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Find us on Google', 'twostars-theme'); ?></a></li><?php endif; ?>
						<li><a href="<?php echo esc_url(home_url('/terms/')); ?>"><?php esc_html_e('Terms', 'twostars-theme'); ?></a></li>
						<li><a href="<?php echo esc_url(home_url('/privacy/')); ?>"><?php esc_html_e('Privacy', 'twostars-theme'); ?></a></li>
					</ul>
				</div>
			</div>
		</footer>
	<?php endif; ?>
</div>
<nav class="twostars-mobile-bar" aria-label="<?php esc_attr_e('Quick booking', 'twostars-theme'); ?>">
	<a class="twostars-mobile-bar__book" href="<?php echo esc_url(home_url('/booking/')); ?>" data-twostars-event="book_now_click"><?php esc_html_e('Book now', 'twostars-theme'); ?></a>
	<a class="twostars-mobile-bar__wa" href="<?php echo esc_url(twostars_theme_get_whatsapp_url()); ?>" target="_blank" rel="noopener noreferrer" data-twostars-event="whatsapp_click"><?php esc_html_e('WhatsApp', 'twostars-theme'); ?></a>
</nav>
<?php wp_footer(); ?>
</body>
</html>
