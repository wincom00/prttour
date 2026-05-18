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
	/*
	select c.grand_eCode,b.traveler_nm,a.grand_revNo,a.reserveCode ,a.p_code,a.p_name,a.stDate,a.p_cnt
 from reserve_info a, reserve_traveler b, tour_master c  where a.reserveCode = b.reserveCode 
&& c.grand_eCode ='TVE190617001'  && a.p_code=c.p_code && a.stDate = '2019-06-27' && b.seqint ='0'  
*/
	$sctour = getTourInfo2($pcode,$st);
	
	$pcnt = getReserveInfoCnt($pcode,$st);
	//echo "111";
	$piccnt = getPicGr5($pcode,$st);
	//echo "111";
	
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
					<li>행사현황</li>
					<li>복합행사예약현황-상세보기</li>
				</ul>
			</div>

			
            <br />
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
					<div class='row'>
                                    <div class='col-sm-1'></div>
                                    <div class='col-sm-5'>
                                        <div class='panel-group'>
                                            <div class='panel panel-default'>
                                                <div class='panel-body custom_padding bg-info'>총인원 : <?=$pcnt['cnt']?>인 &nbsp;&nbsp;&nbsp;&nbsp<br /> 
                                                 <?=$piccnt?>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>    
                                </div>
                    <br/>
					<table class="table table-striped table-bordered table-hover table-condensed js-revTable">
						<thead>
						   
							<tr>
								
								<th width="8%">예약번호</th>
								<th width="8%">여행자</th>
								<th width="5%">인원</th>
								<th width="8%">최종결제금액</th>
								<th width="8%">잔액</th>
								<th width="8%">출발일</th>
								<th width="12%">접수일</th>
								<th width="6%">접수상태</th>
								<th width="6%">담당자</th>
								<th width="*">진행사항</th>
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
					"order": [[ 0, "desc" ]],
					"sServerMethod": "POST",
					"sAjaxSource": "alleveprocess2.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&startDate1=<?=$st?>&pcode=<?=$pcode?>&kindEvent="+selected+"",
					"aoColumns": [ 
                        
						{"sClass": "tleft"},
                        {"sClass": "tcenter"},
						 {"sClass": "tright"},
						 {"sClass": "tright"},
						 {"sClass": "tright"},
						 {"sClass": "tleft"},
						{"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tleft"},
						 {"sClass": "tleft"},
						 
						]
		 
			   });

			

            
		})
	</script>
    </body>
</html>



