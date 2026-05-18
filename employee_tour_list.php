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

	function printemp() {
        global $dbConn,$startDate,$endDate,$term1,$currency;

		//조회기간
		if ($term1 == 'bookday') {
			if ($startDate) {
				$from_w = " && a.revDate >= '$startDate' ";
				
			}
			if ($endDate) {
				$to_w = " AND a.revDate <= '$endDate' ";
			}

		} else {
			if ($startDate) {
				$from_w = " && a.stDate >= '$startDate' ";
				
			}
			if ($endDate) {
				$to_w = " AND a.stDate <= '$endDate' ";
			}
		}

		if ($currency == 'CAD') {
			$curqry = "&& a.base_rate = 'CAD'";

		} else {
			$curqry = "&& a.base_rate = 'USD'";
		}
		if ($startDate) {
			$query = "SELECT a.base_rate, SUM(a.p_cnt) cnt,SUM(last_total) totamt,COUNT(a.reserveCode) totcnt ,b.kor_name,b.c_part1 FROM reserve_info a ,member_list b ,product_master c WHERE a.userid=b.userid  && a.p_code= c.p_code && c.p_type !=5  && (a.rev_status ='DONE' ||a.rev_status ='ORDER') && a.parent='MAIN' && a.tour_type='1'
		    $from_w $to_w $curqry GROUP BY a.userid";
			//echo $query;
			$rst1 = mysql_query($query,$dbConn);
			$k=1;$total_amt=0;$tot_pcnt=0;$totcnt=0;

			while($row1 = mysql_Fetch_assoc($rst1)){
                   $codenm=codebaseName($row1['c_part1']);
				    $tot_pcnt = $tot_pcnt + $row1['cnt'];
                   $total_amt = $total_amt + $row1['totamt'];
				   $tot_cnt = $tot_cnt + $row1['totcnt'];
                   echo "<tr>
							<td align='center'>$k</td>
							<td align='center'>{$codenm['comment']}</td>
							<td align='center'>{$row1['kor_name']}</td>
							<td align='center'>{$row1['cnt']}</td>
							<td align='center'>{$row1['totcnt']}</td>
							<td align='right'>".number_format($row1['totamt'],2)."&nbsp;</td>
						</tr>";

                $k++;
			}
			
           echo "<tr>
					<td align='center'></td>
					<td align='center'></td>
					<td align='center'>합계</td>
					<td align='center'>$tot_pcnt</td>
					<td align='center'>".number_format($tot_cnt,2)."&nbsp;</td>
					<td align='right'>".number_format($total_amt,2)."&nbsp;</td>
				</tr>";
           

		}

      


	}

	function printemp1() {
        global $dbConn,$startDate,$endDate,$term1,$currency;

		//조회기간
		if ($term1 == 'bookday') {
			if ($startDate) {
				$from_w = " && a.revDate >= '$startDate' ";
				
			}
			if ($endDate) {
				$to_w = " AND a.revDate <= '$endDate' ";
			}

		} else {
			if ($startDate) {
				$from_w = " && a.stDate >= '$startDate' ";
				
			}
			if ($endDate) {
				$to_w = " AND a.stDate <= '$endDate' ";
			}
		}

		
		if ($startDate) {
			$query = "SELECT a.base_rate, SUM(a.p_cnt) cnt,SUM(last_total) totamt,COUNT(a.reserveCode) totcnt ,b.kor_name,b.c_part1 FROM reserve_info a ,member_list b, product_master c WHERE a.userid=b.userid  && a.p_code= c.p_code && c.p_type !=5 && (a.rev_status ='DONE' ||a.rev_status ='ORDER') && a.parent='MAIN' && a.tour_type='3'
		    $from_w $to_w  GROUP BY a.userid";
		//echo $query;
			$rst1 = mysql_query($query,$dbConn);
			$k=1;$total_amt=0;$tot_pcnt=0;$totcnt=0;

			while($row1 = mysql_Fetch_assoc($rst1)){
                   $codenm=codebaseName($row1['c_part1']);
				   $tot_pcnt = $tot_pcnt + $row1['cnt'];
                   $total_amt = $total_amt + $row1['totamt'];
				   $tot_cnt = $tot_cnt + $row1['totcnt'];
                   echo "<tr>
							<td align='center'>$k</td>
							<td align='center'>{$codenm['comment']}</td>
							<td align='center'>{$row1['kor_name']}</td>
							<td align='center'>{$row1['cnt']}</td>
							<td align='center'>{$row1['totcnt']}</td>
							<td align='right'>".number_format($row1['totamt'],2)."&nbsp;</td>
						</tr>";

				$k++;
			}
			
           echo "<tr>
					<td align='center'></td>
					<td align='center'></td>
					<td align='center'>합계</td>
					<td align='center'>$tot_pcnt</td>
					<td align='center'>".number_format($tot_cnt,2)."&nbsp;</td>
					<td align='right'>".number_format($total_amt,2)."&nbsp;</td>
				</tr>";
           

		}




	}   
	function printemp2() {
        global $dbConn,$startDate,$endDate,$term1,$currency;

		//조회기간
		if ($term1 == 'bookday') {
			if ($startDate) {
				$from_w = " && a.revDate >= '$startDate' ";
				
			}
			if ($endDate) {
				$to_w = " AND a.revDate <= '$endDate' ";
			}

		} else {
			if ($startDate) {
				$from_w = " && a.stDate >= '$startDate' ";
				
			}
			if ($endDate) {
				$to_w = " AND a.stDate <= '$endDate' ";
			}
		}

		if ($startDate) {
			$query = "SELECT a.base_rate, SUM(p_cnt) cnt,SUM(last_total) totamt,COUNT(a.reserveCode) totcnt ,a.userid FROM reserve_info a  WHERE  (a.rev_status ='DONE' ||a.rev_status ='ORDER') && a.parent='MAIN' && a.tour_type='2'
		    $from_w $to_w ";
		//echo $query;
			$rst1 = mysql_query($query,$dbConn);
			$k=1;$total_amt=0;$tot_pcnt=0;$totcnt=0;

			while($row1 = mysql_Fetch_assoc($rst1)){
                   $codenm=codebaseName($row1['c_part1']);
				   $tot_pcnt = $tot_pcnt + $row1['cnt'];
                   $total_amt = $total_amt + $row1['totamt'];
				   $tot_cnt = $tot_cnt + $row1['totcnt'];
                   echo "<tr>
							
							<td align='center'>웹예약</td>
							
							<td align='center'>{$row1['cnt']}</td>
							<td align='center'>{$row1['totcnt']}</td>
							<td align='right'>".number_format($row1['totamt'],2)."&nbsp;</td>
						</tr>";

				$k++;
			}
			
           echo "<tr>
					
					<td align='center'>합계</td>
					<td align='center'>$tot_pcnt</td>
					<td align='center'>".number_format($tot_cnt,2)."&nbsp;</td>
					<td align='right'>".number_format($total_amt,2)."&nbsp;</td>
				</tr>";
           

		}




	}
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin">정산관리<i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">자금현황</a></li>
					<li>직원별투어예약현황</li>
				</ul>
			</div>
			
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="" name="frmName" method="post">
						<input type="hidden" name="mode" value="search">
						<table class="table table-bordered table-condensed">
							<tr>
								<td width="10%" class="titletd text-center">기간설정</td>
								<td width="40%" class="">
									<div class="row">
                                        <div class="col-sm-2">
                                            <div class="input-group input-group-sm">
                                                <input type="date" name="startDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates js-tourStartDate" aria-label="조회기간" placeholder="조회기간" value="<?php echo $_POST['startDate'];?>">
                                                
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="input-group input-group-sm">
                                                <input type="date" name="endDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates js-tourEndDate" aria-label="조회기간" placeholder="조회기간" value="<?php echo $_POST['endDate'];?>">
                                               
                                            </div>
                                        </div>
                                    </div>
								</td>
							</tr>
							
							<tr>
								<td width="10%" class="titletd text-center">통계기준</td>
								<td width="40%" class="no-right-border">
									<div class="row no-nav">
                                        <div class="col-sm-12">
                                            <label class="radio-inline">
                                                <input type="radio" name="term1" value="bookday" <?php echo ($term1=='bookday')?'checked':'';?>> 예약일 기준
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="term1" value="startday" <?php echo ($term1=='startday')?'checked':'';?>> 출발일 기준
                                            </label>
                                        </div>
                                    </div>
								</td>
                            </tr>	
                          <!--  <tr>
								<td width="10%" class="titletd text-center">통화기준</td>
								<td width="40%" class="no-right-border">
									<div class="row no-nav">
                                        <div class="col-sm-12">
                                            <label class="radio-inline">
                                                <input type="radio" name="currency" value="CAD" <?php echo ($currency=='CAD')?'checked':'';?>>CAD
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" name="currency" value="USD" <?php echo ($currency=='USD')?'checked':'';?>>USD
                                            </label>
                                        </div>
                                    </div>
								</td>
                            </tr>-->						
							<tr>
								<td colspan="2" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
							</tr>
						</table>
					</form>

					<br />
					<div class="row">
						<div class="col-sm-12">
							<h4><b> 직접예약 </b></h4>
							<table id="ctable" class="table table-striped table-bordered table-hover table-condensed js-productTable1">
								<thead>
									<tr>
										<th rowspan="2">NO</th>
                                        <th colspan="2">상담원</th>
										<th colspan="1" id="header_text1">예약인원</th>
										<th colspan="1" id="header_text5">예약건수</th>
										<th colspan="1" id="header_text3">매출액</th>

                                    </tr>
                                    <tr>		
										<th>소속</th>
										<th>성명</th>
										<th id="header_text6"></th>
										<th id="header_text2"></th>
										<th id="header_text4"></th>
									</tr>
								</thead>
								<tbody>
									
									<?php printemp();?>
								</tbody>
							</table>
						</div>
					</div>

						<br />
					<div class="row">
						<div class="col-sm-12">
						<h4><b> 협력사 </b></h4>
							<table id="ctable" class="table table-striped table-bordered table-hover table-condensed js-productTable1">
								<thead>
									<tr>
										<th width='4%' rowspan="2">NO</th>
                                        <th width='25%' colspan="2">상담원</th>
										<th colspan="1" id="header_text7">예약인원</th>
										<th colspan="1" id="header_text8">예약건수</th>
										<th colspan="1" id="header_text9">매출액</th>

                                    </tr>
                                    <tr>		
										<th>소속</th>
										<th width='8%'>성명</th>
										<th id="header_text10"></th>
										<th id="header_text11"></th>
										<th id="header_text12"></th>
									</tr>
								</thead>
								<tbody>
									
									<?php printemp1();?>
								</tbody>
							</table>
						</div>
					</div>
					<br />
					<div class="row">
						<div class="col-sm-12">
						<h4><b> 웹예약 </b></h4>
							<table id="ctable" class="table table-striped table-bordered table-hover table-condensed js-productTable1">
								<thead>
									<tr>
										
                                        <th width='5%' colspan="1"></th>
										<th colspan="1" id="header_text7">예약인원</th>
										<th colspan="1" id="header_text8">예약건수</th>
										<th colspan="1" id="header_text9">매출액</th>

                                    </tr>
                                    
								</thead>
								<tbody>
									
									<?php printemp2();?>
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
           
			$(".dataTables_length").css({ "display" :"none" });
            
            var startD = $(".js-tourStartDate[name=startDate]").val();
            var endD = $(".js-tourEndDate[name=endDate]").val();
            
           
            
            $("#header_text2").text(startD+"~"+endD);
			$("#header_text4").text(startD+"~"+endD);
			$("#header_text6").text(startD+"~"+endD);
            
			$("#header_text10").text(startD+"~"+endD);
			$("#header_text11").text(startD+"~"+endD);
			$("#header_text12").text(startD+"~"+endD);
		})
	</script>
    </body>
</html>
