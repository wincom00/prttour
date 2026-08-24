<?php

	include "include/inc_base.php";

	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
		header("Location: ./login.php");
		exit;
	}

	set_time_limit(60);
	ini_set('memory_limit', '512M');

	$mode   = isset($_POST['mode']) ? $_POST['mode'] : (isset($_GET['mode']) ? $_GET['mode'] : '');
	$p_code = isset($_REQUEST['p_code']) ? $_REQUEST['p_code'] : (isset($_REQUEST['pcode']) ? $_REQUEST['pcode'] : (isset($p_code) ? $p_code : ''));
	$s_code = isset($_REQUEST['s_code']) ? $_REQUEST['s_code'] : (isset($_REQUEST['gscode']) ? $_REQUEST['gscode'] : (isset($s_code) ? $s_code : ''));
	$g_code = isset($_REQUEST['g_code']) ? $_REQUEST['g_code'] : (isset($_REQUEST['gcode']) ? $_REQUEST['gcode'] : (isset($g_code) ? $g_code : ''));
	$gid    = isset($_REQUEST['gid']) ? $_REQUEST['gid'] : (isset($gid) ? $gid : '');
	$stdate = isset($_REQUEST['stdate']) ? $_REQUEST['stdate'] : (isset($_REQUEST['stDate']) ? $_REQUEST['stDate'] : (isset($_REQUEST['st']) ? $_REQUEST['st'] : (isset($stdate) ? $stdate : '')));

	if ($mode === 'down') {
		$filename = ($s_code != "" ? $s_code : 'export') . date('Ymd') . ".xls";
		header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"{$filename}\"");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo "\xEF\xBB\xBF";
	}

	$prodInfo  = getProductMaster($p_code);
	$g_dbinfo1 = getguideInfor($g_code, $s_code);
	$g_dbinfo  = getinfo_dbMemberg($g_dbinfo1['guide_id']);
	$g_dbinfo2 = getinfo_dbMemberg($g_dbinfo1['sguide_id']);
	$picM      = getPicGrM($s_code);
	$picStr    = ($s_code != "" && $stdate != "") ? getPicGr2($s_code, $stdate) : "";
	$picctot   = $picM . "&nbsp;&nbsp;" . $picStr;
	$guideinfo = $g_dbinfo1;
	$carinfo   = getCCInfor($s_code);
	$gss       = getGuideInfo2($s_code);
	$ccinfo    = getCarInfo($gss['c_id']);
	$bus_team  = codebaseName($ccinfo['bus_team']);

	function custlist() {
		global $dbConn, $p_code, $gid, $s_code;

		$totbal = 0;
		$qry1 = "select
					b.book_pri,
					b.p_cnt,
					a.reserveCode,
					a.romm_num,
					a.rev_nm,
					c.traveler_nm,
					c.traveler_phone,
					b.p_name,
					b.tour_type,
					b.h_cnt,
					b.last_bal,
					b.stDate,
					b.base_rate,
					b.parent,
					b.meet_area,
					b.rand_id
				from
					tour_car a,reserve_info b,reserve_traveler c
				where a.p_code=b.p_code && a.reserveCode=b.reserveCode
					 && b.reserveCode=c.reserveCode && a.rev_nm=c.traveler_nm
					 && a.sub_eCode='$s_code' && a.p_code='$p_code' && c.seqint='0'
				group by a.reserveCode,a.sub_eCode";

		$rst1 = mysql_query($qry1);
		while ($row1 = mysql_fetch_assoc($rst1)) {
			if ($row1['parent'] == "MAIN") {
				$picnum = getPicGr4($row1['reserveCode'], $row1['traveler_nm']);
			} else {
				$picnum = pickBaseCodeC($row1['meet_area']);
			}

			$reInfo = getReserveInfo($row1['reserveCode']);
			$sexli  = getTrSex($row1['reserveCode'], $s_code);
			$tinfo  = getTourInfo2($p_code, $row1['stDate']);

			if ($row1['last_bal'] == "0") {
				$rest = "<font color=red>완납</font>";
			} else {
				$rest = "미납";
			}

			$rein = ($reInfo['progress'] == "") ? "" : nl2br($reInfo['progress']);
			$tin = ($tinfo['etc_memo'] == "") ? "" : nl2br($tinfo['etc_memo']);
			$rein2 = ($tinfo['ev_memo'] == "") ? "" : nl2br($tinfo['ev_memo']);

			if ($row1['rand_id'] != "") {
				$rname = randname($row1['rand_id']);
				$rcap = $rname['kor_name'];
			} else {
				$rcap = "푸른투어";
			}

			if ($row1['tour_type'] == "1") {
				$ty = "15";
			} else if ($row1['tour_type'] == "2") {
				$ty = "20";
			} else if ($row1['tour_type'] == "5") {
				$ty = "25";
			} else {
				$ty = "15";
			}

			$hinfo = getHotelass2($row1['reserveCode']);

			echo "<tr>
					<td><a href=\"javascript:openwin('{$row1['reserveCode']}','{$ty}')\">{$row1['book_pri']}/{$rcap}</a></td>
					<td><a href=\"javascript:openwin('{$row1['reserveCode']}','{$ty}')\">{$row1['traveler_nm']}</a></td>
					<td>{$sexli}</td>
					<td>{$row1['p_cnt']}</td>
					<td>{$row1['h_cnt']}</td>
					<td>{$row1['romm_num']}</td>
					<td>{$row1['traveler_phone']}</td>
					<td>{$picnum}</td>
					<td>{$rest}</td>
					<td>{$row1['last_bal']}</td>
					<td>{$rein} {$tin} {$rein2}<br>------------------<br>{$hinfo}</td>
				</tr>";

			$totbal += (float)$row1['last_bal'];
		}

		echo "<tr>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td>총금액</td>
				<td>" . number_format($totbal, 2) . "</td>
				<td></td>
			</tr>";
	}

?>
<!DOCTYPE html>
<html>
    <head>
	   <?php if ($mode != 'down') { echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>"; } ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>푸른투어 인트라넷</title>
        <?php if ($mode != 'down') { ?>
        <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
        <link rel="stylesheet" href="bootstrap/css/bootstrap-theme.min.css" />
        <link rel="stylesheet" href="css/normalize.css" />
        <link rel="stylesheet" href="lib/jquery-ui/css/Aristo/Aristo.css" />
        <link rel="stylesheet" href="lib/jBreadcrumbs/css/BreadCrumb.css" />
        <link rel="stylesheet" href="lib/qtip2/jquery.qtip.min.css" />
        <link rel="stylesheet" href="lib/colorbox/colorbox.css" />
        <link rel="stylesheet" href="lib/google-code-prettify/prettify.css" />
        <link rel="stylesheet" href="lib/sticky/sticky.css" />
        <link rel="stylesheet" href="img/splashy/splashy.css" />
        <link rel="stylesheet" href="img/flags/flags.css" />
        <link rel="stylesheet" href="lib/bootstrap-datepicker-1.6.4-dist/css/bootstrap-datepicker.min.css" />
        <link rel="stylesheet" href="lib/bootstrap-timepicker/css/bootstrap-timepicker.min.css" />
        <link rel="stylesheet" href="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.css" />
        <link rel="stylesheet" href="lib/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css" />
        <link rel="stylesheet" href="img/font-awesome/css/font-awesome.min.css" />
        <link rel="stylesheet" href="lib/fullcalendar/fullcalendar_gebo.css" />
		<link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
        <link rel="stylesheet" href="css/blue.css" id="link_theme" />
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.11/css/all.css" integrity="sha384-p2jx59pefphTFIpeqCcISO9MdVfIm4pNnsL08A6v5vaQc4owkQqxMV8kg4Yvhaw/" crossorigin="anonymous">
        <link rel="stylesheet" href="css/style.css" />
		<link rel="stylesheet" href="css/purun.css?sid=5fe18a1a-0023-476e-afb3-66cdb279d9f7" />
        <link rel="shortcut icon" href="favicon1.ico" />
        <?php } ?>
        <!--[if lte IE 8]>
            <link rel="stylesheet" href="css/ie.css" />
        <![endif]-->

        <!--[if lt IE 9]>
			<script src="js/ie/html5.js"></script>
			<script src="js/ie/respond.min.js"></script>
			<script src="lib/flot/excanvas.min.js"></script>
        <![endif]-->
		<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.0.1/jquery-migrate.min.js"></script>
		<script src="lib/jquery-ui/jquery-ui-1.10.0.custom.min.js"></script>
		<script src="js/forms/jquery.ui.touch-punch.min.js"></script>
		<script src="js/jquery.easing.1.3.min.js"></script>
		<script src="js/jquery.debouncedresize.min.js"></script>
		<script src="js/jquery_cookie_min.js"></script>
		<script src="bootstrap/js/bootstrap.min.js"></script>
		<script src="js/bootstrap.plugins.min.js"></script>
		<script src="lib/typeahead/typeahead.min.js"></script>
		<script src="lib/google-code-prettify/prettify.min.js"></script>
		<script src="lib/sticky/sticky.min.js"></script>
		<script src="lib/colorbox/jquery.colorbox.min.js"></script>
		<script src="js/forms/jquery.inputmask.min.js"></script>
		<script src="lib/jBreadcrumbs/js/jquery.jBreadCrumb.1.1.min.js"></script>
		<script src="js/jquery.actual.min.js"></script>
		<script src="lib/slimScroll/jquery.slimscroll.js"></script>
		<script src="js/ios-orientationchange-fix.js"></script>
		<script src="lib/UItoTop/jquery.ui.totop.min.js"></script>
		<script src="js/selectNav.js"></script>
		<script src="lib/moment/moment.min.js"></script>
		<script src="js/pages/gebo_common.js"></script>
		<script src="js/jquery.imagesloaded.min.js"></script>
		<script src="js/jquery.wookmark.js"></script>
		<script src="js/jquery.mediaTable.min.js"></script>
		<script src="js/jquery.peity.min.js"></script>
		<script src="lib/flot/jquery.flot.min.js"></script>
		<script src="lib/flot/jquery.flot.resize.min.js"></script>
		<script src="lib/flot/jquery.flot.pie.min.js"></script>
		<script src="lib/flot.tooltip/jquery.flot.tooltip.min.js"></script>
		<script src="lib/fullcalendar/fullcalendar.min.js"></script>
		<script src="lib/list_js/list.min.js"></script>
		<script src="lib/list_js/plugins/paging/list.paging.min.js"></script>
		<script src="lib/bootstrap-datepicker-1.6.4-dist/js/bootstrap-datepicker.min.js"></script>
		<script src="js/purun.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>
		<script src="js/purun_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8e"></script>
		<style type="text/css">
			@media print {
				@page {
					size: A4;
					margin: 10mm 3mm 5mm 3mm;
				}

				body {
					margin: 0px;
				}

				.pr {
					padding-right: 5px;
					padding-left: 5px;
				}
			}
		</style>
	</head>

<style>
	table { page-break-inside: auto }
	tr { page-break-inside: avoid; page-break-after: auto }
</style>
<body>
    <br />
	<br />
	<div id="contentwrapper" class="reservationDetailForm">
		<?php if ($mode != 'down'): ?>
		<div id="jCrumbs" class="breadCrumb module">
			<ul>
				<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
				<li><a href="#">행사배정관리</a></li>
				<li>가이드배정현황</li>
				<li>행사명단</li>
			</ul>
		</div>
		<?php endif; ?>
		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="col-sm-12" style='page-break-after:always'>
					<form action="" name="frmName" method="post" onsubmit="exex()">
						<input type="hidden" name="mode" id="mode" value="">
						<input type="hidden" name="p_code" value="<?=$p_code?>">
						<input type="hidden" name="s_code" value="<?=$s_code?>">
						<input type="hidden" name="g_code" value="<?=$g_code?>">
						<input type="hidden" name="gid" value="<?=$gid?>">
						<input type="hidden" name="stdate" value="<?=$stdate?>">
						<?php if ($mode != 'down'): ?>
						<div class="row no-nav">
							<div id="custom_button" class="col-sm-12 text-right">
								<button type="submit" class="btn btn-xs btn-default js-xxx">엑셀보내기</button>
								<button type="button" class="btn btn-xs btn-default js-xxx" onclick="pageprint()">프린트</button>
								<button type="button" onclick="pageprint2('<?=$p_code?>','<?=$stdate?>')" class="btn btn-xs btn-default js-xxx">간단명단출력</button>
							</div>
						</div>
						<?php endif; ?>

						<legend class="guide-assign-border"><span class="pull-left small text-muted">행사고객현황</span></legend>

						<br/>
						<table class="table table-bordered table-condensed">
							<tr>
								<td width="10%" class="titletd text-center">행사명</td>
								<td width="40%" class=""><?=$prodInfo['p_name']?></td>
								<td width="10%" class="titletd text-center">행사번호</td>
								<td width="40%" class=""><?=$s_code?></td>
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">픽업장소</td>
								<td width="40%" class=""><?=$picctot?></td>
								<td width="10%" class="titletd text-center">가이드</td>
								<td width="40%" class=""><?=$g_dbinfo['kor_name']?> &nbsp;&nbsp;&nbsp;<?=$g_dbinfo['company_phone']?>&nbsp;&nbsp;부 : <?=$g_dbinfo2['kor_name']?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?=$g_dbinfo2['company_phone']?></td>
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">차량</td>
								<td width="40%" class=""><?=$bus_team['comment']?> <?=$gss['c_type']?></td>
								<td width="10%" class="titletd text-center">기사</td>
								<td width="40%" class=""><?=$gss['d_nm']?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?=$gss['d_tel']?></td>
							</tr>
						</table>

						▶ 호텔정보
						<table class="table table-condensed">
							<thead>
								<tr>
									<th class="tcenter" width='30%'>날짜</th>
									<th class="tcenter" width='*'>호텔명</th>
									<th class="tcenter" width='10%'>QQ</th>
								</tr>
							</thead>
							<tbody>
							<?php
								$qry2 = "SELECT a.pcnt,a.hotel_code,a.stDate,b.h_name,b.m_rate,a.day
										FROM hotel_assign a, product_hotel b
										WHERE a.hotel_code=b.h_code && a.sub_eCode='$s_code' && a.p_code='$p_code'
										ORDER BY a.day ASC";
								$rst2 = mysql_query($qry2);
								while ($row2 = mysql_fetch_assoc($rst2)) {
									$s_date = explode("-", $row2['stDate']);
									$add_date = $row2['day'] - 1;
									$local_start = date("Y-m-d", mktime(0, 0, 0, $s_date[1], $s_date[2] + $add_date, $s_date[0]));
							?>
								<tr>
									<td><?=$local_start?></td>
									<td><?=$row2['h_name']?></td>
									<td class="tcenter"><?=$row2['pcnt']?></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>

						<br/>
						<table class="table table-striped table-bordered table-hover table-condensed text-center">
							<thead>
								<tr>
									<th class="tcenter" width='10%'>대표예약</th>
									<th class="tcenter" width='7%'>고객명</th>
									<th class="tcenter" width='5%'>성별</th>
									<th class="tcenter" width='5%'>인원</th>
									<th class="tcenter" width='5%'>RM</th>
									<th class="tcenter" width='5%'>#RN</th>
									<th class="tcenter" width='10%'>연락처</th>
									<th class="tcenter" width='7%'>탑승</th>
									<th class="tcenter" width='5%'>납부</th>
									<th class="tcenter" width='7%'>잔금</th>
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
			</div>
		</div>
	</div>

	<script>
		function pageprint() {
			window.print();
		}

		function exex() {
			$('#mode').val('down');
		}

		var ctr = 0;
		function openwin(r_code, ty) {
			var winName = "all_" + (ctr++);
			window.open("base_reservation_m.php?estimateCode=" + r_code + "&division=3&pdx=2&sub=" + ty, winName, "width=1300,height=700,scrollbars=1");
		}

		function pageprint2(s_code, stdate) {
			var winName = "all_" + (ctr++);
			window.open("print_customer.php?s_code=" + s_code + "&stdate=" + stdate, winName, "width=900,height=1080,scrollbars=1");
		}
	</script>
    </body>
</html>
