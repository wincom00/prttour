<?php
    include "include/header.php";
    
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

    //투어 기본 정보
    $query = "SELECT a.*, (SELECT kor_name FROM member_list where userid = a.guide_id AND division ='guide') AS kr_name,
    (SELECT base_rate FROM product_master b where a.p_code = b.p_code) AS base_rate
    FROM tour_guide a WHERE a.seq_no = $seqno ";
    $rst1 = mysql_query($query);
    $data_row = mysql_Fetch_assoc($rst1);

    //가이드 정산코드
    $guide_code = getGuideCode($data_row['grand_eCode'],$data_row['sub_eCode']);
    //행사기간
    $period = getPeriodbyhotel($data_row['p_code'],$data_row['stDate']);
    //행사인원
    $p_cnt = getReserveInfoCnt($data_row['p_code'],$data_row['stDate']);

    //guide setmaster
    $query = "SELECT * FROM guide_setmaster WHERE settle_code = '{$guide_code["settle_code"]}' ";
    $rst00 = mysql_query($query);
    $data_row00 = mysql_Fetch_assoc($rst00);
	
	// --- 추가: 체크내역/메모 불러오기 ---
	$chk_sql = "SELECT * FROM guide_set_check WHERE settle_code = '".$guide_code['settle_code']."' ORDER BY id";
	$chk_rst = mysql_query($chk_sql);
	$check_rows = [];
	while($r = mysql_Fetch_assoc($chk_rst)) $check_rows[] = $r;

	$guide_memo = $data_row00['guide_memo'];

    //본행사인원
    $mainPcnt = getGuideMainPcnt($data_row['p_code'],$data_row['stDate']);
    //복합행사인원
    $subPcnt = getGuideSubPcnt($data_row['p_code'],$data_row['stDate']);
	if ($mainPcnt['p_cnt']=='') {
		$mainPcnt['p_cnt'] = 0;
	}
	$totPcnt = $mainPcnt['p_cnt'] + $subPcnt['p_cnt'];
    if($data_row00['reg_status'] == 'COMPLETE') $disabled = 'disabled';
    else $disabled='';

    //조식,중식,석식
    $meal_b1=0;$meal_b2=0;$meal_b3=0;$meal_l1=0;$meal_l2=0;$meal_l3=0;$meal_d1=0;$meal_d2=0;$meal_d3=0;
    $query = "SELECT * FROM guide_meal WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY meal_type ";
    $rst11 = mysql_query($query);
    $rst11_cnt = mysql_num_rows($rst11);

    $query = "SELECT * FROM guide_meal WHERE settle_code = '{$guide_code["settle_code"]}' AND meal_type='bf' ORDER BY meal_type ";
    $rst2 = mysql_query($query);
    $rst2_cnt = mysql_num_rows($rst2);

    $query = "SELECT * FROM guide_meal WHERE settle_code = '{$guide_code["settle_code"]}' AND meal_type='lunch' ORDER BY meal_type ";
    $rst21 = mysql_query($query);
    $rst21_cnt = mysql_num_rows($rst21);

    $query = "SELECT * FROM guide_meal WHERE settle_code = '{$guide_code["settle_code"]}' AND meal_type='dinner' ORDER BY meal_type ";
    $rst22 = mysql_query($query);
    $rst22_cnt = mysql_num_rows($rst22);

    //입장비
    $en_b1=0;$en_b2=0;
    $query = "SELECT * FROM guide_admission WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst3 = mysql_query($query);
    $rst3_cnt = mysql_num_rows($rst3);

    //옵션명
    $opt_b1=0;$opt_b2=0;$opt_b3=0;$opt_b4=0;$opt_b5=0;$opt_b6=0;$opt_b7=0;$opt_b8=0;
    $query = "SELECT * FROM guide_option WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst4 = mysql_query($query);
    $rst4_cnt = mysql_num_rows($rst4);
	
	//옵션명합계
    $query = "SELECT settle_code,sum(o_cprofit) as oprf FROM guide_option WHERE settle_code = '{$guide_code["settle_code"]}' GROUP BY settle_code  ";
    $rst01 = mysql_query($query);
	$data_row01 = mysql_Fetch_assoc($rst01);

    //가이드 기타비용
    $etcsum = 0;
    $etcamt1=0;
    $query = "SELECT * FROM guide_etcamt WHERE settle_code = '{$guide_code["settle_code"]}' AND etc_pricety = 'guide' ORDER BY seq_no ";
    $rst5 = mysql_query($query);
    $rst5_cnt = mysql_num_rows($rst5);

    //차량 기타비용
    $etcamt2=0;
    $query = "SELECT * FROM guide_etcamt WHERE settle_code = '{$guide_code["settle_code"]}' AND etc_pricety = 'car' ORDER BY seq_no ";
    $rst6 = mysql_query($query);
    $rst6_cnt = mysql_num_rows($rst6);

    //기타 기타비용
    $etcamt3=0;
    $query = "SELECT * FROM guide_etcamt WHERE settle_code = '{$guide_code["settle_code"]}' AND etc_pricety = 'etc' ORDER BY seq_no ";
    $rst7 = mysql_query($query);
    $rst7_cnt = mysql_num_rows($rst7);

    //쇼핑비용
    $sales_amt1=0;$sales_amt2=0;$sales_amt3=0;$sales_amt4=0;
    $query = "SELECT * FROM guide_shopping WHERE settle_code = '{$guide_code["settle_code"]}' ORDER BY seq_no ";
    $rst8 = mysql_query($query);
    $rst8_cnt = mysql_num_rows($rst8);

    //가이드납입금액 adult
    $inputamt1=0;
    $query = "SELECT * FROM guide_inputamt WHERE settle_code = '{$guide_code["settle_code"]}'  ORDER BY seq_no ";
    $rst9 = mysql_query($query);
	$rst9_cnt= mysql_num_rows($rst9);
   
    
	
    //가이드정산 입장지명
    $guide_g01 =  getGuideBaseCode('J01');
    //가이드정산 옵션
    $guide_g02 =  getGuideBaseCode('J02');
    //가이드정산 기타비용
    $guide_g03 =  getGuideBaseCode('J03');
    //가이드정산 쇼핑비용
    $guide_g04 =  getGuideBaseCode('J04');
	//가이드정산납입금
	$guide_g05 =  getGuideBaseCode('J05');


?>
    <!--<script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>-->

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


            <form name="frnName" id="frnName" method="post" action="">
                <input type="hidden" name="mode" id="mode" value="save">
                <input type="hidden" name="grand_eCode" id="grand_eCode" value="<?= $data_row['grand_eCode'] ?>">
                <input type="hidden" name="sub_eCode" id="sub_eCode" value="<?= $data_row['sub_eCode'] ?>">
                <input type="hidden" name="pcode" id="m_rate_h" value="<?= $data_row['p_code'] ?>">
                <input type="hidden" name="stDate" value="<?= $data_row['stDate'] ?>">
                <input type="hidden" name="settle_code" value="<?= $guide_code['settle_code'] ?>">

				<div class="row no-nav">
					<div class="col-sm-9 text-center">
						<button type="button" class="btn btn-xs btn-default js-report" <?=$disabled?>>가이드정산제출</button>
						<button type="button" class="btn btn-xs btn-default js-save" <?=$disabled?>>저장</button>
						<button type="button" class="btn btn-xs btn-default js-delete" <?=$disabled?>>삭제</button>
                        <button type="button" class="btn btn-xs btn-default js-print" >프린트</button>
                        
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
							<td colspan="2" class="active text-center formHeader">행사명</td>
							<td colspan="6"><?=$data_row['p_name']?></td>
                            <td colspan="2" class="active text-center formHeader">행사코드</td>
							<td colspan="6" class="input_text_color"><?=$data_row['sub_eCode']?></td>
                        </tr>
                        <tr>                    			
							<td colspan="2" class="active text-center formHeader">본행사인원</td>
							<td colspan="3"><?=$mainPcnt['p_cnt']?>명</td>
                            <td colspan="2" class="active text-center formHeader">복합행사인원</td>
							<td colspan="3"><?=$subPcnt['p_cnt']?>명</td>
                            <td colspan="2" class="active text-center formHeader">행사총인원</td>
							<td colspan="4"><?=$totPcnt?>명</td>
                        </tr>
                        <tr>                    			
							<td colspan="2" class="active text-center formHeader">차량회사</td>
							<td colspan="6">
				                <div class="row">
									<div class="col-sm-6"><?=$data_row['c_id']?></div>    			    
                                </div>
							</td>
                            <td colspan="2" class="active text-center formHeader">차량인승</td>
							<td colspan="6"><?=$data_row['c_type']?></td>
                        </tr>
                        <tr>                    			
							<td colspan="2" class="active text-center formHeader">가이드</td>
							<td colspan="6"><?=$data_row['kr_name']?> 가이드</td>
                            <td colspan="2" class="active text-center formHeader">기준통화</td>
							<td colspan="6"><?=$data_row['base_rate']?> </td>
                        </tr>
                    </tbody>
				</table>

                
                <!-- //CONTENT TABLE -->
                
				
                <div class="row"><div class="col-sm-12 text-center formHeader fullWidth">식사</div></div>
				<br/>
				<table id="custom_table" class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
                    <thead>
                        <tr>  
                          <th scope="col" width="12%"></th>
                          <th scope="col" width="10%">날짜</th>
                          <th scope="col">식당명</th>
                          <th scope="col">인원</th>
                          <th scope="col">원가/P</th>
                          <th scope="col">원가총액</th>
                        </tr>
                    </thead>
                    <tbody class="innertr">
                        
                        <?php if( $rst11_cnt<=0){  ?>
                            <tr class="basic-class1" param ="tr-parent">
                            <td>조식&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                        
                            <td><input type="date" class="form-control" name="bfDate[]" placeholder="날짜" value=""></td>
                            <td><input type="text" class="form-control" name="bfStoreName[]" aria-label="식당명" value=""/></td>
                            <td><input type="text" class="form-control bfPerson" name="bfPerson[]" aria-label="인원" value=""/></td>
                            <td><input type="text" class="form-control bfCost" name="bfCost[]" aria-label="원가/P" value=""/></td>
                            <td>
                                <div class="row">
                                    <div class="col-sm-10"><input type="text" name="bfTotalCost[]" class="form-control bfTotalCost"  aria-label="원가총액" value=""/></div>
                                    <div class="col-sm-1 hide button-minus">
                                    <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>     
                                </div> 
                            </td>

                            <input type="hidden" name="bf_seq[]" class="bf_seq" value="0">

                        </tr>
                        <tr>
                          <td bgcolor="#5cb85c">합계</td>
                          <td></td>
                          <td></td>
                          <td class="sub_total_b1"></td>
                          <td class="sub_total_b2"></td>
                          <td class="sub_total_b3"></td>
                        </tr>
                        <tr class="basic-class2" param ="tr-parent">
                          <td>중식&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                          
                          <td><input type="date" class="form-control" name="lunchDate[]" placeholder="날짜" value=""></td>
                          <td><input type="text" class="form-control" name="lunchStoreName[]" aria-label="식당명" value=""/></td>
                          <td><input type="text" class="form-control lunchPerson" name="lunchPerson[]" aria-label="인원" value=""/></td>
                          <td><input type="text" class="form-control lunchCost" name="lunchCost[]" aria-label="원가/P" value=""/></td>
                          <td>
                             <div class="row">
				                <div class="col-sm-10"><input type="text" name="lunchTotalCost[]" class="form-control lunchTotalCost" aria-label="원가총액" value=""/></div>
                                <div class="col-sm-1 hide button-minus">
                                   <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                </div>     
                             </div> 
                          </td>

                          <input type="hidden" name="lunch_seq[]" class="lunch_seq" value="0">

                        </tr>
                        <tr>
                          <td bgcolor="#5cb85c">합계</td>
                          <td></td>
                          <td></td>
                          <td class="sub_total_l1"></td>
                          <td class="sub_total_l2"></td>
                          <td class="sub_total_l3"></td>
                        </tr>
                        <tr class="basic-class3" param ="tr-parent">
                          <td>석식&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                          
                          <td><input type="date" class="form-control" name="dinnerDate[]" placeholder="날짜" value=""></td>
                          <td><input type="text" class="form-control" name="dinnerStoreName[]" aria-label="식당명" value=""/></td>
                          <td><input type="text" class="form-control dinnerPerson" name="dinnerPerson[]" aria-label="인원" value=""/></td>
                          <td><input type="text" class="form-control dinnerCost" name="dinnerCost[]" aria-label="원가/P" value=""/></td>
                          <td>
                             <div class="row">
				                <div class="col-sm-10"><input type="text" name="dinnerTotalCost[]" class="form-control dinnerTotalCost" aria-label="원가총액" value=""/></div>
                                <div class="col-sm-1 hide button-minus">
                                   <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                </div>     
                             </div> 
                          </td>
                          <input type="hidden" name="dinner_seq[]" class="dinner_seq" value="0">
                        </tr>
                        <tr>
                          <td bgcolor="#5cb85c">합계</td>
                          <td></td>
                          <td></td>
                          <td class="sub_total_d1"></td>
                          <td class="sub_total_d2"></td>
                          <td class="sub_total_d3"></td>
                        </tr>
                        <tr>
                          <td colspan="2">총합계</td>
                          <td>
                              <div class="row">
                                  <div class="col-sm-12 text-left total_bp">조식인원합계 : </div>
                              </div> 
                              <div class="row">         
                                  <div class="col-sm-12 text-left total_lp">중식인원합계 : </div>
                              </div>
                              <div class="row">      
                                  <div class="col-sm-12 text-left total_dp">석식인원합계 : </div>
                              </div>
                          </td>
                          <td>
                              <div class="row">
                                  <div class="col-sm-12 text-left total_bc">조식원가/P총액 : </div>
                              </div> 
                              <div class="row">         
                                  <div class="col-sm-12 text-left total_lc">중식원가/P총액 : </div>
                              </div>
                              <div class="row">      
                                  <div class="col-sm-12 text-left total_dc">석식원가/P총액 : </div>
                              </div>
                          </td>
                          <td>
                              <div class="row">
                                  <div class="col-sm-12 text-left total_bt">조식원가총액: </div>
                              </div> 
                              <div class="row">         
                                  <div class="col-sm-12 text-left total_lt">중식원가총액 : </div>
                              </div>
                              <div class="row">      
                                  <div class="col-sm-12 text-left total_dt">석식원가총액 : </div>
                              </div>
                          </td>
                          <td>
                              <div class="row">
                                  <div class="col-sm-12 text-left total_mealprice">식사비총액: </div>
                              </div> 
                          </td>
                        </tr>
                       
                        <?php }else { ?>    
                       
                        
                        <?php
                            $ii=0;
                            while($row22 = mysql_Fetch_assoc($rst2)){
                                $meal_pricetotal =  $row22['meal_price'] * $row22['meal_cnt'];
                                $meal_b1 = $meal_b1 + $row22['meal_cnt'];
                                $meal_b2 = $meal_b2 + $row22['meal_price'];
                                $meal_b3 = $meal_b3 + $row22['meal_pricetotal'];
                                
                        ?>
                          
                        <tr class="basic-class1" param ="tr-parent">
                          <?php if($ii == 0) { ?>
                          <td rowspan="<?=$rst2_cnt?>">조식&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                          <?php }?>
                          <td><input type="date" class="form-control" name="bfDate[]" placeholder="날짜" value="<?=$row22['meal_date']?>"></td>
                          <td><input type="text" class="form-control" name="bfStoreName[]" aria-label="식당명" value="<?=$row22['meal_rest']?>"/></td>
                          <td><input type="text" class="form-control bfPerson" name="bfPerson[]" aria-label="인원" value="<?=$row22['meal_cnt']?>"/></td>
                          <td><input type="text" class="form-control bfCost" name="bfCost[]" aria-label="원가/P" value="<?=$row22['meal_price']?>"/></td>
                          <td>
                             <div class="row">
				                <div class="col-sm-10"><input type="text" name="bfTotalCost[]" class="form-control bfTotalCost" aria-label="원가총액" value="<?=$meal_pricetotal?>"/></div>
                                <div class="col-sm-1 hide button-minus">
                                   <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                </div>     
                             </div> 
                          </td>
                          <input type="hidden" name="bf_seq[]" class="bf_seq" value="<?=$row22['seq_no']?>">
                        </tr>
                        
                        <?php $ii++; }?>

                        <tr>
                          <td bgcolor="#5cb85c">합계</td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                        </tr>
                        
                        <?php
                            $jj=0;
                            while($row33 = mysql_Fetch_assoc($rst21)){
                                $meal_pricetotal =  $row33['meal_price'] * $row33['meal_cnt'];
                                $meal_l1 = $meal_l1 + $row33['meal_cnt'];
                                $meal_l2 = $meal_l2 + $row33['meal_price'];
                                $meal_l3 = $meal_l3 + $row33['meal_pricetotal'];
                                
                        ?>
                          
                        <tr class="basic-class2" param ="tr-parent">
                          <?php if($jj == 0) { ?>
                          <td rowspan="<?=$rst21_cnt?>">중식&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                          <?php }?>
                          <td><input type="date" class="form-control" name="lunchDate[]" placeholder="날짜" value="<?=$row33['meal_date']?>"></td>
                          <td><input type="text" class="form-control" name="lunchStoreName[]" aria-label="식당명" value="<?=$row33['meal_rest']?>"/></td>
                          <td><input type="text" class="form-control lunchPerson" name="lunchPerson[]" aria-label="인원" value="<?=$row33['meal_cnt']?>"/></td>
                          <td><input type="text" class="form-control lunchCost" name="lunchCost[]" aria-label="원가/P" value="<?=$row33['meal_price']?>"/></td>
                          <td>
                             <div class="row">
				                <div class="col-sm-10"><input type="text" name="lunchTotalCost[]" class="form-control lunchTotalCost" aria-label="원가총액" value="<?=$meal_pricetotal?>"/></div>
                                <div class="col-sm-1 hide button-minus">
                                   <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                </div>     
                             </div> 
                          </td>
                          <input type="hidden" name="lunch_seq[]" class="lunch_seq" value="<?=$row33['seq_no']?>">  
                        </tr>
                        <?php $jj++; }?>

                        <tr>
                          <td bgcolor="#5cb85c">합계</td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                        </tr>
                        
                        <?php
                            $kk=0;
                            while($row44 = mysql_Fetch_assoc($rst22)){
                                $meal_pricetotal =  $row44['meal_price'] * $row44['meal_cnt'];
                                $meal_d1 = $meal_d1 + $row44['meal_cnt'];
                                $meal_d2 = $meal_d2 + $row44['meal_price'];
                                $meal_d3 = $meal_d3 + $row44['meal_pricetotal'];
                                
                        ?>
                        
                          
                        <tr class="basic-class3" param ="tr-parent">
                          <?php if($kk == 0) { ?>  
                          <td rowspan="<?=$rst22_cnt?>">석식&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                          <?php }?>
                          <td><input type="date" class="form-control" name="dinnerDate[]" placeholder="날짜" value="<?=$row44['meal_date']?>"></td>
                          <td><input type="text" class="form-control" name="dinnerStoreName[]" aria-label="식당명" value="<?=$row44['meal_rest']?>"/></td>
                          <td><input type="text" class="form-control dinnerPerson" name="dinnerPerson[]" aria-label="인원" value="<?=$row44['meal_cnt']?>"/></td>
                          <td><input type="text" class="form-control dinnerCost" name="dinnerCost[]" aria-label="원가/P" value="<?=$row44['meal_price']?>"/></td>
                          <td>
                             <div class="row">
				                <div class="col-sm-10"><input type="text" name="dinnerTotalCost[]" class="form-control dinnerTotalCost" aria-label="원가총액" value="<?=$meal_pricetotal?>"/></div>
                                <div class="col-sm-1 hide button-minus">
                                   <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                </div>     
                             </div> 
                          </td>
                          <input type="hidden" name="dinner_seq[]" class="dinner_seq" value="<?=$row44['seq_no']?>">          
                        </tr>
                        <?php $kk++;}?>

                        <tr>
                          <td bgcolor="#5cb85c">합계</td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                          <td></td>
                        </tr>

                        <tr>
                          <td colspan="2">총합계</td>
                          <td>
                              <div class="row">
                                  <div class="col-sm-12 text-left total_bp">조식인원합계 :  <?=$meal_b1?></div>
                              </div> 
                              <div class="row">         
                                  <div class="col-sm-12 text-left total_lp">중식인원합계 : <?=$meal_l1?></div>
                              </div>
                              <div class="row">      
                                  <div class="col-sm-12 text-left total_dp">석식인원합계 : <?=$meal_d1?></div>
                              </div>
                          </td>
                          <td>
                              <div class="row">
                                  <div class="col-sm-12 text-left total_bc">조식원가/P총액 : <?=$meal_b2?></div>
                              </div> 
                              <div class="row">         
                                  <div class="col-sm-12 text-left total_lc">중식원가/P총액 : <?=$meal_l2?></div>
                              </div>
                              <div class="row">      
                                  <div class="col-sm-12 text-left total_dc">석식원가/P총액 : <?=$meal_d2?></div>
                              </div>
                          </td>
                          <td>
                              <div class="row">
                                  <div class="col-sm-12 text-left total_bt">조식원가총액: <?=$meal_b3?></div>
                              </div> 
                              <div class="row">         
                                  <div class="col-sm-12 text-left total_lt">중식원가총액 : <?=$meal_l3?></div>
                              </div>
                              <div class="row">      
                                  <div class="col-sm-12 text-left total_dt">석식원가총액 : <?=$meal_d3?></div>
                              </div>
                          </td>
                          <?php $mealsum = $meal_b3+$meal_l3+$meal_d3; ?>
                          <td>
                              <div class="row">
                                  <div class="col-sm-12 text-left total_mealprice">식사비총액: <?=$mealsum?></div>
                              </div> 
                          </td>
                        </tr>

                        
                        <?php }?>
                    
                        <?php if($rst3_cnt <=0) {?>
                        <tr class="basic-class4" param ="tr-parent"> 
                            <td class="active text-center formHeader">입장비&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">입장지명</span>
                                            <select class="form-control" name="nameSelect[]">
                                                <option selected>- 선택 -</option>
                                            <?php 
                                            mysqli_data_seek($guide_g01, 0);
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
                                            <span class="input-group-addon">원가/P</span>
                                            <input type="text" name="cost[]" class="form-control text-right en_cost" aria-label="원가/P" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">원가총액</span>
                                            <input type="text" name="totalAmount[]" class="form-control text-right en_totalprice" aria-label="원가총액" value=""/>
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
                            $en_b1 = $en_b1 + $row33['e_cnt'];
                            $en_b2 = $en_b2 + $row33['e_pricetot'];
                        ?> 
                        <tr class="basic-class4" param ="tr-parent"> 
                            <?php if($qq ==0 ) { ?>
                            <td class="active text-center formHeader" rowspan="<?=$rst3_cnt?>">입장비&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>        
                            <?php }?>
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">입장지명</span>
                                            <select class="form-control" name="nameSelect[]">
											<option selected>- 선택 -</option>
                                            <?php 
                                            mysqli_data_seek($guide_g01, 0);
                                            while($row1 = mysql_Fetch_assoc($guide_g01)){
                                            ?>  
                                                <option <?php if ($row33['admission_code'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
                                                 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                            <?php }?>    
                                            </select>
                                        </div>    
                                    </div>    
                                    <div class="col-sm-2">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">인원</span>
                                            <input type="text" name="person[]" class="form-control en_person" aria-label="인원" value="<?=$row33['e_cnt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">원가/P</span>
                                            <input type="text" name="cost[]" class="form-control text-right en_cost" aria-label="원가/P" value="<?=$row33['e_price']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">원가총액</span>
                                            <input type="text" name="totalAmount[]" class="form-control text-right en_totalprice" aria-label="원가총액" value="<?=$row33['e_pricetot']?>"/>
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
                                      <div class="col-sm-4 en_totalsum">총원가 총액 : $<?=number_format($en_b2,2)?> </div>
                                   </div>    
                                </div>    
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-center formHeader fullWidth">옵션명</td>
                        </tr>
                        <tr>
                            <td colspan="6">
                                <table class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
                                    <thead>
                                        <tr>  
                                          <th scope="col" width="1%"></th>
                                          <th scope="col" width="12%">옵션명</th>
                                          <th scope="col" width="13%">정산기준</th>
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
                                    <tbody>
                                        <?php if($rst4_cnt <=0) { ?>
                                        <tr class="basic-class5" param ="tr-parent">
                                          <td>&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                                          <td>
                                              <select class="form-control" name="optionName[]">
                                                <option selected>- 선택 -</option>
                                                <?php 
                                                mysqli_data_seek($guide_g02, 0);
                                                while($row1 = mysql_Fetch_assoc($guide_g02)){ ?>  
                                                <option value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                                <?php }?>
                                              </select>
                                          </td>
                                          <td>
                                              <select class="form-control optset" name="assignGuideLine[]">
                                                <option value='' selected>- 선택 -</option>
                                                <option value="55" >5:5</option>
												<option value="64" >6:4</option>
												<option value="73" >7:3</option>
                                              </select>
                                          </td>
                                          <td><input type="text" class="form-control optperson" name="optPerson[]" aria-label="인원" value=""/></td>
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
                                        while($datarow = mysql_Fetch_assoc($rst4)){  
                                            $opt_b1 = $opt_b1 + $datarow['o_cnt'];
                                            $opt_b2 = $opt_b2 + $datarow['o_price'];
                                            $opt_b3 = $opt_b3 + $datarow['o_pricetot'];
                                            $opt_b4 = $opt_b4 + $datarow['o_cprice'];
                                            $opt_b5 = $opt_b5 + $datarow['o_cpricetot'];
                                            $opt_b6 = $opt_b6 + $datarow['o_diffamt'];
                                            $opt_b7 = $opt_b7 + $datarow['o_cprofit'];
                                            $opt_b8 = $opt_b8 + $datarow['o_gprofit'];

                                        ?>
                                        <tr class="basic-class5" param ="tr-parent">
                                          <?php if($zz==0) { ?>
                                          <td rowspan="<?=$rst4_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                                          <?php }?>
                                          <td>
                                              <select class="form-control" name="optionName[]">
                                                <?php 
                                                mysqli_data_seek($guide_g02, 0); 
                                                while($row1 = mysql_Fetch_assoc($guide_g02)){ ?>  
                                                <option <?php if ($datarow['option_code'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
                                                 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                                <?php }?>
                                              </select>
                                          </td>

                                          <td>
                                              <select class="form-control optset" name="assignGuideLine[]">
                                                <option value='' selected>- 선택 -</option>
                                                <option value="55"  <?php if ($datarow['base_set'] =="55") { ?> selected <?php } ?>>5:5</option>
												<option value="64" <?php if ($datarow['base_set'] == "64") { ?> selected <?php } ?>>6:4</option>
												<option value="73" <?php if ($datarow['base_set'] == "73") { ?> selected <?php } ?>>7:3</option>
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
                                          <td colspan="3">합계</td>
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
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-center formHeader fullWidth">가이드기타비용</td>
                        </tr>
                        <?php if($rst5_cnt <=0) { ?>
                        <tr class="basic-class6" param ="tr-parent"> 
                            <td class="active text-center formHeader">가이드&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="5">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <select class="form-control" name="guideCarSelect[]">
                                            <option>- 선택 -</option>
                                            <?php 
                                            mysqli_data_seek($guide_g03, 0); 
                                            while($row1 = mysql_Fetch_assoc($guide_g03)){ ?>  
                                            <option value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                            <?php }?>
                                        </select>  
                                    </div>    
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">총액</span>
                                            <input type="text" name="guideTotalAmount[]"  class="form-control text-right guideTotalAmount" aria-label="총액" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">메모</span>
                                            <input type="text" name="guideMemo[]" class="form-control" aria-label="메모" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="guide_seq" value="0">
                        </tr>
                            
                        <?php }else{
                        $yy=0;
                        while($datarow = mysql_Fetch_assoc($rst5)){  
                            $etcamt1 = $etcamt1 +  $datarow['etc_amt'];
                        ?>
                        <tr class="basic-class6" param ="tr-parent"> 
                            <?php if($yy==0) { ?>
                            <td rowspan="<?=$rst5_cnt?>" class="active text-center formHeader">가이드&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <?php }?>
                            <td colspan="5">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <select class="form-control" name="guideCarSelect[]">
                                            <option>- 선택 -</option>
                                            <?php mysqli_data_seek($guide_g03, 0);  while($row1 = mysql_Fetch_assoc($guide_g03)){ ?>  
                                            <option <?php if ($datarow['etc_type'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
                                                 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                            <?php }?>
                                        </select>  
                                    </div>    
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">총액</span>
                                            <input type="text" name="guideTotalAmount[]"  class="form-control text-right guideTotalAmount" aria-label="총액" value="<?=$datarow['etc_amt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">메모</span>
                                            <input type="text" name="guideMemo[]" class="form-control" aria-label="메모" value="<?=$datarow['etc_memo']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>

                            <input type="hidden" name="guide_seq" value="<?=$datarow['seq_no']?>">
                        </tr>
                        
                        <?php $yy++; }}?>

                        <?php if($rst6_cnt <=0) { ?>
                        <tr class="basic-class7" param ="tr-parent"> 
                            <td colspan="1" class="active text-center formHeader">차량&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <td colspan="5">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <select class="form-control" name="carSelect[]">
                                            <option>- 선택 -</option>
                                            <?php mysqli_data_seek($guide_g03, 0); while($row1 = mysql_Fetch_assoc($guide_g03)){ ?>  
                                            <option value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                            <?php }?>
                                        </select>  
                                    </div>    
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">총액</span>
                                            <input type="text" name="carTotalAmount[]" class="form-control text-right carTotalAmount" aria-label="총액" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">메모</span>
                                            <input type="text" name="carMemo[]" class="form-control" aria-label="메모" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="car_seq" value="0">
                        </tr>
                        <?php }else{
                        $xx=0;    
                        while($datarow = mysql_Fetch_assoc($rst6)){    
                            $etcamt2 = $etcamt2 +  $datarow['etc_amt'];
                        ?> 
                        <tr class="basic-class7" param ="tr-parent"> 
                            <?php if($xx==0) { ?>
                            <td rowspan="<?=$rst6_cnt?>" colspan="1" class="active text-center formHeader">차량&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <?php }?>                      
                            <td colspan="5">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <select class="form-control" name="carSelect[]">
                                            <option>- 선택 -</option>
                                            <?php mysqli_data_seek($guide_g03, 0);  while($row1 = mysql_Fetch_assoc($guide_g03)){ ?>  
                                            <option <?php if ($datarow['etc_type'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
                                                 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                            <?php }?>
                                        </select>  
                                    </div>    
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">총액</span>
                                            <input type="text" name="carTotalAmount[]" class="form-control text-right carTotalAmount" aria-label="총액" value="<?=$datarow['etc_amt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">메모</span>
                                            <input type="text" name="carMemo[]" class="form-control" aria-label="메모" value="<?=$datarow['etc_memo']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="car_seq" value="<?=$datarow['seq_no']?>">

                        </tr>
                        
                        <?php $xx++; }}?>
                        <?php if($rst7_cnt <=0) { ?>
                        <tr class="basic-class8" param ="tr-parent"> 
                            <td colspan="1" class="active text-center formHeader">기타&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            
                            <td colspan="5">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <select class="form-control" name="etcCarSelect[]">
                                            <option>- 선택 -</option>
                                            <?php mysqli_data_seek($guide_g03, 0);  while($row1 = mysql_Fetch_assoc($guide_g03)){ ?>  
                                            <option value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                            <?php }?>
                                        </select>  
                                    </div>    
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">총액</span>
                                            <input type="text" name="etcTotalAmount[]" class="form-control text-right etcTotalAmount" aria-label="총액" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">메모</span>
                                            <input type="text" name="etcMemo[]" class="form-control" aria-label="메모" value=""/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="etc_seq" value="0">
                            
                        </tr>
                        <?php }else{
                        $ww=0;
                        while($datarow = mysql_Fetch_assoc($rst7)){
                            $etcamt3 = $etcamt3 +  $datarow['etc_amt'];    
                        ?>  
                        <tr class="basic-class8" param ="tr-parent"> 
                            <?php if($ww==0) { ?>
                            <td colspan="1" rowspan="<?=$rst7_cnt?>" class="active text-center formHeader">기타&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                            <?php }?>             
                            <td colspan="5">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <select class="form-control" name="etcCarSelect[]">
                                            <option>- 선택 -</option>
                                            <?php mysqli_data_seek($guide_g03, 0);  while($row1 = mysql_Fetch_assoc($guide_g03)){ ?>  
                                            <option <?php if ($datarow['etc_type'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
                                                 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                            <?php }?>
                                        </select>  
                                    </div>    
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">총액</span>
                                            <input type="text" name="etcTotalAmount[]" class="form-control text-right etcTotalAmount" aria-label="총액" value="<?=$datarow['etc_amt']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-addon">메모</span>
                                            <input type="text" name="etcMemo[]" class="form-control" aria-label="메모" value="<?=$datarow['etc_memo']?>"/>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 hide button-minus">
                                        <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                    </div>
                                </div>
                            </td>
                            <input type="hidden" name="etc_seq" value="<?=$datarow['seq_no']?>">
                        </tr>
                        <?php $ww++;}}?>
                        <?php $etcsum33 = $etcamt1+$etcamt2+$etcamt3; ?>
                        <tr>
                            <td colspan="1" class="active text-center formHeader">합계</td>
                            <td colspan="5" class="etcsum"><?=$etcsum33?></td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-center formHeader fullWidth">쇼핑정산</td>
                        </tr>
                        <tr>
                            <td colspan="6">
                                <table class="table table-striped table-side-no-bordered table-hover table-condensed text-center">
                                    <thead>
                                        <tr>
										  <th scope="col" width="1%"></th>
                                          <th scope="col">쇼핑</th>
                                          <th scope="col">판매총액</th>
                                          <th scope="col">홈쇼핑컴</th>
                                          <th scope="col">회사수익</th>
                                          <th scope="col">가이드수익</th>
										  <th scope="col" width="1%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($rst7_cnt <=0) { ?>
                                        <tr class="basic-class9" param ="tr-parent">
										 <td>&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                                         <td>
                                              <select class="form-control shoppingSelect" name="shoppingSelect[]">
                                                <option selected>- 선택 -</option>
                                                <?php 
                                                mysqli_data_seek($guide_g04, 0);
                                                while($row1 = mysql_Fetch_assoc($guide_g04)){ ?>  
                                                    <option value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                                <?php }?>
                                              </select>
                                          </td>
                                          <td><input type="text" name="saleTotalAmount[]" class="form-control text-right saleTotalAmount" aria-label="판매총액" value=""/></td>
                                          <td><input type="text" name="homeshoppingcom[]" class="form-control text-right homeshoppingcom" aria-label="홈쇼핑컴" value=""/></td>
                                          <td><input type="text" name="companyProfit[]" class="form-control text-right companyProfit" aria-label="회사수익" value=""/></td>
                                          <td><input type="text" name="shoppingGuideProfit[]" class="form-control text-right shoppingGuideProfit" aria-label="가이드수익" value=""/></td>
										  <td><div class="col-sm-1 hide button-minus">
                                                <button type="button" class="btn btn-default btn-xs js-removeHotelButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
                                            </div>
                                          </td>
                                          <input type="hidden" name="shopp_seq" value="0">
                                        </tr>
                                        <?php }else{
                                        $ss=0;$aaa33 = 0;
                                        while($datarow = mysql_Fetch_assoc($rst8)){
                                            $sales_amt1 = $sales_amt1 + $datarow['tot_amt'];
                                            $sales_amt2 = $sales_amt2 + $datarow['home_comamt'];
                                            $sales_amt3 = $sales_amt3 + $datarow['c_profit'];
                                            $sales_amt4 = $sales_amt4 + $datarow['g_profit'];

                                            if($datarow['shop_code'] =='G04|01') $aaa33 = $aaa33 + $datarow['tot_amt'];
                                        ?>

                                        <tr class="basic-class9" param ="tr-parent">
                                         <?php if($ss==0) { ?>   
										 <td rowspan="<?=$rst8_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                                         <?php }?>
                                         <td>
                                              <select class="form-control shoppingSelect" name="shoppingSelect[]">
                                                <option selected>- 선택 -</option>
                                                <?php 
                                                mysqli_data_seek($guide_g04, 0);
                                                while($row1 = mysql_Fetch_assoc($guide_g04)){ ?>  
                                                <option <?php if ($datarow['shop_code'] == $row1['lvcode1']."|".$row1['lvcode2']) { ?> selected <?php } ?> 
                                                 value="<?=$row1['lvcode1']?>|<?=$row1['lvcode2']?>"><?=$row1['comment']?></option>
                                                <?php }?>
                                              </select>
                                          </td>
                                          <td><input type="text" name="saleTotalAmount[]" class="form-control text-right saleTotalAmount" aria-label="판매총액" value="<?=$datarow['tot_amt']?>"/></td>
                                          <td><input type="text" name="homeshoppingcom[]" class="form-control text-right homeshoppingcom" aria-label="홈쇼핑컴" value="<?=$datarow['home_comamt']?>"/></td>
                                          <td><input type="text" name="companyProfit[]" class="form-control text-right companyProfit" aria-label="회사수익" value="<?=$datarow['c_profit']?>"/></td>
                                          <td><input type="text" name="shoppingGuideProfit[]" class="form-control text-right shoppingGuideProfit" aria-label="가이드수익" value="<?=$datarow['g_profit']?>"/></td>
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
                                          <td class="text-right salesamt1"><?=number_format($sales_amt1,2)?></td>
                                          <td class="text-right salesamt2"><?=number_format($sales_amt2,2)?></td>
                                          <td class="text-right salesamt3"><?=number_format($sales_amt3,2)?></td>
                                          <td class="text-right salesamt4"><?=number_format($sales_amt4,2)?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                       <!--<tr>
                            <td colspan="1" class="active text-center formHeader">선지급행사비</td>
                            <td colspan="2"><input type="text" name="guideEtcDepAmount" class="form-control text-right" aria-label="선지급행사비" value="<?=$data_row00['pre_amt']?>"/></td>
                        </tr>-->
                        <tr>
                            <table id="custom_table1" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                                <tbody>
                                    <!--3page-->
									
                                    <tr>
                                        <td colspan="16" class="text-center formHeader fullWidth">가이드 납입금</td>
                                    </tr>
									

									<?php if($rst9_cnt <=0) { ?>
									<tr  class="basic-class10" param ="tr-parent">
                                       
										 <td colspan="1">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow1"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                                        
                                         
                                        <td colspan="15">
										    <div class="row">
											  <div class="col-sm-2">
												 
												  <select class="form-control ipSelect" name="ipSelect[]">
													<option selected>- 선택 -</option>
													<?php 
													mysqli_data_seek($guide_g05, 0);
													while($row1 = mysql_Fetch_assoc($guide_g05)){ ?>  
													<option value="<?=$row1['comment']?>"><?=$row1['comment']?></option>
													<?php }?>
												  </select>
													
											  </div>
											   <div class="col-sm-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-addon">납입금</span>
                                                        <input type="text" class="form-control text-right g_inputamt" name="g_inputamt[]" aria-label="납입금" value=""/>
                                                    </div>    
                                                </div>    
                                                <div class="col-sm-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-addon">인원</span>
                                                        <input type="text" class="form-control g_inputcnt" name="g_inputcnt[]" aria-label="인원" value=""/>
                                                    </div>
                                                </div>
												<div class="col-sm-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-addon">총액</span>
                                                        <input type="text" class="form-control g_inputuamt" name="g_inputuamt[]" aria-label="총액" value=""/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-addon">메모</span>
                                                        <input type="text" class="form-control" name="g_inputmemo[]" aria-label="메모" value=""/>
                                                    </div>
													
                                                </div>
												<div class="col-sm-1 hide button-minus">
												 <button type="button" class="btn btn-default btn-xs js-removegButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
												</div>
                                            </div>
                                        </td>
                                        <input type="hidden" name="g_seqno" value="0">
                                    </tr>
									<?php } else { 
									$ss=0;$totalpaysum = 0;
                                        while($datarow = mysql_Fetch_assoc($rst9)){
                                            
                                            
											$totalusum2 = $datarow['input_cnt'] * $datarow['input_amt'];
											$totalpaysum = $totalpaysum + $totalusum2;
											//echo $datarow['inputamt_type'].'<br/>';
											//var_dump($datarow);
										
                                     ?>
                                  <tr  class="basic-class10" param ="tr-parent">
                                         <?php if($ss==0) { ?>   
										 <td rowspan="<?=$rst9_cnt?>">&nbsp;<button type="button" class="btn btn-default btn-xs js-addPlusRow1"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button></td>
                                         <?php }?>
                                         <td colspan="15">
                                            <div class="row">
											    <div class="col-sm-2">
                                              
														  <select class="form-control ipSelect" name="ipSelect[]">
															<option selected>- 선택 -</option>
															<?php 
															mysqli_data_seek($guide_g05, 0);
															while($row1 = mysql_Fetch_assoc($guide_g05)){ ?>  
															<option <?php if ($datarow['inputamt_type'] == $row1['comment']) { ?> selected <?php } ?> 
															 value="<?=$row1['comment']?>"><?=$row1['comment']?></option>
															<?php }?>
														  </select>
                                           
                                                </div> 
                                                <div class="col-sm-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-addon">납입금</span>
                                                        <input type="text" class="form-control text-right g_inputamt" name="g_inputamt[]" aria-label="납입금" value="<?=$datarow['input_amt']?>"/>
                                                    </div>    
                                                </div>    
                                                <div class="col-sm-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-addon">인원</span>
                                                        <input type="text" class="form-control g_inputcnt" name="g_inputcnt[]" aria-label="인원" value="<?=$datarow['input_cnt']?>"/>
                                                    </div>
                                                </div>
												<div class="col-sm-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-addon">총액</span>
                                                        <input type="text" class="form-control g_inputuamt" name="g_inputuamt[]" aria-label="총액" value="<?=$totalusum2?>"/>
                                                    </div>
                                                </div>
                                                <div class="col-sm-3">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-addon">메모</span>
                                                        <input type="text" class="form-control" name="g_inputmemo[]" aria-label="메모" value="<?=$datarow['input_memo']?>"/>
                                                    </div>
                                                </div>
												<div class="col-sm-1 button-minus">
												 <button type="button" class="btn btn-default btn-xs js-removegButton"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></button>
												</div>1
                                            </div>
                                        </td>
                                        <input type="hidden" name="g_seqno" value="<?=$datarow['seq_no']?>">
										
                                    </tr>
                                <?php $ss++; 	}} ?>
											
                                    <tr>
                                        <td class="active text-center formHeader">납입금총액</td>
                                        <td colspan="15" class="totalpaysum">$<?php echo $totalpaysum;?></td>
                                    </tr>
									<tr>
										 <td colspan='16'>
											
											<?php
											// 위에서 만든 $check_rows 사용 (없으면 빈 1행 렌더)
											$check_rows = (isset($check_rows) && $check_rows !== null && is_array($check_rows)) ? $check_rows : [];

											?>
											<div class="panel panel-default">
											  <div class="panel-heading text-center"><b>체크입력</b></div>
											  <div class="panel-body" style="padding:0">
												<div class="table-responsive">
												  <table class="table table-bordered table-condensed" style="margin:0">
													<thead>
													  <tr class="active" style="text-align:center">
														<th style="width:60px"></th>
														<th style="width:200px">체크번호</th>
														<th style="width:200px">은행/발행처</th>
														<th style="width:180px">사용일</th>
														<th style="width:180px">금액</th>
														<th>비고</th>
													  </tr>
													</thead>
													<tbody id="checkTable">
													  <?php if (count($check_rows) === 0) { ?>
														<tr>
														  <td class="text-center">
															<div class="btn-group btn-group-xs">
															  <button type="button" class="btn btn-default" onclick="addCheckRow()"><b>+</b></button>
															</div>
															<input type="hidden" name="check_id[]" value="0">
														  </td>
														  <td><input type="text" name="check_no[]"   class="form-control input-sm"></td>
														  <td><input type="text" name="bank_name[]"  class="form-control input-sm"></td>
														  <td><input type="date" name="used_date[]"  class="form-control input-sm"></td>
														  <td><input type="text" name="amount[]"     class="form-control input-sm money" value="0.00" oninput="fmtMoney(this);sumCheck();"></td>
														  <td><input type="text" name="note[]"       class="form-control input-sm"></td>
														</tr>
													  <?php } else { foreach ($check_rows as $r) { ?>
														<tr>
														  <td class="text-center">
															<div class="btn-group btn-group-xs">
															  <button type="button" class="btn btn-default" onclick="addCheckRow()"><b>+</b></button>
															  <button type="button" class="btn btn-danger"  onclick="removeCheckRow(this, <?= (int)$r['id'] ?>)">-</button>
															</div>
															<input type="hidden" name="check_id[]" value="<?= (int)$r['id'] ?>">
														  </td>
														  <td><input type="text" name="check_no[]"   class="form-control input-sm" value="<?= htmlspecialchars($r['check_no'],ENT_QUOTES) ?>"></td>
														  <td><input type="text" name="bank_name[]"  class="form-control input-sm" value="<?= htmlspecialchars($r['bank_name'],ENT_QUOTES) ?>"></td>
														  <td><input type="date" name="used_date[]"  class="form-control input-sm" value="<?= htmlspecialchars($r['used_date'],ENT_QUOTES) ?>"></td>
														  <td><input type="text" name="amount[]"     class="form-control input-sm money" value="<?= number_format((float)$r['amount'],2,'.','') ?>" oninput="fmtMoney(this);sumCheck();"></td>
														  <td><input type="text" name="note[]"       class="form-control input-sm" value="<?= htmlspecialchars($r['note'],ENT_QUOTES) ?>"></td>
														</tr>
													  <?php }} ?>
													</tbody>
													<tfoot>
													  <tr class="active">
														<td colspan="4" class="text-right"><b>합계</b></td>
														<td class="text-right"><b id="sum_check_amount">0.00</b></td>
														<td></td>
													  </tr>
													</tfoot>
												  </table>
												</div>
											  </div>
											</div>

											<!-- 삭제ID를 담아 보낼 컨테이너 (삭제 사용 시) -->
											<div id="checkDeleteBin" style="display:none"></div>


										</td>
									</tr>

                                    <tr>
                                        <td colspan="16" class="text-center formHeader fullWidth">가이드정산합계</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="active text-center formHeader input_text_color">총입금액</td>
                                        <td colspan="3" class="active text-center formHeader">선지급행사비</td>
                                        <td colspan="3" class="active text-center formHeader">옵션수익</td>
                                        <td colspan="3" class="active text-center formHeader">가이드입금액</td>
										<td colspan="2" class="active text-center formHeader"></td>
										<td colspan="2" class="active text-center formHeader"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-right depotamt1 input_text_color"></td>
                                        <td colspan="3" class="text-right deportamt2"><?=number_format($data_row['pre_amt'],2)?></td>
                                        
                                        <td colspan="3" class="text-right deportamt4"></td>
                                        <td colspan="3" class="text-right deportamt5"><?=$totalpaysum?></td>
                                        <td colspan="2" class="text-right deportamt6"></td>
										<td colspan="2" class="text-right deportamt3"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="active text-center formHeader">총지급액</td>
                                        <td colspan="3" class="active text-center formHeader">식비</td>
                                        <td colspan="3" class="active text-center formHeader">입장비</td>
                                        <td colspan="3" class="active text-center formHeader">가이드/차량/기타</td>
                                        <td colspan="2" class="active text-center formHeader">쇼핑정산</td>
                                        <td colspan="2" class="active text-center formHeader input_text_color">가이드정산금액</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-right sumamtpay1"></td>
                                        <td colspan="3" class="text-right sumamtpay2"></td>
                                        <td colspan="3" class="text-right sumamtpay3"></td>
                                        <td colspan="3" class="text-right sumamtpay4"></td>
                                        <!--<td colspan="2" class="text-right sumamtpay5"><?=$aaa33?></td>-->
                                        <td colspan="2" class="text-right sumamtpay5"></td>
                                        <td colspan="2" class="text-right sumamtpay6"></td>
                                    </tr>
                            
                                </tbody>
                            </table>
                               
				
                        </tr>
                    </tbody>
                </table>
				
			</form>
		</div>
	</div>

    

    <?php
		include "include/side_m.php"
	?>
    <script>

        var number = "<?=$_GET['number']?>";
        var aaa33 = 0;

        sumamtpay();

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
                //$('.hotel-minus').not( ':first' ).removeClass('hide');
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
            });
			$('.js-addPlusRow1').on( 'click', function () {
                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table1 ."+cls+":last"));
                //$('.hotel-minus').not( ':first' ).removeClass('hide');
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
				
                totalMealSum();
                totalEnSum();
                totalOptSum();
                EtcAmount();
                totalSalesSum();

            });
            $('.js-addPlusRow2').on( 'click', function () {
                var clickedRow = $(this).parent().parent();
                var cls = clickedRow.attr("class");
                var newrow = clickedRow.clone();
                newrow.removeAttr('param');
                newrow.find("td:eq(0)").remove();
                newrow.insertAfter($("#custom_table ."+cls+":last"));
                //$('.hotel-minus').not( ':first' ).removeClass('hide');
                
                var attr = newrow.attr('param');
                if (typeof attr == typeof undefined) newrow.find('.button-minus').removeClass('hide');
                resizeRowspan(cls);
            });

            $(document).on("click", ".js-removeHotelButton", function(){
                var clickedRow = $(this).closest('tr').remove();
                var cls = clickedRow.attr("class");
                resizeRowspan(cls);

                totalMealSum();
                totalEnSum();
                totalOptSum();
                EtcAmount();
                totalSalesSum();


            });
			$(document).on("click", ".js-removegButton", function(){
                var clickedRow = $(this).closest('tr').remove();
                var cls = clickedRow.attr("class");
                resizeRowspan(cls);

                totalMealSum();
                totalEnSum();
                totalOptSum();
                EtcAmount();
                totalSalesSum();


            });
			totalPaySum();
		})
        function moneyToNumber(v){
		  v = (v||'').toString().replace(/,/g,'').trim();
		  if (v==='' || v==='.' || v==='-') return 0;
		  var n = parseFloat(v);
		  return isNaN(n) ? 0 : n;
		}
		function fmtMoney(inp){
		  // 숫자/소수점/마이너스만 허용
		  inp.value = (inp.value||'').replace(/[^0-9.\-]/g,'');
		}
		function sumCheck(){
		  var sum = 0;
		  document.querySelectorAll('input[name="amount[]"]').forEach(function(el){
			sum += moneyToNumber(el.value);
		  });
		  document.getElementById('sum_check_amount').textContent = sum.toFixed(2);
		}
		function addCheckRow(){
		  var tb = document.getElementById('checkTable');
		  var tr = document.createElement('tr');
		  tr.innerHTML =
			'<td class="text-center">'+
			  '<div class="btn-group btn-group-xs">'+
				'<button type="button" class="btn btn-default" onclick="addCheckRow()"><b>+</b></button>'+
				'<button type="button" class="btn btn-danger"  onclick="removeCheckRow(this, 0)">-</button>'+
			  '</div>'+
			  '<input type="hidden" name="check_id[]" value="0">'+
			'</td>'+
			'<td><input type="text" name="check_no[]"   class="form-control input-sm"></td>'+
			'<td><input type="text" name="bank_name[]"  class="form-control input-sm"></td>'+
			'<td><input type="date" name="used_date[]"  class="form-control input-sm"></td>'+
			'<td><input type="text" name="amount[]"     class="form-control input-sm money" value="0.00" oninput="fmtMoney(this);sumCheck();"></td>'+
			'<td><input type="text" name="note[]"       class="form-control input-sm"></td>';
		  tb.appendChild(tr);
		  sumCheck();
		}
		function removeCheckRow(btn, id){
		  // 폼에서 삭제 처리: 기존행(id>0)은 삭제 배열에 담고, DOM에서 제거
		  if (id && id > 0) {
			var bin = document.getElementById('checkDeleteBin');
			var hid = document.createElement('input');
			hid.type  = 'hidden';
			hid.name  = 'check_del[]';
			hid.value = id;
			bin.appendChild(hid);
		  }
		  var tr = btn.closest('tr');
		  tr.parentNode.removeChild(tr);
		  sumCheck();
		}
		// 초기 합계
		document.addEventListener('DOMContentLoaded', sumCheck);
		
		
		
        function resizeRowspan(cls){
            var rowspan = $("."+cls).length;
            $("."+cls+":first td:eq(0)").attr("rowspan", rowspan);
        }

        //조식,중식,석식 원가총액계산        
        $(document).on('focusout',".bfPerson",function () {
            BcalcuAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".bfCost",function () {
            BcalcuAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".lunchPerson",function () {
            LcalcuAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".lunchCost",function () {
            LcalcuAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".dinnerPerson",function () {
            DcalcuAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".dinnerCost",function () {
            DcalcuAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".bfTotalCost",function () {
            totalMealSum();
        });

        $(document).on('focusout',".lunchTotalCost",function () {
            totalMealSum();
        });

        $(document).on('focusout',".dinnerTotalCost",function () {
            totalMealSum();
        });

        
        //조식원가총액
        function BcalcuAmount(aa) {
            var row = aa;
            var person = parseInt(row.find('.bfPerson').val() || 0);
            var cost = parseFloat(row.find('.bfCost').val() || 0);
            row.find('.bfTotalCost').val(person*cost);

            totalMealSum();
        }
        
        //중식원가총액
        function LcalcuAmount(aa) {
            var row = aa;
            var person = parseInt(row.find('.lunchPerson').val() || 0);
            var cost = parseFloat(row.find('.lunchCost').val() || 0);
            row.find('.lunchTotalCost').val(person*cost);

            totalMealSum();

        }

        //석식원가총액
        function DcalcuAmount(aa) {
            var row = aa;
            var person = parseInt(row.find('.dinnerPerson').val() || 0);
            var cost = parseFloat(row.find('.dinnerCost').val() || 0);
            row.find('.dinnerTotalCost').val(person*cost);

            totalMealSum();
        }

        //숫자콤마
        function setComma(value){
            value =  value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            return value;
        }

        //조식,중식,석식 총합계계산
        function totalMealSum(){

            var b1=0,b2=0,b3=0,l1=0,l2=0,l3=0,d1=0,d2=0,d3=0;
            var bp=0,bc=0,lp=0,lc=0,dp=0,dc=0;
            
            $("input[name='bfPerson[]']").each(function(){
                bp = parseInt(bp) + parseInt(this.value);
            });

            $("input[name='bfCost[]']").each(function(){
                bc = parseFloat(bc) + parseFloat(this.value);
            });

            $("input[name='lunchPerson[]']").each(function(){
                lp = parseInt(lp) + parseInt(this.value);
            });

            $("input[name='lunchCost[]']").each(function(){
                lc = parseFloat(lc) + parseFloat(this.value);
            });

            $("input[name='dinnerPerson[]']").each(function(){
                dp = parseInt(dp) + parseInt(this.value);
            });

            $("input[name='dinnerCost[]']").each(function(){
                dc = parseFloat(dc) + parseFloat(this.value);
            });

            bp = getNum(bp);
            bc = getNum(bc);
            lp = getNum(lp);
            lc = getNum(lc);
            dp = getNum(dp);
            dc = getNum(dc);

            $(".sub_total_b1").text(bp);
            $(".sub_total_b2").text(setComma(Number(bc).toFixed(2)));


            $(".sub_total_l1").text(lp);
            $(".sub_total_l2").text(setComma(Number(lc).toFixed(2)));


            $(".sub_total_d1").text(dp);
            $(".sub_total_d2").text(setComma(Number(dc).toFixed(2)));

            $("input[name='bfTotalCost[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
            });

            $("input[name='lunchTotalCost[]']").each(function(){
                l3 = parseFloat(l3) + parseFloat(this.value);
            });

            $("input[name='dinnerTotalCost[]']").each(function(){
                d3 = parseFloat(d3) + parseFloat(this.value);
            });

            b3 = getNum(b3);
            l3 = getNum(l3);
            d3 = getNum(d3);

            
            //서브 원가총액 합계
            $(".sub_total_b3").text(setComma(Number(b3).toFixed(2)));
            $(".sub_total_l3").text(setComma(Number(l3).toFixed(2)));
            $(".sub_total_d3").text(setComma(Number(d3).toFixed(2)));
            

            //총인원
            $(".total_bp").text("조식인원합계 :"+Number(bp));
            //총원가
            $(".total_bc").text("조식원가/P총액 :"+setComma(parseFloat(bc).toFixed(2)));
            //총인원
            $(".total_lp").text("중식인원합계 :"+Number(lp));
            //총원가
            $(".total_lc").text("중식원가/P총액 :"+setComma(parseFloat(lc).toFixed(2)));
            //총인원
            $(".total_dp").text("석식인원합계 :"+Number(dp));
            //총원가
            $(".total_dc").text("석식원가/P총액 :"+setComma(parseFloat(dc).toFixed(2)));


            //총원가총액
            $(".total_bt").text("조식원가총액:"+ setComma(parseFloat(b3).toFixed(2)));
            $(".total_lt").text("중식원가총액:"+ setComma(parseFloat(l3).toFixed(2)));
            $(".total_dt").text("석식원가총액:"+ setComma(parseFloat(d3).toFixed(2)));
            

            //식사총비용
            $(".total_mealprice").text("식사비총액 : "+ setComma(parseFloat(b3+l3+d3).toFixed(2)));

            sumamtpay();

        }

        $(document).on('focusout',".en_person",function () {
            EnAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".en_cost",function () {
            EnAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".en_totalprice",function () {
            totalEnSum();
        });

        //입장비 계산 
        function EnAmount(aa) {
            var row = aa;
            var person = parseInt(row.find('.en_person').val() || 0);
            var cost = parseFloat(row.find('.en_cost').val() || 0);
            row.find('.en_totalprice').val(person*cost);

            totalEnSum();
        }

        function totalEnSum(){
            var b1=0,b2=0,b3=0;
            
            $("input[name='person[]']").each(function(){
                b1 = parseInt(b1) + parseInt(this.value);
            });

            $("input[name='cost[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
            });

            $("input[name='totalAmount[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
            });

            b1 = getNum(b1);
            b2 = getNum(b2);
            b3 = getNum(b3);

            $(".en_totalperson").text("총인원 :"+Number(b1));
            $(".en_totalsum").text("총원가 총액 : $"+ setComma(parseFloat(b3).toFixed(2)));

            sumamtpay();

        }

        $(document).on('focusout',".optperson",function () {
            OptAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".optcost",function () {
            OptAmount($(this).closest("tr"));
        });

        $(document).on('focusout',".optprice",function () {
            OptAmount($(this).closest("tr"));
        });
		$(document).on('change',".optset",function () {
			
            OptAmount($(this).closest("tr"));
        });
        function OptAmount(aa) {
            var row = aa;
            var person = parseInt(row.find('.optperson').val() || 0);
            var cost = parseFloat(row.find('.optcost').val() || 0);
            var optprice = parseFloat(row.find('.optprice').val() || 0);
            var optdiffamount = parseFloat(person*optprice) - parseFloat(person*cost);
           // var optprofit =  (parseFloat(optdiffamount) * 0.6 ).toFixed(2);
            
			var optg=row.find('.optset').val();
			if (optg == '55')
			{
				var optprofit = ( parseFloat(optdiffamount) * 0.5).toFixed(2);
			} 
			if (optg == '64')
			{
				var optprofit = ( parseFloat(optdiffamount) * 0.6).toFixed(2);
			}
			if (optg == '73')
			{
				var optprofit = ( parseFloat(optdiffamount) * 0.7).toFixed(2);
			}

			if (optg == '55')
			{
				var optguideprofit = ( parseFloat(optdiffamount) * 0.5).toFixed(2);
			} 
			if (optg == '64')
			{
				var optguideprofit = ( parseFloat(optdiffamount) * 0.4).toFixed(2);
			}
			if (optg == '73')
			{
				var optguideprofit = ( parseFloat(optdiffamount) * 0.3).toFixed(2);
			}
			

            //alert(optguideprofit);
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

            $(".optsum1").text(b1);
            $(".optsum2").text(b2);
            $(".optsum3").text(b3);
            $(".optsum4").text(b4);
            $(".optsum5").text(b5);
            $(".optsum6").text(b6);
            $(".optsum7").text(b7);
            $(".optsum8").text(b8);

            sumamtpay();
        }

        $(document).on('focusout',".guideTotalAmount",function () {
            EtcAmount();
        });
        $(document).on('focusout',".carTotalAmount",function () {
            EtcAmount();
        });
        $(document).on('focusout',".etcTotalAmount",function () {
            EtcAmount();
        });

        function EtcAmount(){
            var b1=0,b2=0,b3=0,b4=0;
            
            $("input[name='guideTotalAmount[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });

            $("input[name='carTotalAmount[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
            });

            $("input[name='etcTotalAmount[]']").each(function(){
                b3 = parseFloat(b3) + parseFloat(this.value);
            });

            b1 = getNum(b1);
            b2 = getNum(b2);
            b3 = getNum(b3);
            b4 = parseFloat(b1+b2+b3);

            $(".etcsum").text(setComma(b4.toFixed(2)));
            sumamtpay();
        }

        $(document).on('focusout',".saleTotalAmount",function () {
            salesAmount($(this).closest("tr"));
        });

        function salesAmount(aa) {
            var row = aa;
            var saleTotalAmount = parseFloat(row.find('.saleTotalAmount').val() || 0);
            var homeshoppingcom = saleTotalAmount *0.15;
            var companyProfit =  parseFloat(homeshoppingcom) * 0.6;
            var shoppingGuideProfit =  parseFloat(homeshoppingcom) * 0.4;
            var shoppingSelect = row.find('.shoppingSelect').val();

            row.find('.homeshoppingcom').val(homeshoppingcom.toFixed(2));
            row.find('.companyProfit').val(companyProfit.toFixed(2));
            row.find('.shoppingGuideProfit').val(shoppingGuideProfit.toFixed(2));


            totalSalesSum();
        }

        function totalSalesSum(){
            var b1=0,b2=0,b3=0,b4=0;
            
            $("input[name='saleTotalAmount[]']").each(function(){
                b1 = parseFloat(b1) + parseFloat(this.value);
            });

            $("input[name='homeshoppingcom[]']").each(function(){
                b2 = parseFloat(b2) + parseFloat(this.value);
            });

            $("input[name='companyProfit[]']").each(function(){
               // b3 = parseFloat(b3) + parseFloat(this.value);
            });

            $("input[name='shoppingGuideProfit[]']").each(function(){
                b4 = parseFloat(b4) + parseFloat(this.value);
            });

            b1 = getNum(b1);
            b2 = getNum(b2);
            b3 = getNum(b3);
            b4 = getNum(b4);

            $(".salesamt1").text(setComma(b1.toFixed(2)));
            $(".salesamt2").text(setComma(b2.toFixed(2)));
            //$(".salesamt3").text(setComma(b3.toFixed(2)));
            $(".salesamt4").text(setComma(b4.toFixed(2)));
            sumamtpay();
        }

        $(document).on('focusout',".g_inputamt",function () {
			//$(this).val(parseFloat($(this).closest("tr").find('.g_inputcnt').val()); * parseFloat($(this).closest("tr").find('.g_inputamt').val()));
            totalPaySum();
			sumamtpay();
        });
        

		$(document).on('focusout',".g_inputcnt",function () {
            totalPaySum();
			sumamtpay();
        });
       
        function totalPaySum(){
           var guiamt =0;
           $("input[name='g_inputamt[]']").each(function(){
                
                    guiamt  = parseFloat(guiamt) + parseFloat($(this).closest("tr").find('.g_inputuamt').val());
               

            });
            $(".totalpaysum").text("$"+setComma(guiamt.toFixed(2)));    
			$(".deportamt5").text("$"+setComma(guiamt.toFixed(2)));    
        }

        function getNum(val) {
            if (isNaN(val)) {
                return 0;
            }
            return val;
        }

        //총식비,총입장비,총가이드/차량/기타,총쇼핑
        function sumamtpay(){

            var regex = /[^0-9.,-]/g;	
            //var regex1 = /[^0-9.]/g;
            var regex1 = /[^0-9.-]/g;
            var regex2 = /[^0-9.-]/g;	
            var total=0;
            var a1=0
            var a3333=0;
			var comamt=0;
			var guiamt=0;


            var dd33 = $(".optsum7").text().replace(regex, "");

          //  $(".deportamt3").text($(".salesamt3").text().replace(regex, ""));  
            $(".deportamt4").text(setComma(dd33));

            totalPaySum();
			$("select[name='assignGuideLine[]']").each(function(){
                /// if(this.value =='') {   //총지급액
                    //guiamt  = parseFloat(guiamt) + parseFloat($(this).closest("tr").find('.optprofit').val());
                    guiamt  = parseFloat(guiamt) + parseFloat($(this).closest("tr").find('.optguideprofit').val());
                // } else if(this.value =='GUI') { //총입금액
					//comamt = parseFloat(comamt) + parseFloat($(this).closest("tr").find('.optguideprofit').val());  
                    comamt = parseFloat(comamt) + parseFloat($(this).closest("tr").find('.optprofit').val()); 
				/// } 


            });
			$("select[name='shoppingSelect[]']").each(function(){
                 //if(this.value =='G04|01') {
                    a3333 = a3333 + parseFloat($(this).closest("tr").find('.saleTotalAmount').val());
                 //}   
            });
            

            var aaa = "<?=$data_row['pre_amt']?>";
            var bbb = parseFloat($(".salesamt3").text().replace(regex1, ""));
            //var ccc = parseFloat(dd33);
            var ccc = parseFloat(comamt);
            var lstamt =  parseFloat($(".totalpaysum").text().replace(regex1, ""));
			
            var depotamt1 = 0;
            aaa = parseFloat(aaa);
			//alert(ccc);
			//alert(lstamt);
            depotamt1 = aaa+bbb+ccc+lstamt;

            //$(".depotamt1").text(setComma(depotamt1.toString())); 

            $(".depotamt1").text(setComma(depotamt1.toString())); 
            
            var ddd = parseFloat($(".salesamt2").text().replace(regex1, ""));
            var eee = parseFloat($(".salesamt4").text().replace(regex1, ""));
            

            //var total = parseFloat($(".total_mealprice").text().replace(regex1, "")) + parseFloat($(".en_totalsum").text().replace(regex1, "")) + parseFloat($(".etcsum").text().replace(regex1, "")) + parseFloat($(".salesamt1").text().replace(regex1, ""));
            var total = parseFloat($(".total_mealprice").text().replace(regex1, "")) + parseFloat($(".en_totalsum").text().replace(regex1, "")) + parseFloat($(".etcsum").text().replace(regex1, "")) + parseFloat(ddd+eee) ;
            
            total = getNum(total);
			
			//total= total + parseFloat(comamt);
            $(".sumamtpay1").text(setComma(total.toString()));
			
            $(".sumamtpay2").text($(".total_mealprice").text().replace(regex, ""));
            $(".sumamtpay3").text($(".en_totalsum").text().replace(regex, ""));
            $(".sumamtpay4").text($(".etcsum").text().replace(regex, ""));
           /// $(".sumamtpay5").texttext(setComma(total.toString()));
            //$(".sumamtpay5").text(setComma((ddd+eee).toString()));
			$(".sumamtpay5").text(setComma((ddd).toString()));


            
            var fff = parseFloat($(".depotamt1").text().replace(regex1, ""));         
            var ggg = parseFloat($(".sumamtpay2").text().replace(regex1, ""))+
                parseFloat($(".sumamtpay3").text().replace(regex1, ""))+
                parseFloat($(".sumamtpay4").text().replace(regex1, ""))+
                parseFloat($(".sumamtpay5").text().replace(regex1, ""));
            //var hhh = (fff - ggg).toFixed(2);
            var hhh1 =  parseFloat($(".depotamt1").text().replace(regex2, "")) ;
            var hhh2 = parseFloat($(".sumamtpay1").text().replace(regex2, ""));

            var hhh = (hhh1 - hhh2).toFixed(2);

            //$(".sumamtpay6").text(setComma(hhh.toString()));
            $(".sumamtpay6").text(setComma(hhh.toString()));

        }

        //저장클릭
        $(document).on("click",".js-save",function(e) { 
            if(confirm("저장할까요?") == true){
				var form = $("#frnName").closest("form");
				var formData = new FormData(form[0]);
				$.ajax({
					type: 'POST',
					url: 'guide_save1.php',
					data: formData,
					cache:false,
					processData: false,
					contentType: false,
					success: function (response) {
						var msg = response.split("/");

						if(msg[0] =='0') {
							alert(msg[1]);

							return false;
						}else{
							alert("저장했습니다!!");
							location.href = "guide_mysettle.php?division=6&pdx=2&sub=15";
						}
					}
				});
			}

            //document.getElementById("frnName").submit();  
		});

        //삭제클릭
        $(document).on("click",".js-delete",function(e) { 
			if(confirm("삭제할까요?") == true){
					$("#mode").val("delete");
					
					var form = $("#frnName").closest("form");
					var formData = new FormData(form[0]);

					$.ajax({
						type: 'POST',
						url: 'guide_save1.php',
						data: formData,
						cache:false,
						processData: false,
						contentType: false,
						success: function (response) {
							var msg = response.split("/");

							if(msg[0] =='0') {
								alert(msg[1]);

								return false;
							}else{
								alert("삭제했습니다!!");
								location.href = "guide_mysettle.php?division=6&pdx=2&sub=15";
							}
						}
					});
			}

		});

        //가이드정산보고
        $(document).on("click",".js-report",function(e) { 

            if(confirm("가이드 정산제출을 하시겠습니까?") == true) {
                $("#mode").val("report");

                var form = $("#frnName").closest("form");
                var formData = new FormData(form[0]);

                $.ajax({
                    type: 'POST',
                    url: 'guide_save1.php',
                    data: formData,
                    cache:false,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        var msg = response.split("/");

                        if(msg[0] =='0') {
                            alert(msg[1]);

                            return false;
                        }else{
                            location.href = "guide_settle.php?division=6&pdx=2&sub=10";
                        }
                    }
                });
            }
        });
		//가이드정산보고취소
        $(document).on("click",".js-report2",function(e) { 

            if(confirm("가이드 정산보고를 취소하시겠습니까?") == true) {
                $("#mode").val("repcan");

                var form = $("#frnName").closest("form");
                var formData = new FormData(form[0]);

                $.ajax({
                    type: 'POST',
                    url: 'guide_save1.php',
                    data: formData,
                    cache:false,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        var msg = response.split("/");

                        if(msg[0] =='0') {
                            alert(msg[1]);

                            return false;
                        }else{
                           location.href = "guide_settle.php?division=6&pdx=2&sub=10";
                        }
                    }
                });
            }
        });
		//회계확인
        $(document).on("click",".js-save2",function(e) { 

            if(confirm("회계확인을 하시겠습니까?") == true) {
                $("#mode").val("finance");

                var form = $("#frnName").closest("form");
                var formData = new FormData(form[0]);

                $.ajax({
                    type: 'POST',
                    url: 'guide_save1.php',
                    data: formData,
                    cache:false,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        var msg = response.split("/");

                        if(msg[0] =='0') {
                            alert(msg[1]);

                            return false;
                        }else{
							alert("확인하셨습니다.!");
                           // location.href = "guide_settle.php?division=6&pdx=2&sub=10";
                        }
                    }
                });
            }
        });
		//대표확인
        $(document).on("click",".js-save3",function(e) { 

            if(confirm("대표이사확인을 하시겠습니까?") == true) {
                $("#mode").val("ceo");
                
                var form = $("#frnName").closest("form");
                var formData = new FormData(form[0]);

                $.ajax({
                    type: 'POST',
                    url: 'guide_save1.php',
                    data: formData,
                    cache:false,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        var msg = response.split("/");

                        if(msg[0] =='0') {
                            alert(msg[1]);

                            return false;
                        }else{
							alert("확인하셨습니다.!");
                            //location.href = "guide_settle.php?division=6&pdx=2&sub=10";                        
						}
                    }
                });
            }
        });
        /*$(document).on("click",".js-excel",function(e) { 
            $("#contentwrapper").table2excel({
                filename: "Students.xls"
            });
        });*/

        $('.js-print').click(function(){  
            /*var excel_data = $('#contentwrapper').html();  
            console.log(excel_data);
            var page = "export.php?data=" + excel_data;  
            window.location = page;  */

            window.open('guide_cal_m_print.php?number=<?=$_GET[number]?>');
        });   

	</script>
    </body>
</html>
