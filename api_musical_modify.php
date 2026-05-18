<?php
	include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	} else {
		echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	/*if (!hasMenuAccess($division, $pdx, $sub)) {
		 $goUrl_1 = "index.php";
		   Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
			 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			 exit;
	}
	*/
	$no = isset($_GET['no']) ? intval($_GET['no']) : (isset($_POST['no']) ? intval($_POST['no']) : 0);
	$m_qry1 = "select * from api_musical where seq_no = '$no'";
	$m_rst1 = mysql_query($m_qry1);
	$m_info = mysql_fetch_assoc($m_rst1);
    
	
	$day_time_array = explode("@",$m_info['day_time']);
	$night_time_array = explode("@",$m_info['night_time']);
    if($mode == "save")
	{

		//echo $tmpName0."TETS";
		//		exit;
        if ($no == "") {
		// 상품이미지
		
				$tmpName0 = $_FILES['userfile0']['tmp_name'];
				
				if(is_uploaded_file($tmpName0)){
						$pds_file0 = $_FILES['userfile0']['name'];
						$board_pds_pos = "./product_img";
						$attc_name0 = Misc::uploadFileUnsafely($tmpName0 , $pds_file0, $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name0['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name0['savedName']}";     //-- 저장 

						$quality = '80';    //-- jpg 퀄리티 
						$size = '130';    //-- 줄일 크기 pixel (너비, 또는 높이에 적용) 
						$ratio = '4:3';        //-- 이미지를 4:3 비율로 잘라냄 
						$ratio = 'false';        //-- 원본 이미지비율을 유지 

						$get_size = _getimagesize($src, $size, $ratio); 
						$result = resize_image($dst, $src, $get_size, $quality, $ratio); 

				}

				

				$tmpName1 = $_FILES['userfile1']['tmp_name'];

				if(is_uploaded_file($tmpName1)){
						$pds_file1 = $_FILES['userfile1']['name'];
						$board_pds_pos = "./product_img";
						$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name1['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name1['savedName']}";     //-- 저장 

						

						}

				$tmpName2 = $_FILES['userfile2']['tmp_name'];

				if(is_uploaded_file($tmpName2)){
						$pds_file2 = $_FILES['userfile2']['name'];
						$board_pds_pos = "./product_img";
						$attc_name2 = Misc::uploadFileUnsafely($tmpName2 , $pds_file2 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name2['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name2['savedName']}";     //-- 저장 

						}

				$tmpName3 = $_FILES['userfile3']['tmp_name'];

				if(is_uploaded_file($tmpName3)){
						$pds_file3 = $_FILES['userfile3']['name'];
						$board_pds_pos = "./product_img";
						$attc_name3 = Misc::uploadFileUnsafely($tmpName3 , $pds_file3 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name3['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name3['savedName']}";     //-- 저장 

						
						}

				$tmpName4 = $_FILES['userfile4']['tmp_name'];

				if(is_uploaded_file($tmpName4)){
						$pds_file4 = $_FILES['userfile4']['name'];
						$board_pds_pos = "./product_img";
						$attc_name4 = Misc::uploadFileUnsafely($tmpName4 , $pds_file4 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name4['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name4['savedName']}";     //-- 저장 

						
						}

				$tmpName5 = $_FILES['userfile5']['tmp_name'];

				if(is_uploaded_file($tmpName5)){
						$pds_file5 = $_FILES['userfile5']['name'];
						$board_pds_pos = "./product_img";
						$attc_name5 = Misc::uploadFileUnsafely($tmpName5 , $pds_file5 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name5['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name5['savedName']}";     //-- 저장 

						
						}

                 $tmpName6 = $_FILES['userfile6']['tmp_name'];

				if(is_uploaded_file($tmpName6)){
						$pds_file6 = $_FILES['userfile6']['name'];
						$board_pds_pos = "./product_img";
						$attc_name6 = Misc::uploadFileUnsafely($tmpName6 , $pds_file6 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name6['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name6['savedName']}";     //-- 저장 

						
						}

                 $tmpName7 = $_FILES['userfile7']['tmp_name'];

				if(is_uploaded_file($tmpName7)){
						$pds_file7 = $_FILES['userfile7']['name'];
						$board_pds_pos = "./product_img";
						$attc_name7 = Misc::uploadFileUnsafely($tmpName7 , $pds_file7 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name7['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name7['savedName']}";     //-- 저장 

						
						}


                $tmpName8 = $_FILES['userfile8']['tmp_name'];

				if(is_uploaded_file($tmpName8)){
						$pds_file8 = $_FILES['userfile8']['name'];
						$board_pds_pos = "./product_img";
						$attc_name8 = Misc::uploadFileUnsafely($tmpName8 , $pds_file8 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name8['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name8['savedName']}";     //-- 저장 

						
						}
                

				$tmpName9 = $_FILES['userfile9']['tmp_name'];

				if(is_uploaded_file($tmpName9)){
						$pds_file9 = $_FILES['userfile9']['name'];
						$board_pds_pos = "./product_img";
						$attc_name9 = Misc::uploadFileUnsafely($tmpName9 , $pds_file9 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name9['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name9['savedName']}";     //-- 저장 

						
						}

                $tmpName10 = $_FILES['userfile10']['tmp_name'];

				if(is_uploaded_file($tmpName10)){
						$pds_file9 = $_FILES['userfile10']['name'];
						$board_pds_pos = "./product_img";
						$attc_name10 = Misc::uploadFileUnsafely($tmpName10 , $pds_file10 , $board_pds_pos);

						$src = PRODUCT_IMG_URL."{$attc_name10['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name10['savedName']}";     //-- 저장 

						
						}
				$m_name_eng = addslashes($m_name_eng);
				$m_name_kor = addslashes($m_name_kor);
				$simply_desc = addslashes($simply_desc);
				$FCKeditor1 = addslashes($FCKeditor1);
				$FCKeditor2 = addslashes($FCKeditor2);
				$FCKeditor3 = addslashes($FCKeditor3);


				for($m=0; $m<count($m_day_time); $m++)
				{
					$m_day_time_value .= $m_day_time[$m]."@";
				}

				for($n=0; $n<count($m_night_time); $n++)
				{
					$m_night_time_value .= $m_night_time[$m]."@";
				}

				$m_id = trim($m_id);

				$qry1 = "insert into api_musical (m_id,
																	m_category,
																	m_code,
																	m_name_eng,
																	m_name_kor,
																	simply_desc,
																	m_city,
																	theater_name,
																	theater_phone,
																	theater_address,
																	theater_city,
																	theater_state,
																	theater_zipcode,
																	day_time,
																	night_time,
																	userfile0,
																	userfile1,
																	userfile2,
																	userfile3,
																	userfile4,
																	userfile5,
																	userfile6,
																	userfile7,
																	userfile8,
																	userfile9,
																	userfile10,
																	description,
																	notice,
																	video_you,
																	view_opt,
																	sale_flag,
																	m_rate,
																	rate,
																	our_price_msg,
																	booth_price_msg,
																	broker_price_msg,
																	ranking) values ('$m_id',
																							'$m_category',
																							'$m_code',
																							'$m_name_eng',
																							'$m_name_kor',
																							'$simply_desc',
																							'$m_city',
																							'$theater_name',
																							'$theater_phone',
																							'$theater_address',
																							'$theater_city',
																							'$theater_state',
																							'$theater_zipcode',
																							'$m_day_time_value',
																							'$m_night_time_value',
																							'{$attc_name0['savedName']}',
																							'{$attc_name1['savedName']}',
																							'{$attc_name2['savedName']}',
																							'{$attc_name3['savedName']}',
																							'{$attc_name4['savedName']}',
																							'{$attc_name5['savedName']}',
																							'{$attc_name6['savedName']}',
																							'{$attc_name7['savedName']}',
																							'{$attc_name8['savedName']}',
																							'{$attc_name9['savedName']}',
																							'{$attc_name10['savedName']}',
																							'$FCKeditor1',
																							'$FCKeditor2',
																							'$FCKeditor3',
																							'$view_opt',
																							'$sale_flag',
																							'$m_rate',
																							'$rate',
																							'$our_price_msg',
																							'$booth_price_msg',
																							'$broker_price_msg',
																							'100')";
				

				$rst1 = mysql_query($qry1);



				if($rst1)
				{
					echo "<meta http-equiv='refresh' content='0 url=./api_musical_regi.php?division=2&pdx=1&sub=35'>";
					exit;
				}
				else
				{
					echo "fail";
					exit;
				}




	  } else {

                if($photo_del0 != "1")
				{
					$ftmp = $_FILES['image']['tmp_name'];
					$oname = $_FILES['image']['name'];
					$fname = PRODUCT_IMG_URL.$_FILES['image']['name'];

					if(empty($_FILES['userfile0']['name']))
					{
						$attc_name0['savedName'] = $m_info['userfile0'];
					}
					else
					{
						$tmpName0 = $_FILES['userfile0']['tmp_name'];

						if(is_uploaded_file($tmpName0)){
								$pds_file0 = $_FILES['userfile0']['name'];
								$board_pds_pos = "./product_img";
								$attc_name0 = Misc::uploadFileUnsafely($tmpName0 , $pds_file0 , $board_pds_pos);
						}
						
						$src = PRODUCT_IMG_URL."{$attc_name0['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name0['savedName']}";     //-- 저장 

						///$quality = '80';    //-- jpg 퀄리티 
						////$size = '130';    //-- 줄일 크기 pixel (너비, 또는 높이에 적용) 
						//$ratio = '4:3';        //-- 이미지를 4:3 비율로 잘라냄 
						//$ratio = 'false';        //-- 원본 이미지비율을 유지 

						//$get_size = _getimagesize($src, $size, $ratio); 
						//$result = resize_image($dst, $src, $get_size, $quality, $ratio); 
						//print_r($attc_name0[savedName]);
						//exit;
					}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile0']}");
					$attc_name0['savedName'] = "";
				} 

				if($photo_del1 != "1")
				{
					$ftmp = $_FILES['image']['tmp_name'];
					$oname = $_FILES['image']['name'];
					$fname = PRODUCT_IMG_URL.$_FILES['image']['name'];

					if(empty($_FILES['userfile1']['name']))
					{
						$attc_name1['savedName'] = $m_info['userfile1'];
					}
					else
					{
						$tmpName1 = $_FILES['userfile1']['tmp_name'];

						if(is_uploaded_file($tmpName1)){
								$pds_file1 = $_FILES['userfile1']['name'];
								$board_pds_pos = "./product_img";
								$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);
								}

						$src = PRODUCT_IMG_URL."{$attc_name1['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name1['savedName']}";     //-- 저장 

					

					}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile1']}");
					$attc_name1['savedName'] = "";
				}

				if($photo_del2 != "1")
				{
					if(empty($_FILES['userfile2']['name']))
						{
						$attc_name2['savedName'] = $m_info['userfile2'];
						}
					else
					{
						$tmpName2 = $_FILES['userfile2']['tmp_name'];

						if(is_uploaded_file($tmpName2)){
							$pds_file2 = $_FILES['userfile2']['name'];
							$board_pds_pos = "./product_img";
							$attc_name2 = Misc::uploadFileUnsafely($tmpName2 , $pds_file2 , $board_pds_pos);
						}
						$src = PRODUCT_IMG_URL."{$attc_name2['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name2['savedName']}"; 

					}
						
				}
				else
				{
					@unlink("./upload/{$m_info['userfile2']}");
					$attc_name2['savedName'] = "";
				}

				if($photo_del3 != "1")
				{
					if(empty($_FILES['userfile3']['name']))
					{
						$attc_name3['savedName'] = $m_info['userfile3'];
					}
					else
					{
						$tmpName3 = $_FILES['userfile3']['tmp_name'];

						if(is_uploaded_file($tmpName3)){
							$pds_file3 = $_FILES['userfile3']['name'];
							$board_pds_pos = "./product_img";
							$attc_name3 = Misc::uploadFileUnsafely($tmpName3 , $pds_file3 , $board_pds_pos);
						}


						$src = PRODUCT_IMG_URL."{$attc_name3['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name3['savedName']}";     //-- 저장 

						
					}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile3']}");
					$attc_name3['savedName'] = "";
				}


				if($photo_del4 != "1")
				{
					if(empty($_FILES['userfile4']['name']))
						{
						$attc_name4['savedName'] = $m_info['userfile4'];
						}
					else
						{
							$tmpName4 = $_FILES['userfile4']['tmp_name'];

							if(is_uploaded_file($tmpName4)){
									$pds_file4 = $_FILES['userfile4']['name'];
									$board_pds_pos = "./product_img";
									$attc_name4 = Misc::uploadFileUnsafely($tmpName4 , $pds_file4 , $board_pds_pos);
							}


							$src = PRODUCT_IMG_URL."{$attc_name4['savedName']}";        //-- 원본 
							$dst = './upload/thum_'."{$attc_name4['savedName']}";     //-- 저장 

						
						}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile4']}");
					$attc_name4['savedName'] = "";
				}


				if($photo_del5 != "1")
				{
					if(empty($_FILES['userfile5']['name']))
					{
					$attc_name5['savedName'] = $m_info['userfile5'];
					}
					else
					{
						$tmpName5 = $_FILES['userfile5']['tmp_name'];

						if(is_uploaded_file($tmpName5)){
								$pds_file5 = $_FILES['userfile5']['name'];
								$board_pds_pos = "./product_img";
								$attc_name5 = Misc::uploadFileUnsafely($tmpName5 , $pds_file5 , $board_pds_pos);
								}


						$src = PRODUCT_IMG_URL."{$attc_name5['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name5['savedName']}";     //-- 저장 

						
						}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile5']}");
					$attc_name5['savedName'] = "";
				}
                

				if($photo_del6 != "1")
				{
					if(empty($_FILES['userfil6']['name']))
						{
						$attc_name6['savedName'] = $m_info['userfile6'];
						}
					else
						{
						$tmpName6 = $_FILES['userfile6']['tmp_name'];

						if(is_uploaded_file($tmpName6)){
							$pds_file6 = $_FILES['userfile6']['name'];
							$board_pds_pos = "./product_img";
							$attc_name6 = Misc::uploadFileUnsafely($tmpName6 , $pds_file6 , $board_pds_pos);
						}


							$src = PRODUCT_IMG_URL."{$attc_name6['savedName']}";        //-- 원본 
							$dst = './upload/thum_'."{$attc_name6['savedName']}";     //-- 저장 

						
						}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile6']}");
					$attc_name6['savedName'] = "";
				}


                if($photo_del7 != "1")
				{
					if(empty($_FILES['userfil7']['name']))
						{
						$attc_name7['savedName'] = $m_info['userfile7'];
						}
					else
						{
						$tmpName7 = $_FILES['userfile7']['tmp_name'];

						if(is_uploaded_file($tmpName7)){
								$pds_file7 = $_FILES['userfile7']['name'];
								$board_pds_pos = "./product_img";
								$attc_name7 = Misc::uploadFileUnsafely($tmpName7 , $pds_file7 , $board_pds_pos);
								}


						$src = PRODUCT_IMG_URL."{$attc_name7['savedName']}";        //-- 원본 
						$dst = './upload/thum_'."{$attc_name7['savedName']}";     //-- 저장 

						
						}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile7']}");
					$attc_name7['savedName'] = "";
				}

				
				if($photo_del8 != "1")
				{
					if(empty($_FILES['userfile8']['name']))
					{
						$attc_name8['savedName'] = $m_info['userfile8'];
					}
					else
					{
						$tmpName8 = $_FILES['userfile8']['tmp_name'];

						if(is_uploaded_file($tmpName7)){
								$pds_file8 = $_FILES['userfile8']['name'];
								$board_pds_pos = "./product_img";
								$attc_name8 = Misc::uploadFileUnsafely($tmpName8 , $pds_file8 , $board_pds_pos);
						}


					
					}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile8']}");
					$attc_name8['savedName'] = "";
				}


				if($photo_del9 != "1")
				{
					if(empty($_FILES['userfile9']['name']))
						{
						$attc_name9['savedName'] = $m_info['userfile9'];
						}
					else
						{
						$tmpName9 = $_FILES['userfile9']['tmp_name'];

						if(is_uploaded_file($tmpName7)){
								$pds_file9 = $_FILES['userfile9']['name'];
								$board_pds_pos = "./product_img";
								$attc_name9 = Misc::uploadFileUnsafely($tmpName9 , $pds_file9 , $board_pds_pos);
								}


						
						}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile9']}");
					$attc_name9['savedName'] = "";
				}
                
				 
                if($photo_del10 != "1")
				{
					if(empty($_FILES['userfile10']['name']))
						{
						$attc_name10['savedName'] = $m_info['userfile10'];
						}
					else
						{
						$tmpName10 = $_FILES['userfile10']['tmp_name'];

						if(is_uploaded_file($tmpName7)){
								$pds_file10 = $_FILES['userfile10']['name'];
								$board_pds_pos = "./product_img";
								$attc_name10 = Misc::uploadFileUnsafely($tmpName10 , $pds_file10 , $board_pds_pos);
								}


						
						}
				}
				else
				{
					@unlink("./upload/{$m_info['userfile10']}");
					$attc_name10['savedName'] = "";
				}
				$m_name_eng = addslashes($m_name_eng);
				$m_name_kor = addslashes($m_name_kor);
				$simply_desc = addslashes($simply_desc);
				$FCKeditor1 = addslashes($FCKeditor1);
				$FCKeditor2 = addslashes($FCKeditor2);
				$FCKeditor3 = addslashes($FCKeditor3);

				for($m=0; $m<count($m_day_time); $m++)
				{
					$m_day_time_value .= $m_day_time[$m]."@";
				}

				for($n=0; $n<count($m_night_time); $n++)
				{
					$m_night_time_value .= $m_night_time[$n]."@";
				}
				$m_code = trim($m_code);
				$qry1 = "update api_musical set m_id = '$m_id',
																	m_category = '$m_category',
																	m_code = '$m_code',
																	m_name_eng = '$m_name_eng',
																	m_name_kor = '$m_name_kor',
																	simply_desc = '$simply_desc',
																	m_city = '$m_city',
																	theater_name = '$theater_name',
																	theater_phone = '$theater_phone',
																	theater_address = '$theater_address',
																	theater_city = '$theater_city',
																	theater_state = '$theater_state',
																	theater_zipcode = '$theater_zipcode',
																	day_time = '$m_day_time_value',
																	night_time = '$m_night_time_value',
																	sale_flag = '$sale_flag',
																	m_rate = '$m_rate',
																	rate = '$rate',
																	userfile0 = '{$attc_name0['savedName']}',
																	userfile1 = '{$attc_name1['savedName']}',
																	userfile2 = '{$attc_name2['savedName']}',
																	userfile3 = '{$attc_name3['savedName']}',
																	userfile4 = '{$attc_name4['savedName']}',
																	userfile5 = '{$attc_name5['savedName']}',
																	userfile6 = '{$attc_name6['savedName']}',
																	userfile7 = '{$attc_name7['savedName']}',
																	userfile8 = '{$attc_name8['savedName']}',
																	userfile9 = '{$attc_name9['savedName']}',
																	userfile10 = '{$attc_name10['savedName']}',
																	description = '$FCKeditor1',
																	notice = '$FCKeditor2',
																	video_you = '$FCKeditor3',
																	view_opt = '$view_opt',
																	our_price_msg = '$our_price_msg',
																	booth_price_msg = '$booth_price_msg',
																	broker_price_msg = '$broker_price_msg' 
																	where seq_no = '$no'";
									
				
				//echo $qry1;
				//exit;
				$rst1 = mysql_query($qry1);



				if($rst1)
				{
					//echo "<meta http-equiv='refresh' content='0 url=./api_musical_list.php?division=2'>";
					Misc::jvAlert("Completed!","location.replace('./api_musical_regi.php?division=2&pdx=1&sub=35')");
					exit;
				}
				else
				{
					echo "fail";
					exit;
				}







	  }
	} else if ($mode == "del") {

			$qry1 = "DELETE FROM api_musical WHERE seq_no = '$no'";
			$rst1 = mysql_query($qry1, $dbConn);

			
		   
			header('Location: api_musical_regi.php?division=2&pdx=1&sub=35') ;

	}

				

?>


<script src="./ckeditor/ckeditor.js"></script>
<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">상품관리</a></li>
					<li>상품등록</li>
					<li>뮤지컬등록</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
				  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&no=<?=$no?>" name=product method=post Enctype="multipart/form-data">
				  <input type=hidden name=mode value="save">
				  <input type=hidden name=division value="<?= $division ?>">
				  <input type=hidden name=save_type value="">
				  <input type=hidden name=no value="<?= $no ?>">
					<table id="level4" class="txt_12" width="98%" align="center" border="0" cellspacing="0" cellpadding="0">
			
						
						<tr>
							<td colspan="4" height="50" align="center" bgcolor="#FFFFFF"><button type=button  class="btn btn-primary btn-sm btn1" onClick="go_submit('app')">뮤지컬 등록</button><td colspan="4" height="50" align="center" bgcolor="#FFFFFF"><button type="button" class="btn btn-primary btn-sm btn1" onClick="go_delete('<?= $no ?>')"> 뮤지컬삭제</button></td>
							
							<td bgcolor=#FFFFFF height=25 align=right>&nbsp;</td>
						</tr>
						</tr>
						
					  </table>
					  <br>
					  <table class="table table-striped table-bordered table-condensed">
					  <script>
						$(document).ready(function() {
								CKEDITOR.replace( 'FCKeditor1',		{ height: '220px' } );
								CKEDITOR.replace( 'FCKeditor2',		{ height: '220px' } );
								CKEDITOR.replace( 'FCKeditor3',		{ height: '220px' } );
							});
						function go_submit(tf){

							if(tf == 'tmp')
							{
								document.product.save_type.value = 'tmp';
							}
							else
							{
								document.product.save_type.value = 'app';
							}

							if(!document.product.m_id.value)
							  {
									alert('뮤지컬ID를 넣으세요!');
									document.m_id.p_code.focus();
									return false;
							  }			 
							if(!document.product.m_code.value)
							  {
									alert('뮤지컬코드를 넣으세요!');
									document.m_code.p_name.focus();
									return false;
							  }		
							document.product.submit();
						}
						function go_delete(no) {
							if(confirm("삭제하시겠습니까?") == true) {
								location.replace('api_musical_modify.php?mode=del&division=2&pdx=1&sub=35&no=' + no);
							}
							else return;
						}
					  </script>
					  
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>뮤지컬 진열여부</td>
							<td colspan=3 bgcolor=#FFFFFF>&nbsp;<select name=view_opt>
							<option value="YES" <?php if($m_info['view_opt'] == "YES") echo "selected"; ?>>진열
							<option value="NO" <?php if($m_info['view_opt'] == "NO") echo "selected"; ?>>숨기기
							</select></td>
						</tr>
						
						<tr bgcolor=#f9f9f9 height=28>
							<td width=15% align=center>뮤지컬 ID</td>
							<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=m_id size=30 class="form_box" value="<?= $m_info['m_id'] ?>"></td>
							<td width=15% align=center>뮤지컬 CODE</td>
							<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=m_code  size=20 class="form_box" value="<?= $m_info['m_code'] ?>"></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td width=15% align=center>뮤지컬 Name (Eng)</td>
							<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=m_name_eng size=20 class="form_box" value="<?= $m_info['m_name_eng'] ?>"></td>
							<td width=15% align=center>뮤지컬 Name (한글)</td>
							<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=m_name_kor  size=20 class="form_box" value="<?= $m_info['m_name_kor'] ?>"></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>간단소개</td>
							<td colspan=3 bgcolor=#FFFFFF>&nbsp;<textarea name=simply_desc cols=70 rows=5><?= $m_info['simply_desc'] ?></textarea></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>뮤지컬 공연지역</td>
							<td colspan=3 bgcolor=#FFFFFF>&nbsp;<select name=m_city>
							<option value="NYCA" <?php if($m_info['m_city'] == "NYCA") echo "selected"; ?>>NewYork
							<option value="LASV" <?php if($m_info['m_city'] == "LASV") echo "selected"; ?>>Las Vegas
							<option value="NYCA" <?php if($m_info['m_city'] == "NYCS") echo "selected"; ?>>NewYork Sports
							</select></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>공연극장 이름</td>
							<td bgcolor=#FFFFFF>&nbsp;<input type=text name=theater_name  size=30 class="form_box" value="<?= $m_info['theater_name'] ?>"></td>
							<td align=center>공연극장 전화</td>
							<td bgcolor=#FFFFFF>&nbsp;<input type=text name=theater_phone  size=30 class="form_box" value="<?= $m_info['theater_phone'] ?>"></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>주소</td>
							<td bgcolor=#FFFFFF>&nbsp;<input type=text name=theater_address  size=30 class="form_box" value="<?= $m_info['theater_address'] ?>"></td>
							<td align=center>City</td>
							<td bgcolor=#FFFFFF>&nbsp;<input type=text name=theater_city  size=30 class="form_box" value="<?= $m_info['theater_city'] ?>"></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>State</td>
							<td bgcolor=#FFFFFF>&nbsp;<input type=text name=theater_state  size=16 class="form_box" value="<?= $m_info['theater_state'] ?>"></td>
							<td align=center>Zipcode</td>
							<td bgcolor=#FFFFFF>&nbsp;<input type=text name=theater_zipcode  size=5 class="form_box" value="<?= $m_info['theater_zipcode'] ?>"></td>
						</tr>
						
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>판매가</td>
							<td bgcolor=#FFFFFF>&nbsp;
							<select name=sale_flag>
							<option value="flat" <?php if($m_info['sale_flag'] == "flat") echo "selected"; ?>>$
							<option value="percent" <?php if($m_info['sale_flag'] == "percent") echo "selected"; ?>>%
							</select>&nbsp;<input type=text name=m_rate  size=4 class="form_box" value="<?= $m_info['m_rate'] ?>"></td>
							<td align=center>추천별표(ex-3.5)</td>
							<td bgcolor=#FFFFFF>&nbsp;<input type=text name=rate  size=4 class="form_box" value="<?= $m_info['rate'] ?>"></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>우리판매가격</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=text name=our_price_msg  size=12 class="form_box" value="<?= $m_info['our_price_msg'] ?>"></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>부스판매가격</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=text name=booth_price_msg  size=12 class="form_box" value="<?= $m_info['booth_price_msg'] ?>"></td>
						</tr>
						
						
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (banner)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile0']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile0'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del0 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지 (banner)<br> 600 X 400 pixel </td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile0 size=30 class="form_box"></td>
						</tr>

						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (1)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile1']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile1'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del1 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지 (1-메인)<br> 1660 X 552 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile1 size=30 class="form_box"></td>
						</tr>

						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (2)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile2']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile2'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del2 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지 (2)<br> 1000 X 556 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile2 size=30 class="form_box"></td>
						</tr>

						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (3)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile3']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile3'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del3 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지 (3)<br> 1000 X 556 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile3 size=30 class="form_box"></td>
						</tr>

						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (4)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile4']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile4'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del4 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지 (4)<br> 1000 X 556 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile4 size=30 class="form_box"></td>
						</tr>

						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (5)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile5']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile5'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del5 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지(5)<br> 1000 X 556 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile5 size=30 class="form_box"></td>
						</tr>
						
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (6)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile6']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile6'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del6 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지(6)<br> 1000 X 556 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile6 size=30 class="form_box"></td>
						</tr>

						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (7)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile7']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile7'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del7 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지(7)<br> 1000 X 556 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile7 size=30 class="form_box"></td>
						</tr>

						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (8)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile8']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile8'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del8 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지(8)<br> 1000 X 556 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile8 size=30 class="form_box"></td>
						</tr>

						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (9)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile9']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile9'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del9 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지(9)<br> 1000 X 556 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile9 size=30 class="form_box"></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>현재 이미지 (10)</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<?php if($m_info['userfile10']): ?><img src="<?= PRODUCT_IMG_URL ?><?= $m_info['userfile10'] ?>" width=80 height=70><?php endif; ?>&nbsp;<input type=checkbox name=photo_del10 value="1"> Delete Image file</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>이미지(10)<br> 1000 X 556 pixel</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<input type=file name=userfile10 size=30 class="form_box"></td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>공연설명</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;<textarea name="FCKeditor1"  ><?=$m_info['description']?></textarea>
							
							</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>기타 주의사항</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;&nbsp;<textarea name="FCKeditor2"  ><?=$m_info['notice']?></textarea>
								
							</td>
						</tr>
						<tr bgcolor=#f9f9f9 height=28>
							<td align=center>비디오 유투브</td>
							<td bgcolor=#FFFFFF colspan=3>&nbsp;&nbsp;<textarea name="FCKeditor3"  ><?=$m_info['video_you']?></textarea>
				
				<!-- 편집기 끝 -->					
							</td>
						</tr>
						<tr>
							<td colspan=4 height=50 align=center bgcolor=#FFFFFF><button type=button  class="btn btn-primary btn-sm btn1" onClick="go_submit('app')">뮤지컬 등록</button></td>
						</tr>
					</table>
				 </form>
	            </div>
			 </div>
		</div>
	</div>
	<?php
		include "include/side_m.php"
	?>
    </body>
</html>