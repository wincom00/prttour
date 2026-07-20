<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

@set_time_limit(0);
ignore_user_abort(true);

require_once __DIR__ . '/lib/bootstrap.php';
mbx_require_admin_file('include/dbconn.php');
require_once __DIR__ . '/lib/common.php';

function mbx_idle_log($message)
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

function mbx_idle_arg_value($name, $default = null)
{
    global $argv;
    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

function mbx_idle_has_arg($name)
{
    global $argv;
    return in_array('--' . $name, $argv, true);
}

function mbx_idle_int($value, $default, $min)
{
    $value = (int)$value;
    if ($value < $min) {
        return (int)$default;
    }
    return $value;
}

function mbx_idle_folder_keys($raw)
{
    $keys = array();
    foreach (explode(',', (string)$raw) as $key) {
        $key = trim($key);
        if ($key !== '' && preg_match('/^[a-z0-9_\-]+$/i', $key)) {
            $keys[$key] = $key;
        }
    }
    return $keys ? array_values($keys) : array('inbox');
}

function mbx_idle_acquire_lock(mysqli $db)
{
    $row = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT GET_LOCK('mbx_idle_worker', 0) AS l"));
    return $row && (int)$row['l'] === 1;
}

function mbx_idle_release_lock(mysqli $db)
{
    try {
        mbx_fetch_one_stmt(mbx_stmt($db, "SELECT RELEASE_LOCK('mbx_idle_worker') AS l"));
    } catch (Exception $e) {
    }
}

function mbx_idle_accounts(mysqli $db)
{
    return mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts WHERE is_active=1 ORDER BY sort_order ASC, id ASC"));
}

function mbx_idle_runtime_dir()
{
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

// 로그 파일을 소유자와 무관하게 기록 가능하도록 0666 으로 맞춘다.
// (소유자 wincom00 로 도는 cron/버튼이 실행되면 0666 으로 바뀌어, 이후엔
//  ubuntu 등 어떤 사용자가 실행해도 append 가 된다. 다른 사용자 소유라 chmod 가
//  안 되는 경우는 @ 로 조용히 무시한다.) 디렉터리도 0777 로 보정한다.
function mbx_idle_ensure_log_perms()
{
    $dir = mbx_idle_runtime_dir();
    @chmod($dir, 0777);
    foreach (array('worker_idle.log', 'worker_idle.err.log') as $name) {
        $path = $dir . '/' . $name;
        if (!file_exists($path)) {
            @touch($path);
        }
        @chmod($path, 0666);
    }
}

function mbx_idle_stop_file()
{
    return mbx_idle_runtime_dir() . '/worker_idle.stop';
}

function mbx_idle_stop_requested()
{
    return file_exists(mbx_idle_stop_file());
}
function mbx_idle_watch_folder(mysqli $db, array $account, MailboxSync $sync, $folderKey, $idleSeconds)
{
    $client = mbx_imap_connect($db, $account, max(30, (int)$idleSeconds + 10));
    try {
        $folderName = $sync->resolveFolderName($client, $folderKey);
        $client->select($folderName);
        $idle = $client->idleWait($idleSeconds);
        $client->logout();
        if (!empty($idle['changed'])) {
            $new = $sync->syncFolder($folderKey);
            mbx_idle_log($account['email'] . ' ' . $folderKey . ' changed, synced new=' . (int)$new);
            return true;
        }
    } catch (Exception $e) {
        $client->logout();
        throw $e;
    }
    return false;
}

mbx_idle_ensure_log_perms();
$db = mbx_db();
MailboxSync::ensureTables($db);
if (!mbx_idle_acquire_lock($db)) {
    mbx_idle_log('another worker is already running');
    exit(0);
}
register_shutdown_function(function () use ($db) {
    mbx_idle_release_lock($db);
});

$idleSeconds = mbx_idle_int(mbx_idle_arg_value('idle', defined('MBX_IDLE_TIMEOUT_SECONDS') ? MBX_IDLE_TIMEOUT_SECONDS : 55), 55, 5);
$fullPollSeconds = mbx_idle_int(mbx_idle_arg_value('poll', defined('MBX_IDLE_FULL_POLL_SECONDS') ? MBX_IDLE_FULL_POLL_SECONDS : 300), 300, 30);
$folderArg = mbx_idle_arg_value('folders', defined('MBX_IDLE_FOLDERS') ? MBX_IDLE_FOLDERS : 'inbox');
$idleFolders = mbx_idle_folder_keys($folderArg);
$maxLoops = mbx_idle_has_arg('once') ? 1 : mbx_idle_int(mbx_idle_arg_value('loops', 0), 0, 0);
$loop = 0;
$lastFullPollAt = 0;

global $MBX_FOLDERS;
if (!isset($MBX_FOLDERS) || !is_array($MBX_FOLDERS)) {
    $MBX_FOLDERS = array();
}

@unlink(mbx_idle_stop_file());
mbx_idle_log('worker started idle=' . $idleSeconds . ' poll=' . $fullPollSeconds . ' folders=' . implode(',', $idleFolders));
while (true) {
    if (mbx_idle_stop_requested()) {
        mbx_idle_log('stop requested');
        break;
    }
    // PHP 8.1+ 는 mysqli 오류를 예외로 던지므로 @mysqli_ping 로도 못 막는다. try 로 감싼다.
    // 원격 DB(wait_timeout·네트워크)로 유휴 연결이 끊기면 fatal 로 죽지 말고 깨끗이 종료해
    // 락을 반납하고, cron 워치독(매분 실행)이 곧 다시 띄우게 한다.
    $dbAlive = false;
    try {
        $dbAlive = @mysqli_ping($db);
    } catch (Throwable $pingError) {
        $dbAlive = false;
    }
    if (!$dbAlive) {
        // 원격 DB·네트워크로 유휴 연결이 끊긴 경우. 무한 루프 안에서 억지로 붙들기보다
        // 깨끗이 종료해 락을 반납하고, cron 워치독(매분 실행)이 곧 다시 띄우게 한다.
        mbx_idle_log('DB connection lost; exiting for watchdog restart');
        break;
    }

  try {
    $accounts = mbx_idle_accounts($db);
    if (!$accounts) {
        mbx_idle_log('no active accounts');
        sleep(30);
    }

    $doFullPoll = $lastFullPollAt <= 0 || (time() - $lastFullPollAt) >= $fullPollSeconds;
    foreach ($accounts as $account) {
        if (mbx_idle_stop_requested()) {
            break 2;
        }
        $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
        if ($doFullPoll) {
            try {
                $synced = $sync->syncAll();
                $total = 0;
                if (!empty($synced['new']) && is_array($synced['new'])) {
                    foreach ($synced['new'] as $count) {
                        $total += (int)$count;
                    }
                }
                mbx_idle_log($account['email'] . ' full poll synced new=' . $total);
            } catch (Exception $e) {
                mbx_idle_log($account['email'] . ' full poll failed: ' . $e->getMessage());
            }
        }

        foreach ($idleFolders as $folderKey) {
            if (mbx_idle_stop_requested()) {
                break 2;
            }
            try {
                mbx_idle_watch_folder($db, $account, $sync, $folderKey, $idleSeconds);
            } catch (Exception $e) {
                mbx_idle_log($account['email'] . ' ' . $folderKey . ' idle failed: ' . $e->getMessage());
                try {
                    $new = $sync->syncFolder($folderKey);
                    mbx_idle_log($account['email'] . ' ' . $folderKey . ' fallback synced new=' . (int)$new);
                } catch (Exception $syncError) {
                    mbx_idle_log($account['email'] . ' ' . $folderKey . ' fallback failed: ' . $syncError->getMessage());
                }
                sleep(5);
            }
        }
    }
    if ($doFullPoll) {
        $lastFullPollAt = time();
    }

    $loop++;
    if ($maxLoops > 0 && $loop >= $maxLoops) {
        break;
    }
  } catch (Throwable $e) {
    // 한 번의 루프에서 예상 못 한 오류가 나도 워커 자체는 죽이지 않는다.
    // 잠깐 쉬고 다음 루프로 넘어간다(치명적 DB 끊김은 위 mysqli_ping 에서 종료).
    mbx_idle_log('loop error: ' . $e->getMessage());
    sleep(5);
  }
}
mbx_idle_log('worker stopped');
?>