<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

function twostars_theme_enqueue_assets(): void
{
	$theme = wp_get_theme();

	wp_enqueue_style(
		'twostars-theme-style',
		get_stylesheet_uri(),
		[],
		(string) $theme->get('Version')
	);

	wp_enqueue_style(
		'twostars-theme-fonts',
		'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap',
		[],
		null
	);

	wp_enqueue_style(
		'twostars-theme-site',
		get_template_directory_uri() . '/assets/css/site.css',
		['twostars-theme-style', 'twostars-theme-fonts'],
		(string) filemtime(get_template_directory() . '/assets/css/site.css')
	);

	wp_enqueue_script(
		'twostars-theme-site',
		get_template_directory_uri() . '/assets/js/site.js',
		[],
		(string) filemtime(get_template_directory() . '/assets/js/site.js'),
		true
	);

	if (wp_style_is('twostars-elementor-widgets', 'registered')) {
		wp_enqueue_style('twostars-elementor-widgets');
	}

	if (wp_script_is('twostars-elementor-widgets', 'registered')) {
		wp_enqueue_script('twostars-elementor-widgets');
	}

	if (is_page('booking') || (function_exists('twostars_theme_is_named_request') && twostars_theme_is_named_request('booking'))) {
		wp_enqueue_style(
			'twostars-theme-booking',
			get_template_directory_uri() . '/assets/css/booking.css',
			['twostars-theme-site'],
			(string) filemtime(get_template_directory() . '/assets/css/booking.css')
		);

		wp_enqueue_script(
			'twostars-theme-booking',
			get_template_directory_uri() . '/assets/js/booking.js',
			[],
			(string) filemtime(get_template_directory() . '/assets/js/booking.js'),
			true
		);

		$tours = function_exists('twostars_theme_get_booking_tours') ? twostars_theme_get_booking_tours() : [];
		$selected_tour_id = function_exists('twostars_theme_get_booking_prefill_tour_id') ? twostars_theme_get_booking_prefill_tour_id() : 0;
		$pickup_options = class_exists(\TwoStars\Platform\Booking\BookingManager::class)
			? \TwoStars\Platform\Booking\BookingManager::get_pickup_options()
			: [];

		wp_localize_script(
			'twostars-theme-booking',
			'twostarsBookingPage',
			[
				'availabilityUrl' => esc_url_raw(rest_url('twostars/v1/booking/availability')),
				'submitUrl'       => esc_url_raw(rest_url('twostars/v1/booking/create')),
				'tours'           => $tours,
				'pickupOptions'   => $pickup_options,
				'countries'       => function_exists('twostars_theme_get_country_options') ? twostars_theme_get_country_options() : [],
				'selectedTourId'  => $selected_tour_id,
				'startedAt'       => time(),
				'today'           => wp_date('Y-m-d'),
				'originUrl'       => home_url('/booking/'),
				'whatsappUrl'     => function_exists('twostars_theme_get_whatsapp_url') ? twostars_theme_get_whatsapp_url() : home_url('/contact/'),
				'messages'        => [
					'loadingDates' => __('Loading available dates...', 'twostars-theme'),
					'loadingTimes' => __('Loading available times...', 'twostars-theme'),
					'noDates'      => __('No dates are showing for that month yet. Move to another month or ask the team for a private option.', 'twostars-theme'),
					'noTimes'      => __('No times are open for that date. Choose another day or ask for a private option.', 'twostars-theme'),
					'submitting'   => __('Securing your Two Stars booking...', 'twostars-theme'),
					'success'      => __('Your booking request is in. The team will confirm your time by email or phone shortly.', 'twostars-theme'),
					'error'        => __('Something went wrong while saving the booking. Please try again or use WhatsApp.', 'twostars-theme'),
					'calendarHint' => __('Choose a month, select an available day, then pick a time.', 'twostars-theme'),
				],
			]
		);
	}
}

add_action('wp_enqueue_scripts', 'twostars_theme_enqueue_assets', 20);
