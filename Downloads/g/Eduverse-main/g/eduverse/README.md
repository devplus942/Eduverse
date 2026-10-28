# EduVerse

An AI-powered collaborative learning platform for lecturers, students and administrators, rebuilt from the documentation of the final-year project *"EduVerse: A Customization of an AI-Powered Collaborative Learning Platform Solution for Al-Qalam University Katsina (AUK)"* (B.Sc. Software Engineering, 2025).

> **Status: reconstruction, not yet executed.** The original source code was lost. This code was written from the thesis documentation. It was produced in an environment without PHP or MySQL, so **none of it has been run yet**. See [docs/TESTING.md](docs/TESTING.md) and run the checks below before trusting or demonstrating anything. Treat this README's feature list as "implemented in code", not "verified working".

## Features (as in the thesis scope)

- Roles: student (self-registration), lecturer and admin (created by an admin); role-based access
- Courses, enrollment, lessons with file upload, per-course dashboards
- Multiple-choice quizzes with automatic grading and attempt history
- Assignments with deadlines, typed and/or file submissions, lecturer grading
- AI study tutor chatbot and AI essay feedback (advisory; lecturer gives the final grade)
- Local plagiarism check (word 3-gram overlap) on typed answers and text extracted from .txt/.docx uploads
- AI-suggested forum replies (draft only; the user edits and posts)
- Rule-based study recommendations and learning analytics; printable course report (browser Print / Save as PDF) and CSV export (opens in Excel)
- Discussion forums and polling-based live chat per course
- Class scheduling with Jitsi Meet links; in-app notifications and a development mail log
- Dark mode, adjustable text size, keyboard-friendly UI, browser text-to-speech / speech-to-text

**Not implemented:** SMTP e-mail (PHPMailer), server-side PDF/.xlsx generation (use Print to PDF / CSV), PDF text extraction, AI group formation, 2FA, offline mode. Details in [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md).

## Stack

PHP 8.1+ (no framework, no Composer packages), MySQL/MariaDB via PDO, HTML/CSS/vanilla JS, Hugging Face chat-completions API (optional), Jitsi Meet (links).

## Setup on Windows with XAMPP (Apache + MySQL)

Prerequisites: [XAMPP](https://www.apachefriends.org/) with **PHP 8.1 or newer** (the app checks this at startup). In the XAMPP control panel start **Apache** and **MySQL**. `pdo_mysql`, `mbstring` and `curl` are enabled by default in XAMPP.

1. Copy this folder to `C:\xampp\htdocs\eduverse`.
2. Open PowerShell there and run:

```powershell
$env:Path += ";C:\xampp\php"
php -v
copy .env.example .env
notepad .env          # set APP_BASE_PATH=/eduverse, ADMIN_EMAIL, ADMIN_PASSWORD (placeholders are refused), DB_PASS if set
php database\migrate.php        # creates the database and tables; also the UPDATER (re-run after every update)
php database\seed_admin.php     # creates the first admin from .env (no other data is seeded)
```

3. Browse to **http://localhost/eduverse/** and log in as the admin. Create lecturers under **Users**; students register themselves.

The root `.htaccess` routes requests into `public/` and blocks `src/`, `views/`, `storage/`, `database/`, `tests/`, `.env` and dotfiles. Upload limits (20 MB) are set in `public/.htaccess` (works with XAMPP's mod_php).

### Alternative: PHP development server (no Apache)

```powershell
# leave APP_BASE_PATH empty in .env
php -d upload_max_filesize=20M -d post_max_size=25M -S 127.0.0.1:8000 -t public public/index.php
```

### Updating an existing install

Replace the files (keep `.env` and `storage/`), then run `php database\migrate.php`. It applies the idempotent baseline and any new `database/migrations/*.sql` files exactly once each, recording them in `schema_migrations`.

### AI configuration

With no token, the app runs in **MOCK mode**: every AI reply is a fixed placeholder labelled "MOCK - not AI generated". For real answers, create a Hugging Face token and set `HF_API_TOKEN` and `HF_MODEL` (a chat model available to your account through Inference Providers) in `.env`. Free-tier limits and model availability change; check Hugging Face's current documentation. See [docs/AI_DESIGN.md](docs/AI_DESIGN.md).

## Tests

On GitHub, `.github/workflows/ci.yml` runs syntax checks, all tests (with MySQL) and the smoke test on every push. Check the Actions tab: that is your first real verification.

```powershell
python tools\static_audit.py     # cross-reference audit (needs Python; optional locally)
php tests\run.php                 # unit + AI tests; DB tests run against a throw-away database eduverse_test
# second window, with the server running:
php tests\http_smoke.php
```

## Troubleshooting

- `could not find driver`: enable `pdo_mysql` in `php.ini`, restart the terminal.
- `Database error ... refused`: MySQL is not started in XAMPP, or `DB_*` in `.env` is wrong.
- Uploads fail: raise `upload_max_filesize` / `post_max_size` (the command above does this for the dev server).
- Links go to a wrong address or CSS is missing: `APP_BASE_PATH` in `.env` must match the folder (`/eduverse`), or be empty for `php -S`.
- 403/404 on every page under XAMPP: make sure `mod_rewrite` is enabled and `AllowOverride All` is set for `htdocs` (XAMPP default).
- Blank 500 page: see `storage/logs/app.log`; set `APP_ENV=development` for detailed messages.

## Documentation

[Overview](docs/PROJECT_OVERVIEW.md) | [Requirements and traceability](docs/REQUIREMENTS.md) | [Architecture](docs/ARCHITECTURE.md) | [Database](docs/DATABASE_DESIGN.md) | [AI design](docs/AI_DESIGN.md) | [Testing](docs/TESTING.md) | [Security](docs/SECURITY.md) | [User guide](docs/USER_GUIDE.md) | [Development log](docs/DEVELOPMENT_LOG.md)

## Licence

No licence has been chosen yet. Until one is added, all rights are reserved. Choose deliberately (and check whether AUK or your supervisor holds any rights over the thesis work) before publishing.
