<?php
declare(strict_types=1);

use TwoStars\Platform\CRM\LeadForm;

get_header();

$faqs = [
	['How does a private tour work?', 'Send us your date, group size, interests and pickup point. We reply with a plan and a price.'],
	['How much does a private tour cost?', 'Private tours are quoted by route, group size and date. Message us and we will confirm.'],
	['Can we combine places in one day?', 'Yes. You can combine the coast, the lagoon, Swakopmund, Dune 7 or Spitzkoppe in a day that suits you.'],
	['Can you plan around a cruise ship?', 'Yes. Tell us your ship\'s arrival and all-aboard times and we will plan the day around them.'],
	['Can I cancel?', 'Yes. You can cancel up to 24 hours before the experience starts for a full refund.'],
	['Which language is the tour in?', 'Tours are guided in English.'],
];
$hero = twostars_theme_media_url('two-stars-4x4-and-picnic-table-sandwich-harbour-dunes.webp');
$ideas = [
	['Sandwich Harbour', 'The dunes where the desert meets the Atlantic, with long photo stops.', 'sandwich-harbour-tour'],
	['Flamingos and the Pink Salt Lake', 'A relaxed outing for birdlife and photography.', 'walvis-bay-flamingo-tour'],
	['Swakopmund and Dune 7', 'Town landmarks and history, then a desert stop at Dune 7.', 'swakopmund-city-tour-dune-7'],
	['Spitzkoppe', 'A full day among the granite formations.', 'spitzkoppe-day-tour'],
];
?>
<article class="ts-landing">
	<header class="ts-landing__hero"<?php echo $hero !== '' ? ' style="--ts-hero:url(\'' . esc_url($hero) . '\')"' : ''; ?>>
		<div class="ts-wrap ts-landing__hero-copy">
			<nav class="ts-crumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'twostars-theme'); ?>"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a><span aria-hidden="true">/</span><span>Private tours</span></nav>
			<h1>Private Tours in Walvis Bay and Swakopmund</h1>
			<p>Your own vehicle, your own route and your own pace, with Tuli and her women-led local team.</p>
			<div class="ts-landing__actions">
				<a class="ts-btn ts-btn--primary" href="#request" data-twostars-event="book_now_click">Request a private day</a>
				<a class="ts-btn ts-btn--ghost" href="<?php echo esc_url(twostars_theme_wa_link('Hello! I would like to plan a private tour. My date is ___ and we are ___ people.')); ?>" target="_blank" rel="noopener noreferrer" data-twostars-event="whatsapp_click">Plan on WhatsApp</a>
			</div>
		</div>
	</header>

	<ul class="ts-trust">
		<li>Your own vehicle</li>
		<li>Flexible route and timing</li>
		<li>Women-led local team</li>
		<li>Free cancellation up to 24h</li>
	</ul>

	<section class="ts-section">
		<div class="ts-wrap">
			<p class="ts-eyebrow">Why go private</p>
			<h2>A day shaped around you</h2>
			<div class="ts-cols">
				<div><h3>For photographers</h3><p>Stay as long as the light is good. We plan the stops around you, not a timetable.</p></div>
				<div><h3>For families and friends</h3><p>A comfortable pace for your group, with time for questions and photos.</p></div>
				<div><h3>For cruise guests</h3><p>Tell us your ship times and we will plan the day around them.</p></div>
			</div>
		</div>
	</section>

	<section class="ts-section ts-section--cream">
		<div class="ts-wrap">
			<p class="ts-eyebrow">Ideas for your day</p>
			<h2>Choose a route, or mix them</h2>
			<ul class="ts-ideas">
				<?php foreach ($ideas as [$title, $text, $slug]) : ?>
					<?php $tour = get_page_by_path($slug, OBJECT, 'tour'); ?>
					<li>
						<h3><?php echo esc_html($title); ?></h3>
						<p><?php echo esc_html($text); ?></p>
						<?php if ($tour instanceof WP_Post) : ?><a href="<?php echo esc_url(get_permalink($tour)); ?>">See the group tour</a><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="ts-note">Sunset departures are offered on request, depending on the season and availability.</p>
		</div>
	</section>

	<section class="ts-section">
		<div class="ts-wrap ts-wrap--narrow">
			<p class="ts-eyebrow">Questions</p>
			<h2>Private tour FAQ</h2>
			<div class="ts-faq">
				<?php foreach ($faqs as [$q, $a]) : ?>
					<details><summary><?php echo esc_html($q); ?></summary><p><?php echo esc_html($a); ?></p></details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="ts-section ts-section--navy" id="request">
		<div class="ts-wrap ts-wrap--narrow">
			<?php
			echo LeadForm::render_form( // phpcs:ignore WordPress.Security.EscapeOutput
				[
					'title'       => 'Request your private day',
					'description' => 'Share your date, group size and pickup point. We reply with a plan and a price.',
					'source'      => 'private-tours',
					'variant'     => 'private',
					'submit'      => 'Send my request',
				]
			);
			?>
		</div>
	</section>
</article>
<?php echo twostars_theme_faq_schema($faqs, get_permalink()); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php
get_footer();
