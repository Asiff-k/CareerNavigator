<?php
require_once 'includes/init.php';

// Already signed in? Go to the dashboard.
if (is_logged_in()) {
    redirect(home_for_role(current_user()['role']));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $message = 'Please fill in all fields.';
    } elseif (mb_strlen($name) > 100) {
        $message = 'Name must be 100 characters or fewer.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must contain at least 6 characters.';
    } elseif ($password !== $confirm) {
        $message = 'The two passwords do not match.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = 'This email is already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Public registration always creates a student account.
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, 'student')"
            );
            $stmt->execute([$name, $email, $hashedPassword]);

            flash('success', 'Account created successfully! Please sign in.');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | CareerNavigator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Bricolage+Grotesque:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <a href="index.php" class="logo">Career<span>Navigator.</span></a>
        <h1>Join CareerNavigator</h1>
        <p class="muted mb">Create your student account and discover your career path.</p>

        <?php if ($message): ?>
            <div class="alert alert-error"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="POST" data-password-match>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" maxlength="100"
                       value="<?= e($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" maxlength="150"
                       value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="6" required>
                <div class="help">At least 6 characters.</div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <p class="auth-links">Already have an account? <a href="login.php">Sign in</a><br>
            <a href="index.php">&larr; Back to Home</a></p>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
