=== Voyasee Tipping Calculator ===
Contributors: voyasee
Tags: tipping, tip calculator, travel, gratuity, currency
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.2.3
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium, worldwide tipping calculator powered entirely by Voyasee's own
tipping intelligence. 200+ countries, culture-aware advice, an infographic
result, bill splitting, share links and print/PDF. No third-party API.

== Description ==

Most "tipping calculators" are a percentage box that ignores where you are.
This one is built around the one thing that actually matters when you travel:
the local tipping culture.

Pick a country, choose what you are paying for, type the bill, and you get an
exact tip plus a plain-language verdict — including the cases every other
calculator gets wrong:

* Countries where tipping is **not expected** (or can even cause offence) get a
  respectful "no tip needed here" instead of a forced number.
* Countries where a **service charge is usually already included** get a clear
  "don't tip twice" warning.
* Countries where **small tips (baksheesh) are customary** get a "carry small
  change / cash preferred" note.

Everything runs on the plugin's own curated dataset. No paid API, no free API,
no external service is contacted at runtime — the data ships inside the plugin.

= Features =

* 200+ countries and territories, each with its own tipping culture and currency.
* Ten services: restaurant, cafe/bar, taxi/rideshare, food delivery, tour
  guide/driver, spa/salon/barber, hotel housekeeping (per night), hotel porter
  (per bag), valet parking and hotel concierge.
* Offline country auto-detect from the browser timezone (no GPS or permission).
* "Easiest cash tip" suggestion — a clean amount to hand over in local cash.
* Installable as an app (PWA) that works fully offline.
* Embeddable on other sites via an <iframe> route.
* Service-quality adjustment (below par / as expected / great) that stays inside
  each country's real range — it never invents numbers.
* Split the tip and total between any number of people.
* Optional "round up the total" and an optional approximate conversion into your
  home currency (from a bundled reference table, clearly labelled as a guide).
* Infographic result: bill-vs-tip bar, per-person breakdown, a local
  tipping-culture gauge, and a typical-range scale.
* Shareable result links (the exact calculation reopens for anyone you send it
  to) and one-tap print / save-to-PDF.
* A server-rendered "at a glance" guide that works with JavaScript disabled.
* Premium, self-contained deep-blue theme with an HD background that never
  clashes with your site theme; works the same on phone, tablet and desktop,
  including in-app browsers (Facebook / Instagram / WebView).
* A footer linking all 16 sibling Voyasee tools plus affiliate links, all editable.
* One-tap Print / Save-as-PDF produces a clean, branded "tipping receipt".

= Programmatic SEO pages (Phase 2 + 3) =

Optionally enable a full set of programmatic pages that capture the "how much to
tip in X" search demand around the main tool:

* `/tipping-in-{country}/` — full country guide (e.g. `/tipping-in-japan/`).
* `/tipping-in-{country}/{service}/` — service-specific guide (e.g.
  `/tipping-in-france/taxi/`), served only where that service is actually tipped.
* `/tipping-in-{region}/` — region hubs (e.g. `/tipping-in-europe/`).
* `/tipping-guides/` — a master index of every country.

Each renders the calculator pre-set to the relevant country and service, with
real server-rendered content, service-aware structured data, and registration
in the WordPress sitemap.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` and activate it.
2. Add the calculator to any page with the shortcode `[voyasee_tipping_calculator]`.
3. (Optional) Go to Settings → Tipping Calculator to set a default country,
   a default "also show in" currency, toggle the per-country SEO pages, and
   edit the footer tool / affiliate links.
4. If you enable the per-country pages, visit Settings → Permalinks and click
   Save once so the new URLs are registered.

You can also preset a country: `[voyasee_tipping_calculator country="JP"]`.

== Frequently Asked Questions ==

= Does this use any paid or free third-party API? =
No. All tipping and currency data is bundled with the plugin and read locally.
Nothing is fetched from an external service at runtime.

= How accurate are the amounts? =
Percentage-based tips (restaurant, taxi, etc.) are exact, because they are a
percentage of the bill you enter, in that country's own currency. Flat guidance
(hotel housekeeping and porter) is converted from a bundled reference table and
rounded to a sensible local amount, clearly presented as a guideline. Countries
with thinner data are marked as "general guidance" rather than given false
precision.

= Is the home-currency conversion a live rate? =
No. It uses a static reference table bundled with the plugin and refreshed at
each release, shown as an approximate guide. Confirm the real rate with your
bank or card provider before relying on it.

= Will it work in the Facebook or Instagram in-app browser? =
Yes. The front-end is a single classic script authored in an
older-browser-safe style, and the dataset is embedded inline rather than
fetched, so it runs reliably across desktop, tablet, mobile and in-app browsers.

== Changelog ==

= 1.2.3 =
* The 1.2.2 CSS-based fix for the white bill-amount box was reported as still
  not visible live. Rather than add another external stylesheet rule that
  depends on cache-busting and load order, the fix has been moved directly
  into the page's own HTML: the bill-amount field, its label, the country
  selector and search field, the "Round up the total" / "Also show in" text,
  and every other field label now carry their colours as inline styles with
  `!important`. Inline `!important` styles take precedence over essentially
  any theme or cached stylesheet, and take effect immediately because they are
  part of the freshly-rendered page markup, not a separate cached CSS file.
* Verified by loading the page with a simulated hostile theme stylesheet that
  forces every input, select, button, label and span to a white background
  with black text (`!important`) -- the bill-amount box, its text, and every
  affected label still render correctly in the intended colours.
* If you still don't see this change: the active plugin version shown in the
  tool's own footer ("v1.2.3") and in Plugins → Installed Plugins must say
  1.2.3. If it still shows an older number, the upload did not actually
  replace the plugin files -- see the note below.

**Important if updates keep appearing not to apply:** WordPress's Plugins ->
Add New -> Upload Plugin screen can silently fail to replace an already-active
plugin folder of the same name. Before uploading a new zip, first go to
Plugins, Deactivate "Voyasee Tipping Calculator", Delete it, and only then
upload the new zip and Activate it. Also do a hard refresh (Ctrl/Cmd+Shift+R)
or test in a private/incognito window afterwards, in case your browser or a
page-caching plugin cached the old version.

= 1.2.2 =
* Fixed the bill-amount field turning invisible (white box, unreadable text)
  while actively typing. Root cause: Chrome/Safari paint their autofill
  highlight using an internal box-shadow rather than background-color, which
  silently defeated the earlier background override the moment the browser
  treated the field as fillable. Added the standard autofill override (forcing
  our own background and text colour back in with the same box-shadow
  technique) plus `autocomplete="off"` on both the bill-amount and country-
  search fields, and an explicit caret colour so the blinking cursor itself is
  always visible.
* Brightened the form section labels -- "Where are you?", "What are you paying
  for?", "Bill amount", "How was the service?", "Split between" -- from a dim
  blue-grey to full bright white/near-white for clear legibility against the
  blue background.
* Re-verified by typing into the bill-amount field and confirming the text and
  caret stay visible throughout, on both desktop and mobile.

= 1.2.1 =
* Fixed the Egyptian Pound (EGP) symbol rendering as the literal text
  "E\u{00A3}" instead of "E£" -- a PHP string-quoting bug (the escape was in a
  single-quoted string, which PHP never expands). All 147 bundled currency
  symbols were re-scanned and confirmed clean; EGP was the only one affected.
* Fixed overlapping text in the "at a glance" cards and the "typical range for
  this service" panel: a long label ("Hotel housekeeping") next to a long
  value ("~E£95.00 per night") could run into each other. Both are now laid
  out so a label and its value can never collide, regardless of how long the
  country's currency symbol or amount is.
* Re-verified with the exact scenario reported (Egypt, valet parking) on both
  desktop and mobile -- no broken symbols, no overlapping text.

= 1.2.0 =
* New premium deep-blue HD background and theme, with all text re-tuned for
  high contrast and legibility on blue.
* Fixed the bill-amount field rendering with a white background on some host
  themes (it now correctly matches the dark-blue theme), and hardened the
  country search and currency dropdown against theme style bleed.
* Fixed the "typical range for this service" scale where the Low / Standard /
  Generous labels and amounts could run together — now a clean aligned layout.
* Brightened the "Round up the total" and "Also show in" labels for visibility.
* Corrected the hero stat to "10 service types" (valet and concierge were added
  in 1.1.0).
* Footer now links all 16 sibling Voyasee tools (Travel Passport, Interactive
  Travel Map, Interactive World Map, Smart Travel Hub, Trip Budget Calculator,
  Packing List, Carry-On Size Checker, Travel Scam Checker, Destination Quiz,
  Destination Comparison, Travel Month Planner, Medicine & Items Checker, Jet
  Lag Recovery Planner, Transit Visa & Layover, Schengen Day Bank, Best Area to
  Stay) — all editable in Settings.
* Print / Save-as-PDF now opens a dedicated, self-contained branded "tipping
  receipt" (country, service, date, bill, tip, total, per-person, cash tip and
  note) instead of printing the whole themed page — a clean one-card PDF.
* Re-verified layout and legibility across desktop, tablet and mobile.

= 1.1.0 =
* More scenarios: added Valet parking and Hotel concierge, for ten services in all.
* Easiest cash tip: every result now also suggests a clean, hand-over-in-cash
  amount (the exact tip rounded up to a convenient local figure).
* Offline country auto-detect: the calculator now pre-selects your country from
  the browser's own timezone — no GPS, no permission and no external service —
  with a clear "detected" hint you can override.
* Phase 3 SEO: added service-level pages at /tipping-in-{country}/{service}/
  ("how much to tip a taxi driver in France", etc.), region hubs at
  /tipping-in-{region}/, a master index at /tipping-guides/, service-aware
  structured data, and registration of every guide in the WordPress sitemap.
  Service pages are only served where that service is actually tipped, keeping
  the set free of thin pages.
* Installable app (PWA): a web app manifest, app icons and a conservative,
  root-scoped service worker let visitors install the calculator and use it
  fully offline. The service worker only caches the plugin's own assets and
  tipping pages and passes every other request straight through, so it never
  interferes with the rest of the site. An "Install app" button appears where
  the browser supports it.
* Embeddable widget: a theme-free /tipping-embed/ route (with an optional
  ?country=XX preset) plus a ready-to-paste <iframe> snippet in Settings, so the
  calculator can be embedded on other sites.
* New settings: toggle for the installable app (PWA); the programmatic-pages
  toggle now also controls the service pages, region hubs, index and embed route.

= 1.0.0 =
* Initial release.
* Tipping intelligence dataset covering 200+ countries and territories across a
  five-cluster culture model (expected / appreciated / service-included /
  not-customary / baksheesh), with per-service overrides, cultural notes and
  honest confidence levels.
* Bundled currency metadata and static reference rates for 147 currencies; no
  runtime API.
* Matching calculation engine in PHP (authoritative, used by REST and the
  per-country pages) and ES5-safe JavaScript (instant client-side results),
  verified to produce identical figures.
* Premium dark-first UI with an HD CSS background, searchable 200+ country
  selector, service chips, quality control, bill splitting, round-up and
  home-currency options.
* Infographic result: bill-vs-tip bar, per-person split, local tipping-culture
  gauge and a typical-range scale, plus a respectful "no tip needed" state and
  a "don't tip twice" service-charge warning.
* Shareable deep-link results and print / PDF output.
* Server-rendered "at a glance" country guide (no-JavaScript fallback).
* Phase 2: optional programmatic `/tipping-in-{country}/` landing pages with
  SoftwareApplication, BreadcrumbList, HowTo and FAQPage structured data, plus
  generic tool schema on any page containing the shortcode.
* Settings page for default country, default home currency, per-country page
  toggle and footer tool / affiliate links.
