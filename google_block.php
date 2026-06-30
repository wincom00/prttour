<?php
// google_block.php - 모든 PHP 파일 상단에 포함

class GoogleSearchBlocker {
    private static $blockedReferers = [
        'google.com/search',
        'google.co.kr/search',
        'www.google.com/search',
        'www.google.co.kr/search',
        'images.google.com',
        'news.google.com'
    ];
    
    private static $blockedUserAgents = [
        'googlebot',
        'google-structured-data-testing-tool',
        'adsbot-google'
    ];
    
    public static function checkAndBlock() {
        // Referer 체크
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = strtolower($_SERVER['HTTP_REFERER']);
            
            foreach (self::$blockedReferers as $blocked) {
                if (strpos($referer, $blocked) !== false) {
                    self::blockAccess('Google search access denied');
                }
            }
        }
        
        // User-Agent 체크
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
            
            foreach (self::$blockedUserAgents as $blocked) {
                if (strpos($userAgent, $blocked) !== false) {
                    self::blockAccess('Bot access denied');
                }
            }
        }
    }
    
    private static function blockAccess($message) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo $message;
        exit();
    }
}

?>