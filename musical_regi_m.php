<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }
	
	
    $pcap = "뮤지컬/스포츠예약등록";
	/////////////////////////////////////
	$reserveCode = $_GET['reserveCode'];
	$pInfor = getMusicalReserveSelfinfo($reserveCode);
	//$api_return = explode("@",$pInfor[api_result]);
	if ($reserveCode != "") {
        $estimateCode = $reserveCode;
	}
    $today = date("Y-m-d");
	if ( $pInfor['h_code'] == "") {
        $productInfoM = getMusicalBasic($p_code);
		$api_return[0]= "승인이 필요합니다";
	} else  {
		$productInfoM = getMusicalBasic($pInfor['h_code']);
	    $api_return = explode("@",$pInfor['api_result']);

	}

	if ($mode == "save") { 
		
		  if ($pInfor['reserveCode'] =="") {
				 
		      
					
				    
					$reserveNum = getNumMusicalReserveSelf();

					// 최종 예약번호
					$reserveCode = "PURM".date("ymd").$reserveNum;

					
					
				    $reserve_qry1 = "insert into musical_self_info (reserveNum,
																	reserveCode,
																	member_id,
																	member_name,
																	member_phone,
																	h_code,
																	h_name,
																	act_date,
																	act_time,
																	member_adult,
																	member_baby,
																	member_boy,
																	consult_content,
																	register,
																	wdate,
																	reserve_mail,
																	pay_method,
																	pay_status,
																	musical_seqNo,
																	musical_type,
																	musical_price,
																	musical_sale_price,
																	total_amount,
																	status,
																	total_amt,
																	balance,
																	api_result) values ('$reserveNum',
																							'$reserveCode',
																							'$member_id',
																							'$member_name',
																							'$member_phone',
																							'$h_code',
																							'$h_name',
																							'$act_date',
																							'$act_time',
																							'$member_adult',
																							'0',
																							'0',
																							'$consult_content',
																							'{$user_dbinfo['userid']}',
																							now(),
																							'$reserve_email',
																							'$pay_method',
																							'$pay_status',
																							'$musical_seqNo',
																							'$musicalValue[2]',
																							'$musical_price',
																							'$musical_sale_price',
																							'$total_amount',
																							'READY',
																							'$total_amount',
																							'$musical_balance',
																							'$api_result')";
			
				//print_r($reserve_qry1);

				$rst1 = mysql_query($reserve_qry1);
		  
		  
				$balance = getBalance1($reserveCode);
				/**
				* 가격 히스토리 테이블 넣기
				*/
				$pre_history_qry1 = "delete from payment_history where reserveCode = '$reserveCode'";
				$pre_history_rst1 = mysql_query($pre_history_qry1);


				
				$history_rst1 = mysql_query($history_qry1);
                $history_qry1 = "INSERT INTO payment_history (
    											  reserveCode,
												  pay_method,
												  pay_info,
												  payment,
												  b_rate,
												  rate_payment,
												  rate_c,
												  rate_m,
												  payment_status,
												  pay_memo,
												  register,
												  conf_user,
												  conf_p,
												  conf_date,
												  rconf_user,
												  rconf_date,
												  wdate
												)
												VALUES
												  (
													'$reserveCode',
													'init',
													'',
													'$total_amount',
													'USD',
													'$total_amount',
													'',
													'0',
													'READY',
													'초기결제금액',
													'{$user_dbinfo['userid']}',
													'',
													'',
													'',
													'',
													'',
													now()
												  )";
				$history_rst1 = mysql_query($history_qry1);

				
				
				for($a=0; $a<count($agent_id); $a++)
				{
					$agent_division = "credit";
					$a_start = "$s_date 00:00:01";
					

				   $agenet_ary1 = "INSERT INTO rand_company (
												
												  reserveCode,
												  part_area,
												  part_id,
												  money_type,
												  base_rate,
												  amt,
												  cur_amt,
												  tr_date,
												  p_memo,
												  STATUS,
												  settle_memo,
												  u_id,
												  rand_date,
												  air_ptype,
												  wdate
												)
												VALUES
												  (
												
													'$reserveCode',
													'',
													'$agent_id[$a]',
													'$agent_division',
													'USD',
													'$agent_cms[$a]',
													'0',
													'',
													'$agent_memo[$a]',
													'READY',
													'',
													'{$user_dbinfo['userid']}',
													now(),
													'',
													now()
												  )";

					$agent_rst1 = mysql_query($agenet_ary1);

				} 
				
				$goUrl_1 = "musical_regi.php?division=$division&pdx=$pdx&sub=$sub&reserveCode=$reserveCode";
				echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		
				exit;
		  }  
		
	} else if ($mode == "modify") {
		    if (($r_st != "CANCEL") && ($r_st != "ORCANCEL")) { 
				$qry1 = "update musical_self_info set 			member_name = '$member_name',
															member_phone = '$member_phone',
															h_code = '$h_code',
															h_name = '$h_name',
															act_date = '$act_date',
															act_time = '$act_time',
															member_adult = '$member_adult',
															consult_content = '$consult_content',
															reserve_mail = '$reserve_email',
															pay_method = '$pay_method',
															pay_status = '$pay_status',
															musical_seqNo = '$musical_seqNo',  
															musical_price = '$musical_price',
															musical_sale_price = '$musical_sale_price ',
															total_amount = '$total_amount',
															total_amt = '$total_amount',
															status = '$r_st' where reserveCode = '$reserveCode'";
				$rst1 = mysql_query($qry1);
		
		        
				/**
				* 발란스 계산해본다.
				*/
				$balance = getBalance1($reserveCode);
				if ($balance == 0) {
					$pay_status =   "status ='DONE',";
				} else {
					$pay_status =   'Pending';
				}
				$update_qry1 = "update musical_self_info set balance = '$balance' where reserveCode = '$reserveCode'";
				$update_rst1 = mysql_query($update_qry1);
				
				$pre_rand_qry1 = "delete from rand_company where reserveCode = '$reserveCode'";
				$pre_rand_rst1 = mysql_query($pre_rand_qry1);
				$pre_rand_qry1 = "delete from rand_company where reserveCode = '$reserveCode'";
				$pre_rand_rst1 = mysql_query($pre_rand_qry1);
                for($a=0; $a<count($agent_id); $a++)
				{
					$agent_division = "credit";
					$a_start = "$s_date 00:00:01";
					

				    $agenet_ary1 = "INSERT INTO rand_company (
												
												  reserveCode,
												  part_area,
												  part_id,
												  money_type,
												  base_rate,
												  amt,
												  cur_amt,
												  tr_date,
												  p_memo,
												  STATUS,
												  settle_memo,
												  u_id,
												  rand_date,
												  air_ptype,
												  wdate
												)
												VALUES
												  (
												
													'$reserveCode',
													'',
													'$agent_id[$a]',
													'$agent_division',
													'USD',
													'$agent_cms[$a]',
													'0',
													'',
													'$agent_memo[$a]',
													'READY',
													'',
													'{$user_dbinfo['userid']}',
													now(),
													'',
													now()
												  )";

					$agent_rst1 = mysql_query($agenet_ary1);

				}
				
			} else {
                $qry1 = "update musical_self_info set 		member_name = '$member_name',
															member_phone = '$member_phone',
															h_code = '$h_code',
															h_name = '$h_name',
															act_date = '$act_date',
															act_time = '$act_time',
															member_adult = '$member_adult',
															consult_content = '$consult_content',
															reserve_mail = '$reserve_email',
															pay_method = '$pay_method',
															pay_status = '$pay_status',
															musical_seqNo = '$musical_seqNo',  
															musical_price = '$musical_price',
															musical_sale_price = '$musical_sale_price ',
															total_amount = '$total_amount',
															total_amt = '$total_amount',
															status = '$r_st' where reserveCode = '$reserveCode'";
				$rst1 = mysql_query($qry1);
				
				$pre_rand_qry1 = "delete from rand_company where reserveCode = '$reserveCode'";
				$pre_rand_rst1 = mysql_query($pre_rand_qry1);
				$pre_rand_qry1 = "delete from rand_company where reserveCode = '$reserveCode'";
				$pre_rand_rst1 = mysql_query($pre_rand_qry1);

			}
		    
            
			 
            
			$goUrl_1 = "musical_regi.php?division=$division&pdx=$pdx&sub=$sub&reserveCode=$reserveCode";
			echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
	
			exit;
		
    
    
	} else if ($mode== "api") {
	    
			/*
			/* @ MUSICAL 예약하기
			*/
			$eventDate = explode("/",$act_date);

			$eventDate1 = "20".$eventDate[2]."-".$eventDate[0]."-".$eventDate[1];

			$eventTime = explode(":",$act_time);

			$eventTime1 = $eventTime[0]+12;
			$eventTime2 = substr($eventTime[1],0,2);


			$last_eventdate = $eventDate1."T".$eventTime1.":".$eventTime2.":00.0Z";


			require_once('libs/nusoap.php');
			$proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
			$proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
			$proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
			$proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
			$client = new nusoap_client('BIWSSimple_WSDL.wsdl', 'wsdl',
									$proxyhost, $proxyport, $proxyusername, $proxypassword);
			$err = $client->getError();


			// 이름 쪼개기
			$reserve_name= explode("/",$member_name);

			$param = array(
				'SaleTypesCode'         => 'F',
				'ProductId'          => $musical_seqNo,
				'OneShowCode'          => $h_code,
				'EventDateTime'          => $last_eventdate,
				'Quantity'          => $member_adult,
				'Price'          => $musical_price,
				'BookingLastName'          => $reserve_name[1],
				'BookingFirstName'          => $reserve_name[0],
				'BookingReferenceNumber'          => $reserveCode,
				'BookingNotes'          => '',
				'BookingEmailAddress' => $reserve_email,
				'BookingCellPhoneNumber' => array('CountryCode' => '1',
										  'area' => '201',
										  'Number' => '6468842500'),
				);

			
			//'ShowAddedDate'          => '1900-12-17T09:30:47.0Z'
			$headers = '
			<m:AuthHeader xmlns:m="http://tempuri.org/">
			<m:username>285578618</m:username>
			<m:password>W3#Q0xim</m:password>
			</m:AuthHeader>
			';


			$result = $client->call('NewOrder', array('parameters' => $param),'','',$headers);

			$err = $client->getError();
			if ($err) {
				// Display the error
				$data .= '<h2>Error</h2><pre>' . $err . '</pre>';

				$api_result = $data;

			} else {
				// Display the result
				//echo '<h2>Result</h2><pre>';
				//print_r($result);
				//echo '</pre>';

				$OrderID = $result['NewOrderResult']['diffgram']['NewDataSet']['Outbound']['OrderID'];
				$Status = $result['NewOrderResult']['diffgram']['NewDataSet']['Outbound']['Status'];
				$Product = $result['NewOrderResult']['diffgram']['NewDataSet']['Outbound']['Product'];
				$Description = $result['NewOrderResult']['diffgram']['NewDataSet']['Outbound']['Description'];
				$C_price = $result['NewOrderResult']['diffgram']['NewDataSet']['Outbound']['Price'];
				$C_quantity = $result['NewOrderResult']['diffgram']['NewDataSet']['Outbound']['Quantity'];
				if(empty($OrderID))
				{
					Misc::jvAlert("뮤지컬 API 오류!","history.go(-1)");
					exit;
				}

				$api_result = $OrderID."@".$Status."@".$Product."@".$Description."@".$C_price."@".$C_quantity;
			}

			$update_qry1 = "update musical_self_info set api_result = '$api_result' where reserveCode = '$reserveCode'";
			$update_rst1 = mysql_query($update_qry1);


	} else if ($mode == "paymentProcess") {

			  //payment history
			   
				if ($paymentmethod == "creditcard") { //신용카드
					   $order = $estimateCode;
					   $amt =   $clastpayamt;
					   $fname = $fcardname;
					   $lname = $lcardname;
					   $cardnum = $cardnum;
					   $month = $ccexpmo;
					   $mm=substr($ccexpyr,-2);
					   $year = $mm;
					   $cvv = $cvvnum;
					   $address_card = "USANaN$addressNaN$cityNaN$state";

						// 인증ONLY
					   $xType = "AUTH_CAPTURE";
						
					   $credit_result = credit_process($xType,$address_card,$zipcode,$cardnum,$ccv,$month,$year,$amt,$fname,$lname,$order);
					   /*echo "<br/><br/><br/><br/><br/><br/><pre>";
					   print_r($rst);
					   echo "</pre>";
					   //exit; */
					   //$credit_result[0]=1;
					   //$credit_result[6]="A";
					  
					   if($credit_result[0] == "2")
					   {
							
							$tour_credit_return_msg = "$credit_result[0] $credit_result[1] $credit_result[2] $credit_result[3] $credit_result[4] $credit_result[5] $credit_result[6] $credit_result[7]";

							
							echo "<script> window.alert('Declined! $credit_result[1] / $credit_result[2] / $credit_result[3] / $credit_result[4]'); history.go(-1); </script>";
							exit;

					   }
					   else if($credit_result[0] == "3")
					   {
							
							$tour_credit_return_msg = "$credit_result[0] $credit_result[1] $credit_result[2] $credit_result[3] $credit_result[4] $credit_result[5] $credit_result[6] $credit_result[7]";

							echo "<script> window.alert('Declined! $credit_result[1] / $credit_result[2] / $credit_result[3] / $credit_result[4]'); history.go(-1); </script>";
							exit;

							
					   }
					   else
					   {
			
							//$trans_id = $credit_result[7];
							  
							  if ($credit_result[6] != "") {
								 $pinfo = "Approved / $credit_result[6] $credit_result[7]";
								 $currencytype ="USD";
								 $payst1 = "DONE";
								 
								 $qry5 = "insert into payment_history 
													( 
													reserveCode, 
													pay_method, 
													pay_info, 
													payment, 
													b_rate, 
													rate_payment, 
													rate_c, 
													rate_m, 
													payment_status, 
													pay_memo, 
													register, 
													wdate
													)
													values
													( 
													'$estimateCode', 
													'$paymentmethod', 
													'$pinfo', 
													'$amt', 
													'USD', 
													'$clastpayamt', 
													'', 
													'$clastpayamt', 
													'$payst1', 
													'$ccmemo', 
													'{$user_dbinfo['userid']}', 
													now()
													);";
							
								   $rst5 = mysql_query($qry5,$dbConn);
								   $tlastpay=$lastbalance - $amt;
								   if ($tlastpay == 0) {
									  $paycap = "DONE";
								   } else if ($tlastpay > 0) {
									  $paycap = "PPAY";
								   } else if ($tlastpay < 0) {
									  $paycap = "OPAY";
								   } 
								   $update_qry1 = "update musical_self_info set balance = '$tlastpay' where reserveCode = '$estimateCode'";
								   $update_rst1 = mysql_query($update_qry1);

												
								   $rst6 = mysql_query($qry6,$dbConn);
										
							  } else {
									
								  Misc::jvAlert("결제 실패 다시 확인하시고 결제하세요!!!");
								   
								  $goUrl_1 = "musical_regi.php?division=$division&pdx=$pdx&sub=$sub&reserveCode=$reserveCode";
								  echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
	
								  exit;
								
							  }
					  }
					  
						
				} else { 
					   if ($currencytype == "CAD") {
								$ratepay = "BUY";
								$ratevalue = $buyrate;
						} else if ($currencytype == "USD") {
								$ratepay = "SELL";
								$ratevalue = $sellrate;
								
						}
						//echo $currencytype.'<br />S'.$sellrate.'<br />B'.$buyrate.'<br />R'.$ratevalue;

						$payst1 ="DONE";
				

					    $qry5 = "insert into payment_history 
													( 
													reserveCode, 
													pay_method, 
													pay_info, 
													payment, 
													b_rate, 
													rate_payment, 
													rate_c, 
													rate_m, 
													payment_status, 
													pay_memo, 
													register, 
													wdate
													)
													values
													( 
													'$estimateCode', 
													'$paymentmethod', 
													'', 
													'$lastpayamt', 
													'USD', 
													'$lastpayamt', 
													'$ratepay', 
													'', 
													'$payst1', 
													'$dmemo', 
													'$puser', 
													now()
													);";
						//echo $currencytype.'<br />S'.$sellrate.'<br />B'.$buyrate.'<br />R'.$ratevalue;
						//exit;
					   $rst5 = mysql_query($qry5,$dbConn);
					   $tlastpay=$lastbalance - $lastpayamt;
					   if ($tlastpay == 0) {
						  $paycap = "DONE";
					   } else if ($tlastpay > 0) {
						  $paycap = "PPAY";
					   } else if ($tlastpay < 0) {
						  $paycap = "OPAY";
					   } else if ($tlastpay == $lasttotal) {
						  $paycap = "READY";
					   }
					   $qry6= "update reserve_info 
											set
											last_bal = '$tlastpay' , 
											payment_st = '$paycap'  
											where
											reserveCode = '$estimateCode'   ";

									
					  $rst6 = mysql_query($qry6,$dbConn);

				}
			   Misc::jvAlert("결제 완료!!!");
			   if ($pricet == 1) {
				   $sub = "15";
				   $ty = 1;
			   } else if ($pricet == 3) {
				   $sub = "25";
				   $ty = 3;
			   }
			   $goUrl_1 = "musical_regi.php?division=$division&pdx=$pdx&sub=$sub&reserveCode=$reserveCode";
			   echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>"; 
			   exit;

	

	}
	
	////////////////////////////////////
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">예약관리</a></li>
					<li><a href="#">예약관리</a></li>
					<li><?= $pcap ?></li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
				<!-- //////////////////////////////////////////////-->
                    <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&reserveCode=<?=$reserveCode?>" id="product" name="product" method="post" Enctype="multipart/form-data">
			
					<table id="level4" class="txt_12" width="98%" align="center" border="0" cellspacing="0" cellpadding="0">
						
						<tr>
							<td colspan="4" height="50" align="center" bgcolor="#FFFFFF"><input type="button" value="&nbsp;예약 저장&nbsp;" onClick="go_submit('<?=$pInfor['reserveCode']?>')"></td>
							<td colspan="4" height="50" align="center" bgcolor="#FFFFFF"><input type="button" value="&nbsp;바우쳐&nbsp;" onClick="javascript:location.href='print_voucher.php?m_code=<?=$pInfor['h_code']?>&rcode=<?=$pInfor['reserveCode']?>'"></td>
						</tr>
						
					  </table>
					  <br>
					  <table class="table table-bordered table-condensed">
					
					   <input type="hidden" name="mode" id="mode" value="<?= $mode ?>">
					   <input type="hidden" name="mode2" id="mode2" >
					   <input type=hidden name=musical_api value="">
					   <input type=hidden name=musical_result value="<?=$api_result?>">
					   <input type=hidden name=order_status value="">
					   <input type="hidden" name="reserveCode" id="reserveCode" value="<?=$reserve_musical_info['reserveCode']?>">
					   <input type="hidden" name="seq_no" value="<?= $pInfor['seq_no'] ?>">
					   <input type="hidden" name="old_p_code" value="<?= $pInfor['h_code'] ?>"> 
					   <input type=hidden name=old_api_result value="<?= $reserve_musical_info['api_result'] ?>">
					  <tr >
							<td align="left" class="titletd" colspan=4>&nbsp;<b>예약 정보</b>
								
								</td>
						</tr>		
						<tr >
							<td class="titletd text-center">&nbsp;예약상태
								
								</td>
							<td width="15%" colspan=3>
								&nbsp;
								<input type="radio" name="r_st" value="READY" <?php if (($pInfor['status']=='READY') || ($pInfor['status']=='')) { ?> checked <?php } ?> >견적등록
								<input type="radio" name="r_st" value="CANCEL" <?php if ($pInfor['status']=='CANCEL') { ?> checked <?php } ?> >견적취소 
								<input type="radio" name="r_st" value="ORDER" <?php if ($pInfor['status']=='ORDER') { ?> checked <?php } ?> >발주
								<input type="radio" name="r_st" value="ORCANCEL" <?php if ($pInfor['status']=='ORCANCEL') { ?> checked <?php } ?> readOnly>발주취소
							
								</td>
							
							
							
						</tr>
						 
						<tr height="28">
							<td class="titletd text-center" >&nbsp;예약번호
								
								</td>
							<td width="15%" colspan=3>
						   <?php 
							 if ($pInfor['reserveCode'] != "") {
								echo "&nbsp;<b>".$pInfor['reserveCode']."</b>";
							 } else {
								
								  echo "&nbsp;자동생성";	
							 }	    
							?>		    
							</td>
							
							
							
						</tr>
						<tr  height="28">
							<td width="15%" class="titletd text-center" >공연명</td>
							<td width="35%"   colspan=3>
							   &nbsp;<input type="text" id="musical_seqNo" name="musical_seqNo"  size="10" readOnly align='right' class="form_box" value="<?= $pInfor['musical_seqNo'] ?>"> 
							   &nbsp;<input type="text" id="h_code" name="h_code"  size="10" readOnly align='right' class="form_box" value="<?= $pInfor['h_code'] ?>"> 
							   &nbsp;<input type="text" id="h_name" name="h_name"  size="50" readOnly align='right' class="form_box" value="<?= $pInfor['h_name'] ?>">
							   &nbsp;<input type="button" value="공연찾기" onClick="searchProduct();">
							</td>
							
						</tr>
					
						<tr  height="28">
						  
							<td width="15%" class="titletd text-center" align="center">고객명</td>
							<td bgcolor="#FFFFFF" colspan=3>&nbsp;<input type="text" id="member_name" name="member_name" size="30" class="form_box" value="<?= $pInfor['member_name'] ?>">(예 :  gildong/hong) </td>
								
						</tr>
					   
							
							
						
						<tr height="28">
							<td width="15%" class="titletd text-center" >연락처</td>
							<td bgcolor="#FFFFFF" colspan=3>&nbsp;&nbsp;&nbsp;전화번호 : <input type="text" id="member_phone" name="member_phone" size="10" class="form_box" value="<?= $pInfor['member_phone'] ?>">&nbsp;이메일
							 &nbsp;<input type="text" id="reserve_email" name="reserve_email" size="30" class="form_box" value="<?= $pInfor['reserve_mail'] ?>"> 
						   </td>	
						</tr>

						
						
						<tr bgcolor="#f9f9f9" height="28">
							<td width="15%" class="titletd text-center">공연일</td>
							<td width="35%" bgcolor="#FFFFFF">
							   &nbsp;<input type="text" id="act_date" name="act_date"  size="10" align='right' class="form_box" value="<?= $pInfor['act_date'] ?>"> 
							 
							   
							</td>
							<td width="15%" class="titletd text-center">공연시간</td>
							<td bgcolor="#FFFFFF">
								&nbsp;<input type="text" id="act_time" name="act_time" size="10" class="form_box" value="<?= $pInfor['act_time'] ?>">
							
						  </td>	
						</tr> 
						<tr bgcolor="#f9f9f9" height="28">
							<td width="15%" class="titletd text-center">접수일</td>
							<td width="35%" bgcolor="#FFFFFF" >
							   &nbsp;<input type="text" id="register_date" name="register_date"  size="24" align='right' class="form_box" value="<?php if ($pInfor['wdate'] != "") { echo function_exists('mb_convert_encoding') ? mb_convert_encoding($pInfor['wdate'], 'UTF-8', 'ISO-8859-1') : utf8_encode($pInfor['wdate']); } else { echo $today; } ?>"> 
							  
							  
							</td>

							<td width="15%" class="titletd text-center">티켓수</td>
							<td bgcolor="#FFFFFF">
								&nbsp;<input type="text" id="member_adult" align=right name="member_adult" size="5" class="form_box" value="<?= $pInfor['member_adult'] ?>"  OnBlur='javascript:init_sum();'> 
							</td>	
							
						  </td>	 
						</tr>  
						<tr bgcolor="#f9f9f9" height="28">
							<td width="15%" class="titletd text-center">승인번호</td>
							<td width="35%" bgcolor="#FFFFFF" colspan=3>
							   &nbsp;<input type="text" id="api_num" name="api_num"  size="15" align='right' class="form_box" value="<?=$api_return[0]?>"> 
							 
							   
							</td>
							
						</tr> 
						<tr bgcolor="#f9f9f9" height="28">
							<td width="15%" class="titletd text-center">티켓원가</td>
							<td bgcolor="#FFFFFF">
								&nbsp;<input type="text" id="musical_price" align=right name="musical_price" size="20" class="form_box" value="<?= $pInfor['musical_price'] ?>" OnBlur='javascript:init_sum();'> 
						  </td>	
							<td width="15%" class="titletd text-center">판매가격</td>
							<td bgcolor="#FFFFFF">
								&nbsp;<input type="text" id="musical_sale_price" align=right name="musical_sale_price" size="20" class="form_box" value="<?= $pInfor['musical_sale_price'] ?>" OnBlur='javascript:init_sum();'> 
						  </td>	
						</tr> 
						<tr>
							<td colspan=4  bgcolor=#ffffff height='30px'>
							<?php
									$agent_qry1 = "select * from rand_company where reserveCode = '{$pInfor['reserveCode']}' && money_type = 'credit'";
									
									$agent_rst1 = mysql_query($agent_qry1);
									$agent_num1 = mysql_num_rows($agent_rst1);
									
									$l_num = $agent_num1;
									
									$l_num2 = 0;
									if ($l_num > 0)  {
											while($agent_row1 = mysql_fetch_assoc($agent_rst1)):

															
									
											?>
											<table name="example2" id="example2"  width="100%" align="center" border="0" cellspacing="1" cellpadding="0" >
												   <tr class="item3" bgcolor=#FFFFFF>	
														<td width="15%" align="center" rowspan=<?=$cnt?>><button type=button class="addBtn3">에이젼트</button></td>   
														<td>&nbsp;
															<select name=agent_id[] id=agent_id[] class=form_box2 style='font-size:10pt;width:250px;' >
																			<option value="">의뢰업체를 선택하세요.
																			  <?= printRandSelect($agent_row1['part_id']); ?>
																			</select>
																			 &nbsp;금액 :
																			 <input type=text name=agent_cms[] size=20 class=form_box value="<?= $agent_row1['amt'] ?>" > 
																			 Memo:<input type=text name=agent_memo[] size=70 class=form_box value='<?= $agent_row1['p_memo'] ?>' >
																			 <input type='hidden' name=seqamx[] id=seqamx[] value='<?=$agent_row1['seq']?>' >
																			
														</td>	
														<td align=center><button type="button" class="delBtn3">삭제</button></td>  
													</tr>		
											</table>
								<?php
											$l_num++;
											$l_num2++;
											endwhile;
									} else {
												   
								?>	
										  <table name="example2" id="example2"  width="100%" align="center" border="0" cellspacing="1" cellpadding="0" >
												   <tr class="item3">	
														<td width="15%" class="titletd text-center" ><button type="button" class="addBtn3">에이젼트</button></td>   
														<td>&nbsp;
															<select name=agent_id[] id=agent_id[] class=form_box2 style='font-size:10pt;width:250px;' >
															<option value="">의뢰업체를 선택하세요.
															  <?= printRandSelect($agent_row1['part_id']); ?>
															</select>
															 금액 :
															 <input type=text name=agent_cms[] size=20 class=form_box value="" > 
															 Memo:<input type=text name=agent_memo[] size=70 class=form_box value='' >
															 <input type='hidden' name=seqamx[] id=seqamx[] value='' >
																			
														</td>	
														<td align=center><button type="button" class="delBtn3">삭제</button></td>  
													</tr>		
											</table>
								<?php         
												

								  } ?>
							</td>
					   </tr> 
					   <tr>
						  <td colspan=4 bgcolor=#FFFFFF>
							 <table id="level4" class="txt_12" width="100%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
								<tr><td colspan=5 height=1 bgcolor=#dcdcdc></td></tr>
								<tr>
									<td bgcolor=#F4F4F4 height=28 colspan=5>&nbsp;&nbsp;<b>예약API 현황</b>&nbsp;</td>
								</tr>
							</table>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>OrderID</td>
							<td bgcolor=#FFFFFF >&nbsp;<input type=text name=OrderID size=30 class="form_box" value="<?= $api_return[0] ?>"></td>
							<td align=center>Status</td>
							<td bgcolor=#FFFFFF >&nbsp;<input type=text name=Status size=20 class="form_box" value="<?= $api_return[1] ?>"> <a href="../print_voucher.php?m_code=<?= $pInfor['h_code'] ?>&r_code=<?= $pInfor['reserveCode'] ?>" target=_blank>[바우처 출력]</a></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>Product</td>
							<td bgcolor=#FFFFFF >&nbsp;<input type=text name=Product size=30 class="form_box" value="<?= $api_return[2] ?>"></td>
							<td align=center>Description</td>
							<td bgcolor=#FFFFFF >&nbsp;<input type=text name=Description size=30 class="form_box" value="<?= $api_return[3] ?>"></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>C_price</td>
							<td bgcolor=#FFFFFF >&nbsp;<input type=text name=C_price size=30 class="form_box" value="<?= $api_return[4] ?>"></td>
							<td align=center>C_quantity</td>
							<td bgcolor=#FFFFFF >&nbsp;<input type=text name=C_quantity size=30 class="form_box" value="<?= $api_return[5] ?>"></td>
					   </tr>
					   <tr bgcolor=#f9f9f9 height=28>
							<td align=center>Order</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=button value="공연실시간주문" onClick="go_api_musical()"></td>
					    </tr>
					   <tr  height="28">
							<td  align="center" class="titletd text-center" colspan=4 ><b>결제 정보</b></td>
							
						</tr> 
						<tr bgcolor=#f9f9f9 height=28>
							<td width=15% class="titletd text-center">결제방법</td>
							<td width=35% bgcolor=#FFFFFF>&nbsp;
								
								<?php if($pInfor['pay_method'] == "DIRECT"): ?> <input type=radio name=pay_method value="DIRECT" checked> 직접결제 
								<input type=radio name=pay_method value="CREDIT" > 인터넷 <input type=radio name=pay_method value="PENDING" > 견적후결제  
								<?php elseif($pInfor['pay_method'] == "CREDIT"): ?> <input type=radio name=pay_method value="DIRECT" > 직접결제 
								<input type=radio name=pay_method value="CREDIT" checked> 인터넷 <input type=radio name=pay_method value="PENDING" > 견적후결제 
								<?php elseif($pInfor['pay_method'] == "PENDING"): ?> <input type=radio name=pay_method value="DIRECT" > 직접결제 
								<input type=radio name=pay_method value="CREDIT" > 인터넷 <input type=radio name=pay_method value="PENDING" checked> 견적후결제 
								<?php else: ?> <input type=radio name=pay_method value="DIRECT" > 직접결제 
								<input type=radio name=pay_method value="CREDIT" > 인터넷 <input type=radio name=pay_method value="PENDING" checked> 견적후결제 
								<?php endif; ?>
								</td>
							<td width=15% align=center></td>
							<td width=35% bgcolor=#FFFFFF></td>
						</tr>
						<tr bgcolor="#f9f9f9" height="28">
							<td width="15%" align="center" bgcolor="#99CC00">발란스 </td>
							<td width="35%" bgcolor="#FFFFFF" >&nbsp;<font color=red>$ <?=$pInfor['balance']?></font>&nbsp;&nbsp;[payment history] </td>
							<td width="15%" align="center" bgcolor="#99CC00">결제하기</td>
							<td width="35%" bgcolor="#FFFFFF" >&nbsp;
							    &nbsp;<button type="button" class="btn btn-xs btn-block btn-default js-makePayment" data-toggle="modal" data-target=".js-openPaymentProcess" <?php if (!$pInfor['reserveCode']) {?> disabled  <?php } ?>>결제하기</button>

								
							</td>
							
						</tr> 
						
						<tr bgcolor="#f9f9f9" height="28">
							<td width="15%" align="center" bgcolor="FFA500">총판매가격</td>
							<td width="35%" bgcolor="#FFFFFF" colspan=3>&nbsp;$ &nbsp;<input type="text" id="total_amount" name="total_amount"  size="20" align='right' class="form_box" OnBlur='javascript:init_sum();'   value="<?= function_exists('mb_convert_encoding') ? mb_convert_encoding($pInfor['total_amt'], 'UTF-8', 'ISO-8859-1') : utf8_encode($pInfor['total_amt']) ?>">
								&nbsp;<input type="button" value="합계계산" onClick='javascript:init_sum();'></td>
							
							
						</tr>
					  
					   
						
						
						<tr   height="28">
							<td width="15%" class="titletd text-center" >진행사항</td>
							<td width="35%" bgcolor="#FFFFFF" colspan=3>&nbsp;<textarea name='consult_content' cols='120' rows='10'><?=$pInfor['consult_content']?></textarea></td>
							
							
						</tr> 
				
				
				
				
				
			</table>	
			<table id="tab1" class="txt_12" width="98%" align="center" border="0" cellspacing="1" bgcolor="#000000" cellpadding="0" >
		  	<tr>
					<td colspan="4" height="50" align="center" bgcolor="#FFFFFF"><input type="button" value="&nbsp;예약 저장&nbsp;" onClick="go_submit('<?=$pInfor['reserveCode']?>')"></td>
				</tr>
				
			</table>
	    </form>
				 <!--////////////////////////////////////////////-->
				</div>
			</div>
		</div>
	
	<?php
		include "include/side_m.php"
	?>
	<!--/////////////////////////////////////////////////////////////////////////////-->
	<div class="modal fade js-openPaymentProcess" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
				<div class="modal-dialog modal-lg modal-full-width" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
							<h4 class="modal-title" id="gridSystemModalLabel">온라인결제</h4>
						</div>
						<div class="modal-body">
							<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code=<?=$pInfor['reserveCode']?>" name="frmpayment" id="frmpayment" method="post">
								<input type="hidden" name="mode" value="paymentProcess">
								<input type="hidden" name="hcode" value="<?= $hcode ?>">
								<input type="hidden" name="lasttotal" value="<?=$pInfor['total_amount']?>">
						         <input type="hidden" name="lastbalance" id="lastbalance" value="<?=$pInfor['balance']?>">
								
								<input type="hidden" name="estimateCode" id="estimateCode" value="<?= $pInfor['reserveCode'] ?>">
								<input type="hidden" name="hname" value='<?= $pInfor['h_name'] ?>'>
								
								<div class="row">
									<div class="col-sm-12">
										<table class="table table-bordered table-condensed gridSixteen paymentProcessingTable formDetail">
											<tbody>
												<tr>
													<td colspan="2" class="active text-center formHeader">예약번호</td>
													<td colspan="6"><?=$pInfor['reserveCode']?></td>
													<td colspan="2" class="active text-center formHeader">예약자</td>
													<td colspan="2"><?=$pInfor['member_name']?></td>
													<td colspan="2" class="active text-center formHeader">인원</td>
													<td colspan="2" class="text-center"><?=$pInfor['member_adult']?></td>
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">공연명</td>
													<td colspan="6">[<?=$pInfor['h_code']?>] <?=$pInfor['h_name']?></td>
													<td colspan="2" class="active text-center formHeader">관람일</td>
													<td colspan="6"><?=$pInfor['act_date']?>-<?=$pInfor['act_time']?></td>
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">총금액</td>
													<td colspan="6"><span id="lastpay">$ <?=$pInfor['total_amount']?></span></td>
													<td colspan="2" class="active text-center formHeader">잔금</td>
													<td colspan="6"><span id="balpay">$<?=$pInfor['balance']?></span></td>
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
															$qry1 = "select * from payment_history where reserveCode = '{$pInfor['reserveCode']}' && payment_status !='RRQUEST' 
															order by wdate asc";
													        //echo $qry1;
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
																		case "gift" : 
																		    $cappay = "상품권및기타";
																		    break; 
																		 
																		default : 
																		    $cappay = "초기입력";
																		    break; 
																			
																	}
																	if ($p_row['b_rate'] == "CAD") {
																		$signb = "C$";
																	} else {
																		$signb = "U$";
																	}

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
																			<td colspan="2" class="text-center"><?=$sign?> <?=$p_row['payment']?></td>
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
																		$mius = $sign.$p_row['payment'];
																		$mius1 = $sign.$p_row['rate_payment'];
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
										<table class="table table-bordered table-condensed gridSixteen paymentProcessingTable formDetail hidden js-paymentProcess">
											<tbody>
												
												<tr>
													<td colspan="2" class="active text-center formHeader">결제방법</td>
													<td colspan="4" class="no-right-border">
														<select class="form-control js-paymentType" name="paymentmethod" id="paymentmethod">
															<option value="">- 결제방법 선택하세요 -</option>
															<option value="cash">현금</option>
															<option value="debit">데빗</option>
															<option value="creditcard">신용카드</option>
															<option value="bcreditcard">지사단말기</option>
															<option value="check">체크</option>
															<option value="banktransfer">은행송금</option>
															<option value="airsys">항공시스템</option>
															<option value="fundtransfer">금액이동</option>
															<option value="gift">상품권및기타</option>

														</select>							
													</td>
													<td colspan="10" class="no-left-border"></td>
												</tr>
											</tbody>
										</table>
										<table class="table table-bordered table-condensed gridSixteen paymentProcessingTable formDetail hidden js-paymentCrediCard">
											<tbody>
												<tr>
													<td colspan="2" class="active text-center formHeader">카드소유주</td>
													<td colspan="2" class="no-right-border">
														<input type="text" name="fcardname" class="form-control" placeholder="카드소유주-퍼스트네임">
													</td>
													<td colspan="2" class="no-right-border">
														<input type="text" name="lcardname" class="form-control" placeholder="카드소유주-라스트네임">
													</td>
													<td colspan="10" class="no-left-border"></td>
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">카드번호</td>
													<td colspan="4" class="no-right-border">
														<input type="text" name="cardnum" class="form-control" placeholder="카드번호">
													</td>
													<td colspan="10" class="no-left-border"></td>
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">유효기간</td>
													<td colspan="1" class="no-right-border">
														<select class="form-control" name="ccexpmo">
															<option value="01">01</option>
															<option value="02">02</option>
															<option value="03">03</option>
															<option value="04">04</option>
															<option value="05">05</option>
															<option value="06">06</option>
															<option value="07">07</option>
															<option value="08">08</option>
															<option value="09">09</option>
															<option value="10">10</option>
															<option value="11">11</option>
															<option value="12">12</option>
														</select>							
													</td>
													<td colspan="1" class="no-side-border">
														<select class="form-control" name="ccexpyr">
															<option value="2019">2019</option>
															<option value="2020">2020</option>
															<option value="2021">2021</option>
															<option value="2022">2022</option>
															<option value="2023">2023</option>
															<option value="2024">2024</option>
															<option value="2025">2025</option>
															<option value="2026">2026</option>
															<option value="2027">2027</option>
															<option value="2028">2028</option>
															<option value="2029">2029</option>
															<option value="2030">2030</option>
														</select>							
													</td>
													<td colspan="12" class="no-left-border"></td>
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">Security Code (3자리)</td>
													<td colspan="2" class="no-right-border">
														<input type="text" name="cvvnum" class="form-control" placeholder="Security Code (3자리)">
													</td>
													<td colspan="12" class="no-left-border"></td>
												</tr>
												
												<tr>
													
													<td colspan="2" class="active text-center formHeader">결제금액</td>
													<td colspan="4" class="no-right-border">
														<input type="text" class="form-control" name='clastpayamt' value="" placeholder="결제받을금액">
													</td>
													<td colspan="8" class="no-left-border"></td>
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">결제메모</td>
													<td colspan="14">
														<input type="text" name='ccmemo' class="form-control" placeholder="결제메모">
													</td>
												</tr>
											</tbody>
										</table>
										<table class="table table-bordered table-condensed gridSixteen paymentProcessingTable formDetail hidden js-paymentOther">
											<tbody>
												
												<tr>
													<td colspan="2" class="active text-center formHeader">결제금액 <span id="psign"><font color="red"></font></span></td>
													
													<td colspan="6">
														<input type="text" name="lastpayamt" id="lastpayamt" class="form-control" placeholder="결제금액" value="" >
													</td>
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">결제메모</td>
													<td colspan="6">
														<input type="text" class="form-control" name="dmemo" placeholder="결제메모">
													</td>
													<td colspan="2" class="active text-center formHeader">결제자</td>
													<td colspan="6">
														<input type="text" class="form-control" name="puser" placeholder="결제자" value='<?=$user_dbinfo['userid']?>'>
													</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>
							</form>
						</div>
						<div class="modal-footer text-center">
						     
							          <button type="button" class="btn btn-primary 
									  js-processPayment hidden" onClick="go_pay()">결제하기</button>
							
							<button type="button" class="btn btn-default" data-dismiss="modal">뒤로가기</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
</div>
	<!--////////////////////////////////////////////////////////////////////////////-->
    </body>
<script>
    
	  $(document).ready(function () {
				/////////////////////////////////////////////////////////////////
				$.ajaxSetup({async: false});
                pt.initReservationDetail()
				{
					
					var scope = $('.reservationDetailForm');
					
					for (var i = 0; i < scope.length; i++) {
						var self = $(scope['i']);
						var process = self.find('.js-process');
						
						process.off('click').on('click', function (e) {
							//$(document).on('click', '.js-process', function(){ 	
							alert("!");
							var paymentProcessPanel = self.find('.js-paymentProcess');
							var paymentTypeSelection = self.find('.js-paymentType');
							var paymentbtn = self.find('.js-processPayment');
								
							paymentProcessPanel.removeClass('hidden');
							$('input[name^="lastamt"]').val($(this).val())
								
							$('#psign').html("<b><font color='red'>"+"$"+"</font></b>")
							
							$('input[name^="rpay"]').val($("#lastbalance").val());
							$('input[name^="opay"]').val($("#lastbalance").val());
							$('input[name^="lastpayamt"]').val($("#lastbalance").val());
							$('input[name^="lastamt"]').val($("#lastbalance").val());
							var amtf = parseFloat($("#lastbalance").val());
								//alert(amtf);
							$('input[name^="clastpayamt"]').val(amtf);
							paymentTypeSelection.off('change').on('change', function (e) {
								var paymentType = $(this).val()
								var creditcardForm = self.find('.js-paymentCrediCard')
								var otherTypeForm = self.find('.js-paymentOther')
								if (paymentType) {
									if (paymentType === 'creditcard') {
										creditcardForm.removeClass('hidden')
										otherTypeForm.addClass('hidden')
									} else {
										creditcardForm.addClass('hidden')
										otherTypeForm.removeClass('hidden')
									}
									paymentbtn.removeClass('hidden')
								} else {
									creditcardForm.addClass('hidden')
									otherTypeForm.addClass('hidden')
								}
							})
						})

						


					}
				}



				///////////////////////////////////////////////////////////////////
				$("#register_date").datepicker({ dateFormat: "yy-mm-dd" }).val();
				
				$('input.dtaeclass').datepicker({ dateFormat: "yy-mm-dd" }).val();
				

				$(".addBtn2").click(function(){                
					var clickedRow = $(this).parent().parent();                
					var cls = clickedRow.attr("class");                 
					// tr 복사해서 마지막에 추가                
					var newrow = clickedRow.clone();                
					newrow.find("td:first").remove();
					$("td input:hidden", newrow).val("");                
					newrow.insertAfter($("#example2 ."+cls+":last"));    
				});            
								  
			     // 삭제버튼 클릭시            
				$(document).on('click', '.delBtn3', function(){
				                   
					var clickedRow = $(this).parent().parent();                
					var cls = clickedRow.attr("class"); // 각 항목의 첫번째 row를 삭제한 경우 다음 row에 td 하나를 추가해 준다.                
					if( clickedRow.find("td:eq(0)").attr("rowspan") ){                    
						if( clickedRow.next().hasClass(cls) ){                        
						 clickedRow.next().prepend(clickedRow.find("td:eq(0)"));                    
						}                
					 }
					
					 var delrownum = $("td input:hidden", clickedRow).val(); 
					 var sel = $("td select", clickedRow).val(); 
					 var types = 'credit';
					 var reserve = '<?=$estimateCode?>';
					 if (delrownum != "") {
						  	var ParaListS = "?sel="+sel+"&reserve="+reserve+"&types="+types+"&num="+delrownum;
						    $.post("del_rand.php"+ParaListS, function(data) {  
				 	 	 	 	  alert(ParaListS);
				 	        });
					 }     
					 clickedRow.remove();                 
					 resizeRowspan(cls);   
					
								
				});             
		        $(".addBtn3").click(function(){   
					
					var clickedRow = $(this).parent().parent();       ;                
					var cls = clickedRow.attr("class");  
					
					// tr 복사해서 마지막에 추가                
					var newrow = clickedRow.clone();                
					newrow.find("td:eq(0)").remove();
					$("td input:hidden", newrow).val("");                
					newrow.insertAfter($("#example2 ."+cls+":last"));                 // rowspan 증가     
				   
				
					resizeRowspan(cls); 
				});            
								  
			     // 삭제버튼 클릭시            
				$(".delBtn4").click(function(){                
					var clickedRow = $(this).parent().parent();                
					var cls = clickedRow.attr("class");                                 // 각 항목의 첫번째 row를 삭제한 경우 다음 row에 td 하나를 추가해 준다.                
					if( clickedRow.find("td:eq(0)").attr("rowspan") ){                    
						if( clickedRow.next().hasClass(cls) ){                        
						 clickedRow.next().prepend(clickedRow.find("td:eq(0)"));                    
						}                
					 }
					
					 var delrownum = $("td input:hidden", clickedRow).val(); 
					 var sel = $("td select", clickedRow).val(); 
					 var types = 'debit';
					 var reserve = '<?=$estimateCode?>';
					 if (delrownum != "") {
						  	var ParaListS = "?sel="+sel+"&reserve="+reserve+"&types="+types+"&num="+delrownum;
						    $.post("del_rand.php"+ParaListS, function(data) {  
				 	 	 	 	  alert(ParaListS);
				 	        });
					 }     
					
					 clickedRow.remove();                 
					 resizeRowspan(cls);   
					
								
				}); 
				
							    		
		});
		 var tmpInt = 10;  
		function addbtn(obj) {
					
					var clickedRow = $(obj).parent().parent();                
					var cls = clickedRow.attr("class");                 
					// tr 복사해서 마지막에 추가                
					var newrow = clickedRow.clone();                
					newrow.find("td:eq(0)").remove();
					$("td input:hidden", newrow).val("");                
					newrow.insertAfter($("#example ."+cls+":last"));                 // rowspan 증가     
				   
					newrow.find("#l_local_date0").attr("id", "l_local_date"+tmpInt++);
					newrow.find("input.dtaeclass").removeClass('hasDatepicker').datepicker({ dateFormat: 'yy-mm-dd' });
					resizeRowspan(cls); 
							
			 
		}

		function Delbtn(obj) {

			  
					var clickedRow = $(obj).parent().parent();                
					var cls = clickedRow.attr("class");                                 // 각 항목의 첫번째 row를 삭제한 경우 다음 row에 td 하나를 추가해 준다.                
					if( clickedRow.find("td:eq(0)").attr("rowspan") ){                    
						if( clickedRow.next().hasClass(cls) ){                        
						 clickedRow.next().prepend(clickedRow.find("td:eq(0)"));                    
						}                
					 }
					
					 var delrownum = $("td input:hidden", clickedRow).val(); 
					 var sel = $("td select", clickedRow).val(); 
					
					 clickedRow.remove();                 
					 resizeRowspan(cls);   
		}
		function resizeRowspan(cls){                
					 var rowspan = $("."+cls).length;                
						 
					 $("."+cls+":first td:eq(0)").attr("rowspan", rowspan); 
					
		}  

	
		function searchProduct(){

				
		   customer_keyword = $("#p_code").val(); 
			  
        
           window.open("search_musical.php?page=self&customer_keyword="+customer_keyword,"customer","scrollbars=yes,status=yes,width=800,height=500,left=150,top=150"); 	
        
			 
				
		} 
		
		function go_submit(reserve){

			tf = document.product;
			tf.musical_api.value = '';
			if(confirm("저장 하시겠습니까?") == true)
			{
				if (reserve=="")
				{
					tf.mode.value="save";
				} else {

					tf.mode.value="modify";
				}
				tf.order_status.value = 'READY';
				tf.submit();
			}
			else return;
		}
		
		function go_pay() {
              
				  if ($("#paymentmethod option:selected").val() == 'creditcard') {
					    
						if ($('input[name^="cardname"]').val()=="")
						{
							alert("카드소유주 이름을 입력하세요!");
							$('input[name^="cardname"]').focus();
							return;
						}
						if ($('input[name^="cardnum"]').val()=="")
						{
							alert("카드번호를 입력하세요!");
							$('input[name^="cardnum"]').focus();
							return;
						}
						if ($('input[name^="cvvnum"]').val()=="")
						{
							alert("Security Code를 입력하세요!");
							$('input[name^="cvvnum"]').focus();
							return;
						}
						if (($('input[name^="lastamt"]').val()=="0.00") || ($('input[name^="lastamt"]').val()=="0") || ($('input[name^="lastamt"]').val()==""))
						{
							alert("결제금액이 없습니다!");
							$('input[name^="clastpayamt"]').focus();
							return;
						}
						
				  }
				  
				  if(confirm("결제를 진행하시겠습니까?") == true)
				  {
					   
					   $("#frmpayment").submit();
				  }
				  else return;
	
		}
		function view_history(flag,eCode){
      
			  
			  window.open("payment_history.php?flag=" + flag + "&r_code=" + eCode,"payment_history","width=800,height=500,left=200,top=250,scrollbars=1");

      
		} 

		// 자동 최종 합계 스크립트
		function init_sum(){
			
			tf = document.product;
			// 성인 요금 합계
			if(!tf.member_adult.value)
			{
				alert('티켓수량을 입력하세요');
				tf.member_adult.focus();
				return false;
			}


			tf.total_amount.value = tf.member_adult.value * parseFloat(tf.musical_sale_price.value);

		}

		function go_api_musical(){
						
			tf = document.product;
			tf.mode.value="api";
			if(tf.OrderID.value != '')
			{
				alert('이미 티켓이 오더된 예약입니다.!');
				return;
			}
			if(tf.musical_seqNo.value == '')
			{
				alert('뮤지컬 상세정보가 부족합니다.!');
				return;
			}
			if(tf.member_adult.value == '')
			{
				alert('티켓수량이 필요합니다.!');
				return;
			}
			if(tf.musical_price.value == '')
			{
				alert('뮤지컬 티켓가격이 필요합니다.!');
				return;
			}
			
			if(confirm("정말로 티켓을 오더 하시겠습니까?") == true)
			{
				tf.musical_api.value = 'YES';
				document.product.submit();
			}

			
		}

		
</script>
</html>
			  

>