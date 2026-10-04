<?php
require_once 'includes/init.php';
require_once 'includes/recommendation.php';
require_role('student', 'advisor', 'admin');

// Only accept plain text for the search box (e.g. ignore ?q[]=...)
$q = isset($_GET['q']) && is_string($_GET['q']) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$skillFilter = input_int($_GET, 'skill_id');
$careerFilter = input_int($_GET, 'career_id');

$skills = $pdo->query("SELECT id, name FROM skills ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$careerList = $pdo->query("SELECT id, title FROM careers ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Ignore ids that do not exist, so the dropdowns always show the filter that is really applied
if (!in_array($skillFilter, array_map('intval', array_column($skills, 'id')), true)) {
    $skillFilter = 0;
}
if (!in_array($careerFilter, array_map('intval', array_column($careerList, 'id')), true)) {
    $careerFilter = 0;
}

// ===== Course Search =====
// Build the query with only the filters that were used
$where = [];
$params = [];
if ($q !== '') {
    // Every word must appear in the title, provider, description or skill name, in any order.
    // LIKE is case-insensitive because the tables use a *_ci collation.
    foreach (array_slice(preg_split('/\s+/', $q), 0, 10) as $word) {
        $like = '%' . addcslashes($word, '\\%_') . '%'; // treat % and _ as normal characters
        $where[] = "(c.title LIKE ? OR c.provider LIKE ? OR c.description LIKE ? OR s.name LIKE ?)";
        array_push($params, $like, $like, $like, $like);
    }
}
if ($skillFilter) {
    $where[] = "c.skill_id = ?";
    $params[] = $skillFilter;
}
if ($careerFilter) {
    $where[] = "c.id IN (SELECT course_id FROM career_courses WHERE career_id = ?)";
    $params[] = $careerFilter;
}

$sql = "SELECT c.*, s.name AS skill_name,
               GROUP_CONCAT(DISTINCT ca.title ORDER BY ca.title SEPARATOR ', ') AS career_titles
        FROM courses c
        LEFT JOIN skills s ON s.id = c.skill_id
        LEFT JOIN career_courses cc ON cc.course_id = c.id
        LEFT JOIN careers ca ON ca.id = cc.career_id"
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . " GROUP BY c.id ORDER BY c.title";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Personal Suggestions (students only) =====
// Based on the skill gaps of the student's top 3 careers
$recommended = [];
$gapSkillIds = [];
if (current_user()['role'] === 'student') {
    foreach (array_slice(get_recommendations($pdo, current_user_id()), 0, 3) as $r) {
        foreach ($r['skills'] as $s) {
            if ($s['status'] !== 'met') {
                $gapSkillIds[$s['skill_id']] = true;
            }
        }
    }
    foreach (courses_for_skills($pdo, array_keys($gapSkillIds)) as $list) {
        $recommended[] = $list[0]; // one course per missing skill
    }
    $recommended = array_slice($recommended, 0, 6);
}

$pageTitle = 'Courses & Certifications';
$activePage = 'courses';
require 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Courses &amp; Certifications</h1>
        <p>Learning resources mapped to the skills and careers in CareerNavigator.</p>
    </div>
</div>

<?php if ($recommended && !$q && !$skillFilter && !$careerFilter): ?>
    <div class="card">
        <h2>Recommended for you</h2>
        <p class="muted">Based on missing or weak skills for your top 3 career matches.</p>
        <div class="grid grid-3">
            <?php foreach ($recommended as $c): ?>
                <div class="match course-card" style="margin:0">
                    <strong><?= e($c['title']) ?></strong>
                    <p class="small muted"><?= e($c['provider']) ?></p>
                    <a href="<?= e($c['course_url']) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Open course ↗</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<form method="GET" class="filters card">
    <div class="form-group">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Title, provider or keyword" maxlength="100">
    </div>
    <div class="form-group">
        <label for="skill_id">Skill</label>
        <select id="skill_id" name="skill_id">
            <option value="">All skills</option>
            <?php foreach ($skills as $s): ?>
                <option value="<?= (int) $s['id'] ?>" <?= $skillFilter === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="career_id">Career</label>
        <select id="career_id" name="career_id">
            <option value="">All careers</option>
            <?php foreach ($careerList as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $careerFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="btn-group">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="courses.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<p class="muted mb"><?= count($courses) ?> <?= count($courses) === 1 ? 'course' : 'courses' ?> found.</p>

<?php if (!$courses): ?>
    <div class="card empty">No courses match your search and filters. Try different keywords or <a href="courses.php">reset the filters</a>.</div>
<?php endif; ?>

<div class="grid grid-3">
    <?php foreach ($courses as $c): ?>
        <div class="card course-card">
            <div class="course-meta">
                <?php if ($c['skill_name']): ?>
                    <span class="badge <?= isset($gapSkillIds[$c['skill_id']]) ? 'badge-amber' : 'badge-green' ?>"><?= e($c['skill_name']) ?></span>
                <?php endif; ?>
                <?php if (isset($gapSkillIds[$c['skill_id']])): ?><span class="badge badge-dark">Fills a skill gap</span><?php endif; ?>
            </div>
            <h3><?= e($c['title']) ?></h3>
            <div class="small muted mb"><?= e($c['provider']) ?></div>
            <p class="small"><?= e($c['description']) ?></p>
            <?php if ($c['career_titles']): ?>
                <p class="small muted">Useful for: <?= e($c['career_titles']) ?></p>
            <?php endif; ?>
            <?php if ($c['course_url']): ?>
                <a href="<?= e($c['course_url']) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Open course ↗</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php require 'includes/footer.php'; ?>
