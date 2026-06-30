<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
mbx_require_admin_file('include/inc_base.php');
require_once dirname(__DIR__) . '/lib/common.php';

mbx_require_api_auth();

function mbx_b64url_decode($value)
{
    $value = (string)$value;
    $pad = strlen($value) % 4;
    if ($pad) {
        $value .= str_repeat('=', 4 - $pad);
    }
    return base64_decode(strtr($value, '-_', '+/'), true);
}

function mbx_image_proxy_forbidden_host($host)
{
    $host = strtolower(trim((string)$host));
    if ($host === '' || $host === 'localhost') {
        return true;
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    return false;
}

function mbx_fetch_remote_image($url, &$mime)
{
    $mime = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 mailbox-image-proxy');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $data = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $mime = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($data !== false && $code >= 200 && $code < 400) {
            return $data;
        }
    }

    $ctx = stream_context_create(array(
        'http' => array(
            'timeout' => 15,
            'header' => "User-Agent: Mozilla/5.0 mailbox-image-proxy\r\n",
        ),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
        ),
    ));
    $data = @file_get_contents($url, false, $ctx);
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (stripos($h, 'Content-Type:') === 0) {
                $mime = trim(substr($h, 13));
                break;
            }
        }
    }
    return $data;
}

function mbx_detect_image_mime($data, $fallback)
{
    $fallback = strtolower(trim((string)$fallback));
    $fallback = preg_replace('/;.*/', '', $fallback);
    if (strncmp((string)$data, "\xFF\xD8\xFF", 3) === 0) {
        return 'image/jpeg';
    }
    if (strncmp((string)$data, "\x89PNG\r\n\x1A\n", 8) === 0) {
        return 'image/png';
    }
    if (strncmp((string)$data, 'GIF87a', 6) === 0 || strncmp((string)$data, 'GIF89a', 6) === 0) {
        return 'image/gif';
    }
    if (strncmp((string)$data, 'RIFF', 4) === 0 && substr((string)$data, 8, 4) === 'WEBP') {
        return 'image/webp';
    }
    if (strncmp(ltrim((string)$data), '<svg', 4) === 0) {
        return 'image/svg+xml';
    }
    return strpos($fallback, 'image/') === 0 ? $fallback : '';
}

try {
    $encoded = isset($_GET['u']) ? (string)$_GET['u'] : '';
    $url = mbx_b64url_decode($encoded);
    if ($url === false || !preg_match('/^https?:\/\//i', $url)) {
        throw new RuntimeException('Invalid image URL.');
    }
    $parts = parse_url($url);
    if (!$parts || empty($parts['host']) || mbx_image_proxy_forbidden_host($parts['host'])) {
        throw new RuntimeException('Blocked image URL.');
    }

    $mime = '';
    $data = mbx_fetch_remote_image($url, $mime);
    if ($data === false || $data === '') {
        throw new RuntimeException('Image fetch failed.');
    }
    $mime = mbx_detect_image_mime($data, $mime);
    if ($mime === '') {
        throw new RuntimeException('Remote file is not an image.');
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mime);
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . strlen($data));
    echo $data;
} catch (Exception $e) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
}
?>
