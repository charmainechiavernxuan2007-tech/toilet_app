<?php
/**
 * Database connection (PDO).
 * Update these 4 constants for your environment.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'toilet_monitor');
define('DB_USER', 'root');
define('DB_PASS', '0044');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

// ---- Upload configuration ----
define('UPLOAD_BASE_DIR', __DIR__ . '/../uploads');   // filesystem path
define('UPLOAD_BASE_URL', 'uploads');                  // relative URL path
define('MAX_PHOTO_SIZE', 8 * 1024 * 1024);              // 8MB per photo
define('ALLOWED_PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
