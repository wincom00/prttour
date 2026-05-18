<?php
// messenger/message_popup.php
// 새 메시지 팝업 페이지 (상태 표시 추가)

// 세션 확인 및 필요한 포함 파일
session_start();
include "../include/inc_base.php";

// 로그인 확인
if (!isset($_COOKIE['MEMLOGIN_ADMIN_PURUN']) || empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo "<script>alert('로그인이 필요합니다.'); window.close();</script>";
    exit;
}

// 현재 로그인한 사용자의 ID
$current_user_id = $user_info['user_id'];

// 모드 확인 (팝업 또는 최근 메시지)
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'latest';

// 최근 읽지 않은 메시지 가져오기
$sql = "SELECT m.message_id, m.sender_id, m.message_text, m.timestamp, 
               u.kor_name, u.c_part1, u.profile_image_url
        FROM messenger_messages m
        JOIN member_list u ON m.sender_id = u.userid
        WHERE m.recipient_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' 
        AND m.is_read = 0 AND m.deleted_by_recipient = 0
        ORDER BY m.timestamp DESC
        LIMIT 1";

$result = mysql_query($sql, $dbConn);
if (!$result || mysql_num_rows($result) === 0) {
    echo "<script>alert('새로운 메시지가 없습니다.'); window.close();</script>";
    exit;
}

$message = mysql_fetch_assoc($result);

// 발신자의 상태 정보 가져오기
$sender_id = $message['sender_id'];
$status = 'unknown'; // 기본값

$status_sql = "SELECT status FROM messenger_user_status WHERE user_id = '" . mysql_real_escape_string($sender_id, $dbConn) . "'";
$status_result = mysql_query($status_sql, $dbConn);

if ($status_result && mysql_num_rows($status_result) > 0) {
    $status_row = mysql_fetch_assoc($status_result);
    $status = $status_row['status'];
}

// 상태 텍스트 매핑
$status_text = array(
    'online' => '온라인',
    'away' => '자리비움',
    'busy' => '바쁨',
    'offline' => '오프라인',
    'unknown' => '상태 확인 중...'
);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 메시지 알림</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../img/font-awesome/css/font-awesome.min.css">
    <style>
        body {
            font-family: 'Nanum Gothic', sans-serif;
            background-color: #f8f9fa;
            padding: 15px;
            margin: 0;
            overflow: hidden;
        }
        
        .popup-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 100%;
        }
        
        .popup-header {
            padding: 12px 15px;
            background-color: #0062dd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .popup-title {
            font-weight: bold;
            font-size: 16px;
            margin: 0;
        }
        
        .popup-close {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }
        
        .popup-sender {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
        }
        
        .popup-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 1px solid #dee2e6;
            position: relative;
        }
        
        .default-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #0062dd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            margin-right: 10px;
            position: relative;
        }
        
        .popup-sender-info {
            flex: 1;
        }
        
        .popup-sender-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .popup-sender-department {
            font-size: 12px;
            color: #6c757d;
        }
        
        .popup-message {
            padding: 15px;
            min-height: 80px;
            line-height: 1.5;
            word-break: break-word;
        }
        
        .popup-time {
            text-align: right;
            font-size: 12px;
            color: #6c757d;
            padding: 0 15px 10px;
        }
        
        .popup-actions {
            padding: 10px 15px;
            text-align: right;
            border-top: 1px solid #e9ecef;
        }
        
        .popup-actions button {
            margin-left: 10px;
        }
        
        /* 상태 표시기 스타일 */
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            position: absolute;
            bottom: -2px;
            right: -2px;
            border: 2px solid white;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
        }
        
        .status-online {
            background-color: #34c759; /* 녹색 - 온라인 */
        }
        
        .status-away {
            background-color: #ffcc00; /* 노란색 - 자리비움 */
        }
        
        .status-busy {
            background-color: #ff3b30; /* 빨간색 - 바쁨 */
        }
        
        .status-offline {
            background-color: #8e8e93; /* 회색 - 오프라인 */
        }
        
        .status-unknown {
            background-color: #cccccc; /* 연한 회색 - 상태 알 수 없음 */
        }
        
        .popup-sender-status {
            display: flex;
            align-items: center;
            font-size: 11px;
            color: #6c757d;
            margin-top: 2px;
        }
        
        .popup-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-right: 4px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="popup-container">
        <div class="popup-header">
            <h2 class="popup-title">새 메시지 알림</h2>
            <button class="popup-close" onclick="window.close()">&times;</button>
        </div>
        
        <div class="popup-sender">
            <?php if (!empty($message['profile_image_url'])): ?>
                <div style="position: relative;">
                    <img src="<?php echo $message['profile_image_url']; ?>" alt="<?php echo $message['kor_name']; ?>" class="popup-avatar">
                    <span class="status-indicator status-<?php echo $status; ?>"></span>
                </div>
            <?php else: ?>
                <div style="position: relative;">
                    <div class="default-avatar"><?php echo mb_substr($message['kor_name'], 0, 1, 'UTF-8'); ?></div>
                    <span class="status-indicator status-<?php echo $status; ?>"></span>
                </div>
            <?php endif; ?>
            
            <div class="popup-sender-info">
                <div class="popup-sender-name"><?php echo $message['kor_name']; ?></div>
                <div class="popup-sender-department"><?php echo $message['c_part1'] ?: '부서 정보 없음'; ?></div>
                <div class="popup-sender-status">
                    <span class="popup-status-dot status-<?php echo $status; ?>"></span>
                    <span><?php echo $status_text[$status]; ?></span>
                </div>
            </div>
        </div>
        
        <div class="popup-message">
            <?php echo htmlspecialchars($message['message_text']); ?>
        </div>
        
        <div class="popup-time">
            <?php echo date('Y-m-d H:i', strtotime($message['timestamp'])); ?>
        </div>
        
        <div class="popup-actions">
            <button class="btn btn-sm btn-default" onclick="window.close()">닫기</button>
            <button class="btn btn-sm btn-primary" onclick="openMessenger()">대화하기</button>
        </div>
    </div>
    
    <script>
        // 메시지 읽음으로 표시
        function markAsRead() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../messenger/mark_as_read.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(JSON.stringify({
                message_id: <?php echo $message['message_id']; ?>
            }));
        }
        
        // 메신저 페이지 열기
        function openMessenger() {
            window.opener.location.href = '../messenger.php?open_chat=<?php echo $sender_id; ?>';
            window.close();
        }
        
        // 페이지 로드 시 메시지 읽음으로 표시
        window.onload = markAsRead;
    </script>
</body>
</html>