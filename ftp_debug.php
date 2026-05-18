<?php
// =====================================================
// FTP 업로드 단계별 디버그 (사용 후 삭제하세요!)
// 접속: http://localhost:8000/ftp_debug.php
// =====================================================
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/include/remote_upload.php';

echo '<style>
body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
h2   { color: #569cd6; }
.ok  { color: #4ec9b0; }
.err { color: #f44747; }
.info{ color: #dcdcaa; }
.box { background: #252526; border: 1px solid #3c3c3c; padding: 12px; margin: 8px 0; border-radius: 4px; }
</style>';

echo '<h2>FTP 업로드 디버그</h2>';

// ─────────────────────────────────────────
// STEP 1 : 설정값 확인
// ─────────────────────────────────────────
echo '<div class="box"><b class="info">STEP 1 – 설정값</b><br>';
echo 'FTP_HOST    : ' . FTP_HOST . '<br>';
echo 'FTP_PORT    : ' . FTP_PORT . '<br>';
echo 'FTP_USER    : ' . FTP_USER . '<br>';
echo 'FTP_PASS    : ' . str_repeat('*', strlen(FTP_PASS)) . '<br>';
echo 'FTP_BASEDIR : ' . FTP_BASEDIR . '<br>';
echo '</div>';

// ─────────────────────────────────────────
// STEP 2 : cURL 설치 여부
// ─────────────────────────────────────────
echo '<div class="box"><b class="info">STEP 2 – cURL 설치 여부</b><br>';
if (function_exists('curl_init')) {
    $v = curl_version();
    echo '<span class="ok">✔ cURL 설치됨 (버전 ' . $v['version'] . ')</span><br>';
    $protocols = $v['protocols'];
    if (in_array('ftp', $protocols)) {
        echo '<span class="ok">✔ FTP 프로토콜 지원됨</span><br>';
    } else {
        echo '<span class="err">✘ FTP 프로토콜 미지원 – cURL 재빌드 필요</span><br>';
    }
} else {
    echo '<span class="err">✘ cURL 미설치</span><br>';
}
echo '</div>';

// ─────────────────────────────────────────
// STEP 3 : 로컬 디렉토리 확인 (product_img / upload)
// ─────────────────────────────────────────
foreach (['product_img', 'upload'] as $dirName) {
    echo '<div class="box"><b class="info">STEP 3 – 로컬 ' . $dirName . ' 디렉토리</b><br>';
    $dir = __DIR__ . '/' . $dirName;
    if (is_dir($dir)) {
        echo '<span class="ok">✔ 존재함: ' . $dir . '</span><br>';
        echo (is_writable($dir) ? '<span class="ok">✔ 쓰기 권한 있음</span>' : '<span class="err">✘ 쓰기 권한 없음</span>') . '<br>';
        $files = array_diff(scandir($dir), array('.', '..'));
        echo '파일 수: ' . count($files) . '<br>';
        foreach (array_slice($files, 0, 5) as $f) echo '&nbsp;&nbsp;' . $f . '<br>';
        if (count($files) > 5) echo '&nbsp;&nbsp;... 외 ' . (count($files) - 5) . '개<br>';
    } else {
        echo '<span class="err">✘ 디렉토리 없음: ' . $dir . '</span><br>';
    }
    echo '</div>';
}
$dir = __DIR__ . '/product_img'; // STEP 5에서 사용

// ─────────────────────────────────────────
// STEP 4 : FTP 연결 테스트
// ─────────────────────────────────────────
echo '<div class="box"><b class="info">STEP 4 – FTP 연결 테스트</b><br>';
$ftpErrMsg = '';
$ftpOk = remote_ftp_test($ftpErrMsg);
if ($ftpOk) {
    echo '<span class="ok">✔ FTP 연결 성공</span><br>';
} else {
    echo '<span class="err">✘ FTP 연결 실패: ' . htmlspecialchars($ftpErrMsg) . '</span><br>';
}
echo '</div>';

// ─────────────────────────────────────────
// STEP 5 : 테스트 파일 FTP 업로드 (product_img / upload 각각)
// ─────────────────────────────────────────
foreach (['product_img', 'upload'] as $folder) {
    echo '<div class="box"><b class="info">STEP 5 – 테스트 파일 FTP 업로드 → ' . $folder . '</b><br>';
    $testDir   = __DIR__ . '/' . $folder;
    $testLocal = $testDir . '/ftp_test_' . time() . '.txt';
    file_put_contents($testLocal, 'FTP test [' . $folder . '] ' . date('Y-m-d H:i:s'));

    if (!file_exists($testLocal)) {
        echo '<span class="err">✘ 테스트 파일 생성 실패 (쓰기 권한 확인)</span><br>';
    } else {
        echo '테스트 파일 생성: ' . basename($testLocal) . '<br>';

        $remoteUrl = 'ftp://' . FTP_HOST . ':' . FTP_PORT . FTP_BASEDIR . $folder . '/' . rawurlencode(basename($testLocal));
        echo '업로드 대상 URL: <span class="info">' . htmlspecialchars($remoteUrl) . '</span><br>';

        $syncErr = '';
        $syncOk  = remote_sync_file($testLocal, $folder, $syncErr);
        if ($syncOk) {
            echo '<span class="ok">✔ FTP 업로드 성공</span><br>';
        } else {
            echo '<span class="err">✘ FTP 업로드 실패: ' . htmlspecialchars($syncErr) . '</span><br>';
        }

        unlink($testLocal);
        echo '테스트 파일 로컬 삭제 완료<br>';
    }
    echo '</div>';
}

// ─────────────────────────────────────────
// STEP 6 : SERVER_ADDR 확인 (로컬 패스스루 방지 조건)
// ─────────────────────────────────────────
echo '<div class="box"><b class="info">STEP 6 – SERVER_ADDR vs FTP_HOST</b><br>';
$srvIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
echo 'SERVER_ADDR : <b>' . $srvIp . '</b><br>';
echo 'FTP_HOST    : <b>' . FTP_HOST . '</b><br>';
if ($srvIp === FTP_HOST) {
    echo '<span class="err">⚠ 같음 → FTP 스킵 (운영 서버에서 실행 중)</span><br>';
} else {
    echo '<span class="ok">✔ 다름 → FTP 업로드 시도함 (개발 환경)</span><br>';
}
echo '</div>';

echo '<br><small style="color:#555">사용 후 이 파일을 삭제하세요: ftp_debug.php</small>';
