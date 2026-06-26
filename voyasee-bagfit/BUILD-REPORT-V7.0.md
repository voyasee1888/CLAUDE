# Voyasee BagFit 7.0 Technical Build Report

## Architecture

- WordPress plugin, PHP 8.1+
- Shortcode-driven public interface
- Dedicated airline, saved-result, source-monitor and anonymous-event tables
- REST routes for airlines, airport search, deterministic checking, reverse search, shared size, ticket parsing and saved reports
- Vanilla JavaScript and scoped CSS
- No external JavaScript framework
- No AI or paid baggage API

## Public modes

- Quick Check
- Full Trip
- Reverse Bag Search
- Best Shared Size

## Data protection

- No account required
- No PNR, ticket number, name or passport requested
- Saved links use random tokens and expiry
- Anonymous analytics are aggregated by date and result category

## Upgrade safety

- Existing administrator-customised airline records are preserved
- Non-customised bundled records upgrade to the Version 7 schema
- Missing default footer and affiliate links are restored without overwriting non-empty custom values
