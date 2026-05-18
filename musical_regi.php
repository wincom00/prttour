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
	
	
    $pcap = "뮤지컬/스포츠예약현황";
	/////////////////////////////////////
	if(!$start)
	{
	    $start = 0;
	}

	$board_scale = 30;
	$board_page = 15;

	$scale=$board_scale;

	$page_scale=$board_page;



	if($StartYMD)
	{
		$start_date = $StartYMD." "."00:00:01";
		$stop_date = $EndYMD." "."23:59:59";	

		$orderdate_qry = "&& wdate between '$start_date' and '$stop_date'";
	}
	else
	{
		$StartYMD = date("Y-m-d");
		$EndYMD = date("Y-m-d",strtotime("+1 month"));

		$start_date = date('Y-m-d 00:00:01');
		$stop_date = date('Y-m-d 23:59:59',strtotime("+1 month"));
		
		$orderdate_qry = "&& wdate between '$start_date' and '$stop_date'";
	}

	if($reserve_name)
	{
		$reserve_name_qry = "&& member_name like '%$reserve_name%'";
	}
	if($crev)
	{
		$reserve_no_qry = "&& reserveCode like '%$crev%'";
	}
	if($reserve_phone)
	{
		$reserve_phone_qry = "&& member_phone like '%$reserve_phone%'";
	}

	if(($actEYMD) && ($actSYMD)) {

	    $actdate_qry = "&& act_date between '$actSYMD' and '$actEYMD'";
	} else {
	    $actdate_qry = "";

	}
	if($r_staus)
	{
		$status_qry = "&& status = '$r_staus'";
	}


	$que = "select *
				from 
					musical_self_info
				where 1=1 $reserve_name_qry $reserve_no_qry $reserve_phone_qry $status_qry $orderdate_qry $actdate_qry  order by wdate desc limit $start,$scale";


	//print_r($que);

	$page=floor($start/($scale*$page_scale));

	$result=mysql_query($que);
	$result_rows=mysql_num_rows($result);

	$total=mysql_affected_rows();
	$last=floor($total/$scale);


	$page_total_qry = mysql_query("select count(*) as cnt
													from 
													musical_self_info where 1=1 $reserve_name_qry $reserve_no_qry $reserve_phone_qry $status_qry $orderdate_qry $actdate_qry");
  

	$page_total = @mysql_result($page_total_qry,0,0);
	$page_last = floor($page_total/$scale);

	
	$total_page_num = ceil($page_total/$scale);

	$now_page_num = floor($start/$scale) + 1;
//echo $page_total;

	function contentPrint(){

		global $dbConn,$start,$total,$scale,$result,$code,$tableName,$Mode,$how,$S_date,$S_content, $page_total,$HTTP_COOKIE_VARS,$division;

		if($start)
		{
		   $n=$page_total-$start;
		}
		else
		{
		   $n=$page_total;
		}
		
        if($page_total != "0")
        {
				for($i=$start; $i<$start+$scale; $i++)
				{
						if($i<$page_total)
						{
								$row1=mysql_fetch_assoc($result);

								$img_url = _WEB_BASE_DIR;

								if($n%2 == "0")
									{
										$bgcolor = "#FFFFFF";
									}
								else
									{
										$bgcolor = "#F9F9F9";
									}
								
								$wdate = explode(" ",$row1['wdate']);
						
								switch($row1['status'])
								{
									case "READY":
										$status = "구입/견적완료";
										break;
									case "ORDER":
										$status = "발주완료";
										break;
									case "ORDER_CANCEL":
										$status = "<font color=red>구입취소</font>";
										break;
									case "CANCEL":
										$status = "<font color=red>견적취소</font>";
										break;	
								}
								$totmem =$totmem+$row1['member_adult'];
								$totamt =$totamt+$row1['total_amt'];
								echo "<tr bgcolor=#FFFFFF>
								  <td align=center>{$row1['member_adult']}</td>
									<td align=center height=25><b><a href=musical_regi_m.php?division=3&pdx=2&sub=30&reserveCode={$row1['reserveCode']}><u>{$row1['reserveCode']}</u></a></b></td>
									<td align=center>{$row1['member_name']}</td>
									<td align=center><b>{$row1['h_code']}</b></td>
									<td>&nbsp;{$row1['h_name']}</td>
									<td align=center>{$row1['act_date']}<br>{$row1['act_time']}</td>
									<td align=right>${$row1['total_amt']}&nbsp;</td>
									<td align=center>$wdate[0]</td>
									<td align=center>$status</td>
								</tr>";

											
								echo $table_content;


							}
				  $n--;
				}
				echo "<tr bgcolor=#FFFFFF>
					  <td align=center> <font color=red>$totmem 명</font></td>
						<td align=center height=25><b></b></td>
						<td align=center></td>
						<td align=center></td>
						<td></td>
						<td align=center></td>
						<td align=right><font color=red>$".number_format($totamt,2)."&nbsp;</font></td>
						<td align=center></td>
						<td align=center></td>
					</tr>";

        }
        else
        {
                echo "
					<tr bgcolor='#FFFFFF'>
						<td align=center colspan=9 height=30>검색결과가 없습니다.</td>
					</tr>
                ";
        }
    }//contentPrint function end



	function pageNavigation(){

		global $page_total,$page,$start,$scale,$page_scale,$division,$page_last,$Mode;

		$Parameter_value = "division=3&pdx=2&sub=30";

		if($page_total>$scale)
		{
		if($start+1>$scale*$page_scale)
				{
				$pre_start=$page*$scale*$page_scale-$scale;
				echo "<a href='$PHP_SELF?start=0&$Parameter_value'><img src=../images/icon_left_arrow2.gif border=0></a>&nbsp;";
				echo "<a href='$PHP_SELF?start=$pre_start&$Parameter_value'><img src=../images/arrow_left.gif border=0></a>&nbsp;";
				}
		for($vj=0; $vj<$page_scale; $vj++)
			{
			$ln=($page * $page_scale+$vj)*$scale;
			$vk=$page*$page_scale+$vj+1;
				if($ln<$page_total)
				{
						if($ln!=$start)
						{
						echo "<a href='$PHP_SELF?start=$ln&$Parameter_value'><font class=darkgray> $vk </a>.</font>";
						}
						else
						{
						echo "<span class=darkgray>[$vk].</span></font>";
						}
				}
			}
		if($page_total>(($page+1)*$scale*$page_scale))
				{
				$n_start=($page+1)*$scale*$page_scale;
				$last_start=$page_last*$scale;
				echo "&nbsp;<a href='$PHP_SELF?start=$n_start&$Parameter_value'><img src=../images/arrow_right.gif border=0></a>&nbsp;";
				echo "<a href='$PHP_SELF?start=$last_start&$Parameter_value'><img src=../images/icon_right_arrow2.gif border=0></a>";
				}
		}
	}// pageNavigation function end
	
	
?>
	<div id="contentwrapper" class="productDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">예약관리</a></li>
					<li><a href="#">예약관리</a></li>
					<li><?= $pcap ?></li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
				 <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&ty=<?=$ty?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post" >
						<input type="hidden" name="mode" value="search">
						<table class="table table-bordered table-condensed">
						    
							<tr>
								<td width="10%" class="titletd text-center">예약고객명</td>
								<td width="40%" class="">
									<input type="text" id="cname" name="cname" class="inpubase" value="<?=$cname?>"/>
								</td>
								<td width="10%" class="titletd text-center">예약번호</td>
								<td width="40%" class="">
									<input type="text" id="crev" name="crev" class="inpubase" value="<?=$crev?>"/>
								</td>
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">이메일</td>
								<td width="40%" class=""><input type="text" id="cemail" name="cphone" class="inpubase" value="<?=$cemail?>" autocomplete=off /></td>
								<td width="10%" class="titletd text-center">발행일</td>
								<td width="40%" class="">
									<div class="row">
										<div class="col-sm-6">
											<input type="text" id="StartYMD" name="StartYMD" class="inpubase tourDate1" value="<?=$start_date?>" autocomplete=off />
											
										</div>
										<div class="col-sm-6">
											<input type="text" id="EndYMD" name="EndYMD" class="inpubase tourDate1" value="<?=$stop_date?>" autocomplete=off />
											
										</div>
										
									</div>
								</td>
							</tr>
							<tr>
								<td width="10%" class="titletd text-center">&nbsp;예약상태</td>
								<td width="40%" class="">&nbsp;
									<div class="row">
											<div class="col-sm-6">
												<select name=r_staus class="form-control" ><option value=''>전체 
												<option value='READY' <?php if($r_staus == "READY") echo "selected"; ?>>구입/견적완료 
												<option value='ORDER' <?php if($r_staus == "ORDER") echo "selected"; ?>>발주완료
												<option value='ORDER_CANCEL' <?php if($r_staus == "ORDER_CANCEL") echo "selected"; ?>>구입취소
												</select>
											</div>
									</div>
								</td>
								
							</tr>
							<tr>
								<td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button>&nbsp; <button type='button' value='musical_regi_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</button></td>
							</tr>
							
						</table>

						
					</form>

					<br />
			  
				  <table class="table table-striped table-bordered mediaTable js-MListTable">
				  
				  <form name=musical action="<?= $PHP_SELF ?>division=3&pdx=2&sub=30" method=post>
				  <input type=hidden name=mode value=save>
				  <input type=hidden name=mCode value="<?= $mCode ?>">
				  <input type=hidden name=item_type value="musical">
				  <input type=hidden name=view_position value="">
					<thead>
						
						<tr bgcolor=#b2dcca height=28>
							<th width=5% align=center>인원</th>
							<th width=15% align=center >예약코드</th>
							<th width=10% align=center>예약자</th>
							<th width=10% align=center>상품코드</th>
							<th width=20% align=center>상품명</th>
							<th width=10% align=center>관람일</th>
							<th width=10% align=center>가격</th>
							<th width=15% align=center>구입일</th>
							<th width=10% align=center>상태</th>
						</tr>
					</thead>
				 
					<?php contentPrint(); ?>
					<tr>
						<td colspan=8 align=center><?php pageNavigation(); ?></td>
					</tr>
					</form>
				  </table>
				</div>
			</div>
		</div>
	</div>
	<?php
		include "include/side_m.php"
	?>
    </body>
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
			$( ".btnchk" ).click(function() {
				
				if ($("#startDate1").val() == "") {
					alert("출발일을 입력하세요!");
					$("#startDate1").focus();
					return false;
				}
				

			});

			$( ".btn1" ).click(function() {
				var url = $(this).val();
				window.location.href = url;
				

			});

	    });
   </script>
</html>
			  

>