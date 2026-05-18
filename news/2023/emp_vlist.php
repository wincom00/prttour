<?php
    include "include/header.php";
	
	if($_COOKIE[MEMLOGIN_ADMIN_PURUN] !="")
	{
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
	if($Mode == "del")
	{
		$qry1 = "update member_list set out_yn='1' where  seq_no ='$id'";
		$rst1 = mysql_query($qry1,$dbConn);
	}
	if($Mode == "reset")
	{
		$qry1 = "update member_list set out_yn=null where  seq_no ='$id'";
		$rst1 = mysql_query($qry1,$dbConn);
	}
	if($mode == "update")
	{
		update_infoinit($userid);
	}
	if ($mode == 'upst') {
		update_time($userid,$v);
	} 
	function printVendor(){
			
			global $dbConn,$division,$g_nm,$pdx,$sub,$type1;
			if ($g_nm != "") {

				$gudnm = "&& kor_name like '%$g_nm%' ";
			} else {

				$gudnm = "";

			}
			//echo $type1;
			if ($type1 == "1") {
				$qry1 = "select *
							from 
								member_list
							where division in ('admin') && out_yn is null order by wdate desc ";
			} elseif ($type1 == "2") { 
				$qry1 = "select *
							from 
								member_list
							where division in ('admin') && out_yn ='1' order by wdate desc";
				
			} else {
				$qry1 = "(select *, 1 as ord
							from member_list
							where division in('admin')  && out_yn is null && grant_s != 2)
						union (select *, 2
							from member_list
							where division in('admin') && out_yn is null && grant_s = 2)
						order by ord, kor_name";
			} 
			//echo $qry1;
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_Fetch_assoc($rst1)){
			
				
				$log_cnt=getinfo_dbExMember($row1[userid]);
				$usid= $log_cnt[userid];
			//	echo $log_cnt[log_cnt]."11";
				if ($log_cnt[log_cnt] > 3 ) {
					 $st = '<td align=center bgcolor="ffcccc"><a href=emp_list.php?mode=update&division='.$division.'&pdx='.$pdx.'&sub='.$sub.'&userid='.$usid.'>잠김</a></td>';
				} else {
					$st = '<td align=center>정상</td>';
				}
				$log=getinfo_dbExMember($row1[userid]);
				$usid= $log[userid];
				
				if ($log[time_yn] == 'Y' ) {
					$timest = '<td align=center bgcolor="ffcccc"><a href=emp_list.php?division='.$division.'&pdx='.$pdx.'&sub='.$sub.'&mode=upst&userid='.$usid.'&v=N>대상</a></td>';
				} else {
					$timest = '<td align=center bgcolor=""><a href=emp_list.php?division='.$division.'&pdx='.$pdx.'&sub='.$sub.'&mode=upst&userid='.$usid.'&v=Y>비대상</a></td>';
				}
				echo "<tr bgcolor=#FFFFFF>
				 <td align=center height=28><input type=checkbox name=seqNo[]  value='$row1[seq_no]'></td>
				<td align=left>&nbsp;$row1[kor_name]</td>
				<td height=25>&nbsp;$row1[userid]</td>
				<td align=center>&nbsp;<b>P.</b> $row1[phone] &nbsp;&nbsp;<b>C.</b> $row1[cell_phone])</td>
				<td align=center>$row1[email]</td>
				<td align=center>$row1[wdate]</td>
				$st
				$timest
				<td align=center><a href=emp_m.php?division=$division&pdx=$pdx&sub=$sub&id=$row1[seq_no]>수정</a> | <a href=\"javascript:del($row1[seq_no])\">퇴사</a>  | <a href=\"javascript:rest($row1[seq_no])\">입사</a></td>
				</tr>";


			}

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
						직원관리
					</li>
				</ul>
			</div>
			
		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="base_code" id="base_code" method="post">
			          <input type="hidden" name="mode" value="search">
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							   <tr>
							      <td width=10%  class="titletd" style="vertical-align: middle;">항목명 </td>
								  <td width=20% style='border:0;' class="conttd">
								  <select name="type1" class="inpubase lg" >
									<? $option0 = ($type1 == "0") ? ('<option value="0" selected>이름순</option>') : ('<option value="0">이름순</option>'); echo $option0 ?>
									<? $option1 = ($type1 == "1") ? ('<option value="1" selected>입력일순</option>') : ('<option value="1">입력일순</option>'); echo $option1 ?>
									<? $option2 = ($type1== "2") ? ('<option value="2" selected>퇴사자</option>') : ('<option value="2">퇴사자</option>'); echo $option2 ?>
								</select>
								  </td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <td class="conttd"><a href='emp_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
                               </tr> 
							</tbody>
						</table>
					 </form>
					  <table class="table table-striped table-bordered mediaTable">
						<thead>
							<tr>
							    <th width=5% class="essential"><input type=checkbox onclick="GoCheckAll();" ></th>
							    <th width=10% class="essential">직원 명</th>
								<th width=10% class="essential">직원 ID</th>
								<th width=10% class="essential">연락처</th>
								<th width=10% class="essential">이메일</td>
								<th width=10% class="essential">입사일</td>
								<th width=10% class="essential">상태</td>
								<th width=10% class="essential">로그인대상</td>
								<th width=15% class="essential">수정|삭제</td>

							    
							</tr>
						</thead> 
							
						<? printVendor(); ?>
					  </table>
                     
				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
    
    <script>
		function del(id){
			
			if(confirm("퇴사처리할까요?") == true)
			{
				location.replace('emp_list.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
			}
			else return;
		}
		function rest(id){
			
			if(confirm("입사처리할까요?") == true)
			{
				location.replace('emp_list.php?Mode=reset&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
			}
			else return;
		}
		
		
	</script>


    </body>
</html>

      
      