<?php
require_once __DIR__ . '/config.php';

$pdo = getDb();
$stmt = $pdo->query('SELECT id, title, slug, cover_filename, cover_orientation FROM books ORDER BY created_at DESC');
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
