<?php
include "include/inc_base.php";
header("Content-Type: application/json; charset=utf-8");

$cookieKey = defined("MEMLOGIN_ADMIN_PURUN") ? MEMLOGIN_ADMIN_PURUN : "MEMLOGIN_ADMIN_PURUN";
if (empty($_COOKIE[$cookieKey])) {
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

$uploadDir = "uploads/newsletter/";
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        http_response_code(500);
        echo json_encode(array("error" => "Failed to create upload directory"));
        exit;
    }
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

$relativePath = $uploadDir . $filename;
if (!move_uploaded_file($tmpName, $relativePath)) {
    http_response_code(500);
    echo json_encode(array("error" => "Upload failed"));
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
