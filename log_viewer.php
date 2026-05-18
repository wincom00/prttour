<?php
// =====================================================
// file_save 디버그 로그 뷰어
// 접속: http://myprt.org/log_viewer.php
// =====================================================
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/include/inc_base.php';
if (empty($user_dbinfo['userid'])) { header('HTTP/1.0 403 Forbidden'); exit('로그인 필요'); }

$logFile = (is_writable(__DIR__) ? __DIR__ : sys_get_temp_dir()) . '/file_save_debug.log';

// 로그 삭제 요청
if ($_POST['action'] ?? '' === 'clear') {
    file_put_contents($logFile, '');
    header('Location: log_viewer.php');
    exit;
}

$lines = [];
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // 최신 항목 위로
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>file_save 로그</title>
<style>
body  { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
h2    { color: #569cd6; }
.ok   { color: #4ec9b0; }
.err  { color: #f44747; }
.warn { color: #ce9178; }
.info { color: #dcdcaa; }
.ts   { color: #6a9955; margin-right: 6px; }
.box  { background: #252526; border: 1px solid #3c3c3c; padding: 10px 14px; margin: 4px 0; border-radius: 4px; }
.empty{ color: #555; font-style: italic; }
form  { margin-bottom: 16px; }
button{ background:#c72020; color:#fff; border:none; padding:6px 14px; border-radius:4px; cursor:pointer; }
</style>
</head>
<body>
<h2>file_save 디버그 로그</h2>
<p>파일: <span class="info"><?= htmlspecialchars($logFile) ?></span>
   &nbsp;|&nbsp; 총 <b><?= count($lines) ?></b>줄</p>

<form method="post">
  <input type="hidden" name="action" value="clear">
  <button onclick="return confirm('로그를 비우시겠습니까?')">로그 비우기</button>
</form>

<?php if (empty($lines)): ?>
  <div class="box empty">로그가 없습니다.</div>
<?php else: ?>
  <?php foreach ($lines as $line):
    $ts  = '';
    $msg = $line;
    if (preg_match('/^(\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])\s+(.*)$/', $line, $m)) {
        $ts  = $m[1];
        $msg = $m[2];
    }
    if     (str_contains($msg, 'FAIL') || str_contains($msg, '실패')) $cls = 'err';
    elseif (str_contains($msg, 'OK')   || str_contains($msg, '성공')) $cls = 'ok';
    elseif (str_contains($msg, 'null') || str_contains($msg, 'FTP 스킵')) $cls = 'warn';
    else   $cls = 'info';
  ?>
  <div class="box">
    <span class="ts"><?= htmlspecialchars($ts) ?></span>
    <span class="<?= $cls ?>"><?= htmlspecialchars($msg) ?></span>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
