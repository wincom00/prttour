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

    // ── 검색 조건 ────────────────────────────────────────────────
    $term1     = $term1     ?: 'bookday';
    $startDate = $startDate ?: '';
    $endDate   = $endDate   ?: '';

    $from_w = '';
    $to_w   = '';
    $date_col = ($term1 == 'bookday') ? 'a.revDate' : 'a.stDate';

    if ($startDate) $from_w = " AND $date_col >= '$startDate'";
    if ($endDate)   $to_w   = " AND $date_col <= '$endDate'";

    // ── 데이터 수집 ──────────────────────────────────────────────
    $rows          = [];
    $total_pcnt    = 0;
    $total_rescnt  = 0;
    $total_amt     = 0.0;

    if ($startDate && $endDate) {
        $query = "SELECT a.s_area,
                         SUM(a.p_cnt)          AS cnt,
                         SUM(a.last_total)      AS totamt,
                         COUNT(a.reserveCode)   AS totcnt
                  FROM   reserve_info a
                  INNER JOIN code_base b
                         ON a.s_area = CONCAT(b.lvcode1, b.lvcode2, b.lvcode3)
                  WHERE  a.rev_status = 'DONE'
                    AND  a.parent     = 'MAIN'
                    AND  a.s_area    != ''
                    $from_w $to_w
                  GROUP BY a.s_area
                  ORDER BY totamt DESC";

        $rst1 = mysql_query($query, $dbConn);
        while ($row1 = mysql_fetch_assoc($rst1)) {
            $apath         = codebaseName($row1['s_area']);
            $row1['aname'] = $apath['comment'] ?: $row1['s_area'];
            $rows[]        = $row1;
            $total_pcnt   += $row1['cnt'];
            $total_rescnt += $row1['totcnt'];
            $total_amt    += $row1['totamt'];
        }
        // pct 일괄 계산 (두 루프에서 중복 계산 방지)
        foreach ($rows as &$r) {
            $r['pct'] = $total_amt > 0 ? round($r['totamt'] / $total_amt * 100, 1) : 0;
        }
        unset($r);
    }
    $searched = ($startDate && $endDate);
?>

<div id="contentwrapper" class="reservationDetailForm">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="index.php"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">MIS</a></li>
                <li>지사별(지역) 건수</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">

                <!-- ── 검색 폼 ──────────────────────────────────── -->
                <form action="" name="frmName" method="post">
                    <input type="hidden" name="mode" value="search">
                    <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                        <tbody>
                            <tr>
                                <td width="12%" class="text-center formHeader">조회 기간</td>
                                <td>
                                    <input type="text" name="startDate" class="form_box" readonly size="12"
                                           id="date1" value="<?= htmlspecialchars($startDate) ?>">
                                    &nbsp;~&nbsp;
                                    <input type="text" name="endDate" class="form_box" readonly size="12"
                                           id="date2" value="<?= htmlspecialchars($endDate) ?>">
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center formHeader">통계 기준</td>
                                <td>
                                    <label class="radio-inline">
                                        <input type="radio" name="term1" value="bookday"
                                               <?= ($term1=='bookday') ? 'checked' : '' ?>> 예약일 기준
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="term1" value="startday"
                                               <?= ($term1=='startday') ? 'checked' : '' ?>> 출발일 기준
                                    </label>
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

                <?php if ($searched): ?>

                <!-- ── 요약 카드 ─────────────────────────────────── -->
                <div class="row" style="margin-bottom:16px;">
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">지역 수</div>
                            <div style="font-size:22px;font-weight:bold;color:#337ab7;"><?= number_format(count($rows)) ?>개</div>
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
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">총 예약건수</div>
                            <div style="font-size:22px;font-weight:bold;color:#f0ad4e;"><?= number_format($total_rescnt) ?>건</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">총 매출액</div>
                            <div style="font-size:22px;font-weight:bold;color:#d9534f;">$<?= number_format($total_amt, 2) ?></div>
                        </div>
                    </div>
                </div>

                <!-- ── 지역별 비율 막대 ──────────────────────────── -->
                <?php if (count($rows) > 0): ?>
                <div class="row" style="margin-bottom:16px;">
                    <div class="col-sm-12">
                        <table class="table table-bordered table-condensed" style="font-size:12px;">
                            <thead>
                                <tr style="background:#f5f5f5;">
                                    <th width="15%" class="text-center">지역</th>
                                    <th class="text-center">매출 비율</th>
                                    <th width="14%" class="text-right">매출액</th>
                                    <th width="10%" class="text-center">예약인원</th>
                                    <th width="10%" class="text-center">예약건수</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td class="text-center" style="font-weight:bold;">
                                        <i class="fa fa-map-marker" style="color:#337ab7;"></i>
                                        <?= htmlspecialchars($r['aname']) ?>
                                    </td>
                                    <td style="padding:6px 10px;">
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <div style="flex:1;background:#eee;border-radius:3px;height:16px;">
                                                <div style="width:<?= $pct ?>%;background:#2e6da4;height:16px;border-radius:3px;"></div>
                                            </div>
                                            <span style="width:38px;font-size:11px;"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                    <td class="text-right">$<?= number_format($r['totamt'], 2) ?></td>
                                    <td class="text-center"><?= number_format($r['cnt']) ?>명</td>
                                    <td class="text-center"><?= number_format($r['totcnt']) ?>건</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── 상세 테이블 ───────────────────────────────── -->
                <table id="ctable" class="table table-striped table-bordered table-hover table-condensed"
                       style="font-size:13px;">
                    <thead>
                        <tr>
                            <th width="6%"  class="text-center">NO</th>
                            <th width="18%" class="text-center">지역</th>
                            <th width="12%" class="text-center">예약인원</th>
                            <th width="12%" class="text-center">예약건수</th>
                            <th width="18%" class="text-center">
                                매출액<br>
                                <small style="font-weight:normal;color:#aaa;"><?= htmlspecialchars($startDate) ?> ~ <?= htmlspecialchars($endDate) ?></small>
                            </th>
                            <th width="20%" class="text-center">매출 비율</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding:20px;color:#888;">
                                <i class="fa fa-search"></i> 검색된 데이터가 없습니다.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($rows as $k => $r): ?>
                        <tr>
                            <td class="text-center"><?= $k + 1 ?></td>
                            <td>
                                <i class="fa fa-map-marker" style="color:#337ab7;"></i>
                                <?= htmlspecialchars($r['aname']) ?>
                            </td>
                            <td class="text-right"><?= number_format($r['cnt']) ?>명</td>
                            <td class="text-right"><?= number_format($r['totcnt']) ?>건</td>
                            <td class="text-right">$<?= number_format($r['totamt'], 2) ?></td>
                            <td style="padding:5px 10px;">
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div style="flex:1;background:#eee;border-radius:3px;height:14px;">
                                        <div style="width:<?= $pct ?>%;background:#2e6da4;height:14px;border-radius:3px;"></div>
                                    </div>
                                    <span style="width:38px;font-size:11px;"><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <!-- 합계 행 -->
                        <tr style="background:#dff0d8;font-weight:bold;">
                            <td class="text-center">합계</td>
                            <td><?= number_format(count($rows)) ?>개 지역</td>
                            <td class="text-right"><?= number_format($total_pcnt) ?>명</td>
                            <td class="text-right"><?= number_format($total_rescnt) ?>건</td>
                            <td class="text-right" style="color:#c0392b;">$<?= number_format($total_amt, 2) ?></td>
                            <td class="text-center">100%</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php elseif (!$searched): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    조회 기간과 통계 기준을 선택한 후 <strong>검색</strong> 버튼을 클릭하세요.
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
$(document).ready(function () {
    $('#date1').datepicker($.extend({}, pt.defaults.datepicker, { autoclose: true }));
    $('#date2').datepicker($.extend({}, pt.defaults.datepicker, { autoclose: true }));

    <?php if ($searched && count($rows) > 0): ?>
    $('#ctable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', text: '<i class="fa fa-file-excel-o"></i> 엑셀보내기', className: 'btn btn-xs btn-default' },
            { extend: 'print', text: '<i class="fa fa-print"></i> 프린트',           className: 'btn btn-xs btn-default' }
        ],
        order:     [[4, 'desc']],
        bPaginate: false
    });
    <?php endif; ?>
});
</script>
</body>
</html>
