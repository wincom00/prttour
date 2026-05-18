<?php
	include "include/header.php";
	//include "include/inc_base.php";
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	
    if (!$start) {
		$start = 0;
	}
	$board_scale = 40;

	$board_page = 15;

	$scale=$board_scale;

	$page_scale=$board_page;

    
    if ($empid == "") {
    	$emp = "";
    } else {
    	$emp =" && a.userid='".$empid."'";
    }

    if ($tmtype  == 1) {
    	$tmcond = " && substring( a.login_date, 12, 8 ) BETWEEN '01:00:00' AND '11:59:00' ";
    	$logtmp = " && date_format( a.login_date, '%Y-%m-%d' ) between '$st' and '$ed' ";
    } else if ($tmtype  == 2)  {
    	$tmcond = " && substring( a.login_date, 12, 8 ) BETWEEN '12:00:00' AND '14:59:00' ";
    	$logtmp = " && date_format( a.login_date, '%Y-%m-%d' ) between '$st' and '$ed' ";
    } else if ($tmtype  == 3)  {
    	$tmcond = " && substring( a.logout_date, 12, 8 ) BETWEEN '15:00:00' AND '23:59:00' ";
    	$logtmp = " && date_format( a.logout_date, '%Y-%m-%d' ) between '$st' and '$ed' ";
    } else {
    	$tmcond = "";
    	$logtmp = " && date_format( a.login_date, '%Y-%m-%d' ) between '$st' and '$ed' ";
    }

    
    $qry1 = "select *,a.login_date as login
    		from att_log a, member_list b
    		where a.userid=b.userid && b.out_yn is null 
    			$tmcond $emp  $logtmp
			order by b.company_code, b.kor_name, a.login_date, a.logout_date asc limit $start, $scale";

	$page=floor($start/($scale*$page_scale));

	$rst1=mysql_query($qry1);
	$result_rows=mysql_num_rows($rst1);
//echo $qry1;
	$total=mysql_affected_rows();
	$last=floor($total/$scale);

	
	$page_total_qry = mysql_query("select count(*) 
									from att_log a, member_list b
									where a.userid=b.userid && b.out_yn is null  $emp $tmcond $logtmp");

	$page_total = mysql_result($page_total_qry,0,0);

	$page_last = floor($page_total/$scale);

	$total_page_num = ceil($page_total/$scale);

	$now_page_num = floor($start/$scale) + 1;

	$num1 = 0;

	if ($start) {
		$n = $page_total - $start;
	} else {
		$n = $page_total;
	}


	function printattlog2() {
		global $dbConn, $scale, $rst2, $qry2, $empid, $st, $ed, $company_code,$empid;
		
		if ($empid == "") {
			$emp ="";
		} else {
			$emp ="&& userid = '$empid'";
		}
		if ($company_code <> "D030000") {
			$comp_qry = " && company_code = '$company_code' ";
		}
		$qry2 = "select * from member_list where division = 'admin' && time_yn='Y' $comp_qry order by kor_name";
		$rst2=mysql_query($qry2);

		while ($row2 = mysql_Fetch_assoc($rst2)): 
			if ($st != "") {
				$qry3 = "select userid
						from att_log
						where date_format(login_date, '%Y-%m-%d') between '$st' and '$ed'
							$emp
							&& userid = '{$row2['userid']}'";
				$rst3=mysql_query($qry3);      
				$result_rows=mysql_num_rows($rst3);
				if ($result_rows == 0) {
					$tmp="";
					$bgcol = '#ddffdd';
				}

				while ($row3 = mysql_fetch_assoc($rst3)) {
					$tmp = "O";
					$bgcol = '#ffffff';
				}
			}
			echo "<tr bgcolor=$bgcol>
					<td align=center>{$row2['eng_name']}/{$row2['kor_name']}&nbsp; $tmp</td>
				</tr>";
		endwhile;
	}

	function printattlog() {
		global $dbConn, $division, $start, $page_scale, $scale, $rst1, $qry1, $page_total, $user_dbinfo,$st, $ed,$empid;
	   
		if ($page_total != "0") {
			for ($i = $start; $i < $start + $scale; $i++) {
				if($i < $page_total) {
					$row1 = mysql_Fetch_assoc($rst1);
					//echo $row1[id]."|".$row1[login]."|".$row1[lologinout_date]."<br />";
					if ($row1['userid'] <> $prev_mem) {
						$mems++;
					}
					if ($mems % 2) {
						$namebgcol = '#ffffff';
					} else {
						$namebgcol = '#ddddff';
					}
		
					if (date('Y-m-d', strtotime($row1['login'])) <> $prev_date) {
						$dates++;
					}
					if ($dates % 2) {
						$datebgcol = '#ffdddd';
					} else {
						$datebgcol = '#ffffff';
					}
					
					if ($row1['company_code'] <> $prev_company) {
						$codeName = codebaseName($row1['company_code']);
						echo "<tr bgcolor=#fffec2 height=25>
							<td colspan=6>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$codeName['comment']}</td>
						</tr>";
					}
					 
					$total_time2 = strtotime($row1['logout_date']) - strtotime($row1['login']);
					
					$total_hrs2 = (int) ($total_time2 / 3600);
					$total_min2 = (int) ($total_time2 / 60) - ($total_hrs2 * 60);
					if ($total_min2 < 10) {
						$total_min2 = "0".$total_min2;
					}
					
					if ($row1['logout_date'] <> 0) {
						$timestamp1 = strtotime(date('Y-m-d', strtotime($row1['login']))); 
						list($d,$w) = explode(' ',date('Y-m-d w',$timestamp1));

				    if ($w%6==0 ) {
							$total_hrsh+= $total_hrs2 ;
							$tmptimeh += $total_min2; 	
				    } else { 

							$total_hrs+= $total_hrs2 ;
							$tmptime += $total_min2; 
					  }
						$totaltm = $total_hrs2.":".$total_min2;
						
					} else {
						$totaltm = 0;
					}
					
				
					
					echo "<tr bgcolor=#FFFFFF>
					<td align=center bgcolor=$namebgcol>{$row1['eng_name']} / {$row1['kor_name']}</td>
					<td height=25 bgcolor=$datebgcol>&nbsp;".substr($row1['login'],0,10)."</td>
					<td height=25>&nbsp;".substr($row1['login'],11,9)."</td>
					<td height=25 bgcolor=$datebgcol>&nbsp;".substr($row1['logout_date'],0,10)."</td>
					<td height=25>&nbsp;".substr($row1['logout_date'],11,9)."</td>
					<td height=25 align=center>&nbsp;".$totaltm ." </td>
					</tr>";
				

					if ($row1['logout_date'] <> 0) {
						$total_time += strtotime($row1['logout_date']) - strtotime($row1['login']);
					}
          
					$prev_mem = $row1['userid'];
					$prev_date = date('Y-m-d', strtotime($row1['login']));
					$prev_company = $row1['company_code'];
				}
			}
			
			  $tmphour = (int) ($tmptime/60); 
			  $tmpmin = ($tmptime%60); 
			 
			  
			  
			  $tmphourh = (int) ($tmptimeh/60); 
			  $tmpminh = ($tmptimeh%60); 
			  $total_minh = $tmpminh;
			  
			  $total_minh2 = $tmpmin + $tmpminh; 
			   
			  $tmphourh2 = (int) ($total_minh2/60); 
			  $tmpminh2 = ($total_minh2%60); 
			  $total_min = $tmpminh2;
			  
			  $total_hrs = $total_hrs  + $tmphour + $total_hrsh+$tmphourh+$tmphourh2;
			 // echo $total_hrs."|||".$total_min."||".$cnt_days."<br>";
			  
			//$total_hrs = (int) ($total_time / 3600);
			//$total_min = (int) ($total_time / 60) - ($total_hrs * 60);
			
			
			
			$cnt = 0;
			$cnt_days= intval((strtotime($ed)-strtotime($st))/86400)+1; 
		//	echo date('Y-m-d', strtotime($st. ' + 1 day')) 
     

			for ($k=1 ;$k < $cnt_days;$k++) {
				
			
				if ($k ==1) {
				  $timestamp = strtotime($st);	
				} else {
					
					//$timestamp = strtotime($s+$k);	
					$timestamp = strtotime(date('Y-m-d', strtotime($st. ' + '.$k.' days'))); 



				}
				
           list($d,$w) = explode(' ',date('Y-m-d w',$timestamp));

				if ($w%6==0 ) {
					$cnt = $cnt + 1;
					
				} 

				
			}

	      $cnt_days = $cnt_days - $cnt;
		  $days_time= $cnt_days * 8;
		  
		  
		  //lunch time
		 // $total_hrs = $total_hrs - $cnt_days;
			if ($total_min < 10) {
				$total_min = "0".$total_min;
			}
			
			
			$ottime =  $total_hrs - $days_time;
		//	echo $row1[sal_code];
			if ($row1['sal_code']=='E050103') {
				if ($ottime > 0) {
					
						$ottt=$ottime;
						
						$qry2 = "select * from ove_log where userid= '$empid' && ov_status='1' && ov_date1 >= '$st' && ov_date2 <= '$ed' ";
					    $rst2=mysql_query($qry2);
					    $row2 = mysql_Fetch_assoc($rst2);
					   // echo $qry2;
						if ($row2['ov_time']) {
							
							$amt_ot=$ottt *$row1['ove_rate'] ;
						}
				} else {
					
					$ottt= 0;
				}
				
				     $totregamt = $total_hrs * $row1['sal_rate'];
					 
						 if (($ottt > 1) && ($total_min > 30) && ($row2['ov_time'])) {
						 	
						 	  $ottt = $ottt  + 1;
						 	  $amt_ot = $row1['ove_rate'] * $ottt;
						 } else if (($ottt > 1) && ($total_min <= 30) && ($row2['ov_time'])) { 
						 	
						 	 
						 	  $amt_ot = $row1['ove_rate'] * $ottt;
						 } else {
						 	
						 	  $amt_ot = 0.00;
						 }
				
			 } else {
			 	 $totregamt = $row1['sal_rate'];
			 	 $amt_ot = "0.00";
			 	 $ottt= 0;
			 }
			  $totamt =$totregamt + $amt_ot;
			  if ($row1['sal_code']=='E050103') {
			  	$txttime = "시간제";
			  } else if ($row1['sal_code']=='E050102') {
			  	
			  	$txttime = "월급제";
			  } else if ($row1['sal_code']=='E050101') {
			  	$txttime = "주급제";
			  }
			 /*
			   	echo "	<tr bgcolor=#ffffff height=50px>
							<td align=center style='border-spacing:0px;'>&nbsp;총 근무시간:&nbsp;$total_hrs:$total_min 시간 <br> <font color=red>$txttime</font></td>
							<td colspan=6>&nbsp;Reg &nbsp;&nbsp;Hrs : $days_time hrs <br> 
							              &nbsp;OT &nbsp;&nbsp;&nbsp;Hrs : $ottt hrs <br>
							              &nbsp;Reg &nbsp;Rate : $&nbsp;$row1[sal_rate] &nbsp;Reg Amount&nbsp;:&nbsp;$$totregamt <br> 
							              &nbsp;OT &nbsp;Rate : $&nbsp;$row1[ove_rate]  &nbsp;&nbsp;OT  Amount&nbsp;:&nbsp;$$amt_ot <br> 
							              &nbsp;<font color=red>Total &nbsp;Pay : $&nbsp;$totamt</font> </td>
						</tr>";
						*/
							echo "	<tr bgcolor=#ffffff height=50px>
							<td align=center style='border-spacing:0px;'>&nbsp;총 근무시간:&nbsp;$total_hrs:$total_min 시간 <br> <font color=red>$txttime</font></td>
							<td colspan=6> </td>
						</tr>";
			
		}
	}
		
	function pageNavigation(){
        global $page_total, $page, $start, $scale, $page_scale, $division, $mCode, $page_last, $rst1, $st, $ed, $tmtype, $company_code,$empid;

        if ($page_total>$scale) {
			if ($start+1>$scale*$page_scale) {
                $pre_start=$page*$scale*$page_scale-$scale;
                echo "<a href='$PHP_SELF?division=$division&mCode=$mCode&start=0&st=$st&ed=$ed&company_code=$company_code&empid=$empid'><img src=../images/arrow_left.gif border=0></a>&nbsp;";
                echo "<a href='$PHP_SELF?division=$division&mCode=$mCode&start=$pre_start&st=$st&ed=$ed&company_code=$company_code&empid=$empid'><img src=../images/icon_left_arrow2.gif border=0></a>&nbsp;";
			}
			for ($vj=0; $vj<$page_scale; $vj++) {
				$ln=($page * $page_scale+$vj)*$scale;
				$vk=$page*$page_scale+$vj+1;
                if ($ln<$page_total) {
					if ($ln!=$start) {
						echo "<a href='$PHP_SELF?division=$division&mCode=$mCode&start=$ln&st=$st&ed=$ed&tmtype=$tmtype&company_code=$company_code&empid=$empid'><font class=darkgray> $vk </a>.</font>";
					} else {
						echo "<span class=darkgray>[$vk].</span></font>";
					}
                }
            }
			if ($page_total>(($page+1)*$scale*$page_scale)) {
                $n_start=($page+1)*$scale*$page_scale;
                $last_start=$page_last*$scale;
                echo "&nbsp;<a href='$PHP_SELF?division=$division&mCode=$mCode&start=$n_start&st=$st&ed=$ed&company_code=$company_code&empid=$empid'><img src=../images/arrow_right.gif border=0></a>&nbsp;";
                echo "<a href='$PHP_SELF?division=$division&mCode=$mCode&start=$last_start&st=$st&ed=$ed&tmtype=$tmtype&company_code=$company_code&empid=$empid'><img src=../images/icon_right_arrow2.gif border=0></a>";
			}
        }
    }// pageNavigation function end


	if ($mode == "modify") {
		$qry1 = "select * from att_log where id = '$id'";
		$rst1 = mysql_query($qry1);
		$v_info = mysql_fetch_assoc($rst1);
	}
    
?>
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">인사관리</a>
					</li>
					<li>
						<a href="#">직원관리</a>
					</li>
					
					<li>
						<a href="inout_time.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">직원별근무시간관리</a>
					</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" method=post name=att onSubmit="return chk(this)">
					  <input type=hidden name=mode value="save">
					  <input type=hidden name=division value="<?= $division ?>">
					  <input type=hidden name=extra_mode value="<?= $extra_mode ?>">
					  <table class="table table-striped table-bordered table-condensed">
					  
					  
						
						<tr bgcolor=#f9f9f9 height=28>
							<td width=10% align=center>IN DATE</td>
							<td width=40% bgcolor=#FFFFFF >
							  <div class="row">
							            <div class="col-sm-2">
                                            <div class="input-group input-group-sm">
                                                <input type="date" class="form-control" name=st id='date1' max="2999-12-31" placeholder="From" value="<?=$st?>" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="input-group input-group-sm">
                                                <input type="date" class="form-control" name=ed max="2999-12-31" placeholder="to" value="<?=$ed?>" autocomplete="off" />
												
                                            </div>
                                        </div>
										<div class="col-sm-3">
                                            <div class="input-group input-group-sm">
                                                &nbsp;<button type=submit  class="btn btn-primary btn-md btn1">검색</button>
												
                                            </div>
                                        </div>
								</div>
							</td>
						</tr>
						<tr bgcolor=#f9f9f9 >
							<td width=10% align=center>직원아이디</td>
							<td bgcolor=#FFFFFF >&nbsp;
							  <div class="row">
							            <div class="col-sm-5">
                                            <div class="input-group input-group-sm">
							
							
												<select name=empid class="form-control">
													<option value="">ALL
													<?php 
														$que = "select *
																from member_list
																where division in ('admin') && time_yn = 'Y' && out_yn is null 
																order by kor_name";
														$que_rst1 = mysql_query($que);

														while ($que_row1 = mysql_Fetch_assoc($que_rst1)): 
															if (($v_info['userid'] == $que_row1['userid']) || ($empid == $que_row1['userid'])) {  													
													?>
														<option value="<?=$que_row1['userid']?>" selected><?=$que_row1['kor_name']?></option>
													<?php
															} else {
													?>
														<option value="<?=$que_row1['userid']?>" ><?=$que_row1['kor_name']?></option>
													<?php
															}    	
														endwhile;
													?>	
													</select>
										 </div>
										</div>
							     </div>
								</td>
								<!--<td width=15% align=center>시간</td>
								<td bgcolor=#FFFFFF >&nbsp;<select name=tmtype class="form-control" >
								<option value="0">ALL</option>
								<option value="1">아침</option>
								<option value="2">점심</option>
								<option value="3">저녁</option>
							</select></td>-->
						</tr>
						
						<input type=hidden name=id value='<?=$v_info['id']?>'>
						<tr>
							<td colspan=4 height=35 bgcolor=#FFFFFF align=center></td>
						</tr>
					  </table>
					  </form>
					  <br>
					
					  <table class="table table-striped table-bordered mediaTable">
							<tr bgcolor=#b2dcca height=28>
								<td width=20% align=center>사용자</td>
								<td width=10% align=center>in 일자</td>
								<td width=10% align=center>in 시간</td>
								<td width=10% align=center>out 일자</td>
								<td width=10% align=center>out 시간</td>
								<td width=15% align=center>근무 시간</td>
								
							</tr>
						 <?php printattlog(); ?>
					  </table>
					       
					  <br><br>
					  
					  <p align=center><?php pageNavigation(); ?></p>
		    </div>
          </div>
      </div>
</div>
<?php
		include "include/side_m.php"
?>