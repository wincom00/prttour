<?php
final class SmtpClient
{
    private $host;
    private $port;
    private $timeout;
    private $fp;

    public function __construct($host, $port, $timeout = 30)
    {
        $this->host = (string)$host;
        $this->port = (int)$port;
        $this->timeout = (int)$timeout;
    }

    public function sendXOAuth2($user, $accessToken, $from, array $toList, $rawMime)
    {
        if (!$toList) {
            throw new RuntimeException('SMTP recipient is empty.');
        }
        $this->connect();
        try {
            $this->ehlo();
            if ($this->port !== 465) {
                $this->command('STARTTLS', array(220));
                if (!stream_socket_enable_crypto($this->fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('SMTP STARTTLS failed.');
                }
                $this->ehlo();
            }
            $sasl = base64_encode('user=' . $user . "\x01" . 'auth=Bearer ' . $accessToken . "\x01\x01");
            $code = $this->command('AUTH XOAUTH2 ' . $sasl, array(235, 334), false);
            if ($code === 334) {
                fwrite($this->fp, "\r\n");
                $this->expect(array(235));
            }
            $this->command('MAIL FROM:<' . $this->addr($from) . '>', array(250));
            foreach ($toList as $to) {
                $this->command('RCPT TO:<' . $this->addr($to) . '>', array(250, 251));
            }
            $this->command('DATA', array(354));
            fwrite($this->fp, $this->dotStuff($rawMime) . "\r\n.\r\n");
            $this->expect(array(250));
            $this->command('QUIT', array(221), false);
        } catch (Exception $e) {
            $this->close();
            throw $e;
        }
        $this->close();
    }

    private function connect()
    {
        $errno = 0;
        $errstr = '';
        $scheme = $this->port === 465 ? 'ssl://' : 'tcp://';
        $this->fp = @stream_socket_client($scheme . $this->host . ':' . $this->port, $errno, $errstr, $this->timeout);
        if (!$this->fp) {
            throw new RuntimeException('SMTP connect failed: ' . $errstr);
        }
        stream_set_timeout($this->fp, $this->timeout);
        $this->expect(array(220));
    }

    private function ehlo()
    {
        $host = isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== '' ? $_SERVER['SERVER_NAME'] : 'localhost';
        $this->command('EHLO ' . $host, array(250));
    }

    private function command($cmd, array $expect, $throw = true)
    {
        fwrite($this->fp, $cmd . "\r\n");
        return $this->expect($expect, $throw);
    }

    private function expect(array $expect, $throw = true)
    {
        $line = '';
        $code = 0;
        while (!feof($this->fp)) {
            $part = fgets($this->fp);
            if ($part === false) {
                $meta = stream_get_meta_data($this->fp);
                throw new RuntimeException(!empty($meta['timed_out']) ? 'SMTP read timeout.' : 'SMTP read failed.');
            }
            $line .= $part;
            if (preg_match('/^(\d{3})([ -])/', $part, $m)) {
                $code = (int)$m[1];
                if ($m[2] === ' ') {
                    break;
                }
            }
        }
        if (!in_array($code, $expect, true) && $throw) {
            throw new RuntimeException('SMTP command failed: ' . trim($line));
        }
        return $code;
    }

    private function addr($email)
    {
        $email = trim((string)$email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid SMTP address: ' . $email);
        }
        return $email;
    }

    private function dotStuff($raw)
    {
        $raw = str_replace(array("\r\n", "\r"), "\n", (string)$raw);
        $raw = preg_replace('/^\./m', '..', $raw);
        return str_replace("\n", "\r\n", $raw);
    }

    private function close()
    {
        if ($this->fp) {
            fclose($this->fp);
            $this->fp = null;
        }
    }
}
?>