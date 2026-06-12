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
			echo "<tr style='border: 1px solid #aaa;'>
						<td style='padding: 10px;text-align:center;border-bottom: 1px solid #aaa;border-right: 1px solid #aaa;'>$k</td>
						<td style='border-bottom: 1px solid #aaa;border-right: 1px solid #aaa;padding: 5px;'>{$row1['traveler_nm']}</td>
						<td style='text-align:center;border-bottom: 1px solid #aaa;border-right: 1px solid #aaa;padding: 5px;'>$sexcap</td>
						<td style='text-align:center;border-bottom: 1px solid #aaa;border-right: 1px solid #aaa;padding: 5px;'>$rcap</td>
						<td style='text-align:center;border-bottom: 1px solid #aaa;padding: 5px;'>{$picknm['pick_name']} {$picknm['pick_time']} - {$picknm['pick_1desc']}</td>
					</tr>";
			$k++;
		 }

	}

    $cont =get_html('in_1');
?>
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0,">
	

</head>

<style type="text/css">
 body {
    /*font-family: 'Roboto','Jeju Gothic','Open San',sans-serif;*/
	font-family: 'Nanum Gothic', sans-serif;height:100%;
    font-size: 12px;
    font-weight: 400;
    background-color: #ffffff;
}

#invoice{
    padding: 5px 30px 30px 30px;
	font-size :14px;
	font-weight: 500;
	line-height: 1.6;
}

#invoice1{
    padding: 5px 20px 20px 20px;
	font-size :12px;
	font-weight: 500;
	line-height: 1.2;
}

.invoice {
    position: relative;
    background-color: #FFF;
    min-height: 680px;
    padding: 15px 15px 15px 0px;/*15px*/
}

.invoice header {
    padding: 10px 0;
    margin-bottom: 20px;
    border-bottom: 1px solid #3989c6
}

.invoice .company-details { 
    text-align: right;
	font-size:13px;
}

.invoice .company-details .name {
    margin-top: 0;
    margin-bottom: 0
}

.invoice .contacts {
    margin-bottom: 20px;
}

.invoice .tour-details {
	margin-bottom: 5px
}

.invoice .invoice-to  {
    text-align: left;
	font-family:'Open San',sans-serif;
	font-size:16px;
}

.date  {
	font-family:'Open San',sans-serif;
	font-size:14px;
}

.invoice .invoice-to .to {
    margin-top: 0;
    margin-bottom: 0
}

.invoice .invoice-details {
    text-align: right;
	font-family:'Open San',sans-serif;
	font-size:14px;
}

.invoice .invoice-details .invoice-id {
    margin-top: 0;
    color: #3989c6;
	font-size:18px;
}

.invoice main {
    padding-bottom: 50px
}

.invoice main .thanks {
    margin-top: -100px;
    font-size: 2em;
    margin-bottom: 50px
}

.invoice main .notices {
    padding-left: 6px;
    border-left: 6px solid #3989c6
}

.invoice-to h5,.invoice-to h2 {
	color:#3989c6;
	font-size:18px !important;
	font-weight:700;
}

.invoice main .notices .notice {
    font-size: 1.2em
}

.invoice table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
	font-size:12px;
}

.invoice table td,.invoice table th {
    padding: 15px 15px 15px 0px;background: #eee; border-bottom: 1px solid #aaa;
}

.invoice table th {
    white-space: nowrap;
    font-weight: 400;
    font-size: 14px
}

.invoice table td h3 {
    margin: 0;
    font-weight: 400;
    color: #3989c6;
    font-size: 1.2=0em
}

/*.text-center {width:50%;}*/

.invoice table .qty,.invoice table .total,.invoice table .unit , .invoice table .taxes, .invoice table .other {
    text-align: right;
    /*font-size: 1.2em*/
}

.invoice table .no {
    color: #fff;
    font-size: 1.6em;
    background: #3989c6
}

.invoice table .unit {
    background: #ddd
}

.invoice table .total {
    
    color: #212529;width:18%;
}

.invoice table tbody tr:last-child td {
    border: none;
	color: #3989c6; 
	font-style:italic;
}

.invoice table tfoot td {
    background: 0 0;
    border-bottom: none;
    white-space: nowrap;
    text-align: right;
    padding: 10px 20px 10px 0px;/*10px 20px;*/
    /*font-size: 1.2em;*/
    border-top: 1px solid #aaa
}

.invoice table tfoot tr:first-child td {
    border-top: none;
	padding-top:30px;
}

.invoice table tfoot tr:last-child td {
    color: #3989c6;
    /*font-size: 1.4em;*/
	font-weight:600;
    border-top: 1px solid #3989c6
}

.invoice table tfoot tr td:first-child {
    border: none
}

.invoice footer {
    width: 100%;
    text-align: center;
    color: #777;
    border-top: 1px solid #aaa;
    padding: 8px 0
}
.no-color {color: #212529 !important; font-weight:normal !important;}
.border-line {border-bottom: 1px solid #aaa;}
.margin-top {margin-top:15px;padding-left:0px !important;}
.margin-set {padding-top:10px !important ;}
h5 {font-weight:bold;}
.notice-word {font-size :12px;line-height: 1.9;font-family: 'Roboto',sans-serif;}
.border-none {border-bottom: none !important;}
.border-bottom-color{border-bottom: 1px solid #aaa !important;}
.book_confirm_header { background:#3989c6;color:#ffffff !important;vertical-align:middle !important;}
.invoice-to h5,.book-to h2 {
	color:#000;
	font-size:15px !important;
	font-weight:700;
}

book-font {font-size:14px !important;}

.left-column {background: #3989c6; color:#ffffff;}
.book-column {    
	/*line-height:18px;*/
	margin-bottom: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #aaa;
    padding-top: 5px;margin-top:5px;}
.h6_weight {font-weight:bold;font-size:15px;color:#000;}
 
.book_header {color:#000;font-weight:bold;line-height:3.3;padding-left:1.2%;}
a:link {
	color: #2b6ea2;
	font-weight:bold;
	text-decoration: none;
}
a:hover {
	color: #2d638c;
	text-decoration: none;
}
.font_bold {font-weight:bold;}
.line_height {line-height:1.6 !important;}
.terms {font-size:20px;font-weight:bold;padding-bottom:9px;}
.text-padding {padding-left:10px !important;}
.table-fixed {table-layout: fixed;}
.confim_book {background:#3989c6;color:#ffffff;font-weight:bold;line-height:3.3;}
</style>
<body style='max-width : 1280px;'>
	<div style="text-align: center;margin-bottom:-10px;margin-top:10px;"><h2>예약내역</h2></div>
	<br />
	<table style='width: 100%;line-height: inherit;text-align: left;border-top: 0px solid #fff;border-left: 0px solid #aaa;border-right: 0px solid #fff;'>
	
		<div style="background:#3989c6;color:#ffffff;font-weight:bold;line-height:3.3;text-align: center;">예약이 완료되었습니다.</div>
		<br/>
		
		
		<div style="color:#000;font-weight:bold;line-height:3.3;padding-left:1.2%;font-size: 14px;" >1. 예약자 정보</div>
		<?php if ($revInfo['pricet'] == 3) { ?>
		<table style='width: 100%;line-height: 18px;text-align: left;border-top: 1px solid #aaa;border-left: 1px solid #aaa;border-right: 1px solid #aaa;font-size: 13px;'>
			<tbody>
			    
				<tr>
					<td width='15%' style="background: #eee;font-weight: bold;text-align: left;padding: 5px;border-right: 1px solid #aaa;border-bottom: 1px solid #aaa;">협력사명</td>
					<td   style="border-bottom: 1px solid #aaa;"><?=$rname['kor_name']?></td>
				</tr>
				<tr>
					<td width='15%' style="background: #eee;font-weight: bold;text-align: left;padding: 5px;border-right: 1px solid #aaa;border-bottom: 1px solid #aaa;">담당자명</td>
					<td   style="border-bottom: 1px solid #aaa;"><?=$revInfo['book_pri']?></td>
				</tr>
				<tr>
					<td width='15%' style="background: #eee;font-weight: bold;text-align: left;padding: 5px;border-right: 1px solid #aaa;border-bottom: 1px solid #aaa;">이메일</td>
					<td   style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['book_email']?></td>
				</tr>
				<tr>
					
					<td width='15%' style="background: #eee;font-weight: bold;text-align: left;padding: 5px;border-right: 1px solid #aaa;border-bottom: 1px solid #aaa;">연락처</td>
					<td   style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['book_phone']?></td>
				</tr>
			</tbody>
		</table>
		<?php } else { ?>
		<table style='width: 100%;line-height: inherit;text-align: left;border-top: 1px solid #aaa;border-left: 1px solid #aaa;border-right: 1px solid #aaa;'>
			<tbody>
				<tr>
					<td width='15%' style="background: #eee;font-weight: bold;text-align: left;padding: 5px;border-right: 1px solid #aaa;border-bottom: 1px solid #aaa;">예약자명</td>
					<td   style="border-bottom: 1px solid #aaa;"><?=$revInfo['book_pri']?></td>
				</tr>
				<tr>
					<td width='15%' style="background: #eee;font-weight: bold;text-align: left;padding: 5px;border-right: 1px solid #aaa;border-bottom: 1px solid #aaa;">이메일</td>
					<td   style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['book_email']?></td>
				</tr>
				<tr>
					
					<td width='15%' style="background: #eee;font-weight: bold;text-align: left;padding: 5px;border-right: 1px solid #aaa;border-bottom: 1px solid #aaa;">연락처</td>
					<td   style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['book_phone']?></td>
				</tr>
			</tbody>
		</table>
		<?php }  ?>
		<br />
		<!-- 여행 예약정보 -->
		<div style="color:#000;font-weight:bold;line-height:3.3;padding-left:1.2%;font-size: 14px;" >2. 여행 예약 정보</div>
		<table style='width: 100%;line-height: inherit;text-align: left;border-top: 1px solid #aaa;border-left: 1px solid #aaa;border-right: 1px solid #aaa;'>
			<tbody>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">여행명</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$prodInfo['p_name']?></td>
				</tr>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">여행기간</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['stDate']?>(<?=$sweekday?>)~<?=$revInfo['edDate']?>(<?=$eweekday?>)</td>
					
				</tr>
				<tr>
					
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">통합예약번호</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['grand_revNo']?></td>
				</tr>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">예약번호</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><font color="red"><?=$revInfo['reserveCode']?></font></td>
				</tr>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">여행인원</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['p_cnt']?>인</td>
					
				</tr>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">예약일</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['revDate']?></td>
					
				</tr>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">예약상담원</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$rev_dbinfo['kor_name']?></td>
					
				</tr>
				<?php if ($revInfo['pricet'] != 3) { ?>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">여행비용</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['base_rate']?> <?php echo number_format($revInfo['last_sale']);?> (세금포함) </td>
				</tr>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">방갯수</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['room_cnt']?></td>
				</tr>
				<?php } else { ?>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;padding: 5px;border-bottom: 1px solid #aaa;">여행비용</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['room_cnt']?> </td>
				</tr>
				<?php }  ?>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">포함사항</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=nl2br($prodInfo['p_include'])?></td>
				</tr>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">불포함사항</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=nl2br($prodInfo['p_uninclude'])?></td>
				</tr>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">선택관광</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=nl2br($prodInfo['p_otrip'])?>
					</td>
				</tr>
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">준비물</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=nl2br($prodInfo['p_prepare'])?>
					</td>
				</tr>
				
			</tbody>
		</table>
		<br />
		<!-- 여행자 정보 -->
		<div style="color:#000;font-weight:bold;line-height:3.3;padding-left:1.2%;font-size: 14px;" >3. 여행자 정보</div>
		<div class="row">
			<div class="col-sm-12">
				<table style='width: 100%;line-height: 18px;text-align: left;border-top: 1px solid #aaa;border-left: 1px solid #aaa;border-right: 1px solid #aaa;font-size: 13px;'>
							<tbody>
								<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border-bottom: 1px solid #aaa;">
									<th style="padding: 10px;border-bottom: 1px solid #aaa;border-right: 1px solid #aaa;" width='5%'>NO.</th>
									<th style="border-bottom: 1px solid #aaa;border-right: 1px solid #aaa;" width='15%'>성명</th>
									<th style="border-bottom: 1px solid #aaa;border-right: 1px solid #aaa;" width='15%'>성별</th>
									<th style="border-bottom: 1px solid #aaa;border-right: 1px solid #aaa;" width='15%'>객실</th>
									<th style="border-bottom: 1px solid #aaa;">탑승지</th>
								</tr>
							
							
								<?=tourplist()?>
								
							</tbody>
				</table>
			</div>
		</div>
		
		<div style="color:#000;font-weight:bold;line-height:3.3;padding-left:1.2%;font-size: 14px;" >4. 추가 정보</div>
		<div class="row">
			<div class="col-sm-12">
			{ADDINFO}
			</div>
		</div>
	</div>
	
	<!-- invoice page -->
	<div id="invoice" style="max-width: 100%; margin: auto;padding: 10px; border: 0px solid #eee;  box-shadow: 0 0 0px rgba(0, 0, 0, 0); font-size: 12px;line-height: 20px;font-family: 'Nanum Gothic', sans-serif;height:100%; color: #555;">
		
			<div style="min-width: 600px">
				<header style=" margin-bottom: 10px;  border-bottom: 1px solid #3989c6">
					<div class="row">
						<div width='100%' >
							
							<span style="text-align: right;font-size:12px;">
								<img src="http://www.myprt.org/img/top_in3.jpg" data-holder-rendered="true"  height="120px" width="100%"/>
							</span>
						</div>
						
					</div>
				</header>
				<main style="padding-bottom: 50px;">
				    <div style="text-align: center;
    margin-right: -15px;
    margin-left: -15px;margin-bottom: 20px;">
						<div style="text-align: center;
	margin-left: 0;
	font-family:'Open San',sans-serif;
	font-size:14px;">
							<h2 style='font-size:20px !important;text-align:center;font-weight: 900 !important;'>INVOICE
							</h2>
						</div>
	                </div>
					<div style="display: flex;flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;margin-bottom: 20px;">
						<div style="flex-basis: 0;flex-grow: 1;max-width: 100%;text-align: left;
    font-family: 'Open San',sans-serif;font-size: 13px;position: relative;
    width: 100%;min-height: 1px; padding-right: 15px; padding-left: 15px;">
							<h2 style="margin-top: 0;color: #3989c6;font-size: 15px !important; font-weight: 900;">고객정보 | Customer(s)</h2>
							<h2 style= "font-size: 15px !important;color: #212529 !important;
    font-weight: normal !important;margin-bottom: .5rem;font-weight: 500;line-height: 1.2;margin-top: 0;"><b><?=$revInfo['book_pri']?></b> 님</h2>
							<div><?=$revInfo['book_phone']?></div>
						
						</div>
						
						<div style="flex-basis: 0;
    -ms-flex-positive: 1;
    flex-grow: 1;
    max-width: 100%;position: relative;width: 100%;min-height: 1px;
    padding-right: 15px;padding-left: 15px; text-align: right;
    font-family: 'Open San',sans-serif;
    font-size: 14px;">
							<h5 style="margin-top: 0;color: #3989c6; font-size: 16px;">예약번호 : <?=$r_code?>
							</h5>
						</div>
					</div>
					<br />
					<div style="display: flex;flex-wrap: wrap;margin-right: -15px; margin-left: -15px;margin-bottom: 5px;padding-right: 15px; padding-left: 15px;">
						<div style="text-align: left;
    font-family: 'Open San',sans-serif;
    font-size: 13px;">
							<h2 style="color: #3989c6;margin-bottom: .5rem;    margin-top: 0;
    font-size: 15px !important; font-weight: 900;">예약내역 ㅣTour Details</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;'>
						<thead>
							 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							
								<th style="border: 1px solid #aaa;">여행상품<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5;margin-top: 0;"><font size =1>Tour Package</h6></th>
								<th style="border: 1px solid #aaa;">출발일<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Departure</h6></th>
								<th style="border: 1px solid #aaa;">도착일<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Arrival</h6></th>
								<th style="border: 1px solid #aaa;">인원<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Travelers</h6></th>
								<th style="border: 1px solid #aaa;">투어비<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Total Price</h6></th>
							</tr>
						</thead>
						<tbody>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'><?=$revInfo['p_name']?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$revInfo['stDate']?>(<?=$seweekday?>)</td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$revInfo['edDate']?>(<?=$eeweekday?>)</td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$revInfo['p_cnt']?></td>
								<td style='text-align: right;border: 1px solid #aaa;' width="18%"><?=$sign?> <?php echo number_format($lasttot,2);?></td>
							</tr>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'>항공금액</td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: right;border: 1px solid #aaa;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($airamt['samt'],2);?></td>
							</tr>
							<?php if ($cruisetot > 0) { ?>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'>크루즈금액</td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: right;border: 1px solid #aaa;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($cruisetot,2);?></td>
							</tr>
							<?php } ?>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'>추가금액</td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: right;border: 1px solid #aaa;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($lastadd,2);?></td>
							</tr>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'>할인금액</td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: center;border: 1px solid #aaa;'></td>
								<td style='text-align: right;border: 1px solid #aaa;padding: 5px;' width="18%"><?=$sign?>  <?php echo number_format($disamt['amt'],2);?></td>
							</tr>
							
							<tr>
								<td style='text-align: left; padding: 5px;'><span ><b>최종 결제금액</b></span></td>
								<td colspan="3" style='text-align: center; '></td>
								<td style='text-align: right;font-weight: bold;font-size: 15px;' width="18%"><?=$sign?> <?php echo number_format($totamt,2);?>&nbsp;</td>
							</tr>
						
							
							
							
							
						</tbody>
					</table>
					<?php if (count($airList) > 0) { ?>
					<br />
					<div style="display: flex;flex-wrap: wrap;margin-right: -15px; margin-left: -15px;margin-bottom: 5px;padding-right: 15px; padding-left: 15px;">
						<div style="text-align: left;
    font-family: 'Open San',sans-serif;
    font-size: 13px;">
							<h2 style="color: #3989c6;margin-bottom: .5rem;margin-top: 0;
    font-size: 15px !important; font-weight: 900;">항공내역 ㅣAirline Details</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;border-collapse: collapse;border-spacing: 0;margin-bottom: 20px;'>
						<thead>
							<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
								<th style="border: 1px solid #aaa;">출발일<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Date</h6></th>
								<th style="border: 1px solid #aaa;">구간<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Route</h6></th>
								<th style="border: 1px solid #aaa;">편명<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Flight</h6></th>
								<th style="border: 1px solid #aaa;">PNR / TICKET#</th>
								<th style="border: 1px solid #aaa;">인원<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>PAX</h6></th>
								<th style="border: 1px solid #aaa;">판매금액<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Amount</h6></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($airList as $av) {
							$a_route = trim($av['a_start_airport']) . (trim($av['a_stop_airport']) != "" ? " → " . $av['a_stop_airport'] : "");
							$a_pnrtk = trim($av['a_pnr_number']) . (trim($av['a_tk_number']) != "" ? " / " . $av['a_tk_number'] : "");
						?>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: center;border: 1px solid #aaa;padding: 5px;'><?=$av['a_airline_start']?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$a_route?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$av['a_airport_name']?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$a_pnrtk?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$av['a_airport_cnt']?></td>
								<td style='text-align: right;border: 1px solid #aaa;padding: 5px;' width="18%"><?=$sign?> <?php echo number_format($av['a_airline_amt'],2);?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<?php } ?>
					<?php if (count($cruiseList) > 0) { ?>
					<br />
					<div style="display: flex;flex-wrap: wrap;margin-right: -15px; margin-left: -15px;margin-bottom: 5px;padding-right: 15px; padding-left: 15px;">
						<div style="text-align: left;
    font-family: 'Open San',sans-serif;
    font-size: 13px;">
							<h2 style="color: #3989c6;margin-bottom: .5rem;margin-top: 0;
    font-size: 15px !important; font-weight: 900;">크루즈내역 ㅣCruise Details</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;border-collapse: collapse;border-spacing: 0;margin-bottom: 20px;'>
						<thead>
							<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
								<th style="border: 1px solid #aaa;">출항/하선<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Sail / Return</h6></th>
								<th style="border: 1px solid #aaa;">크루즈/선박<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Line / Ship</h6></th>
								<th style="border: 1px solid #aaa;">출항/입항항구<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Ports</h6></th>
								<th style="border: 1px solid #aaa;">객실/예약번호<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Room / Booking#</h6></th>
								<th style="border: 1px solid #aaa;">인원<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>PAX</h6></th>
								<th style="border: 1px solid #aaa;">판매금액<h6 style="margin-bottom: .1rem !important;line-height: .5;margin-top: 0;"><font size =1>Amount</h6></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($cruiseList as $cv) {
							$c_period = trim($cv['c_depart_date']) . (trim($cv['c_return_date']) != "" ? " ~ " . $cv['c_return_date'] : "");
							$c_lineship = trim($cv['c_cruise_line']) . (trim($cv['c_ship_name']) != "" ? " / " . $cv['c_ship_name'] : "");
							$c_ports = trim($cv['c_depart_port']) . (trim($cv['c_arrive_port']) != "" ? " → " . $cv['c_arrive_port'] : "");
							$c_roombook = trim($cv['c_room_type']) . (trim($cv['c_book_no']) != "" ? " / " . $cv['c_book_no'] : "");
						?>
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: center;border: 1px solid #aaa;padding: 5px;'><?=$c_period?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$c_lineship?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$c_ports?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$c_roombook?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?=$cv['c_pax']?></td>
								<td style='text-align: right;border: 1px solid #aaa;padding: 5px;' width="18%"><?=$sign?> <?php echo number_format($cv['c_sale_amt'],2);?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<?php } ?>
					<br />
					<div style="display: flex;flex-wrap: wrap;margin-right: -15px; margin-left: -15px;margin-bottom: 5px;padding-right: 15px; padding-left: 15px;">
						<div style="text-align: left;
    font-family: 'Open San',sans-serif;
    font-size: 13px;">
							<h2 style="color: #3989c6;margin-bottom: .5rem;margin-top: 0;
    font-size: 15px !important; font-weight: 900;">결제내역 ㅣPayments</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;'>
						<thead>
							 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							
								<th width="25%" style="border: 1px solid #aaa;">결제일<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5;margin-top: 0;"><font size =1>Date</h6></th>
								<th width="15%" style="border: 1px solid #aaa;">결제방법<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5;margin-top: 0;"><font size =1>Method</h6></th>
								<th width="30%"style="border: 1px solid #aaa;">결제금액<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5;margin-top: 0;"><font size =1>Paid Amount</h6></th>
								
								<th width="20%" style="border: 1px solid #aaa;">담당자<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5;margin-top: 0;"><font size =1>Agent</h6></th>
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
							<tr style="background: #fff;font-weight: 400;text-align: center;">
								<td style='text-align: left;border: 1px solid #aaa;padding: 5px;'><?=$row['wdate']?></td>
								<td style='text-align: center;border: 1px solid #aaa;'><?php echo $cappay; ?></td>
								<td width="20%" style='text-align: right;border: 1px solid #aaa;'><?=$pamt?><?php if ($row['pay_method']=="creditcard") { echo "<br> (".$row['pay_info'].")";  }?>&nbsp;</td>
								
								<td style='text-align: center;border: 1px solid #aaa;' width="18%"><?=$pay_dbinfo['kor_name']?></td>
							</tr>
							
							
						<?php
										$i++;
						                endwhile;
									
									} else {
						?>

						<?php
						              
									}
						?>
							<tr style='border-bottom: 1px solid #aaa;'>
								<td style='text-align: left; padding: 5px;'><span >TOTAL PAID</span></td>
								<td  style='text-align: center; '></td>
								<td style='text-align: right; '><?=$sign?> <?php echo number_format(
								$totpay,2);?>&nbsp;</td>
								<td style='text-align: right;' width="18%"></td>
							</tr>
							<tr>
								<td style='text-align: left; padding: 5px;color: #3989c6;font-weight: 900;
    font-style: italic;'><span >BALANCE DUE</span></td>
								<td  style='text-align: center; '></td>
								<td  style='text-align: right;color: #3989c6;
    font-style: italic; '><?=$sign?> <?php echo number_format(
								$revInfo['last_bal'],2);?>&nbsp;</td>
								<td style='text-align: right;' width="18%"></td>
							</tr>
						</tbody>
					</table>
					
					<br/>
					<div style="display: flex;flex-wrap: wrap;margin-right: -15px; margin-left: -15px;margin-bottom: 5px;padding-right: 15px; padding-left: 15px;">
						<div style="text-align: left;
    font-family: 'Open San',sans-serif;
    font-size: 13px;">
							<h2 style="color: #3989c6;margin-bottom: .5rem;    margin-top: 0;
    font-size: 15px !important; font-weight: 900;">변경 및 취소규정 | Changes & Cancellation</h2>
						</div>
					</div>
					<div style="font-size: 12px;line-height: 1.9;font-family: 'Roboto',sans-serif;" id=terms>
						
        				<div style="margin-top: 10px;padding-left: 0px !important;">
							
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
			<div></div>

	</div>
	</table>
	
</body>
</html>	

 