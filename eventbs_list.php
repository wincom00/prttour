<?php
    include "include/header.php";
    //include "include/inc_base.php"; 
    // ※ 주의: inc_base.php 내에서 mysql_connect() 및 mysql_select_db()가 수행되어야 합니다.
    
    // 권한 및 로그인 체크
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
        exit;
    }
/*    if (!hasMenuAccess($division, $pdx, $sub)) {
        $goUrl_1 = "index.php";
        Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
        echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
        exit;
    }
*/
    if (($kindEvent == 2) || ($kindEvent == "")) {
        $lst = 2;
    } else {
        $lst = 1;
    }

    // 저장 후 기존 조회조건 유지를 위한 리턴 쿼리
    $keep_view_mode   = isset($_REQUEST['view_mode']) ? $_REQUEST['view_mode'] : '';
    $keep_productName = isset($_REQUEST['productName']) ? $_REQUEST['productName'] : '';
    $keep_startDate1  = isset($_REQUEST['startDate1']) ? $_REQUEST['startDate1'] : '';
    $keep_endDate1    = isset($_REQUEST['endDate1']) ? $_REQUEST['endDate1'] : '';
    $keep_evest       = isset($_REQUEST['evest']) ? $_REQUEST['evest'] : '';
    $keep_kindEvent   = isset($_REQUEST['kindEvent']) ? $_REQUEST['kindEvent'] : '';
    $keep_productOwener = isset($_REQUEST['productOwener']) ? $_REQUEST['productOwener'] : '';

    $returnQuery = "division=4&pdx=".$pdx."&sub=".$sub
                 . "&view_mode=".urlencode($keep_view_mode)
                 . "&productName=".urlencode($keep_productName)
                 . "&startDate1=".urlencode($keep_startDate1)
                 . "&endDate1=".urlencode($keep_endDate1)
                 . "&evest=".urlencode($keep_evest)
                 . "&kindEvent=".urlencode($keep_kindEvent)
                 . "&productOwener=".urlencode($keep_productOwener);

    // =========================================================================
    // 저장 로직 (mysql_* 방식으로 변경)
    // =========================================================================
    if ($mode =="save") {
        for($i=0; $i<count($seqNo); $i++) {
            $s = $seqNo[$i];
            if ($gcode[$s] == "") {
                $total_eventNum = getNumTevent();
                $mt = microtime(true);
                $sec = floor($mt);
                $hs  = sprintf("%03d", ($mt - $sec) * 1000); 
                $total_eventCode = "GTP".date("His", $sec) . $hs.$total_eventNum;

                $qry2 = "insert into tour_master (grand_eNum, grand_eCode, p_code, p_name, tour_pcnt, stDate, edDate, r_status, ev_status, s_pcode, etc_memo, ev_memo, chk_ass, userid, wdate)
                          values ('$total_eventNum', '$total_eventCode', '$pcode[$s]', '$pname[$s]', '$acnt[$s]', '$sdate[$s]', '', 'P', '2', '', '', '', '', '{$user_dbinfo['userid']}', now())";
                 // $rst2=$dbConn->query($qry2); -> 변경
                 $rst2 = mysql_query($qry2);
            } else {
                 $qry2 = " update tour_master set s_pcode = '$productSub', r_status = 'P', ev_status = '2' where grand_eCode = '$gcode[$s]' ";
                 // $rst2=$dbConn->query($qry2); -> 변경
                 $rst2 = mysql_query($qry2);
            }
        }
        Misc::jvAlert("저장 완료!!!");
        echo "<meta http-equiv='refresh' content='0; url=./eventbs_list.php?$returnQuery'>";
        exit;
    }

    if ($mode =="save1") {
         for($i=0; $i<count($seqNo); $i++) {
            $s = $seqNo[$i];
            $qry2 = " update tour_master set r_status = '$bookStatus' where grand_eCode = '$gcode[$s]' ";
            // $rst2=$dbConn->query($qry2); -> 변경
            $rst2 = mysql_query($qry2);
         }
         Misc::jvAlert("저장 완료!!!");
         echo "<meta http-equiv='refresh' content='0; url=./eventbs_list.php?division=4&pdx=$pdx&sub=$sub'>";
         exit;
    }

    if ($mode =="save2") {
        for($i=0; $i<count($seqNo); $i++) {
            $s = $seqNo[$i];
            $qry2 = " update tour_master set ev_status = '$eventStatus' where grand_eCode = '$gcode[$s]' ";
            // $rst2=$dbConn->query($qry2); -> 변경
            $rst2 = mysql_query($qry2);
         }
         Misc::jvAlert("저장 완료!!!");
         echo "<meta http-equiv='refresh' content='0; url=./eventbs_list.php?division=4&pdx=$pdx&sub=$sub'>";
         exit;
    }

    // 날짜 기본값 설정
    if ($startDate1 == "") {
        $startDate1 = date("Y-m-01");
        $endDate1   = date("Y-m-t");
    }
    
    /* =========================================================================
       [핵심 수정] 출력 함수 printSingle (mysql_* 방식)
       ========================================================================= */
    function printSingle(){
            
            // global $dbConn 제거 (mysql_* 함수는 전역 링크를 자동 참조함)
            global $division,$crev,$pdx,$sub,$productName,$evest,$startDate1,$endDate1,$k,$productOwener;
            
            // 1. 조회 모드 확인 (기본값: reserved)
            $view_mode = isset($_REQUEST['view_mode']) ? $_REQUEST['view_mode'] : 'reserved';

            // 2. 검색 조건 쿼리 조각 생성
            $qrynm  = ($productName) ? " && b.p_name like '%$productName%'" : "";
            $qryown = ($productOwener) ? " && b.p_own = '$productOwener'" : "";
            $qryeve = ($evest) ? " && c.ev_status='$evest'" : "";

            // 3. 쿼리 실행 및 출력
            if ($view_mode == 'all') {
                // =============================================================
                // [MODE: 전체 스케줄 보기]
                // =============================================================
                
                $startTS = strtotime($startDate1);
                $endTS   = strtotime($endDate1);

                // 날짜 루프 시작
                for ($i = $startTS; $i <= $endTS; $i += 86400) {
                    $currDate = date("Y-m-d", $i); // 현재 체크하는 날짜
                    $currWeek = date("w", $i);     // 요일 (0:일, 1:월, ... 6:토)

                    $qry1 = "SELECT 
                                c.grand_eCode, 
                                b.p_code, 
                                b.p_name, 
                                '$currDate' as stDate,  /* 날짜는 루프 변수 사용 */
                                b.c_code1, b.c_code2, b.p_own, b.p_day, b.p_cnt, 
                                c.r_status, c.ev_status, c.tour_pcnt, c.s_pcode,
                                COUNT(a.reserveCode) as real_res_cnt
                             FROM product_master b
                             LEFT JOIN tour_master c ON b.p_code = c.p_code AND c.stDate = '$currDate'
                             LEFT JOIN reserve_info a ON b.p_code = a.p_code AND a.stDate = '$currDate' AND a.rev_status != 'CANCEL'
                             WHERE b.p_type IN ('1','2','4') 
                               AND b.m_type = 'S' 
                               AND b.p_code NOT LIKE '%ADD%'
                               /* ▼ [중요] 유효기간 및 요일 체크 */
                               AND b.p_vstart <= '$currDate' 
                               AND b.p_vend >= '$currDate'
                               AND b.p_week LIKE '%$currWeek%'
                               $qrynm $qryeve $qryown 
                             GROUP BY b.p_code
                             ORDER BY b.p_name ASC";
                    
                    // 해당 날짜의 쿼리 실행 (mysql_query 사용)
                    // $rst1 = $dbConn->query($qry1); -> 변경
                    $rst1 = mysql_query($qry1);

                    // while($row1 = $rst1->fetch_assoc()) { -> 변경
                    if ($rst1) {
                        while($row1 = mysql_fetch_assoc($rst1)) {
                            renderRow($row1); // 행 출력 함수 호출
                        }
                    }
                } // End Date Loop

            } else {
                // =============================================================
                // [MODE: 예약된 상품만 보기] (기존 로직 유지)
                // =============================================================
                // 날짜 조건
                if ($startDate1) {
                    $qrysdate = " && (a.stDate BETWEEN '$startDate1' AND '$endDate1') ";
                } else {
                    $qrysdate = "";
                }

                $qry1 = "SELECT 
                            c.grand_eCode, 
                            a.p_code, 
                            b.p_name, 
                            a.stDate, 
                            b.c_code1, b.c_code2, b.p_own, b.p_day, b.p_cnt, 
                            c.r_status, c.ev_status, c.tour_pcnt, c.s_pcode 
                          FROM reserve_info a
                          JOIN product_master b ON a.p_code = b.p_code
                          LEFT OUTER JOIN tour_master c ON a.p_code = c.p_code AND a.stDate = c.stDate
                          WHERE b.p_type IN ('1','2','4') 
                            AND b.m_type = 'S' 
                            AND b.p_code NOT LIKE '%ADD%' 
                            AND a.rev_status != 'CANCEL' 
                            $qrynm $qrysdate $qryeve $qryown 
                          GROUP BY a.p_code, a.stDate  
                          ORDER BY a.stDate DESC";
                
				//echo $qry1;
				
                // $rst1 = $dbConn->query($qry1); -> 변경
                $rst1 = mysql_query($qry1);
                
                // while($row1 = $rst1->fetch_assoc()){ -> 변경
                if ($rst1) {
                    while($row1 = mysql_fetch_assoc($rst1)){
                        renderRow($row1);
                    }
                }
            }
    }

    // =========================================================================
    // [보조 함수] 테이블 행 출력 (중복 코드 제거용)
    // =========================================================================
    function renderRow($row1) {
        global $division, $pdx, $sub, $k;

        $cinfo2 = codebaseName($row1['c_code2']);

        // 상태값 변환
        if ($row1['r_status']== 'P') $row1['r_status'] = "<font color=red>예약접수중</font>";
        elseif ($row1['r_status']== 'C') $row1['r_status'] = "<font color=red>예약마감</font>";
        elseif ($row1['r_status']== '')  $row1['r_status'] = "<font color=red>미등록</font>";

        if ($row1['ev_status']== '1') $row1['ev_status'] = "<font color=red>미확정</font>";
        elseif ($row1['ev_status']== '2') $row1['ev_status'] = "<font color=red>확정</font>";
        elseif ($row1['ev_status']== '3') $row1['ev_status'] = "<font color=red>만차</font>";
        elseif ($row1['ev_status']== '4') $row1['ev_status'] = "<font color=red>취소</font>";
        elseif ($row1['ev_status']== '5') $row1['ev_status'] = "<font color=red>기타</font>";
        elseif ($row1['ev_status']== '')  $row1['ev_status'] = "<font color=red>미등록</font>";

        $randrow = array();
        if ($row1['p_own'] == "purun") {
            $randrow['kor_name'] = "푸른투어본사";
        }

        // 예약 인원수 (전체 보기 모드에서는 real_res_cnt 사용 가능, 아니면 함수 호출)
        if (isset($row1['real_res_cnt'])) {
            $current_res_cnt = $row1['real_res_cnt'];
        } else {
            $pcnt = getReserveInfoCnt($row1['p_code'], $row1['stDate']);
            $current_res_cnt = is_numeric($pcnt['cnt']) ? $pcnt['cnt'] : 0;
        }
        
        // 요일
        $sday = $row1['stDate'];
        $week = array("일", "월", "화", "수", "목", "금", "토");
        $sweekday = $week[ date('w', strtotime($sday)) ];

        if ($row1['tour_pcnt'] != "") {
            $row1['p_cnt'] = $row1['tour_pcnt'];
        }

        // grand_eCode(통합코드)가 없으면 '생성필요' 등으로 표시 가능하나 일단 공란 유지
        $gCodeDisplay = $row1['grand_eCode'] ? $row1['grand_eCode'] : "<span style='color:#ccc;font-size:11px'>스케줄없음</span>";

        echo "<tr class='arhef'>
                    <td> <input type='checkbox' name='seqNo[]' value='$k' /></td>
                    <td align='center'>
                        <a href='eventbs_m.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}'>$gCodeDisplay</a>
                        <input type='hidden' name='gcode[$k]' value='{$row1['grand_eCode']}'>
                    </td>
                    <td><a href='eventbs_m.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}'>{$cinfo2['comment']}</a></td>
                    <td><a href='eventbs_m.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}'>{$row1['p_code']}</a></td>
                    <td>
                        <a href='eventbs_m.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}'>{$row1['p_name']}</a>
                        <input type='hidden' name='pcode[$k]' value='{$row1['p_code']}'>
                        <input type='hidden' name='pname[$k]' value='{$row1['p_name']}'>
                    </td>
                    <td align='center'>
                        <a href='eventbs_m.php?division=$division&pdx=$pdx&sub=$sub&st={$row1['stDate']}&pcode={$row1['p_code']}'>{$row1['stDate']} ($sweekday)</a>
                        <input type='hidden' name='sdate[$k]' id='sdate' value='{$row1['stDate']}'>
                    </td>
                    <td align='center'><input type='hidden' name='acnt[$k]' value='{$row1['p_cnt']}'>{$row1['p_cnt']}</td>
                    <td align='center'>$current_res_cnt</td>
                    <td>{$randrow['kor_name']} </td>
                    <td align='center'>{$row1['r_status']}</td>
                    <td align='center'>{$row1['ev_status']}</td>
                </tr>";
        $k++;
    }
?>
    <div id="contentwrapper" class="reservationDetailForm">
        <div class="main_content">
            <div id="jCrumbs" class="breadCrumb module">
                <ul>
                    <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                    <li><a href="#">행사관리</a></li>
                    <li>행사기본관리</li>
                </ul>
            </div>
            <form id="frmName" name="frmName" method="post">
                <input type="hidden" name="mode" id="mode" value="search">
                <input type="hidden" name="productOwener1" id="productOwener1" value="<?=$productOwener?>">
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <table class="table table-bordered table-condensed">
                        <tr>
                            <td class="titletd text-center">조회 대상</td>
                            <td colspan="3">
                                <label class="radio-inline" style="margin-right:20px;">
                                    <input type="radio" name="view_mode" value="reserved" 
                                    <?php if(empty($_REQUEST['view_mode']) || $_REQUEST['view_mode']=='reserved') echo 'checked'; ?>> 
                                    <strong>예약된 상품만 보기</strong> (기본)
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="view_mode" value="all" 
                                    <?php if(isset($_REQUEST['view_mode']) && $_REQUEST['view_mode']=='all') echo 'checked'; ?>> 
                                    <strong>전체 스케줄 보기</strong> (기간 내 가능 상품)
                                </label>
                            </td>
                        </tr>

                        <tr>
                            <td width="10%" class="titletd text-center">상품명</td>
                            <td width="40%" class=""><input type="text" id="prod_code" name="productName" class="inpubase" value="<?=$productName?>"/></td>
                            <td width="10%" class="titletd text-center">출발일</td>
                            <td width="40%" class="">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <input type="date" class="form-control" id="startDate1" name="startDate1" max="2999-12-31" placeholder="시작일" value="<?=$startDate1?>" autocomplete="off" />
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="date" class="form-control" id="endDate1" name="endDate1" max="2999-12-31" placeholder="마지막일" value="<?=$endDate1?>" autocomplete="off" />
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="10%" class="titletd text-center">행사상태</td>
                            <td width="40%" class="no-right-border">
                                <select class="form-control" name="evest">
                                    <option value="">- 선택 -</option>
                                    <option value="1" <?php if($evest==1) echo "selected"; ?> >미확정</option>
                                    <option value="2" <?php if($evest==2) echo "selected"; ?>>확정</option>
                                    <option value="3" <?php if($evest==3) echo "selected"; ?>>만차</option>
                                    <option value="4" <?php if($evest==4) echo "selected"; ?>>취소</option>
                                    <option value="5" <?php if($evest==5) echo "selected"; ?>>기타</option>
                                </select>
                            </td>
                        </tr>                
                        <tr>
                            <td colspan="4" class="text-center"><button type='submit' class="btn btn-primary btn-sm btn1">검색</button></td>
                        </tr>
                    </table>

                    <br />
                    <div class="row">
                        <div class="col-sm-12">
                            <table name="ctable" id="ctable"  class="table table-striped table-bordered table-hover table-condensed js-productTable1">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll" /></th>
                                        <th>통합행사코드</th>
                                        <th>상품지역분류</th>
                                        <th>상품코드</th>
                                        <th>상품명</th>
                                        <th>출발일</th>
                                        <th>정원</th>
                                        <th>예약</th>
                                        <th>상품소유사</th>
                                        <th>예약상태</th>
                                        <th>행사상태</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                     echo printSingle();
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div><div class="row no-nav">
                    <div class="col-sm-6 text-center">
                        &nbsp;<button type="button" class="btn btn-primary btn-sm js-tsave" >통합행사코드 일괄생성</button>
                    </div>
                </div>
                <br />
                <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                    <tbody>
                        <tr>
                            <td colspan="2" class="active text-center formHeader">예약상태</td>
                            <td colspan="4">
                                <label class="radio-inline">
                                    <input type="radio" name="bookStatus" value="P" <?php if(strstr($sctour['r_status'],"P")) echo "checked"; ?>> 예약접수중
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="bookStatus" value="C" <?php if(strstr($sctour['r_status'],"C")) echo "checked"; ?>> 예약마감
                                </label>
                            </td>
                            <td colspan="10" class=" formHeader"><button type="button" class="btn btn-primary btn-sm js-sesave1" >예약상태저장</button></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="active text-center formHeader">행사상태</td>
                            <td colspan="4">
                                <label class="radio-inline">
                                    <input type="radio" name="eventStatus" value="1" <?php if(strstr($sctour['ev_status'],"1")) echo "checked"; ?>> 미확정
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="eventStatus" value="2" <?php if(strstr($sctour['ev_status'],"2")) echo "checked"; ?>> 확정
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="eventStatus" value="3" <?php if(strstr($sctour['ev_status'],"3")) echo "checked"; ?>> 만차
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="eventStatus" value="4" <?php if(strstr($sctour['ev_status'],"4")) echo "checked"; ?>> 취소
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="eventStatus" value="5" <?php if(strstr($sctour['ev_status'],"5")) echo "checked"; ?>> 기타
                                </label>
                            </td>
                            <td colspan="10" class=" formHeader"><button type="button" class="btn btn-primary btn-sm js-sesave2" >행사상태저장</button></td>
                        </tr>
                    </tbody>
                </table>
                <div class="row no-nav">
                    <div class="col-sm-6"></div>
                </div>
            </div> 
            </form>
        </div>
    </div>
    <?php include "include/side_m.php" ?>
    <script>
        $(document).ready(function () {
            // pt.initReservationDetail() 
            var dateToday = new Date()
            $('.tourDate1').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true
            });
            $('.tourDate2').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true
            });
            
            // pt.initReservationList() 

            var oTable = $('#ctable').dataTable({
                stateSave: true,
                pageLength: 100,
                "order": [[ 5, "desc" ]] 
            });

            var allPages = oTable.fnGetNodes();

            $('body').on('click', '#selectAll', function () {
                if ($(this).hasClass('allChecked')) {
                    $('input[type="checkbox"]', allPages).prop('checked', false);
                } else {
                    $('input[type="checkbox"]', allPages).prop('checked', true);
                }
                $(this).toggleClass('allChecked');
            })
            $('.js-tsave').click(function(e){
                if (confirm("통합행사코드를 일괄생성 하시겠습니까?")){
                    $("#mode").val("save");
                    $("#frmName").submit();
                }
            });
            
            $('.js-sesave1').click(function(e){
                if (confirm("예약상태를 일괄저장 하시겠습니까?")){
                    $("#mode").val("save1");
                    $("#frmName").submit();
                }
            });
            $('.js-sesave2').click(function(e){
                if (confirm("행사상태를 일괄저장 하시겠습니까?")){
                    $("#mode").val("save2");
                    $("#frmName").submit();
                }
            });
             
            $(".dataTables_length").css({ "display" :"none" });
        })
    </script>
    </body>
</html>
