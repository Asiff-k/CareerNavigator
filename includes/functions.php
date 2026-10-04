<?php
// Helper functions used on every page.

const MIN_PASSWORD_LENGTH = 8;

// Escape text before printing it in HTML (prevents XSS)
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Build a link from the project root, so it works from admin/ and advisor/ too
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

// ===== Login and Sessions =====

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

// The user details we keep in the session
function session_user_data(array $user): array
{
    return [
        'id'    => (int) $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
}

function login_user(array $user): void
{
    session_regenerate_id(true); // new session ID after login (prevents session fixation)
    $_SESSION['user'] = session_user_data($user);
}

// Reload the signed-in user from the database on every request,
// so a role change or a deleted account takes effect immediately
function refresh_current_user(PDO $pdo): void
{
    if (!is_logged_in()) {
        return;
    }
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([current_user_id()]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user'] = session_user_data($user);
    } else {
        unset($_SESSION['user']);
    }
}

// The page each role sees after signing in
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

// Only allow signed-in users with one of the given roles
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

// ===== CSRF Protection =====

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Hidden field that goes inside every POST form
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Call this at the start of every POST handler
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        die('Invalid form submission. Please go back, refresh the page and try again.');
    }
}

// ===== Flash Messages (shown once after a redirect) =====

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

// ===== Skill Levels and Scores =====

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

// CSS class for the colour of a progress bar
function score_class(float $score): string
{
    if ($score >= 60) return '';
    if ($score >= 35) return 'mid';
    return 'low';
}

// Read a whole number from $_GET or $_POST
function input_int(array $source, string $key): int
{
    return isset($source[$key]) ? (int) $source[$key] : 0;
}
