<?php
    include "include//inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

    // print_r($_POST);
    
	
?>
<!DOCTYPE html>
<html>
    <head>
	<?php
	    if($mode=='down') {
		    header("Content-type: application/vnd.ms-excel; charset=UTF-8"); 
			header("Content-Disposition: attachment; filename=inv".date('Ymd').".xls");
			header("Content-Description: PHP5 Generated Data");
		    echo "<meta http-equiv='Content-Type' content='application/vnd.ms-excel; charset=utf-8'/>";
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
				  @page 
					{ 
						size: auto;   /* auto is the initial value */ 

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
<?php
	
	//echo $_POST[seqNo];
	//exit;
	$g_dbinfo = getinfo_dbMember($rand_id);
	//print_r($_POST);
	//$picStr  = getPicGr2($s_code,$stdate);
	function custlist() {
		 global $dbConn,$s_date1,$s_date2,$_POST,$rand_id,$table_content1,$totpmt,$totpmt1,$totcnt;
		 extract($_POST);
		 for($i=0; $i<count($_POST['seqNo']); $i++)
		 {
			$s = $_POST['seqNo'][$i];
			
			$qry1 = "select a.seq_no as seqr,a.*,b.*
				from rand_company a, reserve_info b
				where a.reserveCode=b.reserveCode && a.part_id = '$rand_id'  && b.parent='MAIN'
				&& a.reserveCode='$sreserveCode[$s]' && a.seq_no = '$seq[$s]' 
				order by a.wdate desc";
			
			$rst1 = mysql_query($qry1,$dbConn);
			
			while($row1 = mysql_Fetch_assoc($rst1)){
				$recus = getReserveTrRepre($row1['reserveCode']);
				
				$sdate = $row1['stDate'];
				

				if (($row1['cur_amt'] == "") || ($row1['cur_amt'] == "0")) {
					$pamt = "$0";

					$pamt1 = "0";
				} else {
					$pamt = "${$row1['cur_amt']}";
					$pamt1 = "{$row1['cur_amt']}";
				}

				$pamtc = $row1['amt'] - $pamt1;
				$totpmt =$totpmt + $pamtc;
				$totpmt1 = $totpmt1 + $pamt1;
				$totcnt = $totcnt + $row1['p_cnt'];
				//echo $pmatc;
				echo "<tr>
							<td align='center'>$sdate</td>
							<td align='center'>{$row1['p_name']}</td>
							<td align='center'>{$recus['traveler_nm']}</td>
							<td align='center'>{$row1['p_cnt']}</td>
							<td align='right'>$$pamtc</td>
							
							<td align='right'>$pamt</td>
							
						</tr>";


			}

		 }

		$table_content1 = "<table id='level4' class='table table-striped table-bordered table-hover table-condensed text-center'>
			<th>
				
				<td width=20% align=center>총 인원수</td>
				<td width=13% align=center>총 요청금액</td>
				<td width=13% align=center>총 페이먼트</td>
				
			</th>
			<tr height=35 bgcolor=#FFFFFF>
			   <td  align=center>&nbsp;</td>
				<td  align=right>$totcnt</td>
				<td width=13% align=right><b>$$totpmt</b></td>
				
				<td width=13% align=right><b>$$totpmt1</b></td>
			</tr>
			</table>";
		foreach ($_POST['seqNo'] as $i => $value) {
			echo "<input type='hidden' name='seqNo[]' value='$value'/>";
			
		}
		foreach ($_POST['sreserveCode'] as $i => $value) {
			echo "<input type='hidden' name='sreserveCode[]' value='$value'/>";
			
		}
		foreach ($_POST['seq'] as $i => $value) {
			echo "<input type='hidden' name='seq[]' value='$value'/>";
			
		}

		    
	}
	
	if ($mode == "send_email") {
			$sbj = "[푸른투어] 정산요청이 왔습니다.";
			extract($_POST);
			$data = $_POST; 
			$options = array( 
				'http' => array( 
				'method' => 'POST', 
				'content' => http_build_query($data)) 
			); 
		  
			// Create a context stream with 
			// the specified options 
			$stream = stream_context_create($options); 
			  
			// The data is stored in the  
			// result variable 
			$content = file_get_contents( 
					"http://www.myprt.online/invoice_cc.php?rand_id=$rand_id&s_date1=$s_date1&s_date2=$s_date2", false, $stream); 
			  
			//echo $rand_id.$s_date1.$s_date2.$seqNo."<br />";
			//print_r($seqNo);
			//echo $content;
			//exit;
            // 메세지
			$board_pds_pos = "/var/www/html/upload";
			$tmpName1 = $_FILES['userfile1']['tmp_name'];
			if(is_uploaded_file($tmpName1)){
				$pds_file1 = $_FILES['userfile1']['name'];
				$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);
				
				$fileloc1 = $board_pds_pos . "/" . $attc_name1['savedName'];
				
				array_push($atc_arr,$fileloc1);
				$attachment1 = $attc_name1['savedName'];
			}
			$tmpName2 = $_FILES['userfile2']['tmp_name'];
			if(is_uploaded_file($tmpName2)){
				$pds_file2 = $_FILES['userfile2']['name'];
				$attc_name2 = Misc::uploadFileUnsafely($tmpName2 , $pds_file2 , $board_pds_pos);
				
				$fileloc2 = $board_pds_pos . "/" . $attc_name2['savedName'];
				
				array_push($atc_arr,$fileloc2);
				$attachment2 = $attc_name2['savedName'];
			}
			///$msg = "* 추가 사항 <br />".$board_note."<br /><br />".$content;
			$revInfo= getReserveInfo($rev);
			$smail = randname($rand_id);
			if ($revInfo['book_email'] == "") {
				$cmail = $revInfo['book_email'];
			} else {
				$cmail = $smail['company_email'];
			}
			//exit;
			$msg = str_replace('{ADDINFO}',$tempText1,$content);
			$ret= mailsend_k($cmail,$sbj,$msg,$attachment1,$attachment2);
			
			$ret= mailsend_k('online@prttour.com',$sbj,$msg,$attachment1,$attachment2);
			echo "<br><font size=2 color=red><p align=center>이메일 전송완료!</p></font>";
			
			
			
	}


?>


<style>
    /*div.dt-buttons {
        float: right; 
        padding-bottom: 10px;
    }*/
    table { page-break-inside:auto }
    tr    { page-break-inside:avoid; page-break-after:auto }
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
					<li><a href="#">업체정산</a></li>
					<li>업체별정산현황</li>
					<li>인보이스출력</li>
				</ul>
			</div>
		<?php endif; ?>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					
					
						<div class="col-sm-12">
						    <form action="<?php $PHP_SELF;?>" name="frmName" id="frmName" method="post">
						      <input type="hidden" name="mode" id="mode" value="down">
							  <input type="hidden" name="rand_id" value="<?=$rand_id?>">
							  <input type="hidden" name="rev" value="<?=$rev?>">
							  <input type="hidden" name="s_date1" value="<?=$s_date1?>">
							  <input type="hidden" name="s_date2" value="<?=$s_date2?>">
							  <input type="hidden" name="seqNo" value="<?=$_POST['seqNo']?>">
                                <?php if ($mode != 'down'): ?>
                                <div class="row no-nav">
                                    <div id="custom_button" class="col-sm-12 text-right">
										<button type="submit" class="btn btn-xs btn-default js-excel" >엑셀</button>
                                        <button type="button" class="btn btn-xs btn-default js-mail" >이메일</button>
                                        <button type="button" class="btn btn-xs btn-default js-xxx" onclick="pageprint()">프린트</button>
                                    </div>
                                </div>
								<?php endif; ?>
                                
									
										<legend class="guide-assign-border"><span class="pull-left small text-muted">행사고객현황</span></legend>
										
										<br/>
											<table class="table table-bordered table-condensed">
										
													<tr>
														<td width="10%" class="titletd text-center">업체명</td>
														<td width="40%" class=""><?=$g_dbinfo['kor_name']?>
															
														</td>
														
													</tr>
													<!--<tr>
														<td width="10%" class="titletd text-center">픽업장소</td>
														<td width="40%" class="">	</td>
														<td width="10%" class="titletd text-center">가이드</td>
														<td width="40%" class="">									
														</td>
													</tr>-->
												
											</table>
											<br/>
											<table id="custom_table" class="table table-striped table-bordered table-hover table-condensed text-center">
												<thead>
													<tr>  
													  
													  <th class="tcenter" width='7%'>출발일</th>
													  <th class="tcenter" width='15%'>상품명</th>
													  <th class="tcenter" width='7%'>대표고객명</th>
													  <th class="tcenter" width='5%'>인원</th>
													  <th class="tcenter" width='10%'>요청금액</th>
													  <th class="tcenter" width='10%'>페이먼트</th>
													  
													  
													 
													 
													</tr>
												</thead>
												<tbody>
													<?php custlist(); ?>
												</tbody>
											</table>
											<?php
												echo $table_content1;
											?>
											<table id="custom_table" class="table table-striped table-bordered table-hover table-condensed text-center">
											<tbody>
											   <tr>
												<td><textarea name="tempText1"  rows=17 cols=100%></textarea></td>
											   </tr>
											   <tr>
													<td >첨부파일 : <input type=file name=userfile1 class="form_box" value="" style="width:600px"></textarea></td>
													
												</tr>
												<tr>
													<td >첨부파일 : <input type=file name=userfile2 class="form_box" value="" style="width:600px"></textarea></td>
													
												</tr>
											   <tr>
												<td style='text-align:left;padding-left:50px'><b>
												    PLEASE MAKE THE CHECK or MONEY ORDER PAYABLE TO <font color='blue'>"PRT AGENCY"</font>.<br />  
													THANK YOU. <br /><br />          
													
													ADDRESS:324 BROAD AVE RIDGEFIELD NJ 07657<br />                      
													T: 201-778-4000 I F: 201-313-0890<br /><br />      
												    Bank Name      : CHASE BANK<br /> 
													Bank Address      : 188-190 MAIN ST FORT LEE, NJ07024<br />
													ABA #         : 021000021<br /><br />

													Beneficiary Name   : PRUNE WORLD INC  OR PRT AGENCY<br /> 
													Address      : 324 BROAD AVE RIDGEFIELD ,NJ07657<br />
													Account #      : 617168526<br />
													SWIFT CODE  CHASE BANK #CHASUS33</b></td>
											   </tr>
									        </tbody>
											</table>
							</form>
						</div>
					
				</div><!-- -->
			</div>                
	

	</div>
  
    <script>
		$(document).ready(function () {
            pt.initReservationList()
            
            pt.initReservationDetail()

           
            
            //var args = {paging:false, ordering:false, info:false,scrollX:true,scrollY: 200};
           
            $('body').on('click','.js-mail',function() {
				
				$("#mode").val("send_email");
				alert($("#mode").val());	
				$("#frmName").submit();
				
			});
           
		})
		
		function pageprint()
		{
			 
			
           window.print();
  
		}
		var ctr=0;
	    function openwin(r_code,pricet) { 
	       var winName = "all_"+(ctr++);
		   window.open("base_reservation_m.php?estimateCode="+r_code+"&pricet="+pricet+"&division=3&pdx=2&sub=15",winName,"width=900,height=1080,scrollbars=1");
	    }
      
	</script>
    </body>
</html>
