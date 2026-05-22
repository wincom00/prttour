#!/bin/bash

# =====================================================================
# 뉴스레터 백그라운드 워커 관리 스크립트 (크론 및 로컬 제어용)
# =====================================================================

# 스크립트가 위치한 절대 경로
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# PHP 실행 파일 경로 (환경에 따라 변경 가능)
PHP_BIN="php"

# 워커 스크립트 경로
WORKER_SCRIPT="$SCRIPT_DIR/newsletter_background_worker.php"

# 락 파일 경로 (워커 스크립트가 생성하는 락 파일과 동일하게)
LOCK_FILE="$SCRIPT_DIR/newsletter_logs/newsletter_worker.lock"

# 중지 상태를 기록할 플래그 파일
STOP_FLAG_FILE="$SCRIPT_DIR/newsletter_logs/newsletter_cron.stop"

function print_usage() {
    echo "사용법: $0 {start|stop|resume|status|cron|install-cron}"
    echo ""
    echo "  start        : 뉴스레터 워커를 즉시 강제 실행합니다."
    echo "  stop         : 뉴스레터 워커 실행을 중지하고, 크론 실행을 차단합니다."
    echo "  resume       : 크론 실행 차단을 해제합니다."
    echo "  status       : 현재 실행 상태 및 프로세스 상태를 확인합니다."
    echo "  cron         : 크론탭에서 매 분 호출하는 명령입니다. (직접 실행 불필요)"
    echo "  install-cron : 현재 사용자의 크론탭(crontab)에 1분 주기로 자동 등록합니다."
    echo ""
}

# 로그 디렉토리가 없으면 생성
if [ ! -d "$SCRIPT_DIR/newsletter_logs" ]; then
    mkdir -p "$SCRIPT_DIR/newsletter_logs"
fi

case "$1" in
    start)
        rm -f "$STOP_FLAG_FILE"
        echo "[시작] 뉴스레터 워커를 백그라운드에서 실행합니다..."
        nohup $PHP_BIN "$WORKER_SCRIPT" > /dev/null 2>&1 &
        echo "실행 명령이 전달되었습니다."
        ;;
        
    stop)
        touch "$STOP_FLAG_FILE"
        echo "[중지] 뉴스레터 크론 실행이 차단되었습니다."
        
        # 현재 실행중인 워커가 있다면 프로세스 종료
        PID=$(cat "$LOCK_FILE" 2>/dev/null | awk '{print $1}')
        if [ ! -z "$PID" ] && kill -0 "$PID" 2>/dev/null; then
            kill "$PID"
            echo "실행 중이던 워커 프로세스(PID: $PID)를 강제 종료했습니다."
            rm -f "$LOCK_FILE"
        else
            echo "현재 실행 중인 워커 프로세스가 없습니다."
        fi
        ;;
        
    resume)
        rm -f "$STOP_FLAG_FILE"
        echo "[재개] 뉴스레터 크론 실행 차단이 해제되었습니다."
        echo "다음 크론 주기(최대 1분)부터 워커가 다시 자동 실행됩니다."
        ;;
        
    status)
        if [ -f "$STOP_FLAG_FILE" ]; then
            echo "[상태] 크론 스케줄: 정지됨 (STOPPED)"
        else
            echo "[상태] 크론 스케줄: 활성 (ACTIVE)"
        fi
        
        PID=$(cat "$LOCK_FILE" 2>/dev/null | awk '{print $1}')
        if [ ! -z "$PID" ] && kill -0 "$PID" 2>/dev/null; then
            echo "[상태] 워커 프로세스: 실행 중 (PID: $PID)"
        else
            echo "[상태] 워커 프로세스: 대기 중 (처리할 큐가 없으면 자동 종료되므로 정상 상태입니다)"
        fi
        ;;
        
    cron)
        # 크론에서 호출 시, 플래그 파일이 있으면 무시
        if [ -f "$STOP_FLAG_FILE" ]; then
            exit 0
        fi
        
        # 워커 스크립트 백그라운드 실행
        # (PHP 스크립트 내부에 파일 락이 있어, 이미 실행중이면 알아서 종료됨)
        $PHP_BIN "$WORKER_SCRIPT" > /dev/null 2>&1 &
        ;;
        
    install-cron)
        CRON_CMD="* * * * * $SCRIPT_DIR/$(basename "$0") cron"
        # 중복 등록 방지
        if crontab -l 2>/dev/null | grep -q "$WORKER_SCRIPT"; then
            echo "이미 크론탭에 구버전 등록이 존재하는 것 같습니다. 확인이 필요합니다."
        fi
        
        if crontab -l 2>/dev/null | grep -q "$(basename "$0") cron"; then
            echo "이미 크론탭에 등록되어 있습니다."
        else
            (crontab -l 2>/dev/null; echo "$CRON_CMD") | crontab -
            echo "크론탭에 성공적으로 등록되었습니다."
            echo "등록된 내용: $CRON_CMD"
        fi
        ;;
        
    *)
        print_usage
        exit 1
        ;;
esac

exit 0
