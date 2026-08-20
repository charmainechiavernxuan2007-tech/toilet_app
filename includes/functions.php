<?php
require_once __DIR__ . '/../config/db.php';

/** ---------- Flash messages ---------- */
function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function renderFlashes(): void {
    foreach (getFlashes() as $f) {
        $cls = $f['type'] === 'error' ? 'danger' : $f['type'];
        echo '<div class="alert alert-' . htmlspecialchars($cls) . ' alert-dismissible fade show" role="alert">'
            . htmlspecialchars($f['message'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

/** ---------- Formatting ---------- */
function fmtDateTime(?string $dt): string {
    if (!$dt) return '-';
    return date('d M Y, h:i A', strtotime($dt));
}
function fmtDate(?string $dt): string {
    if (!$dt) return '-';
    return date('d M Y', strtotime($dt));
}
function fmtTime(?string $dt): string {
    if (!$dt) return '-';
    return date('h:i A', strtotime($dt));
}

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** ---------- Toilet / assignment helpers ---------- */
function getToiletsForUser(int $userId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT t.* FROM toilets t
         JOIN user_toilets ut ON ut.toilet_id = t.id
         WHERE ut.user_id = ? AND t.status = 'active'
         ORDER BY t.code"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUsersForToilet(int $toiletId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT u.* FROM users u
         JOIN user_toilets ut ON ut.user_id = u.id
         WHERE ut.toilet_id = ? AND u.status = 'active'
         ORDER BY u.full_name"
    );
    $stmt->execute([$toiletId]);
    return $stmt->fetchAll();
}

function userAssignedToToilet(int $userId, int $toiletId): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT 1 FROM user_toilets WHERE user_id = ? AND toilet_id = ?");
    $stmt->execute([$userId, $toiletId]);
    return (bool)$stmt->fetchColumn();
}

/** Returns the user's currently active (not yet checked out) session, if any. */
function getActiveSessionForUser(int $userId): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT s.*, t.code AS toilet_code, t.name AS toilet_name
         FROM toilet_sessions s
         JOIN toilets t ON t.id = s.toilet_id
         WHERE s.user_id = ? AND s.status = 'active'
         ORDER BY s.id DESC LIMIT 1"
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** ---------- Photo upload ---------- */
/**
 * Handles a multi-file upload input, validates and stores files.
 * Returns array of stored relative paths (for DB) on success.
 * Throws RuntimeException on validation failure.
 */
function handlePhotoUploads(string $fieldName, string $subdir): array {
    if (empty($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'][0])) {
        return [];
    }

    $targetDir = UPLOAD_BASE_DIR . '/' . $subdir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $files = $_FILES[$fieldName];
    $count = count($files['name']);
    $stored = [];

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('One of the photos failed to upload (error code ' . $files['error'][$i] . ').');
        }
        if ($files['size'][$i] > MAX_PHOTO_SIZE) {
            throw new RuntimeException('One of the photos exceeds the ' . (MAX_PHOTO_SIZE / 1024 / 1024) . 'MB limit.');
        }

        $tmpPath = $files['tmp_name'][$i];
        $mime = mime_content_type($tmpPath);
        if (!in_array($mime, ALLOWED_PHOTO_TYPES, true)) {
            throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            throw new RuntimeException('Failed to save an uploaded photo.');
        }

        $stored[] = $subdir . '/' . $filename; // relative path stored in DB
    }

    return $stored;
}

function photoUrl(string $relativePath): string {
    return basePathToRoot() . UPLOAD_BASE_URL . '/' . ltrim($relativePath, '/');
}

/** ---------- Session/history helpers ---------- */
function getSessionPhotos(int $sessionId, string $type): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM session_photos WHERE session_id = ? AND photo_type = ? ORDER BY id");
    $stmt->execute([$sessionId, $type]);
    return $stmt->fetchAll();
}

function getToiletHistory(int $toiletId, int $limit = 100): array {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT s.*, u.full_name AS user_name, t.code AS toilet_code, t.name AS toilet_name
         FROM toilet_sessions s
         JOIN users u ON u.id = s.user_id
         JOIN toilets t ON t.id = s.toilet_id
         WHERE s.toilet_id = ?
         ORDER BY s.checkin_time DESC
         LIMIT ?"
    );
    $stmt->bindValue(1, $toiletId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
