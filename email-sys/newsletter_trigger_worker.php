<?php
// email-sys/newsletter_trigger_worker.php
// 백그라운드 워커를 수동으로 트리거하는 스크립트 (항상 JSON 만 응답)
include "../include/inc_base.php";
include __DIR__ . "/newsletter_worker_launch.php";

// 이 엔드포인트는 JSON 전용 — PHP 오류 텍스트가 본문에 섞여 parsererror 가 나지 않도록 출력 차단
ini_set('display_errors', '0');

$GLOBALS['__nl_json_sent'] = false;

function newsletterJsonOut($data) {
    $GLOBALS['__nl_json_sent'] = true;
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data);
    exit;
}

// 치명적 오류(E_ERROR 등)가 나도 빈 응답/HTML 대신 JSON 으로 원인을 돌려준다.
register_shutdown_function(function () {
    if (!empty($GLOBALS['__nl_json_sent'])) {
        return;
    }
    $e = error_get_last();
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $msg = $e ? ('서버 오류: ' . $e['message']) : '알 수 없는 서버 오류가 발생했습니다.';
    echo json_encode(array('success' => false, 'message' => $msg));
});

try {
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
        newsletterJsonOut(array('success' => false, 'message' => '로그인이 필요합니다.'));
    }

    // 선택한 큐
    $selected_queue_ids = newsletterNormalizeQueueIds(isset($_POST['queue_ids']) ? $_POST['queue_ids'] : array());
    $selected_queue_sql = '';
    if (count($selected_queue_ids) > 0) {
        $selected_queue_sql = " AND q.seq_no IN (" . implode(',', $selected_queue_ids) . ")";

        // 선택한 큐 중 '중지(STOPPED)' 상태는 재개를 위해 WAITING 으로 되돌린다.
        $resume_ids = implode(',', $selected_queue_ids);
        mysql_query("UPDATE newsletter_queue SET status = 'WAITING' WHERE seq_no IN ($resume_ids) AND status = 'STOPPED'", $dbConn);
    }

    // 대기중인 큐가 있는지 확인
    $check_qry = "SELECT COUNT(*) as cnt
                  FROM newsletter_queue q
                  WHERE q.status IN ('WAITING', 'PROCESSING')
                    $selected_queue_sql
                    AND EXISTS (
                        SELECT 1 FROM newsletter_send_details d
                        WHERE d.queue_id = q.seq_no AND d.send_status = 'PENDING'
                    )";
    $check_rst = mysql_query($check_qry, $dbConn);
    $check_row = $check_rst ? mysql_fetch_assoc($check_rst) : null;

    if (!$check_row || $check_row['cnt'] == 0) {
        newsletterJsonOut(array('success' => false, 'message' => '처리할 대기중인 큐가 없습니다.'));
    }

    // 중복 실행은 newsletter_background_worker.php 의 lock 에서 차단한다.

    // PHP 실행파일 경로
    $is_win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    $php_path = $is_win ? (PHP_BINDIR . DIRECTORY_SEPARATOR . 'php.exe') : '/usr/bin/php';
    if (!@file_exists($php_path)) {
        $php_path = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
    }
    $worker_script = __DIR__ . '/newsletter_background_worker.php';
    $worker_args = '';
    if (count($selected_queue_ids) > 0) {
        $worker_args = ' --queue_ids=' . implode(',', $selected_queue_ids);
    }

    // exec/popen 이 disable_functions 로 막힌 호스트에서도 안전하게 동작하도록 가드한다.
    // (즉시 실행이 불가능해도 큐는 WAITING 으로 등록되어 서버 스케줄러(크론)가 곧 집어간다.)
    $disabled = array_map('trim', explode(',', strtolower((string) ini_get('disable_functions'))));
    $worker_launched = false;

    if ($is_win) {
        if (function_exists('popen') && !in_array('popen', $disabled, true)) {
            $cmd = 'cmd /C start "" /B "' . $php_path . '" "' . $worker_script . '"' . $worker_args . ' > nul 2>&1';
            newsletterTriggerLog('run: ' . $cmd);
            $h = @popen($cmd, 'r');
            if (is_resource($h)) { pclose($h); $worker_launched = true; }
        }
    } else {
        $cmd = escapeshellarg($php_path) . ' ' . escapeshellarg($worker_script) . $worker_args . ' > /dev/null 2>&1 &';
        if (function_exists('exec') && !in_array('exec', $disabled, true)) {
            newsletterTriggerLog('run(exec): ' . $cmd);
            @exec($cmd);
            $worker_launched = true;
        } elseif (function_exists('shell_exec') && !in_array('shell_exec', $disabled, true)) {
            newsletterTriggerLog('run(shell_exec): ' . $cmd);
            @shell_exec($cmd);
            $worker_launched = true;
        }
    }

    if ($worker_launched) {
        newsletterJsonOut(array('success' => true, 'message' => '백그라운드 워커가 시작되었습니다.'));
    } else {
        newsletterJsonOut(array('success' => true, 'message' => '발송 대기열에 등록되었습니다. 잠시 후 자동으로 발송이 시작됩니다.'));
    }

} catch (Throwable $e) {
    newsletterJsonOut(array('success' => false, 'message' => '서버 오류: ' . $e->getMessage()));
}
?>
