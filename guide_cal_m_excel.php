<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_USER_DEPRECATED);

ob_start();
include "include/header.php";
ob_end_clean();

if (empty($_COOKIE['MEMLOGIN_ADMIN_PURUN'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

function guideExcelLoadPHPExcel()
{
    if (class_exists('PHPExcel') && class_exists('PHPExcel_IOFactory')) {
        return true;
    }

    $basePath = __DIR__ . "/lib/PHPExcel/Classes/";
    if (!file_exists($basePath . "PHPExcel.php") || !file_exists($basePath . "PHPExcel/IOFactory.php")) {
        return false;
    }

    require_once $basePath . "PHPExcel.php";
    require_once $basePath . "PHPExcel/IOFactory.php";

    return class_exists('PHPExcel') && class_exists('PHPExcel_IOFactory');
}

function guideExcelFetchOne($dbConn, $sql, $types = '', $params = array())
{
    $stmt = mysqli_prepare($dbConn, $sql);
    if (!$stmt) {
        return null;
    }
    if ($types !== '' && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return null;
    }
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row;
}

function guideExcelFetchAll($dbConn, $sql, $types = '', $params = array())
{
    $stmt = mysqli_prepare($dbConn, $sql);
    if (!$stmt) {
        return array();
    }
    if ($types !== '' && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return array();
    }
    $res = mysqli_stmt_get_result($stmt);
    $rows = array();
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function guideExcelNum($value)
{
    if ($value === null || $value === '') {
        return 0;
    }
    return (float)str_replace(',', '', (string)$value);
}

function guideExcelVal($row, $key, $default = '')
{
    return isset($row[$key]) ? $row[$key] : $default;
}

function guideExcelCodeLabel($codeMap, $code, $default = '')
{
    $code = trim((string)$code);
    if ($code !== '' && isset($codeMap[$code])) {
        return $codeMap[$code];
    }
    return $default !== '' ? $default : $code;
}

function guideExcelRatioLabel($value)
{
    if ($value === '55') return '5:5';
    if ($value === '64') return '6:4';
    if ($value === '73') return '7:3';
    return (string)$value;
}

function guideExcelSumFormula($column, $startRow, $endRow)
{
    if ($endRow < $startRow) {
        return '=0';
    }
    return '=SUM(' . $column . $startRow . ':' . $column . $endRow . ')';
}

function guideExcelMoneyStyle()
{
    return '#,##0.00;[Red]-#,##0.00;0.00';
}

function guideExcelCleanFilename($name)
{
    $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$name);
    return trim($name, '_');
}

function guideExcelSectionTitle($sheet, $row, $title, $endColumn)
{
    $range = 'A' . $row . ':' . $endColumn . $row;
    $sheet->mergeCells($range);
    $sheet->setCellValue('A' . $row, $title);
    $sheet->getStyle($range)->applyFromArray(array(
        'font' => array('bold' => true, 'size' => 12, 'color' => array('rgb' => 'FFFFFF')),
        'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '355C7D')),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        )
    ));
    $sheet->getRowDimension($row)->setRowHeight(24);
}

function guideExcelHeaderRow($sheet, $row, $headers)
{
    $col = 0;
    foreach ($headers as $header) {
        $cell = PHPExcel_Cell::stringFromColumnIndex($col) . $row;
        $sheet->setCellValue($cell, $header);
        $col++;
    }
    $end = 'K' . $row;
    $sheet->getStyle('A' . $row . ':' . $end)->applyFromArray(array(
        'font' => array('bold' => true, 'color' => array('rgb' => '22313F')),
        'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'D9EAF7')),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
        ),
        'borders' => array(
            'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'B9C6D3'))
        )
    ));
}

function guideExcelApplyTableStyle($sheet, $range)
{
    $sheet->getStyle($range)->applyFromArray(array(
        'borders' => array(
            'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9'))
        ),
        'alignment' => array('vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER)
    ));
}

function guideExcelNoDataRow($sheet, $row, $endColumn)
{
    $sheet->mergeCells('A' . $row . ':' . $endColumn . $row);
    $sheet->setCellValue('A' . $row, '자료 없음');
    $sheet->getStyle('A' . $row . ':' . $endColumn . $row)->applyFromArray(array(
        'font' => array('italic' => true, 'color' => array('rgb' => '777777')),
        'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
    ));
}

$seqno = isset($_GET['number']) ? (int)$_GET['number'] : 0;
if ($seqno <= 0) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

if (!guideExcelLoadPHPExcel()) {
    http_response_code(500);
    echo 'PHPExcel library is not available.';
    exit;
}

$dataRow = guideExcelFetchOne(
    $dbConn,
    "SELECT a.*, ml.kor_name AS kr_name, pm.base_rate AS base_rate
       FROM tour_guide a
       LEFT JOIN member_list ml ON ml.userid = a.guide_id AND ml.division = 'guide'
       LEFT JOIN product_master pm ON pm.p_code = a.p_code
      WHERE a.seq_no = ?
      LIMIT 1",
    'i',
    array($seqno)
);

if (!$dataRow) {
    http_response_code(404);
    echo 'Data not found.';
    exit;
}

$guideCode = getGuideCode($dataRow['grand_eCode'], $dataRow['sub_eCode']);
$settleCode = isset($guideCode['settle_code']) ? (string)$guideCode['settle_code'] : '';
if ($settleCode === '') {
    http_response_code(404);
    echo 'Settle code not found.';
    exit;
}

$mainPcnt = getGuideMainPcnt($dataRow['p_code'], $dataRow['stDate']);
$subPcnt = getGuideSubPcnt($dataRow['p_code'], $dataRow['stDate']);
$mainCnt = (int)guideExcelVal($mainPcnt, 'p_cnt', 0);
$subCnt = (int)guideExcelVal($subPcnt, 'p_cnt', 0);
$totalCnt = $mainCnt + $subCnt;

$setMaster = guideExcelFetchOne($dbConn, "SELECT * FROM guide_setmaster WHERE settle_code = ? LIMIT 1", 's', array($settleCode));
$guideMemo = '';
if (isset($setMaster['guide_memo']) && $setMaster['guide_memo'] !== '') {
    $guideMemo = $setMaster['guide_memo'];
}

$codeRows = guideExcelFetchAll(
    $dbConn,
    "SELECT lvcode1, lvcode2, comment FROM code_base
      WHERE lvcode1 IN ('J01','J02','J03','J04','J05','G01','G02','G03','G04','G05')
        AND lvcode2 <> '00'
        AND lvcode3 = '00'"
);
$codeMap = array();
foreach ($codeRows as $row) {
    $codeMap[$row['lvcode1'] . '|' . $row['lvcode2']] = $row['comment'];
    $codeMap[$row['comment']] = $row['comment'];
}

$mealRows = guideExcelFetchAll($dbConn, "SELECT * FROM guide_meal WHERE settle_code = ? ORDER BY FIELD(meal_type,'bf','lunch','dinner'), seq_no", 's', array($settleCode));
$admissions = guideExcelFetchAll($dbConn, "SELECT * FROM guide_admission WHERE settle_code = ? ORDER BY seq_no", 's', array($settleCode));
$options = guideExcelFetchAll($dbConn, "SELECT * FROM guide_option WHERE settle_code = ? ORDER BY seq_no", 's', array($settleCode));
$etcRows = guideExcelFetchAll($dbConn, "SELECT * FROM guide_etcamt WHERE settle_code = ? ORDER BY FIELD(etc_pricety,'guide','car','etc'), seq_no", 's', array($settleCode));
$shopping = guideExcelFetchAll($dbConn, "SELECT * FROM guide_shopping WHERE settle_code = ? ORDER BY seq_no", 's', array($settleCode));
$inputs = guideExcelFetchAll($dbConn, "SELECT * FROM guide_inputamt WHERE settle_code = ? ORDER BY seq_no", 's', array($settleCode));
$checks = guideExcelFetchAll($dbConn, "SELECT * FROM guide_set_check WHERE settle_code = ? ORDER BY id", 's', array($settleCode));

$excel = new PHPExcel();
$excel->getProperties()
    ->setCreator('MYPRT')
    ->setTitle('Guide Settlement')
    ->setSubject($settleCode);

$sheet = $excel->setActiveSheetIndex(0);
$sheet->setTitle('Guide Settlement');
$sheet->setShowGridLines(false);
$sheet->getDefaultStyle()->getFont()->setName('Malgun Gothic')->setSize(10);
$sheet->getSheetView()->setZoomScale(90);
$sheet->freezePane('A10');

$widths = array('A' => 16, 'B' => 16, 'C' => 22, 'D' => 12, 'E' => 14, 'F' => 14, 'G' => 14, 'H' => 14, 'I' => 14, 'J' => 14, 'K' => 14);
foreach ($widths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

$titleStyle = array(
    'font' => array('bold' => true, 'size' => 20, 'color' => array('rgb' => '1F2933')),
    'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT)
);
$labelStyle = array(
    'font' => array('bold' => true, 'color' => array('rgb' => '334E68')),
    'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'EEF4F8')),
    'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
);
$valueStyle = array(
    'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT),
    'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'D6DEE6')))
);
$totalStyle = array(
    'font' => array('bold' => true),
    'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'FFF3CD')),
    'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9A227')))
);

$sheet->mergeCells('A1:K1');
$sheet->setCellValue('A1', '가이드 정산서');
$sheet->getStyle('A1:K1')->applyFromArray($titleStyle);
$sheet->getRowDimension(1)->setRowHeight(32);

$sheet->mergeCells('A2:K2');
$sheet->setCellValue('A2', '정산코드: ' . $settleCode . '    생성일시: ' . date('Y-m-d H:i'));
$sheet->getStyle('A2:K2')->getFont()->setColor(new PHPExcel_Style_Color('FF52606D'));

$infoRows = array(
    array('행사명', guideExcelVal($dataRow, 'p_name'), '행사코드', guideExcelVal($dataRow, 'sub_eCode'), '행사일', guideExcelVal($dataRow, 'stDate')),
    array('가이드', trim(guideExcelVal($dataRow, 'kr_name') . ' ' . guideExcelVal($dataRow, 'guide_id')), '차량회사', guideExcelVal($dataRow, 'c_id'), '차량', guideExcelVal($dataRow, 'c_type')),
    array('본행사 인원', $mainCnt, '복합행사 인원', $subCnt, '총인원', $totalCnt),
    array('기준통화', guideExcelVal($dataRow, 'base_rate'), '선지급행사비', guideExcelNum(guideExcelVal($dataRow, 'pre_amt')), '상태', guideExcelVal($setMaster, 'reg_status'))
);

$row = 4;
foreach ($infoRows as $info) {
    $sheet->setCellValue('A' . $row, $info[0]);
    $sheet->setCellValue('B' . $row, $info[1]);
    $sheet->mergeCells('B' . $row . ':C' . $row);
    $sheet->setCellValue('D' . $row, $info[2]);
    $sheet->setCellValue('E' . $row, $info[3]);
    $sheet->mergeCells('E' . $row . ':F' . $row);
    $sheet->setCellValue('G' . $row, $info[4]);
    $sheet->setCellValue('H' . $row, $info[5]);
    $sheet->mergeCells('H' . $row . ':K' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
    $sheet->getStyle('D' . $row)->applyFromArray($labelStyle);
    $sheet->getStyle('G' . $row)->applyFromArray($labelStyle);
    $sheet->getStyle('B' . $row . ':C' . $row)->applyFromArray($valueStyle);
    $sheet->getStyle('E' . $row . ':F' . $row)->applyFromArray($valueStyle);
    $sheet->getStyle('H' . $row . ':K' . $row)->applyFromArray($valueStyle);
    $row++;
}
$sheet->getStyle('H7')->getNumberFormat()->setFormatCode(guideExcelMoneyStyle());

$row += 2;
guideExcelSectionTitle($sheet, $row, '식사비', 'K');
$row++;
guideExcelHeaderRow($sheet, $row, array('구분', '일자', '식당명', '인원', '단가/P', '총액'));
$row++;
$mealStart = $row;
$mealTypeLabels = array('bf' => '조식', 'lunch' => '중식', 'dinner' => '석식');
foreach ($mealRows as $meal) {
    $type = guideExcelVal($meal, 'meal_type');
    $sheet->setCellValue('A' . $row, isset($mealTypeLabels[$type]) ? $mealTypeLabels[$type] : $type);
    $sheet->setCellValue('B' . $row, guideExcelVal($meal, 'meal_date'));
    $sheet->setCellValue('C' . $row, guideExcelVal($meal, 'meal_rest'));
    $sheet->setCellValue('D' . $row, guideExcelNum(guideExcelVal($meal, 'meal_cnt')));
    $sheet->setCellValue('E' . $row, guideExcelNum(guideExcelVal($meal, 'meal_price')));
    $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
    $row++;
}
if ($row === $mealStart) {
    guideExcelNoDataRow($sheet, $row, 'K');
    $row++;
}
$mealEnd = $row - 1;
$mealTotalRow = $row;
$sheet->setCellValue('A' . $row, '합계');
$sheet->mergeCells('A' . $row . ':C' . $row);
$sheet->setCellValue('D' . $row, guideExcelSumFormula('D', $mealStart, $mealEnd));
$sheet->setCellValue('E' . $row, guideExcelSumFormula('E', $mealStart, $mealEnd));
$sheet->setCellValue('F' . $row, guideExcelSumFormula('F', $mealStart, $mealEnd));
$sheet->getStyle('A' . $mealStart . ':K' . $row)->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9')))));
$sheet->getStyle('D' . $mealStart . ':F' . $row)->getNumberFormat()->setFormatCode(guideExcelMoneyStyle());
$sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($totalStyle);
$row += 2;

guideExcelSectionTitle($sheet, $row, '입장비', 'K');
$row++;
guideExcelHeaderRow($sheet, $row, array('입장지', '인원', '단가/P', '총액'));
$row++;
$admissionStart = $row;
foreach ($admissions as $admission) {
    $sheet->setCellValue('A' . $row, guideExcelCodeLabel($codeMap, guideExcelVal($admission, 'admission_code')));
    $sheet->setCellValue('B' . $row, guideExcelNum(guideExcelVal($admission, 'e_cnt')));
    $sheet->setCellValue('C' . $row, guideExcelNum(guideExcelVal($admission, 'e_price')));
    $sheet->setCellValue('D' . $row, '=B' . $row . '*C' . $row);
    $row++;
}
if ($row === $admissionStart) {
    guideExcelNoDataRow($sheet, $row, 'K');
    $row++;
}
$admissionEnd = $row - 1;
$admissionTotalRow = $row;
$sheet->setCellValue('A' . $row, '합계');
$sheet->setCellValue('B' . $row, guideExcelSumFormula('B', $admissionStart, $admissionEnd));
$sheet->setCellValue('D' . $row, guideExcelSumFormula('D', $admissionStart, $admissionEnd));
$sheet->getStyle('A' . $admissionStart . ':K' . $row)->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9')))));
$sheet->getStyle('B' . $admissionStart . ':D' . $row)->getNumberFormat()->setFormatCode(guideExcelMoneyStyle());
$sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($totalStyle);
$row += 2;

guideExcelSectionTitle($sheet, $row, '옵션', 'K');
$row++;
guideExcelHeaderRow($sheet, $row, array('옵션명', '정산기준', '인원', '가이드가/P', '가이드 총액', '옵션가/P', '옵션 총액', '차액', '회사수익', '가이드수익', '비고'));
$row++;
$optionStart = $row;
foreach ($options as $option) {
    $sheet->setCellValue('A' . $row, guideExcelCodeLabel($codeMap, guideExcelVal($option, 'option_code')));
    $sheet->setCellValue('B' . $row, guideExcelRatioLabel(guideExcelVal($option, 'base_set')));
    $sheet->setCellValue('C' . $row, guideExcelNum(guideExcelVal($option, 'o_cnt')));
    $sheet->setCellValue('D' . $row, guideExcelNum(guideExcelVal($option, 'o_price')));
    $sheet->setCellValue('E' . $row, '=C' . $row . '*D' . $row);
    $sheet->setCellValue('F' . $row, guideExcelNum(guideExcelVal($option, 'o_cprice')));
    $sheet->setCellValue('G' . $row, '=C' . $row . '*F' . $row);
    $sheet->setCellValue('H' . $row, '=G' . $row . '-E' . $row);
    $sheet->setCellValue('I' . $row, '=IF(B' . $row . '="5:5",H' . $row . '*0.5,IF(B' . $row . '="6:4",H' . $row . '*0.6,IF(B' . $row . '="7:3",H' . $row . '*0.7,0)))');
    $sheet->setCellValue('J' . $row, '=IF(B' . $row . '="5:5",H' . $row . '*0.5,IF(B' . $row . '="6:4",H' . $row . '*0.4,IF(B' . $row . '="7:3",H' . $row . '*0.3,0)))');
    $row++;
}
if ($row === $optionStart) {
    guideExcelNoDataRow($sheet, $row, 'K');
    $row++;
}
$optionEnd = $row - 1;
$optionTotalRow = $row;
$sheet->setCellValue('A' . $row, '합계');
$sheet->mergeCells('A' . $row . ':B' . $row);
$sheet->setCellValue('C' . $row, guideExcelSumFormula('C', $optionStart, $optionEnd));
$sheet->setCellValue('E' . $row, guideExcelSumFormula('E', $optionStart, $optionEnd));
$sheet->setCellValue('G' . $row, guideExcelSumFormula('G', $optionStart, $optionEnd));
$sheet->setCellValue('H' . $row, guideExcelSumFormula('H', $optionStart, $optionEnd));
$sheet->setCellValue('I' . $row, guideExcelSumFormula('I', $optionStart, $optionEnd));
$sheet->setCellValue('J' . $row, guideExcelSumFormula('J', $optionStart, $optionEnd));
$sheet->getStyle('A' . $optionStart . ':K' . $row)->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9')))));
$sheet->getStyle('C' . $optionStart . ':J' . $row)->getNumberFormat()->setFormatCode(guideExcelMoneyStyle());
$sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($totalStyle);
$row += 2;

guideExcelSectionTitle($sheet, $row, '가이드/차량/기타 비용', 'K');
$row++;
guideExcelHeaderRow($sheet, $row, array('구분', '항목', '금액', '메모'));
$row++;
$etcStart = $row;
$etcTypeLabels = array('guide' => '가이드', 'car' => '차량', 'etc' => '기타');
foreach ($etcRows as $etc) {
    $type = guideExcelVal($etc, 'etc_pricety');
    $sheet->setCellValue('A' . $row, isset($etcTypeLabels[$type]) ? $etcTypeLabels[$type] : $type);
    $sheet->setCellValue('B' . $row, guideExcelCodeLabel($codeMap, guideExcelVal($etc, 'etc_type')));
    $sheet->setCellValue('C' . $row, guideExcelNum(guideExcelVal($etc, 'etc_amt')));
    $sheet->setCellValue('D' . $row, guideExcelVal($etc, 'etc_memo'));
    $row++;
}
if ($row === $etcStart) {
    guideExcelNoDataRow($sheet, $row, 'K');
    $row++;
}
$etcEnd = $row - 1;
$etcTotalRow = $row;
$sheet->setCellValue('A' . $row, '합계');
$sheet->mergeCells('A' . $row . ':B' . $row);
$sheet->setCellValue('C' . $row, guideExcelSumFormula('C', $etcStart, $etcEnd));
$sheet->getStyle('A' . $etcStart . ':K' . $row)->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9')))));
$sheet->getStyle('C' . $etcStart . ':C' . $row)->getNumberFormat()->setFormatCode(guideExcelMoneyStyle());
$sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($totalStyle);
$row += 2;

guideExcelSectionTitle($sheet, $row, '쇼핑 정산', 'K');
$row++;
guideExcelHeaderRow($sheet, $row, array('쇼핑', '판매총액', '홈쇼핑수수료', '회사수익', '가이드수익'));
$row++;
$shoppingStart = $row;
foreach ($shopping as $shop) {
    $sheet->setCellValue('A' . $row, guideExcelCodeLabel($codeMap, guideExcelVal($shop, 'shop_code')));
    $sheet->setCellValue('B' . $row, guideExcelNum(guideExcelVal($shop, 'tot_amt')));
    $sheet->setCellValue('C' . $row, '=B' . $row . '*0.15');
    $sheet->setCellValue('D' . $row, '=C' . $row . '*0.6');
    $sheet->setCellValue('E' . $row, '=C' . $row . '*0.4');
    $row++;
}
if ($row === $shoppingStart) {
    guideExcelNoDataRow($sheet, $row, 'K');
    $row++;
}
$shoppingEnd = $row - 1;
$shoppingTotalRow = $row;
$sheet->setCellValue('A' . $row, '합계');
$sheet->setCellValue('B' . $row, guideExcelSumFormula('B', $shoppingStart, $shoppingEnd));
$sheet->setCellValue('C' . $row, guideExcelSumFormula('C', $shoppingStart, $shoppingEnd));
$sheet->setCellValue('D' . $row, guideExcelSumFormula('D', $shoppingStart, $shoppingEnd));
$sheet->setCellValue('E' . $row, guideExcelSumFormula('E', $shoppingStart, $shoppingEnd));
$sheet->getStyle('A' . $shoppingStart . ':K' . $row)->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9')))));
$sheet->getStyle('B' . $shoppingStart . ':E' . $row)->getNumberFormat()->setFormatCode(guideExcelMoneyStyle());
$sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($totalStyle);
$row += 2;

guideExcelSectionTitle($sheet, $row, '가이드 입금', 'K');
$row++;
guideExcelHeaderRow($sheet, $row, array('구분', '입금액', '인원', '총액', '메모'));
$row++;
$inputStart = $row;
foreach ($inputs as $input) {
    $sheet->setCellValue('A' . $row, guideExcelVal($input, 'inputamt_type'));
    $sheet->setCellValue('B' . $row, guideExcelNum(guideExcelVal($input, 'input_amt')));
    $sheet->setCellValue('C' . $row, guideExcelNum(guideExcelVal($input, 'input_cnt')));
    $sheet->setCellValue('D' . $row, '=B' . $row . '*C' . $row);
    $sheet->setCellValue('E' . $row, guideExcelVal($input, 'input_memo'));
    $row++;
}
if ($row === $inputStart) {
    guideExcelNoDataRow($sheet, $row, 'K');
    $row++;
}
$inputEnd = $row - 1;
$inputTotalRow = $row;
$sheet->setCellValue('A' . $row, '합계');
$sheet->mergeCells('A' . $row . ':C' . $row);
$sheet->setCellValue('D' . $row, guideExcelSumFormula('D', $inputStart, $inputEnd));
$sheet->getStyle('A' . $inputStart . ':K' . $row)->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9')))));
$sheet->getStyle('B' . $inputStart . ':D' . $row)->getNumberFormat()->setFormatCode(guideExcelMoneyStyle());
$sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($totalStyle);
$row += 2;

guideExcelSectionTitle($sheet, $row, '체크 입력', 'K');
$row++;
guideExcelHeaderRow($sheet, $row, array('체크번호', '은행/발행처', '사용일', '금액', '비고', ''));
$row++;
$checkStart = $row;
foreach ($checks as $check) {
    $sheet->setCellValue('A' . $row, guideExcelVal($check, 'check_no'));
    $sheet->setCellValue('B' . $row, guideExcelVal($check, 'bank_name'));
    $sheet->setCellValue('C' . $row, guideExcelVal($check, 'used_date'));
    $sheet->setCellValue('D' . $row, guideExcelNum(guideExcelVal($check, 'amount')));
    $sheet->setCellValue('E' . $row, guideExcelVal($check, 'note'));
    $row++;
}
if ($row === $checkStart) {
    guideExcelNoDataRow($sheet, $row, 'K');
    $row++;
}
$checkEnd = $row - 1;
$checkTotalRow = $row;
$sheet->setCellValue('A' . $row, '합계');
$sheet->mergeCells('A' . $row . ':C' . $row);
$sheet->setCellValue('D' . $row, guideExcelSumFormula('D', $checkStart, $checkEnd));
$sheet->getStyle('A' . $checkStart . ':K' . $row)->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9')))));
$sheet->getStyle('D' . $checkStart . ':D' . $row)->getNumberFormat()->setFormatCode(guideExcelMoneyStyle());
$sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($totalStyle);
$row += 2;

guideExcelSectionTitle($sheet, $row, '정산 요약', 'K');
$row++;
$summaryStart = $row;
$summaryRows = array(
    array('입금', '선지급행사비', guideExcelNum(guideExcelVal($dataRow, 'pre_amt'))),
    array('입금', '옵션 회사수익', '=I' . $optionTotalRow),
    array('입금', '쇼핑 회사수익', '=D' . $shoppingTotalRow),
    array('입금', '가이드 입금액', '=D' . $inputTotalRow),
    array('지출', '식사비', '=F' . $mealTotalRow),
    array('지출', '입장비', '=D' . $admissionTotalRow),
    array('지출', '가이드/차량/기타', '=C' . $etcTotalRow),
    array('지출', '쇼핑 정산', '=C' . $shoppingTotalRow . '+E' . $shoppingTotalRow),
    array('참고', '체크 합계', '=D' . $checkTotalRow)
);
guideExcelHeaderRow($sheet, $row, array('구분', '항목', '금액', '', '', '', '', ''));
$row++;
foreach ($summaryRows as $summary) {
    $sheet->setCellValue('A' . $row, $summary[0]);
    $sheet->setCellValue('B' . $row, $summary[1]);
    $sheet->setCellValue('C' . $row, $summary[2]);
    $row++;
}
$depositTotalRow = $row;
$sheet->setCellValue('A' . $row, '합계');
$sheet->setCellValue('B' . $row, '총입금액');
$sheet->setCellValue('C' . $row, '=SUMIF(A' . ($summaryStart + 1) . ':A' . ($row - 1) . ',"입금",C' . ($summaryStart + 1) . ':C' . ($row - 1) . ')');
$row++;
$expenseTotalRow = $row;
$sheet->setCellValue('A' . $row, '합계');
$sheet->setCellValue('B' . $row, '총지출금액');
$sheet->setCellValue('C' . $row, '=SUMIF(A' . ($summaryStart + 1) . ':A' . ($row - 2) . ',"지출",C' . ($summaryStart + 1) . ':C' . ($row - 2) . ')');
$row++;
$settleTotalRow = $row;
$sheet->setCellValue('A' . $row, '최종');
$sheet->setCellValue('B' . $row, '가이드 정산금액');
$sheet->setCellValue('C' . $row, '=C' . $depositTotalRow . '-C' . $expenseTotalRow);
$sheet->getStyle('A' . $summaryStart . ':K' . $row)->applyFromArray(array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9')))));
$sheet->getStyle('C' . ($summaryStart + 1) . ':C' . $row)->getNumberFormat()->setFormatCode(guideExcelMoneyStyle());
$sheet->getStyle('A' . $depositTotalRow . ':K' . $row)->applyFromArray($totalStyle);
$sheet->getStyle('A' . $settleTotalRow . ':K' . $settleTotalRow)->applyFromArray(array(
    'font' => array('bold' => true, 'size' => 13, 'color' => array('rgb' => 'FFFFFF')),
    'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => '1F7A4D'))
));
$row += 2;

guideExcelSectionTitle($sheet, $row, '메모', 'K');
$row++;
$sheet->mergeCells('A' . $row . ':K' . ($row + 4));
$sheet->setCellValue('A' . $row, $guideMemo);
$sheet->getStyle('A' . $row . ':K' . ($row + 4))->applyFromArray(array(
    'borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('rgb' => 'C9D1D9'))),
    'alignment' => array('wrap' => true, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP)
));
$row += 5;

foreach (range('A', 'K') as $column) {
    $sheet->getStyle($column . '1:' . $column . $row)->getAlignment()->setWrapText(true);
}
$sheet->getStyle('A1:K' . $row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
$sheet->getStyle('B4:K7')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('D:K')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('A:A')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

$sheet->getPageSetup()
    ->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4)
    ->setFitToWidth(1)
    ->setFitToHeight(0);
$sheet->getPageMargins()->setTop(0.4)->setRight(0.3)->setLeft(0.3)->setBottom(0.4);
$sheet->getPageSetup()->setPrintArea('A1:K' . $row);

$filename = 'guide_settlement_' . guideExcelCleanFilename($settleCode) . '_' . date('Ymd_His') . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
$writer->setPreCalculateFormulas(false);
$writer->save('php://output');
exit;
