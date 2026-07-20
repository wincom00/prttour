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


					echo "<tr>
					<td class='cell-c cell-strong'>{$row1['send_reg']}</td>
					<td class='cell-c'><a href=javascript:viewmail('$r_code','{$row1['seq_no']}') >{$row1['subject']}</a></td>
					<td class='cell-c'>{$row1['sent_on']}</td>
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
			echo "<tr>
						<td class='cell-c cell-num'>$k</td>
						<td class='cell-c cell-strong'>{$row1['traveler_nm']}</td>
						<td class='cell-c'>$sexcap</td>
						<td class='cell-c'>$rcap</td>
						<td class='cell-c'>{$picknm['pick_name']} {$picknm['pick_time']} - {$picknm['pick_1desc']}</td>
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
	<link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700|Open+Sans|Roboto&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Nanum+Gothic:400,700,800" rel="stylesheet">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<script src="/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>

</head>
<style type="text/css">
  /* ---------- 기본 ---------- */
  body {
	  font-family: 'Nanum Gothic', 'Malgun Gothic', sans-serif;
	  font-size: 13px;
	  color: #2b3138;
	  background: #eceef1;
	  -webkit-font-smoothing: antialiased;
  }
  a:link, a:visited { color: #2b5d8c; text-decoration: none; font-weight: 700; }
  a:hover { color: #1d4266; text-decoration: underline; }

  /* ---------- 용지(시트) ---------- */
  .sheet {
	  max-width: 960px;
	  margin: 34px auto;
	  background: #fff;
	  border: 1px solid #d7dbe0;
	  box-shadow: 0 1px 4px rgba(30, 40, 55, .07);
	  padding: 56px 64px 64px;
  }
  @media (max-width: 767px) {
	  .sheet { margin: 10px; padding: 26px 20px 34px; }
  }

  /* ---------- 페이지 타이틀 ---------- */
  .page-title {
	  text-align: center;
	  margin: 30px 0 4px;
  }
  .page-title h2 {
	  display: inline-block;
	  font-size: 21px;
	  font-weight: 800;
	  letter-spacing: 10px;
	  text-indent: 10px;
	  color: #22303e;
	  padding-bottom: 8px;
	  border-bottom: 2px solid #22303e;
	  margin: 0;
  }

  /* ---------- 완료 안내 배너 ---------- */
  .confim_book {
	  text-align: center;
	  font-size: 15px;
	  font-weight: 700;
	  color: #2b5d8c;
	  letter-spacing: 1px;
	  padding: 13px 0;
	  border-top: 1px solid #2b5d8c;
	  border-bottom: 1px solid #dfe3e8;
	  background: #f7fafc;
	  margin-bottom: 26px;
  }
  .send-ok {
	  max-width: 920px;
	  margin: 14px auto -6px;
	  text-align: center;
	  color: #1e7a45;
	  background: #eef8f1;
	  border: 1px solid #bfe3cc;
	  padding: 9px 0;
	  font-weight: 700;
  }

  /* ---------- 상단 도구 버튼 ---------- */
  .toolbar { text-align: right; margin-bottom: 20px; }
  .btn-tool {
	  display: inline-block;
	  font-family: inherit;
	  font-size: 12px;
	  font-weight: 700;
	  color: #3a4450;
	  background: #fff;
	  border: 1px solid #b9c0c9;
	  border-radius: 2px;
	  padding: 7px 18px;
	  margin-left: 6px;
	  cursor: pointer;
	  transition: background .15s, border-color .15s;
  }
  .btn-tool:hover { background: #f2f4f7; border-color: #8d97a3; }
  .btn-tool.primary { color: #fff; background: #2b5d8c; border-color: #2b5d8c; }
  .btn-tool.primary:hover { background: #234c73; border-color: #234c73; }

  /* ---------- 섹션 헤더 ---------- */
  .book_header {
	  font-size: 15px;
	  font-weight: 800;
	  color: #22303e;
	  padding: 4px 0 8px 10px;
	  margin: 4px 0 10px;
	  border-left: 3px solid #2b5d8c;
	  border-bottom: 1px solid #e3e7ec;
	  line-height: 1.5;
  }

  /* ---------- 공통 테이블 ---------- */
  table.tbl {
	  width: 100%;
	  border-collapse: collapse;
	  font-size: 13px;
	  line-height: 1.7;
	  margin-bottom: 6px;
	  border-top: 2px solid #3a4a5c;
	  border-bottom: 1px solid #c9cfd6;
  }
  table.tbl th, table.tbl td {
	  border: 1px solid #e2e6ea;
	  border-left: none;
	  border-right: none;
	  padding: 8px 12px;
	  vertical-align: middle;
  }
  table.tbl td.label, table.tbl th.label {
	  background: #f6f8fa;
	  color: #45505c;
	  font-weight: 700;
	  text-align: center;
	  white-space: nowrap;
	  width: 13%;
	  border-right: 1px solid #e2e6ea;
  }
  table.tbl thead th {
	  background: #f6f8fa;
	  color: #45505c;
	  font-weight: 700;
	  text-align: center;
	  border-bottom: 1px solid #c9cfd6;
	  padding: 9px 8px 7px;
  }
  table.tbl thead th h6 {
	  margin: 1px 0 0;
	  font-size: 10px;
	  font-weight: 400;
	  color: #8b95a1;
	  letter-spacing: .5px;
	  text-transform: uppercase;
	  line-height: 1.2;
  }
  table.tbl .cell-c { text-align: center; }
  table.tbl .cell-r { text-align: right; }
  table.tbl .cell-strong { font-weight: 700; }
  table.tbl .cell-num { color: #8b95a1; }
  table.tbl .amount { text-align: right; font-family: 'Montserrat', 'Nanum Gothic', sans-serif; white-space: nowrap; }
  table.tbl tr.row-total td {
	  border-top: 2px solid #3a4a5c;
	  background: #fbfcfd;
	  font-weight: 700;
	  padding-top: 10px;
	  padding-bottom: 10px;
  }
  table.tbl tr.row-total td.amount { font-size: 15px; color: #22303e; }
  table.tbl tr.row-sub td { background: #fbfcfd; font-weight: 700; }
  table.tbl tr.row-due td { background: #fbfcfd; font-weight: 700; color: #b03030; }

  /* ---------- 추가내용/첨부 폼 ---------- */
  .attach-box {
	  border: 1px solid #dfe3e8;
	  margin-bottom: 30px;
  }
  .attach-box .editor-cell { padding: 10px; border-bottom: 1px solid #e9edf1; }
  .attach-box .file-row {
	  display: block;
	  padding: 8px 14px;
	  border-bottom: 1px solid #eef1f4;
	  color: #45505c;
  }
  .attach-box .file-row:last-child { border-bottom: none; }
  .attach-box .file-row input[type=file] { font-size: 12px; max-width: 600px; }

  /* ---------- 인보이스 영역 ---------- */
  .invoice header {
	  padding-bottom: 14px;
	  margin-bottom: 22px;
	  border-bottom: 2px solid #2b5d8c;
  }
  .invoice header img { width: 100%; height: auto; display: block; }
  .invoice-title {
	  text-align: center;
	  font-family: 'Montserrat', sans-serif;
	  font-size: 26px;
	  font-weight: 700;
	  letter-spacing: 8px;
	  text-indent: 8px;
	  color: #22303e;
	  margin: 6px 0 24px;
  }
  .invoice .contacts { margin-bottom: 8px; }
  .invoice-to h2.invoice-to {
	  font-size: 12px;
	  font-weight: 800;
	  letter-spacing: 1px;
	  color: #2b5d8c;
	  margin: 0 0 7px;
	  padding-bottom: 5px;
	  border-bottom: 1px solid #e3e7ec;
  }
  .invoice-to h2.no-color {
	  font-size: 17px;
	  font-weight: 400;
	  color: #22303e;
	  margin: 0 0 2px;
  }
  .invoice-to div { color: #5a6572; }
  .invoice-details { text-align: right; }
  .invoice-details .invoice-id {
	  display: inline-block;
	  font-size: 13px;
	  font-weight: 700;
	  color: #22303e;
	  background: #f6f8fa;
	  border: 1px solid #dfe3e8;
	  padding: 8px 16px;
	  margin-top: 20px;
  }
  .tour-details { margin: 22px 0 0; }
  .tour-details h2.invoice-to {
	  font-size: 13px;
	  font-weight: 800;
	  letter-spacing: .5px;
	  color: #22303e;
	  margin: 0 0 9px;
	  padding: 0 0 6px 10px;
	  border-left: 3px solid #2b5d8c;
	  border-bottom: 1px solid #e3e7ec;
  }

  /* ---------- 취소규정 ---------- */
  .terms-body { padding: 4px 2px 0; line-height: 1.85; color: #45505c; }
  .terms-body .row { margin-left: 0; margin-right: 0; }

  /* ---------- 다이얼로그 ---------- */
  #dialog { display: none; }

  @media print {
	  @page { margin: 0; }
	  body { margin: 16mm; background: #fff; }
	  .sheet { border: none; box-shadow: none; margin: 0; padding: 0; max-width: none; }
	  .toolbar, .attach-box, .send-ok { display: none; }
  }
</style>

<body>
<?php if ($send_ok==true){ ?>
  <div class="send-ok">이메일 전송완료!</div>
<?php } ?>
	<!-- book info-->
<div class="page-title"><h2>예약내역</h2></div>

<form name=print id=print action='<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&r_code=<?=$r_code?>' method=post enctype="multipart/form-data">
  <input type=hidden name=r_code id='r_code' value="<?= $r_code ?>">
  <input type=hidden name=mode id="mode" value="send_email">
	<div id="invoice1" class="sheet">
		<div class="confim_book">예약이 완료되었습니다.</div>

		<?php if ($mode != 'print'): ?>
		<div class="toolbar no-nav" id="custom_button">
			<button type="button" class="btn-tool primary js-mail">이메일 보내기</button>
			<button type="button" class="btn-tool js-print" onclick="pageprint()">프린트</button>
		</div>

		<div class="book_header">*추가내용 정보</div>
		<div class="attach-box">
			<div class="editor-cell"><textarea class="form-control js-tripNote js-ckEditor" name="board_note" id="board_note"> </textarea></div>
			<label class="file-row">첨부파일 : <input type=file name=userfile1 class="form_box" value=""></label>
			<label class="file-row">첨부파일 : <input type=file name=userfile2 class="form_box" value=""></label>
			<label class="file-row">첨부파일 : <input type=file name=userfile3 class="form_box" value=""></label>
			<label class="file-row">첨부파일 : <input type=file name=userfile4 class="form_box" value=""></label>
		</div>
		<?php endif; ?>
		<div class="invoice1 overflow-auto">
			<div style="min-width: 600px">
				<div class="book_header">1. 예약자 정보</div>

				<?php if ($revInfo['pricet'] == 3) { ?>
				<table class="tbl">
					<tbody>
						<tr>
							<td class="label">협력사명</td>
							<td width="37%"><?=$rname['kor_name']?></td>
							<td class="label">담당자명</td>
							<td width="37%"><?=$revInfo['book_pri']?></td>
						</tr>
						<tr>
							<td class="label">이메일</td>
							<td><?=$revInfo['book_email']?></td>
							<td class="label">연락처</td>
							<td><?=$revInfo['book_phone']?></td>
						</tr>
					</tbody>
				</table>

				<?php } else { ?>
				<table class="tbl">
					<tbody>
						<tr>
							<td class="label">예약자명</td>
							<td colspan="3"><?=$revInfo['book_pri']?></td>
						</tr>
						<tr>
							<td class="label">이메일</td>
							<td width="37%"><?=$revInfo['book_email']?></td>
							<td class="label">연락처</td>
							<td width="37%"><?=$revInfo['book_phone']?></td>
						</tr>
					</tbody>
				</table>
				<?php }  ?>
				<br/>
				<!-- 여행 예약정보 -->
				<div class="book_header">2. 여행 예약 정보</div>
				<table class="tbl">
					<tbody>
						<tr>
							<td class="label">여행명</td>
							<td colspan="3"><?=$prodInfo['p_name']?></td>
						</tr>
						<tr>
							<td class="label">여행기간</td>
							<td width="37%"><?=$revInfo['stDate']?>(<?=$sweekday?>)~<?=$revInfo['edDate']?>(<?=$eweekday?>)</td>
							<td class="label">통합예약번호</td>
							<td width="37%"><?=$revInfo['grand_revNo']?></td>
						</tr >
						<tr>
							<td class="label">여행인원</td>
							<td><?=$revInfo['p_cnt']?>인</td>
							<td class="label">예약번호</td>
							<td><?=$revInfo['reserveCode']?></td>
						</tr >
						<tr>
							<td class="label">예약일</td>
							<td><?=$revInfo['revDate']?></td>
							<td class="label">예약상담원</td>
							<td><?=$rev_dbinfo['kor_name']?></td>
						</tr>
						<?php// if ($revInfo[pricet] != 3) { ?>
						<tr>
							<td class="label">여행비용</td>
							<td colspan="3"><?=$revInfo['base_rate']?> <?php echo number_format($revInfo['last_sale']);?> </td>
						</tr>
						<tr>
							<td class="label">방갯수</td>
							<td colspan="3"><?=$revInfo['room_cnt']?> </td>
						</tr>
						<?php// } else { ?>
						<!--<tr>
							<td class="label">방갯수</td>
							<td colspan="3"><?=$revInfo['room_cnt']?> </td>
						</tr>-->

						<?php// }  ?>
						<tr>
							<td class="label">포함사항</td>
							<td colspan="3"><?=nl2br($prodInfo['p_include'])?></td>
						</tr >
						<tr>
							<td class="label">불포함사항</td>
							<td colspan="3"><?=nl2br($prodInfo['p_uninclude'])?></td>
						</tr>
						<tr>
							<td class="label">선택관광</td>
							<td colspan="3"><?=nl2br($prodInfo['p_otrip'])?>
							</td>
						</tr>
						<tr>
							<td class="label">준비물</td>
							<td colspan="3"><?=nl2br($prodInfo['p_prepare'])?>
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
				<div class="book_header">3. 여행자 정보</div>
				<div class="row">
					<div class="col-sm-12">
						<table class="tbl">
							<thead>
								<tr>
									<th width='5%'>NO.</th>
									<th width='15%'>성명</th>
									<th width='15%'>성별</th>
									<th width='15%'>객실</th>
									<th>탑승지</th>
								</tr>
							</thead>
							<tbody>
								<?=tourplist()?>
							</tbody>
						</table>
						<br/>
					</div>
				</div>

				<!-- 결제정보 -->

			</div>
		  </div>
	</div>
	<!-- invoice page -->

	<div id="invoice" class="sheet" style="page-break-before: always;">
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
							<img src="https://www.myprt.org/img/top_in3.jpg" data-holder-rendered="true"/>

						</div>
					</div>
				</header>
				<main>
					<h2 class="invoice-title">INVOICE</h2>
					<div class="row contacts">
						<div class="col invoice-to">
							<h2 class="invoice-to">고객정보 | Customer(s)</h2>
							<h2 class="no-color"><b><?=$revInfo['book_pri']?></b> 님</h2>
							<div><?=$revInfo['book_phone']?></div>

						</div>

						<div class="col invoice-details">
							<h5 class="invoice-id">예약번호 : <?=$r_code?>
							</h5>
						</div>
					</div>
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">예약내역 ㅣTour Details</h2>
						</div>
					</div>
					<table class="tbl">
						<thead>
							 <tr>
								<th>여행상품<h6>Tour Package</h6></th>
								<th>출발일<h6>Departure</h6></th>
								<th>도착일<h6>Arrival</h6></th>
								<th>인원<h6>Travelers</h6></th>
								<th width="18%">투어비<h6>Total Price</h6></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?=$revInfo['p_name']?></td>
								<td class="cell-c"><?=$revInfo['stDate']?>(<?=$seweekday?>)</td>
								<td class="cell-c"><?=$revInfo['edDate']?>(<?=$eeweekday?>)</td>
								<td class="cell-c"><?=$revInfo['p_cnt']?></td>
								<td class="amount"><?=$sign?> <?php echo number_format($lasttot,2);?></td>
							</tr>
							<tr>
								<td>항공금액</td>
								<td class="cell-c"></td>
								<td class="cell-c"></td>
								<td class="cell-c"></td>
								<td class="amount"><?=$sign?> <?php echo number_format($airamt['samt'],2);?></td>
							</tr>
							<?php if ($cruisetot > 0) { ?>
							<tr>
								<td>크루즈금액</td>
								<td class="cell-c"></td>
								<td class="cell-c"></td>
								<td class="cell-c"></td>
								<td class="amount"><?=$sign?> <?php echo number_format($cruisetot,2);?></td>
							</tr>
							<?php } ?>
							<tr>
								<td>추가금액</td>
								<td class="cell-c"></td>
								<td class="cell-c"></td>
								<td class="cell-c"></td>
								<td class="amount"><?=$sign?> <?php echo number_format($lastadd,2);?></td>
							</tr>
							<tr>
								<td>할인금액</td>
								<td class="cell-c"></td>
								<td class="cell-c"></td>
								<td class="cell-c"></td>
								<td class="amount"><?=$sign?> <?php echo number_format($disamt['amt'],2);?></td>
							</tr>

							<tr class="row-total">
								<td><b>최종 결제금액</b></td>
								<td colspan="3"></td>
								<td class="amount"><?=$sign?> <?php echo number_format($totamt,2);?></td>
							</tr>


						</tbody>
					</table>
					<?php if (count($airList) > 0) { ?>
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">항공내역 ㅣAirline Details</h2>
						</div>
					</div>
					<table class="tbl">
						<thead>
							<tr>
								<th>출발일<h6>Date</h6></th>
								<th>구간<h6>Route</h6></th>
								<th>편명<h6>Flight</h6></th>
								<th>PNR / TICKET#</th>
								<th>인원<h6>PAX</h6></th>
								<th width="18%">판매금액<h6>Amount</h6></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($airList as $av) {
							$a_route = trim($av['a_start_airport']) . (trim($av['a_stop_airport']) != "" ? " → " . $av['a_stop_airport'] : "");
							$a_pnrtk = trim($av['a_pnr_number']) . (trim($av['a_tk_number']) != "" ? " / " . $av['a_tk_number'] : "");
						?>
							<tr>
								<td class="cell-c"><?=$av['a_airline_start']?></td>
								<td class="cell-c"><?=$a_route?></td>
								<td class="cell-c"><?=$av['a_airport_name']?></td>
								<td class="cell-c"><?=$a_pnrtk?></td>
								<td class="cell-c"><?=$av['a_airport_cnt']?></td>
								<td class="amount"><?=$sign?> <?php echo number_format($av['a_airline_amt'],2);?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<?php } ?>
					<?php if (count($cruiseList) > 0) { ?>
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">크루즈내역 ㅣCruise Details</h2>
						</div>
					</div>
					<table class="tbl">
						<thead>
							<tr>
								<th>출항/하선<h6>Sail / Return</h6></th>
								<th>크루즈/선박<h6>Line / Ship</h6></th>
								<th>출항/입항항구<h6>Ports</h6></th>
								<th>객실/예약번호<h6>Room / Booking#</h6></th>
								<th>인원<h6>PAX</h6></th>
								<th width="18%">판매금액<h6>Amount</h6></th>
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
								<td class="cell-c"><?=$c_period?></td>
								<td class="cell-c"><?=$c_lineship?></td>
								<td class="cell-c"><?=$c_ports?></td>
								<td class="cell-c"><?=$c_roombook?></td>
								<td class="cell-c"><?=$cv['c_pax']?></td>
								<td class="amount"><?=$sign?> <?php echo number_format($cv['c_sale_amt'],2);?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<?php } ?>
					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">결제내역 ㅣPayments</h2>
						</div>
					</div>
					<table class="tbl">
						<thead>
							 <tr>
								<th width="25%">결제일<h6>Date</h6></th>
								<th width="15%">결제방법<h6>Method</h6></th>
								<th width="30%">결제금액<h6>Paid Amount</h6></th>

								<th width="10%">담당자<h6>Agent</h6></th>
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
							<tr>
								<td><?=$row['wdate']?></td>
								<td class="cell-c"><?php echo $cappay; ?></td>
								<td class="cell-c"><?=$pamt?><?php if ($row['pay_method']=="creditcard") { echo "<br> (".$row['pay_info'].")";  }?></td>

								<td class="cell-c"><?=$pay_dbinfo['kor_name']?></td>
							</tr>

						<?php
										$i++;
						                endwhile;

									} else {
						?>

						<?php

									}
						?>
							<tr class="row-sub">
								<td>TOTAL PAID</td>
								<td class="cell-c"></td>
								<td class="cell-c"><?=$sign?> <?php echo number_format(
								$totpay,2);?></td>
								<td></td>
							</tr>
							<tr class="row-due">
								<td><b>BALANCE DUE</b></td>
								<td class="cell-c"></td>
								<td class="cell-c"><?=$sign?> <?php echo number_format(
								$revInfo['last_bal'],2);?></td>
								<td></td>
							</tr>
						</tbody>
					</table>

					<div class="row tour-details">
						<div class="col-md-12 invoice-to">
							<h2 class="invoice-to">변경 및 취소규정 | Changes & Cancellation</h2>
						</div>
					</div>

					<div class="terms-body">

						<div class="row">
						   <?=$cont['content']?>

						</div>


					</div>

					<div class="tour-details">
					      <table class="tbl">
							<thead>
								<tr>
									<th width=20%>보낸사람</th>
									<th width=50%>제목</th>
									<th width=30%>보낸날짜</th>

								</tr>
							</thead>
							<tbody>
							<?php printCustomer(); ?>
							</tbody>
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


