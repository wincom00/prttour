<?php
// email-sys/newsletter_queue_status_data.php
// 발송 큐 상태를 JSON으로 반환 (페이지 새로고침 없이 실시간 갱신용)
include "../include/inc_base.php";

header('Content-Type: application/json; charset=utf-8');

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

$qry = "SELECT q.*, n.subject,
               (SELECT COUNT(*) FROM newsletter_send_details d WHERE d.queue_id = q.seq_no AND d.send_status = 'PENDING') AS pending_count
        FROM newsletter_queue q
        LEFT JOIN newsletter_templates n ON q.newsletter_id = n.seq_no
        ORDER BY q.created_at DESC
        LIMIT 20";
$rst = mysql_query($qry, $dbConn);

$rows = array();
$has_processing = false;
while ($row = mysql_fetch_assoc($rst)) {
    if ($row['status'] == 'WAITING' || $row['status'] == 'PROCESSING') {
        $has_processing = true;
    }
    $rows[] = array(
        'seq_no'           => intval($row['seq_no']),
        'status'           => $row['status'],
        'progress_percent' => $row['progress_percent'],
        'sent_count'       => intval($row['sent_count']),
        'failed_count'     => intval($row['failed_count']),
        'pending_count'    => intval($row['pending_count']),
        'started_at'       => $row['started_at'] ? date('m-d H:i', strtotime($row['started_at'])) : '-',
        'completed_at'     => $row['completed_at'] ? date('m-d H:i', strtotime($row['completed_at'])) : '-'
    );
}

echo json_encode(array(
    'success'        => true,
    'has_processing' => $has_processing,
    'rows'           => $rows
));
?>
