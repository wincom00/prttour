
<?php
    include "include/header.php";
	
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
	if($mode == "save")
	{
		$file_name["image"] = "";
		if ($_FILES["image"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["image"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $img_del == "1") $image_qry = " image = '" . mysql_real_escape_string($file_name["image"]) . "', ";

		$file_name["image1"] = "";
		if ($_FILES["image1"]["tmp_name"] <> "") $file_name["image1"] = file_save($_FILES["image1"], "upload/");

		$image_qry1 = "";
		if ($file_name["image1"] <> "" || $img_del1 == "1") $image_qry1 = " imgsub_m = '" . mysql_real_escape_string($file_name["image1"]) . "', ";

		$file_name["image2"] = "";
		if ($_FILES["image2"]["tmp_name"] <> "") $file_name["image2"] = file_save($_FILES["image2"], "upload/");

		$image_qry2 = "";
		if ($file_name["image2"] <> "" || $img_del2 == "1") $image_qry2 = " imgsub_d = '" . mysql_real_escape_string($file_name["image2"]) . "', ";


		$qry1 = "UPDATE code_base SET lvcode1 = '$lvcode1_value',
									lvcode2 = '$lvcode2_value',
									lvcode3 = '$lvcode3_value',
									lvcode4 = '$lvcode4_value',
									lvcode5 = '$lvcode5_value',
									comment = '" . mysql_real_escape_string($comment) . "',
									$image_qry
									$image_qry1
									$image_qry2
									desc_comm = '$desc',
									active = '$active',
									modified = now()
				WHERE lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 = '$lvcode3' && lvcode4 = '$lvcode4' ";
		$rst1 = mysql_query($qry1);
    	if($rst1)
		{
			Misc::jvAlert("저장완료!","location.replace('base_code.php?division=1&pdx=1&sub=1&lvcode1=$lvcode1&lvcode2=$lvcode2&lvcode3=$lvcode3&lvcode4=$lvcode4')");
			exit;
		}
		else
		{
			Misc::jvAlert("에러!","history.go(-1)");
			exit;
		}

		

	}
	$qry1 = "SELECT * FROM code_base WHERE lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 = '$lvcode3' && lvcode4 = '$lvcode4'";
	$rst1 = mysql_query($qry1);
	$row1 = mysql_fetch_assoc($rst1);
?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/admin"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">기초관리</a>
					</li>
					<li>
						<a href="#">기초관리</a>
					</li>
					<li>
						기초코드등록
					</li>
				</ul>
			</div>	
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="base_code_edit.php?division=1&pdx=1&sub=1" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
							  <input type="hidden" name="mode" value="save">
							  <input type="hidden" name="lvcode1" id="lvcode1" value="<?= $lvcode1 ?>">
							  <input type="hidden" name="lvcode2" value="<?= $lvcode2 ?>">
							  <input type="hidden" name="lvcode3" value="<?= $lvcode3 ?>">
							  <input type="hidden" name="lvcode4" value="<?= $lvcode4 ?>">
							  <input type="hidden" name="lvcode5" value="<?= $lvcode5 ?>">
							   <table class="table table-striped table-advance table-hover">
									   <tbody>
										  <tr>
											<th width=5% align="center">분류</th>
											<th width=5% align="center">대분류</th>
											<th width=5% align="center">중분류</th>
											<th width=5% align="center">세분류</th>
											<th width='*' align="center">코드정의</th>
											<?php if ($lvcode1 == "T01") { ?>
											<th width=9% align="center">이미지</th>
											<th width=9% align="center">이미지P</th>
											<th width=9% align="center">이미지M</th>
											<?php } ?>
											<?php if (($row1['lvcode1'] == "S04") || ($row1['lvcode1'] == "G01"))  { ?>
											<th width=9% align="center">이미지</th>
											
											<?php } ?>
											<th width=5% align="center">사용유무</th>
											<th width=5 align="center"><i class="glyphicon glyphicon-cog"></i>Action</th>
											
										  </tr>
										  <tr>
											<td><input type="text" id="lvcode1_value" name="lvcode1_value" class="form-control" value="<?= $lvcode1 ?>"/></td>
											<td><input type="text" id="lvcode2_value" name="lvcode2_value" class="form-control" value="<?= $lvcode2 ?>"/></td>
											<td><input type="text" id="lvcode3_value" name="lvcode3_value" class="form-control" value="<?= $lvcode3 ?>"/></td>
											<td><input type="text" id="lvcode4_value" name="lvcode4_value" class="form-control" value="<?= $lvcode4 ?>"/></td>
											<td><input type="text"  id="comment" name="comment" style='width : 100%;' class="form-control" placeholder="코드정의" value="<?= $row1['comment'] ?>"/></td>
											<?php if ($lvcode1 == "T01") { ?>
											<td>&nbsp;<?php if($row1['image']): ?><img width='30%' src="<?= UPLOAD_URL ?><?= $row1['image'] ?>" >&nbsp;<input type="checkbox" id="img_del" name="img_del" value="1"> 삭제이미지<br><?php else: ?>&nbsp이미지없음<?php endif; ?>&nbsp;<input type="file" id="image" name="image" size="30" class="form_box"></td>
											<td>&nbsp;<?php if($row1['imgsub_d']): ?><img width='30%' src="<?= UPLOAD_URL ?><?= $row1['imgsub_d'] ?>" >&nbsp;<input type="checkbox" id="img_del2" name="img_del2" value="1"> 삭제이미지<br><?php else: ?>&nbsp이미지없음P<?php endif; ?>&nbsp;<input type="file" id="image2" name="image2" size="30" class="form_box"></td>
											<td>&nbsp;<?php if($row1['imgsub_m']): ?><img width='30%' src="<?= UPLOAD_URL ?><?= $row1['imgsub_m'] ?>" >&nbsp;<input type="checkbox" id="img_del1" name="img_del1" value="1"> 삭제이미지<br><?php else: ?>&nbsp이미지없음M<?php endif; ?>&nbsp;<input type="file" id="image1" name="image1" size="30" class="form_box"></td>
											
											<?php } ?>
											<?php if (($row1['lvcode1'] == "S04") || ($row1['lvcode1'] == "G01"))  { ?>
											<td>&nbsp;<?php if($row1['image']): ?><img width='30%' src="<?= UPLOAD_URL ?><?= $row1['image'] ?>" >&nbsp;<input type="checkbox" id="img_del" name="img_del" value="1"> 삭제이미지<br><?php else: ?>&nbsp이미지없음<?php endif; ?>&nbsp;<input type="file" id="image" name="image" size="30" class="form_box"></td>
											
											
											<?php } ?>

											<td>&nbsp;<input type="radio" name="active" value="yes" <?php if ($row1['active'] == "yes") echo "checked"; ?>>Active<br>&nbsp;<input type="radio" name="active" value="no" <?php if ($row1['active'] == "no") echo "checked"; ?>>Inactive</td>
											
										    <td>&nbsp;<input type="submit" value="저장" class='btn btn-primary btn-xs btnatt'></td>
											
											
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
</html>

      
      