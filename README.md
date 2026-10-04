# CareerNavigator

An intelligent career recommendation platform using student profile analysis.
Built with PHP 8.2, MySQL/MariaDB (PDO), HTML5, CSS3 and vanilla JavaScript.

## Setup

1. Start Apache and MySQL in XAMPP.
2. Put the project in `C:\xampp\htdocs\CareerNavigator`.
3. Database (choose one):
   - Import `database/careernavigator.sql` in phpMyAdmin (creates the database with all data), **or**
   - If the `careernavigator` database with the original 11 tables already exists, run
     `C:\xampp\php\php.exe database\seed.php` (adds 2 extra tables, sample data and demo accounts).
4. Install PHPMailer: `composer install`
5. If your database existed before email verification was added, run
   `C:\xampp\php\php.exe database\migrate_email_verification.php` (existing accounts are marked as verified).
6. Email (SMTP): fill in `config/mail.local.php` (git-ignored; copy `config/mail.local.example.php` if it is missing).
   For Gmail use your address as `username`/`from_email` and a 16-character
   [App Password](https://myaccount.google.com/apppasswords) as `password`.
   Environment variables `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_USERNAME`, `SMTP_PASSWORD`,
   `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` and `APP_URL` override the file.
7. Open http://localhost/CareerNavigator/

## Demo accounts

| Role    | Email                         | Password   |
|---------|-------------------------------|------------|
| Student | student@careernavigator.com   | student123 |
| Advisor | advisor@careernavigator.com   | advisor123 |
| Admin   | admin@careernavigator.com     | admin123   |

New students can register at `register.php`. Advisor and admin accounts are created by an admin (Admin → Users).

## Email verification

- Registration emails a verification link; the account cannot sign in until the link is opened.
- The link holds a random 64-character token; only its SHA-256 hash is stored. It expires after 24 hours
  and works once. Requesting a new link (`resend_verification.php`, max. once per minute) invalidates the old one.
- Demo accounts and accounts created by an admin are verified automatically. Admins can also
  mark a user as verified in Admin → Users.
- If SMTP is not configured or sending fails, the user is told the email could not be sent (details go to the PHP error log).

## Project structure

```
config/db.php              PDO database connection
config/mail.php            email settings (reads config/mail.local.php or environment variables)
includes/init.php          session + database + helpers (included by every page)
includes/functions.php     escaping, auth/role checks, CSRF, flash messages
includes/mailer.php        sending email with PHPMailer, verification tokens
includes/recommendation.php  the scoring algorithm
includes/header.php, footer.php  shared layout and role-based sidebar
includes/auth_header.php, auth_footer.php  layout of the sign-in / registration pages
assets/css/style.css, assets/js/app.js
index.php, register.php, login.php, logout.php
verify_email.php, resend_verification.php         email verification
dashboard.php, profile.php, projects.php          student
recommendations.php, roadmap.php, courses.php, interview.php
advisor/index.php, advisor/student.php            advisor
admin/index.php, users.php, careers.php, career_edit.php, skills.php, courses.php, questions.php
database/schema_additions.sql, seed.php, careernavigator.sql, migrate_email_verification.php
```

## Troubleshooting

If a page shows "Something went wrong", the details are written to the PHP error log
(`C:\xampp\apache\logs\error.log`). Email sending errors are logged there too, starting with "CareerNavigator mail".

## Recommendation algorithm (includes/recommendation.php)

For every career the student gets a compatibility score from 0 to 100:

```
compatibility = 0.60 × Skill match + 0.20 × Interest match + 0.15 × Project evidence + 0.05 × Academic
```

- **Skill match**: proficiency is converted to numbers (Beginner 1, Intermediate 2, Advanced 3).
  For each skill the career requires: `credit = min(student level ÷ required level, 1)`.
  Each credit is weighted by the skill's importance (1–5):
  `skill match = Σ(importance × credit) ÷ Σ(importance) × 100`.
- **Interest match**: student interests found (as whole words) in the career's title, category,
  description or required skills. 1 match = 50, 2+ matches = 100.
- **Project evidence**: percentage of the career's required skills that appear in the student's
  project titles, descriptions or technologies (with simple aliases, e.g. MySQL → SQL).
- **Academic**: CGPA ÷ 4.00 × 100.

The skill gap compares each required skill: **Met** (level ≥ required), **Improve** (has it, below required)
or **Missing**. The roadmap lists missing skills first (most important first), then skills to improve,
each with matching courses from the database, followed by portfolio, interview and certification steps.

Interview practice scores an answer by the percentage of key terms from the model answer that it contains.

## Security measures

- Passwords (at least 8 characters) hashed with `password_hash()` / checked with `password_verify()`
- Every query that uses user input is a PDO prepared statement
- Output escaped with `htmlspecialchars()` (function `e()`)
- CSRF token on every form; session ID regenerated at login and logout; HttpOnly session cookie; strict session mode
- Role checks on every page (`require_role()`); students can only change their own data
- The signed-in user is reloaded from the database on every request, so role changes and deleted accounts apply immediately
- PHP error details are logged instead of shown to visitors
- `.htaccess` blocks web access to `config/`, `includes/`, `database/`, `vendor/`, `.git` and the Composer files
- Email verification required before sign-in; SMTP credentials kept out of Git (`config/mail.local.php`)

## Database changes

The original 11 tables were kept unchanged. Two tables were added (`database/schema_additions.sql`):
- `advisor_notes`: feedback advisors write for students
- `interview_attempts`: students' practice answers and scores

Email verification added four columns to `users` (`database/migrate_email_verification.php`):
`email_verified_at`, `verification_token_hash` (unique), `verification_expires_at`, `verification_sent_at`.
