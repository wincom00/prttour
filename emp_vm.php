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
				case '대표님승인완료': return "<span class='label label-success'>대표님승인완료</span>";
				case '반려':     return "<span class='label label-danger'>반려</span>";
				case '취소':     return "<span class='label label-default'>취소</span>";
				default:         return "<span class='label label-default'>".htmlspecialchars($s)."</span>";
			}
		}
	}

	// 승인 계열 상태 (기존 데이터에 '대표님승인완료' 존재 - 승인건과 동일하게 취급)
	$VM_APPROVED = array('승인완료','대표님승인완료');

	// 신청/취소/삭제는 항상 "로그인한 본인" 기준으로만 처리한다.
	// (POST 로 넘어온 userid/rid 를 그대로 믿으면 남의 신청건을 조작할 수 있다)
	$myid = mysql_real_escape_string($user_dbinfo['userid']);

	if ($mode == "save") {

			$userid  = $myid;
			$types   = mysql_real_escape_string($types);
			$v_date3 = mysql_real_escape_string($v_date3);
			$v_date4 = mysql_real_escape_string($v_date4);
			$rmemo   = mysql_real_escape_string($rmemo);
			$s_vcnt  = mysql_real_escape_string($s_vcnt);

			// 서버측 검증
			$err = "";
			if (!in_array($types, array('V','S','O'), true)) {
				$err = "신청타입(휴가/병가/무급)을 선택하세요.";
			} else if ($v_date3 == "" || $v_date4 == "") {
				$err = "휴가신청기간을 입력하세요.";
			} else if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v_date3) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v_date4)) {
				$err = "날짜 형식(YYYY-MM-DD)이 올바르지 않습니다.";
			} else if (strtotime($v_date3) > strtotime($v_date4)) {
				$err = "시작일이 종료일보다 늦을 수 없습니다.";
			} else if (trim($rmemo) == "") {
				$err = "신청사유를 입력하세요.";
			}
			if ($err != "") {
				Misc::jvAlert($err, "history.back()");
				exit;
			}

			// 신청일수 자동 보정 (비어있거나 숫자가 아니면 기간으로 계산)
			// ※ 반차(0.5) / 주말 제외 때문에 기간 일수보다 작게 넣는 것은 정상이므로 강제하지 않는다.
			//    다만 기간보다 많은 일수는 잘못된 입력이라 막는다.
			$periodDays = (strtotime($v_date4) - strtotime($v_date3)) / 86400 + 1;
			if ($s_vcnt === "" || !is_numeric($s_vcnt)) {
				$s_vcnt = $periodDays;
			} else if (floatval($s_vcnt) <= 0) {
				Misc::jvAlert("신청일수는 0보다 커야 합니다.", "history.back()");
				exit;
			} else if (floatval($s_vcnt) > $periodDays) {
				Misc::jvAlert("신청일수({$s_vcnt}일)가 신청기간({$periodDays}일)보다 많습니다.", "history.back()");
				exit;
			}

			// 기간이 겹치는 신청건이 이미 있으면 차단 (이중 신청 → 승인 시 잔여일수 이중 차감)
			$dupQry = "select seq_no, v_sdate, v_edate, r_status from emp_vacation
						where user_id = '$userid'
						  and r_status in ('신청중','승인완료','대표님승인완료')
						  and v_sdate <= '$v_date4' and v_edate >= '$v_date3'
						limit 1";
			$dupRow = mysql_fetch_assoc(mysql_query($dupQry));
			if ($dupRow) {
				Misc::jvAlert("이미 겹치는 신청건이 있습니다. ({$dupRow['v_sdate']} ~ {$dupRow['v_edate']} / {$dupRow['r_status']})", "history.back()");
				exit;
			}

			$qry1 = "INSERT INTO emp_vacation (
												  user_id,
												  v_type,
												  v_sdate,
												  v_edate,
												  r_vcnt,
												  r_date,
												  r_status,
												  r_memo,
												  wdate
												)
												VALUES
												  (
													'$userid',
													'$types',
													'$v_date3',
													'$v_date4',
													'$s_vcnt',
													null,
													'신청중',
													'$rmemo',
													now()
												  );";

				$rst1 = mysql_query($qry1);

				if($rst1)
				{
					 $goUrl_1 = "emp_vm.php?division=$division&pdx=$pdx&sub=$sub";
					 echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
					 exit;
				}
				Misc::jvAlert("저장에 실패했습니다. 다시 시도해 주세요.", "history.back()");
				exit;
	}

	// 취소/재신청/삭제 공통 처리
	// - where 에 user_id 를 함께 걸어 본인 신청건만 처리한다
	// - 승인완료 건은 이미 잔여일수가 차감된 상태라 본인이 손대지 못하게 막는다 (원복 로직이 없음)
	if ($mode == "rcan" || $mode == "req" || $mode == "rdel") {
			$rseq = mysql_real_escape_string($rid);
			$goUrl_1 = "emp_vm.php?division=$division&pdx=$pdx&sub=$sub";

			if ($mode == "rcan") {
				// 결재 대기중인 건만 취소 가능
				$qry1 = "update emp_vacation set r_status='취소'
							where seq_no='$rseq' and user_id='$myid' and r_status='신청중'";
				$failMsg = "결재 대기중인 신청만 취소할 수 있습니다.";
			} else if ($mode == "req") {
				// 취소/반려된 건만 재신청. r_date(결재일)는 결재 시에만 기록한다.
				$qry1 = "update emp_vacation set r_status='신청중', wdate=now(), r_date=null
							where seq_no='$rseq' and user_id='$myid' and r_status in ('취소','반려')";
				$failMsg = "취소 또는 반려된 신청만 다시 신청할 수 있습니다.";
			} else {
				$qry1 = "delete from emp_vacation
							where seq_no='$rseq' and user_id='$myid' and r_status not in ('승인완료','대표님승인완료')";
				$failMsg = "승인완료된 신청은 삭제할 수 없습니다. 관리자에게 문의하세요.";
			}

			mysql_query($qry1);
			if (mysql_affected_rows() < 1) {
				Misc::jvAlert($failMsg, "location.href='$goUrl_1'");
				exit;
			}

			echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
			exit;
	}
	$v_info = getinfo_dbMember($user_dbinfo['userid']);

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
						<a href="emp_vm.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>">직원휴가관리</a>
					</li>
				</ul>
			</div>

		<div class="row">
				<div class="col-sm-12 col-md-12">
					  <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" enctype="multipart/form-data" name="frmemp" id="frmemp" method="post" >
			           	  <input type=hidden name=mode id=mode value="save">
						  <input type=hidden name=division value="<?= $division ?>">
						  <input type=hidden name=userid value="<?= htmlspecialchars($v_info['userid']) ?>">
						  <input type=hidden name=rid id='rid' value="">

						<!-- 기본정보 (읽기전용) -->
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							        <tr>
										<td colspan=4 class="titletd text-left">&nbsp;<b>기본정보</b> <small class="text-muted">(읽기전용 — 수정은 인사관리에서)</small></td>
									</tr>
									<tr>
										<td width=15% class="titletd">부서/직급</td>
										<td width=35% class="conttd" colspan="3">&nbsp;
											<select class='inpubase md' disabled><?=printBaseCode_first('D02',$v_info['area_comp'])?></select>&nbsp;
											<select class='inpubase md' disabled><?=printBaseCode_first('D01',$v_info['c_part1'])?></select>&nbsp;
											<?= htmlspecialchars($v_info['c_part']) ?>
										</td>
									</tr>
								    <tr>
										<td width=15% class="titletd">이름(한글)</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['kor_name']) ?></td>
										<td width=15% class="titletd">이름(영문)</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['eng_name']) ?></td>
									</tr>
									<tr>
										<td width=15% class="titletd">휴대폰</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['cell_phone']) ?></td>
										<td width=15% class="titletd">일반전화</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['phone']) ?></td>
									</tr>
									<tr>
										<td width=15% class="titletd">비상연락처</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['reference']) ?></td>
										<td width=15% class="titletd">입사일</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['join_date']) ?></td>
									</tr>
									<tr>
										<td width=15% class="titletd">휴가기간</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['v_date1']) ?> &nbsp;~&nbsp; <?= htmlspecialchars($v_info['v_date2']) ?></td>
										<td width=15% class="titletd">휴가총일수</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['tot_vdate']) ?> 일</td>
									</tr>
									<tr>
										<td width=15% class="titletd">휴가잔여일수</td>
										<td width=35% class="conttd">&nbsp;<b class="text-primary"><?= htmlspecialchars($v_info['r_vdate']) ?></b> 일</td>
										<td width=15% class="titletd">미리사용휴가일수</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['use_vdate']) ?> 일</td>
									</tr>
									<tr>
										<td width=15% class="titletd">병가총일수</td>
										<td width=35% class="conttd" colspan=3>&nbsp;<?= htmlspecialchars($v_info['tot_sdate']) ?> 일</td>
									</tr>
									<tr>
										<td width=15% class="titletd">병가잔여일수</td>
										<td width=35% class="conttd">&nbsp;<b class="text-primary"><?= htmlspecialchars($v_info['r_sdate']) ?></b> 일</td>
										<td width=15% class="titletd">미리사용병가일수</td>
										<td width=35% class="conttd">&nbsp;<?= htmlspecialchars($v_info['use_sdate']) ?> 일</td>
									</tr>
							</tbody>
						</table>

						<!-- 신청사항 -->
						<table class="table table-striped table-bordered table-condensed">
						    <tbody>
							        <tr>
										<td colspan=4 class="titletd text-left">&nbsp;<b>신청사항</b>&nbsp;&nbsp;&nbsp;&nbsp;
											<label class="radio-inline"><input type="radio" name="types" id="types1" value="V">휴가신청</label>
											<label class="radio-inline"><input type="radio" name="types" id="types2" value="S">병가신청</label>
											<label class="radio-inline"><input type="radio" name="types" id="types3" value="O">무급휴가신청</label>
										</td>
									</tr>
									<tr>
										<td width=15% class="titletd">휴가신청기간</td>
										<td width=35% class="conttd">&nbsp;<input type=text name=v_date3 id='v_date3' readonly class='inpubase md' value="">&nbsp;~&nbsp;<input type=text name=v_date4 id='v_date4' readonly class='inpubase md' value=""></td>
										<td width=15% class="titletd">휴가신청일수</td>
										<td width=35% class="conttd">&nbsp;<input type=text name=s_vcnt id='s_vcnt' class='inpubase md' value="" style="width:70px;"> 일
											<small class="text-muted">&nbsp;기간에서 자동 계산됩니다. 반차·주말 제외는 직접 수정 (예: 0.5, 1.5)</small>
											<div id="remainMsg" class="text-danger" style="display:none;margin-top:4px;"></div>
										</td>
									</tr>
									<tr>
										<td colspan=4 class="titletd text-left">&nbsp;<b>신청사항사유</b></td>
									</tr>
									<tr>
										<td colspan=4 class="conttd"><textarea class="form-control" name="rmemo" rows=8></textarea></td>
									</tr>
							</tbody>
						</table>

						<table class="table table-bordered table-condensed">
						    <tbody>
									<tr>
										<td colspan=3 class="text-center" style="padding:8px;"><input type=submit value="휴가신청저장" class="btn btn-primary btn-sm"></td>
									</tr>
							</tbody>
						</table>

						<!-- 신청내역 -->
						<table class="table table-striped table-bordered table-condensed table-hover">
						    <thead>
                                <tr>
                                    <th class="table_info text-center">신청일</th>
									<th class="table_info text-center">신청타입</th>
                                    <th class="table_info text-center">신청기간</th>
									<th class="table_info text-center">일수</th>
									<th class="table_info text-center">신청사유</th>
									<th class="table_info text-center">신청상태</th>
									<th class="table_info text-center">결재일</th>
									<th class="table_info text-center">취소/삭제</th>
                                </tr>
                            </thead>
                            <tbody id="loop_area1">
                                <?php
									$listUid = mysql_real_escape_string($v_info['userid']);
									$qry1 = "SELECT * FROM emp_vacation WHERE user_id = '$listUid' order by wdate desc";
									$rst1 = mysql_query($qry1);
									while($request = mysql_fetch_assoc($rst1)){

                                        if ($request['r_status']=="신청중") {
											$cont = "<button type='button' class='btn btn-xs btn-warning btnrp' value='{$request['seq_no']}'>취소하기</button> <button type='button' class='btn btn-xs btn-danger btndel' value='{$request['seq_no']}'>삭제하기</button>";
										} else if ($request['r_status']=="취소") {
											$cont = "<button type='button' class='btn btn-xs btn-primary btnrr' value='{$request['seq_no']}'>신청하기</button> <button type='button' class='btn btn-xs btn-danger btndel' value='{$request['seq_no']}'>삭제하기</button>";
										} else if (in_array($request['r_status'], $VM_APPROVED)) {
											// 승인완료 = 잔여일수가 이미 차감된 상태. 되돌리려면 관리자 반려가 필요하다.
											$cont = "<span class='text-muted' title='승인완료 건은 관리자 반려로만 되돌릴 수 있습니다'><small>관리자 문의</small></span>";
										} else {
											$cont = "<button type='button' class='btn btn-xs btn-primary btnrr' value='{$request['seq_no']}'>재신청</button> <button type='button' class='btn btn-xs btn-danger btndel' value='{$request['seq_no']}'>삭제하기</button>";
										}
                                        if ($request['v_type']== "V") {
											$cap = "휴가신청";
                                        } else if ($request['v_type']== "S") {
											$cap = "병가신청";
										} else if ($request['v_type']== "O") {
											$cap = "무급휴가신청";
										} else {
											$cap = "-";
										}
                                        $reg_date = substr($request['wdate'],0,10);
										// r_date 는 결재일. 미결재 건은 null 또는 예전 데이터의 0000-00-00 이므로 '-' 로 표시
										$appr_date = substr($request['r_date'],0,10);
										if ($appr_date == "" || $appr_date == "0000-00-00") { $appr_date = "-"; }
                                ?>
                                <tr>
                                    <td class="table_info text-center"><?=$reg_date?></td>
									<td class="table_info text-center"><?=$cap?></td>
                                    <td class="table_info text-center" style="white-space:normal;"><?=$request['v_sdate']?> ~ <?=$request['v_edate']?></td>
									<td class="table_info text-center"><?=htmlspecialchars($request['r_vcnt'])?></td>
									<td class="table_info text-left"><?=nl2br(htmlspecialchars($request['r_memo']))?></td>
									<td class="table_info text-center"><?=vmStatusLabel($request['r_status'])?></td>
									<td class="table_info text-center"><?=$appr_date?></td>
									<td class="table_info text-center"><?=$cont?></td>
                                </tr>
                                <?php }?>
                            </tbody>
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

    </body>
	<script>
	var remainV = <?= is_numeric($v_info['r_vdate']) ? floatval($v_info['r_vdate']) : 0 ?>;
	var remainS = <?= is_numeric($v_info['r_sdate']) ? floatval($v_info['r_sdate']) : 0 ?>;

	$(document).ready(function() {

		var dpOpts = (window.pt && pt.defaults && pt.defaults.datepicker)
			? $.extend({}, pt.defaults.datepicker, { autoclose: true })
			: { format: "yyyy-mm-dd", autoclose: true };

		$('#v_date3').datepicker(dpOpts).on('changeDate', calcDays);
		$('#v_date4').datepicker(dpOpts).on('changeDate', calcDays);
	});

	// 신청일수 자동계산 (종료 - 시작 + 1)
	function calcDays(){
		var s = $('#v_date3').val(), e = $('#v_date4').val();
		if(s && e){
			var sd = new Date(s.replace(/-/g,'/')), ed = new Date(e.replace(/-/g,'/'));
			if(ed >= sd){
				var days = Math.round((ed - sd)/86400000) + 1;
				$('#s_vcnt').val(days);
				checkRemain();
			}
		}
	}

	// 잔여일수 초과 경고
	function checkRemain(){
		var days = parseFloat($('#s_vcnt').val()) || 0;
		var t = $('input[name=types]:checked').val();
		var msg = $('#remainMsg');
		if(t == 'V' && days > remainV){
			msg.html('잔여 휴가일수(' + remainV + '일)를 초과했습니다.').show();
		} else if(t == 'S' && days > remainS){
			msg.html('잔여 병가일수(' + remainS + '일)를 초과했습니다.').show();
		} else {
			msg.hide();
		}
	}

	$(document).on('change', 'input[name=types]', checkRemain);
	$(document).on('keyup', '#s_vcnt', checkRemain);

	// 저장 시 클라이언트 검증 (취소/삭제 등 다른 mode는 통과)
	$('#frmemp').on('submit', function(){
		if($('#mode').val() != 'save') return true;
		if(!$('input[name=types]:checked').length){ alert('신청타입을 선택하세요.'); return false; }
		if(!$('#v_date3').val() || !$('#v_date4').val()){ alert('휴가신청기간을 입력하세요.'); return false; }
		if(!$.trim($('textarea[name=rmemo]').val())){ alert('신청사유를 입력하세요.'); return false; }
		return true;
	});

	$(document).on('click', '.btnrp', function(){
		 if (confirm("취소하시겠습니까?")) {
			 $("#mode").val("rcan");
			 $("#rid").val($(this).val());
			 $("#frmemp").submit();
		 }
	});

	$(document).on('click', '.btnrr', function(){
		 if (confirm("신청하시겠습니까?")) {
			 $("#mode").val("req");
			 $("#rid").val($(this).val());
			 $("#frmemp").submit();
		 }
	});

	$(document).on('click', '.btndel', function(){
		 if (confirm("삭제 하시겠습니까?")) {
			 $("#mode").val("rdel");
			 $("#rid").val($(this).val());
			 $("#frmemp").submit();
		 }
	});
	</script>

</html>
