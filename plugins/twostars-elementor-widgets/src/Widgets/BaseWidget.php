<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Widget_Base;

abstract class BaseWidget extends Widget_Base
{
	/**
	 * @return array<string, string>
	 */
	protected function get_lucide_icon_options(): array
	{
		return [
			'check-circle'  => __('Check Circle', 'twostars-elementor-widgets'),
			'map-pinned'    => __('Map Pinned', 'twostars-elementor-widgets'),
			'users'         => __('Users', 'twostars-elementor-widgets'),
			'clock-3'       => __('Clock', 'twostars-elementor-widgets'),
			'camera'        => __('Camera', 'twostars-elementor-widgets'),
			'star'          => __('Star', 'twostars-elementor-widgets'),
			'compass'       => __('Compass', 'twostars-elementor-widgets'),
			'waves'         => __('Waves', 'twostars-elementor-widgets'),
			'route'         => __('Route', 'twostars-elementor-widgets'),
			'mountain'      => __('Mountain', 'twostars-elementor-widgets'),
			'shield-check'  => __('Shield Check', 'twostars-elementor-widgets'),
			'messages-square' => __('Messages', 'twostars-elementor-widgets'),
			'leaf'          => __('Leaf', 'twostars-elementor-widgets'),
			'phone'         => __('Phone', 'twostars-elementor-widgets'),
			'calendar-check' => __('Calendar Check', 'twostars-elementor-widgets'),
		];
	}

	public function get_categories(): array
	{
		return ['twostars'];
	}

	public function get_style_depends(): array
	{
		$styles = ['twostars-elementor-widgets'];
		$widget_style = $this->register_widget_style();

		if ($widget_style !== null) {
			$styles[] = $widget_style;
		}

		return array_values(array_unique($styles));
	}

	public function get_script_depends(): array
	{
		return ['twostars-elementor-widgets'];
	}

	protected function widget_dom_id(string $suffix = ''): string
	{
		$id = sanitize_html_class($this->get_name() . '-' . $this->get_id());

		if ($suffix === '') {
			return $id;
		}

		return $id . '-' . sanitize_html_class($suffix);
	}

	protected function format_text(string $text): string
	{
		return nl2br(esc_html(trim($text)));
	}

	protected function render_highlighted_text(string $text, string $highlight, string $class_name): string
	{
		$text      = trim($text);
		$highlight = trim($highlight);

		if ($text === '') {
			return '';
		}

		if ($highlight === '' || stripos($text, $highlight) === false) {
			return nl2br(esc_html($text));
		}

		$offset = (int) stripos($text, $highlight);
		$before = substr($text, 0, $offset);
		$match  = substr($text, $offset, strlen($highlight));
		$after  = substr($text, $offset + strlen($highlight));

		return nl2br(esc_html($before))
			. '<span class="' . esc_attr($class_name) . '">' . esc_html($match) . '</span>'
			. nl2br(esc_html($after));
	}

	protected function build_inline_vars(array $vars): string
	{
		$parts = [];

		foreach ($vars as $name => $value) {
			if ($value === null || $value === '') {
				continue;
			}

			$clean_value = str_replace([';', "\n", "\r"], '', (string) $value);
			$parts[]     = $name . ':' . $clean_value;
		}

		return implode(';', $parts);
	}

	protected function link_attributes(array $link): string
	{
		if (empty($link['url'])) {
			return '';
		}

		$attributes = [
			'href="' . esc_url((string) $link['url']) . '"',
		];

		$rel = [];

		if (! empty($link['is_external'])) {
			$attributes[] = 'target="_blank"';
			$rel[]        = 'noopener';
		}

		if (! empty($link['nofollow'])) {
			$rel[] = 'nofollow';
		}

		if (! empty($rel)) {
			$attributes[] = 'rel="' . esc_attr(implode(' ', array_unique($rel))) . '"';
		}

		return implode(' ', $attributes);
	}

	protected function render_lucide_icon(string $icon_name, string $class_name = ''): string
	{
		$icon_name = sanitize_key($icon_name);
		$svg = '';

		switch ($icon_name) {
			case 'map-pinned':
				$svg = '<path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Z"></path><circle cx="12" cy="10" r="2.5"></circle>';
				break;
			case 'users':
				$svg = '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="10" cy="7" r="4"></circle><path d="M20 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>';
				break;
			case 'clock-3':
				$svg = '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v6h4"></path>';
				break;
			case 'camera':
				$svg = '<path d="M14.5 4H20a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.5"></path><path d="M9 4h6l1.5 3h-9L9 4Z"></path><circle cx="12" cy="12" r="3.5"></circle>';
				break;
			case 'star':
				$svg = '<path d="m12 3 2.9 5.88 6.5.95-4.7 4.58 1.1 6.46L12 17.8 6.2 20.87l1.1-6.46-4.7-4.58 6.5-.95L12 3Z"></path>';
				break;
			case 'compass':
				$svg = '<circle cx="12" cy="12" r="9"></circle><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36 6.36-2.12Z"></path>';
				break;
			case 'waves':
				$svg = '<path d="M2 6c2 0 2 2 4 2s2-2 4-2 2 2 4 2 2-2 4-2 2 2 4 2"></path><path d="M2 12c2 0 2 2 4 2s2-2 4-2 2 2 4 2 2-2 4-2 2 2 4 2"></path><path d="M2 18c2 0 2 2 4 2s2-2 4-2 2 2 4 2 2-2 4-2 2 2 4 2"></path>';
				break;
			case 'route':
				$svg = '<circle cx="6" cy="19" r="2"></circle><path d="M9 19h5a2 2 0 0 0 2-2v-5"></path><circle cx="18" cy="5" r="2"></circle><path d="M15 5H10a2 2 0 0 0-2 2v5"></path>';
				break;
			case 'mountain':
				$svg = '<path d="m8 3 4 6 5-4 5 16H2L8 3Z"></path><path d="m2 21 6-9 4 5"></path>';
				break;
			case 'shield-check':
				$svg = '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path><path d="m9 12 2 2 4-4"></path>';
				break;
			case 'messages-square':
				$svg = '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><path d="M8 9h8"></path><path d="M8 13h5"></path>';
				break;
			case 'leaf':
				$svg = '<path d="M11 20A7 7 0 0 1 4 13C4 6 12 3 20 4c1 8-2 16-9 16Z"></path><path d="M11 20c-1-5 1-9 6-13"></path>';
				break;
			case 'phone':
				$svg = '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.63 2.61a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6.27 6.27l1.29-1.29a2 2 0 0 1 2.11-.45c.83.3 1.71.51 2.61.63A2 2 0 0 1 22 16.92Z"></path>';
				break;
			case 'calendar-check':
				$svg = '<path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M3 10h18"></path><path d="m9.5 16 2 2 4-4"></path>';
				break;
			case 'check-circle':
			default:
				$svg = '<circle cx="12" cy="12" r="9"></circle><path d="m9 12 2 2 4-4"></path>';
				break;
		}

		$class_attr = $class_name !== '' ? ' class="' . esc_attr($class_name) . '"' : '';

		return '<svg' . $class_attr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $svg . '</svg>';
	}

	private function register_widget_style(): ?string
	{
		$slug = preg_replace('/^twostars-/', '', $this->get_name());

		if (! is_string($slug) || $slug === '') {
			return null;
		}

		$relative_path = 'assets/css/widgets/' . $slug . '.css';
		$path = TWOSTARS_ELEMENTOR_WIDGETS_PATH . $relative_path;

		if (! file_exists($path)) {
			return null;
		}

		$handle = 'twostars-elementor-widget-' . sanitize_key($slug);

		if (! wp_style_is($handle, 'registered')) {
			wp_register_style(
				$handle,
				TWOSTARS_ELEMENTOR_WIDGETS_URL . $relative_path,
				['twostars-elementor-widgets'],
				(string) filemtime($path)
			);
		}

		return $handle;
	}
}
