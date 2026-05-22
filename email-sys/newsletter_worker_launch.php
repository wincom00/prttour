<?php
// Server-side launcher for newsletter background worker.

function newsletterWorkerLog($message) {
    $log_file = __DIR__ . '/newsletter_logs/newsletter_trigger_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);

    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    file_put_contents($log_file, '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n", FILE_APPEND | LOCK_EX);
}

function newsletterTriggerLog($message) {
    newsletterWorkerLog($message);
}

function newsletterNormalizeQueueIds($raw_ids) {
    $queue_ids = array();

    if (!is_array($raw_ids)) {
        $raw_ids = explode(',', $raw_ids);
    }

    foreach ($raw_ids as $id) {
        $id = trim($id);
        if ($id !== '' && ctype_digit($id) && intval($id) > 0) {
            $queue_ids[intval($id)] = intval($id);
        }
    }

    return array_values($queue_ids);
}

function newsletterWorkerPhpPath() {
    $php_path = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? PHP_BINDIR . DIRECTORY_SEPARATOR . 'php.exe' : '/usr/bin/php';
    if (!file_exists($php_path)) {
        $php_path = PHP_BINARY ? PHP_BINARY : 'php';
    }

    return $php_path;
}

function newsletterStartWorker($queue_ids = array()) {
    $queue_ids = newsletterNormalizeQueueIds($queue_ids);
    $php_path = newsletterWorkerPhpPath();
    $worker_script = __DIR__ . '/newsletter_background_worker.php';
    $worker_args = '';

    if (count($queue_ids) > 0) {
        $worker_args = ' --queue_ids=' . implode(',', $queue_ids);
    }

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = 'cmd /C start "" /B "' . $php_path . '" "' . $worker_script . '"' . $worker_args . ' > nul 2>&1';
        newsletterWorkerLog('run: ' . $cmd);
        pclose(popen($cmd, "r"));
    } else {
        $cmd = escapeshellarg($php_path) . " " . escapeshellarg($worker_script) . $worker_args . " > /dev/null 2>&1 &";
        newsletterWorkerLog('run: ' . $cmd);
        exec($cmd);
    }

    return true;
}
?>
