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

	if ($startDate1 == "") {
		$startDate1 =  date("Y-m-d",strtotime("-7days"));
		$endDate1 = date("Y-m-d",strtotime("+1 month"));
	}

    function printSingle(){
    
        global $dbConn,$division,$crev,$pdx,$sub,$startDate1,$endDate1,$h_code,$total_amt,$total_payment,$cal_sum,$tot_pcnt;

		global $dbConn,$division,$crev,$pdx,$sub,$startDate1,$endDate1,$guideid;

        if ($startDate1) {
            $from_w = " AND a.stDate >= '$startDate1' ";
        }
        if ($endDate1) {
            $to_w = " AND a.stDate <= '$endDate1' ";
        }

		$query = "SELECT distinct a.* FROM (
		SELECT a.seq_no,a.grand_eCode,a.sub_eCode,a.stDate,a.guide_id,a.p_code,a.p_name
		FROM  tour_master b,tour_guide a  
		WHERE a.grand_eCode = b.grand_eCode AND a.p_code = b.p_code 
		AND b.p_code not like 'ADD%'
		$from_w $to_w ) a , product_master b  
		WHERE 1=1 && a.p_code = b.p_code ";
		$rst1 = mysql_query($query,$dbConn);
//echo $query;
		while($row1 = mysql_Fetch_assoc($rst1)){
			
			 //가이드 정산코드
			 $guide_code = getGuideCode($row1['grand_eCode'],$row1['sub_eCode']);
			 //echo $guide_code[settle_code]."TEST";
             //행사인원
			 $p_cnt = getReserveInfoCnt($row1['p_code'],$row1['stDate']);

			 //행사기간
			 $period = getPeriodbyrev($row1['p_code'],$row1['stDate']);
 
			 //행사코드
			 $grandCode = $row1['grand_eCode']." <br/><font color='red'>".$row1['sub_eCode'].'</font>';
 
			 //상태
			 $status = getGuideStatus($row1['grand_eCode'],$row1['sub_eCode']);
			 //가이드정보
			 $korname = getinfo_dbMember($row1['guide_id']);
			 echo "<tr>
			     <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number={$row1['seq_no']}&scode={$guide_code['settle_code']}'>{$guide_code['settle_code']}</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number={$row1['seq_no']}&scode={$guide_code['settle_code']}'>$grandCode</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number={$row1['seq_no']}&scode={$guide_code['settle_code']}'>{$row1['stDate']}</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number={$row1['seq_no']}&scode={$guide_code['settle_code']}'>{$row1['p_name']}</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number={$row1['seq_no']}&scode={$guide_code['settle_code']}'>$period</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number={$row1['seq_no']}&scode={$guide_code['settle_code']}'>{$p_cnt['cnt']}</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number={$row1['seq_no']}&scode={$guide_code['settle_code']}'>{$korname['kor_name']}</a></td>
				 <td align='center'><a href='guide_cal_m.php?division=6&pdx=2&sub=10&number={$row1['seq_no']}&scode={$guide_code['settle_code']}'>$status</a></td>
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
					<li><a href="#">정산관리</a></li>
					<li>가이드정산등록</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="" name="frmName"  method="post">
						<input type="hidden" name="mode" value="search">
						<table class="table table-bordered table-condensed">
							<tr>
								<td width="10%" class="titletd text-center">행사일기준</td>
								<td width="40%" class="">
									<div class="row">
										<div class="col-sm-3">
                                            <div class="input-group input-group-sm">
                                                <input type="date" class="form-control" id="startDate1" name="startDate1" max="2999-12-31" placeholder="From" value="<?=$startDate1?>" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="input-group input-group-sm">
                                                <input type="date" class="form-control" id="endDate1" name="endDate1" max="2999-12-31" placeholder="to" value="<?=$endDate1?>" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <button type='submit' class="btn btn-primary btn-sm btn1">검색</button>    
                                        </div>
									</div>
								</td>
							</tr>
						</table>
					</form>
					<br />
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed js-productTable">
								<thead>
									<tr>
									    <th>가이드정산코드</th>
										<th>행사코드</th>
										<th>행사일</th>
										<th>행사명</th>
										<th>행사기간</th>
										<th>행사인원</th>
										<th>가이드명</th>
										<th>상태</th>
									</tr>
								</thead>
								<tbody>
									<?php  echo printSingle(); ?>
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
            
            var dateToday = new Date()
			
			var oTable = $('#ctable').dataTable({
				stateSave: true,
				pageLength: 100,
				"order": [[ 1, "asc" ]]
			});
	
			$(".dataTables_length").css({ "display" :"none" });

		})
        
	</script>
    </body>
</html>
