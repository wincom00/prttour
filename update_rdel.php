<?php

  include("include/inc_base.php");

  header("Content-Type: application/json");


  $qry1 = "delete from payment_history where reserveCode='$reserveCode' && seq_no = '$seq'";
  $rst1 = mysql_query($qry1,$dbConn);

  // 결제내역 삭제 후 잔액/결제상태 재계산 (실결제 DONE/RETURN 기준)
  $rinfo = mysql_query("select last_total from reserve_info where reserveCode='$reserveCode' && parent='MAIN'", $dbConn);
  $rrow = mysql_fetch_assoc($rinfo);
  if ($rrow) {
		$last_total = floatval(str_replace(',', '', $rrow['last_total']));
		$paid = 0;
		$returned = 0;
		$pq = mysql_query("select payment, payment_status from payment_history where reserveCode='$reserveCode' && (payment_status='DONE' || payment_status='RETURN')", $dbConn);
		while ($pq && ($pr = mysql_fetch_assoc($pq))) {
			if ($pr['payment_status'] == 'RETURN') {
				$returned += floatval(str_replace(',', '', $pr['payment']));
			} else {
				$paid += floatval(str_replace(',', '', $pr['payment']));
			}
		}
		$new_bal = round($last_total, 2) - round($paid - $returned, 2);

		if ($new_bal == $last_total) {
			$paystatus = 'READY';   // 미납
		} else if ($new_bal == 0) {
			$paystatus = 'DONE';    // 완납
		} else if ($new_bal < 0) {
			$paystatus = 'OPAY';    // 환불
		} else {
			$paystatus = 'PPAY';    // 부분완납
		}

		$new_bal_s = number_format($new_bal, 2, '.', '');
		mysql_query("update reserve_info set last_bal='$new_bal_s', payment_st='$paystatus' where reserveCode='$reserveCode' && parent='MAIN'", $dbConn);
  }

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

