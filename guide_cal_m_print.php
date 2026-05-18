<?php
include "include/header.php";

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}


$seqno = isset($_GET['number']) ? (int)$_GET['number'] : 0;
if ($seqno <= 0) {
    echo "<script>alert('잘못된 접근입니다.');window.close();</script>";
    exit;
}

/* =========================================================
 * [코드명 매핑] code_base: lvcode1|lvcode2 -> comment
 * ========================================================= */
$CODE_MAP = array();
$cbq = "SELECT CONCAT(lvcode1,'|',lvcode2) AS code, comment FROM code_base";
$cbr = mysql_query($cbq, $dbConn);
if ($cbr) {
    while ($row = mysql_fetch_assoc($cbr)) {
        $CODE_MAP[$row['code']] = $row['comment'];
    }
}
function codeName($code, $map) {
    $code = trim((string)$code);
    if ($code === '') return '';
    return isset($map[$code]) ? $map[$code] : $code;
}
function fnum($v){ return number_format((float)$v, 2, '.', ','); }
function inum($v){ return (int)$v; }

/* =========================================================
 * [기본 조회]
 * ========================================================= */
$query = "
    SELECT a.*,
           (SELECT kor_name FROM member_list WHERE userid = a.guide_id AND division='guide') AS kr_name,
           (SELECT base_rate FROM product_master b WHERE a.p_code = b.p_code) AS base_rate
    FROM tour_guide a
    WHERE a.seq_no = {$seqno}
";
$rst1 = mysql_query($query, $dbConn);
$data_row = mysql_fetch_assoc($rst1);
if (!$data_row) {
    echo "<script>alert('데이터가 없습니다.');window.close();</script>";
    exit;
}

$guide_code = getGuideCode($data_row['grand_eCode'], $data_row['sub_eCode']);
$period     = getPeriodbyhotel($data_row['p_code'], $data_row['stDate']);
$p_cnt      = getReserveInfoCnt($data_row['p_code'], $data_row['stDate']);

$mainPcnt = getGuideMainPcnt($data_row['p_code'], $data_row['stDate']);
$subPcnt  = getGuideSubPcnt($data_row['p_code'], $data_row['stDate']);
$mainCnt  = ($mainPcnt['p_cnt'] == '' ? 0 : (int)$mainPcnt['p_cnt']);
$subCnt   = ($subPcnt['p_cnt'] == '' ? 0 : (int)$subPcnt['p_cnt']);
$totPcnt  = $mainCnt + $subCnt;

$query = "SELECT * FROM guide_setmaster WHERE settle_code = '".mysql_real_escape_string($guide_code['settle_code'])."'";
$rst00 = mysql_query($query, $dbConn);
$data_row00 = mysql_fetch_assoc($rst00);
$guide_memo = isset($data_row00['guide_memo']) ? $data_row00['guide_memo'] : '';

/* ===== 체크내역 ===== */
$chk_sql = "SELECT * FROM guide_set_check WHERE settle_code = '".mysql_real_escape_string($guide_code['settle_code'])."' ORDER BY id";
$chk_rst = mysql_query($chk_sql, $dbConn);
$check_rows = array();
while($r = mysql_fetch_assoc($chk_rst)) $check_rows[] = $r;

/* ===== 식사 ===== */
$meals = array('bf'=>array(), 'lunch'=>array(), 'dinner'=>array());
$q = "SELECT * FROM guide_meal WHERE settle_code = '".mysql_real_escape_string($guide_code['settle_code'])."' ORDER BY meal_type, seq_no";
$r = mysql_query($q, $dbConn);
while($row = mysql_fetch_assoc($r)){
    $t = $row['meal_type'];
    if (!isset($meals[$t])) continue;
    $meals[$t][] = $row;
}

/* ===== 입장비 ===== */
$admissions = array();
$q = "SELECT * FROM guide_admission WHERE settle_code = '".mysql_real_escape_string($guide_code['settle_code'])."' ORDER BY seq_no";
$r = mysql_query($q, $dbConn);
while($row = mysql_fetch_assoc($r)) $admissions[] = $row;

/* ===== 옵션 ===== */
$options = array();
$q = "SELECT * FROM guide_option WHERE settle_code = '".mysql_real_escape_string($guide_code['settle_code'])."' ORDER BY seq_no";
$r = mysql_query($q, $dbConn);
while($row = mysql_fetch_assoc($r)) $options[] = $row;

/* ===== 기타비용 ===== */
$etc_guide = array(); $etc_car = array(); $etc_etc = array();
$q = "SELECT * FROM guide_etcamt WHERE settle_code = '".mysql_real_escape_string($guide_code['settle_code'])."' ORDER BY etc_pricety, seq_no";
$r = mysql_query($q, $dbConn);
while($row = mysql_fetch_assoc($r)){
    if ($row['etc_pricety'] == 'guide') $etc_guide[] = $row;
    else if ($row['etc_pricety'] == 'car') $etc_car[] = $row;
    else $etc_etc[] = $row;
}

/* ===== 쇼핑 ===== */
$shopping = array();
$q = "SELECT * FROM guide_shopping WHERE settle_code = '".mysql_real_escape_string($guide_code['settle_code'])."' ORDER BY seq_no";
$r = mysql_query($q, $dbConn);
while($row = mysql_fetch_assoc($r)) $shopping[] = $row;

/* ===== 가이드 납입금 ===== */
$inputs = array();
$q = "SELECT * FROM guide_inputamt WHERE settle_code = '".mysql_real_escape_string($guide_code['settle_code'])."' ORDER BY seq_no";
$r = mysql_query($q, $dbConn);
while($row = mysql_fetch_assoc($r)) $inputs[] = $row;

/* =========================================================
 * [합계 계산(서버)]
 * ========================================================= */
$sum_meal_cnt = array('bf'=>0,'lunch'=>0,'dinner'=>0);
$sum_meal_pp  = array('bf'=>0,'lunch'=>0,'dinner'=>0);
$sum_meal_tot = array('bf'=>0,'lunch'=>0,'dinner'=>0);

foreach($meals as $type=>$rows){
    foreach($rows as $m){
        $cnt = (int)$m['meal_cnt'];
        $pp  = (float)$m['meal_price'];
        $tot = (float)$m['meal_pricetotal'];
        if ($tot == 0) $tot = $cnt * $pp;
        $sum_meal_cnt[$type] += $cnt;
        $sum_meal_pp[$type]  += $pp;
        $sum_meal_tot[$type] += $tot;
    }
}
$sum_meal_all = $sum_meal_tot['bf'] + $sum_meal_tot['lunch'] + $sum_meal_tot['dinner'];

$en_cnt = 0; $en_tot = 0;
foreach($admissions as $a){
    $en_cnt += (int)$a['e_cnt'];
    $en_tot += (float)$a['e_pricetot'];
}

$opt_cnt=0; $opt_cost_tot=0; $opt_price_tot=0; $opt_diff_tot=0; $opt_cprofit=0; $opt_gprofit=0;
foreach($options as $o){
    $opt_cnt       += (int)$o['o_cnt'];
    $opt_cost_tot  += (float)$o['o_pricetot'];
    $opt_price_tot += (float)$o['o_cpricetot'];
    $opt_diff_tot  += (float)$o['o_diffamt'];
    $opt_cprofit   += (float)$o['o_cprofit'];
    $opt_gprofit   += (float)$o['o_gprofit'];
}

$etc_sum = 0;
foreach($etc_guide as $e) $etc_sum += (float)$e['etc_amt'];
foreach($etc_car as $e)   $etc_sum += (float)$e['etc_amt'];
foreach($etc_etc as $e)   $etc_sum += (float)$e['etc_amt'];

$sale_tot=0; $home_com=0; $shop_cprofit=0; $shop_gprofit=0;
foreach($shopping as $s){
    $sale_tot     += (float)$s['tot_amt'];
    $home_com     += (float)$s['home_comamt'];
    $shop_cprofit += (float)$s['c_profit'];
    $shop_gprofit += (float)$s['g_profit'];
}

$input_sum = 0;
foreach($inputs as $ip){
    $u = (float)$ip['input_amt'] * (int)$ip['input_cnt'];
    $input_sum += $u;
}

$pre_amt = (float)$data_row['pre_amt'];

/* (원본 JS 흐름 최대한 유지)
 * 총입금액 = 선지급행사비 + 옵션 회사수익 + 납입금총액
 * 총지급액 = 식사 + 입장 + 기타 + (쇼핑 홈쇼핑컴 + 쇼핑 가이드수익)
 * 정산금액 = 총입금액 - 총지급액
 */
$total_deposit = $pre_amt + $opt_cprofit + $input_sum;
$total_pay     = $sum_meal_all + $en_tot + $etc_sum + ($home_com + $shop_gprofit);
$settle_amt    = $total_deposit - $total_pay;

$sum_check = 0;
foreach($check_rows as $c) $sum_check += (float)$c['amount'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>가이드정산 프린트</title>
<style>
  body{ font-family: Arial, "Malgun Gothic", sans-serif; font-size:12px; color:#000; }
  .wrap{ width: 100%; }
  .title{ font-size:18px; font-weight:700; text-align:center; margin:10px 0 12px; }
  .subline{ text-align:center; margin-bottom:12px; }
  table{ width:100%; border-collapse:collapse; }
  th, td{ border:1px solid #000; padding:6px 6px; vertical-align:middle; }
  th{ background:#f0f0f0; }
  .text-right{ text-align:right; }
  .text-center{ text-align:center; }
  .mt10{ margin-top:10px; }
  .mt20{ margin-top:20px; }
  .small{ font-size:11px; }
  @media print {
    @page { size: A4; margin: 10mm; }
    .no-print{ display:none !important; }
    body{ -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>
<div class="wrap">

  <div class="title">가이드 정산서</div>
  <div class="subline small">
    정산코드 : <b><?=htmlspecialchars($guide_code['settle_code'])?></b>
    &nbsp; | &nbsp;
    행사코드 : <b><?=htmlspecialchars($data_row['sub_eCode'])?></b>
    &nbsp; | &nbsp;
    출발일 : <b><?=htmlspecialchars($data_row['stDate'])?></b>
  </div>

  <table>
    <tr>
      <th style="width:120px">행사명</th>
      <td><?=$data_row['p_name']?></td>
      <th style="width:120px">기준통화</th>
      <td><?=htmlspecialchars($data_row['base_rate'])?></td>
    </tr>
    <tr>
      <th>본행사인원</th>
      <td><?=$mainCnt?>명</td>
      <th>복합행사인원</th>
      <td><?=$subCnt?>명</td>
    </tr>
    <tr>
      <th>행사총인원</th>
      <td><?=$totPcnt?>명</td>
      <th>가이드</th>
      <td><?=htmlspecialchars($data_row['kr_name'])?> 가이드</td>
    </tr>
    <tr>
      <th>차량회사</th>
      <td><?=htmlspecialchars($data_row['c_id'])?></td>
      <th>차량인승</th>
      <td><?=htmlspecialchars($data_row['c_type'])?></td>
    </tr>
  </table>

  <!-- 식사 -->
  <div class="mt20"></div>
  <table>
    <tr><th colspan="6" class="text-center">식사</th></tr>
    <tr>
      <th style="width:70px">구분</th>
      <th style="width:90px">날짜</th>
      <th>식당명</th>
      <th style="width:70px">인원</th>
      <th style="width:90px">원가/P</th>
      <th style="width:110px">원가총액</th>
    </tr>

    <?php
      $labels = array('bf'=>'조식','lunch'=>'중식','dinner'=>'석식');
      foreach($labels as $k=>$label){
        if (count($meals[$k])==0){
          echo "<tr><td class='text-center'>{$label}</td><td colspan='5' class='text-center'>등록 없음</td></tr>";
        } else {
          foreach($meals[$k] as $m){
            $tot = (float)$m['meal_pricetotal'];
            if ($tot==0) $tot = (int)$m['meal_cnt'] * (float)$m['meal_price'];
            echo "<tr>";
            echo "<td class='text-center'>{$label}</td>";
            echo "<td class='text-center'>".htmlspecialchars($m['meal_date'])."</td>";
            echo "<td>".htmlspecialchars($m['meal_rest'])."</td>";
            echo "<td class='text-right'>".inum($m['meal_cnt'])."</td>";
            echo "<td class='text-right'>".fnum($m['meal_price'])."</td>";
            echo "<td class='text-right'>".fnum($tot)."</td>";
            echo "</tr>";
          }
        }
        echo "<tr>";
        echo "<th colspan='3' class='text-right'>{$label} 합계</th>";
        echo "<td class='text-right'>".$sum_meal_cnt[$k]."</td>";
        echo "<td class='text-right'>".fnum($sum_meal_pp[$k])."</td>";
        echo "<td class='text-right'>".fnum($sum_meal_tot[$k])."</td>";
        echo "</tr>";
      }
    ?>
    <tr>
      <th colspan="5" class="text-right">식사비 총액</th>
      <th class="text-right"><?=fnum($sum_meal_all)?></th>
    </tr>
  </table>

  <!-- 입장비 (코드명 출력) -->
  <div class="mt20"></div>
  <table>
    <tr><th colspan="5" class="text-center">입장비</th></tr>
    <tr>
      <th style="width:260px">입장지명</th>
      <th style="width:90px">인원</th>
      <th style="width:100px">원가/P</th>
      <th style="width:120px">원가총액</th>
      <th>비고</th>
    </tr>
    <?php if(count($admissions)==0){ ?>
      <tr><td colspan="5" class="text-center">등록 없음</td></tr>
    <?php } else { foreach($admissions as $a){ ?>
      <tr>
        <td><?=htmlspecialchars(codeName($a['admission_code'], $CODE_MAP))?></td>
        <td class="text-right"><?=inum($a['e_cnt'])?></td>
        <td class="text-right"><?=fnum($a['e_price'])?></td>
        <td class="text-right"><?=fnum($a['e_pricetot'])?></td>
        <td></td>
      </tr>
    <?php }} ?>
    <tr>
      <th class="text-right">합계</th>
      <th class="text-right"><?=$en_cnt?></th>
      <th></th>
      <th class="text-right"><?=fnum($en_tot)?></th>
      <th></th>
    </tr>
  </table>

  <!-- 옵션 (코드명 출력) -->
  <div class="mt20"></div>
  <table>
    <tr><th colspan="10" class="text-center">옵션</th></tr>
    <tr>
      <th style="width:260px">옵션명</th>
      <th style="width:70px">정산</th>
      <th style="width:70px">인원</th>
      <th style="width:90px">원가/P</th>
      <th style="width:110px">원가총액</th>
      <th style="width:90px">옵션가</th>
      <th style="width:110px">옵션총액</th>
      <th style="width:110px">차액</th>
      <th style="width:110px">회사수익</th>
      <th style="width:110px">가이드수익</th>
    </tr>
    <?php if(count($options)==0){ ?>
      <tr><td colspan="10" class="text-center">등록 없음</td></tr>
    <?php } else { foreach($options as $o){ ?>
      <tr>
        <td><?=htmlspecialchars(codeName($o['option_code'], $CODE_MAP))?></td>
        <td class="text-center"><?=htmlspecialchars($o['base_set'])?></td>
        <td class="text-right"><?=inum($o['o_cnt'])?></td>
        <td class="text-right"><?=fnum($o['o_price'])?></td>
        <td class="text-right"><?=fnum($o['o_pricetot'])?></td>
        <td class="text-right"><?=fnum($o['o_cprice'])?></td>
        <td class="text-right"><?=fnum($o['o_cpricetot'])?></td>
        <td class="text-right"><?=fnum($o['o_diffamt'])?></td>
        <td class="text-right"><?=fnum($o['o_cprofit'])?></td>
        <td class="text-right"><?=fnum($o['o_gprofit'])?></td>
      </tr>
    <?php }} ?>
    <tr>
      <th class="text-right" colspan="2">합계</th>
      <th class="text-right"><?=$opt_cnt?></th>
      <th></th>
      <th class="text-right"><?=fnum($opt_cost_tot)?></th>
      <th></th>
      <th class="text-right"><?=fnum($opt_price_tot)?></th>
      <th class="text-right"><?=fnum($opt_diff_tot)?></th>
      <th class="text-right"><?=fnum($opt_cprofit)?></th>
      <th class="text-right"><?=fnum($opt_gprofit)?></th>
    </tr>
  </table>

  <!-- 기타비용 (코드명 출력) -->
  <div class="mt20"></div>
  <table>
    <tr><th colspan="4" class="text-center">가이드/차량/기타 비용</th></tr>
    <tr>
      <th style="width:100px">구분</th>
      <th style="width:260px">항목</th>
      <th style="width:140px">금액</th>
      <th>메모</th>
    </tr>
    <?php
      $renderEtc = function($label, $arr) use ($CODE_MAP){
        if(count($arr)==0){
          echo "<tr><td class='text-center'>{$label}</td><td colspan='3' class='text-center'>등록 없음</td></tr>";
        } else {
          foreach($arr as $e){
            echo "<tr>";
            echo "<td class='text-center'>{$label}</td>";
            echo "<td>".htmlspecialchars(codeName($e['etc_type'], $CODE_MAP))."</td>";
            echo "<td class='text-right'>".fnum($e['etc_amt'])."</td>";
            echo "<td>".htmlspecialchars($e['etc_memo'])."</td>";
            echo "</tr>";
          }
        }
      };
      $renderEtc('가이드', $etc_guide);
      $renderEtc('차량', $etc_car);
      $renderEtc('기타', $etc_etc);
    ?>
    <tr>
      <th colspan="2" class="text-right">합계</th>
      <th class="text-right"><?=fnum($etc_sum)?></th>
      <th></th>
    </tr>
  </table>

  <!-- 쇼핑 (코드명 출력) -->
  <div class="mt20"></div>
  <table>
    <tr><th colspan="5" class="text-center">쇼핑 정산</th></tr>
    <tr>
      <th style="width:260px">쇼핑명</th>
      <th style="width:140px">판매총액</th>
      <th style="width:140px">홈쇼핑컴</th>
      <th style="width:140px">회사수익</th>
      <th style="width:140px">가이드수익</th>
    </tr>
    <?php if(count($shopping)==0){ ?>
      <tr><td colspan="5" class="text-center">등록 없음</td></tr>
    <?php } else { foreach($shopping as $s){ ?>
      <tr>
        <td><?=htmlspecialchars(codeName($s['shop_code'], $CODE_MAP))?></td>
        <td class="text-right"><?=fnum($s['tot_amt'])?></td>
        <td class="text-right"><?=fnum($s['home_comamt'])?></td>
        <td class="text-right"><?=fnum($s['c_profit'])?></td>
        <td class="text-right"><?=fnum($s['g_profit'])?></td>
      </tr>
    <?php }} ?>
    <tr>
      <th class="text-right">합계</th>
      <th class="text-right"><?=fnum($sale_tot)?></th>
      <th class="text-right"><?=fnum($home_com)?></th>
      <th class="text-right"><?=fnum($shop_cprofit)?></th>
      <th class="text-right"><?=fnum($shop_gprofit)?></th>
    </tr>
  </table>

  <!-- 납입금 (값이 코드로 들어오는 경우도 대비해서 codeName 적용) -->
  <div class="mt20"></div>
  <table>
    <tr><th colspan="5" class="text-center">가이드 납입금</th></tr>
    <tr>
      <th style="width:260px">유형</th>
      <th style="width:120px">납입금</th>
      <th style="width:70px">인원</th>
      <th style="width:140px">총액</th>
      <th>메모</th>
    </tr>
    <?php if(count($inputs)==0){ ?>
      <tr><td colspan="5" class="text-center">등록 없음</td></tr>
    <?php } else { foreach($inputs as $ip){
        $u = (float)$ip['input_amt'] * (int)$ip['input_cnt'];
    ?>
      <tr>
        <td><?=htmlspecialchars(codeName($ip['inputamt_type'], $CODE_MAP))?></td>
        <td class="text-right"><?=fnum($ip['input_amt'])?></td>
        <td class="text-right"><?=inum($ip['input_cnt'])?></td>
        <td class="text-right"><?=fnum($u)?></td>
        <td><?=htmlspecialchars($ip['input_memo'])?></td>
      </tr>
    <?php }} ?>
    <tr>
      <th colspan="3" class="text-right">납입금 총액</th>
      <th class="text-right"><?=fnum($input_sum)?></th>
      <th></th>
    </tr>
  </table>

  <!-- 체크 -->
  <div class="mt20"></div>
  <table>
    <tr><th colspan="5" class="text-center">체크</th></tr>
    <tr>
      <th style="width:160px">체크번호</th>
      <th style="width:160px">은행/발행처</th>
      <th style="width:120px">사용일</th>
      <th style="width:140px">금액</th>
      <th>비고</th>
    </tr>
    <?php if(count($check_rows)==0){ ?>
      <tr><td colspan="5" class="text-center">등록 없음</td></tr>
    <?php } else { foreach($check_rows as $c){ ?>
      <tr>
        <td><?=htmlspecialchars($c['check_no'])?></td>
        <td><?=htmlspecialchars($c['bank_name'])?></td>
        <td class="text-center"><?=htmlspecialchars($c['used_date'])?></td>
        <td class="text-right"><?=fnum($c['amount'])?></td>
        <td><?=htmlspecialchars($c['note'])?></td>
      </tr>
    <?php }} ?>
    <tr>
      <th colspan="3" class="text-right">체크 합계</th>
      <th class="text-right"><?=fnum($sum_check)?></th>
      <th></th>
    </tr>
  </table>

  <!-- 메모 -->
  <div class="mt10"></div>
  <table>
    <tr><th class="text-center" style="width:140px">메모</th></tr>
    <tr><td style="height:120px; white-space:pre-wrap;"><?=htmlspecialchars($guide_memo)?></td></tr>
  </table>

  <!-- 정산 요약 -->
  <div class="mt20"></div>
  <table>
    <tr><th colspan="4" class="text-center">가이드정산 합계</th></tr>
    <tr>
      <th style="width:180px">총입금액</th>
      <th style="width:180px">총지급액</th>
      <th style="width:180px">가이드정산금액</th>
      <th>비고</th>
    </tr>
    <tr>
      <td class="text-right"><?=fnum($total_deposit)?></td>
      <td class="text-right"><?=fnum($total_pay)?></td>
      <td class="text-right"><b><?=fnum($settle_amt)?></b></td>
      <td class="small">
        선지급행사비(<?=fnum($pre_amt)?>) + 옵션회사수익(<?=fnum($opt_cprofit)?>) + 납입금(<?=fnum($input_sum)?>)
        - (식사/입장/기타/쇼핑)
      </td>
    </tr>
  </table>

  <div class="no-print mt20 text-center">
    <button onclick="window.print()">인쇄</button>
    <button onclick="window.close()">닫기</button>
  </div>

</div>

<script>
// 열리면 바로 인쇄 (원치 않으면 주석)
window.onload = function(){ window.print(); };
</script>

</body>
</html>
