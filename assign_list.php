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

	function printSingle(){
		global $dbConn,$division,$crev,$pdx,$sub,$productName,$evest,$startDate1,$endDate1;

		// --- 검색 조건 ---
		if ($productName) $qrynm = " && a.p_name like '%$productName%'";
		else $qrynm ="";

		if ($startDate1 == "") {
			$startDate1 = date("Y-m-d",strtotime("now"));
			$endDate1   = date("Y-m-d",strtotime("+1 month"));
		}
		if ($startDate1) $qrysdate = " && c.stDate between '$startDate1' and '$endDate1'";
		else $qrysdate ="";

		if ($evest) $qryeve = " && c.ev_status='$evest'";
		else $qryeve ="";

		// -----------------------------------------------------
		// union select 구문 (grand_eCode별 배정 카운트 추가)
		// -----------------------------------------------------
		$selectCols = "
			a.reserveCode,c.grand_eCode,a.p_code,a.p_name,c.stDate,
			b.c_code1,b.c_code2,b.p_own,c.tour_pcnt,d.room_num,
			b.p_day,b.p_cnt,c.r_status,c.ev_status,b.p_type,
			(SELECT COUNT(*) FROM tour_car tc WHERE tc.grand_eCode=c.grand_eCode) AS car_cnt,
			(SELECT COUNT(*) FROM hotel_assign ha WHERE ha.grand_eCode=c.grand_eCode) AS hotel_cnt,
			(SELECT COUNT(*) FROM tour_guide tg WHERE tg.grand_eCode=c.grand_eCode) AS guide_cnt,
			(SELECT COUNT(*) FROM hotelroom_assign hra WHERE hra.grand_eCode=c.grand_eCode) AS rooming_cnt
		";

		$fromJoin = "
			FROM reserve_info a,
				 product_master b,
				 tour_master c
				 LEFT OUTER JOIN hotelroom_assign d ON c.grand_eCode=d.grand_eCode
		";

		$whereBase1 = "
			WHERE a.p_code=b.p_code && b.p_code=c.p_code
			  && b.p_type in ('1','3','4')
			  && a.stDate=c.stDate
			  && a.rev_status!='CANCEL'
			  && a.p_code not in ('SPICKUP003','SSEND007')
			  && (b.p_code not like '%ADD%')
			  $qrynm $qrysdate $qryeve
			GROUP BY a.p_code,c.stDate
		";
		$whereBase2 = "
			WHERE a.p_code=b.p_code && b.p_code=c.p_code
			  && b.p_type in ('2')
			  && a.stDate=c.stDate
			  && a.rev_status!='CANCEL'
			  && a.p_code not in ('SPICKUP003','SSEND007')
			  && (b.p_code like '%ADD%')
			  $qrynm $qrysdate $qryeve
			GROUP BY a.p_code,c.stDate
		";

		$whereBase3 = "
			WHERE a.p_code=b.p_code && b.p_code=c.p_code
			  && b.p_type in ('1','3','4')
			  && a.rev_status!='CANCEL'
			  && a.p_code not in ('SPICKUP003','SSEND007')
			  && (b.p_code like '%ADD%')
			  $qrynm $qrysdate $qryeve
			GROUP BY a.p_code,c.stDate
		";

		$qry1 = "
			SELECT $selectCols $fromJoin $whereBase1
			UNION
			SELECT $selectCols $fromJoin $whereBase2
			UNION
			SELECT $selectCols $fromJoin $whereBase3
			ORDER BY stDate DESC
		";
        //echo $qry1;
		$rst1 = mysql_query($qry1,$dbConn);

		while($row1 = mysql_fetch_assoc($rst1)){
			$cinfo2 = codebaseName($row1['c_code2']);

			// 예약상태
			if ($row1['r_status']=='P') $row1['r_status']="<font color=red>예약접수중</font>";
			elseif ($row1['r_status']=='C') $row1['r_status']="<font color=red>예약마감</font>";
			elseif ($row1['r_status']=='') $row1['r_status']="<font color=red>미등록</font>";

			// 행사상태
			$evmap = array('1'=>'미확정','2'=>'확정','3'=>'만차','4'=>'취소','5'=>'기타');
			$row1['ev_status'] = isset($evmap[$row1['ev_status']]) ?
				"<font color=red>".$evmap[$row1['ev_status']]."</font>" : "<font color=red>미등록</font>";

			// 투어분류
			if ($row1['p_type']==1) $row1['p_type']="자사단일상품";
			elseif ($row1['p_type']==3) $row1['p_type']="인바운드로컬합류";
			elseif ($row1['p_type']==4) $row1['p_type']="인바운드단독";

			// 기존 운영 화면과 동일한 예약/대기 집계 함수를 사용한다.
			$pcnt = getReserveInfoCnt($row1['p_code'],$row1['stDate']);
			$pwcnt = getReserveWaitSCnt($row1['p_code'],$row1['stDate']);
			if ($pwcnt['cnt']=="") $pwcnt['cnt']=0;
			if ($pcnt['cnt']=="")  $pcnt['cnt']=0;
			if ($row1['tour_pcnt']!="") $row1['p_cnt']=$row1['tour_pcnt'];

			// 배지 (하나의 컬럼)
			$badgeHTML = "";
			$badgeHTML .= "<span class='mini-badge ".($row1['car_cnt']>0?"mini-ok":"mini-no")."'>차량</span>";
			$badgeHTML .= "<span class='mini-badge ".($row1['hotel_cnt']>0?"mini-ok":"mini-no")."'>호텔</span>";
			$badgeHTML .= "<span class='mini-badge ".($row1['guide_cnt']>0?"mini-ok":"mini-no")."'>가이드</span>";
			$badgeHTML .= "<span class='mini-badge ".($row1['rooming_cnt']>0?"mini-ok":"mini-no")."'>루밍</span>";

			if ($pcnt['cnt']!=0) {
				echo "<tr class='arhef' data-href='assign_m.php?division=$division&pdx=$pdx&sub=$sub&st=".$row1['stDate']."&pcode=".$row1['p_code']."'>
						<td align='center'>".$row1['grand_eCode']."</td>
						<td>".$row1['p_type']."</td>
						<td>".$cinfo2['comment']."</td>
						<td>".$row1['p_code']."</td>
						<td>".$row1['p_name']."</td>
						<td align='center'>".$row1['stDate']."</td>
						<td align='center'>".$row1['p_cnt']."</td>
						<td align='center'>".$pcnt['cnt']."/".$pwcnt['cnt']."</td>
						<td align='center'>".$row1['r_status']."</td>
						<td align='center'>".$row1['ev_status']."</td>
						<td align='center'>".$badgeHTML."</td>
					</tr>";
			
		}
	}
	}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>통합행사배정</title>
<style>
.mini-badge{display:inline-block;padding:2px 6px;border-radius:10px;font-size:11px;line-height:1;color:#fff;margin:1px;}
.mini-ok{background:#28a745;}
.mini-no{background:#6c757d;}
</style>
</head>
<body>
<div id="contentwrapper" class="reservationDetailForm">
	<div class="main_content">
		<div id="jCrumbs" class="breadCrumb module">
			<ul>
				<li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
				<li><a href="#">행사배정관리</a></li>
				<li>통합행사배정</li>
			</ul>
		</div>

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<form action="" name="frmName" method="post">
					<input type="hidden" name="mode" value="search">
					<table class="table table-bordered table-condensed">
						<tr>
							<td class="titletd text-center">상품명</td>
							<td><input type="text" name="productName" class="inpubase" value=""/></td>
							<td class="titletd text-center">출발일</td>
							<td>
								<div class="row">
									<div class="col-sm-6">
										<input type="search" id="startDate1" name="startDate1" class="inpubase tourDate1" placeholder="시작일" value="<?=$startDate1?>" autocomplete="off"/>
									</div>
									<div class="col-sm-6">
										<input type="search" id="endDate1" name="endDate1" class="inpubase tourDate2" placeholder="마지막일" value="<?=$endDate1?>" autocomplete="off"/>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td class="titletd text-center">행사상태</td>
							<td colspan="3">
								<select class="form-control" name="evest">
									<option value="">- 선택 -</option>
									<option value="1" <?php if($evest==1) echo "selected"; ?>>미확정</option>
									<option value="2" <?php if($evest==2) echo "selected"; ?>>확정</option>
									<option value="3" <?php if($evest==3) echo "selected"; ?>>만차</option>
									<option value="4" <?php if($evest==4) echo "selected"; ?>>취소</option>
									<option value="5" <?php if($evest==5) echo "selected"; ?>>기타</option>
								</select>
							</td>
						</tr>
						<tr><td colspan="4" class="text-center"><button type="submit" class="btn btn-primary btn-sm btn1">검색</button></td></tr>
					</table>
				</form>

				<div class="row">
					<div class="col-sm-12">
						<table class="table table-striped table-bordered table-hover table-condensed" id="ctable">
							<thead>
								<tr>
									<th>통합행사코드</th>
									<th>투어분류</th>
									<th>상품지역분류</th>
									<th>상품코드</th>
									<th>상품명</th>
									<th>출발일</th>
									<th>정원</th>
									<th>예약/대기</th>
									<th>예약상태</th>
									<th>행사상태</th>
									<th>배정현황</th>
								</tr>
							</thead>
							<tbody>
								<?=printSingle()?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php include "include/side_m.php"; ?>
<script>
$(document).ready(function(){
	$('tr[data-href]').on("click",function(){window.open($(this).data('href'),'_blank');});
	$('#ctable').dataTable({pageLength:100,"order":[[5,"desc"]]});
	$(".dataTables_length").hide();
	$('.tourDate1').datepicker({ format: "yyyy-mm-dd", autoclose: true });
    $('.tourDate2').datepicker({ format: "yyyy-mm-dd", autoclose: true });
});
</script>
</body>
</html>
