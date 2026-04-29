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
            <iframe src="<?= htmlspecialchars($pdfPath) ?>" sandbox="allow-same-origin" title="<?= htmlspecialchars($book['title']) ?>"></iframe>
        </div>
    </main>
</body>
</html>
