<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireUser();

$userId = currentUserId();
$myToilets = getToiletsForUser($userId);

$toiletId = (int)($_GET['toilet_id'] ?? ($myToilets[0]['id'] ?? 0));

// --- Authorization: only view history for toilets you're assigned to ---
if (!$toiletId || !userAssignedToToilet($userId, $toiletId)) {
    flash('error', 'You can only view history for toilets assigned to you.');
    header('Location: dashboard.php');
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM toilets WHERE id = ?");
$stmt->execute([$toiletId]);
$toilet = $stmt->fetch();

$history = getToiletHistory($toiletId, 100);

$pageTitle = 'Toilet History';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h4 class="mb-0"><i class="bi bi-clock-history"></i> History — <?= e($toilet['code']) ?> (<?= e($toilet['name']) ?>)</h4>
  <?php if (count($myToilets) > 1): ?>
    <form method="get" class="d-flex gap-2">
      <select name="toilet_id" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach ($myToilets as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= $t['id'] == $toiletId ? 'selected' : '' ?>><?= e($t['code']) ?> - <?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>
</div>

<p class="text-muted small">Students assigned to this toilet can see its full check-in / check-out history below.</p>

<?php if (!$history): ?>
  <div class="alert alert-info">No history yet for this toilet.</div>
<?php endif; ?>

<?php foreach ($history as $h): ?>
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><?= fmtDate($h['checkin_time']) ?> — <?= e($h['toilet_code']) ?> &middot; <strong><?= e($h['user_name']) ?></strong></span>
      <span class="status-<?= $h['status'] ?>"><?= ucfirst($h['status']) ?></span>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3 mb-md-0">
          <h6 class="text-success"><i class="bi bi-box-arrow-in-right"></i> Check In: <?= fmtTime($h['checkin_time']) ?></h6>
          <?php $ciPhotos = getSessionPhotos((int)$h['id'], 'checkin'); ?>
          <div class="photo-preview-wrap mb-2">
            <?php foreach ($ciPhotos as $p): ?>
              <a href="<?= e(photoUrl($p['photo_path'])) ?>" target="_blank"><img src="<?= e(photoUrl($p['photo_path'])) ?>" class="photo-thumb"></a>
            <?php endforeach; ?>
            <?php if (!$ciPhotos): ?><span class="text-muted small">No photos.</span><?php endif; ?>
          </div>
          <p class="mb-0 small"><?= $h['checkin_comment'] ? nl2br(e($h['checkin_comment'])) : '<span class="text-muted">No comment.</span>' ?></p>
        </div>
        <div class="col-md-6">
          <h6 class="text-danger"><i class="bi bi-box-arrow-left"></i> Check Out: <?= $h['checkout_time'] ? fmtTime($h['checkout_time']) : 'Not yet checked out' ?></h6>
          <?php if ($h['checkout_time']): ?>
            <?php $coPhotos = getSessionPhotos((int)$h['id'], 'checkout'); ?>
            <div class="photo-preview-wrap mb-2">
              <?php foreach ($coPhotos as $p): ?>
                <a href="<?= e(photoUrl($p['photo_path'])) ?>" target="_blank"><img src="<?= e(photoUrl($p['photo_path'])) ?>" class="photo-thumb"></a>
              <?php endforeach; ?>
              <?php if (!$coPhotos): ?><span class="text-muted small">No photos.</span><?php endif; ?>
            </div>
            <p class="mb-0 small"><?= $h['checkout_comment'] ? nl2br(e($h['checkout_comment'])) : '<span class="text-muted">No comment.</span>' ?></p>
          <?php else: ?>
            <p class="text-muted small">Session still active.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
