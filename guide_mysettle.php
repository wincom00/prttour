<?php
    include "include/header.php";

    // 로그인 체크
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
    } else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }

    // 날짜 초기화 (inc_base.php의 extract로 $startDate1, $endDate1 자동 설정됨)
    if ($startDate1 == "") {
        $startDate1 = date("Y-m-d", strtotime("-7days"));
        $endDate1   = date("Y-m-d", strtotime("+1 month"));
    }

    // ====== PHP 5.6 + mysql_* 버전 ======
    function printSingle() {
        global $division, $crev, $pdx, $sub, $startDate1, $endDate1, $guideid, $user_dbinfo;

        // 조건문
        $where = " WHERE 1=1 ";

        if ($startDate1 != "") {
            $where .= " AND a.stDate >= '" . mysql_real_escape_string($startDate1) . "' ";
        }
        if ($endDate1 != "") {
            $where .= " AND a.stDate <= '" . mysql_real_escape_string($endDate1) . "' ";
        }

        // 사용자 id
        $uid = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : '';
        $uid_esc = mysql_real_escape_string($uid);

        $query = "
          SELECT
            a.seq_no, a.grand_eCode, a.sub_eCode, a.stDate, a.guide_id,
            a.p_code, a.p_name,
            gsm.settle_code, gsm.finance_date, gsm.report_date, gsm.check_out, gsm.check_date,
            ml.kor_name
          FROM tour_guide a
          LEFT JOIN guide_setmaster gsm
            ON gsm.grand_eCode = a.grand_eCode
           AND gsm.sub_eCode   = a.sub_eCode
          LEFT JOIN member_list ml
            ON ml.userid = a.guide_id
          $where
            AND a.guide_id = '".$uid_esc."'
            AND EXISTS (
              SELECT 1
                FROM tour_master b
               WHERE b.grand_eCode = a.grand_eCode
                 AND b.p_code      = a.p_code
            )
        ";

        $rst1 = mysql_query($query);
        if (!$rst1) {
            echo "<tr><td colspan='8' align='center'>Query Error: " . mysql_error() . "</td></tr>";
            return;
        }

        while ($row1 = mysql_fetch_assoc($rst1)) {

            // 가이드 정산코드
            $arr = getGuideCode($row1['grand_eCode'], $row1['sub_eCode']);
            $settle_code = $arr['settle_code'] ?? '';

            // 행사인원
            $arr = getReserveInfoCnt($row1['p_code'], $row1['stDate']);
            $p_cnt = $arr['cnt'] ?? 0;

            // 행사기간
            $period = getPeriodbyrev($row1['p_code'], $row1['stDate']);

            // 행사코드
            $grandCode = $row1['grand_eCode'] . " <br/><font color='red'>" . $row1['sub_eCode'] . "</font>";

            // 상태
            $status = getGuideStatus($row1['grand_eCode'], $row1['sub_eCode'], $row1['stDate']);

            // 가이드명
            $arr = getinfo_dbMember($row1['guide_id']);
            $kor_name = $arr['kor_name'] ?? '';

            $seq_no = $row1['seq_no'];
            $stDate = $row1['stDate'];
            $p_name = $row1['p_name'];

            $link = "guide_cal_my.php?division=6&pdx=2&sub=15&number=" . urlencode($seq_no) . "&scode=" . urlencode($settle_code);

            echo "<tr>
                <td align='center'><a href='{$link}'>{$settle_code}</a></td>
                <td align='center'><a href='{$link}'>{$grandCode}</a></td>
                <td align='center'><a href='{$link}'>{$stDate}</a></td>
                <td align='center'><a href='{$link}'>{$p_name}</a></td>
                <td align='center'><a href='{$link}'>{$period}</a></td>
                <td align='center'><a href='{$link}'>{$p_cnt}</a></td>
                <td align='center'><a href='{$link}'>{$kor_name}</a></td>
                <td align='center'><a href='{$link}'>{$status}</a></td>
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
                    <div class="well well-sm">
                        <table class="table-condensed" style="width:100%">
                            <tr>
                                <td width="80" style="font-weight:bold;"><i class="glyphicon glyphicon-calendar"></i> 행사일</td>
                                <td>
                                    <div class="form-inline">
                                        <div class="input-group">
                                            <input type="date" class="form-control input-sm" id="startDate1" name="startDate1" value="<?=$startDate1?>" style="width:130px;">
                                            <span class="input-group-addon">~</span>
                                            <input type="date" class="form-control input-sm" id="endDate1" name="endDate1" value="<?=$endDate1?>" style="width:130px;">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="glyphicon glyphicon-search"></i> 검색</button>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>

                <div class="row">
                    <div class="col-sm-12">
                        <table id="guide_settlement_table" class="table table-striped table-bordered table-hover table-condensed js-productTable">
                            <thead>
                                <tr class="active">
                                    <th class="text-center">가이드정산코드</th>
                                    <th class="text-center">행사코드</th>
                                    <th class="text-center">행사일</th>
                                    <th class="text-center">행사명</th>
                                    <th class="text-center">행사기간</th>
                                    <th class="text-center">행사인원</th>
                                    <th class="text-center">가이드명</th>
                                    <th class="text-center">상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php printSingle(); ?>
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
        var oTable = $('#guide_settlement_table').dataTable({
            stateSave: true,
            pageLength: 100,
            "order": [[ 2, "desc" ]],
            "columnDefs": [
                { "targets": [0,1,2,3,4,5,6,7], "orderable": true }
            ],
            "language": {
                "emptyTable": "데이터가 없습니다.",
                "lengthMenu": "페이지당 _MENU_ 개씩 보기",
                "info": "현재 _START_ - _END_ / _TOTAL_건",
                "infoEmpty": "데이터 없음",
                "infoFiltered": "( _MAX_건의 데이터에서 필터링됨 )",
                "search": "필터링:",
                "zeroRecords": "일치하는 데이터가 없습니다.",
                "loadingRecords": "로딩중...",
                "processing": "잠시만 기다려 주세요...",
                "paginate": {
                    "next": "다음",
                    "previous": "이전"
                }
            }
        });

        $(".dataTables_length").css({ "display" :"none" });
    });
</script>
</body>
</html>
