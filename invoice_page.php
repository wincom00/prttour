<?php
    include "include/inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
/*
    if (!hasMenuAccess($division, $pdx, $sub)) {
		
		Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
		exit;
    }
*/
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
	$totamt = $revInfo['last_total'] ;//- $disamt[amt];
	//$lasttot = $revInfo[last_sale] + $revInfo[last_add];
	$lasttot = $revInfo['last_sale'];
	$lastadd = $revInfo['last_add'];
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
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>{$row1['traveler_nm']}</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$sexcap</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>$rcap</td>
						<td style='text-align:center;border: 1px solid #aaa;padding: 5px;'>{$picknm['pick_name']} {$picknm['pick_time']} - {$picknm['pick_1desc']}</td>
					</tr>";;
			$k++;
		 }

	}
    $cpage =get_html('pay_1');
	//echo $prodInfo[p_type];
	$send_ok = false;
	if ($mode == "send_email") {
			$sbj = "[푸른투어] $r_code 예약이 완료되었습니다.";
			$atc_arr = array();
			$attachment1 = '';
			$attachment2 = '';
			$attachment3 = '';
			$attachment4 = '';
			$attc_name1 = array('savedName' => '');
			$attc_name2 = array('savedName' => '');
			$attc_name3 = array('savedName' => '');
			$attc_name4 = array('savedName' => '');

			$content= file_get_contents( "https://www.myprt.biz/invoice_m.php?division=3&pdx=2&sub=15&r_code=".$r_code."");

			
           // // 메세지
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
			
			$tmpName3 = $_FILES['userfile3']['tmp_name'];
			if(is_uploaded_file($tmpName3)){
				$pds_file3 = $_FILES['userfile3']['name'];
				$attc_name3 = Misc::uploadFileUnsafely($tmpName3 , $pds_file3 , $board_pds_pos);
				
				$fileloc3 = $board_pds_pos . "/" . $attc_name3['savedName'];
				
				array_push($atc_arr,$fileloc3);
				$attachment3 = $attc_name3['savedName'];
			}
			$tmpName4 = $_FILES['userfile4']['tmp_name'];
			if(is_uploaded_file($tmpName4)){
				$pds_file4 = $_FILES['userfile4']['name'];
				$attc_name4 = Misc::uploadFileUnsafely($tmpName4 , $pds_file4 , $board_pds_pos);
				
				$fileloc4 = $board_pds_pos . "/" . $attc_name4['savedName'];
				
				array_push($atc_arr,$fileloc4);
				$attachment4 = $attc_name4['savedName'];
			}
			///$msg = "* 추가 사항 <br />".$board_note."<br /><br />".$content;
			//echo $revInfo[book_email]."TEST";
			
			//exit;
			$msg = str_replace('{ADDINFO}',$board_note,$content);
			$ret= mailsend_h($revInfo['book_email'],$sbj,$msg,$attachment1,$attachment2,$attachment3,$attachment4);
			//$ret= mailsend_a($revInfo[book_email],$sbj,$msg,$attachment1,$attachment2);
			//echo $msg."TEST";
			//exit;
			if (($prodInfo['p_type'] == 1)) {
			 $ret= mailsend_h('online@prttour.com',$sbj,$msg,$attachment1,$attachment2,$attachment3,$attachment4);
			} else {
			 $ret= mailsend_h('online@prttour.com',$sbj,$msg,$attachment1,$attachment2,$attachment3,$attachment4);
			}
			if ($ret['ok']) {
				$send_ok = true;
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
				//echo $revInfo[book_email]."TEST";
			//exit;																	
			$rst2 = mysql_query($qry2, $dbConn);
			
	}
	if ($mode == "print") {
		echo "<meta http-equiv='refresh' content='0; url=./invoice_p.php?division=3&pdx=2&sub=15&r_code=".$r_code."'>";

	}
	$cont = get_html('in_1');
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
<?php if ($send_ok==true){ ?>
  <p style="color:red;text-align:center;margin:10px 0">이메일 전송완료!</p>
<?php } ?>
	<!-- book info-->
<div style="text-align: center;margin-bottom:-10px;margin-top:10px;"><h2>예약내역</h2></div>

<br />
<form name=print id=print action='<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code=<?=$r_code?>' method=post enctype="multipart/form-data">
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
		
		<div class="text-left book_header">*추가내용 정보</div>
		 <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
			<tbody>
				<tr>
					<td ><textarea class="form-control js-tripNote js-ckEditor" name="board_note" id="board_note"> </textarea></td>
					
				</tr>
				<tr>
					<td >첨부파일 : <input type=file name=userfile1 class="form_box" value="" style="width:600px"></textarea></td>
					
				</tr>
				<tr>
					<td >첨부파일 : <input type=file name=userfile2 class="form_box" value="" style="width:600px"></textarea></td>
					
				</tr>
				<tr>
					<td >첨부파일 : <input type=file name=userfile3 class="form_box" value="" style="width:600px"></textarea></td>
					
				</tr>
				<tr>
					<td >첨부파일 : <input type=file name=userfile4 class="form_box" value="" style="width:600px"></textarea></td>
					
				</tr>
				
			</tbody>
		</table>
		<?php endif; ?>
		<div class="invoice1 overflow-auto">
			<div style="min-width: 600px">
				<div class="text-left book_header">1. 예약자 정보</div>
				
				<?php if ($revInfo['pricet'] == 3) { ?>
				<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
					<tbody>
						 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2"  style="border: 1px solid #aaa;padding: 10px;">협력사명</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$rname['kor_name']?></td>
							<td colspan="2"  style="border: 1px solid #aaa;padding: 10px;">담당자명</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['book_pri']?></td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2"  style="text-align: center;border: 1px solid #aaa; padding: 10px;">이메일</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['book_email']?></td>
							<td colspan="2"  style="text-align: center;border: 1px solid #aaa;">연락처</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['book_phone']?></td>
						</tr>
					</tbody>
				</table>

				<?php } else { ?>
				<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
					<tbody>
						 <tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2"  style="border: 1px solid #aaa;padding: 10px;">예약자명</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['book_pri']?></td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2"  style="text-align: center;border: 1px solid #aaa; padding: 10px;">이메일</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['book_email']?></td>
							<td colspan="2"  style="text-align: center;border: 1px solid #aaa;">연락처</td>
							<td colspan="6" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['book_phone']?></td>
						</tr>
					</tbody>
				</table>
				<?php }  ?>
				<br/>
				<!-- 여행 예약정보 -->
				<div class="text-left book_header">2. 여행 예약 정보</div>
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
						<?php// if ($revInfo[pricet] != 3) { ?>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">여행비용</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['base_rate']?> <?php echo number_format($revInfo['last_sale']);?> </td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">방갯수</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['room_cnt']?> </td>
						</tr>
						<?php// } else { ?>
						<!--<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">방갯수</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=$revInfo['room_cnt']?> </td>
						</tr>-->

						<?php// }  ?>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">포함사항</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=nl2br($prodInfo['p_include'])?></td>
						</tr >
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">불포함사항</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=nl2br($prodInfo['p_uninclude'])?></td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">선택관광</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=nl2br($prodInfo['p_otrip'])?>
							</td>
						</tr>
						<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
							<td colspan="2" style="border: 1px solid #aaa;padding: 10px;">준비물</td>
							<td colspan="14" style="background: #fff;padding: 5px;text-align: left;"><?=nl2br($prodInfo['p_prepare'])?>
							</td>
						</tr>
						<!--<tr>
							<td colspan="2" class="active text-center formHeader">국경 통과시 필요서류</td>
							<td colspan="14" class="line_height"><span class="font_bold">한국 국적 고객님 포함, 미국비자면제프로그램(VWP)에 해당되는 국적의 고객님들은 미국입국시 아래의 내용에 따라 적절한 서류를 준비해주셔야 합니다. </span> <br/>
								- 항공편을 이용하여 미국 입국시에는 ESTA(미국 무비자 입국허가증) 사전승인을 받으셔야 합니다.<br/>
								- 캐나다에서 출발하여 육로를 통해 미국 투어상품을 계획하시는 경우 ESTA 사전 승인 절차가 필수는 아니며, 미국입국세 U$6 이 발생합니다. <br/>
								<span class="font_bold"> 구비서류 </span> <br/>
								- 시민권자 : 여권 <br/>
								- 영주권자 : 전자여권(또는 구 여권+미국비자)+P.R Card / 미국 입국세 U$6 <br/>
								- 캐나다 체류비자 소지자 : 전자여권(또는 구 여권+미국비자)+캐나다 체류비자 / 미국 입국세 U$6 <br/>
								- 한국에서 오신 방문객 : 전자여권(또는 구 여권+미국비자)+한국행 리턴티켓 / 미국 입국세 U$6 <br/>
								- 전자여권은 여권 겉 페이지 앞면 하단에 칩 표시가 되어있으며 여권번호가 ‘M+숫자’로 구성되어 있습니다. 알파벳 두 개로 시작하는 것은 구여권입니다.<br/>
								- 부모를 동반하지 않는 미성년자의 미국 여행시, 사전에 ‘부모여행동의서’와 부모님의 여권 사본을 준비해주셔야 합니다. <br/>
								- 출발 전에 여권의 만기일을 꼭 확인하시기 바랍니다. 여권 만기일로부터 6개월 이상 남아있어야 합니다.
							</td>
						</tr>-->
					</tbody>
				</table>
				<br/>
				<!-- 여행자 정보 -->
				<div class="text-left book_header">3. 여행자 정보</div>
				<div class="row">
					<div class="col-sm-12">
						<table style='width: 100%;line-height: 18px;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
							<tbody>
								<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
									<th style="padding: 10px;border: 1px solid #aaa;" width='5%'>NO.</th>
									<th style="border: 1px solid #aaa;" width='15%'>성명</th>
									<th style="border: 1px solid #aaa;" width='15%'>성별</th>
									<th style="border: 1px solid #aaa;" width='15%'>객실</th>
									<th style="border: 1px solid #aaa;">탑승지</th>
								</tr>
							
							
								<?=tourplist()?>
								
							</tbody>
						</table>
						<br/>
					</div>
				</div>
				
				<!-- 결제정보 -->
				<br />
				
			</div>
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
							<img src="https://www.myprt.org/img/top_in3.jpg" data-holder-rendered="true" height="120px" width="100%"/>

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
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">항공내역 ㅣAirline Details</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
						<thead>
							<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
								<th style="border: 1px solid #aaa;">출발일<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Date</h6></th>
								<th style="border: 1px solid #aaa;">구간<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Route</h6></th>
								<th style="border: 1px solid #aaa;">편명<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Flight</h6></th>
								<th style="border: 1px solid #aaa;">PNR / TICKET#</th>
								<th style="border: 1px solid #aaa;">인원<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>PAX</h6></th>
								<th style="border: 1px solid #aaa;">판매금액<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Amount</h6></th>
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
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">크루즈내역 ㅣCruise Details</h2>
						</div>
					</div>
					<table style='width: 100%;line-height: inherit;text-align: left;border: 1px solid #aaa;font-size: 13px;'>
						<thead>
							<tr style="background: #eee;font-weight: bold;text-align: center;padding: 10px;border: 1px solid #aaa;">
								<th style="border: 1px solid #aaa;">출항/하선<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Sail / Return</h6></th>
								<th style="border: 1px solid #aaa;">크루즈/선박<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Line / Ship</h6></th>
								<th style="border: 1px solid #aaa;">출항/입항항구<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Ports</h6></th>
								<th style="border: 1px solid #aaa;">객실/예약번호<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Room / Booking#</h6></th>
								<th style="border: 1px solid #aaa;">인원<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>PAX</h6></th>
								<th style="border: 1px solid #aaa;">판매금액<h6 style="margin-bottom: .1rem !important;line-height: .5"><font size =1>Amount</h6></th>
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
												case "airsys" : 
													$cappay = "항공시스템";
													break;
												case "ypsys" : 
													$cappay = "YP시스템";
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
					$("#print").attr("action","invoice_page.php?division=3&pdx=2&sub=15&r_code="+r_code+"");
					$("#print").submit();
				});	
		});
		
		function pageprint()
		{ 
		   //$("#mode").val("print");
		   var r_code =$("#r_code").val();
		   $("#print").attr("action","invoice_p.php?division=3&pdx=2&sub=15&r_code="+r_code+"");
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

 
