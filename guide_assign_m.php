<?php
    include "include/header.php";
   // include "include/inc_base.php";
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
	if ($mode == "save") {
		$eventcnt = count($bbnum);
		
		//echo $eventcnt;
		//exit;
		for($r=0;$r<$eventcnt;$r++)
		{
            $qry1 = "delete from tour_guide where grand_eCode= '$gcode' && sub_eCode = '$ssnum[$r]' && p_code = '$pcode' && stDate = '$sdate'";
	    	$rst1 = mysql_query($qry1,$dbConn);

			if ($guideName[$r] !="") {
				$qry2 ="insert into tour_guide 
												( 
												grand_eCode, 
												sub_eCode, 
												p_code, 
												p_name, 
												stDate, 
												bus_num, 
												guide_id, 
												sguide_id, 
												g_email, 
												g_tel, 
												pre_amt,
												c_id,
												c_tel,
												c_type,
												c_memo,
												d_nm,
												d_tel,
												d_memo,
												userid, 
												wdate
												)
												values
												(
												'$gcode', 
												'$ssnum[$r]', 
												'$pcode', 
												'$pname', 
												'$sdate', 
												'$bbnum[$r]', 
												'$guideName[$r]', 
												'$sguideName[$r]', 
												'$guideEmail[$r]', 
												'$guideTelephone[$r]', 
												'$guidePreCost[$r]', 
												'$cName[$r]', 
												'$carTelephone[$r]',
												'$cartype[$r]',
												'$carmemo[$r]',
												'$driver[$r]',
												'$dTelephone[$r]',
												'$dmemo[$r]',
												'{$user_dbinfo['userid']}', 
												now()
												)";
				//echo $qry2;
				//exit;
				$rst2 = mysql_query($qry2,$dbConn);
			}
		}
		
		
		Misc::jvAlert("업데이트 되었습니다!!","");
	}
	$sctour = getTourInfo2($pcode,$st);
	$pcnt = getReserveInfoCnt($pcode,$st);				
	if ($pcnt['cnt'] =="") {
		$pcnt['cnt'] = 0;
	}
    $pInfo = getProductMaster($pcode);

?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정</a></li>
					<li>가이드배정관리</li>
				</ul>
			</div>

			<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&st=<?=$st?>&pcode=<?=$pcode?>" name="frmcar" method="post" onSubmit="return chksave()">
				<input type="hidden" name="mode" id="mode" value="save">
				<input type="hidden" name="gcode" id="gcode" value="<?=$sctour['grand_eCode']?>">
				<input type="hidden" name="pcode" id="pcode" value="<?=$sctour['p_code']?>">
				<input type="hidden" name="pname" id="pname" value='<?=$sctour['p_name']?>'>
				<input type="hidden" name="sdate" id="sdate" value="<?=$sctour['stDate']?>">
				<table id="custom_table" class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
					<tbody>
						<tr>
                        <td colspan="2" class="active text-center formHeader">통합행사코드</td>
                        <td colspan="12"><?=$sctour['grand_eCode']?></td>
                    </tr>
					        			
                        <td colspan="2" class="active text-center formHeader">상품명</td>
                        <td colspan="12">[<?=$sctour['p_code']?>] <?=$sctour['p_name']?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">출발일</td>
                        <td colspan="2"><?=$sctour['stDate']?></td>
                        
                        <td colspan="2" class="active text-center formHeader">투어정원</td>
                        <td colspan="2"><?=$sctour['tour_pcnt']?> 명 </td>
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="2"><?=$pcnt['cnt']?> 명 </td>
                    </tr>
					
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="12">
                            <label class="radio-inline">
                                <input type="radio" name="bookNumber" value="P" <?php if(strstr($sctour['r_status'],"P")) echo "checked"; ?> disabled> 예약접수중
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="bookNumber" value="C" <?php if(strstr($sctour['r_status'],"C")) echo "checked"; ?> disabled> 예약마감
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">행사상태</td>
                        <td colspan="12">
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="input-group input-group-sm">
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="1" <?php if(strstr($sctour['ev_status'],"1")) echo "checked"; ?> disabled> 미확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="2" <?php if(strstr($sctour['ev_status'],"2")) echo "checked"; ?> disabled> 확정
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="3" <?php if(strstr($sctour['ev_status'],"3")) echo "checked"; ?> disabled> 만차
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" value="4" <?php if(strstr($sctour['ev_status'],"4")) echo "checked"; ?> disabled> 취소
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="eventStatus" <?php if(strstr($sctour['ev_status'],"5")) echo "checked"; ?> disabled> 기타
                                        </label>
                                    </div>
                                </div>    
                                <div class="col-sm-8">
                                    <div>   
                                        <input type="text" name="etcMemo" class="form-control" aria-label="기타메모"  placeholder="기타메모" value="<?=$sctour['etc_memo']?>" readOnly/>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                        <tr>
                            <td colspan="16" class="text-center"><button type='submit' class="btn-maroon btn-sm" name="car-assign" id="car-assign" >가이드배정</button>
                                <!--<button type='button' class="btn-orange btn-sm" name="hotel-assign" id="hotel-assign">호텔배정</button>-->
                            </td>
                        </tr>
                    </tbody>
				</table>
				<div class="row">
                    <!--<div class="col-sm-4">
                        <textarea class="form-control" rows="12" name="eventMemo" placeholder="행사메모" readONly><?=$sctour['ev_memo']?></textarea>
                    </div>-->    
                    <div class="col-sm-12">
                        
                        <?php
								$qry1 = "select 	
												grand_eCode, 
												sub_eCode, 
												reserveCode, 
												bus_num
												from tour_car 
												where stDate = '$st' && p_code = '$pcode'  group by bus_num order by bus_num asc";
								$rst1 = mysql_query($qry1,$dbConn);
								$k = 0;
								while($row1 = mysql_Fetch_assoc($rst1)){
								     $gss = getGuideInfo($sctour['grand_eCode'],$row1['sub_eCode'],$row1['bus_num']);
									 $g_dbinfo = getinfo_dbMember($gss['guide_id']);
									 if ($g_dbinfo['userfile1'] == "") {
										$gimg = "http://www.parantours.biz/admin/img/sample.jpg";
									 } else {
										$gimg = UPLOAD_URL.$g_dbinfo['userfile1'];
									 }
						?>
                        <fieldset class="guide-assign-border">
                            <legend class="guide-assign-border"><span class="pull-left small text-muted">차량<?=$row1['bus_num']?>  (행사코드: <?=$row1['sub_eCode']?>)</span></legend>
							<input type="hidden" name="bbnum[]" id="bbnum" value="<?=$row1['bus_num']?>">
							<input type="hidden" name="ssnum[]" id="ssnum" value="<?=$row1['sub_eCode']?>">
                            <table class="table table-borderless table-condensed gridSixteen reserveTable formDetail">
                                <tbody>
								<tr>
                                       <!-- <td rowspan="5" width="5%" class="text-center formHeader">
                                           <span id="gimgid"> <img src="<?=$gimg?>" width="150" height="150"></span>
                                            <!--<input type="file" class="form-control" id="guideImg" name="guideImg<?=$i?>" placeholder="이미지">
                                        </td>-->
                                        <td colspan=2 class="text-center">가이드</td>
                                        <td colspan=2 class="text-center">차량</td>
										<td colspan=2 class="text-center">기사</td>
                                    </tr>
                                    <tr>
                                       <!-- <td rowspan="5" width="5%" class="text-center formHeader">
                                           <span id="gimgid"> <img src="<?=$gimg?>" width="150" height="150"></span>
                                            <!--<input type="file" class="form-control" id="guideImg" name="guideImg<?=$i?>" placeholder="이미지">
                                        </td>-->
                                        <td width="3%" class="text-center">가이드 이름</td>
                                        <td width="10%">
											<select class="form-control guidecs" name="guideName[]">
												<option value="" selected>선택</option>
												 <?=printGuideSelect($gss['guide_id'])?>
											</select>
                                        </td>
										<td width="2%" class="text-center">차량</td>
                                        <td>
											<select class="form-control cName" name="cName[]">
												<option value="" selected>선택</option>
												 <?=printCarSelect($gss['c_id'])?>
											</select>
                                        </td>
										<td width="2%" class="text-center">기사명</td>
                                        <td>
											<input type="text" name="driver[]" id="driver" class="form-control driver" aria-label="기사명" value="<?=$gss['d_nm']?>"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-center">연락처</td>
                                        <td><input type="text" name="guideTelephone[]" id="guideTelephone" class="form-control tel" aria-label="전화번호" value="<?=$gss['g_tel']?>"/></td>
										<td width="2%" class="text-center">연락처</td>
                                        <td><input type="text" name="carTelephone[]" id="carTelephone" class="form-control ctel" aria-label="전화번호" value="<?=$gss['c_tel']?>"/></td>
										<td width="2%" class="text-center">연락처</td>
                                        <td><input type="text" name="dTelephone[]" id="dTelephone" class="form-control dtel" aria-label="전화번호" value="<?=$gss['d_tel']?>"/></td>
                                    </tr>
                                    <tr>
										<td width="2%" class="text-center">선지급 행사비</td>
                                        <td><input type="text" name="guidePreCost[]" class="form-control preamt" aria-label="선지급 행사비" value="<?=$gss['pre_amt']?>"/></td>
                                        <td width="2%" class="text-center">차량종류</td>
                                        <td><input type="text" name="cartype[]"  name="cartype" class="form-control ctype" aria-label="차량종류" value="<?=$gss['c_type']?>"/></td>
										<td width="2%" class="text-center">메모</td>
                                        <td><input type="text" name="dmemo[]"  name="dmemo" class="form-control dmeom" aria-label="메모" value="<?=$gss['d_memo']?>"/></td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-center">부가이드</td>
                                        <td width="10%">
											<select class="form-control sguidecs" name="sguideName[]">
												<option value="" selected>선택</option>
												 <?=printGuideSelect($gss['sguide_id'])?>
											</select>
                                        </td>
                                        <td width="2%" class="text-center">메모</td>
                                        <td><input type="text" name="carmemo[]"  name="carmemo" class="form-control cmemo" aria-label="메모" value="<?=$gss['c_memo']?>"/></td>
										<td width="2%" class="text-left"></td>
                                        <td></td>
                                    </tr>
                                   

                                </tbody>
                            </table> 
                        </fieldset>
                        <?php }?>
                        <!--
                        <fieldset class="guide-assign-border">
                            <legend class="guide-assign-border"><span class="pull-left small text-muted">차량2 가이드배정</span></legend>
                            <table class="table table-borderless table-condensed gridSixteen reserveTable formDetail">
                                <tbody>
                                    <tr>
                                        <td rowspan="5" width="5%" class="text-center formHeader">
                                            <img src="http://www.parantours.biz/admin/img/sample.jpg" width="150" height="="150"">
                                        </td>
                                        <td width="2%" class="text-left">가이드 이름</td>
                                        <td><input type="text" class="form-control" aria-label="가이드 이름" value=""/></td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-left">전화번호</td>
                                        <td><input type="text" class="form-control" aria-label="전화번호" value=""/></td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-left">이메일</td>
                                        <td><input type="text" class="form-control" aria-label="이메일" value=""/></td>
                                    </tr>
                                    <tr>
                                        <td width="2%" class="text-left">선지급 행사비</td>
                                        <td><input type="text" class="form-control" aria-label="선지급 행사비" value=""/></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="text-center"><button type="submit" class="btn btn-xs btn-default js-xxx">가이드배정</button></td>
                                    </tr>

                                </tbody>
                            </table> 
                        </fieldset>-->
                    </div>
                </div>
			</form>
		</div>
	</div>
	
    <?php
		include "include/side_m.php"
	?>
   
   <script>
		$(document).ready(function () {
			pt.initReservationList()
			$('.guidecs').bind("change",function() {
				var ruid = $(this).val();
			    var sel = $(this); 
				$.getJSON("get_guide.php?ruid="+ruid, function(jsonData){
					 sel.closest('table').find("#guideTelephone").val("");
					 sel.closest('table').find(".email").val("");
					 //sel.closest('table').find("#gimgid").html("<img src='http://www.parantours.biz/admin/img/sample.jpg' width='150' height='150'>");
					 
					 $.each(jsonData, function(i,data){
						  var tel = data.company_phone;
						  var email = data.company_email;
						  var gimg = "";
						  if (data.userfile1== "") {
								gimg = "http://www.parantours.biz/admin/img/sample.jpg";
						  } else {
								gimg = ".UPLOAD_URL."+data.userfile1+"";
						  }
						  sel.closest('table').find("#guideTelephone").val(tel);
						  sel.closest('table').find(".email").val(email);
						  sel.closest('table').find("#gimgid").html("<img src='"+gimg+"' width='150' height='150'>");
											
					 });
					  
				});
			});
			$('.cName').bind("change",function() {
				var ruid = $(this).val();
			    var sel = $(this); 
				$.getJSON("get_ccar.php?ruid="+ruid, function(jsonData){
					 sel.closest('table').find(".ctel").val("");
					
					 $.each(jsonData, function(i,data){
						  var tel = data.bus_tel;
						 // var cmemo = data.cmemo;
						 // var gimg = "";
						 
						  $.getJSON("get_codec.php?code="+data.bus_type, function(jsonData1){
								$.each(jsonData1, function(i,data1){
									var code = data1.comment;
								    sel.closest('table').find(".ctype").val(code);
								});
						  });
						  
						 
						  sel.closest('table').find(".ctel").val(tel);
						
											
					 });
					  
				});
			});
		})

		function chksave() {
			/*
                  if ($("#area1").val() == "") {
						alert("상품분류 1을 입력하세요!");
						$("#area1").focus();
						return false;
				  }
			*/	 
				if (confirm("배정할까요?") == true) {
				    return true;
			    } else {
					return false;
				}
			}
	</script>
   
    </body>
</html>
