<?php
// email-sys/newsletter_trigger_worker.php
// 백그라운드 워커를 수동으로 트리거하는 스크립트

include "../include/inc_base.php";

header('Content-Type: application/json');

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

// 대기중인 큐가 있는지 확인
$check_qry = "SELECT COUNT(*) as cnt FROM newsletter_queue WHERE status = 'WAITING'";
$check_rst = mysql_query($check_qry, $dbConn);
$check_row = mysql_fetch_assoc($check_rst);

if($check_row['cnt'] == 0) {
    echo json_encode(['success' => false, 'message' => '처리할 대기중인 큐가 없습니다.']);
    exit;
}

// 이미 진행중인 작업이 있는지 확인
$processing_qry = "SELECT COUNT(*) as cnt FROM newsletter_queue WHERE status = 'PROCESSING'";
$processing_rst = mysql_query($processing_qry, $dbConn);
$processing_row = mysql_fetch_assoc($processing_rst);

if($processing_row['cnt'] > 0) {
    echo json_encode(['success' => false, 'message' => '이미 진행중인 작업이 있습니다.']);
    exit;
}

// PHP 경로 설정 (서버 환경에 맞게 조정)
$php_path = '/usr/bin/php'; // 또는 'php' 또는 '/usr/local/bin/php'
$worker_script = __DIR__ . '/newsletter_background_worker.php';

// 운영체제별 백그라운드 실행
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows 환경
    $cmd = "start /B $php_path \"$worker_script\" > nul 2>&1";
    pclose(popen($cmd, "r"));
} else {
    // Linux/Unix 환경
    $cmd = "$php_path \"$worker_script\" > /dev/null 2>&1 &";
    exec($cmd);
}

echo json_encode([
    'success' => true, 
    'message' => '백그라운드 워커가 시작되었습니다.'
]);
?>