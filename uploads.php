<?php
include "include/inc_base.php";
if (file_exists("include/remote_upload.php")) {
    require_once "include/remote_upload.php";
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

// TinyMCE sends file in "file"; keep "uploads" for compatibility.
$fileField = null;
if (isset($_FILES["file"])) {
    $fileField = "file";
} elseif (isset($_FILES["uploads"])) {
    $fileField = "uploads";
}

if ($fileField === null) {
    http_response_code(400);
    echo json_encode(array("error" => "No file"));
    exit;
}

if (!is_dir("uploads")) {
    mkdir("uploads", 0755, true);
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
$extension = $__allowedImg[$__imgType];
$safeBaseName = preg_replace("/[^\p{L}\p{N}_-]/u", "_", pathinfo(basename($originalName), PATHINFO_FILENAME));
$safeBaseName = trim($safeBaseName, "_");
if ($safeBaseName === "") {
    $safeBaseName = "image";
}

$filename = $safeBaseName . "_" . date("Ymd_His") . "_" . mt_rand(1000, 9999) . "." . $extension;

$relativePath = "uploads/" . $filename;
if (!move_uploaded_file($tmpName, $relativePath)) {
    http_response_code(500);
    echo json_encode(array("error" => "upload failed"));
    exit;
}

// 원격 서버 동기화: 실패하면 로컬 파일도 지워서 양쪽 상태를 맞춘다
$_rf = function_exists("remote_detect_folder") ? remote_detect_folder($relativePath) : null;
if ($_rf) {
    $ftpErr = "";
    if (!remote_sync_file($relativePath, $_rf, $ftpErr)) {
        error_log("[FTP] " . $ftpErr);
        @unlink($relativePath);
        http_response_code(500);
        echo json_encode(array("error" => "FTP 실패: " . $ftpErr));
        exit;
    }
}

// 본문에 저장되는 URL은 양쪽 서버에서 똑같이 열리도록 대표 도메인 기준으로 반환
$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "www.myprt.org";
$localUrl = $scheme . "://" . $host . "/" . $relativePath;
$absoluteUrl = "https://" . (defined("FTP_PRIMARY_DOMAIN") ? FTP_PRIMARY_DOMAIN : "myprt.org") . "/" . $relativePath;

echo json_encode(array(
    // TinyMCE required field
    "location" => $absoluteUrl,
    // Backward-compatible fields
    "fileName" => $filename,
    "uploaded" => 1,
    "url" => $absoluteUrl,
    "local_url" => $localUrl,
    "width" => "auto"
));
?>
