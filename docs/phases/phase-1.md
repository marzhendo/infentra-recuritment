# Phase 1 - Data layer

Read `AGENTS.md` and `docs/PRD.md` (sections 5, 6, 7) first. Build only what is listed here.
No Filament resources, no UI, no import yet. Those are Phase 2.

## Tasks
1. **Migrations** (Laravel conventions, foreign keys with sensible `onDelete`, indexes on lookup columns):
   - `divisions`: name (unique), aliases (json, nullable)
   - `users`: extend the existing table with `nim` (unique), `jabatan`, `role` (enum `admin|koor`), `division_id` (nullable FK)
   - `candidates`: name, nim (nullable), angkatan, pilihan_1_id and pilihan_2_id (FKs to divisions), the file link columns from the Google Form (certificates, CV, portfolio; keep them as text URLs), form_timestamp, status, is_hmif (bool, default false), plus a nullable unique key used for idempotent re-import (form_timestamp + name)
   - `interview_slots`: date, starts_at, ends_at, room (default `DC-302`), candidate_id (nullable, unique so one candidate has at most one slot)
   - `rubric_aspects`: name, position, is_active
   - `scores`: slot_id, interviewer_id (users), rubric_aspect_id, value (1-5, enforce with a check or validation), note (nullable); unique per (slot, interviewer, aspect)
   - `decisions`: candidate_id, division_id, status (enum `lolos|tidak_lolos|cadangan`), note, decided_by; unique per (candidate, division)
   - `placements`: candidate_id (unique), division_id (nullable), final_status, set_by
   - `import_logs`: imported_at, created_count, updated_count, user_id
2. **Enums** for role, candidate status, decision status. **Models** with relationships and casts. **Factories** for every model.
3. **Seeders**
   - `DivisionSeeder`: Acara, Keamanan, Danus & Konsum (alias Usdakom), Humas, Perkap, PDD, Sponsor (alias Sponsorship), IT Team.
   - `RubricAspectSeeder`, exactly these 7, in this order: Komitmen & Tanggung Jawab, Komunikasi, Problem Solving, Kerja Sama Tim, Inisiatif & Proaktif, Ketersediaan Waktu, Motivasi & Pemahaman Peran.
   - `PohUserSeeder`: read `database/seeders/data/poh.csv` (columns: jabatan,nama,nim,role,division). If the file is missing, skip with a clear console message. Add the path to `.gitignore`. Hash nothing yet: login by NIM is Phase 5, so do not invent a password scheme; ask me if the User model requires one.
4. Keep the existing Filament admin user working.

## Verification gate (all must pass before you report)
- `php artisan migrate:fresh --seed` succeeds (with and without `poh.csv` present).
- A test file for each relationship (candidate to two divisions, slot to candidate, score uniqueness, decision uniqueness).
- Show `php artisan model:show` (or equivalent) output summary for Candidate and InterviewSlot.
- `git status` shows `poh.csv` is ignored.

## Report format
1. What was built (files list), 2. command outputs, 3. anything you were unsure about and did not decide,
4. proposed Phase 2 questions. Then STOP.