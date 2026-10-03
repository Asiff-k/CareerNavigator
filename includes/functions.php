<?php
/*
 * Small helper functions used across the application.
 */

// Escape output to prevent XSS.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Build a URL relative to the application root.
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

// ---------- Authentication ----------

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_user_id(): int
{
    return (int) ($_SESSION['user']['id'] ?? 0);
}

function login_user(array $user): void
{
    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user'] = [
        'id'    => (int) $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
}

// Where each role lands after logging in.
function home_for_role(string $role): string
{
    if ($role === 'admin') {
        return 'admin/index.php';
    }
    if ($role === 'advisor') {
        return 'advisor/index.php';
    }
    return 'dashboard.php';
}

// Allow access only to logged-in users with one of the given roles.
function require_role(string ...$roles): void
{
    if (!is_logged_in()) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
    if ($roles && !in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        require __DIR__ . '/header.php';
        echo '<div class="card"><h2>Access denied</h2><p class="muted">You do not have permission to view this page.</p>'
            . '<a class="btn btn-primary" href="' . e(url(home_for_role($_SESSION['user']['role']))) . '">Go to my dashboard</a></div>';
        require __DIR__ . '/footer.php';
        exit;
    }
}

// ---------- CSRF protection ----------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Call at the start of every POST handler.
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        die('Invalid form submission. Please go back, refresh the page and try again.');
    }
}

// ---------- Flash messages (shown once after a redirect) ----------

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// ---------- Proficiency levels ----------

const LEVELS = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3];

function level_number(?string $level): int
{
    return LEVELS[$level] ?? 0;
}

function level_name(int $number): string
{
    $names = [0 => 'None', 1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
    return $names[$number] ?? 'None';
}

// Read an integer from GET/POST safely.
function input_int(array $source, string $key): int
{
    return isset($source[$key]) ? (int) $source[$key] : 0;
}
