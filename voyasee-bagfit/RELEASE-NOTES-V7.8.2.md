# Voyasee BagFit 7.8.2 — Stray apostrophe in bag weight display

## What you spotted

In the "Every Bag" results list, a bag's weight rendered as `0'kg` — with a stray apostrophe jammed between the number and the unit, instead of `0 kg`. Notably, the same weight value rendered correctly elsewhere on the same result (the airport-staff script text showed "0 kg" with a normal space), which narrowed this down to one specific rendering path rather than a problem with the underlying data.

## Root cause

The number-formatting helper used throughout the result page (`num()`) calls JavaScript's `toLocaleString(undefined, ...)`. Passing `undefined` as the locale tells the browser to use whatever locale *the visitor's own browser or operating system* reports — not a fixed, predictable format. Different locales format numbers differently, including using different characters as a thousands separator. Tested directly: under a Swiss-German locale, the same formatting call renders `1000` as `1'000` — using an apostrophe-like character as the separator. This is a real, demonstrable mechanism, and explains why the same underlying value can render differently depending on the visitor's system settings, with no change to the actual stored data.

## Fix

Changed the locale argument from `undefined` to a fixed `'en-US'`, so every visitor sees the same, predictable number formatting regardless of their own browser or OS locale settings. This is also the more correct choice generally — the rest of the tool's interface is in English, so the numbers should consistently use the matching Western number format rather than silently varying per visitor.

Checked the rest of the codebase for the same risk: this was the only `toLocaleString` call in the whole file, and the equivalent PHP-side number formatting (used in the printable report) already hardcodes its decimal and grouping characters explicitly, so it was never affected.

## Verification

- Tested the fix directly: confirmed `0`, `1000`, `24`, and decimal values all render in clean, consistent `en-US` format
- Confirmed only one `toLocaleString` call exists in the codebase, now fixed
- `node --check` clean on app.js
- PHP `php -l` clean on all files (unaffected by this change, re-checked for completeness)
- CSS brace balance re-verified (474/474, unaffected by this change)

No data or layout changes in this release — a single, targeted JavaScript fix.
