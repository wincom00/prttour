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
    $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
    if ($accountId > 0) {
        $account = mbx_get_account($db, $accountId, false);
        if (!$account) {
            throw new RuntimeException('계정을 찾을 수 없습니다.');
        }
        // 관리자가 아니면 본인 소유/공통 계정만 테스트 허용
        if (!mbx_is_admin() && (string)$account['owner_userid'] !== mbx_current_userid() && (int)$account['is_common'] !== 1) {
            throw new RuntimeException('권한이 없습니다.');
        }
        $host = $account['imap_host'];
        $port = (int)$account['imap_port'];
        $user = $account['email'];
        $pass = $account['app_password'];
    } elseif (!mbx_is_admin()) {
        throw new RuntimeException('권한이 없습니다.');
    } else {
        $host = isset($_POST['host']) ? trim($_POST['host']) : 'imap.gmail.com';
        $port = isset($_POST['port']) ? (int)$_POST['port'] : 993;
        $user = isset($_POST['user']) ? trim($_POST['user']) : '';
        $pass = isset($_POST['pass']) ? (string)$_POST['pass'] : '';
    }
    $client = new ImapClient($host, $port);
    $client->connect();
    $client->login($user, $pass);
    $folders = $client->listFolders();
    $client->logout();
    mbx_json(array('status' => 'success', 'folders' => array_slice($folders, 0, 20)));
} catch (Exception $e) {
    mbx_json(array('status' => 'error', 'message' => $e->getMessage()), 500);
}
?>
