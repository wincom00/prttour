	/**
	 * messenger-widget.js (업데이트된 버전)
	 * 푸른투어 인트라넷 메신저 위젯 JavaScript (사용자 상태 표시 추가)
	 */

	// 즉시 실행 함수로 스코프 격리
	(function() {
		// 위젯 상태 변수
		let isWidgetOpen = false;
		let activeTab = 'contacts'; // 'contacts' 또는 'conversation'
		let selectedContact = null;
		let contacts = [];
		let conversations = {};
		let unreadCounts = {};
		let totalUnreadCount = 0;
		let userStatuses = {}; // 사용자 상태 저장 객체
		
		// 인터벌 및 타이머 ID 저장
		let contactsRefreshInterval = null;
		let messagePollingInterval = null;
		let statusRefreshInterval = null;
		
		// 위젯 DOM 요소
		let widgetContainer = null;
		let widgetElement = null;
		let contactsListElement = null;
		let messagesContainer = null;
		let messageInput = null;
		let unreadBadgeElement = null;
		let userStatusSelector = null;
		
		// 초기화 함수
		function initMessengerWidget() {
			// 위젯 마크업을 DOM에 삽입
			insertWidgetHTML();
			
			// DOM 요소 참조 저장
			widgetContainer = document.getElementById('messenger-widget-container');
			widgetElement = document.getElementById('messenger-widget');
			contactsListElement = document.getElementById('messenger-contacts-list');
			messagesContainer = document.getElementById('messenger-messages');
			messageInput = document.getElementById('messenger-input');
			unreadBadgeElement = document.getElementById('messenger-notification-badge');
			userStatusSelector = document.getElementById('user-status-selector');
			
			// 이벤트 리스너 등록
			attachEventListeners();
			
			// 연락처 목록 로드
			loadContacts();
			
			// 알림 권한 요청
			requestNotificationPermission();
			
			// 폴링 설정
			contactsRefreshInterval = setInterval(refreshUnreadCounts, 20000); // 20초마다 메시지 알림 갱신
			statusRefreshInterval = setInterval(refreshContactStatuses, 30000); // 30초마다 상태 갱신
		}
		
		// 위젯 HTML 생성 및 삽입
		function insertWidgetHTML() {
			const widgetHTML = `
				<div id="messenger-widget-container" class="messenger-widget-container">
					<!-- 위젯 트리거 버튼 -->
					<div id="messenger-trigger-button" class="messenger-trigger-button">
						<i class="fas fa-comments"></i>
						<span id="messenger-notification-badge" class="messenger-notification-badge" style="display: none;"></span>
					</div>
					
					<!-- 메신저 위젯 -->
					<div id="messenger-widget" class="messenger-widget hidden">
						<!-- 위젯 헤더 -->
						<div class="messenger-widget-header">
							<div class="messenger-widget-title">
								<i class="fas fa-comments"></i>
								<span id="messenger-widget-title-text">푸른투어 메신저</span>
							</div>
							<div class="messenger-widget-actions">
								<button id="messenger-widget-minimize" class="messenger-widget-action">
									<i class="fas fa-minus"></i>
								</button>
								<button id="messenger-widget-close" class="messenger-widget-action">
									<i class="fas fa-times"></i>
								</button>
							</div>
						</div>
						
						<!-- 상태 선택 영역 -->
						<div class="user-status-selector">
							<label for="user-status-selector">내 상태:</label>
							<select id="user-status-selector">
								<option value="online">온라인</option>
								<option value="away">자리비움</option>
								<option value="busy">바쁨</option>
								<option value="offline">오프라인</option>
							</select>
						</div>
						
						<!-- 위젯 콘텐츠 -->
						<div class="messenger-widget-content">
							<!-- 연락처 목록 탭 -->
							<div id="messenger-contacts-container" class="messenger-contacts-container">
								<!-- 검색 영역 -->
								<div class="messenger-search">
									<div class="messenger-search-wrapper">
										<i class="fas fa-search messenger-search-icon"></i>
										<input type="text" id="messenger-search-input" class="messenger-search-input" placeholder="이름 또는 ID로 검색...">
									</div>
								</div>
								
								<!-- 연락처 목록 -->
								<div id="messenger-contacts-list" class="messenger-contacts-list">
									<!-- 연락처들은 자바스크립트로 동적 생성 -->
									<div class="messenger-loading">
										<i class="fas fa-spinner fa-spin"></i> 연락처 불러오는 중...
									</div>
								</div>
							</div>
							
							<!-- 대화 탭 -->
							<div id="messenger-conversation-container" class="messenger-conversation" style="display: none;">
								<!-- 대화 헤더 -->
								<div class="messenger-conversation-header">
									<div id="messenger-conversation-back" class="messenger-conversation-back">
										<i class="fas fa-arrow-left"></i>
									</div>
									<div class="messenger-conversation-info">
										<div id="messenger-conversation-title" class="messenger-conversation-title"></div>
										<div id="messenger-conversation-status" class="conversation-status">
											<span class="status-indicator"></span>
											<span class="status-text"></span>
										</div>
									</div>
								</div>
								
								<!-- 메시지 목록 -->
								<div id="messenger-messages" class="messenger-messages">
									<!-- 메시지들은 자바스크립트로 동적 생성 -->
									<div class="messenger-empty-state">
										<div class="messenger-empty-icon">
											<i class="fas fa-comments"></i>
										</div>
										<div class="messenger-empty-title">대화를 시작해보세요</div>
										<div class="messenger-empty-message">메시지를 입력하여 대화를 시작할 수 있습니다.</div>
									</div>
								</div>
								
								<!-- 메시지 입력 영역 -->
								<div class="messenger-input-area">
									<textarea id="messenger-input" class="messenger-input" placeholder="메시지를 입력하세요..." rows="1"></textarea>
									<button id="messenger-send-button" class="messenger-send-button" disabled>
										<i class="fas fa-paper-plane"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			`;
			
			// Body 끝에 위젯 HTML 삽입
			const tempDiv = document.createElement('div');
			tempDiv.innerHTML = widgetHTML;
			document.body.appendChild(tempDiv.firstElementChild);
		}
		
		// 이벤트 리스너 추가
		function attachEventListeners() {
			// 위젯 열기/닫기 버튼
			const triggerButton = document.getElementById('messenger-trigger-button');
			triggerButton.addEventListener('click', toggleWidget);
			
			// 위젯 최소화 버튼
			const minimizeButton = document.getElementById('messenger-widget-minimize');
			minimizeButton.addEventListener('click', minimizeWidget);
			
			// 위젯 닫기 버튼
			const closeButton = document.getElementById('messenger-widget-close');
			closeButton.addEventListener('click', closeWidget);
			
			// 뒤로 가기 버튼
			const backButton = document.getElementById('messenger-conversation-back');
			backButton.addEventListener('click', showContactsList);
			
			// 메시지 전송 버튼
			// 이벤트 리스너 추가 (계속)
			const sendButton = document.getElementById('messenger-send-button');
			sendButton.addEventListener('click', sendMessage);
			
			// 메시지 입력창 이벤트 (엔터 키로 전송, 입력 시 버튼 활성화)
			messageInput.addEventListener('keypress', function(e) {
				if (e.key === 'Enter' && !e.shiftKey) {
					e.preventDefault();
					sendMessage();
				}
			});
			
			messageInput.addEventListener('input', function() {
				const sendButton = document.getElementById('messenger-send-button');
				sendButton.disabled = !this.value.trim();
				
				// 텍스트 영역 높이 자동 조절 (최대 3줄)
				this.style.height = 'auto';
				let newHeight = this.scrollHeight;
				if (newHeight > 100) newHeight = 100;
				this.style.height = newHeight + 'px';
			});
			
			// 검색 기능
			const searchInput = document.getElementById('messenger-search-input');
			searchInput.addEventListener('input', filterContacts);
			
			// 사용자 상태 변경 이벤트
			userStatusSelector.addEventListener('change', function() {
				updateUserStatus(this.value);
			});
			
			// 위젯 외부 클릭 시 닫기
			document.addEventListener('click', function(e) {
				if (isWidgetOpen && !widgetContainer.contains(e.target)) {
					closeWidget();
				}
			});
			
			// 사용자 상태 변경 이벤트 리스너
			window.addEventListener('userStatusChanged', function(e) {
				if (userStatusSelector) {
					userStatusSelector.value = e.detail.status;
				}
			});
		}
		
		// 위젯 토글 (열기/닫기)
		function toggleWidget() {
			if (isWidgetOpen) {
				closeWidget();
			} else {
				openWidget();
			}
		}
		
		// 위젯 열기
		function openWidget() {
			widgetElement.classList.remove('hidden');
			widgetElement.classList.add('active');
			isWidgetOpen = true;
			
			// 연락처 목록 업데이트
			loadContacts();
			
			// 현재 상태 로드
			loadCurrentUserStatus();
		}
		
		// 위젯 닫기
		function closeWidget() {
			widgetElement.classList.remove('active');
			widgetElement.classList.add('hidden');
			isWidgetOpen = false;
		}
		
		// 위젯 최소화
		function minimizeWidget() {
			closeWidget();
		}
		
		// 현재 사용자 상태 로드
		function loadCurrentUserStatus() {
			// 전역 상태 관리자가 있으면 그 값을 사용
			if (window.userStatusManager) {
				userStatusSelector.value = window.userStatusManager.currentStatus;
				return;
			}
			
			// 아니면 서버에서 상태 로드
			fetch('messenger/user_status.php')
				.then(response => response.json())
				.then(data => {
					if (data.status === 'success' && data.user_status) {
						userStatusSelector.value = data.user_status.status;
					}
				})
				.catch(error => {
					console.error('상태 로드 중 오류:', error);
				});
		}
		
		// 사용자 상태 업데이트
		function updateUserStatus(status) {
			// 전역 상태 관리자가 있으면 그 기능 사용
			if (window.userStatusManager) {
				window.userStatusManager.setManualStatus(status);
				return;
			}
			
			// 아니면 직접 API 호출
			fetch('messenger/user_status.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({ status: status })
			})
			.then(response => response.json())
			.then(data => {
				if (data.status !== 'success') {
					console.error('상태 업데이트 실패:', data.message);
				}
			})
			.catch(error => {
				console.error('상태 업데이트 요청 중 오류:', error);
			});
		}
		
		// 연락처 목록 로드
		function loadContacts() {
		contactsListElement.innerHTML = '<div class="messenger-loading"><i class="fas fa-spinner fa-spin"></i> 연락처 불러오는 중...</div>';

		fetch('messenger/get_contact.php')
			.then(response => {
				if (!response.ok) { // Check for HTTP errors (e.g., 404, 500)
					throw new Error(`HTTP error! status: ${response.status}`);
				}
				return response.json();
			})
			.then(data => {
				if (data.status === 'success') {
					// Ensure data.contacts is an array. If not, use an empty array.
					const receivedContacts = Array.isArray(data.contacts) ? data.contacts : [];

					// Assign to a global or appropriately scoped 'contacts' variable if needed
					// For example, if 'contacts' is a global variable:
					// window.contacts = receivedContacts;
					// Or if it's defined in a higher scope:
					// contacts = receivedContacts;
					// For this example, let's assume 'contacts' is a local const for clarity within this function's logic
					// and 'renderContacts' will use what's passed to it.

					if (receivedContacts.length === 0 && data.contacts !== undefined) {
						console.warn("연락처 데이터는 성공적으로 받았으나, 연락처 목록이 비어있습니다.");
					} else if (data.contacts === undefined) {
						console.warn("서버 응답에 'contacts' 필드가 없습니다. 빈 목록으로 처리합니다.");
					}

					// Now use 'receivedContacts' which is guaranteed to be an array
					fetchContactStatuses(receivedContacts.map(c => c.userid))
						.then(() => {
							renderContacts(receivedContacts); // Pass the validated contacts
							refreshUnreadCounts();
						})
						.catch(statusError => { // It's good practice to catch errors from nested promises
							console.error('상태 정보 로드 중 오류:', statusError);
							showError('연락처 상태를 불러오는 중 오류가 발생했습니다: ' + statusError.message);
							// Optionally, render contacts even if statuses fail, or handle differently
							// renderContacts(receivedContacts);
						});
				} else {
					showError('연락처를 불러오는 중 오류가 발생했습니다: ' + (data.message || '알 수 없는 오류'));
				}
			})
			.catch(error => {
				console.error('Fetch 오류:', error);
				showError('서버 연결 중 오류가 발생했습니다: ' + error.message);
				// Clear loading indicator on error
				contactsListElement.innerHTML = '<div class="messenger-error">연락처를 불러오지 못했습니다.</div>';
			});
		}

		
		// 사용자 상태 정보 가져오기
		function fetchContactStatuses(userIds) {
			if (!userIds || userIds.length === 0) {
				return Promise.resolve();
			}
			
			return fetch('messenger/user_status.php?user_ids=' + encodeURIComponent(JSON.stringify(userIds)))
				.then(response => response.json())
				.then(data => {
					if (data.status === 'success') {
						userStatuses = data.user_statuses || {};
					}
				})
				.catch(error => {
					console.error('사용자 상태 정보 가져오는 중 오류:', error);
				});
		}
		
		// 연락처 상태 정보 주기적 갱신
		function refreshContactStatuses() {
			if (contacts.length === 0) return;
			
			const userIds = contacts.map(c => c.userid);
			fetchContactStatuses(userIds)
				.then(() => {
					// 연락처 목록이 표시 중인 경우 상태 표시 업데이트
					if (isWidgetOpen && document.getElementById('messenger-contacts-container').style.display !== 'none') {
						updateContactStatusIndicators();
					}
					
					// 현재 대화 중인 상대방 상태 업데이트
					if (selectedContact) {
						updateConversationStatusIndicator(selectedContact.userid);
					}
				});
		}
		
		// 연락처 목록의 상태 표시기 업데이트
		function updateContactStatusIndicators() {
			const contactElements = document.querySelectorAll('.messenger-contact');
			
			contactElements.forEach(contactEl => {
				const userId = contactEl.dataset.userid;
				const statusIndicator = contactEl.querySelector('.status-indicator');
				
				if (statusIndicator) {
					// 기존 상태 클래스 모두 제거
					statusIndicator.classList.remove('online', 'away', 'busy', 'offline', 'unknown');
					
					// 새 상태 클래스 추가
					const status = userStatuses[userId] ? userStatuses[userId].status : 'unknown';
					statusIndicator.classList.add(status);
				}
			});
		}
		
		// 대화창 상태 표시기 업데이트
		function updateConversationStatusIndicator(userId) {
			const statusIndicator = document.querySelector('#messenger-conversation-status .status-indicator');
			const statusText = document.querySelector('#messenger-conversation-status .status-text');
			
			if (statusIndicator && statusText) {
				// 기존 상태 클래스 모두 제거
				statusIndicator.classList.remove('online', 'away', 'busy', 'offline', 'unknown');
				
				// 새 상태 클래스 및 텍스트 추가
				const status = userStatuses[userId] ? userStatuses[userId].status : 'unknown';
				statusIndicator.classList.add(status);
				
				// 상태에 따른 텍스트 설정
				switch (status) {
					case 'online':
						statusText.textContent = '온라인';
						break;
					case 'away':
						statusText.textContent = '자리비움';
						break;
					case 'busy':
						statusText.textContent = '바쁨';
						break;
					case 'offline':
						statusText.textContent = '오프라인';
						break;
					default:
						statusText.textContent = '상태 확인 중...';
				}
			}
		}
		
		// 읽지 않은 메시지 수 갱신
		function refreshUnreadCounts() {
			fetch('messenger/unread_count.php')
				.then(response => response.json())
				.then(data => {
					if (data.status === 'success') {
						unreadCounts = data.unread_counts;
						totalUnreadCount = data.unread_count;
						
						// 위젯 버튼의 배지 업데이트
						updateUnreadBadge();
						
						// 연락처 목록의 배지 업데이트
						if (contacts.length > 0) {
							updateContactBadges();
						}
						
						// 새 메시지 알림
						if (data.latest_unread && !isWidgetOpen && 
							(!selectedContact || selectedContact.userid !== data.latest_unread.sender_id)) {
							showNotification(data.latest_unread);
						}
					}
				})
				.catch(error => {
					console.error('읽지 않은 메시지 수를 가져오는 중 오류 발생:', error);
				});
		}
		
		// 위젯 버튼의 알림 배지 업데이트
		function updateUnreadBadge() {
			if (totalUnreadCount > 0) {
				unreadBadgeElement.textContent = totalUnreadCount > 99 ? '99+' : totalUnreadCount;
				unreadBadgeElement.style.display = 'flex';
			} else {
				unreadBadgeElement.style.display = 'none';
			}
		}
		
		// 연락처 목록의 배지 업데이트
		function updateContactBadges() {
			const contactElements = document.querySelectorAll('.messenger-contact');
			
			contactElements.forEach(contactEl => {
				const userId = contactEl.dataset.userid;
				const badgeEl = contactEl.querySelector('.messenger-contact-badge');
				
				if (unreadCounts[userId] && unreadCounts[userId] > 0) {
					if (badgeEl) {
						badgeEl.textContent = unreadCounts[userId];
					} else {
						const newBadge = document.createElement('div');
						newBadge.className = 'messenger-contact-badge';
						newBadge.textContent = unreadCounts[userId];
						contactEl.appendChild(newBadge);
					}
				} else if (badgeEl) {
					badgeEl.remove();
				}
			});
		}
		
		// 연락처 목록 렌더링 함수 수정
		function renderContacts(contactsList) {
			if (contactsList.length === 0) {
				contactsListElement.innerHTML = `
					<div class="messenger-empty-state">
						<div class="messenger-empty-icon">
							<i class="fas fa-user-friends"></i>
						</div>
						<div class="messenger-empty-title">연락처가 없습니다</div>
						<div class="messenger-empty-message">사용 가능한 연락처가 없습니다.</div>
					</div>
				`;
				return;
			}
			
			let html = '';
			
			contactsList.forEach(contact => {
				// 프로필 이미지 또는 기본 아바타
				let avatar = contact.profile_image_url 
					? `<img src="${contact.profile_image_url}" alt="${contact.kor_name}">`
					: `<span>${contact.kor_name.charAt(0)}</span>`;
				
				// 읽지 않은 메시지 배지
				let badge = '';
				if (unreadCounts[contact.userid] && unreadCounts[contact.userid] > 0) {
					badge = `<div class="messenger-contact-badge">${unreadCounts[contact.userid]}</div>`;
				}
				
				// 마지막 메시지 (추후 구현 예정)
				const lastMessage = '...';
				
				html += `
					<div class="messenger-contact" data-userid="${contact.userid}">
						<div class="messenger-contact-avatar">
							${avatar}
						</div>
						<div class="messenger-contact-info">
							<div class="messenger-contact-name">${contact.kor_name}</div>
							<div class="messenger-contact-preview">${contact.c_part1 || '부서 정보 없음'}</div>
						</div>
						${badge}
					</div>
				`;
			});
			
			contactsListElement.innerHTML = html;
			
			// 연락처 클릭 이벤트 추가
			document.querySelectorAll('.messenger-contact').forEach(el => {
				el.addEventListener('click', function() {
					
					const userId = this.dataset.userid;
					//console.log(userId);
					const contact = contactsList.find(c => c.userid === userId);
					//console.log(contactsList);
					if (contact) {
						openConversation(contact);
					}
				});
			});
		}
		
		// 연락처 필터링 (검색)
		function filterContacts() {
			const searchTerm = document.getElementById('messenger-search-input').value.toLowerCase();
			
			if (!searchTerm.trim()) {
				renderContacts(contacts);
				return;
			}
			
			const filteredContacts = contacts.filter(contact => 
				contact.kor_name.toLowerCase().includes(searchTerm) ||
				(contact.eng_name && contact.eng_name.toLowerCase().includes(searchTerm)) ||
				contact.userid.toLowerCase().includes(searchTerm)
			);
			
			renderContacts(filteredContacts);
		}
		
		// 대화 열기 함수 수정
			function openConversation(contact) {
				selectedContact = contact;
				//alert("11");
				// UI 업데이트
				document.getElementById('messenger-contacts-container').style.display = 'none';
				document.getElementById('messenger-conversation-container').style.display = 'flex';
				document.getElementById('messenger-conversation-title').textContent = contact.kor_name;
				document.getElementById('messenger-widget-title-text').textContent = contact.kor_name;
				
				// 메시지 로드
				loadMessages(contact.userid);
				
				// 폴링 설정
				if (messagePollingInterval) {
					clearInterval(messagePollingInterval);
				}
				
				messagePollingInterval = setInterval(() => {
					loadMessages(contact.userid, true);
				}, 5000); // 5초마다 새 메시지 확인
				
				// 메시지 읽음으로 표시
				markMessagesAsRead(contact.userid);
				
				// 메시지 입력창에 포커스
				messageInput.focus();
			}

		// 연락처 목록으로 돌아가기
		function showContactsList() {
			// 폴링 중지
			if (messagePollingInterval) {
				clearInterval(messagePollingInterval);
				messagePollingInterval = null;
			}
			
			// UI 업데이트
			document.getElementById('messenger-conversation-container').style.display = 'none';
			document.getElementById('messenger-contacts-container').style.display = 'block';
			document.getElementById('messenger-widget-title-text').textContent = '푸른투어 메신저';
			
			// 상태 초기화
			selectedContact = null;
			
			// 연락처 목록 새로고침
			loadContacts();
		}
		
		// 메시지 로드
		function loadMessages(userId, keepScroll = false) {
			// 현재 스크롤 위치
			const scrollPos = messagesContainer.scrollTop;
			const isScrolledToBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 10;
			
			if (!keepScroll || messagesContainer.innerHTML.includes('messenger-empty-state')) {
				messagesContainer.innerHTML = '<div class="messenger-loading"><i class="fas fa-spinner fa-spin"></i> 메시지 불러오는 중...</div>';
			}
			
			fetch(`messenger/get_messages.php?contact_id=${userId}`)
				.then(response => response.json())
				.then(data => {
					if (data.status === 'success') {
						// 메시지 저장
						conversations[userId] = data.messages;
						
						// 메시지 렌더링
						renderMessages(conversations[userId]);
						
						// 스크롤 위치 조정
						if (keepScroll) {
							if (isScrolledToBottom) {
								scrollToBottom();
							} else {
								messagesContainer.scrollTop = scrollPos;
							}
						} else {
							scrollToBottom();
						}
						
						// 알림 배지 업데이트
						refreshUnreadCounts();
					} else {
						showError('메시지를 불러오는 중 오류가 발생했습니다: ' + data.message);
					}
				})
				.catch(error => {
					showError('서버 연결 중 오류가 발생했습니다: ' + error.message);
				});
		}
		
		// 메시지 렌더링
		function renderMessages(messages) {
			if (!messages || messages.length === 0) {
				messagesContainer.innerHTML = `
					<div class="messenger-empty-state">
						<div class="messenger-empty-icon">
							<i class="fas fa-comments"></i>
						</div>		
						<div class="messenger-empty-title">대화를 시작해보세요</div>
						<div class="messenger-empty-message">메시지를 입력하여 대화를 시작할 수 있습니다.</div>
					</div>
				`;
				return;
			}
			
			// 현재 로그인한 사용자 ID
			const currentUserId = getCurrentUserId();
			
			let html = '';
			let currentDate = '';
			
			messages.forEach(message => {
				// 날짜 변경 여부 확인
				const messageDate = new Date(message.timestamp).toLocaleDateString();
				
				if (currentDate !== messageDate) {
					html += `<div class="messenger-date-divider">${messageDate}</div>`;
					currentDate = messageDate;
				}
				
				// 메시지 시간
				const messageTime = new Date(message.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
				
				// 보낸 메시지인지 받은 메시지인지 확인
				const isSent = message.sender_id === currentUserId;
				const messageClass = isSent ? 'sent' : 'received';
				
				html += `
					<div class="messenger-message ${messageClass}" data-message-id="${message.message_id}">
						<div class="messenger-message-content">
							${message.message}
							<div class="messenger-message-time">${messageTime}</div>
						</div>
					</div>
				`;
			});
			
			messagesContainer.innerHTML = html;
		}
		
		// 메시지 보내기
		function sendMessage() {
			if (!selectedContact) return;
			
			const messageText = messageInput.value.trim();
			if (!messageText) return;
			
			// 메시지 전송 API 호출
			fetch('messenger/send_message.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					recipient_id: selectedContact.userid,
					message: messageText
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.status === 'success') {
					// 메시지 입력창 초기화
					messageInput.value = '';
					messageInput.style.height = 'auto';
					document.getElementById('messenger-send-button').disabled = true;
					
					// 메시지 목록 새로고침
					loadMessages(selectedContact.userid);
				} else {
					showError('메시지 전송 중 오류가 발생했습니다: ' + data.message);
				}
			})
			.catch(error => {
				showError('서버 연결 중 오류가 발생했습니다: ' + error.message);
			});
		}
		
		// 메시지 읽음으로 표시
		function markMessagesAsRead(userId) {
			fetch('messenger/mark_as_read.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					contact_id: userId
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.status === 'success') {
					// 읽지 않은 메시지 수 업데이트
					if (unreadCounts[userId]) {
						totalUnreadCount -= unreadCounts[userId];
						unreadCounts[userId] = 0;
						
						// 배지 업데이트
						updateUnreadBadge();
						updateContactBadges();
					}
				}
			})
			.catch(error => {
				console.error('메시지를 읽음으로 표시하는 중 오류 발생:', error);
			});
		}
		
		// 브라우저 알림 권한 요청
		function requestNotificationPermission() {
			if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
				setTimeout(() => {
					Notification.requestPermission();
				}, 3000);
			}
		}
		
		// 브라우저 알림 표시
		function showNotification(messageData) {
			if ('Notification' in window && Notification.permission === 'granted') {
				const notification = new Notification('푸른투어 메신저', {
					body: `${messageData.sender_name}: ${messageData.message}`,
					icon: 'img/favi/favicon-32x32.png'
				});
				
				notification.onclick = function() {
					window.focus();
					
					// 위젯 열기 및 해당 대화로 이동
					openWidget();
					
					const contact = contacts.find(c => c.userid === messageData.sender_id);
					if (contact) {
						openConversation(contact);
					}
					
					this.close();
				};
				
				setTimeout(() => {
					notification.close();
				}, 5000);
			}
		}
		
		// 스크롤을 메시지 목록 하단으로 이동
		function scrollToBottom() {
			messagesContainer.scrollTop = messagesContainer.scrollHeight;
		}
		
		// 현재 로그인한 사용자 ID 가져오기
		function getCurrentUserId() {
			// 쿠키에서 사용자 ID 가져오기 (푸른투어 인트라넷 방식)
			const cookies = document.cookie.split(';');
			for (let i = 0; i < cookies.length; i++) {
				const cookie = cookies[i].trim();
				if (cookie.startsWith('MEMLOGIN_ADMIN_PURUN=')) {
					return cookie.substring('MEMLOGIN_ADMIN_PURUN='.length, cookie.length);
				}
			}
			return null;
		}
		
		// 오류 메시지 표시
		function showError(message) {
			console.error(message);
			
			// 푸른투어 인트라넷이 jQuery를 사용하는 경우
			if (typeof $ !== 'undefined' && typeof $.sticky === 'function') {
				$.sticky(message, {
					type: 'st-error',
					autoclose: 5000,
					position: 'top-right'
				});
			} else {
				alert('오류: ' + message);
			}
		}
		
		// 페이지 로드 시 위젯 초기화
		document.addEventListener('DOMContentLoaded', initMessengerWidget);
	})();