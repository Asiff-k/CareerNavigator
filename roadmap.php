<?php
require_once 'includes/init.php';
require_once 'includes/recommendation.php';
require_role('student');

$userId = current_user_id();
$careers = load_careers_with_skills($pdo);
$student = load_student_data($pdo, $userId);

// ===== Choose the Career =====
// Use the selected career, or the student's best match by default
$careerId = input_int($_GET, 'career_id');
if (!$careerId) {
    $recommendations = get_recommendations($pdo, $userId, $student);
    $careerId = $recommendations ? (int) $recommendations[0]['career']['id'] : 0;
}
$analysis = isset($careers[$careerId]) ? analyze_career($careers[$careerId], $student) : null;

if (!$analysis && isset($_GET['career_id'])) {
    flash('error', 'Career not found.');
    redirect('roadmap.php');
}

// ===== Skill Gap, Courses and Interview Progress =====
if ($analysis) {
    $career = $analysis['career'];
    $missing = array_values(array_filter($analysis['skills'], fn($s) => $s['status'] === 'missing'));
    $improve = array_values(array_filter($analysis['skills'], fn($s) => $s['status'] === 'improve'));
    $notInProjects = array_values(array_filter($analysis['skills'], fn($s) => !$s['in_projects']));

    // Courses for every skill the student still needs
    $coursesBySkill = courses_for_skills($pdo, array_column(array_merge($missing, $improve), 'skill_id'));

    // Courses and certifications linked directly to this career
    $stmt = $pdo->prepare(
        "SELECT c.*, s.name AS skill_name FROM career_courses cc
         JOIN courses c ON c.id = cc.course_id
         LEFT JOIN skills s ON s.id = c.skill_id
         WHERE cc.career_id = ? ORDER BY c.title"
    );
    $stmt->execute([$careerId]);
    $careerCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // How many of this career's interview questions the student has practised
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM interview_questions WHERE career_id = ?");
    $stmt->execute([$careerId]);
    $questionCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT a.question_id) FROM interview_attempts a
         JOIN interview_questions q ON q.id = a.question_id
         WHERE a.user_id = ? AND q.career_id = ?"
    );
    $stmt->execute([$userId, $careerId]);
    $practised = (int) $stmt->fetchColumn();
}

$pageTitle = 'Skill Gap & Roadmap';
$activePage = 'roadmap';
require 'includes/header.php';

// Show up to two courses for one skill inside the roadmap
function render_skill_courses(array $coursesBySkill, int $skillId): void
{
    if (empty($coursesBySkill[$skillId])) {
        echo '<div class="small muted">No course in the database yet. Search online tutorials and documentation.</div>';
        return;
    }
    foreach (array_slice($coursesBySkill[$skillId], 0, 2) as $c) {
        echo '<div class="small">📘 <a href="' . e($c['course_url']) . '" target="_blank" rel="noopener">' . e($c['title']) . '</a> <span class="muted">· ' . e($c['provider']) . '</span></div>';
    }
}
?>

<div class="page-header">
    <div>
        <h1>Skill Gap &amp; Roadmap</h1>
        <p>Compare your skills with a career's requirements and follow a personalised learning plan.</p>
    </div>
    <form method="GET" class="btn-group">
        <select name="career_id" aria-label="Choose a career" style="width:auto">
            <?php foreach ($careers as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $careerId ? 'selected' : '' ?>><?= e($c['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Analyse</button>
    </form>
</div>

<?php if (!$analysis): ?>
    <div class="card empty">No careers are available yet.</div>
<?php else: ?>

<div class="hero-card">
    <span class="badge badge-dark" style="background:rgba(255,255,255,.12)"><?= e($career['category']) ?></span>
    <h1 class="mt"><?= e($career['title']) ?> — <?= $analysis['score'] ?>% compatible</h1>
    <p><?= e($career['description']) ?></p>
    <p class="small">Average salary: <?= e($career['average_salary']) ?></p>
    <div class="progress mt" style="background:rgba(255,255,255,.15)"><div class="progress-bar" style="width:<?= $analysis['score'] ?>%"></div></div>
</div>

<div class="grid grid-4 mb">
    <div class="stat"><div class="stat-value"><?= $analysis['met_count'] ?>/<?= $analysis['required_count'] ?></div><div class="stat-label">Skills fully met</div></div>
    <div class="stat"><div class="stat-value"><?= count($improve) ?></div><div class="stat-label">Skills to improve</div></div>
    <div class="stat"><div class="stat-value"><?= count($missing) ?></div><div class="stat-label">Missing skills</div></div>
    <div class="stat"><div class="stat-value"><?= $analysis['skill_score'] ?>%</div><div class="stat-label">Weighted skill match</div></div>
</div>

<div class="card">
    <h2>Skill gap analysis</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Skill</th><th>Importance</th><th>Your level</th><th>Required</th><th>Status</th><th>In projects</th></tr></thead>
            <tbody>
            <?php foreach ($analysis['skills'] as $s): ?>
                <tr>
                    <td><strong><?= e($s['name']) ?></strong><div class="small muted"><?= e($s['category']) ?></div></td>
                    <td><?= str_repeat('★', $s['importance']) ?><span class="muted"><?= str_repeat('☆', 5 - $s['importance']) ?></span></td>
                    <td><?= level_name($s['student_level']) ?></td>
                    <td><?= level_name($s['required_level']) ?></td>
                    <td>
                        <?php if ($s['status'] === 'met'): ?><span class="badge badge-green">Met</span>
                        <?php elseif ($s['status'] === 'improve'): ?><span class="badge badge-amber">Improve</span>
                        <?php else: ?><span class="badge badge-red">Missing</span><?php endif; ?>
                    </td>
                    <td><?= $s['in_projects'] ? '✓' : '<span class="muted">—</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="small muted mt">Update your levels on the <a href="profile.php#skills">profile page</a> as you learn. The analysis updates immediately.</p>
</div>

<div class="card">
    <h2>Your personalised learning roadmap</h2>
    <div class="timeline">

        <div class="step <?= !$missing ? 'done' : '' ?>" data-step="1">
            <h3>Learn the missing skills</h3>
            <?php if (!$missing): ?>
                <p class="muted">Great — you already have every required skill at some level.</p>
            <?php else: ?>
                <p class="muted">Start with the most important skills first.</p>
                <ul class="list-plain">
                    <?php foreach ($missing as $s): ?>
                        <li><strong><?= e($s['name']) ?></strong> <span class="badge badge-red">reach <?= level_name($s['required_level']) ?></span>
                            <?php render_skill_courses($coursesBySkill, $s['skill_id']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="step <?= !$improve ? 'done' : '' ?>" data-step="2">
            <h3>Level up your existing skills</h3>
            <?php if (!$improve): ?>
                <p class="muted">No skills below the required level.</p>
            <?php else: ?>
                <ul class="list-plain">
                    <?php foreach ($improve as $s): ?>
                        <li><strong><?= e($s['name']) ?></strong>
                            <span class="badge badge-amber"><?= level_name($s['student_level']) ?> &rarr; <?= level_name($s['required_level']) ?></span>
                            <?php render_skill_courses($coursesBySkill, $s['skill_id']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="step <?= !$notInProjects ? 'done' : '' ?>" data-step="3">
            <h3>Build portfolio projects</h3>
            <?php if (!$notInProjects): ?>
                <p class="muted">Your projects already demonstrate every required skill.</p>
            <?php else: ?>
                <p>Build a project that uses
                    <strong><?= e(implode(', ', array_column(array_slice($notInProjects, 0, 3), 'name'))) ?></strong>
                    and add it to <a href="projects.php">My Projects</a>. Employers value practical evidence.</p>
            <?php endif; ?>
        </div>

        <div class="step <?= $questionCount && $practised >= $questionCount ? 'done' : '' ?>" data-step="4">
            <h3>Prepare for interviews</h3>
            <p>You have practised <strong><?= $practised ?></strong> of <?= $questionCount ?> <?= e($career['title']) ?> interview questions.</p>
            <a href="interview.php?career_id=<?= (int) $careerId ?>&amp;practice=1" class="btn btn-primary btn-sm">Start practice</a>
        </div>

        <div class="step" data-step="5">
            <h3>Earn a certification and apply</h3>
            <p class="muted">Complete one of the recommended certifications below, update your profile, and start applying for <?= e($career['title']) ?> internships and junior roles.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Recommended courses &amp; certifications for <?= e($career['title']) ?></h2>
        <a href="courses.php?career_id=<?= (int) $careerId ?>" class="btn btn-secondary btn-sm">Browse all</a>
    </div>
    <?php if (!$careerCourses): ?>
        <p class="muted">No courses linked to this career yet.</p>
    <?php else: ?>
        <ul class="list-plain">
            <?php foreach ($careerCourses as $c): ?>
                <li>
                    <a href="<?= e($c['course_url']) ?>" target="_blank" rel="noopener"><strong><?= e($c['title']) ?></strong> ↗</a>
                    <span class="muted small">· <?= e($c['provider']) ?></span>
                    <?php if ($c['skill_name']): ?><span class="badge badge-gray"><?= e($c['skill_name']) ?></span><?php endif; ?>
                    <div class="small muted"><?= e($c['description']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php require 'includes/footer.php'; ?>
