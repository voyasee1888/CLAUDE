# Voyasee BagFit 7.1.0

## Why this release

A full structural audit of 7.0.1 found that the data engine and airline dataset were accurate, but three things were holding the public tool back: a finished feature that never reached the page, a footer that only exposed a fraction of the configured cross-promotion links, and a declared typeface that was never actually loaded. This release fixes all three.

## 1. Smart Fix Plan is now visible

`VSB_Advisor` already computed a `smart_plan` object on every check — what-if scenarios and a ready-to-read airport script — but neither the result page nor the printable report rendered it. It does now:

- A new **Smart Fix Plan** section appears on the result page after "Before you fly," showing up to four what-if cards ("If you reduce that side by 4cm → the recorded size rule would pass").
- A **"Show this to airport or check-in staff"** box with the generated script and a one-tap **Copy** button.
- The same two elements are added to page 2 of the printable/PDF report.
- No new data, no new API calls — this was already computed server-side on every request and simply wasn't displayed.

## 2. Footer now uses every configured link

The settings panel has always accepted 14 Voyasee tool URLs and 14 affiliate URLs, but the public footer only rendered 6 of each.

- **Prepare your trip** now lists all 14 tools: Packing List, Medicine Checker, Transit Risk, Travel Passport, Trip Budget, Travel Printables, Smart Travel Hub, Best Time to Visit, Compare Destinations, Destination Quiz, Interactive Travel Map, Scam Shield, Book Flights, Book Tours.
- **Optional booking resources** now lists all 14 affiliate partners, including the previously unused Kiwi, Booking.com Asia-Pacific/Middle East, Yesim, VisaHQ, 12Go Asia, Klook, Malaysia Airlines and the luggage-retailer slot.
- The required Booking.com (EU) entry is unchanged: always present, labeled exactly "Booking.com."
- Both footer columns were collapsed `<details>` accordions, with the booking-resources one **closed by default** — meaning most visitors never saw it. Both are now always-visible sections, which should directly improve link visibility and click-through versus 7.0.1.
- Grids now use `auto-fit` sizing instead of a fixed column count, so they reflow cleanly whether 6 or 14 cards are present, on any screen width.
- Footer text sizes increased slightly (8–10px → 9–11px) for mobile legibility.

## 3. Typography now actually renders as designed

The stylesheet has always specified `Inter` as the display font, but the plugin never loaded it — visitors were silently seeing their OS default font (Segoe UI, San Francisco, etc.) instead. This release enqueues the Inter variable font (weights 400–900) via Google Fonts when the shortcode renders, so the existing type scale — including the 750/850 in-between weights already used in the CSS — renders as intended.

*Note: this keeps BagFit's existing navy/cyan "scanner" aesthetic rather than switching to the Cormorant Garamond + DM Sans / navy-gold system used elsewhere in the Voyasee suite. That system-wide alignment is a separate decision and wasn't made here — flag it if you want that pass done too.*

## Also fixed

- Source-monitor's outgoing User-Agent string was hardcoded to `/6.2` while the plugin had moved past 7.0; it now reads the live plugin version automatically.

## Not changed in this release

- No airline or airport data was added, removed, or altered.
- No new external services or paid APIs were introduced (Google Fonts is a free, keyless asset CDN).
- Booking.com APAC/Middle East was added as an **additional, separately labeled** footer entry, not a replacement for the required EU "Booking.com" link or any geo-detection logic.
- The data-freshness clustering (~250 airlines last-verified within the same 48-hour window) and the granular per-dimension ticket-input fields supported by the engine but not exposed in the UI are still open items from the original audit — not addressed here.
