# Dreamtigers Front Page Redesign — Modern Gallery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the single-book carousel with a modern gallery-style front page featuring a hero, featured book section, and responsive book grid.

**Architecture:** Rewrite `index.php` to query the newest book for a featured section, then display the next 10 books in a CSS Grid. Update `style.css` with gallery styles replacing carousel styles.

**Tech Stack:** PHP 8+, SQLite, vanilla CSS (no frameworks)

---

### Task 1: Rewrite index.php — Hero, Featured Section, Book Grid

**Files:**
- Modify: `/Volumes/MrBig/poplar/ai-sites/dreamtigers/index.php`

- [ ] **Step 1: Rewrite index.php with new layout**

Replace the entire content of `index.php` with:

```php
<?php
require_once __DIR__ . '/config.php';

$pdo = getDb();
$stmt = $pdo->query('SELECT id, title, slug, cover_filename, cover_orientation FROM books ORDER BY created_at DESC');
$books = $stmt->fetchAll();

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host;

$featured = $books[0] ?? null;
$gridBooks = $featured ? array_slice($books, 1, 10) : [];

$ogTitle = 'Dreamtigers - εκδόσεις εξωτερικής καύσης-εσωτερικής κατανάλωσης';
$ogDescription = 'Ανακαλύψτε και διαβάστε δωρεάν ebooks από τις εκδόσεις Dreamtigers, επιμέλεια Yannis Adamis.';
$ogUrl = $baseUrl . '/index.php';
$ogImage = $baseUrl . '/logo.png';

if ($featured) {
    $coverDir = $featured['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
    $ogImage = $baseUrl . "/covers/{$coverDir}/{$featured['cover_filename']}";
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreamtigers - εκδόσεις εξωτερικής καύσης-εσωτερικής κατανάλωσης</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo.png" type="image/png">
    <link rel="shortcut icon" href="logo.png" type="image/png">

    <meta property="og:locale" content="el_GR">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($ogUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:site_name" content="Dreamtigers">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
</head>
<body>
    <header class="gallery-hero">
        <img src="logo.png" alt="Dreamtigers" class="gallery-logo">
        <h1 class="gallery-brand">DREAMTIGERS</h1>
        <p class="gallery-tagline">εκδόσεις εξωτερικής καύσης-εσωτερικής κατανάλωσης</p>
    </header>

    <?php if ($featured): ?>
    <section class="featured-section">
        <?php
        $coverDir = $featured['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
        $coverPath = "covers/{$coverDir}/{$featured['cover_filename']}";
        ?>
        <div class="featured-content">
            <a href="book.php?slug=<?= htmlspecialchars($featured['slug']) ?>">
                <img src="<?= htmlspecialchars($coverPath) ?>" alt="<?= htmlspecialchars($featured['title']) ?>" class="featured-cover">
            </a>
            <div class="featured-info">
                <span class="featured-label">ΝΕΑ ΕΚΔΟΣΗ</span>
                <h2 class="featured-title"><?= htmlspecialchars($featured['title']) ?></h2>
                <a href="book.php?slug=<?= htmlspecialchars($featured['slug']) ?>" class="featured-cta">Διαβάστε τώρα</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($gridBooks)): ?>
    <section class="collection-section">
        <h2 class="collection-title">Η Συλλογή</h2>
        <div class="book-grid">
            <?php foreach ($gridBooks as $book):
                $coverDir = $book['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
                $coverPath = "covers/{$coverDir}/{$book['cover_filename']}";
            ?>
            <a href="book.php?slug=<?= htmlspecialchars($book['slug']) ?>" class="book-grid-item">
                <img src="<?= htmlspecialchars($coverPath) ?>" alt="<?= htmlspecialchars($book['title']) ?>" loading="lazy">
                <span class="book-grid-title"><?= htmlspecialchars($book['title']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <footer class="footer">
        <p>Created by <strong>Marios Arseniou</strong></p>
        <p class="social-links">
            <a href="https://www.facebook.com/thedreamtigers" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg class="facebook-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.675 0h-21.35C.6 0 0 .6 0 1.325v21.351C0 23.4.6 24 1.325 24h11.495v-9.294H9.691v-3.622h3.129V8.413c0-3.1 1.893-4.788 4.658-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116C23.4 24 24 23.4 24 22.675V1.325C24 .6 23.4 0 22.675 0z"/>
                </svg>
                @thedreamtigers
            </a>
        </p>
    </footer>
</body>
</html>
```

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l index.php`
Expected: No syntax errors

- [ ] **Step 3: Verify page loads**

Run: `php -S localhost:8000 &` then `curl -s http://localhost:8000/ | head -30`
Expected: HTML with `gallery-hero`, `featured-section`, `book-grid` elements

---

### Task 2: Rewrite style.css — Gallery Styles

**Files:**
- Modify: `/Volumes/MrBig/poplar/ai-sites/dreamtigers/style.css`

- [ ] **Step 1: Replace carousel and hero styles with gallery styles**

Replace the entire content of `style.css` with:

```css
/* style.css - Modern Gallery styles for Dreamtigers */

*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

:root {
    --color-bg: #ffffff;
    --color-bg-alt: #f8f8f8;
    --color-text: #1a1a1a;
    --color-text-secondary: #666666;
    --color-accent: #1a1a1a;
    --color-border: #e5e5e5;
    --font-serif: 'Georgia', 'Times New Roman', serif;
    --font-sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --max-width: 1200px;
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

/* Gallery Hero */
.gallery-hero {
    text-align: center;
    padding: 64px var(--spacing) 48px;
    border-bottom: 1px solid var(--color-border);
}

.gallery-hero .gallery-logo {
    width: 80px;
    height: auto;
    margin-bottom: 20px;
}

.gallery-hero .gallery-brand {
    font-size: 2rem;
    letter-spacing: 4px;
    margin-bottom: 12px;
}

.gallery-hero .gallery-tagline {
    font-size: 0.75rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--color-text-secondary);
}

/* Featured Section */
.featured-section {
    padding: 64px var(--spacing);
    background: var(--color-bg-alt);
    border-bottom: 1px solid var(--color-border);
}

.featured-content {
    max-width: 700px;
    margin: 0 auto;
    display: flex;
    gap: 40px;
    align-items: center;
}

.featured-cover {
    width: 220px;
    max-height: 320px;
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.1);
    flex-shrink: 0;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.featured-cover:hover {
    transform: scale(1.02);
    box-shadow: 0 6px 32px rgba(0,0,0,0.15);
}

.featured-info {
    flex: 1;
}

.featured-label {
    display: inline-block;
    font-size: 0.7rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--color-text-secondary);
    margin-bottom: 12px;
}

.featured-title {
    font-family: var(--font-serif);
    font-size: 1.8rem;
    margin-bottom: 24px;
    line-height: 1.3;
}

.featured-cta {
    display: inline-block;
    padding: 12px 32px;
    background: var(--color-accent);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.95rem;
    transition: background 0.2s ease;
}

.featured-cta:hover {
    background: #000;
}

/* Collection Section */
.collection-section {
    padding: 64px var(--spacing);
    max-width: var(--max-width);
    margin: 0 auto;
}

.collection-title {
    font-family: var(--font-serif);
    font-size: 1.4rem;
    text-align: center;
    margin-bottom: 40px;
    color: var(--color-text);
}

.book-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 28px;
    justify-items: center;
}

.book-grid-item {
    text-align: center;
    text-decoration: none;
    color: var(--color-text);
    transition: transform 0.2s ease;
}

.book-grid-item:hover {
    transform: translateY(-4px);
}

.book-grid-item img {
    width: 100%;
    max-width: 160px;
    aspect-ratio: 3/4;
    object-fit: contain;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s ease;
}

.book-grid-item:hover img {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.book-grid-title {
    display: block;
    margin-top: 12px;
    font-size: 0.85rem;
    color: var(--color-text-secondary);
    font-family: var(--font-serif);
    line-height: 1.3;
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

.social-links {
    margin-top: 16px;
}

.social-links a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--color-text-secondary);
    transition: color 0.2s ease;
}

.social-links a:hover {
    color: #1877F2;
}

.facebook-icon {
    transition: transform 0.2s ease;
}

.social-links a:hover .facebook-icon {
    transform: scale(1.1);
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

.share-facebook-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #1877F2;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9rem;
    margin-top: 12px;
    transition: background 0.2s ease, transform 0.2s ease;
}

.share-facebook-btn:hover {
    background: #1565C0;
    transform: translateY(-1px);
}

.share-facebook-btn svg {
    flex-shrink: 0;
}

.book-pdf-container {
    width: 100%;
    height: 80vh;
    border: 1px solid var(--color-border);
    border-radius: 4px;
}

.book-pdf-container object {
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

.btn-edit {
    background: #17a2b8;
    color: white;
}

.btn-edit:hover {
    background: #138496;
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

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: var(--color-bg);
    border-radius: 8px;
    padding: 32px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.modal-header h2 {
    font-size: 1.5rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--color-text-secondary);
    padding: 4px 8px;
}

.modal-close:hover {
    color: var(--color-text);
}

/* Responsive */
@media (max-width: 1024px) {
    .book-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .gallery-hero {
        padding: 48px var(--spacing) 36px;
    }

    .gallery-hero .gallery-logo {
        width: 60px;
    }

    .gallery-hero .gallery-brand {
        font-size: 1.5rem;
        letter-spacing: 2px;
    }

    .featured-content {
        flex-direction: column;
        text-align: center;
        gap: 24px;
    }

    .featured-cover {
        width: 180px;
        max-height: 260px;
    }

    .book-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .book-grid-item img {
        max-width: 140px;
    }
}
```

- [ ] **Step 2: Verify CSS is valid**

Run: `php -S localhost:8000 &` then `curl -s http://localhost:8000/ | wc -l`
Expected: Page renders without errors, grid is visible

---

### Task 3: Verify All Pages Work

**Files:**
- Check: `/Volumes/MrBig/poplar/ai-sites/dreamtigers/index.php`
- Check: `/Volumes/MrBig/poplar/ai-sites/dreamtigers/book.php`
- Check: `/Volumes/MrBig/poplar/ai-sites/dreamtigers/admin.php`

- [ ] **Step 1: Verify front page**

Run: `php -S localhost:8000 &` then `curl -s http://localhost:8000/ | grep -c 'book-grid-item'`
Expected: 10 (or fewer if less than 11 books exist)

- [ ] **Step 2: Verify book page still works**

Run: `curl -s "http://localhost:8000/book.php?slug=sxedon-authentikoi" | grep -c 'share-facebook-btn'`
Expected: 1

- [ ] **Step 3: Verify admin page still works**

Run: `curl -s -u admin:dreamtigers http://localhost:8000/admin.php | grep -c 'Edit Book'`
Expected: 1
