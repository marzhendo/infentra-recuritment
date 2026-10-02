# Phase 7 (revised) - POH scoring and per-person notes, then results and export

This is the "Keputusan dan Penempatan" work. Heroku deployment (Phase 6) is done and the app is LIVE on Postgres, so every migration must be additive and safe on a populated database, every query portable, and nothing may be stored on disk. Read `AGENTS.md` and `docs/PRD.md` first and follow AGENTS.md section 4 (Filament v5, check against `vendor/filament`).

The work is split in two parts with a hard STOP between them:
- **Part A** is needed on interview day 1 (3 Oct): all POH can score and write notes, notes are separate per person and visible to everyone logged in. Do Part A first and STOP.
- **Part B** (results page and CSV exports) is built after day 1. Do not start it until I say so.

## Decisions already made
- Any user with the admin role (Ketua, SC, PIC, Sekretaris, Bendahara) may SCORE and write a NOTE for any candidate who has an interview (non-HMIF). The head interviewer keeps all existing powers (placement).
- A koor may score and write a note for candidates who chose their division as Pilihan 1 or Pilihan 2. A koor of an unrelated division is read-only (defaults, I may change this).
- HMIF candidates are never scored, but any permitted person may write a note on them (they still receive decisions).
- Every person has ONE note per candidate, editable by its author only. Nobody can edit or delete another person's note, not even the head interviewer.
- Notes and scores are visible to every logged-in koor and POH, always labelled with the author's name and jabatan, grouped per person. They must never appear on any public page.
- Decisions stay as they are (each koor decides only their own division). Placement stays head-interviewer only.
- "Penilai utama" (primary scorers) for any progress or completeness figure = the head interviewer plus the koor of Pilihan 1 and Pilihan 2. Other POH scores are extra input: shown and averaged separately, never required.

## PART A

### Step 0 - Evidence first (tests with synthetic data only, never real data)
1. HMIF candidates have a named public slot but are excluded from the scoring list, the "waiting for your score" counters, scorecard completeness and averages. Show a test for each.
2. A published interview day cannot be regenerated (or needs explicit confirmation and then locks all its slots). Show the test.
3. Report where the Phase 4 scorecard note is stored today (`scores.note` per aspect, or one per interviewer?).

### Step A1 - Data (additive migration)
- New table `candidate_notes`: candidate_id, author_id, body (text), timestamps, unique (candidate_id, author_id), indexes for lookups.
- If notes already exist on `scores`, copy them into `candidate_notes` idempotently inside the migration, stop writing the old column, and keep the old column in place for now (no drop). Tell me exactly what was migrated.
- `candidates.catatan` (the single shared logistics note from Phase 4) stays. Rename its UI label to "Catatan umum" so it is not confused with personal notes.

### Step A2 - Policies and services (TDD, tests first)
- Update `ScorePolicy` and add `NotePolicy` to the rules above. Enforce on the server, never only by hiding buttons.
- Required tests, a full role matrix for score and note: head interviewer, admin-role POH (each of the four), koor of Pilihan 1, koor of Pilihan 2, unrelated koor, a user with no role; for a normal candidate, an HMIF candidate and a candidate without a slot. Plus: editing another person's note is denied; one note per author per candidate; values outside 1-5 rejected; two people saving at the same time never overwrite each other.
- Averages: provide `average_primary` (primary scorers only), `average_other_poh` and `average_all`. Null when there are no scores.

### Step A3 - UI (mobile first, Indonesian labels)
- Wawancara page: the "Nilai" button is visible on every row the current user may score, for admin-role POH on all non-HMIF rows. Users who may not score see a muted "Hanya lihat". Highlight the current or next slot by time (Asia/Jakarta). Poll the list state every 30 seconds so status chips update without a reload.
- Scorecard: 7 rubric aspects as large 1-5 buttons, a field "Catatan saya", save. Below it, "Penilaian dan catatan lain": one block per other person, each with name, jabatan badge, their average and their note. Own block is editable, others read-only.
- Candidate view page: a section "Penilaian dan catatan" with the same per-person blocks (also for HMIF and candidates without a slot, where only notes apply), newest edits marked with the time.
- Dashboard: koor see "N calon menunggu nilai Anda" counting only their own candidates. Admin-role POH see no counter (their scoring is optional).
- Notes contain opinions about students: no export or public exposure in this part.

### Part A verification gate
- Full `php artisan test`, real terminal output with the total count; the new role matrix tests; smoke tests rendering Wawancara, scorecard modal and candidate view for every role.
- A test proving that notes, scores and author names never appear in `/jadwal`.
- Migration verified on an empty and on a populated database (synthetic) on SQLite and, if possible, Postgres. Show the rollback command.
- Do not touch real data. Do not run migrate:fresh. Commit as `phase-7a: ...`, report, then STOP.

## PART B - only after I confirm (after interview day 1)

### Step B1 - Results queries (portable SQL)
One service `ResultsQuery` used by the page and the exports, per non-duplicate candidate: display name, angkatan, Pilihan 1, Pilihan 2, number of scorers, `average_all`, `average_primary`, average of the Pilihan 1 koor, average of the Pilihan 2 koor, `average_other_poh`, decision for each choice, suggested placement (`PlacementSuggester`), final placement and status, shared catatan, HMIF flag.
- Postgres sorts NULLs first on DESC: use NULLS LAST (or COALESCE). Test ordering.
- Incomplete scorecards are flagged, not hidden. Test candidates with Pilihan 1 = Pilihan 2 (one decision, one koor average).

### Step B2 - "Hasil Seleksi" page
- Admin-role users see all candidates. A koor sees only candidates who chose their division and cannot export. Enforce in policies and test every role.
- Progress strip: primary scorecards complete / partial / none, decisions in / missing, placements set / pending.
- Default columns: name, angkatan, Pilihan 1, Pilihan 2, `average_primary`, `average_all`, decisions, placement. The rest togglable. Search, filters (division, decision status, not yet scored, HMIF, placement status), default sort by `average_primary` descending.
- Row detail shows the per-person scores and notes blocks.
- Placement: head interviewer only. Row action "Tetapkan penempatan" and a bulk action "Terapkan saran" applying the suggestion only to selected rows where the Pilihan 1 koor decided `lolos`, with a count and a confirmation. Rows where Pilihan 1 is not `lolos` are never changed automatically.

### Step B3 - CSV exports (admin-role only, streamed, no files on disk)
1. "Rekap nilai": per candidate and per person (name, jabatan), the 7 aspects, the person's average and note.
2. "Penempatan final": name, angkatan, NIM, WhatsApp, Pilihan 1, Pilihan 2, final division, final status, shared catatan.
3. "Per divisi": candidates placed in each division with status `lolos` or `cadangan`.
- UTF-8 with BOM. Neutralise CSV injection (cells starting with `=`, `+`, `-`, `@`), with a test. Log who exported what and when (`export_logs`). Koor never get exports.

### Part B verification gate
Full `php artisan test` with real output and total count, role matrix for the page and every export, bulk-suggestion rule, NULLS LAST ordering, CSV injection, smoke tests per role. Do not touch real data. Commit as `phase-7b: ...`, then STOP.