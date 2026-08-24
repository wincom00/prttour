<?php

	include "include/inc_base.php";

	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
		header("Location: ./login.php");
		exit;
	}

	// 에러/실행 환경
	// error_reporting(E_ALL); ini_set('display_errors',1);
	set_time_limit(60);               // 대용량 엑셀 방지
	ini_set('memory_limit','512M');   // 필요 시 상향

	$p_code = isset($_REQUEST['p_code']) ? $_REQUEST['p_code'] : (isset($p_code) ? $p_code : '');
	$s_code = isset($_REQUEST['s_code']) ? $_REQUEST['s_code'] : (isset($s_code) ? $s_code : '');
	$stdate = isset($_REQUEST['stdate']) ? $_REQUEST['stdate'] : (isset($stdate) ? $stdate : '');
	$gid    = isset($_REQUEST['gid']) ? $_REQUEST['gid'] : (isset($gid) ? $gid : '');

	$mode = isset($_POST['mode']) ? $_POST['mode'] : (isset($_GET['mode']) ? $_GET['mode'] : '');

	if ($mode === 'down') {
		// 어떤 출력도 하기 전에!
		$filename = (isset($_GET['s_code']) ? $_GET['s_code'] : 'export') . date('Ymd') . ".xls";
		header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"{$filename}\"");
		header("Pragma: no-cache");
		header("Expires: 0");
		// BOM (엑셀 한글 깨짐 방지)
		echo "\xEF\xBB\xBF";
	}


	$prodInfo   = getProductMaster($p_code ?: $s_code);
	$totpcnt    = getReserveInfoCntG($s_code,$stdate);
	$totroom    = getReserveInfoRoom($s_code,$stdate);
	$totbal     = getReserveInfoBalSS($s_code,$stdate);
	$totsal     = getReserveInfoSal($s_code,$stdate);
	$g_dbinfo1  = getguideInfor3($s_code);
	$g_dbinfo   = getinfo_dbMemberg($g_dbinfo1['guide_id']);
	$picM       = getPicGrM($s_code);
	$picStr     = getPicGr2($s_code,$stdate);
	$picctot    = $picM."&nbsp;&nbsp;".$picStr;
	$gss        = getGuideInfo2($s_code);
	$ccinfo     = getCarInfo($gss['c_id']);
	$bus_team   = codebaseName($ccinfo['bus_team']);
	$sign       = ($prodInfo['base_rate'] == "CAD") ? "C$" : "$";
	$s_date_arr = explode("-",$stdate);
	$add_date   = max(0,(int)$prodInfo['p_day']-2);
	$end_day    = date("Y-m-d",mktime(0,0,0,$s_date_arr[1],$s_date_arr[2]+$add_date,$s_date_arr[0]));

	function custlist() {
		global $dbConn, $p_code, $gid, $s_code, $stdate, $prodInfo, $mode;

		// 입력값 최소 이스케이프
		$s_code  = esc($s_code);
		$stdate  = esc($stdate);
		$pp_code = isset($prodInfo['p_code']) ? $prodInfo['p_code'] : '';

		// 정렬 분기
		if (in_array($pp_code, ['LAPICKUP','LVPICKUP','PICKUP','EWRPICKUP','SFOPICKUP','SLCPICKUP','ANCPICKUP','SANPICKUP','DFWPICKUP','LGAPICKUP','YULPICKUP'])) {
			$orderBy = " ORDER BY ri.air_arrivetime, ri.reserveCode, rt.traveler_room ASC ";
		} elseif (in_array($pp_code, ['LASENDING','LVSENDING','SENDING','EWRSENDING','SFOSENDING','SLCSENDING','ANCSENDING','YULSENDING','SANSENDING','LAXSENDING'])) {
			$orderBy = " ORDER BY ri.revDate, ri.reserveCode, rt.traveler_room, ri.air_sttime ASC ";
		} else {
			$orderBy = " ORDER BY ri.revDate, ri.reserveCode, rt.traveler_room, rt.pick_area, rt.seqint ASC ";
		}

		// 메인 조회 SQL (원본 로직 유지)
		// DISTINCT 를 붙이면 안 된다. reserve_traveler 행이 seqint 만 다르고 조회 컬럼이
		// 모두 같은 경우(예: 4명을 '이민주+3' 한 이름으로 등록) 1행으로 합쳐지는데,
		// 아래 d.cnt 는 COUNT(*)=4 라 rowspan 과 실제 행수가 어긋나 투어고객이 1명만 나온다.
		// reserve_info 는 reserveCode 당 1행이라 DISTINCT 없이도 중복이 생기지 않는다.
		$sql = "
			SELECT
				ri.pricet, d.cnt, ri.p_code, 
				ri.air_arcity, ri.air_arrivetime, ri.air_arriveNm,
				ri.air_stcity, ri.air_sttime, ri.air_stNm,
				ri.p_name, ri.reserveCode, ri.book_pri, ri.book_phone, 
				ri.rand_id, ri.dis_code, ri.air_stcity2, ri.air_arriveNm2, ri.air_arrivetime2, ri.air_arcity2,
				rt.traveler_room, rt.traveler_nm, rt.traveler_enm, rt.pass_num, rt.pass_date, rt.e_memo, rt.traveler_phone, 
				ri.room_cnt, ri.p_cnt AS pcnt, ri.last_bal, ri.stDate, ri.base_rate, ri.parent, ri.userid,
				rt.pick_area, ri.air_stNm2, ri.air_sttime2,ri.last_total
			FROM reserve_info ri
			JOIN reserve_traveler rt ON ri.reserveCode = rt.reserveCode
			JOIN (
				SELECT a.reserveCode, COUNT(*) AS cnt
				FROM reserve_info a
				JOIN reserve_traveler b ON a.reserveCode = b.reserveCode
				WHERE a.p_code = '{$s_code}'
				  AND a.stDate = '{$stdate}'
				  AND a.rev_status IN ('DONE')
				GROUP BY a.reserveCode
			) d ON ri.reserveCode = d.reserveCode
			WHERE ri.p_code = '{$s_code}'
			  AND ri.stDate = '{$stdate}'
			  AND ri.rev_status IN ('DONE')
			{$orderBy}
		";

		$rst1 = dbq($sql);

		$my_array2 = array("pick2");
		$my_array  = array("send2");
		$my_array3 = array("pick");
		$my_array4 = array("send");

		// 한 번만 조회
		$PICKUP  = getProductPick($pp_code);
		$SENDING = getProductSend($pp_code);

		$orev = "";
		$k = 0;

		// 반복 호출 캐시
		$busCache   = array();
		$guideCache = array();

		while ($row1 = mysql_fetch_assoc($rst1)) {
			$travelerNameKo = trim($row1['traveler_nm']);
			$travelerNameEn = trim($row1['traveler_enm']);
			$travelerNameBoth = $travelerNameKo;
			if ($travelerNameEn !== "") {
				$travelerNameBoth .= " / " . $travelerNameEn;
			}

			// 픽업/샌딩 표시
			$picnum = '--';
			if (($pp_code == $PICKUP['p_code']) && ($pp_code!="")) {
				if (in_array($row1['dis_code'], $my_array2)) {
					$picnum = $row1['air_arcity2']."-".$row1['air_arriveNm2']."-".$row1['air_arrivetime2'];
				} elseif (in_array($row1['dis_code'], $my_array3)) {
					$picnum = $row1['air_arcity']."-".$row1['air_arriveNm']."-".$row1['air_arrivetime'];
				}
			} elseif (($pp_code == $SENDING['p_code']) && ($pp_code!="")) {
				if (in_array($row1['dis_code'], $my_array)) {
					$picnum = $row1['air_stcity2']."-".$row1['air_stNm2']."-".$row1['air_sttime2'];
				} elseif (in_array($row1['dis_code'], $my_array4)) {
					$picnum = $row1['air_stcity']."-".$row1['air_stNm']."-".$row1['air_sttime'];
				}
			} elseif ($row1['parent'] == "MAIN") {
				$picnum = getPicGr3($row1['reserveCode'], $row1['traveler_nm']);
			} else {
				$picnum = getPicSub2($row1['reserveCode'], $s_code, $stdate);
				if ($picnum == '--') {
					if (in_array($row1['dis_code'], $my_array)) {
						$picnum = $row1['air_stcity2']."-".$row1['air_stNm2']."-".$row1['air_sttime2'];
					} elseif (in_array($row1['dis_code'], $my_array3)) {
						$picnum = $row1['air_arcity2']."-".$row1['air_arriveNm2']."-".$row1['air_arrivetime2'];
					} elseif (in_array($row1['dis_code'], $my_array2)) {
						$picnum = $row1['air_arcity2']."-".$row1['air_stNm2']."-".$row1['air_sttime2'];
					} elseif (in_array($row1['dis_code'], $my_array4)) {
						$picnum = $row1['air_stcity']."-".$row1['air_arriveNm']."-".$row1['air_arrivetime'];
					}
				}
			}

			// 예약/가이드/결제
			$reInfo = getReserveInfo($row1['reserveCode']);
			$tinfo  = getTourInfo2($s_code, $row1['stDate']);
			$nrev   = $row1['reserveCode'];
			$sign   = "$";

			if ($reInfo['tour_type'] == "2") {
				$mdbinfo = array('kor_name' => "<font color='blue'>웹예약</font>");
			} else {
				$mdbinfo = getinfo_dbMember($row1['userid']);
			}

			if ($reInfo['payment_st'] == "DONE") {
				$rest = "<font color=red>완납</font>";
			} elseif ($reInfo['payment_st'] == "PPAY") {
				$rest = "<font color=blue>부분완납</font>";
			} else {
				$rest = "미납";
			}

			$rein  = ($reInfo['progress'] != strip_tags($reInfo['progress'])) ? $reInfo['progress'] : nl2br($reInfo['progress']);
			$tin   = ($tinfo['etc_memo'] == "") ? "" : nl2br($tinfo['etc_memo']);
			$rein2 = ($tinfo['ev_memo']  == "") ? "" : nl2br($tinfo['ev_memo']);

			if (!empty($row1['rand_id'])) {
				$rnm = randname($row1['rand_id']);
				$row1['book_pri'] = isset($rnm['kor_name']) ? $rnm['kor_name'] : $row1['book_pri'];
			}

			$hopt = ($row1['hopt'] == "usa") ? "<font color='red'>미국측숙박</font><br/>" : (($row1['hopt']=="can") ? "<font color='red'>캐나다측숙박</font><br/>" : "");
			$vopt = ($row1['vopt'] == "fview") ? "<font color='red'>풀뷰</font><br/>" : (($row1['vopt']=="nview") ? "<font color='red'>논풀뷰</font><br/>" : "");

			if ($row1['pricet'] == '3') {
				$reInfo['last_bal'] = "해당사항없음";
			}

			
			$carinfoRow = getbusInfo($s_code, $stdate, $row1['reserveCode']);
			//echo $carinfoRow;
            $airsum = getAirlineSum($row1['reserveCode']);
			$airsumsum = $airsum['samt'];
			if ($mode == "down") {
				if ($nrev != $orev) {
					echo "<tr>
						<td rowspan='{$row1['cnt']}'><a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['pricet']}')\">{$row1['book_pri']}/<br /> {$reInfo['p_name']}</a></td>
						<td rowspan='{$row1['cnt']}'>{$row1['book_phone']}</td>
						<td rowspan='{$row1['cnt']}'>{$reInfo['p_cnt']}</td>
						<td rowspan='{$row1['cnt']}'>{$reInfo['room_cnt']}</td>
						<td>{$hopt}{$vopt}<a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['pricet']}')\">{$row1['traveler_nm']}</a><br />{$row1['traveler_phone']}</td>
						<td>{$row1['traveler_enm']}</td>
						<td>{$row1['e_memo']}</td>
						<td>{$row1['traveler_room']}</td>
						<td>{$picnum}</td>
						<td rowspan='{$row1['cnt']}'>{$rest}</td>";
						if ($user_dbinfo['division'] != "guide") {
                     echo "
						<td rowspan='{$row1['cnt']}'>{$airsumsum}</td>
						<td rowspan='{$row1['cnt']}'>{$row1['last_total']}</td>";
						}
						echo "
						<td rowspan='{$row1['cnt']}'>$ {$reInfo['last_bal']}</td>
						<td rowspan='{$row1['cnt']}'>{$carinfoRow}</td>
						<td rowspan='{$row1['cnt']}'>{$mdbinfo['kor_name']}</td>
						<td rowspan='{$row1['cnt']}'>{$rein} {$tin} {$rein2}</td>
					</tr>";
				} else {
					echo "<tr>
						<td><a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['pricet']}')\">{$row1['traveler_nm']}</a><br />{$row1['traveler_phone']}</td>
						<td>{$row1['traveler_enm']}</td>
						<td>{$row1['e_memo']}</td>
						<td>{$row1['traveler_room']}</td>
						<td>{$picnum}</td>
					</tr>";
				}
			} else {
				if ($nrev != $orev) {
					echo "<tr>
						<td rowspan='{$row1['cnt']}'><a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['pricet']}')\">{$row1['book_pri']}/<br /> {$reInfo['p_name']}</a></td>
						<td rowspan='{$row1['cnt']}'>{$row1['book_phone']}</td>
						<td rowspan='{$row1['cnt']}'>{$reInfo['p_cnt']}</td>
						<td rowspan='{$row1['cnt']}'>{$reInfo['room_cnt']}</td>
						<td>{$hopt}{$vopt}<a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['pricet']}')\">{$travelerNameBoth}</a><br />{$row1['traveler_phone']}</td>
						<td class='expand-only-col'>{$row1['pass_num']}</td>
						<td class='expand-only-col'>{$row1['pass_date']}</td>
						<td>{$row1['e_memo']}</td>
						<td>{$row1['traveler_room']}</td>
						<td>{$picnum}</td>
						<td rowspan='{$row1['cnt']}'>{$rest}</td>";
						if ($user_dbinfo['division'] != "guide") {
                     echo "
						<td rowspan='{$row1['cnt']}'>{$airsumsum}</td>
						<td rowspan='{$row1['cnt']}'>{$row1['last_total']}</td>";
						}
						echo "
						<td rowspan='{$row1['cnt']}'>$ {$reInfo['last_bal']}</td>
						<td rowspan='{$row1['cnt']}'>{$carinfoRow}</td>
						<td rowspan='{$row1['cnt']}'>{$mdbinfo['kor_name']}</td>
						<td rowspan='{$row1['cnt']}'>{$rein} {$tin} {$rein2}</td>
					</tr>";
				} else {
					echo "<tr>
						<td><a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['pricet']}')\">{$travelerNameBoth}</a><br />{$row1['traveler_phone']}</td>
						<td class='expand-only-col'>{$row1['pass_num']}</td>
						<td class='expand-only-col'>{$row1['pass_date']}</td>
						<td>{$row1['e_memo']}</td>
						<td>{$row1['traveler_room']}</td>
						<td>{$picnum}</td>
					</tr>";
				}
			}

			$orev = $row1['reserveCode'];
			$k++;
		}
	}




?>
<!DOCTYPE html>
<html>
    <head>
	
	   <?php
	    if($mode!='down') {
             echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
		 } ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>푸른투어 인트라넷</title>
        <?php if($mode!='down') { ?>
        <!-- Bootstrap framework -->
            <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
            <link rel="stylesheet" href="bootstrap/css/bootstrap-theme.min.css" />
            <link rel="stylesheet" href="css/normalize.css" />
        <!-- jQuery UI theme -->
            <link rel="stylesheet" href="lib/jquery-ui/css/Aristo/Aristo.css" />
        <!-- breadcrumbs -->
            <link rel="stylesheet" href="lib/jBreadcrumbs/css/BreadCrumb.css" />
        <!-- tooltips-->
            <link rel="stylesheet" href="lib/qtip2/jquery.qtip.min.css" />
		<!-- colorbox -->
            <link rel="stylesheet" href="lib/colorbox/colorbox.css" />
        <!-- code prettify -->
            <link rel="stylesheet" href="lib/google-code-prettify/prettify.css" />
        <!-- sticky notifications -->
            <link rel="stylesheet" href="lib/sticky/sticky.css" />
        <!-- aditional icons -->
            <link rel="stylesheet" href="img/splashy/splashy.css" />
		<!-- flags -->
            <link rel="stylesheet" href="img/flags/flags.css" />
        <!-- datatables -->
            <!-- <link rel="stylesheet" href="lib/datatables/extras/TableTools/media/css/TableTools.css"> -->
			<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css"/>
            <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css" />
        <!-- datepicker -->
            <!-- <link rel="stylesheet" href="lib/datepicker/datepicker.css" /> -->
            <link rel="stylesheet" href="lib/bootstrap-datepicker-1.6.4-dist/css/bootstrap-datepicker.min.css" />
		<!-- timepicker -->
            <!-- <link rel="stylesheet" href="lib/timepicker/css/bootstrap-timepicker.css" /> -->
            <link rel="stylesheet" href="lib/bootstrap-timepicker/css/bootstrap-timepicker.min.css" />
		<!-- clockpicker -->
            <link rel="stylesheet" href="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.css" />

        <!-- switch buttons -->
            <link rel="stylesheet" href="lib/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css" />

        <!-- font-awesome -->
            <link rel="stylesheet" href="img/font-awesome/css/font-awesome.min.css" />
        <!-- calendar -->
            <link rel="stylesheet" href="lib/fullcalendar/fullcalendar_gebo.css" />
			<link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
        
		<!-- theme color-->
            <link rel="stylesheet" href="css/blue.css" id="link_theme" />

        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.11/css/all.css" integrity="sha384-p2jx59pefphTFIpeqCcISO9MdVfIm4pNnsL08A6v5vaQc4owkQqxMV8kg4Yvhaw/" crossorigin="anonymous">
		<!-- main styles -->
            <link rel="stylesheet" href="css/style.css" />
		<!-- paran css -->
			<link rel="stylesheet" href="css/purun.css?sid=5fe18a1a-0023-476e-afb3-66cdb279d9f7" />
		<!-- favicon -->
            <link rel="shortcut icon" href="favicon1.ico" />
			<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
        <?php } ?>
        <!--[if lte IE 8]>
            <link rel="stylesheet" href="css/ie.css" />
        <!['endif']-->

        <!--[if lt IE 9]>
			<script src="js/ie/html5.js"></script>
			<script src="js/ie/respond.min.js"></script>
			<script src="lib/flot/excanvas.min.js"></script>
        <!['endif']-->  
		<!-- <script src="js/jquery.min.js"></script> -->
		<!-- <script src="js/jquery-migrate.min.js"></script> -->
		<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.0.1/jquery-migrate.min.js"></script>
		<script src="lib/jquery-ui/jquery-ui-1.10.0.custom.min.js"></script>

		<!-- touch events for jquery ui-->
			<script src="js/forms/jquery.ui.touch-punch.min.js"></script>
		<!-- easing plugin -->
			<script src="js/jquery.easing.1.3.min.js"></script>
		<!-- smart resize event -->
			<script src="js/jquery.debouncedresize.min.js"></script>
		<!-- js cookie plugin -->
			<script src="js/jquery_cookie_min.js"></script>
		<!-- main bootstrap js -->
			<script src="bootstrap/js/bootstrap.min.js"></script>
		<!-- bootstrap plugins -->
			<script src="js/bootstrap.plugins.min.js"></script>
		<!-- typeahead -->
			<script src="lib/typeahead/typeahead.min.js"></script>
		<!-- code prettifier -->
			<script src="lib/google-code-prettify/prettify.min.js"></script>
		<!-- sticky messages -->
			<script src="lib/sticky/sticky.min.js"></script>
		<!-- lightbox -->
			<script src="lib/colorbox/jquery.colorbox.min.js"></script>
		<!-- masked inputs -->
			<script src="js/forms/jquery.inputmask.min.js"></script>
		<!-- jBreadcrumbs -->
			<script src="lib/jBreadcrumbs/js/jquery.jBreadCrumb.1.1.min.js"></script>
		<!-- hidden elements width/height -->
			<script src="js/jquery.actual.min.js"></script>
		<!-- custom scrollbar -->
			<script src="lib/slimScroll/jquery.slimscroll.js"></script>
		<!-- fix for ios orientation change -->
			<script src="js/ios-orientationchange-fix.js"></script>
		<!-- to top -->
			<script src="lib/UItoTop/jquery.ui.totop.min.js"></script>
		<!-- mobile nav -->
			<script src="js/selectNav.js"></script>
		<!-- moment.js date library -->
			<script src="lib/moment/moment.min.js"></script>

		<!-- common functions -->
			<script src="js/pages/gebo_common.js"></script>

		<!-- multi-column layout -->
			<script src="js/jquery.imagesloaded.min.js"></script>
		<script src="js/jquery.wookmark.js"></script>
		<!-- responsive table -->
			<script src="js/jquery.mediaTable.min.js"></script>
		<!-- small charts -->
			<script src="js/jquery.peity.min.js"></script>
		<!-- charts -->
			<script src="lib/flot/jquery.flot.min.js"></script>
			<script src="lib/flot/jquery.flot.resize.min.js"></script>
			<script src="lib/flot/jquery.flot.pie.min.js"></script>
			<script src="lib/flot.tooltip/jquery.flot.tooltip.min.js"></script>
		<!-- calendar -->
			<script src="lib/fullcalendar/fullcalendar.min.js"></script>
		<!-- sortable/filterable list -->
			<script src="lib/list_js/list.min.js"></script>
			<script src="lib/list_js/plugins/paging/list.paging.min.js"></script>

		<!-- datepicker -->
			<!-- <script src="lib/datepicker/bootstrap-datepicker.min.js"></script> -->
			<script src="lib/bootstrap-datepicker-1.6.4-dist/js/bootstrap-datepicker.min.js"></script>
		
			
        <!-- datatables -->
			<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
			
		<!-- purun js -->
			<script src="js/purun.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>

		<!-- purun_lee js -->
			<script src="js/purun_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8e"></script>
			<style type="text/css">
			  @media print {
				  @page 
					{ 
						size: A4;   /* auto is the initial value */ 

						/* this affects the margin in the printer settings */ 
						margin: 10mm 3mm 5mm 3mm;  
					} 

					body  
					{ 
						/* this affects the margin on the content before sending to printer */ 
						margin: 0px;  
					}		
			  .pr {
					padding-right: 5px;
					padding-left: 5px;
			  }
			</style>
	</head>


<style>
    /*div.dt-buttons {
        float: right; 
        padding-bottom: 10px;
    }*/
    table { page-break-inside:auto }
    tr    { page-break-inside:avoid; page-break-after:auto }
    #custom_table .expand-only-col { display: none; }
    #expandTableWrap .expand-only-col { display: table-cell !important; }
    #expandListModal .modal-dialog { width: 96%; max-width: 96%; }
    #expandListModal .table { font-size: 12px; }
</style>
<body>
    <br />
	<br />
	<div id="contentwrapper" class="reservationDetailForm">
         <?php if ($mode != 'down'): ?>
		<div id="jCrumbs" class="breadCrumb
		module">
			<ul>
				<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
				<li><a href="#">전체스케줄표</a></li>
				<li>행사현황</li>
				<li>행사명단</li>
			</ul>
		</div>
	<?php endif; ?>
		<div class="row">
			<div class="col-sm-12 col-md-12">


					<div class="col-sm-12" style='page-break-after:always'>
					    <form action="" name="frmName" method="post" Onsubmit='exex()'>
					      <input type="hidden" name="mode" id="mode" value="">
                          <input type="hidden" name="p_code" value="<?=$p_code?>">
                          <input type="hidden" name="s_code" value="<?=$s_code?>">
                          <input type="hidden" name="stdate" value="<?=$stdate?>">
                          <input type="hidden" name="gid" value="<?=$gid?>">
                            <?php if ($mode != 'down'): ?>
                            <div class="row no-nav">
                                <div id="custom_button" class="col-sm-12 text-right">
                                    <button type="submit" class="btn btn-xs btn-default js-xxx" >엑셀보내기</button>
                                    <button type="button" class="btn btn-xs btn-default js-expand-list">확장 목록보기</button>
                                   <button type="button" class="btn btn-xs btn-default js-xxx" onclick="pageprint()">프린트</button>
								   <button type="button" onclick="pageprint2('<?=$s_code?>','<?=$stdate?>')" class="btn btn-xs btn-default js-xxx"  >간단명단출력</button>
								   <button type="button" onclick="agentemail('<?=$s_code?>','<?=$stdate?>')" class="btn btn-xs btn-default js-agent"  >에이전트엑셀보내기</button>
                                </div>
                            </div>
							<?php endif; ?>

								<legend class="guide-assign-border"><span class="pull-left small text-muted">행사고객현황</span></legend>

								<br/>
									<table class="table table-bordered table-condensed">

											<tr>
												<td width="10%" class="titletd text-center">행사명</td>
												<td width="40%" class=""><?=$prodInfo['p_name']?>

												</td>
												<td width="10%" class="titletd text-center">행사일자</td>
												<td width="40%" class=""><?php echo $_GET['stdate'];?></td>
											</tr>
											<tr>
												<td width="10%" class="titletd text-center">여행인원</td>
												<td width="40%" class=""><?=$totpcnt['cnt']?> 명	</td>
												<td width="10%" class="titletd text-center">객실수</td>
												<td width="40%" class=""><?=$totroom['rcnt']?> 개
												</td>
											</tr>
											<tr>
												<td width="10%" class="titletd text-center">총판매금액</td>
												<td width="40%" class="">$<?=number_format($totsal['tot'],2)?>	</td>
												<td width="10%" class="titletd text-center">잔금</td>
												<td width="40%" class="">$<?=number_format($totbal['bal'],2)?>
												</td>
											</tr>
										</table>
										<br/>
										<table id="custom_table" class="table table-bordered table-condensed custom_table">
											<thead>
												<tr>

												  <th class="tcenter" width='7%'>예약자</th>
												  <th class="tcenter" width='5%'>연락처</th>
												  <th class="tcenter" width='5%'>인원</th>
												  <th class="tcenter" width='7%'>방갯수</th>
												  <th class="tcenter" width='7%'>투어고객</th>
												  <?php if ($mode!="down") {?>
												  <th class="tcenter expand-only-col" width='7%'>여권번호</th>
												  <th class="tcenter expand-only-col" width='7%'>여권 유효기간</th>
												  <?php } ?>
												  <?php if ($mode=="down") {?>
												  <th class="tcenter" width='7%'>영문이름</th>
												  <?php } ?>
												  <th class="tcenter" width='10%'>예약메모</th>
												  <th class="tcenter" width='5%'>루밍</th>
												  <th class="tcenter" width='8%'>픽업</th>

												  <th class="tcenter" width='5%'>결제상태</th>
												  <?php if ($user_dbinfo['division'] != "guide") { ?>
												  <th class="tcenter" width='7%'>항공금액</th>
												  <th class="tcenter" width='7%'>합계금액</th>
												  <?php } ?>
												  <th class="tcenter" width='7%'>잔금</th>
												  <th class="tcenter" width='7%'>가이드</th>
												  <th class="tcenter" width='5%'>접수자</th>
												  <th class="tleft" width='*'>진행사항</th>

												</tr>
											</thead>
											<tbody>
												<?php custlist(); ?>
											</tbody>
										</table>

							<br />
							<br />
						</form>
					</div>

			</div><!-- -->
		</div>


	</div>
    <?php if ($mode != 'down') { ?>
    <div class="modal fade" id="expandListModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn btn-xs btn-success js-expand-excel" style="margin-right:10px;">엑셀내보내기</button>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">확장 행사고객현황</h4>
                </div>
                <div class="modal-body">
                    <div class="table-responsive" id="expandTableWrap"></div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <script>
		$(document).ready(function () {
           // pt.initReservationList()
             
            //pt.initReservationDetail()

            var customerTable = null;
            if ($.fn.DataTable) {
                try {
                    customerTable = $('.custom_table').DataTable({
    				    dom: 'Bfrtip',
    				    buttons: [
    					    'copy', 'csv', 'excel', 'print'
    				    ],
    				    "order": []
    			    });
                } catch (e) {
                    console.error('DataTable init error:', e);
                }
            }
			$(".dataTables_length").css({ "display" :"none" });

            $('.js-expand-list').on('click', function (e) {
                e.preventDefault();

                var $table = $('<table class="table table-bordered table-condensed expanded-table"></table>');
                var $thead = $('#custom_table thead').clone();
                var $tbody = $('<tbody></tbody>');

                var $rowNodes;
                if (customerTable && typeof customerTable.rows === 'function') {
                    $rowNodes = $(customerTable.rows({ search: 'applied' }).nodes());
                } else {
                    $rowNodes = $('#custom_table tbody tr');
                }

                $rowNodes.each(function () {
                    var $row = $(this).clone();
                    $row.find('.expand-only-col').show();
                    $tbody.append($row);
                });

                $table.append($thead).append($tbody);
                $table.find('.expand-only-col').show();

                $('#expandTableWrap').empty().append($table);

                if (typeof $('#expandListModal').modal === 'function') {
                    $('#expandListModal').modal('show');
                } else {
                    $('#expandListModal').show();
                }
            });

            $('.js-expand-excel').on('click', function (e) {
                e.preventDefault();

                var $expTable = $('#expandTableWrap table');
                if (!$expTable.length) {
                    alert('먼저 확장 목록보기를 열어주세요.');
                    return;
                }

                var html = "<html><head><meta charset='utf-8'></head><body>" + $expTable.prop('outerHTML') + "</body></html>";
                var blob = new Blob(["\uFEFF", html], { type: "application/vnd.ms-excel;charset=utf-8;" });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = "expand_customer_<?=$s_code?>_<?=$stdate?>.xls";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
		
            //var args = {paging:false, ordering:false, info:false,scrollX:true,scrollY: 200};
           
            
           
		})
		
		function pageprint()
		{
			 
			
           window.print();
  
		}
		function exex()
		{
			 
		   
           $('#mode').val('down');
  
		}
		var ctr=0;
	    function openwin(r_code,pricet) { 
	       var winName = "all_"+(ctr++);
		   window.open("base_reservation_m.php?estimateCode="+r_code+"&pricet="+pricet+"&division=3&pdx=2&sub=15",winName,"width=1000,height=1080,scrollbars=1");
	    }

		function pageprint2(s_code,stdate)
		{
			 
			
           var winName = "all_"+(ctr++);
		   window.open("print_customer.php?s_code="+s_code+"&stdate="+stdate,winName,"width=900,height=1080,scrollbars=1");
  
		}

		function agentemail(s_code,stdate)
		{
			 
			
           var winName = "all_"+(ctr++);
		   window.open("print_customer2.php?s_code="+s_code+"&stdate="+stdate,winName,"width=900,height=1080,scrollbars=1");
  
		}
      
	</script>
    </body>
</html>
