<?php
     header('Content-Type: text/html; charset=utf-8');
	// echo $pcode."|".$Mode."11111";
	 if (($pcode != "") && ($Mode=='copy')) {	
		   
            $p_num = getPnumber();
			if ($ty == 1) {
				$pcode1 = "SIN";
			} else if ($ty == 2) {
				$pcode1 = "SCO";
			} else if ($ty == 3) {
				$pcode1 = "INL";
			} else if ($ty == 4) {
				$pcode1 = "INC";
			} else if ($ty == 5) {
				$pcode1 = "OUT";
			}
			$p_code = $pcode1.$p_num['numChar'];
		
			
			$qry1 = "insert into product_pick 
								( 
								p_code, 
								seq, 
								pick_area, 
								pick_time, 
								wdate
								)
								select '$p_code', seq,pick_area,pick_time,now() from product_pick
								where p_code= '$pcode'
								";
			
			$rst1 = mysql_query($qry1, $dbConn);
		
			
			
			
		    $qry1 = "insert into product_limit 
								(
								p_code, 
								seq, 
								p_type, 
								p_limitdate, 
								wdate
								)
								select '$p_code',seq,'L',p_limitdate,now() from product_limit
								where p_code= '$pcode' && p_type='L'
								";
			$rst1 = mysql_query($qry1, $dbConn);
			

			$qry1 = "insert into product_limit 
								(
								p_code, 
								seq, 
								p_type, 
								p_limitdate, 
								wdate
								)
								select '$p_code',seq,'R',p_limitdate,now() from product_limit
								where p_code= '$pcode' && p_type='R'
								";
			$rst1 = mysql_query($qry1, $dbConn);

		
			
			
		    $qry1 = "insert into product_master 
								( 
								num,
								m_dept,
								p_dept,
								m_type,
								p_type, 
								base_rate,
								t_code1,
								t_code2,
								c_code1, 
								c_code2, 
								c_code3, 
								c_code4,
								s_cate, 
								tour_area_value,
								tour_area_value0,
								r_rate,
								p_code, 
								p_name, 
								p_own, 
								p_day, 
								p_cnt, 
								p_scnt, 
								price_0dadult, 
								price_0dchild, 
								price_0adult, 
								price_0child, 
								price_0cadult, 
								price_0cchild, 
								oprice_0cadult,
								price_1dadult, 
								price_1dchild, 
								price_1adult, 
								price_1child, 
								price_1cadult, 
								price_1cchild, 
								oprice_1cadult,
								price_2dadult, 
								price_2dchild, 
								price_2adult, 
								price_2child, 
								price_2cadult, 
								price_2cchild,
								oprice_2cadult,
								price_3dadult, 
								price_3dchild, 
								price_3adult, 
								price_3child, 
								price_3cadult, 
								price_3cchild,
								oprice_3cadult,
								price_4dadult, 
								price_4dchild, 
								price_4adult, 
								price_4child, 
								price_4cadult, 
								price_4cchild,
								oprice_4cadult,
								price_5dadult, 
								price_5dchild, 
								price_5adult, 
								price_5child, 
								price_5cadult, 
								price_5cchild,
								oprice_5cadult,
								price_busodadult, 
								price_busodchild, 
								price_busoadult, 
								price_busochild, 
								price_busocadult, 
								price_busocchild,
								oprice_busocadult,
								price_busrdadult, 
								price_busrdchild, 
								price_busradult, 
								price_busrchild, 
								price_busrcadult, 
								price_busrcchild, 
								oprice_busrcadult,
								p_vstart, 
								p_vend, 
								p_week, 
								t_addr,
								p_sdesc, 
								p_mimg, 
								p_img1, 
								p_img2, 
								p_img3, 
								p_img4, 
								p_img5, 
								p_img6, 
								p_img7, 
								p_img8, 
								p_img9, 
								p_img10,
								p_tmap, 
								p_4sdesc, 
								p_include, 
								p_uninclude, 
								p_otrip, 
								p_prepare, 
								p_ref, 
								p_spec, 
								p_display, 
								p_pay,
								bgcolor,
								grp,
								p_register, 
								wdate
								)
								select 
								'{$p_num['num']}',
								m_dept,
								p_dept,
								m_type,
								p_type, 
								base_rate,
								t_code1,
								t_code2,
								c_code1, 
								c_code2, 
								c_code3, 
								c_code4,
								s_cate, 
								tour_area_value,
								tour_area_value0,
								r_rate,
								'$p_code', 
								p_name, 
								p_own, 
								p_day, 
								p_cnt, 
								p_scnt, 
								price_0dadult, 
								price_0dchild, 
								price_0adult, 
								price_0child, 
								price_0cadult, 
								price_0cchild, 
								oprice_0cadult,
								price_1dadult, 
								price_1dchild, 
								price_1adult, 
								price_1child, 
								price_1cadult, 
								price_1cchild, 
								oprice_1cadult,
								price_2dadult, 
								price_2dchild, 
								price_2adult, 
								price_2child, 
								price_2cadult, 
								price_2cchild,
								oprice_2cadult,
								price_3dadult, 
								price_3dchild, 
								price_3adult, 
								price_3child, 
								price_3cadult, 
								price_3cchild,
								oprice_3cadult,
								price_4dadult, 
								price_4dchild, 
								price_4adult, 
								price_4child, 
								price_4cadult, 
								price_4cchild,
								oprice_4cadult,
								price_5dadult, 
								price_5dchild, 
								price_5adult, 
								price_5child, 
								price_5cadult, 
								price_5cchild,
								oprice_5cadult,
								price_busodadult, 
								price_busodchild, 
								price_busoadult, 
								price_busochild, 
								price_busocadult, 
								price_busocchild,
								oprice_busocadult,
								price_busrdadult, 
								price_busrdchild, 
								price_busradult, 
								price_busrchild, 
								price_busrcadult, 
								price_busrcchild, 
								oprice_busrcadult,
								p_vstart, 
								p_vend, 
								p_week, 
								t_addr,
								p_sdesc, 
								p_mimg, 
								p_img1, 
								p_img2, 
								p_img3, 
								p_img4, 
								p_img5, 
								p_img6, 
								p_img7, 
								p_img8, 
								p_img9, 
								p_img10,
								p_tmap, 
								p_4sdesc, 
								p_include, 
								p_uninclude, 
								p_otrip, 
								p_prepare, 
								p_ref, 
								p_spec, 
								p_display, 
								p_pay,
								bgcolor,
								grp,
								p_register, 
								now()
								from product_master where p_code='$pcode'
								";
		   // echo $qry1;
		   // exit;
		    $rst1 = mysql_query($qry1, $dbConn);
			//include "inc_cprodscsave.php?p_code=$p_code&pcode=$pcode";

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
			//echo $qryt;
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
								'$p_code', 
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
			//echo $qry1;
			//exit;
			$rst1 = mysql_query($qry1, $dbConn);

		    if($rst1)
		    {
			   Misc::jvAlert("복사했습니다.","");
			   echo "<meta http-equiv='refresh' content='0;url=./base_product_m.php?division=2&pdx=$pdx&sub=$sub&ty=$ty&pcode=$p_code'>";	
			   exit;
		    }
		    else
		    {
			   echo "저장실패! 다시시도";
			   exit;
		    }
		 
	 } 