# Implementation Plan — 4 Approaches + Benchmark

Step-by-step plan to implement the four candidate architectures (A–D) on
separate branches against separate databases, then benchmark them and pick a
winner. Companion to [INVESTIGATION.md](INVESTIGATION.md).

Every step lists a **Verify** check (per SKILL.md §4, goal-driven execution).
No code is written by this document — it is the plan only.

---

## Assumptions

Stated explicitly (SKILL.md §1). Adjust before starting if any are wrong.

1. **Shared layer built once.** Images, addresses (geocoding), attendees, and
   email/reminders are identical across all four approaches, so they are built
   once on a shared `bench/base` branch. Each `approach/*` branch differs only
   in the **listing query, pagination, serialization, and the two visual pages**
   — the parts that actually affect performance. (Full-parity ×4 is possible but
   adds ~4× work with no extra benchmark signal.)
2. **Visual styles held constant** across approaches: **Visual 1 = card grid**,
   **Visual 2 = interactive map**. Holding the UI constant isolates the data
   layer as the only benchmarked variable. (Approach C's map additionally shows
   aggregate clusters; noted in its section.)
3. **One engine (SQLite)** for all four, so the DB engine isn't a confounding
   variable. Approach C's "search index" is SQLite FTS5 / a denormalized table,
   not an external service.
4. **Fair data:** seed the base dataset once, then **copy the `.sqlite` file**
   per branch and run only that approach's migrations/backfills on the copy.
5. **Timezone:** `created_time` is treated as a UTC instant (it is the event
   start time). Times are formatted for display in the viewer's local timezone
   on the frontend; UTC is the storage/comparison basis on the backend.
6. **Dataset size** for iteration is TBD by the operator — develop against a
   smaller copy (e.g. 250k) for speed, publish final numbers at the full 1.25M.

---

## Conventions

- Branch from `main` → `bench/base` → one branch per approach.
- Each branch points at its own DB via `.env` (`DB_DATABASE=.../bench_x.sqlite`).
- Keep diffs surgical (SKILL.md §3): touch only the listing path + visuals per
  approach; shared code stays on `bench/base` and is merged forward.
- Update [tests/Feature/EventListingTest.php](tests/Feature/EventListingTest.php)
  deliberately whenever the `/events/data` response shape changes.

---

## Phase 0 — Prerequisites (on `main`)

1. **Create `.env`** from framework defaults: `DB_CONNECTION=sqlite`,
   `QUEUE_CONNECTION=database`, `MAIL_MAILER=log`, `APP_TIMEZONE=UTC`,
   `php artisan key:generate`.
   **Verify:** `php artisan about` runs; `php artisan migrate` succeeds.
2. **Seed the base dataset once** at the chosen size (`SEED_ROWS`), into
   `database/base.sqlite`.
   **Verify:** row count matches `SEED_ROWS`; file size is sane.
3. **Pin the seed RNG** (add `mt_srand(<fixed>)` at the top of
   `EventSeeder::run()`) so re-seeds are reproducible. *(Only change if a re-seed
   rather than file-copy is ever needed; file-copy is the primary path.)*
   **Verify:** two seeds of N rows produce identical first/last row ids.
4. **Commit** Phase 0, branch `bench/base`.
   **Verify:** `git branch` shows `bench/base`; tests still green.

---

## Phase 1 — Shared base (`bench/base`)

### 1.1 Images (end to end, local)
- Add placeholder images under `public/images/events/` (3–5 files, reused).
- Migration: add `images` JSON column to `events` (nullable).
- `Event` model: `images()` accessor returning 2+ local URLs chosen
  **deterministically by id** (e.g. hash → pick N from the pool), so no storage
  bloat and stable per event.
  **Verify:** `Event::first()->images` returns ≥2 local `/images/events/*` URLs;
  a browser request to one returns 200.

### 1.2 Addresses (offline reverse-geocode)
- Add `config/geo.php` (or `database/data/city_anchors.php`) mapping each of the
  ~70 `CITY_ANCHORS` to `{city, region, country}`.
- `App\Support\Geocoder::nearest(lat, lng)` → nearest anchor → address string.
- `Event` model: `address` / `city` / `country` accessors using the geocoder.
  **Verify:** unit test — a NYC-jittered coord resolves to "New York, USA".

### 1.3 Timezone display helper
- Frontend: a `formatEventTime(unixUtc, tz)` util (Intl.DateTimeFormat) used by
  both visuals; default tz = browser. Backend passes raw UTC unix + ISO string.
  **Verify:** a known timestamp renders correctly in two different `tz` values.

### 1.4 Attendees + confirmation email
- Migration: `attendees` table (`id`, `event_id` FK, `name`, `email`, `status`
  [interested|attending], unique `(event_id,email)`, timestamps).
- `Attendee` model + relation `Event::attendees()`.
- `StoreAttendeeRequest` (validate name/email; dedupe).
- `AttendeeController@store` route `POST /events/{event}/attendees`.
- `AttendanceConfirmed` queued Mailable; dispatched on store.
  **Verify:** feature test — POST creates a row, asserts mail queued to the
  address; duplicate POST is rejected.

### 1.5 Reminder emails (3 days + 24 hours, idempotent)
- Migration: `event_reminders` ledger (`event_id`, `attendee_id`, `kind`
  [3d|24h], `sent_at`, unique `(attendee_id,kind)`).
- `EventReminderMail` queued Mailable.
- `events:send-reminders` artisan command: for events whose `created_time`
  (UTC) falls into the 3-day or 24-hour window, for each attendee with no ledger
  row of that kind → queue mail, write ledger row.
- Schedule it hourly in `routes/console.php` (`Schedule::command(...)->hourly()`).
  **Verify:** feature test — an event 24h out queues exactly one 24h reminder per
  attendee; running the command twice does **not** double-send (ledger blocks it).

### 1.6 Benchmark harness (built once, reused by all approaches)
- `events:benchmark {approach}` artisan command that, against the current DB,
  measures over N runs and records:
  - p50 / p95 latency: first page, deep page (offset ~10k), date-filtered,
    location-filtered.
  - bytes per page (reuse existing `stats.bytes` shape).
  - DB size on disk, migration/index build time, peak memory.
- Writes/appends a row to `BENCHMARK.md` (and a machine-readable
  `storage/benchmark/<approach>.json`).
  **Verify:** running it on `bench/base` produces a complete metrics row.

### 1.7 Shared frontend shells
- `Events/VisualOne.vue` (card-grid shell) and `Events/VisualTwo.vue` (map shell)
  with filter bar (date range + location/city) — wired to a thin data source
  that each approach overrides. Tailwind styling + light animations
  (card hover, list enter/leave transitions).
  **Verify:** both pages render with placeholder data; lint + `vue-tsc` pass.

**Commit `bench/base`.** Tests green. This is the fork point for A–D.

---

## Phase 2 — Approach A: Pragmatic server-driven

Branch `approach/a-server-driven` from `bench/base`; DB `bench_a.sqlite`.

1. Migration: composite index `(created_time, id)`; backfill denormalized
   `city` + `country` columns (from the geocoder) with an index on `city`.
   **Verify:** `EXPLAIN QUERY PLAN` uses the index for ordered + city-filtered
   reads.
2. Listing query: **keyset/cursor pagination** on `(created_time, id)`; filter by
   date range (`created_time BETWEEN`) and by `city`. No `COUNT(*)` on the hot
   path (approximate or omit exact total).
   **Verify:** deep-page latency is flat vs. page depth (unlike offset).
3. `EventListResource` — slim payload (id, name, description, type, status,
   starts_at, lat/lng, city/country, image thumbs). **No `payload` blob.**
   **Verify:** bytes/page drops sharply vs. baseline.
4. Wire Visual 1 (grid) + Visual 2 (Leaflet map, marker clustering, bbox filter)
   to the new endpoint.
   **Verify:** both filter by date and location; map clusters render.
5. Update `EventListingTest` for the new shape; run `events:benchmark a`.
   **Verify:** tests green; benchmark row written.

---

## Phase 3 — Approach B: Inertia-native, minimal JS

Branch `approach/b-inertia-native` from `bench/base`; DB `bench_b.sqlite`.

1. Same `(created_time, id)` index as A (no denormalized columns).
   **Verify:** index used for ordered reads.
2. Replace the hand-rolled `fetch('/events/data')` with **Inertia partial
   reloads / `WhenVisible` / merge props**; filters live in the URL query string
   (eliminates the `aplyFilters` bug class). Controller returns paginated Inertia
   props directly to the visual pages.
   **Verify:** filtering updates the URL and reloads only the events prop; back
   button restores state.
3. Geocoding on read via the offline anchor lookup (no extra columns); slim props
   via an Inertia resource transform.
   **Verify:** address shown; no `payload` blob in props.
4. Visual 1 = grid; **Visual 2 = calendar/agenda** grouped by day, with the month
   window as the natural date filter.
   **Verify:** calendar paginates by month; location filter works.
5. Update tests (this branch may drop/replace `/events/data`); run
   `events:benchmark b`.
   **Verify:** tests green; benchmark row written.

---

## Phase 4 — Approach C: Read-model / search-first

Branch `approach/c-read-model` from `bench/base`; DB `bench_c.sqlite`.

1. Build a denormalized read model: either a `event_search` table (slim columns +
   `city`, `month` buckets, composite indexes) or SQLite **FTS5**, populated from
   `events`/`payload` via a backfill command.
   **Verify:** read-model row count matches base; build time recorded.
2. Pre-aggregate facet counts (events per city, per month) into a small
   `event_facets` table so the UI shows filter tallies without `COUNT(*)` on the
   base table.
   **Verify:** facet endpoint returns counts in O(facets), not O(rows).
3. Listing + filtering hit only the read model (keyset pagination). `payload`
   loaded only on the Show page.
   **Verify:** hot-path queries never touch the 1.25M base table.
4. Visual 1 = grid; **Visual 2 = aggregate map** (per-city cluster counts from
   facets, not 1.25M markers); drill-down loads that city's events.
   **Verify:** map renders from aggregates; drill-down filters correctly.
5. Update tests; run `events:benchmark c`.
   **Verify:** tests green; benchmark row written.

---

## Phase 5 — Approach D: Thin vertical slice (MVP)

Branch `approach/d-thin-slice` from `bench/base`; DB `bench_d.sqlite`.

1. Migration: **only** the `created_time` index. No new columns, no read model.
   **Verify:** index used for ordered reads.
2. Listing query: add a `->select()` of just the needed columns (slim, no
   `payload`); scope the default window to upcoming + recent
   (`created_time BETWEEN now-30d AND now+1y`) to keep `COUNT(*)`/offset cheap.
   **Verify:** first-page + filtered latency acceptable within the window.
3. Geocoding via the shared offline static map (no table, no external calls).
   **Verify:** address shown; zero external requests.
4. Visual 1 = grid; **Visual 2 = Leaflet map** (simple markers within the
   window). Date-range + city-dropdown filters.
   **Verify:** both filters work; markers render.
5. Update tests; run `events:benchmark d`.
   **Verify:** tests green; benchmark row written.

---

## Phase 6 — Benchmark & decision

1. With each branch checked out against its own DB copy, run
   `events:benchmark <x>` for a, b, c, d (same N runs, same machine, same
   dataset size for the published run).
   **Verify:** four metrics rows + four JSON files exist.
2. Compile `BENCHMARK.md`: a comparison table (latency p50/p95 per query type,
   bytes/page, DB size, build time, memory) + notes on correctness/complexity.
   **Verify:** table is complete; numbers reproducible on a second run.
3. **Recommendation** section: pick the best approach against the test's
   "quality over quantity / focused" brief, weighing performance vs. complexity
   (SKILL.md §2). Note when each approach would be the right call.
   **Verify:** decision is justified by the recorded metrics, not assertion.

---

## Deliverables checklist

- [ ] `bench/base` with shared images, addresses, attendees, emails, reminders,
      benchmark harness, visual shells.
- [ ] `approach/a-server-driven` + `bench_a.sqlite` + benchmark row.
- [ ] `approach/b-inertia-native` + `bench_b.sqlite` + benchmark row.
- [ ] `approach/c-read-model` + `bench_c.sqlite` + benchmark row.
- [ ] `approach/d-thin-slice` + `bench_d.sqlite` + benchmark row.
- [ ] `BENCHMARK.md` comparison + recommendation.
- [ ] All bugs from [INVESTIGATION.md §2](INVESTIGATION.md) fixed on every branch.
- [ ] Tests green on every branch.
