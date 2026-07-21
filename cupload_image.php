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

// 인증: 쿠키 존재만 확인하지 말고 member_list 로 실제 회원인지 대조 (위조 쿠키 차단)
$__rawCookie  = isset($_COOKIE["MEMLOGIN_ADMIN_PURUN"]) ? $_COOKIE["MEMLOGIN_ADMIN_PURUN"] : "";
$__authInfo   = ($__rawCookie !== "") ? getinfo_Member($__rawCookie) : null;
$__authUid    = (is_array($__authInfo) && !empty($__authInfo["user_id"])) ? $__authInfo["user_id"] : "";
$__authMember = ($__authUid !== "") ? getinfo_dbMember($__authUid) : null;
if (empty($__authMember) || empty($__authMember["userid"])) {
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

// 콘텐츠 검증: 실제 이미지만 허용하고 확장자는 파일 내용에서 도출 (웹쉘 업로드 차단)
$__allowedImg = array(
    IMAGETYPE_JPEG => "jpg", IMAGETYPE_PNG => "png", IMAGETYPE_GIF => "gif",
    IMAGETYPE_BMP => "bmp", IMAGETYPE_WEBP => "webp",
);
$__imgInfo = @getimagesize($tmpName);
$__imgType = (is_array($__imgInfo) && isset($__imgInfo[2])) ? (int) $__imgInfo[2] : 0;
if (!isset($__allowedImg[$__imgType])) {
    http_response_code(400);
    echo json_encode(array("error" => "이미지 파일(jpg, png, gif, bmp, webp)만 업로드할 수 있습니다."));
    exit;
}
$ext = $__allowedImg[$__imgType];
$baseName = preg_replace("/[^\p{L}\p{N}_-]/u", "_", pathinfo(basename($originalName), PATHINFO_FILENAME));
$baseName = trim($baseName, "_");
if ($baseName === "") {
    $baseName = "image";
}

// 원본 파일명(정제) 유지, 중복 시 번호 추가
$filename = $baseName . "." . $ext;
$counter = 1;
while (file_exists($uploadDir . $filename)) {
    $filename = $baseName . "(" . $counter . ")." . $ext;
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
