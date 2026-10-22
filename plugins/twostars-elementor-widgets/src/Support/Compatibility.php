<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Support;

final class Compatibility
{
	public function register(): void
	{
		add_action('admin_notices', [$this, 'render_notice']);
	}

	public function is_elementor_active(): bool
	{
		return did_action('elementor/loaded') || class_exists('\Elementor\Plugin');
	}

	public function render_notice(): void
	{
		if ($this->is_elementor_active() || ! current_user_can('activate_plugins')) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p><?php esc_html_e('Two Stars Elementor Widgets requires Elementor to be installed and active.', 'twostars-elementor-widgets'); ?></p>
		</div>
		<?php
	}
}
