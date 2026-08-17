<?php
    include "include/header.php";

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

    // 첫 진입시 기본 조회범위 (전체를 다 그리면 3천행이 넘어 느리다. 검색하면 입력값 그대로 쓴다)
    if ($_POST['mode'] != 'search' && $startDate1 == "" && $endDate1 == "") {
        $startDate1 = date("Y-m-d",strtotime("-7days"));
        $endDate1   = date("Y-m-d",strtotime("+1 month"));
    }

    function printSingle(){

        global $dbConn,$division,$crev,$pdx,$sub,$startDate1,$endDate1,$guideid;

        if ($startDate1) {
            $from_w = " AND a.stDate >= '$startDate1' ";
        }
        if ($endDate1) {
            $to_w = " AND a.stDate <= '$endDate1' ";
        }

        if($guideid) {
            $guide_w = " AND a.guide_id = '$guideid' ";
        }

        // [수정] 메인코드 + 서브코드 기준으로 그룹화하여 각각의 서브행사가 리스트에 나오도록 함
        // [튜닝] 인원/기간/가이드/정산상태를 행마다 따로 조회하던 것을 이 쿼리 하나로 합쳤다.
        //        (기존: 행당 6쿼리 x 3천행)
        $query = "SELECT MIN(a.seq_no) as seq_no, a.grand_eCode, a.sub_eCode, a.stDate, a.p_code, a.p_name, c.p_day,
        GROUP_CONCAT(IFNULL((SELECT m.kor_name FROM member_list m WHERE m.userid = a.guide_id AND m.division = 'guide' LIMIT 1),'') ORDER BY a.seq_no SEPARATOR '|') as guide_names,
        (SELECT SUM(r.p_cnt) FROM reserve_info r WHERE r.p_code = a.p_code AND r.stDate = a.stDate AND r.rev_status = 'DONE') as p_cnt,
        (SELECT COUNT(*) FROM hotel_settlesum h WHERE h.grand_eCode = a.grand_eCode AND h.sub_eCode = a.sub_eCode) as hotel_cnt,
        (SELECT COUNT(*) FROM car_settlesum s WHERE s.grand_eCode = a.grand_eCode AND s.sub_eCode = a.sub_eCode) as car_cnt
        FROM tour_guide a
        LEFT JOIN product_master c ON a.p_code = c.p_code
        WHERE a.p_code not like 'ADD%' AND c.p_day > 1
        AND EXISTS (SELECT 1 FROM tour_master b WHERE b.grand_eCode = a.grand_eCode AND b.p_code = a.p_code)
        $from_w $to_w $guide_w
        GROUP BY a.grand_eCode, a.sub_eCode, a.stDate, a.p_code, a.p_name, c.p_day
        ORDER BY a.stDate DESC, a.sub_eCode ASC";

        //echo $query;
        $rst1 = mysql_query($query,$dbConn);
        while($row1 = mysql_Fetch_assoc($rst1)){

            //행사기간
            $c_day = ((int)$row1['p_day'] - 1).' day';
            $period = $row1['stDate']."~".date("Y-m-d", strtotime($row1['stDate']." +".$c_day));

            //가이드 (서브코드 기준 전체 명단 - 차량 대수만큼 나온다)
            $names = array();
            foreach(explode('|', (string)$row1['guide_names']) as $nm){
                $names[] = $nm !== '' ? "<b>$nm</b>" : "<span style='color:#ccc;'>미지정</span>";
            }
            $guide_name = implode("<br/>", $names);

            //상태
            $status1 = $row1['hotel_cnt'] > 0 ? "<font color=red>정산등록</font>" : "미등록";
            $status2 = $row1['car_cnt']   > 0 ? "<font color=red>정산등록</font>" : "미등록";

            $link_hotel = "hotel_cal2.php?division=6&pdx=1&sub=15&number=".$row1['seq_no'];
            $link_car   = "car_cal2.php?division=6&pdx=1&sub=15&number=".$row1['seq_no'];

            echo "<tr>
                <td align='center'>{$row1['grand_eCode']}</td>
                <td align='center' style='color:blue; font-weight:bold;'>{$row1['sub_eCode']}</td>
                <td align='center'>{$row1['stDate']}</td>
                <td>{$row1['p_name']}</td>
                <td align='center'>$period</td>
                <td align='center'>{$row1['p_cnt']}</td>
                <td align='center'>$guide_name</td>
                <td align='center'>$status1</td>
                <td align='center'>$status2</td>
                <td align='center' style='vertical-align:middle;'>
                    <div class='btn-group btn-group-xs'>
                        <button type='button' class='btn btn-primary' onclick=\"location.href='$link_hotel'\" title='호텔 정산'>호텔</button>
                        <button type='button' class='btn btn-success' onclick=\"location.href='$link_car'\" title='차량 정산'>차량</button>
                    </div>
                </td>
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
                    <li>호텔별정산(서브코드별)</li>
                </ul>
            </div>
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <form action="" name="frmName"  method="post">
                        <input type="hidden" name="mode" value="search">
                        <table class="table table-bordered table-condensed">
                            <tr>
                                <td width="10%" class="titletd text-center">행사일기준</td>
                                <td width="40%" class="">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <div class="input-group input-group-sm">
                                                <input type="date" class="form-control" id="startDate1" name="startDate1" max="2999-12-31" placeholder="From" value="<?=$startDate1?>" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="input-group input-group-sm">
                                                <input type="date" class="form-control" id="endDate1" name="endDate1" max="2999-12-31" placeholder="to" value="<?=$endDate1?>" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <select class="form-control" name="guideid" id="guideid">
                                                <option value=''>- 가이드 전체 -</option>
                                                <?php
                                                  $query ="SELECT userid,kor_name FROM member_list WHERE division ='guide' ";
                                                  $rst1 = mysql_query($query,$dbConn);
                                                  while($row1 = mysql_Fetch_assoc($rst1)){

                                                ?>
                                                <option value="<?=$row1['userid']?>" <?php if($guideid == $row1['userid']) echo 'selected'; ?>><?=$row1['kor_name']?></option>

                                                <?php }?>

                                            </select>
                                        </div>
                                        <div class="col-sm-3">
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
                            <table name="ctable" id="ctable"  class="table table-striped table-bordered table-hover table-condensed js-productTable">
                                <thead>
                                    <tr class="info">
                                        <th>메인코드</th>
                                        <th>서브코드</th>
                                        <th>행사일</th>
                                        <th>행사명</th>
                                        <th>기간</th>
                                        <th>인원</th>
                                        <th>가이드</th>
                                        <th>호텔상태</th>
                                        <th>차량상태</th>
                                        <th width="90">관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php  echo printSingle(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><!-- -->
            </div>
        </div>

    </div>
    <?php
        include "include/side_m.php"
    ?>
    <script>
        $(document).ready(function () {

            var oTable = $('#ctable').dataTable({
                stateSave: true,
                pageLength: 100,
                "order": [[ 2, "desc" ], [ 1, "asc" ]] // 행사일 내림차순 후 서브코드 오름차순
            });

            $(".dataTables_length").css({ "display" :"none" });

        })

    </script>
    </body>
</html>
