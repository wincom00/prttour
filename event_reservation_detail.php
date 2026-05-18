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
	/*
	select c.grand_eCode,b.traveler_nm,a.grand_revNo,a.reserveCode ,a.p_code,a.p_name,a.stDate,a.p_cnt
 from reserve_info a, reserve_traveler b, tour_master c  where a.reserveCode = b.reserveCode 
&& c.grand_eCode ='TVE190617001'  && a.p_code=c.p_code && a.stDate = '2019-06-27' && b.seqint ='0'  
*/
	$sctour = getTourInfo2($pcode,$st);
	$pcnt = getReserveInfoCnt($pcode,$st);	
	
	
	if ($pcnt['cnt'] =="") {
		$pcnt['cnt'] = 0;
	}
	
	$kindE = $kindEvent;
?>

	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사관리</a></li>
					<li>행사예약현황</li>
					<li>행사예약현황-상세보기</li>
				</ul>
			</div>
			
			<div class="row">
				<div class="col-sm-12 col-md-12">
						<form id="frmName" name="frmName" method="post">
				        	<input type="hidden" name="kind" id="kind" value="<?=$kindEvent?>">
							<div class="row no-nav">
								<div class="col-sm-12">
									<label class="radio-inline">
										<input type="radio" name="kindEvent" value="1" <?php if(strstr($kindE,"1") || ($kindE=="")) echo "checked"; ?>>예약자
									</label>
									
									
									<label class="radio-inline">
										<input type="radio" name="kindEvent" value="3" <?php if(strstr($kindE,"3")) echo "checked"; ?>> 예약취소
									</label>
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<button type='submit' class="btn btn-primary btn-sm btn1">검색</button>
								</div>
							</div>
						 </form>
				</div>
			</div>
			<br />
			<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                <tbody>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">통합행사코드</td>
                        <td colspan="13"><?=$sctour['grand_eCode']?></td>
                    </tr>
                    <tr>                    			
                        <td colspan="2" class="active text-center formHeader">상품명</td>
                        <td colspan="13">[<?=$sctour['p_code']?>] <?=$sctour['p_name']?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">출발일</td>
                        <td colspan="2"><?=$sctour['stDate']?></td>
                        
                        <td colspan="2" class="active text-center formHeader">투어정원</td>
                        <td colspan="2"><?=$sctour['tour_pcnt']?> 명 </td>
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="4"><?=$pcnt['cnt']?> 명 </td>
						
                    </tr>
                    <tr>
                        <td colspan="2" class="active text-center formHeader">예약인원</td>
                        <td colspan="13">
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
                        <td colspan="13">
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
                   <!-- <tr>   
                       <td colspan="16" class="text-center">
                            <div class="row no-nav">
                                <div class="col-sm-12 text-center">
                                    <button type="button" class="btn btn-xs btn-default js-xxx">호텔객실배정현황</button>
                                    <button type="button" class="btn btn-xs btn-default js-xxx">차량배정현황</button>
                                    <button type="button" class="btn btn-xs btn-default js-xxx">호텔배정현황</button>
                                    <button type="button" class="btn btn-xs btn-default js-xxx">가이드배정현황</button>
                                </div>
                            </div>
                        </td>
                    </tr>-->
                    <tr>
                        <td colspan="16">
                            <textarea class="form-control" rows="7" name="eventMemo" placeholder="행사메모"><?=$sctour['ev_memo']?></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            <br />
            <div class="row">
                <div class="col-sm-12">
                    
                    <div class="panel panel-default">
                        <div class="panel-heading text-center"><strong>예약인원</strong></div>
                    </div>
                   
                    <!--<div class="row no-nav">
                        <div class="col-sm-12 text-right">
                            <button type="submit" class="btn btn-xs btn-default js-xxx">엑셀보내기</button>
                            <button type="submit" class="btn btn-xs btn-default js-xxx">프린트</button>
                        </div>
                    </div>-->
                    <br/>
					<table class="table table-striped table-bordered table-hover table-condensed js-revTable">
						<thead>
						   
							<tr>
							    <th width="6%">예약경로</th>
								<th width="8%">투어분류</th>
								<th width="8%">예약번호</th>
								<th width="7%">여행자</th>
								<th width="5%">인원</th>
								<th width="8%">최종결제금액</th>
								<th width="7%">잔액</th>
								<th width="8%">출발일</th>
								<th width="8%">접수일</th>
								<th width="6%">접수상태</th>
								<th width="6%">결제상태</th>
								<th width="*">진행사항</th>
								<th width="6%">최종수정</th>
							</tr>
						</thead>
						<tbody>
							
						</tbody>
					</table>
					<!--
                    <table id="custom_table" class="table table-striped table-bordered table-hover table-condensed">
                        <thead>
                            <tr>
                                <th>예약번호</th>
                                <th>투어종류</th>
                                <th>예약경로</th>
                                <th>접수일</th>
                                <th>고객명</th>
                                <th>연락처</th>
                                <th>인원</th>
                                <th>총금액</th>
                                <th>납입액</th>
                                <th>미납액</th>
                                <th>진행사항+행사메모</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>TS1806093</td>
                                <td>S1806093</td>
                                <td>캐나다동부</td>
                                <td>나이아가라1박2일</td>
                                <td>2018-09-09</td>
                                <td>2018-09-11</td> 
                                <td>아주관광</td>
                                <td>55/34/0</td>
                                <td>예약마감</td>
                                <td>만차</td>
                                <td><div class="tooltips">&nbsp;<span class="tooltiptext">마우스오버시 내용보이게/마우스오버시 
                                내용보이게/마우스오버시 내용보이게/마우스오버시 내용보이게/마우스오버시=====
                                내용보이게/마우스오버시 내용보이게/마우스오버시 내용보이게/마우스오버시 내용보이게</span></div></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>TS18060932</td>
                                <td>S1806091S1806092</td>
                                <td>캐나다동부</td>
                                <td>퀘벡2박3일(외곽숙박)</td>
                                <td>2018-09-09</td>
                                <td>2018-09-11</td>
                                <td>자사</td>
                                <td>55/34/0</td>
                                <td>예약접수중</td>
                                <td>확정</td>
                                <td><div class="tooltips">&nbsp;<span class="tooltiptext">마우스오버시 내용보이게</span></div></td>
                            </tr>
                        </tbody>
                    </table>-->
                </div>
            </div>
		</div>
	</div>
   
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
			pt.initReservationList()
            
            $('#custom_table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '엑셀보내기',
                        className: 'btn btn-xs btn-default'
                    },
                    {
                        extend: 'print',
                        text: '프린트',
                        className: 'btn btn-xs btn-default'
                    }
                ],
				pageLength: 100
            });
			var selected  = $("#kind").val();
			$('.js-revTable').DataTable({
					 "bProcessing": true,
					 "bServerSide": true,
					 "pageLength": 100, 
					  bFilter: false,
					 dom: 'Bfrtip',
					 buttons: [
						{
							extend: 'excel',
							text: '엑셀보내기',
							className: 'btn btn-xs btn-default'
						},
						{
							extend: 'print',
							text: '프린트',
							className: 'btn btn-xs btn-default'
						}
					 ],
					 "order": [[ 2, "desc" ]],
					"sServerMethod": "POST",
					"sAjaxSource": "alleveprocess.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&startDate1=<?=$st?>&pcode=<?=$pcode?>&kindEvent="+selected+"",
					"aoColumns": [ 
						 {"sClass": "tcenter"},
                        {"sClass": "tcenter"},
						{"sClass": "tleft"},
                        {"sClass": "tcenter"},
						 {"sClass": "tright"},
						 {"sClass": "tright"},
						 {"sClass": "tright"},
						 {"sClass": "tcenter"},
						{"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tleft"},
						 {"sClass": "tcenter"},
						 
						]
		 
			   });

			

            
		})
	</script>
    </body>
</html>



