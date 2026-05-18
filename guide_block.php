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

// [추가] 지사 구분 파라미터 (기본값: E - 동부본사)
$viewRegion = isset($_GET['region']) ? $_GET['region'] : 'E';
// SQL Injection 방지
$viewRegion = ($viewRegion === 'W') ? 'W' : 'E';

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

// [추가] 모달용 전체 버스 리스트 조회 (차량 선택 셀렉트박스용)
$busOptionList = array();
$qry_bus_list = "SELECT bus_id, bus_team, bus_number FROM bus_list ORDER BY bus_id ASC";
$rst_bus_list = mysql_query($qry_bus_list);
if($rst_bus_list){
    while($b_row = mysql_fetch_assoc($rst_bus_list)){
        $busOptionList[] = $b_row;
    }
}

/* =================================================================================
 * [Backend] 2. 데이터 처리 (등록/수정/삭제)
 * ================================================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode'])) {

    $grand_eCode = isset($_POST['grand_eCode']) ? mysql_real_escape_string($_POST['grand_eCode']) : '';
    $uid         = isset($_COOKIE['MEMLOGIN_ID']) ? mysql_real_escape_string($_COOKIE['MEMLOGIN_ID']) : 'admin';

    // ✅ [핵심 수정] 수동등록(상품 미지정)도 저장되도록 grand_eCode 기본값 부여
    // - DB에서 NOT NULL/키 제약이 있으면 ''로 INSERT가 실패합니다.
    // - '0'은 기존 JOIN에 매칭되지 않아 "미지정"으로 정상 표시됩니다.
    if ($grand_eCode === '' || $grand_eCode === null) {
        $grand_eCode = '0';
    }

    // A. 가이드 배정 등록 및 수정
    if ($_POST['mode'] === 'assign') {
        $seq_no     = isset($_POST['seq_no']) ? mysql_real_escape_string($_POST['seq_no']) : '';
        $guide_id   = mysql_real_escape_string($_POST['guide_id']);
        $start_date = mysql_real_escape_string($_POST['stDate']);
        $end_date   = mysql_real_escape_string($_POST['edDate']);
        $memo       = mysql_real_escape_string($_POST['memo']);
        $color_code = isset($_POST['color_code']) ? mysql_real_escape_string($_POST['color_code']) : '#3498db';

        // [추가] 수동 입력 값 수신
        $manual_cnt = isset($_POST['manual_cnt']) ? mysql_real_escape_string($_POST['manual_cnt']) : '';
        $manual_bus = isset($_POST['manual_bus']) ? mysql_real_escape_string($_POST['manual_bus']) : '';

        if (empty($guide_id)) {
            echo "<script>alert('가이드는 반드시 지정해야 합니다.'); history.back();</script>";
            exit;
        }

        $tableName = "tour_guide_block";

        if ($seq_no) {
            // [수정] manual_cnt, manual_bus 추가
            $qry = "UPDATE $tableName
                    SET grand_eCode = '$grand_eCode',
                        guide_id = '$guide_id',
                        stDate = '$start_date',
                        edDate = '$end_date',
                        memo = '$memo',
                        color_code = '$color_code',
                        manual_cnt = '$manual_cnt',
                        manual_bus = '$manual_bus',
                        wdate = NOW()
                    WHERE seq_no = '$seq_no'";
        } else {
            // [신규] manual_cnt, manual_bus 추가
            $qry = "INSERT INTO $tableName (grand_eCode, guide_id, stDate, edDate, memo, color_code, manual_cnt, manual_bus, userid, wdate)
                    VALUES ('$grand_eCode', '$guide_id', '$start_date', '$end_date', '$memo', '$color_code', '$manual_cnt', '$manual_bus', '$uid', NOW())";
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

    // 리다이렉트 시 region 파라미터 유지
    $qs = http_build_query(array('year' => $viewYear, 'month' => $viewMonth, 'region' => $viewRegion));
    echo "<script>location.href='{$_SERVER['PHP_SELF']}?{$qs}';</script>";
    exit;
}

/* =================================================================================
 * [Backend] 3. 데이터 조회
 * ================================================================================= */

// 3-1. 가이드 목록 (Y축) - [수정] dept_prior 필터 적용
$guideList = array();
$qry_guide = "SELECT userid, kor_name, cell_phone, division
              FROM member_list
              WHERE division = 'guide'
                AND dept_prior = '$viewRegion'  /* 지사 구분 추가 */
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

    // [수정] 인원수(tour_pcnt)를 reserve_info 테이블에서 실시간 합산(CANCEL 제외)
    // p_code와 stDate를 기준으로 reserve_info와 매칭합니다.
    $qry_assign = "SELECT B.*, P.p_name, P.p_day,
                          (SELECT IFNULL(SUM(p_cnt), 0)
                           FROM reserve_info
                           WHERE p_code = M.p_code
                             AND stDate = M.stDate
                             AND rev_status != 'CANCEL') as tour_pcnt, /* 실시간 인원수 */
                          BL.bus_team as auto_bus_name /* 자동 차량명 */
                    FROM $tableName B
                    LEFT JOIN tour_master M ON B.grand_eCode = M.grand_eCode
                    LEFT JOIN product_master P ON M.p_code = P.p_code
                    /* 가이드 테이블 및 버스 리스트 조인 (자동 차량 정보용) */
                    LEFT JOIN tour_guide TG ON B.grand_eCode = TG.grand_eCode AND B.guide_id = TG.guide_id
                    LEFT JOIN bus_list BL ON BL.bus_id = TG.c_id AND B.grand_eCode = TG.grand_eCode
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

            // [로직] 1. 차량 정보 우선순위 (수동 > 자동)
            if (!empty($row['manual_bus'])) {
                $row['display_bus'] = $row['manual_bus'];
                $row['bus_source']  = 'manual'; // 아이콘 구분을 위한 플래그
            } else {
                // 자동값이 있으면 조합해서 표시
                $auto_bus = $row['auto_bus_name'];
                $row['display_bus'] = $auto_bus;
                $row['bus_source']  = 'auto';
            }

            // [로직] 2. 인원수 우선순위 (수동 > 자동)
            if (!empty($row['manual_cnt'])) {
                $row['display_cnt'] = $row['manual_cnt'];
                $row['cnt_source']  = 'manual';
            } else {
                $row['display_cnt'] = $row['tour_pcnt']; // reserve_info에서 합산된 값
                $row['cnt_source']  = 'auto';
            }

            $gid = $row['guide_id'];

            $displayStart = ($row['stDate'] < $sDate) ? $sDate : $row['stDate'];
            $displayEnd   = ($row['edDate'] > $eDate) ? $eDate : $row['edDate'];

            // 기간 전체 날짜에 매핑 (겹쳐도 쌓이도록)
            $cur = strtotime($displayStart);
            $end = strtotime($displayEnd);
            while ($cur <= $end) {
                $dayKey = date('Y-m-d', $cur);
                if (!isset($scheduleMap[$gid][$dayKey])) $scheduleMap[$gid][$dayKey] = array();
                $scheduleMap[$gid][$dayKey][] = array('info' => $row);
                $cur = strtotime('+1 day', $cur);
            }
        }
    }
}

// 3-3. 대기 목록
$unassignedListData = array();
if(mysql_num_rows($chk_tbl) > 0) {

    // [추가] product_master.m_dept 기준 지사 필터 (구분자 "/" 포함 조건)
    $deptFilter = ($viewRegion === 'W')
        ? "P.m_dept LIKE '%D021500/%'"   // 서부
        : "P.m_dept LIKE '%D020500/%'";  // 동부

    // 대기 목록에서도 인원수를 reserve_info 기준으로 정확히 표시
    $qry_list = "SELECT M.grand_eCode, M.stDate, P.p_name, IFNULL(P.p_day, 1) as p_day,
                    DATE_ADD(M.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) as edDate,
                    (SELECT count(*) FROM $tableName WHERE grand_eCode = M.grand_eCode) as assign_cnt,
                    (SELECT IFNULL(SUM(p_cnt), 0) FROM reserve_info WHERE p_code = M.p_code AND stDate = M.stDate AND rev_status != 'CANCEL') as real_pcnt
                 FROM tour_master M
                 LEFT JOIN product_master P ON M.p_code = P.p_code
                 WHERE
                     (M.stDate <= '$eDate' AND DATE_ADD(M.stDate, INTERVAL (IFNULL(P.p_day, 1) - 1) DAY) >= '$sDate')
                 AND M.stDate >= '$today'
                 AND $deptFilter
                 GROUP BY M.grand_eCode
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
        /* [추가] 라디오 버튼 스타일 */
        .region-toggle { display: flex; gap: 15px; background: #eee; padding: 5px 15px; border-radius: 20px; margin-left: 20px; }
        .region-toggle label { cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 5px; }
        .region-toggle input[type=radio] { cursor: pointer; transform: scale(1.2); }

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

        .card.is-assigned { background-color: #ecf0f1; border-color: #bdc3c7; color: #95a5a6; cursor: default; opacity: 0.8; }
        .card.is-assigned:hover { transform: none; box-shadow: none; border-color: #bdc3c7; }

        .badge-assigned {
            position: absolute; top: 5px; right: 5px;
            background-color: #ff7675; color: white; font-size: 10px;
            padding: 2px 6px; border-radius: 10px; font-weight: bold;
            box-shadow: 0 1px 2px rgba(0,0,0,0.2); z-index: 2;
        }
        .grid-wrapper { background: #fff; border-radius: 8px; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table.scheduler-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1400px; table-layout: fixed; }
        th, td { border: 1px solid #dfe6e9; border-top: 0; border-left: 0; padding: 0; text-align: center; vertical-align: top; position: relative; }

        .grid-wrapper { background: #fff; border-radius: 8px; overflow: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-height: 800px; }
        table.scheduler-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1400px; table-layout: fixed; }

        /* 스티키 헤더 설정 */
        thead th {
            background: #2d3436; color: #fff;
            position: sticky; top: 0;
            z-index: 1000;
            padding: 8px 0; height: 35px;
            box-shadow: 0 2px 2px rgba(0,0,0,0.1);
            border: 1px solid #dfe6e9;
        }

         .merged-cell {
            color: white; cursor: pointer; border-radius: 4px;
            box-sizing: border-box; padding: 4px 6px; margin: 2px;
            min-height: 26px; line-height: 1.4;
            /* 내용이 잘리지 않고 줄바꿈되도록 수정 */
            white-space: normal;
            word-wrap: break-word;
            word-break: break-all;
            overflow: visible;
            box-shadow: 0 1px 2px rgba(0,0,0,0.15);
            position: relative; z-index: 5; font-size: 11px;
            display: block; text-align: left;
        }
        /* [수정] 왼쪽 컬럼 고정 */
        .fixed-col {
            position: sticky; left: 0;
            z-index: 900; /* 데이터 셀보다는 높게, 헤더보다는 낮게 */
            background: #dfe6e9; color: #2d3436;
            width: 100px; min-width: 100px; font-weight: 600;
            border-right: 2px solid #b2bec3;
        }
        /* 교차점(왼쪽 위) 최상위 고정 */
        thead th.fixed-col {
            z-index: 1100;
            background: #636e72; color: #fff;
            top: 0; left: 0;
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

        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 5px; width: 420px; max-height: 90vh; overflow-y: auto;}
        .form-row { margin-bottom: 10px; }
        .form-row label { display: block; font-size: 11px; color: #7f8c8d; margin-bottom: 3px; }
        .form-row input, .form-row select { width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; }

        .btn { padding: 6px 12px; border: none; border-radius: 3px; color: white; cursor: pointer; }

        .guide-info { display: flex; flex-direction: column; justify-content: center; height: 100%; padding: 5px; text-align: left; }
        .guide-name { font-weight: bold; font-size: 12px; }
        .guide-phone { font-size: 10px; color: #636e72; margin-top: 2px; }

        .color-grid { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 5px; }
        .color-swatch {
            width: 28px; height: 28px; border-radius: 4px; cursor: pointer;
            border: 1px solid rgba(0,0,0,0.1); transition: all 0.2s;
        }
        .color-swatch:hover { transform: scale(1.1); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .color-swatch.selected { border: 2px solid #2c3e50; transform: scale(1.1); box-shadow: 0 0 0 2px rgba(255,255,255,0.8) inset; }
    </style>
</head>
<body>

<div class="control-bar">
    <div class="month-control">
        <a href="?year=<?= date('Y', $prevDate) ?>&month=<?= date('m', $prevDate) ?>&region=<?=$viewRegion?>" class="btn-nav">&lt; 이전달</a>
        <div class="month-title"><?= $viewYear ?>년 <?= $viewMonth ?>월 가이드 스케줄</div>
        <a href="?year=<?= date('Y', $nextDate) ?>&month=<?= date('m', $nextDate) ?>&region=<?=$viewRegion?>" class="btn-nav">다음달 &gt;</a>
        <a href="?year=<?= date('Y') ?>&month=<?= date('m') ?>&region=<?=$viewRegion?>" class="btn-nav btn-today">오늘</a>

        <div class="region-toggle">
            <label>
                <input type="radio" name="region_filter" value="E" onclick="changeRegion('E')" <?= ($viewRegion == 'E') ? 'checked' : '' ?>>
                동부 본사
            </label>
            <label>
                <input type="radio" name="region_filter" value="W" onclick="changeRegion('W')" <?= ($viewRegion == 'W') ? 'checked' : '' ?>>
                서부 지사
            </label>
        </div>
    </div>
    <button type="button" class="btn-manual" onclick="openModalManual()">[+] 가이드 스케줄 수동 등록</button>
</div>

<?php if(!empty($unassignedListData)): ?>
<div class="unassigned-zone">
    <div style="font-weight:bold; margin-bottom:5px;">📋 가이드 배정 대기 상품 목록</div>
    <div class="card-container">
        <?php foreach($unassignedListData as $item):
            $stTxt = date('m/d', strtotime($item['stDate']));
            $title = $item['p_name'];
            $pax = isset($item['real_pcnt']) ? $item['real_pcnt'] : 0;

            $isAssigned = ($item['assign_cnt'] > 0);

            if (defined('JSON_UNESCAPED_UNICODE')) {
                $jsonRaw = json_encode($item, JSON_UNESCAPED_UNICODE);
            } else {
                $jsonRaw = json_encode($item);
            }
            if($jsonRaw === false) $jsonRaw = '{}';
            $base64Str = base64_encode($jsonRaw);

            $cardClass = $isAssigned ? 'card is-assigned' : 'card js-click-modal';
        ?>
        <div class="<?= $cardClass ?>"
             <?php if(!$isAssigned): ?>
             data-mode="new"
             data-b64="<?=$base64Str?>"
             <?php endif; ?>
        >
            <?php if($isAssigned): ?>
                <span class="badge-assigned">배정됨</span>
            <?php endif; ?>

            <div class="card-title" title="<?= htmlspecialchars($title) ?>"><?= $title ?></div>
            <div class="card-date"><?= $item['stDate'] ?> (<?= $item['p_day'] ?>일)</div>
            <div style="font-size:10px; color:#7f8c8d; text-align:right;">모객: <?= $pax ?>명</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="grid-wrapper">
    <table class="scheduler-table">
        <colgroup>
            <col style="width: 100px;"> <?php foreach($dateHeaders as $dt): ?>
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

                    if ($events) {
                        $maxColspan = 1; // (현재 로직 유지)
                        $skipCount = $maxColspan - 1;
                ?>
                    <td colspan="<?= $maxColspan ?>" class="cell-hoverable" style="padding: 1px; vertical-align: top;"
                        onclick="openModalManual('<?=$dt?>', '<?=$gId?>')">
                        <div class="add-btn" title="이 날짜에 새 스케줄 추가">+</div>

                        <?php
                        foreach($events as $cell):
                            $info     = $cell['info'];
                            $cls      = '';
                            if ($info['is_unassigned_product']) {
                                $cls .= ' no-product';
                            }

                            $txt      = strip_tags($info['p_name']);
                            $bg_color = !empty($info['color_code']) ? $info['color_code'] : '#3498db';

                            $displayHtml = '';

                            // [변경] 차량 정보 표시 (수동/자동 우선순위 적용됨)
                            if (!empty($info['display_bus'])) {
                                $busIcon = ($info['bus_source'] == 'manual') ? '🖐️🚌' : '🚌';
                                $displayHtml .= '<div style="font-size:10px; color:#f1c40f; margin-bottom:2px; font-weight:bold;">';
                                $displayHtml .= $busIcon . ' ' . htmlspecialchars($info['display_bus']);
                                $displayHtml .= '</div>';
                            }

                            // [추가] 인원수 정보 표시
                            if (!empty($info['display_cnt']) && $info['display_cnt'] > 0) {
                                $cntIcon = ($info['cnt_source'] == 'manual') ? '🖐️' : '';
                                $displayHtml .= '<div style="font-size:10px; color:#fff; margin-bottom:2px; opacity:0.9;">';
                                $displayHtml .= '👥 ' . $cntIcon . htmlspecialchars($info['display_cnt']) . '명';
                                $displayHtml .= '</div>';
                            }

                            if (!empty($info['memo'])) {
                                $displayHtml .= '<div style="margin-top:3px; font-size:10px; opacity:0.9; border-top:1px solid rgba(255,255,255,0.3); padding-top:2px; white-space: normal; line-height: 1.2;">';
                                $displayHtml .= '📝 ' . htmlspecialchars($info['memo']);
                                $displayHtml .= '</div>';
                            }

                            if (defined('JSON_UNESCAPED_UNICODE')) {
                                $jsonRaw = json_encode($info, JSON_UNESCAPED_UNICODE);
                            } else {
                                $jsonRaw = json_encode($info);
                            }
                            if($jsonRaw === false) $jsonRaw = '{}';
                            $base64Str = base64_encode($jsonRaw);
                        ?>
                        <div class="merged-cell <?= $cls ?>"
                             style="background-color: <?= $bg_color ?>;"
                             title="<?= strip_tags($txt) ?>"
                             onclick="event.stopPropagation(); openModalFromData('edit', '<?=$base64Str?>')">

                             <div style="font-weight:bold; margin-bottom:2px;">
                                <?= $txt ?>(<?= $info['p_day'] ?>일)
                             </div>

                             <?= $displayHtml ?>
                        </div>

                        <?php endforeach; ?>
                    </td>
                <?php
                    } else {
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
                <input type="hidden" name="color_code" id="modal_color" value="#3498db">
                <div class="color-grid" id="color_palette"></div>
            </div>

            <div class="form-row" style="background: #f7f9fa; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                <label style="font-weight:bold; color:#2c3e50; margin-bottom:5px;">[옵션] 수동 정보 입력 (우선 적용)</label>

                <div style="margin-bottom:8px;">
                    <label>인원수 (Manual Pax)</label>
                    <input type="text" name="manual_cnt" id="modal_manual_cnt" placeholder="예: 25 (입력 시 자동값 무시됨)">
                </div>

                <div>
                    <label>차량 선택 (Manual Bus)</label>
                    <select name="manual_bus" id="modal_manual_bus" style="width:100%;">
                        <option value="">-- 자동 배정값 사용 (미선택) --</option>
                        <?php foreach($busOptionList as $bus): ?>
                            <option value="<?= $bus['bus_id'] ?> ">
                                <?= $bus['bus_id'] ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="RENTAL">외부 렌탈 차량</option>
                        <option value="ETC">기타 차량</option>
                    </select>
                    <div style="font-size:10px; color:#e74c3c; margin-top:2px;">* 선택 시 가이드/배차 테이블 값보다 우선하여 표시됩니다.</div>
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
    renderColorPalette();

    document.body.addEventListener('click', function(e) {
        var target = e.target.closest('.js-click-modal');
        if (target) {
            var mode = target.getAttribute('data-mode');
            var b64  = target.getAttribute('data-b64');
            openModalFromData(mode, b64);
        }
    });
});

// [추가] 지사 변경 함수 (라디오 버튼)
function changeRegion(region) {
    var url = new URL(window.location.href);
    url.searchParams.set('region', region);
    window.location.href = url.toString();
}

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

function selectColor(color) {
    document.getElementById('modal_color').value = color;

    const swatches = document.querySelectorAll('.color-swatch');
    swatches.forEach(swatch => {
        swatch.classList.remove('selected');
    });

    for(let i=0; i<swatches.length; i++) {
        if (colorList[i] && colorList[i].toLowerCase() === color.toLowerCase()) {
            swatches[i].classList.add('selected');
        }
    }
}

function filterProductOptions(targetDate) {
    var select = document.getElementById('modal_pname_select');
    if (!select) return;

    var options = select.options;

    if (!targetDate) return;

    for (var i = 0; i < options.length; i++) {
        var opt = options[i];
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
    if (currentSelected && (currentSelected.style.display === 'none' || currentSelected.disabled)) {
        select.value = "";
        document.getElementById('modal_ecode_hidden').value = "";
    }
}

// ✅ [추가] (기존 onchange="updateEcode(this)"가 있었는데 함수가 없어서)
// 수동 등록에서 상품 선택 시 hidden grand_eCode / p_day / 날짜 자동 세팅
function updateEcode(sel){
    var hidden = document.getElementById('modal_ecode_hidden');
    var pdayEl = document.getElementById('modal_p_day');

    if(!sel){
        if(hidden) hidden.value = '0';
        if(pdayEl) pdayEl.value = '1';
        calcEndDate();
        return;
    }

    // 상품 미지정이면 0으로 저장(중복 등록 가능 + DB 제약 회피)
    if(sel.value === ''){
        hidden.value = '0';
        pdayEl.value = '1';
        calcEndDate();
        return;
    }

    hidden.value = sel.value;

    var opt = sel.options[sel.selectedIndex];
    if(opt){
        var sDateVal = opt.getAttribute('data-sdate') || '';
        var daysVal  = opt.getAttribute('data-days') || '1';

        if(daysVal) pdayEl.value = daysVal;

        // 수동등록 모드에서만 날짜 자동 세팅(기존 흐름 유지)
        if(sDateVal){
            document.getElementById('modal_sdate').value = sDateVal;
        }
        calcEndDate();
    }
}

function calcEndDate() {
    var sDateVal = document.getElementById('modal_sdate').value;
    var daysVal  = document.getElementById('modal_p_day').value;

    var days = parseInt(daysVal, 10);
    if (isNaN(days) || days < 1) days = 1;

    if (!sDateVal) return;

    // 날짜 파싱(타임존 이슈 방지)
    var parts = sDateVal.split('-');
    if (parts.length !== 3) return;

    var y = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10) - 1;
    var d = parseInt(parts[2], 10);

    var dateObj = new Date(y, m, d);
    dateObj.setDate(dateObj.getDate() + (days - 1));

    var ey = dateObj.getFullYear();
    var em = ('0' + (dateObj.getMonth() + 1)).slice(-2);
    var ed = ('0' + dateObj.getDate()).slice(-2);

    document.getElementById('modal_edate').value = ey + '-' + em + '-' + ed;
}

function openModalManual(preDate, preGuideId) {
    document.getElementById('modal_title').innerText = "가이드 스케줄 수동 등록 (중복 가능)";
    document.getElementById('modal_mode').value = 'assign';
    document.getElementById('modal_seq').value = '';

    // ✅ [핵심 수정] 수동등록 기본 grand_eCode를 0으로 (미지정도 저장 가능 → 중복등록 가능)
    document.getElementById('modal_ecode_hidden').value = '0';
    document.getElementById('modal_p_day').value = '1';

    // [추가] 수동 필드 초기화
    document.getElementById('modal_manual_cnt').value = '';
    document.getElementById('modal_manual_bus').value = '';

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
    document.getElementById('modal_ecode_hidden').value = (data.grand_eCode && data.grand_eCode !== '') ? data.grand_eCode : '0';
    document.getElementById('modal_select_guide').value = data.guide_id || '';

    document.getElementById('modal_sdate').value = data.stDate || '';
    document.getElementById('modal_edate').value = data.edDate || '';
    document.getElementById('modal_memo').value  = data.memo || '';

    // [추가] 수동 값 바인딩
    document.getElementById('modal_manual_cnt').value = data.manual_cnt || '';
    document.getElementById('modal_manual_bus').value = data.manual_bus || '';

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

        // 신규 배정이므로 수동값 초기화
        document.getElementById('modal_manual_cnt').value = '';
        document.getElementById('modal_manual_bus').value = '';

    } else {
        document.getElementById('modal_title').innerText = "배정 수정 / 삭제";
        document.getElementById('modal_mode').value = 'assign';

        var isUnassignedProduct = data.is_unassigned_product;

        if (isUnassignedProduct) {
            document.getElementById('modal_pname_readonly').style.display = 'none';
            document.getElementById('modal_pname_select').style.display = 'block';
            // unassigned면 0 또는 기존값
            document.getElementById('modal_pname_select').value = (data.grand_eCode && data.grand_eCode !== '') ? data.grand_eCode : '';
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

document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('modal_form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        var hidden = document.getElementById('modal_ecode_hidden');
        var sel    = document.getElementById('modal_pname_select');

        // ✅ [수정] 저장 직전 grand_eCode가 비면 0으로 강제 (수동 중복등록/미지정 저장 보장)
        if (hidden && (!hidden.value || hidden.value === '')) {
            hidden.value = '0';
        }

        // 상품 셀렉트가 보이는 상태(=상품 지정 가능한 상태)인데
        // hidden이 비었으면, select값을 강제로 hidden에 복사
        if (sel && sel.style.display !== 'none') {
            if (hidden && (!hidden.value || hidden.value === '' || hidden.value === '0') && sel.value) {
                hidden.value = sel.value;

                // 같이 기간도 정확히 맞춰줌(선택했는데 기간이 안 따라오는 케이스 방지)
                var opt = sel.options[sel.selectedIndex];
                if (opt) {
                    var sDateVal = opt.getAttribute('data-sdate') || '';
                    var daysVal  = opt.getAttribute('data-days') || '1';

                    if (daysVal) document.getElementById('modal_p_day').value = daysVal;
                    if (sDateVal) document.getElementById('modal_sdate').value = sDateVal;

                    calcEndDate();
                }
            }

            // 셀렉트가 "미지정"이면 hidden을 0으로
            if (hidden && (!sel.value || sel.value === '')) {
                hidden.value = '0';
            }
        }
    });
});
</script>

</body>
</html>
