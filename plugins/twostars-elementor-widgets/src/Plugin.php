<?php
declare(strict_types=1);

namespace TwoStars\ElementorWidgets;

use TwoStars\ElementorWidgets\Support\Compatibility;

final class Plugin
{
	private static ?self $instance = null;

	private Compatibility $compatibility;

	public static function boot(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
			self::$instance->register();
		}

		return self::$instance;
	}

	private function __construct()
	{
		$this->compatibility = new Compatibility();
	}

	private function register(): void
	{
		$this->compatibility->register();

		if (! $this->compatibility->is_elementor_active()) {
			return;
		}

		add_action('wp_enqueue_scripts', [$this, 'register_assets']);
		add_action('elementor/frontend/after_register_styles', [$this, 'register_assets']);
		add_action('elementor/frontend/after_register_scripts', [$this, 'register_assets']);
		add_action('elementor/elements/categories_registered', [$this, 'register_category']);
		add_action('elementor/widgets/register', [$this, 'register_widgets']);
	}

	public function register_assets(): void
	{
		if (! wp_style_is('twostars-elementor-fonts', 'registered')) {
			wp_register_style(
				'twostars-elementor-fonts',
				'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap',
				[],
				null
			);
		}

		if (! wp_style_is('twostars-elementor-widgets', 'registered')) {
			wp_register_style(
				'twostars-elementor-widgets',
				TWOSTARS_ELEMENTOR_WIDGETS_URL . 'assets/css/widgets.css',
				['twostars-elementor-fonts'],
				TWOSTARS_ELEMENTOR_WIDGETS_VERSION
			);
		}

		if (! wp_script_is('twostars-elementor-widget-gallery', 'registered')) {
			wp_register_script(
				'twostars-elementor-widget-gallery',
				TWOSTARS_ELEMENTOR_WIDGETS_URL . 'assets/js/widgets/gallery.js',
				['twostars-elementor-widgets'],
				TWOSTARS_ELEMENTOR_WIDGETS_VERSION,
				true
			);
		}

		if (! wp_script_is('twostars-elementor-widgets', 'registered')) {
			wp_register_script(
				'twostars-elementor-widgets',
				TWOSTARS_ELEMENTOR_WIDGETS_URL . 'assets/js/widgets.js',
				[],
				TWOSTARS_ELEMENTOR_WIDGETS_VERSION,
				true
			);

			wp_localize_script(
				'twostars-elementor-widgets',
				'twoStarsWidgets',
				[
					'restUrl'               => esc_url_raw(rest_url('twostars/v1/crm/lead')),
					'reviewUrl'             => esc_url_raw(rest_url('twostars/v1/reviews/submit')),
					'sendingMessage'        => __('Sending inquiry...', 'twostars-elementor-widgets'),
					'sendingReviewMessage'  => __('Sending your review...', 'twostars-elementor-widgets'),
					'errorMessage'          => __('Something went wrong while sending your request. Please try again.', 'twostars-elementor-widgets'),
					'reviewErrorMessage'    => __('Something went wrong while sending your review. Please try again.', 'twostars-elementor-widgets'),
					'contactPrompt'         => __('Enter an email address or phone number.', 'twostars-elementor-widgets'),
					'defaultSuccessMessage' => __('Thanks. Your inquiry has been received and the team can follow up now.', 'twostars-elementor-widgets'),
					'defaultReviewSuccessMessage' => __('Thank you. Your review has been received and will appear once approved by the Two Stars team.', 'twostars-elementor-widgets'),
				]
			);
		}
	}

	/**
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_category($elements_manager): void
	{
		$elements_manager->add_category(
				'twostars',
				[
				'title' => __('Two Stars Tours', 'twostars-elementor-widgets'),
				'icon'  => 'fa fa-star',
			]
		);
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets($widgets_manager): void
	{
		$widgets = [
			Widgets\NavigationWidget::class,
			Widgets\HomeLuxuryHeroWidget::class,
			Widgets\HomeTrustStoryWidget::class,
			Widgets\HomeSignatureCollectionWidget::class,
			Widgets\HomeProofMosaicWidget::class,
			Widgets\ReviewsShowcaseWidget::class,
			Widgets\HomePlanningCtaWidget::class,
			Widgets\HeroBannerWidget::class,
			Widgets\SectionHeaderWidget::class,
			Widgets\AboutSectionWidget::class,
			Widgets\ToursHeroWidget::class,
			Widgets\ToursHighlightsWidget::class,
			Widgets\ToursFilterBarWidget::class,
			Widgets\ToursGridWidget::class,
			Widgets\FeaturedTourWidget::class,
			Widgets\ToursFitGuideWidget::class,
			Widgets\PlanningStripWidget::class,
			Widgets\SingleTourHeroWidget::class,
			Widgets\SingleTourDetailsWidget::class,
			Widgets\SingleTourOverviewWidget::class,
			Widgets\SingleTourInclusionsWidget::class,
			Widgets\RelatedToursWidget::class,
			Widgets\DestinationsHeroWidget::class,
			Widgets\DestinationsHighlightsWidget::class,
			Widgets\DestinationsGridWidget::class,
			Widgets\SingleDestinationHeroWidget::class,
			Widgets\SingleDestinationOverviewWidget::class,
			Widgets\DestinationRelatedToursWidget::class,
			Widgets\DestinationPlanningStripWidget::class,
			Widgets\AboutHeroWidget::class,
			Widgets\AboutStoryWidget::class,
			Widgets\AboutValuesWidget::class,
			Widgets\AboutToursOverviewWidget::class,
			Widgets\AboutCommitmentWidget::class,
			Widgets\AboutLocationExpertiseWidget::class,
			Widgets\AboutStatsWidget::class,
			Widgets\TeamGridWidget::class,
			Widgets\AboutCtaWidget::class,
			Widgets\ContactHeroWidget::class,
			Widgets\FaqHeroWidget::class,
			Widgets\FaqAccordionWidget::class,
			Widgets\FaqCtaWidget::class,
			Widgets\TestimonialsHeroWidget::class,
			Widgets\TestimonialsGridWidget::class,
			Widgets\TestimonialsCtaWidget::class,
			Widgets\BookingHeroWidget::class,
			Widgets\BookingStepsWidget::class,
			Widgets\BookingCalendlyWidget::class,
			Widgets\BookingAssuranceWidget::class,
			Widgets\ExperienceCardsWidget::class,
			Widgets\GalleryWidget::class,
			Widgets\GalleryHeroWidget::class,
			Widgets\GalleryReviewsWidget::class,
			Widgets\GalleryCtaWidget::class,
			Widgets\TestimonialBlockWidget::class,
			Widgets\ContactSectionWidget::class,
			Widgets\FooterWidget::class,
			Widgets\TourCardWidget::class,
			Widgets\BookingCtaWidget::class,
			Widgets\SocialFeedWidget::class,
			Widgets\LeadFormWidget::class,
			Widgets\ContactMethodsGridWidget::class,
			Widgets\ContactProcessWidget::class,
		];

		foreach ($widgets as $widget_class) {
			$widgets_manager->register(new $widget_class());
		}
	}
}
