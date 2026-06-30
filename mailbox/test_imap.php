<?php
require_once __DIR__ . '/lib/bootstrap.php';
mbx_require_admin_file('include/header.php');
require_once __DIR__ . '/lib/common.php';
mbx_require_page_auth();

$db = mbx_db();
$account = null;
$result = null;
$error = '';
$accountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
if ($accountId > 0) {
    $account = mbx_get_account($db, $accountId, false);
    // 관리자가 아니면 본인 소유/공통 계정만 진단 허용
    if ($account && !mbx_is_admin() && (string)$account['owner_userid'] !== mbx_current_userid() && (int)$account['is_common'] !== 1) {
        $account = null;
        $error = '권한이 없습니다.';
    }
} elseif (mbx_is_admin() && isset($_GET['host'], $_GET['user'], $_GET['pass'])) {
    $account = array(
        'email' => $_GET['user'],
        'app_password' => $_GET['pass'],
        'imap_host' => $_GET['host'],
        'imap_port' => isset($_GET['port']) ? (int)$_GET['port'] : 993,
    );
}
if ($account) {
    try {
        $client = new ImapClient($account['imap_host'], (int)$account['imap_port']);
        $client->connect();
        $client->login($account['email'], $account['app_password']);
        $folders = $client->listFolders();
        $status = $client->select('INBOX');
        $uids = $client->uidSearch('ALL');
        $latest = array_slice($uids, -5);
        $headers = $latest ? $client->uidFetch(implode(',', $latest), '(FLAGS RFC822.SIZE BODY.PEEK[HEADER.FIELDS (FROM TO SUBJECT DATE MESSAGE-ID)])') : array();
        $parsed = null;
        if ($latest) {
            $one = $client->uidFetch((string)end($latest), '(BODY.PEEK[])');
            $first = reset($one);
            $parsed = MimeParser::parseMessage(isset($first['BODY']) ? $first['BODY'] : '');
        }
        $client->logout();
        $result = array('folders' => $folders, 'status' => $status, 'headers' => $headers, 'parsed' => $parsed);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
$accounts = mbx_visible_accounts($db);
?>
<div id="contentwrapper">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module"><ul><li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li><li><a href="<?php echo mbx_h(mbx_plugin_url('index.php')); ?>">메일</a></li><li>IMAP 진단</li></ul></div>
    <h3>IMAP 진단</h3>
    <form class="form-inline" method="get">
      <select name="account_id" class="form-control">
        <option value="">계정 선택</option>
        <?php foreach ($accounts as $acc): ?><option value="<?php echo (int)$acc['id']; ?>" <?php echo $accountId===(int)$acc['id']?'selected':''; ?>><?php echo mbx_h($acc['email']); ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-primary" type="submit"><i class="fa fa-play"></i> 테스트</button>
    </form>
    <hr>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo mbx_h($error); ?></div><?php endif; ?>
    <?php if ($result): ?>
      <h4>SELECT INBOX</h4><pre><?php echo mbx_h(print_r($result['status'], true)); ?></pre>
      <h4>Folders</h4><pre><?php echo mbx_h(print_r($result['folders'], true)); ?></pre>
      <h4>Latest headers</h4><pre><?php echo mbx_h(print_r($result['headers'], true)); ?></pre>
      <h4>Latest parsed message</h4><pre><?php echo mbx_h(print_r($result['parsed'], true)); ?></pre>
    <?php endif; ?>
  </div>
</div>
<?php mbx_include_admin_file('include/side_m.php'); ?>
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
