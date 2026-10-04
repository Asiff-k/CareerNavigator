<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ===== Create a Career =====
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $salary = trim($_POST['average_salary'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '' || mb_strlen($title) > 150) $errors[] = 'Title is required (maximum 150 characters).';
        if (mb_strlen($category) > 100) $errors[] = 'Category must be 100 characters or fewer.';
        if (mb_strlen($salary) > 100) $errors[] = 'Salary must be 100 characters or fewer.';

        if (!$errors) {
            $stmt = $pdo->prepare("INSERT INTO careers (title, description, category, average_salary) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $category, $salary]);
            flash('success', 'Career created. Now add the skills it requires.');
            redirect('admin/career_edit.php?id=' . $pdo->lastInsertId());
        }
    }

    // ===== Delete a Career =====
    // The database also deletes its required skills, course links and saved history
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM careers WHERE id = ?");
        $stmt->execute([input_int($_POST, 'id')]);
        flash('success', $stmt->rowCount() ? 'Career deleted.' : 'Career not found.');
        redirect('admin/careers.php');
    }
}

// ===== Career List with Counts =====
$careers = $pdo->query(
    "SELECT c.*,
            (SELECT COUNT(*) FROM career_skills cs WHERE cs.career_id = c.id) AS skill_count,
            (SELECT COUNT(*) FROM career_courses cc WHERE cc.career_id = c.id) AS course_count,
            (SELECT COUNT(*) FROM interview_questions q WHERE q.career_id = c.id) AS question_count
     FROM careers c ORDER BY c.title"
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Careers';
$activePage = 'careers';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Careers</h1>
        <p>Careers and their required skills are what the recommendation algorithm compares students against.</p>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2>Add a career</h2>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="form-grid">
            <div class="form-group"><label for="title">Title</label>
                <input type="text" id="title" name="title" maxlength="150" required value="<?= e($_POST['title'] ?? '') ?>"></div>
            <div class="form-group"><label for="category">Category</label>
                <input type="text" id="category" name="category" maxlength="100" placeholder="e.g. Software Development" value="<?= e($_POST['category'] ?? '') ?>"></div>
            <div class="form-group"><label for="average_salary">Average salary</label>
                <input type="text" id="average_salary" name="average_salary" maxlength="100" placeholder="e.g. $50,000 - $80,000 per year" value="<?= e($_POST['average_salary'] ?? '') ?>"></div>
            <div class="form-group full"><label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Include keywords (e.g. web development, data, security) so student interests can match."><?= e($_POST['description'] ?? '') ?></textarea></div>
        </div>
        <button type="submit" class="btn btn-primary">Create career</button>
    </form>
</div>

<div class="card">
    <h2><?= count($careers) ?> career(s)</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Career</th><th>Category</th><th>Required skills</th><th>Courses</th><th>Questions</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($careers as $c): ?>
                <tr>
                    <td><strong><?= e($c['title']) ?></strong><div class="small muted"><?= e($c['average_salary']) ?></div></td>
                    <td><?= e($c['category']) ?></td>
                    <td><?= $c['skill_count'] ? (int) $c['skill_count'] : '<span class="badge badge-red">0</span>' ?></td>
                    <td><?= (int) $c['course_count'] ?></td>
                    <td><?= (int) $c['question_count'] ?></td>
                    <td class="text-right">
                        <div class="btn-group" style="justify-content:flex-end">
                            <a href="career_edit.php?id=<?= (int) $c['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <form method="POST" class="inline-form" data-confirm="Delete this career, its skill requirements and history?">
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
