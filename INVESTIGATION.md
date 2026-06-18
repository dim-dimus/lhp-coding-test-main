# Investigation & Implementation Plan — Event Visuals

> Senior engineering review of the coding-test codebase, the bugs/incorrect
> approaches found in the current code, and four candidate implementation
> strategies with trade-offs and a recommendation.

---

## 1. What this codebase is

A **Laravel 13 + Inertia + Vue 3 + Tailwind v4** starter kit (shadcn-vue
components, Laravel Fortify auth, Pest tests). The event domain is intentionally
minimal and seeded large.

**Schema** — `events` table
([migration](database/migrations/2024_02_01_000000_create_events_table.php)):

| Column | Notes |
|--------|-------|
| `id` | UUID primary key |
| `user_id` | FK → `users`, cascade delete |
| `type` | concert / conference / meetup / … |
| `status` | draft / published / cancelled / sold_out |
| `created_time` | **unix int — actually the event START time, not an audit field** |
| `latitude` / `longitude` | decimal(10,7), nullable |
| `payload` | longText JSON, ~1.5 KB each (padded with lorem `notes`) |
| timestamps | |

Only **`status` is indexed**. `created_time` is **not** indexed.

**Seeder** — [`EventSeeder`](database/seeders/EventSeeder.php) defaults to
**1,250,000 events** (`SEED_ROWS`) spread across ~70 jittered city anchors
(US / Canada / Mexico / Europe + global hubs) and 3,000 users. This large
dataset is a **deliberately planted performance problem**, not an accident.

**Frontend** — `Events/VisualOne.vue` and `Events/VisualTwo.vue` are currently
empty `<h1>` placeholders — they are the actual deliverable. A separate
`Events/Index.vue` table exists as a reference listing.

**Runtime defaults** (no `.env` committed yet):

- DB: `sqlite`
- Queue: `database` (jobs table migration already present)
- Mail: `log`
- App timezone: `UTC`

---

## 2. Bugs & incorrect approaches in the current code

### Broken / spec-violating

1. **Dead filter button** — [`Index.vue:148`](resources/js/pages/Events/Index.vue:148)
   binds `@click.prevent="aplyFilters"` (typo). The real method is
   `applyFilters`. The Filter button silently does nothing.

2. **`from` date filter is non-functional end-to-end** — the frontend sends a
   `from` param ([`Index.vue:54`](resources/js/pages/Events/Index.vue:54)) but
   [`loadListing()`](app/Http/Controllers/EventController.php:54) only applies
   `status`. Date filtering — explicitly required by the spec — is wired to
   nothing on the backend.

3. **No location filter at all** — required by the spec, entirely absent.

4. **Raw unix timestamp shown to users** —
   [`Index.vue:171`](resources/js/pages/Events/Index.vue:171) prints
   `created_time` as a bare integer. No date formatting, no timezone (both
   required).

### Performance traps (the real test)

5. **`paginate(50)` on 1.25M rows**
   ([`EventController.php:57`](app/Http/Controllers/EventController.php:57)) runs
   a `COUNT(*)` across the whole table on **every** request, and offset
   pagination degrades badly on deep pages.

6. **Unindexed sort** — `orderByDesc('created_time')`
   ([`EventController.php:56`](app/Http/Controllers/EventController.php:56))
   sorts 1.25M rows with no supporting index (only `status` is indexed). This is
   exactly what the `stats.ms` widget is designed to expose.

7. **Payload over-fetching** — `$events->items()` ships the **full `payload`**
   (including lorem `notes` padding) to the client even though the list only
   renders type/status/user/time. The `stats.bytes` widget measures precisely
   this waste. Needs an API Resource / explicit `select()`.

### Modeling / correctness

8. **`created_time` is misnamed** — both
   [factory](database/factories/EventFactory.php:21) and seeder set it to the
   event **start time**, spanning roughly one year in the past to one year out,
   mirrored in `payload.schedule.starts_at`. Any date filter/sort must treat it
   as the event instant, not a creation audit field — easy to get wrong.

9. **Duplicated geo source of truth** — lat/lng live both as decimal columns
   **and** as strings inside `payload.location`.

10. **`protected $guarded = []`** ([`Event.php:15`](app/Models/Event.php:15)) —
    fully open mass assignment; a footgun once attendee/registration writes
    exist.

11. **`Show.vue` dumps raw JSON** — not user-facing.

### Test constraint to respect

[`EventListingTest`](tests/Feature/EventListingTest.php) pins the `/events/data`
JSON shape — `total`, `last_page`, `current_page`, and that `created_time` /
`latitude` come back raw. Switching to cursor/keyset pagination changes
`last_page` / `total` semantics, so those tests must be updated **deliberately**,
not broken by accident.

### Entirely missing (second half of the spec)

- Images (schema + local serving)
- Reverse-geocoded human-readable addresses
- Timezone-aware date/time display
- Attendees / registration
- Confirmation email on registration
- 3-day and 24-hour reminder emails (no scheduler entry exists in
  [`console.php`](routes/console.php) or bootstrap)

---

## 3. Four implementation approaches

| # | Approach | Data / perf strategy | Visual 1 / Visual 2 | Geocoding | Best when |
|---|----------|----------------------|---------------------|-----------|-----------|
| **A** | **Pragmatic server-driven** *(recommended)* | Add `(created_time, id)` index; keyset pagination; slim `EventResource` (no payload); date-range + city/radius filters server-side; backfill denormalized `city` / `country` columns | Card grid / **Leaflet map** with clustering | Offline: reverse-geocode the ~70 anchors once → nearest-anchor lookup, stored on the row | Balanced correctness + performance; the default |
| **B** | **Inertia-native, minimal JS** | Drop the hand-rolled fetch; use Inertia partial reloads / `WhenVisible` / merge props; filters live in the URL (kills the `aplyFilters` bug class) | Card grid / **calendar-agenda** (month window is a natural filter) | On-demand + cache table keyed by rounded lat/lng, with anchor fallback | Cleanest Vue idiom, less custom state — but must update the `/events/data` tests |
| **C** | **Read-model / search-first (perf-max)** | Denormalized `event_search` table (or SQLite FTS5) with composite indexes + pre-aggregated facet counts; base `payload` only loaded on Show; map shows per-city aggregates, not 1.25M markers | Grid / aggregate map | Precomputed per anchor (only ~70 distinct after jitter) → free & exact | Showcases scale thinking; more upfront work, risks over-engineering the timebox |
| **D** | **Thin vertical slice (time-boxed MVP)** | Just add the `created_time` index + `select()` slim columns; scope the UI to a sensible window (recent + upcoming); no large schema migration | Card grid / Leaflet map | Static offline anchor map shipped as JSON/PHP — zero external calls, deterministic | Fastest to a clean, spec-complete, demoable result — matches "quality over quantity, keep it focused" |

### Common to all approaches

- **Images** — `events.images` (JSON column) with 2–3 bundled placeholders in
  `public/images/events`, picked deterministically by event id, served locally.
- **Attendees** — `attendees` table + Form Request validation.
- **Email** — queued `AttendanceConfirmed` mail on registration.
- **Reminders** — an `event_reminders` ledger + a scheduled
  `events:send-reminders` command (run hourly) that finds events entering the
  3-day and 24-hour windows, dispatches queued mail, and marks them sent for
  **idempotency** — avoiding missed or double sends, the subtle correctness risk
  in "global" events.

### Initial recommendation (single-approach)

For a single build, **Approach A** folding in **D's offline anchor-geocoding**
(no external API dependency, deterministic, works in CI). It fixes every bug
above, addresses the planted performance trap honestly (index + keyset
pagination + slim resource), keeps the two visuals genuinely distinct, and stays
within the "quality over quantity" brief.

---

## 4. Decision

Rather than pick one approach on reasoning alone, the agreed direction is to
**implement all four approaches and decide empirically**:

- Each approach lives on its own branch against its own SQLite database copy.
- A shared `bench/base` branch holds the layer common to all four (images,
  addresses, attendees, email/reminders, the benchmark harness, visual shells).
- A repeatable benchmark harness records latency (p50/p95), bytes per page, DB
  size, build time, and memory per approach.
- Results are compiled into `BENCHMARK.md` with a justified, metrics-backed
  recommendation.

The full step-by-step plan lives in
[IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md).

## 5. Development methodology

All development on this project follows [SKILL.md](SKILL.md): think before coding
(state assumptions, surface trade-offs), simplicity first, surgical changes, and
goal-driven execution (each step has a verify check). This is why the plan
builds the shared layer once instead of duplicating it across four branches, and
why every step in the plan carries an explicit **Verify**.
