<?php
require_once __DIR__ . '/lib/bootstrap.php';
mbx_require_admin_file('include/header.php');
require_once __DIR__ . '/lib/common.php';
mbx_require_page_auth();

$db = mbx_db();
MailboxSync::ensureTables($db);
$myId = mbx_current_userid();
$message = '';
$error = '';

if ($myId === '') {
    $error = '로그인 정보를 확인할 수 없습니다.';
}

try {
    if ($error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $mode = isset($_POST['mode']) ? $_POST['mode'] : '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        // 본인 소유 계정만 수정/삭제 가능
        if ($id > 0) {
            $target = mbx_get_account($db, $id, false);
            if (!$target || (string)$target['owner_userid'] !== $myId) {
                throw new RuntimeException('본인 계정만 관리할 수 있습니다.');
            }
        }
        if ($mode === 'add' || $mode === 'edit') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $display = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
            $imapHost = isset($_POST['imap_host']) ? trim($_POST['imap_host']) : 'imap.gmail.com';
            $imapPort = isset($_POST['imap_port']) ? (int)$_POST['imap_port'] : 993;
            $smtpHost = isset($_POST['smtp_host']) ? trim($_POST['smtp_host']) : 'smtp.gmail.com';
            $smtpPort = isset($_POST['smtp_port']) ? (int)$_POST['smtp_port'] : 587;
            $appPassword = isset($_POST['app_password']) ? (string)$_POST['app_password'] : '';
            $provider = isset($_POST['provider']) ? strtolower(trim($_POST['provider'])) : 'gmail';
            if (!isset($GLOBALS['MBX_PROVIDERS'][$provider])) {
                $provider = 'gmail';
            }
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('이메일 형식이 올바르지 않습니다.');
            }
            if ($mode === 'add') {
                if ($appPassword === '') {
                    throw new RuntimeException('앱 비밀번호가 필요합니다.');
                }
                // 소유자는 본인으로 고정, 공통 지정은 불가(0)
                $stmt = mbx_stmt($db, "INSERT INTO mailbox_accounts (email, display_name, imap_host, imap_port, smtp_host, smtp_port, app_password, provider, is_active, sort_order, owner_userid, is_common) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0)", 'sssisissis', array($email, $display, $imapHost, $imapPort, $smtpHost, $smtpPort, $appPassword, $provider, $isActive, $myId));
                mysqli_stmt_close($stmt);
                $message = '내 계정을 추가했습니다.';
            } else {
                if ($appPassword !== '') {
                    $stmt = mbx_stmt($db, "UPDATE mailbox_accounts SET email=?, display_name=?, imap_host=?, imap_port=?, smtp_host=?, smtp_port=?, app_password=?, provider=?, is_active=? WHERE id=? AND owner_userid=?", 'sssisissiis', array($email, $display, $imapHost, $imapPort, $smtpHost, $smtpPort, $appPassword, $provider, $isActive, $id, $myId));
                } else {
                    $stmt = mbx_stmt($db, "UPDATE mailbox_accounts SET email=?, display_name=?, imap_host=?, imap_port=?, smtp_host=?, smtp_port=?, provider=?, is_active=? WHERE id=? AND owner_userid=?", 'sssisisiis', array($email, $display, $imapHost, $imapPort, $smtpHost, $smtpPort, $provider, $isActive, $id, $myId));
                }
                mysqli_stmt_close($stmt);
                $message = '내 계정을 수정했습니다.';
            }
        } elseif ($mode === 'del' && $id > 0) {
            $msgRows = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT id FROM mailbox_messages WHERE account_id=?", 'i', array($id)));
            $msgIds = array();
            foreach ($msgRows as $r) {
                $msgIds[] = (int)$r['id'];
            }
            if ($msgIds) {
                $ph = implode(',', array_fill(0, count($msgIds), '?'));
                $stmt = mbx_stmt($db, "DELETE FROM mailbox_attachments WHERE msg_id IN (" . $ph . ")", str_repeat('i', count($msgIds)), $msgIds);
                mysqli_stmt_close($stmt);
            }
            $stmt = mbx_stmt($db, "DELETE FROM mailbox_messages WHERE account_id=?", 'i', array($id));
            mysqli_stmt_close($stmt);
            $stmt = mbx_stmt($db, "DELETE FROM mailbox_folders WHERE account_id=?", 'i', array($id));
            mysqli_stmt_close($stmt);
            $stmt = mbx_stmt($db, "DELETE FROM mailbox_accounts WHERE id=? AND owner_userid=?", 'is', array($id, $myId));
            mysqli_stmt_close($stmt);
            $message = '내 계정을 삭제했습니다.';
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

$accounts = ($myId !== '') ? mbx_own_accounts($db) : array();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = $editId ? mbx_get_account($db, $editId, false) : null;
if ($edit && (string)$edit['owner_userid'] !== $myId) {
    $edit = null; // 본인 계정이 아니면 수정 폼을 열지 않음
}
?>
<div id="contentwrapper">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul><li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li><li><a href="<?php echo mbx_h(mbx_plugin_url('index.php')); ?>">메일</a></li><li>내 계정</li></ul>
    </div>
    <?php if ($message !== ''): ?><div class="alert alert-success"><?php echo mbx_h($message); ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo mbx_h($error); ?></div><?php endif; ?>
    <div class="row">
      <div class="col-sm-7">
        <h3>내 메일 계정</h3>
        <p class="help-block">본인 이메일 계정을 직접 등록·수정할 수 있습니다. 공통 이메일은 관리자가 지정합니다.</p>
        <table class="table table-bordered table-hover">
          <thead><tr><th>이메일</th><th>표시명</th><th>IMAP</th><th>상태</th><th width="150">관리</th></tr></thead>
          <tbody>
          <?php if (!$accounts): ?>
            <tr><td colspan="5" class="text-center">등록된 내 계정이 없습니다.</td></tr>
          <?php endif; ?>
          <?php foreach ($accounts as $row): ?>
            <tr>
              <td><?php echo mbx_h($row['email']); ?></td>
              <td><?php echo mbx_h($row['display_name']); ?></td>
              <td><?php echo mbx_h($row['imap_host'] . ':' . $row['imap_port']); ?></td>
              <td><?php echo (int)$row['is_active'] ? '사용' : '중지'; ?></td>
              <td>
                <a class="btn btn-xs btn-warning" href="<?php echo mbx_h(mbx_plugin_url('my_account.php?edit=' . (int)$row['id'])); ?>"><i class="fa fa-edit"></i></a>
                <button class="btn btn-xs btn-info btn-test" data-id="<?php echo (int)$row['id']; ?>"><i class="fa fa-plug"></i></button>
                <form method="post" style="display:inline" onsubmit="return confirm('삭제하시겠습니까?');">
                  <input type="hidden" name="mode" value="del"><input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                  <button class="btn btn-xs btn-danger" type="submit"><i class="fa fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="col-sm-5">
        <h3><?php echo $edit ? '내 계정 수정' : '내 계정 추가'; ?></h3>
        <?php
          $curProvider = $edit && isset($edit['provider']) && $edit['provider'] !== '' ? strtolower($edit['provider']) : 'gmail';
          if (!isset($MBX_PROVIDERS[$curProvider])) { $curProvider = 'gmail'; }
        ?>
        <div class="alert alert-info" id="mbxApppwHelp">
          <span class="mbx-apppw-label"><?php echo mbx_h($MBX_PROVIDERS[$curProvider]['apppw_label']); ?></span>
          <a class="mbx-apppw-link" href="<?php echo mbx_h($MBX_PROVIDERS[$curProvider]['apppw_url']); ?>" target="_blank" rel="noopener">앱 비밀번호 발급</a>
        </div>
        <form method="post">
          <input type="hidden" name="mode" value="<?php echo $edit ? 'edit' : 'add'; ?>">
          <input type="hidden" name="id" value="<?php echo $edit ? (int)$edit['id'] : 0; ?>">
          <div class="form-group"><label>이메일</label><input class="form-control" name="email" value="<?php echo mbx_h($edit ? $edit['email'] : ''); ?>" required></div>
          <div class="form-group"><label>표시명</label><input class="form-control" name="display_name" value="<?php echo mbx_h($edit ? $edit['display_name'] : ''); ?>"></div>
          <div class="form-group">
            <label>메일 제공자</label>
            <select class="form-control" name="provider" id="mbxProvider">
              <?php foreach ($MBX_PROVIDERS as $pkey => $pcfg): ?>
                <option value="<?php echo mbx_h($pkey); ?>" data-imap-host="<?php echo mbx_h($pcfg['imap_host']); ?>" data-imap-port="<?php echo (int)$pcfg['imap_port']; ?>" data-smtp-host="<?php echo mbx_h($pcfg['smtp_host']); ?>" data-smtp-port="<?php echo (int)$pcfg['smtp_port']; ?>" data-apppw-url="<?php echo mbx_h($pcfg['apppw_url']); ?>" data-apppw-label="<?php echo mbx_h($pcfg['apppw_label']); ?>" <?php echo $pkey === $curProvider ? 'selected' : ''; ?>><?php echo mbx_h($pcfg['label']); ?></option>
              <?php endforeach; ?>
            </select>
            <p class="help-block">제공자를 선택하면 아래 IMAP/SMTP 주소가 자동으로 채워집니다.</p>
          </div>
          <div class="row">
            <div class="col-xs-8 form-group"><label>IMAP host</label><input class="form-control" name="imap_host" value="<?php echo mbx_h($edit ? $edit['imap_host'] : 'imap.gmail.com'); ?>"></div>
            <div class="col-xs-4 form-group"><label>port</label><input class="form-control" name="imap_port" value="<?php echo mbx_h($edit ? $edit['imap_port'] : '993'); ?>"></div>
          </div>
          <div class="row">
            <div class="col-xs-8 form-group"><label>SMTP host</label><input class="form-control" name="smtp_host" value="<?php echo mbx_h($edit ? $edit['smtp_host'] : 'smtp.gmail.com'); ?>"></div>
            <div class="col-xs-4 form-group"><label>port</label><input class="form-control" name="smtp_port" value="<?php echo mbx_h($edit ? $edit['smtp_port'] : '587'); ?>"></div>
          </div>
          <div class="form-group"><label>앱 비밀번호<?php echo $edit ? ' (변경 시만 입력)' : ''; ?></label><input class="form-control" type="password" name="app_password" <?php echo $edit ? '' : 'required'; ?>></div>
          <div class="checkbox"><label><input type="checkbox" name="is_active" <?php echo (!$edit || (int)$edit['is_active']) ? 'checked' : ''; ?>> 사용</label></div>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> 저장</button>
          <?php if ($edit): ?><a href="<?php echo mbx_h(mbx_plugin_url('my_account.php')); ?>" class="btn btn-default">취소</a><?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>
<?php mbx_include_admin_file('include/side_m.php'); ?>
<script>
$(document).on('click', '.btn-test', function(){
  var btn = $(this);
  btn.prop('disabled', true);
  $.post('<?php echo mbx_h(mbx_plugin_url('api/account_test.php')); ?>', {account_id: btn.data('id')}, function(res){
    alert(res.status === 'success' ? '연결 성공' : res.message);
  }, 'json').fail(function(){ alert('연결 테스트 실패'); }).always(function(){ btn.prop('disabled', false); });
});
$(document).on('change', '#mbxProvider', function(){
  var opt = $(this).find('option:selected');
  var $form = $(this).closest('form');
  $form.find('[name="imap_host"]').val(opt.data('imap-host'));
  $form.find('[name="imap_port"]').val(opt.data('imap-port'));
  $form.find('[name="smtp_host"]').val(opt.data('smtp-host'));
  $form.find('[name="smtp_port"]').val(opt.data('smtp-port'));
  $('#mbxApppwHelp .mbx-apppw-label').text(opt.data('apppw-label'));
  $('#mbxApppwHelp .mbx-apppw-link').attr('href', opt.data('apppw-url'));
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
