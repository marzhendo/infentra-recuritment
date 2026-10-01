# Phase 6 (revised) - Heroku deployment

Read `AGENTS.md` and `docs/PRD.md` first. Goal: one Heroku app (Laravel + Filament, Heroku Postgres) that POH can use on phones on 3-4 Oct 2026. Time is critical: the interviews start 3 Oct at 08:00, so do only what is listed. You prepare files, tests and a runbook; I run the `heroku` commands myself. Never run anything that touches production data or creates paid resources without asking me first.

## Step 0 - Preconditions (show evidence, do not redo what is already done)
Confirm with real output that these earlier items are finished, and do any that are not:
- P1-P6 (schedule count 89 vs 87, leftover locked test slot, password login with `poh:set-password` and `poh:users`, public search limits, regression test for candidate view, real full test count).
- B1-B4 (app name "INFENTRA 2.0" and locale `id`, junk "Koor Div 1/2/3" removed, `phpunit.xml` forced to an in-memory database, login dropdown built from real POH users only).
Also confirm: no scratch or data file is tracked by git, and the local database was not modified by running the tests.

## Step A - SQLite vs Postgres audit (local is SQLite, production is Postgres)
1. Grep `app/`, `database/`, `tests/` for portability traps and fix them: `strftime`, `RANDOM()`, raw `= 1` / `= 0` on boolean columns, `whereRaw` / `selectRaw` / `DB::raw`, and `LIKE` used as case-insensitive search (Postgres `LIKE` is case-sensitive; use `whereLike(..., caseSensitive: false)` or `LOWER(...) LIKE`). This affects the public `/jadwal` search and Filament searches.
2. Run the full tests against a real Postgres database (local or throwaway, never production). If that is impossible, say so and list the tests that remain unproven on Postgres.
3. Make sure every migration runs on an empty Postgres database (`migrate:fresh` on the throwaway DB only), including constraints, unique indexes, `time` and `datetime` columns and later column changes.

## Step B - Production configuration
- `composer.json`: the PHP constraint must be satisfiable by the Heroku PHP buildpack for the current stack. Tell me the exact PHP version Heroku will use.
- `Procfile`: `web: heroku-php-apache2 public/` and a `release:` entry running `php artisan migrate --force`.
- Filament and Livewire assets must exist after the build: after `git push heroku`, `/admin/login` must load CSS and JS without 404. Add whatever build or `composer` script step is needed (for example publishing assets). If any public page uses Vite/Tailwind, add the Node buildpack and a `heroku-postbuild` script; if not, say so.
- Environment variables only: `APP_NAME="INFENTRA 2.0"`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `APP_TIMEZONE=Asia/Jakarta` (dynos run in UTC; "today" and slot times depend on it), `APP_LOCALE=id`, `LOG_CHANNEL=stderr`, `DB_CONNECTION=pgsql` with `DB_URL` from `DATABASE_URL`, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`. The filesystem is ephemeral: nothing important is stored on disk.
- Trust Heroku's proxy headers and force HTTPS in production.
- `X-Robots-Tag: noindex` on the whole app.
- Login rate limit (for example 5 attempts per minute per IP and per NIM). Login stays NIM + password. There must be no way to sign in with a NIM alone, and no default or placeholder password anywhere.
- Confirm no secret, `.env`, CSV or database file is tracked by git.

## Step C - Seeding in production without committing personal data
`poh.csv` and the candidate CSV are git-ignored, so they will not exist on Heroku.
- `PohUserSeeder` must also accept the CSV content from the environment variable `POH_CSV_BASE64` when the file is missing. Document how I create that config var from my local `poh.csv` in PowerShell.
- Divisions (8 real ones with aliases), the 7 rubric aspects and the two interview days (3 and 4 Oct, 08:00, 10-minute slots, `DC-302`, break blocks Dzuhur and Ashar as placeholders) must seed on an empty database.
- The seeder sets `is_head_interviewer = true` for the `Ketua Pelaksana` row, covered by a test. No manual SQL.
- Candidates are not seeded. After deploy I import the final CSV through the admin "Import CSV" action. Expected result on the empty production database: 104 created, 0 updated, 0 errors, 38 candidates with Pilihan 1 = Pilihan 2 (the file has 106 rows, 2 are resubmissions). Confirm the upload and import work on the ephemeral filesystem.
- I set every POH password with `php artisan poh:set-password {nim}` via `heroku run`. It must never print or log a password.

## Step D - Runbook `docs/DEPLOY.md`
Exact commands in order, for Windows PowerShell where relevant: `heroku login`, create the app, add Postgres, buildpacks, every `heroku config:set` above (generate `APP_KEY` locally with `php artisan key:generate --show`), `git push heroku`, run the seeders, set passwords, always-on dyno type for 3-4 Oct, manual Postgres backups (before the event, after the final schedule, and after day 1), rollback of a release, and the order of the live steps: import CSV, mark HMIF, correct the break times from the real prayer schedule, Generate Jadwal, publish the schedule. State which steps cost money. Note that anything I did locally (HMIF flags, renamed candidates) is not on the server.

## Verification gate
- Full `php artisan test`, real terminal output with the total count, on SQLite and (if possible) on Postgres.
- A test that the public name search is case-insensitive.
- A checklist for me to run on the live site: `/admin/login` loads with styles, login with a POH account, import the real CSV, HMIF toggle, Generate Jadwal, open `/jadwal` on a phone, open a candidate with CV preview, the response headers show noindex and HTTPS, `APP_DEBUG` is off (a 404 page shows no stack trace).
- `git status` shows no secret or data file.

## Report format
Files changed, outputs, the exact PHP version Heroku will use, anything you could not verify, and the production risks you still see. Commit as `phase-6: ...`, then STOP.