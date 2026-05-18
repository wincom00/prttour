<?php
include "../include/inc_base.php";

header('Content-Type: application/json');

if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] == "") {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

//$seq_no = $_POST['seq_no'] ?? '';
$seq_no = isset($_GET['seq_no']) ? $_GET['seq_no'] : '';
$region = isset($_GET['region']) ? $_GET['region'] : '';
if(!$seq_no) {
    echo json_encode(['success' => false, 'message' => '뉴스레터 ID가 없습니다.']);
    exit;
}

// 뉴스레터 정보 조회
$qry = "SELECT * FROM newsletter_templates WHERE seq_no = '$seq_no' AND send_status = 'DRAFT'";
$rst = mysql_query($qry, $dbConn);
$newsletter = mysql_fetch_assoc($rst);

if(!$newsletter) {
    echo json_encode(['success' => false, 'message' => '발송할 수 없는 뉴스레터입니다.']);
    exit;
}
// 지역별 수신자 조회
$where_clause = "WHERE chk_sub = '0' AND chk_send = '0'";
    
switch($region) {
	case '본사':
		// 본사: 뉴저지, 뉴욕, 코네티컷, 펜실베니아 등 동부 지역
		$where_clause .= " AND (area='all' || area='head' || area='카카오')";
		break;
	case '서부':
		// 서부: 캘리포니아, 네바다, 오레곤, 워싱턴 등 서부 지역
		$where_clause .= " AND (area='las' || area='la')";
		break;
	case '전지역':
	default:
		// 전지역: 모든 고객
		break;
}
//(area='all' || area='head' || area='카카오') && 
// 메일링 리스트 조회 (구독자만)
$qry_mail = "SELECT COUNT(*) as total FROM prt_mlist $where_clause";
$rst_mail = mysql_query($qry_mail, $dbConn);
$total_row = mysql_fetch_assoc($rst_mail);

if($total_row['total'] == 0) {
    echo json_encode(['success' => false, 'message' => '발송할 구독자가 없습니다.']);
    exit;
}

// 큐에 작업 등록
$insert_queue = "INSERT INTO newsletter_queue 
                (newsletter_id, total_recipients, created_by, created_at) 
                VALUES 
                ('$seq_no', '{$total_row['total']}', '{$user_dbinfo['userid']}', NOW())";

if(mysql_query($insert_queue, $dbConn)) {
    $queue_id = mysql_insert_id();
    
    // 개별 발송 목록 등록
    $qry_recipients = "SELECT mail_addr, m_name FROM prt_mlist $where_clause";
    $rst_recipients = mysql_query($qry_recipients, $dbConn);
    
    while($recipient = mysql_fetch_assoc($rst_recipients)) {
        $email = mysql_real_escape_string($recipient['mail_addr']);
        $name = mysql_real_escape_string($recipient['m_name']);
        
        $insert_detail = "INSERT INTO newsletter_send_details 
                         (queue_id, recipient_email, recipient_name) 
                         VALUES 
                         ('$queue_id', '$email', '$name')";
        mysql_query($insert_detail, $dbConn);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => '뉴스레터가 발송 큐에 등록되었습니다. 백그라운드에서 발송이 진행됩니다.',
        'queue_id' => $queue_id
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => '큐 등록 중 오류가 발생했습니다.'
    ]);
}
?>