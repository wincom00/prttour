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
<style>
.mbx-attach-tools{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.mbx-attach-drop{border:2px dashed #b8c2cc;background:#f8fafc;border-radius:6px;padding:18px;text-align:center;color:#455a64;transition:border-color .15s,background .15s,color .15s}
.mbx-attach-drop.is-dragover{border-color:#2f6fed;background:#eef4ff;color:#1a3e8c}
.mbx-attach-drop .fa-cloud-upload{display:block;font-size:24px;margin-bottom:6px;color:#2f6fed}
.mbx-attach-title{font-weight:bold;color:#333}
.mbx-attach-hint{display:block;margin-top:4px;font-size:12px;color:#777}
.mbx-attach-input{position:absolute;left:-9999px;width:1px;height:1px;opacity:0}
.mbx-attach-list{list-style:none;padding:0;margin:8px 0 0}
.mbx-attach-list li{display:flex;align-items:center;gap:7px;padding:6px 8px;border:1px solid #ddd;border-radius:4px;background:#fff;margin-top:5px}
.mbx-attach-list .mbx-attach-name{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.mbx-attach-list .mbx-attach-size{color:#777;font-size:12px;white-space:nowrap}
.mbx-attach-remove{border:0;background:transparent;color:#a94442;padding:2px 4px;line-height:1.2}
</style>
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
        <div id="mbxAttachDrop" class="mbx-attach-drop">
          <i class="fa fa-cloud-upload"></i>
          <span class="mbx-attach-title">&#54028;&#51068;&#51012; &#50668;&#44592;&#50640; &#45132;&#50612;&#45796; &#45459;&#51004;&#49464;&#50836;</span>
          <span class="mbx-attach-hint">&#46608;&#45716; &#50500;&#47000; &#48260;&#53948;&#51004;&#47196; &#49440;&#53469;&#54616;&#49464;&#50836;. &#52572;&#45824; <?php echo (int)MBX_MAX_ATTACH; ?>&#44060;, &#54028;&#51068;&#45817; <?php echo (int)round(MBX_MAX_ATTACH_SIZE / 1048576); ?>MB &#51060;&#54616;</span>
          <div class="mbx-attach-tools">
            <button type="button" class="btn btn-primary btn-sm" id="pickAttach"><i class="fa fa-folder-open"></i> &#54028;&#51068; &#49440;&#53469;</button>
            <button type="button" class="btn btn-default btn-sm" id="addAttach"><i class="fa fa-plus"></i> &#54028;&#51068; &#52628;&#44032;</button>
          </div>
        </div>
        <div id="attachBox"><input type="file" name="attach[]" class="form-control mbx-attach-input" multiple></div>
        <ul id="attachList" class="mbx-attach-list"></ul>
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
var mbxMaxAttach = <?php echo (int)MBX_MAX_ATTACH; ?>;
var mbxMaxAttachSize = <?php echo (int)MBX_MAX_ATTACH_SIZE; ?>;
function mbxAttachInputs(){ return $('#attachBox input[type=file]'); }
function mbxAttachFileCount(){
  var count = 0;
  mbxAttachInputs().each(function(){ count += this.files ? this.files.length : 0; });
  return count;
}
function mbxAttachSizeLabel(bytes){
  bytes = parseInt(bytes, 10) || 0;
  if(bytes >= 1048576){ return (bytes / 1048576).toFixed(1).replace(/\.0$/, '') + ' MB'; }
  if(bytes >= 1024){ return Math.ceil(bytes / 1024) + ' KB'; }
  return bytes + ' B';
}
function mbxRefreshAttachList(){
  var $list = $('#attachList').empty();
  mbxAttachInputs().each(function(inputIndex){
    var input = this;
    $.each(input.files || [], function(fileIndex, file){
      $('<li></li>')
        .append('<i class="fa fa-paperclip"></i>')
        .append($('<span class="mbx-attach-name"></span>').text(file.name))
        .append($('<span class="mbx-attach-size"></span>').text(mbxAttachSizeLabel(file.size)))
        .append($('<button type="button" class="mbx-attach-remove" title="Remove"><i class="fa fa-times"></i></button>').attr('data-input', inputIndex).attr('data-file', fileIndex))
        .appendTo($list);
    });
  });
}
function mbxAddAttachInput(){
  return $('<input type="file" name="attach[]" class="form-control mbx-attach-input" multiple>').appendTo('#attachBox');
}
function mbxEmptyAttachInput(){
  var $input = mbxAttachInputs().filter(function(){ return !this.files || this.files.length === 0; }).first();
  return $input.length ? $input : mbxAddAttachInput();
}
function mbxAddDroppedFiles(fileList){
  var files = Array.prototype.slice.call(fileList || []);
  if(!files.length){ return; }
  if(typeof DataTransfer === 'undefined'){
    alert('Drag and drop is not supported in this browser. Use the file select button.');
    return;
  }
  if(mbxAttachFileCount() + files.length > mbxMaxAttach){
    alert('Attachments are limited to ' + mbxMaxAttach + ' files.');
    return;
  }
  var tooLarge = false;
  $.each(files, function(_, file){ if(file.size > mbxMaxAttachSize){ tooLarge = true; } });
  if(tooLarge){ alert('Each attachment must be <?php echo (int)round(MBX_MAX_ATTACH_SIZE / 1048576); ?>MB or less.'); return; }
  var dt = new DataTransfer();
  $.each(files, function(_, file){ dt.items.add(file); });
  var $input = mbxEmptyAttachInput();
  $input[0].files = dt.files;
  mbxRefreshAttachList();
}
$(document).on('click','#pickAttach',function(){
  mbxEmptyAttachInput().trigger('click');
});
$(document).on('click','#addAttach',function(){
  if($('#attachBox input[type=file]').length>=mbxMaxAttach){ alert('Attachment fields are limited to ' + mbxMaxAttach + '.'); return; }
  mbxAddAttachInput().trigger('click');
});
$(document).on('change','#attachBox input[type=file]',mbxRefreshAttachList);
$(document).on('click','.mbx-attach-remove',function(){
  var input = mbxAttachInputs().get(parseInt($(this).attr('data-input'), 10));
  var removeIndex = parseInt($(this).attr('data-file'), 10);
  if(!input || !input.files){ return; }
  if(input.files.length <= 1 || typeof DataTransfer === 'undefined'){
    $(input).val('');
    if(mbxAttachInputs().length > 1 && !$(input).is(':first-child')){ $(input).remove(); }
    mbxRefreshAttachList();
    return;
  }
  var dt = new DataTransfer();
  $.each(input.files, function(index, file){ if(index !== removeIndex){ dt.items.add(file); } });
  input.files = dt.files;
  mbxRefreshAttachList();
});
$(document).on('dragenter dragover','#mbxAttachDrop',function(e){
  e.preventDefault();
  e.stopPropagation();
  $(this).addClass('is-dragover');
});
$(document).on('dragleave dragend drop','#mbxAttachDrop',function(e){
  e.preventDefault();
  e.stopPropagation();
  $(this).removeClass('is-dragover');
});
$(document).on('drop','#mbxAttachDrop',function(e){
  var original = e.originalEvent;
  mbxAddDroppedFiles(original && original.dataTransfer ? original.dataTransfer.files : []);
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
  var attachCount = 0;
  $('#attachBox input[type=file]').each(function(){
    var input = this;
    if(!input.files || input.files.length === 0){ return; }
    attachCount += input.files.length;
    $.each(input.files, function(_, file){
      if(file.size > mbxMaxAttachSize){ ok=false; }
    });
  });
  if(attachCount > mbxMaxAttach){ alert('Attachments are limited to ' + mbxMaxAttach + ' files.'); return false; }
  if(!ok){ alert('Each attachment must be <?php echo (int)round(MBX_MAX_ATTACH_SIZE / 1048576); ?>MB or less.'); return false; }
  $('#attachBox input[type=file]').each(function(){
    if(!this.files || this.files.length === 0){ $(this).prop('disabled', true); }
  });
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
