# Launch runbook and access needed

Written 26 Oct 2026. Order matters: staging first, production last.

## 1. What I need from you (never put passwords in chat)

| For | What to set up | Why |
|---|---|---|
| Deploying code and content | **SSH access with a key** to the hosting account (cPanel > Terminal / SSH Access > Manage SSH Keys; add my public key, I will give it to you). If SSH is not offered, an **SFTP/FTP account limited to `public_html/wp-content`**. | The content updater is a PHP script that must run on the server (`php wp-content/tools/content-update.php`). Without SSH it can be run once from a temporary file, but SSH is far safer and quicker. |
| Backups, staging, cron | **cPanel access stays with you.** Please create (1) a full backup (UpdraftPlus or cPanel Backup) and (2) a staging copy, for example `staging.twostarstourandsafari.com` (cPanel > Domains + copy of the site, or Softaculous > Clone). | A staging copy is required by the brief. I do not need the cPanel password for this. |
| WordPress admin | A **WordPress administrator user** for me, or an **Application Password** on your own admin user. | To run plugin updates, check Elementor, and read settings. |
| Speed: CDN | **Cloudflare account** (free plan) and ability to change the domain's nameservers, or add me as a member of the Cloudflare account. | CDN, caching rules and image delivery. |
| Search Console | In Search Console add the site as a property (Domain or URL prefix) and either give me the **HTML tag code** (the `content=` value) or add me as a user with Full permission. | To verify, submit the sitemap, and check indexing. |
| Google Analytics | Create a GA4 property and send me the **Measurement ID (G-XXXXXXXXXX)**. | Goes in Settings > Two Stars Tracking. |
| Meta Pixel | Create a Pixel in Meta Events Manager and send me the **Pixel ID (digits)**. | Goes in Settings > Two Stars Tracking. |
| Tripadvisor | The **listing URL** once Tuli has created it. | Shows on the Reviews page automatically. |

Tuli's Google Business Profile link is already stored: https://share.google/Htz6pMs4mSZQ2CXJm
(it appears in the footer, on the Reviews page and in the site's TravelAgency schema `sameAs`).

I cannot type your passwords into login screens, and I should not be given them in chat.

## 2. Staging run (about 30 minutes)

1. Full backup of production files and database (UpdraftPlus).
2. Create staging as a copy of production. Turn on "discourage search engines" on staging.
3. Upload from git: `themes/twostars-theme`, `plugins/twostars-platform`, `plugins/twostars-elementor-widgets`, and `tools/`.
4. Copy `wp-content/uploads/2026/10/` (about 6 MB: photos, hero, two videos, posters).
5. Dry run: `php wp-content/tools/content-update.php --dry-run`, read the output.
6. Apply: `php wp-content/tools/content-update.php`.
7. Elementor > Tools > Regenerate CSS and Data. Settings > Permalinks > Save. Clear any cache.
8. Walk the checklist in section 5.

## 3. Production run

Same steps as staging, after Tuli approves staging. Take a fresh backup first. Keep the old site live until the new one is checked.

## 4. Tracking and speed (needs live access)

- Settings > Two Stars Tracking: GA4 ID, Meta Pixel ID, Search Console code, Tripadvisor URL.
  GA4 and the Pixel load only after a visitor accepts the cookie banner.
  Events already wired: `book_now_click`, `whatsapp_click`, `form_submit`, `booking_complete`.
  In GA4 mark them as key events (conversions).
- Search Console: verify, submit `https://twostarstourandsafari.com/sitemap_index.xml` (Yoast), check Pages > Indexing.
- Rich Results Test (search.google.com/test/rich-results) on the home page, one tour page, shore excursions, private tours.
  The site outputs TravelAgency, TouristTrip with offer, FAQPage and BreadcrumbList.
- Speed pass on production (needs SSH/WP admin):
  1. Convert the old JPEG library images to WebP (about 60 files from the original WhatsApp exports) and update their
     references. Do it with a backup, and keep the originals until checked.
  2. Install one caching plugin (LiteSpeed Cache if the host is LiteSpeed, otherwise WP Super Cache or W3 Total Cache),
     minify CSS/JS, enable page cache and browser cache.
  3. Cloudflare in front: cache static files, Brotli, Polish/Mirage off (images are already WebP).
  4. Remove unused plugins and the unused Elementor widgets; deactivate the nulled Elementor Pro copy
     (`nulled-elementor-pro-main`) if it is installed on production: it is an unlicensed copy and a security risk.
  5. Preload the hero image (already `fetchpriority=high`). Lazy-load below the fold (already done).
  6. Measure on PageSpeed Insights (mobile) for `/`, `/tours/` and a tour page. Targets: LCP under 2.5 s,
     CLS under 0.1, INP under 200 ms.

## 5. Checklist before and after launch

- [ ] Every page at 375, 768 and 1280 px.
- [ ] Book now (header, mobile bar, tour pages), WhatsApp buttons and the assistant open and work.
- [ ] Lead forms (home, contact, shore excursions, private tours) arrive in the CRM.
- [ ] Old tour URLs redirect (`/tours/flamingo-and-pink-lake-tour/`, `/tours/private-tour/`, `/tours/sandwich-harbour-4x4-adventure/`).
- [ ] Terms and Privacy linked in the footer; cookie banner appears once and remembers the choice.
- [ ] Title, description and a single H1 on every page.
- [ ] Schema validates.
- [ ] No "N$" prices, no placeholder text.
- [ ] Google Cloud billing fixed so the assistant's AI works.

## 6. Needs a decision from Tuli before launch

- Terms: weather clause says "we will contact you to agree the next step". Confirm what you actually offer if a tour cannot run.
- Cruise guests: whether a "back-to-ship guarantee" can be promised. The pages do not promise one.
- Guides drafts: two articles are saved as drafts for her to read.
- Guide names, portraits and bios for Meet the Team (only Tuli is listed so far, without a portrait).
- The Dune 7 photo she is sending (the Swakopmund photo was removed).
