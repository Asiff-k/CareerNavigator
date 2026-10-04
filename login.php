<?php
require_once 'includes/init.php';

// Already signed in? Go to the dashboard.
if (is_logged_in()) {
    redirect(home_for_role(current_user()['role']));
}

$message = '';
$unverifiedEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = 'Please enter your email and password.';
    } else {
        // ===== Check Email and Password =====
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // ===== Check Email Verification =====
            // Only shown after a correct password, so it does not reveal which emails are registered
            if ($user['email_verified_at'] === null) {
                $unverifiedEmail = $user['email'];
                $message = 'Please verify your email address before signing in. Check your inbox for the verification link.';
            } else {
                login_user($user);
                flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect(home_for_role($user['role']));
            }
        } else {
            // Same message for an unknown email and a wrong password
            $message = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Sign In';
require 'includes/auth_header.php';
?>
        <h1>Welcome back</h1>
        <p class="muted mb">Sign in to continue your career journey.</p>

        <?php foreach (get_flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
        <?php if ($message): ?>
            <div class="alert alert-error"><?= e($message) ?>
                <?php if ($unverifiedEmail): ?>
                    <br><a href="resend_verification.php?email=<?= e(urlencode($unverifiedEmail)) ?>">Resend verification email</a>
                <?php endif; ?>
            </div>
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
            Didn't get the verification email? <a href="resend_verification.php">Resend it</a><br>
            <a href="index.php">&larr; Back to Home</a></p>

        <div class="demo-box">
            <strong>Demo accounts</strong><br>
            Student: student@careernavigator.com / student123<br>
            Advisor: advisor@careernavigator.com / advisor123<br>
            Admin: admin@careernavigator.com / admin123
        </div>
<?php require 'includes/auth_footer.php'; ?>
