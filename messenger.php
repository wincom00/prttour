<?php
// messenger.php
// 메신저 메인 페이지

include "include/header_n.php";

// 로그인 확인
if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

// 현재 사용자 정보 가져오기
$current_user_id = $user_info['user_id'];
$qry = "SELECT userid, kor_name, eng_name, email, c_part, c_part1, profile_image_url FROM member_list WHERE userid = '" . mysql_real_escape_string($current_user_id, $dbConn) . "'";
$rst = mysql_query($qry, $dbConn);
$current_user = mysql_fetch_assoc($rst);
?>

<link rel="stylesheet" href="css/messenger.css?v=1.0">

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li>
                    <a href="index.php"><i class="glyphicon glyphicon-home"></i></a>
                </li>
                <li>
                    푸른투어 메신저
                </li>
            </ul>
        </div>
        
        <div class="row">
            <div class="col-sm-12">
                <h3 class="heading"><i class="fas fa-comments"></i> <strong>푸른투어 메신저</strong></h3>
            </div>
        </div>
        
        <!-- 메신저 앱 컨테이너 -->
        <div class="row">
            <div class="col-sm-12">
                <div id="messenger-app">
                    <!-- 왼쪽 사이드바 - 연락처 목록 -->
                    <div class="messenger-sidebar">
                        <div class="search-box">
                            <input type="text" id="contact-search" placeholder="이름 또는 아이디로 검색..." class="form-control">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        
                        <div class="contact-filters">
                            <select id="department-filter" class="form-control">
                                <option value="">모든 부서</option>
                                <!-- 부서 목록은 JavaScript에서 동적으로 생성 -->
                            </select>
                        </div>
                        
                        <div class="contacts-list" id="contacts-container">
                            <!-- 연락처 목록은 JavaScript에서 동적으로 생성 -->
                            <div class="loading-spinner">
                                <i class="fas fa-spinner fa-spin"></i> 연락처 로딩 중...
                            </div>
                        </div>
                    </div>
                    
                    <!-- 오른쪽 영역 - 메시지 화면 -->
                    <div class="messenger-content">
                        <!-- 초기 화면 (대화 선택 전) -->
                        <div id="no-conversation-selected" class="no-conversation">
                            <div class="no-conversation-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3>대화를 시작하세요</h3>
                            <p>왼쪽 목록에서 연락처를 선택하여 대화를 시작할 수 있습니다.</p>
                        </div>
                        
                        <!-- 대화 화면 (대화 선택 후) -->
                        <div id="conversation-container" class="conversation-container" style="display: none;">
                            <!-- 대화 헤더 -->
                            <div class="conversation-header" id="conversation-header">
                                <!-- 대화 상대 정보는 JavaScript에서 동적으로 생성 -->
                            </div>
                            
                            <!-- 메시지 목록 -->
                            <div class="messages-container" id="messages-container">
                                <!-- 메시지는 JavaScript에서 동적으로 생성 -->
                            </div>
                            
                            <!-- 메시지 입력 영역 -->
                            <div class="message-input-area">
                                <textarea id="message-input" placeholder="메시지를 입력하세요..." class="form-control"></textarea>
                                <button id="send-message-btn" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> 전송
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<!-- 메신저 JavaScript -->
<script src="js/messenger.js?v=1.0"></script>

</body>
</html>