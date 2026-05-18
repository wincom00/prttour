<?php
      header('Content-Type: text/html; charset=utf-8');
	  $qry4 = "DELETE FROM product_details WHERE p_code = '$pcode' " ;
	  $rst4 = mysql_query($qry4);
	  $qry4 = "DELETE FROM product_details_local WHERE p_code = '$pcode' " ;
	  $rst4 = mysql_query($qry4);
	  $tourcnt = $_POST['tourLength1'];
	  for($k=0; $k < $tourcnt; $k++)
	  {
		  $kk = $k+1;
		  for($l=0; $l<count($singleDayTour[$kk]); $l++)
	      {
			 $n = $kk;
			 if ($singleDayTour[$n][$l] !="") {
				/* if (($singleDayTour[$n][$l] == "PICKUP") || ($singleDayTour[$n][$l] == "LVPICKUP") || ($singleDayTour[$n][$l] == "LAPICKUP")) {
					$pos[$n][$l] = 0;
				 } else if (($singleDayTour[$n][$l] != "SENDING") || ($singleDayTour[$n][$l] != "LVSENDING") || ($singleDayTour[$n][$l] != "LASENDING")) {
					$pos[$n][$l] = 1;
				 }
				 if (($singleDayTour[$n][$l] == "SENDING") || ($singleDayTour[$n][$l] == "LVSENDING") || ($singleDayTour[$n][$l] == "LASENDING")) {
					$pos[$n][$l] = 999;
				 } else if (($singleDayTour[$n][$l] != "PICKUP") || ($singleDayTour[$n][$l] != "LVPICKUP") || ($singleDayTour[$n][$l] != "LAPICKUP")) {
					$pos[$n][$l] = 1;
				 }
				 */
				 //echo $pos[$n][$l].'<br />';
				 $qryt = "insert into product_details_local 
											( 
											p_code, 
											day, 
											local_code, 
											r_rate,
											position
											)
											values
											( 
											'$pcode', 
											'$kk', 
											'".$singleDayTour[$n][$l]."', 
											'".$percentage[$n][$l]."' ,
											'".$pos[$n][$l]."'
											)";
				
				 $rstt = mysql_query($qryt, $dbConn);
				// echo $qryt.'<br />';
			 }

		  }
		  $qry1 = "insert into product_details 
						( 
						p_code, 
						day, 
						area, 
						content, 
						app_content, 
						meal_black, 
						meal_lunch, 
						meal_dinner, 
						meal_black1, 
						meal_lunch1, 
						meal_dinner1, 
						meal_black11, 
						meal_lunch11, 
						meal_dinner11, 
						hotel, 
						wdate
						)
						values
						( 
						'$pcode', 
						'$kk', 
						'$tourRoute[$k]', 
						'".addslashes($tourDesc[$k])."', 
						'', 
						'$meal1[$k]', 
						'$meal2[$k]', 
						'$meal3[$k]', 
						'$meal4[$k]', 
						'$meal5[$k]', 
						'$meal6[$k]', 
						'$mealnm1[$k]', 
						'$mealnm2[$k]', 
						'$mealnm3[$k]',
						'".addslashes($hotelName[$k])."', 
						now()
						)
					";
	
		  $rst1 = mysql_query($qry1, $dbConn);

		  $qry1 = "update product_master 
									set
									p_day = '$tourcnt' 
						where p_code ='$pcode'";
		  $rst1 = mysql_query($qry1, $dbConn);
		  
		  
	  }
	  //exit;
	  if($rst1)
	  {
		   Misc::jvAlert("일정표를 저장했습니다.","");
		   echo "<meta http-equiv='refresh' content='0;url=./base_product_m.php?division=2&pdx=$pdx&sub=$sub&ty=$ty&pcode=$pcode'>";	
		   exit;
	  }
	  else
	  {
		   echo "저장실패! 다시시도";
		   exit;
	  }
	 

	 