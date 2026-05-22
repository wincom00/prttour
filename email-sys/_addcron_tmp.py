# -*- coding: utf-8 -*-
p = 'newsletter_background_worker.php'
b = open(p, 'rb').read()
if b.startswith(b'\xef\xbb\xbf'):
    b = b[3:]
t = b.decode('utf-8').replace('\r\n', '\n').replace('\r', '\n')

repls = []

# 1) 함수 정의 추가 (워커 시작 로그 바로 앞)
repls.append((
'''writeLog("뉴스레터 백그라운드 워커 시작");''',
'''function newsletterEnsureWorkerCron() {
    // 로컬에 매분 실행 크론(작업 스케줄러)이 없으면 자동 등록한다. (Windows 전용)
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        return; // 리눅스 등은 crontab 으로 별도 등록 필요
    }
    if (!function_exists('exec')) {
        return;
    }
    $task = 'Newsletter_Worker';
    $out = array();
    $code = 1;
    @exec('schtasks /query /tn "' . $task . '" 2>nul', $out, $code);
    if ($code === 0) {
        return; // 이미 등록됨
    }
    $vbs = __DIR__ . DIRECTORY_SEPARATOR . 'run_newsletter_worker.vbs';
    if (!file_exists($vbs)) {
        return; // 숨김 런처가 없으면 등록 생략
    }
    $o2 = array();
    $c2 = 1;
    @exec('schtasks /create /tn "' . $task . '" /tr "wscript.exe ' . $vbs . '" /sc minute /mo 1 /f 2>nul', $o2, $c2);
    writeLog('작업 스케줄러 자동 등록(매분): ' . ($c2 === 0 ? '성공' : '실패 code=' . $c2));
}

writeLog("뉴스레터 백그라운드 워커 시작");'''
))

# 2) 락 획득 직후 호출
repls.append((
'''ftruncate($lock_fp, 0);
fwrite($lock_fp, getmypid() . ' ' . date('Y-m-d H:i:s') . "\\n");

$selected_queue_ids = newsletterWorkerSelectedQueueIds();''',
'''ftruncate($lock_fp, 0);
fwrite($lock_fp, getmypid() . ' ' . date('Y-m-d H:i:s') . "\\n");

// 로컬에 매분 실행 크론(작업 스케줄러)이 없으면 자동 등록
newsletterEnsureWorkerCron();

$selected_queue_ids = newsletterWorkerSelectedQueueIds();'''
))

for i, (old, new) in enumerate(repls, 1):
    c = t.count(old)
    if c != 1:
        raise SystemExit('R%d match=%d (expected 1)' % (i, c))
    t = t.replace(old, new)

out = t.replace('\n', '\r\n').encode('utf-8')
open(p, 'wb').write(out)
print('OK self-register cron added, bytes=', len(out))
