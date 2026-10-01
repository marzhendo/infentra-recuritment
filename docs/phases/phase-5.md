# Phase 5 - POH login, POH accounts page and public schedule page

Read `AGENTS.md` and `docs/PRD.md` first. Follow AGENTS.md section 4 (Filament v5): check the installed vendor source, never write v3 syntax from memory. Build only what is listed. Do not run `migrate:fresh`.

## Step 0 - Clean up Phase 3/4 leftovers (show evidence first)
1. Your last report left a locked test slot in my real local DB (the one you described at 08:00 on Day 1). Delete ALL rows in `interview_slots`, including locked ones, then confirm `interview_slots` is empty and no `scores` exist. Test data must never stay in my DB.
2. Re-run the generator once with 0 HMIF and 0 duplicate flags. The result must equal the number of eligible candidates (89). Your last report implies Day 1 = 44 slots and Day 2 = 43 slots, which is 87. Show per day: sessions, first start, last end, `left_over`, plus a query that lists eligible candidates with NO slot. If any candidate has no slot, find the cause and fix it. Expected with the current placeholder breaks: Day 1 45 sessions ending 17:40, Day 2 44 sessions ending 17:30.
3. Add a test: after `generate()`, `slots + skipped_hmif + skipped_duplicate` equals the number of candidates, so no eligible candidate can be silently dropped.
4. Your permission matrix now shows the Ketua as Allowed to save decisions for Pilihan 1 and Pilihan 2. Earlier it was Denied, and the rule is that only the koor of that division decides. Explain why it changed and restore the rule (koor of that division only) unless you have a strong reason, in which case stop and ask me.
5. Print the real total test count.

## Decisions already made
- Login mode for now is NIM-only. Flow: pick a group, pick your name, enter your NIM.
- If a user has a non-null password, that user must ALSO enter the password. Setting a password later upgrades that account automatically, with no code change. Seeded POH users keep NULL passwords.
- Tomorrow I will collect campus emails and set passwords myself, so build a page for that (below). Any user who has an email and a password can also log in with email and password.
- Only the head interviewer (Ketua) manages accounts.

## Task A - Login
- Custom login page for the panel (use the v5 panel auth API). `/admin/login` shows it.
- Step 1: groups: "Pimpinan" (Ketua Pelaksana, Steering Committee, PIC), "Sekretaris", "Bendahara", then each division by its koor. Step 2: the names in that group. Step 3: NIM field, plus a password field that appears only for accounts that have a password. Add a small separate "Masuk dengan email" form for the existing email accounts, including my dev admin account.
- Errors are generic ("NIM atau kata sandi salah"). Rate limit: at most 5 failed attempts per minute per IP and per user. No "remember me".
- `canAccessPanel`: allow users whose role is admin or koor. Before changing it, check the role of my dev admin account (created with make:filament-user). If its role is null, tell me instead of guessing.
- Add `noindex` (meta robots and `X-Robots-Tag`) on the login page and all `/admin` pages.

## Task B - "Akun POH" page (Ketua only)
- Filament resource listing the 16 POH users: name, jabatan, role, division, whether an email is set, whether a password is set (yes/no only).
- Row action "Atur akun": set the email (unique) and a password (hashed). Row action "Hapus kata sandi" returns the account to NIM-only. Never display a hash or an existing password. Show a clear warning that NIM-only accounts are weaker.
- Add policies and tests (non-Ketua cannot open or use it).

## Task C - Public schedule page `/jadwal`
- Mobile first, one text input "Cari nama". Search is partial and case-insensitive on both the stored name and `display_name`, minimum 3 characters, at most 10 results, no "list everyone" endpoint, no pagination. Rate limit 30 requests per minute per IP.
- Each result shows the display name, date, time, room. Candidates flagged HMIF show "Dibebaskan dari wawancara". Candidates with no slot show "Belum dijadwalkan". Nothing else: no NIM, WhatsApp, files, notes, scores, divisions or decisions.
- Add `noindex`. Show the event name "INFENTRA 2.0".
- Styling: read `DESIGN.md` and apply the antislop filter (antislop-ui and antislop-layoutmobile). If `DESIGN.md` does not exist, build with neutral minimal styling, do not stop, and mark the design tokens as a TODO in your report.

## Required tests
Login: NIM-only account logs in; an account with a password cannot log in with NIM alone; wrong NIM gives the generic error; rate limit triggers; roles other than admin and koor are refused. Accounts page: only the Ketua. Public page: partial name works, fewer than 3 characters returns nothing, HMIF and unscheduled messages, and the response body never contains a NIM or WhatsApp value; search is rate limited. Include the smoke tests for the new pages.

## Verification gate
Real total test count (plain text if you can, otherwise write plain output to `storage/logs/test-output.txt`). `php artisan route:list` summary for `/admin/login`, `/jadwal`. Show that no seeded POH user has a password. `git status` clean of data files.

## Report format
1. Files built, 2. outputs including the Step 0 evidence, 3. things you were unsure about and did not decide, 4. Phase 6 questions (Heroku deploy). Commit as `phase-5: ...`, then STOP.