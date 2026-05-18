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
    
	if ($startDate1 == "") {
		$startDate1 =  date("Y-m-d",strtotime("now"));
		$endDate1 = date("Y-m-d",strtotime("+1 week"));

	}
	function printSingle(){
			
			global $dbConn,$division,$crev,$pdx,$sub,$productName,$evest,$startDate1,$endDate1,$k,$productOwener,$area1,$area2;
			

			if ($productName!="") {
					$qrynm = " && a.p_name like '%$productName%'";

			} else {
				    $qrynm ="";
			}
			if ($productOwener!="") {
					$qryown = " && a.p_own = '$productOwener'";

			} else {
				    $qryown ="";
			}
			if ($startDate1) {
					$qrysdate = " && ((  a.stDate >= '$startDate1' && a.stDate <= '$endDate1' )) ";
					$weektot = array("0", "1", "2","3","4","5","6","9"); 
					$weeknum= ($weektot[date('w', strtotime($startDate1))]);


				    //$startDate = "9" - 매일출발;
				    $startWeek_qry = "|| (a.p_week like '%$weeknum%' || a.p_week like '%9%'))";
			} else {
				    $qrysdate ="";
			}

			if ($evest) {
					$qryeve = " && c.ev_status='$evest'";

			} else {
				    $qryeve ="";
			}
			if ($area1!="") {
				$qryarea1 = " && b.c_code1='$area1'";
			} else {
				$qryarea1 = "";
			}
			if ($area2!="") {
				$qryarea2 = " && b.c_code2='$area2'";
			} else {
				$qryarea2 = "";
			}
			$qry1 = "select c.grand_eCode,a.p_code,a.p_name,a.stDate,b.c_code1,b.c_code2,b.p_own,
						b.p_day,b.p_cnt ,c.r_status,c.ev_status,c.tour_pcnt from 
						product_master b, reserve_info a  left outer join tour_master c on a.p_code=c.p_code && a.stDate = c.stDate
						 where a.p_code = b.p_code  && (b.m_type = 'D'  ||  a.p_code  like '%PICKUP%' || a.p_code  like '%SENDING%') 
						 && a.p_code not in ('SPICKUP003','SSEND007') && a.rev_status !='CANCEL' $qrynm $qrysdate $qryeve $qryown $qryarea1 $qryarea2 group by a.p_code,a.stDate  order by a.stDate asc";
			$rst1 = mysql_query($qry1,$dbConn);
			//echo $qry1."<br/><br/>";
			//$k=0;
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

				
				$sday = $row1['stDate'] ;
				
				$week = array("일" , "월"  , "화" , "수" , "목" , "금" ,"토") ;
				$eweek = array("SUN" , "MON" , "TUE" , "WED" , "THU" , "FRI" ,"SAT") ;
				$sweekday = $week[ date('w'  , strtotime($sday)  ) ] ;
			    if ($pwcnt['cnt'] == "")  {
					$pwcnt['cnt'] =0;
				}
				if ($pcnt['cnt'] == "")  {
					$pcnt['cnt'] =0;
				}
				//$acnt = $row1[p_cnt] - $pcnt[cnt]; 
				if ($row1['tour_pcnt'] != "") {
					$row1['p_cnt'] = $row1['tour_pcnt'];
				}
				echo "<tr class='arhef' data-href='event_reservation_detail2.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}' data-target='_blank'>
							
							
							<td>{$cinfo2['comment']}</td>
							<td>{$row1['p_code']}</td>
							<td>{$row1['p_name']}<input type='hidden' name='pcode[$k]' value='{$row1['p_code']}'><input type='hidden' name='pname[$k]' value='{$row1['p_name']}'></td>
							<td align='center'>{$row1['stDate']} ($sweekday)<input type='hidden' name='sdate[$k]' id='sdate' value='{$row1['stDate']}'></td>
							<td align='center'>{$pcnt['cnt']}</td>
							<td>{$randrow['kor_name']} </td>
							
						</tr>";
				$k++;

			
			}

	}
	
	
    
?>
	<div id="contentwrapper" class="productDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사관리</a></li>
					<li>행사현황</li>
					<li>복합행사예약현황</li>
				</ul>
			</div>
			<form id="frmName" name="frmName" method="post">
				<input type="hidden" name="mode" id="mode" value="search">
				<input type="hidden" name="productOwener1" id="productOwener1" value="<?=$productOwener?>">
			<div class="row">
				<div class="col-sm-12 col-md-12">
					
						
                        <br />
						<table class="table table-bordered table-condensed">
							<tr>
								<td width="10%" class="titletd text-center">상품명</td>
								<td width="40%" class=""><input type="text" id="prod_code" name="productName" class="inpubase" value="<?=$productName?>"/></td>
								<td width="10%" class="titletd text-center">출발일</td>
								<td width="40%" class="">
									<div class="row">
                                        
										<div class="col-sm-6">
                                            <input type="date" class="form-control" id="startDate1" name="startDate1" max="2999-12-31" placeholder="시작일" value="<?=$startDate1?>" autocomplete="off" />
											<!--<input type="search" id="startDate1" name="startDate1" class="inpubase tourDate1" placeholder="시작일" value="<?=$startDate1?>" autocomplete="off" />-->
										</div>
										<div class="col-sm-6">
										    <input type="date" class="form-control" id="endDate1" name="endDate1" max="2999-12-31" placeholder="마지막일" value="<?=$endDate1?>" autocomplete="off" />
											<!--<input type="search" id="endDate1" name="endDate1" class="inpubase tourDate2" placeholder="마지막일" value="<?=$endDate1?>" autocomplete="off" />-->
										</div>
										
								
                                    </div>
								</td>
							</tr>
							
							<tr>
								<td width="10%" class="titletd text-center">상품소유사</td>
								<td width="40%" class="no-right-border">
									<select class="form-control" name="productOwener">
										<option value="">- 선택 -</option>
										<option value="paran" <?php if ($productOwener == "paran") {?> selected <?php } ?>>파란여행</option>
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
								
								<td width="10%" class="titletd text-center">지역분류</td>
								<td width="40%" class="form-inline" bgcolor=#FFFFFF colspan=3>
								
										
											<select class="form-control fst1" name="area1" id="area1">
												<option value="">- 선택 -
												<?=printBaseCode_first('T01',$area1)?>
											</select>
										
											<select class="form-control fst2" name="area2" id="area2">
												<option value="">- 선택 - 
												<?=printBaseCode_hsecond('T01',$area1,$area2)?>
											</select>
										
									
								</td>
							</tr>
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
							</tr>
						</table>
					

					<br />
					<div class="row">
						<div class="col-sm-12">
							<table name="ctable" id="ctable"  class="table table-striped table-bordered table-hover table-condensed js-productTable1">
								<thead>
									<tr>
									    
										
										<th>상품지역분류</th>
										<th>상품코드</th>
										<th>상품명</th>
										<th>출발일</th>
										<th>예약</th>
										<th>상품소유사</th>
										
									</tr>
								</thead>
								<tbody>
								<?php 
								
									 echo printSingle();
											
								   
								?>
								</tbody>
							</table>
						</div>

					</div>
				</div><!-- -->
				
			</div> 
			</form>
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
            
            $.ajaxSetup({async:false});
			pt.initReservationList()
            pt1.initProductDetailForm2()
			var dateToday = new Date()
			$('.tourDate1').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
				
			});
			$('.tourDate2').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
			});
            
			pt.initReservationList()
			var oTable = $('#ctable').dataTable({
				stateSave: true,
				pageLength: 100
			});
			$('tr[data-href]').on("click", function() {
				//document.location = $(this).data('href');
				window.open($(this).data("href"), $(this).data("target"));
			});
			 
			$(".dataTables_length").css({ "display" :"none" });
		})
	</script>
    </body>
</html>
