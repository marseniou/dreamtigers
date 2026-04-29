<?php
// init_db.php - One-time script: scan folders, populate DB, resize covers
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$pdo = getDb();

// Scan PDFs
$pdfs = glob(FREE_EBOOKS_DIR . '/*.pdf');
if (empty($pdfs)) {
    exit("No PDFs found in " . FREE_EBOOKS_DIR . "\n");
}

// Scan original covers
$coverFiles = [];
if (is_dir(COVERS_ORIGINAL)) {
    foreach (scandir(COVERS_ORIGINAL) as $f) {
        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $f)) {
            $coverFiles[] = $f;
        }
    }
}

echo "Found " . count($pdfs) . " PDFs and " . count($coverFiles) . " covers\n";

$count = 0;
foreach ($pdfs as $pdfPath) {
    $pdfFilename = basename($pdfPath);
    $pdfBase = pathinfo($pdfFilename, PATHINFO_FILENAME);

    // Generate title from filename (humanized)
    $title = ucwords(str_replace(['_', '-'], ' ', $pdfBase));

    // Find matching cover
    $coverFile = matchCoverToPdf($pdfBase, $coverFiles);
    if (!$coverFile) {
        echo "No cover found for: $pdfFilename\n";
        continue;
    }

    $coverSrcPath = COVERS_ORIGINAL . '/' . $coverFile;

    // Determine orientation and resize cover
    try {
        $info = getimagesize($coverSrcPath);
        $isVertical = $info[1] > $info[0];
        $destDir = $isVertical ? COVERS_VERTICAL : COVERS_HORIZONTAL;
        $coverDestFilename = resizeCover($coverSrcPath, $destDir);
        $orientation = $isVertical ? 'vertical' : 'horizontal';
    } catch (Exception $e) {
        echo "Error resizing cover for $pdfFilename: " . $e->getMessage() . "\n";
        continue;
    }

    // Generate unique slug
    $slug = generateSlug($title);
    $slug = ensureUniqueSlug($pdo, $slug);

    // Insert into DB
    $stmt = $pdo->prepare('
        INSERT INTO books (title, pdf_filename, cover_filename, cover_orientation, slug)
        VALUES (:title, :pdf, :cover, :orientation, :slug)
    ');
    $stmt->execute([
        ':title' => $title,
        ':pdf' => $pdfFilename,
        ':cover' => $coverDestFilename,
        ':orientation' => $orientation,
        ':slug' => $slug,
    ]);
    $count++;
    echo "Added: $title ($slug)\n";
}

echo "\nDone! Added $count books.\n";
