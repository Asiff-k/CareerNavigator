<?php
require_once 'includes/init.php';
require_role('student');

$userId = current_user_id();
$errors = [];

// Load one project that belongs to the current student (or null).
function find_own_project(PDO $pdo, int $projectId, int $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$projectId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([input_int($_POST, 'id'), $userId]);
        flash('success', $stmt->rowCount() ? 'Project deleted.' : 'Project not found.');
        redirect('projects.php');
    }

    if ($action === 'save') {
        $id = input_int($_POST, 'id');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $technologies = trim($_POST['technologies'] ?? '');
        $projectUrl = trim($_POST['project_url'] ?? '');

        if ($title === '' || mb_strlen($title) > 150) $errors[] = 'Project title is required (maximum 150 characters).';
        if (mb_strlen($description) > 3000) $errors[] = 'Description must be 3000 characters or fewer.';
        if (mb_strlen($technologies) > 500) $errors[] = 'Technologies must be 500 characters or fewer.';
        if ($projectUrl !== '' && (!filter_var($projectUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $projectUrl) || strlen($projectUrl) > 255)) {
            $errors[] = 'Project link must be a valid URL starting with http:// or https://';
        }
        if ($id && !find_own_project($pdo, $id, $userId)) {
            $errors[] = 'Project not found.';
        }

        if (!$errors) {
            $values = [$title, $description, $technologies, $projectUrl ?: null];
            if ($id) {
                $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, technologies = ?, project_url = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([...$values, $id, $userId]);
                flash('success', 'Project updated.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO projects (title, description, technologies, project_url, user_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([...$values, $userId]);
                flash('success', 'Project added. Your career matches have been updated.');
            }
            redirect('projects.php');
        }
    }
}

// Which project is being edited (if any)?
$editing = null;
if (isset($_GET['edit'])) {
    $editing = find_own_project($pdo, input_int($_GET, 'edit'), $userId);
    if (!$editing) {
        flash('error', 'Project not found.');
        redirect('projects.php');
    }
}
$form = $errors ? $_POST : ($editing ?? []);

$stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My Projects';
$activePage = 'projects';
require 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>My Projects</h1>
        <p>Projects are evidence of your skills. Technologies you list here count towards your career compatibility.</p>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="grid grid-2">
    <div class="card">
        <h2><?= !empty($form['id']) ? 'Edit project' : 'Add a project' ?></h2>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($form['id'] ?? 0) ?>">
            <div class="form-group">
                <label for="title">Project title</label>
                <input type="text" id="title" name="title" maxlength="150" required value="<?= e($form['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="3000" placeholder="What does it do? What was your role?"><?= e($form['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="technologies">Technologies used</label>
                <input type="text" id="technologies" name="technologies" maxlength="500" placeholder="e.g. PHP, MySQL, JavaScript, Bootstrap"
                       value="<?= e($form['technologies'] ?? '') ?>">
                <div class="help">Separate with commas. These are matched against career skills.</div>
            </div>
            <div class="form-group">
                <label for="project_url">Project link (optional)</label>
                <input type="url" id="project_url" name="project_url" maxlength="255" placeholder="https://github.com/..."
                       value="<?= e($form['project_url'] ?? '') ?>">
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary"><?= !empty($form['id']) ? 'Save changes' : 'Add project' ?></button>
                <?php if (!empty($form['id'])): ?>
                    <a href="projects.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div>
        <?php if (!$projects): ?>
            <div class="card empty">No projects yet. Add your first project to strengthen your profile.</div>
        <?php endif; ?>
        <?php foreach ($projects as $p): ?>
            <div class="card">
                <div class="card-header">
                    <h3 style="margin:0"><?= e($p['title']) ?></h3>
                    <span class="small muted"><?= e(date('d M Y', strtotime($p['created_at']))) ?></span>
                </div>
                <?php if ($p['description']): ?><p><?= nl2br(e($p['description'])) ?></p><?php endif; ?>
                <?php if ($p['technologies']): ?>
                    <div class="chips mb">
                        <?php foreach (array_filter(array_map('trim', explode(',', $p['technologies']))) as $tech): ?>
                            <span class="chip"><?= e($tech) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="btn-group">
                    <?php if ($p['project_url']): ?>
                        <a href="<?= e($p['project_url']) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">View project ↗</a>
                    <?php endif; ?>
                    <a href="projects.php?edit=<?= (int) $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <form method="POST" class="inline-form" data-confirm="Delete this project?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
