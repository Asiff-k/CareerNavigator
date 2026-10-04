<?php
require_once 'includes/init.php';
require_once 'includes/recommendation.php';

// Advisors and admins have their own dashboards
if (is_logged_in() && current_user()['role'] !== 'student') {
    redirect(home_for_role(current_user()['role']));
}
require_role('student');

// ===== Student Profile and Career Matches =====
$userId = current_user_id();
$student = load_student_data($pdo, $userId);
$completeness = profile_completeness($student);
$recommendations = get_recommendations($pdo, $userId, $student);
$top = array_slice($recommendations, 0, 3);

// ===== Interview Practice and Advisor Notes =====
$stmt = $pdo->prepare("SELECT COUNT(*) AS attempts, AVG(score) AS average FROM interview_attempts WHERE user_id = ?");
$stmt->execute([$userId]);
$practice = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT n.note, n.created_at, u.name AS advisor_name
     FROM advisor_notes n JOIN users u ON u.id = n.advisor_id
     WHERE n.student_id = ? ORDER BY n.created_at DESC LIMIT 3"
);
$stmt->execute([$userId]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// The most important unmet skills for the best match are the next things to learn
$nextSkills = [];
if ($top) {
    foreach ($top[0]['skills'] as $s) {
        if ($s['status'] !== 'met') {
            $nextSkills[] = $s;
        }
    }
    $nextSkills = array_slice($nextSkills, 0, 3);
}

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require 'includes/header.php';
?>

<div class="hero-card">
    <h1>Hello, <?= e(current_user()['name']) ?> 👋</h1>
    <p>Here is a summary of your career profile. Keep your skills and projects up to date to get more accurate recommendations.</p>
    <div class="btn-group mt">
        <a href="recommendations.php" class="btn btn-lime">View career matches &rarr;</a>
        <a href="profile.php" class="btn btn-secondary">Update profile</a>
    </div>
</div>

<div class="grid grid-4 mb">
    <div class="stat">
        <div class="stat-value"><?= $completeness['percent'] ?>%</div>
        <div class="stat-label">Profile complete</div>
        <div class="progress mt"><div class="progress-bar <?= score_class($completeness['percent']) ?>" style="width:<?= $completeness['percent'] ?>%"></div></div>
    </div>
    <div class="stat">
        <div class="stat-value"><?= count($student['skills']) ?></div>
        <div class="stat-label">Skills added</div>
    </div>
    <div class="stat">
        <div class="stat-value"><?= $student['project_count'] ?></div>
        <div class="stat-label">Projects</div>
    </div>
    <div class="stat">
        <div class="stat-value"><?= (int) $practice['attempts'] ?></div>
        <div class="stat-label">Interview answers practised<?= $practice['attempts'] ? ' · avg ' . round($practice['average']) . '%' : '' ?></div>
    </div>
</div>

<?php if ($completeness['tips']): ?>
    <div class="alert alert-info">
        <strong>Improve your recommendations:</strong> <?= e(implode(' · ', $completeness['tips'])) ?>.
        <a href="profile.php">Complete your profile</a>
    </div>
<?php endif; ?>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <h2>Top career matches</h2>
            <a href="recommendations.php" class="btn btn-secondary btn-sm">See all</a>
        </div>
        <?php if (!$student['skills']): ?>
            <div class="empty">Add your skills to see personalised career matches.<br>
                <a href="profile.php" class="btn btn-primary mt">Add skills</a></div>
        <?php else: ?>
            <?php foreach ($top as $i => $r): ?>
                <div class="match">
                    <div class="match-top">
                        <div><span class="rank"><?= $i + 1 ?></span><span class="match-title"><?= e($r['career']['title']) ?></span></div>
                        <span class="match-score"><?= $r['score'] ?>%</span>
                    </div>
                    <div class="progress"><div class="progress-bar <?= score_class($r['score']) ?>" style="width:<?= $r['score'] ?>%"></div></div>
                    <p class="small muted mt"><?= e(match_label($r['score'])) ?> · <?= $r['met_count'] ?>/<?= $r['required_count'] ?> skills met ·
                        <a href="roadmap.php?career_id=<?= (int) $r['career']['id'] ?>">Skill gap &amp; roadmap</a></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div>
        <div class="card">
            <h2>Your next steps</h2>
            <?php if ($nextSkills): ?>
                <p class="muted">To become a stronger <strong><?= e($top[0]['career']['title']) ?></strong> candidate, focus on:</p>
                <ul class="list-plain">
                    <?php foreach ($nextSkills as $s): ?>
                        <li>
                            <strong><?= e($s['name']) ?></strong>
                            <span class="badge <?= $s['status'] === 'missing' ? 'badge-red' : 'badge-amber' ?>">
                                <?= $s['status'] === 'missing' ? 'Missing' : 'Improve' ?>
                            </span>
                            <div class="small muted"><?= level_name($s['student_level']) ?> &rarr; <?= level_name($s['required_level']) ?> required</div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="roadmap.php?career_id=<?= (int) $top[0]['career']['id'] ?>" class="btn btn-primary btn-sm mt">Open my roadmap</a>
            <?php elseif ($top && $student['skills']): ?>
                <p>You meet all required skills for your top match. Practise interview questions next!</p>
                <a href="interview.php" class="btn btn-primary btn-sm">Practise interviews</a>
            <?php else: ?>
                <p class="muted">Add skills to your profile to get a personalised learning plan.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Advisor feedback</h2>
            <?php if (!$notes): ?>
                <p class="muted">No feedback from an advisor yet.</p>
            <?php endif; ?>
            <?php foreach ($notes as $n): ?>
                <div class="note">
                    <?= nl2br(e($n['note'])) ?>
                    <div class="small muted mt">— <?= e($n['advisor_name']) ?>, <?= e(date('d M Y', strtotime($n['created_at']))) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
