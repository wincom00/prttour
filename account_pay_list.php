
<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	} else {
        echo "<meta htt-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
   
	if ($estimateCode) {
		$crev =$estimateCode; 

	}
	if ($startDate1 == "") {
		$startDate1 =  date("Y-m-d",strtotime("now"));
		$endDate1 = date("Y-m-d",strtotime("+1 week"));

	}
	for($m=0; $m<count((array)$rstatus); $m++)
		{
			$rst_value .= $rstatus[$m]."/";
		}
	
?>
	<div id="contentwrapper" class="productDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">자금현황</a></li>
					<li><a href="#">예약별 결제현황</a></li>
					
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form  action='<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' enctype="multipart/form-data" name="base_code" id="base_code" method="post" >
						<input type="hidden" name="estimateCode" value="<?=$estimateCode?>">
						<input type="hidden" name="mode" value="search">
						<input type="hidden" name="rst" id="rst" value="<?=$rst_value?>">
						<table class="table table-bordered table-condensed">
						    
							<tr>
								<td width="10%" class="titletd text-center">예약자/여행자 명</td>
								<td width="40%" class="">
									<input type="text" id="cname" name="cname" class="inpubase" value="<?=$cname?>"/>
								</td>
								<td width="10%" class="titletd text-center">전화번호</td>
								<td width="40%" class="">
									<input type="text" id="ctel" name="ctel" class="inpubase" value="<?=$ctel?>"/>
								</td>
							</tr>
							<tr>
								
								<td width="10%" class="titletd text-center">예약번호</td>
								<td width="40%" class="">
									<input type="text" id="crev" name="crev" class="inpubase" value="<?=$crev?>"/>
								</td>
								<td width="10%" class="titletd text-center">이메일</td>
								<td width="40%" class=""><input type="text" id="cemail" name="cemail" class="inpubase" value="<?=$cemail?>" autocomplete=off /></td>
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">투어분류</td>
								<td width="40%" class="">
									<select class="form-control" name="tourCategory">
										<option value="" >- 투어분류 선택하세요 -</option>
										<option value="1" <?php if ($tourCategory==1) { ?> selected <?php } ?> >로컬상품</option>
										<option value="2" <?php if ($tourCategory==2) { ?> selected <?php } ?>>인바운드</option>
										
										<option value="4" <?php if ($tourCategory==4) { ?> selected <?php } ?>>인센티브</option>
										<option value="5" <?php if ($tourCategory==5) { ?> selected <?php } ?>>아웃바운드</option>
									</select>
								</td>
								<td width="10%" class="titletd text-center">접수상태</td>
								<td width="40%" class="">
									<!--<select class="form-control" name="rstatus">
										<option value="" >- 접수상태를 선택하세요 -</option>
										<option value="READY" <?php if ($rstatus=='READY') { ?> selected <?php } ?> >예약접수</option>
										<option value="ORDER" <?php if ($rstatus=='ORDER') { ?> selected <?php } ?>>예약확정</option>
										<option value="DONE" <?php if ($rstatus=='DONE') { ?> selected <?php } ?>>최종안내</option>
										<option value='WAIT' <?php if ($rstatus=='WAIT') { ?> selected <?php } ?>>예약대기</option>
										<option value='CANCEL' <?php if ($rstatus=='CANCEL') { ?> selected <?php } ?>>예약취소</option>
									</select>-->
									<label class="check-inline">
										<input type="checkbox" name="rstatus[]" id="rstatus1" value="READY" <?php if (strstr($rst_value,"READY/")) echo "checked"; ?>> 예약접수
									</label>
									
									<label class="check-inline">
										<input type="checkbox" name="rstatus[]" id="rstatus3" value="DONE" <?php if (strstr($rst_value,"DONE/")) echo "checked"; ?>> 예약확정
									</label>
									
									<label class="check-inline">
										<input type="checkbox" name="rstatus[]" id="rstatus5" value="CANCEL" <?php if (strstr($rst_value,"CANCEL/")) echo "checked"; ?>> 예약취소
									</label>
								</td>
								
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">결제상태</td>
								<td width="40%" class="">
									<select class="form-control" name="tourpay">
										<option value="" >- 결제상태를 선택하세요 -</option>
										<option value="READY" <?php if ($tourpay=='READY') { ?> selected <?php } ?> >미납</option>
										<option value="PPAY" <?php if ($tourpay=='PPAY') { ?> selected <?php } ?>>부분완납</option>
										<option value="DONE" <?php if ($tourpay=='DONE') { ?> selected <?php } ?>>완납</option>
										<option value="OPAY" <?php if ($tourpay=='OPAY') { ?> selected <?php } ?>>환불</option>
									</select>
								</td>
								<td width="10%" class="titletd text-center">출발일</td>
								<td width="40%" class="">
									<div class="row">
									    <div class="col-sm-5">
											<label class="radio-inline">
												<input type="radio" name="kinddate" value="1" <?php if(strstr($kinddate,"1")) echo "checked"; ?>> 출발일
											</label>
											<label class="radio-inline">
												<input type="radio" name="kinddate" value="2" <?php if(strstr($kinddate,"2")) echo "checked"; ?>> 접수일
											</label>
											<label class="radio-inline">
												<input type="radio" name="kinddate" value="" <?php if($kinddate=="") echo "checked"; ?>> 미지정
											</label>
										</div>
										<div class="col-sm-3">
											<input type="search" id="startDate1" name="startDate1" class="inpubase tourDate1" placeholder="시작일" value="<?=$startDate1?>" autocomplete="off" />
										</div>
										<div class="col-sm-3">
											<input type="search" id="endDate1" name="endDate1" class="inpubase tourDate2" placeholder="마지막일" value="<?=$endDate1?>" autocomplete="off" />
										</div>
										
									</div>
								</td>
							</tr>
							<tr>
								
								<td width="10%" class="titletd text-center">접수상태</td>
								<td width="40%" class="">
									<!--<select class="form-control" name="rstatus">
										<option value="" >- 접수상태를 선택하세요 -</option>
										<option value="READY" <?php if ($rstatus=='READY') { ?> selected <?php } ?> >예약접수</option>
										<option value="ORDER" <?php if ($rstatus=='ORDER') { ?> selected <?php } ?>>예약확정</option>
										<option value="DONE" <?php if ($rstatus=='DONE') { ?> selected <?php } ?>>최종안내</option>
										<option value='WAIT' <?php if ($rstatus=='WAIT') { ?> selected <?php } ?>>예약대기</option>
										<option value='CANCEL' <?php if ($rstatus=='CANCEL') { ?> selected <?php } ?>>예약취소</option>
									</select>-->
									<div class="col-sm-7">
											<label class="radio-inline">
												<input type="radio" name="kindamt" value="1" <?php if(strstr($kindamt,"1")) echo "checked"; ?>> 선수금
											</label>
											<label class="radio-inline">
												<input type="radio" name="kindamt" value="2" <?php if(strstr($kindamt,"2")) echo "checked"; ?>> 미수금
											</label>
											<label class="radio-inline">
												<input type="radio" name="kindamt" value="3" <?php if(strstr($kindamt,"3")) echo "checked"; ?>> 환불금
											</label>
											
									</div>
								</td>
								
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">지사(지역)</td>
								<td width="40%" class="">
									<select class="form-control" name="sarea" id="sarea">
										<option value="">- 지사를 선택하세요 -</option>
										<?=printBaseCode_first("S03",$sarea)?>
									</select>
								</td>
								<td colspan="2"></td>
							</tr>
							<tr>
								<td colspan="4" class="text-center"><button type='button' class="btn btn-primary btn-sm btn1">검색</button></td>

							</tr>
						</table>

						
					</form>

					<br />
					<div class="row" style="margin-bottom:12px;">
						<div class="col-sm-12">
							<div style="display:flex;gap:10px;flex-wrap:wrap;">
								<div style="flex:1;min-width:130px;border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
									<div style="font-size:12px;color:#888;margin-bottom:4px;">총 건수</div>
									<div id="sc-rows" style="font-size:22px;font-weight:bold;color:#337ab7;">-</div>
								</div>
								<div style="flex:1;min-width:130px;border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
									<div style="font-size:12px;color:#888;margin-bottom:4px;">총 인원</div>
									<div id="sc-pcnt" style="font-size:22px;font-weight:bold;color:#5cb85c;">-</div>
								</div>
								<div style="flex:1;min-width:130px;border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
									<div style="font-size:12px;color:#888;margin-bottom:4px;">총 결제금액</div>
									<div id="sc-total" style="font-size:22px;font-weight:bold;color:#d9534f;">-</div>
								</div>
								<div style="flex:1;min-width:130px;border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
									<div style="font-size:12px;color:#888;margin-bottom:4px;">받은금액</div>
									<div id="sc-paid" style="font-size:22px;font-weight:bold;color:#5cb85c;">-</div>
								</div>
								<div style="flex:1;min-width:130px;border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
									<div style="font-size:12px;color:#888;margin-bottom:4px;">잔액 (미수금)</div>
									<div id="sc-balance" style="font-size:22px;font-weight:bold;color:#f0ad4e;">-</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed js-revTable">
								<thead>
								   <!--
									<tr>
										<th>투어분류</th>
										<th>대표예약번호</th>
										<th>예약번호</th>
										<th>상품명</th>
										<th>예약자</th>
										<th>최종결제금액</th>
										<th>잔액</th>
										<th>상품소유사</th>
										<th>출발일</th>
										<th>접수일</th>
										<th>최종수정일</th>
										<th>접수상태</th>
										<th>담당자</th>
									</tr>
									-->
									<tr>
									    <th>예약경로</th>
										<th>투어분류</th>
										<th>대표예약번호</th>
										<th>예약번호</th>
										<th>접수일</th>
										<th>소유사</th>
										<th>상품명</th>
										<th>출발일</th>
										<th>예약자명</th>
										<th>인원</th>
										<th>접수상태</th>
										<th>기준통화</th>
										<th>최종결제금액</th>
										<th>받은금액</th>
										<th>발란스</th>
										
										
									</tr>
								</thead>
								<tbody>
									
								</tbody>
							</table>
						</div>
					</div>
					
				</div><!-- -->
			</div>                
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <style>
        .js-revTable a,
        .js-revTable a:link,
        .js-revTable a:visited,
        .js-revTable a:hover,
        .js-revTable a:active { color: #000000 !important; }
    </style>
    <?php
        $ajaxParams = http_build_query(array(
            'division' => $division,
            'pdx' => $pdx,
            'sub' => $sub,
            'ty' => $ty,
            'cname' => $cname,
            'startDate1' => $startDate1,
            'endDate1' => $endDate1,
            'crev' => $crev,
            'cemail' => $cemail,
            'ctel' => $ctel,
            'tourCategory' => $tourCategory,
            'tourpay' => $tourpay,
            'kinddate' => $kinddate,
            'kindamt' => $kindamt,
            'sarea' => $sarea
        ));
    ?>
    <script>

		$(document).ready(function () {
				//pt.initReservationList()
				//pt1.initProductDetailForm2()
				var dateToday = new Date()
			    $('.tourDate1').datepicker({
					format: "yyyy-mm-dd",
					autoclose: true
			    });
				$('.tourDate2').datepicker({
					format: "yyyy-mm-dd",
					autoclose: true
			    });
				$( ".btnchk" ).click(function() {
					
					if ($("#startDate1").val() == "") {
						alert("출발일을 입력하세요!");
						$("#startDate1").focus();
						return false;
				    }
					

				});
				$( ".btn1" ).click(function() {
					
					
					//$("#base_code").attr("action","total_reservation.php"); 
					$("#base_code").submit();

				});
				$( ".btn6" ).click(function() {
					
					
					$("#base_code").attr("action","tot_reservation_excel.php"); 
					$("#base_code").submit();
					
				});
				
				
				var selected  = $("#rst").val();
				var ajaxSource = "alltotprocess2.php?<?=$ajaxParams?>&rstatus=" + encodeURIComponent(selected);
					
				
				$('.js-revTable').DataTable({
					 "bProcessing": true,
					 "bServerSide": true,
					 "pageLength": 100,
					  "scrollX": true,
					 "scrollY" : "800px",
					  bFilter: false,
					 dom: 'Bfrtip',
					 buttons: [
						'copy', 'csv', 'excel', 'print'
					 ],
					"sServerMethod": "POST",
					 "order": [[ 4, "desc" ]],
					
					"sAjaxSource": ajaxSource,
				"fnServerData": function(sSource, aoData, fnCallback) {
					$.ajax({
						type: "POST", url: sSource, data: aoData, dataType: "json",
						success: function(json) {
							var d = json;
							var fmt = function(n){ return Number(Math.round(n)).toLocaleString(); };
							$("#sc-rows").text(fmt(d.iTotalDisplayRecords || 0));
							$("#sc-pcnt").text(fmt(d.sum_pcnt || 0));
							$("#sc-total").text("$"+fmt(d.sum_total || 0));
							$("#sc-paid").text("$"+fmt(d.sum_paid || 0));
							$("#sc-balance").text("$"+fmt(d.sum_balance || 0));
							fnCallback(d);
						},
						error: function(xhr) {
							console.error("account_pay_list DataTable ajax error", xhr.responseText);
							fnCallback({
								sEcho: 0,
								iTotalRecords: 0,
								iTotalDisplayRecords: 0,
								aaData: []
							});
						}
					});
				},
				    "aoColumns": [ 
                        {"sClass": "tcenter"},
                        {"sClass": "tcenter"},
                        {"sClass": "tcenter"},
                        {"sClass": "tcenter"},
                        {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tleft"},
						 {"sClass": "tleft"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 {"sClass": "tcenter"},
						 

						]
		 
			   });
		})
		
	</script>
    </body>
</html>
