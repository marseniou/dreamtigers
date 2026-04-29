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
$remainingBooks = $featured ? array_slice($books, 11) : [];

$ogTitle = 'Dreamtigers - εκδόσεις εξωτερικής καύσης-εσωτερικής κατανάλωσης';
$ogDescription = 'Ανακαλύψτε και διαβάστε δωρεάν ebooks από τις εκδόσεις Dreamtigers, επιμέλεια Yannis Adamis.';
$ogUrl = $baseUrl . '/index.php';
$ogImage = $baseUrl . '/logo.png';

if ($featured) {
    $coverDir = $featured['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
    $ogImage = $baseUrl . "/covers/{$coverDir}/" . rawurlencode($featured['cover_filename']);
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreamtigers - εκδόσεις εξωτερικής καύσης-εσωτερικής κατανάλωσης</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="shortcut icon" href="favicon.png" type="image/png">

    <meta property="og:locale" content="el_GR">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($ogUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:site_name" content="Dreamtigers">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
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
        $featuredCoverPath = "covers/{$coverDir}/" . rawurlencode($featured['cover_filename']);
        ?>
        <div class="featured-content">
            <a href="book.php?slug=<?= htmlspecialchars(urlencode($featured['slug']), ENT_QUOTES, 'UTF-8') ?>">
                <img src="<?= htmlspecialchars($featuredCoverPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($featured['title'], ENT_QUOTES, 'UTF-8') ?>" class="featured-cover">
            </a>
            <div class="featured-info">
                <span class="featured-label">ΝΕΑ ΕΚΔΟΣΗ</span>
                <h2 class="featured-title"><?= htmlspecialchars($featured['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <a href="book.php?slug=<?= htmlspecialchars(urlencode($featured['slug']), ENT_QUOTES, 'UTF-8') ?>" class="featured-cta">Διαβάστε τώρα</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($gridBooks)): ?>
    <section class="collection-section">
        <h2 class="collection-title">Η Συλλογή</h2>
        <div class="book-grid">
            <?php foreach ($gridBooks as $book):
                $bookCoverDir = $book['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
                $bookCoverPath = "covers/{$bookCoverDir}/" . rawurlencode($book['cover_filename']);
            ?>
            <a href="book.php?slug=<?= htmlspecialchars(urlencode($book['slug']), ENT_QUOTES, 'UTF-8') ?>" class="book-grid-item">
                <img src="<?= htmlspecialchars($bookCoverPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                <span class="book-grid-title"><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($remainingBooks)): ?>
        <div class="book-grid book-grid-hidden" id="remainingBooks">
            <?php foreach ($remainingBooks as $book):
                $bookCoverDir = $book['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
                $bookCoverPath = "covers/{$bookCoverDir}/" . rawurlencode($book['cover_filename']);
            ?>
            <a href="book.php?slug=<?= htmlspecialchars(urlencode($book['slug']), ENT_QUOTES, 'UTF-8') ?>" class="book-grid-item">
                <img src="<?= htmlspecialchars($bookCoverPath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                <span class="book-grid-title"><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="load-more-container">
            <button class="btn-load-more" id="loadMoreBtn" onclick="loadMoreBooks()">Δείτε όλα τα βιβλία</button>
        </div>
        <?php endif; ?>
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

    <script>
        function loadMoreBooks() {
            const grid = document.getElementById('remainingBooks');
            const btn = document.getElementById('loadMoreBtn');
            if (grid) {
                grid.classList.remove('book-grid-hidden');
                btn.style.display = 'none';
            }
        }
    </script>
</body>
</html>
