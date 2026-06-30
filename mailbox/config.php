<?php
if (!defined('MBX_SYNC_KEY')) {
    define('MBX_SYNC_KEY', 'change-this-mailbox-sync-key');
}
if (!defined('MBX_INITIAL_SYNC_LIMIT')) {
    define('MBX_INITIAL_SYNC_LIMIT', 6000);
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

// ── 데모 모드 ──────────────────────────────────────────────
// MBX_DEMO 가 true 면 ERP 로그인은 그대로 쓰되, 아래 지정한 아이디 2개
// (관리자 1명·사용자 1명)만 메일함에 접근할 수 있다. 그 외 계정은 차단된다.
// MBX_DEMO_ADMIN 으로 지정한 아이디가 데모 모드의 메일 관리자(=root) 역할을 한다.
if (!defined('MBX_DEMO')) {
    define('MBX_DEMO', false); // 데모 모드 여부 (true/false)
}
if (!defined('MBX_DEMO_ADMIN')) {
    define('MBX_DEMO_ADMIN', 'admin'); // 데모 관리자 아이디 (member_list.userid)
}
if (!defined('MBX_DEMO_USER')) {
    define('MBX_DEMO_USER', ''); // 데모 일반 사용자 아이디 (member_list.userid)
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
    ),
);
?>
