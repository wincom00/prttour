<?php
// messenger/update_status.php
// 사용자 상태 업데이트 API

// 세션 확인 및 필요한 포함 파일
session_start();
include "../include/inc_base.php";

// 응답 헤더 설정
header('Content-Type: application/json');

// POST 데이터 확인
$data = json_decode(file_get_contents('php://input'), true);

// 로그인 확인
if (!isset($_COOKIE['MEMLOGIN_ADMIN_PURUN']) || empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo json_encode(array('status' => 'error', 'message' => '로그인이 필요합니다.'));
    exit;
}

// 상태 값 확인
if (!isset($data['status']) || !in_array($data['status'], array('online', 'away', 'offline'))) {
    echo json_encode(array('status' => 'error', 'message' => '유효하지 않은 상태 값입니다.'));
    exit;
}

// 현재 로그인한 사용자의 ID
//$current_user_id = $_COOKIE['MEMLOGIN_ADMIN_PURUN'];
$current_user_id =$user_info['user_id'];
try {
    // 현재 상태 레코드 확인
    $sql = "SELECT user_id FROM messenger_user_status WHERE user_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "'";
    $result = mysql_query($sql, $dbConn);
    
    if (mysql_num_rows($result) > 0) {
        // 레코드가 이미 존재하면 업데이트
        $sql = "UPDATE messenger_user_status 
                SET status = '" . mysql_real_escape_string($data['status'], $dbConn) . "', 
                    last_activity = NOW() 
                WHERE user_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "'";
        
        $update_result = mysql_query($sql, $dbConn);
        
        if (!$update_result) {
            throw new Exception("상태 업데이트 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
    } else {
        // 레코드가 없으면 새로 생성
        $sql = "INSERT INTO messenger_user_status (user_id, status, last_activity) 
                VALUES ('" . mysql_real_escape_string($current_user_id, $dbConn) . "', 
                        '" . mysql_real_escape_string($data['status'], $dbConn) . "', 
                        NOW())";
        
        $insert_result = mysql_query($sql, $dbConn);
        
        if (!$insert_result) {
            throw new Exception("상태 생성 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
    }
    
    // 응답 반환
    echo json_encode(array(
        'status' => 'success',
        'message' => '상태가 성공적으로 업데이트되었습니다.'
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>