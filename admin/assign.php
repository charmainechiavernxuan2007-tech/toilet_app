<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pdo = getDB();

// Save assignments for a single selected user (checkbox list of all toilets)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_assignments') {
    verifyCsrf();
    $userId = (int)$_POST['user_id'];
    $toiletIds = array_map('intval', $_POST['toilet_ids'] ?? []);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("DELETE FROM user_toilets WHERE user_id = ?");
        $stmt->execute([$userId]);

        if ($toiletIds) {
            $insert = $pdo->prepare("INSERT INTO user_toilets (user_id, toilet_id) VALUES (?, ?)");
            foreach ($toiletIds as $tid) {
                $insert->execute([$userId, $tid]);
            }
        }
        $pdo->commit();
        flash('success', 'Toilet assignments updated.');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Failed to update assignments: ' . $e->getMessage());
    }
    header('Location: assign.php?user_id=' . $userId);
    exit;
}

$students = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY full_name")->fetchAll();
$allToilets = $pdo->query("SELECT * FROM toilets ORDER BY code")->fetchAll();

$selectedUserId = (int)($_GET['user_id'] ?? ($students[0]['id'] ?? 0));
$assignedIds = [];
if ($selectedUserId) {
    $stmt = $pdo->prepare("SELECT toilet_id FROM user_toilets WHERE user_id = ?");
    $stmt->execute([$selectedUserId]);
    $assignedIds = array_column($stmt->fetchAll(), 'toilet_id');
}

$pageTitle = 'Assign Toilets';
require_once __DIR__ . '/../includes/header.php';
?>
<h4 class="mb-3"><i class="bi bi-link-45deg"></i> Assign Toilets to Students</h4>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Select a Student</div>
      <div class="list-group list-group-flush" style="max-height:520px; overflow-y:auto;">
        <?php foreach ($students as $s): ?>
          <a href="assign.php?user_id=<?= (int)$s['id'] ?>"
             class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $s['id'] == $selectedUserId ? 'active' : '' ?>">
            <span><?= e($s['full_name']) ?> <br><small class="text-muted <?= $s['id'] == $selectedUserId ? 'text-white-50' : '' ?>">@<?= e($s['username']) ?></small></span>
          </a>
        <?php endforeach; ?>
        <?php if (!$students): ?>
          <div class="p-3 text-muted">No students yet. <a href="users.php">Add one first</a>.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <?php if ($selectedUserId): ?>
    <div class="card">
      <div class="card-header">
        Toilets assigned to:
        <strong><?= e(array_values(array_filter($students, fn($s) => $s['id'] == $selectedUserId))[0]['full_name'] ?? '') ?></strong>
      </div>
      <div class="card-body">
        <form method="post">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="save_assignments">
          <input type="hidden" name="user_id" value="<?= (int)$selectedUserId ?>">
          <div class="row row-cols-1 row-cols-md-2 g-2 mb-3">
            <?php foreach ($allToilets as $t): ?>
              <div class="col">
                <div class="form-check border rounded p-2">
                  <input class="form-check-input" type="checkbox" name="toilet_ids[]" value="<?= (int)$t['id'] ?>"
                         id="toilet_<?= (int)$t['id'] ?>" <?= in_array($t['id'], $assignedIds) ? 'checked' : '' ?>>
                  <label class="form-check-label w-100" for="toilet_<?= (int)$t['id'] ?>">
                    <strong><?= e($t['code']) ?></strong> - <?= e($t['name']) ?>
                    <?php if ($t['status'] !== 'active'): ?><span class="badge bg-secondary">inactive</span><?php endif; ?>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="submit" class="btn btn-brand">Save Assignments</button>
        </form>
      </div>
    </div>
    <?php else: ?>
      <div class="alert alert-info">Select a student from the left to manage their toilet assignments.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
