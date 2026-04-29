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
