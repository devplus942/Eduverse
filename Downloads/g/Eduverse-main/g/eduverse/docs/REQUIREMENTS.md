# Requirements and traceability

Source: the EduVerse thesis (chapters 1-5). "Status" describes the code, not verified behaviour. **No test has been executed yet** (see TESTING.md).

| ID | Documented requirement (thesis section) | Implementation | Verification | Status |
|---|---|---|---|---|
| FR1 | Role-based registration/login/logout (3.2.1, test T2, T10) | `Auth`, `AuthController`, `require_role` | `db_test` login/lockout; `http_smoke` redirects | Implemented |
| FR2 | Admin manages users (T1) | `AdminController`, `views/admin/users` | manual | Implemented |
| FR3 | Lecturers upload course materials (T8) | `LecturerController::lessonCreate`, `Upload` | `unit_test` Upload; manual upload | Implemented |
| FR4 | Student dashboard and enrolled courses (3.3.3) | `AuthController::home`, `enrollments` | `db_test` access control | Implemented |
| FR5 | Class scheduling and notifications | `class_sessions`, `Notifier` (DB + mail log) | manual | Partial: no SMTP |
| FR6 | Virtual classroom, Jitsi (T6) | `Jitsi` link, opens new tab | `unit_test` Jitsi; manual | Implemented (link only) |
| FR7 | Quizzes with auto-grading (T4) | `Grader`, quiz routes | `unit_test` Grader | Implemented |
| FR8 | Assignment submission (T3) | `StudentController::assignmentSubmit` | manual | Implemented |
| FR9 | AI essay grading/feedback (T5) | `AiService`, `aiFeedback` (advisory) | `ai_test`, `db_test` | Implemented (typed text, .txt, .docx) |
| FR10 | AI chatbot tutor (T7) | `AiController` | `ai_test`, `db_test` | Implemented |
| FR11 | Adaptive recommendations | `Recommender` (rule-based) | `unit_test` | Implemented |
| FR12 | Plagiarism detection (scope 1.5) | `Plagiarism` (n-gram overlap), `TextExtract` | `unit_test` | Implemented (typed text, .txt, .docx; no PDF) |
| FR16 | AI reply suggestions (thesis evaluation suggestion) | `AiController::suggestReply` | manual | Implemented (draft only) |
| FR13 | Discussion forums and live chat | `CollabController` (polling) | manual | Implemented |
| FR14 | Analytics and reports (T9) | `Analytics`, printable report (`/lecturer/report`, browser Print to PDF), CSV export | `db_test`; manual | Implemented (no server-side PDF/.xlsx) |
| FR17 | Updater (maintenance; added) | `Schema`, `database/migrate.php`, `database/migrations/` | `db_test` | Implemented (database only; no code self-update) |
| FR15 | Text-to-speech / speech-to-text | Web Speech API in `app.js` | manual (browser-dependent) | Implemented |
| NFR1 | Security: SQLi, XSS, sessions, passwords (3.2.2) | PDO prepared statements, `e()`, CSRF, `password_hash` | `unit_test`, `db_test`, `http_smoke` | Implemented |
| NFR2 | Responsive, accessible UI, dark mode, font scaling | `app.css`, `app.js` | manual checklist | Implemented |
| NFR3 | Scalability, 99% uptime (3.2.2) | none | cannot be verified locally | **Not claimed** |
| NFR4 | HTTPS in deployment | deployment configuration | n/a | Out of code scope |

## Documented but not built

PHPMailer SMTP e-mail; server-side PDF/.xlsx generation (printable report and CSV provided); PDF text extraction; AI group formation (literature review/future work only); 2FA, audit logs, PWA/offline, multilingual and mobile app (thesis section 5.3 recommendations).

## Decisions where the thesis is silent or inconsistent

1. The thesis ERD (users, lecturer, student, lesson, exercise, score, studentquestion) lacks courses, enrollments, assignments, submissions, schedules, forums and plagiarism. Tables were added for the features the thesis text describes; `tblusers/tblstudent/tbllecturer` were merged into `users` with a role.
2. Plaintext-style password columns (`PASS`, `STUDPASS`, `LECPASS`) were replaced by one hashed column.
3. The thesis names no Hugging Face model; the model is configuration (`HF_MODEL`).
4. Plagiarism and AI essay feedback work on typed answers and text extracted from .txt/.docx; PDF extraction is not implemented.
5. Students may self-enroll in any course. Late submissions are blocked after the deadline.
6. The thesis evaluation figures (e.g. chatbot accuracy) have no stated method and are **not** reproduced here as results of this rebuild.
