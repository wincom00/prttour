<?php
    include "include/inc_base.php";

    header("Content-Type: application/json");
    
	
    $qry1 = "update rand_company set cur_amt = '0',status='READY',settle_memo='' where reserveCode = '$reserveCode' && seq_no = '$seqNo1'";
	$rst1= mysql_query($qry1);
  
	$qry2 = "delete from rand_pay  where  reserveCode = '$reserveCode' && seq_rand='$seqNo1'";
    $rst2 = mysql_query($qry2,$dbConn);
   
    echo $rst2;
?>