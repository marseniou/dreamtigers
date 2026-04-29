<?php
require_once __DIR__ . '/config.php';

$pdo = getDb();
$stmt = $pdo->query('SELECT id, title, slug, cover_filename, cover_orientation FROM books ORDER BY created_at DESC');
$books = $stmt->fetchAll();

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host;

$ogTitle = 'Dreamtigers - εκδόσεις εξωτερικής καύσης-εσωτερικής κατανάλωσης';
$ogDescription = 'Ανακαλύψτε και διαβάστε δωρεάν ebooks από τις εκδόσεις Dreamtigers, επιμέλεια Yannis Adamis.';
$ogUrl = $baseUrl . '/index.php';
$ogImage = $baseUrl . '/logo.png';

if (!empty($books)) {
    $newest = $books[0];
    $coverDir = $newest['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
    $ogImage = $baseUrl . "/covers/{$coverDir}/{$newest['cover_filename']}";
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
    <header class="hero">
        <img src="logo.png" alt="Dreamtigers" class="logo">
        <h1>Dreamtigers</h1>
        <p class="tagline">εκδόσεις εξωτερικής καύσης-εσωτερικής κατανάλωσης</p>
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
        (function() {
            const slides = document.querySelectorAll('.carousel-slide');
            const bullets = document.querySelectorAll('.carousel-bullet');
            const totalSlides = slides.length;
            if (totalSlides === 0) return;

            let currentSlide = 0;

            function showSlide(index) {
                slides[currentSlide].classList.remove('active');
                bullets[currentSlide].classList.remove('active');
                currentSlide = (index + totalSlides) % totalSlides;
                slides[currentSlide].classList.add('active');
                bullets[currentSlide].classList.add('active');
            }

            window.changeSlide = function(direction) {
                showSlide(currentSlide + direction);
            };

            window.goToSlide = function(index) {
                showSlide(index);
            };

            // Keyboard navigation (scoped to non-input elements)
            document.addEventListener('keydown', (e) => {
                const tag = document.activeElement.tagName.toLowerCase();
                if (tag === 'input' || tag === 'textarea') return;
                if (e.key === 'ArrowLeft') window.changeSlide(-1);
                if (e.key === 'ArrowRight') window.changeSlide(1);
            });

            // Touch/swipe support
            const carousel = document.getElementById('carousel');
            if (!carousel) return;
            let touchStartX = 0;
            carousel.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            });
            carousel.addEventListener('touchend', (e) => {
                const diff = touchStartX - e.changedTouches[0].screenX;
                if (Math.abs(diff) > 50) {
                    window.changeSlide(diff > 0 ? 1 : -1);
                }
            });
        })();
    </script>
</body>
</html>
