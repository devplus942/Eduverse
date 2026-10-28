# Security notes

Implemented in code (not yet independently tested or audited):

- Passwords hashed with `password_hash`; login lockout after 5 failures per email in 15 minutes; session ID regenerated on login; cookies HttpOnly + SameSite=Lax.
- All SQL uses PDO prepared statements (native, not emulated).
- Output escaped with `e()`; chat messages inserted with `textContent`; Content-Security-Policy forbids inline scripts.
- CSRF token required on every POST (also via `X-CSRF-Token` for chat).
- Authorisation checks per course (`Access`): students see only enrolled courses, lecturers manage only their own, admin everything.
- Uploads: extension whitelist, size limit, random stored names, stored outside the web root, served as attachments with `nosniff` after an access check; path traversal guarded with `realpath`.
- CSV export neutralises spreadsheet formulas.
- Secrets only in `.env` (git-ignored).

Known gaps: no HTTPS configuration (use TLS in deployment and the `secure` cookie flag follows `$_SERVER['HTTPS']`); no file-content scanning (extension check only); no e-mail verification or password reset; no 2FA; no per-IP rate limiting; no automated dependency/security scanning; not penetration tested. Do not describe this as production-ready.
