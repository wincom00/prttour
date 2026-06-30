<?php
final class ImapClient
{
    private $host;
    private $port;
    private $timeout;
    private $fp;
    private $tagNo = 1;

    public function __construct($host, $port, $timeout = 30)
    {
        $this->host = (string)$host;
        $this->port = (int)$port;
        $this->timeout = (int)$timeout;
    }

    public function connect()
    {
        $errno = 0;
        $errstr = '';
        $this->fp = @stream_socket_client('ssl://' . $this->host . ':' . $this->port, $errno, $errstr, $this->timeout);
        if (!$this->fp) {
            throw new RuntimeException('IMAP connect failed: ' . $errstr);
        }
        stream_set_timeout($this->fp, $this->timeout);
        $greeting = $this->readLine();
        if (strpos($greeting, '* OK') !== 0) {
            throw new RuntimeException('Invalid IMAP greeting: ' . trim($greeting));
        }
    }

    public function login($user, $pass)
    {
        $this->command('LOGIN ' . $this->quote($user) . ' ' . $this->quote($pass));
    }

    public function listFolders()
    {
        $folders = array();
        foreach ($this->listFolderDetails() as $row) {
            $folders[] = $row['name'];
        }
        return $folders;
    }

    public function listFolderDetails()
    {
        $lines = $this->command('LIST "" "*"');
        $folders = array();
        foreach ($lines as $line) {
            $row = $this->parseListLine($line);
            if ($row) {
                $folders[] = $row;
            }
        }
        return $folders;
    }

    public function select($folder)
    {
        $lines = $this->command('SELECT ' . $this->quote($folder));
        $out = array('uidvalidity' => 0, 'uidnext' => 0, 'exists' => 0);
        foreach ($lines as $line) {
            if (preg_match('/^\* (\d+) EXISTS/i', $line, $m)) {
                $out['exists'] = (int)$m[1];
            }
            if (preg_match('/UIDVALIDITY\s+(\d+)/i', $line, $m)) {
                $out['uidvalidity'] = (int)$m[1];
            }
            if (preg_match('/UIDNEXT\s+(\d+)/i', $line, $m)) {
                $out['uidnext'] = (int)$m[1];
            }
        }
        return $out;
    }

    public function uidSearch($criteria)
    {
        $lines = $this->command('UID SEARCH ' . $criteria);
        $uids = array();
        foreach ($lines as $line) {
            if (preg_match('/^\* SEARCH\s*(.*)$/i', trim($line), $m)) {
                $parts = preg_split('/\s+/', trim($m[1]));
                foreach ($parts as $part) {
                    if ($part !== '' && ctype_digit($part)) {
                        $uids[] = (int)$part;
                    }
                }
            }
        }
        sort($uids, SORT_NUMERIC);
        return $uids;
    }

    public function uidFetch($set, $items)
    {
        $lines = $this->command('UID FETCH ' . $set . ' ' . $items);
        return $this->parseFetch($lines, $items, $set);
    }

    public function uidStore($set, $op, $flags)
    {
        $this->command('UID STORE ' . $set . ' ' . $op . ' (' . $flags . ')');
    }

    public function expunge()
    {
        $this->command('EXPUNGE');
    }

    public function uidMove($set, $folder)
    {
        try {
            $this->command('UID MOVE ' . $set . ' ' . $this->quote($folder));
        } catch (RuntimeException $e) {
            $this->command('UID COPY ' . $set . ' ' . $this->quote($folder));
            $this->command('UID STORE ' . $set . ' +FLAGS.SILENT (\Deleted)');
            $this->command('EXPUNGE');
        }
    }

    public function logout()
    {
        if ($this->fp) {
            try {
                $this->command('LOGOUT', true);
            } catch (Exception $e) {
            }
            fclose($this->fp);
            $this->fp = null;
        }
    }

    private function command($cmd, $allowBye = false)
    {
        if (!$this->fp) {
            throw new RuntimeException('IMAP socket is not connected.');
        }
        $tag = 'A' . str_pad((string)$this->tagNo++, 4, '0', STR_PAD_LEFT);
        fwrite($this->fp, $tag . ' ' . $cmd . "\r\n");
        $lines = array();
        while (!feof($this->fp)) {
            $line = $this->readLogicalLine();
            $lines[] = $line;
            if (strpos($line, $tag . ' ') === 0) {
                if (preg_match('/^' . preg_quote($tag, '/') . '\s+OK/i', $line)) {
                    return $lines;
                }
                if ($allowBye && preg_match('/^' . preg_quote($tag, '/') . '\s+BYE/i', $line)) {
                    return $lines;
                }
                throw new RuntimeException('IMAP command failed: ' . trim($line));
            }
        }
        throw new RuntimeException('IMAP command timed out.');
    }

    private function readLogicalLine()
    {
        $line = $this->readLine();
        while (preg_match('/\{(\d+)\}\r?\n$/', $line, $m)) {
            $len = (int)$m[1];
            $literal = $this->readBytes($len);
            $line .= $literal;
            $line .= $this->readLine();
        }
        return $line;
    }

    private function readLine()
    {
        $line = fgets($this->fp);
        if ($line === false) {
            $meta = stream_get_meta_data($this->fp);
            throw new RuntimeException(!empty($meta['timed_out']) ? 'IMAP read timeout.' : 'IMAP read failed.');
        }
        return $line;
    }

    private function readBytes($len)
    {
        $buf = '';
        while (strlen($buf) < $len && !feof($this->fp)) {
            $part = fread($this->fp, $len - strlen($buf));
            if ($part === false || $part === '') {
                $meta = stream_get_meta_data($this->fp);
                if (!empty($meta['timed_out'])) {
                    throw new RuntimeException('IMAP literal read timeout.');
                }
                usleep(10000);
                continue;
            }
            $buf .= $part;
        }
        if (strlen($buf) !== $len) {
            throw new RuntimeException('IMAP literal length mismatch.');
        }
        return $buf;
    }

    private function quote($value)
    {
        return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), (string)$value) . '"';
    }

    private function parseListLine($line)
    {
        $line = trim($line);
        if (strpos($line, '* LIST ') !== 0) {
            return null;
        }
        $attrs = array();
        if (preg_match('/^\* LIST\s+\(([^)]*)\)\s+("[^"]*"|NIL)\s+(.+)$/i', $line, $m)) {
            $attrText = trim($m[1]);
            if ($attrText !== '') {
                $attrs = preg_split('/\s+/', $attrText);
            }
            $name = trim($m[3]);
            if (strlen($name) >= 2 && $name[0] === '"' && substr($name, -1) === '"') {
                $name = stripcslashes(substr($name, 1, -1));
            }
            return array('name' => $name, 'attrs' => $attrs, 'raw' => $line);
        }
        return null;
    }

    private function parseFetch(array $lines, $items, $set = '')
    {
        $rows = array();
        $fallbackUid = preg_match('/^\d+$/', (string)$set) ? (int)$set : 0;
        foreach ($lines as $line) {
            if (strpos($line, ' FETCH ') === false) {
                continue;
            }
            if (preg_match('/\bUID\s+(\d+)/i', $line, $m)) {
                $uid = (int)$m[1];
            } elseif ($fallbackUid > 0) {
                $uid = $fallbackUid;
            } else {
                continue;
            }
            $row = array('_raw' => $line, 'UID' => $uid);
            if (preg_match('/FLAGS\s+\(([^)]*)\)/i', $line, $fm)) {
                $row['FLAGS'] = trim($fm[1]);
            }
            if (preg_match('/RFC822\.SIZE\s+(\d+)/i', $line, $sm)) {
                $row['RFC822.SIZE'] = (int)$sm[1];
            }
            if (preg_match('/X-GM-THRID\s+(\d+)/i', $line, $tm)) {
                $row['X-GM-THRID'] = $tm[1];
            }
            if (preg_match('/BODYSTRUCTURE\s+(.+?)(?:\s+BODY(?:\.PEEK)?\[|\s+RFC822\.SIZE|\s+FLAGS|\s+UID|\)\r?\n?$)/is', $line, $bm)) {
                $row['BODYSTRUCTURE'] = trim($bm[1]);
            }

            $literals = $this->extractBodyLiterals($line);
            foreach ($literals as $lit) {
                $section = strtoupper($lit['section']);
                if (strpos($section, 'HEADER') !== false) {
                    $row['HEADER'] = $lit['body'];
                } elseif ($section === '' || $section === 'TEXT') {
                    $row['BODY'] = $lit['body'];
                } else {
                    $row['BODY'] = $lit['body'];
                    $row['SECTION'] = $lit['section'];
                }
            }
            $rows[$uid] = $row;
        }
        return $rows;
    }

    private function extractBodyLiterals($line)
    {
        $out = array();
        $offset = 0;
        $pattern = '/BODY(?:\.PEEK)?\[([^\]]*)\](?:<\d+>)?\s+\{(\d+)\}\r?\n/is';
        while (preg_match($pattern, $line, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $section = $m[1][0];
            $len = (int)$m[2][0];
            $start = $m[0][1] + strlen($m[0][0]);
            $body = substr($line, $start, $len);
            $out[] = array('section' => $section, 'body' => $body);
            $offset = $start + $len;
        }
        return $out;
    }
}
?>
