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

	$id  = mysql_real_escape_string($id);
	$backUrl = "emp_vmlist.php?division=$division&pdx=$pdx&sub=$sub";

	if ($id == "") {
		Misc::jvAlert("잘못된 접근입니다.", "location.href='$backUrl'");
		exit;
	}

	// 승인/반려 처리
	if ($mode == "approve" || $mode == "reject") {

			// 신청건 조회
			$vq  = "select * from emp_vacation where seq_no = '$id'";
			$vr  = mysql_query($vq);
			$vrow = mysql_fetch_assoc($vr);

			if (!$vrow) {
				Misc::jvAlert("신청 내역을 찾을 수 없습니다.", "location.href='$backUrl'");
				exit;
			}

			$prevStatus = $vrow['r_status'];
			$vtype      = $vrow['v_type'];
			$cnt        = is_numeric($vrow['r_vcnt']) ? floatval($vrow['r_vcnt']) : 0;
			$uid        = mysql_real_escape_string($vrow['user_id']);

			if ($mode == "approve") {

				// 상태 갱신
				mysql_query("update emp_vacation set r_status='승인완료', r_date=now() where seq_no='$id'");

				// 잔여일수 차감 (이전이 승인완료가 아닐 때만 — 이중 차감 방지)
				if ($prevStatus != '승인완료' && $cnt > 0) {
					if ($vtype == 'V') {
						mysql_query("update member_list set r_vdate = coalesce(r_vdate,0) - $cnt, use_vdate = coalesce(use_vdate,0) + $cnt where userid='$uid'");
					} else if ($vtype == 'S') {
						mysql_query("update member_list set r_sdate = coalesce(r_sdate,0) - $cnt, use_sdate = coalesce(use_sdate,0) + $cnt where userid='$uid'");
					}
					// 'O'(무급) 은 차감 없음
				}

				Misc::jvAlert("승인 처리되었습니다.", "location.href='$backUrl'");
				exit;

			} else { // reject

				mysql_query("update emp_vacation set r_status='반려', r_date=now() where seq_no='$id'");

				// 이미 승인완료였다면 차감분 원복
				if ($prevStatus == '승인완료' && $cnt > 0) {
					if ($vtype == 'V') {
						mysql_query("update member_list set r_vdate = coalesce(r_vdate,0) + $cnt, use_vdate = coalesce(use_vdate,0) - $cnt where userid='$uid'");
					} else if ($vtype == 'S') {
						mysql_query("update member_list set r_sdate = coalesce(r_sdate,0) + $cnt, use_sdate = coalesce(use_sdate,0) - $cnt where userid='$uid'");
					}
				}

				Misc::jvAlert("반려 처리되었습니다.", "location.href='$backUrl'");
				exit;
			}
	}

	// 표시용 데이터
	$vq  = "select * from emp_vacation where seq_no = '$id'";
	$vr  = mysql_query($vq);
	$vrow = mysql_fetch_assoc($vr);

	if (!$vrow) {
		Misc::jvAlert("신청 내역을 찾을 수 없습니다.", "location.href='$backUrl'");
		exit;
	}
	$m_info = getinfo_dbMember($vrow['user_id']);

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
						<a href="<?=$backUrl?>">휴가승인관리</a>
					</li>
					<li>
						결재
					</li>
				</ul>
			</div>

		<div class="row">
				<div class="col-sm-12 col-md-8">

					<table class="table table-striped table-bordered table-condensed">
					    <tbody>
							<tr>
								<td colspan=4 class="titletd text-left">&nbsp;<b>신청자 정보</b></td>
							</tr>
							<tr>
								<td width=18% class="titletd">직원명</td>
								<td width=32% class="conttd">&nbsp;<?= htmlspecialchars($m_info['kor_name']) ?> (<?= htmlspecialchars($m_info['userid']) ?>)</td>
								<td width=18% class="titletd">입사일</td>
								<td width=32% class="conttd">&nbsp;<?= htmlspecialchars($m_info['join_date']) ?></td>
							</tr>
							<tr>
								<td class="titletd">휴가잔여 / 미리사용</td>
								<td class="conttd">&nbsp;<b class="text-primary"><?= htmlspecialchars($m_info['r_vdate']) ?></b> 일 / <?= htmlspecialchars($m_info['use_vdate']) ?> 일</td>
								<td class="titletd">병가잔여 / 미리사용</td>
								<td class="conttd">&nbsp;<b class="text-primary"><?= htmlspecialchars($m_info['r_sdate']) ?></b> 일 / <?= htmlspecialchars($m_info['use_sdate']) ?> 일</td>
							</tr>
						</tbody>
					</table>

					<table class="table table-striped table-bordered table-condensed">
					    <tbody>
							<tr>
								<td colspan=4 class="titletd text-left">&nbsp;<b>신청 내역</b></td>
							</tr>
							<tr>
								<td width=18% class="titletd">신청타입</td>
								<td width=32% class="conttd">&nbsp;<?= vmTypeLabel($vrow['v_type']) ?></td>
								<td width=18% class="titletd">신청일수</td>
								<td width=32% class="conttd">&nbsp;<b><?= htmlspecialchars($vrow['r_vcnt']) ?></b> 일</td>
							</tr>
							<tr>
								<td class="titletd">신청기간</td>
								<td class="conttd">&nbsp;<?= $vrow['v_sdate'] ?> ~ <?= $vrow['v_edate'] ?></td>
								<td class="titletd">현재상태</td>
								<td class="conttd">&nbsp;<?= vmStatusLabel($vrow['r_status']) ?></td>
							</tr>
							<tr>
								<td class="titletd">신청사유</td>
								<td class="conttd" colspan=3>&nbsp;<?= nl2br(htmlspecialchars($vrow['r_memo'])) ?></td>
							</tr>
						</tbody>
					</table>

					<form name="frmappr" id="frmappr" method="post" action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">
						<input type="hidden" name="mode" id="ap_mode" value="">
						<input type="hidden" name="id" value="<?= htmlspecialchars($vrow['seq_no']) ?>">
						<div class="text-center" style="padding:10px;">
							<button type="button" class="btn btn-success btn-sm" id="btnApprove">승인 (잔여일수 차감)</button>
							&nbsp;
							<button type="button" class="btn btn-danger btn-sm" id="btnReject">반려</button>
							&nbsp;
							<a href="<?=$backUrl?>" class="btn btn-default btn-sm">목록</a>
						</div>
						<?php if ($vrow['v_type'] == 'O'): ?>
						<p class="text-center text-muted"><small>※ 무급휴가는 승인하여도 잔여일수가 차감되지 않습니다.</small></p>
						<?php endif; ?>
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
		$(document).on('click', '#btnApprove', function(){
			if (confirm("승인 처리하시겠습니까? 휴가/병가는 잔여일수가 차감됩니다.")) {
				$('#ap_mode').val('approve');
				$('#frmappr').submit();
			}
		});
		$(document).on('click', '#btnReject', function(){
			if (confirm("반려 처리하시겠습니까?")) {
				$('#ap_mode').val('reject');
				$('#frmappr').submit();
			}
		});
	</script>


    </body>
</html>
