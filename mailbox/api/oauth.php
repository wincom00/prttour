<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
mbx_require_admin_file('include/inc_base.php');
require_once dirname(__DIR__) . '/lib/common.php';
mbx_require_page_auth();

function mbx_oauth_session_start()
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
}

function mbx_oauth_random_state()
{
    $raw = function_exists('random_bytes') ? random_bytes(24) : openssl_random_pseudo_bytes(24);
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

function mbx_oauth_account_provider(array $account)
{
    $provider = isset($_GET['provider']) ? strtolower(trim((string)$_GET['provider'])) : '';
    if ($provider === 'google' || $provider === 'microsoft') {
        return $provider;
    }
    return mbx_account_oauth_provider($account);
}

function mbx_oauth_return_url(array $account)
{
    if (mbx_is_admin()) {
        return mbx_plugin_url('accounts.php?edit=' . (int)$account['id']);
    }
    return mbx_plugin_url('my_account.php?edit=' . (int)$account['id']);
}

function mbx_oauth_fail($message, array $account = null)
{
    $url = $account ? mbx_oauth_return_url($account) : mbx_plugin_url('my_account.php');
    mbx_redirect($url . (strpos($url, '?') === false ? '?' : '&') . 'oauth_error=' . urlencode($message));
}

try {
    $db = mbx_db();
    MailboxSync::ensureTables($db);
    mbx_oauth_session_start();
    // 등록된 리디렉션 URI 에는 쿼리스트링(action=callback)을 넣을 수 없으므로,
    // 제공자가 돌려준 code/error 가 있으면 콜백으로 간주한다.
    $action = isset($_GET['action']) ? (string)$_GET['action'] : '';
    if ($action === '' && (isset($_GET['code']) || isset($_GET['error']))) {
        $action = 'callback';
    } elseif ($action === '') {
        $action = 'start';
    }

    if ($action === 'start') {
        $accountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
        $account = $accountId > 0 ? mbx_get_account($db, $accountId, false) : null;
        if (!$account) {
            throw new RuntimeException('계정을 찾을 수 없습니다.');
        }
        if (!mbx_account_manage_allowed($account)) {
            throw new RuntimeException('권한이 없습니다.');
        }
        $provider = mbx_oauth_account_provider($account);
        $state = mbx_oauth_random_state();
        if (!isset($_SESSION['mbx_oauth_states']) || !is_array($_SESSION['mbx_oauth_states'])) {
            $_SESSION['mbx_oauth_states'] = array();
        }
        $_SESSION['mbx_oauth_states'][$state] = array(
            'account_id' => (int)$account['id'],
            'provider' => $provider,
            'time' => time(),
        );
        mbx_redirect(mbx_oauth_authorize_url($provider, $state, (string)$account['email']));
    }

    if ($action === 'callback') {
        $state = isset($_GET['state']) ? (string)$_GET['state'] : '';
        if ($state === '' || empty($_SESSION['mbx_oauth_states'][$state])) {
            throw new RuntimeException('OAuth state 검증에 실패했습니다.');
        }
        $saved = $_SESSION['mbx_oauth_states'][$state];
        unset($_SESSION['mbx_oauth_states'][$state]);
        if (isset($saved['time']) && time() - (int)$saved['time'] > 900) {
            throw new RuntimeException('OAuth state가 만료되었습니다.');
        }
        $account = mbx_get_account($db, (int)$saved['account_id'], false);
        if (!$account) {
            throw new RuntimeException('계정을 찾을 수 없습니다.');
        }
        if (!mbx_account_manage_allowed($account)) {
            throw new RuntimeException('권한이 없습니다.');
        }
        if (!empty($_GET['error'])) {
            $desc = isset($_GET['error_description']) ? (string)$_GET['error_description'] : (string)$_GET['error'];
            throw new RuntimeException('OAuth 연결 실패: ' . $desc);
        }
        $code = isset($_GET['code']) ? (string)$_GET['code'] : '';
        if ($code === '') {
            throw new RuntimeException('OAuth code가 없습니다.');
        }
        $provider = (string)$saved['provider'];
        $token = mbx_oauth_exchange_code($provider, $code);
        if (empty($token['refresh_token']) && empty($account['oauth_refresh_token'])) {
            throw new RuntimeException('OAuth refresh token을 받지 못했습니다. 다시 연결해 주세요.');
        }
        $refresh = !empty($token['refresh_token']) ? (string)$token['refresh_token'] : mbx_secret_decrypt($account['oauth_refresh_token']);
        $access = (string)$token['access_token'];
        $expires = time() + (isset($token['expires_in']) ? (int)$token['expires_in'] : 3600);
        $stmt = mbx_stmt($db, "UPDATE mailbox_accounts SET auth_type='oauth2', oauth_provider=?, oauth_refresh_token=?, oauth_access_token=?, oauth_token_expires=? WHERE id=?", 'sssii', array($provider, mbx_secret_encrypt($refresh), mbx_secret_encrypt($access), $expires, (int)$account['id']));
        mysqli_stmt_close($stmt);
        mbx_redirect(mbx_oauth_return_url($account) . '&oauth_success=1');
    }

    throw new RuntimeException('지원하지 않는 OAuth action입니다.');
} catch (Exception $e) {
    try {
        if (isset($db) && isset($saved['account_id'])) {
            $account = mbx_get_account($db, (int)$saved['account_id'], false);
            if ($account) {
                mbx_oauth_fail($e->getMessage(), $account);
            }
        }
    } catch (Exception $ignored) {
    }
    mbx_oauth_fail($e->getMessage());
}
?>