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

	function printSingle(){
			
			global $dbConn,$division,$crev,$pdx,$sub,$productName,$evest,$startDate1,$endDate1;
			

			if ($productName) {
					$qrynm = " && a.p_name like '%$productName%'";

			} else {
				    $qrynm ="";
			}

			if ($startDate1) {
					$qrysdate = " && a.stDate between '$startDate1' and '$endDate1'";

			} else {
				    $qrysdate ="";
			}

			if ($evest) {
					$qryeve = " && c.ev_status='$evest'";

			} else {
				    $qryeve ="";
			}
			$qry1 = "select a.reserveCode,c.grand_eCode,a.p_code,a.p_name,a.stDate ,b.c_code1,b.c_code2,b.p_own,c.tour_pcnt,
						b.p_day,b.p_cnt ,c.r_status,c.ev_status from reserve_info a ,
						product_master b ,tour_master c 
						 where a.p_code = b.p_code &&  b.p_code=c.p_code  && b.p_type in ('1','2','4') && b.m_type = 'S' && a.stDate =c.stDate && b.p_own='purun' && (b.p_day > 1 )
						 && a.p_code not like '%PICKUP%'  && a.p_code not like '%SENDING%' $qrynm $qrysdate $qryeve group by a.p_code,a.stDate order by a.stDate asc";
			
			$rst1 = mysql_query($qry1,$dbConn);
			//echo $qry1;
			while($row1 = mysql_Fetch_assoc($rst1)){
				$cinfo1=codebaseName($row1['c_code1']);
				$cinfo2=codebaseName($row1['c_code2']);
				if ($row1['r_status']== 'P') {
					$row1['r_status'] = "<font color=red>예약접수중</font>";
				}
				if ($row1['r_status']== 'C') {
					$row1['r_status'] = "<font color=red>예약마감</font>";
				}
                
				if ($row1['r_status']== '') {
					$row1['r_status'] = "<font color=red>미등록</font>";
				}

				if ($row1['ev_status']== '1') {
					$row1['ev_status'] = "<font color=red>미확정</font>";
				}
				if ($row1['ev_status']== '2') {
					$row1['ev_status'] = "<font color=red>확정</font>";
				}
				if ($row1['ev_status']== '3') {
					$row1['ev_status'] = "<font color=red>만차</font>";
				}
				if ($row1['ev_status']== '4') {
					$row1['ev_status'] = "<font color=red>취소</font>";
				}
				if ($row1['ev_status']== '5') {
					$row1['ev_status'] = "<font color=red>기타</font>";
				}

				if ($row1['ev_status']== '') {
					$row1['ev_status'] = "<font color=red>미등록</font>";
				}

				
				//$user_rinfo = getinfo_dbMember($row1[userid]);
				if ($row1['p_own'] == "purun") {
					$randrow['kor_name'] = "푸른투어";
				} else {
					$randrow['kor_name'] = randname($row1['p_own']);
				}

				$pcnt = getReserveInfoCnt($row1['p_code'],$row1['stDate']);

				
			    if ($pwcnt['cnt'] == "")  {
					$pwcnt['cnt'] =0;
				}
				if ($pcnt['cnt'] == "")  {
					$pcnt['cnt'] =0;
				}
				if ($row1['tour_pcnt'] != "")  {
					$row1['p_cnt'] = $row1['tour_pcnt'];
				}
				$buscnt = getBusCnt($row1['grand_eCode']);
				if ($buscnt['cnt'] == "0")  {
					$row1['room_num'] = "<font color=red>미배정</font>";
				} else {
					$row1['room_num'] = "<font color=red>배정</font>";
				}
				$chkchg  = 0;
				$bg = "#fff";
				
				
				echo "<tr class='arhef' data-href='hotel_assign_m.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}'>
							<td align='center' bgcolor='$bg'>{$row1['grand_eCode']}</td>
							<td>{$cinfo1['comment']}:{$cinfo2['comment']}</td>
							<td>[{$row1['p_code']}] {$row1['p_name']}</td>
							<td align='center'>{$row1['stDate']}</td>
							<td align='center'>{$pcnt['cnt']}</td>
							<td>{$randrow['kor_name']} </td>
							<td align='center'>{$row1['r_status']}</td>
							<td align='center'>{$row1['ev_status']}</td>
							<td align='center'>{$row1['room_num']}</td>
						</tr>";


			}

	}
	
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정</a></li>
					<li>호텔배정관리</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="" name="frmName" method="post">
						<input type="hidden" name="mode" value="search">
						<input type="hidden" name="productOwener1" id="productOwener1" value="<?=$productOwener?>">
						<table class="table table-bordered table-condensed">
							<tr>
								<td width="10%" class="titletd text-center">상품명</td>
								<td width="40%" class=""><input type="text" id="prod_code" name="productName" class="inpubase" value=""/></td>
								<td width="10%" class="titletd text-center">출발일</td>
								<td width="40%" class="">
									<div class="row">
                                        
										<div class="col-sm-6">
											<input type="search" id="startDate1" name="startDate1" class="inpubase tourDate1" placeholder="시작일" value="<?=$startDate1?>" autocomplete="off" />
										</div>
										<div class="col-sm-6">
											<input type="search" id="endDate1" name="endDate1" class="inpubase tourDate2" placeholder="마지막일" value="<?=$endDate1?>" autocomplete="off" />
										</div>
										
								
                                    </div>
								</td>
							</tr>
							
							<tr>
								<td width="10%" class="titletd text-center">상품소유사</td>
								<td width="40%" class="no-right-border">
									<select class="form-control" name="productOwener">
										<option value="">- 선택 -</option>
										<?=printRandSelect($productOwener1)?>
									</select>
								</td>
								<td width="10%" class="titletd text-center">행사상태</td>
								<td width="40%" class="no-right-border">
									<select class="form-control" name="evest">
										<option value="">- 선택 -</option>
										<option value="1" <?php if($evest==1) echo "selected"; ?> >미확정</option>
										<option value="2" <?php if($evest==2) echo "selected"; ?>>확정</option>
										<option value="3" <?php if($evest==3) echo "selected"; ?>>만차</option>
										<option value="4" <?php if($evest==4) echo "selected"; ?>>취소</option>
										<option value="5" <?php if($evest==5) echo "selected"; ?>>기타</option>
										
									</select>
								</td>
                            </tr>				
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
							</tr>
						</table>
					</form>

					<br />
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed " id='ctable'>
								<thead>
									<tr>
										<th>통합행사코드</th>
										<th>상품지역분류</th>
										<th>상품명</th>
										<th>출발일</th>
										<th>예약</th>
										<th>상품소유사</th>
										<th>예약상태</th>
										<th>행사상태</th>
										<th>배정상태</th>
									</tr>
								</thead>
								<tbody>
									<?=printSingle()?>
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
    <script>
		$(document).ready(function () {
            
            pt.initReservationDetail()

			{
				
			}
			var dateToday = new Date()
			$('.tourDate1').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true,
				//startDate: dateToday
			});
			$('.tourDate2').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true,
				//startDate: dateToday
			});
            
			pt.initReservationList()
			var oTable = $('#ctable').dataTable({
				
				pageLength: 100,
				"order": [[ 3, "desc" ]]
			});
			$('tr[data-href]').on("click", function() {
				document.location = $(this).data('href');
			});
			$(".dataTables_length").css({ "display" :"none" });
		})
	</script>
    </body>
</html>
