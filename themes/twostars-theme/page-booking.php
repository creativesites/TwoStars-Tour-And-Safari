<?php
declare(strict_types=1);

get_header();

$page = get_page_by_path('booking');
$booking_page = $page instanceof WP_Post ? $page : get_queried_object();
$booking_page = $booking_page instanceof WP_Post ? $booking_page : null;
$tours = function_exists('twostars_theme_get_booking_tours') ? twostars_theme_get_booking_tours() : [];
$selected_tour_id = function_exists('twostars_theme_get_booking_prefill_tour_id') ? twostars_theme_get_booking_prefill_tour_id() : 0;
$hero_image = $booking_page instanceof WP_Post ? get_the_post_thumbnail_url($booking_page, 'full') : '';

if (($hero_image === false || $hero_image === '') && isset($tours[0]['image'])) {
	$hero_image = (string) $tours[0]['image'];
}

$hero_excerpt = __('Choose the route that fits your day, browse live availability, and send your request straight to the Two Stars team in Walvis Bay.', 'twostars-theme');
?>
<div class="twostars-booking-page">
	<section class="twostars-booking-page__hero">
		<div class="twostars-booking-page__hero-media">
			<?php if (is_string($hero_image) && $hero_image !== '') : ?>
				<img class="twostars-booking-page__hero-image" src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr(get_the_title($booking_page)); ?>">
			<?php endif; ?>
		</div>
		<div class="twostars-booking-page__hero-overlay"></div>
		<div class="twostars-booking-page__hero-shell">
			<div class="twostars-booking-page__hero-copy">
				<div class="twostars-booking-page__eyebrow">
					<span class="twostars-booking-page__eyebrow-line"></span>
					<span><?php esc_html_e('Two Stars Booking', 'twostars-theme'); ?></span>
				</div>
				<h1 class="twostars-booking-page__hero-title"><?php echo esc_html($booking_page instanceof WP_Post ? get_the_title($booking_page) : __('Book Your Tour', 'twostars-theme')); ?></h1>
				<p class="twostars-booking-page__hero-text"><?php echo esc_html($hero_excerpt); ?></p>
				<div class="twostars-booking-page__hero-actions">
					<a class="twostars-booking-page__hero-button twostars-booking-page__hero-button--primary" href="#booking-app">
						<?php esc_html_e('Start Planning', 'twostars-theme'); ?>
					</a>
					<a class="twostars-booking-page__hero-button twostars-booking-page__hero-button--ghost" href="<?php echo esc_url(twostars_theme_get_whatsapp_url()); ?>" target="_blank" rel="noopener">
						<?php esc_html_e('Ask On WhatsApp', 'twostars-theme'); ?>
					</a>
				</div>
			</div>
			<aside class="twostars-booking-page__hero-panel">
				<p class="twostars-booking-page__hero-panel-label"><?php esc_html_e('What You Can Do Here', 'twostars-theme'); ?></p>
				<h2><?php esc_html_e('Choose the right tour, hold the right date, and send one clear request.', 'twostars-theme'); ?></h2>
				<ul>
					<li><?php esc_html_e('Compare the current Two Stars tour collection', 'twostars-theme'); ?></li>
					<li><?php esc_html_e('Browse availability month by month', 'twostars-theme'); ?></li>
					<li><?php esc_html_e('Share pickup details before the team follows up', 'twostars-theme'); ?></li>
				</ul>
			</aside>
		</div>
	</section>

	<section id="booking-app" class="twostars-booking-page__experience">
		<div class="twostars-booking-page__experience-shell">
			<div class="twostars-booking-page__intro">
				<p class="twostars-booking-page__section-label"><?php esc_html_e('Direct Booking', 'twostars-theme'); ?></p>
				<h2><?php esc_html_e('Plan your day with the actual Two Stars routes.', 'twostars-theme'); ?></h2>
				<p><?php esc_html_e('This booking page is built around the tours currently on offer, with live date selection, tour-specific route cues, pickup details, and direct follow-up from the local team.', 'twostars-theme'); ?></p>
			</div>

			<div class="twostars-booking-app">
				<aside class="twostars-booking-app__sidebar">
					<div class="twostars-booking-app__sidebar-card">
						<p class="twostars-booking-app__sidebar-label"><?php esc_html_e('How It Works', 'twostars-theme'); ?></p>
						<ol class="twostars-booking-app__steps">
							<li><span>01</span><?php esc_html_e('Choose the tour that matches your pace, scenery, and route style.', 'twostars-theme'); ?></li>
							<li><span>02</span><?php esc_html_e('Move through the calendar and open a date with live times.', 'twostars-theme'); ?></li>
							<li><span>03</span><?php esc_html_e('Add guest and pickup details so the team can confirm quickly.', 'twostars-theme'); ?></li>
						</ol>
					</div>

					<div class="twostars-booking-app__sidebar-card twostars-booking-app__sidebar-card--summary">
						<p class="twostars-booking-app__sidebar-label"><?php esc_html_e('Your Selection', 'twostars-theme'); ?></p>
						<div class="twostars-booking-app__selection" data-booking-selection>
							<p class="twostars-booking-app__selection-title"><?php esc_html_e('Choose a tour to begin.', 'twostars-theme'); ?></p>
							<p class="twostars-booking-app__selection-text"><?php esc_html_e('The route summary, key highlights, and selected date and time will appear here.', 'twostars-theme'); ?></p>
						</div>
					</div>

					<div class="twostars-booking-app__sidebar-card twostars-booking-app__sidebar-card--help">
						<p class="twostars-booking-app__sidebar-label"><?php esc_html_e('Need A Custom Plan?', 'twostars-theme'); ?></p>
						<p><?php esc_html_e('If you want a private option, different pickup timing, or help choosing between routes, message Two Stars directly and the team can guide you.', 'twostars-theme'); ?></p>
						<a class="twostars-booking-app__whatsapp" href="<?php echo esc_url(twostars_theme_get_whatsapp_url()); ?>" target="_blank" rel="noopener">
							<?php esc_html_e('Message Two Stars', 'twostars-theme'); ?>
						</a>
					</div>
				</aside>

				<div class="twostars-booking-app__panel">
					<div class="twostars-booking-app__stage">
						<div class="twostars-booking-app__stage-head">
							<p class="twostars-booking-page__section-label"><?php esc_html_e('Step 1', 'twostars-theme'); ?></p>
							<h3><?php esc_html_e('Choose your tour', 'twostars-theme'); ?></h3>
						</div>
						<div class="twostars-booking-app__tour-grid" data-booking-tour-grid>
							<?php foreach ($tours as $tour) : ?>
								<?php
								$tour_id = (int) ($tour['id'] ?? 0);
								$summary = array_slice(array_filter(array_map('strval', (array) ($tour['summary'] ?? []))), 0, 3);
								$highlights = array_slice(array_filter(array_map('strval', (array) ($tour['highlights'] ?? []))), 0, 3);
								?>
								<button
									type="button"
									class="twostars-booking-tour-card<?php echo $tour_id === $selected_tour_id ? ' is-selected' : ''; ?>"
									data-tour-card
									data-tour-id="<?php echo esc_attr((string) $tour_id); ?>"
								>
									<span class="twostars-booking-tour-card__media">
										<?php if (! empty($tour['image'])) : ?>
											<img src="<?php echo esc_url((string) $tour['image']); ?>" alt="<?php echo esc_attr((string) ($tour['title'] ?? '')); ?>">
										<?php endif; ?>
									</span>
									<span class="twostars-booking-tour-card__body">
										<span class="twostars-booking-tour-card__header">
											<span class="twostars-booking-tour-card__title"><?php echo esc_html((string) ($tour['title'] ?? '')); ?></span>
											<span class="twostars-booking-tour-card__toggle"><?php esc_html_e('Open', 'twostars-theme'); ?></span>
										</span>
										<span class="twostars-booking-tour-card__text"><?php echo esc_html((string) ($tour['excerpt'] ?? '')); ?></span>
										<?php if ($summary !== []) : ?>
											<span class="twostars-booking-tour-card__meta">
												<?php foreach ($summary as $item) : ?>
													<span class="twostars-booking-tour-card__meta-chip"><?php echo esc_html($item); ?></span>
												<?php endforeach; ?>
											</span>
										<?php endif; ?>
										<span class="twostars-booking-tour-card__details">
											<?php if ($highlights !== []) : ?>
												<span class="twostars-booking-tour-card__details-label"><?php esc_html_e('Stops & Feel', 'twostars-theme'); ?></span>
												<span class="twostars-booking-tour-card__details-grid">
													<?php foreach ($highlights as $highlight) : ?>
														<span class="twostars-booking-tour-card__story-chip"><?php echo esc_html($highlight); ?></span>
													<?php endforeach; ?>
												</span>
											<?php endif; ?>
										</span>
									</span>
								</button>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="twostars-booking-app__stage">
						<div class="twostars-booking-app__stage-head">
							<p class="twostars-booking-page__section-label"><?php esc_html_e('Step 2', 'twostars-theme'); ?></p>
							<h3><?php esc_html_e('Choose a date and time', 'twostars-theme'); ?></h3>
						</div>
						<div class="twostars-booking-app__calendar">
							<div class="twostars-booking-calendar__panel">
								<div class="twostars-booking-calendar__head">
									<button type="button" class="twostars-booking-calendar__nav" data-booking-prev-month aria-label="<?php esc_attr_e('Previous month', 'twostars-theme'); ?>">
										<span>&larr;</span>
									</button>
									<div class="twostars-booking-calendar__month" data-booking-month-label><?php esc_html_e('Loading month', 'twostars-theme'); ?></div>
									<button type="button" class="twostars-booking-calendar__nav" data-booking-next-month aria-label="<?php esc_attr_e('Next month', 'twostars-theme'); ?>">
										<span>&rarr;</span>
									</button>
								</div>
								<div class="twostars-booking-calendar__weekdays">
									<span><?php esc_html_e('Mon', 'twostars-theme'); ?></span>
									<span><?php esc_html_e('Tue', 'twostars-theme'); ?></span>
									<span><?php esc_html_e('Wed', 'twostars-theme'); ?></span>
									<span><?php esc_html_e('Thu', 'twostars-theme'); ?></span>
									<span><?php esc_html_e('Fri', 'twostars-theme'); ?></span>
									<span><?php esc_html_e('Sat', 'twostars-theme'); ?></span>
									<span><?php esc_html_e('Sun', 'twostars-theme'); ?></span>
								</div>
								<div class="twostars-booking-calendar__grid" data-booking-dates></div>
							</div>
							<div class="twostars-booking-calendar__times">
								<div class="twostars-booking-calendar__times-head">
									<h4><?php esc_html_e('Available Times', 'twostars-theme'); ?></h4>
									<p><?php esc_html_e('Select an open slot once you have chosen a date.', 'twostars-theme'); ?></p>
								</div>
								<div class="twostars-booking-app__times" data-booking-times></div>
							</div>
						</div>
					</div>

					<div class="twostars-booking-app__stage" data-booking-form-stage>
						<div class="twostars-booking-app__stage-head">
							<p class="twostars-booking-page__section-label"><?php esc_html_e('Step 3', 'twostars-theme'); ?></p>
							<h3><?php esc_html_e('Enter your booking details', 'twostars-theme'); ?></h3>
						</div>
						<form class="twostars-booking-form" data-booking-form novalidate>
							<input type="hidden" name="tour_id" value="<?php echo esc_attr((string) $selected_tour_id); ?>">
							<input type="hidden" name="date" value="">
							<input type="hidden" name="time" value="">
							<input type="hidden" name="origin_url" value="<?php echo esc_url(home_url('/booking/')); ?>">
							<input type="hidden" name="form_started_at" value="">
							<input type="text" name="company_website" value="" class="twostars-booking-form__honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">

							<div class="twostars-booking-form__grid">
								<label class="twostars-booking-form__field">
									<span><?php esc_html_e('Full Name', 'twostars-theme'); ?></span>
									<input type="text" name="full_name" required>
									<small class="twostars-booking-form__error" data-field-error="full_name"></small>
								</label>
								<label class="twostars-booking-form__field">
									<span><?php esc_html_e('Email Address', 'twostars-theme'); ?></span>
									<input type="email" name="email">
									<small class="twostars-booking-form__error" data-field-error="email"></small>
								</label>
								<label class="twostars-booking-form__field">
									<span><?php esc_html_e('Phone / WhatsApp', 'twostars-theme'); ?></span>
									<input type="text" name="phone">
									<small class="twostars-booking-form__error" data-field-error="phone"></small>
								</label>
								<label class="twostars-booking-form__field">
									<span><?php esc_html_e('Guests', 'twostars-theme'); ?></span>
									<input type="number" name="guest_count" min="1" max="20" value="2" required>
									<small class="twostars-booking-form__error" data-field-error="guest_count"></small>
								</label>
								<label class="twostars-booking-form__field">
									<span><?php esc_html_e('Pickup Area', 'twostars-theme'); ?></span>
									<select name="pickup_location" data-booking-pickup-select required></select>
									<small class="twostars-booking-form__error" data-field-error="pickup_location"></small>
								</label>
								<div class="twostars-booking-form__field">
									<span><?php esc_html_e('Country', 'twostars-theme'); ?></span>
									<div class="twostars-booking-country">
										<input type="search" class="twostars-booking-country__search" data-country-search placeholder="<?php esc_attr_e('Search country', 'twostars-theme'); ?>">
										<select name="country" data-booking-country required></select>
									</div>
									<small class="twostars-booking-form__error" data-field-error="country"></small>
								</div>
								<label class="twostars-booking-form__field twostars-booking-form__field--wide">
									<span><?php esc_html_e('Notes For The Team', 'twostars-theme'); ?></span>
									<textarea name="notes" rows="5" placeholder="<?php esc_attr_e('Share private timing requests, children in the group, exact pickup details, or anything else the team should know before confirming.', 'twostars-theme'); ?>"></textarea>
									<small class="twostars-booking-form__error" data-field-error="notes"></small>
								</label>
							</div>

							<label class="twostars-booking-form__consent">
								<input type="checkbox" name="contact_consent" value="1" checked>
								<span><?php esc_html_e('I’m happy for Two Stars to contact me by email or phone to confirm this booking request.', 'twostars-theme'); ?></span>
							</label>

							<div class="twostars-booking-form__footer">
								<div class="twostars-booking-form__status" data-booking-status>
									<?php esc_html_e('Choose a tour, date, and time to activate booking.', 'twostars-theme'); ?>
								</div>
								<button type="submit" class="twostars-booking-form__submit" data-booking-submit>
									<?php esc_html_e('Send Booking Request', 'twostars-theme'); ?>
								</button>
							</div>
						</form>

						<div class="twostars-booking-success" data-booking-success hidden>
							<p class="twostars-booking-success__eyebrow"><?php esc_html_e('Request Received', 'twostars-theme'); ?></p>
							<h4><?php esc_html_e('Thank you. Your booking request is now with the Two Stars team.', 'twostars-theme'); ?></h4>
							<p class="twostars-booking-success__text" data-booking-success-text></p>
							<div class="twostars-booking-success__meta" data-booking-success-meta></div>
							<div class="twostars-booking-success__actions">
								<a class="twostars-booking-success__button twostars-booking-success__button--primary" href="<?php echo esc_url(twostars_theme_get_whatsapp_url()); ?>" target="_blank" rel="noopener">
									<?php esc_html_e('Open WhatsApp', 'twostars-theme'); ?>
								</a>
								<button type="button" class="twostars-booking-success__button twostars-booking-success__button--ghost" data-booking-reset>
									<?php esc_html_e('Plan Another Booking', 'twostars-theme'); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="twostars-booking-page__reasons">
		<div class="twostars-booking-page__reasons-shell">
			<div class="twostars-booking-page__intro twostars-booking-page__intro--center">
				<p class="twostars-booking-page__section-label"><?php esc_html_e('Why Book Direct', 'twostars-theme'); ?></p>
				<h2><?php esc_html_e('A smoother booking path for the real Two Stars experience.', 'twostars-theme'); ?></h2>
			</div>
			<div class="twostars-booking-page__reason-grid">
				<article class="twostars-booking-page__reason-card">
					<h3><?php esc_html_e('Real Routes, Clearly Shown', 'twostars-theme'); ?></h3>
					<p><?php esc_html_e('You are choosing from the current Two Stars tour collection only, with the route style, stops, and tour feel visible before you book.', 'twostars-theme'); ?></p>
				</article>
				<article class="twostars-booking-page__reason-card">
					<h3><?php esc_html_e('Tour Planning In One Step', 'twostars-theme'); ?></h3>
					<p><?php esc_html_e('Pickup area, group size, country, and route notes are collected in the same flow so the team can confirm without unnecessary back-and-forth.', 'twostars-theme'); ?></p>
				</article>
				<article class="twostars-booking-page__reason-card">
					<h3><?php esc_html_e('Fast Local Follow-Up', 'twostars-theme'); ?></h3>
					<p><?php esc_html_e('Every request creates a booking record, creates or updates a lead for the team, and triggers an admin email immediately.', 'twostars-theme'); ?></p>
				</article>
			</div>
		</div>
	</section>
</div>
<?php
get_footer();
