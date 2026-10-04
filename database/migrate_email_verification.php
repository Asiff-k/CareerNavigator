<?php
/*
 * Adds email verification columns to the users table.
 *
 * Run from the terminal:   C:\xampp\php\php.exe database\migrate_email_verification.php
 * (seed.php also runs it.)
 *
 * Safe to run more than once: missing columns are added, existing ones are left alone.
 * Accounts that already exist when the column is first added are marked as verified,
 * so current students, advisors and admins are not locked out.
 */

if (PHP_SAPI !== 'cli') {
    die('Run this script from the command line.');
}

require_once __DIR__ . '/../config/db.php';

$existing = $pdo->query(
    "SELECT COLUMN_NAME FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'"
)->fetchAll(PDO::FETCH_COLUMN);

$columns = [
    'email_verified_at'       => "DATETIME NULL DEFAULT NULL",
    'verification_token_hash' => "CHAR(64) NULL DEFAULT NULL",
    'verification_expires_at' => "DATETIME NULL DEFAULT NULL",
    'verification_sent_at'    => "DATETIME NULL DEFAULT NULL",
];

$addedVerifiedColumn = false;
foreach ($columns as $column => $definition) {
    if (!in_array($column, $existing, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN `$column` $definition");
        echo "Added users.$column\n";
        if ($column === 'email_verified_at') {
            $addedVerifiedColumn = true;
        }
    }
}

$hasIndex = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'verification_token_hash'"
)->fetchColumn();
if (!$hasIndex) {
    $pdo->exec("ALTER TABLE users ADD UNIQUE KEY `verification_token_hash` (`verification_token_hash`)");
    echo "Added unique index on users.verification_token_hash\n";
}

// Only on the first run: treat every account that existed before verification was introduced as verified.
if ($addedVerifiedColumn) {
    $count = $pdo->exec("UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL");
    echo "Marked $count existing account(s) as verified.\n";
}

echo "Email verification migration complete.\n";
