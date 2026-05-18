<?php
// 1. 에러 설정 (PHP 5.6 환경 호환)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

include "include/inc_base.php"; 
// ※ 중요: inc_base.php 파일에서 mysql_connect() 및 mysql_select_db()가 실행되어야 합니다.

/* =================================================================================
 * [Backend] 1. 파라미터 및 날짜 설정
 * ================================================================================= */

if (empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo "<script>alert('관리자 로그인이 필요합니다.'); window.close();</script>";
    exit;
}

// 1. 파라미터 수신 (PHP 5.6 호환: 삼항 연산자 사용)
$viewYear  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$viewMonth = isset($_GET['month']) ? $_GET['month'] : date('m');

// 2. 날짜 계산
$sDate = date("{$viewYear}-{$viewMonth}-01"); 
$eDate = date("Y-m-t", strtotime($sDate));    

// 3. 네비게이션
$today    = date('Y-m-d');
$prevDate = strtotime("-1 month", strtotime($sDate));
$nextDate = strtotime("+1 month", strtotime($sDate));

// 4. 날짜 배열 생성
$dateHeaders = array();
$dStart = new DateTime($sDate);
$dEnd   = new DateTime($eDate);
$dEnd->modify('+1 day');
$interval = new DateInterval('P1D');
$period   = new DatePeriod($dStart, $interval, $dEnd);

foreach($period as $dt) {
    $dateHeaders[] = $dt->format('Y-m-d');
}

/* =================================================================================
 * [Backend] 2. 데이터 처리 (등록/수정/삭제) - mysql_* 적용
 * ================================================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode'])) {
    
    // mysql_real_escape_string 사용
    $grand_eCode = isset($_POST['grand_eCode']) ? mysql_real_escape_string($_POST['grand_eCode']) : '';
    $uid         = isset($_COOKIE['MEMLOGIN_ID']) ? mysql_real_escape_string($_COOKIE['MEMLOGIN_ID']) : 'admin';

    // A. 배정 등록 및 수정
    if ($_POST['mode'] === 'assign') {
        $seq_no     = isset($_POST['seq_no']) ? mysql_real_escape_string($_POST['seq_no']) : '';
        $bus_num    = mysql_real_escape_string($_POST['bus_num']); 
        $start_date = mysql_real_escape_string($_POST['stDate']);
        $end_date   = mysql_real_escape_string($_POST['edDate']);
        $memo       = mysql_real_escape_string($_POST['memo']);
        $color_code = isset($_POST['color_code']) ? mysql_real_escape_string($_POST['color_code']) : '#3498db';

        // 차량 지정 필수 체크
        if (empty($bus_num)) {
            echo "<script>alert('차량은 반드시 지정해야 합니다.'); history.back();</script>";
            exit;
        }

        if ($seq_no) {
            // [수정] 기존 데이터 업데이트
            $qry = "UPDATE tour_car_block 
                    SET grand_eCode = '$grand_eCode',
                        bus_num = '$bus_num', 
                        stDate = '$start_date', 
                        edDate = '$end_date', 
                        memo = '$memo', 
                        color_code = '$color_code', 
                        wdate = NOW() 
                    WHERE seq_no = '$seq_no'";
        } else {
            // [신규] 데이터 삽입
            $qry = "INSERT INTO tour_car_block (grand_eCode, bus_num, stDate, edDate, memo, color_code, userid, wdate) 
                    VALUES ('$grand_eCode', '$bus_num', '$start_date', '$end_date', '$memo', '$color_code', '$uid', NOW())";
        }
        
        if (!mysql_query($qry)) {
            echo "<script>alert('DB Error: " . mysql_error() . "'); history.back();</script>";
            exit;
        }
    }
    // B. 배정 삭제
    else if ($_POST['mode'] === 'delete') {
        $seq_no = mysql_real_escape_string($_POST['seq_no']);
        $qry = "DELETE FROM tour_car_block WHERE seq_no = '$seq_no'";
        
        if(mysql_query($qry)) {
            echo "<script>alert('정상적으로 삭제되었습니다.');</script>";
        } else {
            echo "<script>alert('삭제 실패: " . mysql_error() . "'); history.back();</script>";
            exit;
        }
    }

    $qs = http_build_query(array('year' => $viewYear, 'month' => $viewMonth));
    echo "<script>location.href='{$_SERVER['PHP_SELF']}?{$qs}';</script>";
    exit;
}

/* =================================================================================
 * [Backend] 3. 데이터 조회 - mysql_* 적용
 * ================================================================================= */

// 3-1. 차량 목록
$busList = array();
$rst_bus = mysql_query("SELECT * FROM bus_list ORDER BY bus_team, bus_id");
if ($rst_bus) {
    while($row = mysql_fetch_assoc($rst_bus)) {
        $busList[] = $row;
    }
}

// 3-2. 배정 데이터 조회 (실제 배정된 건만 조회)
$scheduleMap = array(); 

$qry_assign = "SELECT B.*, P.p_name, P.p_day, G.guide_id, G.sguide_id,
               (SELECT count(*) FROM tour_car WHERE grand_eCode = B.grand_eCode) as locked_cnt
               FROM tour_car_block B
               LEFT JOIN tour_master M ON B.grand_eCode = M.grand_eCode
               LEFT JOIN product_master P ON M.p_code = P.p_code
               LEFT JOIN tour_guide G ON B.grand_eCode = G.grand_eCode 
                                     AND B.bus_num = G.c_id 
               WHERE (B.stDate <= '$eDate' AND B.edDate >= '$sDate')
               ORDER BY B.stDate ASC";

$rst_assign = mysql_query($qry_assign);
if ($rst_assign) {
    while($row = mysql_fetch_assoc($rst_assign)) {
        
        $guideNameStr = "";
        if (!empty($row['guide_id'])) {
            $memInfo = getinfo_dbMember($row['guide_id']);
            $gName = isset($memInfo['kor_name']) ? $memInfo['kor_name'] : $row['guide_id'];
            $guideNameStr .= $gName;
        }
        if (!empty($row['sguide_id'])) {
            $memInfoSub = getinfo_dbMember($row['sguide_id']);
            $sName = isset($memInfoSub['kor_name']) ? $memInfoSub['kor_name'] : $row['sguide_id'];
            if($guideNameStr !== "") $guideNameStr .= ", " . $sName;
            else $guideNameStr .= $sName;
        }
        $row['guide_name_all'] = $guideNameStr;
        
        // 상품명이 없으면(상품 미지정 상태) 메모를 제목으로 사용
        if (empty($row['p_name'])) {
            $row['p_name'] = $row['memo'] ? $row['memo'] : "(상품 미지정)";
            $row['is_unassigned_product'] = true;
        } else {
            $row['is_unassigned_product'] = false;
        }

        $bid = $row['bus_num'];
        
        $displayStart = ($row['stDate'] < $sDate) ? $sDate : $row['stDate'];
        $displayEnd   = ($row['edDate'] > $eDate) ? $eDate : $row['edDate'];
        
        $d1 = new DateTime($displayStart);
        $d2 = new DateTime($displayEnd);
        $diff = $d1->diff($d2);
        $colspan = $diff->days + 1; 
        
        $scheduleMap[$bid][$displayStart][] = array(
            'colspan' => $colspan,
            'info'    => $row
        );
    }
}

// 3-3. 대기 목록 조회 (상품은 존재하나 차량이 배정되지 않은 건)
$unassignedListData = array();
$qry_list = "SELECT M.grand_eCode, M.stDate, P.p_name, IFNULL(P.p_day, 1) as p_day, 
                DATE_ADD(M.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) as edDate,
                (SELECT count(*) FROM tour_car_block WHERE grand_eCode = M.grand_eCode) as assign_cnt
             FROM tour_master M
             LEFT JOIN product_master P ON M.p_code = P.p_code
             LEFT JOIN reserve_info R ON M.grand_eCode = R.reserveCode
             WHERE 
                 (M.stDate <= '$eDate' AND DATE_ADD(M.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) >= '$sDate')
             AND M.stDate >= '$today'
             AND (R.rev_status IS NULL OR R.rev_status != 'CANCEL') 
             GROUP BY M.grand_eCode
             HAVING assign_cnt = 0 
             ORDER BY M.stDate ASC";

$rst_list = mysql_query($qry_list);
if ($rst_list) {
    while($row = mysql_fetch_assoc($rst_list)) {
        $unassignedListData[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>차량 배정 스케줄러</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background: #f5f6fa; margin: 0; padding: 20px; font-size: 12px; color: #2f3640; }
        
        .control-bar { background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .month-control { display: flex; align-items: center; gap: 20px; }
        .month-title { font-size: 20px; font-weight: 700; color: #2c3e50; }
        .btn-nav { background: #ecf0f1; border: 1px solid #bdc3c7; padding: 6px 12px; border-radius: 4px; text-decoration: none; color: #333; font-weight: 600; }
        .btn-today { background: #3498db; color: #fff; border-color: #2980b9; }
        .btn-manual { background: #27ae60; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; cursor: pointer; border: 1px solid #2ecc71; }

        .unassigned-zone { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #1abc9c; }
        
        .card-container { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap; 
            padding-bottom: 5px;
            align-items: flex-start; 
        }
        
        .card { 
            background: #e0f2f1; border: 1px solid #b2dfdb; padding: 8px; border-radius: 4px; 
            min-width: 150px; width: 150px; 
            flex: 0 0 auto;
            cursor: pointer; transition: 0.2s; position: relative;
            height: auto; 
            display: flex; flex-direction: column; justify-content: space-between;
            margin-bottom: 10px; 
        }
        .card:hover { border-color: #009688; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transform: translateY(-2px); }
        
        .card-title { 
            font-weight: bold; 
            margin-bottom: 5px; 
            white-space: normal;       
            word-break: keep-all;      
            line-height: 1.35;         
            font-size: 11px;
        }
        .card-date { font-size: 11px; color: #555; margin-top: 5px; text-align: right; }

        .grid-wrapper { background: #fff; border-radius: 8px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table.scheduler-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1400px; table-layout: fixed; }
        th, td { border: 1px solid #dfe6e9; border-top: 0; border-left: 0; padding: 0; text-align: center; vertical-align: top; }
        
        thead th { background: #2d3436; color: #fff; position: sticky; top: 0; z-index: 10; padding: 8px 0; height: 30px; }
        .fixed-col { position: sticky; left: 0; z-index: 20; background: #dfe6e9; color: #2d3436; width: 80px; min-width: 80px; font-weight: 600; border-right: 2px solid #b2bec3; }
        thead th.fixed-col { z-index: 30; background: #636e72; color: #fff; }

        .merged-cell {
            color: white; cursor: pointer; border-radius: 4px;
            box-sizing: border-box; padding: 4px 6px; margin: 2px; 
            min-height: 26px; line-height: 1.3;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            box-shadow: 0 1px 2px rgba(0,0,0,0.15); 
            position: relative; z-index: 5; font-size: 11px;
            display: block; text-align: left;
        }
        .merged-cell:hover { opacity: 0.9; transform: translateY(-1px); z-index: 10; box-shadow: 0 3px 6px rgba(0,0,0,0.2); }
        
        .merged-cell.no-product {
            background-image: linear-gradient(45deg, rgba(255,255,255,0.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.15) 75%, transparent 75%, transparent);
            background-size: 10px 10px;
            border: 1px dashed rgba(255,255,255,0.5);
        }

        .js-click-modal { cursor: pointer; }
        .empty-cell { background: #fff; }
        .weekend-cell { background: #f9f9f9; }

        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 5px; width: 400px; }
        .form-row { margin-bottom: 10px; }
        .form-row label { display: block; font-size: 11px; color: #7f8c8d; margin-bottom: 3px; }
        .form-row input, .form-row select { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; }
        
        .color-picker-wrapper { display: flex; align-items: center; gap: 10px; }
        input[type="color"] { border: none; width: 40px; height: 30px; cursor: pointer; padding: 0; background: none; }

        .btn { padding: 6px 12px; border: none; border-radius: 3px; color: white; cursor: pointer; }
    </style>
</head>
<body>

<div class="control-bar">
    <div class="month-control">
        <a href="?year=<?= date('Y', $prevDate) ?>&month=<?= date('m', $prevDate) ?>" class="btn-nav">&lt; 이전달</a>
        <div class="month-title"><?= $viewYear ?>년 <?= $viewMonth ?>월</div>
        <a href="?year=<?= date('Y', $nextDate) ?>&month=<?= date('m', $nextDate) ?>" class="btn-nav">다음달 &gt;</a>
        <a href="?year=<?= date('Y') ?>&month=<?= date('m') ?>" class="btn-nav btn-today">오늘</a>
    </div>
    <button type="button" class="btn-manual" onclick="openModalManual()">[+] 스케줄 수동 등록</button>
</div>

<?php if(!empty($unassignedListData)): ?>
<div class="unassigned-zone">
    <div style="font-weight:bold; margin-bottom:5px;">📋 배정 대기 상품 목록</div>
    <div class="card-container">
        <?php foreach($unassignedListData as $item): 
            $stTxt = date('m/d', strtotime($item['stDate']));
            $title = "[{$stTxt}] " . $item['p_name'];
            
            if (defined('JSON_UNESCAPED_UNICODE')) {
                $jsonRaw = json_encode($item, JSON_UNESCAPED_UNICODE);
            } else {
                $jsonRaw = json_encode($item);
            }
            if($jsonRaw === false) $jsonRaw = '{}';
            $base64Str = base64_encode($jsonRaw);
        ?>
        <div class="card js-click-modal" 
             data-mode="new"
             data-b64="<?=$base64Str?>">
            <div class="card-title" title="<?= htmlspecialchars($title) ?>"><?= $title ?></div>
            <div class="card-date"><?= $item['stDate'] ?> (<?= $item['p_day'] ?>일)</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="grid-wrapper">
    <table class="scheduler-table">
        <colgroup>
            <col style="width: 80px;"> 
            <?php foreach($dateHeaders as $dt): ?>
            <col style="width: 40px;">
            <?php endforeach; ?>
        </colgroup>
        <thead>
            <tr>
                <th class="fixed-col">차량</th>
                <?php foreach($dateHeaders as $dt): 
                    $w = date('w', strtotime($dt));
                    $color = ($w==0) ? '#ff7675' : (($w==6) ? '#74b9ff' : '#fff');
                ?>
                <th style="color:<?= $color ?>;">
                    <?= date('d', strtotime($dt)) ?><br>
                    <span style="font-size:10px; font-weight:normal;"><?= date('D', strtotime($dt)) ?></span>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($busList as $bus): 
                $skipCount = 0; 
            ?>
            <tr>
                <td class="fixed-col" style="padding: 5px; vertical-align:middle;">
                    <div style="font-weight:bold;"><?= $bus['bus_id'] ?></div>
                    <div style="font-size:10px; color:#636e72;"><?= $bus['bus_number'] ?></div>
                </td>
                <?php foreach($dateHeaders as $dt): 
                    if ($skipCount > 0) {
                        $skipCount--;
                        continue; 
                    }

                    $events = isset($scheduleMap[$bus['bus_id']][$dt]) ? $scheduleMap[$bus['bus_id']][$dt] : null;

                    if ($events) {
                        $maxColspan = 1;
                        foreach($events as $ev) {
                            if($ev['colspan'] > $maxColspan) $maxColspan = $ev['colspan'];
                        }
                        $skipCount = $maxColspan - 1; 
                ?>
                    <td colspan="<?= $maxColspan ?>" style="padding: 1px;">
                        <?php 
                        foreach($events as $cell):
                            $info     = $cell['info'];
                            $isLocked = ($info['locked_cnt'] > 0);
                            $cls      = $isLocked ? 'locked' : '';
                            if ($info['is_unassigned_product']) {
                                $cls .= ' no-product';
                            }

                            $originStart = date('m/d', strtotime($info['stDate']));
                            $txt      = "[{$originStart}] " . $info['p_name'];
                            if($info['guide_name_all']) $txt .= " ({$info['guide_name_all']})";
                            
                            $bg_color = !empty($info['color_code']) ? $info['color_code'] : '#3498db';
                            
                            if (defined('JSON_UNESCAPED_UNICODE')) {
                                $jsonRaw = json_encode($info, JSON_UNESCAPED_UNICODE);
                            } else {
                                $jsonRaw = json_encode($info);
                            }
                            if($jsonRaw === false) $jsonRaw = '{}';
                            $base64Str = base64_encode($jsonRaw);
                        ?>
                        <div class="merged-cell <?= $cls ?> js-click-modal"
                             style="background-color: <?= $bg_color ?>;"
                             title="<?= htmlspecialchars($txt) ?> (<?= $info['stDate'] ?> ~ <?= $info['edDate'] ?>)"
                             data-mode="edit"
                             data-b64="<?=$base64Str?>">
                            <?= $txt ?>
                        </div>
                        <?php endforeach; ?>
                    </td>
                <?php 
                    } else { 
                        $w = date('w', strtotime($dt));
                        $bg = ($w==0 || $w==6) ? 'weekend-cell' : 'empty-cell';
                ?>
                    <td class="<?= $bg ?>"></td>
                <?php } ?>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="assignModal" class="modal-overlay">
    <div class="modal-content">
        <h3 id="modal_title" style="margin-top:0;">배정 정보</h3>
        <form method="POST" id="modal_form">
            <input type="hidden" name="mode" id="modal_mode">
            <input type="hidden" name="seq_no" id="modal_seq">
            <input type="hidden" name="grand_eCode" id="modal_ecode_hidden"> 
            <input type="hidden" name="p_day" id="modal_p_day" value="1">

            <div class="form-row">
                <label>상품명 (연결 대상)</label>
                <input type="text" id="modal_pname_readonly" readonly style="background:#eee; display:none;">
                
                <select id="modal_pname_select" style="display:none;" onchange="updateEcode(this)">
                    <option value="" data-sdate="" data-days="">[상품 미지정] - 메모로 관리</option>
                    <?php foreach($unassignedListData as $uItem): ?>
                        <option value="<?= $uItem['grand_eCode'] ?>" 
                                data-sdate="<?= $uItem['stDate'] ?>" 
                                data-days="<?= $uItem['p_day'] ?>">
                            [<?= $uItem['stDate'] ?>] <?= $uItem['p_name'] ?> (<?= $uItem['p_day'] ?>일)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <label>스케줄 색상</label>
                <div class="color-picker-wrapper">
                    <input type="color" name="color_code" id="modal_color" value="#3498db">
                    <span style="font-size:11px; color:#999;">(클릭하여 색상 변경)</span>
                </div>
            </div>

            <div class="form-row">
                <label>차량 선택 (필수)</label>
                <select name="bus_num" id="modal_select_bus" required>
                    <option value="">-- 차량 선택 --</option>
                    <?php foreach($busList as $b): ?>
                    <option value="<?= $b['bus_id'] ?>"><?= $b['bus_id'] ?> (<?= $b['bus_number'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>기간 (자동 계산됨)</label>
                <div style="display:flex; gap:5px;">
                    <input type="date" name="stDate" id="modal_sdate" required onchange="calcEndDate()">
                    <input type="date" name="edDate" id="modal_edate" required>
                </div>
            </div>
            <div class="form-row">
                <label>메모</label>
                <input type="text" name="memo" id="modal_memo" placeholder="상품 미지정 시 표시될 내용 입력">
            </div>
            
            <div style="text-align:right; margin-top:15px;">
                <button type="button" class="btn" style="background:#95a5a6;" onclick="closeModal()">닫기</button>
                <button type="submit" class="btn" style="background:#3498db;" id="btn_save">저장</button>
                <button type="button" class="btn" style="background:#e74c3c;" id="btn_del" onclick="delAssign()">삭제</button>
            </div>
            <div id="lock_msg" style="color:red; font-size:11px; text-align:center; margin-top:5px; display:none;">
                ※ 수정 권한이 없습니다.
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        var target = e.target.closest('.js-click-modal');
        if (target) {
            var mode = target.getAttribute('data-mode');
            var b64  = target.getAttribute('data-b64');
            openModalFromData(mode, b64);
        }
    });
});

function filterProductOptions(targetDate) {
    var select = document.getElementById('modal_pname_select');
    var options = select.options;
    
    if (!targetDate) return;

    for (var i = 0; i < options.length; i++) {
        var opt = options['i'];
        if (opt.value === "") {
            opt.style.display = 'block';
            continue;
        }
        var optDate = opt.getAttribute('data-sdate');
        if (optDate === targetDate) {
            opt.style.display = 'block';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
        }
    }
    
    var currentSelected = select.options[select.selectedIndex];
    if (currentSelected.style.display === 'none' || currentSelected.disabled) {
        select.value = "";
        document.getElementById('modal_ecode_hidden').value = "";
    }
}

// [추가] 기간 자동 계산 로직 (시작일 + 박수 - 1)
function calcEndDate() {
    var sDateVal = document.getElementById('modal_sdate').value;
    var daysVal = document.getElementById('modal_p_day').value; // hidden field에서 가져옴

    if (sDateVal && daysVal && parseInt(daysVal) > 0) {
        var dateObj = new Date(sDateVal);
        var days = parseInt(daysVal);
        
        // 시작일 포함이므로 (일수 - 1)을 더함
        dateObj.setDate(dateObj.getDate() + (days));
        
        var y = dateObj.getFullYear();
        var m = ('0' + (dateObj.getMonth() + 1)).slice(-2);
        var d = ('0' + dateObj.getDate()).slice(-2);
        
        document.getElementById('modal_edate').value = y + '-' + m + '-' + d;
    }
}

function openModalManual() {
    document.getElementById('modal_title').innerText = "스케줄 수동 등록 (상품 미지정)";
    document.getElementById('modal_mode').value = 'assign';
    document.getElementById('modal_seq').value = '';
    document.getElementById('modal_ecode_hidden').value = ''; 
    document.getElementById('modal_p_day').value = '1'; // 기본 1일
    
    document.getElementById('modal_pname_readonly').style.display = 'none';
    document.getElementById('modal_pname_select').style.display = 'block';
    document.getElementById('modal_pname_select').value = '';

    document.getElementById('modal_select_bus').value = '';
    document.getElementById('modal_select_bus').disabled = false;
    
    var today = '<?= date("Y-m-d") ?>';
    document.getElementById('modal_sdate').value = today;
    document.getElementById('modal_edate').value = today;
    document.getElementById('modal_sdate').readOnly = false;
    document.getElementById('modal_edate').readOnly = false;
    document.getElementById('modal_sdate').style.background = '#fff';
    document.getElementById('modal_edate').style.background = '#fff';

    document.getElementById('modal_memo').value = '';
    document.getElementById('modal_color').value = '#3498db';
    
    document.getElementById('btn_save').style.display = 'inline-block';
    document.getElementById('btn_del').style.display = 'none';
    document.getElementById('lock_msg').style.display = 'none';

    filterProductOptions(today);
    document.getElementById('assignModal').style.display = 'flex';
}

function openModalFromData(mode, base64Str) {
    if (!base64Str) { alert("데이터 오류"); return; }

    var data = {};
    try {
        var jsonStr = decodeURIComponent(escape(window.atob(base64Str)));
        data = JSON.parse(jsonStr);
    } catch (e) { console.error(e); return; }

    document.getElementById('modal_seq').value   = data.seq_no || '';
    document.getElementById('modal_ecode_hidden').value = data.grand_eCode || '';
    document.getElementById('modal_select_bus').value = data.bus_num;
    document.getElementById('modal_sdate').value = data.stDate || '';
    document.getElementById('modal_edate').value = data.edDate || '';
    document.getElementById('modal_memo').value  = data.memo || '';
    document.getElementById('modal_color').value = data.color_code || '#3498db';
    
    // 데이터에서 p_day 가져와 세팅 (없으면 1)
    document.getElementById('modal_p_day').value = data.p_day || '1';

    filterProductOptions(data.stDate);

    if (mode === 'new') {
        document.getElementById('modal_title').innerText = "신규 배정 (상품 지정됨)";
        document.getElementById('modal_mode').value = 'assign';
        
        document.getElementById('modal_pname_readonly').value = data.p_name;
        document.getElementById('modal_pname_readonly').style.display = 'block';
        document.getElementById('modal_pname_select').style.display = 'none';

        document.getElementById('modal_sdate').readOnly = true;
        document.getElementById('modal_edate').readOnly = true;
        document.getElementById('modal_sdate').style.background = '#eee';
        document.getElementById('modal_edate').style.background = '#eee';

        document.getElementById('modal_select_bus').disabled = false;
        document.getElementById('btn_save').style.display = 'inline-block';
        document.getElementById('btn_del').style.display = 'none';
        document.getElementById('lock_msg').style.display = 'none';

    } else {
        document.getElementById('modal_title').innerText = "배정 상세 / 수정";
        document.getElementById('modal_mode').value = 'assign'; 

        var isUnassignedProduct = data.is_unassigned_product; 

        if (isUnassignedProduct) {
            document.getElementById('modal_pname_readonly').style.display = 'none';
            document.getElementById('modal_pname_select').style.display = 'block';
            document.getElementById('modal_pname_select').value = data.grand_eCode || ''; 
        } else {
            document.getElementById('modal_pname_readonly').value = data.p_name;
            document.getElementById('modal_pname_readonly').style.display = 'block';
            document.getElementById('modal_pname_select').style.display = 'none';
        }

        // 수정 시에는 날짜/기간 변경 가능
        document.getElementById('modal_sdate').readOnly = false;
        document.getElementById('modal_edate').readOnly = false;
        document.getElementById('modal_sdate').style.background = '#fff';
        document.getElementById('modal_edate').style.background = '#fff';
        document.getElementById('modal_select_bus').disabled = false;

        document.getElementById('btn_save').style.display = 'inline-block';
        document.getElementById('btn_del').style.display = 'inline-block';
        document.getElementById('lock_msg').style.display = 'none';
    }
    
    document.getElementById('assignModal').style.display = 'flex';
}

// 상품 선택 시 -> hidden 값 업데이트 & 날짜 자동 계산
function updateEcode(sel) {
    document.getElementById('modal_ecode_hidden').value = sel.value;

    var selectedOption = sel.options[sel.selectedIndex];
    var sDateVal = selectedOption.getAttribute('data-sdate');
    var daysVal  = selectedOption.getAttribute('data-days');

    if (sDateVal && daysVal) {
        document.getElementById('modal_sdate').value = sDateVal;
        document.getElementById('modal_p_day').value = daysVal; // 일차 저장
        
        calcEndDate(); // 종료일 재계산 호출
    }
}

function delAssign() {
    if(confirm('정말 삭제하시겠습니까?')) {
        document.getElementById('modal_mode').value = 'delete';
        document.getElementById('modal_form').submit();
    }
}

function closeModal() {
    document.getElementById('assignModal').style.display = 'none';
}
window.onclick = function(e) { if(e.target == document.getElementById('assignModal')) closeModal(); }
</script>

</body>
</html>