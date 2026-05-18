<?php
// -------------------------------------------------------------------------
// [중요] 세션 시작 확인 (header.php보다 먼저 실행하여 안전 확보)
// -------------------------------------------------------------------------
if (session_id() == '') {
    session_start();
}

include "include/header.php";

// -------------------------------------------------------------------------
// [기능 수정] 검색 조건 유지 로직 (강력한 버전)
// -------------------------------------------------------------------------
// 1. 검색 버튼을 눌러서 들어온 경우 (POST 'search')
if (isset($_POST['mode']) && $_POST['mode'] == 'search') {
    $_SESSION['ses_guide_settle_search'] = $_POST;
}
// 2. 상세 페이지에서 저장 후 돌아온 경우 (POST 값 없음, 세션 있음)
elseif (empty($_POST) && isset($_SESSION['ses_guide_settle_search'])) {
    $_POST = $_SESSION['ses_guide_settle_search'];

    // [핵심] 복구된 $_POST 데이터를 실제 변수($startDate1, $guide_kw 등)로 강제 변환
    // 이렇게 해야 아래쪽의 if($startDate1 == "") 초기화 로직을 건너뛸 수 있습니다.
    extract($_POST);
}

// -------------------------------------------------------------------------

if (!empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
} else {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}
if (!hasMenuAccess($division, $pdx, $sub)) {
    $goUrl_1 = "index.php";
    Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!", "");
    echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
    exit;
}

// 날짜 변수가 복구되지 않았을 때만 기본값(최근 7일) 설정
if (!isset($startDate1) || $startDate1 == "") {
    $startDate1 = date("Y-m-d", strtotime("-7days"));
    $endDate1   = date("Y-m-d", strtotime("+1 month"));
}

// 현재 검색 상태를 항상 세션에 저장 (저장 후 돌아왔을 때 복원용)
$_SESSION['ses_guide_settle_search'] = array_merge(
    isset($_SESSION['ses_guide_settle_search']) ? $_SESSION['ses_guide_settle_search'] : [],
    ['mode' => 'search', 'startDate1' => $startDate1, 'endDate1' => $endDate1]
);
if (!empty($_POST)) {
    $_SESSION['ses_guide_settle_search'] = array_merge(
        $_SESSION['ses_guide_settle_search'],
        $_POST
    );
}

function printSingle() {

    global $division, $crev, $pdx, $sub, $startDate1, $endDate1, $guideid, $dbConn;

    $view_type      = isset($_POST['view_type']) ? trim($_POST['view_type']) : '';
    $guide_kw       = isset($_POST['guide_kw']) ? trim($_POST['guide_kw']) : '';
    $settle_code_kw = isset($_POST['settle_code_kw']) ? trim($_POST['settle_code_kw']) : '';
    $pname_kw       = isset($_POST['pname_kw']) ? trim($_POST['pname_kw']) : '';
    $order_by       = isset($_POST['order_by']) ? trim($_POST['order_by']) : 'stDate_desc';
    $dep_from       = isset($_POST['dep_from']) ? trim($_POST['dep_from']) : '';
    $dep_to         = isset($_POST['dep_to']) ? trim($_POST['dep_to']) : '';

    $conds = array();

    // 날짜 범위
    if ($dep_from !== '' || $dep_to !== '') {
        if ($dep_from !== '') $conds[] = "a.stDate >= '".esc($dep_from)."'";
        if ($dep_to   !== '') $conds[] = "a.stDate <= '".esc($dep_to)."'";
    } else {
        if (!empty($startDate1)) $conds[] = "a.stDate >= '".esc($startDate1)."'";
        if (!empty($endDate1))   $conds[] = "a.stDate <= '".esc($endDate1)."'";
    }

    // 상태 필터
    switch ($view_type) {
        case 'unchecked':
            $conds[] = "(gsm.check_out IS NULL OR gsm.check_out <> 'V')";
            break;
        case 'finance_done':
            $conds[] = "(gsm.finance_st IS NOT NULL AND gsm.finance_st <> '' AND gsm.finance_date IS NOT NULL)";
            break;
        case 'report_missing':
            $conds[] = "(gsm.report_st IS NULL OR gsm.report_st = '' OR gsm.report_date IS NULL)";
            break;
    }

    // 키워드 필터
    if ($guide_kw !== '') {
        $kw = esc($guide_kw);
        $conds[] = "(a.guide_id LIKE '%{$kw}%' OR ml.kor_name LIKE '%{$kw}%')";
    }
    if ($settle_code_kw !== '') {
        $kw = esc($settle_code_kw);
        $conds[] = "gsm.settle_code LIKE '%{$kw}%'";
    }
    if ($pname_kw !== '') {
        $kw = esc($pname_kw);
        $conds[] = "a.p_name LIKE '%{$kw}%'";
    }

    $conds[] = "a.p_code NOT LIKE 'ADD%'";

    // 정렬
    $orderby = "a.stDate DESC";
    if ($order_by === 'stDate_asc') $orderby = "a.stDate ASC";
    if ($order_by === 'guide_asc')  $orderby = "ml.kor_name ASC, a.stDate DESC";
    if ($order_by === 'pcode_asc')  $orderby = "gsm.settle_code ASC, a.stDate DESC";

    $where = "WHERE ".implode(" AND ", $conds);

    /*
     * 최적화: 루프 내 반복 DB 호출 제거
     *  - gsm.reg_status    → getGuideStatus() 대체 (PHP에서 직접 계산)
     *  - ml.kor_name       → getinfo_dbMember() 대체 (이미 JOIN됨)
     *  - gsm.settle_code 등→ getGuideCode() 대체 (이미 JOIN됨)
     *  - p_day 서브쿼리    → getPeriodbyrev() 대체
     *  - p_cnt_sum 서브쿼리→ getReserveInfoCnt() 대체
     */
    $query = "
      SELECT DISTINCT
        a.seq_no, a.grand_eCode, a.sub_eCode, a.stDate, a.guide_id,
        a.p_code, a.p_name,
        gsm.settle_code, gsm.finance_date, gsm.report_date,
        gsm.check_out, gsm.check_date, gsm.reg_status,
        ml.kor_name,
        (SELECT b2.p_day FROM product_master b2
         WHERE b2.p_code = a.p_code ORDER BY b2.seq_no LIMIT 1) AS p_day,
        (SELECT SUM(ri.p_cnt) FROM reserve_info ri
         WHERE ri.p_code = a.p_code AND ri.stDate = a.stDate
           AND ri.rev_status = 'DONE') AS p_cnt_sum
      FROM tour_guide a
      LEFT JOIN (
          SELECT g1.*
          FROM guide_setmaster g1
          INNER JOIN (
              SELECT grand_eCode, sub_eCode, MAX(wdate) AS mw
              FROM guide_setmaster
              GROUP BY grand_eCode, sub_eCode
          ) g2
            ON g1.grand_eCode = g2.grand_eCode
           AND g1.sub_eCode   = g2.sub_eCode
           AND g1.wdate       = g2.mw
      ) gsm
        ON gsm.grand_eCode = a.grand_eCode
       AND gsm.sub_eCode   = a.sub_eCode
      LEFT JOIN member_list ml
        ON ml.userid = a.guide_id
      $where
        AND EXISTS (
          SELECT 1 FROM tour_master b
          WHERE b.grand_eCode = a.grand_eCode AND b.p_code = a.p_code
        )
      ORDER BY $orderby
    ";

    $rst1 = mysql_query($query, $dbConn);

    if (!$rst1) {
        return;
    }

    while ($row1 = mysql_fetch_assoc($rst1)) {

        $seq_no = $row1['seq_no'];
        $scode  = isset($row1['settle_code']) ? $row1['settle_code'] : '';
        $href   = "guide_cal_m.php?division=6&pdx=2&sub=10&number=".$seq_no."&scode=".$scode;

        $grandCode = $row1['grand_eCode']." <br/><font color='red'>".$row1['sub_eCode']."</font>";

        // 행사기간 — getPeriodbyrev() 대체
        $p_day  = isset($row1['p_day']) ? max(0, (int)$row1['p_day'] - 1) : 0;
        $period = $row1['stDate']." ~ ".date("Y-m-d", strtotime($row1['stDate']." +".$p_day." day"));

        // 행사인원 — getReserveInfoCnt() 대체
        $p_cnt_val = isset($row1['p_cnt_sum']) ? (int)$row1['p_cnt_sum'] : 0;

        // 상태 — getGuideStatus() 대체 (루프 내 DB 2회 → 0회)
        if ($scode === '') {
            $status = '미등록';
        } else if ($row1['reg_status'] === 'COMPLETE') {
            $status = '정산보고완료';
        } else if ($row1['reg_status'] === 'DONE') {
            $status = '등록';
        } else {
            $status = '미등록';
        }

        $check_out  = isset($row1['check_out'])  ? $row1['check_out']  : '';
        $check_date = isset($row1['check_date']) ? $row1['check_date'] : '';

        echo "<tr>
             <td align='center'><a href='{$href}'>".$scode."</a></td>
             <td align='center'><a href='{$href}'>".$grandCode."</a></td>
             <td align='center'><a href='{$href}'>".$row1['stDate']."</a></td>
             <td align='center'><a href='{$href}'>".$row1['p_name']."</a></td>
             <td align='center'><a href='{$href}'>".$period."</a></td>
             <td align='center'><a href='{$href}'>".$p_cnt_val."</a></td>
             <td align='center'><a href='{$href}'>".$row1['kor_name']."</a></td>
             <td align='center'><a href='{$href}'>".(isset($row1['report_date'])?$row1['report_date']:'')."</a></td>
             <td align='center'><a href='{$href}'>".(isset($row1['finance_date'])?$row1['finance_date']:'')."</a></td>
             <td align='center' class='check-date-cell'>";
        if ($check_out != 'V') {
            echo "<button type='button' class='btn btn-info btn-sm check-button' data-seq-no='{$seq_no}'>확인 완료</button>";
        } else {
            echo $check_date;
        }
        echo "</td>
             <td align='center'><a href='{$href}'>".$status."</a></td>
             </tr>";
    }
}
?>
<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">정산관리</a></li>
        <li>가이드정산등록</li>
      </ul>
    </div>

    <div class="row">
      <div class="col-sm-12 col-md-12">
        <form action="" name="frmName" method="post">
          <input type="hidden" name="mode" value="search">
          <table class="table table-bordered table-condensed">
            <tr>
              <td width="10%" class="titletd text-center">조회조건</td>
              <td>
                <div class="row" style="row-gap:8px;">
                  <div class="col-sm-3">
                    <label class="control-label" style="font-weight:600;">행사일(From)</label>
                    <div class="input-group input-group-sm">
                      <input type="date" class="form-control" id="startDate1" name="startDate1"
                             max="2999-12-31" value="<?=$startDate1?>" autocomplete="off" />
                    </div>
                  </div>
                  <div class="col-sm-3">
                    <label class="control-label" style="font-weight:600;">행사일(To)</label>
                    <div class="input-group input-group-sm">
                      <input type="date" class="form-control" id="endDate1" name="endDate1"
                             max="2999-12-31" value="<?=$endDate1?>" autocomplete="off" />
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <label class="control-label" style="opacity:.75;">빠른 선택</label>
                    <div class="btn-group btn-group-sm" role="group" id="quickRange" style="display:block;">
                      <button type="button" class="btn btn-default" data-range="7d">최근 7일</button>
                      <button type="button" class="btn btn-default" data-range="30d">최근 30일</button>
                      <button type="button" class="btn btn-default" data-range="thism">이번 달</button>
                      <button type="button" class="btn btn-default" data-range="nextm">다음 달</button>
                      <button type="button" class="btn btn-default" data-range="clear">지우기</button>
                    </div>
                  </div>
                </div>

                <div class="row" style="margin-top:10px; row-gap:8px;">
                  <div class="col-sm-3">
                    <label class="control-label" style="font-weight:600;">조회유형</label>
                    <select name="view_type" id="view_type" class="form-control input-sm">
                      <?php $vt = isset($_POST['view_type']) ? $_POST['view_type'] : ''; ?>
                      <option value="" <?=($vt===''?'selected':'')?>>전체</option>
                      <option value="unchecked" <?=($vt==='unchecked'?'selected':'')?>>체크 미완료(미확인)</option>
                      <option value="finance_done" <?=($vt==='finance_done'?'selected':'')?>>회계확인 완료</option>
                      <option value="report_missing" <?=($vt==='report_missing'?'selected':'')?>>가이드 보고 미제출</option>
                      <option value="my_guide" <?=($vt==='my_guide'?'selected':'')?>>특정 가이드</option>
                    </select>
                  </div>
                  <div class="col-sm-3">
                    <label class="control-label" style="font-weight:600;">정렬</label>
                    <?php $ob = isset($_POST['order_by']) ? $_POST['order_by'] : 'stDate_desc'; ?>
                    <select name="order_by" class="form-control input-sm">
                      <option value="stDate_desc" <?=$ob==='stDate_desc'?'selected':''?>>행사일 ↓</option>
                      <option value="stDate_asc"  <?=$ob==='stDate_asc'?'selected':''?>>행사일 ↑</option>
                      <option value="pcode_asc"   <?=$ob==='pcode_asc'?'selected':''?>>정산코드</option>
                      <option value="guide_asc"   <?=$ob==='guide_asc'?'selected':''?>>가이드명</option>
                    </select>
                  </div>
                  <div class="col-sm-3">
                    <label class="control-label" style="font-weight:600;">가이드ID/이름</label>
                    <input type="text" class="form-control input-sm" name="guide_kw"
                           value="<?=htmlspecialchars(isset($_POST['guide_kw'])?$_POST['guide_kw']:'', ENT_QUOTES)?>" placeholder="예: honggildong">
                  </div>
                  <div class="col-sm-3">
                    <label class="control-label" style="font-weight:600;">정산코드</label>
                    <input type="text" class="form-control input-sm" name="settle_code_kw"
                           value="<?=htmlspecialchars(isset($_POST['settle_code_kw'])?$_POST['settle_code_kw']:'', ENT_QUOTES)?>" placeholder="예: GU-101512">
                  </div>
                </div>

                <div class="row" style="margin-top:10px;">
                  <div class="col-sm-3">
                    <label class="control-label" style="font-weight:600;">상품명</label>
                    <input type="text" class="form-control input-sm" name="pname_kw"
                           value="<?=htmlspecialchars(isset($_POST['pname_kw'])?$_POST['pname_kw']:'', ENT_QUOTES)?>" placeholder="상품명 키워드">
                  </div>
                  <div class="col-sm-3" style="padding-top:22px;">
                    <button type='submit' class="btn btn-primary btn-sm btn1">검색</button>
                  </div>
                </div>
              </td>
            </tr>
          </table>
        </form>

        <br />

        <div class="row">
          <div class="col-sm-12">
            <table id='ctable' class="table table-striped table-bordered table-hover table-condensed js-productTable">
              <thead>
                <tr>
                  <th>가이드정산코드</th>
                  <th>행사코드</th>
                  <th>행사일</th>
                  <th>행사명</th>
                  <th>행사기간</th>
                  <th>행사인원</th>
                  <th>가이드명</th>
                  <th>기이드제출날짜</th>
                  <th>회계확인날짜</th>
                  <th>체크 확인일</th>
                  <th>상태</th>
                </tr>
              </thead>
              <tbody>
                <?php echo printSingle(); ?>
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
$(document).ready(function () {

  var oTable = $('#ctable').dataTable({
    stateSave: true,
    pageLength: 100,
    "order": [[ 2, "asc" ]]
  });

  $(".dataTables_length").css({ "display" :"none" });

  // '확인 완료' 버튼 클릭 이벤트 처리
  $('#ctable').on('click', '.check-button', function() {
    var $button = $(this);
    var seqNo = $button.data('seq-no');
    var $cell = $button.closest('td');

    var updateUrl = 'update_check.php';

    $.ajax({
      url: updateUrl,
      method: 'POST',
      data: { seq_no: seqNo },
      dataType: 'json',
      success: function(response) {
        if (response.status === 'success') {
          $button.remove();
          $cell.text(response.check_date);
        } else {
          alert('업데이트 실패: ' + response.message);
        }
      },
      error: function(xhr, status, error) {
        alert('AJAX 오류 발생: ' + status + ' - ' + error);
        console.error(xhr.responseText);
      }
    });
  });

  // 행사일 기간 빠른 선택
  $('#quickRange button').on('click', function(){
    const v = $(this).data('range');
    const now = new Date();
    const toISO = d => d.toISOString().slice(0,10);

    let s = '', e = '';
    if (v==='7d')   { e = now; const sdt=new Date(now); sdt.setDate(now.getDate()-7); s = sdt; }
    if (v==='30d')  { e = now; const sdt=new Date(now); sdt.setDate(now.getDate()-30); s = sdt; }
    if (v==='thism'){ const sdt=new Date(now.getFullYear(),now.getMonth(),1);
                      const edt=new Date(now.getFullYear(),now.getMonth()+1,0);
                      s=sdt; e=edt; }
    if (v==='nextm'){ const sdt=new Date(now.getFullYear(),now.getMonth()+1,1);
                      const edt=new Date(now.getFullYear(),now.getMonth()+2,0);
                      s=sdt; e=edt; }
    if (v==='clear'){ $('#startDate1, #endDate1').val(''); return; }

    $('#startDate1').val(toISO(s));
    $('#endDate1').val(toISO(e));
  });

});
</script>
</body>
</html>

