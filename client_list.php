<?php
    include "include/header.php";
	
	if($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="")
	{
	} else {
		
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
	/*
    if (!hasMenuAccess($division, $pdx, $sub)) {
    	 $goUrl_1 = "index.php";
		   Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		 	 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			 exit;
    }
	*/
	if (!isset($start) || $start == "") {
		$start = 0;
	}
	$start = (int)$start;
	$scale = 50;
	$page = 0;
	$page_total = 0;
	$page_scale = 10;
	$page_last = 0;

	if (!isset($type1) || $type1 == "") {
		$type1 = "0";
	}
	if (!isset($g_nm)) {
		$g_nm = "";
	}
	$g_nm = trim($g_nm);

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
	function buildClientWhere(){
		global $dbConn, $g_nm, $type1;

		$searchSql = "";
		if ($g_nm != "") {
			$searchWord = mysql_real_escape_string($g_nm, $dbConn);
			$searchSql = " && ((kor_name like '%$searchWord%') || (eng_name like '%$searchWord%') || (email like '%$searchWord%') || (phone like '%$searchWord%') || (userid like '%$searchWord%'))";
		}

		if ($type1 == "2") {
			return "where division in ('web-member','NORMAL') && out_yn = '1' $searchSql";
		}

		return "where division in ('web-member','NORMAL') && out_yn is null $searchSql";
	}

	function buildClientOrder(){
		global $type1;

		if ($type1 == "1" || $type1 == "2") {
			return "order by wdate desc";
		}

		return "order by case when grant_s = 2 then 2 else 1 end asc, kor_name asc";
	}

	function loadClientPageStats(){
		global $dbConn, $scale, $page_total, $page_last;

		$whereSql = buildClientWhere();
		$countQry = "select count(*) as cnt from member_list $whereSql";
		$countRst = mysql_query($countQry, $dbConn);
		$countRow = mysql_fetch_assoc($countRst);

		$page_total = (int)$countRow['cnt'];
		$page_last = ($page_total > 0) ? floor(($page_total - 1) / $scale) : 0;
	}

	function printVendor(){
			
			global $dbConn, $division, $pdx, $sub, $start, $scale;

			$whereSql = buildClientWhere();
			$orderSql = buildClientOrder();

			$qry1 = "select * from member_list $whereSql $orderSql limit $start, $scale";
			$rst1 = mysql_query($qry1, $dbConn);
			$found = false;

			while($row1 = mysql_Fetch_assoc($rst1)){
				$found = true;
				$seqNo = (int)$row1['seq_no'];
				$userid = htmlspecialchars($row1['userid']);
				$useridParam = urlencode($row1['userid']);
				$korName = htmlspecialchars($row1['kor_name']);
				$email = htmlspecialchars($row1['email']);
				$phone = htmlspecialchars($row1['phone']);
				$logCnt = (int)$row1['log_cnt'];

				if ($logCnt > 3) {
					 $st = '<td align=center bgcolor="ffcccc"><a href="client_list.php?mode=update&division='.$division.'&pdx='.$pdx.'&sub='.$sub.'&userid='.$useridParam.'">잠김</a></td>';
				} else {
					$st = '<td align=center>정상</td>';
				}

				echo "<tr bgcolor=#FFFFFF>
				 <td align=center height=28><input type=checkbox name=seqNo[] value='{$seqNo}'></td>
				<td align=left>&nbsp;{$korName}</td>
				<td height=25>&nbsp;{$userid}</td>
				<td align=center>{$email}</td>
				<td align=center>&nbsp;<b>P.</b> {$phone} &nbsp;&nbsp;</td>
				$st
				<td align=center><a href=cli_m.php?division=$division&pdx=$pdx&sub=$sub&id={$seqNo}>수정</a> | <a href=pu_cash.php?division=$division&pdx=$pdx&sub=$sub&id={$seqNo}>푸른포인트</a> | <a href=\"javascript:del({$seqNo})\">탈퇴</a>  | <a href=\"javascript:rest({$seqNo})\">재가입</a></td>
				</tr>";
			}

			if (!$found) {
				echo "<tr bgcolor=#FFFFFF><td colspan='7' align='center'>데이터없음</td></tr>";
			}
	}

	function clientPageNavigation(){
		global $page_total, $start, $scale, $page_scale, $page_last, $division, $pdx, $sub, $type1, $g_nm, $PHP_SELF;

		$page = floor($start / ($scale * $page_scale));
		$parameterValue = "division=$division&pdx=$pdx&sub=$sub&type1=$type1&g_nm=".urlencode($g_nm);

		if ($page_total <= $scale) {
			return;
		}

		if ($start + 1 > $scale * $page_scale) {
			$pre_start = $page * $scale * $page_scale - $scale;
			echo "<li class='page-item'><a class='page-link' href='$PHP_SELF?start=0&$parameterValue'>Previous</a></li>";
		}

		for ($vj = 0; $vj < $page_scale; $vj++) {
			$ln = ($page * $page_scale + $vj) * $scale;
			$vk = $page * $page_scale + $vj + 1;

			if ($ln < $page_total) {
				if ($ln != $start) {
					echo "<li class='page-item'><a class='page-link' href='$PHP_SELF?start=$ln&$parameterValue'>$vk</a></li>";
				} else {
					echo "<li class='page-item active'><a class='page-link' href='#'>$vk</a></li>";
				}
			}
		}

		if ($page_total > (($page + 1) * $scale * $page_scale)) {
			$n_start = ($page + 1) * $scale * $page_scale;
			$last_start = $page_last * $scale;
			echo "<li class='page-item'><a class='page-link' href='$PHP_SELF?start=$n_start&$parameterValue'>Next</a></li>";
		}
	}

	loadClientPageStats();
?>
     
<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">고객관리</a>
					</li>
					<li>
						<a href="#">고객정보</a>
					</li>
					
					<li>
						<a href="client_list.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">고객정보리스트</a>
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
									<?php $option2 = ($type1== "2") ? ('<option value="2" selected>탈퇴자</option>') : ('<option value="2">탈퇴자</option>'); echo $option2 ?>
								</select><input type=text name=g_nm size=15 class='inpubase md' placeholder="이름조회" value='<?=htmlspecialchars($g_nm)?>'>
								  </td>
								  <td width=5%  class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button> </td>
								  <td class="conttd"><a href='cli_m.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>' class="btn btn-primary btn-sm btn1">추가</a> </td>
                               </tr> 
							</tbody>
						</table>
					 </form>
					 <div style="margin-bottom:10px;">총 <?=number_format($page_total)?>명 / 페이지당 <?=$scale?>명</div>
					  <table id='ctable' class="table table-striped table-bordered mediaTable">
						<thead>
							<tr>
							    <th width=5% class="essential"><input id='selectAll' type=checkbox ></th>
							    <th width=10% class="essential">이름</th>
								<th width=10% class="essential">아이디</th>
								<th width=10% class="essential">이메일</td>
								<th width=10% class="essential">연락처</th>
								<th width=10% class="essential">상태</td>
								<th width=15% class="essential">수정|삭제</td>

							    
							</tr>
						</thead>
						<tbody>
						<?php printVendor(); ?>
						</tbody>
					  </table>
					  <?php if ($page_total > $scale) { ?>
					  <nav aria-label="고객 목록 페이지">
						  <ul class="pagination">
							  <?php clientPageNavigation(); ?>
						  </ul>
					  </nav>
					  <?php } ?>
                     
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
			$('#selectAll').on('click', function () {
				$('#ctable tbody input[type="checkbox"][name="seqNo[]"]').prop('checked', this.checked);
			});
		});
			function del(id){
				
				if(confirm("탈퇴처리할까요?") == true)
				{
					location.replace('client_list.php?Mode=del&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
				}
				else return;
			}
			function rest(id){
				
				if(confirm("재가입처리할까요?") == true)
				{
					location.replace('client_list.php?Mode=reset&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&id=' + id);
				}
				else return;
			}
		
		
	</script>


    </body>
</html>

      
      
