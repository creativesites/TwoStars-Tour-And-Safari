<?php
declare(strict_types=1);

use TwoStars\Platform\CRM\LeadForm;

get_header();

$faqs = [
	['Where do we meet you?', 'We collect cruise guests from the Walvis Bay cruise port. The exact meeting point is confirmed with you when you book.'],
	['Will we be back in time for the ship?', 'We plan your day around your ship\'s arrival and all-aboard times and confirm the timings with you before you book. Send your ship\'s times on WhatsApp and we will tell you which tours fit.'],
	['How many guests are in each vehicle?', 'A maximum of 4 guests per vehicle, plus your guide and driver, so the day is never rushed.'],
	['How much does it cost?', 'The Sandwich Harbour tour is US$135 per adult. Children under 6 go free and ages 6 to 11 pay half price. Message us for the price of the other tours.'],
	['Can we cancel?', 'Yes. You can cancel up to 24 hours before the experience starts for a full refund.'],
	['Do you also collect from hotels?', 'Yes. We collect from Walvis Bay hotels, Swakopmund hotels and the Walvis Bay cruise port.'],
];
$hero = twostars_theme_media_url('hero-sandwich-harbour-dunes-lagoon.webp');
?>
<article class="ts-landing">
	<header class="ts-landing__hero"<?php echo $hero !== '' ? ' style="--ts-hero:url(\'' . esc_url($hero) . '\')"' : ''; ?>>
		<div class="ts-wrap ts-landing__hero-copy">
			<nav class="ts-crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'twostars-theme'); ?>"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a><span aria-hidden="true">/</span><span>Shore excursions</span></nav>
			<h1>Walvis Bay Shore Excursions for Cruise Passengers</h1>
			<p>Small-group days out planned around your ship's schedule, with pickup at the Walvis Bay cruise port.</p>
			<div class="ts-landing__actions">
				<a class="ts-btn ts-btn--primary" href="#plan" data-twostars-event="book_now_click">Plan my port day</a>
				<a class="ts-btn ts-btn--ghost" href="<?php echo esc_url(twostars_theme_wa_link('Hello! I am on a cruise and would like to ask about a shore excursion in Walvis Bay.')); ?>" target="_blank" rel="noopener noreferrer" data-twostars-event="whatsapp_click">Ask on WhatsApp</a>
			</div>
		</div>
	</header>

	<ul class="ts-trust">
		<li>Pickup at the cruise port</li>
		<li>Max 4 guests per vehicle</li>
		<li>Women-led local team</li>
		<li>Free cancellation up to 24h</li>
	</ul>

	<section class="ts-section">
		<div class="ts-wrap">
			<p class="ts-eyebrow">How it works</p>
			<h2>Your port day in three steps</h2>
			<ol class="ts-steps">
				<li><span class="ts-steps__n">1</span><h3>Tell us your ship times</h3><p>Send your ship's name, arrival time and all-aboard time, by WhatsApp or the form below.</p></li>
				<li><span class="ts-steps__n">2</span><h3>We match the tour</h3><p>We recommend the tour that fits your time in port and confirm the timings and price with you.</p></li>
				<li><span class="ts-steps__n">3</span><h3>Meet us at the port</h3><p>Your guide meets you at the cruise port, and the day is paced so you are not rushing for the ship.</p></li>
			</ol>
		</div>
	</section>

	<section class="ts-section ts-section--cream">
		<div class="ts-wrap">
			<p class="ts-eyebrow">Tours for a port day</p>
			<h2>Pick the day that suits you</h2>
			<p class="ts-lead">Tell us how long you have in port and we will tell you which of these fits.</p>
			<?php echo twostars_theme_tour_mini_cards(['sandwich-harbour-tour', 'walvis-bay-flamingo-tour', 'swakopmund-city-tour-dune-7']); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>

	<section class="ts-section">
		<div class="ts-wrap ts-wrap--narrow">
			<p class="ts-eyebrow">Good to know</p>
			<h2>Questions cruise guests ask</h2>
			<div class="ts-faq">
				<?php foreach ($faqs as [$q, $a]) : ?>
					<details><summary><?php echo esc_html($q); ?></summary><p><?php echo esc_html($a); ?></p></details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="ts-section ts-section--navy" id="plan">
		<div class="ts-wrap ts-wrap--narrow">
			<?php
			echo LeadForm::render_form( // phpcs:ignore WordPress.Security.EscapeOutput
				[
					'title'       => 'Plan your port day',
					'description' => 'Tell us your ship and times and we will reply with the tour that fits.',
					'source'      => 'shore-excursions',
					'variant'     => 'cruise',
					'submit'      => 'Send my ship details',
				]
			);
			?>
		</div>
	</section>
</article>
<?php echo twostars_theme_faq_schema($faqs, get_permalink()); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php
get_footer();
