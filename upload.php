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

// TinyMCE sends file in "file"; keep "upload" for compatibility.
$fileField = null;
if (isset($_FILES["file"])) {
    $fileField = "file";
} elseif (isset($_FILES["upload"])) {
    $fileField = "upload";
}

if ($fileField === null) {
    http_response_code(400);
    echo json_encode(array("error" => "No file"));
    exit;
}

if (!is_dir("upload")) {
    mkdir("upload", 0755, true);
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

$relativePath = "upload/" . $filename;
if (!move_uploaded_file($tmpName, $relativePath)) {
    http_response_code(500);
    echo json_encode(array("error" => "Upload failed"));
    exit;
}

// 원격 서버 동기화
$_rf = remote_detect_folder($relativePath); if ($_rf) remote_sync_file($relativePath, $_rf);

$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "www.myprt.org";
$absoluteUrl = $scheme . "://" . $host . "/" . $relativePath;

echo json_encode(array(
    // TinyMCE required field
    "location" => $absoluteUrl,
    // Backward-compatible fields
    "fileName" => $filename,
    "uploaded" => 1,
    "url" => $absoluteUrl,
    "width" => "auto"
));
?>
