<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
  $qry1 = "delete from payment_history where reserveCode='$reserveCode' && seq_no = '$seq'";
  $rst1 = mysql_query($qry1,$dbConn); 

  $qry1 = "select last_total,last_bal from reserve_info where reserveCode='$reserveCode' && parent='MAIN'";
  $rst1 = mysql_query($qry1,$dbConn);    
  $result_array =  array();
// echo $qry1."<br>";
  while($row = mysql_fetch_object($rst1)) 
  {
		$result_array[] = $row;
  }
 
  $result_array = json_encode($result_array);

  echo $result_array;
 