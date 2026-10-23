<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use TwoStars\Platform\Support\TourData;

final class ContactSectionWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-contact-section';
	}

	public function get_title(): string
	{
		return __('Safari Contact Form Section', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-mail';
	}

	protected function register_controls(): void
	{
		$this->start_controls_section(
			'contact_section',
			[
				'label' => __('Contact Details', 'twostars-elementor-widgets'),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'icon',
			[
				'label'   => __('Icon', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-phone-alt',
					'library' => 'fa-solid',
				],
			]
		);

		$repeater->add_control(
			'label',
			[
				'label'   => __('Label', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Call or WhatsApp', 'twostars-elementor-widgets'),
			]
		);

		$repeater->add_control(
			'value',
			[
				'label'   => __('Value', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('+264 00 000 000', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'contact_items',
			[
				'label'       => __('Contact Items', 'twostars-elementor-widgets'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'default'     => [
					[
						'label' => __('Call or WhatsApp', 'twostars-elementor-widgets'),
						'value' => __('+264 00 000 000', 'twostars-elementor-widgets'),
					],
					[
						'label' => __('Email', 'twostars-elementor-widgets'),
						'value' => __('hello@twostarssafari.com', 'twostars-elementor-widgets'),
					],
					[
						'label' => __('Based In', 'twostars-elementor-widgets'),
						'value' => __('Windhoek, Namibia', 'twostars-elementor-widgets'),
					],
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'form_section',
			[
				'label' => __('Form Settings', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'form_title',
			[
				'label'   => __('Form Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Start Your Safari Inquiry', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'success_message',
			[
				'label'   => __('Success Message', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Thanks. Your inquiry has been received and the team can follow up now.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'source',
			[
				'label'   => __('Lead Source', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'website' => __('Website', 'twostars-elementor-widgets'),
					'contact' => __('Contact Page', 'twostars-elementor-widgets'),
					'landing' => __('Landing Page', 'twostars-elementor-widgets'),
				],
				'default' => 'contact',
			]
		);

		$this->add_control(
			'default_tour',
			[
				'label'   => __('Default Tour', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_tour_options(),
				'default' => '',
			]
		);

		$this->add_control(
			'submit_text',
			[
				'label'   => __('Submit Button Text', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Send Inquiry', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'placeholder_name',
			[
				'label'   => __('Name Placeholder', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Full Name', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'placeholder_email',
			[
				'label'   => __('Email Placeholder', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Email Address', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'placeholder_phone',
			[
				'label'   => __('Phone Placeholder', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Phone or WhatsApp', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'placeholder_dates',
			[
				'label'   => __('Travel Dates Placeholder', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Preferred travel dates', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'placeholder_message',
			[
				'label'   => __('Message Placeholder', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Tell us which tour you like, your date, group size and pickup point.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'enable_intelligence_prefill',
			[
				'label'        => __('Enable Intelligence Prefill', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'intelligence_note_label',
			[
				'label'     => __('Intelligence Note Label', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXT,
				'default'   => __('Website Concierge', 'twostars-elementor-widgets'),
				'condition' => [
					'enable_intelligence_prefill' => 'yes',
				],
			]
		);

		$this->add_control(
			'intelligence_note_copy',
			[
				'label'     => __('Default Intelligence Note', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => __('The site can carry forward route interest, comparison behaviour, and booking signals so the team starts with better context.', 'twostars-elementor-widgets'),
				'condition' => [
					'enable_intelligence_prefill' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'whatsapp_section',
			[
				'label' => __('WhatsApp Integration', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'show_whatsapp',
			[
				'label'        => __('Show WhatsApp Button', 'twostars-elementor-widgets'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __('Yes', 'twostars-elementor-widgets'),
				'label_off'    => __('No', 'twostars-elementor-widgets'),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'whatsapp_number',
			[
				'label'     => __('WhatsApp Number (Full)', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXT,
				'default'   => __('26400000000', 'twostars-elementor-widgets'),
				'placeholder' => __('e.g. 26400000000', 'twostars-elementor-widgets'),
				'condition' => ['show_whatsapp' => 'yes'],
			]
		);

		$this->add_control(
			'whatsapp_text',
			[
				'label'     => __('Button Text', 'twostars-elementor-widgets'),
				'type'      => Controls_Manager::TEXT,
				'default'   => __('Chat on WhatsApp', 'twostars-elementor-widgets'),
				'condition' => ['show_whatsapp' => 'yes'],
			]
		);

		$this->end_controls_section();
	}
protected function render(): void
	{
		$settings = $this->get_settings_for_display();
		?>
		<section class="twostars-contact" id="inquiry">
			<div class="twostars-contact__grid">
				<div class="twostars-contact__sidebar">
					<div class="twostars-contact__concierge-card" data-twostars-animate="fade-in-up">
						<div class="twostars-contact__concierge-header">
							<div class="twostars-contact__concierge-avatar">
								<div class="twostars-contact__concierge-avatar-inner">
									<i class="fas fa-user-tie" aria-hidden="true"></i>
								</div>
								<span class="twostars-contact__status-dot" title="<?php esc_attr_e('Advisor Online', 'twostars-elementor-widgets'); ?>"></span>
							</div>
							<div class="twostars-contact__concierge-info">
								<p class="twostars-contact__concierge-label"><?php esc_html_e('Your Personal Advisor', 'twostars-elementor-widgets'); ?></p>
								<h3 class="twostars-contact__concierge-name"><?php esc_html_e('The Two Stars Team', 'twostars-elementor-widgets'); ?></h3>
							</div>
						</div>
						
						<div class="twostars-contact__details">
							<?php foreach (($settings['contact_items'] ?? []) as $item) : ?>
								<?php if (empty($item['label']) && empty($item['value'])) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<div class="twostars-contact__item">
									<?php if (! empty($item['icon']['value'])) : ?>
										<span class="twostars-contact__icon" aria-hidden="true">
											<?php Icons_Manager::render_icon((array) $item['icon'], ['aria-hidden' => 'true']); ?>
										</span>
									<?php endif; ?>
									<div>
										<?php if (! empty($item['label'])) : ?>
											<p class="twostars-contact__label"><?php echo esc_html((string) $item['label']); ?></p>
										<?php endif; ?>
										<?php if (! empty($item['value'])) : ?>
											<p class="twostars-contact__value"><?php echo $this->format_text((string) $item['value']); ?></p>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>

						<?php if (($settings['enable_intelligence_prefill'] ?? 'yes') === 'yes') : ?>
							<div
								class="twostars-contact__intelligence"
								data-twostars-contact-intelligence
								data-default-copy="<?php echo esc_attr((string) ($settings['intelligence_note_copy'] ?? '')); ?>"
							>
								<div class="twostars-contact__intelligence-header">
									<i class="fas fa-sparkles" aria-hidden="true"></i>
									<?php if (! empty($settings['intelligence_note_label'])) : ?>
										<p class="twostars-contact__intelligence-label"><?php echo esc_html((string) $settings['intelligence_note_label']); ?></p>
									<?php endif; ?>
								</div>
								<p class="twostars-contact__intelligence-copy" data-twostars-contact-intelligence-copy>
									<?php echo esc_html((string) ($settings['intelligence_note_copy'] ?? '')); ?>
								</p>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="twostars-contact__form-wrap" data-twostars-animate="fade-in-up" style="--twostars-delay:0.2s;">
					<div class="twostars-contact__form-header">
						<?php if (! empty($settings['form_title'])) : ?>
							<h2 class="twostars-contact__form-title"><?php echo esc_html((string) $settings['form_title']); ?></h2>
						<?php endif; ?>
						<p class="twostars-contact__form-subtitle"><?php esc_html_e('Share your route goals and group size so we can tailor the adventure.', 'twostars-elementor-widgets'); ?></p>
					</div>

					<form
						class="twostars-contact-form"
						data-twostars-contact-form
						data-intelligence-enabled="<?php echo ($settings['enable_intelligence_prefill'] ?? 'yes') === 'yes' ? 'true' : 'false'; ?>"
						data-success-message="<?php echo esc_attr((string) ($settings['success_message'] ?? '')); ?>"
						data-source="<?php echo esc_attr((string) ($settings['source'] ?? 'contact')); ?>"
						data-endpoint="<?php echo esc_url(rest_url('twostars/v1/crm/lead')); ?>"
					>
						<div class="twostars-contact-form__grid">
							<div class="twostars-contact-form__field">
								<label for="ts-name"><?php esc_html_e('Full Name', 'twostars-elementor-widgets'); ?></label>
								<input type="text" id="ts-name" name="full_name" required placeholder="<?php echo esc_attr((string) ($settings['placeholder_name'] ?? '')); ?>">
							</div>

							<div class="twostars-contact-form__field">
								<label for="ts-email"><?php esc_html_e('Email Address', 'twostars-elementor-widgets'); ?></label>
								<input type="email" id="ts-email" name="email" placeholder="<?php echo esc_attr((string) ($settings['placeholder_email'] ?? '')); ?>">
							</div>

							<div class="twostars-contact-form__field">
								<label for="ts-phone"><?php esc_html_e('Phone Number', 'twostars-elementor-widgets'); ?></label>
								<input type="tel" id="ts-phone" name="phone" placeholder="<?php echo esc_attr((string) ($settings['placeholder_phone'] ?? '')); ?>">
							</div>

							<div class="twostars-contact-form__field">
								<label for="ts-country"><?php esc_html_e('Country', 'twostars-elementor-widgets'); ?></label>
								<input type="text" id="ts-country" name="country" placeholder="<?php esc_attr_e('Where are you travelling from?', 'twostars-elementor-widgets'); ?>">
							</div>

							<div class="twostars-contact-form__field">
								<label for="ts-tour"><?php esc_html_e('Initial Tour Interest', 'twostars-elementor-widgets'); ?></label>
								<select id="ts-tour" name="tour_interest">
									<option value=""><?php esc_html_e('Select a tour', 'twostars-elementor-widgets'); ?></option>
									<?php foreach ($this->get_tour_options() as $tour_id => $label) : ?>
										<?php if ($tour_id === '') : ?>
											<?php continue; ?>
										<?php endif; ?>
										<option value="<?php echo esc_attr((string) $tour_id); ?>" <?php selected((string) $tour_id, (string) ($settings['default_tour'] ?? '')); ?>>
											<?php echo esc_html($label); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="twostars-contact-form__field">
								<label for="ts-guests"><?php esc_html_e('Number of Guests', 'twostars-elementor-widgets'); ?></label>
								<input type="number" id="ts-guests" name="guest_count" min="1" step="1" placeholder="<?php esc_attr_e('How many travellers?', 'twostars-elementor-widgets'); ?>">
							</div>

							<div class="twostars-contact-form__field twostars-contact-form__field--full">
								<label for="ts-dates"><?php esc_html_e('Preferred Travel Dates', 'twostars-elementor-widgets'); ?></label>
								<input type="text" id="ts-dates" name="travel_dates" placeholder="<?php echo esc_attr((string) ($settings['placeholder_dates'] ?? '')); ?>">
							</div>

							<div class="twostars-contact-form__field twostars-contact-form__field--full">
								<label for="ts-message"><?php esc_html_e('Your Inquiry Notes', 'twostars-elementor-widgets'); ?></label>
								<textarea id="ts-message" name="message" rows="5" placeholder="<?php echo esc_attr((string) ($settings['placeholder_message'] ?? '')); ?>"></textarea>
							</div>
						</div>

						<label class="twostars-contact-form__consent">
							<input type="checkbox" name="contact_consent" value="1" required>
							<span><?php esc_html_e('I agree to be contacted about my safari inquiry.', 'twostars-elementor-widgets'); ?></span>
						</label>

						<input type="hidden" name="source" value="<?php echo esc_attr((string) ($settings['source'] ?? 'contact')); ?>">
						<input type="hidden" name="origin_url" value="<?php echo esc_url(home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''))); ?>">
						<input type="hidden" name="form_started_at" value="<?php echo esc_attr((string) time()); ?>">

						<div class="twostars-contact-form__actions">
							<button type="submit" class="twostars-safari-button twostars-safari-button--primary twostars-safari-button--block">
								<span><?php echo esc_html((string) ($settings['submit_text'] ?? __('Send Inquiry', 'twostars-elementor-widgets'))); ?></span>
							</button>

							<?php if (($settings['show_whatsapp'] ?? 'yes') === 'yes' && ! empty($settings['whatsapp_number'])) : ?>
								<a 
									href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', (string) $settings['whatsapp_number'])); ?>" 
									class="twostars-safari-button twostars-safari-button--ghost twostars-safari-button--block twostars-whatsapp-button"
									target="_blank"
									rel="noopener"
								>
									<i class="fab fa-whatsapp" aria-hidden="true"></i>
									<span><?php echo esc_html((string) ($settings['whatsapp_text'] ?? __('Chat on WhatsApp', 'twostars-elementor-widgets'))); ?></span>
								</a>
							<?php endif; ?>

							<p class="twostars-contact-form__status" data-twostars-contact-form-status aria-live="polite"></p>
						</div>
					</form>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * @return array<string, string>
	 */
	private function get_tour_options(): array
	{
		if (! class_exists(TourData::class)) {
			return ['' => __('No tours available', 'twostars-elementor-widgets')];
		}

		return ['' => __('No default tour', 'twostars-elementor-widgets')] + TourData::get_tour_options();
	}
}
