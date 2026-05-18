<?php
include_once "include/inc_base.php";

// 웹훅 보안을 위한 Mailjet 웹훅 시크릿 키 (Mailjet 대시보드에서 확인)
// 이 키는 API Secret Key와 다를 수 있습니다.
define('MAILJET_WEBHOOK_SECRET', 'YOUR_MAILJET_WEBHOOK_SIGNATURE_SECRET');

// -----------------------------------------------------------
// 2. 보안 검증: Mailjet 웹훅 시그니처 검증 (필수!)
// -----------------------------------------------------------
function verifyMailjetWebhookSignature($payload, $signature, $timestamp, $secret) {
    $parts = explode(',', $signature);
    $receivedTimestamp = '';
    $receivedSignature = '';

    foreach ($parts as $part) {
        if (strpos($part, 't=') === 0) {
            $receivedTimestamp = substr($part, 2);
        } elseif (strpos($part, 's=') === 0) {
            // PHP 5.5에서 hex2bin이 없을 수 있으므로 직접 구현 또는 다른 방법 고려
            // 여기서는 hex2bin이 있다고 가정합니다.
            $receivedSignature = substr(hex2bin(substr($part, 2)), 0);
        }
    }

    if (empty($receivedTimestamp) || empty($receivedSignature)) {
        return false;
    }

    // 시간 오차 범위 (예: 5분)
    if (abs(time() - (int)$receivedTimestamp) > 300) {
        error_log("Mailjet Webhook: Timestamp outside tolerance. Received: " . $receivedTimestamp . ", Current: " . time());
        return false;
    }

    // 서명 생성
    $signed_payload = $timestamp . $payload;
    $computedSignature = hash_hmac('sha256', $signed_payload, $secret, true); // raw_output=true

    // PHP 5.5에서는 hash_equals() 함수가 없으므로 간접적으로 비교 (보안상 약점)
    return base64_encode($computedSignature) === base64_encode($receivedSignature);
}


// POST 요청 본문 읽기
$payload = file_get_contents('php://input');

// 헤더에서 서명 및 타임스탬프 추출
$mailjetSignature = $_SERVER['HTTP_X_MAILJET_SIGNATURE'] ?? '';
$mailjetTimestamp = $_SERVER['HTTP_X_MAILJET_REQUEST_TIMESTAMP'] ?? '';

// **실제 운영 환경에서는 반드시 이 검증을 활성화해야 합니다.**
/*
if (!verifyMailjetWebhookSignature($payload, $mailjetSignature, $mailjetTimestamp, MAILJET_WEBHOOK_SECRET)) {
    error_log("Mailjet Webhook: Invalid signature or timestamp. Payload: " . $payload);
    http_response_code(403); // Forbidden
    die('Signature verification failed');
}
*/

// -----------------------------------------------------------
// 3. 웹훅 데이터 수신 및 파싱
// -----------------------------------------------------------
$eventData = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Mailjet Webhook: Invalid JSON payload. Input: " . $payload);
    http_response_code(400); // Bad Request
    die('Invalid JSON payload');
}

// -----------------------------------------------------------

// -----------------------------------------------------------
// 5. 이벤트 타입 확인 및 처리
// -----------------------------------------------------------
if (isset($eventData['event'])) {
    $eventType = $eventData['event'];
    $messageId = mysql_real_escape_string($eventData['MessageID'] ?? ''); // SQL 인젝션 방지
    $emailAddress = mysql_real_escape_string($eventData['email'] ?? '');
    $timestamp = mysql_real_escape_string($eventData['time'] ?? ''); // Unix timestamp

    // Mailjet 커스텀 ID (이메일 발송 시 PHPMailer에서 설정한 X-MJ-CustomID)
    $customId = mysql_real_escape_string($eventData['CustomID'] ?? '');

    switch ($eventType) {
        case 'open':
            // 이메일 오픈 이벤트 처리
            if (!empty($emailAddress) && !empty($messageId)) {
                // `mailing_history` 테이블에 `mailjet_message_id`, `status`, `open_date`, `custom_id` 필드가
                // 추가되었다는 가정하에 진행합니다.
                // 또는 `email_open_log`와 같은 별도의 테이블을 생성하여 상세 이력을 남깁니다.

                // 예시: mailing_history 업데이트
                // custom_mailjet_id 필드가 mailing_history 테이블에 존재해야 합니다.
                $update_query = "UPDATE mailing_history SET status = 'opened', open_date = FROM_UNIXTIME({$timestamp}) WHERE recipient = '{$emailAddress}' AND custom_id = '{$customId}'";
                $update_result = mysql_query($update_query);

                if ($update_result) {
                    error_log("Mailjet Webhook: Email opened - Email: {$emailAddress}, MessageID: {$messageId}, CustomID: {$customId}. Updated mailing_history.");
                } else {
                    error_log("Mailjet Webhook: Failed to update mailing_history for open event - " . mysql_error($dbConn) . " Query: " . $update_query);
                }

                // 예시: email_event_log 테이블에 기록 (권장, 위에서 제안한 테이블 스키마 사용)
                $insert_query = "INSERT INTO email_event_log (email, mailjet_message_id, custom_id, event_type, event_timestamp) VALUES ('{$emailAddress}', '{$messageId}', '{$customId}', 'open', FROM_UNIXTIME({$timestamp}))";
                $insert_result = mysql_query($insert_query);

                if ($insert_result) {
                     error_log("Mailjet Webhook: Logged open event to email_event_log - Email: {$emailAddress}");
                } else {
                     error_log("Mailjet Webhook: Failed to insert open event into email_event_log - " . mysql_error($dbConn) . " Query: " . $insert_query);
                }

            } else {
                error_log("Mailjet Webhook: Missing required data for open event. Data: " . $payload);
            }
            break;

        case 'click':
            // 이메일 클릭 이벤트 처리 (추가 구현 가능)
            $url = mysql_real_escape_string($eventData['url'] ?? '');
            if (!empty($emailAddress) && !empty($messageId) && !empty($url)) {
                error_log("Mailjet Webhook: Email clicked - Email: {$emailAddress}, URL: {$url}, MessageID: {$messageId}");
                // email_event_log 테이블에 기록
                $insert_query = "INSERT INTO email_event_log (email, mailjet_message_id, custom_id, event_type, event_timestamp, url) VALUES ('{$emailAddress}', '{$messageId}', '{$customId}', 'click', FROM_UNIXTIME({$timestamp}), '{$url}')";
                mysql_query($dbConn, $insert_query);
            }
            break;

        case 'bounce':
            // 이메일 반송 이벤트 처리 (추가 구현 가능)
            $hardBounce = isset($eventData['hard_bounce']) ? ($eventData['hard_bounce'] ? 'Yes' : 'No') : 'No';
            $error_details = mysql_real_escape_string($eventData['error'] ?? '');
            if (!empty($emailAddress) && !empty($messageId)) {
                error_log("Mailjet Webhook: Email bounced - Email: {$emailAddress}, Hard Bounce: {$hardBounce}, Error: {$error_details}");
                // email_event_log 테이블에 기록
                $insert_query = "INSERT INTO email_event_log (email, mailjet_message_id, custom_id, event_type, event_timestamp, error_details) VALUES ('{$emailAddress}', '{$messageId}', '{$customId}', 'bounce', FROM_UNIXTIME({$timestamp}), '{$error_details}')";
                mysql_query($insert_query);
                // `member_list` 또는 `prt_mlist` 테이블에서 해당 이메일의 발송 상태를 업데이트하여 향후 발송 방지
            }
            break;

        // 다른 이벤트 유형 (sent, delivered, spam, unsubscribe 등) 추가 처리 가능
        default:
            error_log("Mailjet Webhook: Unhandled event type - " . $eventType . ". Data: " . $payload);
            break;
    }
} else {
    error_log("Mailjet Webhook: Event type not found in payload. Data: " . $payload);
}

// -----------------------------------------------------------
// 6. 데이터베이스 연결 종료
// -----------------------------------------------------------
mysqli_close($dbConn);

// Mailjet 웹훅은 200 OK 응답을 기대합니다.
// 오류가 발생했더라도, 웹훅 처리는 정상적으로 받았음을 알려야 Mailjet이 재전송을 시도하지 않습니다.
http_response_code(200);
echo 'OK'; // Mailjet에 성공적인 수신을 알림

?>
