<?php
    include "include/header.php";
	///include "include/inc_base.php";
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    if (!hasMenuAccess($division, $pdx, $sub)) {
    	 $goUrl_1 = "index.php";
		   Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		 	 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			 exit;
    }
    if ($mode == "save") {
		  if ($seq_no == "") {
		        // 상품이미지
				$tmpName1 = $_FILES['userfile1']['tmp_name'];

				$ftmp = $_FILES['image']['tmp_name'];
				$oname = $_FILES['image']['name'];
				$fname = UPLOAD_URL.$_FILES['image']['name'];

				if(is_uploaded_file($tmpName1)){
						$pds_file1 = $_FILES['userfile1']['name'];
						$board_pds_pos = "product_img";
						$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);

						$src = UPLOAD_URL."{$attc_name1['savedName']}";        //-- 원본 
						$dst = 'upload/thum_'."{$attc_name1['savedName']}";     //-- 저장 

						$quality = '80';    //-- jpg 퀄리티 
						$size = '70';    //-- 줄일 크기 pixel (너비, 또는 높이에 적용) 
						$ratio = '4:3';        //-- 이미지를 4:3 비율로 잘라냄 
						$ratio = 'false';        //-- 원본 이미지비율을 유지 

						$get_size = _getimagesize($src, $size, $ratio); 
						$result = resize_image($dst, $src, $get_size, $quality, $ratio); 

				}

				$qry1 = "insert into member_list (division, 
																userid,
																passwd,
																kor_name,
																eng_name,
																company_code,
																company_type,
																company_division,
																company_homepage,
																zipcode,
																address,
																city,
																state,
																country,
																company_boss,
																company_manager,
																company_phone,
																company_email,
																guide_rate,
																birthday,
																userfile1,
																guide_status,
																log_cnt,
																dept_prior,
																kakao,
																expire_date) values ('guide',
																									'$userid',
																									'$passwd',
																									'$kor_name',
																									'$eng_name',
																									'$guide_team',
																									'$company_type',
																									'$company_division',
																									'$company_homepage',
																									'$zipcode',
																									'$address',
																									'$city',
																									'$state',
																									'$country',
																									'$company_boss',
																									'$company_manager',
																									'$company_phone',
																									'$company_email',
																									'$guide_rate',
																									'$birthday',
																									'{$attc_name1['savedName']}',
																									'$dept1',
																									'$kakaoid',
																									'$guide_status','0',now())";

				 $rst1 = mysql_query($qry1,$dbConn);
				 
				 $goUrl_1 = "base_guide.php?division=$division&pdx=$pdx&sub=$sub";
			     echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		  } else {

			  
				        $qry3 = "delete from menu_info_user where userid = '$userid'";
				$rst3 = $dbConn->query($qry3);
				
				for($i=1; $i<11; $i++)
				{
					$k = "access_level".$i;
									
					$u = $$k;
					
					
					//echo $u."<br/>";
						if($$k)
						{
							$new_access_level .= $$k."/";
							$s = $MCode[$u-1];
							//Save Menu Items
							$r = $urlA[$u-1];
							//echo $r."<br>";
						   $qry4 = "INSERT INTO `menu_info_user` 
								   (`division`, `parent_idx`, `sub_idx`, `menu_name`, 
								   `menu_link`, `access_level`, `pos`, `menu_code`, `userid`) 
									VALUES
								   ('m', 0, 0, '$s', '$r', 0, 0, '0', '$userid')";

							//print_r($qry4);
							//echo "<br>";
							$rst4 = $dbConn->query($qry4);
						}
					
				}
				//exit;
			 // print_r($_POST["menu2"]);
			  //exit;
			  if(isset($_POST["menu2"])) {
					foreach($_POST["menu2"] as $key => $val) {
						$qry4 = "select * from menu_info_user where seq_no='$val'";
						
						$rst4 = $dbConn->query($qry4);
						$cntm = $rst4->num_rows;
						if ($cntm <= 0) {
							$qry3 = "SELECT * 
								FROM menu_info
								WHERE seq_no='$val'";
							$rst3 = $dbConn->query($qry3);
							
							$cnt = 0;
							while($row2 = $rst3->fetch_assoc())
							{
								  $qry5 = "INSERT INTO `menu_info_user` 
								   (`division`, `parent_idx`, `sub_idx`, `menu_name`, 
								   `menu_link`, `access_level`, `pos`, `menu_code`, `userid`) 
									VALUES
						   ('{$row2['division']}', '{$row2['parent_idx']}', '{$row2['sub_idx']}', '{$row2['menu_name']}', '{$row2['menu_link']}','{$row2['access_level']}', '{$row2['pos']}', '{$row2['menu_code']}', '$userid')";
								$rst5 = $dbConn->query($qry5);
								
							}
					   }
					}
				}
				//exit;
				for($k=0; $k<count((array)$dCode); $k++)
				{
					$deny .= $dCode[$k]."@".$deny_code[$k]."NaN";
				}

			    if($photo_del1 != "1")
				{
					$ftmp = $_FILES['image']['tmp_name'];
					$oname = $_FILES['image']['name'];
					$fname = 'product_img/'.$_FILES['image']['name'];

					if(empty($_FILES['userfile1']['name']))
					{
					     $attc_name1['savedName'] = $old_pic;
					}
					else
					{
						$tmpName1 = $_FILES['userfile1']['tmp_name'];

						if(is_uploaded_file($tmpName1)){
								$pds_file1 = $_FILES['userfile1']['name'];
								$board_pds_pos = "uploads";
								$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);
						}

						$src = 'uploads/'.$attc_name1['savedName'];        //-- 원본
						$dst = 'uploads/thum_'.$attc_name1['savedName'];     //-- 저장

						$quality = '80';    //-- jpg 퀄리티 
						$size = '70';    //-- 줄일 크기 pixel (너비, 또는 높이에 적용) 
						$ratio = '4:3';        //-- 이미지를 4:3 비율로 잘라냄 
						$ratio = 'false';        //-- 원본 이미지비율을 유지 

						$get_size = _getimagesize($src, $size, $ratio); 
						$result = resize_image($dst, $src, $get_size, $quality, $ratio); 

					}
				}
				else
				{
					@unlink("uploads/$old_pic");
					 $attc_name1['savedName'] = "";
				}

				$qry1 = "update member_list set passwd = '$passwd',
											kor_name = '$kor_name',
											eng_name = '$eng_name',
											email = '$email',
											company_code = '$company_code',
											birthday = '$birthday',
											sex = '$sex',
											company_phone='$company_phone',
											access_level = '$new_access_level',
											c_part1 = '$c_part1',
											deny = '$deny',
											ssn = '$ssn',
											grant_s = '$grantmode',
											address = '$address',
											reference = '$reference',
											join_date = '$join_date',
											c_part = '$c_part',
											userfile1 = '{$attc_name1['savedName']}' ,
											area_comp = '$area_comp',
											dept_prior = '$dept1',
											kakao = '$kakaoid',
											guide_status='$guide_status'											
											where seq_no = '$seq_no'";
//echo $qry1;
//exit;
				$rst1 = $dbConn->query($qry1);
				$goUrl_1 = "base_guide.php?division=7&pdx=1&sub=15";
			     echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
                 exit;


		  }
	} 

	$v_info = getinfo_dbMember_byid($id);
	$qry1 = "select * from member_list where seq_no = '$id'";
	$rst1 = $dbConn->query($qry1);
	$row1 = $rst1->fetch_assoc();


	// deny 
	$deny = explode("NaN",$row1['deny']);

	$qry2 = "select * from menu_info_user where division='m' && userid='{$row1['userid']}'";
	$rst2 = $dbConn->query($qry2);
	//$row2 = $rst2->fetch_assoc();

	// url
	$menu_num = array('기초관리' => '1',
					'상품관리' => '2',
					'예약관리' => '3',
					'행사관리' => '4',
					'MIS' => '5',
					'정산관리' => '6',
					'인사관리' => '7',
					'게시판관리' => '8',
					'컨텐츠관리' => '9',
					'고객관리' => '10'
					);
	while($row2 = $rst2->fetch_assoc()) {
		$mName = $row2['menu_name'];
		$url[$menu_num[$mName]] = $row2['menu_link'];
		//echo $row2['menu_link'].$mName."<br />";

	}
	//print_r($url);
	$qry3 = "SELECT *
					FROM menu_info
					WHERE division <> 'm' AND menu_name != '' AND menu_name IS NOT NULL
					ORDER BY division, parent_idx, sub_idx ASC
					";
	$rst3 = $dbConn->query($qry3);
	$cnt = 0;
	while($row2 = $rst3->fetch_assoc())
	{
		    $cnt= $cnt+1;
			if ($row2['pos'] == '0') {

			   $stylec[] = "style='color:red'";
			} else {

			   $stylec[] ="";
			}
			$mnm[] = $row2['menu_name'];
			$mdivision[] = $row2['division'];
			$mpidx1[] = $row2['parent_idx'];
			$mpidx[] = $row2['sub_idx'];
			$macc[] = $row2['access_level'];
			$mpos[] = $row2['pos'];
			$mcode[] = $row2['menu_code'];
			$mseq[] = $row2['seq_no'];


	}
	//print_r($mnm);
	$qry4 = "SELECT * 
					FROM menu_info_user
					WHERE division <> 'm' && userid='{$row1['userid']}'
					ORDER BY division, parent_idx, sub_idx ASC 
					";
	$rst4 = $dbConn->query($qry4);
	$cnt1 = 0;
	//echo $qry4;
	while($row4 = $rst4->fetch_assoc())
	{
		    $cnt1= $cnt1+1;
			$mnm4[] = $row4['menu_name'];

			$qry5 = "SELECT *
					FROM menu_info
					WHERE division != 'm' && menu_name='{$row4['menu_name']}'
					ORDER BY division, parent_idx, sub_idx ASC
					";
			//echo $qry5."<br>";
			$rst5 = $dbConn->query($qry5);
			$row5 = $rst5->fetch_assoc();
			$mdiv4[] = $row4['division'];
			$mpidx4[] = $row5['parent_idx'];
			$mseq4[] = $row5['seq_no'];
			$mpos4[] = $row4['pos'];
			if ($row4['pos'] == '0') {

			   $stylec4[] = "style='color:red'";
			} else {

			   $stylec4[] ="";
			}
	}

	$qry6 = "select * from menu_info where division='m'";
	$rst6 = $dbConn->query($qry6);

	// url
	
	$menu_num = array('기초관리' => '1',
					'상품관리' => '2',
					'예약관리' => '3',
					'행사관리' => '4',
					'MIS' => '5',
					'정산관리' => '6',
					'인사관리' => '7',
					'게시판관리' => '8',
					'컨텐츠관리' => '9',
					'고객관리' => '10');
	while($row6 = $rst6->fetch_assoc()) {
		$mName = $row6['menu_name'];
		$m_url[$menu_num[$mName]] = $row6['menu_link'];
	}


?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">인사관리</a>
					</li>
					<li>
						가이드관리
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="base_guide" id="base_guide" method="post" onSubmit="return chk(this)">
			           	<input type=hidden name=mode value="save">
					   <input type=hidden name=seq_no value="<?= $id ?>">
					   <input type=hidden name=old_pic value="<?= $v_info['userfile1'] ?>">
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							   
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>가이드성명(한글)</td>
										<td >&nbsp;<input type=text name=kor_name class="inpubase md" value="<?= $v_info['kor_name'] ?>"></td>
										<td width=15% align=center>가이드성명(영문)</td>
										<td >&nbsp;<input type=text name=eng_name class="inpubase md" value="<?= $v_info['eng_name'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>가이드 ID</td>
										<td width=35%>&nbsp;<input type=text name=userid class="inpubase md" value="<?= $v_info['userid'] ?>"></td>
										<td width=15% align=center>가이드 PW</td>
										<td width=35%>&nbsp;<input type=text name=passwd  class="inpubase md" value="<?= $v_info['passwd'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>전화</td>
										<td width=35%>&nbsp;<input type=text name=company_phone class="inpubase md" value="<?= $v_info['company_phone'] ?>"></td>
										<td width=15% align=center>이메일</td>
										<td width=35%>&nbsp;<input type=text name=company_email  class="inpubase lg" value="<?= $v_info['company_email'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>커미션</td>
										<td width=35%>&nbsp;<input type=text name=guide_rate class="inpubase sm1" value="<?= $v_info['guide_rate'] ?>"> %</td>
										<td width=15% align=center>생년월일</td>
										<td width=35%>&nbsp;<input type=text name=birthday id=stday  class="inpubase sm1" autocomplete="off" value="<?= $v_info['birthday'] ?>"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>현재사진</td>
										<td width=35%>&nbsp;<?php if($v_info['userfile1']): ?><img src="<?= UPLOAD_URL ?>thum_<?= $v_info['userfile1'] ?>" ><?php endif; ?> &nbsp;<input type=checkbox name=photo_del1 value="1"> Delete Image file</td>
										<td width=15% align=center>사진</td>
										<td width=35%>&nbsp;<input type=file name=userfile1 class="inpubase md"></td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% align=center>Status</td>
										<td width=35% >&nbsp;<select name=guide_status class="inpubase md">
										<option value="GOOD" <?php if($v_info['guide_status'] == "GOOD") echo "selected"; ?>>근무가능
										<option value="DISABLE1" <?php if($v_info['guide_status'] == "DISABLE1") echo "selected"; ?>>근무불가(휴가)
										<option value="DISABLE2" <?php if($v_info['guide_status'] == "DISABLE2") echo "selected"; ?>>근무불가(병가)
										<option value="DISABLE3" <?php if($v_info['guide_status'] == "DISABLE3") echo "selected"; ?>>근무불가(개인사정)
										<option value="DISABLE4" <?php if($v_info['guide_status'] == "DISABLE4") echo "selected"; ?>>근무불가(징계)
										<option value="DISABLE5" <?php if($v_info['guide_status'] == "DISABLE5") echo "selected"; ?>>퇴사
										</select></td>
										<td width=15% align=center>카카오톡 ID</td>
										<td width=35%>&nbsp;<input type=text name=kakaoid  class="inpubase lg" value="<?= $v_info['kakao'] ?>"></td>
									</tr>
									<tr>
										<td width=15% align=center>지사구분</td>
										<td width=25% align=left bgcolor=#FFFFFF>
											&nbsp;<input type="radio" name="dept1" value = "W" <?php if($v_info['dept_prior'] == "W") echo "checked"; ?>> 서부지사 &nbsp;&nbsp;
										  <input type="radio" name="dept1" value = "E" <?php if($v_info['dept_prior'] == "E") echo "checked"; ?>> 동부본사
										</td>
									</tr>
							</tbody>
						</table>
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							       <tr>
										<td bgcolor=#F9F9F9 height=25 align='center'><span class="label label-default">접근권한</span></td>
										<td bgcolor=#F9F9F9 height=25 align='center'><span class="label label-default">허용된 대메뉴</span></td>
										<td bgcolor=#F9F9F9 height=25>&nbsp;<span class="label label-default">기본메뉴접근 URL이 다르면 여기에서 수정해주세요</span></td>
									</tr>
									<tr  height=28>
										<td width=15% align=center>허용 메뉴</td>
										<td width=25% align=left bgcolor=#FFFFFF>
											<table cellspacing=0 cellpadding=0>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level1 value="1" <?php if (preg_match("/^1/", $row1['access_level'])) echo "checked"; ?>> 기초관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level2 value="2" <?php if (preg_match("/2/", $row1['access_level'])) echo "checked"; ?>> 상품관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level3 value="3" <?php if (preg_match("/3/", $row1['access_level'])) echo "checked"; ?>> 예약관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level4 value="4" <?php if (preg_match("/4/", $row1['access_level'])) echo "checked"; ?>> 행사관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level5 value="5" <?php if (preg_match("/5/", $row1['access_level'])) echo "checked"; ?>> MIS<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level6 value="6" <?php if (preg_match("/6/", $row1['access_level'])) echo "checked"; ?>> 정산관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level7 value="7" <?php if (preg_match("/7/", $row1['access_level'])) echo "checked"; ?>> 인사관리<br></td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level8 value="8" <?php if (preg_match("/8/", $row1['access_level'])) echo "checked"; ?>> 게시판관리</td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level9 value="9" <?php if (preg_match("/9/", $row1['access_level'])) echo "checked"; ?>> 컨텐츠관리</td></tr>
												<tr height=22><td>&nbsp;<input type=checkbox name=access_level10 value="10" <?php if (preg_match("/10/", $row1['access_level'])) echo "checked"; ?>> 고객관리</td></tr>
											</table>
										</td>
										<td width=60% align=left bgcolor=#FFFFFF colspan=2>
											<table cellspacing=0 cellpadding=0>
											   
												<tr height=20><td>&nbsp;<input type=hidden name=dCode[] value="1"><input type=hidden name=MCode[] value="기초관리"><input type=text name=urlA[]  value="<?= preg_match('/^1/', $row1['access_level']) ? $url[1] : $m_url[1] ?>" class="inpubase lg"><br></td></tr>
												<tr height=20><td>&nbsp;<input type=hidden name=dCode[] value="2"><input type=hidden name=MCode[] value="상품관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/2/', $row1['access_level']) ? $url[2] : $m_url[2] ?>" class="inpubase lg"><br></td></tr>
												<tr height=20><td>&nbsp;<input type=hidden name=dCode[] value="3"><input type=hidden name=MCode[] value="예약관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/3/', $row1['access_level']) ? $url[3] : $m_url[3] ?>" class="inpubase lg"><br></td></tr>
												<tr height=20><td>&nbsp;<input type=hidden name=dCode[] value="4"><input type=hidden name=MCode[] value="행사관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/4/', $row1['access_level']) ? $url[4] : $m_url[4] ?>" class="inpubase lg"><br></td></tr>

												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="5"><input type=hidden name=MCode[] value="MIS"><input type=text name=urlA[] size=50 value="<?= preg_match('/5/', $row1['access_level']) ? $url[5] : $m_url[5] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="6"><input type=hidden name=MCode[] value="정산관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/6/', $row1['access_level']) ? $url[6] : $m_url[6] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="7"><input type=hidden name=MCode[] value="인사관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/7/', $row1['access_level']) ? $url[7] : $m_url[7] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="8"><input type=hidden name=MCode[] value="게시판관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/8/', $row1['access_level']) ? $url[8] : $m_url[8] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="9"><input type=hidden name=MCode[] value="컨텐츠관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/9/', $row1['access_level']) ? $url[9] : $m_url[9] ?>" class="inpubase lg"><br></td></tr>
												<tr height=22><td>&nbsp;<input type=hidden name=dCode[] value="10"><input type=hidden name=MCode[] value="고객관리"><input type=text name=urlA[] size=50 value="<?= preg_match('/10/', $row1['access_level']) ? $url[10] : $m_url[10] ?>" class="inpubase lg"><br></td></tr>
												
											</table>
										</td>
									</tr>
									
									<tr bgcolor=#b2dcca height=28>
										<td width=15% align=center>상세 메뉴</td>
										<td width=25% align=left bgcolor=#FFFFFF>
										    <p><span class="label label-default">메뉴 고르기</span></p>
											<select name=menu1[] size='20' style='width:300px' multiple="multiple" ondblclick="move(this.form.elements['menu1[]'],this.form.elements['menu2[]'])">
											<?php for ($s=1 ; $s <= $cnt; $s++) { ?>
											<option <?=$stylec[$s-1]?> value=<?=$mseq[$s-1]?>><font color=red>[<?=$mdivision[$s-1]?>-<?=$mpidx1[$s-1]?>-<?=$mpos[$s-1]?>] <?= $mnm[$s-1] ?></font> </option>
											<?php } ?>
											</select>
									 </td>
										<td width=65% align=left bgcolor=#FFFFFF>
											<table cellspacing=0 cellpadding=0>
												<tr>
													<td style="vertical-align:middle;">&nbsp;<input type=button value="&rArr;" onclick="move(this.form.elements['menu1[]'],this.form.elements['menu2[]'])"></td>
													<td rowspan=2>
													     <p><span class="label label-default">허용된 메뉴</span></p>
														<select name=menu2[]  size='20' style='width:300px' multiple="multiple" ondblclick="move(this.form.elements['menu2[]'],this.form.elements['menu1[]'])">
														<?php for ($s=1 ; $s <= $cnt1; $s++) { ?>
														<option <?=$stylec4[$s-1]?> value=<?=$mseq4[$s-1]?> >[<?=$mdiv4[$s-1]?>-<?=$mpidx4[$s-1]?>-<?=$mpos4[$s-1]?>] <?= $mnm4[$s-1] ?>  </option>
														<?php } ?>
														</select>
													</td>
												</tr>
												<tr>
													<td style="vertical-align:middle;">&nbsp;<input type=button value="&lArr;" onclick="move(this.form.elements['menu2[]'],this.form.elements['menu1[]'])">&nbsp;&nbsp;</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr>
										<td colspan=3 height=35 bgcolor=#FFFFFF align=center><input type=submit value="전체 정보 저장" class="btn btn-success btn-sm"></td>
									</tr>
									<tr>
										<td width=15% align=center>정산활성권한</td>
										<td colspan=3 align=left bgcolor=#FFFFFF>
											&nbsp;<input type="radio" name="dept1" value = "A" <?php if($v_info['dept_prior'] == "A") echo "checked"; ?>> 활성 &nbsp;&nbsp;
										  <input type="radio" name="dept1" value = "J" <?php if($v_info['dept_prior'] == "J") echo "checked"; ?>> 비활성
										</td>
									</tr>
							</tbody>
						</table>
					 </form>
					  
				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
   

    </body>
	<script>
	         $(document).ready(function() {
		   
					$('#stday').datepicker({
						format: "yyyy-mm-dd",
						autoclose: true
					});
	        });

			$("#stday").inputmask("9999-99-99",{placeholder:"____-__-__"});

			function move(from, to) {
				for (var i = 0; i < from.options.length; i++) {
					if (from.options[i].selected) {
						to.options[to.options.length] = new Option(from.options[i].text, from.options[i].value);
						from.options[i] = null;
						i--;
					}
				}
			}

              function chk(tf){

					  if(!tf.kor_name.value)
					  {
							alert('가이드성명(한글)을 넣으세요!');
							tf.kor_name.focus();
							return false;
					  }
					  if(!tf.eng_name.value)
					  {
							alert('가이드(영문)을 넣으세요!');
							tf.eng_name.focus();
							return false;
					  }
					  if(!tf.userid.value)
					  {
							alert('가이드아이디를 넣으세요!');
							tf.userid.focus();
							return false;
					  }
					  // 허용된 메뉴 전체 선택 후 전송
					  var menu2 = tf.elements['menu2[]'];
					  if (menu2) {
						  for (var i = 0; i < menu2.options.length; i++) {
							  menu2.options[i].selected = true;
						  }
					  }
					  return true;
				}

	</script>
</html>

      
      