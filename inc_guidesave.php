<?php
     header('Content-Type: text/html; charset=utf-8');
	 //echo $pcode."|".$Mode."11111";
	 //exit;
	 if ($mode=='save') {	
		if ($scode =='') {
		  
			$num1 = getNumguide();
			$settlecode = "S".time().$num1;
			$qry1 = "INSERT INTO guide_setmaster (
									  settle_code,
									  s_num,
									  grand_eCode,
									  sub_eCode,
									  stDate,
									  guide_etcamt,
									  status1,
									  status2,
									  status3,
									  status4,
									  status5,
									  reg_status,
									  reg_user,
									  wdate
									)
									VALUES
									  (
										'$settlecode',
										'$num1',
										'$grand_eCode',
										'$sub_eCode',
										'$stDate',
										'$tottouramt',
										'$guiderpt',
										'$opconfirm',
										'$accconfirm',
										'$teamconfirm',
										'$ceoconfirm',
										'{$user_dbinfo['userid']}',
										'DONE',
										now()
									  );";
		    $rst1 = mysql_query($qry1, $dbConn);
	 	} else  {

			$settlecode = $scode;
			$qry1="UPDATE
						  guide_setmaster
						SET
						  
						  status1 = '$guiderpt',
    					  status2 = '$opconfirm',
						  status3 = '$accconfirm',
						  status4 = '$teamconfirm',
						  status5 = '$ceoconfirm',
						  reg_status = 'DONE'
						 
						WHERE settle_code = '$settlecode';";
			$rst1 = mysql_query($qry1, $dbConn);
				
		}
		
												
		
		
		$qry1= "DELETE FROM just_credit WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);
		for($k=0; $k<count($justnm); $k++)
	    {	
			
				$qry1 = "INSERT INTO just_credit (
									  
									  settle_code,
									  tour_cname,
									  pay_amt,
									  pay_type,
									  wdate
									)
									VALUES
									  (
										
										'$settlecode',
										'$justnm[$k]',
										'$justamt[$k]',
										'$paytype[$k]',
										now()
									  );";
              // echo $qry1;
				$rst1 = mysql_query($qry1, $dbConn);
		}

        $qry1= "DELETE FROM etc_settle WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);
		for($k=0; $k<count($ecnt); $k++)
	    {	
			
				$qry1 = "INSERT INTO etc_settle (
									  
									  settle_code,
									  e_cnt,
									  e_amt,
									  e_tot,
									  wdate
									)
									VALUES
									  (
										
										'$settlecode',
										'$ecnt[$k]',
										'$etcamt[$k]',
										'$etcttamt[$k]',
										now()
									  );";
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1, $dbConn);

		}
        $qry1= "DELETE FROM guide_ticket WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		for($k=0; $k<count($nameSelect); $k++)
	    {
				$qry1="insert into guide_ticket 
										( 
										settle_code, 
										g_ticket, 
										g_cnt, 
										v_ea, 
										g_amt, 
										wdate
										)
										values
										(
										'$settlecode',
										'$nameSelect[$k]', 
										'$person[$k]', 
										'$vea[$k]', 
										'$totalAmount[$k]', 
										now()
										);";
				$rst1 = mysql_query($qry1, $dbConn);

		}
		$qry1= "DELETE FROM guide_amount WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		for($k=0; $k<count($lpcnt); $k++)
	    {	
			
				$qry1 = "INSERT INTO guide_amount (
										  
										  settle_code,
										  inputtype,
										  lp_cnt,
										  l_amt,
										  l_memo,
										  wdate
										)
										VALUES
										  (
											
											'$settlecode',
											'local',
											'$lpcnt[$k]',
											'$lamt[$k]',
											'$lmemo[$k]',
											now()
										  );";

				$rst1 = mysql_query($qry1, $dbConn);

		}
		for($k=0; $k<count($gpcnt); $k++)
	    {	
			
				$qry1 = "INSERT INTO guide_amount (
										 
										  settle_code,
										  inputtype,
										  sp_cnt,
										  s_amt,
										  g_memo,
										  wdate
										)
										VALUES
										  (
											'$settlecode',
											'support',
											'$gpcnt[$k]',
											'$gamt[$k]',
											'$gmemo[$k]',
											now()
										  );";

				$rst1 = mysql_query($qry1, $dbConn);

		}

		
		
		for($k=0; $k<count($ipcnt); $k++)
	    {	
			
				$qry1 = "INSERT INTO guide_amount (
										  
										  settle_code,
										  inputtype,
										  ip_cnt,
										  i_amt,
										  i_memo,
										  wdate
										)
										VALUES
										  (
										
											'$settlecode',
											'inbound',
											'$ipcnt[$k]',
											'$iamt[$k]',
											'$imemo[$k]',
											now()
										  );";

				$rst1 = mysql_query($qry1, $dbConn);

		}

		$qry1= "DELETE FROM comp_payout WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		for($k=0; $k<count($co_name); $k++)
	    {	
			
				$qry1 = "INSERT INTO comp_payout (
										 
										  settle_code,
										  comp_name,
										  c_cnt,
										  c_amt,
										  c_tot,
										  c_type,
										  wdate
										)
										VALUES
										  (
											
											'$settlecode',
											'$co_name[$k]',
											'$co_person[$k]',
											'$exp_amount[$k]',
											'$ct_amt[$k]',
											'$ct_type[$k]',
											now()
										  );";

				$rst1 = mysql_query($qry1, $dbConn);

		}
		$qry1= "DELETE FROM event_guide WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		for($k=0; $k<count($tip_person); $k++)
	    {	
			
				$qry1 = "INSERT INTO event_guide (
											  
											  settle_code,
											  event_date,
											  event_type,
											  event_cnt,
											  event_amt,
											  event_totamt,
											  wdate
											)
											VALUES
											  (
												
												'$settlecode',
												'$tip_date[$k]',
												'tip',
												'$tip_person[$k]',
												'$tip_exp[$k]',
												'$tip_amt[$k]',
												now()
											  );";

				$rst1 = mysql_query($qry1, $dbConn);

		}

		for($k=0; $k<count($cc_date); $k++)
	    {	
			
				$qry1 = "INSERT INTO event_guide (
											  
											  settle_code,
											  event_date,
											  event_type,
											  event_cnt,
											  event_amt,
											  event_totamt,
											  wdate
											)
											VALUES
											  (
												
												'$settlecode',
												'$cc_date[$k]',
												'camt',
												'0',
												'$cc_exp[$k]',
												'$cc_amt[$k]',
												now()
											  );";

				$rst1 = mysql_query($qry1, $dbConn);

		}

		for($k=0; $k<count($fe_date); $k++)
	    {	
			
				$qry1 = "INSERT INTO event_guide (
											  
											  settle_code,
											  event_date,
											  event_type,
											  event_cnt,
											  event_amt,
											  event_totamt,
											  wdate
											)
											VALUES
											  (
												
												'$settlecode',
												'$fe_date[$k]',
												'fee',
												'0',
												'$fe_exp[$k]',
												'$fe_amt[$k]',
												now()
											  );";

				$rst1 = mysql_query($qry1, $dbConn);

		}
		for($k=0; $k<count($me_date); $k++)
	    {	
			
				$qry1 = "INSERT INTO event_guide (
											  
											  settle_code,
											  event_date,
											  event_type,
											  event_cnt,
											  event_amt,
											  event_totamt,
											  wdate
											)
											VALUES
											  (
												
												'$settlecode',
												'$me_date[$k]',
												'mtip',
												'$me_person[$k]',
												'$me_exp[$k]',
												'$me_amt[$k]',
												now()
											  );";

				$rst1 = mysql_query($qry1, $dbConn);

		}
        $qry1= "DELETE FROM guide_date WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		for($k=0; $k<count($gu_date); $k++)
	    {	
			
				$qry1 = "INSERT INTO guide_date (
										  
										  settle_code,
										  work_seq,
										  work_time,
										  wdate
										)
										VALUES
										  (
											
											'$settlecode',
											'$gu_date[$k]',
											'$gu_time[$k]',
											now()
										  );";

				$rst1 = mysql_query($qry1, $dbConn);

		}

		$qry1= "DELETE FROM shop_opt WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		for($k=0; $k<count($shoppingSelect); $k++)
	    {	
			
				$qry1 = "
						INSERT INTO shop_opt (
						  settle_code,
						  opt_name,
						  opt_date,
					      opt_amt,
						  sale_cnt,
						  shop_income,
						  wdate
						)
						VALUES
						  (
			
							'$settlecode',
							'$shoppingSelect[$k]',
							'$saleDate[$k]',
							'$saleamount[$k]',
							'$salecnt[$k]',
							'$shoppingProfit[$k]',
							now()
						  );
						";

				$rst1 = mysql_query($qry1, $dbConn);

		}
		$qry1= "DELETE FROM guide_option WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		for($k=0; $k<count($optionName); $k++)
	    {	
			
				$qry1 = "INSERT INTO guide_option (
										
										  settle_code,
										  option_code,
										  base_set,
										  o_cnt,
										  o_price,
										  o_pricetot,
										  o_cprice,
										  o_cpricetot,
										  o_diffamt,
										  o_cprofit,
										  o_gprofit,
										  wdate
										)
										VALUES
										  (
											
											'$settlecode',
											'$optionName[$k]',
											'$assignGuideLine[$k]',
											'$optPerson[$k]',
											'$optCost[$k]',
											'$optTotalAmount[$k]',
											'$optPrice[$k]',
											'$optTotalPrice[$k]',
											'$optDiffAmount[$k]',
											'$optProfit[$k]',
											'$optGuideProfit[$k]',
											now()
										  );";


				$rst1 = mysql_query($qry1, $dbConn);

		}
		$qry1= "DELETE FROM car_settle WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		for($k=0; $k<count($cartype); $k++)
	    {	
			
				$qry1 = "INSERT INTO car_settle (
										  
										  settle_code,
										  car_day,
										  car_type,
										  comp_name,
										  driver_nm,
										  drive_time,
										  driver_tip,
										  driver_ovtip,
										  self_car,
										  park_exp,
										  fuel_exp,
										  toll_exp,
										  sub_tot,
										  wdate
										)
										VALUES
										  (
											
											'$settlecode',
											'$cday[$k]',
											'$cartype[$k]',
											'$compname[$k]',
											'$drname[$k]',
											'$drtime[$k]',
											'$drtip[$k]',
											'$drovtip[$k]',
											'$selfcar[$k]',
											'$parkexp[$k]',
											'$fuelexp[$k]',
											'$tollexp[$k]',
											'$totexp[$k]',
											now()
										  );";


				$rst1 = mysql_query($qry1, $dbConn);

		}

		$qry1= "DELETE FROM meal_settle WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		for($k=0; $k<count($rname); $k++)
	    {	
			
				$qry1 = "INSERT INTO meal_settle (
										 
										  settle_code,
										  r_name,
										  r_type,
										  g_date,
										  g_fmealtime,
										  g_tmealtime,
										  gid_fmealtime,
										  gid_tmealtime,
										  r_tipcnt,
										  r_tipamt,
										  r_mealcnt,
										  r_mealamt,
										  r_tamt,
										  paym,
										  wdate
										)
										VALUES
										  (
											
											'$settlecode',
											'$rname[$k]',
											'bf',
											'$rday[$k]',
											'$gstime[$k]',
											'$getime[$k]',
											'$gidstime[$k]',
											'$gidetime[$k]',
											'$rt_cnt[$k]',
											'$rt_amt[$k]',
											'$r_cnt[$k]',
											'$r_amt[$k]',
											'$rtot_amt[$k]',
											'$paym[$k]',
											now()
										  );";


				$rst1 = mysql_query($qry1, $dbConn);

		}
		for($k=0; $k<count($rname1); $k++)
	    {	
			
				$qry1 = "INSERT INTO meal_settle (
										 
										  settle_code,
										  r_name,
										  r_type,
										  g_date,
										  g_fmealtime,
										  g_tmealtime,
										  gid_fmealtime,
										  gid_tmealtime,
										  r_tipcnt,
										  r_tipamt,
										  r_mealcnt,
										  r_mealamt,
										  r_tamt,
										  paym,
										  wdate
										)
										VALUES
										  (
											
											'$settlecode',
											'$rname1[$k]',
											'lunch',
											'$rday1[$k]',
											'$gstime1[$k]',
											'$getime1[$k]',
											'$gidstime1[$k]',
											'$gidetime1[$k]',
											'$rt_cnt1[$k]',
											'$rt_amt1[$k]',
											'$r_cnt1[$k]',
											'$r_amt1[$k]',
											'$rtot_amt1[$k]',
											'$paym1[$k]',
											now()
										  );";


				$rst1 = mysql_query($qry1, $dbConn);

		}

		for($k=0; $k<count($rname2); $k++)
	    {	
			
				$qry1 = "INSERT INTO meal_settle (
										 
										  settle_code,
										  r_name,
										  r_type,
										  g_date,
										  g_fmealtime,
										  g_tmealtime,
										  gid_fmealtime,
										  gid_tmealtime,
										  r_tipcnt,
										  r_tipamt,
										  r_mealcnt,
										  r_mealamt,
										  r_tamt,
										  paym,
										  wdate
										)
										VALUES
										  (
											
											'$settlecode',
											'$rname2[$k]',
											'dinner',
											'$rday2[$k]',
											'$gstime2[$k]',
											'$getime2[$k]',
											'$gidstime2[$k]',
											'$gidetime2[$k]',
											'$rt_cnt2[$k]',
											'$rt_amt2[$k]',
											'$r_cnt2[$k]',
											'$r_amt2[$k]',
											'$rtot_amt2[$k]',
											'$paym2[$k]',
											now()
										  );";


				$rst1 = mysql_query($qry1, $dbConn);

		}
		$qry1= "DELETE FROM summary_guidesettle WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		$qry1 = "INSERT INTO summary_guidesettle (
									  
									  seetle_code,
									  tour_totamt,
									  tour_income,
									  tour_totexpense,
									  shopping_profit,
									  tot_profit,
									  wdate
									)
									VALUES
									  (
										
										'$settlecode',
										'$tottouramt',
										'$tour_income',
										'$tour_totexpense',
										'$shopping_profit',
										'$tot_profit',
										now()
									  );";



		$rst1 = mysql_query($qry1, $dbConn);
		if($rst1)
		{
		   Misc::jvAlert("저장했습니다.","");
		   echo "<meta http-equiv='refresh' content='0;url=./guide_settle.php?division=6&pdx=2&sub=10'>";	
		   exit;
		}
		else
		{
		   echo "저장실패! 다시시도";
		   exit;
		}
		 
	 }  else if ($mode=='delete') {
        $qry1= "DELETE FROM summary_guidesettle WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
        $qry1= "DELETE FROM meal_settle WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		$qry1= "DELETE FROM car_settle WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);
		$qry1= "DELETE FROM car_settle WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);
		$qry1= "DELETE FROM guide_option WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		$qry1= "DELETE FROM shop_opt WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		$qry1= "DELETE FROM guide_date WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		$qry1= "DELETE FROM event_guide WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		$qry1= "DELETE FROM comp_payout WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		$qry1= "DELETE FROM guide_amount WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);
		$qry1= "DELETE FROM guide_ticket WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);	
		$qry1= "DELETE FROM etc_settle WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);
		$qry1= "DELETE FROM guide_setmaster WHERE settle_code = '$settlecode'";
		$rst1 = mysql_query($qry1, $dbConn);
	 } else if ($mode=='report') {
		$settlecode = $scode;
		 $qry1="UPDATE
						  guide_setmaster
						SET
						  
						 reg_status = 'COMPLETE'
						 
						WHERE settle_code = '$settlecode';";
		$rst1 = mysql_query($qry1, $dbConn);
		if($rst1)
		{
		   Misc::jvAlert("저장했습니다.","");
		   echo "<meta http-equiv='refresh' content='0;url=./guide_settle.php?division=6&pdx=2&sub=10'>";	
		   exit;
		}
		else
		{
		   echo "저장실패! 다시시도";
		   exit;
		}

	 } else if ($mode=='creport') {
		$settlecode = $scode;
		 $qry1="UPDATE
						  guide_setmaster
						SET
						  
						 reg_status = ''
						 
						WHERE settle_code = '$settlecode';";
		$rst1 = mysql_query($qry1, $dbConn);
		if($rst1)
		{
		   Misc::jvAlert("취소했습니다.","");
		   echo "<meta http-equiv='refresh' content='0;url=./guide_settle.php?division=6&pdx=2&sub=10'>";	
		   exit;
		}
		else
		{
		   echo "저장실패! 다시시도";
		   exit;
		}

	 }