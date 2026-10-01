# Phase 3 - Interview days, break blocks and schedule generator

Read `AGENTS.md` and `docs/PRD.md` (sections 4, 5, 7) first. Build only what is listed. No scoring, decisions, login or public page yet.

## Decisions already made (these answer your Open Question 2 and 3)
- Candidate order: by `form_timestamp`, earliest first.
- Day split: distribute candidates evenly across the configured days, keeping that order (first half Day 1, second half Day 2).
- Break blocks are DATA, editable in the admin: each day has its own list of blocks (label, start time, duration in minutes). Seed placeholders: "Dzuhur" 11:45 for 60 min and "Ashar" 15:00 for 60 min on both days. These times are placeholders; I will correct them from the real prayer schedule.
- Defaults: days 2026-10-03 and 2026-10-04, start 08:00, slot 10 minutes, room `DC-302`.
- A candidate with `is_hmif = true` or `is_duplicate = true` NEVER gets a slot. They stay visible in the candidate list.
- The finish time is not enforced. The generator only REPORTS the estimated finish time per day (nullable optional `ends_at` on the day shows a warning if exceeded).

## Step 0 - Phase 2 loose ends (answer in the report, change code only if needed)
1. Re-importing the same file currently reports 89 "updated". Make the importer report `unchanged` for rows whose data did not change, and `updated` only for real changes. Add a test.
2. Explain in two sentences exactly how the importer decides two rows are the same person, and how it picks which submission wins. Show the real merged pairs (row numbers and timestamps only, no personal data) and confirm the older submission's data is kept in `form_data` history or explain what is lost.

## Step A - Schema (new migrations, no fresh)
- `interview_days`: date (unique), starts_at, slot_minutes (default 10), room (default `DC-302`), ends_at (nullable).
- `break_blocks`: interview_day_id (FK, cascade), label, starts_at, duration_minutes.
- Link `interview_slots` to its day (`interview_day_id`, nullable at first, then required for new rows). Keep the existing unique `candidate_id`. Add `is_locked` (bool, default false) so a manually placed slot is not moved by regeneration.
- Models, factories, seeder for the two days with their placeholder breaks.

## Step B - ScheduleGenerator service (TDD, write tests first)
`App\Services\ScheduleGenerator`, a plain class with no Filament dependency.
- Eligible candidates: not HMIF, not duplicate, and not already holding a LOCKED slot.
- Walk each day's timeline from `starts_at` in `slot_minutes` steps. A slot may not overlap a break block: if the next slot would start inside or straddle a break, jump to the end of that break.
- Persist empty slots first, then assign candidates in order, so empty slots exist for manual use.
- `generate()` is idempotent: it replaces all UNLOCKED slots, and refuses (throws a clear exception) if any slot of that day already has a score.
- Returns a report per day: number of sessions, first start, estimated finish, number of candidates left over.
Required tests:
1. No breaks: 12 candidates, 10 minutes each, correct start times.
2. A break in the middle: no slot overlaps it, the slot after the break starts exactly at the break end.
3. A slot that would straddle a break start is pushed after the break.
4. HMIF and duplicate candidates are skipped.
5. Order follows `form_timestamp`.
6. 89 candidates over 2 days split 45 / 44.
7. Running `generate()` twice gives the same result and no duplicate slots.
8. Locked slots survive regeneration, and their candidates are not assigned twice.
9. Refuses when a score exists for that day.

## Step C - Filament admin
Generate with artisan and follow the v5 generated shape (AGENTS.md section 4). Indonesian labels.
- `InterviewDayResource`: edit date, start, slot length, room, optional end, and a repeater for break blocks. Header action "Generate jadwal" with a confirmation modal that shows the numbers of eligible candidates, HMIF and duplicate skipped, then shows the generator report as a notification.
- `InterviewSlotResource` (or a table page): grouped by day, columns time, candidate name, Pilihan 1, Pilihan 2, locked icon. Row actions: assign candidate (select only eligible unscheduled candidates), clear slot, lock/unlock, swap with another slot. Validation: HMIF and duplicate candidates cannot be assigned.
- Show per day: sessions, first start, estimated finish, unscheduled eligible candidates count.

## Verification gate
- `php artisan test` passes. I will also run it myself, so print the real terminal output, not a JSON summary.
- On the real local data: run the generator with 0 HMIF candidates and report per day sessions and estimated finish. Then mark 10 candidates as HMIF, run again, and report the new numbers.
- Show that a locked slot survives regeneration.
- `git status` clean of any data file.

## Report format
1. Files built, 2. outputs, 3. things you were unsure about and did not decide, 4. Phase 4 questions (scoring and decisions). Commit as `phase-3: ...`, then STOP.