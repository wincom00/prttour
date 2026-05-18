<?php
// messenger/get_messages.php
// 특정 사용자와의 메시지 목록 조회 API

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

// 현재 로그인한 사용자의 ID
$current_user_id = $user_info['user_id'];

// 대화 상대 ID 확인
if (!isset($_GET['contact_id']) || empty($_GET['contact_id'])) {
    echo json_encode(array('status' => 'error', 'message' => '대화 상대 ID가 필요합니다.'));
    exit;
}

$contact_id = mysql_real_escape_string($_GET['contact_id'], $dbConn);

try {
    // 페이지네이션 파라미터 (선택적)
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50; // 기본 50개 메시지
    $offset = ($page - 1) * $limit;
    
    // 두 사용자 간의 메시지 조회
    $sql = "SELECT m.message_id, m.sender_id, m.recipient_id, m.message_text, m.timestamp, m.is_read
            FROM messenger_messages m
            WHERE ((m.sender_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
                    AND m.recipient_id = '" . $contact_id . "' 
                    AND m.deleted_by_sender = 0)
               OR (m.sender_id = '" . $contact_id . "' 
                    AND m.recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
                    AND m.deleted_by_recipient = 0))
            ORDER BY m.timestamp DESC
            LIMIT " . $offset . ", " . $limit;
    
    $result = mysql_query($sql, $dbConn);
    if (!$result) {
        throw new Exception("메시지 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
    }
    
    $messages = array();
    
    while ($row = mysql_fetch_assoc($result)) {
        $messages[] = array(
            'message_id' => $row['message_id'],
            'sender_id' => $row['sender_id'],
            'recipient_id' => $row['recipient_id'],
            'message' => $row['message_text'],
            'timestamp' => $row['timestamp'],
            'is_read' => (bool) $row['is_read']
        );
    }
    
    // 최신 메시지가 마지막에 오도록 배열 순서 반전
    $messages = array_reverse($messages);
    
    // 이 사용자로부터 받은 메시지를 읽음으로 표시
    $sql = "UPDATE messenger_messages 
            SET is_read = 1 
            WHERE sender_id = '" . $contact_id . "' 
            AND recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
            AND is_read = 0";
    
    $update_result = mysql_query($sql, $dbConn);
    if (!$update_result) {
        throw new Exception("메시지 읽음 표시 중 오류가 발생했습니다: " . mysql_error($dbConn));
    }
    
    // 읽음 확인 기록 추가
    $sql = "INSERT INTO messenger_read_receipts (message_id, user_id, read_at)
            SELECT message_id, '" . mysql_real_escape_string($current_user_id, $dbConn) . "', NOW()
            FROM messenger_messages 
            WHERE sender_id = '" . $contact_id . "' 
            AND recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
            AND is_read = 1
            AND NOT EXISTS (
                SELECT 1 FROM messenger_read_receipts 
                WHERE messenger_read_receipts.message_id = messenger_messages.message_id 
                AND messenger_read_receipts.user_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "'
            )";
    
    $insert_result = mysql_query($sql, $dbConn);
    
    // 응답 반환
    echo json_encode(array(
        'status' => 'success',
        'messages' => $messages,
        'pagination' => array(
            'page' => $page,
            'limit' => $limit,
            'total' => count($messages)
        )
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>