<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
  $result_array =  array();
  $qry6= "update payment_history 
							set
							conf_user = '{$user_dbinfo['userid']}',
							conf_p = '2' ,
							conf_date = now()
							where
							seq_no = '$seq'  ";
 //echo $qry6;
 $rst6 = mysql_query($qry6,$dbConn);		
 $result_array = json_encode("1");
 echo $result_array;
