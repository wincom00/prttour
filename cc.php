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
	if ($mode1 == "save") {

		for($i=0; $i<count($seqNo); $i++)
		{
			$s = $seqNo[$i];
			//print_r($s);
			if ($sdivi[$s] == 'credit') {
				$amt = $amta[$s];
			} else {
				$amt = -($amta[$s]);
			}
			$qry1= "insert into prtadmindb.rand_pay 
											( 
											rand_id, 
											reserveCode, 
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
											wdate
											)
											values
											( 
											'$randid[$s]', 
											'$sreserveCode[$s]', 
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
											now()
											);";
			//echo $qry1;
			//exit;
			$rst1 = mysql_query($qry1,$dbConn);
			$qry2 = "update rand_company set cur_amt = '$amt' ,status='SETTLEDONE',settle_memo='$calMemo' where part_id = '$randid[$i]' && seq_no = '$seq[$s]'";
			$rst2 = mysql_query($qry2);
			//echo $qry2;
			//exit;
		}
		Misc::jvAlert("저장 완료!!!");


	}
	function printsettle(){
			
			global $dbConn,$division,$g_nm,$pdx,$sub,$rand_id,$stm,$ctot,$dtot,$pcnt,$flag,$k;
			
			//echo $type1;
			if (($flag == '1') || ($flag == '')) {

				$flag_qry = "&& date_format(b.stDate, '%Y-%m') = '$stm'";
			} else {
				$flag_qry = "&& date_format(b.revDate, '%Y-%m') = '$stm'";
			}
		    $qry1 = "select a.seq_no as seqr,a.*,b.*
				from rand_company a, reserve_info b
				where a.reserveCode=b.reserveCode && a.part_id = '$rand_id'  && p_code not in ('PICKUP','SENDING') && b.parent='MAIN'
				&& a.reserveCode is not null  $flag_qry  order by a.wdate desc";
			
			//'echo $qry1;
			$rst1 = mysql_query($qry1,$dbConn);
			$k=0;
			while($row1 = mysql_Fetch_assoc($rst1)){
			
				$recus = getReserveTrRepre($row1['reserveCode']);
				$reserve_info = getReserveInfo($row1['reserveCode']);
				if ($row1['money_type'] == "credit") {
						$camt = "<font color=blue>\${$row1['amt']}</font>";
						$damt = 0;
						$ctot = $ctot + $row1['amt'];
				} else {
						$damt = "<font color=red>\${$row1['amt']}</font>";
						$camt = 0;
						$dtot = $dtot + $row1['amt'];
				}
				$pcnt = $pcnt + $row1['p_cnt'];
				if ($row1['cur_amt'] == "") {
					$pamt = "";
				} else {
					$pamt = "\${$row1['cur_amt']}";
				}
				
				$uinfo=getinfo_dbMember($row1['userid']);
				
				$rand_balance = getRandBalance2($row1['part_id'],$row1['reserveCode']);

				//$rand_cbalance = getCRandBalance2($row1[part_id],$row1[reserveCode]);

				//$rand_dbalance = getDRandBalance2($row1[part_id],$row1[reserveCode]);

				$randbamt = $rand_cbalance - $rand_dbalance;

				$randtotbal = $randtotbal + $randbamt;

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
				//echo $reserve_info[rev_status]."<br>";
				echo "<tr>
							<td align='center'><input type='checkbox' name=seqNo[] id='seq_rno' value='$k' class='form-control' onclick='GoCheck($k);'><input type=hidden name=amta[] value='{$row1['amt']}'><input type=hidden name=balamt[] value='{$row1['cur_amt']}'><input type=hidden name='seq[]' id='seqr' value='{$row1['seqr']}'></td>
							<td align='center'>{$row1['stDate']}<br/>{$row1['reserveCode']}<input type=hidden name='sreserveCode[]' id='sreserveCode' value='{$row1['reserveCode']}'><input type=hidden name='sdivi[]' id='sdivi' value='{$row1['money_type']}'><input type=hidden name='randid[]' id='randid' value='{$row1['part_id']}'></td>
							<td align='center'>{$row1['p_cnt']}</td>
							<td>{$row1['p_name']}<br/>{$row1['stDate']}~{$row1['edDate']}</td>
							<td align='center'>{$recus['traveler_nm']}</td>
							
							<td align='right'>$damt</td>
							<td align='right'>$camt</td>
							<td align='right'>$pamt</td>
							<td align='center'>{$uinfo['kor_name']}</td>
							<td align='center'>$status_msg<input type=hidden name='sta[]' id='sta' value='{$row1['rev_status']}'><input type=hidden name='sta2[]' id='sta2' value='{$row1['status']}'></td>
							<td>{$row1['settle_memo']}</td>
							<td align='center'><button type=button name=in[] value='{$row1['part_id']}/{$row1['reserveCode']}' class='btn btn-xs btn-default js-pay' onClick=javascript:openwin('{$row1['part_id']}','{$row1['reserveCode']}','{$row1['seqr']}')>PAY</button></td>
						</tr>";

				$k++;
			}
			//echo "<input type=hidden name='cnt' id='cnt' value='$k'>";

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
                                    <td colspan="2" class="active text-center formHeader">지역별조회</td>
                                    <td colspan="2">   
                                        <select class="form-control" name="company_area">
                                            <option value="" selected>전체</option>
                                            <?= printBaseCode4_without('A01',$company_area); ?>
                                        </select>
                                    </td>
                                    <td colspan="2" class="active text-center formHeader">협력사명</td>
                                    <td colspan="2"><input type="text" name="companyName" class="form-control" aria-label="협력사명입력" placeholder="협력사명" value="" /></td>
                                    <td colspan="2" class="active text-center formHeader">출발일기준</td>
                                    <td colspan="2">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="singleDayTourStartDate" data-date-format='yyyy-mm-dd' class="form-control js-singleDayTourDate js-singleDayTourDate1" aria-label="출발일" placeholder="출발일">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td colspan="1" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                                    <td colspan="3" class="text-center"><button type='button' class="btn btn-xs btn-default js-xxx">인보이스출력</button></td>
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
								<table  id='level4'  class='txt_12' width='100%' align=center border='0' cellspacing='1'  cellpadding='0'>
									<tr height=10>
												<td width=4% align=center></td>
												<td width=13% align=center></td>
												<td width=25% align=center></td>
												<td width=7% align=center></td>
												<td width=7% align=center></td>
												<td width=13% align=center></td>
												<td align=center></td>
									</tr>
									<tr height=35>
										<td align=center>&nbsp;</td>
										<td align=right><font color=red>총인원:&nbsp;<b><?=$pcnt?> 명</b></font></td>
										<td align=center>&nbsp;</td>
										<td align=right style='font-size:8pt;'>DEBIT:&nbsp;<b><br><?=$pcnt?></b>&nbsp;<br><b><?=$pcnt?></b>&nbsp;<br>------------------<br><b>$deb_bal</b>&nbsp;</td>
										<td align=right style='font-size:8pt;'>CREDIT:&nbsp;<b><br><?=$pcnt?></b>&nbsp;<br><b><?=$pcnt?></b>&nbsp;<br>------------------<br><b><?=$pcnt?></b>&nbsp;</td>
										<td align=right style='font-size:8pt;'>TOTAL BALANCE:&nbsp;<b><br>$deb_bal</b>&nbsp;<br><b><?=$pcnt?></b>&nbsp;<br>--------------------<br><b><?=$pcnt?></b>&nbsp;</td>
										<td align=center>&nbsp;</td>
								</tr></table>   
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
															<input type="text" name="depositAmount1" class="inpubase" aria-label="선택입금예정금액" placeholder="선택입금예정금액" value=0>
															
														</div>
													</div>
												</div>    
											</td>
											<td colspan="2" class="active text-center formHeader">선택지출예정금액</td>
											<td colspan="6">
												<div class="row">
													<div class="col-sm-6">
														<div class="input-group input-group-sm">
															<input type="text" name="spendAmount1" class="inpubase" aria-label="선택지출예정금액" placeholder="선택지출예정금액" value=0>
															
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
                                <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="active text-center formHeader">업체명</td>
                                            <td colspan="6">홍길동 투어_캐나다</td>
                                            <td colspan="2" class="active text-center formHeader">행사명</td>
                                            <td colspan="6">퀘벡2박3일</td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">예약코드/고객명</td>
                                            <td colspan="6">TD2018051800003 아무개</td>
                                            <td colspan="2" class="active text-center formHeader">거래예정금액</td>
                                            <td colspan="6">C$3000</td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">거래타입</td>
                                            <td colspan="14">
                                                <label class="radio-inline">
                                                    <input type="radio" name="dealType" value=""> 크레딧(업체로부터 수금)
                                                </label>
                                                <label class="radio-inline">
                                                    <input type="radio" name="dealType" value=""> 데빗(업체에게 지급)
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">거래일자</td>
                                            <td colspan="14">
                                                <input type="text" id="" name="popStartDate" class="inpubase tourDate" value="" placeholder="출발일"/>
                                            </td>
                                        </tr>
                                        <tr>       
                                            
                                            <td colspan="2" class="active text-center formHeader">거래금액</td>
                                            <td colspan="14">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="input-group input-group-sm">
                                                            
                                                            <input type="text" class="form-control" placeholder="거래금액" aria-label="거래금액">
                                                        </div>
                                                    </div>
                                                </div>    
                                            </td>
                                        </tr>
                                        <tr>       
                                            <td colspan="2" class="active text-center formHeader">결제방법</td>
                                            <td colspan="6">
                                                <select class="form-control" name="popPayment">
                                                    <option selected>은행송금</option>
                                                    <option value="">현금</option>
                                                    <option value="">데빗</option>
                                                </select>
                                            </td>
                                            <td colspan="2" class="active text-center formHeader">거래은행</td>
                                            <td colspan="6">
                                                <select class="form-control" name="popBank">
                                                    <option selected>은행명</option>
                                                    <option value="">1</option>
                                                    <option value="">2</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="active text-center formHeader">정산메모</td>
                                            <td colspan="14">
                                                <input type="text" name="popMemo" class="form-control" aria-label="정산메모" value=""/> 
                                            </td>
                                        </tr>
                                        <tr>
                                           <td colspan="16" class="text-center"><button type="submit" class="btn btn-xs btn-default js-xxx">결제하기</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br />
                                <div class="row">
                                    <div class="col-sm-12">
                                        <table class="table table-striped table-bordered table-hover table-condensed js-productTable">
                                            <thead>
                                                <tr>
                                                    <th>거래일자</th>
                                                    <th>거래타입</th>
                                                    <th>결제방법</th>
                                                    <th>거래금액</th>
                                                    <th>거래은행</th>
                                                    <th>정산메모</th>
                                                    <th>등록자</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td align="center">2018-09-12</td>
                                                    <td align="center">크래딧</td>
                                                    <td align="center">은행송금</td>
                                                    <td align="right">C$1000</td>
                                                    <td align="center">푸른은행</td>
                                                    <td >은행결제완료</td>
                                                    <td align="center">김일동</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
   
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
            //$.ajaxSetup({async:false});
            var oTable = $('.js-settleTable').dataTable({
				stateSave: true,
				pageLength: 100,
				dom: 'Bfrtip',
				buttons: [
					'copy', 'csv', 'excel', 'print'
				]
				
			});

			var allPages = oTable.fnGetNodes();

			$('body').on('click', '#selectAll', function () {
			   var FormChkObj = document.getElementsByName("seqNo[]"); 	
			   var FormamtObj = document.getElementsByName("amta[]");
			   var FormbalObj = document.getElementsByName("balamt[]"); 
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
			   //alert(FormChkObj[0].value);
			   csumamt = parseFloat($("input[name='depositAmount1']").val());
			   dsumamt = parseFloat($("input[name='spendAmount1']").val());
			   for(var k=0;k < parseInt(chklength);k++)
			   {
					if ((FormstaObj['k'].value != "CANCEL") && (FormstaObj2['k'].value != "SETTLEDONE") && (FormstaObj['k'].value != "READY"))
					{
						//alert(FormdivObj[k].value);
					   
					   if(FormChkObj['k'].checked == false){
							camt=parseFloat(FormamtObj['k'].value);
							damt=parseFloat(FormamtObj['k'].value);
							if (FormdivObj['k'].value == "credit")
							{
								
								csumamt1 = csumamt + camt;
								//alert(FormdivObj[k].value);
								//alert(csumamt1);
								$("input[name='depositAmount1']").val(csumamt1);
							} else {
								
								dsumamt1 = dsumamt + damt;
								///alert(dsumamt1);
								$("input[name='spendAmount1']").val(dsumamt1);
							}
							FormChkObj['k'].checked = true;

					   } else {
							if (FormdivObj['k'].value == "credit")
							{
								camt=parseFloat(FormamtObj['k'].value);
								if (csumamt >=0)
								{
									csumamt1 = csumamt - camt;
								} else {
								
									csumamt1 = 0;
								}
								$("input[name='depositAmount1']").val(csumamt1);
							} else {
								damt=parseFloat(FormamtObj['k'].value);
								if (dsumamt >=0)
								{
									dsumamt1 = dsumamt -  damt;
								} else {
								
									dsumamt1 = 0;
								}
								$("input[name='spendAmount1']").val(dsumamt1);
							}
							FormChkObj['k'].checked = false;

					   }


					} else {
						
						FormChkObj['k'].checked = false;

						//alert("!");

					}
			   }
			})
			$(".dataTables_length").css({ "display" :"none" });  
            //js-batch
			$('body').on('click', '.js-batch', function () {
				if (confirm("일괄정산 하시겠습니까?"))
				{
					$("#mode1").val("save");
					
				    $("#frmbb").submit();
				}
			})
		})

		var ctr=0;
        function openwin(randid,rev,seqr) { 
	       var winName = "all_"+(ctr++);
		   window.open("paysettle.php?rand_id="+randid+"&seqNo="+seqr+"&rev="+rev,winName,"width=800,height=600,scrollbars=1");
	    }
		//GoCheck
		function GoCheck(k) {
		   var FormChkObj = document.getElementsByName("seqNo[]"); 	
		   var FormamtObj = document.getElementsByName("amta[]");
		   var FormbalObj = document.getElementsByName("balamt[]"); 
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
						//alert(csumamt1);
						//alert(csumamt1);
						$("input[name='depositAmount1']").val(csumamt1);
					} else {
						
						dsumamt1 = dsumamt + damt;
						///alert(dsumamt1);
						$("input[name='spendAmount1']").val(dsumamt1);
					}
					FormChkObj['k'].checked = true;

			   } else {
				    if (FormdivObj['k'].value == "credit")
					{
						camt=parseFloat(FormamtObj['k'].value);
						if (csumamt >=0)
						{
							csumamt1 = csumamt - camt;
						} else {
						
							csumamt1 = 0;
						}
						$("input[name='depositAmount1']").val(csumamt1);
					} else {
						damt=parseFloat(FormamtObj['k'].value);
						if (dsumamt >=0)
						{
							dsumamt1 = dsumamt -  damt;
						} else {
						
							dsumamt1 = 0;
						}
						$("input[name='spendAmount1']").val(dsumamt1);
					}
					FormChkObj['k'].checked = false;

			   }


			} else {
				
				FormChkObj['k'].checked = false;

				//alert("!");

			}
			
			
			

	    }
	</script>
    </body>
</html>
