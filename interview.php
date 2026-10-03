<?php
require_once 'includes/init.php';
require_role('student', 'advisor', 'admin');

$userId = current_user_id();
$isStudent = current_user()['role'] === 'student';

// Common words ignored when comparing answers.
const STOPWORDS = ['about', 'after', 'also', 'such', 'than', 'that', 'their', 'them', 'then', 'there', 'these', 'they',
    'this', 'those', 'when', 'where', 'which', 'while', 'with', 'what', 'your', 'from', 'have', 'into', 'more', 'most',
    'only', 'other', 'over', 'same', 'should', 'some', 'very', 'will', 'would', 'each', 'every', 'make', 'makes',
    'using', 'used', 'uses', 'does', 'been', 'being', 'were', 'example', 'usually', 'often', 'like', 'just', 'both'];

// Important words of a text: lowercase, 4+ letters, not a stopword, unique.
function keywords(string $text): array
{
    preg_match_all('/[a-z][a-z0-9+#\/-]{3,}/', strtolower($text), $m);
    return array_values(array_unique(array_diff($m[0], STOPWORDS)));
}

// Compare the student's answer with the model answer.
// A keyword counts as covered if the answer contains it (or a word with the same first 6 letters,
// so "normalise" matches "normalization").
function evaluate_answer(string $answer, string $modelAnswer): array
{
    $expected = keywords($modelAnswer);
    $given = keywords($answer);
    $matched = [];
    $missed = [];
    foreach ($expected as $word) {
        $found = in_array($word, $given, true);
        if (!$found && strlen($word) >= 6) {
            foreach ($given as $g) {
                if (strlen($g) >= 6 && substr($g, 0, 6) === substr($word, 0, 6)) {
                    $found = true;
                    break;
                }
            }
        }
        if ($found) {
            $matched[] = $word;
        } else {
            $missed[] = $word;
        }
    }
    $score = $expected ? round(count($matched) / count($expected) * 100, 1) : 0;
    return ['score' => $score, 'matched' => $matched, 'missed' => $missed];
}

// ---------- Submit a practice answer (students only) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$isStudent) {
        flash('error', 'Only students can submit practice answers.');
        redirect('interview.php');
    }
    $questionId = input_int($_POST, 'question_id');
    $answer = trim($_POST['answer'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM interview_questions WHERE id = ?");
    $stmt->execute([$questionId]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        flash('error', 'Question not found.');
        redirect('interview.php');
    }
    if ($answer === '' || mb_strlen($answer) > 5000) {
        flash('error', 'Please write an answer (maximum 5000 characters).');
        redirect('interview.php?practice=1&question_id=' . $questionId);
    }

    $result = evaluate_answer($answer, (string) $question['answer']);
    $stmt = $pdo->prepare("INSERT INTO interview_attempts (user_id, question_id, answer, score) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $questionId, $answer, $result['score']]);
    redirect('interview.php?result=' . $pdo->lastInsertId());
}

$careers = $pdo->query("SELECT id, title FROM careers ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
$careerId = input_int($_GET, 'career_id');
$difficulty = in_array($_GET['difficulty'] ?? '', ['easy', 'medium', 'hard'], true) ? $_GET['difficulty'] : '';

// Decide which view to show: result, practice or question bank.
$view = 'bank';
$question = null;
$attempt = null;

if ($isStudent && isset($_GET['result'])) {
    $stmt = $pdo->prepare(
        "SELECT a.*, q.question, q.answer AS model_answer, q.difficulty, q.career_id, c.title AS career_title
         FROM interview_attempts a
         JOIN interview_questions q ON q.id = a.question_id
         LEFT JOIN careers c ON c.id = q.career_id
         WHERE a.id = ? AND a.user_id = ?"
    );
    $stmt->execute([input_int($_GET, 'result'), $userId]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($attempt) {
        $view = 'result';
        $evaluation = evaluate_answer($attempt['answer'], (string) $attempt['model_answer']);
    }
} elseif ($isStudent && isset($_GET['practice'])) {
    $view = 'practice';
    if (isset($_GET['question_id'])) {
        $stmt = $pdo->prepare(
            "SELECT q.*, c.title AS career_title FROM interview_questions q
             LEFT JOIN careers c ON c.id = q.career_id WHERE q.id = ?"
        );
        $stmt->execute([input_int($_GET, 'question_id')]);
    } else {
        // Random question for the chosen career, preferring ones not yet practised.
        $sql = "SELECT q.*, c.title AS career_title,
                       (SELECT COUNT(*) FROM interview_attempts a WHERE a.question_id = q.id AND a.user_id = ?) AS times
                FROM interview_questions q LEFT JOIN careers c ON c.id = q.career_id
                WHERE 1 = 1";
        $params = [$userId];
        if ($careerId) {
            $sql .= " AND (q.career_id = ? OR q.career_id IS NULL)";
            $params[] = $careerId;
        }
        if ($difficulty) {
            $sql .= " AND q.difficulty = ?";
            $params[] = $difficulty;
        }
        $sql .= " ORDER BY times ASC, RAND() LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Question bank listing
if ($view === 'bank') {
    $sql = "SELECT q.*, c.title AS career_title FROM interview_questions q LEFT JOIN careers c ON c.id = q.career_id WHERE 1 = 1";
    $params = [];
    if ($careerId) {
        $sql .= " AND q.career_id = ?";
        $params[] = $careerId;
    }
    if ($difficulty) {
        $sql .= " AND q.difficulty = ?";
        $params[] = $difficulty;
    }
    $sql .= " ORDER BY c.title IS NULL DESC, c.title, FIELD(q.difficulty, 'easy', 'medium', 'hard')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $recentAttempts = [];
    if ($isStudent) {
        $stmt = $pdo->prepare(
            "SELECT a.id, a.score, a.created_at, q.question FROM interview_attempts a
             JOIN interview_questions q ON q.id = a.question_id
             WHERE a.user_id = ? ORDER BY a.created_at DESC, a.id DESC LIMIT 10"
        );
        $stmt->execute([$userId]);
        $recentAttempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$difficultyBadge = ['easy' => 'badge-green', 'medium' => 'badge-amber', 'hard' => 'badge-red'];
$practiceLink = 'interview.php?practice=1' . ($careerId ? '&career_id=' . $careerId : '') . ($difficulty ? '&difficulty=' . $difficulty : '');

$pageTitle = $isStudent ? 'Interview Practice' : 'Question Bank';
$activePage = 'interview';
require 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?= $isStudent ? 'Interview Practice' : 'Interview Question Bank' ?></h1>
        <p>Career-specific interview questions with model answers<?= $isStudent ? '. Practise and get instant keyword-based feedback.' : '.' ?></p>
    </div>
    <?php if ($isStudent && $view !== 'practice'): ?>
        <a href="<?= e($practiceLink) ?>" class="btn btn-primary">Start practice session</a>
    <?php endif; ?>
</div>

<?php if ($view === 'practice'): ?>
    <?php if (!$question): ?>
        <div class="card empty">No questions found for this selection. <a href="interview.php">Back to question bank</a></div>
    <?php else: ?>
        <div class="card">
            <div class="course-meta">
                <span class="badge badge-gray"><?= e($question['career_title'] ?? 'General') ?></span>
                <span class="badge <?= $difficultyBadge[$question['difficulty']] ?>"><?= ucfirst(e($question['difficulty'])) ?></span>
            </div>
            <h2><?= e($question['question']) ?></h2>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="question_id" value="<?= (int) $question['id'] ?>">
                <div class="form-group">
                    <label for="answer">Your answer</label>
                    <textarea id="answer" name="answer" maxlength="5000" rows="8" required placeholder="Answer as you would in a real interview…"></textarea>
                    <div class="help" id="answer-count">0 words</div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Submit answer</button>
                    <a href="<?= e($practiceLink) ?>" class="btn btn-secondary">Skip question</a>
                    <a href="interview.php" class="btn btn-secondary">End session</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

<?php elseif ($view === 'result'): ?>
    <div class="card">
        <div class="card-header">
            <div class="course-meta" style="margin:0">
                <span class="badge badge-gray"><?= e($attempt['career_title'] ?? 'General') ?></span>
                <span class="badge <?= $difficultyBadge[$attempt['difficulty']] ?>"><?= ucfirst(e($attempt['difficulty'])) ?></span>
            </div>
            <span class="match-score"><?= (float) $attempt['score'] ?>%</span>
        </div>
        <h2><?= e($attempt['question']) ?></h2>
        <div class="progress mb"><div class="progress-bar <?= $attempt['score'] >= 60 ? '' : ($attempt['score'] >= 35 ? 'mid' : 'low') ?>" style="width:<?= (float) $attempt['score'] ?>%"></div></div>

        <div class="grid grid-2">
            <div>
                <h3>Your answer</h3>
                <div class="note"><?= nl2br(e($attempt['answer'])) ?></div>
            </div>
            <div>
                <h3>Model answer</h3>
                <div class="note"><?= nl2br(e($attempt['model_answer'])) ?></div>
            </div>
        </div>

        <h3 class="mt">Key points covered</h3>
        <div class="chips mb">
            <?php foreach ($evaluation['matched'] as $w): ?><span class="badge badge-green">✓ <?= e($w) ?></span><?php endforeach; ?>
            <?php if (!$evaluation['matched']): ?><span class="muted small">None yet.</span><?php endif; ?>
        </div>
        <h3>Key points to add</h3>
        <div class="chips mb">
            <?php foreach ($evaluation['missed'] as $w): ?><span class="badge badge-red"><?= e($w) ?></span><?php endforeach; ?>
            <?php if (!$evaluation['missed']): ?><span class="muted small">You covered every key point. Excellent!</span><?php endif; ?>
        </div>
        <p class="small muted">The score is the percentage of key terms from the model answer that appear in your answer. Use it as a guide, not a final grade.</p>

        <div class="btn-group mt">
            <a href="interview.php?practice=1<?= $attempt['career_id'] ? '&amp;career_id=' . (int) $attempt['career_id'] : '' ?>" class="btn btn-primary">Next question</a>
            <a href="interview.php?practice=1&amp;question_id=<?= (int) $attempt['question_id'] ?>" class="btn btn-secondary">Try again</a>
            <a href="interview.php" class="btn btn-secondary">Back to question bank</a>
        </div>
    </div>

<?php else: ?>
    <form method="GET" class="filters card">
        <div class="form-group">
            <label for="career_id">Career</label>
            <select id="career_id" name="career_id">
                <option value="">All careers</option>
                <?php foreach ($careers as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $careerId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="difficulty">Difficulty</label>
            <select id="difficulty" name="difficulty">
                <option value="">All levels</option>
                <?php foreach (['easy', 'medium', 'hard'] as $d): ?>
                    <option value="<?= $d ?>" <?= $difficulty === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="interview.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <div class="card">
        <h2><?= count($questions) ?> question(s)</h2>
        <?php if (!$questions): ?><p class="muted">No questions match your filters.</p><?php endif; ?>
        <ul class="list-plain">
            <?php foreach ($questions as $qn): ?>
                <li>
                    <div class="course-meta">
                        <span class="badge badge-gray"><?= e($qn['career_title'] ?? 'General') ?></span>
                        <span class="badge <?= $difficultyBadge[$qn['difficulty']] ?>"><?= ucfirst(e($qn['difficulty'])) ?></span>
                    </div>
                    <strong><?= e($qn['question']) ?></strong>
                    <details>
                        <summary>Show model answer</summary>
                        <p class="mt"><?= nl2br(e($qn['answer'])) ?></p>
                    </details>
                    <?php if ($isStudent): ?>
                        <a href="interview.php?practice=1&amp;question_id=<?= (int) $qn['id'] ?>" class="btn btn-secondary btn-sm mt">Practise this question</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($isStudent): ?>
        <div class="card">
            <h2>My recent practice</h2>
            <?php if (!$recentAttempts): ?>
                <p class="muted">You have not practised any questions yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Question</th><th>Score</th><th>Date</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($recentAttempts as $a): ?>
                            <tr>
                                <td><?= e($a['question']) ?></td>
                                <td><strong><?= (float) $a['score'] ?>%</strong></td>
                                <td class="muted small"><?= e(date('d M Y, H:i', strtotime($a['created_at']))) ?></td>
                                <td><a href="interview.php?result=<?= (int) $a['id'] ?>" class="btn btn-secondary btn-sm">Review</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
