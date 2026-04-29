<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');
checkAuth();

$pdo = getDb();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        if (!isset($_FILES['pdf']) || !isset($_FILES['cover']) || !isset($_POST['title'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        $title = trim($_POST['title']);
        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title is required']);
            exit;
        }

        // Handle PDF upload
        $pdfExt = pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION);
        $pdfFilename = bin2hex(random_bytes(8)) . '.' . $pdfExt;
        move_uploaded_file($_FILES['pdf']['tmp_name'], UPLOADS_PDFS . '/' . $pdfFilename);

        // Handle cover upload and resize
        $coverOrientation = 'vertical';
        try {
            $info = getimagesize($_FILES['cover']['tmp_name']);
            $coverOrientation = $info[1] > $info[0] ? 'vertical' : 'horizontal';
            $correctDir = $coverOrientation === 'vertical' ? COVERS_VERTICAL : COVERS_HORIZONTAL;
            $coverFilename = resizeCover($_FILES['cover']['tmp_name'], $correctDir);
        } catch (Exception $e) {
            if (file_exists(UPLOADS_PDFS . '/' . $pdfFilename)) {
                unlink(UPLOADS_PDFS . '/' . $pdfFilename);
            }
            http_response_code(500);
            echo json_encode(['error' => 'Invalid cover image: ' . $e->getMessage()]);
            exit;
        }

        $slug = generateSlug($title);
        $slug = ensureUniqueSlug($pdo, $slug);

        $stmt = $pdo->prepare('
            INSERT INTO books (title, pdf_filename, cover_filename, cover_orientation, slug)
            VALUES (:title, :pdf, :cover, :orientation, :slug)
        ');
        $stmt->execute([
            ':title' => $title,
            ':pdf' => $pdfFilename,
            ':cover' => $coverFilename,
            ':orientation' => $coverOrientation,
            ':slug' => $slug,
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid book ID']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $book = $stmt->fetch();

        if ($book) {
            $coverDir = $book['cover_orientation'] === 'horizontal' ? COVERS_HORIZONTAL : COVERS_VERTICAL;
            $coverPath = $coverDir . '/' . $book['cover_filename'];
            if (file_exists($coverPath)) unlink($coverPath);
            $pdfPath = UPLOADS_PDFS . '/' . $book['pdf_filename'];
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
