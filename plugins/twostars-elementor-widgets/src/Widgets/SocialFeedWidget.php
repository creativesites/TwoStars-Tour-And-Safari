<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;

final class SocialFeedWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-social-feed';
	}

	public function get_title(): string
	{
		return __('Social Feed', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-social-icons';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Content', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => __('Section Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Recent Safari Moments', 'twostars-elementor-widgets'),
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$items    = apply_filters('twostars_platform/social_feed_items', []);
		?>
		<section class="twostars-widget-feed">
			<h3 class="twostars-widget-feed__title"><?php echo esc_html((string) ($settings['title'] ?? '')); ?></h3>
			<div class="twostars-widget-feed__grid">
				<?php foreach ($items as $item) : ?>
					<a class="twostars-widget-feed__item" href="<?php echo esc_url((string) ($item['url'] ?? home_url('/'))); ?>">
						<strong><?php echo esc_html((string) ($item['title'] ?? '')); ?></strong>
						<span><?php echo esc_html((string) ($item['text'] ?? '')); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
