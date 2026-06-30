<?php
require_once __DIR__ . '/lib/bootstrap.php';
mbx_require_admin_file('include/header.php');
require_once __DIR__ . '/lib/common.php';
mbx_require_page_auth();

$db = mbx_db();
MailboxSync::ensureTables($db);
$isAdmin = mbx_is_admin();
$canManageCommonAccounts = mbx_can_manage_common_accounts();
$isRootAdmin = mbx_is_root_admin();
$myId = mbx_current_userid();
$message = '';
$error = '';

// 관리자 전용 화면. 일반 사용자는 본인 계정 화면(my_account.php)으로 안내
if (!$canManageCommonAccounts) {
    echo '<div id="contentwrapper"><div class="main_content"><div class="alert alert-warning" style="margin:20px">관리자만 접근할 수 있습니다. <a href="' . mbx_h(mbx_plugin_url('my_account.php')) . '" class="btn btn-primary btn-sm">내 계정 관리로 이동</a></div></div></div>';
    mbx_include_admin_file('include/side_m.php');
    include __DIR__ . '/footer.php';
    echo '</body></html>';
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mode = isset($_POST['mode']) ? $_POST['mode'] : '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        // 비관리자는 본인 소유 계정만 수정/삭제할 수 있다
        if (!$isAdmin && $id > 0) {
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
            $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
            // 소유자/공통: 관리자만 지정 가능, 비관리자는 본인 소유로 고정·공통 불가
            if ($isAdmin) {
                $ownerUserid = isset($_POST['owner_userid']) ? trim($_POST['owner_userid']) : '';
                $isCommon = isset($_POST['is_common']) ? 1 : 0;
            } else {
                $ownerUserid = $myId;
                $isCommon = 0;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('이메일 형식이 올바르지 않습니다.');
            }
            if ($mode === 'add') {
                if ($appPassword === '') {
                    throw new RuntimeException('앱 비밀번호가 필요합니다.');
                }
                $stmt = mbx_stmt($db, "INSERT INTO mailbox_accounts (email, display_name, imap_host, imap_port, smtp_host, smtp_port, app_password, provider, is_active, sort_order, owner_userid, is_common) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 'sssisissiisi', array($email, $display, $imapHost, $imapPort, $smtpHost, $smtpPort, $appPassword, $provider, $isActive, $sortOrder, $ownerUserid, $isCommon));
                mysqli_stmt_close($stmt);
                $message = '계정을 추가했습니다.';
            } else {
                if ($appPassword !== '') {
                    $stmt = mbx_stmt($db, "UPDATE mailbox_accounts SET email=?, display_name=?, imap_host=?, imap_port=?, smtp_host=?, smtp_port=?, app_password=?, provider=?, is_active=?, sort_order=?, owner_userid=?, is_common=? WHERE id=?", 'sssisissiisii', array($email, $display, $imapHost, $imapPort, $smtpHost, $smtpPort, $appPassword, $provider, $isActive, $sortOrder, $ownerUserid, $isCommon, $id));
                } else {
                    $stmt = mbx_stmt($db, "UPDATE mailbox_accounts SET email=?, display_name=?, imap_host=?, imap_port=?, smtp_host=?, smtp_port=?, provider=?, is_active=?, sort_order=?, owner_userid=?, is_common=? WHERE id=?", 'sssisisiisii', array($email, $display, $imapHost, $imapPort, $smtpHost, $smtpPort, $provider, $isActive, $sortOrder, $ownerUserid, $isCommon, $id));
                }
                mysqli_stmt_close($stmt);
                $message = '계정을 수정했습니다.';
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
            $stmt = mbx_stmt($db, "DELETE FROM mailbox_accounts WHERE id=?", 'i', array($id));
            mysqli_stmt_close($stmt);
            $message = '계정을 삭제했습니다.';
        } elseif ($mode === 'admin_add') {
            if (!$isRootAdmin) {
                throw new RuntimeException('admin 계정만 메일 관리자를 추가할 수 있습니다.');
            }
            mbx_add_admin($db, isset($_POST['admin_userid']) ? $_POST['admin_userid'] : '');
            $message = '메일 관리자를 추가했습니다.';
        } elseif ($mode === 'admin_del') {
            if (!$isRootAdmin) {
                throw new RuntimeException('admin 계정만 메일 관리자를 해제할 수 있습니다.');
            }
            mbx_remove_admin($db, isset($_POST['admin_userid']) ? $_POST['admin_userid'] : '');
            $message = '메일 관리자를 해제했습니다.';
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

$accounts = mbx_accounts($db);
$adminIds = mbx_admin_userids($db);
$members = $isAdmin ? mbx_member_users($db) : array();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = $editId ? mbx_get_account($db, $editId, false) : null;
if ($edit && !$isAdmin && (string)$edit['owner_userid'] !== $myId) {
    $edit = null; // 본인 계정이 아니면 수정 폼을 열지 않음
}
?>
<div id="contentwrapper">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul><li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li><li><a href="<?php echo mbx_h(mbx_plugin_url('index.php')); ?>">메일</a></li><li>계정 관리</li></ul>
    </div>
    <?php if ($message !== ''): ?><div class="alert alert-success"><?php echo mbx_h($message); ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo mbx_h($error); ?></div><?php endif; ?>
    <div class="row">
      <div class="col-sm-7">
        <h3>메일 계정</h3>
        <?php if (!$isAdmin): ?><div class="alert alert-info">본인 이메일 계정을 직접 등록·수정할 수 있습니다. 공통 이메일 지정은 관리자만 가능합니다.</div><?php endif; ?>
        <table class="table table-bordered table-hover">
          <thead><tr><th>이메일</th><th>표시명</th><th>소유자</th><th>공통</th><th>상태</th><th width="150">관리</th></tr></thead>
          <tbody>
          <?php if (!$accounts): ?>
            <tr><td colspan="6" class="text-center">등록된 계정이 없습니다.</td></tr>
          <?php endif; ?>
          <?php foreach ($accounts as $row): ?>
            <tr>
              <td><?php echo mbx_h($row['email']); ?></td>
              <td><?php echo mbx_h($row['display_name']); ?></td>
              <td><?php echo mbx_h($row['owner_userid'] !== '' ? $row['owner_userid'] : '-'); ?></td>
              <td><?php echo (int)$row['is_common'] ? '<span class="label label-success">공통</span>' : ''; ?></td>
              <td><?php echo (int)$row['is_active'] ? '사용' : '중지'; ?></td>
              <td>
                <a class="btn btn-xs btn-warning" href="<?php echo mbx_h(mbx_plugin_url('accounts.php?edit=' . (int)$row['id'])); ?>"><i class="fa fa-edit"></i></a>
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
      <?php if ($isAdmin): ?>
      <div class="col-sm-5">
        <h3><?php echo $edit ? '계정 수정' : '계정 추가'; ?></h3>
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
            <p class="help-block">제공자를 선택하면 아래 IMAP/SMTP 주소가 자동으로 채워집니다. 필요하면 직접 수정할 수 있습니다.</p>
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
          <div class="form-group">
            <label>소유자 (이 계정을 볼 사용자)</label>
            <?php $ownerVal = $edit ? (string)$edit['owner_userid'] : ''; ?>
            <select class="form-control" name="owner_userid">
              <option value="">— 소유자 없음 —</option>
              <?php $ownerFound = false; foreach ($members as $m): $uid = (string)$m['userid']; if ($uid === $ownerVal) { $ownerFound = true; } ?>
                <option value="<?php echo mbx_h($uid); ?>" <?php echo $uid === $ownerVal ? 'selected' : ''; ?>><?php echo mbx_h(trim($m['kor_name'] . ' ' . $m['eng_name']) . ' (' . $uid . ')' . ($m['email'] !== '' ? ' · ' . $m['email'] : '')); ?></option>
              <?php endforeach; ?>
              <?php if ($ownerVal !== '' && !$ownerFound): ?><option value="<?php echo mbx_h($ownerVal); ?>" selected><?php echo mbx_h($ownerVal); ?> (목록 외)</option><?php endif; ?>
            </select>
            <p class="help-block">이 사용자에게만 표시됩니다. 모두에게 보이려면 아래 ‘공통’을 체크하세요.</p>
          </div>
          <div class="row">
            <div class="col-xs-6 form-group"><label>정렬</label><input class="form-control" name="sort_order" value="<?php echo mbx_h($edit ? $edit['sort_order'] : '0'); ?>"></div>
            <div class="col-xs-6">
              <label>&nbsp;</label>
              <div class="checkbox"><label><input type="checkbox" name="is_active" <?php echo (!$edit || (int)$edit['is_active']) ? 'checked' : ''; ?>> 사용</label></div>
              <div class="checkbox"><label><input type="checkbox" name="is_common" <?php echo ($edit && (int)$edit['is_common']) ? 'checked' : ''; ?>> 공통 (모든 사용자에게 표시)</label></div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> 저장</button>
          <?php if ($edit): ?><a href="<?php echo mbx_h(mbx_plugin_url('accounts.php')); ?>" class="btn btn-default">취소</a><?php endif; ?>
        </form>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($isRootAdmin): ?>
    <hr>
    <div class="row">
      <div class="col-sm-7">
        <h3>메일 관리자</h3>
        <p class="help-block">여기에 등록된 아이디만 공통 이메일 지정과 계정 관리를 할 수 있습니다 (member_list의 division과 무관). 추후 관리자를 추가할 수 있습니다.</p>
        <?php if (!$adminIds): ?>
          <div class="alert alert-warning">아직 지정된 메일 관리자가 없습니다. 지정 전까지는 시스템 admin이 임시로 관리합니다. 아래에서 관리자를 추가하세요.</div>
        <?php endif; ?>
        <table class="table table-bordered">
          <thead><tr><th>관리자 아이디</th><th width="100">해제</th></tr></thead>
          <tbody>
          <?php if (!$adminIds): ?><tr><td colspan="2" class="text-center">없음</td></tr><?php endif; ?>
          <?php foreach ($adminIds as $aid): ?>
            <tr>
              <td><?php echo mbx_h($aid); ?></td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('관리자에서 해제하시겠습니까?');">
                  <input type="hidden" name="mode" value="admin_del"><input type="hidden" name="admin_userid" value="<?php echo mbx_h($aid); ?>">
                  <button class="btn btn-xs btn-danger" type="submit"><i class="fa fa-times"></i> 해제</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="col-sm-5">
        <h3>관리자 추가</h3>
        <form method="post">
          <input type="hidden" name="mode" value="admin_add">
          <div class="form-group" style="width:100%">
            <select class="form-control" name="admin_userid" required>
              <option value="">사용자 선택 (재직 중 admin 직원)</option>
              <?php foreach ($members as $m): $uid = (string)$m['userid']; ?>
                <option value="<?php echo mbx_h($uid); ?>"><?php echo mbx_h(trim($m['kor_name'] . ' ' . $m['eng_name']) . ' (' . $uid . ')'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary" type="submit"><i class="fa fa-user-plus"></i> 관리자 추가</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
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
