<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = input_int($_POST, 'id');

    if ($action === 'save') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Skill name is required (maximum 100 characters).';
        if (mb_strlen($category) > 100) $errors[] = 'Category must be 100 characters or fewer.';

        // Skill names must be unique.
        $check = $pdo->prepare("SELECT id FROM skills WHERE name = ? AND id <> ?");
        $check->execute([$name, $id]);
        if ($check->fetch()) $errors[] = 'A skill with this name already exists.';

        if (!$errors) {
            if ($id) {
                $pdo->prepare("UPDATE skills SET name = ?, category = ? WHERE id = ?")->execute([$name, $category, $id]);
                flash('success', 'Skill updated.');
            } else {
                $pdo->prepare("INSERT INTO skills (name, category) VALUES (?, ?)")->execute([$name, $category]);
                flash('success', 'Skill added.');
            }
            redirect('admin/skills.php');
        }
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM skills WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', $stmt->rowCount() ? 'Skill deleted.' : 'Skill not found.');
        redirect('admin/skills.php');
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM skills WHERE id = ?");
    $stmt->execute([input_int($_GET, 'edit')]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$form = $errors ? $_POST : ($editing ?? []);

$skills = $pdo->query(
    "SELECT s.*,
            (SELECT COUNT(*) FROM career_skills cs WHERE cs.skill_id = s.id) AS career_count,
            (SELECT COUNT(*) FROM student_skills ss WHERE ss.skill_id = s.id) AS student_count,
            (SELECT COUNT(*) FROM courses c WHERE c.skill_id = s.id) AS course_count
     FROM skills s ORDER BY s.category, s.name"
)->fetchAll(PDO::FETCH_ASSOC);
$categories = array_unique(array_filter(array_column($skills, 'category')));

$pageTitle = 'Skills';
$activePage = 'skills';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Skills</h1>
        <p>Skills students can add to their profile and careers can require.</p>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2><?= !empty($form['id']) ? 'Edit skill' : 'Add a skill' ?></h2>
    <form method="POST" class="filters" style="margin:0">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($form['id'] ?? 0) ?>">
        <div class="form-group"><label for="name">Skill name</label>
            <input type="text" id="name" name="name" maxlength="100" required value="<?= e($form['name'] ?? '') ?>"></div>
        <div class="form-group"><label for="category">Category</label>
            <input type="text" id="category" name="category" maxlength="100" list="category-list" value="<?= e($form['category'] ?? '') ?>">
            <datalist id="category-list">
                <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
            </datalist></div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= !empty($form['id']) ? 'Save changes' : 'Add skill' ?></button>
            <?php if (!empty($form['id'])): ?><a href="skills.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2><?= count($skills) ?> skill(s)</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Skill</th><th>Category</th><th>Careers</th><th>Students</th><th>Courses</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($skills as $s): ?>
                <tr>
                    <td><strong><?= e($s['name']) ?></strong></td>
                    <td class="muted"><?= e($s['category']) ?></td>
                    <td><?= (int) $s['career_count'] ?></td>
                    <td><?= (int) $s['student_count'] ?></td>
                    <td><?= $s['course_count'] ? (int) $s['course_count'] : '<span class="badge badge-amber">0</span>' ?></td>
                    <td class="text-right">
                        <div class="btn-group" style="justify-content:flex-end">
                            <a href="skills.php?edit=<?= (int) $s['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <form method="POST" class="inline-form" data-confirm="Delete this skill? It will be removed from all careers and student profiles.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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
