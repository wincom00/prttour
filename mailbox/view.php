<?php
require_once __DIR__ . '/lib/bootstrap.php';
mbx_require_admin_file('include/header.php');
require_once __DIR__ . '/lib/common.php';
mbx_require_page_auth();

$db = mbx_db();
$account = mbx_current_account($db);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;
$attachments = array();
$threadRows = array();

function mbx_view_subject_base($subject)
{
    $subject = trim((string)$subject);
    while (preg_match('/^\s*(?:re|fw|fwd)\s*:\s*/i', $subject)) {
        $subject = preg_replace('/^\s*(?:re|fw|fwd)\s*:\s*/i', '', $subject, 1);
    }
    $subject = preg_replace('/\s+/u', ' ', strtolower(trim($subject)));
    return $subject !== '' ? $subject : '(no subject)';
}

function mbx_view_thread_party(array $row)
{
    return '';
}

function mbx_view_thread_key(array $row)
{
    $threadId = isset($row['thread_id']) ? trim((string)$row['thread_id']) : '';
    return $threadId !== '' ? 'thread|' . $threadId : 'row|' . (int)$row['id'];
}

function mbx_view_body_score(array $row)
{
    $html = isset($row['body_html']) ? MimeParser::decodeEncodedBlob($row['body_html']) : '';
    if ($html !== '' && MimeParser::isDisplayableText($html)) {
        return 3;
    }
    $text = isset($row['body_text']) ? MimeParser::decodeEncodedBlob($row['body_text']) : '';
    if ($text !== '' && MimeParser::isDisplayableText($text)) {
        return 3;
    }
    $snippet = isset($row['snippet']) ? MimeParser::cleanPreviewText($row['snippet']) : '';
    return $snippet !== '' ? 1 : 0;
}

function mbx_view_has_body(array $row)
{
    return mbx_view_body_score($row) > 0;
}

function mbx_view_thread_label(array $row)
{
    $to = json_decode($row['to_addr'], true);
    $toLabel = (is_array($to) && isset($to[0]['email'])) ? $to[0]['email'] : '';
    $name = $row['folder_key'] === 'sent' ? ($toLabel !== '' ? '받는 사람: ' . $toLabel : '보낸메일') : ($row['from_name'] !== '' ? $row['from_name'] : $row['from_email']);
    $ts = strtotime((string)$row['mail_date']);
    $date = $ts ? date('m-d H:i', $ts) : mbx_date_label($row['mail_date']);
    $snippet = MimeParser::cleanPreviewText(($row['body_html'] !== '' ? $row['body_html'] : ($row['body_text'] !== '' ? $row['body_text'] : $row['snippet'])), 90);
    $score = mbx_view_body_score($row);
    $bodyLabel = $score >= 3 ? '' : ($score === 1 ? '미리보기' : '본문 없음');
    return trim($date . ' ' . $name . ($bodyLabel !== '' ? ' [' . $bodyLabel . ']' : '') . ($snippet !== '' ? ' - ' . $snippet : ''));
}

if ($account) {
    $row = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT * FROM mailbox_messages WHERE id=? AND account_id=?", 'ii', array($id, (int)$account['id'])));
    if ($row) {
        if (trim((string)$row['thread_id']) === '') {
            global $MBX_FOLDERS;
            $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
            $threadId = $sync->refreshThreadId((int)$row['id']);
            if ($threadId !== '') {
                $row['thread_id'] = $threadId;
            }
        }
        $threadKey = mbx_view_thread_key($row);
        $candidates = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_messages WHERE account_id=? ORDER BY mail_date DESC, uid DESC", 'i', array((int)$account['id'])));
        foreach ($candidates as $candidate) {
            if (mbx_view_thread_key($candidate) === $threadKey) {
                $threadRows[] = $candidate;
            }
        }
        if (count($threadRows) > 1 && mbx_view_body_score($row) === 0) {
            $bestScore = 0;
            $bestRow = null;
            foreach ($threadRows as $candidate) {
                $score = mbx_view_body_score($candidate);
                if ((int)$candidate['id'] !== (int)$row['id'] && $score > $bestScore) {
                    $bestScore = $score;
                    $bestRow = $candidate;
                    if ($score >= 3) {
                        break;
                    }
                }
            }
            if ($bestRow) {
                $row = $bestRow;
                $id = (int)$row['id'];
            }
        }
    }
    // Opening a message marks it read and syncs the flag back to the server.
    if ($row && (int)$row['is_read'] === 0) {
        global $MBX_FOLDERS;
        $sync = new MailboxSync($db, $account, $MBX_FOLDERS);
        try {
            $sync->markRead(array((int)$row['id']), true);
        } catch (Exception $e) {
            $stmt = mbx_stmt($db, "UPDATE mailbox_messages SET is_read=1 WHERE id=? AND account_id=?", 'ii', array((int)$row['id'], (int)$account['id']));
            mysqli_stmt_close($stmt);
        }
        $row['is_read'] = 1;
    }
    if ($row) {
        $attachments = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_attachments WHERE msg_id=? ORDER BY id ASC", 'i', array((int)$row['id'])));
    }
}
$to = $row ? json_decode($row['to_addr'], true) : array();
$cc = $row ? json_decode($row['cc_addr'], true) : array();
function mbx_addr_line($list) {
    $out = array();
    if (is_array($list)) {
        foreach ($list as $a) {
            $out[] = trim((isset($a['name']) ? $a['name'] : '') . ' <' . (isset($a['email']) ? $a['email'] : '') . '>');
        }
    }
    return implode(', ', $out);
}
?>
<div id="contentwrapper">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module"><ul><li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li><li><a href="<?php echo mbx_h(mbx_plugin_url('index.php')); ?>">메일</a></li><li>메일 보기</li></ul></div>
    <?php if (!$row): ?>
      <div class="alert alert-danger">메일을 찾을 수 없습니다.</div>
    <?php else: ?>
      <h3><?php echo mbx_h($row['subject'] !== '' ? $row['subject'] : '(제목 없음)'); ?></h3>
      <div class="btn-toolbar" style="margin-bottom:15px">
        <a class="btn btn-default" href="<?php echo mbx_h(mbx_plugin_url('compose.php?reply=' . (int)$row['id'])); ?>"><i class="fa fa-reply"></i> 답장</a>
        <a class="btn btn-default" href="<?php echo mbx_h(mbx_plugin_url('compose.php?forward=' . (int)$row['id'])); ?>"><i class="fa fa-share"></i> 전달</a>
        <button class="btn btn-danger" id="btnTrash"><i class="fa fa-trash"></i> <?php echo $row['folder_key'] === 'trash' ? '완전삭제' : '삭제'; ?></button>
        <a class="btn btn-default" href="<?php echo mbx_h(mbx_plugin_url('index.php?folder=' . urlencode($row['folder_key']))); ?>"><i class="fa fa-list"></i> 목록</a>
      </div>
      <table class="table table-bordered">
        <tr><th width="120">보낸 사람</th><td><?php echo mbx_h(trim($row['from_name'] . ' <' . $row['from_email'] . '>')); ?></td></tr>
        <tr><th>받는 사람</th><td><?php echo mbx_h(mbx_addr_line($to)); ?></td></tr>
        <?php if ($cc): ?><tr><th>참조</th><td><?php echo mbx_h(mbx_addr_line($cc)); ?></td></tr><?php endif; ?>
        <tr><th>날짜</th><td><?php echo mbx_h($row['mail_date']); ?></td></tr>
      </table>
      <iframe id="mbxBody" src="<?php echo mbx_h(mbx_plugin_url('api/body.php?id=' . (int)$row['id'] . '&v=' . (int)time())); ?>" sandbox="allow-same-origin" style="width:100%;border:1px solid #ddd;min-height:500px"></iframe>
      <?php if ($attachments): ?>
        <div class="panel panel-default mbx-attachments-panel"><div class="panel-heading"><i class="fa fa-paperclip"></i> 첨부파일 <?php echo count($attachments); ?>개</div><div class="panel-body">
          <?php foreach ($attachments as $att): ?>
            <?php
              $attId = (int)$att['id'];
              $attName = $att['filename'] ?: 'attachment';
              $attMime = isset($att['mime_type']) ? (string)$att['mime_type'] : '';
              $isImageAttachment = preg_match('/^image\//i', $attMime);
            ?>
            <span class="mbx-attachment-item">
              <?php if ($isImageAttachment): ?>
                <a class="mbx-attachment-preview" href="<?php echo mbx_h(mbx_plugin_url('api/attachment.php?id=' . $attId . '&inline=1')); ?>" target="_blank" rel="noopener">
                  <img src="<?php echo mbx_h(mbx_plugin_url('api/attachment.php?id=' . $attId . '&inline=1')); ?>" alt="<?php echo mbx_h($attName); ?>">
                </a>
              <?php endif; ?>
              <a class="btn btn-default btn-sm" href="<?php echo mbx_h(mbx_plugin_url('api/attachment.php?id=' . $attId)); ?>"><i class="fa fa-download"></i> <?php echo mbx_h($attName); ?> (<?php echo mbx_h(mbx_size($att['size_bytes'])); ?>)</a>
            </span>
          <?php endforeach; ?>
        </div></div>
      <?php endif; ?>
      <?php if (count($threadRows) > 1): ?>
        <div class="mbx-thread-stack">
          <?php foreach ($threadRows as $threadRow): ?>
            <?php if ((int)$threadRow['id'] === (int)$row['id']) { continue; } ?>
            <?php
              $threadName = $threadRow['folder_key'] === 'sent' ? '보낸메일' : ($threadRow['from_name'] !== '' ? $threadRow['from_name'] : $threadRow['from_email']);
              $threadTs = strtotime((string)$threadRow['mail_date']);
              $threadDate = $threadTs ? date('m-d H:i', $threadTs) : mbx_date_label($threadRow['mail_date']);
              $threadSnippet = MimeParser::cleanPreviewText(($threadRow['body_html'] !== '' ? $threadRow['body_html'] : ($threadRow['body_text'] !== '' ? $threadRow['body_text'] : $threadRow['snippet'])), 160);
              $threadState = mbx_view_body_score($threadRow) >= 3 ? '' : (mbx_view_body_score($threadRow) === 1 ? '미리보기' : '본문 없음');
            ?>
            <a class="mbx-thread-card" href="<?php echo mbx_h(mbx_plugin_url('view.php?id=' . (int)$threadRow['id'])); ?>">
              <span class="mbx-thread-avatar"><?php echo mbx_h(function_exists('mb_substr') ? mb_substr($threadName, 0, 1, 'UTF-8') : substr($threadName, 0, 1)); ?></span>
              <span class="mbx-thread-main">
                <strong><?php echo mbx_h($threadName); ?></strong>
                <?php if ($threadSnippet !== ''): ?><span><?php echo mbx_h($threadSnippet); ?></span><?php endif; ?>
                <?php if ($threadState !== ''): ?><em><?php echo mbx_h($threadState); ?></em><?php endif; ?>
              </span>
              <span class="mbx-thread-meta"><?php if ((int)$threadRow['has_attachment']): ?><i class="fa fa-paperclip"></i><?php endif; ?> <?php echo mbx_h($threadDate); ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php mbx_include_admin_file('include/side_m.php'); ?>
<style>
.mbx-attachment-item{display:inline-block;margin:0 8px 8px 0;vertical-align:top}
.mbx-attachment-preview{display:block;width:120px;height:90px;margin-bottom:6px;border:1px solid #ddd;background:#f8f8f8;text-align:center;overflow:hidden}
.mbx-attachment-preview img{max-width:100%;max-height:100%;object-fit:contain}
.mbx-attachment-preview.mbx-preview-broken{display:none}
.mbx-attachments-panel{margin-top:12px}
.mbx-thread-stack{margin-top:16px;border-top:1px solid #e5e5e5}
.mbx-thread-card{display:flex;align-items:center;gap:10px;padding:12px 8px;border-bottom:1px solid #e5e5e5;color:#333;text-decoration:none;background:#fff}
.mbx-thread-card:hover{background:#f7fbff;text-decoration:none}
.mbx-thread-avatar{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:#607d8b;color:#fff;font-weight:bold;flex:0 0 34px}
.mbx-thread-main{display:block;min-width:0;flex:1;color:#333}
.mbx-thread-main strong{display:block;margin-bottom:2px}
.mbx-thread-main span{display:block;color:#555;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mbx-thread-main em{font-style:normal;color:#999}
.mbx-thread-meta{color:#777;white-space:nowrap;font-size:12px}
</style>
<script>
$(document).on('click','#btnTrash',function(){
  $.post('<?php echo mbx_h(mbx_plugin_url('api/action.php')); ?>',{ids:[<?php echo (int)$id; ?>],op:'<?php echo ($row && $row['folder_key'] === 'trash') ? 'delete' : 'trash'; ?>'},function(r){
    if(r.status==='success') location.href='<?php echo mbx_h(mbx_plugin_url('index.php?folder=trash')); ?>'; else alert(r.message);
  },'json').fail(function(xhr){var msg='삭제 실패'; if(xhr.responseJSON&&xhr.responseJSON.message){msg=xhr.responseJSON.message;} alert(msg);});
});
$(document).on('error', '.mbx-attachment-preview img', function(){
  $(this).closest('.mbx-attachment-preview').addClass('mbx-preview-broken');
});
function mbxResizeBodyFrame(){
  var frame = $('#mbxBody')[0];
  if (!frame || !frame.contentWindow || !frame.contentWindow.document) return;
  try {
    var doc = frame.contentWindow.document;
    var body = doc.body || {};
    var root = doc.documentElement || {};
    var h = Math.max(body.scrollHeight || 0, body.offsetHeight || 0, root.scrollHeight || 0, root.offsetHeight || 0);
    $('#mbxBody').height(Math.max(500, h + 30));
  } catch(e) {}
}
$('#mbxBody').on('load', function(){
  mbxResizeBodyFrame();
  try {
    var $doc = $(this.contentWindow.document);
    var $meta = $doc.find('.mbx-fallback-meta').first();
    if ($meta.length) {
      var $cells = $('.table.table-bordered tr td');
      var from = $meta.data('from') || '';
      var to = $meta.data('to') || '';
      var date = $meta.data('date') || '';
      if (from) $cells.eq(0).text(from);
      if (to) $cells.eq(1).text(to);
      if (date) $cells.last().text(date);
    }
    $doc.find('img').on('load error', mbxResizeBodyFrame);
  } catch(e) {}
  setTimeout(mbxResizeBodyFrame, 300);
  setTimeout(mbxResizeBodyFrame, 1000);
  setTimeout(mbxResizeBodyFrame, 2500);
});
$(window).on('load resize', mbxResizeBodyFrame);
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
