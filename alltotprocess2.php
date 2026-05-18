<?php
		 include 'include/inc_base.php';
		 header('Content-Type: application/json; charset=utf-8');

		/*
		 * Script:    DataTables server-side script for PHP and MySQL
		 * Copyright: 2010 - Allan Jardine, 2012 - Chris Wright
		 * License:   GPL v2 or BSD (3-point)
		 */
		 
		/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * Easy set variables
		 */
		 
		/* Array of database columns which should be read and sent back to DataTables. Use a space where
		 * you want to insert a non-database field (for example a counter or static image)
		 */
	   
		$bColumns = array('tour_type',
											'p_type',
											'grand_revNo', 
											'reserveCode',
											'revDate',
											'p_code',  
											'p_name',
											'stDate',
											'book_pri',
											'p_cnt',
											'revst',
											'base_rate',
											'last_total',
											'r_pay',
											'last_bal'
											
											);
			
		/* Indexed column (used for fast and accurate table cardinality) */
		$sIndexColumn = "a.wdate";
		 
	   
		/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 * If you just want to use the basic configuration for DataTables with PHP server-side, there is
		 * no need to edit below this line
		 */
		 
		/*
		 * Local functions
		 */
		function fatal_error ( $sErrorMessage = '' )
		{
			header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
			die( $sErrorMessage );
		}
	 
		 

		 /*
		 * Paging
		 */
		$sLimit = "";
		if ( isset( $_POST['iDisplayStart'] ) && $_POST['iDisplayLength'] != '-1' )
		{
			$sLimit = "LIMIT ".intval( $_POST['iDisplayStart'] ).", ".
				intval( $_POST['iDisplayLength'] );
		}
		 
		 
		 
			/*
			 * Ordering
			 */
	if ( isset( $_POST['iSortCol_0'] ) )
		{
			$sOrder = "ORDER BY  ";
			for ( $i=0 ; $i<intval( $_POST['iSortingCols'] ) ; $i++ )
			{
				if ( $_POST[ 'bSortable_'.intval($_POST['iSortCol_'.$i]) ] == "true" )
				{
					$sOrder .= $bColumns[ intval( $_POST['iSortCol_'.$i] ) ]."
						".($_POST['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
				}
			}
			 
			$sOrder = substr_replace( $sOrder, "", -2 );
			if ( $sOrder == "ORDER BY" )
			{
				$sOrder = "";
			}
		}
		
	   // $sOrder = "ORDER BY a.grand_revNo desc ,a.wdate desc ";
			
		
		
	  //  $sOrder = " ORDER BY b.wdate DESC";
		 $sWhere = " && a.parent ='MAIN'";
		 $sWhere0 = "";
		 $sWhere1 =  "";
		//&& rev_status!='CANCEL' ";
		 if ($_GET['kinddate'] =="1") {
			 if ($_GET['startDate1'] !="") {
					$start=  date("Y-m-d",strtotime($_GET['startDate1']));
				
					$sWhere .=  "&& a.stDate >= '$startDate1' && a.stDate <= '$endDate1'";//" && a.stDate between '$startDate1' and '$endDate1' ";
					$sWhere1 .=  "&& a.start_date >= '$startDate1' && a.start_date <= '$endDate1'";

			 }
		 } else if ($_GET['kinddate'] =="2") {
			 if ($_GET['startDate1'] !="") {
					$start=  date("Y-m-d",strtotime($_GET['startDate1']));
				
					$sWhere .= "&& a.revDate >= '$startDate1' && a.revDate <= '$endDate1'";//" && a.revDate between '$startDate1' and '$endDate1' ";
					$sWhere1 .= "&& a.reserve_date >= '$startDate1' && a.reserve_date <= '$endDate1'";

			 }


		 }  
		 if ($_GET['kindamt'] =="1") {
			 $sWhere .= " && (a.last_total - a.last_bal) > 0"; 
			 $sWhere0 .= " && (a.last_total - a.last_bal) > 0";
			 $sWhere1 .= " && (a.last_total - a.last_bal) > 0";

		 } else if ($_GET['kindamt'] =="2") {
			 
			 $sWhere .= " && a.last_bal > 0"; 
			 $sWhere0 .= " && a.last_bal > 0";
			 $sWhere1 .= " && a.last_bal > 0";

		 } else if ($_GET['kindamt'] =="3") {
			 
			 $sWhere .= " && a.reserveCode in (select reserveCode from payment_history where payment_status in ('RRQUEST','APPROVE','RETURN'))";
			 $sWhere0 .=  " && a.reserveCode in (select reserveCode from payment_history where payment_status in ('RRQUEST','APPROVE','RETURN'))";
			 $sWhere1 .=  " && a.reserveCode in (select reserveCode from payment_history where payment_status in ('RRQUEST','APPROVE','RETURN'))";
			 
		 } 
		 if ($_GET['cname'] !="") {
				
			
				$sWhere .= " && a.book_pri like '"."%".$_GET['cname']."%"."'"; 
				$sWhere0 .= " && c.traveler_nm like '"."%".$_GET['cname']."%"."'";
				$sWhere1 .= " && a.r_kname like '"."%".$_GET['cname']."%"."'";

		 }
		 if ($_GET['crev'] !="") {
				
			
				$sWhere .= " && a.reserveCode like '"."%".$_GET['crev']."%"."'";
				$sWhere1 .= " && a.reserveCode like '"."%".$_GET['crev']."%"."'";

		 }
		 if ($_GET['cemail'] !="") {
				
			
				$sWhere .= " && a.book_email like '"."%".$_GET['cemail']."%"."'";
				$sWhere1 .= " && a.r_email like '"."%".$_GET['cemail']."%"."'";

		 }
		 if ($_GET['ctel'] !="") {
				
			
				$sWhere .= " && a.book_phone like '"."%".$_GET['ctel']."%"."'";
				$sWhere0 .= " && c.traveler_phone like '"."%".$_GET['ctel']."%"."'";
				$sWhere1 .= " && a.r_phone like '"."%".$_GET['ctel']."%"."'";

		 }
		  if ($_GET['rstatus'] !="") {
				 $rstt = explode("/",$_GET['rstatus']);
				 for($m=0; $m<=count($rstt)-1; $m++)
				 {    
					if (count($rstt) == 1) {
							 $wstr = "'$rstt[$m]'";
					} else {
						if ($m == 0) {
							$wstr = "'$rstt[$m]',";

						} else if ($m == (count($rstt)-1)) {
							$wstr .= "'$rstt[$m]'";
						} else {
							$wstr .= "'$rstt[$m]',";
						}
						
					}

				 }
				 $sWhere .= " && a.rev_status in ($wstr)";
				 $sWhere1 .= " && a.rev_status in ($wstr)";
				//print_R($_GET[rstatus]);
				//echo $sWhere;
				//exit;
		 }
		 
		 
		  
		 if ($_GET['tourCategory'] !="") {
				
			
				$sWhere .= " && b.p_type='$tourCategory'";


		 }
		 if ($_GET['tourpay'] !="") {


				$sWhere .= " && a.payment_st='$tourpay'";
				$sWhere1 .= " && a.payment_st='$tourpay'";

		 }
		 if ($_GET['sarea'] !="") {
				$sWhere .= " && a.s_area='".$_GET['sarea']."'";
		 }


		/*
		 * SQL queries
		 * Get data to display
		 */
		//&& a.rev_status != 'CANCEL'
		if (($_GET['cname'] !="") || ($_GET['ctel'] !="")) {
			$sQuery = "select SQL_CALC_FOUND_ROWS distinct
												a.tour_type,
												b.p_type,
												a.grand_revNo, 
												a.reserveCode,
												a.revDate,
												a.p_code,  
												a.p_name,
												a.stDate,
												a.book_pri,
												a.p_cnt,
												a.rev_status as revst,
												a.base_rate,
												a.last_total,
												0 as r_pay,
												a.last_bal,
												a.userid
			 from reserve_info a,product_master b
			 where a.p_code=b.p_code 
			 $sWhere
			 union
			 select SQL_CALC_FOUND_ROWS distinct
												a.tour_type,
												b.p_type,
												a.grand_revNo, 
												a.reserveCode,
												a.revDate,
												a.p_code,  
												a.p_name,
												a.stDate,
												a.book_pri,
												a.p_cnt,
												a.rev_status as revst,
												a.base_rate,
												a.last_total,
												0 as r_pay,
												a.last_bal,
												a.userid
			 from reserve_info a,product_master b,reserve_traveler c
			 where a.p_code=b.p_code && a.reserveCode = c.reserveCode && a.parent ='MAIN'
			 $sWhere0
			 
			 $sOrder
			 $sLimit";
		} else {

		
			$sQuery = "select SQL_CALC_FOUND_ROWS distinct
												a.tour_type,
												b.p_type,
												a.grand_revNo, 
												a.reserveCode,
												a.revDate,
												a.p_code,  
												b.p_name,
												a.stDate,
												a.book_pri,
												a.p_cnt,
												a.rev_status as revst,
												a.base_rate,
												a.last_total,
												0 as r_pay,
												a.last_bal,
												a.userid
			 from reserve_info a,product_master b
			 where a.p_code=b.p_code 
			 $sWhere
			 
			 $sOrder
			 $sLimit";

		}
		//echo $sQuery;
		
		//exit;
		$rResult = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
		 
		 
		/* Data set length after filtering */
		$sQuery = "
			SELECT FOUND_ROWS()
		";
		$rResultFilterTotal = mysql_query( $sQuery, $dbConn) or fatal_error( 'MySQL Error: ' . mysql_errno() );
		$aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
		
		$iFilteredTotal = $aResultFilterTotal[0];
		 
	  
		if (($_GET['cname'] !="") || ($_GET['ctel'] !="")) {
		$sQuery = "select SQL_CALC_FOUND_ROWS sum(cnt) from (
	select   distinct COUNT(a.reserveCode) as cnt from reserve_info a,product_master b,reserve_traveler c 
	where a.p_code=b.p_code && a.reserveCode = c.reserveCode 
	$sWhere 
	 ) a
		 ";
		} else {
		$sQuery = "select SQL_CALC_FOUND_ROWS sum(cnt) from (
	select   distinct COUNT(a.reserveCode) as cnt from reserve_info a,product_master b
	where a.p_code=b.p_code 
	$sWhere 
	 ) a
		 ";

		}
		//echo $sQuery;
		//exit;
	   //exit;
		$rResultTotal = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
		$aResultTotal = mysql_fetch_array($rResultTotal);
		$iTotal = $aResultTotal[0];
		//echo $sQuery.'<br />'.$iTotal;
		//exit;
		/*
		 * Output
		 */
		
		// 서머리 합계 쿼리
		$sSumQuery = "SELECT
			SUM(a.last_total)              AS sum_total,
			SUM(a.last_total - a.last_bal) AS sum_paid,
			SUM(a.last_bal)                AS sum_balance,
			SUM(a.p_cnt)                   AS sum_pcnt
			FROM reserve_info a, product_master b
			WHERE a.p_code = b.p_code and a.rev_status in ('DONE','READY','PPAY','OPAY') $sWhere";
		$rSum = mysql_query($sSumQuery, $dbConn);
		$aSum = mysql_fetch_assoc($rSum);

		$output = array(
			"sEcho"                => intval($_POST['sEcho']),
			"iTotalRecords"        => $iTotal,
			"iTotalDisplayRecords" => $iFilteredTotal,
			"sum_total"            => (float)($aSum['sum_total'] ?? 0),
			"sum_paid"             => (float)($aSum['sum_paid'] ?? 0),
			"sum_balance"          => (float)($aSum['sum_balance'] ?? 0),
			"sum_pcnt"             => (int)($aSum['sum_pcnt'] ?? 0),
			"aaData"               => array()
		);
		
		 
		$pInfoCache = [];
		while ( $aRow = mysql_fetch_array( $rResult ) )
		{
			
			$row = array();
			if (!isset($pInfoCache[$aRow['p_code']])) {
				$pInfoCache[$aRow['p_code']] = getProductMaster($aRow['p_code']);
			}
			$pInfo = $pInfoCache[$aRow['p_code']];
			
			for ( $i=0 ; $i<15; $i++ )
			{
				   
					if ($i==0) {
						
						if ($aRow['tour_type']== '1') {
							$aRow['tour_type'] = "<font color=green>직접예약</font>";
							$ty=1;
							$pricet=1;
							$sub=15;
						} else if ($aRow['tour_type']== '2') {
							$aRow['tour_type'] = "<font color=green>웹예약</font>";
							$ty=2;
							$pricet=2;
							$sub=20;
						}else if ($aRow['tour_type']== '3') {
							$aRow['tour_type'] = "<font color=green>업체예약</font>";
							$ty=3;
							$pricet=3;
							$sub=25;
						}
						

					}

					if ($i==1) {
						if ($aRow['parent']== 'SUB') {
							$aRow['parent'] = "<font color=BLUE>복합</font>";
						}else{
							$aRow['parent'] = "<font color=BLUE>단일</font>";
						}
						if ($aRow['p_type'] == 1) {
							$aRow['p_type'] = "로컬상품";
						} else if ($aRow['p_type'] == 2) {
							$aRow['p_type'] = "인바운드";
						} else if ($aRow['p_type'] == 4) {
							$aRow['p_type'] = "인센티브";
						} else if ($aRow['p_type'] == 5) {
							$aRow['p_type'] = "아웃바운드";
						} 

					}
					
					if ($i==5) {
						if ($aRow['p_type'] != 6) {
							if ($pInfo['p_own'] == "purun") {
								$aRow['p_code']= "푸른투어";
							} else {
								$rname=randname($pInfo['p_own']);
								$aRow['p_code']= $rname['kor_name'];
							} 
						} else {
							$aRow['p_code']= "";
						}
						
					}
					if ($i==7) {
						
						$sday = $aRow['stDate'] ;
					
						$week = array("일" , "월"  , "화" , "수" , "목" , "금" ,"토") ;
						$eweek = array("SUN" , "MON" , "TUE" , "WED" , "THU" , "FRI" ,"SAT") ;
						$sweekday = $week[ date('w'  , strtotime($sday)  ) ] ;
						$aRow['stDate'] = $sday." (".$sweekday.")";
					}
					
					if ($i==10) {
						
						if ($aRow['revst']== 'READY') {
							$aRow['revst'] = "<font color=#0984a3>예약접수</font>";
						}
						if ($aRow['revst']== 'DONE') {
							$aRow['revst'] = "<font color=#03923a>예약확정</font>";
						}
						
						if ($aRow['revst']== 'CANCEL') {
							$aRow['revst'] = "<font color=#e02133>예약취소</font>";
						}
					}

					if ($i==11) {
						
						if ($aRow['payment_st']== 'READY') {
							$aRow['payment_st'] = "<font color=red>미납</font>";
						}
						if ($aRow['payment_st']== 'PPAY') {
							$aRow['payment_st'] = "<font color=red>부분완납</font>";
						}
						
						if ($aRow['payment_st']== 'DONE') {
							$aRow['payment_st'] = "<font color=red>완납</font>";
						}
						if ($aRow['payment_st']== 'OPAY') {
							$aRow['payment_st'] = "<font color=red>환불</font>";
						}
					}
					if ($i==13) {
						
					   $tmpr = $aRow['last_total']- $aRow['last_bal'];
					   if ($aRow['last_total'] != $aRow['last_bal']) {
						   $aRow['r_pay'] = $tmpr;
					   }
					}
					/*
					if ($i==15) {
						if ($aRow[muser_id]=='') {
							$user_rinfo = getinfo_dbMember($aRow[userid]);
							$aRow[muser_id] = $user_rinfo[kor_name];
						} else {
							$user_rinfo = getinfo_dbMember($aRow[muser_id]);
							$aRow[muser_id] = $user_rinfo[kor_name];
						}
						
					}
					*/
					$row[] = "<a href='base_reservation_m.php?estimateCode={$aRow['reserveCode']}&division=3&pdx=2&sub=$sub&ty=$ty&pricet=$pricet#TOP' >".$aRow[ $bColumns[$i] ]."</a>";
				
				
			}
			$output['aaData'][] = $row;

		}
		//print_r($output['aaData']);

		

		
		$jsonOptions = 0;
		if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
			$jsonOptions |= JSON_INVALID_UTF8_SUBSTITUTE;
		}
		if (ob_get_length() !== false) {
			ob_clean();
		}
		echo json_encode( $output, $jsonOptions );
	?>
