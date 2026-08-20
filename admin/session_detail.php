<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT s.*, u.full_name AS user_name, u.username, t.code AS toilet_code, t.name AS toilet_name
     FROM toilet_sessions s
     JOIN users u ON u.id = s.user_id
     JOIN toilets t ON t.id = s.toilet_id
     WHERE s.id = ?"
);
$stmt->execute([$id]);
$session = $stmt->fetch();

if (!$session) {
    flash('error', 'Session not found.');
    header('Location: history.php');
    exit;
}

$pageTitle = 'Session Detail';
require_once __DIR__ . '/../includes/header.php';
?>
<a href="history.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back to History</a>
<?php require_once __DIR__ . '/../includes/session_detail_view.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
