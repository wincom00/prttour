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

    // 크론 없이 서버에서 직접 분리된 백그라운드 프로세스로 워커를 실행한다.
    // (Windows/Linux 모두 newsletterStartWorker 가 처리한다.)
    $worker_launched = newsletterStartWorker($selected_queue_ids);

    if ($worker_launched) {
        newsletterJsonOut(array('success' => true, 'message' => '백그라운드 워커가 시작되었습니다.'));
    } elseif (newsletterIsSystemStopped()) {
        newsletterJsonOut(array('success' => false, 'message' => '시스템이 중지 상태입니다. 먼저 "시스템 재개"를 눌러 주세요.'));
    } else {
        newsletterJsonOut(array('success' => false, 'message' => '백그라운드 워커 실행에 실패했습니다(셸 분리 및 서버 루프백 호출 모두 실패). 트리거 로그를 확인해 주세요.'));
    }

} catch (Throwable $e) {
    newsletterJsonOut(array('success' => false, 'message' => '서버 오류: ' . $e->getMessage()));
}
?>
