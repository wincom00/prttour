
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
		
		$endDate = date("Y-m-d",strtotime("+3 month"));
		
	}
	function printProduct() {
		global $dbConn, $division, $pdx, $sub,$st, $user_dbinfo,$tnum,$pnum,$rand_id_air,$startDate1,$endDate;

		// 누산 변수 초기화
		$acc_tot = 0; $acc_tot3 = 0; $acc_doc = 0; $acc_com = 0;
		$acc_rand = 0; $tot_airamt = 0; $list = '';

		// 조건 쿼리 초기화
		$pnrnum_qry = ''; $comp_qry = ''; $tk_qry = '';

		if($startDate1)
		{
			$StartYMD = "$startDate1 01:01:01";
			$EndYMD = "$endDate 23:23:59";


		}
		else
		{
			$StartYMD = date("Y-m-d",mktime (0,0,0,date("m")  , date("d")-7, date("Y")));
			$EndYMD =  date("Y-m-d");


			$startDate1 = date("Y-m-d",mktime (0,0,0,date("m")  , date("d")-7, date("Y")));
			$endDate = date('Y-m-d');


		}
		if($pnum)
		{

			$pnrnum_qry = "&& b.a_pnr_number1 like '%$pnum%'";

		}

		if($rand_id_air)
		{

			$comp_qry = "&& c.userid  = '$rand_id_air'";

		}
	    if ($tnum) {
			$tk_qry = "&& b.a_tk_number like '%$tnum%'";
		}
		$qry1 = "SELECT a.book_pri,a.p_name,a.userid,b.*
						FROM `reserve_info` a, `reserve_airline_pnr` b ,member_list c
						WHERE a.reserveCode = b.reserveCode && a.userid = c.userid && a.rev_status != 'CANCEL'  $comp_qry
						 && b.a_airline_print between '$StartYMD' and  '$EndYMD'
						  $tk_qry $pnrnum_qry order by b.a_airline_start ";
		$rst1 = mysql_query($qry1,$dbConn);
		if (!$rst1) return $list;
		$rrows=mysql_num_rows($rst1);
		
		while($result_rows = mysql_Fetch_assoc($rst1)){
					$amt1 = 0; $amt4 = 0; $arcamt = 0;
					if(($result_rows['a_settle_type'] == 1) && ($result_rows['a_fee'] != 0)){
				  		
				    	$arcamt = ($result_rows['a_fee'] - $result_rows['a_fee1']) * $result_rows['a_airport_cnt']; //ARC Settle
				  	    $amt4 = ($result_rows['a_rate'] + $result_rows['a_tax']) * $result_rows['a_airport_cnt']; //Doc Total
				  	    $amt1  = '0';
				    } else if(($result_rows['a_settle_type'] != 1) && ($result_rows['a_fee'] != 0)){  
				    	$amt4 = ($result_rows['a_rate'] + $result_rows['a_tax']) * $result_rows['a_airport_cnt']; //Doc Total
				    	$arcamt = $amt4;//ARC Settle
				  	    $amt1  = '0';
					 
				    	
				    } else if(($result_rows['a_settle_type'] == 1) && ($result_rows['a_fee'] == 0)){ 
				    	 $amt4 = ($result_rows['a_rate'] + $result_rows['a_tax']) * $result_rows['a_airport_cnt']; //Doc Total
				    	 $amt3 = $result_rows['a_airline_amt'];
				    	 $arcamt = $amt3-$amt4; //ARC Settle 
						
				    	 
				    } else if(($result_rows['a_settle_type'] != 1) && ($result_rows['a_fee'] == 0)){ 
				    	 
				    	$amt4 = ($result_rows['a_rate'] + $result_rows['a_tax']) * $result_rows['a_airport_cnt']; //Doc Total
				    	$arcamt =$amt4; //ARC Settle 
							
				    }
				  
					if($result_rows['a_settle_type'] == 1) {
				  	if ($gu != 1) {
					  	if ($arcamt < 0) {
					  	  $arcamt = -$arcamt ;
					    }	else {
					    	$arcamt = $arcamt ;
					    }
				    }
				  	$settlenm = "항공시스템";
					}
					if($result_rows['a_settle_type'] == 2) {
						$arcamt = -$arcamt ;
						$settlenm = "Cash&Check";
					}
					if($result_rows['a_settle_type'] == 3) {
						$arcamt = -$arcamt ;
						$settlenm = "지사단말기";
					}
					if($result_rows['a_settle_type'] == 4) {
						$arcamt = -$arcamt ;
						$settlenm = "웹결제";
					}
					  
				    $acc_tot = $acc_tot + $result_rows['a_airline_amt'];
		
					$acc_tot3 = $acc_tot3 + $arcamt;
					  
					$acc_doc = $acc_doc +$amt4;
					  
					$acc_com = $acc_com + $amt1;
				    $acc_rand = $acc_rand + $result_rows['rand_fee'];
					$tot_airamt = $tot_airamt + $result_rows['a_air_amt'];
					$mcnt = getMembercnt($result_rows['reserveCode']);
					$list .= " <tr>
								<td align='center'>{$result_rows['reserveCode']}</td>
								<td align='center'>{$result_rows['a_airline_start']}</td>
								<td align='left'>{$result_rows['book_pri']}($mcnt)</td>
								<td align='center'>$settlenm</td>
								<td align='right'>$".number_format($arcamt,2)."</td>
								<td align='right'>$".number_format($result_rows['a_air_amt'],2)."</td>
								<td align=right>$".number_format($amt4,2)."</td>
								<td align=right>$".number_format($amt1,2)."</td>
								<td align=right>$".number_format($result_rows['a_airline_amt'],2)."</td>			   
						
					  ";
			  
					
					$list .= "<td align=center>{$result_rows['userid']}</td>";
							   
					$list .= "</tr>";
					
				
		}
		$list .= "<tr>
		<td align=center></td>
		<td align=center></td>
		<td align=center></td>
		<td align=center>총합 : </td>
		<td align=right >$".number_format($acc_tot3,2)."</td>
		<td align=right>$".number_format($tot_airamt,2)."</td>
        <td align=right>$".number_format($acc_doc,2)."</td>
		<td align=right>$".number_format($acc_com,2)."</td>
		<td align=right>$".number_format($acc_tot,2)."</td>
		
		<td align=center></td>
		</tr>";
    
		if($rrows == "0")
		{
			$list .= "<tr bgcolor=#FFFFFF>
			<td colspan=10 height=30 align=center>정산 자료가 없습니다.</td>
			</tr>";
		}
		return $list;

	}
?>
	<div id="contentwrapper" class="productDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">예약관리</a></li>
					<li><a href="#">기타정산현황</a></li>
					<li>ARC 리포트</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post" onSubmit='return chksave()'>
						<input type="hidden" name="mode" value="search">
						<table class="table table-bordered table-condensed">
						    
							<tr>
								<td width="10%" class="titletd text-center">발권처</td>
								<td width="40%" class="">
									<select name=rand_id_air class=form-control >
									  <option value='' >선 택
										<?= printRandSelectAirlie($rand_id_air); ?>
								    </select>
								</td>
								<td width="10%" class="titletd text-center">TK #NUM</td>
								<td width="40%" class="">
									
										<input type="text" id="tnum" name="tnum" class="inpubase" value="<?=$tnum?>"/>
								
								</td>
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">PNR #NUM</td>
								<td width="40%" class=""><input type="text" id="pnum" name="pnum" class="inpubase" value="<?=$pnum?>"/></td>
								<td width="10%" class="titletd text-center">기준일자</td>
								<td width="40%" class="">
									<div class="row">
										<div class="col-sm-6">
											<input type="text" id="startDate1" name="startDate1" class="inpubase tourDate1" placeholder="시작일" value="<?=$startDate1?>" autocomplete=off />
										</div>
										<div class="col-sm-6">
											<input type="text" id="endDate" name="endDate" class="inpubase tourDate1" placeholder="마지막일" autocomplete=off value="<?=$endDate?>"/>
										</div>
									</div>
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
							<table class="table table-striped table-bordered table-hover table-condensed js-productTable2">
								<thead>
								    
									<tr>
										<th width=10% align=center>예약번호</th>
										<th width=13% align=center>출발일</th> 
										<th width=15% align=center>고객이름</th>
										<th width=9% align=center>결제수단</th>
										<th width=9% align=center>Net Remit</th>
										<th width=10% align=center>항공수익</th>
										<th width=9% align=center>DOC Total</th>
										<th width=9% align=center>ARC Com</th>
										<th width=10% align=center>항공결제금액</th>
										<th width=10% align=center>담당자</th>
									</tr>
								</thead>
								<tbody>
									<?php
									

									  
												 echo printProduct();
											
										
									
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
		       $.ajaxSetup({async:false});
				pt.initReservationList()
				pt1.initProductDetailForm2()
				var dateToday = new Date()
			    $('.tourDate1').datepicker({
					format: "yyyy-mm-dd",
					autoclose: true,
					
			    });
				$('.tourDate2').datepicker({
					format: "yyyy-mm-dd",
					autoclose: true,
					
			    });
				/*
				$( ".btnchk" ).click(function() {
					
					if ($("#startDate1").val() == "") {
						alert("출발일을 입력하세요!");
						$("#startDate1").focus();
						return false;
				    }
					

				});
				**/
				$('.js-productTable2').DataTable( {
					 dom: 'Bfrtip',
					buttons: [
							'copy', 'csv', 'excel', 'print'
						 ],
					"order": [[ 0, "desc" ]]
				} );
				$(".dataTables_length").css({ "display" :"none" });
				
		})
		function chksave() {
				
                  if ($("#startDate").val() == "") {
						alert("출발일을 입력하세요!");
						$("#startDate").focus();
						return false;
				  }
		}
	</script>
    </body>
</html>
