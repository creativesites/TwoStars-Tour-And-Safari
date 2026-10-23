<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Sitewide TravelAgency (LocalBusiness) schema. NAP must match the footer, Google and Tripadvisor.
 */
function twostars_theme_output_business_schema(): void
{
	$phone = preg_replace('/[^0-9+]/', '', twostars_theme_get_whatsapp_number());

	$schema = [
		'@context'    => 'https://schema.org',
		'@type'       => 'TravelAgency',
		'@id'         => home_url('/#business'),
		'name'        => 'Two Stars Tour & Safari',
		'legalName'   => 'Two Stars Tour & Safari CC',
		'url'         => home_url('/'),
		'email'       => 'info@twostarstourandsafari.com',
		'telephone'   => $phone,
		'identifier'  => 'NTB #TSO01736',
		'description' => 'Women-led day tours from Walvis Bay and Swakopmund, Namibia: small groups, unhurried photo stops and cruise passenger pickups.',
		'address'     => ['@type' => 'PostalAddress', 'addressLocality' => 'Walvis Bay', 'addressCountry' => 'NA'],
		'areaServed'  => ['Walvis Bay', 'Swakopmund', 'Sandwich Harbour', 'Spitzkoppe'],
		'priceRange'  => 'N$2,600+',
		'sameAs'      => [],
	];

	printf("<script type=\"application/ld+json\">%s</script>\n", wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
add_action('wp_head', 'twostars_theme_output_business_schema', 30);

/**
 * Hide [PLACEHOLDER: ...] text from visitors. Editors see it flagged in the tour template.
 */
function twostars_theme_has_placeholder(string $value): bool
{
	return stripos($value, '[PLACEHOLDER') !== false;
}
