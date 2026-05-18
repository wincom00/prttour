<?php
/**
 * breakdown_mailer_allinone.php
 * PHP 5.6 + mysql_*  / PHPExcel 1.8
 * - 상세 견적서 XLSX 생성(시트 끝까지 셀 병합)
 * - 이메일 본문 생성 + XLSX 첨부 발송
 * - PHPMailer 우선, 없으면 MIME 수동으로 mail() 사용 (여기서는 mailsend_h() 호출 가정)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1'); // 필요 시 1

/* =========================
 * 공통 DB 연결 (mysql_* 리소스: $dbConn)
 * ========================= */
require_once "include/inc_base.php"; // $dbConn

/* =========================
 * 유틸: 안전한 이메일 판별
 * ========================= */
function is_valid_email($s){
    return (bool)preg_match('/^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$/i', trim($s));
}

/* ============================================================
 * 1) XLSX (Styled) 생성/다운로드
 *  - $asFile=true: ['path','filename','mime'] 배열 반환 (첨부용)
 *  - $asFile=false: 브라우저 다운로드 스트림으로 전송 후 종료
 * ============================================================ */
function streamBreakdownExcelXlsxStyled($dbConn, $estimateId, $asFile = false) {
    // === 옵션 ===
    $MEAL_FILL_AUTO_SPAN = true; // true: master.start~end 날짜를 MEAL 헤더에 모두 포함
    $MAX_COL_IDX = 16;           // P 열

    /* ---------- 데이터 로드 (mysql_*) ---------- */
    $master = array();
    $sql = "SELECT * FROM estimate_master WHERE id = " . (int)$estimateId . " LIMIT 1";
    $res = mysql_query($sql, $dbConn);
    if ($res && mysql_num_rows($res)) {
        $master = mysql_fetch_assoc($res);
    } else {
        return array('error' => "견적서를 찾을 수 없습니다. id=".$estimateId);
    }

    $items = array();
    $sql = "SELECT * FROM estimate_items WHERE estimate_id = " . (int)$estimateId . " ORDER BY section, id";
    $res = mysql_query($sql, $dbConn);
    if ($res) {
        while ($row = mysql_fetch_assoc($res)) $items[] = $row;
    }

    // ---------- 유틸 ----------
    $masterStart = isset($master['start_date']) ? trim((string)$master['start_date']) : '';

    $normalizeDate = function($s) use ($masterStart) {
        $s = trim((string)$s);
        if ($s === '') return '';
        $s = str_replace(array('.','/'),'-',$s);
        if (preg_match('/^\d{8}$/',$s)) return substr($s,0,4).'-'.substr($s,4,2).'-'.substr($s,6,2);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)) return $s;
        if (preg_match('/^\d{1,2}-\d{1,2}$/',$s)) {
            $y = date('Y', $masterStart ? strtotime($masterStart) : time());
            $parts = explode('-', $s);
            $m = isset($parts[0]) ? $parts[0] : 0;
            $d = isset($parts[1]) ? $parts[1] : 0;
            return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
        }
        if (strpos($s,'~')!==false) { $left = explode('~',$s); $left = isset($left[0]) ? $left[0] : ''; return trim($left); }
        return $s;
    };

    $addDays = function($baseYmd,$days){
        if(!$baseYmd||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$baseYmd)) return '';
        return date('Y-m-d', strtotime($baseYmd.' +'.(int)$days.' day'));
    };

    $resolveDate = function($section, $etc) use ($normalizeDate,$addDays,$masterStart) {
        $candBy = array(
            'HOTEL'=>array('date','hotel_date','checkin_date'),
            'MEAL'=>array('date','meal_date'),
            'TRANSPORT'=>array('date','transport_date','drive_date'),
            'TICKET'=>array('date','ticket_date','visit_date'),
            'GUIDE'=>array('date','guide_date','service_date'),
            'ETC'=>array('date'),
        );
        $cand = isset($candBy[$section]) ? $candBy[$section] : array('date');
        foreach ($cand as $k) if (!empty($etc[$k])) return $normalizeDate($etc[$k]);
        foreach (array('day','day_index','d','dayno') as $k) if (!empty($etc[$k]) && (int)$etc[$k]>0) return $addDays($masterStart,(int)$etc[$k]-1);
        if (isset($etc['day_offset'])) return $addDays($masterStart,(int)$etc['day_offset']);
        if (!empty($etc['date_range'])) {
            $left = explode('~', str_replace(array(' ','/','.'),array('','','-'), (string)$etc['date_range']));
            $left = isset($left[0]) ? $left[0] : '';
            return $normalizeDate($left);
        }
        return '';
    };

    // 날짜 키 정규화(맵) → 'YYYY-MM-DD' 키로
    $normalizeKeyedDates = function($arr) use ($normalizeDate) {
        $out = array();
        foreach ($arr as $k => $v) {
            $nk = $normalizeDate(is_string($k) ? $k : (string)$k);
            if ($nk === '') continue;
            $out[$nk] = $v;
        }
        ksort($out);
        return $out;
    };

    // dates(맵/리스트) → "일수" 맵으로 변환 (리스트면 각 날짜 일수=1)
    $datesToDaysMap = function($dates) use ($normalizeKeyedDates, $normalizeDate) {
        $days = array();
        if (is_array($dates)) {
            // 맵
            if (array_keys($dates) !== range(0, count($dates)-1)) {
                $dates = $normalizeKeyedDates($dates);
                foreach ($dates as $d => $v) $days[$d] = (float)$v ?: 1.0;
            } else { // 리스트
                foreach ($dates as $d) {
                    $d = $normalizeDate($d);
                    if ($d==='') continue;
                    if (!isset($days[$d])) $days[$d] = 0.0;
                    $days[$d] += 1.0;
                }
            }
        }
        ksort($days);
        return $days;
    };

    // 번호 -> 엑셀 컬럼 레터
    $colByIdx = function($n){
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = (int)(($n - 1) / 26);
        }
        return $s;
    };

    // 날짜 범위 전개
    $buildDateRange = function($startYmd, $endYmd){
        $s = strtotime($startYmd ? $startYmd : ''); $e = strtotime($endYmd ? $endYmd : '');
        if (!$s || !$e || $e < $s) return array();
        $out = array();
        for ($t=$s; $t <= $e; $t = strtotime('+1 day', $t)) $out[] = date('Y-m-d', $t);
        return $out;
    };

    // --- PHPExcel 로더 ---
    $phpexcelCandidates = array(
        __DIR__ . '/lib/PHPExcel/Classes/PHPExcel.php',
        __DIR__ . '/admin/lib/PHPExcel/Classes/PHPExcel.php',
        __DIR__ . '/vendor/phpoffice/phpexcel/Classes/PHPExcel.php',
        dirname(__DIR__) . '/vendor/phpoffice/phpexcel/Classes/PHPExcel.php'
    );
    $loaded=false; foreach ($phpexcelCandidates as $p) { if (is_file($p)) { require_once $p; $loaded=true; break; } }
    if (!$loaded) return array('error' => 'PHPExcel.php 를 찾을 수 없습니다.');

    // --- 워크북/시트 ---
    $excel = new PHPExcel();
    $excel->getProperties()->setCreator("System")->setTitle("상세 견적서");
    $excel->setActiveSheetIndex(0);
    $sheet = $excel->getActiveSheet();
    $sheet->setTitle('BREAKDOWN');

    // 팔레트
    $CLR_BLUE  = '2E86AB';
    $CLR_LBLUE = 'E3F2FD';
    $CLR_HGRAY = 'F8F9FA';
    $CLR_BORDER= 'DDDDDD';
    $CLR_WHITE = 'FFFFFF';
    $CLR_BLACK = '000000';
    $CLR_YELLOW= 'FFF9C4';

    // 공통 스타일
    $bold=array('font'=>array('bold'=>true));
    $center=array('alignment'=>array('horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
    $wrap=array('alignment'=>array('wrap'=>true));
    $vbCenter=array('alignment'=>array('vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER));
    $borderAll=array('borders'=>array('allborders'=>array('style'=>PHPExcel_Style_Border::BORDER_THIN,'color'=>array('rgb'=>$CLR_BORDER))));
    $setFill=function($range,$rgb)use($sheet){$sheet->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);};
    $setFontColor=function($range,$rgb)use($sheet){$sheet->getStyle($range)->getFont()->getColor()->setRGB($rgb);};

    // 기본 컬럼 폭 (A~P)
    for ($i=1;$i<=$MAX_COL_IDX;$i++) {
        $c = $colByIdx($i);
        $sheet->getColumnDimension($c)->setWidth(in_array($c,array('A','B','C','D','H'))?14:10);
    }

    $row = 1;

    // 섹션 분류
    $sections=array();
    foreach ($items as $it) {
        $sec = isset($it['section']) ? $it['section'] : 'ETC';
        if (!isset($sections[$sec])) $sections[$sec] = array();
        $sections[$sec][] = $it;
    }

    $order=array('HOTEL','MEAL','TRANSPORT','TICKET','GUIDE','ETC');
    $titles=array(
        'HOTEL'=>'1) HOTEL',
        'MEAL'=>'2) MEAL (일자×식사 매트릭스)',
        'TRANSPORT'=>'3) TRANSPORTATION (일자별 차량료)',
        'TICKET'=>'4) 입장권',
        'GUIDE'=>'5) 가이드/기사',
        'ETC'=>'7) 기타경비'
    );

    // ── 사전 계산: 섹션별 날짜열 준비 ──
    $mealAllDates = array();
    if (!empty($sections['MEAL'])) {
        $set = array();
        foreach ($sections['MEAL'] as $it) {
            $etc = array();
            if (!empty($it['etc_json'])) { $tmp = json_decode($it['etc_json'], true); if (is_array($tmp)) $etc = $tmp; }
            $map = $datesToDaysMap(isset($etc['dates'])?$etc['dates']:array());
            if (empty($map)) {
                $d0 = $resolveDate('MEAL', $etc); if ($d0==='') $d0=date('Y-m-d');
                $map = array($d0 => (float)(isset($etc['unit_per_pax']) ? $etc['unit_per_pax'] : (float)(isset($it['unit'])?$it['unit']:0)));
            }
            foreach ($map as $ymd => $_) $set[$ymd] = true;
        }
        if ($MEAL_FILL_AUTO_SPAN) {
            $ms = isset($master['start_date']) ? trim((string)$master['start_date']) : '';
            $me = isset($master['end_date'])   ? trim((string)$master['end_date'])   : '';
            if ($ms !== '' && $me !== '') {
                $rng = $buildDateRange($ms,$me);
                foreach ($rng as $d) $set[$d] = true;
            }
        }
        $mealAllDates = array_keys($set); sort($mealAllDates);
        if (empty($mealAllDates)) $mealAllDates = array(date('Y-m-d'));
    }

    // TRANSPORT 헤더용 날짜
    $transAllDates = $mealAllDates;
    if (empty($transAllDates) && !empty($sections['TRANSPORT'])) {
        $set = array();
        foreach ($sections['TRANSPORT'] as $it) {
            $etc = array();
            if (!empty($it['etc_json'])) { $tmp = json_decode($it['etc_json'], true); if (is_array($tmp)) $etc = $tmp; }
            $m = $datesToDaysMap(isset($etc['dates'])?$etc['dates']:array());
            if (empty($m)) {
                $d0 = $resolveDate('TRANSPORT',$etc); if ($d0==='') $d0=date('Y-m-d');
                $m = array($d0 => (float)(isset($it['qty'])?$it['qty']:1));
            }
            foreach ($m as $ymd => $_) $set[$ymd] = true;
        }
        $transAllDates = array_keys($set); sort($transAllDates);
        if (empty($transAllDates)) $transAllDates = array(date('Y-m-d'));
    }

    // ── P 상한 절단 ──
    $MEAL_MAX_DATES = max(0, $MAX_COL_IDX - 1 - 3); // 12
    if (count($mealAllDates) > $MEAL_MAX_DATES) { $mealAllDates = array_slice($mealAllDates, 0, $MEAL_MAX_DATES); }
    $TRANS_MAX_DATES = max(0, $MAX_COL_IDX - 1 - 2); // 13
    if (count($transAllDates) > $TRANS_MAX_DATES) { $transAllDates = array_slice($transAllDates, 0, $TRANS_MAX_DATES); }

    // ── “가장 긴 열” 계산 후 P 상한 적용 ──
    $MEAL_LAST_COL_IDX  = !empty($mealAllDates)  ? (1 + count($mealAllDates)  + 3) : 0;
    $TRANS_LAST_COL_IDX = !empty($transAllDates) ? (1 + count($transAllDates) + 2) : 0;
    $LAST_COL_IDX = min($MAX_COL_IDX, max(8, $MEAL_LAST_COL_IDX, $TRANS_LAST_COL_IDX));
    $colBy = $colByIdx; $L = $colBy($LAST_COL_IDX); // 시트 실제 끝 컬럼(최대 P)

    // 합계/우측 영역을 시트 끝까지 병합하는 헬퍼
    $mergeToSheetEnd = function($rowNum, $fromColLetter) use ($sheet, $L) {
        if ($L !== $fromColLetter) {
            $sheet->mergeCells($fromColLetter.$rowNum.":".$L.$rowNum);
        }
    };

    // ── 타이틀 ──
    $sheet->mergeCells("A{$row}:{$L}{$row}");
    $sheet->setCellValue("A{$row}","상세 견적서");
    $sheet->getStyle("A{$row}")->applyFromArray($bold + $center);
    $sheet->getStyle("A{$row}")->getFont()->setSize(18);
    $setFill("A{$row}:{$L}{$row}",$CLR_BLUE); $setFontColor("A{$row}:{$L}{$row}",$CLR_WHITE);
    $row += 2;

    // 기본정보
    $firstInfoRow = $row;
    $sheet->fromArray(array(array(
        'TO',(string)(isset($master['to_name'])?$master['to_name']:''),
        'GROUP',(string)(isset($master['group_name'])?$master['group_name']:''),
        'PAX',(int)(isset($master['pax'])?$master['pax']:0),
        'FOC',(int)(isset($master['foc'])?$master['foc']:0)
    )),null,"A{$row}");
    $sheet->fromArray(array(array(
        '시작일',(string)(isset($master['start_date'])?$master['start_date']:''),
        '종료일',(string)(isset($master['end_date'])?$master['end_date']:''),
        '총인원',(int)(isset($master['total_pax'])?$master['total_pax']:0),
        '작성일',(string)(isset($master['wdate'])?$master['wdate']:'')
    )),null,"A".($row+1));
    foreach (array('A','C','E','G') as $c){ $setFill($c.$row,$CLR_HGRAY); $setFill($c.($row+1),$CLR_HGRAY); }
    $sheet->getStyle("A{$row}:{$L}".($row+1))->applyFromArray($borderAll + $vbCenter);
    $paxCell="F{$firstInfoRow}";
    $row += 3;

    $subtotalCells=array();

    // ───────────────────── 렌더링 ─────────────────────
    foreach ($order as $sec) {
        if (empty($sections[$sec])) continue;

        // 섹션 타이틀 바
        $sheet->mergeCells("A{$row}:{$L}{$row}");
        $sheet->setCellValue("A{$row}", isset($titles[$sec]) ? $titles[$sec] : $sec);
        $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $vbCenter);
        $setFill("A{$row}:{$L}{$row}", $CLR_YELLOW); $setFontColor("A{$row}:{$L}{$row}", $CLR_BLACK);
        $row++;

        $startDataRow = $row;

        // ── 각 섹션별 표 렌더링 (HOTEL, MEAL, TRANSPORT, TICKET, GUIDE, ETC)
        // ... [생략 없이 원문 로직과 동일 — 여기까지의 코드 그대로 유지] ...
        // *** 공간상 요약: 아래는 질문에 주신 섹션별 렌더링 코드를 그대로 둡니다. ***
        // HOTEL
        if ($sec==='HOTEL') {
            $sheet->fromArray(array(array('지역','날짜','요일/시간','호텔명','방수','요금(USD)','박수','합계')),null,"A{$row}");
            $mergeToSheetEnd($row, 'H');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            foreach ($sections[$sec] as $it) {
                $etcJson = isset($it['etc_json']) ? (string)$it['etc_json'] : '{}';
                $etc = json_decode($etcJson, true); if (!is_array($etc)) $etc=array();
                $a=(string)(isset($etc['region']) ? $etc['region'] : '');
                $b=$resolveDate('HOTEL',$etc);
                $c=(string)(isset($etc['weekday']) ? $etc['weekday'] : (isset($etc['time'])?$etc['time']:'')); // 요일/시간
                $d=(string)(isset($it['label']) ? $it['label'] : ''); if ($d!=='') $d.=" 또는 동급호텔";
                $e=(float)(isset($it['cnt']) ? $it['cnt'] : 0); $f=(float)(isset($it['unit']) ? $it['unit'] : 0); $g=(float)(isset($it['qty']) ? $it['qty'] : 0);

                $sheet->fromArray(array(array( $a,$b,$c,$d,$e,$f,$g )),null,"A{$row}");
                $sheet->setCellValue("H{$row}","=F{$row}*G{$row}");
                $sheet->getStyle("E{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'H');
                $row++;
            }

            $endDataRow=$row-1;
            $sheet->mergeCells("A{$row}:G{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            $sheet->setCellValue("H{$row}", ($endDataRow>=$startDataRow) ? "=SUM(H{$startDataRow}:H{$endDataRow})" : 0);
            $mergeToSheetEnd($row,'H');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[]="H{$row}";
            $row+=2;
            continue;
        }

        // MEAL
        if ($sec==='MEAL') {
            $col=1; $sheet->setCellValue($colBy($col).$row,'구분'); $col++;
            foreach($mealAllDates as $ymd){ $sheet->setCellValue($colBy($col).$row,$ymd); $col++; }
            $colSumPerPax=$col; $sheet->setCellValue($colBy($col).$row,'일인당 합계단가'); $col++;
            $colPax=$col;       $sheet->setCellValue($colBy($col).$row,'인원수');       $col++;
            $colTotal=$col;     $sheet->setCellValue($colBy($col).$row,'합계');

            $mergeToSheetEnd($row, $colBy($colTotal));
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            $classifyMeal=function($etc,$time){
                $v=strtolower(trim((string)(isset($etc['meal_type'])?$etc['meal_type']:'')));
                if (in_array($v,array('b','bf','breakfast','조식','아침'),true)) return 'B';
                if (in_array($v,array('l','lunch','중식','점심'),true)) return 'L';
                if (in_array($v,array('d','din','dinner','석식','저녁'),true)) return 'D';
                if (preg_match('/^(\d{1,2}):(\d{2})$/',(string)$time,$m)){ $h=(int)$m[1]; return ($h<=10?'B':($h<=15?'L':'D')); }
                return 'L';
            };
            $labelBy=array('B'=>'조식','L'=>'중식','D'=>'석식');
            $grid=array('B'=>array(),'L'=>array(),'D'=>array());
            $paxByMeal=array('B'=>0,'L'=>0,'D'=>0);
            $defaultPax=(int)(isset($master['total_pax'])?$master['total_pax']:0);

            foreach ($sections[$sec] as $it){
                $etcJson = isset($it['etc_json']) ? (string)$it['etc_json'] : '{}';
                $etc = json_decode($etcJson,true); if(!is_array($etc)) $etc=array();
                $mk=$classifyMeal($etc, isset($etc['time'])?$etc['time']:'');
                $pax=(int)(isset($etc['pax']) ? $etc['pax'] : $defaultPax);
                if ($pax>0) $paxByMeal[$mk]=max($paxByMeal[$mk],$pax);

                $map=$datesToDaysMap(isset($etc['dates'])?$etc['dates']:array());
                if (empty($map)) {
                    $d0=$resolveDate('MEAL',$etc); if ($d0==='') $d0=date('Y-m-d');
                    $map=array($d0 => (float)(isset($etc['unit_per_pax']) ? $etc['unit_per_pax'] : (float)(isset($it['unit'])?$it['unit']:0)));
                }
                foreach ($map as $ymd=>$unitPerPax){
                    if (!isset($grid[$mk][$ymd])) $grid[$mk][$ymd]=0.0;
                    $grid[$mk][$ymd]+=(float)$unitPerPax;
                }
            }

            $mealRowTotals=array();
            foreach (array('B','L','D') as $mk) {
                $sheet->setCellValue("A{$row}", $labelBy[$mk]);
                $sumCells=array(); $col=2;
                foreach ($mealAllDates as $ymd){
                    $addr=$colBy($col).$row;
                    $val = isset($grid[$mk][$ymd]) ? (float)$grid[$mk][$ymd] : 0.0;
                    $sheet->setCellValue($addr,$val);
                    $sheet->getStyle($addr)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                    $sumCells[]=$addr; $col++;
                }
                $addrSum=$colBy($col).$row;
                $sheet->setCellValue($addrSum, !empty($sumCells)?'='.implode('+',$sumCells):0);
                $sheet->getStyle($addrSum)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0'); $col++;

                $addrPax=$colBy($col).$row; $sheet->setCellValueExplicit($addrPax,(int)$paxByMeal[$mk],PHPExcel_Cell_DataType::TYPE_NUMERIC); $col++;

                $addrTot=$colBy($col).$row; $sheet->setCellValue($addrTot,"={$addrSum}*{$addrPax}");
                $sheet->getStyle($addrTot)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $mealRowTotals[]=$addrTot;

                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row, $colBy($col));
                $row++;
            }

            $sumExpr = !empty($mealRowTotals)?'='.implode('+',$mealRowTotals):'0';
            $sheet->mergeCells("A{$row}:".$colBy($colTotal-1)."{$row}");
            $sheet->setCellValue("A{$row}", 'MEAL 소계');
            $sheet->setCellValue($colBy($colTotal).$row, $sumExpr);
            $mergeToSheetEnd($row, $colBy($colTotal));
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle($colBy($colTotal).$row)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[] = $colBy($colTotal).$row;
            $row+=2;
            continue;
        }

        // TRANSPORT
        if ($sec==='TRANSPORT') {
            $col=1; $sheet->setCellValue($colBy($col).$row,'차량'); $col++;
            foreach($transAllDates as $ymd){ $sheet->setCellValue($colBy($col).$row,$ymd); $col++; }
            $colCar=$col;       $sheet->setCellValue($colBy($col).$row,'차량수'); $col++;
            $colTotal=$col;     $sheet->setCellValue($colBy($col).$row,'합계');

            $mergeToSheetEnd($row, $colBy($colTotal));
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            $rows = array();
            foreach ($sections[$sec] as $it) {
                $etcJson = isset($it['etc_json']) ? (string)$it['etc_json'] : '{}';
                $etc  = json_decode($etcJson,true); if (!is_array($etc)) $etc=array();
                $veh  = (string)(isset($etc['vehicle_type']) ? $etc['vehicle_type'] : (isset($it['label'])?$it['label']:'')); // 차량종류
                $route= (string)(isset($etc['route']) ? $etc['route'] : (isset($it['description'])?$it['description']:'')); // 노선
                $key  = trim($veh . ($route ? " / {$route}" : ''));

                if (!isset($rows[$key])) $rows[$key] = array('title'=>$key,'car'=>0.0,'days'=>array(),'unit'=>(float)(isset($it['unit'])?$it['unit']:0));

                $rows[$key]['car'] += (float)(isset($etc['unit_per_car']) ? $etc['unit_per_car'] : (float)(isset($it['cnt'])?$it['cnt']:0));

                $daysMap = $datesToDaysMap(isset($etc['dates'])?$etc['dates']:array());
                if (empty($daysMap)) {
                    $d0 = $resolveDate('TRANSPORT',$etc); if ($d0==='') $d0=date('Y-m-d');
                    $daysMap = array($d0 => (float)(isset($it['qty'])?$it['qty']:1));
                }
                foreach ($daysMap as $ymd=>$days) {
                    if (!isset($rows[$key]['days'][$ymd])) $rows[$key]['days'][$ymd]=0.0;
                    $rows[$key]['days'][$ymd] += (float)$days;
                }
            }

            $transRowTotals=array();
            foreach ($rows as $r) {
                $sheet->setCellValue("A{$row}", $r['title']);

                $col=2; $sumCells=array();
                foreach ($transAllDates as $ymd){
                    $addr=$colBy($col).$row;
                    $days = isset($r['days'][$ymd]) ? (float)$r['days'][$ymd] : 0.0;
                    $sheet->setCellValueExplicit($addr,$days, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $sumCells[]=$addr; $col++;
                }

                $addrCar=$colBy($col).$row;
                $sheet->setCellValueExplicit($addrCar,(float)$r['car'] ?: 1.0, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $col++;

                $addrTot  = $colBy($col).$row;
                $sumExpr  = !empty($sumCells)?'('.implode('+',$sumCells).')': '0';
                $unitVal  = (float)$r['unit'];
                $sheet->setCellValue($addrTot, "={$sumExpr}*{$unitVal}*{$addrCar}");
                $sheet->getStyle($addrTot)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $transRowTotals[]=$addrTot;

                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row, $colBy($col));
                $row++;
            }

            $sumExpr = !empty($transRowTotals)?'='.implode('+',$transRowTotals):'0';
            $sheet->mergeCells("A{$row}:".$colBy($colTotal-1)."{$row}");
            $sheet->setCellValue("A{$row}", 'TRANSPORTATION 소계');
            $sheet->setCellValue($colBy($colTotal).$row, $sumExpr);
            $mergeToSheetEnd($row, $colBy($colTotal));
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle($colBy($colTotal).$row)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[] = $colBy($colTotal).$row;
            $row+=2;
            continue;
        }

        // TICKET
        if ($sec==='TICKET') {
            $sheet->fromArray(array(array('입장지','단가','인원','합계')),null,"A{$row}");
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            foreach ($sections[$sec] as $it) {
                $etcJson = isset($it['etc_json']) ? (string)$it['etc_json'] : '{}';
                $etc = json_decode($etcJson,true); if(!is_array($etc)) $etc=array();
                $place=(string)(isset($etc['place']) ? $etc['place'] : (isset($it['label'])?$it['label']:'')); // 장소
                $unit=(float)(isset($it['unit'])?$it['unit']:0);
                $cnt=(float)(isset($it['cnt'])?$it['cnt']:0); $qty=(float)(isset($it['qty'])?$it['qty']:0);
                $people = $cnt>0?$cnt:$qty;

                $sheet->fromArray(array(array( $place, $unit, $people )),null,"A{$row}");
                $sheet->setCellValue("D{$row}","=B{$row}*C{$row}");
                $sheet->getStyle("B{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'D');
                $row++;
            }

            $endDataRow=$row-1;
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            $sheet->setCellValue("D{$row}", ($endDataRow>=$startDataRow) ? "=SUM(D{$startDataRow}:D{$endDataRow})" : 0);
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[]="D{$row}";
            $row+=2;
            continue;
        }

        // GUIDE
        if ($sec==='GUIDE') {
            $sheet->fromArray(array(array('서비스종류','일수/시간','대수','단가','합계')),null,"A{$row}");
            $mergeToSheetEnd($row,'E');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            foreach ($sections[$sec] as $it) {
                $etcJson = isset($it['etc_json']) ? (string)$it['etc_json'] : '{}';
                $etc = json_decode($etcJson,true); if(!is_array($etc)) $etc=array();
                $label=(string)(isset($it['label'])?$it['label']:(isset($etc['service_type'])?$etc['service_type']:'')); // 서비스종류
                $days=(float)(isset($it['qty'])?$it['qty']:0); $cars=(float)(isset($it['cnt'])?$it['cnt']:0); $unit=(float)(isset($it['unit'])?$it['unit']:0);

                $sheet->fromArray(array(array( $label, $days, $cars, $unit )),null,"A{$row}");
                $sheet->setCellValue("E{$row}","=B{$row}*C{$row}*D{$row}");
                $sheet->getStyle("B{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'E');
                $row++;
            }

            $endDataRow=$row-1;
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            $sheet->setCellValue("E{$row}", ($endDataRow>=$startDataRow) ? "=SUM(E{$startDataRow}:E{$endDataRow})" : 0);
            $mergeToSheetEnd($row,'E');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[]="E{$row}";
            $row+=2;
            continue;
        }

        // ETC
        if ($sec==='ETC') {
            $sheet->fromArray(array(array('항목명','내용','인원','요금(USD)','수량','합계')),null,"A{$row}");
            $mergeToSheetEnd($row,'F');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            foreach ($sections[$sec] as $it) {
                $label=(string)(isset($it['label'])?$it['label']:''); $desc=(string)(isset($it['description'])?$it['description']:'');
                $cnt=(float)(isset($it['cnt'])?$it['cnt']:0); $unit=(float)(isset($it['unit'])?$it['unit']:0); $qty=(float)(isset($it['qty'])?$it['qty']:0);

                $sheet->fromArray(array(array( $label, $desc, $cnt, $unit, $qty )), null, "A{$row}");
                $sheet->setCellValue("F{$row}","=D{$row}*E{$row}");
                $sheet->getStyle("C{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'F');
                $row++;
            }

            $endDataRow=$row-1;
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            $sheet->setCellValue("F{$row}", ($endDataRow>=$startDataRow) ? "=SUM(F{$startDataRow}:F{$endDataRow})" : 0);
            $mergeToSheetEnd($row,'F');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $subtotalCells[]="F{$row}";
            $row+=2;
            continue;
        }
    }

    // 총합 & 1인당
    $totalRow=$row;
    $sheet->mergeCells("A{$row}:D{$row}");
    $sheet->setCellValue("A{$row}",'10) TOTAL TOUR FEE');
    $sheet->setCellValue("H{$row}", !empty($subtotalCells)?'='.implode('+',$subtotalCells):0);
    $mergeToSheetEnd($row,'H');
    $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $vbCenter + $borderAll);
    $setFill("A{$row}:{$L}{$row}",$CLR_BLUE); $setFontColor("A{$row}:{$L}{$row}",$CLR_WHITE);
    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
    $row++;

    $sheet->mergeCells("A{$row}:D{$row}");
    $sheet->setCellValue("A{$row}",'11) 1인당 요금');
    $sheet->setCellValue("H{$row}","=IFERROR(H{$totalRow}/{$paxCell},0)");
    $mergeToSheetEnd($row,'H');
    $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $vbCenter + $borderAll);
    $setFill("A{$row}:{$L}{$row}",$CLR_BLUE); $setFontColor("A{$row}:{$L}{$row}",$CLR_WHITE);
    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');

    // 정렬
    $sheet->getStyle("A1:{$L}{$row}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("A1:{$L}{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    // 파일 이름
    $groupName = trim((string)(isset($master['group_name']) ? $master['group_name'] : ''));
    if ($groupName === '') $groupName = 'GROUP_'.$estimateId;
    $safeGroup = preg_replace('/[^a-zA-Z0-9가-힣_-]+/', '_', $groupName);
    $filename  = "BREAKDOWN_QUOTATION_{$safeGroup}_".date('Ymd').".xlsx";

    // 작성기
    $writer = PHPExcel_IOFactory::createWriter($excel,'Excel2007');
    $writer->setPreCalculateFormulas(false);

    // 첨부용 파일(임시) 반환
    if ($asFile) {
        $tmp = tempnam(sys_get_temp_dir(),'xl_');
        $writer->save($tmp);
        return array(
            'path'     => $tmp,
            'filename' => $filename,
            'mime'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    // 브라우저 다운로드 스트림
    @ini_set('zlib.output_compression','0'); while (ob_get_level()) @ob_end_clean();
    $tmp = tempnam(sys_get_temp_dir(),'xl_'); $writer->save($tmp);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate, max-age=0');
    header('Pragma: public'); header('Expires: 0');
    header('Content-Length: '.filesize($tmp));
    $fp=fopen($tmp,'rb'); fpassthru($fp); fclose($fp); @unlink($tmp);
    exit; // 다운로드 후 종료
}

/* ============================================================
 * 3) 이메일 HTML 본문
 * ============================================================ */
function generateEmailContent($recipientName, $estimateInfo, $groupName) {
    $pax        = isset($estimateInfo['pax']) ? (int)$estimateInfo['pax'] : 0;
    $foc        = isset($estimateInfo['foc']) ? (int)$estimateInfo['foc'] : 0;
    $start_date = isset($estimateInfo['start_date']) ? $estimateInfo['start_date'] : '';
    $end_date   = isset($estimateInfo['end_date']) ? $estimateInfo['end_date'] : '';
    $grand      = isset($estimateInfo['grand_total']) ? (float)$estimateInfo['grand_total'] : 0.0;
    $per_pax    = isset($estimateInfo['per_pax']) ? (float)$estimateInfo['per_pax'] : 0.0;

    return "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
.container { max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: linear-gradient(135deg, #2E86AB 0%, #A23B72 100%); color: #fff; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
.content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
.footer { background: #333; color: #fff; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; }
.info-box { background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2E86AB; border-radius: 5px; }
.highlight { color: #2E86AB; font-weight: bold; }
table { width: 100%; border-collapse: collapse; }
td { padding: 8px 0; border-bottom: 1px solid #eee; }
</style>
</head>
<body>
  <div class='container'>
    <div class='header'>
      <h1>푸른투어</h1>
      <h2>상세 견적서</h2>
    </div>
    <div class='content'>
      <p>안녕하세요, <strong>" . htmlspecialchars($recipientName) . "</strong>님.</p>
      <p>푸른투어에서 요청하신 <span class='highlight'>" . htmlspecialchars($groupName) . "</span> 그룹의 견적서를 보내드립니다.</p>
      <div class='info-box'>
        <h3 style='color:#2E86AB;margin-top:0;'>견적서 정보</h3>
        <table>
          <tr><td><strong>그룹명:</strong></td><td>" . htmlspecialchars($groupName) . "</td></tr>
          <tr><td><strong>인원:</strong></td><td>" . $pax . "명 (FOC: " . $foc . "명)</td></tr>
          <tr><td><strong>여행기간:</strong></td><td>" . htmlspecialchars($start_date) . " ~ " . htmlspecialchars($end_date) . "</td></tr>
          <tr><td><strong>총 금액:</strong></td><td class='highlight'>$" . number_format($grand, 2) . "</td></tr>
          <tr><td><strong>1인당 요금:</strong></td><td class='highlight'>$" . number_format($per_pax, 2) . "</td></tr>
        </table>
      </div>
      <p>상세한 내역은 첨부된 상세 견적서 파일을 확인해 주세요.</p>
      <p>감사합니다.</p>
    </div>
    <div class='footer'>
      <p><strong>푸른투어</strong></p>
      <p>이 이메일은 자동으로 발송된 메일입니다.</p>
    </div>
  </div>
</body>
</html>";
}

/* ============================================================
 * 4) 메일 발송 본체
 * ============================================================ */
function sendBreakdownEmail($dbConn, $userId, $estimateId) {
    // 1) 마스터 로드
    $sql = "SELECT * FROM estimate_master WHERE id=".(int)$estimateId." LIMIT 1";
    $rs  = mysql_query($sql, $dbConn);
    if (!$rs || !mysql_num_rows($rs)) return "견적서를 찾을 수 없습니다.";
    $master = mysql_fetch_assoc($rs);

    $groupName = trim((string)(isset($master['group_name']) ? $master['group_name'] : ('GROUP_'.$estimateId)));
    $toName    = trim((string)(isset($master['to_name'])    ? $master['to_name']    : '고객님'));

    // 2) 수신자 이메일 결정
    if (is_valid_email($userId)) {
        $toEmail = $userId;
    } else {
        if (!empty($master['email']) && is_valid_email($master['email'])) {
            $toEmail = $master['email'];
        } elseif (!empty($master['to_email']) && is_valid_email($master['to_email'])) {
            $toEmail = $master['to_email'];
        } else {
            return "수신자 이메일을 결정할 수 없습니다. (user_id가 이메일이 아님)";
        }
    }

    // 3) XLSX 첨부 생성
    $xlsx = streamBreakdownExcelXlsxStyled($dbConn, (int)$estimateId, true);
    if (!is_array($xlsx) || isset($xlsx['error'])) {
        return "XLSX 생성 실패: " . (isset($xlsx['error']) ? $xlsx['error'] : 'unknown');
    }
    if (empty($xlsx['path']) || !is_file($xlsx['path'])) {
        return "XLSX 임시파일이 없습니다.";
    }

    // mailsend_h가 upload/ 기준 파일명을 받는 경우를 대비
    $uploadDir = __DIR__ . '/upload';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
    $destPath  = $uploadDir . '/' . $xlsx['filename'];

    // 같은 이름이 이미 있으면 덮어쓰기
    if (!@copy($xlsx['path'], $destPath)) {
        if (!@rename($xlsx['path'], $destPath)) {
            @unlink($xlsx['path']);
            return "첨부용 파일 복사/이동 실패";
        }
    }
    @chmod($destPath, 0644);
    $attachArg = basename($destPath); // mailsend_h에 파일명만 전달

    // 4) 메일 본문/제목
    $subject = "상세 견적서 - ".$groupName;
    $bodyHtml = generateEmailContent($toName, $master, $groupName);

    // 5) 발송 (사내/고객)
    $ok1 = mailsend_h('online@prttour.com', $subject, $bodyHtml, $attachArg, '', '', '');
    $ok2 = mailsend_h($toEmail,           $subject, $bodyHtml, $attachArg, '', '', '');

    // 6) 임시파일 정리
    if (is_string($xlsx['path']) && file_exists($xlsx['path'])) @unlink($xlsx['path']);
    // 필요 시 업로드 파일도 정리
    // if (file_exists($destPath)) @unlink($destPath);

    return ($ok1 && $ok2) ? "성공" : "메일 발송 실패";
}

/* ============================================================
 * 5) 실행 엔드포인트
 * ============================================================ */
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // 엑셀 다운로드
    if ($action === 'download_excel') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header("Content-Type: text/plain; charset=UTF-8");
            echo "유효한 견적서 ID가 필요합니다.";
            exit;
        }
        // 다운로드 스트림 (함수 내부에서 exit)
        streamBreakdownExcelXlsxStyled($dbConn, $id, false);
    }

    // 이메일 발송
    if ($action === 'send_email') {
        $userId     = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
        $estimateId = isset($_GET['estimate_id']) ? (int)$_GET['estimate_id'] : 0;

        if ($userId === '' || $estimateId <= 0) {
            echo "<script>alert('user_id(수신자)와 estimate_id(견적서 ID)가 필요합니다.'); history.back();</script>";
            exit;
        }

        $result = sendBreakdownEmail($dbConn, $userId, $estimateId);
        if ($result === "성공") {
            echo "<script>alert('이메일이 성공적으로 발송되었습니다.\\n수신자: " . addslashes($userId) . "'); window.close();</script>";
        } else {
            echo "<script>alert('이메일 발송 실패: " . addslashes($result) . "'); history.back();</script>";
        }
        exit;
    }
}

/* =========================
 * 기본 페이지 (테스트 폼)
 * ========================= */
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>BREAKDOWN XLSX / 메일 발송</title>
<style>
  body{font-family:Arial,sans-serif;max-width:720px;margin:50px auto;padding:20px}
  .card{border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:24px}
  .row{display:flex;gap:12px}
  label{display:block;font-weight:bold;margin:6px 0}
  input{width:100%;padding:8px;border:1px solid #ddd;border-radius:4px}
  button{background:#2E86AB;color:#fff;padding:10px 16px;border:none;border-radius:4px;cursor:pointer}
</style>
</head>
<body>
  <h2>상세 견적서 - XLSX 다운로드 / 이메일 발송</h2>

  <div class="card">
    <h3>XLSX 다운로드</h3>
    <form method="GET">
      <input type="hidden" name="action" value="download_excel">
      <label>견적서 ID</label>
      <input type="number" name="id" placeholder="estimate_master.id" required>
      <div style="margin-top:12px;"><button type="submit">엑셀 다운로드</button></div>
    </form>
  </div>

  <div class="card">
    <h3>이메일 발송</h3>
    <form method="GET">
      <input type="hidden" name="action" value="send_email">
      <div class="row">
        <div style="flex:1">
          <label>수신자 (이메일 또는 ID)</label>
          <input type="text" name="user_id" placeholder="customer@example.com" required>
        </div>
        <div style="flex:1">
          <label>견적서 ID</label>
          <input type="number" name="estimate_id" placeholder="estimate_master.id" required>
        </div>
      </div>
      <div style="margin-top:12px;"><button type="submit">메일 발송</button></div>
    </form>
  </div>
</body>
</html>
