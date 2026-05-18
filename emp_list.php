<?php
    include "include/header.php";
	//include "include/inc_base.php";
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	//echo $ty1;
	//exit;

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



	if ($ty1=="batch1") {
		
        
		for ($i=0;$i<count($seqNo) ;$i++ ) {
			$qry = "select join_date,v_date1,v_date2
							from 
								member_list
							where division in ('admin') && out_yn is null && seq_no ='".$seqNo[$i]."' order by wdate desc ";
			$rst = mysql_query($qry,$dbConn);
			$row = mysql_fetch_assoc($rst);
			if ($row['v_date1']=="") {
				$mydate = date("Y-m-d", strtotime("+12 month", strtotime($row['join_date']))); 
			} else  {
				$mydate = date("Y-m-d", strtotime("+12 month", strtotime($row['v_date2']))); 
			}
			
//echo $row['join_date'];
			$jdate = explode('-',$mydate);
			$year = $jdate[0];
			$month =$jdate[1];
			$day =  $jdate[2];
			$w_date = date("Y-m-d", mktime(0, 0, 0, $month + 12, $day, $year));

			$qry1 = "update member_list set v_date1='".$mydate."',v_date2='".$w_date."' where  seq_no ='".$seqNo[$i]."'";
			$rst1 = mysql_query($qry1,$dbConn);
		//	echo $qry1;
		 //   exit;
		}
		
		Misc::jvAlert("업데이트 되었습니다.!!","");
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
			
				
				$log_cnt=getinfo_dbExMember($row1['userid']);
				$usid= $log_cnt['userid'];
			//	echo $log_cnt[log_cnt]."11";
				if ($log_cnt['log_cnt'] > 3 ) {
					 $st = '<td align=center bgcolor="ffcccc"><a href=emp_list.php?mode=update&division='.$division.'&pdx='.$pdx.'&sub='.$sub.'&userid='.$usid.'>잠김</a></td>';
				} else {
					$st = '<td align=center>정상</td>';
				}
				$log=getinfo_dbExMember($row1['userid']);
				$usid= $log['userid'];
				
				if ($log['time_yn'] == 'Y' ) {
					$timest = '<td align=center bgcolor="ffcccc"><a href=emp_list.php?division='.$division.'&pdx='.$pdx.'&sub='.$sub.'&mode=upst&userid='.$usid.'&v=N>대상</a></td>';
				} else {
					$timest = '<td align=center bgcolor=""><a href=emp_list.php?division='.$division.'&pdx='.$pdx.'&sub='.$sub.'&mode=upst&userid='.$usid.'&v=Y>비대상</a></td>';
				}
				echo "<tr bgcolor=#FFFFFF>
				 <td align=center height=28><input type=checkbox name=seqNo[]  value='{$row1['seq_no']}'></td>
				<td align=left>&nbsp;{$row1['kor_name']}</td>
				<td height=25>&nbsp;{$row1['userid']}</td>
				<td align=center>&nbsp;<b>P.</b> {$row1['phone']} &nbsp;&nbsp;<b>C.</b> {$row1['cell_phone']})</td>
				<td align=center>{$row1['email']}</td>
				<td align=center>{$row1['join_date']}</td>
				$st
				$timest
				<td align=center><a href=emp_m.php?division=$division&pdx=$pdx&sub=$sub&id={$row1['seq_no']}>수정</a> | <a href=\"javascript:del({$row1['seq_no']})\">퇴사</a>  | <a href=\"javascript:rest({$row1['seq_no']})\">입사</a></td>
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
									<?php $option0 = ($type1 == "0") ? ('<option value="0" selected>이름순</option>') : ('<option value="0">이름순</option>'); echo $option0 ?>
									<?php $option1 = ($type1 == "1") ? ('<option value="1" selected>입력일순</option>') : ('<option value="1">입력일순</option>'); echo $option1 ?>
									<?php $option2 = ($type1== "2") ? ('<option value="2" selected>퇴사자</option>') : ('<option value="2">퇴사자</option>'); echo $option2 ?>
								</select>
								  </td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <td class="conttd"><a href='emp_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
								 
                               </tr> 
							</tbody>
						</table>
					 </form>
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="emp_form" id="emp_form" method="post">
			          <input type="hidden" name="ty1" id="ty1" value="search">
					  <table class="table table-striped table-bordered table-condensed">
						    <tbody>
								<td width=15%  class="conttd"><button type='button' class="btn btn-primary btn-sm btnrest">휴가기간일괄업데이트</button> </td>
							    <td width=15% style='border:0;' class="conttd"><button type='button' class="btn btn-primary btn-sm btnsick">병가일수업데이트</button></td>
								 <td width='*' class="conttd">&nbsp; </td>
							</tbody>
					  </table>
					  <table id='ctable' class="table table-striped table-bordered mediaTable">
						<thead>
							<tr>
							    <th width=5% class="essential"><input id='selectAll' type=checkbox ></th>
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
							
						<?php printVendor(); ?>
					  </table>
                      </form>
				</div><!-- -->
		</div>                
		</div>
	  </div>

	</div>

    <?php
		include "include/side_m.php"
	?>
    
    <script>
	    $(document).ready(function () {
			//pt.initProductList()
			
			
			var oTable = $('#ctable').dataTable({
				
				stateSave: true,
				pageLength: 100,
				"order": [[ 0, "asc" ]]
			});

			var allPages = oTable.fnGetNodes();

			$('body').on('click', '#selectAll', function () {
				if ($(this).hasClass('allChecked')) {
					$('input[type="checkbox"]', allPages).prop('checked', false);
				} else {
					$('input[type="checkbox"]', allPages).prop('checked', true);
				}
				$(this).toggleClass('allChecked');
			});
			$(".dataTables_length").css({ "display" :"none" });
			$('body').on('click', '.btnrest', function () {
				$("#ty1").val("batch1");
				$("#emp_form").submit();
			});

			$('body').on('click', '.btnsick', function () {
				$("#ty1").val("batch2");
				$("#emp_form").submit();
			});
		});
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

      
      