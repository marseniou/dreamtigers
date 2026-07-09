<?php
require_once __DIR__ . '/../app/config.php';

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

// Path for the browser (URL)
$pdfUrl = '/free_ebooks/' . $book['pdf_filename'];

// Path for the server (Filesystem)
// Use the constant from config.php which points to the real folder
$pdfFile = FREE_EBOOKS_DIR . '/' . $book['pdf_filename'];

if (!file_exists($pdfFile)) {
    header('HTTP/1.0 404 Not Found');
    exit('Book PDF not available.');
}

$baseUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$bookUrl = $baseUrl . '/book/' . $book['slug'];

$coverDir = $book['cover_orientation'] === 'horizontal' ? 'horizontal' : 'vertical';
$coverUrl = $baseUrl . '/cover.php?o=' . $coverDir . '&f=' . $book['cover_filename'];

$ogTitle = htmlspecialchars($book['title']) . ' - Dreamtigers';
$ogDescription = 'Διαβάστε δωρεάν το "' . htmlspecialchars($book['title']) . '" από τις εκδόσεις Dreamtigers, επιμέλεια Yannis Adamis.';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title><?= $ogTitle ?></title>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/favicon.png" type="image/png">
    <link rel="shortcut icon" href="/favicon.png" type="image/png">

    <meta property="og:locale" content="el_GR">
    <meta property="og:type" content="book">
    <meta property="og:title" content="<?= htmlspecialchars($book['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($bookUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($coverUrl) ?>">
    <meta property="og:site_name" content="Dreamtigers">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($book['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($ogDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($coverUrl) ?>">
</head>
<body>
    <main class="book-page">
        <a href="/" class="book-back">&larr; Back to all books</a>
        <div class="book-header">
            <h1><?= htmlspecialchars($book['title']) ?></h1>
        </div>
        <div class="book-pdf-container">
            <object data="<?= htmlspecialchars($pdfUrl) ?>" type="application/pdf" width="100%" height="100%">
                <p>Your browser doesn't support embedded PDFs. <a href="<?= htmlspecialchars($pdfUrl) ?>">Download the PDF</a> instead.</p>
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
