<?php
    include "include/header.php";
   // include "include/inc_base.php";
    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] !="") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}


    $mode      = isset($_POST['mode']) ? $_POST['mode'] : '';
    // 기본값: 현재 분기
    $curYear = (int)date('Y');
    $curQ    = (int)ceil(date('n') / 3);
    $qStartMap = [1=>'01-01', 2=>'04-01', 3=>'07-01', 4=>'10-01'];
    $qEndMap   = [1=>'03-31', 2=>'06-30', 3=>'09-30', 4=>'12-31'];
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : ($curYear . '-' . $qStartMap[$curQ]);
    $endDate   = isset($_POST['endDate'])   ? $_POST['endDate']   : ($curYear . '-' . $qEndMap[$curQ]);
    $kinddate  = isset($_POST['kinddate'])  ? $_POST['kinddate']  : '1';
    $cname     = isset($_POST['cname'])     ? trim($_POST['cname'])     : '';
    $crev      = isset($_POST['crev'])      ? trim($_POST['crev'])      : '';
    $rstatus   = isset($_POST['rstatus'])   ? $_POST['rstatus']   : ['READY', 'DONE'];
    $ptype     = isset($_POST['ptype'])     ? (array)$_POST['ptype'] : ['1'];  // 기본: 로컬상품
    $rand_id   = isset($_POST['rand_id'])   ? $_POST['rand_id']   : '';
    $tourtype  = isset($_POST['tourtype'])  ? $_POST['tourtype']  : ['1'];  // 기본: 직접예약
    $branch    = isset($_POST['branch'])    ? (array)$_POST['branch'] : [];

    // 지사 목록 (code_base D02 — product_master.m_dept 기준)
    $branchList = [];
    $brQry = "SELECT lvcode1, lvcode2, lvcode3, comment FROM code_base WHERE lvcode1='D02' AND lvcode2<>'00' AND lvcode3='00' ORDER BY lvcode2 ASC";
    $brRst = mysql_query($brQry, $dbConn);
    while ($brRow = mysql_fetch_assoc($brRst)) {
        $brCode = $brRow['lvcode1'] . $brRow['lvcode2'] . $brRow['lvcode3'];
        $branchList[$brCode] = $brRow['comment'];
    }

    // 엑셀 다운로드
    if ($mode == 'down') {
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"inbound_list_" . date('Ymd') . ".xls\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo "\xEF\xBB\xBF";
    }

    function buildWhere($startDate, $endDate, $kinddate, $cname, $crev, $rstatus, $ptype, $rand_id = '', $tourtype = [], $branch = []) {
        $w = " AND a.parent = 'MAIN' ";
        $ptypeArr = array_filter((array)$ptype);
        if (!empty($ptypeArr)) {
            $plist = implode("','", array_map('addslashes', $ptypeArr));
            $w .= " AND b.p_type IN ('{$plist}')";
        }

        if ($kinddate == '1') {
            if ($startDate) $w .= " AND a.stDate >= '$startDate'";
            if ($endDate)   $w .= " AND a.stDate <= '$endDate'";
        } else {
            if ($startDate) $w .= " AND a.revDate >= '$startDate'";
            if ($endDate)   $w .= " AND a.revDate <= '$endDate'";
        }

        if ($cname != '') {
            $cname_esc = addslashes($cname);
            $w .= " AND (a.book_pri LIKE '%{$cname_esc}%' OR c.traveler_nm LIKE '%{$cname_esc}%' OR c.traveler_enm LIKE '%{$cname_esc}%')";
        }
        if ($crev != '') {
            $crev_esc = addslashes($crev);
            $w .= " AND a.reserveCode LIKE '%{$crev_esc}%'";
        }
        if (!empty($rstatus)) {
            $rlist = implode("','", array_map('addslashes', $rstatus));
            $w .= " AND a.rev_status IN ('{$rlist}')";
        }
        if (!empty($branch)) {
            $bConds = [];
            foreach ($branch as $bc) {
                $bConds[] = "b.m_dept LIKE '%" . addslashes($bc) . "/%'";
            }
            $w .= " AND (" . implode(' OR ', $bConds) . ")";
        }
        if ($rand_id != '') {
            $w .= " AND a.rand_id = '" . addslashes($rand_id) . "'";
        }
        if (!empty($tourtype)) {
            $tlist = implode("','", array_map('addslashes', $tourtype));
            $w .= " AND a.tour_type IN ('{$tlist}')";
        }
        return $w;
    }

    function printList() {
        global $dbConn, $startDate, $endDate, $kinddate, $cname, $crev, $rstatus, $mode, $ptype, $rand_id, $tourtype, $branch;

        $sWhere = buildWhere($startDate, $endDate, $kinddate, $cname, $crev, $rstatus, $ptype, $rand_id, $tourtype, $branch);

        $qry = "SELECT
                    a.reserveCode,
                    a.grand_revNo,
                    a.revDate,
                    a.stDate,
                    a.p_name,
                    a.book_pri,
                    a.p_cnt,
                    a.rev_status,
                    a.base_rate,
                    a.last_total,
                    a.last_bal,
                    c.traveler_nm,
                    c.traveler_enm,
                    c.traveler_phone,
                    c.traveler_birth,
                    c.seqint,
                    a.rand_id
                FROM reserve_info a
                INNER JOIN product_master b ON a.p_code = b.p_code
                INNER JOIN reserve_traveler c ON a.reserveCode = c.reserveCode
                $sWhere
                ORDER BY a.stDate ASC, a.reserveCode ASC, c.seqint ASC";

        $rst = mysql_query($qry, $dbConn);
        if (!$rst) { echo "<tr><td colspan='14'>쿼리 오류</td></tr>"; return; }

        // 예약번호가 바뀔 때마다 색상 교체 (0=흰색, 1=연파랑)
        $k = 0;
        $colorIdx    = 0;
        $prevRevCode = '';
        $rowColors   = ['#ffffff', '#cce5ff'];   // 짝수그룹=흰색, 홀수그룹=파랑

        while ($row = mysql_fetch_assoc($rst)) {
            $k++;

            // 예약번호가 바뀌면 색상 토글
            $isFirstInGroup = false;
            if ($row['reserveCode'] !== $prevRevCode) {
                if ($prevRevCode !== '') $colorIdx = ($colorIdx + 1) % 2;
                $prevRevCode    = $row['reserveCode'];
                $isFirstInGroup = true;
            }
            $bg        = $rowColors[$colorIdx];
            $borderTop = $isFirstInGroup ? 'border-top:2px solid #888;' : '';
            $bgStyle   = "style='background:{$bg};{$borderTop}'";

            switch ($row['rev_status']) {
                case 'READY':  $rstLabel = '예약접수'; break;
                case 'DONE':   $rstLabel = '예약확정'; break;
                case 'CANCEL': $rstLabel = '예약취소'; break;
                default:       $rstLabel = $row['rev_status'];
            }
            $bal      = (float)$row['last_total'] - (float)$row['last_bal'];
            $sign     = ($row['base_rate'] == 'CAD') ? 'C$' : 'U$';
            $compInfo = ($row['rand_id'] != '') ? randname($row['rand_id']) : [];
            $compNm   = !empty($compInfo['kor_name']) ? $compInfo['kor_name'] : '';

            if ($mode == 'down') {
                echo "<tr>
                    <td>{$k}</td>
                    <td>{$row['reserveCode']}</td>
                    <td>{$row['revDate']}</td>
                    <td>{$row['stDate']}</td>
                    <td>{$row['p_name']}</td>
                    <td>{$compNm}</td>
                    <td>{$row['p_cnt']}</td>
                    <td>{$row['traveler_nm']}</td>
                    <td>{$row['traveler_enm']}</td>
                    <td>{$row['traveler_phone']}</td>
                    <td>{$row['traveler_birth']}</td>
                    <td>{$rstLabel}</td>
                    <td>{$sign}" . number_format($row['last_total'], 2) . "</td>
                    <td>{$sign}" . number_format($bal, 2) . "</td>
                </tr>\n";
            } else {
                $revLink = "base_reservation_m.php?estimateCode={$row['reserveCode']}&division=3&pdx=2&sub=20&ty=2&pricet=2#TOP";
                echo "<tr {$bgStyle}>
                    <td class='text-center'>{$k}</td>
                    <td class='text-center'><a href='{$revLink}' target='_blank'>{$row['reserveCode']}</a></td>
                    <td class='text-center'>{$row['revDate']}</td>
                    <td class='text-center'>{$row['stDate']}</td>
                    <td>{$row['p_name']}</td>
                    <td class='text-center'>{$compNm}</td>
                    <td class='text-center'>{$row['p_cnt']}</td>
                    <td class='text-center'><strong>{$row['traveler_nm']}</strong></td>
                    <td class='text-center'>{$row['traveler_enm']}</td>
                    <td class='text-center'>{$row['traveler_phone']}</td>
                    <td class='text-center'>{$row['traveler_birth']}</td>
                    <td class='text-center'>{$rstLabel}</td>
                    <td class='text-right'>{$sign}" . number_format($row['last_total'], 2) . "</td>
                    <td class='text-right'>{$sign}" . number_format($bal, 2) . "</td>
                </tr>\n";
            }
        }

        if ($k == 0) {
            echo "<tr><td colspan='14' class='text-center'>조회된 데이터가 없습니다.</td></tr>";
        }
    }

    function printSummary() {
        global $dbConn, $startDate, $endDate, $kinddate, $cname, $crev, $rstatus, $ptype, $rand_id, $tourtype, $branch;

        $sWhere = buildWhere($startDate, $endDate, $kinddate, $cname, $crev, $rstatus, $ptype, $rand_id, $tourtype, $branch);

        /* reserve_traveler JOIN 시 여행자 수만큼 금액이 중복 합산되므로
           DISTINCT 서브쿼리로 먼저 예약 1건씩 추려낸 뒤 집계한다. */
        $qry = "SELECT
                    COUNT(*) AS tot_rev,
                    SUM(x.p_cnt) AS tot_pcnt,
                    SUM(x.last_total) AS tot_amt,
                    SUM(x.last_total - x.last_bal) AS tot_paid,
                    SUM(x.last_bal) AS tot_bal
                FROM (
                    SELECT DISTINCT a.reserveCode, a.p_cnt, a.last_total, a.last_bal
                    FROM reserve_info a
                    INNER JOIN product_master b ON a.p_code = b.p_code
                    INNER JOIN reserve_traveler c ON a.reserveCode = c.reserveCode
                    $sWhere
                ) x";

        $rst = mysql_query($qry, $dbConn);
        $row = mysql_fetch_assoc($rst);
        return $row;
    }
?>

<?php if ($mode == 'down'): ?>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
<body>
<table border="1">
<tr>
    <th>No</th>
    <th>예약번호</th>
    <th>접수일</th>
    <th>출발일</th>
    <th>상품명</th>
    <th>업체명</th>
    <th>인원</th>
    <th>고객명(한글)</th>
    <th>고객명(영문)</th>
    <th>전화번호</th>
    <th>생년월일</th>
    <th>접수상태</th>
    <th>결제금액</th>
    <th>받은금액</th>
</tr>
<?php printList(); ?>
</table>
</body>
</html>
<?php
    exit;
endif;
?>

<div id="contentwrapper" class="productDetailForm">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">예약현황</a></li>
                <li>예약고객현황</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="<?= $PHP_SELF ?>" method="post" name="frm1" id="frm1">
                    <input type="hidden" name="mode" id="mode" value="">
                    <table class="table table-bordered table-condensed">
                        <tr>
                            <td width="10%" class="titletd text-center">분기선택</td>
                            <td colspan="3">
                                <?php
                                $btnYear = (int)date('Y');
                                // 현재 선택된 startDate로 활성 분기 계산
                                $selMonth = (int)substr($startDate, 5, 2);
                                $selQ     = $selMonth > 0 ? (int)ceil($selMonth / 3) : 0;
                                foreach ([1,2,3,4] as $q) {
                                    $active = ($q == $selQ) ? 'btn-info' : 'btn-default';
                                    echo "<button type='button' class='btn btn-sm {$active} js-quarter' data-year='{$btnYear}' data-q='{$q}' style='margin:2px'>{$q}분기</button>";
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td width="10%" class="titletd text-center">기간종류</td>
                            <td width="40%">
                                <label class="radio-inline">
                                    <input type="radio" name="kinddate" value="1" <?php if ($kinddate=='1') echo 'checked'; ?>> 출발일
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="kinddate" value="2" <?php if ($kinddate=='2') echo 'checked'; ?>> 접수일
                                </label>
                            </td>
                            <td width="10%" class="titletd text-center">기간</td>
                            <td width="40%">
                                <div class="row">
                                    <div class="col-sm-5">
                                        <input type="search" name="startDate" id="startDate" class="inpubase js-datepicker" value="<?= $startDate ?>" placeholder="시작일" autocomplete="off" />
                                    </div>
                                    <div class="col-sm-5">
                                        <input type="search" name="endDate" id="endDate" class="inpubase js-datepicker" value="<?= $endDate ?>" placeholder="종료일" autocomplete="off" />
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="10%" class="titletd text-center">투어분류</td>
                            <td colspan="3">
                                <?php
                                $ptypeMap = ['1'=>'로컬상품', '2'=>'인바운드', '4'=>'인센티브', '5'=>'아웃바운드'];
                                foreach ($ptypeMap as $val => $label) {
                                    $checked = in_array($val, $ptype) ? 'checked' : '';
                                    echo "<label class='check-inline'><input type='checkbox' name='ptype[]' value='{$val}' {$checked}> {$label}</label>";
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td width="10%" class="titletd text-center">지사</td>
                            <td width="40%">
                                <?php foreach ($branchList as $bCode => $bName):
                                    $bc = in_array($bCode, $branch) ? 'checked' : ''; ?>
                                <label class="check-inline">
                                    <input type="checkbox" name="branch[]" value="<?= $bCode ?>" class="js-branch" <?= $bc ?>> <?= $bName ?>
                                </label>
                                <?php endforeach; ?>
                            </td>
                            <td width="10%" class="titletd text-center">업체</td>
                            <td width="40%">
                                <select name="rand_id" id="rand_id" class="form-control">
                                    <option value="">- 전체 업체 -</option>
                                    <?= printCompanySelect($rand_id) ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="10%" class="titletd text-center">고객명/예약자</td>
                            <td width="40%">
                                <input type="text" name="cname" id="cname" class="inpubase" value="<?= htmlspecialchars($cname) ?>" placeholder="고객명(한글/영문)" />
                            </td>
                            <td width="10%" class="titletd text-center">예약번호</td>
                            <td width="40%">
                                <input type="text" name="crev" id="crev" class="inpubase" value="<?= htmlspecialchars($crev) ?>" />
                            </td>
                        </tr>
                        <tr>
                            <td width="10%" class="titletd text-center">예약경로</td>
                            <td colspan="3">
                                <label class="check-inline">
                                    <input type="checkbox" name="tourtype[]" value="1" <?php if (in_array('1', $tourtype)) echo 'checked'; ?>> 직접예약
                                </label>
                                <label class="check-inline">
                                    <input type="checkbox" name="tourtype[]" value="2" <?php if (in_array('2', $tourtype)) echo 'checked'; ?>> 웹예약
                                </label>
                                <label class="check-inline">
                                    <input type="checkbox" name="tourtype[]" value="3" <?php if (in_array('3', $tourtype)) echo 'checked'; ?>> 업체예약
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td width="10%" class="titletd text-center">접수상태</td>
                            <td colspan="3">
                                <label class="check-inline">
                                    <input type="checkbox" name="rstatus[]" value="READY" <?php if (in_array('READY', $rstatus)) echo 'checked'; ?>> 예약접수
                                </label>
                                <label class="check-inline">
                                    <input type="checkbox" name="rstatus[]" value="DONE" <?php if (in_array('DONE', $rstatus)) echo 'checked'; ?>> 예약확정
                                </label>
                                <label class="check-inline">
                                    <input type="checkbox" name="rstatus[]" value="CANCEL" <?php if (in_array('CANCEL', $rstatus)) echo 'checked'; ?>> 예약취소
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-center">
                                <button type="button" class="btn btn-primary btn-sm js-search">검색</button>
                                &nbsp;
                                <button type="button" class="btn btn-success btn-sm js-excel">엑셀 다운로드</button>
                            </td>
                        </tr>
                    </table>
                </form>

                <?php
                $summary = printSummary();
                ?>
                <div class="row" style="margin-bottom:12px;">
                    <div class="col-sm-12">
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <div style="flex:1;min-width:120px;border:1px solid #ddd;border-radius:6px;padding:10px 14px;background:#fff;text-align:center;">
                                <div style="font-size:12px;color:#888;margin-bottom:4px;">총 예약건</div>
                                <div style="font-size:20px;font-weight:bold;color:#337ab7;"><?= number_format($summary['tot_rev']) ?></div>
                            </div>
                            <div style="flex:1;min-width:120px;border:1px solid #ddd;border-radius:6px;padding:10px 14px;background:#fff;text-align:center;">
                                <div style="font-size:12px;color:#888;margin-bottom:4px;">총 인원</div>
                                <div style="font-size:20px;font-weight:bold;color:#5cb85c;"><?= number_format($summary['tot_pcnt']) ?></div>
                            </div>
                            <div style="flex:1;min-width:130px;border:1px solid #ddd;border-radius:6px;padding:10px 14px;background:#fff;text-align:center;">
                                <div style="font-size:12px;color:#888;margin-bottom:4px;">총 결제금액</div>
                                <div style="font-size:20px;font-weight:bold;color:#d9534f;">$<?= number_format($summary['tot_amt'], 2) ?></div>
                            </div>
                            <div style="flex:1;min-width:130px;border:1px solid #ddd;border-radius:6px;padding:10px 14px;background:#fff;text-align:center;">
                                <div style="font-size:12px;color:#888;margin-bottom:4px;">받은금액</div>
                                <div style="font-size:20px;font-weight:bold;color:#5cb85c;">$<?= number_format($summary['tot_paid'], 2) ?></div>
                            </div>
                            <div style="flex:1;min-width:130px;border:1px solid #ddd;border-radius:6px;padding:10px 14px;background:#fff;text-align:center;">
                                <div style="font-size:12px;color:#888;margin-bottom:4px;">잔액</div>
                                <div style="font-size:20px;font-weight:bold;color:#f0ad4e;">$<?= number_format($summary['tot_bal'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <div style="overflow-x:auto;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="listTable">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th class="text-center">예약번호</th>
                                    <th class="text-center">접수일</th>
                                    <th class="text-center">출발일</th>
                                    <th class="text-center">상품명</th>
                                    <th class="text-center">업체명</th>
                                    <th class="text-center">인원</th>
                                    <th class="text-center">고객명<h6>Korean</h6></th>
                                    <th class="text-center">영문명<h6>English</h6></th>
                                    <th class="text-center">전화번호</th>
                                    <th class="text-center">생년월일</th>
                                    <th class="text-center">접수상태</th>
                                    <th class="text-center">결제금액</th>
                                    <th class="text-center">받은금액</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php printList(); ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<style>
    #listTable td, #listTable th { font-size: 12px; white-space: nowrap; }
    #listTable a { color: #000 !important; }
    /* DataTables 정렬 화살표 색상 */
    #listTable thead th { cursor: pointer; }
    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after { opacity: 0.8; }
</style>
<script>
$(document).ready(function () {
    $('.js-datepicker').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true
    });

    // 지사 라디오 → 업체 드롭다운 필터
    var $compSel  = $('#rand_id');
    var allOptions = $compSel.find('option').clone();

    function filterByBranch() {
        var checked = [];
        $('.js-branch:checked').each(function () { checked.push($(this).val()); });
        var prev = $compSel.val();
        $compSel.empty();
        allOptions.each(function () {
            if (checked.length === 0 || $(this).val() === '') {
                $compSel.append($(this).clone());
            } else {
                var txt = $(this).text();
                var match = false;
                $.each(checked, function (i, code) {
                        if (txt.indexOf('[' + code) !== -1) { match = true; }
                });
                if (match) $compSel.append($(this).clone());
            }
        });
        if ($compSel.find('option[value="' + prev + '"]').length) {
            $compSel.val(prev);
        }
    }

    $(document).on('change', '.js-branch', function () {
        filterByBranch();
    });

    // 페이지 로드 시 지사 필터 + 업체 선택값 복원
    var savedRandId = '<?= addslashes($rand_id) ?>';
    filterByBranch();
    if (savedRandId !== '') {
        $compSel.val(savedRandId);
    }

    // 분기 버튼 → 날짜 자동 세팅 후 검색
    var qStart = { 1:'01-01', 2:'04-01', 3:'07-01', 4:'10-01' };
    var qEnd   = { 1:'03-31', 2:'06-30', 3:'09-30', 4:'12-31' };
    $(document).on('click', '.js-quarter', function () {
        var yr = $(this).data('year');
        var q  = $(this).data('q');
        $('#startDate').val(yr + '-' + qStart[q]);
        $('#endDate').val(yr + '-' + qEnd[q]);
        $('.js-quarter').removeClass('btn-info').addClass('btn-default');
        $(this).removeClass('btn-default').addClass('btn-info');
        $('#mode').val('');
        $('#frm1').submit();
    });

    $('.js-search').click(function () {
        $('#mode').val('');
        $('#frm1').submit();
    });

    $('.js-excel').click(function () {
        $('#frm1').attr('action', 'inbound_customer_excel.php').submit();
        $('#frm1').attr('action', '');
    });

    // DataTables 소팅 적용 (그룹 배경색 유지)
    var dt = $('#listTable').DataTable({
        paging:   false,
        info:     false,
        filter:   false,
        scrollX:  true,
        ordering: true,
        order:    [[3, 'asc']], // 기본: 출발일 오름차순
        columnDefs: [
            { orderable: false, targets: [0] }  // No 컬럼은 소팅 제외
        ],
        // 소팅 후 그룹별 색상 재적용
        drawCallback: function () {
            var prevCode = '', colorIdx = 0;
            var colors   = ['#ffffff', '#cce5ff'];
            $('#listTable tbody tr').each(function () {
                var code = $(this).find('td:eq(1)').text().trim();
                if (code !== prevCode) {
                    if (prevCode !== '') colorIdx = (colorIdx + 1) % 2;
                    prevCode = code;
                }
                $(this).css('background-color', colors[colorIdx]);
            });
        }
    });
});
</script>
</body>
</html>
