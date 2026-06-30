<?php
require_once __DIR__ . '/lib/bootstrap.php';
mbx_require_admin_file('include/header.php');
require_once __DIR__ . '/lib/common.php';
mbx_require_page_auth();

$db = mbx_db();
$accounts = mbx_visible_accounts($db);
$account = mbx_current_account($db);
$to = '';
$cc = '';
$subject = '';
$body = '';
$inReplyTo = '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

function mbx_compose_addr($name, $email)
{
    $name = trim((string)$name);
    $email = trim((string)$email);
    if ($name !== '') {
        return $name . ' <' . $email . '>';
    }
    return $email;
}

function mbx_compose_addr_list($json)
{
    $rows = json_decode((string)$json, true);
    if (!is_array($rows)) {
        return '';
    }
    $out = array();
    foreach ($rows as $row) {
        if (!is_array($row) || empty($row['email'])) {
            continue;
        }
        $out[] = mbx_compose_addr(isset($row['name']) ? $row['name'] : '', $row['email']);
    }
    return implode(', ', $out);
}

function mbx_compose_mail_date($value)
{
    $ts = strtotime((string)$value);
    if ($ts === false) {
        return (string)$value;
    }
    return date('D, M j, Y \a\t g:i A', $ts);
}

function mbx_compose_subject($prefix, $subject)
{
    $subject = trim((string)$subject);
    $subject = preg_replace('/^(' . preg_quote($prefix, '/') . ')\s*/i', '', $subject);
    return $prefix . ' ' . $subject;
}

function mbx_compose_quote_body(array $src)
{
    return $src['body_html'] !== '' ? $src['body_html'] : nl2br(mbx_h($src['body_text']));
}

function mbx_compose_reply_body(array $src)
{
    $from = mbx_compose_addr($src['from_name'], $src['from_email']);
    return '<br><br><div class="gmail_quote">'
        . '<div dir="ltr" class="gmail_attr">On ' . mbx_h(mbx_compose_mail_date($src['mail_date'])) . ', ' . mbx_h($from) . ' wrote:</div>'
        . '<blockquote class="gmail_quote" style="margin:0 0 0 .8ex;border-left:1px solid #ccc;padding-left:1ex">'
        . mbx_compose_quote_body($src)
        . '</blockquote></div>';
}

function mbx_compose_forward_body(array $src)
{
    $from = mbx_compose_addr($src['from_name'], $src['from_email']);
    $to = mbx_compose_addr_list($src['to_addr']);
    $cc = mbx_compose_addr_list($src['cc_addr']);
    $header = '---------- Forwarded message ---------<br>'
        . 'From: ' . mbx_h($from) . '<br>'
        . 'Date: ' . mbx_h(mbx_compose_mail_date($src['mail_date'])) . '<br>'
        . 'Subject: ' . mbx_h($src['subject']) . '<br>'
        . 'To: ' . mbx_h($to) . '<br>';
    if ($cc !== '') {
        $header .= 'Cc: ' . mbx_h($cc) . '<br>';
    }
    return '<br><br><div class="gmail_quote"><div dir="ltr" class="gmail_attr">' . $header . '</div><br>'
        . mbx_compose_quote_body($src)
        . '</div>';
}

$sourceId = isset($_GET['reply']) ? (int)$_GET['reply'] : (isset($_GET['forward']) ? (int)$_GET['forward'] : 0);
$isReply = isset($_GET['reply']);
$isForward = isset($_GET['forward']);
if ($account && $sourceId > 0) {
    $src = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT * FROM mailbox_messages WHERE id=? AND account_id=?", 'ii', array($sourceId, (int)$account['id'])));
    if ($src) {
        if ((int)$src['body_fetched'] === 0) {
            try {
                global $MBX_FOLDERS;
                $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
                $src = $sync->fetchBody($sourceId);
            } catch (Exception $e) {
            }
        }
        $subject = $isReply ? mbx_compose_subject('Re:', $src['subject']) : mbx_compose_subject('Fwd:', $src['subject']);
        if ($isReply) {
            $to = $src['from_email'];
            $inReplyTo = $src['message_id'];
            $body = mbx_compose_reply_body($src);
        } elseif ($isForward) {
            $body = mbx_compose_forward_body($src);
        }
    }
}
?>
<div id="contentwrapper">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module"><ul><li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li><li><a href="<?php echo mbx_h(mbx_plugin_url('index.php')); ?>">메일</a></li><li>메일 쓰기</li></ul></div>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo mbx_h($error); ?></div><?php endif; ?>
    <?php if (!$accounts): ?><div class="alert alert-warning">등록된 메일 계정이 없습니다. <a href="<?php echo mbx_h(mbx_plugin_url(mbx_can_manage_common_accounts() ? 'accounts.php' : 'my_account.php')); ?>">계정 관리</a>에서 계정을 추가하세요.</div><?php endif; ?>
    <form method="post" action="<?php echo mbx_h(mbx_plugin_url('api/send.php')); ?>" enctype="multipart/form-data" id="frmCompose">
      <div id="emailError" class="alert alert-danger" style="display:none"></div>
      <div class="form-group"><label>보내는 계정</label><select class="form-control" name="account_id"><?php foreach ($accounts as $acc): ?><option value="<?php echo (int)$acc['id']; ?>" <?php echo $account && (int)$acc['id']===(int)$account['id']?'selected':''; ?>><?php echo mbx_h($acc['email']); ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label>받는 사람</label><input class="form-control" name="to" value="<?php echo mbx_h($to); ?>" required></div>
      <div class="form-group"><label>참조</label><input class="form-control" name="cc" value="<?php echo mbx_h($cc); ?>"></div>
      <div class="form-group"><label>제목</label><input class="form-control" name="subject" value="<?php echo mbx_h($subject); ?>"></div>
      <input type="hidden" name="in_reply_to" value="<?php echo mbx_h($inReplyTo); ?>">
      <div class="form-group"><label>본문</label><textarea id="mbxEditor" name="body" class="form-control" rows="16"><?php echo mbx_h($body); ?></textarea></div>
      <div class="form-group">
        <label>첨부</label>
        <div id="attachBox"><input type="file" name="attach[]" class="form-control"></div>
        <button type="button" class="btn btn-default btn-sm" id="addAttach" style="margin-top:6px"><i class="fa fa-plus"></i> 추가</button>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> 보내기</button>
      <a href="<?php echo mbx_h(mbx_plugin_url('index.php')); ?>" class="btn btn-default">취소</a>
    </form>
  </div>
</div>
<?php mbx_include_admin_file('include/side_m.php'); ?>
<script src="/admin/js/tinymce/tinymce.min.js"></script>
<script>
tinymce.init({selector:'#mbxEditor',height:420,menubar:false,plugins:'link lists table',toolbar:'undo redo | bold italic underline | bullist numlist | link table'});
function mbxInvalidEmails(raw){
  var bad = [];
  $.each(String(raw || '').split(/[,;\r\n]+/), function(_, part){
    part = $.trim(part);
    if(!part){ return; }
    var m = part.match(/<([^<>]+)>$/);
    var email = $.trim(m ? m[1] : part).replace(/^["']|["']$/g, '');
    if(!/^[^\s@<>]+@[^\s@<>]+\.[^\s@<>]+$/.test(email)){ bad.push(part); }
  });
  return bad;
}
$(document).on('click','#addAttach',function(){
  if($('#attachBox input[type=file]').length>=<?php echo (int)MBX_MAX_ATTACH; ?>){ alert('첨부는 최대 <?php echo (int)MBX_MAX_ATTACH; ?>개까지 가능합니다.'); return; }
  $('#attachBox').append('<input type="file" name="attach[]" class="form-control" style="margin-top:6px">');
});
$(document).on('submit','#frmCompose',function(){
  tinymce.triggerSave();
  var bad = mbxInvalidEmails($('[name=to]').val()).concat(mbxInvalidEmails($('[name=cc]').val()));
  if(bad.length){
    $('#emailError').text('잘못된 이메일 주소: ' + bad.join(', ')).show();
    return false;
  }
  $('#emailError').hide();
  var ok=true;
  $('#attachBox input[type=file]').each(function(){
    if(this.files && this.files[0] && this.files[0].size > <?php echo (int)MBX_MAX_ATTACH_SIZE; ?>){ ok=false; }
  });
  if(!ok){ alert('첨부 파일은 20MB 이하만 가능합니다.'); return false; }
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
