<?php
// messenger/user_status.php
// 사용자 상태 관리 API

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

// 요청 방식에 따라 분기
$method = $_SERVER['REQUEST_METHOD'];

try {
    // 상태 GET 요청 처리
    if ($method === 'GET') {
        // 단일 사용자 상태 요청
        if (isset($_GET['user_id'])) {
            $user_id = mysql_real_escape_string($_GET['user_id'], $dbConn);
            
            $sql = "SELECT user_id, status, last_activity FROM messenger_user_status WHERE user_id = '$user_id'";
            $result = mysql_query($sql, $dbConn);
            echo $sql;
            if (!$result) {
                throw new Exception("사용자 상태 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
            }
            
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_assoc($result);
                echo json_encode(array(
                    'status' => 'success',
                    'user_status' => array(
                        'user_id' => $row['user_id'],
                        'status' => $row['status'],
                        'last_activity' => $row['last_activity']
                    )
                ));
            } else {
                // 상태 정보가 없으면 기본값 반환
                echo json_encode(array(
                    'status' => 'success',
                    'user_status' => array(
                        'user_id' => $user_id,
                        'status' => 'offline',
                        'last_activity' => null
                    )
                ));
            }
        } 
        // 여러 사용자 상태 요청
        else if (isset($_GET['user_ids'])) {
            $user_ids = json_decode($_GET['user_ids'], true);
            if (!$user_ids || !is_array($user_ids)) {
                throw new Exception("잘못된 사용자 ID 목록 형식입니다.");
            }
            
            // 안전한 SQL을 위해 각 ID를 이스케이프
            $safe_user_ids = array();
            foreach ($user_ids as $id) {
                $safe_user_ids[] = "'" . mysql_real_escape_string($id, $dbConn) . "'";
            }
            
            // ID 목록이 비어있으면 빈 결과 반환
            if (empty($safe_user_ids)) {
                echo json_encode(array(
                    'status' => 'success',
                    'user_statuses' => array()
                ));
                exit;
            }
            
            $ids_str = implode(',', $safe_user_ids);
            $sql = "SELECT user_id, status, last_activity FROM messenger_user_status WHERE user_id IN ($ids_str)";
            $result = mysql_query($sql, $dbConn);
            
            if (!$result) {
                throw new Exception("사용자 상태 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
            }
            
            $statuses = array();
            while ($row = mysql_fetch_assoc($result)) {
                $statuses[$row['user_id']] = array(
                    'status' => $row['status'],
                    'last_activity' => $row['last_activity']
                );
            }
            
            // 요청한 모든 ID에 대해 상태 정보 제공 (없는 ID는 offline으로 설정)
            $response_statuses = array();
            foreach ($user_ids as $id) {
                if (isset($statuses[$id])) {
                    $response_statuses[$id] = $statuses[$id];
                } else {
                    $response_statuses[$id] = array(
                        'status' => 'offline',
                        'last_activity' => null
                    );
                }
            }
            
            echo json_encode(array(
                'status' => 'success',
                'user_statuses' => $response_statuses
            ));
        }
        // 모든 온라인 사용자 상태 요청
        else {
            // 최근 15분 이내 활동이 있는 사용자만
            $time_threshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));
            
            $sql = "SELECT user_id, status, last_activity FROM messenger_user_status 
                    WHERE last_activity > '$time_threshold' AND status != 'offline'";
            $result = mysql_query($sql, $dbConn);
            
            if (!$result) {
                throw new Exception("온라인 사용자 상태 조회 중 오류가 발생했습니다: " . mysql_error($dbConn));
            }
            
            $online_users = array();
            while ($row = mysql_fetch_assoc($result)) {
                $online_users[$row['user_id']] = array(
                    'status' => $row['status'],
                    'last_activity' => $row['last_activity']
                );
            }
            
            echo json_encode(array(
                'status' => 'success',
                'online_users' => $online_users
            ));
        }
    }
    // 상태 UPDATE 요청 처리 (POST)
    else if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['status']) || empty($data['status'])) {
            throw new Exception("상태값이 누락되었습니다.");
        }
        
        $status = mysql_real_escape_string($data['status'], $dbConn);
        
        // 유효한 상태값 검증
        $valid_statuses = array('online', 'away', 'busy', 'offline');
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("유효하지 않은 상태값입니다: " . $status);
        }
        
        // 현재 시간
        $now = date('Y-m-d H:i:s');
        
        // 데이터 존재 확인 후 INSERT 또는 UPDATE
        $check_sql = "SELECT COUNT(*) as count FROM messenger_user_status WHERE user_id = '$current_user_id'";
        $check_result = mysql_query($check_sql, $dbConn);
        
        if (!$check_result) {
            throw new Exception("사용자 상태 확인 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        $row = mysql_fetch_assoc($check_result);
        
        if ($row['count'] > 0) {
            // 기존 데이터 업데이트
            $sql = "UPDATE messenger_user_status 
                    SET status = '$status', last_activity = '$now' 
                    WHERE user_id = '$current_user_id'";
        } else {
            // 새 데이터 삽입
            $sql = "INSERT INTO messenger_user_status (user_id, status, last_activity) 
                    VALUES ('$current_user_id', '$status', '$now')";
        }
        
        $result = mysql_query($sql, $dbConn);
        
        if (!$result) {
            throw new Exception("사용자 상태 업데이트 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        echo json_encode(array(
            'status' => 'success',
            'message' => '상태가 업데이트되었습니다.'
        ));
    }
    // 활동 시간만 업데이트 (사용자가 활동 중임을 나타냄)
    else if ($method === 'PUT') {
        // 현재 시간
        $now = date('Y-m-d H:i:s');
        
        // 데이터 존재 확인 후 INSERT 또는 UPDATE
        $check_sql = "SELECT COUNT(*) as count FROM messenger_user_status WHERE user_id = '$current_user_id'";
        $check_result = mysql_query($check_sql, $dbConn);
        
        if (!$check_result) {
            throw new Exception("사용자 상태 확인 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        $row = mysql_fetch_assoc($check_result);
        
        if ($row['count'] > 0) {
            // 기존 데이터 업데이트
            $sql = "UPDATE messenger_user_status 
                    SET last_activity = '$now' 
                    WHERE user_id = '$current_user_id'";
        } else {
            // 새 데이터 삽입 (기본 상태는 online)
            $sql = "INSERT INTO messenger_user_status (user_id, status, last_activity) 
                    VALUES ('$current_user_id', 'online', '$now')";
        }
        
        $result = mysql_query($sql, $dbConn);
        
        if (!$result) {
            throw new Exception("사용자 활동 시간 업데이트 중 오류가 발생했습니다: " . mysql_error($dbConn));
        }
        
        echo json_encode(array(
            'status' => 'success',
            'message' => '활동 시간이 업데이트되었습니다.'
        ));
    }
    else {
        throw new Exception("지원하지 않는 요청 방식입니다: " . $method);
    }
} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}
?>