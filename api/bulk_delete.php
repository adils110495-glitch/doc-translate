<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['files']) || !is_array($data['files']) || empty($data['files'])) {
        throw new Exception('No files specified');
    }

    $files = $data['files'];
    $translatedRealPath = realpath(TRANSLATED_DIR);
    $deletedCount = 0;
    $failedCount = 0;

    foreach ($files as $file) {
        // Sanitize file path
        $file = str_replace(['..', '\\', "\0"], '', $file);

        $filePath = TRANSLATED_DIR . '/' . $file;
        $realPath = realpath($filePath);

        // Validate path
        if ($realPath === false || strpos($realPath, $translatedRealPath) !== 0) {
            $failedCount++;
            continue;
        }

        if (!file_exists($realPath) || !is_file($realPath)) {
            $failedCount++;
            continue;
        }

        // Delete file
        if (unlink($realPath)) {
            $deletedCount++;

            // Log deletion
            $fileName = basename($realPath);
            $pathParts = explode('/', $file);
            $project = $pathParts[0] ?? 'Unknown';
            $topic = $pathParts[1] ?? 'Unknown';

            file_put_contents(
                LOG_FILE,
                sprintf(
                    "[%s] DELETED | %s | %s | %s\n",
                    date('Y-m-d H:i:s'),
                    $fileName,
                    $project,
                    $topic
                ),
                FILE_APPEND | LOCK_EX
            );
        } else {
            $failedCount++;
        }
    }

    // Clean up empty directories
    cleanupEmptyDirectories(TRANSLATED_DIR);

    $message = sprintf(
        'Deleted %d file(s) successfully',
        $deletedCount
    );

    if ($failedCount > 0) {
        $message .= sprintf(' (%d failed)', $failedCount);
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'deleted' => $deletedCount,
        'failed' => $failedCount
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Recursively remove empty directories
 */
function cleanupEmptyDirectories($path) {
    if (!is_dir($path)) {
        return;
    }

    $items = array_diff(scandir($path), ['.', '..']);

    foreach ($items as $item) {
        $itemPath = $path . '/' . $item;

        if (is_dir($itemPath)) {
            cleanupEmptyDirectories($itemPath);

            // Check if directory is now empty
            $subItems = array_diff(scandir($itemPath), ['.', '..']);
            if (empty($subItems)) {
                @rmdir($itemPath);
            }
        }
    }
}
