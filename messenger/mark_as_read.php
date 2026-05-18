<?php
// messenger/mark_as_read.php
// 메시지를 읽음으로 표시하는 API

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

// 현재 로그인한 사용자의 ID
$current_user_id = $user_info['user_id'];

// 파라미터 확인 - 연락처 ID 또는 메시지 ID 필요
if ((!isset($data['contact_id']) || empty($data['contact_id'])) && 
    (!isset($data['message_id']) || empty($data['message_id']))) {
    echo json_encode(array('status' => 'error', 'message' => '연락처 ID 또는 메시지 ID가 필요합니다.'));
    exit;
}

try {
    // 특정 연락처로부터 온 모든 메시지를 읽음으로 표시
    if (isset($data['contact_id']) && !empty($data['contact_id'])) {
        $contact_id = mysql_real_escape_string($data['contact_id'], $dbConn);
        
        // 메시지 업데이트
        $sql = "UPDATE messenger_messages 
                SET is_read = 1 
                WHERE sender_id = '" . $contact_id . "' 
                AND recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
                AND is_read = 0";
        
        $result = mysql_query($sql, $dbConn);
        if (!$result) {
            throw new Exception("메시지 읽음 표시 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        $affected_rows = mysql_affected_rows($dbConn);
        
        // 읽음 확인 기록 추가
        if ($affected_rows > 0) {
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
        }
        
        echo json_encode(array(
            'status' => 'success',
            'marked_count' => $affected_rows
        ));
    }
    // 특정 메시지를 읽음으로 표시
    else if (isset($data['message_id']) && !empty($data['message_id'])) {
        $message_id = intval($data['message_id']);
        
        // 메시지가 현재 사용자에게 온 것인지 확인
        $sql = "SELECT sender_id FROM messenger_messages 
                WHERE message_id = " . $message_id . " 
                AND recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "'";
        
        $result = mysql_query($sql, $dbConn);
        if (!$result) {
            throw new Exception("메시지 확인 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        if (mysql_num_rows($result) === 0) {
            echo json_encode(array(
                'status' => 'error',
                'message' => '유효하지 않은 메시지 ID입니다.'
            ));
            exit;
        }
        
        $row = mysql_fetch_assoc($result);
        $sender_id = $row['sender_id'];
        
        // 메시지 업데이트
        $sql = "UPDATE messenger_messages 
                SET is_read = 1 
                WHERE message_id = " . $message_id . " 
                AND recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "'";
        
        $update_result = mysql_query($sql, $dbConn);
        if (!$update_result) {
            throw new Exception("메시지 읽음 표시 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        // 읽음 확인 기록 추가
        $sql = "INSERT INTO messenger_read_receipts (message_id, user_id, read_at)
                VALUES (" . $message_id . ", '" . mysql_real_escape_string($current_user_id, $dbConn) . "', NOW())
                ON DUPLICATE KEY UPDATE read_at = NOW()";
        
        $insert_result = mysql_query($sql, $dbConn);
        
        echo json_encode(array(
            'status' => 'success',
            'marked_count' => 1
        ));
    }
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?><?php
// messenger/mark_as_read.php
// 메시지를 읽음으로 표시하는 API

// 세션 확인 및 필요한 포함 파일
session_start();
include "../inc_base.php";

// 응답 헤더 설정
header('Content-Type: application/json');

// POST 데이터 확인
$data = json_decode(file_get_contents('php://input'), true);

// 로그인 확인
if (!isset($_COOKIE['MEMLOGIN_ADMIN_PURUN']) || empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo json_encode(array('status' => 'error', 'message' => '로그인이 필요합니다.'));
    exit;
}

// 현재 로그인한 사용자의 ID
$current_user_id = $user_info['user_id'];

// 파라미터 확인 - 연락처 ID 또는 메시지 ID 필요
if ((!isset($data['contact_id']) || empty($data['contact_id'])) && 
    (!isset($data['message_id']) || empty($data['message_id']))) {
    echo json_encode(array('status' => 'error', 'message' => '연락처 ID 또는 메시지 ID가 필요합니다.'));
    exit;
}

try {
    // 특정 연락처로부터 온 모든 메시지를 읽음으로 표시
    if (isset($data['contact_id']) && !empty($data['contact_id'])) {
        $contact_id = mysql_real_escape_string($data['contact_id'], $dbConn);
        
        // 메시지 업데이트
        $sql = "UPDATE messenger_messages 
                SET is_read = 1 
                WHERE sender_id = '" . $contact_id . "' 
                AND recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
                AND is_read = 0";
        
        $result = mysql_query($sql, $dbConn);
        if (!$result) {
            throw new Exception("메시지 읽음 표시 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        $affected_rows = mysql_affected_rows($dbConn);
        
        // 읽음 확인 기록 추가
        if ($affected_rows > 0) {
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
        }
        
        echo json_encode(array(
            'status' => 'success',
            'marked_count' => $affected_rows
        ));
    }
    // 특정 메시지를 읽음으로 표시
    else if (isset($data['message_id']) && !empty($data['message_id'])) {
        $message_id = intval($data['message_id']);
        
        // 메시지가 현재 사용자에게 온 것인지 확인
        $sql = "SELECT sender_id FROM messenger_messages 
                WHERE message_id = " . $message_id . " 
                AND recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "'";
        
        $result = mysql_query($sql, $dbConn);
        if (!$result) {
            throw new Exception("메시지 확인 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        if (mysql_num_rows($result) === 0) {
            echo json_encode(array(
                'status' => 'error',
                'message' => '유효하지 않은 메시지 ID입니다.'
            ));
            exit;
        }
        
        $row = mysql_fetch_assoc($result);
        $sender_id = $row['sender_id'];
        
        // 메시지 업데이트
        $sql = "UPDATE messenger_messages 
                SET is_read = 1 
                WHERE message_id = " . $message_id . " 
                AND recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "'";
        
        $update_result = mysql_query($sql, $dbConn);
        if (!$update_result) {
            throw new Exception("메시지 읽음 표시 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        // 읽음 확인 기록 추가
        $sql = "INSERT INTO messenger_read_receipts (message_id, user_id, read_at)
                VALUES (" . $message_id . ", '" . mysql_real_escape_string($current_user_id, $dbConn) . "', NOW())
                ON DUPLICATE KEY UPDATE read_at = NOW()";
        
        $insert_result = mysql_query($sql, $dbConn);
        
        echo json_encode(array(
            'status' => 'success',
            'marked_count' => 1
        ));
    }
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>