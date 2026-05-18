<?php
    include "include/header.php";

    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
    } else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }

    /* ===============================
     * 파라미터 / 초기값
     * =============================== */
    $companyName  = isset($_POST['companyName']) ? trim($_POST['companyName']) : (isset($_GET['companyName']) ? trim($_GET['companyName']) : "");
    $company_area = isset($_POST['company_area']) ? trim($_POST['company_area']) : (isset($_GET['company_area']) ? trim($_GET['company_area']) : "");
    $basedate     = isset($_POST['basedate']) ? trim($_POST['basedate']) : (isset($_GET['basedate']) ? trim($_GET['basedate']) : "1");
    $StartYMD     = isset($_POST['StartYMD']) ? trim($_POST['StartYMD']) : (isset($_GET['StartYMD']) ? trim($_GET['StartYMD']) : "");

    if ($startyear == "") $startyear = date("Y");

    if ($StartYMD == "" || $StartYMD == "null") $StartYMD = date("Y-m");
    if (!preg_match('/^\d{4}\-\d{2}$/', $StartYMD)) $StartYMD = date("Y-m");

    // 기존 변수들 원본 스타일 유지
    if ($StartYMD) {
        $year = date("Y");
        $EndYMD = isset($EndYMD) ? $EndYMD : "";
    } else {
        $year = date("Y");
        $month = date("m");
    }

    /* ===============================
     * 업체 지역 기본값 (원본 유지)
     * =============================== */
    if ($company_area != "") {
        $sqlSelCondition = " && company_area = '" . mysql_real_escape_string($company_area) . "'";
    } else {
        $sqlSelCondition = "&& company_area ='A010120'";
        $company_area = "A010120";
    }

    /* ===============================
     * dept 조건 (원본 유지)
     * =============================== */
    if (($user_dbinfo['dept_prior'] == "J") || ($user_dbinfo['dept_prior'] == "")) {
        $deptqry = " && ((company_area like '%" . mysql_real_escape_string($user_dbinfo['company_area']) . "%'))";
    } else {
        $deptqry = "";
    }

    /* ===============================
     * 13개월 리스트 생성 (원본 계산 방식 유지)
     * =============================== */
    $start_date = explode("-", $StartYMD); // [Y, m]
    $baseY = (int)$start_date[0];
    $baseM = (int)$start_date[1];

    $months = array();   // 'YYYY-MM'
    for ($i = 13; $i > 0; $i--) {
        $months[] = date("Y-m", mktime(0,0,0, ($baseM+6)-$i, 1, $baseY));
    }
    $curYM = date("Y-m", mktime(0,0,0,$baseM,1,$baseY));

    // 집계 범위: 첫달 1일 ~ 마지막달 다음달 1일
    $minDate = $months[0] . "-01";
    $lastFirst = $months[count($months)-1] . "-01";
    $maxNext = date("Y-m-d", strtotime($lastFirst . " +1 month"));

    /* ===============================
     * 기준 컬럼 선택
     *  basedate=1 : 출발월 (reserve_info.stDate, rand_pay.stDate)
     *  basedate=2 : 판매월 (reserve_info.revDate, rand_pay.rand_date)
     * =============================== */
    $dateCol    = ($basedate == "2") ? "b.revDate" : "b.stDate";
    $payDateCol = ($basedate == "2") ? "rand_date" : "stDate";

    /* =========================================================
     * [핵심 수정] CANCEL은 "예약 자체가 CANCEL이면" 무조건 제외
     * - rand_company 집계에서 b.rev_status!='CANCEL' 유지
     * - rand_pay 집계도 CANCEL 예약과 조인해서 제외
     * - 예약건수 집계도 동일
     * ========================================================= */

    /* ===============================
     * [1] rand_company 월별 credit/debit 집계 (1쿼리)
     * =============================== */
    $mapCompany = array(); // [part_id][ym] = ['credit'=>, 'debit'=>]
    $sqlAggCompany = "
        SELECT
            a.part_id,
            DATE_FORMAT($dateCol, '%Y-%m') AS ym,
            SUM(CASE WHEN a.money_type='credit' THEN a.amt ELSE 0 END) AS credit_sum,
            SUM(CASE WHEN a.money_type='debit'  THEN a.amt ELSE 0 END) AS debit_sum
        FROM rand_company a
        JOIN reserve_info b ON a.reserveCode = b.reserveCode
        WHERE
            a.reserveCode IS NOT NULL
            AND b.parent='MAIN'
            AND b.rev_status!='CANCEL'
            AND $dateCol >= '" . mysql_real_escape_string($minDate) . "'
            AND $dateCol <  '" . mysql_real_escape_string($maxNext) . "'
        GROUP BY a.part_id, ym
    ";
    $rstAggCompany = mysql_query($sqlAggCompany);
    if ($rstAggCompany) {
        while ($r = mysql_fetch_assoc($rstAggCompany)) {
            $pid = $r['part_id'];
            $ym  = $r['ym'];
            if (!isset($mapCompany[$pid])) $mapCompany[$pid] = array();
            $mapCompany[$pid][$ym] = array(
                'credit' => (double)$r['credit_sum'],
                'debit'  => (double)$r['debit_sum']
            );
        }
    }

    /* ===============================
     * [2] rand_pay 월별 수금 집계 (1쿼리)
     *  - CANCEL 예약은 "아예 계산에서 제외" (reserve_info 조인)
     *  - pay 기록 존재 여부(pay_cnt)는 CANCEL 제외 후 카운트
     * =============================== */
    $mapPaySum = array();   // [rand_id][ym] = pay_sum
    $mapPayCnt = array();   // [rand_id][ym] = pay_cnt
    $sqlAggPay = "
        SELECT
            p.rand_id,
            DATE_FORMAT(p.$payDateCol, '%Y-%m') AS ym,
            SUM(p.payment) AS pay_sum,
            COUNT(*) AS pay_cnt
        FROM rand_pay p
        JOIN reserve_info b ON p.reserveCode = b.reserveCode
        WHERE
            p.reserveCode IS NOT NULL
            AND b.parent='MAIN'
            AND b.rev_status!='CANCEL'
            AND p.$payDateCol >= '" . mysql_real_escape_string($minDate) . "'
            AND p.$payDateCol <  '" . mysql_real_escape_string($maxNext) . "'
        GROUP BY p.rand_id, ym
    ";
    $rstAggPay = mysql_query($sqlAggPay);
    if ($rstAggPay) {
        while ($r = mysql_fetch_assoc($rstAggPay)) {
            $rid = $r['rand_id'];
            $ym  = $r['ym'];
            if (!isset($mapPaySum[$rid])) $mapPaySum[$rid] = array();
            if (!isset($mapPayCnt[$rid])) $mapPayCnt[$rid] = array();
            $mapPaySum[$rid][$ym] = (double)$r['pay_sum'];
            $mapPayCnt[$rid][$ym] = (int)$r['pay_cnt'];
        }
    }

    /* ===============================
     * [3] ⭐ 예약건수 집계 (1쿼리)
     *  - CANCEL 제외
     * =============================== */
    $mapCnt = array(); // [part_id][ym] = cnt
    $sqlAggCnt = "
        SELECT
            a.part_id,
            DATE_FORMAT($dateCol, '%Y-%m') AS ym,
            COUNT(DISTINCT a.reserveCode) AS cnt
        FROM rand_company a
        JOIN reserve_info b ON a.reserveCode = b.reserveCode
        WHERE
            a.reserveCode IS NOT NULL
            AND b.parent='MAIN'
            AND b.rev_status!='CANCEL'
            AND $dateCol >= '" . mysql_real_escape_string($minDate) . "'
            AND $dateCol <  '" . mysql_real_escape_string($maxNext) . "'
        GROUP BY a.part_id, ym
    ";
    $rstAggCnt = mysql_query($sqlAggCnt);
    if ($rstAggCnt) {
        while ($r = mysql_fetch_assoc($rstAggCnt)) {
            $pid = $r['part_id'];
            $ym  = $r['ym'];
            if (!isset($mapCnt[$pid])) $mapCnt[$pid] = array();
            $mapCnt[$pid][$ym] = (int)$r['cnt'];
        }
    }
?>
<link rel="stylesheet" type="text/css" href="lib/datatables.css"/>

<style>
    .tableFixHead          { overflow-y: auto; height: 600px; }
    .tableFixHead thead th { position: sticky; top: 0; background:#eee;border:0.05em solid #848484; }
    table.dataTable thead th, table.dataTable thead td { border-bottom: 1px solid #111; }
</style>

<div id="contentwrapper" class="reservationDetailForm">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">업체정산</a></li>
                <li>업체별정산현황</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="<?= $PHP_SELF ?>" name="frmName" id="frmName" method="post">
                    <input type="hidden" name="mode" value="search">

                    <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                        <tbody>
                            <tr>
                                <td colspan="2" class="active text-center formHeader">업체명</td>
                                <td colspan="6">
                                    <input type="text" name="companyName" class="form-control" aria-label="업체명입력" placeholder="업체명입력" value="<?= htmlspecialchars($companyName) ?>" />
                                </td>
                                <td colspan="2" class="active text-center formHeader">지역별업체</td>
                                <td colspan="6">
                                    <select name="company_area" class="inpubase md">
                                        <option value=''> 전체보기 </option>
                                        <?= printBaseCode4_without('A01', $company_area); ?>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2" class="active text-center formHeader">
                                    <select name="basedate" class="form-control">
                                        <option <?php if ($basedate == "1") echo "selected"; ?> value="1">출발 기준월</option>
                                        <option <?php if ($basedate == "2") echo "selected"; ?> value="2">판매 기준월</option>
                                    </select>
                                </td>
                                <td colspan="12">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <input type="text" name="StartYMD" data-date-format="yyyy-mm" class="form-control tourdate1"
                                                   aria-label="조회기간" placeholder="조회기간" autocomplete="off" value="<?= htmlspecialchars($StartYMD) ?>">
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="16" class="text-center">
                                    <button type="submit" class="btn btn-primary btn-sm btn1">검색</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>

                <br />

                <div class="row">
                    <div class="col-sm-12 tableFixHead">
                        <table width="100%" id="guide_table" class="display nowrap table-bordered text-right">
                            <thead>
                                <tr>
                                    <th style="border:0.05em solid #848484;width:17%" height="28px" align="center">업 체 명</th>
                                    <?php
                                        for ($i=0; $i<count($months); $i++) {
                                            $ym = $months[$i];
                                            $bgCellStyle = ($ym == $curYM) ? "background-color:#D4EFDF;" : "";
                                            echo "<th style='margin:0;border:0.05em solid #848484;$bgCellStyle' align='center'><font style='font-size:7pt'>$ym</font></th>";
                                        }
                                    ?>
                                </tr>
                            </thead>

                            <tbody>
                            <?php
                                $whereName = "";
                                if ($companyName != "") {
                                    $cn = mysql_real_escape_string($companyName);
                                    $whereName = " && (userid LIKE '%$cn%' OR kor_name LIKE '%$cn%')";
                                }

                                $zip_qry1 = "select * from member_list
                                            where division = 'comp' and del_yn  ='N'
                                            $sqlSelCondition
                                            $deptqry
                                            $whereName
                                            order by a_color,pos desc";
                                $zip_rst1 = mysql_query($zip_qry1);

                                while($zip_row1 = mysql_fetch_assoc($zip_rst1)) {

                                    $pid = $zip_row1['userid'];
                                    $bg  = ($zip_row1['a_color'] != "") ? $zip_row1['a_color'] : "#ffffff";

                                    $month_td = "";
                                    for ($mi=0; $mi<count($months); $mi++) {

                                        $ym = $months[$mi];

                                        $credit = 0; $debit = 0; $pay = 0; $pay_cnt = 0; $cnt = 0;

                                        if (isset($mapCompany[$pid]) && isset($mapCompany[$pid][$ym])) {
                                            $credit = (double)$mapCompany[$pid][$ym]['credit'];
                                            $debit  = (double)$mapCompany[$pid][$ym]['debit'];
                                        }
                                        if (isset($mapPaySum[$pid]) && isset($mapPaySum[$pid][$ym])) {
                                            $pay = (double)$mapPaySum[$pid][$ym];
                                        }
                                        if (isset($mapPayCnt[$pid]) && isset($mapPayCnt[$pid][$ym])) {
                                            $pay_cnt = (int)$mapPayCnt[$pid][$ym];
                                        }
                                        if (isset($mapCnt[$pid]) && isset($mapCnt[$pid][$ym])) {
                                            $cnt = (int)$mapCnt[$pid][$ym];
                                        }

                                        // 원본 계산 로직 유지
                                        $tot_bal = (double)$credit + -(double)$debit;
                                        $tot_bal = $tot_bal + -($pay);

                                        $creditTot = ($credit != 0) ? number_format($credit, 2) : "";
                                        $debitTot  = ($debit  != 0) ? number_format($debit,  2) : "";

                                        // ✅ 정산완료 표시 로직 (요구사항):
                                        // - credit/debit 이 존재
                                        // - 해당 월에 pay "레코드" 존재
                                        $has_cd  = (($credit != 0) || ($debit != 0));
                                        $has_pay = ($pay_cnt > 0);

                                        if (($tot_bal != 0) && ($tot_bal != "")) {
                                            $totBal = number_format($tot_bal, 2);
                                        } else {
                                            $totBal = ($has_cd && $has_pay) ? "정산완료" : "";
                                        }

                                        $bgCellColor = ($ym == $curYM) ? "#D4EFDF" : "#FFFFFF";
                                        $lineBraker = "<br>";

                                        $link = "cooperation_cal_list2.php?division=6&pdx=4&sub=10&sell=$ym&rand_id=$pid&stm=$ym&flag=$basedate";

                                        $cntText = ($cnt > 0) ? $cnt."건" : "";

                                        $month_td .= " <td bgcolor='$bgCellColor'>
                                            <a href='$link' target='_blank'><font style='font-size:8pt;color:#1f5fbf'>$cntText</font></a>$lineBraker
                                            <a href='$link' target='_blank'><font style='font-size:8pt;color:green'>$creditTot</font></a>$lineBraker
                                            <a href='$link' target='_blank'><font style='font-size:8pt;color:orange'>$debitTot</font></a>$lineBraker
                                            <a href='$link' target='_blank'><font style='font-size:8pt'>".$totBal."</font></a>
                                        </td>";
                                    }

                                    echo "<tr>
                                            <td align='left' style='border:0.05em solid #848484;' bgcolor='$bg'>
                                                <font style='font-size:8pt'>&nbsp;{$zip_row1['userid']}<br/><b>&nbsp;{$zip_row1['kor_name']}</b></font>
                                            </td>
                                            $month_td
                                          </tr>";
                                }
                            ?>
                            </tbody>
                        </table>

                    </div>
                </div>

            </div><!-- -->
        </div>
    </div>
</div>

<?php include "include/side_m.php" ?>

<script>
$(document).ready(function () {
    pt.initReservationList();
    pt.initReservationDetail();

    $('.tourdate1').datepicker({
        format: "yyyy-mm",
        viewMode: "months",
        minViewMode: "months",
        autoclose: true
    });
    $('.tourdate2').datepicker({
        format: "yyyy-mm",
        viewMode: "months",
        minViewMode: "months",
        autoclose: true
    });
});
var ctr=0;
function openwin(stdate,s_code,rcd) {
    var winName = "all_"+(ctr++);
    window.open("guide_assign_customer.php?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>&s_code="+s_code+"&stdate="+stdate+"&rcode="+rcd,winName,"width=1090px,height=700,scrollbars=1");
}
function numberOfDays(month,year) {
    var d = new Date(year, month, 0);
    return d.getDate();
}
function cal(mon) {
    if(mon<10) mon = "0" + mon;
    var st = $("#startyear").val()+"-"+mon+"-"+"01";
    $("#startDate1").val(st);
    var lastday = numberOfDays(mon,$("#startyear").val());
    var ed = $("#startyear").val()+"-"+mon+"-"+lastday;
    $("#endDate").val(ed);
    $("#frmName").submit();
}
</script>
</body>
</html>
