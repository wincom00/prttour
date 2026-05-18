     
      
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
   
	$bColumns = array(  'tynmm',
								'grand_revNo', 
								'reserveCode', 
								'p_name',
								'book_pri',
								'p_cnt',
								'last_total',
								'last_bal',
								'p_code',
								'stDate',
								'revDate', 
								'wdate',
								'rev_status', 
								'userid',
								'muser_id',
								'pricet'
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
     
   // $sOrder = "ORDER BY grand_revNo desc ,wdate desc ";
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
    
	
  //  $sOrder = " ORDER BY b.wdate DESC";
     $sWhere = " && parent ='MAIN'";
	
	//&& rev_status!='CANCEL' ";
     if ($_GET['startDate1'] !="" && $_GET['startDate2'] !="") {
			$start=  date("Y-m-d",strtotime($_GET['startDate1']));
			$end =  date("Y-m-d",strtotime($_GET['startDate2']));

			$sWhere .= " && a.stDate >= '$start' && a.stDate <= '$end' ";

	 } else if ($_GET['startDate1'] !="") {
			$start=  date("Y-m-d",strtotime($_GET['startDate1']));

			$sWhere .= " && a.stDate >= '$start' ";

	 } else if ($_GET['startDate2'] !="") {
			$end =  date("Y-m-d",strtotime($_GET['startDate2']));

			$sWhere .= " && a.stDate <= '$end' ";

	 }
	 if ($_GET['cname'] !="") {
			
		
			$sWhere .= " && a.book_pri like '"."%".$_GET['cname']."%"."'"; 
			$sWhere0 .= " && b.traveler_nm like '"."%".$_GET['cname']."%"."'";

	 }
	 if ($_GET['crev'] !="") {
			
		
			$sWhere .= " && a.reserveCode like '"."%".$_GET['crev']."%"."'";

	 }
     if ($_GET['cemail'] !="") {
			
		
			$sWhere .= " && a.book_email like '"."%".$_GET['cemail']."%"."'";

	 }
	
	 if ( $_GET['ty'] !="") {
			
		    if ($_GET['ty'] == 1) {
			    $sWhere .= " && a.tour_type ='".$_GET['ty']."' && a.pricet ='1'";
			} else if ($_GET['ty']== 2) {
				$sWhere .= " && (a.tour_type ='".$_GET['ty']."')";
			} else if ($_GET['ty']== 3) {
				$sWhere .= " && (a.tour_type ='".$_GET['ty']."' || a.pricet ='3')";
			}

	 }
	 /*
	 if (($user_dbinfo[dept_prior] == "J") || ($user_dbinfo[dept_prior] == "")) {
		$sWhere .= " && ((c.m_dept like '%{$user_dbinfo['area_comp']}%') || (c.p_dept like '%{$user_dbinfo['area_comp']}%'))";
	 } else {
		$sWhere .= "";
	 }
	 */
	 if (($user_dbinfo['dept_prior'] == "J") || ($user_dbinfo['dept_prior'] == "")) {
		//$sWhere .= " && ((c.m_dept like '%{$user_dbinfo['area_comp']}%') ||  (c.m_dept='')  || (c.p_dept like '%{$user_dbinfo['area_comp']}%'))";
	 } else {
		$sWhere .= "";
	 }
	
    /*
     * SQL queries
     * Get data to display
     */
    if (($_GET['cname'] =="")) {
		$sQuery = "select SQL_CALC_FOUND_ROWS 
	                            '$tynm' as tynmm,
								a.grand_revNo, 
								a.reserveCode, 
								a.p_name,
								a.book_pri,
								a.p_cnt,
								a.last_total,
								a.last_bal,
								a.p_code,
								a.stDate,
								a.revDate, 
								a.wdate,
								a.rev_status, 
								a.userid,
								a.muser_id,
								a.pricet
		 from reserve_info a,product_master b where 1=1 && a.rev_status !='CANCEL'  && a.p_code=b.p_code && a.parent ='MAIN' 
		 $sWhere
		 $sOrder
		 $sLimit";
	} else {
		 $sQuery = "select distinct 
	                            '$tynm' as tynmm,
								a.grand_revNo, 
								a.reserveCode, 
								a.p_name,
								a.book_pri,
								a.p_cnt,
								a.last_total,
								a.last_bal,
								a.p_code,
								a.stDate,
								a.revDate, 
								a.wdate,
								a.rev_status, 
								a.userid,
								a.muser_id,
								a.pricet
		 from reserve_info a,reserve_traveler b,product_master c where 1=1 && a.rev_status !='CANCEL' && a.reserveCode = b.reserveCode && a.p_code=c.p_code && a.parent ='MAIN' 
		 $sWhere
		 $sWhere0
		 $sOrder
		 $sLimit";
	}
	/*
	$sQuery = "select SQL_CALC_FOUND_ROWS distinct
	                            '$tynm' as tynmm,
								a.grand_revNo, 
								a.reserveCode, 
								a.p_name,
								a.book_pri,
								a.p_cnt,
								a.last_total,
								a.last_bal,
								a.p_code,
								a.stDate,
								a.revDate, 
								a.wdate,
								a.rev_status, 
								a.userid,
								a.muser_id,
								a.pricet
     from reserve_info a,reserve_traveler b,product_master c where 1=1 && a.rev_status !='CANCEL' && a.reserveCode = b.reserveCode && a.p_code=c.p_code
	 $sWhere
	 $sOrder
	 $sLimit";
	 */
   // echo $sQuery;
	//exit;
    $rResult = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
     
	 
    /* Data set length after filtering */
    $sQuery = "
        SELECT FOUND_ROWS()
    ";
    $rResultFilterTotal = mysql_query( $sQuery, $dbConn) or fatal_error( 'MySQL Error: ' . mysql_errno() );
    $aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
	
    $iFilteredTotal = $aResultFilterTotal[0];
     
    if (($_GET['cname'] !="")) {
		$sQuery = "select SQL_CALC_FOUND_ROWS distinct COUNT(".$sIndexColumn.")
		 from  reserve_info a,reserve_traveler b,product_master c where 1=1 && a.rev_status !='CANCEL' && a.reserveCode = b.reserveCode && a.p_code=c.p_code
		 $sWhere
		 ";
	} else {
		$sQuery = "select SQL_CALC_FOUND_ROWS distinct COUNT(".$sIndexColumn.")
		 from  reserve_info a,product_master b where 1=1 && a.rev_status !='CANCEL' && a.p_code=b.p_code
		 $sWhere
		 ";
	}
    $rResultTotal = mysql_query( $sQuery, $dbConn ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
    $aResultTotal = mysql_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];
    
    /*
     * Output
     */
    
	$output = array(
        "sEcho" => intval($_POST['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );
	
     
    while ( $aRow = mysql_fetch_array( $rResult ) )
    {
		
        $row = array();
		
        for ( $i=0 ; $i<15; $i++ )
        {

				if ($i==0) {
					if ($_GET['ty'] == '1') {
						$aRow['tynmm'] = '직접예약';
					}
					if ($_GET['ty'] == '2') {
						$aRow['tynmm'] = '웹예약';
					}
					if ($_GET['ty'] == '3') {
						$aRow['tynmm'] = '업체예약';
					}
					if (($_GET['ty'] == '3') && ($aRow['pricet'] == '3'))  {
						$aRow['tynmm'] = '업체예약';
					}

				}
			    if ($i==7) {
					$pInfo=getProductMaster($aRow['p_code']);	
					if ($pInfo['p_own'] == "purun") {
						$aRow['p_code']= "푸른투어";
					} else {
					    $rname=randname($pInfo['p_own']);
						$aRow['p_code']= $rname['kor_name'];
					}
					
				}
				if ($i==10) {
					
					if ($aRow['rev_status']== 'READY') {
						$aRow['rev_status'] = "<font color=#0984a3>예약접수</font>";
					}
					
					if ($aRow['rev_status']== 'DONE') {
						$aRow['rev_status'] = "<font color=#911f77>예약확정</font>";
					}
					
					if ($aRow['rev_status']== 'CANCEL') {
						$aRow['rev_status'] = "<font color=#e02133>예약취소</font>";
					}
				}
                $row[] = "<a href='base_reservation_m.php?estimateCode={$aRow['reserveCode']}&division=$division&pdx=$pdx&sub=$sub&ty=$ty&pricet={$aRow['pricet']}#TOP' style='color:#000000'>".$aRow[ $bColumns[$i] ]."</a>";
            
            
        }
        $output['aaData'][] = $row;

    }
	//print_r($output['aaData']);

	

	
    echo json_encode( $output );
?>