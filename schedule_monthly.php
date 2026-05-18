<?php
    // [Excel Export Logic] - 헤더 출력 전에 처리해야 하므로 최상단에 위치합니다.
    $mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : "";
    
    if ($mode == "excel") {
        // DB 연결 (헤더를 include 하지 않으므로 별도 연결 필요)
        require_once $_SERVER['DOCUMENT_ROOT'] . '/include/dbconn.php';
        
        $StartYMD = $_REQUEST['StartYMD'];
        $a_area   = $_REQUEST['a_area'];
        
        // 파일명 생성
        $filename = $StartYMD . "_여행사별통계_" . date("Ymd") . ".xls";
        
        // 엑셀 헤더 설정
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Description: PHP4 Generated Data");
        
        // 엑셀 내부 스타일 정의
        echo "
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
        <style>
            table { border-collapse:collapse; }
            td, th { border: 1px solid #000; padding: 5px; mso-number-format:'\@'; }
            .header { background-color: #f0f8ff; font-weight: bold; text-align: center; }
            .total { background-color: #fffbf0; font-weight: bold; }
            .main-total { background-color: #009999; color: white; font-weight: bold; }
            .sub-title { font-size: 14px; font-weight: bold; height: 30px; border:none; }
        </style>
        ";
        
        if ($StartYMD) {
            $year = $StartYMD;
        } else {
            $year = date("Y");
        }
        $year_first = date("Y-m-d", mktime(0, 0, 0, "1", "1", $year));
        $year_last  = date("Y-m-d", mktime(0, 0, 0, "12", "31", $year));
        
        if ($a_area) {
            $sarea = substr($a_area, 0, 5);

            // 1. 에이전트 목록 (reserve_info의 rand_id 기준) [cite: 142, 143, 75]
            $qry_agent = "SELECT a.rand_id as part_id, c.kor_name 
                          FROM reserve_info a
                          LEFT JOIN member_list c ON a.rand_id = c.userid
                          WHERE SUBSTRING(c.company_area, 1, 5) = '$sarea'
                          AND a.parent = 'MAIN'
                          AND a.rev_status IN ('DONE')
                          AND a.stDate BETWEEN '$year_first' AND '$year_last'
                          GROUP BY a.rand_id ORDER BY c.kor_name ASC";
            $rst_agent = mysql_query($qry_agent, $dbConn);
            $agents = array();
            while ($row = mysql_fetch_assoc($rst_agent)) $agents[] = $row;

            // 2. 월별 통계 (rand_id 기준 합산) [cite: 142, 144, 145]
            $qry_stats = "SELECT a.rand_id as part_id, 
                                 DATE_FORMAT(a.stDate, '%c') as mon, 
                                 SUM(a.p_cnt) as tot_cnt, 
                                 SUM(a.last_total) as tot_amt
                          FROM reserve_info a
                          JOIN member_list c ON a.rand_id = c.userid
                          WHERE SUBSTRING(c.company_area, 1, 5) = '$sarea'
                          AND a.parent = 'MAIN'
                          AND a.rev_status IN ('DONE')
                          AND a.stDate BETWEEN '$year_first' AND '$year_last'
                          GROUP BY a.rand_id, mon";
            $rst_stats = mysql_query($qry_stats, $dbConn);
            
            $stat_data = array();
            $agent_totals = array(); 
            while ($row = mysql_fetch_assoc($rst_stats)) {
                $stat_data[$row['part_id']][$row['mon']] = array('cnt' => $row['tot_cnt'], 'amt' => $row['tot_amt']);
                if(!isset($agent_totals[$row['part_id']])) $agent_totals[$row['part_id']] = array('cnt'=>0, 'amt'=>0);
                $agent_totals[$row['part_id']]['cnt'] += $row['tot_cnt'];
                $agent_totals[$row['part_id']]['amt'] += $row['tot_amt'];
            }
            
            // 3. 전체 상세 데이터 (상품별 상세) 
            $qry_all_detail = "SELECT a.rand_id as part_id, c.kor_name, 
                                      DATE_FORMAT(a.stDate, '%m') as mon, 
                                      a.p_name, 
                                      COUNT(a.reserveCode) as rev_cnt, 
                                      SUM(a.p_cnt) as pax_cnt
                               FROM reserve_info a
                               JOIN member_list c ON a.rand_id = c.userid
                               WHERE SUBSTRING(c.company_area, 1, 5) = '$sarea'
                               AND a.stDate BETWEEN '$year_first' AND '$year_last'
                               AND a.rev_status IN ('DONE')
                               AND a.parent = 'MAIN'
                               GROUP BY a.rand_id, a.p_name, mon
                               ORDER BY c.kor_name ASC, mon ASC, a.p_name ASC";
            $rst_all_detail = mysql_query($qry_all_detail, $dbConn);
            $agent_details = array();
            while ($row = mysql_fetch_assoc($rst_all_detail)) {
                $agent_details[$row['part_id']][] = $row;
            }

            // --- 엑셀 출력 테이블 ---
            echo "<table>";
            echo "<tr><td colspan='" . (count($agents) + 1) . "' class='sub-title' style='font-size:16px; font-weight:bold; text-align:center;'>여행사별 월별 예약 통계 ($year)</td></tr>";
            echo "<tr><th class='header' style='background-color:#f0f0f0;'>월 / 거래처</th>";
            foreach ($agents as $agent) {
                echo "<th class='header' style='background-color:#f0f0f0;'>" . $agent['kor_name'] . "</th>";
            }
            echo "</tr>";
            
            echo "<tr><td class='main-total' style='background-color:#009999; color:white;'>연간 합계</td>";
            foreach ($agents as $agent) {
                $t_cnt = isset($agent_totals[$agent['part_id']]) ? $agent_totals[$agent['part_id']]['cnt'] : 0;
                $t_amt = isset($agent_totals[$agent['part_id']]) ? $agent_totals[$agent['part_id']]['amt'] : 0;
                echo "<td class='main-total' style='background-color:#009999; color:white;'>" . number_format($t_cnt) . "<br>($" . number_format($t_amt, 2) . ")</td>";
            }
            echo "</tr>";

            for ($i = 1; $i <= 12; $i++) {
                echo "<tr><td style='text-align:center;'>{$i}월</td>";
                foreach ($agents as $agent) {
                    $curr_cnt = isset($stat_data[$agent['part_id']][$i]) ? $stat_data[$agent['part_id']][$i]['cnt'] : 0;
                    $curr_amt = isset($stat_data[$agent['part_id']][$i]) ? $stat_data[$agent['part_id']][$i]['amt'] : 0;
                    $display = ($curr_cnt > 0) ? number_format($curr_cnt) . "<br>($" . number_format($curr_amt, 2) . ")" : "";
                    echo "<td style='text-align:center;'>" . $display . "</td>";
                }
                echo "</tr>";
            }
            echo "</table><br><br>";

            // 상세 내역 출력
            foreach ($agents as $agent) {
                $current_id = $agent['part_id'];
                if (isset($agent_details[$current_id]) && count($agent_details[$current_id]) > 0) {
                    echo "<table>";
                    echo "<tr><td colspan='4' class='sub-title' style='border:none; font-size:14px; font-weight:bold;'>[" . $agent['kor_name'] . "] 상품별 판매 상세 내역</td></tr>";
                    echo "<tr><th class='header'>월 (Month)</th><th class='header'>상품명</th><th class='header'>예약 건수</th><th class='header'>총 인원(명)</th></tr>";
                    $sum_rev = 0; $sum_pax = 0;
                    foreach ($agent_details[$current_id] as $d_row) {
                        $sum_rev += $d_row['rev_cnt']; $sum_pax += $d_row['pax_cnt'];
                        echo "<tr><td style='text-align:center;'>".$d_row['mon']."월</td><td>".$d_row['p_name']."</td><td style='text-align:center;'>".number_format($d_row['rev_cnt'])."</td><td style='text-align:center;'>".number_format($d_row['pax_cnt'])."</td></tr>";
                    }
                    echo "<tr><td colspan='2' class='total' style='text-align:center;'>합 계</td><td class='total' style='text-align:center;'>".number_format($sum_rev)."</td><td class='total' style='text-align:center;'>".number_format($sum_pax)."</td></tr>";
                    echo "</table><br>";
                }
            }
        }
        exit;
    }

    include "include/header.php";

    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }

    if ($StartYMD) {
        $year = $StartYMD;
    } else {
        $year = date("Y");
        $StartYMD = date("Y");
    }

    $year_first = date("Y-m-d", mktime(0, 0, 0, 1, 1, $year));
    $year_last  = date("Y-m-d", mktime(0, 0, 0, 12, 31, $year));
?>

<div id="contentwrapper" class="reservationDetailForm">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">MIS</a></li>
                <li>여행사별 월별 예약통계 (rand_id)</li>
            </ul>
        </div>
        
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form name="searchForm" action="<?= $PHP_SELF ?>?division=5&pdx=1&sub=25" method="post">
                    <input type="hidden" name="mode" value="save">
                    <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                        <tbody>
                            <tr>
                                <td width="10%" class="titletd text-center">기준년도</td>
                                <td width="30%">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <input type="text" id="StartYMD" name="StartYMD" class="inpubase tourDate1" placeholder="시작일" value="<?=$StartYMD?>" autocomplete="off" />
                                        </div>
                                    </div>
                                </td>
                                <td width="15%" class="titletd text-center">지역선택</td>
                                <td width="20%" bgcolor="#FFFFFF">
                                    <select name="a_area" class="form-control">
                                        <option value="">==선택==</option>
                                        <?php echo printBaseCode_first1("A01", $a_area); ?>
                                    </select>
                                </td>
                                <td width="25%" class="text-center">
                                    <button type="submit" class="btn btn-primary btn-sm btn1">검색</button>
                                    <button type="button" class="btn btn-success btn-sm" onclick="fn_excel()">Excel Down</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
                <br>

                <?php if ($mode == "save" && $a_area) { 
                    $sarea = substr($a_area, 0, 5);

                    // 1. 거래처 목록 (rand_id 기준) 
                    $qry_agent = "SELECT a.rand_id as part_id, c.kor_name 
                                  FROM reserve_info a
                                  LEFT JOIN member_list c ON a.rand_id = c.userid
                                  WHERE SUBSTRING(c.company_area, 1, 5) = '$sarea'
                                  AND a.parent = 'MAIN'
                                  AND a.rev_status IN ('DONE')
                                  AND a.stDate BETWEEN '$year_first' AND '$year_last'
                                  GROUP BY a.rand_id ORDER BY c.kor_name ASC";
                    $rst_agent = mysql_query($qry_agent, $dbConn);
                    $agents = array();
                    while ($row = mysql_fetch_assoc($rst_agent)) $agents[] = $row;

                    // 2. 통계 데이터 (rand_id 기준) [cite: 144]
                    $qry_stats = "SELECT a.rand_id as part_id, 
                                         DATE_FORMAT(a.stDate, '%c') as mon, 
                                         SUM(a.p_cnt) as tot_cnt, 
                                         SUM(a.last_total) as tot_amt
                                  FROM reserve_info a
                                  JOIN member_list c ON a.rand_id = c.userid
                                  WHERE SUBSTRING(c.company_area, 1, 5) = '$sarea'
                                  AND a.parent = 'MAIN'
                                  AND a.rev_status IN ('DONE')
                                  AND a.stDate BETWEEN '$year_first' AND '$year_last'
                                  GROUP BY a.rand_id, mon";
                    $rst_stats = mysql_query($qry_stats, $dbConn);
                    
                    $stat_data = array();
                    $agent_totals = array(); 
                    while ($row = mysql_fetch_assoc($rst_stats)) {
                        $stat_data[$row['part_id']][$row['mon']] = array('cnt' => $row['tot_cnt'], 'amt' => $row['tot_amt']);
                        if(!isset($agent_totals[$row['part_id']])) $agent_totals[$row['part_id']] = array('cnt'=>0, 'amt'=>0);
                        $agent_totals[$row['part_id']]['cnt'] += $row['tot_cnt'];
                        $agent_totals[$row['part_id']]['amt'] += $row['tot_amt'];
                    }

                    if (count($agents) == 0) {
                        echo "<div class='alert alert-warning'>해당 조건에 맞는 거래처 데이터가 없습니다.</div>";
                    } else {
                ?>
                
                <div style="overflow-x: auto; white-space: nowrap; padding-bottom: 15px;">
                    <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail" style="width:auto; min-width:100%;">
                        <thead>
                            <tr>
                                <th style="border:1px dotted black; text-align:center; min-width:80px; background:#f0f0f0;">월 / 거래처</th>
                                <?php foreach ($agents as $agent) { ?>
                                    <th style="border:1px dotted black; text-align:center; min-width:120px;">
                                        <a href="#agent_<?= $agent['part_id'] ?>" style="color:black;"><?= $agent['kor_name'] ?></a>
                                    </th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border:1px dotted black; background-color:#009999; color:white; text-align:center;"><b>연간 합계</b></td>
                                <?php foreach ($agents as $agent) { 
                                    $t_cnt = isset($agent_totals[$agent['part_id']]) ? $agent_totals[$agent['part_id']]['cnt'] : 0;
                                    $t_amt = isset($agent_totals[$agent['part_id']]) ? $agent_totals[$agent['part_id']]['amt'] : 0;
                                    $t_amt_str = ($t_amt > 0) ? "<br><span style='color:yellow; font-size:11px;'>($".number_format($t_amt, 2).")</span>" : "";
                                ?>
                                    <td style="border:1px dotted black; background-color:#009999; text-align:center; color:white;">
                                        <b><?=number_format($t_cnt)?></b><?=$t_amt_str?>
                                    </td>
                                <?php } ?>
                            </tr>
                            <?php for ($i = 1; $i <= 12; $i++) { ?>
                                <tr>
                                    <td style="border:1px dotted black; text-align:center; background:#f9f9f9;"><b><?=$i?> 월</b></td>
                                    <?php foreach ($agents as $agent) { 
                                        $curr_cnt = isset($stat_data[$agent['part_id']][$i]) ? $stat_data[$agent['part_id']][$i]['cnt'] : 0;
                                        $curr_amt = isset($stat_data[$agent['part_id']][$i]) ? $stat_data[$agent['part_id']][$i]['amt'] : 0;
                                        $amt_display = ($curr_amt > 0) ? "<span style='color:blue; font-size:11px;'>($".number_format($curr_amt, 2).")</span>" : "";
                                        $cnt_display = ($curr_cnt > 0) ? "<b>".number_format($curr_cnt)."</b>" : "";
                                    ?>
                                        <td style="border:1px dotted black; text-align:center;">
                                            <?=$cnt_display?><?=$cnt_display && $amt_display ? "<br>" : ""?><?=$amt_display?>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <hr style="border-top: 2px dashed #8c8b8b; margin: 30px 0;">

                <?php 
                    // 3. 전체 상세 내역 (rand_id 기준) 
                    $qry_all_detail = "SELECT a.rand_id as part_id, c.kor_name, 
                                              DATE_FORMAT(a.stDate, '%m') as mon, 
                                              a.p_name, 
                                              COUNT(a.reserveCode) as rev_cnt, 
                                              SUM(a.p_cnt) as pax_cnt
                                       FROM reserve_info a
                                       JOIN member_list c ON a.rand_id = c.userid
                                       WHERE SUBSTRING(c.company_area, 1, 5) = '$sarea'
                                       AND a.stDate BETWEEN '$year_first' AND '$year_last'
                                       AND a.rev_status IN ('DONE')
                                       AND a.parent = 'MAIN'
                                       GROUP BY a.rand_id, a.p_name, mon
                                       ORDER BY c.kor_name ASC, mon ASC, a.p_name ASC";
                    $rst_all_detail = mysql_query($qry_all_detail, $dbConn);
                    $agent_details = array();
                    while ($row = mysql_fetch_assoc($rst_all_detail)) {
                        $agent_details[$row['part_id']][] = $row;
                    }

                    foreach ($agents as $agent) { 
                        $current_id = $agent['part_id'];
                        $current_name = $agent['kor_name'];
                        if (isset($agent_details[$current_id]) && count($agent_details[$current_id]) > 0) {
                            $rows = $agent_details[$current_id];
                ?>
                    <div class="row" id="agent_<?= $current_id ?>" style="margin-bottom: 40px;">
                        <div class="col-sm-12">
                            <h4 style="border-left: 5px solid #4285F4; padding-left: 10px; margin-bottom: 10px;">
                                <i class="glyphicon glyphicon-list-alt"></i> 
                                [<?=$current_name?>] 상품별 판매 상세 내역 (<?=$year?>년)
                            </h4>
                            <table class="table table-striped table-bordered table-hover" style="width:100%;">
                                <thead style="background-color: #f0f8ff;">
                                    <tr>
                                        <th style="text-align:center;">월 (Month)</th>
                                        <th style="text-align:center;">상품명 (Product Name)</th>
                                        <th style="text-align:center;">예약 건수</th>
                                        <th style="text-align:center;">총 인원(명)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $sum_rev = 0; $sum_pax = 0;
                                    foreach ($rows as $d_row) { 
                                        $sum_rev += $d_row['rev_cnt'];
                                        $sum_pax += $d_row['pax_cnt'];
                                    ?>
                                        <tr>
                                            <td style="text-align:center; font-weight:bold; color:#555;"><?=$d_row['mon']?>월</td>
                                            <td style="padding-left:10px;"><?=$d_row['p_name']?></td>
                                            <td style="text-align:center;"><?=number_format($d_row['rev_cnt'])?></td>
                                            <td style="text-align:center; font-weight:bold;"><?=number_format($d_row['pax_cnt'])?></td>
                                        </tr>
                                    <?php } ?>
                                    <tr style="background-color: #fffbf0;">
                                        <td colspan="2" style="text-align:center;"><b>합 계</b></td>
                                        <td style="text-align:center;"><b><?=number_format($sum_rev)?></b></td>
                                        <td style="text-align:center;"><b><?=number_format($sum_pax)?></b></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php 
                        }
                    }
                } 
                ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>
<script>
    $(document).ready(function () {
        $('#StartYMD').datepicker({
            format: "yyyy",
            minViewMode: 'years',
            autoclose: true
        });
    });

    function fn_excel() {
        var f = document.searchForm;
        if(f.a_area.value == "") {
            alert("지역을 선택해주세요.");
            return;
        }
        f.mode.value = "excel";
        f.target = "_self";
        f.submit();
        f.mode.value = "save";
    }
</script>
</body>
</html>