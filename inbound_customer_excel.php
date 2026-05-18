<?php
    include "include/inc_base.php";

    if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] != "") {
    } else {
        exit;
    }

    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
    $endDate   = isset($_POST['endDate'])   ? $_POST['endDate']   : '';
    $kinddate  = isset($_POST['kinddate'])  ? $_POST['kinddate']  : '1';
    $cname     = isset($_POST['cname'])     ? trim($_POST['cname'])     : '';
    $crev      = isset($_POST['crev'])      ? trim($_POST['crev'])      : '';
    $rstatus   = isset($_POST['rstatus'])   ? $_POST['rstatus']   : [];
    $ptype     = isset($_POST['ptype'])     ? (array)$_POST['ptype']   : [];
    $rand_id   = isset($_POST['rand_id'])   ? $_POST['rand_id']         : '';
    $tourtype  = isset($_POST['tourtype'])  ? $_POST['tourtype']        : [];

    // ── WHERE 조건 ──────────────────────────────────────────
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
        $e = addslashes($cname);
        $w .= " AND (a.book_pri LIKE '%{$e}%' OR c.traveler_nm LIKE '%{$e}%' OR c.traveler_enm LIKE '%{$e}%')";
    }
    if ($crev != '') {
        $w .= " AND a.reserveCode LIKE '%" . addslashes($crev) . "%'";
    }
    if (!empty($rstatus)) {
        $rlist = implode("','", array_map('addslashes', $rstatus));
        $w .= " AND a.rev_status IN ('{$rlist}')";
    }
    if ($rand_id != '') {
        $w .= " AND a.rand_id = '" . addslashes($rand_id) . "'";
    }
    if (!empty($tourtype)) {
        $tlist = implode("','", array_map('addslashes', $tourtype));
        $w .= " AND a.tour_type IN ('{$tlist}')";
    }

    // ── 합계 쿼리 ───────────────────────────────────────────
    $sumQry = "SELECT COUNT(*) AS tot_rev,
                      SUM(x.p_cnt) AS tot_pcnt,
                      SUM(x.last_total) AS tot_amt,
                      SUM(x.last_total - x.last_bal) AS tot_paid,
                      SUM(x.last_bal) AS tot_bal
               FROM (
                   SELECT DISTINCT a.reserveCode, a.p_cnt, a.last_total, a.last_bal
                   FROM reserve_info a
                   INNER JOIN product_master b ON a.p_code = b.p_code
                   INNER JOIN reserve_traveler c ON a.reserveCode = c.reserveCode
                   $w
               ) x";
    $sumRst = mysql_query($sumQry, $dbConn);
    $sum    = mysql_fetch_assoc($sumRst);

    // ── 명단 쿼리 ───────────────────────────────────────────
    $qry = "SELECT
                a.reserveCode, a.revDate, a.stDate, a.p_name,
                a.tour_type, a.p_cnt, a.rev_status, a.base_rate,
                a.last_total, a.last_bal, a.rand_id,
                c.traveler_nm, c.traveler_enm, c.traveler_phone,
                c.traveler_birth, c.seqint
            FROM reserve_info a
            INNER JOIN product_master b ON a.p_code = b.p_code
            INNER JOIN reserve_traveler c ON a.reserveCode = c.reserveCode
            $w
            ORDER BY a.stDate ASC, a.reserveCode ASC, c.seqint ASC";
    $rst = mysql_query($qry, $dbConn);

    // ── 필터 조건 레이블 ────────────────────────────────────
    $kindLabel = ($kinddate == '1') ? '출발일' : '접수일';

    $ptypeMap   = ['1'=>'로컬상품','2'=>'인바운드','4'=>'인센티브','5'=>'아웃바운드'];
    $ptypeLabel = !empty($ptypeArr)
        ? implode(', ', array_map(function($v) use ($ptypeMap){ return $ptypeMap[$v] ?? $v; }, $ptypeArr))
        : '전체';

    $ttMap    = ['1'=>'직접예약','2'=>'웹예약','3'=>'업체예약'];
    $ttLabel  = !empty($tourtype)
        ? implode(', ', array_map(function($v) use ($ttMap){ return $ttMap[$v] ?? $v; }, $tourtype))
        : '전체';

    $rstMap   = ['READY'=>'예약접수','DONE'=>'예약확정','CANCEL'=>'예약취소'];
    $rstLabel = !empty($rstatus)
        ? implode(', ', array_map(function($v) use ($rstMap){ return $rstMap[$v] ?? $v; }, $rstatus))
        : '전체';

    $compLabel = '';
    if ($rand_id != '') {
        $ci = randname($rand_id);
        $compLabel = $ci['kor_name'] ?? $rand_id;
    } else {
        $compLabel = '전체';
    }

    // ── 엑셀 출력 헤더 ──────────────────────────────────────
    $fname = 'customer_list_' . date('Ymd') . '.xls';
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"{$fname}\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF";
?>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
<body>

<!-- ① 필터 조건표 -->
<table border="1" style="margin-bottom:6px;">
    <tr style="background:#D9E1F2;font-weight:bold;">
        <td colspan="4" align="center" style="font-size:14px;">■ 조회 조건</td>
    </tr>
    <tr>
        <td style="background:#BDD7EE;font-weight:bold;" width="80">기간종류</td>
        <td width="160"><?= $kindLabel ?></td>
        <td style="background:#BDD7EE;font-weight:bold;" width="80">조회기간</td>
        <td width="200"><?= $startDate ?> ~ <?= $endDate ?></td>
    </tr>
    <tr>
        <td style="background:#BDD7EE;font-weight:bold;">투어분류</td>
        <td><?= $ptypeLabel ?></td>
        <td style="background:#BDD7EE;font-weight:bold;">예약경로</td>
        <td><?= $ttLabel ?></td>
    </tr>
    <tr>
        <td style="background:#BDD7EE;font-weight:bold;">접수상태</td>
        <td><?= $rstLabel ?></td>
        <td style="background:#BDD7EE;font-weight:bold;">업체</td>
        <td><?= $compLabel ?></td>
    </tr>
    <?php if ($cname != ''): ?>
    <tr>
        <td style="background:#BDD7EE;font-weight:bold;">고객명</td>
        <td colspan="3"><?= htmlspecialchars($cname) ?></td>
    </tr>
    <?php endif; ?>
</table>

<!-- ② 합계 요약표 -->
<table border="1" style="margin-bottom:10px;">
    <tr style="background:#E2EFDA;font-weight:bold;">
        <td colspan="5" align="center" style="font-size:14px;">■ 합계</td>
    </tr>
    <tr style="background:#E2EFDA;font-weight:bold;" align="center">
        <td width="80">총 예약건</td>
        <td width="80">총 인원</td>
        <td width="130">총 결제금액</td>
        <td width="130">받은금액</td>
        <td width="130">잔액</td>
    </tr>
    <tr align="center">
        <td><?= number_format($sum['tot_rev']) ?></td>
        <td><?= number_format($sum['tot_pcnt']) ?></td>
        <td align="right">$<?= number_format($sum['tot_amt'], 2) ?></td>
        <td align="right">$<?= number_format($sum['tot_paid'], 2) ?></td>
        <td align="right">$<?= number_format($sum['tot_bal'], 2) ?></td>
    </tr>
</table>

<!-- ③ 명단 -->
<table border="1">
<tr style="background:#4472C4;color:#fff;font-weight:bold;">
    <th>No</th>
    <th>예약번호</th>
    <th>접수일</th>
    <th>출발일</th>
    <th>상품명</th>
    <th>예약경로</th>
    <th>업체명</th>
    <th>인원</th>
    <th>고객명(한글)</th>
    <th>고객명(영문)</th>
    <th>전화번호</th>
    <th>생년월일</th>
    <th>접수상태</th>
    <th>통화</th>
    <th>결제금액</th>
    <th>받은금액</th>
    <th>잔액</th>
</tr>
<?php
    $k          = 0;
    $prevCode   = '';
    $colorIdx   = 0;
    $rowColors  = ['#FFFFFF', '#DDEEFF'];
    $pInfoCache = [];
    while ($row = mysql_fetch_assoc($rst)) {
        $k++;

        switch ($row['tour_type']) {
            case '1': $ttRow = '직접예약'; break;
            case '2': $ttRow = '웹예약';   break;
            case '3': $ttRow = '업체예약'; break;
            default:  $ttRow = $row['tour_type'];
        }
        switch ($row['rev_status']) {
            case 'READY':  $rstRow = '예약접수'; break;
            case 'DONE':   $rstRow = '예약확정'; break;
            case 'CANCEL': $rstRow = '예약취소'; break;
            default:       $rstRow = $row['rev_status'];
        }

        $compNm = '';
        if ($row['rand_id'] != '') {
            if (!isset($pInfoCache[$row['rand_id']])) {
                $pInfoCache[$row['rand_id']] = randname($row['rand_id']);
            }
            $compNm = $pInfoCache[$row['rand_id']]['kor_name'] ?? '';
        }

        $sign = ($row['base_rate'] == 'CAD') ? 'C$' : 'U$';
        $paid = (float)$row['last_total'] - (float)$row['last_bal'];
        $bal  = (float)$row['last_bal'];

        $isNew = ($row['reserveCode'] !== $prevCode);
        if ($isNew && $prevCode !== '') $colorIdx = ($colorIdx + 1) % 2;
        $prevCode = $row['reserveCode'];
        $bg     = $rowColors[$colorIdx];
        $border = $isNew && $prevCode !== '' ? "border-top:2px solid #888;" : '';
        $style  = " style='background:{$bg};{$border}'";

        echo "<tr{$style}>
            <td align='center'>{$k}</td>
            <td align='center'>{$row['reserveCode']}</td>
            <td align='center'>{$row['revDate']}</td>
            <td align='center'>{$row['stDate']}</td>
            <td>{$row['p_name']}</td>
            <td align='center'>{$ttRow}</td>
            <td align='center'>{$compNm}</td>
            <td align='center'>{$row['p_cnt']}</td>
            <td align='center'><b>{$row['traveler_nm']}</b></td>
            <td align='center'>{$row['traveler_enm']}</td>
            <td align='center'>{$row['traveler_phone']}</td>
            <td align='center'>{$row['traveler_birth']}</td>
            <td align='center'>{$rstRow}</td>
            <td align='center'>{$sign}</td>
            <td align='right'>" . number_format($row['last_total'], 2) . "</td>
            <td align='right'>" . number_format($paid, 2) . "</td>
            <td align='right'>" . number_format($bal, 2) . "</td>
        </tr>\n";
    }
?>
</table>
</body>
</html>
