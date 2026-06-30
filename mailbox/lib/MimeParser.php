<?php
final class MimeParser
{
    public static function decodeHeader($raw)
    {
        $raw = (string)$raw;
        if ($raw === '') {
            return '';
        }
        $fixed = preg_replace('/=\?(ks_c_5601-1987|ks_c_5601|euc-kr)\?/i', '=?CP949?', $raw);
        if (function_exists('mb_decode_mimeheader')) {
            $decoded = @mb_decode_mimeheader($fixed);
            if ($decoded !== false && $decoded !== '') {
                return self::toUtf8($decoded, 'UTF-8');
            }
        }
        if (function_exists('iconv_mime_decode')) {
            $decoded = @iconv_mime_decode($fixed, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($decoded !== false && $decoded !== '') {
                return $decoded;
            }
        }
        return self::toUtf8($raw, 'UTF-8');
    }

    public static function parseAddressList($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return array();
        }
        $parts = self::splitHeaderList($raw);
        $out = array();
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $name = '';
            $email = '';
            if (preg_match('/^(.*)<([^>]+)>$/', $part, $m)) {
                $name = trim($m[1], " \t\n\r\0\x0B\"'");
                $email = trim($m[2]);
            } else {
                $email = trim($part, " \t\n\r\0\x0B\"'");
            }
            $email = trim($email);
            if ($email === '') {
                continue;
            }
            $out[] = array('name' => self::decodeHeader($name), 'email' => $email);
        }
        return $out;
    }

    public static function parseMessage($raw)
    {
        list($headerText, $body) = self::splitHeaderBody((string)$raw);
        $headers = self::parseHeaders($headerText);
        $result = array(
            'headers' => $headers,
            'body_html' => '',
            'body_text' => '',
            'attachments' => array(),
        );
        self::parsePart($headers, $body, '', $result);
        if ($result['body_html'] === '' && $result['body_text'] !== '') {
            $result['body_html'] = nl2br(htmlspecialchars($result['body_text'], ENT_QUOTES, 'UTF-8'));
        }
        return $result;
    }

    public static function hasAttachmentFromBodyStructure($bodystructure)
    {
        $s = strtolower((string)$bodystructure);
        return strpos($s, 'attachment') !== false || strpos($s, 'filename') !== false || strpos($s, 'name ') !== false;
    }

    public static function makeSnippet($htmlOrText, $len = 200)
    {
        $text = (string)$htmlOrText;
        // Drop blocks whose inner text would otherwise leak into the snippet (CSS, JS, head).
        $text = preg_replace('/<(style|script|head|title)\b[^>]*>.*?<\/\1\s*>/is', ' ', $text);
        $text = preg_replace('/<!--.*?-->/s', ' ', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);
        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $len) {
            return mb_substr($text, 0, $len, 'UTF-8');
        }
        return strlen($text) > $len ? substr($text, 0, $len) : $text;
    }

    public static function makePreviewSnippet($raw, $len = 220)
    {
        return self::cleanPreviewText($raw, $len);
    }

    public static function cleanPreviewText($raw, $len = 220)
    {
        $text = (string)$raw;
        if ($text === '') {
            return '';
        }
        $decoded = self::decodeEncodedBlob($text);
        if ($decoded !== $text) {
            $text = $decoded;
        } else {
            $text = quoted_printable_decode($text);
        }
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/<!--.*?-->/s', ' ', $text);
        $text = preg_replace('/<style\b[^>]*>.*?<\/style\s*>/is', ' ', $text);
        $text = preg_replace('/@font-face\s*\{.*?\}/is', ' ', $text);
        $text = preg_replace('/\/\*.*?\*\//s', ' ', $text);
        $text = preg_replace('/^\s*--[^\r\n]+(--)?\s*$/m', ' ', $text);
        $text = preg_replace('/\bContent-(?:Type|Transfer-Encoding|Disposition|ID|Description):.*$/im', ' ', $text);
        $text = preg_replace('/^\s*(?:charset|boundary|name|filename)\s*=.*$/im', ' ', $text);
        $text = preg_replace('/[-_*]{8,}[^\\r\\n]*/', ' ', $text);
        $snippet = self::makeSnippet($text, $len);
        if ($snippet === '' || self::looksLikeEncodedBlob($snippet) || self::looksLikeMimeJunk($snippet) || !self::isDisplayableText($snippet)) {
            return '';
        }
        return $snippet;
    }

    public static function isDisplayableText($text)
    {
        $text = trim(strip_tags(html_entity_decode((string)$text, ENT_QUOTES, 'UTF-8')));
        if ($text === '') {
            return false;
        }
        if (self::looksLikeEncodedBlob($text) || self::looksLikeMimeJunk($text)) {
            return false;
        }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $text)) {
            return false;
        }
        $len = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        if ($len <= 0) {
            return false;
        }
        $asciiLetters = preg_match_all('/[A-Za-z0-9가-힣]/u', $text, $m);
        $spaces = preg_match_all('/\s/u', $text, $m2);
        $replacement = substr_count($text, "\xEF\xBF\xBD");
        if ($replacement > 0) {
            return false;
        }
        if ($len <= 20 && $asciiLetters < 4 && $spaces < 2) {
            return false;
        }
        if ($len > 20 && (($asciiLetters + $spaces) / $len) < 0.35) {
            return false;
        }
        if ($len <= 30 && preg_match('/[^\x00-\x7F]/', $text) && preg_match('/[A-Za-z]{2,}/', $text) && preg_match('/[^\p{L}\p{N}\s\.,;:!\?@\#\$%\&\*\(\)\[\]\{\}<>"\'\/\\\\_\-\+=\|`~]/u', $text)) {
            return false;
        }
        $plain = preg_replace('/[A-Za-z0-9가-힣\s\.,;:!\?@\#\$%\&\*\(\)\[\]\{\}<>"\'\/\\\\_\-\+=\|`~]/u', '', $text);
        $plainLen = function_exists('mb_strlen') ? mb_strlen($plain, 'UTF-8') : strlen($plain);
        if ($plainLen > 0 && ($plainLen / $len) > 0.20) {
            return false;
        }
        return true;
    }

    private static function looksLikeEncodedBlob($text)
    {
        $text = preg_replace('/\s+/', '', (string)$text);
        return strlen($text) > 80 && preg_match('/^[A-Za-z0-9+\/=]+$/', $text);
    }

    private static function looksLikeMimeJunk($text)
    {
        $text = trim((string)$text);
        if ($text === '') {
            return false;
        }
        if (preg_match('/(?:Content-Type|Content-Transfer-Encoding|boundary=|NextPart_|FONT PATH)/i', $text)) {
            return true;
        }
        // 긴 base64 런 검사 전에 URL을 제거한다. 마케팅 메일의 추적 URL 토큰은
        // 80자 이상 영숫자가 흔해 정상 본문이 오탐되기 때문이다(실제로 본문에 새어
        // 들어온 base64 MIME 덩어리는 URL 이 아니므로 제거 후에도 남아 탐지된다).
        $stripped = preg_replace('#\b(?:https?|ftp|mailto):[^\s"\'<>]+#i', ' ', $text);
        if (preg_match('/[A-Za-z0-9+\/=]{200,}/', $stripped)) {
            return true;
        }
        return false;
    }

    public static function parseHeaders($headerText)
    {
        $headerText = preg_replace("/\r?\n[ \t]+/", ' ', (string)$headerText);
        $headers = array();
        foreach (preg_split("/\r?\n/", trim($headerText)) as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($name, $value) = explode(':', $line, 2);
            $key = strtolower(trim($name));
            $value = trim($value);
            if (isset($headers[$key])) {
                $headers[$key] .= ', ' . $value;
            } else {
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    public static function decodePartBody($body, $encoding, $charset)
    {
        return self::toUtf8(self::decodeTransferEncoding($body, $encoding), $charset);
    }

    public static function decodeTransferEncoding($body, $encoding)
    {
        $encoding = strtolower(trim((string)$encoding));
        if ($encoding === 'base64') {
            $decoded = base64_decode(preg_replace('/\s+/', '', (string)$body), true);
            if ($decoded !== false) {
                return $decoded;
            }
        } elseif ($encoding === 'quoted-printable') {
            return quoted_printable_decode((string)$body);
        }
        return (string)$body;
    }

    public static function decodeEncodedBlob($value, $charset = 'UTF-8')
    {
        $value = trim((string)$value);
        if ($value === '') {
            return $value;
        }
        $candidate = preg_replace('/<br\s*\/?>/i', "\n", $value);
        $candidate = html_entity_decode(strip_tags($candidate), ENT_QUOTES, 'UTF-8');

        $encodedBlocks = array();
        if (self::looksLikeEncodedBlob($candidate)) {
            $encodedBlocks[] = preg_replace('/\s+/', '', $candidate);
        }

        $current = '';
        foreach (preg_split("/\r?\n/", $candidate) as $line) {
            $line = trim($line);
            if ($line === '') {
                if ($current !== '') {
                    $encodedBlocks[] = $current;
                    $current = '';
                }
                continue;
            }
            if (preg_match('/^--/', $line) || preg_match('/^[A-Za-z0-9-]+:/', $line)) {
                if ($current !== '') {
                    $encodedBlocks[] = $current;
                    $current = '';
                }
                continue;
            }
            $lineCompact = preg_replace('/\s+/', '', $line);
            if (preg_match('/^[A-Za-z0-9+\/=]{20,}$/', $lineCompact)) {
                $current .= $lineCompact;
            } else {
                if ($current !== '') {
                    $encodedBlocks[] = $current;
                    $current = '';
                }
            }
        }
        if ($current !== '') {
            $encodedBlocks[] = $current;
        }

        // 긴 base64 런을 본문 어디서든 찾기 전에 URL을 제거한다. 추적 URL 토큰이
        // 200자를 넘으면 인코딩 블록으로 오인해 본문을 디코딩 쓰레기로 바꿔버리기 때문.
        $candidateNoUrls = preg_replace('#\b(?:https?|ftp|mailto):[^\s"\'<>]+#i', ' ', $candidate);
        if (!$encodedBlocks && preg_match_all('/[A-Za-z0-9+\/=]{200,}/', $candidateNoUrls, $matches)) {
            $encodedBlocks = $matches[0];
        }

        $fallbackText = null;
        foreach (array_unique($encodedBlocks) as $compact) {
            $compact = strtr($compact, '-_', '+/');
            $pad = strlen($compact) % 4;
            if ($pad > 0) {
                $compact .= str_repeat('=', 4 - $pad);
            }
            $decoded = base64_decode($compact, true);
            if ($decoded === false || $decoded === '') {
                continue;
            }
            $text = self::toUtf8($decoded, $charset);
            $sample = substr($text, 0, 1000);
            if (preg_match('/<\s*(?:!doctype|html|head|body|table|div|p|span)\b/i', $sample)) {
                return $text;
            }
            if ($fallbackText === null && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $sample)) {
                $fallbackText = $text;
            }
        }
        return $fallbackText !== null ? $fallbackText : $value;
    }

    private static function parsePart(array $headers, $body, $partNo, array &$result)
    {
        $contentType = isset($headers['content-type']) ? $headers['content-type'] : 'text/plain';
        $type = strtolower(trim(strtok($contentType, ';')));
        $params = self::parseHeaderParams($contentType);
        $disposition = isset($headers['content-disposition']) ? strtolower($headers['content-disposition']) : '';
        $dispParams = self::parseHeaderParams(isset($headers['content-disposition']) ? $headers['content-disposition'] : '');
        $charset = isset($params['charset']) ? $params['charset'] : 'UTF-8';
        $encoding = isset($headers['content-transfer-encoding']) ? $headers['content-transfer-encoding'] : '';

        if (strpos($type, 'multipart/') === 0 && !empty($params['boundary'])) {
            $children = self::splitMultipart($body, $params['boundary']);
            $idx = 1;
            foreach ($children as $child) {
                list($childHeadersText, $childBody) = self::splitHeaderBody($child);
                $childHeaders = self::parseHeaders($childHeadersText);
                $childNo = $partNo === '' ? (string)$idx : $partNo . '.' . $idx;
                self::parsePart($childHeaders, $childBody, $childNo, $result);
                $idx++;
            }
            return;
        }

        $filename = '';
        if (isset($dispParams['filename'])) {
            $filename = self::decodeHeader($dispParams['filename']);
        } elseif (isset($params['name'])) {
            $filename = self::decodeHeader($params['name']);
        }
        $currentPartNo = $partNo === '' ? '1' : $partNo;
        $contentId = isset($headers['content-id']) ? trim($headers['content-id'], '<> ') : '';
        $isAttach = strpos($disposition, 'attachment') !== false || $filename !== '' || ($contentId !== '' && strpos($type, 'image/') === 0);
        $decoded = self::decodePartBody($body, $encoding, $charset);
        // decodeEncodedBlob 은 본문 안의 base64 처럼 보이는 한 줄만 디코딩해 본문 전체를
        // 덮어쓸 수 있어 파괴적이다. 이미 표시 가능한 정상 HTML/텍스트에는 적용하지 않고,
        // 본문 자체가 인코딩 블록으로 보일 때만(전송 인코딩 헤더 누락 등) 복구용으로 쓴다.
        if (!self::isDisplayableText($decoded)) {
            $decoded = self::decodeEncodedBlob($decoded, $charset);
        }

        if ($isAttach) {
            $result['attachments'][] = array(
                'part_no' => $currentPartNo,
                'filename' => $filename,
                'mime_type' => $type,
                'size' => strlen((string)$body),
                'content_id' => $contentId,
                'encoding' => $encoding,
                'raw_body' => (string)$body,
            );
            return;
        }

        if ($type === 'text/html' && $result['body_html'] === '') {
            $result['body_html'] = $decoded;
        } elseif ($type === 'text/plain' && $result['body_text'] === '') {
            $result['body_text'] = $decoded;
        }
    }

    private static function splitHeaderBody($raw)
    {
        $pos = strpos($raw, "\r\n\r\n");
        $sep = 4;
        if ($pos === false) {
            $pos = strpos($raw, "\n\n");
            $sep = 2;
        }
        if ($pos === false) {
            return array($raw, '');
        }
        return array(substr($raw, 0, $pos), substr($raw, $pos + $sep));
    }

    private static function parseHeaderParams($value)
    {
        $params = array();
        $parts = preg_split('/;\s*/', (string)$value);
        array_shift($parts);
        foreach ($parts as $part) {
            if (strpos($part, '=') === false) {
                continue;
            }
            list($k, $v) = explode('=', $part, 2);
            $k = strtolower(trim($k));
            $v = trim($v, " \t\n\r\0\x0B\"");
            $params[$k] = $v;
        }
        return $params;
    }

    private static function splitMultipart($body, $boundary)
    {
        $boundary = preg_quote($boundary, '/');
        $parts = preg_split('/\r?\n--' . $boundary . '(?:--)?\r?\n/', "\r\n" . (string)$body);
        $out = array();
        foreach ($parts as $part) {
            $part = trim($part, "\r\n");
            if ($part !== '' && $part !== '--') {
                $out[] = $part;
            }
        }
        return $out;
    }

    private static function splitHeaderList($raw)
    {
        $out = array();
        $buf = '';
        $quote = false;
        $angle = 0;
        $len = strlen($raw);
        for ($i = 0; $i < $len; $i++) {
            $c = $raw[$i];
            if ($c === '"' && ($i === 0 || $raw[$i - 1] !== '\\')) {
                $quote = !$quote;
            } elseif (!$quote && $c === '<') {
                $angle++;
            } elseif (!$quote && $c === '>' && $angle > 0) {
                $angle--;
            } elseif (!$quote && $angle === 0 && $c === ',') {
                $out[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $c;
        }
        if ($buf !== '') {
            $out[] = $buf;
        }
        return $out;
    }

    private static function toUtf8($value, $charset)
    {
        $value = (string)$value;
        $charset = strtoupper(trim((string)$charset));
        if ($charset === '' || $charset === 'DEFAULT') {
            $charset = 'UTF-8';
        }
        if (in_array($charset, array('KS_C_5601-1987', 'KS_C_5601', 'EUC-KR'), true)) {
            $charset = 'CP949';
        }
        if ($charset === 'UTF-8' || $charset === 'US-ASCII') {
            if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
                $converted = @mb_convert_encoding($value, 'UTF-8', 'CP949,EUC-KR,ISO-8859-1');
                return $converted !== false ? $converted : $value;
            }
            return $value;
        }
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', $charset);
            if ($converted !== false) {
                return $converted;
            }
        }
        $converted = @iconv($charset, 'UTF-8//IGNORE', $value);
        return $converted !== false ? $converted : $value;
    }
}
?>
