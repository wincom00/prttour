<?php
// Run this file from the server scheduler/cron. It does not require a browser session.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "CLI only";
    exit;
}

chdir(__DIR__);
include __DIR__ . '/newsletter_background_worker.php';
?>
