<?php
/*
 * Email (SMTP) settings. Do NOT put real passwords in this file.
 *
 * Settings are read from, in this order:
 *   1. Environment variables (SMTP_HOST, SMTP_PORT, SMTP_ENCRYPTION, SMTP_USERNAME, SMTP_PASSWORD,
 *      MAIL_FROM_ADDRESS, MAIL_FROM_NAME, APP_URL)
 *   2. config/mail.local.php (not stored in Git; copy mail.local.example.php to create it)
 *   3. The default values below
 */

// ===== Default Values =====
$defaults = [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',        // 'tls' (port 587), 'ssl' (port 465) or 'none'
    'username'   => '',
    'password'   => '',
    'from_email' => '',
    'from_name'  => 'CareerNavigator',
    // Public address of the site, used to build the link inside verification emails.
    'app_url'    => 'http://localhost/CareerNavigator',
    // How long a verification link stays valid.
    'verification_hours' => 24,
];

// ===== Local Settings File =====
$local = is_file(__DIR__ . '/mail.local.php') ? require __DIR__ . '/mail.local.php' : [];

// ===== Environment Variables =====
$env = [];
$envNames = [
    'host' => 'SMTP_HOST', 'port' => 'SMTP_PORT', 'encryption' => 'SMTP_ENCRYPTION',
    'username' => 'SMTP_USERNAME', 'password' => 'SMTP_PASSWORD',
    'from_email' => 'MAIL_FROM_ADDRESS', 'from_name' => 'MAIL_FROM_NAME', 'app_url' => 'APP_URL',
];
foreach ($envNames as $key => $name) {
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        $env[$key] = $value;
    }
}

return array_merge($defaults, is_array($local) ? $local : [], $env);
