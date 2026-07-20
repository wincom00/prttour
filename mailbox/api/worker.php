<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
mbx_require_admin_file('include/inc_base.php');
require_once dirname(__DIR__) . '/lib/common.php';

mbx_require_api_auth();
if (!mbx_can_manage_common_accounts()) {
    mbx_json(array('status' => 'error', 'message' => 'Admin only.'), 403);
}

function mbx_worker_runtime_dir()
{
    $dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

// 로그 파일을 소유자와 무관하게 기록 가능하도록 0666 으로 맞춘다. FPM(wincom00)이
// 실행하면 소유자로서 chmod 가 되어, 이후 cron 이 ubuntu 등 다른 사용자로 돌아도
// append 가 된다. 다른 사용자 소유라 chmod 가 안 되는 경우는 @ 로 조용히 무시한다.
function mbx_worker_ensure_log_perms()
{
    $dir = mbx_worker_runtime_dir();
    @chmod($dir, 0777);
    foreach (array('worker_idle.log', 'worker_idle.err.log') as $name) {
        $path = $dir . '/' . $name;
        if (!file_exists($path)) {
            @touch($path);
        }
        @chmod($path, 0666);
    }
}

function mbx_worker_stop_file()
{
    return mbx_worker_runtime_dir() . '/worker_idle.stop';
}

function mbx_worker_log_file()
{
    return mbx_worker_runtime_dir() . '/worker_idle.log';
}

function mbx_worker_php_bin()
{
    // worker_idle.php has a hard "CLI only" guard, so we must launch the CLI
    // php binary, never the web SAPI (php-cgi.exe on Windows/Laragon, php-fpm on
    // Linux). PHP_BINARY under the web server points at that non-CLI binary, so
    // resolve the sibling/standard CLI executable instead.
    $isWin = stripos(PHP_OS, 'WIN') === 0;
    $exe = $isWin ? 'php.exe' : 'php';
    $candidates = array();
    // Windows: php.exe (CLI) always sits next to php-cgi.exe in the same folder.
    if (defined('PHP_BINARY') && PHP_BINARY !== '') {
        $candidates[] = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . $exe;
    }
    // PHP_BINDIR is the CLI install dir (e.g. /usr/bin on Linux).
    if (defined('PHP_BINDIR') && PHP_BINDIR !== '') {
        $candidates[] = rtrim(PHP_BINDIR, '\/') . DIRECTORY_SEPARATOR . $exe;
    }
    if (!$isWin) {
        // Linux: php-fpm lives in /usr/sbin while the CLI is /usr/bin; add the
        // common CLI locations (incl. a version-suffixed name) as fallbacks.
        $candidates[] = '/usr/bin/php';
        $candidates[] = '/usr/local/bin/php';
        $candidates[] = '/usr/bin/php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
    }
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    // Last resort: rely on PATH.
    return $exe;
}

// 매분 실행해도 안전한 cron 워치독 명령. worker_idle.php 는 이미 mbx_idle_worker
// 락으로 중복 실행을 막으므로(이미 돌면 즉시 종료), cron 이 매분 이 명령을 돌리면
// 워커가 어떤 이유로 죽어도 1분 안에 자동으로 다시 살아난다.
function mbx_worker_cron_command()
{
    $php = mbx_worker_php_bin();
    $worker = dirname(__DIR__) . '/worker_idle.php';
    $log = mbx_worker_log_file();
    return '* * * * * ' . $php . ' ' . $worker . ' >> ' . $log . ' 2>&1';
}

function mbx_worker_status(mysqli $db)
{
    $row = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT IS_USED_LOCK('mbx_idle_worker') AS lock_owner"));
    $running = $row && isset($row['lock_owner']) && $row['lock_owner'] !== null;
    // 락 기반 running 은 cron --once 에선 매분 깜빡이므로, "최근 동기화 시각"으로 건강 상태를 판정한다.
    // last_sync 는 워커가 NY 타임존 연결로 기록하고, 이 연결도 dbconn.php 가 NY 로 맞추므로 TIMESTAMPDIFF 가 정확하다.
    $syncRow = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT MAX(last_sync) AS last_sync, TIMESTAMPDIFF(SECOND, MAX(last_sync), NOW()) AS secs_ago FROM mailbox_folders"));
    $lastSync = isset($syncRow['last_sync']) ? $syncRow['last_sync'] : null;
    $secsAgo = ($lastSync !== null && isset($syncRow['secs_ago'])) ? (int)$syncRow['secs_ago'] : null;
    return array(
        'running' => $running,
        'lock_owner' => $running ? (int)$row['lock_owner'] : 0,
        'stop_requested' => file_exists(mbx_worker_stop_file()),
        'log_file' => mbx_worker_log_file(),
        'cron_command' => mbx_worker_cron_command(),
        'last_sync' => $lastSync,
        'last_sync_secs_ago' => $secsAgo,
    );
}

function mbx_worker_start(mysqli $db)
{
    $status = mbx_worker_status($db);
    if (!empty($status['running'])) {
        return $status;
    }
    @unlink(mbx_worker_stop_file());
    mbx_worker_ensure_log_perms();
    $php = mbx_worker_php_bin();
    $worker = dirname(__DIR__) . '/worker_idle.php';
    $log = mbx_worker_log_file();
    if (stripos(PHP_OS, 'WIN') === 0) {
        $cmd = 'start "" /B ' . escapeshellarg($php) . ' ' . escapeshellarg($worker) . ' >> ' . escapeshellarg($log) . ' 2>&1';
        pclose(popen($cmd, 'r'));
    } else {
        // 워커는 PHP-FPM 요청에서 띄우지만, 요청 종료·FPM 리로드 때 같은 프로세스
        // 그룹/세션으로 SIGHUP·SIGTERM 이 전달되면 워커가 같이 죽는다. 그래서
        //  - setsid: 새 세션으로 완전히 분리(컨트롤링 터미널·그룹에서 떼어냄)
        //  - nohup : SIGHUP 무시
        //  - < /dev/null: stdin 을 끊어 부모 파이프에 매이지 않게 함
        // 을 씌워 백그라운드로 돌린다. setsid 가 없는 환경(드묾)을 위해 nohup 단독 폴백.
        $tail = ' ' . escapeshellarg($worker) . ' < /dev/null >> ' . escapeshellarg($log) . ' 2>&1 &';
        $hasSetsid = trim((string)@shell_exec('command -v setsid 2>/dev/null')) !== '';
        if ($hasSetsid) {
            $cmd = 'setsid nohup ' . escapeshellarg($php) . $tail;
        } else {
            $cmd = 'nohup ' . escapeshellarg($php) . $tail;
        }
        pclose(popen($cmd, 'r'));
    }
    usleep(500000);
    return mbx_worker_status($db);
}

try {
    $db = mbx_db();
    MailboxSync::ensureTables($db);
    $op = isset($_POST['op']) ? (string)$_POST['op'] : (isset($_GET['op']) ? (string)$_GET['op'] : 'status');
    if ($op === 'start') {
        $status = mbx_worker_start($db);
        mbx_json(array('status' => 'success', 'worker' => $status));
    }
    if ($op === 'stop') {
        file_put_contents(mbx_worker_stop_file(), date('c'));
        $status = mbx_worker_status($db);
        mbx_json(array('status' => 'success', 'worker' => $status));
    }
    if ($op === 'status') {
        mbx_json(array('status' => 'success', 'worker' => mbx_worker_status($db)));
    }
    mbx_json(array('status' => 'error', 'message' => 'Unknown operation.'), 400);
} catch (Throwable $e) {
    mbx_json(array('status' => 'error', 'message' => $e->getMessage()), 200);
}
?>