<?php

  include("include/inc_base.php");
    
  header("Content-Type: application/json");
  
  
  $qry1 = "select last_sale,last_total,last_bal from reserve_info where reserveCode='$code1' && parent='MAIN'";
 

  $rst1 = mysql_query($qry1,$dbConn);    
  $result_array =  array();
// echo $qry1."<br>";
  while($row = mysql_fetch_object($rst1)) 
  {
	     //$totamt = ($unitamt+$lastval2+$airamt)-$lastval;

		 $cruiseamt = isset($cruiseamt) ? $cruiseamt : 0;
		 $totamt = ($unitamt+$lastval2+$airamt+$cruiseamt)-$lastval;
         /*if ($totamt == $row[last_total]) {
				$totamt = ($unitamt+$lastval2)-$lastval;
		 } else if ($totamt < $row[last_total]) {
				$totamt = ($row[last_total]+$lastval2)-$lastval;
		 } else {
				$totamt = ($unitamt+$lastval2)-$lastval;
		 }
		 */
		// echo $unitamt."||".$lastval2."||".$airamt."||".$lastval;
		 $qryp = "select * from payment_history where reserveCode = '$code1' && (payment_status='DONE' || payment_status='RETURN')";
		 $rstp = mysql_query($qryp,$dbConn);
		 while($rowp = mysql_fetch_assoc($rstp)){

			  if ( $rowp['payment_status'] === "RETURN") {

					$rtnamt = $rtnamt + $rowp['payment'];
					//echo "R :".$rowp[payment]."\n";
			  } else {
					$ttotamt1 = $ttotamt1 + $rowp['payment'];
					//echo "D :".$rowp[payment]."||".$ttotamt1."\n";

			  }
			  
			  

		 }
		 $totpay = $ttotamt1 - $rtnamt;
		 //$totpay=bcsub( $ttotamt1,$rtnamt); 
		// $lstbal = $unitamt - $totpay+($lastval2 - $lastval);
		 $lstbal = round($totamt,2) - round($totpay,2);
		/// $lstbal=bcsub($totamt,$totpay); 
		 //echo $totamt."test".$lstbal."TEST".$totpay;
		//echo $totamt."||".$totpay;
		//exit;
		 $bal = $lstbal;
		 $row->last_bal = $bal;
		 
         $result_array[] = $row;

		 

  }
	
  
  
  $result_array = json_encode($result_array);
   
  echo $result_array;
