=== Voyasee Tipping Calculator ===
Contributors: voyasee
Tags: tipping, tip calculator, travel, gratuity, currency
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
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
* Eight services: restaurant, cafe/bar, taxi/rideshare, food delivery, tour
  guide/driver, spa/salon/barber, hotel housekeeping (per night) and hotel
  porter (per bag).
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
* Premium, self-contained dark theme with an HD background that never clashes
  with your site theme; works the same on phone, tablet and desktop, including
  in-app browsers (Facebook / Instagram / WebView).
* A footer with your other Voyasee tools and affiliate links, all editable.

= Per-country SEO pages (Phase 2) =

Optionally enable programmatic landing pages at `/tipping-in-{country}/` (e.g.
`/tipping-in-japan/`). Each page renders the calculator pre-set to that country
plus real "how much to tip in {country}" content and structured data, capturing
the large "how much to tip in X" search demand around the main tool.

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
