
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
		$qry1 = "update member_list  set del_yn = 'Y'  where seq_no= '$id'";
		$rst1 = mysql_query($qry1,$dbConn);
	}
	
	if($Mode == "rec")
	{
		$qry1 = "update member_list  set del_yn = 'N'  where seq_no= '$id'";
		$rst1 = mysql_query($qry1,$dbConn);
	}
	function printVendor(){
			
			global $dbConn,$division,$pdx,$sub,$com_nm;

			if ($com_nm != "") {
				$qrycom = " && kor_name like '%$com_nm%' ";
			} else {
				$qrycom ="";

			}

			$qry1 = "select * from member_list where division = 'comp' $qrycom order by company_area asc, userid asc";
			$rst1 = mysql_query($qry1,$dbConn);
			//echo $qry1;
			while($row1 = mysql_Fetch_assoc($rst1)){
				
				    $company_area = codebaseName($row1['company_area']);
				    if ($row1['del_yn'] == 'Y') {
					    $delyn = '(<font color=red>삭제됨</font>)';
					
				    } else {
				    	$delyn ="";
				    }
				    if ($row1['set_acc'] == "C") {
					   $checkyn = "checked";
					
				     } else {
					   $checkyn = "";
					
				    }
					echo "<tr bgcolor=#FFFFFF>
					<td align=center>{$row1['ruserid']}$delyn</td>
					<td align=center>{$row1['userid']}$delyn</td>
					<td align=center>{$company_area['comment']}</td>
					<td height=25>&nbsp;{$row1['kor_name']}</td>
					<td>&nbsp;{$row1['company_phone']}</td>
					<td>&nbsp;{$row1['company_manager']}</td>
					<td align=center>&nbsp;<input type=checkbox class='bs-switch'  data-size='mini' name=set_a[]   $checkyn disabled></td>
					<td align=center>&nbsp;{$row1['pos']}</td>";
					 if ($row1['del_yn'] <> 'Y') {
						echo "
						<td align=center><a href=base_agent_m.php?division=$division&pdx=$pdx&sub=$sub&id={$row1['seq_no']}>수정</a> | <a href=\"javascript:del({$row1['seq_no']})\">삭제</a></td>
					   </tr>";
					 } else {
						
						echo "
						<td align=center><a href=base_agent_m.php?division=$division&pdx=$pdx&sub=$sub&id={$row1['seq_no']}>수정</a> | <a href=\"javascript:recover({$row1['seq_no']})\">복구</a></td>
					   </tr>";
					   
					 } 


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
						<a href="#">기초관리</a>
					</li>
					<li>
						<a href="#">협력사관리</a>
					</li>
					<li>
						협력사등록
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
							      <td width=10%  class="titletd" style="vertical-align: middle;">협력사명 </td>
								  <td width=20% style='border:0;' class="conttd"><input width=30%  type="text" id="com_nm" name="com_nm" class="inpubase lg" value="<?=$com_nm?>"/></td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <td class="conttd"><a href='base_agent_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
                               </tr> 
							</tbody>
						</table>
					 </form>
					  <table class="table table-striped table-bordered mediaTable">
						<thead>
							<tr>
							    <th width=10% class="essential" align="center">접속 ID</th>
								<th width=10% class="essential" align="center">협력사 ID</th>
								<th width=10% class="essential">지역</th>
								<th width=20% class="essential">협력사</th>
								<th width=10% class="essential">전화</th>
								<th width=10% class="essential">담당자</th>
								<th width=10% class="essential">회계노출</th>
								<th width=10% class="essential">정산위치</th>
								<th width=10% class="essential">수정 | 삭제</th>
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
	   
         $(document).ready(function() {
				// bootstrap switch
		        paran_bs_switch.init();

	     });

		 // bootstrap switch
		paran_bs_switch = {
			init: function() {
				if($('.bs-switch').length) {
					$('.bs-switch').bootstrapSwitch();
				}
			}
		};
	
		function del(id){
			
			if(confirm("삭제할까요?") == true)
			{
				location.replace('base_agent.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
			}
			else return;
		}
		
		function recover(id){
			
			if(confirm("복구할까요?") == true)
			{
				location.replace('base_agent.php?Mode=rec&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
			}
			else return;
		}
	</script>


    </body>
</html>

      
      