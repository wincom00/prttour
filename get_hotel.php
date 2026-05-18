<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
 

  $qry1 = "select h_code,h_name,m_rate from product_hotel where h_typea = '$code1' && u_type in ('1','3')  order by h_name asc";
// echo $qry1;
// exit;
  $rst1 = mysql_query($qry1,$dbConn);    
  $result_array =  array();
 
  while($row = mysql_fetch_object($rst1)) 
  {
  
         $result_array[] = $row;
  }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
