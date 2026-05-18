
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

		if ($_FILES["image"]["tmp_name"] <> "") $file_name["image"] = file_save1($_FILES["image"], "upload/");
		
		$file_name["image1"] = "";

		if ($_FILES["image1"]["tmp_name"] <> "") $file_name["image1"] = file_save1($_FILES["image1"], "upload/");

        $file_name["image2"] = "";

		if ($_FILES["image2"]["tmp_name"] <> "") $file_name["image2"] = file_save1($_FILES["image2"], "upload/");

		$qry1 = "insert into code_base (lvcode1, lvcode2, lvcode3, lvcode4, comment, active, image,imgsub_d,imgsub_m, modified, created)
								values ('$lvcode1_value','$lvcode2_value','$lvcode3_value','$lvcode4_value','" . mysql_real_escape_string($comment) . "','yes', '".$file_name['image']."','".$file_name['image1']."','".$file_name['image2']."','',now())";
		$rst1 = mysql_query($qry1,$dbConn);


		if(!$rst1)
		{
			echo "error";
			exit;
		}
	}
	else if($mode == "del")
	{

		$qry2 = "delete from code_base where lvcode1 = '$lvcode1' && lvcode2 = '$lvcode2' && lvcode3 = '$lvcode3' && lvcode4 = '$lvcode4' ";
		$rst2 = mysql_query($qry2,$dbConn);

		if($rst2)
		{
			Misc::jvAlert("Completed!","location.replace('base_code.php?division=$division&pdx=$pdx&sub=$sub&lvcode1=$lvcode1')");
			exit;
		}
		else
		{
			Misc::jvAlert("Error!","history.go(-1)");
			exit;
		}

	}

	function printCode1($code1){
		
		global $dbConn;

		$qry1 = "select * from code_base where lvcode2 = '00' && lvcode3 = '00' && lvcode4 = '00'  order by lvcode1 asc";
		$rst1 = mysql_query($qry1,$dbConn);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			if($row1['lvcode1'] == $code1)
			{
				echo "<option value={$row1['lvcode1']} selected>{$row1['comment']} ({$row1['lvcode1']})";
			}
			else
			{
				echo "<option value={$row1['lvcode1']}>{$row1['comment']} ({$row1['lvcode1']})";
			}
			

		}

	}


	function printCode2($code1,$code2){
		
		global $dbConn;

		$qry1 = "select * from code_base where lvcode1 = '$code1' && lvcode2 <> '00' && lvcode3 = '00' && lvcode4 = '00' order by lvcode2 asc";
		$rst1 = mysql_query($qry1,$dbConn);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			if($row1['lvcode2'] == $code2)
			{
				echo "<option value={$row1['lvcode2']} selected>{$row1['comment']}";
			}
			else
			{
				echo "<option value={$row1['lvcode2']}>{$row1['comment']}";
			}
			

		}

	}

	function printCode3($code1,$code2,$code3){
		
		global $dbConn;

		$qry1 = "select * from code_base where lvcode1 = '$code1' && lvcode2 = '$code2' && lvcode3 <> '00' && lvcode4 = '00'  order by lvcode2 asc";
		$rst1 = mysql_query($qry1,$dbConn);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			if($row1['lvcode3'] == $code3)
			{
				echo "<option value={$row1['lvcode3']} selected>{$row1['comment']}";
			}
			else
			{
				echo "<option value={$row1['lvcode3']}>{$row1['comment']}";
			}
			

		}

	}

	function printCode4($code1,$code2,$code3,$code4){
		
		global $dbConn;

		$qry1 = "select * from code_base where lvcode1 = '$code1' && lvcode2 = '$code2' && lvcode3 = '$code3' && lvcode4 <> '00'  order by lvcode2 asc";
		$rst1 = mysql_query($qry1,$dbConn);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			if($row1['lvcode4'] == $code4)
			{
				echo "<option value={$row1['lvcode4']} selected>{$row1['comment']}";
			}
			else
			{
				echo "<option value={$row1['lvcode4']}>{$row1['comment']}";
			}
			

		}

	}

	function printCode5($code1,$code2,$code3,$code4,$code5){
		
		global $dbConn;

		$qry1 = "select * from code_base where lvcode1 = '$code1' && lvcode2 = '$code2' && lvcode3 = '$code3' && lvcode4 <> '00'  order by lvcode2 asc";
		$rst1 = mysql_query($qry1,$dbConn);

		while($row1 = mysql_fetch_assoc($rst1)){
			
			if($row1['lvcode5'] == $code5)
			{
				echo "<option value={$row1['lvcode5']} selected>{$row1['comment']}";
			}
			else
			{
				echo "<option value={$row1['lvcode5']}>{$row1['comment']}";
			}
			

		}

	}

	function printContentlist($code1,$code2,$code3,$code4){
		
		global $dbConn,$division,$pdx,$sub;

		if($code2)
		{
			$code2_qry = "&& lvcode2 = '$code2'";
		}

		if($code3)
		{
			$code3_qry = "&& lvcode3 = '$code3'";
		}

		if($code4)
		{
			$code4_qry = "&& lvcode4 = '$code4'";
		}

		

		$qry1 = "select * from code_base where lvcode1 = '$code1' $code2_qry $code3_qry $code4_qry order by lvcode1,lvcode2,lvcode3,lvcode4 asc";
		$rst1 = mysql_query($qry1,$dbConn);


		while($row1 = mysql_fetch_assoc($rst1)){
			if ($row1['active'] == 'yes') {
				$checked1 = 'Active';
				$checked2 = '';
			}
			if ($row1['active'] == 'no') {
				$checked1 = '';
				$checked2 = 'Inactive';
			}
			$icon = "";
			if ($row1['image'] <> "") $icon = "<img width='30%' src='".UPLOAD_URL."{$row1['image']}'>";

			$icon1 = "&nbsp;";
			if ($row1['imgsub_d'] <> "") $icon1 = "<img width='30%' src='".UPLOAD_URL."{$row1['imgsub_d']}'>";

			$icon2 = "&nbsp;";
			if ($row1['imgsub_m'] <> "") $icon2 = "<img width='30%' src='".UPLOAD_URL."{$row1['imgsub_m']}'>";

			echo "<tr >
			<td height=25>&nbsp;{$row1['lvcode1']}</td>
			<td>&nbsp;{$row1['lvcode2']}</td>
			<td>&nbsp;{$row1['lvcode3']}</td>
			<td>&nbsp;{$row1['lvcode4']}</td>

			<td>&nbsp;{$row1['comment']}<br /><font color=blue>{$row1['desc_comm']}</font></td>
			";
			if ($row1['lvcode1'] == "T01") {
				echo "<td>$icon</td>
				<td>$icon1</td>
				<td>$icon2</td>";
			}
			if (($row1['lvcode1'] == "S04") || ($row1['lvcode1'] == "G01")) {
				echo "<td>$icon</td>";
				
			}
			echo "
			<td>&nbsp;{$checked1}{$checked2}</td>
			<td align=left  {$checked2}><a class='btn btn-primary btn-xs' href=base_code_edit.php?division={$division}&pdx={$pdx}&sub={$sub}&mode=modify&lvcode1={$row1['lvcode1']}&lvcode2={$row1['lvcode2']}&lvcode3={$row1['lvcode3']}&lvcode4={$row1['lvcode4']}&lvcode5={$row1['lvcode5']}>수정</a> | <a class='btn btn-danger btn-xs' href=\"javascript:del('{$row1['lvcode1']}','{$row1['lvcode2']}','{$row1['lvcode3']}','{$row1['lvcode4']}','{$row1['lvcode5']}')\">삭제</a></td>
			</tr>";

		}
	}

	$rowm = getinfo_menufst($user_dbinfo['userid'],$division);
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
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
							  <input type="hidden" name="mode" value="save">
							  <input type="hidden" name="lvcode1" id="lvcode1" value="<?= $lvcode1 ?>">
							  <input type="hidden" name="lvcode2" value="<?= $lvcode2 ?>">
							  <input type="hidden" name="lvcode3" value="<?= $lvcode3 ?>">
							  <input type="hidden" name="lvcode4" value="<?= $lvcode4 ?>">
							  <table class="table table-striped table-advance table-hover">
									   <tbody>
										  <tr>
											<th width=9% align="center">분류</th>
											<th width=9% align="center">대분류</th>
											<th width=9% align="center">중분류</th>
											<th width=9% align="center">세분류</th>
											
											<?php
											if ($lvcode1 == "T01")                          $cw = "27%";
											elseif ($lvcode1 == "S04" || $lvcode1 == "G01") $cw = "43%";
											else                                            $cw = "51%";
											?>
											<th width="<?=$cw?>" align="center">코드정의</th>
											<?php if ($lvcode1 == "T01") { ?>
											<th width=8% align="center">이미지</th>
											<th width=8% align="center">이미지P</th>
											<th width=8% align="center">이미지M</th>
											<?php } ?>
											<?php if (($lvcode1 == "S04") ||($lvcode1 == "G01")) { ?>
											<th width=8% align="center">이미지</th>
											<?php } ?>
											<th width=5% align="center">사용유무</th>
											<th width=8% align="center"><i class="glyphicon glyphicon-cog"></i>Action</th>
											
										  </tr>
										  <tr>
											 <td>
											
											  	<select id="lvcode1" name="lvcode1" class="form-control" onChange="go_change(this.options[this.selectedIndex].value)">
													<option value="" selected>코드선택
							                             <?php printCode1($lvcode1); ?>
											    </select>
												
                                             </td>
											 <td> 
											     
												<?php if($lvcode1): ?>
												<select id="lvcode2" name="lvcode2" class="form-control" onChange="go_change2(this.options[this.selectedIndex].value,'<?=$lvcode1?>')">
													<option value="">코드선택
													<?php printCode2($lvcode1,$lvcode2); ?>
													
												</select>
												<?php endif; ?>
												
											</td>
											 <td>
											     
												 <?php if($lvcode1 && $lvcode2): ?>
													<select id="lvcode3" name="lvcode3" class="form-control" onChange="go_change3(this.options[this.selectedIndex].value,'<?=$lvcode1?>','<?=$lvcode2?>')">
														<option value="">코드선택
							                            <?php printCode3($lvcode1,$lvcode2,$lvcode3); ?>
													</select>
												<?php endif; ?>
												
											 </td>
											 <td>
											   
													<?php if($lvcode1 && $lvcode2 && $lvcode3): ?>
													<select id="lvcode4" name="lvcode4" class="form-control" onChange="go_change4(this.options[this.selectedIndex].value,'<?=$lvcode1?>','<?=$lvcode2?>','<?=$lvcode3?>')">>
														<option value="">코드선택
							                             <?php printCode4($lvcode1,$lvcode2,$lvcode3,$lvcode4); ?>
													</select>
												
											   <?php endif; ?>
												
											 </td>
											 <td>
											    
                                                  <?php if($lvcode1 && $lvcode2 && $lvcode3 && $lvcode4): ?>
													<!--<select id="code_etc" name="code_etc" class="form-control">
														<option value="">코드선택
							                            <?php printCode5($lvcode1,$lvcode2,$lvcode3,$lvcode4,$lvcode4); ?>
													</select>-->
                                                <?php endif; ?> 
												
											 </td>
											<td> </td>
											<td>&nbsp;</td>
											<td> </td>
											<?php printContentlist($lvcode1,$lvcode2,$lvcode3,$lvcode4); ?>
								       </tr>
								       <tr>
											<td><input type="text" id="lvcode1_value" name="lvcode1_value" class="form-control" value="<?= $lvcode1 ?>"/></td>
											<td><input type="text" id="lvcode2_value" name="lvcode2_value" class="form-control" value="<?= $lvcode2 ?>"/></td>
											<td><input type="text" id="lvcode3_value" name="lvcode3_value" class="form-control" value="<?= $lvcode3 ?>"/></td>
											<td><input type="text" id="lvcode4_value" name="lvcode4_value" class="form-control" value="<?= $lvcode4 ?>"/></td>
											<td><input type="text"  id="comment" name="comment" style='width : 100%;' class="form-control" placeholder="코드정의" /></td>
											<?php if ($lvcode1 == "T01") { ?>
											<td >&nbsp;<input type="file" name="image"></td>
											<td >&nbsp;<input type="file" name="image1"></td>
											<td >&nbsp;<input type="file" name="image2"></td>
											<?php } ?>
											<?php if(($lvcode1 == "S04") ||($lvcode1 == "G01")) { ?>
											<td >&nbsp;<input type="file" name="image"></td>
											
											<?php } ?>
											<td>&nbsp;<input type="radio" name="active" value="yes" checked>Active<br>&nbsp;<input type="radio" name="active" value="no">Inactive</td>
											<td>&nbsp;&nbsp;<button  class="btn btn-primary btn-sm btnatt" OnClick="javascript:chk()" class='btn btn-primary btn-xs'>저장</button></td>
										
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
        function chk(){
				
		//	alert(tf.lvcode1_value.value);					
		  if(!$("#lvcode1_value").val())
		  {
				alert('No Value !!');
				$("#lvcode1_value").focus();
				return;
		  }

		  //$( "#base_code" ).submit();
		}

		function go_change(str){
				
				location.replace('base_code.php?division=1&pdx=<?=$pdx?>&sub=<?=$sub?>&lvcode1=' + str);

		}
		function go_change2(str,code1){
			
				location.replace('base_code.php?division=1&pdx=<?=$pdx?>&sub=<?=$sub?>&lvcode1=' + code1 + '&lvcode2=' + str);

		}
		function go_change3(str,code1,code2){
			
				location.replace('base_code.php?division=1&pdx=<?=$pdx?>&sub=<?=$sub?>&lvcode1=' + code1 + '&lvcode2=' + code2 + '&lvcode3=' + str);

		}
		function go_change4(str,code1,code2,code3){
			
				location.replace('base_code.php?division=1&pdx=<?=$pdx?>&sub=<?=$sub?>&lvcode1=' + code1 + '&lvcode2=' + code2 + '&lvcode3=' + code3 + '&lvcode4=' + str);

		}
		function go_change5(str,code1,code2,code3,code4){
			
				location.replace('base_code.php?division=1&pdx=<?=$pdx?>&sub=<?=$sub?>&lvcode1=' + code1 + '&lvcode2=' + code2 + '&lvcode3=' + code3 + '&lvcode4=' + code4 + '&lvcode5 =' + str);

		}
		function del(lvcode1,lvcode2,lvcode3,lvcode4,lvcode5){
			
			if(confirm("Delete?") == true)
			{
				location.replace('<?= $PHP_SELF ?>?division=1&mode=del&pdx=<?=$pdx?>&sub=<?=$sub?>&lvcode1=' + lvcode1 + '&lvcode2=' + lvcode2 + '&lvcode3=' + lvcode3 + '&lvcode4=' + lvcode4 + '&lvcode5=' + lvcode5);
			}
			else return;
		}

	</script>


    </body>
</html>

      
      