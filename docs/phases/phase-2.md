# Phase 2 - Close Phase 1, then CSV import and candidate browsing

Read `AGENTS.md` and `docs/PRD.md` first. Build only what is listed. No scheduling, scoring or login yet.

## Decisions already made (answers to your Phase 2 questions)
- CSV parsing: use `league/csv`. It is the only new package allowed in this phase.
- Panels: ONE Filament panel at `/admin`. Admin vs Koor differences come later via policies (Phase 5).
- Duplicates: the import key is `form_timestamp + normalized name`. If the same normalized name appears
  with a different timestamp, do NOT merge or overwrite. Import both rows and show a "Possible duplicate" badge.
  I will decide the merge policy later.
- Import runs synchronously (about 70 rows). No queues, no Filament ImportAction.

## Step A - Close the Phase 1 gate (evidence was missing)
1. Run `php artisan test` and show the full summary output.
2. Run `php artisan migrate:fresh --seed` with `database/seeders/data/poh.csv` temporarily renamed, show it does not error and prints the skip message, then restore the file and run it again.
3. Show the indexes on `candidates` (confirm a unique index on the import key exists) and on `users` (nim unique).
4. Show `User::count()` is 16 after seeding and that every seeded POH user has a NULL password.
5. Run `git log --all --oneline -- database/seeders/data/poh.csv`. It must print nothing. If it prints anything, tell me immediately.
After this step do NOT run `migrate:fresh` again. From here on, change the schema only with NEW migrations.

## Step B - Schema fixes (new migration, no fresh)
Input file: `storage/app/private/imports/candidates.csv` (gitignored; add the path to `.gitignore` if not ignored).
0. Read ONLY the header row of that CSV. Print a table: CSV header text -> proposed column. Then WAIT for my OK before continuing.
Known so far: the form has TWO certificate fields (PKKMB certificate and WPI certificate), plus CV and Portfolio, so the single `file_certificate` column is wrong.
Planned changes, to be confirmed against the real headers:
- `form_timestamp`: change from string to nullable datetime. The CSV format is `d/m/Y H:i:s` (example `22/09/2026 14:50:35`).
- Split the certificate column into `file_cert_pkkmb` and `file_cert_wpi`; keep `file_cv`, `file_portfolio`.
- Add `form_data` (json, nullable) that stores the full original CSV row keyed by header text, so no form answer is ever lost.
- Update models, factories and tests accordingly.

## Step C - Import service (TDD)
Create a plain class `App\Services\CandidateImporter` (no Filament dependency). Write the tests first.
- Map columns by header text, never by column position. Handle BOM, quoted fields and blank trailing lines.
- Parse timestamps as `d/m/Y H:i:s`. Trim names. Store `angkatan` consistently.
- Resolve `Pilihan 1` and `Pilihan 2` to divisions by name or alias, case-insensitive and trimmed. Unknown division name: record a row error with the line number and skip that row. Never crash the whole import.
- Idempotent upsert by the import key. On update, never touch `is_hmif`, `status`, slots, scores, decisions or placements.
- Write one `import_logs` row per run. Return a summary: created, updated, skipped, errors (with line numbers).
Required tests: importing the same file twice gives 0 created on the second run; BOM file; unknown division; duplicate-name rows are both imported; `is_hmif` survives a re-import.

## Step D - Filament CandidateResource
Generate with artisan and follow the v5 generated shape (see AGENTS.md section 4). Indonesian labels.
- Table columns: name, angkatan, Pilihan 1, Pilihan 2, status, HMIF (icon), badges "Possible duplicate" (same normalized name exists) and "Pilihan 1 = Pilihan 2".
- Search by name. Filters: division (matches Pilihan 1 OR Pilihan 2), angkatan, HMIF yes/no.
- Row action and bulk actions: "Tandai HMIF" and "Hapus tanda HMIF".
- Header action "Import CSV": file upload, runs `CandidateImporter` synchronously, shows a notification with the summary and, if any, the error lines.
- No create or delete actions needed; candidates come from the import.

## Step E - View page with file previews
- Show every form field (from `form_data` plus the typed columns).
- A helper `App\Support\DriveLink` converts `open?id=X`, `file/d/X/view` and `file/d/X/edit` links to `https://drive.google.com/file/d/X/preview`. Render in an iframe. Always add an "Buka di tab baru" link. Empty or invalid links must render a neutral "Tidak ada berkas" message, never an error. Unit-test the helper.

## Verification gate
- `php artisan test` passes (show summary).
- Import the real CSV twice. Show both summaries; the second must show 0 created.
- Show `php artisan route:list --path=admin` summary.
- A test proves the bulk HMIF action and that a re-import keeps `is_hmif`.
- `git status` shows no CSV or NIM data is tracked.

## Report format
1. Files built, 2. command outputs, 3. things you were unsure about and did not decide, 4. Phase 3 questions. Commit as `phase-2: ...`, then STOP.