<?php
/* ============================================================
 * 호텔 사용 통계 (PHP 7.0)
 * - 집계 기준: hotel_assign.stDate
 * - 지역 라벨: product_hotel.p_types / p_typem → code_base(lvcode1~5, comment)로 해석
 * - Excel 내보내기: PHPExcel 자동 감지(없으면 CSV)
 * - 차트: 상위 10, 가로 스크롤, 라벨 -45°
 * - 테이블 헤더 클릭 정렬(js-sortable)
 * - 월별 통계 안에 "월별 지역 분포" 차트/표
 * - ★추가: 호텔 '지역별' 통계(전체 기간 합계) 탭/엑셀
 * - ★추가: 지역코드 정규화(끝 '00' 단계적 제거)로 GROUP BY
 * - ★추가: 주간별(ISO Week, 월요일 시작) 탭/엑셀
 * ============================================================ */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (isset($_GET['action']) && $_GET['action']=='export') ob_start();
include "include/header.php"; // $dbConn: mysqli
if (isset($_GET['action']) && $_GET['action']=='export') ob_end_clean();

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

/* --------------------------- 공통 헬퍼 ---------------------------- */
function fetch_all_assoc($res){
    if (!$res) return [];
    if (method_exists($res, 'fetch_all')) return $res->fetch_all(MYSQLI_ASSOC);
    $out=[]; while($row=$res->fetch_assoc()){ $out[]=$row; } return $out;
}
function _ym_first($ym){ return $ym ? $ym.'-01' : null; }
function _ym_last($ym){
    if(!$ym) return null;
    $dt=DateTime::createFromFormat('Y-m-d',$ym.'-01');
    $dt->modify('last day of this month');
    return $dt->format('Y-m-d');
}

/* --------------------------- PHPExcel 자동감지 ---------------------------- */
function ensurePHPExcelLoaded() {
    if (class_exists('PHPExcel') && class_exists('PHPExcel_IOFactory')) return true;
    $paths = [
        __DIR__."/vendor/phpoffice/phpexcel/Classes/PHPExcel.php",
        __DIR__."/admin/lib/PHPExcel/Classes/PHPExcel.php",
        $_SERVER['DOCUMENT_ROOT']."/admin/lib/PHPExcel/PHPExcel.php",
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            $io=str_replace('PHPExcel.php','PHPExcel/IOFactory.php',$p);
            if (file_exists($io)) require_once $io;
            if (class_exists('PHPExcel') && class_exists('PHPExcel_IOFactory')) {
                error_log("[PHPExcel] Loaded from: $p");
                return true;
            }
        }
    }
    if (ini_get('display_errors')) {
        echo "<div style='background:#fee;border:1px solid #f99;padding:8px;margin:6px;color:#900;font-size:12px;'>
        PHPExcel 라이브러리를 찾을 수 없습니다. CSV로 대체됩니다.</div>";
    }
    return false;
}
$hasXlsx = ensurePHPExcelLoaded();

/* --------------------------- code_base 해석 ---------------------------- */
function codebase_name(mysqli $db,$code){
    static $cache=[]; $code=trim((string)$code);
    if($code==='') return '';
    if(isset($cache[$code])) return $cache[$code];

    $lv1=substr($code,0,3); $rest=substr($code,3); $pairs=[];
    for($i=0;$i<strlen($rest);$i+=2) $pairs[]=substr($rest,$i,2);

    $lv2=$pairs[0]??'00'; $lv3=$pairs[1]??'00'; $lv4=$pairs[2]??''; $lv5=$pairs[3]??'';
    $tries=[
        [$lv1,$lv2,$lv3,$lv4,$lv5],
        [$lv1,$lv2,$lv3,$lv4,'00'],
        [$lv1,$lv2,$lv3,'00',''],
        [$lv1,$lv2,'00','00',''],
        [$lv1,'00','00','',''],
    ];

    foreach($tries as $t){
        list($a,$b,$c,$d,$e)=$t;
        $sql="
            SELECT comment FROM code_base
            WHERE TRIM(lvcode1)='{$db->real_escape_string($a)}'
              AND TRIM(lvcode2)='{$db->real_escape_string($b)}'
              AND TRIM(lvcode3)='{$db->real_escape_string($c)}'
              AND TRIM(lvcode4)='{$db->real_escape_string($d)}'
              AND TRIM(lvcode5)='{$db->real_escape_string($e)}'
            LIMIT 1";
        if($rs=$db->query($sql)){
            if($row=$rs->fetch_assoc()){
                return $cache[$code]=$row['comment'];
            }
        }
    }
    return $cache[$code]=$code; // 못찾으면 코드 그대로
}
function fmtRegionLabel_from_codebase($types,$typem,mysqli $db){
    $mid=$typem?codebase_name($db,$typem):'';
    return $mid?:'미지정';
}
function table_columns(mysqli $db,$table){
    $cols=[];
    $safe=preg_replace('/[^a-zA-Z0-9_]/','',$table);
    if(!$safe) return $cols;
    $rs=$db->query("SHOW COLUMNS FROM `{$safe}`");
    if(!$rs) return $cols;
    while($r=$rs->fetch_assoc()){
        $cols[strtolower($r['Field'])]=true;
    }
    return $cols;
}
function detect_amount_sql_expr(mysqli $db){
    $cols=table_columns($db,'hotel_assign');
    if(!$cols) return ['expr'=>'0','label'=>'없음'];

    $rowTotalCandidates=[
        'tot_price','total_price','sum_price','amount','amt','pay_amt',
        'pay_amount','total_amt','price_total','final_price','sale_price',
        'sprice','price_sum'
    ];
    foreach($rowTotalCandidates as $c){
        if(isset($cols[$c])) return ['expr'=>"COALESCE(ha.`{$c}`,0)",'label'=>$c];
    }

    $unitCandidates=['price','unit_price','room_price','sprice','base_price'];
    foreach($unitCandidates as $c){
        if(isset($cols[$c]) && isset($cols['pcnt'])){
            return ['expr'=>"COALESCE(ha.`{$c}`,0) * COALESCE(ha.`pcnt`,0)",'label'=>$c.'*pcnt'];
        }
    }
    foreach($unitCandidates as $c){
        if(isset($cols[$c])) return ['expr'=>"COALESCE(ha.`{$c}`,0)",'label'=>$c];
    }

    return ['expr'=>'0','label'=>'없음'];
}
function money_num($v){
    if($v===null || $v==='') return 0;
    return (float)$v;
}

/* --------------------------- 필터 ---------------------------- */
$todayY=date('Y');
$from_ym=$_GET['from_ym']??($todayY.'-01');
$to_ym=$_GET['to_ym']??date('Y-m');
$pcode=$_GET['pcode']??'';

$from_date=_ym_first($from_ym)?:($todayY.'-01-01');
$to_date=_ym_last($to_ym)?:($todayY.'-12-31');

$esc_from=$dbConn->real_escape_string($from_date);
$esc_to=$dbConn->real_escape_string($to_date);

$where="WHERE ha.stDate BETWEEN '$esc_from' AND '$esc_to'";
if($pcode) $where.=" AND ha.p_code='".$dbConn->real_escape_string($pcode)."'" ;

/* --------------------------- p_types 정규화 ---------------------------- */
$SQL_PTYPE_NORM = "
  CASE
    WHEN COALESCE(ph.p_types,'') = '' THEN ''
    WHEN RIGHT(ph.p_types,2) <> '00' THEN ph.p_types
    WHEN SUBSTRING(ph.p_types,6,2) <> '00' THEN SUBSTRING(ph.p_types,1,7)
    WHEN SUBSTRING(ph.p_types,4,2) <> '00' THEN SUBSTRING(ph.p_types,1,5)
    ELSE SUBSTRING(ph.p_types,1,3)
  END
";

/* --------------------------- 주간(ISO Week) ---------------------------- */
$SQL_WEEK_ISO = "YEARWEEK(ha.stDate, 3)"; // ISO 주차(월요일 시작): 예 202601

/* --------------------------- 총금액 집계식 자동 감지 ---------------------------- */
$amountMeta = detect_amount_sql_expr($dbConn);
$SQL_AMOUNT_EXPR = $amountMeta['expr'];
$amountSourceLabel = $amountMeta['label'];

/* ============================================================
   엑셀 다운로드
============================================================ */
if(isset($_GET['action']) && $_GET['action']==='export'){
    $type=$_GET['type']??'monthly';

    switch($type){
        case 'yearly':
            $sql="SELECT DATE_FORMAT(ha.stDate,'%Y') AS 연도,SUM(ha.pcnt) AS 객실수
                  FROM hotel_assign ha $where
                  GROUP BY 연도 ORDER BY 연도 ASC";
            $headers=['연도','객실수'];
            $filename="hotel_yearly_{$from_ym}_{$to_ym}.xlsx";
            break;

        case 'weekly':
            $sql="SELECT CONCAT(SUBSTRING($SQL_WEEK_ISO,1,4),'-W',LPAD(SUBSTRING($SQL_WEEK_ISO,5,2),2,'0')) AS 주,
                         SUM(ha.pcnt) AS 객실수
                  FROM hotel_assign ha
                  $where
                  GROUP BY 주
                  ORDER BY 주 ASC";
            $headers=['주','객실수'];
            $filename="hotel_weekly_{$from_ym}_{$to_ym}.xlsx";
            break;

        case 'weekly_region':
            $sql="SELECT CONCAT(SUBSTRING($SQL_WEEK_ISO,1,4),'-W',LPAD(SUBSTRING($SQL_WEEK_ISO,5,2),2,'0')) AS 주,
                         ($SQL_PTYPE_NORM) AS 지역코드,
                         SUM(ha.pcnt) AS 객실수
                  FROM hotel_assign ha
                  JOIN product_hotel ph ON ph.h_code=ha.hotel_code
                  $where
                  GROUP BY 주, 지역코드
                  ORDER BY 주 ASC, 객실수 DESC";
            $headers=['주','지역','지역코드','객실수'];
            $filename="hotel_weekly_region_{$from_ym}_{$to_ym}.xlsx";
            break;

        case 'hotel':
            $sql="SELECT ha.hotel_code AS 호텔코드,MAX(ph.h_name) AS 호텔명,SUM(ha.pcnt) AS 객실수
                  FROM hotel_assign ha
                  JOIN product_hotel ph ON ph.h_code=ha.hotel_code
                  $where
                  GROUP BY ha.hotel_code
                  ORDER BY 객실수 DESC, 호텔명 ASC";
            $headers=['호텔코드','호텔명','객실수'];
            $filename="hotel_by_hotel_{$from_ym}_{$to_ym}.xlsx";
            break;

        case 'hotel_mr':
            $sql="SELECT DATE_FORMAT(ha.stDate,'%Y-%m') AS 월,
                         ($SQL_PTYPE_NORM) AS 지역코드,
                         COALESCE(ph.h_name,ha.hotel_code) AS 호텔명,
                         ha.hotel_code AS 호텔코드,
                         SUM(ha.pcnt) AS 객실수
                  FROM hotel_assign ha
                  JOIN product_hotel ph ON ph.h_code=ha.hotel_code
                  $where
                  GROUP BY 월, 지역코드, 호텔명, 호텔코드
                  ORDER BY 월 ASC, 객실수 DESC";
            $headers=['월','지역','지역코드','호텔명','호텔코드','객실수'];
            $filename="hotel_month_region_{$from_ym}_{$to_ym}.xlsx";
            break;

        case 'monthly_region':
            $sql="SELECT DATE_FORMAT(ha.stDate,'%Y-%m') AS 월,
                         ($SQL_PTYPE_NORM) AS 지역코드,
                         SUM(ha.pcnt) AS 객실수
                  FROM hotel_assign ha
                  JOIN product_hotel ph ON ph.h_code=ha.hotel_code
                  $where
                  GROUP BY 월, 지역코드
                  ORDER BY 월 ASC, 객실수 DESC";
            $headers=['월','지역','지역코드','객실수'];
            $filename="hotel_monthly_region_{$from_ym}_{$to_ym}.xlsx";
            break;

        case 'region':
            $sql="SELECT ($SQL_PTYPE_NORM) AS 지역코드,
                         SUM(ha.pcnt) AS 객실수
                  FROM hotel_assign ha
                  JOIN product_hotel ph ON ph.h_code=ha.hotel_code
                  $where
                  GROUP BY 지역코드
                  ORDER BY 객실수 DESC";
            $headers=['지역','지역코드','객실수'];
            $filename="hotel_region_total_{$from_ym}_{$to_ym}.xlsx";
            break;

        default: // monthly
            $sql="SELECT DATE_FORMAT(ha.stDate,'%Y-%m') AS 월,SUM(ha.pcnt) AS 객실수
                  FROM hotel_assign ha
                  $where
                  GROUP BY 월 ORDER BY 월 ASC";
            $headers=['월','객실수'];
            $filename="hotel_monthly_{$from_ym}_{$to_ym}.xlsx";
    }

    $res=$dbConn->query($sql);
    if(!$res){
        while(ob_get_level()) ob_end_clean();
        header('Content-Type:text/plain; charset=UTF-8');
        echo $dbConn->error;
        exit;
    }

    $rows=[];
    while($a=$res->fetch_assoc()){
        if($type==='hotel_mr'){
            $label = codebase_name($dbConn,$a['지역코드']);
            $rows[] = [$a['월'], $label, $a['지역코드'], $a['호텔명'], $a['호텔코드'], (float)$a['객실수']];
            continue;
        }
        if($type==='monthly_region'){
            $label = codebase_name($dbConn,$a['지역코드']);
            $rows[] = [$a['월'], $label, $a['지역코드'], (float)$a['객실수']];
            continue;
        }
        if($type==='region'){
            $label = codebase_name($dbConn,$a['지역코드']);
            $rows[] = [$label, $a['지역코드'], (float)$a['객실수']];
            continue;
        }
        if($type==='weekly_region'){
            $label = codebase_name($dbConn,$a['지역코드']);
            $rows[] = [$a['주'], $label, $a['지역코드'], (float)$a['객실수']];
            continue;
        }
        $rows[]=array_values($a);
    }

    while(ob_get_level()) ob_end_clean();

    if($hasXlsx){
        $obj=new PHPExcel(); $sh=$obj->getActiveSheet();
        foreach($headers as $i=>$h) $sh->setCellValueByColumnAndRow($i,1,$h);
        $rnum=2;
        foreach($rows as $row){
            $c=0;
            foreach($row as $v){
                $sh->setCellValueByColumnAndRow($c++,$rnum,$v);
            }
            $rnum++;
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $w=PHPExcel_IOFactory::createWriter($obj,'Excel2007'); $w->save('php://output');
    }else{
        header('Content-Type:text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.preg_replace('/\.xlsx$/','.csv',$filename).'"');
        $fp=fopen('php://output','w'); fprintf($fp,chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($fp,$headers);
        foreach($rows as $r) fputcsv($fp,$r);
        fclose($fp);
    }
    exit;
}

/* ============================================================
   화면 데이터
============================================================ */

/* 월별(합계) */
$q_monthly="SELECT DATE_FORMAT(ha.stDate,'%Y-%m') AS ym, SUM(ha.pcnt) AS rooms
            FROM hotel_assign ha
            $where
            GROUP BY ym ORDER BY ym ASC";
$monthly=fetch_all_assoc($dbConn->query($q_monthly));

/* ★주간(합계) */
$q_weekly="SELECT CONCAT(SUBSTRING($SQL_WEEK_ISO,1,4),'-W',LPAD(SUBSTRING($SQL_WEEK_ISO,5,2),2,'0')) AS wk,
                  SUM(ha.pcnt) AS rooms
           FROM hotel_assign ha
           $where
           GROUP BY wk
           ORDER BY wk ASC";
$weekly=fetch_all_assoc($dbConn->query($q_weekly));

/* 월별·지역별 합계 (정규화 코드 사용) */
$q_month_region="
  SELECT DATE_FORMAT(ha.stDate,'%Y-%m') AS ym,
         ($SQL_PTYPE_NORM) AS p_types_norm,
         SUM(ha.pcnt) AS rooms
  FROM hotel_assign ha
  JOIN product_hotel ph ON ph.h_code=ha.hotel_code
  $where
  GROUP BY ym, p_types_norm
  ORDER BY ym ASC, rooms DESC";
$month_region_raw=fetch_all_assoc($dbConn->query($q_month_region));

/* 연도별 */
$q_yearly="SELECT DATE_FORMAT(ha.stDate,'%Y') AS yy, SUM(ha.pcnt) AS rooms
           FROM hotel_assign ha $where
           GROUP BY yy ORDER BY yy ASC";
$yearly=fetch_all_assoc($dbConn->query($q_yearly));

/* 호텔별 */
$q_hotel="SELECT ha.hotel_code, MAX(ph.h_name) AS hotel_name, SUM(ha.pcnt) AS rooms
          FROM hotel_assign ha
          JOIN product_hotel ph ON ph.h_code=ha.hotel_code
          $where
          GROUP BY ha.hotel_code
          ORDER BY rooms DESC, hotel_name ASC";
$hotel=fetch_all_assoc($dbConn->query($q_hotel));

/* 월별·지역별·호텔별 (정규화 코드 사용) */
$q_hotel_mr="
  SELECT DATE_FORMAT(ha.stDate,'%Y-%m') AS ym,
         ($SQL_PTYPE_NORM) AS p_types_norm,
         COALESCE(ph.h_name,ha.hotel_code) AS hotel_name,
         ha.hotel_code,
         SUM(ha.pcnt) AS rooms
  FROM hotel_assign ha
  JOIN product_hotel ph ON ph.h_code=ha.hotel_code
  $where
  GROUP BY ym, p_types_norm, ha.hotel_code
  ORDER BY ym ASC, rooms DESC";
$hotel_mr_raw=fetch_all_assoc($dbConn->query($q_hotel_mr));

/* ★지역별(전체 기간 합계) - 정규화 코드 사용 */
$q_region_all="
  SELECT ($SQL_PTYPE_NORM) AS p_types_norm,
         SUM(ha.pcnt) AS rooms
  FROM hotel_assign ha
  JOIN product_hotel ph ON ph.h_code=ha.hotel_code
  $where
  GROUP BY p_types_norm
  ORDER BY rooms DESC";
$region_all_raw=fetch_all_assoc($dbConn->query($q_region_all));

/* 지역 상위 10(월별·지역·호텔에서 누계) */
$regionAgg=[];
foreach($hotel_mr_raw as $r){
    $code = $r['p_types_norm'] ?? '';
    $label=fmtRegionLabel_from_codebase('', $code, $dbConn);
    $regionAgg[$label]=($regionAgg[$label]??0)+(int)$r['rooms'];
}
arsort($regionAgg);
$regionTopLabels=array_slice(array_keys($regionAgg),0,10);
$regionTopValues=array_slice(array_values($regionAgg),0,10);

/* 화면 테이블용 라벨 치환 + 월별 지역 맵 구성 */
$hotel_mr=[];
$monthRegionMap=[];
foreach($hotel_mr_raw as $r){
    $code = $r['p_types_norm'] ?? '';
    $lab  = fmtRegionLabel_from_codebase('', $code, $dbConn);
    $r['region_label']=$lab;
    $r['region_code']=$code;
    $hotel_mr[]=$r;
}
foreach($month_region_raw as $r){
    $ym   = $r['ym'];
    $code = $r['p_types_norm'] ?? '';
    $lab  = fmtRegionLabel_from_codebase('', $code, $dbConn);
    if (!isset($monthRegionMap[$ym])) $monthRegionMap[$ym]=[];
    $monthRegionMap[$ym][$lab]=($monthRegionMap[$ym][$lab]??0)+(float)$r['rooms'];
}

/* ★지역별(전체 기간 합계) 라벨 치환/정렬 (코드 함께 보관) */
$region_all=[]; $regionAllLabels=[]; $regionAllValues=[];
foreach($region_all_raw as $r){
    $code = trim($r['p_types_norm'] ?? '');
    $lab  = fmtRegionLabel_from_codebase('', $code, $dbConn);
    $region_all[] = ['region'=>$lab, 'code'=>$code, 'rooms'=>(float)$r['rooms']];
}
usort($region_all, function($a,$b){ return $b['rooms']<=>$a['rooms']; });
foreach($region_all as $x){ $regionAllLabels[]=$x['region']; $regionAllValues[]=$x['rooms']; }

/* JS 직렬화용: 월별 → labels/vals 배열 */
$monthRegionJS=[];
foreach($monthRegionMap as $ym=>$pairs){
    arsort($pairs);
    $monthRegionJS[$ym]=[
        'labels'=>array_keys($pairs),
        'vals'=>array_values($pairs),
    ];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>호텔 사용 통계</title>
<style>
.chart-wrap{width:100%;overflow-x:auto;}
.chart-svg{height:360px;overflow:visible;background:#fafafa;border:1px solid #eee;}
.chart-title{font-weight:700;margin:8px 0;}
.table-condensed th,.table-condensed td{vertical-align:middle!important;}
/* 정렬용 */
.js-sortable th{cursor:pointer;position:relative;user-select:none;}
.js-sortable th .sort-caret{position:absolute;right:8px;top:50%;transform:translateY(-50%);font-size:10px;opacity:.35;}
.js-sortable th[aria-sort="ascending"] .sort-caret::after{content:"▲";opacity:.9;}
.js-sortable th[aria-sort="descending"] .sort-caret::after{content:"▼";opacity:.9;}
.small{font-size:12px;color:#666;}
</style>
</head>
<body>
<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">통계</a></li>
        <li>호텔 사용 통계</li>
      </ul>
    </div>

    <!-- 필터 -->
    <form class="form-inline" method="get" action="<?=htmlspecialchars($_SERVER['PHP_SELF'])?>">
      <div class="panel panel-default">
        <div class="panel-body">
          <div class="form-group">
            <label>기간</label>
            <input type="month" class="form-control input-sm" name="from_ym" value="<?=$from_ym?>"> ~
            <input type="month" class="form-control input-sm" name="to_ym" value="<?=$to_ym?>">
          </div>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-left:10px;">조회</button>
        </div>
      </div>
    </form>

    <ul class="nav nav-tabs">
      <li class="active"><a data-toggle="tab" href="#tabMonthly">월별</a></li>
      <li><a data-toggle="tab" href="#tabWeekly">주간별</a></li>
      <li><a data-toggle="tab" href="#tabYearly">연도별</a></li>
      <li><a data-toggle="tab" href="#tabHotel">호텔별</a></li>
      <li><a data-toggle="tab" href="#tabRegion">지역별</a></li>
      <li><a data-toggle="tab" href="#tabHotelMR">월별·지역별</a></li>
    </ul>

    <div class="tab-content" style="padding-top:12px;">
      <!-- 월별 -->
      <div id="tabMonthly" class="tab-pane fade in active">
        <div class="text-right" style="margin-bottom:6px;">
          <a class="btn btn-success btn-xs"
             href="<?=$_SERVER['PHP_SELF']?>?<?=http_build_query(['action'=>'export','type'=>'monthly','from_ym'=>$from_ym,'to_ym'=>$to_ym])?>">엑셀(월별 합계)</a>
          <a class="btn btn-success btn-xs"
             href="<?=$_SERVER['PHP_SELF']?>?<?=http_build_query(['action'=>'export','type'=>'monthly_region','from_ym'=>$from_ym,'to_ym'=>$to_ym])?>">엑셀(월별·지역)</a>
        </div>

        <div class="chart-wrap">
          <div class="chart-title">월별 객실수</div>
          <svg id="chartM" class="chart-svg"></svg>
        </div>

        <table class="table table-bordered table-condensed js-sortable">
          <thead><tr><th data-sort-type="date-ym">월</th><th data-sort-type="number">객실수</th></tr></thead>
          <tbody>
          <?php $sum=0.0; foreach($monthly as $r): $sum+= (float)$r['rooms']; ?>
            <tr><td><?=htmlspecialchars($r['ym'])?></td><td class="text-right"><?=(float)$r['rooms']?></td></tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th>합계</th><th class="text-right"><?=(float)$sum?></th></tr></tfoot>
        </table>

        <!-- ▼ 월별 지역 분포 -->
        <div class="panel panel-default" style="margin-top:14px;">
          <div class="panel-heading">
            월별 지역 분포
            <span class="small">· 드롭다운으로 월을 선택하면 해당 월의 지역별 객실 분포가 표시됩니다.</span>
          </div>
          <div class="panel-body">
            <div class="form-inline" style="margin-bottom:8px;">
              <label class="small">월 선택</label>
              <select id="selMonth" class="form-control input-sm" style="min-width:140px;">
                <?php foreach(array_column($monthly,'ym') as $ym): ?>
                  <option value="<?=$ym?>"><?=$ym?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="chart-wrap">
              <div class="chart-title" id="chartMRTitle">지역별 객실수</div>
              <svg id="chartMonthlyRegion" class="chart-svg"></svg>
            </div>
            <div class="table-responsive" style="margin-top:10px;">
              <table id="tblMonthlyRegion" class="table table-bordered table-condensed js-sortable">
                <thead><tr><th data-sort-type="text">지역</th><th data-sort-type="number">객실수</th></tr></thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- ▲ 월별 지역 분포 끝 -->
      </div>

      <!-- ★주간별 -->
      <div id="tabWeekly" class="tab-pane fade">
        <div class="text-right" style="margin-bottom:6px;">
          <a class="btn btn-success btn-xs"
             href="<?=$_SERVER['PHP_SELF']?>?<?=http_build_query(['action'=>'export','type'=>'weekly','from_ym'=>$from_ym,'to_ym'=>$to_ym])?>">엑셀(주간 합계)</a>
          <a class="btn btn-success btn-xs"
             href="<?=$_SERVER['PHP_SELF']?>?<?=http_build_query(['action'=>'export','type'=>'weekly_region','from_ym'=>$from_ym,'to_ym'=>$to_ym])?>">엑셀(주간·지역)</a>
        </div>

        <div class="chart-wrap">
          <div class="chart-title">주간별 객실수 (ISO Week)</div>
          <svg id="chartW" class="chart-svg"></svg>
        </div>

        <table class="table table-bordered table-condensed js-sortable">
          <thead><tr><th data-sort-type="text">주</th><th data-sort-type="number">객실수</th></tr></thead>
          <tbody>
          <?php $sumw=0.0; foreach($weekly as $r): $sumw += (float)$r['rooms']; ?>
            <tr><td><?=htmlspecialchars($r['wk'])?></td><td class="text-right"><?=(float)$r['rooms']?></td></tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th>합계</th><th class="text-right"><?=(float)$sumw?></th></tr></tfoot>
        </table>
      </div>

      <!-- 연도별 -->
      <div id="tabYearly" class="tab-pane fade">
        <div class="text-right" style="margin-bottom:6px;">
          <a class="btn btn-success btn-xs"
             href="<?=$_SERVER['PHP_SELF']?>?<?=http_build_query(['action'=>'export','type'=>'yearly','from_ym'=>$from_ym,'to_ym'=>$to_ym])?>">엑셀 다운로드</a>
        </div>
        <div class="chart-wrap">
          <div class="chart-title">연도별 객실수</div>
          <svg id="chartY" class="chart-svg"></svg>
        </div>
        <table class="table table-bordered table-condensed js-sortable">
          <thead><tr><th data-sort-type="text">연도</th><th data-sort-type="number">객실수</th></tr></thead>
          <tbody>
          <?php $sumy=0.0; foreach($yearly as $r): $sumy+=(float)$r['rooms']; ?>
            <tr><td><?=htmlspecialchars($r['yy'])?></td><td class="text-right"><?=(float)$r['rooms']?></td></tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th>합계</th><th class="text-right"><?=(float)$sumy?></th></tr></tfoot>
        </table>
      </div>

      <!-- 호텔별 -->
      <div id="tabHotel" class="tab-pane fade">
        <div class="text-right" style="margin-bottom:6px;">
          <a class="btn btn-success btn-xs"
             href="<?=$_SERVER['PHP_SELF']?>?<?=http_build_query(['action'=>'export','type'=>'hotel','from_ym'=>$from_ym,'to_ym'=>$to_ym])?>">엑셀 다운로드</a>
        </div>
        <div class="chart-wrap">
          <div class="chart-title">호텔별 객실수 (상위 10)</div>
          <svg id="chartH" class="chart-svg"></svg>
        </div>
        <table class="table table-bordered table-condensed js-sortable">
          <thead><tr><th data-sort-type="text">호텔코드</th><th data-sort-type="text">호텔명</th><th data-sort-type="number">객실수</th></tr></thead>
          <tbody>
          <?php $sumh=0.0; foreach($hotel as $r): $sumh+=(float)$r['rooms']; ?>
            <tr>
              <td><?=htmlspecialchars($r['hotel_code'])?></td>
              <td><?=htmlspecialchars($r['hotel_name'])?></td>
              <td class="text-right"><?=(float)$r['rooms']?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th colspan="2">합계</th><th class="text-right"><?=(float)$sumh?></th></tr></tfoot>
        </table>
      </div>

      <!-- 지역별(전체 기간 합계) -->
      <div id="tabRegion" class="tab-pane fade">
        <div class="text-right" style="margin-bottom:6px;">
          <a class="btn btn-success btn-xs"
             href="<?=$_SERVER['PHP_SELF']?>?<?=http_build_query(['action'=>'export','type'=>'region','from_ym'=>$from_ym,'to_ym'=>$to_ym])?>">엑셀(지역별)</a>
        </div>
        <div class="chart-wrap">
          <div class="chart-title">지역별 총 객실수 (상위 10)</div>
          <svg id="chartRegionAll" class="chart-svg"></svg>
        </div>
        <table class="table table-bordered table-condensed js-sortable">
          <thead><tr><th data-sort-type="text">지역 (코드)</th><th data-sort-type="number">객실수</th></tr></thead>
          <tbody>
          <?php $sumr=0.0; foreach($region_all as $r): $sumr += (float)$r['rooms']; ?>
            <tr>
              <td>
                <?=htmlspecialchars($r['region'])?>
                <?php if(!empty($r['code'])): ?>
                  <span class="small" style="color:#888;">(<?=htmlspecialchars($r['code'])?>)</span>
                <?php endif; ?>
              </td>
              <td class="text-right"><?=(float)$r['rooms']?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th>합계</th><th class="text-right"><?=(float)$sumr?></th></tr></tfoot>
        </table>
      </div>

      <!-- 월별·지역별 -->
      <div id="tabHotelMR" class="tab-pane fade">
        <div class="text-right" style="margin-bottom:6px;">
          <a class="btn btn-success btn-xs"
             href="<?=$_SERVER['PHP_SELF']?>?<?=http_build_query(['action'=>'export','type'=>'hotel_mr','from_ym'=>$from_ym,'to_ym'=>$to_ym])?>">엑셀 다운로드</a>
        </div>

        <div class="chart-wrap">
          <div class="chart-title">지역별 총 객실수 (상위 10)</div>
          <svg id="chartRegionTop" class="chart-svg"></svg>
        </div>

        <div class="panel panel-default" style="margin-top:12px;">
          <div class="panel-heading">월별 · 지역별 · 호텔별 객실수</div>
          <div class="panel-body" style="padding:0;">
            <table class="table table-bordered table-condensed js-sortable" style="margin:0;">
              <thead><tr>
                <th data-sort-type="date-ym">월</th>
                <th data-sort-type="text">지역</th>
                <th data-sort-type="text">호텔명</th>
                <th data-sort-type="text">호텔코드</th>
                <th data-sort-type="number">객실수</th>
              </tr></thead>
              <tbody>
      <?php $sum_mr=0.0; foreach($hotel_mr as $r): $sum_mr+=(float)$r['rooms']; ?>
                <tr>
                  <td><?=htmlspecialchars($r['ym'])?></td>
                  <td>
                    <?=htmlspecialchars($r['region_label'])?>
                    <?php if(!empty($r['region_code'])): ?>
                      <span class="small" style="color:#888;">(<?=htmlspecialchars($r['region_code'])?>)</span>
                    <?php endif; ?>
                  </td>
                  <td><?=htmlspecialchars($r['hotel_name'])?></td>
                  <td><?=htmlspecialchars($r['hotel_code'])?></td>
          <td class="text-right"><?=(float)$r['rooms']?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
      <tfoot><tr><th colspan="4" class="text-right">합계</th><th class="text-right"><?=(float)$sum_mr?></th></tr></tfoot>
            </table>
          </div>
        </div>
      </div>
    </div><!-- /tab-content -->
  </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
// ===== 막대 차트 (가로 스크롤/상위 10) =====
function drawBarWide(id, labels, vals, barW=46, gap=22){
  var svg=document.getElementById(id); if(!svg) return;
  var n=labels.length, pL=50,pR=20,pT=20,pB=70, H=360;
  var W=pL+pR+n*(barW+gap);
  svg.setAttribute('width', W); svg.setAttribute('height', H);
  while(svg.firstChild) svg.removeChild(svg.firstChild);

  var maxV=1; for(var i=0;i<vals.length;i++){ var v=+vals['i']||0; if(v>maxV) maxV=v; }
  var ch=H-pT-pB;

  var axis=document.createElementNS('http://www.w3.org/2000/svg','line');
  axis.setAttribute('x1',pL); axis.setAttribute('x2',pL);
  axis.setAttribute('y1',pT); axis.setAttribute('y2',pT+ch);
  axis.setAttribute('stroke','#ddd'); svg.appendChild(axis);

  for (var i=0;i<labels.length;i++){
    var v=+vals['i']||0, h=(v/maxV)*ch, x=pL+i*(barW+gap), y0=pT+ch-h;

    var r=document.createElementNS('http://www.w3.org/2000/svg','rect');
    r.setAttribute('x',x); r.setAttribute('y',y0);
    r.setAttribute('width',barW); r.setAttribute('height',h);
    r.setAttribute('fill','#5bbdfa'); svg.appendChild(r);

    var t=document.createElementNS('http://www.w3.org/2000/svg','text');
    t.setAttribute('x',x+barW/2); t.setAttribute('y',y0-4);
    t.setAttribute('text-anchor','middle'); t.setAttribute('font-size','10');
    t.textContent=v; svg.appendChild(t);

    var tx=document.createElementNS('http://www.w3.org/2000/svg','text');
    tx.setAttribute('x',x+barW/2); tx.setAttribute('y',pT+ch+14);
    tx.setAttribute('transform','rotate(-45 '+(x+barW/2)+' '+(pT+ch+14)+')');
    tx.setAttribute('text-anchor','end'); tx.setAttribute('font-size','10');
    tx.textContent=labels['i']; svg.appendChild(tx);
  }
}

// ===== PHP → JS 데이터 =====
var M_labels = <?=json_encode(array_column($monthly,'ym'))?>;
var M_vals   = <?=json_encode(array_map('floatval',array_column($monthly,'rooms')))?>;

var W_labels = <?=json_encode(array_column($weekly,'wk'))?>;
var W_vals   = <?=json_encode(array_map('floatval',array_column($weekly,'rooms')))?>;

var Y_labels = <?=json_encode(array_column($yearly,'yy'))?>;
var Y_vals   = <?=json_encode(array_map('floatval',array_column($yearly,'rooms')))?>;

// 호텔별 상위 10
<?php
$hotelTop = array_slice($hotel, 0, 10);
$hotelTopLabels = [];
foreach ($hotelTop as $rr){
    $nm = trim($rr['hotel_name'] ? $rr['hotel_name'] : $rr['hotel_code']);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        $hotelTopLabels[] = mb_substr($nm,0,20) . (mb_strlen($nm)>20?'…':'');
    } else {
        $hotelTopLabels[] = substr($nm,0,20) . (strlen($nm)>20?'…':'');
    }
}
$hotelTopVals = [];
foreach ($hotelTop as $rr){ $hotelTopVals[] = (float)$rr['rooms']; }
?>
var H_labels = <?=json_encode($hotelTopLabels)?>;
var H_vals   = <?=json_encode($hotelTopVals)?>;

// 지역 상위 10 (월별·지역별·호텔별에서 누적)
var R_labels = <?=json_encode($regionTopLabels)?>;
var R_vals   = <?=json_encode($regionTopValues)?>;

// 월별 지역 분포 데이터 (월 → {labels:[], vals:[]})
var MR = <?=json_encode($monthRegionJS)?>;

// 지역별(전체 기간 합계) 상위 10
var REG_labels = <?=json_encode(array_slice($regionAllLabels,0,10))?>;
var REG_vals   = <?=json_encode(array_slice($regionAllValues,0,10))?>;

drawBarWide('chartM', M_labels, M_vals);
drawBarWide('chartW', W_labels, W_vals);
drawBarWide('chartY', Y_labels, Y_vals);
drawBarWide('chartH', H_labels, H_vals);
drawBarWide('chartRegionTop', R_labels, R_vals);
drawBarWide('chartRegionAll', REG_labels, REG_vals);

// ===== 정렬 유틸 =====
function parseByType(text, type) {
  if (text == null) return null;
  var t = String(text).trim();
  switch (type) {
    case 'number':
      t = t.replace(/,/g, '');
      var n = parseFloat(t);
      return isNaN(n) ? null : n;
    case 'date-ym': // YYYY-MM
      var d = new Date(t + '-01T00:00:00');
      return isNaN(d.getTime()) ? null : d.getTime();
    case 'date':
      var d2 = new Date(t);
      return isNaN(d2.getTime()) ? null : d2.getTime();
    default:
      return t.toLowerCase();
  }
}
function makeTablesSortable() {
  var tables = document.querySelectorAll('table.js-sortable');
  Array.prototype.forEach.call(tables, function(table){
    var thead = table.tHead;
    if (!thead || !thead.rows.length) return;

    Array.prototype.forEach.call(thead.rows[0].cells, function(th){
      if (!th.querySelector('.sort-caret')) {
        var s = document.createElement('span');
        s.className = 'sort-caret';
        th.appendChild(s);
      }
      th.setAttribute('tabindex', '0');
      th.setAttribute('role', 'button');
    });

    thead.addEventListener('click', function(ev){
      var th = ev.target.closest('th'); if (!th) return; sortByTH(table, th);
    });
    thead.addEventListener('keydown', function(ev){
      if (ev.key === 'Enter' || ev.key === ' ') {
        var th = ev.target.closest('th'); if (!th) return; ev.preventDefault(); sortByTH(table, th);
      }
    });
  });

  function sortByTH(table, th){
    var ths = Array.prototype.slice.call(table.tHead.rows[0].cells);
    var colIndex = ths.indexOf(th); if (colIndex < 0) return;
    var type = th.getAttribute('data-sort-type') || 'text';

    var curr = th.getAttribute('aria-sort');
    var dir  = (curr === 'ascending') ? 'descending' : 'ascending';

    ths.forEach(function(h){ if (h !== th) h.removeAttribute('aria-sort'); });
    th.setAttribute('aria-sort', dir);

    var tbody = table.tBodies[0]; if (!tbody) return;
    var tfoot = table.tFoot;

    var rows = Array.prototype.slice.call(tbody.rows);
    rows.forEach(function(r, i){ r.__idx = i; });

    rows.sort(function(a, b){
      var A = a.cells['colIndex'] ? a.cells['colIndex'].innerText : '';
      var B = b.cells['colIndex'] ? b.cells['colIndex'].innerText : '';
      var aV = parseByType(A, type);
      var bV = parseByType(B, type);

      if (aV == null && bV == null) return a.__idx - b.__idx;
      if (aV == null) return 1;
      if (bV == null) return -1;

      var cmp = 0;
      if (type === 'number' || type.indexOf('date') === 0) {
        cmp = (aV < bV) ? -1 : (aV > bV ? 1 : 0);
      } else {
        cmp = aV.localeCompare(bV);
      }
      return (dir === 'ascending') ? cmp : -cmp;
    });

    var frag = document.createDocumentFragment();
    rows.forEach(function(r){ frag.appendChild(r); });
    tbody.appendChild(frag);

    if (tfoot) table.appendChild(tfoot);
  }
}

/* ===== 월별 지역 분포 UI 바인딩 ===== */
function renderMonthlyRegion(ym){
  var data = MR['ym'] || {labels:[], vals:[]};
  document.getElementById('chartMRTitle').textContent = '지역별 객실수 ('+ ym +')';
  drawBarWide('chartMonthlyRegion', data.labels, data.vals);

  var tb = document.querySelector('#tblMonthlyRegion tbody');
  if (!tb) return;
  while (tb.firstChild) tb.removeChild(tb.firstChild);
  for (var i=0;i<data.labels.length;i++){
    var tr=document.createElement('tr');
    var td1=document.createElement('td'); td1.textContent=data.labels['i'];
    var td2=document.createElement('td'); td2.className='text-right'; td2.textContent=(data.vals['i']||0).toLocaleString();
    tr.appendChild(td1); tr.appendChild(td2); tb.appendChild(tr);
  }
  makeTablesSortable();
}

function bindMonthlyRegion(){
  var sel = document.getElementById('selMonth');
  if (!sel) return;
  sel.addEventListener('change', function(){ renderMonthlyRegion(sel.value); });
  if (sel.value) renderMonthlyRegion(sel.value);
}

// 초기화
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function(){
    makeTablesSortable();
    bindMonthlyRegion();
  });
} else {
  makeTablesSortable();
  bindMonthlyRegion();
}
</script>
</body>
</html>
