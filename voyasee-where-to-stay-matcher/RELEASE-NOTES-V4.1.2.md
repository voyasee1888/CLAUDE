# Voyasee Where to Stay Matcher 4.1.2 — the 4.1.1 focus-state fix wasn't strong enough

## What was reported

After installing v4.1.1, the destination field on the live site still went
invisible (cream text on white) while actively typing -- the exact same
symptom the 4.1.1 release targeted. Confirmed with the user that the new zip
had actually been installed, ruling out "still running the old version" as
the explanation.

## Root cause

The 4.1.1 fix re-asserted background/text color on
`.vwtsm-root .vwtsm-input:focus` (two chained classes). That's *usually*
enough, but CSS only resolves a tie between two `!important` declarations by
specificity, then by source order -- it doesn't matter that our declaration
also says `!important` if the competing rule is more specific or loads later.
Page builders in particular (Elementor is visible in this site's admin bar)
commonly scope their own generated CSS to an auto-generated container ID
(e.g. `#elementor-widget-xyz input:focus`), and an ID selector always
outranks a class-based one. That's almost certainly what was still winning.

## What shipped

- Added `#vwtsm-destination:focus`, `#vwtsm-nights:focus`, and
  `#vwtsm-travel-date:focus` directly to the color-reasserting rule,
  alongside the existing class-based one. An ID selector on the exact field
  outranks essentially any class-based selector a theme or builder could
  reasonably use, closing the specificity gap for good rather than guessing
  at a slightly-higher specificity workaround.
- Also added `!important` to `-webkit-text-fill-color` (missed in 4.1.1) and
  `border-color`, so every color-relevant property on the focused state is
  now equally locked down, not just `background`/`color`.

## Verification

- CSS brace balance verified (330 open / 330 close) after the edit.
- Could not verify against the live site's actual Elementor/theme CSS in
  this environment (no live WordPress install available) -- if this still
  doesn't resolve it, the next diagnostic step would be to inspect the
  destination input in the browser's DevTools while it's focused, to see
  literally which stylesheet/selector is winning the "background" property,
  rather than guessing at a third fix blind.
- Also worth ruling out on the live site: after uploading a plugin update,
  a caching plugin (WP Rocket, LiteSpeed Cache, etc.) or a CDN can keep
  serving the *old* CSS file to visitors until its cache is purged, which
  would look identical to "the fix didn't work" even when it did.
