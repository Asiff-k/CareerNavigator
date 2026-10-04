<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

// ===== Load the Career =====
$careerId = input_int($_GET, 'id');
$stmt = $pdo->prepare("SELECT * FROM careers WHERE id = ?");
$stmt->execute([$careerId]);
$career = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$career) {
    flash('error', 'Career not found.');
    redirect('admin/careers.php');
}

$errors = [];
$back = 'admin/career_edit.php?id=' . $careerId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ===== Update Career Details =====
    if ($action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $salary = trim($_POST['average_salary'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '' || mb_strlen($title) > 150) $errors[] = 'Title is required (maximum 150 characters).';
        if (mb_strlen($category) > 100) $errors[] = 'Category must be 100 characters or fewer.';
        if (mb_strlen($salary) > 100) $errors[] = 'Salary must be 100 characters or fewer.';

        if (!$errors) {
            $stmt = $pdo->prepare("UPDATE careers SET title = ?, description = ?, category = ?, average_salary = ? WHERE id = ?");
            $stmt->execute([$title, $description, $category, $salary, $careerId]);
            flash('success', 'Career details saved.');
            redirect($back);
        }
    }

    // ===== Add or Update a Required Skill =====
    // Level is 1-3 and importance is 1-5
    if ($action === 'save_skill') {
        $skillId = input_int($_POST, 'skill_id');
        $level = input_int($_POST, 'required_level');
        $importance = input_int($_POST, 'importance');

        $check = $pdo->prepare("SELECT id FROM skills WHERE id = ?");
        $check->execute([$skillId]);
        if (!$check->fetch() || $level < 1 || $level > 3 || $importance < 1 || $importance > 5) {
            flash('error', 'Please choose a valid skill, level (1-3) and importance (1-5).');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO career_skills (career_id, skill_id, required_level, importance) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE required_level = VALUES(required_level), importance = VALUES(importance)"
            );
            $stmt->execute([$careerId, $skillId, $level, $importance]);
            flash('success', 'Required skill saved.');
        }
        redirect($back . '#skills');
    }

    // ===== Remove a Required Skill =====
    if ($action === 'remove_skill') {
        $pdo->prepare("DELETE FROM career_skills WHERE career_id = ? AND skill_id = ?")
            ->execute([$careerId, input_int($_POST, 'skill_id')]);
        flash('success', 'Skill removed from this career.');
        redirect($back . '#skills');
    }

    // ===== Link or Unlink a Course =====
    if ($action === 'add_course') {
        $courseId = input_int($_POST, 'course_id');
        $check = $pdo->prepare("SELECT id FROM courses WHERE id = ?");
        $check->execute([$courseId]);
        if ($check->fetch()) {
            $pdo->prepare("INSERT IGNORE INTO career_courses (career_id, course_id) VALUES (?, ?)")->execute([$careerId, $courseId]);
            flash('success', 'Course linked to this career.');
        } else {
            flash('error', 'Please choose a valid course.');
        }
        redirect($back . '#courses');
    }

    if ($action === 'remove_course') {
        $pdo->prepare("DELETE FROM career_courses WHERE career_id = ? AND course_id = ?")
            ->execute([$careerId, input_int($_POST, 'course_id')]);
        flash('success', 'Course unlinked.');
        redirect($back . '#courses');
    }
}

// ===== Load Data for the Page =====
$form = $errors ? $_POST : $career;

$stmt = $pdo->prepare(
    "SELECT cs.*, s.name, s.category FROM career_skills cs JOIN skills s ON s.id = cs.skill_id
     WHERE cs.career_id = ? ORDER BY cs.importance DESC, s.name"
);
$stmt->execute([$careerId]);
$requiredSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allSkills = $pdo->query("SELECT id, name, category FROM skills ORDER BY category, name")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT c.id, c.title, c.provider FROM career_courses cc JOIN courses c ON c.id = cc.course_id
     WHERE cc.career_id = ? ORDER BY c.title"
);
$stmt->execute([$careerId]);
$linkedCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT id, title FROM courses WHERE id NOT IN (SELECT course_id FROM career_courses WHERE career_id = ?) ORDER BY title"
);
$stmt->execute([$careerId]);
$otherCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Edit ' . $career['title'];
$activePage = 'careers';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <a href="careers.php" class="small">&larr; All careers</a>
        <h1 class="mt"><?= e($career['title']) ?></h1>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2>Career details</h2>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <div class="form-grid">
            <div class="form-group"><label for="title">Title</label>
                <input type="text" id="title" name="title" maxlength="150" required value="<?= e($form['title']) ?>"></div>
            <div class="form-group"><label for="category">Category</label>
                <input type="text" id="category" name="category" maxlength="100" value="<?= e($form['category']) ?>"></div>
            <div class="form-group"><label for="average_salary">Average salary</label>
                <input type="text" id="average_salary" name="average_salary" maxlength="100" value="<?= e($form['average_salary']) ?>"></div>
            <div class="form-group full"><label for="description">Description</label>
                <textarea id="description" name="description"><?= e($form['description']) ?></textarea></div>
        </div>
        <button type="submit" class="btn btn-primary">Save details</button>
    </form>
</div>

<div class="card" id="skills">
    <h2>Required skills (<?= count($requiredSkills) ?>)</h2>
    <p class="muted small">Level: 1 = Beginner, 2 = Intermediate, 3 = Advanced. Importance 1–5 is the weight used in the skill match score.</p>

    <form method="POST" class="filters">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_skill">
        <div class="form-group"><label for="skill_id">Skill</label>
            <select id="skill_id" name="skill_id" required>
                <option value="">Choose a skill…</option>
                <?php foreach ($allSkills as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['category']) ?>)</option>
                <?php endforeach; ?>
            </select></div>
        <div class="form-group"><label for="required_level">Required level</label>
            <select id="required_level" name="required_level">
                <option value="1">1 – Beginner</option><option value="2" selected>2 – Intermediate</option><option value="3">3 – Advanced</option>
            </select></div>
        <div class="form-group"><label for="importance">Importance</label>
            <select id="importance" name="importance">
                <?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= $i === 3 ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?>
            </select></div>
        <button type="submit" class="btn btn-primary">Add / update skill</button>
    </form>

    <?php if (!$requiredSkills): ?>
        <div class="empty">No required skills yet. This career will always score low until skills are added.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Skill</th><th>Required level</th><th>Importance</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($requiredSkills as $s): ?>
                    <tr>
                        <td><strong><?= e($s['name']) ?></strong><div class="small muted"><?= e($s['category']) ?></div></td>
                        <td colspan="2">
                            <form method="POST" class="btn-group">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="save_skill">
                                <input type="hidden" name="skill_id" value="<?= (int) $s['skill_id'] ?>">
                                <select name="required_level" style="width:auto" aria-label="Required level">
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                        <option value="<?= $i ?>" <?= (int) $s['required_level'] === $i ? 'selected' : '' ?>><?= level_name($i) ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select name="importance" style="width:auto" aria-label="Importance">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?= $i ?>" <?= (int) $s['importance'] === $i ? 'selected' : '' ?>>Importance <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                                <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                            </form>
                        </td>
                        <td class="text-right">
                            <form method="POST" class="inline-form" data-confirm="Remove this skill requirement?">
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

<div class="card" id="courses">
    <h2>Linked courses &amp; certifications (<?= count($linkedCourses) ?>)</h2>
    <?php if ($otherCourses): ?>
        <form method="POST" class="filters">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_course">
            <div class="form-group"><label for="course_id">Course</label>
                <select id="course_id" name="course_id" required>
                    <option value="">Choose a course…</option>
                    <?php foreach ($otherCourses as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <button type="submit" class="btn btn-primary">Link course</button>
        </form>
    <?php endif; ?>
    <?php if (!$linkedCourses): ?><p class="muted">No courses linked yet.</p><?php endif; ?>
    <ul class="list-plain">
        <?php foreach ($linkedCourses as $c): ?>
            <li class="match-top" style="margin:0">
                <span><strong><?= e($c['title']) ?></strong> <span class="small muted">· <?= e($c['provider']) ?></span></span>
                <form method="POST" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove_course">
                    <input type="hidden" name="course_id" value="<?= (int) $c['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Unlink</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
