<?php
require_once 'includes/init.php';
require_once 'includes/recommendation.php';
require_role('student');

$userId = current_user_id();
$recommendations = get_recommendations($pdo, $userId);
$student = load_student_data($pdo, $userId);

// Save a snapshot of the current analysis into recommendation_history.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    verify_csrf();
    if (!$student['skills']) {
        flash('error', 'Add some skills to your profile before saving an analysis.');
        redirect('recommendations.php');
    }
    $now = $pdo->query("SELECT NOW()")->fetchColumn(); // one timestamp groups the whole run
    $stmt = $pdo->prepare(
        "INSERT INTO recommendation_history (user_id, career_id, compatibility_score, explanation, created_at)
         VALUES (?, ?, ?, ?, ?)"
    );
    $pdo->beginTransaction();
    foreach ($recommendations as $r) {
        $stmt->execute([$userId, $r['career']['id'], $r['score'], $r['explanation'], $now]);
    }
    $pdo->commit();
    flash('success', 'Analysis saved to your history. Your advisor can now see it.');
    redirect('recommendations.php#history');
}

// Previous saved runs (latest 5), each with its top 3 careers.
$stmt = $pdo->prepare(
    "SELECT created_at FROM recommendation_history WHERE user_id = ?
     GROUP BY created_at ORDER BY created_at DESC LIMIT 5"
);
$stmt->execute([$userId]);
$runs = $stmt->fetchAll(PDO::FETCH_COLUMN);

$history = [];
$topOfRun = $pdo->prepare(
    "SELECT h.compatibility_score, c.title FROM recommendation_history h
     JOIN careers c ON c.id = h.career_id
     WHERE h.user_id = ? AND h.created_at = ?
     ORDER BY h.compatibility_score DESC LIMIT 3"
);
foreach ($runs as $run) {
    $topOfRun->execute([$userId, $run]);
    $history[$run] = $topOfRun->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Career Matches';
$activePage = 'recommendations';
require 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Career Matches</h1>
        <p>Every career in our database, ranked by how well it fits your skills, interests, projects and CGPA.</p>
    </div>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <button type="submit" class="btn btn-primary">Save this analysis</button>
    </form>
</div>

<?php if (!$student['skills']): ?>
    <div class="alert alert-info">You have not added any skills yet, so all scores are low.
        <a href="profile.php#skills">Add your skills</a> to get meaningful recommendations.</div>
<?php endif; ?>

<details class="card">
    <summary>How is the compatibility percentage calculated?</summary>
    <div class="mt">
        <p><strong>Compatibility = 60% Skill match + 20% Interest match + 15% Project evidence + 5% Academic score</strong></p>
        <ul style="margin-left:20px">
            <li><strong>Skill match:</strong> for each required skill, your level ÷ required level (max 1), weighted by the skill's importance (1–5).</li>
            <li><strong>Interest match:</strong> interests found in the career's title, category, description or skills. One match = 50, two or more = 100.</li>
            <li><strong>Project evidence:</strong> percentage of required skills mentioned in your project titles, descriptions or technologies.</li>
            <li><strong>Academic:</strong> CGPA ÷ 4.00.</li>
        </ul>
    </div>
</details>

<?php foreach ($recommendations as $i => $r): ?>
    <div class="match">
        <div class="match-top">
            <div>
                <span class="rank"><?= $i + 1 ?></span>
                <span class="match-title"><?= e($r['career']['title']) ?></span>
                <span class="badge badge-gray"><?= e($r['career']['category']) ?></span>
            </div>
            <div class="text-right">
                <span class="match-score"><?= $r['score'] ?>%</span>
                <div class="small muted"><?= e(match_label($r['score'])) ?></div>
            </div>
        </div>
        <div class="progress"><div class="progress-bar <?= score_class($r['score']) ?>" style="width:<?= $r['score'] ?>%"></div></div>

        <div class="breakdown">
            <div><strong><?= $r['skill_score'] ?>%</strong><span>Skill match (60%)</span></div>
            <div><strong><?= $r['interest_score'] ?>%</strong><span>Interest match (20%)</span></div>
            <div><strong><?= $r['project_score'] ?>%</strong><span>Project evidence (15%)</span></div>
            <div><strong><?= $r['academic_score'] ?>%</strong><span>Academic (5%)</span></div>
        </div>

        <p class="mt"><?= e($r['explanation']) ?></p>

        <details>
            <summary>Required skills (<?= $r['met_count'] ?>/<?= $r['required_count'] ?> met)</summary>
            <div class="chips mt">
                <?php foreach ($r['skills'] as $s): ?>
                    <?php $class = ['met' => 'badge-green', 'improve' => 'badge-amber', 'missing' => 'badge-red'][$s['status']]; ?>
                    <span class="badge <?= $class ?>"><?= e($s['name']) ?> · <?= level_name($s['student_level']) ?>/<?= level_name($s['required_level']) ?></span>
                <?php endforeach; ?>
            </div>
            <p class="small muted mt"><?= e($r['career']['description']) ?><br>Average salary: <?= e($r['career']['average_salary']) ?></p>
        </details>

        <div class="btn-group mt">
            <a href="roadmap.php?career_id=<?= (int) $r['career']['id'] ?>" class="btn btn-secondary btn-sm">Skill gap &amp; roadmap</a>
            <a href="courses.php?career_id=<?= (int) $r['career']['id'] ?>" class="btn btn-secondary btn-sm">Courses</a>
            <a href="interview.php?career_id=<?= (int) $r['career']['id'] ?>" class="btn btn-secondary btn-sm">Interview questions</a>
        </div>
    </div>
<?php endforeach; ?>

<?php if (!$recommendations): ?>
    <div class="card empty">No careers have been added yet. Please ask an administrator.</div>
<?php endif; ?>

<div class="card mt" id="history">
    <h2>Saved analysis history</h2>
    <?php if (!$history): ?>
        <p class="muted">You have not saved an analysis yet. Click “Save this analysis” to track your progress over time.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Top 3 careers at that time</th></tr></thead>
                <tbody>
                <?php foreach ($history as $date => $rows): ?>
                    <tr>
                        <td><?= e(date('d M Y, H:i', strtotime($date))) ?></td>
                        <td>
                            <?php foreach ($rows as $row): ?>
                                <span class="badge badge-green"><?= e($row['title']) ?> · <?= (float) $row['compatibility_score'] ?>%</span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
