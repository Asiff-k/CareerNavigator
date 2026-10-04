<?php
require_once 'includes/init.php';
require_role('student');

$userId = current_user_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ===== Save Academic Information =====
    if ($action === 'save_profile') {
        $name = trim($_POST['name'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $cgpaInput = trim($_POST['cgpa'] ?? '');
        $interests = trim($_POST['interests'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Name is required (maximum 100 characters).';
        if (mb_strlen($university) > 150) $errors[] = 'University must be 150 characters or fewer.';
        if (mb_strlen($department) > 100) $errors[] = 'Department must be 100 characters or fewer.';
        if (mb_strlen($semester) > 50) $errors[] = 'Semester must be 50 characters or fewer.';
        if (mb_strlen($interests) > 1000) $errors[] = 'Interests must be 1000 characters or fewer.';
        if (mb_strlen($bio) > 2000) $errors[] = 'Bio must be 2000 characters or fewer.';

        $cgpa = null;
        if ($cgpaInput !== '') {
            if (!is_numeric($cgpaInput) || $cgpaInput < 0 || $cgpaInput > 4) {
                $errors[] = 'CGPA must be a number between 0.00 and 4.00.';
            } else {
                $cgpa = round((float) $cgpaInput, 2);
            }
        }

        if (!$errors) {
            $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$name, $userId]);
            $_SESSION['user']['name'] = $name;

            // Create the profile, or update it if it already exists
            $stmt = $pdo->prepare(
                "INSERT INTO student_profiles (user_id, university, department, semester, cgpa, interests, bio)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE university = VALUES(university), department = VALUES(department),
                     semester = VALUES(semester), cgpa = VALUES(cgpa), interests = VALUES(interests), bio = VALUES(bio)"
            );
            $stmt->execute([$userId, $university, $department, $semester, $cgpa, $interests, $bio]);
            flash('success', 'Profile saved.');
            redirect('profile.php');
        }
    }

    // ===== Add or Update a Skill =====
    if ($action === 'add_skill' || $action === 'update_skill') {
        $skillId = input_int($_POST, 'skill_id');
        $proficiency = $_POST['proficiency'] ?? '';

        $check = $pdo->prepare("SELECT id FROM skills WHERE id = ?");
        $check->execute([$skillId]);
        if (!$check->fetch()) {
            $errors[] = 'Please choose a valid skill.';
        } elseif (!isset(LEVELS[$proficiency])) {
            $errors[] = 'Please choose a valid proficiency level.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO student_skills (user_id, skill_id, proficiency) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE proficiency = VALUES(proficiency)"
            );
            $stmt->execute([$userId, $skillId, $proficiency]);
            flash('success', $action === 'add_skill' ? 'Skill added.' : 'Skill level updated.');
            redirect('profile.php#skills');
        }
    }

    // ===== Remove a Skill =====
    if ($action === 'remove_skill') {
        $stmt = $pdo->prepare("DELETE FROM student_skills WHERE user_id = ? AND skill_id = ?");
        $stmt->execute([$userId, input_int($_POST, 'skill_id')]);
        flash('success', 'Skill removed.');
        redirect('profile.php#skills');
    }

    // ===== Change Password =====
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $errors[] = 'Your current password is incorrect.';
        } elseif (strlen($new) < MIN_PASSWORD_LENGTH) {
            $errors[] = 'The new password must contain at least ' . MIN_PASSWORD_LENGTH . ' characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'The new passwords do not match.';
        } else {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            flash('success', 'Password changed.');
            redirect('profile.php');
        }
    }
}

// ===== Load Data for the Page =====
$stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// If validation failed, show what the user typed.
if ($errors && ($_POST['action'] ?? '') === 'save_profile') {
    $profile = array_merge($profile, array_intersect_key($_POST, array_flip(['university', 'department', 'semester', 'cgpa', 'interests', 'bio'])));
}

$stmt = $pdo->prepare(
    "SELECT ss.skill_id, ss.proficiency, s.name, s.category
     FROM student_skills ss JOIN skills s ON s.id = ss.skill_id
     WHERE ss.user_id = ? ORDER BY s.category, s.name"
);
$stmt->execute([$userId]);
$mySkills = $stmt->fetchAll(PDO::FETCH_ASSOC);
$mySkillIds = array_column($mySkills, 'skill_id');

// Skills the student has not added yet, grouped by category for the dropdown
$available = [];
foreach ($pdo->query("SELECT id, name, category FROM skills ORDER BY category, name") as $s) {
    if (!in_array($s['id'], $mySkillIds)) {
        $available[$s['category'] ?: 'Other'][] = $s;
    }
}

$suggestedInterests = ['Web Development', 'Data', 'Artificial Intelligence', 'Machine Learning', 'Security',
    'Design', 'Cloud', 'Mobile', 'Testing', 'Business', 'Networking', 'Automation'];

$pageTitle = 'My Profile & Skills';
$activePage = 'profile';
require 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>My Profile &amp; Skills</h1>
        <p>Your academic details, interests and skills are used to calculate your career matches.</p>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2>Academic information</h2>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_profile">
        <div class="form-grid">
            <div class="form-group">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" maxlength="100" required
                       value="<?= e($_POST['name'] ?? current_user()['name']) ?>">
            </div>
            <div class="form-group">
                <label for="university">University</label>
                <input type="text" id="university" name="university" maxlength="150" value="<?= e($profile['university'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department" maxlength="100" placeholder="e.g. Computer Science and Engineering"
                       value="<?= e($profile['department'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="semester">Semester / Year</label>
                <input type="text" id="semester" name="semester" maxlength="50" placeholder="e.g. 6th Semester"
                       value="<?= e($profile['semester'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="cgpa">CGPA (out of 4.00)</label>
                <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" max="4" value="<?= e($profile['cgpa'] ?? '') ?>">
            </div>
            <div class="form-group full">
                <label for="interests">Interests</label>
                <input type="text" id="interests" name="interests" maxlength="1000" placeholder="Separate interests with commas"
                       value="<?= e($profile['interests'] ?? '') ?>">
                <div class="help">Click a suggestion to add it:</div>
                <div class="chips mt">
                    <?php foreach ($suggestedInterests as $interest): ?>
                        <button type="button" class="chip" data-interest="<?= e($interest) ?>">+ <?= e($interest) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group full">
                <label for="bio">Short bio</label>
                <textarea id="bio" name="bio" maxlength="2000" placeholder="Tell advisors a little about your goals"><?= e($profile['bio'] ?? '') ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save profile</button>
    </form>
</div>

<div class="card" id="skills">
    <div class="card-header">
        <h2>My skills (<?= count($mySkills) ?>)</h2>
    </div>

    <?php if ($available): ?>
        <form method="POST" class="filters">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_skill">
            <div class="form-group">
                <label for="skill_id">Skill</label>
                <select id="skill_id" name="skill_id" required>
                    <option value="">Choose a skill…</option>
                    <?php foreach ($available as $category => $list): ?>
                        <optgroup label="<?= e($category) ?>">
                            <?php foreach ($list as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="proficiency">Proficiency</label>
                <select id="proficiency" name="proficiency" required>
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add skill</button>
        </form>
    <?php endif; ?>

    <?php if (!$mySkills): ?>
        <div class="empty">You have not added any skills yet.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Skill</th><th>Category</th><th>Proficiency</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($mySkills as $s): ?>
                    <tr>
                        <td><strong><?= e($s['name']) ?></strong></td>
                        <td class="muted"><?= e($s['category']) ?></td>
                        <td>
                            <form method="POST" class="btn-group">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_skill">
                                <input type="hidden" name="skill_id" value="<?= (int) $s['skill_id'] ?>">
                                <select name="proficiency" style="width:auto">
                                    <?php foreach (LEVELS as $level => $n): ?>
                                        <option value="<?= $level ?>" <?= $s['proficiency'] === $level ? 'selected' : '' ?>><?= ucfirst($level) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                            </form>
                        </td>
                        <td class="text-right">
                            <form method="POST" class="inline-form" data-confirm="Remove this skill?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove_skill">
                                <input type="hidden" name="skill_id" value="<?= (int) $s['skill_id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Change password</h2>
    <form method="POST" data-password-match>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="form-grid">
            <div class="form-group full">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New password</label>
                <input type="password" id="new_password" name="new_password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm new password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-secondary">Change password</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>
