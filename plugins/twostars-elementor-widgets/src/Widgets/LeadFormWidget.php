<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets\Widgets;

use Elementor\Controls_Manager;
use TwoStars\Platform\CRM\LeadForm;
use TwoStars\Platform\Support\TourData;

final class LeadFormWidget extends BaseWidget
{
	public function get_name(): string
	{
		return 'twostars-lead-form';
	}

	public function get_title(): string
	{
		return __('Lead Form', 'twostars-elementor-widgets');
	}

	public function get_icon(): string
	{
		return 'eicon-form-horizontal';
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
				'label'   => __('Title', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXT,
				'default' => __('Plan Your Safari', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'description',
			[
				'label'   => __('Description', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __('Tell us about your travel dates, route ideas, and contact details.', 'twostars-elementor-widgets'),
			]
		);

		$this->add_control(
			'source',
			[
				'label'   => __('Lead Source', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'website'  => __('Website', 'twostars-elementor-widgets'),
					'chatbot'  => __('Chatbot', 'twostars-elementor-widgets'),
					'social'   => __('Social', 'twostars-elementor-widgets'),
					'manual'   => __('Manual', 'twostars-elementor-widgets'),
				],
				'default' => 'website',
			]
		);

		$this->add_control(
			'tour_id',
			[
				'label'   => __('Default Tour', 'twostars-elementor-widgets'),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->get_tour_options(),
				'default' => '',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void
	{
		if (! class_exists(LeadForm::class)) {
			echo esc_html__('Two Stars Platform must be active to use this widget.', 'twostars-elementor-widgets');
			return;
		}

		$settings = $this->get_settings_for_display();

		echo LeadForm::render_form(
			[
				'title'       => (string) ($settings['title'] ?? ''),
				'description' => (string) ($settings['description'] ?? ''),
				'source'      => (string) ($settings['source'] ?? 'website'),
				'tour_id'     => absint($settings['tour_id'] ?? 0),
			]
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function get_tour_options(): array
	{
		if (! class_exists(TourData::class)) {
			return [];
		}

		return ['' => __('No default tour', 'twostars-elementor-widgets')] + TourData::get_tour_options();
	}
}
