<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

abstract class BookingPageWidgetBase extends StaticPageWidgetBase
{
	protected function get_static_page_slug(): string
	{
		return 'booking';
	}

	protected function get_static_page_control_label(): string
	{
		return __('Booking Page', 'twostars-elementor-widgets');
	}
}
