<?php
    include "include/header.php";
	
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
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
	
	function printVendor(){
			
			global $dbConn,$division,$g_nm,$pdx,$sub,$type1;
			if ($g_nm != "") {

				$gudnm = "&& kor_name like '%$g_nm%' ";
			} else {

				$gudnm = "";

			}
			//echo $type1;
			$qry1 = "select *
							from 
								member_list
							where division in ('admin') && out_yn is null $gudnm order by kor_name  ";
					 
			//echo $qry1;
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_Fetch_assoc($rst1)){
			    $v_row=getVStatus($row1['user_id']);
				$v_st=between($v_row['v_sdate'],$v_row['v_edate']);
				
			//	echo $log_cnt[log_cnt]."11"; 
				if (($v_st == true) && ($v_row['v_sdate']=="V")) { 
					$st = '<td align=center bgcolor="ffcccc">휴가</td>';
                } else  if (($v_st == true) && ($v_row['v_sdate']=="S")){
					$st = '<td align=center bgcolor="ffcccc">병가</td>';
				} else {
					$st = '<td align=center>근무</td>';
				}
				$log=getinfo_dbExMember($row1['userid']);
				$usid= $log['userid'];
				
				
				echo "<tr bgcolor=#FFFFFF>
				<td align=left>&nbsp;{$row1['kor_name']}</td>
				<td height=25>&nbsp;{$row1['userid']}</td>
				<td align=center>&nbsp;<b>P.</b> {$row1['phone']} &nbsp;&nbsp;<b>C.</b> {$row1['cell_phone']})</td>
				<td align=center>{$row1['email']}</td>
				<td align=center>{$row1['join_date']}</td>
				$st
				<td align=center>{$row1['v_date1']} ~ {$row1['v_date2']}</td>
				
				<td align=center><a href=emp_vm.php?division=$division&pdx=$pdx&sub=$sub&id={$row1['seq_no']}>수정</a> </td>
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
							      <td width=10%  class="titletd" style="vertical-align: middle;">직원명 </td>
								  <td width=20% style='border:0;' class="conttd">
								  <input type="text" name="g_nm" class="inpubase md" value="" spellcheck="false" data-ms-editor="true">
								  </td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <td class="conttd"><a href='emp_vm.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
                               </tr> 
							</tbody>
						</table>
					 </form>
					  <table class="table table-striped table-bordered mediaTable">
						<thead>
							<tr>
							    
							    <th width=10% class="essential">직원 명</th>
								<th width=10% class="essential">직원 ID</th>
								<th width=13% class="essential">연락처</th>
								<th width=10% class="essential">이메일</td>
								<th width=10% class="essential">입사일</td>
								<th width=10% class="essential">상태</td>
								<th width=15% class="essential">휴가기간</td>
								<th width=10% class="essential">수정</td>

							    
							</tr>
						</thead> 
							
						<?php printVendor(); ?>
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
		
		
		
	</script>


    </body>
</html>

      
      