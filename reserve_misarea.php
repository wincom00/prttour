<?php
    include "include/header.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
    } else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }
    if (!hasMenuAccess($division, $pdx, $sub)) {
        Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
        echo "<meta http-equiv='refresh' content='0; url=index.php'>";
        exit;
    }

    // ── 기본 날짜: 최근 7일 ──────────────────────────────────────
    if ($StartYMD) {
        $start_date    = "$StartYMD 00:00:00";
        $stop_date     = "$EndYMD 23:59:59";
        if ($seldate == '1') {
            $orderdate_qry = " AND a.revDate BETWEEN '$start_date' AND '$stop_date'";
        } else {
            $orderdate_qry = " AND a.stDate  BETWEEN '$start_date' AND '$stop_date'";
        }
    } else {
        $StartYMD = date("Y-m-d", mktime(0,0,0, date("m"), date("d")-7, date("Y")));
        $EndYMD   = date("Y-m-d");
        $seldate  = '1';
        $orderdate_qry = "";
    }

    // ── 초기화 ────────────────────────────────────────────────────
    $content        = '';
    $num            = 1;
    $total_rows     = 0;
    $total_pcnt     = 0;
    $total_amt      = 0.0;
    $total_balance  = 0.0;
    $area_summary   = [];   // [area_code => [cnt, pcnt, amt, bal, area_name]]
    $all_rows       = [];

    if ($Mode == "SEARCH") {

        if ($seldate == '1') {
            $date_col   = "a.revDate";
            $date_label = "예약일";
        } else {
            $date_col   = "a.stDate";
            $date_label = "출발일";
        }

        $qry1 = "SELECT a.s_area,
                        a.r_path        as typp,
                        $date_col       as wdate,
                        a.tour_type,
                        a.p_name,
                        a.p_code,
                        SUM(a.p_cnt)      as pcnt,
                        SUM(a.last_total) as last_total_amt,
                        SUM(a.last_bal)   as balance
                 FROM   reserve_info a
                 INNER JOIN product_master b ON a.p_code = b.p_code
                 WHERE  a.p_code NOT LIKE '%PICKUP%'
                   AND  a.p_code NOT LIKE '%SNEDING%'
                   AND  a.parent = 'MAIN'
                   AND  a.rev_status NOT IN ('READY','CANCEL')
                   $orderdate_qry
                 GROUP BY a.s_area, $date_col, a.p_name
                 ORDER BY a.s_area, $date_col DESC";

        $rst1 = mysql_query($qry1, $dbConn);

        // ── 결과 배열로 수집 ──────────────────────────────────────
        while ($row1 = mysql_fetch_assoc($rst1)) {
            $acode = $row1['s_area'];
            $apath = codebaseName($acode);
            $aname = $apath['comment'] ? $apath['comment'] : $acode;
            $row1['aname'] = $aname;
            $all_rows[] = $row1;

            if (!isset($area_summary[$acode])) {
                $area_summary[$acode] = ['cnt'=>0, 'pcnt'=>0, 'amt'=>0.0, 'bal'=>0.0, 'name'=>$aname];
            }
            $area_summary[$acode]['cnt']++;
            $area_summary[$acode]['pcnt'] += $row1['pcnt'];
            $area_summary[$acode]['amt']  += $row1['last_total_amt'];
            $area_summary[$acode]['bal']  += $row1['balance'];

            $total_rows    += 1;
            $total_pcnt    += $row1['pcnt'];
            $total_amt     += $row1['last_total_amt'];
            $total_balance += $row1['balance'];
        }

        // ── 상세 테이블 HTML 빌드 ─────────────────────────────────
        $cur_area = null;
        foreach ($all_rows as $row1) {
            $acode = $row1['s_area'];
            $aname = $row1['aname'];

            // ── 지역 그룹 헤더 ────────────────────────────────────
            if ($cur_area !== $acode) {
                if ($cur_area !== null && isset($area_summary[$cur_area])) {
                    $s = $area_summary[$cur_area];
                    $content .= "<tr style='background:#fff8e1;'>
                        <td colspan='5' align='right' style='font-weight:bold;padding:5px 10px;'>
                            {$area_summary[$cur_area]['name']} 소계
                        </td>
                        <td align='right' style='font-weight:bold;padding:5px 10px;'>".number_format($s['pcnt'])."명</td>
                        <td align='right' style='font-weight:bold;padding:5px 10px;'>$".number_format($s['amt'],2)."</td>
                        <td align='right' style='font-weight:bold;padding:5px 10px;color:#c0392b;'>$".number_format($s['bal'],2)."</td>
                    </tr>";
                }
                $content .= "<tr style='background:#eaf2fb;'>
                    <td colspan='8' style='padding:6px 12px;font-weight:bold;color:#1a3a5c;'>
                        <i class='fa fa-map-marker'></i>&nbsp; ".htmlspecialchars($aname)."
                    </td>
                </tr>";
                $cur_area = $acode;
            }

            // ── 예약 타입 ─────────────────────────────────────────
            if ($row1['tour_type'] == 1)     $trtype = '직접예약';
            elseif ($row1['tour_type'] == 2) $trtype = '인터넷예약';
            elseif ($row1['tour_type'] == 4) $trtype = '업체예약';
            else                             $trtype = '-';

            $path = codebaseName($row1['typp']);

            $content .= "<tr>
                <td align='center'>".htmlspecialchars($aname)."</td>
                <td align='center'>{$trtype}</td>
                <td align='center'>".htmlspecialchars($path['comment'])."</td>
                <td align='center'>{$row1['wdate']}</td>
                <td align='left'>".$row1['p_name']."</td>
                <td align='right'>".number_format($row1['pcnt'])."</td>
                <td align='right'>$".number_format($row1['last_total_amt'],2)."</td>
                <td align='right' style='color:#c0392b;'>$".number_format($row1['balance'],2)."</td>
            </tr>";
        }

        // ── 마지막 지역 소계 ──────────────────────────────────────
        if ($cur_area !== null && isset($area_summary[$cur_area])) {
            $s = $area_summary[$cur_area];
            $content .= "<tr style='background:#fff8e1;'>
                <td colspan='5' align='right' style='font-weight:bold;padding:5px 10px;'>
                    {$area_summary[$cur_area]['name']} 소계
                </td>
                <td align='right' style='font-weight:bold;padding:5px 10px;'>".number_format($s['pcnt'])."명</td>
                <td align='right' style='font-weight:bold;padding:5px 10px;'>$".number_format($s['amt'],2)."</td>
                <td align='right' style='font-weight:bold;padding:5px 10px;color:#c0392b;'>$".number_format($s['bal'],2)."</td>
            </tr>";
        }

        if ($total_rows == 0) {
            $content = "<tr><td colspan='8' class='text-center' style='padding:20px;color:#888;'>
                <i class='fa fa-search'></i> 검색된 예약이 없습니다.
            </td></tr>";
        } else {
            $content .= "<tr style='background:#dff0d8;font-weight:bold;'>
                <td colspan='5' align='right' style='padding:7px 10px;'>총 합계</td>
                <td align='right' style='padding:7px 10px;'>".number_format($total_pcnt)."명</td>
                <td align='right' style='padding:7px 10px;'>$".number_format($total_amt,2)."</td>
                <td align='right' style='padding:7px 10px;color:#c0392b;'>$".number_format($total_balance,2)."</td>
            </tr>";
        }
    }
?>

<div id="contentwrapper" class="reservationDetailForm">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="index.php"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">MIS</a></li>
                <li>지역별 예약 매출</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">

                <!-- ── 검색 폼 ──────────────────────────────────── -->
                <form action="<?= $_SERVER['PHP_SELF'] ?>?division=<?=$division?>&pdx=<?=$pdx?>&sub=<?=$sub?>" method="post">
                    <input type="hidden" name="Mode" value="SEARCH">
                    <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                        <tbody>
                            <tr>
                                <td width="15%" class="text-center formHeader">
                                    <select class="form-control" name="seldate">
                                        <option value="1" <?= ($seldate=='1') ? 'selected' : '' ?>>예약일</option>
                                        <option value="2" <?= ($seldate=='2') ? 'selected' : '' ?>>출발일</option>
                                    </select>
                                </td>
                                <td width="85%">
                                    &nbsp;<input name="StartYMD" type="text" class="form_box" readonly size="12" id="date1" value="<?= $StartYMD ?>">
                                    &nbsp;~&nbsp;
                                    <input name="EndYMD" type="text" class="form_box" readonly size="12" id="date2" value="<?= $EndYMD ?>">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-center">
                                    <button type="submit" class="btn btn-primary btn-sm btn1">
                                        <i class="fa fa-search"></i> 검색
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>

                <?php if ($Mode == "SEARCH"): ?>

                <!-- ── 요약 카드 ─────────────────────────────────── -->
                <div class="row" style="margin-bottom:16px;">
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">총 건수</div>
                            <div style="font-size:22px;font-weight:bold;color:#337ab7;"><?= number_format($total_rows) ?></div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">총 예약인원</div>
                            <div style="font-size:22px;font-weight:bold;color:#5cb85c;"><?= number_format($total_pcnt) ?>명</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">총 예약금액</div>
                            <div style="font-size:22px;font-weight:bold;color:#d9534f;">$<?= number_format($total_amt,2) ?></div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">잔액 (발란스)</div>
                            <div style="font-size:22px;font-weight:bold;color:#f0ad4e;">$<?= number_format($total_balance,2) ?></div>
                        </div>
                    </div>
                </div>

                <!-- ── 지역별 집계 ───────────────────────────────── -->
                <?php if ($total_rows > 0): ?>
                <div class="row" style="margin-bottom:16px;">
                    <div class="col-sm-12">
                        <table class="table table-bordered table-condensed" style="font-size:13px;">
                            <thead>
                                <tr style="background:#f5f5f5;">
                                    <th class="text-center">지역</th>
                                    <th class="text-center">건수</th>
                                    <th class="text-center">예약인원</th>
                                    <th class="text-center">예약금액</th>
                                    <th class="text-center">잔액</th>
                                    <th class="text-center">금액비율</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($area_summary as $acode => $s): ?>
                                <?php $ratio = $total_amt > 0 ? round($s['amt'] / $total_amt * 100, 1) : 0; ?>
                                <tr>
                                    <td class="text-center" style="font-weight:bold;">
                                        <i class="fa fa-map-marker" style="color:#337ab7;"></i>
                                        <?= htmlspecialchars($s['name']) ?>
                                    </td>
                                    <td class="text-center"><?= number_format($s['cnt']) ?>건</td>
                                    <td class="text-center"><?= number_format($s['pcnt']) ?>명</td>
                                    <td class="text-right">$<?= number_format($s['amt'],2) ?></td>
                                    <td class="text-right" style="color:#c0392b;">$<?= number_format($s['bal'],2) ?></td>
                                    <td class="text-center">
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <div style="flex:1;background:#eee;border-radius:3px;height:14px;">
                                                <div style="width:<?=$ratio?>%;background:#337ab7;height:14px;border-radius:3px;"></div>
                                            </div>
                                            <span style="font-size:11px;width:36px;"><?=$ratio?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── 상세 테이블 ───────────────────────────────── -->
                <div style="margin-bottom:8px;display:flex;justify-content:flex-end;gap:6px;">
                    <button onclick="printTable()" class="btn btn-xs btn-default">
                        <i class="fa fa-print"></i> 프린트
                    </button>
                    <button onclick="exportExcel()" class="btn btn-xs btn-default">
                        <i class="fa fa-file-excel-o"></i> 엑셀
                    </button>
                </div>
                <table id="rvtab" class="table table-striped table-bordered table-condensed" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th width="10%" class="text-center">지역</th>
                            <th width="9%"  class="text-center">예약타입</th>
                            <th width="9%"  class="text-center">예약경로</th>
                            <th width="10%" class="text-center"><?= ($seldate=='1') ? '예약일' : '출발일' ?></th>
                            <th width="30%" class="text-center">예약상품</th>
                            <th width="8%"  class="text-center">예약인원</th>
                            <th width="12%" class="text-center">예약금액</th>
                            <th width="10%" class="text-center">잔액</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= $content ?>
                    </tbody>
                </table>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
$(document).ready(function () {
    $.ajaxSetup({async: false});
    $('#date1').datepicker($.extend({}, pt.defaults.datepicker, { autoclose: true }));
    $('#date2').datepicker($.extend({}, pt.defaults.datepicker, { autoclose: true }));
});

function printTable() {
    var printWin = window.open('', '_blank');
    var html = '<html><head><title>지역별 예약 매출</title>'
             + '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">'
             + '<style>body{font-size:12px;font-family:"Malgun Gothic",sans-serif;}th,td{padding:4px 6px!important;}</style>'
             + '</head><body>'
             + '<h4 style="margin:10px 0;">지역별 예약 매출 (<?= $StartYMD ?> ~ <?= $EndYMD ?>)</h4>'
             + document.getElementById('rvtab').outerHTML
             + '</body></html>';
    printWin.document.write(html);
    printWin.document.close();
    printWin.print();
}

function exportExcel() {
    var table = document.getElementById('rvtab');
    var wb = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">'
           + '<head><meta charset="UTF-8"></head><body>' + table.outerHTML + '</body></html>';
    var blob = new Blob([wb], {type: 'application/vnd.ms-excel'});
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    a.href   = url;
    a.download = '지역별예약매출_<?= $StartYMD ?>_<?= $EndYMD ?>.xls';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>
