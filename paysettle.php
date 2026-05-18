<?php
    include "include//inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	
	$recus = getReserveTrRepre($rev);
	$reserve_info = getReserveInfo($rev);
	$randinfo = getRandInfo($seqNo);

	if ($mode1 == "delete") {
			$del_id = (int)$_POST['del_id'];
			$qry_del = "DELETE FROM rand_pay WHERE id='$del_id'";
			mysql_query($qry_del, $dbConn);

			// 남은 결제 합계 재계산
			$qry_sum = "SELECT COALESCE(SUM(payment), 0) as sum_paid FROM rand_pay WHERE reserveCode='$rev' AND seq_rand='$seqNo'";
			$rst_sum = mysql_query($qry_sum, $dbConn);
			$row_sum = mysql_fetch_assoc($rst_sum);
			$sum_paid = $row_sum['sum_paid'];

			if ($randinfo['money_type'] == 'credit') {
				$new_cur_amt = $sum_paid;
			} else {
				$new_cur_amt = -$sum_paid;
			}

			if ($sum_paid > 0 && $sum_paid >= $randinfo['amt']) {
				$new_status = 'SETTLEDONE';
			} else {
				$new_status = $randinfo['status'];
			}

			$qry_upd = "UPDATE rand_company SET cur_amt='$new_cur_amt', status='$new_status' WHERE part_id='$rand_id' AND seq_no='$seqNo'";
			mysql_query($qry_upd, $dbConn);

			Misc::jvAlert("삭제 완료!!!");
			echo "<meta http-equiv='refresh' content='0; url=./paysettle.php?rand_id=$rand_id&seqNo=$seqNo&rev=$rev'>";
	}

	if ($mode1 == "save") {
			if ($dealType == 'credit') {
				if (($randinfo['cur_amt'] == "") || ($randinfo['cur_amt'] == "0")) {
					$ccamt = $amt;
				} else {
					
					$ccamt1 = $randinfo['cur_amt']+$amt;
					$ccamt = $ccamt1;
					//echo $ccamt1."|".$randinfo[cur_amt]."|+".$randinfo[amt]."<br>";
				}
				
				if ($ccamt == $randinfo['amt']) {
				  $amta = $randinfo['amt'];
				} else { 
				  $amta = $ccamt;
				}
				
			} else {	
				if (($randinfo['cur_amt'] == "") || ($randinfo['cur_amt'] == "0")) {
					$ccamt = $amt;
					//echo "!!";
				} else {
					if ($amt > $randinfo['cur_amt']) {
						$amt = $randinfo['cur_amt'];
					}
					//$ccamt1 = ($randinfo['cur_amt']-$amt);
					//$ccamt = $randinfo['amt']+ $ccamt1;
					//echo "!";
				}
				//echo $ccamt1."|".$randinfo[cur_amt]."|-".$randinfo[amt]."<br>";
				if ($ccamt == 0) {
				  $amta = -$randinfo['amt'];
				} else {
				  $amta = -$ccamt;
				}
			}
			//echo $amta;
			//exit;
			//$calMemo = $randinfo[settle_memo]."<br/>".$popMemo;
			$rp_amt = ($dealType == 'debit') ? -abs($amt) : abs($amt);
			$qry1= "insert into prtadmindb.rand_pay 
											( 
											rand_id, 
											reserveCode, 
											tr_date,
											rand_date,
											stDate,
											tr_type, 
											tr_bank, 
											trans_rate, 
											trans_type, 
											pay_method, 
											payment, 
											r_payment, 
											set_memo, 
											u_id,
											seq_rand,
											wdate
											)
											values
											( 
											'$rand_id', 
											'$rev', 
											'$popStartDate', 
											'$rdate',
											'$sdate',
											'', 
											'$popBank', 
											'', 
											'$dealType', 
											'$popPayment',
											'$rp_amt',
											'',
											'$popMemo', 
											'{$user_dbinfo['userid']}', 
											'$seqNo',
											now()
											);";
			//echo $qry1;
			//exit;

			$rst1 = mysql_query($qry1,$dbConn);

			if (($amta== 0) || ($amta== $randinfo['amt'])) {
				$stap = "SETTLEDONE	";
				
			} else {
				$stap = $randinfo['status'];

			}
			$qry2 = "update rand_company set cur_amt = '$amta' ,status='$stap',settle_memo='$calMemo' where part_id = '$rand_id' && seq_no = '$seqNo'";
			$rst2 = mysql_query($qry2);
			if (mysql_affected_rows($dbConn) == 0) {
				$qry2_ins = "insert into rand_company
								(seq_no, reserveCode, part_area, part_id, money_type, base_rate, amt, cur_amt, tr_date, p_memo, status, settle_memo, u_id, rand_date, wdate)
								values
								('$seqNo', '$rev', '', '$rand_id', '$dealType', '', '$amta', '$amta', '$popStartDate', '', '$stap', '$popMemo', '{$user_dbinfo['userid']}', now(), now())";
				mysql_query($qry2_ins, $dbConn);
			}
			Misc::jvAlert("저장 완료!!!");
			echo "<meta http-equiv='refresh' content='0; url=./paysettle.php?rand_id=$rand_id&seqNo=$seqNo&rev=$rev'>";

	}
	
    $recus = getReserveTrRepre($rev);
	$reserve_info = getReserveInfo($rev);
	$randinfo = getRandInfo($seqNo);

	if ($randinfo['money_type'] == "credit") {
		    if (($randinfo['cur_amt'] == "") || ($randinfo['cur_amt'] == "0")) {
				$amtb = $randinfo['amt'];
				$amt = "<font color=blue>$$amtb</font>";
			} else {
				$amtb = $randinfo['amt']- $randinfo['cur_amt'];
				$amt = "<font color=blue>$$amtb</font>";
			}
	
			
			
	} else {
		    if (($randinfo['cur_amt'] == "") || ($randinfo['cur_amt'] == "0")){
				$amtb = $randinfo['amt'];
				$amt = "<font color=red>$$amtb</font>";
			} else {
				if ($randinfo['cur_amt'] > $randinfo['amt']) {
					$amtb =  $randinfo['amt'];
				} else {
					$amtb = $randinfo['amt']- $randinfo['cur_amt'];
				}
				//$amtb = $randinfo['amt']+ $randinfo['cur_amt'];
				$amt = "<font color=red>$$amtb</font>";
			}
		
	}
	
	
	$uinfo=getinfo_dbMember($rand_id);
	
	
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
           
			<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker.css" rel="stylesheet" type="text/css" />
		<!-- timepicker -->
            <!-- <link rel="stylesheet" href="lib/timepicker/css/bootstrap-timepicker.css" /> -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/css/bootstrap-timepicker.min.css" />
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
		<!--<link id="link_theme" rel="stylesheet" href="css/green.css">-->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.11/css/all.css" integrity="sha384-p2jx59pefphTFIpeqCcISO9MdVfIm4pNnsL08A6v5vaQc4owkQqxMV8kg4Yvhaw/" crossorigin="anonymous">
		<!-- main styles -->
            <link rel="stylesheet" href="css/style.css" />
		<!-- purun css -->
			<link rel="stylesheet" href="css/purun.css?sid=5fe18a1a-0023-476e-afb3-66cdb279d9f7" />
		<!-- favicon -->
            <link rel="apple-touch-icon" sizes="180x180" href="img/favi/apple-touch-icon.png">
			<link rel="icon" type="image/png" sizes="32x32" href="img/favi/favicon-32x32.png">
			<link rel="icon" type="image/png" sizes="16x16" href="img/favi/favicon-16x16.png">
			<link rel="manifest" href="/site.webmanifest">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.css">
			
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
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
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
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.js"></script>
		<!-- timepicker -->
			<!-- <script src="lib/timepicker/js/bootstrap-timepicker.min.js"></script> -->
			<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/js/bootstrap-timepicker.min.js"></script>
		<!-- clockpicker -->
			<script src="lib/bootstrap-clockpicker/dist/bootstrap-clockpicker.min.js"></script>

		<!-- switch buttons -->
			<script src="lib/bootstrap-switch/dist/js/bootstrap-switch.min.js"></script>
        <!-- datatables -->
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
			<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/v/bs/jszip-2.5.0/dt-1.10.18/af-2.3.2/b-1.5.4/b-colvis-1.5.4/b-flash-1.5.4/b-html5-1.5.4/b-print-1.5.4/cr-1.5.0/fc-3.2.5/fh-3.1.4/kt-2.5.0/r-2.2.2/rg-1.1.0/rr-1.2.4/sc-1.5.0/sl-1.2.6/datatables.min.js"></script>
			<script type="text/javascript" src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
			<link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
            <script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
			
		<!-- purun js -->
			<script src="js/purun.js?sid=b778ad81-59cf-49a4-b7bf-b9bc7808d745"></script>

		<!-- purun_lee js -->
			<script src="js/purun_lee.js?sid=f10d80e0-c59c-4b4f-8927-17e44a330d8e"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>
	</head>
<style>
    /*div.dt-buttons {
        float: right; 
        padding-bottom: 10px;
    }*/
    
</style>
<body>
	<div id="contentwrapper" class="reservationDetailForm">
         <?php if ($mode != 'down'): ?>
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">업체정산</a></li>
					<li>업체별정산현황</li>
					<li>정산처리</li>
				</ul>
			</div>
		<?php endif; ?>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					
					<div class="row">
					 <div class="col-sm-12">
					  <form action="<?php $PHP_SELF;?>?rand_id=<?=$rand_id?>&seqNo=<?=$seqNo?>&rev=<?=$rev?>&rdate=<?=$rdate?>&sdate=<?=$sdate?>" id="frmbb" name="frmbb"  method="post">
					   <input type="hidden" name="mode1" id="mode1" value="save">
					   <input type="hidden" name="rdate" id="rdate" value="<?php echo $popStartDate?>">
					   <input type="hidden" name="sdate" id="sdate" value="<?php echo $sdate?>">
                        <div class="row">
                            <div class="col-sm-12">
                                <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="active text-center formHeader">업체명</td>
                                            <td colspan="6"><?=$uinfo['kor_name']?></td>
                                            <td colspan="2" class="active text-center formHeader">행사명</td>
                                            <td colspan="6"><?=$reserve_info['p_name']?></td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">예약코드/고객명</td>
                                            <td colspan="6"><?=$rev?> <?=$recus['traveler_nm']?></td>
                                            <td colspan="2" class="active text-center formHeader">거래예정금액</td>
                                            <td colspan="6"><?=$amt?></td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">거래타입</td>
                                            <td colspan="14">
                                                <label class="radio-inline">
                                                    <input type="radio" name="dealType" <?php if ($randinfo['money_type'] == "credit") { ?> checked <?php }?> value="credit"> 크레딧(업체로부터 수금)
                                                </label>
                                                <label class="radio-inline">
                                                    <input type="radio" name="dealType" <?php if ($randinfo['money_type'] == "debit") { ?> checked <?php }?> value="debit"> 데빗(업체에게 지급)
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">거래일자</td>
                                            <td colspan="14">
											    <div class="input-group input-group-sm">
                                                   <input type="date" id="popStartDate" name="popStartDate" class="inpubase tourDate" value="<?=date("Y-m-d")?>" placeholder="거래일자" size="50"/>
												</div>
                                            </td>
                                        </tr>
                                        <tr>       
                                            
                                            <td colspan="2" class="active text-center formHeader">거래금액</td>
                                            <td colspan="14">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="input-group input-group-sm">
                                                            
                                                            <input type="text" name='amt' class="inpubase md" placeholder="거래금액" aria-label="거래금액">
                                                        </div>
                                                    </div>
                                                </div>    
                                            </td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">결제방법</td>
                                            <td colspan="6">
                                                <select class="form-control" name="popPayment">
												    <option selected value="">결제방법</option>
                                                    <option value="check">체크</option>
                                                    <option value="cash">현금</option>
                                                    <option value="credit">크레딧카드</option>
													<option value="wireko">한국 계좌 송금</option>
													<option value="wireus">미국 계좌 송금</option>
													<option value="airsys">항공시스템</option>
                                                </select>
                                            </td>
                                            <td colspan="2" class="active text-center formHeader">거래은행</td>
                                            <td colspan="6">
                                                <select class="form-control" name="popBank">
                                                    <option selected>은행명</option>
                                                    <option value="chase">Chase Bank</option>
                                                    <option value="pnc">PNC Bank</option>
													<option value="capital">Capital One</option>
													<option value="hana1">(한국) 하나은행 원화계좌</option>
													<option value="hana2">(한국) 하나은행 다국적계좌</option>	
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="active text-center formHeader">정산메모</td>
                                            <td colspan="14">
                                                <input type="text" name="popMemo" class="form-control" aria-label="정산메모" value=""/> 
                                            </td>
                                        </tr>
                                        <tr>
                                           <td colspan="16" class="text-center"><button type="submit" class="btn btn-xs btn-primary js-pay">결제하기</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br />
                                <div class="row">
                                    <div class="col-sm-12">
                                        <table class="table table-striped table-bordered table-hover table-condensed js-productTable">
                                            <thead>
                                                <tr>
                                                    <th>거래일자</th>
                                                    <th>거래타입</th>
                                                    <th>결제방법</th>
                                                    <th>거래금액</th>
                                                    <th>거래은행</th>
                                                    <th>정산메모</th>
                                                    <th>등록자</th>
                                                    <th>삭제</th>
                                                </tr>
                                            </thead>
                                            <tbody>
											<?php 
												$qry2 = "SELECT * FROM  rand_pay WHERE reserveCode='$rev' && seq_rand='$seqNo'";
												$rst2 = mysql_query($qry2);
												//echo $qry2;
												while($row2 = mysql_Fetch_assoc($rst2)){
													switch($row2['pay_method']) {
														case "check":
															$payst = "체크";
															break;
														case "cash":
															$payst = "현금";
															break;
														case "credit":
															$payst = "크레딧카드";
															break;
														case "wireko":
															$payst = "한국계좌송금";
															break;
														case "wireus":
															$payst = "미국계좌송금";
															break;
														case "airsys":
															$payst = "항공시스템";
															break;
														
														default:
															$payst = "해당없음";
															break;
													}

													if ($row2['trans_type'] == "credit") {
														$transtype = "크레딧";
													} else {
														$transtype = "데빗";
													}
													switch($row2['tr_bank']) {
														case "chase":
															$bank = "Chase Bank";
															break;
														case "pnc":
															$bank = "PNC Bank";
															break;
														case "capital":
															$bank = "Capital One";
															break;
														case "hana1":
															$bank = "(한국)하나은행 원화계좌";
															break;
														case "hana2":
															$bank = "(한국)하나은행 다국적계좌";
															break;
														
														default:
															$bank = "해당없음";
															break;
													}

													$tinfo=getinfo_dbMember($row2['u_id']);
											?>
											
													<tr>
														<td align="center"><?=$row2['tr_date']?></td>
														<td align="center"><?=$transtype?></td>
														<td align="center"><?=$payst?></td>
														<td align="right">$<?=number_format($row2['payment'],2)?></td>
														<td align="center"><?=$bank?></td>
														<td ><?=$row2['set_memo']?></td>
														<td align="center"><?=$tinfo['kor_name']?></td>
															<td align="center">
																<form method="post" action="paysettle.php?rand_id=<?=$rand_id?>&seqNo=<?=$seqNo?>&rev=<?=$rev?>" style="margin:0">
																	<input type="hidden" name="mode1" value="delete">
																	<input type="hidden" name="del_id" value="<?=$row2['id']?>">
																	<button type="button" class="btn btn-xs btn-danger js-del-pay">삭제</button>
																</form>
															</td>
													</tr>
											<?php 
												}
											?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                      </form>
						</div>
					</div>
				</div><!-- -->
			</div>                
	

	</div>
  
    <script>
		
		$(document).ready(function() {
		   
			
			$('#popStartDate').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
				
			});'
			$(document).on('click', '.js-del-pay', function () {
				if (confirm("삭제하시겠습니까?")) {
					$(this).closest('form').submit();
				}
			});

			$('body').on('click', '.js-pay', function () {
				if (confirm("결제 하시겠습니까?"))
				{
					$("#mode1").val("save");
					
				    $("#frmbb").submit();
				}
			})
	    });

		var ctr=0;
	    function openwin(r_code) { 
	       var winName = "all_"+(ctr++);
		   window.open("base_reservation_m.php?estimateCode="+r_code+"&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>",winName,"width=1300,height=700,scrollbars=1");
	    }
      
	</script>
    </body>
</html>
