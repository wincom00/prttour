<?php
    include "include/inc_base.php";



	$revInfo= getReserveInfo($r_code);
	$prodInfo = getProductMaster($revInfo['p_code']);

	$sday = $revInfo['stDate'] ;
	$eday = $revInfo['edDate'] ;
    $week = array("일" , "월"  , "화" , "수" , "목" , "금" ,"토") ;
	$eweek = array("SUN" , "MON" , "TUE" , "WED" , "THU" , "FRI" ,"SAT") ;
    $sweekday = $week[ date('w'  , strtotime($sday)  ) ] ;
	$eweekday = $week[ date('w'  , strtotime($eday)  ) ] ;
	$seweekday = $eweek[ date('w'  , strtotime($sday)  ) ] ;
	$eeweekday = $eweek[ date('w'  , strtotime($eday)  ) ] ;
	if ($revInfo['base_rate'] == "CAD") {
		$sign = "C$";
	} else {
		$sign = "U$";
	}
	$disinfo = codebaseName($revInfo['dis_code']);
	$disamt = getReserveSum($r_code);
	$totamt = $revInfo['last_total'];// - $disamt[amt];
	$airamt = getAirlineSum($r_code);
	// 항공 인보이스내역 (PNR 목록)
	$airList = array();
	$air_rst = mysql_query("select * from reserve_airline_pnr where reserveCode = '$r_code'");
	while ($air_rst && ($air_row = mysql_fetch_assoc($air_rst))) {
		$airList[] = $air_row;
	}
	// 크루즈 인보이스내역 및 총금액
	$cruiseList = function_exists('getCruiseinfoList') ? getCruiseinfoList($r_code) : array();
	$cruisetot = 0;
	foreach ($cruiseList as $cv) {
		$cruisetot += floatval(str_replace(',', '', isset($cv['c_sale_amt']) ? $cv['c_sale_amt'] : 0));
	}
	$lasttot = $revInfo['last_sale'];
	$lastadd = $revInfo['last_add'];
	if ($revInfo['base_rate'] == "CAD") {
		$pricep = $totamt/1.13;
		$taxp = $totamt - $pricep;
	}
	$rev_dbinfo = getinfo_dbMember($revInfo['userid']);
	$rname=randname($revInfo['rand_id']);
	$cpage =get_html('pay_1');
	$ppage =get_html('mail1');
	if ($revInfo['c_code'] == "C021000") {
		$canhtml =get_html('cancel_1');
	} else if ($revInfo['c_code'] == "C021500") {
		$canhtml =get_html('cancel_2');
	} else if ($revInfo['c_code'] == "C022000") {
		$canhtml =get_html('cancel_3');
	} else if ($revInfo['c_code'] == "C022500") {
		$canhtml =get_html('cancel_4');
	} else if ($revInfo['c_code'] == "C023000") {
		$canhtml =get_html('cancel_5');
	} else if ($revInfo['c_code'] == "C023500") {
		$canhtml =get_html('cancel_6');
	}
	$lasttot = $revInfo['last_sale'] + $revInfo['last_add'];
	function tourplist()
	{
		 global $dbConn,$r_code;
		 $qry1="select * from reserve_traveler where reserveCode='$r_code' order by seqint asc";
		 $rst1 = mysql_query($qry1);
		 $k=1;
		 while($row1 = mysql_fetch_assoc($rst1)){
			if ($row1['sextype'] == "man") {
				$sexcap= "남자";
			} else if ($row1['sextype'] == "female") {
				$sexcap= "여자";
			} else if ($row1['sextype'] == "mfemale") {
				$sexcap= "혼성";
			}
			if ($row1['room_type'] == "1r1p") {
				$rcap= "1인1실";
			} else if ($row1['room_type'] == "1r2p") {
				$rcap= "2인1실";
			} else if ($row1['room_type'] == "1r3p") {
				$rcap= "3인1실";
			} else if ($row1['room_type'] == "1r4p") {
				$rcap= "4인1실";
			} else if ($row1['room_type'] == "1r5p") {
				$rcap= "5인1실";
			}
			$pickarr = explode("/",$row1['pick_area']);
			$picknm=pickBaseInfo($pickarr[0],$pickarr[1]);
			//print_r($picknm);
			echo "<tr>
						<td style='padding:8px 10px;text-align:center;border-bottom:1px solid #e2e6ea;color:#8b95a1;'>$k</td>
						<td style='padding:8px 10px;text-align:center;border-bottom:1px solid #e2e6ea;font-weight:bold;'>{$row1['traveler_nm']}</td>
						<td style='padding:8px 10px;text-align:center;border-bottom:1px solid #e2e6ea;'>$sexcap</td>
						<td style='padding:8px 10px;text-align:center;border-bottom:1px solid #e2e6ea;'>$rcap</td>
						<td style='padding:8px 10px;text-align:center;border-bottom:1px solid #e2e6ea;'>{$picknm['pick_name']} {$picknm['pick_time']} - {$picknm['pick_1desc']}</td>
					</tr>";
			$k++;
		 }

	}

    $cont =get_html('in_1');

	// 이메일용 공통 인라인 스타일 (메일 클라이언트가 <style>를 지원하지 않으므로 전부 인라인 처리)
	$st_tbl   = "width:100%;border-collapse:collapse;font-size:13px;line-height:1.7;border-top:2px solid #3a4a5c;border-bottom:1px solid #c9cfd6;margin-bottom:6px;";
	$st_label = "background:#f6f8fa;color:#45505c;font-weight:bold;text-align:center;white-space:nowrap;padding:8px 12px;border-bottom:1px solid #e2e6ea;border-right:1px solid #e2e6ea;";
	$st_val   = "padding:8px 12px;border-bottom:1px solid #e2e6ea;";
	$st_th    = "background:#f6f8fa;color:#45505c;font-weight:bold;text-align:center;padding:9px 8px 7px;border-bottom:1px solid #c9cfd6;";
	$st_h6    = "margin:1px 0 0;font-size:10px;font-weight:normal;color:#8b95a1;letter-spacing:.5px;text-transform:uppercase;line-height:1.2;";
	$st_c     = "padding:8px 10px;text-align:center;border-bottom:1px solid #e2e6ea;";
	$st_amt   = "padding:8px 12px;text-align:right;border-bottom:1px solid #e2e6ea;white-space:nowrap;";
	$st_head  = "font-size:14px;font-weight:bold;color:#22303e;padding:4px 0 8px 10px;margin:20px 0 10px;border-left:3px solid #2b5d8c;border-bottom:1px solid #e3e7ec;line-height:1.5;";
	$st_total = "border-top:2px solid #3a4a5c;background:#fbfcfd;font-weight:bold;padding:10px 12px;";
?>
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0,">


</head>

<body style="margin:0;padding:0;background:#eceef1;font-family:'Nanum Gothic','Malgun Gothic','Apple SD Gothic Neo',sans-serif;font-size:13px;color:#2b3138;">
	<div style="max-width:920px;margin:0 auto;background:#ffffff;border:1px solid #d7dbe0;padding:30px 32px 40px;">

	<div style="text-align:center;margin:16px 0 24px;">
		<h2 style="display:inline-block;font-size:20px;font-weight:800;letter-spacing:10px;text-indent:10px;color:#22303e;padding-bottom:8px;border-bottom:2px solid #22303e;margin:0;">예약내역</h2>
	</div>

	<div style="text-align:center;font-size:15px;font-weight:bold;color:#2b5d8c;letter-spacing:1px;padding:13px 0;border-top:1px solid #2b5d8c;border-bottom:1px solid #dfe3e8;background:#f7fafc;margin-bottom:26px;">예약이 완료되었습니다.</div>


	<div style="<?=$st_head?>">1. 예약자 정보</div>
	<?php if ($revInfo['pricet'] == 3) { ?>
	<table style="<?=$st_tbl?>">
		<tbody>

			<tr>
				<td width='15%' style="<?=$st_label?>">협력사명</td>
				<td style="<?=$st_val?>"><?=$rname['kor_name']?></td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">담당자명</td>
				<td style="<?=$st_val?>"><?=$revInfo['book_pri']?></td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">이메일</td>
				<td style="<?=$st_val?>"><?=$revInfo['book_email']?></td>
			</tr>
			<tr>

				<td width='15%' style="<?=$st_label?>">연락처</td>
				<td style="<?=$st_val?>"><?=$revInfo['book_phone']?></td>
			</tr>
		</tbody>
	</table>
	<?php } else { ?>
	<table style="<?=$st_tbl?>">
		<tbody>
			<tr>
				<td width='15%' style="<?=$st_label?>">예약자명</td>
				<td style="<?=$st_val?>"><?=$revInfo['book_pri']?></td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">이메일</td>
				<td style="<?=$st_val?>"><?=$revInfo['book_email']?></td>
			</tr>
			<tr>

				<td width='15%' style="<?=$st_label?>">연락처</td>
				<td style="<?=$st_val?>"><?=$revInfo['book_phone']?></td>
			</tr>
		</tbody>
	</table>
	<?php }  ?>

	<!-- 여행 예약정보 -->
	<div style="<?=$st_head?>">2. 여행 예약 정보</div>
	<table style="<?=$st_tbl?>">
		<tbody>
			<tr>
				<td width='15%' style="<?=$st_label?>">여행명</td>
				<td style="<?=$st_val?>"><?=$prodInfo['p_name']?></td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">여행기간</td>
				<td style="<?=$st_val?>"><?=$revInfo['stDate']?>(<?=$sweekday?>)~<?=$revInfo['edDate']?>(<?=$eweekday?>)</td>

			</tr>
			<tr>

				<td width='15%' style="<?=$st_label?>">통합예약번호</td>
				<td style="<?=$st_val?>"><?=$revInfo['grand_revNo']?></td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">예약번호</td>
				<td style="<?=$st_val?>"><span style="color:#b03030;font-weight:bold;"><?=$revInfo['reserveCode']?></span></td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">여행인원</td>
				<td style="<?=$st_val?>"><?=$revInfo['p_cnt']?>인</td>

			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">예약일</td>
				<td style="<?=$st_val?>"><?=$revInfo['revDate']?></td>

			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">예약상담원</td>
				<td style="<?=$st_val?>"><?=$rev_dbinfo['kor_name']?></td>

			</tr>
			<?php if ($revInfo['pricet'] != 3) { ?>
			<tr>
				<td width='15%' style="<?=$st_label?>">여행비용</td>
				<td style="<?=$st_val?>"><?=$revInfo['base_rate']?> <?php echo number_format($revInfo['last_sale']);?> (세금포함) </td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">방갯수</td>
				<td style="<?=$st_val?>"><?=$revInfo['room_cnt']?></td>
			</tr>
			<?php } else { ?>
			<tr>
				<td width='15%' style="<?=$st_label?>">여행비용</td>
				<td style="<?=$st_val?>"><?=$revInfo['room_cnt']?> </td>
			</tr>
			<?php }  ?>
			<tr>
				<td width='15%' style="<?=$st_label?>">포함사항</td>
				<td style="<?=$st_val?>"><?=nl2br($prodInfo['p_include'])?></td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">불포함사항</td>
				<td style="<?=$st_val?>"><?=nl2br($prodInfo['p_uninclude'])?></td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">선택관광</td>
				<td style="<?=$st_val?>"><?=nl2br($prodInfo['p_otrip'])?>
				</td>
			</tr>
			<tr>
				<td width='15%' style="<?=$st_label?>">준비물</td>
				<td style="<?=$st_val?>"><?=nl2br($prodInfo['p_prepare'])?>
				</td>
			</tr>

		</tbody>
	</table>

	<!-- 여행자 정보 -->
	<div style="<?=$st_head?>">3. 여행자 정보</div>
	<table style="<?=$st_tbl?>">
				<thead>
					<tr>
						<th style="<?=$st_th?>" width='5%'>NO.</th>
						<th style="<?=$st_th?>" width='15%'>성명</th>
						<th style="<?=$st_th?>" width='15%'>성별</th>
						<th style="<?=$st_th?>" width='15%'>객실</th>
						<th style="<?=$st_th?>">탑승지</th>
					</tr>
				</thead>
				<tbody>
					<?=tourplist()?>
				</tbody>
	</table>

	<div style="<?=$st_head?>">4. 추가 정보</div>
	<div style="padding:4px 2px 0;line-height:1.85;color:#45505c;">
	{ADDINFO}
	</div>

	<!-- invoice page -->
	<div id="invoice" style="margin-top:36px;font-size:13px;line-height:1.7;color:#2b3138;">

			<header style="padding-bottom:14px;margin-bottom:22px;border-bottom:2px solid #2b5d8c;">
				<img src="http://www.myprt.org/img/top_in3.jpg" data-holder-rendered="true" style="width:100%;height:auto;display:block;"/>
			</header>
			<main style="padding-bottom:30px;">
				<h2 style="text-align:center;font-size:24px;font-weight:bold;letter-spacing:8px;text-indent:8px;color:#22303e;margin:6px 0 24px;">INVOICE</h2>

				<table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
					<tr>
						<td style="vertical-align:top;text-align:left;">
							<h2 style="margin:0 0 7px;font-size:12px;font-weight:800;letter-spacing:1px;color:#2b5d8c;padding-bottom:5px;border-bottom:1px solid #e3e7ec;">고객정보 | Customer(s)</h2>
							<h2 style="margin:0 0 2px;font-size:17px;font-weight:normal;color:#22303e;"><b><?=$revInfo['book_pri']?></b> 님</h2>
							<div style="color:#5a6572;"><?=$revInfo['book_phone']?></div>
						</td>
						<td style="vertical-align:top;text-align:right;">
							<span style="display:inline-block;font-size:13px;font-weight:bold;color:#22303e;background:#f6f8fa;border:1px solid #dfe3e8;padding:8px 16px;margin-top:20px;">예약번호 : <?=$r_code?></span>
						</td>
					</tr>
				</table>

				<div style="<?=$st_head?>">예약내역 ㅣTour Details</div>
				<table style="<?=$st_tbl?>">
					<thead>
						 <tr>
							<th style="<?=$st_th?>">여행상품<h6 style="<?=$st_h6?>">Tour Package</h6></th>
							<th style="<?=$st_th?>">출발일<h6 style="<?=$st_h6?>">Departure</h6></th>
							<th style="<?=$st_th?>">도착일<h6 style="<?=$st_h6?>">Arrival</h6></th>
							<th style="<?=$st_th?>">인원<h6 style="<?=$st_h6?>">Travelers</h6></th>
							<th style="<?=$st_th?>" width="18%">투어비<h6 style="<?=$st_h6?>">Total Price</h6></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td style="<?=$st_val?>"><?=$revInfo['p_name']?></td>
							<td style="<?=$st_c?>"><?=$revInfo['stDate']?>(<?=$seweekday?>)</td>
							<td style="<?=$st_c?>"><?=$revInfo['edDate']?>(<?=$eeweekday?>)</td>
							<td style="<?=$st_c?>"><?=$revInfo['p_cnt']?></td>
							<td style="<?=$st_amt?>" width="18%"><?=$sign?> <?php echo number_format($lasttot,2);?></td>
						</tr>
						<tr>
							<td style="<?=$st_val?>">항공금액</td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_amt?>" width="18%"><?=$sign?> <?php echo number_format($airamt['samt'],2);?></td>
						</tr>
						<?php if ($cruisetot > 0) { ?>
						<tr>
							<td style="<?=$st_val?>">크루즈금액</td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_amt?>" width="18%"><?=$sign?> <?php echo number_format($cruisetot,2);?></td>
						</tr>
						<?php } ?>
						<tr>
							<td style="<?=$st_val?>">추가금액</td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_amt?>" width="18%"><?=$sign?> <?php echo number_format($lastadd,2);?></td>
						</tr>
						<tr>
							<td style="<?=$st_val?>">할인금액</td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_c?>"></td>
							<td style="<?=$st_amt?>" width="18%"><?=$sign?> <?php echo number_format($disamt['amt'],2);?></td>
						</tr>

						<tr>
							<td style="<?=$st_total?>"><b>최종 결제금액</b></td>
							<td colspan="3" style="<?=$st_total?>"></td>
							<td style="<?=$st_total?>text-align:right;font-size:15px;color:#22303e;white-space:nowrap;" width="18%"><?=$sign?> <?php echo number_format($totamt,2);?>&nbsp;</td>
						</tr>

					</tbody>
				</table>
				<?php if (count($airList) > 0) { ?>
				<div style="<?=$st_head?>">항공내역 ㅣAirline Details</div>
				<table style="<?=$st_tbl?>">
					<thead>
						<tr>
							<th style="<?=$st_th?>">출발일<h6 style="<?=$st_h6?>">Date</h6></th>
							<th style="<?=$st_th?>">구간<h6 style="<?=$st_h6?>">Route</h6></th>
							<th style="<?=$st_th?>">편명<h6 style="<?=$st_h6?>">Flight</h6></th>
							<th style="<?=$st_th?>">PNR / TICKET#</th>
							<th style="<?=$st_th?>">인원<h6 style="<?=$st_h6?>">PAX</h6></th>
							<th style="<?=$st_th?>" width="18%">판매금액<h6 style="<?=$st_h6?>">Amount</h6></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($airList as $av) {
						$a_route = trim($av['a_start_airport']) . (trim($av['a_stop_airport']) != "" ? " → " . $av['a_stop_airport'] : "");
						$a_pnrtk = trim($av['a_pnr_number']) . (trim($av['a_tk_number']) != "" ? " / " . $av['a_tk_number'] : "");
					?>
						<tr>
							<td style="<?=$st_c?>"><?=$av['a_airline_start']?></td>
							<td style="<?=$st_c?>"><?=$a_route?></td>
							<td style="<?=$st_c?>"><?=$av['a_airport_name']?></td>
							<td style="<?=$st_c?>"><?=$a_pnrtk?></td>
							<td style="<?=$st_c?>"><?=$av['a_airport_cnt']?></td>
							<td style="<?=$st_amt?>" width="18%"><?=$sign?> <?php echo number_format($av['a_airline_amt'],2);?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				<?php } ?>
				<?php if (count($cruiseList) > 0) { ?>
				<div style="<?=$st_head?>">크루즈내역 ㅣCruise Details</div>
				<table style="<?=$st_tbl?>">
					<thead>
						<tr>
							<th style="<?=$st_th?>">출항/하선<h6 style="<?=$st_h6?>">Sail / Return</h6></th>
							<th style="<?=$st_th?>">크루즈/선박<h6 style="<?=$st_h6?>">Line / Ship</h6></th>
							<th style="<?=$st_th?>">출항/입항항구<h6 style="<?=$st_h6?>">Ports</h6></th>
							<th style="<?=$st_th?>">객실/예약번호<h6 style="<?=$st_h6?>">Room / Booking#</h6></th>
							<th style="<?=$st_th?>">인원<h6 style="<?=$st_h6?>">PAX</h6></th>
							<th style="<?=$st_th?>" width="18%">판매금액<h6 style="<?=$st_h6?>">Amount</h6></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($cruiseList as $cv) {
						$c_period = trim($cv['c_depart_date']) . (trim($cv['c_return_date']) != "" ? " ~ " . $cv['c_return_date'] : "");
						$c_lineship = trim($cv['c_cruise_line']) . (trim($cv['c_ship_name']) != "" ? " / " . $cv['c_ship_name'] : "");
						$c_ports = trim($cv['c_depart_port']) . (trim($cv['c_arrive_port']) != "" ? " → " . $cv['c_arrive_port'] : "");
						$c_roombook = trim($cv['c_room_type']) . (trim($cv['c_book_no']) != "" ? " / " . $cv['c_book_no'] : "");
					?>
						<tr>
							<td style="<?=$st_c?>"><?=$c_period?></td>
							<td style="<?=$st_c?>"><?=$c_lineship?></td>
							<td style="<?=$st_c?>"><?=$c_ports?></td>
							<td style="<?=$st_c?>"><?=$c_roombook?></td>
							<td style="<?=$st_c?>"><?=$cv['c_pax']?></td>
							<td style="<?=$st_amt?>" width="18%"><?=$sign?> <?php echo number_format($cv['c_sale_amt'],2);?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				<?php } ?>
				<div style="<?=$st_head?>">결제내역 ㅣPayments</div>
				<table style="<?=$st_tbl?>">
					<thead>
						 <tr>
							<th width="25%" style="<?=$st_th?>">결제일<h6 style="<?=$st_h6?>">Date</h6></th>
							<th width="15%" style="<?=$st_th?>">결제방법<h6 style="<?=$st_h6?>">Method</h6></th>
							<th width="30%" style="<?=$st_th?>">결제금액<h6 style="<?=$st_h6?>">Paid Amount</h6></th>

							<th width="20%" style="<?=$st_th?>">담당자<h6 style="<?=$st_h6?>">Agent</h6></th>
						</tr>
					</thead>
					<tbody>
					<?php
								$qryr = "select * from payment_history where reserveCode = '$r_code' && pay_method !='init'  && payment_status != 'RRQUEST' order by seq_no asc";
								//echo $qryr;
								$rstr = mysql_query($qryr);
								$cntr= mysql_num_rows($rstr);
								$i = 0;
								if ($cntr > 0) {
									while($row = mysql_fetch_assoc($rstr)):

									   $rate = "";
									   switch ($row['pay_method'])
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
												$cappay = "자사단말기";
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
											case "crsys" :
												$cappay = "크루즈시스템";
												break;
											case "gift" :
												$cappay = "상품권및기타";
												break;
											default :
												$cappay = "";
												break;

										}
										if ($row['b_rate'] == "CAD") {

											$amtt=$row['payment'] / 1.13 ;
											$tax = $row['payment'] - $amtt;
											if ($row['rate_m'] != '0.0000') {
											  $rate ="<br />(Rate : {$row['rate_m']})";
											}
											$sign1 = "C$";

										} else {
											$tax = 0;

											if ($row['rate_m'] != '0.0000') {
											  $rate ="<br />(Rate : {$row['rate_m']})";
											}
											$sign1 = "U$";
										}
										if ($row['payment_status'] == "RETURN") {
											$pamt = "<font color=red>-".$sign." ".$row['payment']."</font>";
										} else {

											$pamt = $sign." ".$row['payment'];
										}
										$pay_dbinfo = getinfo_dbMember($row['register']);

										if ($row['payment_status'] == "RETURN") {
											$pamt1 = "<font color=red>-".$sign1." ".$row['rate_payment']."</font>";
											$totpay=$totpay - $row['payment'] ;
										} else {
											$totpay=$totpay + $row['payment'] ;
											$pamt1 = $sign1." ".$row['rate_payment'];
										}
										$pay_dbinfo = getinfo_dbMember($row['register']);

					?>
						<tr>
							<td style="<?=$st_val?>"><?=$row['wdate']?></td>
							<td style="<?=$st_c?>"><?php echo $cappay; ?></td>
							<td width="20%" style="<?=$st_amt?>"><?=$pamt?><?php if ($row['pay_method']=="creditcard") { echo "<br> (".$row['pay_info'].")";  }?>&nbsp;</td>

							<td style="<?=$st_c?>" width="18%"><?=$pay_dbinfo['kor_name']?></td>
						</tr>


					<?php
									$i++;
					                endwhile;

								} else {
					?>

					<?php

								}
					?>
						<tr>
							<td style="<?=$st_total?>">TOTAL PAID</td>
							<td style="<?=$st_total?>"></td>
							<td style="<?=$st_total?>text-align:right;white-space:nowrap;"><?=$sign?> <?php echo number_format(
							$totpay,2);?>&nbsp;</td>
							<td style="<?=$st_total?>" width="18%"></td>
						</tr>
						<tr>
							<td style="<?=$st_total?>color:#b03030;">BALANCE DUE</td>
							<td style="<?=$st_total?>"></td>
							<td style="<?=$st_total?>text-align:right;color:#b03030;white-space:nowrap;"><?=$sign?> <?php echo number_format(
							$revInfo['last_bal'],2);?>&nbsp;</td>
							<td style="<?=$st_total?>" width="18%"></td>
						</tr>
					</tbody>
				</table>

				<div style="<?=$st_head?>">변경 및 취소규정 | Changes & Cancellation</div>
				<div style="font-size:12px;line-height:1.9;color:#45505c;" id=terms>

					<div style="margin-top:10px;">

						<div class="row">
						  <?=$cont['content']?>
						  <!--<table style="width: 100%;line-height: inherit;text-align: left;">
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
									※미동부/미서부/그외 투어 : 출발 2~3주전까지 완불 입니다
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
									※전날예약자는 전액 완불 입니다!! 캔슬시 환불 안됩니다.
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
									※투어 규정은 꼭 확인요청드립니다.(성수기시즌은 약간씩 규정이 변경될수 있습니다. 꼭 담당자께 확인요청드립니다.)
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
									※국제선&국내선 항공권은 발권후에 요금이 변동이 되어도 환불 또는 취소하실경우엔 패널티가 발생하므로 이점 꼭 유념해서 확인부탁드립니다.
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
									※고객님의 개인사정으로 국경을 통과하지 못하였거나, 부득이 한 사정으로 인하여 투어를 중단 하실경우에는 모든 책임과 금점적인 부담은 여행자 본인에게 있습니다.
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
									★캐나다 관광(국경 통과)시 학생은 학교 관계자의 싸인(6개월 이상 남아 있어야 함) 이 되어있는 I-20 , 교환 교수는 학교에서 발부되는 체류자격 서류를 필히 지참하시길 바랍니다.
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
									★미국 영주권자의 경우 영주권과 여권, 시민권자의 경우 여권, 한국에서 오신경우에는 여권과 리턴티켓이 있어야 합니다.
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
									★여권 유효기간은 출발일로부터 6개월 이상 남아있어야 합니다.
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
									★한국 및 미국 여권 외에 다른 여권을 소유하고 계신 분들은 비자가 필요한지 꼭 확인 부탁드립니다.<br>
									투어 외 발생하는 모든 비용은 투어 당사자에게 있으며 본사(푸른)은 책임지지 않습니다.
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:red;text-align:left;">
									※기상악화로 인해 투어 및 항공이 진행(운행)이 되지 않을경우에는 당사(푸른)에 책임이 없음을 알려드립니다.
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
									※해외 여행자 보험은 불포함입니다. 출국 전 개별적으로 가입하시는 것을 권장합니다.
							</tr>
							<tr>
								<td style="padding: 5px;font-weight: 400;font-size: 13px;color:blue;text-align:left;">
									※항공사측에서 항공편을 변경 및 캔슬 할 경우에 당사(푸른)의 책임은 없음을 알려드립니다.
							</tr>
							<tr><td style="padding-bottom:15px;"></td></tr>
							<tr>
								<td style="padding:5px;font-weight: 400;font-size: 13px;color:#111;text-align:center;border-top:1px solid #ddd;">
									저희 푸른투어를 이용해 주셔서 대단히 감사합니다.<br>
									즐거운 여행 되십시요.
							</tr>
						</table> -->
						</div>


					</div>

				</div>
			</main>

	</div>

	</div>
</body>
</html>


