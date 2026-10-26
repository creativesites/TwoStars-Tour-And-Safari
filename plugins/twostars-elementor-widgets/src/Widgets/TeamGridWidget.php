<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;

final class TeamGridWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-team-grid';
	}

	public function get_title(): string
	{
		return __('Team Grid', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-person';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Display', 'twostars-elementor-widgets')]);
		$this->add_control('heading', ['label' => __('Heading', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('The People Behind The Routes', 'twostars-elementor-widgets')]);
		$this->add_control('posts_to_show', ['label' => __('Team Members To Show', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 12]);
		$this->add_control('group_image', ['label' => __('Group Photo (shown beside a single team member)', 'twostars-elementor-widgets'), 'type' => Controls_Manager::MEDIA]);
		$this->add_control('group_caption', ['label' => __('Group Photo Caption', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$members = TourContent::get_team_members(['posts_per_page' => absint($settings['posts_to_show'] ?? 3)]);

		if ($members === []) {
			return;
		}
		?>
		<?php
		$solo      = count($members) === 1;
		$group_id  = absint($settings['group_image']['id'] ?? 0);
		?>
		<section class="twostars-team-grid<?php echo $solo ? ' twostars-team-grid--solo' : ''; ?>" data-twostars-animate="fade-in-up">
			<h2 class="twostars-team-grid__title"><?php echo esc_html((string) ($settings['heading'] ?? '')); ?></h2>
			<div class="twostars-team-grid__cards">
				<?php if ($solo && $group_id > 0) : ?>
					<figure class="twostars-team-grid__group">
						<?php echo wp_get_attachment_image($group_id, 'large', false, ['loading' => 'lazy', 'decoding' => 'async']); ?>
						<?php if (! empty($settings['group_caption'])) : ?><figcaption><?php echo esc_html((string) $settings['group_caption']); ?></figcaption><?php endif; ?>
					</figure>
				<?php endif; ?>
				<?php foreach ($members as $index => $member) : ?>
					<?php $image_id = get_post_thumbnail_id($member->ID); ?>
					<article class="twostars-team-grid__card" data-twostars-animate="fade-in-up" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.14, 2)); ?>s;">
						<?php if ($image_id > 0 || ! $solo) : ?>
						<div class="twostars-team-grid__media">
							<?php if ($image_id > 0) : ?>
								<?php echo wp_get_attachment_image($image_id, 'medium_large', false, ['loading' => 'lazy']); ?>
							<?php else : ?>
								<div class="twostars-team-grid__placeholder"></div>
							<?php endif; ?>
						</div>
						<?php endif; ?>
						<div class="twostars-team-grid__body">
							<?php if ($solo && $image_id === 0) : ?><span class="twostars-team-grid__monogram" aria-hidden="true"><?php echo esc_html(mb_strtoupper(mb_substr(get_the_title($member), 0, 1))); ?></span><?php endif; ?>
							<h3><?php echo esc_html(get_the_title($member)); ?></h3>
							<p class="twostars-team-grid__role"><?php echo esc_html(get_the_excerpt($member)); ?></p>
							<p><?php echo esc_html($solo ? wp_strip_all_tags($member->post_content) : wp_trim_words(wp_strip_all_tags($member->post_content), 24, '...')); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
