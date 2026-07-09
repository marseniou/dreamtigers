<?php
$orientation = $_GET['o'] ?? 'vertical';
$filename = $_GET['f'] ?? '';

// Validate orientation
if (!in_array($orientation, ['vertical', 'horizontal'])) {
    http_response_code(404);
    exit('Invalid orientation');
}

// Prevent directory traversal
$filename = basename($filename);
if (empty($filename)) {
    http_response_code(404);
    exit('Missing filename');
}

$coverPath = __DIR__ . '/covers/' . $orientation . '/' . $filename;

if (!file_exists($coverPath)) {
    http_response_code(404);
    exit('Cover not found');
}

// Set cache headers (Facebook caches aggressively)
header('Cache-Control: public, max-age=31536000');
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($coverPath));

readfile($coverPath);
