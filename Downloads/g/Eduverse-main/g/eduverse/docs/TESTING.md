# Testing

## Honest status

The code was written in an environment with **no PHP interpreter and no MySQL**, so **no test has been run**. The only check performed was a rough bracket-balance heuristic, which is not a syntax check. Expect to fix small errors on first run.

## How to run (Windows, from the project folder)

```powershell
php -l src\helpers.php            # syntax check; or for all files:
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
php tests\run.php
php -d upload_max_filesize=20M -d post_max_size=25M -S 127.0.0.1:8000 -t public public/index.php   # window 1
php tests\http_smoke.php          # window 2
```

## Continuous integration

`.github/workflows/ci.yml` runs `php -l` on every file, `php tests/run.php` against a MySQL 8 service, and the HTTP smoke test. After pushing, open the repository's **Actions** tab; a green run is real evidence, a red one shows exactly what to fix.

## Static audit

`python tools/static_audit.py` cross-checks, without running PHP: class/file names for the autoloader, every `Class::method` reference, every route to controller method, every `view()` target, every link/form/redirect/fetch to a route, view variables against controller data, INSERT/UPDATE columns against `schema.sql`, and view array keys against known columns. It is also run in CI. It proves reference consistency only; it does not execute any PHP.

## What the tests cover

| File | Needs | Covers |
|---|---|---|
| `tests/unit_test.php` | PHP only | grading, plagiarism, recommender, upload validation, CSV safety, escaping, Jitsi links, CSRF |
| `tests/ai_test.php` | PHP only | Hugging Face success, 401, 429, 5xx, timeout, malformed/empty response, token not leaked, mock labelling, provider selection |
| `tests/db_test.php` | MySQL | password hashing, duplicate/invalid registration, login/lockout/disabled, course access control, analytics, unique submissions, cascades, AI logging, failure handling, hourly limit |
| `tests/http_smoke.php` | running server | unauthenticated redirects, CSRF rejection (419), 404, security headers |

DB tests create and drop only a database named `eduverse_test`. Without MySQL they are reported SKIPPED, never PASSED.

## Not automated (manual checklist)

Registration/login flows in a browser; lecturer creates course, lesson (with file), quiz, assignment, class; student enrols, takes quiz, submits; grading and AI feedback in mock and real mode; plagiarism run with two similar answers; forum and chat between two browsers; dark mode, text size and keyboard navigation at phone width; speech features (browser-dependent).

## Record your results

Paste the real output of the commands above into `docs/DEVELOPMENT_LOG.md` once you have run them.
