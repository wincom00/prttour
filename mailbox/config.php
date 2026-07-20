<?php
// 비밀값(OAuth client secret, 토큰 암호화 키)은 git 에 올리지 않는 config.local.php 에서 로드한다.
// config.local.php 가 먼저 define 하면 아래 config.php 의 빈 기본값은 건너뛴다.
$__mbx_local_config = __DIR__ . '/config.local.php';
if (is_file($__mbx_local_config)) {
    require $__mbx_local_config;
}
unset($__mbx_local_config);

if (!defined('MBX_SYNC_KEY')) {
    define('MBX_SYNC_KEY', 'change-this-mailbox-sync-key');
}
if (!defined('MBX_INITIAL_SYNC_LIMIT')) {
    // 최초 동기화 때 폴더당 가져오는 최신 메일 헤더 수(본문·첨부는 열 때만 받음).
    // 크게 잡으면 초기 스캔이 폴더마다 수천 통이라 느리므로, 목록 몇 페이지 분량만
    // 먼저 받아 빠르게 뜨게 한다(20통/페이지 × 25 = 500). 그보다 오래된 메일은
    // 필요 시 수동 전체 동기화로 채운다.
    define('MBX_INITIAL_SYNC_LIMIT', 500);
}
if (!defined('MBX_INBOX_MAX_MESSAGES')) {
    define('MBX_INBOX_MAX_MESSAGES', 5000);
}
if (!defined('MBX_MAX_ATTACH')) {
    define('MBX_MAX_ATTACH', 5);
}
if (!defined('MBX_MAX_ATTACH_SIZE')) {
    define('MBX_MAX_ATTACH_SIZE', 20 * 1024 * 1024);
}
if (!defined('MBX_IDLE_TIMEOUT_SECONDS')) {
    define('MBX_IDLE_TIMEOUT_SECONDS', 55);
}
if (!defined('MBX_IDLE_FULL_POLL_SECONDS')) {
    define('MBX_IDLE_FULL_POLL_SECONDS', 300);
}
if (!defined('MBX_IDLE_FOLDERS')) {
    define('MBX_IDLE_FOLDERS', 'inbox');
}// ── OAuth2 설정 ─────────────────────────────────────────────
// Google Cloud / Microsoft Entra 에서 웹 OAuth 클라이언트를 만든 뒤 값을 입력한다.
// MBX_OAUTH_REDIRECT 는 mailbox/api/oauth.php 를 가리키는 공개 HTTPS 절대 URL이어야 한다.
if (!defined('MBX_GOOGLE_CLIENT_ID')) {
    define('MBX_GOOGLE_CLIENT_ID', '1056728240063-dnofu7qv4oa1s77usga2573vmhv9t7hh.apps.googleusercontent.com');
}
// 실제 값은 config.local.php 에 둔다(위에서 먼저 로드). 여기 빈 값은 폴백일 뿐이다.
if (!defined('MBX_GOOGLE_CLIENT_SECRET')) {
    define('MBX_GOOGLE_CLIENT_SECRET', '');
}
if (!defined('MBX_MS_CLIENT_ID')) {
    define('MBX_MS_CLIENT_ID', '');
}
if (!defined('MBX_MS_CLIENT_SECRET')) {
    define('MBX_MS_CLIENT_SECRET', '');
}
if (!defined('MBX_MS_TENANT')) {
    define('MBX_MS_TENANT', 'common');
}
// Google Cloud 콘솔에 등록된 승인된 리디렉션 URI 와 정확히 일치해야 한다.
// 현재 등록값: https://myprt.biz/mailbox/  → mailbox 루트(index.php)가 콜백을 가로채 처리한다.
if (!defined('MBX_OAUTH_REDIRECT')) {
    define('MBX_OAUTH_REDIRECT', 'https://myprt.biz/mailbox/');
}
// OAuth 토큰 저장 암호화 키(AES-256-GCM). 실제 값은 config.local.php 에 둔다.
// 비우면 평문 저장으로 폴백한다. 이 키를 바꾸면 기존 저장 토큰은 복호화 불가 → 계정 재연결 필요.
if (!defined('MBX_TOKEN_KEY')) {
    define('MBX_TOKEN_KEY', '');
}

// ── 환경 연결 설정 (설치 화면 install.php 에서 입력 가능) ──────
// ERP admin 디렉터리 절대경로. 비우면 자동 탐지(플러그인 상위 폴더 등)한다.
if (!defined('MBX_ADMIN_DIR')) {
    define('MBX_ADMIN_DIR', '');
}
// ERP 로그인 쿠키 이름(쉼표 구분, 앞에서부터 순서대로 확인).
// 프로젝트마다 다르다: 푸른(PURUN) / 파란(PARAN) 등.
if (!defined('MBX_LOGIN_COOKIES')) {
    define('MBX_LOGIN_COOKIES', 'MEMLOGIN_ADMIN_PURUN');
}
// 메일함 전용 DB 연결. MBX_DB_HOST 를 비우면 ERP 기본 연결(dbconn.php)을 상속한다.
if (!defined('MBX_DB_HOST')) {
    define('MBX_DB_HOST', '');
}
if (!defined('MBX_DB_PORT')) {
    define('MBX_DB_PORT', 3306);
}
if (!defined('MBX_DB_USER')) {
    define('MBX_DB_USER', '');
}
if (!defined('MBX_DB_PASS')) {
    define('MBX_DB_PASS', '');
}
if (!defined('MBX_DB_NAME')) {
    define('MBX_DB_NAME', '');
}

// ── 데모 모드 ──────────────────────────────────────────────
// MBX_DEMO 가 true 면 ERP 로그인은 그대로 쓰되, "계정 관리"에서 관리자로 등록한
// 사용자(mailbox_admins)와 데모 최고관리자(MBX_DEMO_ADMIN)만 메일함에 접근할 수
// 있고 사이드 메뉴도 이들에게만 보인다. 그 외 계정은 접근·메뉴 모두 차단된다.
// MBX_DEMO_ADMIN 으로 지정한 아이디가 데모 모드의 메일 관리자(=root) 역할을 한다.
if (!defined('MBX_DEMO')) {
    define('MBX_DEMO', false); // 데모 모드 여부 (true/false)
}
if (!defined('MBX_DEMO_ADMIN')) {
    define('MBX_DEMO_ADMIN', 'admin'); // 데모 관리자 아이디 (member_list.userid)
}
// (구버전 호환용) 예전엔 데모 사용자 1명을 여기서 지정했지만, 이제 데모 접근 권한은
// "관리자 등록 여부(mailbox_admins)"로 판정하므로 이 값은 접근 제어에 쓰이지 않는다.
if (!defined('MBX_DEMO_USER')) {
    define('MBX_DEMO_USER', ''); // (미사용) 데모 일반 사용자 아이디
}

$MBX_FOLDERS = array(
    'gmail' => array(
        'inbox' => 'INBOX',
        'sent' => '[Gmail]/Sent Mail',
        'trash' => '[Gmail]/Trash',
    ),
    'outlook' => array(
        'inbox' => 'INBOX',
        'sent' => 'Sent Items',
        'trash' => 'Deleted Items',
    ),
);

// 메일 제공자 프리셋: 계정 등록 시 IMAP/SMTP 호스트·포트를 자동 채움.
// 폴더 인식은 IMAP SPECIAL-USE(\Sent,\Trash) 우선이라 제공자와 무관하게 동작한다.
// provider 값은 mailbox_accounts.provider 에 저장되어 Gmail 전용 확장(X-GM-THRID)을 켜고 끄는 데 쓰인다.
$MBX_PROVIDERS = array(
    'gmail' => array(
        'label' => 'Gmail',
        'imap_host' => 'imap.gmail.com',
        'imap_port' => 993,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'apppw_url' => 'https://myaccount.google.com/apppasswords',
        'apppw_label' => 'Gmail은 2단계 인증 후 발급한 앱 비밀번호를 입력하세요.',
            'supports_oauth' => true,
),
    'outlook' => array(
        // 개인 Outlook(outlook.com/hotmail/live). 앱 비밀번호 기반 기본 인증.
        'label' => 'Outlook',
        'imap_host' => 'outlook.office365.com',
        'imap_port' => 993,
        'smtp_host' => 'smtp-mail.outlook.com',
        'smtp_port' => 587,
        'apppw_url' => 'https://account.live.com/proofs/AppPassword',
        'apppw_label' => 'Outlook은 2단계 인증 후 발급한 앱 비밀번호를 입력하세요. (개인 outlook.com/hotmail 계정)',
            'supports_oauth' => false,
),
    'microsoft365' => array(
        'label' => 'Microsoft 365',
        'imap_host' => 'outlook.office365.com',
        'imap_port' => 993,
        'smtp_host' => 'smtp.office365.com',
        'smtp_port' => 587,
        'apppw_url' => 'https://entra.microsoft.com/',
        'apppw_label' => 'Microsoft 365 조직 계정은 OAuth2 연결을 사용하세요.',
        'supports_oauth' => true,
    ),
);

$MBX_OAUTH = array(
    'google' => array(
        'label' => 'Google',
        'client_id' => MBX_GOOGLE_CLIENT_ID,
        'client_secret' => MBX_GOOGLE_CLIENT_SECRET,
        'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'scope' => 'https://mail.google.com/',
        'redirect_uri' => MBX_OAUTH_REDIRECT,
    ),
    'microsoft' => array(
        'label' => 'Microsoft',
        'client_id' => MBX_MS_CLIENT_ID,
        'client_secret' => MBX_MS_CLIENT_SECRET,
        'auth_url' => 'https://login.microsoftonline.com/' . rawurlencode(MBX_MS_TENANT) . '/oauth2/v2.0/authorize',
        'token_url' => 'https://login.microsoftonline.com/' . rawurlencode(MBX_MS_TENANT) . '/oauth2/v2.0/token',
        'scope' => 'https://outlook.office365.com/IMAP.AccessAsUser.All https://outlook.office365.com/SMTP.Send offline_access',
        'redirect_uri' => MBX_OAUTH_REDIRECT,
    ),
);
?>