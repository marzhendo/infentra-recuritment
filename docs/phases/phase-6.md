# Phase 6 - Heroku deployment (Postgres, production hardening)

Read `AGENTS.md` and `docs/PRD.md` first. Goal: a single Heroku app (Laravel + Filament) with Heroku Postgres, usable by POH on phones on 3-4 Oct 2026. Do not add features. You prepare files, tests and a step-by-step runbook; I run the `heroku` commands myself. Never run anything that touches production data or creates paid resources without asking me first.

## Step A - SQLite vs Postgres audit (local DB is SQLite, production is Postgres)
1. Grep `app/`, `database/` and `tests/` for SQLite-only or portability traps and fix them: `strftime`, `RANDOM()`, raw `= 1` / `= 0` comparisons on boolean columns, `whereRaw` / `selectRaw` / `DB::raw`, `LIKE` used for case-insensitive search. Postgres `LIKE` is case-sensitive: use `whereLike(..., caseSensitive: false)` or `LOWER(...) LIKE`. This matters for the public `/jadwal` name search and for Filament searches.
2. Run the test suite against a real Postgres database (a local Postgres or a throwaway database, not production). If you cannot, say so and list exactly which tests are unproven on Postgres. Report the real terminal output.
3. Check every migration runs on an empty Postgres database (`migrate:fresh` on the throwaway DB only) including check constraints, unique indexes, `time` columns and the column changes made in later migrations.

## Step B - Production configuration
- `composer.json`: the PHP constraint must be satisfiable by the Heroku PHP buildpack for the current stack. Check it and tell me the exact PHP version Heroku will use.
- `Procfile`: `web: heroku-php-apache2 public/` and a `release:` entry running `php artisan migrate --force` (plus `php artisan filament:upgrade` or asset publishing only if the build needs it).
- Node build: if any public page uses Vite/Tailwind, add the Node buildpack setup and a `heroku-postbuild` script that runs the build. If no page needs it, say so and skip.
- Config via environment variables only: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `APP_TIMEZONE=Asia/Jakarta` (default day logic and slot times depend on it; dynos run in UTC), `LOG_CHANNEL=stderr`, `DB_CONNECTION=pgsql` with `DB_URL` taken from `DATABASE_URL`, `SESSION_DRIVER=database` or `cookie`, `SESSION_SECURE_COOKIE=true`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`. The filesystem is ephemeral: nothing important may be stored on disk.
- Trust Heroku's proxy headers so HTTPS URLs and secure cookies work (trusted proxies). Force HTTPS in production.
- Send `X-Robots-Tag: noindex` on the whole app, not only `/jadwal`.
- Rate limit the login route (for example 5 attempts per minute per IP and per NIM).
- Confirm no secret, `.env`, CSV or database file is tracked by git.

## Step C - Seeding in production without committing personal data
`poh.csv` and the candidate CSV are git-ignored, so they will NOT exist on Heroku.
- Make `PohUserSeeder` also accept the CSV content from the environment variable `POH_CSV_BASE64` when the file is missing. Document how I create that config var from my local `poh.csv`.
- Divisions, rubric aspects (the 7 final ones) and interview days (3 and 4 Oct, 08:00, 10 minutes, `DC-302`, break blocks) must seed on an empty database. Break times are placeholders that I will correct in the admin.
- The seeder must set `is_head_interviewer = true` for the `Ketua Pelaksana` row, with a test. Do not rely on any manual SQL.
- Candidates are NOT seeded: I will import the final CSV through the admin "Import CSV" action on the live site, then mark HMIF by hand, then press Generate Jadwal. Confirm that the upload and import work on an ephemeral filesystem.
- No user may be created with a default or placeholder password. Provide `php artisan poh:set-password {nim}` (prompts for the password, hidden input, stores a hash) and `php artisan poh:users` (lists id, name, jabatan, role, has_password yes/no, never any hash).

## Step D - Runbook
Write `docs/DEPLOY.md` with the exact commands in order, for Windows PowerShell where relevant: `heroku login`, create the app, add the Postgres add-on, buildpacks, `heroku config:set ...` for every variable above (generate `APP_KEY` locally with `php artisan key:generate --show`), `git push heroku`, run the seeders, create my admin with `poh:set-password`, set the dyno to an always-on type for 3-4 Oct, take a manual Postgres backup before the event and a second one after day 1, and how to roll back a release. State clearly which steps cost money.

## Verification gate
- Full `php artisan test` (real terminal output, total count) on SQLite and, if possible, on Postgres.
- A test that the public `/jadwal` search is case-insensitive and returns at most 5 results.
- A checklist of manual checks I will do on the live site: `/admin/login`, login by a POH account, import the real CSV, HMIF toggle, generate the schedule, open `/jadwal` on a phone, view a candidate with CV preview, response headers show noindex and HTTPS.
- `git status` shows no secret or data file.

## Report format
Files changed, outputs, the exact PHP version Heroku will use, anything you could not verify, and a list of production risks you still see. Commit as `phase-6: ...`, then STOP.