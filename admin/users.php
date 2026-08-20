<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pdo = getDB();
$editUser = null;

// ---- Handle Add / Edit ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    verifyCsrf();
    $id        = (int)($_POST['id'] ?? 0);
    $username  = trim($_POST['username'] ?? '');
    $fullName  = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = $_POST['role'] === 'admin' ? 'admin' : 'user';
    $password  = $_POST['password'] ?? '';
    $status    = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if ($username === '' || $fullName === '') {
        flash('error', 'Username and full name are required.');
    } else {
        try {
            if ($id > 0) {
                // Edit existing
                if ($password !== '') {
                    $stmt = $pdo->prepare(
                        "UPDATE users SET username=?, full_name=?, email=?, role=?, status=?, password_hash=?, must_change_password=1 WHERE id=?"
                    );
                    $stmt->execute([$username, $fullName, $email, $role, $status, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE users SET username=?, full_name=?, email=?, role=?, status=? WHERE id=?"
                    );
                    $stmt->execute([$username, $fullName, $email, $role, $status, $id]);
                }
                flash('success', 'User updated successfully.');
            } else {
                // Add new. Admin sets an initial password; student must change it on first login.
                if ($password === '') $password = 'welcome123';
                $stmt = $pdo->prepare(
                    "INSERT INTO users (username, password_hash, full_name, email, role, status, must_change_password)
                     VALUES (?, ?, ?, ?, ?, ?, 1)"
                );
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName, $email, $role, $status]);
                flash('success', "User created. Initial password: \"$password\" (student will be asked to change it at first login).");
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                flash('error', 'That username is already taken. Please choose another.');
            } else {
                flash('error', 'Database error: ' . $e->getMessage());
            }
        }
    }
    header('Location: users.php');
    exit;
}

// ---- Handle Delete ----
if (isset($_GET['delete'])) {
    verifyCsrf();
    $id = (int)$_GET['delete'];
    if ($id === (int)currentUserId()) {
        flash('error', 'You cannot delete your own account.');
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            flash('success', 'User deleted.');
        } catch (PDOException $e) {
            // FK constraint: user has toilet_sessions history
            flash('error', 'This user has check-in/check-out history and cannot be deleted (to preserve records). Set their status to Inactive instead.');
        }
    }
    header('Location: users.php');
    exit;
}

// ---- Load for edit ----
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editUser = $stmt->fetch();
}

$users = $pdo->query("SELECT * FROM users ORDER BY role DESC, full_name")->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4><i class="bi bi-people"></i> Manage Users</h4>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><?= $editUser ? 'Edit User' : 'Add New User' ?></div>
      <div class="card-body">
        <form method="post">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($editUser['id'] ?? 0) ?>">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required value="<?= e($editUser['username'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required value="<?= e($editUser['full_name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Email (optional)</label>
            <input type="email" name="email" class="form-control" value="<?= e($editUser['email'] ?? '') ?>">
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Role</label>
              <select name="role" class="form-select">
                <option value="user" <?= (($editUser['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>Student</option>
                <option value="admin" <?= (($editUser['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
              </select>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?= (($editUser['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= (($editUser['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= $editUser ? 'Reset Password (leave blank to keep current)' : 'Initial Password (leave blank for default: welcome123)' ?></label>
            <input type="text" name="password" class="form-control" placeholder="<?= $editUser ? 'Leave blank to keep current password' : 'welcome123' ?>">
            <div class="form-text">Student can change this themselves after logging in.</div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?= $editUser ? 'Update User' : 'Create User' ?></button>
            <?php if ($editUser): ?>
              <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">All Users (<?= count($users) ?>)</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light">
            <tr><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td><?= e($u['username']) ?></td>
              <td><?= e($u['full_name']) ?></td>
              <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'dark' : 'info' ?>"><?= ucfirst($u['role']) ?></span></td>
              <td><span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($u['status']) ?></span></td>
              <td class="text-nowrap">
                <a href="users.php?edit=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                <a href="users.php?delete=<?= (int)$u['id'] ?>&csrf_token=<?= urlencode(csrfToken()) ?>"
                   class="btn btn-sm btn-outline-danger"
                   data-confirm="Delete user '<?= e($u['username']) ?>'? This cannot be undone."><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
