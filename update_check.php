<?php
include("include/inc_base.php");

header("Content-Type: application/json");

// POST 요청으로 seq_no 받기
if (isset($_POST['seq_no']) && $_POST['seq_no'] !== '') {

    // ✅ PHP5.6 + mysql_* : real_escape_string / $dbConn 사용 금지
    $seqNo = mysql_real_escape_string($_POST['seq_no']);

    // 현재 날짜 (YYYY-MM-DD)
    $currentDate = date("Y-m-d");

    // 업데이트 쿼리
    $updateQuery = "
        UPDATE tour_guide
           SET check_out = 'V',
               check_date = '".$currentDate."'
         WHERE seq_no = '".$seqNo."'
    ";

    $response = array();

    $rst = mysql_query($updateQuery);
    if ($rst) {
        $response['status'] = 'success';
        $response['check_date'] = $currentDate;
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Database update failed: ' . mysql_error();
    }

    echo json_encode($response);
    exit;

} else {

    $response = array();
    $response['status'] = 'error';
    $response['message'] = 'Invalid request: seq_no not provided.';
    echo json_encode($response);
    exit;
}

// ✅ mysql_* 은 보통 close를 안 해도 되지만 원하면 아래 사용 가능
// mysql_close();
?>
