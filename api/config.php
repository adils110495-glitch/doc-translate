<?php
// Base paths
define('BASE_DIR', dirname(__DIR__));
define('SOURCE_DIR', BASE_DIR . '/source');        // Original uploaded files
define('TRANSLATED_DIR', BASE_DIR . '/translated'); // Translated files
define('LOGS_DIR', BASE_DIR . '/logs');
define('LOG_FILE', LOGS_DIR . '/translation.log');

// File upload settings
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB in bytes

// Ensure directories exist
if (!is_dir(SOURCE_DIR)) {
    mkdir(SOURCE_DIR, 0755, true);
}

if (!is_dir(TRANSLATED_DIR)) {
    mkdir(TRANSLATED_DIR, 0755, true);
}

if (!is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
}

// Timezone for logging
date_default_timezone_set('UTC');
