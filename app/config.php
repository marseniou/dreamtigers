<?php
// config.php - Database connection, paths, constants
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('DB_PATH', __DIR__ . '/../app.db');
define('COVERS_DIR', __DIR__ . '/../covers');
define('COVERS_VERTICAL', COVERS_DIR . '/vertical');
define('COVERS_HORIZONTAL', COVERS_DIR . '/horizontal');
define('UPLOADS_DIR', __DIR__ . '/../uploads');
define('UPLOADS_PDFS', UPLOADS_DIR . '/pdfs');
define('UPLOADS_COVERS', UPLOADS_DIR . '/covers');
define('FREE_EBOOKS_DIR', __DIR__ . '/../free_ebooks');
define('COVERS_ORIGINAL', __DIR__ . '/../covers_original');
define('COVER_WIDTH_VERTICAL', 419);
define('COVER_HEIGHT_VERTICAL', 595);
define('COVER_WIDTH_HORIZONTAL', 595);
define('COVER_HEIGHT_HORIZONTAL', 419);

// Admin credentials - override via env vars, admin_config.php, or defaults
if (getenv('ADMIN_USERNAME') && getenv('ADMIN_PASSWORD')) {
    define('ADMIN_USERNAME', getenv('ADMIN_USERNAME'));
    define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD'));
} elseif (file_exists(__DIR__ . '/admin_config.php')) {
    require_once __DIR__ . '/admin_config.php';
} else {
    define('ADMIN_USERNAME', 'admin');
    define('ADMIN_PASSWORD', 'dreamtigers');
}

function getDb(): PDO {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $db;
}

function initDirectories(): void {
    foreach ([COVERS_VERTICAL, COVERS_HORIZONTAL, UPLOADS_PDFS, UPLOADS_COVERS] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

initDirectories();

// Create database tables if they don't exist
function initDatabase(): void {
    $db = getDb();
    $db->exec('
        CREATE TABLE IF NOT EXISTS books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            pdf_filename TEXT NOT NULL,
            cover_filename TEXT NOT NULL,
            cover_orientation TEXT DEFAULT "vertical",
            slug TEXT UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
}
initDatabase();
