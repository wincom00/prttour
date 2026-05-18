/**
 * messenger.js
 * 푸른투어 인트라넷 메신저 기능을 위한 JavaScript 파일
 */

// 전역 변수
let contacts = [];              // 연락처 목록
let selectedContact = null;     // 현재 선택된 연락처
let messages = [];              // 현재 대화의 메시지 목록
let unreadCounts = {};          // 읽지 않은 메시지 수
let departmentList = [];        // 부서 목록
let messagePollingInterval = null; // 메시지 폴링 인터벌 ID

// DOM 요소
const contactsContainer = document.getElementById('contacts-container');
const conversationHeader = document.getElementById('conversation-header');
const messagesContainer = document.getElementById('messages-container');
const messageInput = document.getElementById('message-input');
const sendMessageBtn = document.getElementById('send-message-btn');
const contactSearch = document.getElementById('contact-search');
const departmentFilter = document.getElementById('department-filter');
const noConversationDiv = document.getElementById('no-conversation-selected');
const conversationContainer = document.getElementById('conversation-container');

// 초기화 함수
function initMessenger() {
    // 연락처 목록 로드
    loadContacts();
    
    // 이벤트 리스너 추가
    sendMessageBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', handleMessageInputKeypress);
    contactSearch.addEventListener('input', filterContacts);
    departmentFilter.addEventListener('change', filterContacts);
    
    // 5초마다 읽지 않은 메시지 수 업데이트
    setInterval(updateUnreadCounts, 5000);
}

// 연락처 목록 로드
function loadContacts() {
    contactsContainer.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> 연락처 로딩 중...</div>';
    
    fetch('messenger/get_contact.php?include_departments=true')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                contacts = data.contacts;
                
                // 부서 목록 저장 및 드롭다운 생성 (있는 경우)
                if (data.departments) {
                    departmentList = data.departments;
                    populateDepartmentFilter(departmentList);
                }
                
                // 연락처 목록 렌더링
                renderContacts(contacts);
                
                // 읽지 않은 메시지 수 로드
                updateUnreadCounts();
            } else {
                showError('연락처를 불러오는 중 오류가 발생했습니다: ' + data.message);
            }
        })
        .catch(error => {
            showError('서버 연결 중 오류가 발생했습니다: ' + error.message);
        });
}

// 부서 필터 드롭다운 생성
function populateDepartmentFilter(departments) {
    let options = '<option value="">모든 부서</option>';
    
    departments.forEach(dept => {
        options += `<option value="${dept}">${dept}</option>`;
    });
    
    departmentFilter.innerHTML = options;
}

// 연락처 목록 렌더링
function renderContacts(contactsList) {
    if (contactsList.length === 0) {
        contactsContainer.innerHTML = '<div class="no-contacts">연락처가 없습니다.</div>';
        return;
    }
    
    let html = '';
    
    contactsList.forEach(contact => {
        // 프로필 이미지 또는 기본 아바타
        let avatar = contact.profile_image_url 
            ? `<img src="${contact.profile_image_url}" alt="${contact.kor_name}" class="contact-avatar">`
            : `<div class="default-avatar">${contact.kor_name.charAt(0)}</div>`;
        
        // 읽지 않은 메시지 배지
        let unreadBadge = '';
        if (unreadCounts[contact.userid] && unreadCounts[contact.userid] > 0) {
            unreadBadge = `<span class="unread-badge">${unreadCounts[contact.userid]}</span>`;
        }
        
        html += `
            <div class="contact-item" data-userid="${contact.userid}">
                <div class="contact-avatar-container">
                    ${avatar}
                </div>
                <div class="contact-info">
                    <div class="contact-name">${contact.kor_name}</div>
                    <div class="contact-department">${contact.c_part1 || '부서 정보 없음'}</div>
                </div>
                ${unreadBadge}
            </div>
        `;
    });
    
    contactsContainer.innerHTML = html;
    
    // 연락처 클릭 이벤트 리스너 추가
    document.querySelectorAll('.contact-item').forEach(item => {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-userid');
            const contact = contacts.find(c => c.userid === userId);
            
            if (contact) {
                selectContact(contact);
            }
        });
    });
}

// 연락처 필터링
function filterContacts() {
    const searchTerm = contactSearch.value.toLowerCase();
    const departmentValue = departmentFilter.value;
    
    // 검색어와 부서 필터 모두 적용
    const filteredContacts = contacts.filter(contact => {
        const nameMatch = contact.kor_name.toLowerCase().includes(searchTerm) || 
                         (contact.eng_name && contact.eng_name.toLowerCase().includes(searchTerm)) ||
                         contact.userid.toLowerCase().includes(searchTerm);
        
        const departmentMatch = !departmentValue || 
                              contact.c_part === departmentValue ||
                              contact.c_part1 === departmentValue;
        
        return nameMatch && departmentMatch;
    });
    
    renderContacts(filteredContacts);
}

// 연락처 선택 처리
function selectContact(contact) {
    // 이전 연락처 선택 해제
    const previousSelectedItem = document.querySelector('.contact-item.selected');
    if (previousSelectedItem) {
        previousSelectedItem.classList.remove('selected');
    }
    
    // 새 연락처 선택
    const newSelectedItem = document.querySelector(`.contact-item[data-userid="${contact.userid}"]`);
    if (newSelectedItem) {
        newSelectedItem.classList.add('selected');
    }
    
    selectedContact = contact;
    
    // UI 상태 변경
    noConversationDiv.style.display = 'none';
    conversationContainer.style.display = 'flex';
    
    // 대화 헤더 업데이트
    updateConversationHeader(contact);
    
    // 해당 연락처의 메시지 로드
    loadMessages(contact.userid);
    
    // 기존 메시지 폴링 정지
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    
    // 새 메시지 폴링 시작 (5초마다)
    messagePollingInterval = setInterval(() => {
        loadMessages(contact.userid, true);
    }, 5000);
    
    // 메시지 읽음으로 표시
    markMessagesAsRead(contact.userid);
}

// 대화 헤더 업데이트
function updateConversationHeader(contact) {
    // 프로필 이미지 또는 기본 아바타
    let avatar = contact.profile_image_url 
        ? `<img src="${contact.profile_image_url}" alt="${contact.kor_name}" class="conversation-avatar">`
        : `<div class="conversation-default-avatar">${contact.kor_name.charAt(0)}</div>`;
    
    conversationHeader.innerHTML = `
        <div class="conversation-contact">
            <div class="conversation-avatar-container">
                ${avatar}
            </div>
            <div class="conversation-contact-info">
                <div class="conversation-contact-name">${contact.kor_name}</div>
                <div class="conversation-contact-department">${contact.c_part1 || '부서 정보 없음'}</div>
            </div>
        </div>
    `;
}

// 메시지 로드
function loadMessages(userId, keepScroll = false) {
    const currentScrollPosition = messagesContainer.scrollTop;
    const isScrolledToBottom = 
        messagesContainer.scrollHeight - messagesContainer.clientHeight <= messagesContainer.scrollTop + 5;
    
    fetch(`messenger/get_messages.php?contact_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                messages = data.messages;
                renderMessages(messages);
                
                // 스크롤 위치 유지 또는 하단으로 이동
                if (keepScroll) {
                    if (isScrolledToBottom) {
                        scrollToBottom();
                    } else {
                        messagesContainer.scrollTop = currentScrollPosition;
                    }
                } else {
                    scrollToBottom();
                }
                
                // 이 연락처에 대한 읽지 않은 메시지 수 업데이트
                updateUnreadCounts();
            } else {
                showError('메시지를 불러오는 중 오류가 발생했습니다: ' + data.message);
            }
        })
        .catch(error => {
            showError('서버 연결 중 오류가 발생했습니다: ' + error.message);
        });
}

// 메시지 렌더링
function renderMessages(messagesList) {
    if (messagesList.length === 0) {
        messagesContainer.innerHTML = '<div class="no-messages">대화를 시작해보세요.</div>';
        return;
    }
    
    let html = '';
    let currentDate = '';
    
    messagesList.forEach(message => {
        // 날짜 구분선 표시
        const messageDate = new Date(message.timestamp).toLocaleDateString();
        if (currentDate !== messageDate) {
            html += `<div class="date-divider">${messageDate}</div>`;
            currentDate = messageDate;
        }
        
        // 메시지 시간 포맷팅
        const messageTime = new Date(message.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        // 메시지 타입 (보낸 메시지 또는 받은 메시지)
        const messageType = message.sender_id === userId ? 'sent' : 'received';
        
        html += `
            <div class="message ${messageType}" data-message-id="${message.message_id}">
                <div class="message-bubble">
                    ${message.message}
                    <div class="message-time">${messageTime}</div>
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
            
            // 메시지 목록 다시 로드
            loadMessages(selectedContact.userid);
        } else {
            showError('메시지 전송 중 오류가 발생했습니다: ' + data.message);
        }
    })
    .catch(error => {
        showError('서버 연결 중 오류가 발생했습니다: ' + error.message);
    });
}

// 키보드 이벤트 처리 (Enter로 메시지 전송)
function handleMessageInputKeypress(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
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
            unreadCounts[userId] = 0;
            
            // 연락처 목록 업데이트 (배지 제거)
            const contactItem = document.querySelector(`.contact-item[data-userid="${userId}"]`);
            if (contactItem) {
                const badge = contactItem.querySelector('.unread-badge');
                if (badge) {
                    badge.remove();
                }
            }
        }
    })
    .catch(error => {
        console.error('메시지를 읽음으로 표시하는 중 오류 발생:', error);
    });
}

// 읽지 않은 메시지 수 업데이트
function updateUnreadCounts() {
    fetch('messenger/unread_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                unreadCounts = data.unread_counts;
                
                // 헤더의 메시지 아이콘 배지 업데이트
                updateHeaderBadge(data.unread_count);
                
                // 연락처 목록의 배지 업데이트
                updateContactBadges();
                
                // 새 메시지가 있고 브라우저 알림이 가능하면 알림 표시
                if (data.latest_unread && Notification.permission === 'granted' && !document.hasFocus()) {
                    showBrowserNotification(data.latest_unread);
                }
            }
        })
        .catch(error => {
            console.error('읽지 않은 메시지 수를 가져오는 중 오류 발생:', error);
        });
}

// 헤더 배지 업데이트
function updateHeaderBadge(count) {
    const badge = document.getElementById('messenger-notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// 연락처 배지 업데이트
function updateContactBadges() {
    document.querySelectorAll('.contact-item').forEach(item => {
        const userId = item.getAttribute('data-userid');
        const count = unreadCounts[userId] || 0;
        
        // 기존 배지 제거
        const existingBadge = item.querySelector('.unread-badge');
        if (existingBadge) {
            existingBadge.remove();
        }
        
        // 읽지 않은 메시지가 있으면 배지 추가
        if (count > 0) {
            const badge = document.createElement('span');
            badge.className = 'unread-badge';
            badge.textContent = count;
            item.appendChild(badge);
        }
    });
}

// 브라우저 알림 표시
function showBrowserNotification(messageData) {
    // 현재 선택된 연락처의 메시지면 알림 표시하지 않음
    if (selectedContact && messageData.sender_id === selectedContact.userid) {
        return;
    }
    
    const notification = new Notification('푸른투어 메신저', {
        body: `${messageData.sender_name}: ${messageData.message}`,
        icon: 'img/favi/favicon-32x32.png'
    });
    
    // 알림 클릭 시 해당 대화로 이동
    notification.onclick = function() {
        window.focus();
        
        // 해당 연락처 찾아 선택
        const contact = contacts.find(c => c.userid === messageData.sender_id);
        if (contact) {
            selectContact(contact);
        }
        
        this.close();
    };
    
    // 5초 후 자동으로 알림 닫기
    setTimeout(() => {
        notification.close();
    }, 5000);
}

// 스크롤을 메시지 목록 하단으로 이동
function scrollToBottom() {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// 오류 메시지 표시
function showError(message) {
    console.error(message);
    
    // 토스트 알림 표시 (푸른투어 인트라넷이 jQuery 사용)
    if (typeof $.sticky === 'function') {
        $.sticky(message, {
            type: 'st-error',
            autoclose: 5000,
            position: 'top-right'
        });
    } else {
        alert('오류: ' + message);
    }
}

// 모듈 초기화
document.addEventListener('DOMContentLoaded', initMessenger);

// 브라우저 알림 권한 요청
if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
    // 페이지 로드 1초 후 권한 요청
    setTimeout(() => {
        Notification.requestPermission();
    }, 1000);
}