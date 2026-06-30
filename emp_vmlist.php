<?php
    include "include/header.php";
	//include "include/inc_base.php";
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

	// 신청내역 상태 라벨 (공용)
	if (!function_exists('vmStatusLabel')) {
		function vmStatusLabel($s) {
			switch ($s) {
				case '신청중':   return "<span class='label label-warning'>신청중</span>";
				case '승인완료': return "<span class='label label-success'>승인완료</span>";
				case '반려':     return "<span class='label label-danger'>반려</span>";
				case '취소':     return "<span class='label label-default'>취소</span>";
				default:         return "<span class='label label-default'>".htmlspecialchars($s)."</span>";
			}
		}
	}

	function vmTypeLabel($t) {
		if ($t == "V") return "휴가신청";
		if ($t == "S") return "병가신청";
		if ($t == "O") return "무급휴가신청";
		return "-";
	}

	// 휴가 신청건 목록 출력 (신청건 기준)
	function printVacationRequests(){

			global $division, $pdx, $sub, $empid, $r_st;

			$where = " where m.division = 'admin' && m.out_yn is null ";

			if ($empid != "") {
				$f_empid = mysql_real_escape_string($empid);
				$where .= " && v.user_id = '$f_empid' ";
			}
			if ($r_st != "" && $r_st != "ALL") {
				$f_st = mysql_real_escape_string($r_st);
				$where .= " && v.r_status = '$f_st' ";
			}

			$qry1 = "select v.*, m.kor_name, m.phone, m.cell_phone, m.email, m.join_date
						from emp_vacation v
						inner join member_list m on m.userid = v.user_id
						$where
						order by v.wdate desc ";

			$rst1 = mysql_query($qry1);

			$cnt = 0;
			while($row1 = mysql_fetch_assoc($rst1)){
				$cnt++;

				// 현재 휴가중 여부 (승인완료 + 기간 내)
				$onLeave = ($row1['r_status'] == '승인완료') && between($row1['v_sdate'], $row1['v_edate']);
				if ($onLeave && $row1['v_type'] == 'V') {
					$nowSt = '<span class="label label-danger">휴가중</span>';
				} else if ($onLeave && $row1['v_type'] == 'S') {
					$nowSt = '<span class="label label-danger">병가중</span>';
				} else {
					$nowSt = '<span class="label label-default">근무</span>';
				}

				$reg_date = substr($row1['wdate'], 0, 10);
				$memo = nl2br(htmlspecialchars($row1['r_memo']));

				echo "<tr>
					<td class='text-center'>".htmlspecialchars($row1['kor_name'])."</td>
					<td class='text-center'>".htmlspecialchars($row1['user_id'])."</td>
					<td class='text-center'>".vmTypeLabel($row1['v_type'])."</td>
					<td class='text-center'>{$row1['v_sdate']} ~ {$row1['v_edate']}</td>
					<td class='text-center'>".htmlspecialchars($row1['r_vcnt'])."</td>
					<td class='text-left' style='white-space:normal;'>$memo</td>
					<td class='text-center'>$reg_date</td>
					<td class='text-center'>".vmStatusLabel($row1['r_status'])."</td>
					<td class='text-center'>$nowSt</td>
					<td class='text-center'><a class='btn btn-xs btn-primary' href=emp_vmm.php?division=$division&pdx=$pdx&sub=$sub&id={$row1['seq_no']}>결재하기</a></td>
					</tr>";
			}

			if ($cnt == 0) {
				echo "<tr><td colspan='10' class='text-center' style='padding:20px;'>해당 조건의 신청 내역이 없습니다.</td></tr>";
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
						휴가승인관리
					</li>
				</ul>
			</div>

		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" name="base_code" id="base_code" method="post">
			          <input type="hidden" name="mode" value="search">
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							   <tr>
							      <td width=10% class="titletd" style="vertical-align: middle;">직원명</td>
								  <td width=20% class="conttd">
								  <select name=empid class="form-control">
										<option value="">전체</option>
										<?php
											$que = "select * from member_list where division in ('admin') && out_yn is null order by kor_name";
											$que_rst1 = mysql_query($que);
											while ($que_row1 = mysql_fetch_assoc($que_rst1)):
										?>
											<option value="<?=htmlspecialchars($que_row1['userid'])?>" <?= ($empid == $que_row1['userid']) ? 'selected' : '' ?>><?=htmlspecialchars($que_row1['kor_name'])?></option>
										<?php
											endwhile;
										?>
										</select>
								  </td>
							      <td width=10% class="titletd" style="vertical-align: middle;">신청상태</td>
								  <td width=20% class="conttd">
								  <?php $r_st = ($r_st == "") ? "신청중" : $r_st; ?>
								  <select name=r_st class="form-control">
										<option value="ALL"     <?= ($r_st=="ALL")     ?'selected':'' ?>>전체</option>
										<option value="신청중"   <?= ($r_st=="신청중")   ?'selected':'' ?>>신청중</option>
										<option value="승인완료" <?= ($r_st=="승인완료") ?'selected':'' ?>>승인완료</option>
										<option value="반려"     <?= ($r_st=="반려")     ?'selected':'' ?>>반려</option>
										<option value="취소"     <?= ($r_st=="취소")     ?'selected':'' ?>>취소</option>
									</select>
								  </td>
								  <td class="conttd"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                               </tr>
							</tbody>
						</table>
					 </form>
					  <table id="ctable" class="table table-striped table-bordered table-hover table-condensed mediaTable">
						<thead>
							<tr>
							    <th width=10% class="text-center">직원 명</th>
								<th width=10% class="text-center">직원 ID</th>
								<th width=10% class="text-center">신청타입</th>
								<th width=15% class="text-center">신청기간</th>
								<th width=5%  class="text-center">일수</th>
								<th width=20% class="text-center">신청사유</th>
								<th width=8%  class="text-center">신청일</th>
								<th width=8%  class="text-center">신청상태</th>
								<th width=6%  class="text-center">현재상태</th>
								<th width=8%  class="text-center">결재</th>
							</tr>
						</thead>
						<tbody>
							<?php printVacationRequests(); ?>
						</tbody>
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
		$(document).ready(function () {
			$('#ctable').dataTable({
				stateSave: true,
				pageLength: 100,
				"order": [[ 6, "desc" ]]
			});
		});
	</script>


    </body>
</html>
