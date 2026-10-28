# Database design

MySQL/MariaDB, InnoDB, utf8mb4. Schema: `database/schema.sql` (idempotent; applied by `php database/migrate.php`).

```mermaid
erDiagram
  users ||--o{ courses : teaches
  users ||--o{ enrollments : enrols
  courses ||--o{ enrollments : has
  courses ||--o{ lessons : contains
  lessons ||--o{ exercises : has
  users ||--o{ attempts : takes
  lessons ||--o{ attempts : for
  attempts ||--o{ attempt_answers : records
  exercises ||--o{ attempt_answers : answers
  courses ||--o{ assignments : has
  assignments ||--o{ submissions : receives
  users ||--o{ submissions : submits
  submissions ||--o{ plagiarism_reports : flagged
  courses ||--o{ class_sessions : schedules
  courses ||--o{ threads : hosts
  threads ||--o{ posts : has
  courses ||--o{ chat_messages : has
  users ||--o{ ai_interactions : makes
  users ||--o{ notifications : receives
```

Source: `docs/diagrams/erd.mmd`.

## Mapping from the thesis ERD

| Thesis table | Here |
|---|---|
| tblusers, tbllecturer, tblstudent | `users` (role column; hashed password) |
| tbllesson | `lessons` (chapter, title, category, file) |
| tblexercise | `exercises` (MCQ, answer A-D) |
| tblscore | `attempts` |
| tblstudentquestion | `attempt_answers` |
| tblautonumbers | dropped (native AUTO_INCREMENT) |
| (not in thesis ERD) | `courses`, `enrollments`, `assignments`, `submissions`, `plagiarism_reports`, `class_sessions`, `threads`, `posts`, `chat_messages`, `ai_interactions`, `notifications`, `login_attempts` |
