<?php
// Server-side launcher for newsletter background worker.
// 크론 없이, 트리거(웹요청)에서 직접 "분리된" 백그라운드 프로세스로 워커를 실행한다.
//  - Windows : cmd /C start "" /B  (새 콘솔 창 없이 백그라운드)
//  - Linux   : nohup ... < /dev/null &  (php-fpm/apache 요청이 끝나도 살아남음)

function newsletterWorkerLog($message) {
    $log_file = __DIR__ . '/newsletter_logs/newsletter_trigger_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);

    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    file_put_contents($log_file, '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n", FILE_APPEND | LOCK_EX);
}

function newsletterTriggerLog($message) {
    newsletterWorkerLog($message);
}

function newsletterNormalizeQueueIds($raw_ids) {
    $queue_ids = array();

    if (!is_array($raw_ids)) {
        $raw_ids = explode(',', $raw_ids);
    }

    foreach ($raw_ids as $id) {
        $id = trim($id);
        if ($id !== '' && ctype_digit($id) && intval($id) > 0) {
            $queue_ids[intval($id)] = intval($id);
        }
    }

    return array_values($queue_ids);
}

function newsletterWorkerPhpPath() {
    $php_path = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? PHP_BINDIR . DIRECTORY_SEPARATOR . 'php.exe' : '/usr/bin/php';
    if (!file_exists($php_path)) {
        $php_path = PHP_BINARY ? PHP_BINARY : 'php';
    }

    return $php_path;
}

// 시스템 전체 일시중지 플래그 파일 (UI 의 "시스템 중지/재개" 버튼이 사용)
// 이 파일이 있으면 새 트리거가 워커를 실행하지 않는다.
function newsletterStopFlagFile() {
    return __DIR__ . '/newsletter_logs/newsletter_cron.stop';
}

function newsletterIsSystemStopped() {
    return file_exists(newsletterStopFlagFile());
}

// 루프백 HTTP 호출(서버 백그라운드 실행)의 인증 토큰. 없으면 생성해 저장한다.
function newsletterWorkerToken() {
    $file = __DIR__ . '/newsletter_logs/.worker_token';
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_file($file)) {
        $t = trim((string) @file_get_contents($file));
        if ($t !== '') {
            return $t;
        }
    }
    $t = function_exists('random_bytes') ? bin2hex(random_bytes(16)) : md5(uniqid(mt_rand(), true));
    @file_put_contents($file, $t, LOCK_EX);
    return $t;
}

// 셸(exec/popen)로 별도 프로세스를 분리 실행한다. 성공 시 true.
function newsletterStartWorkerCli($queue_ids = array()) {
    $php_path = newsletterWorkerPhpPath();
    $worker_script = __DIR__ . '/newsletter_background_worker.php';
    $is_win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

    $worker_args = '';
    if (count($queue_ids) > 0) {
        $worker_args = ' --queue_ids=' . implode(',', $queue_ids);
    }

    // exec/popen 이 disable_functions 로 막힌 호스트를 가드한다.
    $disabled = array_map('trim', explode(',', strtolower((string) ini_get('disable_functions'))));

    if ($is_win) {
        // 윈도우: 새 콘솔 창 없이 백그라운드로 분리 실행
        if (function_exists('popen') && !in_array('popen', $disabled, true)) {
            $cmd = 'cmd /C start "" /B "' . $php_path . '" "' . $worker_script . '"' . $worker_args . ' > nul 2>&1';
            newsletterWorkerLog('run(win): ' . $cmd);
            $h = @popen($cmd, 'r');
            if (is_resource($h)) {
                pclose($h);
                return true;
            }
        }
        return false;
    }

    // 리눅스/유닉스: 웹요청(php-fpm/apache)이 끝나도 살아남도록 nohup + stdin 분리로 완전히 떼어낸다.
    $cmd = 'nohup ' . escapeshellarg($php_path) . ' ' . escapeshellarg($worker_script) . $worker_args
         . ' < /dev/null > /dev/null 2>&1 &';

    if (function_exists('exec') && !in_array('exec', $disabled, true)) {
        newsletterWorkerLog('run(exec): ' . $cmd);
        @exec($cmd);
        return true;
    }
    if (function_exists('shell_exec') && !in_array('shell_exec', $disabled, true)) {
        newsletterWorkerLog('run(shell_exec): ' . $cmd);
        @shell_exec($cmd);
        return true;
    }
    if (function_exists('popen') && !in_array('popen', $disabled, true)) {
        newsletterWorkerLog('run(popen): ' . $cmd);
        $h = @popen($cmd, 'r');
        if (is_resource($h)) {
            pclose($h);
            return true;
        }
    }

    return false;
}

// exec/popen 이 막힌 호스트용: 자기 서버로 루프백 HTTP 요청을 쏘아 워커 엔드포인트를 깨운다.
// 워커 엔드포인트(newsletter_run_worker.php)는 ignore_user_abort + fastcgi_finish_request 로
// 응답을 먼저 끊은 뒤 "서버 백그라운드"에서 발송을 끝까지 진행한다. (작업 스케줄러/크론 불필요)
function newsletterFireBackgroundHttp($queue_ids = array()) {
    if (!function_exists('fsockopen')) {
        newsletterWorkerLog('http-fire 불가: fsockopen 비활성');
        return false;
    }

    $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
          || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
          || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');

    // 같은 디렉터리의 워커 엔드포인트 경로
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/email-sys/newsletter_trigger_worker.php';
    $base_dir = rtrim(str_replace('\\', '/', dirname($script_name)), '/');
    $path = $base_dir . '/newsletter_run_worker.php';

    $query = 'token=' . urlencode(newsletterWorkerToken());
    if (count($queue_ids) > 0) {
        $query .= '&queue_ids=' . urlencode(implode(',', $queue_ids));
    }
    $path .= '?' . $query;

    // host 에 포트가 붙어있을 수 있으니 분리
    $conn_host = $host;
    $port = $https ? 443 : 80;
    if (strpos($host, ':') !== false) {
        list($conn_host, $hp) = explode(':', $host, 2);
        if (is_numeric($hp)) {
            $port = intval($hp);
        }
    }
    $remote = ($https ? 'ssl://' : 'tcp://') . $conn_host;

    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($remote, $port, $errno, $errstr, 5);
    if (!$fp) {
        newsletterWorkerLog('http-fire 연결 실패: ' . $remote . ':' . $port . ' / ' . $errstr . ' (' . $errno . ')');
        return false;
    }

    $req  = 'GET ' . $path . " HTTP/1.1\r\n";
    $req .= 'Host: ' . $host . "\r\n";
    $req .= "User-Agent: newsletter-worker\r\n";
    $req .= "Connection: Close\r\n\r\n";
    fwrite($fp, $req);

    // 워커 엔드포인트는 시작 즉시 짧은 응답을 보내고 백그라운드로 넘어가므로,
    // 첫 줄만 읽어 "수신/시작" 을 확인한 뒤 연결을 닫는다. (긴 발송 루프는 기다리지 않음)
    stream_set_timeout($fp, 10);
    $first = fgets($fp, 256);
    fclose($fp);

    newsletterWorkerLog('http-fire: ' . $remote . ':' . $port . ' ' . $path . ' -> ' . trim((string) $first));
    return true;
}

// 백그라운드 워커를 실행한다. (1) 셸 분리 프로세스 → (2) 실패 시 루프백 HTTP. 성공 시 true.
function newsletterStartWorker($queue_ids = array()) {
    $queue_ids = newsletterNormalizeQueueIds($queue_ids);

    // 시스템 전체가 '중지' 상태면 실행하지 않는다.
    if (newsletterIsSystemStopped()) {
        newsletterWorkerLog('skip: 시스템 중지 플래그가 설정되어 있어 워커를 실행하지 않습니다.');
        return false;
    }

    // 1) 셸로 별도 프로세스 분리 (exec/popen 가능한 호스트)
    if (newsletterStartWorkerCli($queue_ids)) {
        return true;
    }

    // 2) 셸이 막힌 호스트: 자기 서버로 루프백 HTTP 호출 → 서버 백그라운드에서 실행
    return newsletterFireBackgroundHttp($queue_ids);
}
?>
