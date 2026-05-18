<?php
include "include/inc_base.php";
if (file_exists("include/remote_upload.php")) {
    require_once "include/remote_upload.php";
}
header("Content-Type: application/json; charset=utf-8");

if (empty($_COOKIE["MEMLOGIN_ADMIN_PURUN"])) {
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
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$safeBaseName = preg_replace("/[^\p{L}\p{N}_-]/u", "_", pathinfo($originalName, PATHINFO_FILENAME));
$safeBaseName = trim($safeBaseName, "_");
if ($safeBaseName === "") {
    $safeBaseName = "image";
}

$filename = $safeBaseName . "_" . date("Ymd_His") . "_" . mt_rand(1000, 9999);
if ($extension !== "") {
    $filename .= "." . $extension;
}

$relativePath = "uploads/" . $filename;
if (!move_uploaded_file($tmpName, $relativePath)) {
    http_response_code(500);
    echo json_encode(array("error" => "upload failed"));
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
