# Development log

## Source of truth
Thesis PDF `EDUVERSE-AUK.pdf` (87 pages). Original code lost. Phase 1 analysis found the thesis ERD incomplete relative to its text; see REQUIREMENTS.md decisions.

## Milestones
1. Schema, config, core helpers, auth and roles: written
2. Courses, lessons, uploads, enrollment, quizzes, assignments: written
3. AI service layer (Hugging Face + labelled mock), chatbot, essay feedback: written
4. Plagiarism, recommendations, analytics, forum, chat, Jitsi, notifications: written
5. Tests and documentation: written
6. CI workflow, .txt/.docx text extraction, AI reply suggestions: written (also not yet executed)

## Verification record
- 2026: Code authored in a sandbox without PHP/MySQL and without network. **No PHP syntax check, test run or browser run has happened.** Only a rough bracket-count heuristic was applied.
- Run the commands in TESTING.md and record real results below.

| Date | Command | Result |
|---|---|---|
| | `php -l` on all files | |
| | `php tests\run.php` | |
| | `php tests\http_smoke.php` | |
| | Manual checklist | |

## Review pass (no execution)
Static read-through found and fixed: CSRF check accepted an empty token when the session had none (`hash_equals('', '')` is true); `seed_admin.php` now refuses the placeholder credentials from `.env.example`. A regression test was added. Not executed.

## Architecture audit (static, no execution)
Method: `tools/static_audit.py` over all 26 classes, 42 routes, 19 views, 18 tables, plus a manual review of every call to a non-project function (all are PHP built-ins available in 8.1 with `pdo_mysql`/`mbstring`; `curl` and `zip` are optional and guarded).
Found and repaired:
1. **Sub-folder installs broken:** every link, form, redirect, asset and fetch was root-relative, so `htdocs/eduverse` would fail. Added `APP_BASE_PATH`, `url()`, prefixing `redirect()`, a `<meta name="base">` for JS, a root `.htaccess` for XAMPP and deny rules for private folders.
2. **Duplicated schema logic** in `migrate.php` and the test runner: replaced by `Schema`.
3. **No updater:** added tracked migrations (`schema_migrations`, `database/migrations/`).
4. **No PDF-style reporting:** added the printable report page.
5. **No environment check:** PHP >= 8.1, `pdo_mysql` and `mbstring` are verified at startup with a clear message.
6. Hardening: strict session mode, session cookie path, HSTS over HTTPS.
No missing classes, methods, views, routes or SQL columns were found by the auditor. Nothing was executed.

## Known limitations
No SMTP e-mail; no PDF/Excel reports; no PDF text extraction; polling chat; no HTTPS config; no performance/load testing; thesis scalability and uptime claims not evaluated.
