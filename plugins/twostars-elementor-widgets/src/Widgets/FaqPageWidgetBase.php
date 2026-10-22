<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

abstract class FaqPageWidgetBase extends StaticPageWidgetBase
{
	protected function get_static_page_slug(): string
	{
		return 'faqs';
	}

	protected function get_static_page_control_label(): string
	{
		return __('FAQ Page', 'twostars-elementor-widgets');
	}
}
