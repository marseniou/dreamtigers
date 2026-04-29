<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
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
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (err) {
                alert('Error deleting book.');
            }
        }
    </script>
</body>
</html>
