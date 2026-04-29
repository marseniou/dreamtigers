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

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host;
$bookUrl = $baseUrl . '/book.php?slug=' . $book['slug'];

$coverDir = $book['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
$coverUrl = $baseUrl . "/covers/{$coverDir}/{$book['cover_filename']}";

$ogTitle = htmlspecialchars($book['title']) . ' - Dreamtigers';
$ogDescription = 'Διαβάστε δωρεάν το "' . htmlspecialchars($book['title']) . '" από τις εκδόσεις Dreamtigers, επιμέλεια Yannis Adamis.';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $ogTitle ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="shortcut icon" href="favicon.png" type="image/png">

    <meta property="og:locale" content="el_GR">
    <meta property="og:type" content="book">
    <meta property="og:title" content="<?= htmlspecialchars($book['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($bookUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($coverUrl) ?>">
    <meta property="og:site_name" content="Dreamtigers">
    <meta property="og:image:width" content="600">
    <meta property="og:image:height" content="800">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($book['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($coverUrl) ?>">
</head>
<body>
    <main class="book-page">
        <a href="index.php" class="book-back">&larr; Back to all books</a>
        <div class="book-header">
            <h1><?= htmlspecialchars($book['title']) ?></h1>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://dreamtigers.gr/book/' . $book['slug']) ?>&quote=<?= urlencode('Διαβάστε δωρεάν: ' . $book['title'] . ' - Dreamtigers') ?>"
               target="_blank"
               rel="noopener noreferrer"
               class="share-facebook-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.675 0h-21.35C.6 0 0 .6 0 1.325v21.351C0 23.4.6 24 1.325 24h11.495v-9.294H9.691v-3.622h3.129V8.413c0-3.1 1.893-4.788 4.658-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116C23.4 24 24 23.4 24 22.675V1.325C24 .6 23.4 0 22.675 0z"/>
                </svg>
                Share on Facebook
            </a>
        </div>
        <div class="book-pdf-container">
            <object data="<?= htmlspecialchars($pdfPath) ?>" type="application/pdf" width="100%" height="100%">
                <p>Your browser doesn't support embedded PDFs. <a href="<?= htmlspecialchars($pdfPath) ?>">Download the PDF</a> instead.</p>
            </object>
        </div>
    </main>

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
