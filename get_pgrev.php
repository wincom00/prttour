<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  $qry1 = "select tour_pcnt as pcnt from reserve_info where p_code = '$pcode' && stDate='$st'";
  $rst1 = mysql_query($qry1);
  $num1 = mysql_num_rows($rst1);
  
  if ($num1 == 0) {
	  $qry1 = "select p_cnt as pcnt from product_master where p_code = '$pcode' ";
      $rst1 = mysql_query($qry1);

  }
  
  $result_array =  array();
 
  while($row = mysql_fetch_object($rst1)) 
  {
  
         $result_array[] = $row;
  }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
