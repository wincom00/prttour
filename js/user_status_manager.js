/**
 * user_status_manager.js
 * 사용자 상태 자동 감지 및 관리
 */

(function() {
    // 사용자 상태 클래스
    class UserStatusManager {
        constructor() {
            // 설정
            this.activityEvents = ['mousedown', 'keydown', 'mousemove', 'scroll', 'touchstart'];
            this.awayTimeout = 5 * 60 * 1000; // 5분 무반응 시 자리비움 상태로 변경
            this.heartbeatInterval = 60 * 1000; // 1분마다 활동 시간 업데이트
            
            // 상태값
            this.lastActivityTime = Date.now();
            this.currentStatus = 'online';
            this.manualStatus = null; // 사용자가 수동으로 설정한 상태
            
            // 타이머 ID
            this.activityCheckTimer = null;
            this.heartbeatTimer = null;
            
            // 초기화
            this.init();
        }
        
        // 초기화 함수
        init() {
            // 이벤트 리스너 등록
            this.registerActivityListeners();
            
            // 현재 상태 서버에서 가져오기
            this.fetchCurrentStatus();
            
            // 상태 체크 타이머 시작
            this.startActivityCheck();
            
            // 하트비트 타이머 시작
            this.startHeartbeat();
            
            // 페이지 언로드 이벤트 등록
            window.addEventListener('beforeunload', this.handleUnload.bind(this));
        }
        
        // 활동 이벤트 리스너 등록
        registerActivityListeners() {
            // 모든 활동 이벤트에 대해 핸들러 등록
            this.activityEvents.forEach(eventType => {
                window.addEventListener(eventType, this.handleUserActivity.bind(this), true);
            });
        }
        
        // 사용자 활동 처리
        handleUserActivity() {
            this.lastActivityTime = Date.now();
            
            // 자동 상태가 'away'였다면 다시 'online'으로 변경
            // 단, 사용자가 수동으로 상태를 설정한 경우는 변경하지 않음
            if (!this.manualStatus && this.currentStatus === 'away') {
                this.setStatus('online');
            }
        }
        
        // 페이지 언로드 처리
        handleUnload() {
            // 오프라인 상태로 변경 (즉시 처리를 위해 동기 요청 사용)
            if (!this.manualStatus) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'messenger/user_status.php', false); // 동기 요청
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.send(JSON.stringify({status: 'offline'}));
            }
        }
        
        // 상태 변경 함수
        setStatus(status, isManual = false) {
            if (isManual) {
                this.manualStatus = status;
            } else if (this.manualStatus && !isManual) {
                // 수동 설정된 상태가 있으면 자동 변경 무시
                return;
            }
            
            // 상태가 변경된 경우만 처리
            if (this.currentStatus !== status) {
                this.currentStatus = status;
                
                // 서버에 상태 업데이트
                fetch('messenger/user_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({status: status})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') {
                        console.error('상태 업데이트 실패:', data.message);
                    }
                })
                .catch(error => {
                    console.error('상태 업데이트 요청 실패:', error);
                });
                
                // 상태 변경 이벤트 발생
                const event = new CustomEvent('userStatusChanged', {
                    detail: { status: status }
                });
                window.dispatchEvent(event);
            }
        }
        
        // 수동 상태 설정 함수 (UI에서 호출)
        setManualStatus(status) {
            this.setStatus(status, true);
        }
        
        // 자동 상태 설정 초기화 (수동 설정 해제)
        resetManualStatus() {
            this.manualStatus = null;
            // 현재 활동 상태에 따라 상태 업데이트
            const idle = Date.now() - this.lastActivityTime > this.awayTimeout;
            this.setStatus(idle ? 'away' : 'online');
        }
        
        // 현재 서버에 저장된 상태 가져오기
        fetchCurrentStatus() {
            fetch('messenger/user_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.user_status) {
                        this.currentStatus = data.user_status.status;
                        // 상태 변경 이벤트 발생
                        const event = new CustomEvent('userStatusChanged', {
                            detail: { status: this.currentStatus }
                        });
                        window.dispatchEvent(event);
                    }
                })
                .catch(error => {
                    console.error('상태 정보 가져오기 실패:', error);
                });
        }
        
        // 활동 체크 타이머 시작
        startActivityCheck() {
            // 기존 타이머 정리
            if (this.activityCheckTimer) {
                clearInterval(this.activityCheckTimer);
            }
            
            // 30초마다 활동 상태 확인
            this.activityCheckTimer = setInterval(() => {
                // 수동 상태 설정이 없고 'online' 상태일 때만 자동 변경
                if (!this.manualStatus && this.currentStatus === 'online') {
                    const idle = Date.now() - this.lastActivityTime > this.awayTimeout;
                    if (idle) {
                        this.setStatus('away');
                    }
                }
            }, 30000); // 30초
        }
        
        // 하트비트 타이머 시작 (주기적으로 활동 시간 업데이트)
        startHeartbeat() {
            // 기존 타이머 정리
            if (this.heartbeatTimer) {
                clearInterval(this.heartbeatTimer);
            }
            
            // 1분마다 활동 시간 업데이트
            this.heartbeatTimer = setInterval(() => {
                // 오프라인 상태가 아니면 활동 시간 업데이트
                if (this.currentStatus !== 'offline') {
                    fetch('messenger/user_status.php', {
                        method: 'PUT'
                    })
                    .catch(error => {
                        console.error('활동 시간 업데이트 실패:', error);
                    });
                }
            }, this.heartbeatInterval);
        }
    }
    
    // DOM 로드 완료 후 상태 관리자 초기화
    document.addEventListener('DOMContentLoaded', function() {
        // 전역 userStatusManager 객체 생성
        window.userStatusManager = new UserStatusManager();
    });
})();