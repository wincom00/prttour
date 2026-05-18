<?php
    include "include//inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

    $reserve_info = getReserveInfo($r_code);

?>
<!DOCTYPE html>
<html>
    <head>
	<?php
	    
             echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
	 ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>푸른투어 인트라넷</title>
        
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
			<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.css"/>
            <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css" />
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
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
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
		<!-- timepicker -->
			<!-- <script src="lib/timepicker/js/bootstrap-timepicker.min.js"></script> -->
			<script src="lib/bootstrap-timepicker/js/bootstrap-timepicker.min.js"></script>
		<!-- clockpicker -->
			<script src="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.js"></script>

		<!-- switch buttons -->
			<script src="lib/bootstrap-switch/dist/js/bootstrap-switch.min.js"></script>
        <!-- datatables -->
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
			
            <script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>

		<!-- paran js -->
			<script src="js/paran.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>

		<!-- paran_lee js -->
			<script src="js/paran_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8e"></script>
			<style type="text/css">
			  @media print {
				  @page { margin: 2.6 em;margin-top: 2.6 em; }
				  body { margin-top: 4 em; 
						margin-left: 0 cm;
						margin-right: 0 cm;
						margin-bottom: 1 cm;
				  }
			  }
			</style>
	</head>
<?php
  
	
   
?>


<body>
    <br />
	<br />
	<div id="contentwrapper" class="reservationDetailForm">
         
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">직원정산</a></li>
					<li>직원별정산현황</li>
					<li>결제정보</li>
				</ul>
			</div>
		
			
			
			<div class="row">
				<div class="col-sm-12">
					<table class="table table-bordered table-condensed gridSixteen paymentProcessingTable formDetail">
						<tbody>
							<tr>
								<td colspan="2" class="active text-center formHeader">예약번호</td>
								<td colspan="6"><?=$reserve_info['reserveCode']?></td>
								<td colspan="2" class="active text-center formHeader">대표예약자</td>
								<td colspan="2"><?=$reserve_info['book_pri']?></td>
								<td colspan="2" class="active text-center formHeader">인원</td>
								<td colspan="2" class="text-center"><?=$reserve_info['p_cnt']?></td>
							</tr>
							<tr>
								<td colspan="2" class="active text-center formHeader">상품명</td>
								<td colspan="6">[<?=$reserve_info['p_code']?>] <?=$reserve_info['p_name']?></td>
								<td colspan="2" class="active text-center formHeader">출발일</td>
								<td colspan="6"><?=$reserve_info['stDate']?></td>
							</tr>
							<tr>
								<td colspan="2" class="active text-center formHeader">총금액</td>
								<td colspan="6"><?=$sign?> <?=$reserve_info['last_total']?></td>
								<td colspan="2" class="active text-center formHeader">잔금</td>
								<td colspan="6"><?=$sign?> <?=$reserve_info['last_bal']?></td>
							</tr>
						</tbody>
					</table>
					<table class="table table-bordered table-condensed gridSixteen paymentProcessingTable formDetail">
						<tbody>
							</tr>
								<td colspan="16" class="active text-center formHeader">결제정보</td>
							</tr>
							<tr>
								<td colspan="2" class="active text-center formHeader">거래일</td>
								<td colspan="1" class="active text-center formHeader">결제방법</td>
								<td colspan="2" class="active text-center formHeader">승인정보</td>
								<td colspan="2" class="active text-center formHeader">결제금액</td>
								<td colspan="1" class="active text-center formHeader">결제통화</td>
								<td colspan="2" class="active text-center formHeader">결제완료금액</td>
								
								<td colspan="1" class="active text-center formHeader">결제상태</td>
								<td colspan="1" class="active text-center formHeader">결제자</td>
								<td colspan="1" class="active text-center formHeader">결제</td>
								<td colspan="2" class="active text-center formHeader">결제메모</td>
							</tr>
							<?php
										$qry1 = "select * from payment_history where reserveCode = '$r_code' && payment_status !='RRQUEST' 
										order by wdate asc";
								
										$rst1 = mysql_query($qry1);
										$cntp = mysql_num_rows($rst1);
										$h = 0;
										if  ($cntp > 0) {
											while($p_row = mysql_fetch_assoc($rst1)):
											
												switch ($p_row['pay_method'])
												{
													case "cash" : 
														$cappay = "현금";
														break;   
													case "creditcard" : 
														$cappay = "신용카드";
														break;
													case "bcreditcard" : 
														$cappay = "지사단말기";
														break; 
													case "check" : 
														$cappay = "체크";
														break; 
													case "banktransfer" : 
														$cappay = "은행송금";
														break; 
													
													case "fundtransfer" : 
														$cappay = "금액이동";
														break;
													case "airsys" : 
														$cappay = "항공시스템";
														break;
													default : 
														$cappay = "초기입력";
														break; 
														
												}
												
												$signb = "U$";
												

												if ($p_row['rate_m'] == "0.0000") {
													$rate_m = "";
													$rate_c = "";
												} else {
													$rate_m = $p_row['rate_m'];
													$rate_c = $p_row['rate_c'];

												}
												$uinfo=getinfo_dbMember($p_row['register']);
												if (($p_row['payment_status'] != "DONE") && ($p_row['payment_status'] != "RETURN") ){
							 ?>
													<tr>
														<td colspan="2" class="text-center"><?=$p_row['wdate']?></td>
														<td colspan="1" class="text-center"><?=$cappay?></td>
														<td colspan="2" class="text-center"><?=$p_row['pay_info']?></td>
														<td colspan="2" class="text-center"><?=$signb?> <?=$p_row['payment']?></td>
														<td colspan="1" class="text-center"><?=$p_row['b_rate']?></td>
														<td colspan="2" class="text-center"><?=$signb?> <?=$p_row['rate_payment']?></td>
														
														<td colspan="1" class="text-center"><?=$p_row['payment_status']?></td>
														<td colspan="1" class="text-center"><?=$uinfo['kor_name']?></td>
														<td colspan="1" class="text-center"><button type="button" class="btn btn-xs btn-block btn-default js-process" value="<?=$p_row['payment']?>">결제</button></td>
														<td colspan="2" class="text-center"><?=$p_row['pay_memo']?></td>
													</tr>
									
							 <?php
												} else {
												 if ($p_row['payment_status'] == "RETURN") {
													$mius ="<font color=red>- ".$sign.$p_row['payment']."</font>";
													$cappay = "환불";
													$mius1 ="<font color=red>- ".$sign.$p_row['rate_payment']."</font>";
													$cappay = "환불";
												 } else {
													$mius = $signb.$p_row['payment'];
													$mius1 = $signb.$p_row['rate_payment'];
													$cappay = $cappay;
												 }
							 ?>
													<tr>
														<td colspan="2" class="text-center"><?=$p_row['wdate']?></td>
														<td colspan="1" class="text-center"><?=$cappay?></td>
														<td colspan="2" class="text-center"><?=$p_row['pay_info']?></td>
														<td colspan="2" class="text-center"><?=$mius?></td>
														<td colspan="1" class="text-center"><?=$p_row['b_rate']?></td>
														<td colspan="2" class="text-center"><?=$mius1?></td>
														
														<td colspan="1" class="text-center"><?=$p_row['payment_status']?></td>
														<td colspan="1" class="text-center"><?=$uinfo['kor_name']?></td>
														<td colspan="1" class="text-center"></td>
														<td colspan="2" class="text-center"><?=$p_row['pay_memo']?></td>
													</tr>
							 <?php
											}
										$h++;
										endwhile;
								} else {
							?>
											<tr>
													<td colspan="2" class="text-center"> </td>
													<td colspan="1" class="text-center"></td>
													<td colspan="2" class="text-center"></td>
													<td colspan="2" class="text-center"></td>
													<td colspan="1" class="text-center"></td>
													<td colspan="2" class="text-center"></td>
													<td colspan="1" class="text-center"></td>
													<td colspan="1" class="text-center"></td>
													<td colspan="1" class="text-center"></td>
													<td colspan="1" class="text-center"></td>
													<td colspan="2" class="text-center"></td>
											</tr>

							<?php
								}
							?>


							
						</tbody>
					</table>
					
					
				</div>
			</div>
			
						          
	

	</div>
  
    <script>
		$(document).ready(function () {
          
           
		})
		
	</script>
    </body>
</html>
