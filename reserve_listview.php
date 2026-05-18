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
    
	$lst = 2;

	
	if ($startDate1 == "") {
		$startDate1 =  date("Y-m-d",strtotime("now"));
		$endDate1 = date("Y-m-d",strtotime("+1 week"));

	}
	$summary_rows  = 0;
	$summary_pcnt  = 0;
	$summary_rcnt  = 0.0;
	$summary_resv  = 0;

	function printSingle(){

			global $dbConn,$division,$crev,$pdx,$sub,$productName,$area1,$area2,$startDate1,$endDate1,$k,$user_dbinfo,$deptarea;
			global $summary_rows,$summary_pcnt,$summary_rcnt,$summary_resv;
			

			if ($productName!="") {
					$qrynm = " && a.p_name like '%$productName%'";

			} else {
				    $qrynm ="";
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

			if ($area1) {
                $qryarea = " && b.c_code2 like '$area2%'";

			}
			if ($deptarea =="1") {
				$deptqry1 = "";
			} else if ($deptarea ==""){
				$deptqry1 = " && ((b.sc_grp= '{$user_dbinfo['sc_grp']}'))";
			} else {
				$deptqry1 = " && ((b.sc_grp= '$deptarea'))";
			}
			//&& a.p_code !='NJP1N'
			$qry1 = "select c.grand_eCode,a.p_code,b.p_name,a.stDate,b.c_code1,b.c_code2,b.p_own,
						b.p_day,b.p_cnt ,a.rev_status,c.tour_pcnt,a.revDate,sum(a.room_cnt) as rcnt from 
						product_master b, reserve_info a  left outer join tour_master c on a.p_code=c.p_code && a.stDate = c.stDate
						 where a.p_code = b.p_code && p_type in ('1','2','4') && b.m_type = 'S' && a.rev_status!='CANCEL' 
						  $qrynm $qrysdate $qryarea $deptqry1 group by a.p_code,a.stDate  order by a.stDate asc";
			$rst1 = mysql_query($qry1,$dbConn);
			//echo $qry1."<br/><br/>";
			//$k=0;
			$room= 0;
			while($row1 = mysql_Fetch_assoc($rst1)){
				$cinfo1=codebaseName($row1['c_code1']);
				$cinfo2=codebaseName($row1['c_code2']);
				if ($row1['rev_status']== 'READY') {
					$row1['rev_status'] = "<font color=red>예약접수</font>";
				}
				if ($row1['rev_status']== 'DONE') {
					$row1['rev_status'] = "<font color=red>예약확정</font>";
				}
                
				if ($row1['rev_status']== 'CANCEL') {
					$row1['rev_status'] = "<font color=red>예약취소</font>";
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
				$summary_rows++;
				$summary_pcnt += (int)$row1['p_cnt'];
				$summary_resv += (int)$pcnt['cnt'];
				$summary_rcnt += (float)$row1['rcnt'];
				echo "<tr class='arhef' data-href='event_reservation_detail.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}' data-target='_blank'>

							<td>{$cinfo2['comment']}</td>
							<td>{$row1['p_code']}</td>
							<td>{$row1['p_name']}<input type='hidden' name='pcode[$k]' value='{$row1['p_code']}'><input type='hidden' name='pname[$k]' value='{$row1['p_name']}'></td>
							<td align='center'>{$row1['stDate']} ($sweekday)<input type='hidden' name='sdate[$k]' id='sdate' value='{$row1['stDate']}'></td>
							<td align='center'><input type='hidden' name='acnt[$k]' value='{$row1['p_cnt']}'>{$row1['p_cnt']}</td>
							<td align='center'>{$pcnt['cnt']}</td>
							<td align='center'>{$row1['rcnt']} </td>
							<td align='center'>{$row1['rev_status']}</td>
							<td align='center'>{$row1['revDate']}</td>
							
						</tr>";
				$k++;

			
			}

	}
	
	
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">MIS</a></li>
					<li>예약현황</li>
					<li>기간별 예약현황</li>
				</ul>
			</div>
			<form id="frmName" name="frmName" method="post">
				<input type="hidden" name="mode" id="mode" value="search">
				<input type="hidden" name="productOwener1" id="productOwener1" value="<?=$productOwener?>">
			<div class="row">
				<div class="col-sm-12 col-md-12">
					
						
						<table class="table table-bordered table-condensed productDetailForm">
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
								<td width="10%" class="titletd text-center">상품지역</td>
								<td width="40%" class="">
								    <div class="row">
								       <div class="col-sm-6">
                                            <select class="form-control fst1" name="area1">
											<option value="" >- 싱품지역을 선택하세요.1 -</option>
											<?=printBaseCode_first('T01',$area1)?>
									</select>
										</div>
										<div class="col-sm-6">
										    <select class="form-control fst2" name="area2">
											<option value="" >- 싱품지역을 선택하세요.2 -</option>
											<?=printBaseCode_second('T01',$area1,$area2)?>
									</select>
										</div>
									</div>
									
								</td>
								<td width="10%" class="titletd text-center">지역선택</td>
								<td width="40%" class="">
								    
								       <select class="form-control" name="deptarea">
										<option value="1" selected>- 지역그룹선택 -</option>
										<?=printBaseCode_first('G03',$deptarea)?>
									   </select>
									</div>
									
								</td>
							</tr>
											
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
							</tr>
						</table>
					

					<br />
					<?php ob_start(); ?>
					<div class="row">
						<div class="col-sm-12">
							<table name="ctable" id="ctable"  class="table table-striped table-bordered table-hover table-condensed js-productTable1">
								<thead>
									<tr>
									    
										
										<th>상품지역분류</th>
										<th>상품코드</th>
										<th>상품명</th>
										<th>출발일</th>
										<th>정원</th>
										<th>예약</th>
										<th>방갯수</th>
										<th>예약상태</th>
										<th>접수일</th>
									</tr>
								</thead>
								<tbody>
								<?php 
								//echo $kindEvent;
								   if ($lst == 1) {
									   $k = 0;
									    if ($startDate1 == "") {
											
											for ($i = 0 ;$i < 7; $i++) {
												//echo printSingle2();
												$cdate = date("Y-m-d",strtotime("+$i day"));
												echo printSingle2($cdate);
											}

										} else {
											$date1 = $startDate1;
											$date2 = $endDate1;
											$new_date = date("Y-m-d", strtotime("-1 day", strtotime($date1)));
											while(true) {
												 $new_date = date("Y-m-d", strtotime("+1 day", strtotime($new_date)));
												 echo printSingle2($new_date);
												 if($new_date == $date2) break;
											}
										}
									    
								   } else {
									         $k = 0;
											 echo printSingle();
											
								   }
								?>
								</tbody>
							<tfoot>
								<tr style="background:#f5f5f5;font-weight:bold;">
									<td colspan="4" class="text-right">합&nbsp;&nbsp;계</td>
									<td class="text-center"><?=$summary_pcnt?></td>
									<td class="text-center"><?=$summary_resv?></td>
									<td class="text-center"><?= (float)$summary_rcnt ?></td>
									<td colspan="2"></td>
								</tr>
							</tfoot>
							</table>
						</div>

					</div>
				</div><!-- -->
				<?php
					$_tableHtml = ob_get_clean();
				?>
						<div class="row" style="margin-bottom:12px;">
							<div class="col-sm-12">
								<div style="display:flex;gap:10px;flex-wrap:wrap;">
									<div style="flex:1;min-width:140px;border:1px solid #ddd;border-radius:6px;padding:14px 18px;background:#fff;text-align:center;">
										<div style="font-size:12px;color:#888;margin-bottom:4px;">행사 수</div>
										<div style="font-size:22px;font-weight:bold;color:#337ab7;"><?=$summary_rows?></div>
									</div>
									<div style="flex:1;min-width:140px;border:1px solid #ddd;border-radius:6px;padding:14px 18px;background:#fff;text-align:center;">
										<div style="font-size:12px;color:#888;margin-bottom:4px;">총 정원</div>
										<div style="font-size:22px;font-weight:bold;color:#5cb85c;"><?=number_format($summary_pcnt)?></div>
									</div>
									<div style="flex:1;min-width:140px;border:1px solid #ddd;border-radius:6px;padding:14px 18px;background:#fff;text-align:center;">
										<div style="font-size:12px;color:#888;margin-bottom:4px;">총 예약인원</div>
										<div style="font-size:22px;font-weight:bold;color:#d9534f;"><?=number_format($summary_resv)?></div>
									</div>
									<div style="flex:1;min-width:140px;border:1px solid #ddd;border-radius:6px;padding:14px 18px;background:#fff;text-align:center;">
										<div style="font-size:12px;color:#888;margin-bottom:4px;">잔여 정원</div>
										<div style="font-size:22px;font-weight:bold;color:#f0ad4e;"><?=number_format(max(0,$summary_pcnt-$summary_resv))?></div>
									</div>
									<div style="flex:1;min-width:140px;border:1px solid #ddd;border-radius:6px;padding:14px 18px;background:#fff;text-align:center;">
										<div style="font-size:12px;color:#888;margin-bottom:4px;">총 방 수</div>
										<div style="font-size:22px;font-weight:bold;color:#9b59b6;"><?= (float)$summary_rcnt ?></div>
									</div>
									<?php if ($summary_pcnt > 0): ?>
									<div style="flex:1;min-width:140px;border:1px solid #ddd;border-radius:6px;padding:14px 18px;background:#fff;text-align:center;">
										<div style="font-size:12px;color:#888;margin-bottom:4px;">예약율</div>
										<div style="font-size:22px;font-weight:bold;color:#e67e22;"><?=round($summary_resv/$summary_pcnt*100,1)?>%</div>
									</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
				<?= $_tableHtml ?>
				
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
			pt.initProductDetailForm()
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
				stateSave: true,
				pageLength: 500,
				
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
