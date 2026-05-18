<?php
/**
 * send_breakdown_excel_xlsx_styled.php
 * - mysqli + PHPExcel 1.8
 * - XLSX + 수식
 * - MEAL: 동적 매트릭스(가로 날짜), master.start~end 스팬 옵션
 * - TRANSPORT: 날짜별 일수 합산 → 합계 = SUM(일수열)*단가*차량수
 * - 모든 섹션: 마지막 합계 셀을 시트 끝(P)까지 병합
 * - 최대 컬럼은 P(16)이며, 실제 데이터 중 가장 긴 열을 기준으로 하되 P를 상한으로 사용
 * - 로우수는 섹션별로 독립 (세로 패딩 제거)
 */

error_reporting(E_ALL);
ini_set('display_errors', '0'); // 필요 시 1

if (isset($_GET['action']) && $_GET['action'] === 'download_excel') {
    require_once __DIR__ . "/include/inc_base.php"; // $dbConn (mysqli)

    $estimateId = isset($_GET['estimate_id']) ? (int)$_GET['estimate_id'] : 0;
    if ($estimateId <= 0) {
        header("Content-Type: text/html; charset=UTF-8");
        echo "<script>alert('견적서 ID가 필요합니다.'); history.back();</script>";
        exit;
    }

    $ret = streamBreakdownExcelXlsxStyled($dbConn, $estimateId);
    if ($ret !== true) {
        header("Content-Type: text/html; charset=UTF-8");
        echo "<script>alert('엑셀 생성 실패: " . htmlspecialchars((string)$ret, ENT_QUOTES, 'UTF-8') . "'); history.back();</script>";
    }
    exit;
} else {
    header("Content-Type: text/html; charset=UTF-8");
    echo "<!DOCTYPE html>
    <html><head><meta charset='UTF-8'><title>BREAKDOWN XLSX (Styled)</title>
    <style>body{font-family:Arial,sans-serif;max-width:520px;margin:50px auto;padding:20px}
    .form-group{margin-bottom:15px}label{display:block;margin-bottom:5px;font-weight:bold}
    input{width:100%;padding:8px;border:1px solid #ddd;border-radius:4px}
    button{background:#2E86AB;color:#fff;padding:10px 20px;border:none;border-radius:4px;cursor:pointer}</style>
    </head><body>
    <h2>BREAKDOWN QUOTATION .xlsx (Styled)</h2>
    <form method='GET'>
      <input type='hidden' name='action' value='download_excel'>
      <div class='form-group'><label for='estimate_id'>견적서 ID:</label>
      <input type='number' id='estimate_id' name='estimate_id' required placeholder='estimate_master의 id'></div>
      <div class='form-group'><button type='submit'>엑셀 다운로드</button></div>
    </form>
    </body></html>";
}

function streamBreakdownExcelXlsxStyled(mysqli $dbConn, int $estimateId) {
    // === 옵션 ===
    $MEAL_FILL_AUTO_SPAN = true; // true: master.start~end 날짜를 MEAL 헤더에 모두 포함
    $MAX_COL_IDX = 16;           // P 열
    $MAX_COL_LETTER = 'P';

    // --- 데이터 로드 ---
    $sql = "SELECT * FROM estimate_master WHERE id = ? LIMIT 1";
    if (!$stmt = $dbConn->prepare($sql)) return "master prepare 실패: ".$dbConn->error;
    $stmt->bind_param("i", $estimateId);
    if (!$stmt->execute()) return "master execute 실패: ".$stmt->error;
    $master = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$master) return "견적서를 찾을 수 없습니다. id=".$estimateId;

    $sql = "SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY section, id";
    if (!$stmt = $dbConn->prepare($sql)) return "items prepare 실패: ".$dbConn->error;
    $stmt->bind_param("i", $estimateId);
    if (!$stmt->execute()) return "items execute 실패: ".$stmt->error;
    $rs = $stmt->get_result();
    $items = [];
    while ($row = $rs->fetch_assoc()) $items[] = $row;
    $stmt->close();

    // ---------- 유틸 ----------
    $masterStart = isset($master['start_date']) ? trim((string)$master['start_date']) : '';

    $normalizeDate = function($s) use ($masterStart) {
        $s = trim((string)$s);
        if ($s === '') return '';
        $s = str_replace(['.','/'],'-',$s);
        if (preg_match('/^\d{8}$/',$s)) return substr($s,0,4).'-'.substr($s,4,2).'-'.substr($s,6,2);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)) return $s;
        if (preg_match('/^\d{1,2}-\d{1,2}$/',$s)) {
            $y = date('Y', $masterStart ? strtotime($masterStart) : time());
            list($m,$d) = explode('-', $s);
            return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
        }
        if (strpos($s,'~')!==false) { $left = explode('~',$s)[0] ?? ''; return trim($left); }
        return $s;
    };

    $addDays = function($baseYmd,$days){
        if(!$baseYmd||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$baseYmd)) return '';
        return date('Y-m-d', strtotime($baseYmd.' +'.(int)$days.' day'));
    };

    $resolveDate = function(string $section, array $etc) use ($normalizeDate,$addDays,$masterStart) {
        $candBy = [
            'HOTEL'=>['date','hotel_date','checkin_date'],
            'MEAL'=>['date','meal_date'],
            'TRANSPORT'=>['date','transport_date','drive_date'],
            'TICKET'=>['date','ticket_date','visit_date'],
            'GUIDE'=>['date','guide_date','service_date'],
            'OVERTIME'=>['date','overtime_date'],
            'TIP'=>['date','tip_date'],
            'PROFIT'=>['date'],
            'ETC'=>['date'],
        ];
        $cand = $candBy[$section] ?? ['date'];
        foreach ($cand as $k) if (!empty($etc[$k])) return $normalizeDate($etc[$k]);
        foreach (['day','day_index','d','dayno'] as $k) if (!empty($etc[$k]) && (int)$etc[$k]>0) return $addDays($masterStart,(int)$etc[$k]-1);
        if (isset($etc['day_offset'])) return $addDays($masterStart,(int)$etc['day_offset']);
        if (!empty($etc['date_range'])) {
            $left = explode('~', str_replace([' ','/','.'],['','-','-'], (string)$etc['date_range']))[0] ?? '';
            return $normalizeDate($left);
        }
        return '';
    };

    // 날짜 키 정규화(맵) → 'YYYY-MM-DD' 키로
    $normalizeKeyedDates = function(array $arr) use ($normalizeDate) {
        $out = [];
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
        $days = [];
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

    // 번호 -> 엑셀 컬럼 레터 (A..Z, AA..)
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
        $s = strtotime($startYmd ?: ''); $e = strtotime($endYmd ?: '');
        if (!$s || !$e || $e < $s) return [];
        $out = [];
        for ($t=$s; $t <= $e; $t = strtotime('+1 day', $t)) $out[] = date('Y-m-d', $t);
        return $out;
    };

    // --- PHPExcel 로더 ---
    $phpexcelCandidates = [
        __DIR__ . '/lib/PHPExcel/Classes/PHPExcel.php',
        __DIR__ . '/admin/lib/PHPExcel/Classes/PHPExcel.php',
        __DIR__ . '/vendor/phpoffice/phpexcel/Classes/PHPExcel.php',
        dirname(__DIR__) . '/vendor/phpoffice/phpexcel/Classes/PHPExcel.php'
    ];
    $loaded=false; foreach ($phpexcelCandidates as $p) { if (is_file($p)) { require_once $p; $loaded=true; break; } }
    if (!$loaded) return 'PHPExcel.php 를 찾을 수 없습니다.';

    // --- 워크북/시트 ---
    $excel = new PHPExcel();
    $excel->getProperties()->setCreator("System")->setTitle("BREAKDOWN QUOTATION");
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
    $bold=['font'=>['bold'=>true]];
    $center=['alignment'=>['horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_CENTER]];
    $wrap=['alignment'=>['wrap'=>true]];
    $vbCenter=['alignment'=>['vertical'=>PHPExcel_Style_Alignment::VERTICAL_CENTER]];
    $borderAll=['borders'=>['allborders'=>['style'=>PHPExcel_Style_Border::BORDER_THIN,'color'=>['rgb'=>$CLR_BORDER]]]];
    $setFill=function($range,$rgb)use($sheet){$sheet->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);};
    $setFontColor=function($range,$rgb)use($sheet){$sheet->getStyle($range)->getFont()->getColor()->setRGB($rgb);};

    // 기본 컬럼 폭 (A~P)
    foreach (range(1,$MAX_COL_IDX) as $i) {
        $c = $colByIdx($i);
        $sheet->getColumnDimension($c)->setWidth(in_array($c,['A','B','C','D','H'])?14:10);
    }

    $row = 1;

    // 섹션 분류
    $sections=[];
    foreach ($items as $it) {
        $sec = $it['section'] ?? 'ETC';
        if (!isset($sections[$sec])) $sections[$sec] = [];
        $sections[$sec][] = $it;
    }

    $order=['HOTEL','MEAL','TRANSPORT','OVERTIME','TICKET','GUIDE','ETC','TIP','PROFIT'];
    $titles=[
        'HOTEL'=>'1) HOTEL',
        'MEAL'=>'2) MEAL',
        'TRANSPORT'=>'3) TRANSPORTATION',
        'OVERTIME'=>'6) OVERTIME',
        'TICKET'=>'4) 입장권',
        'GUIDE'=>'5) 가이드 및 기사',
        'ETC'=>'7) 기타경비',
        'TIP'=>'8) 팁 & 매너',
        'PROFIT'=>'9) 회사 수익금'
    ];

    $buildViewDates = function($sectionItems) use ($master, $buildDateRange, $normalizeDate) {
        $sd = isset($master['start_date']) ? trim((string)$master['start_date']) : '';
        $ed = isset($master['end_date']) ? trim((string)$master['end_date']) : '';
        if ($sd !== '' && $ed !== '' && strtotime($sd) !== false && strtotime($ed) !== false) {
            return $buildDateRange($sd, $ed);
        }

        $set = [];
        foreach ($sectionItems as $it) {
            $etc = json_decode((string)($it['etc_json'] ?? '{}'), true);
            if (!is_array($etc) || empty($etc['dates']) || !is_array($etc['dates'])) continue;
            if (array_keys($etc['dates']) !== range(0, count($etc['dates']) - 1)) {
                foreach ($etc['dates'] as $d => $_) {
                    $d = $normalizeDate($d);
                    if ($d !== '') $set[$d] = true;
                }
            } else {
                foreach ($etc['dates'] as $d) {
                    $d = $normalizeDate($d);
                    if ($d !== '') $set[$d] = true;
                }
            }
        }
        $dates = array_keys($set);
        sort($dates);
        if (empty($dates)) {
            $base = time();
            for ($i = 0; $i < 3; $i++) $dates[] = date('Y-m-d', strtotime('+' . $i . ' day', $base));
        }
        return $dates;
    };

    // ── estimate_view.php와 같은 날짜열 준비 ──
    $mealAllDates  = !empty($sections['MEAL']) ? $buildViewDates($sections['MEAL']) : [];
    $transAllDates = !empty($sections['TRANSPORT']) ? $buildViewDates($sections['TRANSPORT']) : [];
    $otAllDates    = $buildViewDates(isset($sections['OVERTIME']) ? $sections['OVERTIME'] : []);

    // ── P 상한에 맞춰 날짜 열 수 절단 ──
    // MEAL: A(구분)=1 + 날짜N + [일인당합계단가, 인원수, 합계]=3 → N <= 16-1-3 = 12
    $MEAL_MAX_DATES = max(0, $MAX_COL_IDX - 1 - 3); // 12
    if (count($mealAllDates) > $MEAL_MAX_DATES) {
        $mealAllDates = array_slice($mealAllDates, 0, $MEAL_MAX_DATES);
    }
    // TRANSPORT: A(차량)=1 + 날짜N + [차량수, 합계]=2 → N <= 16-1-2 = 13
    $TRANS_MAX_DATES = max(0, $MAX_COL_IDX - 1 - 2); // 13
    if (count($transAllDates) > $TRANS_MAX_DATES) {
        $transAllDates = array_slice($transAllDates, 0, $TRANS_MAX_DATES);
    }
    if (count($otAllDates) > $TRANS_MAX_DATES) {
        $otAllDates = array_slice($otAllDates, 0, $TRANS_MAX_DATES);
    }

    // ── “가장 긴 열” 계산 후 P 상한 적용 ──
    $MEAL_LAST_COL_IDX  = !empty($mealAllDates)  ? (1 + count($mealAllDates)  + 3) : 0;
    $TRANS_LAST_COL_IDX = !empty($transAllDates) ? (1 + count($transAllDates) + 2) : 0;
    $OT_LAST_COL_IDX = !empty($otAllDates) ? (1 + count($otAllDates) + 2) : 0;
    $LAST_COL_IDX = min($MAX_COL_IDX, max(8, $MEAL_LAST_COL_IDX, $TRANS_LAST_COL_IDX, $OT_LAST_COL_IDX));
    $L = $colByIdx($LAST_COL_IDX); // 시트 운용상의 실제 끝 컬럼(최대 P)

    // 합계/우측 영역을 시트 끝까지 병합하는 헬퍼
    $mergeToSheetEnd = function($row, $fromColLetter) use ($sheet, $L) {
        if ($L !== $fromColLetter) {
            $sheet->mergeCells("{$fromColLetter}{$row}:{$L}{$row}");
        }
    };

    // ── 타이틀 ──
    $sheet->mergeCells("A{$row}:{$L}{$row}");
    $sheet->setCellValue("A{$row}","BREAKDOWN QUOTATION");
    $sheet->getStyle("A{$row}")->applyFromArray($bold + $center);
    $sheet->getStyle("A{$row}")->getFont()->setSize(18);
    $setFill("A{$row}:{$L}{$row}",$CLR_BLUE); $setFontColor("A{$row}:{$L}{$row}",$CLR_WHITE);
    $row += 2;

    // 기본정보
    $firstInfoRow = $row;
    $sheet->fromArray([['PAX',(int)($master['pax']??0),'FOC',(int)($master['foc']??0),'총인원',(int)($master['total_pax']??0),'TO',(string)($master['to_name']??'')]],null,"A{$row}");
    $sheet->fromArray([['여행 시작일',(string)($master['start_date']??''),'여행 종료일',(string)($master['end_date']??''),'작성일',(string)($master['wdate']??''),'GROUP',(string)($master['group_name']??'')]],null,"A".($row+1));
    foreach (['A','C','E','G'] as $c){ $setFill("{$c}{$row}",$CLR_HGRAY); $setFill("{$c}".($row+1),$CLR_HGRAY); }
    $sheet->getStyle("A{$row}:{$L}".($row+1))->applyFromArray($borderAll + $vbCenter);
    $row += 3;

    $sectionTotals = [];
    $subtotalAddrs = []; // 섹션별 소계 셀 주소 (수식 참조용)
    $hasMasterProfit = ((float)($master['profit'] ?? 0) > 0 || trim((string)($master['profit_memo'] ?? '')) !== '');

    // ───────────────────── 렌더링 ─────────────────────
    foreach ($order as $sec) {
        if (empty($sections[$sec]) && $sec !== 'OVERTIME' && !($sec === 'PROFIT' && $hasMasterProfit)) continue;

        // 섹션 타이틀 바
        $sheet->mergeCells("A{$row}:{$L}{$row}");
        $sheet->setCellValue("A{$row}", $titles[$sec]);
        $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $vbCenter);
        $setFill("A{$row}:{$L}{$row}", $CLR_YELLOW); $setFontColor("A{$row}:{$L}{$row}", $CLR_BLACK);
        $row++;

        $startDataRow = $row;

        // ── HOTEL ──
        if ($sec==='HOTEL') {
            $hotelTotal = 0.0;
            $sheet->fromArray([['지역','날짜','요일','호텔명','방수','요금(USD)','박수','합계']],null,"A{$row}");
            // 헤더 마지막 셀(H) ~ L 까지 병합 일관화
            $mergeToSheetEnd($row, 'H');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            $hotelFirstRow = $row;
            foreach ($sections[$sec] as $it) {
                $etc=json_decode((string)($it['etc_json']??'{}'),true)?:[];
                $a=(string)($etc['region']??'');
                $b=$resolveDate('HOTEL',$etc);
                $c=(string)($etc['weekday']??'');
                $d=(string)($it['label']??'');
                $e=(float)($it['cnt']??0); $f=(float)($it['unit']??0); $g=(float)($it['qty']??0);
                $hotelTotal += (float)($it['sum'] ?? 0);

                $sheet->fromArray([[ $a,$b,$c,$d,$e,$f,$g ]],null,"A{$row}");
                $sheet->setCellValue("H{$row}", "=E{$row}*F{$row}*MAX(1,G{$row})");
                $sheet->getStyle("E{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'H');
                $row++;
            }
            $hotelLastRow = $row - 1;

            // 소계
            $sheet->mergeCells("A{$row}:G{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            if ($hotelFirstRow <= $hotelLastRow) {
                $sheet->setCellValue("H{$row}", "=SUM(H{$hotelFirstRow}:H{$hotelLastRow})");
            } else {
                $sheet->setCellValue("H{$row}", 0);
            }
            $mergeToSheetEnd($row,'H');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sectionTotals['HOTEL'] = $hotelTotal;
            $subtotalAddrs['HOTEL'] = "H{$row}";
            $row+=2;
            continue;
        }

        // ── MEAL (동적 매트릭스) ──
        if ($sec==='MEAL') {
            // 헤더: A=구분 | dates... | 일인당 합계단가 | 인원수 | 합계
            $col=1; $sheet->setCellValue($colByIdx($col).$row,'구분'); $col++;
            foreach($mealAllDates as $ymd){ $sheet->setCellValue($colByIdx($col).$row,$ymd); $col++; }
            $colSumPerPax=$col; $sheet->setCellValue($colByIdx($col).$row,'일인당 합계단가'); $col++;
            $colPax=$col;       $sheet->setCellValue($colByIdx($col).$row,'인원수');         $col++;
            $colTotal=$col;     $sheet->setCellValue($colByIdx($col).$row,'합계');

            // 헤더 마지막 셀(합계) ~ L까지 병합
            $mergeToSheetEnd($row, $colByIdx($colTotal));

            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            // 데이터 집계
            $mealTypes = ['BREAKFAST','LUNCH','DINNER'];
            $grid=['BREAKFAST'=>[],'LUNCH'=>[],'DINNER'=>[]];
            $mealUnits=['BREAKFAST'=>0.0,'LUNCH'=>0.0,'DINNER'=>0.0];
            $paxByMeal=['BREAKFAST'=>0,'LUNCH'=>0,'DINNER'=>0];
            $mealSavedTotals=['BREAKFAST'=>0.0,'LUNCH'=>0.0,'DINNER'=>0.0];
            $defaultPax=(int)($master['total_pax']??0);

            foreach ($mealAllDates as $d) foreach ($mealTypes as $mt) $grid[$mt][$d] = 0;

            $mealFallbackIndex = 0;
            foreach ($sections[$sec] as $it){
                $label=(string)($it['label']??'');
                $etc=json_decode((string)($it['etc_json']??'{}'),true)?:[];
                $mk='';
                if (isset($etc['meal_type']) && $etc['meal_type'] !== '') {
                    $mk = (string)$etc['meal_type'];
                } else {
                    if (stripos($label,'breakfast')!==false) $mk='BREAKFAST';
                    elseif (stripos($label,'lunch')!==false) $mk='LUNCH';
                    elseif (stripos($label,'dinner')!==false) $mk='DINNER';
                }
                if ($mk === '' && isset($mealTypes[$mealFallbackIndex])) $mk = $mealTypes[$mealFallbackIndex];
                $mealFallbackIndex++;
                if (!in_array($mk, $mealTypes, true)) continue;

                $mealSavedTotals[$mk] += (float)($it['sum'] ?? 0);
                $pax=(int)($etc['pax'] ?? $defaultPax);
                if ($pax>0 && $paxByMeal[$mk] <= 0) $paxByMeal[$mk]=$pax;
                if (isset($etc['unit_per_pax']) && is_numeric($etc['unit_per_pax']) && $mealUnits[$mk] <= 0) {
                    $mealUnits[$mk] = (float)$etc['unit_per_pax'];
                }

                if (!empty($etc['dates']) && is_array($etc['dates'])) {
                    if (array_keys($etc['dates']) !== range(0, count($etc['dates'])-1)) {
                        foreach ($etc['dates'] as $ymd=>$cnt) {
                            $ymd = $normalizeDate($ymd);
                            if (isset($grid[$mk][$ymd])) $grid[$mk][$ymd] += (int)$cnt;
                        }
                    } else {
                        foreach ($etc['dates'] as $ymd) {
                            $ymd = $normalizeDate($ymd);
                            if (isset($grid[$mk][$ymd])) $grid[$mk][$ymd] += 1;
                        }
                    }
                }
            }

            $mealRowTotals=[];
            $mealTotal = 0.0;
            $perPersonTotal = 0.0;
            $paxDisplay = 0;
            $mealFirstRow = $row;
            foreach ($mealTypes as $mk) {
                $sheet->setCellValue("A{$row}", $mk);
                $col=2; // 날짜열 시작
                $firstDateAddr = !empty($mealAllDates) ? $colByIdx(2).$row : '';
                $lastDateAddr = !empty($mealAllDates) ? $colByIdx(1+count($mealAllDates)).$row : '';
                foreach ($mealAllDates as $ymd){
                    $val = isset($grid[$mk][$ymd]) ? (float)$grid[$mk][$ymd] : 0.0;
                    $addr=$colByIdx($col).$row;
                    $sheet->setCellValue($addr,$val);
                    $sheet->getStyle($addr)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                    $col++;
                }
                $addrSum=$colByIdx($col).$row;
                if (!empty($mealAllDates)) {
                    $sheet->setCellValue($addrSum, "=SUM({$firstDateAddr}:{$lastDateAddr})");
                } else {
                    $sheet->setCellValue($addrSum, (float)$mealUnits[$mk]);
                }
                $sheet->getStyle($addrSum)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0'); $col++;

                $addrPax=$colByIdx($col).$row; $sheet->setCellValueExplicit($addrPax,(int)$paxByMeal[$mk],PHPExcel_Cell_DataType::TYPE_NUMERIC); $col++;

                $addrTot=$colByIdx($col).$row;
                $rowTotal = ((float)$mealSavedTotals[$mk] != 0.0) ? (float)$mealSavedTotals[$mk] : ((float)$mealUnits[$mk] * (int)$paxByMeal[$mk]);
                $mealTotal += $rowTotal;
                $perPersonTotal += (float)$mealUnits[$mk];
                if ($paxDisplay === 0 && $paxByMeal[$mk] > 0) $paxDisplay = (int)$paxByMeal[$mk];
                $sheet->setCellValue($addrTot, "={$addrSum}*{$addrPax}");
                $sheet->getStyle($addrTot)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $mealRowTotals[]=$addrTot;

                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                // 합계 셀 ~ L 까지 병합
                $mergeToSheetEnd($row, $colByIdx($col));
                $row++;
            }
            $mealLastRow = $row - 1;

            // 소계
            $sheet->mergeCells("A{$row}:".$colByIdx($colSumPerPax-1)."{$row}");
            $sheet->setCellValue("A{$row}", 'MEAL 소계');
            $colSumLetter = $colByIdx($colSumPerPax);
            $colTotalLetter = $colByIdx($colTotal);
            $sheet->setCellValue($colSumLetter.$row, "=SUM({$colSumLetter}{$mealFirstRow}:{$colSumLetter}{$mealLastRow})");
            $sheet->setCellValue($colByIdx($colPax).$row, $paxDisplay);
            $sheet->setCellValue($colTotalLetter.$row, "=SUM({$colTotalLetter}{$mealFirstRow}:{$colTotalLetter}{$mealLastRow})");
            $mergeToSheetEnd($row, $colTotalLetter);
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle($colSumLetter.$row)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sheet->getStyle($colTotalLetter.$row)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sectionTotals['MEAL'] = $mealTotal;
            $subtotalAddrs['MEAL'] = $colTotalLetter.$row;
            $row+=2;
            continue;
        }

        // ── TRANSPORT (일자별 차량료 매트릭스) ──
        if ($sec==='TRANSPORT') {
            // 헤더: A=차량 | dates(단가)... | 차량수 | 합계
            $col=1; $sheet->setCellValue($colByIdx($col).$row,'차량'); $col++;
            foreach($transAllDates as $ymd){ $sheet->setCellValue($colByIdx($col).$row,$ymd); $col++; }
            $colCar=$col;       $sheet->setCellValue($colByIdx($col).$row,'차량수'); $col++;
            $colTotal=$col;     $sheet->setCellValue($colByIdx($col).$row,'합계');

            // 헤더 마지막(합계) ~ L 병합
            $mergeToSheetEnd($row, $colByIdx($colTotal));

            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            // 차량/노선별 집계 (날짜셀 = 단가/금액)
            $rows = []; // key => ['title'=>..., 'car'=>N, 'sum'=>amount, 'rates'=>[ymd=>rate]]
            foreach ($sections[$sec] as $it) {
                $etc  = json_decode((string)($it['etc_json']??'{}'),true)?:[];
                $key  = trim((string)($it['label'] ?? 'Transportation'));
                if ($key === '') $key = 'Transportation';

                if (!isset($rows[$key])) $rows[$key] = ['title'=>$key,'car'=>0.0,'sum'=>0.0,'rates'=>[]];

                $car = 0.0;
                if (isset($etc['unit_per_car']) && is_numeric($etc['unit_per_car'])) $car = (float)$etc['unit_per_car'];
                elseif (isset($it['cnt']) && is_numeric($it['cnt'])) $car = (float)$it['cnt'];
                if ($car > 0) $rows[$key]['car'] = $car;
                $rows[$key]['sum'] += (float)($it['sum'] ?? 0);

                if (!empty($etc['dates']) && is_array($etc['dates'])) {
                    if (array_keys($etc['dates']) !== range(0, count($etc['dates'])-1)) {
                        foreach ($etc['dates'] as $ymd=>$rate) {
                            $ymd = $normalizeDate($ymd);
                            if (!isset($rows[$key]['rates'][$ymd])) $rows[$key]['rates'][$ymd]=0.0;
                            if (in_array($ymd, $transAllDates, true)) $rows[$key]['rates'][$ymd] += (float)$rate;
                        }
                    } else {
                        foreach ($etc['dates'] as $ymd) {
                            $ymd = $normalizeDate($ymd);
                            if (!isset($rows[$key]['rates'][$ymd])) $rows[$key]['rates'][$ymd]=0.0;
                            if (in_array($ymd, $transAllDates, true)) $rows[$key]['rates'][$ymd] += 1.0;
                        }
                    }
                }
            }

            $transRowTotals=[];
            $transportTotal = 0.0;
            $transFirstRow = $row;
            $transFirstDateCol = !empty($transAllDates) ? $colByIdx(2) : '';
            $transLastDateCol = !empty($transAllDates) ? $colByIdx(1 + count($transAllDates)) : '';
            foreach ($rows as $r) {
                $sheet->setCellValue("A{$row}", $r['title']);

                // 날짜칸: 단가(금액) 입력
                $col=2; $rateSum=0.0;
                foreach ($transAllDates as $ymd){
                    $addr=$colByIdx($col).$row;
                    $rate = isset($r['rates'][$ymd]) ? (float)$r['rates'][$ymd] : 0.0;
                    $rateSum += $rate;
                    $sheet->setCellValueExplicit($addr,$rate, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $sheet->getStyle($addr)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                    $col++;
                }

                // 차량수
                $addrCar=$colByIdx($col).$row;
                $sheet->setCellValueExplicit($addrCar,(float)$r['car'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle($addrCar)->getNumberFormat()->setFormatCode('#,##0.00');
                $col++;

                $addrTot  = $colByIdx($col).$row;
                $transportTotal += ((float)$r['sum'] != 0.0) ? (float)$r['sum'] : ($rateSum * max(1.0, (float)$r['car']));
                if (!empty($transAllDates)) {
                    $sheet->setCellValue($addrTot, "=SUM({$transFirstDateCol}{$row}:{$transLastDateCol}{$row})*MAX(1,{$addrCar})");
                } else {
                    $sheet->setCellValue($addrTot, 0);
                }
                $sheet->getStyle($addrTot)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $transRowTotals[]=$addrTot;

                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                // 합계 셀 ~ L 병합
                $mergeToSheetEnd($row, $colByIdx($col));
                $row++;
            }
            $transLastRow = $row - 1;

            // 소계
            $sheet->mergeCells("A{$row}:".$colByIdx($colCar-1)."{$row}");
            $sheet->setCellValue("A{$row}", 'TRANSPORTATION 소계');
            $sheet->setCellValue($colByIdx($colCar).$row, '');
            $colTotLetter = $colByIdx($colTotal);
            if ($transFirstRow <= $transLastRow) {
                $sheet->setCellValue($colTotLetter.$row, "=SUM({$colTotLetter}{$transFirstRow}:{$colTotLetter}{$transLastRow})");
            } else {
                $sheet->setCellValue($colTotLetter.$row, 0);
            }
            $mergeToSheetEnd($row, $colTotLetter);
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle($colTotLetter.$row)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sectionTotals['TRANSPORT'] = $transportTotal;
            $subtotalAddrs['TRANSPORT'] = $colTotLetter.$row;
            $row+=2;
            continue;
        }

        // ── OVERTIME (날짜셀 = 금액, reasons = 셀 코멘트) ──
        if ($sec==='OVERTIME') {
            $col=1; $sheet->setCellValue($colByIdx($col).$row,'오버타임'); $col++;
            foreach($otAllDates as $ymd){ $sheet->setCellValue($colByIdx($col).$row,$ymd); $col++; }
            $colTarget=$col; $sheet->setCellValue($colByIdx($col).$row,'건수'); $col++;
            $colTotal=$col;  $sheet->setCellValue($colByIdx($col).$row,'합계');
            $mergeToSheetEnd($row, $colByIdx($colTotal));
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            $rows = [];
            foreach ((isset($sections[$sec]) ? $sections[$sec] : []) as $it) {
                $etc = json_decode((string)($it['etc_json']??'{}'), true) ?: [];
                $key = trim((string)($it['label'] ?? '오버타임'));
                if ($key === '') $key = '오버타임';
                if (!isset($rows[$key])) $rows[$key] = ['title'=>$key,'target'=>0.0,'sum'=>0.0,'rates'=>[],'reasons'=>[]];

                $target = 0.0;
                if (isset($etc['unit_per_target']) && is_numeric($etc['unit_per_target'])) $target = (float)$etc['unit_per_target'];
                elseif (isset($it['cnt']) && is_numeric($it['cnt'])) $target = (float)$it['cnt'];
                if ($target > 0) $rows[$key]['target'] = $target;
                $rows[$key]['sum'] += (float)($it['sum'] ?? 0);

                if (!empty($etc['dates']) && is_array($etc['dates'])) {
                    if (array_keys($etc['dates']) !== range(0, count($etc['dates'])-1)) {
                        foreach ($etc['dates'] as $ymd=>$rate) {
                            $ymd = $normalizeDate($ymd);
                            if (!isset($rows[$key]['rates'][$ymd])) $rows[$key]['rates'][$ymd] = 0.0;
                            if (in_array($ymd, $otAllDates, true)) $rows[$key]['rates'][$ymd] += (float)$rate;
                        }
                    } else {
                        foreach ($etc['dates'] as $ymd) {
                            $ymd = $normalizeDate($ymd);
                            if (!isset($rows[$key]['rates'][$ymd])) $rows[$key]['rates'][$ymd] = 0.0;
                            if (in_array($ymd, $otAllDates, true)) $rows[$key]['rates'][$ymd] += 1.0;
                        }
                    }
                }
                if (!empty($etc['reasons']) && is_array($etc['reasons'])) {
                    foreach ($etc['reasons'] as $ymd=>$reason) {
                        $ymd = $normalizeDate($ymd);
                        if ($ymd !== '' && in_array($ymd, $otAllDates, true)) {
                            $rows[$key]['reasons'][$ymd] = (string)$reason;
                        }
                    }
                }
            }
            if (empty($rows)) $rows['오버타임'] = ['title'=>'오버타임','target'=>0.0,'sum'=>0.0,'rates'=>[],'reasons'=>[]];

            $otRowTotals=[];
            $overtimeTotal = 0.0;
            $otFirstRow = $row;
            $otFirstDateCol = !empty($otAllDates) ? $colByIdx(2) : '';
            $otLastDateCol = !empty($otAllDates) ? $colByIdx(1 + count($otAllDates)) : '';
            foreach ($rows as $r) {
                $sheet->setCellValue("A{$row}", $r['title']);
                $col=2; $rateSum=0.0;
                foreach ($otAllDates as $ymd) {
                    $addr=$colByIdx($col).$row;
                    $rate = isset($r['rates'][$ymd]) ? (float)$r['rates'][$ymd] : 0.0;
                    $rateSum += $rate;
                    $sheet->setCellValueExplicit($addr,$rate, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $sheet->getStyle($addr)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                    $reason = isset($r['reasons'][$ymd]) ? (string)$r['reasons'][$ymd] : '';
                    if ($reason !== '') {
                        $sheet->getComment($addr)->getText()->createTextRun($reason);
                    }
                    $col++;
                }
                $addrTarget=$colByIdx($col).$row;
                $sheet->setCellValueExplicit($addrTarget,(float)$r['target'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle($addrTarget)->getNumberFormat()->setFormatCode('#,##0.00');
                $col++;
                $addrTot=$colByIdx($col).$row;
                $overtimeTotal += ((float)$r['sum'] != 0.0) ? (float)$r['sum'] : ($rateSum * max(1.0, (float)$r['target']));
                if (!empty($otAllDates)) {
                    $sheet->setCellValue($addrTot, "=SUM({$otFirstDateCol}{$row}:{$otLastDateCol}{$row})*MAX(1,{$addrTarget})");
                } else {
                    $sheet->setCellValue($addrTot, 0);
                }
                $sheet->getStyle($addrTot)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $otRowTotals[]=$addrTot;
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row, $colByIdx($col));
                $row++;
            }
            $otLastRow = $row - 1;

            $sheet->mergeCells("A{$row}:".$colByIdx($colTarget-1)."{$row}");
            $sheet->setCellValue("A{$row}", 'OVERTIME 소계');
            $sheet->setCellValue($colByIdx($colTarget).$row, '');
            $colTotLetterOt = $colByIdx($colTotal);
            if ($otFirstRow <= $otLastRow) {
                $sheet->setCellValue($colTotLetterOt.$row, "=SUM({$colTotLetterOt}{$otFirstRow}:{$colTotLetterOt}{$otLastRow})");
            } else {
                $sheet->setCellValue($colTotLetterOt.$row, 0);
            }
            $mergeToSheetEnd($row, $colTotLetterOt);
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle($colTotLetterOt.$row)->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sectionTotals['OVERTIME'] = $overtimeTotal;
            $subtotalAddrs['OVERTIME'] = $colTotLetterOt.$row;
            $row+=2;
            continue;
        }

        if ($sec==='TICKET') {
            $ticketTotal = 0.0;
            $sheet->fromArray([['입장지','단가','인원','합계']],null,"A{$row}");
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            $tkFirstRow = $row;
            foreach ($sections[$sec] as $it) {
                $place=(string)($it['label']??'');
                $unit=(float)($it['unit']??0);
                $qty=(float)($it['qty']??0);
                $people = $qty;
                $ticketTotal += (float)($it['sum'] ?? 0);

                $sheet->fromArray([[ $place, $unit, $people ]],null,"A{$row}");
                $sheet->setCellValue("D{$row}", "=B{$row}*C{$row}");
                $sheet->getStyle("B{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'D');
                $row++;
            }
            $tkLastRow = $row - 1;

            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            if ($tkFirstRow <= $tkLastRow) {
                $sheet->setCellValue("D{$row}", "=SUM(D{$tkFirstRow}:D{$tkLastRow})");
            } else {
                $sheet->setCellValue("D{$row}", 0);
            }
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sectionTotals['TICKET'] = $ticketTotal;
            $subtotalAddrs['TICKET'] = "D{$row}";
            $row+=2;
            continue;
        }

        // ── GUIDE (항목 / 기간(일/시간) / 인원·대수 / 단가 / 합계) ──
        if ($sec==='GUIDE') {
            $guideTotal = 0.0;
            $sheet->fromArray([['항목','기간(일/시간)','인원/대수','단가','합계']],null,"A{$row}");
            $mergeToSheetEnd($row,'E');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            $gdFirstRow = $row;
            foreach ($sections[$sec] as $it) {
                $etc=json_decode((string)($it['etc_json']??'{}'),true)?:[];
                $label=(string)($it['label']??($etc['service_type']??''));
                $qty=(float)($it['qty']??0);
                $cnt=(float)($it['cnt']??1);
                $unit=(float)($it['unit']??0);
                $guideTotal += (float)($it['sum'] ?? 0);

                $sheet->fromArray([[ $label, $qty, $cnt, $unit ]],null,"A{$row}");
                // 합계 = 단가(D) × 기간(B) × MAX(1, 인원/대수(C))
                $sheet->setCellValue("E{$row}", "=D{$row}*B{$row}*MAX(1,C{$row})");
                $sheet->getStyle("B{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'E');
                $row++;
            }
            $gdLastRow = $row - 1;

            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            if ($gdFirstRow <= $gdLastRow) {
                $sheet->setCellValue("E{$row}", "=SUM(E{$gdFirstRow}:E{$gdLastRow})");
            } else {
                $sheet->setCellValue("E{$row}", 0);
            }
            $mergeToSheetEnd($row,'E');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sectionTotals['GUIDE'] = $guideTotal;
            $subtotalAddrs['GUIDE'] = "E{$row}";
            $row+=2;
            continue;
        }

        // ── ETC (기타경비: 항목 / 인원·수량 / 단가 / 합계) ──
        if ($sec==='ETC') {
            $etcTotal = 0.0;
            $sheet->fromArray([['항목','인원/수량','단가','합계']],null,"A{$row}");
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            $etcFirstRow = $row;
            foreach ($sections[$sec] as $it) {
                $label=(string)($it['label']??'');
                $unit=(float)($it['unit']??0); $qty=(float)($it['qty']??0);
                $etcTotal += (float)($it['sum'] ?? 0);

                $sheet->fromArray([[ $label, $qty, $unit ]], null, "A{$row}");
                $sheet->setCellValue("D{$row}", "=B{$row}*C{$row}");
                $sheet->getStyle("B{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'D');
                $row++;
            }
            $etcLastRow = $row - 1;

            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}",$titles[$sec].' 소계');
            if ($etcFirstRow <= $etcLastRow) {
                $sheet->setCellValue("D{$row}", "=SUM(D{$etcFirstRow}:D{$etcLastRow})");
            } else {
                $sheet->setCellValue("D{$row}", 0);
            }
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sectionTotals['ETC'] = $etcTotal;
            $subtotalAddrs['ETC'] = "D{$row}";
            $row+=2;
            continue;
        }

        // ── TIP (팁 & 매너: 항목 / 가이드팁 / 기사팁 / 일수 / 인원 / 합계) ──
        if ($sec==='TIP') {
            $tipTotal = 0.0;
            $sheet->fromArray([['항목','가이드 팁','기사 팁','일수','인원','합계']],null,"A{$row}");
            $mergeToSheetEnd($row,'F');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            $tipFirstRow = $row;
            foreach ($sections[$sec] as $it) {
                $label=(string)($it['label']??'Tip & Manner');
                $etc = json_decode((string)($it['etc_json']??'{}'), true) ?: [];
                $guideTip  = isset($etc['guide'])  && is_numeric($etc['guide'])  ? (float)$etc['guide']  : 0.0;
                $driverTip = isset($etc['driver']) && is_numeric($etc['driver']) ? (float)$etc['driver'] : 0.0;
                if ($guideTip == 0.0 && $driverTip == 0.0) {
                    // 폴백: unit 전체를 가이드팁으로
                    $guideTip = (float)($it['unit']??0);
                }
                $days = (float)($it['qty']??0);
                $pax  = (float)($it['cnt']??0);
                $tipTotal += (float)($it['sum'] ?? 0);
                $sheet->fromArray([[ $label, $guideTip, $driverTip, $days, $pax ]],null,"A{$row}");
                // 합계 = (가이드팁 + 기사팁) × 일수 × 인원
                $sheet->setCellValue("F{$row}", "=(B{$row}+C{$row})*D{$row}*E{$row}");
                $sheet->getStyle("B{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'F');
                $row++;
            }
            $tipLastRow = $row - 1;

            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->setCellValue("A{$row}",'TIP 소계');
            if ($tipFirstRow <= $tipLastRow) {
                $sheet->setCellValue("F{$row}", "=SUM(F{$tipFirstRow}:F{$tipLastRow})");
            } else {
                $sheet->setCellValue("F{$row}", 0);
            }
            $mergeToSheetEnd($row,'F');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sectionTotals['TIP'] = $tipTotal;
            $subtotalAddrs['TIP'] = "F{$row}";
            $row+=2;
            continue;
        }

        if ($sec==='PROFIT') {
            $profitTotal = 0.0;
            $sheet->fromArray([['항목','단가','수량','합계']],null,"A{$row}");
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_HGRAY);
            $row++;

            $pfFirstRow = $row;
            if (!empty($sections[$sec])) {
                foreach ($sections[$sec] as $it) {
                    $label=(string)($it['label']??'PROFIT');
                    $unit=(float)($it['unit']??0);
                    $qty=(float)($it['qty']??0);
                    $profitTotal += (float)($it['sum'] ?? 0);
                    $sheet->fromArray([[ $label, $unit, $qty ]],null,"A{$row}");
                    $sheet->setCellValue("D{$row}", "=B{$row}*C{$row}");
                    $sheet->getStyle("B{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                    $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                    $mergeToSheetEnd($row,'D');
                    $row++;
                }
            } else {
                $label = trim((string)($master['profit_memo'] ?? '')) !== '' ? trim((string)$master['profit_memo']) : 'PROFIT';
                $profitTotal = (float)($master['profit'] ?? 0);
                $sheet->mergeCells("A{$row}:C{$row}");
                $sheet->setCellValue("A{$row}", $label);
                $sheet->setCellValue("D{$row}", $profitTotal);
                $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
                $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($borderAll + $vbCenter + $wrap);
                $mergeToSheetEnd($row,'D');
                $row++;
            }
            $pfLastRow = $row - 1;

            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}",'PROFIT 소계');
            if ($pfFirstRow <= $pfLastRow) {
                $sheet->setCellValue("D{$row}", "=SUM(D{$pfFirstRow}:D{$pfLastRow})");
            } else {
                $sheet->setCellValue("D{$row}", 0);
            }
            $mergeToSheetEnd($row,'D');
            $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $borderAll + $vbCenter);
            $setFill("A{$row}:{$L}{$row}",$CLR_LBLUE);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
            $sectionTotals['PROFIT'] = $profitTotal;
            $subtotalAddrs['PROFIT'] = "D{$row}";
            $row+=2;
            continue;
        }
    }

    // 총합 & 1인당 (오른쪽 끝 ~ L까지 병합)
    $autoGrand = 0.0;
    foreach ($sectionTotals as $v) $autoGrand += (float)$v;
    $grandTotal = (isset($master['grand_total']) && is_numeric($master['grand_total'])) ? (float)$master['grand_total'] : 0.0;
    $displayTotal = ($grandTotal > 0) ? $grandTotal : $autoGrand;
    $perPax = (isset($master['per_pax']) && is_numeric($master['per_pax'])) ? (float)$master['per_pax'] : 0.0;
    $chargePax = (int)($master['pax'] ?? 0) - (int)($master['foc'] ?? 0);
    if ($chargePax < 0) $chargePax = 0;
    $perPaxDivisor = ($chargePax > 0) ? ($chargePax + 1) : 0;
    if ($perPax <= 0 && $grandTotal > 0 && $perPaxDivisor > 0) {
        $perPax = $grandTotal / $perPaxDivisor;
    } elseif ($perPax <= 0 && $grandTotal <= 0 && $autoGrand > 0 && $perPaxDivisor > 0) {
        $perPax = $autoGrand / $perPaxDivisor;
    }

    // 섹션별 요약 (form의 summary pills 라벨에 맞춤) — 수식으로 라이브 합산
    $summaryLabels = [
        'HOTEL'     => 'HOTEL',
        'MEAL'      => 'MEAL',
        'TRANSPORT' => 'TRANS',
        'OVERTIME'  => 'OVERTIME',
        'TICKET'    => '입장권',
        'GUIDE'     => '가이드/기사',
        'ETC'       => '기타경비',
        'TIP'       => '팁',
        'PROFIT'    => '마진',
    ];
    $summaryFormulaParts = [];
    foreach ($summaryLabels as $key => $label) {
        if (isset($subtotalAddrs[$key])) {
            $addr = $subtotalAddrs[$key];
            $summaryFormulaParts[] = '"' . $label . ': $"&TEXT(' . $addr . ',"#,##0.00")';
        } else {
            $summaryFormulaParts[] = '"' . $label . ': $0.00"';
        }
    }
    $summaryFormula = '=' . implode('&"  |  "&', $summaryFormulaParts);
    $sheet->mergeCells("A{$row}:{$L}{$row}");
    $sheet->setCellValue("A{$row}", $summaryFormula);
    $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $center + $vbCenter + $borderAll + $wrap);
    $setFill("A{$row}:{$L}{$row}", $CLR_LBLUE);
    $row++;

    // TOTAL TOUR FEE = 모든 섹션 소계의 합
    $totalRow=$row;
    $sheet->mergeCells("A{$row}:D{$row}");
    $sheet->setCellValue("A{$row}",'10) TOTAL TOUR FEE');
    if (!empty($subtotalAddrs)) {
        $sheet->setCellValue("H{$row}", "=" . implode("+", array_values($subtotalAddrs)));
    } else {
        $sheet->setCellValue("H{$row}", $displayTotal);
    }
    $mergeToSheetEnd($row,'H');
    $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $vbCenter + $borderAll);
    $setFill("A{$row}:{$L}{$row}",$CLR_BLUE); $setFontColor("A{$row}:{$L}{$row}",$CLR_WHITE);
    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');
    $grandTotalAddr = "H{$row}";
    $row++;

    // 1인당 요금 = TOTAL / (charge_pax + 1)
    $sheet->mergeCells("A{$row}:D{$row}");
    $sheet->setCellValue("A{$row}",'11) 1인당 요금');
    if ($perPaxDivisor > 0) {
        $sheet->setCellValue("H{$row}", "={$grandTotalAddr}/{$perPaxDivisor}");
    } else {
        $sheet->setCellValue("H{$row}", $perPax);
    }
    $mergeToSheetEnd($row,'H');
    $sheet->getStyle("A{$row}:{$L}{$row}")->applyFromArray($bold + $vbCenter + $borderAll);
    $setFill("A{$row}:{$L}{$row}",$CLR_BLUE); $setFontColor("A{$row}:{$L}{$row}",$CLR_WHITE);
    $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;0');

    // 정렬
    $sheet->getStyle("A1:{$L}{$row}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("A1:{$L}{$row}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    // 출력
    $groupName = trim((string)($master['group_name'] ?? '')) ?: ('GROUP_'.$estimateId);
    $safeGroup = preg_replace('/[^a-zA-Z0-9가-힣_-]+/', '_', $groupName);
    $filename  = "BREAKDOWN_QUOTATION_{$safeGroup}_".date('Ymd').".xlsx";

    @ini_set('zlib.output_compression','0'); while (ob_get_level()) @ob_end_clean();

    $writer = PHPExcel_IOFactory::createWriter($excel,'Excel2007');
    $writer->setPreCalculateFormulas(true);
    $tmp = tempnam(sys_get_temp_dir(),'xl_'); $writer->save($tmp);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate, max-age=0');
    header('Pragma: public'); header('Expires: 0');
    header('Content-Length: '.filesize($tmp));

    $fp=fopen($tmp,'rb'); fpassthru($fp); fclose($fp); @unlink($tmp);
    return true;
}
