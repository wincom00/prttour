<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
mbx_require_admin_file('include/inc_base.php');
require_once dirname(__DIR__) . '/lib/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mbx_json(array('status' => 'error', 'message' => 'POST만 허용됩니다.'), 405);
}
mbx_require_api_auth();

try {
    $db = mbx_db();
    $account = mbx_current_account($db);
    if (!$account) {
        mbx_json(array('status' => 'error', 'message' => '등록된 메일 계정이 없습니다.'), 400);
    }
    $ids = isset($_POST['ids']) ? $_POST['ids'] : array();
    if (!is_array($ids)) {
        $ids = array($ids);
    }
    $op = isset($_POST['op']) ? (string)$_POST['op'] : '';
    global $MBX_FOLDERS;
    $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
    if ($op === 'read') {
        $sync->markRead($ids, true);
    } elseif ($op === 'unread') {
        $sync->markRead($ids, false);
    } elseif ($op === 'trash') {
        $sync->moveToTrash($ids);
    } elseif ($op === 'delete') {
        $sync->deleteForever($ids);
    } else {
        mbx_json(array('status' => 'error', 'message' => '허용되지 않은 작업입니다.'), 400);
    }
    mbx_json(array('status' => 'success'));
} catch (Throwable $e) {
    mbx_json(array('status' => 'error', 'message' => $e->getMessage()), 200);
}
?>
