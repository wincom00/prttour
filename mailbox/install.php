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
        throw new RuntimeException(basename($path) . ' 백업 파일을 만들 수 없습니다.');
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

// ── side_m.php 훅 상태 확인(적용 없이 검사만) ─────────────────────
function mbx_install_side_menu_hook_applied()
{
    $path = mbx_install_side_menu_path();
    if (!file_exists($path)) {
        return null;
    }
    $text = file_get_contents($path);
    return strpos($text, 'mbx_plugin_prepare_sidebar') !== false
        && strpos($text, 'mbx_plugin_render_sidebar') !== false
        && strpos($text, 'mbx_plugin_render_sidebar_script') !== false;
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
function mbx_install_menu_link_patch_applied()
{
    $path = mbx_admin_path('include/func_list.php');
    if (!file_exists($path)) {
        return null;
    }
    return strpos(file_get_contents($path), 'function adminMenuLink(') !== false;
}

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

    // 설치 위치에 따라 접두사가 다르다: admin 하위 설치는 /admin/, document root 설치는 /
    $prefix = mbx_root_mode() === 'admin' ? '/admin/' : '/';

    $helper = <<<'PHP'
	// 메뉴 링크를 절대경로로 변환한다. (메일함 플러그인 설치 시 추가)
	// menu_info_user.menu_link 는 'base_reservation.php?...' 상대경로라 %WEBROOT%mailbox/ 같은
	// 하위 폴더에서 누르면 경로가 깨진다. 절대경로/외부 URL/javascript: 는 그대로 둔다.
	function adminMenuLink($link){
		$link = trim((string)$link);
		if ($link === '' || $link === '#') { return $link; }
		if ($link[0] === '/') { return $link; }
		if (substr($link, 0, 2) === '//') { return $link; }
		if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $link)) { return $link; }
		return '%WEBROOT%' . ltrim($link, './');
	}

	// 메뉴접근
	function printLeftMenu($division,$userid,$pdx,$sub){
PHP;
    $helper = str_replace('%WEBROOT%', $prefix, $helper);

    // adminMenuLink 헬퍼를 printLeftMenu 정의 앞에 삽입
    if (!mbx_install_replace_once($text, "\t// 메뉴접근\n\tfunction printLeftMenu(\$division,\$userid,\$pdx,\$sub){", $helper)) {
        throw new RuntimeException('func_list.php 에 adminMenuLink 헬퍼 삽입 위치를 찾을 수 없습니다.');
    }

    // 헤더 메뉴(printMenu) 링크 절대경로화.
    // 변형에 따라 이미 절대경로("<a href='/".$row1['menu_link']."'>")인 경우가 있어 그때는 건너뛴다.
    $hdrFrom = "<li class='dropdown'><a href='{\$row1['menu_link']}'>";
    $hdrTo = "<li class='dropdown'><a href='\" . adminMenuLink(\$row1['menu_link']) . \"'>";
    $hdrStatus = '치환 1곳';
    if (!mbx_install_replace_once($text, $hdrFrom, $hdrTo)) {
        if (strpos($text, "<li class='dropdown'><a href='/\".\$row1['menu_link'].\"'>") !== false
            || strpos($text, 'adminMenuLink($row1') !== false) {
            $hdrStatus = '이미 절대경로';
        } else {
            throw new RuntimeException('func_list.php 에 헤더 메뉴(printMenu) 링크 치환 위치를 찾을 수 없습니다.');
        }
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

    return '적용 완료 (헤더 ' . $hdrStatus . '·좌측 ' . $leftCount . '곳, 백업: ' . basename($backupPath) . ')';
}

// 헤더(header.php)의 정적 링크(브랜드·통합예약검색)도 동일하게 /admin/ 절대경로화(멱등, best-effort)
function mbx_install_header_link_patch_applied()
{
    $path = mbx_admin_path('include/header.php');
    if (!file_exists($path)) {
        return null;
    }
    $text = file_get_contents($path);
    // 패치 대상 문자열이 남아있지 않으면 적용된 것으로 본다.
    return strpos($text, 'href="index.php">파란여행') === false
        && strpos($text, 'href="total_reservation.php"') === false;
}

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

// ── config.php 설정 읽기/저장 ─────────────────────────────────
function mbx_install_config_path()
{
    return __DIR__ . '/config.php';
}

function mbx_install_config_values()
{
    return array(
        'sync_key' => defined('MBX_SYNC_KEY') ? MBX_SYNC_KEY : '',
        'initial_sync_limit' => defined('MBX_INITIAL_SYNC_LIMIT') ? (int)MBX_INITIAL_SYNC_LIMIT : 500,
        'inbox_max_messages' => defined('MBX_INBOX_MAX_MESSAGES') ? (int)MBX_INBOX_MAX_MESSAGES : 5000,
        'max_attach' => defined('MBX_MAX_ATTACH') ? (int)MBX_MAX_ATTACH : 5,
        'max_attach_size_mb' => defined('MBX_MAX_ATTACH_SIZE') ? (int)round(MBX_MAX_ATTACH_SIZE / 1048576) : 20,
        'demo' => defined('MBX_DEMO') ? (bool)MBX_DEMO : false,
        'demo_admin' => defined('MBX_DEMO_ADMIN') ? (string)MBX_DEMO_ADMIN : 'admin',
        'demo_user' => defined('MBX_DEMO_USER') ? (string)MBX_DEMO_USER : '',
        'admin_dir' => defined('MBX_ADMIN_DIR') ? (string)MBX_ADMIN_DIR : '',
        'login_cookies' => defined('MBX_LOGIN_COOKIES') ? (string)MBX_LOGIN_COOKIES : 'MEMLOGIN_ADMIN_PURUN,MEMLOGIN_ADMIN_PARAN',
        'db_host' => defined('MBX_DB_HOST') ? (string)MBX_DB_HOST : '',
        'db_port' => defined('MBX_DB_PORT') ? (int)MBX_DB_PORT : 3306,
        'db_user' => defined('MBX_DB_USER') ? (string)MBX_DB_USER : '',
        'db_pass' => defined('MBX_DB_PASS') ? (string)MBX_DB_PASS : '',
        'db_name' => defined('MBX_DB_NAME') ? (string)MBX_DB_NAME : '',
    );
}

// define('이름', 값); 한 줄만 교체한다. 뒤따르는 주석과 $MBX_FOLDERS/$MBX_PROVIDERS
// 같은 사용자 수정 영역은 건드리지 않는다.
function mbx_install_patch_define(&$text, $name, $phpValue)
{
    $pattern = "/define\\('" . preg_quote($name, '/') . "',\\s*.*?\\);/";
    $replacement = "define('" . $name . "', " . $phpValue . ");";
    $count = 0;
    $new = preg_replace($pattern, $replacement, $text, 1, $count);
    if ($count < 1 || $new === null) {
        throw new RuntimeException('config.php 에서 ' . $name . ' 정의를 찾을 수 없습니다.');
    }
    $text = $new;
}

function mbx_install_php_string($value)
{
    return "'" . str_replace(array('\\', "'"), array('\\\\', "\\'"), (string)$value) . "'";
}

// define 이 아직 없는(구버전) config.php 에도 저장할 수 있도록, 못 찾으면
// $MBX_FOLDERS 정의 앞(없으면 파일 끝 종료 태그 앞)에 새 define 을 추가한다.
function mbx_install_upsert_define(&$text, $name, $phpValue)
{
    try {
        mbx_install_patch_define($text, $name, $phpValue);
        return;
    } catch (RuntimeException $e) {
    }
    $insert = "if (!defined('" . $name . "')) {\n    define('" . $name . "', " . $phpValue . ");\n}\n";
    if (mbx_install_replace_once($text, '$MBX_FOLDERS = array(', $insert . "\n\$MBX_FOLDERS = array(")) {
        return;
    }
    if (mbx_install_replace_once($text, '?>', $insert . '?>')) {
        return;
    }
    $text .= "\n" . $insert;
}

function mbx_install_save_config(array $values)
{
    $path = mbx_install_config_path();
    if (!file_exists($path)) {
        throw new RuntimeException('config.php 파일을 찾을 수 없습니다.');
    }
    $original = file_get_contents($path);
    if ($original === false) {
        throw new RuntimeException('config.php 파일을 읽을 수 없습니다.');
    }
    $text = $original;
    mbx_install_patch_define($text, 'MBX_SYNC_KEY', mbx_install_php_string($values['sync_key']));
    mbx_install_patch_define($text, 'MBX_INITIAL_SYNC_LIMIT', (string)max(0, (int)$values['initial_sync_limit']));
    mbx_install_patch_define($text, 'MBX_INBOX_MAX_MESSAGES', (string)max(0, (int)$values['inbox_max_messages']));
    mbx_install_patch_define($text, 'MBX_MAX_ATTACH', (string)max(1, (int)$values['max_attach']));
    mbx_install_patch_define($text, 'MBX_MAX_ATTACH_SIZE', (string)(max(1, (int)$values['max_attach_size_mb']) * 1048576));
    mbx_install_patch_define($text, 'MBX_DEMO', !empty($values['demo']) ? 'true' : 'false');
    mbx_install_patch_define($text, 'MBX_DEMO_ADMIN', mbx_install_php_string($values['demo_admin']));
    mbx_install_patch_define($text, 'MBX_DEMO_USER', mbx_install_php_string($values['demo_user']));
    // 환경 연결(폴더·쿠키·DB) — 구버전 config.php 에 define 이 없으면 추가한다.
    mbx_install_upsert_define($text, 'MBX_ADMIN_DIR', mbx_install_php_string(rtrim(str_replace('\\', '/', trim((string)$values['admin_dir'])), '/')));
    mbx_install_upsert_define($text, 'MBX_LOGIN_COOKIES', mbx_install_php_string($values['login_cookies']));
    mbx_install_upsert_define($text, 'MBX_DB_HOST', mbx_install_php_string($values['db_host']));
    mbx_install_upsert_define($text, 'MBX_DB_PORT', (string)max(1, (int)$values['db_port']));
    mbx_install_upsert_define($text, 'MBX_DB_USER', mbx_install_php_string($values['db_user']));
    mbx_install_upsert_define($text, 'MBX_DB_PASS', mbx_install_php_string($values['db_pass']));
    mbx_install_upsert_define($text, 'MBX_DB_NAME', mbx_install_php_string($values['db_name']));

    if ($text === $original) {
        return '변경 사항 없음';
    }
    $backupPath = mbx_install_backup_file($path);
    if (file_put_contents($path, $text) === false) {
        throw new RuntimeException('config.php 파일을 갱신할 수 없습니다.');
    }
    return '저장 완료 (백업: ' . basename($backupPath) . ')';
}

// ── ERP DB 연결(include/dbconn.php 기준) 읽기/저장 ─────────────
function mbx_install_dbconn_path()
{
    return mbx_admin_path('include/dbconn.php');
}

// 파일에서 $db_* 할당값을 읽는다. 상단 주석 블록에 옛 값이 남아있을 수 있어
// 마지막(=활성) 할당을 사용한다.
function mbx_install_dbconn_values($path)
{
    $text = (string)@file_get_contents($path);
    $values = array('db_host' => '', 'db_port' => '3306', 'db_user' => '', 'db_passwd' => '', 'db_name' => '');
    foreach (array_keys($values) as $var) {
        if (preg_match_all('/^\s*\$' . $var . '\s*=\s*(["\'])(.*?)\1\s*;/m', $text, $m) && $m[2]) {
            $idx = count($m[2]) - 1;
            $quote = $m[1][$idx];
            $raw = $m[2][$idx];
            // 소스의 이스케이프를 해제해 실제 값으로 돌려준다.
            // 작은따옴표: \' 와 \\ 만, 큰따옴표: \" \\ \$ 만 처리(호스트/암호 값에 충분).
            if ($quote === "'") {
                $values[$var] = preg_replace('/\\\\([\\\\\'])/', '$1', $raw);
            } else {
                $values[$var] = preg_replace('/\\\\(["\\\\$])/', '$1', $raw);
            }
        }
    }
    return $values;
}

// 주석(블록/라인) 밖의 $db_* = "..."; 할당만 교체한다.
function mbx_install_patch_dbconn($path, array $values)
{
    $original = file_get_contents($path);
    if ($original === false) {
        throw new RuntimeException(basename($path) . ' 파일을 읽을 수 없습니다.');
    }
    $text = mbx_install_normalize_newlines($original);
    $lines = explode("\n", $text);
    $inComment = false;
    $changed = 0;
    foreach ($lines as $i => $line) {
        if ($inComment) {
            if (strpos($line, '*/') !== false) {
                $inComment = false;
            }
            continue;
        }
        if (preg_match('/^\s*\/\*/', $line)) {
            if (strpos($line, '*/') === false) {
                $inComment = true;
            }
            continue;
        }
        if (preg_match('/^\s*(\/\/|#)/', $line)) {
            continue;
        }
        foreach ($values as $var => $val) {
            if (preg_match('/^(\s*)\$' . $var . '\s*=\s*["\']/', $line, $m)) {
                $lines[$i] = $m[1] . '$' . $var . ' = ' . mbx_install_php_string($val) . ';';
                $changed++;
            }
        }
    }
    if ($changed === 0) {
        throw new RuntimeException(basename($path) . ' 에서 DB 설정 변수($db_host 등)를 찾을 수 없습니다.');
    }
    $backupPath = mbx_install_backup_file($path);
    $writeText = mbx_install_preserve_newlines(implode("\n", $lines), $original);
    if (file_put_contents($path, $writeText) === false) {
        throw new RuntimeException(basename($path) . ' 파일을 갱신할 수 없습니다.');
    }
    return '저장 완료 (' . $changed . '곳 갱신, 백업: ' . basename($backupPath) . ')';
}

// ── $MBX_FOLDERS / $MBX_PROVIDERS 배열 저장 ───────────────────
function mbx_install_php_array_code($name, array $arr)
{
    $out = '$' . $name . " = array(\n";
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            $out .= '    ' . mbx_install_php_string($k) . " => array(\n";
            foreach ($v as $k2 => $v2) {
                $out .= '        ' . mbx_install_php_string($k2) . ' => ' . (is_int($v2) ? $v2 : mbx_install_php_string($v2)) . ",\n";
            }
            $out .= "    ),\n";
        } else {
            $out .= '    ' . mbx_install_php_string($k) . ' => ' . (is_int($v) ? $v : mbx_install_php_string($v)) . ",\n";
        }
    }
    $out .= ');';
    return $out;
}

function mbx_install_save_arrays(array $folders, array $providers)
{
    $path = mbx_install_config_path();
    $original = file_get_contents($path);
    if ($original === false) {
        throw new RuntimeException('config.php 파일을 읽을 수 없습니다.');
    }
    $text = $original;
    $count = 0;
    $text = preg_replace('/\$MBX_FOLDERS\s*=\s*array\s*\(.*?\n\);/s', str_replace(array('\\', '$'), array('\\\\', '\\$'), mbx_install_php_array_code('MBX_FOLDERS', $folders)), $text, 1, $c1);
    $count += (int)$c1;
    $text = preg_replace('/\$MBX_PROVIDERS\s*=\s*array\s*\(.*?\n\);/s', str_replace(array('\\', '$'), array('\\\\', '\\$'), mbx_install_php_array_code('MBX_PROVIDERS', $providers)), $text, 1, $c2);
    $count += (int)$c2;
    if ($text === null || $count < 2) {
        throw new RuntimeException('config.php 에서 $MBX_FOLDERS / $MBX_PROVIDERS 정의를 찾을 수 없습니다.');
    }
    if ($text === $original) {
        return '변경 사항 없음';
    }
    $backupPath = mbx_install_backup_file($path);
    if (file_put_contents($path, $text) === false) {
        throw new RuntimeException('config.php 파일을 갱신할 수 없습니다.');
    }
    return '저장 완료 (백업: ' . basename($backupPath) . ')';
}

function mbx_install_random_key()
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(16));
    }
    return md5(uniqid((string)mt_rand(), true));
}

// Windows 에서 is_writable() 은 디렉터리에 대해 ACL 을 제대로 반영하지 못해
// 실제로는 쓰기 가능한데 false 를 돌려주는 경우가 있다. 실제 파일 생성으로 확인한다.
function mbx_install_dir_writable($dir)
{
    if (!is_dir($dir)) {
        $dir = dirname($dir);
        if (!is_dir($dir)) {
            return false;
        }
    }
    $probe = rtrim($dir, '/\\') . '/.mbx_write_test_' . uniqid('', true);
    $ok = @file_put_contents($probe, 'x') !== false;
    if ($ok) {
        @unlink($probe);
    }
    return $ok;
}

// ── 환경 점검 ─────────────────────────────────────────────────
// 각 항목: array(라벨, 상태(true/false/null=경고), 상세)
function mbx_install_env_checks()
{
    $checks = array();
    $checks[] = array('PHP 버전', version_compare(PHP_VERSION, '5.6', '>='), PHP_VERSION);
    foreach (array(
        'mysqli' => 'DB 연결(필수)',
        'openssl' => 'IMAP/SMTP SSL 연결(필수)',
        'mbstring' => '한글 제목·본문 디코딩(필수)',
        'iconv' => '문자셋 변환(권장)',
        'dom' => '본문 HTML 정화(권장)',
        'json' => 'API 응답(필수)',
    ) as $ext => $why) {
        $checks[] = array('확장 모듈: ' . $ext, extension_loaded($ext) ? true : ($ext === 'iconv' || $ext === 'dom' ? null : false), $why);
    }

    $adminDirConfigured = defined('MBX_ADMIN_DIR') ? trim((string)MBX_ADMIN_DIR) : '';
    try {
        $adminDir = mbx_admin_dir();
        $adminDirLabel = $adminDirConfigured !== '' ? '지정값 사용: ' : '자동 탐지: ';
        if ($adminDirConfigured !== '' && rtrim(str_replace('\\', '/', $adminDirConfigured), '/') !== $adminDir) {
            $checks[] = array('ERP admin 디렉터리', null, '지정 경로(' . $adminDirConfigured . ')가 유효하지 않아 자동 탐지로 대체: ' . $adminDir);
        } else {
            $checks[] = array('ERP admin 디렉터리', true, $adminDirLabel . $adminDir);
        }
    } catch (Throwable $e) {
        $checks[] = array('ERP admin 디렉터리', false, $e->getMessage());
    }

    // 로그인 쿠키: 설정된 이름 목록과 현재 요청에서 발견되는 쿠키 표시
    if (function_exists('mbx_login_cookie_names')) {
        $cookieNames = mbx_login_cookie_names();
        $found = '';
        foreach ($cookieNames as $cookieName) {
            if (!empty($_COOKIE[$cookieName])) {
                $found = $cookieName;
                break;
            }
        }
        if (php_sapi_name() === 'cli') {
            $checks[] = array('로그인 쿠키 설정', true, implode(', ', $cookieNames));
        } else {
            $checks[] = array('로그인 쿠키 설정', $found !== '' ? true : null, implode(', ', $cookieNames) . ($found !== '' ? ' — 현재 세션: ' . $found : ' — 현재 요청에서 로그인 쿠키를 찾지 못했습니다'));
        }
    }

    // DB 연결 방식
    $dedicated = defined('MBX_DB_HOST') && trim((string)MBX_DB_HOST) !== '';
    $checks[] = array('DB 연결 방식', true, $dedicated ? '전용 DB 사용: ' . MBX_DB_HOST . ':' . (int)MBX_DB_PORT . ' / ' . MBX_DB_NAME : 'ERP 기본 연결(dbconn.php) 상속');

    foreach (array(
        array('플러그인 디렉터리 쓰기 권한', __DIR__),
        array('uploads 디렉터리', __DIR__ . '/uploads'),
    ) as $item) {
        $checks[] = array($item[0], mbx_install_dir_writable($item[1]), $item[1]);
    }
    // 설치가 패치하는 코어 파일들. 쓰기 가능하면 정상, 쓰기 불가라도 이미 패치가
    // 적용돼 있으면(재설치·기존 서버) 재패치 때만 권한이 필요하므로 실패가 아니라
    // 주의로 표시한다. 패치도 안 됐고 쓰기도 불가할 때만 실패(설치가 훅을 못 넣음).
    $mbxHookAppliedFn = array(
        'include/side_m.php' => 'mbx_install_side_menu_hook_applied',
        'include/func_list.php' => 'mbx_install_menu_link_patch_applied',
        'include/header.php' => 'mbx_install_header_link_patch_applied',
    );
    foreach ($mbxHookAppliedFn as $rel => $appliedFn) {
        try {
            $p = mbx_admin_path($rel);
            $writable = file_exists($p) ? is_writable($p) : false;
            if ($writable) {
                $checks[] = array($rel . ' 쓰기 권한', true, $p);
                continue;
            }
            $applied = function_exists($appliedFn) ? call_user_func($appliedFn) : null;
            if ($applied === true) {
                $checks[] = array($rel . ' 쓰기 권한', null, '이미 패치 적용됨 — 재패치 시에만 쓰기 권한 필요: ' . $p);
            } else {
                $checks[] = array($rel . ' 쓰기 권한', false, '쓰기 권한이 없어 설치가 이 파일을 패치할 수 없습니다: ' . $p);
            }
        } catch (Throwable $e) {
            $checks[] = array($rel . ' 쓰기 권한', false, $e->getMessage());
        }
    }

    // IMAP 외부 연결(Gmail 기준 최소 확인, 방화벽/네트워크 문제 조기 발견용)
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client('ssl://imap.gmail.com:993', $errno, $errstr, 5);
    if ($fp) {
        fclose($fp);
        $checks[] = array('IMAP 외부 연결(993/SSL)', true, 'imap.gmail.com 연결 확인');
    } else {
        $checks[] = array('IMAP 외부 연결(993/SSL)', null, '연결 실패: ' . $errstr . ' (방화벽/네트워크 확인 필요)');
    }

    // 자동 동기화 워커(worker_idle.php)는 CLI 전용 가드가 있어 CLI php 로만 뜬다.
    // 웹 SAPI 의 php-cgi/php-fpm 이 아니라, 같은 폴더의 php(.exe) 를 찾을 수 있어야
    // "시작" 버튼이 실제로 동작한다. api/worker.php 의 mbx_worker_php_bin() 과 동일한
    // 후보 경로로 CLI 바이너리 존재만 확인한다(찾지 못하면 PATH 폴백이라 경고).
    $mbxIsWin = stripos(PHP_OS, 'WIN') === 0;
    $mbxCliExe = $mbxIsWin ? 'php.exe' : 'php';
    $mbxCliCandidates = array();
    if (defined('PHP_BINARY') && PHP_BINARY !== '') {
        $mbxCliCandidates[] = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . $mbxCliExe;
    }
    if (defined('PHP_BINDIR') && PHP_BINDIR !== '') {
        $mbxCliCandidates[] = rtrim(PHP_BINDIR, '\/') . DIRECTORY_SEPARATOR . $mbxCliExe;
    }
    $mbxCliPath = '';
    foreach ($mbxCliCandidates as $mbxCand) {
        if (is_file($mbxCand)) {
            $mbxCliPath = $mbxCand;
            break;
        }
    }
    if ($mbxCliPath !== '') {
        $checks[] = array('자동 동기화 워커(CLI php)', true, $mbxCliPath);
    } else {
        $checks[] = array('자동 동기화 워커(CLI php)', null, 'CLI php 실행 파일을 못 찾아 PATH 의 ' . $mbxCliExe . ' 로 폴백합니다. 워커 "시작"이 STOPPED 로 남으면 php.exe(CLI) 경로를 확인하세요.');
    }

    if (defined('MBX_SYNC_KEY') && MBX_SYNC_KEY === 'change-this-mailbox-sync-key') {
        $checks[] = array('동기화 키(MBX_SYNC_KEY)', null, '기본값 그대로입니다. 아래 설정에서 무작위 키로 변경하세요.');
    } else {
        $checks[] = array('동기화 키(MBX_SYNC_KEY)', true, '설정됨');
    }
    return $checks;
}

// ── DB 상태 ───────────────────────────────────────────────────
function mbx_install_db_status()
{
    $status = array('connected' => false, 'server' => '', 'database' => '', 'tables' => array());
    try {
        $db = mbx_db();
        $status['connected'] = true;
        $status['server'] = mysqli_get_server_info($db);
        $res = mysqli_query($db, 'SELECT DATABASE() AS d');
        if ($res && ($r = mysqli_fetch_assoc($res))) {
            $status['database'] = (string)$r['d'];
        }
        $manifest = mbx_install_manifest();
        foreach ($manifest['tables'] as $table) {
            $info = array('name' => $table, 'exists' => false, 'rows' => 0, 'collation' => '');
            if (mbx_install_table_exists($db, $table)) {
                $info['exists'] = true;
                $res = mysqli_query($db, 'SELECT COUNT(*) AS c FROM ' . $table);
                if ($res && ($r = mysqli_fetch_assoc($res))) {
                    $info['rows'] = (int)$r['c'];
                }
                $res = mysqli_query($db, "SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . mysqli_real_escape_string($db, $table) . "' LIMIT 1");
                if ($res && ($r = mysqli_fetch_assoc($res))) {
                    $info['collation'] = (string)$r['TABLE_COLLATION'];
                }
            }
            $status['tables'][] = $info;
        }
        $res = mysqli_query($db, "SELECT COUNT(*) AS c FROM mailbox_accounts WHERE is_active=1");
        $status['accounts'] = ($res && ($r = mysqli_fetch_assoc($res))) ? (int)$r['c'] : null;
        $res = mysqli_query($db, "SELECT COUNT(*) AS c FROM mailbox_admins");
        $status['admins'] = ($res && ($r = mysqli_fetch_assoc($res))) ? (int)$r['c'] : null;
    } catch (Throwable $e) {
        $status['error'] = $e->getMessage();
    }
    return $status;
}

// ── 설치 실행(단계별 결과 수집) ───────────────────────────────
function mbx_install_run_steps(mysqli $db)
{
    $steps = array();
    $run = function ($label, $fn) use (&$steps) {
        try {
            $detail = $fn();
            $steps[] = array($label, true, $detail === null ? '완료' : (string)$detail);
        } catch (Throwable $e) {
            $steps[] = array($label, false, $e->getMessage());
        }
    };

    $run('plugin.json 동적 경로 갱신', function () {
        mbx_install_write_manifest(mbx_install_manifest());
        return '완료';
    });
    $run('DB 테이블 생성/업그레이드', function () use ($db) {
        MailboxSync::ensureTables($db);
        return '완료 (컬럼·인덱스·collation 자동 보정 포함)';
    });
    $run('uploads 디렉터리 보호', function () {
        mbx_install_ensure_upload_guard();
        return '완료';
    });
    $run('side_m.php 사이드바 훅', function () {
        return mbx_install_ensure_side_menu_hook();
    });
    $run('메뉴 링크 절대경로 패치(func_list.php)', function () {
        return mbx_install_ensure_menu_link_patch();
    });
    $run('헤더 링크 절대경로 패치(header.php)', function () {
        return mbx_install_ensure_header_link_patch();
    });
    $run('메일함 관리자 등록', function () use ($db) {
        $userid = php_sapi_name() === 'cli' ? 'admin' : mbx_install_current_userid();
        if ($userid === '') {
            return '건너뜀 (로그인 사용자 없음)';
        }
        mbx_add_admin($db, $userid);
        return $userid . ' 등록 완료';
    });

    $manifest = mbx_install_manifest();
    foreach ($manifest['tables'] as $table) {
        $steps[] = array('테이블 확인: ' . $table, mbx_install_table_exists($db, $table), mbx_install_table_exists($db, $table) ? '정상' : '누락');
    }
    return $steps;
}

function mbx_install_uninstall_steps(mysqli $db)
{
    $steps = array();
    $tables = array(
        'mailbox_attachments',
        'mailbox_messages',
        'mailbox_folders',
        'mailbox_admins',
        'mailbox_accounts',
    );
    foreach ($tables as $table) {
        if (mysqli_query($db, 'DROP TABLE IF EXISTS ' . $table)) {
            $steps[] = array('테이블 삭제: ' . $table, true, '완료');
        } else {
            $steps[] = array('테이블 삭제: ' . $table, false, mysqli_error($db));
        }
    }
    return $steps;
}

// ── CSRF 토큰(더블 서브밋 쿠키) ───────────────────────────────
function mbx_install_token()
{
    if (!empty($_COOKIE['mbx_install_token'])) {
        return (string)$_COOKIE['mbx_install_token'];
    }
    $token = mbx_install_random_key();
    setcookie('mbx_install_token', $token, 0, '/');
    $_COOKIE['mbx_install_token'] = $token;
    return $token;
}

function mbx_install_check_token()
{
    $cookie = isset($_COOKIE['mbx_install_token']) ? (string)$_COOKIE['mbx_install_token'] : '';
    $posted = isset($_POST['token']) ? (string)$_POST['token'] : '';
    return $cookie !== '' && $posted !== '' && hash_equals($cookie, $posted);
}

// ════════════════════════════════════════════════════════════
// CLI 모드: 기존과 동일하게 텍스트로 설치/제거 실행
// ════════════════════════════════════════════════════════════
if (php_sapi_name() === 'cli') {
    try {
        $db = mbx_db();
        if (mbx_install_has_cli_option('--uninstall')) {
            if (!mbx_install_has_cli_option('--yes')) {
                mbx_install_out('삭제 확인 옵션이 없어 중단했습니다.');
                mbx_install_out('CLI: php install.php --uninstall --yes');
                exit(1);
            }
            foreach (mbx_install_uninstall_steps($db) as $step) {
                mbx_install_out(($step[1] ? '[OK] ' : '[실패] ') . $step[0] . ': ' . $step[2]);
            }
            mbx_install_out('메일함 플러그인 DB 테이블 삭제가 완료되었습니다.');
        } else {
            mbx_install_out('=== 환경 점검 ===');
            foreach (mbx_install_env_checks() as $check) {
                $mark = $check[1] === true ? '[OK]' : ($check[1] === null ? '[주의]' : '[실패]');
                mbx_install_out($mark . ' ' . $check[0] . ': ' . $check[2]);
            }
            mbx_install_out('');
            mbx_install_out('=== 설치 실행 ===');
            foreach (mbx_install_run_steps($db) as $step) {
                mbx_install_out(($step[1] ? '[OK] ' : '[실패] ') . $step[0] . ': ' . $step[2]);
            }
            mbx_install_out('');
            mbx_install_out('진입 경로: ' . mbx_plugin_url('index.php'));
        }
    } catch (Throwable $e) {
        mbx_install_out('오류: ' . $e->getMessage());
        exit(1);
    }
    exit(0);
}

// ════════════════════════════════════════════════════════════
// 웹 모드: 통합 설치 대시보드
// ════════════════════════════════════════════════════════════
mbx_install_require_admin();
$mbxToken = mbx_install_token();

$resultTitle = '';
$resultSteps = array();
$resultError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mbx_install_check_token()) {
        $resultError = '보안 토큰이 일치하지 않습니다. 페이지를 새로고침한 뒤 다시 시도하세요.';
    } else {
        $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
        try {
            $db = mbx_db();
            if ($action === 'install') {
                $resultTitle = '설치/재설치 결과';
                $resultSteps = mbx_install_run_steps($db);
            } elseif ($action === 'save_config') {
                $resultTitle = '설정 저장 결과';
                $newValues = array(
                    'sync_key' => isset($_POST['sync_key']) ? trim((string)$_POST['sync_key']) : '',
                    'initial_sync_limit' => isset($_POST['initial_sync_limit']) ? (int)$_POST['initial_sync_limit'] : 500,
                    'inbox_max_messages' => isset($_POST['inbox_max_messages']) ? (int)$_POST['inbox_max_messages'] : 5000,
                    'max_attach' => isset($_POST['max_attach']) ? (int)$_POST['max_attach'] : 5,
                    'max_attach_size_mb' => isset($_POST['max_attach_size_mb']) ? (int)$_POST['max_attach_size_mb'] : 20,
                    'demo' => !empty($_POST['demo']),
                    'demo_admin' => isset($_POST['demo_admin']) ? trim((string)$_POST['demo_admin']) : 'admin',
                    'demo_user' => isset($_POST['demo_user']) ? trim((string)$_POST['demo_user']) : '',
                    'admin_dir' => isset($_POST['admin_dir']) ? trim((string)$_POST['admin_dir']) : '',
                    'login_cookies' => isset($_POST['login_cookies']) ? trim((string)$_POST['login_cookies']) : '',
                    'db_host' => isset($_POST['db_host']) ? trim((string)$_POST['db_host']) : '',
                    'db_port' => isset($_POST['db_port']) ? (int)$_POST['db_port'] : 3306,
                    'db_user' => isset($_POST['db_user']) ? trim((string)$_POST['db_user']) : '',
                    'db_pass' => isset($_POST['db_pass']) ? (string)$_POST['db_pass'] : '',
                    'db_name' => isset($_POST['db_name']) ? trim((string)$_POST['db_name']) : '',
                );
                // 사용자 입력 검증: admin 디렉터리 경로 / 전용 DB 는 저장 전에 실제로 확인한다.
                $adminDirInput = rtrim(str_replace('\\', '/', $newValues['admin_dir']), '/');
                if ($adminDirInput !== '' && !file_exists($adminDirInput . '/include/inc_base.php')) {
                    throw new RuntimeException('ERP admin 디렉터리가 올바르지 않습니다 (include/inc_base.php 를 찾을 수 없음): ' . $adminDirInput);
                }
                if ($newValues['db_host'] !== '') {
                    $testDb = @mysqli_connect($newValues['db_host'], $newValues['db_user'], $newValues['db_pass'], $newValues['db_name'], max(1, (int)$newValues['db_port']));
                    if (!$testDb) {
                        throw new RuntimeException('전용 DB 연결 테스트 실패: ' . mysqli_connect_error());
                    }
                    mysqli_close($testDb);
                }
                $saveMsg = mbx_install_save_config($newValues);
                $resultSteps[] = array('config.php 저장', true, $saveMsg);
                // 저장된 값을 화면에 반영하기 위해 리다이렉트 (상수는 재정의 불가)
                header('Location: ' . mbx_plugin_url('install.php?saved=1'));
                exit;
            } elseif ($action === 'save_dbconn') {
                $vals = array(
                    'db_host' => isset($_POST['erp_db_host']) ? trim((string)$_POST['erp_db_host']) : '',
                    'db_port' => isset($_POST['erp_db_port']) ? trim((string)$_POST['erp_db_port']) : '3306',
                    'db_user' => isset($_POST['erp_db_user']) ? trim((string)$_POST['erp_db_user']) : '',
                    'db_passwd' => isset($_POST['erp_db_passwd']) ? (string)$_POST['erp_db_passwd'] : '',
                    'db_name' => isset($_POST['erp_db_name']) ? trim((string)$_POST['erp_db_name']) : '',
                );
                if ($vals['db_host'] === '' || $vals['db_user'] === '' || $vals['db_name'] === '') {
                    throw new RuntimeException('호스트/사용자/DB명은 비울 수 없습니다.');
                }
                // 저장 전 실제 연결 테스트 (host 에 :포트 포함 형태 지원)
                $testHost = $vals['db_host'];
                $testPort = (int)$vals['db_port'];
                if (strpos($testHost, ':') !== false) {
                    list($h, $p) = explode(':', $testHost, 2);
                    if ((int)$p > 0) {
                        $testHost = $h;
                        $testPort = (int)$p;
                    }
                }
                $testDb = @mysqli_connect($testHost, $vals['db_user'], $vals['db_passwd'], $vals['db_name'], $testPort > 0 ? $testPort : 3306);
                if (!$testDb) {
                    throw new RuntimeException('ERP DB 연결 테스트 실패: ' . mysqli_connect_error());
                }
                mysqli_close($testDb);
                mbx_install_patch_dbconn(mbx_install_dbconn_path(), $vals);
                header('Location: ' . mbx_plugin_url('install.php?saved=1'));
                exit;
            } elseif ($action === 'save_folders') {
                global $MBX_FOLDERS, $MBX_PROVIDERS;
                $folders = is_array($MBX_FOLDERS) ? $MBX_FOLDERS : array();
                $providers = is_array($MBX_PROVIDERS) ? $MBX_PROVIDERS : array();
                $inFolders = isset($_POST['folders']) && is_array($_POST['folders']) ? $_POST['folders'] : array();
                foreach ($folders as $prov => $map) {
                    if (!is_array($map) || !isset($inFolders[$prov]) || !is_array($inFolders[$prov])) {
                        continue;
                    }
                    foreach ($map as $key => $imapName) {
                        if (isset($inFolders[$prov][$key]) && trim((string)$inFolders[$prov][$key]) !== '') {
                            $folders[$prov][$key] = trim((string)$inFolders[$prov][$key]);
                        }
                    }
                }
                $inProviders = isset($_POST['providers']) && is_array($_POST['providers']) ? $_POST['providers'] : array();
                foreach ($providers as $prov => $preset) {
                    if (!is_array($preset) || !isset($inProviders[$prov]) || !is_array($inProviders[$prov])) {
                        continue;
                    }
                    foreach (array('imap_host', 'smtp_host') as $key) {
                        if (isset($inProviders[$prov][$key]) && trim((string)$inProviders[$prov][$key]) !== '') {
                            $providers[$prov][$key] = trim((string)$inProviders[$prov][$key]);
                        }
                    }
                    foreach (array('imap_port', 'smtp_port') as $key) {
                        if (isset($inProviders[$prov][$key]) && (int)$inProviders[$prov][$key] > 0) {
                            $providers[$prov][$key] = (int)$inProviders[$prov][$key];
                        }
                    }
                }
                mbx_install_save_arrays($folders, $providers);
                header('Location: ' . mbx_plugin_url('install.php?saved=1'));
                exit;
            } elseif ($action === 'uninstall') {
                $confirm = isset($_POST['confirm']) ? (string)$_POST['confirm'] : '';
                if ($confirm !== 'DELETE_MAILBOX_PLUGIN') {
                    $resultError = '삭제 확인 문구가 일치하지 않습니다. DELETE_MAILBOX_PLUGIN 을 정확히 입력하세요.';
                } else {
                    $resultTitle = '플러그인 DB 제거 결과';
                    $resultSteps = mbx_install_uninstall_steps($db);
                }
            } else {
                $resultError = '알 수 없는 작업입니다.';
            }
        } catch (Throwable $e) {
            $resultError = $e->getMessage();
        }
    }
}

$envChecks = mbx_install_env_checks();
$dbStatus = mbx_install_db_status();
$configValues = mbx_install_config_values();
try {
    $erpDbValues = mbx_install_dbconn_values(mbx_install_dbconn_path());
    $erpDbEditable = is_writable(mbx_install_dbconn_path());
} catch (Throwable $e) {
    $erpDbValues = null;
    $erpDbEditable = false;
}
global $MBX_FOLDERS, $MBX_PROVIDERS;
$uiFolders = is_array($MBX_FOLDERS) ? $MBX_FOLDERS : array();
$uiProviders = is_array($MBX_PROVIDERS) ? $MBX_PROVIDERS : array();
$uiFolderLabels = array('inbox' => '받은편지함', 'sent' => '보낸편지함', 'trash' => '휴지통');
$fileStatus = array(
    array('plugin.json', file_exists(mbx_install_manifest_path()), mbx_install_manifest_path()),
    array('side_m.php 사이드바 훅', mbx_install_side_menu_hook_applied(), '메일함 메뉴를 ERP 사이드바에 표시'),
    array('func_list.php 메뉴 링크 패치', mbx_install_menu_link_patch_applied(), '메일함 페이지에서 ERP 메뉴 경로 유지'),
    array('header.php 헤더 링크 패치', mbx_install_header_link_patch_applied(), '브랜드·통합예약검색 링크 절대경로화'),
    array('uploads/.htaccess 보호', file_exists(__DIR__ . '/uploads/.htaccess'), '첨부 임시 폴더 직접 접근 차단'),
);
$webRoot = rtrim(mbx_plugin_web_root(), '/');
$syncKeyIsDefault = $configValues['sync_key'] === 'change-this-mailbox-sync-key';

function mbx_install_badge($state)
{
    if ($state === true) {
        return '<span class="badge ok">정상</span>';
    }
    if ($state === null) {
        return '<span class="badge warn">주의</span>';
    }
    return '<span class="badge fail">누락/실패</span>';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>메일함 플러그인 통합 설치</title>
<style>
* { box-sizing: border-box; }
body { font-family: 'Malgun Gothic', Arial, sans-serif; font-size: 14px; color: #333; background: #f4f6f8; margin: 0; padding: 24px; }
.wrap { max-width: 980px; margin: 0 auto; }
h1 { font-size: 22px; margin: 0 0 4px; }
.sub { color: #777; margin-bottom: 20px; }
.card { background: #fff; border: 1px solid #e2e6ea; border-radius: 6px; margin-bottom: 18px; overflow: hidden; }
.card h2 { font-size: 16px; margin: 0; padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e6ea; }
.card .body { padding: 14px 16px; }
table.status { width: 100%; border-collapse: collapse; }
table.status th, table.status td { padding: 7px 10px; border-bottom: 1px solid #f0f2f4; text-align: left; vertical-align: top; font-size: 13px; }
table.status th { width: 260px; color: #555; font-weight: normal; white-space: nowrap; }
table.status tr:last-child th, table.status tr:last-child td { border-bottom: 0; }
.badge { display: inline-block; min-width: 64px; text-align: center; padding: 2px 8px; border-radius: 3px; font-size: 12px; color: #fff; margin-right: 8px; }
.badge.ok { background: #4caf50; }
.badge.warn { background: #ff9800; }
.badge.fail { background: #e53935; }
.detail { color: #888; font-size: 12px; word-break: break-all; }
.form-row { display: flex; align-items: center; margin-bottom: 10px; gap: 10px; flex-wrap: wrap; }
.form-row label { width: 240px; color: #555; }
.form-row input[type=text], .form-row input[type=number] { padding: 6px 8px; border: 1px solid #ccd3da; border-radius: 4px; width: 300px; font-size: 13px; }
.form-row input[type=number] { width: 120px; }
.form-row .hint { color: #999; font-size: 12px; }
.btn { display: inline-block; padding: 8px 18px; border: 1px solid #ccd3da; border-radius: 4px; background: #fff; color: #333; font-size: 14px; cursor: pointer; text-decoration: none; }
.btn:hover { background: #f2f5f8; }
.btn.primary { background: #1976d2; border-color: #1976d2; color: #fff; }
.btn.primary:hover { background: #145ba6; }
.btn.danger { background: #fff; border-color: #e53935; color: #e53935; }
.btn.danger:hover { background: #fdeceb; }
.btn.small { padding: 4px 10px; font-size: 12px; }
.result { border: 1px solid #cfe3cf; background: #f2faf2; border-radius: 6px; padding: 12px 16px; margin-bottom: 18px; }
.result.err { border-color: #f2c4c2; background: #fdf3f2; }
.result h3 { margin: 0 0 8px; font-size: 15px; }
.result ul { margin: 0; padding-left: 18px; }
.result li { margin: 3px 0; font-size: 13px; }
.result li.fail { color: #c62828; }
code { background: #eef1f4; padding: 1px 5px; border-radius: 3px; font-size: 12px; }
.actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.topnav { margin-bottom: 16px; }
</style>
</head>
<body>
<div class="wrap">
  <h1>메일함 플러그인 통합 설치</h1>
  <div class="sub">환경 점검 → DB → 파일 패치 → 설정을 이 화면 하나에서 확인하고 실행합니다. 설치는 여러 번 실행해도 안전합니다(멱등).</div>
  <div class="topnav"><a class="btn small" href="<?php echo mbx_h($webRoot . '/index.php'); ?>">&larr; 메일함으로 이동</a></div>

  <?php if (isset($_GET['saved'])): ?>
    <div class="result"><h3>설정 저장 완료</h3><ul><li>config.php 가 갱신되었습니다. (백업 파일이 함께 생성됨)</li></ul></div>
  <?php endif; ?>
  <?php if ($resultError !== ''): ?>
    <div class="result err"><h3>오류</h3><ul><li class="fail"><?php echo mbx_h($resultError); ?></li></ul></div>
  <?php endif; ?>
  <?php if ($resultSteps): ?>
    <div class="result"><h3><?php echo mbx_h($resultTitle); ?></h3><ul>
      <?php foreach ($resultSteps as $step): ?>
        <li class="<?php echo $step[1] ? '' : 'fail'; ?>"><?php echo $step[1] ? '✔' : '✖'; ?> <?php echo mbx_h($step[0]); ?> — <?php echo mbx_h($step[2]); ?></li>
      <?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <div class="card">
    <h2>1. 환경 점검</h2>
    <div class="body">
      <table class="status">
        <?php foreach ($envChecks as $check): ?>
          <tr><th><?php echo mbx_h($check[0]); ?></th><td><?php echo mbx_install_badge($check[1]); ?><span class="detail"><?php echo mbx_h($check[2]); ?></span></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>2. 데이터베이스</h2>
    <div class="body">
      <table class="status">
        <tr><th>DB 연결</th><td><?php echo mbx_install_badge($dbStatus['connected']); ?><span class="detail"><?php echo mbx_h(isset($dbStatus['error']) ? $dbStatus['error'] : ($dbStatus['server'] . ' / ' . $dbStatus['database'])); ?></span></td></tr>
        <?php foreach ($dbStatus['tables'] as $t): ?>
          <tr><th><?php echo mbx_h($t['name']); ?></th><td><?php echo mbx_install_badge($t['exists']); ?><span class="detail"><?php echo $t['exists'] ? mbx_h(number_format($t['rows']) . '행 / ' . $t['collation']) : '설치 실행 시 자동 생성됩니다'; ?></span></td></tr>
        <?php endforeach; ?>
        <?php if (isset($dbStatus['accounts']) && $dbStatus['accounts'] !== null): ?>
          <tr><th>등록된 메일 계정</th><td><?php echo mbx_install_badge($dbStatus['accounts'] > 0 ? true : null); ?><span class="detail"><?php echo (int)$dbStatus['accounts']; ?>개 <?php echo $dbStatus['accounts'] === 0 ? '— 설치 후 [계정 관리]에서 등록하세요' : ''; ?></span></td></tr>
        <?php endif; ?>
        <?php if (isset($dbStatus['admins']) && $dbStatus['admins'] !== null): ?>
          <tr><th>메일함 관리자</th><td><?php echo mbx_install_badge($dbStatus['admins'] > 0 ? true : null); ?><span class="detail"><?php echo (int)$dbStatus['admins']; ?>명 — 설치 실행 시 현재 로그인 계정이 자동 등록됩니다</span></td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>3. 파일 / ERP 연동 패치</h2>
    <div class="body">
      <table class="status">
        <?php foreach ($fileStatus as $f): ?>
          <tr><th><?php echo mbx_h($f[0]); ?></th><td><?php echo mbx_install_badge($f[1]); ?><span class="detail"><?php echo mbx_h($f[2]); ?></span></td></tr>
        <?php endforeach; ?>
      </table>
      <p class="detail" style="margin:10px 0 0">패치는 원본을 자동 백업(<code>*.mailbox_backup_날짜</code>)한 뒤 적용되며, 이미 적용된 파일은 건너뜁니다.</p>
    </div>
  </div>

  <div class="card">
    <h2>4. 설정 (config.php)</h2>
    <div class="body">
      <form method="post" action="">
        <input type="hidden" name="token" value="<?php echo mbx_h($mbxToken); ?>">
        <input type="hidden" name="action" value="save_config">
        <div class="form-row">
          <label>동기화 키 (MBX_SYNC_KEY)</label>
          <input type="text" name="sync_key" id="syncKey" value="<?php echo mbx_h($configValues['sync_key']); ?>">
          <button type="button" class="btn small" id="btnGenKey">무작위 생성</button>
          <?php if ($syncKeyIsDefault): ?><span class="hint" style="color:#e53935">기본값입니다 — 반드시 변경하세요</span><?php endif; ?>
        </div>
        <div class="form-row">
          <label>초기 동기화 최대 통수</label>
          <input type="number" name="initial_sync_limit" value="<?php echo (int)$configValues['initial_sync_limit']; ?>" min="0">
          <span class="hint">계정 첫 동기화 때 폴더당 가져올 최근 메일 수 (0 = 무제한)</span>
        </div>
        <div class="form-row">
          <label>받은편지함 보관 상한</label>
          <input type="number" name="inbox_max_messages" value="<?php echo (int)$configValues['inbox_max_messages']; ?>" min="0">
          <span class="hint">초과분은 오래된 것부터 로컬 목록에서 정리 (0 = 무제한)</span>
        </div>
        <div class="form-row">
          <label>첨부 최대 개수 / 크기(MB)</label>
          <input type="number" name="max_attach" value="<?php echo (int)$configValues['max_attach']; ?>" min="1" style="width:80px">
          <input type="number" name="max_attach_size_mb" value="<?php echo (int)$configValues['max_attach_size_mb']; ?>" min="1" style="width:80px">
          <span class="hint">메일 쓰기에서 첨부할 수 있는 파일 제한</span>
        </div>
        <div class="form-row">
          <label>데모 모드 (MBX_DEMO)</label>
          <label style="width:auto"><input type="checkbox" name="demo" value="1" <?php echo $configValues['demo'] ? 'checked' : ''; ?>> 사용</label>
          <span class="hint">켜면 아래 지정한 아이디만 메일함 접근 가능</span>
        </div>
        <div class="form-row">
          <label>데모 관리자 / 사용자 아이디</label>
          <input type="text" name="demo_admin" value="<?php echo mbx_h($configValues['demo_admin']); ?>" style="width:140px" placeholder="관리자">
          <input type="text" name="demo_user" value="<?php echo mbx_h($configValues['demo_user']); ?>" style="width:140px" placeholder="사용자(선택)">
        </div>

        <hr style="border:0;border-top:1px solid #eef1f4;margin:16px 0">
        <p style="margin:0 0 10px;font-weight:bold;color:#444">환경 연결 (폴더 · 쿠키 · DB)</p>
        <div class="form-row">
          <label>ERP admin 디렉터리 (폴더)</label>
          <input type="text" name="admin_dir" value="<?php echo mbx_h($configValues['admin_dir']); ?>" placeholder="비우면 자동 탐지 (현재: <?php echo mbx_h(mbx_admin_dir()); ?>)" style="width:420px">
        </div>
        <div class="form-row">
          <label>로그인 쿠키 이름 (쉼표 구분)</label>
          <input type="text" name="login_cookies" value="<?php echo mbx_h($configValues['login_cookies']); ?>" style="width:420px" placeholder="MEMLOGIN_ADMIN_PURUN,MEMLOGIN_ADMIN_PARAN">
          <span class="hint">앞에서부터 순서대로 확인합니다</span>
        </div>
        <div class="form-row">
          <label>전용 DB 호스트 / 포트</label>
          <input type="text" name="db_host" value="<?php echo mbx_h($configValues['db_host']); ?>" style="width:220px" placeholder="비우면 ERP 연결 상속">
          <input type="number" name="db_port" value="<?php echo (int)$configValues['db_port']; ?>" min="1" style="width:90px">
          <span class="hint">비우면 ERP 기본 연결(dbconn.php)을 그대로 사용</span>
        </div>
        <div class="form-row">
          <label>전용 DB 사용자 / 비밀번호 / DB명</label>
          <input type="text" name="db_user" value="<?php echo mbx_h($configValues['db_user']); ?>" style="width:130px" placeholder="사용자">
          <input type="password" name="db_pass" value="<?php echo mbx_h($configValues['db_pass']); ?>" style="width:130px;padding:6px 8px;border:1px solid #ccd3da;border-radius:4px;font-size:13px" placeholder="비밀번호">
          <input type="text" name="db_name" value="<?php echo mbx_h($configValues['db_name']); ?>" style="width:130px" placeholder="DB명">
          <span class="hint">저장 시 연결 테스트 후 반영됩니다</span>
        </div>
        <div class="actions" style="margin-top:14px">
          <button type="submit" class="btn primary">설정 저장</button>
          <span class="hint">저장 시 config.php 를 백업 후 갱신합니다. 폴더 매핑($MBX_FOLDERS)·제공자 프리셋은 그대로 유지됩니다.</span>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <h2>5. 메일 폴더 매핑 · 제공자 프리셋 (config.php)</h2>
    <div class="body">
      <form method="post" action="">
        <input type="hidden" name="token" value="<?php echo mbx_h($mbxToken); ?>">
        <input type="hidden" name="action" value="save_folders">
        <?php foreach ($uiFolders as $prov => $map): if (!is_array($map)) { continue; } ?>
          <p style="margin:0 0 8px;font-weight:bold;color:#444"><?php echo mbx_h(isset($uiProviders[$prov]['label']) ? $uiProviders[$prov]['label'] : $prov); ?> 폴더 매핑</p>
          <?php foreach ($map as $key => $imapName): ?>
            <div class="form-row">
              <label><?php echo mbx_h(isset($uiFolderLabels[$key]) ? $uiFolderLabels[$key] . ' (' . $key . ')' : $key); ?></label>
              <input type="text" name="folders[<?php echo mbx_h($prov); ?>][<?php echo mbx_h($key); ?>]" value="<?php echo mbx_h($imapName); ?>" style="width:300px">
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php foreach ($uiProviders as $prov => $preset): if (!is_array($preset)) { continue; } ?>
          <p style="margin:12px 0 8px;font-weight:bold;color:#444"><?php echo mbx_h(isset($preset['label']) ? $preset['label'] : $prov); ?> 서버 프리셋</p>
          <div class="form-row">
            <label>IMAP 호스트 / 포트</label>
            <input type="text" name="providers[<?php echo mbx_h($prov); ?>][imap_host]" value="<?php echo mbx_h(isset($preset['imap_host']) ? $preset['imap_host'] : ''); ?>" style="width:220px">
            <input type="number" name="providers[<?php echo mbx_h($prov); ?>][imap_port]" value="<?php echo (int)(isset($preset['imap_port']) ? $preset['imap_port'] : 993); ?>" min="1" style="width:90px">
          </div>
          <div class="form-row">
            <label>SMTP 호스트 / 포트</label>
            <input type="text" name="providers[<?php echo mbx_h($prov); ?>][smtp_host]" value="<?php echo mbx_h(isset($preset['smtp_host']) ? $preset['smtp_host'] : ''); ?>" style="width:220px">
            <input type="number" name="providers[<?php echo mbx_h($prov); ?>][smtp_port]" value="<?php echo (int)(isset($preset['smtp_port']) ? $preset['smtp_port'] : 587); ?>" min="1" style="width:90px">
          </div>
        <?php endforeach; ?>
        <div class="actions" style="margin-top:14px">
          <button type="submit" class="btn primary">폴더 매핑 · 프리셋 저장</button>
          <span class="hint">폴더 인식은 IMAP SPECIAL-USE(\Sent,\Trash)가 우선이고 이 매핑은 폴백으로 사용됩니다. 계정 등록 시 서버 프리셋이 자동 입력됩니다.</span>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <h2>6. ERP DB 연결 (include/dbconn.php)</h2>
    <div class="body">
      <?php if ($erpDbValues === null): ?>
        <p class="detail">dbconn.php 를 찾을 수 없습니다.</p>
      <?php else: ?>
      <form method="post" action="">
        <input type="hidden" name="token" value="<?php echo mbx_h($mbxToken); ?>">
        <input type="hidden" name="action" value="save_dbconn">
        <div class="form-row">
          <label>호스트 / 포트</label>
          <input type="text" name="erp_db_host" value="<?php echo mbx_h($erpDbValues['db_host']); ?>" style="width:220px" placeholder="host 또는 host:port">
          <input type="number" name="erp_db_port" value="<?php echo (int)$erpDbValues['db_port']; ?>" min="1" style="width:90px">
        </div>
        <div class="form-row">
          <label>사용자 / 비밀번호</label>
          <input type="text" name="erp_db_user" value="<?php echo mbx_h($erpDbValues['db_user']); ?>" style="width:150px">
          <input type="password" name="erp_db_passwd" value="<?php echo mbx_h($erpDbValues['db_passwd']); ?>" style="width:150px;padding:6px 8px;border:1px solid #ccd3da;border-radius:4px;font-size:13px">
        </div>
        <div class="form-row">
          <label>DB명</label>
          <input type="text" name="erp_db_name" value="<?php echo mbx_h($erpDbValues['db_name']); ?>" style="width:150px">
        </div>
        <div class="actions" style="margin-top:14px">
          <button type="submit" class="btn danger" onclick="return confirm('ERP 전체가 사용하는 DB 연결(dbconn.php)을 변경합니다. 계속할까요?');" <?php echo $erpDbEditable ? '' : 'disabled'; ?>>ERP DB 연결 저장</button>
          <span class="hint" style="color:#e53935">주의: 메일함뿐 아니라 ERP 전체에 적용됩니다. 저장 전 연결 테스트를 통과해야 하며, 원본은 자동 백업됩니다.</span>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h2>7. 설치 실행</h2>
    <div class="body">
      <form method="post" action="" style="margin-bottom:12px">
        <input type="hidden" name="token" value="<?php echo mbx_h($mbxToken); ?>">
        <input type="hidden" name="action" value="install">
        <div class="actions">
          <button type="submit" class="btn primary">설치 / 재설치 실행</button>
          <span class="hint">DB 테이블 생성·업그레이드, 파일 패치, plugin.json 갱신, 관리자 등록을 한 번에 수행합니다.</span>
        </div>
      </form>
      <table class="status">
        <tr><th>자동 동기화(크론) 설정</th><td><span class="detail">
          CLI: <code>php <?php echo mbx_h(str_replace('\\', '/', __DIR__)); ?>/api/sync.php</code> (모든 활성 계정 동기화)<br>
          웹훅: <code><?php echo mbx_h($webRoot); ?>/api/sync.php?key=동기화키</code> — 5~10분 간격 권장
        </span></td></tr>
        <tr><th>설치 후 절차</th><td><span class="detail">① [계정 관리]에서 메일 계정(앱 비밀번호) 등록 → ② 사이드바 [동기화] 실행 → ③ INBOX 확인</span></td></tr>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>8. 플러그인 제거</h2>
    <div class="body">
      <form method="post" action="" onsubmit="return confirm('메일함 DB 테이블을 모두 삭제합니다. 계속할까요?');">
        <input type="hidden" name="token" value="<?php echo mbx_h($mbxToken); ?>">
        <input type="hidden" name="action" value="uninstall">
        <div class="form-row">
          <label>확인 문구 입력</label>
          <input type="text" name="confirm" placeholder="DELETE_MAILBOX_PLUGIN" autocomplete="off">
          <button type="submit" class="btn danger">DB 테이블 전체 삭제</button>
        </div>
        <p class="detail" style="margin:4px 0 0">메일함 테이블 5개(계정·메시지·폴더·관리자·첨부)를 삭제합니다. 파일 패치 원복은 각 <code>*.mailbox_backup_*</code> 백업 파일로 수동 복원하세요.</p>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('btnGenKey').addEventListener('click', function () {
  var chars = 'abcdef0123456789';
  var key = '';
  if (window.crypto && window.crypto.getRandomValues) {
    var buf = new Uint8Array(32);
    window.crypto.getRandomValues(buf);
    for (var i = 0; i < buf.length; i++) { key += chars[buf[i] % chars.length]; }
  } else {
    for (var j = 0; j < 32; j++) { key += chars[Math.floor(Math.random() * chars.length)]; }
  }
  document.getElementById('syncKey').value = key;
});
</script>
</body>
</html>
