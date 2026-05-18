<?php
include "../include/inc_base.php";

header('Content-Type: application/json');

if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] == "") {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$seq_no = isset($_GET['seq_no']) ? $_GET['seq_no'] : '';

if(!$seq_no) {
    echo json_encode(['success' => false, 'message' => '뉴스레터 ID가 없습니다.']);
    exit;
}

// 발송완료된 뉴스레터는 삭제 불가
$check_qry = "SELECT send_status FROM newsletter_templates WHERE seq_no = '$seq_no'";
$check_rst = mysql_query($check_qry, $dbConn);
$check_row = mysql_fetch_assoc($check_rst);

if(!$check_row) {
    echo json_encode(['success' => false, 'message' => '뉴스레터를 찾을 수 없습니다.']);
    exit;
}

if($check_row['send_status'] == 'SENT') {
	
	// 개별발송 삭제 처리
	$delete_qry = "DELETE FROM newsletter_send_details WHERE queue_id = '$seq_no' AND send_status = 'SENT'";

	mysql_query($delete_qry, $dbConn);
    echo json_encode(['success' => true, 'message' => '개별발송 삭제 처리는 완료되었으나 발송완료된 원래뉴스레터는 삭제할 수 없습니다.']);
    exit;
}

// 삭제 처리
$delete_qry = "DELETE FROM newsletter_templates WHERE seq_no = '$seq_no' AND send_status = 'DRAFT'";

if(mysql_query($delete_qry, $dbConn)) {
    echo json_encode(['success' => true, 'message' => '삭제되었습니다.']);
	exit;
} 

?>