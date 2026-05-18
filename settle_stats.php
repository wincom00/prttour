<?php
/* *********************************************************
   푸른투어 인트라넷 - 직원별 수금/정산 통계 대시보드 (별도 페이지)
   파일명 예: settle_stats.php
   - PURUN 버전 기준 (mysql_* 함수 사용)
   - header/권한/사이드 메뉴는 기존과 동일하게 include
********************************************************** */

include "include/header.php";

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
} else {
  echo "<meta http-equiv='refresh' content='0; url=./login.php'>"; exit;
}
/*
if (!hasMenuAccess($division, $pdx, $sub)) {
  $goUrl_1 = "index.php";
  Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
  echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>"; exit;
}
*/
/* ============ 유틸 ============ */
function _fetch_all($sql){
  global $dbConn;
  $res = mysql_query($sql, $dbConn);
  $rows = array();
  if ($res) while($r=mysql_fetch_assoc($res)) $rows[]=$r;
  return $rows;
}
function _one($sql){
  global $dbConn;
  $res = mysql_query($sql, $dbConn);
  if ($res && mysql_num_rows($res)) { $row = mysql_fetch_row($res); return $row[0]; }
  return 0;
}

/* ============ 필터 입력값 ============ */
$seldate       = isset($_POST['seldate']) ? $_POST['seldate'] : (isset($_GET['seldate']) ? $_GET['seldate'] : '');
$startDate     = isset($_POST['startDate']) ? $_POST['startDate'] : (isset($_GET['startDate']) ? $_GET['startDate'] : '');
$endDate       = isset($_POST['endDate']) ? $_POST['endDate'] : (isset($_GET['endDate']) ? $_GET['endDate'] : '');
$cname         = isset($_POST['cname']) ? $_POST['cname'] : (isset($_GET['cname']) ? $_GET['cname'] : '');
$employeeName  = isset($_POST['employeeName']) ? $_POST['employeeName'] : (isset($_GET['employeeName']) ? $_GET['employeeName'] : '');
$searchpay     = isset($_POST['searchpay']) ? $_POST['searchpay'] : (isset($_GET['searchpay']) ? $_GET['searchpay'] : '');

/* ============ WHERE 생성 (기존 화면 로직 최대 반영) ============ */
function STATS_build_where(&$groupDateField){
  global $cname,$seldate,$startDate,$endDate,$employeeName,$searchpay,$user_dbinfo;

  $w = array();
  $w[] = "a.reserveCode=b.reserveCode";
  $w[] = "b.pay_method <> 'init'";
  $w[] = "b.payment_status IN ('READY','DONE','PPAY','OPAY')";
  $w[] = "a.parent='MAIN'";
  $w[] = "a.p_code=d.p_code";

  if (!empty($cname)) {
    $c = mysql_real_escape_string($cname);
    $w[] = "(a.book_pri LIKE '%{$c}%')";
  }

  // 1=예약일, 2=결제일, 3=회계확인, 4=취소, 그 외=최근 14일 예약일
  if ($seldate == '1') {
    $groupDateField = "DATE(a.revDate)";
    if (!empty($startDate)) {
      $sd = mysql_real_escape_string($startDate);
      $ed = mysql_real_escape_string($endDate);
      $w[] = "(a.revDate >= '{$sd}' AND a.revDate <= '{$ed}')";
    }
  } else if ($seldate == '2') {
    $groupDateField = "DATE(b.wdate)";
    if (!empty($startDate)) {
      $sd = mysql_real_escape_string($startDate.' 00:00:00');
      $ed = mysql_real_escape_string($endDate.' 23:23:59');
      $w[] = "(b.wdate >= '{$sd}' AND b.wdate <= '{$ed}')";
    }
  } else if ($seldate == '3') {
    $groupDateField = "DATE(b.conf_date)";
    if (!empty($startDate)) {
      $sd = mysql_real_escape_string($startDate.' 00:00:00');
      $ed = mysql_real_escape_string($endDate.' 23:23:59');
      $w[] = "(b.conf_date >= '{$sd}' AND b.conf_date <= '{$ed}')";
    }
  } else if ($seldate == '4') {
    $groupDateField = "DATE(b.wdate)";
    if (!empty($startDate)) {
      $sd = mysql_real_escape_string($startDate.' 00:00:00');
      $ed = mysql_real_escape_string($endDate.' 23:23:59');
      $w[] = "(b.wdate >= '{$sd}' AND b.wdate <= '{$ed}')";
    } else {
      $sdate = date("Y-m-d")." 23:23:59";
      $edate = date("Y-m-d",strtotime("-30 day"))." 00:00:00";
      $w[] = "(a.revDate >= '{$edate}' AND a.revDate <= '{$sdate}')";
    }
    $w[] = "a.rev_status='CANCEL'";
  } else {
    $groupDateField = "DATE(a.revDate)";
    $sdate = date("Y-m-d")." 23:23:59";
    $edate = date("Y-m-d",strtotime("-14 day"))." 00:00:00";
    $w[]   = "a.revDate BETWEEN '{$edate}' AND '{$sdate}'";
  }

  if (!empty($searchpay)) {
    $sp = mysql_real_escape_string($searchpay);
    $w[] = "b.conf_p='{$sp}'";
  }

  if (!empty($employeeName)) {
    $ee = mysql_real_escape_string($employeeName);
    $w[] = "b.register='{$ee}'";
  }

  // 부서 접근 제한(현행 로직)
  global $user_dbinfo;
  if (($user_dbinfo['dept_prior'] == "J") || ($user_dbinfo['dept_prior'] == "")) {
    $area = mysql_real_escape_string($user_dbinfo['area_comp']);
    $w[]  = "d.m_dept LIKE '%{$area}%'";
  }

  return implode(' AND ', $w);
}

/* ============ 공통 변수/표현식 ============ */
$amtExpr = "CASE WHEN b.payment_status='RETURN' THEN -b.payment ELSE b.payment END";
$grpDate = '';
$where   = STATS_build_where($grpDate);

/* ============ 1) 기본 통계 ============ */
$sql1 = "
SELECT
  (SELECT COUNT(*) 
     FROM (SELECT a.reserveCode 
             FROM reserve_info a
             JOIN payment_history b ON a.reserveCode=b.reserveCode
             JOIN product_master d ON a.p_code=d.p_code
            WHERE {$where}
            GROUP BY a.reserveCode) t1) AS booking_cnt,
  (SELECT COALESCE(SUM(p_cnt),0)
     FROM (SELECT a.reserveCode, MAX(a.p_cnt) AS p_cnt
             FROM reserve_info a
             JOIN payment_history b ON a.reserveCode=b.reserveCode
             JOIN product_master d ON a.p_code=d.p_code
            WHERE {$where}
            GROUP BY a.reserveCode) t2) AS pax_sum,
  COALESCE(SUM({$amtExpr}),0) AS paid_sum
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where}";
$s1 = _fetch_all($sql1);
$s1 = $s1 ? $s1[0] : array('booking_cnt'=>0,'pax_sum'=>0,'paid_sum'=>0);
$avg_per_booking = ($s1['booking_cnt']>0)? $s1['paid_sum']/$s1['booking_cnt'] : 0;
$avg_per_pax     = ($s1['pax_sum']>0)?     $s1['paid_sum']/$s1['pax_sum']     : 0;

/* ============ 2) 결제자(직원=b.register)별 ============ */
$sql2 = "
SELECT b.register, COALESCE(ml.kor_name,b.register) AS staff_name,
       COUNT(DISTINCT a.reserveCode) AS cnt, COALESCE(SUM({$amtExpr}),0) AS sum_amt
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
LEFT JOIN member_list ml ON ml.userid=b.register
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where}
GROUP BY b.register, staff_name
ORDER BY sum_amt DESC
LIMIT 20";
$staffRows = _fetch_all($sql2);

/* ============ 3) 상품명별 TOP10 ============ */
$sql3 = "
SELECT a.p_name, COUNT(DISTINCT a.reserveCode) AS cnt,
       COALESCE(SUM({$amtExpr}),0) AS sum_amt
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where}
GROUP BY a.p_name
ORDER BY sum_amt DESC
LIMIT 10";
$prodRows = _fetch_all($sql3);

/* ============ 4) 결제수단별 비중 ============ */
$sql4 = "
SELECT b.pay_method, COUNT(*) AS rows_cnt, COALESCE(SUM({$amtExpr}),0) AS sum_amt
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where}
GROUP BY b.pay_method
ORDER BY sum_amt DESC";
$methodRows = _fetch_all($sql4);

/* ============ 5) 예약상태/정산상태 ============ */
$sql5a = "
SELECT a.rev_status, COUNT(DISTINCT a.reserveCode) AS cnt
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where}
GROUP BY a.rev_status";
$revStatusRows = _fetch_all($sql5a);

$sql5b = "
SELECT b.conf_p, COUNT(*) AS cnt
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where}
GROUP BY b.conf_p";
$confRows = _fetch_all($sql5b);

/* ============ 6) 날짜(선택 기준일)별 추이 ============ */
$sql6 = "
SELECT {$grpDate} AS gdate, COALESCE(SUM({$amtExpr}),0) AS sum_amt
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where}
GROUP BY {$grpDate}
ORDER BY gdate ASC";
$dateRows = _fetch_all($sql6);

/* ============ 7) 취소/환불 ============ */
$sql7a = "
SELECT COUNT(DISTINCT a.reserveCode)
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where} AND a.rev_status='CANCEL'";
$cancel_cnt = _one($sql7a);

$sql7b = "
SELECT COALESCE(SUM(b.payment),0)
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where} AND b.payment_status='RETURN'";
$refund_sum = _one($sql7b) * 1;

/* ============ 8) 요일/월 패턴 ============ */
$sql8a = "
SELECT DAYOFWEEK({$grpDate}) AS dw, COALESCE(SUM({$amtExpr}),0) AS sum_amt
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where}
GROUP BY DAYOFWEEK({$grpDate})
ORDER BY dw ASC";
$weekdayRows = _fetch_all($sql8a);

$sql8b = "
SELECT DATE_FORMAT({$grpDate},'%Y-%m') AS ym, COALESCE(SUM({$amtExpr}),0) AS sum_amt
FROM reserve_info a
JOIN payment_history b ON a.reserveCode=b.reserveCode
JOIN product_master d ON a.p_code=d.p_code
WHERE {$where}
GROUP BY DATE_FORMAT({$grpDate},'%Y-%m')
ORDER BY ym ASC";
$monthRows = _fetch_all($sql8b);

/* ============ JS 데이터 직렬화 ============ */
function _arr($rows,$k){ $o=array(); foreach($rows as $r){ $o[]=$r[$k]; } return $o; }
$staff_labels  = json_encode(_arr($staffRows,'staff_name'));
$staff_values  = json_encode(_arr($staffRows,'sum_amt'));
$prod_labels   = json_encode(_arr($prodRows,'p_name'));
$prod_values   = json_encode(_arr($prodRows,'sum_amt'));
$method_labels = json_encode(_arr($methodRows,'pay_method'));
$method_values = json_encode(_arr($methodRows,'sum_amt'));
$date_labels   = json_encode(_arr($dateRows,'gdate'));
$date_values   = json_encode(_arr($dateRows,'sum_amt'));

$weekday_map = array(1=>'일',2=>'월',3=>'화',4=>'수',5=>'목',6=>'금',7=>'토');
$weekday_labels_php = array(); $weekday_values_php = array();
foreach($weekdayRows as $r){ $weekday_labels_php[]=$weekday_map[intval($r['dw'])]; $weekday_values_php[]=(float)$r['sum_amt']; }
$weekday_labels = json_encode($weekday_labels_php);
$weekday_values = json_encode($weekday_values_php);
$month_labels   = json_encode(_arr($monthRows,'ym'));
$month_values   = json_encode(_arr($monthRows,'sum_amt'));
?>
<!----- 화면 시작 ----->
<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">직원별수금정산</a></li>
        <li>정산 통계 대시보드</li>
      </ul>
    </div>

    <!-- 검색/필터 -->
    <form action="" method="post" name="frmStats">
      <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
        <tbody>
          <tr>
            <td class="text-center formHeader" style="width:120px">
              <select class="form-control" name="seldate">
                <option value="">- 선택 -</option>
                <option value="1" <?php if($seldate=='1') echo 'selected'; ?>>예약일</option>
                <option value="2" <?php if($seldate=='2') echo 'selected'; ?>>결제일</option>
                <option value="3" <?php if($seldate=='3') echo 'selected'; ?>>회계확인</option>
                <option value="4" <?php if($seldate=='4') echo 'selected'; ?>>취소</option>
              </select>
            </td>
            <td colspan="4">
              <div class="row">
                <div class="col-sm-5">
                  <div class="input-group input-group-sm">
                    <input type="text" name="startDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate1" placeholder="시작일" autocomplete='off' value="<?=htmlspecialchars($startDate)?>">
                    <span class="input-group-btn"><button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar"></span></button></span>
                  </div>
                </div>
                <div class="col-sm-5">
                  <div class="input-group input-group-sm">
                    <input type="text" name="endDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate2" placeholder="종료일" autocomplete='off' value="<?=htmlspecialchars($endDate)?>">
                    <span class="input-group-btn"><button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar"></span></button></span>
                  </div>
                </div>
              </div>
            </td>
            <td class="text-center formHeader" style="width:140px">
              <input type="text" id="cname" name="cname" placeholder="고객명" class="inpubase md" value="<?=htmlspecialchars($cname)?>"/>
            </td>
            <td class="text-center formHeader" style="width:170px">
              <select class="form-control" name="employeeName">
                <option value="">- 결제자 -</option>
                <?=employeelist($employeeName)?>
              </select>
            </td>
            <td class="text-center formHeader" style="width:170px">
              <select class="form-control" name="searchpay">
                <option value="">정산상태</option>
                <option value="1" <?php if($searchpay=='1') echo 'selected'; ?>>회계확인</option>
                <option value="2" <?php if($searchpay=='2') echo 'selected'; ?>>회계확인완료</option>
              </select>
            </td>
            <td class="text-center" style="width:120px">
              <button type='submit' class="btn btn-primary btn-sm btn1">검색</button>
            </td>
          </tr>
        </tbody>
      </table>
    </form>

    <!-- 통계 카드 -->
    <style>
      .stats-cards{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));grid-gap:12px;margin-bottom:16px}
      .stats-card{border:1px solid #eee;border-radius:6px;padding:12px;background:#fff}
      .stats-card .tit{font-size:12px;color:#666;margin-bottom:6px}
      .stats-card .val{font-size:18px;font-weight:700}
      @media (max-width: 991px){.stats-cards{grid-template-columns:repeat(2,1fr)}}
      h4{margin-top:16px;margin-bottom:8px}
    </style>
    <div class="stats-cards">
      <div class="stats-card"><div class="tit">총 예약건수</div><div class="val"><?=number_format($s1['booking_cnt'])?> 건</div></div>
      <div class="stats-card"><div class="tit">총 인원</div><div class="val"><?=number_format($s1['pax_sum'])?> 명</div></div>
      <div class="stats-card"><div class="tit">총 결제액(순액)</div><div class="val">$<?=number_format($s1['paid_sum'],2)?></div></div>
      <div class="stats-card"><div class="tit">평균(건당 / 인원당)</div><div class="val">$<?=number_format($avg_per_booking,2)?> / $<?=number_format($avg_per_pax,2)?></div></div>
    </div>

    <!-- 2/3 -->
    <div class="row">
      <div class="col-sm-6"><h4>② 결제자별 매출 TOP</h4><canvas id="chartStaff"></canvas></div>
      <div class="col-sm-6"><h4>③ 상품별 매출 TOP10</h4><canvas id="chartProd"></canvas></div>
    </div>

    <!-- 4/5 -->
    <div class="row">
      <div class="col-sm-6"><h4>④ 결제수단별 비중</h4><canvas id="chartMethod"></canvas></div>
      <div class="col-sm-6">
        <h4>⑤ 상태 통계</h4>
        <table class="table table-bordered table-condensed">
          <thead><tr><th>예약상태</th><th class="text-right">건수</th></tr></thead>
          <tbody>
            <?php foreach($revStatusRows as $r){ ?>
            <tr><td><?=$r['rev_status']?></td><td class="text-right"><?=number_format($r['cnt'])?></td></tr>
            <?php } ?>
          </tbody>
        </table>
        <table class="table table-bordered table-condensed">
          <thead><tr><th>정산상태(conf_p)</th><th class="text-right">건수</th></tr></thead>
          <tbody>
            <?php foreach($confRows as $r){ ?>
            <tr><td><?=$r['conf_p']==''?'(미지정)':$r['conf_p']?></td><td class="text-right"><?=number_format($r['cnt'])?></td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 6/7/8 -->
    <div class="row">
      <div class="col-sm-8"><h4>⑥ 일자별 매출 추이 (기준: <?=$grpDate?>)</h4><canvas id="chartDaily"></canvas></div>
      <div class="col-sm-4">
        <h4>⑦ 취소/환불</h4>
        <table class="table table-bordered table-condensed">
          <tbody>
            <tr><th>취소 예약건수</th><td class="text-right"><?=number_format($cancel_cnt)?> 건</td></tr>
            <tr><th>환불 총액</th><td class="text-right">$<?=number_format($refund_sum,2)?></td></tr>
          </tbody>
        </table>
        <h4>⑧ 요일별 패턴</h4><canvas id="chartWeek"></canvas>
        <h4 style="margin-top:12px">⑧ 월별 패턴</h4><canvas id="chartMonth"></canvas>
      </div>
    </div>
  </div>
</div>

<?php include "include/side_m.php"; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
  // 날짜 입력기
  $('.tourDate1').datepicker({ format:'yyyy-mm-dd', autoclose:true });
  $('.tourDate2').datepicker({ format:'yyyy-mm-dd', autoclose:true });

  function money(v){ try{ return '$' + Number(v).toLocaleString(undefined,{maximumFractionDigits:2}); }catch(e){ return v; } }

  // PHP → JS 데이터
  const staffLabels  = <?=$staff_labels?>,  staffValues  = <?=$staff_values?>;
  const prodLabels   = <?=$prod_labels?>,   prodValues   = <?=$prod_values?>;
  const methodLabels = <?=$method_labels?>, methodValues = <?=$method_values?>;
  const dateLabels   = <?=$date_labels?>,   dateValues   = <?=$date_values?>;
  const weekLabels   = <?=$weekday_labels?>,weekValues   = <?=$weekday_values?>;
  const monthLabels  = <?=$month_labels?>,  monthValues  = <?=$month_values?>;

  // ② 직원별
  new Chart(document.getElementById('chartStaff'), {
    type:'bar',
    data:{ labels: staffLabels, datasets:[{ label:'매출', data: staffValues }] },
    options:{ plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label:(ctx)=> money(ctx.parsed.y) } } }, scales:{ y:{ beginAtZero:true } } }
  });

  // ③ 상품별
  new Chart(document.getElementById('chartProd'), {
    type:'bar',
    data:{ labels: prodLabels, datasets:[{ label:'매출', data: prodValues }] },
    options:{ indexAxis:'y', plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label:(c)=> money(c.parsed.x) } } }, scales:{ x:{ beginAtZero:true } } }
  });

  // ④ 결제수단
  new Chart(document.getElementById('chartMethod'), {
    type:'doughnut',
    data:{ labels: methodLabels, datasets:[{ data: methodValues }] },
    options:{ plugins:{ legend:{ position:'bottom' }, tooltip:{ callbacks:{ label:(c)=> c.label+': '+money(c.parsed) } } } }
  });

  // ⑥ 일자별
  new Chart(document.getElementById('chartDaily'), {
    type:'line',
    data:{ labels: dateLabels, datasets:[{ data: dateValues, tension:0.2, fill:false }] },
    options:{ plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label:(c)=> money(c.parsed.y) } } }, scales:{ y:{ beginAtZero:true } } }
  });

  // ⑧ 요일별
  new Chart(document.getElementById('chartWeek'), {
    type:'bar',
    data:{ labels: weekLabels, datasets:[{ data: weekValues }] },
    options:{ plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label:(c)=> money(c.parsed.y) } } }, scales:{ y:{ beginAtZero:true } } }
  });

  // ⑧ 월별
  new Chart(document.getElementById('chartMonth'), {
    type:'line',
    data:{ labels: monthLabels, datasets:[{ data: monthValues, tension:0.2 }] },
    options:{ plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label:(c)=> money(c.parsed.y) } } }, scales:{ y:{ beginAtZero:true } } }
  });
})();
</script>
</body>
</html>
