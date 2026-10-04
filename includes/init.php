<?php
// Included at the top of every page: starts the session, connects to the database and loads the helpers.

// ===== Session =====
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,   // JavaScript cannot read the session cookie
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,   // ignore session IDs the server did not create
    ]);
}

// ===== Error Handling =====
// Show a simple message instead of PHP error details. The full error goes to the PHP error log.
set_exception_handler(function (Throwable $error): void {
    error_log('CareerNavigator: ' . $error);
    http_response_code(500);
    echo '<h1>Something went wrong</h1><p>Please go back and try again.</p>';
});

// ===== Database and Helpers =====
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

refresh_current_user($pdo);

// Base URL of the project, e.g. "/CareerNavigator/", so links work from the admin/ and advisor/ folders
if (!defined('BASE_URL')) {
    $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $appRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
    if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
        define('BASE_URL', rtrim(substr($appRoot, strlen($docRoot)), '/') . '/');
    } else {
        define('BASE_URL', '/CareerNavigator/');
    }
}
