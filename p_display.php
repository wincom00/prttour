
<?php
    include "include/header.php";
    //include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
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
//print_r($_POST);
//exit;
	if ($mode1 =="save") {
		 for($i=0; $i<count((array)$seqNo); $i++)
		 {
			$s = $seqNo[$i];
			$pre_qry1 = "select * from main_display where divii='$displaydivi' && view_position = '$displaysel' && p_code = '$pcode[$s]'";
			$pre_rst1 = mysql_query($pre_qry1, $dbConn);
			$pre_num1 = $pre_rst1 ? mysql_num_rows($pre_rst1) : 0;
           // echo $pre_qry1;
			switch($displaysel){

				case "MAIN_BEST":
					$title = "[메인]BEST상품";
					break;
				case "MAIN_POP":
					$title = "[메인]인기상품";
					break;
				case "MAIN_DPOP":
					$title = "[메인]인기할인";
					break;
				case "MAIN_RE1":
					$title = "[메인]추천1";
					break;
				case "MAIN_RE2":
					$title = "[메인]추천2";
					break;
				case "MAIN_RE3":
					$title = "[메인]추천3";
					break;
				case "CUSTOM":
					$title = "[업체]추천";
					break;
				
				
			}

			if($pre_num1<=0)
		    {

				$qry1 = "insert into main_display (item_type,view_position,
																p_code,
																pos,
																m_link,
																w_title,
																divii) values (   'tour',
																				'$displaysel',
																				'$pcode[$s]',
																				'100',
																				'$mlink[$s]',
																				'$w_tit[$s]',
																				'$displaydivi')";
				$rst1 = mysql_query($qry1, $dbConn);

		   }
			 //echo $qry1;
		 }
		//exit;
		 Misc::jvAlert("저장 완료!!!");
		 
	}

	if ($mode1 =="usave") {
		//print_r($_post);
		
		//echo $divity;
		//exit;
		 for($i=0; $i<count((array)$seqNo); $i++)
		 {
			 $s = $num[$i];
			 $qry1 = "update main_display set pos = '$pos[$s]',m_link = '$mlink[$s]' ,p_name = '$p_name[$s]',w_title='$w_tit[$s]' where seq_no = '$seqNo[$i]' && divii = '$divity'";
			 $rst1 = mysql_query($qry1, $dbConn);
			 //echo $qry1;
			// exit;
		 }
		//exit;
		 Misc::jvAlert("업데이트 완료!!!");
		 
	}
    if($mode1 == "del")
	{
		  for($i=0; $i<count((array)$p_code); $i++)
		  {
			
			$qry1 = "delete from main_display where p_code = '$p_code[$i]' && view_position='".$flag."' && divii='$divity'";
			//echo $qry1;
		//	exit;
			$rst1 = mysql_query($qry1, $dbConn);

		  }
	}

	if ($flag == "") {
		 $flag=$displaysel;
         $divi1=$displaydivi;

	}
	if ($divi=="head") {
		$act1 = "class='active'";
		$tact1 = "in active";
	} else  {
		$act1 = "";
		$tact1 = "";
	}
	if ($divi=="west") {
		$act2 = "class='active'";
		$tact2 = "in active";
	} else  {
		$act2 = "";
		$tact2 = "";
	}
	if ($divi=="las") {
		$act3 = "class='active'";
		$tact3 = "in active";
	} else  {
		$act3 = "";
		$tact3 = "";
	}
	if ($divi=="das") {
		$act4 = "class='active'";
		$tact4 = "in active";
	} else  {
		$act4 = "";
		$tact4 = "";
	}
	$act5 = ""; $tact5 = ""; $act6 = ""; $tact6 = "";
	if ($divi=="ats") {
		$act5 = "class='active'";
		$tact5 = "in active";
	} else if ($divi=="sea") {
		$act6 = "class='active'";
		$tact6= "in active";
	}
	function printProduct($divi1){
		
		global $dbConn,$flag;

		if(empty($flag))
		{
			$flag = "MAIN_BEST";
		}
		if (empty($divi1)) {
       	   $qrydivi = "&& divii='head'";

		} else {
		   $qrydivi = "&& divii='$divi1'";
		}
        
		$qry1 = "select * from main_display where view_position = '$flag'  $qrydivi order by pos asc";
		$rst1 = mysql_query($qry1, $dbConn);
//echo $qry1;
		$num1 = 0;
		if (!$rst1) return;

		while($row1 = mysql_fetch_assoc($rst1)){

			$p_info = getProductMaster($row1['p_code']);

    

			switch($row1['view_position']){

				
                case "MAIN_BEST":
					$title = "[메인]BEST상품";
					break;
				case "MAIN_POP":
					$title = "[메인]인기상품";
					break;
				case "MAIN_DPOP":
					$title = "[메인]인기할인";
					break;
				case "MAIN_RE1":
					$title = "[메인]추천1";
					break;
				case "MAIN_RE2":
					$title = "[메인]추천2";
					break;
				case "MAIN_RE3":
					$title = "[메인]추천3";
					break;
				case "CUSTOM":
					$title = "[업체]추천";
					break;
                
			} 
            if ($row1['p_name'] == "") {
				$pnm=$p_info['p_name'];
			} else {
				$pnm=$row1['p_name'];
			}
			if ($row1['w_title'] == "") {
				$wtit=$p_info['w_title'];
			} else {
				$wtit=$row1['w_title'];
			}
			echo "<tr bgcolor=#FFFFFF>
					<td  align=center height=28><input type=checkbox name=p_code[] value={$row1['p_code']}></td>
					<td align=center><input type=hidden name=seqNo[] value={$row1['seq_no']}><input type=hidden name=num[] value=$num1><input type=text name=pos[] value={$row1['pos']} class='form-control'></td>
					<td  align=center>$title</td>
					<td  align=center>{$row1['p_code']}</td>
					<td  align=left>&nbsp;<input type=text name=p_name[] value='$pnm' size=50 class='form-control'><br/>&nbsp;<input type=text name=w_tit[] value='$wtit' size=50 class='form-control'> </td>
					<td  align=center><input type=text name=mlink[] value='{$row1['m_link']}' size=50 class='form-control'></td>
					</tr>";
			
			$num1++;
		}

		if($num1 == "0")
		{
			echo "<tr><td colspan=5 height=35 align=center bgcolor=#FFFFFF>진열된 상품없습니다.</td></tr>";
		}

	}
    
    
	
	
?>
     
	<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">홈페이지관련설정</a></li>
					<li><a href="#">홈페이지상품설정</a></li>
					<li><?=$pcap?></li>
				</ul>
			</div>
			<div class="row">
			    <ul class="nav nav-tabs">
				  <li <?=$act1?>><a data-toggle="tab" href="#home">본사</a></li>
				  <li <?=$act2?>><a data-toggle="tab" href="#west">미서부 LA</a></li>
				  <li <?=$act3?>><a data-toggle="tab" href="#las">라스베가스</a></li>
				  <li <?=$act4?>><a data-toggle="tab" href="#das">달라스</a></li>
				  <li <?=$act5?>><a data-toggle="tab" href="#ats">애틀란타</a></li>
				  <li <?=$act6?>><a data-toggle="tab" href="#sea">시애틀</a></li>
				</ul>
				<br>
				<div class="col-sm-12 col-md-12 tab-content">
				  <div id="home" class="tab-pane fade <?=$tact1?>">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=head" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
						<input type="hidden" name="mode" value="search">
						<input type=hidden name=divity  id=divity value="head">
						<table id="level4" class="txt_12" width="98%" align=center border="0" cellspacing="1" cellpadding="0" bgcolor=#cccccc>
							<tr>
								<td bgcolor=#FFFFFF height=30>&nbsp;
								
								   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_BEST&divi=head>[메인]BEST상품</a>	|			
									
							<!--	<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_POP>[메인]인기상품</a>     |-->
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_DPOP&divi=head>[메인]인기할인</a>   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE1&divi=head>[메인]추천1</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE2&divi=head>[메인]추천2</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE3&divi=head>[메인]추천3</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=CUSTOM&divi=head>[업체]추천</a>     |
								</td>
							</tr>
						</table>
					</form>
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=head" enctype="multipart/form-data" id="frmprod" name="frmprod"  method="post">
					<input type="hidden" name="mode1" id="mode1" value="usave">
				
					<input type=hidden name=divity id=divity value="head">
						   <table class="table table-striped table-bordered table-condensed js-prod1">
								<tbody>
								<tr>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-dsave">지정삭제</button> </td>
									<td class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1 js-usave" value="head">업데이트</button> </td>
								</tr> 
								</tbody>
							</table>
					
							<table id='ctable' class="table table-striped table-bordered mediaTable js-productListTable">
								<thead>
									<tr>
										<th width=10% align=center><input type=checkbox id="selectAll" ></th>
										<th width=10% align=center>순서</th>
										<th width=20% align=center>위치</th>
										<th width=20% align=center>상품코드</th>
										<th width=30% align=center>상품명</th>
										<th width=30% align=center>사용자링크</th>
									</tr>
								</thead> 
								<tbody>
									<?php echo printProduct('head'); ?>
								</tbody>
							</table>
					</form>
				  </div>
				  <div id="west" class="tab-pane fade <?=$tact2?>">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=west" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
						<input type="hidden" name="mode" value="search">
						<input type=hidden name=divity id=divity value="west">
						<table id="level4" class="txt_12" width="98%" align=center border="0" cellspacing="1" cellpadding="0" bgcolor=#cccccc>
							<tr>
								<td bgcolor=#FFFFFF height=30>&nbsp;
								
								   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_BEST&divi=west>[메인]BEST상품</a>	|			
									
							<!--	<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_POP>[메인]인기상품</a>     |-->
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_DPOP&divi=west>[메인]인기할인</a>   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE1&divi=west>[메인]추천1</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE2&divi=west>[메인]추천2</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE3&divi=west>[메인]추천3</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=CUSTOM&divi=west>[업체]추천</a>     |
								</td>
							</tr>
						</table>
					</form>
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=west" enctype="multipart/form-data" id="frmprod1" name="frmprod1"  method="post">
					<input type="hidden" name="mode1" id="mode1" value="usave">
					<input type=hidden name=divity id=divity value="west">
					
					
					
						   <table class="table table-striped table-bordered table-condensed js-prod1">
								<tbody>
								<tr>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-dsave1">지정삭제</button> </td>
									<td class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1 js-usave" value="west">업데이트</button> </td>
								</tr> 
								</tbody>
							</table>
					
							<table id='ctable' class="table table-striped table-bordered mediaTable js-productListTable">
								<thead>
									<tr>
										<th width=10% align=center><input type=checkbox id="selectAll" ></th>
										<th width=10% align=center>순서</th>
										<th width=20% align=center>위치</th>
										<th width=20% align=center>상품코드</th>
										<th width=30% align=center>상품명</th>
										<th width=30% align=center>사용자링크</th>
									</tr>
								</thead> 
								<tbody>
									<?php echo printProduct('west'); ?>
								</tbody>
							</table>
					</form>
				  </div>
				  <div id="las" class="tab-pane fade <?=$tact3?>">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=las" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
						<input type="hidden" name="mode" value="search">
						<input type=hidden name=divity id=divity value="las">
						<table id="level4" class="txt_12" width="98%" align=center border="0" cellspacing="1" cellpadding="0" bgcolor=#cccccc>
							<tr>
								<td bgcolor=#FFFFFF height=30>&nbsp;
								
								   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_BEST&divi=las>[메인]BEST상품</a>	|			
									
							<!--	<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_POP>[메인]인기상품</a>     |-->
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_DPOP&divi=las>[메인]인기할인</a>   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE1&divi=las>[메인]추천1</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE2&divi=las>[메인]추천2</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE3&divi=las>[메인]추천3</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=CUSTOM&divi=las>[업체]추천</a>     |
								</td>
							</tr>
						</table>
					</form>
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=das" enctype="multipart/form-data" id="frmprod2" name="frmprod2" method="post">
					<input type="hidden" name="mode1" id="mode1" value="usave">
					<input type=hidden name=divity id=divity value="las">
						   <table class="table table-striped table-bordered table-condensed js-prod1">
								<tbody>
								<tr>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-dsave2">지정삭제</button> </td>
									<td class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1 js-usave" value="las">업데이트</button> </td>
								</tr> 
								</tbody>
							</table>
					
							<table id='ctable' class="table table-striped table-bordered mediaTable js-productListTable">
								<thead>
									<tr>
										<th width=10% align=center><input type=checkbox id="selectAll" ></th>
										<th width=10% align=center>순서</th>
										<th width=20% align=center>위치</th>
										<th width=20% align=center>상품코드</th>
										<th width=30% align=center>상품명</th>
										<th width=30% align=center>사용자링크</th>
									</tr>
								</thead> 
								<tbody>
									<?php echo printProduct('las'); ?>
								</tbody>
							</table>
					</form>
				  </div>
				  <div id="das" class="tab-pane fade <?=$tact4?>">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=das" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
						<input type="hidden" name="mode" value="search">
						<input type=hidden name=divity id=divity value="das">
						<table id="level4" class="txt_12" width="98%" align=center border="0" cellspacing="1" cellpadding="0" bgcolor=#cccccc>
							<tr>
								<td bgcolor=#FFFFFF height=30>&nbsp;
								
								   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_BEST&divi=das>[메인]BEST상품</a>	|			
									
							<!--	<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_POP>[메인]인기상품</a>     |-->
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_DPOP&divi=das>[메인]인기할인</a>   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE1&divi=das>[메인]추천1</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE2&divi=das>[메인]추천2</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE3&divi=das>[메인]추천3</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=CUSTOM&divi=das>[업체]추천</a>     |
								</td>
							</tr>
						</table>
					</form>
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=das" enctype="multipart/form-data" id="frmprod3" name="frmprod3" method="post">
					<input type="hidden" name="mode1" id="mode1" value="usave">
					<input type=hidden name=divity id=divity value="das">
						   <table class="table table-striped table-bordered table-condensed js-prod1">
								<tbody>
								<tr>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-dsave3">지정삭제</button> </td>
									<td class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1 js-usave" value="das">업데이트</button> </td>
								</tr> 
								</tbody>
							</table>
					
							<table id='ctable' class="table table-striped table-bordered mediaTable js-productListTable">
								<thead>
									<tr>
										<th width=10% align=center><input type=checkbox id="selectAll" ></th>
										<th width=10% align=center>순서</th>
										<th width=20% align=center>위치</th>
										<th width=20% align=center>상품코드</th>
										<th width=30% align=center>상품명</th>
										<th width=30% align=center>사용자링크</th>
									</tr>
								</thead> 
								<tbody>
									<?php echo printProduct('das'); ?>
								</tbody>
							</table>
					</form>
				  </div>
				  <div id="ats" class="tab-pane fade <?=$tact5?>">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=ats" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
						<input type="hidden" name="mode" value="search">
						<input type=hidden name=divity id=divity value="ats">
						<table id="level4" class="txt_12" width="98%" align=center border="0" cellspacing="1" cellpadding="0" bgcolor=#cccccc>
							<tr>
								<td bgcolor=#FFFFFF height=30>&nbsp;
								
								   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_BEST&divi=ats>[메인]BEST상품</a>	|			
									
							<!--	<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_POP>[메인]인기상품</a>     |-->
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_DPOP&divi=ats>[메인]인기할인</a>   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE1&divi=ats>[메인]추천1</a>    |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE2&divi=ats>[메인]추천2</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE3&divi=ats>[메인]추천3</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=CUSTOM&divi=ats>[업체]추천</a>     |
								</td>
							</tr>
						</table>
					</form>
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=ats" enctype="multipart/form-data" id="frmprod4" name="frmprod4"  method="post">
					<input type="hidden" name="mode1" id="mode1" value="usave">
					<input type=hidden name=divity id=divity value="ats">
						   <table class="table table-striped table-bordered table-condensed js-prod1">
								<tbody>
								<tr>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-dsave4">지정삭제</button> </td>
									<td class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1 js-usave" value="ats">업데이트</button> </td>
								</tr> 
								</tbody>
							</table>
					
							<table id='ctable' class="table table-striped table-bordered mediaTable js-productListTable">
								<thead>
									<tr>
										<th width=10% align=center><input type=checkbox id="selectAll" ></th>
										<th width=10% align=center>순서</th>
										<th width=20% align=center>위치</th>
										<th width=20% align=center>상품코드</th>
										<th width=30% align=center>상품명</th>
										<th width=30% align=center>사용자링크</th>
									</tr>
								</thead> 
								<tbody>
									<?php echo printProduct('ats'); ?>
								</tbody>
							</table>
					</form>
				  </div>
				  <div id="sea" class="tab-pane fade <?=$tact6?>">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=sea" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
						<input type="hidden" name="mode" value="search">
						<input type=hidden name=divity id=divity value="sea">
						<table id="level4" class="txt_12" width="98%" align=center border="0" cellspacing="1" cellpadding="0" bgcolor=#cccccc>
							<tr>
								<td bgcolor=#FFFFFF height=30>&nbsp;
								
								   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_BEST&divi=sea>[메인]BEST상품</a>	|			
									
							<!--	<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_POP>[메인]인기상품</a>     |-->
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_DPOP&divi=sea>[메인]인기할인</a>   
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE1&divi=sea>[메인]추천1</a>    |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE2&divi=sea>[메인]추천2</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=MAIN_RE3&divi=sea>[메인]추천3</a>     |
								<a href=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=CUSTOM&divi=sea>[업체]추천</a>     |
								</td>
							</tr>
						</table>
					</form>
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&flag=<?=$flag?>&divity=sea" enctype="multipart/form-data" id="frmprod5" name="frmprod5"  method="post">
					<input type="hidden" name="mode1" id="mode1" value="usave">
					<input type=hidden name=divity id=divity value="sea">
						   <table class="table table-striped table-bordered table-condensed js-prod1">
								<tbody>
								<tr>
									
									<td width=5%  class="conttd"><button type='button' class="btn btn-primary btn-sm btn1 js-dsave5">지정삭제</button> </td>
									<td class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1 js-usave" value="sea">업데이트</button> </td>
								</tr> 
								</tbody>
							</table>
					
							<table id='ctable' class="table table-striped table-bordered mediaTable js-productListTable">
								<thead>
									<tr>
										<th width=10% align=center><input type=checkbox id="selectAll" ></th>
										<th width=10% align=center>순서</th>
										<th width=20% align=center>위치</th>
										<th width=20% align=center>상품코드</th>
										<th width=30% align=center>상품명</th>
										<th width=30% align=center>사용자링크</th>
									</tr>
								</thead> 
								<tbody>
									<?php echo printProduct('sea'); ?>
								</tbody>
							</table>
					</form>
				  </div>
				</div><!-- -->
			</div>                
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
			//pt.initProductList()
			
			
			var oTable = $('#ctable').dataTable({
				stateSave: true,
				pageLength: 100,
				"order": [[ 2, "asc" ]]
			});

			var allPages = oTable.fnGetNodes();

			$('body').on('click', '#selectAll', function () {
				if ($(this).hasClass('allChecked')) {
					$('input[type="checkbox"]', allPages).prop('checked', false);
				} else {
					$('input[type="checkbox"]', allPages).prop('checked', true);
				}
				$(this).toggleClass('allChecked');
			})
			$(".dataTables_length").css({ "display" :"none" });
			$('.js-usave').click(function(e){
				if (confirm("업데이트 하시겠습니까?"))
				{
					$("#mode1").val("usave");
					
				    $("#frmprod").submit();
				}
				

			});

			$('.js-dsave').click(function(e){
				if (confirm("삭제 하시겠습니까?"))
				{
					//$("#mode1").val("del");
					document.forms['frmprod']['mode1'].value = "del";
				    $("#frmprod").submit();
				}
				

			});
			$('.js-dsave1').click(function(e){
				if (confirm("삭제 하시겠습니까?"))
				{
					//$("#mode1").val("del");
					document.forms['frmprod1']['mode1'].value = "del";
				    $("#frmprod1").submit();
				}
				

			});
			$('.js-dsave2').click(function(e){
				if (confirm("삭제 하시겠습니까?"))
				{
					//$("#mode1").val("del");
					document.forms['frmprod2']['mode1'].value = "del";
				    $("#frmprod2").submit();
				}
				

			});
			$('.js-dsave3').click(function(e){
				if (confirm("삭제 하시겠습니까?"))
				{
					//$("#mode1").val("del");
					document.forms['frmprod3']['mode1'].value = "del";
				    $("#frmprod3").submit();
				}
				

			});
			$('.js-dsave4').click(function(e){
				if (confirm("삭제 하시겠습니까?"))
				{
					//$("#mode1").val("del");
					document.forms['frmprod4']['mode1'].value = "del";
				    $("#frmprod4").submit();
				}
				

			});
			$('.js-dsave5').click(function(e){
				if (confirm("삭제 하시겠습니까?"))
				{
					//$("#mode1").val("del");
					document.forms['frmprod5']['mode1'].value = "del";
				    $("#frmprod5").submit();
				}
				

			});
			
			
		});
		
			
		
	</script>
    </body>
</html>
