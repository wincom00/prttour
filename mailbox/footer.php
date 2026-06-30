<?php
/**
 * mailbox/footer.php — 공통 푸터 (각 메일함 페이지의 </body> 직전에 include)
 *
 * 기능
 *  1) 상태메시지: 페이지에서 $mbx_status = '메시지' 를 정의해두면 로드 시 하단 토스트로 표시
 *  2) 전역 mbxToast(msg, onClick) 제공 — 어느 페이지/스크립트에서나 상태메시지 전송
 *  3) 자동 동기화 알림: 60초마다 백그라운드 sync 후 받은편지함 안읽음 수가 늘면 토스트
 */
require_once __DIR__ . '/lib/common.php';

$mbxUnreadInbox = 0;
try {
    $mbxFooterDb = mbx_db();
    $mbxFooterAcc = mbx_current_account($mbxFooterDb);
    if ($mbxFooterAcc) {
        $u = mbx_fetch_one_stmt(mbx_stmt($mbxFooterDb, "SELECT COUNT(*) AS c FROM mailbox_messages WHERE account_id=? AND folder_key='inbox' AND is_read=0", 'i', array((int)$mbxFooterAcc['id'])));
        $mbxUnreadInbox = (int)$u['c'];
    }
} catch (Exception $e) {
    $mbxUnreadInbox = 0;
}
$mbxStatusMsg = isset($mbx_status) ? trim((string)$mbx_status) : '';
?>
<style>
#mbxToastWrap{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:9999;text-align:center}
.mbx-toast{display:inline-block;background:#323a45;color:#fff;padding:12px 20px;border-radius:6px;box-shadow:0 3px 14px rgba(0,0,0,.28);margin-top:8px;cursor:pointer;font-size:14px;opacity:0;transition:opacity .25s,transform .25s;transform:translateY(10px)}
.mbx-toast.show{opacity:1;transform:translateY(0)}
.mbx-toast .fa{margin-right:8px;color:#7fb0ff}
.mbx-sidebar .badge-unread{background:#d9534f}
</style>
<script>
// 전역 상태메시지 토스트 — 어느 페이지에서나 mbxToast('메시지') 호출 가능
window.mbxToast = function(msg, onClick){
  var w = document.getElementById('mbxToastWrap');
  if(!w){ w = document.createElement('div'); w.id = 'mbxToastWrap'; document.body.appendChild(w); }
  var t = $('<div class="mbx-toast"><i class="fa fa-envelope"></i><span></span></div>');
  t.find('span').text(msg);
  if(onClick){ t.on('click', onClick); }
  $(w).append(t);
  setTimeout(function(){ t.addClass('show'); }, 20);
  setTimeout(function(){ t.removeClass('show'); setTimeout(function(){ t.remove(); }, 350); }, 8000);
};
$(function(){
<?php if ($mbxStatusMsg !== ''): ?>
  mbxToast(<?php echo json_encode($mbxStatusMsg, JSON_UNESCAPED_UNICODE); ?>);
<?php endif; ?>
});
</script>
