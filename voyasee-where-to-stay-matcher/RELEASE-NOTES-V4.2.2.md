# Voyasee Where to Stay Matcher 4.2.2 — rebrand to "Best Area to Stay Finder"

## What was requested

A screenshot of this page's SEO setup (SEO Title, H1 Page Header, and focus
keyword "best area to stay" in another Claude conversation) with the instruction
to bring the plugin's own Hero title in line with that keyword, and remove
"Where to Stay Matcher" from the Hero and everywhere else it appears.

## What shipped

Every visible mention of "Where to Stay Matcher" / "Voyasee Where to Stay" was
replaced with "Voyasee Best Area to Stay Finder" (or "Best Area to Stay Finder"
where "Voyasee" was already implied by context):

- **Plugin Name** header (shows in the WP admin Plugins list).
- **Front-end hero title** (`templates/quiz-container.php`) -- was
  `Voyasee <em>Where to Stay</em> Matcher`, now
  `Voyasee <em>Best Area to Stay</em> Finder`, mirroring the exact emphasis
  pattern of the original so the styling/animation around the `<em>` tag
  still applies correctly.
- **Admin menu label** (top-level page title + sidebar menu title).
- **Settings page heading** ("Best Area to Stay Finder -- Settings & Footer
  Links").
- **"Suggest a correction" email** subject line and body text (what actually
  lands in the site admin's inbox).
- **Downloadable match-card image's watermark text** ("Matched with Voyasee
  Best Area to Stay Finder -- voyasee.com").
- **Current setup instructions** in the readme (the numbered steps telling a
  site owner where to click in wp-admin) -- these were updated because they're
  live navigational instructions that should match today's actual menu label,
  not a historical record.
- Assorted code comments (file headers in `matcher.js`, `matcher.css`, and a
  couple of PHP includes) for consistency, since a future contributor reading
  those shouldn't see the old name either.

## What was deliberately NOT changed

- **Every internal code identifier** -- class names (`WTSM_*`, `VNI_*`),
  function names, PHP constants (`WTSM_VERSION`, `WTSM_PLUGIN_DIR`, etc.), the
  database table name constants, the plugin's folder name, and its text domain
  (`voyasee-wtsm`). None of these are visible to a visitor or, really, to the
  site owner day-to-day, and renaming them would be pure risk (broken
  references, a botched find-and-replace somewhere) for zero visible benefit.
  WordPress identifies an *active* plugin by its folder + main file path, not
  its `Plugin Name:` header text, so this rename installs cleanly in place --
  no deactivate/reactivate dance needed, no settings lost.
- **Old changelog entries** describing past releases -- those are a historical
  record of what shipped and when, not current instructions, so they were left
  exactly as originally written.
- **One specific readme line** (the 2.0.0 migration note telling users to
  delete the old *literal* 1.x plugin named "Voyasee Where to Stay Matcher")
  -- that's identifying a real plugin by its actual old name for a deactivate/
  delete step, not a branding mention, so changing it would make that
  instruction wrong, not more current.

## Verification

- `php -l` clean on every PHP file (full sweep), `node --check` clean on
  `matcher.js`, CSS brace balance verified (349/349).
- Grepped the entire codebase after the edits to confirm zero remaining
  "Where to Stay Matcher" / "Voyasee Where to Stay" mentions outside the one
  intentional historical reference described above.
- No live WordPress install was available in this environment to visually
  confirm the rendered hero title; the `<em>` emphasis markup was kept in the
  same position/structure as the original so the existing CSS styling for
  that heading should apply unchanged.
