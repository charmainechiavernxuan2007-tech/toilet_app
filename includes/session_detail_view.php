<?php
/**
 * Expects $session (assoc array from toilet_sessions joined with users/toilets)
 * already loaded and permission-checked by the including page.
 */
$checkinPhotos  = getSessionPhotos((int)$session['id'], 'checkin');
$checkoutPhotos = getSessionPhotos((int)$session['id'], 'checkout');
?>
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-droplet-half"></i> <?= e($session['toilet_code']) ?> - <?= e($session['toilet_name']) ?></span>
    <span class="status-<?= $session['status'] ?>"><?= ucfirst($session['status']) ?></span>
  </div>
  <div class="card-body">
    <p class="mb-1"><strong>Student:</strong> <?= e($session['user_name'] ?? $session['full_name'] ?? '') ?></p>
    <p class="text-muted small">Session #<?= (int)$session['id'] ?></p>

    <div class="row">
      <div class="col-md-6 mb-4">
        <div class="timeline-item">
          <h6 class="mb-1"><i class="bi bi-box-arrow-in-right text-success"></i> Check-In</h6>
          <p class="mb-1 small text-muted"><?= fmtDateTime($session['checkin_time']) ?></p>
          <p class="mb-2"><?= $session['checkin_comment'] ? nl2br(e($session['checkin_comment'])) : '<span class="text-muted">No comment provided.</span>' ?></p>
          <div class="photo-preview-wrap">
            <?php foreach ($checkinPhotos as $p): ?>
              <a href="<?= e(photoUrl($p['photo_path'])) ?>" target="_blank">
                <img src="<?= e(photoUrl($p['photo_path'])) ?>" class="photo-thumb" alt="Check-in photo">
              </a>
            <?php endforeach; ?>
            <?php if (!$checkinPhotos): ?><span class="text-muted small">No photos.</span><?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-6 mb-4">
        <div class="timeline-item" style="border-color:#0d6f6f;">
          <h6 class="mb-1"><i class="bi bi-box-arrow-left text-danger"></i> Check-Out</h6>
          <?php if ($session['checkout_time']): ?>
            <p class="mb-1 small text-muted"><?= fmtDateTime($session['checkout_time']) ?></p>
            <p class="mb-2"><?= $session['checkout_comment'] ? nl2br(e($session['checkout_comment'])) : '<span class="text-muted">No comment provided.</span>' ?></p>
            <div class="photo-preview-wrap">
              <?php foreach ($checkoutPhotos as $p): ?>
                <a href="<?= e(photoUrl($p['photo_path'])) ?>" target="_blank">
                  <img src="<?= e(photoUrl($p['photo_path'])) ?>" class="photo-thumb" alt="Check-out photo">
                </a>
              <?php endforeach; ?>
              <?php if (!$checkoutPhotos): ?><span class="text-muted small">No photos.</span><?php endif; ?>
            </div>
          <?php else: ?>
            <p class="text-muted">Not checked out yet — session still active.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
