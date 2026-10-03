<?php
require_once 'includes/init.php';

if (is_logged_in()) {
    redirect(home_for_role(current_user()['role']));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // password_verify compares the password with the stored hash.
        if ($user && password_verify($password, $user['password'])) {
            login_user($user);
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(home_for_role($user['role']));
        }
        // Same message for unknown email and wrong password, so attackers cannot discover accounts.
        $message = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | CareerNavigator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Bricolage+Grotesque:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <a href="index.php" class="logo">Career<span>Navigator.</span></a>
        <h1>Welcome back</h1>
        <p class="muted mb">Sign in to continue your career journey.</p>

        <?php foreach (get_flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
        <?php if ($message): ?>
            <div class="alert alert-error"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>

        <p class="auth-links">New to CareerNavigator? <a href="register.php">Create an account</a><br>
            <a href="index.php">&larr; Back to Home</a></p>

        <div class="demo-box">
            <strong>Demo accounts</strong><br>
            Student: student@careernavigator.com / student123<br>
            Advisor: advisor@careernavigator.com / advisor123<br>
            Admin: admin@careernavigator.com / admin123
        </div>
    </div>
</div>
</body>
</html>
