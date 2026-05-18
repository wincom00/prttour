<?php
    include "include/inc_base.php";

    header("Content-Type: application/json");
    
	
    $qry1 = "delete from rand_company  where part_id = '$randid' && reserveCode = '$rev' && money_type='credit'";
	$rst1= mysql_query($qry1);
  //echo $qry1;
  //exit;
	$qry2 = "delete from rand_pay  where rand_id='$randid' && reserveCode = '$reserveCode' && trans_type='credit'";
    $rst2 = mysql_query($qry2,$dbConn);
   
    echo $rst2;
?>