<?php

$db_host = "XX.XX.XX.XX";
$db_port = "3306";
$db_user = "wincom00";
$db_passwd = "XXXXXXX!";
$db_name = "prtadmindb";
$dbConn = mysqli_connect($db_host, $db_user, $db_passwd, $db_name, $db_port);
if (!$dbConn) {
    die("Don't Connect MySQL Server: " . mysqli_connect_error());
}

mysqli_set_charset($dbConn, 'utf8mb4');

// ===== 간단 헬퍼 =====
function dbq($sql){
    global $dbConn;
    $res = mysqli_query($dbConn, $sql);
    if ($res === false) {
        die("[SQL ERROR] ".mysqli_error($dbConn)."\n-- SQL --\n".$sql);
    }
    return $res;
}

function esc($s){ 
    global $dbConn;
    return mysqli_real_escape_string($dbConn, $s); 
}
?>
