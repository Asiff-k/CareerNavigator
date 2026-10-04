<?php
require_once 'includes/init.php';

$token = $_GET['token'] ?? '';
$status = 'invalid';   // invalid, expired or verified
$expiredEmail = '';

// ===== Check the Token =====
// A valid token is 64 hexadecimal characters. Only its hash is stored in the database.
if (is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $hash = hash('sha256', $token);

    $stmt = $pdo->prepare(
        "SELECT id, email, verification_expires_at > NOW() AS still_valid
         FROM users WHERE verification_token_hash = ? AND email_verified_at IS NULL"
    );
    $stmt->execute([$hash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !$user['still_valid']) {
        $status = 'expired';
        $expiredEmail = $user['email'];
    } elseif ($user) {
        // ===== Mark the Account as Verified =====
        // The conditions are checked again here, so the same link cannot be used twice
        $update = $pdo->prepare(
            "UPDATE users
             SET email_verified_at = NOW(), verification_token_hash = NULL, verification_expires_at = NULL
             WHERE id = ? AND verification_token_hash = ? AND email_verified_at IS NULL
               AND verification_expires_at > NOW()"
        );
        $update->execute([$user['id'], $hash]);
        if ($update->rowCount() === 1) {
            $status = 'verified';
        }
    }
}

if ($status === 'verified') {
    flash('success', 'Your email address has been verified. You can now sign in.');
    redirect('login.php');
}

$pageTitle = 'Verify Email';
require 'includes/auth_header.php';
?>
        <h1>Email verification</h1>

        <?php if ($status === 'expired'): ?>
            <div class="alert alert-error">This verification link has expired. Please request a new one.</div>
            <a href="resend_verification.php?email=<?= e(urlencode($expiredEmail)) ?>" class="btn btn-primary">Send a new link</a>
        <?php else: ?>
            <div class="alert alert-error">This verification link is invalid or has already been used.
                If you have already verified your email, you can simply sign in.</div>
            <a href="login.php" class="btn btn-primary">Go to Sign In</a>
        <?php endif; ?>

        <p class="auth-links">Need a new link? <a href="resend_verification.php">Resend verification email</a><br>
            <a href="index.php">&larr; Back to Home</a></p>
<?php require 'includes/auth_footer.php'; ?>
