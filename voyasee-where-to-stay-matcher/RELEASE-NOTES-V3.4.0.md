# Voyasee Where to Stay Matcher 3.4.0 — input fix + colorful infographic pass

## What was reported

New screenshots confirmed the v3.3.0 map fix is working beautifully in production (real Kyoto streets/wards, labeled pins, legend, zoom controls, proper attribution). Two things were asked for:

1. A real bug: on Step 1, the "Destination" and "How many nights" fields were rendering as plain white boxes with default browser text instead of the tool's styled dark input.
2. Two improvements: make the results page a more colorful infographic, and make the footer more unique/beautiful.

## Bug fix

Root cause: `.vwtsm-input`/`.vwtsm-select` had no defensive `!important` protection on background/border/text color, unlike headings (`h1`-`h6`), which already got this treatment back in 2.1.0 after the exact same class of bug made card names invisible. A WordPress theme's own `input[type=...]` styling (which, via an element+attribute selector, can carry *higher* CSS specificity than a bare `.vwtsm-input` class) can silently win the cascade and repaint the field white. Separately, Chrome's autofill layer paints its own white background over any CSS once a saved value is selected, regardless of `autocomplete="off"`.

Fix: `.vwtsm-root .vwtsm-input, .vwtsm-root .vwtsm-select` now forces background/border/color with `!important` (`includes/class-wtsm-matching-engine.php` untouched — this is purely `assets/css/matcher.css`), plus the standard long-transition trick to neutralize Chrome's `:-webkit-autofill` repaint. `.vwtsm-field-label` got the same `!important` hardening as a precaution.

## Results page: colorful infographic pass

Used the dataviz skill's method rather than picking colors by eye:

- Chose 5 categorical colors (Budget `#c98500`, Walkability `#199e70`, Nightlife `#9085e9`, Airport `#3987e5`, Safety `#008300`) from the skill's pre-validated dark-mode 8-hue set, in their given fixed order (never re-ordered/cycled).
- Validated with `scripts/validate_palette.js` against this plugin's actual navy surface (`#121b30`), not a generic default -- lightness band, chroma floor, and contrast all pass; CVD separation lands in the floor band, which the skill says is legal *only* with secondary encoding -- satisfied here since every colored element already carries a direct text label (never color alone).
- Applied consistently: Trip Reality strip chips, comparison scorecard bars, and a new icon on the comparison scorecard's row headings all use the same metric→color mapping. Match Score intentionally stays the existing brand gold everywhere (it's the one "hero number," not a categorical series) -- previously the scorecard's Match Score/Walkability/Nightlife/Safety bars all reused whichever neighborhood's *archetype* color happened to apply, which didn't actually encode anything meaningful.
- The "Price & Logistics" row (previously one plain concatenated text string per neighborhood) is now three colored icon pills (price/airport-time/center-time), reusing the same palette.
- Left the radar chart single-hued (still gold) -- rainbow-coloring a single-neighborhood radar's axes would misread as multiple series, which the dataviz skill flags as an anti-pattern.

## Footer redesign

- A thin brand-gold gradient seam across the top (the same idea as the match card's ticket-perforation seam).
- A very faint grid/glow texture layer so the footer isn't a flat color slab.
- A small compass emblem next to the "Voyasee" wordmark, echoing the hero background's compass-rose watermark.
- A small icon on every column heading (route/compass for Plan the Trip, shield for Safety & Documents, handshake for Travel Partners, info for Good to know).
- Dashed vertical seams between columns on wide layouts.
- Link hover now slides the arrow glyph rather than just changing color.

## Files changed

- `assets/css/matcher.css` -- input/label defensive fix, metric color tokens, reality-chip/compare-scorecard/logistics-pill styling, footer redesign.
- `assets/js/matcher.js` -- `metricColor()`/`metricIcon()` helpers; `buildTripRealityStrip()` and `buildCompareScorecard()` now use per-metric color/icon instead of archetype color; Price & Logistics row rebuilt as pills.
- `templates/quiz-container.php` -- footer markup: compass emblem, column icons, arrow-wrapped links, texture layer div.

## Verification

- `php -l` clean on all PHP files, `node --check` clean on `matcher.js`, CSS brace balance verified.
- Palette validated with the dataviz skill's `scripts/validate_palette.js` against the plugin's real navy surface color, not a generic default.
- Traced the exact markup change for the reported Step 1 bug (`#vwtsm-destination` / `#vwtsm-nights` both use `class="vwtsm-input"`, confirming the fix targets the right elements) and reviewed every `VoyaseeMatcher.prototype.*` method name for duplicates/orphans after editing -- none found.
- No live WordPress install was available in this environment to visually confirm the rendered page.
