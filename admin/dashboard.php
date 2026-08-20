<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pdo = getDB();
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalToilets = $pdo->query("SELECT COUNT(*) FROM toilets")->fetchColumn();
$activeSessions = $pdo->query("SELECT COUNT(*) FROM toilet_sessions WHERE status='active'")->fetchColumn();
$todaySessions = $pdo->query("SELECT COUNT(*) FROM toilet_sessions WHERE DATE(checkin_time) = CURDATE()")->fetchColumn();

$recent = $pdo->query(
    "SELECT s.*, u.full_name AS user_name, t.code AS toilet_code, t.name AS toilet_name
     FROM toilet_sessions s
     JOIN users u ON u.id = s.user_id
     JOIN toilets t ON t.id = s.toilet_id
     ORDER BY s.id DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h4>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="fs-3 fw-bold text-teal" style="color:#0d6f6f"><?= (int)$totalUsers ?></div>
      <div class="text-muted small">Students</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="fs-3 fw-bold" style="color:#0d6f6f"><?= (int)$totalToilets ?></div>
      <div class="text-muted small">Toilets</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="fs-3 fw-bold text-warning"><?= (int)$activeSessions ?></div>
      <div class="text-muted small">Active Check-ins</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card p-3 text-center">
      <div class="fs-3 fw-bold text-success"><?= (int)$todaySessions ?></div>
      <div class="text-muted small">Sessions Today</div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <a href="users.php" class="text-decoration-none">
      <div class="card p-3 toilet-tile"><i class="bi bi-people fs-3"></i> <div class="mt-2 fw-semibold">Manage Users</div></div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="toilets.php" class="text-decoration-none">
      <div class="card p-3 toilet-tile"><i class="bi bi-signpost-2 fs-3"></i> <div class="mt-2 fw-semibold">Manage Toilets</div></div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="assign.php" class="text-decoration-none">
      <div class="card p-3 toilet-tile"><i class="bi bi-link-45deg fs-3"></i> <div class="mt-2 fw-semibold">Assign Toilets</div></div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="history.php" class="text-decoration-none">
      <div class="card p-3 toilet-tile"><i class="bi bi-clock-history fs-3"></i> <div class="mt-2 fw-semibold">Full History</div></div>
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header">Recent Activity</div>
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr><th>Toilet</th><th>Student</th><th>Check-in</th><th>Check-out</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= e($r['toilet_code']) ?> - <?= e($r['toilet_name']) ?></td>
          <td><?= e($r['user_name']) ?></td>
          <td><?= fmtDateTime($r['checkin_time']) ?></td>
          <td><?= fmtDateTime($r['checkout_time']) ?></td>
          <td><span class="status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
          <td><a href="session_detail.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No activity yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
