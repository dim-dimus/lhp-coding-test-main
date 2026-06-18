# CLAUDE.md — Event Visuals (Approach A)

Laravel 13 + Inertia + Vue 3 + Tailwind v4. Two event-browsing visuals over a
large (planted 1.25M-row) MySQL dataset. The chosen solution is **Approach A**.

- Brief: [CODING_TEST.md](CODING_TEST.md)
- Original code review + bug list: [INVESTIGATION.md](INVESTIGATION.md)
- Local setup / run: [README.md](README.md)
- Development methodology (must follow): [SKILL.md](SKILL.md)

> Only Approach A is in scope. The other variants (B/C/D) and the multi-approach
> benchmark are dropped. Work happens on the `final-approach` branch.

---

## Project structure

```
app/
  Models/
    Event.php              # events: payload, lat/lng, created_time (event start);
                           #   accessors: images, address, city; relations: user, attendees
    Attendee.php           # registrations (event_id, name, email, status)
    EventReminder.php      # idempotency ledger (attendee_id, kind: 3d|24h, sent_at)
    User.php
  Http/
    Controllers/
      EventController.php   # index, visualOne, visualTwo, data (listing JSON), show
      AttendeeController.php# store (register interest) -> JSON for fetch, redirect otherwise
    Requests/StoreAttendeeRequest.php
  Mail/
    AttendanceConfirmed.php # queued confirmation on registration
    EventReminder.php       # queued 3-day / 24-hour reminder
  Console/Commands/
    SendEventReminders.php  # events:send-reminders (hourly, idempotent)
  Support/Geocoder.php      # OFFLINE reverse geocode: lat/lng -> city/country/address
config/geo.php              # ~75 labelled city anchors (matches EventSeeder)
database/
  migrations/               # events, attendees, event_reminders (+ Approach A index/columns)
  seeders/EventSeeder.php    # bulk seed; SEED_ROWS controls size
routes/
  web.php                   # events.* routes + POST events/{event}/attendees
  console.php               # Schedule: events:send-reminders hourly
resources/js/
  pages/Events/
    VisualOne.vue           # Visual 1 — card grid (filter: date range + location + status)
    VisualTwo.vue           # Visual 2 — agenda timeline (month nav + location/status)
    Index.vue               # reference table (legacy); Show.vue — detail
  components/EventRegisterDialog.vue  # shared registration dialog (fetch + CSRF + toast)
  lib/datetime.ts           # Intl-based UTC -> viewer-local formatting
public/images/events/       # local placeholder SVGs (served locally)
tests/Feature/              # EventListingTest, AttendeeRegistrationTest, EventReminderTest
```

Already built (shared base merged into this branch): offline geocoding, image
accessor, attendees + confirmation/reminder emails, Visual 1 + Visual 2.

---

## Approach A — what we are building

Approach A ("pragmatic server-driven") fixes the planted performance traps at the
data layer, completely and without a parallel search system.

### 1. Breakdown of A

Four pillars:

- **Composite index `(created_time, id)`** — makes the chronological sort indexed
  (no full-table sort) and gives keyset pagination a stable tiebreaker.
- **Keyset / cursor pagination** — fetch "the next N rows after this
  `(created_time, id)`" instead of `LIMIT/OFFSET`. No `COUNT(*)` on the hot path;
  deep pages cost the same as the first page.
- **Slim API resource** — the listing returns only what a card/agenda row needs
  (id, name, description, type, status, start time, city/country, images, price);
  the heavy `payload` blob never goes over the wire.
- **Denormalized `city` / `country` columns (indexed)** — location filtering
  becomes an indexed `WHERE city = ?` instead of an unindexed lat/lng bounding-box
  scan. Backfilled once from the offline `Geocoder`.

### 2. Why A

1. **Cures exactly what the 1.25M dataset was planted to expose** — `COUNT(*)`,
   the unindexed sort, payload over-fetch, and the unindexed location filter.
2. **No over-engineering (unlike C)** — denormalized `city`/`country` is one
   simple, low-risk addition (a backfill + an index), not a second data model
   with its own sync/consistency burden.
3. **More robust than D** — D only *contains* the problem by narrowing the date
   window; A *eliminates* it, so it doesn't degrade on wider windows or deeper
   browsing.
4. **B's ideas fold in for free** — URL-based filters and Inertia partial reloads
   can be added on top without changing the data model.

### 3. Main aspects of A (from the comparison table)

| Aspect | A's choice |
|---|---|
| Pagination | **Keyset / cursor** (no `COUNT(*)`) |
| Sorting | Index **`(created_time, id)`** |
| Payload | **Slim resource** (no blob on the wire) |
| Location filter | **Denormalized `city` + index** (equality, not bbox) |
| Schema change | Index + `city`/`country` columns + one-time backfill |
| Scales to 1.25M | Yes, fully |
| Complexity / risk | Medium |

---

## Build plan (step → verify)

**Phase 0 — Prep.** Branch `final-approach`; capture baseline `/events/data`
metrics (latency first vs deep page, bytes/page) to quantify A's gain later.

**Phase 1 — Schema.** Migrations: index `(created_time, id)`; `city`/`country`
columns + index on `city`; chunked backfill command from `Geocoder`.
→ verify: `EXPLAIN` uses the indexes; all rows have `city`/`country`.

**Phase 2 — Backend.** `EventListResource` (slim, no payload); rewrite
`loadListing` to keyset (no `COUNT`); response `{ data, next_cursor, has_more,
stats }`; location filter via indexed `city`.
→ verify: deep-page latency ≈ first-page; bytes/page down; `EXPLAIN` clean.

**Phase 3 — Frontend.** Update TS interfaces to the flat shape; cursor-based
loading in VisualOne (infinite scroll) and VisualTwo (month window).
→ verify: `vue-tsc` clean; no duplicates/gaps while scrolling.

**Phase 4 — Tests.** Update `EventListingTest` to the keyset/slim shape; add a
no-duplicates/no-gaps cursor test and a "no payload on the wire" test.
→ verify: green.

**Phase 5 — Perf check.** Re-measure vs the Phase 0 baseline.
→ verify: improvement across all four traps.

**Phase 6 — Quality.** Pint, PHPStan, vue-tsc, ESLint, full `php artisan test`;
update README. → verify: all gates green; then stop for commit approval.

---

## Conventions

- **Never commit or push without explicit approval.** Stage, show the diff, ask.
- `created_time` is a **UTC unix timestamp = event start**; display in the
  viewer's local timezone (`lib/datetime.ts`); compare in UTC on the backend.
- Tests run on in-memory SQLite (`phpunit.xml`); the app runs on MySQL 8.
- Reminders are **idempotent** (ledger + unique constraint); the hourly cadence
  only affects timeliness, never double-sends.
- Match existing code style (4-space indent, single quotes in JS/TS); ESLint is
  the enforced gate (`npm run lint:check`).
