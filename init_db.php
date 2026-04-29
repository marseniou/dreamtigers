<?php
// init_db.php - One-time script: scan folders, populate DB, resize covers
if (php_sapi_name() !== 'cli') {
    exit('CLI only');
}

require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/helpers.php';

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

    // Title mapping from Drupal database (PDF filename => Greek title)
    $titleMap = [
        '1607' => '1607',
        '20000' => "20,0000 λεύγες κάτω απ' ότι χάλασα",
        'a_edo' => 'α εδώ',
        'antigrafi' => 'η αντιγραφή',
        'apo_kummata' => 'από κύματα',
        'apoktitheda' => 'Τα αποκτηθέντα κατά τύχην όλως',
        'askopes_metakiniseis' => 'άσκοπες μετακινήσεις',
        'astragaloi' => 'Γύρω από τους αστραγάλους της',
        'callegraficas' => 'callegraficas',
        'egine' => 'έγινε',
        'eikosi_eikosi' => 'είκοσι είκοσι',
        'ena_enteka' => 'ένα έντεκα',
        'erw' => 'Ερω',
        'esodon_eksodon' => 'εσόδων - εξόδων',
        'geometropolis' => 'Geometropolis',
        'gia_aurio' => 'Για Αύριο',
        'gia_mia_xoufta_dinaria' => 'Για μια χούφτα δηνάρια',
        'goustav' => 'γκούσταβ',
        'hpomphia' => 'Η μπόμπια',
        'i_mixani_tou_kronou' => 'η μηχανή του κρόνου',
        'i_oraia_nustagmeni' => 'Η Ωραία Νυσταγμένη',
        'iatreio_mikron_zoon' => 'Ιατρείο Μικρών Ζωών',
        'if' => 'ιφ',
        'iliotherapeutis' => 'Ο Ηλιοθεραπευτής',
        'indos' => 'ο ινδός ωτορινολαρυγγολόγος',
        'ipeirokeanio' => 'Το Ηπειροκεάνιο',
        'koinopoiimata' => 'κοινοποιήματα',
        'madrid' => 'Οι τελευταίες εκλογές στην Πομπηία',
        'me_ta_podia' => 'με τα πόδια',
        'meta_tis_9' => 'μετά τις 9',
        'mi_tis_to_peis' => 'μην της το πεις',
        'mpalkanmobil' => 'μπαλκανμομπίλ',
        'o_iroas' => 'Ο ήρωας',
        'o_kleftis_ton_anaptiron' => 'Ο κλέφτης των αναπτήρων',
        'o_psychonaftis' => 'ο ψυχοναύτης',
        'oi_fones_apo_dipla' => 'οι φωνές από δίπλα',
        'oi_muthosullektes' => 'οι μυθοσυλλέκτες',
        'onemansland' => "one man's land",
        'opoios_fovatai' => 'Όποιος φοβάται',
        'polemos' => 'Ο πόλεμος των 6 ημερών',
        'pouma' => 'πούμα',
        'prigipodouleies' => 'Πριγκιποδουλειές',
        'prosorinos_titlos' => '(προσωρινός τίτλος)',
        'psemata' => 'Ψέματα λένε',
        'rimada' => 'η ρημάδα',
        'scripta_volant' => 'Scripta Volant',
        'se_lathos_xeria' => 'Σε λάθος χέρια',
        'starboard' => 'Starboard Home',
        'sxedon_authentikoi' => 'σχεδόν αυθεντικοί',
        'ta_daneia' => 'Τα Δάνεια',
        'ta_ksila' => 'πίσω γιάννη τα ξύλα',
        'tierradelego' => 'tierra del ego',
        'to_aeikinito' => 'το αεικίνητο',
        'to_paidi_vrikolakas' => 'το παιδί βρικόλακας',
        'xasaposkilo' => 'Σαν το Χασαπόσκυλο',
        'xiliometra_kaname_pali' => 'Χιλιόμετρα κάναμε πάλι',
        'συνεχεια' => 'συνέχεια',
    ];

    // Use Greek title from map, fallback to humanized filename
    $title = $titleMap[$pdfBase] ?? ucwords(str_replace(['_', '-'], ' ', $pdfBase));

    // Find matching cover
    $coverFile = matchCoverToPdf($pdfBase, $coverFiles);
    if (!$coverFile) {
        echo "No cover found for: $pdfFilename\n";
        continue;
    }

    // Remove matched cover to prevent duplicate assignment
    $coverFiles = array_diff($coverFiles, [$coverFile]);

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
