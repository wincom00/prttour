<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
mbx_require_admin_file('include/inc_base.php');
require_once dirname(__DIR__) . '/lib/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mbx_json(array('status' => 'error', 'message' => 'POST만 허용됩니다.'), 405);
}
mbx_require_api_auth();

// 목록(index.php)의 한 행은 대화(thread) 단위로 묶여 있어 전달되는 id 는 대표 1건뿐이다.
// 읽음/안읽음/삭제는 같은 폴더에 있는 같은 대화의 나머지 메일에도 적용해야
// 목록의 안읽음 표시·행 존재 여부가 실제 상태와 어긋나지 않는다.
// (폴더를 넘지 않는 이유: 목록이 한 폴더만 보여주므로 sent 사본까지 건드리면 안 된다.)
function mbx_expand_thread_ids(mysqli $db, $accountId, array $ids)
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) {
        return array();
    }
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $rows = mbx_fetch_all_stmt(mbx_stmt(
        $db,
        "SELECT DISTINCT m2.id FROM mailbox_messages m1
           JOIN mailbox_messages m2
             ON m2.account_id = m1.account_id
            AND m2.folder_key = m1.folder_key
            AND m2.thread_id  = m1.thread_id
          WHERE m1.account_id=? AND m1.thread_id<>'' AND m1.id IN (" . $ph . ")",
        'i' . str_repeat('i', count($ids)),
        array_merge(array((int)$accountId), $ids)
    ));
    $out = array();
    foreach ($ids as $id) {
        $out[$id] = $id;
    }
    foreach ($rows as $row) {
        $out[(int)$row['id']] = (int)$row['id'];
    }
    return array_values($out);
}

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
    $ids = mbx_expand_thread_ids($db, (int)$account['id'], $ids);
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
