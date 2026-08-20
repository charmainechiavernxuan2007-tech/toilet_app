<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireUser();

$userId = currentUserId();
$myToilets = getToiletsForUser($userId);
$activeSession = getActiveSessionForUser($userId);

// If exactly one toilet assigned and no active session, offer direct check-in link.
$pageTitle = 'Home';
require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($activeSession): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center">
    <div>
      <i class="bi bi-exclamation-triangle-fill"></i>
      You have an <strong>active check-in</strong> at
      <strong><?= e($activeSession['toilet_code']) ?> - <?= e($activeSession['toilet_name']) ?></strong>
      since <?= fmtDateTime($activeSession['checkin_time']) ?>. Please check out before starting a new session.
    </div>
    <a href="checkout.php?toilet_id=<?= (int)$activeSession['toilet_id'] ?>" class="btn btn-brand btn-sm">Check Out Now</a>
  </div>
<?php endif; ?>

<h4 class="mb-3"><i class="bi bi-signpost-2"></i> Your Assigned Toilets</h4>

<?php if (!$myToilets): ?>
  <div class="alert alert-info">No toilets have been assigned to you yet. Please contact the administrator.</div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($myToilets as $t): ?>
      <div class="col-md-4">
        <div class="card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="mb-0"><?= e($t['code']) ?></h5>
            <?php if ($activeSession && $activeSession['toilet_id'] == $t['id']): ?>
              <span class="status-active">Active</span>
            <?php endif; ?>
          </div>
          <p class="text-muted mb-1"><?= e($t['name']) ?></p>
          <?php if ($t['location']): ?><p class="text-muted small mb-3"><i class="bi bi-geo-alt"></i> <?= e($t['location']) ?></p><?php endif; ?>
          <div class="mt-auto d-flex gap-2">
            <?php if ($activeSession && $activeSession['toilet_id'] == $t['id']): ?>
              <a href="checkout.php?toilet_id=<?= (int)$t['id'] ?>" class="btn btn-brand btn-sm w-100">Check Out</a>
            <?php elseif ($activeSession): ?>
              <button class="btn btn-outline-secondary btn-sm w-100" disabled title="Finish your active session first">Check In</button>
            <?php else: ?>
              <a href="checkin.php?toilet_id=<?= (int)$t['id'] ?>" class="btn btn-brand btn-sm w-100">Check In</a>
            <?php endif; ?>
            <a href="history.php?toilet_id=<?= (int)$t['id'] ?>" class="btn btn-outline-secondary btn-sm w-100">History</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
