<?php
require_once __DIR__ . '/lib/bootstrap.php';
if (php_sapi_name() === 'cli') {
    mbx_require_admin_file('include/dbconn.php');
} else {
    mbx_require_admin_file('include/inc_base.php');
}
require_once __DIR__ . '/lib/common.php';

function mbx_install_out($message)
{
    echo $message . PHP_EOL;
}

function mbx_install_current_userid()
{
    return function_exists('mbx_current_userid') ? mbx_current_userid() : '';
}

function mbx_install_require_admin()
{
    if (php_sapi_name() === 'cli') {
        return;
    }
    $userid = mbx_install_current_userid();
    if ($userid !== 'admin') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        mbx_install_out('메일함 플러그인 설치는 최고 관리자(admin) 계정만 실행할 수 있습니다.');
        exit;
    }
}

function mbx_install_has_cli_option($name)
{
    global $argv;
    return is_array($argv) && in_array($name, $argv, true);
}

function mbx_install_requested_action()
{
    if (php_sapi_name() === 'cli') {
        return mbx_install_has_cli_option('--uninstall') ? 'uninstall' : 'install';
    }
    return isset($_GET['action']) ? (string)$_GET['action'] : 'install';
}

function mbx_install_confirmed()
{
    if (php_sapi_name() === 'cli') {
        return mbx_install_has_cli_option('--yes');
    }
    return isset($_GET['confirm']) && $_GET['confirm'] === 'DELETE_MAILBOX_PLUGIN';
}

function mbx_install_manifest_path()
{
    return __DIR__ . '/plugin.json';
}

function mbx_install_default_manifest()
{
    $mailboxLabel = json_decode('"\\uba54\\uc77c\\ud568"', true);
    return array(
        'id' => 'mailbox',
        'name' => $mailboxLabel,
        'label' => $mailboxLabel,
        'version' => '1.0.0',
        'enabled' => true,
        'hooks' => array('sidebar', 'sidebar_script'),
        'tables' => array(
            'mailbox_accounts',
            'mailbox_admins',
            'mailbox_folders',
            'mailbox_messages',
            'mailbox_attachments',
        ),
    );
}

function mbx_install_manifest()
{
    $manifest = mbx_install_default_manifest();
    $jsonPath = mbx_install_manifest_path();
    if (file_exists($jsonPath)) {
        $decoded = json_decode(file_get_contents($jsonPath), true);
        if (is_array($decoded) && (!isset($decoded['id']) || $decoded['id'] === 'mailbox')) {
            $manifest = array_merge($manifest, $decoded);
        }
    }
    $manifest['id'] = 'mailbox';
    return $manifest;
}

function mbx_install_dynamic_manifest(array $manifest)
{
    $rootMode = mbx_root_mode();
    $pluginDir = $rootMode === 'admin' ? 'admin/mailbox' : 'mailbox';
    $manifest['root'] = mbx_plugin_web_root();
    $manifest['web_root'] = mbx_plugin_web_root();
    $manifest['entry'] = mbx_plugin_url('index.php');
    $manifest['install'] = mbx_plugin_url('install.php');
    $manifest['delete_batch'] = $pluginDir . '/delete_mailbox_plugin.cmd';
    $manifest['delete_changes_batch'] = $pluginDir . '/delete_mailbox_changes.cmd';
    $manifest['root_mode'] = $rootMode;
    $manifest['plugin_dir'] = $pluginDir;
    $manifest['updated_at'] = date('c');
    return $manifest;
}

function mbx_install_write_manifest(array $manifest)
{
    $manifest = mbx_install_dynamic_manifest($manifest);
    $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('plugin.json 생성에 실패했습니다: ' . json_last_error_msg());
    }
    if (file_put_contents(mbx_install_manifest_path(), $json . "\r\n") === false) {
        throw new RuntimeException('plugin.json 파일을 갱신할 수 없습니다.');
    }
    return $manifest;
}

function mbx_install_ensure_upload_guard()
{
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('uploads 디렉터리를 생성하지 못했습니다.');
    }
    $guardPath = $uploadDir . '/.htaccess';
    if (!file_exists($guardPath)) {
        file_put_contents($guardPath, "Require all denied\r\nDeny from all\r\n");
    }
}

function mbx_install_side_menu_path()
{
    return mbx_admin_path('include/side_m.php');
}

function mbx_install_normalize_newlines($text)
{
    return str_replace(array("\r\n", "\r"), "\n", (string)$text);
}

function mbx_install_preserve_newlines($text, $original)
{
    return strpos((string)$original, "\r\n") !== false ? str_replace("\n", "\r\n", $text) : $text;
}

function mbx_install_backup_file($path)
{
    $backupPath = $path . '.mailbox_backup_' . date('Ymd_His');
    if (!copy($path, $backupPath)) {
        throw new RuntimeException('side_m.php 백업 파일을 만들 수 없습니다.');
    }
    return $backupPath;
}

function mbx_install_replace_once(&$text, $search, $replace)
{
    $pos = strpos($text, $search);
    if ($pos === false) {
        return false;
    }
    $text = substr($text, 0, $pos) . $replace . substr($text, $pos + strlen($search));
    return true;
}

function mbx_install_ensure_side_menu_hook()
{
    $path = mbx_install_side_menu_path();
    if (!file_exists($path)) {
        throw new RuntimeException('side_m.php 파일을 찾을 수 없습니다.');
    }

    $original = file_get_contents($path);
    if ($original === false) {
        throw new RuntimeException('side_m.php 파일을 읽을 수 없습니다.');
    }

    if (strpos($original, 'mbx_plugin_prepare_sidebar') !== false
        && strpos($original, 'mbx_plugin_render_sidebar') !== false
        && strpos($original, 'mbx_plugin_render_sidebar_script') !== false) {
        return '이미 적용됨';
    }

    $text = mbx_install_normalize_newlines($original);
    $changed = false;

    $loader = <<<'PHP'
    if ((!isset($user_dbinfo) || !is_array($user_dbinfo)) && isset($GLOBALS['user_dbinfo']) && is_array($GLOBALS['user_dbinfo'])) {
        $user_dbinfo = $GLOBALS['user_dbinfo'];
    }
    if (!isset($user_dbinfo) || !is_array($user_dbinfo)) {
        $user_dbinfo = array();
    }
    if (!isset($user_dbinfo['userid'])) {
        $user_dbinfo['userid'] = '';
    }
    if (!isset($user_dbinfo['kor_name'])) {
        $user_dbinfo['kor_name'] = '';
    }
    if (!isset($division)) {
        $division = '';
    }
    if (!isset($pdx)) {
        $pdx = '';
    }
    if (!isset($sub)) {
        $sub = '';
    }
    if (!isset($table_id)) {
        $table_id = '';
    }
    $adminPluginPaths = array(
        dirname(__DIR__) . '/mailbox/plugin.php',
        dirname(dirname(__DIR__)) . '/mailbox/plugin.php',
    );
    foreach ($adminPluginPaths as $adminPluginPath) {
        if (file_exists($adminPluginPath)) {
            require_once $adminPluginPath;
            break;
        }
    }
    $mbxPluginState = function_exists('mbx_plugin_prepare_sidebar')
        ? mbx_plugin_prepare_sidebar(array(
            'request_path' => isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '',
            'accounts' => isset($accounts) && is_array($accounts) ? $accounts : null,
            'account' => isset($account) && is_array($account) ? $account : null,
            'folder' => isset($folder) ? $folder : null,
            'row' => isset($row) && is_array($row) ? $row : null,
            'unread' => isset($unread) && is_array($unread) ? $unread : null,
        ))
        : array('active' => false, 'hide_default_menu' => false, 'ready' => false);

PHP;

    if (strpos($text, 'mbx_plugin_prepare_sidebar') === false) {
        if (mbx_install_replace_once($text, "    \$m_row1 = array('status' => '', 'login_date' => '');", $loader . "    \$m_row1 = array('status' => '', 'login_date' => '');")) {
            $changed = true;
        } elseif (mbx_install_replace_once($text, '$m_row1 = array', $loader . '    $m_row1 = array')) {
            $changed = true;
        } else {
            throw new RuntimeException('side_m.php에 플러그인 로더 삽입 위치를 찾을 수 없습니다.');
        }
    }

    if (strpos($text, "empty(\$mbxPluginState['hide_default_menu'])") === false) {
        $patterns = array(
            "<div id=\"side_accordion\" class=\"panel-group\">\n\t\t\t\t   <?php",
            "<div id=\"side_accordion\" class=\"panel-group\">\n                   <?php",
            "<div id=\"side_accordion\" class=\"panel-group\">\n<?php",
        );
        $wrapped = false;
        foreach ($patterns as $pattern) {
            if (strpos($text, $pattern) !== false) {
                $replacement = "<div id=\"side_accordion\" class=\"panel-group\">\n                   <?php if (empty(\$mbxPluginState['hide_default_menu'])): ?>\n\t\t\t\t   <?php";
                mbx_install_replace_once($text, $pattern, $replacement);
                $wrapped = true;
                $changed = true;
                break;
            }
        }
        if (!$wrapped) {
            throw new RuntimeException('side_m.php에 기본 메뉴 감싸기 위치를 찾을 수 없습니다.');
        }
    }

    if (strpos($text, 'mbx_plugin_render_sidebar($mbxPluginState)') === false) {
        $render = "                   <?php endif; ?>\n                   <?php if (function_exists('mbx_plugin_render_sidebar')) { mbx_plugin_render_sidebar(\$mbxPluginState); } ?>";
        if (strpos($text, '//echo $date->format') !== false) {
            if (mbx_install_replace_once($text, "                        //echo \$date->format('Y-m-d H:i:s');\n\t\t\t\t\t?>", "                        //echo \$date->format('Y-m-d H:i:s');\n\t\t\t\t\t?>\n" . $render)) {
                $changed = true;
            }
        }
        if (strpos($text, 'mbx_plugin_render_sidebar($mbxPluginState)') === false) {
            if (mbx_install_replace_once($text, "\n                </div>\n\n                <div class=\"push\"></div>", "\n" . $render . "\n\n                </div>\n\n                <div class=\"push\"></div>")) {
                $changed = true;
            } else {
                throw new RuntimeException('side_m.php에 플러그인 사이드바 출력 위치를 찾을 수 없습니다.');
            }
        }
    }

    if (strpos($text, 'mbx_plugin_render_sidebar_script($mbxPluginState)') === false) {
        $script = "\t\t$( document ).ready(function() {\n            <?php if (function_exists('mbx_plugin_render_sidebar_script')) { mbx_plugin_render_sidebar_script(\$mbxPluginState); } ?>";
        if (mbx_install_replace_once($text, "\t\t$( document ).ready(function() {", $script)) {
            $changed = true;
        } elseif (mbx_install_replace_once($text, "\t\t$(document).ready(function() {", "\t\t$(document).ready(function() {\n            <?php if (function_exists('mbx_plugin_render_sidebar_script')) { mbx_plugin_render_sidebar_script(\$mbxPluginState); } ?>")) {
            $changed = true;
        } else {
            throw new RuntimeException('side_m.php에 플러그인 스크립트 출력 위치를 찾을 수 없습니다.');
        }
    }

    if (!$changed) {
        return '이미 적용됨';
    }

    $backupPath = mbx_install_backup_file($path);
    $writeText = mbx_install_preserve_newlines($text, $original);
    if (file_put_contents($path, $writeText) === false) {
        throw new RuntimeException('side_m.php 파일을 갱신할 수 없습니다.');
    }

    return '적용 완료 (백업: ' . basename($backupPath) . ')';
}

// ── ERP 코어 메뉴 링크 절대경로 패치 ───────────────────────────
// menu_info_user.menu_link 는 'base_reservation.php?...' 상대경로라 /admin/mailbox/
// 하위에서 메뉴를 누르면 경로가 깨진다. func_list.php 의 메뉴 출력 함수가 출력 시점에
// adminMenuLink() 로 /admin/ 절대경로화 하도록 패치한다(멱등).
function mbx_install_ensure_menu_link_patch()
{
    $path = mbx_admin_path('include/func_list.php');
    if (!file_exists($path)) {
        throw new RuntimeException('func_list.php 파일을 찾을 수 없습니다.');
    }
    $original = file_get_contents($path);
    if ($original === false) {
        throw new RuntimeException('func_list.php 파일을 읽을 수 없습니다.');
    }
    if (strpos($original, 'function adminMenuLink(') !== false) {
        return '이미 적용됨';
    }

    $text = mbx_install_normalize_newlines($original);

    $helper = <<<'PHP'
	// 메뉴 링크를 admin/ 기준 절대경로로 변환한다. (메일함 플러그인 설치 시 추가)
	// menu_info_user.menu_link 는 'base_reservation.php?...' 상대경로라 /admin/mailbox/ 같은
	// 하위 폴더에서 누르면 경로가 깨진다. 절대경로/외부 URL/javascript: 는 그대로 둔다.
	function adminMenuLink($link){
		$link = trim((string)$link);
		if ($link === '' || $link === '#') { return $link; }
		if ($link[0] === '/') { return $link; }
		if (substr($link, 0, 2) === '//') { return $link; }
		if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $link)) { return $link; }
		return '/admin/' . ltrim($link, './');
	}

	// 메뉴접근
	function printLeftMenu($division,$userid,$pdx,$sub){
PHP;

    // adminMenuLink 헬퍼를 printLeftMenu 정의 앞에 삽입
    if (!mbx_install_replace_once($text, "\t// 메뉴접근\n\tfunction printLeftMenu(\$division,\$userid,\$pdx,\$sub){", $helper)) {
        throw new RuntimeException('func_list.php 에 adminMenuLink 헬퍼 삽입 위치를 찾을 수 없습니다.');
    }

    // 헤더 메뉴(printMenu) 링크 절대경로화
    $hdrFrom = "<li class='dropdown'><a href='{\$row1['menu_link']}'>";
    $hdrTo = "<li class='dropdown'><a href='\" . adminMenuLink(\$row1['menu_link']) . \"'>";
    if (!mbx_install_replace_once($text, $hdrFrom, $hdrTo)) {
        throw new RuntimeException('func_list.php 에 헤더 메뉴(printMenu) 링크 치환 위치를 찾을 수 없습니다.');
    }

    // 좌측 메뉴(printLeftMenu / printLeftMenu_b) 링크 절대경로화 (동일 라인 2곳)
    $leftFrom = "<a href='{\$row2['menu_link']}'>";
    $leftTo = "<a href='\" . adminMenuLink(\$row2['menu_link']) . \"'>";
    $leftCount = 0;
    while ($leftCount < 10 && mbx_install_replace_once($text, $leftFrom, $leftTo)) {
        $leftCount++;
    }
    if ($leftCount === 0) {
        throw new RuntimeException('func_list.php 에 좌측 메뉴 링크 치환 위치를 찾을 수 없습니다.');
    }

    $backupPath = mbx_install_backup_file($path);
    $writeText = mbx_install_preserve_newlines($text, $original);
    if (file_put_contents($path, $writeText) === false) {
        throw new RuntimeException('func_list.php 파일을 갱신할 수 없습니다.');
    }

    return '적용 완료 (헤더 1곳·좌측 ' . $leftCount . '곳, 백업: ' . basename($backupPath) . ')';
}

// 헤더(header.php)의 정적 링크(브랜드·통합예약검색)도 동일하게 /admin/ 절대경로화(멱등, best-effort)
function mbx_install_ensure_header_link_patch()
{
    $path = mbx_admin_path('include/header.php');
    if (!file_exists($path)) {
        throw new RuntimeException('header.php 파일을 찾을 수 없습니다.');
    }
    $original = file_get_contents($path);
    if ($original === false) {
        throw new RuntimeException('header.php 파일을 읽을 수 없습니다.');
    }

    $text = mbx_install_normalize_newlines($original);
    $changed = false;

    if (strpos($text, 'href="/admin/index.php">파란여행') === false
        && mbx_install_replace_once($text, 'href="index.php">파란여행', 'href="/admin/index.php">파란여행')) {
        $changed = true;
    }
    if (strpos($text, 'href="/admin/total_reservation.php"') === false
        && mbx_install_replace_once($text, 'href="total_reservation.php"', 'href="/admin/total_reservation.php"')) {
        $changed = true;
    }

    if (!$changed) {
        return '이미 적용됨(또는 대상 없음)';
    }

    $backupPath = mbx_install_backup_file($path);
    $writeText = mbx_install_preserve_newlines($text, $original);
    if (file_put_contents($path, $writeText) === false) {
        throw new RuntimeException('header.php 파일을 갱신할 수 없습니다.');
    }

    return '적용 완료 (백업: ' . basename($backupPath) . ')';
}

function mbx_install_table_exists(mysqli $db, $table)
{
    $table = mysqli_real_escape_string($db, $table);
    $res = mysqli_query($db, "SHOW TABLES LIKE '" . $table . "'");
    return $res && mysqli_num_rows($res) > 0;
}

function mbx_install_run(mysqli $db)
{
    $manifest = mbx_install_manifest();
    $manifest = mbx_install_write_manifest($manifest);
    $sideMenuStatus = mbx_install_ensure_side_menu_hook();
    MailboxSync::ensureTables($db);
    mbx_install_ensure_upload_guard();

    // ERP 코어 메뉴 링크 절대경로 패치는 실패해도 설치 전체를 중단하지 않는다.
    try {
        $menuLinkStatus = mbx_install_ensure_menu_link_patch();
    } catch (Throwable $e) {
        $menuLinkStatus = '실패: ' . $e->getMessage();
    }
    try {
        $headerLinkStatus = mbx_install_ensure_header_link_patch();
    } catch (Throwable $e) {
        $headerLinkStatus = '실패: ' . $e->getMessage();
    }

    $userid = php_sapi_name() === 'cli' ? 'admin' : mbx_install_current_userid();
    if ($userid !== '') {
        mbx_add_admin($db, $userid);
    }

    mbx_install_out('메일함 플러그인 설치가 완료되었습니다.');
    mbx_install_out('설치 위치: ' . (mbx_root_mode() === 'admin' ? 'admin 하위' : 'document root 하위'));
    mbx_install_out('진입 경로: ' . mbx_plugin_url('index.php'));
    mbx_install_out('plugin.json: 동적 경로 갱신 완료');
    mbx_install_out('side_m.php 훅: ' . $sideMenuStatus);
    mbx_install_out('메뉴 링크 절대경로 패치(func_list.php): ' . $menuLinkStatus);
    mbx_install_out('헤더 링크 절대경로 패치(header.php): ' . $headerLinkStatus);
    mbx_install_out('테이블 확인:');
    foreach ($manifest['tables'] as $table) {
        mbx_install_out(' - ' . $table . ': ' . (mbx_install_table_exists($db, $table) ? '정상' : '누락'));
    }
    mbx_install_out('업로드 보호 파일: ' . (file_exists(__DIR__ . '/uploads/.htaccess') ? '정상' : '누락'));
}

function mbx_install_uninstall(mysqli $db)
{
    if (!mbx_install_confirmed()) {
        mbx_install_out('삭제 확인 문구가 없어 중단했습니다.');
        mbx_install_out('CLI: php install.php --uninstall --yes');
        mbx_install_out('웹: install.php?action=uninstall&confirm=DELETE_MAILBOX_PLUGIN');
        exit(1);
    }

    $tables = array(
        'mailbox_attachments',
        'mailbox_messages',
        'mailbox_folders',
        'mailbox_admins',
        'mailbox_accounts',
    );
    foreach ($tables as $table) {
        $sql = 'DROP TABLE IF EXISTS ' . $table;
        if (!mysqli_query($db, $sql)) {
            throw new RuntimeException(mysqli_error($db));
        }
        mbx_install_out('테이블 삭제: ' . $table);
    }
    mbx_install_out('메일함 플러그인 DB 테이블 삭제가 완료되었습니다.');
}

mbx_install_require_admin();
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

try {
    $db = mbx_db();
    $action = mbx_install_requested_action();
    if ($action === 'uninstall') {
        mbx_install_uninstall($db);
    } else {
        mbx_install_run($db);
    }
} catch (Throwable $e) {
    http_response_code(500);
    mbx_install_out('오류: ' . $e->getMessage());
    exit(1);
}
?>
