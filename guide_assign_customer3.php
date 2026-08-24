<?php
    include "include/inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     
    
	$prodInfo = getProductMaster($p_code);
	$totpcnt = getReserveInfoCntG2($p_code,$stdate,$s_code);
	$totroom = getReserveInfoRoom2($p_code,$stdate,$s_code);
	$totbal = getReserveInfoBalSS($p_code,$stdate);
	$totsal = getReserveInfoSal($p_code,$stdate);
	$revinfom =getReserveInfo3($p_code,$stdate);
    $g_dbinfo1 = getguideInfor3($s_code);
	$g_dbinfo = getinfo_dbMemberg($g_dbinfo1['guide_id']);
    //print_r($g_dbinfo);;
	$picM = getPicGrM($s_code);
	$picStr  = getPicGr2($s_code,$stdate);
	$picctot = $picM."&nbsp;&nbsp;".$picStr;
	$guideinfo = getguideInfor3($s_code);
	$carinfo = getCCInfor($s_code);
    
	$gss = getGuideInfo2($s_code);
	$ccinfo = getCarInfo($gss['c_id']);
	$bus_team = codebaseName($ccinfo['bus_team']);
	if ($prodInfo['base_rate'] == "CAD") {
		$sign = "C$";
	} else {
		$sign = "$";
	}
	$rcnt = getHRoomCnt($grand_eCode,$s_code);
	$s_date = explode("-",$stdate);
										
	$add_date = $prodInfo['p_day']-2;

    $end_day  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));	
	//print_r($carinfo);

	function custlist() {
               global $dbConn,$p_code,$gid,$s_code,$stdate,$prodInfo,$totbal_t,$sign ;
			 if (($prodInfo['p_code'] == 'LAPICKUP') || ($prodInfo['p_code'] == 'LVPICKUP') || ($prodInfo['p_code'] == 'PICKUP') || ($prodInfo['p_code'] == 'EWRPICKUP') || ($prodInfo['p_code'] == 'SFOPICKUP') || ($prodInfo['p_code'] == 'SLCPICKUP') || ($prodInfo['p_code'] == 'ANCPICKUP')  || ($prodInfo['p_code'] == 'SANPICKUP') || ($prodInfo['p_code'] == 'DFWPICKUP') || ($prodInfo['p_code'] == 'DFWPICKUP') || ($prodInfo['p_code'] == 'LGAPICKUP') || ($prodInfo['p_code'] == 'YULPICKUP') ) {
				$order = "order by b.air_arrivetime,b.reserveCode asc"; 
			 } else if (($prodInfo['p_code'] == 'LASENDING') || ($prodInfo['p_code'] == 'LVSENDING') || ($prodInfo['p_code'] == 'SENDING') || ($prodInfo['p_code'] == 'EWRSENDING') || ($prodInfo['p_code'] == 'SFOSENDING') || ($prodInfo['p_code'] == 'SLCSENDING') || ($prodInfo['p_code'] == 'ANCSENDING') || ($prodInfo['p_code'] == 'YULSENDING') || ($prodInfo['p_code'] == 'SANSENDING') || ($prodInfo['p_code'] == 'LAXSENDING')   ) {
				$order = "order by b.reserveCode,b.air_sttime asc"; 
			 } else {
				$order = "order by b.reserveCode,c.pick_area,c.seqint asc";
			 }
			 
		       $qry1="select  b.pricet,d.cnt,b.p_code,b.air_arcity,b.air_arrivetime,b.air_arriveNm,b.air_stcity,b.air_sttime,b.air_stNm, b.p_name, b.reserveCode, b.book_pri, b.book_phone, b.rand_id,
					c.traveler_nm, c.traveler_phone, b.h_cnt, b.p_cnt as pcnt, b.last_bal, b.stDate, b.base_rate, b.parent,b.userid ,c.pick_area
					from reserve_info b,reserve_traveler c,(select count(a.reserveCode) as cnt,a.reserveCode from reserve_info a,reserve_traveler b 
					where  a.reserveCode=b.reserveCode && a.p_code='$p_code' && a.stDate ='$stdate' && (a.rev_status ='READY' || a.rev_status ='ORDER' || a.rev_status ='DONE')  group by a.reserveCode) d
					 where b.reserveCode=c.reserveCode && b.reserveCode=d.reserveCode
					 && b.p_code='$p_code' && b.stDate ='$stdate' && (b.rev_status ='READY' || b.rev_status ='ORDER' || b.rev_status ='DONE') &&
					 b.reserveCode in (select reserveCode from tour_car where sub_eCode='$s_code') $order ";
						//echo $qry1; 
			 $rst1 = mysql_query($qry1);
			 $k = 0;
			 $s =1;
			 $orev= "";
			 $totbal_t=0;
			 while($row1 = mysql_fetch_assoc($rst1)){
				$PICKUP = getProductPick($prodInfo['p_code']);
				$SENDING = getProductSend($prodInfo['p_code']);
				//echo $prodInfo[p_code]."1";
				if (($prodInfo['p_code'] == $PICKUP['p_code']) && ($prodInfo['p_code']!="")) {
					$picnum = $row1['air_arcity']."-".$row1['air_arriveNm']."-".$row1['air_arrivetime'];
				} else if (($prodInfo['p_code'] == $SENDING['p_code']) && ($prodInfo['p_code']!="")) {
					$picnum = $row1['air_stcity']."-".$row1['air_stNm']."-".$row1['air_sttime'];
					$picnum = $picnum.'<br/>'.getPicSub($row1['reserveCode'],$s_code,$stdate);
				} else if ($row1['parent'] == "MAIN") {
				    $picnum = getPicGr4($row1['reserveCode'],$row1['traveler_nm']);
				///	echo "2222";
				} else {
				///	echo "3333";
					$picnum = getPicSub2($row1['reserveCode'],$s_code,$stdate);
				}
				//echo $picnum;
				$carinfo = getbusInfo($s_code,$stdate,$row1['reserveCode']);
				
				$reInfo = getReserveInfo($row1['reserveCode']);
				$tinfo = getTourInfo2($s_code,$row1['stDate']);
				$nrev=$row1['reserveCode'];
				if ($row1['base_rate'] == "CAD") {
					$sign = "C$";
				} else {
					$sign = "$";
				}
				
				if ($reInfo['payment_st'] == "DONE") {
					$rest = "<font color=red>완납</font>";
				} else if ($reInfo['payment_st'] == "PPAY") {
					$rest = "<font color=blue>부분완납</font>";
				} else {
					$rest = "미납";
				}
				if($reInfo['progress'] != strip_tags($reInfo['progress'])) {
					// contains HTML
					$rein = $reInfo['progress'];
				} else {
					$rein = nl2br($reInfo['progress']);
				}
				if ($tinfo['etc_memo'] == "") {
					$tin = "";
				} else {
					$tin = nl2br($tinfo['etc_memo']);
				}
				if ($tinfo['ev_memo'] == "") {
					$rein2 = "";
				} else {
					$rein2 = nl2br($tinfo['ev_memo']);
				}
				if ($reInfo['tour_type'] == "2") {
					$mdbinfo['kor_name']= "<font color='blue'>웹예약</font>";
				}else {
					$mdbinfo = getinfo_dbMember($row1['userid']);
				}
				//echo $row1[rand_id]."<br/>";
				if ($row1['rand_id'] != "") {
					$rnm = randname($row1['rand_id']);
					$row1['book_pri'] = $rnm['kor_name'];
					//echo $rnm[kor_name]."<br/>";
					
				}
				
				if ($row1['pricet'] == '3') {
					$reInfo['last_bal'] ="0";

				}
				$carinfo = implode('@',array_unique(explode('@', $carinfo)));
				$hinfo1= getHotelrnum($row1['reserveCode'],$row1['traveler_nm'],$row1['stDate']);
				if ($nrev != $orev) {
					echo "<tr>
							  <td alignb='center'>$s</td>
							  <td ><a href=javascript:openwin('{$row1['reserveCode']}','{$row1['pricet']}') >{$row1['traveler_nm']}({$reInfo['p_cnt']})</a></td>
							  <td>{$row1['traveler_phone']}</td>
							 
							  <td >$rest</td>
							  <td >$sign {$reInfo['last_bal']}</td>
							
							  <td>{$mdbinfo['kor_name']}</td>
							  <td  rowspan='{$row1['cnt']}'>$rein $tin $rein2</td>
							  
							</tr>";
					$totbal_t = $totbal_t + $reInfo['last_bal'];
					
				} else {
					echo "<tr>
						      <td>$s</td>
							  <td><a href=javascript:openwin('{$row1['reserveCode']}','{$row1['pricet']}') >{$row1['traveler_nm']}({$reInfo['p_cnt']})</a></td>
							  <td>{$row1['traveler_phone']}</td>
							  <td></td>
							  <td colspan='2'></td>
							</tr>";

				}
				
				
				$orev = $row1['reserveCode'];
				$k++;
				$s++;

			}

	}
?>
<!DOCTYPE html>
<html>
    <head>
	<?php
	    if($mode=='down') {
		    header("Content-type: application/vnd.ms-excel; charset=UTF-8"); 
			header("Content-Disposition: attachment; filename=".$_GET['s_code']."".date('Ymd').".xls");
			header("Pragma: no-cache"); 
			header("Expires: 0");

			//header("Content-Description: PHP5 Generated Data");
		    //echo "<meta http-equiv='Content-Type' content='application/vnd.ms-excel; charset=utf-8'/>";
			echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";


       }
	   ?>
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
			<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
			
		<!-- 로얄 js -->
			<script src="js/royal.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>

		
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
    body { font-size: 15px !important; }
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
								<li><a href="#">행사배정관리</a></li>
								<li>가이드배정현황</li>
								<li>행사명단</li>
							</ul>
						</div>
					<?php endif; ?>
						<div class="row">
							<div class="col-sm-12 col-md-12">
								
								<div class="row">
									<div class="col-sm-12">
										<form action="" name="frmName" method="post">
										  <input type="hidden" name="mode" value="down">
										  <fieldset class="guide-assign-border">
											<legend class="guide-assign-border"><span class="pull-left small text-muted">행사고객현황</span></legend>
											<?php if ($mode != 'down'): ?>
											<div class="row no-nav">
												<div id="custom_button" class="col-sm-12 text-right">
													<button type="submit" class="btn btn-xs btn-default js-xxx" >엑셀보내기</button>
													<button type="button" class="btn btn-xs btn-default js-xxx" onclick="pageprint()">프린트</button>
													<!--<button type="button" class="btn btn-xs btn-default js-xxx" onclick="pageprint()">프린트</button>-->
												</div>
											</div>
											<?php endif; ?>
											<br/>
											<div class="col-sm-12" id='printarea'>
											<table class="table table-bordered table-condensed">
										            <tr>
														<td width="10%" class="titletd text-center">행사기간</td>
														<td width="40%" class="" colspan=3><?=$revinfom['stDate']?>  ~  <?=$end_day?>
															
														
													</tr>
													<tr>
														<td width="10%" class="titletd text-center">행사명</td>
														<td width="40%" class=""><?=$prodInfo['p_name']?>
															
														</td>
														<td width="10%" class="titletd text-center">행사번호</td>
														<td width="40%" class=""><?php echo $_GET['s_code'];?></td>
													</tr>
													<td width="10%" class="titletd text-center">총여행인원</td>
														<td width="40%" class=""><?=$totpcnt['rcnt']?> 명	</td>
														<td width="10%" class="titletd text-center">객실수</td>
														<td width="40%" class=""><?=$totroom['rcnt']?> 개									
														</td>
													<tr>
														<td width="10%" class="titletd text-center">픽업장소</td>
														<td width="40%" class=""><?=$picctot?>	</td>
														<td width="10%" class="titletd text-center">가이드</td>
														<td width="40%" class=""><?php echo $g_dbinfo['kor_name']; ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $g_dbinfo['company_phone'];?>									
														</td>
													</tr>
													<tr>
														<td width="10%" class="titletd text-center">차량</td>
														<td width="40%" class=""><?=$bus_team['comment']?> <?=$gss['c_type']?>	</td>
														<td width="10%" class="titletd text-center">기사</td>
														<td width="40%" class=""><?php echo $gss['d_nm']; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $gss['d_tel'];?>									
														</td>
													</tr>
													<!--<tr>
														<td width="10%" class="titletd text-center">총수금할금액</td>
														<td width="40%" class="" colspan=3><?=$totbal_t?></td>
													</tr>	--->
												
											</table>
											
											<br/>
											▶ 미팅 장소
											<table id="custom_table" class="table table-bordered table-condensed custom_table">
												<thead>
													<tr>  
													  
													  <th class="tcenter" width='30%'>미팅장소</th>
													  <th class="tcenter" width='5%'>미팅시간</th>
													  <th class="tcenter" width='5%'>인원수</th>
													  <th class="tcenter" width='*'>명단</th>
													  
													 
													</tr>
												</thead>
												<tbody>
												<?php 
													$qry1="
													 SELECT a.reserveCode,COUNT(b.pick_area) cnt,b.pick_area,a.p_code,a.meet_area,a.parent FROM reserve_info a,reserve_traveler b
													  WHERE a.reserveCode=b.reserveCode 
													 && a.p_code = '$p_code'  && a.reserveCode IN (SELECT reserveCode FROM  tour_car WHERE  sub_eCode='$s_code' ) 
													  && a.stDate='$stdate' && a.rev_status!='CANCEL' GROUP BY b.pick_area";
													//echo $qry1;
													$rst1 = mysql_query($qry1);
													while($row1 = mysql_fetch_assoc($rst1)){
														//echo $row1[picCode]."TEST<br />";
												      // if ($row1[picCode] != "") {
												      //echo $row1[reserveCode]."TEST<br />";\
													  if ($row1[parent=='SUB']) {
														   $picname = $row1['meet_area'];
													  } else  {
													 
															$pickarr = explode("/",$row1['pick_area']);
															$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
															$picarea = "$pickarr[0]/{$picknm['pick_time']}";
                                                            $picname = "{$picknm['pick_name']}-{$picknm['pick_time']}";
												            $pickp = "{$row1['cnt']}인";
													  }
													
												?>
													<tr>
						      
														  <td><?=$picname?></td>
														  <td ><?=$picknm['pick_time']?></td>
														  <td ><?=$pickp?></td>
												  
												
												<?php
													        if ($picarea == "/") {
															    $picarea = '';
													        }
															$qry2="select '단일' as gnm,b.traveler_nm from reserve_info a,reserve_traveler b where a.reserveCode=b.reserveCode && a.stDate='$stdate' && a.p_code='$p_code'  && b.pick_area='$picarea' && (a.rev_status != 'CANCEL') ";
					
												           
												//echo $qry2."<br />";
														    $rst2 = mysql_query($qry2);
															$tnm="";
														    while($row2 = mysql_fetch_assoc($rst2)){
															 
                                                               $tnm .= $row2['traveler_nm'].",";
															}
													   

												?>

														 <td><?=$tnm?></td>
													</tr>
												
												
												<?php } ?>
													
												</tbody>
											</table>
											<?php
											/*

											$qry = "SELECT  a.p_code,a.reserveCode,a.p_name, COUNT(b.p_cnt) cnt,b.meet_area,b.meet_area2 FROM reserve_info a,(SELECT a.reserveCode ,a.p_cnt,a.meet_area,a.meet_area2,a.p_name FROM reserve_info a,reserve_traveler b WHERE a.reserveCode=b.reserveCode && a.p_code = '$p_code' && a.parent='SUB'
                                             && a.p_code NOT LIKE '%ADD%' && a.stDate='$st' && a.rev_status='ORDER') b WHERE a.reserveCode=b.reserveCode && a.parent='MAIN' group by a.p_code";
											 */
											$qry = "SELECT a.reserveCode,a.p_name, b.cnt,b.meet_area FROM reserve_info a,(SELECT a.reserveCode ,COUNT(a.p_cnt) cnt,a.meet_area,a.p_name FROM reserve_info a,reserve_traveler b WHERE a.reserveCode=b.reserveCode && a.p_code = '$p_code' && a.parent='SUB'
                                             && a.stDate='$stdate' && a.rev_status!='CANCEL' GROUP BY a.reserveCode ORDER BY a.reserveCode DESC) b  WHERE a.reserveCode=b.reserveCode && a.reserveCode IN (SELECT reserveCode FROM  tour_car WHERE  sub_eCode='$s_code' ) && a.parent='MAIN' group by a.reserveCode order by a.p_code";
										//	echo $qry;
										    $rst = mysql_query($qry);
											while($row = mysql_fetch_assoc($rst)){
												if ($oldpname != $row['p_name']) {
												
											?>
											
											▶ 복합상품 <?=$row['p_name']?>
											<table id="custom_table" class="table table-bordered table-condensed custom_table">
												<thead>
													<tr>  
													  
													  <th class="tcenter" width='30%'>픽업장소</th>
													  <th class="tcenter" width='5%'>샌딩장소</th>
													  <th class="tcenter" width='5%'>인원수</th>
													  <th class="tcenter" width='*'>명단</th>
													  
													 
													</tr>
												</thead>
												<tbody>
												
													<tr>
						      
														  <td><?=$row['meet_area']?></td>
														  <td ><?=$row['meet_area2']?></td>
														  <td ><?=$row['cnt']?></td>
												  
												
												<?php
															$qry2="select b.traveler_nm from reserve_info a,reserve_traveler b where a.reserveCode=b.reserveCode && a.reserveCode='{$row['reserveCode']}' && a.stDate='$stdate' && a.p_code='$p_code' && a.parent='SUB'";
												            //echo $qry2."TEST<br />";
														    $rst2 = mysql_query($qry2);
															$tnm="";
														    while($row2 = mysql_fetch_assoc($rst2)){
															 
                                                               $tnm .= $row2['traveler_nm'].",";
															}
													

												?>

														 <td><?=$tnm?></td>
													</tr>
											
													
												</tbody>
											</table>
											   <?php $oldpname = $row['p_name']; 
												} else  {
												
												?>
											 <table id="custom_table" class="table table-bordered table-condensed custom_table">
												<thead>
													<tr>  
													  
													  <th class="tcenter" width='30%'></th>
													  <th class="tcenter" width='5%'></th>
													  <th class="tcenter" width='5%'></th>
													  <th class="tcenter" width='*'></th>
													  
													 
													</tr>
												</thead>
												<tbody>
													<tr class="table table-bordered table-condensed custom_table">
						      
														  <td><?=$row['meet_area']?></td>
														  <td ><?=$row['meet_area2']?></td>
														  <td ><?=$row['cnt']?></td>
												  
												
												<?php
															$qry2="select b.traveler_nm from reserve_info a,reserve_traveler b where a.reserveCode=b.reserveCode && a.reserveCode='{$row['reserveCode']}' && a.stDate='$stdate' && a.p_code='$p_code' && a.parent='SUB'";
												            /////echo $qry2."TEST<br />";
														    $rst2 = mysql_query($qry2);
															$tnm="";
														    while($row2 = mysql_fetch_assoc($rst2)){
															 
                                                               $tnm .= $row2['traveler_nm'].",";
															}
													

												?>

														 <td><?=$tnm?></td>
													</tr>
											
													
												</tbody>
											</table>
											   <?php $oldpname = $row['p_name']; }?>									   
										<?php }?>


										
											
											▶ 호텔정보 
											<table id="custom_table" class="table table-bordered table-condensed custom_table">
												<thead>
													<tr>  
													  
													  <th class="tcenter" width='30%'>날짜</th>
													  <th class="tcenter" width='*'>호텔명</th>
													  <th class="tcenter" width='10%'>QQ</th>
													  <th class="tcenter" width='10%'>합계</th>
													  <th class="tcenter" width='10%'>통화</th>
													 
													</tr>
												</thead>
												<tbody>
												<?php
															$qry2="SELECT sum(a.pcnt) cnt,a.hotel_code,a.stDate,b.h_name,b.m_rate,a.day FROM hotel_assign a ,product_hotel b WHERE a.hotel_code=b.h_code && a.sub_eCode='$s_code' 
													        GROUP BY a.hotel_code,a.stDate,a.day order by a.day asc";
												            //echo $qry2."TEST<br />";
														    $rst2 = mysql_query($qry2);
															$tnm="";
														    while($row2 = mysql_fetch_assoc($rst2)){
															 
                                                              $s_date = explode("-",$row2['stDate']);
										
															  $add_date = $row2['day']-1;

															  $local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));	
												?>
													<tr>
						      
														  <td><?=$local_start?></td>
														  <td ><?=$row2['h_name']?></td>
														  <td ><?=$row2['cnt']?></td>
												     	  <td><?=$row2['cnt']?></td>
														  <td><?=$row2['m_rate']?></td>
													</tr>
											    <?php    } ?>
													
												</tbody>
											</table>
																						
										   
											▶ 예약자 목록
											<table id="custom_table" class="table table-bordered table-condensed custom_table">
												<thead>
													<tr>  
													  <th width='5%'>번호</th>
													  <th class="tcenter" width='13%'>투어고객(인원)</th>
													  <th class="tcenter" width='10%'>연락처</th>
													  <th class="tcenter" width='15%'>결제상태</th>
													  <th class="tcenter" width='10%'>잔금</th>
													  <th class="tcenter" width='10%'>접수자</th>
													  <th class="tleft" width='*'>진행사항</th>
													 
													</tr>
												</thead>
												<tbody>
													<?php custlist(); ?>
													<tr>
													  
													  <td colspan=6 class="text-right">&nbsp;<b>&nbsp;총수금금액 :  <?=$sign?> <?=$totbal_t?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b><td>
													  
										
													</tr>  
												</tbody>
											</table>

											
									
								<br />
								<br />
							</form>
						</div>
					
				</div><!-- -->
			</div>                
	

	</div>
  
    <script>
		$(document).ready(function () {
           
            
           
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
		   window.open("base_reservation_m.php?estimateCode="+r_code+"&pricet="+pricet+"&division=3&pdx=2&sub=15",winName,"width=900,height=1080,scrollbars=1");
	    }

		function pageprint2(s_code,stdate)
		{
			 
			
           var winName = "all_"+(ctr++);
		   window.open("print_customer.php?s_code="+s_code+"&stdate="+stdate,winName,"width=900,height=1080,scrollbars=1");
  
		}

		
      
	</script>
    </body>
</html>
