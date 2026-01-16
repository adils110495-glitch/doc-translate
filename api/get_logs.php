<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

try {
    $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;
    $lines = max(1, min($lines, 1000)); // Limit between 1 and 1000 lines

    if (!file_exists(LOG_FILE)) {
        echo json_encode([
            'success' => true,
            'logs' => []
        ]);
        exit;
    }

    // Read last N lines of the log file
    $file = new SplFileObject(LOG_FILE, 'r');
    $file->seek(PHP_INT_MAX);
    $totalLines = $file->key() + 1;

    $startLine = max(0, $totalLines - $lines);

    $logs = [];
    $file->seek($startLine);

    while (!$file->eof()) {
        $line = trim($file->fgets());
        if (!empty($line)) {
            $logs[] = $line;
        }
    }

    // Reverse to show newest first
    $logs = array_reverse($logs);

    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'total_lines' => $totalLines
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error reading logs: ' . $e->getMessage()
    ]);
}
