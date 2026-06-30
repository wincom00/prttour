<?php
include 'include/inc_base.php';

/**
 * DataTables server-side (mysql_* 유지 + 속도개선 + 페이징)
 * - PHP 5.x/legacy 환경 가정
 */

function fatal_error($msg='') {
  header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');
  die($msg);
}

// DataTables에서 사용하는 컬럼 맵(SELECT 순서와 동일)
$bColumns = array(
  'tour_type',     // 0
  'grand_revNo',   // 1
  'reserveCode',   // 2
  'p_name',        // 3
  'book_pri',      // 4
  'p_cnt',         // 5
  'last_total',    // 6
  'last_bal',      // 7
  'p_code',        // 8
  'stDate',        // 9
  'revDate',       // 10
  'wdate',         // 11
  'rev_status',    // 12
  'userid',        // 13
  'pricet'         // 14 (출력은 안 하더라도 정렬에 필요할 수 있음)
);

// 인덱스/기본 정렬 컬럼
$defaultOrder = " ORDER BY a.grand_revNo DESC, a.wdate DESC ";

// 페이징
$sLimit = "";
if (isset($_POST['start']) && isset($_POST['length']) && $_POST['length'] != '-1') {
  $start = intval($_POST['start']);
  $len   = intval($_POST['length']);
  if ($len < 0 || $len > 500) $len = 50;
  $sLimit = " LIMIT $start, $len ";
} else if (isset($_POST['iDisplayStart']) && $_POST['iDisplayLength'] != '-1') {
  $start = intval($_POST['iDisplayStart']);
  $len   = intval($_POST['iDisplayLength']);
  // 안전망
  if ($len < 0 || $len > 500) $len = 50;
  $sLimit = " LIMIT $start, $len ";
}

// 정렬
$sOrder = "";
if (isset($_POST['iSortCol_0'])) {
  $orders = array();
  $sortingCols = isset($_POST['iSortingCols']) ? intval($_POST['iSortingCols']) : 1;
  for ($i=0; $i<$sortingCols; $i++) {
    $colIdx = intval($_POST['iSortCol_'.$i]);
    $dir    = (isset($_POST['sSortDir_'.$i]) && $_POST['sSortDir_'.$i] === 'asc') ? 'ASC' : 'DESC';
    // 정렬 가능 여부 확인
    if (isset($_POST['bSortable_'.$colIdx]) && $_POST['bSortable_'.$colIdx] === "true") {
      if (isset($bColumns[$colIdx])) {
        // alias 붙은 컬럼은 a.로 한정
        $col = 'a.`'.$bColumns[$colIdx].'`';
        $orders[] = "$col $dir";
      }
    }
  }
  if (!empty($orders)) {
    $orders[] = "a.`grand_revNo` DESC";
    $orders[] = "a.`wdate` DESC";
    $sOrder = " ORDER BY ".implode(", ", $orders)." ";
  }
} else if (isset($_POST['order']) && is_array($_POST['order'])) {
  $orders = array();
  foreach ($_POST['order'] as $orderInfo) {
    if (!isset($orderInfo['column'])) continue;
    $colIdx = intval($orderInfo['column']);
    $dir    = (isset($orderInfo['dir']) && $orderInfo['dir'] === 'asc') ? 'ASC' : 'DESC';
    $orderable = true;
    if (isset($_POST['columns'][$colIdx]['orderable'])) {
      $orderable = ($_POST['columns'][$colIdx]['orderable'] === 'true');
    }
    if ($orderable && isset($bColumns[$colIdx])) {
      $col = 'a.`'.$bColumns[$colIdx].'`';
      $orders[] = "$col $dir";
    }
  }
  if (!empty($orders)) {
    $orders[] = "a.`grand_revNo` DESC";
    $orders[] = "a.`wdate` DESC";
    $sOrder = " ORDER BY ".implode(", ", $orders)." ";
  }
}
if ($sOrder === "") $sOrder = $defaultOrder;

// ------ 필터링(WHERE) ------
$userId = mysql_real_escape_string($user_dbinfo['userid']);
$where  = array();
$where[] = "a.parent='MAIN'";
$where[] = "a.userid='".$userId."'";

// GET 파라미터 안전 처리
$startDate1 = isset($_GET['startDate1']) ? trim($_GET['startDate1']) : '';
$cname      = isset($_GET['cname']) ? trim($_GET['cname']) : '';
$crev       = isset($_GET['crev']) ? trim($_GET['crev']) : '';
$cemail     = isset($_GET['cemail']) ? trim($_GET['cemail']) : '';
$ty         = isset($_GET['ty']) ? trim($_GET['ty']) : '';

// 출발일
if ($startDate1 !== '') {
  $start = date("Y-m-d", strtotime($startDate1));
  $where[] = "a.stDate = '".mysql_real_escape_string($start)."'";
}

// 예약자명/여행자명
$needTravelerJoin = false;
if ($cname !== '') {
  $kw = mysql_real_escape_string($cname);
  // book_pri(예약자) 또는 traveler_nm(여행자) 중 하나라도 like
  $where[] = "(a.book_pri LIKE '%$kw%' OR c.traveler_nm LIKE '%$kw%')";
  $needTravelerJoin = true;
}

// 예약번호
if ($crev !== '') {
  $kw = mysql_real_escape_string($crev);
  $where[] = "a.reserveCode LIKE '%$kw%'";
}

// 이메일
if ($cemail !== '') {
  $kw = mysql_real_escape_string($cemail);
  $where[] = "a.book_email LIKE '%$kw%'";
}

// 타입
if ($ty !== '') {
  $ty = mysql_real_escape_string($ty);
  if ($ty === '1') {
    $where[] = "a.tour_type='1' AND a.pricet='1'";
  } else if ($ty === '2') {
    $where[] = "a.tour_type='2'";
  } else if ($ty === '3') {
    $where[] = "(a.tour_type='3' OR a.pricet='3')";
  }
}

$sWhere = "";
if (!empty($where)) $sWhere = " WHERE ".implode(" AND ", $where)." ";

// ------ FROM / JOIN 구성 ------
// 기본은 traveler 조인 안 함. 이름 검색시에만 조인.
$from = " FROM reserve_info a ";
if ($needTravelerJoin) {
  $from .= " LEFT JOIN reserve_traveler c ON a.reserveCode = c.reserveCode ";
}

// ------ 총 레코드 수(iTotalRecords): 필터 미적용 전체 ------
$sqlTotal = "SELECT COUNT(*) AS cnt FROM reserve_info a WHERE a.parent='MAIN' AND a.userid='".$userId."'";
$resTotal = mysql_query($sqlTotal, $dbConn) or fatal_error('MySQL Error: '.mysql_errno());
$rowTotal = mysql_fetch_assoc($resTotal);
$iTotal   = intval($rowTotal['cnt']);

// ------ 필터 후 레코드 수(iTotalDisplayRecords) ------
// traveler 조인 시 중복 가능 → DISTINCT reserveCode 기준으로 세는 게 안전
if ($needTravelerJoin) {
  $sqlFiltered = "SELECT COUNT(DISTINCT a.reserveCode) AS cnt $from $sWhere";
} else {
  $sqlFiltered = "SELECT COUNT(*) AS cnt $from $sWhere";
}
$resFiltered = mysql_query($sqlFiltered, $dbConn) or fatal_error('MySQL Error: '.mysql_errno());
$rowFiltered = mysql_fetch_assoc($resFiltered);
$iFilteredTotal = intval($rowFiltered['cnt']);

// ------ 실제 데이터 조회 ------
// DISTINCT는 traveler 조인 시에만 필요(중복 방지)
$distinct = $needTravelerJoin ? "DISTINCT" : "";
$select = "
  SELECT $distinct
    a.tour_type,
    a.grand_revNo,
    a.reserveCode,
    a.p_name,
    a.book_pri,
    a.p_cnt,
    a.last_total,
    a.last_bal,
    a.p_code,
    a.stDate,
    a.revDate,
    a.wdate,
    a.rev_status,
    a.userid,
    a.pricet
";

$sqlData = $select . $from . $sWhere . $sOrder . $sLimit;
// echo $sqlData; exit;
$rResult = mysql_query($sqlData, $dbConn) or fatal_error('MySQL Error: '.mysql_errno());

// ------ 행 렌더링 ------
$output = array(
  "sEcho" => intval(isset($_POST['sEcho']) ? $_POST['sEcho'] : 1),
  "iTotalRecords" => $iTotal,
  "iTotalDisplayRecords" => $iFilteredTotal,
  "aaData" => array()
);

// 간단 캐시: p_code → p_own → rname
$pCache = array();
$rnameCache = array();

while ($aRow = mysql_fetch_assoc($rResult)) {
  // 표시값 가공
  // 0: tour_type
  if ($aRow['tour_type'] == '1') $aRow['tour_type'] = '직접예약';
  else if ($aRow['tour_type'] == '2') $aRow['tour_type'] = '웹예약';
  else if ($aRow['tour_type'] == '3') $aRow['tour_type'] = '업체예약';
  if ($aRow['tour_type'] == '업체예약' && $aRow['pricet'] == '3') {
    $aRow['tour_type'] = '업체예약';
  }

  // 8: p_code → 소유사명 변환(캐시)
  $pcode = $aRow['p_code'];
  if ($pcode !== '') {
    if (!isset($pCache[$pcode])) {
      $pInfo = getProductMaster($pcode);
      $pCache[$pcode] = $pInfo; // p_own 포함
    }
    $pInfo = $pCache[$pcode];
    if (isset($pInfo['p_own']) && $pInfo['p_own'] == "purun") {
      $aRow['p_code'] = "푸른투어";
    } else {
      $own = isset($pInfo['p_own']) ? $pInfo['p_own'] : '';
      if ($own !== '') {
        if (!isset($rnameCache[$own])) {
          $rnameCache[$own] = randname($own);
        }
        $rname = $rnameCache[$own];
        $aRow['p_code'] = isset($rname['kor_name']) ? $rname['kor_name'] : $own;
      }
    }
  }

  // 12: rev_status 색상
  if ($aRow['rev_status'] == 'READY') {
    $aRow['rev_status'] = "<font color='#0984a3'>예약접수</font>";
  } else if ($aRow['rev_status'] == 'DONE') {
    $aRow['rev_status'] = "<font color='#911f77'>예약확정</font>";
  } else if ($aRow['rev_status'] == 'CANCEL') {
    $aRow['rev_status'] = "<font color='#e02133'>예약취소</font>";
  }

  // 행 구성: DataTables가 요구하는 순서대로 링크 감싸기
  $row = array();
  for ($i=0; $i<count($bColumns)-1; $i++) { // pricet(14)는 숨김이라면 제외
    $colName = $bColumns[$i];
    $val = isset($aRow[$colName]) ? $aRow[$colName] : '';
    $href = "base_reservation_m.php?estimateCode=".$aRow['reserveCode']."&division=$division&pdx=$pdx&sub=$sub&ty=$ty&pricet=".$aRow['pricet']."#TOP";
    $row[] = "<a href='$href' style='color:#000000'>".$val."</a>";
  }
  $output['aaData'][] = $row;
}

$output['draw'] = intval(isset($_POST['draw']) ? $_POST['draw'] : $output['sEcho']);
$output['recordsTotal'] = $iTotal;
$output['recordsFiltered'] = $iFilteredTotal;
$output['data'] = $output['aaData'];

echo json_encode($output);
