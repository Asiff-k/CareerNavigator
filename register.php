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

    // ===== Form Validation =====
    if ($name === '' || $email === '' || $password === '') {
        $message = 'Please fill in all fields.';
    } elseif (mb_strlen($name) > 100) {
        $message = 'Name must be 100 characters or fewer.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $message = 'Password must contain at least ' . MIN_PASSWORD_LENGTH . ' characters.';
    } elseif ($password !== $confirm) {
        $message = 'The two passwords do not match.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = 'This email is already registered.';
        } else {
            // ===== Save User Information =====
            // Public registration always creates a student account
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, 'student')"
            );
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $newUser = ['id' => (int) $pdo->lastInsertId(), 'name' => $name, 'email' => $email];

            // ===== Send the Verification Email =====
            // The account cannot sign in until the link in this email is opened
            if (send_verification_email($pdo, $newUser)) {
                flash('success', 'Account created! We sent a verification link to ' . $email
                    . '. Please open it to activate your account, then sign in.');
            } else {
                flash('error', 'Your account was created, but we could not send the verification email to '
                    . $email . '. Please use the "Resend it" link below to try again.');
            }

            // ===== Redirect After Registration =====
            redirect('login.php');
        }
    }
}

$pageTitle = 'Create Account';
require 'includes/auth_header.php';
?>
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
                <input type="password" id="password" name="password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required>
                <div class="help">At least <?= MIN_PASSWORD_LENGTH ?> characters.</div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="<?= MIN_PASSWORD_LENGTH ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <p class="auth-links">Already have an account? <a href="login.php">Sign in</a><br>
            <a href="index.php">&larr; Back to Home</a></p>
<?php require 'includes/auth_footer.php'; ?>
