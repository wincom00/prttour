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
	if($Mode == "del")
	{
		$qry1 = "delete from member_list where seq_no= '$id'";
		$rst1 = mysql_query($qry1,$dbConn);
	}
	if($mode == "update")
	{
		update_infoinit($userid);
	}
	function printVendor(){
			
			global $dbConn,$division,$g_nm,$pdx,$sub;
			if ($g_nm != "") {

				$gudnm = "&& kor_name like '%$g_nm%' ";
			} else {

				$gudnm = "";

			}
			
			$qry1 = "select * from member_list where division = 'guide' $gudnm order by kor_name asc";
			$rst1 = mysql_query($qry1,$dbConn);

			while($row1 = mysql_Fetch_assoc($rst1)){
			
				switch($row1['guide_status'])
				{
					case "GOOD":
						$status = "근무가능";
						break;
					case "DISABLE1":
						$status = "근무불가(휴가)";
						break;
					case "DISABLE2":
						$status = "근무불가(병가)";
						break;
					case "DISABLE3":
						$status = "근무불가(개인사정)";
						break;
					case "DISABLE4":
						$status = "근무불가(징계)";
						break;
					case "DISABLE5":
						$status = "퇴사";	
						break;
				}
				$log_cnt=getinfo_dbExMember($row1['userid']);
				$usid= $log_cnt['userid'];
			//	echo $log_cnt[log_cnt]."11";
				if ($log_cnt['log_cnt'] > 3 ) {
					 $st = '<td align=center bgcolor="ffcccc"><a href=base_guide.php?mode=update&division=7&pdx=1&sub=15&userid='.$usid.'>잠김</a></td>';
				} else {
					$st = '<td align=center>정상</td>';
				}	
				echo "<tr bgcolor=#FFFFFF>
				<td align=left>&nbsp;{$row1['kor_name']}</td>
				<td height=25>&nbsp;{$row1['eng_name']}</td>
				<td align=center>{$row1['userid']}</td>
				<td align=center>{$row1['company_phone']}</td>
				<td align=center>$status</td>
				$st
				<td align=center><a href=base_guide_m.php?division=$division&pdx=$pdx&sub=$sub&id={$row1['seq_no']}>수정</a> | <a href=\"javascript:del({$row1['seq_no']})\">삭제</a></td>
				</tr>";


			}

	}
?>
     
	<div id="contentwrapper">
			<div class="main_content">
				<div id="jCrumbs" class="breadCrumb module">
					<ul>
						<li>
							<a href="/admin"><i class="glyphicon glyphicon-home"></i></a>
						</li>
						<li>
							<a href="#">인사관리</a>
						</li>
						<li>
							<a href="#">직원관리</a>
						</li>
						<li>
							가이드관리
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
							      <td width=10%  class="titletd" style="vertical-align: middle;">가이드명 </td>
								  <td width=20% style='border:0;' class="conttd"><input width=30%  type="text" id="g_nm" name="g_nm" class="inpubase lg" value="<?=$g_nm?>"/></td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <td class="conttd"><a href='base_guide_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
                               </tr> 
							</tbody>
						</table>
					 </form>
					  <table class="table table-striped table-bordered mediaTable">
						<thead>
							<tr>
							    <th width=10% class="essential">가이드 명(한글)</th>
								<th width=10% class="essential">가이드 명(영문)</th>
								<th width=10% class="essential">가이드ID</th>
								<th width=10% class="essential">전화</td>
								<th width=10% class="essential">상태</td>
								<th width=10% class="essential">로그인상태</td>
								<th width=15% class="essential">수정|삭제</td>

							    
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
		function del(id){
			
			if(confirm("삭제할까요?") == true)
			{
				location.replace('base_guide.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
			}
			else return;
		}
		
		
	</script>


    </body>
</html>

      
      