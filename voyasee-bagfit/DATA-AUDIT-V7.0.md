# BagFit 7.0 Data Audit

## Airline coverage

| Tier | Profiles | Public behaviour |
|---|---:|---|
| Deep verified | 34 | Can return a strong pass when all booking fields are known and the rule remains fresh |
| Core source-linked | 125 | Uses stored numerical starter fields but converts a simple pass into a conditional result |
| Directory only | 91 | Official source link only; cannot return an unconditional numerical pass |
| Total | 250 | Global airline directory |

## Airport coverage

- 7,883 IATA-indexed airport records
- Search fields include IATA, ICAO, airport name, city, country, coordinates and timezone
- Airport data is a search convenience only and never creates an airline baggage rule
- Source package: airportsdata 20260315, MIT license

## Accuracy controls

- No universal checked-bag assumption
- Ticket-entered checked allowance
- Six rectangular bag orientations
- Piece-count and shared cabin-weight checks
- Operating carrier used where supplied
- Freshness and coverage tier affect public verdict strength
- Exact excess measurement calculated locally
- No automatic publication from source-monitor changes

## Known limitations

- Fare and route variants remain incomplete for many airlines
- Baggage fees are not calculated as exact live prices
- Directory-only profiles provide official confirmation rather than a numerical answer
- Camera-based measurement is not included
