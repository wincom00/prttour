<?php
// =====================================================
// 원격 서버 FTP 동기화 설정 (cURL FTP 방식)
// =====================================================
if (!defined('FTP_HOST'))    define('FTP_HOST',    '98.90.182.87');
if (!defined('FTP_USER'))    define('FTP_USER',    'wincom00');
if (!defined('FTP_PASS'))    define('FTP_PASS',    'Lee10011!');
if (!defined('FTP_PORT'))    define('FTP_PORT',    21);
if (!defined('FTP_BASEDIR')) define('FTP_BASEDIR', '/html/'); // 원격 웹루트 절대경로 (끝에 / 포함)

/**
 * cURL로 FTP 연결 가능 여부만 테스트 (업로드 없음)
 *
 * @param string &$errMsg  실패 시 오류 메시지 반환
 * @return bool
 */
function remote_ftp_test(&$errMsg = '') {
    if (!function_exists('curl_init')) {
        $errMsg = 'cURL이 서버에 설치되어 있지 않습니다.';
        return false;
    }
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => 'ftp://' . FTP_HOST . ':' . FTP_PORT . '/',
        CURLOPT_USERPWD        => FTP_USER . ':' . FTP_PASS,
        CURLOPT_FTPLISTONLY    => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_FTP_USE_EPSV   => false,
    ));
    curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        $errMsg = 'FTP 연결 실패: ' . $error;
        return false;
    }
    return true;
}


if (!function_exists('remote_sync_file')):
/**
 * cURL로 로컬 파일을 FTP 원격 서버의 동일 폴더에 업로드
 *
 * @param string $localFilePath  로컬 파일 경로 (예: "product_img/abc.jpg")
 * @param string $folder         대상 폴더명 (upload | uploads | product_img)
 * @return bool  성공 여부
 */
function remote_sync_file($localFilePath, $folder, &$errMsg = '') {
    // 현재 서버가 FTP 호스트와 같으면 로컬 저장 = 원격 저장이므로 FTP 불필요
    $_srvIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
    if ($_srvIp === FTP_HOST) { return true; }

    if (!function_exists('curl_init')) { $errMsg = 'cURL 없음'; return false; }
    $absLocal = realpath($localFilePath) ?: $localFilePath;
    if (!file_exists($absLocal)) { $errMsg = '로컬파일 없음: '.$absLocal; return false; }

    $fp = fopen($absLocal, 'rb');
    if (!$fp) { $errMsg = '파일열기 실패'; return false; }

    $remoteFile = 'ftp://' . FTP_HOST . ':' . FTP_PORT
                . FTP_BASEDIR . $folder . '/' . basename($localFilePath);

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL            => $remoteFile,
        CURLOPT_USERPWD        => FTP_USER . ':' . FTP_PASS,
        CURLOPT_UPLOAD         => true,
        CURLOPT_INFILE         => $fp,
        CURLOPT_INFILESIZE     => filesize($absLocal),
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_FTP_USE_EPSV   => false,
        CURLOPT_FTP_CREATE_MISSING_DIRS => true,
    ));
    $result = curl_exec($ch);
    $errno  = curl_errno($ch);
    $errMsg = $errno ? ('cURL FTP 오류 #'.$errno.': '.curl_error($ch).' | URL: '.$remoteFile) : '';
    curl_close($ch);
    fclose($fp);

    return ($errno === 0 && $result !== false);
}
endif;

if (!function_exists('remote_detect_folder')):
/**
 * 경로 문자열에서 대상 폴더명 감지
 *
 * @param string $path
 * @return string|null
 */
function remote_detect_folder($path) {
    foreach (array('product_img', 'upload') as $folder) {
        if (strpos($path, $folder) !== false) {
            return $folder;
        }
    }
    return null;
}
endif;
