<?php
/**
 * 전체 스케줄 테이블 (PHP 5.5 튜닝 버전)
 * - DB: mysql_* 그대로 유지 (PHP 5.5 호환)
 * - 날짜 생성 SQL(거대한 number table cross join) 제거 → PHP DateTime 계산으로 대체 (쿼리 1개 삭제)
 * - 일자별/상품별 인원/객실 수 집계 1회 선조회 → 셀마다 반복쿼리 제거 (대폭 감소)
 * - 미정의 변수/상수 사용 최소화, 배열 키 전부 '문자열'로 명시
 * - $_SERVER['PHP_SELF'] 사용, 입력값 기본 검증 추가
 * - 성능 팁: 필요한 인덱스 DDL 주석으로 안내
 */

@ini_set('display_errors', '0');
@error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

include 'include/header.php';
// include 'include/inc_base.php'; // DB 커넥션/상수 선언부(환경에 맞게 유지)

if (empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

// --- 유틸: 날짜 포맷 검증 ---
function _is_ymd($s) {
    if (!is_string($s)) return false;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return false;
    $p = explode('-', $s);
    return checkdate((int)$p[1], (int)$p[2], (int)$p[0]);
}

// --- 입력값 수집 (POST 우선, 구버전 호환을 위해 기존 변수 있으면 fallback) ---
$StartYMD  = isset($_POST['StartYMD']) ? trim($_POST['StartYMD']) : (isset($StartYMD) ? trim($StartYMD) : '');
$EndYMD    = isset($_POST['EndYMD'])   ? trim($_POST['EndYMD'])   : (isset($EndYMD)   ? trim($EndYMD)   : '');
$startyear = isset($_POST['startyear'])? trim($_POST['startyear']): (isset($startyear)? trim($startyear): '');
$deptarea  = isset($_POST['deptarea']) ? trim($_POST['deptarea']) : (isset($deptarea) ? trim($deptarea) : '');

// --- 기본 날짜 범위(이번 달 1일 ~ 말일) ---
if ($startyear === '') {
    $startyear = date('Y');
}

if (!_is_ymd($StartYMD) || !_is_ymd($EndYMD)) {
    $y = date('Y');
    $m = date('m');
    $StartYMD = date('Y-m-01', strtotime($y.'-'.$m.'-01'));
    $EndYMD   = date('Y-m-t',  strtotime($y.'-'.$m.'-01'));
}

// --- 날짜 목록 생성 (SQL 없이 PHP로) ---
$startDT   = new DateTime($StartYMD);
$endDT     = new DateTime($EndYMD);
$endPlus1  = clone $endDT; $endPlus1->modify('+1 day'); // inclusive
$interval  = $startDT->diff($endPlus1);
$totalDay  = (int)$interval->days; // inclusive 개수

// 안전장치
if ($totalDay < 1) {
    $totalDay = 1;
}

// 날짜 리스트 배열(문자열 'Y-m-d')
$dateList = array();
$cursor   = clone $startDT;
for ($i=0; $i<$totalDay; $i++) {
    $dateList[] = $cursor->format('Y-m-d');
    $cursor->modify('+1 day');
}

// --- 지역 필터 쿼리 구성 ---
// 주의: $user_dbinfo['sc_grp'] 는 include 환경에 따라 존재. 기존 로직 유지.
if ($deptarea === '1') {
    $deptqry1 = '';
} else if ($deptarea === '') {
    $deptqry1 = " && ((b.sc_grp='" . mysql_real_escape_string($user_dbinfo['sc_grp']) . "'))";
} else {
    $deptqry1 = " && ((b.sc_grp='" . mysql_real_escape_string($deptarea) . "'))";
}

// --- 상품 목록(행) 조회: 기간 내 예약된 상품 + 총 인원(person)
$zip_qry1 = "SELECT 
                b.p_day, b.p_name, b.p_code, b.bgcolor,
                SUM(a.p_cnt) AS person
            FROM reserve_info a
            JOIN product_master b ON a.p_code = b.p_code
            WHERE a.rev_status IN ('DONE')
              AND b.p_type IN ('1','3','4')
              AND b.m_type IN ('S')
              AND a.stDate >= '".$StartYMD."'
              AND a.stDate <= '".$EndYMD."'
              $deptqry1
            GROUP BY a.p_code
            ORDER BY b.grp, b.p_day, b.p_name ASC";
$zip_rst1 = mysql_query($zip_qry1);

// --- 기간 내 일자/상품별 집계(선조회) : 인원합계, 객실합계 ---
// 인덱스 권장: ALTER TABLE reserve_info ADD INDEX idx_resv_pcode_date_status (p_code, stDate, rev_status);
$pre_qry = "SELECT p_code, stDate, SUM(p_cnt) AS total_pax, SUM(room_cnt) AS total_rooms
           FROM reserve_info
           WHERE rev_status IN ('DONE')
             AND stDate >= '".$StartYMD."'
             AND stDate <= '".$EndYMD."'
           GROUP BY p_code, stDate";
$pre_rst = mysql_query($pre_qry);

$matrix = array(); // $matrix[p_code][Y-m-d] = array('p'=>int, 'rooms'=>int)
while ($r = mysql_fetch_assoc($pre_rst)) {
    $pc = $r['p_code'];
    $dt = $r['stDate'];
    if (!isset($matrix[$pc])) $matrix[$pc] = array();
    $matrix[$pc][$dt] = array('p' => (float)$r['total_pax'], 'rooms' => (float)$r['total_rooms']);
}

// 오늘 색상 강조를 위해 오늘 날짜 준비
$todayYmd = date('Y-m-d');
$weekKR   = array('일','월','화','수','목','금','토');

?>
<link rel="stylesheet" type="text/css" href="lib/datatables.css"/>
<style>
.tableFixHead          { height: 600px; }
.tableFixHead thead th { top:0; background:#eee; border:0.05em solid #848484; }

table.dataTable thead tr th,
table.dataTable thead td,
table.dataTable tbody tr td {
  border-bottom:1px solid #111; padding:1px 1px;
}

div.dataTables_wrapper { margin:0 auto; }
</style>
<?php if (isset($mode) && $mode==='down') { ?>
  <?php
    header('Content-type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename=sc_'.date('Ymd').'.xls');
    header('Content-Description: PHP5 Generated Data');
    echo "<meta http-equiv='Content-Type' content='application/vnd.ms-excel; charset=utf-8'/>";
  ?>
<?php } ?>

<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">전체스케줄</a></li>
      </ul>
    </div>

    <div class="row">
      <div class="col-sm-12 col-md-12">
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" name="frmName" id="frmName" method="post">
          <input type="hidden" name="mode" value="">

          <table class="table table-bordered table-condensed">
            <tr>
              <td width="10%" class="titletd text-center">출발일</td>
              <td width="40%">
                <div class="row">
                  <div class="col-sm-12">
                    <div class="input-group input-group-sm">
                      <div class="row">
                        <div class="col-sm-6">
                          <input type="text" id="startDate1" name="StartYMD" class="inpubase tourDate1" placeholder="시작일" value="<?= htmlspecialchars($StartYMD, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" />
                        </div>
                        <div class="col-sm-6">
                          <input type="text" id="endDate" name="EndYMD" class="inpubase tourDate1" placeholder="마지막일" value="<?= htmlspecialchars($EndYMD, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </td>

              <td width="10%" class="titletd text-center">지역선택</td>
              <td width="40%" class="no-right-border">
                <select class="form-control" name="deptarea">
                  <option value="1"<?= ($deptarea1==='1' ? ' selected' : ''); ?>>- 지역그룹선택 -</option>
                  <?= printBaseCode_first('G03', $deptarea1); ?>
                </select>
              </td>
            </tr>
          </table>

          <table class="table table-bordered table-condensed">
            <tr>
              <td width="5%" class="text-center">검색년도</td>
              <td>
                <div class="row no-nav">
                  <div class="col-sm-2">
                    <input type="text" id="startyear" name="startyear" class="inpubase tourDate3" placeholder="년도" value="<?= htmlspecialchars($startyear, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" />
                  </div>
                  <div class="col-sm-6">
                    <ul class="pagination non-nav">
                      <?php for ($m=1; $m<=12; $m++): ?>
                        <li class="disabled"><span><a href="javascript:cal('<?= $m; ?>')"><?= $m; ?>월</a></span></li>
                      <?php endfor; ?>
                    </ul>
                  </div>
                  <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary btn-sm text-left btns1">검색</button>
                  </div>
                </div>
              </td>
            </tr>
          </table>
        </form>

        <br />
        <div class="row">
          <div class="col-sm-12 tableFixHead">
            <table width="100%" id="guide_table" class="stripe row-border order-column text-center">
              <thead>
                <tr>
                  <th style="position:sticky; margin:0; border:0.05em solid #848484;" align="center">상 품 명</th>
                  <?php foreach ($dateList as $d):
                        $m = date('m', strtotime($d));
                        $day = date('d', strtotime($d));
                        $w   = $weekKR[(int)date('w', strtotime($d))];
                        if ($w==='일')      $label = "<font style='font-size:7pt;color:red'>{$m}/{$day}<br>({$w})</font>";
                        else if ($w==='토') $label = "<font style='font-size:7pt;color:blue'>{$m}/{$day}<br>({$w})</font>";
                        else                $label = "<font style='font-size:7pt'>{$m}/{$day}<br>({$w})</font>";
                        $thExtra = ($d===$todayYmd) ? " style='margin:0;border:0.05em solid #848484;background-color:#DDA0DD;'" : " style='margin:0;border:0.05em solid #848484;'";
                  ?>
                    <th class="sticky-col"<?= $thExtra; ?> align="center"><?= $label; ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
              <?php while($zip_row1 = mysql_fetch_assoc($zip_rst1)): ?>
                <?php
                  $pcode     = $zip_row1['p_code'];
                  $pname     = $zip_row1['p_name'];
                  $pday      = $zip_row1['p_day'];
                  $bgcolor   = $zip_row1['bgcolor'];
                  $totalPers = (int)$zip_row1['person'];

                  $trip_day = ($pday==='1') ? '당일' : ($pday.' 일');

                  ob_start();
                  foreach ($dateList as $d) {
                      $pax   = isset($matrix[$pcode][$d]) ? (float)$matrix[$pcode][$d]['p']     : 0;
                      $rooms = isset($matrix[$pcode][$d]) ? (float)$matrix[$pcode][$d]['rooms'] : 0.0;

                      if ($d === $todayYmd)      $cellBg = '#DDA0DD';
                      else if ($pax > 0)         $cellBg = '#FFFF99';
                      else                       $cellBg = '#FFFFFF';

                      $memStr   = ($pax   > 0) ? (string)$pax            : '';
                      $roomStr  = ($rooms > 0) ? ('/'.(string)$rooms)    : '';

                      $titleTxt = '호텔 : '.$rooms;
                      $block    = "<a href=\"javascript:openwin('".$d."','".$pcode."')\"><font color=black><font style='font-size:8pt'>".$memStr."</font></font></a><font color=black><font style='font-size:8pt'><br>".$roomStr."</font></font>";

                      echo "<td height=35 style='width:10px !important;border:0.05em solid #848484;' align='center' bgcolor='".$cellBg."' title='".htmlspecialchars($titleTxt, ENT_QUOTES, 'UTF-8')."'>".$block."</td>";
                  }
                  $cellsHtml = ob_get_clean();
                ?>
                <tr>
                  <td align="left" style="border:0.05em solid #848484;" class="sticky-col first-col" bgcolor="<?= htmlspecialchars($bgcolor, ENT_QUOTES, 'UTF-8'); ?>">
                    <font style="font-size:8pt">&nbsp;<?= htmlspecialchars($pcode, ENT_QUOTES, 'UTF-8'); ?>&nbsp;<b><font color="red">(<?= $totalPers; ?>)</font></b><br/><b>&nbsp;<?= $pname ?></b></font>
                  </td>
                  <?= $cellsHtml; ?>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<?php include 'include/side_m.php'; ?>

<script>
$(document).ready(function(){
  if (window.pt && typeof pt.initReservationList === 'function')  pt.initReservationList();
  if (window.pt && typeof pt.initReservationDetail === 'function') pt.initReservationDetail();

  $('.tourDate1').datepicker({ format: 'yyyy-mm-dd', autoclose: true });
  $('.tourDate2').datepicker({ format: 'yyyy-mm-dd', autoclose: true });
  $('.tourDate3').datepicker({ minViewMode: 2, format: 'yyyy', autoclose: true });

  var table = $('#guide_table').DataTable({
    scrollY: 600,
    scrollX: true,
    scrollCollapse: true,
    paging: false,
    fixedHeader: true,
    ordering: false,
    autoWidth: false,
    deferRender: true, // 대량 DOM 렌더링 최적화
    columnDefs: [ { width: 150, targets: 0 } ],
    fixedColumns: true
  });
});

var ctr = 0;
function openwin(stdate, s_code, rcd){
  var winName = 'all_' + (ctr++);
  window.open('guide_assign_customer.php?division=<?= isset($division)?$division:''; ?>&pdx=<?= isset($pdx)?$pdx:''; ?>&sub=<?= isset($sub)?$sub:''; ?>&s_code='+encodeURIComponent(s_code)+'&stdate='+encodeURIComponent(stdate)+'&rcode='+(rcd||''), winName, 'width=1190px,height=700,scrollbars=1');
}
function numberOfDays(month, year){
  var d = new Date(year, month, 0); return d.getDate();
}
function cal(mon){
  if (mon < 10) mon = '0' + mon;
  var y = document.getElementById('startyear').value || '<?= date('Y'); ?>';
  var st = y + '-' + mon + '-01';
  var lastday = numberOfDays(parseInt(mon,10), parseInt(y,10));
  var ed = y + '-' + mon + '-' + lastday;
  document.getElementById('startDate1').value = st;
  document.getElementById('endDate').value   = ed;
  document.getElementById('frmName').submit();
}
</script>
</body>
</html>
