<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use TwoStars\ElementorWidgets\Support\TourContent;

final class HomePlanningCtaWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-home-planning-cta';
	}

	public function get_title(): string
	{
		return __('Homepage Planning CTA', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-call-to-action';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section('content_section', ['label' => __('Content', 'twostars-elementor-widgets')]);
		$this->add_control('eyebrow', ['label' => __('Eyebrow', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Plan With Two Stars', 'twostars-elementor-widgets')]);
		$this->add_control('title', ['label' => __('Title', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('End the homepage with one clear next move.', 'twostars-elementor-widgets')]);
		$this->add_control('intro', ['label' => __('Intro', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Invite the visitor into a real planning exchange, not a generic contact dead-end. The section should feel energetic, direct, and low-friction.', 'twostars-elementor-widgets')]);

		$repeater = new Repeater();
		$repeater->add_control('item', ['label' => __('Promise', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('A human reply with route logic and next steps.', 'twostars-elementor-widgets')]);
		$this->add_control(
			'promises',
			[
				'label'       => __('Promises', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ item }}}',
				'default'     => [
					['item' => __('A human reply with route logic and next steps.', 'twostars-elementor-widgets')],
					['item' => __('Clear guidance on tour fit, timing, and pacing.', 'twostars-elementor-widgets')],
					['item' => __('A smoother handoff from browsing into planning.', 'twostars-elementor-widgets')],
				],
			]
		);

		$this->add_control('assistant_label', ['label' => __('Assistant Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Need a quicker answer first?', 'twostars-elementor-widgets')]);
		$this->add_control('assistant_button_label', ['label' => __('Assistant Button Label', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Open Concierge', 'twostars-elementor-widgets')]);
		$this->add_control('assistant_fallback_link', ['label' => __('Assistant Fallback Link', 'twostars-elementor-widgets'), 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/contact/')]]);
		$this->add_control('default_tour', ['label' => __('Default Tour', 'twostars-elementor-widgets'), 'type' => Controls_Manager::SELECT, 'options' => TourContent::get_tour_options(), 'default' => '']);
		$this->add_control('submit_text', ['label' => __('Submit Text', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXT, 'default' => __('Request My Safari Plan', 'twostars-elementor-widgets')]);
		$this->add_control('success_message', ['label' => __('Success Message', 'twostars-elementor-widgets'), 'type' => Controls_Manager::TEXTAREA, 'default' => __('Thanks. Your planning request is in and the team can now follow up with the right route advice.', 'twostars-elementor-widgets')]);
		$this->end_controls_section();
	}

	protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-home-planning">
			<div class="twostars-home-planning__grid">
				<div class="twostars-home-planning__content" data-twostars-animate="fade-in-left">
					<?php if (! empty($settings['eyebrow'])) : ?><p class="twostars-home-section-eyebrow"><?php echo esc_html((string) $settings['eyebrow']); ?></p><?php endif; ?>
					<?php if (! empty($settings['title'])) : ?><h2 class="twostars-home-section-title"><?php echo nl2br(esc_html((string) $settings['title'])); ?></h2><?php endif; ?>
					<?php if (! empty($settings['intro'])) : ?><p class="twostars-home-planning__intro"><?php echo esc_html((string) $settings['intro']); ?></p><?php endif; ?>

					<?php if (! empty($settings['promises'])) : ?>
						<ul class="twostars-home-planning__promises">
							<?php foreach ((array) $settings['promises'] as $promise) : ?>
								<?php if (empty($promise['item'])) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<li><?php echo esc_html((string) $promise['item']); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<div
						class="twostars-home-planning__assistant"
						data-twostars-contact-intelligence
						data-default-copy="<?php echo esc_attr((string) ($settings['assistant_label'] ?? '')); ?>"
					>
						<p class="twostars-home-planning__assistant-copy" data-twostars-contact-intelligence-copy><?php echo esc_html((string) ($settings['assistant_label'] ?? '')); ?></p>
						<button
							type="button"
							class="twostars-safari-button twostars-safari-button--ghost"
							data-twostars-assistant-trigger
							data-fallback-url="<?php echo esc_url((string) ($settings['assistant_fallback_link']['url'] ?? home_url('/contact/'))); ?>"
						>
							<?php echo esc_html((string) ($settings['assistant_button_label'] ?? __('Open Concierge', 'twostars-elementor-widgets'))); ?>
						</button>
					</div>
				</div>

				<div class="twostars-home-planning__form-wrap" data-twostars-animate="fade-in-right" style="--twostars-delay:0.15s;">
					<form
						class="twostars-contact-form twostars-home-planning__form"
						data-twostars-contact-form
						data-intelligence-enabled="true"
						data-success-message="<?php echo esc_attr((string) ($settings['success_message'] ?? '')); ?>"
						data-source="homepage"
						data-endpoint="<?php echo esc_url(rest_url('twostars/v1/crm/lead')); ?>"
					>
						<div class="twostars-contact-form__grid">
							<label class="twostars-contact-form__field">
								<span><?php esc_html_e('Full Name', 'twostars-elementor-widgets'); ?></span>
								<input type="text" name="full_name" required placeholder="<?php esc_attr_e('Full Name', 'twostars-elementor-widgets'); ?>">
							</label>

							<label class="twostars-contact-form__field">
								<span><?php esc_html_e('Email', 'twostars-elementor-widgets'); ?></span>
								<input type="email" name="email" placeholder="<?php esc_attr_e('Email Address', 'twostars-elementor-widgets'); ?>">
							</label>

							<label class="twostars-contact-form__field">
								<span><?php esc_html_e('Phone', 'twostars-elementor-widgets'); ?></span>
								<input type="tel" name="phone" placeholder="<?php esc_attr_e('Phone or WhatsApp', 'twostars-elementor-widgets'); ?>">
							</label>

							<label class="twostars-contact-form__field">
								<span><?php esc_html_e('Preferred Travel Dates', 'twostars-elementor-widgets'); ?></span>
								<input type="text" name="travel_dates" placeholder="<?php esc_attr_e('Preferred travel dates', 'twostars-elementor-widgets'); ?>">
							</label>

							<label class="twostars-contact-form__field twostars-contact-form__field--full">
								<span><?php esc_html_e('Tour Interest', 'twostars-elementor-widgets'); ?></span>
								<select name="tour_interest">
									<option value=""><?php esc_html_e('Select a tour', 'twostars-elementor-widgets'); ?></option>
									<?php foreach (TourContent::get_tour_options() as $tour_id => $label) : ?>
										<?php if ($tour_id === '') : ?>
											<?php continue; ?>
										<?php endif; ?>
										<option value="<?php echo esc_attr((string) $tour_id); ?>" <?php selected((string) $tour_id, (string) ($settings['default_tour'] ?? '')); ?>>
											<?php echo esc_html($label); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>

							<label class="twostars-contact-form__field twostars-contact-form__field--full">
								<span><?php esc_html_e('Message', 'twostars-elementor-widgets'); ?></span>
								<textarea name="message" rows="5" placeholder="<?php esc_attr_e('Tell us the type of safari you want, the regions you are leaning toward, and any timing or budget constraints.', 'twostars-elementor-widgets'); ?>"></textarea>
							</label>
						</div>

						<label class="twostars-contact-form__consent">
							<input type="checkbox" name="contact_consent" value="1" required>
							<span><?php esc_html_e('I agree to be contacted about my safari inquiry.', 'twostars-elementor-widgets'); ?></span>
						</label>

						<input type="hidden" name="source" value="homepage">
						<input type="hidden" name="origin_url" value="<?php echo esc_url(home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''))); ?>">
						<input type="hidden" name="form_started_at" value="<?php echo esc_attr((string) time()); ?>">

						<div class="twostars-contact-form__actions">
							<button type="submit" class="twostars-safari-button twostars-safari-button--primary twostars-safari-button--block">
								<?php echo esc_html((string) ($settings['submit_text'] ?? __('Request My Safari Plan', 'twostars-elementor-widgets'))); ?>
							</button>
							<p class="twostars-contact-form__status" data-twostars-contact-form-status aria-live="polite"></p>
						</div>
					</form>
				</div>
			</div>
		</section>
		<?php
	}
}
