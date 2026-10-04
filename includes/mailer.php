<?php
// Sending emails with PHPMailer, and the email verification helpers.

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

// ===== Mail Settings =====

function mail_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/mail.php';
    }
    return $config;
}

// Check whether the SMTP details have been filled in
function mail_is_configured(): bool
{
    $c = mail_config();
    return $c['host'] !== '' && $c['username'] !== '' && $c['password'] !== '' && $c['from_email'] !== '';
}

// ===== Send an Email =====
// Returns true only if the SMTP server accepted the email.
// Errors are written to the PHP error log, never shown to the user.
function send_mail(string $toEmail, string $toName, string $subject, string $html, string $text): bool
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('CareerNavigator mail: PHPMailer is not installed. Run "composer install".');
        return false;
    }
    require_once $autoload;

    if (!mail_is_configured()) {
        error_log('CareerNavigator mail: SMTP is not configured (see config/mail.local.example.php).');
        return false;
    }

    $c = mail_config();
    $mail = new PHPMailer(true);
    try {
        // SMTP server and login
        $mail->isSMTP();
        $mail->Host = $c['host'];
        $mail->Port = (int) $c['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $c['username'];
        $mail->Password = $c['password'];
        if ($c['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($c['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }
        $mail->Timeout = 15;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;

        // Sender, receiver and content
        $mail->setFrom($c['from_email'], $c['from_name']);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = $text;

        return $mail->send();
    } catch (MailException) {
        error_log('CareerNavigator mail: sending to ' . $toEmail . ' failed: ' . $mail->ErrorInfo);
        return false;
    }
}

// ===== Email Verification =====

// Create a new random token for the user and return it.
// Only a hash of the token is saved, and a new token replaces any older one.
function issue_verification_token(PDO $pdo, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $hours = max(1, (int) mail_config()['verification_hours']);

    $stmt = $pdo->prepare(
        "UPDATE users
         SET verification_token_hash = ?,
             verification_expires_at = DATE_ADD(NOW(), INTERVAL $hours HOUR),
             verification_sent_at = NOW()
         WHERE id = ? AND email_verified_at IS NULL"
    );
    $stmt->execute([hash('sha256', $token), $userId]);
    return $token;
}

function verification_link(string $token): string
{
    return rtrim(mail_config()['app_url'], '/') . '/verify_email.php?token=' . urlencode($token);
}

// Create a new token and email the verification link to the user.
// Returns true only if the email was accepted for delivery.
function send_verification_email(PDO $pdo, array $user): bool
{
    $token = issue_verification_token($pdo, (int) $user['id']);
    $link = verification_link($token);
    $hours = (int) mail_config()['verification_hours'];
    $name = $user['name'];

    $subject = 'Verify your CareerNavigator email address';
    $html = '<div style="font-family:Arial,sans-serif;font-size:15px;color:#1E2422;max-width:520px">'
        . '<h2 style="color:#14342B">Welcome to CareerNavigator, ' . e($name) . '!</h2>'
        . '<p>Please confirm your email address to activate your account.</p>'
        . '<p><a href="' . e($link) . '" style="display:inline-block;padding:12px 22px;background:#14342B;color:#fff;'
        . 'text-decoration:none;border-radius:8px;font-weight:bold">Verify my email</a></p>'
        . '<p style="font-size:13px;color:#68736E">Or copy this link into your browser:<br>'
        . '<a href="' . e($link) . '">' . e($link) . '</a></p>'
        . '<p style="font-size:13px;color:#68736E">This link expires in ' . $hours . ' hours and can be used once. '
        . 'If you did not create an account, you can ignore this email.</p></div>';
    $text = "Welcome to CareerNavigator, $name!\n\n"
        . "Please confirm your email address by opening this link:\n$link\n\n"
        . "This link expires in $hours hours and can be used once. "
        . "If you did not create an account, you can ignore this email.\n";

    if (!send_mail($user['email'], $name, $subject, $html, $text)) {
        // Nothing was sent, so clear the time so the "wait a minute" limit does not apply
        $pdo->prepare("UPDATE users SET verification_sent_at = NULL WHERE id = ?")->execute([(int) $user['id']]);
        return false;
    }
    return true;
}
