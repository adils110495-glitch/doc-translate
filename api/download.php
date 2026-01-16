<?php
require_once __DIR__ . '/config.php';

try {
    if (!isset($_GET['file'])) {
        throw new Exception('File parameter is required');
    }

    $requestedFile = $_GET['file'];

    // Sanitize and validate file path
    $requestedFile = str_replace(['..', '\\', "\0"], '', $requestedFile);

    // Construct full path
    $filePath = TRANSLATED_DIR . '/' . $requestedFile;

    // Ensure file exists and is within translated directory
    $realPath = realpath($filePath);
    $translatedRealPath = realpath(TRANSLATED_DIR);

    if ($realPath === false || strpos($realPath, $translatedRealPath) !== 0) {
        throw new Exception('Invalid file path');
    }

    if (!file_exists($realPath) || !is_file($realPath)) {
        throw new Exception('File not found');
    }

    // Get file info
    $fileName = basename($realPath);
    $fileSize = filesize($realPath);
    $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    // Set headers for download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // Output file
    readfile($realPath);
    exit;

} catch (Exception $e) {
    header('HTTP/1.1 404 Not Found');
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit;
}
