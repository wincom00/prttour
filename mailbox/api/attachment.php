<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
mbx_require_admin_file('include/inc_base.php');
require_once dirname(__DIR__) . '/lib/common.php';

mbx_require_api_auth();

function mbx_decode_attachment_data($data, $encoding, $mime)
{
    $decoded = MimeParser::decodeTransferEncoding($data, $encoding);
    if (trim((string)$encoding) !== '' || $decoded !== (string)$data) {
        return mbx_fix_image_binary($decoded, $mime);
    }

    $mime = strtolower((string)$mime);
    if (strpos($mime, 'text/') === 0) {
        return (string)$data;
    }
    $compact = preg_replace('/\s+/', '', (string)$data);
    if ($compact !== '' && preg_match('/^[A-Za-z0-9+\/=]+$/', $compact) && strlen($compact) % 4 === 0) {
        $guess = base64_decode($compact, true);
        if ($guess !== false) {
            return mbx_fix_image_binary($guess, $mime);
        }
    }
    return mbx_fix_image_binary((string)$data, $mime);
}

function mbx_fix_image_binary($data, $mime)
{
    $mime = strtolower((string)$mime);
    if (strpos($mime, 'image/') !== 0) {
        return (string)$data;
    }
    $data = (string)$data;
    if (mbx_is_image_binary($data)) {
        return $data;
    }
    $qp = quoted_printable_decode($data);
    if ($qp !== $data && mbx_is_image_binary($qp)) {
        return $qp;
    }
    $compact = preg_replace('/\s+/', '', $data);
    if ($compact !== '' && preg_match('/^[A-Za-z0-9+\/=]+$/', $compact) && strlen($compact) % 4 === 0) {
        $b64 = base64_decode($compact, true);
        if ($b64 !== false && mbx_is_image_binary($b64)) {
            return $b64;
        }
    }
    return $data;
}

function mbx_is_image_binary($data)
{
    $data = (string)$data;
    if (strncmp($data, "\xFF\xD8\xFF", 3) === 0) {
        return true;
    }
    if (strncmp($data, "\x89PNG\r\n\x1A\n", 8) === 0) {
        return true;
    }
    if (strncmp($data, "GIF87a", 6) === 0 || strncmp($data, "GIF89a", 6) === 0) {
        return true;
    }
    if (strncmp($data, "RIFF", 4) === 0 && substr($data, 8, 4) === "WEBP") {
        return true;
    }
    if (strncmp(ltrim($data), '<svg', 4) === 0) {
        return true;
    }
    return false;
}

// Content-ID 를 비교용으로 정규화한다(꺾쇠·공백 제거, 소문자). 인라인 이미지는
// 파일명 없이 Content-ID(cid:)로만 식별되는 경우가 많아 이 키가 결정적이다.
function mbx_normalize_att_cid($cid)
{
    return strtolower(trim(trim((string)$cid), '<> '));
}

function mbx_find_attachment_in_raw_message($raw, array $att)
{
    if ((string)$raw === '') {
        return null;
    }
    $parsed = MimeParser::parseMessage($raw);
    if (empty($parsed['attachments'])) {
        return null;
    }
    $wantedPart = (string)$att['part_no'];
    $wantedName = (string)$att['filename'];
    $wantedCid = mbx_normalize_att_cid(isset($att['content_id']) ? $att['content_id'] : '');
    foreach ($parsed['attachments'] as $candidate) {
        if ($wantedPart !== '' && isset($candidate['part_no']) && (string)$candidate['part_no'] === $wantedPart) {
            return $candidate;
        }
    }
    // 인라인 이미지는 파일명이 없고 Content-ID 로만 식별되는 경우가 많다. part_no
    // 번호가 서버와 어긋나 못 찾았으면 Content-ID 로 다시 매칭한다(이미지 깨짐의 주원인).
    if ($wantedCid !== '') {
        foreach ($parsed['attachments'] as $candidate) {
            if (isset($candidate['content_id']) && mbx_normalize_att_cid($candidate['content_id']) === $wantedCid) {
                return $candidate;
            }
        }
    }
    foreach ($parsed['attachments'] as $candidate) {
        if ($wantedName !== '' && isset($candidate['filename']) && (string)$candidate['filename'] === $wantedName) {
            return $candidate;
        }
    }
    return null;
}

try {
    $db = mbx_db();
    $account = mbx_current_account($db);
    if (!$account) {
        throw new RuntimeException('등록된 메일 계정이 없습니다.');
    }
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $sql = "SELECT a.*, m.uid, m.folder_key, m.message_id FROM mailbox_attachments a INNER JOIN mailbox_messages m ON m.id=a.msg_id WHERE a.id=? AND m.account_id=?";
    $att = mbx_fetch_one_stmt(mbx_stmt($db, $sql, 'ii', array($id, (int)$account['id'])));
    if (!$att) {
        throw new RuntimeException('첨부 파일을 찾을 수 없습니다.');
    }
    global $MBX_FOLDERS;
    $client = mbx_imap_connect($db, $account);
    $resolver = new MailboxSync($db, $account, $MBX_FOLDERS);
    $folderName = $resolver->resolveFolderName($client, $att['folder_key']);
    $client->select($folderName);
    $uid = (int)$att['uid'];
    $mimeRows = array();
    try {
        $mimeRows = $client->uidFetch((string)$uid, '(BODY.PEEK[' . $att['part_no'] . '.MIME])');
    } catch (Exception $e) {
        $mimeRows = array();
    }
    try {
        $rows = $client->uidFetch((string)$uid, '(BODY.PEEK[' . $att['part_no'] . '])');
    } catch (Exception $e) {
        $rows = array();
    }
    $first = reset($rows);
    if ((!$first || !isset($first['BODY']) || $first['BODY'] === '') && trim((string)$att['message_id']) !== '') {
        $criteria = 'HEADER MESSAGE-ID "' . addcslashes(trim((string)$att['message_id']), '"\\') . '"';
        $found = $client->uidSearch($criteria);
        if ($found) {
            $uid = (int)end($found);
            if ($uid !== (int)$att['uid']) {
                $stmt = mbx_stmt($db, "UPDATE mailbox_messages SET uid=? WHERE id=?", 'ii', array($uid, (int)$att['msg_id']));
                mysqli_stmt_close($stmt);
            }
            try {
                $mimeRows = $client->uidFetch((string)$uid, '(BODY.PEEK[' . $att['part_no'] . '.MIME])');
            } catch (Exception $e) {
                $mimeRows = array();
            }
            try {
                $rows = $client->uidFetch((string)$uid, '(BODY.PEEK[' . $att['part_no'] . '])');
            } catch (Exception $e) {
                $rows = array();
            }
        }
    }
    $rawAttachment = null;
    $first = reset($rows);
    if (!$first || !isset($first['BODY']) || $first['BODY'] === '') {
        try {
            $fullRows = $client->uidFetch((string)$uid, '(BODY.PEEK[])');
            $fullFirst = reset($fullRows);
            $fullRaw = isset($fullFirst['BODY']) ? $fullFirst['BODY'] : '';
            $rawAttachment = mbx_find_attachment_in_raw_message($fullRaw, $att);
        } catch (Exception $e) {
            $rawAttachment = null;
        }
    }
    $client->logout();
    $mimeFirst = reset($mimeRows);
    $first = reset($rows);
    $data = isset($first['BODY']) ? $first['BODY'] : '';
    $mimeHeaders = isset($mimeFirst['BODY']) ? MimeParser::parseHeaders($mimeFirst['BODY']) : array();
    $encoding = isset($mimeHeaders['content-transfer-encoding']) ? $mimeHeaders['content-transfer-encoding'] : '';
    $mime = strtolower($att['mime_type']) === 'text/html' ? 'application/octet-stream' : ($att['mime_type'] ?: 'application/octet-stream');
    if ($data === '' && $rawAttachment) {
        $data = isset($rawAttachment['raw_body']) ? $rawAttachment['raw_body'] : '';
        $encoding = isset($rawAttachment['encoding']) ? $rawAttachment['encoding'] : $encoding;
        if (!empty($rawAttachment['mime_type'])) {
            $mime = strtolower($rawAttachment['mime_type']) === 'text/html' ? 'application/octet-stream' : $rawAttachment['mime_type'];
        }
    }
    if ($data === '') {
        throw new RuntimeException('첨부 파일을 서버에서 가져오지 못했습니다. 메일을 다시 동기화한 뒤 시도해 주세요.');
    }
    $data = mbx_decode_attachment_data($data, $encoding, $mime);
    if (preg_match('/^image\//i', $mime) && !mbx_is_image_binary($data)) {
        throw new RuntimeException('Image attachment fetch failed.');
    }
    $filename = preg_replace('/[\r\n"\\\\\/]+/', '_', $att['filename'] !== '' ? $att['filename'] : 'attachment.bin');
    $disposition = (isset($_GET['inline']) && preg_match('/^image\//i', $mime)) ? 'inline' : 'attachment';
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($filename) . '"');
    header('Content-Length: ' . strlen($data));
    echo $data;
} catch (Exception $e) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
}
?>
