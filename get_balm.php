<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
  $qry1 = "select last_total,last_bal from reserve_info where reserveCode='$code1' && parent='MAIN'";
 

  $rst1 = mysql_query($qry1,$dbConn);    
 
  while($row = mysql_fetch_object($rst1)) 
  {
		 $bal = $row->last_bal - $lastval;
		 $row->last_bal = $bal;
		 $tot  = $row->last_total +  $lastval;
        
		 $qry6= "update reserve_info 
									set
									last_bal = '$bal' , 
									last_total = '$tot' , 
									where
									reserveCode = '$estimateCode'  && parent = 'MAIN' ";

				
		$rst6 = mysql_query($qry6,$dbConn);


  }
  
	
  $qry1 = "select last_total,last_bal from reserve_info where reserveCode='$code1' && parent='MAIN'";
 
  $rst1 = mysql_query($qry1,$dbConn);    
  $result_array =  array();
// echo $qry1."<br>";
  while($row1 = mysql_fetch_object($rst1)) 
  {
      $result_array[] = $row1;
  }
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
