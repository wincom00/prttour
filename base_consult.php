
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
	function printProduct($basedate = '') {
		global $dbConn, $division, $pdx, $sub,$st, $user_dbinfo,$tourCategory,$pname,$area1,$area2,$startDate1,$endDate,$ty,$grestimateCode,$estimateCode,$cp;

	   //echo $tourCategory;
		if ($pname) {
			$keywords = explode(' ', $pname);
			$likes = array();
			foreach($keywords as $kw) {
				  $kw = mysql_real_escape_string($kw);
				  $likes[] = "p_name LIKE '%{$kw}%'";
			}
		   // $qrynm = "&& a.p_name like '%$pname%'";
		   $qrynm =" && " .implode(" AND ", $likes)."";
		  // exit;
	    } else {
			$qrynm = "";
		}
		if ($tourCategory != "") {
			$qrytca = " && a.p_type='$tourCategory'";

		} else {
			$qrytca = "";
		}
		
		if ($area1 != "") {
			$qrycod1 = " && a.c_code1 = '$area1'";
		} else {
			$qrycod1 = "";
		}
		if ($area2 != "") {
			$qrycod2 = " && a.c_code2 = '$area2'";
		} else {
			$qrycod2 = "";
		}

		$qry1 = "select p_limitdate from product_limit where p_code = '$pcode' && p_type='L'";
		$rst1 = mysql_query($qry1);
		$rowcnt = mysql_num_rows($rst1);
		$LimitdatePrint = "";
		$s = 0;
		while($row1 = mysql_fetch_assoc($rst1)){

			if($s == $rowcnt-1)
			{
				$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
			}
			else
			{
				$LimitdatePrint .= "\"".$row1['p_limitdate']."\",";
			}
				
			$s++;
					
		}
		$st = date("Y-m-d",strtotime("now"));
        //echo $st."<br/>".$startDate1;
		if ($st != $startDate1) {
		    $startDate3 = substr($startDate1, 0, 7);
			$endDate3 = substr($endDate, 0, 7);
			//$qryperiod = " && ((DATE_FORMAT(a.p_vstart,'%Y-%m') <= '$startDate3' && a.p_vend >= '$endDate' )) ";
			//$qryperiod =" && (DATE_FORMAT(a.p_vend,'%Y-%m')  between '$startDate3' and '$endDate3'
			 //|| DATE_FORMAT(a.p_vstart,'%Y-%m')  between '$startDate3' and '$endDate3')";
			 $qryperiod ="&& DATE_FORMAT(a.p_vend,'%Y-%m') >= '$startDate3' and DATE_FORMAT(a.p_vstart,'%Y-%m') <= '$endDate3'";
		} else {
			$startDate3 = substr($startDate1, 0, 7);
			$endDate3 = substr($endDate, 0, 7);
			//$qryperiod = " && ((DATE_FORMAT(a.p_vstart,'%Y-%m') <= '$startDate3' && a.p_vend >= '$endDate' )) ";
			//$qryperiod =" && DATE_FORMAT(a.p_vend,'%Y-%m')  between '$startDate3' and '$endDate3'
			 //|| DATE_FORMAT(a.p_vstart,'%Y-%m')  between '$startDate3' and '$endDate3'";

			 //$qryperiod =" && ('$startDate3' between DATE_FORMAT(a.p_vstart,'%Y-%m') and DATE_FORMAT(a.p_vend,'%Y-%m')
			 //|| '$endDate3' between DATE_FORMAT(a.p_vstart,'%Y-%m') and DATE_FORMAT(a.p_vend,'%Y-%m'))";
			//$qryperiod = " && (a.p_vend <= '$endDate') ";
			// $qryperiod =" && ('$startDate3' between DATE_FORMAT(a.p_vstart,'%Y-%m') and DATE_FORMAT(a.p_vend,'%Y-%m')
			// || '$endDate3' between DATE_FORMAT(a.p_vstart,'%Y-%m') and DATE_FORMAT(a.p_vend,'%Y-%m'))";

			 $qryperiod2 =" && ('$startDate3' between DATE_FORMAT(a.p_vstart,'%Y-%m') and DATE_FORMAT(a.p_vend,'%Y-%m')
			<= '$endDate3' between DATE_FORMAT(a.p_vstart,'%Y-%m') and DATE_FORMAT(a.p_vend,'%Y-%m'))";

			$qryperiod2 ="&& DATE_FORMAT(a.p_vend,'%Y-%m') >= '$startDate3' and DATE_FORMAT(a.p_vstart,'%Y-%m') <= '$endDate3'";
		}
		$qryperiod1 = "&& (( b.p_limitdate >= '$startDate1' && b.p_limitdate <= '$endDate' ))";
		$weektot = array("0", "1", "2","3","4","5","6","9"); 
		$weeknum= ($weektot[date('w', strtotime($startDate1))]);


	   //$startDate = "9" - 매일출발;
	     //$startWeek_qry = "&&  ((a.p_week like '%$weeknum%' || a.p_week like '%9%'))";

		if (($user_dbinfo['dept_prior'] == "J") || ($user_dbinfo['dept_prior'] == "")) {
		    $deptqry = " && ((a.m_dept like '%{$user_dbinfo['area_comp']}%') || (a.p_dept like '%{$user_dbinfo['area_comp']}%'))";
		} else {
		    $deptqry = "";
		}
	
		$qry1 = "select a.p_code, a.p_name,a.c_code1,a.c_code2,a.p_type,a.p_own,a.p_day,a.price_0dadult,a.price_4dadult,a.p_week 
		from product_master a where 1=1 && a.p_code not REGEXP 'ADD' && a.p_vend != '0000-00-00' $qrynm $qrytca $qrycod1 $qrycod2 $qryperiod $startWeek_qry $deptqry group by a.p_code 
		union
		select a.p_code, a.p_name,a.c_code1,a.c_code2,a.p_type,a.p_own,a.p_day,a.price_0dadult,a.price_4dadult,a.p_week 
		from product_master a ,product_limit b
		 where 1=1 && a.p_code=b.p_code && b.p_type='R' && a.p_code not REGEXP 'ADD' $qrytca $qrynm $qrycod1 $qrycod2 $qryperiod1 $qryperiod2 $startWeek_qry $deptqry
		 group by a.p_code ";
		$rst1 = mysql_query($qry1,$dbConn);
		//echo $qry1."<br />";
		while($row1 = mysql_Fetch_assoc($rst1)){
			$cinfo1=codebaseName($row1['c_code1']);
			$cinfo2=codebaseName($row1['c_code2']);
			if ($row1['p_type'] == 1) {
			   $pcap = "로컬상품";
			} else if ($row1['p_type'] == 2) {
				$pcap = "인바운드";
			} else if ($row1['p_type'] == 4) {
				$pcap = "인센티브";
			} else if ($row1['p_type'] == 5) {
				$pcap = "아웃바운드";
			}
			if ($row1['p_own'] == "purun") {
				$randrow['kor_name'] = "푸른투어";
			} else {
				$randrow = randname($row1['p_own']);
			}
			if ($row1['p_day']==1) {
				$day = "당일";
				$dprice = $row1['price_0dadult'];
			} else {
				$dprice = $row1['price_2dadult'];
				$day = $row1['p_day'];
			}
			$week1 = array("0", "1", "2","3","4","5","6","9");
			$week2   = array("일","월", "화", "수","목","금","토","매일");

			
			
					
					$list .= " <tr>
								<td align='center'>$pcap</td>
								<td align='center'>{$cinfo1['comment']}/{$cinfo2['comment']}</td>
								<td align='left'>{$row1['p_code']}</td>
								<td align='left'> {$row1['p_name']}</td>
								<td align='center'>{$randrow['kor_name']} </td>
								
								<td align='center'>
									<a  href='base_conslut_m.php?division=$division&pdx=$pdx&sub=$sub&pcode={$row1['p_code']}&st=$startDate1' class='btn btn-md btn-default btnchk'>선택</a>
									
								</td>
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
					<li><a href="#">예약상담관리</a></li>
					<li>예약상담등록</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="<?= $PHP_SELF ?>?grestimateCode=<?=$grestimateCode?>&estimateCode=<?= $estimateCode ?>&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>&cp=<?=$cp?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post" onSubmit='return chksave()'>
						<input type="hidden" name="mode" value="search">
						<table class="table table-bordered table-condensed">
						    
							<tr>
								<td width="10%" class="titletd text-center">투어분류</td>
								<td width="40%" class="">
									<select class="form-control" name="tourCategory">
										<option value="" >- 선택 -</option>
										<option value="1" <?php if ($tourCategory==1) { ?> selected <?php } ?> >로컬상품</option>
										<option value="2" <?php if ($tourCategory==2) { ?> selected <?php } ?>>인바운드</option>
										
										<option value="4" <?php if ($tourCategory==4) { ?> selected <?php } ?>>인센티브</option>
										<option value="5" <?php if ($tourCategory==5) { ?> selected <?php } ?>>아웃바운드</option>
									</select>
								</td>
								<td width="10%" class="titletd text-center">지역분류</td>
								<td width="40%" class="">
									<div class="row">
										<div class="col-sm-6">
											<select class="form-control fst1" name="area1" id="area1">
												<option value="">- 선택 -
												<?=printBaseCode_first('T01',$area1)?>
											</select>
										</div>
										<div class="col-sm-6">
											<select class="form-control fst2" name="area2" id="area2">
												<option value="">- 선택 - 
												<?=printBaseCode_second('T01','',$area2)?>
											</select>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">상품명</td>
								<td width="40%" class=""><input type="text" id="pname" name="pname" class="inpubase" value="<?=$pname?>"/></td>
								<td width="10%" class="titletd text-center">출발일</td>
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
								    <?php
									$divide_date = explode("-",$startDate1);
									$startDateStr =  date("D", mktime(0, 0, 0, $divide_date[1], $divide_date[2], $divide_date[0]));
									echo "";
									?>
									<tr>
										<th>투어분류</th>
										<th>지역분류</th>
										<th>상품코드</th>
										<th>상품명</th>
										<th>소유사</th>
										
										<th>예약구분</th>
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
					startDate: dateToday
			    });
				$('.tourDate2').datepicker({
					format: "yyyy-mm-dd",
					autoclose: true,
					startDate: dateToday
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
