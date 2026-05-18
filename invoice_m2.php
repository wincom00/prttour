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
	$totamt = $revInfo['last_total'] ;//- $disamt[amt];
	$lasttot = $revInfo['last_sale'] + $revInfo['last_add'];
	if ($revInfo['base_rate'] == "CAD") {
		$pricep = $totamt/1.13;
		$taxp = $totamt - $pricep;
	} 
	$rev_dbinfo = getinfo_dbMember($revInfo['userid']);
	$rname=randname($revInfo['rand_id']);
	$cpage =get_html('pay_1');
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

	function printCustomer() {
		global $dbConn, $division, $randSelection,$r_code;

		

		$qry1 = "select seq_no,send_reg,subject,sent_on from mailing_history where reserveCode='$r_code' order by sent_on desc";
		$rst1 = mysql_query($qry1,$dbConn);
	

		while($row1 = mysql_Fetch_assoc($rst1)){
		
			
					echo "<tr bgcolor=#FFFFFF>
					<td height=25 style='text-align: center;border: 1px solid #aaa;font-weight: bold;'>&nbsp;{$row1['send_reg']}</td>
					<td style='text-align: center;border: 1px solid #aaa;'><a href=javascript:viewmail('$r_code','{$row1['seq_no']}') >{$row1['subject']}</a></td>
					<td style='text-align: center;border: 1px solid #aaa;font-weight: bold;'>{$row1['sent_on']}</td>
					</tr>";
				
		}
		
	}
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
			echo "<tr style='font-weight: bold;border: 1px solid #aaa;'>
						<td style='padding: 10px;text-align:center;border: 1px solid #aaa;'>$k</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>{$row1['traveler_nm']}<br />{$row1['traveler_enm']}</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$sexcap</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>{$row1['traveler_birth']}</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>{$row1['pass_date']}</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>{$row1['pass_num']}</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>{$row1['traveler_room']}</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>{$row1['traveler_phone']}</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>{$row1['e_memo']}</td>
					</tr>";;
			$k++;
		 }

	}
    $cpage =get_html('pay_1');
	//echo $prodInfo[p_type];
    
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
	
		<div style="background:#3989c6;color:#ffffff;font-weight:bold;line-height:3.3;text-align: center;">예약을 요청드립니다.</div>
		<br/>
		
		
		<!-- 여행 예약정보 -->
		<div style="color:#000;font-weight:bold;line-height:3.3;padding-left:1.2%;font-size: 14px;" >1. 여행 예약 정보</div>
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
				
				<!--<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">여행비용</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['base_rate']?> <?php echo number_format($revInfo['last_sale']);?> (세금포함) </td>
				</tr>-->
				<tr>
					<td width='15%'  style="background: #eee;font-weight: bold;text-align: left;border-right: 1px solid #aaa;padding: 5px;border-bottom: 1px solid #aaa;">방갯수</td>
					<td  style="border-bottom: 1px solid #eee;border-bottom: 1px solid #aaa;"><?=$revInfo['room_cnt']?></td>
				</tr>
				
				
			</tbody>
		</table>
		<br />
		<!-- 여행자 정보 -->
		<div style="color:#000;font-weight:bold;line-height:3.3;padding-left:1.2%;font-size: 14px;" >2. 여행자 정보</div>
		<div class="row">
			<div class="col-sm-12">
				<table style='width: 100%;line-height: 18px;text-align: left;border-top: 1px solid #aaa;border-left: 1px solid #aaa;border-right: 1px solid #aaa;font-size: 13px;'>
							<tbody>
								<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
									<th style="padding: 10px;border: 1px solid #aaa;" width='5%'>NO.</th>
									<th style="border: 1px solid #aaa;" width='10%'>성명</th>
									<th style="border: 1px solid #aaa;" width='5%'>성별</th>
									<th style="border: 1px solid #aaa;" width='10%'>DOB</th>
									<th style="border: 1px solid #aaa;" width='10%'>여권만료일</th>
									<th style="border: 1px solid #aaa;" width='10%'>여권번호</th>
									<th style="border: 1px solid #aaa;" >RM</th>
									<th style="border: 1px solid #aaa;" >연락처</th>
									<th style="border: 1px solid #aaa;"width='*'>특이사항</th>
								</tr>
							
							
								<?=tourplist()?>
								
							</tbody>
				</table>
			</div>
		</div>
		
		<div style="color:#000;font-weight:bold;line-height:3.3;padding-left:1.2%;font-size: 14px;" >3. 추가 정보</div>
		<div class="row">
			<div class="col-sm-12">
			{ADDINFO}
			</div>
		</div>
	</div>
	
</body>
</html>	

 