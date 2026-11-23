# EduQuest Beta Implementation Plan

> Implement in the approved workspace with executing-plans and test-driven-development. Track each task from failing test through verification.

**Goal** — Ship a locally runnable beta covering the full quiz and review journey.

**Architecture** — A PHP front controller renders templates and delegates quiz mutations to a PostgreSQL service. Tailwind supplies compiled CSS. JavaScript enhances answer saving, countdown and dialogs while normal form submissions remain available.

**Stack** — PHP 8.2+, PDO_PGSQL, PostgreSQL, Tailwind CSS and vanilla JavaScript.

**Spec** — [EduQuest design](../specs/2026-11-23-eduquest-design.md)

## Constraints

- Desktop-first Modern Scholastic, with narrow-screen reflow and visible keyboard focus.
- One server-controlled timer per attempt. Two synthetic demo accounts. Results use the exact question version attempted.
- Tutor, profile editing, community and mentor are labeled placeholders. Supabase and Vercel are future deployment targets.
- Workspace has no Git metadata. Run inline in the approved directory and record evidence in this plan.

## Review focus

- Refresh, timeout and late writes preserve only saved answers.
- Duplicate start or submit requests respect attempt limits and produce consistent results.
- A second user cannot read or mutate the first user's attempts.
- An option belonging to another question cannot be saved.
- Browser save failure retains input and allows retry before deadline.

## Tasks

- [x] **1. Persistence and quiz service.** Add `tests/quiz.php` exercising real PostgreSQL through `QuizService` in `app/QuizService.php`. Assert a resumable attempt, rounded score, empty-answer handling, ownership, invalid options, duplicate submission, expiry and retry limits. Run it to verify the missing service failure. Add `database/001_schema.sql`, `database/002_demo.sql`, connection configuration, and the service. Run the suite against a temporary database.
- [x] **2. HTTP and UI.** Add `tests/http.mjs` to exercise login and CSRF, dashboard, catalogue/search, quiz brief, submit and review, and placeholder routes over HTTP. Observe the missing route failure. Implement `public/index.php`, `app/Http.php`, and PHP templates in `views/`. Compile Tailwind from `assets/app.css`. Re-run the integration suite.
- [x] **3. Browser and release checks.** Add a browser flow proving saved state, failure recovery, dialog behavior, responsive layout and keyboard use. Verify RED for the missing enhancement, implement `public/assets/app.js`, then re-run browser and backend suites. Document local setup, demo accounts, and deployment boundaries in `README.md`. Run PHP lint, clean asset build, visual checks and a fresh review.

## Verification

- Observed the expected failing tests for the missing service, HTTP entry point, and browser enhancement.
- Reproduced and fixed the review findings for late writes, historical option labels, native confirmation position, lost save acknowledgements, and current unanswered counts across tabs.
- `npm test` passed against a disposable PostgreSQL database and Chrome. `npm run build`, PHP lint, and the Hallmark render check passed. The local beta and its assets responded over HTTP.
