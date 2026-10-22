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
							<h2 class="site-footer__title"><?php esc_html_e('Adventure Beyond the Horizon', 'twostars-theme'); ?></h2>
						</div>
					</div>
					<p class="site-footer__text">
						<?php esc_html_e('Elegant coastal and desert day tours from Walvis Bay and Swakopmund, shaped around scenic stops, local insight, flexible planning, and comfortable 4x4 experiences.', 'twostars-theme'); ?>
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
					<p class="site-footer__cta-text"><?php esc_html_e('Tell us which tour caught your eye or ask for a private option and we will help you choose the right next step.', 'twostars-theme'); ?></p>
					<a class="tour-booking-button site-footer__button" href="<?php echo esc_url(home_url('/booking/')); ?>">
						<?php esc_html_e('Book Your Tour', 'twostars-theme'); ?>
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
								__('© %d Two Stars Tour and Safari', 'twostars-theme'),
								(int) gmdate('Y')
							)
						);
						?>
					</p>
					<p class="site-footer__legal"><?php esc_html_e('Walvis Bay and Swakopmund coastal tours, Sandwich Harbour experiences, and private day-trip planning.', 'twostars-theme'); ?></p>
				</div>
			</div>
		</footer>
	<?php endif; ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
