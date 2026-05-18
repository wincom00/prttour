<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
    include "include/header.php";
    //include "include/inc_base.php";

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

    // ===== 기본 날짜 (오늘 ~ +3개월) =====
    if ($startDate1 == "") {
        $startDate1 = date("Y-m-d", strtotime("now"));
        $endDate    = date("Y-m-d", strtotime("+3 month"));
    }

    // ===== 입력값(필터) =====
    $mode         = isset($_POST['mode']) ? $_POST['mode'] : '';
    $sDate        = isset($_POST['startDate1']) ? $_POST['startDate1'] : $startDate1;
    $eDate        = isset($_POST['endDate'])    ? $_POST['endDate']    : $endDate;

    $pname        = isset($_POST['pname']) ? trim($_POST['pname']) : '';
    $reserveCode  = isset($_POST['reserveCode']) ? trim($_POST['reserveCode']) : '';
    $book_pri     = isset($_POST['book_pri']) ? trim($_POST['book_pri']) : '';
    $book_phone   = isset($_POST['book_phone']) ? trim($_POST['book_phone']) : '';
    $rand_id      = isset($_POST['rand_id']) ? trim($_POST['rand_id']) : '';

    // ===== 유틸 =====
   
    function mres($s) {
        return mysql_real_escape_string($s);
    }
    function chk($v, $arr) {
        return (is_array($arr) && in_array($v, $arr)) ? "checked" : "";
    }

    /*
      ✅ 체크박스 + READY 기본값 정책
      - 첫 진입(POST 없음) : READY 자동 체크
      - 사용자가 체크를 "전부 해제"하고 검색 : 필터 미적용(전체처럼)
    */
    $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');

    if (!$isPost) {
        $payment_st_arr = array('READY');
        $rev_status_arr = array('READY');
    } else {
        $payment_st_arr = array();
        $rev_status_arr = array();

        if (isset($_POST['payment_st']) && is_array($_POST['payment_st'])) $payment_st_arr = $_POST['payment_st'];
        if (isset($_POST['rev_status']) && is_array($_POST['rev_status'])) $rev_status_arr = $_POST['rev_status'];
    }

    // ===== 미수금 리스트 출력 =====
    function printReceivableList($sDate, $eDate, $filters = array()) {
        global $dbConn,$_GET;

        $where = " WHERE 1=1 ";

        // ✅ 무조건 MAIN만
        $where .= " AND A.parent = 'MAIN' ";

        // ✅ MAIN이 여러개면 reserveCode 당 1건만 (seq_no 최소)
        $where .= " AND A.seq_no = (
                        SELECT MIN(X.seq_no)
                        FROM reserve_info X
                        WHERE X.reserveCode = A.reserveCode
                          AND X.parent = 'MAIN'
                   ) ";

        // 날짜는 출발일(stDate) 기준
        if ($sDate != "" && $eDate != "") {
            $sd = mres($sDate);
            $ed = mres($eDate);
            $where .= " AND A.stDate >= '{$sd}' AND A.stDate <= '{$ed}' ";
        }

        // 취소 제외(기본)
        $where .= " AND IFNULL(A.rev_status,'') != 'CANCEL' ";

        // 미수금만 (last_bal > 0)
        $where .= " AND IFNULL(A.last_bal,0) > 0 ";

        // 상품명 검색(공백 키워드 AND)
        if (!empty($filters['pname'])) {
            $keywords = explode(' ', $filters['pname']);
            $likes = array();
            foreach ($keywords as $kw) {
                $kw = trim($kw);
                if ($kw === '') continue;
                $kw = mres($kw);
                $likes[] = "A.p_name LIKE '%{$kw}%'";
            }
            if (count($likes) > 0) {
                $where .= " AND (" . implode(" AND ", $likes) . ") ";
            }
        }

        if (!empty($filters['reserveCode'])) {
            $rc = mres($filters['reserveCode']);
            $where .= " AND A.reserveCode LIKE '%{$rc}%' ";
        }
        if (!empty($filters['book_pri'])) {
            $bp = mres($filters['book_pri']);
            $where .= " AND A.book_pri LIKE '%{$bp}%' ";
        }
        if (!empty($filters['book_phone'])) {
            $ph = mres($filters['book_phone']);
            $where .= " AND A.book_phone LIKE '%{$ph}%' ";
        }
        if (!empty($filters['rand_id'])) {
            $rid = mres($filters['rand_id']);
            $where .= " AND A.rand_id = '{$rid}' ";
        }

        // ✅ 결제상태 체크박스 IN()
        if (!empty($filters['payment_st_arr']) && is_array($filters['payment_st_arr'])) {
            $in = array();
            foreach ($filters['payment_st_arr'] as $v) {
                $v = trim($v);
                if ($v === '') continue;
                $in[] = "'" . mres($v) . "'";
            }
            if (count($in) > 0) {
                $where .= " AND IFNULL(A.payment_st,'') IN (" . implode(",", $in) . ") ";
            }
        }

        // ✅ 예약상태 체크박스 IN()
        if (!empty($filters['rev_status_arr']) && is_array($filters['rev_status_arr'])) {
            $in = array();
            foreach ($filters['rev_status_arr'] as $v) {
                $v = trim($v);
                if ($v === '') continue;
                $in[] = "'" . mres($v) . "'";
            }
            if (count($in) > 0) {
                $where .= " AND IFNULL(A.rev_status,'') IN (" . implode(",", $in) . ") ";
            }
        }

        // payment_history 합산(예약코드별)
        $qry = "
            SELECT
                A.reserveCode,
                A.grand_revNo,
                A.p_code,
                A.p_name,
                A.stDate,
                A.edDate,
                A.book_pri,
                A.book_phone,
                A.book_email,
                A.rand_id,
                A.last_total,
                A.last_bal,
                A.payment_st,
                A.rev_status,
				A.tour_type,
                IFNULL(PH.paid_amt, 0) AS paid_amt,
                (IFNULL(A.last_total,0) - IFNULL(PH.paid_amt,0)) AS calc_bal

            FROM reserve_info A
            LEFT JOIN (
                SELECT
                    reserveCode,
                    SUM(payment) AS paid_amt
                FROM payment_history
                WHERE 1=1
                AND (payment_status = 'DONE' OR payment_status IS NULL OR payment_status = '')
                GROUP BY reserveCode
            ) PH ON PH.reserveCode = A.reserveCode

            {$where}
            ORDER BY A.stDate ASC, A.reserveCode ASC
        ";

        $rst = mysql_query($qry, $dbConn);
        if (!$rst) {
            return "<tr><td colspan='12' class='text-center' style='color:red;'>SQL ERROR : ".esc(mysql_error())."</td></tr>";
        }

        $list = "";
        $i = 0;
        while ($row = mysql_fetch_assoc($rst)) {
            $i++;

            $reserveCode = esc($row['reserveCode']);
            $pcode       = esc($row['p_code']);
            $pname       = $row['p_name']; // 원본 유지
            $stDate      = esc($row['stDate']);

            $book_pri    = esc($row['book_pri']);
            $book_phone  = esc($row['book_phone']);
            $rand_id     = esc($row['rand_id']);
			$tour_type     = esc($row['tour_type']);

            $last_total  = number_format((float)$row['last_total'], 2);
            $paid_amt    = number_format((float)$row['paid_amt'], 2);
            $last_bal    = number_format((float)$row['last_bal'], 2);
            $calc_bal    = number_format((float)$row['calc_bal'], 2);

            $payment_st  = esc($row['payment_st']);
            $rev_status  = esc($row['rev_status']);

            $detailUrl = "base_reservation_m.php?division=".$_GET['division']."&pdx=".$_GET['pdx']."&sub=".$_GET['sub']."&pricet=".$tour_type."&estimateCode=" . urlencode($row['reserveCode']);

            $list .= "
                <tr>
                    <td class='text-center'>{$i}</td>
                    <td class='text-center'><a href='{$detailUrl}' target='_blank'>{$reserveCode}</a></td>
                    <td class='text-center'>{$stDate}</td>
                    <td class='text-center'>{$pcode}</td>
                    <td>{$pname}</td>
                    <td class='text-center'>{$book_pri}</td>
                    <td class='text-center'>{$book_phone}</td>
                    <td class='text-center'>{$rand_id}</td>
                    <td class='text-right'>{$last_total}</td>
                    <td class='text-right'>{$paid_amt}</td>
                    <td class='text-right'><b style='color:#d9534f;'>{$last_bal}</b></td>
                    <td class='text-center'>
                        <span class='label label-info'>{$payment_st}</span>
                        <span class='label label-default'>{$rev_status}</span>
                        <div style='font-size:11px; margin-top:4px; color:#777;'>계산잔액: {$calc_bal}</div>
                    </td>
                </tr>
            ";
        }

        if ($i <= 0) {
            $list = "<tr><td colspan='12' class='text-center'>조회 결과가 없습니다.</td></tr>";
        }

        return $list;
    }

    // ===== KPI 계산 =====
    function getReceivableKPI($sDate, $eDate, $filters = array()) {
        global $dbConn;

        $where = " WHERE 1=1 ";

        // ✅ 무조건 MAIN만 + reserveCode당 1건
        $where .= " AND A.parent = 'MAIN' ";
        $where .= " AND A.seq_no = (
                        SELECT MAX(X.seq_no)
                        FROM reserve_info X
                        WHERE X.reserveCode = A.reserveCode
                          AND X.parent = 'MAIN'
                   ) ";

        if ($sDate != "" && $eDate != "") {
            $sd = mres($sDate);
            $ed = mres($eDate);
            $where .= " AND A.stDate >= '{$sd}' AND A.stDate <= '{$ed}' ";
        }
        $where .= " AND IFNULL(A.rev_status,'') != 'CANCEL' ";
        $where .= " AND IFNULL(A.last_bal,0) > 0 ";

        if (!empty($filters['pname'])) {
            $keywords = explode(' ', $filters['pname']);
            $likes = array();
            foreach ($keywords as $kw) {
                $kw = trim($kw);
                if ($kw === '') continue;
                $kw = mres($kw);
                $likes[] = "A.p_name LIKE '%{$kw}%'";
            }
            if (count($likes) > 0) {
                $where .= " AND (" . implode(" AND ", $likes) . ") ";
            }
        }
        if (!empty($filters['reserveCode'])) $where .= " AND A.reserveCode LIKE '%" . mres($filters['reserveCode']) . "%' ";
        if (!empty($filters['book_pri']))    $where .= " AND A.book_pri LIKE '%" . mres($filters['book_pri']) . "%' ";
        if (!empty($filters['book_phone']))  $where .= " AND A.book_phone LIKE '%" . mres($filters['book_phone']) . "%' ";
        if (!empty($filters['rand_id']))     $where .= " AND A.rand_id = '" . mres($filters['rand_id']) . "' ";

        if (!empty($filters['payment_st_arr']) && is_array($filters['payment_st_arr'])) {
            $in = array();
            foreach ($filters['payment_st_arr'] as $v) {
                $v = trim($v);
                if ($v === '') continue;
                $in[] = "'" . mres($v) . "'";
            }
            if (count($in) > 0) $where .= " AND IFNULL(A.payment_st,'') IN (" . implode(",", $in) . ") ";
        }
        if (!empty($filters['rev_status_arr']) && is_array($filters['rev_status_arr'])) {
            $in = array();
            foreach ($filters['rev_status_arr'] as $v) {
                $v = trim($v);
                if ($v === '') continue;
                $in[] = "'" . mres($v) . "'";
            }
            if (count($in) > 0) $where .= " AND IFNULL(A.rev_status,'') IN (" . implode(",", $in) . ") ";
        }

        $qry = "
            SELECT
                COUNT(*) AS cnt,
                SUM(IFNULL(A.last_total,0)) AS sum_total,
                SUM(IFNULL(A.last_bal,0)) AS sum_bal
            FROM reserve_info A
            {$where}
        ";
        $rst = mysql_query($qry, $dbConn);
        $row = mysql_fetch_assoc($rst);

        return array(
            'cnt' => (int)$row['cnt'],
            'sum_total' => (float)$row['sum_total'],
            'sum_bal' => (float)$row['sum_bal'],
        );
    }

    $filters = array(
        'pname' => $pname,
        'reserveCode' => $reserveCode,
        'book_pri' => $book_pri,
        'book_phone' => $book_phone,
        'rand_id' => $rand_id,
        'payment_st_arr' => $payment_st_arr,
        'rev_status_arr' => $rev_status_arr,
    );

    $kpi = getReceivableKPI($sDate, $eDate, $filters);
?>

<div id="contentwrapper" class="productDetailForm">
    <div class="main_content">

        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">정산관리</a></li>
                <li><a href="#">미수금관리</a></li>
                <li>미수금 현황</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">

                <!-- KPI -->
                <div class="row" style="margin-bottom:10px;">
                    <div class="col-sm-4">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div style="font-size:12px;color:#777;">미수 건수</div>
                                <div style="font-size:22px;font-weight:700;"><?=number_format($kpi['cnt'])?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div style="font-size:12px;color:#777;">총 판매금액(미수 대상)</div>
                                <div style="font-size:22px;font-weight:700;"><?=number_format($kpi['sum_total'],2)?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="panel panel-danger">
                            <div class="panel-body">
                                <div style="font-size:12px;color:#777;">총 미수금액</div>
                                <div style="font-size:22px;font-weight:800;color:#d9534f;"><?=number_format($kpi['sum_bal'],2)?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 검색 -->
                <form action="<?= $PHP_SELF ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" method="post" name="frm_receivable" id="frm_receivable">
                    <input type="hidden" name="mode" value="search">

                    <table class="table table-bordered table-condensed">
                        <tr>
                            <td width="10%" class="titletd text-center">출발일</td>
                            <td width="40%">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <input type="text" id="startDate1" name="startDate1" class="inpubase tourDate1" placeholder="시작일" value="<?=esc($sDate)?>" autocomplete="off" />
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" id="endDate" name="endDate" class="inpubase tourDate1" placeholder="마지막일" value="<?=esc($eDate)?>" autocomplete="off" />
                                    </div>
                                </div>
                            </td>
                            <td width="10%" class="titletd text-center">상품명</td>
                            <td width="40%">
                                <input type="text" name="pname" class="inpubase" value="<?=esc($pname)?>" placeholder="상품명 키워드(공백 AND 검색)">
                            </td>
                        </tr>
                        <tr>
                            <td class="titletd text-center">예약코드</td>
                            <td><input type="text" name="reserveCode" class="inpubase" value="<?=esc($reserveCode)?>" placeholder="예약코드"></td>
                            <td class="titletd text-center">예약자명</td>
                            <td><input type="text" name="book_pri" class="inpubase" value="<?=esc($book_pri)?>" placeholder="예약자명"></td>
                        </tr>
                        <tr>
                            <td class="titletd text-center">연락처</td>
                            <td><input type="text" name="book_phone" class="inpubase" value="<?=esc($book_phone)?>" placeholder="연락처"></td>
                            <td class="titletd text-center">에이전시ID</td>
                            <td><input type="text" name="rand_id" class="inpubase" value="<?=esc($rand_id)?>" placeholder="에이전시ID"></td>
                        </tr>

                        <tr>
                            <td class="titletd text-center">결제상태</td>
                            <td>
                                <label style="margin-right:12px;">
                                    <input type="checkbox" name="payment_st[]" value="READY" <?=chk('READY',$payment_st_arr)?>> READY
                                </label>
                                <label style="margin-right:12px;">
                                    <input type="checkbox" name="payment_st[]" value="DONE" <?=chk('DONE',$payment_st_arr)?>> DONE
                                </label>
                                <label style="margin-right:12px;">
                                    <input type="checkbox" name="payment_st[]" value="CANCEL" <?=chk('CANCEL',$payment_st_arr)?>> CANCEL
                                </label>
                            </td>

                            <td class="titletd text-center">예약상태</td>
                            <td>
                                <label style="margin-right:12px;">
                                    <input type="checkbox" name="rev_status[]" value="READY" <?=chk('READY',$rev_status_arr)?>> READY
                                </label>
                                <label style="margin-right:12px;">
                                    <input type="checkbox" name="rev_status[]" value="DONE" <?=chk('DONE',$rev_status_arr)?>> DONE
                                </label>
                                <label style="margin-right:12px;">
                                    <input type="checkbox" name="rev_status[]" value="CANCEL" <?=chk('CANCEL',$rev_status_arr)?>> CANCEL
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" class="text-center">
                                <button type="submit" class="btn btn-primary btn-sm">검색</button>
                            </td>
                        </tr>
                    </table>
                </form>

                <br />

                <div class="row">
                    <div class="col-sm-12">
                        <table class="table table-striped table-bordered table-hover table-condensed js-receivableTable">
                            <thead>
                                <tr>
                                    <th style="width:50px;">No</th>
                                    <th>예약코드</th>
                                    <th>출발일</th>
                                    <th>상품코드</th>
                                    <th>상품명</th>
                                    <th>예약자</th>
                                    <th>연락처</th>
                                    <th>에이전시ID</th>
                                    <th class="text-right">총액</th>
                                    <th class="text-right">수금(결제합)</th>
                                    <th class="text-right">미수금</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo printReceivableList($sDate, $eDate, $filters); ?>
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
    $.ajaxSetup({async:false});

    $('.tourDate1').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true
    });

    $('.js-receivableTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'print'],
        pageLength: 50,
        order: [[2, "asc"]]
    });

    $(".dataTables_length").css({ "display" :"none" });
});
</script>

</body>
</html>
