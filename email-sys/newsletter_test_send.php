<?php
include "../include/inc_base.php";
if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] == "") {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}
if ($_POST['seq_no']) {
    $seq_no = (int)$_POST['seq_no'];
    $test_emails = trim($_POST['test_emails']);
    
    try {
        // 뉴스레터 정보 조회
        $qry = "SELECT * FROM newsletter_templates WHERE seq_no = $seq_no AND send_status = 'DRAFT'";
        $result = mysql_query($qry, $dbConn);
        
        if (!$result) {
            throw new Exception('데이터베이스 쿼리 오류: ' . mysql_error($dbConn));
        }
        
        $newsletter = mysql_fetch_assoc($result);
        
        if (!$newsletter) {
            throw new Exception('뉴스레터를 찾을 수 없습니다.');
        }
        
        $test_email_list = array();
        
        // 입력된 이메일 주소들 처리
        if ($test_emails) {
            $emails = preg_split('/[,\n]/', $test_emails);
            foreach ($emails as $email) {
                $email = trim($email);
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $test_email_list[] = $email;
                }
            }
        }
        
        
        if (empty($test_email_list)) {
            throw new Exception('발송할 유효한 이메일 주소가 없습니다.');
        }
        
        if (count($test_email_list) > 10) {
            throw new Exception('테스트 발송은 최대 10개의 이메일까지만 가능합니다.');
        }
        
        // 테스트 발송 실행
        $sent_count = 0;
        $failed_emails = array();
        
                
        // 테스트 발송 표시를 위한 제목 수정
        $test_subject = "[테스트] " . $newsletter['subject'];
        
        // 테스트 발송 안내 추가
        $test_content = '<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin-bottom: 20px; border-radius: 5px;">';
        $test_content .= '<strong>⚠️ 이것은 테스트 발송입니다</strong><br>';
        $test_content .= '발송일시: ' . date('Y-m-d H:i:s') . '<br>';
        $test_content .= '발송자: ' . $user_dbinfo['kor_name'] . ' (' . $user_dbinfo['email'] . ')';
        $test_content .= '</div>';
        $test_content .= $newsletter['content'];
        
        foreach ($test_email_list as $email) {
            if (mailsend_a($email, $test_subject, $test_content, $attachment1, $attachment2)) {
                $sent_count++;
            } else {
                $failed_emails[] = $email;
            }
        }
        
        // 테스트 발송 로그 기록 (옵션)
                
        $response = array(
            'success' => true,
            'sent_count' => $sent_count,
            'total_count' => count($test_email_list)
        );
        
        if (!empty($failed_emails)) {
            $response['failed_emails'] = $failed_emails;
            $response['message'] = $sent_count . '개 발송 완료, ' . count($failed_emails) . '개 실패';
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode(array(
            'success' => false,
            'message' => $e->getMessage()
        ));
    }
} else {
    echo json_encode(array(
        'success' => false,
        'message' => '잘못된 요청입니다.'
    ));
}
?>