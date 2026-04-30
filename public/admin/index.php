<?php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/helpers.php';
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
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <main class="admin-section">
        <div class="admin-header">
            <h1>Admin Panel</h1>
            <p><a href="../">&larr; Back to site</a></p>
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
                $coverPath = "/covers/{$coverDir}/{$book['cover_filename']}";
            ?>
            <div class="book-list-item" id="book-<?= $book['id'] ?>">
                <img src="<?= htmlspecialchars($coverPath) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                <span class="title"><?= htmlspecialchars($book['title']) ?></span>
                <div class="actions">
                    <a href="book/<?= htmlspecialchars($book['slug']) ?>" class="btn btn-primary" style="font-size: 0.9rem; padding: 8px 16px;">View</a>
                    <button class="btn btn-edit" onclick="openEditModal(<?= $book['id'] ?>, '<?= htmlspecialchars(addslashes($book['title'])) ?>', '<?= htmlspecialchars($book['pdf_filename']) ?>')">Edit</button>
                    <button class="btn btn-danger" onclick="deleteBook(<?= $book['id'] ?>)">Delete</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div id="editModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Book</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editBookForm" enctype="multipart/form-data">
                <input type="hidden" id="editId" name="id">
                <div class="form-group">
                    <label for="editTitle">Title</label>
                    <input type="text" id="editTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label>Current PDF: <span id="editCurrentPdf"></span></label>
                    <label for="editPdf">Replace PDF (optional)</label>
                    <input type="file" id="editPdf" name="pdf" accept=".pdf">
                </div>
                <div class="form-group">
                    <label for="editCover">Replace Cover (optional)</label>
                    <input type="file" id="editCover" name="cover" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
            <p id="editMessage" style="margin-top: 12px;"></p>
        </div>
    </div>

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

        function openEditModal(id, title, pdfFilename) {
            document.getElementById('editId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editCurrentPdf').textContent = pdfFilename;
            document.getElementById('editPdf').value = '';
            document.getElementById('editCover').value = '';
            document.getElementById('editMessage').textContent = '';
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editBookForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'edit');

            const msg = document.getElementById('editMessage');
            msg.textContent = 'Saving...';

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    msg.textContent = 'Book updated successfully!';
                    msg.style.color = 'green';
                    setTimeout(() => location.reload(), 1000);
                } else {
                    msg.textContent = 'Error: ' + data.error;
                    msg.style.color = 'red';
                }
            } catch (err) {
                msg.textContent = 'Error updating book.';
                msg.style.color = 'red';
            }
        });

        document.getElementById('editModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('editModal')) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
