<?php
/*
 * Shared page layout (top part). Set $pageTitle and $activePage before including.
 * The sidebar links depend on the logged-in user's role.
 */
$user = current_user();
$activePage = $activePage ?? '';

$menus = [
    'student' => [
        'dashboard'       => ['dashboard.php', 'Dashboard', '◎'],
        'profile'         => ['profile.php', 'My Profile & Skills', '◉'],
        'projects'        => ['projects.php', 'My Projects', '▣'],
        'recommendations' => ['recommendations.php', 'Career Matches', '↗'],
        'roadmap'         => ['roadmap.php', 'Skill Gap & Roadmap', '▤'],
        'courses'         => ['courses.php', 'Courses & Certifications', '✧'],
        'interview'       => ['interview.php', 'Interview Practice', '✎'],
    ],
    'advisor' => [
        'advisor'   => ['advisor/index.php', 'My Students', '◎'],
        'courses'   => ['courses.php', 'Courses', '✧'],
        'interview' => ['interview.php', 'Question Bank', '✎'],
    ],
    'admin' => [
        'admin'      => ['admin/index.php', 'Admin Dashboard', '◎'],
        'users'      => ['admin/users.php', 'Users', '◉'],
        'careers'    => ['admin/careers.php', 'Careers', '↗'],
        'skills'     => ['admin/skills.php', 'Skills', '▣'],
        'courses'    => ['admin/courses.php', 'Courses', '✧'],
        'questions'  => ['admin/questions.php', 'Interview Questions', '✎'],
        'advisor'    => ['advisor/index.php', 'Student Overview', '▤'],
    ],
];
$menu = $user ? ($menus[$user['role']] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'CareerNavigator') ?> | CareerNavigator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Bricolage+Grotesque:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-top">
            <a href="<?= e(url('index.php')) ?>" class="logo logo-light">Career<span>Navigator.</span></a>
            <button class="menu-toggle" type="button" data-toggle-menu aria-label="Toggle menu">☰</button>
        </div>
        <nav class="side-nav" id="side-nav">
            <?php foreach ($menu as $key => [$link, $label, $icon]): ?>
                <a href="<?= e(url($link)) ?>" class="<?= $key === $activePage ? 'active' : '' ?>">
                    <span class="nav-icon"><?= $icon ?></span><?= e($label) ?>
                </a>
            <?php endforeach; ?>
            <?php if ($user): ?>
                <div class="side-user">
                    <div class="avatar"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></div>
                    <div>
                        <strong><?= e($user['name']) ?></strong>
                        <small><?= e(ucfirst($user['role'])) ?></small>
                    </div>
                </div>
                <a href="<?= e(url('logout.php')) ?>" class="logout-link"><span class="nav-icon">⇥</span>Sign out</a>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="main">
        <?php foreach (get_flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
