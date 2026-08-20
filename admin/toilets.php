<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pdo = getDB();
$editToilet = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    verifyCsrf();
    $id       = (int)($_POST['id'] ?? 0);
    $code     = trim($_POST['code'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $status   = $_POST['status'] === 'inactive' ? 'inactive' : 'active';

    if ($code === '' || $name === '') {
        flash('error', 'Toilet code and name are required.');
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE toilets SET code=?, name=?, location=?, status=? WHERE id=?");
                $stmt->execute([$code, $name, $location, $status, $id]);
                flash('success', 'Toilet updated successfully.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO toilets (code, name, location, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$code, $name, $location, $status]);
                flash('success', 'Toilet added successfully.');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                flash('error', 'That toilet code already exists.');
            } else {
                flash('error', 'Database error: ' . $e->getMessage());
            }
        }
    }
    header('Location: toilets.php');
    exit;
}

if (isset($_GET['delete'])) {
    verifyCsrf();
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM toilets WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Toilet deleted.');
    } catch (PDOException $e) {
        flash('error', 'This toilet has session history and cannot be deleted (to preserve records). Set its status to Inactive instead.');
    }
    header('Location: toilets.php');
    exit;
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM toilets WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editToilet = $stmt->fetch();
}

$toilets = $pdo->query(
    "SELECT t.*, (SELECT COUNT(*) FROM user_toilets ut WHERE ut.toilet_id = t.id) AS assigned_count
     FROM toilets t ORDER BY t.code"
)->fetchAll();

$pageTitle = 'Manage Toilets';
require_once __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-signpost-2"></i> Manage Toilets</h4>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><?= $editToilet ? 'Edit Toilet' : 'Add New Toilet' ?></div>
      <div class="card-body">
        <form method="post">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($editToilet['id'] ?? 0) ?>">
          <div class="mb-3">
            <label class="form-label">Toilet Code / Number</label>
            <input type="text" name="code" class="form-control" required placeholder="e.g. T01" value="<?= e($editToilet['code'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Toilet Name</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Block A Level 1 Male Toilet" value="<?= e($editToilet['name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Location (optional)</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Block A, Level 1" value="<?= e($editToilet['location'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="active" <?= (($editToilet['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= (($editToilet['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand"><?= $editToilet ? 'Update Toilet' : 'Add Toilet' ?></button>
            <?php if ($editToilet): ?>
              <a href="toilets.php" class="btn btn-outline-secondary">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">All Toilets (<?= count($toilets) ?>)</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light">
            <tr><th>Code</th><th>Name</th><th>Location</th><th>Assigned Users</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($toilets as $t): ?>
            <tr>
              <td><strong><?= e($t['code']) ?></strong></td>
              <td><?= e($t['name']) ?></td>
              <td><?= e($t['location']) ?></td>
              <td><span class="badge bg-secondary"><?= (int)$t['assigned_count'] ?></span></td>
              <td><span class="badge bg-<?= $t['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($t['status']) ?></span></td>
              <td class="text-nowrap">
                <a href="toilets.php?edit=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                <a href="toilets.php?delete=<?= (int)$t['id'] ?>&csrf_token=<?= urlencode(csrfToken()) ?>"
                   class="btn btn-sm btn-outline-danger"
                   data-confirm="Delete toilet '<?= e($t['code']) ?>'? This cannot be undone."><i class="bi bi-trash"></i></a>
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
