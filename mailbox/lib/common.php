<?php
if (!defined('MBX_COMMON_LOADED')) {
    define('MBX_COMMON_LOADED', true);

    require_once __DIR__ . '/bootstrap.php';
    require dirname(__DIR__) . '/config.php';
    require_once __DIR__ . '/ImapClient.php';
    require_once __DIR__ . '/OAuthClient.php';
    require_once __DIR__ . '/MimeParser.php';
    require_once __DIR__ . '/MailboxSync.php';

    function mbx_db()
    {
        // 설치 화면에서 전용 DB(MBX_DB_HOST)를 지정하면 ERP 연결 대신 그 연결을 쓴다.
        static $ownDb = null;
        if (defined('MBX_DB_HOST') && trim((string)MBX_DB_HOST) !== '') {
            if ($ownDb instanceof mysqli) {
                return $ownDb;
            }
            $ownDb = @mysqli_connect(MBX_DB_HOST, MBX_DB_USER, MBX_DB_PASS, MBX_DB_NAME, (int)MBX_DB_PORT);
            if (!$ownDb) {
                throw new RuntimeException('메일함 전용 DB 연결 실패: ' . mysqli_connect_error());
            }
            @mysqli_set_charset($ownDb, 'utf8mb4');
            return $ownDb;
        }

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

    // 데모 모드가 아니면 항상 허용. 데모 모드면 "계정 관리"에 관리자로 등록된
    // 사용자(mailbox_admins)와 데모 최고관리자(MBX_DEMO_ADMIN)만 허용하고, 그 외
    // 사용자는 메일함 접근·사이드 메뉴를 모두 차단한다. (사이드바 노출을 판정하는
    // mbx_plugin_menu_entry_visible() 도 데모 모드에서 mbx_is_admin() 을 쓰므로,
    // 접근 권한과 메뉴 노출 기준이 "관리자 등록 여부"로 하나로 통일된다.)
    function mbx_demo_is_allowed_user()
    {
        if (!mbx_demo_enabled()) {
            return true;
        }
        if (mbx_current_userid() === '') {
            return false;
        }
        return mbx_is_admin();
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

    // 설치 화면(config.php: MBX_LOGIN_COOKIES)에서 지정한 로그인 쿠키 이름 목록.
    function mbx_login_cookie_names()
    {
        $raw = defined('MBX_LOGIN_COOKIES') ? trim((string)MBX_LOGIN_COOKIES) : '';
        $names = array();
        foreach (explode(',', $raw) as $name) {
            $name = trim($name);
            if ($name !== '') {
                $names[] = $name;
            }
        }
        return $names ? $names : array('MEMLOGIN_ADMIN_PURUN', 'MEMLOGIN_ADMIN_PARAN');
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
            $cookieNames = mbx_login_cookie_names();
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

    // 계정 가시성 조건. 비관리자 사용자가 볼 수 있는 계정은 오직
    // 본인 소유(owner_userid) 이거나 공통 열람 대상으로 명시 지정된 경우뿐이다.
    // (공통이라도 선택된 사람에게만 보이며, 전체 공개 폴백은 없다.)
    // 반환된 조건의 물음표 개수만큼 현재 사용자 아이디를 두 번 바인딩해야 한다.
    function mbx_visible_condition_sql()
    {
        return "(owner_userid=? "
            . "OR EXISTS (SELECT 1 FROM mailbox_account_owners o WHERE o.account_id=mailbox_accounts.id AND o.userid=?))";
    }

    // 특정 공통 계정의 열람 대상 사용자 아이디 목록
    function mbx_account_owner_ids(mysqli $db, $accountId)
    {
        MailboxSync::ensureTables($db);
        $rows = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT userid FROM mailbox_account_owners WHERE account_id=? ORDER BY userid ASC", 'i', array((int)$accountId)));
        $ids = array();
        foreach ($rows as $r) {
            $ids[] = (string)$r['userid'];
        }
        return $ids;
    }

    // 공통 계정의 열람 대상 목록을 통째로 교체 저장한다. 빈 배열이면 전체 공개로 되돌린다.
    function mbx_save_account_owners(mysqli $db, $accountId, array $userids)
    {
        MailboxSync::ensureTables($db);
        $accountId = (int)$accountId;
        $stmt = mbx_stmt($db, "DELETE FROM mailbox_account_owners WHERE account_id=?", 'i', array($accountId));
        mysqli_stmt_close($stmt);
        $seen = array();
        foreach ($userids as $uid) {
            $uid = trim((string)$uid);
            if ($uid === '' || isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = true;
            $stmt = mbx_stmt($db, "INSERT INTO mailbox_account_owners (account_id, userid, created_at) VALUES (?, ?, NOW())", 'is', array($accountId, $uid));
            mysqli_stmt_close($stmt);
        }
    }

    // 주어진 계정 행이 현재 사용자에게 열람 가능한지 판정한다(mbx_visible_condition_sql 과 동일한 규칙).
    // account_id 로 직접 계정을 여는 API 진입점에서 소유/공통 권한을 강제하는 데 쓴다.
    function mbx_account_visible(mysqli $db, array $account)
    {
        if (mbx_is_admin()) {
            return true;
        }
        $uid = mbx_current_userid();
        if ($uid === '') {
            return false;
        }
        if (isset($account['owner_userid']) && (string)$account['owner_userid'] === $uid) {
            return true;
        }
        // 공통이라도 명시적으로 지정된 열람 대상에게만 보인다(전체 공개 없음).
        return in_array($uid, mbx_account_owner_ids($db, (int)$account['id']), true);
    }

    // 현재 사용자가 볼 수 있는 계정만: 관리자는 전체, 그 외는 본인 소유 + 공통 열람 대상만
    function mbx_visible_accounts(mysqli $db)
    {
        MailboxSync::ensureTables($db);
        if (mbx_is_admin()) {
            return mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts ORDER BY sort_order ASC, id ASC"));
        }
        $uid = mbx_current_userid();
        return mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_accounts WHERE is_active=1 AND " . mbx_visible_condition_sql() . " ORDER BY sort_order ASC, id ASC", 'ss', array($uid, $uid)));
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
            $uid = mbx_current_userid();
            $cond = "is_active=1 AND " . mbx_visible_condition_sql();
            $types = 'ss';
            $params = array($uid, $uid);
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


    function mbx_secret_key()
    {
        if (!defined('MBX_TOKEN_KEY') || trim((string)MBX_TOKEN_KEY) === '') {
            return '';
        }
        return hash('sha256', (string)MBX_TOKEN_KEY, true);
    }

    function mbx_secret_encrypt($value)
    {
        $value = (string)$value;
        $key = mbx_secret_key();
        if ($value === '' || $key === '' || !function_exists('openssl_encrypt')) {
            return $value;
        }
        $iv = function_exists('random_bytes') ? random_bytes(12) : openssl_random_pseudo_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false || $tag === '') {
            return $value;
        }
        return 'mbxenc1:' . base64_encode($iv . $tag . $cipher);
    }

    function mbx_secret_decrypt($value)
    {
        $value = (string)$value;
        if (strpos($value, 'mbxenc1:') !== 0) {
            return $value;
        }
        $key = mbx_secret_key();
        if ($key === '' || !function_exists('openssl_decrypt')) {
            throw new RuntimeException('토큰 복호화 키 MBX_TOKEN_KEY 설정이 필요합니다.');
        }
        $raw = base64_decode(substr($value, 8), true);
        if ($raw === false || strlen($raw) < 29) {
            throw new RuntimeException('저장된 OAuth 토큰 형식이 올바르지 않습니다.');
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new RuntimeException('OAuth 토큰 복호화에 실패했습니다.');
        }
        return $plain;
    }

    function mbx_account_manage_allowed(array $account)
    {
        if (mbx_is_admin()) {
            return true;
        }
        return isset($account['owner_userid']) && (string)$account['owner_userid'] === mbx_current_userid();
    }

    function mbx_account_oauth_provider(array $account)
    {
        $provider = isset($account['oauth_provider']) ? strtolower(trim((string)$account['oauth_provider'])) : '';
        if ($provider !== '') {
            return $provider;
        }
        $preset = isset($account['provider']) ? strtolower(trim((string)$account['provider'])) : '';
        if ($preset === 'microsoft365' || $preset === 'outlook') {
            return 'microsoft';
        }
        return 'google';
    }

    function mbx_account_access_token(mysqli $db, array &$account, $forceRefresh = false)
    {
        $authType = isset($account['auth_type']) ? strtolower(trim((string)$account['auth_type'])) : 'password';
        if ($authType !== 'oauth2') {
            return '';
        }
        $expires = isset($account['oauth_token_expires']) ? (int)$account['oauth_token_expires'] : 0;
        $accessToken = isset($account['oauth_access_token']) ? mbx_secret_decrypt($account['oauth_access_token']) : '';
        // $forceRefresh 가 true 면(예: 저장된 토큰을 IMAP 서버가 거부) 만료 전이라도 refresh_token 으로 새로 발급받는다.
        if (!$forceRefresh && $accessToken !== '' && $expires > time() + 60) {
            return $accessToken;
        }
        $refreshToken = isset($account['oauth_refresh_token']) ? mbx_secret_decrypt($account['oauth_refresh_token']) : '';
        if ($refreshToken === '') {
            throw new RuntimeException('OAuth 계정 연결이 필요합니다.');
        }
        $provider = mbx_account_oauth_provider($account);
        $token = mbx_oauth_refresh($provider, $refreshToken);
        $newAccess = (string)$token['access_token'];
        $newRefresh = !empty($token['refresh_token']) ? (string)$token['refresh_token'] : $refreshToken;
        $newExpires = time() + (isset($token['expires_in']) ? (int)$token['expires_in'] : 3600);
        $encAccess = mbx_secret_encrypt($newAccess);
        $encRefresh = mbx_secret_encrypt($newRefresh);
        $stmt = mbx_stmt($db, "UPDATE mailbox_accounts SET oauth_provider=?, oauth_access_token=?, oauth_refresh_token=?, oauth_token_expires=? WHERE id=?", 'sssii', array($provider, $encAccess, $encRefresh, $newExpires, (int)$account['id']));
        mysqli_stmt_close($stmt);
        $account['oauth_provider'] = $provider;
        $account['oauth_access_token'] = $encAccess;
        $account['oauth_refresh_token'] = $encRefresh;
        $account['oauth_token_expires'] = $newExpires;
        return $newAccess;
    }

    function mbx_imap_connect(mysqli $db, array &$account, $timeout = 30)
    {
        $authType = isset($account['auth_type']) ? strtolower(trim((string)$account['auth_type'])) : 'password';
        if ($authType === 'oauth2') {
            // 1차: 저장된(또는 만료 시 자동 갱신된) access token 으로 인증.
            // XOAUTH2 가 거부되면(토큰 조기 만료·재발급·부분 폐기) refresh_token 으로 강제 재발급 후 한 번 더 시도한다.
            for ($attempt = 0; $attempt < 2; $attempt++) {
                $client = new ImapClient($account['imap_host'], (int)$account['imap_port'], $timeout);
                $client->connect();
                try {
                    $client->authenticateXOAuth2($account['email'], mbx_account_access_token($db, $account, $attempt > 0));
                    return $client;
                } catch (RuntimeException $e) {
                    $client->logout();
                    if ($attempt > 0 || strpos($e->getMessage(), 'XOAUTH2') === false) {
                        throw $e;
                    }
                    // 재시도 전 강제 갱신 대상을 명확히 하기 위해 만료 시각을 0 으로 낮춰 캐시를 무효화한다.
                    $account['oauth_token_expires'] = 0;
                }
            }
            throw new RuntimeException('IMAP OAuth 인증에 실패했습니다.');
        }
        $client = new ImapClient($account['imap_host'], (int)$account['imap_port'], $timeout);
        $client->connect();
        $pass = isset($account['app_password']) ? (string)$account['app_password'] : '';
        if ($pass === '') {
            throw new RuntimeException('앱 비밀번호가 필요합니다.');
        }
        $client->login($account['email'], $pass);
        return $client;
    }
    function mbx_default_folder_rows()
    {
        $labels = array(
            'inbox' => json_decode('"\\ubc1b\\uc740\\uba54\\uc77c"', true),
            'sent' => json_decode('"\\ubcf4\\ub0b8\\uba54\\uc77c"', true),
            'trash' => json_decode('"\\ud734\\uc9c0\\ud1b5"', true),
        );
        $rows = array();
        $sort = 0;
        foreach ($labels as $key => $label) {
            $rows[] = array('folder_key' => $key, 'imap_name' => $key === 'inbox' ? 'INBOX' : $key, 'display_name' => $label, 'is_selectable' => 1, 'is_visible' => 1, 'sort_order' => $sort++);
        }
        return $rows;
    }

    function mbx_account_folders(mysqli $db, $accountId, $visibleOnly = true)
    {
        MailboxSync::ensureTables($db);
        $allRows = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT * FROM mailbox_folders WHERE account_id=? AND is_selectable=1 ORDER BY sort_order ASC, id ASC", 'i', array((int)$accountId)));
        if (!$allRows) {
            return mbx_default_folder_rows();
        }
        if (!$visibleOnly) {
            return $allRows;
        }
        $rows = array();
        foreach ($allRows as $row) {
            if ((int)$row['is_visible'] === 1) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    function mbx_unread_counts(mysqli $db, $accountId, array $folderRows)
    {
        $counts = array();
        $keys = array();
        foreach ($folderRows as $folderRow) {
            $key = isset($folderRow['folder_key']) ? trim((string)$folderRow['folder_key']) : '';
            if ($key === '') { continue; }
            $counts[$key] = 0;
            $keys[] = $key;
        }
        if (!$keys) {
            return $counts;
        }
        $ph = implode(',', array_fill(0, count($keys), '?'));
        $rows = mbx_fetch_all_stmt(mbx_stmt(
            $db,
            "SELECT folder_key, COUNT(*) AS c FROM mailbox_messages WHERE account_id=? AND is_read=0 AND folder_key IN (" . $ph . ") GROUP BY folder_key",
            'i' . str_repeat('s', count($keys)),
            array_merge(array((int)$accountId), $keys)
        ));
        foreach ($rows as $row) {
            $key = (string)$row['folder_key'];
            if (isset($counts[$key])) {
                $counts[$key] = (int)$row['c'];
            }
        }
        return $counts;
    }

    function mbx_folder_allowed(mysqli $db, array $account, $folderKey, $visibleOnly = true)
    {
        $folderKey = (string)$folderKey;
        $row = mbx_fetch_one_stmt(mbx_stmt($db, "SELECT folder_key, is_selectable, is_visible FROM mailbox_folders WHERE account_id=? AND folder_key=? LIMIT 1", 'is', array((int)$account['id'], $folderKey)));
        if ($row) {
            return (int)$row['is_selectable'] === 1 && (!$visibleOnly || (int)$row['is_visible'] === 1);
        }
        return in_array($folderKey, array('inbox', 'sent', 'trash'), true);
    }

    function mbx_folder_display_name(array $folder)
    {
        if (isset($folder['display_name']) && trim((string)$folder['display_name']) !== '') {
            $name = (string)$folder['display_name'];
        } elseif (isset($folder['imap_name']) && trim((string)$folder['imap_name']) !== '') {
            $name = (string)$folder['imap_name'];
        } else {
            $name = isset($folder['folder_key']) ? (string)$folder['folder_key'] : '';
        }
        if (class_exists('ImapClient')) {
            $name = ImapClient::decodeMailboxName($name);
        }
        $name = str_replace('\\', '/', $name);
        $lower = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
        foreach (array('[gmail]/', '[google mail]/') as $prefix) {
            if (strpos($lower, $prefix) === 0) {
                $name = substr($name, strlen($prefix));
                break;
            }
        }
        return trim($name);
    }

    function mbx_save_folder_visibility(mysqli $db, $accountId, array $visibleKeys)
    {
        MailboxSync::ensureTables($db);
        $visible = array();
        foreach ($visibleKeys as $key) {
            $key = trim((string)$key);
            if ($key !== '') { $visible[$key] = true; }
        }
        $rows = mbx_fetch_all_stmt(mbx_stmt($db, "SELECT folder_key FROM mailbox_folders WHERE account_id=? AND is_selectable=1", 'i', array((int)$accountId)));
        foreach ($rows as $row) {
            $key = (string)$row['folder_key'];
            $isVisible = isset($visible[$key]) ? 1 : 0;
            $stmt = mbx_stmt($db, "UPDATE mailbox_folders SET is_visible=? WHERE account_id=? AND folder_key=?", 'iis', array($isVisible, (int)$accountId, $key));
            mysqli_stmt_close($stmt);
        }
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
