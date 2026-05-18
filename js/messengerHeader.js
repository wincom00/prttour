   // 메신저 알림 관련 스크립트
    $(document).ready(function() {
        // 알림 사운드 엘리먼트 참조
        var notificationSound = document.getElementById('notification-sound');
        
        // 사운드 미리 로드 (모바일 브라우저에서는 사용자 상호작용 필요할 수 있음)
        try {
            notificationSound.load();
        } catch (e) {
            console.log('사운드 로드 중 오류 발생:', e);
        }
        
        // 브라우저 알림 권한 요청
        if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            setTimeout(function() {
                Notification.requestPermission();
            }, 3000);
        }
        
        // 읽지 않은 메시지 확인 및 배지 업데이트
        function checkUnreadMessages() {
            // IE 호환성을 위해 캐시 방지 파라미터 추가
            var cacheBuster = '?_=' + new Date().getTime();
            
            $.ajax({
                url: 'messenger/unread_count.php' + cacheBuster,
                dataType: 'json',
                cache: false,
                success: function(data) {
                    if (data.status === 'success') {
                        updateNotificationBadge(data.unread_count);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('메시지 확인 중 오류 발생:', error);
                }
            });
        }
        
        // 이전 읽지 않은 메시지 수를 기록하기 위한 변수
        var previousUnreadCount = 0;
        
        // 알림 배지 업데이트 함수
        function updateNotificationBadge(count) {
            var badge = $('#messenger-notification-badge');
            if (badge.length > 0) {
                if (count > 0) {
                    badge.text(count > 99 ? '99+' : count);
                    badge.show();
                    
                    // 새 메시지가 있고 이전보다 메시지 수가 증가했으며
                    // 현재 페이지가 메신저가 아닌 경우에만 알림
                    if (count > previousUnreadCount && !window.location.href.includes('messenger.php')) {
                        playNotificationSound();
                        showNotification(count);
                    }
                } else {
                    badge.hide();
                }
                
                // 현재 읽지 않은 메시지 수 업데이트
                previousUnreadCount = count;
            }
        }
        
        // 알림 사운드 재생 함수
        function playNotificationSound() {
            try {
                // 사운드 재생 시 오류 처리 (일부 브라우저에서는 사용자 상호작용 필요)
                notificationSound.currentTime = 0; // 처음부터 재생
                
                // 볼륨 설정
                notificationSound.volume = 0.5; // 50% 볼륨
                
                // 재생
                var playPromise = notificationSound.play();
                
                // 일부 브라우저에서는 play()가 Promise를 반환
                if (playPromise !== undefined) {
                    playPromise.catch(function(error) {
                        console.log('사운드 재생 중 오류 발생:', error);
                    });
                }
            } catch (e) {
                console.log('사운드 재생 중 오류 발생:', e);
            }
        }
        
        // 브라우저 알림 표시 함수
        function showNotification(count) {
            // 브라우저 알림 권한이 허용되었는지 확인
            if ('Notification' in window && Notification.permission === 'granted') {
                var notificationOptions = {
                    body: '읽지 않은 메시지가 ' + count + '개 있습니다.',
                    icon: 'img/favi/favicon-32x32.png',
                    silent: true // 브라우저 기본 사운드 사용 안 함 (직접 사운드 재생)
                };
                
                const notification = new Notification('푸른투어 인트라넷 메신저', notificationOptions);
                
                // 알림 클릭 시 메시지 팝업 또는 메신저 페이지로 이동
                notification.onclick = function() {
                    window.focus();
                    
                    // 팝업 창으로 마지막 메시지 표시 (작은 창으로 열림)
                    var popupWidth = 350;
                    var popupHeight = 300;
                    var left = (screen.width/2)-(popupWidth/2);
                    var top = (screen.height/2)-(popupHeight/2);
                    
                    window.open('messenger/message_popup.php?mode=popup', 
                               '새 메시지', 
                               'width='+popupWidth+',height='+popupHeight+',left='+left+',top='+top+',resizable=no,scrollbars=no,status=no,location=no,menubar=no');
                    
                    this.close();
                };
                
                // 5초 후 자동으로 알림 닫기
                setTimeout(function() {
                    notification.close();
                }, 5000);
            }
        }
        
        // 최초 로드 및 주기적 확인
        checkUnreadMessages();
        setInterval(checkUnreadMessages, 30000); // 30초마다 확인
    });	