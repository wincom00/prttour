<?php

if (!defined('DATA_CHANGE_LOG_DIR')) {
    define('DATA_CHANGE_LOG_DIR', dirname(__DIR__) . '/logs/data_change');
}

if (!defined('DATA_CHANGE_LOG_RETENTION_MONTHS')) {
    define('DATA_CHANGE_LOG_RETENTION_MONTHS', 6);
}

if (!function_exists('data_change_log_action')) {
    function data_change_log_action($sql)
    {
        $sql = ltrim((string)$sql);
        $sql = preg_replace('/^\s*\/\*.*?\*\/\s*/s', '', $sql);
        $sql = preg_replace('/^\s*--[^\r\n]*(\r\n|\r|\n)\s*/', '', $sql);

        if (preg_match('/^([a-z]+)/i', $sql, $m)) {
            $action = strtoupper($m[1]);
            $allowed = array('INSERT', 'UPDATE', 'DELETE', 'REPLACE', 'ALTER', 'DROP', 'CREATE', 'TRUNCATE', 'RENAME');
            return in_array($action, $allowed, true) ? $action : '';
        }

        return '';
    }
}

if (!function_exists('data_change_log_table')) {
    function data_change_log_table($sql, $action)
    {
        $sql = trim((string)$sql);
        $patterns = array(
            'INSERT' => '/^\s*INSERT\s+(?:IGNORE\s+)?INTO\s+`?([A-Za-z0-9_]+)`?/i',
            'REPLACE' => '/^\s*REPLACE\s+(?:IGNORE\s+)?INTO\s+`?([A-Za-z0-9_]+)`?/i',
            'UPDATE' => '/^\s*UPDATE\s+`?([A-Za-z0-9_]+)`?/i',
            'DELETE' => '/^\s*DELETE\s+FROM\s+`?([A-Za-z0-9_]+)`?/i',
            'ALTER' => '/^\s*ALTER\s+TABLE\s+`?([A-Za-z0-9_]+)`?/i',
            'DROP' => '/^\s*DROP\s+(?:TEMPORARY\s+)?TABLE\s+(?:IF\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?/i',
            'CREATE' => '/^\s*CREATE\s+(?:TEMPORARY\s+)?TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?/i',
            'TRUNCATE' => '/^\s*TRUNCATE\s+(?:TABLE\s+)?`?([A-Za-z0-9_]+)`?/i',
            'RENAME' => '/^\s*RENAME\s+TABLE\s+`?([A-Za-z0-9_]+)`?/i',
        );

        if (isset($patterns[$action]) && preg_match($patterns[$action], $sql, $m)) {
            return $m[1];
        }

        return '';
    }
}

if (!function_exists('data_change_log_normalize_sql')) {
    function data_change_log_normalize_sql($sql)
    {
        $sql = preg_replace('/\s+/', ' ', trim((string)$sql));
        $sql = preg_replace("/((?:pass|passwd|password|card_num|cvv|cvv2)\s*=\s*)'[^']*'/i", "$1'[MASKED]'", $sql);
        $sql = preg_replace('/((?:pass|passwd|password|card_num|cvv|cvv2)\s*=\s*)"[^"]*"/i', '$1"[MASKED]"', $sql);
        if (preg_match('/\b(pass|passwd|password|card_num|cvv|cvv2)\b/i', $sql)) {
            $sql = preg_replace("/'[^']*'/", "'[MASKED]'", $sql);
            $sql = preg_replace('/"[^"]*"/', '"[MASKED]"', $sql);
            $sql = preg_replace('/\b\d{6,}\b/', '[MASKED]', $sql);
        }

        if (strlen($sql) > 4000) {
            $sql = substr($sql, 0, 4000) . ' ...[truncated]';
        }

        return $sql;
    }
}

if (!function_exists('data_change_log_current_user')) {
    function data_change_log_current_user()
    {
        $info = isset($GLOBALS['user_dbinfo']) && is_array($GLOBALS['user_dbinfo']) ? $GLOBALS['user_dbinfo'] : array();

        $userid = isset($info['userid']) ? (string)$info['userid'] : '';
        if ($userid === '' && isset($_POST['userid'])) {
            $userid = (string)$_POST['userid'];
        }
        if ($userid === '' && isset($_GET['userid'])) {
            $userid = (string)$_GET['userid'];
        }

        return array(
            'userid' => $userid,
            'kor_name' => isset($info['kor_name']) ? (string)$info['kor_name'] : '',
            'division' => isset($info['division']) ? (string)$info['division'] : '',
        );
    }
}

if (!function_exists('data_change_log_purge_old')) {
    function data_change_log_purge_old()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $baseDir = DATA_CHANGE_LOG_DIR;
        if (!is_dir($baseDir)) {
            return;
        }

        $cutoffDay = date('Y-m-d', strtotime('-' . DATA_CHANGE_LOG_RETENTION_MONTHS . ' months'));
        $monthDirs = glob($baseDir . '/*', GLOB_ONLYDIR);
        if (!is_array($monthDirs)) {
            return;
        }

        foreach ($monthDirs as $monthDir) {
            $files = glob($monthDir . '/*.jsonl');
            if (!is_array($files)) {
                continue;
            }
            foreach ($files as $file) {
                $day = basename($file, '.jsonl');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) && $day < $cutoffDay) {
                    @unlink($file);
                }
            }
            $left = glob($monthDir . '/*');
            if (is_array($left) && count($left) === 0) {
                @rmdir($monthDir);
            }
        }
    }
}

if (!function_exists('data_change_log_write')) {
    function data_change_log_write($sql, $success, $error = '', $affectedRows = null, $insertId = null)
    {
        $action = data_change_log_action($sql);
        if ($action === '') {
            return;
        }

        data_change_log_purge_old();

        $dir = DATA_CHANGE_LOG_DIR . '/' . date('Y-m');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            error_log('[data_change_log] log directory is not writable: ' . $dir);
            return;
        }

        $user = data_change_log_current_user();
        $link = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';

        $entry = array(
            'ts' => date('c'),
            'user_id' => $user['userid'],
            'user_name' => $user['kor_name'],
            'division' => $user['division'],
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '',
            'method' => isset($_SERVER['REQUEST_METHOD']) ? (string)$_SERVER['REQUEST_METHOD'] : '',
            'script' => $link,
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '',
            'action' => $action,
            'table' => data_change_log_table($sql, $action),
            'success' => (bool)$success,
            'error' => (string)$error,
            'affected_rows' => $affectedRows,
            'insert_id' => $insertId,
            'sql_hash' => sha1((string)$sql),
            'sql' => data_change_log_normalize_sql($sql),
        );

        $jsonFlags = defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0;
        @file_put_contents($dir . '/' . date('Y-m-d') . '.jsonl', json_encode($entry, $jsonFlags) . "\n", FILE_APPEND | LOCK_EX);
    }
}

