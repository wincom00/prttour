<?php
// 1. 에러 설정 (PHP 5.6 환경 호환 / mysql_* 함수 사용 유지)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

include "include/inc_base.php"; 
// ※ inc_base.php에서 DB 연결(mysql_connect)이 되어 있어야 합니다.

/* =================================================================================
 * [Backend] 1. 파라미터 및 날짜 설정
 * ================================================================================= */

if (empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo "<script>alert('관리자 로그인이 필요합니다.'); window.close();</script>";
    exit;
}

// 1. 파라미터 수신
$viewYear  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$viewMonth = isset($_GET['month']) ? $_GET['month'] : date('m');

// 2. 날짜 계산
$sDate = date("{$viewYear}-{$viewMonth}-01"); 
$eDate = date("Y-m-t", strtotime($sDate));    

// 3. 네비게이션
$today    = date('Y-m-d');
$prevDate = strtotime("-1 month", strtotime($sDate));
$nextDate = strtotime("+1 month", strtotime($sDate));

// 4. 날짜 배열 생성 (X축)
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
 * [Backend] 2. 데이터 처리 (등록/수정/삭제)
 * ================================================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode'])) {
    
    $grand_eCode = isset($_POST['grand_eCode']) ? mysql_real_escape_string($_POST['grand_eCode']) : '';
    $uid         = isset($_COOKIE['MEMLOGIN_ID']) ? mysql_real_escape_string($_COOKIE['MEMLOGIN_ID']) : 'admin';

    // A. 가이드 배정 등록 및 수정
    if ($_POST['mode'] === 'assign') {
        $seq_no     = isset($_POST['seq_no']) ? mysql_real_escape_string($_POST['seq_no']) : '';
        $guide_id   = mysql_real_escape_string($_POST['guide_id']);
        $start_date = mysql_real_escape_string($_POST['stDate']);
        $end_date   = mysql_real_escape_string($_POST['edDate']);
        $memo       = mysql_real_escape_string($_POST['memo']);
        $color_code = isset($_POST['color_code']) ? mysql_real_escape_string($_POST['color_code']) : '#3498db';

        if (empty($guide_id)) {
            echo "<script>alert('가이드는 반드시 지정해야 합니다.'); history.back();</script>";
            exit;
        }

        $tableName = "tour_guide_block"; 

        if ($seq_no) {
            // [수정]
            $qry = "UPDATE $tableName 
                    SET grand_eCode = '$grand_eCode',
                        guide_id = '$guide_id', 
                        stDate = '$start_date', 
                        edDate = '$end_date', 
                        memo = '$memo', 
                        color_code = '$color_code', 
                        wdate = NOW() 
                    WHERE seq_no = '$seq_no'";
        } else {
            // [신규] - 중복 날짜 허용
            $qry = "INSERT INTO $tableName (grand_eCode, guide_id, stDate, edDate, memo, color_code, userid, wdate) 
                    VALUES ('$grand_eCode', '$guide_id', '$start_date', '$end_date', '$memo', '$color_code', '$uid', NOW())";
        }
        
        if (!mysql_query($qry)) {
            echo "<script>alert('DB Error: " . mysql_error() . "'); history.back();</script>";
            exit;
        }
    }
    // B. 배정 삭제
    else if ($_POST['mode'] === 'delete') {
        $seq_no = mysql_real_escape_string($_POST['seq_no']);
        $tableName = "tour_guide_block";
        $qry = "DELETE FROM $tableName WHERE seq_no = '$seq_no'";
        
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
 * [Backend] 3. 데이터 조회
 * ================================================================================= */

// 3-1. 가이드 목록 (Y축)
$guideList = array();
$qry_guide = "SELECT userid, kor_name, cell_phone, division 
              FROM member_list 
              WHERE division = 'guide' 
                AND (out_yn IS NULL OR out_yn = '' OR out_yn = 'n')
              ORDER BY kor_name ASC";

$rst_guide = mysql_query($qry_guide);
if ($rst_guide) {
    while($row = mysql_fetch_assoc($rst_guide)) {
        $guideList[] = $row;
    }
}

// 3-2. 배정 데이터 조회
$scheduleMap = array(); 
$tableName = "tour_guide_block";

$chk_tbl = mysql_query("SHOW TABLES LIKE '$tableName'");
if(mysql_num_rows($chk_tbl) > 0) {
    $qry_assign = "SELECT B.*, P.p_name, P.p_day
                   FROM $tableName B
                   LEFT JOIN tour_master M ON B.grand_eCode = M.grand_eCode
                   LEFT JOIN product_master P ON M.p_code = P.p_code
                   WHERE (B.stDate <= '$eDate' AND B.edDate >= '$sDate')
                   ORDER BY B.stDate ASC, B.seq_no ASC"; 

    $rst_assign = mysql_query($qry_assign);
    if ($rst_assign) {
        while($row = mysql_fetch_assoc($rst_assign)) {
            if (empty($row['p_name'])) {
                $row['p_name'] = $row['memo'] ? $row['memo'] : "(일정명 미입력)";
                $row['is_unassigned_product'] = true;
            } else {
                $row['is_unassigned_product'] = false;
            }

            $gid = $row['guide_id'];
            
            $displayStart = ($row['stDate'] < $sDate) ? $sDate : $row['stDate'];
            $displayEnd   = ($row['edDate'] > $eDate) ? $eDate : $row['edDate'];
            
            $d1 = new DateTime($displayStart);
            $d2 = new DateTime($displayEnd);
            $diff = $d1->diff($d2);
            $colspan = $diff->days + 1; 
            
            $scheduleMap[$gid][$displayStart][] = array(
                'colspan' => $colspan,
                'info'    => $row
            );
        }
    }
}

// 3-3. 대기 목록
$unassignedListData = array();
if(mysql_num_rows($chk_tbl) > 0) {
    $qry_list = "SELECT M.grand_eCode, M.stDate, P.p_name, IFNULL(P.p_day, 1) as p_day, 
                    DATE_ADD(M.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) as edDate,
                    (SELECT count(*) FROM $tableName WHERE grand_eCode = M.grand_eCode) as assign_cnt
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
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>가이드 배정 스케줄러</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background: #f5f6fa; margin: 0; padding: 20px; font-size: 12px; color: #2f3640; }
        
        .control-bar { background: #fff; padding: 10px 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .month-control { display: flex; align-items: center; gap: 20px; }
        .month-title { font-size: 20px; font-weight: 700; color: #2c3e50; }
        .btn-nav { background: #ecf0f1; border: 1px solid #bdc3c7; padding: 6px 12px; border-radius: 4px; text-decoration: none; color: #333; font-weight: 600; }
        .btn-today { background: #3498db; color: #fff; border-color: #2980b9; }
        .btn-manual { background: #8e44ad; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; cursor: pointer; border: 1px solid #9b59b6; }

        .unassigned-zone { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f1c40f; }
        
        .card-container { display: flex; gap: 10px; flex-wrap: wrap; padding-bottom: 5px; align-items: flex-start; }
        .card { 
            background: #fff3e0; border: 1px solid #ffe0b2; padding: 8px; border-radius: 4px; 
            min-width: 150px; width: 150px; flex: 0 0 auto;
            cursor: pointer; transition: 0.2s; position: relative;
            height: auto; display: flex; flex-direction: column; justify-content: space-between;
            margin-bottom: 10px; 
        }
        .card:hover { border-color: #ffb74d; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .card-title { font-weight: bold; margin-bottom: 5px; white-space: normal; word-break: keep-all; line-height: 1.35; font-size: 11px; }
        .card-date { font-size: 11px; color: #e67e22; margin-top: 5px; text-align: right; }

        .grid-wrapper { background: #fff; border-radius: 8px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table.scheduler-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1400px; table-layout: fixed; }
        th, td { border: 1px solid #dfe6e9; border-top: 0; border-left: 0; padding: 0; text-align: center; vertical-align: top; position: relative; }
        
        thead th { background: #2d3436; color: #fff; position: sticky; top: 0; z-index: 10; padding: 8px 0; height: 30px; }
        .fixed-col { position: sticky; left: 0; z-index: 20; background: #dfe6e9; color: #2d3436; width: 100px; min-width: 100px; font-weight: 600; border-right: 2px solid #b2bec3; }
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
        .merged-cell:hover { opacity: 0.9; transform: translateY(-1px); z-index: 15; box-shadow: 0 3px 6px rgba(0,0,0,0.2); }
        .merged-cell.no-product {
            background-image: linear-gradient(45deg, rgba(255,255,255,0.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.15) 75%, transparent 75%, transparent);
            background-size: 10px 10px;
            border: 1px dashed rgba(255,255,255,0.5);
        }

        .js-click-modal { cursor: pointer; }
        td.cell-hoverable:hover { background-color: #f0f9ff; }
        td.cell-hoverable:hover .add-btn { display: block; }
        
        .empty-cell { background: #fff; }
        .weekend-cell { background: #f9f9f9; }

        .add-btn {
            display: none;
            position: absolute;
            top: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            line-height: 16px;
            background: #27ae60;
            color: white;
            text-align: center;
            border-radius: 50%;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            z-index: 8;
            opacity: 0.7;
        }
        .add-btn:hover { opacity: 1; transform: scale(1.1); }

        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 5px; width: 400px; }
        .form-row { margin-bottom: 10px; }
        .form-row label { display: block; font-size: 11px; color: #7f8c8d; margin-bottom: 3px; }
        .form-row input, .form-row select { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; }
        
        .btn { padding: 6px 12px; border: none; border-radius: 3px; color: white; cursor: pointer; }
        
        .guide-info { display: flex; flex-direction: column; justify-content: center; height: 100%; padding: 5px; text-align: left; }
        .guide-name { font-weight: bold; font-size: 12px; }
        .guide-phone { font-size: 10px; color: #636e72; margin-top: 2px; }

        /* [변경] 색상표 바둑판 스타일 */
        .color-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 5px;
        }
        .color-swatch {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        .color-swatch:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .color-swatch.selected {
            border: 2px solid #2c3e50;
            transform: scale(1.1);
            box-shadow: 0 0 0 2px rgba(255,255,255,0.8) inset; /* 내부 흰 테두리 효과 */
        }
    </style>
</head>
<body>

<div class="control-bar">
    <div class="month-control">
        <a href="?year=<?= date('Y', $prevDate) ?>&month=<?= date('m', $prevDate) ?>" class="btn-nav">&lt; 이전달</a>
        <div class="month-title"><?= $viewYear ?>년 <?= $viewMonth ?>월 가이드 스케줄</div>
        <a href="?year=<?= date('Y', $nextDate) ?>&month=<?= date('m', $nextDate) ?>" class="btn-nav">다음달 &gt;</a>
        <a href="?year=<?= date('Y') ?>&month=<?= date('m') ?>" class="btn-nav btn-today">오늘</a>
    </div>
    <button type="button" class="btn-manual" onclick="openModalManual()">[+] 가이드 스케줄 수동 등록</button>
</div>

<?php if(!empty($unassignedListData)): ?>
<div class="unassigned-zone">
    <div style="font-weight:bold; margin-bottom:5px;">📋 가이드 배정 대기 상품 목록</div>
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
            <col style="width: 100px;"> <!-- 가이드명 -->
            <?php foreach($dateHeaders as $dt): ?>
            <col style="width: 40px;">
            <?php endforeach; ?>
        </colgroup>
        <thead>
            <tr>
                <th class="fixed-col">가이드</th>
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
            <?php foreach($guideList as $guide): 
                $skipCount = 0; 
                $gId = $guide['userid'];
            ?>
            <tr>
                <td class="fixed-col">
                    <div class="guide-info">
                        <div class="guide-name"><?= $guide['kor_name'] ?></div>
                        <div class="guide-phone"><?= $guide['cell_phone'] ?></div>
                    </div>
                </td>
                <?php foreach($dateHeaders as $dt): 
                    if ($skipCount > 0) {
                        $skipCount--;
                        continue; 
                    }

                    $events = isset($scheduleMap[$gId][$dt]) ? $scheduleMap[$gId][$dt] : null;

                    // 1. 이벤트가 있는 경우
                    if ($events) {
                        $maxColspan = 1;
                        foreach($events as $ev) {
                            if($ev['colspan'] > $maxColspan) $maxColspan = $ev['colspan'];
                        }
                        $skipCount = $maxColspan - 1; 
                ?>
                    <td colspan="<?= $maxColspan ?>" class="cell-hoverable" style="padding: 1px; vertical-align: top;"
                        onclick="openModalManual('<?=$dt?>', '<?=$gId?>')">
                        <!-- 추가 버튼 (호버 시 표시) -->
                        <div class="add-btn" title="이 날짜에 새 스케줄 추가">+</div>

                        <?php 
                        foreach($events as $cell):
                            $info     = $cell['info'];
                            $cls      = '';
                            if ($info['is_unassigned_product']) {
                                $cls .= ' no-product';
                            }

                            $originStart = date('m/d', strtotime($info['stDate']));
                            $txt      = $info['p_name'];
                            
                            $bg_color = !empty($info['color_code']) ? $info['color_code'] : '#3498db';
                            
                            if (defined('JSON_UNESCAPED_UNICODE')) {
                                $jsonRaw = json_encode($info, JSON_UNESCAPED_UNICODE);
                            } else {
                                $jsonRaw = json_encode($info);
                            }
                            if($jsonRaw === false) $jsonRaw = '{}';
                            $base64Str = base64_encode($jsonRaw);
                        ?>
                        <!-- stopPropagation: 스케줄 박스 클릭 시 '추가' 모달이 아닌 '수정' 모달을 띄우기 위함 -->
                        <div class="merged-cell <?= $cls ?>"
                             style="background-color: <?= $bg_color ?>;"
                             title="<?= htmlspecialchars($txt) ?> (<?= $info['stDate'] ?> ~ <?= $info['edDate'] ?>)"
                             onclick="event.stopPropagation(); openModalFromData('edit', '<?=$base64Str?>')">
                            <?= $txt ?>
                        </div>
                        <?php endforeach; ?>
                    </td>
                <?php 
                    } else { 
                        // 2. 이벤트가 없는 빈 셀
                        $w = date('w', strtotime($dt));
                        $bg = ($w==0 || $w==6) ? 'weekend-cell' : 'empty-cell';
                ?>
                    <td class="<?= $bg ?> cell-hoverable" onclick="openModalManual('<?=$dt?>', '<?=$gId?>')">
                        <div class="add-btn" title="새 스케줄 추가">+</div>
                    </td>
                <?php } ?>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 모달: 가이드 배정용 -->
<div id="assignModal" class="modal-overlay">
    <div class="modal-content">
        <h3 id="modal_title" style="margin-top:0;">가이드 배정</h3>
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
                <label>스케줄 색상 선택</label>
                <!-- [변경] 컬러 피커 대신 hidden input과 div 컨테이너 -->
                <input type="hidden" name="color_code" id="modal_color" value="#3498db">
                <div class="color-grid" id="color_palette">
                    <!-- 자바스크립트로 색상 타일 생성 -->
                </div>
            </div>

            <div class="form-row">
                <label>가이드 선택 (필수)</label>
                <select name="guide_id" id="modal_select_guide" required>
                    <option value="">-- 가이드 선택 --</option>
                    <?php foreach($guideList as $g): ?>
                    <option value="<?= $g['userid'] ?>"><?= $g['kor_name'] ?> (<?= $g['userid'] ?>)</option>
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
                <input type="text" name="memo" id="modal_memo" placeholder="일정명 또는 특이사항">
            </div>
            
            <div style="text-align:right; margin-top:15px;">
                <button type="button" class="btn" style="background:#95a5a6;" onclick="closeModal()">닫기</button>
                <button type="submit" class="btn" style="background:#3498db;" id="btn_save">저장</button>
                <button type="button" class="btn" style="background:#e74c3c;" id="btn_del" onclick="delAssign()">삭제</button>
            </div>
        </form>
    </div>
</div>

<script>
// [변경] 사용할 색상 리스트 정의
const colorList = [
    '#3498db', '#2980b9', // Blue
    '#e74c3c', '#c0392b', // Red
    '#2ecc71', '#27ae60', // Green
    '#f1c40f', '#f39c12', // Yellow
    '#9b59b6', '#8e44ad', // Purple
    '#1abc9c', '#16a085', // Teal
    '#e67e22', '#d35400', // Orange
    '#34495e', '#7f8c8d'  // Grey
];

document.addEventListener('DOMContentLoaded', function() {
    renderColorPalette(); // 색상표 생성

    document.body.addEventListener('click', function(e) {
        var target = e.target.closest('.js-click-modal');
        if (target) {
            var mode = target.getAttribute('data-mode');
            var b64  = target.getAttribute('data-b64');
            openModalFromData(mode, b64);
        }
    });
});

// [변경] 색상표 렌더링 함수
function renderColorPalette() {
    const palette = document.getElementById('color_palette');
    palette.innerHTML = '';
    
    colorList.forEach(color => {
        const div = document.createElement('div');
        div.className = 'color-swatch';
        div.style.backgroundColor = color;
        div.onclick = function() { selectColor(color); };
        palette.appendChild(div);
    });
}

// [변경] 색상 선택 함수
function selectColor(color) {
    document.getElementById('modal_color').value = color;
    
    // UI 업데이트 (선택된 타일 강조)
    const swatches = document.querySelectorAll('.color-swatch');
    swatches.forEach(swatch => {
        // rgb로 변환되는 경우도 고려하여 비교하거나 단순히 style값 비교
        // 여기서는 간단히 모든 클래스 제거 후, 클릭된 요소만 추가하는 방식이 아니라
        // 전체 루프 돌며 배경색 비교
        swatch.classList.remove('selected');
        
        // 브라우저가 hex를 rgb로 변환할 수 있으므로, 단순 비교를 위해 
        // 현재 선택한 color 값을 input에 넣고, 다시 가져오는 방식보단
        // 렌더링 시점에 할당된 값과 비교하는 것이 안전하나,
        // 여기서는 간단하게 처리 (backgroundColor 값을 직접 비교 시 hex/rgb 차이 발생 가능)
    });

    // 선택된 컬러와 일치하는 swatch 찾기 (hex to rgb 변환 문제 회피를 위해 다시 루프)
    // 좀 더 정확한 매칭을 위해 render 시 dataset 이용 권장하지만, 간단 구현
    for(let i=0; i<swatches.length; i++) {
        // inline style을 hex로 지정했으므로 style.backgroundColor가 브라우저에 따라 rgb(...)로 나옴
        // 따라서, colorList의 인덱스로 매칭하거나, dataset을 활용하는 것이 좋음.
        // 이번에는 colorList 순서대로 생성했으므로, colorList에서 인덱스를 찾아 매칭.
        if (colorList['i'] && colorList['i'].toLowerCase() === color.toLowerCase()) {
            swatches['i'].classList.add('selected');
        }
    }
}

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

function calcEndDate() {
    var sDateVal = document.getElementById('modal_sdate').value;
    var daysVal = document.getElementById('modal_p_day').value; 

    if (sDateVal && daysVal && parseInt(daysVal) > 0) {
        var dateObj = new Date(sDateVal);
        var days = parseInt(daysVal);
        
        dateObj.setDate(dateObj.getDate() + (days - 1));
        
        var y = dateObj.getFullYear();
        var m = ('0' + (dateObj.getMonth() + 1)).slice(-2);
        var d = ('0' + dateObj.getDate()).slice(-2);
        
        document.getElementById('modal_edate').value = y + '-' + m + '-' + d;
    }
}

function openModalManual(preDate, preGuideId) {
    document.getElementById('modal_title').innerText = "가이드 스케줄 수동 등록 (중복 가능)";
    document.getElementById('modal_mode').value = 'assign';
    document.getElementById('modal_seq').value = '';
    document.getElementById('modal_ecode_hidden').value = ''; 
    document.getElementById('modal_p_day').value = '1'; 
    
    document.getElementById('modal_pname_readonly').style.display = 'none';
    document.getElementById('modal_pname_select').style.display = 'block';
    document.getElementById('modal_pname_select').value = '';

    var guideSel = document.getElementById('modal_select_guide');
    if(preGuideId) {
        guideSel.value = preGuideId;
    } else {
        guideSel.value = '';
    }
    guideSel.disabled = false;
    
    var targetDate = preDate ? preDate : '<?= date("Y-m-d") ?>';
    document.getElementById('modal_sdate').value = targetDate;
    document.getElementById('modal_edate').value = targetDate;
    
    document.getElementById('modal_sdate').readOnly = false;
    document.getElementById('modal_edate').readOnly = false;
    document.getElementById('modal_sdate').style.background = '#fff';
    document.getElementById('modal_edate').style.background = '#fff';

    document.getElementById('modal_memo').value = '';
    
    // [변경] 기본 색상 선택
    selectColor('#8e44ad');
    
    document.getElementById('btn_save').style.display = 'inline-block';
    document.getElementById('btn_del').style.display = 'none';

    filterProductOptions(targetDate);
    document.getElementById('assignModal').style.display = 'flex';
}

function openModalFromData(mode, base64Str) {
    if (!base64Str) { alert("데이터 오류"); return; }

    var data = {};
    try {
        var jsonStr = decodeURIComponent(escape(window.atob(base64Str)));
        data = JSON.parse(jsonStr);
    } catch (e) { console.error(e); return; }

    document.getElementById('modal_seq').value    = data.seq_no || '';
    document.getElementById('modal_ecode_hidden').value = data.grand_eCode || '';
    document.getElementById('modal_select_guide').value = data.guide_id || '';
    
    document.getElementById('modal_sdate').value = data.stDate || '';
    document.getElementById('modal_edate').value = data.edDate || '';
    document.getElementById('modal_memo').value  = data.memo || '';
    
    // [변경] 저장된 색상 선택 (없으면 기본값)
    var savedColor = data.color_code || '#3498db';
    selectColor(savedColor);
    
    document.getElementById('modal_p_day').value = data.p_day || '1';

    filterProductOptions(data.stDate);

    if (mode === 'new') {
        document.getElementById('modal_title').innerText = "신규 가이드 배정";
        document.getElementById('modal_mode').value = 'assign';
        
        document.getElementById('modal_pname_readonly').value = data.p_name;
        document.getElementById('modal_pname_readonly').style.display = 'block';
        document.getElementById('modal_pname_select').style.display = 'none';

        document.getElementById('modal_sdate').readOnly = true;
        document.getElementById('modal_edate').readOnly = true;
        document.getElementById('modal_sdate').style.background = '#eee';
        document.getElementById('modal_edate').style.background = '#eee';

        document.getElementById('modal_select_guide').disabled = false;
        document.getElementById('btn_save').style.display = 'inline-block';
        document.getElementById('btn_del').style.display = 'none';

    } else {
        document.getElementById('modal_title').innerText = "배정 수정 / 삭제";
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

        document.getElementById('modal_sdate').readOnly = false;
        document.getElementById('modal_edate').readOnly = false;
        document.getElementById('modal_sdate').style.background = '#fff';
        document.getElementById('modal_edate').style.background = '#fff';
        document.getElementById('modal_select_guide').disabled = false;

        document.getElementById('btn_save').style.display = 'inline-block';
        document.getElementById('btn_del').style.display = 'inline-block';
    }
    
    document.getElementById('assignModal').style.display = 'flex';
}

function updateEcode(sel) {
    document.getElementById('modal_ecode_hidden').value = sel.value;

    var selectedOption = sel.options[sel.selectedIndex];
    var sDateVal = selectedOption.getAttribute('data-sdate');
    var daysVal  = selectedOption.getAttribute('data-days');

    if (sDateVal && daysVal) {
        document.getElementById('modal_sdate').value = sDateVal;
        document.getElementById('modal_p_day').value = daysVal; 
        
        calcEndDate(); 
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