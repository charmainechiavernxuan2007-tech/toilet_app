<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireUser();

$pdo = getDB();
$userId = currentUserId();
$toiletId = (int)($_GET['toilet_id'] ?? $_POST['toilet_id'] ?? 0);

// --- Authorization: must be assigned to this toilet ---
if (!$toiletId || !userAssignedToToilet($userId, $toiletId)) {
    flash('error', 'You are not assigned to that toilet.');
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM toilets WHERE id = ?");
$stmt->execute([$toiletId]);
$toilet = $stmt->fetch();
if (!$toilet) {
    flash('error', 'Toilet not found.');
    header('Location: dashboard.php');
    exit;
}

// --- Business rule: cannot start a new check-in while another session is active ---
$activeSession = getActiveSessionForUser($userId);
if ($activeSession) {
    flash('error', 'You already have an active check-in at ' . $activeSession['toilet_code'] . '. Please check out first.');
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    // Re-check nobody raced us into an active session (defense in depth)
    if (getActiveSessionForUser($userId)) {
        $error = 'You already have an active check-in. Please refresh and check out first.';
    } else {
        $comment = trim($_POST['comment'] ?? '');
        try {
            $photos = handlePhotoUploads('photos', 'checkin');

            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "INSERT INTO toilet_sessions (toilet_id, user_id, checkin_time, checkin_comment, status)
                 VALUES (?, ?, NOW(), ?, 'active')"
            );
            $stmt->execute([$toiletId, $userId, $comment]);
            $sessionId = (int)$pdo->lastInsertId();

            if ($photos) {
                $insertPhoto = $pdo->prepare(
                    "INSERT INTO session_photos (session_id, photo_path, photo_type) VALUES (?, ?, 'checkin')"
                );
                foreach ($photos as $p) {
                    $insertPhoto->execute([$sessionId, $p]);
                }
            }
            $pdo->commit();

            flash('success', 'Checked in successfully at ' . $toilet['code'] . '. Remember to check out when you leave.');
            header('Location: dashboard.php');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Check-in failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Check In';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-box-arrow-in-right text-success"></i> Check In — <?= e($toilet['code']) ?> (<?= e($toilet['name']) ?>)</div>
      <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <p class="text-muted">Check-in time will be recorded automatically as the current date & time when you submit.</p>
        <form method="post" enctype="multipart/form-data" data-camera-form>
          <?= csrfField() ?>
          <input type="hidden" name="toilet_id" value="<?= (int)$toiletId ?>">

          <div class="mb-3" data-camera="checkin">
            <label class="form-label">Photos of toilet condition (before)</label>
            <div class="camera-box">
              <video class="camera-video" data-camera-video autoplay playsinline muted hidden></video>
              <p class="text-muted small mb-2" data-camera-status>Click start camera to take photos.</p>
              <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-primary" data-camera-start><i class="bi bi-camera-video"></i> Start Camera</button>
                <button type="button" class="btn btn-brand" data-camera-capture disabled><i class="bi bi-camera"></i> Take Photo</button>
                <button type="button" class="btn btn-outline-secondary" data-camera-stop hidden><i class="bi bi-stop-circle"></i> Stop Camera</button>
              </div>
              <input type="file" name="photos[]" accept="image/jpeg,image/png" multiple hidden data-camera-input>
              <canvas class="camera-canvas" data-camera-canvas hidden></canvas>
              <div class="photo-preview-wrap" data-camera-preview></div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Comment</label>
            <textarea name="comment" class="form-control" rows="3" placeholder="e.g. Floor wet and rubbish bin full."></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand"><i class="bi bi-check2-circle"></i> Submit Check-In</button>
            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
