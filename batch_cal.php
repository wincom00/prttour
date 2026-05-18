<?php
    include "include/header.php";

    // ===== 로그인 / 권한 확인 =====
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
    // ===== 입력 파라미터 =====
    $seldate       = isset($_REQUEST['seldate'])       ? trim($_REQUEST['seldate'])       : '';
    $startDate     = isset($_REQUEST['startDate'])     ? trim($_REQUEST['startDate'])     : '';
    $endDate       = isset($_REQUEST['endDate'])       ? trim($_REQUEST['endDate'])       : '';
    $cname         = isset($_REQUEST['cname'])         ? trim($_REQUEST['cname'])         : '';
    $employeeName  = isset($_REQUEST['employeeName'])  ? trim($_REQUEST['employeeName'])  : '';
    $searchpay     = isset($_REQUEST['searchpay'])     ? trim($_REQUEST['searchpay'])     : '';
    $productName   = isset($_REQUEST['productName'])   ? trim($_REQUEST['productName'])   : '';

    // ===== 페이지네이션 =====
    $page    = isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1;
    $perPage = isset($_REQUEST['per'])  ? max(10,(int)$_REQUEST['per'])  : 50;
    if ($perPage > 500) $perPage = 500;
    $offset  = ($page - 1) * $perPage;

    // ===== 날짜 범위 계산 =====
    function buildDateRange($seldate, $startDate, $endDate) {
        if ($startDate && !$endDate) $endDate = $startDate;
        if ($endDate && !$startDate) $startDate = $endDate;

        if ($startDate && $endDate) {
            $s = $startDate . " 00:00:00";
            $e = $endDate   . " 23:59:59";
        } else {
            $s = date("Y-m-d", strtotime("-14 day")) . " 00:00:00";
            $e = date("Y-m-d") . " 23:59:59";
        }
        return [$s,$e];
    }

    // ===== 리스트 출력(페이징 지원) =====
    function printPay(&$totalCount = 0){
        global $dbConn,$cname,$division,$pdx,$sub,$seldate,$startDate,$endDate,$employeeName,$searchpay,$user_dbinfo,$page,$perPage,$offset,$productName;

        $where  = [];
        $joins  = [];
        $where[] = "b.pay_method <> 'init'";
        $where[] = "b.payment_status IN ('READY','DONE','PPAY','OPAY')";
        $where[] = "a.parent = 'MAIN'";

        list($S,$E) = buildDateRange($seldate, $startDate, $endDate);
        if ($seldate === '1') {
            $where[] = "a.revDate BETWEEN '$S' AND '$E'";
        } else if ($seldate === '2') {
            $where[] = "b.wdate BETWEEN '$S' AND '$E'";
        } else if ($seldate === '3') {
            $where[] = "b.conf_date BETWEEN '$S' AND '$E'";
        } else if ($seldate === '4') {
            if ($startDate || $endDate) $where[] = "b.wdate BETWEEN '$S' AND '$E'";
            else $where[] = "a.revDate BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND NOW()";
            $where[] = "a.rev_status = 'CANCEL'";
        } else {
            $where[] = "a.revDate BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND NOW()";
        }

        if (!empty($productName)) {
            $pname = mysql_real_escape_string($productName);
            $where[] = "a.p_name LIKE '%$pname%'";
        }

        $needTraveler = false;
        if (!empty($cname)) {
            $cn = mysql_real_escape_string($cname);
            $where[] = "(a.book_pri LIKE '%$cn%' OR c.traveler_nm LIKE '%$cn%')";
            $needTraveler = true;
        }

        if (!empty($searchpay)) {
            $sp = mysql_real_escape_string($searchpay);
            if ($sp === '1') {
                $where[] = "b.conf_p <> '2'";
                $where[] = "b.pay_method NOT IN ('creditcard','airsys')";
            } else if ($sp === '2') {
                $where[] = "(b.conf_p='2' OR b.pay_method IN ('creditcard','airsys'))";
            }
        }

        if (!empty($employeeName)) {
            $emp = mysql_real_escape_string($employeeName);
            $where[] = "b.register='$emp'";
        }

        if (($user_dbinfo['dept_prior'] == "J") || ($user_dbinfo['dept_prior'] == "")) {
            $dept = mysql_real_escape_string($user_dbinfo['area_comp']);
            $where[] = "d.m_dept LIKE '%$dept%'";
        }

        $joins[] = "JOIN payment_history b ON a.reserveCode = b.reserveCode";
        $joins[] = "JOIN product_master d ON a.p_code = d.p_code";
        if ($needTraveler) $joins[] = "LEFT JOIN reserve_traveler c ON a.reserveCode = c.reserveCode";

        $whereSql = $where ? ("WHERE ".implode(" AND ", $where)) : "";

        // 총건수
        $cntSql = "SELECT COUNT(DISTINCT b.seq_no) AS cnt
                   FROM reserve_info a
                   ".implode("\n",$joins)."
                   $whereSql";
        $cntRst = mysql_query($cntSql,$dbConn);
        $rowCnt = mysql_fetch_assoc($cntRst);
        $totalCount = (int)$rowCnt['cnt'];

        // 데이터
        $qry1 = "
        SELECT
            a.rev_status,
            a.grand_revNo,
            a.reserveCode,
            a.p_code,
            a.p_name,
            a.book_pri,
            a.revDate,
            a.stDate,
            a.edDate,
            a.last_total,
            a.last_bal,
            a.p_cnt,
            a.base_rate,
            b.payment_status,
            b.pay_method AS pmethod,
            b.rate_m AS rm,
            b.seq_no,
            b.payment,
            DATE_FORMAT(b.wdate, '%Y-%m-%d') AS wwdate,
            b.register AS pregister,
            b.conf_p,
            b.pay_memo
        FROM reserve_info a
        ".implode("\n",$joins)."
        $whereSql
        GROUP BY b.seq_no
        ORDER BY a.revDate DESC
        LIMIT $offset, $perPage
        ";

        $rst1 = mysql_query($qry1,$dbConn);

        while($row1 = mysql_fetch_assoc($rst1)){
            $sign  = "$";
            $totamt = $sign.$row1['last_total'];
            $balamt = $sign.$row1['last_bal'];

            switch ($row1['pmethod']) {
                case "cash":        $cappay = "현금"; break;
                case "creditcard":  $cappay = "신용카드웹"; break;
                case "debitcard":   $cappay = "데빗"; break;
                case "bcreditcard": $cappay = "신용카드 자사단말기"; break;
                case "check":       $cappay = "체크"; break;
                case "banktransfer":$cappay = "은행송금"; break;
                case "fundtransfer":$cappay = "금액이동"; break;
                case "airsys":      $cappay = "항공시스템"; break;
                case "gift":        $cappay = "상품권및기타"; break;
                default:            $cappay = "";
            }

            if ($row1['payment_status']== 'RETURN') {
                $cappay = "환불완료";
                $pamt = "<font color=RED>-".$sign.$row1['payment']."</font>";
            } else {
                $pamt = $sign.$row1['payment'];
            }

            // 결제자 이름
            $uinfo = getinfo_dbMember($row1['pregister']);
            $kor_name = $uinfo['kor_name'];

            // 예약상태 정렬값 (문자 대신 숫자 우선순위)
            $revStRaw = $row1['rev_status'];
            $revOrder = 0;
            if ($row1['payment_status']== 'RETURN') { $revSt = "<font color=RED>환불완료</font>"; $revOrder=4; }
            else if ($revStRaw== 'READY')  { $revSt = "<font color=#0984a3>예약접수</font>"; $revOrder=1; }
            else if ($revStRaw== 'DONE')   { $revSt = "<font color=#911f77>예약확정</font>"; $revOrder=2; }
            else if ($revStRaw== 'CANCEL') { $revSt = "<font color=#e02133>예약취소</font>"; $revOrder=3; }
            else { $revSt = htmlspecialchars($revStRaw, ENT_QUOTES, 'UTF-8'); $revOrder=0; }

            // 회계확인 표기 및 체크박스
            $isAutoConfirmed = in_array($row1['pmethod'], array('creditcard', 'airsys'));
            $isConfirmed = ($row1['conf_p']=='2');
            if ($isAutoConfirmed) {
                $confText = "<span class='accspan text-primary'>자동확인</span>";
                $cb = "<input type='checkbox' class='js-rowCheck' value='{$row1['seq_no']}' disabled>";
            } else if ($isConfirmed) {
                $confText = "<span class='accspan text-primary'>확인완료</span>";
                $cb = "<input type='checkbox' class='js-rowCheck' value='{$row1['seq_no']}' disabled>";
            } else {
                $confText = "<span class='text-muted'>미확인</span>";
                $cb = "<input type='checkbox' class='js-rowCheck' value='{$row1['seq_no']}'>";
            }

            // ===== 정렬을 위해 data-sort-value 부여 =====
            // 날짜는 YmdHis, 숫자는 원시값, 텍스트는 소문자
            $sort_revdate = strtotime($row1['revDate']) ?: 0;
            $sort_wdate   = strtotime($row1['wwdate']) ?: 0;
            $sort_pname   = strtolower($row1['p_name']);
            $sort_bookpri = strtolower($row1['book_pri']);
            $sort_pcnt    = (int)$row1['p_cnt'];
            $sort_total   = (float)$row1['last_total'];
            $sort_paym    = strtolower($cappay);
            $sort_payment = (float)$row1['payment'];
            $sort_user    = strtolower($kor_name);
            $sort_memo    = strtolower($row1['pay_memo']);
            $sort_conf    = ($isConfirmed || $isAutoConfirmed) ? 2 : 1; // 미확인(1) < 확인완료/자동확인(2)

            echo "<tr>
                <td class='text-center'>$cb</td>

                <td align='center' data-sort-value='{$sort_revdate}'>
                    <a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['p_code']}')\">{$row1['revDate']}<br/>{$row1['reserveCode']}</a>
                </td>

                <td align='center' data-sort-value='{$sort_wdate}'>{$row1['wwdate']}</td>

                <td data-sort-value='".htmlspecialchars($sort_pname,ENT_QUOTES,"UTF-8")."'>
                    <a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['p_code']}')\">{$row1['p_name']}</a>
                </td>

                <td align='center' data-sort-value='".htmlspecialchars($sort_bookpri,ENT_QUOTES,"UTF-8")."'>
                    <a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['p_code']}')\">{$row1['book_pri']}</a>
                </td>

                <td align='center' data-sort-value='{$sort_pcnt}'>{$row1['p_cnt']}</td>

                <td align='center' data-sort-value='{$revOrder}'>{$revSt}</td>

                <td align='right' data-sort-value='{$sort_total}'>
                    {$totamt}<br /><font color=red>{$balamt}</font>
                </td>

                <td align='center' data-sort-value='".htmlspecialchars($sort_paym,ENT_QUOTES,"UTF-8")."'>{$cappay}</td>

                <td align='right' data-sort-value='{$sort_payment}'>
                    <a href=\"javascript:openwin('{$row1['reserveCode']}','{$row1['p_code']}')\">{$pamt}</a>
                </td>

                <td align='center' data-sort-value='".htmlspecialchars($sort_user,ENT_QUOTES,"UTF-8")."'>
                    {$kor_name}
                </td>

                <td width='10%' data-sort-value='".htmlspecialchars($sort_memo,ENT_QUOTES,"UTF-8")."'>
                    {$row1['pay_memo']}
                </td>

                <td align='center' data-sort-value='{$sort_conf}'>
                    <span class='accspan'>{$confText}</span>
                </td>
            </tr>";
        }
    }

    // ===== 페이저 =====
    function renderPager($total, $page, $perPage) {
        $pages = max(1, ceil($total / $perPage));
        if ($pages <= 1) return;

        $start = max(1, $page-3);
        $end   = min($pages, $page+3);

        echo "<div class='text-center' style='margin:10px 0'>";
        echo "<ul class='pagination pagination-sm' style='margin:0;'>";
        $prev = max(1, $page-1);
        $disabled = ($page==1) ? "class='disabled'" : "";
        echo "<li $disabled><a href='#' class='js-pg' data-page='$prev'>&laquo;</a></li>";

        for ($p=$start; $p<=$end; $p++) {
            $act = ($p==$page) ? "class='active'" : "";
            echo "<li $act><a href='#' class='js-pg' data-page='$p'>$p</a></li>";
        }

        $next = min($pages, $page+1);
        $disabled = ($page==$pages) ? "class='disabled'" : "";
        echo "<li $disabled><a href='#' class='js-pg' data-page='$next'>&raquo;</a></li>";

        echo "</ul>";
        echo "</div>";
    }
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>직원별수금정산</title>
<style>
  th.js-sort { cursor:pointer; user-select:none; }
  th.js-sort .sort-arrow { margin-left:4px; font-size:11px; opacity:.6; }
  th.js-sort.asc  .sort-arrow::after { content:"▲"; }
  th.js-sort.desc .sort-arrow::after { content:"▼"; }
</style>
</head>
<body>
    <div id="contentwrapper" class="reservationDetailForm">
        <div class="main_content">
            <div id="jCrumbs" class="breadCrumb module">
                <ul>
                    <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                    <li><a href="#">직원별수금정산</a></li>
                    <li>직원별정산현황</li>
                </ul>
            </div>
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <form action="" method="post" name="frmName">
                        <input type="hidden" name="page" value="<?=$page?>">
                        <input type="hidden" name="per"  value="<?=$perPage?>">
                        <table class="table table-bordered table-condensed gridSixteen reserveTable formDetail">
                            <tbody>
                                <tr>
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="seldate">
                                            <option value="">- 선택 -</option>
                                            <option <?php if (($seldate == "1")) { ?> selected <?php } ?> value="1">예약일</option>
                                            <option <?php if ($seldate == "2") { ?> selected <?php } ?> value="2">결제일</option>
                                            <option <?php if ($seldate == "3") { ?> selected <?php } ?> value="3">회계확인</option>
                                            <option <?php if ($seldate == "4") { ?> selected <?php } ?> value="4">취소</option>
                                        </select>
                                    </td>
                                    <td colspan="5">
                                        <div class="row">
                                            <div class="col-sm-5">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="startDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate1" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$startDate?>'>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="endDate" data-date-format='yyyy-mm-dd' class="form-control js-dateInputWithBlocks js-tourDates tourDate2" aria-label="조회기간" placeholder="조회기간" autocomplete='off' value='<?=$endDate?>'>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default js-dateInputBtn" type="button"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td colspan="2" class="text-center formHeader">
                                        <input type="text" id="cname" name="cname" placeholder="고객명" class="inpubase md" value="<?=$cname?>"/>
                                    </td>
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="employeeName">
                                            <option value="">- 선택 -</option>
                                            <?=employeelist($employeeName)?>
                                        </select>
                                    </td>
                                    <td colspan="2" class="text-center formHeader">
                                        <select class="form-control" name="searchpay">
                                            <option value="">정산상태</option>
                                            <option <?php if ($searchpay == "1") { ?> selected <?php } ?> value="1">회계확인</option>
                                            <option <?php if ($searchpay == "2") { ?> selected <?php } ?> value="2">회계확인완료</option>
                                        </select>
                                    </td>
                                    <td colspan="3" class="text-center">
                                        <button type='submit' class="btn btn-primary btn-sm btn1">검색</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>

                    <!-- 일괄 회계확인 버튼 -->
                    <div class="row" style="margin:8px 0;">
                        <div class="col-sm-12 text-right">
                            <button type="button" id="js-bulkConfirm" class="btn btn-success btn-sm">
                                선택 회계확인
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <table id="js-sortTable" class="table table-striped table-bordered table-hover table-condensed js-productTable2">
                                <thead>
                                    <tr>
                                        <th style="width:32px;text-align:center;">
                                            <input type="checkbox" id="js-checkAll">
                                        </th>
                                        <th class="js-sort" data-type="number">예약날짜<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="number">결제일<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="string">상품명<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="string">예약자<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="number">인원<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="number">예약상태<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="number">최종결제금액<br />잔금<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="string">결제방법<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="number">결제금액<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="string">결제자<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="string">결제메모<span class="sort-arrow"></span></th>
                                        <th class="js-sort" data-type="number">정산상태<span class="sort-arrow"></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $totalCount=0; printPay($totalCount); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 페이저 -->
                    <div id="pagerWrap">
                        <?php renderPager($totalCount, $page, $perPage); ?>
                    </div>

                    <br/>
                </div>
            </div>
        </div>
    </div>

    <?php include "include/side_m.php"; ?>

    <script>
        // ===== 간단 테이블 정렬기 =====
        (function(){
          function getCellSortValue(td){
            var v = td.getAttribute('data-sort-value');
            if (v !== null) return v;
            return td.textContent || td.innerText || '';
          }
          function cmp(a,b,type,asc){
            if(type==='number'){
              var x = parseFloat(a)||0, y = parseFloat(b)||0;
              return asc ? (x-y) : (y-x);
            }else{
              a = (a+'').toLowerCase(); b=(b+'').toLowerCase();
              if (a<b) return asc?-1:1;
              if (a>b) return asc?1:-1;
              return 0;
            }
          }
          function clearSortIndicators(ths){ ths.forEach(function(th){ th.classList.remove('asc','desc'); }); }
          function sortTable(table, colIndex, type, asc){
            var tbody = table.tBodies[0];
            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
            rows.sort(function(r1,r2){
              var c1 = r1.children['colIndex'], c2 = r2.children['colIndex'];
              var v1 = getCellSortValue(c1), v2 = getCellSortValue(c2);
              return cmp(v1, v2, type, asc);
            });
            rows.forEach(function(r){ tbody.appendChild(r); });
          }
          window.initClientSorter = function(){
            var table = document.getElementById('js-sortTable');
            if(!table) return;
            var ths = Array.prototype.slice.call(table.tHead.rows[0].cells);
            ths.forEach(function(th, idx){
              if(!th.classList.contains('js-sort')) return;
              th.addEventListener('click', function(){
                var type = th.getAttribute('data-type')||'string';
                var asc  = !th.classList.contains('asc'); // 토글
                clearSortIndicators(ths);
                th.classList.add(asc?'asc':'desc');
                sortTable(table, idx, type, asc);
              });
            });
          };
        })();
    </script>

    <script>
        $(document).ready(function () {
            pt.initReservationDetail();
            pt.initReservationList();

            $('.tourDate1').datepicker({ format: "yyyy-mm-dd", autoclose: true });
            $('.tourDate2').datepicker({ format: "yyyy-mm-dd", autoclose: true });

            // 전체 선택
            $('#js-checkAll').on('change', function(){
                $('.js-rowCheck:not(:disabled)').prop('checked', this.checked);
            });

            // 페이지 클릭 → 동일 폼 제출
            $(document).on('click', '.js-pg', function(e){
                e.preventDefault();
                var p = $(this).data('page');
                $('input[name=page]').val(p);
                document.forms['frmName'].submit();
            });

            // 일괄 회계확인
            $('#js-bulkConfirm').on('click', function(){
                var ids = $('.js-rowCheck:checked').map(function(){ return this.value; }).get();
                if (ids.length === 0) {
                    alert('회계확인할 항목을 선택하세요.');
                    return;
                }
                if (!confirm(ids.length + '건을 회계확인 처리하시겠습니까?')) return;

                var $btn = $(this).prop('disabled', true).text('처리중...');
                $.ajax({
                    type: 'POST',
                    url: 'update_acc_bulk.php',
                    data: { ids: ids },
                    dataType: 'json'
                }).done(function(res){
                    if (res && res.ok) {
                        alert('회계확인 완료: ' + res.updated + '건');
                        $('.js-rowCheck:checked').each(function(){
                            var $tr = $(this).closest('tr');
                            $tr.find('.accspan').text('확인완료');
                            // 정산상태 정렬값 2로 올려줌
                            $tr.find('td:last').attr('data-sort-value','2');
                            $(this).prop('checked', false).prop('disabled', true);
                        });
                        $('#js-checkAll').prop('checked', false);
                    } else {
                        alert(res && res.msg ? res.msg : '처리 중 오류가 발생했습니다.');
                    }
                }).fail(function(){
                    alert('서버 통신 오류');
                }).always(function(){
                    $btn.prop('disabled', false).text('선택 회계확인');
                });
            });

            // 정렬기 활성화
            initClientSorter();
        });

        var ctr=0;
        function openwin(r_code,pcode) {
            var winName = "all_"+(ctr++);
            window.open("pay_hist.php?r_code="+r_code+"&pcode="+pcode,winName,"width=1000,height=600,scrollbars=1");
        }
    </script>
</body>
</html>
s
