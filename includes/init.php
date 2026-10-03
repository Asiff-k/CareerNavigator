<?php
/*
 * Included at the top of every page.
 * Starts the session, connects to the database and loads helper functions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,   // JavaScript cannot read the session cookie
        'cookie_samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Base URL of the application, e.g. "/CareerNavigator/", so links work from sub-folders (admin/, advisor/).
if (!defined('BASE_URL')) {
    $docRoot = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $appRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
    if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
        define('BASE_URL', rtrim(substr($appRoot, strlen($docRoot)), '/') . '/');
    } else {
        define('BASE_URL', '/CareerNavigator/');
    }
}
