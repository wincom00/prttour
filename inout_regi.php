<?php
	include "include/header.php";
	//include "include/inc_base.php";
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}


	if ($mode == "del") {
		$qry1 = "delete from att_log where id= '$id'";
		$rst1 = mysql_query($qry1,$dbConn);
		
	}

	if ($mode == "save") {
		if ($extra_mode == "modify") {
			$new_date=date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
			$dates=date("Y-m-d", $new_date);
			$qry1 = "update att_log set login_date='$attin_date',login_ip='$attin_ip',logout_date='$attout_date' ,logout_ip='$attout_ip' where id= '$id'";
			$rst1 = mysql_query($qry1,$dbConn);
		}
	}
	if ($mode == "insert") {
		
			$new_date=date("U", mktime(0,0,0,(date("m")), (date("d")), date("Y")));
			$dates=date("Y-m-d", $new_date);
			$qry1 = "insert into  att_log (userid,login_date,login_ip,logout_date,logout_ip,status) values
			                      ('$empid','$attin_date','$attin_ip','$attout_date' ,'$attout_ip' ,'2')";
			$rst1 = mysql_query($qry1,$dbConn);
		//echo $qry1 ;
		//exit;
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
    
    $qry1 = "select *, a.login_date as lodate
    		from att_log a, member_list b
    		where a.userid=b.userid $emp $comp_qry &&
    			date_format( a.login_date, '%Y-%m-%d' ) between '$st' and '$ed'
			order by a.userid, a.login_date, a.logout_date asc
			limit $start, $scale";
//echo $qry1;
//exit;
	$page=floor($start/($scale*$page_scale));

	$rst2=mysql_query($qry1);
	$result_rows=mysql_num_rows($rst1);

	$total=mysql_affected_rows();
	$last=floor($total/$scale);
	
	$page_total_qry = mysql_query("select count(*) 
									from att_log a,member_list b
									where a.userid=b.userid $emp $comp_qry &&
										date_format( a.login_date, '%Y-%m-%d' ) between '$st' and '$ed'");
    
	$page_total = mysql_result($page_total_qry,0,0);

	$page_last = floor($page_total/$scale);

	$total_page_num = ceil($page_total/$scale);

	$now_page_num = floor($start/$scale) + 1;

	$num1 = 0;

	if ($start) {
		$n=$page_total-$start;
	} else {
		$n=$page_total;
	}

	function printattlog() {
		global $dbConn, $division,  $mode, $st, $ed, $start, $page_scale, $scale, $rst2, $qry1, $page_total, $user_dbinfo;
//echo $qry1;
		if ($mode <> "modify") {
			if ($page_total != "0") {
				for ($i=$start; $i<$start+$scale; $i++) {
					if ($i< $page_total) {
						$row1 = mysql_Fetch_assoc($rst2);

						if ($row1['userid'] <> $prev_mem) {
							$mems++;
						}
						if ($mems % 2) {
							$namebgcol = '#ffffff';
						} else {
							$namebgcol = '#ddddff';
						}
			//echo $row1[lodate];
						if (date('Y-m-d', strtotime($row1['lodate'])) <> $prev_date) {
							$dates++;
							$dur1 = "";
							if ($row1['logout_date'] <> 0) {
								$dur2 = hr_display(strtotime($row1['logout_date']) - strtotime($row1['lodate']));
							} else {
								$dur2 = "";
							}
							//echo $dur2."TEST1<br />";
						} else {
							if ($prev_logout != "0000-00-00 00:00:00") {
								$dur1 = "<span style='color:blue;'>" . hr_display(strtotime($row1['lodate']) - strtotime($prev_logout)) . "</span>";
								//$dur1 = hr_display(strtotime($row1[login_date]) - strtotime($prev_logout));
							} else {
								$dur1 = "";
							}
							if ($row1['logout_date'] != "0000-00-00 00:00:00") {
								$dur2 = hr_display(strtotime($row1['logout_date']) - strtotime($row1['lodate']));
							} else {
								$dur2 = "";
							}
							//echo $prev_logout."/".$dur1."TEST2<br />";
						}
						if ($dates % 2) {
							$datebgcol = '#ffdddd';
						} else {
							$datebgcol = '#ffffff';
						}
						if ($i % 2) {
							//$rowbg = '#fffee2';
						} else {
							$rowbg = '#ffffff';
						}
						
						if ($row1['company_code'] <> $prev_company) {
							$codeName = codebaseName($row1['company_code']);
							echo "<tr bgcolor=#fffec2 height=25>
					<td colspan=6>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$codeName['comment']}</td>
				</tr>";
						}
						/*
						echo "
				<tr bgcolor=#FFFFFF>
					<td height=20 align=center bgcolor=$namebgcol>$row1[userid]/$row1[kor_name]</td>
					<td align=center>&nbsp;in</td>
					<td align=center bgcolor=$datebgcol>&nbsp;$row1[login_date]</td>
					<td align=center bgcolor=$rowbg>$dur1</td>
					<td align=center>&nbsp;$row1[login_ip]</td>
					<td rowspan=2 align=center><a href=$PHP_SELF?mode=modify&id=$row1[id]&extra_mode=modify&division=7&pdx=1&sub=25&st=$st&ed=$ed><span style='border:3px outset lightgray'>수정</span></a> | <a href=\"javascript:del($row1[id])\"><span style='border:3px outset lightgray'>삭제</span></a></td>
				</tr>";
				*/
						echo "
				<tr bgcolor=#FFFFFF>
					<td height=20 align=center bgcolor=$namebgcol>{$row1['userid']}/{$row1['kor_name']}</td>
					<td align=center>&nbsp;in</td>
					<td align=center bgcolor=$datebgcol>&nbsp;{$row1['lodate']}</td>
					<td align=center bgcolor=$rowbg>$dur1</td>
					<td align=center>&nbsp;{$row1['login_ip']}</td>
					<td rowspan=2 align=center><a href=$PHP_SELF?mode=modify&id={$row1['id']}&extra_mode=modify&division=7&pdx=1&sub=25&st=$st&ed=$ed>수정</a> | <a href=\"javascript:del({$row1['id']})\">삭제</a></td>
				</tr>";
						echo "
				<tr bgcolor=#FFFFFF>
					<td height=20 align=center bgcolor=$namebgcol>{$row1['userid']}/{$row1['kor_name']}</td>
					<td align=center>&nbsp;out</td>
					<td align=center bgcolor=$datebgcol>&nbsp;{$row1['logout_date']}</td>
					<td align=center bgcolor=$rowbg>$dur2</td>
					<td align=center>&nbsp;{$row1['logout_ip']}</td>
				</tr>
				<tr bgcolor=#cccccc><td colspan=6 height=0></td></tr>";

						if ($row1['logout_date'] <> 0) {
							$total_time += strtotime($row1['logout_date']) - strtotime($row1['lodate']);
						}

						$prev_mem = $row1['userid'];
						$prev_login = $row1['lodate'];
						$prev_logout = $row1['logout_date'];
						$prev_date = date('Y-m-d', strtotime($row1['lodate']));
						$prev_company = $row1['company_code'];
					}
				}
				
				echo "	<tr bgcolor=#ffffff height=50px>
							<td align=center style='border-spacing:0px;'>&nbsp;총 근무시간:&nbsp;". hr_display($total_time) ." 시간</td>
							<td colspan=5></td>
						</tr>";
				
			}
		}
	}


	function pageNavigation() {
        global $page_total, $page, $start, $scale, $page_scale, $division, $mCode, $mode, $page_last, $rst1, $st, $ed, $company_code;

		if ($mode <> "modify") {
			if ($page_total>$scale) {
				if ($start+1>$scale*$page_scale) {
					$pre_start=$page*$scale*$page_scale-$scale;
					echo "<a href='$PHP_SELF?division=7&pdx=1&sub=25&start=0&st=$st&ed=$ed'><img src=../images/arrow_left.gif border=0></a>&nbsp;";
					echo "<a href='$PHP_SELF?division=7&pdx=1&sub=25&start=$pre_start&st=$st&ed=$ed'><img src=../images/icon_left_arrow2.gif border=0></a>&nbsp;";
				}
				for ($vj=0; $vj<$page_scale; $vj++) {
					$ln=($page * $page_scale+$vj)*$scale;
					$vk=$page*$page_scale+$vj+1;
					if ($ln<$page_total) {
						if ($ln!=$start) {
						echo "<a href='$PHP_SELF?division=7&pdx=1&sub=25&start=$ln&st=$st&ed=$ed&tmtype=$tmtype'><font class=darkgray> $vk </a>.</font>";
						} else {
							echo "<span class=darkgray>[$vk].</span></font>";
						}
					}
				}
				if($page_total>(($page+1)*$scale*$page_scale)) {
					$n_start=($page+1)*$scale*$page_scale;
					$last_start=$page_last*$scale;
					echo "&nbsp;<a href='$PHP_SELF?division=7&pdx=1&sub=25&start=$n_start&st=$st&ed=$ed'><img src=../images/arrow_right.gif border=0></a>&nbsp;";
					echo "<a href='$PHP_SELF?division=7&pdx=1&sub=25&start=$last_start&st=$st&ed=$ed&tmtype=$tmtype'><img src=../images/icon_right_arrow2.gif border=0></a>";
				}
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
						<a href="inout_regi.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">직원별근무시간수정</a>
					</li>
				</ul>
			</div>
	
            <div class="row">
				<div class="col-sm-12 col-md-12">

						  <table class="table table-striped table-bordered table-condensed">
						  <script>
						  
							function chk(submit_mode) {
								tf = document.att;					

								tf.mode.value = submit_mode;
								
								tf.submit(); 
								//return true;
							}
						  </script>
						  <form action=<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?> method=post name=att onSubmit="return chk(this)">
						  <input type=hidden name=mode value="save">
						  <input type=hidden name=division value="<?= $division ?>">
						  <input type=hidden name=extra_mode value="<?= $extra_mode ?>">
						  
						  <tr bgcolor=#f9f9f9 height=28>
								<td width=10% align=center>IN DATE</td>
								<td width=40% bgcolor=#FFFFFF colspan=3>
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
											<div class="col-sm-2">
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
								
						</tr>
							<tr bgcolor=#f9f9f9 height=28>
								<td width=15% align=center>입실일자</td>
								<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=attin_date size=20 class="inpubase md" value="<?= $v_info['login_date'] ?>"></td>
								<td width=15% align=center>입실 IP</td>
								<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=attin_ip  size=20 class="inpubase md" value="<?= $v_info['login_ip'] ?>"></td>
							</tr>
							<tr bgcolor=#f9f9f9 height=28>
								<td width=15% align=center>퇴실일자</td>
								<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=attout_date size=20 class="inpubase md" value="<?= $v_info['logout_date'] ?>"></td>
								<td width=15% align=center>퇴실 IP</td>
								<td width=35% bgcolor=#FFFFFF>&nbsp;<input type=text name=attout_ip  size=20 class="inpubase md" value="<?= $v_info['logout_ip'] ?>"></td>
							</tr>
							<input type=hidden name=id value='<?=$v_info['id']?>'>
							<tr>
								<td colspan=4 height=35 bgcolor=#FFFFFF align=center><input type=button value="새로생성" class="btn btn-primary btn-md" onClick="chk('insert')"> &nbsp; <input type=button value="저장" class="btn btn-primary btn-md2022-05-17" onClick="chk('save')"></td>
							</tr></form>
						  </table>
						  <br>
						<script>
							function del(id){
								if (confirm("삭제할까요?") == true) {
									location.replace('inout_regi.php?division=7&pdx=1&sub=25&mode=del&id='+id+'');
								} else return;
							}
						</script>
						  <table class="table table-striped table-bordered mediaTable">
							<tr>
								<td width=24% align=center>사용자아이디</td>
								<td width=10% align=center>상태</td>
								<td width=25% align=center>IN/OUT 날짜/시간</td>
								<td width=8% align=center>소요시간</td>
								<td width=20% align=center>IN/OUT IP</td>
								<td width=13% align=center>수정|삭제</td>
							</tr>
							<?php printattlog(); ?>
						  </table>
						  <br><br>
						  
               </div>
          </div>
      </div>
</div>
<?php
		include "include/side_m.php"
?>
