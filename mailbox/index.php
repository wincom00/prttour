<?php
require_once __DIR__ . '/lib/bootstrap.php';
mbx_require_admin_file('include/header.php');
require_once __DIR__ . '/lib/common.php';
mbx_require_page_auth();

$db = mbx_db();
MailboxSync::ensureTables($db);
$accounts = mbx_visible_accounts($db);
$account = mbx_current_account($db);
$folder = isset($_GET['folder']) && in_array($_GET['folder'], array('inbox','sent','trash'), true) ? $_GET['folder'] : 'inbox';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$rows = array();
$totalRows = 0;
$unread = array('inbox' => 0, 'sent' => 0, 'trash' => 0);

if ($account) {
    foreach ($unread as $k => $v) {
        $r = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT COUNT(*) AS c FROM mailbox_messages WHERE account_id=? AND folder_key=? AND is_read=0", 'is', array((int)$account['id'], $k)));
        $unread[$k] = (int)$r['c'];
    }
    $where = "account_id=? AND folder_key=?";
    $types = 'is';
    $params = array((int)$account['id'], $folder);
    if ($search !== '') {
        $where .= " AND (subject LIKE ? OR from_email LIKE ? OR from_name LIKE ? OR snippet LIKE ?)";
        $like = '%' . $search . '%';
        $types .= 'ssss';
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }
    $allRows = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_messages WHERE " . $where . " ORDER BY mail_date DESC, uid DESC", $types, $params));
    $threadRows = mbx_group_list_threads($allRows, $folder);
    $totalRows = count($threadRows);
    $rows = array_slice($threadRows, $offset, $limit);
}
$totalPages = max(1, (int)ceil($totalRows / $limit));
function mbx_list_subject_parts($subject)
{
    $subject = trim((string)$subject);
    $prefixes = array();
    while (preg_match('/^\s*((?:re|fw|fwd)\s*:)\s*/i', $subject, $m)) {
        $raw = strtolower(rtrim($m[1], ':'));
        $label = ($raw === 'fw' || $raw === 'fwd') ? 'Fwd:' : 'Re:';
        if (!in_array($label, $prefixes, true)) {
            $prefixes[] = $label;
        }
        $subject = preg_replace('/^\s*(?:re|fw|fwd)\s*:\s*/i', '', $subject, 1);
    }
    return array('prefixes' => $prefixes, 'subject' => trim($subject));
}

function mbx_list_thread_subject($subject)
{
    $parts = mbx_list_subject_parts($subject);
    $s = strtolower(trim($parts['subject']));
    $s = preg_replace('/\s+/u', ' ', $s);
    return $s !== '' ? $s : '(no subject)';
}

function mbx_list_thread_party(array $row, $folder)
{
    return '';
}

function mbx_group_list_threads(array $rows, $folder)
{
    $groups = array();
    $out = array();
    foreach ($rows as $row) {
        $threadId = isset($row['thread_id']) ? trim((string)$row['thread_id']) : '';
        $key = $threadId !== '' ? 'thread|' . $threadId : 'row|' . (int)$row['id'];
        if (!isset($groups[$key])) {
            $row['_thread_count'] = 1;
            $groups[$key] = count($out);
            $out[] = $row;
        } else {
            $idx = $groups[$key];
            $out[$idx]['_thread_count']++;
            if ((int)$row['is_read'] === 0) {
                $out[$idx]['is_read'] = 0;
            }
            if ((int)$row['has_attachment'] === 1) {
                $out[$idx]['has_attachment'] = 1;
            }
        }
    }
    return $out;
}
if (isset($_GET['sent'])) { $mbx_status = '메일을 보냈습니다.'; } // footer.php 가 토스트로 표시
?>
<style>
.mbx-unread td{font-weight:bold;background:#eaf2ff}
.mbx-unread td:first-child{box-shadow:inset 3px 0 0 #2f6fed}
.mbx-unread .mbx-subject{color:#1a3e8c}
.mbx-sidebar .btn{margin-bottom:8px}
.mbx-subject{display:inline;color:#333}.mbx-subject-prefix{color:#5f6368;font-weight:normal;margin-right:4px}.mbx-snippet{color:#777;font-weight:normal}.mbx-snippet:before{content:" - "}
.mbx-row{cursor:pointer}.mbx-actions{margin:10px 0}
.table.mailbox-list th:first-child,.table.mailbox-list td:first-child{width:52px;text-align:center;vertical-align:middle!important}
.table.mailbox-list td:first-child{cursor:pointer}
.table.mailbox-list input[type=checkbox]{width:18px;height:18px;margin:0;vertical-align:middle;cursor:pointer}
.mbx-row.mbx-selected td{background:#fff8d7!important}
.mbx-row.mbx-selected td:first-child{box-shadow:inset 3px 0 0 #f0ad4e}
.mbx-row.mbx-row-new td{animation:mbxNewMailFlash 3s ease-out}
@keyframes mbxNewMailFlash{0%{background:#fff0b3}100%{background:inherit}}
.mbx-badge-new{display:inline-block;margin-left:6px;padding:1px 7px;font-size:11px;font-weight:bold;color:#fff;background:#2f6fed;border-radius:10px;vertical-align:middle}
.mbx-thread-count{color:#5f6368;font-weight:normal;margin-left:4px}
.mbx-live-state{display:inline-block;margin-left:8px;color:#777;font-size:12px}
.mbx-live-state .fa{margin-right:4px}
</style>
<div id="contentwrapper">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module"><ul><li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li><li>메일</li><li>메일 목록</li></ul></div>
    <?php if (!$account): ?>
      <div class="alert alert-warning">등록된 메일 계정이 없습니다. <a href="<?php echo mbx_h(mbx_plugin_url(mbx_can_manage_common_accounts() ? 'accounts.php' : 'my_account.php')); ?>">계정 관리</a>에서 먼저 계정을 추가하세요.</div>
    <?php endif; ?>
    <div class="row">
      <div class="col-sm-12">
        <form class="form-inline" method="get">
          <input type="hidden" name="folder" value="<?php echo mbx_h($folder); ?>">
          <div class="form-group" style="width:70%"><input class="form-control" style="width:100%" name="search" value="<?php echo mbx_h($search); ?>" placeholder="제목, 보낸 사람, 내용 검색"></div>
          <button class="btn btn-default" type="submit"><i class="fa fa-search"></i> 검색</button>
        </form>
        <div class="mbx-actions">
          <span id="mbxLiveState" class="mbx-live-state"><i class="fa fa-refresh"></i><span>LIVE</span></span>
          <button class="btn btn-default btn-act" data-op="read" disabled><i class="fa fa-envelope-open-o"></i> 읽음</button>
          <button class="btn btn-default btn-act" data-op="unread" disabled><i class="fa fa-envelope-o"></i> 안읽음</button>
          <button class="btn btn-danger btn-act" data-op="<?php echo $folder === 'trash' ? 'delete' : 'trash'; ?>" disabled><i class="fa fa-trash"></i> <?php echo $folder === 'trash' ? '완전삭제' : '삭제'; ?></button>
        </div>
        <table class="table table-hover mailbox-list">
          <thead><tr><th width="35"><input type="checkbox" id="chkAll"></th><th width="30"></th><th width="180"><?php echo $folder==='sent'?'받는 사람':'보낸 사람'; ?></th><th>제목</th><th width="90">날짜</th><th width="80">크기</th></tr></thead>
          <tbody>
          <?php if (!$rows): ?><tr><td colspan="6" class="text-center">메일이 없습니다.</td></tr><?php endif; ?>
          <?php foreach ($rows as $row):
            $to = json_decode($row['to_addr'], true);
            $toLabel = isset($to[0]['email']) ? $to[0]['email'] : '';
            $previewSource = ($row['body_html'] !== null && $row['body_html'] !== '') ? $row['body_html'] : (string)$row['body_text'];
            $snippet = $previewSource !== '' ? MimeParser::cleanPreviewText($previewSource) : MimeParser::cleanPreviewText((string)$row['snippet']);
            $subjectParts = mbx_list_subject_parts($row['subject']);
            $senderLabel = $folder==='sent' ? $toLabel : ($row['from_name'] !== '' ? $row['from_name'] : $row['from_email']);
            $threadCount = isset($row['_thread_count']) ? (int)$row['_thread_count'] : 1;
            $subjectText = $subjectParts['subject'] !== '' ? $subjectParts['subject'] : '(제목 없음)';
          ?>
          <tr class="mbx-row <?php echo (int)$row['is_read'] ? '' : 'mbx-unread'; ?>" data-id="<?php echo (int)$row['id']; ?>" data-url="<?php echo mbx_h(mbx_plugin_url('view.php?id=' . (int)$row['id'])); ?>">
            <td><input type="checkbox" class="chk" value="<?php echo (int)$row['id']; ?>"></td>
            <td><?php echo (int)$row['has_attachment'] ? '<i class="fa fa-paperclip"></i>' : ''; ?></td>
            <td><?php echo mbx_h($senderLabel); ?><?php if ($threadCount > 1): ?><span class="mbx-thread-count">(<?php echo $threadCount; ?>)</span><?php endif; ?></td>
            <td><span class="mbx-subject"><?php echo mbx_h($row['subject'] !== '' ? $row['subject'] : '(제목 없음)'); ?><?php if (!(int)$row['is_read']): ?><span class="mbx-badge-new">안읽음</span><?php endif; ?></span><?php if ($snippet !== ''): ?><span class="mbx-snippet"><?php echo mbx_h($snippet); ?></span><?php endif; ?></td>
            <td><?php echo mbx_h(mbx_date_label($row['mail_date'])); ?></td>
            <td><?php echo mbx_h(mbx_size($row['msg_size'])); ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php if ($totalPages > 1): ?><ul class="pagination mbx-pagination">
          <?php for ($i=max(1,$page-5); $i<=min($totalPages,$page+5); $i++): ?><li class="<?php echo $i===$page?'active':''; ?>"><a href="<?php echo mbx_h(mbx_plugin_url('index.php?folder=' . urlencode($folder) . '&page=' . $i . '&search=' . urlencode($search))); ?>"><?php echo $i; ?></a></li><?php endfor; ?>
        </ul><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php mbx_include_admin_file('include/side_m.php'); ?>
<script>
var mbxListFolder = <?php echo json_encode($folder); ?>;
var mbxListPage = <?php echo (int)$page; ?>;
var mbxListSearch = <?php echo json_encode($search); ?>;
var mbxListRefreshing = false;
var mbxListAutoSyncing = false;
var mbxListAutoTimer = null;
var mbxListSyncUrl = <?php echo json_encode(mbx_plugin_url('api/sync.php')); ?>;
var mbxListLastUnread = <?php echo (int)$unread['inbox']; ?>;
function selectedIds(){var ids=[];$('.chk:checked').each(function(){ids.push($(this).val());});return ids;}
function refreshButtons(){
  var total = $('.chk').length;
  var checked = $('.chk:checked').length;
  $('.btn-act').prop('disabled', checked===0);
  $('.mbx-row').each(function(){
    $(this).toggleClass('mbx-selected', $(this).find('.chk').prop('checked'));
  });
  $('#chkAll').prop('checked', total > 0 && checked === total).prop('indeterminate', checked > 0 && checked < total);
}
function mbxCurrentRowIds(){
  var ids = {};
  $('.mailbox-list tbody .mbx-row').each(function(){
    ids[String($(this).data('id'))] = true;
  });
  return ids;
}
window.mbxInboxRefresh = function(opts){
  opts = opts || {};
  if(mbxListRefreshing){ return; }
  mbxListRefreshing = true;
  var before = mbxCurrentRowIds();
  var keepSelected = selectedIds();
  $.getJSON('<?php echo mbx_h(mbx_plugin_url('api/list.php')); ?>', {
    folder: mbxListFolder,
    page: mbxListPage,
    search: mbxListSearch,
    _: (new Date()).getTime()
  }, function(r){
    if(!r || r.status !== 'success'){ return; }
    var $table = $('.mailbox-list');
    $table.find('tbody').html(r.rows_html || '');
    $('.mbx-pagination').remove();
    if(r.pagination_html){ $table.after(r.pagination_html); }
    $.each(keepSelected, function(_, id){
      $('.mailbox-list .chk[value="' + id + '"]').prop('checked', true);
    });
    mbxListUpdateUnreadBadge(parseInt(r.unread_inbox, 10) || 0);
    mbxFormatListSubjects();
    refreshButtons();
    $table.find('.mbx-row').each(function(){
      var id = String($(this).data('id'));
      if(id && !before[id]){
        $(this).addClass('mbx-row-new');
      }
    });
    if(opts.toast && window.mbxToast){
      mbxToast(opts.toast);
    }
  }).always(function(){
    mbxListRefreshing = false;
  });
};
function mbxListSetLiveState(text, spinning){
  var $state = $('#mbxLiveState');
  $state.find('span').text(text);
  $state.find('.fa').toggleClass('fa-spin', !!spinning);
}
function mbxListNewCount(r){
  var n = 0;
  if(r && r.new){
    $.each(r.new, function(_, folders){
      if(folders && folders[mbxListFolder]){
        n += parseInt(folders[mbxListFolder], 10) || 0;
      } else if(mbxListFolder === 'inbox' && folders && folders.inbox) {
        n += parseInt(folders.inbox, 10) || 0;
      }
    });
  }
  return n;
}
function mbxListUpdateUnreadBadge(n){
  var $links = $('.mbx-sidebar a').filter(function(){
    return String($(this).attr('href') || '').indexOf('folder=inbox') !== -1;
  });
  $links.each(function(){
    var $link = $(this);
    var $badge = $link.find('.badge-unread');
    if(n > 0){
      if(!$badge.length){ $badge = $('<span class="badge badge-unread"></span>').appendTo($link); }
      $badge.text(n);
    } else {
      $badge.remove();
    }
  });
}
function mbxListAutoSync(manual){
  if(mbxListAutoSyncing || mbxListRefreshing || !mbxListSyncUrl){ return; }
  mbxListAutoSyncing = true;
  mbxListSetLiveState('SYNC', true);
  var beforeUnread = mbxListLastUnread;
  $.getJSON(mbxListSyncUrl, {
    folder: mbxListFolder,
    _: (new Date()).getTime()
  }, function(r){
    if(!r || r.status !== 'success'){
      mbxListSetLiveState('ERR', false);
      return;
    }
    var unread = parseInt(r.unread_inbox, 10) || 0;
    var newCount = mbxListNewCount(r);
    mbxListLastUnread = unread;
    mbxListUpdateUnreadBadge(unread);
    window.mbxInboxRefresh({
      response: r,
      toast: newCount > 0 && window.mbxToast ? '새 메일 ' + newCount + '통이 도착했습니다.' : ''
    });
    if(manual && newCount <= 0 && unread === beforeUnread && window.mbxToast){
      mbxToast('메일함을 업데이트했습니다.');
    }
  }).fail(function(){
    mbxListSetLiveState('ERR', false);
  }).always(function(){
    mbxListAutoSyncing = false;
    if(!mbxListRefreshing){ mbxListSetLiveState('LIVE', false); }
  });
}
function mbxFormatListSubjects(){
  $('.mbx-subject').each(function(){
    var $s = $(this);
    if($s.data('mbxFormatted')){ return; }
    var $badge = $s.find('.mbx-badge-new').detach();
    var text = $.trim($s.text());
    text = $.trim(text.replace(/안읽음/g, '').replace(/\?덉씫\?\?/g, ''));
    var prefixes = [];
    var m;
    while((m = text.match(/^\s*((?:re|fw|fwd)\s*:)\s*/i))){
      var raw = m[1].replace(':','').toLowerCase();
      var label = (raw === 'fw' || raw === 'fwd') ? 'Fwd:' : 'Re:';
      if($.inArray(label, prefixes) === -1){ prefixes.push(label); }
      text = $.trim(text.replace(/^\s*(?:re|fw|fwd)\s*:\s*/i, ''));
    }
    if(!text){ text = '(제목 없음)'; }
    $s.empty();
    $.each(prefixes, function(_, prefix){
      $('<span class="mbx-subject-prefix"></span>').text(prefix).appendTo($s);
    });
    $('<span class="mbx-subject-text"></span>').text(text).appendTo($s);
    if($badge.length){ $s.append($badge); }
    $s.data('mbxFormatted', true);
  });
}
$(mbxFormatListSubjects);
$(refreshButtons);
$(document).on('click','.mbx-row',function(e){if($(e.target).closest('input,button,a').length || $(e.target).closest('td').is(':first-child')) return; location.href=$(this).data('url');});
$(document).on('click','.mailbox-list tbody td:first-child',function(e){
  if($(e.target).is('input')) return;
  e.preventDefault();
  e.stopPropagation();
  var chk = $(this).find('.chk');
  chk.prop('checked', !chk.prop('checked')).trigger('change');
});
$(document).on('change','#chkAll',function(){$('.chk').prop('checked',this.checked);refreshButtons();});
$(document).on('change','.chk',refreshButtons);
$(document).on('click','.btn-act',function(){var ids=selectedIds(); if(!ids.length)return; $.post('<?php echo mbx_h(mbx_plugin_url('api/action.php')); ?>',{ids:ids,op:$(this).data('op')},function(r){ if(r.status==='success') location.reload(); else alert(r.message);},'json').fail(function(xhr){var msg='작업 실패'; if(xhr.responseJSON&&xhr.responseJSON.message){msg=xhr.responseJSON.message;} alert(msg);});});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
