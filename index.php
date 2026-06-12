<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		
		exit;
	}
	$mm = date("m");
	$ym = date("Y-m");
	$ymd = date("Y-m-d");
	$qry0 = "select sum(last_total) scnt from reserve_info where revDate = '$ymd' && parent='MAIN' && base_rate='USD' && rev_status !='CANCEL'";
    $rst0 = mysql_query($qry0,$dbConn);
	$row0 = mysql_Fetch_assoc($rst0);

	$qry1 = "select sum(last_total) scnt from reserve_info where substr(revDate,1,7) ='$ym' && parent='MAIN' && base_rate='USD' && rev_status !='CANCEL' ";
    $rst1 = mysql_query($qry1,$dbConn);
	$row1 = mysql_Fetch_assoc($rst1);
	
	$qry00 = "select sum(p_cnt) cnt from reserve_info where revDate = '$ymd' && parent='MAIN' && rev_status !='CANCEL' ";
    $rst00 = mysql_query($qry00,$dbConn);
	$row00 = mysql_Fetch_assoc($rst00);

	$qry11 = "select count(*) cnt from reserve_info where substr(revDate,1,7) ='$ym'&& parent='MAIN' && rev_status !='CANCEL'";;
    $rst11 = mysql_query($qry11,$dbConn);
	$row11 = mysql_Fetch_assoc($rst11);


	$qry2 = "select count(*) cnt from reserve_info where substr(revDate,1,7) ='$ym'&& parent='MAIN' && rev_status !='CANCEL'";
    $rst2 = mysql_query($qry2,$dbConn);
	$row2 = mysql_Fetch_assoc($rst2);

	

	$qry5 = "select count(*) cnt from reserve_info where YEARWEEK(revDate) = YEARWEEK(now()) && parent='MAIN' && rev_status !='CANCEL' ";
    $rst5 = mysql_query($qry5,$dbConn);
	$row5 = mysql_Fetch_assoc($rst5);

	$qry6 = "select sum(p_cnt) cnt from reserve_info where substr(revDate,1,7) ='$ym' && parent='MAIN' && rev_status !='CANCEL' ";
    $rst6 = mysql_query($qry6,$dbConn);
	$row6 = mysql_Fetch_assoc($rst6);

	$qry7 = "select sum(p_cnt) cnt from reserve_info where YEARWEEK(revDate) = YEARWEEK(now()) && parent='MAIN' && rev_status !='CANCEL' ";
    $rst7 = mysql_query($qry7,$dbConn);
	$row7 = mysql_Fetch_assoc($rst7);
	
	function printBoard($table_id){
		
		global $dbConn,$division,$pdx,$sub;

		$qry1 = "select * from paran_board where tablename = '$table_id' order by seq_no desc limit 10";
		$rst1 = mysql_query($qry1,$dbConn);


		$num1 = 0;

		while($row1 = mysql_fetch_assoc($rst1)){
			if ($table_id == '25') {
				$url ='board_view.php?division=8&pdx=1&sub=25&table_id='.$table_id.'&';
			}
			if ($table_id == '15') {
				$url ='board_view.php?division=8&pdx=1&sub=15&table_id='.$table_id.'&';
			}
			if ($table_id == '10') {
				$url ='board_view.php?division=8&pdx=1&sub=10&table_id='.$table_id.'&';
			} 
			if ($table_id == '01') {
				$url ='board_view.php?division=8&pdx=1&sub=10&table_id='.$table_id.'&';
			}
			if ($table_id == '35') {
				$url ='board_view.php?division=8&pdx=1&sub=35&table_id='.$table_id.'&';;
			}
			if ($table_id == '80') {
				$url ='board_view.php?division=8&pdx=1&sub=35&table_id='.$table_id.'&';;
			}
			$today = explode(" ",$row1['wdate']);
			$today2 = explode("-",$today[0]);

			$yesterday1 = date("Y-m-d H:i:s",time()-86400);
			if($row1['wdate'] > $yesterday1)
			{
			$new_icon = "<img src='img/New2.gif'>";
			}
			else
			{
			$new_icon = "&nbsp;";
			}

			$title = Misc::cutLongString($row1['title'], 25, $dot=true);

			$content .= "<table class='table table-borderless index_table_fixed'>
					<tr bgcolor=#FFFFFF>
						<td  height=22 width=80%><a href=".$url."no={$row1['seq_no']}&start=0&board_mode=view>$title</a> $new_icon</td>
						<td align=right width=20%><span class=stxt>$today2[1].$today2[2]</span></td>
					</tr>
					</table>
					
			";

			$num1++;

		}

		echo $content;
	}


	$ymdate = date("Y-m");
	$qrymem = "select userid,kor_name from  member_list where division='admin' && out_yn is null || out_yn='n";
	$qry100 ="SELECT a.base_rate, SUM(a.p_cnt) cnt,SUM(last_total) totamt,COUNT(a.reserveCode) pcnt ,b.kor_name,b.c_part1 FROM reserve_info a ,member_list b ,product_master c WHERE a.userid=b.userid  && a.p_code= c.p_code && c.p_type =5  && (a.rev_status ='DONE' ||a.rev_status ='ORDER') && a.parent='MAIN' && a.tour_type='3'
	&& DATE_FORMAT(a.stDate, '%Y-%m')= '$ymdate'  GROUP BY a.userid";
	$rst100 = mysql_query($qry100);
	//  echo $qry1
   
	while($row = mysql_fetch_assoc($rst100)) 
	{
			
			$events[] = [
			'label' =>$row['kor_name'],
			
			'y' => $row['pcnt']
		
			 ];
			


    }

	
	//$qry101 = "select userid,kor_name from  member_list where division='admin' && out_yn is null || out_yn='n";
	$qry101 ="SELECT a.base_rate, SUM(a.p_cnt) cnt,SUM(last_total) totamt,COUNT(a.reserveCode) pcnt ,b.kor_name,b.c_part1 FROM reserve_info a ,member_list b ,product_master c WHERE a.userid=b.userid  && a.p_code= c.p_code && c.p_type !=5  && (a.rev_status ='DONE' ||a.rev_status ='ORDER') && a.parent='MAIN' && a.tour_type='1'
	&& DATE_FORMAT(a.stDate, '%Y-%m')= '$ymdate'  GROUP BY a.userid";
	$rst101 = mysql_query($qry101);
	  echo $qry101;
    
	while($row = mysql_fetch_assoc($rst101)) 
	{
			
			$events2[] = [
			'label' =>$row['kor_name'],
			'y' => $row['pcnt']
		
			 ];
			


    }
?>

    <style>
		.index_div1 {/*border-top:1px solid #999;*/border-bottom:1px solid #999;padding:7px 7px 7px 0px;}
		.index_padding {padding-top:10px;}
		.index_margin-top {margin-top:10px;}
		.index_margin-bottom {margin-bottom:0px !important;}
		.index-border-bottom {border-bottom:1px solid #999;}
		.autocut{text-overflow:ellipsis;overflow:hidden;}
		.index_table_fixed {table-layout:fixed;white-space:nowrap;}
		.index_scroll {height: 200px;overflow-y: scroll;}
		.index_table_color {color:#eee;}
		.index_table_color a {text-decoration:none ; color:#eee;}
		.autocut a {text-decoration:none ; color:#333;}
		.index-margint-top {margin-top:0px !important;}
	</style>

<div id="contentwrapper" class="js-mainPage">
	<div class="main_content">
	   <div id="jCrumbs" class="breadCrumb module">
			<ul>
				<li>
					<a href="#"><i class="glyphicon glyphicon-home"></i></a>
				</li>
			</ul>
	   </div>
	   <!-- 바로가기 -->	 
	   <div class="row">
	       <div class="col-sm-12">
		       <h4 class="heading"><strong>바로가기</strong></h4>
		   </div>
	    </div>
		<div class="row index-margint-top">
			<div class="col-sm-12">
				<ul class="dshb_icoNav clearfix">
					<li><a href="base_reservation.php?division=3&pdx=2&sub=10&ty=1" style="background-image: url(img/gCons/bookmark.png)">예약등록</a></li>
					<li><a href="base_reservation_mylist.php?division=3&pdx=4&sub=10" style="background-image: url(img/gCons/addressbook.png)">MY 예약현황</a></li>
					
					<li><a href="employee_cal_mylist.php?division=6&pdx=3&sub=15" style="background-image: url(img/gCons/dollar.png)">MY 수금현황</a></li>
				</ul>
			</div>
		</div>
        <!-- 예약현황 -->	
		<div class="row">
	       <div class="col-sm-12">
				<h4 class="heading"><strong>예약현황</strong></h4>
		    </div>
	    </div>
		<div class="row index-margint-top">
			<div class="col-sm-12">
				<div class="col-sm-12 tac">
					<div class="col-sm-4 index_div1">
						<table class="table table-borderless index_margin-bottom">
							<tr>
								<td class="text-left index-border-bottom index_table_color" width="100%" bgcolor="#2e6da4" ><strong>Daily Sale<strong></td>
							</tr>
							<tr>
								<td width="100%" style="padding-top:10px;">
								    <table class="table table-borderless index_margin-bottom">
										<tr>
											<td><i class="fa fa-dollar fa-2x"></i></td>
											<td class="text-left" width="29%"><?php echo  number_format($row0['scnt'],2) ?></</td>
											<td><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row11['cnt'];?></td>
											<td><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row00['cnt'];?></td>
										</tr>
									</table>
								</td>
							</tr>
							
						</table>
					</div>
					<div class="col-sm-4 index_div1">
						<table class="table table-borderless index_margin-bottom">
							<tr>
								<td class="text-left index-border-bottom index_table_color" width="100%" bgcolor="#2e6da4"><strong>Monthly Sale<strong></td>
							</tr>
							<tr>
								<td width="100%" style="padding-top:10px;">
								    <table class="table table-borderless index_margin-bottom">
										<tr>
											<td><i class="fa fa-dollar fa-2x"></i></td>
											<td class="text-left" width="29%"><?php echo  number_format($row1['scnt'],2) ?></</td>
											<td><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row2['cnt'];?></td>
											<td><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row6['cnt'];?></td>
										</tr>
									</table>
								</td>
							</tr>
							
						</table>
					</div>
					<div class="col-sm-4 index_div1">
						<table class="table table-borderless index_margin-bottom">
							<tr>
								<td class="text-left index-border-bottom index_table_color" width="100%" bgcolor="#2e6da4"><strong>Weekly Sale<strong></td>
							</tr>
							<tr>
								<td width="100%" style="padding-top:10px;">
								    <table class="table table-borderless index_margin-bottom">
										<tr>
											<td><i class="fa fa-dollar fa-2x"></i></td>
											<td class="text-left" width="35%"><?php echo  number_format($row11['scnt'],2) ?></td>
											<td><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row5['cnt'];?></td>
											<td><i class="fas fa-male fa-2x"></i></td>
											<td><?php echo $row7['cnt'];?></td>
										</tr>
									</table>
								</td>
							</tr>
							
						</table>
					</div>
				</div>
				<br />
				<br />
				<br />
				<br />
				<div class="col-sm-12 tac index_margin-top">
					<table class="table table-borderless index_margin-bottom">
						<tr>
							<td width="100%" style="padding-top:50px;">
							  
							</td>
						</tr>
						
					</table>
				</div>
			</div>
				<!--<div class="col-sm-12 tac index_margin-top">
					<table class="table table-borderless index_margin-bottom">
						<tr>
							<td width="100%" style="padding-top:10px;">
							    <!-- 예약현황 - bar chart 
								<div id="bar-container" style="min-width: 310px; height: 270px; max-width: 600px; margin: 0 auto"></div>
							</td>
						</tr>
						
					</table>
				</div>
			</div>-->
			<!-- 예약현황 - pie chart 
			<div class="col-sm-4">
				<div id="pie-container" style="min-width: 310px; height: 250px; max-width: 600px; margin: 0 auto"></div>
			</div>-->
		</div>
    <!--    <div class="col-sm-12">git
		  <div class="col-sm-4">
					<div id="chartContainer" style="height: 370px; width: 100%;"></div>
		  </div>
		   <div class="col-sm-8">
					<div id="chartContainer2" style="height: 370px; width: 100%;"></div>
		  </div>
		<div>
		<!-- 게시판 -->
		<div class="row">
	       <div class="col-sm-12">
				<h4 class="heading"><strong>게시판</strong></h4>
		    </div>
	    </div>
		<div class="row index-margint-top">
			<div class="col-sm-12">
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#2e6da4"><strong>단체문의<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#2e6da4"><strong><a href="board_list.php?division=8&pdx=3&sub=10&table_id=35">더보기</a><strong></td>
						</tr>
						<?php printBoard('35'); ?>
					</table>
				</div>
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#2e6da4"><strong>신상품게시판<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#2e6da4"><strong><a href="board_list.php?division=8&pdx=1&sub=28&table_id=80">더보기</a><strong></td>
						</tr>
						<?php printBoard('80'); ?>
					</table>
				</div>
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#2e6da4"><strong>사내공지<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#2e6da4"><strong><a href="board_list.php?division=8&pdx=1&sub=15&table_id=15">더보기</a><strong></td>
						</tr>
						<?php printBoard('15'); ?>
						
					</table>
				</div>
				<div class="col-sm-3 index_scroll">
					<table class="table table-borderless">
						<tr>
							<td class="text-left index-border-bottom index_table_color"  bgcolor="#2e6da4"><strong>문의게시판<strong></td>
							<td class="text-right index-border-bottom index_table_color" bgcolor="#2e6da4"><strong><a href="board_list.php?division=8&pdx=1&sub=10&table_id=01">더보기</a><strong></td>
						</tr>
						<?php printBoard('01'); ?>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

	    <?php
			include "include/side_m.php"
		?>
		
	<script src="https://cdnjs.cloudflare.com/ajax/libs/canvasjs/1.7.0/canvasjs.min.js" integrity="sha512-FJ2OYvUIXUqCcPf1stu+oTBlhn54W0UisZB/TNrZaVMHHhYvLBV9jMbvJYtvDe5x/WVaoXZ6KB+Uqe5hT2vlyA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/11.4.3/highcharts.js" integrity="sha512-qCaTHDKX58QLNYgW+wHYhMDNak+/fN7qg1ZNMsbmDhyOnceqaOOtPCLIELLpdRdvIngZZPw1rmrtmc9EFfJLOQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

		<!-- dashboard functions -->
		<!-- <script src="js/pages/gebo_dashboard.js"></script> -->
		<script>
			
	window.onload = function () {
 
		var chart = new CanvasJS.Chart("chartContainer", {
			animationEnabled: true,
			theme: "light",
			title:{
				text: "현재월 실적(협력사)"
			},
			axisY:{
				includeZero: true
			},
			legend:{
				cursor: "pointer",
				verticalAlign: "center",
				horizontalAlign: "right",
				itemclick: toggleDataSeries
			},
			data: [{
				type: "column",
				name: "",
				indexLabel: "{y}",
				yValueFormatString: "",
				showInLegend: true,
				dataPoints: <?php echo json_encode($events, JSON_NUMERIC_CHECK); ?>
			}]
		});
		chart.render();
		var chart2 = new CanvasJS.Chart("chartContainer2", {
			animationEnabled: true,
			theme: "light",
			title:{
				text: "현재월 실적(직접예약)"
			},
			axisY:{
				includeZero: true
			},
			legend:{
				cursor: "pointer",
				verticalAlign: "center",
				horizontalAlign: "right",
				itemclick: toggleDataSeries
			},
			data: [{
				type: "column",
				name: "",
				indexLabel: "{y}",
				yValueFormatString: "",
				showInLegend: true,
				dataPoints: <?php echo json_encode($events2, JSON_NUMERIC_CHECK); ?>
			}]
		});
		chart2.render();
		function toggleDataSeries(e){
			if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
				e.dataSeries.visible = false;
			}
			else{
				e.dataSeries.visible = true;
			}
			chart.render();
			chart2.render();
		}
	 
	}
</script>

    </body>
</html>
