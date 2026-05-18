<?php
// messenger/unread_count.php
// 읽지 않은 메시지 수 API

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

try {
    // 전체 읽지 않은 메시지 수 가져오기
    $sql = "SELECT COUNT(*) as total_unread 
            FROM messenger_messages 
            WHERE recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
            AND is_read = 0 AND deleted_by_recipient = 0";
    
    $result = mysql_query($sql, $dbConn);
    if (!$result) {
        throw new Exception("읽지 않은 메시지 수 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
    }
    
    $row = mysql_fetch_assoc($result);
    $total_unread = $row['total_unread'];
    
    // 사용자별 읽지 않은 메시지 수 가져오기
    $sql = "SELECT sender_id, COUNT(*) as unread_count 
            FROM messenger_messages 
            WHERE recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
            AND is_read = 0 AND deleted_by_recipient = 0
            GROUP BY sender_id";
    
    $result = mysql_query($sql, $dbConn);
    if (!$result) {
        throw new Exception("사용자별 읽지 않은 메시지 수 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
    }
    
    $unread_counts = array();
    
    while ($row = mysql_fetch_assoc($result)) {
        $unread_counts[$row['sender_id']] = intval($row['unread_count']);
    }
    
    // 마지막 미확인 메시지 정보 (선택적)
    $latest_unread = null;
    
    if ($total_unread > 0) {
        $sql = "SELECT m.message_id, m.sender_id, m.message_text, m.timestamp, u.kor_name
                FROM messenger_messages m
                JOIN member_list u ON m.sender_id = u.userid
                WHERE m.recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
                AND m.is_read = 0 AND m.deleted_by_recipient = 0
                ORDER BY m.timestamp DESC
                LIMIT 1";
        
        $result = mysql_query($sql, $dbConn);
        if (!$result) {
            throw new Exception("최근 읽지 않은 메시지 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        if ($row = mysql_fetch_assoc($result)) {
            $latest_unread = array(
                'message_id' => $row['message_id'],
                'sender_id' => $row['sender_id'],
                'sender_name' => $row['kor_name'],
                'message' => $row['message_text'],
                'timestamp' => $row['timestamp']
            );
        }
    }
    
    // 응답 반환
    $response = array(
        'status' => 'success',
        'unread_count' => $total_unread,
        'unread_counts' => $unread_counts
    );
    
    if ($latest_unread) {
        $response['latest_unread'] = $latest_unread;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>