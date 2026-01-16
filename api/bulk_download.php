<?php
require_once __DIR__ . '/config.php';

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['files']) || !is_array($data['files']) || empty($data['files'])) {
        throw new Exception('No files specified');
    }

    $files = $data['files'];

    // Create temporary ZIP file
    $zipName = 'translated_files_' . time() . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipName;

    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Failed to create ZIP file');
    }

    $translatedRealPath = realpath(TRANSLATED_DIR);
    $addedFiles = 0;

    foreach ($files as $file) {
        // Sanitize file path
        $file = str_replace(['..', '\\', "\0"], '', $file);

        $filePath = TRANSLATED_DIR . '/' . $file;
        $realPath = realpath($filePath);

        // Validate path
        if ($realPath === false || strpos($realPath, $translatedRealPath) !== 0) {
            continue;
        }

        if (!file_exists($realPath) || !is_file($realPath)) {
            continue;
        }

        // Add file to ZIP with its relative path structure
        $zip->addFile($realPath, $file);
        $addedFiles++;
    }

    $zip->close();

    if ($addedFiles === 0) {
        @unlink($zipPath);
        throw new Exception('No valid files to download');
    }

    // Send ZIP file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');

    readfile($zipPath);

    // Clean up
    @unlink($zipPath);

    exit;

} catch (Exception $e) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
