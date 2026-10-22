<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\ElementorWidgets\Support\TourContent;

final class ToursFilterBarWidget extends BaseWidget
{
	private const TAXONOMIES = [
		'tour_type'     => 'Style',
		'tour_region'   => 'Region',
		'tour_duration' => 'Duration',
		'tour_activity' => 'Activity',
	];

	public function get_name(): string
	{
		return 'twostars-tours-filter-bar';
	}

	public function get_title(): string
	{
		return __('Tours Filter Bar', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-filter';
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
			'group_id',
			[
				'label'   => __('Filter Group ID', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => 'tours-main',
			]
		);

		$this->add_control(
			'label',
			[
				'label'   => __('Intro Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Browse by priority', 'twostars-elementor-widgets'),
			]
		);

		foreach (self::TAXONOMIES as $taxonomy => $label) {
			$this->add_control(
				'enable_' . $taxonomy,
				[
					'label'        => sprintf(__('Show %s Filter', 'twostars-elementor-widgets'), $label),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __('Yes', 'twostars-elementor-widgets'),
					'label_off'    => __('No', 'twostars-elementor-widgets'),
					'return_value' => 'yes',
					'default'      => $taxonomy !== 'tour_activity' ? 'yes' : '',
				]
			);
		}

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$group_id = sanitize_title((string) ($settings['group_id'] ?? 'tours-main'));
		?>
		<section class="twostars-tours-filters" data-twostars-filter-bar data-filter-group="<?php echo esc_attr($group_id); ?>" data-twostars-animate="fade-in-up">
			<?php if (! empty($settings['label'])) : ?>
				<p class="twostars-tours-filters__label"><?php echo esc_html((string) $settings['label']); ?></p>
			<?php endif; ?>
			<div class="twostars-tours-filters__groups">
				<?php foreach (self::TAXONOMIES as $taxonomy => $title) : ?>
					<?php if (($settings['enable_' . $taxonomy] ?? '') !== 'yes') : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php $terms = TourContent::get_terms($taxonomy); ?>
					<?php if ($terms === []) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<div class="twostars-tours-filters__group">
						<span class="twostars-tours-filters__heading"><?php echo esc_html($title); ?></span>
						<div class="twostars-tours-filters__chips">
							<button type="button" class="twostars-tours-filters__chip is-active" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" data-term="">
								<?php esc_html_e('All', 'twostars-elementor-widgets'); ?>
							</button>
							<?php foreach ($terms as $term) : ?>
								<button type="button" class="twostars-tours-filters__chip" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" data-term="<?php echo esc_attr($term->slug); ?>">
									<?php echo esc_html($term->name); ?>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
