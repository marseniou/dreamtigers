<?php
// add_remaining.php - Add the 19 books that init_db.php couldn't match
if (php_sapi_name() !== 'cli') {
    exit('CLI only');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$pdo = getDb();

// Manual cover mapping: pdf_base => cover_filename
$manualMatches = [
    '20000'               => '20_20_web.jpg',
    'astragaloi'          => 'astagaloi_cover.jpg',
    'callegraficas'       => 'calle.jpg',
    'erw'                 => 'ero_front.jpg',
    'gia_mia_xoufta_dinaria' => 'xoufta_front.jpg',
    'ipeirokeanio'        => 'IMG_9949_0.JPG',
    'meta_tis_9'          => 'meta tis 9_cover.jpg',
    'o_kleftis_ton_anaptiron' => 'kleftis_front_web.jpg',
    'o_psychonaftis'      => 'psychonaftis_web.jpg',
    'oi_fones_apo_dipla'  => 'oi_fones.jpg',
    'oi_muthosullektes'   => 'oi_muthosilekets.jpg',
    'onemansland'         => "ANTAMIS_EXOFILLO_ONE MAN'S LAND_FINAL2019-01_0.jpg",
    'pouma'               => 'πουμα εξω.jpg',
    'prigipodouleies'     => 'exofillo fb.jpg',
    'se_lathos_xeria'     => 'se-lathos-xeria_cover.jpg',
    'ta_ksila'            => 'piso_gianni_ta_jila_web.jpg',
    'tierradelego'        => 'Cover_jpg.jpg',
    'συνεχεια'            => '406886086_355311063793784_8240610649760424924_n (2).jpg',
];

$titleMap = [
    '20000' => "20,0000 λεύγες κάτω απ' ότι χάλασα",
    'astragaloi' => 'Γύρω από τους αστραγάλους της',
    'callegraficas' => 'callegraficas',
    'erw' => 'Ερω',
    'gia_mia_xoufta_dinaria' => 'Για μια χούφτα δηνάρια',
    'ipeirokeanio' => 'Το Ηπειροκεάνιο',
    'meta_tis_9' => 'μετά τις 9',
    'o_kleftis_ton_anaptiron' => 'Ο κλέφτης των αναπτήρων',
    'o_psychonaftis' => 'ο ψυχοναύτης',
    'oi_fones_apo_dipla' => 'οι φωνές από δίπλα',
    'oi_muthosullektes' => 'οι μυθοσυλλέκτες',
    'onemansland' => "one man's land",
    'pouma' => 'πούμα',
    'prigipodouleies' => 'Πριγκιποδουλειές',
    'se_lathos_xeria' => 'Σε λάθος χέρια',
    'ta_ksila' => 'πίσω γιάννη τα ξύλα',
    'tierradelego' => 'tierra del ego',
    'συνεχεια' => 'συνέχεια',
];

$count = 0;
foreach ($manualMatches as $pdfBase => $coverFile) {
    $pdfFilename = $pdfBase . '.pdf';
    $pdfPath = FREE_EBOOKS_DIR . '/' . $pdfFilename;
    $coverSrcPath = COVERS_ORIGINAL . '/' . $coverFile;

    if (!file_exists($pdfPath)) {
        echo "PDF not found: $pdfFilename\n";
        continue;
    }
    if (!file_exists($coverSrcPath)) {
        echo "Cover not found: $coverFile\n";
        continue;
    }

    $title = $titleMap[$pdfBase] ?? ucwords(str_replace(['_', '-'], ' ', $pdfBase));

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

    $slug = generateSlug($title);
    $slug = ensureUniqueSlug($pdo, $slug);

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
