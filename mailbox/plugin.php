<?php
if (!defined('MBX_PLUGIN_LOADED')) {
    define('MBX_PLUGIN_LOADED', true);
    require_once __DIR__ . '/lib/bootstrap.php';

    function mbx_plugin_manifest()
    {
        $mailboxLabel = json_decode('"\\uba54\\uc77c\\ud568"', true);
        $manifest = array(
            'id' => 'mailbox',
            'name' => $mailboxLabel,
            'label' => $mailboxLabel,
            'version' => '1.0.0',
            'hooks' => array('sidebar', 'sidebar_script'),
            'tables' => array(
                'mailbox_accounts',
                'mailbox_admins',
                'mailbox_folders',
                'mailbox_messages',
                'mailbox_attachments',
            ),
        );

        $jsonPath = __DIR__ . '/plugin.json';
        if (file_exists($jsonPath)) {
            $decoded = json_decode(file_get_contents($jsonPath), true);
            if (is_array($decoded) && (!isset($decoded['id']) || $decoded['id'] === 'mailbox')) {
                $manifest = array_merge($manifest, $decoded);
            }
        }

        $rootMode = mbx_root_mode();
        $pluginDir = $rootMode === 'admin' ? 'admin/mailbox' : 'mailbox';
        $manifest['id'] = 'mailbox';
        $manifest['root_path'] = __DIR__;
        $manifest['root'] = mbx_plugin_web_root();
        $manifest['web_root'] = mbx_plugin_web_root();
        $manifest['entry'] = mbx_plugin_url('index.php');
        $manifest['install'] = mbx_plugin_url('install.php');
        $manifest['delete_batch'] = $pluginDir . '/delete_mailbox_plugin.cmd';
        $manifest['delete_changes_batch'] = $pluginDir . '/delete_mailbox_changes.cmd';
        $manifest['root_mode'] = $rootMode;
        $manifest['plugin_dir'] = $pluginDir;

        return $manifest;
    }

    function mbx_plugin_current_path()
    {
        return isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    }

    function mbx_plugin_is_active_request($requestPath = null)
    {
        $requestPath = $requestPath === null ? mbx_plugin_current_path() : str_replace('\\', '/', (string)$requestPath);
        $webRoot = rtrim(mbx_plugin_web_root(), '/') . '/';
        return strpos($requestPath, $webRoot) !== false || strpos($requestPath, '/admin/mailbox/') !== false || strpos($requestPath, '/mailbox/') !== false;
    }

    function mbx_plugin_prepare_sidebar(array $context = array())
    {
        $state = array(
            'active' => false,
            'hide_default_menu' => false,
            'ready' => false,
            'accounts' => array(),
            'account' => null,
            'folder' => 'inbox',
            'unread' => array('inbox' => 0, 'sent' => 0, 'trash' => 0),
        );

        // 메일함 메뉴를 볼 수 있는 사용자에게만 준비한다(데모 모드면 지정 계정만).
        // 메일박스 안/밖 어디서나 메인 메뉴 아래에 메일함 메뉴를 붙이기 위해
        // 활성 요청(active) 여부와 무관하게 준비를 진행한다.
        if (!mbx_plugin_menu_entry_visible()) {
            return $state;
        }

        $requestPath = isset($context['request_path']) ? $context['request_path'] : null;
        // active: 메일박스 페이지 여부(폴더 강조·자동 동기화 판단용). 기본 메뉴는 숨기지 않는다.
        $state['active'] = mbx_plugin_is_active_request($requestPath);
        $state['hide_default_menu'] = false;
        $commonPath = __DIR__ . '/lib/common.php';
        if (!function_exists('mbx_visible_accounts') && file_exists($commonPath)) {
            require_once $commonPath;
        }
        if (!function_exists('mbx_visible_accounts')) {
            return $state;
        }

        try {
            $db = mbx_db();
            $state['accounts'] = isset($context['accounts']) && is_array($context['accounts']) ? $context['accounts'] : mbx_visible_accounts($db);
            $state['account'] = isset($context['account']) && is_array($context['account']) ? $context['account'] : mbx_current_account($db);
            if (isset($context['folder']) && in_array($context['folder'], array('inbox', 'sent', 'trash'), true)) {
                $state['folder'] = $context['folder'];
            } elseif (isset($_GET['folder']) && in_array($_GET['folder'], array('inbox', 'sent', 'trash'), true)) {
                $state['folder'] = $_GET['folder'];
            } elseif (isset($context['row']) && is_array($context['row']) && isset($context['row']['folder_key']) && in_array($context['row']['folder_key'], array('inbox', 'sent', 'trash'), true)) {
                $state['folder'] = $context['row']['folder_key'];
            }
            if (isset($context['unread']) && is_array($context['unread'])) {
                $state['unread'] = array_merge($state['unread'], $context['unread']);
            } elseif ($state['account']) {
                foreach ($state['unread'] as $k => $v) {
                    $unreadRow = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT COUNT(*) AS c FROM mailbox_messages WHERE account_id=? AND folder_key=? AND is_read=0", 'is', array((int)$state['account']['id'], $k)));
                    $state['unread'][$k] = isset($unreadRow['c']) ? (int)$unreadRow['c'] : 0;
                }
            }
            $state['ready'] = true;
        } catch (Throwable $e) {
            $state['ready'] = false;
        }

        return $state;
    }

    // ERP 메인 메뉴(side_m.php)에 항상 노출되는 "메일함" 진입 항목 표시 여부.
    // 메일박스 안에서뿐 아니라 다른 메인 메뉴에서도 공통으로 보이게 하기 위한 것.
    function mbx_plugin_menu_entry_visible()
    {
        $commonPath = __DIR__ . '/lib/common.php';
        if (!function_exists('mbx_current_userid') && file_exists($commonPath)) {
            require_once $commonPath;
        }
        if (!function_exists('mbx_current_userid') || mbx_current_userid() === '') {
            return false;
        }
        // 데모 모드면 관리자로 등록된 사용자에게만 사이드바를 렌더한다.
        if (function_exists('mbx_demo_enabled') && mbx_demo_enabled()) {
            return function_exists('mbx_is_admin') ? mbx_is_admin() : false;
        }
        // 라이브 모드: 관리자이거나, 사용할 수 있는 메일 계정이 1개 이상 있는
        // 사용자에게만 노출한다. 소유 계정도 공통 계정도 없는(메일함 미등록) 사용자에게는
        // 메일함 모듈 자체를 숨긴다. 관리자 미등록 사용자는 "계정 관리" 버튼만
        // mbx_can_manage_common_accounts() 로 별도로 숨긴다.
        if (function_exists('mbx_is_admin') && mbx_is_admin()) {
            return true;
        }
        if (!function_exists('mbx_visible_accounts')) {
            return false;
        }
        try {
            $db = mbx_db();
        } catch (Throwable $e) {
            return false;
        }
        $accounts = mbx_visible_accounts($db);
        return !empty($accounts);
    }

    function mbx_plugin_render_sidebar(array $state)
    {
        if (empty($state['ready'])) {
            return;
        }
        $accounts = $state['accounts'];
        $account = $state['account'];
        $folder = $state['folder'];
        $unread = $state['unread'];
        $webRoot = rtrim(mbx_plugin_web_root(), '/');
        ?>
                    <div class="mbx-sidebar" style="padding:0 10px 15px">
                        <select class="form-control" id="mbxAccount">
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo (int)$acc['id']; ?>" <?php echo $account && (int)$account['id'] === (int)$acc['id'] ? 'selected' : ''; ?>><?php echo mbx_h($acc['email']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <br>
                        <a href="<?php echo mbx_h($webRoot . '/my_account.php'); ?>" class="btn btn-default btn-block"><i class="fa fa-user"></i> 내 계정</a>
                        <?php if (function_exists('mbx_can_manage_common_accounts') && mbx_can_manage_common_accounts()): ?>
                            <a href="<?php echo mbx_h($webRoot . '/accounts.php'); ?>" class="btn btn-default btn-block"><i class="fa fa-cog"></i> 계정 관리</a>
                        <?php endif; ?>
                        <a href="<?php echo mbx_h($webRoot . '/compose.php'); ?>" class="btn btn-primary btn-block"><i class="fa fa-pencil"></i> 메일 쓰기</a>
                        <ul class="nav nav-pills nav-stacked">
                            <li class="<?php echo $folder === 'inbox' ? 'active' : ''; ?>">
                                <a href="<?php echo mbx_h($webRoot . '/index.php?folder=inbox'); ?>"><i class="fa fa-inbox"></i> 받은메일 <?php if (!empty($unread['inbox'])): ?><span class="badge badge-unread"><?php echo (int)$unread['inbox']; ?></span><?php endif; ?></a>
                            </li>
                            <li class="<?php echo $folder === 'sent' ? 'active' : ''; ?>">
                                <a href="<?php echo mbx_h($webRoot . '/index.php?folder=sent'); ?>"><i class="fa fa-paper-plane"></i> 보낸메일</a>
                            </li>
                            <li class="<?php echo $folder === 'trash' ? 'active' : ''; ?>">
                                <a href="<?php echo mbx_h($webRoot . '/index.php?folder=trash'); ?>"><i class="fa fa-trash"></i> 휴지통</a>
                            </li>
                        </ul>
                        <br>
                        <button id="btnSync" class="btn btn-default btn-block"><i class="fa fa-refresh"></i> 동기화</button>
                    </div>
        <?php
    }

    function mbx_plugin_render_sidebar_script(array $state)
    {
        if (empty($state['ready'])) {
            return;
        }
        $unread = $state['unread'];
        $webRoot = rtrim(mbx_plugin_web_root(), '/');
        ?>
            if ($('#mbxAccount').length || $('#btnSync').length) {
            $(document).off('change.mbxSide', '#mbxAccount').on('change.mbxSide', '#mbxAccount', function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                document.cookie = 'mbx_account_id=' + $(this).val() + '; path=/';
                location.href = '<?php echo $webRoot; ?>/index.php';
            });
            function mbxSideNewCount(r){
                var n = 0;
                if(r && r.new){
                    $.each(r.new, function(_, folders){
                        if(folders && folders.inbox){ n += parseInt(folders.inbox, 10) || 0; }
                    });
                }
                return n;
            }
            function mbxSideUpdateBadge(n){
                var $a = $('.mbx-sidebar a[href="<?php echo $webRoot; ?>/index.php?folder=inbox"], .mbx-sidebar a[href="?folder=inbox"]');
                if(!$a.length){ return; }
                var $b = $a.find('.badge-unread');
                if(n > 0){
                    if(!$b.length){ $b = $('<span class="badge badge-unread"></span>').appendTo($a); }
                    $b.text(n);
                } else {
                    $b.remove();
                }
            }
            var mbxSideLastUnread = <?php echo (int)$unread['inbox']; ?>;
            var mbxSideAutoSyncing = false;
            window.mbxSideAutoSyncInstalled = true;
            function mbxSideAutoSync(){
                if(mbxSideAutoSyncing || !$('#mbxAccount').length){ return; }
                mbxSideAutoSyncing = true;
                $.getJSON('<?php echo $webRoot; ?>/api/sync.php?folder=inbox&_=' + (new Date()).getTime(), function(r){
                    if(!r || r.status !== 'success'){ return; }
                    if(r.errors && Object.keys && Object.keys(r.errors).length){ console.log('mailbox auto sync partial errors', r.errors); }
                    var unread = parseInt(r.unread_inbox, 10) || 0;
                    var newCount = mbxSideNewCount(r);
                    mbxSideUpdateBadge(unread);
                    if((newCount > 0 || unread > mbxSideLastUnread) && window.mbxToast){
                        mbxToast('새 메일 ' + Math.max(newCount, unread - mbxSideLastUnread) + '통이 도착했습니다.');
                    }
                    if((newCount > 0 || unread !== mbxSideLastUnread) && location.pathname.replace(/\\/g, '/').toLowerCase() === '<?php echo strtolower($webRoot); ?>/index.php' && (location.search === '' || location.search.indexOf('folder=inbox') !== -1)){
                        if(window.mbxInboxRefresh){
                            window.mbxInboxRefresh({response:r});
                        } else {
                            setTimeout(function(){ location.reload(); }, 800);
                        }
                    }
                    mbxSideLastUnread = unread;
                }).always(function(){
                    mbxSideAutoSyncing = false;
                });
            }
            $(document).off('click.mbxSide', '#btnSync').on('click.mbxSide', '#btnSync', function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                var b = $(this), i = b.find('i');
                b.prop('disabled', true);
                i.addClass('fa-spin');
                $.getJSON('<?php echo $webRoot; ?>/api/sync.php', function(r){
                    if(r.status === 'success'){
                        if(r.errors && Object.keys(r.errors).length){ console.log('mailbox sync partial errors', r.errors); }
                        var n = mbxSideNewCount(r);
                        if(window.mbxInboxRefresh && location.pathname.replace(/\\/g, '/').toLowerCase() === '<?php echo strtolower($webRoot); ?>/index.php'){
                            window.mbxInboxRefresh({response:r});
                            if(n > 0 && window.mbxToast){
                                mbxToast('새 메일 ' + n + '통이 도착했습니다.');
                            }
                        } else if(n > 0 && window.mbxToast){
                            mbxToast('새 메일 ' + n + '통이 도착했습니다.');
                            setTimeout(function(){ location.reload(); }, 1500);
                        } else {
                            location.reload();
                        }
                    } else {
                        alert(r.message);
                    }
                }).fail(function(xhr){
                    var msg = '동기화 실패';
                    if(xhr.responseJSON && xhr.responseJSON.message){ msg = xhr.responseJSON.message; }
                    alert(msg);
                }).always(function(){
                    b.prop('disabled', false);
                    i.removeClass('fa-spin');
                });
            });
            <?php if (!empty($state['active'])): ?>
            setTimeout(mbxSideAutoSync, 3000);
            setInterval(mbxSideAutoSync, 20000);
            <?php endif; ?>
            }
        <?php
    }
}
?>
