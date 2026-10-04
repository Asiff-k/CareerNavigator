<?php
require_once 'includes/init.php';

// Already signed in? Go to the dashboard.
if (is_logged_in()) {
    redirect(home_for_role(current_user()['role']));
}

$message = '';
$email = trim($_GET['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        // ===== Find the Account =====
        $stmt = $pdo->prepare(
            "SELECT id, name, email, email_verified_at,
                    (verification_sent_at IS NOT NULL AND verification_sent_at > NOW() - INTERVAL 60 SECOND) AS sent_recently
             FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ===== Send a New Verification Email =====
        // Allow at most one email per minute for each account
        if ($user && $user['email_verified_at'] === null) {
            if ($user['sent_recently']) {
                $message = 'A verification email was sent less than a minute ago. Please wait a moment before requesting another.';
            } elseif (!send_verification_email($pdo, $user)) {
                $message = 'We could not send the verification email right now. Please try again later.';
            }
        }

        // Same message whether or not the account exists, so this form cannot be used to find accounts
        if ($message === '') {
            flash('success', 'If an unverified account exists for ' . $email . ', a new verification link has been sent. '
                . 'Any earlier links no longer work.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Resend Verification';
require 'includes/auth_header.php';
?>
        <h1>Resend verification</h1>
        <p class="muted mb">Enter the email address you registered with and we'll send you a new verification link.</p>

        <?php if ($message): ?>
            <div class="alert alert-error"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" maxlength="150" value="<?= e($email) ?>" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary">Send verification link</button>
        </form>

        <p class="auth-links">Already verified? <a href="login.php">Sign in</a><br>
            <a href="index.php">&larr; Back to Home</a></p>
<?php require 'includes/auth_footer.php'; ?>
