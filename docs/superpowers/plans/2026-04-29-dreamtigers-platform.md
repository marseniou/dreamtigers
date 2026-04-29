# Dreamtigers Platform Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a lightweight PHP + SQLite publishing platform with a refined single-book-per-slide carousel front page, PDF viewer, and admin upload panel.

**Architecture:** Plain PHP reads SQLite for book catalog. Front page renders a CSS-driven carousel with left/right arrows and bullet indicators. Book page embeds PDFs natively. Admin panel uses HTTP Basic Auth and PHP GD for cover auto-resizing.

**Tech Stack:** PHP 7.4+, SQLite3, GD library, vanilla JS for carousel, CSS Grid/Flexbox

---

## File Structure

| File | Responsibility |
|------|----------------|
| `style.css` | All styles: hero, carousel, book page, admin, responsive |
| `config.php` | DB connection, paths, constants |
| `helpers.php` | Slug generation, cover resize, auth check |
| `index.php` | Front page: logo, carousel, bullets, navigation |
| `book.php` | Book detail page with embedded PDF viewer |
| `admin.php` | Protected admin: list, add, edit, delete |
| `api.php` | AJAX endpoints for admin CRUD |
| `init_db.php` | One-time script to scan folders, populate DB, resize covers |
| `covers/vertical/` | Resized vertical covers (419×595) |
| `covers/horizontal/` | Resized horizontal covers (595×419) |
| `uploads/pdfs/` | Admin-uploaded PDFs |
| `uploads/covers/` | Admin-uploaded covers (will be resized to `covers/`) |

---

### Task 1: Core Infrastructure (config, helpers, DB setup, style.css base)

**Files:**
- Create: `config.php`
- Create: `helpers.php`
- Create: `style.css`
- Create: `app.db` (created by config.php)

- [ ] **Step 1: Create config.php**

```php
<?php
// config.php - Database connection, paths, constants
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('DB_PATH', __DIR__ . '/app.db');
define('COVERS_DIR', __DIR__ . '/covers');
define('COVERS_VERTICAL', COVERS_DIR . '/vertical');
define('COVERS_HORIZONTAL', COVERS_DIR . '/horizontal');
define('UPLOADS_DIR', __DIR__ . '/uploads');
define('UPLOADS_PDFS', UPLOADS_DIR . '/pdfs');
define('UPLOADS_COVERS', UPLOADS_DIR . '/covers');
define('FREE_EBOOKS_DIR', __DIR__ . '/free_ebooks');
define('COVERS_ORIGINAL', __DIR__ . '/covers_original');
define('COVER_WIDTH_VERTICAL', 419);
define('COVER_HEIGHT_VERTICAL', 595);
define('COVER_WIDTH_HORIZONTAL', 595);
define('COVER_HEIGHT_HORIZONTAL', 419);
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'dreamtigers'); // Change this!

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
```

- [ ] **Step 2: Create helpers.php**

```php
<?php
// helpers.php - Slug generation, cover resize, auth check
require_once __DIR__ . '/config.php';

function generateSlug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9_\-\s]/u', '', $slug);
    $slug = preg_replace('/[\s_]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'book-' . time();
}

function ensureUniqueSlug(PDO $db, string $slug): string {
    $original = $slug;
    $counter = 1;
    $stmt = $db->prepare('SELECT COUNT(*) FROM books WHERE slug = :slug');
    while (true) {
        $stmt->execute([':slug' => $slug]);
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $original . '-' . $counter;
        $counter++;
    }
}

function resizeCover(string $sourcePath, string $destDir): string {
    $info = getimagesize($sourcePath);
    if ($info === false) {
        throw new Exception('Invalid image: ' . $sourcePath);
    }
    [$width, $height, $type] = $info;
    $isVertical = $height > $width;

    $targetW = $isVertical ? COVER_WIDTH_VERTICAL : COVER_WIDTH_HORIZONTAL;
    $targetH = $isVertical ? COVER_HEIGHT_VERTICAL : COVER_HEIGHT_HORIZONTAL;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($sourcePath);
            $ext = 'jpg';
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($sourcePath);
            $ext = 'png';
            break;
        case IMAGETYPE_GIF:
            $srcImage = imagecreatefromgif($sourcePath);
            $ext = 'gif';
            break;
        default:
            throw new Exception('Unsupported image type: ' . $type);
    }

    $destImage = imagecreatetruecolor($targetW, $targetH);
    imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, $targetW, $targetH, $width, $height);
    imagedestroy($srcImage);

    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $destPath = $destDir . '/' . $filename;

    if ($ext === 'png') {
        imagepng($destImage, $destPath);
    } else {
        imagejpeg($destImage, $destPath, 90);
    }
    imagedestroy($destImage);

    return $filename;
}

function checkAuth(): void {
    if (!isset($_SERVER['PHP_AUTH_USER']) ||
        $_SERVER['PHP_AUTH_USER'] !== ADMIN_USERNAME ||
        $_SERVER['PHP_AUTH_PW'] !== ADMIN_PASSWORD) {
        header('WWW-Authenticate: Basic realm="Dreamtigers Admin"');
        header('HTTP/1.0 401 Unauthorized');
        exit('Authentication required.');
    }
}
```

- [ ] **Step 3: Create style.css base**

```css
/* style.css - Minimal & refined styles for Dreamtigers */

*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

:root {
    --color-bg: #ffffff;
    --color-text: #1a1a1a;
    --color-text-secondary: #666666;
    --color-accent: #2c2c2c;
    --color-border: #e0e0e0;
    --color-light: #f5f5f5;
    --font-serif: 'Georgia', 'Times New Roman', serif;
    --font-sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --max-width: 1400px;
    --spacing: 24px;
}

body {
    font-family: var(--font-sans);
    color: var(--color-text);
    background: var(--color-bg);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}

h1, h2, h3 {
    font-family: var(--font-serif);
    font-weight: normal;
}

.container {
    max-width: var(--max-width);
    margin: 0 auto;
    padding: 0 var(--spacing);
}

/* Hero */
.hero {
    text-align: center;
    padding: 60px var(--spacing) 40px;
}

.hero img.logo {
    max-width: 200px;
    height: auto;
    margin-bottom: 20px;
}

.hero h1 {
    font-size: 2.5rem;
    margin-bottom: 8px;
}

.hero .tagline {
    color: var(--color-text-secondary);
    font-size: 1rem;
    letter-spacing: 0.5px;
}

/* Carousel */
.carousel-section {
    padding: 40px 0 80px;
}

.carousel-container {
    position: relative;
    max-width: 500px;
    margin: 0 auto;
}

.carousel-slide {
    display: none;
    text-align: center;
}

.carousel-slide.active {
    display: block;
}

.carousel-slide img {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.carousel-slide img:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 30px rgba(0,0,0,0.12);
}

.carousel-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.9);
    border: 1px solid var(--color-border);
    width: 48px;
    height: 48px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.5rem;
    color: var(--color-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 10;
}

.carousel-arrow:hover {
    background: var(--color-accent);
    color: white;
}

.carousel-arrow.prev { left: -60px; }
.carousel-arrow.next { right: -60px; }

.carousel-bullets {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}

.carousel-bullet {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--color-border);
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.carousel-bullet.active {
    background: var(--color-accent);
    transform: scale(1.2);
}

.carousel-title {
    font-family: var(--font-serif);
    font-size: 1.5rem;
    margin-top: 24px;
    margin-bottom: 16px;
}

.carousel-cta {
    display: inline-block;
    padding: 12px 32px;
    background: var(--color-accent);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.2s ease;
}

.carousel-cta:hover {
    background: #000;
}

/* Footer */
.footer {
    text-align: center;
    padding: 40px var(--spacing);
    border-top: 1px solid var(--color-border);
    color: var(--color-text-secondary);
    font-size: 0.9rem;
}

.footer a {
    color: var(--color-text);
    text-decoration: none;
}

/* Book Page */
.book-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px var(--spacing);
}

.book-header {
    margin-bottom: 40px;
}

.book-header h1 {
    font-size: 2rem;
    margin-bottom: 16px;
}

.book-back {
    display: inline-block;
    color: var(--color-text-secondary);
    text-decoration: none;
    margin-bottom: 20px;
}

.book-back:hover {
    color: var(--color-accent);
}

.book-pdf-container {
    width: 100%;
    height: 80vh;
    border: 1px solid var(--color-border);
    border-radius: 4px;
}

.book-pdf-container iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* Admin */
.admin-section {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px var(--spacing);
}

.admin-header {
    margin-bottom: 40px;
}

.admin-header h1 {
    font-size: 2rem;
    margin-bottom: 8px;
}

.admin-form {
    margin-bottom: 40px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--color-border);
    border-radius: 4px;
    font-size: 1rem;
}

.form-group input[type="text"]:focus {
    outline: none;
    border-color: var(--color-accent);
}

.btn {
    padding: 10px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s ease;
}

.btn-primary {
    background: var(--color-accent);
    color: white;
}

.btn-primary:hover {
    background: #000;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.book-list {
    border-top: 1px solid var(--color-border);
}

.book-list-item {
    display: flex;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid var(--color-border);
    gap: 16px;
}

.book-list-item img {
    width: 60px;
    height: auto;
    border-radius: 4px;
}

.book-list-item .title {
    flex: 1;
    font-family: var(--font-serif);
    font-size: 1.1rem;
}

.book-list-item .actions {
    display: flex;
    gap: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .carousel-arrow {
        width: 36px;
        height: 36px;
        font-size: 1.2rem;
    }
    .carousel-arrow.prev { left: -20px; }
    .carousel-arrow.next { right: -20px; }
    .hero h1 { font-size: 2rem; }
    .carousel-container { max-width: 90%; }
}
```

- [ ] **Step 4: Create database initialization**

Append to `config.php` (at end of file):

```php
// Create database tables if they don't exist
function initDatabase(): void {
    if (!file_exists(DB_PATH)) {
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
}
initDatabase();
```

- [ ] **Step 5: Commit**

```bash
cd /Volumes/MrBig/poplar/ai-sites/dreamtigers
git add config.php helpers.php style.css
git commit -m "feat: add core infrastructure (config, helpers, base CSS, DB setup)"
```

---

### Task 2: Init Script - Scan Folders and Populate Database

**Files:**
- Create: `init_db.php`
- Modify: `helpers.php` (add matchCoverToPdf helper)

- [ ] **Step 1: Add matchCoverToPdf to helpers.php**

Add to `helpers.php`:

```php
function matchCoverToPdf(string $pdfBaseName, array $coverFiles): ?string {
    $lower = strtolower($pdfBaseName);
    $matches = [];
    foreach ($coverFiles as $cover) {
        $coverLower = strtolower(pathinfo($cover, PATHINFO_FILENAME));
        if (str_contains($coverLower, $lower)) {
            $matches[] = $cover;
        }
    }
    return $matches[0] ?? null;
}
```

- [ ] **Step 2: Create init_db.php**

```php
<?php
// init_db.php - One-time script: scan folders, populate DB, resize covers
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$pdo = getDb();

// Create books table if not exists
$pdo->exec('
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

// Scan PDFs
$pdfs = glob(FREE_EBOOKS_DIR . '/*.pdf');
if (empty($pdfs)) {
    exit("No PDFs found in " . FREE_EBOOKS_DIR . "\n");
}

// Scan original covers
$coverFiles = [];
if (is_dir(COVERS_ORIGINAL)) {
    foreach (scandir(COVERS_ORIGINAL) as $f) {
        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $f)) {
            $coverFiles[] = $f;
        }
    }
}

echo "Found " . count($pdfs) . " PDFs and " . count($coverFiles) . " covers\n";

$count = 0;
foreach ($pdfs as $pdfPath) {
    $pdfFilename = basename($pdfPath);
    $pdfBase = pathinfo($pdfFilename, PATHINFO_FILENAME);

    // Generate title from filename (humanized)
    $title = ucwords(str_replace(['_', '-'], ' ', $pdfBase));

    // Find matching cover
    $coverFile = matchCoverToPdf($pdfBase, $coverFiles);
    if (!$coverFile) {
        echo "No cover found for: $pdfFilename\n";
        continue;
    }

    $coverSrcPath = COVERS_ORIGINAL . '/' . $coverFile;

    // Resize cover
    try {
        $coverDestFilename = resizeCover($coverSrcPath, COVERS_VERTICAL . '/../' . (getimagesize($coverSrcPath)[1] > getimagesize($coverSrcPath)[0] ? '' : 'horizontal'));
        // Determine orientation
        $info = getimagesize($coverSrcPath);
        $isVertical = $info[1] > $info[0];
        $destDir = $isVertical ? COVERS_VERTICAL : COVERS_HORIZONTAL;
        $coverDestFilename = resizeCover($coverSrcPath, $destDir);
        $orientation = $isVertical ? 'vertical' : 'horizontal';
    } catch (Exception $e) {
        echo "Error resizing cover for $pdfFilename: " . $e->getMessage() . "\n";
        continue;
    }

    // Generate unique slug
    $slug = generateSlug($title);
    $slug = ensureUniqueSlug($pdo, $slug);

    // Insert into DB
    $stmt = $pdo->prepare('
        INSERT INTO books (title, pdf_filename, cover_filename, cover_orientation, slug)
        VALUES (:title, :pdf, :cover, :orientation, :slug)
    ');
    $stmt->execute([
        ':title' => $title,
        ':pdf' => $pdfFilename,
        ':cover' => $coverDestFilename,
        ':orientation' => $orientation,
        ':slug' => $slug,
    ]);
    $count++;
    echo "Added: $title ($slug)\n";
}

echo "\nDone! Added $count books.\n";
```

- [ ] **Step 3: Run init_db.php to populate database**

```bash
cd /Volumes/MrBig/poplar/ai-sites/dreamtigers
php init_db.php
```

Expected output: List of books added, count summary.

- [ ] **Step 4: Commit**

```bash
git add init_db.php helpers.php
git commit -m "feat: add init script to scan folders and populate database"
```

---

### Task 3: Front Page (index.php) with Carousel

**Files:**
- Create: `index.php`
- Create: `carousel.js` (inline in index.php)

- [ ] **Step 1: Create index.php**

```php
<?php
require_once __DIR__ . '/config.php';

$pdo = getDb();
$stmt = $pdo->query('SELECT * FROM books ORDER BY created_at DESC');
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreamtigers - Free Publishing Platform</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="hero">
        <img src="logo.png" alt="Dreamtigers" class="logo">
        <h1>Dreamtigers</h1>
        <p class="tagline">Free publishing platform</p>
    </header>

    <?php if (!empty($books)): ?>
    <section class="carousel-section">
        <div class="carousel-container" id="carousel">
            <?php foreach ($books as $idx => $book):
                $coverDir = $book['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
                $coverPath = "covers/{$coverDir}/{$book['cover_filename']}";
            ?>
            <div class="carousel-slide <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>">
                <a href="book.php?slug=<?= htmlspecialchars($book['slug']) ?>">
                    <img src="<?= htmlspecialchars($coverPath) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                </a>
                <h2 class="carousel-title"><?= htmlspecialchars($book['title']) ?></h2>
                <a href="book.php?slug=<?= htmlspecialchars($book['slug']) ?>" class="carousel-cta">View Book</a>
            </div>
            <?php endforeach; ?>

            <button class="carousel-arrow prev" onclick="changeSlide(-1)">&#8249;</button>
            <button class="carousel-arrow next" onclick="changeSlide(1)">&#8250;</button>
        </div>

        <div class="carousel-bullets" id="bullets">
            <?php foreach ($books as $idx => $book): ?>
            <button class="carousel-bullet <?= $idx === 0 ? 'active' : '' ?>"
                    onclick="goToSlide(<?= $idx ?>)"
                    aria-label="Go to slide <?= $idx + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
    <section class="carousel-section">
        <p style="text-align: center; color: var(--color-text-secondary);">No books available yet.</p>
    </section>
    <?php endif; ?>

    <footer class="footer">
        <p>Published by <strong>Yannis Adamis</strong></p>
        <p>Platform managed by <strong>Marios Arseniou</strong></p>
    </footer>

    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const bullets = document.querySelectorAll('.carousel-bullet');
        const totalSlides = slides.length;

        function showSlide(index) {
            slides[currentSlide].classList.remove('active');
            bullets[currentSlide].classList.remove('active');
            currentSlide = (index + totalSlides) % totalSlides;
            slides[currentSlide].classList.add('active');
            bullets[currentSlide].classList.add('active');
        }

        function changeSlide(direction) {
            showSlide(currentSlide + direction);
        }

        function goToSlide(index) {
            showSlide(index);
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') changeSlide(-1);
            if (e.key === 'ArrowRight') changeSlide(1);
        });

        // Touch/swipe support
        let touchStartX = 0;
        const carousel = document.getElementById('carousel');
        carousel.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        carousel.addEventListener('touchend', (e) => {
            const diff = touchStartX - e.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) {
                changeSlide(diff > 0 ? 1 : -1);
            }
        });
    </script>
</body>
</html>
```

- [ ] **Step 2: Test by running PHP server**

```bash
cd /Volumes/MrBig/poplar/ai-sites/dreamtigers
php -S localhost:8000
```

Open http://localhost:8000 - should show logo, carousel with first book, arrows, bullets.

- [ ] **Step 3: Commit**

```bash
git add index.php
git commit -m "feat: add front page with single-book carousel"
```

---

### Task 4: Book Page (book.php) with PDF Viewer

**Files:**
- Create: `book.php`

- [ ] **Step 1: Create book.php**

```php
<?php
require_once __DIR__ . '/config.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: index.php');
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT * FROM books WHERE slug = :slug LIMIT 1');
$stmt->execute([':slug' => $slug]);
$book = $stmt->fetch();

if (!$book) {
    header('HTTP/1.0 404 Not Found');
    exit('Book not found.');
}

$pdfPath = 'free_ebooks/' . $book['pdf_filename'];
if (!file_exists($pdfPath)) {
    header('HTTP/1.0 404 Not Found');
    exit('Book PDF not available.');
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['title']) ?> - Dreamtigers</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="book-page">
        <a href="index.php" class="book-back">&larr; Back to all books</a>
        <div class="book-header">
            <h1><?= htmlspecialchars($book['title']) ?></h1>
        </div>
        <div class="book-pdf-container">
            <iframe src="<?= htmlspecialchars($pdfPath) ?>"></iframe>
        </div>
    </main>
</body>
</html>
```

- [ ] **Step 2: Test book page**

Visit http://localhost:8000/book.php?slug=<any-slug> - should show PDF embedded.

- [ ] **Step 3: Commit**

```bash
git add book.php
git commit -m "feat: add book page with embedded PDF viewer"
```

---

### Task 5: Admin Panel (admin.php + api.php)

**Files:**
- Create: `admin.php`
- Create: `api.php`

- [ ] **Step 1: Create api.php**

```php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');
checkAuth();

$pdo = getDb();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        if (!isset($_FILES['pdf']) || !isset($_FILES['cover']) || !isset($_POST['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        $title = trim($_POST['title']);
        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title is required']);
            exit;
        }

        // Handle PDF upload
        $pdfExt = pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION);
        $pdfFilename = bin2hex(random_bytes(8)) . '.' . $pdfExt;
        move_uploaded_file($_FILES['pdf']['tmp_name'], UPLOADS_PDFS . '/' . $pdfFilename);

        // Handle cover upload and resize
        $coverOrientation = 'vertical';
        try {
            $coverFilename = resizeCover($_FILES['cover']['tmp_name'], COVERS_VERTICAL);
            $info = getimagesize($_FILES['cover']['tmp_name']);
            $coverOrientation = $info[1] > $info[0] ? 'vertical' : 'horizontal';
            // Re-resize to correct directory
            $correctDir = $coverOrientation === 'vertical' ? COVERS_VERTICAL : COVERS_HORIZONTAL;
            unlink(COVERS_VERTICAL . '/' . $coverFilename);
            $coverFilename = resizeCover($_FILES['cover']['tmp_name'], $correctDir);
        } catch (Exception $e) {
            unlink(UPLOADS_PDFS . '/' . $pdfFilename);
            http_response_code(500);
            echo json_encode(['error' => 'Invalid cover image: ' . $e->getMessage()]);
            exit;
        }

        $slug = generateSlug($title);
        $slug = ensureUniqueSlug($pdo, $slug);

        $stmt = $pdo->prepare('
            INSERT INTO books (title, pdf_filename, cover_filename, cover_orientation, slug)
            VALUES (:title, :pdf, :cover, :orientation, :slug)
        ');
        $stmt->execute([
            ':title' => $title,
            ':pdf' => $pdfFilename,
            ':cover' => $coverFilename,
            ':orientation' => $coverOrientation,
            ':slug' => $slug,
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid book ID']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $book = $stmt->fetch();

        if ($book) {
            $coverDir = $book['cover_orientation'] === 'horizontal' ? COVERS_HORIZONTAL : COVERS_VERTICAL;
            $coverPath = $coverDir . '/' . $book['cover_filename'];
            if (file_exists($coverPath)) unlink($coverPath);
            if (file_exists(UPLOADS_PDFS . '/' . $book['pdf_filename'])) {
                unlink(UPLOADS_PDFS . '/' . $book['pdf_filename']);
            }
            $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
```

- [ ] **Step 2: Create admin.php**

```php
<?php
require_once __DIR__ . '/config.php';
checkAuth();

$pdo = getDb();
$stmt = $pdo->query('SELECT * FROM books ORDER BY created_at DESC');
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Dreamtigers</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="admin-section">
        <div class="admin-header">
            <h1>Admin Panel</h1>
            <p><a href="index.php">&larr; Back to site</a></p>
        </div>

        <div class="admin-form">
            <h2>Add New Book</h2>
            <form id="addBookForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="pdf">PDF File</label>
                    <input type="file" id="pdf" name="pdf" accept=".pdf" required>
                </div>
                <div class="form-group">
                    <label for="cover">Cover Image</label>
                    <input type="file" id="cover" name="cover" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Book</button>
            </form>
            <p id="addMessage" style="margin-top: 12px;"></p>
        </div>

        <h2>Existing Books</h2>
        <div class="book-list" id="bookList">
            <?php foreach ($books as $book):
                $coverDir = $book['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
                $coverPath = "covers/{$coverDir}/{$book['cover_filename']}";
            ?>
            <div class="book-list-item" id="book-<?= $book['id'] ?>">
                <img src="<?= htmlspecialchars($coverPath) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                <span class="title"><?= htmlspecialchars($book['title']) ?></span>
                <div class="actions">
                    <a href="book.php?slug=<?= htmlspecialchars($book['slug']) ?>" class="btn btn-primary" style="font-size: 0.9rem; padding: 8px 16px;">View</a>
                    <button class="btn btn-danger" onclick="deleteBook(<?= $book['id'] ?>)">Delete</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        document.getElementById('addBookForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'add');

            const msg = document.getElementById('addMessage');
            msg.textContent = 'Adding...';

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    msg.textContent = 'Book added successfully!';
                    msg.style.color = 'green';
                    e.target.reset();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    msg.textContent = 'Error: ' + data.error;
                    msg.style.color = 'red';
                }
            } catch (err) {
                msg.textContent = 'Error adding book.';
                msg.style.color = 'red';
            }
        });

        async function deleteBook(id) {
            if (!confirm('Delete this book?')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('book-' + id).remove();
                }
            } catch (err) {
                alert('Error deleting book.');
            }
        }
    </script>
</body>
</html>
```

- [ ] **Step 3: Test admin panel**

Visit http://localhost:8000/admin.php - will prompt for credentials:
- Username: `admin`
- Password: `dreamtigers`

Test adding a book and deleting a book.

- [ ] **Step 4: Commit**

```bash
git add admin.php api.php
git commit -m "feat: add admin panel with upload/delete functionality"
```

---

### Task 6: Final Cleanup and Testing

**Files:**
- Modify: `.gitignore`
- Verify: all files

- [ ] **Step 1: Update .gitignore**

```
app.db
.cover
.DS_Store
```

- [ ] **Step 2: Add covers_original entry**

Rename original covers folder:
```bash
cd /Volumes/MrBig/poplar/ai-sites/dreamtigers
mv covers covers_original
mkdir -p covers/vertical covers/horizontal
```

- [ ] **Step 3: Run init_db.php again**

```bash
php init_db.php
```

- [ ] **Step 4: Full site test**

```bash
php -S localhost:8000
```

Test:
1. Front page loads with carousel
2. Arrows and bullets navigate correctly
3. Click cover → book page with PDF
4. Admin panel: login, add book, delete book
5. Responsive layout on mobile

- [ ] **Step 5: Final commit**

```bash
git add .
git commit -m "feat: complete dreamtigers platform implementation"
```

---

## Notes

- **Credentials:** Change `ADMIN_USERNAME` and `ADMIN_PASSWORD` in `config.php` before deploying
- **PHP Requirements:** PHP 7.4+ with `pdo_sqlite` and `gd` extensions enabled
- **Cover matching:** The script matches covers to PDFs by checking if the cover filename contains the PDF's base name. Some manual cleanup may be needed for ambiguous matches.
- **PDF serving:** PDFs are served directly from `free_ebooks/` or `uploads/pdfs/`. Ensure your server has correct MIME type for `.pdf` files.
