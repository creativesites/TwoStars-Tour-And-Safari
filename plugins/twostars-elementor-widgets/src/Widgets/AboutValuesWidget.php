<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

final class AboutValuesWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-about-values';
	}

	public function get_title(): string
	{
		return __('About Values Grid', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-icon-box';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Values', 'twostars-elementor-widgets')]);
		$this->add_control('section_title', ['label' => __('Section Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Why Choose Two Stars', 'twostars-elementor-widgets')]);
		$this->add_control('section_subtitle', ['label' => __('Section Subtitle', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Elegant coastal and desert day tours shaped by local knowledge, personal pacing, and clear service from pickup to drop-off.', 'twostars-elementor-widgets')]);
		$this->add_control(
			'columns',
			[
				'label'   => __('Columns', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'2' => __('2 Columns', 'twostars-elementor-widgets'),
					'3' => __('3 Columns', 'twostars-elementor-widgets'),
				],
				'default' => '3',
			]
		);

		$repeater = new Repeater();
		$repeater->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Local Judgment', 'twostars-elementor-widgets')]);
		$repeater->add_control('description', ['label' => __('Description', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('We shape itineraries around what actually works on the ground, not just what looks good on paper.', 'twostars-elementor-widgets')]);
		$this->add_control(
			'values',
			[
				'label' => __('Value Cards', 'twostars-elementor-widgets'),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default' => [
					['title' => __('Local Expertise', 'twostars-elementor-widgets'), 'description' => __('We guide the coast from a Walvis Bay base, with local knowledge of Sandwich Harbour, the lagoon, Swakopmund, Dune 7, and Spitzkoppe.', 'twostars-elementor-widgets')],
					['title' => __('Small Groups, Big Experiences', 'twostars-elementor-widgets'), 'description' => __('The pace stays personal, scenic, and comfortable so you can enjoy the journey instead of being rushed through it.', 'twostars-elementor-widgets')],
					['title' => __('Flexible & Personal', 'twostars-elementor-widgets'), 'description' => __('Shared departures and private options make it easy to choose a day that fits your style, timing, and group needs.', 'twostars-elementor-widgets')],
					['title' => __('Photography-Focused', 'twostars-elementor-widgets'), 'description' => __('We plan around scenic stops, changing light, and the kind of moments guests actually want to capture.', 'twostars-elementor-widgets')],
					['title' => __('Comfort & Care', 'twostars-elementor-widgets'), 'description' => __('Clean vehicles, thoughtful pacing, light refreshments where included, and attentive guiding shape the full day.', 'twostars-elementor-widgets')],
					['title' => __('Authentic Connection', 'twostars-elementor-widgets'), 'description' => __('Guests leave with context, stories, and a stronger sense of place, not just a checklist of stops.', 'twostars-elementor-widgets')],
				],
			]
		);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		$columns = in_array((string) ($settings['columns'] ?? '3'), ['2', '3'], true) ? (string) $settings['columns'] : '3';
		?>
		<section class="twostars-about-values">
			<div class="twostars-about-values__header" data-twostars-animate="fade-in-up">
				<?php if (! empty($settings['section_title'])) : ?>
					<h2 class="twostars-about-values__title"><?php echo esc_html((string) $settings['section_title']); ?></h2>
				<?php endif; ?>
				<?php if (! empty($settings['section_subtitle'])) : ?>
					<p class="twostars-about-values__subtitle"><?php echo esc_html((string) $settings['section_subtitle']); ?></p>
				<?php endif; ?>
			</div>
			<div class="twostars-about-values__grid twostars-about-values__grid--cols-<?php echo esc_attr($columns); ?>">
				<?php foreach (($settings['values'] ?? []) as $index => $item) : ?>
					<article class="twostars-about-values__card" style="--twostars-delay:<?php echo esc_attr((string) number_format(($index % 3) * 0.14, 2)); ?>s;" data-twostars-animate="fade-in-up">
						<div class="twostars-about-values__index" aria-hidden="true">
							<span><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
						</div>
						<p class="twostars-about-values__eyebrow"><?php esc_html_e('Why Guests Choose Us', 'twostars-elementor-widgets'); ?></p>
						<h3><?php echo esc_html((string) ($item['title'] ?? '')); ?></h3>
						<p><?php echo esc_html((string) ($item['description'] ?? '')); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
