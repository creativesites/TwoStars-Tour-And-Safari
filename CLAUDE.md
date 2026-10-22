# Two Stars Tour & Safari — Claude Code Project Guide

## Project Structure

- **Theme:** `themes/twostars-theme/` — custom WordPress theme
- **Elementor Widgets Plugin:** `plugins/twostars-elementor-widgets/` — all custom page-builder widgets
- **Platform Plugin:** `plugins/twostars-platform/` — CPTs, REST endpoints, CRM logic

### Widget Plugin Layout

```
src/Widgets/           PHP widget classes (BaseWidget + per-widget)
assets/css/widgets/    Per-widget CSS files (auto-registered by BaseWidget)
assets/css/widgets.css Global CSS design tokens + shared component styles
assets/js/widgets.js   Global JS (animations, navigation, form handling)
assets/js/widgets/     Per-widget JS files (e.g. gallery.js)
```

`BaseWidget::register_widget_style()` auto-registers `assets/css/widgets/{slug}.css` — just create the file and it loads automatically when the widget renders.

Scripts declared in `get_script_depends()` must be registered in `Plugin::register_assets()`.

---

## Design System

**Typography:**
- Headings: Cormorant Garamond (`var(--font-display)`)
- Body / UI: Montserrat (`var(--font-body)`)

**CSS Custom Properties (defined in `assets/css/widgets.css`):**

```css
/* Colors */
--navy-primary:     #1B3A6B   /* primary brand anchor */
--adventure-orange: #C96F3B   /* CTA accent (use sparingly) */
--coastal-cyan:     #5F9EA8   /* secondary accent */
--sahara-sand:      #C9A37B   /* decorative warm accent */
--sunset-gold:      #B78A56
--deep-navy:        #101C2E
--desert-cream:     #F5EFE6   /* warm background */
--pure-white:       #FFFFFF
--warm-ivory:       #FBF7F0

/* Typography */
--font-display:  "Cormorant Garamond", serif
--font-body:     "Montserrat", sans-serif

/* Layout */
--spacing-section: 10rem      /* standard vertical section padding */
--content-max:     1400px     /* standard content max-width */
--content-max-narrow: 900px

/* Functional aliases */
--color-sunset-orange: var(--adventure-orange)
--color-deep-night:    var(--deep-navy)
--color-ivory:         var(--warm-ivory)
```

**Palette Intent:**
- Navy primary: structural anchors, backgrounds, headings
- Desert cream / warm ivory: warm section backgrounds
- Orange accent: CTAs, eyebrow labels, interactive highlights — use sparingly
- Coastal cyan: secondary interactive, icons, link accents — use sparingly
- Sahara sand: decorative borders, image frames, badge details

---

## Animation System

Global IntersectionObserver animation is defined in `widgets.css` + `widgets.js`:

```css
[data-twostars-animate]           { opacity: 0; transition: opacity 0.8s ease, transform 0.8s ease; transition-delay: var(--twostars-delay, 0s); }
[data-twostars-animate="fade-in-up"]    { transform: translateY(30px); }
[data-twostars-animate="fade-in-left"]  { transform: translateX(-30px); }
[data-twostars-animate="fade-in-right"] { transform: translateX(30px); }
[data-twostars-animate="scale-in"]      { transform: scale(0.9); }
[data-twostars-animate].is-visible      { opacity: 1; transform: translate3d(0,0,0) scale(1); }
```

Use `data-twostars-animate="fade-in-up"` on section headers, `scale-in` on cards/items.
Use `style="--twostars-delay: 0.15s"` for stagger.
`initAnimations(root)` in widgets.js observes all `[data-twostars-animate]` elements and adds `is-visible` when they enter the viewport.

**Elementor hook for widget re-init:**
```js
window.elementorFrontend.hooks.addAction('frontend/element_ready/twostars-{widget-name}.default', function(scope) {
  initMyWidget(scope[0]);
});
```

---

## Responsive Breakpoints

```
> 1024px  Desktop — full layout
768–1024px Tablet — reduced columns/gaps
< 768px   Mobile — single column, stacked
< 640px   Small mobile
```

Section padding:
- Desktop: `var(--spacing-section)` = 10rem
- Tablet: 7rem
- Mobile: 5rem

---

## Widget Checklist (new widgets)

- [ ] Cormorant Garamond for all headings
- [ ] Montserrat for body/UI text
- [ ] Use CSS custom properties — never hardcode palette values
- [ ] Section padding: `var(--spacing-section) 5%`
- [ ] Inner max-width: `width: min(100%, var(--content-max)); margin: 0 auto`
- [ ] Scroll-triggered entrance animations (`data-twostars-animate`)
- [ ] Reduced-motion support (`@media (prefers-reduced-motion: reduce)`)
- [ ] Mobile-first responsive (test at 375px, 768px, 1024px, 1440px)
- [ ] WCAG AA contrast on all text
- [ ] Keyboard navigation + visible focus states
- [ ] All content editable via Elementor controls (no hardcoded strings except defaults)
- [ ] Orange and cyan used sparingly (eyebrows, icons, CTAs only — not backgrounds)

---

## About Page — Widget Specifications

**Design Principle:** Elegant, warm, wide layout. Generous whitespace. Restrained accent use.

### Widget 1: About Hero Section

Full-width hero, 60vh desktop / 50vh tablet / 40vh mobile.
Background image with overlay gradient: `rgba(13,27,42,0.3) → rgba(27,58,107,0.5)`.
Headline: Cormorant Garamond, `clamp(3rem, 6vw, 4.5rem)`, weight 400, white.
Subheadline: Montserrat 400, `clamp(1rem, 2vw, 1.3rem)`, `rgba(255,255,255,0.9)`, max-width 700px.
Controls: Background Image, Headline, Subheadline, Content Alignment, Enable Parallax, Overlay Opacity.

### Widget 2: Who We Are — Two Column Story

Background: `var(--desert-cream)`. Padding: `var(--spacing-section) 5%`.
Grid: `1.2fr 1fr` desktop, single column mobile. Gap: 6rem.
Left: Section Label (orange, 0.85rem, 600, uppercase, letter-spacing 3px) + Title (Cormorant 600, clamp) + Body (Montserrat 400, 1.05rem, 1.8 line-height) + optional Stats Row.
Right: Image with border-radius 12px, box-shadow, optional 8px white border. Decorative Sahara sand circle accent.
Text slides in from left, image from right (scroll-triggered).
Controls: Label, Title, Body (WYSIWYG), Stats (toggle + 3×number/label), Image, Image Position.

### Widget 3: Why Choose Us — Value Grid

Background: `var(--navy-primary)`. Padding: `var(--spacing-section) 5%`.
Centered header: title (Cormorant 600, white) + optional subtitle (Montserrat 400, rgba white 0.8).
Cards grid: 3 cols desktop / 2 tablet / 1 mobile. Gap 3rem.
Card: `rgba(255,255,255,0.05)` bg, `blur(10px)` backdrop, 1px rgba-white border, border-radius 12px, padding 2.5rem.
Card hover: background lightens, border-color rgba cyan 0.3, translateY(-4px).
Icon: coastal cyan or sahara sand, 48px.
Title: Cormorant 600, 1.5rem, white.
Description: Montserrat 400, 0.95rem, rgba white 0.85.
Stagger animation on cards (0.15s per card).
Controls: Title, Subtitle, Columns (2/3/6), Value Items repeater (icon, title, description, max 6).

### Widget 4: Tour Overview Cards

Background: white. Padding: `var(--spacing-section) 5%`.
Centered header: eyebrow (orange) + title (Cormorant) + subtitle (Montserrat 400, rgba navy 0.7).
Cards grid: 3 cols desktop / 2 tablet / 1 mobile. Gap 2.5rem.
Card: white bg, border-radius 12px, overflow hidden, box-shadow, hover translateY(-8px).
Image: 240px height, object-fit cover, hover scale(1.08).
Content: Tour Title (Cormorant 600, 1.6rem, navy) + Description (Montserrat 400, 0.95rem) + Learn More link (Montserrat 600, coastal cyan, uppercase, arrow icon).
Controls: Label, Title, Subtitle, Auto-populate toggle, Manual Cards repeater, Grid Columns.

### Widget 5: Our Commitment — Promises Section

Background: linear gradient `#F7EDE2 → #F0E5D8`. Padding: `var(--spacing-section) 5%`.
Centered title (Cormorant 600). Items grid: 2 cols desktop / 1 mobile. Gap 3rem.
Item layout: flex row, gap 1.5rem. Icon in 50×50 circle (rgba navy 0.08). Title: Cormorant 600, 1.4rem, navy. Description: Montserrat 400, 0.95rem.
Stagger animation (0.1s per item).
Controls: Title, Commitment Items repeater (icon, title, description, max 8), Items Per Row.

### Widget 6: Meet the Team (Optional)

Background: white. Padding: `var(--spacing-section) 5%`.
Grid: 3 cols desktop / 2 tablet / 1 mobile. Gap 3rem.
Card: centered. Photo: 200×200 circle, Sahara sand border 4px, box-shadow, hover scale(1.05).
Name: Cormorant 600, 1.5rem, navy. Role: Montserrat 600, 0.85rem, coastal cyan, uppercase.
Bio: Montserrat 400, 0.95rem. Optional quote: Cormorant italic, border-top.
Controls: Title, Subtitle, Team Members repeater, Members Per Row.

### Widget 7: Location Expertise — Split Section

Background: `var(--desert-cream)`. Padding: `var(--spacing-section) 5%`.
Grid: `1fr 1fr` desktop, single column mobile. Gap 6rem.
Left: Title (Cormorant 600) + Body (Montserrat 400, 1.05rem) + optional Highlights grid (2 cols, icon + text).
Right: Image or map embed, border-radius 12px, box-shadow. Optional Adventure orange decorative border.
Controls: Title, Body (WYSIWYG), Enable Highlights toggle, Highlights repeater, Visual Type (image/embed), Image/Map.

### Widget 8: CTA Section — Book Your Adventure

Background: `linear-gradient(var(--navy-primary), var(--deep-navy))`. Padding: 8rem 5%. Centered.
Headline (Cormorant 600, white) + Subtext (Montserrat 400, rgba white 0.85).
Primary Button: Adventure orange bg, Montserrat 700, uppercase.
Secondary Button: transparent, coastal cyan border.
Trust Elements row: Montserrat 500, 0.85rem, rgba white 0.7, coastal cyan icons.
Controls: Headline, Subtext, Primary Button (text + Calendly link), Secondary Button (text + WhatsApp link), Trust Elements repeater.

---

## Gallery Page — Widget Specifications

### GalleryWidget (twostars-gallery)

**Purpose:** Masonry / uniform-grid photo + video gallery with category filtering, lightbox, and infinite scroll.

**Layout Options:**
- `masonry` — JS column-based masonry (variable image height, natural proportions)
- `grid` — uniform CSS grid (all items equal aspect ratio)

**Category Filter:**
- Define categories in "Filter Categories" repeater (label + slug)
- Assign category slug to each image in "Image Metadata" repeater
- Filter bar renders pill buttons above grid; "All" always first
- Filtering shows/hides items client-side

**Controls:** Section Header (eyebrow, title, subtitle, background), Gallery Images (images, desktop/tablet/mobile cols, gap, lightbox toggle, hover zoom toggle, layout style, enable filters, filter all label), Filter Categories (repeater: label, slug), Image Metadata (repeater: position, caption, category slug), Pagination (images per page, load more text), Video Item (url, poster, caption, position, autoplay, muted, loop).

**CSS classes:** `twostars-gallery-page`, `twostars-gallery-page--bg-{white|cream|navy}`, `twostars-gallery-page--layout-{masonry|grid}`

**JS file:** `assets/js/widgets/gallery.js` — manages masonry columns, IntersectionObserver item animation, category filter, load-more, lightbox with swipe support and counter.
