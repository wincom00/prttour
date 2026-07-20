<?php
if (php_sapi_name() === 'cli') {
    require_once dirname(__DIR__) . '/lib/bootstrap.php';
    mbx_require_admin_file('include/dbconn.php');
} else {
    require_once dirname(__DIR__) . '/lib/bootstrap.php';
    mbx_require_admin_file('include/inc_base.php');
}
require_once dirname(__DIR__) . '/lib/common.php';

try {
    $db = mbx_db();
    MailboxSync::ensureTables($db);
    $isCli = php_sapi_name() === 'cli';
    $key = isset($_GET['key']) ? (string)$_GET['key'] : '';
    if (!$isCli && !hash_equals(MBX_SYNC_KEY, $key)) {
        mbx_require_api_auth();
    }

    global $MBX_FOLDERS;
    $accounts = array();
    if ($isCli) {
        $accounts = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts WHERE is_active=1 ORDER BY sort_order ASC, id ASC"));
    } else {
        $account = mbx_current_account($db);
        if ($account) {
            $accounts[] = $account;
        }
    }
    if (!$accounts) {
        mbx_json(array('status' => 'error', 'message' => '등록된 메일 계정이 없습니다.'), 400);
    }
    $folder = isset($_GET['folder']) ? (string)$_GET['folder'] : '';
    $result = array();
    $errors = array();
    $unreadInbox = 0;
    foreach ($accounts as $account) {
        $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
        if ($folder !== '') {
            try {
                $sync->syncFolderList();
                if (!mbx_folder_allowed($db, $account, $folder, true)) {
                    throw new RuntimeException('Unknown folder.');
                }
                $result[$account['email']] = array($folder => $sync->syncFolder($folder));
            } catch (Throwable $e) {
                $result[$account['email']] = array($folder => 0);
                $errors[$account['email']] = array($folder => $e->getMessage());
            }
        } else {
            $synced = $sync->syncAll();
            $result[$account['email']] = $synced['new'];
            if (!empty($synced['errors'])) {
                $errors[$account['email']] = $synced['errors'];
            }
        }
        $u = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT COUNT(*) AS c FROM mailbox_messages WHERE account_id=? AND folder_key='inbox' AND is_read=0", 'i', array((int)$account['id'])));
        $unreadInbox += (int)$u['c'];
    }
    mbx_json(array('status' => 'success', 'new' => $result, 'errors' => $errors, 'unread_inbox' => $unreadInbox));
} catch (Throwable $e) {
    mbx_json(array('status' => 'error', 'message' => $e->getMessage()), 200);
}
?>
