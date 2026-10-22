<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

abstract class ContactPageWidgetBase extends StaticPageWidgetBase
{
	protected function get_static_page_slug(): string
	{
		return 'contact';
	}

	protected function get_static_page_control_label(): string
	{
		return __('Contact Page', 'twostars-elementor-widgets');
	}
}
