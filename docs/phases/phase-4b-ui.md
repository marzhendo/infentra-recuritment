# Phase 4b - Admin polish (cosmetic, time-boxed)

Run this AFTER Phase 5 (login and public schedule page). It changes presentation only: no schema changes except where stated, no new business rules. Read `AGENTS.md` first. Follow section 4 (Filament v5 warning) and use only Filament's theming and configuration APIs. Do not add custom CSS hacks or override Filament components. Use the `antislop-ui` and `antislop-layoutmobile` skills only as a review lens for spacing, hierarchy and contrast on these screens.

## Problems seen on the real screens
- English and Indonesian are mixed ("Candidates", "New Hari Wawancara", "Showing 1 to 2 of 2 results", "Sign in", "Welcome").
- The brand is the default "Laravel". Dashboard shows the stock Welcome and Filament docs/GitHub widgets, which are useless here.
- The candidates table repeats a red "Hapus Tanda HMIF" text on every row (looks destructive), and "Pilihan 1 = Pilihan 2" is a loose grey line under the name.
- Names are inconsistent (some all caps), and the table is cramped.

## Tasks
1. **Language and naming:** set `APP_NAME="INFENTRA Recruitment"`, locale `id` (fallback `en`). Verify Filament's built-in strings (pagination, buttons, login) appear in Indonesian. Resource and navigation labels: "Calon Panitia", "Hari Wawancara", "Slot Wawancara", and the Phase 4 pages in Indonesian. Group navigation sensibly (for example "Seleksi" and "Wawancara"). Set the panel brand name to the app name.
2. **Colour and type:** use one primary colour through the panel's colour API and one font through the panel's font API. Take the colours from `DESIGN.md` if it exists; otherwise ask me before choosing. Keep dark and light modes both readable.
3. **Candidates table (`/admin/candidates`):**
   - Replace the per-row HMIF actions with an inline toggle column for `is_hmif` (I will mark many candidates by hand, so one click per row matters). Keep the bulk actions.
   - Show names through the display name from Phase 4. Show "P1 = P2" and "Portofolio belum ada" as small badges in their own column. Hide the `status` column by default (togglable).
   - Group the row actions into one action group (view, rename, mark duplicate). Default sort by `form_timestamp` ascending, striped rows, page sizes 25/50/100 with 50 default.
4. **Dashboard:** remove the default Welcome and docs/GitHub widgets. Add stat cards: total candidates, HMIF marked, duplicates flagged, candidates scheduled vs unscheduled, and per interview day the sessions and estimated finish.
5. **Slot table:** compact rows grouped by day, time in a fixed-width style, division shown as coloured badges, locked icon, empty slots visibly different.
6. **Interview days list:** show the break blocks per day in the table (for example "Dzuhur 11:45, Ashar 15:00").

## Verification gate
- Full `php artisan test` with real terminal output and total count (existing tests must still pass).
- A test that the inline HMIF toggle persists and that a re-import keeps it.
- Locale test: a sample of built-in strings render in Indonesian.
- Do NOT regenerate the schedule or change any real candidate data while verifying. Use tests.

## Report format
Files changed, test output, the colour and font you chose and why, anything you left undone. Commit as `phase-4b: ...`, then STOP.