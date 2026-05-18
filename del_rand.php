<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
  $result_array =  array();
   if ($types == "credit") {
  	 $qry1 = "delete  from rand_account_history where division='credit' && rand_id='$sel' && reserveCode = '$reserve' && seq='$num'";
  	 $rst1 = mysql_query($qry1,$dbConn); 
  } else {
  	
  	 $qry1 = "delete  from rand_account_history where division='debit' && rand_id='$sel' && reserveCode = '$reserve' && seq='$num'";
	 $rst1 = mysql_query($qry1,$dbConn);
  	
  }	
 $result_array = json_encode("1");
 echo $result_array;
