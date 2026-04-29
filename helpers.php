<?php
// helpers.php - Slug generation, cover resize, auth check
require_once __DIR__ . '/config.php';

function generateSlug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9_\-\s]/u', '', $slug);
    $slug = preg_replace('/[\s_]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'book-' . time();
}

function ensureUniqueSlug(PDO $db, string $slug): string {
    $original = $slug;
    $counter = 1;
    $stmt = $db->prepare('SELECT COUNT(*) FROM books WHERE slug = :slug');
    while (true) {
        $stmt->execute([':slug' => $slug]);
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $original . '-' . $counter;
        $counter++;
    }
}

function resizeCover(string $sourcePath, string $destDir): string {
    $info = getimagesize($sourcePath);
    if ($info === false) {
        throw new Exception('Invalid image: ' . $sourcePath);
    }
    [$width, $height, $type] = $info;
    $isVertical = $height > $width;

    $targetW = $isVertical ? COVER_WIDTH_VERTICAL : COVER_WIDTH_HORIZONTAL;
    $targetH = $isVertical ? COVER_HEIGHT_VERTICAL : COVER_HEIGHT_HORIZONTAL;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($sourcePath);
            $ext = 'jpg';
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($sourcePath);
            $ext = 'png';
            break;
        case IMAGETYPE_GIF:
            $srcImage = imagecreatefromgif($sourcePath);
            $ext = 'gif';
            break;
        default:
            throw new Exception('Unsupported image type: ' . $type);
    }

    $destImage = imagecreatetruecolor($targetW, $targetH);
    imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, $targetW, $targetH, $width, $height);
    imagedestroy($srcImage);

    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $destPath = $destDir . '/' . $filename;

    if ($ext === 'png') {
        imagepng($destImage, $destPath);
    } else {
        imagejpeg($destImage, $destPath, 90);
    }
    imagedestroy($destImage);

    return $filename;
}

function checkAuth(): void {
    if (!isset($_SERVER['PHP_AUTH_USER']) ||
        $_SERVER['PHP_AUTH_USER'] !== ADMIN_USERNAME ||
        $_SERVER['PHP_AUTH_PW'] !== ADMIN_PASSWORD) {
        header('WWW-Authenticate: Basic realm="Dreamtigers Admin"');
        header('HTTP/1.0 401 Unauthorized');
        exit('Authentication required.');
    }
}
