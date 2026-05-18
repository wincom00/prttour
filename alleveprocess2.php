<?php
include 'include/inc_base.php';

/*
 * Script:    DataTables server-side script for PHP and MySQL
 * Copyright: 2010 - Allan Jardine, 2012 - Chris Wright
 * License:   GPL v2 or BSD (3-point)
 */

function fatal_error( $sErrorMessage = '' )
{
    header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
    die( $sErrorMessage );
}

$bColumns = array(
    'reserveCode',
    'book_pri',
    'p_cnt',
    'last_total',
    'last_bal',
    'stDate',
    'revDate',
    'rev_status',
    'userid',
    'progress'
);

$sIndexColumn = "a.wdate";

/* Paging */
$sLimit = "";
if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
{
    $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".intval( $_GET['iDisplayLength'] );
}

/* Ordering */
if ( isset( $_POST['iSortCol_0'] ) )
{
    $sOrder = "ORDER BY  ";
    for ( $i=0 ; $i<intval( $_POST['iSortingCols'] ) ; $i++ )
    {
        if ( $_POST[ 'bSortable_'.intval($_POST['iSortCol_'.$i]) ] == "true" )
        {
            $sOrder .= $bColumns[ intval( $_POST['iSortCol_'.$i] ) ]."
                ".($_POST['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
        }
    }
    $sOrder = substr_replace( $sOrder, "", -2 );
    if ( $sOrder == "ORDER BY" ) {
        $sOrder = "";
    }
}

/* Filters */
$sWhere = "";
if ($_GET['startDate1'] != "") {
    $start = date("Y-m-d", strtotime($_GET['startDate1']));
    $sWhere .= " && a.stDate = '$start'";
}
if ($_GET['pcode'] != "") {
    $sWhere .= " && a.p_code = '".$_GET['pcode']."'";
}
if ($_GET['kindEvent'] != "") {
    if ($_GET['kindEvent'] == 1) {
        $sWhere .= " && a.rev_status in ('READY','ORDER','DONE')";
    } else if ($_GET['kindEvent'] == 2) {
        $sWhere .= " && a.rev_status in ('WAIT')";
    } else if ($_GET['kindEvent'] == 3) {
        $sWhere .= " && a.rev_status in ('CANCEL')";
    }
} else {
    $sWhere .= " && a.rev_status not in ('CANCEL')";
}

/* SQL queries */
$sQuery = "select SQL_CALC_FOUND_ROWS
                a.tour_type,
                '$tourCategory' as p_type1,
                a.reserveCode,
                a.book_pri,
                a.last_total,
                a.last_bal,
                a.stDate,
                a.revDate,
                a.wdate,
                a.rev_status,
                a.userid,
                a.progress,
                a.p_cnt
    from reserve_info a, product_master b
    where a.p_code=b.p_code
    $sWhere
    $sOrder
    $sLimit";

$rResult = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );

/* Total after filtering */
$sQuery = "SELECT FOUND_ROWS()";
$rResultFilterTotal = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
$aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
$iFilteredTotal = $aResultFilterTotal[0];

/* Total without filtering */
$sQuery = "select SQL_CALC_FOUND_ROWS COUNT($sIndexColumn)
    from reserve_info a, product_master b
    where a.p_code=b.p_code
    $sWhere";
$rResultTotal = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
$aResultTotal = mysql_fetch_array($rResultTotal);
$iTotal = $aResultTotal[0];

/* Output */
$output = array(
    "sEcho"                => intval($_GET['sEcho']),
    "iTotalRecords"        => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData"               => array()
);

$memberCache = array();

while ( $aRow = mysql_fetch_array( $rResult ) )
{
    $row = array();

    /* tour_type → sub/ty/pricet (루프 밖에서 한 번만 계산) */
    if ($aRow['tour_type'] == '1') {
        $ty = 1; $pricet = 1; $sub = 15;
    } else if ($aRow['tour_type'] == '2') {
        $ty = 2; $pricet = 2; $sub = 20;
    } else if ($aRow['tour_type'] == '3') {
        $ty = 3; $pricet = 3; $sub = 25;
    }

    /* 대표 여행자명 (루프 밖에서 한 번만) */
    $trnm = getReserveTrRepre($aRow['reserveCode']);
    $aRow['book_pri'] = $trnm['traveler_nm'];

    /* 예약 상태 → 한글 레이블 */
    $statusMap = array(
        'READY'  => "<font color=red>예약접수</font>",
        'DONE'   => "<font color=red>예약확정</font>",
        'CANCEL' => "<font color=red>예약취소</font>",
    );
    if (isset($statusMap[$aRow['rev_status']])) {
        $aRow['rev_status'] = $statusMap[$aRow['rev_status']];
    }

    /* 담당자명 — userid 별 캐시로 중복 DB 호출 방지 */
    $uid = $aRow['userid'];
    if (!isset($memberCache[$uid])) {
        $memberCache[$uid] = getinfo_dbMember($uid);
    }
    $aRow['userid'] = $memberCache[$uid]['kor_name'];

    $link = "base_reservation_m.php?estimateCode={$aRow['reserveCode']}&division=3&pdx=2&sub=$sub&ty=$ty&pricet=$pricet#TOP";

    for ( $i=0; $i<count($bColumns); $i++ )
    {
        if ($i == 9) {
            $row[] = "<a href='$link' target='_blank'>".$aRow['progress']."</a>";
        } else {
            $row[] = "<a href='$link' target='_blank'>".$aRow[ $bColumns[$i] ]."</a>";
        }
    }

    $output['aaData'][] = $row;
}

echo json_encode( $output );
?>
