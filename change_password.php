<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$pdo = getDB();
$error = '';
$success = '';
$forced = isset($_GET['forced']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([currentUserId()]);
    $hash = $stmt->fetchColumn();

    // On forced first-time change, some systems skip "current password" (temp password given verbally).
    // We still require it for security; admin communicates the initial password to the student.
    if (!password_verify($current, $hash)) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?");
        $stmt->execute([$newHash, currentUserId()]);
        $_SESSION['must_change_password'] = false;
        flash('success', 'Password updated successfully.');
        header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
        exit;
    }
}

$pageTitle = 'Change Password';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-key"></i> Change Password</div>
      <div class="card-body">
        <?php if ($forced): ?>
          <div class="alert alert-warning py-2">For security, please set your own password before continuing.</div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <?= csrfField() ?>
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" minlength="6" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" minlength="6" required>
          </div>
          <button type="submit" class="btn btn-brand w-100">Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
