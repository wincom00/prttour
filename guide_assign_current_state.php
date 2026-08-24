<?php
    include "include/header.php";
	
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

    if($StartYMD)
	{
		$start_date = $StartYMD." "."00:00:01";
		$stop_date = $EndYMD." "."23:59:59";	
	}
	else
	{
		$year = date("Y");
		$month = date("m");
		$day = date("d");

		$StartYMD = date("Y-m-d",mktime(0,0,0,$month,$day -4,$year));
		$EndYMD = date("Y-m-d",mktime(0,0,0,$month,$day +60,$year));

	}
	//$StartYMD = "2019-09-01";
	//$EndYMD = "2019-09-21";
	// 1주일간 뽑아내기
    $cur_day = date("w",mktime(0,0,0,$month,$day,$year));
    $now = date("Y-m-d",mktime(0,0,0,$month,$day,$year));
    $minus_day = 14 - $cur_day;
    $week_first = date("m / d / Y",mktime(0,0,0,$month,$day - $cur_day,$year));
    $week_last = date("m / d / Y",mktime(0,0,0,$month,$day + $minus_day,$year));

	
	// [튜닝] 날짜 수 계산을 DB 숫자생성 쿼리 대신 PHP로 처리
	$totalDay = (int)floor((strtotime($EndYMD) - strtotime($StartYMD)) / 86400) + 1;
	if ($totalDay < 0) $totalDay = 0;

	$total_td = 150 + (($totalDay+1) * 60);

   
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/dataTables.fixedHeader.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/dataTables.fixedColumns.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/css/dataTables.bootstrap4.min.css">
<style>
    /*div.dt-buttons {
        float: right;
        padding-bottom: 10px;
    }*/

    .tableFixHead {
        overflow: auto; /*  x, y축 모두 auto로 설정 */
        height: 600px;
    }

    .tableFixHead thead th {
        position: sticky;
        top: 0;
        background: #eee;
        border: 0.05em solid #848484;
        z-index: 1; /* 헤더 z-index */
    }
     /* 가이드 컬럼 고정 */
    .tableFixHead tbody td:first-child,
    .tableFixHead thead th:first-child
     {
        position: sticky;
        left: 0;
        background-color: #f0f0f0;
        z-index: 2; /* 가이드 컬럼 z-index (헤더보다 높게) */
    }
     /* 헤더의 첫 번째 셀 (가이드) */
     .tableFixHead thead th:first-child{
        z-index:3; /* 최상위 z-index */
     }

    table.dataTable thead tr th,
    table.dataTable thead td,
    table.dataTable tbody tr td {
        border-bottom: 1px solid #111;
        padding: 1px 1px;
        box-sizing: border-box; /* padding, border가 width/height에 포함되도록 */
    }
    /* DataTables 1.13+ 에서는 아래 설정이 필요할 수 있습니다. */
    table.dataTable.fixedHeader-floating,
    table.dataTable.fixedHeader-locked {
      z-index: 99999 !important;
    }


    div.dataTables_wrapper {
        margin: 0 auto;
    }
	.guide-tour-day {
		background-color: #ffcccc;
		vertical-align: top;
	}
	.guide-tour-item {
		display: block;
		background: #fff;
		border: 1px solid #e58c8c;
		border-radius: 3px;
		color: #064780;
		font-size: 8pt;
		line-height: 1.25;
		margin: 2px;
		padding: 2px 4px;
		text-align: left;
		white-space: normal;
		word-break: keep-all;
	}
    /* 필요하다면 아래처럼 최소/최대 너비를 설정해 줄 수 있습니다.
    td, th {
      min-width: 50px;  가장 좁은 셀의 너비
      max-width: 200px;  넓은 셀의 최대 너비
    }
    */
</style>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">행사배정관리</a></li>
					<li>가이드배정현황</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="" name="frmName" method="post">
						<input type="hidden" name="mode" value="search">
						<table class="table table-bordered table-condensed">
							<tr>
							    <td width="10%" class="titletd text-center">출발일</td>
								<td width="40%" class="">
									<div class="row">
                                        <div class="col-sm-12">
                                            <div class="input-group input-group-sm">
                                                <div class="row">
										<div class="col-sm-6">
											<input type="text" id="startDate1" name="StartYMD" class="inpubase tourDate1" placeholder="시작일" value="<?= $StartYMD ?>" autocomplete=off />
										</div>
										<div class="col-sm-6">
											<input type="text" id="endDate" name="EndYMD" class="inpubase tourDate1" placeholder="마지막일" autocomplete=off value="<?= $EndYMD ?>"/>
										</div>
									</div>
                                            </div>
                                        </div>
                                    </div>
								</td>
								<?php if ($user_dbinfo['division'] != "guide") { ?>
								<td width="10%" class="titletd text-center">가이드</td>
								<td width="40%" class="no-right-border">
									<select class="form-control" name="guideSelect">
										<option value="" selected>- 가이드선택 -</option>
										<?=printGuideSelect($guideSelect)?>
									</select>
								</td>
								<?php } else  {?>
								<td width="10%" class="titletd text-center">가이드</td>
								<td width="40%" class="no-right-border">
									<?=$user_dbinfo['kor_name']?>
									<input type="hidden" id="guideSelect" name="guideSelect"  value="<?=$user_dbinfo['userid']?>"/>
								</td>
								<?php } ?>
							</tr>
									
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색(반드시 2개월 이상 조회하세요!!!)</button></td>
							</tr>
						</table>
					</form>
					<br />
					<div class="row" style="overflow:scroll;">
						<div class="col-sm-12 tableFixHead" >
						    <table id="guide_table" class="table table-striped table-bordered text-center ">
                                <thead>
                                    <tr>
                                        
                                        <?php
												
												$sDate = $StartYMD;
												echo "<th style='position: sticky;margin:0;border:0.05em solid #848484;'  align=center>가 이 드</th>";
												for($i=0; $i<$totalDay; $i++)
												{

													$sDate2 = explode("-",$sDate);

													$pdate  = date("Y-m-d",mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));

													$month  = date("m",mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));	
													$day  = date("d",mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));	

													$yoil = date("w", mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));
													
													$week = array("일", "월", "화", "수", "목", "금", "토");

													$yoil = $week[date("w", mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]))];


													if($yoil == "일")
													{
														$day = "<font style='font-size:7pt;color:red'>$month/$day<br>($yoil)</font>";
													} else if($yoil == "토")
													{
														$day = "<font style='font-size:7pt;color:blue'>$month/$day<br>($yoil)</font>";
													}
													else
													{
														$day = "<font style='font-size:7pt'>$month/$day<br>($yoil)</font>";
													}
													if ($pdate == $now) {
														echo "<th style='border:0.05em solid #848484;background-color:#DDA0DD;' align=center>$day</th>";

													} else {
													    echo "<th style='border:0.05em solid #848484;' align=center>$day</th>";
													}
												}


											?>
											
                                    </tr>
                                </thead>
                                <tbody>
 <?php

		$vDate = $StartYMD;
		if ($guideSelect != "") {
		    $guideqry = " && a.guide_id = '$guideSelect'";
		}

		$zip_qry1 = "select a.*,b.kor_name from tour_guide a, member_list b ,tour_car c 
											where a.guide_id=b.userid && division='guide'
											&& a.p_code=c.p_code && a.stDate=c.stDate && a.grand_eCode =c.grand_eCode
										    && a.sub_eCode = c.sub_eCode	&& a.stDate between '$StartYMD' and '$EndYMD'
											&& exists (
												select 1 from reserve_info ri
												where ri.reserveCode = c.reserveCode
												&& ri.p_code = c.p_code
												&& ri.stDate = c.stDate
												&& ri.rev_status != 'CANCEL'
											)
											$guideqry
											group by a.guide_id";
		
		/*$zip_qry1 = "select a.*,b.kor_name from tour_guide a, member_list b 
											where a.guide_id=b.userid && division='guide' 
											&& a.stDate between '2019-09-01' and '2019-09-20' 
											group by a.guide_id";*/
		//echo $totalDay ;
		///echo $zip_qry1;
		$zip_rst1 = mysql_query($zip_qry1,$dbConn);

		// --- Helper Function (Highly Recommended) ---
	// --- Helper Function (Highly Recommended) ---
	function getTourCellContent($row, $guideId) {
		if (!$row) {
			return "<td style='border:1px dotted black;' align=center'>&nbsp;<br /></td>";
		}

		$pCode = $row['p_code'];
		$productInfo = getProductMaster($pCode);
		$addDate = isset($productInfo['p_day']) ? $productInfo['p_day'] : 1;  // PHP 5.5 compatible

		$link = sprintf(
			"<a href=\"javascript:openwin('%s','%s','%s','%s')\">%s</a>",
			htmlspecialchars($row['grand_eCode']),
			htmlspecialchars($row['p_code']),
			htmlspecialchars($row['sub_eCode']),
			htmlspecialchars($guideId),
			htmlspecialchars($row['p_name']) //use p_name from the query result
		);

		return "<td colspan='{$addDate}' style='border:1px dotted black;' align=center bgcolor=#FFFF99'>{$link}</td>";
	}


	// --- Main Logic ---

	// [튜닝] 가이드×날짜별 반복 쿼리(가이드수×기간일수 회) → 기간 전체 1회 조회 후 맵 사용
	$guideqry_a = "";
	if ($guideSelect != "") {
		$guideSelect_escaped = mysql_real_escape_string($guideSelect, $dbConn);
		$guideqry_a = " AND a.guide_id = '$guideSelect_escaped'";
	}
	$tourMap = array(); // [guide_id][stDate] = 해당일 투어 행 배열
	$batch_qry = "SELECT a.*, p.p_name, p.p_day,
		  (
			  SELECT COUNT(DISTINCT c2.seq_no)
			  FROM tour_car c2, reserve_info ri2
			  WHERE c2.grand_eCode = a.grand_eCode
			  AND c2.sub_eCode = a.sub_eCode
			  AND c2.p_code = a.p_code
			  AND c2.stDate = a.stDate
			  AND ri2.reserveCode = c2.reserveCode
			  AND ri2.p_code = c2.p_code
			  AND ri2.stDate = c2.stDate
			  AND ri2.rev_status != 'CANCEL'
		  ) as pax_cnt
		  FROM tour_guide a
		  LEFT JOIN product_master p ON a.p_code = p.p_code
		  WHERE a.stDate BETWEEN '$StartYMD' AND '$EndYMD'
		  $guideqry_a
		  AND EXISTS (
			  SELECT 1
			  FROM tour_car c, reserve_info ri
			  WHERE c.grand_eCode = a.grand_eCode
			  AND c.sub_eCode = a.sub_eCode
			  AND c.p_code = a.p_code
			  AND c.stDate = a.stDate
			  AND ri.reserveCode = c.reserveCode
			  AND ri.p_code = c.p_code
			  AND ri.stDate = c.stDate
			  AND ri.rev_status != 'CANCEL'
		  )
		  ORDER BY a.guide_id ASC, a.stDate ASC, a.bus_num ASC, a.p_code ASC";
	$batch_rst = mysql_query($batch_qry, $dbConn);
	if ($batch_rst) {
		while ($trow = mysql_fetch_assoc($batch_rst)) {
			$tourMap[$trow['guide_id']][$trow['stDate']][] = $trow;
		}
	}

	while ($zip_row1 = mysql_fetch_assoc($zip_rst1)) {
    $guideId = $zip_row1['guide_id'];
    $guideName = htmlspecialchars($zip_row1['kor_name']);
    $content = "";

    for ($k = 0; $k < $totalDay; $k++) {
        $vDate2 = explode("-", $vDate);
        $choose_date = date("Y-m-d", mktime(0, 0, 0, $vDate2[1], $vDate2[2] + $k, $vDate2[0]));

        // [튜닝] 일자별 개별 쿼리 제거 — 위에서 일괄 조회한 맵 사용
        $dayTours = isset($tourMap[$guideId][$choose_date]) ? $tourMap[$guideId][$choose_date] : array();

        $tourLinks = "";
		$maxTourDay = 1;
		$i = 1;
        foreach ($dayTours as $tour) {
			$tourName = ($tour['p_name'] != "") ? $tour['p_name'] : $tour['p_code'];
			$tourTitle = ($tour['p_name'] != "") ? strip_tags($tourName, '<br><b><strong><i><em><u><span><font>') : htmlspecialchars($tourName);
			$paxCnt = isset($tour['pax_cnt']) ? intval($tour['pax_cnt']) : 0;
			$paxText = ($paxCnt > 0) ? " (".$paxCnt."명)" : "";
			$tourDay = isset($tour['p_day']) ? intval($tour['p_day']) : 1;
			if ($tourDay < 1) {
				$tourDay = 1;
			}
			if ($tourDay > $maxTourDay) {
				$maxTourDay = $tourDay;
			}
			$tourLinks .= sprintf(
				"<a class=\"guide-tour-item\" href=\"javascript:openwin('%s','%s','%s','%s')\">%s</a>",
				htmlspecialchars($tour['grand_eCode']),
				htmlspecialchars($tour['p_code']),
				htmlspecialchars($tour['sub_eCode']),
				htmlspecialchars($guideId),
				$i.". ".$tourTitle.$paxText
			);
			$i++;
		}

        if ($tourLinks != "") {
            $remainDay = $totalDay - $k;
			if ($maxTourDay > $remainDay) {
				$maxTourDay = $remainDay;
			}
            $content .= "<td colspan='{$maxTourDay}' class='guide-tour-day' style='border:1px dotted black;' align=center'>";
            $content .= $tourLinks;
            $content .= "</td>";
			$k += ($maxTourDay - 1);
        } else {
            $content .= "<td style='border:1px dotted black;' align=center'>&nbsp;<br /></td>"; // Empty cell
        }
    }

    echo "<tr>
            <td align=left style='border:0.05em solid #848484;'><font style='font-size:8pt'>&nbsp;{$guideName}({$guideId})</font></td>
            {$content}
          </tr>";
    unset($content);
}

?>
		


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
            pt.initReservationList()
            
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
            var hh  =window.innerHeight-150;
			
            //var args = {paging:false, ordering:false, info:false,scrollX:true,scrollY: 200};
            /* var table = $('#guide_table').DataTable({
                    scrollY:        hh+"px",
					scrollX:        true,
					scrollCollapse: true,
					paging:         false,
                    bSort : false,
					fixedColumns:   {
						leftColumns: 1
					
					}
			  
			   
			 }); */
			 var table = $('#guide_table').DataTable({
                    scrollY:        600,
					scrollX:        true,
					scrollCollapse: true,
					paging:         false,
					fixedColumns: {
					  leftColumns: 1
					},
					sort : false,
					autoWidth: false,
					columnDefs: [
						{ width: 150, targets: 0 }
					],
					fixedHeader: {
						header: true
					},
					
					fixedColumns: true
			   
			 });
			
		});
		var ctr=0;
        function openwin(grand_eCode,p_code,s_code,gid) { 
	       var winName = "all_"+(ctr++);
		   window.open("guide_assign_customer1.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&g_code="+grand_eCode+"&s_code="+s_code+"&p_code="+p_code+"&gid="+gid,winName,"width=1050,height=700,scrollbars=1");
	    }
	</script>
    </body>
</html>
