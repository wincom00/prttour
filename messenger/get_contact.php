<?php
// messenger/get_contacts.php
// 메신저에서 사용할 직원 연락처 목록 조회 API

// 세션 확인 및 필요한 포함 파일
session_start();
include "../include/inc_base.php";

// 응답 헤더 설정
header('Content-Type: application/json');

// 로그인 확인
if (!isset($_COOKIE['MEMLOGIN_ADMIN_PURUN']) || empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo json_encode(array('status' => 'error', 'message' => '로그인이 필요합니다.'));
    exit;
}

// 현재 로그인한 사용자의 ID
$current_user_id = $user_info['user_id'];

try {
    // 검색어 파라미터 (선택적)
    $search = isset($_GET['search']) ? '%' . mysql_real_escape_string($_GET['search'], $dbConn) . '%' : null;
    
    // 부서 필터링 (선택적)
    $department = isset($_GET['department']) ? mysql_real_escape_string($_GET['department'], $dbConn) : null;
    
    // 기본 쿼리 - 현재 활성화된 모든 직원 가져오기
    $sql = "SELECT m.userid, m.kor_name, m.eng_name, m.email, m.company_area, m.c_part, m.c_part1, 
                  m.profile_image_url, s.status
            FROM member_list m
            LEFT JOIN messenger_user_status s ON m.userid = s.user_id
            WHERE m.division = 'admin' AND (m.out_yn IS NULL OR m.out_yn = 'n')";
    
    // 현재 사용자 제외
    $sql .= " AND m.userid != '" . mysql_real_escape_string($current_user_id, $dbConn) . "'";
    
    // 검색어가 있으면 조건 추가
    if ($search) {
        $sql .= " AND (m.kor_name LIKE '" . $search . "' OR m.eng_name LIKE '" . $search . "' OR m.userid LIKE '" . $search . "')";
    }
    
    // 부서 필터링이 있으면 조건 추가
    if ($department) {
        $sql .= " AND (m.c_part = '" . $department . "' OR m.c_part1 = '" . $department . "')";
    }
    
    // 최근 대화 상대 우선 정렬
    $sql .= " ORDER BY IFNULL((
                SELECT MAX(updated_at) 
                FROM messenger_conversations 
                WHERE (user1_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "' AND user2_id = m.userid) 
                   OR (user1_id = m.userid AND user2_id = '" . mysql_real_escape_string($current_user_id, $dbConn) . "')
            ), '0000-00-00 00:00:00') DESC, m.kor_name ASC";
    //echo $sql;
    $result = mysql_query($sql, $dbConn);
    if (!$result) {
        throw new Exception("직원 목록 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
    }
    
    $contacts = array();
    
    while ($row = mysql_fetch_assoc($result)) {
        // 사용자 상태 확인
        $status = isset($row['status']) && $row['status'] ? $row['status'] : 'offline';
        
        // 마지막 활동 시간이 15분 이상 전이면 오프라인으로 처리
        if ($status !== 'offline') {
            $sql_activity = "SELECT last_activity FROM messenger_user_status WHERE user_id = '" . $row['userid'] . "'";
            $activity_result = mysql_query($sql_activity, $dbConn);
            
            if ($activity_result && mysql_num_rows($activity_result) > 0) {
                $activity_row = mysql_fetch_assoc($activity_result);
                $last_activity_time = strtotime($activity_row['last_activity']);
                $current_time = time();
                $inactive_period = 10 * 60; // 15분
                
                if (($current_time - $last_activity_time) > $inactive_period) {
                    $status = 'offline';
                }
            }
        }
        
        $contacts[] = array(
            'userid' => $row['userid'],
            'kor_name' => $row['kor_name'],
            'eng_name' => $row['eng_name'],
            'email' => $row['email'],
            'company_area' => $row['company_area'],
            'c_part' => $row['c_part'],
            'c_part1' => $row['c_part1'],
            'profile_image_url' => $row['profile_image_url'],
            'status' => $status
        );
    }
    
    // 부서 목록 가져오기 (선택적)
    $departments = array();
    
    if (isset($_GET['include_departments']) && $_GET['include_departments'] === 'true') {
        $sql = "SELECT DISTINCT c_part FROM member_list 
                WHERE division = 'admin' AND (out_yn IS NULL OR out_yn = 'n') AND c_part IS NOT NULL
                ORDER BY c_part";
        
        $result = mysql_query($sql, $dbConn);
        if (!$result) {
            throw new Exception("부서 목록 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        while ($row = mysql_fetch_assoc($result)) {
            $departments[] = $row['c_part'];
        }
    }
    
    // 응답 반환
    $response = array(
        'status' => 'success',
        'contacts' => $contacts
    );
    
    if (!empty($departments)) {
        $response['departments'] = $departments;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>