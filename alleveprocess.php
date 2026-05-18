     
      
	<?php
	 include 'include/inc_base.php';

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
   
	$bColumns = array(/*'p_type1',*/'tour_type','parent','reserveCode', 
									 	'book_pri',
										'p_cnt',
										'last_total',
										'last_bal',
										'stDate',
										'revDate', 
										/*'wdate',*/
										'revst', 
										'payment_st', 
										'progress',
										'muser_id'
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
    if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
    {
        $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
            intval( $_GET['iDisplayLength'] );
    }
     
     
    /*
     * Ordering
     */
     
   // $sOrder = "ORDER BY a.revDate desc ";
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
    
	
  
	//&& rev_status!='CANCEL' ";
     if ($_GET['startDate1'] !="") {
			$start=  date("Y-m-d",strtotime($_GET['startDate1']));
		
			$sWhere .= " && a.stDate = '$start' ";

	 }
	 if ($_GET['pcode'] !="") {
			
		
			$sWhere .= " && a.p_code = '".$_GET['pcode']."'";

	 }
	// echo $_GET[kindEvent]."tRST";
	 if ($_GET['kindEvent'] !="") {
			if ($_GET['kindEvent'] == 1) {
		
			    $sWhere .= " && a.rev_status in ('READY','ORDER','DONE')";
			} else if ($_GET['kindEvent'] == 2) {
				$sWhere .= " && a.rev_status in ('WAIT')";

			} else if ($_GET['kindEvent'] == 3) {
				$sWhere .= " && a.rev_status in ('CANCEL')";

			}
	 } else {
		$sWhere .= " && a.rev_status not in ('CANCEL')";

	 }
	 
    /*
     * SQL queries
     * Get data to display
     */
    
	$sQuery = "select SQL_CALC_FOUND_ROWS
								a.tour_type,
								'$tourCategory' as p_type1,
								a.parent,
                				a.reserveCode, 
								a.book_pri,
								a.last_total,
								a.last_bal,
								a.stDate,
								a.revDate , 
								a.wdate,
								a.rev_status as revst, 
								a.payment_st,
								a.progress,
								a.p_cnt,
								a.muser_id
     from reserve_info a,product_master b
	  where a.p_code=b.p_code 
	 $sWhere
	 $sOrder
	 $sLimit";
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
     
  
	$sQuery = "select SQL_CALC_FOUND_ROWS COUNT(".$sIndexColumn.")
     from reserve_info a,product_master b
	 where a.p_code=b.p_code 
	 $sWhere
	 ";
    $rResultTotal = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
    $aResultTotal = mysql_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];
    
    /*
     * Output
     */
    
	$output = array(
        "sEcho" => intval($_GET['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );
	
     
    while ( $aRow = mysql_fetch_array( $rResult ) )
    {
		
        $row = array();
		$trnm = getReserveTrRepre($aRow['reserveCode']);
		//echo $pcode;
        for ( $i=0 ; $i<13; $i++ )
        {
			    $pInfo=getProductMaster($pcode); 
				
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
						$aRow['parent'] = "<font color=BLUE>복합상품예약</font>";
					}else{
						$aRow['parent'] = "<font color=BLUE>단일상품예약</font>";
					}

				}
				if ($i==2) {
					
					$aRow['p_cnt'] = $aRow['p_cnt'] ;
					if ($aRow['p_cnt'] == 0) {
						$aRow['traveler_nm'] = $aRow['traveler_nm'];
					} else {
						$aRow['traveler_nm'] = $aRow['traveler_nm'];//."+".$aRow[p_cnt];
					}
					
				}

				if ($i==3) {
					
					
						$aRow['book_pri'] = $trnm['traveler_nm'];
					
				}

				
				
				if ($i==8) {
					
					if ($aRow['revst']== 'READY') {
						$aRow['revst'] = "<font color=#0984a3>예약접수</font>";
					}
					
					if ($aRow['revst']== 'DONE') {
						$aRow['revst'] = "<font color=#911f77>예약확정</font>";
					}
					
					if ($aRow['revst']== 'CANCEL') {
						$aRow['revst'] = "<font color=#e02133>예약취소</font>";
					}
				}
				
				if ($i==9) {
					
				   /// $user_rinfo = getinfo_dbMember($aRow[userid]);
					//$aRow[userid] = $user_rinfo[kor_name];
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
				
				if ($i==10) {
					
				    $sStr = $str = substr($aRow['progress'], 0, 60);//mb_substr($aRow[progress], 0, 45, 'utf-8');
					//echo $sStr;
				}
				 
				
				if ($i==12) {
					
				    $user_rinfo = getinfo_dbMember($aRow['muser_id']);
					$aRow['muser_id'] = $user_rinfo['kor_name'];
					$row[] = "<a href='base_reservation_m.php?estimateCode={$aRow['reserveCode']}&division=3&pdx=2&sub=$sub&ty=$ty&pricet=$pricet#TOP' target='_blank'>".$aRow['muser_id']."</a>";
					
				}  else {
						$row[] = "<a href='base_reservation_m.php?estimateCode={$aRow['reserveCode']}&division=3&pdx=2&sub=$sub&ty=$ty&pricet=$pricet#TOP' target='_blank'>".$aRow[ $bColumns[$i] ]."</a>";
				}
               
                //echo $i;
        }
		
        $output['aaData'][] = $row;
		
    }
	//print_r($output['aaData']);

	

	
    echo json_encode( $output );
?>