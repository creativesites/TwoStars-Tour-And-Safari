<?php
declare(strict_types=1);

namespace TwoStars\Platform;

use TwoStars\Platform\Admin\SettingsPage;
use TwoStars\Platform\Admin\ContentManager;
use TwoStars\Platform\Admin\EditorialWorkbench;
use TwoStars\Platform\Admin\VisitorsManager;
use TwoStars\Platform\AI\ConversationMemory;
use TwoStars\Platform\AI\ContextStore;
use TwoStars\Platform\AI\IntelligenceManager;
use TwoStars\Platform\AI\SemanticSearch;
use TwoStars\Platform\AI\WebsiteIntelligence;
use TwoStars\Platform\Booking\BookingManager;
use TwoStars\Platform\CRM\Dashboard;
use TwoStars\Platform\CRM\Chatbot;
use TwoStars\Platform\CRM\CoreCRM;
use TwoStars\Platform\CRM\Hub;
use TwoStars\Platform\CRM\LeadForm;
use TwoStars\Platform\CRM\LeadManager;
use TwoStars\Platform\Integrations\CalendlyIntegration;
use TwoStars\Platform\Integrations\SocialFeedManager;
use TwoStars\Platform\Integrations\YoastIntegration;
use TwoStars\Platform\PostTypes\ContentTypes;
use TwoStars\Platform\PostTypes\DestinationMeta;
use TwoStars\Platform\PostTypes\ReviewMeta;
use TwoStars\Platform\PostTypes\TourMeta;
use TwoStars\Platform\Reviews\ReviewManager;
use TwoStars\Platform\Rest\Routes;
use TwoStars\Platform\Support\Service;
use TwoStars\Platform\Taxonomies\TourTaxonomies;
use TwoStars\Platform\Visitors\VisitorProfileManager;

final class Plugin
{
	/**
	 * @var Service[]
	 */
	private array $services = [];

	private static ?self $instance = null;

	public static function boot(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
			self::$instance->register();
		}

		return self::$instance;
	}

	public static function activate(): void
	{
		self::boot();
		ConversationMemory::install();
		CoreCRM::install();
		SemanticSearch::install();
		WebsiteIntelligence::install();
		VisitorProfileManager::install();
		flush_rewrite_rules();
	}

	public static function deactivate(): void
	{
		flush_rewrite_rules();
	}

	private function __construct()
	{
		$this->services = [
			new ContentTypes(),
			new ConversationMemory(),
			new ContextStore(),
			new SemanticSearch(),
			new WebsiteIntelligence(),
			new VisitorProfileManager(),
			new ContentManager(),
			new VisitorsManager(),
			new Dashboard(),
			new Hub(),
			new CoreCRM(),
			new Chatbot(),
			new LeadForm(),
			new LeadManager(),
			new ReviewManager(),
			new BookingManager(),
			new TourMeta(),
			new DestinationMeta(),
			new ReviewMeta(),
			new TourTaxonomies(),
			new SettingsPage(),
			new EditorialWorkbench(),
			new Routes(),
			new IntelligenceManager(),
			new CalendlyIntegration(),
			new YoastIntegration(),
			new SocialFeedManager(),
		];
	}

	private function register(): void
	{
		foreach ($this->services as $service) {
			$service->register();
		}
	}
}
