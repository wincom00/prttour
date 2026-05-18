<?php
require_once __DIR__ . "/mysql_compat.php";

// 서버 PHP 시간대 설정
date_default_timezone_set('America/New_York');

// ===== DB 연결 설정 =====
// 이 파일을 dbconn.php 로 복사한 뒤 실제 값으로 채워서 사용하세요.
// dbconn.php 는 .gitignore 로 제외되어 있습니다.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 0);

$db_host   = "YOUR_DB_HOST:3306";
$db_port   = "3306";
$db_user   = "YOUR_DB_USER";
$db_passwd = "YOUR_DB_PASSWORD";
$db_name   = "YOUR_DB_NAME";

$dbConn = mysql_connect($db_host, $db_user, $db_passwd) or die ("Don't Connect MySQL Server");
mysql_select_db($db_name);

// DB 시간대 설정 (America/New_York - 서머타임 자동 반영)
$ny_offset = (new DateTime('now', new DateTimeZone('America/New_York')))->format('P');
mysql_query("SET time_zone = '{$ny_offset}'");
mysql_query("SET NAMES utf8mb4");
mysql_query("SET SESSION character_set_results = utf8mb4");
mysql_query("SET SESSION character_set_client   = utf8mb4");
mysql_query("SET SESSION character_set_connection = utf8mb4");

// ===== 간단 헬퍼 =====
function dbq($sql){
	global $dbConn;
    $res = mysql_query($sql,$dbConn);
    if ($res == false) {
        die("[SQL ERROR] ".mysql_error()."\n-- SQL --\n".$sql);
    }
    return $res;
}
function esc($s) {
    if (function_exists('mysql_real_escape_string')) return mysql_real_escape_string($s);
    return addslashes($s);
}
?>
