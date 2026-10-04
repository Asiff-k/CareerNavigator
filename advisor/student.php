<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/recommendation.php';
require_role('advisor', 'admin');

$studentId = input_int($_GET, 'id');

// ===== Load the Student =====
// Only student accounts can be opened here
$stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$studentId]);
$studentUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$studentUser) {
    flash('error', 'Student not found.');
    redirect('advisor/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ===== Add a Feedback Note =====
    if ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        if ($note === '' || mb_strlen($note) > 3000) {
            $errors[] = 'Please write a note (maximum 3000 characters).';
        } else {
            $stmt = $pdo->prepare("INSERT INTO advisor_notes (advisor_id, student_id, note) VALUES (?, ?, ?)");
            $stmt->execute([current_user_id(), $studentId, $note]);
            flash('success', 'Feedback saved. The student will see it on their dashboard.');
            redirect('advisor/student.php?id=' . $studentId);
        }
    }

    // ===== Delete a Feedback Note =====
    // Advisors can delete only their own notes; admins can delete any note
    if ($action === 'delete_note') {
        if (current_user()['role'] === 'admin') {
            $stmt = $pdo->prepare("DELETE FROM advisor_notes WHERE id = ? AND student_id = ?");
            $stmt->execute([input_int($_POST, 'note_id'), $studentId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM advisor_notes WHERE id = ? AND student_id = ? AND advisor_id = ?");
            $stmt->execute([input_int($_POST, 'note_id'), $studentId, current_user_id()]);
        }
        flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Note deleted.' : 'You can only delete your own notes.');
        redirect('advisor/student.php?id=' . $studentId);
    }
}

// ===== Student Profile, Skills, Projects and Notes =====
$student = load_student_data($pdo, $studentId);
$profile = $student['profile'];
$completeness = profile_completeness($student);
$recommendations = $student['skills'] ? array_slice(get_recommendations($pdo, $studentId, $student), 0, 5) : [];

$stmt = $pdo->prepare(
    "SELECT s.name, s.category, ss.proficiency FROM student_skills ss JOIN skills s ON s.id = ss.skill_id
     WHERE ss.user_id = ? ORDER BY FIELD(ss.proficiency, 'advanced', 'intermediate', 'beginner'), s.name"
);
$stmt->execute([$studentId]);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$studentId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT n.*, u.name AS advisor_name FROM advisor_notes n JOIN users u ON u.id = n.advisor_id
     WHERE n.student_id = ? ORDER BY n.created_at DESC, n.id DESC"
);
$stmt->execute([$studentId]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) AS attempts, AVG(score) AS average FROM interview_attempts WHERE user_id = ?");
$stmt->execute([$studentId]);
$practice = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT MAX(created_at) FROM recommendation_history WHERE user_id = ?");
$stmt->execute([$studentId]);
$lastSaved = $stmt->fetchColumn();

$pageTitle = $studentUser['name'];
$activePage = 'advisor';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" class="small">&larr; All students</a>
        <h1 class="mt"><?= e($studentUser['name']) ?></h1>
        <p><?= e($studentUser['email']) ?> · joined <?= e(date('d M Y', strtotime($studentUser['created_at']))) ?></p>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="grid grid-4 mb">
    <div class="stat"><div class="stat-value"><?= $completeness['percent'] ?>%</div><div class="stat-label">Profile complete</div></div>
    <div class="stat"><div class="stat-value"><?= $profile['cgpa'] ?? '—' ?></div><div class="stat-label">CGPA</div></div>
    <div class="stat"><div class="stat-value"><?= count($skills) ?> / <?= count($projects) ?></div><div class="stat-label">Skills / projects</div></div>
    <div class="stat"><div class="stat-value"><?= (int) $practice['attempts'] ?></div><div class="stat-label">Interview answers<?= $practice['attempts'] ? ' · avg ' . round($practice['average']) . '%' : '' ?></div></div>
</div>

<div class="grid grid-2">
    <div>
        <div class="card">
            <h2>Academic profile</h2>
            <?php if (!$profile): ?>
                <p class="muted">This student has not completed their profile yet.</p>
            <?php else: ?>
                <ul class="list-plain">
                    <li><strong>University:</strong> <?= e($profile['university'] ?: '—') ?></li>
                    <li><strong>Department:</strong> <?= e($profile['department'] ?: '—') ?></li>
                    <li><strong>Semester:</strong> <?= e($profile['semester'] ?: '—') ?></li>
                    <li><strong>Interests:</strong>
                        <?php foreach ($student['interests'] as $i): ?><span class="chip"><?= e($i) ?></span> <?php endforeach; ?>
                        <?= $student['interests'] ? '' : '—' ?></li>
                    <?php if ($profile['bio']): ?><li><strong>Bio:</strong> <?= nl2br(e($profile['bio'])) ?></li><?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Skills</h2>
            <?php if (!$skills): ?><p class="muted">No skills added.</p><?php endif; ?>
            <div class="chips">
                <?php foreach ($skills as $s): ?>
                    <?php $cls = ['advanced' => 'badge-green', 'intermediate' => 'badge-amber', 'beginner' => 'badge-gray'][$s['proficiency']]; ?>
                    <span class="badge <?= $cls ?>"><?= e($s['name']) ?> · <?= e(ucfirst($s['proficiency'])) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h2>Projects</h2>
            <?php if (!$projects): ?><p class="muted">No projects added.</p><?php endif; ?>
            <ul class="list-plain">
                <?php foreach ($projects as $p): ?>
                    <li>
                        <strong><?= e($p['title']) ?></strong>
                        <?php if ($p['project_url']): ?> · <a href="<?= e($p['project_url']) ?>" target="_blank" rel="noopener">link ↗</a><?php endif; ?>
                        <div class="small muted"><?= e($p['technologies']) ?></div>
                        <?php if ($p['description']): ?><div class="small"><?= e($p['description']) ?></div><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div>
        <div class="card">
            <h2>Top career matches</h2>
            <?php if ($lastSaved): ?>
                <p class="small muted">Student last saved an analysis on <?= e(date('d M Y, H:i', strtotime($lastSaved))) ?>. Scores below are live.</p>
            <?php endif; ?>
            <?php if (!$recommendations): ?><p class="muted">No recommendations until the student adds skills.</p><?php endif; ?>
            <?php foreach ($recommendations as $i => $r): ?>
                <div class="match">
                    <div class="match-top">
                        <div><span class="rank"><?= $i + 1 ?></span><span class="match-title"><?= e($r['career']['title']) ?></span></div>
                        <span class="match-score"><?= $r['score'] ?>%</span>
                    </div>
                    <div class="progress"><div class="progress-bar <?= score_class($r['score']) ?>" style="width:<?= $r['score'] ?>%"></div></div>
                    <details>
                        <summary>Skill gap (<?= $r['met_count'] ?>/<?= $r['required_count'] ?> met)</summary>
                        <div class="chips mt">
                            <?php foreach ($r['skills'] as $s): ?>
                                <?php if ($s['status'] !== 'met'): ?>
                                    <span class="badge <?= $s['status'] === 'missing' ? 'badge-red' : 'badge-amber' ?>">
                                        <?= e($s['name']) ?> · <?= level_name($s['student_level']) ?> &rarr; <?= level_name($s['required_level']) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <p class="small muted mt"><?= e($r['explanation']) ?></p>
                    </details>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h2>Advisor feedback</h2>
            <form method="POST" class="mb">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_note">
                <div class="form-group">
                    <label for="note">Write guidance for this student</label>
                    <textarea id="note" name="note" maxlength="3000" required placeholder="e.g. Focus on React this semester and build a portfolio project."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save feedback</button>
            </form>

            <?php if (!$notes): ?><p class="muted">No feedback yet.</p><?php endif; ?>
            <?php foreach ($notes as $n): ?>
                <div class="note">
                    <?= nl2br(e($n['note'])) ?>
                    <div class="btn-group mt">
                        <span class="small muted">— <?= e($n['advisor_name']) ?>, <?= e(date('d M Y, H:i', strtotime($n['created_at']))) ?></span>
                        <?php if ((int) $n['advisor_id'] === current_user_id() || current_user()['role'] === 'admin'): ?>
                            <form method="POST" class="inline-form" data-confirm="Delete this note?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_note">
                                <input type="hidden" name="note_id" value="<?= (int) $n['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
