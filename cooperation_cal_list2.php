<?php
    include "include/header.php";
	//include "include/inc_base.php";

	if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
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

	/* =========================================================
	 * [중요 수정]
	 * - 예약 자체가 CANCEL 이면: 상태는 무조건 CANCEL
	 * - CANCEL 건은 어떤 합계/계산에도 포함하지 않음
	 * - CANCEL 건은 체크/일괄정산 불가(서버에서도 방어)
	 * ========================================================= */

	if ($mode1 == "save") {

		for($i=0; $i<count($seqNo); $i++)
		{
			$s = $seqNo[$i];

			// ✅ 서버 방어: CANCEL/READY/이미정산건은 저장(정산)하지 않음
			if ($sta[$s] == "CANCEL" || $sta[$s] == "READY" || $sta2[$s] == "SETTLEDONE") {
				continue;
			}

			if ($sdivi[$s] == 'credit') {
				$amt = $amta[$s];
			} else {
				$amt = -($amta[$s]);
			}

			$qry1= "insert into prtadmindb.rand_pay 
											( 
											rand_id, 
											reserveCode,
											rand_date,
											stDate,
											tr_date, 
											tr_type, 
											tr_bank, 
											trans_rate, 
											trans_type, 
											pay_method, 
											payment, 
											r_payment, 
											set_memo, 
											u_id, 
											seq_rand,
											wdate
											)
											values
											( 
											'$randid[$s]', 
											'$sreserveCode[$s]', 
											'$rdate[$s]',
											'$sdate[$s]',
											'$calStartDate', 
											'', 
											'$bankSelect', 
											'', 
											'$sdivi[$s]', 
											'$paymentMethod', 
											'$amt', 
											'', 
											'$calMemo', 
											'{$user_dbinfo['userid']}',
											'$seq[$s]',
											now()
											);";
			$rst1 = mysql_query($qry1,$dbConn);

			$qry2 = "update rand_company 
						set cur_amt = '$amt' ,status='SETTLEDONE',settle_memo='$calMemo' 
						where part_id = '$randid[$s]' && seq_no = '$seq[$s]'";
			$rst2 = mysql_query($qry2);

		}
		Misc::jvAlert("저장 완료!!!");
	}

	function printsettle(){
			global $dbConn,$division,$g_nm,$pdx,$sub,$rand_id,$stm,$EndYMD,$ctot,$dtot,$pcnt,$flag,$k,$pamtc,$pamtd,$totpamtc,$totpamtd,$dtot1,$ctot1;

			if ($EndYMD == "") {
				$EndYMD = $stm;
			}

			if (($flag == '1') || ($flag == '')) {
				$flag_qry = "&& (date_format(b.stDate, '%Y-%m') >= '$stm' && date_format(b.stDate, '%Y-%m') <= '$EndYMD')";
			} else {
				$flag_qry = "&& date_format(b.revDate, '%Y-%m') = '$stm'";
			}

		    $qry1 = "select a.seq_no as seqr,a.*,b.*
				from rand_company a, reserve_info b
				where a.reserveCode=b.reserveCode 
					&& a.part_id = '$rand_id'  
					&& p_code not in ('PICKUP','SENDING') 
					&& b.parent='MAIN'
					&& a.reserveCode is not null  
					$flag_qry  
				order by a.wdate desc";

			$rst1 = mysql_query($qry1,$dbConn);
			$k=0;

			while($row1 = mysql_Fetch_assoc($rst1)){

				$recus = getReserveTrRepre($row1['reserveCode']);
				$reserve_info = getReserveInfo($row1['reserveCode']);

				$rdate = $row1['revDate'];
				$sdate = $row1['stDate'];

				/* =========================
				 * ✅ CANCEL 예약 처리 (핵심)
				 * - 상태는 무조건 CANCEL
				 * - 어떤 합계/계산에도 포함하지 않음
				 * - 체크/정산 불가
				 * ========================= */
				$isCancel = ($reserve_info['rev_status'] == "CANCEL");

				if ($isCancel) {
					// 표시용(원래 금액은 보여주고 싶으면 아래 주석 해제)
					// $damt = ($row1[money_type] == "debit") ? "<font color=red>\${$row1['amt']}</font>" : "";
					// $camt = ($row1[money_type] == "credit") ? "<font color=blue>\${$row1['amt']}</font>" : "";
					$damt = "";
					$camt = "";

					$pamt = ""; // 페이먼트 표시도 안 함(원하면 "$$row1[cur_amt]"로 표시 가능)

					$status_msg = "CANCEL";

					$chkDisabled = "disabled";
					$chkOnclick  = "";
				} else {

					$chkDisabled = "";
					$chkOnclick  = "onclick='GoCheck($k);'";

					if ($row1['money_type'] == "credit") {
							$camt = "<font color=blue>\${$row1['amt']}</font>";
							$damt = 0;
					} else {
							$damt = "<font color=red>\${$row1['amt']}</font>";
							$camt = 0;
					}

					// ✅ 합계는 READY/CANCEL 제외 (기존 유지)
					if (($row1['money_type'] == "credit") && ($reserve_info['rev_status'] != "READY") && ($reserve_info['rev_status'] != "CANCEL")) {
							$ctot = $ctot + $row1['amt'];
							$pamtc= $pamtc + $row1['cur_amt'];
					} else if (($row1['money_type'] == "debit") && ($reserve_info['rev_status'] != "READY") && ($reserve_info['rev_status'] != "CANCEL")) {
							$dtot = $dtot + $row1['amt'];
							$pamtd= $pamtd + -($row1['cur_amt']);
					}

					if (($row1['money_type'] == "credit") && ($reserve_info['rev_status'] == "READY")) {
							$ctot1 = $ctot1 + $row1['amt'];
					} else if (($row1['money_type'] == "debit") && ($reserve_info['rev_status'] == "READY")) {
							$dtot1 = $dtot1 + $row1['amt'];
					}

					$totpamtc = $ctot - $pamtc;
					$totpamtd = $dtot - $pamtd;

					// ✅ 인원 합계도 CANCEL 제외 (수정)
					$pcnt = $pcnt + $row1['p_cnt'];

					if (($row1['cur_amt'] == "") || ($row1['cur_amt'] == "0")) {
						$pamt = "";
					} else {
						$pamt = "\${$row1['cur_amt']}";
					}

					$uinfo=getinfo_dbMember($row1['userid']);

					switch($reserve_info['rev_status']) {
						case "READY":
							$status_msg = "PENDING";
							break;
						default:
							if ($row1['status'] == 'SETTLEDONE') {
								$status_msg = "<span style='color:blue;'>정산완료</span>";
							} else {
								$status_msg = $row1['rev_status'];
							}
							break;
					}
				}

				$uinfo = $uinfo ? $uinfo : getinfo_dbMember($row1['userid']);
				$paym  = getPaymemo($rand_id,$row1['reserveCode'],$row1['seqr']);

				echo "<tr>
							<td align='center'>
								<input type='checkbox' name=seqNo[] id='seq_rno' value='$k' class='form-control' $chkDisabled $chkOnclick>
								<input type=hidden name=amta[] value='{$row1['amt']}'>
								<input type=hidden name=balamt[] value='{$row1['cur_amt']}'>
								<input type=hidden name='seq[]' id='seqr' value='{$row1['seqr']}'>
								<input type=hidden name='rdate[]' id='rdate' value='$rdate'>
								<input type=hidden name='sdate[]' id='sdate' value='$sdate'>
							</td>
							<td align='center'>
								$rdate<br/>{$row1['reserveCode']}
								<input type=hidden name='sreserveCode[]' id='sreserveCode' value='{$row1['reserveCode']}'>
								<input type=hidden name='sdivi[]' id='sdivi' value='{$row1['money_type']}'>
								<input type=hidden name='randid[]' id='randid' value='{$row1['part_id']}'>
							</td>
							<td align='center'>{$row1['p_cnt']}</td>
							<td>{$row1['p_name']}<br/>{$row1['stDate']}~{$row1['edDate']}</td>
							<td>{$row1['stDate']}</td>
							<td align='center'>{$recus['traveler_nm']}</td>
							<td align='right'>$damt</td>
							<td align='right'>$camt</td>
							<td align='right'>$pamt</td>
							<td align='center'>{$uinfo['kor_name']}</td>
							<td align='center'>
								$status_msg
								<input type=hidden name='sta[]' id='sta' value='{$reserve_info['rev_status']}'>
								<input type=hidden name='sta2[]' id='sta2' value='{$row1['status']}'>
							</td>
							<td>
								<input type=hidden name='pmemo[]' id='pmemo' value='{$row1['p_memo']}'>
								{$row1['p_memo']}<br>{$row1['settle_memo']}<br>$paym
							</td>
							<td align='center'>
								<button type=button name=in[] value='{$row1['part_id']}/{$row1['reserveCode']}' class='btn btn-xs btn-default js-pay' onClick=javascript:openwin('{$row1['part_id']}','{$row1['reserveCode']}','{$row1['seqr']}','$rdate','$sdate')>PAY</button>
								<button type=button name=in2[] value='{$row1['part_id']}|{$row1['reserveCode']}|{$row1['seqr']}' class='btn btn-xs btn-default js-rpay'>RESET</button>
							</td>
						</tr>";

				$k++;
			}
	}

	if ($EndYMD == "") {
		$EndYMD = $stm;
	}
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">업체정산</a></li>
					<li>업체별정산현황</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action=""  method="post" name="frmName">
					<input type="hidden" name="mode" id="mode" value="save">
						<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                <tr>
                                    <td colspan="2" class="active text-center formHeader">업체명</td>
                                    <td colspan="3">   
                                        <select class="form-control comp" name="rand_id" id="rand_id" >
											<option value="">- 업체를 선택하세요 -
											<?=printCompanySelect($rand_id)?>

										</select>
                                    </td>
                                    
                                    <td colspan="2" class="active text-center formHeader">출발일기준</td>
                                    <td colspan="4">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="stm" id="stm" data-date-format='yyyy-mm' class="form-control tourdate1" aria-label="조회기간" placeholder="조회기간" autocomplete="off" value="<?=$stm ?>">
                                                    <span class="input-group-btn">
                                                        &nbsp;
                                                    </span> 
													<input type="text" name="EndYMD" id="EndYMD" data-date-format='yyyy-mm' class="form-control tourdate2" aria-label="조회기간" placeholder="조회기간" autocomplete="off" value="<?=$EndYMD ?>">
                                                    <span class="input-group-btn">
                                                        &nbsp;
                                                    </span>
                                                </div>

                                            </div>
                                        </div>
                                    </td>
                                    <td colspan="1" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                                    <td colspan="2" class="text-center"><button type='button' class="btn btn-xs btn-default js-in">인보이스출력</button></td>
                                </tr>
                            </tbody>
                        </table>
					</form>
					<br />
					<form action="<?php $PHP_SELF;?>?division=6&pdx=4&sub=10&sell=<?=$sell?>&rand_id=<?=$rand_id?>&stm=<?=$stm?>&flag=<?=$flag?>" id="frmbb" name="frmbb"  method="post">
					<input type="hidden" name="mode1" id="mode1" value="save">
						<div class="row">
							<div class="col-sm-12">
								<table class="table table-striped table-bordered table-hover table-condensed js-settleTable">
									<thead>
										<tr>
											<th width="1%" align="center"><input type="checkbox" id='selectAll' clas="form-control"></th>
											<th>날짜</th>
											<th>인원</th>
											<th>상품명</th>
											<th>출발일</th>
											<th>대표고객명</th>
											<th>지출예정</th>
											<th>입금예정</th>
											<th>페이먼트</th>
											<th>접수자</th>
											<th>정산상태</th>
											<th width="20%">메모</th>
											<th>결제</th>
										</tr>
									</thead>
									<tbody>
										<?=printsettle()?>
									</tbody>
								</table>
							</div>
						</div>
						
						<br/>
						<div class="row">
							<div class="col-sm-12">
								<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
									<tbody>
										<tr>
											<td colspan="2" class="text-center">총인원</td>
											<td colspan="2" align='center'><?=$pcnt?> 명</td>
											<td colspan="2" class="text-center">총합계</td>
											<td colspan="10">
												<div class="row">
													<div class="col-sm-12"></div> 
												</div>
												<div class="row">
													<div class="col-sm-12">
													   <div class="col-sm-4 text-center">지출</div>
													   <div class="col-sm-4 text-center">입금</div>
													   <div class="col-sm-4 text-center">가예약</div>
													</div> 
												</div>
												<div class="row">
													<div class="col-sm-12">
													   <div class="col-sm-4 text-right">
														   <div class="col-sm-4 text-right">예정</div>
														   <div class="col-sm-8 text-right"><font color=red>$-<?=number_format($dtot,2)?></font></div> 
													   </div>
													   <div class="col-sm-4 text-right" >$<?=number_format($ctot,2)?></div>
													   <div class="col-sm-4 text-right">
														   <div class="col-sm-4 text-left">지출</div>
														   <div class="col-sm-8 text-right"><font color=red>$-<?=number_format($dtot1,2)?></font></div> 
													   </div>
													</div> 
												</div>   
												<div class="row">
													<div class="col-sm-12">
													   <div class="col-sm-4 text-right">
														   <div class="col-sm-4 text-right">페이먼트</div>
														   <div class="col-sm-8 text-right">$<?=number_format($pamtd,2)?></div> 
													   </div>
													   <div class="col-sm-4 text-right" >$<?=number_format($pamtc,2)?></div>
													   <div class="col-sm-4 text-right">
														   <div class="col-sm-4 text-left">입금</div>
														   <div class="col-sm-8 text-right"><font color=blue>$<?=number_format($ctot1,2)?></font></div> 
													   </div>
													</div> 
												</div>  
												<div class="row">
													<div class="col-sm-12">

														<div class="col-sm-4 text-right"><hr class='dotted' /><div class="col-sm-4 text-left">총합계</div>$-<?=number_format($totpamtd,2)?>&nbsp;</div>
														<div class="col-sm-4 text-right"><hr class='dotted' />$<?=number_format($totpamtc,2)?>&nbsp;</div>
														<div class="col-sm-4 text-right"><hr class='dotted' /></div> 
														
													</div> 
												</div>
											</td>
										</tr>
									</tbody>
								</table>   
								<br/>
								<div class="row no-nav">
									<div class="col-sm-12 text-left">
										<button type="button" class="btn btn-xs btn-primary js-batch">일괄정산처리</button>
									</div>
								</div>
								<br />
								<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
									<tbody>
										<tr>
											<td colspan="2" class="titletd text-center">거래일자</td>
											<td colspan="14">
												<div class="row">
													<div class="col-sm-4">
														<input type="date" name="calStartDate" class="inpubase tourDate" value="<?=date("Y-m-d")?>"/>
													</div>
												</div>
											</td>
										</tr>
									   
										<tr>                    			
											<td colspan="2" class="active text-center formHeader">선택입금예정금액</td>
											<td colspan="6">
												<div class="row">
													<div class="col-sm-6">
														<div class="input-group input-group-sm">
															<input type="text" name="depositAmount1" class="inpubase" aria-label="선택입금예정금액" placeholder="선택입금예정금액" readOnly value=0>
															
														</div>
													</div>
												</div>    
											</td>
											<td colspan="2" class="active text-center formHeader">선택지출예정금액</td>
											<td colspan="6">
												<div class="row">
													<div class="col-sm-6">
														<div class="input-group input-group-sm">
															<input type="text" name="spendAmount1" class="inpubase" aria-label="선택지출예정금액" placeholder="선택지출예정금액" readOnly value=0>
															 
														</div>
													</div>
												</div>    
											</td>
										</tr>
										<tr>                    			
											<td colspan="2" class="active text-center formHeader">결제방법</td>
											<td colspan="6">
											  
												<select class="form-control" name="paymentMethod" id="paymentMethod">
													<option selected value="">-결제방법-</option>
													<option value="check">체크</option>
													<option value="cash">현금</option>
													<option value="credit">크레딧카드</option>
													<option value="wireko">한국 계좌 송금</option>
													<option value="wireus">미국 계좌 송금</option>
												</select>
											</td>
											<td colspan="2" class="active text-center formHeader">거래은행</td>
											<td colspan="6">
												<select class="form-control" name="bankSelect" id="bankSelect">
													<option selected>-은행명-</option>
													<option value="chase">Chase Bank</option>
													<option value="pnc">PNC Bank</option>
													<option value="capital">Capital One</option>
													<option value="hana1">(한국) 하나은행 원화계좌</option>
													<option value="hana2">(한국) 하나은행 다국적계좌</option>
												</select>    
											</td>
										</tr>
										<tr>
											<td colspan="2" class="active text-center formHeader">정산메모</td>
											<td colspan="14">
												<input type="text" name="calMemo" class="form-control" aria-label="정산메모" value=""/> 
											</td>
										</tr>
									   
									</tbody>
								</table> 
						   </div>
						</div> 
					</form>
				</div><!-- -->
			</div>                
		</div>
	</div>

   <!--modal -->
   <div class="modal fade js-openCooperationCal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        <div class="modal-dialog modal-lg modal-full-width" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="gridSystemModalLabel">정산처리</h4>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <div class="row">
                            <div class="col-sm-12">
                                <!-- (모달 내부는 샘플 UI라 원본 유지) -->
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include "include/side_m.php" ?>

    <script>
		$(document).ready(function () {

			$('.tourdate1').datepicker({
				format: "yyyy-mm",
				viewMode: "months",
				minViewMode: "months",
				autoclose: true
			});
			$('.tourdate2').datepicker({
				format: "yyyy-mm",
				viewMode: "months",
				minViewMode: "months",
				autoclose: true
			});

            var oTable = $('.js-settleTable').dataTable({
				stateSave: true,
				pageLength: 100,
				dom: 'Bfrtip',
				buttons: ['copy', 'csv', 'excel', 'print']
			});

			var allPages = oTable.fnGetNodes();

			$('body').on('click', '#selectAll', function () {
			   var FormChkObj = document.getElementsByName("seqNo[]");
			   var FormamtObj = document.getElementsByName("amta[]");
			   var FormstaObj = document.getElementsByName("sta[]");
			   var FormstaObj2 = document.getElementsByName("sta2[]");
			   var FormdivObj = document.getElementsByName("sdivi[]");

			   var camt = 0;
			   var damt = 0;
			   var csumamt = 0;
			   var dsumamt = 0;
			   var csumamt1 = 0;
			   var dsumamt1 = 0;
			   var chklength = $("input[name='seqNo[]']").length;

			   csumamt = parseFloat($("input[name='depositAmount1']").val());
			   dsumamt = parseFloat($("input[name='spendAmount1']").val());

			   for(var k=0;k < parseInt(chklength);k++)
			   {
					// ✅ CANCEL은 무조건 제외 + READY 제외 + 정산완료 제외
					if ((FormstaObj['k'].value != "CANCEL") && (FormstaObj2['k'].value != "SETTLEDONE") && (FormstaObj['k'].value != "READY"))
					{
					   if(FormChkObj['k'].checked == false){
							camt=parseFloat(FormamtObj['k'].value);
							damt=parseFloat(FormamtObj['k'].value);
							if (FormdivObj['k'].value == "credit")
							{
								csumamt1 = csumamt1 + camt;
								$("input[name='depositAmount1']").val(csumamt1);
							} else {
								dsumamt1 = dsumamt1 + damt;
								$("input[name='spendAmount1']").val(dsumamt1);
							}
							FormChkObj['k'].checked = true;

					   } else {
							if (FormdivObj['k'].value == "credit")
							{
								camt=parseFloat(FormamtObj['k'].value);
								if (csumamt >=0) csumamt1 = csumamt - camt;
								else csumamt1 = 0;

								$("input[name='depositAmount1']").val(csumamt1);
							} else {
								damt=parseFloat(FormamtObj['k'].value);
								if (dsumamt >=0) dsumamt1 = dsumamt -  damt;
								else dsumamt1 = 0;

								$("input[name='spendAmount1']").val(dsumamt1);
							}
							FormChkObj['k'].checked = false;
					   }

					} else {
						FormChkObj['k'].checked = false;
					}
			   }
			})

			$(".dataTables_length").css({ "display" :"none" });

			$('body').on('click', '.js-batch', function () {
				if (confirm("일괄정산 하시겠습니까?"))
				{
					$("#mode1").val("save");
				    $("#frmbb").submit();
				}
			})

			$('body').on('click','.js-rpay',function() {
				if(confirm("리셋할까요?") == true){
					var str= $(this).val();
					var result = str.split('|');
					var reserveCode = result[1];
					var seqNo1 = result[2];

					$.getJSON("reset_pay.php?reserveCode="+reserveCode+"&seqNo1="+seqNo1, function(jsonData){
						 alert("페이먼트가 리셋되었습니다.");
						 location.reload();
					});
				}
				return ;
			});

			$('body').on('click', '.js-in', function () {

				var f = document.frmbb;
				var rand_id = $("#rand_id").val();

				var s_date1 = $("#stm").val();
				var s_date2 = $("#EndYMD").val();
				var FormChkObj = document.getElementsByName("seqNo[]");
				var FormstaObj = document.getElementsByName("sta[]");
				for (var orderidx = 0; orderidx<FormChkObj.length; orderidx++) {
					if (FormChkObj['orderidx'].checked==true) {
						if ((FormstaObj['orderidx'].value == 'CANCEL')) {
							alert('선택하신 항목중 취소된 정산이 있습니다.확인후 다시하세요!!');
							FormChkObj['orderidx'].setFocus;
							return;
						 }
					}
				}
				if (confirm("인보이스를 출력 하시겠습니까?"))
				{
					f.target = "invoice";
					f.action = "in.php?rand_id=" + rand_id + "&s_date1=" + s_date1 + "&s_date2=" + s_date2+"";
					popup= window.open("","invoice","width=900,height=600,scrollbars=1");
					popup.focus();
					f.submit();
				}
			})

			$(".comp").chosen({});
		})

		var ctr=0;
        function openwin(randid,rev,seqr,rdate,sdate) {
	       var winName = "all_"+(ctr++);
		   window.open("paysettle.php?rand_id="+randid+"&seqNo="+seqr+"&rev="+rev+"&rdate="+rdate+"&sdate="+sdate,winName,"width=800,height=600,scrollbars=1");
	    }

		function GoCheck(k) {
		   var FormChkObj = document.getElementsByName("seqNo[]");
		   var FormamtObj = document.getElementsByName("amta[]");
		   var FormstaObj = document.getElementsByName("sta[]");
		   var FormstaObj2 = document.getElementsByName("sta2[]");
		   var FormdivObj = document.getElementsByName("sdivi[]");

		   var camt = 0;
		   var damt = 0;
		   var csumamt = 0;
		   var dsumamt = 0;
		   var csumamt1 = 0;
		   var dsumamt1 = 0;
		   csumamt = parseFloat($("input[name='depositAmount1']").val());
		   dsumamt = parseFloat($("input[name='spendAmount1']").val());

	       if ((FormstaObj['k'].value != "CANCEL") && (FormstaObj2['k'].value != "SETTLEDONE") && (FormstaObj['k'].value != "READY"))
			{
			   if(FormChkObj['k'].checked == true){
					camt=parseFloat(FormamtObj['k'].value);
					damt=parseFloat(FormamtObj['k'].value);
					if (FormdivObj['k'].value == "credit")
					{
						csumamt1 = csumamt + camt;
						$("input[name='depositAmount1']").val(csumamt1);
					} else {
						dsumamt1 = dsumamt + damt;
						$("input[name='spendAmount1']").val(dsumamt1);
					}
					FormChkObj['k'].checked = true;

			   } else {
				    if (FormdivObj['k'].value == "credit")
					{
						camt=parseFloat(FormamtObj['k'].value);
						if (csumamt >=0) csumamt1 = csumamt - camt;
						else csumamt1 = 0;

						$("input[name='depositAmount1']").val(csumamt1);
					} else {
						damt=parseFloat(FormamtObj['k'].value);
						if (dsumamt >=0) dsumamt1 = dsumamt -  damt;
						else dsumamt1 = 0;

						$("input[name='spendAmount1']").val(dsumamt1);
					}
					FormChkObj['k'].checked = false;
			   }
			} else {
				FormChkObj['k'].checked = false;
			}
	    }
	</script>
    </body>
</html>
