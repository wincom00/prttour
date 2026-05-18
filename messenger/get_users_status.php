<?php
// messenger/get_users_status.php
// 여러 사용자 상태 일괄 조회 API

// 세션 확인 및 필요한 포함 파일
session_start();
include "../include/inc_base.php";

// 응답 헤더 설정
header('Content-Type: application/json');

// 로그인 확인
if (!isset($_COOKIE['MEMLOGIN_ADMIN_PURUN']) || empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo json_encode(array('status' => 'error', 'message' => '로그인이 필요합니다.'));
    exit;
}

try {
    // POST 데이터 확인
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 사용자 ID 목록 확인
    if (!isset($data['user_ids']) || !is_array($data['user_ids']) || empty($data['user_ids'])) {
        echo json_encode(array('status' => 'error', 'message' => '사용자 ID 목록이 필요합니다.'));
        exit;
    }
    
    $user_ids = $data['user_ids'];
    
    // 최대 50명까지만 처리 (성능 및 보안 제한)
    if (count($user_ids) > 50) {
        $user_ids = array_slice($user_ids, 0, 50);
    }
    
    // SQL 인젝션 방지를 위한 사용자 ID 이스케이프 처리
    foreach ($user_ids as $key => $id) {
        $user_ids[$key] = mysql_real_escape_string($id, $dbConn);
    }
    
    // 사용자 ID를 쉼표로 구분된 문자열로 변환 (SQL IN 연산자용)
    $user_ids_str = "'" . implode("','", $user_ids) . "'";
    
    // 사용자 상태 일괄 조회
    $sql = "SELECT user_id, status, last_activity 
            FROM messenger_user_status 
            WHERE user_id IN (" . $user_ids_str . ")";
    
    $result = mysql_query($sql, $dbConn);
    if (!$result) {
        throw new Exception("상태 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
    }
    
    // 사용자별 상태 정보 저장
    $user_statuses = array();
    
    while ($row = mysql_fetch_assoc($result)) {
        $status = $row['status'];
        $last_activity = $row['last_activity'];
        
        // 마지막 활동 시간이 15분 이상 전이면 오프라인으로 처리
        if ($status !== 'offline') {
            $last_activity_time = strtotime($last_activity);
            $current_time = time();
            $inactive_period = 15 * 60; // 15분
            
            if (($current_time - $last_activity_time) > $inactive_period) {
                $status = 'offline';
            }
        }
        
        $user_statuses[$row['user_id']] = array(
            'status' => $status,
            'last_activity' => $last_activity
        );
    }
    
    // 조회된 상태가 없는 사용자는 오프라인으로 처리
    foreach ($user_ids as $user_id) {
        if (!isset($user_statuses[$user_id])) {
            $user_statuses[$user_id] = array(
                'status' => 'offline',
                'last_activity' => null
            );
        }
    }
    
    // 응답 반환
    echo json_encode(array(
        'status' => 'success',
        'user_statuses' => $user_statuses
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>