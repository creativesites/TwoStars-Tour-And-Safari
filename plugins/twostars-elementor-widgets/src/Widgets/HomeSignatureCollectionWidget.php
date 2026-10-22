<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;
use TwoStars\Platform\Support\TourData;
use WP_Post;

final class HomeSignatureCollectionWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-home-signature-collection';
	}

	public function get_title(): string
	{
		return __('Homepage Signature Collection', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-posts-grid';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('eyebrow', ['label' => __('Eyebrow', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Signature Routes', 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Show visitors the few itineraries that define the brand best.', 'twostars-elementor-widgets')]);
		$this->add_control('intro', ['label' => __('Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('This section should narrow choice, not expand it. The goal is to guide visitors toward the strongest route shapes first.', 'twostars-elementor-widgets')]);
		$this->add_control('source', ['label' => __('Source', 'twostars-elementor-widgets'), 'type' => Controls_Manager::SELECT, 'options' => ['auto' => __('Auto latest tours', 'twostars-elementor-widgets'), 'selected' => __('Selected tours', 'twostars-elementor-widgets')], 'default' => 'auto']);
		$this->add_control('tour_limit', ['label' => __('Auto Limit', 'twostars-elementor-widgets'), 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 6, 'condition' => ['source' => 'auto']]);
		$this->add_control('selected_tours', ['label' => __('Selected Tours', 'twostars-elementor-widgets'), 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => TourContent::get_tour_options(), 'condition' => ['source' => 'selected']]);
		$this->add_control('strip_copy', ['label' => __('Planning Strip Copy', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('The site can surface the strongest route based on what the visitor has already explored.', 'twostars-elementor-widgets')]);
		$this->add_control('strip_button_label', ['label' => __('Strip Button Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Ask The Concierge', 'twostars-elementor-widgets')]);
		$this->add_control('strip_fallback_link', ['label' => __('Strip Fallback Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/contact/')]]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$tours    = $this->resolve_tours($settings);
		?>
		<section class="twostars-home-collection">
			<div class="twostars-home-collection__header" data-twostars-animate="fade-in-up">
				<?php if (! empty($settings['eyebrow'])) : ?><p class="twostars-home-section-eyebrow"><?php echo esc_html((string) $settings['eyebrow']); ?></p><?php endif; ?>
				<?php if (! empty($settings['title'])) : ?><h2 class="twostars-home-section-title"><?php echo nl2br(esc_html((string) $settings['title'])); ?></h2><?php endif; ?>
				<?php if (! empty($settings['intro'])) : ?><p class="twostars-home-collection__intro"><?php echo esc_html((string) $settings['intro']); ?></p><?php endif; ?>
			</div>

			<div class="twostars-home-collection__grid" data-twostars-home-collection>
				<?php foreach ($tours as $index => $tour) : ?>
					<?php
					$summary_items = class_exists(TourData::class) ? TourData::get_summary_items($tour->ID) : [];
					$image_id      = get_post_thumbnail_id($tour->ID);
					?>
					<article
						class="twostars-home-collection__card"
						data-twostars-home-collection-card
						data-tour-id="<?php echo esc_attr((string) $tour->ID); ?>"
						data-content-type="tour"
						data-twostars-animate="fade-in-up"
						style="--twostars-delay:<?php echo esc_attr((string) number_format($index * 0.12, 2)); ?>s;"
					>
						<div class="twostars-home-collection__card-media">
							<?php if ($image_id > 0) : ?>
								<?php echo wp_get_attachment_image($image_id, 'large', false, ['loading' => 'lazy']); ?>
							<?php else : ?>
								<div class="twostars-home-collection__card-placeholder"></div>
							<?php endif; ?>
						</div>
						<div class="twostars-home-collection__card-body">
							<span class="twostars-home-collection__card-badge" data-twostars-home-collection-badge><?php esc_html_e('Signature Route', 'twostars-elementor-widgets'); ?></span>
							<h3><?php echo esc_html(get_the_title($tour)); ?></h3>
							<p><?php echo esc_html(get_the_excerpt($tour)); ?></p>
							<?php if (! empty($summary_items)) : ?>
								<ul class="twostars-home-collection__meta">
									<?php foreach (array_slice($summary_items, 0, 3) as $item) : ?>
										<li><span><?php echo esc_html($item['label']); ?></span><strong><?php echo esc_html($item['value']); ?></strong></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<a class="twostars-safari-button twostars-safari-button--ghost" href="<?php echo esc_url(get_permalink($tour)); ?>">
								<?php esc_html_e('View Route', 'twostars-elementor-widgets'); ?>
							</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<div
				class="twostars-home-collection__strip"
				data-twostars-home-collection-strip
				data-default-copy="<?php echo esc_attr((string) ($settings['strip_copy'] ?? '')); ?>"
				data-default-label="<?php echo esc_attr((string) ($settings['strip_button_label'] ?? '')); ?>"
				data-fallback-url="<?php echo esc_url((string) ($settings['strip_fallback_link']['url'] ?? home_url('/contact/'))); ?>"
				data-twostars-animate="fade-in-up"
			>
				<p class="twostars-home-collection__strip-copy" data-twostars-home-collection-copy><?php echo esc_html((string) ($settings['strip_copy'] ?? '')); ?></p>
				<button type="button" class="twostars-safari-button twostars-safari-button--primary" data-twostars-assistant-trigger>
					<?php echo esc_html((string) ($settings['strip_button_label'] ?? __('Ask The Concierge', 'twostars-elementor-widgets'))); ?>
				</button>
			</div>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<int, WP_Post>
	 */
	private function resolve_tours(array $settings): array
	{
		if (($settings['source'] ?? 'auto') === 'selected') {
			$selected = array_values(array_filter(array_map('absint', (array) ($settings['selected_tours'] ?? []))));

			if (! empty($selected)) {
				return TourContent::get_tours(['post__in' => $selected, 'orderby' => 'post__in', 'posts_per_page' => count($selected)]);
			}
		}

		$limit = max(1, (int) ($settings['tour_limit'] ?? 3));

		return array_slice(TourContent::get_tours(['posts_per_page' => $limit]), 0, $limit);
	}
}
