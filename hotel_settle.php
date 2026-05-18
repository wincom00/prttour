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
/*
    if ($startDate1 == "") {
		$startDate1 =  date("Y-m-d",strtotime("-7days"));
		$endDate1 = date("Y-m-d",strtotime("+1 month"));

	}

*/	
	function printSingle(){
    
        global $dbConn,$division,$crev,$pdx,$sub,$startDate1,$endDate1,$guideid;

        if ($startDate1) {
            $from_w = " AND a.stDate >= '$startDate1' ";
        }
        if ($endDate1) {
            $to_w = " AND a.stDate <= '$endDate1' ";
        }

        if($guideid) {
            $guide_w = " AND a.guide_id = '$guideid' ";
        }

        $query = "SELECT a.* FROM (
         SELECT a.seq_no,a.grand_eCode,a.sub_eCode,a.stDate,a.guide_id,a.p_code,a.p_name,c.kor_name
         FROM tour_guide a, tour_master b,member_list c
        WHERE a.grand_eCode = b.grand_eCode AND a.p_code = b.p_code AND c.userid = a.guide_id 
		AND c.division ='guide'
        AND b.p_code not like 'ADD%'
        $from_w $to_w $guide_w )a LEFT JOIN product_master b ON a.p_code = b.p_code
        WHERE 1=1 && b.p_day > 1";

		//echo $query;
		$rst1 = mysql_query($query,$dbConn);
        while($row1 = mysql_Fetch_assoc($rst1)){

            //행사인원
            $p_cnt = getReserveInfoCnt($row1['p_code'],$row1['stDate']);

            //행사기간
            $period = getPeriodbyhotel($row1['p_code'],$row1['stDate']);

            //행사코드
            $grandCode = $row1['grand_eCode']." <br/><font color='red'>".$row1['sub_eCode'].'</font>';

            //상태
            $status = getHotelStStatus($row1['grand_eCode'],$row1['sub_eCode'],$row1['stDate']);

            echo "<tr>
                <td align='center'><a href='hotel_cal2.php?division=6&pdx=1&sub=15&number={$row1['seq_no']}'>$grandCode</a></td>
                <td align='center'><a href='hotel_cal2.php?division=6&pdx=1&sub=15&number={$row1['seq_no']}'>{$row1['stDate']}</a></td>
                <td align='center'><a href='hotel_cal2.php?division=6&pdx=1&sub=15&number={$row1['seq_no']}'>{$row1['p_name']}</a></td>
                <td align='center'><a href='hotel_cal2.php?division=6&pdx=1&sub=15&number={$row1['seq_no']}'>$period</a></td>
                <td align='center'><a href='hotel_cal2.php?division=6&pdx=1&sub=15&number={$row1['seq_no']}'>{$p_cnt['cnt']}</a></td>
                <td align='center'><a href='hotel_cal2.php?division=6&pdx=1&sub=15&number={$row1['seq_no']}'>{$row1['kor_name']}</a></td>
                <td align='center'><a href='hotel_cal2.php?division=6&pdx=1&sub=15&number={$row1['seq_no']}'>$status</a></td>
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
					<li>호텔별정산</li>
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
                                            <select class="form-control" name="guideid" id="guideid">
                                                <option value=''>- 선택 -</option>
                                                <?php 
                                                  $query ="SELECT userid,kor_name FROM member_list WHERE division ='guide' ";
                                                  $rst1 = mysql_query($query,$dbConn);
                                                  while($row1 = mysql_Fetch_assoc($rst1)){

                                                ?>
                                                <option value="<?=$row1['userid']?>" <?php if($guideid == $row1['userid']) echo 'selected'; ?>><?=$row1['kor_name']?></option>

                                                <?php }?>

                                            </select>
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
							<table name="ctable" id="ctable"  class="table table-striped table-bordered table-hover table-condensed js-productTable">
								<thead>
									<tr>
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
