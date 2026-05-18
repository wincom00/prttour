<?php
// messenger/send_message.php
// 메시지 전송 API
//ini_set('display_errors', on);
///    ini_set('display_startup_errors', 1);
//    error_reporting(E_ALL & ~E_NOTICE);
// 세션 확인 및 필요한 포함 파일
session_start();
include "../include/inc_base.php";

// 응답 헤더 설정
header('Content-Type: application/json');

// POST 데이터 확인
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_COOKIE['MEMLOGIN_ADMIN_PURUN']) || empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo json_encode(array('status' => 'error', 'message' => '로그인이 필요합니다.'));
    exit;
}

if (!isset($data['recipient_id']) || !isset($data['message']) || empty($data['message'])) {
    echo json_encode(array('status' => 'error', 'message' => '필수 필드가 누락되었습니다.'));
    exit;
}

// 현재 로그인한 사용자의 ID 가져오기
$sender_id = $user_info['user_id'];
$recipient_id = mysql_real_escape_string($data['recipient_id'], $dbConn);
$message_text = mysql_real_escape_string($data['message'], $dbConn);

// XSS 방지를 위한 메시지 필터링은 이미 mysql_real_escape_string에서 처리

try {
    // 메시지 저장
    $sql = "INSERT INTO messenger_messages (sender_id, recipient_id, message_text) 
            VALUES ('" . $sender_id . "', '" . $recipient_id . "', '" . $message_text . "')";
    
    $result = mysql_query($sql, $dbConn);
    if (!$result) {
        throw new Exception("메시지 저장 중 오류가 발생했습니다: " . mysql_error($dbConn));
    }
    
    $message_id = mysql_insert_id($dbConn);
    
    // 대화 업데이트 또는 생성
    // user1_id는 항상 사전순으로 더 작은 값이 되도록 함
    $user1 = ($sender_id < $recipient_id) ? $sender_id : $recipient_id;
    $user2 = ($sender_id < $recipient_id) ? $recipient_id : $sender_id;
    
    // MySQL에서는 DUPLICATE KEY UPDATE 문법을 지원하지만, 구 버전에서는 ON DUPLICATE KEY가 없을 수 있으므로 INSERT-UPDATE 로직 분리
    $check_sql = "SELECT conversation_id FROM messenger_conversations 
                  WHERE user1_id = '" . $user1 . "' AND user2_id = '" . $user2 . "'";
    $check_result = mysql_query($check_sql, $dbConn);
    
    if (mysql_num_rows($check_result) > 0) {
        // 이미 대화가 존재하면 업데이트
        $sql = "UPDATE messenger_conversations 
                SET last_message_id = " . $message_id . ", updated_at = NOW() 
                WHERE user1_id = '" . $user1 . "' AND user2_id = '" . $user2 . "'";
        $update_result = mysql_query($sql, $dbConn);
        
        if (!$update_result) {
            throw new Exception("대화 업데이트 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
    } else {
        // 대화가 없으면 새로 생성
        $sql = "INSERT INTO messenger_conversations (user1_id, user2_id, last_message_id, updated_at) 
                VALUES ('" . $user1 . "', '" . $user2 . "', " . $message_id . ", NOW())";
        $insert_result = mysql_query($sql, $dbConn);
        
        if (!$insert_result) {
            throw new Exception("대화 생성 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
    }
    
    // 응답 반환
    echo json_encode(array(
        'status' => 'success',
        'message_id' => $message_id,
        'timestamp' => date('Y-m-d H:i:s')
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>