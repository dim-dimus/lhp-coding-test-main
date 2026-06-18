# Investigation — Event Visuals

> Senior engineering review of the coding-test codebase and the bugs / incorrect
> approaches found in the original code. The chosen solution is **Approach A**;
> its design and the step-by-step plan live in [CLAUDE.md](CLAUDE.md).

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

**Frontend** — `Events/VisualOne.vue` and `Events/VisualTwo.vue` started as empty
`<h1>` placeholders — they are the actual deliverable. A separate
`Events/Index.vue` table exists as a reference listing.

**Runtime** — this build targets **MySQL 8 / InnoDB** (see [README.md](README.md)),
queue `database`, mail `log`, app timezone `UTC`.

---

## 2. Bugs & incorrect approaches in the original code

### Broken / spec-violating

1. **Dead filter button** — `Index.vue` bound `@click.prevent="aplyFilters"`
   (typo); the real method is `applyFilters`. The Filter button did nothing.
2. **`from` date filter non-functional end-to-end** — the frontend sent a `from`
   param but `loadListing()` only applied `status`. Date filtering — required by
   the spec — was wired to nothing.
3. **No location filter at all** — required by the spec, entirely absent.
4. **Raw unix timestamp shown to users** — `Index.vue` printed `created_time` as
   a bare integer. No date formatting, no timezone (both required).

### Performance traps (the real test)

5. **`paginate()` on 1.25M rows** runs a `COUNT(*)` across the whole table on
   **every** request, and offset pagination degrades on deep pages.
6. **Unindexed sort** — `orderByDesc('created_time')` sorts 1.25M rows with no
   supporting index (only `status` is indexed).
7. **Payload over-fetching** — the listing ships the **full `payload`** (incl.
   lorem padding) even though only a few fields are rendered. The `stats.bytes`
   widget measures exactly this waste.
8. **Unindexed location data** — `latitude`/`longitude` are not indexed, so any
   geographic filter is a full scan.

### Modeling / correctness

9. **`created_time` is misnamed** — both the factory and seeder set it to the
   event **start time** (±1 year), mirrored in `payload.schedule.starts_at`. Any
   date filter/sort must treat it as the event instant, not an audit field.
10. **Duplicated geo source of truth** — lat/lng live both as decimal columns and
    as strings inside `payload.location`.
11. **`protected $guarded = []`** on `Event` — fully open mass assignment.
12. **`Show.vue` dumps raw JSON** — not user-facing.

### Test constraint to respect

[`EventListingTest`](tests/Feature/EventListingTest.php) pins the `/events/data`
JSON shape. Approach A switches it to keyset pagination + a slim resource, so
those tests are updated **deliberately**, not broken by accident.

---

## 3. Decision

**Approach A (pragmatic server-driven)** was chosen: composite
`(created_time, id)` index + keyset/cursor pagination (no `COUNT(*)` on the hot
path) + a slim resource (no `payload` blob on the wire) + denormalized
`city`/`country` columns (indexed location filter, backfilled from the offline
geocoder). Rationale, design, and the step-by-step build plan are in
[CLAUDE.md](CLAUDE.md). Development follows [SKILL.md](SKILL.md).
