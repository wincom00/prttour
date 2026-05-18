<?php
// estimate_view.php - ???繹먮굟瑗????썹땟?????戮?뜪???????곗맫 + ?꿔꺂??袁ㅻ븶????????????ㅻ쿋?? (PHP 5.6 + mysql_*)
include 'include/header.php';
include 'include/side_m.php';

/* $dbConn ?? include/header.php ?嚥싲갭큔????mysql_connect ?????????잙갭큔?딆뼇???????????醫딆쓧??癲ル슢怡??????딅젩. */

/* ????怨몄７ ?????쀪쑴??嚥????*/
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ???뚯??????됰슦???? master */
$master = array();
$sql  = "SELECT * FROM estimate_master WHERE id = " . (int)$id;
$result = mysql_query($sql);
if ($result && mysql_num_rows($result)) {
    $master = mysql_fetch_assoc($result);
}

/* ???뚯??????됰슦???? items */
$sql  = "SELECT * FROM estimate_items WHERE estimate_id = " . (int)$id . " ORDER BY section, id";
$result = mysql_query($sql);
$items = array();
if ($result) {
    while ($row = mysql_fetch_assoc($result)) {
        $items[] = $row;
    }
}

/* ?????????곗뒩泳?봺異?*/
$sections = array();
foreach ($items as $item) {
    $sec = isset($item['section']) ? $item['section'] : 'ETC';
    if (!isset($sections[$sec])) $sections[$sec] = array();
    $sections[$sec][] = $item;
}

/* ?????????????????용닽???熬곣뫖?삥납??關?? */
function money($v) { $n = is_numeric($v) ? (float)$v : 0; return '$'.number_format($n, 2); }

function estimate_per_pax_divisor($master) {
  $pax = (int)(isset($master['pax']) ? $master['pax'] : 0);
  $foc = (int)(isset($master['foc']) ? $master['foc'] : 0);
  $charge_pax = $pax - $foc;
  if ($charge_pax < 0) $charge_pax = 0;
  return $charge_pax > 0 ? ($charge_pax + 1) : 0;
}

/* ????熬곣뫖利?影?뉖뜦???? (PHP 5.6 ?癲ル슢?뤸뤃?? */
function is_assoc_array($arr){
  if (!is_array($arr)) return false;
  if ($arr === array()) return false;
  return array_keys($arr) !== range(0, count($arr)-1);
}

?>
<style>
/* ====== ???뚯???????????? ====== */
.breakdown-wrapper { width:100%; padding:10px; box-sizing:border-box; }
.breakdown-header { background:linear-gradient(135deg,#2E86AB 0%,#A23B72 100%); color:#fff; padding:12px 15px; margin-bottom:15px; border-radius:6px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.breakdown-title { font-size:18px; font-weight:bold; margin:0; flex-shrink:0; }
.action-buttons { display:flex; gap:6px; flex-wrap:wrap; }
.btn-export { padding:6px 10px; border:none; border-radius:4px; color:#fff; text-decoration:none; font-size:11px; font-weight:bold; transition:.2s; white-space:nowrap; }
.btn-excel{background:#28a745;} .btn-pdf{background:#dc3545;} .btn-edit{background:#ffc107;color:#212529;} .btn-list{background:#6c757d;}
.btn-export:hover{opacity:.8; text-decoration:none; color:#fff;}
.info-grid{ display:grid; grid-template-columns:auto 1fr auto 1fr auto 1fr auto 1fr; gap:8px 15px; background:#f8f9fa; padding:12px; border-radius:6px; margin-bottom:15px; font-size:12px; align-items:center; }
.info-label{ font-weight:bold; color:#495057; white-space:nowrap; }
.info-value{ color:#212529; }
.section-header{ background:linear-gradient(135deg,#A23B72 0%,#F18F01 100%); color:#fff; padding:8px 12px; margin:15px 0 8px; border-radius:4px; font-weight:bold; font-size:13px; }
.data-table{ width:100%; border-collapse:collapse; margin-bottom:12px; font-size:11px; }
.data-table th,.data-table td{ border:1px solid #dee2e6; padding:4px 6px; text-align:center; }
.data-table th{ background:#f8f9fa; font-weight:bold; font-size:10px; }
.data-table .text-left{text-align:left;} .data-table .text-right{text-align:right;}
.data-table .currency{ color:#2E86AB; font-weight:bold; }
.total-row{ background:#e3f2fd; font-weight:bold; }
.summary-section{ background:linear-gradient(135deg,#2E86AB 0%,#A23B72 100%); color:#fff; padding:12px; border-radius:6px; margin-top:15px; }
.summary-pills{ display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;}
.pill{ background:rgba(255,255,255,.2); padding:3px 6px; border-radius:12px; font-size:10px; font-weight:bold; white-space:nowrap;}
.final-totals{ display:grid; grid-template-columns:1fr 1fr; gap:8px;}
.final-total-item{ background:rgba(255,255,255,.1); padding:8px; border-radius:4px; text-align:center;}
.final-total-label{ font-size:11px; margin-bottom:4px;}
.final-total-amount{ font-size:16px; font-weight:bold;}
@media (max-width:1024px){ .breakdown-header{flex-direction:column; text-align:center;} .info-grid{grid-template-columns:auto 1fr; gap:4px 10px;} .final-totals{grid-template-columns:1fr;} }
@media (max-width:768px){ .breakdown-wrapper{padding:5px;} .data-table{font-size:10px;} .data-table th,.data-table td{padding:2px 4px;} }
@media print{ .action-buttons{display:none!important;} .breakdown-wrapper{padding:0!important;} }
</style>
<style>
/* estimate_form.php ???뚯??? PDF ????썼キ?κ괌???????*/
:root{ --bg:#fff; --ink:#161a1d; --muted:#667085; --line:#e5e7eb; --key:#0b5bd3; --soft:#f5f7fb; --warn:#fff2b2;}
.breakdown-wrapper{max-width:1280px;margin:24px auto 80px;padding:0 16px;color:var(--ink);font:16px/1.6 system-ui,Segoe UI,Apple SD Gothic Neo,Malgun Gothic,sans-serif}
.breakdown-header{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:10px 0 18px;background:transparent!important;color:var(--ink)!important;padding:0!important;border-radius:0!important;flex-wrap:wrap}
.breakdown-title{font-size:28px!important;font-weight:800!important;margin:0!important;letter-spacing:.2px;color:var(--ink)!important}
.action-buttons{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.btn-export{height:36px;padding:0 12px;border:1px solid var(--line)!important;border-radius:8px!important;background:#fff;cursor:pointer;display:inline-flex;align-items:center;color:var(--ink);text-decoration:none;font-size:14px;font-weight:700;white-space:nowrap}
.btn-excel{border-color:var(--key)!important;color:#fff!important;background:var(--key)!important}
.btn-pdf{border-color:#dc3545!important;color:#fff!important;background:#dc3545!important}
.btn-edit{border-color:#ffc107!important;background:#ffc107!important;color:#212529!important}
.btn-list{border-color:#6c757d!important;background:#6c757d!important;color:#fff!important}
.btn-export:hover{text-decoration:none;opacity:.85}
.info-grid{display:grid;grid-template-columns:repeat(4,auto 1fr);gap:10px 16px;background:#fff!important;border:1px solid var(--line);border-radius:12px!important;box-shadow:0 1px 2px rgba(16,24,40,.04);padding:16px!important;margin-bottom:16px!important;font-size:14px;align-items:center}
.info-label{font-weight:700;color:#495057;white-space:nowrap}
.info-value{color:#212529}
.section-header{padding:14px 16px!important;border:1px solid var(--line);border-bottom:0;background:var(--soft)!important;border-radius:12px 12px 0 0!important;margin:16px 0 0!important;font-size:18px!important;font-weight:700;color:var(--ink)!important}
.data-table{width:100%;border-collapse:collapse;margin-bottom:16px!important;background:#fff;border:1px solid var(--line);border-radius:0 0 12px 12px;box-shadow:0 1px 2px rgba(16,24,40,.04);font-size:14px!important}
.data-table th,.data-table td{border:1px solid var(--line)!important;padding:10px 12px!important;text-align:center;vertical-align:middle}
.data-table th{background:#fafafa!important;font-weight:700;font-size:14px!important}
.data-table .text-left{text-align:left}
.data-table .text-right{text-align:right}
.data-table .currency{color:#0b5bd3!important;font-weight:700}
.total-row{background:#f7fbff!important;font-weight:800}
.summary-section{background:#fff!important;border:1px solid var(--line);border-radius:12px!important;box-shadow:0 1px 2px rgba(16,24,40,.04);padding:14px!important;margin-top:14px!important;color:var(--ink)!important}
.summary-pills{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:10px;padding:10px 14px;background:#f0f6ff;border:1px dashed #cfe0ff;border-radius:10px;margin-bottom:10px}
.pill{padding:6px 10px!important;border-radius:999px!important;background:#eef2ff!important;font-weight:700;color:var(--ink)!important;white-space:nowrap}
.final-totals{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.final-total-item{border:1px solid var(--line);border-radius:10px!important;padding:12px!important;text-align:center;background:#fff!important}
.final-total-label{font-size:14px;color:var(--muted)!important;margin-bottom:4px}
.final-total-amount{font-size:22px!important;font-weight:800;color:var(--ink)!important}
input[readonly]{border:1px solid var(--line);border-radius:8px;background:transparent}
@media (max-width:1024px){ .info-grid{grid-template-columns:auto 1fr}.final-totals{grid-template-columns:1fr} }
@media (max-width:768px){ .breakdown-wrapper{padding:0 8px}.data-table{font-size:10px!important}.data-table th,.data-table td{padding:4px!important} }
@media print{
  html,body{background:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  @page{size:A4 portrait;margin:12mm}
  #sidebar,.sidebar,.left_nav,.left_menu,.side_m,#side_m,
  header,.header,.navbar,.topbar,#jCrumbs,.breadCrumb,
  .action-buttons{display:none!important}
  #contentwrapper,.reservationDetailForm,.main_content,.breakdown-wrapper{width:100%!important;max-width:100%!important;margin:0!important;padding:0!important}
  .data-table,.data-table tr,.summary-section,.info-grid{break-inside:avoid!important;page-break-inside:avoid!important}
  .data-table thead{display:table-header-group!important}
}
</style>

<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div class="breakdown-wrapper">
      <!-- ????諛몄? -->
      <div class="breakdown-header">
        <h1 class="breakdown-title">BREAKDOWN QUOTATION</h1>
        <div class="action-buttons">
          <a href="estimate_excel.php?action=download_excel&estimate_id=<?= (int)$id ?>" class="btn-export btn-excel">엑셀</a>
          <a href="estimate_export_breakdown.php?action=pdf&id=<?= (int)$id ?>" class="btn-export btn-pdf">PDF</a>
          <a href="estimate_form.php?id=<?= (int)$id ?>" class="btn-export btn-edit">수정</a>
          <a href="estimate_list.php" class="btn-export btn-list">목록</a>
        </div>
      </div>

      <!-- ???뚯?????癲ル슢???ъ쒜?-->
      <div class="info-grid">
        <span class="info-label">PAX</span><span class="info-value"><?= (int)(isset($master['pax']) ? $master['pax'] : 0) ?></span>
        <span class="info-label">FOC</span><span class="info-value"><?= (int)(isset($master['foc']) ? $master['foc'] : 0) ?></span>
        <span class="info-label">총인원</span><span class="info-value"><?= (int)(isset($master['total_pax']) ? $master['total_pax'] : 0) ?></span>
        <span class="info-label">TO</span><span class="info-value"><?= htmlspecialchars(isset($master['to_name']) ? $master['to_name'] : '', ENT_QUOTES, 'UTF-8') ?></span>

        <span class="info-label">여행 시작일</span><span class="info-value"><?= isset($master['start_date']) ? $master['start_date'] : '' ?></span>
        <span class="info-label">여행 종료일</span><span class="info-value"><?= isset($master['end_date']) ? $master['end_date'] : '' ?></span>
        <span class="info-label">작성일</span><span class="info-value"><?= isset($master['wdate']) ? $master['wdate'] : '' ?></span>
        <span class="info-label">GROUP</span><span class="info-value"><?= htmlspecialchars(isset($master['group_name']) ? $master['group_name'] : '', ENT_QUOTES, 'UTF-8') ?></span>
      </div>

      <?php
      $section_totals = array();

      /* ========== 1) HOTEL ========== */
      if (!empty($sections['HOTEL'])):
          $hotel_total = 0;
      ?>
      <div class="section-header">1) HOTEL</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="10%">지역</th>
            <th width="12%">날짜</th>
            <th width="6%">요일</th>
            <th width="30%">호텔명</th>
            <th width="8%">방수</th>
            <th width="12%">요금(USD)</th>
            <th width="6%">박수</th>
            <th width="16%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['HOTEL'] as $it):
              $etc = json_decode(isset($it['etc_json']) ? $it['etc_json'] : '{}', true);
              if (!is_array($etc)) $etc = array();
              $hotel_total += (float)(isset($it['sum']) ? $it['sum'] : 0);
          ?>
          <tr>
            <td><?= htmlspecialchars(isset($etc['region']) ? $etc['region'] : '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= isset($etc['date']) ? $etc['date'] : '' ?></td>
            <td><?= isset($etc['weekday']) ? $etc['weekday'] : '' ?></td>
            <td class="text-left"><?= htmlspecialchars(isset($it['label']) ? $it['label'] : '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= isset($it['cnt']) ? $it['cnt'] : 0 ?></td>
            <td class="text-right currency"><?= money(isset($it['unit']) ? $it['unit'] : 0) ?></td>
            <td><?= isset($it['qty']) ? $it['qty'] : 0 ?></td>
            <td class="text-right currency"><?= money(isset($it['sum']) ? $it['sum'] : 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="7" class="text-right">HOTEL 소계</td>
            <td class="text-right currency"><?= money($hotel_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['HOTEL'] = $hotel_total; endif; ?>

      <!-- ========== 2) MEAL (??濚밸Ŧ?긷칰?????꾩룆?????꿔꺂??????⑸걦????| PHP 5.6 ?癲ル슢?뤸뤃?? ========== -->
      <?php
      if (!empty($sections['MEAL'])):

        /* 1) ????◈? ????諛몄? ???????*/
        $dates = array();
        $sd = isset($master['start_date']) ? trim($master['start_date']) : '';
        $ed = isset($master['end_date'])   ? trim($master['end_date'])   : '';
        if ($sd !== '' && $ed !== '' && strtotime($sd)!==false && strtotime($ed)!==false) {
          $cur = strtotime($sd); $end = strtotime($ed);
          while ($cur <= $end) { $dates[] = date('Y-m-d',$cur); $cur = strtotime('+1 day',$cur); }
        } else {
          $dateSet = array();
          foreach ($sections['MEAL'] as $it) {
            $etc = json_decode(isset($it['etc_json']) ? $it['etc_json'] : '{}', true);
            if (!is_array($etc)) $etc = array();
            if (!empty($etc['dates']) && is_array($etc['dates'])) {
              if (is_assoc_array($etc['dates'])) {
                foreach ($etc['dates'] as $d=>$v) $dateSet[$d] = true;
              } else {
                foreach ($etc['dates'] as $d) $dateSet[$d] = true;
              }
            }
          }
          $dates = array_keys($dateSet);
          sort($dates);
        }
        if (empty($dates)) { $base=time(); for($i=0;$i<3;$i++) $dates[] = date('Y-m-d', strtotime('+'.$i.' day',$base)); }

        /* 2) ?꿔꺂??????⑸걦???????/?癲ル슢????*/
        $mealTypes  = array('BREAKFAST','LUNCH','DINNER');
        $mealUnits  = array('BREAKFAST'=>0,'LUNCH'=>0,'DINNER'=>0);
        $mealPax    = array('BREAKFAST'=>0,'LUNCH'=>0,'DINNER'=>0);
        $mealSavedTotals = array('BREAKFAST'=>0.0,'LUNCH'=>0.0,'DINNER'=>0.0);
        $mealMatrix = array('BREAKFAST'=>array(),'LUNCH'=>array(),'DINNER'=>array());

        foreach ($dates as $d) foreach ($mealTypes as $mt) $mealMatrix[$mt][$d]=0;

        $mealFallbackIndex = 0;
        foreach ($sections['MEAL'] as $it) {
          $label = (string)(isset($it['label']) ? $it['label'] : '');
          $etc   = json_decode(isset($it['etc_json']) ? $it['etc_json'] : '{}', true);
          if (!is_array($etc)) $etc = array();

          // ?????????
          $type = '';
          if (isset($etc['meal_type']) && $etc['meal_type']!=='') {
            $type = $etc['meal_type'];
          } else {
            if (function_exists('mb_strpos')) {
              if (stripos($label,'breakfast')!==false) $type='BREAKFAST';
              elseif (stripos($label,'lunch')!==false) $type='LUNCH';
              elseif (stripos($label,'dinner')!==false) $type='DINNER';
            } else {
              if (stripos($label,'breakfast')!==false) $type='BREAKFAST';
              elseif (stripos($label,'lunch')!==false) $type='LUNCH';
              elseif (stripos($label,'dinner')!==false) $type='DINNER';
            }
          }
          if ($type === '' && isset($mealTypes[$mealFallbackIndex])) $type = $mealTypes[$mealFallbackIndex];
          $mealFallbackIndex++;
          if (!in_array($type,$mealTypes,true)) continue;
          $mealSavedTotals[$type] += (float)(isset($it['sum']) ? $it['sum'] : 0);

          // ???/?癲ル슢????(??????ъ군??袁⑥º?
          if (isset($etc['unit_per_pax']) && is_numeric($etc['unit_per_pax']) && $mealUnits[$type]<=0)
            $mealUnits[$type] = (float)$etc['unit_per_pax'];
          if (isset($etc['pax']) && is_numeric($etc['pax']) && $mealPax[$type]<=0)
            $mealPax[$type] = (int)$etc['pax'];

          // ????◈????????낅쵂
          if (!empty($etc['dates']) && is_array($etc['dates'])) {
            if (is_assoc_array($etc['dates'])) {
              foreach ($etc['dates'] as $d=>$cnt) if (isset($mealMatrix[$type][$d])) $mealMatrix[$type][$d] += (int)$cnt;
            } else {
              foreach ($etc['dates'] as $d) if (isset($mealMatrix[$type][$d])) $mealMatrix[$type][$d] += 1;
            }
          }
        }

        /* 3) ???猷명룏????影??낟??*/
        $meal_total = 0.0;
        $rowTotals  = array(); $perPersonTotal=0; $paxDisplay=0;
        foreach ($mealTypes as $mt) {
          $rowTotals[$mt] = ($mealSavedTotals[$mt] != 0.0)
            ? $mealSavedTotals[$mt]
            : ((float)$mealUnits[$mt]) * ((int)$mealPax[$mt]);
          $meal_total += $rowTotals[$mt];
          $perPersonTotal += (float)$mealUnits[$mt];
          if ($paxDisplay===0 && $mealPax[$mt]>0) $paxDisplay = (int)$mealPax[$mt];
        }
        $section_totals['MEAL'] = $meal_total;
      ?>
      <div class="section-header">2) MEAL</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="10%">구분</th>
            <?php foreach ($dates as $d): ?><th><?= htmlspecialchars($d,ENT_QUOTES,'UTF-8') ?></th><?php endforeach; ?>
            <th>1인당 합계</th><th>인원</th><th>합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($mealTypes as $mt): ?>
            <tr>
              <td><?= $mt ?></td>
              <?php foreach ($dates as $d): ?>
                <td><input type="text" value="<?= (int)$mealMatrix[$mt][$d] ?>" style="width:50px;text-align:center" readonly></td>
              <?php endforeach; ?>
              <td class="text-right currency"><?= $mealUnits[$mt] ?></td>
              <td><?= (int)$mealPax[$mt] ?></td>
              <td class="text-right currency"><?= money($rowTotals[$mt]) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td class="text-right" colspan="<?= 1+count($dates) ?>">MEAL 소계</td>
            <td class="text-right currency"><?= money($perPersonTotal) ?></td>
            <td><?= (int)$paxDisplay ?></td>
            <td class="text-right currency"><?= money($meal_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php endif; ?>

      <!-- ========== 3) TRANSPORTATION (??濚밸Ŧ?긷칰???꿔꺂?볟젆怨곷븶????얜Ŋ??| PHP 5.6 ?癲ル슢?뤸뤃?? ========== -->
      <?php
      if (!empty($sections['TRANSPORT'])):

        /* ????◈? ????諛몄? */
        $dates = array();
        $sd = isset($master['start_date']) ? trim($master['start_date']) : '';
        $ed = isset($master['end_date'])   ? trim($master['end_date'])   : '';
        if ($sd !== '' && $ed !== '' && strtotime($sd)!==false && strtotime($ed)!==false) {
          $cur=strtotime($sd); $end=strtotime($ed);
          while ($cur <= $end) { $dates[] = date('Y-m-d',$cur); $cur = strtotime('+1 day',$cur); }
        } else {
          $dateSet = array();
          foreach ($sections['TRANSPORT'] as $it){
            $etc = json_decode(isset($it['etc_json']) ? $it['etc_json'] : '{}', true);
            if (!is_array($etc)) $etc = array();
            if (!empty($etc['dates']) && is_array($etc['dates'])) {
              if (array_keys($etc['dates']) !== range(0, count($etc['dates'])-1)) {
                foreach ($etc['dates'] as $d=>$v) $dateSet[$d]=true;
              } else {
                foreach ($etc['dates'] as $d) $dateSet[$d]=true;
              }
            }
          }
          $dates = array_keys($dateSet); sort($dates);
        }
        if (empty($dates)) { $base=time(); for($i=0;$i<3;$i++) $dates[] = date('Y-m-d', strtotime('+'.$i.' day',$base)); }

        /* ?꿔꺂?볟젆怨곷븶???筌먐삳１??꿔꺂??????⑸걦????*/
        $rows = array(); // $rows[label] = ['unit'=>0.0,'vehicles'=>0.0,'sum'=>0.0,'matrix'=>[date=>0]]
        foreach ($sections['TRANSPORT'] as $it) {
          $label = trim((string)(isset($it['label']) ? $it['label'] : 'OVERTIME'));
          if ($label==='') $label = 'OVERTIME';

          $etc = json_decode(isset($it['etc_json']) ? $it['etc_json'] : '{}', true);
          if (!is_array($etc)) $etc = array();

          $unitUSD  = (isset($it['unit']) && is_numeric($it['unit'])) ? (float)$it['unit'] : 0.0;
          $vehicles = 0.0;
          if (isset($etc['unit_per_car']) && is_numeric($etc['unit_per_car'])) $vehicles = (float)$etc['unit_per_car'];
          elseif (isset($it['cnt']) && is_numeric($it['cnt']))                 $vehicles = (float)$it['cnt'];

          if (!isset($rows[$label])) {
            $rows[$label] = array('unit'=>0.0,'vehicles'=>0.0,'sum'=>0.0,'matrix'=>array());
            foreach ($dates as $d) $rows[$label]['matrix'][$d]=0;
          }

          if ($unitUSD  > 0) $rows[$label]['unit']     = $unitUSD;
          if ($vehicles > 0) $rows[$label]['vehicles'] = $vehicles;
          $rows[$label]['sum'] += (float)(isset($it['sum']) ? $it['sum'] : 0);

          if (!empty($etc['dates']) && is_array($etc['dates'])) {
            if (array_keys($etc['dates']) !== range(0, count($etc['dates'])-1)) {
              foreach ($etc['dates'] as $d=>$cnt) {
                if (isset($rows[$label]['matrix'][$d])) $rows[$label]['matrix'][$d] += (int)$cnt;
              }
            } else {
              foreach ($etc['dates'] as $d) {
                if (isset($rows[$label]['matrix'][$d])) $rows[$label]['matrix'][$d] += 1;
              }
            }
          }
        }

        /* ???猷명룏??*/
        $transport_total = 0.0; $rowTotals=array();
        foreach ($rows as $name=>$r) {
          $occ = 0; foreach ($dates as $d) $occ += (int)$r['matrix'][$d];
          $rowTotals[$name] = ((float)$r['sum'] != 0.0) ? (float)$r['sum'] : ((float)$r['vehicles']) * $occ;
          $transport_total += $rowTotals[$name];
        }
        $section_totals['TRANSPORT'] = $transport_total;
      ?>
      <div class="section-header">3) TRANSPORTATION</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="12%">차량/항목</th>
            <?php foreach ($dates as $d): ?><th><?= htmlspecialchars($d,ENT_QUOTES,'UTF-8') ?></th><?php endforeach; ?>
            <th width="8%">차량수</th>
            <th width="12%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $name=>$r): ?>
            <tr>
              <td class="text-left"><?= htmlspecialchars($name,ENT_QUOTES,'UTF-8') ?></td>
              <?php foreach ($dates as $d): ?>
                <td><input type="text" value="<?= (int)$r['matrix'][$d] ?>" style="width:50px;text-align:center" readonly></td>
              <?php endforeach; ?>
              <td><?= number_format($r['vehicles'], 2) ?></td>
              <td class="text-right currency"><?= money($rowTotals[$name]) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td class="text-right" colspan="<?= 1+count($dates) ?>">TRANSPORTATION 소계</td>
            <td><!-- ?꿔꺂?볟젆怨곷븶?????????--></td>
            <td class="text-right currency"><?= money($transport_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php endif; ?>

<?php
if (true):

  // 1) ????◈? ????諛몄?
  $ot_dates = array();
  $sd = isset($master['start_date']) ? trim($master['start_date']) : '';
  $ed = isset($master['end_date'])   ? trim($master['end_date'])   : '';
  if ($sd !== '' && $ed !== '' && strtotime($sd)!==false && strtotime($ed)!==false) {
    $cur=strtotime($sd); $end=strtotime($ed);
    while ($cur <= $end) { $ot_dates[] = date('Y-m-d',$cur); $cur = strtotime('+1 day',$cur); }
  } else {
    $dateSet = array();
    foreach ((isset($sections['OVERTIME']) ? $sections['OVERTIME'] : array()) as $it){
      $etc = json_decode(isset($it['etc_json']) ? $it['etc_json'] : '{}', true);
      if (!is_array($etc)) $etc = array();
      if (!empty($etc['dates']) && is_array($etc['dates'])) {
        $isAssoc = array_keys($etc['dates']) !== range(0, count($etc['dates'])-1);
        if ($isAssoc) { foreach ($etc['dates'] as $d=>$v) $dateSet[$d]=true; }
        else          { foreach ($etc['dates'] as $d)     $dateSet[$d]=true; }
      }
    }
    $ot_dates = array_keys($dateSet); sort($ot_dates);
  }
  if (empty($ot_dates)) { $base=time(); for($i=0;$i<3;$i++) $ot_dates[] = date('Y-m-d', strtotime('+'.$i.' day',$base)); }

  // 2) ?????꿔꺂??????⑸걦????
  $ot_rows = array();
  foreach ((isset($sections['OVERTIME']) ? $sections['OVERTIME'] : array()) as $it) {
    $label = trim((string)(isset($it['label']) ? $it['label'] : 'OVERTIME'));
    if ($label==='') $label = 'OVERTIME';

    $etc = json_decode(isset($it['etc_json']) ? $it['etc_json'] : '{}', true);
    if (!is_array($etc)) $etc = array();

    $targets = 0.0;
    if (isset($etc['unit_per_target']) && is_numeric($etc['unit_per_target'])) $targets = (float)$etc['unit_per_target'];
    elseif (isset($it['cnt']) && is_numeric($it['cnt']))                        $targets = (float)$it['cnt'];

    if (!isset($ot_rows[$label])) {
      $ot_rows[$label] = array('targets'=>0.0,'sum'=>0.0,'matrix'=>array(),'reasons'=>array());
      foreach ($ot_dates as $d) { $ot_rows[$label]['matrix'][$d]=0; $ot_rows[$label]['reasons'][$d]=''; }
    }
    if ($targets > 0) $ot_rows[$label]['targets'] = $targets;
    $ot_rows[$label]['sum'] += (float)(isset($it['sum']) ? $it['sum'] : 0);

    if (!empty($etc['dates']) && is_array($etc['dates'])) {
      $isAssoc = array_keys($etc['dates']) !== range(0, count($etc['dates'])-1);
      if ($isAssoc) {
        foreach ($etc['dates'] as $d=>$cnt) {
          if (isset($ot_rows[$label]['matrix'][$d])) $ot_rows[$label]['matrix'][$d] += (int)$cnt;
        }
      } else {
        foreach ($etc['dates'] as $d) {
          if (isset($ot_rows[$label]['matrix'][$d])) $ot_rows[$label]['matrix'][$d] += 1;
        }
      }
    }
    if (!empty($etc['reasons']) && is_array($etc['reasons'])) {
      foreach ($etc['reasons'] as $d=>$reason) {
        if (isset($ot_rows[$label]['reasons'][$d])) $ot_rows[$label]['reasons'][$d] = (string)$reason;
      }
    }
  }

  if (empty($ot_rows)) {
    $ot_rows['OVERTIME'] = array('targets'=>0.0,'sum'=>0.0,'matrix'=>array(),'reasons'=>array());
    foreach ($ot_dates as $d) {
      $ot_rows['OVERTIME']['matrix'][$d] = 0;
      $ot_rows['OVERTIME']['reasons'][$d] = '';
    }
  }

  $overtime_total = 0.0; $ot_rowTotals = array();
  foreach ($ot_rows as $name=>$r) {
    $occ = 0; foreach ($ot_dates as $d) { $occ += (int)$r['matrix'][$d]; }
    $ot_rowTotals[$name] = ((float)$r['sum'] != 0.0) ? (float)$r['sum'] : ((float)$r['targets']) * $occ;
    $overtime_total += $ot_rowTotals[$name];
  }
  $section_totals['OVERTIME'] = $overtime_total;
?>
<div class="section-header">6) OVERTIME</div>
<table class="data-table">
  <thead>
    <tr>
      <th width="12%">항목</th>
      <?php foreach ($ot_dates as $d): ?><th><?= htmlspecialchars($d,ENT_QUOTES,'UTF-8') ?></th><?php endforeach; ?>
      <th width="10%">건수</th>
      <th width="12%">합계</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($ot_rows as $name=>$r): ?>
      <tr>
        <td class="text-left"><?= htmlspecialchars($name,ENT_QUOTES,'UTF-8') ?></td>
        <?php foreach ($ot_dates as $d): ?>
          <td>
            <input type="text" value="<?= (int)$r['matrix'][$d] ?>" style="width:50px;text-align:center" readonly>
            <?php if (!empty($r['reasons'][$d])): ?>
              <div style="font-size:12px;color:#667085"><?= htmlspecialchars($r['reasons'][$d],ENT_QUOTES,'UTF-8') ?></div>
            <?php endif; ?>
          </td>
        <?php endforeach; ?>
        <td><?= number_format($r['targets'], 2) ?></td>
        <td class="text-right currency"><?= money($ot_rowTotals[$name]) ?></td>
      </tr>
    <?php endforeach; ?>
    <tr class="total-row">
      <td class="text-right" colspan="<?= 1 + count($ot_dates) ?>">OVERTIME 소계</td>
      <td><!-- ?꿸쑨??????????--></td>
      <td class="text-right currency"><?= money($overtime_total) ?></td>
    </tr>
  </tbody>
</table>
<?php endif; ?>

      <!-- ========== 4) TICKET ========== -->
      <?php if (!empty($sections['TICKET'])):
          $tk_total = 0;
      ?>
      <div class="section-header">4) TICKET</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="40%">티켓명</th>
            <th width="20%">단가</th>
            <th width="20%">매수/인원</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['TICKET'] as $it):
              $tk_total += (float)(isset($it['sum']) ? $it['sum'] : 0);
          ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars(isset($it['label']) ? $it['label'] : '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-right currency"><?= money(isset($it['unit']) ? $it['unit'] : 0) ?></td>
            <td><?= isset($it['qty']) ? $it['qty'] : 0 ?></td>
            <td class="text-right currency"><?= money(isset($it['sum']) ? $it['sum'] : 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">TICKET 소계</td>
            <td class="text-right currency"><?= money($tk_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['TICKET'] = $tk_total; endif; ?>

      <!-- ========== 5) GUIDE ========== -->
      <?php if (!empty($sections['GUIDE'])):
          $gd_total = 0;
      ?>
      <div class="section-header">5) GUIDE</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="40%">가이드/설명</th>
            <th width="20%">일당/단가</th>
            <th width="20%">일수</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['GUIDE'] as $it):
              $gd_total += (float)(isset($it['sum']) ? $it['sum'] : 0);
          ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars(isset($it['label']) ? $it['label'] : '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-right currency"><?= money(isset($it['unit']) ? $it['unit'] : 0) ?></td>
            <td><?= isset($it['qty']) ? $it['qty'] : 0 ?></td>
            <td class="text-right currency"><?= money(isset($it['sum']) ? $it['sum'] : 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">GUIDE 소계</td>
            <td class="text-right currency"><?= money($gd_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['GUIDE'] = $gd_total; endif; ?>

      <!-- ========== 6) ETC ========== -->
      <?php if (!empty($sections['ETC'])):
          $etc_total = 0;
      ?>
      <div class="section-header">7) ETC</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="50%">항목</th>
            <th width="15%">단가</th>
            <th width="15%">수량</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['ETC'] as $it):
              $etc_total += (float)(isset($it['sum']) ? $it['sum'] : 0);
          ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars(isset($it['label']) ? $it['label'] : '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-right currency"><?= money(isset($it['unit']) ? $it['unit'] : 0) ?></td>
            <td><?= isset($it['qty']) ? $it['qty'] : 0 ?></td>
            <td class="text-right currency"><?= money(isset($it['sum']) ? $it['sum'] : 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">ETC 소계</td>
            <td class="text-right currency"><?= money($etc_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['ETC'] = $etc_total; endif; ?>

      <!-- ========== 7) TIP ========== -->
      <?php if (!empty($sections['TIP'])):
          $tip_total = 0;
      ?>
      <div class="section-header">8) TIP</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="50%">항목</th>
            <th width="15%">단가</th>
            <th width="15%">수량/인원</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['TIP'] as $it):
              $tip_total += (float)(isset($it['sum']) ? $it['sum'] : 0);
          ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars(isset($it['label']) ? $it['label'] : '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-right currency"><?= money(isset($it['unit']) ? $it['unit'] : 0) ?></td>
            <td><?= isset($it['cnt']) ? $it['cnt'] : 0 ?></td>
            <td class="text-right currency"><?= money(isset($it['sum']) ? $it['sum'] : 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">TIP 소계</td>
            <td class="text-right currency"><?= money($tip_total) ?></td>
          </tr>
        </tbody>
      </table>
      <?php $section_totals['TIP'] = $tip_total; endif; ?>

      <!-- ========== 8) PROFIT ========== -->
      <?php
        $profit_total_items = 0.0;
        $master_profit      = (float)(isset($master['profit'])      ? $master['profit']      : 0);
        $master_profit_memo = trim((string)(isset($master['profit_memo']) ? $master['profit_memo'] : ''));

        if (!empty($sections['PROFIT'])):
          // items??PROFIT?????繹먮겧嫄х솾?items ???뚯???????Β????嶺?筌?(master.profit?? ????怨뺣윞 ??醫딆┫????れ뫒??????嶺뚮ㅎ???
          foreach ($sections['PROFIT'] as $it) $profit_total_items += (float)(isset($it['sum']) ? $it['sum'] : 0);
      ?>
      <div class="section-header">9) PROFIT</div>
      <table class="data-table">
        <thead>
          <tr>
            <th width="55%">항목</th>
            <th width="15%">단가</th>
            <th width="10%">수량</th>
            <th width="20%">합계</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sections['PROFIT'] as $it): ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars(isset($it['label']) ? $it['label'] : '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-right currency"><?= money(isset($it['unit']) ? $it['unit'] : 0) ?></td>
            <td><?= isset($it['qty']) ? $it['qty'] : 0 ?></td>
            <td class="text-right currency"><?= money(isset($it['sum']) ? $it['sum'] : 0) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="3" class="text-right">PROFIT 소계</td>
            <td class="text-right currency"><?= money($profit_total_items) ?></td>
          </tr>
        </tbody>
      </table>
      <?php
        $section_totals['PROFIT'] = $profit_total_items;
        elseif ($master_profit > 0 || $master_profit_memo !== ''):
        // items??醫딆쓧? ????ㅼ굡???????master.profit????Β????嶺?筌?
      ?>
      <div class="section-header">9) PROFIT</div>
      <table class="data-table">
        <tbody>
          <tr class="total-row">
            <td class="text-left" style="width:80%;"><?= htmlspecialchars($master_profit_memo !== '' ? $master_profit_memo : 'PROFIT', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-right currency" style="width:20%;"><?= money($master_profit) ?></td>
          </tr>
        </tbody>
      </table>
      <?php
        $section_totals['PROFIT'] = $master_profit;
        endif;
      ?>

      <!-- ========== ???됰Ŋ????꿔꺂????쭍?瑜귣젺????猷명룏??========== -->
      <?php
        $auto_grand = 0.0;
        foreach ($section_totals as $v) $auto_grand += (float)$v;

        $grand_total = (isset($master['grand_total']) && is_numeric($master['grand_total'])) ? (float)$master['grand_total'] : 0.0;
        $per_pax     = (isset($master['per_pax']) && is_numeric($master['per_pax'])) ? (float)$master['per_pax'] : 0.0;

        $per_pax_divisor = estimate_per_pax_divisor($master);
        if ($per_pax <= 0 && $grand_total > 0 && $per_pax_divisor > 0) {
          $per_pax = $grand_total / $per_pax_divisor;
        } elseif ($per_pax <= 0 && $grand_total <= 0 && $auto_grand > 0 && $per_pax_divisor > 0) {
          $per_pax = $auto_grand / $per_pax_divisor;
        }
      ?>

      <div class="summary-section">
        <div class="summary-pills">
          <span class="pill">HOTEL: <?= money(isset($section_totals['HOTEL']) ? $section_totals['HOTEL'] : 0) ?></span>
          <span class="pill">MEAL: <?= money(isset($section_totals['MEAL']) ? $section_totals['MEAL'] : 0) ?></span>
          <span class="pill">TRANSPORT: <?= money(isset($section_totals['TRANSPORT']) ? $section_totals['TRANSPORT'] : 0) ?></span>
          <span class="pill">OVERTIME: <?= money(isset($section_totals['OVERTIME']) ? $section_totals['OVERTIME'] : 0) ?></span>
          <span class="pill">TICKET: <?= money(isset($section_totals['TICKET']) ? $section_totals['TICKET'] : 0) ?></span>
          <span class="pill">GUIDE: <?= money(isset($section_totals['GUIDE']) ? $section_totals['GUIDE'] : 0) ?></span>
          <span class="pill">ETC: <?= money(isset($section_totals['ETC']) ? $section_totals['ETC'] : 0) ?></span>
          <span class="pill">TIP: <?= money(isset($section_totals['TIP']) ? $section_totals['TIP'] : 0) ?></span>
          <span class="pill">PROFIT: <?= money(isset($section_totals['PROFIT']) ? $section_totals['PROFIT'] : 0) ?></span>
        </div>

        <div class="final-totals">
          <div class="final-total-item">
            <div class="final-total-label">10) TOTAL TOUR FEE</div>
            <div class="final-total-amount">
              <?= money($grand_total > 0 ? $grand_total : $auto_grand) ?>
            </div>
          </div>
          <div class="final-total-item">
            <div class="final-total-label">11) 1인당 요금</div>
            <div class="final-total-amount">
              <?= money($per_pax) ?>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /.breakdown-wrapper -->
  </div>
</div>

