<?php
//ini_set('display_errors', on);
//ini_set('display_startup_errors', 1);
include "include/inc_base.php";
header('Content-Type: application/json; charset=utf-8');


$ids = isset($_POST['ids']) ? $_POST['ids'] : [];
if (!is_array($ids) || count($ids) === 0) {
    echo json_encode(['ok'=>false,'msg'=>'대상이 없습니다']); exit;
}

$clean = [];
//var_dump($ids);
foreach ($ids as $id) {
   
    if (ctype_digit((string)$id)) $clean[] = (int)$id;
}
if (!$clean) { echo json_encode(['ok'=>false,'msg'=>'잘못된 요청']); exit; }

$user = '';
if (isset($user_dbinfo['user_id']) && $user_dbinfo['user_id']!=='') $user = $user_dbinfo['user_id'];
else if (isset($user_dbinfo['email']) && $user_dbinfo['email']!=='') $user = $user_dbinfo['email'];
else $user = 'system';

mysql_query("START TRANSACTION", $dbConn);

$updated = 0;
// mysql_* 환경에선 안전하게 1건씩 업데이트
foreach ($clean as $seq) {
    $seq = (int)$seq;
    $q = "
        UPDATE payment_history
        SET conf_p='2',
            conf_date=NOW()
        WHERE seq_no=$seq AND conf_p <> '2'
    ";
	//echo $q."<br />";
    $ok = mysql_query($q, $dbConn);
    if ($ok && mysql_affected_rows($dbConn) > 0) $updated++;
}

if ($updated >= 0) {
    mysql_query("COMMIT", $dbConn);
    echo json_encode(['ok'=>true,'updated'=>$updated]);
} else {
    mysql_query("ROLLBACK", $dbConn);
    echo json_encode(['ok'=>false,'msg'=>'DB 오류']);
}
