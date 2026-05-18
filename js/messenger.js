/**
 * 푸른투어 인트라넷 메신저 위젯 JavaScript
 */

(function() {
    // DOM이 로드된 후 실행
    document.addEventListener('DOMContentLoaded', function() {
        // 필요한 DOM 요소 선택
        const messengerWidget = document.getElementById('messenger-widget');
        const messengerHeader = document.querySelector('.messenger-header');
        const messengerToggle = document.getElementById('messenger-toggle');
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanels = document.querySelectorAll('.tab-panel');
        const messengerSearchInput = document.getElementById('messenger-search-input');
        const messengerSearchBtn = document.getElementById('messenger-search-btn');
        const newMessageBtn = document.querySelector('.new-message-btn');
        const newMessageModal = document.getElementById('new-message-modal');
        const modalCloseBtn = document.querySelector('.modal-close-btn');
        const modalCancelBtn = document.querySelector('.modal-cancel-btn');
        const recipientSearchInput = document.getElementById('recipient-search-input');
        const recipientSearchResults = document.getElementById('recipient-search-results');
        const selectedRecipientsContainer = document.querySelector('.selected-recipients');
        const newMessageContent = document.getElementById('new-message-content');
        const sendNewMessageBtn = document.getElementById('send-new-message-btn');
        const desktopNotificationsToggle = document.getElementById('desktop-notifications');
        const soundNotificationsToggle = document.getElementById('sound-notifications');
        const userStatusSelect = document.getElementById('user-status');
        
        // 글로벌 상태 변수
        let selectedRecipients = [];
        let activeConversationWindows = [];
        let lastFetchTime = 0;
        let pollingInterval;
        let notificationSound = new Audio('sounds/notification.mp3');
        
        // 위젯 초기화
        initWidget();
        
        // 폴링 시작 (실시간 메시지 및 알림 확인)
        startPolling();
        
		
		
        /**
         * 위젯 초기화 함수
         */
        function initWidget() {
            // 위젯 토글 기능
            messengerHeader.addEventListener('click', function(e) {
                if (e.target.closest('.messenger-toggle')) return;
                toggleWidget();
            });
            
            messengerToggle.addEventListener('click', toggleWidget);
            
            // 탭 전환 기능
            tabButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const tabName = button.getAttribute('data-tab');
                    switchTab(tabName);
                });
            });
            
            // 검색 기능
            messengerSearchBtn.addEventListener('click', function() {
                performSearch(messengerSearchInput.value);
            });
            
            messengerSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch(messengerSearchInput.value);
                }
            });
            
            // 대화 목록 클릭 이벤트
            document.addEventListener('click', function(e) {
                const conversationItem = e.target.closest('.conversation-item');
                if (conversationItem) {
                    const conversationId = conversationItem.getAttribute('data-conversation-id');
                    const type = conversationItem.getAttribute('data-type');
                    const displayName = conversationItem.querySelector('.conversation-name').textContent;
                    
                    openConversationWindow(conversationId, displayName, type);
                    
                    // 읽음 표시
                    markConversationAsRead(conversationId);
                }
            });
            
            // 연락처 목록의 채팅 시작 버튼 클릭 이벤트
            document.addEventListener('click', function(e) {
                const startChatBtn = e.target.closest('.start-chat-btn');
                if (startChatBtn) {
                    const userId = startChatBtn.getAttribute('data-user-id');
                    const userName = startChatBtn.getAttribute('data-user-name');
                    
                    // 임시 모달 방식으로 1:1 대화 시작
                    openNewMessageModal([{
                        id: userId,
                        name: userName
                    }]);
                }
            });
            
            // 새 메시지 버튼 클릭 이벤트
            newMessageBtn.addEventListener('click', function() {
                openNewMessageModal();
            });
            
            // 모달 닫기 버튼 클릭 이벤트
            modalCloseBtn.addEventListener('click', closeNewMessageModal);
            modalCancelBtn.addEventListener('click', closeNewMessageModal);
            
            // 모달 외부 클릭 시 닫기
            newMessageModal.addEventListener('click', function(e) {
                if (e.target === newMessageModal) {
                    closeNewMessageModal();
                }
            });
            
            // 수신자 검색 기능
            recipientSearchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                if (searchTerm.length >= 2) {
                    searchUsers(searchTerm);
                } else {
                    recipientSearchResults.innerHTML = '';
                    recipientSearchResults.classList.remove('show');
                }
            });
            
            // 수신자 검색 결과 클릭 이벤트
            recipientSearchResults.addEventListener('click', function(e) {
                const searchResultItem = e.target.closest('.search-result-item');
                if (searchResultItem) {
                    const userId = searchResultItem.getAttribute('data-user-id');
                    const userName = searchResultItem.querySelector('.search-result-name').textContent;
                    
                    addRecipient({
                        id: userId,
                        name: userName
                    });
                    
                    recipientSearchInput.value = '';
                    recipientSearchResults.innerHTML = '';
                    recipientSearchResults.classList.remove('show');
                }
            });
            
            // 새 메시지 전송 버튼 이벤트
            sendNewMessageBtn.addEventListener('click', function() {
                if (selectedRecipients.length > 0 && newMessageContent.value.trim() !== '') {
                    sendNewMessage();
                }
            });
            
            // 새 메시지 내용 입력 이벤트
            newMessageContent.addEventListener('input', function() {
                sendNewMessageBtn.disabled = !(selectedRecipients.length > 0 && this.value.trim() !== '');
            });
            
            // 알림 설정 이벤트
            desktopNotificationsToggle.addEventListener('change', function() {
                saveNotificationSettings('desktop', this.checked);
                
                // 데스크톱 알림 권한 요청
                if (this.checked && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                    Notification.requestPermission();
                }
            });
            /**
         * 새 메시지 모달 열기 함수
         * @param {Array} [initialRecipients] - (선택) 초기 수신자 목록 [{id: '...', name: '...'}]
         */
        function openNewMessageModal(initialRecipients = []) { //
            selectedRecipients = []; // 선택된 수신자 목록 초기화
            if (selectedRecipientsContainer) { //
                selectedRecipientsContainer.innerHTML = ''; // 기존 선택된 수신자 태그 지우기
            }
            if (newMessageContent) { //
                newMessageContent.value = ''; // 메시지 내용 지우기
            }
            if (sendNewMessageBtn) { //
                sendNewMessageBtn.disabled = true; // 보내기 버튼 비활성화
            }
            if (recipientSearchInput) { //
                recipientSearchInput.value = ''; // 수신자 검색창 지우기
            }
            if (recipientSearchResults) { //
                recipientSearchResults.innerHTML = ''; // 검색 결과 지우기
                recipientSearchResults.classList.remove('show'); // 검색 결과 숨기기
            }

            if (initialRecipients && initialRecipients.length > 0) { //
                initialRecipients.forEach(rec => addRecipient(rec, false)); // 초기 수신자 추가 (검색 결과 업데이트 안함)
                if (newMessageContent) newMessageContent.focus(); // 수신자가 미리 채워져 있으면 메시지 입력창에 포커스
            } else {
                if (recipientSearchInput) recipientSearchInput.focus(); // 초기 수신자 없으면 검색창에 포커스
            }
            
            if (newMessageModal) { //
                newMessageModal.style.display = 'flex'; // 모달창 보이기 (CSS에 따라 'block'일 수도 있음)
            }
        }

        /**
         * 새 메시지 모달 닫기 함수
         */
        function closeNewMessageModal() { //
            if (newMessageModal) { //
                newMessageModal.style.display = 'none'; //
            }
        }

        /**
         * 수신자 추가 함수 (내부적으로 renderSelectedRecipients 호출)
         * @param {object} recipient - {id: '...', name: '...'}
         * @param {boolean} triggerSearchUpdate - (선택) 검색 결과 업데이트 트리거 여부 (기본값 true)
         */
        function addRecipient(recipient, triggerSearchUpdate = true) { //
            if (!selectedRecipients.some(r => r.id === recipient.id)) { //
                selectedRecipients.push(recipient); //
                renderSelectedRecipients(); //
                
                if (sendNewMessageBtn && newMessageContent) { //
                    sendNewMessageBtn.disabled = !(selectedRecipients.length > 0 && newMessageContent.value.trim() !== ''); //
                }

                if (triggerSearchUpdate && recipientSearchInput) { //
                    const currentSearchTerm = recipientSearchInput.value.trim(); //
                    if (currentSearchTerm.length >= 2) { //
                        searchUsers(currentSearchTerm); // 선택된 사용자를 결과에서 제외하기 위해 검색 다시 실행
                    } else if (recipientSearchResults) { //
                        recipientSearchResults.innerHTML = ''; //
                        recipientSearchResults.classList.remove('show'); //
                    }
                }
            }
        }

        /**
         * 선택된 수신자 UI 렌더링 함수
         */
        function renderSelectedRecipients() { //
            if (!selectedRecipientsContainer) return; //
            selectedRecipientsContainer.innerHTML = ''; //
            selectedRecipients.forEach(recipient => { //
                const tag = document.createElement('div'); //
                tag.className = 'recipient-tag'; //
                tag.textContent = recipient.name; //
                
                const removeBtn = document.createElement('button'); //
                removeBtn.className = 'remove-recipient'; //
                removeBtn.innerHTML = '&times;'; //
                removeBtn.onclick = function() { //
                    removeRecipient(recipient.id); //
                };
                
                tag.appendChild(removeBtn); //
                selectedRecipientsContainer.appendChild(tag); //
            });
        }

        /**
         * 수신자 제거 함수
         * @param {string} recipientId - 제거할 수신자 ID
         */
        function removeRecipient(recipientId) { //
            selectedRecipients = selectedRecipients.filter(r => r.id !== recipientId); //
            renderSelectedRecipients(); //
            if (sendNewMessageBtn && newMessageContent) { //
                sendNewMessageBtn.disabled = !(selectedRecipients.length > 0 && newMessageContent.value.trim() !== ''); //
            }
            
            if (recipientSearchInput) { //
                 const currentSearchTerm = recipientSearchInput.value.trim(); //
                 if (currentSearchTerm.length >= 2) { //
                      searchUsers(currentSearchTerm); // 제거된 수신자가 검색 결과에 다시 나타날 수 있도록 검색 새로고침
                 }
            }
        }

            soundNotificationsToggle.addEventListener('change', function() {
                saveNotificationSettings('sound', this.checked);
            });
            
            // 상태 변경 이벤트
            userStatusSelect.addEventListener('change', function() {
                updateUserStatus(this.value);
            });
            
            // 대화창 컨테이너 생성
            const conversationWindowsContainer = document.createElement('div');
            conversationWindowsContainer.className = 'conversation-windows-container';
            document.body.appendChild(conversationWindowsContainer);
            
            // 초기 데이터 로드
            loadConversations();
            
            // 알림 설정 로드
            loadNotificationSettings();
            
            // 상태 로드
            loadUserStatus();
        }
        
        /**
         * 위젯 토글 함수
         */
        function toggleWidget() {
            const isOpen = messengerWidget.classList.contains('open');
            
            if (isOpen) {
                messengerWidget.classList.remove('open');
                messengerWidget.classList.add('closed');
                messengerToggle.innerHTML = '<i class="fas fa-chevron-up"></i>';
                setCookie('messenger_widget_state', 'closed', 30);
            } else {
                messengerWidget.classList.remove('closed');
                messengerWidget.classList.add('open');
                messengerToggle.innerHTML = '<i class="fas fa-chevron-down"></i>';
                setCookie('messenger_widget_state', 'open', 30);
            }
        }
        
        /**
         * 탭 전환 함수
         * @param {string} tabName - 활성화할 탭 이름
         */
        function switchTab(tabName) {
            // 모든 탭 버튼과 패널 비활성화
            tabButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            tabPanels.forEach(function(panel) {
                panel.classList.remove('active');
            });
            
            // 선택한 탭 활성화
            document.querySelector(`.tab-btn[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById(`${tabName}-panel`).classList.add('active');
        }
         /**
         * 메시지 및 알림 폴링 시작 함수
         */
        function startPolling() {
            // 폴링 중지 (기존 인터벌이 있다면 클리어)
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }

            // 즉시 한 번 실행
            fetchUpdates();

            // 주기적 폴링 설정 (예: 30초마다)
            pollingInterval = setInterval(fetchUpdates, 30000); // 30000 milliseconds = 30 seconds
        }
		/***
		  * 업데이트 가져오기 함수 (메시지, 알림 등)
         */
        function fetchUpdates() { // 여기에 fetchUpdates 함수가 정의되어 있습니다.
            const now = new Date().getTime();
            // 너무 자주 요청하지 않도록 최소 간격 설정 (예: 5초)
            if (now - lastFetchTime < 5000 && lastFetchTime !== 0) { //
                return; //
            }

            // CSRF 토큰 등 필요한 파라미터 추가 (실제 환경에 맞게 수정)
            // const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // 서버에 업데이트 요청
            // 실제 요청 URL 및 파라미터는 시스템에 맞게 조정해야 합니다.
            fetch('messenger-ajax.php?action=get_updates&since=' + lastFetchTime) //
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        // 새 메시지 처리
                        if (data.messages && data.messages.length > 0) {
                            // 현재 열려있는 대화창에 메시지 추가 또는 새 메시지 알림
                            processNewMessages(data.messages); //
                            showNewMessageNotifications(data.messages); //
                        }

                        // 읽지 않은 메시지 수 업데이트
                        if (typeof data.unread_count !== 'undefined') {
                            updateUnreadBadges(data.unread_count); //
                        }

                        // 기타 알림 처리 (예: 사용자 상태 변경 알림 등)
                        if (data.notifications && data.notifications.length > 0) {
                            processSystemNotifications(data.notifications); //
                        }

                        // 대화 목록 새로고침 (필요한 경우)
                        // 예: 새 대화가 시작되었거나, 기존 대화의 순서/내용이 중요하게 변경된 경우
                        if (data.refresh_conversations) {
                            loadConversations(); //
                        }

                    } else if (data.status === 'no_new_data') {
                        // 새 데이터 없음
                    } else {
                        console.error('Error fetching updates:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch updates error:', error);
                })
                .finally(() => {
                    lastFetchTime = now; //
                });
        }

        /**
         * 대화 목록 로드 함수
         */
        function loadConversations() {
            fetch('messenger-ajax.php?action=get_conversations')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateConversationsList(data.conversations);
                    }
                })
                .catch(error => {
                    console.error('대화 목록 로드 중 오류:', error);
                });
        }
        
        /**
         * 대화 목록 업데이트 함수
         * @param {Array} conversations - 대화 목록 데이터
         */
        function updateConversationsList(conversations) {
            const conversationsList = document.querySelector('.conversation-list');
            
            if (!conversationsList) return;
            
            // 총 읽지 않은 메시지 수 계산
            let totalUnread = 0;
            conversations.forEach(conv => {
                totalUnread += parseInt(conv.unread_count);
            });
            
            // 헤더와 탭 배지 업데이트
            updateUnreadBadges(totalUnread);
            
            if (conversations.length === 0) {
                conversationsList.innerHTML = `
                    <div class="no-conversations">
                        <p>아직 대화가 없습니다.</p>
                        <button class="start-new-chat-btn">새 대화 시작하기</button>
                    </div>
                `;
                
                // 새 대화 시작 버튼 이벤트 바인딩
                const startNewChatBtn = conversationsList.querySelector('.start-new-chat-btn');
                if (startNewChatBtn) {
                    startNewChatBtn.addEventListener('click', function() {
                        openNewMessageModal();
                    });
                }
                
                return;
            }
            
            let html = '';

			conversations.forEach(conv => {
				const lastMessageTime = new Date(conv.last_activity);
				const formattedTime = formatTime(lastMessageTime);

				html += '<li class="conversation-item ' + (conv.unread_count > 0 ? 'unread' : '') + '" ' +
						'data-conversation-id="' + conv.conversation_id + '" data-type="' + conv.conversation_type + '">' +
						'<div class="conversation-avatar">' +
						(conv.conversation_type === 'group' ? '<i class="fas fa-users"></i>' : '<i class="fas fa-user"></i>') +
						'</div>' +
						'<div class="conversation-info">' +
						'<div class="conversation-name">' + escapeHtml(conv.display_name) + '</div>' +
						'<div class="conversation-last-message">' + escapeHtml(conv.last_message || '') + '</div>' +
						'</div>' +
						'<div class="conversation-meta">' +
						'<div class="conversation-time">' + formattedTime + '</div>' +
						(conv.unread_count > 0 ? '<div class="conversation-unread">' + conv.unread_count + '</div>' : '') +
						'</div>' +
						'</li>';
			});

           // 대화 목록에 "모든 대화 보기" 링크 추가
            html += `
                <div class="view-all-link">
                    <a href="messenger.php" target="_blank">모든 대화 보기</a>
                </div>
            `;
            
            conversationsList.innerHTML = html;
        }
        
        /**
         * 읽지 않은 메시지 배지 업데이트 함수
         * @param {number} count - 읽지 않은 메시지 수
         */
        function updateUnreadBadges(count) {
            // 헤더 배지 업데이트
            const headerBadge = document.querySelector('.messenger-title .unread-badge');
            
            if (count > 0) {
                if (headerBadge) {
                    headerBadge.textContent = count;
                } else {
                    const badge = document.createElement('span');
                    badge.className = 'unread-badge';
                    badge.textContent = count;
                    document.querySelector('.messenger-title').appendChild(badge);
                }
            } else {
                if (headerBadge) {
                    headerBadge.remove();
                }
            }
            
            // 탭 배지 업데이트
            const tabBadge = document.querySelector('.tab-btn[data-tab="chats"] .tab-badge');
            
            if (count > 0) {
                if (tabBadge) {
                    tabBadge.textContent = count;
                } else {
                    const badge = document.createElement('span');
                    badge.className = 'tab-badge';
                    badge.textContent = count;
                    document.querySelector('.tab-btn[data-tab="chats"]').appendChild(badge);
                }
            } else {
                if (tabBadge) {
                    tabBadge.remove();
                }
            }
            
            // 상단 메뉴 배지 업데이트 (헤더 메뉴)
            updateHeaderMenuBadge(count);
        }
        
        /**
         * 상단 메뉴(헤더) 배지 업데이트 함수
         * @param {number} count - 읽지 않은 메시지 수
         */
        function updateHeaderMenuBadge(count) {
            // 헤더 메뉴의 메신저 배지 업데이트 (기존 인트라넷 헤더에 있는 경우)
            const headerMenuBadge = document.getElementById('messenger-notification-badge');
            
            if (headerMenuBadge) {
                if (count > 0) {
                    headerMenuBadge.textContent = count;
                    headerMenuBadge.style.display = 'inline';
                } else {
                    headerMenuBadge.style.display = 'none';
                }
            }
        }
        
        /**
         * 검색 실행 함수
         * @param {string} term - 검색어
         */
        function performSearch(term) {
            if (!term.trim()) return;
            
            // 현재 활성 탭 확인
            const activeTab = document.querySelector('.tab-btn.active').getAttribute('data-tab');
            
            if (activeTab === 'chats') {
                // 대화 검색
                searchConversations(term);
            } else if (activeTab === 'contacts') {
                // 연락처 검색
                searchContacts(term);
            }
        }
        
        /**
         * 대화 검색 함수
         * @param {string} term - 검색어
         */
        function searchConversations(term) {
            // 서버에 대화 검색 요청
            fetch(`messenger-ajax.php?action=search_conversations&term=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateConversationsList(data.conversations);
                    }
                })
                .catch(error => {
                    console.error('대화 검색 중 오류:', error);
                });
        }
        
        /**
         * 연락처 검색 함수
         * @param {string} term - 검색어
         */
        function searchContacts(term) {
            // 서버에 연락처 검색 요청
            fetch(`messenger-ajax.php?action=search_users&term=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        updateContactsList(data.users);
                    }
                })
                .catch(error => {
                    console.error('연락처 검색 중 오류:', error);
                });
        }
        
        /**
         * 연락처 목록 업데이트 함수
         * @param {Array} users - 사용자 목록 데이터
         */
        function updateContactsList(users) {
            const contactsList = document.querySelector('.contacts-list');
            
            if (!contactsList) return;
            
            if (users.length === 0) {
                contactsList.innerHTML = '<p class="no-contacts">검색 결과가 없습니다.</p>';
                return;
            }
            
            let html = '';
            
            users.forEach(user => {
                html += `
                    <li class="contact-item" data-user-id="${user.userid}">
                        <div class="contact-avatar">
                            <i class="fas fa-user"></i>
                            <span class="status-indicator ${user.is_online ? 'online' : 'offline'}"></span>
                        </div>
                        <div class="contact-info">
                            <div class="contact-name">${escapeHtml(user.kor_name)}</div>
                            <div class="contact-details">${escapeHtml(user.company_area || '')}</div>
                        </div>
                        <div class="contact-actions">
                            <button class="start-chat-btn" data-user-id="${user.userid}" 
                                    data-user-name="${escapeHtml(user.kor_name)}">
                                <i class="fas fa-comment"></i>
                            </button>
                        </div>
                    </li>
                `;
            });
            
            contactsList.innerHTML = html;
        }
        
        /**
         * 사용자 검색 함수 (새 메시지 모달용)
         * @param {string} term - 검색어
         */
        function searchUsers(term) {
            fetch(`messenger-ajax.php?action=search_users&term=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        renderSearchResults(data.users);
                    }
                })
                .catch(error => {
                    console.error('사용자 검색 중 오류:', error);
                });
        }
        
        /**
         * 검색 결과 렌더링 함수
         * @param {Array} users - 사용자 목록 데이터
         */
        function renderSearchResults(users) {
            if (users.length === 0) {
                recipientSearchResults.innerHTML = '<div class="search-empty">검색 결과가 없습니다.</div>';
                recipientSearchResults.classList.add('show');
                return;
            }
            
            let html = '';
            
            users.forEach(user => {
                // 이미 추가된 수신자인지 확인
                const isSelected = selectedRecipients.some(r => r.id === user.userid);
                
                if (!isSelected) {
                    html += `
                        <div class="search-result-item" data-user-id="${user.userid}">
                            <div class="search-result-avatar">
                                <i class="fas fa-user"></i>
                                <span class="status-indicator ${user.is_online ? 'online' : 'offline'}"></span>
                            </div>
                            <div class="search-result-info">
                                <div class="search-result-name">${escapeHtml(user.kor_name)}</div>
                                <div class="search-result-details">${escapeHtml(user.company_area || '')}</div>
                            </div>
                        </div>
                    `;
                }
            });
            
            if (!html) {
                html = '<div class="search-empty">모든 검색 결과가 이미 선택되었습니다.</div>';
            }
            
            recipientSearchResults.innerHTML = html;
            recipientSearchResults.classList.add('show');
        }
        
        /**
         * 새 메시지 알림 표시 함수
         * @param {Array} messages - 새 메시지 목록
         */
        function showNewMessageNotifications(messages) {
            // 알림 설정 확인
            const settings = JSON.parse(localStorage.getItem('messenger_notification_settings') || '{"desktop":true,"sound":true}');
            
            // 알림 없으면 종료
            if (!settings.desktop && !settings.sound) return;
            
            // 소리 알림
            if (settings.sound && notificationSound) {
                notificationSound.play().catch(error => {
                    console.error('알림 소리 재생 중 오류:', error);
                });
            }
            
            // 데스크톱 알림
            if (settings.desktop && 'Notification' in window && Notification.permission === 'granted') {
                // 대화별로 그룹화
                const conversationMessages = {};
                
                messages.forEach(message => {
                    if (!conversationMessages[message.conversation_id]) {
                        conversationMessages[message.conversation_id] = [];
                    }
                    
                    conversationMessages[message.conversation_id].push(message);
                });
                
                // 대화별로 알림 표시
                Object.keys(conversationMessages).forEach(conversationId => {
                    const messagesInConversation = conversationMessages[conversationId];
                    const latestMessage = messagesInConversation[messagesInConversation.length - 1];
                    
                    // 이미 대화창이 열려있는 경우 알림 표시 안함
                    const isConversationOpen = activeConversationWindows.some(win => 
                        win.conversationId === conversationId && 
                        !win.window.classList.contains('minimized')
                    );
                    
                    if (!isConversationOpen) {
                        const notification = new Notification(latestMessage.sender_name, {
                            body: latestMessage.content.length > 60 
                                ? latestMessage.content.substring(0, 57) + '...' 
                                : latestMessage.content,
                            icon: 'img/favi/favicon-32x32.png'
                        });
                        
                        // 알림 클릭 시 대화창 열기
                        notification.onclick = function() {
                            openConversationWindow(
                                conversationId,
                                latestMessage.conversation_name || latestMessage.sender_name,
                                latestMessage.conversation_type || 'individual'
                            );
                            
                            // 읽음 표시
                            markConversationAsRead(conversationId);
                            
                            this.close();
                            window.focus();
                        };
                        
                        // 5초 후 자동 닫기
                        setTimeout(() => {
                            notification.close();
                        }, 5000);
                    }
                });
            }
        }
        
        /**
         * 시간 포맷팅 함수
         * @param {Date} date - 날짜 객체
         * @returns {string} - 포맷팅된 시간 문자열
         */
        function formatTime(date) {
            const now = new Date();
            const diffMs = now - date;
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) {
                // 오늘
                return date.getHours().toString().padStart(2, '0') + ':' + 
                       date.getMinutes().toString().padStart(2, '0');
            } else if (diffDays === 1) {
                // 어제
                return '어제';
            } else if (diffDays < 7) {
                // 이번 주
                const days = ['일', '월', '화', '수', '목', '금', '토'];
                return days[date.getDay()] + '요일';
            } else {
                // 그 이전
                return (date.getMonth() + 1) + '/' + date.getDate();
            }
        }
        
        /**
         * HTML 이스케이프 함수
         * @param {string} text - 이스케이프할 텍스트
         * @returns {string} - 이스케이프된 텍스트
         */
        function escapeHtml(text) {
            if (!text) return '';
            
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, m => map[m]);
        }
        
        /**
         * 쿠키 설정 함수
         * @param {string} name - 쿠키 이름
         * @param {string} value - 쿠키 값
         * @param {number} days - 쿠키 유효 기간(일)
         */
        function setCookie(name, value, days) {
            let expires = '';
            
            if (days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            
            document.cookie = name + '=' + (value || '') + expires + '; path=/';
        }
        
        /**
         * 쿠키 가져오기 함수
         * @param {string} name - 쿠키 이름
         * @returns {string} - 쿠키 값
         */
        function getCookie(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            
            return null;
        }
    });
})();
