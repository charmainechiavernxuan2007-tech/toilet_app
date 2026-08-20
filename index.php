<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (attemptLogin($username, $password)) {
        header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Toilet Monitor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center" style="min-height:100vh; background:linear-gradient(135deg,#094f4f,#0d6f6f);">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card shadow-lg p-4">
        <div class="text-center mb-3">
          <i class="bi bi-droplet-half" style="font-size:2.5rem; color:#0d6f6f;"></i>
          <h4 class="mt-2 mb-0">Toilet Cleanliness Monitor</h4>
          <small class="text-muted">College-wide monitoring system</small>
        </div>
        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <?= csrfField() ?>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-brand w-100">Login</button>
        </form>
        <p class="text-center text-muted small mt-3 mb-0">Accounts are created by the Admin. Contact your admin if you don't have a login.</p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
