<?php
// email-sys/newsletter_background_worker.php
// 백그라운드에서 뉴스레터 발송을 처리하는 워커 스크립트

ini_set('max_execution_time', 0); // 시간 제한 없음
ini_set('memory_limit', '256M');

include "../include/inc_base.php";

// 로그 함수
function writeLog($message) {
    $log_file = __DIR__ . './newsletter_logs/newsletter_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

function newsletterSafeMailSend($to_email, $subject, $content, $attachment1, $attachment2) {
    $to_email = trim($to_email);

    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return array(
            'success' => false,
            'message' => 'Invalid email format: ' . $to_email
        );
    }

    try {
        $result = mailsend_a($to_email, $subject, $content, $attachment1, $attachment2);

        if ($result === true) {
            return array('success' => true, 'message' => '');
        }

        return array(
            'success' => false,
            'message' => is_string($result) ? $result : 'Mail send failed'
        );
    } catch (Throwable $e) {
        return array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
}

writeLog("뉴스레터 백그라운드 워커 시작");

// 대기중인 큐 조회
$qry = "SELECT q.*, n.title, n.subject, n.content ,n.main_image
        FROM newsletter_queue q 
        LEFT JOIN newsletter_templates n ON q.newsletter_id = n.seq_no 
        WHERE q.status = 'WAITING' 
        ORDER BY q.created_at ASC 
        LIMIT 1";

$rst = mysql_query($qry, $dbConn);

if(mysql_num_rows($rst) == 0) {
    writeLog("처리할 큐가 없습니다.");
    exit;
}

$queue = mysql_fetch_assoc($rst);
$queue_id = $queue['seq_no'];
$newsletter_id = $queue['newsletter_id'];

writeLog("큐 처리 시작: Queue ID $queue_id, Newsletter ID $newsletter_id, Title: {$queue['title']}");

// 큐 상태를 PROCESSING으로 변경
$update_status = "UPDATE newsletter_queue SET 
                  status = 'PROCESSING', 
                  started_at = NOW() 
                  WHERE seq_no = '$queue_id'";
mysql_query($update_status, $dbConn);

// 발송할 목록 조회
$qry_details = "SELECT * FROM newsletter_send_details 
                WHERE queue_id = '$queue_id' AND send_status = 'PENDING' 
                ORDER BY seq_no ASC";
$rst_details = mysql_query($qry_details, $dbConn);

$sent_count = 0;
$failed_count = 0;
$total_count = mysql_num_rows($rst_details);

writeLog("총 발송 대상: $total_count 명");

while($detail = mysql_fetch_assoc($rst_details)) {
    $detail_id = $detail['seq_no'];
    $to_email = trim($detail['recipient_email']);
    $send_error = '';
    $to_name = $detail['recipient_name'] ?: '고객님';
    
    try {
        // 개인화된 내용 생성
        $personalized_content = str_replace(
            ['{{name}}', '{{email}}'], 
            [$to_name, $to_email], 
            $queue['content']
        );
        
        
        
               
        // 이메일 발송
        $send_result = newsletterSafeMailSend($to_email,$queue['subject'],$queue['content'],$attachment1,$attachment2);
		if ($send_result['success']) {

       // if(mail($to_email, $queue['subject'], $html_content, $headers)) {
            $sent_count++;
            
            // 발송 성공 업데이트
            $update_detail = "UPDATE newsletter_send_details SET 
                             send_status = 'SENT', 
                             sent_at = NOW() 
                             WHERE seq_no = '$detail_id'";
            mysql_query($update_detail, $dbConn);
            /*
            // 발송 이력 기록
            $insert_history = "INSERT INTO mailing_history 
                              (division, send_reg, recipient, subject, message, sent_on) 
                              VALUES 
                              ('newsletter_bg', 'system', '$to_email', 
                               '{$queue['subject']}', '$personalized_content', NOW())";
            mysql_query($insert_history, $dbConn);
			*/
            
        } else {
            $failed_count++;
            $send_error = mysql_real_escape_string($send_result['message']);
             // 발송 성공 업데이트
            $update_detail = "UPDATE newsletter_send_details SET 
                             send_status = 'FAILED', 
                             error_message = '$send_error',
                             sent_at = NOW() 
                             WHERE seq_no = '$detail_id'";
            mysql_query($update_detail, $dbConn);
        }
        
    } catch (Throwable $e) {
        $failed_count++;
        $error_msg = mysql_real_escape_string($e->getMessage());
        
        // 발송 실패 업데이트
        $update_detail = "UPDATE newsletter_send_details SET 
                         send_status = 'FAILED', 
                         error_message = '$error_msg',
                         sent_at = NOW()
                         WHERE seq_no = '$detail_id'";
        mysql_query($update_detail, $dbConn);
    }
    
    // 진행률 업데이트
    $processed = $sent_count + $failed_count;
    $progress = round(($processed / $total_count) * 100, 2);
    
    $update_progress = "UPDATE newsletter_queue SET 
                       sent_count = '$sent_count', 
                       failed_count = '$failed_count', 
                       progress_percent = '$progress' 
                       WHERE seq_no = '$queue_id'";
    mysql_query($update_progress, $dbConn);
    
	$update_mailing = "UPDATE prt_mlist SET 
                         chk_send = '1'
                       WHERE mail_addr = '$to_email'";
    mysql_query($update_mailing, $dbConn);
	
    // 과부하 방지를 위한 지연 (100ms)
    usleep(150000);
    
    // 20건마다 로그 출력
    if($processed % 20 == 0) {
        writeLog("진행률: $progress% ($processed/$total_count)");
    }
}

// 최종 상태 업데이트
if($failed_count == 0) {
    $final_status = 'COMPLETED';
    writeLog("발송 완료: 모든 이메일이 성공적으로 발송되었습니다. (총 $sent_count건)");
} else if($sent_count > 0) {
    $final_status = 'COMPLETED';
    writeLog("발송 완료: 성공 $sent_count건, 실패 $failed_count건");
} else {
    $final_status = 'FAILED';
    writeLog("발송 실패: 모든 이메일 발송이 실패했습니다.");
}

// 큐 완료 처리
$update_final = "UPDATE newsletter_queue SET 
                 status = '$final_status', 
                 completed_at = NOW(),
                 sent_count = '$sent_count',
                 failed_count = '$failed_count',
                 progress_percent = 100 
                 WHERE seq_no = '$queue_id'";
mysql_query($update_final, $dbConn);

// 뉴스레터 상태 업데이트 (성공한 경우에만)
if($sent_count > 0) {
    $update_newsletter = "UPDATE newsletter_templates SET 
                         send_status = 'SENT', 
                         send_date = NOW() 
                         WHERE seq_no = '$newsletter_id'";
    mysql_query($update_newsletter, $dbConn);
	
	
	 // ========================================
    // news_hist 테이블에 데이터 삽입 (NEW!)
    // ========================================
    
   
    // HTML 태그 제거하여 텍스트만 추출
    $plain_content = strip_tags($queue['content']);
    $plain_content = html_entity_decode($plain_content);
    
    // 내용이 너무 길면 자르기 (MySQL text 타입 제한 고려)
    if (strlen($plain_content) > 60000) {
        $plain_content = substr($plain_content, 0, 60000) . '...';
    }
	
	$src = getImageSrcWithDOM($queue['content']);
    
    // 제목과 내용 이스케이프
    $escaped_subject = mysql_real_escape_string($queue['subject']);
    $escaped_content = mysql_real_escape_string($plain_content);
    $escaped_image = mysql_real_escape_string($first_image);
    
    // news_hist 테이블에 삽입
    $insert_news_hist = "INSERT INTO news_hist 
                        (subj, content, send_date, img, wdate, count_n) 
                        VALUES 
                        ('".$queue['subject']."', '$src' , NOW(), '".$queue['main_image']."', NOW(), '0')";
    
    if(mysql_query($insert_news_hist, $dbConn)) {
        writeLog("news_hist 테이블에 데이터 삽입 완료 (발송건수: $sent_count)");
    } else {
        writeLog("news_hist 테이블 삽입 실패: " . mysql_error());
    }
}

writeLog("뉴스레터 백그라운드 워커 완료");
?>
