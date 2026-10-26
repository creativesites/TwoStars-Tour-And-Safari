<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Tracking and profile settings (Settings > Two Stars Tracking).
 * IDs live in one option so they can be set on production without touching code.
 *
 * Analytics (GA4) and the Meta Pixel load only after the visitor accepts the consent banner.
 * The Search Console verification tag is not tracking and is always printed.
 */

function twostars_theme_tracking_fields(): array
{
	return [
		'ga4_id'            => ['label' => __('GA4 Measurement ID', 'twostars-theme'), 'help' => __('Looks like G-XXXXXXXXXX.', 'twostars-theme'), 'pattern' => '/^G-[A-Z0-9]{6,14}$/'],
		'meta_pixel_id'     => ['label' => __('Meta Pixel ID', 'twostars-theme'), 'help' => __('Digits only.', 'twostars-theme'), 'pattern' => '/^\d{6,20}$/'],
		'gsc_verification'  => ['label' => __('Search Console verification code', 'twostars-theme'), 'help' => __('The content value of the google-site-verification tag.', 'twostars-theme'), 'pattern' => '/^[A-Za-z0-9_\-]{20,80}$/'],
		'google_profile_url' => ['label' => __('Google Business Profile link', 'twostars-theme'), 'help' => __('Public profile or share link.', 'twostars-theme'), 'url' => true],
		'google_review_url' => ['label' => __('Google "write a review" link', 'twostars-theme'), 'help' => __('Optional. Shown on the Reviews page.', 'twostars-theme'), 'url' => true],
		'tripadvisor_url'   => ['label' => __('Tripadvisor listing link', 'twostars-theme'), 'help' => __('Hidden on the site until set.', 'twostars-theme'), 'url' => true],
	];
}

function twostars_theme_get_tracking(): array
{
	$saved = get_option('twostars_tracking', []);

	return is_array($saved) ? $saved : [];
}

function twostars_theme_tracking_value(string $key): string
{
	return (string) (twostars_theme_get_tracking()[$key] ?? '');
}

function twostars_theme_register_tracking_settings(): void
{
	register_setting(
		'twostars_tracking_group',
		'twostars_tracking',
		[
			'type'              => 'array',
			'sanitize_callback' => static function ($input): array {
				$clean = [];

				foreach (twostars_theme_tracking_fields() as $key => $field) {
					$value = isset($input[$key]) ? trim((string) $input[$key]) : '';

					if ($value === '') {
						continue;
					}

					if (! empty($field['url'])) {
						$clean[$key] = esc_url_raw($value);
					} elseif (isset($field['pattern']) && preg_match($field['pattern'], $value) === 1) {
						$clean[$key] = $value;
					}
				}

				return $clean;
			},
		]
	);
}
add_action('admin_init', 'twostars_theme_register_tracking_settings');

function twostars_theme_add_tracking_menu(): void
{
	add_options_page(
		__('Two Stars Tracking', 'twostars-theme'),
		__('Two Stars Tracking', 'twostars-theme'),
		'manage_options',
		'twostars-tracking',
		'twostars_theme_render_tracking_page'
	);
}
add_action('admin_menu', 'twostars_theme_add_tracking_menu');

function twostars_theme_render_tracking_page(): void
{
	if (! current_user_can('manage_options')) {
		return;
	}

	$values = twostars_theme_get_tracking();
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Two Stars Tracking and Profiles', 'twostars-theme'); ?></h1>
		<p><?php esc_html_e('Analytics and the Meta Pixel only load after a visitor accepts the cookie banner. Leave a field empty to switch that feature off.', 'twostars-theme'); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields('twostars_tracking_group'); ?>
			<table class="form-table" role="presentation">
				<?php foreach (twostars_theme_tracking_fields() as $key => $field) : ?>
					<tr>
						<th scope="row"><label for="ts-<?php echo esc_attr($key); ?>"><?php echo esc_html($field['label']); ?></label></th>
						<td>
							<input class="regular-text" id="ts-<?php echo esc_attr($key); ?>" name="twostars_tracking[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr((string) ($values[$key] ?? '')); ?>">
							<p class="description"><?php echo esc_html($field['help']); ?></p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/** Search Console ownership tag. */
function twostars_theme_print_verification_tag(): void
{
	$code = twostars_theme_tracking_value('gsc_verification');

	if ($code !== '') {
		printf("<meta name=\"google-site-verification\" content=\"%s\">\n", esc_attr($code));
	}
}
add_action('wp_head', 'twostars_theme_print_verification_tag', 1);

/** Consent banner and tracking loader. */
function twostars_theme_enqueue_tracking(): void
{
	$path = get_template_directory() . '/assets/js/tracking.js';

	wp_enqueue_script(
		'twostars-theme-tracking',
		get_template_directory_uri() . '/assets/js/tracking.js',
		[],
		(string) filemtime($path),
		true
	);

	wp_localize_script(
		'twostars-theme-tracking',
		'twostarsTracking',
		[
			'ga4'       => twostars_theme_tracking_value('ga4_id'),
			'pixel'     => twostars_theme_tracking_value('meta_pixel_id'),
			'privacyUrl' => home_url('/privacy/'),
		]
	);
}
add_action('wp_enqueue_scripts', 'twostars_theme_enqueue_tracking', 25);

/**
 * [twostars_external_reviews]: cards linking to the Google Business Profile and Tripadvisor listing.
 * A card only appears once its link is set in Settings > Two Stars Tracking.
 */
function twostars_theme_external_reviews_shortcode(): string
{
	$cards = [
		[
			'name'  => __('Google', 'twostars-theme'),
			'text'  => __('Read what guests say about Two Stars on Google, and see photos, opening details and directions.', 'twostars-theme'),
			'url'   => twostars_theme_tracking_value('google_profile_url'),
			'cta'   => __('See us on Google', 'twostars-theme'),
			'extra' => twostars_theme_tracking_value('google_review_url'),
			'extra_cta' => __('Write a Google review', 'twostars-theme'),
		],
		[
			'name'  => __('Tripadvisor', 'twostars-theme'),
			'text'  => __('Read and share reviews of our tours on Tripadvisor.', 'twostars-theme'),
			'url'   => twostars_theme_tracking_value('tripadvisor_url'),
			'cta'   => __('See us on Tripadvisor', 'twostars-theme'),
			'extra' => '',
			'extra_cta' => '',
		],
	];

	$cards = array_values(array_filter($cards, static fn(array $c): bool => $c['url'] !== ''));

	if ($cards === []) {
		return '';
	}

	ob_start();
	?>
	<section class="ts-ext-reviews" aria-label="<?php esc_attr_e('Reviews on other sites', 'twostars-theme'); ?>">
		<div class="ts-ext-reviews__inner">
			<?php foreach ($cards as $card) : ?>
				<article class="ts-ext-reviews__card">
					<h3><?php echo esc_html($card['name']); ?></h3>
					<p><?php echo esc_html($card['text']); ?></p>
					<div class="ts-ext-reviews__actions">
						<a class="ts-ext-reviews__btn" href="<?php echo esc_url($card['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($card['cta']); ?></a>
						<?php if ($card['extra'] !== '') : ?>
							<a class="ts-ext-reviews__link" href="<?php echo esc_url($card['extra']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($card['extra_cta']); ?></a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php

	return (string) ob_get_clean();
}
add_shortcode('twostars_external_reviews', 'twostars_theme_external_reviews_shortcode');
