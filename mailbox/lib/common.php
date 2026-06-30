<?php
if (!defined('MBX_COMMON_LOADED')) {
    define('MBX_COMMON_LOADED', true);

    require_once __DIR__ . '/bootstrap.php';
    require_once dirname(__DIR__) . '/config.php';
    require_once __DIR__ . '/ImapClient.php';
    require_once __DIR__ . '/MimeParser.php';
    require_once __DIR__ . '/MailboxSync.php';

    function mbx_db()
    {
        $db = null;
        if (isset($GLOBALS['dbConn']) && $GLOBALS['dbConn'] instanceof mysqli) {
            $db = $GLOBALS['dbConn'];
        } elseif (isset($GLOBALS['mysql_compat_default_link']) && $GLOBALS['mysql_compat_default_link'] instanceof mysqli) {
            $db = $GLOBALS['mysql_compat_default_link'];
        }
        if (!$db) {
            throw new RuntimeException('DB connection not found.');
        }
        // 메일함 테이블은 utf8mb4 인데 레거시 연결은 dbconn.php 에서 매 요청 utf8(utf8mb3)로 설정된다.
        // 그 상태로 prepared statement 파라미터를 utf8mb4 컬럼에 바인딩하면 MySQL 8.0 이
        // "Conversion from collation utf8mb3_general_ci into utf8mb4_* impossible for parameter" 로
        // INSERT 를 거부해 메일이 한 통도 저장되지 않는다(초기 동기화 실패의 원인).
        // 메일함 작업 동안만 연결을 utf8mb4 로 올린다. dbconn.php 가 다음 요청마다 utf8mb3 로
        // 되돌리고 레거시 페이지는 inline 쿼리(파라미터 바인딩 없음)라 영구 연결이라도 영향이 없다.
        static $charsetSet = false;
        if (!$charsetSet) {
            @mysqli_set_charset($db, 'utf8mb4');
            $charsetSet = true;
        }
        return $db;
    }

    function mbx_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    function mbx_json($data, $code = 200)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 데모 모드 ──────────────────────────────────────────────
    // config.php 의 MBX_DEMO 가 켜져 있으면 지정한 아이디 2개만 메일함을 쓸 수 있다.
    function mbx_demo_enabled()
    {
        return defined('MBX_DEMO') && MBX_DEMO;
    }

    function mbx_demo_admin_id()
    {
        return defined('MBX_DEMO_ADMIN') ? trim((string)MBX_DEMO_ADMIN) : '';
    }

    function mbx_demo_user_id()
    {
        return defined('MBX_DEMO_USER') ? trim((string)MBX_DEMO_USER) : '';
    }

    // 데모 모드에서 메일함을 쓸 수 있는 아이디 목록 (관리자 1 + 사용자 1)
    function mbx_demo_allowed_userids()
    {
        $ids = array();
        $admin = mbx_demo_admin_id();
        $user = mbx_demo_user_id();
        if ($admin !== '') {
            $ids[] = $admin;
        }
        if ($user !== '') {
            $ids[] = $user;
        }
        return $ids;
    }

    // 데모 모드가 아니면 항상 허용. 데모 모드면 지정한 2개 아이디만 허용.
    function mbx_demo_is_allowed_user()
    {
        if (!mbx_demo_enabled()) {
            return true;
        }
        $uid = mbx_current_userid();
        if ($uid === '') {
            return false;
        }
        return in_array($uid, mbx_demo_allowed_userids(), true);
    }

    function mbx_require_api_auth()
    {
        if (mbx_current_userid() === '') {
            mbx_json(array('status' => 'error', 'message' => '로그인이 필요합니다.'), 401);
        }
        if (mbx_demo_enabled() && !mbx_demo_is_allowed_user()) {
            mbx_json(array('status' => 'error', 'message' => '데모 메일함은 지정된 데모 계정만 사용할 수 있습니다.'), 403);
        }
    }

    function mbx_require_page_auth()
    {
        if (mbx_current_userid() === '') {
            echo "<meta http-equiv='refresh' content='0; url=/login.php'>";
            exit;
        }
        if (mbx_demo_enabled() && !mbx_demo_is_allowed_user()) {
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="margin:40px;font-family:sans-serif;font-size:14px">데모 메일함은 지정된 데모 계정만 사용할 수 있습니다.</div>';
            exit;
        }
    }

    function mbx_stmt(mysqli $db, $sql, $types = '', array $params = array())
    {
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException(mysqli_error($db));
        }
        if ($types !== '') {
            $refs = array();
            $refs[] = $types;
            foreach ($params as $k => $v) {
                $refs[] = &$params[$k];
            }
            call_user_func_array(array($stmt, 'bind_param'), $refs);
        }
        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException($err);
        }
        return $stmt;
    }

    function mbx_fetch_all_stmt(mysqli_stmt $stmt)
    {
        $res = mysqli_stmt_get_result($stmt);
        $rows = array();
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $rows[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }

    function mbx_fetch_one_stmt(mysqli_stmt $stmt)
    {
        $rows = mbx_fetch_all_stmt($stmt);
        return isset($rows[0]) ? $rows[0] : null;
    }

    // 현재 사용자는 인트라넷 본체(header.php/inc_base.php)가 준비한 전역 사용자 정보를 상속받는다.
    function mbx_current_user()
    {
        static $cached = null;
        static $done = false;
        if ($done) {
            return $cached;
        }
        $done = true;
        if (isset($GLOBALS['user_info']) && is_array($GLOBALS['user_info']) && !empty($GLOBALS['user_info']['user_id'])) {
            return $cached = $GLOBALS['user_info'];
        }
        if (isset($GLOBALS['user_dbinfo']) && is_array($GLOBALS['user_dbinfo']) && !empty($GLOBALS['user_dbinfo']['userid'])) {
            return $cached = array(
                'user_id' => (string)$GLOBALS['user_dbinfo']['userid'],
                'division' => isset($GLOBALS['user_dbinfo']['division']) ? $GLOBALS['user_dbinfo']['division'] : '',
                'user_level' => isset($GLOBALS['user_dbinfo']['user_level']) ? $GLOBALS['user_dbinfo']['user_level'] : '',
            );
        }
        // 본체가 전역을 준비하지 못한 진입점(install.php/index.php 처럼 inc_base/header 를
        // 함수 스코프에서 require 하는 경우)에서는 로그인 쿠키를 직접 디코딩한다.
        // 쿠키 이름은 프로젝트마다 다르다: 푸른(PURUN) / 파란(PARAN).
        if (function_exists('getinfo_Member')) {
            $cookieNames = array('MEMLOGIN_ADMIN_PURUN', 'MEMLOGIN_ADMIN_PARAN');
            foreach ($cookieNames as $cookieName) {
                if (empty($_COOKIE[$cookieName])) {
                    continue;
                }
                $userInfo = getinfo_Member($_COOKIE[$cookieName]);
                if (is_array($userInfo) && !empty($userInfo['user_id'])) {
                    $GLOBALS['user_info'] = $userInfo;
                    if ((!isset($GLOBALS['user_dbinfo']) || !is_array($GLOBALS['user_dbinfo'])) && function_exists('getinfo_dbMember')) {
                        $GLOBALS['user_dbinfo'] = getinfo_dbMember($userInfo['user_id']);
                    }
                    return $cached = $userInfo;
                }
            }
        }
        return $cached = null;
    }

    function mbx_current_userid()
    {
        $u = mbx_current_user();
        return $u ? (string)$u['user_id'] : '';
    }

    function mbx_is_root_admin()
    {
        if (mbx_demo_enabled()) {
            return mbx_current_userid() === mbx_demo_admin_id();
        }
        return mbx_current_userid() === 'admin';
    }

    // 메일함 전용 관리자 아이디 목록 (member_list.division 과 무관, mailbox_admins 테이블로 별도 관리)
    function mbx_admin_userids(mysqli $db)
    {
        MailboxSync::ensureTables($db);
        $rows = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT userid FROM mailbox_admins ORDER BY userid ASC"));
        $ids = array();
        foreach ($rows as $r) {
            $ids[] = (string)$r['userid'];
        }
        return $ids;
    }

    function mbx_is_admin()
    {
        static $cached = null;
        static $done = false;
        if ($done) {
            return $cached;
        }
        $done = true;
        $uid = mbx_current_userid();
        if ($uid === '') {
            return $cached = false;
        }
        // 항상 관리자로 인정하는 슈퍼 관리자 아이디
        //  - 라이브: 'admin'
        //  - 데모  : MBX_DEMO_ADMIN
        if (mbx_demo_enabled()) {
            if ($uid === mbx_demo_admin_id()) {
                return $cached = true;
            }
        } elseif ($uid === 'admin') {
            return $cached = true;
        }
        // 그 외에는 mailbox_admins 테이블 등록 여부로 판단한다(라이브·데모 공통).
        // 데모 모드에서도 "계정 관리"로 관리자에 등록한 사용자는 관리자로 인정한다.
        try {
            $db = mbx_db();
        } catch (Exception $e) {
            return $cached = false;
        }
        $admins = mbx_admin_userids($db);
        return $cached = in_array($uid, $admins, true);
    }

    function mbx_can_manage_common_accounts()
    {
        return mbx_is_admin();
    }

    function mbx_add_admin(mysqli $db, $userid)
    {
        $userid = trim((string)$userid);
        if ($userid === '') {
            return;
        }
        $stmt = mbx_stmt($db, "INSERT INTO mailbox_admins (userid, added_by, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE added_by=VALUES(added_by)", 'ss', array($userid, mbx_current_userid()));
        mysqli_stmt_close($stmt);
    }

    function mbx_remove_admin(mysqli $db, $userid)
    {
        $stmt = mbx_stmt($db, "DELETE FROM mailbox_admins WHERE userid=?", 's', array((string)$userid));
        mysqli_stmt_close($stmt);
    }

    // 현재 사용자가 소유한 계정 (본인 계정 편집 화면용)
    function mbx_own_accounts(mysqli $db)
    {
        MailboxSync::ensureTables($db);
        return mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts WHERE owner_userid=? ORDER BY sort_order ASC, id ASC", 's', array(mbx_current_userid())));
    }

    // 현재 사용자가 볼 수 있는 계정만: 관리자는 전체, 그 외는 본인 소유 + 공통(is_common)만
    function mbx_visible_accounts(mysqli $db)
    {
        MailboxSync::ensureTables($db);
        if (mbx_is_admin()) {
            return mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts ORDER BY sort_order ASC, id ASC"));
        }
        return mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts WHERE is_active=1 AND (owner_userid=? OR is_common=1) ORDER BY sort_order ASC, id ASC", 's', array(mbx_current_userid())));
    }

    // 소유자 지정용: 로그인 가능한 직원(member_list) 목록
    function mbx_member_users(mysqli $db)
    {
        $rows = array();
        $res = @mysqli_query($db, "SELECT userid, kor_name, eng_name, email FROM member_list WHERE division='admin' AND (out_yn IS NULL OR out_yn<>'1') AND userid IS NOT NULL AND userid<>'' ORDER BY kor_name ASC, userid ASC");
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    function mbx_current_account(mysqli $db)
    {
        MailboxSync::ensureTables($db);
        if (mbx_is_admin()) {
            $cond = "is_active=1";
            $types = '';
            $params = array();
        } else {
            $cond = "is_active=1 AND (owner_userid=? OR is_common=1)";
            $types = 's';
            $params = array(mbx_current_userid());
        }
        $cookieId = isset($_COOKIE['mbx_account_id']) ? (int)$_COOKIE['mbx_account_id'] : 0;
        if ($cookieId > 0) {
            $row = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts WHERE id=? AND " . $cond, 'i' . $types, array_merge(array($cookieId), $params)));
            if ($row) {
                return $row;
            }
        }
        return mbx_fetch_one_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts WHERE " . $cond . " ORDER BY sort_order ASC, id ASC LIMIT 1", $types, $params));
    }

    function mbx_get_account(mysqli $db, $accountId, $activeOnly = true)
    {
        $accountId = (int)$accountId;
        if ($activeOnly) {
            return mbx_fetch_one_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts WHERE id=? AND is_active=1", 'i', array($accountId)));
        }
        return mbx_fetch_one_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts WHERE id=?", 'i', array($accountId)));
    }

    function mbx_accounts(mysqli $db)
    {
        MailboxSync::ensureTables($db);
        return mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts ORDER BY sort_order ASC, id ASC"));
    }

    function mbx_size($bytes)
    {
        $bytes = (int)$bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        return number_format(max(1, ceil($bytes / 1024))) . ' KB';
    }

    function mbx_date_label($date)
    {
        if (!$date) {
            return '';
        }
        $ts = strtotime($date);
        if (!$ts) {
            return '';
        }
        if (date('Y-m-d', $ts) === date('Y-m-d')) {
            return date('H:i', $ts);
        }
        if (date('Y', $ts) === date('Y')) {
            return date('m-d', $ts);
        }
        return date('Y-m-d', $ts);
    }

    function mbx_redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
}
?>
