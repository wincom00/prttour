<?php
if (!defined('MBX_OAUTH_CLIENT_LOADED')) {
    define('MBX_OAUTH_CLIENT_LOADED', true);

    function mbx_oauth_config($provider)
    {
        global $MBX_OAUTH;
        $provider = strtolower(trim((string)$provider));
        if (!isset($MBX_OAUTH[$provider]) || !is_array($MBX_OAUTH[$provider])) {
            throw new RuntimeException('지원하지 않는 OAuth 제공자입니다.');
        }
        $cfg = $MBX_OAUTH[$provider];
        if (trim((string)$cfg['client_id']) === '' || trim((string)$cfg['client_secret']) === '') {
            throw new RuntimeException('OAuth 클라이언트 ID/Secret 설정이 필요합니다.');
        }
        if (trim((string)$cfg['redirect_uri']) === '') {
            throw new RuntimeException('MBX_OAUTH_REDIRECT 설정이 필요합니다.');
        }
        return $cfg;
    }

    function mbx_oauth_authorize_url($provider, $state, $loginHint = '')
    {
        $provider = strtolower(trim((string)$provider));
        $cfg = mbx_oauth_config($provider);
        $params = array(
            'client_id' => $cfg['client_id'],
            'redirect_uri' => $cfg['redirect_uri'],
            'response_type' => 'code',
            'scope' => $cfg['scope'],
            'state' => $state,
        );
        if ($loginHint !== '') {
            $params['login_hint'] = $loginHint;
        }
        if ($provider === 'google') {
            $params['access_type'] = 'offline';
            $params['prompt'] = 'consent';
        } elseif ($provider === 'microsoft') {
            $params['response_mode'] = 'query';
        }
        return $cfg['auth_url'] . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    function mbx_oauth_exchange_code($provider, $code)
    {
        $cfg = mbx_oauth_config($provider);
        return mbx_oauth_token_request($cfg, array(
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'redirect_uri' => $cfg['redirect_uri'],
            'grant_type' => 'authorization_code',
            'code' => $code,
        ));
    }

    function mbx_oauth_refresh($provider, $refreshToken)
    {
        $cfg = mbx_oauth_config($provider);
        return mbx_oauth_token_request($cfg, array(
            'client_id' => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ));
    }

    function mbx_oauth_token_request(array $cfg, array $params)
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('OAuth 토큰 요청에는 curl 확장이 필요합니다.');
        }
        $ch = curl_init($cfg['token_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false) {
            throw new RuntimeException('OAuth 토큰 요청 실패: ' . $err);
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new RuntimeException('OAuth 토큰 응답을 해석할 수 없습니다.');
        }
        if ($code < 200 || $code >= 300 || empty($json['access_token'])) {
            $msg = isset($json['error_description']) ? $json['error_description'] : (isset($json['error']) ? $json['error'] : 'OAuth token error');
            throw new RuntimeException('OAuth 토큰 발급 실패: ' . $msg);
        }
        return $json;
    }
}
?>