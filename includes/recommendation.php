<?php
/*
 * CareerNavigator recommendation engine.
 *
 * Every career gets a compatibility score from 0 to 100, made of four transparent parts:
 *
 *   1. Skill match      (60%)  For each skill the career requires:
 *                              credit = min(student level / required level, 1)
 *                              weighted by the skill's importance (1-5).
 *                              skill score = sum(importance x credit) / sum(importance) x 100
 *   2. Interest match   (20%)  Student interests found in the career's title, category,
 *                              description or skills. 1 match = 50, 2+ matches = 100.
 *   3. Project evidence (15%)  % of the career's required skills that appear in the
 *                              student's project titles, descriptions or technologies.
 *   4. Academic         (5%)   CGPA / 4.00 x 100.
 *
 *   compatibility = 0.60 x skill + 0.20 x interest + 0.15 x project + 0.05 x academic
 */

const WEIGHT_SKILLS    = 0.60;
const WEIGHT_INTERESTS = 0.20;
const WEIGHT_PROJECTS  = 0.15;
const WEIGHT_ACADEMIC  = 0.05;

// Extra words that count as evidence of a skill in project descriptions.
const SKILL_ALIASES = [
    'HTML & CSS' => ['html', 'css', 'html5', 'css3'],
    'JavaScript' => ['javascript', 'js', 'typescript'],
    'React' => ['react', 'reactjs', 'react.js'],
    'Responsive Design' => ['responsive', 'bootstrap', 'tailwind', 'media queries'],
    'PHP' => ['php', 'laravel'],
    'Python' => ['python', 'django', 'flask', 'pandas', 'numpy'],
    'Java' => ['java', 'spring'],
    'Kotlin' => ['kotlin'],
    'Flutter/Dart' => ['flutter', 'dart'],
    'SQL' => ['sql', 'mysql', 'postgresql', 'sqlite', 'mariadb'],
    'Database Design' => ['database', 'mysql', 'postgresql', 'erd', 'schema'],
    'REST APIs' => ['rest', 'api', 'apis', 'json'],
    'Object-Oriented Programming' => ['oop', 'object-oriented', 'object oriented'],
    'Data Structures & Algorithms' => ['algorithm', 'algorithms', 'data structures', 'dsa'],
    'Git & Version Control' => ['git', 'github', 'gitlab'],
    'Linux' => ['linux', 'ubuntu', 'bash', 'shell'],
    'Networking' => ['network', 'networking', 'tcp/ip', 'cisco'],
    'Cloud Computing' => ['cloud', 'aws', 'azure', 'gcp', 'firebase'],
    'Docker' => ['docker', 'container', 'containers', 'kubernetes'],
    'CI/CD' => ['ci/cd', 'github actions', 'jenkins', 'pipeline'],
    'Cybersecurity Fundamentals' => ['security', 'cybersecurity', 'encryption', 'penetration'],
    'Statistics' => ['statistics', 'statistical', 'regression'],
    'Excel' => ['excel', 'spreadsheet', 'spreadsheets'],
    'Data Visualization' => ['visualization', 'visualisation', 'chart', 'charts', 'dashboard', 'tableau', 'power bi', 'matplotlib'],
    'Machine Learning' => ['machine learning', 'ml', 'scikit-learn', 'prediction', 'classification'],
    'Deep Learning' => ['deep learning', 'neural network', 'tensorflow', 'pytorch', 'cnn'],
    'UI/UX Design' => ['ui', 'ux', 'user interface', 'wireframe', 'prototype'],
    'Figma' => ['figma', 'adobe xd'],
    'Software Testing' => ['testing', 'selenium', 'phpunit', 'jest', 'unit test'],
    'Business Analysis' => ['requirements', 'business analysis', 'stakeholder'],
    'Project Management' => ['project management', 'scrum', 'agile', 'jira'],
    'Communication' => ['presentation', 'documentation', 'communication'],
    'Problem Solving' => ['problem solving', 'algorithm', 'competitive programming'],
    'Teamwork' => ['team', 'teamwork', 'group project'],
];

// Does $term appear in $text as a whole word/phrase? (case-insensitive)
function text_contains(string $text, string $term): bool
{
    $term = trim($term);
    if ($term === '') {
        return false;
    }
    return preg_match('/(?<![a-z0-9])' . preg_quote(strtolower($term), '/') . '(?![a-z0-9])/i', $text) === 1;
}

// Words that represent a skill when searching project text.
function skill_terms(string $skillName): array
{
    if (isset(SKILL_ALIASES[$skillName])) {
        return SKILL_ALIASES[$skillName];
    }
    // Fallback: split names such as "Git & Version Control" into parts.
    $parts = preg_split('/\s*(?:&|\/|,|\band\b)\s*/i', strtolower($skillName));
    return array_filter(array_map('trim', $parts));
}

// Split the comma-separated interests text into a clean list.
function parse_interests(?string $text): array
{
    $items = preg_split('/[,;\n]+/', (string) $text);
    $items = array_map('trim', $items);
    return array_values(array_unique(array_filter($items, fn($i) => strlen($i) >= 2)));
}

// Load everything about a student that the algorithm needs.
function load_student_data(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare(
        "SELECT ss.skill_id, ss.proficiency, s.name
         FROM student_skills ss JOIN skills s ON s.id = ss.skill_id
         WHERE ss.user_id = ?"
    );
    $stmt->execute([$userId]);
    $skills = [];
    foreach ($stmt as $row) {
        $skills[(int) $row['skill_id']] = ['name' => $row['name'], 'level' => level_number($row['proficiency'])];
    }

    $stmt = $pdo->prepare("SELECT title, description, technologies FROM projects WHERE user_id = ?");
    $stmt->execute([$userId]);
    $projectText = '';
    $projectCount = 0;
    foreach ($stmt as $row) {
        $projectText .= ' ' . $row['title'] . ' ' . $row['description'] . ' ' . $row['technologies'];
        $projectCount++;
    }

    return [
        'profile'       => $profile,
        'skills'        => $skills,
        'interests'     => parse_interests($profile['interests'] ?? ''),
        'cgpa'          => isset($profile['cgpa']) ? (float) $profile['cgpa'] : null,
        'project_text'  => strtolower($projectText),
        'project_count' => $projectCount,
    ];
}

// All careers with their required skills (loaded once per request).
function load_careers_with_skills(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $careers = [];
    foreach ($pdo->query("SELECT * FROM careers ORDER BY title") as $row) {
        $row['skills'] = [];
        $careers[(int) $row['id']] = $row;
    }

    $rows = $pdo->query(
        "SELECT cs.career_id, cs.skill_id, cs.required_level, cs.importance, s.name, s.category
         FROM career_skills cs JOIN skills s ON s.id = cs.skill_id
         ORDER BY cs.importance DESC, s.name"
    );
    foreach ($rows as $row) {
        if (isset($careers[(int) $row['career_id']])) {
            $careers[(int) $row['career_id']]['skills'][] = $row;
        }
    }

    return $cache = $careers;
}

// Score one career for one student and explain the result.
function analyze_career(array $career, array $student): array
{
    // 1. Skill match
    $totalWeight = 0;
    $earned = 0;
    $skillRows = [];
    $met = 0;
    $projectEvidence = [];

    foreach ($career['skills'] as $req) {
        $required = max(1, (int) $req['required_level']);
        $importance = max(1, (int) $req['importance']);
        $have = $student['skills'][(int) $req['skill_id']]['level'] ?? 0;

        $credit = min($have / $required, 1);
        $totalWeight += $importance;
        $earned += $importance * $credit;

        if ($have >= $required) {
            $status = 'met';
            $met++;
        } elseif ($have > 0) {
            $status = 'improve';
        } else {
            $status = 'missing';
        }

        // 3. Project evidence for this skill
        $inProjects = false;
        foreach (skill_terms($req['name']) as $term) {
            if (text_contains($student['project_text'], $term)) {
                $inProjects = true;
                break;
            }
        }
        if ($inProjects) {
            $projectEvidence[] = $req['name'];
        }

        $skillRows[] = [
            'skill_id'       => (int) $req['skill_id'],
            'name'           => $req['name'],
            'category'       => $req['category'],
            'required_level' => $required,
            'importance'     => $importance,
            'student_level'  => $have,
            'status'         => $status,
            'in_projects'    => $inProjects,
        ];
    }

    $requiredCount = count($career['skills']);
    $skillScore = $totalWeight > 0 ? ($earned / $totalWeight) * 100 : 0;
    $projectScore = $requiredCount > 0 ? (count($projectEvidence) / $requiredCount) * 100 : 0;

    // 2. Interest match
    $haystack = strtolower($career['title'] . ' ' . $career['category'] . ' ' . $career['description'] . ' '
        . implode(' ', array_column($career['skills'], 'name')));
    $matchedInterests = [];
    foreach ($student['interests'] as $interest) {
        if (text_contains($haystack, $interest)) {
            $matchedInterests[] = $interest;
        }
    }
    $interestScore = min(count($matchedInterests), 2) / 2 * 100;

    // 4. Academic performance
    $academicScore = $student['cgpa'] !== null ? min($student['cgpa'] / 4, 1) * 100 : 0;

    $score = WEIGHT_SKILLS * $skillScore
        + WEIGHT_INTERESTS * $interestScore
        + WEIGHT_PROJECTS * $projectScore
        + WEIGHT_ACADEMIC * $academicScore;
    $score = round($score, 1);

    // Plain-language explanation
    $parts = [];
    $parts[] = "You fully meet $met of $requiredCount required skills (skill match " . round($skillScore) . "%).";
    $parts[] = $matchedInterests
        ? 'Your interests in ' . implode(', ', $matchedInterests) . ' relate to this career.'
        : 'None of your listed interests directly relate to this career.';
    $parts[] = $projectEvidence
        ? 'Your projects demonstrate ' . implode(', ', $projectEvidence) . '.'
        : 'Your projects do not yet show skills required for this career.';
    if ($student['cgpa'] !== null) {
        $parts[] = 'Your CGPA of ' . number_format($student['cgpa'], 2) . ' adds ' . round(WEIGHT_ACADEMIC * $academicScore, 1) . ' points.';
    }

    return [
        'career'            => $career,
        'score'             => $score,
        'skill_score'       => round($skillScore, 1),
        'interest_score'    => round($interestScore, 1),
        'project_score'     => round($projectScore, 1),
        'academic_score'    => round($academicScore, 1),
        'skills'            => $skillRows,
        'met_count'         => $met,
        'required_count'    => $requiredCount,
        'matched_interests' => $matchedInterests,
        'project_skills'    => $projectEvidence,
        'explanation'       => implode(' ', $parts),
    ];
}

// Rank every career for a student, best match first.
// Pass $student if the page has already loaded it, so it is not loaded twice.
function get_recommendations(PDO $pdo, int $userId, ?array $student = null): array
{
    $student = $student ?? load_student_data($pdo, $userId);
    $results = [];
    foreach (load_careers_with_skills($pdo) as $career) {
        $results[] = analyze_career($career, $student);
    }
    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    return $results;
}

// Courses that teach each of the given skills: [skill_id => [course, ...]]
function courses_for_skills(PDO $pdo, array $skillIds): array
{
    $skillIds = array_values(array_unique(array_map('intval', $skillIds)));
    if (!$skillIds) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE skill_id IN ($placeholders) ORDER BY title");
    $stmt->execute($skillIds);
    $bySkill = [];
    foreach ($stmt as $course) {
        $bySkill[(int) $course['skill_id']][] = $course;
    }
    return $bySkill;
}

function match_label(float $score): string
{
    if ($score >= 75) return 'Strong match';
    if ($score >= 50) return 'Good match';
    if ($score >= 30) return 'Potential match';
    return 'Low match';
}

// How complete a student's profile is (0-100), with tips for what is missing.
function profile_completeness(array $student): array
{
    $p = $student['profile'];
    $checks = [
        'Add your university and department' => !empty($p['university']) && !empty($p['department']),
        'Add your CGPA'                       => $student['cgpa'] !== null,
        'Add at least two interests'          => count($student['interests']) >= 2,
        'Add at least five skills'            => count($student['skills']) >= 5,
        'Add at least one project'            => $student['project_count'] >= 1,
    ];
    $done = count(array_filter($checks));
    $tips = array_keys(array_filter($checks, fn($ok) => !$ok));
    return ['percent' => (int) round($done / count($checks) * 100), 'tips' => $tips];
}
