
<?php
    if ($mode == "save") {
		//처음접수
		if ($estimateCode == "") {
		       // 토탈예약용 예약코드
				 if ($grestimateCode=="") {
					$total_estimateNum = getNumReserve_total();
					$total_estimateCode = "TPU".date("ymd").$total_estimateNum;	
				} else {
					$total_estimateNum = getNumReserve_ctotal();
					$total_estimateCode = $grestimateCode;	
				}
				$estimateNum = getNumHReserve();
				$estimateCode = "HPU".date("ymd").$estimateNum;
				$qry0 ="insert into grand_reserve 
													( 
													grandNum,
													grand_revNo, 
													revNo, 
													tour_type, 
													p_code, 
													p_name, 
													revDate, 
													stDate, 
													wdate
													)
													values
													( 
													'$total_estimateNum',
									                '$total_estimateCode', 
													'$estimateCode', 
													'4', 
													'$hotelName', 
													'', 
													now(), 
													'$startDate', 
													now()
													)";
				$rst0 = mysql_query($qry0,$dbConn);

				
				for($i=0; $i<count($meal); $i++)
				{
					$mealValue .= $meal[$i]."/";
				}
				$lasttot= ($room_number * $room_amount) + $extra_amount;
				$qry1 ="insert into reserve_hotel 
										( 
										grand_revNo, 
										reserveNum, 
										reserveCode, 
										reserve_date, 
										code1, 
										code2, 
										h_code, 
										r_kname, 
										r_ename, 
										r_phone, 
										r_email, 
										s_day, 
										start_date, 
										end_date, 
										p_cnt, 
										r_cnt, 
										u_amt, 
										meal, 
										a_amt, 
										etc_memo, 
										b_rate, 
										payment_st, 
										rev_status, 
										last_total, 
										last_bal, 
										amt_memo, 
										userid,
										wdate
										)
										values
										( 
										'$total_estimateCode', 
										'$estimateNum', 
										'$estimateCode', 
										now(), 
										'$area1', 
										'$area2', 
										'$hotelName', 
										'$book_name', 
										'$book_name_eng', 
										'$telephone', 
										'$hemail', 
										'$cal_day_h', 
										'$startDate', 
										'$endDate', 
										'$people_number', 
										'$room_number', 
										'$room_amount', 
										'$mealValue', 
										'$extra_amount', 
										'$p4desc', 
										'$brate', 
										'READY', 
										'READY', 
										'$lasttot', 
										'$lasttot', 
										'',
										'{$user_dbinfo['userid']}', 
										now()
										)";

			   
		       $rst1 = mysql_query($qry1,$dbConn);
			  
			   //호텔협력사
               if ($hotelCooperationRegion) {
				    $qry4="insert into rand_company 
										( 
										reserveCode, 
										part_area, 
										part_id, 
										money_type, 
										base_rate, 
										amt, 
										tr_date,
										p_memo,
										status,
										u_id, 
										wdate
										)
										values
										(
										'$estimateCode', 
										'$hotelCooperationRegion', 
										'$hotelCooperationName', 
										'debit', 
										'$brate', 
										'$net_amount', 
										'',
										'READY',
										'$amount_memo',
										'{$user_dbinfo['userid']}', 
										now()
										);";
					$rst4 = mysql_query($qry4,$dbConn);
			   }  
			   

			   //payment history

			   $qry5 = "insert into payment_history 
										( 
										reserveCode, 
										pay_method, 
										pay_info, 
										payment, 
										b_rate, 
										rate_payment, 
										rate_m, 
										payment_status, 
										pay_memo, 
										register, 
										wdate
										)
										values
										( 
										'$estimateCode', 
										'init', 
										'결제대상', 
										'$lasttot', 
										'$brate', 
										'$lasttot', 
										'', 
										'READY', 
										'', 
										'{$user_dbinfo['userid']}', 
										now()
										);";

			  $rst5 = mysql_query($qry5,$dbConn);


			   Misc::jvAlert("저장 완료!!!");
			   if ($pricet == 1) {
				   $sub = "15";
			   } else if ($pricet == 3) {
				   $sub = "25";
			   }
			   echo "<meta http-equiv='refresh' content='0; url=./hotel_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=$pdx&sub=$sub'>";
				
		} else if ($estimateCode != "") {
			  
			    //메인 저장
				//발란스계산
				//echo $order_status;
				//exit;
				$lasttot= ($room_number * $room_amount) + $extra_amount;
				$qry6= "update payment_history 
									set
									payment = '$lasttot' , 
									rate_payment= '$lasttot'
									where
									reserveCode = '$estimateCode' && payment_status='READY' && pay_method = 'init'";

							
			    $rst6 = mysql_query($qry6,$dbConn);

				$qryp = "select * from payment_history where reserveCode = '$estimateCode' && (payment_status='DONE' || payment_status='RETURN')";
				$rstp = mysql_query($qryp,$dbConn);
				while($rowp = mysql_fetch_assoc($rstp)){

            	      if ( $rowp['payment_status'] == "RETURN") {

							$rtnamt = $rtnamt + $rowp['payment'];
					  } else {
					 		$ttotamt1 = $ttotamt1 + $rowp['payment'];
					  }
					  
					  $totpay = $ttotamt1 - $rtnamt;
					  

	            }
				$totbal2 = $lasttot - $totpay;
				for($i=0; $i<count($meal); $i++)
				{
					$mealValue .= $meal[$i]."/";
				}
				if ($paystatus == "CGPAY") {

					if ($totbal2 > 0) {
						$paystatus = "PPAY";
					}
					if ($totbal2 == 0) {
						$paystatus = "DONE";
					}
					if ($totbal2 < 0) {
						$paystatus = "OPAY";
					}
					if ($totbal2 == $lasttot) {
						$paystatus = "READY";
					}
				}
				//echo $qryp."<br >".$totpay ;
				//exit;
				$qry1 ="update reserve_hotel 
												set
												code1 = '$area1' , 
												code2 = '$area2' , 
												h_code = '$hotelName' , 
												r_kname = '$book_name' , 
												r_ename = '$book_name_eng' , 
												r_phone = '$telephone' , 
												r_email = '$hemail' , 
												s_day = '$cal_day_h' , 
												start_date = '$startDate' , 
												end_date = '$endDate' , 
												p_cnt = '$people_number', 
												r_cnt = '$room_number', 
												u_amt ='$room_amount', 
												meal = '$mealValue', 
												a_amt = '$extra_amount', 
												etc_memo = '$p4desc',  
												b_rate = '$brate',
												payment_st = '$paystatus' , 
												rev_status = '$order_status' , 
												last_total = '$lasttot' , 
												last_bal = '$totbal2' , 
												amt_memo = '' , 
												userid = '{$user_dbinfo['userid']}' , 
												wdate = now()
												
												where
												reserveCode = '$estimateCode' ";

									
					
				$rst1 = mysql_query($qry1,$dbConn);
				echo $qry1;
				exit;
				
			   //호텔협력사
               if ($hotelCooperationRegion) {
				    $qryr = "select rand_id from rand_pay where reserveCode = '$estimateCode' && rand_id ='$hotelCooperationName'";
				    $rstr = mysql_query($qryr);
				    $rowrcnt = mysql_num_rows($rstr);
					
					if ($rowrcnt > 0) {
						 Misc::jvAlert("이 협력사에 페이먼트 자료가있습니다. <br />회계담당자에게 먼저 문의하신후 수정하세요!!!","history.back(-1)");
						 exit;
					} else {
						$qryc = "select part_id from rand_company where reserveCode = '$estimateCode' && part_id ='$hotelCooperationRegion' && money_type='debit'";
						$rstc = mysql_query($qryc);
						$rowdcnt = mysql_num_rows($rstc);
						if ($rowdcnt == 0) {
								$qry4="insert into rand_company 
														( 
														reserveCode, 
														part_area, 
														part_id, 
														money_type, 
														base_rate, 
														amt, 
														p_memo,
														status,
														u_id, 
														wdate
														)
														values
														(
														'$estimateCode', 
														'$hotelCooperationRegion', 
														'$hotelCooperationName', 
														'debit', 
														'$brate', 
														'$pamt', 
														'$pamtmemo',
														'READY',
														'{$user_dbinfo['userid']}', 
														now()
														);";
									$rst4 = mysql_query($qry4,$dbConn);
						} else {
							$qry4="update rand_company 
															set
															part_area = '$hotelCooperationRegion' , 
															part_id = '$hotelCooperationName' , 
															amt = '$pamt' , 
															p_memo = '$pamtmemo' ,
															status = '$order_status',
															wdate = now()
															where
															reserveCode = '$estimateCode'  && money_type = 'debit' ";
					        $rst4 = mysql_query($qry4,$dbConn);
						}
					}
			   }
			   
			   Misc::jvAlert("저장 완료!!!");
			   
			   echo "<meta http-equiv='refresh' content='0; url=./hotel_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=$pdx&sub=$sub'>";

		}
    } else if ($mode == "paymentProcess") {

			    //payment history
				if ($paymentmethod != "creditcard") {
						if ($currencytype == "CAD") {
								$ratepay = "SELL";
								$ratevalue = $sellrate;
						} else if ($currencytype == "USD") {
								$ratepay = "BUY";
								$ratevalue = $buyrate;

						}
						$payst1 ="DONE";
				} else { //신용카드


				}
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
											'$rpay', 
											'$currencytype', 
											'$lastpayamt', 
											'$ratepay', 
											'$ratevalue', 
											'$payst1', 
											'$dmemo', 
											'$puser', 
											now()
											);";

			  $rst5 = mysql_query($qry5,$dbConn);
			  $tlastpay=$lastbalance - $rpay;
			  if ($tlastpay == 0) {
				  $paycap = "DONE";
			  } else if ($tlastpay > 0) {
				  $paycap = "PPAY";
			  } else if ($tlastpay < 0) {
				  $paycap = "OPAY";
			  }
			  $qry6= "update reserve_hotel
									set
									last_bal = '$tlastpay' , 
									payment_st = '$paycap'  
									where
									reserveCode = '$estimateCode'";

							
			  $rst6 = mysql_query($qry6,$dbConn);


			  Misc::jvAlert("결제 완료!!!");
			  if ($pricet == 1) {
				   $sub = "15";
			  } else if ($pricet == 3) {
				   $sub = "25";
			  }
			  echo "<meta http-equiv='refresh' content='0; url=./hotel_reservation_m.php?estimateCode=$estimateCode&division=$division&pdx=$pdx&sub=$sub'>";

	} else if ($mode == "paymentReturn") {
		      if ($paymentmethod != "creditcard") {
					if ($currencytype2 == "CAD") {
							$ratepay = "SELL";
							$ratevalue = $sellrate2;
					} else if ($currencytype2 == "USD") {
							$ratepay = "BUY";
							$ratevalue = $buyrate2;

					}
					$payst1 ="RRQUEST";
			  }

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
											'$paymentmethod2', 
											'', 
											'$rpay2', 
											'$currencytype2', 
											'$lastpayamt2', 
											'$ratepay', 
											'$ratevalue', 
											'$payst1', 
											'$dmemo2', 
											'$puser2', 
											now()
											);";

			  $rst5 = mysql_query($qry5,$dbConn);
			  

			  Misc::jvAlert("환불신청 완료!!!");
			  if ($pricet == 1) {
				   $sub = "15";
			  } else if ($pricet == 3) {
				   $sub = "25";
			  }
			  echo "<meta http-equiv='refresh' content='0; url=./hotel_reservation_m.php?estimateCode=$estimateCode&division=$division&pdx=$pdx&sub=$sub'>";


	}

?>