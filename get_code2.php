<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
 
  $lvcode1 = substr($code1,0,3);
  $lvcode2 = substr($code1,3,2);

  $qry1 = "select * from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 <> '00'  && lvcode4 = '00' order by lvcode4 asc";
 
  //echo $qry1;
  $rst1 = mysql_query($qry1,$dbConn);    
  $result_array =  array();
 
  while($row = mysql_fetch_object($rst1)) 
  {
  
         $result_array[] = $row;
  }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
