<?php
    include "include/header.php";
    if (!empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    } else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }
    if (!hasMenuAccess($division, $pdx, $sub)) {
        Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
        echo "<meta http-equiv='refresh' content='0; url=index.php'>";
        exit;
    }

    // ── 표시 단위 결정 ────────────────────────────────────────────
    // seldate: 1=출발일(stDate), 2=판매일(revDate)
    // typer:   1=매출액,          2=인원수

    // ── 연도 선택 목록 ───────────────────────────────────────────
    // 예전에는 연도 datepicker(년 단위 팝업)를 썼는데, 팝업이 화면 위쪽으로 잘려
    // 과거 연도 칸이 가려지고 선택 자체가 안 됐다. 연도 select 로 바꿔 전부 노출한다.
    // 선택한 기준(출발일=stDate / 판매일=revDate)의 실제 최소 연도를 하한으로 쓴다
    $yearFrom  = 2020;                      // 조회 실패 시 사용할 하한
    $yearTo    = (int)date('Y') + 1;        // 내년 예약까지 조회 가능
    $year_base = ($seldate == '2') ? 'revDate' : 'stDate';
    $minRow = mysql_fetch_assoc(mysql_query(
        "SELECT MIN(YEAR($year_base)) AS y FROM reserve_info
          WHERE $year_base > '1990-01-01'", $dbConn));
    if (!empty($minRow['y']) && (int)$minRow['y'] > 1990) {
        $yearFrom = (int)$minRow['y'];
    }

    // 처음 들어오면 올해 기준으로 바로 보여준다 (예전에는 연도를 고르기 전까지 화면이 비어 있었다)
    if ($StartYMD == '') { $StartYMD = date('Y'); }
    $StartYMD = preg_replace('/[^0-9]/', '', $StartYMD);   // 쿼리에 그대로 들어가므로 숫자만 남긴다
    if ($StartYMD == '') { $StartYMD = date('Y'); }

    // ── printAmt(): 쿼리 실행 + 테이블 HTML echo ─────────────────
    function printAmt() {
        global $dbConn, $division, $pdx, $sub,
               $seldate, $StartYMD, $typer,
               $pm1,$pm2,$pm3,$pm4,$pm5,$pm6,
               $pm7,$pm8,$pm9,$pm10,$pm11,$pm12,$ptotamt;

        // 기준 날짜 컬럼
        //   출발일 = b.stDate  /  판매일 = b.revDate   (b.wdate 는 레코드 기록일이라 기준으로 쓰지 않는다)
        //   예전에는 출발일을 골라도 revDate(판매일)로, 판매일을 고르면 wdate(기록일)로 걸러졌고
        //   월 구분은 어느 쪽이든 항상 revDate 여서 기준 선택이 사실상 동작하지 않았다.
        $date_col = ($seldate == '2') ? 'b.revDate' : 'b.stDate';

        // 연도 필터
        $qrysdate = " AND YEAR($date_col) = '$StartYMD'";

        // 집계 컬럼 선택 (매출액 vs 인원수)
        $val_col = ($typer == 2) ? 'b.p_cnt' : 'b.last_total';

        // ── CASE WHEN pivot 쿼리 ──────────────────────────────────
        $cases = '';
        for ($m = 1; $m <= 12; $m++) {
            $cases .= "SUM(CASE WHEN MONTH($date_col) = $m $qrysdate
                                THEN $val_col ELSE 0 END) AS tt$m,\n";
        }
        $cases = rtrim($cases, ",\n");

        $qry1 = "SELECT a.p_day, b.p_code, b.p_name,
                        $cases
                 FROM   product_master a
                 INNER JOIN reserve_info b ON a.p_code = b.p_code
                 WHERE  b.rev_status = 'DONE'
                   AND  a.p_code NOT LIKE '%PICKUP%'
                   AND  a.p_code NOT LIKE '%SENDING%'
                   AND  b.parent = 'MAIN'
                   $qrysdate
                 GROUP BY a.p_day, b.p_code
                 ORDER BY a.p_day ASC";

        $rst1 = mysql_query($qry1, $dbConn);

        $content = '';
        $is_amt  = ($typer != 2);
        $fmt     = function($v) use ($is_amt) {
            return $is_amt ? '$'.number_format((float)$v, 2) : number_format((float)$v);
        };
        $fmt_tot = function($v) use ($is_amt) {
            return $is_amt ? number_format((float)$v, 2) : number_format((float)$v);
        };

        // 월별 누계 배열
        $month_tot = array_fill(1, 12, 0.0);
        $grand_tot = 0.0;

        while ($row1 = mysql_fetch_assoc($rst1)) {
            $p_day  = ($row1['p_day'] == 1) ? '당일' : $row1['p_day'].'일';
            $p_name = $row1['p_name'];

            // 월별 값 + 행합계
            $row_vals  = [];
            $row_total = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $v            = (float)$row1['tt'.$m];
                $row_vals[$m] = $v;
                $row_total   += $v;
                $month_tot[$m] += $v;
            }
            $grand_tot += $row_total;

            $tds = '';
            foreach ($row_vals as $v) {
                $tds .= "<td class='text-right'>".($v == 0 ? '<span style="color:#ccc;">-</span>' : $fmt($v))."</td>";
            }

            $content .= "<tr>
                <td class='text-center'>{$p_day}</td>
                <td>{$p_name}</td>
                {$tds}
                <td class='text-right' style='font-weight:bold;'>{$fmt($row_total)}</td>
            </tr>";
        }

        // ── 총합계 행 ─────────────────────────────────────────────
        if ($grand_tot > 0) {
            $tot_tds = '';
            for ($m = 1; $m <= 12; $m++) {
                $tot_tds .= "<td class='text-right' style='color:#1a5276;font-weight:bold;'>"
                          . ($is_amt ? '$' : '') . $fmt_tot($month_tot[$m])
                          . "</td>";
            }
            $content .= "<tr style='background:#eaf2fb;'>
                <td></td>
                <td style='font-weight:bold;'>총합계</td>
                {$tot_tds}
                <td class='text-right' style='font-weight:bold;color:#c0392b;'>"
                . ($is_amt ? '$' : '') . $fmt_tot($grand_tot)
                . "</td>
            </tr>";
        }

        if (!$grand_tot) {
            $content = "<tr><td colspan='15' class='text-center' style='padding:20px;color:#888;'>
                <i class='fa fa-search'></i> 검색된 데이터가 없습니다.
            </td></tr>";
        }

        // 외부 변수 업데이트 (요약 카드용)
        $pm1=$month_tot[1];  $pm2=$month_tot[2];  $pm3=$month_tot[3];
        $pm4=$month_tot[4];  $pm5=$month_tot[5];  $pm6=$month_tot[6];
        $pm7=$month_tot[7];  $pm8=$month_tot[8];  $pm9=$month_tot[9];
        $pm10=$month_tot[10]; $pm11=$month_tot[11]; $pm12=$month_tot[12];
        $ptotamt = $grand_tot;

        echo $content;
    }
?>

<div id="contentwrapper" class="reservationDetailForm">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="index.php"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">MIS</a></li>
                <li>월별/상품별 매출</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">

                <!-- ── 검색 폼 ──────────────────────────────────── -->
                <form action="" method="post" name="frmName">
                    <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                        <tbody>
                            <tr>
                                <td width="15%" class="text-center formHeader">
                                    <select class="form-control" name="seldate">
                                        <option value="1" <?= ($seldate=='1'||!$seldate) ? 'selected' : '' ?>>출발일</option>
                                        <option value="2" <?= ($seldate=='2') ? 'selected' : '' ?>>판매일</option>
                                    </select>
                                </td>
                                <td width="15%" class="text-center formHeader">
                                    <select class="form-control" name="typer">
                                        <option value="1" <?= ($typer=='1'||!$typer) ? 'selected' : '' ?>>매출액</option>
                                        <option value="2" <?= ($typer=='2') ? 'selected' : '' ?>>인원수</option>
                                    </select>
                                </td>
                                <td width="15%">
                                    <select class="form-control" name="StartYMD">
                                        <?php for ($y = $yearTo; $y >= $yearFrom; $y--): ?>
                                        <option value="<?= $y ?>" <?= ($StartYMD == $y) ? 'selected' : '' ?>><?= $y ?>년</option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td class="text-left">
                                    <button type="submit" class="btn btn-primary btn-sm btn1">
                                        <i class="fa fa-search"></i> 검색
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>

                <?php if ($StartYMD): ?>

                <!-- ── 테이블 + 요약 카드 ────────────────────────── -->
                <?php ob_start(); ?>
                <div class="row">
                    <div class="col-sm-12">
                        <table id="rvtab" class="table table-striped table-bordered table-hover table-condensed">
                            <thead>
                                <tr>
                                    <th class="text-center" style="white-space:nowrap;">일차</th>
                                    <th style="min-width:140px;">투어명</th>
                                    <th class="text-center">1월</th>
                                    <th class="text-center">2월</th>
                                    <th class="text-center">3월</th>
                                    <th class="text-center">4월</th>
                                    <th class="text-center">5월</th>
                                    <th class="text-center">6월</th>
                                    <th class="text-center">7월</th>
                                    <th class="text-center">8월</th>
                                    <th class="text-center">9월</th>
                                    <th class="text-center">10월</th>
                                    <th class="text-center">11월</th>
                                    <th class="text-center">12월</th>
                                    <th class="text-center" style="white-space:nowrap;">연간합계</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php printAmt(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                $_tableHtml = ob_get_clean();

                // 요약 카드 계산
                $_is_amt = ($typer != 2);
                $_unit   = $_is_amt ? '$' : '';
                $_dec    = $_is_amt ? 2 : 0;
                $_half1  = $pm1+$pm2+$pm3+$pm4+$pm5+$pm6;
                $_half2  = $pm7+$pm8+$pm9+$pm10+$pm11+$pm12;
                $_months = [$pm1,$pm2,$pm3,$pm4,$pm5,$pm6,$pm7,$pm8,$pm9,$pm10,$pm11,$pm12];
                $_maxVal = max($_months);
                $_maxIdx = $_maxVal > 0 ? array_search($_maxVal, $_months) + 1 : '-';
                ?>

                <!-- ── 요약 카드 ─────────────────────────────────── -->
                <div class="row" style="margin-bottom:16px;">
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">연간 합계 (<?= $StartYMD ?>)</div>
                            <div style="font-size:22px;font-weight:bold;color:#d9534f;"><?= $_unit.number_format($ptotamt, $_dec) ?></div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">상반기 (1~6월)</div>
                            <div style="font-size:22px;font-weight:bold;color:#337ab7;"><?= $_unit.number_format($_half1, $_dec) ?></div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">하반기 (7~12월)</div>
                            <div style="font-size:22px;font-weight:bold;color:#5bc0de;"><?= $_unit.number_format($_half2, $_dec) ?></div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-3">
                        <div style="border:1px solid #ddd;border-radius:6px;padding:12px 16px;background:#fff;text-align:center;">
                            <div style="font-size:12px;color:#888;margin-bottom:4px;">최고 월</div>
                            <div style="font-size:22px;font-weight:bold;color:#5cb85c;">
                                <?= is_numeric($_maxIdx) ? $_maxIdx.'월' : '-' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── 월별 비율 막대 ────────────────────────────── -->
                <?php if ($ptotamt > 0): ?>
                <div class="row" style="margin-bottom:16px;">
                    <div class="col-sm-12">
                        <table class="table table-bordered table-condensed" style="font-size:12px;">
                            <thead>
                                <tr style="background:#f5f5f5;">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <th class="text-center"><?= $m ?>월</th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php
                                    $max_m = max($_months) ?: 1;
                                    foreach ($_months as $mi => $mv):
                                        $pct    = round($mv / $ptotamt * 100, 1);
                                        $bar_w  = round($mv / $max_m * 100);
                                        $is_max = ($mi + 1 == $_maxIdx);
                                    ?>
                                    <td class="text-center" style="padding:4px 6px;">
                                        <div style="background:#<?= $is_max ? '27ae60' : '337ab7' ?>;
                                                    height:<?= $bar_w ?>px;min-height:2px;border-radius:2px 2px 0 0;
                                                    max-height:60px;margin:0 auto 2px;width:70%;"></div>
                                        <div style="font-size:11px;color:#555;"><?= $pct ?>%</div>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── 상세 테이블 ───────────────────────────────── -->
                <?= $_tableHtml ?>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
$(document).ready(function () {
    // 연도는 select 로 고른다 (datepicker 팝업이 잘려 과거 연도를 못 고르던 문제)

    $.ajaxSetup({async: false});
    // 헤더 고정: FixedHeader 확장은 scrollX 와 같이 쓸 수 없다(헤더가 별도 div 로 분리됨).
    // 대신 scrollY 를 주면 표 자체가 세로 스크롤 영역이 되어 헤더가 항상 위에 남는다.
    // (guide_assign_current_state.php 에서 쓰는 것과 같은 방식)
    $('#rvtab').dataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', text: '<i class="fa fa-file-excel-o"></i> 엑셀보내기', className: 'btn btn-xs btn-default' },
            { extend: 'print', text: '<i class="fa fa-print"></i> 프린트',           className: 'btn btn-xs btn-default' }
        ],
        stateSave:      true,
        bPaginate:      false,
        ordering:       false,
        scrollX:        true,
        scrollY:        '55vh',
        scrollCollapse: true    // 행이 적으면 높이를 내용만큼만 쓴다
    });
});
</script>
</body>
</html>
