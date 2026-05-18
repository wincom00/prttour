<?php
include "include/inc_base.php";
if (file_exists("include/remote_upload.php")) {
    require_once "include/remote_upload.php";
}
if (!function_exists('remote_sync_file')) {
    function remote_sync_file($_p, $_f) { return false; }
    function remote_detect_folder($_p) { return null; }

    function remote_ftp_test(&$e='') { return true; }
}
header("Content-Type: application/json; charset=utf-8");

$cookieKey = defined("MEMLOGIN_ADMIN_PURUN") ? MEMLOGIN_ADMIN_PURUN : "MEMLOGIN_ADMIN_PURUN";
if (empty($_COOKIE[$cookieKey])) {
    http_response_code(403);
    echo json_encode(array("error" => "Forbidden"));
    exit;
}

if (!isset($_FILES["file"]) && !isset($_FILES["upload"])) {
    http_response_code(400);
    echo json_encode(array("error" => "No file"));
    exit;
}

$fileField = isset($_FILES["file"]) ? "file" : "upload";
$uploadDir = "upload/";

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        http_response_code(500);
        echo json_encode(array("error" => "Failed to create upload directory"));
        exit;
    }
}

$originalName = $_FILES[$fileField]["name"];
$tmpName = $_FILES[$fileField]["tmp_name"];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$baseName = pathinfo($originalName, PATHINFO_FILENAME);

// 원본 파일명 그대로 유지, 중복 시 번호 추가
$filename = $baseName . ($ext !== "" ? "." . $ext : "");
$counter = 1;
while (file_exists($uploadDir . $filename)) {
    $filename = $baseName . "(" . $counter . ")" . ($ext !== "" ? "." . $ext : "");
    $counter++;
}

$filePath = $uploadDir . $filename;
if (!move_uploaded_file($tmpName, $filePath)) {
    http_response_code(500);
    echo json_encode(array("error" => "Upload failed"));
    exit;
}

// 현재 서버가 FTP 호스트와 같으면 FTP 불필요 (로컬 저장 = 원격 저장)
$_isSameServer = function_exists('remote_is_primary_host') ? remote_is_primary_host() : false;

if (!$_isSameServer) {
    $_rf = remote_detect_folder($filePath);
    if ($_rf) {
        $ftpErr = '';
        $syncOk = remote_sync_file($filePath, $_rf, $ftpErr);
        if (!$syncOk) {
            error_log("[FTP] " . $ftpErr);
            @unlink($filePath);
            http_response_code(500);
            echo json_encode(array("error" => "FTP 실패: " . $ftpErr));
            exit;
        }
    }
}

$publicPath = '/' . ltrim(str_replace('\\', '/', $filePath), '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $scheme = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0];
}
$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'myprt.biz');
$absoluteUrl = $scheme . '://' . $host . $publicPath;
$primaryImageUrl = 'https://' . (defined('FTP_PRIMARY_DOMAIN') ? FTP_PRIMARY_DOMAIN : 'myprt.org') . $publicPath;

echo json_encode(array(
    "location" => $primaryImageUrl,
    "url"      => $primaryImageUrl,
    "local_url" => $absoluteUrl,
    "path"     => $publicPath,
    "uploaded" => 1,
    "fileName" => $filename
));
?>
