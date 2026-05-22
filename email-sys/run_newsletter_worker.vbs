' 뉴스레터 워커를 창 없이(숨김) 실행하는 런처 — 작업 스케줄러에서 호출
' 0 = 숨김 창, False = 종료를 기다리지 않음
CreateObject("WScript.Shell").Run """D:\www\prttour_myprt\email-sys\run_newsletter_worker.cmd""", 0, False
