# Voyasee BagFit 7.0.1

## Public security-session hotfix

Version 7.0.1 fixes the front-end error:

> The security token expired. Refresh the page and try again.

### Root cause

Version 7.0.0 embedded a custom WordPress nonce while the page was rendered. On logged-in preview pages and on cached pages, WordPress REST authentication could treat the later public request as an unauthenticated visitor, causing the embedded user-specific nonce to fail immediately.

### Changes

- Removed the user-specific public nonce from cached shortcode HTML.
- Added a cache-resistant public nonce renewal endpoint.
- The browser requests a fresh public token directly from WordPress REST.
- Protected requests renew the token automatically before submission.
- A failed token is refreshed and the original request is retried once automatically.
- Added `no-store` response headers and a cache-busting query value.
- Kept existing request validation and IP-based rate limits.
- Bumped asset versions so Hostinger/CDN/browser caches load the corrected JavaScript.

No airline, airport, result-engine, footer, affiliate, or user-interface data was removed.
