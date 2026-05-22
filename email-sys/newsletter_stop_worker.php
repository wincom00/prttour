<?php
// email-sys/newsletter_stop_worker.php
// 선택한 큐를 '중지(STOPPED)' 상태로 변경한다.
// 실제 발송 중단은 newsletter_background_worker.php 가 매 건마다 현재 상태(STOPPED)를 확인하여 처리한다.
include "../include/inc_base.php";
include __DIR__ . "/newsletter_worker_launch.php";

header('Content-Type: application/json; charset=utf-8');

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

$queue_ids = newsletterNormalizeQueueIds(isset($_POST['queue_ids']) ? $_POST['queue_ids'] : array());

if (count($queue_ids) == 0) {
    echo json_encode(array('success' => false, 'message' => '중지할 큐를 선택해 주세요.'));
    exit;
}

$ids = implode(',', $queue_ids);
$ok = mysql_query("UPDATE newsletter_queue SET status = 'STOPPED' WHERE seq_no IN ($ids) AND status IN ('WAITING', 'PROCESSING')", $dbConn);

if (!$ok) {
    echo json_encode(array('success' => false, 'message' => mysql_error()));
    exit;
}

$affected = mysql_affected_rows($dbConn);
newsletterTriggerLog('stop requested: ' . $ids . ' (affected=' . $affected . ')');

if ($affected == 0) {
    echo json_encode(array(
        'success'  => true,
        'affected' => 0,
        'message'  => '중지할 수 있는(진행중/대기중) 큐가 없습니다.'
    ));
    exit;
}

echo json_encode(array(
    'success'  => true,
    'affected' => $affected,
    'message'  => $affected . '개 큐에 중지 요청을 보냈습니다. 발송 중인 건은 곧 멈춥니다.'
));
?>
