<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

// Simple counts for the statistics cards.
$count = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();
$stats = [
    'Students'            => $count("SELECT COUNT(*) FROM users WHERE role = 'student'"),
    'Advisors'            => $count("SELECT COUNT(*) FROM users WHERE role = 'advisor'"),
    'Careers'             => $count("SELECT COUNT(*) FROM careers"),
    'Skills'              => $count("SELECT COUNT(*) FROM skills"),
    'Courses'             => $count("SELECT COUNT(*) FROM courses"),
    'Interview questions' => $count("SELECT COUNT(*) FROM interview_questions"),
    'Saved analyses'      => $count("SELECT COUNT(DISTINCT user_id, created_at) FROM recommendation_history"),
    'Practice answers'    => $count("SELECT COUNT(*) FROM interview_attempts"),
];

$recentUsers = $pdo->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC, id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

// Data quality checks: things that make recommendations or roadmaps weaker.
$careersWithoutSkills = $pdo->query(
    "SELECT c.id, c.title FROM careers c LEFT JOIN career_skills cs ON cs.career_id = c.id
     WHERE cs.id IS NULL ORDER BY c.title"
)->fetchAll(PDO::FETCH_ASSOC);
$skillsWithoutCourses = $pdo->query(
    "SELECT s.name FROM skills s LEFT JOIN courses c ON c.skill_id = s.id
     WHERE c.id IS NULL ORDER BY s.name"
)->fetchAll(PDO::FETCH_COLUMN);

// Most popular skills among students.
$popularSkills = $pdo->query(
    "SELECT s.name, COUNT(*) AS total FROM student_skills ss JOIN skills s ON s.id = ss.skill_id
     GROUP BY s.id ORDER BY total DESC, s.name LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin Dashboard';
$activePage = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="hero-card">
    <h1>Admin Dashboard</h1>
    <p>Manage the careers, skills, courses and interview questions that power CareerNavigator's recommendations.</p>
    <div class="btn-group mt">
        <a href="careers.php" class="btn btn-lime">Manage careers</a>
        <a href="users.php" class="btn btn-secondary">Manage users</a>
    </div>
</div>

<div class="grid grid-4 mb">
    <?php foreach ($stats as $label => $value): ?>
        <div class="stat"><div class="stat-value"><?= $value ?></div><div class="stat-label"><?= e($label) ?></div></div>
    <?php endforeach; ?>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header"><h2>Recent registrations</h2><a href="users.php" class="btn btn-secondary btn-sm">All users</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Role</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td><strong><?= e($u['name']) ?></strong><div class="small muted"><?= e($u['email']) ?></div></td>
                        <td><span class="badge badge-gray"><?= e(ucfirst($u['role'])) ?></span></td>
                        <td class="small muted"><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card">
            <h2>Data quality checks</h2>
            <?php if (!$careersWithoutSkills && !$skillsWithoutCourses): ?>
                <p class="muted">Everything looks good. Every career has required skills and every skill has a course.</p>
            <?php endif; ?>
            <?php if ($careersWithoutSkills): ?>
                <p><strong>Careers without required skills</strong> (they cannot be scored properly):</p>
                <div class="chips mb">
                    <?php foreach ($careersWithoutSkills as $c): ?>
                        <a class="badge badge-red" href="career_edit.php?id=<?= (int) $c['id'] ?>"><?= e($c['title']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($skillsWithoutCourses): ?>
                <p><strong>Skills without any course</strong> (roadmaps will have no resource):</p>
                <div class="chips">
                    <?php foreach ($skillsWithoutCourses as $name): ?><span class="badge badge-amber"><?= e($name) ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Most common student skills</h2>
            <?php if (!$popularSkills): ?><p class="muted">No student skills yet.</p><?php endif; ?>
            <?php $max = $popularSkills ? max(array_column($popularSkills, 'total')) : 1; ?>
            <?php foreach ($popularSkills as $s): ?>
                <div class="mb">
                    <div class="match-top" style="margin-bottom:4px"><span><?= e($s['name']) ?></span><strong><?= (int) $s['total'] ?></strong></div>
                    <div class="progress"><div class="progress-bar" style="width:<?= round($s['total'] / $max * 100) ?>%"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
