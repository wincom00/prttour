
<?php
    include "include/header.php";
	//include "include/inc_base.php";
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
	$v_info = getinfo_dbHotel_bycode($pcode);
	
	$lvcode2 = substr($v_info['p_typem'],3,2);
    if ($mode == "save") {
			 $qry1 = "delete  from product_hotel where h_code='$pcode'";
			 $rst1 = mysql_query($qry1,$dbConn);
			 $file_name["huserfile"] = "";
			 if ($_FILES["huserfile"]["tmp_name"] <> "")			$file_name["huserfile"] = file_save($_FILES["huserfile"], "./upload/");
			 if ($pcode == "") {
				$h_num = getHnumber();
				$hcode = "PHOT".$h_num['numChar'];
			 }  else {
				$h_num['num'] = $v_info['num'];
			 }
			 $qry1 = " insert into product_hotel 
														(
														num,
														m_rate, 
														u_type, 
														h_type, 
														h_typea,
														p_typem, 
														p_types, 
														h_stype, 
														h_code, 
														h_name, 
														h_grade, 
														hd_price, 
														h_addr, 
														h_room, 
														h_userfile, 
														h_desc, 
														wdate
														)
														values
														(
														'{$h_num['num']}', 
														'$currency', 
														'$utype', 
														'$htype',
														'$ahotel', 
														'$area1', 
														'$area2', 
														'', 
														'$hcode', 
														'$hname', 
														'$hgrade', 
														'$hdprice', 
														'$haddr', 
														'$hroom', 
														'" . mysql_real_escape_string($file_name["huserfile"]) . "', 
														'$hdesc', 
														now()
														)";
			  $rst1 = mysql_query($qry1, $dbConn);

			  if($rst1)
			  {
				     Misc::jvAlert("저장했습니다.","");
				     $goUrl_1 = "hotel_regi.php?division=$division&pdx=$pdx&sub=$sub";
					 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
					 exit;
			  }
			

	} 
	
	

?>
<script src="ckeditor/ckeditor.js"></script>
<div id="contentwrapper" class="HotelDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/admin"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">상품관리</a>
					</li>
					<li>
						<a href="#">상품등록</a>
					</li>
					<li>
						호텔등록
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>&pcode=<?=$pcode?>" name="frmproduct" id="frmproduct" method="post" Enctype="multipart/form-data" onSubmit="return chksave()">
			            <input type=hidden name=mode value="save">
						<input type=hidden name=pcode value="<?= $pcode ?>">
						<input type="hidden" name="currency" value="USD">
						
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
								    <tr>
										<td colspan=4 height=35 bgcolor=#FFFFFF class="titletd" style="vertical-align: middle;"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
									</tr> 
									<!--<tr  bgcolor=#f9f9f9 height=28>
									    <td width=15% class="titletd" style="vertical-align: middle;">기준통화</td>
									    <td  colspan="3" bgcolor=#FFFFFF>
												<label class="radio-inline">
													<input type="radio" name="currency" id="currencyCAD" value="CAD" <?php if ($v_info['m_rate'] == "CAD") echo "checked"; ?>> CAD
												</label>
												<label class="radio-inline">
													<input type="radio" name="currency" id="currencyUSD" value="USD" <?php if ($v_info['m_rate'] == "USD") echo "checked"; ?>> USD
												</label>
										</td>
										
									</tr>-->
									<tr  bgcolor=#f9f9f9 height=28>
									    <td width=15% class="titletd" style="vertical-align: middle;">사용구분</td>
									    <td  colspan="3" bgcolor=#FFFFFF>
												<label class="radio-inline">
													<input type="radio" name="utype" id="utype1" value="1" <?php if ($v_info['u_type'] == "1") echo "checked"; ?>> 둘다사용
												</label>
												<label class="radio-inline">
													<input type="radio" name="utype" id="utype2" value="2" <?php if ($v_info['u_type'] == "2") echo "checked"; ?>> 투어만사용
												</label>
												<label class="radio-inline">
													<input type="radio" name="utype" id="utype2" value="2" <?php if ($v_info['u_type'] == "3") echo "checked"; ?>> FIT전용
												</label>
										</td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
									    <td width=15% class="titletd" style="vertical-align: middle;">호텔지역분류</td>
									    <td colspan="3" class="form-inline" bgcolor=#FFFFFF>
											<select class="form-control fst1" name="area1" id="area1">
												<option value="">분류선택1
												<?=printBaseCode_first('T01',$v_info['p_typem'])?>
											</select>
											<select class="form-control fst2" name="area2" id="area2">
												<option value="">분류선택2</option>
												<?=printBaseCode_second('T01',$lvcode2,$v_info['p_types'])?>
											</select>
											
										</td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
									    <td width=15% class="titletd" style="vertical-align: middle;">호텔배정분류</td>
									    <td colspan="3" class="form-inline" bgcolor=#FFFFFF>
											<select class="form-control " name="ahotel" id="ahotel">
												<option selected>- 호텔배정지역선택 -</option>
												<?php echo printBaseCode_hotel($v_info['h_typea']); ?>
												
											</select>
											
											
										</td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
									    <td width=15% class="titletd" style="vertical-align: middle;">호텔등급</td>
									    <td colspan="3" class="form-inline" bgcolor=#FFFFFF>
											<select class="form-control " name="hgrade" id="hgrade">
												<option value="">등급선택</option>
												<option value="5" <?php if ($v_info['h_grade'] == "5") echo "selected"; ?>>5성급 호텔</option>
												<option value="4" <?php if ($v_info['h_grade'] == "4") echo "selected"; ?>>4성급 호텔</option>
												<option value="3" <?php if ($v_info['h_grade'] == "3") echo "selected"; ?>>3성급 호텔</option>
												<option value="2" <?php if ($v_info['h_grade'] == "2") echo "selected"; ?>>2성급 호텔</option>
												<option value="1" <?php if ($v_info['h_grade'] == "1") echo "selected"; ?>>1성급 호텔</option>
												
												
											</select>
											
											
										</td>
										
									</tr>
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd">호텔코드</td>
										<td colspan="3" bgcolor=#FFFFFF><input type=text name=hcode  class="inpubase md"  placeholder="자동생성 및 수정가능"  value="<?= $v_info['h_code'] ?>"> </td>
										
									</tr>
									
									
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">호텔명</td>
										<td colspan="3" bgcolor=#FFFFFF><input type=text name=hname  class="inpubase lg" value="<?= $v_info['h_name'] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">호텔주소</td>
										<td colspan="3" bgcolor=#FFFFFF><input type=text name=haddr  class="inpubase llg" value="<?= $v_info['h_addr'] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">방갯수</td>
										<td  colspan="3" bgcolor=#FFFFFF><input type=text name=hroom  class="inpubase sm1" value="<?= $v_info['h_room'] ?>"></td>
										
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">호텔표시용가격</td>
										<td  colspan="3" bgcolor=#FFFFFF><input type=text name=hdprice  class="inpubase md" value="<?= $v_info['hd_price'] ?>"></td>
										
									</tr>
									
									<tr>
										<td class="active text-center formHeader">현재호텔이미지</td>
										<td >
											
												 <img width='140px' height='140px' alt="140x140" data-src="<?= UPLOAD_URL ?><?= $v_info['h_userfile'] ?>" class="img-thumbnail js-placeholderImg" src="<?= UPLOAD_URL ?><?= $v_info['h_userfile'] ?>" data-holder-rendered="true">
										
										</td>
										<td  class="active text-center formHeader">이미지업로드</td>
										<td >
											<div class="col-sm-6">
												<div class="form-group removeBottomMargin">
													<label class="sr-only" for="huserfile">현재호텔이미지 </label>
													<input type="file" class="form-control" id="huserfile" name="huserfile" placeholder="현재호텔이미지">
												</div>
											</div>
											
										</td>
									</tr>
									<tr bgcolor=#f9f9f9 height=28>
										<td width=15% class="titletd" style="vertical-align: middle;">호텔한줄설명</td>
										<td colspan="3" bgcolor=#FFFFFF><input type=text name=hdesc  class="inpubase" value="<?= $v_info['h_desc'] ?>"></td>
										
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
    <script>
	   
         $(document).ready(function() {
				//* bootstrap timepicker
		        pt1.inithotelDetailForm();
	     });
		
		function chksave() {
				  
				  
                  if (($('input:radio[name=utype]').is(':checked'))== false)
                  {
						alert("사용구분을 입력하세요!");
						$('input:radio[name=utype]').focus();
						return false;
                  }
                  if ($("#area1").val() == "") {
						alert("호텔분류 1을 입력하세요!");
						$("#area1").focus();
						return false;
				  }
				  if ($("#area2").val() == "") {
						alert("호텔분류 2를 입력하세요!");
						$("#area2").focus();
						return false;
				  }
				  if ($("#hname").val() == "") {
						alert("호텔명을 입력하세요!");
						$("#hname").focus();
						return false;
				  }
				  
				  
				  return true;

			}
		
		
	</script>

    </body>
</html>

      
      