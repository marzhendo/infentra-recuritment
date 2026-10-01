# Phase 4 - Fix Phase 3 duplicate handling, then scoring, decisions and placement

Read `AGENTS.md` and `docs/PRD.md` (sections 3, 5, 6, 7) first. Build only what is listed. No login or public page yet (Phase 5).

## Step 0 - Fix Phase 3 loose ends (do this first, show evidence)
1. Duplicates in the generator: remove the name-matching logic from `ScheduleGenerator`. The importer already merges the same person (same normalized name AND same WhatsApp). Two different people with the same name but different WhatsApp are two candidates and BOTH must get a slot. Skipping is allowed ONLY for `is_hmif = true` or `is_duplicate = true` (the manual flag from Phase 2).
   - Show that the `is_duplicate` column exists and that the "Tandai duplikat" / "Hapus tanda duplikat" row action exists in the candidates table. If either is missing, add it as specified in `docs/phases/phase-2.md` and test it.
   - Add a test: two candidates with the same name and different WhatsApp both receive slots. Add another: a candidate with `is_duplicate = true` receives none.
   - Add `skipped_hmif` and `skipped_duplicate` counts to the generator report.
2. Test evidence: your last two reports gave a JSON summary showing only 9 tests, but the full suite should be about 29 or more (the earlier 20 plus the new ones). Run the FULL `php artisan test`, paste the real terminal text (copy and paste, not a summary), and state the total number of tests. If the number is lower than the previous total, explain which tests disappeared and why.
Do not run `migrate:fresh` in this phase.

## Decisions already made (these answer your Phase 4 questions)
- Who may score a candidate: the head interviewer (Ketua Pelaksana) for every candidate, plus the koor whose division is that candidate's Pilihan 1 OR Pilihan 2. All other users are read-only. Enforce this on the server in policies, not only by hiding UI.
- No "simultaneous overwrite" problem by design: each score row belongs to one interviewer (unique per slot, interviewer, aspect). A person saving twice overwrites only their own rows (last write wins). Nobody can edit another person's scores.
- Visibility: every logged-in koor and admin can SEE all scores of a candidate.
- Final placement is MANUAL. No threshold or sorting algorithm. The UI only shows helper information.

## Step A - Schema (new migrations)
- `users.is_head_interviewer` (bool, default false). `PohUserSeeder` sets it true only where `jabatan` is `Ketua Pelaksana`.
- Check that `scores`, `decisions`, `placements` match the PRD data model; add only what is missing.

## Step B - Services and policies (TDD, tests first)
- `App\Policies\ScorePolicy` and a `ScoreSubmitter` service: stores 1-5 values for the active rubric aspects, rejects values outside 1-5, allows partial save, and exposes "scorecard complete" (a score for every active aspect).
- `DecisionPolicy`: a koor may set a decision (`lolos|tidak_lolos|cadangan` plus a note) ONLY for their own division and only if that division is the candidate's Pilihan 1 or Pilihan 2. A candidate with Pilihan 1 = Pilihan 2 has ONE decision row for that division.
- HMIF candidates have no slot and no scores, but they DO receive decisions from the koor (based on their files).
- `PlacementSuggester`: pure function. If the Pilihan 1 decision is `lolos`, suggest Pilihan 1. Otherwise suggest nothing and expose the Pilihan 2 decision for the admin. It never saves anything by itself.
- Aggregation helpers: average per interviewer and overall average per candidate.
Required tests: the full permission matrix (Ketua yes; koor of Pilihan 1 yes; koor of Pilihan 2 yes; unrelated koor no; non-koor admin no), cannot edit another person's rows, value 0 and 6 rejected, completeness check, decision only for own division, Pilihan 1 = Pilihan 2 gives a single decision, HMIF candidate can get a decision but not a score, suggester cases.

## Step C - Filament UI (mobile first, Indonesian labels; follow AGENTS.md section 4)
- Page "Panel Wawancara": select the interview day (default today if it matches, else the first day), list the slots in time order with candidate name, Pilihan 1/2, and a state chip (belum dinilai / sebagian / lengkap for the current user). Tapping a slot opens the scorecard.
- Scorecard: candidate summary, links to open the files (reuse the Drive preview), the active rubric aspects as large 1-5 radio buttons, a note field, save. Shows other interviewers' saved scores read-only.
- Decisions: on the candidate view page, a section "Keputusan" listing both divisions' decisions; a koor sees an editable form only for their own division.
- Placement: on the candidate view page (admin only), a section "Penempatan" with the helper info (decision per division, per-interviewer averages, overall average), the suggestion from `PlacementSuggester` shown as a hint, and a manual form: division and final status. Saving requires an explicit click.
- Do not restyle Filament defaults beyond what these pages need.

## Verification gate
- Full `php artisan test`, real terminal output, with the total count.
- Print the permission matrix results as a table (who tried to score or decide what, allowed or denied).
- Walk through the UI using the acting-as feature in tests only; do not create any user with a default password. I will test the UI by hand with my own account.
- `git status` clean of data files.

## Report format
1. Files built, 2. outputs, 3. things you were unsure about and did not decide, 4. Phase 5 questions (login by division, name and NIM, plus the shared access code). Commit as `phase-4: ...`, then STOP.