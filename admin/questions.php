<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$difficulties = ['easy', 'medium', 'hard'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = input_int($_POST, 'id');

    // ===== Add or Update a Question =====
    if ($action === 'save') {
        $careerId = input_int($_POST, 'career_id') ?: null; // empty means a general question
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $difficulty = $_POST['difficulty'] ?? '';

        if ($question === '' || mb_strlen($question) > 2000) $errors[] = 'Question is required (maximum 2000 characters).';
        if ($answer === '') $errors[] = 'A model answer is required so students can compare their practice answers.';
        if (!in_array($difficulty, $difficulties, true)) $errors[] = 'Please choose a valid difficulty.';
        if ($careerId) {
            $check = $pdo->prepare("SELECT id FROM careers WHERE id = ?");
            $check->execute([$careerId]);
            if (!$check->fetch()) $errors[] = 'Please choose a valid career.';
        }

        if (!$errors) {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE interview_questions SET career_id = ?, question = ?, answer = ?, difficulty = ? WHERE id = ?");
                $stmt->execute([$careerId, $question, $answer, $difficulty, $id]);
                flash('success', 'Question updated.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO interview_questions (career_id, question, answer, difficulty) VALUES (?, ?, ?, ?)");
                $stmt->execute([$careerId, $question, $answer, $difficulty]);
                flash('success', 'Question added.');
            }
            redirect('admin/questions.php');
        }
    }

    // ===== Delete a Question =====
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM interview_questions WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', $stmt->rowCount() ? 'Question deleted.' : 'Question not found.');
        redirect('admin/questions.php');
    }
}

// ===== Question Being Edited =====
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM interview_questions WHERE id = ?");
    $stmt->execute([input_int($_GET, 'edit')]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$form = $errors ? $_POST : ($editing ?? []);

// ===== Question List with Career Filter =====
$careers = $pdo->query("SELECT id, title FROM careers ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
$careerFilter = input_int($_GET, 'career_id');

$sql = "SELECT q.*, c.title AS career_title FROM interview_questions q LEFT JOIN careers c ON c.id = q.career_id";
$params = [];
if ($careerFilter) {
    $sql .= " WHERE q.career_id = ?";
    $params[] = $careerFilter;
}
$sql .= " ORDER BY c.title IS NULL DESC, c.title, FIELD(q.difficulty, 'easy', 'medium', 'hard')";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Interview Questions';
$activePage = 'questions';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Interview Questions</h1>
        <p>Questions and model answers used in the student interview practice module.</p>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2><?= !empty($form['id']) ? 'Edit question' : 'Add a question' ?></h2>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($form['id'] ?? 0) ?>">
        <div class="form-grid">
            <div class="form-group"><label for="career_id">Career</label>
                <select id="career_id" name="career_id">
                    <option value="">General (all careers)</option>
                    <?php foreach ($careers as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) ($form['career_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="form-group"><label for="difficulty">Difficulty</label>
                <select id="difficulty" name="difficulty">
                    <?php foreach ($difficulties as $d): ?>
                        <option value="<?= $d ?>" <?= ($form['difficulty'] ?? 'easy') === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="form-group full"><label for="question">Question</label>
                <textarea id="question" name="question" maxlength="2000" required rows="2"><?= e($form['question'] ?? '') ?></textarea></div>
            <div class="form-group full"><label for="answer">Model answer</label>
                <textarea id="answer" name="answer" required><?= e($form['answer'] ?? '') ?></textarea>
                <div class="help">Key terms in the model answer are used to score student practice answers.</div></div>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= !empty($form['id']) ? 'Save changes' : 'Add question' ?></button>
            <?php if (!empty($form['id'])): ?><a href="questions.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<form method="GET" class="filters card">
    <div class="form-group"><label for="filter_career">Filter by career</label>
        <select id="filter_career" name="career_id">
            <option value="">All</option>
            <?php foreach ($careers as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $careerFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
            <?php endforeach; ?>
        </select></div>
    <div class="btn-group">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="questions.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<div class="card">
    <h2><?= count($questions) ?> question(s)</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Question</th><th>Career</th><th>Difficulty</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($questions as $qn): ?>
                <tr>
                    <td><?= e($qn['question']) ?></td>
                    <td><?= e($qn['career_title'] ?? 'General') ?></td>
                    <td><span class="badge <?= ['easy' => 'badge-green', 'medium' => 'badge-amber', 'hard' => 'badge-red'][$qn['difficulty']] ?>"><?= ucfirst(e($qn['difficulty'])) ?></span></td>
                    <td class="text-right">
                        <div class="btn-group" style="justify-content:flex-end">
                            <a href="questions.php?edit=<?= (int) $qn['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <form method="POST" class="inline-form" data-confirm="Delete this question?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $qn['id'] ?>">
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
