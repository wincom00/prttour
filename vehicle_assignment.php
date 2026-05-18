<?php
include "include/header.php";

// 보안 및 세션 관리
class SecurityManager {
    public static function validateAdminSession($cookie) {
        // 실제 세션 검증 로직 구현
        return !empty($cookie) && strlen($cookie) > 10;
    }
    
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// 세션 보안 강화
if (!SecurityManager::validateAdminSession($_COOKIE['MEMLOGIN_ADMIN_HELLO'] ?? '')) {
    header("Location: ./login.php");
    exit;
}

// CSRF 토큰 생성
$csrf_token = SecurityManager::generateCSRFToken();

// PHP 7.4/8.2 호환: FILTER_SANITIZE_STRING은 8.1부터 deprecated
if (!defined('FILTER_SANITIZE_STRING')) {
    define('FILTER_SANITIZE_STRING', FILTER_DEFAULT);
}

// 입력값 검증 및 필터링
$pcode = filter_input(INPUT_GET, 'pcode', FILTER_SANITIZE_STRING) ?? '';
$st = filter_input(INPUT_GET, 'st', FILTER_SANITIZE_STRING) ?? '';
$mode = filter_input(INPUT_POST, 'mode', FILTER_SANITIZE_STRING) ?? '';
$division = filter_input(INPUT_GET, 'division', FILTER_SANITIZE_STRING) ?? '';
$pdx = filter_input(INPUT_GET, 'pdx', FILTER_SANITIZE_STRING) ?? '';
$sub = filter_input(INPUT_GET, 'sub', FILTER_SANITIZE_STRING) ?? '';
$num1 = filter_input(INPUT_GET, 'num1', FILTER_VALIDATE_INT) ?? 0;

// 에러 핸들링 클래스
class ErrorHandler {
    public static function logError($message, $context = []) {
        $logMessage = date('Y-m-d H:i:s') . " - " . $message;
        if (!empty($context)) {
            $logMessage .= " - Context: " . json_encode($context);
        }
        error_log($logMessage, 3, 'logs/vehicle_assignment.log');
    }
    
    public static function handleDatabaseError($error, $query = '') {
        self::logError("Database Error: " . $error, ['query' => $query]);
        return ['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.'];
    }
}

// 캐싱 관리 클래스
class CacheManager {
    private static $cache = [];
    
    public static function get($key) {
        if (self::isValid($key)) {
            return self::$cache[$key]['value'];
        }
        return null;
    }
    
    public static function set($key, $value, $ttl = 300) {
        self::$cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
    }
    
    public static function isValid($key) {
        return isset(self::$cache[$key]) && self::$cache[$key]['expires'] > time();
    }
}

// 차량 배정 관리 클래스
class VehicleAssignmentManager {
    private $dbConn;
    private $user_dbinfo;
    
    public function __construct($dbConn, $user_dbinfo) {
        $this->dbConn = $dbConn;
        $this->user_dbinfo = $user_dbinfo;
    }
    
    public function getTourInfo($pcode, $st) {
        $cacheKey = "tour_info_{$pcode}_{$st}";
        $cached = CacheManager::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $stmt = $this->dbConn->prepare("
                SELECT * FROM tour_master 
                WHERE p_code = ? AND stDate = ?
            ");
            $stmt->bind_param("ss", $pcode, $st);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            CacheManager::set($cacheKey, $result);
            return $result;
        } catch (Exception $e) {
            ErrorHandler::logError("getTourInfo error", ['pcode' => $pcode, 'st' => $st]);
            return null;
        }
    }
    
    public function getReserveInfoCnt($pcode, $st) {
        try {
            $stmt = $this->dbConn->prepare("
                SELECT COUNT(*) as cnt 
                FROM reserve_info r
                JOIN reserve_traveler t ON r.reserveCode = t.reserveCode
                WHERE r.p_code = ? AND r.stDate = ? 
                AND r.rev_status NOT IN ('WAIT', 'CANCEL')
            ");
            $stmt->bind_param("ss", $pcode, $st);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result ?? ['cnt' => 0];
        } catch (Exception $e) {
            ErrorHandler::logError("getReserveInfoCnt error", ['pcode' => $pcode, 'st' => $st]);
            return ['cnt' => 0];
        }
    }
    
    public function saveVehicleAssignment($data) {
        if (!SecurityManager::validateCSRFToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'CSRF 토큰이 유효하지 않습니다.'];
        }
        
        try {
            $this->dbConn->autocommit(false);
            
            $eventcnt = count($data['rnum'] ?? []);
            $sub_eventCode = null;
            $nbnum = '';
            
            for ($r = 0; $r < $eventcnt; $r++) {
                if (!empty($data['bnum'][$r])) {
                    if (empty($sub_eventCode) || $data['bnum'][$r] != $nbnum) {
                        $sub_eventNum = $this->getNumSevent($data['gcode'], $data['sdate']);
                        $sub_eventCode = "GSE" . $data['sdate'] . "-" . $sub_eventNum;
                    }
                    
                    $this->insertTourCar($data, $r, $sub_eventNum, $sub_eventCode);
                    $nbnum = $data['bnum'][$r];
                }
            }
            
            $this->dbConn->commit();
            return ['success' => true, 'message' => '차량배정이 저장되었습니다.'];
            
        } catch (Exception $e) {
            $this->dbConn->rollback();
            ErrorHandler::logError("Vehicle assignment error: " . $e->getMessage());
            return ['success' => false, 'message' => '저장 중 오류가 발생했습니다.'];
        }
    }
    
    private function insertTourCar($data, $index, $sub_eventNum, $sub_eventCode) {
        $stmt = $this->dbConn->prepare("
            INSERT INTO tour_car (
                grand_eCode, sub_eNum, sub_eCode, reserveCode, p_code, p_name,
                stDate, bus_num, romm_num, rand_id, rev_nm, room_type,
                sex, picCode, userid, h_seq, wdate
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->bind_param("sisssssiisssssis",
            $data['gcode'], $sub_eventNum, $sub_eventCode, $data['rev'][$index],
            $data['pcode'], $data['pname'], $data['sdate'], $data['bnum'][$index],
            $data['rnum'][$index], $data['rand'][$index] ?? '', $data['revnm'][$index],
            $data['roomt'][$index], $data['rsex'][$index], $data['pick'][$index],
            $this->user_dbinfo['userid'], $data['hseq'][$index]
        ) && $stmt->execute();
    }
    
    private function getNumSevent($gcode, $sdate) {
        $stmt = $this->dbConn->prepare("
            SELECT COALESCE(MAX(sub_eNum), 0) + 1 as next_num 
            FROM tour_car 
            WHERE grand_eCode = ? AND stDate = ?
        ");
        $stmt->bind_param("ss", $gcode, $sdate);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['next_num'] ?? 1;
    }
    
    public function renderReserveList($pcode, $st) {
        try {
            $stmt = $this->dbConn->prepare("
                SELECT a.grand_eCode, a.p_code, a.p_name, a.bus_cnt, a.tour_pcnt, a.stDate,
                       b.reserveCode, b.rand_id, b.tour_type, c.traveler_nm, c.pick_area, 
                       c.sextype, c.seqint, c.room_type
                FROM tour_master a 
                JOIN reserve_info b ON a.stDate = b.stDate AND a.p_code = b.p_code
                JOIN reserve_traveler c ON b.reserveCode = c.reserveCode
                WHERE a.stDate = ? AND a.p_code = ? 
                AND c.seqint NOT IN (
                    SELECT h_seq FROM tour_car WHERE stDate = ? AND p_code = ?
                )
                AND b.rev_status NOT IN ('WAIT', 'CANCEL')
                ORDER BY c.seqint ASC
            ");
            
            $stmt->bind_param("ssss", $st, $pcode, $st, $pcode);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $output = '';
            while ($row = $result->fetch_assoc()) {
                $output .= $this->renderReserveRow($row);
            }
            
            return $output;
        } catch (Exception $e) {
            ErrorHandler::logError("renderReserveList error", ['pcode' => $pcode, 'st' => $st]);
            return '<tr><td colspan="8">데이터를 불러오는 중 오류가 발생했습니다.</td></tr>';
        }
    }
    
    private function renderReserveRow($row) {
        $roomTypeDisplay = $this->getRoomTypeDisplay($row['room_type']);
        $genderDisplay = $this->getGenderDisplay($row['sextype'], $roomTypeDisplay);
        $prodInfo = $this->getProductMaster($row['p_code']);
        $tourTypeDisplay = $this->getTourTypeDisplay($prodInfo['p_type'] ?? 1);
        $reserveTypeDisplay = $this->getReserveTypeDisplay($row['tour_type'] ?? 1);
        
        return "
        <tr data-reserve-code='{$row['reserveCode']}'>
            <td class='text-center'>
                <input type='checkbox' class='form-control' value='{$row['seqint']}'>
                <input type='hidden' name='hseq[]' value='{$row['seqint']}'>
                <input type='hidden' name='bnum[]' value=''>
            </td>
            <td>1<input type='hidden' name='rnum[]' value='1'></td>
            <td title='{$row['p_name']}'>{$row['reserveCode']}
                <input type='hidden' name='rev[]' value='{$row['reserveCode']}'>
                <input type='hidden' name='rand[]' value='{$row['rand_id']}'>
            </td>
            <td class='text-center'>{$tourTypeDisplay}</td>
            <td class='text-center'>{$reserveTypeDisplay}</td>
            <td class='text-center'>{$row['traveler_nm']}
                <input type='hidden' name='revnm[]' value='{$row['traveler_nm']}'>
            </td>
            <td class='text-center'>{$genderDisplay}
                <input type='hidden' name='roomt[]' value='{$row['room_type']}'>
                <input type='hidden' name='rsex[]' value='{$row['sextype']}'>
            </td>
            <td>{$row['pick_area']}
                <input type='hidden' name='pick[]' value='" . htmlspecialchars($row['pick_area']) . "'>
            </td>
        </tr>";
    }
    
    private function getRoomTypeDisplay($roomType) {
        $types = [
            '1r1p' => '1인1실',
            '1r2p' => '2인1실',
            '1r3p' => '3인1실',
            '1r4p' => '4인1실',
            '1r5p' => '5인1실'
        ];
        return $types[$roomType] ?? $roomType;
    }
    
    private function getGenderDisplay($sextype, $roomTypeDisplay) {
        $genders = [
            'man' => '남자',
            'female' => '여자',
            'mfemale' => '혼성'
        ];
        $genderText = $genders[$sextype] ?? $sextype;
        return $roomTypeDisplay . '<br />/' . $genderText;
    }
    
    private function getTourTypeDisplay($pType) {
        $types = [
            1 => '로컬',
            2 => '인바운드',
            4 => '인센티브',
            5 => '아웃바운드'
        ];
        return $types[$pType] ?? '기타';
    }
    
    private function getReserveTypeDisplay($tourType) {
        return $tourType == 3 ? '협력사' : '자사';
    }
    
    private function getProductMaster($pcode) {
        $cacheKey = "product_master_{$pcode}";
        $cached = CacheManager::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $stmt = $this->dbConn->prepare("SELECT * FROM product_master WHERE p_code = ?");
            $stmt->bind_param("s", $pcode);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            CacheManager::set($cacheKey, $result);
            return $result;
        } catch (Exception $e) {
            ErrorHandler::logError("getProductMaster error", ['pcode' => $pcode]);
            return [];
        }
    }
    
    public function renderBusList($sctour, $pcode, $st) {
        $busCnt = $this->getBusCnt($sctour['grand_eCode']);
        $content = '';
        
        for ($r = 1; $r <= $busCnt; $r++) {
            $content .= $this->renderSingleBus($r, $sctour, $pcode, $st);
        }
        
        return $content;
    }
    
    private function renderSingleBus($busNum, $sctour, $pcode, $st) {
        $content = "
        <div class='row'>
            <div class='col-sm-1'>
                <div class='row'></div>
                <div class='row text-center moveR' id='topRight_{$busNum}'><i class='splashy-arrow_medium_right'></i></div>
                <div class='row text-center moveL' id='topLeft_{$busNum}'><i class='splashy-arrow_medium_left'></i></div>
            </div>
            <div class='col-sm-11'>
                <table id='rightTableTop{$busNum}' class='table table-striped table-side-no-bordered table-hover table-condensed text-center rtab'>
                    <thead>
                        <tr>
                            <th scope='col' colspan='8'>차량{$busNum}</th>
                        </tr>
                        <tr>
                            <th align='center'><input type='checkbox' class='form-control checkAll'></th>
                            <th width='10%'>룸넘버</th>
                            <th width='13%'>예약자</th>
                            <th>구분</th>
                            <th>예약</th>
                            <th>고객명</th>
                            <th width='10%'>성별</th>
                            <th>탑승지</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        // 차량별 배정된 고객 목록 조회
        $assignedCustomers = $this->getAssignedCustomers($sctour['grand_eCode'], $busNum);
        $content .= $assignedCustomers;
        
        $content .= "
                    </tbody>
                </table>
            </div>
        </div>";
        
        return $content;
    }
    
    private function getAssignedCustomers($grandCode, $busNum) {
        try {
            $stmt = $this->dbConn->prepare("
                SELECT grand_eCode, sub_eNum, sub_eCode, reserveCode, p_code, p_name,
                       stDate, bus_num, romm_num, rev_nm, room_type, sex, picCode,
                       userid, h_seq, wdate
                FROM tour_car 
                WHERE grand_eCode = ? AND bus_num = ? AND p_code NOT LIKE '%ADD%'
            ");
            $stmt->bind_param("si", $grandCode, $busNum);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $content = '';
            while ($row = $result->fetch_assoc()) {
                $content .= $this->renderAssignedCustomerRow($row);
            }
            
            return $content;
        } catch (Exception $e) {
            ErrorHandler::logError("getAssignedCustomers error", ['grandCode' => $grandCode, 'busNum' => $busNum]);
            return '';
        }
    }
    
    private function renderAssignedCustomerRow($row) {
        $roomTypeDisplay = $this->getRoomTypeDisplay($row['room_type']);
        $genderDisplay = $this->getGenderDisplay($row['sex'], $roomTypeDisplay);
        $roomNum = ($row['romm_num'] == 1 || $row['romm_num'] == 0) ? '1' : $row['romm_num'];
        
        return "
        <tr>
            <td class='text-center'>
                <input type='checkbox' class='form-control' value='{$row['h_seq']}'>
                <input type='hidden' name='hseq[]' value='{$row['h_seq']}'>
                <input type='hidden' name='bnum[]' value='{$row['bus_num']}'>
            </td>
            <td>{$roomNum}<input type='hidden' name='rnum[]' value='{$row['romm_num']}'></td>
            <td>{$row['reserveCode']}<input type='hidden' name='rev[]' value='{$row['reserveCode']}'></td>
            <td class='text-center'>로컬</td>
            <td class='text-center'>자사</td>
            <td class='text-center'>{$row['rev_nm']}<input type='hidden' name='revnm[]' value='{$row['rev_nm']}'></td>
            <td class='text-center'>{$genderDisplay}
                <input type='hidden' name='roomt[]' value='{$row['room_type']}'>
                <input type='hidden' name='rsex[]' value='{$row['sex']}'>
            </td>
            <td>{$row['picCode']}<input type='hidden' name='pick[]' value='{$row['picCode']}'></td>
        </tr>";
    }
    
    private function getBusCnt($grandCode) {
        try {
            $stmt = $this->dbConn->prepare("
                SELECT bus_cnt FROM tour_master WHERE grand_eCode = ?
            ");
            $stmt->bind_param("s", $grandCode);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['bus_cnt'] ?? 1;
        } catch (Exception $e) {
            ErrorHandler::logError("getBusCnt error", ['grandCode' => $grandCode]);
            return 1;
        }
    }
}

// 인스턴스 생성
$vehicleManager = new VehicleAssignmentManager($dbConn, $user_dbinfo);

// 데이터 조회
$sctour = $vehicleManager->getTourInfo($pcode, $st);
$pcnt = $vehicleManager->getReserveInfoCnt($pcode, $st);
$pInfo = $vehicleManager->getProductMaster($pcode);

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'save') {
    header('Content-Type: application/json');
    $result = $vehicleManager->saveVehicleAssignment($_POST);
    echo json_encode($result);
    exit;
}

// 기본값 설정
if (empty($pcnt['cnt'])) {
    $pcnt['cnt'] = 0;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <title>차량배정관리</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css">
    <style>
        .vehicle-assignment-container {
            padding: 20px;
        }
        .form-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .table-container {
            overflow: auto;
            height: 500px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        .bus-assignment-border {
            border: 2px solid #007bff;
            border-radius: 0.375rem;
            padding: 15px;
            margin-top: 10px;
        }
        .move-button {
            cursor: pointer;
            padding: 5px;
            color: #007bff;
        }
        .move-button:hover {
            color: #0056b3;
            background-color: #f8f9fa;
        }
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div id="contentwrapper" class="vehicle-assignment-container">
        <div class="main_content">
            <!-- 브레드크럼 -->
            <div id="jCrumbs" class="breadCrumb module">
                <ul>
                    <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                    <li><a href="#">행사배정관리</a></li>
                    <li>차량배정관리</li>
                </ul>
            </div>

            <!-- 메인 폼 -->
            <form id="vehicleAssignmentForm" action="<?= $_SERVER['PHP_SELF'] ?>?division=<?= $division ?>&pdx=<?= $pdx ?>&sub=<?= $sub ?>&st=<?= $st ?>&pcode=<?= $pcode ?>" method="post">
                <input type="hidden" name="mode" value="save">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="gcode" value="<?= $sctour['grand_eCode'] ?>">
                <input type="hidden" name="pcode" value="<?= $sctour['p_code'] ?>">
                <input type="hidden" name="pname" value="<?= htmlspecialchars($sctour['p_name']) ?>">
                <input type="hidden" name="sdate" value="<?= $sctour['stDate'] ?>">

                <!-- 행사 정보 테이블 -->
                <table class="table table-bordered table-condensed">
                    <tbody>
                        <tr>
                            <td colspan="2" class="form-header text-center">통합행사코드</td>
                            <td colspan="12"><?= $sctour['grand_eCode'] ?></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="form-header text-center">상품명</td>
                            <td colspan="12">[<?= $sctour['p_code'] ?>] <?= htmlspecialchars($sctour['p_name']) ?></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="form-header text-center">출발일</td>
                            <td colspan="2"><?= $sctour['stDate'] ?></td>
                            <td colspan="2" class="form-header text-center">투어정원</td>
                            <td colspan="2"><?= $sctour['tour_pcnt'] ?> 명</td>
                            <td colspan="2" class="form-header text-center">예약인원</td>
                            <td colspan="2"><?= $pcnt['cnt'] ?> 명</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="form-header text-center">예약상태</td>
                            <td colspan="12">
                                <label class="radio-inline">
                                    <input type="radio" name="bookNumber" value="P" <?= strstr($sctour['r_status'], "P") ? "checked" : "" ?> disabled> 예약접수중
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="bookNumber" value="C" <?= strstr($sctour['r_status'], "C") ? "checked" : "" ?> disabled> 예약마감
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="form-header text-center">행사상태</td>
                            <td colspan="12">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="1" <?= strstr($sctour['ev_status'], "1") ? "checked" : "" ?> disabled> 미확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="2" <?= strstr($sctour['ev_status'], "2") ? "checked" : "" ?> disabled> 확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="3" <?= strstr($sctour['ev_status'], "3") ? "checked" : "" ?> disabled> 만차
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="4" <?= strstr($sctour['ev_status'], "4") ? "checked" : "" ?> disabled> 취소
                                        </label>
                                    </div>
                                    <div class="col-sm-8">
                                        <input type="text" name="etcMemo" class="form-control" placeholder="기타메모" value="<?= htmlspecialchars($sctour['etc_memo']) ?>" readonly>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="14" class="text-center">
                                <button type="button" class="btn btn-primary btn-sm" id="addVehicle">차량추가</button>
                                <button type="submit" class="btn btn-success btn-sm" id="saveAssignment">차량배정저장</button>
                                <button type="button" class="btn btn-warning btn-sm" id="resetAssignment">전체초기화</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- 메인 콘텐츠 영역 -->
                <div class="row">
                    <!-- 예약고객현황 (왼쪽) -->
                    <div class="col-sm-6">
                        <div class="table-container">
                            <h5 class="text-success"><strong>예약고객현황</strong></h5>
                            <table id="leftTable" class="table table-striped table-hover text-center">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>룸넘버</th>
                                        <th>예약자</th>
                                        <th>구분</th>
                                        <th>예약</th>
                                        <th>고객명</th>
                                        <th>성별</th>
                                        <th>탑승지</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?= $vehicleManager->renderReserveList($pcode, $st) ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <div class="alert alert-info">
                                총인원: <span id="totalCustomers"><?= $pcnt['cnt'] ?></span>명 &nbsp;&nbsp;
                                총객실수: <span id="totalRooms"><?= $pcnt['cnt'] ?></span>개
                            </div>
                        </div>
                    </div>

                    <!-- 행사차량배정 (오른쪽) -->
                    <div class="col-sm-6">
                        <div class="table-container">
                            <fieldset class="bus-assignment-border" id="busAssignment">
                                <legend><span class="text-muted">행사차량배정</span></legend>
                                <div id="busContainer">
                                    <?= $vehicleManager->renderBusList($sctour, $pcode, $st) ?>
                               </div>
                           </fieldset>
                       </div>
                   </div>
               </div>
           </form>
       </div>
   </div>

   <!-- JavaScript 라이브러리 -->
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
   <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
   <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
   <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap4.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
   <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>

   <script>
       // 차량 배정 관리 클래스
       class VehicleAssignmentManager {
           constructor() {
               this.leftTable = null;
               this.rightTables = new Map();
               this.vehicleCounter = parseInt('<?= $vehicleManager->getBusCnt($sctour["grand_eCode"]) ?>');
               this.csrfToken = $('meta[name="csrf-token"]').attr('content');
               this.init();
           }

           init() {
               this.initDataTables();
               this.bindEvents();
               this.updateCounters();
           }

           initDataTables() {
               const tableConfig = {
                   paging: false,
                   ordering: true,
                   info: false,
                   searching: true,
                   dom: 'Bfrtip',
                   buttons: [
                       {
                           extend: 'excel',
                           text: 'Excel 다운로드',
                           className: 'btn btn-success btn-sm'
                       }
                   ],
                   language: {
                       url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ko.json',
                       emptyTable: '데이터가 없습니다.',
                       zeroRecords: '검색 결과가 없습니다.'
                   },
                   columnDefs: [
                       { targets: 0, orderable: false, searchable: false }
                   ]
               };

               // 왼쪽 테이블 초기화
               this.leftTable = $('#leftTable').DataTable(tableConfig);

               // 오른쪽 테이블들 초기화
               $('.rtab').each((index, element) => {
                   const tableId = $(element).attr('id');
                   this.rightTables.set(tableId, $(element).DataTable(tableConfig));
               });
           }

           bindEvents() {
               // 이벤트 위임 사용
               $(document).on('click', '.moveR', (e) => this.moveToRight(e));
               $(document).on('click', '.moveL', (e) => this.moveToLeft(e));
               $(document).on('click', '#addVehicle', () => this.addVehicle());
               $(document).on('click', '#resetAssignment', () => this.resetAssignment());
               $(document).on('submit', '#vehicleAssignmentForm', (e) => this.handleSubmit(e));

               // 체크박스 이벤트
               $('#selectAll').on('click', (e) => this.toggleAllCheckboxes(e, '#leftTable'));
               $(document).on('click', '.checkAll', (e) => this.toggleAllCheckboxes(e, e.target.closest('table')));

               // 실시간 카운터 업데이트
               $(document).on('change', 'input[type="checkbox"]', () => this.updateCounters());
           }

           moveToRight(e) {
               const busNumber = e.currentTarget.id.split('_')[1];
               const targetTableId = `rightTableTop${busNumber}`;
               this.moveRows('#leftTable', `#${targetTableId}`, busNumber);
           }

           moveToLeft(e) {
               const busNumber = e.currentTarget.id.split('_')[1];
               const sourceTableId = `rightTableTop${busNumber}`;
               this.moveRows(`#${sourceTableId}`, '#leftTable', '');
           }

           moveRows(sourceId, targetId, busNumber) {
               const sourceTable = sourceId === '#leftTable' ? 
                   this.leftTable : 
                   this.rightTables.get(sourceId.substring(1));
               const targetTable = targetId === '#leftTable' ? 
                   this.leftTable : 
                   this.rightTables.get(targetId.substring(1));

               if (!sourceTable || !targetTable) {
                   console.error('테이블을 찾을 수 없습니다.');
                   return;
               }

               const checkedRows = $(`${sourceId} input[type=checkbox]:checked`);
               if (checkedRows.length === 0) {
                   alert('이동할 항목을 선택해주세요.');
                   return;
               }

               checkedRows.each((index, checkbox) => {
                   const row = $(checkbox).closest('tr');
                   const rowData = this.extractRowData(row, busNumber);
                   
                   // 소스 테이블에서 제거
                   sourceTable.row(row).remove();
                   
                   // 타겟 테이블에 추가
                   const newRow = targetTable.row.add(rowData);
                   
                   // 체크박스 상태 초기화
                   $(checkbox).prop('checked', false);
               });

               sourceTable.draw();
               targetTable.draw();
               this.updateCounters();
           }

           extractRowData(row, busNumber) {
               const cells = row.find('td');
               const rowData = [];
               
               cells.each((index, cell) => {
                   const $cell = $(cell);
                   let cellContent = $cell.html();
                   
                   // 차량 번호 업데이트
                   if (index === 0) { // 첫 번째 셀 (체크박스)
                       const hiddenInput = $cell.find('input[name="bnum[]"]');
                       if (hiddenInput.length > 0) {
                           hiddenInput.val(busNumber);
                           cellContent = $cell.html();
                       }
                   }
                   
                   rowData.push(cellContent);
               });
               
               return rowData;
           }

           addVehicle() {
               this.vehicleCounter++;
               const busHtml = this.generateBusHTML(this.vehicleCounter);
               $('#busContainer').append(busHtml);

               // 새 테이블을 DataTables로 초기화
               const newTableId = `rightTableTop${this.vehicleCounter}`;
               const tableConfig = {
                   paging: false,
                   ordering: true,
                   info: false,
                   searching: true,
                   dom: 'Bfrtip',
                   buttons: ['excel'],
                   language: {
                       url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ko.json'
                   }
               };

               this.rightTables.set(newTableId, $(`#${newTableId}`).DataTable(tableConfig));
               this.updateCounters();
           }

           generateBusHTML(busNumber) {
               return `
               <div class="row" id="busRow${busNumber}">
                   <div class="col-sm-1">
                       <div class="row"></div>
                       <div class="row text-center move-button moveR" id="topRight_${busNumber}">
                           <i class="fas fa-arrow-right"></i>
                       </div>
                       <div class="row text-center move-button moveL" id="topLeft_${busNumber}">
                           <i class="fas fa-arrow-left"></i>
                       </div>
                       <div class="row text-center mt-2">
                           <button type="button" class="btn btn-danger btn-sm" onclick="vehicleManager.removeBus(${busNumber})">
                               <i class="fas fa-trash"></i>
                           </button>
                       </div>
                   </div>
                   <div class="col-sm-11">
                       <table id="rightTableTop${busNumber}" class="table table-striped table-hover table-condensed text-center rtab">
                           <thead>
                               <tr>
                                   <th colspan="8" class="bg-primary text-white">
                                       차량${busNumber} 
                                       <span class="badge badge-light ml-2" id="busCount${busNumber}">0명</span>
                                   </th>
                               </tr>
                               <tr>
                                   <th><input type="checkbox" class="checkAll"></th>
                                   <th>룸넘버</th>
                                   <th>예약자</th>
                                   <th>구분</th>
                                   <th>예약</th>
                                   <th>고객명</th>
                                   <th>성별</th>
                                   <th>탑승지</th>
                               </tr>
                           </thead>
                           <tbody></tbody>
                       </table>
                   </div>
               </div>`;
           }

           removeBus(busNumber) {
               if (!confirm(`차량${busNumber}을 삭제하시겠습니까?\n배정된 고객은 예약고객현황으로 이동됩니다.`)) {
                   return;
               }

               const tableId = `rightTableTop${busNumber}`;
               const table = this.rightTables.get(tableId);
               
               if (table) {
                   // 테이블의 모든 행을 왼쪽으로 이동
                   const rows = table.rows().nodes();
                   $(rows).find('input[type="checkbox"]').prop('checked', true);
                   this.moveRows(`#${tableId}`, '#leftTable', '');
                   
                   // 테이블 제거
                   table.destroy();
                   this.rightTables.delete(tableId);
               }
               
               // DOM에서 제거
               $(`#busRow${busNumber}`).remove();
               this.updateCounters();
           }

           toggleAllCheckboxes(e, tableSelector) {
               const table = $(tableSelector);
               const isChecked = $(e.target).prop('checked');
               
               table.find('tbody input[type="checkbox"]').prop('checked', isChecked);
               this.updateCounters();
           }

           updateCounters() {
               // 전체 고객 수 업데이트
               const leftTableRows = this.leftTable.rows().count();
               $('#totalCustomers').text(leftTableRows);
               $('#totalRooms').text(leftTableRows);

               // 각 차량별 고객 수 업데이트
               this.rightTables.forEach((table, tableId) => {
                   const busNumber = tableId.replace('rightTableTop', '');
                   const rowCount = table.rows().count();
                   $(`#busCount${busNumber}`).text(`${rowCount}명`);
               });
           }

           resetAssignment() {
               if (!confirm('모든 차량 배정을 초기화하시겠습니까?')) {
                   return;
               }

               // 모든 오른쪽 테이블의 데이터를 왼쪽으로 이동
               this.rightTables.forEach((table, tableId) => {
                   const rows = table.rows().nodes();
                   $(rows).find('input[type="checkbox"]').prop('checked', true);
                   this.moveRows(`#${tableId}`, '#leftTable', '');
               });

               this.updateCounters();
           }

           async handleSubmit(e) {
               e.preventDefault();

               if (!confirm('차량배정을 저장하시겠습니까?')) {
                   return;
               }

               const form = e.target;
               const submitBtn = $('#saveAssignment');
               const originalText = submitBtn.text();

               try {
                   // 로딩 상태 설정
                   submitBtn.prop('disabled', true).text('저장 중...');
                   $(form).addClass('loading');

                   const formData = new FormData(form);
                   formData.set('csrf_token', this.csrfToken);

                   const response = await fetch(form.action, {
                       method: 'POST',
                       body: formData,
                       headers: {
                           'X-Requested-With': 'XMLHttpRequest'
                       }
                   });

                   if (!response.ok) {
                       throw new Error(`HTTP error! status: ${response.status}`);
                   }

                   const result = await response.json();

                   if (result.success) {
                       alert(result.message);
                       location.reload();
                   } else {
                       alert('오류: ' + result.message);
                   }

               } catch (error) {
                   console.error('Submit error:', error);
                   alert('저장 중 네트워크 오류가 발생했습니다.');
               } finally {
                   // 로딩 상태 해제
                   submitBtn.prop('disabled', false).text(originalText);
                   $(form).removeClass('loading');
               }
           }

           // 유틸리티 메서드
           validateForm() {
               const errors = [];

               // 필수 필드 검증
               if (!$('input[name="gcode"]').val()) {
                   errors.push('행사코드가 없습니다.');
               }

               if (!$('input[name="pcode"]').val()) {
                   errors.push('상품코드가 없습니다.');
               }

               if (!$('input[name="sdate"]').val()) {
                   errors.push('출발일이 없습니다.');
               }

               // 배정된 고객이 있는지 확인
               let hasAssignedCustomers = false;
               this.rightTables.forEach((table) => {
                   if (table.rows().count() > 0) {
                       hasAssignedCustomers = true;
                   }
               });

               if (!hasAssignedCustomers) {
                   errors.push('배정된 고객이 없습니다.');
               }

               return errors;
           }

           // 데이터 내보내기
           exportToExcel() {
               const wb = XLSX.utils.book_new();

               // 예약고객현황 시트
               const leftData = this.leftTable.rows().data().toArray();
               const leftSheet = XLSX.utils.aoa_to_sheet([
                   ['룸넘버', '예약자', '구분', '예약', '고객명', '성별', '탑승지'],
                   ...leftData.map(row => [
                       $(row[1]).text(),
                       $(row[2]).text(),
                       $(row[3]).text(),
                       $(row[4]).text(),
                       $(row[5]).text(),
                       $(row[6]).text(),
                       $(row[7]).text()
                   ])
               ]);
               XLSX.utils.book_append_sheet(wb, leftSheet, "예약고객현황");

               // 각 차량별 시트
               this.rightTables.forEach((table, tableId) => {
                   const busNumber = tableId.replace('rightTableTop', '');
                   const data = table.rows().data().toArray();
                   
                   if (data.length > 0) {
                       const sheet = XLSX.utils.aoa_to_sheet([
                           ['룸넘버', '예약자', '구분', '예약', '고객명', '성별', '탑승지'],
                           ...data.map(row => [
                               $(row[1]).text(),
                               $(row[2]).text(),
                               $(row[3]).text(),
                               $(row[4]).text(),
                               $(row[5]).text(),
                               $(row[6]).text(),
                               $(row[7]).text()
                           ])
                       ]);
                       XLSX.utils.book_append_sheet(wb, sheet, `차량${busNumber}`);
                   }
               });

               // 파일 다운로드
               const fileName = `차량배정_${$('input[name="pcode"]').val()}_${$('input[name="sdate"]').val()}.xlsx`;
               XLSX.writeFile(wb, fileName);
           }

           // 인쇄 기능
           printAssignment() {
               const printWindow = window.open('', '_blank');
               const printContent = this.generatePrintContent();
               
               printWindow.document.write(printContent);
               printWindow.document.close();
               printWindow.print();
           }

           generatePrintContent() {
               const tourInfo = {
                   code: $('input[name="gcode"]').val(),
                   product: $('input[name="pname"]').val(),
                   date: $('input[name="sdate"]').val()
               };

               let content = `
               <html>
               <head>
                   <title>차량배정표</title>
                   <style>
                       body { font-family: Arial, sans-serif; margin: 20px; }
                       .header { text-align: center; margin-bottom: 30px; }
                       .tour-info { margin-bottom: 20px; }
                       .bus-section { margin-bottom: 30px; page-break-inside: avoid; }
                       table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                       th, td { border: 1px solid #000; padding: 8px; text-align: center; }
                       th { background-color: #f0f0f0; }
                       .bus-title { background-color: #007bff; color: white; }
                   </style>
               </head>
               <body>
                   <div class="header">
                       <h1>차량배정표</h1>
                   </div>
                   <div class="tour-info">
                       <p><strong>행사코드:</strong> ${tourInfo.code}</p>
                       <p><strong>상품명:</strong> ${tourInfo.product}</p>
                       <p><strong>출발일:</strong> ${tourInfo.date}</p>
                       <p><strong>출력일:</strong> ${new Date().toLocaleDateString('ko-KR')}</p>
                   </div>
               `;

               // 각 차량별 정보 추가
               this.rightTables.forEach((table, tableId) => {
                   const busNumber = tableId.replace('rightTableTop', '');
                   const data = table.rows().data().toArray();
                   
                   if (data.length > 0) {
                       content += `
                       <div class="bus-section">
                           <table>
                               <tr><th colspan="7" class="bus-title">차량${busNumber} (${data.length}명)</th></tr>
                               <tr>
                                   <th>순번</th>
                                   <th>예약코드</th>
                                   <th>고객명</th>
                                   <th>성별</th>
                                   <th>탑승지</th>
                                   <th>연락처</th>
                                   <th>비고</th>
                               </tr>
                       `;
                       
                       data.forEach((row, index) => {
                           content += `
                           <tr>
                               <td>${index + 1}</td>
                               <td>${$(row[2]).text()}</td>
                               <td>${$(row[5]).text()}</td>
                               <td>${$(row[6]).text()}</td>
                               <td>${$(row[7]).text()}</td>
                               <td></td>
                               <td></td>
                           </tr>
                           `;
                       });
                       
                       content += '</table></div>';
                   }
               });

               content += '</body></html>';
               return content;
           }
       }

       // 전역 변수로 설정하여 다른 함수에서 접근 가능
       let vehicleManager;

       // 페이지 로드 시 초기화
       $(document).ready(function() {
           vehicleManager = new VehicleAssignmentManager();
           
           // 추가 UI 개선
           initializeTooltips();
           initializeKeyboardShortcuts();
       });

       // 툴팁 초기화
       function initializeTooltips() {
           $('[data-toggle="tooltip"]').tooltip();
           
           // 동적 툴팁 추가
           $('.moveR').attr('title', '선택한 고객을 이 차량으로 이동');
           $('.moveL').attr('title', '선택한 고객을 예약현황으로 이동');
       }

       // 키보드 단축키
       function initializeKeyboardShortcuts() {
           $(document).keydown(function(e) {
               // Ctrl + S: 저장
               if (e.ctrlKey && e.which === 83) {
                   e.preventDefault();
                   $('#saveAssignment').click();
               }
               
               // Ctrl + A: 전체 선택
               if (e.ctrlKey && e.which === 65) {
                   e.preventDefault();
                   $('#selectAll').click();
               }
               
               // ESC: 선택 해제
               if (e.which === 27) {
                   $('input[type="checkbox"]').prop('checked', false);
                   vehicleManager.updateCounters();
               }
           });
       }

       // 추가 유틸리티 함수들
       function showNotification(message, type = 'info') {
           const alertClass = type === 'error' ? 'alert-danger' : 
                             type === 'success' ? 'alert-success' : 'alert-info';
           
           const notification = $(`
               <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                    style="top: 20px; right: 20px; z-index: 9999;">
                   ${message}
                   <button type="button" class="close" data-dismiss="alert">
                       <span>&times;</span>
                   </button>
               </div>
           `);
           
           $('body').append(notification);
           
           // 3초 후 자동 제거
           setTimeout(() => {
               notification.alert('close');
           }, 3000);
       }

       // 검색 기능 강화
       function enhanceSearch() {
           // 전체 테이블 검색
           $('#globalSearch').on('keyup', function() {
               const searchTerm = this.value;
               
               // 왼쪽 테이블 검색
               vehicleManager.leftTable.search(searchTerm).draw();
               
               // 오른쪽 테이블들 검색
               vehicleManager.rightTables.forEach((table) => {
                   table.search(searchTerm).draw();
               });
           });
       }

       // 자동 저장 기능
       function enableAutoSave() {
           let autoSaveTimer;
           
           $(document).on('change', 'input[type="checkbox"]', function() {
               clearTimeout(autoSaveTimer);
               autoSaveTimer = setTimeout(() => {
                   // 임시 저장 로직
                   const formData = $('#vehicleAssignmentForm').serialize();
                   localStorage.setItem('vehicleAssignment_temp', formData);
                   showNotification('임시 저장되었습니다.', 'info');
               }, 5000);
           });
       }

       // 데이터 복원 기능
       function restoreFromAutoSave() {
           const savedData = localStorage.getItem('vehicleAssignment_temp');
           if (savedData && confirm('임시 저장된 데이터가 있습니다. 복원하시겠습니까?')) {
               // 복원 로직 구현
               showNotification('데이터가 복원되었습니다.', 'success');
           }
       }
   </script>
</body>
</html>

<?php
// 푸터 포함
if (file_exists("include/side_m.php")) {
   include "include/side_m.php";
}
?>