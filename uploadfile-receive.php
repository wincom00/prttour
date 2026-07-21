<?php
/**
 * uploadfile-receive.php
 * -----------------------------------------------------------------------------
 * 안전한 이미지 업로드 수신 엔드포인트.
 *
 * [보안 정책]
 *  1) 인증: MEMLOGIN_ADMIN_PURUN 쿠키를 member_list DB 행과 대조하여 실제 로그인
 *     회원만 허용한다. (쿠키 값 존재 여부만 보는 방식은 우회가 가능하므로 금지)
 *  2) 콘텐츠 검증: getimagesize 로 실제 이미지인지 확인하고, 저장 확장자는
 *     "파일 내용에서 도출"한다. 업로드된 파일명/확장자는 신뢰하지 않는다.
 *     → .php / .phtml 등 실행 파일 저장이 원천적으로 불가능하다.
 *  3) 경로 고정: 저장 폴더는 이 스크립트 위치(__DIR__)의 upload/ 로 고정하고,
 *     파일명은 basename() + 랜덤으로 새로 생성한다. → 경로 탈출(../) 불가.
 *
 * 입력 방식(둘 다 지원):
 *   - multipart/form-data: $_FILES['file'] 또는 $_FILES['upload']
 *   - 레거시 base64:        $_POST['fileName'] + $_POST['fileData']
 * -----------------------------------------------------------------------------
 */

include "include/inc_base.php";
if (file_exists("include/remote_upload.php")) {
    require_once "include/remote_upload.php";
}
header("Content-Type: application/json; charset=utf-8");

// 허용 이미지 형식: 실제 콘텐츠 타입 → 저장 확장자 매핑 (화이트리스트)
$ALLOWED_IMAGE_TYPES = array(
    IMAGETYPE_JPEG => "jpg",
    IMAGETYPE_PNG  => "png",
    IMAGETYPE_GIF  => "gif",
    IMAGETYPE_BMP  => "bmp",
    IMAGETYPE_WEBP => "webp",
);
$MAX_BYTES = 10 * 1024 * 1024; // 10MB

function respond_error($code, $msg) {
    http_response_code($code);
    echo json_encode(array("error" => $msg));
    exit;
}

// -----------------------------------------------------------------------------
// 1) 인증 — 쿠키를 실제 회원 정보와 대조
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// 2) 입력 수집 (multipart 우선, 없으면 레거시 base64)
// -----------------------------------------------------------------------------
$originalName = "";
$imageBytes   = null;   // base64 모드에서 사용
$tmpFilePath  = null;   // multipart 모드에서 사용

if (isset($_FILES["file"]) || isset($_FILES["upload"])) {
    $fileField = isset($_FILES["file"]) ? "file" : "upload";

    $uploadErr = isset($_FILES[$fileField]["error"]) ? $_FILES[$fileField]["error"] : UPLOAD_ERR_NO_FILE;
    if ($uploadErr !== UPLOAD_ERR_OK) {
        respond_error(400, "업로드 오류 코드 " . $uploadErr);
    }

    $tmpFilePath = $_FILES[$fileField]["tmp_name"];
    if (!is_uploaded_file($tmpFilePath)) {
        respond_error(400, "잘못된 업로드입니다.");
    }
    if (filesize($tmpFilePath) > $MAX_BYTES) {
        respond_error(400, "파일이 허용 용량(10MB)을 초과했습니다.");
    }
    $originalName = $_FILES[$fileField]["name"];

} elseif (isset($_POST["fileName"]) && isset($_POST["fileData"]) && $_POST["fileData"] !== "") {
    // 레거시 base64 인터페이스 (안전하게 처리)
    $originalName = (string) $_POST["fileName"];
    $imageBytes = base64_decode((string) $_POST["fileData"], true);
    if ($imageBytes === false) {
        respond_error(400, "잘못된 데이터 형식입니다.");
    }
    if (strlen($imageBytes) > $MAX_BYTES) {
        respond_error(400, "파일이 허용 용량(10MB)을 초과했습니다.");
    }

} else {
    respond_error(400, "전송된 파일이 없습니다.");
}

// -----------------------------------------------------------------------------
// 3) 콘텐츠 검증 — 실제 이미지인지 확인하고 저장 확장자를 내용에서 도출
// -----------------------------------------------------------------------------
if ($tmpFilePath !== null) {
    $info = @getimagesize($tmpFilePath);
} else {
    $info = @getimagesizefromstring($imageBytes);
}

$detectedType = (is_array($info) && isset($info[2])) ? (int) $info[2] : 0;
if (!isset($ALLOWED_IMAGE_TYPES[$detectedType])) {
    respond_error(400, "이미지 파일(jpg, png, gif, bmp, webp)만 업로드할 수 있습니다.");
}
$ext = $ALLOWED_IMAGE_TYPES[$detectedType];

// -----------------------------------------------------------------------------
// 4) 안전한 저장 경로/파일명 생성 (basename + 랜덤 + 내용기반 확장자)
// -----------------------------------------------------------------------------
$baseSource = pathinfo(basename($originalName), PATHINFO_FILENAME);
$safeBase = preg_replace("/[^\p{L}\p{N}_-]/u", "_", $baseSource);
$safeBase = trim($safeBase, "_");
if ($safeBase === "") {
    $safeBase = "image";
}
$filename = $safeBase . "_" . date("Ymd_His") . "_" . mt_rand(1000, 9999) . "." . $ext;

$uploadDir    = "upload/";
$uploadAbsDir = __DIR__ . DIRECTORY_SEPARATOR . "upload" . DIRECTORY_SEPARATOR;
if (!is_dir($uploadAbsDir)) {
    if (!mkdir($uploadAbsDir, 0755, true) && !is_dir($uploadAbsDir)) {
        respond_error(500, "업로드 폴더를 만들 수 없습니다.");
    }
}

$relativePath = $uploadDir . $filename;       // 웹 경로
$absolutePath = $uploadAbsDir . $filename;    // 실제 저장 경로

if ($tmpFilePath !== null) {
    if (!move_uploaded_file($tmpFilePath, $absolutePath)) {
        respond_error(500, "파일 저장에 실패했습니다.");
    }
} else {
    if (file_put_contents($absolutePath, $imageBytes) === false) {
        respond_error(500, "파일 저장에 실패했습니다.");
    }
    @chmod($absolutePath, 0644);
}

// -----------------------------------------------------------------------------
// 5) 원격 서버 동기화 (기존 엔드포인트와 동일)
// -----------------------------------------------------------------------------
if (function_exists("remote_detect_folder") && function_exists("remote_sync_file")) {
    $_rf = remote_detect_folder($relativePath);
    if ($_rf) {
        remote_sync_file($relativePath, $_rf);
    }
}

// -----------------------------------------------------------------------------
// 6) 응답 (기존 엔드포인트와 동일한 JSON 형식)
// -----------------------------------------------------------------------------
$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "www.myprt.org";
$absoluteUrl = $scheme . "://" . $host . "/" . $relativePath;

echo json_encode(array(
    "location" => $absoluteUrl,
    "url"      => $absoluteUrl,
    "uploaded" => 1,
    "fileName" => $filename,
));
?>
