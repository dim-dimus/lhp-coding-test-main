# Event Visuals

Laravel 13 + Inertia + Vue 3 + Tailwind v4 coding test. See
[CODING_TEST.md](CODING_TEST.md) for the brief,
[INVESTIGATION.md](INVESTIGATION.md) for the original code review and bug list,
and [CLAUDE.md](CLAUDE.md) for the chosen solution (**Approach A**) and its plan.

Development on this project follows [SKILL.md](SKILL.md).

---

## Requirements

- PHP 8.3+ (tested on 8.4) and Composer
- Node 20+ and npm
- Docker (for the MySQL 8 database)

## Local setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Start MySQL 8 (host port 3307 — see note below)
docker run --name lhp-mysql \
  -e MYSQL_ROOT_PASSWORD=secret \
  -e MYSQL_DATABASE=lhp_events \
  -e MYSQL_USER=lhp \
  -e MYSQL_PASSWORD=secret \
  -p 3307:3306 -d mysql:8.0 \
  --default-authentication-plugin=mysql_native_password

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Database — migrate, then seed events (count = SEED_ROWS in .env, default 500).
#    `db:seed` also backfills events.city/country for the location filter.
php artisan migrate
php artisan db:seed
# (if you seed only EventSeeder, run the backfill yourself:)
# php artisan events:backfill-locations

# 5. Run everything (server + queue worker + Vite + log tail)
composer dev
```

Open **http://localhost:8000** — `/` redirects to `/events`.

> **Port 3307:** the container listens on 3306 internally but is published on
> **3307** because 3306 is frequently already in use locally (a native MySQL or
> an SSH tunnel). If 3306 is free for you, change the `-p` mapping and
> `DB_PORT` to `3306`.

## Useful commands

```bash
composer dev          # serve + queue:listen + vite + pail (all-in-one dev)
php artisan serve     # app only
npm run dev           # Vite only (HMR)
npm run build         # production asset build (needed if not running Vite)
php artisan test      # full test suite (uses an in-memory SQLite DB)
composer lint         # Pint (PHP) formatting
```

## Local conventions

- **Database:** MySQL 8 via Docker (above). The automated tests run against an
  isolated in-memory SQLite DB (see `phpunit.xml`), so they don't need the
  container.
- **Mail:** `MAIL_MAILER=log` — confirmation/reminder emails are written to
  `storage/logs/laravel.log`, no real SMTP needed in dev.
- **Queue:** `QUEUE_CONNECTION=database` — `composer dev` runs a `queue:listen`
  worker so queued mail is processed.
- **Dataset size:** `SEED_ROWS` in `.env` controls how many events are seeded
  (default 500 for a fast local start). The planted full dataset is 1,250,000 —
  Approach A is built to handle it (see [CLAUDE.md](CLAUDE.md)).

## Bug fixes applied

Fixed as part of getting the project running (details in
[INVESTIGATION.md §2](INVESTIGATION.md)):

- **Dead filter button** — `Events/Index.vue` bound a misspelled handler
  (`aplyFilters`), so the Filter button did nothing. Now wired to `applyFilters`.
- **`from` date filter ignored** — the frontend sent a `from` param but
  `EventController::loadListing()` only filtered by status. Now applied.

The performance findings (unindexed sort, `COUNT(*)`, payload over-fetch,
unindexed location data) are addressed by **Approach A** — see [CLAUDE.md](CLAUDE.md).

## Stopping / resetting

```bash
docker stop lhp-mysql            # stop the database
docker start lhp-mysql           # start it again
docker rm -f lhp-mysql           # remove it (data is lost)
php artisan migrate:fresh --seed # rebuild schema + reseed
```
