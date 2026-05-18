<?php
/* ============================================================
 * eis_m.php  (Executive MIS - Fullscreen Dashboard)
 * PHP 5.6 + mysql_* (Legacy compatible)
 * Data: reserve_info, payment_history, product_master(m_dept)
 * UI: FULL HEIGHT + Sticky Filterbar + Scroll-safe Charts + Region Donut
 * + Modal: click list rows -> show reservation list/detail
 *
 * ✅ FIX(요청):
 *  - 모든 통계를 "출발일(stDate)" 또는 "예약일(revDate / rev_Date)" 기준으로 선택 조회
 *  - 매출/수금/미수금 포함 모든 집계/차트/리스트가 선택 기준 날짜컬럼으로 동일 적용
 *  - 상태 기준: READY/DONE (MAIN)
 *  - 모달 상품리스트: 전체 보기(페이지네이션) + 상태표시(READY/DONE/CANCEL + 결제상태)
 * ============================================================ */

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 1);
@session_start();

/* ====== DB INCLUDE (프로젝트에 맞게 경로 조정) ====== */
$incPath = dirname(__FILE__) . "/include/inc_base.php";
if (file_exists($incPath)) {
    include_once $incPath;
} else {
    $incPath2 = $_SERVER['DOCUMENT_ROOT'] . "/include/inc_base.php";
    if (file_exists($incPath2)) {
        include_once $incPath2;
    }
}

/* ====== LOGIN CHECK (쿠키명 호환) ====== */
$authOk = false;
$cookieKeys = array('MEMLOGIN_ADMIN_PURUN','MEMLOGIN_ADMIN_HELLO','MEMLOGIN_ADMIN_ROYAL');
for ($i=0; $i<count($cookieKeys); $i++) {
    $ck = $cookieKeys[$i];
    if (!empty($_COOKIE[$ck])) { $authOk = true; break; }
}
if (!$authOk) {
    echo "<script>alert('관리자 로그인이 필요합니다.'); location.href='./login.php';</script>";
    exit;
}

/* ====== mysql_* 연결 체크 ====== */
if (!function_exists('mysql_query')) {
    echo "<div style='padding:20px;font-family:Arial;color:#b00'>mysql_* 확장이 없습니다. PHP 5.6 환경인지 확인하세요.</div>";
    exit;
}

/* ====== Helpers ====== */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n) { return number_format((float)$n, 2); }
function intfmt($n) { return number_format((int)$n); }

/* esc()가 inc_base.php에 없을 수도 있으니 방어 */
if (!function_exists('esc')) {
    function esc($s) {
        if (is_array($s)) return '';
        $s = (string)$s;
        if (function_exists('mysql_real_escape_string')) return mysql_real_escape_string($s);
        return addslashes($s);
    }
}

function db_one($sql) {
    $r = mysql_query($sql);
    if (!$r) return null;
    $row = mysql_fetch_assoc($r);
    return $row ? $row : null;
}
function db_all($sql) {
    $r = mysql_query($sql);
    $rows = array();
    if (!$r) return $rows;
    while ($row = mysql_fetch_assoc($r)) $rows[] = $row;
    return $rows;
}

/* ============================================================
 * Filters (기본: 이번달)
 * - date_basis: st(출발일) | rev(예약일)
 * ============================================================ */
$today = date('Y-m-d');

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'month'; // month | custom
$viewYear  = isset($_GET['year']) ? $_GET['year'] : date('Y');
$viewMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$viewMonth = str_pad((string)$viewMonth, 2, '0', STR_PAD_LEFT);

/* ✅ 기준일 선택 */
$date_basis = isset($_GET['date_basis']) ? $_GET['date_basis'] : 'st'; // st | rev
$date_basis = ($date_basis === 'rev') ? 'rev' : 'st';

/* reserve_info의 실제 컬럼명은 코드상 revDate를 사용 */
$DATE_COL = ($date_basis === 'rev') ? 'revDate' : 'stDate';
$DATE_LABEL = ($date_basis === 'rev') ? '예약일' : '출발일';

if ($mode === 'custom') {
    $sDate = isset($_GET['s']) ? $_GET['s'] : date('Y-m-01');
    $eDate = isset($_GET['e']) ? $_GET['e'] : date('Y-m-t');
} else {
    $sDate = date($viewYear . "-" . $viewMonth . "-01");
    $eDate = date("Y-m-t", strtotime($sDate));
}

$sDate = esc($sDate);
$eDate = esc($eDate);

$sDT = esc($sDate . " 00:00:00");
$eDT = esc($eDate . " 23:59:59");

/* ============================================================
 * ✅ 공통 기준: 선택일자(출발/예약) + MAIN + READY/DONE (완전 통일)
 * ============================================================ */
$BASE_WHERE    = "parent='MAIN' AND {$DATE_COL} BETWEEN '{$sDate}' AND '{$eDate}' AND rev_status IN ('READY','DONE')";
$BASE_WHERE_RI = "ri.parent='MAIN' AND ri.{$DATE_COL} BETWEEN '{$sDate}' AND '{$eDate}' AND ri.rev_status IN ('READY','DONE')";

/* ============================================================
 * AJAX: Modal content provider (same file)
 * ============================================================ */
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');

    $kind = isset($_GET['kind']) ? $_GET['kind'] : '';
    $kind = preg_replace('/[^a-z_]/', '', $kind);

    $out = array('ok'=>0, 'title'=>'', 'html'=>'');

    /* ----------------------------
     * 예약 상세
     * ---------------------------- */
    if ($kind === 'reserve_detail') {
        $reserveCode = isset($_GET['reserveCode']) ? esc($_GET['reserveCode']) : '';
        if ($reserveCode === '') {
            $out['title'] = '예약 상세';
            $out['html'] = '<div class="text-danger">reserveCode가 없습니다.</div>';
            echo json_encode($out); exit;
        }

        $row = db_one("
            SELECT reserveCode, book_pri, stDate, revDate, p_code, p_name, last_total, last_bal, rev_status,
                   payment_st
            FROM reserve_info
            WHERE parent='MAIN' AND reserveCode='{$reserveCode}'
            LIMIT 1
        ");

        if (!$row) {
            $out['title'] = '예약 상세';
            $out['html'] = '<div class="text-muted">데이터가 없습니다.</div>';
            echo json_encode($out); exit;
        }

        $out['ok'] = 1;
        $out['title'] = '예약 상세: ' . $row['reserveCode'];

        $html = '';
        $html .= '<div class="row">';
        $html .= '  <div class="col-sm-6"><div class="well well-sm" style="margin-bottom:10px;">';
        $html .= '    <div><b>고객명</b>: '.h($row['book_pri']).'</div>';
        $html .= '    <div><b>출발일</b>: '.h($row['stDate']).'</div>';
        $html .= '    <div><b>예약일</b>: '.h($row['revDate']).'</div>';
        $html .= '    <div><b>상품</b>: '.h(strip_tags($row['p_name'])).' ('.h($row['p_code']).')</div>';
        $html .= '  </div></div>';
        $html .= '  <div class="col-sm-6"><div class="well well-sm" style="margin-bottom:10px;">';
        $html .= '    <div><b>총액</b>: $ '.money($row['last_total']).'</div>';
        $html .= '    <div><b>미수</b>: $ '.money($row['last_bal']).'</div>';
        $html .= '    <div><b>상태</b>: '.h($row['rev_status']).' / 결제: '.h($row['payment_st']).'</div>';
        $html .= '  </div></div>';
        $html .= '</div>';

        $out['html'] = $html;
        echo json_encode($out); exit;
    }

    /* ----------------------------
     * ✅ 상품 예약 리스트 (전체 보기 + 상태표시 + 페이지네이션)
     * ---------------------------- */
    if ($kind === 'product_reservations') {
        $p_code = isset($_GET['p_code']) ? esc($_GET['p_code']) : '';
        if ($p_code === '') {
            $out['title'] = '상품 예약 리스트';
            $out['html'] = '<div class="text-danger">p_code가 없습니다.</div>';
            echo json_encode($out); exit;
        }

        // ✅ 페이지네이션
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;

        $perPage = isset($_GET['per']) ? (int)$_GET['per'] : 200; // 기본 200
        if ($perPage < 50) $perPage = 50;
        if ($perPage > 500) $perPage = 500;

        // 전체 건수
        $cntRow = db_one("
            SELECT COUNT(*) AS total_cnt
            FROM reserve_info
            WHERE {$BASE_WHERE}
              AND p_code='{$p_code}'
        ");
        $totalCnt = (int)($cntRow ? $cntRow['total_cnt'] : 0);

        $totalPage = ($totalCnt > 0) ? (int)ceil($totalCnt / $perPage) : 1;
        if ($totalPage < 1) $totalPage = 1;
        if ($page > $totalPage) $page = $totalPage;
        if ($page < 1) $page = 1;

        $offset = ($page - 1) * $perPage;
        if ($offset < 0) $offset = 0;

        // 리스트
        $rows = db_all("
            SELECT reserveCode, book_pri, stDate, revDate, p_name, last_total, last_bal, rev_status, payment_st
            FROM reserve_info
            WHERE {$BASE_WHERE}
              AND p_code='{$p_code}'
            ORDER BY {$DATE_COL} DESC
            LIMIT {$offset}, {$perPage}
        ");

        $out['ok'] = 1;
        $out['title'] = '상품 예약 리스트: ' . $p_code . " (전체 {$totalCnt}건 / {$page}/{$totalPage}p)";

        // 상태 배지
        $fnBadge = function($txt, $type){
            $bg = '#777';
            if ($type === 'ok') $bg = '#1b7f3a';
            if ($type === 'warn') $bg = '#b7791f';
            if ($type === 'danger') $bg = '#b00020';
            return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;color:#fff;background:'.$bg.';">'.h($txt).'</span>';
        };

        $html = '';

        // 상단 컨트롤
        $html .= '<div class="clearfix" style="margin-bottom:8px;">';
        $html .= '  <div class="pull-left text-muted" style="font-size:12px;line-height:28px;">';
        $html .= '    전체 <b>'.intfmt($totalCnt).'</b>건 / 페이지당 ';
        $html .= '    <select class="form-control input-sm js-prod-per" style="display:inline-block;width:90px;">';
        foreach (array(100,200,300,500) as $pp){
            $sel = ($pp == $perPage) ? 'selected' : '';
            $html .= '<option value="'.$pp.'" '.$sel.'>'.$pp.'</option>';
        }
        $html .= '    </select>';
        $html .= '  </div>';

        $prev = ($page > 1) ? ($page - 1) : 1;
        $next = ($page < $totalPage) ? ($page + 1) : $totalPage;
        $disPrev = ($page <= 1) ? 'disabled' : '';
        $disNext = ($page >= $totalPage) ? 'disabled' : '';

        $html .= '  <div class="pull-right">';
        $html .= '    <button class="btn btn-default btn-sm js-prod-page" data-page="'.$prev.'" '.$disPrev.'>이전</button> ';
        $html .= '    <span class="text-muted" style="font-size:12px;vertical-align:middle;margin:0 6px;">'.$page.' / '.$totalPage.'</span>';
        $html .= '    <button class="btn btn-default btn-sm js-prod-page" data-page="'.$next.'" '.$disNext.'>다음</button> ';
        $html .= '  </div>';
        $html .= '</div>';

        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-condensed table-bordered table-hover">';
        $html .= '<thead><tr>';
        $html .= '<th>출발</th><th>예약</th><th>예약코드</th><th>고객</th><th>상품명</th>';
        $html .= '<th class="text-right">총액</th><th class="text-right">미수</th><th>상태</th><th>결제</th>';
        $html .= '</tr></thead><tbody>';

        if (count($rows) <= 0) {
            $html .= '<tr><td colspan="9" class="text-center text-muted">데이터 없음</td></tr>';
        } else {
            for ($i=0; $i<count($rows); $i++) {
                $r = $rows[$i];

                // 상태 배지: READY/DONE/CANCEL + 결제상태
                $st = (string)$r['rev_status'];
                $pay = (string)$r['payment_st'];

                $stBadge = $fnBadge($st, ($st==='DONE')?'ok':(($st==='READY')?'warn':'danger'));
                $payBadge = $fnBadge($pay, ($pay==='DONE')?'ok':(($pay==='READY')?'warn':'danger'));

                $html .= '<tr class="js-open-reserve" data-reservecode="'.h($r['reserveCode']).'" style="cursor:pointer;">';
                $html .= '<td>'.h($r['stDate']).'</td>';
                $html .= '<td>'.h($r['revDate']).'</td>';
                $html .= '<td><b>'.h($r['reserveCode']).'</b></td>';
                $html .= '<td>'.h($r['book_pri']).'</td>';
                $html .= '<td>'.h(strip_tags($r['p_name'])).'</td>';
                $html .= '<td class="text-right">$ '.money($r['last_total']).'</td>';
                $html .= '<td class="text-right">$ '.money($r['last_bal']).'</td>';
                $html .= '<td>'.$stBadge.'</td>';
                $html .= '<td>'.$payBadge.'</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table></div>';

        // 페이지 정보 + JS가 읽을 hidden
        $html .= '<input type="hidden" class="js-prod-pcode" value="'.h($p_code).'">';
        $html .= '<input type="hidden" class="js-prod-pageval" value="'.h($page).'">';
        $html .= '<input type="hidden" class="js-prod-perval" value="'.h($perPage).'">';

        $html .= '<div class="text-muted" style="font-size:12px;">행 클릭 시 해당 예약 상세를 다시 모달로 엽니다.</div>';

        $out['html'] = $html;
        echo json_encode($out); exit;
    }

    $out['title'] = '알림';
    $out['html'] = '<div class="text-muted">지원하지 않는 요청입니다.</div>';
    echo json_encode($out); exit;
}

/* ============================================================
 * KPI Aggregations (✅ 매출/미수: 선택일자 기준 통일)
 * ============================================================ */
$kpiSql = "
    SELECT
      COUNT(*) AS cnt,
      IFNULL(SUM(last_total),0) AS sales,
      IFNULL(SUM(last_bal),0)   AS ar_bal
    FROM reserve_info
    WHERE {$BASE_WHERE}
";
$kpi = db_one($kpiSql);

$cnt    = (int)($kpi ? $kpi['cnt'] : 0);
$sales  = (float)($kpi ? $kpi['sales'] : 0);
$ar_bal = (float)($kpi ? $kpi['ar_bal'] : 0);

/* 취소율(전체 대비 CANCEL) - 날짜 기준도 선택일자로 맞춤 */
$cancelAgg = db_one("
    SELECT
      SUM(CASE WHEN rev_status='CANCEL' THEN 1 ELSE 0 END) AS cancel_cnt,
      COUNT(*) AS total_cnt
    FROM reserve_info
    WHERE parent='MAIN' AND {$DATE_COL} BETWEEN '{$sDate}' AND '{$eDate}'
");
$cancelCnt = (int)($cancelAgg ? $cancelAgg['cancel_cnt'] : 0);
$totalCnt  = (int)($cancelAgg ? $cancelAgg['total_cnt'] : 0);

$cancelRate = ($totalCnt > 0) ? round(($cancelCnt / $totalCnt) * 100, 2) : 0;
$avgTicket  = ($cnt > 0) ? round($sales / $cnt, 2) : 0;

/* ============================================================
 * ✅ 수금: 선택일자 기준 통일 (reserve_info와 JOIN)
 * ============================================================ */
$paidSql = "
    SELECT IFNULL(SUM(ph.payment),0) AS paid
    FROM payment_history ph
    INNER JOIN reserve_info ri
        ON ri.reserveCode = ph.reserveCode
    WHERE ph.pay_method!='init'
      AND {$BASE_WHERE_RI}
";
$paidRow = db_one($paidSql);
$paid = (float)($paidRow ? $paidRow['paid'] : 0);

/* 결제완료율: 선택일자 기준 통일 */
$doneSql = "
    SELECT
      SUM(CASE WHEN ri.payment_st='DONE' THEN 1 ELSE 0 END) AS done_cnt,
      COUNT(*) AS tot_cnt
    FROM reserve_info ri
    WHERE {$BASE_WHERE_RI}
";
$doneRow = db_one($doneSql);
$doneCnt = (int)($doneRow ? $doneRow['done_cnt'] : 0);
$totCnt2 = (int)($doneRow ? $doneRow['tot_cnt'] : 0);
$payDoneRate = ($totCnt2 > 0) ? round(($doneCnt / $totCnt2) * 100, 2) : 0;

/* ============================================================
 * Chart Data (✅ KPI 기준과 동일하게 선택일자+READY/DONE 통일)
 * ============================================================ */

/* 월별 매출(최근 12개월) - 선택일자 기준 */
$baseYm = date('Y-m', strtotime($sDate));
$baseYmEsc = esc($baseYm);

$chartSalesSql = "
    SELECT DATE_FORMAT({$DATE_COL},'%Y-%m') AS ym,
           IFNULL(SUM(last_total),0) AS sales
    FROM reserve_info
    WHERE parent='MAIN'
      AND rev_status IN ('READY','DONE')
      AND {$DATE_COL} >= DATE_FORMAT(DATE_SUB(STR_TO_DATE(CONCAT('{$baseYmEsc}', '-01'), '%Y-%m-%d'), INTERVAL 11 MONTH), '%Y-%m-01')
      AND {$DATE_COL} <= '{$eDate}'
    GROUP BY ym
    ORDER BY ym ASC
";
$chartSales = db_all($chartSalesSql);

/* 결제수단 비중: 선택일자 기준 */
$chartPayMethodSql = "
    SELECT ph.pay_method, IFNULL(SUM(ph.payment),0) AS amt
    FROM payment_history ph
    INNER JOIN reserve_info ri
        ON ri.reserveCode = ph.reserveCode
    WHERE ph.pay_method!='init'
      AND {$BASE_WHERE_RI}
    GROUP BY ph.pay_method
    ORDER BY amt DESC
";
$chartPayMethod = db_all($chartPayMethodSql);

/* 상품 TOP10: 선택일자 기준 */
$chartTopProductsSql = "
    SELECT p_code, p_name,
           IFNULL(SUM(last_total),0) AS sales
    FROM reserve_info
    WHERE {$BASE_WHERE}
    GROUP BY p_code, p_name
    ORDER BY sales DESC
    LIMIT 10
";
$chartTopProducts = db_all($chartTopProductsSql);

/* 지사(동/서) 비중 도넛 - 선택일자 기준 */
$chartRegionSql = "
    SELECT
      CASE
        WHEN pm.m_dept LIKE '%D021500%' THEN '서부'
        WHEN pm.m_dept LIKE '%D020500%' THEN '동부'
        ELSE '기타'
      END AS region,
      IFNULL(SUM(ri.last_total),0) AS sales
    FROM reserve_info ri
    LEFT JOIN product_master pm ON pm.p_code = ri.p_code
    WHERE {$BASE_WHERE_RI}
    GROUP BY region
    ORDER BY sales DESC
";
$chartRegion = db_all($chartRegionSql);

/* ============================================================
 * Red Zone (✅ 선택일자 기준 통일)
 * ============================================================ */
$rzAr = db_all("
    SELECT reserveCode, stDate, revDate, p_code, p_name, last_bal
    FROM reserve_info
    WHERE {$BASE_WHERE}
      AND last_bal > 0
    ORDER BY last_bal DESC
    LIMIT 10
");

/* 취소율 높은 상품 TOP - 선택일자 기준 */
$rzCancelByProd = db_all("
    SELECT p_code, p_name,
           SUM(CASE WHEN rev_status='CANCEL' THEN 1 ELSE 0 END) AS c_cnt,
           COUNT(*) AS t_cnt
    FROM reserve_info
    WHERE parent='MAIN' AND {$DATE_COL} BETWEEN '{$sDate}' AND '{$eDate}'
    GROUP BY p_code, p_name
    HAVING t_cnt >= 5
    ORDER BY (c_cnt / t_cnt) DESC
    LIMIT 10
");

/* 7일 구간(선택일자 기준) */
$todayEsc = esc($today);
$rzNearTitle = ($date_basis === 'rev') ? '최근 7일 예약 미수' : '임박 7일 출발 미수';

$rzNear = db_all("
    SELECT reserveCode, stDate, revDate, p_code, p_name, payment_st, last_bal
    FROM reserve_info
    WHERE parent='MAIN'
      AND rev_status IN ('READY','DONE')
      AND {$DATE_COL} BETWEEN '{$todayEsc}' AND DATE_ADD('{$todayEsc}', INTERVAL 7 DAY)
      AND last_bal > 0
    ORDER BY {$DATE_COL} ASC
    LIMIT 10
");

/* ============================================================
 * Prepare JS datasets
 * ============================================================ */
$jsSalesLabels = array(); $jsSalesData = array();
for ($i=0; $i<count($chartSales); $i++) { $jsSalesLabels[] = $chartSales[$i]['ym']; $jsSalesData[] = (float)$chartSales[$i]['sales']; }

$jsPayLabels = array(); $jsPayData = array();
for ($i=0; $i<count($chartPayMethod); $i++) { $label = $chartPayMethod[$i]['pay_method']; if ($label === '' || $label === null) $label='UNKNOWN'; $jsPayLabels[]=$label; $jsPayData[]=(float)$chartPayMethod[$i]['amt']; }

$jsTopLabels = array(); $jsTopData = array();
for ($i=0; $i<count($chartTopProducts); $i++) { $nm=strip_tags($chartTopProducts[$i]['p_name']); if ($nm===''||$nm===null) $nm=$chartTopProducts[$i]['p_code']; $jsTopLabels[]=$nm; $jsTopData[]=(float)$chartTopProducts[$i]['sales']; }

$jsRegionLabels = array(); $jsRegionData = array();
for ($i=0; $i<count($chartRegion); $i++) { $lab=$chartRegion[$i]['region']; if ($lab===''||$lab===null) $lab='기타'; $jsRegionLabels[]=$lab; $jsRegionData[]=(float)$chartRegion[$i]['sales']; }

?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>EIS (Executive MIS)</title>

<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/jquery@1.12.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
  html, body { height:100%; }
  body { background:#f5f7fb; margin:0; overflow:hidden; }

  .wrap { height: 100vh; display: flex; flex-direction: column; padding: 10px; }

  .filterbar {
    background:#fff; border-radius:10px; padding:10px 12px;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
    position: sticky; top: 0; z-index: 999; flex: 0 0 auto;
  }

  .content {
    flex: 1 1 auto; min-height: 0;
    background:#fff; border-radius:10px; padding:12px;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
    display: flex; flex-direction: column; overflow: hidden; margin-top: 10px;
  }

  .row-tight { margin-left:-6px; margin-right:-6px; }
  .row-tight > [class*='col-'] { padding-left:6px; padding-right:6px; }

  .page-title { margin:0 0 8px; font-weight:900; }

  .kpi { border:1px solid #e8edf5; border-radius:12px; padding:10px 12px; margin-bottom:10px; background:#fff; }
  .kpi .labelx { color:#6b778c; font-size:12px; }
  .kpi .val { font-size:22px; font-weight:900; margin-top:2px; }
  .kpi .sub { color:#97a0af; font-size:12px; margin-top:2px; }

  .panelx { border:1px solid #e8edf5; border-radius:12px; padding:10px 12px; background:#fff; }
  .panelx h4 { margin:0 0 6px; font-weight:900; font-size:14px; }

  .topArea { flex: 0 0 auto; min-height: 0; overflow: visible; display: flex; flex-direction: column; }


  
  .chartGrid{
	  flex: 0 0 auto;
	  display:flex; flex-wrap:wrap; gap:10px;
	  overflow: visible;           /* ✅ 잘림 방지 */
	  padding-right:6px;
	  max-height: none;            /* ✅ 42vh 제한 제거 */
	}


  .chartCard { flex: 1 1 calc(50% - 10px); min-width: 0; display: flex; flex-direction: column; overflow: hidden; }
  .chartBox { flex: 0 0 auto; height: 190px; position: relative; }
  .chartBox canvas { width: 100% !important; height: 100% !important; max-width: 100%; max-height: 100%; }

  .rz {
    flex: 1 1 auto;
    min-height: 260px;
    border:1px solid #ffd6d6; background:#fff5f5; border-radius:12px;
    padding:10px 12px; margin-top: 10px;
    overflow: hidden; display:flex; flex-direction: column;
  }
  .rz h4 { margin:0 0 8px; font-weight:900; color:#b00020; }
  .rzBody { flex: 1 1 auto; min-height:0; overflow:auto; }
   
  .rz { flex: 1 1 auto; min-height: 260px; max-height: 42vh; ... }
  .rzBody { flex: 1 1 auto; min-height:0; overflow:auto; }

  .table>thead>tr>th { background:#f3f6ff; }

  .btn-blue { background:#1f2d86; color:#fff; border-color:#1f2d86; }
  .btn-blue:hover { background:#172063; color:#fff; }

  .click-row { cursor:pointer; }
  .click-row:hover { background:#fff0f0; }

  @media (max-width: 1100px){
    .chartCard{ flex: 1 1 100%; }
    .chartGrid{ max-height: 45vh; }
    .chartBox{ height: 200px; }
  }
</style>
</head>

<body>
<div class="wrap">

  <div class="filterbar">
    <form class="form-inline" method="get" style="margin:0;">
      <div class="form-group">
        <label>기준일</label>
        <select name="date_basis" class="form-control">
          <option value="st"  <?php echo ($date_basis==='st')?'selected':''; ?>>출발일</option>
          <option value="rev" <?php echo ($date_basis==='rev')?'selected':''; ?>>예약일</option>
        </select>
      </div>

      <div class="form-group" style="margin-left:8px;">
        <label>기간</label>
        <select name="mode" class="form-control">
          <option value="month" <?php echo ($mode==='month')?'selected':''; ?>>월별</option>
          <option value="custom" <?php echo ($mode==='custom')?'selected':''; ?>>직접선택</option>
        </select>
      </div>

      <div class="form-group" style="margin-left:8px;">
        <label>월</label>
        <input type="number" name="year" class="form-control" style="width:90px" value="<?php echo h($viewYear); ?>">
        <input type="number" name="month" class="form-control" style="width:70px" value="<?php echo h((int)$viewMonth); ?>">
      </div>

      <div class="form-group" style="margin-left:8px;">
        <label>시작</label>
        <input type="date" name="s" class="form-control" value="<?php echo h($sDate); ?>">
      </div>

      <div class="form-group" style="margin-left:8px;">
        <label>종료</label>
        <input type="date" name="e" class="form-control" value="<?php echo h($eDate); ?>">
      </div>

      <button type="submit" class="btn btn-blue" style="margin-left:8px;">적용</button>

      <span class="text-muted" style="margin-left:10px;">
        기준: <?php echo h($DATE_LABEL); ?> / <?php echo h($sDate); ?> ~ <?php echo h($eDate); ?>
      </span>
    </form>
  </div>

  <div class="content">
    <div class="topArea">
      <h3 class="page-title">Executive Overview</h3>

      <!-- KPI -->
      <div class="row row-tight">
        <div class="col-sm-4">
          <div class="kpi">
            <div class="labelx">매출 (<?php echo h($DATE_LABEL); ?> 기준)</div>
            <div class="val">$ <?php echo money($sales); ?></div>
            <div class="sub">예약건수: <?php echo intfmt($cnt); ?>건 </div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="kpi">
            <div class="labelx">수금 (<?php echo h($DATE_LABEL); ?> 기준)</div>
            <div class="val">$ <?php echo money($paid); ?></div>
            <div class="sub">결제금액 합 </div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="kpi">
            <div class="labelx">미수금 (<?php echo h($DATE_LABEL); ?> 기준)</div>
            <div class="val">$ <?php echo money($ar_bal); ?></div>
            <div class="sub">잔액 합 </div>
          </div>
        </div>
      </div>

      <div class="row row-tight">
        <div class="col-sm-4">
          <div class="kpi">
            <div class="labelx">취소율 (<?php echo h($DATE_LABEL); ?> 기준)</div>
            <div class="val"><?php echo h($cancelRate); ?>%</div>
            <div class="sub">취소 <?php echo intfmt($cancelCnt); ?> / 전체 <?php echo intfmt($totalCnt); ?></div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="kpi">
            <div class="labelx">평균 객단가</div>
            <div class="val">$ <?php echo money($avgTicket); ?></div>
            <div class="sub">매출/예약건</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="kpi">
            <div class="labelx">결제완료율</div>
            <div class="val"><?php echo h($payDoneRate); ?>%</div>
            <div class="sub"></div>
          </div>
        </div>
      </div>

      <!-- Charts (2x2) -->
      <div class="chartGrid">
        <div class="panelx chartCard">
          <h4>월별 매출 추이 (최근 12개월 / <?php echo h($DATE_LABEL); ?> 기준)</h4>
          <div class="chartBox"><canvas id="chSales"></canvas></div>
        </div>

        <div class="panelx chartCard">
          <h4>결제수단 비중 (기간 내 / <?php echo h($DATE_LABEL); ?> 기준)</h4>
          <div class="chartBox"><canvas id="chPay"></canvas></div>
        </div>

        <div class="panelx chartCard">
          <h4>지사 비중 (동부/서부 / <?php echo h($DATE_LABEL); ?> 기준)</h4>
          <div class="chartBox"><canvas id="chRegion"></canvas></div>
        </div>

        <div class="panelx chartCard">
          <h4>상품 TOP 10 (기간 내 매출 / <?php echo h($DATE_LABEL); ?> 기준)</h4>
          <div class="chartBox"><canvas id="chTop"></canvas></div>
        </div>
      </div>
    </div>

    <!-- Red Zone -->
    <div class="rz">
      <h4>RED ZONE (리스트 클릭 → 모달 상세)</h4>
      <div class="rzBody">
        <div class="row row-tight">
          <div class="col-sm-4">
            <h5 style="font-weight:900;color:#b00020;margin-top:0;">미수금 TOP 10</h5>
            <table class="table table-condensed table-bordered">
              <thead><tr><th>출발</th><th>예약</th><th>상품</th><th class="text-right">미수</th></tr></thead>
              <tbody>
              <?php if (count($rzAr) <= 0) { ?>
                <tr><td colspan="4" class="text-center text-muted">데이터 없음</td></tr>
              <?php } else { ?>
                <?php for($i=0;$i<count($rzAr);$i++){ ?>
                  <tr class="click-row js-open-reserve" data-reservecode="<?php echo h($rzAr[$i]['reserveCode']); ?>">
                    <td><?php echo h($rzAr[$i]['stDate']); ?></td>
                    <td><?php echo h($rzAr[$i]['revDate']); ?></td>
                    <td title="<?php echo h(strip_tags($rzAr[$i]['p_name'])); ?>"><?php echo h(strip_tags($rzAr[$i]['p_name'])); ?></td>
                    <td class="text-right">$ <?php echo money($rzAr[$i]['last_bal']); ?></td>
                  </tr>
                <?php } ?>
              <?php } ?>
              </tbody>
            </table>
            <div class="text-muted" style="font-size:12px;">행 클릭 → 해당 예약 상세</div>
          </div>

          <div class="col-sm-4">
            <h5 style="font-weight:900;color:#b00020;margin-top:0;">취소율 높은 상품 TOP</h5>
            <table class="table table-condensed table-bordered">
              <thead><tr><th>상품</th><th class="text-right">취소율</th></tr></thead>
              <tbody>
              <?php if (count($rzCancelByProd) <= 0) { ?>
                <tr><td colspan="2" class="text-center text-muted">데이터 없음</td></tr>
              <?php } else { ?>
                <?php for($i=0;$i<count($rzCancelByProd);$i++){
                  $c = (int)$rzCancelByProd[$i]['c_cnt'];
                  $t = (int)$rzCancelByProd[$i]['t_cnt'];
                  $rate = ($t>0) ? round(($c/$t)*100,2) : 0;
                ?>
                  <tr class="click-row js-open-product" data-pcode="<?php echo h($rzCancelByProd[$i]['p_code']); ?>">
                    <td title="<?php echo h(strip_tags($rzCancelByProd[$i]['p_name'])); ?>"><?php echo h(strip_tags($rzCancelByProd[$i]['p_name'])); ?></td>
                    <td class="text-right"><?php echo h($rate); ?>% (<?php echo intfmt($c); ?>/<?php echo intfmt($t); ?>)</td>
                  </tr>
                <?php } ?>
              <?php } ?>
              </tbody>
            </table>
            <div class="text-muted" style="font-size:12px;">행 클릭 → 해당 상품 예약 리스트(전체)</div>
          </div>

          <div class="col-sm-4">
            <h5 style="font-weight:900;color:#b00020;margin-top:0;"><?php echo h($rzNearTitle); ?> (READY/DONE)</h5>
            <table class="table table-condensed table-bordered">
              <thead><tr><th>출발</th><th>예약</th><th>상품</th><th class="text-right">미수</th></tr></thead>
              <tbody>
              <?php if (count($rzNear) <= 0) { ?>
                <tr><td colspan="4" class="text-center text-muted">데이터 없음</td></tr>
              <?php } else { ?>
                <?php for($i=0;$i<count($rzNear);$i++){ ?>
                  <tr class="click-row js-open-reserve" data-reservecode="<?php echo h($rzNear[$i]['reserveCode']); ?>">
                    <td><?php echo h($rzNear[$i]['stDate']); ?></td>
                    <td><?php echo h($rzNear[$i]['revDate']); ?></td>
                    <td title="<?php echo h(strip_tags($rzNear[$i]['p_name'])); ?>"><?php echo h(strip_tags($rzNear[$i]['p_name'])); ?></td>
                    <td class="text-right">$ <?php echo money($rzNear[$i]['last_bal']); ?></td>
                  </tr>
                <?php } ?>
              <?php } ?>
              </tbody>
            </table>
            <div class="text-muted" style="font-size:12px;">행 클릭 → 해당 예약 상세</div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- content -->
</div><!-- wrap -->

<!-- Modal -->
<div class="modal fade" id="misModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" style="width:90%;max-width:1200px;">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title" id="misModalTitle">상세</h4>
      </div>
      <div class="modal-body" id="misModalBody">
        <div class="text-muted">로딩중...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var salesLabels  = <?php echo json_encode($jsSalesLabels); ?>;
  var salesData    = <?php echo json_encode($jsSalesData); ?>;
  var payLabels    = <?php echo json_encode($jsPayLabels); ?>;
  var payData      = <?php echo json_encode($jsPayData); ?>;
  var regionLabels = <?php echo json_encode($jsRegionLabels); ?>;
  var regionData   = <?php echo json_encode($jsRegionData); ?>;
  var topLabels    = <?php echo json_encode($jsTopLabels); ?>;
  var topData      = <?php echo json_encode($jsTopData); ?>;

  function makeLine(id, labels, data){
    var el = document.getElementById(id);
    if(!el) return;
    new Chart(el, {
      type: 'line',
      data: { labels: labels, datasets: [{ label: 'Sales', data: data, tension: 0.25 }] },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
    });
  }
  function makeDoughnut(id, labels, data){
    var el = document.getElementById(id);
    if(!el) return;
    new Chart(el, {
      type: 'doughnut',
      data: { labels: labels, datasets: [{ data: data }] },
      options: { responsive:true, maintainAspectRatio:false }
    });
  }
  function makeBar(id, labels, data){
    var el = document.getElementById(id);
    if(!el) return;
    new Chart(el, {
      type: 'bar',
      data: { labels: labels, datasets: [{ label:'Sales', data:data }] },
      options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}},
        scales:{ x:{ ticks:{ autoSkip:false, maxRotation:45, minRotation:0 } } }
      }
    });
  }

  makeLine('chSales', salesLabels, salesData);
  makeDoughnut('chPay', payLabels, payData);
  makeDoughnut('chRegion', regionLabels, regionData);
  makeBar('chTop', topLabels, topData);

  function openModal(title, url){
    $('#misModalTitle').text(title || '상세');
    $('#misModalBody').html('<div class="text-muted">로딩중...</div>');
    $('#misModal').modal('show');

    $.get(url, function(res){
      if(res && res.ok){
        $('#misModalTitle').text(res.title || title || '상세');
        $('#misModalBody').html(res.html || '<div class="text-muted">내용 없음</div>');
      }else{
        $('#misModalTitle').text((res && res.title) ? res.title : '오류');
        $('#misModalBody').html((res && res.html) ? res.html : '<div class="text-danger">조회 실패</div>');
      }
    }, 'json').fail(function(){
      $('#misModalTitle').text('오류');
      $('#misModalBody').html('<div class="text-danger">서버 통신 실패</div>');
    });
  }

  function qsBase(){
    return '&date_basis=<?php echo h($date_basis); ?>'
         + '&mode=<?php echo h($mode); ?>&year=<?php echo h($viewYear); ?>&month=<?php echo h($viewMonth); ?>'
         + '&s=<?php echo h($sDate); ?>&e=<?php echo h($eDate); ?>';
  }

  function openProduct(pcode, page, per){
    if(!pcode) return;
    page = page || 1;
    per  = per || 200;
    var url = '?ajax=1&kind=product_reservations&p_code=' + encodeURIComponent(pcode)
            + '&page=' + encodeURIComponent(page)
            + '&per='  + encodeURIComponent(per)
            + qsBase();
    openModal('상품 예약 리스트', url);
  }

  $(document).on('click', '.js-open-reserve', function(){
    var reserveCode = $(this).data('reservecode');
    if(!reserveCode) return;
    var url = '?ajax=1&kind=reserve_detail&reserveCode=' + encodeURIComponent(reserveCode) + qsBase();
    openModal('예약 상세', url);
  });

  $(document).on('click', '.js-open-product', function(){
    var pcode = $(this).data('pcode');
    if(!pcode) return;
    openProduct(pcode, 1, 200);
  });

  // 모달 안에서 예약 상세 다시 열기
  $(document).on('click', '#misModalBody .js-open-reserve', function(){
    var reserveCode = $(this).data('reservecode');
    if(!reserveCode) return;
    var url = '?ajax=1&kind=reserve_detail&reserveCode=' + encodeURIComponent(reserveCode) + qsBase();
    openModal('예약 상세', url);
  });

  // ✅ 모달 안 페이지 이동
  $(document).on('click', '#misModalBody .js-prod-page', function(){
    var p = $(this).data('page');
    var pcode = $('#misModalBody .js-prod-pcode').val();
    var per = parseInt($('#misModalBody .js-prod-per').val() || $('#misModalBody .js-prod-perval').val() || '200', 10);
    openProduct(pcode, p, per);
  });

  // ✅ 모달 안 per 변경
  $(document).on('change', '#misModalBody .js-prod-per', function(){
    var pcode = $('#misModalBody .js-prod-pcode').val();
    var per = parseInt($(this).val() || '200', 10);
    openProduct(pcode, 1, per);
  });

})();
</script>

</body>
</html>
