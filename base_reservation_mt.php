
<?php
    include "include/header.php";
	//include('simple_html_dom.php');
    //include "include/inc_base.php";
	//require_once 'lib/credit.php';
	header("Cache-Control:no-cache,must-revalidate");
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
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
	//echo "!111!";
	//exit;
    // 예약접수된게 있다면...
	if ($consultCode) {
		 $prodInfo = getProductMaster($pcode);
		 $consult_info = getConsultInfo($consultCode);
		 if ($prodInfo['p_type'] == 1) {
		   $pcap = "로컬상품";
		 } else if ($prodInfo['p_type'] == 2) {
			$pcap = "인바운드";
		 } else if ($prodInfo['p_type'] == 4) {
			$pcap = "인센티브";
		 } else if ($prodInfo['p_type'] == 5) {
			$pcap = "아웃바운드";
		 }
		 $weektot = array("0", "1", "2","3","4","5","6","9"); 
		 if ((strpos($prodInfo['p_week'],"9")=== false)) {
			  $startWeek_Array = explode("/",$prodInfo['p_week']);
			  $RemoveWeek = array_diff($weektot,$startWeek_Array);
			  $RemoveWeek2 = array_values($RemoveWeek);
			  $startWeek_cnt = count($startWeek_Array);
			  $RemoveWeek_cnt = count($RemoveWeek2);
			 
			  for($s=0; $s<$startWeek_cnt-1; $s++)
			  {
					if($s == $startWeek_cnt-2)
					{
						$startWeekPrint .= $startWeek_Array[$s];
					}
					else
					{
						$startWeekPrint .= $startWeek_Array[$s].",";
					}
				
			  }
		 }
		 
		 ///////////////////////////////////////

		 //$st = $startDate;
		 
		 $stop_date = $endDate;
		 $gcnt=getReserveInfoGCnt($pcode,$st);
		 if ($gcnt['tour_pcnt'] !="") {
			$prodInfo['p_cnt'] = $gcnt['tour_pcnt'];
		 } 
		 $pcnt = getReserveInfoCnt($pcode,$st);
		 $pTinfo = getTourInfo2($pcode,$st);
		 if ($pcnt['cnt'] =="") {
				$pcnt['cnt'] = 0;
		 }
		 if ($prodInfo['p_type'] == 1) {
		
			 if ($pTinfo['tour_pcnt'] =="") {
			   $bcnt = $prodInfo['p_cnt'];

			 } else {
			   $bcnt =$pTinfo['tour_pcnt'];
			   $prodInfo['p_cnt'] = $pTinfo['tour_pcnt'];
			 }
		 } else {
			$bcnt = $prodInfo['p_cnt'];
		 }
		 $acnt = $prodInfo['p_cnt'] - $pcnt['cnt'];
		 
		 if ($pcnt['cnt'] > $prodInfo['p_cnt']) {
			$revst= "WAIT";
		 } else {
			$revst= "READY";
		 }
		 ////////////////////////////////////
		 if (($prodInfo['p_vstart'] == "0000-00-00") || ($st == "")) {

			 $startfrom = "";
		 } else {
			 $startfrom = $prodInfo['p_vstart'];

		 }

		 if ($prodInfo['p_vend'] == "0000-00-00") {

			 $endto = "";
		 } else {
			 $endto = $prodInfo['p_vend'];

		 }

		 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='L'";
		 $rst1 = mysql_query($qry1);
		 $rowcnt = mysql_num_rows($rst1);
			//echo $qry1 ;
		 $LimitdatePrint = "";
		 $s = 0;
		 while($row1 = mysql_fetch_assoc($rst1)){

			if($s == $rowcnt-1)
			{
				$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
			}
			else
			{
				$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
			}
				
			$s++;
					
		  }


		 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='R'";
		 $rst1 = mysql_query($qry1);
		 $rowcnt = mysql_num_rows($rst1);
			//echo $qry1 ;
		 $SetdatePrint = "";
		 $s = 0;
		 while($row1 = mysql_fetch_assoc($rst1)){

			if($s == $rowcnt-1)
			{
				$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
			}
			else
			{
				$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
			}
				
			$s++;
					
		  }
		  $pday = $prodInfo['p_day'] ;
		  
		  /*$start_date = explode("-",$st);
		  $add_date = $pday-1;

		  $stop_date  = date("Y-m-d",mktime (0,0,0,$start_date[1]  , $start_date[2]+$add_date, $start_date[0]));	
		  */
		  
		  if ($prodInfo['base_rate'] == "CAD") {
				$sign = "C$";
		  } else {
				$sign = "$";
		  }

		  if ($pricet ==1) {
				$pricead = $prodInfo['price_2adult'];

		 } else  if ($pricet ==3) {
				$pricead = $prodInfo['price_2cadult'];

		 }


	} else if(($estimateCode) && ($cp==''))
	{
			 $reserve_info = getReserveInfo($estimateCode);
			 $reserve_pick = getReservePSInfo($estimateCode,'pick');

			 $reserve_send = getReservePSInfo($estimateCode,'send');

			 $pcode = $reserve_info['p_code'];
			 $prodInfo = getProductMaster($pcode);
			 $ccomp_info=getCRandInfo($estimateCode);
			 $dcomp_info=getDRandInfo($estimateCode);
			 $Return_info = getRPaymethod($estimateCode);
			 $payamth = getPayment2($estimateCode);
			 $grestimateCode = $reserve_info['grand_revNo'];
			 $st = $reserve_info['stDate'];

			 $pcnt = getReserveInfoCnt($reserve_info['p_code'],$reserve_info['stDate']);
			 $pTinfo = getTourInfo2($pcode,$reserve_info['stDate']);
			 
			 if ($prodInfo['p_type'] == 1) {
			   $pcap = "로컬상품";
			 } else if ($prodInfo['p_type'] == 2) {
				$pcap = "인바운드";
			 } else if ($prodInfo['p_type'] == 4) {
				$pcap = "인센티브";
			 } else if ($prodInfo['p_type'] == 5) {
				$pcap = "아웃바운드";
			 }
			 
			 if ($pcnt['cnt'] =="") {
				$pcnt['cnt'] = 0;
			 }
			 if ($pTinfo['tour_pcnt'] =="") {
				 $pTinfo['tour_pcnt'] = 0;
			 }
			 if ($prodInfo['p_type'] == 1) {
			    
				if ($pTinfo['tour_pcnt'] =="") {
				   $bcnt = $prodInfo['p_cnt'];
				} else {
				   $bcnt =$pTinfo['tour_pcnt'];
				   $prodInfo['p_cnt'] = $pTinfo['tour_pcnt'];
				   
				}
			 } else {
				$bcnt = $prodInfo['p_cnt'];
			 }

			 $acnt = $prodInfo['p_cnt'] - $pcnt['cnt'];
			 
			// echo $pcnt[cnt]."|".$prodInfo[p_cnt];
			//exit;
			/*
			 if ($pcnt[cnt] > $prodInfo[p_cnt]) {
				 $revst= "WAIT";
				
			 } else {
				if ($reserve_info[rev_status]== "WAIT") {
					$reserve_info[rev_status] = "READY";
					$revst= "READY";
				} else {
				    $revst= $reserve_info[rev_status];
				}
			 }
			  */
			 $revst=$reserve_info['rev_status'];

			 
			 $weektot = array("0", "1", "2","3","4","5","6","9"); 
			 if ((strpos($prodInfo['p_week'],"9")=== false)) {
				  $startWeek_Array = explode("/",$prodInfo['p_week']);
				  $RemoveWeek = array_diff($weektot,$startWeek_Array);
				  $RemoveWeek2 = array_values($RemoveWeek);
				  $startWeek_cnt = count($startWeek_Array);
				  $RemoveWeek_cnt = count($RemoveWeek2);
				 
				  for($s=0; $s<$startWeek_cnt-1; $s++)
				  {
						if($s == $startWeek_cnt-2)
						{
							$startWeekPrint .= $startWeek_Array[$s];
						}
						else
						{
							$startWeekPrint .= $startWeek_Array[$s].",";
						}
					
				  }
			 }

			 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='L'";
			 $rst1 = mysql_query($qry1);
			 $rowcnt = mysql_num_rows($rst1);
				//echo $qry1 ;
			 $LimitdatePrint = "";
			 $s = 0;
			 while($row1 = mysql_fetch_assoc($rst1)){

				if($s == $rowcnt-1)
				{
					$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
				}
				else
				{
					$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
				}
					
				$s++;
						
			  }


			 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='R'";
			 $rst1 = mysql_query($qry1);
			 $rowcnt = mysql_num_rows($rst1);
				//echo $qry1 ;
			 $SetdatePrint = "";
			 $s = 0;
			 while($row1 = mysql_fetch_assoc($rst1)){

				if($s == $rowcnt-1)
				{
					$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
				}
				else
				{
					$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
				}
					
				$s++;
						
			  }
			  $pday = $prodInfo['p_day'] ;
			  if (($prodInfo['p_vstart'] == "0000-00-00") || ($st == "")) {

				 $startfrom = "";
			 } else {
				 $startfrom = $prodInfo['p_vstart'];

			 }

			 if ($prodInfo['p_vend'] == "0000-00-00") {

				 $endto = "";
			 } else {
				 $endto = $prodInfo['p_vend'];

			 }

			 if (($reserve_info['rev_status']== "CANCEL") && ($payamth<=0 )){
				 $reserve_info['payment_st'] = "";
				 $qry6= "update reserve_info 
									set
									payment_st = ''  
									where
									reserveCode = '$estimateCode' ";

			
		        $rst6 = mysql_query($qry6,$dbConn);
				
			 } else if (($reserve_info['rev_status']== "CANCEL") && ($payamth>0 )){
				$reserve_info['payment_st'] = "OPAY";
				$qry6= "update reserve_info 
									set
									payment_st = 'OPAY'  
									where
									reserveCode = '$estimateCode' ";

			
		        $rst6 = mysql_query($qry6,$dbConn);

			 } 
			 ////////////////
			 /*
			  if ($st !="") {
				  $start_date = explode("-",$st);
				  $add_date = $pday-1;

				  $stop_date  = date("Y-m-d",mktime (0,0,0,$start_date[1]  , $start_date[2]+$add_date, $start_date[0]));	
			  } else if ($reserve_info[edDate] ) {
				  $stop_date = $reserve_info[edDate];
				  echo "!!!";
				  exit;
				  
			  } else {
				  $stop_date ="";
			  }
			  */
			  $stop_date = $reserve_info['edDate'];
			 if ($reserve_info['base_rate'] == "CAD") {
					$sign = "C$";
			  } else {
					$sign = "U$";
			  }

			 
			$uamt1 = $sign. " ".$reserve_info['last_sale']; //판매가
			$aamt1 = $sign. " ".$reserve_info['last_add'];  //추가납부
			$damt1=  $sign. " ".$reserve_info['last_dis'];  //할인금액
			$tamt1 =  $sign." ".$reserve_info['last_total'];  //총금액
			$lastdiff = $reserve_info['last_total'] - $reserve_info['last_bal'];
			if ($lastdiff == 0) {
				$bamt1 =  $sign." ".$reserve_info['last_bal'];  //발란스

			} else if (($lastdiff < 0)) {
				$bamt1 =  "<font color=red>".$sign." ".$lastdiff."</font>";  //발란스
			} else if (($reserve_info['last_bal'] < 0)) {
				$bamt1 =  "<font color=red>".$sign." ".$reserve_info['last_bal']."</font>";  //발란스
			} else {
				$bamt1 =  $sign." ".$reserve_info['last_bal'];  //발란스
			}
			
			
			$uamt = $reserve_info['last_sale']; //판매가
			$aamt = $reserve_info['last_add'];  //추가납부
			$damt=  $reserve_info['last_dis'];  //할인금액
			$tamt =  $reserve_info['last_total'];  //총금액
			if ($lastdiff == 0) {
				$bamt =  $reserve_info['last_bal'];  //발란스

			} else if ($lastdiff < 0) {
				$bamt =  $lastdiff;  //발란스
			} else if ($reserve_info['last_bal'] < 0) {
				$bamt = $reserve_info['last_bal'];  //발란스
			} else {
				$bamt =  $reserve_info['last_bal'];  //발란스
			}
			//$bamt =  $reserve_info[last_bal];  //발란스
			//echo $tamt."|".$aamt."|".$damt."|".$ttbal;
			// exit;
	}
	if(($estimateCode) && ($cp=='1')) { 
		         $prodInfo = getProductMaster($pcode);
				 $reserve_info = getReserveInfo($estimateCode);
				 $reserve_pick = getReservePSInfo($estimateCode,'pick');

				 $reserve_send = getReservePSInfo($estimateCode,'send');

				 if ($prodInfo['p_type'] == 1) {
				   $pcap = "로컬상품";
				 } else if ($prodInfo['p_type'] == 2) {
					$pcap = "인바운드";
				 } else if ($prodInfo['p_type'] == 4) {
					$pcap = "인센티브";
				 } else if ($prodInfo['p_type'] == 5) {
					$pcap = "아웃바운드";
				 }
			 
				 $reserve_info['progress'] = "";
                 $reserve_info['c_progress'] = "";

				 $weektot = array("0", "1", "2","3","4","5","6","9"); 
				 if ((strpos($prodInfo['p_week'],"9")=== false)) {
					  $startWeek_Array = explode("/",$prodInfo['p_week']);
					  $RemoveWeek = array_diff($weektot,$startWeek_Array);
					  $RemoveWeek2 = array_values($RemoveWeek);
					  $startWeek_cnt = count($startWeek_Array);
					  $RemoveWeek_cnt = count($RemoveWeek2);
					 
					  for($s=0; $s<$startWeek_cnt-1; $s++)
					  {
							if($s == $startWeek_cnt-2)
							{
								$startWeekPrint .= $startWeek_Array[$s];
							}
							else
							{
								$startWeekPrint .= $startWeek_Array[$s].",";
							}
						
					  }
				 }
				 
				 ///////////////////////////////////////

				 //$st = $startDate;
				 
				 $stop_date = $endDate;
				 $gcnt=getReserveInfoGCnt($pcode,$st);

				 if ($gcnt['tour_pcnt'] !="") {
					$prodInfo['p_cnt'] = $gcnt['tour_pcnt'];
				 } 
				 $pcnt = getReserveInfoCnt($pcode,$st);

				 $pTinfo = getTourInfo2($pcode,$st);
				 if ($pcnt['cnt'] =="") {
						$pcnt['cnt'] = 0;
				 }
				 if ($prodInfo['p_type'] == 1) {
			    
					 if ($pTinfo['tour_pcnt'] =="") {
					   $bcnt = $prodInfo['p_cnt'];

					 } else {
					   $bcnt =$pTinfo['tour_pcnt'];
					   $prodInfo['p_cnt'] = $pTinfo['tour_pcnt'];
					 }
				 } else {
					$bcnt = $prodInfo['p_cnt'];
				 }
				 $acnt = $prodInfo['p_cnt'] - $pcnt['cnt'];
				 
				 if ($pcnt['cnt'] > $prodInfo['p_cnt']) {
					$revst= "WAIT";
				 } else {
					$revst= "READY";
				 }
				 ////////////////////////////////////
				 if (($prodInfo['p_vstart'] == "0000-00-00") || ($st == "")) {

					 $startfrom = "";
				 } else {
					 $startfrom = $prodInfo['p_vstart'];

				 }

				 if ($prodInfo['p_vend'] == "0000-00-00") {

					 $endto = "";
				 } else {
					 $endto = $prodInfo['p_vend'];

				 }

				 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='L'";
				 $rst1 = mysql_query($qry1);
				 $rowcnt = mysql_num_rows($rst1);
					//echo $qry1 ;
				 $LimitdatePrint = "";
				 $s = 0;
				 while($row1 = mysql_fetch_assoc($rst1)){

					if($s == $rowcnt-1)
					{
						$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
					else
					{
						$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
						
					$s++;
							
				  }


				 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='R'";
				 $rst1 = mysql_query($qry1);
				 $rowcnt = mysql_num_rows($rst1);
					//echo $qry1 ;
				 $SetdatePrint = "";
				 $s = 0;
				 while($row1 = mysql_fetch_assoc($rst1)){

					if($s == $rowcnt-1)
					{
						$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
					else
					{
						$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
						
					$s++;
							
				  }
				  $pday = $prodInfo['p_day'] ;
				  
				 

				
				  if ($prodInfo['base_rate'] == "CAD") {
						$sign = "C$";
				  } else {
						$sign = "$";
				  }

				  if ($pricet ==1) {
						$pricead = '';//$prodInfo[price_4adult];

				 } else  if ($pricet ==3) {
						$pricead = '';//$prodInfo[price_4cadult];

				 }
				 $estimateCode = "";
				 $reserve_info['pay_memo'] = "";
				 $reserve_info['payment_st']='READY';
				 $reserve_info['rev_status']='READY';
	} else if((!$estimateCode) && ($cp=='')) { 

				 $prodInfo = getProductMaster($pcode);
				 if ($prodInfo['p_type'] == 1) {
				   $pcap = "로컬상품";
				 } else if ($prodInfo['p_type'] == 2) {
					$pcap = "인바운드";
				 } else if ($prodInfo['p_type'] == 4) {
					$pcap = "인센티브";
				 } else if ($prodInfo['p_type'] == 5) {
					$pcap = "아웃바운드";
				 }
				 $weektot = array("0", "1", "2","3","4","5","6","9"); 
				 if ((strpos($prodInfo['p_week'],"9")=== false)) {
					  $startWeek_Array = explode("/",$prodInfo['p_week']);
					  $RemoveWeek = array_diff($weektot,$startWeek_Array);
					  $RemoveWeek2 = array_values($RemoveWeek);
					  $startWeek_cnt = count($startWeek_Array);
					  $RemoveWeek_cnt = count($RemoveWeek2);
					 
					  for($s=0; $s<$startWeek_cnt-1; $s++)
					  {
							if($s == $startWeek_cnt-2)
							{
								$startWeekPrint .= $startWeek_Array[$s];
							}
							else
							{
								$startWeekPrint .= $startWeek_Array[$s].",";
							}
						
					  }
				 }
				 
				 ///////////////////////////////////////

				 //$st = $startDate;
				 
				 $stop_date = $endDate;
				 $gcnt=getReserveInfoGCnt($pcode,$st);
				 if ($gcnt['tour_pcnt'] !="") {
					$prodInfo['p_cnt'] = $gcnt['tour_pcnt'];
				 } 
				 $pcnt = getReserveInfoCnt($pcode,$st);
				 $pTinfo = getTourInfo2($pcode,$st);
				 if ($pcnt['cnt'] =="") {
						$pcnt['cnt'] = 0;
				 }
				 if ($prodInfo['p_type'] == 1) {
			    
					 if ($pTinfo['tour_pcnt'] =="") {
					   $bcnt = $prodInfo['p_cnt'];

					 } else {
					   $bcnt =$pTinfo['tour_pcnt'];
					   $prodInfo['p_cnt'] = $pTinfo['tour_pcnt'];
					 }
				 } else {
					$bcnt = $prodInfo['p_cnt'];
				 }
				 $acnt = $prodInfo['p_cnt'] - $pcnt['cnt'];
				 
				 if ($pcnt['cnt'] > $prodInfo['p_cnt']) {
					$revst= "WAIT";
				 } else {
					$revst= "READY";
				 }
				 ////////////////////////////////////
				 if (($prodInfo['p_vstart'] == "0000-00-00") || ($st == "")) {

					 $startfrom = "";
				 } else {
					 $startfrom = $prodInfo['p_vstart'];

				 }

				 if ($prodInfo['p_vend'] == "0000-00-00") {

					 $endto = "";
				 } else {
					 $endto = $prodInfo['p_vend'];

				 }

				 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='L'";
				 $rst1 = mysql_query($qry1);
				 $rowcnt = mysql_num_rows($rst1);
					//echo $qry1 ;
				 $LimitdatePrint = "";
				 $s = 0;
				 while($row1 = mysql_fetch_assoc($rst1)){

					if($s == $rowcnt-1)
					{
						$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
					else
					{
						$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
						
					$s++;
							
				  }


				 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='R'";
				 $rst1 = mysql_query($qry1);
				 $rowcnt = mysql_num_rows($rst1);
					//echo $qry1 ;
				 $SetdatePrint = "";
				 $s = 0;
				 while($row1 = mysql_fetch_assoc($rst1)){

					if($s == $rowcnt-1)
					{
						$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
					else
					{
						$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
						
					$s++;
							
				  }
				  $pday = $prodInfo['p_day'] ;
				  
				  /*$start_date = explode("-",$st);
				  $add_date = $pday-1;

				  $stop_date  = date("Y-m-d",mktime (0,0,0,$start_date[1]  , $start_date[2]+$add_date, $start_date[0]));	
				  */
				  
				  if ($prodInfo['base_rate'] == "CAD") {
						$sign = "C$";
				  } else {
						$sign = "$";
				  }

				  if ($pricet ==1) {
						$pricead = $prodInfo['price_2adult'];

				 } else  if ($pricet ==3) {
						$pricead = $prodInfo['price_2cadult'];

				 }

				 

				 

	}
	
	include ("inc_insert_reserve1.php");

	

	
	  
?>


	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">예약관리</a></li>
					<li>예약관리</li>
					<li>예약등록</li>
				</ul>
			</div>
	
			<form action="<?= $PHP_SELF ?>?estimateCode=<?=$estimateCode?>&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&ty=<?=$ty?>&pcode=<?=$pcode?>&pricet=<?=$pricet?>" name="frmreserve" id="frmreserve" method="post" Enctype="multipart/form-data" autocomplete="false" >
				<input type="hidden" name="mode" id="mode" value="save">
				<input type="hidden" name="order_status" id="order_status" value="<?=$revst?>">
                <input type="hidden" name="grestimateCode" value="<?=$grestimateCode ?>">
				<input type="hidden" name="estimateCode" value="<?=$estimateCode?>">
				<input type="hidden" name="pcode" id="pcode" value="<?=$pcode ?>">
				<input type="hidden" name="pname" value='<?=$prodInfo['p_name']?>'>
				<input type="hidden" name="cday" id="cday" value="<?=$pday?>">
				<input type="hidden" name="tcnt" id="tcnt" value="<?=$acnt?>">
				<input type="hidden" name="ttype" id="ttype" value="<?=$ty?>">
				<input type="hidden" name="brate" id="brate" value="<?=$prodInfo['base_rate'] ?>">
				<input type="hidden" name="tccomp" id="tccomp" value="<?=$ccomp_info['part_id']?>">
				<input type="hidden" name="tdcomp" id="tdcomp" value="<?=$dcomp_info['part_id']?>">
				<input type="hidden" name="paystatus" id="paystatus" value="<?=$reserve_info['payment_st']?>">
				<input type="hidden" name="pricet" id="pricet" value="<?=$pricet?>">
				<input type="hidden" name="chkc" id="chkc" value="">
				<input type="hidden" name="payc" id="payc" value="<?=$payamth?>">
				<input type="hidden" name="pickcode" id="pickcode" value="<?=$reserve_pick['p_code']?>">
				<input type="hidden" name="sendcode" id="sendcode" value="<?=$reserve_send['p_code']?>">
				<input type="hidden" name="revdate" id="revdate" value="<?=$reserve_info['revDate']?>">
				
				<div class="row no-nav">
					<div class="col-sm-6">
						<ul class="pagination non-nav">
						
							<li <?php if (($reserve_info['rev_status']=='READY') && ($revst != 'WAIT')) { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>><span>예약접수</span></li>
							
							<li <?php if (($reserve_info['rev_status']=='DONE')) { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>><span>예약확정</span></li>
							
							<li <?php if ($reserve_info['rev_status']=='CANCEL')  { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>><span>예약취소</span></li>
						
						</ul>
					</div>
				</div>
				<?php// if ($consultCode) {print_r($consult_info);} ?>
				<div class="row no-nav">
					<div class="col-sm-6">
						<ul class="pagination non-nav">
							<li <?php if ($reserve_info['payment_st']=='READY') { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>><span>미납</span></li>
							<li <?php if ($reserve_info['payment_st']=='PPAY') { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>><span>부분완납</span></li>
							<li <?php if ($reserve_info['payment_st']=='DONE') { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>class="disabled"><span>완납</span></li>
							<li <?php if ($reserve_info['payment_st']=='OPAY') { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>class="disabled"><span>환불</span></li>
						</ul>
					</div>
					<div class="col-sm-6 text-right">
					  <?php if (($estimateCode)  && ($cp =='')) { ?>
							<button type="button" class="btn btn-xs btn-default js-rr" onClick="go_submit()">예약저장</button>
							
							<button type="button" <?php if (($reserve_info['rev_status']=='DONE') || ($reserve_info['rev_status']=='CANCEL') && ($reserve_info['rev_status']!=''))  { ?> class="btn btn-xs btn-default js-done disabled" disabled <?php } else { ?> class="btn btn-xs btn-default js-ccr" <?php } ?> onClick="go_corder()">예약확정</button>
							<button type="button" class="btn btn-xs btn-default js-can" onClick="go_cancel()">예약취소</button>
							<button type="button" class="btn btn-xs btn-default js-prn" onClick="javascript:openwin('<?=$estimateCode?>')">영수증 출력</button>
							<button type="button" class="btn btn-xs btn-default js-prn2" onClick="javascript:openwin2('<?=$estimateCode?>')">아웃바운드요청</button>
					  <?php } else { ?>
					        <button type="button" class="btn btn-xs btn-default js-rr" onClick="go_submit()">예약접수</button>
							
							<button type="button" class="btn btn-xs btn-default js-done disabled" disabled  class="btn btn-xs btn-default js-ccr" onClick="go_corder()">예약확정</button>
							<button type="button" class="btn btn-xs btn-default js-can" disabled onClick="go_cancel()">예약취소</button>
							<button type="button" class="btn btn-xs btn-default js-prn" disabled>영수증 출력</button>
							<button type="button" class="btn btn-xs btn-default js-prn2" disabled>아웃바운드요청</button>
							
					   <?php } ?>
					</div>
				</div>
				<br />
				<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base">
					<tbody>
						<tr>
							<td colspan="16" class="active text-center formHeader fullWidth">예약기본정보</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">투어분류</td>
							<td colspan="6"><?=$pcap?></td>
							
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품명</td>
							<td colspan="6"><?=$prodInfo['p_name']?></td>
							<td colspan="2" class="active text-center formHeader">상품코드</td>
							<td colspan="6"><?=$prodInfo['p_code']?></td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">통합예약번호</td>
							<td colspan="6"><?php if ($grestimateCode) { echo $grestimateCode; } else { ?>저장후에 생성<?php } ?></td>
							<td colspan="2" class="active text-center formHeader">여행기간</td>
							<td colspan="6">
								<div class="row">
									<div class="col-sm-6">
										<div class="input-group input-group-sm">
											<input type="text" name="startDate" id="startDate" class="form-control js-dateInputWithBlocks js-tourDates js-tourStartDate" aria-label="여행시작날짜" placeholder="여행시작날짜" autocomplete="off" value='<?php if ($consultCode) { echo $consult_info['start_date']; } else {  echo $reserve_info['stDate']; } ?>'>
											<span class="input-group-btn">
												<button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
											</span>
										</div>
									</div>
									<div class="col-sm-6">
										<div class="input-group input-group-sm">
											<input type="text" name="endDate" class="form-control js-dateInputWithBlocks js-tourDates js-tourEndDate" aria-label="여행종료날짜" placeholder="여행종료날짜" autocomplete="off" value='<?php if ($consultCode) { echo $consult_info['stop_date']; } else {  echo $stop_date; } ?>'>
											<span class="input-group-btn">
												<button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
											</span>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">예약번호</td>
							<td colspan="6"><?php if (($reserve_info['reserveCode']) && ($cp =='')) { echo $reserve_info['reserveCode']; } else { ?>저장후에 생성<?php } ?></td>
							<td colspan="2" class="active text-center formHeader">방갯수</td>
							<td colspan="2"><div class="row">
									<div class="col-sm-12">
									<?php if ( ($cp =='')) { ?>
									    <div class="input-group input-group-sm">
											<input type="text"  name='rcnt1' id='rcnt1' class="form-control text-right" aria-label="개" value="<?= $reserve_info['room_cnt'] ? $reserve_info['room_cnt'] : ($consultCode ? $consult_info['room_cnt'] : 0) ?>"
											/>
											<span class="input-group-addon">개</span>
										</div>
									<?php } else { ?>
										 <div class="input-group input-group-sm">
											<input type="text"  name='rcnt1' id='rcnt1' class="form-control text-right" aria-label="개" value="<?= $consultCode ? $consult_info['room_cnt'] : 0 ?>"
											/>
											<span class="input-group-addon">개</span>
										</div>
									 <?php } ?>
									</div>
								</div></td>
							<td colspan="2" class="active text-center formHeader">예약인원</td>
							<td colspan="2"><div class="text-right cntid"><?=$pcnt['cnt']?>명</div></td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">접수일</td>
							<td colspan="6"><span><?php if (($reserve_info['revDate']) && ($cp =='')) { echo $reserve_info['revDate']; } else { echo date("Y-m-d"); } ?></span></td>
							<td colspan="2" class="active text-center formHeader">여행인원</td>
							<td colspan="2">
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<input type="number" min="1" name='pcnt1' id='pcnt1' class="form-control text-right js-numTourists" aria-label="명" value="<?php if ($reserve_info['p_cnt']) {  echo $reserve_info['p_cnt']; } else if ($consultCode){ echo $consult_info['p_cnt']; } else {?>1<?php } ?>"
											<?php if (($reserve_info['p_cnt']) && ($cp =='')) { ?> readOnly <?php }?>/>
											<span class="input-group-addon">명</span>
										</div>
									</div>
								</div>
							</td>
							<td colspan="2" class="active text-center formHeader"></td>
							<td colspan="2"><div class="text-right acntid"></div></td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">예약지역선택</td>
							<td colspan="6">
								
								<select class=" form-control sarea" name="sarea" id="sarea" >
									<option value="">- 지역을 선택하세요 -
									<?=printBaseCode_first("S03",$reserve_info['s_area'])?>
								</select>
							</td>
							<td colspan="2" class="active text-center formHeader">예약경로선택</td>
							<td colspan="6">
								
								<select class=" form-control sarea" name="rpath" id="rpath" >
									<option value="">- 예약경로를 선택하세요 -
									<?=printBaseCode_first("F01",$reserve_info['r_path'])?>
								</select>
							</td>
							
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">숙박선택</td>
							<td colspan="6">
								<div class="form-group">
									<div class="col-sm-12 col-sm-offset-3">
										
										<label class="radio-inline">
											<input type="radio" name="hopt" id="hopt1" value="usa" <?php if ($reserve_info['hopt']=="usa") { ?> checked <?php }?> /> 미국측 숙박
										</label>
										
										<label class="radio-inline">
											<input type="radio" name="hopt" id="hopt2" value="can" <?php if ($reserve_info['hopt']=="can") { ?> checked <?php }?>/> 캐나다측 숙박
										</label>

										<label class="radio-inline">
											<input type="radio" name="hopt" id="hopt3" value="n" <?php if ($reserve_info['hopt']=="n") { ?> checked <?php }?> /> 없음
										</label>
									</div>
								</div>
								
									
							</td>
							<td colspan="2" class="active text-center formHeader">뷰선택</td>
							<td colspan="6">
								<div class="form-group">
									<div class="col-sm-12 col-sm-offset-3">
										
										<label class="radio-inline">
											<input type="radio" name="vopt" id="vopt1" value="fview" <?php if ($reserve_info['vopt']=="fview") { ?> checked <?php }?> /> 폴뷰
										</label>
										
										<label class="radio-inline">
											<input type="radio" name="vopt" id="vopt2" ="nview" <?php if ($reserve_info['vopt']=="nview") { ?> checked <?php }?> /> 논폴뷰
										</label>

										<label class="radio-inline">
											<input type="radio" name="vopt" id="vopt3" value="n" <?php if ($reserve_info['vopt']=="n") { ?> checked <?php }?> /> 없음
										</label>
									</div>
								</div>
							</td>
							
						</tr>
					</tbody>
				</table>
				<table id="example" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
					<?php 
						  if (($pricet == '3')) {
					?>
						<tr>
							<td colspan="5" class="active text-center formHeader fullWidth">예약상세정보 &nbsp;<button type="button" class="btn btn-default btn-sm js-addTraveler1" ><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
						</tr>
					
						<tr class="innerTable1">
							<td  class="active text-center formHeader">업체정보</td>
							
							<td width='20%'>
								<!-- <input type="text" class="form-control" placeholder="이름"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-md">
											
											<select class="rand" name="rand" id="rand" >
												<option value="">- 업체를 선택하세요 -
												<?=printCompanySelect($reserve_info['rand_id'])?>
											</select>
										</div>
									</div>
								</div>
							</td>
							<td width='20%'>
								<!-- <input type="text" class="form-control" placeholder="이름"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">담당자</span>
											<input type="text" class="form-control" aria-label="이름" name="r_name" id="r_name" value="<?=$reserve_info['book_pri']?>"/>
										</div>
									</div>
								</div>
							</td>
							
							<td width='20%'>
								<!-- <input type="text" class="form-control" placeholder="전화번호"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<span class="input-group-addon"><span class="glyphicon glyphicon-phone-alt" aria-hidden="true"></span></span>
											<input type="text" class="form-control" aria-label="전화번호" name="r_phone" id="r_phone" value="<?=$reserve_info['book_phone']?>"/>
										</div>
									</div>
								</div>
							</td>
							<td >
								<!-- <input type="text" class="form-control" placeholder="이메일"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">이메일</span>
											<input type="text" class="form-control" aria-label="이메일" name="r_email" id="r_email" value="<?=$reserve_info['book_email']?>"/>
										</div>
									</div>
								</div>
							</td>
					    </tr>
					<?php } else { ?>
						<tr>
							<td colspan="4" class="active text-center formHeader fullWidth">예약상세정보 &nbsp;<button type="button" class="btn btn-default btn-sm js-addTraveler1" ><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
						</tr>
						<tr class="innerTable1">
							<td  class="active text-center formHeader">예약자정보</td>
							<td width='20%'>
								<!-- <input type="text" class="form-control" placeholder="이름"> -->
								<div class="row">
									<div class="col-sm-9">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">이름</span>
											<input type="text" class="form-control" aria-label="이름" name="r_name" id="r_name" value="<?php if ($consultCode) { echo $consult_info['member_name']; } else {  echo $reserve_info['book_pri']; } ?>"/>
										</div>
									</div>
								</div>
							</td>
							<td width='20%'>
								<!-- <input type="text" class="form-control" placeholder="전화번호"> -->
								<div class="row">
									<div class="col-sm-9">
										<div class="input-group input-group-sm">
											<span class="input-group-addon"><span class="glyphicon glyphicon-phone-alt" aria-hidden="true"></span></span>
											<input type="text" class="form-control" aria-label="전화번호" name="r_phone" id="r_phone" value="<?php if ($consultCode) { echo $consult_info['member_phone']; } else {  echo $reserve_info['book_phone']; } ?>"/>
										</div>
									</div>
								</div>
							</td>
							<td >
								<!-- <input type="text" class="form-control" placeholder="이메일"> -->
								<div class="row">
									<div class="col-sm-5">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">이메일</span>
											<input type="text" class="form-control" aria-label="이메일" name="r_email" id="r_email" value="<?php if ($consultCode) { echo $consult_info['member_email']; } else {  echo $reserve_info['book_email']; } ?>"/>
										</div>
									</div>
								</div>
							</td>
					    </tr>

					<?php }  ?>
				</table>
				<table id="example1" class="table table-bordered table-condensed gridSixteen reserveTable formDetail innerTable">
						<thead>
							<tr>
							    <th width=2%>#RM</th>
								<th width=7%>이름/<br>영문이름</th>
								<th width=8%>전화번호/<br>여권번호</th>
								<th width=8%>이메일/<br>여권만료일</th>
								<th width=10%>생년월일/메모</th>
								<th width=5%>성별</th>
								
								<th width=8%>판매가</th>
								<th width=5%>타입</th>
								<th width=7%>객실타입</th>
								<th width=8%>픽업지역</th>
								<th width=5%>추가납부</th>
								<th width=5%>할인</th>
								<th width=5%>총액</th>
								<th width=7%>Action</th>
							</tr>
						</thead>
						 
						<?php
							$qryt = "select * from reserve_traveler where reserveCode = '{$reserve_info['reserveCode']}' order by traveler_room,seqint asc";
							
							$rstt = mysql_query($qryt);
							$cnt = mysql_num_rows($rstt);
							if ($cnt > 0) {
								$num = 0;
							    while($rowt = mysql_fetch_assoc($rstt)):
								  if (($num == 0) && ($cp =='')) {
									    
						?>
									
											  <tbody >
												<tr class='innertr'>
														<td >
															
															<input type="text" class="form-control" aria-label="ROOM" name="room_num[]" id="room_num" autocomplete=off
															value="<?=$rowt['traveler_room']?>"/>
																	
														</td>
														<td >
															
														    <input type="text" class="form-control" aria-label="이름" name="t_name[]" id="t_name" autocomplete=off placeholder="이름" value="<?=$rowt['traveler_nm']?>"/>
															<input type="text" class="form-control" aria-label="영문이름" name="t_ename[]" id="t_name" autocomplete=off placeholder="영문이름" value="<?=$rowt['traveler_enm']?>"/>
																	
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="전화번호" name="t_phone[]" id="t_phone" autocomplete="off"
															value="<?=$rowt['traveler_phone']?>"/>
															<input type="text" class="form-control" aria-label="여권번호" name="t_passnum[]" id="t_name" autocomplete=off placeholder="여권번호" value="<?=$rowt['pass_num']?>"/>		
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="이메일" name="t_email[]" id="t_email" autocomplete="off"
															value="<?=$rowt['traveler_email']?>"/>
															<input type="date" class="form-control pass" aria-label="여권만료" name="t_pass[]" id="t_pass" placeholder="여권만료" value="<?=$rowt['pass_date']?>"/>		
														</td>
														<td >
															
															<input type="date" class="form-control" aria-label="생년월일" name="t_birth[]" id="t_birth" value="<?=$rowt['traveler_birth']?>"/>
															<textarea class="form-control" rows="2" name="tmemo[]" placeholder="비고"><?=$rowt['e_memo']?></textarea>		
														
											            </td>
													
													
													    <td>
															<select class="form-control js-sexType" name="sexType[]" id="sexType">
																<option <?php if ($rowt['sextype'] == "man") { ?> selected <?php } ?> value="" >- 성별 -</option>
																<option <?php if ($rowt['sextype'] == "man") { ?> selected <?php } ?> value="man">남자</option>
																<option <?php if ($rowt['sextype'] == "female") { ?> selected <?php } ?> value="female">여자</option>
																<option <?php if ($rowt['sextype'] == "mfemale") { ?> selected <?php } ?> value="mfemale">혼성</option>
																
															</select>							
														</td>
														
														<td >
															
															<div class="row">
																<div class="col-sm-12">
																	<div class="input-group input-group-sm">
																		<input type="text" name="unitPrice[]" class="form-control text-right js-pickPrice" aria-label="판매가" placeholder="판매가" value="<?=$rowt['sale_price']?>" />
																		<span class="input-group-btn">
																			<button class="btn btn-default dropdown-toggle tourPricePick-dropdown js-tourPricePick" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></button>
																				<ul class="dropdown-menu js-tourPriceDropdown">
																					
																				</ul>
																		</span>
																		</div>
																	</div>
															  </div>
													    </td>
														<td >
														    <input type="hidden" name="pickPriceType[]" class="form-control js-pickPriceType" id="pickPriceType" value="<?=$rowt['pick_type']?>">
															<select class="form-control js-pickPriceType1" name="pickPriceType1[]" id="pickPriceType1" >
																<option <?php if ($rowt['pick_type'] == "") { ?> selected <?php } ?> value="">가격타입</option>
																<option <?php if ($rowt['pick_type'] == "G") { ?> selected <?php } ?> value="G">성인</option>
																<option <?php if ($rowt['pick_type'] == "I") { ?> selected <?php } ?> value="I">어린이</option>
																
															</select>							
														</td>
														<td >
														    <input type="hidden" class="form-control js-pickRoomType" name="pickRoomType[]" id="pickRoomType" value="<?=$rowt['room_type']?>">
															<select class="form-control js-pickRoomType1" name="pickRoomType1[]" id="pickRoomType1" >
																<option value="" selected >- 룸타입 -</option>
																<option <?php if ($rowt['room_type'] == "0r1p") { ?> selected <?php } ?> value="0r1p">당일</option>
																<option <?php if ($rowt['room_type'] == "1r1p") { ?> selected <?php } ?> value="1r1p">1인1실</option>
																<option <?php if ($rowt['room_type'] == "1r2p") { ?> selected <?php } ?> value="1r2p">2인1실</option>
																<option <?php if ($rowt['room_type'] == "1r3p") { ?> selected <?php } ?> value="1r3p">3인1실</option>
																<option <?php if ($rowt['room_type'] == "1r4p") { ?> selected <?php } ?> value="1r4p">4인1실</option>
																<option <?php if ($rowt['room_type'] == "1r5p") { ?> selected <?php } ?> value="1r5p">5인1실</option>
															</select>							
														</td>
														
														<td >
															<select class="form-control" name="pickuploc[]" id="pickuploc">
																<option value="">- 탑승지 선택하세요 -
																<?= printPickSelect($pcode ,$rowt['pick_area'],$prodInfo['p_type'])?>
															</select>							
														</td>
														<td >
															<!-- <input type="text" class="form-control" placeholder="추가납부"> -->
															
															<input type="text" name="addamt[]" id="addamt" class="form-control text-right js-additionalCharge" aria-label="추가납부" value="<?=$rowt['add_pay']?>"/>
																	
														</td>
														<td >
															
															<input type="text" name="disamt[]" id="disamt" class="form-control text-right js-discount" aria-label="할인" value="<?=$rowt['dis_pay']?>"/>
																	
														</td>
														<td >
															
															<input type="text" name="lasttamt[]" id="lasttamt" class="form-control text-right js-totalPerPerson" aria-label="총액" value="<?=$rowt['last_pay']?>" readonly/>
																	
														</td>
														<td >
															<div class="row">
																<div class="col-sm-6">
																	<button type="button" class="btn btn-primary btn-sm js-resetTraveler">RESET</button>
																</div>
																<div class="col-sm-5">
																	<button type="button" class="btn btn-primary hidden  js-removeTraveler" >DEL</button>
																</div>
															</div>
														</td>
												</tr>
												
							<?php 
									
							        } else if (($num == 0) && ($cp =='1')) {
						     ?>
											<tr class='innertr'>
														<td >
															
															<input type="text" class="form-control" aria-label="ROOM" name="room_num[]" id="room_num" autocomplete=off value="<?=$rowt['traveler_room']?>"/>
																	
														</td>
														<td >
															
														    <input type="text" class="form-control" aria-label="이름" name="t_name[]" id="t_name" autocomplete=off placeholder="이름" value="<?=$rowt['traveler_nm']?>"/>
															<input type="text" class="form-control" aria-label="영문이름" name="t_ename[]" id="t_name" autocomplete=off placeholder="영문이름" value="<?=$rowt['traveler_enm']?>"/>
																	
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="전화번호" name="t_phone[]" id="t_phone" autocomplete="off"
															value="<?=$rowt['traveler_phone']?>"/>
															<input type="text" class="form-control" aria-label="여권번호" name="t_passnum[]" id="t_name" autocomplete=off placeholder="여권번호" value="<?=$rowt['pass_num']?>"/>		
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="이메일" name="t_email[]" id="t_email" autocomplete="off"
															value="<?=$rowt['traveler_email']?>"/>
															<input type="date" class="form-control pass" aria-label="여권만료" name="t_pass[]" id="t_pass" placeholder="여권만료" value="<?=$rowt['pass_date']?>"/>		
														</td>
														<td >
															
															<input type="date" class="form-control" aria-label="생년월일" name="t_birth[]" id="t_birth" value="<?=$rowt['traveler_birth']?>"/>
															<textarea class="form-control" rows="2" name="tmemo[]" placeholder="비고"><?=$rowt['e_memo']?></textarea>		
														
											            </td>
													
													
													    <td>
															<select class="form-control js-sexType" name="sexType[]" id="sexType">
																<option <?php if ($rowt['sextype'] == "man") { ?> selected <?php } ?> value="" >- 성별 -</option>
																<option <?php if ($rowt['sextype'] == "man") { ?> selected <?php } ?> value="man">남자</option>
																<option <?php if ($rowt['sextype'] == "female") { ?> selected <?php } ?> value="female">여자</option>
																<option <?php if ($rowt['sextype'] == "mfemale") { ?> selected <?php } ?> value="mfemale">혼성</option>
																
															</select>							
														</td>
														
														<td >
															
															<div class="row">
																<div class="col-sm-12">
																	<div class="input-group input-group-sm">
																		<input type="text" name="unitPrice[]" class="form-control text-right js-pickPrice" aria-label="판매가" placeholder="판매가" value="" />
																		<span class="input-group-btn">
																			<button class="btn btn-default dropdown-toggle tourPricePick-dropdown js-tourPricePick" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></button>
																				<ul class="dropdown-menu js-tourPriceDropdown">
																					
																				</ul>
																		</span>
																		</div>
																	</div>
															  </div>
													    </td>
														<td >
														    <input type="hidden" name="pickPriceType[]" class="form-control js-pickPriceType" id="pickPriceType" value="">
															<select class="form-control js-pickPriceType1" name="pickPriceType1[]" id="pickPriceType1" >
																<option selected value="">가격타입</option>
																<option value="G">성인</option>
																<option value="I">어린이</option>
																
															</select>							
														</td>
														<td >
														    <input type="hidden" class="form-control js-pickRoomType" name="pickRoomType[]" id="pickRoomType" value="1r2p">
															<select class="form-control js-pickRoomType1" name="pickRoomType1[]" id="pickRoomType1" >
																<option value="" selected >- 룸타입 -</option>
																<option  value="0r1p">당일</option>
																<option  value="1r1p">1인1실</option>
																<option selected value="1r2p">2인1실</option>
																<option  value="1r3p">3인1실</option>
																<option  value="1r4p">4인1실</option>
																<option  value="1r5p">5인1실</option>
															</select>							
														</td>
														
														<td >
															<select class="form-control" name="pickuploc[]" id="pickuploc">
																<option value="">- 탑승지 선택하세요 -
																<?= printPickSelect($pcode ,'',$prodInfo['p_type'])?>
															</select>							
														</td>
														<td >
															<!-- <input type="text" class="form-control" placeholder="추가납부"> -->
															
															<input type="text" name="addamt[]" id="addamt" class="form-control text-right js-additionalCharge" aria-label="추가납부" value="0"/>
																	
														</td>
														<td >
															
															<input type="text" name="disamt[]" id="disamt" class="form-control text-right js-discount" aria-label="할인" value="0"/>
																	
														</td>
														<td >
															
															<input type="text" name="lasttamt[]" id="lasttamt" class="form-control text-right js-totalPerPerson" aria-label="총액" value="0" readonly/>
																	
														</td>
														<td >
															<div class="row">
																<div class="col-sm-6">
																	<button type="button" class="btn btn-primary btn-sm js-resetTraveler">RESET</button>
																</div>
																<div class="col-sm-5">
																	<button type="button" class="btn btn-primary btn-sm js-removeTraveler" >DELETE</button>
																</div>
															</div>
														</td>
										   </tr>
							 <?php 
									
							        } else if (($num > 0) && ($cp =='1')) {
						     ?>
										   <tr class='innertr'>
														<td >
															
															<input type="text" class="form-control" aria-label="ROOM" name="room_num[]" id="room_num" autocomplete=off value="<?=$rowt['traveler_room']?>"/>
																	
														</td>
														<td >
															
														    <input type="text" class="form-control" aria-label="이름" name="t_name[]" id="t_name" autocomplete=off placeholder="이름" value="<?=$rowt['traveler_nm']?>"/>
															<input type="text" class="form-control" aria-label="영문이름" name="t_ename[]" id="t_name" autocomplete=off placeholder="영문이름" value="<?=$rowt['traveler_enm']?>"/>
																	
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="전화번호" name="t_phone[]" id="t_phone" autocomplete="off"
															value="<?=$rowt['traveler_phone']?>"/>
															<input type="text" class="form-control" aria-label="여권번호" name="t_passnum[]" id="t_name" autocomplete=off placeholder="여권번호" value="<?=$rowt['pass_num']?>"/>		
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="이메일" name="t_email[]" id="t_email" autocomplete="off"
															value="<?=$rowt['traveler_email']?>"/>
															<input type="date" class="form-control pass" aria-label="여권만료" name="t_pass[]" id="t_pass" placeholder="여권만료" value="<?=$rowt['pass_date']?>"/>		
														</td>
														<td >
															
															<input type="date" class="form-control" aria-label="생년월일" name="t_birth[]" id="t_birth" value="<?=$rowt['traveler_birth']?>"/>
															<textarea class="form-control" rows="2" name="tmemo[]" placeholder="비고"><?=$rowt['e_memo']?></textarea>		
														
											            </td>
													
													
													    <td>
															<select class="form-control js-sexType" name="sexType[]" id="sexType">
																<option <?php if ($rowt['sextype'] == "man") { ?> selected <?php } ?> value="" >- 성별 -</option>
																<option <?php if ($rowt['sextype'] == "man") { ?> selected <?php } ?> value="man">남자</option>
																<option <?php if ($rowt['sextype'] == "female") { ?> selected <?php } ?> value="female">여자</option>
																<option <?php if ($rowt['sextype'] == "mfemale") { ?> selected <?php } ?> value="mfemale">혼성</option>
																
															</select>							
														</td>
														
														<td >
															
															<div class="row">
																<div class="col-sm-12">
																	<div class="input-group input-group-sm">
																		<input type="text" name="unitPrice[]" class="form-control text-right js-pickPrice" aria-label="판매가" placeholder="판매가" value="" />
																		<span class="input-group-btn">
																			<button class="btn btn-default dropdown-toggle tourPricePick-dropdown js-tourPricePick" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></button>
																				<ul class="dropdown-menu js-tourPriceDropdown">
																					
																				</ul>
																		</span>
																		</div>
																	</div>
															  </div>
													    </td>
														<td >
														    <input type="hidden" name="pickPriceType[]" class="form-control js-pickPriceType" id="pickPriceType" value="">
															<select class="form-control js-pickPriceType1" name="pickPriceType1[]" id="pickPriceType1" >
																<option selected value="">가격타입</option>
																<option value="G">성인</option>
																<option value="I">어린이</option>
																
															</select>							
														</td>
														<td >
														    <input type="hidden" class="form-control js-pickRoomType" name="pickRoomType[]" id="pickRoomType" value="1r2p">
															<select class="form-control js-pickRoomType1" name="pickRoomType1[]" id="pickRoomType1" >
																<option value="" selected >- 룸타입 -</option>
																<option  value="0r1p">당일</option>
																<option  value="1r1p">1인1실</option>
																<option selected value="1r2p">2인1실</option>
																<option  value="1r3p">3인1실</option>
																<option  value="1r4p">4인1실</option>
																<option  value="1r5p">5인1실</option>
															</select>							
														</td>
														
														<td >
															<select class="form-control" name="pickuploc[]" id="pickuploc">
																<option value="">- 탑승지 선택하세요 -
																<?= printPickSelect($pcode ,'',$prodInfo['p_type'])?>
															</select>							
														</td>
														<td >
															<!-- <input type="text" class="form-control" placeholder="추가납부"> -->
															
															<input type="text" name="addamt[]" id="addamt" class="form-control text-right js-additionalCharge" aria-label="추가납부" value="0"/>
																	
														</td>
														<td >
															
															<input type="text" name="disamt[]" id="disamt" class="form-control text-right js-discount" aria-label="할인" value="0"/>
																	
														</td>
														<td >
															
															<input type="text" name="lasttamt[]" id="lasttamt" class="form-control text-right js-totalPerPerson" aria-label="총액" value="0" readonly/>
																	
														</td>
														<td >
															<div class="row">
																<div class="col-sm-6">
																	<button type="button" class="btn btn-primary btn-sm js-resetTraveler">RESET</button>
																</div>
																<div class="col-sm-5">
																	<button type="button" class="btn btn-primary btn-sm js-removeTraveler" >DELETE</button>
																</div>
															</div>
														</td>
										   </tr>       
						    <?php 
									
							        } else {
						      ?>
								    
												
												<tr class='innertr'>
														<td >
															
															<input type="text" class="form-control" aria-label="ROOM" name="room_num[]" id="room_num" autocomplete=off value="<?=$rowt['traveler_room']?>"/>
																	
														</td>
														<td >
															
														    <input type="text" class="form-control" aria-label="이름" name="t_name[]" id="t_name" autocomplete=off placeholder="이름" value="<?=$rowt['traveler_nm']?>"/>
															<input type="text" class="form-control" aria-label="영문이름" name="t_ename[]" id="t_name" autocomplete=off placeholder="영문이름" value="<?=$rowt['traveler_enm']?>"/>
																	
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="전화번호" name="t_phone[]" id="t_phone" autocomplete="off"
															value="<?=$rowt['traveler_phone']?>"/>
															<input type="text" class="form-control" aria-label="여권번호" name="t_passnum[]" id="t_name" autocomplete=off placeholder="여권번호" value="<?=$rowt['pass_num']?>"/>		
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="이메일" name="t_email[]" id="t_email" autocomplete="off"
															value="<?=$rowt['traveler_email']?>"/>
															<input type="date" class="form-control pass" aria-label="여권만료" name="t_pass[]" id="t_pass" placeholder="여권만료" value="<?=$rowt['pass_date']?>"/>		
														</td>
														<td >
															
															<input type="date" class="form-control" aria-label="생년월일" name="t_birth[]" id="t_birth" value="<?=$rowt['traveler_birth']?>"/>
															<textarea class="form-control" rows="2" name="tmemo[]" placeholder="비고"><?=$rowt['e_memo']?></textarea>		
														
											            </td>
													
													
													    <td>
															<select class="form-control js-sexType" name="sexType[]" id="sexType">
																<option <?php if ($rowt['sextype'] == "man") { ?> selected <?php } ?> value="" >- 성별 -</option>
																<option <?php if ($rowt['sextype'] == "man") { ?> selected <?php } ?> value="man">남자</option>
																<option <?php if ($rowt['sextype'] == "female") { ?> selected <?php } ?> value="female">여자</option>
																<option <?php if ($rowt['sextype'] == "mfemale") { ?> selected <?php } ?> value="mfemale">혼성</option>
																
															</select>							
														</td>
														
														<td >
															
															<div class="row">
																<div class="col-sm-12">
																	<div class="input-group input-group-sm">
																		<input type="text" name="unitPrice[]" class="form-control text-right js-pickPrice" aria-label="판매가" placeholder="판매가" value="<?=$rowt['sale_price']?>" />
																		<span class="input-group-btn">
																			<button class="btn btn-default dropdown-toggle tourPricePick-dropdown js-tourPricePick" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></button>
																				<ul class="dropdown-menu js-tourPriceDropdown">
																					
																				</ul>
																		</span>
																		</div>
																	</div>
															  </div>
													    </td>
														<td >
														    <input type="hidden" name="pickPriceType[]" class="form-control js-pickPriceType" id="pickPriceType" value="<?=$rowt['pick_type']?>">
															<select class="form-control js-pickPriceType1" name="pickPriceType1[]" id="pickPriceType1" >
																<option <?php if ($rowt['pick_type'] == "") { ?> selected <?php } ?> value="">가격타입</option>
																<option <?php if ($rowt['pick_type'] == "G") { ?> selected <?php } ?> value="G">성인</option>
																<option <?php if ($rowt['pick_type'] == "I") { ?> selected <?php } ?> value="I">어린이</option>
																
															</select>							
														</td>
														<td >
														    <input type="hidden" class="form-control js-pickRoomType" name="pickRoomType[]" id="pickRoomType" value="<?=$rowt['room_type']?>">
															<select class="form-control js-pickRoomType1" name="pickRoomType1[]" id="pickRoomType1" >
																<option value="" selected >- 룸타입 -</option>
																<option <?php if ($rowt['room_type'] == "0r1p") { ?> selected <?php } ?> value="0r1p">당일</option>
																<option <?php if ($rowt['room_type'] == "1r1p") { ?> selected <?php } ?> value="1r1p">1인1실</option>
																<option <?php if ($rowt['room_type'] == "1r2p") { ?> selected <?php } ?> value="1r2p">2인1실</option>
																<option <?php if ($rowt['room_type'] == "1r3p") { ?> selected <?php } ?> value="1r3p">3인1실</option>
																<option <?php if ($rowt['room_type'] == "1r4p") { ?> selected <?php } ?> value="1r4p">4인1실</option>
																<option <?php if ($rowt['room_type'] == "1r5p") { ?> selected <?php } ?> value="1r5p">5인1실</option>
															</select>							
														</td>
														
														<td >
															<select class="form-control" name="pickuploc[]" id="pickuploc">
																<option value="">- 탑승지 선택하세요 -
																<?= printPickSelect($pcode ,$rowt['pick_area'],$prodInfo['p_type'])?>
															</select>							
														</td>
														<td >
															<!-- <input type="text" class="form-control" placeholder="추가납부"> -->
															
															<input type="text" name="addamt[]" id="addamt" class="form-control text-right js-additionalCharge" aria-label="추가납부" value="<?=$rowt['add_pay']?>"/>
																	
														</td>
														<td >
															
															<input type="text" name="disamt[]" id="disamt" class="form-control text-right js-discount" aria-label="할인" value="<?=$rowt['dis_pay']?>"/>
																	
														</td>
														<td >
															
															<input type="text" name="lasttamt[]" id="lasttamt" class="form-control text-right js-totalPerPerson" aria-label="총액" value="<?=$rowt['last_pay']?>" readonly/>
																	
														</td>
														<td >
															<div class="row">
																<div class="col-sm-6">
																	<button type="button" class="btn btn-primary btn-sm js-resetTraveler">RESET</button>
																</div>
																<div class="col-sm-5">
																	<button type="button" class="btn btn-primary btn-sm js-removeTraveler" >DELETE</button>
																</div>
															</div>
														</td>
												</tr>

							<?php
							           
								  }
								  $num++;
								  
							?>
									

						<?php endwhile; ?>
						<?php } else { ?>

						
									<tbody >
												<tr class='innertr'>
														<td >
															
															<input type="text" class="form-control" aria-label="ROOM" name="room_num[]" id="room_num" autocomplete=off value="1"/>
																	
														</td>
														<td >
															
														    <input type="text" class="form-control" aria-label="이름" name="t_name[]" id="t_name" autocomplete=off placeholder="이름" value=""/>
															<input type="text" class="form-control" aria-label="영문이름" name="t_ename[]" id="t_name" autocomplete=off placeholder="영문이름" value=""/>
																	
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="전화번호" name="t_phone[]" id="t_phone" autocomplete="off"
															value=""/>
															<input type="text" class="form-control" aria-label="여권번호" name="t_passnum[]" id="t_name" autocomplete=off placeholder="여권번호" value=""/>		
														</td>
														<td >
															
															<input type="text" class="form-control" aria-label="이메일" name="t_email[]" id="t_email" autocomplete="off"
															value=""/>
															<input type="date" class="form-control pass" aria-label="여권만료" name="t_pass[]" id="t_pass" placeholder="여권만료" value=""/>		
														</td>
														<td >
															
															<input type="date" class="form-control" aria-label="생년월일" name="t_birth[]" id="t_birth" value=""/>
															<textarea class="form-control" rows="2" name="tmemo[]" placeholder="비고"></textarea>		
														
											            </td>
													
													
													    <td>
															<select class="form-control js-sexType" name="sexType[]" id="sexType">
																<option selected value="" >- 성별 -</option>
																<option value="man">남자</option>
																<option value="female">여자</option>
																<option value="mfemale">혼성</option>
																
															</select>							
														</td>
														
														<td >
															
															<div class="row">
																<div class="col-sm-12">
																	<div class="input-group input-group-sm">
																		<input type="text" name="unitPrice[]" class="form-control text-right js-pickPrice" aria-label="판매가" placeholder="판매가" value="" />
																		<span class="input-group-btn">
																			<button class="btn btn-default dropdown-toggle tourPricePick-dropdown js-tourPricePick" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></button>
																				<ul class="dropdown-menu js-tourPriceDropdown">
																					
																				</ul>
																		</span>
																		</div>
																	</div>
															  </div>
													    </td>
														<td >
														    <input type="hidden" name="pickPriceType[]" class="form-control js-pickPriceType" id="pickPriceType" value="">
															<select class="form-control js-pickPriceType1" name="pickPriceType1[]" id="pickPriceType1" >
																<option selected value="">가격타입</option>
																<option value="G">성인</option>
																<option value="I">어린이</option>
																
															</select>							
														</td>
														<td >
														    <input type="hidden" class="form-control js-pickRoomType" name="pickRoomType[]" id="pickRoomType" value="1r2p">
															<select class="form-control js-pickRoomType1" name="pickRoomType1[]" id="pickRoomType1" >
																<option value="" selected >- 룸타입 -</option>
																<option  value="0r1p">당일</option>
																<option  value="1r1p">1인1실</option>
																<option selected value="1r2p">2인1실</option>
																<option  value="1r3p">3인1실</option>
																<option  value="1r4p">4인1실</option>
																<option  value="1r5p">5인1실</option>
															</select>							
														</td>
														
														<td >
															<select class="form-control" name="pickuploc[]" id="pickuploc">
																<option value="">- 탑승지 선택하세요 -
																<?= printPickSelect($pcode ,'',$prodInfo['p_type'])?>
															</select>							
														</td>
														<td >
															<!-- <input type="text" class="form-control" placeholder="추가납부"> -->
															
															<input type="text" name="addamt[]" id="addamt" class="form-control text-right js-additionalCharge" aria-label="추가납부" value="0"/>
																	
														</td>
														<td >
															
															<input type="text" name="disamt[]" id="disamt" class="form-control text-right js-discount" aria-label="할인" value="0"/>
																	
														</td>
														<td >
															
															<input type="text" name="lasttamt[]" id="lasttamt" class="form-control text-right js-totalPerPerson" aria-label="총액" value="0" readonly/>
																	
														</td>
														<td >
															<div class="row">
																<div class="col-sm-6">
																	<button type="button" class="btn btn-primary js-resetTraveler">RESET</button>
																</div>
																<div class="col-sm-6">
																	<button type="button" class="btn btn-primary  js-removeTraveler" >DEL</button>
																</div>
															</div>
														</td>
												</tr>
									
							
						<?php 
									 $uamt1 = $sign." ".$pricead; //판매가
									 $aamt1 = $sign." ".'0';  //추가납부
									 $damt1 = $sign." ".'0';  //할인금액
									 $tamt1 =  $sign." ".$pricead;  //총금액
									 $bamt1 =  $sign." ".$pricead;  //발란스

									 $uamt =$pricead; //판매가
									 $aamt = '0';  //추가납부
									 $damt = '0';  //할인금액
									 $tamt =  $pricead;  //총금액
									 $bamt =  $pricead;  //발란스
									 
							} 
						
						?>
						
				   </tbody>
				</table>
				<table class="table table-bordered table-condensed gridSixteen reserveTable1 formDetail">
				  <tbody>
				  
						<tr>
							<td colspan="2" class="active text-center formHeader"><font color=red>수금할</font>-업체</td>
							<td colspan="4">
								<div class="row">
									<!--<div class="col-sm-6">
										<select class="form-control areaf" name="tourRegion">
											<option value="">- 지역 선택하세요 -
											<?= printBaseCode4_without('A01','00',$v_info['company_area']); ?>
										</select>
									</div>-->
									<div class="col-sm-12">
									
										<select class="form-control comp" name="tourcomp" id="tourcomp" >
											<option value="">- 업체를 선택하세요 -
											<?=printCompanySelect($ccomp_info['part_id'])?>

										</select>
									</div>
								</div>
							</td>
							<td colspan="2" class="active text-center formHeader">수금할 NET 금액</td>
							<td colspan="8">
								<div class="row">
									<div class="col-sm-3">
										<!-- <input type="text" id="" name="startDate" class="inpubase" placeholder="금액" value=""/> -->
										<div class="input-group input-group-sm">
											<span class="input-group-addon">금액</span>
											<input type="text" name="ramt" id="ramt" class="form-control text-right" aria-label="금액" value="<?=$ccomp_info['amt']?>"/>
										</div>
									</div>
									<div class="col-sm-5">
										<!-- <input type="text" id="" name="endDate" class="inpubase" placeholder="금액내용" value=""/> -->
										<div class="input-group input-group-sm">
											<span class="input-group-addon">금액내용</span>
											<input type="text" name="ramtmemo" id="ramtmemo" class="form-control" aria-label="금액내용" value="<?=$ccomp_info['p_memo']?>"/>
										</div>
									</div>
									<div class="col-sm-4">
									<!--	<button type="button" class="btn btn-primary  js-removecomp" >DELETE</button>-->
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader"><font color=red>지급할</font>-업체</td>
							<td colspan="4">
								<div class="row">
									<!--<div class="col-sm-6">
										<select class="form-control areaf2" name="tourRegion1">
											<option value="">- 지역 선택하세요 -
											<?= printBaseCode4_without('A01','00',$v_info['company_area']); ?>
										</select>
									</div>-->
									<div class="col-sm-12">
									
										<select class="form-control comp2" name="tourcomp1" id="tourcomp1">
											<option value="">- 업체를 선택하세요 -
										<?=printCompanySelect($dcomp_info['part_id'])?>
										</select>
									</div>
								</div>
							</td>
							<td colspan="2" class="active text-center formHeader">지급할 NET 금액</td>
							<td colspan="8">
								<div class="row">
									<div class="col-sm-3">
										<!-- <input type="text" id="" name="startDate" class="inpubase" placeholder="금액" value=""/> -->
										<div class="input-group input-group-sm">
											<span class="input-group-addon">금액</span>
											<input type="text" name="pamt" class="form-control text-right" aria-label="금액" value="<?=$dcomp_info['amt']?>"/>
										</div>
									</div>
									<div class="col-sm-5">
										<!-- <input type="text" id="" name="endDate" class="inpubase" placeholder="금액내용" value=""/> -->
										<div class="input-group input-group-sm">
											<span class="input-group-addon">금액내용</span>
											<input type="text" name="pamtmemo" class="form-control" aria-label="금액내용" value="<?=$dcomp_info['p_memo']?>"/>
										</div>
									</div>
									<div class="col-sm-4">
									<!---	<button type="button" class="btn btn-primary  js-removecomp1" >DELETE</button>-->
									</div>
								</div>
							</td>
						</tr>
						
						<tr>
							<td colspan="2" class="active text-center formHeader">진행사항</td>
							<td colspan="14">
								<textarea class="form-control js-specialBenefit js-ckEditor" rows="15" name="pmemo" id="pmemo" ><?=$reserve_info['progress']?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">고객/업체요청사항</td>
							<td colspan="14">
								<textarea class="form-control" rows="8" name="cmemo" ><?=$reserve_info['c_progress']?></textarea>
							</td>
						</tr>
				 </tbody>
				</table>
				<table id="air" class="table table-bordered table-condensed gridSixteen">
					<tbody id="airtbody">
						<tr>
							<td colspan="16" class="active text-center formHeader fullWidth">항공예약 &nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-default btn-xs js-addair"  >PNR 추가 <span class="glyphicon glyphicon-plus" aria-hidden="true" ></span></button>&nbsp;<button type="button" class="btn btn-default btn-xs js-addairstop"  >STOP 추가 <span class="glyphicon glyphicon-plus" aria-hidden="true" ></span></button>&nbsp;
							&nbsp;<button type="button" class="btn btn-default btn-xs js-reairstop"  >STOP 삭제 <span class="glyphicon glyphicon-minus" aria-hidden="true" ></span></button>&nbsp;<button type="button" class="btn btn-default btn-xs js-hideShowToggle"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span></button> </td>
						</tr>
						<?php
									$qryr = "select * from reserve_airline_pnr where reserveCode = '$estimateCode' order by a_airline_print asc ";
									//echo $qryr;
									$rstr = mysql_query($qryr);
									$cntr= mysql_num_rows($rstr);
									$i =1; 
									$totamtair =0.00;
									if ($cntr > 0) {
										while($rrow = mysql_fetch_assoc($rstr)):
										   $productInfo = getProductMaster($rrow['p_code']);
										   $totamtair = $totamtair + $rrow['a_airline_amt'];
						?>
											<tr class="airin2">
											  <td colspan='16'>
												  <table  id="airsub" class="table-bordered table-condensed gridSixteen airsub">
													<tbody>
														 <tr >
															<td colspan="14" class="active text-center formHeader"><span id="innum">항공 인보이스#<?=$i?></span></td>
															
														</tr>
														 <tr>
															<td  class="active text-center formHeader">출발일</td>
															<td >
															   <input type="date" name="stdate_air[]" class="form-control stclass" aria-label="출발일" value="<?=$rrow['a_airline_start']?>"/>
															</td>
															<td  class="active text-center formHeader">출발공항</td>
															<td >
															   <input type="text" name="st_air[]" class="inpubase sm1" aria-label="출발공항" value="<?=$rrow['a_start_airport']?>"/>
															</td>
															
															<td  class="active text-center formHeader">도착공항</td>
															 <td>  <input type="text" name="de_air[]" class="inpubase sm1" aria-label="도착공항" value="<?=$rrow['a_stop_airport']?>"/>
															</td>
															<td  class="active text-center formHeader">편명</td>
															 <td>  <input type="text" name="sairnm[]" class="inpubase sm1" aria-label="편명" value="<?=$rrow['a_airport_name']?>"/>
															</td>
															<td  class="active text-center formHeader">출발/도착시간</td>
															 <td >  <input type="text" name="sairtime[]" class="inpubase sm1" placeholder="출발시간" value="<?=$rrow['a_airport_time']?>"/>
															 <input type="text" name="dairtime[]" class="inpubase sm1" placeholder="도착시간" value="<?=$rrow['a_airport_time1']?>"/>
															</td>
															<td colspan="2" class="active text-center formHeader">PNR/TICKET#</td>
															 <td colspan="2">  <input type="text" name="pnrnum[]" class="inpubase sm1" placeholder="pnr" value="<?=$rrow['a_pnr_number']?>"/>
															 <input type="text" name="ticket[]" class="inpubase sm1" placeholder="ticket#" value="<?=$rrow['a_tk_number']?>"/>
															</td>
														</tr>
														 <tr>
														    <td  class="active text-center formHeader">발권일</td>
															<td >
															   <input type="date" name="airdate[]" class="form-control printclass" aria-label="발권일" value="<?=$rrow['a_airline_print']?>"/>
															</td>
															<td  class="active text-center formHeader">복귀일</td>
															<td >
															   <input type="date" name="redate_air[]" class="form-control returnclass" aria-label="복귀일" value="<?=$rrow['a_airline_return']?>"/>
															</td>
															<td  class="active text-center formHeader">출발공항</td>
															<td >
															   <input type="text" name="rst_air[]" class="inpubase sm1" aria-label="출발공항" value="<?=$rrow['a_start_airport2']?>"/>
															</td>
															
															<td  class="active text-center formHeader">도착공항</td>
															 <td>  <input type="text" name="rde_air[]" class="inpubase sm1" aria-label="도착공항" value="<?=$rrow['a_stop_airport2']?>"/>
															</td>
															<td  class="active text-center formHeader">편명</td>
															 <td>  <input type="text" name="rairnm[]" class="inpubase sm1" aria-label="편명" value="<?=$rrow['a_airport_name2']?>"/>
															</td>
															<td  class="active text-center formHeader">출발/도착시간</td>
															 <td>  <input type="text" name="rairtime[]" class="inpubase sm1" placeholder="출발시간" value="<?=$rrow['a_airport_time2']?>"/>
															 <input type="text" name="dairtime[]" class="inpubase sm1" placeholder="도착시간" value="<?=$rrow['a_airport_time3']?>"/>
															</td>
															<td   class="active text-center formHeader">PNR/TICKET#</td>
															 <td >  <input type="text" name="rpnrnum[]" class="inpubase sm1" placeholder="pnr" value="<?=$rrow['a_pnr_number1']?>"/>
															 <input type="text" name="rticket[]" class="inpubase sm1" placeholder="ticket#" value="<?=$rrow['a_tk_number2']?>"/>
															</td>
														</tr>
														<tr>
														    <td  class="active text-center formHeader">인원</td>
															<td >
															   <input type="text" name="air_p[]" class="inpubase sm1" aria-label="인원" value="<?=$rrow['a_airport_cnt']?>" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td  class="active text-center formHeader">단가항공료</td>
															<td >
															   <input type="text" name="air_rate[]" class="inpubase sm1" aria-label="단가항공료" value="<?=$rrow['a_rate']?>" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td  class="active text-center formHeader">TAX</td>
															<td >
															   <input type="text" name="airtax[]" class="inpubase sm1" aria-label="TAX" value="<?=$rrow['a_tax']?>" onBlur="javascript:aircalc_ticket()"/>
															</td>
															
															<td  class="active text-center formHeader">MCO/MCO Fee</td>
															 <td>  <input type="text" name="airmco[]" class="inpubase sm1" placeholder="MCO" value="<?=$rrow['a_fee']?>" onBlur="javascript:aircalc_ticket()"/>
															<input type="text" name="mcofee[]" class="inpubase sm1" placeholder="MCO Fee" value="<?=$rrow['a_fee1']?>" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td  class="active text-center formHeader">VCOM</td>
															 <td>  <input type="text" name="a_cms[]" class="inpubase sm1" placeholder="VCOM" value="<?=$rrow['a_cms']?>" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td  class="active text-center formHeader">티켓 NET총합</td>
															 <td>  <input type="text" name="a_amt[]" class="inpubase sm1" placeholder="티켓 NET총합" value="<?=$rrow['a_amt']?>" onBlur="javascript:aircalc_ticket()"/>
															 
															</td>
															<td   class="active text-center formHeader">Class</td>
															 <td > <select name=a_cls_type[] class=form-control>
																		<option value="1" <?php if($rrow['a_cls_type'] == 1) echo "selected"; ?>>ADT
																		<option value="2" <?php if($rrow['a_cls_type'] == 2) echo "selected"; ?>>CHD
																		<option value="3" <?php if($rrow['a_cls_type'] == 3) echo "selected"; ?>>SRC
																		<option value="4" <?php if($rrow['a_cls_type'] == 4) echo "selected"; ?>>INF
																	</select>
															</td>
														</tr>

														<tr>
														   
														    <td  class="active text-center formHeader">결제방법</td>
															<td >
															   <select name=a_settle_type[] class=form-control Onchange ="javascript:aircalc_ticket(0)">
																	<option value="1" <?php if($rrow['a_settle_type'] == 1) echo "selected"; ?>>항공시스템
																	<option value="2" <?php if($rrow['a_settle_type'] == 2) echo "selected"; ?>>Cash&Check
																	<option value="3" <?php if($rrow['a_settle_type'] == 3) echo "selected"; ?>>지사단말기
																	<option value="4" <?php if($rrow['a_settle_type'] == 4) echo "selected"; ?>>웹결제
																</select>
															</td>
															<td colspan="2" class="active text-center formHeader">판매금액</td>
															<td >
															   <input type="text" name="a_airline_amt[]" class="inpubase sm1" aria-label="판매금액" value="<?=$rrow['a_airline_amt']?>" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td colspan="2" class="active text-center formHeader">발권처</td>
															<td  colspan="2">
															  <select name=rand_id_air[] class=form-control >
																  <option value='0' >선 택
																	<?= printRandSelectAirlie($rrow['rand_id']); ?>
															  </select>
															</td>
															
															<td colspan="2" class="active text-center formHeader">항공수익</td>
															 <td>  <input type="text" name="a_air_amt[]" class="inpubase sm1" placeholder="항공수익" value="<?=$rrow['a_air_amt']?>" onBlur="javascript:aircalc_ticket()"/>
															
															</td>
															
															
														</tr>
														<tr class="airstop">
														   <td colspan=17>
														   <?php
																$qry1 = "select * from reserve_airline_rstop where reserveCode='".$reserve_info['reserveCode']."' && a_pnr_number='".$rrow['a_pnr_number1']."' order by seq asc ";
														        //echo $qry1;
																$rst1 = mysql_query($qry1);
																$cnts= mysql_num_rows($rst1);
																if ($cnts > 0) {
																	while($srow = mysql_fetch_assoc($rst1)) {



														   ?>
																	   <table id="stopair" class="table table-bordered table-condensed gridSixteen reserveTable formDetail  stopair">
																		<tbody>
																			<tr >
																				<td rowspan="2" class="active text-center formHeader">STOP<br /><b>*PNR과 TK넘버는 반드시 <br /> 같아야합니다!!</b></td>
																				<td class="active text-center formHeader">  
																				  출발일
																				</td>
																				<td class="active text-center formHeader">  
																				  출발공항
																				</td>
																				<td class="active text-center formHeader">  
																				  도착공항
																				</td>
																				<td colspan=2 class="active text-center formHeader">  
																				  편명
																				</td>
																				<td colspan=2 class="active text-center formHeader">  
																				  시간(24시->16:00)
																				</td>
																				<td colspan=2 class="active text-center formHeader">  
																				 PNR/#TK
																				</td>
																			</tr>
																			<tr >
																				
																				<td class= "text-center">  
																				   <input type="date" name="stop_stardate[]" class="form-control returnclass" placeholder="출발일" value="<?=$srow['a_airline_start']?>"/>
																				
																				</td>

																				<td class= "text-center">
																					<input type="text" name="stop_starair[]" class="inpubase sm1" placeholder="출발공항" value="<?=$srow['a_start_airport']?>"/>
																				</td>

																				<td class= "text-center">
																					<input type="text" name="stop_stopair[]" class="inpubase sm1" placeholder="도착공항" value="<?=$srow['a_stop_airport']?>"/>
																				</td>

																				<td class= "text-center">
																					<input type="text" name="stop_airnum1[]" class="inpubase sm1" placeholder="항공편명1" value="<?=$srow['a_airport_name']?>"/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_airnum2[]" class="inpubase sm1" placeholder="항공편명2" value="<?=$srow['a_airport_name2']?>"/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_time1[]" class="inpubase sm1" placeholder="시간" value="<?=$srow['a_airport_time']?>"/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_time2[]" class="inpubase sm1" placeholder="시간" value="<?=$srow['a_airport_time1']?>"/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_pnr[]" class="inpubase sm1" placeholder="PNR" value="<?=$srow['a_pnr_number']?>"/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_tk[]" class="inpubase sm1" placeholder="#TK" value="<?=$srow['a_tk_number']?>"/>
																				</td>
																			</tr>
																		</tbody>
																	   </table>
														  <?php
									                           }
															} else {
                                                          ?>
															   
																	   <table id="stopair" class="table table-bordered table-condensed gridSixteen reserveTable formDetail  stopair">
																		<tbody>
																			<tr >
																				<td rowspan="2" class="active text-center formHeader">STOP<br /><b>*PNR과 TK넘버는 반드시 <br /> 같아야합니다!!</b></td>
																				<td class="active text-center formHeader">  
																				  출발일
																				</td>
																				<td class="active text-center formHeader">  
																				  출발공항
																				</td>
																				<td class="active text-center formHeader">  
																				  도착공항
																				</td>
																				<td colspan=2 class="active text-center formHeader">  
																				  편명
																				</td>
																				<td colspan=2 class="active text-center formHeader">  
																				  시간(24시->16:00)
																				</td>
																				<td colspan=2 class="active text-center formHeader">  
																				 PNR/#TK
																				</td>
																			</tr>
																			<tr >
																				
																				<td class= "text-center">  
																				   <input type="date" name="stop_stardate[]" class="form-control returnclass" placeholder="출발일" value=""/>
																				
																				</td>

																				<td class= "text-center">
																					<input type="text" name="stop_starair[]" class="inpubase sm1" placeholder="출발공항" value=""/>
																				</td>

																				<td class= "text-center">
																					<input type="text" name="stop_stopair[]" class="inpubase sm1" placeholder="도착공항" value=""/>
																				</td>

																				<td class= "text-center">
																					<input type="text" name="stop_airnum1[]" class="inpubase sm1" placeholder="항공편명1" value=""/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_airnum2[]" class="inpubase sm1" placeholder="항공편명2" value=""/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_time1[]" class="inpubase sm1" placeholder="시간" value=""/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_time2[]" class="inpubase sm1" placeholder="시간" value=""/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_pnr[]" class="inpubase sm1" placeholder="PNR" value=""/>
																				</td>
																				<td class= "text-center">
																					<input type="text" name="stop_tk[]" class="inpubase sm1" placeholder="#TK" value=""/>
																				</td>
																			</tr>
																		</tbody>
																	</table>
																   
															   <?php
																}
															   ?>
																

									
														   </td>
														 </tr>
													</tbody>
												</table>
											  </td>
											</tr>	
						<?php
										$i++;
						                endwhile;
									} else {
						?>
						
											
											<tr class="airin2 hidden">
											  <td colspan='16'>
												  <table id="airsub" class="table-bordered table-condensed gridSixteen airsub">
													<tbody>
														 <tr >
															<td colspan="14" class="active text-center formHeader"><span id="innum">항공 인보이스#1</span></td>
															
														</tr>
														 <tr>
															<td  class="active text-center formHeader">출발일</td>
															<td >
															   <input type="date" name="stdate_air[]" class="form-control stclass" aria-label="출발일" value=""/>
															</td>
															<td  class="active text-center formHeader">출발공항</td>
															<td >
															   <input type="text" name="st_air[]" class="inpubase sm1" aria-label="출발공항" value=""/>
															</td>
															
															<td  class="active text-center formHeader">도착공항</td>
															 <td>  <input type="text" name="de_air[]" class="inpubase sm1" aria-label="출발공항" value=""/>
															</td>
															<td  class="active text-center formHeader">편명</td>
															 <td>  <input type="text" name="sairnm[]" class="inpubase sm1" aria-label="편명" value=""/>
															</td>
															<td  class="active text-center formHeader">출발/도착시간</td>
															 <td >  <input type="text" name="sairtime[]" class="inpubase sm1" placeholder="출발시간" value=""/>
															 <input type="text" name="dairtime[]" class="inpubase sm1" placeholder="도착시간" value=""/>
															</td>
															<td colspan="2" class="active text-center formHeader">PNR/TICKET#</td>
															 <td colspan="2">  <input type="text" name="pnrnum[]" class="inpubase sm1" placeholder="pnr" value=""/>
															 <input type="text" name="ticket[]" class="inpubase sm1" placeholder="ticket#" value=""/>
															</td>
														</tr>
														 <tr>
														    <td  class="active text-center formHeader">발권일</td>
															<td >
															   <input type="date" name="airdate[]" class="form-control printclass" aria-label="발권일" value=""/>
															</td>
															<td  class="active text-center formHeader">복귀일</td>
															<td >
															   <input type="date" name="redate_air[]" class="form-control returnclass" aria-label="복귀일" value=""/>
															</td>
															<td  class="active text-center formHeader">출발공항</td>
															<td >
															   <input type="text" name="rst_air[]" class="inpubase sm1" aria-label="출발공항" value=""/>
															</td>
															
															<td  class="active text-center formHeader">도착공항</td>
															 <td>  <input type="text" name="rst_air[]" class="inpubase sm1" aria-label="출발공항" value=""/>
															</td>
															<td  class="active text-center formHeader">편명</td>
															 <td>  <input type="text" name="rairnm[]" class="inpubase sm1" aria-label="편명" value=""/>
															</td>
															<td  class="active text-center formHeader">출발/도착시간</td>
															 <td>  <input type="text" name="rairtime[]" class="inpubase sm1" placeholder="출발시간" value=""/>
															 <input type="text" name="dairtime[]" class="inpubase sm1" placeholder="도착시간" value=""/>
															</td>
															<td   class="active text-center formHeader">PNR/TICKET#</td>
															 <td >  <input type="text" name="rpnrnum[]" class="inpubase sm1" placeholder="pnr" value=""/>
															 <input type="text" name="rticket[]" class="inpubase sm1" placeholder="ticket#" value=""/>
															</td>
														</tr>
														<tr>
														    <td  class="active text-center formHeader">인원</td>
															<td >
															   <input type="text" name="air_p[]" class="inpubase sm1" aria-label="인원" value="0" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td  class="active text-center formHeader">단가항공료</td>
															<td >
															   <input type="text" name="air_rate[]" class="inpubase sm1" aria-label="단가항공료" value="0" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td  class="active text-center formHeader">TAX</td>
															<td >
															   <input type="text" name="airtax[]" class="inpubase sm1" aria-label="TAX" value="0" onBlur="javascript:aircalc_ticket()" />
															</td>
															
															<td  class="active text-center formHeader">MCO/MCO Fee</td>
															 <td>  <input type="text" name="airmco[]" class="inpubase sm1" placeholder="MCO" value="" onBlur="javascript:aircalc_ticket()"/>
															<input type="text" name="mcofee[]" class="inpubase sm1" placeholder="MCO Fee" value="0" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td  class="active text-center formHeader">VCOM</td>
															 <td>  <input type="text" name="a_cms[]" class="inpubase sm1" placeholder="VCOM" value="0" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td  class="active text-center formHeader">티켓 NET총합</td>
															 <td>  <input type="text" name="a_amt[]" class="inpubase sm1" placeholder="티켓 NET총합" value="0" onBlur="javascript:aircalc_ticket()"/>
															 
															</td>
															<td   class="active text-center formHeader">Class</td>
															 <td > <select name=a_cls_type[] class=form-control>
																		<option value="1">ADT
																		<option value="2">CHD
																		<option value="3">SRC
																		<option value="4">INF
																	</select>
															</td>
														</tr>

														<tr>
														   
														    <td  class="active text-center formHeader">결제방법</td>
															<td >
															   <select name=a_settle_type[] class=form-control Onchange ="javascript:aircalc_ticket(0)">
																	<option value="1" >항공시스템
																	<option value="2" >Cash&Check
																	<option value="3" >지사단말기
																</select>
															</td>
															<td colspan="2" class="active text-center formHeader">판매금액</td>
															<td >
															   <input type="text" name="a_airline_amt[]" class="inpubase sm1" aria-label="판매금액" value="0" onBlur="javascript:aircalc_ticket()"/>
															</td>
															<td colspan="2" class="active text-center formHeader">발권처</td>
															<td  colspan="2">
															  <select name=rand_id_air[] class=form-control >
																  <option value='0' >선 택
																	<?= printRandSelectAirlie($a_row1['rand_id']); ?>
															  </select>
															</td>
															
															<td colspan="2" class="active text-center formHeader">항공수익</td>
															 <td>  <input type="text" name="a_air_amt[]" class="inpubase sm1" placeholder="항공수익" value="0" onBlur="javascript:aircalc_ticket()"/>
															
															</td>
															
															
														</tr>
														<tr class="airstop">
														   <td colspan=16>
                                                               <table id="stopair" class="table table-bordered table-condensed gridSixteen reserveTable formDetail  stopair">
																<tbody>
																	<tr >
																		<td rowspan="2" class="active text-center formHeader">STOP<br /><b>*PNR과 TK넘버는 반드시 <br /> 같아야합니다!!</b></td>
																		<td class="active text-center formHeader">  
																		  출발일
																		</td>
																		<td class="active text-center formHeader">  
																		  출발공항
																		</td>
																		<td class="active text-center formHeader">  
																		  도착공항
																		</td>
																		<td colspan=2 class="active text-center formHeader">  
																		  편명
																		</td>
																		<td colspan=2 class="active text-center formHeader">  
																		  시간(24시->16:00)
																		</td>
																		<td colspan=2 class="active text-center formHeader">  
																		 PNR/#TK
																		</td>
																	</tr>
																	<tr >
																		
																		<td class= "text-center">  
																		   <input type="date" name="stop_stardate[]" class="form-control returnclass" placeholder="출발일" value=""/>
																		
																		</td>

																		<td class= "text-center">
																			<input type="text" name="stop_starair[]" class="inpubase sm1" placeholder="출발공항" value=""/>
																		</td>

																		<td class= "text-center">
																			<input type="text" name="stop_stopair[]" class="inpubase sm1" placeholder="도착공항" value=""/>
																		</td>

																		<td class= "text-center">
																			<input type="text" name="stop_airnum1[]" class="inpubase sm1" placeholder="항공편명1" value=""/>
																		</td>
																		<td class= "text-center">
																			<input type="text" name="stop_airnum2[]" class="inpubase sm1" placeholder="항공편명2" value=""/>
																		</td>
																		<td class= "text-center">
																			<input type="text" name="stop_time1[]" class="inpubase sm1" placeholder="시간" value=""/>
																		</td>
																		<td class= "text-center">
																			<input type="text" name="stop_time2[]" class="inpubase sm1" placeholder="시간" value=""/>
																		</td>
																		<td class= "text-center">
																			<input type="text" name="stop_pnr[]" class="inpubase sm1" placeholder="PNR" value=""/>
																		</td>
																		<td class= "text-center">
																			<input type="text" name="stop_tk[]" class="inpubase sm1" placeholder="#TK" value=""/>
																		</td>
																	</tr>
																</tbody>
															</table>
														   </td>
													    </tr>

													</tbody>
												</table>
											  </td>
											</tr>
											
						
						<?php
						            
									}
									
						?>
											

					</tbody>
				</table>
			<!--	<div class="stoparea">
					<table id="stopair" class="table table-bordered table-condensed gridSixteen reserveTable formDetail ">
						<tbody>
							<tr >
								<td rowspan="2" class="active text-center formHeader">STOP<br /><b>*PNR과 TK넘버는 반드시 <br /> 같아야합니다!!</b></td>
								<td class="active text-center formHeader">  
								  출발일
								</td>
								<td class="active text-center formHeader">  
								  출발공항
								</td>
								<td class="active text-center formHeader">  
								  도착공항
								</td>
								<td colspan=2 class="active text-center formHeader">  
								  편명
								</td>
								<td colspan=2 class="active text-center formHeader">  
								  시간(24시->16:00)
								</td>
								<td colspan=2 class="active text-center formHeader">  
								 PNR/#TK
								</td>
							</tr>
							<tr >
								
								<td class= "text-center">  
								   <input type="date" name="stop_stardate[]" class="form-control returnclass" placeholder="출발일" value=""/>
								
								</td>

								<td class= "text-center">
									<input type="text" name="stop_starair[]" class="inpubase sm1" placeholder="출발공항" value=""/>
								</td>

								<td class= "text-center">
									<input type="text" name="stop_stopair[]" class="inpubase sm1" placeholder="도착공항" value=""/>
								</td>

								<td class= "text-center">
									<input type="text" name="stop_airnum1[]" class="inpubase sm1" placeholder="항공편명1" value=""/>
								</td>
								<td class= "text-center">
									<input type="text" name="stop_airnum2[]" class="inpubase sm1" placeholder="항공편명2" value=""/>
								</td>
								<td class= "text-center">
									<input type="text" name="stop_time1[]" class="inpubase sm1" placeholder="시간" value=""/>
								</td>
								<td class= "text-center">
									<input type="text" name="stop_time2[]" class="inpubase sm1" placeholder="시간" value=""/>
								</td>
								<td class= "text-center">
									<input type="text" name="stop_pnr[]" class="inpubase sm1" placeholder="PNR" value=""/>
								</td>
								<td class= "text-center">
									<input type="text" name="stop_tk[]" class="inpubase sm1" placeholder="#TK" value=""/>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				-->
				<table id="singletour" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody id="sbody">
						<tr>
							<td colspan="16" class="active text-center formHeader fullWidth">단일투어정보&nbsp;
							<button type="button" class="btn btn-default btn-sm js-addlocal" ><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
						</tr>
						<?php
									$qryr = "select a.* from reserve_info a  where  a.reserveCode = '$estimateCode'&& a.parent='SUB'  && a.p_code not like  '%PICKUP%' && a.p_code not like  '%SENDING%'   order by a.stDate,pos asc";
									//echo $qryr;
									$rstr = mysql_query($qryr);
									$cntr= mysql_num_rows($rstr);
									if ($cntr > 0) {
										while($rrow = mysql_fetch_assoc($rstr)):
										   $productInfo = getProductMaster($rrow['p_code']);
											
						?>
										<tr class="local">
											<td colspan="2" class="active text-center formHeader">출발일</td>
											
											<td colspan="3" class="no-right-border">
												<div class="row">
													<div class="col-sm-12">
														<!-- <input type="date" id="" name="startDate" class="inpubase" value=""/> -->
														<div class="input-group input-group-sm">
															<input type="date" name="singleDayTourStartDate[]" class="form-control js-singleDayTourDate js-singleDayTourDate1" aria-label="출발일" placeholder="출발일" value='<?=$rrow['stDate']?>' readonly>
															<span class="input-group-btn">
																<button class="btn btn-default popp" data-toggle="modal" data-target=".js-openSingleDayTourModal" type="button"  disabled><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true">상품선택</span></button>
																	
															</span>
															<input type=hidden name='tday[]' class=form_box value="<?php echo $rrow['c_day']; ?>">
															<input type=hidden name='pos[]' class=form_box value="<?= $rrow['pos'] ?>">
															<input type=hidden name='seqnum[]' class=form_box value="<?= $rrow['seq_no'] ?>">
														</div>
													</div>
												</div>
											</td>
											<td colspan="3" class="no-left-border">
												<div class="row">
													<div class="col-sm-12">
														<!-- <input type="text" class="form-control actionRequired1" id="unitPrice1" name="unitPrice[]" placeholder="단독상품명" readonly/>
														<button type="button" class="btn btn-default btn-xs js-xxxxx"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></button> -->
														<div class="row">
															<div class="col-sm-12">
																
																	<span id="p_nm"><?= $productInfo['p_name'] ?></span>
																	<input type=hidden name='l_p_code[]' id="l_p_code" class=form_box value="<?= $rrow['p_code'] ?>">
																	<input type=hidden name='singleTour[]' id="singleTour" class=form_box value='<?= $productInfo['p_name'] ?>'>
																
															</div>
														</div>
													</div>
												</div>
											</td>
											<td colspan="2" class="active text-center formHeader">미팅장소</td>
											
											<td colspan="3" class="required">
												<select class="form-control mt1" name="mtarea[]" id="mt1">
													<option value="">- 미팅장소 선택하세요 -</option>
													<?=pickBaseCode2($rrow['meet_area'])?>
												</select>
											</td>
											<td width=5%>
											<button type="button" class="btn btn-primary btn-sm js-removelocal" >삭제</button>
											</td>
										
										</tr>		
						<?php
						                endwhile;
									} else {
						
									$qry1 = "select * from product_details_local where p_code = '{$prodInfo['p_code']}' && local_code not like  '%PICKUP%' && local_code not like  '%SENDING%'  
									order by day,position,seq_no asc";
									//echo $qry1;
									$rst1 = mysql_query($qry1);
									$cntd = mysql_num_rows($rst1);
									while($r_row = mysql_fetch_assoc($rst1)):
									    $productInfo = getProductMaster($r_row['local_code']);
										
										// start day
										$s_date = explode("-",$st);
										
										$add_date = $r_row['day']-1;

										$local_start  = date("Y-m-d",mktime (0,0,0,$s_date[1]  , $s_date[2]+$add_date, $s_date[0]));	
						
						 ?>
										<tr class="local">
											<td colspan="2" class="active text-center formHeader">출발일</td>
											
											<td colspan="3" class="no-right-border">
												<div class="row">
													<div class="col-sm-12">
														<!-- <input type="date" id="" name="startDate" class="inpubase" value=""/> -->
														<div class="input-group input-group-sm">
															<input type="date" name="singleDayTourStartDate[]" class="form-control js-singleDayTourDate js-singleDayTourDate1" aria-label="출발일" placeholder="출발일" value='<?=$local_start?>' readonly>
															<span class="input-group-btn">
																<button class="btn btn-default popp" data-toggle="modal" data-target=".js-openSingleDayTourModal"  type="button" ><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></button>
																	
															</span>
															<input type=hidden name='tday[]' class=form_box value="<?= $r_row['day'] ?>">
															<input type=hidden name='pos[]' class=form_box value="<?= $r_row['position'] ?>">
															<input type=hidden name='seqnum[]' class=form_box value="<?= $r_row['seq_no'] ?>">
														</div>
													</div>
												</div>
											</td>
											<td colspan="3" class="no-left-border">
												<div class="row">
													<div class="col-sm-12">
														<!-- <input type="text" class="form-control actionRequired1" id="unitPrice1" name="unitPrice[]" placeholder="단독상품명" readonly/>
														<button type="button" class="btn btn-default btn-xs js-xxxxx"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></button> -->
														<div class="row">
															<div class="col-sm-12">
																
																	<span><?= $productInfo['p_name'] ?></span>
																	<input type=hidden name='l_p_code[]'  id="l_p_code" class=form_box value="<?= $r_row['local_code'] ?>">
																	<input type=hidden name='singleTour[]'  id="singleTour" class=form_box value='<?= $productInfo['p_name'] ?>'>
																
															</div>
														</div>
													</div>
												</div>
											</td>
											<td colspan="2" class="active text-center formHeader">미팅장소</td>
											
											<td colspan="3" class="required">
												<select class="form-control mt1"  name="mtarea[]">
													<option value="">- 미팅장소 선택하세요 -</option>
													<?=pickBaseCode2('')?>
												</select>
											</td>
											<td width=5%>
											<button type="button" class="btn btn-primary btn-sm js-removelocal" >삭제</button>
											</td>	
											
											
											
										</tr>
						
						<?php
						              endwhile;
									}
						?>

					</tbody>
				</table>
			<!--	<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
							<td colspan="16" class="active text-center formHeader fullWidth">배정호텔정보</td>
						</tr>
						<?php
							$qry1 = "select distinct reserveCode,hotel_code,p_code,p_name,stDate,DATE_ADD(stDate, INTERVAL (day-1) DAY) as sldate from hotel_assign where reserveCode='$estimateCode' order by stDate asc";
							
							$rst1 = mysql_query($qry1);
							$cnth = mysql_num_rows($rst1);
							while($h_row = mysql_fetch_assoc($rst1)):
							   $hinfo = getinfo_dbHotel_bycode($h_row['hotel_code']);
						?>
						<tr>
							<td colspan="2" class="active text-center formHeader">숙박일/호텔명</td>
							
							<td colspan="2" class="no-right-border">
								<div class="row">
									<div class="col-sm-12">
										
										<div class="input-group input-group-sm">
											<input type="text" name="sleepDate" class="form-control  js-hotelStayDate" aria-label="숙박일" placeholder="숙박일" value="<?= $h_row['sldate'] ?>" readOnly>
											
										</div>
									</div>
								</div>
							</td>
							<td colspan="4" class="no-left-border">
								<div class="row">
									<div class="col-sm-12">
										<div class="well well-sm pt-well"><?= $h_row['p_name'] ?> </div>
									</div>
								</div>
							</td>
							<td colspan="8" class="no-left-border">
								<div class="row">
									<div class="col-sm-12">
										<div class="well well-sm pt-well"><?= $hinfo['h_name'] ?></div>
									</div>
								</div>
							</td>
						</tr>
					  <?php
						      endwhile;
									
					  ?>
					</tbody>
				</table>-->
				
				<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
							<td colspan="16" class="active text-center formHeader fullWidth">픽업/샌딩 항공정보 &nbsp;<button type="button" class="btn btn-default btn-xs js-hideShowToggle"><span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span></button></td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">픽업정보</td>
							<td colspan="2">
								
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											
											<select class="form-control" name="tourpick" id="tourpick">
											<option value="">- 픽업상품을 선택하세요 -
											<?php echo getProductPickup($reserve_pick['p_code']); ?>
                                           
										</select>
										</div>
									</div>
								</div>
							</td>
							<td colspan="4">
								<!-- <input type="text" class="form-control" placeholder="출발도시"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">픽업도착도시</span>
											<input type="text" name="arrivecity" class="form-control " aria-label="도착도시" value="<?=$reserve_info['air_arcity']?>"/>
										</div>
									</div>
								</div>
							</td>
							<td colspan="2">
								<!-- <input type="text" class="form-control" placeholder="도착일"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<input type="date" name="arrivalDate" class="form-control js-dateInput2 js-arrivalDate" aria-label="도착일" placeholder="도착일" value="<?=$reserve_info['air_arriveDate']?>">
											<!--<span class="input-group-btn">
												<button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
											</span>-->
										</div>
									</div>
								</div>
							</td>
							<td colspan="1">
								<!-- <input type="text" class="form-control js-airTimes" placeholder="도착시간"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<input type="text" name="arrivalTime" class="form-control " aria-label="도착시간" placeholder="도착시간" value="<?=$reserve_info['air_arrivetime']?>">
											<!--<span class="input-group-btn">
												<button class="btn btn-default js-timeInputBtn" type="button"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></button>
											</span>-->
										</div>
									</div>
								</div>
							</td>
							<td colspan="3">
								<!-- <input type="text" class="form-control" placeholder="항공사/편명"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">항공사/편명</span>
											<input type="text" name="airname" class="form-control" aria-label="항공사/편명" value="<?=$reserve_info['air_arriveNm']?>"/>
										</div>
									</div>
								</div>
							</td>
							<td colspan="6">
								<!-- <input type="text" class="form-control" placeholder="메모"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">메모</span>
											<input type="text" name="arrivememo" class="form-control" aria-label="메모" value="<?=$reserve_info['air_arriveMemo']?>"/>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">샌딩정보</td>
							<td colspan="2">
								
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											
											<select class="form-control" name="toursend" id="toursend">
											<option value="">- 샌딩 상품을 선택하세요 -
											<?php echo getProductSending($reserve_send['p_code']); ?>
											
										</select>
										</div>
									</div>
								</div>
							</td>
							<td colspan="4">
								<!-- <input type="text" class="form-control" placeholder="출발도시"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">샌딩출발도시</span>
											<input type="text" name="stcity" class="form-control " aria-label="출발도시" value="<?=$reserve_info['air_stcity']?>"/>
										</div>
									</div>
								</div>
							</td>
							<td colspan="2">
								<!-- <input type="text" class="form-control" placeholder="출발일"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<input type="date" name="departureDate" class="form-control js-dateInput2 js-departureDate" aria-label="출발일" placeholder="출발일" value="<?=$reserve_info['air_stdate']?>">
											<!--<span class="input-group-btn">
												<button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
											</span>-->
										</div>
									</div>
								</div>
							</td>
							<td colspan="1">
								<!-- <input type="text" class="form-control js-airTimes" placeholder="출발시간"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<input type="text" name="departureTime" class="form-control" aria-label="출발시간" placeholder="출발시간" value="<?=$reserve_info['air_sttime']?>">
											
										</div>
									</div>
								</div>
							</td>
							<td colspan="3">
								<!-- <input type="text" class="form-control" placeholder="항공사/편명"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">항공사/편명</span>
											<input type="text" name="departureairname" class="form-control" aria-label="항공사/편명" value="<?=$reserve_info['air_stNm']?>"/>
										</div>
									</div>
								</div>
							</td>
							<td colspan="5">
								<!-- <input type="text" class="form-control" placeholder="메모"> -->
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">메모</span>
											<input type="text" name="departurememo" class="form-control" aria-label="메모" value="<?=$reserve_info['air_stMemo']?>"/>
										</div>
									</div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
							<td colspan="16" class="active text-center formHeader fullWidth">관련예약 &nbsp;&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-default btn-xs js-addtour" <?php if (!$estimateCode) {?>disabled  <?php } ?> >투어추가 <span class="glyphicon glyphicon-plus" aria-hidden="true" ></span></button> <!--&nbsp;<button type="button" class="btn btn-default btn-xs js-addhotel" <?php if (!$estimateCode) {?> disabled  <?php } ?>>호텔추가 <span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>--></td>
						</tr>
					    <?php
									$qry1 = "select * from reserve_info where grand_revNo = '{$reserve_info['grand_revNo']}' && reserveCode != '$estimateCode' && parent='MAIN'  order by revDate asc";
									//echo $qry1;
									$rst1 = mysql_query($qry1);
									
									while($rr_row = mysql_fetch_assoc($rst1)):
									    if ($rr_row['tour_type']== '1') {
											
											$ty=1;
											$pricet=1;
											$sub=15;
										} else if ($rr_row['tour_type']== '2') {
											
											$ty=2;
											$pricet=2;
											$sub=20;
										} else if ($rr_row['tour_type']== '3') {
											
											$ty=3;
											$pricet=3;
											$sub=25;
										}
										if ($rr_row['rev_status']== 'READY') {
											$rr_row['rev_status'] = "<font color=#0984a3>예약접수</font>";
										}
										if ($rr_row['rev_status']== 'DONE') {
											$rr_row['rev_status'] = "<font color=#03923a>예약확정</font>";
										}
										
										if ($rr_row['rev_status']== 'CANCEL') {
											$rr_row['rev_status'] = "<font color=#e02133>예약취소</font>";
										}
						 ?>
										<tr>
											<td colspan="2" class="active text-center formHeader">출발일</td>
											<td colspan="2"><?=$rr_row['stDate']?></td>
											<td colspan="4"><a href="base_reservation_m.php?estimateCode=<?=$rr_row['reserveCode']?>&division=3&pdx=2&sub=<?=$sub?>&ty=<?=$ty?>&pricet=<?=$pricet?>">[<?=$rr_row['p_code']?>] <?=$rr_row['p_name']?></a></td>
											<td colspan="2" class="active text-center formHeader">예약번호</td>
											<td colspan="3"><?=$rr_row['reserveCode']?></td>
											<td colspan="3"><?=$rr_row['rev_status']?></td>
										</tr>
						<?php
						           endwhile;
								
						?>

						<?php
									$qry1 = "select * from reserve_hotel where grand_revNo = '{$reserve_info['grand_revNo']}' && reserveCode != '$estimateCode'  order by reserve_date asc";
									//echo $qry1;
									$rst1 = mysql_query($qry1);
									
									while($rr_row = mysql_fetch_assoc($rst1)):
										$prodHInfo = getProductHMaster($rr_row['h_code']);
										
						 ?>
										<tr>
											<td colspan="2" class="active text-center formHeader">출발일</td>
											<td colspan="2"><?=$rr_row['start_date']?></td>
											<td colspan="4"><a href="hotel_reservation_m.php?estimateCode=<?=$rr_row['reserveCode']?>&division=3&pdx=<?=$pdx?>&sub=<?=$sub?>">[<?=$rr_row['h_code']?>] <?=$prodHInfo['h_name']?></a></td>
											<td colspan="2" class="active text-center formHeader">예약번호</td>
											<td colspan="6"><?=$rr_row['reserveCode']?></td>
										</tr>
						<?php
						           endwhile;
								
						?>
					</tbody>
				</table>
				<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
							<td colspan="16" class="active text-center formHeader fullWidth">결제정보</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">항공료총금액</td>
							<td colspan="14"> U$ <span id="totairdisplay"> <?=number_format($totamtair,2)?></span>  <input type="hidden" name="tot_air_amt" id="tot_air_amt" class="inpubase sm1" placeholder="항공료총금액" value="<?=number_format($totamtair,2)?>"/>
							
							</td>
						<tr>
							<td colspan="2" class="active text-center formHeader">총판매가</td>
							<td colspan="14"><span id="totamt"><?=$uamt1?></span><input type="hidden" name="ttamt" id="ttamt" value="<?= $uamt ?>"></td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">총할인가</td>
							<td colspan="14"><span id="totdis"><?=$damt1?></span><input type="hidden" name="ttotdis" id="ttotdis" value="<?= $damt ?>"></td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">총추가납부</td>
							<td colspan="6"><span id="totaddamt"><?=$aamt1?></span><input type="hidden" name="ttotaddamt" id="ttotaddamt" value="<?= $aamt ?>"></td>
							<td colspan="2" class="active text-center formHeader">결제상태</td>
							<td colspan="2" class="no-right-border"></td>
							<td colspan="4" class="no-left-border">
								<div class="row no-nav">
									<div class="col-sm-12">
										<ul class="pagination non-nav flex-justify-flex-end">
											<li <?php if ($reserve_info['payment_st']=='READY') { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>><span>미납</span></li>
											<li <?php if ($reserve_info['payment_st']=='PPAY') { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>><span>부분완납</span></li>
											
											<li <?php if ($reserve_info['payment_st']=='DONE') { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>class="disabled"><span>완납
											<li <?php if ($reserve_info['payment_st']=='OPAY') { ?> class="active" <?php } else { ?> class="disabled" <?php } ?>class="disabled"><span>환불
										</ul>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">최종결제금액</td>
							<td colspan="3" class="no-right-border"><span id='gtotamt'><?=$tamt1?></span><input type="hidden" name="tgtotamt" id="tgtotamt" value="<?= $tamt ?>"></td>
							<td colspan="3" class="no-left-border">
								<div class="row">
									<div class="col-sm-6">
									    
										<button type="button" class="btn btn-xs btn-block btn-default js-makePayment" data-toggle="modal" data-target=".js-openPaymentProcess" <?php if (!$estimateCode) {?>disabled  <?php } ?>>결제하기</button>
									</div>
									<div class="col-sm-6">
										<button type="button" class="btn btn-xs btn-block btn-default js-calculateFinalAmount" <?php if (!$estimateCode) {?>disabled  <?php } ?> onClick="javascript:aircalc_ticket();">최종계산</button>
									</div>
								</div>
							</td>
							<td colspan="2" class="active text-center formHeader">결제메모</td>
							<td colspan="5" class="no-right-border"><input type="text" class="form-control" name="paymemo" value="<?=$reserve_info['pay_memo']?>"></td>
							<td colspan="1" class="no-left-border">
								
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">잔액</td>
							<td colspan="6" title="오버페이번트시 수정"><span id='balamt'><?=$bamt1?></span><input type="hidden" name="tbalamt" id="tbalamt" value="<?= $bamt ?>"><input type="hidden" name="baltype" id="baltype" value=""><span id='balmemo'></span></td>
							<td colspan="2" class="active text-center formHeader">환불신청</td>
							<td colspan="3" class="no-right-border">
								<div class="row">
									<div class="col-sm-6">
										<button type="button" class="btn btn-xs btn-block btn-default js-makeReturn" data-toggle="modal" data-target=".js-openPaymentReturn" >환불신청</button>
									</div>
									
								</div>
							</td>
							<td colspan="3" class="no-left-border">
								<div class="row no-nav">
									<div class="col-sm-12">
										<ul class="pagination non-nav flex-justify-flex-end">
											<li <?php if ($Return_info['payment_status'] == "RRQUEST") { ?> class="active" <?php } else { ?> class="disabled" <?php } ?> ><span>신청</span></li>
											<li <?php if ($Return_info['payment_status'] == "RETURN") { ?> class="active" <?php } else { ?> class="disabled" <?php } ?> ><span>완료</span></li>
										</ul>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">잔액수금방법</td>
							<td colspan="6">
								<select class="form-control" name="rmethod">
									<option value="">- 수금방법 선택하세요 -</option>
									<option value="colltype1">현지수금</option>
									<option value="colltype2">주간정산</option>
									<option value="colltype3" selected>즉시결제</option>
								</select>
							</td>
							<td colspan="2" class="active text-center formHeader">환불금액</td>
							<td colspan="6"><span id='returnamt'></span></td>
						</tr>
					</tbody>
				</table>
				
				<div class="row">
					<div class="col-sm-6 col-sm-offset-6 text-right">
						<?php if ($estimateCode) { ?>
							<button type="button" class="btn btn-xs btn-default js-rr" onClick="go_submit()">예약저장</button>
							
							<button type="button" <?php if (($reserve_info['rev_status']=='DONE') || ($reserve_info['rev_status']=='CANCEL') && ($reserve_info['rev_status']!=''))  { ?> class="btn btn-xs btn-default js-done disabled" disabled <?php } else { ?> class="btn btn-xs btn-default js-ccr" <?php } ?> onClick="go_corder()">예약확정</button>
							<button type="button" class="btn btn-xs btn-default js-can" onClick="go_cancel()">예약취소</button>
							<button type="button" class="btn btn-xs btn-default js-prn" onClick="javascript:openwin('<?=$estimateCode?>')">영수증 출력</button>
							<button type="button" class="btn btn-xs btn-default js-prn2" onClick="javascript:openwin2('<?=$estimateCode?>')">아웃바운드요청</button>
					  <?php } else { ?>
					        <button type="button" class="btn btn-xs btn-default js-rr" onClick="go_submit()">예약접수</button>
							
							<button type="button" class="btn btn-xs btn-default js-done disabled" disabled  class="btn btn-xs btn-default js-ccr" onClick="go_corder()">예약확정</button>
							<button type="button" class="btn btn-xs btn-default js-can" disabled onClick="go_cancel()">예약취소</button>
							<button type="button" class="btn btn-xs btn-default js-prn" disabled>영수증 출력</button>
							<button type="button" class="btn btn-xs btn-default js-prn2" disabled>아웃바운드요청</button>
							
					   <?php } ?>
					</div>
				</div>
			</form>





			<div class="modal fade js-openPaymentProcess" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
				<div class="modal-dialog modal-lg modal-full-width" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
							<h4 class="modal-title" id="gridSystemModalLabel">온라인결제</h4>
						</div>
						<div class="modal-body">
							<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&ty=<?=$ty?>&pcode=<?=$pcode?>&pricet=<?=$pricet?>" name="frmpayment" id="frmpayment" method="post">
								<input type="hidden" name="mode" value="paymentProcess">
								<input type="hidden" name="pcode" value="<?= $pcode ?>">
								<input type="hidden" name="lasttotal" value="<?=$reserve_info['last_total']?>">
						         <input type="hidden" name="lastbalance" id="lastbalance" value="<?=$reserve_info['last_bal']?>">
								<input type="hidden" name="grestimateCode" value="<?= $grestimateCode ?>">
								<input type="hidden" name="estimateCode" id="estimateCode" value="<?= $estimateCode ?>">
								<input type="hidden" name="pname" value='<?= $prodInfo['p_name'] ?>'>
								<input type="hidden" name="cday" id="cday" value="<?= $pday ?>">
								<input type="hidden" name="ttype" id="ttype" value="<?= $ty ?>">
								<input type="hidden" name="brate" id="brate" value="<?= $prodInfo['base_rate'] ?>">

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
													<td colspan="6"><span id="lastpay"><?=$sign?> <?=$reserve_info['last_total']?></span></td>
													<td colspan="2" class="active text-center formHeader">잔금</td>
													<td colspan="6"><span id="balpay"><?=$sign?> <?=$reserve_info['last_bal']?></span></td>
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
															$qry1 = "select * from payment_history where reserveCode = '{$reserve_info['reserveCode']}' && payment_status !='RRQUEST' 
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
												<!--<tr>
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
												</tr>-->
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



			<div class="modal fade js-openPaymentReturn" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
				<div class="modal-dialog modal-lg modal-full-width" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
							<h4 class="modal-title" id="gridSystemModalLabel">환불결제</h4>
						</div>
						<div class="modal-body">
							<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&ty=<?=$ty?>&pcode=<?=$pcode?>&pricet=<?=$pricet?>" name="frmRpayment" id="frmRpayment" method="post">
								<input type="hidden" name="mode" value="paymentReturn">
								<input type="hidden" name="pcode" value="<?= $pcode ?>">
								<input type="hidden" name="lasttotal" value="<?=$reserve_info['last_total']?>">
						         <input type="hidden" name="lastbalance" id="lastbalance"value="<?=$reserve_info['last_bal']?>">
								<input type="hidden" name="grestimateCode" value="<?= $grestimateCode ?>">
								<input type="hidden" name="estimateCode" id="estimateCode" value="<?= $estimateCode ?>">
								<input type="hidden" name="pname" value='<?= $prodInfo['p_name'] ?>'>
								<input type="hidden" name="cday" id="cday" value="<?= $pday ?>">
								<input type="hidden" name="ttype" id="ttype" value="<?= $ty ?>">
								<input type="hidden" name="brate" id="brate" value="<?= $prodInfo['base_rate'] ?>">

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
													<td colspan="6"><span id="lastpay"><?=$sign?> <?=$reserve_info['last_total']?></span></td>
													<td colspan="2" class="active text-center formHeader">잔금</td>
													<td colspan="6"><span id="balamtpay"><?=$sign?> <?=$reserve_info['last_bal']?></span></td>
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
													<td colspan="2" class="active text-center formHeader">신청금액</td>
													
													<td colspan="1" class="active text-center formHeader">결제상태</td>
													<td colspan="1" class="active text-center formHeader">결제자</td>
													<td colspan="1" class="active text-center formHeader">결제</td>
													<td colspan="2" class="active text-center formHeader">결제메모</td>
												</tr>
												<?php
															$qry1 = "select * from payment_history where reserveCode = '{$reserve_info['reserveCode']}' && (payment_status ='RRQUEST' || payment_status ='RETURN')
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
																		    $cappay = "환불신청";
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
																	if ($p_row['payment_status'] == "RRQUEST")  {
																		$p_row['payment_status']="환불신청";
																	}
																	if ($p_row['payment_status'] != "RETURN") {
												 ?>
																		<tr>
																			<td colspan="2" class="text-center"><?=$p_row['wdate']?></td>
																			<td colspan="1" class="text-center"><?=$cappay?></td>
																			<td colspan="2" class="text-center"><?=$p_row['pay_info']?></td>
																			<td colspan="2" class="text-center"><?=$sign?> <?=$p_row['payment']?></td>
																			
																			<td colspan="1" class="text-center"><?=$p_row['payment_status']?></td>
																			<td colspan="1" class="text-center"><?=$uinfo['kor_name']?></td>
																			<td colspan="1" class="text-center"><button type="button" class="btn btn-xs btn-block btn-default js-rdel" value="<?=$p_row['seq_no']?>">신청삭제</button></td>
																			<td colspan="2" class="text-center"><?=$p_row['pay_memo']?></td>
																		</tr>
														
												 <?php
																	} else {
												 ?>
																		<tr>
																			<td colspan="2" class="text-center"><?=$p_row['wdate']?></td>
																			<td colspan="1" class="text-center"><?=$cappay?></td>
																			<td colspan="2" class="text-center"><?=$p_row['pay_info']?></td>
																			<td colspan="2" class="text-center">- <?=$sign?> <?=$p_row['payment']?></td>
																			
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
																		<td colspan="1" class="text-center"></td>
																		<td colspan="1" class="text-center"></td>
																		<td colspan="2" class="text-center"></td>
																</tr>

												<?php
													}
												?>


												
											</tbody>
										</table>
										<table class="table table-bordered table-condensed gridSixteen paymentProcessingTable formDetail js-RpaymentProcess">
											<tbody>
												
												<tr>
													<td colspan="2" class="active text-center formHeader">환불방법</td>
													<td colspan="4" class="no-right-border">
														<select class="form-control js-paymentType" name="paymentmethod2" id="paymentmethod2">
															<option value="">- 환불방법 선택하세요 -</option>
															<option value="cash">현금</option>
															<option value="debit">데빗</option>
															<option value="creditcard">신용카드</option>
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
										
										<table class="table table-bordered table-condensed gridSixteen paymentProcessingTable formDetail js-paymentOther">
											<tbody>
												
												<tr>
													<td colspan="2" class="active text-center formHeader">환불금액 <span id="psign2"><font color="red"></font></span></td>
													<td colspan="6" cl>
														<input type="text" name="rpay2" id="rpay2" class="form-control" placeholder="결제금액" value="">
														<input type="hidden" name="opay2" id="opay2" class="form-control" placeholder="환불금액" value="">
													</td>
													
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">환불메모</td>
													<td colspan="6">
														<input type="text" class="form-control" name="dmemo2" placeholder="결제메모">
													</td>
													<td colspan="2" class="active text-center formHeader">환불신청자</td>
													<td colspan="6">
														<input type="text" class="form-control" name="puser2" placeholder="결제자" value='<?=$user_dbinfo['userid']?>'>
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
									  js-RRprocessPayment" onClick="go_Rpay()">환불신청하기</button>
							
							<button type="button" class="btn btn-default" data-dismiss="modal">뒤로가기</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->
			
			<div id="modal" class="modal fade js-openSingleDayTourModal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
				<div class="modal-dialog modal-lg modal-in-modal" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="gridSystemModalLabel">단일투어</h4>
						</div>
						<div class="modal-body">
							<div class="row">
								<div class="col-sm-12">
									<input type="text" class="form-control removeBottomMargin js-searchSingleDayTour" name="sskeyword" placeholder="검색">
								</div>
							</div>
							<div class="row overflowBody">
								<div class="col-sm-12">
								<?php
								    
								   $qry1 = "select * from product_master where 1=1 && p_type='1' order by p_name asc ";
								   $rst1 = mysql_query($qry1,$dbConn);
									//echo $qry1;	
								   while($row1 = mysql_Fetch_assoc($rst1)){
								?>
										 <div class="radio">
											<label><!-- data-search-str needs to be in all lower case -->
												<input type="radio" name="singleDayTour[]" value="<?=$row1['p_code']?>" data-tour-name='<?=$row1['p_name']?>' data-tour-code="<?=$row1['p_code']?>" data-search-str='<?=$row1['p_code']?> <?=$row1['p_name']?>'>
												[<?=$row1['p_code']?>] <?=$row1['p_name']?>
											</label>
										</div>
								<?php
								   }
								?>
									
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
							<button type="button" class="btn btn-primary js-saveSelection">선택사항 저장</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->

		</div>
	</div>
    <?php
		include "include/side_m.php"
		
	?>
	<script src="ckeditor/ckeditor.js"></script>
    <script>
		$(document).ready(function () {
			$.ajaxSetup({async: false});
			//$("#room_num").keydown(function (e) {
			var o='<?=$i?>';
			$(document).on('keydown','#example1 > tbody tr #room_num',function (e) {
				var cellindex = $(this).parents('td').index();

				if (e.which == 40) {
					$(e.target).closest('tr').nextAll('tr').find('td').eq(cellindex).find(':text').focus();
				}
				if (e.which == 38) {
					$(e.target).closest('tr').prevAll('tr').first().find('td').eq(cellindex).find(':text').focus();
				}
				if (e.which == 39) { // right arrow
				  $(this).closest('td').next().find('input').focus();
		 
				} 
				if (e.which == 37) { // left arrow
				  $(this).closest('td').prev().find('input').focus();
		 
				} 

			});
			$(document).on('keydown','#example1 > tbody tr #t_name',function (e) {
				var cellindex = $(this).parents('td').index();

				if (e.which == 40) {
						  $(e.target).closest('tr').nextAll('tr').find('td').eq(cellindex).find(':text').focus();
				}
				if (e.which == 38) {
				$(e.target).closest('tr').prevAll('tr').first().find('td').eq(cellindex).find(':text').focus();
				}
				if (e.which == 39) { // right arrow
				  $(this).closest('td').next().find('input').focus();
		 
				} 
				if (e.which == 37) { // left arrow
				  $(this).closest('td').prev().find('input').focus();
		 
				} 

			});
			
			$(".meetcls").chosen({
					
			});
			$(".rand").chosen({
					
			});
			$(".comp").chosen({
					
			});
			$(".comp2").chosen({
					
			});
			$(".sarea").chosen({
					
			});
			$.ajaxSetup({async: false});
			CKEDITOR.on('instanceReady', function (ev) {
					ev.editor.dataProcessor.htmlFilter.addRules(
					{
						elements:
						{
							$: function (element) {
								// check for the tag name
								if (element.name == 'img') {
									var style = element.attributes.style;
									element.addClass("img-responsive");
								   // remove style tag if it exists
									if (style) {
										delete element.attributes.style;
									}
								}

								// return element without style attribute
								return element;
							}
						}
					});
				}); 
				
				
				
			CKEDITOR.replace( 'pmemo', {
					extraPlugins : 'simpleuploads',
					filebrowserUploadUrl: 'upload.php',
					allowedContent : true,
					enterMode:'2',
					height : '495px',
					
			} )
			pt.initReservationDetail()
			{
				
				var scope = $('.reservationDetailForm')
				for (var i = 0; i < scope.length; i++) {
					var self = $(scope[i])
					var tourStartDate = self.find('.js-tourStartDate')
					var tourEndDate = self.find('.js-tourEndDate')
					var localDate = self.find('.js-singleDayTourDate1')
					var singleDateTourDate1 = self.find('.js-singleDayTourDate1')
					var dateStday = new Date('<?=$startfrom?>')
					var dateToday = new Date()

					
					//console.log(exarr);
					//alert(endmonth);
					if ((('<?=$startfrom?>'=="") || (dateStday < dateToday)) && ('<?=$reserve_info[edDate]?>' == ""))
					{
						    var date = new Date();
							date.setDate(date.getDate());
						    
						    tourStartDate.datepicker($.extend({}, pt.defaults.datepicker, {
							daysOfWeekEnabled: [
								<?php echo $startWeekPrint; ?>
							],
							datesDisabled: [
								<?php echo $LimitdatePrint; ?>
							],
							datesEnabled: [    // to override datesDisabled
								<?php echo $SetdatePrint; ?>
							],
							st: '<?=$startfrom?>',
							et: '<?=$endto?>',
							//"setDate": date,
							//startDate: date,
							//startDate: '<?=$startfrom?>',
							//endDate: '<?=$endto?>',
							todayHighlight: true,
							
							beforeShowDay: function (date) {
								var enabled = pt.beforeShowDayFunc(date, this)
								return enabled
							},
						})).off('changeDate').on('changeDate', { self: self }, pt.changeTourStartDate)
						tourEndDate.prop({ "readOnly": false })
						.closest('.input-group').find('button').prop({ disabled: false })
								
				} else {

							
							tourStartDate.datepicker($.extend({}, pt.defaults.datepicker, {
							daysOfWeekEnabled: [
								<?php echo $startWeekPrint; ?>
							],
							datesDisabled: [
								<?php echo $LimitdatePrint; ?>
							],
							datesEnabled: [    // to override datesDisabled
								<?php echo $SetdatePrint; ?>
							],
							//"setDate": '<?=$startfrom?>',
							st: '<?=$startfrom?>',
							et: '<?=$endto?>',
							todayHighlight: true,
							
							//startDate: '<?=$startfrom?>',
							//endDate: '<?=$endto?>',
							
							beforeShowDay: function (date) {
								var enabled = pt.beforeShowDayFunc(date, this)
								return enabled
							},
						})).off('changeDate').on('changeDate', { self: self }, pt.changeTourStartDate)
						tourEndDate.prop({ "readOnly": false })
						.closest('.input-group').find('button').prop({ disabled: false })
					}
					
					tourEndDate.datepicker($.extend({}, pt.defaults.datepicker, {
						daysOfWeekEnabled: [
							
						],
						datesDisabled: [
							
						],
						datesEnabled: [    // to override datesDisabled
							
						],
						
						beforeShowDay: function (date) {
							
							return true
						},
					}))
					/*singleDateTourDate1.datepicker($.extend({}, pt.defaults.datepicker, {
						daysOfWeekEnabled: [
							
						],
						datesDisabled: [
							
						],
						datesEnabled: [    // to override datesDisabled
							
						],
						
						beforeShowDay: function (date) {
							var enabled = pt.beforeShowDayFunc(date, this)
							return enabled
						},
					}))*/
				

					
				    /*	
					var pickRoomType = self.find('.js-pickRoomType')
					
					pickRoomType.off('change').on('change', function (e) {
						var prices = {
							_1r1p: {
								adult: <?=$prodInfo[price_1adult]?>,
								child: <?=$prodInfo[price_1child]?>,
								partnerAdult: <?=$prodInfo[price_1cadult]?>,
								partnerChild: <?=$prodInfo[price_1cchild]?>,
							},
							_1r2p: {
								adult: <?=$prodInfo[price_2adult]?>,
								child: <?=$prodInfo[price_2child]?>,
								partnerAdult: <?=$prodInfo[price_2cadult]?>,
								partnerChild: <?=$prodInfo[price_2cchild]?>,
							},
							_1r3p: {
								adult: <?=$prodInfo[price_3adult]?>,
								child: <?=$prodInfo[price_3child]?>,
								partnerAdult: <?=$prodInfo[price_3cadult]?>,
								partnerChild: <?=$prodInfo[price_3cchild]?>,
							},
							_1r4p: {
								adult: <?=$prodInfo[price_4adult]?>,
								child: <?=$prodInfo[price_4child]?>,
								partnerAdult: <?=$prodInfo[price_4cadult]?>,
								partnerChild: <?=$prodInfo[price_4cchild]?>,
							},
							_1r5p: {
								adult: <?=$prodInfo[price_5adult]?>,
								child: <?=$prodInfo[price_5child]?>,
								partnerAdult: <?=$prodInfo[price_5cadult]?>,
								partnerChild: <?=$prodInfo[price_5cchild]?>,
							},
						}
						var section = $(this).closest('table.innerTable')
						var dropdown = section.find('.js-tourPriceDropdown')
						
						var roomTypeSelected = $(this).val()
						if (roomTypeSelected) {
							var price = prices['_'+roomTypeSelected]
							if ((('<?=$pricet?>'=='1') || ('<?=$reserve_info[tour_type]?>' == '1')) && ('<?=$pricet?>' !='3'))
							{
								if ('<?=$prodInfo[base_rate]?>'=="CAD")
								{
									var priceOptions = $('' +
									'<li><a href="#" data-price="' + price.adult + '">일반성인: C$' + price.adult + '</a></li>' +
									'<li><a href="#" data-price="' + price.child + '">일반어린이: C$' + price.child + '</a></li>' +
									
									'')
								} else {

									var priceOptions = $('' +
									'<li><a href="#" data-price="' + price.adult + '">일반성인: $' + price.adult + '</a></li>' +
									'<li><a href="#" data-price="' + price.child + '">일반어린이:$' + price.child + '</a></li>' +
									
									'')

								}
							} else if (('<?=$pricet?>'=='3') || ('<?=$reserve_info[tour_type]?>' == '3')) {
								if ('<?=$prodInfo[base_rate]?>'=="CAD")
								{

									var priceOptions = $('' +
									
									'<li><a href="#" data-price="' + price.partnerAdult + '">협력사성인: C$' + price.partnerAdult + '</a></li>' +
									'<li><a href="#" data-price="' + price.partnerChild + '">협력사어린이: C$' + price.partnerChild + '</a></li>' +
									'')
								} else {

									var priceOptions = $('' +
									
									'<li><a href="#" data-price="' + price.partnerAdult + '">협력사성인:$' + price.partnerAdult + '</a></li>' +
									'<li><a href="#" data-price="' + price.partnerChild + '">협력사어린이:$' + price.partnerChild + '</a></li>' +
									'')

								}

							}
							dropdown.empty().append(priceOptions)
							dropdownbtn.attr('aria-expanded', true)
							priceOptions.find('a').off('click').on('click', function (e) {
								
								var selectedPrice = parseFloat($(this).data('price'))
								var additionalChargeAmt = parseFloat(section.find('.js-additionalCharge').val() || 0)
								var discountAmt = parseFloat(section.find('.js-discount').val() || 0)
								var totalPerPerson = section.find('.js-totalPerPerson')
								var sellPrice = section.find('.js-pickPrice')
							    var totamt = self.find("#totamt")
								e.preventDefault()
								totalPerPerson.val(selectedPrice + additionalChargeAmt - discountAmt)
								
								sellPrice.val(selectedPrice)
								calc(1)
							})
						} else {
							dropdown.empty()
						}
					})
					var pickPrice = self.find('.js-pickPrice')
					pickPrice.off('click').on('click', function (e) {
						
					})
					*/
					var tourPricePick = self.find('.js-tourPricePick')
					$(document).on('click', '.js-tourPricePick', function(e){ 
					//tourPricePick.off('click').on('click', function (e) {
						
						if ((('<?=$pricet?>'=='1') || ('<?=$reserve_info[tour_type]?>' == '1')) && ('<?=$pricet?>' !='3'))
						{	
							var prices = {
								adult0: <?=$prodInfo['price_0adult']?>,
								adult1: <?=$prodInfo['price_1adult']?>,
								adult2: <?=$prodInfo['price_2adult']?>,
								adult3: <?=$prodInfo['price_3adult']?>,
								adult4: <?=$prodInfo['price_4adult']?>,
								adult5: <?=$prodInfo['price_5adult']?>,
								child0: <?=$prodInfo['price_0child']?>,
								child1: <?=$prodInfo['price_1child']?>,
								child2: <?=$prodInfo['price_2child']?>,
								child3: <?=$prodInfo['price_3child']?>,
								child4: <?=$prodInfo['price_4child']?>,
								child5: <?=$prodInfo['price_5child']?>,

							}
						} else if (('<?=$pricet?>'=='3') || ('<?=$reserve_info[tour_type]?>' == '3')) {
						
							var prices = {
								adult0: <?=$prodInfo['price_0cadult']?>,
								adult1: <?=$prodInfo['price_1cadult']?>,
								adult2: <?=$prodInfo['price_2cadult']?>,
								adult3: <?=$prodInfo['price_3cadult']?>,
								adult4: <?=$prodInfo['price_4cadult']?>,
								adult5: <?=$prodInfo['price_5cadult']?>,
								child0: <?=$prodInfo['price_0cchild']?>,
								child1: <?=$prodInfo['price_1cchild']?>,
								child2: <?=$prodInfo['price_2cchild']?>,
								child3: <?=$prodInfo['price_3cchild']?>,
								child4: <?=$prodInfo['price_4cchild']?>,
								child5: <?=$prodInfo['price_5cchild']?>,

							}
						}



							
							
						
						var section = $(this).closest('.innertr')
						var dropdown = section.find('.js-tourPriceDropdown')
						
					
						if ('<?=$prodInfo[base_rate]?>'=="CAD")
						{
							var priceOptions = $('' +
							'<li><a href="#" data-price="' + prices.adult0 + '" data-att="0r1p/G">성인: C$' + prices.adult0 + ' (당일)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult1 + '" data-att="1r1p/G">성인: C$' + prices.adult1 + ' (1인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult2 + '" data-att="1r2p/G">성인: C$' + prices.adult2 + ' (2인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult3 + '" data-att="1r3p/G">성인: C$' + prices.adult3 + ' (3인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult4 + '" data-att="1r4p/G">성인: C$' + prices.adult4 + ' (4인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult5 + '" data-att="1r5p/G">성인: C$' + prices.adult5 + ' (5인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child0 + '" data-att="0r1p/I">어린이: C$' + prices.child0 + ' (당일)</a></li>' +
							'<li><a href="#" data-price="' + prices.child1 + '" data-att="1r1p/I">어린이: C$' + prices.child1 + ' (1인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child2 + '" data-att="1r2p/I">어린이: C$' + prices.child2 + ' (2인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child3 + '" data-att="1r3p/I">어린이: C$' + prices.child3 + ' (3인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child4 + '" data-att="1r4p/I">어린이: C$' + prices.child4 + ' (4인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child5 + '" data-att="1r5p/I">어린이: C$' + prices.child5 + ' (5인1실)</a></li>' +
							
							'')
						} else {

							var priceOptions = $('' +
							'<li><a href="#" data-price="' + prices.adult0 + '" data-att="0r1p/G">성인: U$' + prices.adult0 + ' (당일)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult1 + '" data-att="1r1p/G">성인: U$' + prices.adult1 + ' (1인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult2 + '" data-att="1r2p/G">성인: U$' + prices.adult2 + ' (2인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult3 + '" data-att="1r3p/G">성인: U$' + prices.adult3 + ' (3인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult4 + '" data-att="1r4p/G">성인: U$' + prices.adult4 + ' (4인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.adult5 + '" data-att="1r5p/G">성인: U$' + prices.adult5 + ' (5인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child0 + '" data-att="0r1p/I">어린이: U$' + prices.child0 + ' (당일)</a></li>' +
							'<li><a href="#" data-price="' + prices.child1 + '" data-att="1r1p/I">어린이: U$' + prices.child1 + ' (1인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child2 + '" data-att="1r2p/I">어린이: U$' + prices.child2 + ' (2인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child3 + '" data-att="1r3p/I">어린이: U$' + prices.child3 + ' (3인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child4 + '" data-att="1r4p/I">어린이: U$' + prices.child4 + ' (4인1실)</a></li>' +
							'<li><a href="#" data-price="' + prices.child5 + '" data-att="1r5p/I">어린이: U$' + prices.child5 + ' (5인1실)</a></li>' +
							
							'')

						}
						

						
						dropdown.empty().append(priceOptions)
						
						priceOptions.find('a').off('click').on('click', function (e) {
							var att =$(this).data('att')
							var arr = att.split("/")
							var row= $(this).closest('.innertr')
							var pickPriceType = row.find('.js-pickPriceType')
							pickPriceType.val(arr[1])
							var pickRoomType = row.find('.js-pickRoomType')
							pickRoomType.val(arr[0])
							
							var pickPriceType1 = row.find('.js-pickPriceType1')
							pickPriceType1.val(arr[1])
							var pickRoomType1 = row.find('.js-pickRoomType1')
							pickRoomType1.val(arr[0])
							//alert(arr[0])
							var selectedPrice = parseFloat($(this).data('price'))
							var additionalChargeAmt = parseFloat(row.find('.js-additionalCharge').val() || 0)
							var discountAmt = parseFloat(row.find('.js-discount').val() || 0)
							var totalPerPerson = row.find('.js-totalPerPerson')
							var sellPrice = row.find('.js-pickPrice')
							var totamt = self.find("#totamt")
							e.preventDefault()
							totalPerPerson.val(selectedPrice + additionalChargeAmt - discountAmt)
							
							sellPrice.val(selectedPrice)
							calc(1)
						})
						
					})

					/////////
					var tourPriceTot = self.find('.js-additionalCharge')
					$(document).on('blur', '.js-additionalCharge', function(e){ 
					//tourPriceTot.off('blur').on('blur', function (e) {
						
						
						
						    var section= $(this).closest('.innertr')
						
					
							var selectedPrice = parseFloat(section.find('.js-totalPerPerson').val())
								
							var additionalChargeAmt = parseFloat(section.find('.js-additionalCharge').val() || 0)
							var discountAmt = parseFloat(section.find('.js-discount').val() || 0)
							var totalPerPerson = section.find('.js-totalPerPerson')
							var sellPrice = section.find('.js-pickPrice')
							var totamt = self.find("#totamt")
							e.preventDefault()
							totalPerPerson.val(parseFloat(sellPrice.val()) + parseFloat(additionalChargeAmt) - parseFloat(discountAmt))
							
							//sellPrice.val(selectedPrice)
							calc(1)
						
						
					})

					/////////
					var tourPriceDis = self.find('.js-discount')
					
					//tourPriceDis.off('blur').on('blur', function (e) {
					$(document).on('blur', '.js-discount', function(e){ 	
						
						
						    var section= $(this).closest('.innertr')
						
					
							var selectedPrice = parseFloat(section.find('.js-totalPerPerson').val())
								
							var additionalChargeAmt = parseFloat(section.find('.js-additionalCharge').val() || 0)
							var discountAmt = parseFloat(section.find('.js-discount').val() || 0)
							var totalPerPerson = section.find('.js-totalPerPerson')
							var sellPrice = section.find('.js-pickPrice')
							var totamt = self.find("#totamt")
							e.preventDefault()
							totalPerPerson.val(parseFloat(sellPrice.val()) + parseFloat(additionalChargeAmt) - parseFloat(discountAmt))
							
							//sellPrice.val(selectedPrice)
							calc(1)
						
						
					})
					$(document).on('blur', '.js-pickPrice', function(e){ 	
						
						
						    var section= $(this).closest('.innertr')
						
					
							var selectedPrice = parseFloat(section.find('.js-totalPerPerson').val())
								
							var additionalChargeAmt = parseFloat(section.find('.js-additionalCharge').val() || 0)
							var discountAmt = parseFloat(section.find('.js-discount').val() || 0)
							var totalPerPerson = section.find('.js-totalPerPerson')
							var sellPrice = section.find('.js-pickPrice')
							var totamt = self.find("#totamt")
							e.preventDefault()
							totalPerPerson.val(parseFloat(sellPrice.val()) + parseFloat(additionalChargeAmt) - parseFloat(discountAmt))
							
							//sellPrice.val(selectedPrice)
							calc(1)
						
						
					})
					
					var pickPrice = self.find('.js-pickPrice')
					///pickPrice.off('click').on('click', function (e) {
					$(document).on('click', '.js-pickPrice', function(e){ 
						calc(1)
					})
					
					var process = self.find('.js-process')
					process.off('click').on('click', function (e) {
						var paymentProcessPanel = self.find('.js-paymentProcess')
						var paymentTypeSelection = self.find('.js-paymentType')
						var paymentbtn = self.find('.js-processPayment')
							
						paymentProcessPanel.removeClass('hidden')
						$('input[name^="lastamt"]').val($(this).val())
							
						$('#psign').html("<b><font color='red'>"+"<?=$prodInfo[base_rate]?>"+"</font></b>")
						
						$('input[name^="rpay"]').val($("#lastbalance").val())
						$('input[name^="opay"]').val($("#lastbalance").val())
						$('input[name^="lastpayamt"]').val($("#lastbalance").val())
						$('input[name^="lastamt"]').val($("#lastbalance").val())
						var amtf = parseFloat($("#lastbalance").val())
							//alert(amtf);
						$('input[name^="clastpayamt"]').val(amtf)
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
			$('input[name^="clastpayamt"]').on("keyup", function() {
				$(this).val($(this).val());//.replace(/[^0-9]/g,""));
			});

			//$(document).on('click', '.pass', function(e){
				//$(".pass").datepicker({ autoclose: true});
			//});
			$( ".js-addtour" ).click(function() {
					window.open( 'base_reservation.php?grestimateCode=<?= $reserve_info[grand_revNo] ?>&estimateCode=<?= $reserve_info[reserveCode] ?>&division=3&pdx=2&sub=10&ty=1&cp=1', '_blank');
					
			});
			$( ".js-addhotel" ).click(function() {
					window.open( 'hotel_reservation_m.php?grestimateCode=<?= $reserve_info[grand_revNo] ?>&division=3&pdx=3&sub=10', '_blank');
					
			});
			$( ".js-Rprocessrate" ).click(function() {
				    
					rratecalc();
			});

			//$(".comp").chosen();
			//$(".comp2").chosen();
			
			
			$( ".js-processrate" ).click(function() {
					ratecalc();
			});
			
			$('input[type=radio][name=currencytype]').change(function() {
				ratecalc();
			});
			$('input[name^="rpay"]').keyup(function() {
				$('input[name^="opay"]').val($(this).val());
				ratecalc();

			});
			
			
			$('body').on('click','.js-removecomp',function() {
				if(confirm("삭제할까요?") == true){
					var randid = $("#tourcomp").val();
					var rev = $("#estimateCode").val();
					
					//alert(reserveCode);
					$.getJSON("comp_reset.php?randid="+randid+"&rev="+rev, function(jsonData){
										 
						 alert("수금업체가 리셋되었습니다."); 
						 location.reload();
						
						  
					});
				}
				return ;
				
			});
			
			$('body').on('click','.js-removecomp1',function() {
				if(confirm("삭제할까요?") == true){
					var randid = $("#tourcomp1").val();
					var rev = $("#estimateCode").val();
					
					//alert(reserveCode);
					$.getJSON("comp_reset1.php?randid="+randid+"&rev="+rev, function(jsonData){
										 
						 alert("지급업체가 리셋되었습니다."); 
						 location.reload();
						
						  
					});
				}
				return ;
				
			});
			
			$(document).on('click', '.js-addair', function(){ 	
				var $tableBody = $('#air').find("#airtbody"),
				$trLast = $tableBody.find(".airin2:last"),
				$trNew = $trLast.clone();

				$trLast.after($trNew);
				o++;
				$trNew.find("td:eq(0)").find("span").text("항공 인보이스#"+o);
				//zvar clonedRow = $('.airin2').find('tr:last').clone();
				
				
			});
			$(document).on('click', '.js-addlocal', function(){ 
				var $tableBody = $('#singletour').find("#sbody"),
				$trLast = $tableBody.find(".local:last"),
				$trNew = $trLast.clone();
				$trLast.after($trNew);
				///o++;
				$trNew.find("td:eq(1)").find("input").attr("readOnly",false);
				$trNew.find("td:eq(1)").find("button").attr("disabled",false);
				//zvar clonedRow = $('.airin2').find('tr:last').clone();
				
				
			});

			$(document).on('click', '.js-saveSelection', function(){
				var radioVal = $('input[name="singleDayTour[]"]:checked').val();
			    var DataVal = $('input[name="singleDayTour[]"]:checked').data("tour-name");
				var $tableBody = $('#singletour').find("#sbody"),
				$trLast = $tableBody.find(".local:last");
                $trLast.find("td:eq(2)").find("span").html(DataVal);
				$trLast.find("td:eq(2)").find("#l_p_code").val(radioVal);
				$trLast.find("td:eq(2)").find("#singleTour").val(DataVal);
				//alert(DataVal);
				$('#modal').modal('hide');
			});
			
			$(document).on('click', '.js-addTraveler1', function(){ 	
				
				var clonedRow = $('.innerTable').find('tr:last').clone();
				/*
				clonedRow.find("td:eq(5)").find("select").val($('#sexType').val());
				clonedRow.find("td:eq(7)").find("select").val($('#pickPriceType1').val());
				clonedRow.find("td:eq(8)").find("select").val($('#pickRoomType1').val());
				clonedRow.find("td:eq(9)").find("select").val($('#pickuploc').val());
				*/
				var sel = $('.innerTable').find('tr:last').find('#sexType').val()
				clonedRow.find("td:eq(5)").find("select").val(sel);
				var pick = $('.innerTable').find('tr:last').find('#pickPriceType1').val()
				clonedRow.find("td:eq(7)").find("select").val(pick);
				var room = $('.innerTable').find('tr:last').find('#pickRoomType1').val()
				clonedRow.find("td:eq(8)").find("select").val(room);
				var price = $('.innerTable').find('tr:last').find('#pickuploc').val()
				clonedRow.find("td:eq(9)").find("select").val(price);
				$('.innerTable tbody').append(clonedRow);
				
				var numTouristsElem = $('.js-numTourists')
				var numTourists = parseInt(numTouristsElem.val())
				if (numTourists >= 1) {
					numTouristsElem.val(parseInt(numTourists) + 1)
				} else {
					numTouristsElem.val(2).prop({ readonly: true })
				}
				calc(1);
			});
			$(document).on('change', '.rand', function(){ 
				var randnm = $(this).val();
				$.getJSON("rand_txt.php?randid="+randnm, function(jsonData){
					 var cHtml = "";
					 $('#r_name').empty();
					 $('#r_phone').empty();
					 $('#r_email').empty();
					
					 $.each(jsonData, function(i,data){
						 $('#r_name').val(data.company_manager);
						 $('#r_phone').val(data.company_phone);
						 $('#r_email').val(data.company_email);
					 });
					
					  
				});
				 
			})
			$(document).on('click', '.js-resetTraveler', function(){ 
				 //alert('1');
				 var btn = $(this)
				 var set = btn.closest('tr')
				 set.find(':input').val('')
				 .end().find('select > option:first-child').prop({ selected: true })
				 calc(1);
			})
			




			$(document).on('change', '.js-numTourists', function(e){ 
				//alert('1');
				var eData = e.data || {}
				var self = eData.self
				var input = $(this)
				var numTourists = input.val()
				if (numTourists > 0) {
					if (numTourists > 1) {
						var addTravelerBtns = $('.js-addTraveler1')
						input.val(1)
						for (var j = 1; j < numTourists; j++) {
							addTravelerBtns.trigger('click')
						}
					}
					input
					.off('change.js-numTourists')
					.prop({ readonly: true })
				}
				calc(1);
			})
			
			//$('.js-resetTraveler').click(function() {
				 
				
			//});
			$(".innerTable").on("click", ".js-removeTraveler", function() {
			    $(this).closest("tr").remove();
			    var numTouristsElem = $('.js-numTourists')
				numTouristsElem.val(parseInt(numTouristsElem.val()) - 1)
				calc(1);
			});
			$(document).on('click', '.js-removelocal', function(e){ 
			//$(".local").on("click", ".js-removelocal", function() {
			    $(this).closest("tr").remove();
			   
				
			});

			$(document).on('click', '.js-addairstop', function(){ 
				
				var $tableBody = $('#air').find("#airtbody"),
				$trLast = $tableBody.find(".airin2:last").find(".airsub").find(".airstop").find("#stopair"),
				$trNew = $trLast.clone();

				$trLast.after($trNew);
				o++;
				///$trNew.find("td:eq(0)").find("span").text("항공 인보이스#"+o);
			})
			$(document).on('click', '.js-reairstop', function(){ 
				
				var $tableBody = $('#air').find("#airtbody"),
				$trLast = $tableBody.find(".airin2:last").find(".airsub").find(".airstop").find("#stopair"),
                $trNew = $trLast.remove();
                
			}) 
			$('input[type=radio][name=currencytype2]').change(function() {
				rratecalc();
				$('#psign2').html("<b><font color='red'>"+"<?=$prodInfo[base_rate]?>"+"</font></b>");
			});
			$('input[name^="rpay2"]').keyup(function() {
				$('input[name^="opay2"]').val($(this).val());
				rratecalc();
				$('#psign2').html("<b><font color='red'>"+"<?=$prodInfo[base_rate]?>"+"</font></b>");

			});
			$( ".js-rate" ).click(function() {
					cratecalc();
			});
			/*$( ".discount" ).change(function() {
				
				var distxt = $(this).val();
			
				$.getJSON("cancel_txt.php?distxt="+distxt, function(jsonData){
					var cHtml = "";
					$('#dismemo').empty();
					$('#dismemo').append("<option value=''>- 취소사유를 선택하세요 -");
					 $.each(jsonData, function(i,data){
						  var code = data.lvcode1+data.lvcode2+data.lvcode3;
						  cHtml += "<option value='+code+' >"+data.comment+"";
										
					 });
					$('#dismemo').append(cHtml);
					  
				});
				
		    
					
			});
			*/
			$( ".js-approve" ).click(function() {
				var estimateCode = $("#estimateCode").val();
				var payamt = $(this).val();
			
				$.getJSON("update_return.php?reserveCode="+estimateCode+"&payamt="+payamt, function(jsonData){
					 $.each(jsonData, function(i,data){
						
						$("#frmreserve").submit();					
					 });
					  
				});
				
		    
					
			});
			$( ".js-rdel" ).click(function() {
				
				var estimateCode = $("#estimateCode").val();
				var seq = $(this).val();
			
				$.getJSON("update_rdel.php?reserveCode="+estimateCode+"&seq="+seq, function(jsonData){
					 $.each(jsonData, function(i,data){
						//alert("1");
						//$("#frmreserve").submit();					
					 });
					  
				});
				
		    
					
			});
			//$('input[name^="disamt"]').blur(function() {
			//		calc(1);
			//});
			//$('input[name^="addamt"]').blur(function() {
			//	    
			//		calc(1);
			//});
			
		})
	
	    function ratecalc() {
				
			    if ('<?=$reserve_info[base_rate]?>' == 'CAD')
				{
				   
					if ($('input[name=currencytype]:checked').val() == 'CAD') {
						var amt = parseInt($('input[name^="opay"]').val());
						var amtf = amt;
						$('input[name^="lastpayamt"]').val(amtf);
						
					}
					else if ($('input[name=currencytype]:checked').val() == 'USD') {
						var srate = parseFloat($('input[name^="sellrate"]').val());
						var amt = parseFloat($('input[name^="opay"]').val()) / srate.toFixed(4);
						var amtf = parseInt(amt);
						$('input[name^="lastpayamt"]').val(amtf);
						
					}
				} else if ('<?=$reserve_info[base_rate]?>' == 'USD') {
					if ($('input[name=currencytype]:checked').val() == 'CAD') {
						var brate = parseFloat($('.buyrate').val());
						
						var amt = parseFloat($('input[name^="opay"]').val())* brate.toFixed(4);
						var amtf = parseInt(amt);
						$('input[name^="lastpayamt"]').val(amtf);
						
						
					}
					else if ($('input[name=currencytype]:checked').val() == 'USD') {
						var amt = parseInt($('input[name^="opay"]').val());
						var amtf = amt;
						$('input[name^="lastpayamt"]').val(amtf);
						
					}
				}

		}

		function rratecalc() {
				
			    if ('<?=$reserve_info[base_rate]?>' == 'CAD')
				{
				   
					if ($('input[name=currencytype2]:checked').val() == 'CAD') {
						var amt = parseInt($('input[name^="opay2"]').val());
						var amtf = amt;
						$('input[name^="lastpayamt2"]').val(amtf);
						
					}
					else if ($('input[name=currencytype2]:checked').val() == 'USD') {
						var srate = parseFloat($('input[name^="sellrate2"]').val());
						var amt = parseFloat($('input[name^="opay2"]').val())/ srate.toFixed(4);
						var amtf = parseInt(amt);
						$('input[name^="lastpayamt2"]').val(amtf);
						
					}
				} else if ('<?=$reserve_info[base_rate]?>' == 'USD') {
					if ($('input[name=currencytype2]:checked').val() == 'CAD') {
						var brate = parseFloat($('input[name^="buyrate2"]').val());
						var amt = parseFloat($('input[name^="opay2"]').val()) * brate.toFixed(4);
						var amtf = parseInt(amt);
						$('input[name^="lastpayamt2"]').val(amtf);
						//alert(amtf+"test");
						
					}
					else if ($('input[name=currencytype2]:checked').val() == 'USD') {
						var amt = parseInt($('input[name^="opay2"]').val());
						var amtf = amt;
						$('input[name^="lastpayamt2"]').val(amtf);
					}
				}

		}
		function cratecalc() {
				if ($('input[name^="buyrate1"]').val()=="")
				{
					alert("적용환율을 입력하세요!");
					$('input[name^="buyrate1"]').focus();
					return;
				}
			    if ('<?=$reserve_info[base_rate]?>' == 'CAD')
				{
				    var amt = parseInt($('input[name^="lastamt"]').val());
					var amtf = amt;
					$('input[name^="clastpayamt"]').val(amtf);
					
					
					
				} else if ('<?=$reserve_info[base_rate]?>' == 'USD') {
					
						var brate = parseFloat($('input[name^="buyrate1"]').val());
						var amt = parseFloat($('input[name^="lastamt"]').val()) * brate.toFixed(4);
						var amtf = parseInt(amt);
						$('input[name^="clastpayamt"]').val(amtf);
						
						
					
				}

		}
		function calc(gu) {
			$.ajaxSetup({async: false});
			var unitamt = 0;
			var addamt = 0;
			var disamt = 0;
			var tamt1 = 0;
			var tamt = 0;
			var p = 0;
			$('input[name^="unitPrice"]').each(function() {
              unitamt = unitamt +  parseFloat($(this).val())
			  p++;  
            });
			var lastval= 0;
			lastval =$('input[name^="unitPrice"]').last().val();
			//alert(lastval);
			$("#totamt").html('<?=$sign?> '+ unitamt);
			$("#ttamt").val(unitamt);
			$('input[name^="addamt"]').each(function() {
              addamt = addamt +  parseFloat($(this).val())
			 
            });
			$("#totaddamt").html('<?=$sign?> '+ addamt);
			$("#ttotaddamt").val(addamt);
			
			$('input[name^="disamt"]').each(function() {
              disamt = disamt +  parseFloat($(this).val())
			    
            });
			$("#totdis").html('<?=$sign?> '+ disamt);
			$("#ttotdis").val(disamt);
			
            $('input[name^="lasttamt"]').each(function() {
             
			  tamt1 = tamt1 +  parseFloat($(this).val())
			    
            });

			var airtot = $('input[name^="tot_air_amt"]').val();
			var tttotamt = (parseFloat(unitamt)+parseFloat(addamt)+parseFloat(airtot))-parseFloat(disamt);
			//alert(airtot);
			//alert(unitamt);
			//alert(disamt);
			$("#gtotamt").html('<?=$sign?> '+ tttotamt.toFixed(2));
			$("#lastpay").html('<?=$sign?> '+ tttotamt.toFixed(2));
			$("#tgtotamt").val(tttotamt.toFixed(2));
			$("#baltype").val("");
			$("#pcnt1").val(p);
			
			$("#balamt").html("");
		    $("#balamt").html("$ "+tttotamt.toFixed(2));
			//alert(tttotamt);
		    $('input[name^="tbalamt"]').val(tttotamt.toFixed(2));
		    $("#baltype").val("1");

			var bal =0;
			var code1 = '<?=$estimateCode?>';
			$.getJSON("get_bal.php?code1="+code1+"&lastval="+disamt+"&lastval2="+addamt+"&unitamt="+unitamt+"&airamt="+airtot, function(jsonData){
	 
				 $.each(jsonData, function(i,data){
					  var unittamt  =parseFloat($("#ttamt").val());
					  var alltot = parseFloat($("#tgtotamt").val());
					  var baltmp = parseFloat(data.last_bal);
					  var lastdiff = alltot - baltmp;
					  //alert(alltot);
					  //alert(baltmp);
					  //alert(lastdiff);
					  
					  
					  
					  if ((lastdiff == 0) || (baltmp <0)) {
						  if ((baltmp <0)) {
							  var bamt0 =  '<font color=red><?=$sign?> '+baltmp+ '</font>';  //발란스
							  var bamt =  baltmp;  //발란스
						  } else {
							  var bamt0 =  '<?=$sign?> '+ baltmp;  //발란스
							  var bamt =  baltmp;  //발란스
						  }
					  } else if (lastdiff < 0) {
						  var bamt0 =  '<font color=red><?=$sign?> '+lastdiff+ '</font>';  //발란스
						  var bamt =  lastdiff;  //발란스
					  } else {
						  var bamt0 =  '<?=$sign?> '+ baltmp;  //발란스
						  var bamt =  baltmp;  //발란스
					  }
					  if (unittamt == 0)
					  {
						  //alert(alltot);
						  var bamt0 =  '<?=$sign?> '+baltmp;  //발란스
						  var bamt =  baltmp;  //발란스
					  }
					  //alert(bamt0);
					  $("#balamt").html("");
					  $("#balamt").html(bamt0);
					  $('input[name^="tbalamt"]').val(bamt);
					 /// alert(bamt0);
					  $("#lastbalance").val(lastdiff);
					  
					  $("#balpay").html(bamt0);
					  $("#baltype").val("1");

				 });
				  
			});
			if ($("#baltype").val() =="")
			{
			
				ttbalamt = parseFloat(unitamt);
				 
				
				$("#balamt").html("");
				$("#balamt").html('<?=$sign?> '+ ttbalamt);
				$('input[name^="tbalamt"]').val(ttbalamt.toFixed(2));
		
            }
			  

		}

		
		function go_submit() {
				  $.ajaxSetup({async: false});
                  if ($("#t_name").val() == "") {
						alert("여행자를 입력하세요!");
						$("#t_name").focus();
						return;
				  }
				  if ($("#startDate").val() == "") {
						alert("여행기간을 입력하세요!");
						$("#startDate").focus();
						return;
				  }
				  if ($("#r_name").val() == "") {
						alert("예약자이름을 입력하세요!");
						$("#r_name").focus();
						return;
				  }
				  if ($("#r_phone").val() == "") {
						alert("예약자 전화번호를 입력하세요!");
						$("#r_phone").focus();
						return;
				  }
				  
				  if ($("#r_email").val() == "") {
						alert("예약자 이메일을 입력하세요!");
						$("#r_email").focus();
						return ;
				  }
				  if ($("#sarea").val() == "") {
						alert("예약지역을 선택하세요!");
						$("#sarea").focus();
						return ;
				  }
				  if ($("#rpath").val() == "") {
						alert("예약경로를 선택하세요!");
						$("#rpath").focus();
						return ;
				  }

				  var isChecked1 = $('#hopt1').prop('checked');
				  var isChecked2 = $('#hopt2').prop('checked');
				  var isChecked3 = $('#hopt3').prop('checked');
				  
				  if ((isChecked1 == false ) && (isChecked2 == false ) && (isChecked3 == false )) {
						alert("숙박을 선택하세요!");
						$("input[name='hopt']").focus();
						return ;
				  }
				  var isChecked4 = $('#vopt1').prop('checked');
				  var isChecked5 = $('#vopt2').prop('checked');
				  var isChecked6 = $('#vopt3').prop('checked');

				  if ((isChecked4 == false ) && (isChecked5 == false ) && (isChecked6 == false )) {
					     alert("뷰선택을 선택하세요!");
						 $("input[name='vopt']").focus();
						 return ;
				  }
				  
				  /*
				  if ('<?=$pricet?>' == '3')
				  {
					  if ($(".comp").val() == "") {
						alert("수금협력사를 선택하세요!");
						$(".comp").focus();
						return;
					  }
					  if ($("#ramt").val() == "") {
						alert("수금협력사 금액을 입력하세요!");
						$("#ramt").focus();
						return;
					  }

					  

				  }
				  */
				  if ('<?=$estimateCode?>' == '')
				  {
					  $.getJSON("get_chkc.php", function(jsonData){
						
						 $('#chkc').empty();
						
						 $.each(jsonData, function(i,data){
							 $('#chkc').val(data.cnt);
											
						 });
						 
							  
					  });
					 // alert($("#chkc").val());
					  if ($("#chkc").val() > 0) {
							alert("다른 예약을 저장 중입니다. 잠시만 기다려주세요.");
							
							return;
					  }
				  }
				  aircalc_ticket();
				  if(confirm("예약을 저장하시겠습니까?") == true)
				  {
					   
					   $("#frmreserve").submit();
				  }
				  else return;
	
		}
		function go_submit1() {
				  $.ajaxSetup({async: false});
				  if ($("#startDate").val() == "") {
						alert("여행기간을 입력하세요!");
						$("#startDate").focus();
						return;
				  }
				  if ($("#r_name").val() == "") {
						alert("예약자이름을 입력하세요!");
						$("#r_name").focus();
						return;
				  }
				  if ($("#r_phone").val() == "") {
						alert("예약자 전화번호를 입력하세요!");
						$("#r_phone").focus();
						return;
				  }
				  if ($("#r_email").val() == "") {
						alert("예약자 이메일을 입력하세요!");
						$("#r_email").focus();
						return ;
				  }
				  if ($("#sarea").val() == "") {
						alert("예약지역을 선택하세요!");
						$("#sarea").focus();
						return ;
				  }
				  if ($("#rpath").val() == "") {
						alert("예약경로를 선택하세요!");
						$("#rpath").focus();
						return ;
				  }
				/*
				  if (('<?=$pricet?>' == '3') )
				  {
					  if ($(".comp").val() == "") {
						alert("수금협력사를 선택하세요!");
						$(".comp").focus();
						return;
					  }
					  if ($("#ramt").val() == "") {
						alert("수금협력사 금액을 입력하세요!");
						$("#ramt").focus();
						return;
					  }

					  

				  }
				  */
				  aircalc_ticket();
				  $("#frmreserve").submit();
				  return;
		}
		function go_order() {
                  $.ajaxSetup({async: false});
				  aircalc_ticket();
				  if(confirm("예약확정을 하시겠습니까?") == true)
				  {
					   $("#order_status").val("ORDER");
					   $("#frmreserve").submit();
				  }
				  else return;
	
		}

		function go_corder() {
			      $.ajaxSetup({async: false});
			      
				  if ($("#startDate").val() == "") {
						alert("여행기간을 입력하세요!");
						$("#startDate").focus();
						return;
				  }
				  if ($("#r_name").val() == "") {
						alert("예약자이름을 입력하세요!");
						$("#r_name").focus();
						return ;
				  }
				  if ($("#r_phone").val() == "") {
						alert("예약자 전화번호를 입력하세요!");
						$("#r_phone").focus();
						return ;
				  }
				  if ($("#r_email").val() == "") {
						alert("예약자 이메일을 입력하세요!");
						$("#r_email").focus();
						return ;
				  }
				  if ($("#sarea").val() == "") {
						alert("예약지역을 선택하세요!");
						$("#sarea").focus();
						return ;
				  }
				  if ($("#rpath").val() == "") {
						alert("예약경로를 선택하세요!");
						$("#rpath").focus();
						return ;
				  }
				  aircalc_ticket()
				  if(confirm("최종안내를 하시겠습니까?") == true)
				  {
					   $("#order_status").val("DONE");
					   $("#frmreserve").submit();
				  }
				  else return;
	
		}
		function go_cancel() {
               
				  
				  if(confirm("예약을 취소하시겠습니까?") == true)
				  {
					   $("#order_status").val("CANCEL");
					   $("#frmreserve").submit();
				  }
				  else return;
	
		}
		function go_guarantee() {
				  
				  if ($("#paystatus").val()=="DONE")
				  {
					  alert("결제가 완납되었습니다.");
					  return;
				  }
				  
				  if(confirm("결제보증을 하시겠습니까?") == true)
				  {
					   $("#paystatus").val("GPAY");
					   $("#frmreserve").submit();
				  }
				  else return;
	
		}
		function go_cguarantee() {
               
				  if ($("#paystatus").val()=="DONE")
				  {
					  alert("결제가 완납되었습니다.");
					  return;
				  }
				  if(confirm("결제보증을 취소 하시겠습니까?") == true)
				  {
					   $("#paystatus").val("CGPAY");
					   $("#frmreserve").submit();
				  }
				  else return;
	
		}
		function go_pay() {
              
				  if ($("#paymentmethod option:selected").val() == 'creditcard') {
					    if ('<?=$reserve_info[base_rate]?>' == 'USD') {
							if ($('input[name^="buyrate1"]').val()=="")
							{
								alert("적용환율을 입력하세요!");
								$('input[name^="buyrate1"]').focus();
								return;
							}

						}
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

		function go_Rpay() {
				if ($('input[name^="rpay2"]').val()=="")
				{
					alert("환불금액을 입력하세요!");
					$('input[name^="rpay2"]').focus();
					return;
				}
				
				if ($('input[name^="paymentmethod2"]').val()=="")
				{
					alert("환불방법을 선택하세요!");
					$('input[name^="paymentmethod2"]').focus();
					return;
				}
				  
			    if(confirm("환불신청을 하시겠습니까?") == true)
			    {
				   
				   $("#frmRpayment").submit();
			    }
			    else return;
	
		}
		var ctr=0;
        function openwin(r_code) { 
	       var winName = "all_"+(ctr++);
		   window.open("invoice_page.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code="+r_code,winName,"width=1000,height=700,scrollbars=1");
	    }
		function openwin2(r_code) { 
	       var winName = "all_"+(ctr++);
		   window.open("invoice_page2.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code="+r_code,winName,"width=1000,height=700,scrollbars=1");
	    }
		//Each Settle type of Total
		function aircalc_ticket() {
			
			
			
			/*
			var FormTypeObj = document.getElementsByName("a_settle_type[]"); 	
	
			var FormRateObj = document.getElementsByName("a_rate[]"); //rate
			var FormTaxObj = document.getElementsByName("a_tax[]"); //tax
			var FormFeeObj = document.getElementsByName("a_fee[]"); //MCO
			var FormMFeeObj = document.getElementsByName("a_fee1[]"); //MCO Fee
			var FormActObj = document.getElementsByName("a_amt_act[]"); //커미션1
			var FormComObj = document.getElementsByName("a_cms[]"); //커미션2

			
			var FormAirCntObj = document.getElementsByName("a_airport_cnt[]"); //인원
			var FormAmtObj = document.getElementsByName("a_amt[]"); //Net 
			var FormAiramtObj = document.getElementsByName("a_air_amt[]"); //항공수익 
			var FormAccObj = document.getElementsByName("a_acc_amt[]");
			var FormAccamtObj = document.getElementsByName("a_airline_amt[]"); 
			var FormAccdiviObj = document.getElementsByName("a_acc_divi[]"); 
			
			var FormRandObj = document.getElementsByName("a_rand_fee[]"); 

			var FormTotObj = document.all.air_tot; //총항공료
			*/
			var air_rate = 0.00;
			var airtax = 0.00;
			var airmco = 0.00;
			var mcofee = 0.00;
			var tamt = 0.00;
			var a_cms = 0.00;
			var a_airport_cnt=0;
			var a_amt = 0.00;
			var a_airline_amt =0.00;
			var cntpnr =$('input[name^="stdate_air"]').length;
			var amtprofit = 0;
			var Act = 0; 
			var tmpMfee = 0;
			var totairamt = 0;
            for (var i=0; i < cntpnr ; i++ )
            {
				air_rate=$('input[name^="air_rate"]').eq(i).val();
				airtax=$('input[name^="airtax"]').eq(i).val();
				airmco=$('input[name^="airmco"]').eq(i).val();
				mcofee=$('input[name^="mcofee"]').eq(i).val();
				a_cms=$('input[name^="a_cms"]').eq(i).val();
				a_amt=$('input[name^="a_amt"]').eq(i).val();
				a_airline_amt=$('input[name^="a_airline_amt"]').eq(i).val();
				a_airport_cnt=$('input[name^="air_p"]').eq(i).val();
				tmpMfee = airmco * (0.035);
				//var amttmp = a_airport_cnt * (parseFloat(air_rate) + parseFloat(airtax) + parseFloat(tmpMfee)) - (a_cms/100);
				var amttmp = parseInt(a_airport_cnt) * (parseFloat(air_rate) + parseFloat(airtax) + parseFloat(tmpMfee) - ((air_rate*(Act/100))+((air_rate-(air_rate*(Act/100)))*(a_cms/100))));
				//alert(amttmp);
				console.log("rate:"+air_rate);
				console.log("tax:"+airtax);
				console.log("amt:"+amttmp);
				$('input[name^="mcofee"]').eq(i).val(tmpMfee.toFixed(2));
				$('input[name^="a_amt"]').eq(i).val(amttmp.toFixed(2));
				amtprofit = parseFloat(a_airline_amt)- parseFloat(amttmp);
				$('input[name^="a_air_amt"]').eq(i).val(amtprofit.toFixed(2));
				totairamt = totairamt + parseFloat(a_airline_amt);
			    
            } 
			$('input[name^="tot_air_amt"]').val(totairamt.toFixed(2));
			$("#totairdisplay").html("");
			$("#totairdisplay").html(totairamt.toFixed(2));
			calc(1);
			/*
			var Acnt = 0;
			var Rate = 0;
			var Tax = 0;
			var Fee = 0;
			var MFee = 0;
			var Act = 0;
			var Com = 0;
			var tot = 0;
			var rand_fee = 0;
			
			if (FormRateObj[picnum].value!='') {
				Rate = parseFloat(FormRateObj[picnum].value);
			}
			if (FormTaxObj[picnum].value !='') {
				Tax = parseFloat(FormTaxObj[picnum].value);
			}
			if (FormFeeObj[picnum].value !='') {
				Fee = parseFloat(FormFeeObj[picnum].value);
			}
			//com 1
			if (FormActObj[picnum].value !='') {
			   Act = parseFloat(FormActObj[picnum].value);
			}
			//com 2
			if (FormComObj[picnum].value !='') {
			   Com = parseFloat(FormComObj[picnum].value);
			}
			if (FormMFeeObj[picnum].value !='') {
			   MFee = parseFloat(FormMFeeObj[picnum].value);
			}
			if (FormAirCntObj[picnum].value !='') {
			   Acnt = parseFloat(FormAirCntObj[picnum].value);
			}
			if (FormRandObj[picnum].value != '') {
			   rand_fee = parseFloat(FormRandObj[picnum].value);
			}
		 
			var tmpMfee = Fee *(0.035);
			
			var amttmp = Acnt * parseFloat(Rate + Tax + tmpMfee - ((Rate*(Act/100))+((Rate-(Rate*(Act/100)))*(Com/100))));
			FormMFeeObj[picnum].value = tmpMfee.toFixed(2);
			
			FormAmtObj[picnum].value = amttmp.toFixed(2);
		 
			
			FormAccdiviObj[picnum].value ="debit";
		
			var amtpay = 0;
			var amtprofit = 0;
			
			amtprofit = FormAccamtObj[picnum].value - amttmp;
			FormAiramtObj[picnum].value = amtprofit.toFixed(2);
		
			
			var str = 0;
			
			if (cnt > 0) { 
		
				for(var i=0; i< cnt; i++)
				{
				 
		
					str = parseFloat(str) + parseFloat(FormAccamtObj[i].value); //+ rand_fee;
		
					$("#tot_air_amt").val(str.toFixed(2));
					
		
		
				}
		
		
			}
			
							
			*/
		
			return;

		}
		///////////////////////////
		
	</script>
    </body>
</html>
