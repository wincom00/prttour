<?php
      header('Content-Type: text/html; charset=utf-8');
	  $qry4 = "DELETE FROM product_details WHERE p_code = '$p_code' " ;
	  $rst4 = mysql_query($qry4);
	  $qry4 = "DELETE FROM product_details_local WHERE p_code = '$p_code' " ;
	  $rst4 = mysql_query($qry4);
	
	  
	 $qryt = "insert into product_details_local 
							( 
							p_code, 
							day, 
							local_code, 
							r_rate,
							position
							)
							select
							'$p_code',
							day, 
							local_code, 
							r_rate,
							position
							from product_details_local
							where p_code= '$pcode'
							";

	$rstt = mysql_query($qryt, $dbConn);
				 //echo $qryt.'<br />';
			//exit;
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
						select
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
						now()
						from product_details
						where p_code='$pcode'
					";
	
	$rst1 = mysql_query($qry1, $dbConn);

		  
	 