<?php
    include "include/inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}

     if (!hasMenuAccess($division, $pdx, $sub)) {
		
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		exit;
    }

	$revInfo= getReserveInfo($r_code);
	$prodInfo = getProductMaster($revInfo['p_code']);
    $randC = getCRandInfo($r_code);
	$randD = getDRandInfo($r_code);
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
	$cont = get_html('in_1');
	//echo $prodInfo[p_type];
	if ($mode == "send_email") {
			$sbj = "[푸른투어] $sday ,{$prodInfo['p_name']} 예약을 요청드립니다.";
 
			$content= file_get_contents( "http://www.myprt.org/invoice_m2.php?division=3&pdx=2&sub=15&r_code=".$r_code."");

			//echo $content;
			//exit;
            // 메세지
			$board_pds_pos = "/var/www/html/upload";
			$tmpName1 = $_FILES['userfile1']['tmp_name'];
			if(is_uploaded_file($tmpName1)){
				$pds_file1 = $_FILES['userfile1']['name'];
				$attc_name1 = Misc::uploadFileUnsafely($tmpName1 , $pds_file1 , $board_pds_pos);
				
				$fileloc1 = $board_pds_pos . "/" . $attc_name1['savedName'];
				
				array_push($atc_arr,$fileloc1);
				$attachment1 = $attc_name1['savedName'];
			}
			$tmpName2 = $_FILES['userfile2']['tmp_name'];
			if(is_uploaded_file($tmpName2)){
				$pds_file2 = $_FILES['userfile2']['name'];
				$attc_name2 = Misc::uploadFileUnsafely($tmpName2 , $pds_file2 , $board_pds_pos);
				
				$fileloc2 = $board_pds_pos . "/" . $attc_name2['savedName'];
				
				array_push($atc_arr,$fileloc2);
				$attachment2 = $attc_name2['savedName'];
			}
			$smail = randname($randD['part_id']);
			//print_r($smail);
			//exit;
			
			$smail = randname($rand_id);
			if ($revInfo['book_email'] == "") {
				$cmail = $revInfo['book_email'];
			} else {
				$cmail = $smail['company_email'];
			}
			///$msg = "* 추가 사항 <br />".$board_note."<br /><br />".$content;
			$msg = str_replace('{ADDINFO}',$board_note,$content);
			$ret= mailsend_a($cmail,$sbj,$msg,$attachment1,$attachment2);
			
			if (($prodInfo['p_type'] == 1)) {
			$ret= mailsend_a('online@prttour.com',$sbj,$msg,$attachment1,$attachment2);
			} else {
			$ret= mailsend_a('online@prttour.com',$sbj,$msg,$attachment1,$attachment2);
			}
			echo "<br><font size=2 color=red><p align=center>이메일 전송완료!</p></font>";
			$qry2 = "insert into mailing_history (division,
												send_reg,
												subject,
												message,
												attach1,
												attach2,
												attach3,
												reserveCode) values ('mailinglist',
																'{$user_dbinfo['userid']}',
																'$sbj',
																'".addslashes($msg)."',
																'{$attc_name1['savedName']}',
																'{$attc_name2['savedName']}',
																'',
																'$r_code')";
																					
			$rst2 = mysql_query($qry2, $dbConn);
			
	}
	if ($mode == "print") {
		echo "<meta http-equiv='refresh' content='0; url=./invoice_p.php?division=3&pdx=2&sub=15&r_code=".$r_code."'>";

	}
?>
<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<title>Invoice</title>
	<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<link href="https://fonts.googleapis.com/css?family=Montserrat|Open+Sans|Roboto&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Nanum+Gothic" rel="stylesheet">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<link rel="stylesheet" href="/resources/demos/style.css">
	<link href="css/invoice-f.css" rel="stylesheet" id="invoice-css">

	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<script src="/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>	


</head>
<style type="text/css">
  @media print {
	  @page { margin: 0; }
	  body { margin: 1.6cm; }
  }
</style>

<body>
	<!-- book info-->
<div style="text-align: center;margin-bottom:-10px;margin-top:10px;"><h2>예약내역</h2></div>

<br />
<form name=print id=print action='invoice_page2.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code=<?=$r_code?>' method=post enctype="multipart/form-data">
  <input type=hidden name=r_code id='r_code' value="<?= $r_code ?>">
  <input type=hidden name=mode id="mode" value="send_email">
	<div id="invoice1">
		<div class="text-center confim_book">예약이 완료되었습니다.</div>
		<br/>
		
		<?php if ($mode != 'print'): ?>
		<div class="row no-nav">
			<div id="custom_button" class="col-sm-12 text-right">
				<button type="button" class="btn btn-xs btn-default js-mail" >이메일 보내기</button>
				<button type="button" class="btn btn-xs btn-default js-print" onclick="pageprint()">프린트</button><br /><br />

			</div>
		</div>
		
	
		<?php endif; ?>
		<?php if ($mode != 'print'): ?>

		<div class="text-left book_header">*추가내용 정보(항공정보 포함)</div>
		 <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
			<tbody>
				<tr>
					<td ><textarea class="form-control js-tripNote js-ckEditor" name="board_note" id="board_note"></textarea></td>
					
				</tr>
				<tr>
					<td >첨부파일 : <input type=file name=userfile1 class="form_box" value="" style="width:600px"></textarea></td>
					
				</tr>
				<tr>
					<td >첨부파일 : <input type=file name=userfile2 class="form_box" value="" style="width:600px"></textarea></td>
					
				</tr>
				
			</tbody>
		</table>
		<?php endif; ?>
		<div class="invoice1 overflow-auto">
			<div style="min-width: 600px">
				
				
				
				<!-- 여행 예약정보 -->
				<div class="text-left book_header">1. 여행 예약 정보</div>
				<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
					<tbody>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" width = '10%' style="border: 1px solid #aaa;padding: 10px;">여행명</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$prodInfo['p_name']?></td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">여행기간</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['stDate']?>(<?=$sweekday?>)~<?=$revInfo['edDate']?>(<?=$eweekday?>)</td>
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">통합예약번호</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['grand_revNo']?></td>
						</tr >
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">여행인원</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['p_cnt']?>인</td>
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">예약번호</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['reserveCode']?></td>
						</tr >
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">예약일</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['revDate']?></td>
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">예약상담원</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$rev_dbinfo['kor_name']?></td>
						</tr>
						<?php if ($revInfo['pricet'] != 3) { ?>
						
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">방갯수</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['room_cnt']?> </td>
						</tr>
						<?php } else { ?>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">방갯수</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['room_cnt']?> </td>
						</tr>

						<?php }  ?>
						
					</tbody>
				</table>
				<br/>
				<!-- 여행자 정보 -->
				<div class="text-left book_header">2. 여행자 정보</div>
				<div class="row">
					<div class="col-sm-12">
						<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
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
						<br/>
					</div>
				</div>
				
				<!-- 여행참고사항 -->
				<br />
				
		  </div>
	    </div>

		
		
	<!-- invoice page -->
	
	<div id="invoice" style="page-break-before: always;">
		<div class="invoice overflow-auto">
			<div style="min-width: 600px">
				<header>
					<div class="row">
						<!--<div class="col-sm-3">
							<a target="_blank" href="#">
								<img src="/img/logo.jpg" data-holder-rendered="true" height="64px" width="100%" />
							</a>
						</div>-->
						<div class="col-sm-12 company-details" >
							<!--<div><B>캐나다본사: </B> 5633 Yonge Street, North York, ON M2M 3S9,TEL: 416-223-7767, 070-7752-1311,FAX: 416-223-7789 </div>
							<div><B>한국사무소: </B> 서울 종로구 종로 19, A동 714호 종로1가, 르메이에르 종로타운1, TEL: 02-720-7767, FAX: 02-720-7769 </div>
							<div>GST Registration No.  8574 12191RT0001 www.parantours.com </div>
							<div>TICO Registration No. 50015723  KATALK: 파란여행 admin@parantours.com </div>-->
							<img src="http://www.myprt.online/img/top_in3.jpg" data-holder-rendered="true" height="120px" width="100%"/>

						</div>
					</div>
				</header>
				<main>
				   <div class="row contacts">
						<div class="col invoice-center">
							<h2 style='text-align:center;font-weight: 700;'>INVOICE
							</h2>
						</div>
					</div>
					<div class="row contacts">
						<div class="col invoice-to">
							<h2 class="invoice-to">고객정보 | Customer(s)</h2>
							<h2 class= "no-color"><b><?=$revInfo['book_pri']?></b> 님</h2>
							<div><?=$revInfo['book_phone']?></div>
							
						</div>
						
						<div class="col invoice-details">
							<h5 class="invoice-id">예약번호 : <?=$r_code?>
							</h5>
						</div>
					</div>
					<br />
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">예약내역 ㅣTour Details</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
						<thead>
							 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							
								<th style="border: 1px solid #aaa;">여행상품<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Tour Package</h6></th>
								<th style="border: 1px solid #aaa;">출발일<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Departure</h6></th>
								<th style="border: 1px solid #aaa;">도착일<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Arrival</h6></th>
								<th style="border: 1px solid #aaa;">인원<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Travelers</h6></th>
								<th style="border: 1px solid #aaa;">투어비<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Total Price</h6></th>
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
					<br />
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">결제내역 ㅣPayments</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
						<thead>
							 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							
								<th width="25%" style="border: 1px solid #aaa;">결제일<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Date</h6></th>
								<th width="15%" style="border: 1px solid #aaa;">결제방법<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Method</h6></th>
								<th width="30%"style="border: 1px solid #aaa;" >결제금액<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Paid Amount</h6></th>
								
								<th width="10%" style="border: 1px solid #aaa;">담당자<h6 style="margin-bottom: .3rem !important;padding-top:1px ;line-height: .5"><font size =1>Agent</h6></th>
							</tr>
						</thead>
						<tbody>
						<?php
									$qryr = "select * from payment_history where reserveCode = '$r_code' && pay_method !='init' && payment_status != 'RRQUEST'  order by seq_no asc";
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
								<td width="20%" style='text-align: center;border: 1px solid #aaa;'><?=$pamt?><?php if ($row['pay_method']=="creditcard") { echo "<br> (".$row['pay_info'].")";  }?></td>
								
								<td style='text-align: center;border: 1px solid #aaa;' width="20%"><?=$pay_dbinfo['kor_name']?></td>
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
								<td  style='text-align: center; '><?=$sign?> <?php echo number_format(
								$totpay,2);?></td>
								<td style='text-align: right;' width="18%"></td>
							</tr>
							<tr>
								<td style='text-align: left; padding: 5px;'><span ><b>BALANCE DUE</b></span></td>
								<td  style='text-align: center; '></td>
								<td  style='text-align: center; '><?=$sign?> <?php echo number_format(
								$revInfo['last_bal'],2);?></td>
								<td style='text-align: right;' width="18%"></td>
							</tr>
						</tbody>
					</table>
					
					<br/>
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">변경 및 취소규정 | Changes & Cancellation</h2>
						</div>
					</div>
						
					<div style="margin-top: 15px;padding-left: 0px !important;">
						
						<div class="row">
						   <?=$cont['content']?>
							<!--<table border=0 style="border : 0;width: 100%;line-height: inherit;text-align: left;">
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
							</table>-->
						</div>
						
						
					</div>
					
					<div>
					      <table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
							<tbody>
								<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
									<td width=20% style="border: 1px solid #aaa;">&nbsp;보낸사람</td>
									<td width=50% style="border: 1px solid #aaa;">&nbsp;제목</td>
									<td width=30% style="border: 1px solid #aaa;">&nbsp;보낸날짜</td>
								
								</tr>
							</tbody>
							<?php printCustomer(); ?>
						</table>
					</div>
				</main>
			</div>
			<div></div>
		</div>
	</div>
</form>
<div id="dialog" width="800px" title="Basic dialog">
	    <div name="msg" id="msg" style="width: 800px; height: 600px"></div>
  
</div>
    
	<script>
	    $(document).ready(function () {
				$.ajaxSetup({async:false});
				tinymce.init({
					selector: '#board_note',
					height: 400,
					language: 'ko_KR',
					forced_root_block: false,
					plugins: [
						'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
						'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
						'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons'
					],
					
					toolbar: 'undo redo | blocks fontfamily fontsize | ' +
							 'bold italic underline strikethrough | link image media table | ' +
							 'align lineheight | numlist bullist indent outdent | emoticons charmap | ' +
							 'removeformat | code fullscreen preview',
					
					font_family_formats: 
						'나눔고딕=Nanum Gothic, sans-serif;' +
						'맑은 고딕=Malgun Gothic,sans-serif;' +
						'돋움=Dotum,sans-serif;' +
						'굴림=Gulim,sans-serif;' +
						'바탕=Batang,serif;' +
						'Arial=arial,helvetica,sans-serif;' +
						'Times New Roman=times new roman,times,serif;' +
						'Courier New=courier new,courier,monospace',
					
					fontsize_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 28pt 30pt 32pt 34pt 36pt',
					
					images_upload_url: 'cupload_image.php',
					automatic_uploads: true,
					paste_data_images: true,
					images_reuse_filename: true,
					
					document_base_url: 'https://myprt.org/',
					relative_urls: false,
					remove_script_host: false,
					content_style: 'body { font-family: Nanum Gothic, sans-serif; font-size: 14px; }',
					menubar: 'file edit view insert format tools table help',
					branding: false,
					resize: 'both',
					elementpath: false,
					statusbar: true,
					images_upload_handler: function (blobInfo, progress) {
						return new Promise(function(resolve, reject) {
							var xhr = new XMLHttpRequest();
							xhr.withCredentials = false;
							xhr.open('POST', 'cupload_image.php');
							
							xhr.upload.onprogress = function (e) {
								if (progress && e.lengthComputable) {
									progress(e.loaded / e.total * 100);
								}
							};
							
							xhr.onload = function() {
								if (xhr.status === 200) {
									try {
										var json = JSON.parse(xhr.responseText);
										if (json && json.location) {
											var cleanUrl = json.location.split('?')[0];
											resolve(cleanUrl);
										} else {
											reject('Invalid response');
										}
									} catch (e) {
										reject('Invalid JSON response: ' + xhr.responseText);
									}
								} else {
									var msg = 'HTTP Error: ' + xhr.status;
									try { var e2 = JSON.parse(xhr.responseText); if (e2 && e2.error) msg = e2.error; } catch(ex) {}
									reject(msg);
								}
							};
							
							xhr.onerror = function () {
								reject('Upload failed due to network error');
							};
							
							var formData = new FormData();
							formData.append('file', blobInfo.blob(), blobInfo.filename());
							xhr.send(formData);
						});
					}
				});
				$(".js-mail").click(function() {
					$("#mode").val("send_email");
					var r_code =$("#r_code").val();
					$("#print").attr("action","invoice_page2.php?division=3&pdx=2&sub=15&r_code="+r_code+"");
					$("#print").submit();
				});	
		});
		
		function pageprint()
		{ 
		   //$("#mode").val("print");
		   var r_code =$("#r_code").val();
		   $("#print").attr("action","invoice_p2.php?division=3&pdx=2&sub=15&r_code="+r_code+"");
		   $("#print").submit();
		} 

		function viewmail(estimateCode,seqno) {
		 
		 $.getJSON("get_mailc.php?estimateCode="+estimateCode+"&seq="+seqno, function(result){
			$.each(result, function(j,data) {
				
				$( "#msg" ).html(data.message);
				$( "#dialog" ).dialog({ 
					  
					  width: 1000
				});

			});
		 });

	  }
		
	</script>
</body>
</html>	

 
