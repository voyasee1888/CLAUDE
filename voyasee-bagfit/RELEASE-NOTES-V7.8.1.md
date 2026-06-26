# Voyasee BagFit 7.8.1 — Hero graphic clipping after starting the form

## What you spotted

Once the form is in progress (the "compacted" hero state — smaller heading, smaller suitcase graphic, Live Trip Summary panel visible), a dimension label on the right side of the suitcase illustration was getting cut off mid-text, with only a stray "2..." visible where a measurement like "23 cm" should have shown in full.

## Root cause

The hero section's outer container (`.vsb7-hero`) uses `overflow: hidden`, which is needed elsewhere to keep a decorative background pattern inside the section's rounded corners. The suitcase graphic's dimension labels are positioned with fixed pixel offsets (e.g., `right: -28px`) rather than offsets that scale with the container.

When the form starts, the hero graphic shrinks (`height: 150px` plus a `0.75` scale) to make room for the live form below it. The left-side decorative elements (the "BAG CHECK" callout, the bottom-center "40 cm" label) stay safely inside the shrunk container. But the right-side elements — the "MULTI-FLIGHT / Strictest leg" callout and the depth dimension label — sit close enough to the edge that, once the container shrinks, they get pushed past the boundary and the parent's `overflow: hidden` clips them mid-character instead of just hiding them cleanly.

## Fix

Rather than trying to recalculate exact pixel offsets for a transform-scaled container (fragile, and prone to breaking again at some other zoom level or screen size), the right-side elements that were at risk are now hidden specifically in this compacted state: the "MULTI-FLIGHT" callout and the depth label. The left-side elements are untouched, since they were rendering correctly. Once the user has started filling in the form, these were decorative explainer callouts anyway — their job (explaining what the tool does) is already done by the point the form is in progress, so removing them here is a reasonable simplification, not a loss of real information.

This fix applies universally whenever the compacted state is active, regardless of screen width or zoom level, rather than patching one specific viewport size — the actual bug was about the compacted state itself, not a particular breakpoint.

## Verification

- CSS brace balance and depth-tracking check: 474/474, no unclosed or stray braces
- PHP `php -l` clean on all files
- `node --check` clean on app.js
- JSON valid, 250 airlines unchanged

No functional or data changes in this release — CSS only.
