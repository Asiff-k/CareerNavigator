<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$roles = ['student', 'advisor', 'admin'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = input_int($_POST, 'id');

    // Create a new account (used to add advisors and admins).
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Name is required (maximum 100 characters).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 6) $errors[] = 'Password must contain at least 6 characters.';
        if (!in_array($role, $roles, true)) $errors[] = 'Please choose a valid role.';

        if (!$errors) {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = 'This email is already registered.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash('success', ucfirst($role) . ' account created for ' . $name . '.');
                redirect('admin/users.php');
            }
        }
    }

    // An admin cannot change or delete their own account here (prevents locking yourself out).
    if (($action === 'role' || $action === 'delete') && $id === current_user_id()) {
        flash('error', 'You cannot change or delete your own account.');
        redirect('admin/users.php');
    }

    if ($action === 'role') {
        $role = $_POST['role'] ?? '';
        if (in_array($role, $roles, true)) {
            $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $id]);
            flash('success', 'Role updated.');
        } else {
            flash('error', 'Invalid role.');
        }
        redirect('admin/users.php');
    }

    if ($action === 'delete') {
        // Related profile, skills, projects, history and notes are removed by ON DELETE CASCADE.
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', $stmt->rowCount() ? 'User deleted.' : 'User not found.');
        redirect('admin/users.php');
    }
}

$roleFilter = in_array($_GET['role'] ?? '', $roles, true) ? $_GET['role'] : '';
$q = trim($_GET['q'] ?? '');
$sql = "SELECT id, name, email, role, created_at FROM users WHERE 1 = 1";
$params = [];
if ($roleFilter) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}
if ($q !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    array_push($params, "%$q%", "%$q%");
}
$sql .= " ORDER BY FIELD(role, 'admin', 'advisor', 'student'), name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Users';
$activePage = 'users';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Users</h1>
        <p>Create advisor and admin accounts, change roles or remove users.</p>
    </div>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
    <h2>Create an account</h2>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="form-grid">
            <div class="form-group"><label for="name">Full name</label>
                <input type="text" id="name" name="name" maxlength="100" required value="<?= e($_POST['name'] ?? '') ?>"></div>
            <div class="form-group"><label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="150" required value="<?= e($_POST['email'] ?? '') ?>"></div>
            <div class="form-group"><label for="password">Temporary password</label>
                <input type="password" id="password" name="password" minlength="6" required></div>
            <div class="form-group"><label for="role">Role</label>
                <select id="role" name="role">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r ?>" <?= ($_POST['role'] ?? 'advisor') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                </select></div>
        </div>
        <button type="submit" class="btn btn-primary">Create account</button>
    </form>
</div>

<form method="GET" class="filters card">
    <div class="form-group"><label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Name or email"></div>
    <div class="form-group"><label for="rf">Role</label>
        <select id="rf" name="role">
            <option value="">All roles</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
            <?php endforeach; ?>
        </select></div>
    <div class="btn-group">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="users.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<div class="card">
    <h2><?= count($users) ?> user(s)</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= e($u['name']) ?></strong></td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <?php if ((int) $u['id'] === current_user_id()): ?>
                            <span class="badge badge-dark">Admin (you)</span>
                        <?php else: ?>
                            <form method="POST" class="btn-group">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="role">
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <select name="role" style="width:auto">
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td class="small muted"><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                    <td class="text-right">
                        <div class="btn-group" style="justify-content:flex-end">
                            <?php if ($u['role'] === 'student'): ?>
                                <a href="../advisor/student.php?id=<?= (int) $u['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                            <?php endif; ?>
                            <?php if ((int) $u['id'] !== current_user_id()): ?>
                                <form method="POST" class="inline-form" data-confirm="Delete this user and all of their data?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
