# BagFit Source and Maintenance Policy

## Airline rules

Every published airline profile must contain an official airline source URL. The database stores independently structured facts such as dimensions, weight, pieces, inclusion and route or fare conditions. It does not republish complete airline policy pages.

Coverage tiers:

- `deep_verified`: manually reviewed against an official page
- `core_source_linked`: official page linked with starter numerical fields requiring a fresh review
- `directory`: official site or baggage page only, without a confident numerical rule

The source monitor can flag page availability or content-hash changes. It never edits or publishes a rule automatically.

## Airport index

The airport search index was generated from the `airportsdata` Python package version 20260315 under the MIT license. The required notice is included at `data/licenses/AIRPORTSDATA-LICENSE.txt`.

Airport metadata is used only for search convenience. It is never used to infer baggage policy.

## Checked baggage

Checked baggage varies by ticket, route, fare and carrier. The plugin asks the traveller to enter the allowance shown in the issued booking rather than applying a generic limit.

## Fees

The plugin does not invent exact baggage fees. Live airline booking or manage-booking pages remain the appropriate source for a current price.
