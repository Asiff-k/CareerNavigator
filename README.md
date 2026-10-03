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
4. Open http://localhost/CareerNavigator/

## Demo accounts

| Role    | Email                         | Password   |
|---------|-------------------------------|------------|
| Student | student@careernavigator.com   | student123 |
| Advisor | advisor@careernavigator.com   | advisor123 |
| Admin   | admin@careernavigator.com     | admin123   |

New students can register at `register.php`. Advisor and admin accounts are created by an admin (Admin → Users).

## Project structure

```
config/db.php              PDO database connection
includes/init.php          session + database + helpers (included by every page)
includes/functions.php     escaping, auth/role checks, CSRF, flash messages
includes/recommendation.php  the scoring algorithm
includes/header.php, footer.php  shared layout and role-based sidebar
assets/css/style.css, assets/js/app.js
index.php, register.php, login.php, logout.php
dashboard.php, profile.php, projects.php          student
recommendations.php, roadmap.php, courses.php, interview.php
advisor/index.php, advisor/student.php            advisor
admin/index.php, users.php, careers.php, career_edit.php, skills.php, courses.php, questions.php
database/schema_additions.sql, seed.php, careernavigator.sql
```

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

- Passwords hashed with `password_hash()` / checked with `password_verify()`
- All queries use PDO prepared statements
- Output escaped with `htmlspecialchars()` (function `e()`)
- CSRF token on every form; session ID regenerated at login; HttpOnly session cookie
- Role checks on every page (`require_role()`); students can only change their own data
- `.htaccess` blocks web access to `config/`, `includes/` and `database/`

## Database changes

The original 11 tables were kept unchanged. Two tables were added (`database/schema_additions.sql`):
- `advisor_notes`: feedback advisors write for students
- `interview_attempts`: students' practice answers and scores
