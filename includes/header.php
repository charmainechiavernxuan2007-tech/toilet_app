<?php
/**
 * Expects (optionally) $pageTitle to be set before include.
 * Requires auth.php + functions.php already loaded.
 */
$pageTitle = $pageTitle ?? 'Toilet Cleanliness Monitoring';
$root = basePathToRoot();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | Toilet Monitor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= $root ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php if (isLoggedIn()): ?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="<?= $root ?><?= isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php' ?>">
      <i class="bi bi-droplet-half"></i> Toilet Monitor
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if (isAdmin()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>admin/users.php"><i class="bi bi-people"></i> Users</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>admin/toilets.php"><i class="bi bi-signpost-2"></i> Toilets</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>admin/assign.php"><i class="bi bi-link-45deg"></i> Assignments</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>admin/history.php"><i class="bi bi-clock-history"></i> Full History</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>user/dashboard.php"><i class="bi bi-house"></i> Home</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>user/history.php"><i class="bi bi-clock-history"></i> Toilet History</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item"><span class="navbar-text me-3 text-light-emphasis">
          <i class="bi bi-person-circle"></i> <?= e($_SESSION['full_name']) ?>
          <span class="badge bg-light text-dark ms-1"><?= e(ucfirst($_SESSION['role'])) ?></span>
        </span></li>
        <li class="nav-item"><a class="nav-link" href="<?= $root ?>change_password.php"><i class="bi bi-key"></i> Password</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= $root ?>logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>
<main class="container py-4">
  <?php renderFlashes(); ?>
