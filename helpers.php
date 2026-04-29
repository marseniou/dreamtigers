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
    $maxAttempts = 1000;
    $stmt = $db->prepare('SELECT COUNT(*) FROM books WHERE slug = :slug');
    while ($counter <= $maxAttempts) {
        $stmt->execute([':slug' => $slug]);
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $original . '-' . $counter;
        $counter++;
    }
    return $original . '-' . time();
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

    // Preserve PNG transparency
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($destImage, false);
        imagesavealpha($destImage, true);
        $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
        imagefilledrectangle($destImage, 0, 0, $targetW, $targetH, $transparent);
    }

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

function matchCoverToPdf(string $pdfBaseName, array $coverFiles): ?string {
    $manualMap = [
        'oi_muthosullektes' => 'oi_muthosilekets.jpg',
        'ta_ksila' => 'piso_gianni_ta_jila_web.jpg',
        'pouma' => 'πουμα εξω.jpg',
        'erw' => 'ero_front.jpg',
        'eikosi_eikosi' => '20_20_web.jpg',
    ];
    if (isset($manualMap[$pdfBaseName])) {
        foreach ($coverFiles as $cover) {
            if ($cover === $manualMap[$pdfBaseName]) {
                return $cover;
            }
        }
    }

    $pdfNorm = normalizeName($pdfBaseName);
    $bestMatch = null;
    $bestScore = 0;
    foreach ($coverFiles as $cover) {
        $coverName = pathinfo($cover, PATHINFO_FILENAME);
        $coverNorm = normalizeName($coverName);
        $score = matchScore($pdfNorm, $coverNorm);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $cover;
        }
    }
    return $bestScore > 0 ? $bestMatch : null;
}

function normalizeName(string $name): string {
    $name = strtolower($name);
    $name = preg_replace('/[\s_\-]+/', ' ', $name);
    $name = str_replace("'", '', $name);
    $name = preg_replace('/\b(web|front|back|cover|final)\b/i', '', $name);
    $name = preg_replace('/[\-\s](f|fb)\b/i', '', $name);
    $name = preg_replace('/\s+[0-9]+$/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

function matchScore(string $pdf, string $cover): int {
    if ($pdf === $cover) {
        return 1000;
    }
    if (str_contains($cover, $pdf)) {
        return 500;
    }
    if (str_contains($pdf, $cover) && strlen($cover) > 3) {
        return 400;
    }
    $pdfNoSpace = str_replace(' ', '', $pdf);
    $coverNoSpace = str_replace(' ', '', $cover);
    if ($pdfNoSpace === $coverNoSpace) {
        return 450;
    }
    if (str_contains($coverNoSpace, $pdfNoSpace)) {
        return 350;
    }
    if (str_contains($pdfNoSpace, $coverNoSpace) && strlen($coverNoSpace) > 3) {
        return 300;
    }
    if (strlen($pdf) <= 25 && strlen($cover) <= 40) {
        $dist = levenshtein($pdf, $cover);
        $maxLen = max(strlen($pdf), strlen($cover));
        if ($dist > 0 && $dist <= 3 && $dist / $maxLen < 0.25) {
            return 200;
        }
    }
    $pdfWords = array_filter(explode(' ', $pdf));
    $pdfWords = array_filter($pdfWords, fn($w) => strlen($w) > 2 && !in_array($w, ['the', 'and', 'gia', 'tou', 'tis', 'ton', 'tas']));
    if (empty($pdfWords)) {
        return 0;
    }
    $matched = 0;
    foreach ($pdfWords as $word) {
        if (str_contains($cover, $word)) {
            $matched++;
        }
    }
    $ratio = $matched / count($pdfWords);
    if ($ratio >= 0.5) {
        return (int)($ratio * 300);
    }
    return 0;
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
