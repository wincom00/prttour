<?php
    include "include/header.php";
	
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

		function printPay(){
			
			global $dbConn,$cname,$division,$crev,$pdx,$sub,$seldate,$startDate,$endDate,$employeeName,$searchpay,$user_dbinfo;
			

			if ($productName) {
					$qrynm = " && a.p_name like '%$productName%'";

			} else {
				    $qrynm ="";
			}
			if ($cname !="") {
			
		
			      $qrycname= " && ((a.book_pri like '"."%".$cname."%"."') || (c.traveler_nm like '"."%".$cname."%"."'))";

	        }
			if ($seldate == '1') {
				if ($startDate) {
						$qrysdate = " && ((  a.revDate >= '$startDate' && a.revDate <= '$endDate' )) ";
						
				} 
			} else if ($seldate == '2') {
				if ($startDate) {
					    $startDate = "$startDate 00:00:00";
			            $endDate = "$endDate 23:23:59";
						$qrysdate = " && ((  b.wdate >= '$startDate' && b.wdate <= '$endDate' )) ";
						
				} 
			} else if ($seldate == '3') {
				
				$startDate = "$startDate 00:00:00";
			    $endDate = "$endDate 23:23:59";
				$qrysdate =" && ((  b.conf_date >= '$startDate' && b.conf_date <= '$endDate' )) ";
			
			} else {
				
				$sdate = date("Y-m-d");
				$sdate = "$sdate 23:23:59";
				$edate = date("Y-m-d",strtotime("-7 day"));
				$edate = "$edate 00:00:00";
				$qrysdate =" && (( a.revDate >= '$edate' && a.revDate <= '$sdate' )) ";
			}

			if ($searchpay) {
					$qrypay = " && b.conf_p='$searchpay'";

			} else {
				    $qrypay ="";
			}

			$qryemp = " && b.register='{$user_dbinfo['userid']}'";
			$qry1 ="SELECT DISTINCT
				a.rev_status,
				a.grand_revNo,
				a.reserveCode,
				a.p_code,
				a.p_name,
				a.book_pri,
				a.revDate,
				a.stDate,
				a.edDate,
				a.last_total,
				a.last_bal,
				a.p_cnt,
				a.base_rate,
				b.payment_status,
				b.pay_method AS pmethod,
				rate_m AS rm,
				b.*,
				DATE_FORMAT(b.wdate, '%Y-%m-%d') AS wwdate,
				b.register AS pregister
			FROM
				reserve_info a
			JOIN payment_history b ON a.reserveCode = b.reserveCode
			JOIN product_master d ON a.p_code = d.p_code
			WHERE
				b.pay_method != 'init'
				AND b.payment_status  IN ('READY','DONE','PPAY','OPAY')
				AND a.parent = 'MAIN'
				$qrycname $qrysdate $qrypay $qryemp $deptqry $qryst
			ORDER BY
				a.revDate DESC
				"
				;
			/*
			$qry1 = "select distinct a.grand_revNo,a.reserveCode,a.p_code,a.p_name,a.book_pri,a.revDate,a.stDate,a.edDate,a.last_total,a.last_bal,a.p_cnt,a.base_rate,b.payment_status,b.pay_method as pmethod,rate_m as rm,
			            b.* ,DATE_FORMAT(b.wdate, '%Y-%m-%d')  as wwdate,b.register as pregister from reserve_info a,payment_history b,reserve_traveler c
					  where a.reserveCode=b.reserveCode && a.rev_status not in ('CANCEL') && b.pay_method != 'init'  && b.payment_status not in ('RRQUEST','RETURN') && a.reserveCode = c.reserveCode
					  && a.parent ='MAIN' $qrycname $qrysdate $qrypay $qryemp order by a.revDate desc
					  
					  ";
					 */
			$rst1 = mysql_query($qry1,$dbConn);
			//echo $qry1;
			$k=0;
			while($row1 = mysql_Fetch_assoc($rst1)){
				$renm = getReserveTrRepre($row1['reserveCode']);
				if ($row1['base_rate'] == "CAD") {
					$sign = "C$";
					$row1['rate_m'] = 0;
			    } else {
					$sign = "U$";
			    }
				$totamt = $sign.$row1['last_total'];
				$balamt = $sign.$row1['last_bal'];
				switch ($row1['pmethod'])
				{
					case "cash" : 
						$cappay = "현금";
						break;   
					case "creditcard" : 
						$cappay = "신용카드웹";
						break;
					case "debitcard" : 
						$cappay = "데빗";
						break;
					
					case "bcreditcard" : 
						$cappay = "신용카드 자사단말기";
						break; 
					case "check" : 
						$cappay = "체크";
						break; 
					case "banktransfer" : 
						$cappay = "은행송금";
						break; 
					
					case "fundtransfer" : 
						$cappay = "금액이동";
						break;
					case "airsys" : 
						$cappay = "항공시스템";
						break; 
					case "gift" : 
						$cappay = "상품권및기타";
						break; 
					default : 
						$cappay = "";
						break; 
						
				}
				$pamt = $sign.$row1['payment'];
				if ($row1['b_rate'] == "CAD") {
					$sign1 = "C$";
					$row1['rate_m'] = 0;
			    } else {
					$sign1 = "U$";
			    }
				$ramt = $sign1.$row1['rate_payment'];
				$uinfo=getinfo_dbMember($row1['pregister']);
				if ($row1['conf_p'] == '2') {

					$btnv = "확인완료";
				} else {
					$btnv ="회계확인중";
				}
				echo "<tr>
						<td align='center'><a href=javascript:openwin('{$row1['reserveCode']}')  >{$row1['revDate']}<br/>{$row1['reserveCode']}</a></td>
						<td align='center'>{$row1['wwdate']}</td>
						<td><a href=javascript:openwin('{$row1['reserveCode']}')  >{$row1['p_name']}</td>
						<td align='center'><a href=javascript:openwin('{$row1['reserveCode']}')  >{$row1['book_pri']}</a></td>
						<td align='center'>{$row1['p_cnt']}</td>
						<td align='right'>$totamt<br /><font color=red>$balamt</font></td>
						
						<td align='center'>$cappay</td>
						<td align='right'>$pamt</td>
						
						<td align='center'>{$uinfo['kor_name']}</td>
						<td>{$row1['pay_memo']}</td>
						<td align='center'><span id='accspan$k' class='accspan$k'>$btnv</span></td> 
					</tr>";
				$k++;

			
			}

	}
	
?>
	<div id="contentwrapper" class="reservationDetailForm">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb 
			module">
				<ul>
					<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
					<li><a href="#">직원별수금정산</a></li>
					<li>직원별정산현황</li>
				</ul>
			</div>
			<div class="row">
				<div class="col-sm-12 col-md-12">
					<form action=""  method="post" name="frmName">
						<table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                <tr>
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="seldate">
                                            <option value="">- 선택 -</option>
                                            <option <?php if (($seldate == "1")) { ?> selected <?php } ?> value="1" >예약일</option>
                                            <option <?php if ($seldate == "2") { ?> selected <?php } ?> value="2">결제일</option><option <?php if ($seldate == "3") { ?> selected <?php } ?> value="2">회계확인</option>
                                        </select>
                                    </td>
                                    <td colspan="5">
                                        <div class="row">
                                            <div class="col-sm-5">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="startDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate1" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$startDate?>'>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="endDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate2" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$endDate?>'>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
									<td colspan="2" class="text-center formHeader">
                                        <input type="text" id="cname" name="cname" placeholder="고객명" class="inpubase md" value="<?=$cname?>"/>
                                    </td>
                                    
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="searchpay">
                                            <option value="">정산상태</option>
                                            <option <?php if ($searchpay == "1") { ?> selected <?php } ?> value="1">회계확인</option>
                                            <option <?php if ($searchpay == "2") { ?> selected <?php } ?> value="2">회계확인완료</option>
                                        </select>
                                    </td>
                                    <td colspan="3" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                                </tr>
                            </tbody>
                        </table>
					</form>
					<br />
					<div class="row">
						<div class="col-sm-12">
							<table class="table table-striped table-bordered table-hover table-condensed js-productTable">
								<thead>
									<tr>
										<th>예약날짜</th>
										<th>결제일</th>
										<th>상품명</th>
										<th>예약자</th>
										<th>인원</th>
										<th>최종결제금액<br />잔금</th>
										
										<th>결제방법</th>
										<th>결제금액</th>
										
										<th>결제자</th>
										<th>결제메모</th>
										<th>정산상태</th>
									</tr>
								</thead>
								<tbody>
									<?=printPay()?>
								</tbody>
							</table>
						</div>
					</div>
					<br/>
					
				</div><!-- -->
			</div>                
		</div>

	</div>
    <?php
		include "include/side_m.php"
	?>
    <script>
		$(document).ready(function () {
            pt.initReservationDetail()

			pt.initReservationList()
			var dateToday = new Date()
			$('.tourDate1').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
				
			});
			$('.tourDate2').datepicker({
				format: "yyyy-mm-dd",
				autoclose: true
			});

			

			$(".dataTables_length").css({ "display" :"none" });
		})
		function appbtn(obj){

				var tmp = $(obj).val();
				var tmpstr = tmp.split("/");

				var num =tmpstr[0];
				var seq =tmpstr[1];
				
				$.ajax({
							type: "POST",
							url: "update_acc.php?seq="+seq,
							data: "",
							dataType: "json",
							success: function(data) {
								if (data==1)
								{
									alert("확인되었습니다.!!");
									$("#accspan"+num).html(""); 
									$("#accspan"+num).html("확인완료"); 
								}
							},
							error: function(){
								  alert('저장 에러 !!');
							}
				  }); 
				  
				  

		}
		var ctr=0;
	    function openwin(r_code) { 
			
	       var winName = "all_"+(ctr++);
		   window.open("base_reservation_m.php?estimateCode="+r_code+"&division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>",winName,"width=1300,height=700,scrollbars=1");
	    }
	</script>
    </body>
</html>
