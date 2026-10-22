<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use TwoStars\ElementorWidgets\Support\TourContent;
use WP_Post;

final class ExperienceCardsWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-experience-cards';
	}

	public function get_title(): string
	{
		return __('Safari Experience Cards', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-gallery-grid';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Cards', 'twostars-elementor-widgets'),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'image',
			[
				'label' => __('Background Image', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::MEDIA,
			]
		);

		$repeater->add_control(
			'title',
			[
				'label'   => __('Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Fly-In Desert Escape', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'description',
			[
				'label'   => __('Description', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('A polished route for travelers who want silence, space, and dramatic desert perspective.', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'duration',
			[
				'label'   => __('Duration', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('10 Days', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'group_size',
			[
				'label'   => __('Group Size', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('2-8 Travelers', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'price',
			[
				'label'   => __('Price', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('From $2,400', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'link',
			[
				'label' => __('Link', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::URL,
			]
		);

		$repeater->add_control(
			'tour_id',
			[
				'label'   => __('Related Tour', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => TourContent::get_tour_options(),
				'default' => '',
			]
		);

		$repeater->add_control(
			'destination_id',
			[
				'label'   => __('Related Destination', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => TourContent::get_destination_options(),
				'default' => '',
			]
		);

		$repeater->add_control(
			'intelligence_label',
			[
				'label' => __('Badge Label', 'twostars-elementor-widgets'),
				'type'  => Controls_Manager::TEXT,
			]
		);

		$this->add_control(
			'content_source',
			[
				'label'   => __('Content Source', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'manual'       => __('Manual cards', 'twostars-elementor-widgets'),
					'tours'        => __('Auto: tours', 'twostars-elementor-widgets'),
					'destinations' => __('Auto: destinations', 'twostars-elementor-widgets'),
				],
				'default' => 'manual',
			]
		);

		$this->add_control(
			'cards',
			[
				'label'       => __('Experience Cards', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => [
					[
						'title'       => __('Sandwich Harbour', 'twostars-elementor-widgets'),
						'description' => __('Coastal dune driving, Atlantic views, and scenic stops built for memorable photography.', 'twostars-elementor-widgets'),
					],
					[
						'title'       => __('Flamingo & Pink Lake', 'twostars-elementor-widgets'),
						'description' => __('A lighter scenic outing focused on birdlife, colour, and relaxed time around Walvis Bay.', 'twostars-elementor-widgets'),
					],
					[
						'title'       => __('Private Touring', 'twostars-elementor-widgets'),
						'description' => __('A flexible way to explore with a private vehicle, tailored pacing, and a more personal experience.', 'twostars-elementor-widgets'),
					],
				],
				'condition'   => [
					'content_source' => 'manual',
				],
			]
		);

		$this->add_control(
			'auto_limit',
			[
				'label'     => __('Auto Item Limit', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 12,
				'condition' => [
					'content_source!' => 'manual',
				],
			]
		);

		$this->add_control(
			'desktop_columns',
			[
				'label'   => __('Desktop Columns', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'2' => '2',
					'3' => '3',
					'4' => '4',
				],
				'default' => '3',
			]
		);

		$this->add_control(
			'card_height',
			[
				'label'      => __('Card Height', 'twostars-elementor-widgets'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => [
						'min' => 320,
						'max' => 680,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 500,
				],
			]
		);

		$this->add_control(
			'show_intelligence_badges',
			[
				'label'        => __('Show Intelligence Badges', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings     = $this->get_settings_for_display();
		$cards        = $this->resolve_cards($settings);
		$columns      = max(1, min(4, (int) ($settings['desktop_columns'] ?? 3)));
		$card_height  = (int) (($settings['card_height']['size'] ?? 500));
		$show_badges  = ($settings['show_intelligence_badges'] ?? 'yes') === 'yes';
		$inline_style = $this->build_inline_vars(
			[
				'--twostars-experience-columns' => 'repeat(' . $columns . ', minmax(0, 1fr))',
				'--twostars-experience-height'  => $card_height . 'px',
			]
		);
		?>
		<section
			class="twostars-experience-grid"
			data-twostars-experience-root
			<?php if ($inline_style !== '') : ?>style="<?php echo esc_attr($inline_style); ?>"<?php endif; ?>
		>
			<?php foreach ($cards as $index => $card) : ?>
				<?php
				$has_link = ! empty($card['link']['url']);
				$tag      = $has_link ? 'a' : 'article';
				?>
				<<?php echo esc_html($tag); ?>
					class="twostars-experience-card"
					data-twostars-experience-card
					data-content-type="<?php echo esc_attr((string) ($card['content_type'] ?? 'manual')); ?>"
					data-tour-id="<?php echo esc_attr((string) absint($card['tour_id'] ?? 0)); ?>"
					data-destination-id="<?php echo esc_attr((string) absint($card['destination_id'] ?? 0)); ?>"
					<?php if ($has_link) : ?>
						<?php echo $this->link_attributes((array) $card['link']); ?>
					<?php endif; ?>
					data-twostars-animate="fade-in-up"
					style="--twostars-delay:<?php echo esc_attr((string) number_format($index * 0.2, 1)); ?>s;"
				>
					<div class="twostars-experience-card__media" aria-hidden="true">
						<?php if (! empty($card['image']['id'])) : ?>
							<?php echo wp_get_attachment_image(absint($card['image']['id']), 'large', false, ['loading' => 'lazy']); ?>
						<?php else : ?>
							<div class="twostars-experience-card__placeholder"></div>
						<?php endif; ?>
					</div>
					<div class="twostars-experience-card__overlay"></div>
					<div class="twostars-experience-card__content">
						<?php if ($show_badges) : ?>
							<span class="twostars-experience-card__badge<?php echo ! empty($card['intelligence_label']) ? ' is-visible' : ''; ?>" data-twostars-experience-badge>
								<?php echo esc_html((string) ($card['intelligence_label'] ?? '')); ?>
							</span>
						<?php endif; ?>
						<?php if (! empty($settings['cta_text'])) : ?>
							<span class="twostars-experience-card__cta">
								<?php echo esc_html((string) $settings['cta_text']); ?>
							</span>
						<?php endif; ?>

						<?php if (! empty($card['title'])) : ?>
							<h3 class="twostars-experience-card__title"><?php echo esc_html((string) $card['title']); ?></h3>
						<?php endif; ?>

						<?php if (! empty($card['description'])) : ?>
							<p class="twostars-experience-card__description"><?php echo esc_html((string) $card['description']); ?></p>
						<?php endif; ?>

						<div class="twostars-experience-card__meta">
							<?php if (! empty($card['duration'])) : ?>
								<div class="twostars-experience-card__meta-item">
									<i class="far fa-clock" aria-hidden="true"></i>
									<span><?php echo esc_html((string) $card['duration']); ?></span>
								</div>
							<?php endif; ?>
							<?php if (! empty($card['group_size'])) : ?>
								<div class="twostars-experience-card__meta-item">
									<i class="far fa-user" aria-hidden="true"></i>
									<span><?php echo esc_html((string) $card['group_size']); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<?php if (! empty($card['price'])) : ?>
							<div class="twostars-experience-card__price">
								<span class="twostars-experience-card__price-label"><?php esc_html_e('Starting from', 'twostars-elementor-widgets'); ?></span>
								<span class="twostars-experience-card__price-value"><?php echo esc_html((string) $card['price']); ?></span>
							</div>
						<?php endif; ?>

						<div class="twostars-experience-card__actions">
							<span class="twostars-safari-button twostars-safari-button--ghost">
								<?php esc_html_e('Explore Tour', 'twostars-elementor-widgets'); ?>
							</span>
						</div>
					</div>
				</<?php echo esc_html($tag); ?>>
			<?php endforeach; ?>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_cards(array $settings): array
	{
		$source = (string) ($settings['content_source'] ?? 'manual');
		$limit  = max(1, (int) ($settings['auto_limit'] ?? 3));

		if ($source === 'tours') {
			return array_map(
				fn(WP_Post $post): array => [
					'image'              => ['id' => get_post_thumbnail_id($post->ID)],
					'title'              => $post->post_title,
					'description'        => has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 22),
					'link'               => ['url' => get_permalink($post)],
					'duration'           => (string) get_post_meta($post->ID, '_tour_duration', true),
					'group_size'         => (string) get_post_meta($post->ID, '_tour_group_size', true),
					'price'              => (string) get_post_meta($post->ID, '_tour_price', true),
					'tour_id'            => $post->ID,
					'destination_id'     => 0,
					'content_type'       => 'tour',
					'intelligence_label' => __('Safari route', 'twostars-elementor-widgets'),
				],
				array_slice(TourContent::get_tours(['posts_per_page' => $limit]), 0, $limit)
			);
		}

		if ($source === 'destinations') {
			return array_map(
				fn(WP_Post $post): array => [
					'image'              => ['id' => get_post_thumbnail_id($post->ID)],
					'title'              => $post->post_title,
					'description'        => has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 22),
					'link'               => ['url' => get_permalink($post)],
					'tour_id'            => 0,
					'destination_id'     => $post->ID,
					'content_type'       => 'destination',
					'intelligence_label' => __('Destination lens', 'twostars-elementor-widgets'),
				],
				array_slice(TourContent::get_destinations(['posts_per_page' => $limit]), 0, $limit)
			);
		}

		return array_map(
			static function ($card): array {
				$card = is_array($card) ? $card : [];
				$card['tour_id'] = absint($card['tour_id'] ?? 0);
				$card['destination_id'] = absint($card['destination_id'] ?? 0);
				$card['content_type'] = $card['tour_id'] > 0 ? 'tour' : ($card['destination_id'] > 0 ? 'destination' : 'manual');

				return $card;
			},
			array_values((array) ($settings['cards'] ?? []))
		);
	}
}
