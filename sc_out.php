<?php
    include "include/header.php";
	
	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
    if ($startyear == "") {
		$startyear = date("Y");
	}
    if($StartYMD)
	{
		$StartYMD = $StartYMD;
		$EndYMD = $EndYMD;
		//$StartYMD = date("Y-m-d",mktime(0,0,0,$month,$day,$year));
		//$EndYMD = date("Y-m-d",mktime(0,0,0,$month,$day + 1 ,$year));
	}
	else
	{
		$year = date("Y");
		$month = date("m");
		$day = 1;

		$number = cal_days_in_month(CAL_GREGORIAN,$month, $year); 

		$StartYMD = date("Y-m-d",mktime(0,0,0,$month,$day,$year));
		$EndYMD = date("Y-m-d",mktime(0,0,0,$month,$number ,$year));

	}
//$StartYMD = "2019-09-01";
	//$EndYMD = "2019-09-21";
	// 1주일간 뽑아내기
    $cur_day = date("w",mktime(0,0,0,$month,$day,$year));
    $now = date("Y-m-d",mktime(0,0,0,$month,$day,$year));

    $minus_day = 14 - $cur_day;
    $week_first = date("m / d / Y",mktime(0,0,0,$month,$day - $cur_day,$year));
    $week_last = date("m / d / Y",mktime(0,0,0,$month,$day + $minus_day,$year));

	$qry22 = "(select selected_date from 
						(select adddate('1970-01-01',t4*10000 + t3*1000 + t2*100 + t1*10 + t0) selected_date from
						 (select 0 t0 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
						 (select 0 t1 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
						 (select 0 t2 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2,
						 (select 0 t3 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t3,
						 (select 0 t4 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t4) v
					   where selected_date between '$StartYMD' and '$EndYMD')";
	$rst22 = mysql_query($qry22);
    $totalDay =mysql_num_rows($rst22);

	$total_td = 150 + (($totalDay+1) * 60);

   
?>
<link rel="stylesheet" type="text/css" href="lib/datatables.css"/>

<style>
    /*div.dt-buttons {
        float: right; 
        padding-bottom: 10px;
    }*/
    .tableFixHead          { overflow-y: auto; height: 600px; }
    .tableFixHead thead th { position: sticky; top: 0; background:#eee;border:0.05em solid #848484; }
	table.dataTable thead th, table.dataTable thead td {
    
    border-bottom: 1px solid #111;
    }
</style>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">아웃바운드스케줄</a></li>
					
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action="<?= $PHP_SELF ?>" name="frmName" id="frmName" method="post">
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
								<?php  if ($user_dbinfo['dept_prior'] != "J")  { ?>
								<td width="10%" class="titletd text-center">지사선택</td>
								<td width="40%" class="no-right-border">
								
									<select class="form-control" name="dept">
										<option value="" selected>- 지사선택 -</option>
										<?=printBaseCode_first1('D02',$dept)?>
									</select>
								<?php } ?>
								</td>
							</tr>
									
							
						</table>

						<table class="table table-bordered table-condensed">
							<tr>
							    <td width="5%" class="text-center">검색년도</td>
								<td width="*" class="text-left">
									
                                     <div class="row no-nav">
										<div class="col-sm-2">
											<input type="text" id="startyear" name="startyear" class="inpubase tourDate3" placeholder="년도" value="<?= $startyear ?>" autocomplete=off />
										</div>
										<div class="col-sm-6">
											<ul class="pagination non-nav">
												<li class="disabled"><span><a href="javascript:cal('1')">1월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('2')">2월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('3')">3월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('4')">4월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('5')">5월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('6')">6월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('7')">7월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('8')">8월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('9')">9월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('10')">10월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('11')">11월</a></span></li>
												<li class="disabled"><span><a href="javascript:cal('12')">12월</a></span></li>
											</ul>
										</div>

										<div class="col-sm-2">
											<button type='submit' class="btn btn-primary btn-sm text-left btn1">검색</button>
										</div>
										
									  </div>
								</td>
								
							</tr>
									
							
						</table>
					</form>
					<br />
					<div class="row">
						<div class="col-sm-12 tableFixHead" >
						    <table width=100% id="guide_table" class="display nowrap table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th  style ='border:1px solid #848484;width:17%' align=center>상&nbsp;품&nbsp;명</th>
                                        <?php
												
												$sDate = $StartYMD;

												for($i=0; $i<$totalDay; $i++)
												{

													$sDate2 = explode("-",$sDate);

													$pdate  = date("Y-m-d",mktime (0,0,0,$sDate2[1]  , $sDate2[2]+$i, $sDate2[0]));
													$today1 = date("Y-m-d");
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
													if ($pdate == $today1) {
														echo "<th style='margin:0;border:0.05em solid #848484;background-color:#DDA0DD;' align=center>$day</th>";

													} else {
													    echo "<th style='margin:0;border:0.05em solid #848484;' align=center>$day</th>";
													}
												}


											?>
											
                                    </tr>
                                </thead>
                              
 <?php  
        
		if (($dept!="")) {
		    $deptqry1 = " && ((b.m_dept like '%$dept%') || (b.p_dept like '%$dept%'))";
		} else {
		    $deptqry1 = "";
		}
		$zip_qry1 = "select 
								b.p_day,
								b.p_name,
								b.p_code,
								b.bgcolor,
								sum(a.p_cnt) as person
								
							from 
								reserve_info a,
								product_master b
							where
									a.p_code = b.p_code && 
									a.rev_status in ('DONE') &&
									b.p_type in ('5') &&
									a.stDate >= '$StartYMD' and a.stDate <='$EndYMD' $deptqry $deptqry1
							group by a.p_code order by b.grp,b.p_day,b.p_name asc";
		//echo $zip_qry1 ;
		$zip_rst1 = mysql_query($zip_qry1);

		while($zip_row1 = mysql_fetch_assoc($zip_rst1)){
     
						$vDate = $StartYMD;
			
						if($zip_row1['p_day'] == "1")
						{
							$trip_day = "당일";
						}
						else
						{
							$trip_start = $zip_row1['p_day']-1;
							$trip_stop = $zip_row1['p_day'];
			
							$trip_day = "$trip_stop 일";
						}
			//echo $totalDay;
			
						for($k=0; $k<$totalDay; $k++)
						{
							
			
							$vDate2 = explode("-",$vDate);
			
							$choose_date = date("Y-m-d",mktime (0,0,0,$vDate2[1]  , $vDate2[2]+$k, $vDate2[0]));	
						
							$today = date("Y-m-d");
			
							$tmpstart_date = explode("-",$choose_date);
							$add_date = $productInfor['p_day_cnt']-1;
					
							$tmpstop_date  = date("Y-m-d",mktime (0,0,0,$tmpstart_date[1]  , $tmpstart_date[2]+$add_date, $tmpstart_date[0]));	
			
			
								// 당일 해당 상품 예약자
								$r_qry1 = "select sum(p_cnt)
								from reserve_info where (rev_status = 'DONE') && stDate = '$choose_date' && p_code = '{$zip_row1['p_code']}'
										";
			
								//print_r($r_qry1);
								///exit;
								$r_rst1 = mysql_query($r_qry1);
								$r_adult = @mysql_result($r_rst1,0,0) + @mysql_result($r_rst1,1,0);
								//$r_baby = @mysql_result($r_rst1,0,1) + @mysql_result($r_rst1,1,1);
								
								
			
								if($today == $choose_date)
								{
									$bgcolor = "#DDA0DD";
								}
								else
								{
									if(($r_adult ) > 0)
									{
										//$r_row1 = mysql_fetch_assoc($r_rst1);
			
										$bgcolor = "#FFFF99";
									}
									else
									{
										$bgcolor = "#FFFFFF";
									}
								}
			
							
								$qry1 = "select distinct a.* from reserve_info as a  where
										 a.stDate = '$choose_date' && a.p_code = '{$zip_row1['p_code']}' && (a.rev_status = 'DONE')";
					
								
								
								$rst1 = mysql_query($qry1);
			
								while($row1 = mysql_fetch_Assoc($rst1)){
									
									
								 
							        $hotel_qty1 = (float)$hotel_qty1 + (float)$row1['room_cnt'];
							        
									if ($row1['p_cnt'] != "") {
									   $adult = $row1['p_cnt'] ; 
									} else {
									   $adult = 0;
									}
									
									
									
									
							 
								}
			                    
			                    
								if(($r_adult) > 0)
								{
									$total_mem = "$r_adult";
								}
								else
								{
									$total_mem = "";
								}
			
								if($hotel_qty1)
								{
									$total_room = "/$hotel_qty1";
								}
								else
								{
									$total_room = "";
								}
			
										
								
			         
						    $block_content = "<a href=javascript:openwin('$choose_date','{$zip_row1['p_code']}') ><font color=black><font style='font-size:8pt'>$total_mem</font></font></a><font color=black><font style='font-size:8pt'><br>$total_room$n_show</font></font>";
					   
							//	
			               // echo $choose_date."|".$hotel_qty."|".$zip_row1[p_name]."<br>";	
							$content .= "<td width=30 height=35 style='border:1px solid #848484;' align=center bgcolor=$bgcolor title='호텔 : ".$hotel_qty1."'>$block_content</font></td>";
							$hotel_qty1 = 0;
							

						//	exit;
					   }
			         
						echo "<tr >
						<td align=left  style='border:1px solid #848484;' bgcolor='{$zip_row1['bgcolor']}'><font style='font-size:8pt'>&nbsp;{$zip_row1['p_code']}<font color=red>({$zip_row1['person']})</font></b><br/><b>{$zip_row1['p_name']}</b> </font></td>
						$content
						</tr>";
						
			            $hotel_qty1 = 0;
						unset($content);
						//exit;
		}

	?>
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
				$('.tourDate3').datepicker({
					minViewMode: 2,
					format: "yyyy",
					autoclose: true
					
			    });
            var hh  =window.innerHeight-150;
			/*
            //var args = {paging:false, ordering:false, info:false,scrollX:true,scrollY: 200};
             var table = $('#guide_table').DataTable({
                    scrollY:        hh+"px",
					scrollX:        true,
					scrollCollapse: true,
					paging:         false,
                    bSort : false,
					fixedColumns:   {
						leftColumns: 1
					
					}
			  
			   
			 }); */
            
			
		});
		var ctr=0;
        function openwin(stdate,s_code,rcd) { 
	       var winName = "all_"+(ctr++);
		   window.open("guide_assign_customer.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&s_code="+s_code+"&stdate="+stdate+"&rcode="+rcd,winName,"width=1290px,height=700,scrollbars=1");
	    }
		function numberOfDays(month,year) {
			var d = new Date(year, month, 0);
			return d.getDate();
		}
		function cal(mon) {
		   if(mon<10) mon = "0" + mon;
		   var st = $("#startyear").val()+"-"+mon+"-"+"01";
		   //alert($("#startDate1").val());;
	       $("#startDate1").val(st);
		   var lastday = numberOfDays(mon,$("#startyear").val());
		   //alert(lastday);
		   var ed = $("#startyear").val()+"-"+mon+"-"+lastday;
		   $("#endDate").val(ed);
		   $("#frmName").submit();
	    }
	</script>
    </body>
</html>
