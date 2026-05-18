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

    $seqno = $_GET['number'];
    $scode = $_GET['scode'];

    include "inc_guidesave.php";

    //투어 기본 정보
    $query = "SELECT a.*,b.kor_name
    FROM tour_guide a left join member_list b on a.guide_id=b.userid WHERE a.seq_no = $seqno ";
    $rst1 = mysql_query($query,$dbConn);
    $data_row = mysql_fetch_assoc($rst1);

    //가이드 정산코드
    $guide_code = getGuideCode($data_row['grand_eCode'],$data_row['sub_eCode']);
    //행사기간
    $period = getPeriodbyrev($data_row['p_code'],$data_row['stDate']);
    //행사인원
    $p_cnt = getReserveInfoCnt($data_row['p_code'],$data_row['stDate']);

    //guide setmaster
    $query = "SELECT * FROM guide_setmaster WHERE settle_code = '{$guide_code["settle_code"]}' ";
    $rst00 = mysql_query($query,$dbConn);
    $data_row00 = mysql_fetch_assoc($rst00);

    
    if($data_row00['reg_status'] == 'COMPLETE') $disabled = 'disabled';
    else $disabled='';

    //조식,중식,석식
    $meal_b1=0;$meal_b2=0;$meal_b3=0;$meal_l1=0;$meal_l2=0;$meal_l3=0;$meal_d1=0;$meal_d2=0;$meal_d3=0;
    $query = "SELECT g_date,r_type,
		COALESCE(SUM(CASE WHEN r_type='bf' THEN r_mealcnt END), 0) AS bf_cnt,
		COALESCE(SUM(CASE WHEN r_type='lunch' THEN r_mealcnt END), 0) AS lunch_cnt,
		COALESCE(SUM(CASE WHEN r_type='dinner' THEN r_mealcnt END), 0) AS dinner_cnt,

		COALESCE(SUM(CASE WHEN r_type='bf' THEN r_mealamt END), 0) AS bf_price,
		COALESCE(SUM(CASE WHEN r_type='lunch' THEN r_mealamt END), 0) AS lunch_price,
		COALESCE(SUM(CASE WHEN r_type='dinner' THEN r_mealamt END), 0) AS dinner_price,

		COALESCE(SUM(CASE WHEN r_type='bf' THEN r_tamt END), 0) AS bf_totalprice,
		COALESCE(SUM(CASE WHEN r_type='lunch' THEN r_tamt END), 0) AS lunch_totalprice,
		COALESCE(SUM(CASE WHEN r_type='dinner' THEN r_tamt END), 0) AS dinner_totalprice


		FROM meal_settle
    WHERE settle_code = '{$guide_code["settle_code"]}' GROUP BY g_date,r_type 
    ORDER BY g_date,r_type ";

    $rst3333 = mysql_query($query,$dbConn);
//echo $query;
//exit;
    $query = "SELECT * FROM meal_settle WHERE settle_code = '{$guide_code["settle_code"]}' AND r_type='bf' ORDER BY r_type ";
    $rst2 = mysql_query($query,$dbConn);
    $rst2_cnt = mysql_num_rows($rst2);

    $query = "SELECT * FROM meal_settle WHERE settle_code = '{$guide_code["settle_code"]}' AND r_type='lunch' ORDER BY r_type ";
    $rst21 = mysql_query($query,$dbConn);
    $rst21_cnt = mysql_num_rows($rst21);

    $query = "SELECT * FROM meal_settle WHERE settle_code = '{$guide_code["settle_code"]}' AND r_type='dinner' ORDER BY r_type ";
    $rst22 = mysql_query($query,$dbConn);
    $rst22_cnt = mysql_num_rows($rst22);

    //입장비
    $en_b1=0;$en_b2=0;
    $query = "SELECT * FROM guide_ticket WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst3 = mysql_query($query,$dbConn);
    $rst3_cnt = mysql_num_rows($rst3);

    //옵션명
    $opt_b1=0;$opt_b2=0;$opt_b3=0;$opt_b4=0;$opt_b5=0;$opt_b6=0;$opt_b7=0;$opt_b8=0; $opt_bg=0;$opt_bc=0;
    $query = "SELECT * FROM etcopt_settle WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst4 = mysql_query($query,$dbConn);
    $rst4_cnt = mysql_num_rows($rst4);

    //가이드 기타입금
    $etcsum = 0;
    $etcamt1=0;
    $query = "SELECT * FROM etc_settle WHERE settle_code = '{$guide_code["settle_code"]}'  ORDER BY seq_no ";
    $rst5 = mysql_query($query,$dbConn);
    $rst5_cnt = mysql_num_rows($rst5);

    //차량 기타비용
    $etcamt2=0;
    $query = "SELECT * FROM car_settle WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst6 = mysql_query($query,$dbConn);
    $rst6_cnt = mysql_num_rows($rst6);

    //가이드입금
	$inputloccnt=0;$guidetotamt=0;$guidetotcnt = 0;
    $query = "SELECT * FROM guide_amount WHERE settle_code = '{$guide_code["settle_code"]}' && inputtype='local' ORDER BY seq_no ";
    $rst7 = mysql_query($query,$dbConn);
    $rst7_cnt = mysql_num_rows($rst7);

	$inputinbcnt=0;
	$query = "SELECT * FROM guide_amount WHERE settle_code = '{$guide_code["settle_code"]}' && inputtype='inbound' ORDER BY seq_no ";
    $rst71 = mysql_query($query,$dbConn);
    $rst71_cnt = mysql_num_rows($rst71);
	//echo $query;
	//exit;
	$inputsuamt=0; $inputspamt=0;
	$query = "SELECT * FROM guide_amount WHERE settle_code = '{$guide_code["settle_code"]}' && inputtype='support' ORDER BY seq_no ";
    $rst72 = mysql_query($query,$dbConn);
    $rst72_cnt = mysql_num_rows($rst72);

    //쇼핑비용
    $sales_amt1=0;$sales_amt2=0;$sales_amt3=0;$sales_amt4=0;
    $query = "SELECT * FROM shop_opt WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst8 = mysql_query($query,$dbConn);
    $rst8_cnt = mysql_num_rows($rst8);

    //가이드 현지수금액
    $inputamt1=0;
    $query = "SELECT * FROM just_credit WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst9 = mysql_query($query,$dbConn);
	$rst9_cnt = mysql_num_rows($rst9);
    
	//회사 행사지출
    $inputamt2=0;
    $query = "SELECT * FROM comp_payout WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst10 = mysql_query($query,$dbConn);
	$rst10_cnt = mysql_num_rows($rst10);
    
		
	//가이드 행사총지출-tip
    $inputamt3=0;$inputamt4=0; $inputamt5=0;$inputamt6=0;
    $query = "SELECT * FROM event_guide WHERE settle_code = '{$guide_code["settle_code"]}' && event_type='tip' ORDER BY seq_no ";
    $rst11 = mysql_query($query,$dbConn);
	$rst11_cnt = mysql_num_rows($rst11);
    
	//가이드 행사총지출- 가이드fee
    
    $query = "SELECT * FROM event_guide WHERE settle_code = '{$guide_code["settle_code"]}' && event_type='fee' ORDER BY seq_no ";
    $rst12 = mysql_query($query,$dbConn);
	$rst12_cnt = mysql_num_rows($rst12);

    //가이드 행사총지출- 식사팁
   
    $query = "SELECT * FROM event_guide WHERE settle_code = '{$guide_code["settle_code"]}' && event_type='mtip' ORDER BY seq_no ";
    $rst13 = mysql_query($query,$dbConn);
	$rst13_cnt = mysql_num_rows($rst13);

	//가이드 행사총지출- 회사지원금
    $query = "SELECT * FROM event_guide WHERE settle_code = '{$guide_code["settle_code"]}' && event_type='camt' ORDER BY seq_no ";
    $rst14 = mysql_query($query,$dbConn);
	$rst14_cnt = mysql_num_rows($rst14);
    

    //옵션명
    $opt_b1=0;$opt_b2=0;$opt_b3=0;$opt_b4=0;$opt_b5=0;$opt_b6=0;$opt_b7=0;$opt_b8=0;
    $query = "SELECT * FROM guide_option WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst15 = mysql_query($query,$dbConn);
    $rst15_cnt = mysql_num_rows($rst15);


	//가이드업무시간
    $query = "SELECT * FROM guide_date WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst16 = mysql_query($query,$dbConn);
    $rst16_cnt = mysql_num_rows($rst16);

    //차량정산	
    $car_b1=0;$car_b2=0;$car_b3=0;$car_b4=0;$car_b5=0;$car_b6=0;$car_b7=0;$car_b8=0;
    $query = "SELECT * FROM car_settle WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst17 = mysql_query($query,$dbConn);
    $rst17_cnt = mysql_num_rows($rst17);

	//요약정보
    $query = "SELECT * FROM summary_guidesettle WHERE seetle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
	
    $rst18 = mysql_query($query,$dbConn);
    $rst18_cnt = mysql_num_rows($rst18);
    

	//가이드 업무시간
    $query = "SELECT * FROM guide_time WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst20 = mysql_query($query,$dbConn);
	$rst20_cnt = mysql_num_rows($rst20);


    $inputTotamt=0;
    //가이드정산 입장지명
    $guide_g01 =  getGuideBaseCode('N01');
	
    //가이드정산 옵션
    $guide_g02 =  getGuideBaseCode('N04');
    //가이드정산 차량비용
    $guide_g03 =  getGuideBaseCode('N03');
    //가이드정산 쇼핑비용
    $guide_g04 =  getGuideBaseCode('N02');
	$guide_g05 =  getGuideBaseCode('N05');

?>

<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">가이드정산</a></li>
					<li>가이드정산등록-1</li>
				</ul>
			</div>

            
            <!--<form action="guide-excel.php">
                <input type="submit" value="EXCEL 저장">
                <?php //foreach($_POST as $k=>$v){ ?>
                <input type="hidden" name="<?=htmlspecialchars($k)?>"  value="<?=htmlspecialchars($v)?>" >
                <?php //} ?>
            </form>-->


            <form name="frnName" id="frnName" method="post" >
                <input type="hidden" name="mode" id="mode" value="save">
                <input type="hidden" name="grand_eCode" id="grand_eCode" value="<?= $data_row['grand_eCode'] ?>">
                <input type="hidden" name="sub_eCode" id="sub_eCode" value="<?= $data_row['sub_eCode'] ?>">
                <input type="hidden" name="pcode" id="pcode" value="<?= $data_row['p_code'] ?>">
                <input type="hidden" name="stDate" value="<?= $data_row['stDate'] ?>">
                <input type="hidden" name="settle_code" value="<?= $guide_code['settle_code'] ?>">

				<div class="row no-nav">
				    <div class="col-sm-6 text-left">
						
						<input type="checkbox" class="custom-control-input guiderpt" id="guiderpt" name="guiderpt" <?php  if($data_row00['status1'] == '1') {?> checked <?php }?> value="1">
						<label class="custom-control-label" for="customCheck guiderpt">가이드정산보고</label>
						<input type="checkbox" cla="custom-control-input" id="opconfirm" name="opconfirm"  <?php  if($data_row00['status2'] == '2') {?> checked <?php }?> value="2">
						<label class="custom-control-label" for="customCheck">OP확인</label>
						<input type="checkbox" class="custom-control-input" id="accconfirm" name="accconfirm" <?php  if($data_row00['status3'] == '3') {?> checked <?php }?> value="3">
						<label class="custom-control-label" for="customCheck">ACCOUNT 확인</label>
						<input type="checkbox" class="custom-control-input" id="teamconfirm" name="teamconfirm" <?php  if($data_row00['status4'] == '4') {?> checked <?php }?> value="4">
						<label class="custom-control-label" for="customCheck">팀장확인</label>
						<input type="checkbox" class="custom-control-input" id="ceoconfirm" name="ceoconfirm" <?php  if($data_row00['status5'] == '5') {?> checked <?php }?> value="5">
						<label class="custom-control-label" for="customCheck">대표이사확인</label>
                        
					</div>
					<div class="col-sm-4 text-right">
						
						<button type="submit" class="btn btn-xs btn-default js-save" <?=$disabled?>>저장</button>
						<button type="button" class="btn btn-xs btn-default js-delete" <?=$disabled?>>삭제</button>
						<button type="button" class="btn btn-xs btn-default js-settle" <?=$disabled?>>정산보고완료</button>
						<button type="button" class="btn btn-xs btn-default js-csettle" <?=$disabled?>>정산보고취소</button>
                        <button type="button" class="btn btn-xs btn-default js-print" >프린트</button>
                        
					</div>
					<div class="col-sm-2 text-right">
						&nbsp;
					</div>
				</div>

               
				<br />
                <div class="input_text_color" style="font-size:18px">가이드정산코드 : <?=$guide_code['settle_code']?> </div>
                <br>
                
                <!-- 3333CONTENT TABLE -->
                <!-- //3333CONTENT TABLE -->
    
				<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
							<td colspan="2" class="active text-center formHeader">통합행사코드</td>
							<td colspan="6"><?=$data_row['grand_eCode']?></td>
                            <td colspan="2" class="active text-center formHeader">행사코드</td>
							<td colspan="6" class="input_text_color"><?=$data_row['sub_eCode']?></td>
                        </tr>
                        <tr>                    			
							<td colspan="2" class="active text-center formHeader">행사총인원</td>
							<td colspan="6"><?=$p_cnt['cnt']?>명</td>
							<td colspan="2" class="active text-center formHeader">가이드</td>
							<td colspan="6"><?=$data_row['kor_name']?> 가이드</td>
                        </tr>
                        <tr>                    			
							<td colspan="2" class="active text-center formHeader">행사기간</td>
							<td colspan="6">
				                <div class="row">
									<div class="col-sm-6"><?=$period?></div>    			    
                                </div>
							</td>
                            <td colspan="2" class="active text-center formHeader">차량인승</td>
							<td colspan="6"><?=$data_row['c_type']?></td>
                        </tr>
                        
                    </tbody>
				</table>
				<div class="row"><div class="col-sm-12 text-center formHeader fullWidth"><b>현지수금액(투어비)</b></div></div>
				<table id="custom_table" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
                    <thead>
                        <tr>
						  <th scope="col" ></th>
                          <th scope="col" width="*">내역(손님/여행사)</th>
                          <th scope="col" width="10%">금액</th>
                          <th scope="col">카드/현금</th>
                       </tr>
                    </thead>
					<tbody class="innertr">
                      <?php if( $rst9_cnt<=0){  ?>
                         <tr class="basic-class1" param ="tr-parent">
                            <td><button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td><input type="text" class="form-control" name="justnm[]" placeholder="내역" value=""></td>
                            <td><input type="text" class="form-control text-right justtot" name="justamt[]" aria-label="금액" value="0.00"/></td>
							<td>
							 <div class="row">
                                    <div class="col-sm-10">
									   <select  class="form-control" name="paytype[]" />
									   <option value="" selected>결제타입</option>
									   <option value="card">카드결제</option>
									   <option value="cash">현금결제</option>
									   </select>
								    </div>
                                    <div class="col-sm-1 hide button-minus">
                                    <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>     
                             </div> 
                            
							</td>
						</tr>
					<?php } else {

						$qq=0 ;  $inputjustamt=0;
                        while($row9 = mysql_Fetch_assoc($rst9)){ 
                            
                            $inputjustamt = $inputjustamt+ $row9['pay_amt'];
				    ?>
						<tr class="basic-class1" param ="tr-parent">
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst9_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td><input type="text" class="form-control" name="justnm[]" placeholder="내역" value="<?=$row9['tour_cname']?>"></td>
                            <td><input type="text" class="form-control text-right justtot" name="justamt[]" aria-label="금액" value="<?=$row9['pay_amt']?>"/></td>
							<td>
							 <div class="row">
                                    <div class="col-sm-10">
									   <select  class="form-control selectpay" name="paytype[]"  />
										    <option <?php if ($row9['pay_type']=="") { ?> selected <?php } ?> >- 결제타입 -</option>
											<option <?php if ($row9['pay_type']=="card") { ?> selected <?php } ?> value="card">크레딧카드</option>
											<option value="cash" <?php if ($row9['pay_type']=="cash") { ?> selected <?php } ?>>현금</option>
									   </select>
								    </div>
                                    <div class="col-sm-1 hide button-minus">
                                    <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>     
                             </div> 
                            
							</td>
						</tr>



                     <?php }} ?>
						<tr>
                          <td bgcolor="#5cb85c">총합계</td>
                          <td><input type="hidden" name="justtotamt" id="justtotamt" value=""></td>
                          <td align="right" ><span class="justtotamt">0.00</span></td>
                          <td></td>
                          
                        </tr>
				</table>
				<div class="row"><div class="col-sm-12 text-center formHeader fullWidth"><b>기타입금액</b></div></div>
				<table id="custom_table2" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
					   <thead>
                        <tr>
						  <th scope="col" ></th>
                          <th scope="col" >인원</th>
                          <th scope="col" >입금단가금액</th>
                          <th scope="col">입금액</th>
                       </tr>
                       </thead>
					   <tbody class="innertr2">
						
					<?php if( $rst5_cnt<=0){  ?>
                         <tr class="basic-class2" param ="tr-parent2">
                            <td><button type="button" class="btn btn-default btn-xs js-addPlusRow2"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td><input type="text" class="form-control ecnt" name="ecnt[]" placeholder="인원" value=""></td>
                            <td><input type="text" class="form-control text-right etctot" name="etcamt[]" aria-label="금액" value="0.00"/></td>
							<td width='*'>
							   <div class="col-sm-10">
							          <input type="text" class="form-control text-right etcttamt" name="etcttamt[]" aria-label="금액" value="0.00"/>
														

									 
							   </div>
								<div class="col-sm-1 hide button-minus">
								<button type="button" class="btn btn-default btn-xs js-removeHotelButton2"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
								</div>   
							
							</td>
						</tr>
					<?php } else {

						$qq=0 ;  $inputetcamt=0;
                        while($row5 = mysql_Fetch_assoc($rst5)){ 
                            
                            $inputetcamt = $inputetcamt+ $row5['e_tot'];
				    ?>
						<tr class="basic-class2" param ="tr-parent2">
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst5_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow2"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td><input type="text" class="form-control ecnt" name="ecnt[]" placeholder="인원" value="<?=$row5['e_cnt']?>"></td>
                            <td><input type="text" class="form-control text-right etctot" name="etcamt[]" aria-label="금액" value="<?=$row5['e_amt']?>"/></td>
							<td width='*'>
							   <div class="col-sm-10">
							          <input type="text" class="form-control text-right etcttot" name="etcttamt[]" aria-label="금액" value="<?=$row5['e_tot']?>"/>
														

									 
							   </div>
								<div class="col-sm-1 hide button-minus">
								<button type="button" class="btn btn-default btn-xs js-removeHotelButton2"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
								</div>   
							
							</td>
						</tr>


                     <?php }} ?>
						<tr>
                          <td bgcolor="#5cb85c">총입금액</td>
                          <td><input type="hidden" name="etctttotamt" id="etctttotamt" value="">&nbsp;</td>
                          <td align="right" class="etctotamt">0.00</td>
                          <td align="right" style="padding-right:7%;" class="etctttotamt">0.00</td>
                          
                        </tr>

					  </tbody>
				</table>
                <div class="row"><div class="col-sm-12 text-center formHeader fullWidth"><b>가이드입금액</b></div></div>
				<table id="custom_table3" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
					   <thead>
                        <tr>
						  <th scope="col" width="10%"></th>
                          <th scope="col" width="20%">인원</th>
                          <th scope="col" width="20%">입금금액</th>
                          <th scope="col" width="*">비고</th>
                       </tr>
                       </thead>
					   <tbody class="innertr3">
						
					<?php if( $rst7_cnt<=0){  ?>
                         <tr class="basic-class3" param ="tr-parent3">
                            <td width="10%"><button type="button" class="btn btn-default btn-xs js-addPlusRow3"><span class="glyphicon glyphicon-plus" aria-hidden="true">로컬</span></button></td>
                            <td width="20%"><input type="number" class="form-control lpcnt" name="lpcnt[]" placeholder="인원" value="0"></td>
                            <td width="20%"><input type="text" class="form-control text-right lamt" name="lamt[]" aria-label="금액" value="0.00"/></td>
							<td width='*'>
							   <div class="col-sm-10">
							          <input type="text" class="form-control lmemo" name="lmemo[]" aria-label="메모" value=""/>
														

									 
							   </div>
								<div class="col-sm-1 hide button-minus">
								<button type="button" class="btn btn-default btn-xs js-removeHotelButton3"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
								</div>   
							
							</td>
						</tr>
						
					<?php } else {

						$qq=0 ;  $inputlocamt=0;
                        while($row7 = mysql_Fetch_assoc($rst7)){ 
                            
                            $inputlocamt = $inputlocamt+ $row7['l_amt'];
							$inputloccnt = $inputloccnt+ $row7['lp_cnt'];
				    ?>
						<tr class="basic-class2" param ="tr-parent2">
                            <tr class="basic-class3" param ="tr-parent3">
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst7_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow3"><span class="glyphicon glyphicon-plus" aria-hidden="true">로컬</span></button></td>                            <?php }?>
                            <td width="20%"><input type="number" class="form-control ipcnt" name="lpcnt[]" placeholder="인원" value="<?=$row7['lp_cnt']?>"></td>
                            <td width="20%"><input type="text" class="form-control text-right lamt" name="lamt[]" aria-label="금액" value="<?=$row7['l_amt']?>"/></td>
							<td width='*'>
							   <div class="col-sm-10">
							          <input type="text" class="form-control lmemo" name="lmemo[]" aria-label="메모" value="<?=$row7['l_memo']?>"/>
														

									 
							   </div>
								<div class="col-sm-1  button-minus">
								<button type="button" class="btn btn-default btn-xs js-removeHotelButton3"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
								</div>   
							
							</td>
						</tr>


                     <?php $qq++;}} ?>
						

					  </tbody>
				</table>
				<table id="custom_table4" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
					   
					   <tbody class="innertr4">
						
					<?php if( $rst71_cnt<=0){  ?>
                         <tr class="basic-class4" param ="tr-parent4">
                            <td width="10%"><button type="button" class="btn btn-default btn-xs js-addPlusRow4"><span class="glyphicon glyphicon-plus" aria-hidden="true">인바운드</span></button></td>
                            <td width="20%"><input type="number" class="form-control ipcnt" name="ipcnt[]" placeholder="인원" value="0"></td>
                            <td width="20%"><input type="text" class="form-control text-right iamt" name="iamt[]" aria-label="금액" value="0.00"/></td>
							<td width='*'>
							   <div class="col-sm-10">
							          <input type="text" class="form-control  imemo" name="imemo[]" aria-label="메모" value=""/>
														

									 
							   </div>
								<div class="col-sm-1 hide button-minus">
								<button type="button" class="btn btn-default btn-xs js-removeHotelButton3"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
								</div>   
							
							</td>
						</tr>
						
					<?php } else {

						$qq=0 ;  $inputinbamt=0;
                        while($row71 = mysql_Fetch_assoc($rst71)){ 
                            
                            $inputinbcamt = $inputinbcamt+ $row7l['i_amt'];
							$inputinbcnt = $inputinbcnt+ $row7l['ip_cnt'];
				    ?>
						<tr class="basic-class4" param ="tr-parent4">
                          
                            <?php if($qq ==0 ) { ?>
                            <td width="10%" class="active text-center formHeader" rowspan="<?=$rst71_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow4"><span class="glyphicon glyphicon-plus" aria-hidden="true">인바운드</span></button></td>                         <?php }?>
                            <td width="20%"><input type="number" class="form-control ipcnt" name="ipcnt[]" placeholder="인원" value="<?=$row71['ip_cnt']?>"></td>
                            <td width="20%"><input type="text" class="form-control text-right iamt" name="iamt[]" aria-label="금액" value="<?=$row71['i_amt']?>"/></td>
							<td width='*'>
							   <div class="col-sm-10">
							          <input type="text" class="form-control imemo" name="imemo[]" aria-label="메모" value="<?=$row71['i_memo']?>"/>
														

									 
							   </div>
								<div class="col-sm-1  button-minus">
								<button type="button" class="btn btn-default btn-xs js-removeHotelButton3"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
								</div>   
							
							</td>
						</tr>


                     <?php $qq++; }} ?>

					  </tbody>
				</table>
				<table id="custom_table5" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
					   
					   <tbody class="innertr5">
						
						<?php if( $rst72_cnt<=0){  ?>
                         <tr class="basic-class5" param ="tr-parent5">
                            <td width="10%"><button type="button" class="btn btn-default btn-xs js-addPlusRow5"><span class="glyphicon glyphicon-plus" aria-hidden="true">가이드지원금</span></button></td>
                            <td width="20%"><input type="number" class="form-control gpcnt" name="gpcnt[]" placeholder="인원" value="0"></td>
                            <td width="20%"><input type="text" class="form-control text-right gamt" name="gamt[]" aria-label="금액" value="0.00"/></td>
							<td width='*'>
							   <div class="col-sm-10">
							          <input type="text" class="form-control  gmemo" name="gmemo[]" aria-label="메모" value=""/>
														

									 
							   </div>
								<div class="col-sm-1 hide button-minus">
								<button type="button" class="btn btn-default btn-xs js-removeHotelButton3"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
								</div>   
							
							</td>
						</tr>
						
						<?php } else {

						$qq=0 ;  $inputsuamt=0; $inputspamt=0;
                        while($row72 = mysql_Fetch_assoc($rst72)){ 
                            
                            $inputsuamt = $inputsuamt+ $row72['s_amt'];
							$inputspamt = $inputspamt+ $row72['sp_cnt'];
				    ?>
						<tr class="basic-class5" param ="tr-parent5">
                          
                            <?php if($qq ==0 ) { ?>
                            <td width="10%" class="active text-center formHeader" rowspan="<?=$rst72_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow5"><span class="glyphicon glyphicon-plus" aria-hidden="true">가이드지원금</span></button></td>                       
							<?php }?>
                            <td width="20%"><input type="text" class="form-control gpcnt" name="gpcnt[]" placeholder="인원" value="<?=$row72['sp_cnt']?>"></td>
                            <td width="20%"><input type="text" class="form-control text-right gamt" name="gamt[]" aria-label="금액" value="<?=$row72['s_amt']?>"/></td>
							<td width='*'>
							   <div class="col-sm-10">
							          <input type="text" class="form-control gmemo" name="gmemo[]" aria-label="메모" value="<?=$row72['g_memo']?>"/>
														

									 
							   </div>
								<div class="col-sm-1  button-minus">
								<button type="button" class="btn btn-default btn-xs js-removeHotelButton3"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
								</div>   
							
							</td>
						</tr>


                     <?php $qq++; }} 
					       $guidetotamt =  $inputlocamt+$inputinbcamt+$inputsuamt;
						   $guidetotcnt =  $inputloccnt+$inputinbcnt+$inputspcnt;
					       $guidetotal = $guidetotcnt * $guidetotamt; 
					 ?>

						<tr>
                          <td bgcolor="#5cb85c" width="10%">총합계</td>
						  <td width="20%" class="guidetotcnt"><?=$guidetotcnt?></td>
                          <td width="20%" class="guidetotamt"><?=number_format($guidetotamt,2)?></td>
                          <td  width="*" class="guidetotal">총합계 = <?=$guidetotal ?> </td>
                          <td><input type="hidden" name="guidetotal" value="<?=$guidetotal ?> "></td>
                          
                        </tr>

					  </tbody>
				</table>
				<!---입장비-->
				<table id="custom_table6" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">

				     <?php if($rst3_cnt <=0) {?>
                        <tr class="basic-class6" param ="tr-parent6"> 
                            <td class="active text-center formHeader">입장비&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow6"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">입장지명</span>
                                            <select class="form-control" name="nameSelect[]">
                                                <option selected>- 선택 -</option>
													<?php 
													mysql_data_seek($guide_g01, 0);
													while($row1 = mysql_Fetch_assoc($guide_g01)){
														
													?>
														<option value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
													<?php }?>    
                                            </select>
                                        </div>    
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">인원</span>
                                            <input type="text" name="person[]" class="form-control en_person" aria-label="인원" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">바우처수량</span>
                                            <input type="text" name="vea[]" class="form-control text-right en_cost" aria-label="바우처수량" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">현지수금</span>
                                            <input type="text" name="totalAmount[]" class="form-control text-right en_totalprice" aria-label="현지수금" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="admission_seq" value="0">
                        </tr>
                        
                        <?php }else{
                        $qq=0 ;  
                        while($row33 = mysql_Fetch_assoc($rst3)){ 
                            $en_b1 = $en_b1 + $row33['g_cnt'];
                            $en_b2 = $en_b2 + $row33['g_amt'];
                        ?> 
                        <tr class="basic-class4" param ="tr-parent6"> 
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst3_cnt?>">입장비&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">입장지명</span>
                                            <select class="form-control" name="nameSelect[]">
                                            <?php 
                                            mysql_data_seek($guide_g01, 0);
                                            while($row1 = mysql_Fetch_assoc($guide_g01)){
                                            ?>  
                                                <option <?php if ($row33['g_ticket'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
                                                 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                            <?php }?>    
                                            </select>
                                        </div>    
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">인원</span>
                                            <input type="text" name="person[]" class="form-control en_person" aria-label="인원" value="<?=$row33['g_cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">바우처수량</span>
                                            <input type="text" name="vea[]" class="form-control text-right en_cost" aria-label="바우처수량" value="<?=$row33['v_ea']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">현지수금</span>
                                            <input type="text" name="totalAmount[]" class="form-control text-right en_totalprice" aria-label="현지수금" value="<?=$row33['g_amt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>

                            <input type="hidden" name="admission_seq" value="<?=$row33['seq_no']?>">
                        </tr>
                        
                        <?php $qq++;}}?>
                        <tr>
                            <td class="active text-center formHeader">입장비 합계</td>
                            <td colspan="5">
                               <div class="row"> 
                                  <div class="col-sm-8">
                                      <div class="col-sm-4 en_totalperson">총인원 : <?=$en_b1?> </div>
                                      <div class="col-sm-4 en_totalsum">현지수금 총액 : $<?=number_format($en_b2,2)?> </div>
                                   </div>    
                                </div>    
                            </td>
                        </tr>

				</table>

				<!---회사행사총지출-->
				<div class="row"><div class="col-sm-12 text-center formHeader fullWidth"><b>회사행사총지출</b></div></div>
				<table id="custom_table7" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">

				     <?php if($rst10_cnt <=0) {?>
                        <tr class="basic-class7" param ="tr-parent7"> 
                            <td class="active text-center formHeader"><button type="button" class="btn btn-default btn-xs js-addPlusRow7"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">이름</span>
                                            <input type="text" name="co_name[]" class="form-control coname" aria-label="이름" value=""/>
                                        </div>
                                    </div>
									<div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">인원</span>
                                            <input type="text" name="co_person[]" class="form-control text-right co_person" aria-label="인원" value=""/>
                                        </div>
                                    </div>
									<div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="exp_amount[]" class="form-control text-right exp_amount" aria-label="금액" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2"> 
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="ct_amt[]" class="form-control text-right ct_amt" aria-label="합계금액" value="0.00"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">결제방법</span>
                                            <input type="text" name="ct_type[]" class="form-control  t_amt" aria-label="결제방법" value=""/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="co_seq" value="0">
                        </tr>
                        
                        <?php }else{
                        $qq=0 ;  
                        while($row10 = mysql_Fetch_assoc($rst10)){ 
                            
                            $inputamt2 = $inputamt2 + $row10['c_tot'];
							
                        ?> 
                        <tr class="basic-class7" param ="tr-parent7"> 
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst10_cnt?>"><button type="button" class="btn btn-default btn-xs js-addPlusRow7"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">이름</span>
                                            <input type="text" name="co_name[]" class="form-control coname" aria-label="이름" value="<?=$row10['comp_name']?>"/>
                                        </div>
                                    </div>
									<div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">인원</span>
                                            <input type="text" name="co_person[]" class="form-control co_person text-right" aria-label="인원" value="<?=$row10['c_cnt']?>"/>
                                        </div>
                                    </div>
									<div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="exp_amount[]" class="form-control text-right exp_amount" aria-label="금액" value="<?=$row10['c_amt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="ct_amt[]" class="form-control text-right ct_amt" aria-label="합계금액" value="<?=$row10['c_tot']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">결제방법</span>
                                            <input type="text" name="ct_type[]" class="form-control  t_amt" aria-label="결제방법" value="<?=$row10['c_type']?>"/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>

                            <input type="hidden" name="co_seq" value="<?=$row10['seq_no']?>">
                        </tr>
                        
                        <?php $qq++;}}?>
                        <tr>
                            <td class="active text-center formHeader">총 합계</td>
                            <td colspan="5">
                               <div class="row"> 
                                  <div class="col-sm-10	">
                                      <div class="col-sm-3"></div>
									  <div class="col-sm-3"></div>
									  <div class="col-sm-2"></div>
                                      <div class="col-sm-3 t_totalsum">전체총액 : $<?=number_format($inputamt2,2)?>
									  </div>
									  
                                   </div>    
                                </div>    
                            </td>
                        </tr>

				</table>
				<!---가이드행사총지출-->
				<div class="row"><div class="col-sm-12 text-center formHeader fullWidth"><b>가이드행사총지출</b></div></div>
				<table id="custom_table8" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">

				     
              
					 
					 
					 <?php if($rst12_cnt <=0) {  ?>
                        <tr class="basic-class10" param ="tr-parent10"> 
                            <td class="active text-center formHeader">가이드FEE&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="fe_date[]" class="form-control cc_date" aria-label="일자" value=""/>
                                        </div>    
                                    </div>  
									<div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon"></span>
                                            
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="fe_exp[]" class="form-control fe_exp" aria-label="금액" value="0.00"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="fe_amt[]" class="form-control text-right fe_amt" aria-label="합계금액" value="0"/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="fe_seq" value="0">
                        </tr>
                        
                        <?php }else{
                        $qq=0 ;  
                        while($row12 = mysql_Fetch_assoc($rst12)){ 
                            
                            $inputamt5 = $inputamt5+ $row12['event_totamt'];
                        ?> 
                        <tr class="basic-class10" param ="tr-parent10"> 
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst12_cnt?>">가이드FEE&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="fe_date[]" class="form-control cc_date" aria-label="일자" value="<?=$row12['event_date']?>"/>

                                        </div>    
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon"></span>
                                           
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="fe_exp[]" class="form-control text-right fe_exp"  value="<?=$row12['event_amt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="fe_amt[]" class="form-control text-right fe_amt" aria-label="현지수금" value="<?=$row12['event_totamt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1  button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>

                            <input type="hidden" name="fe_seq" value="<?=$row12['seq_no']?>">
                        </tr>
                        
                     <?php $qq++;}}?>

                     <?php if($rst11_cnt <=0) {?>
                        <tr class="basic-class8" param ="tr-parent8"> 
                            <td class="active text-center formHeader">가이드팁수금&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="tip_date[]" class="form-control tip_date" aria-label="일자" value=""/>
                                        </div>    
                                    </div>  
									<div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">인원</span>
                                            <input type="text" name="tip_person[]" class="form-control tip_person" aria-label="인원" value="<?=$row33['c_cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="tip_exp[]" class="form-control tip_exp" aria-label="금액" value="15"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="tip_amt[]" class="form-control text-right tip_amt" aria-label="합계금액" value="0"/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="admission_seq" value="0">
                        </tr>
                        
                        <?php }else{
                        $qq=0 ;  
                        while($row11 = mysql_Fetch_assoc($rst11)){ 
                            
                            $inputamt3 = $inputamt3+ $row11['event_totamt'];
                        ?> 
                        <tr class="basic-class8" param ="tr-parent8"> 
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst11_cnt?>">가이드팁수금&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="tip_date[]" class="form-control tip_date" aria-label="일자" value="<?=$row11['event_date']?>"/>

                                        </div>    
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">인원</span>
                                            <input type="text" name="tip_person[]" class="form-control tip_person" aria-label="인원" value="<?=$row11['event_cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="tip_exp[]" class="form-control text-right tip_exp"  value="<?=$row11['event_amt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="tip_amt[]" class="form-control text-right tip_amt" aria-label="현지수금" value="<?=$row11['event_totamt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1  button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>

                            <input type="hidden" name="exp_seq" value="<?=$row11['seq_no']?>">
                        </tr>
                        
                     <?php $qq++;}}?>

					 <?php if($rst14_cnt <=0) {?>
                        <tr class="basic-class9" param ="tr-parent9"> 
                            <td class="active text-center formHeader">회사지원금&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="cc_date[]" class="form-control cc_date" aria-label="일자" value=""/>
                                        </div>    
                                    </div>  
									<div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon"></span>
                                            
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="cc_exp[]" class="form-control cc_exp" aria-label="금액" value="0.00"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="cc_amt[]" class="form-control text-right cc_amt" aria-label="합계금액" value="0"/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="cc_seq" value="0">
                        </tr>
                        
                        <?php }else{
                        $qq=0 ;  
                        while($row14 = mysql_Fetch_assoc($rst14)){ 
                            
                            $inputamt4 = $inputamt4+ $row14['event_totamt'];
                        ?> 
                        <tr class="basic-class9" param ="tr-parent9"> 
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst14_cnt?>">회사지원금&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="cc_date[]" class="form-control cc_date" aria-label="일자" value="<?=$row14['event_date']?>"/>

                                        </div>    
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon"></span>
                                           
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="cc_exp[]" class="form-control text-right cc_exp"  value="<?=$row14['event_amt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="cc_amt[]" class="form-control text-right cc_amt" aria-label="현지수금" value="<?=$row14['event_totamt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1  button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>

                            <input type="hidden" name="cc_seq" value="<?=$row11['seq_no']?>">
                        </tr>
                        
                     <?php $qq++;}}?>

					 <?php if($rst13_cnt <=0) {  ; ?>
                        <tr class="basic-class11" param ="tr-parent11"> 
                            <td class="active text-center formHeader">식사TIP&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="me_date[]" class="form-control me_date" aria-label="일자" value=""/>
                                        </div>    
                                    </div>  
									<div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">인원</span>
                                            <input type="text" name="me_person[]" class="form-control me_person" aria-label="인원" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="me_exp[]" class="form-control me_exp" aria-label="금액" value="2.00"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="me_amt[]" class="form-control text-right me_amt" aria-label="합계금액" value="0.00"/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="fe_seq" value="0">
                        </tr>
                        
                        <?php }else{
                        $qq=0 ;  
                        while($row13 = mysql_Fetch_assoc($rst13)){ 
                            
                            $inputamt6 = $inputamt6+ $row11['event_totamt'];
                        ?> 
                        <tr class="basic-class11" param ="tr-parent11"> 
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst13_cnt?>">식사TIP&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="me_date[]" class="form-control me_date" aria-label="일자" value="<?=$row13['event_date']?>"/>

                                        </div>    
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">인원</span>
                                            <input type="text" name="me_person[]" class="form-control me_person" aria-label="인원" value="<?=$row13['c_cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">금액</span>
                                            <input type="text" name="me_exp[]" class="form-control text-right me_exp"  value="<?=$row13['event_amt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">합계금액</span>
                                            <input type="text" name="me_amt[]" class="form-control text-right me_amt" aria-label="현지수금" value="<?=$row13['event_totamt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1  button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>

                            <input type="hidden" name="me_seq" value="<?=$row13['seq_no']?>">
                        </tr>
                        
                     <?php $qq++;}}?>
					 <?php $inputTotamt = $inputamt3+$inputamt4+$inputamt5+$inputamt6; ?>
                        <tr>
                            <td class="active text-center formHeader">총 합계</td>
                            <td colspan="5">
                               <div class="row"> 
                                  <div class="col-sm-10	">
                                      <div class="col-sm-4"></div>
									  <div class="col-sm-4"></div>
                                      <div class="col-sm-4 t_totalsum1">전체총액 : $<?=number_format($inputTotamt,2)?> </div>
                                   </div>    
                                </div>    
                            </td>
                        </tr>
                     <?php if($rst16_cnt <=0) {?>
                        <tr class="basic-class12" param ="tr-parent12"> 
                            <td class="active text-center formHeader">가이드 업무시간<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="gu_date[]" class="form-control me_date" aria-label="일자" value=""/>
                                        </div>    
                                    </div>  
									<div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">업무시간</span>
                                            <input type="number" name="gu_time[]" class="form-control gu_time" aria-label="업무시간" value=""/>
                                        </div>
                                    </div>
                                                                      
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="gu_seq" value="0">
                        </tr>
                        
                        <?php }else{
                        $qq=0 ;  
                        while($row16 = mysql_Fetch_assoc($rst16)){ 
                            
                            
                        ?> 
                        <tr class="basic-class12" param ="tr-parent12"> 
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst16_cnt?>">가이드 업무시간&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow8"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">일차</span>
                                            <input type="text" name="gu_date[]" class="form-control gu_date" aria-label="일자" value="<?=$row16['work_seq']?>"/>

                                        </div>    
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">업무시간</span>
                                            <input type="number" name="gu_time[]" class="form-control gu_time" aria-label="업무시간" value="<?=$row16['work_time']?>"/>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>

                            <input type="hidden" name="gu_seq" value="<?=$row16['seq_no']?>">
                        </tr>
                        
                     <?php $qq++;}}?>
				</table>
				<!---옵션가격정산-->
				<div class="row"><div class="col-sm-12 text-center formHeader fullWidth"><b>옵션가격정산</b></div></div>
				<table id="custom_table9" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
					   <thead>
                        <tr>
						  <tr>  
							  <th scope="col" width="1%"></th>
							  <th scope="col" width="12%">옵션명</th>
							  <th scope="col" width="13%">선택관광정산기준</th>
							  <th scope="col" width="5%">인원</th>
							  <th scope="col" width="10%">원가/P</th>
							  <th scope="col" width="10%">원가총액</th>
							  <th scope="col" width="10%">옵션가</th>
							  <th scope="col" width="10%">옵션가총액</th>
							  <th scope="col" width="10%">차액</th>
							  <th scope="col" width="10%">회사수익</th>
							  <th scope="col" width="10%">가이드수익</th>
							  <th scope="col" width="1%"></th>
                                      
                       </tr>
                       </thead>
					   <tbody class="innertr">
						
						    <?php if($rst15_cnt <=0) { ?>
									<tr class="basic-class" param ="tr-parent">
									  <td>&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow9"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
									  <td>
										  <select class="form-control optnm" name="optionName[]">
											<option selected>- 선택 -</option>
											<?php 
											mysql_data_seek($guide_g02, 0);
											while($row1 = mysql_Fetch_assoc($guide_g02)){ ?>  
											<option value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
											<?php }?>
										  </select>
									  </td>
									  <td>
										  <select class="form-control optset" name="assignGuideLine[]">
											<option value='' selected>- 선택 -</option>
											<option value="COM" >수금된 선택관광비용이 회사에 있는경우</option>
											<option value="GUI" >수금된 선택관광비용이 가이드에게 있는경우</option>
										  </select>
									  </td>
									  <td><input type="text" class="form-control optperson" name="optPerson[]" aria-label="인원" value="1"/></td>
									  <td><input type="text" class="form-control text-right optcost" name="optCost[]" aria-label="원가/P" value=""/></td>
									  <td><input type="text" class="form-control text-right opttotalamoount" name="optTotalAmount[]" aria-label="원가총액" value=""/></td>
									  <td><input type="text" class="form-control text-right optprice" name="optPrice[]" aria-label="옵션가" value=""/></td>
									  <td><input type="text" class="form-control text-right opttotalprice" name="optTotalPrice[]" aria-label="옵션가총액" value=""/></td>
									  <td><input type="text" class="form-control text-right optdiffamount" name="optDiffAmount[]" aria-label="차액" value=""/></td>
									  <td><input type="text" class="form-control text-right optprofit" name="optProfit[]" aria-label="회사수익" value=""/></td>
									  <td><input type="text" class="form-control text-right optguideprofit" name="optGuideProfit[]" aria-label="가이드수익" value=""/></td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="opt_seq" value="0">
									</tr>

									<?php  }else{ 
									$zz=0;
									while($datarow = mysql_Fetch_assoc($rst15)){  
										$opt_b1 = $opt_b1 + $datarow['o_cnt'];
										$opt_b2 = $opt_b2 + $datarow['o_price'];
										$opt_b3 = $opt_b3 + $datarow['o_pricetot'];
										$opt_b4 = $opt_b4 + $datarow['o_cprice'];
										$opt_b5 = $opt_b5 + $datarow['o_cpricetot'];
										$opt_b6 = $opt_b6 + $datarow['o_diffamt'];
										$opt_b7 = $opt_b7 + $datarow['o_cprofit'];
										$opt_b8 = $opt_b8 + $datarow['o_gprofit'];

									?>
									<tr class="basic-class" param ="tr-parent">
									  <?php if($zz==0) { ?>
									  <td rowspan="<?=$rst15_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow9"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
									  <?php }?>
									  <td>
										  <select class="form-control optnm" name="optionName[]">
											<?php 
											mysql_data_seek($guide_g02, 0); 
											while($row1 = mysql_Fetch_assoc($guide_g02)){ ?>  
											<option <?php if ($datarow['option_code'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
											 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
											<?php }?>
										  </select>
									  </td>

									  <td>
										  <select class="form-control" name="assignGuideLine[]">
											<option value='' selected>- 선택 -</option>
											<option value="COM"  <?php if ($datarow['base_set'] =="COM") { ?> selected <?php } ?>>수금된 선택관광비용이 회사에 있는경우</option>
											<option value="GUI" <?php if ($datarow['base_set'] == "GUI") { ?> selected <?php } ?>>수금된 선택관광비용이 가이드에게 있는경우</option>
										  </select>
									  </td>
									  <td><input type="text" class="form-control optperson" name="optPerson[]" aria-label="인원" value="<?=$datarow['o_cnt']?>"/></td>
									  <td><input type="text" class="form-control text-right optcost" name="optCost[]" aria-label="원가/P" value="<?=$datarow['o_price']?>"/></td>
									  <td><input type="text" class="form-control text-right opttotalamoount" name="optTotalAmount[]" aria-label="원가총액" value="<?=$datarow['o_pricetot']?>"/></td>
									  <td><input type="text" class="form-control text-right optprice" name="optPrice[]" aria-label="옵션가" value="<?=$datarow['o_cprice']?>"/></td>
									  <td><input type="text" class="form-control text-right opttotalprice" name="optTotalPrice[]" aria-label="옵션가총액" value="<?=$datarow['o_cpricetot']?>"/></td>
									  <td><input type="text" class="form-control text-right optdiffamount" name="optDiffAmount[]" aria-label="차액" value="<?=$datarow['o_diffamt']?>"/></td>
									  <td><input type="text" class="form-control text-right optprofit" name="optProfit[]" aria-label="회사수익" value="<?=$datarow['o_cprofit']?>"/></td>
									  <td><input type="text" class="form-control text-right optguideprofit" name="optGuideProfit[]" aria-label="가이드수익" value="<?=$datarow['o_gprofit']?>"/></td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="opt_seq" value="<?=$datarow['seq_no']?>">
									</tr>

                            <?php $zz++;}} ?>             

									<tr>
									  <td colspan="3">합계<input type="hidden" name="optsum5" value="<?=number_format($opt_b5,2)?>"></td>
									  <td class="optsum1"><?=$opt_b1?></td>
									  <td class="text-right optsum2"><?=number_format($opt_b2,2)?></td>
									  <td class="text-right optsum3"><?=number_format($opt_b3,2)?></td>
									  <td class="text-right optsum4"><?=number_format($opt_b4,2)?></td>
									  <td class="text-right optsum5"><?=number_format($opt_b5,2)?></td>
									  <td class="text-right optsum6"><?=number_format($opt_b6,2)?></td>
									  <td class="text-right optsum7"><?=number_format($opt_b7,2)?></td>
									  <td class="text-right optsum8"><?=number_format($opt_b8,2)?></td>
									</tr>
                                   

					  </tbody>
				</table>

				<!---쇼핑정산-->
				<div class="row"><div class="col-sm-12 text-center formHeader fullWidth"><b>쇼핑정산</b></div></div>
				<table id="custom_table10" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
					   <thead>
							<tr>
								
								  <th scope="col" width="1%"></th>
								  <th scope="col">내용</th>
								  <th scope="col">판매날짜</th>
								  <th scope="col">판매금액</th>
								  <th scope="col">판매량</th>
								  <th scope="col">쇼핑수입</th>
								  <th scope="col" width="1%"></th>
										  
						   </tr>
                       </thead>
					   <tbody class="innertr10">
						
						    <?php if($rst8_cnt <=0) { ?>
									<tr class="basic-class13" param ="tr-parent13">
									 <td>&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow10"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
									 <td>
										  <select class="form-control shoppingSelect" name="shoppingSelect[]">
											<option selected>- 선택 -</option>
											<?php 
											mysql_data_seek($guide_g04, 0);
											while($row1 = mysql_Fetch_assoc($guide_g04)){ ?>  
												<option value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
											<?php }?>
										  </select>
									  </td>
									  <td><input type="date" name="saleDate[]" class="form-control text-right saleTotalAmount" aria-label="판매날짜" value=""/></td>
									  <td><input type="text" name="saleamount[]" class="form-control text-right saleamount" aria-label="판매금액" value=""/></td>
									  <td><input type="text" name="salecnt[]" class="form-control text-right salecnt" aria-label="판매량" value=""/></td>
									  <td><input type="text" name="shoppingProfit[]" class="form-control text-right shoppingProfit" aria-label="쇼핑수입" value=""/></td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="shopp_seq" value="0">
									</tr>
							<?php }else{
									$ss=0;$aaa33 = 0;
									while($datarow = mysql_Fetch_assoc($rst8)){
										$sales_amt1 = $sales_amt1 + $datarow['opt_amt'];
										$sales_amt2 = $sales_amt2 + $datarow['sale_cnt'];
										$sales_amt3 = $sales_amt3 + $datarow['shop_income'];
										

										if($datarow['shop_code'] =='N04|01') $aaa33 = $aaa33 + $datarow['opt_amt'];
									?>

									<tr class="basic-class13" param ="tr-parent13">
									 <?php if($ss==0) { ?>   
									 <td rowspan="<?=$rst8_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow10"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
									 <?php }?>
									 <td>
										  <select class="form-control shoppingSelect" name="shoppingSelect[]">
											<option selected>- 선택 -</option>
											<?php 
											mysql_data_seek($guide_g04, 0);
											while($row1 = mysql_Fetch_assoc($guide_g04)){ ?>  
											<option <?php if ($datarow['opt_name'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
											 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
											<?php }?>
										  </select>
									  </td>
									  <td><input type="date" name="saleDate[]" class="form-control text-right saleTotalAmount" aria-label="판매날짜" value="<?=$datarow['opt_date']?>"/></td>
									  <td><input type="text" name="saleamount[]" class="form-control text-right homeshoppingcom" aria-label="판매금액" value="<?=$datarow['opt_amt']?>"/></td>
									  <td><input type="text" name="salecnt[]" class="form-control text-right companyProfit" aria-label="판매량" value="<?=$datarow['sale_cnt']?>"/></td>
									  <td><input type="text" name="shoppingProfit[]" class="form-control text-right shoppingProfit" aria-label="쇼핑수입" value="<?=$datarow['shop_income']?>"/></td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="shopp_seq" value="<?=$datarow['seq_no']?>">
									</tr>
                            <?php $ss++;}}?>

								<tr>
								  <td>합계</td>
								  <td></td>
								  <td class="text-right salesamt1"><input type="hidden" name="shopp_amt" value="<?=$sales_amt3?>"></td>
								  <td class="text-right salesamt2"><?=number_format($sales_amt1,2)?></td>
								  <td class="text-right salesamt3"><?=number_format($sales_amt2,2)?></td>
								  <td class="text-right salesamt4"><?=number_format($sales_amt3,2)?></td>
								</tr>
								
					  </tbody>
				</table>
				
				<!---차량정산-->
				<div class="row"><div class="col-sm-12 text-center formHeader fullWidth"><b>차량정산</b></div></div>
				<table id="custom_table11" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
					   <thead>
							<tr>
								
								  <th scope="col" width="1%"></th>
								  <th scope="col">구분</th>
								  <th scope="col">일차</th>
								  <th scope="col">회사명</th>
								  <th scope="col">기사명</th>
								  <th scope="col">운행시간</th>
								  <th scope="col">기사팁</th>
								  <th scope="col">오버타임팁</th>
								  <th scope="col">자차비</th>
								  <th scope="col">주차비</th>
								  <th scope="col">주유비</th>
								  <th scope="col">톨비</th>
								  <th scope="col">합계</th>
								  <th scope="col" width="1%"></th>
										  
						   </tr>
                       </thead>
					   <tbody class="innertr11">
						
						     <?php if($rst17_cnt <=0) {?>
									<tr class="basic-class14" param ="tr-parent14">
									 <td>&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow11"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
									 
									 <td>
										  <select class="form-control cartype" name="cartype[]">
											<option selected>- 선택 -</option>
											<?php 
											mysql_data_seek($guide_g05, 0);
											while($row1 = mysql_Fetch_assoc($guide_g05)){ ?>  
											<option <?php if ($datarow['opt_name'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
											 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
											<?php }?>
										  </select>
									  </td>
									  <td><input type="number" name="cday[]" class="form-control cday" aria-label="일차" value=""/></td>
									  <td><input type="text" name="compname[]" class="form-control compname" aria-label="회사명" value=""/></td>
									  <td><input type="text" name="drname[]" class="form-control  drname" aria-label="기사명" value=""/></td>
									  <td><input type="text" name="drtime[]" class="form-control  drtime" aria-label="운행시간" value=""/></td>
									  <td><input type="text" name="drtip[]" class="form-control text-right drtip" aria-label="기사팁" value=""/></td>
									  <td><input type="text" name="drovtip[]" class="form-control text-right drovtip" aria-label="기사오버타임팁" value=""/></td>
									  <td><input type="text" name="selfcar[]" class="form-control text-right selfcar" aria-label="자차비" value=""/></td>
									  <td><input type="text" name="parkexp[]" class="form-control text-right parkexp" aria-label="주차비" value=""/></td>
									  <td><input type="text" name="fuelexp[]" class="form-control text-right fuelexp" aria-label="주유비" value=""/></td>
									  <td><input type="text" name="tollexp[]" class="form-control text-right tollexp" aria-label="톨비" value=""/></td>
									   <td><input type="text" name="totexp[]" class="form-control text-right totexp" aria-label="합계" value=""/></td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="car_seq" value="0">
									</tr>
							<?php }else{
									$ss=0;
									$totexpg = 0;
									while($datarow = mysql_Fetch_assoc($rst17)){
										$totexpg = $totexpg + $datarow['sub_tot'];
										
										
									?>

									<tr class="basic-class14" param ="tr-parent14">
									 <?php if($ss==0) { ?>   
									 <td rowspan="<?=$rst17_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow11"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
									 <?php }?>
									 <td>
										  <select class="form-control cartype" name="cartype[]">
											<?php 
											mysql_data_seek($guide_g05, 0);
											while($row1 = mysql_Fetch_assoc($guide_g05)){ ?>  
											<option <?php if ($datarow['opt_name'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
											 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
											<?php }?>
										  </select> 
									  </td>
									  <td><input type="number" name="cday[]" class="form-control cday" aria-label="일차" value="<?=$datarow['car_day']?>"/></td>
									  <td><input type="text" name="compname[]" class="form-control compname" aria-label="회사명" value="<?=$datarow['comp_name']?>"/></td>
									  <td><input type="text" name="drname[]" class="form-control  drname" aria-label="기사명" value="<?=$datarow['driver_nm']?>"/></td>
									  <td><input type="text" name="drtime[]" class="form-control  drtime" aria-label="운행시간" value="<?=$datarow['drive_time']?>"/></td>
									  <td><input type="text" name="drtip[]" class="form-control text-right drtip" aria-label="기사팁" value="<?=$datarow['driver_tip']?>"/></td>
									  <td><input type="text" name="drovtip[]" class="form-control text-right drovtip" aria-label="기사오버타임팁" value="<?=$datarow['driver_ovtip']?>"/></td>
									  <td><input type="text" name="selfcar[]" class="form-control text-right selfcar" aria-label="자차비" value="<?=$datarow['self_car']?>"/></td>
									  <td><input type="text" name="parkexp[]" class="form-control text-right parkexp" aria-label="주차비" value="<?=$datarow['park_exp']?>"/></td>
									  <td><input type="text" name="fuelexp[]" class="form-control text-right fuelexp" aria-label="주차비" value="<?=$datarow['fuel_exp']?>"/></td>
									  <td><input type="text" name="tollexp[]" class="form-control text-right tollexp" aria-label="톨비" value="<?=$datarow['toll_exp']?>"/></td>
									  <td><input type="text" name="totexp[]" class="form-control text-right totexp" aria-label="합계" value="<?=$datarow['sub_tot']?>"/></td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="car_seq" value="0">
									</tr>
                            <?php $ss++;}}?>

								<tr>
								  <td>합계</td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  
								  <td class="text-right caramtsum"><?=number_format($totexpg,2)?></td>
								  <td><input type="hidden" name="caramtsum" value="0"><?=number_format($totexpg,2)?></td>
								</tr>
								
					  </tbody>
				</table>
				<!---식사-->
				<div class="row"><div class="col-sm-12 text-center formHeader fullWidth"><b>식사</b></div></div>
				<table id="custom_table12" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
					   <thead>
							<tr>
								
								  <th scope="col" width="1%"></th>
								  <th scope="col">일차</th>
								  <th scope="col">식당명</th>
								  <th scope="col">손님식사시간</th>
								  <th scope="col"></th>
								  <th scope="col">가이드식사시간</th>
								  <th scope="col"></th>
								  <th scope="col">식당팁인원</th>
								  <th scope="col">식당팁금액</th>
								  <th scope="col">식사비인원</th>
								  <th scope="col">식사비금액</th>
								  <th scope="col">식사비합계</th>
								  <th scope="col">결제타입</th>
								  <th scope="col" width="1%"></th>
										  
						   </tr>
                       </thead>
					   <tbody class="innertr12">
						
						   <?php if($rst2_cnt <=0) {?>
									<tr class="basic-class15" param ="tr-parent15">
									 <td>&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow12"><span class="glyphicon glyphicon-plus" aria-hidden="true">조식</span></button></td>
									 									 
									  <td><input type="number" name="rday[]" class="form-control rday" aria-label="일차" value=""/></td>
									  <td><input type="text" name="rname[]" class="form-control rname" aria-label="식당명" value=""/></td>
									  <td><input type="time" name="gstime[]" class="form-control  gstime" aria-label="손님식사시간" value="06:00"/></td>
									  <td><input type="time" name="getime[]" class="form-control  gstgetime"  value="06:30"/></td>
									  <td><input type="time" name="gidstime[]" class="form-control  gstime" aria-label="가이드식사시간" value="06:00"/></td>
									  <td><input type="time" name="gidetime[]" class="form-control  gidetime" aria-label="" value="06:30"/></td>
									  <td><input type="text" name="rt_cnt[]" class="form-control  rt_cnt" aria-label="식당팁인원" value=""/></td>
									  <td><input type="text" name="rt_amt[]" class="form-control  rt_amt" aria-label="식당팁금액" value=""/></td>
									  <td><input type="text" name="r_cnt[]" class="form-control  r_cnt" aria-label="식사비인원" value=""/></td>
									  <td><input type="text" name="r_amt[]" class="form-control text-right r_amt" aria-label="식사비금액" value=""/></td>
									  <td><input type="text" name="rtot_amt[]" class="form-control text-right rtot_amt" aria-label="식사비합계" value=""/></td>
									  <td>
										  <select class="form-control paym" name="paym[]">
											<option <?php if ($datarow['p_type']=="") { ?> selected <?php } ?> >- 선택 -</option>
											<option <?php if ($datarow['p_type']=="1") { ?> selected <?php } ?> value="1">크레딧카드</option>
											<option value="2" <?php if ($datarow['p_type']=="2") { ?> selected <?php } ?>>현금</option>
											<option value="3" <?php if ($datarow['p_type']=="3") { ?> selected <?php } ?>>은행송금</option>
										  </select> 
									  </td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="meal_seq" value="0">
									</tr>
						   <?php }else{
									$ss=0;
									$rtotal = 0;
									while($row1 = mysql_Fetch_assoc($rst2)){
										$rtotal = $rtotal + $row1['r_tamt'];
										
										//print_r($datarow);
										//echo $row1[r_name]."TEST";
									?>

									<tr class="basic-class15" param ="tr-parent15">
									 <?php if($ss==0) { ?>   
									 <td rowspan="<?=$rst2_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow12"><span class="glyphicon glyphicon-plus" aria-hidden="true">조식</span></button></td>
									 <?php }?>
									  <td><input type="number" name="rday[]" class="form-control rday" aria-label="일차" value="<?=$row1['g_date']?>"/></td>
									  <td><input type="text" name="rname[]" class="form-control rname" aria-label="식당명" value="<?=$row1['r_name']?>"/></td>
									  <td><input type="time" name="gstime[]" class="form-control  gstime" aria-label="손님식사시간" value="<?=$row1['g_fmealtime']?>"/></td>
									  <td><input type="time" name="getime[]" class="form-control  gstgetime"  value="<?=$row1['g_tmealtime']?>"/></td>
									  <td><input type="time" name="gidstime[]" class="form-control  gstime" aria-label="가이드식사시간" value="<?=$row1['gid_fmealtime']?>"/></td>
									  <td><input type="time" name="gidetime[]" class="form-control  gidetime" aria-label="" value="<?=$row1['gid_tmealtime']?>"/></td>
									  <td><input type="text" name="rt_cnt[]" class="form-control  rt_cnt" aria-label="식당팁인원" value="<?=$row1['r_tipcnt']?>"/></td>
									  <td><input type="text" name="rt_amt[]" class="form-control  rt_amt" aria-label="식당팁금액" value="<?=$row1['r_tipamt']?>"/></td>
									  <td><input type="text" name="r_cnt[]" class="form-control  r_cnt" aria-label="식사비인원" value="<?=$row1['r_mealcnt']?>"/></td>
									  <td><input type="text" name="r_amt[]" class="form-control text-right r_amt" aria-label="식사비금액" value="<?=$row1['r_mealamt']?>"/></td>
									  <td><input type="text" name="rtot_amt[]" class="form-control text-right rtot_amt" aria-label="식사비합계" value="<?=$row1['r_tamt']?>"/></td>
									  <td>
										  <select class="form-control paym" name="paym[]">
											<option <?php if ($datarow['p_type']=="") { ?> selected <?php } ?> >- 선택 -</option>
											<option <?php if ($datarow['p_type']=="1") { ?> selected <?php } ?> value="1">크레딧카드</option>
											<option value="2" <?php if ($datarow['p_type']=="2") { ?> selected <?php } ?>>현금</option>
											<option value="3" <?php if ($datarow['p_type']=="3") { ?> selected <?php } ?>>은행송금</option>
										  </select> 
									  </td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="meal_seq" value="0">
									</tr>
                           <?php $ss++;}}?>

								<tr>
								  <td>합계</td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td class="text-right mealTot"><?=number_format($rtotal1,2)?></td>
								  <td></td>
								  <td></td>
								</tr>

				       <!---------중식 -------------------------->

					       <?php if($rst21_cnt <=0) {?>
									<tr class="basic-class16" param ="tr-parent16">
									 <td>&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow12"><span class="glyphicon glyphicon-plus" aria-hidden="true">중식</span></button></td>
									 									 
									  <td><input type="number" name="rday1[]" class="form-control rday" aria-label="일차" value=""/></td>
									  <td><input type="text" name="rname1[]" class="form-control rname1" aria-label="식당명" value=""/></td>
									  <td><input type="time" name="gstime1[]" class="form-control  gstime1" aria-label="손님식사시간" value="11:00"/></td>
									  <td><input type="time" name="getime1[]" class="form-control  gstgetime1"  value="11:30"/></td>
									  <td><input type="time" name="gidstime1[]" class="form-control  gstime1" aria-label="가이드식사시간" value="11:00"/></td>
									  <td><input type="time" name="gidetime1[]" class="form-control  gidetime1" aria-label="" value="11:30"/></td>
									  <td><input type="text" name="rt_cnt1[]" class="form-control  rt_cnt1" aria-label="식당팁인원" value=""/></td>
									  <td><input type="text" name="rt_amt1[]" class="form-control  rt_amt1" aria-label="식당팁금액" value=""/></td>
									  <td><input type="text" name="r_cnt1[]" class="form-control  r_cnt1" aria-label="식사비인원" value=""/></td>
									  <td><input type="text" name="r_amt1[]" class="form-control text-right r_amt1" aria-label="식사비금액" value=""/></td>
									  <td><input type="text" name="rtot_amt1[]" class="form-control text-right rtot_amt1" aria-label="식사비합계" value=""/></td>
									  <td>
										  <select class="form-control paym" name="paym1[]">
											<option <?php if ($datarow['p_type']=="") { ?> selected <?php } ?> >- 선택 -</option>
											<option <?php if ($datarow['p_type']=="1") { ?> selected <?php } ?> value="1">크레딧카드</option>
											<option value="2" <?php if ($datarow['p_type']=="2") { ?> selected <?php } ?>>현금</option>
											<option value="3" <?php if ($datarow['p_type']=="3") { ?> selected <?php } ?>>은행송금</option>
										  </select> 
									  </td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="meal_seq1" value="0">
									</tr>
						   <?php }else{
									$ss=0;
									$rtotal1 = 0;
									while($row1 = mysql_Fetch_assoc($rst21)){
										$rtotal1 = $rtotal1+ $row1['r_tamt'];
										
										
									?>

									<tr class="basic-class16" param ="tr-parent16">
									 <?php if($ss==0) { ?>   
									 <td rowspan="<?=$rst21_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow12"><span class="glyphicon glyphicon-plus" aria-hidden="true">중식</span></button></td>
									 <?php }?>
									  <td><input type="number" name="rday1[]" class="form-control rday1" aria-label="일차" value="<?=$row1['g_date']?>"/></td>
									  <td><input type="text" name="rname1[]" class="form-control rname1" aria-label="식당명" value="<?=$row1['r_name']?>"/></td>
									  <td><input type="time" name="gstime1[]" class="form-control  gstime1" aria-label="손님식사시간" value="<?=$row1['g_fmealtime']?>"/></td>
									  <td><input type="time" name="getime1[]" class="form-control  gstgetime1"  value="<?=$row1['g_tmealtime']?>"/></td>
									  <td><input type="time" name="gidstime1[]" class="form-control  gstime1" aria-label="가이드식사시간" value="<?=$row1['gid_fmealtime']?>"/></td>
									  <td><input type="time" name="gidetime1[]" class="form-control  gidetime1" aria-label="" value="<?=$row1['gid_tmealtime']?>"/></td>
									  <td><input type="text" name="rt_cnt1[]" class="form-control  rt_cnt1" aria-label="식당팁인원" value="<?=$row1['r_tipcnt']?>"/></td>
									  <td><input type="text" name="rt_amt1[]" class="form-control  rt_amt1" aria-label="식당팁금액" value="<?=$row1['r_tipamt']?>"/></td>
									  <td><input type="text" name="r_cnt1[]" class="form-control  r_cnt1" aria-label="식사비인원" value="<?=$row1['r_mealcnt']?>"/></td>
									  <td><input type="text" name="r_amt1[]" class="form-control text-right r_amt1" aria-label="식사비금액" value="<?=$row1['r_mealamt']?>"/></td>
									  <td><input type="text" name="rtot_amt1[]" class="form-control text-right rtot_amt1" aria-label="식사비합계" value="<?=$row1['r_tamt']?>"/></td>
									  <td>
										  <select class="form-control paym" name="paym1[]">
											<option <?php if ($datarow['p_type']=="") { ?> selected <?php } ?> >- 선택 -</option>
											<option <?php if ($datarow['p_type']=="1") { ?> selected <?php } ?> value="1">크레딧카드</option>
											<option value="2" <?php if ($datarow['p_type']=="2") { ?> selected <?php } ?>>현금</option>
											<option value="3" <?php if ($datarow['p_type']=="3") { ?> selected <?php } ?>>은행송금</option>
										  </select> 
									  </td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="meal_seq1" value="0">
									</tr>
                           <?php $ss++;}}?>

								<tr>
								  <td>합계</td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td class="text-right mealTot1"><?=number_format($rtotal1,2)?></td>
								  <td></td>
								  <td></td>
								</tr>
					<!--------------------석식--------------------------->	
					      <?php if($rst22_cnt <=0) {?>
									<tr class="basic-class18" param ="tr-parent18">
									 <td>&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow12"><span class="glyphicon glyphicon-plus" aria-hidden="true">석식</span></button></td>
									 									 
									  <td><input type="number" name="rday2[]" class="form-control rday2" aria-label="일차" value=""/></td>
									  <td><input type="text" name="rname2[]" class="form-control rname2" aria-label="식당명" value=""/></td>
									  <td><input type="time" name="gstime2[]" class="form-control  gstime2" aria-label="손님식사시간" value="17:00"/></td>
									  <td><input type="time" name="getime2[]" class="form-control  gstgetime2"  value="17:40"/></td>
									  <td><input type="time" name="gidstime2[]" class="form-control  gstime2" aria-label="가이드식사시간" value="17:00"/></td>
									  <td><input type="time" name="gidetime2[]" class="form-control  gidetime2" aria-label="" value="17:40"/></td>
									  <td><input type="text" name="rt_cnt2[]" class="form-control  rt_cnt2" aria-label="식당팁인원" value=""/></td>
									  <td><input type="text" name="rt_amt2[]" class="form-control  rt_amt2" aria-label="식당팁금액" value=""/></td>
									  <td><input type="text" name="r_cnt2[]" class="form-control  r_cnt2" aria-label="식사비인원" value=""/></td>
									  <td><input type="text" name="r_amt2[]" class="form-control text-right r_amt2" aria-label="식사비금액" value=""/></td>
									  <td><input type="text" name="rtot_amt2[]" class="form-control text-right rtot_amt2" aria-label="식사비합계" value=""/></td>
									  <td>
										  <select class="form-control paym" name="paym2[]">
											<option <?php if ($datarow['p_type']=="") { ?> selected <?php } ?> >- 선택 -</option>
											<option <?php if ($datarow['p_type']=="1") { ?> selected <?php } ?> value="1">크레딧카드</option>
											<option value="2" <?php if ($datarow['p_type']=="2") { ?> selected <?php } ?>>현금</option>
											<option value="3" <?php if ($datarow['p_type']=="3") { ?> selected <?php } ?>>은행송금</option>
										  </select> 
									  </td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="meal_seq2" value="0">
									</tr>
						   <?php }else{
									$ss=0;
									$rtotal2 = 0;
									while($row1 = mysql_Fetch_assoc($rst22)){
										$rtotal2 = $rtotal2+ $row1['r_tamt'];
										
										
									?>

									<tr class="basic-class18" param ="tr-parent18">
									 <?php if($ss==0) { ?>   
									 <td rowspan="<?=$rst21_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow12"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
									 <?php }?>
									  <td><input type="number" name="rday2[]" class="form-control rday2" aria-label="일차" value="<?=$row1['g_date']?>"/></td>
									  <td><input type="text" name="rname2[]" class="form-control rname2" aria-label="식당명" value="<?=$row1['r_name']?>"/></td>
									  <td><input type="time" name="gstime2[]" class="form-control  gstime2" aria-label="손님식사시간" value="<?=$row1['g_fmealtime']?>"/></td>
									  <td><input type="time" name="getime2[]" class="form-control  gstgetime2"  value="<?=$row1['g_tmealtime']?>"/></td>
									  <td><input type="time" name="gidstime2[]" class="form-control  gstime2" aria-label="가이드식사시간" value="<?=$row1['gid_fmealtime']?>"/></td>
									  <td><input type="time" name="gidetime2[]" class="form-control  gidetime2" aria-label="" value="<?=$row1['gid_tmealtime']?>"/></td>
									  <td><input type="text" name="rt_cnt2[]" class="form-control  rt_cnt2" aria-label="식당팁인원" value="<?=$row1['r_tipcnt']?>"/></td>
									  <td><input type="text" name="rt_amt2[]" class="form-control  rt_amt2" aria-label="식당팁금액" value="<?=$row1['r_tipamt']?>"/></td>
									  <td><input type="text" name="r_cnt2[]" class="form-control  r_cnt2" aria-label="식사비인원" value="<?=$row1['r_mealcnt']?>"/></td>
									  <td><input type="text" name="r_amt2[]" class="form-control text-right r_amt2" aria-label="식사비금액" value="<?=$row1['r_mealamt']?>"/></td>
									  <td><input type="text" name="rtot_amt2[]" class="form-control text-right rtot_amt2" aria-label="식사비합계" value="<?=$row1['r_tamt']?>"/></td>
									  <td>
										  <select class="form-control paym" name="paym2[]">
											<option <?php if ($datarow['p_type']=="") { ?> selected <?php } ?> >- 선택 -</option>
											<option <?php if ($datarow['p_type']=="1") { ?> selected <?php } ?> value="1">크레딧카드</option>
											<option value="2" <?php if ($datarow['p_type']=="2") { ?> selected <?php } ?>>현금</option>
											<option value="3" <?php if ($datarow['p_type']=="3") { ?> selected <?php } ?>>은행송금</option>
										  </select> 
									  </td>
									  <td><div class="col-sm-1 hide button-minus">
											<button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
										</div>
									  </td>
									  <input type="hidden" name="meal_seq2" value="0">
									</tr>
                           <?php $ss++;}}?>

								<tr>
								  <td>합계</td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td class="text-right mealTot2"><?=number_format($rtotal2,2)?></td>
								  <td></td>
								  <td></td>
								</tr>
								<?php
								$total_amt = $rtotal + $rtotal1 + $rtotal2;


								?>
								<tr>
								  <td>총합계</td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td></td>
								  <td class="text-right mealTottot">$<?=number_format($total_amt,2)?></td>
								  <td><input type="hidden" name="mealTottot" value="<?=number_format($total_amt,2)?>"></td>
								  <td></td>
								</tr>
					  </tbody>
				</table>
				<br />
				<?php 
				   $row18 = mysql_Fetch_assoc($rst18);

				?>
				<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
							<td width="10%" class="active text-center formHeader">총투어비</td>
							<td width="10%" class="text-right tottouraamt">
								
								<input type="text" name="tottouramt" class="form-control text-right tottouramt" aria-label="총투어비" value="<?=$row18['tour_totamt']?>"/>
								
							</td>
                            <td width="10%" class="active text-center formHeader">총행사수입</td>
							<td width="10%" class="text-right toteveamt"><span id="toteveamtv" class="toteveamtv text-right"><?=$row18['tour_income']?></span><input type="hidden" name="tour_income" value="<?=$row18['tour_income']?>"></td>
							<td width="10%" class="active text-center formHeader">총행사지출</td>
							<td width="10%"  class="text-right totexpamt"><span id="totexpamtv" class="totexpamtv text-right"><?=$row18['tour_totexpense']?></span><input type="hidden" name="tour_totexpense" value="<?=$row18['tour_totexpense']?>"></td>
							<td width="10%" class="active text-center formHeader">쇼핑수익</td>
							<td width="10%" class="text-right totshopamt"><span id="totshopamt" class="totshopamtv text-right"><?=$row18['shopping_profit']?></span><input type="hidden" name="shopping_profit" value="<?=$row18['shopping_profit']?>"></td>
							<td width="10%" class="active text-center formHeader">PROFIT</td>
							<td width="10%" class="text-right totprofit"><span id="totprofit" class="totprofitv text-right"><?=$row18['tot_profit']?></span><input type="hidden" name="tot_profit" value="<?=$row18['tot_profit']?>"></td>
                        </tr>
                       
                    </tbody>
				</table>
			</form>
		</div>
   </div>
   <?php
		include "include/side_m.php";
   ?>
   <script>

        var number = "<?=$_GET['number']?>";
       

        //sumamtpay();
        $.ajaxSetup({async:false});
		$(document).ready(function () {
       
            var total_bp=0,total_lp=0,total_dp=0,total_bc=0,total_lc=0,total_dc=0,total_bt=0,
            total_lt=0,total_dt=0,total_mealprice=0;

			pt.initReservationList()
            
            $('.js-addPlusRow').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				jcalcuAmount();
				sumsettle();
            });
			$('.js-addPlusRow2').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table2 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				etccalcuAmount();
				sumsettle();
            });
			$('.js-addPlusRow3').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table3 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				glcAmount();
				sumsettle();
            });
			$('.js-addPlusRow4').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table4 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				glcAmount();
				sumsettle();
            });
			$('.js-addPlusRow5').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table5 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				glcAmount();
                sumsettle();
            });
			$('.js-addPlusRow6').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table6 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				admiAmount();
            });
			$('.js-addPlusRow7').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table7 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				coAmount();
            });
			$('.js-addPlusRow8').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table8 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				eveAmount();

            });
			$('.js-addPlusRow9').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table9 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				totalOptSum();
            });
			$('.js-addPlusRow10').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table10 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				ShopAmount();
            });
			$('.js-addPlusRow11').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table11 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				CarAmount();
            });
			$('.js-addPlusRow12').on( 'click', function () {

                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table12 ."+cls+":last"));
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				MealAmount();
				MealAmount1();
				MealAmount2();
				$(".mealTottot").text(((("input[name='meal_seq']").val()) + parseFloat($("input[name='meal_seq1']").val()) + parseFloat($("input[name='meal_seq2']").val())));
            });
			$(document).on("click", ".js-removeHotelButton", function(){
                var clickedRow = $(this).closest('tr').remove();
                var cls = clickedRow.attr("class");
                resizeRowspan(cls);
				//alert("!");
				jcalcuAmount();
				etccalcuAmount();
				admiAmount();
				coAmount();
				eveAmount();
				totalOptSum();
				ShopAmount();
				CarAmount();
				MealAmount();
				MealAmount1();
				MealAmount2();
                //totalMealSum();
               // totalEnSum();
                //totalOptSum();
                //EtcAmount();
               // totalSalesSum();


            });
			$(document).on("click", ".js-removeHotelButton2", function(){
                var clickedRow = $(this).closest('tr').remove();
                var cls = clickedRow.attr("class");
                resizeRowspan(cls);
				etccalcuAmount();
				admiAmount();

                //totalMealSum();
               // totalEnSum();
                //totalOptSum();
                //EtcAmount();
               // totalSalesSum();


            });
			$(document).on("click", ".js-removeHotelButton3", function(){
                var clickedRow = $(this).closest('tr').remove();
                var cls = clickedRow.attr("class");
                resizeRowspan(cls);

                //totalMealSum();
               // totalEnSum();
                //totalOptSum();
                //EtcAmount();
               // totalSalesSum();


            });
			//totalPaySum();
			sumsettle();

		});

		//삭제클릭
        $(document).on("click",".js-delete",function(e) { 

            $("#mode").val("delete");
        	
            $("#frnName").submit();
            
		});

        //가이드정산보고
        $(document).on("click",".js-settle",function(e) { 

            if(confirm("가이드 정산보고를 하시겠습니까?") == true) {
                $("#mode").val("report");
				
                $("#frnName").submit();
                
            }
        });
		//가이드정산보고
        $(document).on("click",".js-csettle",function(e) { 

            if(confirm("가이드 정산보고를  취소하시겠습니까?") == true) {
                $("#mode").val("creport");
				
                $("#frnName").submit();
                
            }
        });

		function resizeRowspan(cls){
            var rowspan = $("."+cls).length;
            $("."+cls+":first td:eq(0)").attr("rowspan", rowspan);
        }

		$(document).on('focusout',".justtot",function () {
			
            jcalcuAmount();
			sumsettle();
        });
        $(document).on('focusout',".ecnt",function () {
            EtcAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".etctot",function () {
            EtcAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".etcttamt",function () {
            EtcAmount($(this).closest("tr"));
			sumsettle();
        });
        //로컬
		$(document).on('focusout',".lpcnt",function () {
            glcAmount();
			sumsettle();
        });
		$(document).on('focusout',".lamt",function () {
            glcAmount();
			sumsettle();
			//alert("1");
        });
		$(document).on('focusout',".lmemo",function () {
            glcAmount();
			sumsettle();
			//alert("1");
        });
		//인바운드
        $(document).on('focusout',".ipcnt",function () {
            glcAmount();
			sumsettle();
        });
		$(document).on('focusout',".iamt",function () {
            glcAmount();
			sumsettle();
        });
		$(document).on('focusout',".imemo",function () {
            glcAmount();
			sumsettle();
        });
		//가이드지원금
        $(document).on('focusout',".gpcnt",function () {
            glcAmount();
			sumsettle();
        });
		$(document).on('focusout',".gamt",function () {
            glcAmount();
			sumsettle();
        });
		$(document).on('focusout',".gmemo",function () {
            glcAmount();
			sumsettle();
        });
		//입장권
        $(document).on('focusout',".en_person",function () {
            admiAmount();
            sumsettle();
        });
		$(document).on('focusout',".en_cost",function () {
            admiAmount();
			sumsettle();
        });
		$(document).on('focusout',".en_totalprice",function () {
            admiAmount();
			sumsettle();
        });
		//회사행사총지출 
		$(document).on('focusout',".co_person",function () {
            coAmountMain($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".exp_amount",function () {
            coAmountMain($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".ct_amt",function () {
            coAmountMain($(this).closest("tr"));
			sumsettle();
        });
		//가이드행사총지출
		$(document).on('focusout',".tip_person",function () {
            eveMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".tip_exp",function () {
            eveMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".tip_amt",function () {
            eveMainAmount($(this).closest("tr"));
			sumsettle();
        });
		/////////////////////////////////////////////////////
		
		$(document).on('focusout',".cc_exp",function () {
            eveMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".cc_amt",function () {
            eveAmount();
			sumsettle();
        }); 
		/////////////////////////////////////////////////////
		$(document).on('focusout',".fe_exp",function () {
           eveMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".fe_amt",function () {
           // eveMainAmount($(this).closest("tr"));
			//sumsettle();
        });
        ////////////////////////////////////////////////////
		$(document).on('focusout',".me_person",function () {
            eveMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".me_exp",function () {
            eveMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".me_amt",function () {
          //  eveMainAmount($(this).closest("tr"));
			//sumsettle();
        });
		 
		//옵션정산
		$(document).on('focusout',".optperson",function () {
            OptAmount($(this).closest("tr"));
			sumsettle();
        });

        $(document).on('focusout',".optcost",function () {
            OptAmount($(this).closest("tr"));
			sumsettle();
        });
        $(document).on('change',".optnm",function () {
			var codeval = $(this).val();
			var rowdata=  $(this).closest("tr");
            $.ajax({

				url : 'get_code1.php',
				type : 'post',
				data : {codeval : codeval},
				dataType:'json',
				success : function(data) {              
					//alert('Data: '+data[0].comment);
					rowdata.find(".optcost").val(data[0].comment);
					rowdata.find(".optprice").val(data[1].comment)
				},
				error : function(request,error)
				{
					alert("Request: "+JSON.stringify(request));
				}
			});
        });
		function OptAmount(aa) {
            var row = aa;
            var person = parseInt(row.find('.optperson').val() || 0);
            var cost = parseFloat(row.find('.optcost').val() || 0);
            var optprice = parseFloat(row.find('.optprice').val() || 0);
            var optdiffamount = parseFloat(person*optprice) - parseFloat(person*cost);
            var optprofit =  (parseFloat(optdiffamount) * 0.5 ).toFixed(2);
            var optguideprofit = ( parseFloat(optdiffamount) * 0.5).toFixed(2);

            
            row.find('.opttotalamoount').val(person*cost);
            row.find('.opttotalprice').val(person*optprice);
            row.find('.optdiffamount').val(optdiffamount);
            row.find('.optprofit').val(optprofit);
            row.find('.optguideprofit').val(optguideprofit);

            totalOptSum();
        }

        function totalOptSum(){
            var b1=0,b2=0,b3=0,b4=0,b5=0,b6=0,b7=0,b8=0;
            
            $("input[name='optPerson[]']").each(function(){
                b1 = parseInt(b1) + parseInt(this.value);
            });

            $("input[name='optCost[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
            });

            $("input[name='optTotalAmount[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
            });

            $("input[name='optPrice[]']").each(function(){
                b4 = parseFloat(b4) + parseFloat(this.value);
            });

            $("input[name='optTotalPrice[]']").each(function(){
                b5 = parseFloat(b5) + parseFloat(this.value);
            });

            $("input[name='optDiffAmount[]']").each(function(){
                b6 = parseFloat(b6) + parseFloat(this.value);
            });
			 
            
			
			
            $("input[name='optProfit[]']").each(function(){
                b7 = parseFloat(b7) + parseFloat(this.value);
            });

            $("input[name='optGuideProfit[]']").each(function(){
                b8 = parseFloat(b8) + parseFloat(this.value);
            });
			
            b1 = getNum(b1);
            b2 = getNum(b2);
            b3 = getNum(b3);
            b4 = getNum(b4);
            b5 = getNum(b5);
            b6 = getNum(b6);
            b7 = getNum(b7);
            b8 = getNum(b8);

            $(".optsum1").text("$"+b1);
            $(".optsum2").text("$"+b2);
            $(".optsum3").text("$"+b3);
            $(".optsum4").text("$"+b4);
            $(".optsum5").text("$"+b5);
            $(".optsum6").text("$"+b6);
            $(".optsum7").text("$"+b7);
            $(".optsum8").text("$"+b8);
			$("input[name='optsum5']").val(parseFloat(b5));
		}
		//쇼핑정산
		$(document).on('change',".shoppingSelect",function () {
			var codeval = $(this).val();
			var rowdata=  $(this).closest("tr");
            $.ajax({

				url : 'get_code1.php',
				type : 'post',
				data : {codeval : codeval},
				dataType:'json',
				success : function(data) {              
					//alert('Data: '+data[0].comment);
					rowdata.find(".saleamount").val(data[0].comment);
					
				},
				error : function(request,error)
				{
					alert("Request: "+JSON.stringify(request));
				}
			});
			ShopMainAmount($(this).closest("tr"));
			sumsettle();
        });
		function ShopMainAmount(aa) {
            var row = aa;
            var saleamount = parseInt(row.find('.saleamount').val() || 0);
			var salecnt = parseInt(row.find('.salecnt').val() || 0);
			
            row.find('.shoppingProfit').val(saleamount*salecnt);
			
            ShopAmount();
        }
		function ShopAmount() {
            
			var b1=0;
            var b2=0;
            var b3=0;
           
            
           
			$("input[name='saleamount[]']").each(function(){
                b1 = parseInt(b1) + parseInt(this.value);
            });

            $("input[name='salecnt[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
            });

            $("input[name='shoppingProfit[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
            });
            

			b1 = getNum(b1);
            b2 = getNum(b2);
            b3 = getNum(b3);
			 

            $(".salesamt2").text("$"+ setComma(parseFloat(b1).toFixed(2)));
			$(".salesamt3").text("$"+ setComma(parseFloat(b2).toFixed(2)));
			$(".salesamt4").text("$"+ setComma(parseFloat(b3).toFixed(2)));
		
        }
		$(document).on('focusout',".saleamount",function () {
            ShopMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".salecnt",function () {
            ShopMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".shoppingProfit",function () {
            ShopMainAmount($(this).closest("tr"));
			sumsettle();
        });
		//차량정산
		$(document).on('focusout',".drtip",function () {
            
			CarMainAmount($(this).closest("tr"));
			sumsettle();
        }); 
		$(document).on('focusout',".drovtip",function () {
            
			CarMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".selfcar",function () {
            
			CarMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".parkexp",function () {
            
			CarMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".fuelexp",function () {
            
			CarMainAmount($(this).closest("tr"));
			sumsettle();
        });
        $(document).on('focusout',".tollexp",function () {
            
			CarMainAmount($(this).closest("tr"));
			sumsettle();
        });
		$(document).on('focusout',".totexp",function () {
            
			CarMainAmount($(this).closest("tr"));
			sumsettle();
        });
		function CarMainAmount(aa) {
            var row = aa;
            var drtip = parseInt(row.find('.drtip').val() || 0);
			var drovtip = parseInt(row.find('.drovtip').val() || 0);
			var selfcar = parseInt(row.find('.selfcar').val() || 0);
            var parkexp = parseFloat(row.find('.parkexp').val() || 0);
			var fuelexp = parseFloat(row.find('.fuelexp').val() || 0);
			var tollexp = parseFloat(row.find('.tollexp').val() || 0);
            row.find('.totexp').val(drtip+drovtip+selfcar+parkexp+fuelexp+tollexp);
			
            CarAmount();
        }
		function CarAmount() {
            
			var b1=0;
            var b2=0;
            var b3=0;
           
            $("input[name='totexp[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });        
            
			b1 = getNum(b1);
            
            
            $(".caramtsum").text("$"+ setComma(parseFloat(b1).toFixed(2)));
			$("input[name='caramtsum[]']").val(parseFloat(b1).toFixed(2));

        }
		///////////////////////////////////////////////////
		//식사정산-조식
		$(document).on('focusout',".rt_cnt",function () {
            
			MealMainAmount($(this).closest("tr"));
			
			MealAmount1();
			MealAmount2();
			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);
			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        }); 
		$(document).on('focusout',".rt_amt",function () {
            
			MealMainAmount($(this).closest("tr"));
			
			MealAmount1();
			MealAmount2();
			
			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
		$(document).on('focusout',".r_cnt",function () {
            
			MealMainAmount($(this).closest("tr"));
			MealAmount1();
			MealAmount2();
			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);
			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
		$(document).on('focusout',".r_amt",function () {
            
			MealMainAmount($(this).closest("tr"));
			MealAmount1();
			MealAmount2();

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);
			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
		    $("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
		$(document).on('focusout',".rtot_amt",function () {
            
			MealMainAmount($(this).closest("tr"));
			MealAmount1();
			MealAmount2();

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);
			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
        
		function MealMainAmount(aa) {
            var row = aa;
            var rt_cnt = parseInt(row.find('.rt_cnt').val() || 0);
			var rt_amt = parseInt(row.find('.rt_amt').val() || 0);
			var r_cnt = parseInt(row.find('.r_cnt').val() || 0);
            var r_amt = parseFloat(row.find('.r_amt').val() || 0);
			//var rtot_amt = parseFloat(row.find('.rtot_amt').val() || 0);
			var rtot1= rt_cnt * rt_amt;
			var rtot2= r_cnt * r_amt;
            row.find('.rtot_amt').val(rtot1+rtot2);
			
            
            MealAmount();
        }
		function MealAmount() {
            
			var b1=0;
            var b2=0;
            var b3=0;
           
            $("input[name='rtot_amt[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });        
            
			b1 = getNum(b1);
            
            
            $(".mealTot").text("$"+ setComma(parseFloat(b1).toFixed(2)));
			$("input[name='meal_seq']").val(b1);

        }
		////////////////////////////////////////////////////////////////
		//식사정산-중식
		$(document).on('focusout',".rt_cnt1",function () {
            MealAmount();
			MealMainAmount1($(this).closest("tr"));
			MealAmount2();

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
	
        }); 
		$(document).on('focusout',".rt_amt1",function () {
            MealAmount();
			MealMainAmount1($(this).closest("tr"));
			MealAmount2();

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
		$(document).on('focusout',".r_cnt1",function () {
            
			MealAmount();
			MealMainAmount1($(this).closest("tr"));
			MealAmount2();

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
		$(document).on('focusout',".r_amt1",function () {
            
			MealAmount();
			MealMainAmount1($(this).closest("tr"));
			MealAmount2();

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();

        });
		$(document).on('focusout',".rtot_amt1",function () {
            
			MealAmount();
			MealMainAmount1($(this).closest("tr"));
			MealAmount2();
			
			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
        
		function MealMainAmount1(aa) {
            var row = aa;
            var rt_cnt = parseInt(row.find('.rt_cnt1').val() || 0);
			var rt_amt = parseInt(row.find('.rt_amt1').val() || 0);
			var r_cnt = parseInt(row.find('.r_cnt1').val() || 0);
            var r_amt = parseFloat(row.find('.r_amt1').val() || 0);
			//var rtot_amt = parseFloat(row.find('.rtot_amt').val() || 0);
			var rtot1= rt_cnt * rt_amt;
			var rtot2= r_cnt * r_amt;
            row.find('.rtot_amt1').val(rtot1+rtot2);
			
            MealAmount1();
        }
		function MealAmount1() {
            
			var b1=0;
            var b2=0;
            var b3=0;
           
            $("input[name='rtot_amt1[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });        
            
			b1 = getNum(b1);
            
            
            $(".mealTot1").text("$"+ setComma(parseFloat(b1).toFixed(2)));
			$("input[name='meal_seq1']").val(b1);

        }
		/////////////////////////////////////////////////////
		////////////////////////////////////////////////////////////////
		//식사정산-석식
		$(document).on('focusout',".rt_cnt2",function () {
            MealAmount();
			MealAmount1();
			MealMainAmount2($(this).closest("tr"));

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();

        }); 
		$(document).on('focusout',".rt_amt2",function () {
            
			MealAmount();
			MealAmount1();
			MealMainAmount2($(this).closest("tr"));

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();

        });
		$(document).on('focusout',".r_cnt2",function () {
            
			MealAmount();
			MealAmount1();
			MealMainAmount2($(this).closest("tr"));


			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
		$(document).on('focusout',".r_amt2",function () {
            
			MealAmount();
			MealAmount1();
			MealMainAmount2($(this).closest("tr"));

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
		$(document).on('focusout',".rtot_amt2",function () {
            
			MealAmount();
			MealAmount1();
			MealMainAmount2($(this).closest("tr"));

			var meal = parseFloat($("input[name='meal_seq']").val());
			var meal1 = parseFloat($("input[name='meal_seq1']").val());
			var meal2 = parseFloat($("input[name='meal_seq2']").val());
            meal = getNum(meal);
			meal1 = getNum(meal1);
			meal2 = getNum(meal2);

			$(".mealTottot").text("$"+(meal+meal1+meal2).toFixed(2));
			$("input[name='mealTottot']").val((meal+meal1+meal2).toFixed(2));
			sumsettle();
        });
        
		function MealMainAmount2(aa) {
            var row = aa;
            var rt_cnt = parseInt(row.find('.rt_cnt2').val() || 0);
			var rt_amt = parseInt(row.find('.rt_amt2').val() || 0);
			var r_cnt = parseInt(row.find('.r_cnt2').val() || 0);
            var r_amt = parseFloat(row.find('.r_amt2').val() || 0);
			//var rtot_amt = parseFloat(row.find('.rtot_amt').val() || 0);
			var rtot1= rt_cnt * rt_amt;
			var rtot2= r_cnt * r_amt;
            row.find('.rtot_amt2').val(rtot1+rtot2);
			
            MealAmount2();
        }
		function MealAmount2() {
            
			var b1=0;
            var b2=0;
            var b3=0;
           
            $("input[name='rtot_amt2[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });        
            
			b1 = getNum(b1);
            
            
            $(".mealTot2").text("$"+ setComma(parseFloat(b1).toFixed(2)));
			$("input[name='meal_seq2']").val(b1);

        }
		function getNum(val) {
            if (isNaN(val)) {
                return 0;
            }
            return val;
        }
		//숫자콤마
        function setComma(value){
            value =  value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            return value;
        }
		//현지수금총액
        function jcalcuAmount() {
            
            var b2=0;
            $("input[name='justamt[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
            });

            b2 = getNum(b2);
                     
            $(".justtotamt").text("$"+ setComma(parseFloat(b2).toFixed(2)));
            $("input[name='justtotamt']").val(parseFloat(b2).toFixed(2));
                     

        }
		function EtcAmount(aa) {
            var row = aa;
            var person = parseInt(row.find('.ecnt').val() || 0);
            var cost = parseFloat(row.find('.etctot').val() || 0);
            row.find('.etcttamt').val(person*cost);

            etccalcuAmount();
        }
		//기타입금총액
        function etccalcuAmount() {
            
			var b1=0;
            var b2=0;
            var b3=0;
           
            $("input[name='ecnt[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });        
            $("input[name='etcamt[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
            });
			$("input[name='etcttamt[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
				
            });
			b1 = getNum(b1);
            b2 = getNum(b2);
			b3 = getNum(b3);
            
            
            $(".etctotamt").text("$"+ setComma(parseFloat(b2).toFixed(2)));
			$(".etctttotamt").text("$"+ setComma(parseFloat(b3).toFixed(2)));
            $("input[name='etctttotamt']").val(parseFloat(b3).toFixed(2));
                     

        }

		function glcAmount() {
            var b1=0;
            var b2=0;
            var b3=0;
			var b4=0;
			var b5=0;
			var b6=0;
            var totp =0;
			var totgamt =0;
			var totgcnt =0;
			
            $("input[name='lpcnt[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });        
            $("input[name='lamt[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
				
            });
			$("input[name='ipcnt[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
            });        
            $("input[name='iamt[]']").each(function(){
                b4 = parseFloat(b4) + parseFloat(this.value);
				//alert(b4);
            });
			$("input[name='gpcnt[]']").each(function(){
                b5 = parseFloat(b5) + parseFloat(this.value);
            });        
            $("input[name='gamt[]']").each(function(){
                b6 = parseFloat(b6) + parseFloat(this.value);
				
            });
			
            
            totgcnt = b1 + b3 + b5 ;
			//totgmt = parseFloat(b2) + parseFloat(b4) + parseFloat(b6);
			totgmt = b2+b4+b6;
			//totgamt = getNum(totgamt);
			//totgcnt = getNum(totgcnt);
            
			
		
			$(".guidetotcnt").text(setComma(parseFloat(b1+b3+b5).toFixed(2)));
			$(".guidetotamt").text("$"+ setComma(parseFloat(b2+b4+b6).toFixed(2)));
            $(".guidetotal").text("총합계 = $"+ setComma((parseFloat(b1+b3+b5) * parseFloat(b2+b4+b6)).toFixed(2)));
            $("input[name='guidetotal']").val((parseFloat(b1+b3+b5) * parseFloat(b2+b4+b6)).toFixed(2)); 
			//alert(totgmt);(parseFloat(b1+b3+b5) * parseFloat(b2+b4+b6)).toFixed(2))

        }

		//입장비
		function admiAmount() {
            
			var b1=0;
            var b2=0;
            var b3=0;
           
            $("input[name='person[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });        
            $("input[name='vea[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
            });
			$("input[name='totalAmount[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
				//alert(b3);
            });
			b1 = getNum(b1);
            b2 = getNum(b2);
			b3 = getNum(b3);
           // alert(b3);
            
            $(".en_totalperson").text("총인원 : $"+ setComma(parseFloat(b1).toFixed(2)));
			$(".en_totalsum").text("현지수금 총액 : $"+ setComma(parseFloat(b3).toFixed(2)));

                   

        }

		//회사총지출액
		function coAmountMain(aa) {
            var row = aa;
            var person = parseInt(row.find('.co_person').val() || 0);
            var cost = parseFloat(row.find('.exp_amount').val() || 0);
            row.find('.ct_amt').val(person*cost);

            coAmount();
        }
        function coAmount() {
            
			var b1=0;
            var b2=0;
            var b3=0;
           
            $("input[name='co_person[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });        
            $("input[name='exp_amount[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
            });
			$("input[name='ct_amt[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
				//alert(b3);
            });
			b1 = getNum(b1);
            b2 = getNum(b2);
			b3 = getNum(b3);
           // alert(b3);
            
          //  $(".en_totalperson").text("총인원 : $"+ setComma(parseFloat(b1).toFixed(2)));
			$(".t_totalsum").text("전체총액 : $"+ setComma(parseFloat(b3).toFixed(2)));
			//$(".t_totalsum1").text("전체총액 : $"+ setComma(parseFloat(b3).toFixed(2)));

                   

		}
		//가이드행사총지출
		//회사총지출액
		function eveMainAmount(aa) {
            var row = aa;
            var person = parseInt(row.find('.tip_person').val() || 0);
            var cost = parseFloat(row.find('.tip_exp').val() || 0);
            row.find('.tip_amt').val(person*cost);
            var tamt= parseFloat(person*cost);
                 
			//var cost1 = parseFloat(row.find('.fe_exp').val() || 0);
          //  row.find('.fe_amt').val(cost1);
			//var tfee = parseFloat(cost1);
			
			var person1 = parseInt(row.find('.me_person').val() || 0);
            var cost1 = parseFloat(row.find('.me_exp').val() || 0);
            row.find('.me_amt').val(person1*cost1);
			
            
             var tsupp=tamt;
			
             
			var cost1 = parseFloat(row.find('.cc_exp').val() || 0);
            row.find('.cc_amt').val(cost1);
		
                                     
            eveAmount();
        }
        function eveAmount() {
            
			var b1=0;
            var b2=0;
            var b3=0;
            var b4=0;
			var b5=0;
			var b6=0;
			var b7=0;
            $("input[name='tip_person[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });        
            $("input[name='tip_exp[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);

            });
			$("input[name='tip_amt[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
				
				//alert(b3);
            });
			$("input[name='cc_exp[]']").each(function(){
                b4 = parseFloat(b4) + parseFloat(this.value);
				$(this).find(".cc_amt").val(b4);
				//alert(b4);
            });
			$("input[name='cc_amt[]']").each(function(){
                b5 = parseFloat(b5) + parseFloat(this.value);
				//alert(b3);
            });
			$("input[name='fe_exp[]']").each(function(){
                b6 = parseFloat(b6) + parseFloat(this.value);
				//alert(b3);
            });
			$("input[name='me_amt[]']").each(function(){
                b7 = parseFloat(b7) + parseFloat(this.value);
				//alert(b3);
            });
			b1 = getNum(b1);
            b2 = getNum(b2);
			b3 = getNum(b3);//
			b4 = getNum(b4);//
			//b6 = getNum(b6);//
			//b7 = getNum(b7);//
           // alert(b3);
            
          //  $(".en_totalperson").text("총인원 : $"+ setComma(parseFloat(b1).toFixed(2)));
			$(".t_totalsum1").text("전체총액 : $"+ setComma(parseFloat(b3+b4+b7).toFixed(2)));
       		

                   

		}
		$(document).on('focusout',".tottouramt",function () {
            sumsettle();
        });
		function sumsettle() {
			
			var just = parseFloat($("input[name='justtotamt']").val() || 0);
			var etcamt =parseFloat($("input[name='etctttotamt']").val() || 0);
			var guidetot  = parseFloat($("input[name='guidetotal']").val()  ||0);

			var toteveamt = parseFloat(just) + parseFloat(etcamt) + parseFloat(guidetot);
			///////////////////////////////////////////////////////
			var ccamt = 0;
			$("input[name='cc_amt[]']").each(function(){
                ccamt = parseFloat(ccamt) + parseFloat(this.value);
				
            });
			var caramtsum = $("input[name='caramtsum']").val();
			var mealTottot= $("input[name='mealTottot']").val();
			var optsum5 = $("input[name='optsum5']").val();
			var shopp_amt = $("input[name='shopp_amt']").val();
			var tottour_amt = $("input[name='tottouramt']").val();
			
           //console.log(tottour_amt);
            var totexpamtv = parseFloat(ccamt) + parseFloat(caramtsum) + parseFloat(mealTottot) + parseFloat(optsum5) ;
			var totshopamtv =parseFloat(shopp_amt);
			var tottouramt =parseFloat(tottour_amt);
			if (!tottouramt)
			{
				tottouramt = 0;
			}
			console.log(tottouramt);
			/////////////////////////////////////////////////////
            //console.log(totshopamtv);
		    //총행사수입
			$(".toteveamtv").text("$"+ setComma(toteveamt.toFixed(2)));
			
		    //총행사지출
			$(".totexpamtv").text("$"+ setComma(totexpamtv.toFixed(2)));
			
			//쇼핑수익
			$(".totshopamtv").text("$"+ setComma(totshopamtv.toFixed(2)));
			
			

			var profit =  tottouramt +  toteveamt +totexpamtv + totshopamtv;
            
			//PROFIT
			//$(".totprofitv").text("$"+ setComma($("input[name='shopping_profit']").val()));			
			$("input[name='tot_profit']").val(profit);
            $(".totprofitv").text("$"+ setComma(profit.toFixed(2)));	
		}
   </script>
  </body>
</html>
