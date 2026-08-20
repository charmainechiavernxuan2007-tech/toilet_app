<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pdo = getDB();

$toiletFilter = (int)($_GET['toilet_id'] ?? 0);
$userFilter   = (int)($_GET['user_id'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if ($toiletFilter) { $where[] = 's.toilet_id = ?'; $params[] = $toiletFilter; }
if ($userFilter)   { $where[] = 's.user_id = ?';   $params[] = $userFilter; }
if ($statusFilter === 'active' || $statusFilter === 'completed') { $where[] = 's.status = ?'; $params[] = $statusFilter; }
if ($dateFrom) { $where[] = 'DATE(s.checkin_time) >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'DATE(s.checkin_time) <= ?'; $params[] = $dateTo; }

$sql = "SELECT s.*, u.full_name AS user_name, u.username, t.code AS toilet_code, t.name AS toilet_name
        FROM toilet_sessions s
        JOIN users u ON u.id = s.user_id
        JOIN toilets t ON t.id = s.toilet_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY s.checkin_time DESC LIMIT 300';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

$toilets = $pdo->query("SELECT * FROM toilets ORDER BY code")->fetchAll();
$users   = $pdo->query("SELECT * FROM users WHERE role='user' ORDER BY full_name")->fetchAll();

$pageTitle = 'Full History';
require_once __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-clock-history"></i> Complete Toilet Usage & Cleanliness History</h4>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small">Toilet</label>
        <select name="toilet_id" class="form-select form-select-sm">
          <option value="0">All Toilets</option>
          <?php foreach ($toilets as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= $toiletFilter == $t['id'] ? 'selected' : '' ?>><?= e($t['code']) ?> - <?= e($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small">Student</label>
        <select name="user_id" class="form-select form-select-sm">
          <option value="0">All Students</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>" <?= $userFilter == $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Any</option>
          <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active (checked-in)</option>
          <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">From</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">To</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-brand btn-sm mt-2"><i class="bi bi-funnel"></i> Filter</button>
        <a href="history.php" class="btn btn-outline-secondary btn-sm mt-2">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">Results (<?= count($sessions) ?>)</div>
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Toilet</th><th>Student</th><th>Check-in</th><th>Check-out</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sessions as $s): ?>
        <tr>
          <td><strong><?= e($s['toilet_code']) ?></strong><br><small class="text-muted"><?= e($s['toilet_name']) ?></small></td>
          <td><?= e($s['user_name']) ?><br><small class="text-muted">@<?= e($s['username']) ?></small></td>
          <td><?= fmtDateTime($s['checkin_time']) ?></td>
          <td><?= fmtDateTime($s['checkout_time']) ?></td>
          <td><span class="status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
          <td><a href="session_detail.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-secondary">View Details</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$sessions): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No records match your filters.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
