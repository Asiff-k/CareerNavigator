<?php
/*
 * Copy this file to config/mail.local.php and fill in your SMTP details.
 * mail.local.php is not stored in Git, and the config/ folder cannot be opened in a browser.
 *
 * Gmail: turn on 2-Step Verification, then create an App Password at
 * https://myaccount.google.com/apppasswords and use it as 'password' (not your normal password).
 * 'from_email' should be the same Gmail address as 'username'.
 */
return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => 'your.address@gmail.com',
    'password'   => 'your-16-character-app-password',
    'from_email' => 'your.address@gmail.com',
    'from_name'  => 'CareerNavigator',
    'app_url'    => 'http://localhost/CareerNavigator',
];
