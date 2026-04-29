<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');
checkAuth();

// File size limits (bytes)
define('MAX_PDF_SIZE', 100 * 1024 * 1024);    // 100 MB
define('MAX_COVER_SIZE', 10 * 1024 * 1024);   // 10 MB

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

        // Validate file sizes
        if ($_FILES['pdf']['size'] > MAX_PDF_SIZE) {
            http_response_code(400);
            echo json_encode(['error' => 'PDF too large (max 100 MB)']);
            exit;
        }
        if ($_FILES['cover']['size'] > MAX_COVER_SIZE) {
            http_response_code(400);
            echo json_encode(['error' => 'Cover image too large (max 10 MB)']);
            exit;
        }

        // Validate PDF
        $pdfExt = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
        if ($pdfExt !== 'pdf') {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type: PDF required']);
            exit;
        }

        // Validate cover is an image
        $coverInfo = getimagesize($_FILES['cover']['tmp_name']);
        if ($coverInfo === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid cover image']);
            exit;
        }

        // Handle PDF upload
        $pdfFilename = bin2hex(random_bytes(8)) . '.' . $pdfExt;
        move_uploaded_file($_FILES['pdf']['tmp_name'], UPLOADS_PDFS . '/' . $pdfFilename);

        // Handle cover upload and resize
        $coverFilename = null;
        try {
            $coverOrientation = $coverInfo[1] > $coverInfo[0] ? 'vertical' : 'horizontal';
            $correctDir = $coverOrientation === 'vertical' ? COVERS_VERTICAL : COVERS_HORIZONTAL;
            $coverFilename = resizeCover($_FILES['cover']['tmp_name'], $correctDir);
        } catch (Exception $e) {
            if (file_exists(UPLOADS_PDFS . '/' . $pdfFilename)) {
                unlink(UPLOADS_PDFS . '/' . $pdfFilename);
            }
            http_response_code(500);
            echo json_encode(['error' => 'Error processing cover image']);
            exit;
        }

        $slug = generateSlug($title);
        $slug = ensureUniqueSlug($pdo, $slug);

        // Insert into DB with cleanup on failure
        try {
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
        } catch (Exception $e) {
            // Clean up uploaded files on DB failure
            if (file_exists(UPLOADS_PDFS . '/' . $pdfFilename)) {
                unlink(UPLOADS_PDFS . '/' . $pdfFilename);
            }
            $coverDir = $coverOrientation === 'vertical' ? COVERS_VERTICAL : COVERS_HORIZONTAL;
            $coverPath = $coverDir . '/' . $coverFilename;
            if (file_exists($coverPath)) {
                unlink($coverPath);
            }
            http_response_code(500);
            echo json_encode(['error' => 'Error saving book to database']);
            exit;
        }

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'edit':
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid book ID']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $book = $stmt->fetch();

        if (!$book) {
            http_response_code(404);
            echo json_encode(['error' => 'Book not found']);
            exit;
        }

        $newTitle = isset($_POST['title']) ? trim($_POST['title']) : $book['title'];
        if (empty($newTitle)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title is required']);
            exit;
        }

        $pdfFilename = $book['pdf_filename'];
        $coverFilename = $book['cover_filename'];
        $coverOrientation = $book['cover_orientation'];

        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['pdf']['size'] > MAX_PDF_SIZE) {
                http_response_code(400);
                echo json_encode(['error' => 'PDF too large (max 100 MB)']);
                exit;
            }
            $pdfExt = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
            if ($pdfExt !== 'pdf') {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid file type: PDF required']);
                exit;
            }
            $oldPdfPath = UPLOADS_PDFS . '/' . $book['pdf_filename'];
            $pdfFilename = bin2hex(random_bytes(8)) . '.' . $pdfExt;
            move_uploaded_file($_FILES['pdf']['tmp_name'], UPLOADS_PDFS . '/' . $pdfFilename);
            if (file_exists($oldPdfPath)) {
                unlink($oldPdfPath);
            }
        }

        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['cover']['size'] > MAX_COVER_SIZE) {
                http_response_code(400);
                echo json_encode(['error' => 'Cover image too large (max 10 MB)']);
                exit;
            }
            $coverInfo = getimagesize($_FILES['cover']['tmp_name']);
            if ($coverInfo === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid cover image']);
                exit;
            }
            $coverOrientation = $coverInfo[1] > $coverInfo[0] ? 'vertical' : 'horizontal';
            $correctDir = $coverOrientation === 'vertical' ? COVERS_VERTICAL : COVERS_HORIZONTAL;
            try {
                $oldCoverDir = $book['cover_orientation'] === 'horizontal' ? COVERS_HORIZONTAL : COVERS_VERTICAL;
                $oldCoverPath = $oldCoverDir . '/' . $book['cover_filename'];
                $coverFilename = resizeCover($_FILES['cover']['tmp_name'], $correctDir);
                if (file_exists($oldCoverPath) && $oldCoverPath !== $correctDir . '/' . $coverFilename) {
                    unlink($oldCoverPath);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error processing cover image']);
                exit;
            }
        }

        $slug = generateSlug($newTitle);
        $slug = $pdo->prepare('SELECT COUNT(*) FROM books WHERE slug = :slug AND id != :id');
        $slug->execute([':slug' => $slug, ':id' => $id]);
        if ((int)$slug->fetchColumn() > 0) {
            $stmt = $pdo->prepare('SELECT slug FROM books WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $slug = $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare('SELECT slug FROM books WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $originalSlug = $stmt->fetchColumn();
            $counter = 1;
            $baseSlug = generateSlug($newTitle);
            $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM books WHERE slug = :slug');
            $testSlug = $baseSlug;
            while (true) {
                $stmt2->execute([':slug' => $testSlug]);
                if ((int)$stmt2->fetchColumn() === 0 || $testSlug === $originalSlug) {
                    $slug = $testSlug;
                    break;
                }
                $testSlug = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        $stmt = $pdo->prepare('
            UPDATE books SET title = :title, pdf_filename = :pdf, cover_filename = :cover,
                cover_orientation = :orientation, slug = :slug, updated_at = datetime("now")
            WHERE id = :id
        ');
        $stmt->execute([
            ':title' => $newTitle,
            ':pdf' => $pdfFilename,
            ':cover' => $coverFilename,
            ':orientation' => $coverOrientation,
            ':slug' => $slug,
            ':id' => $id,
        ]);

        echo json_encode(['success' => true, 'id' => $id, 'newSlug' => $slug]);
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

        if (!$book) {
            http_response_code(404);
            echo json_encode(['error' => 'Book not found']);
            exit;
        }

        $coverDir = $book['cover_orientation'] === 'horizontal' ? COVERS_HORIZONTAL : COVERS_VERTICAL;
        $coverPath = $coverDir . '/' . $book['cover_filename'];
        if (file_exists($coverPath)) unlink($coverPath);
        $pdfPath = UPLOADS_PDFS . '/' . $book['pdf_filename'];
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }
        $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
