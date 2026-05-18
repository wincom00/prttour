<?php
    include "include/header.php";
    //include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
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

	if ($mode == "scheduleSave") {
		include "inc_prodscsave.php";
	}
 
	if ($mode == "save") {
		include "inc_prodmsave.php";
	}

	$_listPage = max(1, intval($_REQUEST['page'] ?? 1));
	$_listSortCol = isset($_REQUEST['sort_col']) && $_REQUEST['sort_col'] !== '' ? $_REQUEST['sort_col'] : 'region';
	$_listSortDir = isset($_REQUEST['sort_dir']) && strtolower($_REQUEST['sort_dir']) === 'desc' ? 'desc' : 'asc';
	$_listSearch = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
	$_listQuery = http_build_query(array(
		'division' => $division,
		'pdx' => $pdx,
		'sub' => $sub,
		'ty' => $ty,
		'page' => $_listPage,
		'sort_col' => $_listSortCol,
		'sort_dir' => $_listSortDir,
		'search' => $_listSearch
	));
    
	if($mode == "del")
	{
		$qry1 = "delete from product_limit where p_code like '%$pcode%'";
		$rst1 = mysql_query($qry1,$dbConn); 

		$qry1 = "delete from product_details_local where p_code like '%$pcode%'";
		$rst1 = mysql_query($qry1,$dbConn);

		$qry1 = "delete from product_pick where p_code like '%$pcode%'";
		$rst1 = mysql_query($qry1,$dbConn);
		
		$qry1 = "delete from product_details where p_code like '%$pcode%'";
		$rst1 = mysql_query($qry1,$dbConn);

		$qry1 = "delete from product_master where p_code like '%$pcode%'";  
		$rst1 = mysql_query($qry1,$dbConn);

		Misc::jvAlert("삭제했습니다.","");
		echo "<meta http-equiv='refresh' content='0;url=./base_product.php?$_listQuery'>";	
		exit;	
		
	}


	$prodInfo = getProductMaster(trim($pcode));
	
	if ($Mode == 'copy') {
		
			include "inc_cprodmsave.php";

	}
	$lvcode2 = substr($prodInfo['c_code1'],3,2);
	if ($ty == 1) {
        $pcap = "로컬상품";
	} else if ($ty == 2) {
        $pcap = "인바운드";
	
	} else if ($ty == 4) {
        $pcap = "인센티브";
	} else if ($ty == 5) {
        $pcap = "아웃바운드";
	}
	$v_info=getinfo_dbMember($user_dbinfo['userid']);
	$_editorBaseUrl = 'https://' . (defined('FTP_PRIMARY_DOMAIN') ? FTP_PRIMARY_DOMAIN : 'myprt.org') . '/';

?>
	<div id="contentwrapper" class="productDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">상품관리</a></li>
					<li><a href="#">상품등록</a></li>
					<li><?= $pcap ?></li>
				</ul>
			</div>

			<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>&pcode=<?=$pcode?>" name="frmproduct" id="frmproduct" method="post" Enctype="multipart/form-data" onSubmit="return chksave()">
				<input type="hidden" name="mode" id="mode" value="save">
				<input type="hidden" name="pcode" value="<?= $pcode ?>">
				<input type="hidden" name="currency" value="USD">
				<input type="hidden" name="page" value="<?= htmlspecialchars($_listPage, ENT_QUOTES) ?>">
				<input type="hidden" name="sort_col" value="<?= htmlspecialchars($_listSortCol, ENT_QUOTES) ?>">
				<input type="hidden" name="sort_dir" value="<?= htmlspecialchars($_listSortDir, ENT_QUOTES) ?>">
				<input type="hidden" name="search" value="<?= htmlspecialchars($_listSearch, ENT_QUOTES) ?>">
				<div class="row">
					<div class="col-sm-6 col-sm-offset-6 text-right">
						<button type="submit" class="btn btn-xs btn-default js-formSave">상품저장</button>
						<?php if ($pcode!="") { ?>
							<button type="button" class="btn btn-xs btn-default js-formDelete" OnClick="javascript:pdel()">상품삭제</button>
							<button type="button" class="btn btn-xs btn-default js-openSchedule" data-toggle="modal" data-target=".js-openScheduleModal">일정표작성</button>
						<?php } ?>
					</div>
				</div>
				<br />
				<table class="table table-bordered table-condensed ptTable formDetail">
					<tbody>
					    <tr>
							<td colspan="2"  class="active text-center formHeader">상품구분</td>
							<td colspan="10" align=left bgcolor=#FFFFFF>
								&nbsp;<input type="radio" name="mty" value = "S" <?php if($prodInfo['m_type'] == "S") echo "checked"; ?>> 단일상품 &nbsp;&nbsp;
							  <input type="radio" name="mty" value = "D" <?php if($prodInfo['m_type'] == "D") echo "checked"; ?>> 복합상품
							</td>
						</tr>
						<?php if ($ty == 1) { ?>
						<tr>
							<td colspan="2" class="active text-center formHeader">메인가이드상품체크</td>
							<td colspan="10" align=left bgcolor=#FFFFFF>
								&nbsp;<input type="hidden" name="m_guidechk" value="">
								<input type="checkbox" name="m_guidechk" value="V" <?php if($prodInfo['m_guidechk'] == "V") echo "checked"; ?>>
								<span style="color:#d9534f;">메인가이드상품 로컬상품만 사용</span>
							</td>
						</tr>
						<?php } ?>
						<?php if ($ty == 2) { ?>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품분류명칭</td>
							<td colspan="10" align=left bgcolor=#FFFFFF>
								&nbsp;<input type="text" name="p_cat_name" class="form-control" style="display:inline-block;width:auto;" value="<?= htmlspecialchars($prodInfo['p_cat_name'] ?? '', ENT_QUOTES) ?>">
							</td>
						</tr>
						<?php } ?>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품분류</td>
							<td colspan="8" class="form-inline">
								<table border=0>
										<tr>
										<?php
											$cr_qry1 = "select * from code_base where lvcode1 = 'K01' && lvcode2 <> '00' && lvcode3 = '00' && lvcode4 = '00' order by lvcode2 asc";
											$cr_rst1 = mysql_query($cr_qry1);

											$cr_num1 = 1;

											while($cr_row1 = mysql_fetch_assoc($cr_rst1)):

											//$area_name = codebasename($cr_row1[lvcode1]);
											$ty0 = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3'].$cr_row1['lvcode4'];
											$ty_value_check = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3'].$cr_row1['lvcode4']."/";

										?>
											<td width=130><input type=checkbox name=ty0[] value="<?= $ty0 ?>" <?php if(strstr($prodInfo['t_code1'],$ty_value_check)) echo "checked"; ?>> <?= $cr_row1['comment']; ?></td>
										<?php
											if($cr_num1%7 == 0)
											{
												echo "</tr><tr>";
											}
											$cr_num1++;
											endwhile;
										?>
										 
									</table>
							</td>
							<td colspan="2" class="form-inline">
								
								<select class="form-control " name="ty2" id="ty2">
									<option value="">분류선택2</option>
									<?=printBaseCode_first('K02',$prodInfo['t_code2'])?>
								</select>
							
							</td>
							
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품지역</td>
							<td colspan="4" class="form-inline">
								<select class="form-control fst1" name="area1" id="area1">
									<option value="">지역선택
									<?=printBaseCode_first('T01',$prodInfo['c_code1'])?>
								</select>
								
								<select class="form-control fst2" name="area2" id="area2">
									<option value="">지역선택2</option>
									<?=printBaseCode_second('T01',$lvcode2,$prodInfo['c_code2'])?>
								</select>
							
							</td>
							<td colspan="1" class="active text-center formHeader">관리지사선택</td>
							<td colspan="2" class="form-inline">
								
								
									<?php
											$cr_qry1 = "select * from code_base where lvcode1 = 'D02' && lvcode2 <> '00' && lvcode3 = '00'  order by lvcode2 asc";
											$cr_rst1 = mysql_query($cr_qry1);

											$cr_num1 = 1;

											while($cr_row1 = mysql_fetch_assoc($cr_rst1)):

											//$area_name = codebasename($cr_row1[lvcode1]);
											$tour_area_value0 = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3'];
											$tour_area_value_check = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3']."/";

										?>
											<input type=checkbox name=dept2[] value="<?= $tour_area_value0 ?>" <?php if(strstr($prodInfo['m_dept'],$tour_area_value_check)) echo "checked"; ?>> <?= $cr_row1['comment']; ?>
										<?php
											
											endwhile;
										?>

								
							</td>
								
							</td>
							<td colspan="1" class="active text-center formHeader">판매지사선택</td>
							<td colspan="2" class="form-inline">
								<?php
											$cr_qry1 = "select * from code_base where lvcode1 = 'D02' && lvcode2 <> '00' && lvcode3 = '00'  order by lvcode2 asc";
											$cr_rst1 = mysql_query($cr_qry1);

											$cr_num1 = 1;

											while($cr_row1 = mysql_fetch_assoc($cr_rst1)):

											//$area_name = codebasename($cr_row1[lvcode1]);
											$tour_area_value0 = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3'];
											$tour_area_value_check = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3']."/";

										?>
											<input type=checkbox name=dept1[] value="<?= $tour_area_value0 ?>" <?php if(strstr($prodInfo['p_dept'],$tour_area_value_check)) echo "checked"; ?>> <?= $cr_row1['comment']; ?>
										<?php
											
											endwhile;
										?>
							</td>
						</tr>
						<tr>
								<td colspan="2" class="active text-center formHeader">리스트지정1</td>
								<td colspan="10">
									<table border=0>
										<tr>
										<?php
											$cr_qry1 = "select * from code_base where lvcode1 = 'T01' && lvcode2 <> '00' && lvcode3 <> '00' && lvcode4 <> '00' order by lvcode4 asc";
											$cr_rst1 = mysql_query($cr_qry1);

											$cr_num1 = 1;

											while($cr_row1 = mysql_fetch_assoc($cr_rst1)):

											//$area_name = codebasename($cr_row1[lvcode1]);
											$tour_area_value0 = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3'].$cr_row1['lvcode4'];
											$tour_area_value_check = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3'].$cr_row1['lvcode4']."/";

										?>
											<td width=130><input type=checkbox name=tour_area0[] value="<?= $tour_area_value0 ?>" <?php if(strstr($prodInfo['tour_area_value0'],$tour_area_value_check)) echo "checked"; ?>> <?= $cr_row1['comment']; ?></td>
										<?php
											if($cr_num1%7 == 0)
											{
												echo "</tr><tr>";
											}
											$cr_num1++;
											endwhile;
										?>
										 
									</table>
							
							</td>
				        </tr>
						<tr>
								<td colspan="2" class="active text-center formHeader">리스트지정2</td>
								<td colspan="10">
									<table border=0>
										<tr>
										<?php
											$cr_qry1 = "select * from code_base where lvcode1 = 'S02' && lvcode2 <> '00' && lvcode3 = '00' && lvcode4 = '00' order by lvcode2 asc";
											$cr_rst1 = mysql_query($cr_qry1);

											$cr_num1 = 1;

											while($cr_row1 = mysql_fetch_assoc($cr_rst1)):

											//$area_name = codebasename($cr_row1[lvcode1]);
											$tour_area_value = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3'].$cr_row1['lvcode4'];
											$tour_area_value_check = $cr_row1['lvcode1'].$cr_row1['lvcode2'].$cr_row1['lvcode3'].$cr_row1['lvcode4']."/";

										?>
											<td width=130><input type=checkbox name=tour_area1[] value="<?= $tour_area_value ?>" <?php if(strstr($prodInfo['tour_area_value'],$tour_area_value_check)) echo "checked"; ?>> <?= $cr_row1['comment']; ?></td>
										<?php
											if($cr_num1%7 == 0)
											{
												echo "</tr><tr>";
											}
											$cr_num1++;
											endwhile;
										?>
										 
									</table>
							
							</td>
				        </tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">이벤트설정</td>
							<td colspan="10" width='10%'>
							   <table border=0>
										<tr>
										<td width=130><input type=checkbox name=tour_area1[] value="S02030000" <?php if(strstr($prodInfo['tour_area_value'],'S02030000')) echo "checked"; ?>> 프리미엄상품
										</td>
										
										<td width=130><input type=checkbox name=tour_area1[] value="S02370000" <?php if(strstr($prodInfo['tour_area_value'],'S02370000')) echo "checked"; ?>> 동부기획전
										</td>
										
										<td width=130><input type=checkbox name=tour_area1[] value="S02380000" <?php if(strstr($prodInfo['tour_area_value'],'S02380000')) echo "checked"; ?>> 서부기획전
										</td>
										<tr/>
							  </table>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">메인 인기지역</td>
							<td colspan="10" width='10%'>
										<select class="form-control" name="psel">
											<option value="">인기지역선택
											<?=printBaseCode_first('S04',$prodInfo['p_cate'])?>
										</select> 
						
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품구성</td>
							<td colspan="10">
						
						<?php
							$d_qry1 = "select * from product_details_local where p_code = '$pcode' order by day,position asc";
							$d_rst1 = mysql_query($d_qry1);
							$d = 1;
							while($d_row1 = mysql_fetch_assoc($d_rst1)):
								$sproductInfo = getProductMaster($d_row1['local_code']);
								if ($d_row1['local_code'] !="") {
							
						?>
									<div class="well well-sm thinMargin" role="alert"><strong><?=$d_row1['day']?>일차: </strong>[<?=$d_row1['local_code']?>] <?=$sproductInfo['p_name']?></div>
								
						<?php
							    }
							endwhile;
						?>
						
							</td>
						</tr>
						
						
						
						<tr>
							<td colspan="2" class="active text-center formHeader">상품코드</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="prodCode">상품코드</label>
									<input type="text" class="form-control" id="prodCode" name="prodCode" placeholder="자동생성 및 수정가능" value='<?=$prodInfo['p_code']?>'>
								</div>
							</td>
							<td colspan="2" class="active text-center formHeader">상품명</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="prodName">상품명</label>
									<input type="text" class="form-control" id="prodName" name="prodName" placeholder="상품명" value='<?=$prodInfo['p_name']?>'>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품소유사</td>
							
							<td colspan="4">
								<select class="randcomp" name="p_own" id="p_own">
									<option value="">소유사(지사) 선택</option>
									<option value="purun" selected>푸른투어-본사</option>
									<?=printRandSelect($prodInfo['p_own'])?>
								</select>
							</td>
							<td colspan="2" class="active text-center formHeader">여행기간</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="tourLength">여행기간</label>
									<input type="text" class="form-control js-tourLength" id="tourLength" name="tourLength" placeholder="여행기간" value="<?=$prodInfo['p_day']?>">
								</div>
							</td>
						</tr>
						<tr id="rinfo">
							
							<td colspan="2" class="active text-center formHeader">추천 RATE(EX: 1~5)</td>
							<td colspan="4">
								
									<input type="text" class="inpubase sm1" id="rrate" name="rrate" placeholder="추천 RATE" value='<?=$prodInfo['r_rate']?>'>
								
								
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">탑승지설정 &nbsp;<button type="button" class="btn btn-default btn-xs js-addPickup"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
							<td colspan="10">
							   <?php
									$qry1 = "select * from product_pick where p_code = '{$prodInfo['p_code']}'";
									
									$rst1 = mysql_query($qry1);
									$cnt = mysql_num_rows($rst1);
									while($pick_row = mysql_fetch_assoc($rst1)):
						
							   ?>
									<div class="form-inline js-pickupSet">
										<select class="form-control pickarea" name="pickLoc[]">
											<option value="">픽업지역선택</option>
											<?=pickBaseCode($pick_row['pick_area'])?>
										</select>
										<select class="form-control picktt" name="pickTime[]">
											<option value="">픽업시간선택</option>
											<?=pickBaseCodeSencond($pick_row['pick_area'],$pick_row['pick_time'])?>
										</select>
										<button type="button" class="btn btn-default btn-xs js-removePickup"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
									</div>
							   <?php
									
							      endwhile;
							  
							      if ($cnt == 0) {  	   
						       ?>
									<div class="form-inline js-pickupSet">
										<select class="form-control pickarea" name="pickLoc[]">
											<option value="">픽업지역선택</option>
											<?=pickBaseCode('')?>
										</select>
										<select class="form-control picktt" name="pickTime[]">
											<option value="">픽업시간선택</option>
											
										</select>
										<button type="button" class="btn btn-default btn-xs js-removePickup hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
									</div>
							   <?php

								  }
						       ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">투어정원</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="maxPerCar">투어정원</label>
									<input type="text" class="form-control" id="maxPerCar" name="maxPerCar" placeholder="여행기간" value="<?=$prodInfo['p_cnt']?>">
								</div>
							</td>
							<td colspan="2" class="active text-center formHeader">최소출발인원</td>
							<td colspan="4">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="minViableNum">최소출발인원</label>
									<input type="text" class="form-control" id="minViableNum" name="minViableNum" placeholder="최소출발인원" value="<?=$prodInfo['p_scnt']?>">
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="12" class="active text-center formHeader fullWidth">구분별 상품가격</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">당일</td>
							<td colspan="10">
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<label for="displayAdultPrice0">표시용 성인가격</label>
										
												<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayAdultPrice0" name="displayAdultPrice0" placeholder="표시용 성인가격" value="<?=$prodInfo['price_0dadult']?>">
												</div>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="displayChildPrice0">표시용 어린이가격</label>
												<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayChildPrice0" name="displayChildPrice0" placeholder="표시용 어린이가격" value="<?=$prodInfo['price_0dchild']?>">
												</div>

											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="regularAdultPrice0">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice0" name="regularAdultPrice0" placeholder="일반 성인가격" value="<?=$prodInfo['price_0adult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="regularChildPrice0">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice0" name="regularChildPrice0" placeholder="일반 어린이가격" value="<?=$prodInfo['price_0child']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="partnerAdultPrice0">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice0" name="partnerAdultPrice0" placeholder="협력사 성인가격" value="<?=$prodInfo['price_0cadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label for="partnerChildPrice0">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice0" name="partnerChildPrice0" placeholder="협력사 어린이가격" value="<?=$prodInfo['price_0cchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-9">
											<div class="form-group thinMargin">
												<label for="OutAdultPrice0">아웃바운드기준가격</label>
												<input type="text" class="form-control" id="OutAdultPrice0" name="OutAdultPrice0" placeholder="아웃바운드기준가격" value="<?=$prodInfo['oprice_0cadult']?>">
											</div>
										</div>
										
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">1인1실</td>
							<td colspan="10">
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayAdultPrice1" name="displayAdultPrice1" placeholder="표시용 성인가격" value="<?=$prodInfo['price_1dadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayChildPrice1" name="displayChildPrice1" placeholder="표시용 어린이가격" value="<?=$prodInfo['price_1dchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="regularAdultPrice1" name="regularAdultPrice1" placeholder="일반 성인가격" value="<?=$prodInfo['price_1adult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="regularChildPrice1" name="regularChildPrice1" placeholder="일반 어린이가격" value="<?=$prodInfo['price_1child']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="partnerAdultPrice1" name="partnerAdultPrice1" placeholder="협력사 성인가격" value="<?=$prodInfo['price_1cadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="partnerChildPrice1" name="partnerChildPrice1" placeholder="협력사 어린이가격" value="<?=$prodInfo['price_1cchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-9">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="OutAdultPrice1" name="OutAdultPrice1" placeholder="아웃바운드기준가격" value="<?=$prodInfo['oprice_1cadult']?>">
											</div>
										</div>
										
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">2인1실</td>
							<td colspan="10">
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayAdultPrice2" name="displayAdultPrice2" placeholder="표시용 성인가격" value="<?=$prodInfo['price_2dadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayChildPrice2" name="displayChildPrice2" placeholder="표시용 어린이가격" value="<?=$prodInfo['price_2dchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPrice2">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice2" name="regularAdultPrice2" placeholder="일반 성인가격" value="<?=$prodInfo['price_2adult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPrice2">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice2" name="regularChildPrice2" placeholder="일반 어린이가격" value="<?=$prodInfo['price_2child']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPrice2">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice2" name="partnerAdultPrice2" placeholder="협력사 성인가격" value="<?=$prodInfo['price_2cadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPrice2">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice2" name="partnerChildPrice2" placeholder="협력사 어린이가격" value="<?=$prodInfo['price_2cchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-9">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="OutAdultPrice2" name="OutAdultPrice2" placeholder="아웃바운드기준가격" value="<?=$prodInfo['oprice_2cadult']?>">
											</div>
										</div>
										
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">3인1실</td>
							<td colspan="10">
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayAdultPrice3" name="displayAdultPrice3" placeholder="표시용 성인가격"  value="<?=$prodInfo['price_3dadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayChildPrice3" name="displayChildPrice3" placeholder="표시용 어린이가격" value="<?=$prodInfo['price_3dchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPrice3">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice3" name="regularAdultPrice3" placeholder="일반 성인가격" value="<?=$prodInfo['price_3adult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPrice3">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice3" name="regularChildPrice3" placeholder="일반 어린이가격" value="<?=$prodInfo['price_3child']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPrice3">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice3" name="partnerAdultPrice3" placeholder="협력사 성인가격" value="<?=$prodInfo['price_3cadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPrice3">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice3" name="partnerChildPrice3" placeholder="협력사 어린이가격" value="<?=$prodInfo['price_3cchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-9">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="OutAdultPrice3" name="OutAdultPrice3" placeholder="아웃바운드기준가격" value="<?=$prodInfo['oprice_3cadult']?>">
											</div>
										</div>
										
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">4인1실</td>
							<td colspan="10">
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayAdultPrice4" name="displayAdultPrice4" placeholder="표시용 성인가격" value="<?=$prodInfo['price_4dadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayChildPrice4" name="displayChildPrice4" placeholder="표시용 어린이가격"  value="<?=$prodInfo['price_4dchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPrice4">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice4" name="regularAdultPrice4" placeholder="일반 성인가격" value="<?=$prodInfo['price_4adult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPrice4">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice4" name="regularChildPrice4" placeholder="일반 어린이가격" value="<?=$prodInfo['price_4child']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPrice4">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice4" name="partnerAdultPrice4" placeholder="협력사 성인가격" value="<?=$prodInfo['price_4cadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPrice4">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice4" name="partnerChildPrice4" placeholder="협력사 어린이가격" value="<?=$prodInfo['price_4cchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-9">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="OutAdultPrice4" name="OutAdultPrice4" placeholder="아웃바운드기준가격" value="<?=$prodInfo['oprice_4cadult']?>">
											</div>
										</div>
										
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">5인1실</td>
							<td colspan="10">
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayAdultPrice5" name="displayAdultPrice5" placeholder="표시용 성인가격" value="<?=$prodInfo['price_5dadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayChildPrice5" name="displayChildPrice5" placeholder="표시용 어린이가격" value="<?=$prodInfo['price_5dchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPrice5">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPrice5" name="regularAdultPrice5" placeholder="일반 성인가격" value="<?=$prodInfo['price_5adult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPrice5">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPrice5" name="regularChildPrice5" placeholder="일반 어린이가격" value="<?=$prodInfo['price_5child']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPrice5">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPrice5" name="partnerAdultPrice5" placeholder="협력사 성인가격" value="<?=$prodInfo['price_5cadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPrice5">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPrice5" name="partnerChildPrice5" placeholder="협력사 어린이가격" value="<?=$prodInfo['price_5cchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-9">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="OutAdultPrice5" name="OutAdultPrice5" placeholder="아웃바운드기준가격" value="<?=$prodInfo['oprice_5cadult']?>">
											</div>
										</div>
										
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">편도버스이용</td>
							<td colspan="10">
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayAdultPriceBusOneway" name="displayAdultPriceBusOneway" placeholder="표시용 성인가격" value="<?=$prodInfo['price_busodadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayChildPriceBusOneway" name="displayChildPriceBusOneway" placeholder="표시용 어린이가격" value="<?=$prodInfo['price_busodchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPriceBusOneway">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPriceBusOneway" name="regularAdultPriceBusOneway" placeholder="일반 성인가격" value="<?=$prodInfo['price_busoadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPriceBusOneway">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPriceBusOneway" name="regularChildPriceBusOneway" placeholder="일반 어린이가격" value="<?=$prodInfo['price_busochild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPriceBusOneway">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPriceBusOneway" name="partnerAdultPriceBusOneway" placeholder="협력사 성인가격" value="<?=$prodInfo['price_busocadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPriceBusOneway">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPriceBusOneway" name="partnerChildPriceBusOneway" placeholder="협력사 어린이가격" value="<?=$prodInfo['price_busocchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-9">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="OutAdultPriceBusOneway" name="OutAdultPriceBusOneway" placeholder="아웃바운드기준가격" value="<?=$prodInfo['oprice_busocadult']?>">
											</div>
										</div>
										
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">왕복버스이용</td>
							<td colspan="10">
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayAdultPriceBusRoundTrip" name="displayAdultPriceBusRoundTrip" placeholder="표시용 성인가격" value="<?=$prodInfo['price_busrdadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="input-group thinMargin">
												<span class="input-group-addon"><?php if ($prodInfo['base_rate'] == "CAD") echo "C$"; ?>
												<?php if ($prodInfo['base_rate'] == "USD") echo "U$"; ?></span>
												<input type="text" class="form-control" id="displayChildPriceBusRoundTrip" name="displayChildPriceBusRoundTrip" placeholder="표시용 어린이가격" value="<?=$prodInfo['price_busrdchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularAdultPriceBusRoundTrip">일반 성인가격</label>
												<input type="text" class="form-control" id="regularAdultPriceBusRoundTrip" name="regularAdultPriceBusRoundTrip" placeholder="일반 성인가격" value="<?=$prodInfo['price_busradult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="regularChildPriceBusRoundTrip">일반 어린이가격</label>
												<input type="text" class="form-control" id="regularChildPriceBusRoundTrip" name="regularChildPriceBusRoundTrip" placeholder="일반 어린이가격" value="<?=$prodInfo['price_busrchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerAdultPriceBusRoundTrip">협력사 성인가격</label>
												<input type="text" class="form-control" id="partnerAdultPriceBusRoundTrip" name="partnerAdultPriceBusRoundTrip" placeholder="협력사 성인가격" value="<?=$prodInfo['price_busrcadult']?>">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group thinMargin">
												<label class="sr-only" for="partnerChildPriceBusRoundTrip">협력사 어린이가격</label>
												<input type="text" class="form-control" id="partnerChildPriceBusRoundTrip" name="partnerChildPriceBusRoundTrip" placeholder="협력사 어린이가격" value="<?=$prodInfo['price_busrcchild']?>">
											</div>
										</div>
									</div>
								</div>
								<div class="col-lg-3">
									<div class="row">
										<div class="col-sm-9">
											<div class="form-group thinMargin">
												
												<input type="text" class="form-control" id="OutAdultPriceBusRoundTrip" name="OutAdultPriceBusRoundTrip" placeholder="아웃바운드기준가격" value="<?=$prodInfo['oprice_busrcadult']?>">
											</div>
										</div>
										
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">출발요일별선택</td>
							<td colspan="10" class="form-inline">
								<div class="col-sm-2">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="startDate1">출발요일별선택</label>
										<input type="date" class="form-control" id="startDate1" name="startDate1" max="2999-12-31" placeholder="출발요일별선택" value="<?=$prodInfo['p_vstart']?>">
									</div>
								</div>
								<div class="col-sm-2">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="startDate2">출발요일별선택</label>
										<input type="date" class="form-control" id="startDate2" name="startDate2" max="2999-12-31" placeholder="출발요일별선택" value="<?=$prodInfo['p_vend']?>">
									</div>
								</div>
								<div class="col-sm-8">
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="monday" value="0" <?php if(strstr($prodInfo['p_week'],"0/")) echo "checked"; ?> > 일
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="tuesday" value="1" <?php if(strstr($prodInfo['p_week'],"1/")) echo "checked"; ?>> 월
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="wednesday" value="2" <?php if(strstr($prodInfo['p_week'],"2/")) echo "checked"; ?>> 화
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="thursday" value="3" <?php if(strstr($prodInfo['p_week'],"3/")) echo "checked"; ?>> 수
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="friday" value="4" <?php if(strstr($prodInfo['p_week'],"4/")) echo "checked"; ?>> 목
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="saturday" value="5" <?php if(strstr($prodInfo['p_week'],"5/")) echo "checked"; ?>> 금
									</label>
									<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="sunday" value="6" <?php if(strstr($prodInfo['p_week'],"6/")) echo "checked"; ?>> 토
									</label>
									<!--<label class="form-inline">
										<input type="checkbox" name="weekday[]" id="everyday" value="9" <?php if(strstr($prodInfo['p_week'],"9/")) echo "checked"; ?>> 매일
									</label>-->
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">예약제한일자 &nbsp;<button type="button" class="btn btn-default btn-xs js-addBlockDate"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
							<td colspan="10" class="form-inline">
							    <?php
									$qry1 = "select * from product_limit where p_code = '{$prodInfo['p_code']}' && p_type ='L' order by p_limitdate asc";
									
									$rst1 = mysql_query($qry1);
									$cntL = mysql_num_rows($rst1);
									while($limit_row = mysql_fetch_assoc($rst1)):
						
							   ?>
										<div class="col-sm-12 js-blockDateSet">
											<div class="form-group removeBottomMargin">
												<label class="sr-only" for="blockDate">예약제한일자</label>
												<input type="date" class="form-control" id="blockDate" name="blockDate[]" placeholder="예약제한일자" max="2999-12-31" value="<?= $limit_row['p_limitdate'] ?>">
											</div>
											<button type="button" class="btn btn-default btn-xs js-removeBlockDate"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
							  <?php
							      endwhile;
							      if ($cntL == 0) {  	   
						      ?>
									  <div class="col-sm-12 js-blockDateSet">
											<div class="form-group removeBottomMargin">
												<label class="sr-only" for="blockDate">예약제한일자</label>
												<input type="date" class="form-control" id="blockDate" name="blockDate[]" max="2999-12-31" placeholder="예약제한일자" value="">
											</div>
											<button type="button" class="btn btn-default btn-xs js-removeBlockDate hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>


							  <?php

								  }
						       ?>
								 
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">예약특정일자 &nbsp;<button type="button" class="btn btn-default btn-xs js-addReservationDate"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
							<td colspan="10" class="form-inline">
							   <?php
									$qry1 = "select * from product_limit where p_code = '{$prodInfo['p_code']}' && p_type ='R' order by p_limitdate asc";
									
									$rst1 = mysql_query($qry1);
									$cntR = mysql_num_rows($rst1);
									while($r_row = mysql_fetch_assoc($rst1)):
						
							   ?>
										<div class="col-sm-12 js-reservationDateSet">
											<div class="form-group removeBottomMargin">
												<label class="sr-only" for="reservationDate">예약특정일자</label>
												<input type="date" class="form-control" id="reservationDate" name="reservationDate[]" placeholder="예약특정일자" max="2999-12-31" value="<?= $r_row['p_limitdate'] ?>">
											</div>
											<button type="button" class="btn btn-default btn-xs js-removeReservationDate"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
							  <?php
							      endwhile;
							      if ($cntR == 0) {  	   
						      ?>
                                       <div class="col-sm-12 js-reservationDateSet">
											<div class="form-group removeBottomMargin">
												<label class="sr-only" for="reservationDate">예약특정일자</label>
												<input type="date" class="form-control" id="reservationDate" name="reservationDate[]" placeholder="예약특정일자" max="2999-12-31" value="">
											</div>
											<button type="button" class="btn btn-default btn-xs js-removeReservationDate hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
							  <?php

								  }
						       ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">여행간단설명</td>
							<td colspan="10">
								<div class="form-group removeBottomMargin">
									<label class="sr-only" for="prodDesc">여행간단설명</label>
									<input type="text" class="form-control" id="prodDesc" name="prodDesc" placeholder="여행간단설명" value="<?=$prodInfo['p_sdesc']?>">
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">일정표 업로드</td>
							<td colspan="10">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_scdoc">일정표업로드</label>
										<input type="file" class="form-control" id="p_scdoc" name="p_scdoc" placeholder="현재상품배너이미지">
									</div>
								</div>
								<?php if($prodInfo['p_scdoc']): ?>
									<div class="col-sm-6 form-inline text-right">
										<input type="checkbox" id="sc_delm" name="sc_delm" value="1">삭제 
										<span class=""><?= $prodInfo['p_scdoc'] ?></span>
									</div>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">영문일정표 업로드</td>
							<td colspan="10">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_escdoc">영문일정표업로드</label>
										<input type="file" class="form-control" id="p_escdoc" name="p_escdoc" placeholder="일정표업로드">
									</div>
								</div>
								<?php if($prodInfo['p_escdoc']): ?>
									<div class="col-sm-6 form-inline text-right">
										<input type="checkbox" id="sc_edelm" name="sc_edelm" value="1">삭제 
										<span class=""><?= $prodInfo['p_escdoc'] ?></span>
									</div>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재상품배너이미지(1920X450)</td>
							<td colspan="4">
                               <?php if($prodInfo['p_mimg']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_mimg'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_mimg'] ?>" data-holder-rendered="true">
							   <?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_mimg">현재상품배너이미지</label>
										<input type="file" class="form-control" id="p_mimg" name="p_mimg" placeholder="현재상품배너이미지">
									</div>
								</div>
								<?php if($prodInfo['p_mimg']): ?>
									<div class="col-sm-6 form-inline text-right">
										<input type="checkbox" id="photo_delm" name="photo_delm" value="1">삭제 
										<span class=""><?= $prodInfo['p_mimg'] ?></span>
									</div>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 1(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img1']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img1'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img1'] ?>" data-holder-rendered="true">
							    <?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 1</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img1">현재서브이미지 - 1</label>
										<input type="file" class="form-control" id="p_img1" name="p_img1" placeholder="현재서브이미지 - 1">
									</div>
								</div>
								<?php if($prodInfo['p_img1']): ?>
									<div class="col-sm-6 form-inline text-right">
										<input type="checkbox" id="photo_del1" name="photo_del1" value="1">삭제 
										<span class=""><?= $prodInfo['p_img1'] ?></span>
									</div>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 2(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img2']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img2'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img2'] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 2</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img2">현재서브이미지 - 2</label>
										<input type="file" class="form-control" id="p_img2" name="p_img2" placeholder="현재서브이미지 - 2">
									</div>
									
								</div>
								<?php if($prodInfo['p_img2']): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del2" name="photo_del2" value="1">삭제 
											<span class=""><?= $prodInfo['p_img2'] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 3(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img3']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img3'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img3'] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 3</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img3">현재서브이미지 - 3</label>
										<input type="file" class="form-control" id="p_img3" name="p_img3" placeholder="현재서브이미지 - 3">
									</div>
								</div>
								<?php if($prodInfo['p_img3']): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del3" name="photo_del3" value="1">삭제 
											<span class=""><?= $prodInfo['p_img3'] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 4(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img4']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img4'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img4'] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 4</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img4">현재서브이미지 - 4</label>
										<input type="file" class="form-control" id="p_img4" name="p_img4" placeholder="현재서브이미지 - 4">
									</div>
								</div>
								<?php if($prodInfo['p_img4']): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del4" name="photo_del4" value="1">삭제 
											<span class=""><?= $prodInfo['p_img4'] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 5(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img5']): ?>
									<img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img5'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img5'] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 5</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img5">현재서브이미지 - 5</label>
										<input type="file" class="form-control" id="p_img5" name="p_img5" placeholder="현재서브이미지 - 5">
									</div>
								</div>
								<?php if($prodInfo['p_img5']): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del5" name="photo_del5" value="1">삭제 
											<span class=""><?= $prodInfo['p_img5'] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 6(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img6']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img6'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img6'] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 6</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img6">현재서브이미지 - 6</label>
										<input type="file" class="form-control" id="p_img6" name="p_img6" placeholder="현재서브이미지 - 6">
									</div>
								</div>
								<?php if($prodInfo['p_img6']): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del6" name="photo_del6" value="1">삭제 
											<span class=""><?= $prodInfo['p_img6'] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 7(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img7']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img7'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img7'] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 7</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img7">현재서브이미지 - 7</label>
										<input type="file" class="form-control" id="p_img7" name="p_img7" placeholder="현재서브이미지 - 7">
									</div>
								</div>
								<?php if($prodInfo['p_img7']): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del7" name="photo_del7" value="1">삭제 
											<span class=""><?= $prodInfo['p_img7'] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 8(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img8']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img8'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img8'] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 8</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img8">현재서브이미지 - 8</label>
										<input type="file" class="form-control" id="p_img8" name="p_img8" placeholder="현재서브이미지 - 8">
									</div>
								</div>
								<?php if($prodInfo['p_img8']): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del8" name="photo_del8" value="1">삭제 
											<span class=""><?= $prodInfo['p_img8'] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 9(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img9']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img9'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img9'] ?>" data-holder-rendered="true">
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 9</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img9">현재서브이미지 - 9</label>
										<input type="file" class="form-control" id="p_img9" name="p_img9" placeholder="현재서브이미지 - 9">
									</div>
								</div>
								<?php if($prodInfo['p_img9']): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del9" name="photo_del9" value="1">삭제 
											<span class=""><?= $prodInfo['p_img9'] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">현재서브이미지 - 10(850X450)</td>
							<td colspan="4">
								<?php if($prodInfo['p_img10']): ?>
									 <img width='140px' height='140px' alt="140x140" data-src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img10'] ?>" class="img-thumbnail js-placeholderImg" src="<?= PRODUCT_IMG_URL ?><?= $prodInfo['p_img10'] ?>" data-holder-rendered="true">>
							 	<?php endif; ?>
							</td>
							<td colspan="2" class="active text-center formHeader">이미지업로드 - 10</td>
							<td colspan="4">
								<div class="col-sm-6">
									<div class="form-group removeBottomMargin">
										<label class="sr-only" for="p_img10">현재서브이미지 - 10</label>
										<input type="file" class="form-control" id="p_img10" name="p_img10" placeholder="현재서브이미지 - 10">
									</div>
								</div>
								<?php if($prodInfo['p_img10']): ?>
									<div class="col-sm-6 form-inline text-right">
											<input type="checkbox" id="photo_del10" name="photo_del10" value="1">삭제 
											<span class=""><?= $prodInfo['p_img10'] ?></span>
									 </div>
								 <?php endif; ?>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">대표여행지주소(구글맵)</td>
							<td colspan="10">
								<input type="text" class="form-control" id="taddr" name="taddr" placeholder="대표여행지주소(구글맵)" value="<?=$prodInfo['t_addr']?>">
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">대표여행지지도</td>
							<td colspan="10">
								<!--<textarea class="form-control" rows="4" name="poption"><?= $prodInfo['p_otrip'] ?></textarea>-->
								<textarea class="form-control js-tripNote js-ckEditor" name="ptmap"><?= $prodInfo['p_tmap'] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">4줄간단설명</td>
							<td colspan="10">
								<textarea class="form-control" rows="4" name="p4desc" ><?= $prodInfo['p_4sdesc'] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">포함사항</td>
							<td colspan="10">
								<textarea class="form-control" rows="4" name="pinclude"><?= $prodInfo['p_include'] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">불포함사항</td>
							<td colspan="10">
								<textarea class="form-control" rows="4" name="pninclude"><?= $prodInfo['p_uninclude'] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">선택관광</td>
							<td colspan="10">
								<!--<textarea class="form-control" rows="4" name="poption"><?= $prodInfo['p_otrip'] ?></textarea>-->
								<textarea class="form-control js-tripNote js-ckEditor" name="poption"><?= $prodInfo['p_otrip'] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader" >준비물</td>
							<td colspan="10">
									<textarea class="form-control" rows="4" name="pprepare"><?= $prodInfo['p_prepare'] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">여행개요</td>
							<td colspan="10">
								<textarea class="form-control js-tripNote js-ckEditor" name="pref"><?= $prodInfo['p_ref'] ?></textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">이용특전</td>
							<td colspan="10">
								<textarea class="form-control js-specialBenefit js-ckEditor" name="pspecial"><?= $prodInfo['p_spec'] ?></textarea>
							</td>
						</tr>
						
						<tr>
							<td colspan="2" class="active text-center formHeader">노출여부</td>
							<td colspan="10">
								<label class="radio-inline">
									<input type="radio" name="exposure" id="immediately" value="y" <?php if ($prodInfo['p_display'] == "y" ) echo "checked"; ?>> 바로노출
								</label>
								<label class="radio-inline">
									<input type="radio" name="exposure" id="draft" value="n" <?php if ($prodInfo['p_display'] == "n" ) echo "checked"; ?>> 임시저장
								</label>
								<div class="radio-inline">
									<a href="#">미리보기 링크</a>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">즉시결제여부</td>
							<td colspan="10">
								<label class="radio-inline">
									<input type="radio" name="purchasable" id="purchasable" value="y" <?php if ($prodInfo['p_pay'] == "y" ) echo "checked"; ?>> 즉시결제가능
								</label>
								<label class="radio-inline">
									<input type="radio" name="purchasable" id="consultingRequired" value="n" <?php if ($prodInfo['p_pay'] == "n" ) echo "checked"; ?>> 문의후 결제
								</label>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품마감여부</td>
							<td colspan="10">
								<label class="radio-inline">
									<input type="radio" name="endyn" id="endyn" value="y" <?php if ($prodInfo['end_yn'] == "y" ) echo "checked"; ?>> 상품마감
								</label>
								<label class="radio-inline">
									<input type="radio" name="endyn" id="endynn" value="n" <?php if ($prodInfo['end_yn'] == "n" ) echo "checked"; ?>> 상품진행중
								</label>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">스케줄표그룹</td>
							<td colspan="3">
								<select name=grp class="form-control ">
									<option value="0" <?php if ($prodInfo['grp']=='0') echo "selected"; ?>>그룹선택
									<option value="1" <?php if ($prodInfo['grp']=='1') echo "selected"; ?>>그룹1
									<option value="2" <?php if ($prodInfo['grp']=='2') echo "selected"; ?>>그룹2
									<option value="3" <?php if ($prodInfo['grp']=='3') echo "selected"; ?>>그룹3
									<option value="4" <?php if ($prodInfo['grp']=='4') echo "selected"; ?>>그룹4
									<option value="5" <?php if ($prodInfo['grp']=='5') echo "selected"; ?>>그룹5
									<option value="6" <?php if ($prodInfo['grp']=='6') echo "selected"; ?>>그룹6
									<option value="7" <?php if ($prodInfo['grp']=='7') echo "selected"; ?>>그룹7
									<option value="8" <?php if ($prodInfo['grp']=='8') echo "selected"; ?>>그룹8
									<option value="9" <?php if ($prodInfo['grp']=='9') echo "selected"; ?>>그룹9
									<option value="10"<?php if ($prodInfo['grp']=='10') echo "selected"; ?>>그룹10
									<option value="11"<?php if ($prodInfo['grp']=='11') echo "selected"; ?>>그룹11
									<option value="12"<?php if ($prodInfo['grp']=='12') echo "selected"; ?>>그룹12
									<option value="13"<?php if ($prodInfo['grp']=='13') echo "selected"; ?>>그룹13
									<option value="14"<?php if ($prodInfo['grp']=='14') echo "selected"; ?>>그룹14
									<option value="15"<?php if ($prodInfo['grp']=='15') echo "selected"; ?>>그룹15
									<option value="16"<?php if ($prodInfo['grp']=='16') echo "selected"; ?>>그룹16
									<option value="17"<?php if ($prodInfo['grp']=='17') echo "selected"; ?>>그룹17
									<option value="18"<?php if ($prodInfo['grp']=='18') echo "selected"; ?>>그룹18
									<option value="19"<?php if ($prodInfo['grp']=='19') echo "selected"; ?>>그룹19
									<option value="20"<?php if ($prodInfo['grp']=='20') echo "selected"; ?>>그룹20
									<option value="21"<?php if ($prodInfo['grp']=='21') echo "selected"; ?>>그룹21
									<option value="22"<?php if ($prodInfo['grp']=='22') echo "selected"; ?>>그룹22
									<option value="23"<?php if ($prodInfo['grp']=='23') echo "selected"; ?>>그룹23
									<option value="24"<?php if ($prodInfo['grp']=='24') echo "selected"; ?>>그룹24
									<option value="25"<?php if ($prodInfo['grp']=='25') echo "selected"; ?>>그룹25
									<option value="26"<?php if ($prodInfo['grp']=='26') echo "selected"; ?>>그룹26
									<option value="27"<?php if ($prodInfo['grp']=='27') echo "selected"; ?>>그룹27
									<option value="28"<?php if ($prodInfo['grp']=='28') echo "selected"; ?>>그룹28
									<option value="29"<?php if ($prodInfo['grp']=='29') echo "selected"; ?>>그룹29
									<option value="30"<?php if ($prodInfo['grp']=='30') echo "selected"; ?>>그룹30
									<option value="31"<?php if ($prodInfo['grp']=='31') echo "selected"; ?>>그룹31
									<option value="32"<?php if ($prodInfo['grp']=='32') echo "selected"; ?>>그룹32
									<option value="33"<?php if ($prodInfo['grp']=='33') echo "selected"; ?>>그룹33
									<option value="34"<?php if ($prodInfo['grp']=='34') echo "selected"; ?>>그룹34
									<option value="35"<?php if ($prodInfo['grp']=='35') echo "selected"; ?>>그룹35
									<option value="36"<?php if ($prodInfo['grp']=='36') echo "selected"; ?>>그룹36
									<option value="37"<?php if ($prodInfo['grp']=='37') echo "selected"; ?>>그룹37
									<option value="38"<?php if ($prodInfo['grp']=='38') echo "selected"; ?>>그룹38
									<option value="39"<?php if ($prodInfo['grp']=='39') echo "selected"; ?>>그룹39
									<option value="40"<?php if ($prodInfo['grp']=='40') echo "selected"; ?>>그룹40
									<option value="41"<?php if ($prodInfo['grp']=='41') echo "selected"; ?>>그룹41
									<option value="42"<?php if ($prodInfo['grp']=='42') echo "selected"; ?>>그룹42
									<option value="43"<?php if ($prodInfo['grp']=='43') echo "selected"; ?>>그룹43
									<option value="44"<?php if ($prodInfo['grp']=='44') echo "selected"; ?>>그룹44
									<option value="45"<?php if ($prodInfo['grp']=='45') echo "selected"; ?>>그룹45
									<option value="46"<?php if ($prodInfo['grp']=='46') echo "selected"; ?>>그룹46
									<option value="47"<?php if ($prodInfo['grp']=='47') echo "selected"; ?>>그룹47
									<option value="48"<?php if ($prodInfo['grp']=='48') echo "selected"; ?>>그룹48
									<option value="49"<?php if ($prodInfo['grp']=='49') echo "selected"; ?>>그룹49
									<option value="50"<?php if ($prodInfo['grp']=='50') echo "selected"; ?>>그룹50
								</select>
							</td>
							<td colspan="7">
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">전체스케줄표그룹선택</td>
							<td colspan="3" width="10%" class="titletd text-center ">
							   <select class="form-control" name="scsel">
									<option value="">전체스케줄표그룹선택
									<?=printBaseCode_first('G03',$prodInfo['sc_grp'])?>
								</select> 
							</td>
							<td colspan="7">
							</td>
						</tr>
						<tr>
								<td colspan="2" class="active text-center formHeader">그룹배경색</td>
								<td colspan="10">
									<input type="text" class="inpubase md" id="bgcolor" name="bgcolor" placeholder="그룹배경색" value='<?=$prodInfo['bgcolor']?>'>&nbsp;&nbsp;<a href="https://htmlcolorcodes.com/" target="_blank">배경색(#00000)</a>
								</td>
						</tr>
					</tbody>
				</table>
				<div class="row">
					<div class="col-sm-6 col-sm-offset-6 text-right">
						<button type="submit" class="btn btn-xs btn-default js-formSave">상품저장</button>
						<?php if ($pcode!="") { ?>
						<button type="button" class="btn btn-xs btn-default js-formDelete" OnClick="javascript:pdel()">상품삭제</button>
						<button type="button" class="btn btn-xs btn-default js-openSchedule" data-toggle="modal" data-target=".js-openScheduleModal">일정표작성</button>
						<?php } ?>
					</div>
				</div>
			</form>

			<div class="modal fade js-openScheduleModal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
				<div class="modal-dialog modal-lg modal-full-width" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="gridSystemModalLabel">일정표</h4>
						</div>
						<div class="modal-body">
							<form action="<?= $PHP_SELF ?>?division=<?= $division ?>&pdx=<?= $pdx ?>&sub=<?= $sub ?>&ty=<?= $ty ?>&pcode=<?= $pcode ?>" name="frmproductschedule" id="frmproductschedule" method="post">
								<input type="hidden" name="mode" value="scheduleSave">
								<input type="hidden" name="pcode" value="<?= $pcode ?>">
								<div class="row">
									<div class="col-sm-6">
										<table class="table table-bordered table-condensed ptTable formSchedule scheduleHeader">
											<tbody>
												<tr>
													<td colspan="2" class="active text-center formHeader">상품명/코드</td>
													<td colspan="10"><?=$prodInfo['p_name']?>– <?=$pcode?> </td>
												</tr>
												<tr>
													<td colspan="2" class="active text-center formHeader">여행기간</td>
													<td colspan="3" class="js-formScheduleTourLength"><?=$prodInfo['p_day']?></td>
													<td colspan="3"><input type="text" class="form-control js-tourLength1" id="tourLength1" name="tourLength1" placeholder="여행기간" value="<?=$prodInfo['p_day']?>"></td>
													<td colspan="4"></td>
												</tr>
											</tbody>
										</table>
									</div>
									<div class="col-sm-6 text-right">
										<br />
										<br />
										<button type="submit" class="btn btn-xs btn-default js-scheduleSave">일정표등록</button>
										
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
										<table class="table table-bordered table-condensed ptTable formSchedule scheduleBody js-scheduleBody" id='scc'>
											<tbody id='scc1'>
												<tr>
													<td colspan="2" class="active text-center formHeader">상품명/코드</td>
													<td colspan="4" class="active text-center formHeader">경유지</td>
													<td colspan="6" class="active text-center formHeader">일정설명</td>
												</tr>
												<!------ --------->
												<?php
													$d_qry2 = "select * from product_details where p_code = '$pcode' order by day asc";
													$d_rst2 = mysql_query($d_qry2);
													if (mysql_affected_rows() > "0") {
													  //$n =1;
													  //while($d_row2 = mysql_fetch_assoc($d_rst2)):
													  for($n=1; $n<=$prodInfo['p_day']; $n++):
														  $d_row2 = mysql_fetch_assoc($d_rst2)
												?>
													
														<tr class="day-<?=$n?>">
															<td colspan="2" class="formHeader text-center">
																<font color='#131176'?><?=$n?>일차</font>
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<button type="button" class="btn btn-xs btn-default js-addSingleDayTour">단일투어추가</button>
															</td>
															<td colspan="4" class="formHeader">
																<textarea name="tourRoute[]" class="textarea-halfSize js-tourRoute" rows="10"><?=$d_row2['area']?></textarea>
																<?php 
																	$qry1 = "select * from product_details_local where p_code='$pcode' && day='$n' order by position asc";
																	$rst1 = mysql_query($qry1,$dbConn);
																	$l = 0;
																	 if (mysql_affected_rows() > "0") { 
																		while($row1 = mysql_Fetch_assoc($rst1)) {
																			$lcode=getProductMaster($row1['local_code']);
																?>
																			<div class="form-inline js-tourSet">
																				<button type="button" class="btn btn-xs btn-default js-openSingleDayTourSelection" data-toggle="modal" data-target=".js-openSingleDayTourModal">선택</button>
																				<div class="form-group removeBottomMargin">
																					<input type="text" class="form-control js-tourName" name="singleDayTourName[<?=$n?>][]" placeholder="단일투어" value='<?=$lcode['p_name']?>'>
																					<input type="hidden" name="singleDayTour[<?=$n?>][]" class="js-tourCode" value="<?=$row1['local_code']?>">
																				</div>
																				<input type="text" class="form-control" name="pos[<?=$n?>][]" placeholder="위치" style="width:15% !important;" value="<?=$row1['position']?>">

																				<div class="input-group removeBottomMargin">
																					<input type="text" class="form-control text-right" name="percentage[<?=$n?>][]" placeholder="배분율" value="<?=$row1['r_rate']?>">
																					<div class="input-group-addon">%</div>
																				</div>
																				<button type="button" class="btn btn-default btn-xs js-removeSingleDayTour "><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
																			</div>
																<?php
																		  $l++;
																		}
																	 } else {
																?>
																		<div class="form-inline js-tourSet">
																				<button type="button" class="btn btn-xs btn-default js-openSingleDayTourSelection" data-toggle="modal" data-target=".js-openSingleDayTourModal">선택</button>
																				<div class="form-group removeBottomMargin">
																					<input type="text" class="form-control js-tourName" name="singleDayTourName[<?=$n?>][]" placeholder="단독투어" value="">
																					<input type="hidden" name="singleDayTour[<?=$n?>][]" class="js-tourCode" value="">
																				</div>
																				<input type="text" class="form-control" name="pos[<?=$n?>][]" placeholder="위치" style="width:15% !important;" value="1">
																				<div class="input-group removeBottomMargin">
																					<input type="text" class="form-control text-right" name="percentage[<?=$n?>][]" placeholder="배분율" value="">
																					<div class="input-group-addon">%</div>
																				</div>
																				<button type="button" class="btn btn-default btn-xs js-removeSingleDayTour hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
																		</div>
																<?php
																		
																	 } 
																?>
															</td>
															<td colspan="6">
																<textarea name="tourDesc[]" class="js-tourDesc js-ckEditor"><?=$d_row2['content']?></textarea>
															</td>
														</tr>
														<tr class="day-<?=$n?>">
															<td colspan="2" class="formHeader text-center">숙박호텔</td>
															<td colspan="10">
																<div class="form-group removeBottomMargin">
																	<label class="sr-only" for="hotelName1">호텔명</label>
																	<input type="text" class="form-control" id="hotelName1" name="hotelName[]" placeholder="호텔명" value="<?=$d_row2['hotel']?>">
																</div>
															</td>
														</tr>
														<tr class="day-<?=$n?>">
															<td colspan="2" class="formHeader text-center">식사선택</td>
															<td colspan="10">
																<label class="form-inline js-tourSet">
																	<input type=text name="meal1[]" class="form-control text-center" size=3 value="<?= $d_row2['meal_black'] ?>"><input type=text name="mealnm1[]" class="form-control text-center" size=10 value="<?= $d_row2['meal_black11'] ?>" placeholder="조식명">&nbsp;조식&nbsp;
																
																	<input type=text name="meal2[]" class="form-control text-center" size=3 value="<?= $d_row2['meal_lunch'] ?>"><input type=text name="mealnm2[]" class="form-control text-center" size=10 value="<?= $d_row2['meal_lunch11'] ?>" placeholder="중식명">&nbsp;중식&nbsp;&nbsp;
																
																	<input type=text name="meal3[]" class="form-control text-center" size=3 value="<?= $d_row2['meal_dinner'] ?>"><input type=text name="mealnm3[]" class="form-control text-center" size=10 value="<?= $d_row2['meal_dinner11'] ?>" placeholder="석식명">&nbsp;석식&nbsp;&nbsp;
															
																	<input type=text name="meal4[]" class="form-control text-center" size=3 value="<?= $d_row2['meal_black1'] ?>">&nbsp;조식(자유식)&nbsp;&nbsp;
																
																	<input type=text name="meal5[]" class="form-control text-center" size=3 value="<?= $d_row2['meal_lunch1'] ?>">&nbsp;중식(자유식)&nbsp;&nbsp;
																
																	<input type=text name="meal6[]" class="form-control text-center" size=3 value="<?= $d_row2['meal_dinner1'] ?>">&nbsp;석식(자유식)
																</label>
															</td>
														</tr>
														
													<?php
														//$n++;
														//endwhile;
														endfor;
													?>
												<?php
												  } else {
													 for($k=1; $k<=$prodInfo['p_day']; $k++):
												?>
														<tr class="day-<?=$k?>" id='s<?=$k?>'>
															<td colspan="2" class="formHeader text-center">
																<font color='#131176'?><?=$k?>일차</font>
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<br />
																<button type="button" class="btn btn-xs btn-default js-addSingleDayTour">단일투어추가</button>
															</td>
															<td colspan="4" class="formHeader">
																<textarea name="tourRoute[]" class="textarea-halfSize js-tourRoute" rows="10"></textarea>
																<div class="form-inline js-tourSet">
																	<button type="button" class="btn btn-xs btn-default js-openSingleDayTourSelection" data-toggle="modal" data-target=".js-openSingleDayTourModal">선택</button>
																	<div class="form-group removeBottomMargin">
																		<input type="text" class="form-control js-tourName" name="singleDayTourName[<?=$k?>][]" placeholder="단일투어" value="">
																		<input type="hidden" name="singleDayTour[<?=$k?>][]" class="js-tourCode" value="">
																		
																	</div>
																	<input type="text" class="form-control " name="pos[<?=$k?>][]" placeholder="위치" style="width:15% !important;" value="1">

														
																	<div class="input-group removeBottomMargin">
																	
																		<input type="text" class="form-control text-right" name="percentage[<?=$k?>][]" placeholder="배분율(숫자)"  value="">
																		<div class="input-group-addon">%</div>
																		
																	</div>
																	<button type="button" class="btn btn-default btn-xs js-removeSingleDayTour hidden"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
																</div>
															</td>
															<td colspan="6">
																<textarea name="tourDesc[]" class="js-tourDesc js-ckEditor"></textarea>
															</td>
														</tr>
														<tr class="day-<?=$k?>">
															<td colspan="2" class="formHeader text-center">숙박호텔</td>
															<td colspan="10">
																<div class="form-group removeBottomMargin">
																	<label class="sr-only" for="hotelName1">호텔명</label>
																	<input type="text" class="form-control" id="hotelName1" name="hotelName[]" placeholder="호텔명">
																</div>
															</td>
														</tr>
														<tr class="day-<?=$k?>">
															<td colspan="2" class="formHeader text-center">식사선택</td>
															<td colspan="10">
															   <label class="form-inline js-tourSet">
																   <input type=text name="meal1[]" class="form-control text-center" size=3 value=""><input type=text name="mealnm1[]" class="form-control text-center" size=10 value="" placeholder="조식명">&nbsp;조식&nbsp;
																   
																	<input type=text name="meal2[]" class="form-control text-center" size=3 value=""><input type=text name="mealnm2[]" class="form-control text-center" size=10 value="" placeholder="중식명">&nbsp;중식&nbsp;&nbsp;
																
																	<input type=text name="meal3[]" class="form-control text-center" size=3 value=""><input type=text name="mealnm3[]" class="form-control text-center" size=10 value="" placeholder="석식명">&nbsp;석식&nbsp;&nbsp;
															
																	<input type=text name="meal4[]" class="form-control text-center" size=3 value="">&nbsp;조식(자유식)&nbsp;&nbsp;
																
																	<input type=text name="meal5[]" class="form-control text-center" size=3 value="">&nbsp;중식(자유식)&nbsp;&nbsp;
																
																	<input type=text name="meal6[]" class="form-control text-center" size=3 value="">&nbsp;석식(자유식)
																</label>
															</td>
														</tr>
												<?php
													endfor;
												  } 
												?>
												<!------- -------->
											</tbody>
										</table>
									</div>
								</div>
							</form>
						</div>
						<div class="modal-footer">
							<!-- <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							<button type="button" class="btn btn-primary">Save changes</button> -->
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->

			<div class="modal fade js-openSingleDayTourModal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
				<div class="modal-dialog modal-lg modal-in-modal" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="gridSystemModalLabel">단일투어</h4>
						</div>
						<div class="modal-body">
							<div class="row">
								<div class="col-sm-12">
									<input type="text" class="form-control removeBottomMargin js-searchSingleDayTour" name="sskeyword" placeholder="검색">
								</div>
							</div>
							<div class="row overflowBody">
								<div class="col-sm-12">
								<?php
								    
								   $qry1 = "select * from product_master where 1=1 && p_type='1' && (end_yn is null || end_yn != 'y') order by p_name asc ";
								   $rst1 = mysql_query($qry1,$dbConn);
									//echo $qry1;	
								   while($row1 = mysql_Fetch_assoc($rst1)){
								?>
										 <div class="radio">
											<label><!-- data-search-str needs to be in all lower case -->
												<input type="radio" name="singleDayTour[]" value="<?=$row1['p_code']?>" data-tour-name='<?=$row1['p_name']?>' data-tour-code="<?=$row1['p_code']?>" data-search-str='<?=$row1['p_code']?> <?=$row1['p_name']?>'>
												[<?=$row1['p_code']?>] <?=$row1['p_name']?>
											</label>
										</div>
								<?php
								   }
								?>
									
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
							<button type="button" class="btn btn-primary js-saveSelection">선택사항 저장</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</div><!-- /.modal -->

		</div>
	</div>
    <?php
		include "include/side_m.php"
	?>
	<script src="ckeditor/ckeditor.js"></script>
	<!--<script src="//cdn.ckeditor.com/4.11.0/full/ckeditor.js"></script>-->
   

	<script>
			function normalizeMyprtOrgUrl(url) {
				if (!url) {
					return url;
				}

				if (/^https?:\/\/(www\.)?myprt\.org/i.test(url)) {
					return url.replace(/^http:\/\//i, 'https://');
				}

				if (/^https?:\/\/(www\.)?myprt\.biz/i.test(url)) {
					return url.replace(/^https?:\/\/(www\.)?myprt\.biz/i, 'https://myprt.org');
				}

				if (/^\/upload\//i.test(url)) {
					return 'https://myprt.org' + url;
				}

				if (/^upload\//i.test(url)) {
					return 'https://myprt.org/' + url;
				}

				return url;
			}

			function normalizeEditorHtml(html) {
				if (!html) {
					return html;
				}

				return html.replace(/((?:src|data-mce-src|href|data-mce-href)=["'])([^"']+)(["'])/gi, function (match, prefix, url, suffix) {
					return prefix + normalizeMyprtOrgUrl(url) + suffix;
				});
			}

			function normalizeEditorImageUrls() {
				if (!window.tinymce) {
					return;
				}

				var editors = tinymce.editors || [];
				for (var i = 0; i < editors.length; i++) {
					var editor = editors[i];
					if (!editor) {
						continue;
					}

					var content = editor.getContent();
					var normalizedContent = normalizeEditorHtml(content);
					if (content !== normalizedContent) {
						editor.setContent(normalizedContent);
					}
				}

				tinymce.triggerSave();
				$('textarea.js-ckEditor').each(function () {
					this.value = normalizeEditorHtml(this.value);
				});
			}

			$(document).ready(function () {
				$.ajaxSetup({async:false});
				pt.initProductDetailForm()
				pt1.initProductDetailForm2()
				$(".randcomp").chosen({
					width: '100%'
				});

       
				// TinyMCE 초기화
				tinymce.init({
					selector: '.js-ckEditor',
					forced_root_block: 'div',
					height: 700,
					language: 'ko_KR',
					license_key: 'gpl',
					plugins: [
						'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
						'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
						'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons','code'
					],
					// 중요: readonly 모드 비활성화
				    readonly: false,
					toolbar: 'undo redo | blocks fontfamily fontsize | ' +
							 'bold italic underline strikethrough | link image media table | ' +
							 'align lineheight | numlist bullist indent outdent | emoticons charmap | ' +
							 'removeformat | code fullscreen preview',
					
					font_family_formats: 
			            '나눔고딕=Nanum Gothic, sans-serif;' +
						'맑은 고딕=Malgun Gothic,sans-serif;' +
						'돋움=Dotum,sans-serif;' +
						'굴림=Gulim,sans-serif;' +
						'바탕=Batang,serif;' +
						'Arial=arial,helvetica,sans-serif;' +
						'Times New Roman=times new roman,times,serif;' +
						'Courier New=courier new,courier,monospace',
					
					fontsize_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 28pt 30pt 32pt 34pt 36pt',
					
					images_upload_url: 'cupload_image.php',
					automatic_uploads: true,
					paste_data_images: true,
					images_reuse_filename: true,
					
					document_base_url: '<?= $_editorBaseUrl ?>',
					convert_urls: false,
					relative_urls: false,
					remove_script_host: false,
					content_style: 'body { font-family: Nanum Gothic, sans-serif; font-size: 14px; }',
					menubar: 'file edit view insert format tools table help',
					branding: false,
					resize: 'both',
					elementpath: false,
					statusbar: true,
					images_upload_handler: function (blobInfo, progress) {
					return new Promise(function(resolve, reject) {
						var filename = blobInfo.filename();
						
						if (filename.indexOf('mceclip') === 0) {
							// 원본에서 확장자 추출
							var extension = '';
							var dotPos = filename.lastIndexOf('.');
							if (dotPos !== -1) {
								extension = filename.substring(dotPos); // .jpg, .png 등
							} else {
								extension = '.jpg'; // 기본 확장자
							}
							
							// 새로운 파일명 (확장자 제외) + 원본 확장자
							filename = 'purun_image' + extension;
						}
    
						
						var xhr = new XMLHttpRequest();
						xhr.withCredentials = false;
						xhr.open('POST', 'cupload_image.php');
						
						xhr.onload = function() {
							if (xhr.status < 200 || xhr.status >= 300) {
								var msg = 'HTTP Error: ' + xhr.status;
								try { var e2 = JSON.parse(xhr.responseText); if (e2 && e2.error) msg = e2.error; } catch(ex) {}
								reject(msg);
								return;
							}
							
							var json = JSON.parse(xhr.responseText);
							if (!json || typeof json.location != 'string') {
								reject('Invalid JSON: ' + xhr.responseText);
								return;
							}
							
							var imageUrl = normalizeMyprtOrgUrl(json.location);
							resolve(imageUrl);
						};
						
						var formData = new FormData();
						var file = new File([blobInfo.blob()], filename, {type: blobInfo.blob().type});
						formData.append('file', file);
						xhr.send(formData);
					});
				}
				});

				$('#frmproductschedule').on('submit', function () {
					normalizeEditorImageUrls();
				});

				
			})
			function chksave() {
				  normalizeEditorImageUrls();
				  
				  if ($("#ty1").val() == "") {
						alert("분류선택 1을 입력하세요!");
						$("#ty1").focus();
						return false;
				  }
				  if ($("#ty2").val() == "") {
						alert("분류선택 2를 입력하세요!");
						$("#ty2").focus();
						return false;
				  }
                  if ($("#area1").val() == "") {
						alert("상품분류 1을 입력하세요!");
						$("#area1").focus();
						return false;
				  }
				  
				 /* if ($("#area2").val() == "") {
						alert("상품분류 2를 입력하세요!");
						$("#area2").focus();
						return false;
				  }*/
				  if ($("#prodName").val() == "") {
						alert("상품명을 입력하세요!");
						$("#prodName").focus();
						return false;
				  }
				  
				  if ($("#tourLength").val() == "") {
						alert("상품기간을 입력하세요!");
						$("#tourLength").focus();
						return false;
				  }

				  if ($("#p_own").val() == "") {
						alert("상품소유사를 입력하세요!");
						$("#p_own").focus();
						return false;
				  }
				  if ($("#maxPerCar").val() == "") {
						alert("투어정원을 입력하세요!");
						$("#maxPerCar").focus();
						return false;
				  }
				  
				  return true;

			}
			function pdel() {
					if (confirm("삭제할까요?") == true) {

						$("#mode").val("del");
						$("#frmproduct").submit();
					}
			}
			// Bootstrap 모달과 TinyMCE 충돌 방지
			$(document).on('focusin', function(e) {
				if ($(e.target).closest(".tox-dialog").length) {
					e.stopImmediatePropagation();
				}
			});
						// 모달 + TinyMCE 완전한 설정
		</script>
    </body>
</html>
