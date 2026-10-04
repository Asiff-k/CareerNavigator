<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = input_int($_POST, 'id');

    // ===== Add or Update a Course =====
    if ($action === 'save') {
        $title = trim($_POST['title'] ?? '');
        $provider = trim($_POST['provider'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $courseUrl = trim($_POST['course_url'] ?? '');
        $skillId = input_int($_POST, 'skill_id') ?: null;
        $careerIds = array_map('intval', (array) ($_POST['career_ids'] ?? []));

        if ($title === '' || mb_strlen($title) > 200) $errors[] = 'Title is required (maximum 200 characters).';
        if (mb_strlen($provider) > 150) $errors[] = 'Provider must be 150 characters or fewer.';
        if ($courseUrl !== '' && (!filter_var($courseUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $courseUrl) || strlen($courseUrl) > 255)) {
            $errors[] = 'Course link must be a valid URL starting with http:// or https://';
        }
        if ($skillId) {
            $check = $pdo->prepare("SELECT id FROM skills WHERE id = ?");
            $check->execute([$skillId]);
            if (!$check->fetch()) $errors[] = 'Please choose a valid skill.';
        }

        if (!$errors) {
            $pdo->beginTransaction();
            if ($id) {
                $stmt = $pdo->prepare("UPDATE courses SET title = ?, provider = ?, description = ?, course_url = ?, skill_id = ? WHERE id = ?");
                $stmt->execute([$title, $provider, $description, $courseUrl ?: null, $skillId, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO courses (title, provider, description, course_url, skill_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $provider, $description, $courseUrl ?: null, $skillId]);
                $id = (int) $pdo->lastInsertId();
            }
            // Replace the course's career links with the ticked careers
            $pdo->prepare("DELETE FROM career_courses WHERE course_id = ?")->execute([$id]);
            $link = $pdo->prepare("INSERT IGNORE INTO career_courses (career_id, course_id) SELECT id, ? FROM careers WHERE id = ?");
            foreach ($careerIds as $careerId) {
                $link->execute([$id, $careerId]);
            }
            $pdo->commit();
            flash('success', 'Course saved.');
            redirect('admin/courses.php');
        }
    }

    // ===== Delete a Course =====
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', $stmt->rowCount() ? 'Course deleted.' : 'Course not found.');
        redirect('admin/courses.php');
    }
}

// ===== Course Being Edited =====
$editing = null;
$editingCareerIds = [];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([input_int($_GET, 'edit')]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($editing) {
        $stmt = $pdo->prepare("SELECT career_id FROM career_courses WHERE course_id = ?");
        $stmt->execute([$editing['id']]);
        $editingCareerIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
$form = $errors ? $_POST : ($editing ?? []);
$checkedCareers = $errors ? array_map('intval', (array) ($_POST['career_ids'] ?? [])) : $editingCareerIds;

// ===== Load Data for the Page =====
$skills = $pdo->query("SELECT id, name FROM skills ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$careers = $pdo->query("SELECT id, title FROM careers ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
$courses = $pdo->query(
    "SELECT c.*, s.name AS skill_name, COUNT(cc.id) AS career_count
     FROM courses c LEFT JOIN skills s ON s.id = c.skill_id
     LEFT JOIN career_courses cc ON cc.course_id = c.id
     GROUP BY c.id ORDER BY c.title"
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Courses';
$activePage = 'courses';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Courses &amp; Certifications</h1>
        <p>Each course teaches one skill and can be linked to several careers. Roadmaps use these links.</p>
    </div>
    <a href="../courses.php" class="btn btn-secondary">View as catalogue</a>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2><?= !empty($form['id']) ? 'Edit course' : 'Add a course' ?></h2>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($form['id'] ?? 0) ?>">
        <div class="form-grid">
            <div class="form-group"><label for="title">Title</label>
                <input type="text" id="title" name="title" maxlength="200" required value="<?= e($form['title'] ?? '') ?>"></div>
            <div class="form-group"><label for="provider">Provider</label>
                <input type="text" id="provider" name="provider" maxlength="150" value="<?= e($form['provider'] ?? '') ?>"></div>
            <div class="form-group"><label for="course_url">Link</label>
                <input type="url" id="course_url" name="course_url" maxlength="255" placeholder="https://" value="<?= e($form['course_url'] ?? '') ?>"></div>
            <div class="form-group"><label for="skill_id">Skill taught</label>
                <select id="skill_id" name="skill_id">
                    <option value="">— None —</option>
                    <?php foreach ($skills as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) ($form['skill_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="form-group full"><label for="description">Description</label>
                <textarea id="description" name="description"><?= e($form['description'] ?? '') ?></textarea></div>
            <div class="form-group full"><label>Useful for careers</label>
                <div class="chips">
                    <?php foreach ($careers as $c): ?>
                        <label class="chip" style="margin:0;font-weight:600">
                            <input type="checkbox" name="career_ids[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $checkedCareers, true) ? 'checked' : '' ?>>
                            <?= e($c['title']) ?>
                        </label>
                    <?php endforeach; ?>
                </div></div>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= !empty($form['id']) ? 'Save changes' : 'Add course' ?></button>
            <?php if (!empty($form['id'])): ?><a href="courses.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2><?= count($courses) ?> course(s)</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Course</th><th>Skill</th><th>Careers</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($courses as $c): ?>
                <tr>
                    <td><strong><?= e($c['title']) ?></strong><div class="small muted"><?= e($c['provider']) ?>
                        <?php if ($c['course_url']): ?> · <a href="<?= e($c['course_url']) ?>" target="_blank" rel="noopener">link ↗</a><?php endif; ?></div></td>
                    <td><?= e($c['skill_name'] ?? '—') ?></td>
                    <td><?= (int) $c['career_count'] ?></td>
                    <td class="text-right">
                        <div class="btn-group" style="justify-content:flex-end">
                            <a href="courses.php?edit=<?= (int) $c['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <form method="POST" class="inline-form" data-confirm="Delete this course?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
