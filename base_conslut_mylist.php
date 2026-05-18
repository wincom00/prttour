<?php
    include "include/header.php";
	
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
   /* if (!hasMenuAccess($division, $pdx, $sub)) {
		$goUrl_1 = "index.php";
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
		exit;
    }
    */
	function printConsult(){
			
			global $dbConn,$cname,$division,$crev,$pdx,$sub,$seldate,$startDate,$endDate,$employeeName,$user_dbinfo;
			

			if ($seldate == '1') {
				if ($startDate) {
						$qrysdate = " && ((  start_date >= '$startDate' && start_date <= '$endDate' )) ";
						
				} 
			} else if ($seldate == '2') {
				if ($startDate) {
					    $startDate = "$startDate 00:00:00";
			            $endDate = "$endDate 23:23:59";
						$qrysdate = " && (( wdate >= '$startDate' && wdate <= '$endDate' )) ";
						
				} 
			}  else {
				
				$sdate = date("Y-m-d");
				$startDate = "$sdate 23:23:59";
				$edate = date("Y-m-d",strtotime("-30 day"));
				$endDate = "$edate 00:00:00";
				$qrysdate =" && (( wDate >= '$endDate' && wDate <= '$startDate' )) ";
			}

			if ($cname !="") {
			
		
			      $qrycname= " && (member_name like '"."%".$cname."%"."')";

	        }
			

			$qryemp = " && (register='{$user_dbinfo['userid']}' || t_memeber='{$user_dbinfo['userid']}')";

			//echo $qryemp;
			$qry1 = "select *
				from 
					consult_info
				where 1=1 $qrysdate ".$qryemp." group by member_name,member_phone,member_email order by wdate,consultCode desc
					   
					  ";
			$rst1 = mysql_query($qry1,$dbConn);
			//echo $qry1;
			$k=0;
			while($row1 = mysql_Fetch_assoc($rst1)){
				
				$uinfo=getinfo_dbMember($row1['register']);
				if ($row1['t_memeber'] !="") {
					$uinfo1=getinfo_dbMember($row1['t_memeber']);
				} else {
					$uinfo1['kor_name'] = "";

				}
				
				$linkR="base_conslut_m.php?consultCode=".$row1['consultCode']."&division=$division&pdx=$pdx&sub=$sub&pcode={$row1['p_code']}&no={$row1['seq_no']}";
				echo "<tr>
						<td align='center'><a href='$linkR'>".$row1['consultCode']."</a></td>
						<td align='center'>{$row1['wdate']}</td>
						<td><a href='$linkR' >".$row1['p_name']."</a></td>
						<td align='center'><a href='$linkR'>{$row1['member_name']}</a></td>
						<td align='center'><a href='$linkR'>{$row1['member_phone']}</a></td>
						
						<td align='center'>{$row1['p_cnt']}</td>
						<td align='right'><a href='$linkR'>{$row1['start_date']}</a></td>
						
						<td align='center'>{$uinfo['kor_name']}</td>
						<td align='center'>{$uinfo1['kor_name']}</td>
						
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
					<li><a href="#">예약관리</a></li>
					<li>예약상담관리</li>
					<li>예약상담현황</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action=""  method="post" name="frmName">
						<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                <tr>
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="seldate">
                                            <option <?php if (($seldate == "2")) { ?> selected <?php } ?> value="2" >등록일</option>
                                            <option <?php if ($seldate == "1") { ?> selected <?php } ?> value="1">출발일</option>
                                        </select>
                                    </td>
                                    <td colspan="5">
                                        <div class="row">
                                            <div class="col-sm-5">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="startDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate1" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$startDate?>'>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="endDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate2" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$endDate?>'>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
									<td colspan="2" class="text-center formHeader">
                                        <input type="text" id="cname" name="cname" placeholder="고객명" class="inpubase md" value="<?=$cname?>"/>
                                    </td>
                                    
                                    <td colspan="3" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                                </tr>
                            </tbody>
                        </table>
					</form>
					<br />
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed js-productTable2">
								<thead>
									<tr>
										<th>상담코드</th>
										<th>등록일</th>
										<th>상품명</th>
										<th>예약자</th>
										<th>예약자전화번호</th>
										<th>인원</th>
										<th>출발일</th>
										
										<th>최초등록직원</th>
										<th>전달직원</th>
										
									</tr>
								</thead>
								<tbody>
									<?=printConsult()?>
								</tbody>
							</table>
						</div>
					</div>
					<br/>
					
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

			pt.initReservationList()
			var dateToday = new Date()
			$('.tourDate1').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
				
			});
			$('.tourDate2').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
			});

			$('.js-productTable2').DataTable( {
				 dom: 'Bfrtip',
				buttons: [
						'copy', 'csv', 'excel', 'print'
					 ],
				"order": [[ 1, "desc" ]]
			} );


			$(".dataTables_length").css({ "display" :"none" });
		})
		
	</script>
    </body>
</html>
