<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireUser();

$pdo = getDB();
$userId = currentUserId();
$toiletId = (int)($_GET['toilet_id'] ?? $_POST['toilet_id'] ?? 0);

if (!$toiletId || !userAssignedToToilet($userId, $toiletId)) {
    flash('error', 'You are not assigned to that toilet.');
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM toilets WHERE id = ?");
$stmt->execute([$toiletId]);
$toilet = $stmt->fetch();

// --- Business rule: cannot check out unless an ACTIVE check-in exists for this toilet ---
$stmt = $pdo->prepare(
    "SELECT * FROM toilet_sessions WHERE toilet_id = ? AND user_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1"
);
$stmt->execute([$toiletId, $userId]);
$session = $stmt->fetch();

if (!$session) {
    flash('error', 'You do not have an active check-in for this toilet, so you cannot check out.');
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Re-verify the session is still active right before writing (defense in depth
    // against double-submits / concurrent requests). A completed session is never re-edited.
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT status FROM toilet_sessions WHERE id = ?");
    $stmt->execute([$session['id']]);
    $currentStatus = $stmt->fetchColumn();

    if ($currentStatus !== 'active') {
        $pdo->rollBack();
        $error = 'This session has already been checked out.';
    } else {
        $comment = trim($_POST['comment'] ?? '');
        try {
            $photos = handlePhotoUploads('photos', 'checkout');

            $upd = $pdo->prepare(
                "UPDATE toilet_sessions
                 SET checkout_time = NOW(), checkout_comment = ?, status = 'completed'
                 WHERE id = ? AND status = 'active'"
            );
            $upd->execute([$comment, $session['id']]);

            if ($upd->rowCount() === 0) {
                throw new RuntimeException('Session was already checked out by another request.');
            }

            if ($photos) {
                $insertPhoto = $pdo->prepare(
                    "INSERT INTO session_photos (session_id, photo_path, photo_type) VALUES (?, ?, 'checkout')"
                );
                foreach ($photos as $p) {
                    $insertPhoto->execute([$session['id'], $p]);
                }
            }
            $pdo->commit();

            flash('success', 'Checked out successfully from ' . $toilet['code'] . '. Thank you!');
            header('Location: dashboard.php');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Check-out failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Check Out';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-box-arrow-left text-danger"></i> Check Out — <?= e($toilet['code']) ?> (<?= e($toilet['name']) ?>)</div>
      <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <div class="alert alert-light border small">
          <strong>Checked in:</strong> <?= fmtDateTime($session['checkin_time']) ?><br>
          <?php if ($session['checkin_comment']): ?><strong>Check-in comment:</strong> <?= e($session['checkin_comment']) ?><?php endif; ?>
        </div>
        <p class="text-muted">Check-out time will be recorded automatically as the current date & time when you submit.</p>
        <form method="post" enctype="multipart/form-data">
          <?= csrfField() ?>
          <input type="hidden" name="toilet_id" value="<?= (int)$toiletId ?>">

          <div class="mb-3">
            <label class="form-label">Photos of toilet condition (after) <span class="text-muted">— you may select multiple</span></label>
            <input type="file" name="photos[]" class="form-control" accept="image/*" capture="environment" multiple data-preview="checkoutPreview">
            <div id="checkoutPreview" class="photo-preview-wrap"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Comment (optional)</label>
            <textarea name="comment" class="form-control" rows="3" placeholder="e.g. Floor cleaned and rubbish removed."></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand"><i class="bi bi-check2-circle"></i> Submit Check-Out</button>
            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
