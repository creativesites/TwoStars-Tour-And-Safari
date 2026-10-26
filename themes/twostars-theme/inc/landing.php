<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use TwoStars\Platform\Support\TourData;

/**
 * Helpers for the landing pages (shore excursions, private tours) and legal pages.
 * Media is looked up by file name so the same templates work on every copy of the site.
 */

function twostars_theme_media_id(string $basename): int
{
	static $cache = [];
	$key = strtolower($basename);

	if (isset($cache[$key])) {
		return $cache[$key];
	}

	global $wpdb;
	$id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
			'%/' . $wpdb->esc_like($basename)
		)
	);

	return $cache[$key] = $id;
}

function twostars_theme_media_url(string $basename, string $size = 'full'): string
{
	$id = twostars_theme_media_id($basename);

	return $id > 0 ? (string) wp_get_attachment_image_url($id, $size) : '';
}

function twostars_theme_media_img(string $basename, string $size = 'large', array $attr = []): string
{
	$id = twostars_theme_media_id($basename);

	return $id > 0 ? wp_get_attachment_image($id, $size, false, $attr + ['loading' => 'lazy', 'decoding' => 'async']) : '';
}

function twostars_theme_wa_link(string $text = ''): string
{
	$url = twostars_theme_get_whatsapp_url();

	return $text !== '' ? add_query_arg('text', rawurlencode($text), $url) : $url;
}

/** @param array<int, array{0:string,1:string}> $faqs */
function twostars_theme_faq_schema(array $faqs, string $url): string
{
	$graph = [
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'url'        => $url,
		'mainEntity' => array_map(
			static fn(array $f): array => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]],
			$faqs
		),
	];

	return '<script type="application/ld+json">' . wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

/** Compact tour cards: photo, title, price, link. */
function twostars_theme_tour_mini_cards(array $slugs): string
{
	$html = '';

	foreach ($slugs as $slug) {
		$tour = get_page_by_path($slug, OBJECT, 'tour');

		if (! $tour instanceof WP_Post) {
			continue;
		}

		$thumb = get_post_thumbnail_id($tour->ID);
		$img   = $thumb ? wp_get_attachment_image($thumb, 'medium_large', false, ['loading' => 'lazy', 'decoding' => 'async']) : '';
		$price = class_exists(TourData::class) ? TourData::get_price_label($tour->ID) : '';
		$hook  = (string) get_post_meta($tour->ID, '_twostars_tour_hook', true);

		$html .= sprintf(
			'<a class="ts-mini" href="%1$s"><span class="ts-mini__media">%2$s</span><span class="ts-mini__body"><strong>%3$s</strong><span class="ts-mini__hook">%4$s</span><span class="ts-mini__price">%5$s</span></span></a>',
			esc_url(get_permalink($tour)),
			$img,
			esc_html(get_the_title($tour)),
			esc_html($hook),
			esc_html($price)
		);
	}

	return '<div class="ts-minis">' . $html . '</div>';
}

function twostars_theme_landing_assets(): void
{
	$slugs = ['walvis-bay-shore-excursions', 'private-tours', 'terms', 'privacy'];

	if (! is_page($slugs)) {
		return;
	}

	$path = get_template_directory() . '/assets/css/landing.css';
	wp_enqueue_style('twostars-theme-landing', get_template_directory_uri() . '/assets/css/landing.css', ['twostars-theme-site'], (string) filemtime($path));
}
add_action('wp_enqueue_scripts', 'twostars_theme_landing_assets', 30);

/** [twostars_cruise_banner]: slim banner for cruise passengers (home page). */
function twostars_theme_cruise_banner_shortcode(): string
{
	$img = twostars_theme_media_url('flamingos-wading-walvis-bay-lagoon.webp', 'large');

	ob_start();
	?>
	<section class="ts-cruise-banner">
		<div class="ts-cruise-banner__inner">
			<?php if ($img !== '') : ?><span class="ts-cruise-banner__media" style="background-image:url('<?php echo esc_url($img); ?>')" aria-hidden="true"></span><?php endif; ?>
			<div class="ts-cruise-banner__copy">
				<p class="ts-cruise-banner__eyebrow"><?php esc_html_e('Cruise passengers', 'twostars-theme'); ?></p>
				<h2><?php esc_html_e('In port at Walvis Bay? We meet you at the cruise port.', 'twostars-theme'); ?></h2>
				<p><?php esc_html_e('Small-group days planned around your ship\'s arrival and all-aboard times.', 'twostars-theme'); ?></p>
			</div>
			<a class="ts-cruise-banner__btn" href="<?php echo esc_url(home_url('/walvis-bay-shore-excursions/')); ?>"><?php esc_html_e('Shore excursions', 'twostars-theme'); ?></a>
		</div>
	</section>
	<?php

	return (string) ob_get_clean();
}
add_shortcode('twostars_cruise_banner', 'twostars_theme_cruise_banner_shortcode');
