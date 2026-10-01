# Phase 2 - Close Phase 1, then CSV import and candidate browsing

Read `AGENTS.md` and `docs/PRD.md` first. Build only what is listed. No scheduling, scoring or login yet.

## Decisions already made (answers to your Phase 2 questions)
- CSV parsing: use `league/csv`. It is the only new package allowed in this phase.
- Panels: ONE Filament panel at `/admin`. Admin vs Koor differences come later via policies (Phase 5).
- Duplicates (decided from the real data): a person is identified by `import_key` = normalized name + normalized
  WhatsApp number. Rows sharing an `import_key` are the SAME person who resubmitted the form: keep ONE candidate,
  the latest timestamp wins for all form fields. If the same normalized name appears with a DIFFERENT WhatsApp
  number, they stay separate candidates and get a "Possible duplicate" badge (no merge).
- Normalization: name = trimmed, whitespace collapsed, case-insensitive. WhatsApp = digits only, with a leading `0`,
  `62` or `+62` normalized to `62`.
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
The real CSV has 12 columns. Header cells contain line breaks inside the quoted cell, so match each header on its
FIRST LINE only (trimmed, case-insensitive). Use this fixed mapping:

| CSV header (first line) | Column | Notes |
|---|---|---|
| Timestamp | `form_timestamp` | datetime, format `j/n/Y G:i:s` |
| Nama Lengkap | `name` | trim |
| Angkatan | `angkatan` | 4-digit string (2024, 2025, 2026) |
| Pilihan 1 / Pilihan 2 | `pilihan_1_id` / `pilihan_2_id` | resolve by division name or alias |
| Sertifikat PKKMB/Bukti keikutsertaan | `file_cert_pkkmb` | Drive URL |
| Sertifikat WPI/Bukti keikutsertaan | `file_cert_wpi` | Drive URL |
| CV | `file_cv` | Drive URL |
| WhatsApp | `whatsapp` | keep original text; admin-only, NEVER on public pages |
| Email Address | (ignored) | 100% empty; still kept inside `form_data` |
| Portofolio | `file_portfolio` | optional, only a few rows (PDD and IT Team) |
| NIM | `nim` | optional: most rows are empty; must stay editable in the admin |

Schema changes (new migration only):
- `form_timestamp`: string -> nullable datetime.
- Replace `file_certificate` with `file_cert_pkkmb` and `file_cert_wpi` (nullable text).
- Add `whatsapp` (nullable string) and `form_data` (json, nullable; full original row keyed by the first-line header text, so nothing is lost).
- Replace the previous import key with a unique `import_key` column (string) = normalized name + `|` + normalized WhatsApp. If WhatsApp is empty, use the name alone.
- Update models, factories and tests accordingly.

## Step C - Import service (TDD)
Create a plain class `App\Services\CandidateImporter` (no Filament dependency). Write the tests first.
- Map columns by first-line header text (see Step B), never by column position. Handle BOM, quoted fields with embedded line breaks and blank trailing lines.
- Parse timestamps as `j/n/Y G:i:s`: day, month and hour may have one digit (example `29/09/2026 8:14:52`). Trim every cell. Store `angkatan` as a 4-digit string.
- Resolve `Pilihan 1` and `Pilihan 2` to divisions by name or alias, case-insensitive and trimmed. Unknown division name: record a row error with the line number and skip that row. Never crash the whole import.
- Idempotent upsert by `import_key`. When several rows share a key, the latest timestamp wins for all form fields and the earlier timestamps are stored in `form_data` under `previous_submissions`. Non-empty NIM from any row is kept. On update, never touch `is_hmif`, `status`, slots, scores, decisions or placements.
- Write one `import_logs` row per run. Return a summary: created, updated, skipped, errors (with line numbers).
Tests must use a small SYNTHETIC fixture CSV that you generate (fake names, fake phone numbers, fake Drive links). Never copy rows from the real candidate file into tests or commits. The fixture must reproduce the real quirks: multi-line header cells, a one-digit hour/day timestamp, a blank NIM, a blank Portfolio, and a resubmission pair.
Required tests: importing the same file twice gives 0 created on the second run; BOM file; header with embedded line break; single-digit hour/day timestamps; unknown division; same name + same WhatsApp across two rows gives ONE candidate with the later row's files; same name + different WhatsApp gives TWO candidates; `+62`, `62` and `0` WhatsApp forms normalize to the same key; `is_hmif` survives a re-import.

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
- Import the real CSV twice. Show both summaries; the second must show 0 created. The first run should end with 89 candidates (the file has 91 rows including 2 resubmissions) and 36 candidates whose Pilihan 1 equals Pilihan 2.
- Show `php artisan route:list --path=admin` summary.
- A test proves the bulk HMIF action and that a re-import keeps `is_hmif`.
- `git status` shows no CSV or NIM data is tracked.

## Report format
1. Files built, 2. command outputs, 3. things you were unsure about and did not decide, 4. Phase 3 questions. Commit as `phase-2: ...`, then STOP.