<?php
// estimate_export_breakdown2.php (PHP 5.6 + mysql_*)
// - 프린트 전용 견적서 화면: 헤더/사이드 제거, 표 헤더 반복, 배경색 보존
// - 사용: estimate_export_breakdown2.php?id=123&auto=1

ini_set('display_errors','0'); error_reporting(E_ALL & ~E_NOTICE);

// header.php가 화면에 뭔가를 출력하더라도, DB 연결만 쓰고 출력은 버립니다.
ob_start();
include 'include/header.php'; // $dbConn 기대
ob_end_clean();

// 입력 파라미터
$id   = isset($_GET['id'])   ? (int)$_GET['id'] : 0;
$auto = isset($_GET['auto']) ? (int)$_GET['auto'] : 0;

// 유틸
function money($v){ $n=is_numeric($v)?(float)$v:0; return '$'.number_format($n,2); }
function is_assoc_array($arr){
  if(!is_array($arr)) return false;
  if($arr===array()) return false;
  return array_keys($arr)!==range(0,count($arr)-1);
}

// 데이터 로딩
$master=array();
$r = mysql_query("SELECT * FROM estimate_master WHERE id=".$id, $dbConn);
if($r && mysql_num_rows($r)) $master=mysql_fetch_assoc($r);

$items=array();
$r = mysql_query("SELECT * FROM estimate_items WHERE estimate_id=".$id." ORDER BY section,id", $dbConn);
if($r){ while($row=mysql_fetch_assoc($r)) $items[]=$row; }

$sections=array();
foreach($items as $it){
  $sec = isset($it['section']) ? $it['section'] : 'ETC';
  if(!isset($sections[$sec])) $sections[$sec]=array();
  $sections[$sec][]=$it;
}
$section_totals=array();

function build_dates($master,$pool=array()){
  $dates=array();
  $sd=trim(isset($master['start_date'])?$master['start_date']:'');
  $ed=trim(isset($master['end_date'])?$master['end_date']:'');
  if($sd!=='' && $ed!=='' && strtotime($sd)!==false && strtotime($ed)!==false){
    $cur=strtotime($sd); $end=strtotime($ed);
    while($cur<=$end){ $dates[]=date('Y-m-d',$cur); $cur=strtotime('+1 day',$cur); }
  } else if(!empty($pool)) {
    $set=array(); foreach($pool as $d) $set[$d]=true; $dates=array_keys($set); sort($dates);
  }
  if(empty($dates)){ $base=time(); for($i=0;$i<3;$i++) $dates[]=date('Y-m-d',strtotime("+$i day",$base)); }
  return $dates;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>상세 견적서 (인쇄)</title>
<style>
@font-face{font-family:'NotoSansKR';src:url('fonts/NotoSansKR-Regular.otf') format('opentype');}
*{font-family:'NotoSansKR',-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,'Apple SD Gothic Neo','Malgun Gothic',sans-serif;}

:root{
  --brand1:#2E86AB; --brand2:#A23B72; --acc:#F18F01;
  --line:#dee2e6; --bg:#f8f9fa;
}

html,body{margin:0;padding:0;color:#212529;background:#fff;}
.wrap{padding:14mm 12mm;}
.header{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:8mm;}
.title{font-size:20px;font-weight:800;margin:0;color:#222;}
.meta{font-size:11px;color:#6c757d;text-align:right;line-height:1.4}
.badge{display:inline-block;background:linear-gradient(135deg,var(--brand1),var(--brand2));color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;margin-left:6px;}

.info{display:grid;grid-template-columns:auto 1fr auto 1fr auto 1fr auto 1fr;gap:6px 16px;background:var(--bg);padding:10px;border-radius:6px;margin-bottom:10mm;font-size:12px;}
.label{font-weight:700;color:#495057;white-space:nowrap;}
.val{color:#111}

.hsec{background:linear-gradient(135deg,#A23B72,#F18F01);color:#fff;padding:6px 10px;border-radius:4px;margin:10mm 0 4mm;font-weight:700;font-size:13px;}

.tbl{width:100%;border-collapse:collapse;font-size:11px;margin-bottom:4mm;}
.tbl th,.tbl td{border:1px solid var(--line);padding:4px 6px;text-align:center;}
.tbl th{background:#f8f9fa;font-weight:700;font-size:10px;}
.left{text-align:left}.right{text-align:right}
.cur{color:#2E86AB;font-weight:700}
.sumrow{background:#e3f2fd;font-weight:700}

.sumwrap{background:linear-gradient(135deg,var(--brand1),var(--brand2));color:#fff;border-radius:6px;margin-top:10mm;padding:10px;}
.pills{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;}
.pill{background:rgba(255,255,255,.18);padding:3px 8px;border-radius:999px;font-size:10px;font-weight:700}
.ftotal{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.box{background:rgba(255,255,255,.12);padding:10px;border-radius:6px;text-align:center}
.box .k{font-size:11px;margin-bottom:4px;opacity:.9}
.box .v{font-size:18px;font-weight:800}

/* 인풋형 셀 제거(보기만) */
.tbl input[type="text"]{border:none;background:transparent;width:auto;text-align:center;padding:0;margin:0}

/* 프린트 설정 */
@page{size:A4;margin:12mm 10mm 14mm 10mm;}
@media print{
  html,body{-webkit-print-color-adjust:exact; print-color-adjust:exact;}
  thead{display:table-header-group} tfoot{display:table-footer-group}
  tr{page-break-inside:avoid;break-inside:avoid}
}
</style>
<?php if($auto): ?>
<script>
addEventListener('load',function(){ setTimeout(function(){ print(); },150); });
</script>
<?php endif; ?>
</head>
<body>
<div class="wrap">

  <!-- 헤더/사이드 없이, 프린트 전용 헤더만 -->
  <div class="header">
    <h1 class="title">상세 견적서 <span class="badge">PRINT</span></h1>
    <div class="meta">
      작성일: <?= htmlspecialchars(isset($master['wdate'])?$master['wdate']:'') ?><br>
      출력일: <?= date('Y-m-d H:i') ?>
    </div>
  </div>

  <!-- 기본 정보 -->
  <div class="info">
    <div class="label">PAX</div><div class="val"><?= (int)(isset($master['pax'])?$master['pax']:0) ?></div>
    <div class="label">FOC</div><div class="val"><?= (int)(isset($master['foc'])?$master['foc']:0) ?></div>
    <div class="label">총인원</div><div class="val"><?= (int)(isset($master['total_pax'])?$master['total_pax']:0) ?></div>
    <div class="label">TO</div><div class="val"><?= htmlspecialchars(isset($master['to_name'])?$master['to_name']:'',ENT_QUOTES,'UTF-8') ?></div>

    <div class="label">여행 시작일</div><div class="val"><?= isset($master['start_date'])?$master['start_date']:'' ?></div>
    <div class="label">여행 종료일</div><div class="val"><?= isset($master['end_date'])?$master['end_date']:'' ?></div>
    <div class="label">GROUP</div><div class="val"><?= htmlspecialchars(isset($master['group_name'])?$master['group_name']:'',ENT_QUOTES,'UTF-8') ?></div>
    <div class="label">비고</div><div class="val"><?= htmlspecialchars(isset($master['memo'])?$master['memo']:'',ENT_QUOTES,'UTF-8') ?></div>
  </div>

  <?php
  /* ===== 1) HOTEL ===== */
  if(!empty($sections['HOTEL'])):
    $tot=0;
  ?>
  <div class="hsec">1) HOTEL</div>
  <table class="tbl">
    <thead>
      <tr>
        <th width="10%">지역</th><th width="12%">날짜</th><th width="6%">요일</th>
        <th width="30%">호텔명</th><th width="8%">방수</th><th width="12%">요금(USD)</th>
        <th width="6%">박수</th><th width="16%">합계</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($sections['HOTEL'] as $it):
        $etc=json_decode(isset($it['etc_json'])?$it['etc_json']:'{}',true); if(!is_array($etc)) $etc=array();
        $tot += (float)(isset($it['sum'])?$it['sum']:0);
      ?>
      <tr>
        <td><?= htmlspecialchars(isset($etc['region'])?$etc['region']:'',ENT_QUOTES,'UTF-8') ?></td>
        <td><?= isset($etc['date'])?$etc['date']:'' ?></td>
        <td><?= isset($etc['weekday'])?$etc['weekday']:'' ?></td>
        <td class="left"><?= htmlspecialchars((isset($it['label'])?$it['label']:'').' 또는 동급호텔',ENT_QUOTES,'UTF-8') ?></td>
        <td><?= isset($it['cnt'])?(int)$it['cnt']:0 ?></td>
        <td class="right cur"><?= money(isset($it['unit'])?$it['unit']:0) ?></td>
        <td><?= isset($it['qty'])?(int)$it['qty']:0 ?></td>
        <td class="right cur"><?= money(isset($it['sum'])?$it['sum']:0) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="sumrow"><td colspan="7" class="right">HOTEL 소계</td><td class="right cur"><?= money($tot) ?></td></tr>
    </tbody>
  </table>
  <?php $section_totals['HOTEL']=$tot; endif; ?>

  <?php
  /* ===== 2) MEAL ===== */
  if(!empty($sections['MEAL'])):
    $pool=array();
    foreach($sections['MEAL'] as $it){
      $e=json_decode(isset($it['etc_json'])?$it['etc_json']:'{}',true); if(!is_array($e)) $e=array();
      if(!empty($e['dates']) && is_array($e['dates'])){
        if(is_assoc_array($e['dates'])) foreach($e['dates'] as $d=>$c) $pool[]=$d; else foreach($e['dates'] as $d) $pool[]=$d;
      }
    }
    $dates=build_dates($master,$pool);
    $types=array('조식','중식','석식');
    $unit=array('조식'=>0,'중식'=>0,'석식'=>0);
    $pax =array('조식'=>0,'중식'=>0,'석식'=>0);
    $mat =array('조식'=>array(),'중식'=>array(),'석식'=>array());
    foreach($dates as $d) foreach($types as $t) $mat[$t][$d]=0;

    foreach($sections['MEAL'] as $it){
      $label=(string)(isset($it['label'])?$it['label']:''); 
      $e=json_decode(isset($it['etc_json'])?$it['etc_json']:'{}',true); if(!is_array($e)) $e=array();
      $type='';
      if(isset($e['meal_type']) && $e['meal_type']!=='') $type=$e['meal_type'];
      else{
        if(function_exists('mb_strpos')){
          if(mb_strpos($label,'조식')!==false) $type='조식';
          elseif(mb_strpos($label,'중식')!==false) $type='중식';
          elseif(mb_strpos($label,'석식')!==false) $type='석식';
        }else{
          if(strpos($label,'조식')!==false) $type='조식';
          elseif(strpos($label,'중식')!==false) $type='중식';
          elseif(strpos($label,'석식')!==false) $type='석식';
        }
      }
      if(!in_array($type,$types,true)) continue;
      if(isset($e['unit_per_pax']) && is_numeric($e['unit_per_pax']) && $unit[$type]<=0) $unit[$type]=(float)$e['unit_per_pax'];
      if(isset($e['pax']) && is_numeric($e['pax']) && $pax[$type]<=0) $pax[$type]=(int)$e['pax'];
      if(!empty($e['dates']) && is_array($e['dates'])){
        if(is_assoc_array($e['dates'])) foreach($e['dates'] as $d=>$c) if(isset($mat[$type][$d])) $mat[$type][$d]+=(int)$c;
        else foreach($e['dates'] as $d) if(isset($mat[$type][$d])) $mat[$type][$d]+=1;
      }
    }

    $tot=0.0; $row=array(); $perUnit=0; $paxShow=0;
    foreach($types as $t){
      $occ=0; foreach($dates as $d) $occ+=(int)$mat[$t][$d];
      $row[$t]=((float)$unit[$t]) * ((int)$pax[$t]) * $occ;
      $tot += $row[$t];
      $perUnit += (float)$unit[$t];
      if($paxShow===0 && $pax[$t]>0) $paxShow=(int)$pax[$t];
    }
  ?>
  <div class="hsec">2) MEAL</div>
  <table class="tbl">
    <thead>
      <tr>
        <th width="10%">구분</th>
        <?php foreach($dates as $d): ?><th><?= htmlspecialchars($d,ENT_QUOTES,'UTF-8') ?></th><?php endforeach; ?>
        <th>일인단가</th><th>인원</th><th>합계</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($types as $t): ?>
      <tr>
        <td><?= $t ?></td>
        <?php foreach($dates as $d): ?><td><?= (int)$mat[$t][$d] ?></td><?php endforeach; ?>
        <td class="right cur"><?= $unit[$t] ?></td>
        <td><?= (int)$pax[$t] ?></td>
        <td class="right cur"><?= money($row[$t]) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="sumrow">
        <td class="right" colspan="<?= 1+count($dates) ?>">MEAL 소계</td>
        <td class="right cur"><?= money($perUnit) ?></td>
        <td><?= (int)$paxShow ?></td>
        <td class="right cur"><?= money($tot) ?></td>
      </tr>
    </tbody>
  </table>
  <?php $section_totals['MEAL']=$tot; endif; ?>

  <?php
  /* ===== 3) TRANSPORTATION ===== */
  if(!empty($sections['TRANSPORT'])):
    $pool=array();
    foreach($sections['TRANSPORT'] as $it){
      $e=json_decode(isset($it['etc_json'])?$it['etc_json']:'{}',true); if(!is_array($e)) $e=array();
      if(!empty($e['dates']) && is_array($e['dates'])){
        if(is_assoc_array($e['dates'])) foreach($e['dates'] as $d=>$c) $pool[]=$d; else foreach($e['dates'] as $d) $pool[]=$d;
      }
    }
    $dates=build_dates($master,$pool);
    $rows=array(); // label => ['unit'=>0,'veh'=>0,'m'=>[d=>cnt]]
    foreach($sections['TRANSPORT'] as $it){
      $label=trim((string)(isset($it['label'])?$it['label']:'차량')); if($label==='') $label='차량';
      $e=json_decode(isset($it['etc_json'])?$it['etc_json']:'{}',true); if(!is_array($e)) $e=array();
      $unit = (isset($it['unit']) && is_numeric($it['unit'])) ? (float)$it['unit'] : 0.0;
      $veh  = 0.0;
      if(isset($e['unit_per_car']) && is_numeric($e['unit_per_car'])) $veh=(float)$e['unit_per_car'];
      elseif(isset($it['cnt']) && is_numeric($it['cnt'])) $veh=(float)$it['cnt'];
      if(!isset($rows[$label])){ $rows[$label]=array('unit'=>0,'veh'=>0,'m'=>array()); foreach($dates as $d) $rows[$label]['m'][$d]=0; }
      if($unit>0) $rows[$label]['unit']=$unit;
      if($veh>0)  $rows[$label]['veh']=$veh;
      if(!empty($e['dates']) && is_array($e['dates'])){
        if(is_assoc_array($e['dates'])) foreach($e['dates'] as $d=>$c) if(isset($rows[$label]['m'][$d])) $rows[$label]['m'][$d]+=(int)$c;
        else foreach($e['dates'] as $d) if(isset($rows[$label]['m'][$d])) $rows[$label]['m'][$d]+=1;
      }
    }
    $tot=0; $rowTot=array();
    foreach($rows as $nm=>$r){ $occ=0; foreach($dates as $d) $occ+=(int)$r['m'][$d]; $rowTot[$nm]=((float)$r['unit'])*((float)$r['veh'])*$occ; $tot+=$rowTot[$nm]; }
  ?>
  <div class="hsec">3) TRANSPORTATION</div>
  <table class="tbl">
    <thead>
      <tr>
        <th width="12%">차량/항목</th>
        <?php foreach($dates as $d): ?><th><?= htmlspecialchars($d,ENT_QUOTES,'UTF-8') ?></th><?php endforeach; ?>
        <th width="10%">단가(USD/대/일)</th><th width="8%">차량수</th><th width="12%">합계</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $nm=>$r): ?>
      <tr>
        <td class="left"><?= htmlspecialchars($nm,ENT_QUOTES,'UTF-8') ?></td>
        <?php foreach($dates as $d): ?><td><?= (int)$r['m'][$d] ?></td><?php endforeach; ?>
        <td class="right cur"><?= money($r['unit']) ?></td>
        <td><?= number_format($r['veh'],2) ?></td>
        <td class="right cur"><?= money($rowTot[$nm]) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="sumrow"><td class="right" colspan="<?= 2+count($dates) ?>">TRANSPORTATION 소계</td><td></td><td class="right cur"><?= money($tot) ?></td></tr>
    </tbody>
  </table>
  <?php $section_totals['TRANSPORT']=$tot; endif; ?>

  <?php
  /* ===== 4) TICKET ===== */
  if(!empty($sections['TICKET'])):
    $tot=0;
  ?>
  <div class="hsec">4) TICKET</div>
  <table class="tbl">
    <thead><tr><th width="40%">티켓명</th><th width="20%">단가</th><th width="20%">매수/인원</th><th width="20%">합계</th></tr></thead>
    <tbody>
      <?php foreach($sections['TICKET'] as $it): $tot+=(float)(isset($it['sum'])?$it['sum']:0); ?>
      <tr>
        <td class="left"><?= htmlspecialchars(isset($it['label'])?$it['label']:'',ENT_QUOTES,'UTF-8') ?></td>
        <td class="right cur"><?= money(isset($it['unit'])?$it['unit']:0) ?></td>
        <td><?= isset($it['cnt'])?(int)$it['cnt']:0 ?></td>
        <td class="right cur"><?= money(isset($it['sum'])?$it['sum']:0) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="sumrow"><td colspan="3" class="right">TICKET 소계</td><td class="right cur"><?= money($tot) ?></td></tr>
    </tbody>
  </table>
  <?php $section_totals['TICKET']=$tot; endif; ?>

  <?php
  /* ===== 5) GUIDE ===== */
  if(!empty($sections['GUIDE'])):
    $tot=0;
  ?>
  <div class="hsec">5) GUIDE</div>
  <table class="tbl">
    <thead><tr><th width="40%">가이드/설명</th><th width="20%">일당/단가</th><th width="20%">일수</th><th width="20%">합계</th></tr></thead>
    <tbody>
      <?php foreach($sections['GUIDE'] as $it): $tot+=(float)(isset($it['sum'])?$it['sum']:0); ?>
      <tr>
        <td class="left"><?= htmlspecialchars(isset($it['label'])?$it['label']:'',ENT_QUOTES,'UTF-8') ?></td>
        <td class="right cur"><?= money(isset($it['unit'])?$it['unit']:0) ?></td>
        <td><?= isset($it['qty'])?(int)$it['qty']:0 ?></td>
        <td class="right cur"><?= money(isset($it['sum'])?$it['sum']:0) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="sumrow"><td colspan="3" class="right">GUIDE 소계</td><td class="right cur"><?= money($tot) ?></td></tr>
    </tbody>
  </table>
  <?php $section_totals['GUIDE']=$tot; endif; ?>

  <?php
  /* ===== 6) ETC ===== */
  if(!empty($sections['ETC'])):
    $tot=0;
  ?>
  <div class="hsec">6) ETC</div>
  <table class="tbl">
    <thead><tr><th width="50%">항목</th><th width="15%">단가</th><th width="15%">수량</th><th width="20%">합계</th></tr></thead>
    <tbody>
      <?php foreach($sections['ETC'] as $it): $tot+=(float)(isset($it['sum'])?$it['sum']:0); ?>
      <tr>
        <td class="left"><?= htmlspecialchars(isset($it['label'])?$it['label']:'',ENT_QUOTES,'UTF-8') ?></td>
        <td class="right cur"><?= money(isset($it['unit'])?$it['unit']:0) ?></td>
        <td><?= isset($it['qty'])?(int)$it['qty']:0 ?></td>
        <td class="right cur"><?= money(isset($it['sum'])?$it['sum']:0) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="sumrow"><td colspan="3" class="right">ETC 소계</td><td class="right cur"><?= money($tot) ?></td></tr>
    </tbody>
  </table>
  <?php $section_totals['ETC']=$tot; endif; ?>

  <?php
  /* ===== 7) TIP ===== */
  if(!empty($sections['TIP'])):
    $tot=0;
  ?>
  <div class="hsec">7) TIP</div>
  <table class="tbl">
    <thead><tr><th width="50%">항목</th><th width="15%">단가</th><th width="15%">수량/인원</th><th width="20%">합계</th></tr></thead>
    <tbody>
      <?php foreach($sections['TIP'] as $it): $tot+=(float)(isset($it['sum'])?$it['sum']:0); ?>
      <tr>
        <td class="left"><?= htmlspecialchars(isset($it['label'])?$it['label']:'',ENT_QUOTES,'UTF-8') ?></td>
        <td class="right cur"><?= money(isset($it['unit'])?$it['unit']:0) ?></td>
        <td><?= isset($it['cnt'])?(int)$it['cnt']:0 ?></td>
        <td class="right cur"><?= money(isset($it['sum'])?$it['sum']:0) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="sumrow"><td colspan="3" class="right">TIP 소계</td><td class="right cur"><?= money($tot) ?></td></tr>
    </tbody>
  </table>
  <?php $section_totals['TIP']=$tot; endif; ?>

  <?php
  /* ===== 8) PROFIT ===== */
  $profit_items=0.0;
  if(!empty($sections['PROFIT'])):
    foreach($sections['PROFIT'] as $it) $profit_items += (float)(isset($it['sum'])?$it['sum']:0);
  ?>
  <div class="hsec">8) PROFIT</div>
  <table class="tbl">
    <thead><tr><th width="55%">항목</th><th width="15%">단가</th><th width="10%">수량</th><th width="20%">합계</th></tr></thead>
    <tbody>
      <?php foreach($sections['PROFIT'] as $it): ?>
      <tr>
        <td class="left"><?= htmlspecialchars(isset($it['label'])?$it['label']:'',ENT_QUOTES,'UTF-8') ?></td>
        <td class="right cur"><?= money(isset($it['unit'])?$it['unit']:0) ?></td>
        <td><?= isset($it['qty'])?(int)$it['qty']:0 ?></td>
        <td class="right cur"><?= money(isset($it['sum'])?$it['sum']:0) ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="sumrow"><td colspan="3" class="right">PROFIT 소계(아이템)</td><td class="right cur"><?= money($profit_items) ?></td></tr>
    </tbody>
  </table>
  <?php endif;
  $master_profit=(float)(isset($master['profit'])?$master['profit']:0);
  $master_profit_memo=trim((string)(isset($master['profit_memo'])?$master['profit_memo']:''));
  if($master_profit>0 || $master_profit_memo!==''): ?>
  <table class="tbl"><tbody>
    <tr class="sumrow">
      <td class="left" style="width:80%;">PROFIT(마스터): <?= htmlspecialchars($master_profit_memo,ENT_QUOTES,'UTF-8') ?></td>
      <td class="right cur" style="width:20%;"><?= money($master_profit) ?></td>
    </tr>
  </tbody></table>
  <?php endif;
  $section_totals['PROFIT']=(isset($section_totals['PROFIT'])?$section_totals['PROFIT']:0)+$profit_items+$master_profit;

  // 최종 합계
  $auto_grand=0.0; foreach($section_totals as $v) $auto_grand+=(float)$v;
  $grand_total=(isset($master['grand_total']) && is_numeric($master['grand_total']))?(float)$master['grand_total']:0.0;
  $per_pax=(isset($master['per_pax']) && is_numeric($master['per_pax']))?(float)$master['per_pax']:0.0;
  $tp=(int)(isset($master['total_pax'])?$master['total_pax']:0);
  if($per_pax<=0 && $grand_total>0 && $tp>0) $per_pax=$grand_total/$tp;
  elseif($per_pax<=0 && $grand_total<=0 && $auto_grand>0 && $tp>0) $per_pax=$auto_grand/$tp;
  ?>
  <div class="sumwrap">
    <div class="pills">
      <span class="pill">HOTEL: <?= money(isset($section_totals['HOTEL'])?$section_totals['HOTEL']:0) ?></span>
      <span class="pill">MEAL: <?= money(isset($section_totals['MEAL'])?$section_totals['MEAL']:0) ?></span>
      <span class="pill">TRANSPORT: <?= money(isset($section_totals['TRANSPORT'])?$section_totals['TRANSPORT']:0) ?></span>
      <span class="pill">TICKET: <?= money(isset($section_totals['TICKET'])?$section_totals['TICKET']:0) ?></span>
      <span class="pill">GUIDE: <?= money(isset($section_totals['GUIDE'])?$section_totals['GUIDE']:0) ?></span>
      <span class="pill">ETC: <?= money(isset($section_totals['ETC'])?$section_totals['ETC']:0) ?></span>
      <span class="pill">TIP: <?= money(isset($section_totals['TIP'])?$section_totals['TIP']:0) ?></span>
      <span class="pill">PROFIT: <?= money(isset($section_totals['PROFIT'])?$section_totals['PROFIT']:0) ?></span>
    </div>
    <div class="ftotal">
      <div class="box"><div class="k">10) TOTAL TOUR FEE</div><div class="v"><?= money($grand_total>0?$grand_total:$auto_grand) ?></div></div>
      <div class="box"><div class="k">11) 1인당 요금</div><div class="v"><?= money($per_pax) ?></div></div>
    </div>
  </div>

</div>
</body>
</html>
