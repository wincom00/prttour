<?php

    include("include/inc_base.php");
    

	header("Content-Type: application/json");
	//$date = new DateTime("now", new DateTimeZone(date_default_timezone_get()) );
   // $now1= $date->format('Y-m-d H:i:s');
	$now1= $_GET['gdate'];
    if($kind == "1")
	{
			$qry1 = "insert into att_log (userid,login_date,login_ip,status) values ('{$user_dbinfo['userid']}','$now1','".$_SERVER['REMOTE_ADDR']."','1')";
			$rst1 = mysql_query($qry1);
			//print_r($qry1);
			//$new_date=date('Y,m,d');//date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
			//$dates=date("Y-m-d", $new_date);
		    $dates=date("Y-m-d");
		    $qry2 = "select max(id) as mxid from att_log where userid='{$user_dbinfo['userid']}' && date_format( login_date, '%Y-%m-%d' ) = '$dates'";
			$rst2 = mysql_query($qry2);
			$row0 = mysql_Fetch_assoc($rst2);
			// echo
		    $m_qry1 = "select status,login_date from att_log where userid='{$user_dbinfo['userid']}' && date_format( login_date, '%Y-%m-%d' ) = '$dates' && id='{$row0['mxid']}' ";
			$m_rst1 = mysql_query($m_qry1);
			//echo $m_qry1;

	} else {
		    //$new_date=date('Y-m-d');//date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
			//$dates=date("Y-m-d", $new_date);
		    $dates=date("Y-m-d");
			$qry2 = "select max(id) as mxid from att_log where userid='{$user_dbinfo['userid']}' && date_format( login_date, '%Y-%m-%d' ) = '$dates'";
			
			$rst2 = mysql_query($qry2);
			$row1 = mysql_Fetch_assoc($rst2);
		
			//퇴근
			
			$qry1 = "update att_log set logout_date='$now1' ,logout_ip='".$_SERVER['REMOTE_ADDR']."' , status='2' 
			where userid='{$user_dbinfo['userid']}' && date_format( login_date, '%Y-%m-%d' ) = '".$dates."' && id='{$row1['mxid']}'";
			//echo $qry1;
			//exit;
			$rst1 = mysql_query($qry1);

			$m_qry1 = "select status,logout_date from att_log where userid='{$user_dbinfo['userid']}' && date_format( login_date, '%Y-%m-%d' ) = '$dates' && id='{$row1['mxid']}' ";
			$m_rst1 = mysql_query($m_qry1);
			//echo $m_qry1;
			//exit;

	}
    $result_array =  array();
	
	while($row = mysql_fetch_object($m_rst1)) 
	{
  
         $result_array[] = $row;
        
	}
	$result_array = json_encode($result_array);
    
    echo $result_array;

?>
