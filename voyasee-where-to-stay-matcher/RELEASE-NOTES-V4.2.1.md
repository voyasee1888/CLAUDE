# Voyasee Where to Stay Matcher 4.2.1 — verified against real sibling-plugin source, not guesses

## What was requested

An independent third-party audit of v4.2.0 (provided as a document) plus live screenshots
of the site after installing v4.2.0. The audit's overall verdict was positive ("ship
it"), but flagged that the three new external-data facts (air quality, Practical Facts
panel, currency) were built by guessing at Weather Bridge/Country Intelligence's response
shape and recommended spot-checking them live. The screenshots then showed exactly that:
the Air Quality fact never appeared anywhere, and the new "Compare with another
destination" field (added in 4.2.0) had the same invisible-text-while-typing bug fixed
for Step 1 in 4.1.2. The explicit ask: check the whole thing end-to-end, fix anything
actually wrong, and don't just guess again.

## The key difference this time: real source, not guesses

Both sibling plugins' actual source (Voyasee Weather Bridge v1.3.1, Voyasee Country
Intelligence v2.0.0) were available in this session's uploaded files. Rather than
re-guessing field names a third time, every integration point this plugin calls into
either sibling was checked line-by-line against the real `VWB_Service`/`VWB_Normalizer`
and `VCI_Data` source and, where relevant, a real compiled country JSON file (Italy's).
This turned "spot-check the facts on a live page" into "confirm each field path is
correct against ground truth," which is a stronger form of verification than clicking
around a browser could have given on its own.

## What was found and fixed

1. **The compare-destination field's invisible text (confirmed bug).** The field added
   in 4.2.0 has no unique styling exemption of its own -- it was simply never added to
   the ID-anchored `:focus` rule created in 4.1.2, because it didn't exist yet when that
   rule was written. Same root cause, different field. Fixed by giving it a stable `id`
   and adding it to that rule. Also pre-emptively hardened the "Suggest a correction"
   form's two fields (message textarea, email) the same way, and added an explicit
   comment on the CSS rule stating the convention going forward: every new text field
   this plugin renders must get a stable `#id` in this list, since this is now the
   second time skipping that step has caused this exact bug.
2. **Autocomplete dropdowns not closing on outside click (confirmed bug).** The
   "close when clicking elsewhere" handler was hardcoded to the Step 1 destination
   field's specific ID and results box. The new compare-destination dropdown stayed
   open after clicking away. Generalized to close any open `.vwtsm-autocomplete-results`
   on the page, so a future third autocomplete field won't miss this either. Also added
   the missing `focus`-reopens-suggestions listener to the compare-destination field to
   match Step 1's behavior.
3. **Air Quality never appeared (confirmed bug, root cause found).** The function name
   and signature (`voyasee_weather_get_air_quality($lat, $lon)`) were correct. The
   response-shape guess was not: the real normalized object uses `usAqiEstimate`,
   `usAqiCategory`, `owmIndex`, `owmCategory` -- none of which were among the guessed
   `aqi`/`index`/`value`/`category`/`level`/`label` keys, so the fact silently omitted
   itself every single time, exactly matching what the screenshots showed. Rewritten
   against the confirmed real shape: prefers the US AQI estimate (0-500 scale, most
   globally recognized) with Weather Bridge's own EPA-breakpoint category label, falling
   back to OpenWeather's native 1-5 index + category when no US AQI could be derived.
   Both scales are labeled distinctly in the UI so a "42" and a "2" are never ambiguous
   about which scale they're on.
4. **Three "Good to know" facts were silently never appearing (confirmed bugs).**
   Checked all seven `pick()` calls in `buildPracticalFactsPanel()` against a real
   compiled country JSON. Four were already correct (plug type, tipping, emergency
   numbers, currency -- matching what the screenshots showed working). Three were not:
   - The country name in the panel heading used `core.name.common`; the real field is
     `core.names.common` (plural).
   - The "Drives on the ___" fact used `core.drivingSide`/`travel.drivingSide`; the real
     field is nested one level deeper, at `core.transport.drivingSide`.
   - The electrical voltage used `travel.electrical.voltage`; the real field name is
     `nominalVoltage`. (Plug type itself was already correct, which is why it showed in
     the screenshots while voltage silently didn't.)
   All three fixed with the confirmed real path checked first, old guesses kept as
   harmless fallbacks.

## What was checked and confirmed already correct (no change)

- `voyasee_ni_maybe_get_weather()`'s forecast and climate-normals paths -- every field
  name (`date`, `tempMinC`, `tempMaxC`, `condition.text`, `climateNormals.months`,
  `temperatureMeanC`, `estimatedRainDays`, etc.) matches Weather Bridge's real
  normalizer output exactly.
- The currency extraction's "array of `{code, symbol}` objects" branch -- confirmed
  against a real compiled country JSON, this is genuinely the shape Country
  Intelligence returns; the code's other defensive branch (object keyed by code) was
  unnecessary but harmless.
- `voyasee_ni_maybe_get_holiday_overlap()` -- `holidays.holidays[].date` /
  `.name` match the real compiled holiday JSON exactly.
- The weight-sum invariant and matching engine -- untouched this release; nothing here
  affects scoring.

## Verification

- `php -l` clean on every PHP file (full sweep), `node --check` clean on `matcher.js`,
  CSS brace balance verified (349/349).
- Every fix in this release was checked against actual sibling-plugin source code and/or
  a real compiled data file, not re-guessed -- the opposite of how the original bugs were
  introduced.
- No live WordPress install was available in this environment to visually re-confirm
  the rendered fixes; recommend a spot-check of the Air Quality chip, the three
  corrected "Good to know" facts, and the compare-destination field's typing behavior
  after updating.
