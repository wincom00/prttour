
<?php
    include "include/header.php";
	
    //include "include/inc_base.php";

	header("Cache-Control:no-cache,must-revalidate");
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
	if($mode == "save")
	{

		
		  if($consultCode !='')
	      {
			 
            
			$tmp1 = substr($consultCode,18,3);
			$tmp2 = substr($consultCode,0,17);
			$cunsultpreNum = getRConsultNum($tmp2);
			$finalseq = $cunsultpreNum['cmax']+1;
			if(strlen($finalseq) == "1")
			{
				$num1 = "00".$finalseq;
			}
			elseif(strlen($finalseq) == "2")
			{
				$num1 = "0".$finalseq;
			}
			else
			{
				$num1 = $finalseq;
			}
			$consultCode = $tmp2."-".$num1;
			
		  } else {
            

			$cunsultNum = getNumConsult();
			// 최종 예약번호
			$consultCode = "C".date("ymd").time()."-".$cunsultNum;


		  }

			$qry1 = "insert into consult_info 
											(
											consultNum, 
											consultCode, 
											register, 
											wdate, 
											t_memeber, 
											member_name, 
											member_phone, 
											member_email, 
											p_code, 
											p_name, 
											start_date, 
											stop_date, 
											p_cnt, 
											room_cnt, 
											estimate_content
											)
											values
											( 
											'$cunsultNum', 
											'$consultCode', 
											'{$user_dbinfo['userid']}', 
											 now(), 
											'$t_mem', 
											'$r_name', 
											'$r_phone', 
											'$r_email', 
											'$pcode', 
											'$pname', 
											'$startDate', 
											'$endDate', 
											'$pcnt1', 
											'$rcnt1', 
											'$estimate_content'
											)";
			$rst1 = mysql_query($qry1);

		//}

		if($rst1)
		{
			$linkR="base_conslut_mylist.php?consultCode=".$consultCode."&division=3&pdx=1&sub=20";
			Misc::jvAlert("저장되었습니다.","location.replace('$linkR')");
			exit;
		}
		else
		{
			echo "error!";
			exit;
		}
	}
	else if($mode == "delete")
	{
		$qry1 = "delete from consult_info where seq_no = '$no'";
		$rst1 = mysql_query($qry1);

		if($rst1)
		{
			$linkR='base_conslut_list.php?division=3&pdx=1&sub=15';
			Misc::jvAlert("삭제되었습니다.","location.replace('$linkR')");
			exit;
		}
		else
		{
			echo "error!";
			exit;
		}
	}

    // 예약접수된게 있다면...	
	if(($consultCode))
	{
			 $reserve_info = getReserveInfo($consultCode);
			 $consult_info = getConsultInfo($consultCode);
		
			 $consultCode = $consult_info['consultCode'];
			 
			// 상품코드로 상품정보 가져오기
			// $p_code
			 
			 $pcode = $consult_info['p_code'];
			 $prodInfo = getProductMaster($pcode);
			 
			 $reservePcode = !empty($reserve_info['p_code']) ? $reserve_info['p_code'] : $pcode;
			 $reserveStDate = !empty($reserve_info['stDate']) ? $reserve_info['stDate'] : $consult_info['start_date'];
			 $st = $reserveStDate;

			 $pcnt = getReserveInfoCnt($reservePcode,$reserveStDate);
			 $pTinfo = getTourInfo2($pcode,$reserveStDate);
			 
			 if ($prodInfo['p_type'] == 1) {
			   $pcap = "로컬상품";
			 } else if ($prodInfo['p_type'] == 2) {
				$pcap = "인바운드";
			 } else if ($prodInfo['p_type'] == 4) {
				$pcap = "인센티브";
			 } else if ($prodInfo['p_type'] == 5) {
				$pcap = "아웃바운드";
			 }
			 
			 if ($pcnt['cnt'] =="") {
				$pcnt['cnt'] = 0;
			 }
			 if ($pTinfo['tour_pcnt'] =="") {
				 $pTinfo['tour_pcnt'] = 0;
			 }
			 if ($prodInfo['p_type'] == 1) {
			    
				if ($pTinfo['tour_pcnt'] =="") {
				   $bcnt = $prodInfo['p_cnt'];
				} else {
				   $bcnt =$pTinfo['tour_pcnt'];
				   $prodInfo['p_cnt'] = $pTinfo['tour_pcnt'];
				   
				}
			 } else {
				$bcnt = $prodInfo['p_cnt'];
			 }

			 $acnt = $prodInfo['p_cnt'] - $pcnt['cnt'];
			 $sign = "$";
				  $weektot = array("0", "1", "2","3","4","5","6","9"); 
				 if ((strpos($prodInfo['p_week'],"9")=== false)) {
					  $startWeek_Array = explode("/",$prodInfo['p_week']);
					  $RemoveWeek = array_diff($weektot,$startWeek_Array);
					  $RemoveWeek2 = array_values($RemoveWeek);
					  $startWeek_cnt = count($startWeek_Array);
					  $RemoveWeek_cnt = count($RemoveWeek2);
					 
					  for($s=0; $s<$startWeek_cnt-1; $s++)
					  {
							if($s == $startWeek_cnt-2)
							{
								$startWeekPrint .= $startWeek_Array[$s];
							}
							else
							{
								$startWeekPrint .= $startWeek_Array[$s].",";
							}
						
					  }
				 }

				 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='L'";
				 $rst1 = mysql_query($qry1);
				 $rowcnt = mysql_num_rows($rst1);
					//echo $qry1 ;
				 $LimitdatePrint = "";
				 $s = 0;
				 while($row1 = mysql_fetch_assoc($rst1)){

					if($s == $rowcnt-1)
					{
						$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
					else
					{
						$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
						
					$s++;
							
				  }


				 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='R'";
				 $rst1 = mysql_query($qry1);
				 $rowcnt = mysql_num_rows($rst1);
					//echo $qry1 ;
				 $SetdatePrint = "";
				 $s = 0;
				 while($row1 = mysql_fetch_assoc($rst1)){

					if($s == $rowcnt-1)
					{
						$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
					else
					{
						$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
						
					$s++;
							
				  }
			
			 
	} else if(($ConsultCode=="")) { 
					
				 $prodInfo = getProductMaster($pcode);
				 if ($prodInfo['p_type'] == 1) {
				   $pcap = "로컬상품";
				 } else if ($prodInfo['p_type'] == 2) {
					$pcap = "인바운드";
				 } else if ($prodInfo['p_type'] == 4) {
					$pcap = "인센티브";
				 } else if ($prodInfo['p_type'] == 5) {
					$pcap = "아웃바운드";
				 }
				 
				 ///////////////////////////////////////

				 //$st = $startDate;
				 
				 $stop_date = $endDate;
				 $gcnt=getReserveInfoGCnt($pcode,$st);
				 if ($gcnt['tour_pcnt'] !="") {
					$prodInfo['p_cnt'] = $gcnt['tour_pcnt'];
				 } 
				 $pcnt = getReserveInfoCnt($pcode,$st);
				 $pTinfo = getTourInfo2($pcode,$st);
				 if ($pcnt['cnt'] =="") {
						$pcnt['cnt'] = 0;
				 }
				 if ($prodInfo['p_type'] == 1) {
			    
					 if ($pTinfo['tour_pcnt'] =="") {
					   $bcnt = $prodInfo['p_cnt'];

					 } else {
					   $bcnt =$pTinfo['tour_pcnt'];
					   $prodInfo['p_cnt'] = $pTinfo['tour_pcnt'];
					 }
				 } else {
					$bcnt = $prodInfo['p_cnt'];
				 }
				 $acnt = $prodInfo['p_cnt'] - $pcnt['cnt'];
				 
				 
				 ////////////////////////////////////
				 if (($prodInfo['p_vstart'] == "0000-00-00") || ($st == "")) {

					 $startfrom = "";
				 } else {
					 $startfrom = $prodInfo['p_vstart'];

				 }

				 if ($prodInfo['p_vend'] == "0000-00-00") {

					 $endto = "";
				 } else {
					 $endto = $prodInfo['p_vend'];

				 }

				 


				 
				  $pday = $prodInfo['p_day'] ;
				  $sign = "$";
				  $weektot = array("0", "1", "2","3","4","5","6","9"); 
				 if ((strpos($prodInfo['p_week'],"9")=== false)) {
					  $startWeek_Array = explode("/",$prodInfo['p_week']);
					  $RemoveWeek = array_diff($weektot,$startWeek_Array);
					  $RemoveWeek2 = array_values($RemoveWeek);
					  $startWeek_cnt = count($startWeek_Array);
					  $RemoveWeek_cnt = count($RemoveWeek2);
					 
					  for($s=0; $s<$startWeek_cnt-1; $s++)
					  {
							if($s == $startWeek_cnt-2)
							{
								$startWeekPrint .= $startWeek_Array[$s];
							}
							else
							{
								$startWeekPrint .= $startWeek_Array[$s].",";
							}
						
					  }
				 }

				 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='L'";
				 $rst1 = mysql_query($qry1);
				 $rowcnt = mysql_num_rows($rst1);
					//echo $qry1 ;
				 $LimitdatePrint = "";
				 $s = 0;
				 while($row1 = mysql_fetch_assoc($rst1)){

					if($s == $rowcnt-1)
					{
						$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
					else
					{
						$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
						
					$s++;
							
				  }


				 $qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='R'";
				 $rst1 = mysql_query($qry1);
				 $rowcnt = mysql_num_rows($rst1);
					//echo $qry1 ;
				 $SetdatePrint = "";
				 $s = 0;
				 while($row1 = mysql_fetch_assoc($rst1)){

					if($s == $rowcnt-1)
					{
						$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
					else
					{
						$SetdatePrint .= "\"".$row1['p_limitdate']."\",";
					}
						
					$s++;
							
				  }
				

				
				 

	}

    function contentPrint(){

		global $dbConn, $division, $pdx, $sub, $user_dbinfo,$consult_info,$consultCode;

		$que = "select *
				from
					consult_info
				where (p_code='{$consult_info['p_code']}' && member_name='{$consult_info['member_name']}' && member_phone='{$consult_info['member_phone']}' &&  member_email='{$consult_info['member_email']}') order by wdate desc";


	//print_r($consult_info);

	
		$result=mysql_query($que);
		while($row = mysql_Fetch_assoc($result)){
		if ($row['t_memeber'] !="") {
			$uinfo1=getinfo_dbMember($row['t_memeber']);
		} else {
			$uinfo1['kor_name'] = "";

		}
		$linkR="base_conslut_m.php?consultCode=".$row['consultCode']."&division=3&pdx=1&sub=15&pcode=".$row['p_code']."&no=".$row['seq_no']."";
		$table_content .="
			<tr>
				<td align=center height=28><a href=$linkR><b><u>{$row['consultCode']}</u></b></a></td>
				<td align=left>&nbsp;{$row['p_name']}</td>
				<td align=center>{$row['member_name']} </td>
				<td align=center>{$row['member_phone']}</td>
				<td align=center>{$row['start_date']}</td>
				<td align=center>{$row['register']}</td>
				<td align=center>{$uinfo1['kor_name']}</td>
			</tr>
			";
		}
		echo $table_content;


    }//contentPrint function end

	
	 
?>


	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">예약상담관리</a></li>
					<li>예약상담관리</li>
					<li>예약상담등록</li>
				</ul>
			</div>
	
			<form action="<?= $PHP_SELF ?>?ConsultCode=<?=$ConsultCode?>&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&pcode=<?=$pcode?>" name="frmreserve" id="frmreserve" method="post" Enctype="multipart/form-data" autocomplete="false" >
				<input type="hidden" name="mode" id="mode" value="save">
				
				<input type="hidden" name="consultCode" value="<?=$consultCode?>">
				<input type="hidden" name="pcode" id="pcode" value="<?=$pcode ?>">
				<input type="hidden" name="pname" value='<?=$prodInfo['p_name']?>'>
				<input type="hidden" name="cday" id="cday" value="<?=$pday?>">
				<input type="hidden" name="tcnt" id="tcnt" value="<?=$acnt?>">
				<input type="hidden" name="pricet" id="pricet" value="<?=$pricet?>">
				<input type="hidden" name="no" id="no" value="<?=$no?>">
				<div class="row no-nav">
					<div class="col-sm-6">
						&nbsp;
					</div>
				</div>
				<div class="row no-nav">
					<div class="col-sm-6">
					  
						
					</div>
					<div class="col-sm-6 text-right">
					  
					        <button type="button" class="btn btn-xs btn-default js-rr" onClick="go_submit()">상담등록</button>
							
							
							<button type="button" class="btn btn-xs btn-default js-can" onClick="go_cancel()">상담삭제</button>
							<a  type="button" href="base_reservation_m.php?consultCode=<?=$consultCode?>&division=3&pdx=2&sub=10&ty=1&pcode=<?=$pcode ?>&st=<?=$consult_info['start_date']?>&pricet=1"class="btn btn-xs btn-default js-esti" >견적등록</a>
							
					  
					</div>
				</div>
				<br />
				<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail js-base">
					<tbody>
						<tr>
							<td colspan="16" class="active text-center formHeader fullWidth">상담예약기본정보</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">투어분류</td>
							<td colspan="6"><?=$pcap?></td>
							
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">상품명</td>
							<td colspan="6"><?=$prodInfo['p_name']?></td>
							<td colspan="2" class="active text-center formHeader">상품코드</td>
							<td colspan="6"><?=$prodInfo['p_code']?></td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">상담예약번호</td>
							<td colspan="6"><?php if ($consultCode) { echo $consultCode; } else { ?>저장후에 생성<?php } ?></td>
							<td colspan="2" class="active text-center formHeader">여행기간</td>
							<td colspan="6">
								<div class="row">
									<div class="col-sm-6">
										<div class="input-group input-group-sm">
											<input type="text" name="startDate" id="startDate" class="form-control js-dateInputWithBlocks js-tourDates js-tourStartDate" aria-label="여행시작날짜" placeholder="여행시작날짜" autocomplete="off" value='<?=$consult_info['start_date']?>'>
											<span class="input-group-btn">
												<button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
											</span>
										</div>
									</div>
									<div class="col-sm-6">
										<div class="input-group input-group-sm">
											<input type="text" name="endDate" class="form-control js-dateInputWithBlocks js-tourDates js-tourEndDate" aria-label="여행종료날짜" placeholder="여행종료날짜" autocomplete="off" value='<?=$consult_info['stop_date']?>'>
											<span class="input-group-btn">
												<button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
											</span>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">전달직원</td>
							<td colspan="6"><select name=t_mem class="form-control" >
							<option value=''> - 전달직원 선택 -</option>
							<?= employeelist($consult_info['t_memeber']); ?></select></td>
							<td colspan="2" class="active text-center formHeader">방갯수</td>
							<td colspan="2"><div class="row">
									<div class="col-sm-12">
									
									    <div class="input-group input-group-sm">
											<input type="text"  name='rcnt1' id='rcnt1' class="form-control text-right" aria-label="개" value="<?php if ($consult_info['room_cnt']) {  echo $consult_info['room_cnt']; } else { ?>0<?php } ?>"	/>
										
											<span class="input-group-addon">개</span>
										</div>
									
									</div>
								</div></td>
							<td colspan="2" class="active text-center formHeader">예약인원</td>
							<td colspan="2"><div class="text-right cntid"><?=$pcnt['cnt']?>명</div></td>
						</tr>
						<tr>
							<td colspan="2" class="active text-center formHeader">접수일</td>
							<td colspan="6"><span><?php if (($consult_info['wdate'])) { echo $consult_info['wdate']; } else { echo date("Y-m-d"); } ?></span></td>
							<td colspan="2" class="active text-center formHeader">여행인원</td>
							<td colspan="2">
								<div class="row">
									<div class="col-sm-12">
										<div class="input-group input-group-sm">
											<input type="number" min="1" name='pcnt1' id='pcnt1' class="form-control text-right js-numTourists" aria-label="명" value="<?php if ($consult_info['p_cnt']) {  echo $consult_info['p_cnt']; } else { ?>1<?php } ?>" />
											
											<span class="input-group-addon">명</span>
										</div>
									</div>
								</div>
							</td>
							<td colspan="2" class="active text-center formHeader"></td>
							<td colspan="2"><div class="text-right acntid"></div></td>
						</tr>
					</tbody>
				</table>
				<table id="example" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
					
						<tr>
							<td colspan="4" class="active text-center formHeader fullWidth">상담정보</td>
						</tr>
						<tr class="innerTable1">
							<td  class="active text-center formHeader">예약자정보</td>
							<td width='20%'>
								<!-- <input type="text" class="form-control" placeholder="이름"> -->
								<div class="row">
									<div class="col-sm-9">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">이름</span>
											<input type="text" class="form-control" aria-label="이름" name="r_name" id="r_name" value="<?=$consult_info['member_name']?>"/>
										</div>
									</div>
								</div>
							</td>
							<td width='20%'>
								<!-- <input type="text" class="form-control" placeholder="전화번호"> -->
								<div class="row">
									<div class="col-sm-9">
										<div class="input-group input-group-sm">
											<span class="input-group-addon"><span class="glyphicon glyphicon-phone-alt" aria-hidden="true"></span></span>
											<input type="text" class="form-control" aria-label="전화번호" name="r_phone" id="r_phone" value="<?=$consult_info['member_phone']?>"/>
										</div>
									</div>
								</div>
							</td>
							<td >
								<!-- <input type="text" class="form-control" placeholder="이메일"> -->
								<div class="row">
									<div class="col-sm-5">
										<div class="input-group input-group-sm">
											<span class="input-group-addon">이메일</span>
											<input type="text" class="form-control" aria-label="이메일" name="r_email" id="r_email" value="<?=$consult_info['member_email']?>"/>
										</div>
									</div>
								</div>
							</td>
					    </tr>
						<tr>
							<td  class="active text-center formHeader">상담내용</td>
							<td colspan="8"><textarea name=estimate_content rows=15 class="form-control" ><?= $consult_info['estimate_content'] ?></textarea></td>
						</tr>
					</tbody>
				</table>
				
			
				
			</form>

			<table id="tabcon" class="table table-bordered table-condensed tabcon"  cellspacing="1" cellpadding="0">
			  <tbody>
			    <tr>
					<td colspan="7" class="active text-center formHeader fullWidth">상담이력</td>
				</tr>
				<tr height=28>
					<td width=15% align=center>상담코드</td>
					<td width=25% align=center>상품명</td>
					<td width=15% align=center>예약자</td>
					<td width=15% align=center>연락처</td>
					<td width=10% align=center>출발일</td>
					<td width=10% align=center>담당</td>
					<td width=10% align=center>전달직원</td>
				</tr>
			 </tbody>
			  <?= contentPrint(); ?>
			 </table>



			

		</div>
	</div>
    <?php
		include "include/side_m.php"
		
	?>
	<script src="ckeditor/ckeditor.js"></script>
    <script>
		$(document).ready(function () {
		
			$.ajaxSetup({async: false});
			pt.initReservationDetail()
			{
				
				var scope = $('.reservationDetailForm')
				for (var i = 0; i < scope.length; i++) {
					var self = $(scope['i'])
					var tourStartDate = self.find('.js-tourStartDate')
					var tourEndDate = self.find('.js-tourEndDate')
					var singleDateTourDate1 = self.find('.js-singleDayTourDate1')
					var dateStday = new Date('<?=$startfrom?>')
					var dateToday = new Date()

					
					//console.log(exarr);
					//alert(endmonth);
					if ((('<?=$startfrom?>'=="") || (dateStday < dateToday)) && ('<?=$reserve_info[edDate]?>' == ""))
					{
						    var date = new Date();
							date.setDate(date.getDate());
						    
						    tourStartDate.datepicker($.extend({}, pt.defaults.datepicker, {
							daysOfWeekEnabled: [
								<?php echo $startWeekPrint; ?>
							],
							datesDisabled: [
								<?php echo $LimitdatePrint; ?>
							],
							datesEnabled: [    // to override datesDisabled
								<?php echo $SetdatePrint; ?>
							],
							st: '<?=$startfrom?>',
							et: '<?=$endto?>',
							//"setDate": date,
							//startDate: date,
							//startDate: '<?=$startfrom?>',
							//endDate: '<?=$endto?>',
							todayHighlight: true,
							
							beforeShowDay: function (date) {
								var enabled = pt.beforeShowDayFunc(date, this)
								return enabled
							},
						})).off('changeDate').on('changeDate', { self: self }, pt.changeTourStartDate)
						tourEndDate.prop({ "readOnly": true })
						.closest('.input-group').find('button').prop({ disabled: true })
								
				} else {

							
							tourStartDate.datepicker($.extend({}, pt.defaults.datepicker, {
							daysOfWeekEnabled: [
								<?php echo $startWeekPrint; ?>
							],
							datesDisabled: [
								<?php echo $LimitdatePrint; ?>
							],
							datesEnabled: [    // to override datesDisabled
								<?php echo $SetdatePrint; ?>
							],
							//"setDate": '<?=$startfrom?>',
							st: '<?=$startfrom?>',
							et: '<?=$endto?>',
							todayHighlight: true,
							
							//startDate: '<?=$startfrom?>',
							//endDate: '<?=$endto?>',
							
							beforeShowDay: function (date) {
								var enabled = pt.beforeShowDayFunc(date, this)
								return enabled
							},
						})).off('changeDate').on('changeDate', { self: self }, pt.changeTourStartDate)
						tourEndDate.prop({ "readOnly": true })
						.closest('.input-group').find('button').prop({ disabled: true })
					}
					

					singleDateTourDate1.datepicker($.extend({}, pt.defaults.datepicker, {
						daysOfWeekEnabled: [
							
						],
						datesDisabled: [
							
						],
						datesEnabled: [    // to override datesDisabled
							
						],
						
						beforeShowDay: function (date) {
							var enabled = pt.beforeShowDayFunc(date, this)
							return enabled
						},
					}))

				    
					
					
					


				}
			}
			pt.initDataTable();			
			
	});
		
		function go_submit() {
                  
				  if ($("#startDate").val() == "") {
						alert("여행기간을 입력하세요!");
						$("#startDate").focus();
						return;
				  }
				  if ($("#r_name").val() == "") {
						alert("예약자이름을 입력하세요!");
						$("#r_name").focus();
						return;
				  }
				  if ($("#r_phone").val() == "") {
						alert("예약자 전화번호를 입력하세요!");
						$("#r_phone").focus();
						return;
				  }
				  
				  if ($("#r_email").val() == "") {
						alert("예약자 이메일을 입력하세요!");
						$("#r_email").focus();
						return ;
				  }
				  
				 
				  if(confirm("예약을 저장하시겠습니까?") == true)
				  {
					   
					   $("#frmreserve").submit();
				  }
				  else return;
	
		}
		
		
	
		function go_cancel() {
               
				  
				  if(confirm("상담을 삭제하시겠습니까?") == true)
				  {
					   $("#mode").val("delete");
					   $("#frmreserve").submit();
				  }
				  else return;
	
		}
		
		///////////////////////////
		
	</script>
    </body>
</html>
