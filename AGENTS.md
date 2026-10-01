# AGENTS.md - INFENTRA Recruitment Hub

> Merge this into the project's existing `AGENTS.md`. Do NOT delete or edit the
> `<!-- antislop:start -->` ... `<!-- antislop:end -->` block written by the antislop installer.

## 1. What this project is
Internal web app for INFENTRA 2.0 committee recruitment: import candidate data from a Google Form
CSV, browse candidates with Drive file previews, schedule 10-minute panel interviews, record
scores and final decisions, export results.
Deadlines: demo 2 Oct 2026, interviews 3-4 Oct 2026. Time is the main constraint: P0 scope only.

## 2. Source of truth
- `docs/PRD.md` is the single source of truth. Read it fully before every phase.
- If the PRD is silent or an item sits under "Open Questions", STOP and ask. Do not invent behaviour.
- If code and PRD conflict, report the conflict instead of choosing silently.

## 3. Stack (fixed, do not change)
- Laravel (use the version already in `composer.json`) + Filament v5 panel builder (single app, no separate frontend).
- PostgreSQL in production (Heroku). Local DB as configured in `.env`.
- Hosting: Heroku. No Vercel, no separate API/SPA.
- Code identifiers in English. UI labels in Indonesian.

## 4. Filament v5 warning
Your training data likely knows Filament v3 better. v4/v5 syntax differs (for example schemas/forms
API, namespaces, file structure). Before writing any Filament code:
1. Generate with artisan (`php artisan make:filament-resource ...`) and follow the generated shape.
2. Read the docs shipped in `vendor/filament/*/docs` if present.
3. Never paste v3-style code from memory. If unsure, check the vendor source.

## 5. Working rules
- Work in phases. After each phase: run its verification gate, report results (commands + output
  summary), commit, then STOP and wait for confirmation.
- One concern per commit. Commit message: `phase-N: short description`.
- Tests: write them for scheduling logic first (TDD). Use whichever test runner the project already has.
- No scope creep: nothing from P1/P2 in the PRD unless asked.
- No new packages without asking, except what the current phase requires.
- Never commit secrets, `.env`, or the POH seed file (see section 7).

## 6. Domain vocabulary
- **POH**: core committee (Ketua Pelaksana, SC, PIC, secretaries, treasurers, Koor).
- **Koor**: division coordinator. **Calon**: candidate (committee applicant).
- **Slot**: one 10-minute interview session. **Ishoma block**: 1-hour prayer/rest block (Dzuhur and Ashar).
- **Pilihan 1 / Pilihan 2**: the candidate's first and second division choice.
- **Placement**: final division result per candidate (Pilihan 1 has priority; manual if Pilihan 1 is not Lolos).
- **HMIF flag** (`is_hmif`): exempt from interview; gets no slot but still needs a final decision.
- Division names: "Usdakom" and "Danus & Konsum" are the same division (alias). The form also writes
  "Sponsor" where the coordinator title says "Sponsorship": treat as aliases of one division.

## 7. Data and privacy
- Candidate data (CVs, certificates, scores) is private. Only logged-in POH may see it.
- The public schedule page shows only candidate name and schedule, nothing else.
- POH NIM values are NOT in the PRD or code. Seed users from `database/seeders/data/poh.csv`,
  which must be listed in `.gitignore`.

## 8. UI
For public pages (schedule lookup, login) read `DESIGN.md` for direction, then apply the antislop
filter. Filament admin pages use Filament defaults; do not restyle them. Mobile first: interviewers
use phones.