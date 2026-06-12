<?php
// email-sys/newsletter_run_worker.php
// 웹에서 호출되어 "서버 백그라운드"로 뉴스레터 워커를 끝까지 실행한다.
// exec/popen 이 막힌 호스팅에서도 동작하도록, 응답을 먼저 끊고(ignore_user_abort +
// fastcgi_finish_request) 서버 측에서 발송 루프를 계속 진행한다. (작업 스케줄러/크론 불필요)

// 토큰/헬퍼 (inc_base 는 아래 background_worker 가 include 하므로 여기서는 제외)
include __DIR__ . "/newsletter_worker_launch.php";

ignore_user_abort(true);
@set_time_limit(0);
@ini_set('max_execution_time', '0');
@ini_set('memory_limit', '256M');

// 인증: 관리자 로그인 쿠키 또는 내부 워커 토큰
$token = isset($_REQUEST['token']) ? (string) $_REQUEST['token'] : '';
$has_cookie = isset($_COOKIE['MEMLOGIN_ADMIN_PURUN']) && $_COOKIE['MEMLOGIN_ADMIN_PURUN'] != '';
$token_ok = ($token !== '' && hash_equals(newsletterWorkerToken(), $token));

if (!$has_cookie && !$token_ok) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'message' => 'forbidden'));
    exit;
}

// 시스템 전체가 '중지' 상태면 실행하지 않는다.
if (newsletterIsSystemStopped()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'message' => 'system stopped'));
    exit;
}

// 응답을 즉시 끊고 서버 백그라운드로 전환
$payload = json_encode(array('success' => true, 'message' => 'worker started'));
if (function_exists('fastcgi_finish_request')) {
    header('Content-Type: application/json; charset=utf-8');
    echo $payload;
    @fastcgi_finish_request();
} else {
    // php-fpm 이 아니면: 클라이언트 연결을 끊고 계속 실행
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: close');
    header('Content-Length: ' . strlen($payload));
    echo $payload;
    @ob_flush();
    @flush();
    if (function_exists('session_write_close')) { @session_write_close(); }
}

// 워커 본체 실행. queue_ids 는 $_GET 으로 전달되어 background_worker 가 인식한다.
// (background_worker 가 inc_base.php / 파일 락 / 발송 루프를 모두 처리한다.)
include __DIR__ . "/newsletter_background_worker.php";
