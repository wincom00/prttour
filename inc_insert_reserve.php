
<?php
    if ($mode == "save") {
		//처음접수
		if ($estimateCode == "") {
		       // 토탈예약용 예약코드
				 if ($grestimateCode=="") {
					$total_estimateNum = getNumReserve_total();
					$total_estimateCode = "TU".substr(time(),4);	
				} else {
					$total_estimateNum = getNumReserve_ctotal();
					$total_estimateCode = $grestimateCode;	
				}
				$estimateNum = getNumReserve();
				$estimateCode = "PUR".substr(time(),4);
				if ($pricet == "3") {
					$ttype = "3";
				}
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
													'$ttype', 
													'$pcode', 
													'$pname', 
													now(), 
													'$startDate', 
													now()
													)";
				$rst0 = mysql_query($qry0,$dbConn);

				
				//메인 저장
				if ($pricet == 1) {
					$ttype = 1;
				} else if ($pricet == 3) {
					$ttype = 3;
				}
				$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,

									air_arcity2, 
									air_arriveDate2, 
									air_arrivetime2, 
									air_arriveNm2, 
									air_arriveMemo2, 
									air_stdate2, 
									air_sttime2, 
									air_stNm2, 
									air_stMemo2, 
									air_stcity2,
									air_astcity2,

									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'MAIN', 
									'$sarea',
									'$ttype', 
									'$pcode', 
									'".addslashes($pname)."', 
									'', 
									now(), 
									'$startDate', 
									'$endDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'$pickloc', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',

									'$arrivecity2', 
									'$arrivalDate2', 
									'$arrivalTime2', 
									'$airname2', 
									'$arrivememo2', 
									'$departureDate2', 
									'$departureTime2', 
									'$departureairname2', 
									'$departurememo2', 
									'$stcity2',
									'$astcity2',

									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'{$user_dbinfo['userid']}', 
									'$paymemo', 
									now()
									)";
			   
		       $rst1 = mysql_query($qry1,$dbConn);
			   if ($tourpick != "") {
				    $propic = getProductMaster($tourpick);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,

								
									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$sarea',
									'$ttype', 
									'$tourpick', 
									'".addslashes($propic['p_name'])."', 
									'', 
									now(), 
									'$startDate', 
									'$endDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'pick', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',

									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'{$user_dbinfo['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);




			   }
			   if ($toursend != "") {
				    $prosend = getProductMaster($toursend);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,

									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$sarea',
									'$ttype', 
									'$toursend', 
									'".addslashes($prosend['p_name'])."', 
									'', 
									now(), 
									'$startDate', 
									'$endDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'send', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',

									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'{$user_dbinfo['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);




			   }
			    if ($tourpick2 != "") {
				    $propic = getProductMaster($tourpick);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									
									air_arcity2, 
									air_arriveDate2, 
									air_arrivetime2, 
									air_arriveNm2, 
									air_arriveMemo2, 
									air_stdate2, 
									air_sttime2, 
									air_stNm2, 
									air_stMemo2, 
									air_stcity2,
									air_astcity2,

									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$sarea',
									'$ttype', 
									'$tourpick', 
									'".addslashes($propic['p_name'])."', 
									'', 
									now(), 
									'$startDate', 
									'$endDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'pick2', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									
									'$arrivecity2', 
									'$arrivalDate2', 
									'$arrivalTime2', 
									'$airname2', 
									'$arrivememo2', 
									'$departureDate2', 
									'$departureTime2', 
									'$departureairname2', 
									'$departurememo2', 
									'$stcity2',
									'$astcity2',

									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'{$user_dbinfo['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);




			   }
			   if ($toursend2 != "") {
				    $prosend = getProductMaster($toursend2);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									
									air_arcity2, 
									air_arriveDate2, 
									air_arrivetime2, 
									air_arriveNm2, 
									air_arriveMemo2, 
									air_stdate2, 
									air_sttime2, 
									air_stNm2, 
									air_stMemo2, 
									air_stcity2,
									air_astcity2,

									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$sarea',
									'$ttype', 
									'$toursend', 
									'".addslashes($prosend['p_name'])."', 
									'', 
									now(), 
									'$startDate', 
									'$endDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'send2', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									

									'$arrivecity2', 
									'$arrivalDate2', 
									'$arrivalTime2', 
									'$airname2', 
									'$arrivememo2', 
									'$departureDate2', 
									'$departureTime2', 
									'$departureairname2', 
									'$departurememo2', 
									'$stcity2',
									'$astcity2',

									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'{$user_dbinfo['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);




			   }
			   //항공정보
			   for($k=0; $k<count($pnrnum); $k++)
			   {
					//echo count($pnrnum);
					//echo $a_pnr_number[$k];
					if($pnrnum[$k])
					{
					 
						// 입력
						$a_qry1 = "insert into reserve_airline_pnr 
												(reserveCode, 
												rand_id, 
												a_pnr_number, 
												a_tk_number, 
												a_invoice1, 
												a_invoice2, 
												a_airline_start, 
												a_start_airport, 
												a_stop_airport, 
												a_airline_issue, 
												a_pnr_status, 
												a_airport_name, 
												a_airport_num, 
												a_airport_time, 
												a_airport_time1, 
												a_airline_print, 
												a_airline_return, 
												a_start_airport2, 
												a_stop_airport2, 
												a_airport_name2, 
												a_airport_num2, 
												a_airport_time2, 
												a_airport_time3, 
												a_pnr_number1, 
												a_tk_number2, 
												a_settle_type, 
												a_cls_type, 
												a_airline_amt, 
												a_airport_cnt, 
												a_amt_act, 
												a_rate, 
												a_tax, 
												a_fee, 
												a_fee1, 
												a_cms, 
												a_amt, 
												a_air_amt, 
												acc_bal_amt, 
												rand_fee, 
												a_tk_by, 
												a_acc_by, 
												a_re_by, 
												a_memo, 
												a_mco_num, 
												rand_fee_num, 
												seqm
												)
												values
												('$estimateCode', 
												'$rand_id_air[$k]', 
												'$pnrnum[$k]', 
												'$ticket[$k]', 
												'', 
												'', 
												'$stdate_air[$k]', 
												'$st_air[$k]', 
												'$de_air[$k]', 
												'', 
												'', 
												'$sairnm[$k]', 
												'', 
												'$sairtime[$k]', 
												'$dairtime[$k]', 
												'$airdate[$k]', 
												'$redate_air[$k]', 
												'$rst_air[$k]', 
												'$rde_air[$k]', 
												'$rairnm[$k]', 
												'', 
												'$rairtime[$k]', 
												'$dairtime[$k]', 
												'$rpnrnum[$k]', 
												'$rticket[$k]', 
												'$a_settle_type[$k]', 
												'$a_cls_type[$k]', 
												'$a_airline_amt[$k]', 
												'$air_p[$k]', 
												'', 
												'$air_rate[$k]', 
												'$airtax[$k]', 
												'$airmco[$k]', 
												'$mcofee[$k]', 
												'$a_cms[$k]', 
												'$a_amt[$k]', 
												'$a_air_amt[$k]', 
												'', 
												'$a_rand_fee[$k]', 
												'', 
												'', 
												'', 
												'', 
												'', 
												'', 
												'$k'
												)";
						$a_rst1 = mysql_query($a_qry1);
						//print_r($a_qry1);
						
						
						$seqtmp2=$seqtmp+1;
						//$totamt=$a_amt[$k];
						if ($a_settle_type[$k]==1) {
						    $totamt=$a_air_amt[$k];
						} else {
							$totamt=-($a_amt[$k]);

						}
						if ($a_settle_type[$k]==1) {
						$history_qry1 = "insert into rand_pay 
																(
																rand_id, 
																reserveCode, 
																rand_date, 
																stDate, 
																tr_date, 
																tr_type, 
																tr_bank, 
																trans_rate, 
																trans_type, 
																pay_method, 
																payment, 
																r_payment, 
																set_memo, 
																seq_rand, 
																u_id, 
																wdate
																)
																values
																( 
																'$rand_id_air[$k]', 
																'$estimateCode', 
																now(), 
																'$stdate_air[$k]', 
																now(), 
																'', 
																'', 
																'USD', 
																'credit', 
																'$airsys', 
																'$totamt', 
																'', 
																'$pnrnum[$k]:$rpnrnum[$k]-발권합계:$a_airline_amt[$k]', 
																'$seqtmp2', 
																'{$user_dbinfo['userid']}', 
																now()
																);";
						//print_r($history_qry1);

						$history_rst1 = mysql_query($history_qry1);
						}
						$balamt=$totamt;
						
						$totamt=-($a_air_amt[$k]);
					   //echo $totamt."bl".$tmpamt;
						$qry4="insert into rand_company 
									( 
									reserveCode, 
									part_area, 
									part_id, 
									money_type, 
									base_rate, 
									amt,
									cur_amt,
									tr_date,
									p_memo,
									status,
									settle_memo,
									u_id, 
									rand_date,
									wdate
									)
									values
									(
									'$estimateCode', 
									'', 
									'$rand_id_air[$k]', 
									'debit', 
									'USD', 
									'$totamt',
									'0',
									'$airdate[$k]', 
									'$ramtmemo',
									'READY',
									'$pnrnum[$k]:$rpnrnum[$k]-발권합계:$a_airline_amt[$k]'
									'{$user_dbinfo['userid']}',
									'$stdate_air[$k]',
									now()
									);";
						$rst4 = mysql_query($qry4,$dbConn);
						if ($a_settle_type[$k]==1) {
							$qry1 = "update rand_company set cur_amt = '$balamt' ,status='DONE'
									 where rand_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && settle_memo like '%$pnrnum[$k]%'";

							$rst1 = mysql_query($qry1);	
							
							//exit;
						}
						

						$totamt = 0;
						$tmpamt = 0;
						$balamt = 0;
						
						
												
						
					}

			   }
			   
			   //STOP AIR
			   for($i=0; $i<count($stop_starair); $i++)
               {
			        if ($stop_starair[$i] != "") {

						$qry1="insert into reserve_airline_rstop 
											(reserveCode, 
											a_pnr_number, 
											seq, 
											a_tk_number, 
											a_type, 
											a_airline_start, 
											a_start_airport, 
											a_stop_airport, 
											a_airport_name, 
											a_airport_name2, 
											a_airport_time, 
											a_airport_time1, 
											a_write, 
											seqm
											)
											values
											('$estimateCode', 
											'$stop_pnr[$i]', 
											'$i', 
											'$stop_tk[$i]', 
											'', 
											'$stop_stardate[$i]', 
											'$stop_starair[$i]', 
											'$stop_stopair[$i]', 
											'$stop_airnum1[$i]', 
											'$stop_airnum2[$i]', 
											'$stop_time1[$i]', 
											'$stop_time2[$i]', 
											now(), 
											''
											)";

						$rst1 = mysql_query($qry1);






					}
			   
			   }		   
			   //예약멤버 저장
			   for($i=0; $i<count($t_name); $i++)
               {
				   $qry2 =" insert into reserve_traveler 
									( 
									grand_revNo, 
									reserveCode,
									pass_num,
									pass_date,
									e_memo,
									traveler_nm,
									traveler_enm,
									traveler_phone, 
									traveler_email,
									traveler_birth,
									traveler_room,
									seqint, 
									sextype, 
									room_type,
									pick_type,
									sale_price, 
									pick_area, 
									add_pay, 
									dis_pay, 
									last_pay, 
									wdate
									)
									values
									(
									'$total_estimateCode', 
									'$estimateCode',
									'$t_passnum[$i]',
									'$t_pass[$i]',
									'".addslashes($tmemo[$i])."',
									'$t_name[$i]', 
									'$t_ename[$i]',
									'$t_phone[$i]', 
									'$t_email[$i]',
									'$t_birth[$i]',
									'$room_num[$i]',
									'$i', 
									'$sexType[$i]', 
									'$pickRoomType1[$i]',
									'$pickPriceType1[$i]',
									'$unitPrice[$i]', 
									'$pickuploc[$i]', 
									'$addamt[$i]', 
									'$disamt[$i]', 
									'$lasttamt[$i]', 
									now()
									)";
				   $rst2 = mysql_query($qry2,$dbConn);
			   }
			   //단일투어 정보
			   for($j=0; $j<count($singleDayTourStartDate); $j++)
               {

				   				
					// start day
				   if ($arrivalDate !="") {
					    $s_date = explode("-",$arrivalDate);
				   } else {

						$s_date = explode("-",$startDate);
				   }
					
				   $add_date = $tday[$j]-1;
				   $pos1 = $pos[$j];
				   
				   $local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));	
				   $qry3 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									dis_desc, 
									progress, 
									c_progress, 
									air_astcity,
									air_stcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_arcity,
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 

									air_arcity2, 
									air_arriveDate2, 
									air_arrivetime2, 
									air_arriveNm2, 
									air_arriveMemo2, 
									air_stdate2, 
									air_sttime2, 
									air_stNm2, 
									air_stMemo2, 
									air_stcity2,
									air_astcity2,
									base_rate, 
									pricet,
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo,
									pos,
									wdate
									)
									values
									(
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB', 
									'$sarea',
									'$ttype', 
									'$l_p_code[$j]', 
									'".addslashes($singleTour[$j])."', 
									'$mtarea[$j]', 
									now(), 
									'$local_start', 
									'',
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$tday[$j]', 
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'$pickloc', 
									'$dismemo', 
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$astcity',
									'$stcity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$arrivecity', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 

									'$arrivecity2', 
									'$arrivalDate2', 
									'$arrivalTime2', 
									'$airname2', 
									'$arrivememo2', 
									'$departureDate2', 
									'$departureTime2', 
									'$departureairname2', 
									'$departurememo2', 
									'$stcity2',
									'$astcity2',


									'$brate', 
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'READY', 
									'READY', 
									'{$user_dbinfo['userid']}', 
									'$paymemo',
									'$pos[$j]',
									now()
									)";
		            $rst3 = mysql_query($qry3,$dbConn);
			   }

			   if ($tourcomp) {
				    $qry4="insert into rand_company 
										( 
										reserveCode, 
										part_area, 
										part_id, 
										money_type, 
										base_rate, 
										amt,
										cur_amt,
										tr_date,
										p_memo,
										status,
										u_id, 
										rand_date,
										wdate
										)
										values
										(
										'$estimateCode', 
										'$tourRegion', 
										'$tourcomp', 
										'credit', 
										'$brate', 
										'$ramt',
										'0',
										'$rDate', 
										'$ramtmemo',
										'READY',
										'{$user_dbinfo['userid']}',
										'$startDate',
										now()
										);";
					$rst4 = mysql_query($qry4,$dbConn);
			   }
               if ($tourcomp1) {
				    $qry4="insert into rand_company 
										( 
										reserveCode, 
										part_area, 
										part_id, 
										money_type, 
										base_rate, 
										amt,
										cur_amt,
										tr_date,
										p_memo,
										status,
										u_id,
										rand_date,
										wdate
										)
										values
										(
										'$estimateCode', 
										'$tourRegion1', 
										'$tourcomp1', 
										'debit', 
										'$brate', 
										'$pamt', 
										'0', 
										'',
										'READY',
										'$pamtmemo',
										'{$user_dbinfo['userid']}', 
										'$startDate',
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
										'$tgtotamt', 
										'$brate', 
										'$tgtotamt', 
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
				   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=$pdx&sub=$sub&ty=$ty'>";
			   } else if ($pricet == 3) {
				   $sub = "25";
				   $ty = 3;
				   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=$pdx&sub=$sub&ty=$ty'>";
			   } else {
			       echo "<meta http-equiv='refresh' content='0; url=./base_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=$pdx&sub=$sub&ty=$ty'>";
			   }
				
		} else if ($estimateCode != "") {
			  
			    //메인 저장
				//발란스계산
				//echo $order_status;
				//exit;

				$qry6= "update payment_history 
									set
									payment = '$tgtotamt' , 
									rate_payment= '$tgtotamt'
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
				$totbal2 =$tbalamt;//$tgtotamt - $totpay;
				
				if ($paystatus != "CGPAY") {
				  if ($paystatus != "GPAY") {

				  
					if ($totbal2 > 0) {
						$paystatus = "PPAY";
					}
					if ($totbal2 == 0) {
						$paystatus = "DONE";
					}
					
					if ($totbal2 == $tgtotamt) {
						$paystatus = "READY";
					}
					if ($totbal2 < 0) {
						$paystatus = "OPAY";
					}
				  }
				}
				if (($order_status == "CANCEL") && ($payc >0)) {
					
						$paystatus = "OPAY";
				} else if (($order_status == "CANCEL") && ($payc == 0)) {
					    $paystatus = "";
				}
				//echo $tgtotamt."<br >".$totpay ;
				//exit;
				$qry1 ="update reserve_info 
								set
								s_area = '$sarea',
	  						   	stDate = '$startDate' , 
								edDate = '$endDate' , 
								p_cnt = '$pcnt1' ,
								rand_id = '$rand',
								book_pri = '$r_name' , 
								book_phone = '$r_phone' , 
								book_email = '$r_email' , 
								p_name = '".addslashes($pname)."', 
								dis_code = '$pickloc' , 
								c_code = '$dismemo' , 
								progress = '".addslashes($pmemo)."' , 
								c_progress = '".addslashes($cmemo)."' ,  
								tour_pcnt ='$tcnt',
								room_cnt = '$rcnt1',
								air_astcity = '$astcity' , 
								air_arcity = '$arrivecity' , 
								air_arriveDate = '$arrivalDate' , 
								air_arrivetime = '$arrivalTime' , 
								air_arriveNm = '$airname' , 
								air_arriveMemo = '$arrivememo' ,
								air_stcity = '$stcity' , 
								air_stdate = '$departureDate' , 
								air_sttime = '$departureTime' , 
								air_stNm = '$departureairname' , 
								air_stMemo = '$departurememo' , 

								air_astcity2 = '$astcity2' , 
								air_arcity2 = '$arrivecity2' , 
								air_arriveDate2 = '$arrivalDate2' , 
								air_arrivetime2 = '$arrivalTime2' , 
								air_arriveNm2 = '$airname2' , 
								air_arriveMemo2 = '$arrivememo2' ,
								air_stcity2 = '$stcity2' , 
								air_stdate2 = '$departureDate2' , 
								air_sttime2 = '$departureTime2' , 
								air_stNm2 = '$departureairname2' , 
								air_stMemo2 = '$departurememo2' , 

								pricet ='$pricet',
								last_sale = '$ttamt' , 
								last_dis = '$ttotdis' , 
								last_add = '$ttotaddamt' , 
								last_total = '$tgtotamt' , 
								last_bal = '$totbal2' ,
								payment_st= '$paystatus',
							    rev_status = '$order_status' , 
								muser_id ='{$user_dbinfo['userid']}', 
								pay_memo = '$paymemo' , 
								wdate = now()
								
								where
								reserveCode = '$estimateCode' ";
					
				$rst1 = mysql_query($qry1,$dbConn);
				//echo $qry1;
				//exit;
				$qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$pickcode' && parent = 'SUB'";
				//echo $qryd;
				//exit;
				$rstd = mysql_query($qryd,$dbConn);
				if ($tourpick != "") {
					
				    $propic = getProductMaster($tourpick);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,

									air_arcity2, 
									air_arriveDate2, 
									air_arrivetime2, 
									air_arriveNm2, 
									air_arriveMemo2, 
									air_stdate2, 
									air_sttime2, 
									air_stNm2, 
									air_stMemo2, 
									air_stcity2,
									air_astcity2,


									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$sarea',
									'$ttype', 
									'$tourpick', 
									'".addslashes($propic['p_name'])."', 
									'', 
									now(), 
									'$arrivalDate', 
									'$arrivalDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'pick', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',

									'$arrivecity2', 
									'$arrivalDate2', 
									'$arrivalTime2', 
									'$airname2', 
									'$arrivememo2', 
									'$departureDate2', 
									'$departureTime2', 
									'$departureairname2', 
									'$departurememo2', 
									'$stcity2',
									'$astcity2',

									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'$paystatus', 
									'$order_status', 
									'{$user_dbinfo['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);


					

			   }
			   $qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$sendcode' && parent = 'SUB'";
			   $rstd = mysql_query($qryd,$dbConn);
			   if ($toursend != "") {
				    
				    $prosend = getProductMaster($toursend);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									air_arcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 
									air_stcity,
									air_astcity,

									air_arcity2, 
									air_arriveDate2, 
									air_arrivetime2, 
									air_arriveNm2, 
									air_arriveMemo2, 
									air_stdate2, 
									air_sttime2, 
									air_stNm2, 
									air_stMemo2, 
									air_stcity2,
									air_astcity2,


									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$sarea',
									'$ttype', 
									'$toursend', 
									'".addslashes($prosend['p_name'])."', 
									'', 
									now(), 
									'$departureDate', 
									'$departureDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'send', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									'$arrivecity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 
									'$stcity',
									'$astcity',

									'$arrivecity2', 
									'$arrivalDate2', 
									'$arrivalTime2', 
									'$airname2', 
									'$arrivememo2', 
									'$departureDate2', 
									'$departureTime2', 
									'$departureairname2', 
									'$departurememo2', 
									'$stcity2',
									'$astcity2',

									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'$paystatus', 
									'$order_status', 
									'{$user_dbinfo['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);





			   }
				//////////////pick2send2/////////////////////

			    $qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$pickcode2' && parent = 'SUB'";
				//echo $qryd;
				//exit;
				$rstd = mysql_query($qryd,$dbConn);
				if ($tourpick != "") {
					
				    $propic = getProductMaster($tourpick);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									
									air_arcity2, 
									air_arriveDate2, 
									air_arrivetime2, 
									air_arriveNm2, 
									air_arriveMemo2, 
									air_stdate2, 
									air_sttime2, 
									air_stNm2, 
									air_stMemo2, 
									air_stcity2,
									air_astcity2,


									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$sarea',
									'$ttype', 
									'$tourpick', 
									'".addslashes($propic['p_name'])."', 
									'', 
									now(), 
									'$arrivalDate', 
									'$arrivalDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'pick2', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									
									'$arrivecity2', 
									'$arrivalDate2', 
									'$arrivalTime2', 
									'$airname2', 
									'$arrivememo2', 
									'$departureDate2', 
									'$departureTime2', 
									'$departureairname2', 
									'$departurememo2', 
									'$stcity2',
									'$astcity2',

									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'$paystatus', 
									'$order_status', 
									'{$user_dbinfo['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);


					

			    }
			   $qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$sendcode2' && parent = 'SUB'";
			   $rstd = mysql_query($qryd,$dbConn);
			   if ($toursend != "") {
				    
				    $prosend = getProductMaster($toursend);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									c_code,
									progress, 
									c_progress, 
									
									air_arcity2, 
									air_arriveDate2, 
									air_arrivetime2, 
									air_arriveNm2, 
									air_arriveMemo2, 
									air_stdate2, 
									air_sttime2, 
									air_stNm2, 
									air_stMemo2, 
									air_stcity2,
									air_astcity2,


									base_rate,
									pricet, 
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo, 
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$sarea',
									'$ttype', 
									'$toursend', 
									'".addslashes($prosend['p_name'])."', 
									'', 
									now(), 
									'$departureDate', 
									'$departureDate', 
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$cday',
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'send2', 
									'$dismemo',
									'".addslashes($pmemo)."' , 
									'".addslashes($cmemo)."' , 
									
									'$arrivecity2', 
									'$arrivalDate2', 
									'$arrivalTime2', 
									'$airname2', 
									'$arrivememo2', 
									'$departureDate2', 
									'$departureTime2', 
									'$departureairname2', 
									'$departurememo2', 
									'$stcity2',
									'$astcity2',

									'$brate',
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'$paystatus', 
									'$order_status', 
									'{$user_dbinfo['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);





			   }
			   //STOP AIR
			   $qryd = "delete from reserve_airline_rstop 
										where
										reserveCode = '$estimateCode'";
			   $rstd = mysql_query($qryd,$dbConn);
			   for($i=0; $i<count($stop_starair); $i++)
               {
			        if ($stop_starair[$i] != "") {

						$qry1="insert into reserve_airline_rstop 
											(reserveCode, 
											a_pnr_number, 
											seq, 
											a_tk_number, 
											a_type, 
											a_airline_start, 
											a_start_airport, 
											a_stop_airport, 
											a_airport_name, 
											a_airport_name2, 
											a_airport_time, 
											a_airport_time1, 
											a_write, 
											seqm
											)
											values
											('$estimateCode', 
											'$stop_pnr[$i]', 
											'$i', 
											'$stop_tk[$i]', 
											'', 
											'$stop_stardate[$i]', 
											'$stop_starair[$i]', 
											'$stop_stopair[$i]', 
											'$stop_airnum1[$i]', 
											'$stop_airnum2[$i]', 
											'$stop_time1[$i]', 
											'$stop_time2[$i]', 
											now(), 
											''
											)";

						$rst1 = mysql_query($qry1);






					}
			   
				}	
				//예약멤버 저장
				$qryd = "delete from reserve_traveler 
										where
										reserveCode = '$estimateCode'";
				//$rstd = mysql_query($qryd,$dbConn);
			    for($i=0; $i<count($t_name); $i++)
                { 
				   $qry2 =" insert into reserve_traveler 
									( 
									grand_revNo, 
									reserveCode,
									pass_num,
									pass_date,
									e_memo,
									traveler_nm,
									traveler_enm,
									traveler_phone, 
									traveler_email,
									traveler_birth,
									traveler_room,
									seqint, 
									sextype, 
									room_type,
									pick_type,
									sale_price, 
									pick_area, 
									add_pay, 
									dis_pay, 
									last_pay, 
									wdate
									)
									values
									(
									'$total_estimateCode', 
									'$estimateCode',
									'$t_passnum[$i]',
									'$t_pass[$i]',
									'".htmlspecialchars($tmemo[$i])."',
									'$t_name[$i]', 
									'$t_ename[$i]',
									'$t_phone[$i]', 
									'$t_email[$i]',
									'$t_birth[$i]',
									'$room_num[$i]',
									'$i', 
									'$sexType[$i]', 
									'$pickRoomType1[$i]',
									'$pickPriceType1[$i]',
									'$unitPrice[$i]', 
									'$pickuploc[$i]', 
									'$addamt[$i]', 
									'$disamt[$i]', 
									'$lasttamt[$i]', 
									now()
									)";
				   echo $qry2;
				   exit;
				   $rst2 = mysql_query($qry2,$dbConn);
			    }

			    //단일투어 정보
				$qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode'  && parent = 'SUB' && p_code not like  '%PICKUP%' && p_code not like  '%SENDING%'";
				//echo $qryd;
				//exit;
				$rstd = mysql_query($qryd,$dbConn);
				for($j=0; $j<count($singleDayTourStartDate); $j++)
               {

				   				
					// start day
				   /*
				   if ($arrivalDate !="") {
					    $s_date = explode("-",$arrivalDate);
				   } else {

						$s_date = explode("-",$startDate);
				   }
					
				   $add_date = $tday[$j]-1;
				   */
				   $pos1 = $pos['j'];
				   
				   //$local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));	
				   $qry3 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent, 
									s_area,
									tour_type, 
									p_code, 
									p_name, 
									meet_area, 
									revDate, 
									stDate, 
									edDate, 
									p_cnt,
									tour_pcnt,
									room_cnt,
									c_day,
									rand_id,
									book_pri, 
									book_phone, 
									book_email, 
									dis_code, 
									dis_desc, 
									progress, 
									c_progress, 
									air_astcity,
									air_stcity, 
									air_arriveDate, 
									air_arrivetime, 
									air_arriveNm, 
									air_arriveMemo, 
									air_arcity,
									air_stdate, 
									air_sttime, 
									air_stNm, 
									air_stMemo, 

									air_arcity2, 
									air_arriveDate2, 
									air_arrivetime2, 
									air_arriveNm2, 
									air_arriveMemo2, 
									air_stdate2, 
									air_sttime2, 
									air_stNm2, 
									air_stMemo2, 
									air_stcity2,
									air_astcity2,

									base_rate, 
									pricet,
									last_sale, 
									last_dis, 
									last_add, 
									last_total, 
									last_bal, 
									payment_st, 
									rev_status, 
									userid, 
									pay_memo,
									pos,
									wdate
									)
									values
									(
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$sarea',
									'$ttype', 
									'$l_p_code[$j]', 
									'".addslashes($singleTour[$j])."', 
									'$mtarea[$j]', 
									'$revdate', 
									'$singleDayTourStartDate[$j]', 
									'',
									'$pcnt1',
									'$tcnt',
									'$rcnt1',
									'$tday[$j]', 
									'$rand',
									'$r_name', 
									'$r_phone', 
									'$r_email', 
									'$pickloc', 
									'$dismemo', 
									'".addslashes($pmemo)."', 
									'".addslashes($cmemo)."', 
									'$astcity',
									'$stcity', 
									'$arrivalDate', 
									'$arrivalTime', 
									'$airname', 
									'$arrivememo', 
									'$arrivecity', 
									'$departureDate', 
									'$departureTime', 
									'$departureairname', 
									'$departurememo', 

									'$arrivecity2', 
									'$arrivalDate2', 
									'$arrivalTime2', 
									'$airname2', 
									'$arrivememo2', 
									'$departureDate2', 
									'$departureTime2', 
									'$departureairname2', 
									'$departurememo2', 
									'$stcity2',
									'$astcity2',

									'$brate', 
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'$paystatus', 
									'$order_status', 
									'{$user_dbinfo['userid']}', 
									'$paymemo',
									'$pos[$j]',
									now()
									)";
		            $rst3 = mysql_query($qry3,$dbConn);
					//echo $qry3."<br />";
			   }
			   
				/*
				$qry1 = "select * from product_details_local where p_code = '$pcode'  && local_code not like  '%PICKUP%' && local_code not like  '%SENDING%'  order by day,position,seq_no asc";
									
				$rst1 = mysql_query($qry1);
				$cntd = mysql_num_rows($rst1);
				$j = 0;
				while($r_row = mysql_fetch_assoc($rst1)):
				   // start day
				   $s_date = explode("-",$startDate);
					
				   $add_date = $r_row[day]-1;

				   $local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));	
				   //echo $local_start."<br>";
				   $prodsinfo = getProductMaster($r_row[local_code]);
				   $qry3 ="update reserve_info 
									set
									p_name ='".addslashes($prodsinfo[p_name])."',
									rand_id = '$rand',
									stDate = '$local_start',
									meet_area = '$mtarea[$j]' ,
									
									h_cnt = '$hcnt1',
									p_cnt = '$pcnt1' ,
									c_day = '$tday[$j]',
									rev_status = '$order_status' , 
									payment_st= '$paystatus',
									progress = '".addslashes($pmemo)."' , 
									pay_memo = '".addslashes($paymemo)."' , 						
									muser_id ='$user_dbinfo[userid]',
									pos = '$pos[$j]',
									rev_status = '$order_status' ,
									air_astcity = '$astcity',
									air_arcity = '$arrivecity' , 
									air_arriveDate = '$pickar' , 
									air_arrivetime = '$arrivalTime' , 
									air_arriveNm = '$airname' , 
									air_arriveMemo = '$arrivememo' ,
									air_stcity = '$stcity' , 
									air_stdate = '$pickst' , 
									air_sttime = '$departureTime' , 
									air_stNm = '$departureairname' , 
									air_stMemo = '$departurememo' , 
									muser_id ='$user_dbinfo[userid]',
									pos = '$pos[$j]',
									wdate = now()
									where
									reserveCode = '$estimateCode' && 
								    parent = 'SUB' && p_code = '$l_p_code[$j]' && seq_no='$seqnum[$j]'";
		           $rst3 = mysql_query($qry3,$dbConn);
				   //echo $qry3."<br>";
				   $j++;
			    endwhile;
				*/
				//echo $tourcomp;
			  //exit;
			   if ($tourcomp !="") {
				    
						$qryr1 = "select part_id from rand_company where reserveCode = '$estimateCode' && money_type='credit' && p_memo !='항공발권'";
				        $rstr1 = mysql_query($qryr1);
						$rowr1 = mysql_fetch_assoc($rstr1);
						//echo $tourcomp."|".$rowr1[part_id];
						///exit;
						if ($rowr1['part_id'] != $tourcomp) {
							$qryq = "select rand_id from rand_pay where reserveCode = '$estimateCode' && rand_id ='$tccomp' && trans_type='credit' && stDate='$startDate'";
							$rstq = mysql_query($qryq);
							$rowrcnt = mysql_num_rows($rstq);

							//$rowr1 = mysql_fetch_assoc($rstr);
							if (($rowrcnt > 0)) {
								 Misc::jvAlert("이 업체에 페이먼트 자료가 있습니다. <br />회계담당자에게 먼저 문의하신후 수정하세요!!!","history.back(-1)");
								 exit;
							}
							$qryc = "delete from rand_company where part_id ='{$rowr1['part_id']}' && reserveCode = '$estimateCode' && money_type='credit' && p_memo !='항공발권'";
							$rstc = mysql_query($qryc);
						
						
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
													'$tourRegion', 
													'$tourcomp', 
													'credit', 
													'$brate', 
													'$ramt', 
													'$rDate', 
													'$ramtmemo',
													'READY',
													'{$user_dbinfo['userid']}', 
													now()
													);";
							    $rst4 = mysql_query($qry4,$dbConn);
						   } else {
								
								$qry4 ="
											update rand_company 
												set
												
												money_type = 'credit' , 
												base_rate = '$brate' , 
												amt = '$ramt' , 
												tr_date = '$tr_date' , 
												p_memo = '$ramtmemo',
												rand_date ='$startDate'
												where
												reserveCode = '$estimateCode' && part_id='$tourcomp' && money_type = 'credit' && p_memo !='항공발권'";
							   $rst4 = mysql_query($qry4,$dbConn);
						 }
					
			   }
               if ($tourcomp1) {
				   
						$qryr1 = "select part_id from rand_company where reserveCode = '$estimateCode' && money_type='debit' && p_memo !='항공발권'";
				        $rstr1 = mysql_query($qryr1);
						$rowr1 = mysql_fetch_assoc($rstr1);
						//echo $tourcomp1."|".$rowr1[part_id];
						//exit;
						
						if ($rowr1['part_id'] != $tourcomp1) {
							$qryq = "select rand_id from rand_pay where reserveCode = '$estimateCode' && rand_id ='$tdcomp' && trans_type='debit' && stDate='$startDate'";
							$rstq = mysql_query($qryq);
							$rowrcnt = mysql_num_rows($rstq);
							
							if (($rowrcnt > 0)) {
								 Misc::jvAlert("이 업체에 페이먼트 자료가 있습니다. <br />회계담당자에게 먼저 문의하신후 수정하세요!!!!!!","history.back(-1)");
								 exit;
							} 
							$qryc = "delete from rand_company where part_id ='{$rowr1['part_id']}' && reserveCode = '$estimateCode' && money_type='debit' && p_memo !='항공발권'";
							$rstc = mysql_query($qryc);
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
														'$tourRegion1', 
														'$tourcomp1', 
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
						
							$qry4 ="
											update rand_company 
												set
												
												money_type = 'debit' , 
												base_rate = '$brate' , 
												amt = '$pamt' , 
												p_memo = '$pamtmemo',
												rand_date ='$startDate'
												where
												reserveCode = '$estimateCode' && part_id='$tourcomp1' && money_type = 'debit' && p_memo !='항공발권'";

							   $rst4 = mysql_query($qry4,$dbConn);
						    //echo $qry4;
							//exit;
						}
						
					//}
			   }
			   //항공정보
			   $pre_airline_qry2 = "delete from reserve_airline_pnr where reserveCode = '$estimateCode'";
			   $pre_airline_rst2 = mysql_query($pre_airline_qry2);
			   for($k=0; $k<count($pnrnum); $k++)
			   {
					//echo count($pnrnum);
					//echo $a_pnr_number[$k];
					if($pnrnum[$k])
					{
					 
		
						// 입력
						$a_qry1 = "insert into reserve_airline_pnr 
												(reserveCode, 
												rand_id, 
												a_pnr_number, 
												a_tk_number, 
												a_invoice1, 
												a_invoice2, 
												a_airline_start, 
												a_start_airport, 
												a_stop_airport, 
												a_airline_issue, 
												a_pnr_status, 
												a_airport_name, 
												a_airport_num, 
												a_airport_time, 
												a_airport_time1, 
												a_airline_print, 
												a_airline_return, 
												a_start_airport2, 
												a_stop_airport2, 
												a_airport_name2, 
												a_airport_num2, 
												a_airport_time2, 
												a_airport_time3, 
												a_pnr_number1, 
												a_tk_number2, 
												a_settle_type, 
												a_cls_type, 
												a_airline_amt, 
												a_airport_cnt, 
												a_amt_act, 
												a_rate, 
												a_tax, 
												a_fee, 
												a_fee1, 
												a_cms, 
												a_amt, 
												a_air_amt, 
												acc_bal_amt, 
												rand_fee, 
												a_tk_by, 
												a_acc_by, 
												a_re_by, 
												a_memo, 
												a_mco_num, 
												rand_fee_num, 
												seqm
												)
												values
												('$estimateCode', 
												'$rand_id_air[$k]', 
												'$pnrnum[$k]', 
												'$ticket[$k]', 
												'', 
												'', 
												'$stdate_air[$k]', 
												'$st_air[$k]', 
												'$de_air[$k]', 
												'', 
												'', 
												'$sairnm[$k]', 
												'', 
												'$sairtime[$k]', 
												'$dairtime[$k]', 
												'$airdate[$k]', 
												'$redate_air[$k]', 
												'$rst_air[$k]', 
												'$rde_air[$k]', 
												'$rairnm[$k]', 
												'', 
												'$rairtime[$k]', 
												'$dairtime[$k]', 
												'$rpnrnum[$k]', 
												'$rticket[$k]', 
												'$a_settle_type[$k]', 
												'$a_cls_type[$k]', 
												'$a_airline_amt[$k]', 
												'$air_p[$k]', 
												'', 
												'$air_rate[$k]', 
												'$airtax[$k]', 
												'$airmco[$k]', 
												'$mcofee[$k]', 
												'$a_cms[$k]', 
												'$a_amt[$k]', 
												'$a_air_amt[$k]', 
												'', 
												'$a_rand_fee[$k]', 
												'', 
												'', 
												'', 
												'', 
												'', 
												'', 
												'$k'
												)";
						$a_rst1 = mysql_query($a_qry1);
						if ($a_settle_type[$k]!=2) {
						    $totamt=$a_air_amt[$k];
							$divi = "credit";
						} else {
							$totamt=-($a_amt[$k]);
							$divi = "debit";
						}
						// 넣기전에 이미 있는지 체크한다.
						$pre_qry1 = "select max(seq_no) as seq,seq_no from rand_company where part_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && p_memo='항공발권'
						&& settle_memo like '%$ticket[$k]%' && amt = '$totamt'";
						$pre_rst1 = mysql_query($pre_qry1);
						$rand_row1 = mysql_Fetch_assoc($pre_rst1);
						if ($rand_row1['seq'] == 0) {
						   $seqtmp = 0;
						} else {
						   $seqtmp = $rand_row1['seq']+1;
						}
						
						//echo $pre_qry1."<br />";
						//exit;
						if (($rand_row1['seq'] == '')) {
							$qry4="insert into rand_company 
											( 
											reserveCode, 
											part_area, 
											part_id, 
											money_type, 
											base_rate, 
											amt, 
											cur_amt, 
											tr_date, 
											p_memo, 
											air_ptype, 
											status, 
											settle_memo, 
											u_id, 
											rand_date, 
											wdate
											)
											values
											( 
											'$estimateCode', 
											'', 
											'$rand_id_air[$k]', 
											'$divi', 
											'USD', 
											'$totamt', 
											'0', 
											'$airdate[$k]', 
											'항공발권', 
											'$a_settle_type[$k]', 
											'READY', 
											'".$pnrnum[$k].":".$ticket[$k]."', 
											'{$user_dbinfo['userid']}', 
											'$stdate_air[$k]', 
											now()
											)";
							//echo $qry4;
							$rst4 = mysql_query($qry4,$dbConn);
							//exit;
						}
						$oldticket = $ticket[$k];
						if ($order_status == "DONE") {
							if ($a_settle_type[$k]!=2) {
								$totamt=$a_air_amt[$k];
								$divi = "credit";
							} else {
								$totamt=-($a_amt[$k]);
								$divi = "debit";
							}
							$rand_qry1 = "update rand_company set amt='$totamt',
																		money_type ='$divi',
																		settle_memo='$pnrnum[$k]:$ticket[$k]',
																		air_ptype='$a_settle_type[$k]',
																		u_id ='{$user_dbinfo['userid']}'
																		 
																	  where part_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && p_memo = '항공발권' && settle_memo like '%$ticket[$k]%' && amt='$totamt'";

							$rand_rst1 = mysql_query($rand_qry1);
							
							
							if ($a_settle_type[$k]!= 2) {
								$tmpamt1=$a_air_amt[$k];
								$pre_qry1 = "select max(seq_no) as seq from rand_pay where rand_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && set_memo like '%$pnrnum[$k]%' && payment = '$totamt'";
								$pre_rst1 = mysql_query($pre_qry1);
								$rand_row2 = mysql_Fetch_assoc($pre_rst1);
								
								if ($rand_row2['seq'] == 0) {
									$history_qry1 = "insert into rand_pay 
																			(
																			rand_id, 
																			reserveCode, 
																			rand_date, 
																			stDate, 
																			tr_date, 
																			tr_type, 
																			tr_bank, 
																			trans_rate, 
																			trans_type, 
																			pay_method, 
																			payment, 
																			r_payment, 
																			set_memo, 
																			seq_rand, 
																			u_id, 
																			wdate
																			)
																			values
																			( 
																			'$rand_id_air[$k]', 
																			'$estimateCode', 
																			now(), 
																			'$stdate_air[$k]', 
																			now(), 
																			'', 
																			'', 
																			'USD', 
																			'credit', 
																			'airsys', 
																			'$totamt', 
																			'', 
																			'$pnrnum[$k]', 
																			'$seqtmp', 
																			'{$user_dbinfo['userid']}', 
																			now()
																			);";
									//print_r($history_qry1);
									//exit;
									$history_rst1 = mysql_query($history_qry1);

								} else {
									$history_qry1 = "update rand_pay SET trans_type ='credit',
																				payment='$totamt',
																				pay_method= 'airsys',
																				seq_rand = '{$rand_row1['seq_no']}' 
																				where rand_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && set_memo like '%$pnrnum[$k]%' && payment='$totamt'";
									$history_rst1 = mysql_query($history_qry1);

								}
								//echo $history_qry1."<br />";
								//$totamt1=-($totamt);

								$balamt=$totamt-$tmpamt1;
								//$balamt=$totamt;
								
								$qry1 = "update rand_company set cur_amt = '$totamt' ,status='SETTLEDONE'
										 where part_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && settle_memo like '%$pnrnum[$k]%' && amt='$totamt'";

								$rst2 = mysql_query($qry1);	
								
							}

								$totamt1 = 0;
								$tmpamt1 = 0;
								$balamt = 0;

							
						}
												
						
					}

			   }
			   //exit;
			   Misc::jvAlert("저장 완료!!!");
			   if ($pricet == 1) {
				   $sub = "15";
				   $ty = 1;
			   } else if ($pricet == 3) {
				   $sub = "25";
				   $ty = 3;
			   }
			   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_list.php?estimateCode=$estimateCode&division=3&pdx=2&sub=$sub&ty=$ty&pricet=$pricet'>";

		}
    } else if ($mode == "paymentProcess") {

			  //payment history
			   
				if ($paymentmethod == "creditcard") { //신용카드
					   $order = $estimateCode;
					   $amt = $clastpayamt;
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
								   $qry6= "update reserve_info 
														set
														last_bal = '$tlastpay' , 
														payment_st = '$paycap'  
														where
														reserveCode = '$estimateCode'  ";

												
								  $rst6 = mysql_query($qry6,$dbConn);
										
							  } else {
									
								  Misc::jvAlert("결제 실패 다시 확인하시고 결제하세요!!!");
								   if ($pricet == 1) {
									   $sub = "15";
								   } else if ($pricet == 3) {
									   $sub = "25";
								   }
								   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_m.php?estimateCode=$estimateCode&division=$division&pdx=$pdx&sub=$sub&ty=$ty&pricet=$pricet'>";
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
			   echo "<meta http-equiv='refresh' content='0; url=./base_reservation_mt.php?estimateCode=$estimateCode&division=$division&pdx=$pdx&sub=$sub&ty=$ty&pricet=$pricet'>";
			   exit;

	} else if ($mode == "paymentReturn") {
		      if ($paymentmethod != "creditcard") {
					$currencytype2 == "USD";
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
											'USD', 
											'$rpay2', 
											'$ratepay', 
											'0', 
											'$payst1', 
											'$dmemo2', 
											'$puser2', 
											now()
											);";

			  $rst5 = mysql_query($qry5,$dbConn);
			  //$tlastpay=$lastbalance - $rpay2;
			  

			  Misc::jvAlert("환불신청 완료!!!");
			  if ($pricet == 1) {
				   $sub = "15";
				   $ty = 1;
			   } else if ($pricet == 3) {
				   $sub = "25";
				   $ty = 3;
			   }
			  echo "<meta http-equiv='refresh' content='0; url=./base_reservation_m.php?estimateCode=$estimateCode&division=$division&pdx=$pdx&sub=$sub&ty=$ty&pricet=$pricet'>";


	}

?>