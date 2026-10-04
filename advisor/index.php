<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/recommendation.php';
require_role('advisor', 'admin');

// ===== Student List with Search =====
$q = trim($_GET['q'] ?? '');

$sql = "SELECT u.id, u.name, u.email, u.created_at, p.department, p.semester, p.cgpa,
               (SELECT COUNT(*) FROM student_skills ss WHERE ss.user_id = u.id) AS skill_count,
               (SELECT COUNT(*) FROM projects pr WHERE pr.user_id = u.id) AS project_count,
               (SELECT COUNT(*) FROM advisor_notes n WHERE n.student_id = u.id) AS note_count
        FROM users u LEFT JOIN student_profiles p ON p.user_id = u.id
        WHERE u.role = 'student'";
$params = [];
if ($q !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR p.department LIKE ?)";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY u.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Best Career Match for Each Student =====
foreach ($students as &$s) {
    $recs = $s['skill_count'] ? get_recommendations($pdo, (int) $s['id']) : [];
    $s['top'] = $recs[0] ?? null;
}
unset($s);

$pageTitle = 'My Students';
$activePage = 'advisor';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Student Overview</h1>
        <p>Review student profiles, career matches and skill gaps, and leave guidance for them.</p>
    </div>
</div>

<div class="grid grid-3 mb">
    <div class="stat"><div class="stat-value"><?= count($students) ?></div><div class="stat-label">Students<?= $q !== '' ? ' found' : '' ?></div></div>
    <div class="stat"><div class="stat-value"><?= count(array_filter($students, fn($s) => $s['skill_count'] > 0)) ?></div><div class="stat-label">With skills added</div></div>
    <div class="stat"><div class="stat-value"><?= count(array_filter($students, fn($s) => $s['note_count'] == 0)) ?></div><div class="stat-label">Without advisor feedback</div></div>
</div>

<form method="GET" class="filters card">
    <div class="form-group">
        <label for="q">Search students</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, email or department">
    </div>
    <div class="btn-group">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="index.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<div class="card">
    <?php if (!$students): ?>
        <div class="empty">No students found.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Student</th><th>Department</th><th>CGPA</th><th>Skills</th><th>Projects</th><th>Top match</th><th>Notes</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><strong><?= e($s['name']) ?></strong><div class="small muted"><?= e($s['email']) ?></div></td>
                        <td><?= e($s['department'] ?: '—') ?><div class="small muted"><?= e($s['semester'] ?? '') ?></div></td>
                        <td><?= $s['cgpa'] !== null ? e($s['cgpa']) : '—' ?></td>
                        <td><?= (int) $s['skill_count'] ?></td>
                        <td><?= (int) $s['project_count'] ?></td>
                        <td>
                            <?php if ($s['top']): ?>
                                <?= e($s['top']['career']['title']) ?> <span class="badge badge-green"><?= $s['top']['score'] ?>%</span>
                            <?php else: ?><span class="muted">No skills yet</span><?php endif; ?>
                        </td>
                        <td><?= (int) $s['note_count'] ?></td>
                        <td><a href="student.php?id=<?= (int) $s['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
