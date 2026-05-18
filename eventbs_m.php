<?php
  include "include/header.php";
 // include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

    if (!hasMenuAccess(4, 1, 10)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }
	$prodInfo = getProductMaster($pcode);
	$pcnt = getReserveInfoCnt($pcode,$st);				
	if ($pcnt['cnt'] =="") {
		$pcnt['cnt'] = 0;
	}
    

	
	$qry1 = "select c.grand_eNum,c.grand_eCode,a.p_code,a.p_name,c.stDate,a.c_code1,a.c_code2,a.p_own,a.p_day,a.p_cnt ,c.r_status,c.ev_status, c.tour_pcnt,c.s_pcode 
	from product_master a 
	left outer join tour_master c on a.p_code=c.p_code && c.stDate = '$st' 
	where a.p_code='$pcode' group by a.p_code,c.stDate
	union
	select c.grand_eNum,c.grand_eCode,a.p_code,a.p_name,c.stDate,a.c_code1,a.c_code2,a.p_own,a.p_day,a.p_cnt ,c.r_status,c.ev_status, c.tour_pcnt ,s_pcode
	from product_master a 
	left outer join tour_master c on c.p_code=c.p_code && c.stDate = '$st'
	left outer join product_limit d on a.p_code=d.p_code && d.p_type = 'R'
	where a.p_code ='$pcode'  && d.p_limitdate = '$st' group by a.p_code,c.stDate
			";
	$rst1 = mysql_query($qry1,$dbConn);
	////echo $qry1;
	$sctour = mysql_fetch_assoc($rst1);
     if ($sctour['tour_pcnt'] != "") {
					
		$prodInfo['p_cnt'] = $sctour['tour_pcnt'];
	}

	//}
	
	if ($_POST['mode'] == "save") {
		if ($gcode == "") {
			$total_eventNum = getNumTevent();
			$total_eventCode = "GTP".date("ymd").$total_eventNum;	
			$qry2 = "insert into tour_master 
								(
								grand_eNum, 
								grand_eCode, 
								p_code, 
								p_name, 
								tour_pcnt, 
								stDate, 
								edDate, 
								r_status, 
								ev_status, 
								s_pcode, 
								etc_memo, 
								ev_memo, 
								chk_ass, 
								userid, 
								wdate
								)
								values
								( 
								'$total_eventNum', 
								'$total_eventCode', 
								'$pcode', 
								'$pname', 
								'$tourNumber', 
								'$startDate', 
								'', 
								'$bookStatus', 
								'$eventStatus', 
								'$productSub', 
								'$etcMemo', 
								'$eventMemo', 
								'$earr', 
								'{$user_dbinfo['userid']}', 
								now()
								)";
			 $rst2 = mysql_query($qry2,$dbConn);
			 

			 $qry3 ="update reserve_info 
								set
	  						   	tour_pcnt = '$tourNumber'
								where
								p_code = '{$sctour['p_code']}' && stDate= '$startDate'";
			 $rst3 = mysql_query($qry3,$dbConn);
			 //추가숙박
			 
			 
			 Misc::jvAlert("저장 완료!!!");
			 echo "<meta http-equiv='refresh' content='0; url=./eventbs_list.php?startDate1=$sdate&endDate1=$sdate&division=4&pdx=1&sub=10'>";
          
	     
		} else {
			 $qry2 = " update tour_master 
									set
									tour_pcnt = '$tourNumber',
									r_status = '$bookStatus' , 
									ev_status = '$eventStatus' , 
									etc_memo = '$etcMemo' , 
									ev_memo = '$eventMemo' 
								where
									grand_eCode = '$gcode' ";

			  $rst2 = mysql_query($qry2,$dbConn);
			  //echo $qry2."<br />";
			  //추가숙박
			 
			 
			  $qry3 ="update reserve_info 
								set
	  						   	tour_pcnt = '$tourNumber'
								where
								p_code = '{$sctour['p_code']}' && stDate= '$startDate'";
			  $rst3 = mysql_query($qry3,$dbConn);
			 
			
			  Misc::jvAlert("저장 완료!!!");
			  echo "<meta http-equiv='refresh' content='0; url=./eventbs_list.php?startDate1=$sdate&endDate1=$sdate&division=4&pdx=$pdx&sub=$sub'>";
		}

	} else if ($_POST['mode'] == "delete") {
			 $qry3 ="delete from tour_master where grand_eCode = '$gcode' ";
			
			 $rst3 = mysql_query($qry3,$dbConn);
			 Misc::jvAlert("삭제 완료!!!");
			  echo "<meta http-equiv='refresh' content='0; url=./eventbs_list.php?startDate1=$sdate&endDate1=$sdate&division=4&pdx=$pdx&sub=$sub'>";
	}
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사관리</a></li>
					<li>행사기본관리</li>
				</ul>
			</div>

			<form  name="frmevent" id="frmevent" method="post" >
				<input type="hidden" name="mode" id="mode" value="">
				<input type="hidden" name="gcode" id="gcode" value="<?=$sctour['grand_eCode']?>">
				<input type="hidden" name="pcode" id="pcode" value="<?=$sctour['p_code']?>">
				<input type="hidden" name="pname" id="pname" value='<?=$sctour['p_name']?>'>
				<input type="hidden" name="sdate" id="sdate" value="<?=$sctour['stDate']?>">
				<input type="hidden" name="rcnt" id="rcnt" value="<?=$pcnt['cnt']?>">
				<input type="hidden" name="earr" id="earr" value="">
				<div class="row no-nav">
					<div class="col-sm-12 text-center">
						<button type="button" class="btn btn-primary btn-sm js-esave" >행사저장</button>&nbsp;
						<button type="button" class="btn btn-primary btn-sm js-del" >행사초기화</button>
					</div>
				</div>
				<br />
				<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
							<td colspan="2" class="active text-center formHeader">통합행사코드</td>
							<td colspan="14"><?php if ($sctour['grand_eCode']) { echo $sctour['grand_eCode']; } else { ?> 미지정-자동배정 <?php } ?></td>
                        </tr>
						
                        <tr>                    			
							<td colspan="2" class="active text-center formHeader">상품명</td>
							<td colspan="14">[<?=$sctour['p_code']?>] <?=$sctour['p_name']?></td>
                        </tr>
                        <tr>
							<td colspan="2" class="active text-center formHeader">출발일</td>
							<td colspan="4">
                                <div class="row">
									<div class="col-sm-6">
										<div class="input-group input-group-sm">
											<input type="text" name="startDate" class="form-control js-dateInputWithBlocks js-tourDates" aria-label="출발일" placeholder="출발일" value='<?=$st?>' readOnly>
											<span class="input-group-btn">
												
											</span>
										</div>
									</div>
									
								</div>
                            </td>
                            <td colspan="2" class="active text-center formHeader">투어정원</td>
							<td colspan="2">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="tourNumber" class="form-control text-right" aria-label="명" value="<?=$prodInfo['p_cnt']?>"/>
                                    <span class="input-group-addon">명</span>
                                </div>
                            </td>
                            <td colspan="2" class="active text-center formHeader">예약인원</td>
							<td colspan="2"><?=$pcnt['cnt']?>명 </td>
				        </tr>
						
						<tr>
                            <td colspan="2" class="active text-center formHeader">예약상태</td>
                            <td colspan="14">
								<label class="radio-inline">
                                    <input type="radio" name="bookStatus" value="P" <?php if(strstr($sctour['r_status'],"P")) echo "checked"; ?>> 예약접수중
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="bookStatus" value="C" <?php if(strstr($sctour['r_status'],"C")) echo "checked"; ?>> 예약마감
                                </label>
							</td>
                        </tr>
						
                        <tr>
                            <td colspan="2" class="active text-center formHeader">행사상태</td>
                            <td colspan="14">
                                <div class="row">
									<div class="col-sm-4">
										<div class="input-group input-group-sm">
                                            <label class="radio-inline">
                                                <input type="radio" name="eventStatus" value="1" <?php if(strstr($sctour['ev_status'],"1")) echo "checked"; ?>> 미확정
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="eventStatus" value="2" <?php if(strstr($sctour['ev_status'],"2")) echo "checked"; ?>> 확정
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="eventStatus" value="3" <?php if(strstr($sctour['ev_status'],"3")) echo "checked"; ?>> 만차
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="eventStatus" value="4" <?php if(strstr($sctour['ev_status'],"4")) echo "checked"; ?>> 취소
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="eventStatus" value="5" <?php if(strstr($sctour['ev_status'],"5")) echo "checked"; ?>> 기타
                                            </label>
                                        </div>
                                    </div>    
                                    <div class="col-sm-8">
                                        <div>   
											<input type="text" name="etcMemo" class="form-control" aria-label="기타메모"  placeholder="기타메모" value="<?=$sctour['etc_memo']?>"/>
										</div>
                                    </div>
								</div>
                            </td>
                        </tr>
						<tr>
							<td colspan="16">
								<textarea class="form-control" rows="7" name="eventMemo" placeholder="행사메모"><?=$sctour['ev_memo']?></textarea>
							</td>
				        </tr>
                      <!-- <tr>
                            <td colspan="16" class="text-center" ><span id="activesp"><button type='button' class="btn btn-primary btn-sm btnass" value="<?=$sctour['chk_act']?>"><?php if ($sctour['chk_act']==1) { ?>배정상태비활성<?php }else{ ?>배정상태활성<?php }?></button></span></td>
                        </tr>-->
                    </tbody>
				</table>
				
			</form>
		</div>
	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
			pt.initReservationDetail()

			{
				
			}
			$( ".btnass" ).click(function() {
				var act = $(".btnass").val();
				if (act == 0)
				{
					act = 1;
				} else {
					act = 0;
				}
				var gCode = $("#gcode").val();
				$.getJSON("update_active.php?gCode="+gCode+"&act="+act, function(jsonData){
					 $.each(jsonData, function(i,data){
						 $("#activesp").html('1');
						/*if (act ==1)
						{
							$("#activesp").html("<button type='button' class='btn btn-primary btn-sm btnass' value='1'>배정상태비활성</button>");
						} else {
							$("#activesp").html("<button type='button' class='btn btn-primary btn-sm btnass' value='0'>배정상태활성</button>");
						}
						*/
					 });
					  
				});
				
		    
					
			});
			
			//삭제클릭
			$(document).on("click",".js-del",function(e) { 
				if(confirm("행사를 초기화 하시겠습니까?") == true) {
				$("#mode").val("delete");
				
				$("#frmevent").submit();
				}
				
			});

			$(document).on("click",".js-esave",function(e) { 
				if(confirm("행사를 저장하시겠습니까?") == true) {
					$("#mode").val("save");
					
					$("#frmevent").submit();
				}
				
			});

			
		});
		function chksave() {
			
		  if(confirm("행사를 저장하시겠습니까?") == true)
		  {
			return true;
		  }else {
			return;
		  }

		  
		}
	</script>
    </body>
</html>
