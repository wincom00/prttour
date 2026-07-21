<?php
include "include/inc_base.php";
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

// TinyMCE uses "file". Keep "upload" for backward compatibility.
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

// PHP 업로드 단계 오류를 먼저 확인한다 (용량 초과/임시폴더 없음 등).
$uploadErr = isset($_FILES[$fileField]["error"]) ? $_FILES[$fileField]["error"] : UPLOAD_ERR_NO_FILE;
if ($uploadErr !== UPLOAD_ERR_OK) {
    $errMsgs = array(
        UPLOAD_ERR_INI_SIZE   => "파일이 서버 허용 용량(upload_max_filesize)을 초과했습니다.",
        UPLOAD_ERR_FORM_SIZE  => "파일이 폼 허용 용량을 초과했습니다.",
        UPLOAD_ERR_PARTIAL    => "파일이 일부만 전송되었습니다.",
        UPLOAD_ERR_NO_FILE    => "전송된 파일이 없습니다.",
        UPLOAD_ERR_NO_TMP_DIR => "서버에 임시 폴더가 없습니다.",
        UPLOAD_ERR_CANT_WRITE => "디스크에 파일을 쓸 수 없습니다.",
        UPLOAD_ERR_EXTENSION  => "PHP 확장에 의해 업로드가 중단되었습니다."
    );
    $msg = isset($errMsgs[$uploadErr]) ? $errMsgs[$uploadErr] : ("업로드 오류 코드 " . $uploadErr);
    http_response_code(400);
    echo json_encode(array("error" => $msg));
    exit;
}

// 경로는 항상 이 스크립트 위치(__DIR__ = 도큐먼트 루트) 기준 절대경로로 고정한다.
// (Apache 로 실행될 때 PHP 의 CWD 가 프로젝트 루트가 아니어서 상대경로가 실패하는 문제 방지)
$uploadDir = "uploads/newsletter/"; // 반환 URL 에 쓰는 상대경로
$uploadAbsDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "newsletter" . DIRECTORY_SEPARATOR;
if (!is_dir($uploadAbsDir)) {
    if (!mkdir($uploadAbsDir, 0755, true) && !is_dir($uploadAbsDir)) {
        http_response_code(500);
        echo json_encode(array("error" => "업로드 폴더를 만들 수 없습니다: " . $uploadAbsDir));
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
$extension = $__allowedImg[$__imgType];
$safeBaseName = preg_replace("/[^\p{L}\p{N}_-]/u", "_", pathinfo(basename($originalName), PATHINFO_FILENAME));
$safeBaseName = trim($safeBaseName, "_");
if ($safeBaseName === "") {
    $safeBaseName = "image";
}

$filename = $safeBaseName . "_" . date("Ymd_His") . "_" . mt_rand(1000, 9999) . "." . $extension;

$relativePath = $uploadDir . $filename;          // 반환 URL 용 (웹 경로)
$absolutePath = $uploadAbsDir . $filename;        // 실제 저장 경로 (파일시스템)
if (!move_uploaded_file($tmpName, $absolutePath)) {
    http_response_code(500);
    echo json_encode(array("error" => "파일 저장에 실패했습니다. (대상 폴더 쓰기 권한 확인 필요: " . $uploadAbsDir . ")"));
    exit;
}

$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "www.myprt.org";
$absoluteUrl = $scheme . "://" . $host . "/" . $relativePath;

echo json_encode(array(
    "location" => $absoluteUrl,
    "url" => $absoluteUrl,
    "uploaded" => 1,
    "fileName" => $filename
));
?>
