<?php
// email-sys/newsletter_background_worker.php
// 백그라운드에서 뉴스레터 발송을 처리하는 워커 스크립트

ini_set('max_execution_time', 0); // 시간 제한 없음
ini_set('memory_limit', '256M');

include __DIR__ . "/../include/inc_base.php";

$active_queue_id = 0;
$active_detail_id = 0;
$lock_fp = null;        // (구버전 호환용, 미사용)
$lock_dir = null;       // 원자적 mkdir 락 디렉터리
$lock_owned = false;    // 이 프로세스가 락을 소유했는지
$lock_stale_sec = 240;  // 하트비트(디렉터리 mtime) 미갱신이 이 시간을 넘으면 죽은 워커로 간주해 회수

// 로그 함수
function writeLog($message) {
    $log_file = __DIR__ . '/newsletter_logs/newsletter_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

function newsletterWorkerSelectedQueueIds() {
    global $argv;

    $queue_ids = array();

    // 1) CLI 인자: --queue_ids=1,2,3
    if (is_array($argv)) {
        foreach ($argv as $arg) {
            if (strpos($arg, '--queue_ids=') === 0) {
                $raw_ids = explode(',', substr($arg, strlen('--queue_ids=')));
                foreach ($raw_ids as $id) {
                    $id = trim($id);
                    if ($id !== '' && ctype_digit($id) && intval($id) > 0) {
                        $queue_ids[intval($id)] = intval($id);
                    }
                }
            }
        }
    }

    // 2) 웹 호출(서버 백그라운드 실행): ?queue_ids=1,2,3
    if (count($queue_ids) === 0 && isset($_GET['queue_ids'])) {
        $raw = is_array($_GET['queue_ids']) ? $_GET['queue_ids'] : explode(',', (string) $_GET['queue_ids']);
        foreach ($raw as $id) {
            $id = trim($id);
            if ($id !== '' && ctype_digit($id) && intval($id) > 0) {
                $queue_ids[intval($id)] = intval($id);
            }
        }
    }

    return array_values($queue_ids);
}

function newsletterGetQueueCounts($queue_id, $total_count = null) {
    global $dbConn;

    $queue_id = mysql_real_escape_string($queue_id);

    if ($total_count === null) {
        $total_qry = "SELECT total_recipients FROM newsletter_queue WHERE seq_no = '$queue_id'";
        $total_rst = mysql_query($total_qry, $dbConn);
        $total_row = $total_rst ? mysql_fetch_assoc($total_rst) : null;
        $total_count = $total_row ? intval($total_row['total_recipients']) : 0;
    }

    if ($total_count <= 0) {
        $total_rst = mysql_query("SELECT COUNT(*) AS cnt FROM newsletter_send_details WHERE queue_id = '$queue_id'", $dbConn);
        $total_row = $total_rst ? mysql_fetch_assoc($total_rst) : null;
        $total_count = $total_row ? intval($total_row['cnt']) : 0;
    }

    $sent_rst = mysql_query("SELECT COUNT(*) AS cnt FROM newsletter_send_details WHERE queue_id = '$queue_id' AND send_status = 'SENT'", $dbConn);
    $sent_row = $sent_rst ? mysql_fetch_assoc($sent_rst) : null;
    $sent_count = $sent_row ? intval($sent_row['cnt']) : 0;

    $failed_rst = mysql_query("SELECT COUNT(*) AS cnt FROM newsletter_send_details WHERE queue_id = '$queue_id' AND send_status = 'FAILED'", $dbConn);
    $failed_row = $failed_rst ? mysql_fetch_assoc($failed_rst) : null;
    $failed_count = $failed_row ? intval($failed_row['cnt']) : 0;

    $pending_rst = mysql_query("SELECT COUNT(*) AS cnt FROM newsletter_send_details WHERE queue_id = '$queue_id' AND send_status = 'PENDING'", $dbConn);
    $pending_row = $pending_rst ? mysql_fetch_assoc($pending_rst) : null;
    $pending_count = $pending_row ? intval($pending_row['cnt']) : 0;

    $processed = $sent_count + $failed_count;
    $progress = ($total_count > 0) ? round(($processed / $total_count) * 100, 2) : 100;
    if ($progress > 100) {
        $progress = 100;
    }

    return array(
        'total' => $total_count,
        'sent' => $sent_count,
        'failed' => $failed_count,
        'pending' => $pending_count,
        'processed' => $processed,
        'progress' => $progress
    );
}

function newsletterUpdateQueueProgress($queue_id, $total_count = null) {
    global $dbConn;

    $counts = newsletterGetQueueCounts($queue_id, $total_count);
    $queue_id = mysql_real_escape_string($queue_id);

    $update_progress = "UPDATE newsletter_queue SET 
                       sent_count = '{$counts['sent']}', 
                       failed_count = '{$counts['failed']}', 
                       progress_percent = '{$counts['progress']}' 
                       WHERE seq_no = '$queue_id'";
    mysql_query($update_progress, $dbConn);

    return $counts;
}

function newsletterGetQueueStatus($queue_id) {
    global $dbConn;
    $queue_id = mysql_real_escape_string($queue_id);
    $rst = mysql_query("SELECT status FROM newsletter_queue WHERE seq_no = '$queue_id'", $dbConn);
    $row = $rst ? mysql_fetch_assoc($rst) : null;
    return $row ? $row['status'] : '';
}

register_shutdown_function(function() {
    global $dbConn, $active_queue_id, $active_detail_id, $lock_dir, $lock_owned;

    $error = error_get_last();
    if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
        $error_msg = mysql_real_escape_string('Worker stopped: ' . $error['message']);

        if ($active_detail_id) {
            $detail_id = mysql_real_escape_string($active_detail_id);
            mysql_query("UPDATE newsletter_send_details SET 
                        send_status = 'FAILED',
                        error_message = '$error_msg',
                        sent_at = NOW()
                        WHERE seq_no = '$detail_id' AND send_status = 'PENDING'", $dbConn);
        }

        if ($active_queue_id && newsletterGetQueueStatus($active_queue_id) !== 'STOPPED') {
            $counts = newsletterUpdateQueueProgress($active_queue_id);
            $queue_id = mysql_real_escape_string($active_queue_id);
            $status = ($counts['pending'] > 0) ? 'WAITING' : (($counts['sent'] > 0) ? 'COMPLETED' : 'FAILED');
            $completed_sql = ($counts['pending'] > 0) ? "completed_at = completed_at" : "completed_at = NOW()";
            mysql_query("UPDATE newsletter_queue SET 
                        status = '$status',
                        $completed_sql
                        WHERE seq_no = '$queue_id'", $dbConn);
        }

        writeLog($error_msg);
    }

    // 락 디렉터리 해제 (우리가 소유한 경우에만)
    if ($lock_owned && $lock_dir && is_dir($lock_dir)) {
        @rmdir($lock_dir);
    }
});

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

// 중복 실행 방지 락 — flock 대신 "원자적 mkdir" 사용 (일부 호스트/NFS 에서 flock 미동작 문제 회피).
// 살아있는 워커는 루프마다 락 디렉터리 mtime 을 갱신(하트비트)하므로,
// mtime 이 $lock_stale_sec 를 넘으면 죽은/멈춘 워커로 보고 락을 회수한다.
$lock_dir  = __DIR__ . '/newsletter_logs/newsletter_worker.lock.d';
$lock_pidf = __DIR__ . '/newsletter_logs/newsletter_worker.lock'; // 상태표시(UI)용 PID 파일

$lock_owned = @mkdir($lock_dir, 0755);
if (!$lock_owned) {
    $mt = @filemtime($lock_dir);
    $age = ($mt !== false) ? (time() - $mt) : -1;
    if ($age < 0 || $age > $lock_stale_sec) {
        writeLog("stale lock 감지 (age={$age}s) — 회수합니다.");
        @rmdir($lock_dir);
        $lock_owned = @mkdir($lock_dir, 0755);
    }
}
if (!$lock_owned) {
    writeLog("newsletter worker is already running.");
    exit;
}
@file_put_contents($lock_pidf, getmypid() . ' ' . date('Y-m-d H:i:s') . "\n", LOCK_EX);

$selected_queue_ids = newsletterWorkerSelectedQueueIds();
$selected_queue_sql = '';
if (count($selected_queue_ids) > 0) {
    $selected_queue_sql = " AND q.seq_no IN (" . implode(',', $selected_queue_ids) . ")";
    writeLog("Selected queues: " . implode(',', $selected_queue_ids));
}

$worker_loop_selected = count($selected_queue_ids) > 0;

while (true) {
$qry = "SELECT q.*, n.title, n.subject, n.content ,n.main_image
        FROM newsletter_queue q 
        LEFT JOIN newsletter_templates n ON q.newsletter_id = n.seq_no 
        WHERE q.status IN ('WAITING', 'PROCESSING')
          $selected_queue_sql
          AND EXISTS (
              SELECT 1 FROM newsletter_send_details d 
              WHERE d.queue_id = q.seq_no AND d.send_status = 'PENDING'
          )
        ORDER BY CASE WHEN q.status = 'PROCESSING' THEN 0 ELSE 1 END, q.created_at ASC 
        LIMIT 1";

$rst = mysql_query($qry, $dbConn);

if(mysql_num_rows($rst) == 0) {
    writeLog("처리할 큐가 없습니다.");
    exit;
}

$queue = mysql_fetch_assoc($rst);
$queue_id = $queue['seq_no'];
$newsletter_id = $queue['newsletter_id'];
$active_queue_id = $queue_id;

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

$counts = newsletterUpdateQueueProgress($queue_id, intval($queue['total_recipients']));
$sent_count = $counts['sent'];
$failed_count = $counts['failed'];
$total_count = $counts['total'];

writeLog("총 발송 대상: $total_count 명");

$stopped = false;

while($detail = mysql_fetch_assoc($rst_details)) {
    // 락 하트비트: 살아있음을 알려 다른 워커가 stale 로 회수하지 못하게 한다.
    if ($lock_dir) { @touch($lock_dir); }

    // 중지 요청 확인 (현재 큐 상태 체크) — 외부에서 STOPPED 로 변경되면 즉시 발송 중단
    if (newsletterGetQueueStatus($queue_id) === 'STOPPED') {
        $stopped = true;
        writeLog("중지 요청 감지: Queue ID $queue_id 발송을 중단합니다.");
        break;
    }

    $detail_id = $detail['seq_no'];
    $active_detail_id = $detail_id;
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
    $counts = newsletterUpdateQueueProgress($queue_id, $total_count);
    $sent_count = $counts['sent'];
    $failed_count = $counts['failed'];
    $processed = $counts['processed'];
    $progress = $counts['progress'];
    
	$update_mailing = "UPDATE prt_mlist SET 
                         chk_send = '1'
                       WHERE mail_addr = '" . mysql_real_escape_string($to_email) . "'";
    try { mysql_query($update_mailing, $dbConn); } catch (Throwable $e) { writeLog('prt_mlist update skipped: ' . $e->getMessage()); }
	
    // 과부하 방지를 위한 지연 (100ms)
    usleep(150000);
    
    // 20건마다 로그 출력
    if($processed % 20 == 0) {
        writeLog("진행률: $progress% ($processed/$total_count)");
    }

    $active_detail_id = 0;
}

$counts = newsletterUpdateQueueProgress($queue_id, $total_count);
$sent_count = $counts['sent'];
$failed_count = $counts['failed'];

if ($stopped) {
    // 중지됨: 상태를 STOPPED 로 유지하고 진행 카운트만 갱신 (completed_at 미설정 -> 재개 가능)
    mysql_query("UPDATE newsletter_queue SET
                 status = 'STOPPED',
                 sent_count = '$sent_count',
                 failed_count = '$failed_count'
                 WHERE seq_no = '$queue_id'", $dbConn);
    writeLog("발송 중지됨: 성공 {$sent_count}건, 실패 {$failed_count}건, 남은 {$counts['pending']}건 (재개 가능)");
} else {

// 최종 상태 업데이트
if($failed_count == 0) {
    $final_status = 'COMPLETED';
    writeLog("발송 완료: 모든 이메일이 성공적으로 발송되었습니다. (총 {$sent_count}건)");
} else if($sent_count > 0) {
    $final_status = 'COMPLETED';
    writeLog("발송 완료: 성공 {$sent_count}건, 실패 {$failed_count}건");
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

} // end if (!$stopped)

$active_queue_id = 0;
if (!$worker_loop_selected) {
    break;
}
}

writeLog("뉴스레터 백그라운드 워커 완료");
?>
