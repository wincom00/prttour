
<?php
/*echo "<pre>";
print_r($_POST);
echo "</pre>";
exit;
*/
    //include "include/inc_base.php";
	if (!function_exists('reserve_numeric_sum')) {
		function reserve_numeric_sum($values) {
			$total = 0;
			if (!is_array($values)) {
				$v = str_replace(",", "", trim((string)$values));
				return $v === "" ? 0.0 : (float)$v;
			}
			foreach ($values as $value) {
				$value = str_replace(",", "", trim((string)$value));
				if ($value === "") {
					continue;
				}
				$total += (float)$value;
			}
			return $total;
		}
	}
	if (!function_exists('reserve_save_cruise_entries')) {
		function reserve_save_cruise_entries($estimateCode, $write_userid) {
			global $dbConn;

			mysql_query("delete from reserve_cruise where reserveCode = '$estimateCode'", $dbConn);

			$c_depart_date = isset($_POST['c_depart_date']) ? $_POST['c_depart_date'] : array();
			$c_return_date = isset($_POST['c_return_date']) ? $_POST['c_return_date'] : array();
			$c_cruise_line = isset($_POST['c_cruise_line']) ? $_POST['c_cruise_line'] : array();
			$c_ship_name = isset($_POST['c_ship_name']) ? $_POST['c_ship_name'] : array();
			$c_depart_port = isset($_POST['c_depart_port']) ? $_POST['c_depart_port'] : array();
			$c_arrive_port = isset($_POST['c_arrive_port']) ? $_POST['c_arrive_port'] : array();
			$c_book_no = isset($_POST['c_book_no']) ? $_POST['c_book_no'] : array();
			$c_room_type = isset($_POST['c_room_type']) ? $_POST['c_room_type'] : array();
			$c_pax = isset($_POST['c_pax']) ? $_POST['c_pax'] : array();
			$c_unit_price = isset($_POST['c_unit_price']) ? $_POST['c_unit_price'] : array();
			$c_tax_port_fee = isset($_POST['c_tax_port_fee']) ? $_POST['c_tax_port_fee'] : array();
			$c_settle_type = isset($_POST['c_settle_type']) ? $_POST['c_settle_type'] : array();
			$c_vendor_id = isset($_POST['c_vendor_id']) ? $_POST['c_vendor_id'] : array();
			$c_net_amt = isset($_POST['c_net_amt']) ? $_POST['c_net_amt'] : array();
			$c_sale_amt = isset($_POST['c_sale_amt']) ? $_POST['c_sale_amt'] : array();
			$c_profit = isset($_POST['c_profit']) ? $_POST['c_profit'] : array();
			$c_memo = isset($_POST['c_memo']) ? $_POST['c_memo'] : array();
			$rand_id_cruise = isset($_POST['rand_id_cruise']) ? $_POST['rand_id_cruise'] : array();

			if (!isset($c_depart_date) || !is_array($c_depart_date)) {
				return;
			}

			foreach ($c_depart_date as $k => $dd) {
				$depart_date = isset($c_depart_date[$k]) ? trim($c_depart_date[$k]) : "";
				$return_date = isset($c_return_date[$k]) ? trim($c_return_date[$k]) : "";
				$cruise_line_raw = isset($c_cruise_line[$k]) ? trim($c_cruise_line[$k]) : "";
				$ship_name = isset($c_ship_name[$k]) ? trim($c_ship_name[$k]) : "";
				$depart_port_raw = isset($c_depart_port[$k]) ? trim($c_depart_port[$k]) : "";
				$arrive_port_raw = isset($c_arrive_port[$k]) ? trim($c_arrive_port[$k]) : "";
				$book_no = isset($c_book_no[$k]) ? trim($c_book_no[$k]) : "";
				$room_type_raw = isset($c_room_type[$k]) ? trim($c_room_type[$k]) : "";
				$memo_raw = isset($c_memo[$k]) ? trim($c_memo[$k]) : "";
				$sale_raw = isset($c_sale_amt[$k]) ? trim($c_sale_amt[$k]) : "";

				if ($depart_date == "" && $return_date == "" && $cruise_line_raw == "" && $ship_name == "" && $depart_port_raw == "" && $arrive_port_raw == "" && $book_no == "" && $room_type_raw == "" && $memo_raw == "" && ($sale_raw == "" || $sale_raw == "0")) {
					continue;
				}

				$rand = (isset($rand_id_cruise[$k]) && $rand_id_cruise[$k] != "") ? $rand_id_cruise[$k] : uniqid('cr_');
				$depart = $depart_date ? date("Y-m-d", strtotime($depart_date)) : "";
				$ret = (isset($c_return_date[$k]) && $c_return_date[$k]) ? date("Y-m-d", strtotime($c_return_date[$k])) : "";
				$depart_sql = $depart ? "'$depart'" : "NULL";
				$ret_sql = $ret ? "'$ret'" : "NULL";

				$cruise_line = isset($c_cruise_line[$k]) ? addslashes($c_cruise_line[$k]) : "";
				$depart_port = isset($c_depart_port[$k]) ? addslashes($c_depart_port[$k]) : "";
				$arrive_port = isset($c_arrive_port[$k]) ? addslashes($c_arrive_port[$k]) : "";
				$room_type = isset($c_room_type[$k]) ? addslashes($c_room_type[$k]) : "";
				$vendor_id = isset($c_vendor_id[$k]) ? addslashes($c_vendor_id[$k]) : "";
				$memo = isset($c_memo[$k]) ? addslashes($c_memo[$k]) : "";

				$pax = isset($c_pax[$k]) ? intval(str_replace(",", "", $c_pax[$k])) : 0;
				$unit_price = isset($c_unit_price[$k]) ? floatval(str_replace(",", "", $c_unit_price[$k])) : 0;
				$tax_port_fee = isset($c_tax_port_fee[$k]) ? floatval(str_replace(",", "", $c_tax_port_fee[$k])) : 0;
				$settle_type = isset($c_settle_type[$k]) ? intval($c_settle_type[$k]) : 1;
				$net_amt = isset($c_net_amt[$k]) ? floatval(str_replace(",", "", $c_net_amt[$k])) : 0;
				$sale_amt = isset($c_sale_amt[$k]) ? floatval(str_replace(",", "", $c_sale_amt[$k])) : 0;
				$profit = isset($c_profit[$k]) ? floatval(str_replace(",", "", $c_profit[$k])) : 0;

				$sql = "insert into reserve_cruise
							(reserveCode, rand_id, c_seqm,
							c_depart_date, c_return_date, c_cruise_line, c_ship_name,
							c_depart_port, c_arrive_port, c_book_no,
							c_room_type, c_pax, c_unit_price, c_tax_port_fee,
							c_settle_type, c_vendor_id,
							c_net_amt, c_sale_amt, c_profit, c_memo,
							wdate, userid)
							values
							('$estimateCode', '$rand', '$k',
							$depart_sql, $ret_sql,
							'$cruise_line', '".addslashes($ship_name)."',
							'$depart_port', '$arrive_port', '".addslashes($book_no)."',
							'$room_type', '$pax', '$unit_price', '$tax_port_fee',
							'$settle_type', '$vendor_id',
							'$net_amt', '$sale_amt', '$profit', '$memo',
							now(), '$write_userid')";
				if (!mysql_query($sql, $dbConn)) {
					error_log("reserve_cruise insert failed: ".mysql_error()." SQL=".$sql);
				}
			}
		}
	}
	if (!function_exists('reserve_calc_balance_from_payments')) {
		function reserve_calc_balance_from_payments($estimateCode, $totalAmount) {
			global $dbConn;

			$totalAmount = reserve_numeric_sum($totalAmount);
			if (trim((string)$estimateCode) == "") {
				return $totalAmount;
			}

			$paid = 0;
			$returned = 0;
			$qryp = "select payment, payment_status from payment_history where reserveCode = '$estimateCode' && (payment_status='DONE' || payment_status='RETURN')";
			$rstp = mysql_query($qryp, $dbConn);
			while($rstp && ($rowp = mysql_fetch_assoc($rstp))) {
				if ($rowp['payment_status'] == "RETURN") {
					$returned += reserve_numeric_sum($rowp['payment']);
				} else {
					$paid += reserve_numeric_sum($rowp['payment']);
				}
			}

			return round($totalAmount, 2) - round(($paid - $returned), 2);
		}
	}

    $server_ttamt = reserve_numeric_sum(isset($unitPrice) ? $unitPrice : array());
    $server_ttotaddamt = reserve_numeric_sum(isset($addamt) ? $addamt : array());
    $server_ttotdis = reserve_numeric_sum(isset($disamt) ? $disamt : array());
    $server_airtot = reserve_numeric_sum(isset($tot_air_amt) ? $tot_air_amt : array());
    $server_cruisetot = reserve_numeric_sum(isset($tot_cruise_amt) ? $tot_cruise_amt : array());
    if (isset($_POST['c_sale_amt']) && is_array($_POST['c_sale_amt'])) {
		$server_cruisetot = reserve_numeric_sum($_POST['c_sale_amt']);
	}
    $server_tgtotamt = $server_ttamt + $server_ttotaddamt + $server_airtot + $server_cruisetot - $server_ttotdis;

    $ttamt = number_format($server_ttamt, 2, '.', '');
    $ttotaddamt = number_format($server_ttotaddamt, 2, '.', '');
    $ttotdis = number_format($server_ttotdis, 2, '.', '');
    $tgtotamt = number_format($server_tgtotamt, 2, '.', '');

    if ($mode == "save") {
		$tbalamt = number_format(reserve_calc_balance_from_payments($estimateCode, $server_tgtotamt), 2, '.', '');
	}

    if ($mode == "save") {
		//처음접수
		if (($estimateCode == "") ) {
		       // 토탈예약용 예약코드
				 if ($grestimateCode=="") {
					$total_estimateNum = getNumReserve_total();
					$total_estimateCode = "TU".substr(time(), -4).$total_estimateNum;	
				} else {
					$total_estimateNum = getNumReserve_ctotal();
					$total_estimateCode = $grestimateCode;	
				}
				$estimateNum = getNumReserve();
				$estimateCode = "PUR".substr(time(), -4).$estimateNum;
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
									r_path,
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
									sprogress,
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
									hopt,
									vopt,
									wdate
									)
									values
									( 
									'$total_estimateNum',
									'$total_estimateCode', 
									'$estimateNum', 
									'$estimateCode', 
									'MAIN',
									'$rpath',
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
									'".addslashes($sendmemo)."' ,
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
									'$hopt',
									'$vopt',
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
									r_path,
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
									sprogress,
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
									'$rpath',
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
									'".addslashes($sendmemo)."' ,
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
									r_path,
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
									sprogress,
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
									'$rpath',
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
									'".addslashes($sendmemo)."' ,
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
									'$paystatus', 
									'$order_status', 
									'{$reserve_info2['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);




			   }
			   if ($tourpick3 != "") {
					
				    $propic = getProductMaster($tourpick3);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									r_path,
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
									sprogress,
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
									'{$reserve_info2['grand_revNo']}', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$rpath',
									'$sarea',
									'$ttype', 
									'$tourpick3', 
									'".addslashes($propic['p_name'])."', 
									'', 
									now(), 
									'$arrivalDate2', 
									'$arrivalDate2', 
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
									'".addslashes($sendmemo)."' ,
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
									'{$reserve_info2['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);
					
			   }
			   if ($toursend3 != "") {
				    
				    $prosend = getProductMaster($toursend3);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									r_path,
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
									sprogress,
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
									'{$reserve_info2['grand_revNo']}', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$rpath',
									'$sarea',
									'$ttype', 
									'$toursend3', 
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
									'".addslashes($sendmemo)."' ,
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
									'{$reserve_info2['userid']}', 
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
									'항공발권',
									'READY',
									'$pnrnum[$k]:$rpnrnum[$k]-발권합계:$a_airline_amt[$k]',
									'{$user_dbinfo['userid']}',
									'$stdate_air[$k]',
									now()
									);";
						$rst4 = mysql_query($qry4,$dbConn);
						if ($a_settle_type[$k]==1) {
							$qry1 = "update rand_company set cur_amt = '$balamt' ,status='DONE'
									 where part_id='$rand_id_air[$k]' && reserveCode = '$estimateCode' && settle_memo like '%$pnrnum[$k]%'";

							$rst1 = mysql_query($qry1);	
							
							//exit;
						}
						

						$totamt = 0;
						$tmpamt = 0;
						$balamt = 0;
						
						
												
						
					}

			   }
			   $cruise_write_userid = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : $userid;
			   reserve_save_cruise_entries($estimateCode, $cruise_write_userid);
			   
			   //STOP AIR
			   for($i=0; $i<count((array)$stop_starair); $i++)
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
			   $traveler_seq = 0;
			   for($i=0; $i<count((array)$t_name); $i++)
               {
				   $traveler_nm = isset($t_name[$i]) ? trim($t_name[$i]) : "";
				   $traveler_enm = isset($t_ename[$i]) ? trim($t_ename[$i]) : "";
				   $traveler_phone = isset($t_phone[$i]) ? trim($t_phone[$i]) : "";
				   $traveler_email = isset($t_email[$i]) ? trim($t_email[$i]) : "";
				   $traveler_birth = isset($t_birth[$i]) ? trim($t_birth[$i]) : "";
				   $traveler_room = isset($room_num[$i]) ? trim($room_num[$i]) : "";
				   $pass_num = isset($t_passnum[$i]) ? trim($t_passnum[$i]) : "";
				   $pass_date = isset($t_pass[$i]) ? trim($t_pass[$i]) : "";
				   $traveler_memo = isset($tmemo[$i]) ? trim($tmemo[$i]) : "";
				   $sex_type = isset($sexType[$i]) ? trim($sexType[$i]) : "";
				   $room_type = isset($pickRoomType1[$i]) ? trim($pickRoomType1[$i]) : "";
				   $pick_type = isset($pickPriceType1[$i]) ? trim($pickPriceType1[$i]) : "";
				   $sale_price = isset($unitPrice[$i]) ? str_replace(",", "", trim($unitPrice[$i])) : "0";
				   $pick_area = isset($pickuploc[$i]) ? trim($pickuploc[$i]) : "";
				   $add_pay = isset($addamt[$i]) ? str_replace(",", "", trim($addamt[$i])) : "0";
				   $dis_pay = isset($disamt[$i]) ? str_replace(",", "", trim($disamt[$i])) : "0";
				   $last_pay = isset($lasttamt[$i]) ? str_replace(",", "", trim($lasttamt[$i])) : "0";

				   if (
						$traveler_nm === "" &&
						$traveler_enm === "" &&
						$traveler_phone === "" &&
						$traveler_email === "" &&
						$traveler_birth === "" &&
						$traveler_room === "" &&
						$pass_num === "" &&
						$pass_date === ""
				   ) {
						continue;
				   }

				   if ($sale_price === "") { $sale_price = "0"; }
				   if ($add_pay === "") { $add_pay = "0"; }
				   if ($dis_pay === "") { $dis_pay = "0"; }
				   if ($last_pay === "") { $last_pay = "0"; }

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
									'".addslashes($pass_num)."',
									'$pass_date',
									'".addslashes($traveler_memo)."',
									'".addslashes($traveler_nm)."', 
									'".addslashes($traveler_enm)."',
									'".addslashes($traveler_phone)."', 
									'".addslashes($traveler_email)."',
									'$traveler_birth',
									'$traveler_room',
									'$traveler_seq', 
									'$sex_type', 
									'$room_type',
									'$pick_type',
									'$sale_price', 
									'".addslashes($pick_area)."', 
									'$add_pay', 
									'$dis_pay', 
									'$last_pay', 
									now()
									)";
				   $rst2 = mysql_query($qry2,$dbConn);
				   $traveler_seq++;
			   }
			   //단일투어 정보
			   for($j=0; $j<count((array)$singleDayTourStartDate); $j++)
               {

				   				
					// start day
				   if ($arrivalDate !="") {
					    $s_date = explode("-",$arrivalDate);
				   } else {

						$s_date = explode("-",$startDate);
				   }
					
				   $add_date = $tday[$j]-1;
				   $pos1 = $pos['j'];
				   
				   $local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));	
				   $qry3 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									r_path,
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
									sprogress,
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
									'$rpath',
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
									'$pmemo', 
									'$cmemo', 
									'".addslashes($sendmemo)."' ,
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

			   $collect_company_list = array();
			   if (is_array($tourcomp_multi)) {
					for ($ci=0; $ci<count($tourcomp_multi); $ci++) {
						$part_id = trim($tourcomp_multi[$ci]);
						$part_area = trim($tourRegion_multi[$ci]);
						$amt = trim($ramt_multi[$ci]);
						$memo = trim($ramtmemo_multi[$ci]);
						if ($part_id != "") {
							$collect_company_list[] = array(
								"part_id" => $part_id,
								"part_area" => $part_area,
								"amt" => ($amt === "" ? 0 : $amt),
								"memo" => $memo
							);
						}
					}
			   } else if ($tourcomp) {
					$collect_company_list[] = array(
						"part_id" => $tourcomp,
						"part_area" => $tourRegion,
						"amt" => ($ramt === "" ? 0 : $ramt),
						"memo" => $ramtmemo
					);
			   }
			   for ($ci=0; $ci<count($collect_company_list); $ci++) {
					$part_id = $collect_company_list[$ci]["part_id"];
					$part_area = addslashes($collect_company_list[$ci]["part_area"]);
					$amt = $collect_company_list[$ci]["amt"];
					$memo = addslashes($collect_company_list[$ci]["memo"]);
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
										'$part_area', 
										'$part_id', 
										'credit', 
										'$brate', 
										'$amt',
										'0',
										'$startDate', 
										'$memo',
										'READY',
										'{$user_dbinfo['userid']}',
										'$startDate',
										now()
										);";
					$rst4 = mysql_query($qry4,$dbConn);
			   }

			   $pay_company_list = array();
			   if (is_array($tourcomp1_multi)) {
					for ($di=0; $di<count($tourcomp1_multi); $di++) {
						$part_id = trim($tourcomp1_multi[$di]);
						$part_area = trim($tourRegion1_multi[$di]);
						$amt = trim($pamt_multi[$di]);
						$memo = trim($pamtmemo_multi[$di]);
						if ($part_id != "") {
							$pay_company_list[] = array(
								"part_id" => $part_id,
								"part_area" => $part_area,
								"amt" => ($amt === "" ? 0 : $amt),
								"memo" => $memo
							);
						}
					}
			   } else if ($tourcomp1) {
					$pay_company_list[] = array(
						"part_id" => $tourcomp1,
						"part_area" => $tourRegion1,
						"amt" => ($pamt === "" ? 0 : $pamt),
						"memo" => $pamtmemo
					);
			   }
			   for ($di=0; $di<count($pay_company_list); $di++) {
					$part_id = $pay_company_list[$di]["part_id"];
					$part_area = addslashes($pay_company_list[$di]["part_area"]);
					$amt = $pay_company_list[$di]["amt"];
					$memo = addslashes($pay_company_list[$di]["memo"]);
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
										'$part_area',
										'$part_id',
										'debit',
										'$brate',
										'$amt',
										'0',
										'$startDate',
										'$memo',
										'READY',
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

				$reserve_info2 = getReserveInfo($estimateCode);
				$qry6_chk = "select seq_no from payment_history 
									where
									reserveCode = '$estimateCode' && payment_status='READY' && pay_method = 'init'";
				$rst6_chk = mysql_query($qry6_chk,$dbConn);
				$has_init_payment = ($rst6_chk && mysql_num_rows($rst6_chk) > 0);

				if ($has_init_payment) {
					$qry6= "update payment_history 
										set
										payment = '$tgtotamt' , 
										rate_payment= '$tgtotamt'
										where
										reserveCode = '$estimateCode' && payment_status='READY' && pay_method = 'init'";
					$rst6 = mysql_query($qry6,$dbConn);
				} else {
					$qry6= "insert into payment_history 
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
										)";
					$rst6 = mysql_query($qry6,$dbConn);
				}

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
				$totbal2 = number_format(reserve_calc_balance_from_payments($estimateCode, $tgtotamt), 2, '.', '');
				
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
				} else if (($order_status == "DONE")) {

						$qryp = "select * from member_list where email = '$r_email'";
				        $rstp = mysql_query($qryp,$dbConn);
						$rowrcnt = mysql_num_rows($rstp);
						if (($rowrcnt > 0)) {


								$qry1 = "insert into pcash_hist 
										(
										userid, 
										gr_code, 
										r_code, 
										p_date, 
										p_cont, 
										p_cash, 
										p_yn, 
										m_usser, 
										wdate
										)
										values
										(
										'$r_email', 
										'{$reserve_info2['grand_revNo']}', 
										'$estimateCode', 
										now(), 
										'직접예약', 
										'$totbal2', 
										'n', 
										'{$user_dbinfo['userid']}', 
										now()
										);
									";

									$rst1 = mysql_query($qry1);





						}

				
					// 이메일 리스트 등록 (업체 제외)
					if (empty($rand) && !empty($r_email)) {
						$qry_ml = "SELECT seq_no FROM prt_mlist WHERE mail_addr = '$r_email'";
						$rst_ml = mysql_query($qry_ml, $dbConn);
						if ($rst_ml && mysql_num_rows($rst_ml) == 0) {
							$safe_mname = addslashes($r_name);
							$safe_tel   = addslashes($r_phone);
							$qry_ml2 = "INSERT INTO prt_mlist (m_name, mail_addr, chk_sub, tel_num, chk_send, area, wdate) VALUES ('$safe_mname', '$r_email', '0', '$safe_tel', '0', '직접', now())";
							mysql_query($qry_ml2, $dbConn);
						}
					}
}
				$tqry = "tour_type='{$reserve_info['tour_type']}',";
				
				//echo $tgtotamt."<br >".$totpay ;
				//exit;
				$qry1 ="update reserve_info 
								set
								$tqry
								r_path ='$rpath',
								s_area = '$sarea',
	  						   	stDate = '$startDate' , 
								edDate = '$endDate' , 
								p_cnt = '$pcnt1' ,
								rand_id = '$rand',
								book_pri = '$r_name' , 
								book_phone = '$r_phone' , 
								book_email = '$r_email' , 
								p_name = '".addslashes($pname)."', 
								c_code = '$dismemo' , 
								progress = '".addslashes($_POST['pmemo'])."' , 
								c_progress = '".addslashes($cmemo)."' ,  
								sprogress = '".addslashes($sendmemo)."' ,
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
								hopt = '$hopt' ,
								vopt = '$vopt' ,
								wdate = now()
								
								where
								reserveCode = '$estimateCode' ";
				//print_r($_POST);
				//exit;
				$rst1 = mysql_query($qry1,$dbConn);
			    // tour_car는 car_assign_m.php에서 관리 (reseq 컬럼 미존재로 제거)
				$qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$pickcode' && parent = 'SUB' && dis_code='pick'";
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
									r_path,
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
									sprogress,
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
									'0',
									'{$reserve_info2['grand_revNo']}', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$rpath',
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
									'".addslashes($sendmemo)."' ,
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
									'{$reserve_info2['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);

					

			   }
			   $qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$sendcode' && parent = 'SUB' && dis_code='send'";
			   $rstd = mysql_query($qryd,$dbConn);
			   //echo $qryd ;
			   //exit;
			   if ($toursend != "") {
				    
				    $prosend = getProductMaster($toursend);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									r_path,
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
									sprogress,
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
									'0',
									'{$reserve_info2['grand_revNo']}', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$rpath',
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
									'".addslashes($sendmemo)."' ,
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
									'{$reserve_info2['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);





			   }
				/////////////////////////////////////////////////////////////////
			    $qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$pickcode2' && parent = 'SUB' && dis_code='pick2'";
				//echo $qryd;
				///exit;
				$rstd = mysql_query($qryd,$dbConn);
				if ($tourpick3 != "") {
					
				    $propic = getProductMaster($tourpick3);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									r_path,
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
									sprogress,
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
									'{$reserve_info2['grand_revNo']}', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$rpath',
									'$sarea',
									'$ttype', 
									'$tourpick3', 
									'".addslashes($propic['p_name'])."', 
									'', 
									now(), 
									'$arrivalDate2', 
									'$arrivalDate2', 
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
									'".addslashes($sendmemo)."' ,
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
									'{$reserve_info2['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);
					
			    }
			   $qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode' &&  p_code='$sendcode2' && parent = 'SUB' && dis_code='send2'";
			   $rstd = mysql_query($qryd,$dbConn);
			   if ($toursend3 != "") {
				    
				    $prosend = getProductMaster($toursend3);
					$qry1 ="insert into reserve_info 
									(
									grandNum,
									grand_revNo, 
									reserveNum, 
									reserveCode, 
									parent,
									r_path,
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
									sprogress,
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
									'{$reserve_info2['grand_revNo']}', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$rpath',
									'$sarea',
									'$ttype', 
									'$toursend3', 
									'".addslashes($prosend['p_name'])."', 
									'', 
									now(), 
									'$departureDate2', 
									'$departureDate2', 
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
									'".addslashes($sendmemo)."' ,
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
									'{$reserve_info2['userid']}', 
									'$paymemo', 
									now()
									)";
			   
					$rst1 = mysql_query($qry1,$dbConn);





			   }












				//////////////////////////////////////////////////////////////////////////
			   //STOP AIR
			   $qryd = "delete from reserve_airline_rstop 
										where
										reserveCode = '$estimateCode'";
			   $rstd = mysql_query($qryd,$dbConn);
			   for($i=0; $i<count((array)$stop_starair); $i++)
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
				$rstd = mysql_query($qryd,$dbConn);
			    $traveler_seq = 0;
			    for($i=0; $i<count((array)$t_name); $i++)
                { 
				   $traveler_nm = isset($t_name[$i]) ? trim($t_name[$i]) : "";
				   $traveler_enm = isset($t_ename[$i]) ? trim($t_ename[$i]) : "";
				   $traveler_phone = isset($t_phone[$i]) ? trim($t_phone[$i]) : "";
				   $traveler_email = isset($t_email[$i]) ? trim($t_email[$i]) : "";
				   $traveler_birth = isset($t_birth[$i]) ? trim($t_birth[$i]) : "";
				   $traveler_room = isset($room_num[$i]) ? trim($room_num[$i]) : "";
				   $pass_num = isset($t_passnum[$i]) ? trim($t_passnum[$i]) : "";
				   $pass_date = isset($t_pass[$i]) ? trim($t_pass[$i]) : "";
				   $traveler_memo = isset($tmemo[$i]) ? trim($tmemo[$i]) : "";
				   $sex_type = isset($sexType[$i]) ? trim($sexType[$i]) : "";
				   $room_type = isset($pickRoomType1[$i]) ? trim($pickRoomType1[$i]) : "";
				   $pick_type = isset($pickPriceType1[$i]) ? trim($pickPriceType1[$i]) : "";
				   $sale_price = isset($unitPrice[$i]) ? str_replace(",", "", trim($unitPrice[$i])) : "0";
				   $pick_area = isset($pickuploc[$i]) ? trim($pickuploc[$i]) : "";
				   $add_pay = isset($addamt[$i]) ? str_replace(",", "", trim($addamt[$i])) : "0";
				   $dis_pay = isset($disamt[$i]) ? str_replace(",", "", trim($disamt[$i])) : "0";
				   $last_pay = isset($lasttamt[$i]) ? str_replace(",", "", trim($lasttamt[$i])) : "0";

				   if (
						$traveler_nm === "" &&
						$traveler_enm === "" &&
						$traveler_phone === "" &&
						$traveler_email === "" &&
						$traveler_birth === "" &&
						$traveler_room === "" &&
						$pass_num === "" &&
						$pass_date === ""
				   ) {
						continue;
				   }

				   if ($sale_price === "") { $sale_price = "0"; }
				   if ($add_pay === "") { $add_pay = "0"; }
				   if ($dis_pay === "") { $dis_pay = "0"; }
				   if ($last_pay === "") { $last_pay = "0"; }

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
									'{$reserve_info2['grand_revNo']}', 
									'$estimateCode',
									'".addslashes($pass_num)."',
									'$pass_date',
									'".addslashes($traveler_memo)."',
									'".addslashes($traveler_nm)."', 
									'".addslashes($traveler_enm)."',
									'".addslashes($traveler_phone)."', 
									'".addslashes($traveler_email)."',
									'$traveler_birth',
									'$traveler_room',
									'$traveler_seq', 
									'$sex_type', 
									'$room_type',
									'$pick_type',
									'$sale_price', 
									'".addslashes($pick_area)."', 
									'$add_pay', 
									'$dis_pay', 
									'$last_pay', 
									now()
									)";
				   $rst2 = mysql_query($qry2,$dbConn);
				   $traveler_seq++;
			    }

			    //단일투어 정보
				$qryd = "delete from reserve_info 
										where
										reserveCode = '$estimateCode'  && parent = 'SUB' && p_code not like  '%PICKUP%' && p_code not like  '%SENDING%'";
				//echo $qryd;
				//exit;
				$rstd = mysql_query($qryd,$dbConn);
				for($j=0; $j<count((array)$singleDayTourStartDate); $j++)
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
									r_path,
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
									sprogress,
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
									'{$reserve_info2['grand_revNo']}', 
									'$estimateNum', 
									'$estimateCode', 
									'SUB',
									'$rpath',
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
									'".addslashes($sendmemo)."' ,
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
									'$brate', 
									'$pricet',
									'$ttamt', 
									'$ttotdis', 
									'$ttotaddamt', 
									'$tgtotamt', 
									'$tbalamt', 
									'$paystatus', 
									'$order_status', 
									'".$reserve_info2['userid']."', 
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
			   $collect_company_list = array();
			   if (is_array($tourcomp_multi)) {
					for ($ci=0; $ci<count($tourcomp_multi); $ci++) {
						$part_id = trim($tourcomp_multi[$ci]);
						$part_area = trim($tourRegion_multi[$ci]);
						$amt = trim($ramt_multi[$ci]);
						$memo = trim($ramtmemo_multi[$ci]);
						if ($part_id != "") {
							$collect_company_list[] = array(
								"part_id" => $part_id,
								"part_area" => $part_area,
								"amt" => ($amt === "" ? 0 : $amt),
								"memo" => $memo
							);
						}
					}
			   } else if ($tourcomp != "") {
					$collect_company_list[] = array(
						"part_id" => $tourcomp,
						"part_area" => $tourRegion,
						"amt" => ($ramt === "" ? 0 : $ramt),
						"memo" => $ramtmemo
					);
			   }

			   $pay_company_list = array();
			   if (is_array($tourcomp1_multi)) {
					for ($di=0; $di<count($tourcomp1_multi); $di++) {
						$part_id = trim($tourcomp1_multi[$di]);
						$part_area = trim($tourRegion1_multi[$di]);
						$amt = trim($pamt_multi[$di]);
						$memo = trim($pamtmemo_multi[$di]);
						if ($part_id != "") {
							$pay_company_list[] = array(
								"part_id" => $part_id,
								"part_area" => $part_area,
								"amt" => ($amt === "" ? 0 : $amt),
								"memo" => $memo
							);
						}
					}
			   } else if ($tourcomp1) {
					$pay_company_list[] = array(
						"part_id" => $tourcomp1,
						"part_area" => $tourRegion1,
						"amt" => ($pamt === "" ? 0 : $pamt),
						"memo" => $pamtmemo
					);
			   }

			   $qryc = "delete from rand_company where reserveCode = '$estimateCode' && money_type='credit' && p_memo !='항공발권'";
			   $rstc = mysql_query($qryc,$dbConn);
			   for ($ci=0; $ci<count($collect_company_list); $ci++) {
					$part_id = $collect_company_list[$ci]["part_id"];
					$part_area = addslashes($collect_company_list[$ci]["part_area"]);
					$amt = $collect_company_list[$ci]["amt"];
					$memo = addslashes($collect_company_list[$ci]["memo"]);
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
										rand_date,
										wdate
										)
										values
										(
										'$estimateCode', 
										'$part_area', 
										'$part_id', 
										'credit', 
										'$brate', 
										'$amt', 
										'$startDate', 
										'$memo',
										'READY',
										'{$user_dbinfo['userid']}', 
										'$startDate',
										now()
										);";
					$rst4 = mysql_query($qry4,$dbConn);
			   }

			   $qryc = "delete from rand_company where reserveCode = '$estimateCode' && money_type='debit' && p_memo !='항공발권'";
			   $rstc = mysql_query($qryc,$dbConn);
			   for ($di=0; $di<count($pay_company_list); $di++) {
					$part_id = $pay_company_list[$di]["part_id"];
					$part_area = addslashes($pay_company_list[$di]["part_area"]);
					$amt = $pay_company_list[$di]["amt"];
					$memo = addslashes($pay_company_list[$di]["memo"]);
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
										rand_date,
										wdate
										)
										values
										(
										'$estimateCode', 
										'$part_area', 
										'$part_id', 
										'debit', 
										'$brate', 
										'$amt', 
										'$startDate',
										'$memo',
										'READY',
										'{$user_dbinfo['userid']}', 
										'$startDate',
										now()
										);";
					$rst4 = mysql_query($qry4,$dbConn);
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
												
						
					}

			   }
			   // 기존 rand_pay(항공발권) 먼저 삭제 - rand_company 삭제 전에 JOIN으로 처리
			   /*mysql_query("DELETE rp FROM rand_pay rp
							INNER JOIN rand_company rc ON rp.seq_rand = rc.seq_no
							WHERE rc.reserveCode='$estimateCode' AND rc.p_memo='항공발권'", $dbConn);*/

			   // rand_company(항공발권) 삭제
			   $cruise_write_userid = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : $userid;
			   reserve_save_cruise_entries($estimateCode, $cruise_write_userid);
			   mysql_query("DELETE FROM rand_company WHERE reserveCode='$estimateCode' AND p_memo='항공발권'", $dbConn);
			    // rand_company(항공발권) 삭제
			   mysql_query("DELETE FROM rand_pay WHERE reserveCode='$estimateCode' AND set_memo like'%발권합계%'", $dbConn);
			   // rand_company + rand_pay 한 루프에서 처리
			   for ($di=0; $di<count($pnrnum); $di++) {
					if (!$pnrnum[$di]) continue;

					$rc_air_raw     = -($a_air_amt[$di]);
					$rc_money_type  = ($rc_air_raw < 0) ? 'credit' : 'debit';
					$rc_air_amt     = ($rc_money_type == 'debit') ? -abs($rc_air_raw) : abs($rc_air_raw);
					$rc_air_memo    = "$pnrnum[$di]:$rpnrnum[$di]-발권합계:$a_airline_amt[$di]";

					// rand_company 삽입
					mysql_query("INSERT INTO rand_company
								(reserveCode, part_area, part_id, money_type, base_rate, amt, cur_amt, tr_date, p_memo, status, settle_memo, u_id, rand_date, wdate)
								VALUES
								('$estimateCode', '', '$rand_id_air[$di]', '$rc_money_type', 'USD', '$rc_air_amt', '$rc_air_amt', '$airdate[$di]', '항공발권', 'SETTLEDONE', '$rc_air_memo', '{$user_dbinfo['userid']}', '$stdate_air[$di]', now())", $dbConn);

					$rp_seq_rand = mysql_insert_id($dbConn); // 방금 삽입된 seq_no

					// rand_pay trans_type: payment 음수=debit, 양수=credit
					$rp_payment    = $rc_air_amt;
					$rp_trans_type = ($rp_payment < 0) ? 'debit' : 'credit';

					mysql_query("INSERT INTO rand_pay
								(rand_id, reserveCode, rand_date, stDate, tr_date, tr_type, tr_bank, trans_rate, trans_type, pay_method, payment, r_payment, set_memo, seq_rand, u_id, wdate)
								VALUES
								('$rand_id_air[$di]', '$estimateCode', now(), '$stdate_air[$di]', now(), '', '', 'USD', '$rp_trans_type', '$airsys', '$rp_payment', '', '$rc_air_memo', '$rp_seq_rand', '{$user_dbinfo['userid']}', now())", $dbConn);
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
					   $zipcode = $_POST['zipcode'];
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
							  if ($credit_result[0] == "1") {
								 $pinfo = "Approved / $credit_result[4] / $credit_result[6]";
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
