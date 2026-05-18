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
    if (($kindEvent == 2) || ($kindEvent == "")) {
		$lst = 2;

	} else if (($kindEvent == 1)) { 
		$lst = 1;

	}
	
	if ($startDate1 == "") {
		$startDate1 =  date("Y-m-d",strtotime("now"));
		$endDate1 = date("Y-m-d",strtotime("+1 month"));

	}
	$page            = max(1, (int)(isset($_POST['page']) ? $_POST['page'] : 1));
	$pageSize        = 30;
	$paginationTotal = 0;

	function printSingle($page=1, $pageSize=30){
			
			global $dbConn,$division,$crev,$pdx,$sub,$productName,$evest,$startDate1,$endDate1,$k,$productOwener;
			

			if ($productName!="") {
					$qrynm = " && a.p_name like '%$productName%'";

			} else {
				    $qrynm ="";
			}
			if ($productOwener!="") {
					$qryown = " && b.p_own = '$productOwener'";

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
			//&& a.p_code !='NJP1N'
			$cntQry = "select count(*) as total from (
						select a.p_code,a.stDate from
						product_master b, reserve_info a  left outer join tour_master c on a.p_code=c.p_code && a.stDate = c.stDate
						 where a.p_code = b.p_code && (p_type in ('1','2','4') || a.p_code not like '%PICKUP%' || a.p_code not like '%SENDING%') && b.m_type = 'S'  &&  b.p_code not like '%ADD%' && a.rev_status!='CANCEL'
						  $qrynm $qrysdate $qryeve $qryown group by a.p_code,a.stDate
					) as _cnt";
			$cntRst = mysql_query($cntQry,$dbConn);
			$cntRow = mysql_fetch_assoc($cntRst);
			$total  = (int)$cntRow['total'];
			$offset = ($page - 1) * $pageSize;
			$qry1 = "select c.grand_eCode,a.p_code,b.p_name,a.stDate,b.c_code1,b.c_code2,b.p_own,
						b.p_day,b.p_cnt ,c.r_status,c.ev_status,c.tour_pcnt from
						product_master b, reserve_info a  left outer join tour_master c on a.p_code=c.p_code && a.stDate = c.stDate
						 where a.p_code = b.p_code && (p_type in ('1','2','4') || a.p_code not like '%PICKUP%' || a.p_code not like '%SENDING%') && b.m_type = 'S'  &&  b.p_code not like '%ADD%' && a.rev_status!='CANCEL'
						  $qrynm $qrysdate $qryeve $qryown group by a.p_code,a.stDate  order by a.stDate asc
						 LIMIT $offset, $pageSize";
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
					$randrow= randname($row1['p_own']);
				}
				//print_r($randrow);
				//echo "<br>";
				$pcnt = getReserveInfoCnt($row1['p_code'],$row1['stDate']);

				$pwcnt = getReserveWaitSCnt($row1['p_code'],$row1['stDate']);
				$sday = $row1['stDate'] ;
				
				$week = array("일" , "월"  , "화" , "수" , "목" , "금" ,"토") ;
				$eweek = array("SUN" , "MON" , "TUE" , "WED" , "THU" , "FRI" ,"SAT") ;
				$sweekday = $week[ date('w'  , strtotime($sday)  ) ] ;
			    if ($pwcnt['cnt'] == "")  {
					$pwcnt['cnt'] =0;
				}
			
				//$acnt = $row1[p_cnt] - $pcnt[cnt]; 
				if ($row1['tour_pcnt'] != "") {
					$row1['p_cnt'] = $row1['tour_pcnt'];
				}
				echo "<tr class='arhef' data-href='event_reservation_detail.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}' data-target='_blank'>
							
							<td align='center'>{$row1['grand_eCode']}<input type='hidden' name='gcode[$k]' value='{$row1['grand_eCode']}'></td>
							<td>{$cinfo2['comment']}</td>
							<td>{$row1['p_code']}</td>
							<td>{$row1['p_name']}<input type='hidden' name='pcode[$k]' value='{$row1['p_code']}'><input type='hidden' name='pname[$k]' value='{$row1['p_name']}'></td>
							<td align='center'>{$row1['stDate']} ($sweekday)<input type='hidden' name='sdate[$k]' id='sdate' value='{$row1['stDate']}'></td>
							<td align='center'><input type='hidden' name='acnt[$k]' value='{$row1['p_cnt']}'>{$row1['p_cnt']}</td>
							<td align='center'>{$pcnt['cnt']}</td>
							<td>{$randrow['kor_name']} </td>
							<td align='center'>{$row1['r_status']}</td>
							<td align='center'>{$row1['ev_status']}</td>
						</tr>";
				$k++;

			
			}

	return $total;
	}
	
	function printSingle2($basedate){
			
			global $dbConn,$division,$crev,$pdx,$sub,$productName,$evest,$startDate1,$endDate1,$k,$productOwener;

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


			
			$qrysdate = " && ((  a.p_vstart <= '$basedate' && a.p_vend >= '$basedate' ) ";
			//$qrysdate = " &&  ((a.p_vend >= '$basedate') ";
			$weektot = array("0", "1", "2","3","4","5","6","9"); 
			$weeknum= ($weektot[date('w', strtotime($basedate))]);


			//$startDate = "9" - 매일출발;
			$startWeek_qry = "&& (a.p_week like '%$weeknum%'))";
	


		
			
			if ($evest!="") {
					$qryeve = " && c.ev_status='$evest'";

			} else {
				    $qryeve ="";
			}
			$qry1 = "select c.grand_eCode,a.p_code,a.p_name,b.stDate,a.c_code1,a.c_code2,a.p_own,a.p_day,a.p_cnt ,c.r_status,c.ev_status, c.tour_pcnt 
			from product_master a left outer join reserve_info b on a.p_code = b.p_code && b.stDate = '$basedate' &&  b.rev_status!='CANCEL'
			left outer join tour_master c on a.p_code=c.p_code && c.stDate = '$basedate' 
			where a.p_type in ('1','3','4','5')  && a.m_type = 'S' && a.p_code not in ('SPICKUP003','SSEND007') && a.p_own='paran' &&  a.p_code not like '%ADD%' $qrysdate $startWeek_qry $evest $qrynm $qryown group by a.p_code,b.stDate
			union
			select c.grand_eCode,a.p_code,a.p_name,b.stDate,a.c_code1,a.c_code2,a.p_own,a.p_day,a.p_cnt ,c.r_status,c.ev_status, c.tour_pcnt 
			from product_master a left outer join reserve_info b on a.p_code = b.p_code && b.stDate = '$basedate' &&  b.rev_status!='CANCEL'
			left outer join tour_master c on a.p_code=c.p_code && c.stDate = '$basedate'
			left outer join product_limit d on a.p_code=d.p_code && d.p_type = 'R'
            where a.p_type in ('1','3','4','5') && a.m_type = 'S'  && a.p_code not in ('SPICKUP003','SSEND007')  && d.p_limitdate = '$basedate' &&  a.p_code not like '%ADD%' $evest $qrynm $qryown group by a.p_code,b.stDate
			";

			//echo $qry1."<br/><br/>";
			/*
			$qry1 = "select * from (
						select c.grand_eCode,a.p_code,a.p_name,c.stDate,a.c_code1,a.c_code2,a.p_own,a.p_week,
						a.p_day,a.p_cnt ,c.r_status,c.ev_status, c.tour_pcnt from product_master a 
						left outer join tour_master c on a.p_code=c.p_code && c.stDate = '$basedate' 
						where a.p_type in ('1','5') && a.p_code not in ('SPICKUP003','SSEND007') && a.p_code not like '%ADD%' 
						$qrysdate $startWeek_qry $evest  group by a.p_code,c.stDate 
						union 
						select c.grand_eCode,a.p_code,a.p_name,c.stDate,a.c_code1,a.c_code2,a.p_week,
						a.p_own,a.p_day,a.p_cnt ,c.r_status,c.ev_status, c.tour_pcnt from product_master a 
						left outer join tour_master c on a.p_code=c.p_code && c.stDate = '$basedate' 
						left outer join product_limit d on a.p_code=d.p_code && d.p_type = 'R' 
						 where a.p_type in ('1','5') && a.p_code not in ('SPICKUP003','SSEND007')
						 && d.p_limitdate = '$basedate'  && a.p_code not like '%ADD%' group by a.p_code,c.stDate) x
						where  not exists ( select * from product_limit b where x.p_code=b.p_code && b.p_limitdate = '$basedate')  group by x.p_code
					"; 
			/*$qry1 = "select * from (select c.grand_eCode,a.p_code,a.p_name,c.stDate,a.c_code1,a.c_code2,a.p_own,a.p_day,a.p_cnt ,c.r_status,c.ev_status, c.tour_pcnt 
			from product_master a 
			left outer join tour_master c on a.p_code=c.p_code && c.stDate = '$basedate' 
			where a.p_type in ('1','5')  && a.p_code not in ('SPICKUP003','SSEND007') &&  a.p_code not like '%ADD%' $qrysdate $startWeek_qry $qryeve $qrynm group by a.p_code,c.stDate
			union
			select c.grand_eCode,a.p_code,a.p_name,c.stDate,a.c_code1,a.c_code2,a.p_own,a.p_day,a.p_cnt ,c.r_status,c.ev_status, c.tour_pcnt 
			from product_master a 
			left outer join tour_master c on c.p_code=c.p_code && c.stDate = '$basedate'
			left outer join product_limit d on a.p_code=d.p_code && d.p_type = 'R'
            where a.p_type in ('1','5')  && a.p_code not in ('SPICKUP003','SSEND007')  && d.p_limitdate = '$basedate' &&  a.p_code not like '%ADD%' $qryeve $qrynm group by a.p_code,c.stDate) a,product_limit b where a.p_code = b.p_code && b.p_limitdate not in ( '$basedate') group by a.p_code
			";*/ 
			$rst1 = mysql_query($qry1,$dbConn);
			//echo $qry1."<br /><br />";
			//exit;
			//$k=0;
			while($row1 = mysql_Fetch_assoc($rst1)){
				$qry2= "select count(*) cnt from product_limit  where p_code='{$row1['p_code']}' && p_limitdate = '$basedate' && p_type='L'";
				$rst2 = mysql_query($qry2,$dbConn);
				$row2 = mysql_Fetch_assoc($rst2);
				if ($row2['cnt'] == 0) {
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
							$randrow = randname($row1['p_own']);
						}
						$row1['stDate'] = $basedate;
						$pcnt = getReserveInfoCnt($row1['p_code'],$row1['stDate']);

						$pwcnt = getReserveWaitSCnt($row1['p_code'],$row1['stDate']);
					 
						if ($pwcnt['cnt'] == "")  {
							$pwcnt['cnt'] =0;
						}
						if ($pcnt['cnt'] == "")  {
							$pcnt['cnt'] =0;
						}
						if ($row1['tour_pcnt'] != "") {
							$row1['p_cnt'] = $row1['tour_pcnt'];
						}
						if (($pcnt['cnt'] > 0) || ($row1['grand_eCode']!=""))  {
							$href="data-href='event_reservation_detail.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}'";
						} else {
							$href="";
						}
						
						$sday = $row1['stDate'] ;
				
						$week = array("일" , "월"  , "화" , "수" , "목" , "금" ,"토") ;
						$eweek = array("SUN" , "MON" , "TUE" , "WED" , "THU" , "FRI" ,"SAT") ;
						$sweekday = $week[ date('w'  , strtotime($sday)  ) ] ;	
						//$acnt = $row1[p_cnt] - $pcnt[cnt]; 
						//if ($row1[stDate] == "") {
							

						//}
						//echo $row1[grand_eCode]."<br />";
						$list .= "<tr class='arhef' $href data-target='_blank'>
									
									<td align='center'>{$row1['grand_eCode']}</a><input type='hidden' name='gcode[$k]' value='{$row1['grand_eCode']}'></td>
									<td>{$cinfo2['comment']}</a></td>
									<td>{$row1['p_code']}</a></td>
									<td>{$row1['p_name']}</a><input type='hidden' name='pcode[$k]' value='{$row1['p_code']}'><input type='hidden' name='pname[$k]' value='{$row1['p_name']}'></td>
									<td align='center'>{$row1['stDate']} ($sweekday)</a><input type='hidden' name='sdate[$k]' id='sdate' value='{$row1['stDate']}'></td>
									<td align='center'><input type='hidden' name='acnt[$k]' value='{$row1['p_cnt']}'>{$row1['p_cnt']}</td>
									<td align='center'>{$pcnt['cnt']}</td>
									<td>{$randrow['kor_name']} </td>
									<td align='center'>{$row1['r_status']}</td>
									<td align='center'>{$row1['ev_status']}</td>
								</tr>";
						$k++;
				}

			}
			return $list;

	}
    
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사관리</a></li>
					<li>행사현황</li>
					<li>행사예약현황</li>
				</ul>
			</div>
			<form id="frmName" name="frmName" method="post">
				<input type="hidden" name="mode" id="mode" value="search">
				<input type="hidden" name="productOwener1" id="productOwener1" value="<?=$productOwener?>">
				<input type="hidden" name="page" id="pageInput" value="<?=$page?>">
			<div class="row">
				<div class="col-sm-12 col-md-12">
					
						
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
										<option value="paran" <?php if ($productOwener == "paran") {?> selected <?php } ?>>푸른투어</option>
										<?=printRandSelect($productOwener)?>
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
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1" onclick="document.getElementById('pageInput').value=1">검색</button></td>
							</tr>
						</table>
					

					<br />
					<div class="row">
						<div class="col-sm-12">
							<table name="ctable" id="ctable"  class="table table-striped table-bordered table-hover table-condensed js-productTable1">
								<thead>
									<tr>
									    
										<th>통합행사코드</th>
										<th>상품지역분류</th>
										<th>상품코드</th>
										<th>상품명</th>
										<th>출발일</th>
										<th>정원</th>
										<th>예약</th>
										<th>상품소유사</th>
										<th>예약상태</th>
										<th>행사상태</th>
									</tr>
								</thead>
								<tbody>
								<?php
								   if ($lst == 1) {
									   $k = 0;
									   $startD = ($startDate1 != "") ? $startDate1 : date("Y-m-d");
									   $endD   = ($endDate1   != "") ? $endDate1   : date("Y-m-d", strtotime("+6 days"));
									   $allDates = [];
									   $cur = $startD;
									   while ($cur <= $endD) {
										   $allDates[] = $cur;
										   $cur = date("Y-m-d", strtotime("+1 day", strtotime($cur)));
									   }
									   $paginationTotal = count($allDates);
									   $pagedDates = array_slice($allDates, ($page - 1) * $pageSize, $pageSize);
									   foreach ($pagedDates as $cdate) {
										   echo printSingle2($cdate);
									   }
								   } else {
									   $k = 0;
									   $paginationTotal = printSingle($page, $pageSize);
								   }
								?>
								</tbody>
							</table>
						<?php
						$totalPages = (int)ceil($paginationTotal / $pageSize);
						if ($totalPages > 1): ?>
						<nav style="text-align:center;margin-top:10px;">
						  <ul class="pagination pagination-sm" style="display:inline-flex;">
							<?php if ($page > 1): ?>
							  <li><a href="#" onclick="return gotoPage(1)">&laquo;</a></li>
							  <li><a href="#" onclick="return gotoPage(<?=$page-1?>)">&lsaquo;</a></li>
							<?php endif; ?>
							<?php
							  $ps = max(1, $page - 4);
							  $pe = min($totalPages, $page + 4);
							  for ($pi = $ps; $pi <= $pe; $pi++): ?>
							  <li class="<?= $pi == $page ? 'active' : '' ?>">
								<a href="#" onclick="return gotoPage(<?=$pi?>)"><?=$pi?></a>
							  </li>
							<?php endfor; ?>
							<?php if ($page < $totalPages): ?>
							  <li><a href="#" onclick="return gotoPage(<?=$page+1?>)">&rsaquo;</a></li>
							  <li><a href="#" onclick="return gotoPage(<?=$totalPages?>)">&raquo;</a></li>
							<?php endif; ?>
						  </ul>
						</nav>
						<?php endif; ?>
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
            
            pt.initReservationDetail()
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
				stateSave: false,
				paging: false,
				ordering: true
			});
			$('tr[data-href]').on("click", function() {
				//document.location = $(this).data('href');
				 window.open($(this).data("href"), $(this).data("target"));
			});
			 
			$(".dataTables_length").css({ "display" :"none" });
		})
		function gotoPage(p) {
			document.getElementById('pageInput').value = p;
			document.getElementById('frmName').submit();
			return false;
		}
	</script>
    </body>
</html>
