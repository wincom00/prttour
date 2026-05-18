
<?php
    include "include/header.php";
    //include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     if (!hasMenuAccess($division, $pdx, $sub)) {
		
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		exit;
    }

   //echo $divi;
   //exit;
    if (!$divi) {
		$diviqry = " && divi='head'";
    } else  {
		$diviqry = " && divi='$divi'";
    }
	if ($mode == "save") {
		$diviqry = " && divi = '$divi'";
		$file_name["image"] = "";
		if ($_FILES["userfile1"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile1"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del1 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "',";


		$qry0 = "update banner_page set $image_qry  alink='$alink1',pos='$apos1' where 1=1 $diviqry && area = 'b1'";
		$rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile2"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile2"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del2 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink2',pos='$apos2' where 1=1 $diviqry && area = 'b2'";
		$rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile3"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile3"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del3 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink3',pos='$apos3' where 1=1 $diviqry && area = 'b3'";
		$rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile4"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile4"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del4 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink4',pos='$apos4' where 1=1 $diviqry && area = 'b4'";
		$rst0 = mysql_query($qry0,$dbConn);

		$file_name["image"] = "";
		if ($_FILES["userfile5"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile5"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del5 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink5',pos='$apos5' where 1=1 $diviqry && area = 'b5'";
	    $rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile6"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile6"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del6 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink6',pos='$apos6' where 1=1 $diviqry && area = 'b6'";
	    $rst0 = mysql_query($qry0,$dbConn);

		$file_name["image"] = "";
		if ($_FILES["userfile7"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile7"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del7 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink7',pos='$apos7' where 1=1 $diviqry && area = 'b7'";
	    $rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile8"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile8"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del8 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink8',pos='$apos8' where 1=1 $diviqry && area = 'b8'";
	    $rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile9"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile9"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del9 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink9',pos='$apos9' where 1=1 $diviqry && area = 'b9'";
	    $rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile10"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile10"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del10 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink10',pos='$apos10' where 1=1 $diviqry && area = 'b10'";
	    $rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile110"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile110"], "upload/");



		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del101 == "1") $image_qry = " img1 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink111',pos='$apos11' where 1=1 $diviqry && area = 'b11'";
	    $rst0 = mysql_query($qry0,$dbConn);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		$file_name["image"] = "";
		if ($_FILES["userfile11"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile11"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del11 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "',";


		$qry0 = "update banner_page set $image_qry  alink='$alink1',pos='$apos1' where 1=1 $diviqry && area = 'b1'";
		$rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile22"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile22"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del22 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink2',pos='$apos2' where 1=1 $diviqry && area = 'b2'";
		$rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile33"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile33"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del33 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink3' ,pos='$apos3' where 1=1 $diviqry && area = 'b3'";
		$rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile44"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile44"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del44 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink4' ,pos='$apos4' where 1=1 $diviqry && area = 'b4'";
		$rst0 = mysql_query($qry0,$dbConn);

		$file_name["image"] = "";
		if ($_FILES["userfile55"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile55"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del55 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink5' ,pos='$apos5' where 1=1 $diviqry && area = 'b5'";
	    $rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile66"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile66"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del66 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink6',pos='$apos6' where 1=1 $diviqry && area = 'b6'";
	    $rst0 = mysql_query($qry0,$dbConn);

		$file_name["image"] = "";
		if ($_FILES["userfile77"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile77"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del77 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink7' ,pos='$apos7' where 1=1 $diviqry && area = 'b7'";
	    $rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile88"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile88"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del88 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink8' ,pos='$apos8' where 1=1 $diviqry && area = 'b8'";
	    $rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile99"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile99"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del99 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink9' ,pos='$apos9' where 1=1 $diviqry && area = 'b9'";
	    $rst0 = mysql_query($qry0,$dbConn);


		$file_name["image"] = "";
		if ($_FILES["userfile100"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile100"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del100 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink10',pos='$apos10' where 1=1 $diviqry && area = 'b10'";
	    $rst0 = mysql_query($qry0,$dbConn);

        $file_name["image"] = "";
		if ($_FILES["userfile1110"]["tmp_name"] <> "") $file_name["image"] = file_save($_FILES["userfile1110"], "upload/");

		$image_qry = "";
		if ($file_name["image"] <> "" || $photo_del1101 == "1") $image_qry = " img2 = '" . mysql_real_escape_string($file_name["image"]) . "' ,";
		$qry0 = "update banner_page set $image_qry  alink='$alink111',pos='$apos11' where 1=1 $diviqry && area = 'b11'";
	    $rst0 = mysql_query($qry0,$dbConn);





		
		echo "<meta http-equiv='refresh' content='0; url=./m_banner.php?division=9&pdx=2&sub=20'>";
		exit;
	}

/*		
	$qry1 = "select * from banner_page where 1=1 $diviqry && area = 'b1'";
	$rst1 = mysql_query($qry1, $dbConn);
	$row1 = mysql_fetch_assoc($rst1);
	//echo $qry1;
	$qry2 = "select * from banner_page where 1=1 $diviqry && area = 'b2'";
	$rst2 = mysql_query($qry2);
	$row2 = mysql_fetch_assoc($rst2);

	$qry3 = "select * from banner_page where 1=1 $diviqry && area= 'b3'";
	$rst3 = mysql_query($qry3);
	$row3 = mysql_fetch_assoc($rst3);

	$qry4 = "select * from banner_page where 1=1 $diviqry && area = 'b4'";
	$rst4 = mysql_query($qry4);
	$row4 = mysql_fetch_assoc($rst4);

	$qry5 = "select * from banner_page where 1=1 $diviqry && area = 'b5'";
	$rst5 = mysql_query($qry5);
	$row5 = mysql_fetch_assoc($rst5);

	$qry6 = "select * from banner_page where 1=1 $diviqry && area = 'b6'";
	$rst6 = mysql_query($qry6);
	$row6 = mysql_fetch_assoc($rst6);

	$qry7 = "select * from banner_page where 1=1 $diviqry && area = 'b7'";
	$rst7 = mysql_query($qry7);
	$row7 = mysql_fetch_assoc($rst7);

	$qry8 = "select * from banner_page where 1=1 $diviqry && area = 'b8'";
	$rst8 = mysql_query($qry8);
	$row8 = mysql_fetch_assoc($rst8);

    $qry9 = "select * from banner_page where 1=1 $diviqry && area = 'b9'";
	$rst9 = mysql_query($qry9);
	$row9 = mysql_fetch_assoc($rst9);

	$qry10 = "select * from banner_page where 1=1 $diviqry && area = 'b10'";
	$rst10 = mysql_query($qry10);
	$row10 = mysql_fetch_assoc($rst10);
    
	$qry11 = "select * from banner_page where 1=1 $diviqry && area = 'b11'";
	$rst11 = mysql_query($qry11);
	$row11 = mysql_fetch_assoc($rst11);

*/

	

?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">홈페이지관련설정</a>
					</li>
					<li>
						메인배너
					</li>
				</ul>
			</div>
			
		<div class="row">
			    <ul class="nav nav-tabs">
				  <li class="active"><a data-toggle="tab" href="#home">본사</a></li>
				  <li><a data-toggle="tab" href="#west">미서부 LA</a></li>
				  <li><a data-toggle="tab" href="#las">라스베가스</a></li>
				  <li><a data-toggle="tab" href="#das">달라스</a></li>
				  <li><a data-toggle="tab" href="#ats">애틀란타</a></li>
				  <li><a data-toggle="tab" href="#sea">시애틀</a></li>
				</ul>
				<div class="col-sm-12 col-md-12 tab-content">
						
					<div id="home" class="tab-pane fade  in active">
							<form action='m_banner.php?division=9&pdx=2&sub=20' Enctype="multipart/form-data" method=post onSubmit="return chk(this)">
									  <input type=hidden name=mode value="save">
									  <input type=hidden name=divi value="head">
									  
									  <table id="productDetailForm" class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
									  <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b1'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row1 = mysql_fetch_assoc($rst1);	
									   ?>

										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인1</br>
											&nbsp;<input type="text" class="form-control" id="apos1" name="apos1" placeholder="위치" value="<?=$row1['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink1" name="alink1" placeholder="링크" value="<?=$row1['alink']?>">&nbsp;PC<input type=file name=userfile1 size=30>
											<?= $row1['img1'] ?>
											<?php if($row1['img1']): ?>
												
													<input type="checkbox" id="photo_del1" name="photo_del1" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile11 size=30>
											<?= $row1['img2'] ?>
											<?php if($row1['img2']): ?>
												
													<input type="checkbox" id="photo_del11" name="photo_del11" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
                                       <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b2'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row2 = mysql_fetch_assoc($rst1);	
									   ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인2</br>
											&nbsp;<input type="text" class="form-control" id="apos2" name="apos2" placeholder="위치" value="<?=$row2['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink2" name="alink2" placeholder="링크" value="<?=$row2['alink']?>">&nbsp;PC<input type=file name=userfile2 size=30>
											<?= $row2['img1'] ?>
											<?php if($row2['img1']): ?>
												
													<input type="checkbox" id="photo_del2" name="photo_del2" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile22 size=30>
											<?= $row2['img2'] ?>
											<?php if($row2['img2']): ?>
												
													<input type="checkbox" id="photo_del22" name="photo_del22" value="1">삭제 
													
												
											<?php endif; ?>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b3'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row3 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인3</br>
											&nbsp;<input type="text" class="form-control" id="apos3" name="apos3" placeholder="위치" value="<?=$row3['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink3" name="alink3" placeholder="링크" value="<?=$row3['alink']?>">&nbsp;PC<input type=file name=userfile3 size=30>
											<?= $row3['img1'] ?>
											<?php if($row3['img1']): ?>
												
													<input type="checkbox" id="photo_del3" name="photo_del3" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile33 size=30>
											<?= $row3['img2'] ?>
											<?php if($row3['img2']): ?>
												
													<input type="checkbox" id="photo_del33" name="photo_del33" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b4'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row4 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인4</br>
											&nbsp;<input type="text" class="form-control" id="apos4" name="apos4" placeholder="위치" value="<?=$row4['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink4" name="alink4" placeholder="링크" value="<?=$row4['alink']?>">&nbsp;PC<input type=file name=userfile4 size=30>
											<?= $row4['img1'] ?>
											<?php if($row4['img1']): ?>
												
													<input type="checkbox" id="photo_del4" name="photo_del4" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile44 size=30>
											<?= $row4['img2'] ?>
											<?php if($row4['img2']): ?>
												
													<input type="checkbox" id="photo_del44" name="photo_del44" value="1">삭제 
													
												
											<?php endif; ?></td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b5'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row5 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인5</br>
											&nbsp;<input type="text" class="form-control" id="apos5" name="apos5" placeholder="위치" value="<?=$row5['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink5" name="alink5" placeholder="링크" value="<?=$row5['alink']?>">&nbsp;PC<input type=file name=userfile5 size=30>
											<?= $row5['img1'] ?>
											<?php if($row5['img1']): ?>
												
													<input type="checkbox" id="photo_del5" name="photo_del5" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile55 size=30>
											<?= $row5['img2'] ?>
											<?php if($row5['img2']): ?>
												
													<input type="checkbox" id="photo_del55" name="photo_del55" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b6'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row6 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인6</br>
											&nbsp;<input type="text" class="form-control" id="apos6" name="apos6" placeholder="위치" value="<?=$row6['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink6" name="alink6" placeholder="링크" value="<?=$row6['alink']?>">&nbsp;PC<input type=file name=userfile6 size=30>
											<?= $row6['img1'] ?>

											<?php if($row6['img1']): ?>
												
													<input type="checkbox" id="photo_del6" name="photo_del6" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile66 size=30>
											<?= $row6['img2'] ?>
											<?php if($row6['img2']): ?>
												
													<input type="checkbox" id="photo_del66" name="photo_del66" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b7'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row7 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인7</br>
											&nbsp;<input type="text" class="form-control" id="apos7" name="apos7" placeholder="위치" value="<?=$row7['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink7" name="alink7" placeholder="링크" value="<?=$row7['alink']?>">&nbsp;PC<input type=file name=userfile7 size=30>
											<?= $row7['img1'] ?>
											<?php if($row7['img1']): ?>
												
													<input type="checkbox" id="photo_del7" name="photo_del7" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile77 size=30>
											<?= $row7['img2'] ?>
											<?php if($row7['img2']): ?>
												
													<input type="checkbox" id="photo_del77" name="photo_del77" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b8'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row8 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인8</br>
											&nbsp;<input type="text" class="form-control" id="apos8" name="apos8" placeholder="위치" value="<?=$row8['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink8" name="alink8" placeholder="링크" value="<?=$row8['alink']?>">&nbsp;PC<input type=file name=userfile8 size=30>
											<?= $row8['img1'] ?>

											<?php if($row8['img1']): ?>
												
													<input type="checkbox" id="photo_del8" name="photo_del8" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile88 size=30>
											<?= $row8['img2'] ?>
											<?php if($row8['img2']): ?>
												
													<input type="checkbox" id="photo_del88" name="photo_del88" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b9'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row9 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인9</br>
											&nbsp;<input type="text" class="form-control" id="apos9" name="apos9" placeholder="위치" value="<?=$row9['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink9" name="alink9" placeholder="링크" value="<?=$row9['alink']?>">&nbsp;PC<input type=file name=userfile9 size=30>
											<?= $row9['img1'] ?>

											<?php if($row9['img1']): ?>
												
													<input type="checkbox" id="photo_del9" name="photo_del9" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile99 size=30>
											<?= $row9['img2'] ?>
											<?php if($row9['img2']): ?>
												
													<input type="checkbox" id="photo_del99" name="photo_del99" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b10'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row10 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인10</br>
											&nbsp;<input type="text" class="form-control" id="apos10" name="apos10" placeholder="위치" value="<?=$row10['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink10" name="alink10" placeholder="링크" value="<?=$row10['alink']?>">&nbsp;PC<input type=file name=userfile10 size=30>
											<?= $row10['img1'] ?>

											<?php if($row10['img1']): ?>
												
													<input type="checkbox" id="photo_del10" name="photo_del10" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile100 size=30>
											<?= $row10['img2'] ?>
											<?php if($row10['img2']): ?>
												
													<input type="checkbox" id="photo_del100" name="photo_del100" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='head' && area = 'b11'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row11 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인11</br>
											&nbsp;<input type="text" class="form-control" id="apos11" name="apos11" placeholder="위치" value="<?=$row11['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink111" name="alink111" placeholder="링크" value="<?=$row11['alink']?>">&nbsp;PC<input type=file name=userfile110 size=30>
											<?= $row11['img1'] ?>

											<?php if($row11['img1']): ?>
												
													<input type="checkbox" id="photo_del101" name="photo_del101" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile1110 size=30>
											<?= $row11['img2'] ?>
											<?php if($row11['img2']): ?>
												
													<input type="checkbox" id="photo_del1101" name="photo_del1101" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<tr bgcolor=#FFFFFF>
											
											<tr>
												<td colspan=4 height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
											</tr>
										</tr>
									  </table>
									  <br><br>
									  
									  
										
										
						    </form>
						  
					</div> 

					<div id="west" class="tab-pane fade">
							<form action='m_banner.php?division=9&pdx=2&sub=20' Enctype="multipart/form-data" method=post onSubmit="return chk(this)">
									  <input type=hidden name=mode value="save">
									  <input type=hidden name=divi value="west">
									  
									  <table id="productDetailForm" class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
									  <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b1'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row1 = mysql_fetch_assoc($rst1);	
									   ?>

										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인1</br>
											&nbsp;<input type="text" class="form-control" id="apos1" name="apos1" placeholder="위치" value="<?=$row1['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink1" name="alink1" placeholder="링크" value="<?=$row1['alink']?>">&nbsp;PC<input type=file name=userfile1 size=30>
											<?= $row1['img1'] ?>
											<?php if($row1['img1']): ?>
												
													<input type="checkbox" id="photo_del1" name="photo_del1" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile11 size=30>
											<?= $row1['img2'] ?>
											<?php if($row1['img2']): ?>
												
													<input type="checkbox" id="photo_del11" name="photo_del11" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
                                       <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b2'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row2 = mysql_fetch_assoc($rst1);	
									   ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인2</br>
											&nbsp;<input type="text" class="form-control" id="apos2" name="apos2" placeholder="위치" value="<?=$row2['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink2" name="alink2" placeholder="링크" value="<?=$row2['alink']?>">&nbsp;PC<input type=file name=userfile2 size=30>
											<?= $row2['img1'] ?>
											<?php if($row2['img1']): ?>
												
													<input type="checkbox" id="photo_del2" name="photo_del2" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile22 size=30>
											<?= $row2['img2'] ?>
											<?php if($row2['img2']): ?>
												
													<input type="checkbox" id="photo_del22" name="photo_del22" value="1">삭제 
													
												
											<?php endif; ?>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b3'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row3 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인3</br>
											&nbsp;<input type="text" class="form-control" id="apos3" name="apos3" placeholder="위치" value="<?=$row3['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink3" name="alink3" placeholder="링크" value="<?=$row3['alink']?>">&nbsp;PC<input type=file name=userfile3 size=30>
											<?= $row3['img1'] ?>
											<?php if($row3['img1']): ?>
												
													<input type="checkbox" id="photo_del3" name="photo_del3" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile33 size=30>
											<?= $row3['img2'] ?>
											<?php if($row3['img2']): ?>
												
													<input type="checkbox" id="photo_del33" name="photo_del33" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b4'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row4 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인4</br>
											&nbsp;<input type="text" class="form-control" id="apos4" name="apos4" placeholder="위치" value="<?=$row4['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink4" name="alink4" placeholder="링크" value="<?=$row4['alink']?>">&nbsp;PC<input type=file name=userfile4 size=30>
											<?= $row4['img1'] ?>
											<?php if($row4['img1']): ?>
												
													<input type="checkbox" id="photo_del4" name="photo_del4" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile44 size=30>
											<?= $row4['img2'] ?>
											<?php if($row4['img2']): ?>
												
													<input type="checkbox" id="photo_del44" name="photo_del44" value="1">삭제 
													
												
											<?php endif; ?></td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b5'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row5 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인5</br>
											&nbsp;<input type="text" class="form-control" id="apos5" name="apos5" placeholder="위치" value="<?=$row5['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink5" name="alink5" placeholder="링크" value="<?=$row5['alink']?>">&nbsp;PC<input type=file name=userfile5 size=30>
											<?= $row5['img1'] ?>
											<?php if($row5['img1']): ?>
												
													<input type="checkbox" id="photo_del5" name="photo_del5" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile55 size=30>
											<?= $row5['img2'] ?>
											<?php if($row5['img2']): ?>
												
													<input type="checkbox" id="photo_del55" name="photo_del55" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b6'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row6 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인6</br>
											&nbsp;<input type="text" class="form-control" id="apos6" name="apos6" placeholder="위치" value="<?=$row6['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink6" name="alink6" placeholder="링크" value="<?=$row6['alink']?>">&nbsp;PC<input type=file name=userfile6 size=30>
											<?= $row6['img1'] ?>

											<?php if($row6['img1']): ?>
												
													<input type="checkbox" id="photo_del6" name="photo_del6" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile66 size=30>
											<?= $row6['img2'] ?>
											<?php if($row6['img2']): ?>
												
													<input type="checkbox" id="photo_del66" name="photo_del66" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b7'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row7 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인7</br>
											&nbsp;<input type="text" class="form-control" id="apos7" name="apos7" placeholder="위치" value="<?=$row7['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink7" name="alink7" placeholder="링크" value="<?=$row7['alink']?>">&nbsp;PC<input type=file name=userfile7 size=30>
											<?= $row7['img1'] ?>
											<?php if($row7['img1']): ?>
												
													<input type="checkbox" id="photo_del7" name="photo_del7" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile77 size=30>
											<?= $row7['img2'] ?>
											<?php if($row7['img2']): ?>
												
													<input type="checkbox" id="photo_del77" name="photo_del77" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b8'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row8 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인8</br>
											&nbsp;<input type="text" class="form-control" id="apos8" name="apos8" placeholder="위치" value="<?=$row8['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink8" name="alink8" placeholder="링크" value="<?=$row8['alink']?>">&nbsp;PC<input type=file name=userfile8 size=30>
											<?= $row8['img1'] ?>

											<?php if($row8['img1']): ?>
												
													<input type="checkbox" id="photo_del8" name="photo_del8" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile88 size=30>
											<?= $row8['img2'] ?>
											<?php if($row8['img2']): ?>
												
													<input type="checkbox" id="photo_del88" name="photo_del88" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b9'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row9 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인9</br>
											&nbsp;<input type="text" class="form-control" id="apos9" name="apos9" placeholder="위치" value="<?=$row9['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink9" name="alink9" placeholder="링크" value="<?=$row9['alink']?>">&nbsp;PC<input type=file name=userfile9 size=30>
											<?= $row9['img1'] ?>

											<?php if($row9['img1']): ?>
												
													<input type="checkbox" id="photo_del9" name="photo_del9" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile99 size=30>
											<?= $row9['img2'] ?>
											<?php if($row9['img2']): ?>
												
													<input type="checkbox" id="photo_del99" name="photo_del99" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b10'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row10 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인10</br>
											&nbsp;<input type="text" class="form-control" id="apos10" name="apos10" placeholder="위치" value="<?=$row10['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink10" name="alink10" placeholder="링크" value="<?=$row10['alink']?>">&nbsp;PC<input type=file name=userfile10 size=30>
											<?= $row10['img1'] ?>

											<?php if($row10['img1']): ?>
												
													<input type="checkbox" id="photo_del10" name="photo_del10" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile100 size=30>
											<?= $row10['img2'] ?>
											<?php if($row10['img2']): ?>
												
													<input type="checkbox" id="photo_del100" name="photo_del100" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='west' && area = 'b11'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row11 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인11</br>
											&nbsp;<input type="text" class="form-control" id="apos11" name="apos11" placeholder="위치" value="<?=$row11['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink111" name="alink111" placeholder="링크" value="<?=$row11['alink']?>">&nbsp;PC<input type=file name=userfile110 size=30>
											<?= $row11['img1'] ?>

											<?php if($row11['img1']): ?>
												
													<input type="checkbox" id="photo_del101" name="photo_del101" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile1110 size=30>
											<?= $row11['img2'] ?>
											<?php if($row11['img2']): ?>
												
													<input type="checkbox" id="photo_del1101" name="photo_del1101" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<tr bgcolor=#FFFFFF>
											
											<tr>
												<td colspan=4 height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
											</tr>
										</tr>
									  </table>
									  <br><br>
									  
									  
										
										
						    </form>
						  
					</div> 

					<div id="las" class="tab-pane fade">
							<form action='m_banner.php?division=9&pdx=2&sub=20' Enctype="multipart/form-data" method=post onSubmit="return chk(this)">
									  <input type=hidden name=mode value="save">
									  <input type=hidden name=divi value="las">
									  
									  <table id="productDetailForm" class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
									  <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b1'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row1 = mysql_fetch_assoc($rst1);	
									   ?>

										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인1</br>
											&nbsp;<input type="text" class="form-control" id="apos1" name="apos1" placeholder="위치" value="<?=$row1['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink1" name="alink1" placeholder="링크" value="<?=$row1['alink']?>">&nbsp;PC<input type=file name=userfile1 size=30>
											<?= $row1['img1'] ?>
											<?php if($row1['img1']): ?>
												
													<input type="checkbox" id="photo_del1" name="photo_del1" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile11 size=30>
											<?= $row1['img2'] ?>
											<?php if($row1['img2']): ?>
												
													<input type="checkbox" id="photo_del11" name="photo_del11" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
                                       <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b2'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row2 = mysql_fetch_assoc($rst1);	
									   ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인2</br>
											&nbsp;<input type="text" class="form-control" id="apos2" name="apos2" placeholder="위치" value="<?=$row2['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink2" name="alink2" placeholder="링크" value="<?=$row2['alink']?>">&nbsp;PC<input type=file name=userfile2 size=30>
											<?= $row2['img1'] ?>
											<?php if($row2['img1']): ?>
												
													<input type="checkbox" id="photo_del2" name="photo_del2" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile22 size=30>
											<?= $row2['img2'] ?>
											<?php if($row2['img2']): ?>
												
													<input type="checkbox" id="photo_del22" name="photo_del22" value="1">삭제 
													
												
											<?php endif; ?>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b3'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row3 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인3</br>
											&nbsp;<input type="text" class="form-control" id="apos3" name="apos3" placeholder="위치" value="<?=$row3['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink3" name="alink3" placeholder="링크" value="<?=$row3['alink']?>">&nbsp;PC<input type=file name=userfile3 size=30>
											<?= $row3['img1'] ?>
											<?php if($row3['img1']): ?>
												
													<input type="checkbox" id="photo_del3" name="photo_del3" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile33 size=30>
											<?= $row3['img2'] ?>
											<?php if($row3['img2']): ?>
												
													<input type="checkbox" id="photo_del33" name="photo_del33" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b4'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row4 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인4</br>
											&nbsp;<input type="text" class="form-control" id="apos4" name="apos4" placeholder="위치" value="<?=$row4['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink4" name="alink4" placeholder="링크" value="<?=$row4['alink']?>">&nbsp;PC<input type=file name=userfile4 size=30>
											<?= $row4['img1'] ?>
											<?php if($row4['img1']): ?>
												
													<input type="checkbox" id="photo_del4" name="photo_del4" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile44 size=30>
											<?= $row4['img2'] ?>
											<?php if($row4['img2']): ?>
												
													<input type="checkbox" id="photo_del44" name="photo_del44" value="1">삭제 
													
												
											<?php endif; ?></td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b5'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row5 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인5</br>
											&nbsp;<input type="text" class="form-control" id="apos5" name="apos5" placeholder="위치" value="<?=$row5['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink5" name="alink5" placeholder="링크" value="<?=$row5['alink']?>">&nbsp;PC<input type=file name=userfile5 size=30>
											<?= $row5['img1'] ?>
											<?php if($row5['img1']): ?>
												
													<input type="checkbox" id="photo_del5" name="photo_del5" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile55 size=30>
											<?= $row5['img2'] ?>
											<?php if($row5['img2']): ?>
												
													<input type="checkbox" id="photo_del55" name="photo_del55" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b6'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row6 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인6</br>
											&nbsp;<input type="text" class="form-control" id="apos6" name="apos6" placeholder="위치" value="<?=$row6['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink6" name="alink6" placeholder="링크" value="<?=$row6['alink']?>">&nbsp;PC<input type=file name=userfile6 size=30>
											<?= $row6['img1'] ?>

											<?php if($row6['img1']): ?>
												
													<input type="checkbox" id="photo_del6" name="photo_del6" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile66 size=30>
											<?= $row6['img2'] ?>
											<?php if($row6['img2']): ?>
												
													<input type="checkbox" id="photo_del66" name="photo_del66" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b7'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row7 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인7</br>
											&nbsp;<input type="text" class="form-control" id="apos7" name="apos7" placeholder="위치" value="<?=$row7['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink7" name="alink7" placeholder="링크" value="<?=$row7['alink']?>">&nbsp;PC<input type=file name=userfile7 size=30>
											<?= $row7['img1'] ?>
											<?php if($row7['img1']): ?>
												
													<input type="checkbox" id="photo_del7" name="photo_del7" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile77 size=30>
											<?= $row7['img2'] ?>
											<?php if($row7['img2']): ?>
												
													<input type="checkbox" id="photo_del77" name="photo_del77" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b8'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row8 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인8</br>
											&nbsp;<input type="text" class="form-control" id="apos8" name="apos8" placeholder="위치" value="<?=$row8['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink8" name="alink8" placeholder="링크" value="<?=$row8['alink']?>">&nbsp;PC<input type=file name=userfile8 size=30>
											<?= $row8['img1'] ?>

											<?php if($row8['img1']): ?>
												
													<input type="checkbox" id="photo_del8" name="photo_del8" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile88 size=30>
											<?= $row8['img2'] ?>
											<?php if($row8['img2']): ?>
												
													<input type="checkbox" id="photo_del88" name="photo_del88" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b9'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row9 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인9</br>
											&nbsp;<input type="text" class="form-control" id="apos9" name="apos9" placeholder="위치" value="<?=$row9['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink9" name="alink9" placeholder="링크" value="<?=$row9['alink']?>">&nbsp;PC<input type=file name=userfile9 size=30>
											<?= $row9['img1'] ?>

											<?php if($row9['img1']): ?>
												
													<input type="checkbox" id="photo_del9" name="photo_del9" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile99 size=30>
											<?= $row9['img2'] ?>
											<?php if($row9['img2']): ?>
												
													<input type="checkbox" id="photo_del99" name="photo_del99" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b10'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row10 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인10</br>
											&nbsp;<input type="text" class="form-control" id="apos10" name="apos10" placeholder="위치" value="<?=$row10['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink10" name="alink10" placeholder="링크" value="<?=$row10['alink']?>">&nbsp;PC<input type=file name=userfile10 size=30>
											<?= $row10['img1'] ?>

											<?php if($row10['img1']): ?>
												
													<input type="checkbox" id="photo_del10" name="photo_del10" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile100 size=30>
											<?= $row10['img2'] ?>
											<?php if($row10['img2']): ?>
												
													<input type="checkbox" id="photo_del100" name="photo_del100" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='las' && area = 'b11'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row11 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인11</br>
											&nbsp;<input type="text" class="form-control" id="apos11" name="apos11" placeholder="위치" value="<?=$row11['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink111" name="alink111" placeholder="링크" value="<?=$row11['alink']?>">&nbsp;PC<input type=file name=userfile110 size=30>
											<?= $row11['img1'] ?>

											<?php if($row11['img1']): ?>
												
													<input type="checkbox" id="photo_del101" name="photo_del101" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile1110 size=30>
											<?= $row11['img2'] ?>
											<?php if($row11['img2']): ?>
												
													<input type="checkbox" id="photo_del1101" name="photo_del1101" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<tr bgcolor=#FFFFFF>
											
											<tr>
												<td colspan=4 height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
											</tr>
										</tr>
									  </table>
									  <br><br>
									  
									  
										
										
						    </form>
						  
					</div> 
					<div id="das" class="tab-pane fade">
							<form action='m_banner.php?division=9&pdx=2&sub=20' Enctype="multipart/form-data" method=post onSubmit="return chk(this)">
									  <input type=hidden name=mode value="save">
									  <input type=hidden name=divi value="das">
									  
									  <table id="productDetailForm" class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
									  <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b1'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row1 = mysql_fetch_assoc($rst1);	
									   ?>

										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인1</br>
											&nbsp;<input type="text" class="form-control" id="apos1" name="apos1" placeholder="위치" value="<?=$row1['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink1" name="alink1" placeholder="링크" value="<?=$row1['alink']?>">&nbsp;PC<input type=file name=userfile1 size=30>
											<?= $row1['img1'] ?>
											<?php if($row1['img1']): ?>
												
													<input type="checkbox" id="photo_del1" name="photo_del1" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile11 size=30>
											<?= $row1['img2'] ?>
											<?php if($row1['img2']): ?>
												
													<input type="checkbox" id="photo_del11" name="photo_del11" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
                                       <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b2'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row2 = mysql_fetch_assoc($rst1);	
									   ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인2</br>
											&nbsp;<input type="text" class="form-control" id="apos2" name="apos2" placeholder="위치" value="<?=$row2['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink2" name="alink2" placeholder="링크" value="<?=$row2['alink']?>">&nbsp;PC<input type=file name=userfile2 size=30>
											<?= $row2['img1'] ?>
											<?php if($row2['img1']): ?>
												
													<input type="checkbox" id="photo_del2" name="photo_del2" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile22 size=30>
											<?= $row2['img2'] ?>
											<?php if($row2['img2']): ?>
												
													<input type="checkbox" id="photo_del22" name="photo_del22" value="1">삭제 
													
												
											<?php endif; ?>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b3'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row3 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인3</br>
											&nbsp;<input type="text" class="form-control" id="apos3" name="apos3" placeholder="위치" value="<?=$row3['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink3" name="alink3" placeholder="링크" value="<?=$row3['alink']?>">&nbsp;PC<input type=file name=userfile3 size=30>
											<?= $row3['img1'] ?>
											<?php if($row3['img1']): ?>
												
													<input type="checkbox" id="photo_del3" name="photo_del3" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile33 size=30>
											<?= $row3['img2'] ?>
											<?php if($row3['img2']): ?>
												
													<input type="checkbox" id="photo_del33" name="photo_del33" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b4'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row4 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인4</br>
											&nbsp;<input type="text" class="form-control" id="apos4" name="apos4" placeholder="위치" value="<?=$row4['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink4" name="alink4" placeholder="링크" value="<?=$row4['alink']?>">&nbsp;PC<input type=file name=userfile4 size=30>
											<?= $row4['img1'] ?>
											<?php if($row4['img1']): ?>
												
													<input type="checkbox" id="photo_del4" name="photo_del4" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile44 size=30>
											<?= $row4['img2'] ?>
											<?php if($row4['img2']): ?>
												
													<input type="checkbox" id="photo_del44" name="photo_del44" value="1">삭제 
													
												
											<?php endif; ?></td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b5'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row5 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인5</br>
											&nbsp;<input type="text" class="form-control" id="apos5" name="apos5" placeholder="위치" value="<?=$row5['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink5" name="alink5" placeholder="링크" value="<?=$row5['alink']?>">&nbsp;PC<input type=file name=userfile5 size=30>
											<?= $row5['img1'] ?>
											<?php if($row5['img1']): ?>
												
													<input type="checkbox" id="photo_del5" name="photo_del5" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile55 size=30>
											<?= $row5['img2'] ?>
											<?php if($row5['img2']): ?>
												
													<input type="checkbox" id="photo_del55" name="photo_del55" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b6'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row6 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인6</br>
											&nbsp;<input type="text" class="form-control" id="apos6" name="apos6" placeholder="위치" value="<?=$row6['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink6" name="alink6" placeholder="링크" value="<?=$row6['alink']?>">&nbsp;PC<input type=file name=userfile6 size=30>
											<?= $row6['img1'] ?>

											<?php if($row6['img1']): ?>
												
													<input type="checkbox" id="photo_del6" name="photo_del6" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile66 size=30>
											<?= $row6['img2'] ?>
											<?php if($row6['img2']): ?>
												
													<input type="checkbox" id="photo_del66" name="photo_del66" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b7'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row7 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인7</br>
											&nbsp;<input type="text" class="form-control" id="apos7" name="apos7" placeholder="위치" value="<?=$row7['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink7" name="alink7" placeholder="링크" value="<?=$row7['alink']?>">&nbsp;PC<input type=file name=userfile7 size=30>
											<?= $row7['img1'] ?>
											<?php if($row7['img1']): ?>
												
													<input type="checkbox" id="photo_del7" name="photo_del7" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile77 size=30>
											<?= $row7['img2'] ?>
											<?php if($row7['img2']): ?>
												
													<input type="checkbox" id="photo_del77" name="photo_del77" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b8'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row8 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인8</br>
											&nbsp;<input type="text" class="form-control" id="apos8" name="apos8" placeholder="위치" value="<?=$row8['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink8" name="alink8" placeholder="링크" value="<?=$row8['alink']?>">&nbsp;PC<input type=file name=userfile8 size=30>
											<?= $row8['img1'] ?>

											<?php if($row8['img1']): ?>
												
													<input type="checkbox" id="photo_del8" name="photo_del8" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile88 size=30>
											<?= $row8['img2'] ?>
											<?php if($row8['img2']): ?>
												
													<input type="checkbox" id="photo_del88" name="photo_del88" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b9'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row9 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인9</br>
											&nbsp;<input type="text" class="form-control" id="apos9" name="apos9" placeholder="위치" value="<?=$row9['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink9" name="alink9" placeholder="링크" value="<?=$row9['alink']?>">&nbsp;PC<input type=file name=userfile9 size=30>
											<?= $row9['img1'] ?>

											<?php if($row9['img1']): ?>
												
													<input type="checkbox" id="photo_del9" name="photo_del9" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile99 size=30>
											<?= $row9['img2'] ?>
											<?php if($row9['img2']): ?>
												
													<input type="checkbox" id="photo_del99" name="photo_del99" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b10'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row10 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인10</br>
											&nbsp;<input type="text" class="form-control" id="apos10" name="apos10" placeholder="위치" value="<?=$row10['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink10" name="alink10" placeholder="링크" value="<?=$row10['alink']?>">&nbsp;PC<input type=file name=userfile10 size=30>
											<?= $row10['img1'] ?>

											<?php if($row10['img1']): ?>
												
													<input type="checkbox" id="photo_del10" name="photo_del10" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile100 size=30>
											<?= $row10['img2'] ?>
											<?php if($row10['img2']): ?>
												
													<input type="checkbox" id="photo_del100" name="photo_del100" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='das' && area = 'b11'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row11 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인11</br>
											&nbsp;<input type="text" class="form-control" id="apos11" name="apos11" placeholder="위치" value="<?=$row11['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink111" name="alink111" placeholder="링크" value="<?=$row11['alink']?>">&nbsp;PC<input type=file name=userfile110 size=30>
											<?= $row11['img1'] ?>

											<?php if($row11['img1']): ?>
												
													<input type="checkbox" id="photo_del101" name="photo_del101" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile1110 size=30>
											<?= $row11['img2'] ?>
											<?php if($row11['img2']): ?>
												
													<input type="checkbox" id="photo_del1101" name="photo_del1101" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<tr bgcolor=#FFFFFF>
											
											<tr>
												<td colspan=4 height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
											</tr>
										</tr>
									  </table>
									  <br><br>
									  
									  
										
										
						    </form>
						  
					</div> 
					<div id="ats" class="tab-pane fade">
							<form action='m_banner.php?division=9&pdx=2&sub=20' Enctype="multipart/form-data" method=post onSubmit="return chk(this)">
									  <input type=hidden name=mode value="save">
									  <input type=hidden name=divi value="ats">
									  
									  <table id="productDetailForm" class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
									  <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b1'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row1 = mysql_fetch_assoc($rst1);	
									   ?>

										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인1</br>
											&nbsp;<input type="text" class="form-control" id="apos1" name="apos1" placeholder="위치" value="<?=$row1['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink1" name="alink1" placeholder="링크" value="<?=$row1['alink']?>">&nbsp;PC<input type=file name=userfile1 size=30>
											<?= $row1['img1'] ?>
											<?php if($row1['img1']): ?>
												
													<input type="checkbox" id="photo_del1" name="photo_del1" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile11 size=30>
											<?= $row1['img2'] ?>
											<?php if($row1['img2']): ?>
												
													<input type="checkbox" id="photo_del11" name="photo_del11" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
                                       <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b2'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row2 = mysql_fetch_assoc($rst1);	
									   ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인2</br>
											&nbsp;<input type="text" class="form-control" id="apos2" name="apos2" placeholder="위치" value="<?=$row2['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink2" name="alink2" placeholder="링크" value="<?=$row2['alink']?>">&nbsp;PC<input type=file name=userfile2 size=30>
											<?= $row2['img1'] ?>
											<?php if($row2['img1']): ?>
												
													<input type="checkbox" id="photo_del2" name="photo_del2" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile22 size=30>
											<?= $row2['img2'] ?>
											<?php if($row2['img2']): ?>
												
													<input type="checkbox" id="photo_del22" name="photo_del22" value="1">삭제 
													
												
											<?php endif; ?>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b3'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row3 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인3</br>
											&nbsp;<input type="text" class="form-control" id="apos3" name="apos3" placeholder="위치" value="<?=$row3['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink3" name="alink3" placeholder="링크" value="<?=$row3['alink']?>">&nbsp;PC<input type=file name=userfile3 size=30>
											<?= $row3['img1'] ?>
											<?php if($row3['img1']): ?>
												
													<input type="checkbox" id="photo_del3" name="photo_del3" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile33 size=30>
											<?= $row3['img2'] ?>
											<?php if($row3['img2']): ?>
												
													<input type="checkbox" id="photo_del33" name="photo_del33" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b4'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row4 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인4</br>
											&nbsp;<input type="text" class="form-control" id="apos4" name="apos4" placeholder="위치" value="<?=$row4['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink4" name="alink4" placeholder="링크" value="<?=$row4['alink']?>">&nbsp;PC<input type=file name=userfile4 size=30>
											<?= $row4['img1'] ?>
											<?php if($row4['img1']): ?>
												
													<input type="checkbox" id="photo_del4" name="photo_del4" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile44 size=30>
											<?= $row4['img2'] ?>
											<?php if($row4['img2']): ?>
												
													<input type="checkbox" id="photo_del44" name="photo_del44" value="1">삭제 
													
												
											<?php endif; ?></td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b5'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row5 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인5</br>
											&nbsp;<input type="text" class="form-control" id="apos5" name="apos5" placeholder="위치" value="<?=$row5['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink5" name="alink5" placeholder="링크" value="<?=$row5['alink']?>">&nbsp;PC<input type=file name=userfile5 size=30>
											<?= $row5['img1'] ?>
											<?php if($row5['img1']): ?>
												
													<input type="checkbox" id="photo_del5" name="photo_del5" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile55 size=30>
											<?= $row5['img2'] ?>
											<?php if($row5['img2']): ?>
												
													<input type="checkbox" id="photo_del55" name="photo_del55" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b6'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row6 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인6</br>
											&nbsp;<input type="text" class="form-control" id="apos6" name="apos6" placeholder="위치" value="<?=$row6['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink6" name="alink6" placeholder="링크" value="<?=$row6['alink']?>">&nbsp;PC<input type=file name=userfile6 size=30>
											<?= $row6['img1'] ?>

											<?php if($row6['img1']): ?>
												
													<input type="checkbox" id="photo_del6" name="photo_del6" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile66 size=30>
											<?= $row6['img2'] ?>
											<?php if($row6['img2']): ?>
												
													<input type="checkbox" id="photo_del66" name="photo_del66" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b7'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row7 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인7</br>
											&nbsp;<input type="text" class="form-control" id="apos7" name="apos7" placeholder="위치" value="<?=$row7['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink7" name="alink7" placeholder="링크" value="<?=$row7['alink']?>">&nbsp;PC<input type=file name=userfile7 size=30>
											<?= $row7['img1'] ?>
											<?php if($row7['img1']): ?>
												
													<input type="checkbox" id="photo_del7" name="photo_del7" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile77 size=30>
											<?= $row7['img2'] ?>
											<?php if($row7['img2']): ?>
												
													<input type="checkbox" id="photo_del77" name="photo_del77" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b8'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row8 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인8</br>
											&nbsp;<input type="text" class="form-control" id="apos8" name="apos8" placeholder="위치" value="<?=$row8['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink8" name="alink8" placeholder="링크" value="<?=$row8['alink']?>">&nbsp;PC<input type=file name=userfile8 size=30>
											<?= $row8['img1'] ?>

											<?php if($row8['img1']): ?>
												
													<input type="checkbox" id="photo_del8" name="photo_del8" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile88 size=30>
											<?= $row8['img2'] ?>
											<?php if($row8['img2']): ?>
												
													<input type="checkbox" id="photo_del88" name="photo_del88" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b9'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row9 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인9</br>
											&nbsp;<input type="text" class="form-control" id="apos9" name="apos9" placeholder="위치" value="<?=$row9['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink9" name="alink9" placeholder="링크" value="<?=$row9['alink']?>">&nbsp;PC<input type=file name=userfile9 size=30>
											<?= $row9['img1'] ?>

											<?php if($row9['img1']): ?>
												
													<input type="checkbox" id="photo_del9" name="photo_del9" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile99 size=30>
											<?= $row9['img2'] ?>
											<?php if($row9['img2']): ?>
												
													<input type="checkbox" id="photo_del99" name="photo_del99" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b10'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row10 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인10</br>
											&nbsp;<input type="text" class="form-control" id="apos10" name="apos10" placeholder="위치" value="<?=$row10['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink10" name="alink10" placeholder="링크" value="<?=$row10['alink']?>">&nbsp;PC<input type=file name=userfile10 size=30>
											<?= $row10['img1'] ?>

											<?php if($row10['img1']): ?>
												
													<input type="checkbox" id="photo_del10" name="photo_del10" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile100 size=30>
											<?= $row10['img2'] ?>
											<?php if($row10['img2']): ?>
												
													<input type="checkbox" id="photo_del100" name="photo_del100" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='ats' && area = 'b11'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row11 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인11</br>
											&nbsp;<input type="text" class="form-control" id="apos11" name="apos11" placeholder="위치" value="<?=$row11['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink111" name="alink111" placeholder="링크" value="<?=$row11['alink']?>">&nbsp;PC<input type=file name=userfile110 size=30>
											<?= $row11['img1'] ?>

											<?php if($row11['img1']): ?>
												
													<input type="checkbox" id="photo_del101" name="photo_del101" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile1110 size=30>
											<?= $row11['img2'] ?>
											<?php if($row11['img2']): ?>
												
													<input type="checkbox" id="photo_del1101" name="photo_del1101" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<tr bgcolor=#FFFFFF>
											
											<tr>
												<td colspan=4 height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
											</tr>
										</tr>
									  </table>
									  <br><br>
									  
									  
										
										
						    </form>
						  
					</div>
					<div id="sea" class="tab-pane fade">
							<form action='m_banner.php?division=9&pdx=2&sub=20' Enctype="multipart/form-data" method=post onSubmit="return chk(this)">
									  <input type=hidden name=mode value="save">
									  <input type=hidden name=divi value="sea">
									  
									  <table id="productDetailForm" class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base" width="98%" align=center border="0" cellspacing="1" bgcolor=#cccccc cellpadding="0">
									  <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b1'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row1 = mysql_fetch_assoc($rst1);	
									   ?>

										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인1</br>
											&nbsp;<input type="text" class="form-control" id="apos1" name="apos1" placeholder="위치" value="<?=$row1['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink1" name="alink1" placeholder="링크" value="<?=$row1['alink']?>">&nbsp;PC<input type=file name=userfile1 size=30>
											<?= $row1['img1'] ?>
											<?php if($row1['img1']): ?>
												
													<input type="checkbox" id="photo_del1" name="photo_del1" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile11 size=30>
											<?= $row1['img2'] ?>
											<?php if($row1['img2']): ?>
												
													<input type="checkbox" id="photo_del11" name="photo_del11" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
                                       <?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b2'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row2 = mysql_fetch_assoc($rst1);	
									   ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인2</br>
											&nbsp;<input type="text" class="form-control" id="apos2" name="apos2" placeholder="위치" value="<?=$row2['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink2" name="alink2" placeholder="링크" value="<?=$row2['alink']?>">&nbsp;PC<input type=file name=userfile2 size=30>
											<?= $row2['img1'] ?>
											<?php if($row2['img1']): ?>
												
													<input type="checkbox" id="photo_del2" name="photo_del2" value="1">삭제 
													
												
											<?php endif; ?>
											<br />&nbsp;Mobile<input type=file name=userfile22 size=30>
											<?= $row2['img2'] ?>
											<?php if($row2['img2']): ?>
												
													<input type="checkbox" id="photo_del22" name="photo_del22" value="1">삭제 
													
												
											<?php endif; ?>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b3'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row3 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인3</br>
											&nbsp;<input type="text" class="form-control" id="apos3" name="apos3" placeholder="위치" value="<?=$row3['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink3" name="alink3" placeholder="링크" value="<?=$row3['alink']?>">&nbsp;PC<input type=file name=userfile3 size=30>
											<?= $row3['img1'] ?>
											<?php if($row3['img1']): ?>
												
													<input type="checkbox" id="photo_del3" name="photo_del3" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile33 size=30>
											<?= $row3['img2'] ?>
											<?php if($row3['img2']): ?>
												
													<input type="checkbox" id="photo_del33" name="photo_del33" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b4'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row4 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인4</br>
											&nbsp;<input type="text" class="form-control" id="apos4" name="apos4" placeholder="위치" value="<?=$row4['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink4" name="alink4" placeholder="링크" value="<?=$row4['alink']?>">&nbsp;PC<input type=file name=userfile4 size=30>
											<?= $row4['img1'] ?>
											<?php if($row4['img1']): ?>
												
													<input type="checkbox" id="photo_del4" name="photo_del4" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile44 size=30>
											<?= $row4['img2'] ?>
											<?php if($row4['img2']): ?>
												
													<input type="checkbox" id="photo_del44" name="photo_del44" value="1">삭제 
													
												
											<?php endif; ?></td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b5'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row5 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인5</br>
											&nbsp;<input type="text" class="form-control" id="apos5" name="apos5" placeholder="위치" value="<?=$row5['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink5" name="alink5" placeholder="링크" value="<?=$row5['alink']?>">&nbsp;PC<input type=file name=userfile5 size=30>
											<?= $row5['img1'] ?>
											<?php if($row5['img1']): ?>
												
													<input type="checkbox" id="photo_del5" name="photo_del5" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile55 size=30>
											<?= $row5['img2'] ?>
											<?php if($row5['img2']): ?>
												
													<input type="checkbox" id="photo_del55" name="photo_del55" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b6'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row6 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인6</br>
											&nbsp;<input type="text" class="form-control" id="apos6" name="apos6" placeholder="위치" value="<?=$row6['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink6" name="alink6" placeholder="링크" value="<?=$row6['alink']?>">&nbsp;PC<input type=file name=userfile6 size=30>
											<?= $row6['img1'] ?>

											<?php if($row6['img1']): ?>
												
													<input type="checkbox" id="photo_del6" name="photo_del6" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile66 size=30>
											<?= $row6['img2'] ?>
											<?php if($row6['img2']): ?>
												
													<input type="checkbox" id="photo_del66" name="photo_del66" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b7'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row7 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인7</br>
											&nbsp;<input type="text" class="form-control" id="apos7" name="apos7" placeholder="위치" value="<?=$row7['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink7" name="alink7" placeholder="링크" value="<?=$row7['alink']?>">&nbsp;PC<input type=file name=userfile7 size=30>
											<?= $row7['img1'] ?>
											<?php if($row7['img1']): ?>
												
													<input type="checkbox" id="photo_del7" name="photo_del7" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile77 size=30>
											<?= $row7['img2'] ?>
											<?php if($row7['img2']): ?>
												
													<input type="checkbox" id="photo_del77" name="photo_del77" value="1">삭제 
													
												
											<?php endif; ?>
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b8'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row8 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인8</br>
											&nbsp;<input type="text" class="form-control" id="apos8" name="apos8" placeholder="위치" value="<?=$row8['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink8" name="alink8" placeholder="링크" value="<?=$row8['alink']?>">&nbsp;PC<input type=file name=userfile8 size=30>
											<?= $row8['img1'] ?>

											<?php if($row8['img1']): ?>
												
													<input type="checkbox" id="photo_del8" name="photo_del8" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile88 size=30>
											<?= $row8['img2'] ?>
											<?php if($row8['img2']): ?>
												
													<input type="checkbox" id="photo_del88" name="photo_del88" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b9'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row9 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인9</br>
											&nbsp;<input type="text" class="form-control" id="apos9" name="apos9" placeholder="위치" value="<?=$row9['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink9" name="alink9" placeholder="링크" value="<?=$row9['alink']?>">&nbsp;PC<input type=file name=userfile9 size=30>
											<?= $row9['img1'] ?>

											<?php if($row9['img1']): ?>
												
													<input type="checkbox" id="photo_del9" name="photo_del9" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile99 size=30>
											<?= $row9['img2'] ?>
											<?php if($row9['img2']): ?>
												
													<input type="checkbox" id="photo_del99" name="photo_del99" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b10'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row10 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인10</br>
											&nbsp;<input type="text" class="form-control" id="apos10" name="apos10" placeholder="위치" value="<?=$row10['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink10" name="alink10" placeholder="링크" value="<?=$row10['alink']?>">&nbsp;PC<input type=file name=userfile10 size=30>
											<?= $row10['img1'] ?>

											<?php if($row10['img1']): ?>
												
													<input type="checkbox" id="photo_del10" name="photo_del10" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile100 size=30>
											<?= $row10['img2'] ?>
											<?php if($row10['img2']): ?>
												
													<input type="checkbox" id="photo_del100" name="photo_del100" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<?php
									    	$qry1 = "select * from banner_page where 1=1 && divi='sea' && area = 'b11'";
											$rst1 = mysql_query($qry1, $dbConn);
											$row11 = mysql_fetch_assoc($rst1);	
									    ?>
										<tr bgcolor=#FFFFFF>
											<td width=10% height=30 class="malgun">&nbsp;<i class="glyphicon glyphicon-folder-close"></i> 메인11</br>
											&nbsp;<input type="text" class="form-control" id="apos11" name="apos11" placeholder="위치" value="<?=$row11['pos']?>"></td>
											<td width=33% height=30 class="malgun">&nbsp;<input type="text" class="form-control" id="alink111" name="alink111" placeholder="링크" value="<?=$row11['alink']?>">&nbsp;PC<input type=file name=userfile110 size=30>
											<?= $row11['img1'] ?>

											<?php if($row11['img1']): ?>
												
													<input type="checkbox" id="photo_del101" name="photo_del101" value="1">삭제 
													
												
											<?php endif; ?>
											
											<br />&nbsp;Moblie<input type=file name=userfile1110 size=30>
											<?= $row11['img2'] ?>
											<?php if($row11['img2']): ?>
												
													<input type="checkbox" id="photo_del1101" name="photo_del1101" value="1">삭제 
													
												
											<?php endif; ?>
											
											</td>
											
										</tr>
										<tr bgcolor=#FFFFFF>
											
											<tr>
												<td colspan=4 height=35 bgcolor=#FFFFFF align=center class="malgun"><input type=submit value="저장" class="btn btn-primary btn-sm"></td>
											</tr>
										</tr>
									  </table>
									  <br><br>
									  
									  
										
										
						    </form>
						  
					</div> 
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

      
      