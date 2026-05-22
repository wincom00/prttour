<?php
// email-sys/newsletter_cron_api.php
include "../include/inc_base.php";

ini_set('display_errors', '0');

function jsonOut($success, $message, $data=array()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(array('success' => $success, 'message' => $message), $data));
    exit;
}

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    jsonOut(false, '로그인이 필요합니다.');
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

$stop_file = __DIR__ . '/newsletter_logs/newsletter_cron.stop';
$lock_file = __DIR__ . '/newsletter_logs/newsletter_worker.lock';
$is_win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

if ($action === 'status') {
    $cron_status = file_exists($stop_file) ? 'STOPPED' : 'ACTIVE';
    $worker_status = 'WAITING';
    $pid = '';
    
    if (file_exists($lock_file)) {
        $lock_content = @file_get_contents($lock_file);
        $parts = explode(' ', $lock_content);
        if (count($parts) > 0 && is_numeric($parts[0])) {
            $check_pid = intval($parts[0]);
            
            if ($is_win) {
                $tasklist = @shell_exec('tasklist /FI "PID eq ' . $check_pid . '" 2>NUL');
                if (stripos($tasklist, (string)$check_pid) !== false) {
                    $worker_status = 'RUNNING';
                    $pid = $check_pid;
                }
            } else {
                // Linux ps check
                $ps = @shell_exec('ps -p ' . $check_pid . ' -o pid= 2>/dev/null');
                if (trim($ps) == $check_pid) {
                    $worker_status = 'RUNNING';
                    $pid = $check_pid;
                }
            }
        }
    }
    
    jsonOut(true, 'OK', array('cron_status' => $cron_status, 'worker_status' => $worker_status, 'pid' => $pid));
}
elseif ($action === 'stop') {
    $script = __DIR__ . '/newsletter_cron_manager.sh';
    if (!$is_win && file_exists($script)) {
        shell_exec('bash ' . escapeshellarg($script) . ' stop');
    } else {
        // 직접 처리
        touch($stop_file);
        if (file_exists($lock_file)) {
            $parts = explode(' ', @file_get_contents($lock_file));
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $pid = intval($parts[0]);
                if ($is_win) {
                    @shell_exec('taskkill /F /PID ' . $pid . ' > NUL 2>&1');
                } else {
                    @shell_exec('kill -9 ' . $pid . ' > /dev/null 2>&1');
                }
            }
            @unlink($lock_file);
        }
    }
    jsonOut(true, '시스템(크론) 및 현재 워커가 중지되었습니다.');
}
elseif ($action === 'resume') {
    $script = __DIR__ . '/newsletter_cron_manager.sh';
    if (!$is_win && file_exists($script)) {
        shell_exec('bash ' . escapeshellarg($script) . ' resume');
    } else {
        @unlink($stop_file);
    }
    jsonOut(true, '시스템(크론)이 재개되었습니다. 다음 주기부터 정상 작동합니다.');
}
else {
    jsonOut(false, '알 수 없는 명령입니다.');
}
?>