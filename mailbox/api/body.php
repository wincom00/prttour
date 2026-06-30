<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
mbx_require_admin_file('include/inc_base.php');
require_once dirname(__DIR__) . '/lib/common.php';

mbx_require_api_auth();

function mbx_normalize_cid($cid)
{
    $cid = trim((string)$cid);
    $cid = trim($cid, '<> ');
    $cid = rawurldecode($cid);
    return strtolower($cid);
}

function mbx_rewrite_body_images($html, array $cidMap)
{
    return preg_replace_callback('/\s(src)\s*=\s*(["\'])(.*?)\2/is', function ($m) use ($cidMap) {
        $src = trim(html_entity_decode($m[3], ENT_QUOTES, 'UTF-8'));
        if (stripos($src, 'cid:') === 0) {
            $cid = mbx_normalize_cid(substr($src, 4));
            if (isset($cidMap[$cid])) {
                return ' src="' . mbx_h($cidMap[$cid]) . '"';
            }
        }
        if (strpos($src, '//') === 0) {
            return ' src="' . mbx_h(mbx_image_proxy_url('https:' . $src)) . '"';
        }
        if (preg_match('/^https?:\/\//i', $src)) {
            return ' src="' . mbx_h(mbx_image_proxy_url($src)) . '"';
        }
        return $m[0];
    }, (string)$html);
}

function mbx_normalize_image_url($url)
{
    $url = trim((string)$url);
    if ($url === '') {
        return $url;
    }
    $decoded = rawurldecode($url);
    if ($decoded !== '' && (!function_exists('mb_check_encoding') || mb_check_encoding($decoded, 'UTF-8'))) {
        return $decoded;
    }
    return $url;
}

function mbx_image_proxy_url($url)
{
    $url = mbx_normalize_image_url($url);
    $encoded = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    return mbx_plugin_url('api/image.php?u=' . $encoded);
}

function mbx_load_cid_map(mysqli $db, $msgId)
{
    $cidMap = array();
    $atts = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT id, content_id FROM mailbox_attachments WHERE msg_id=? AND content_id<>''", 'i', array((int)$msgId)));
    foreach ($atts as $att) {
        $cid = mbx_normalize_cid($att['content_id']);
        if ($cid !== '') {
            $cidMap[$cid] = mbx_plugin_url('api/attachment.php?id=' . (int)$att['id'] . '&inline=1');
        }
    }
    return $cidMap;
}

function mbx_sanitize_body($html)
{
    $html = preg_replace('/<script\b[^>]*>.*?<\/script\s*>/is', '', (string)$html);
    $html = preg_replace('/<script\b[^>]*\/?>/is', '', $html);
    $html = preg_replace('/<\/script\s*>/is', '', $html);
    $html = preg_replace('/<(style|title|head|iframe|object|embed|form|meta|base|svg|math|applet)\b[^>]*>.*?<\/\1\s*>/is', '', $html);
    $html = preg_replace('/<(style|title|head|iframe|object|embed|form|meta|base|svg|math|applet)\b[^>]*\/?>/is', '', $html);
    $html = preg_replace('/<[^>]*(?:javascript|vbscript|data:text\/html|onload|onerror)[^>]*>/is', '', $html);
    $html = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html);
    $html = preg_replace('/(href|src|xlink:href|formaction)\s*=\s*([\'"])\s*(?:javascript|vbscript|data:text\/html):.*?\2/is', '$1="#"', $html);
    if (class_exists('DOMDocument')) {
        $prev = libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $loaded = $doc->loadHTML('<?xml encoding="UTF-8"><div id="mbx-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if ($loaded) {
            $removeTags = array('script', 'style', 'title', 'head', 'iframe', 'object', 'embed', 'form', 'meta', 'base', 'svg', 'math', 'applet');
            foreach ($removeTags as $tag) {
                while (($nodes = $doc->getElementsByTagName($tag))->length > 0) {
                    $node = $nodes->item(0);
                    $node->parentNode->removeChild($node);
                }
            }
            $allowedTags = array(
                'a','p','div','span','br','b','strong','i','em','u','s','ul','ol','li',
                'table','thead','tbody','tfoot','tr','td','th','img','blockquote','pre','code',
                'h1','h2','h3','h4','h5','h6','hr','center','font'
            );
            $nodes = array();
            foreach ($doc->getElementsByTagName('*') as $node) {
                $nodes[] = $node;
            }
            for ($i = count($nodes) - 1; $i >= 0; $i--) {
                $node = $nodes[$i];
                $tagName = strtolower($node->nodeName);
                if ($tagName === 'div' && $node->getAttribute('id') === 'mbx-root') {
                    continue;
                }
                if (!in_array($tagName, $allowedTags, true)) {
                    mbx_unwrap_node($node);
                }
            }

            $xpath = new DOMXPath($doc);
            foreach ($xpath->query('//*[@*]') as $node) {
                $removeAttrs = array();
                foreach ($node->attributes as $attr) {
                    $name = strtolower($attr->name);
                    $value = strtolower(trim($attr->value));
                    if (!mbx_allowed_attr($node->nodeName, $name, $value)) {
                        $removeAttrs[] = $attr->name;
                    }
                }
                foreach ($removeAttrs as $attrName) {
                    $node->removeAttribute($attrName);
                }
            }
            $root = $doc->getElementById('mbx-root');
            if ($root) {
                $clean = '';
                foreach ($root->childNodes as $child) {
                    $clean .= $doc->saveHTML($child);
                }
                $html = $clean;
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
    }
    $html = preg_replace('/<script\b[^>]*>.*?<\/script\s*>/is', '', $html);
    $html = preg_replace('/<script\b[^>]*\/?>/is', '', $html);
    $html = preg_replace('/<\/script\s*>/is', '', $html);
    return $html;
}

function mbx_unwrap_node($node)
{
    if (!$node || !$node->parentNode) {
        return;
    }
    $parent = $node->parentNode;
    while ($node->firstChild) {
        $parent->insertBefore($node->firstChild, $node);
    }
    $parent->removeChild($node);
}

function mbx_allowed_attr($tagName, $name, $value)
{
    $tagName = strtolower((string)$tagName);
    $name = strtolower((string)$name);
    $value = trim((string)$value);
    if ($name === 'id') {
        return $tagName === 'div' && $value === 'mbx-root';
    }
    if ($tagName === 'div' && $name === 'class' && in_array($value, array('mbx-fallback-meta', 'mbx-thread-source'), true)) {
        return true;
    }
    if ($tagName === 'div' && in_array($name, array('data-date', 'data-from', 'data-to'), true)) {
        return true;
    }
    if (strpos($name, 'on') === 0 || in_array($name, array('srcdoc', 'formaction'), true)) {
        return false;
    }
    if (preg_match('/^(javascript|vbscript|data:text\/html):/i', $value)) {
        return false;
    }
    $common = array('title', 'alt', 'width', 'height', 'align', 'valign', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'colspan', 'rowspan');
    if (in_array($name, $common, true)) {
        return true;
    }
    if ($name === 'style') {
        return !preg_match('/(expression\s*\(|javascript:|vbscript:|data:text\/html|-moz-binding|behavior\s*:)/i', $value);
    }
    if ($tagName === 'a' && $name === 'href') {
        return preg_match('/^(https?:|mailto:|tel:|#)/i', $value) === 1;
    }
    if ($tagName === 'a' && in_array($name, array('target', 'rel'), true)) {
        return true;
    }
    if ($tagName === 'img' && $name === 'src') {
        // 프록시/첨부 URL 경로는 설치 위치에 따라 다르다(/mailbox 또는 /admin/mailbox).
        // mbx_plugin_web_root() 로 실제 경로를 반영해 허용한다($value 는 소문자화되어 들어옴).
        $webRoot = function_exists('mbx_plugin_web_root') ? strtolower(rtrim(mbx_plugin_web_root(), '/')) : '';
        if ($webRoot !== '') {
            $root = preg_quote($webRoot, '/');
            if (preg_match('/^' . $root . '\/api\/attachment\.php\?id=\d+&inline=1$/i', $value)) {
                return true;
            }
            if (preg_match('/^' . $root . '\/api\/image\.php\?u=[a-z0-9_-]+$/i', $value)) {
                return true;
            }
        }
        return preg_match('/^(https?:|data:image\/|cid:)/i', $value) === 1;
    }
    return false;
}

function mbx_body_from_row(array $row)
{
    if (isset($row['body_html']) && trim((string)$row['body_html']) !== '') {
        $html = MimeParser::decodeEncodedBlob($row['body_html']);
        // 표시 가능한 HTML 일 때만 사용한다. 깨진/인코딩 깨진 HTML 이면
        // 아래의 텍스트 본문으로 폴백한다(텍스트가 멀쩡한 경우 정상 표시).
        if ($html !== '' && MimeParser::isDisplayableText($html)) {
            return $html;
        }
    }
    if (isset($row['body_text']) && trim((string)$row['body_text']) !== '') {
        $text = MimeParser::decodeEncodedBlob($row['body_text']);
        if (preg_match('/<\s*(?:!doctype|html|head|body|table|div|p|span)\b/i', substr($text, 0, 1000))) {
            return $text;
        }
        if (!MimeParser::isDisplayableText($text)) {
            return '';
        }
        return nl2br(mbx_h($text));
    }
    if (isset($row['snippet']) && trim((string)$row['snippet']) !== '') {
        $snippet = MimeParser::decodeEncodedBlob($row['snippet']);
        if (preg_match('/<\s*(?:!doctype|html|head|body|table|div|p|span)\b/i', substr($snippet, 0, 1000))) {
            return $snippet;
        }
        if (!MimeParser::isDisplayableText($snippet)) {
            return '';
        }
        return '<div class="mbx-preview-only">' . mbx_h($snippet) . '</div>';
    }
    return '';
}

function mbx_body_friendly_error($message)
{
    $message = (string)$message;
    if (stripos($message, 'AUTHENTICATIONFAILED') !== false || stripos($message, 'Invalid credentials') !== false) {
        return '메일 계정 인증에 실패했습니다. 계정 관리에서 앱 비밀번호를 새로 저장한 뒤 동기화를 다시 실행해 주세요.';
    }
    return '본문을 동기화하지 못했습니다: ' . $message;
}

function mbx_body_subject_base($subject)
{
    $subject = trim((string)$subject);
    while (preg_match('/^\s*(?:re|fw|fwd)\s*:\s*/i', $subject)) {
        $subject = preg_replace('/^\s*(?:re|fw|fwd)\s*:\s*/i', '', $subject, 1);
    }
    $subject = preg_replace('/\s+/u', ' ', strtolower(trim($subject)));
    return $subject !== '' ? $subject : '(no subject)';
}

function mbx_body_renderable($body)
{
    if ((string)$body === '') {
        return false;
    }
    $rendered = mbx_sanitize_body($body);
    $decoded = MimeParser::decodeEncodedBlob($rendered);
    if ($decoded !== $rendered) {
        $rendered = mbx_sanitize_body($decoded);
    }
    return MimeParser::isDisplayableText($rendered);
}

function mbx_body_open_client(array $account)
{
    $client = new ImapClient($account['imap_host'], (int)$account['imap_port']);
    $client->connect();
    $client->login($account['email'], $account['app_password']);
    return $client;
}

function mbx_body_all_mail_folder(ImapClient $client)
{
    $folders = $client->listFolderDetails();
    foreach ($folders as $folder) {
        if (empty($folder['attrs'])) {
            continue;
        }
        foreach ($folder['attrs'] as $attr) {
            if (strcasecmp($attr, '\\All') === 0) {
                return $folder['name'];
            }
        }
    }

    $candidates = array(
        '[gmail]/all mail',
        '[google mail]/all mail',
        '[gmail]/all',
        '[google mail]/all',
        'all mail',
        'all',
        '[gmail]/전체보관함',
        '[google mail]/전체보관함',
        '전체보관함',
    );
    foreach ($folders as $folder) {
        $name = strtolower((string)$folder['name']);
        if (in_array($name, $candidates, true)) {
            return $folder['name'];
        }
        foreach ($candidates as $candidate) {
            if ($candidate !== '' && substr($name, -strlen($candidate)) === $candidate) {
                return $folder['name'];
            }
        }
    }
    return '';
}

function mbx_body_search_quote($value)
{
    return '"' . addcslashes((string)$value, '"\\') . '"';
}

function mbx_body_from_parsed(array $parsed)
{
    $html = isset($parsed['body_html']) ? (string)$parsed['body_html'] : '';
    if (trim($html) !== '') {
        return MimeParser::decodeEncodedBlob($html);
    }
    $text = isset($parsed['body_text']) ? MimeParser::decodeEncodedBlob($parsed['body_text']) : '';
    if (trim($text) === '' || !MimeParser::isDisplayableText($text)) {
        return '';
    }
    return nl2br(mbx_h($text));
}

function mbx_body_source_meta($label, $date, $from, $to)
{
    $date = trim((string)$date);
    $from = trim((string)$from);
    $to = trim((string)$to);
    return '<div class="mbx-fallback-meta" data-date="' . mbx_h($date) . '" data-from="' . mbx_h($from) . '" data-to="' . mbx_h($to) . '" style="display:none"></div>'
        . '<div class="mbx-thread-source">' . mbx_h($label . ': ' . trim($date . ' ' . $from)) . '</div>';
}

function mbx_body_raw_candidate(ImapClient $client, $uid)
{
    $rows = $client->uidFetch((string)(int)$uid, '(BODY.PEEK[HEADER] BODY.PEEK[TEXT]<0.262144>)');
    $first = reset($rows);
    $header = isset($first['HEADER']) ? (string)$first['HEADER'] : '';
    $body = isset($first['BODY']) ? (string)$first['BODY'] : '';
    if ($header !== '' || $body !== '') {
        return $header . "\r\n\r\n" . $body;
    }
    return '';
}

function mbx_body_all_mail_fallback(array $account, array $current, &$syncError)
{
    $threadId = isset($current['thread_id']) ? trim((string)$current['thread_id']) : '';
    if ($threadId === '') {
        return '';
    }

    $client = null;
    $lastSearchError = '';
    try {
        $client = mbx_body_open_client($account);
        $allMail = mbx_body_all_mail_folder($client);
        if ($allMail !== '') {
            $client->select($allMail);
        } else {
            $client->select('INBOX');
            $lastSearchError = 'Gmail All Mail folder was not found; tried in:anywhere search from INBOX.';
        }

        $searches = array();
        $searches[] = 'X-GM-THRID ' . $threadId;

        foreach ($searches as $criteria) {
            try {
                $uids = $client->uidSearch($criteria);
            } catch (Exception $e) {
                $lastSearchError = $e->getMessage();
                continue;
            }
            if (!$uids) {
                continue;
            }
            $uids = array_reverse($uids);
            $uids = array_slice($uids, 0, 10);
            foreach ($uids as $uid) {
                try {
                    $raw = mbx_body_raw_candidate($client, $uid);
                } catch (Exception $e) {
                    $lastSearchError = $e->getMessage();
                    continue;
                }
                if ($raw === '') {
                    continue;
                }
                $parsed = MimeParser::parseMessage($raw);
                $headers = isset($parsed['headers']) ? $parsed['headers'] : array();
                $candidateBody = mbx_body_from_parsed($parsed);
                if (!mbx_body_renderable($candidateBody)) {
                    continue;
                }

                $from = '';
                if (!empty($headers['from'])) {
                    $addr = MimeParser::parseAddressList($headers['from']);
                    if (isset($addr[0])) {
                        $from = trim($addr[0]['name'] !== '' ? $addr[0]['name'] : $addr[0]['email']);
                    }
                }
                $to = '';
                $toHeader = !empty($headers['to']) ? $headers['to'] : (!empty($headers['cc']) ? $headers['cc'] : '');
                if ($toHeader !== '') {
                    $toAddr = MimeParser::parseAddressList($toHeader);
                    $parts = array();
                    foreach ($toAddr as $addr) {
                        $parts[] = trim($addr['name'] !== '' ? $addr['name'] . ' <' . $addr['email'] . '>' : $addr['email']);
                    }
                    $to = implode(', ', array_filter($parts));
                }
                $date = '';
                if (!empty($headers['date'])) {
                    $ts = strtotime($headers['date']);
                    $date = $ts ? date('Y-m-d H:i', $ts) : $headers['date'];
                }
                $client->logout();
                return mbx_body_source_meta('Gmail All Mail', $date, $from, $to) . $candidateBody;
            }
        }
        $client->logout();
    } catch (Exception $e) {
        if ($client) {
            $client->logout();
        }
        $lastSearchError = $e->getMessage();
    }

    if ($syncError === '') {
        $syncError = 'Gmail 전체메일에서 같은 대화 ID의 표시 가능한 본문을 찾지 못했습니다.'
            . ($lastSearchError !== '' ? ' (' . $lastSearchError . ')' : '');
    }
    return '';
}

function mbx_body_thread_fallback(mysqli $db, array $account, array $current, &$cidMap, &$syncError)
{
    global $MBX_FOLDERS;
    $threadId = isset($current['thread_id']) ? trim((string)$current['thread_id']) : '';
    if ($threadId === '') {
        return '';
    }
    $rows = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_messages WHERE account_id=? AND thread_id=? ORDER BY mail_date DESC, uid DESC", 'is', array((int)$account['id'], $threadId)));
    foreach ($rows as $candidate) {
        if ((int)$candidate['id'] === (int)$current['id']) {
            continue;
        }
        if ((int)$candidate['body_fetched'] === 0) {
            try {
                $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
                $candidate = $sync->fetchBody((int)$candidate['id']);
            } catch (Exception $e) {
                if ($syncError === '') {
                    $syncError = mbx_body_friendly_error($e->getMessage());
                }
            }
        }
        $candidateBody = mbx_body_from_row($candidate);
        if (!mbx_body_renderable($candidateBody)) {
            continue;
        }
        $cidMap = mbx_load_cid_map($db, (int)$candidate['id']);
        $from = $candidate['from_name'] !== '' ? $candidate['from_name'] : $candidate['from_email'];
        $date = mbx_date_label($candidate['mail_date']);
        return '<div class="mbx-thread-source">같은 주제 메일에서 표시 중: ' . mbx_h(trim($date . ' ' . $from)) . '</div>' . $candidateBody;
    }
    $allMailBody = mbx_body_all_mail_fallback($account, $current, $syncError);
    if ($allMailBody !== '') {
        $cidMap = array();
        return $allMailBody;
    }
    return '';
}

try {
    $db = mbx_db();
    $account = mbx_current_account($db);
    if (!$account) {
        throw new RuntimeException('등록된 메일 계정이 없습니다.');
    }
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    global $MBX_FOLDERS;
    $row = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT * FROM mailbox_messages WHERE id=? AND account_id=?", 'ii', array($id, (int)$account['id'])));
    if (!$row) {
        throw new RuntimeException('메일을 찾을 수 없습니다.');
    }
    $syncError = '';
    if (trim((string)$row['thread_id']) === '') {
        try {
            $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
            $threadId = $sync->refreshThreadId($id);
            if ($threadId !== '') {
                $row['thread_id'] = $threadId;
            }
        } catch (Exception $e) {
        }
    }
    $alreadyFetched = false;
    if ((int)$row['body_fetched'] === 0) {
        try {
            $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
            $row = $sync->fetchBody($id);
        } catch (Exception $e) {
            $syncError = mbx_body_friendly_error($e->getMessage());
        }
        $alreadyFetched = true;
    }
    $body = mbx_body_from_row($row);
    $cidMap = mbx_load_cid_map($db, (int)$row['id']);
    if ($syncError === '' && !$cidMap && stripos($body, 'cid:') !== false) {
        try {
            $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
            $row = $sync->fetchBody($id);
            $body = mbx_body_from_row($row);
            $cidMap = mbx_load_cid_map($db, (int)$row['id']);
            $alreadyFetched = true;
        } catch (Exception $e) {
            $syncError = mbx_body_friendly_error($e->getMessage());
        }
    }
    // DB 에 표시 가능한 본문이 없으면(이전 동기화가 헤더만 받았거나 빈 본문을 저장한 경우 포함),
    // 스레드/전체보관함 폴백으로 넘어가기 전에 서버에서 "이 메일만" 강제로 다시 받아온다.
    if (!mbx_body_renderable($body) && !$alreadyFetched) {
        try {
            $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
            $refetched = $sync->fetchBody($id);
            if ($refetched) {
                $row = $refetched;
                $body = mbx_body_from_row($row);
                $cidMap = mbx_load_cid_map($db, (int)$row['id']);
            }
        } catch (Exception $e) {
            if ($syncError === '') {
                $syncError = mbx_body_friendly_error($e->getMessage());
            }
        }
    }
    if (!mbx_body_renderable($body)) {
        $fallbackBody = mbx_body_thread_fallback($db, $account, $row, $cidMap, $syncError);
        if ($fallbackBody !== '') {
            $body = $fallbackBody;
        }
    }
    $body = mbx_rewrite_body_images($body, $cidMap);
    header("Content-Type: text/html; charset=utf-8");
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header("Content-Security-Policy: default-src 'none'; script-src 'none'; img-src 'self' http: https: data:; style-src 'unsafe-inline' http: https:; font-src http: https: data:");
    header('X-Content-Type-Options: nosniff');
    echo '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#333}.mbx-sync-warning{padding:12px 14px;margin:0 0 15px;color:#8a6d3b;background:#fcf8e3;border:1px solid #faebcc;border-radius:4px}.mbx-preview-only{white-space:pre-wrap;color:#333}.mbx-thread-source{padding:8px 10px;margin:0 0 12px;color:#31708f;background:#d9edf7;border:1px solid #bce8f1;border-radius:4px}img{max-width:100%;height:auto}</style></head><body>';
    if ($syncError !== '') {
        echo '<div class="mbx-sync-warning">' . mbx_h($syncError) . '</div>';
    }
    if ($body !== '') {
        $renderedBody = mbx_sanitize_body($body);
        $decodedRenderedBody = MimeParser::decodeEncodedBlob($renderedBody);
        if ($decodedRenderedBody !== $renderedBody) {
            $renderedBody = mbx_sanitize_body($decodedRenderedBody);
        }
        if (MimeParser::isDisplayableText($renderedBody)) {
            echo $renderedBody;
        } elseif ($syncError === '') {
            echo '<div class="mbx-sync-warning">표시할 본문이 없습니다. 첨부 파일만 있는 메일이거나, 같은 주제의 다른 메일을 선택해 주세요.</div>';
        }
    } elseif ($syncError === '') {
        echo '<div class="mbx-sync-warning">표시할 본문이 없습니다. 첨부 파일만 있는 메일이거나, 같은 주제의 다른 메일을 선택해 주세요.</div>';
    }
    echo '</body></html>';
} catch (Exception $e) {
    header("Content-Type: text/html; charset=utf-8");
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header("Content-Security-Policy: default-src 'none'; script-src 'none'; style-src 'unsafe-inline'");
    header('X-Content-Type-Options: nosniff');
    echo '<div style="padding:15px;color:#a94442">본문을 불러오지 못했습니다: ' . mbx_h($e->getMessage()) . '</div>';
}
?>
