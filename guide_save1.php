<?php
include("include/dbconn.php");
include("include/func_list.php");
include("include/purun_func.php");

/*
 * PHP 5.6 + mysql_* 버전
 * - 기존 mysqli_query / mysqli_real_escape_string 제거
 * - inc_base/dbconn.php 에서 mysql_connect/mysql_select_db 되어 있어야 함
 */

function arr($k){
    return (isset($_POST[$k]) && is_array($_POST[$k])) ? $_POST[$k] : array();
}
function val($k, $def=''){
    return isset($_POST[$k]) ? $_POST[$k] : $def;
}

// 조식
$bfDate      = arr('bfDate');
$bfStoreName = arr('bfStoreName');
$bfPerson    = arr('bfPerson');
$bfCost      = arr('bfCost');
$bfTotalCost = arr('bfTotalCost');
$bfseq       = arr('bf_seq');

// 중식
$lunchDate      = arr('lunchDate');
$lunchStoreName = arr('lunchStoreName');
$lunchPerson    = arr('lunchPerson');
$lunchCost      = arr('lunchCost');
$lunchTotalCost = arr('lunchTotalCost');
$lunchseq       = arr('lunch_seq');

// 석식
$dinnerDate      = arr('dinnerDate');
$dinnerStoreName = arr('dinnerStoreName');
$dinnerPerson    = arr('dinnerPerson');
$dinnerCost      = arr('dinnerCost');
$dinnerTotalCost = arr('dinnerTotalCost');
$dinnerseq       = arr('dinner_seq');

// 행사기간
$period      = getPeriodbyhotel(val('pcode'), val('stDate'));
$returndata  = explode("~", $period);

$grand_eCode   = val('grand_eCode');
$sub_eCode     = val('sub_eCode');
$guide_etcamt  = val('guideEtcDepAmount');

// ✅ 원본 그대로: $user_dbinfo['userid']를 쓰고 있었음(inc에서 제공된다고 가정)
$userid = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : '';

// ===== 체크(다건) + 전체정산 메모 =====
$check_no   = arr('check_no');
$bank_name  = arr('bank_name');
$used_date  = arr('used_date');
$amount     = arr('amount');
$note       = arr('note');

// 전체정산 메모(guide_setmaster.g_memo 컬럼)
$guide_memo = val('guide_memo', '');

$settle_code_p = val('settle_code');
$mode          = val('mode');

if ($mode == 'save') {

    // 정산코드 생성/유지
    if ($settle_code_p == "") {
        $mt  = microtime(true);
        $sec = floor($mt);
        $hs  = sprintf("%03d", ($mt - $sec) * 1000);
        $guide_code = "SETTLE-" . date("His", $sec) . $hs;
    } else {
        $guide_code = $settle_code_p;
    }

    // 기존 데이터 삭제(기존 로직 유지)
    mysql_query("DELETE FROM guide_meal      WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_admission WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_option    WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_etcamt    WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_shopping  WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_inputamt  WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_setmaster WHERE settle_code = '".esc($settle_code_p)."'");

    // 0. guide_setmaster
    $q = "INSERT INTO guide_setmaster (
            settle_code,
            grand_eCode,
            sub_eCode,
            stDate,
            edDate,
            guide_etcamt,
            reg_status,
            reg_user,
            g_memo,
            wdate
          ) VALUES (
            '".esc($guide_code)."',
            '".esc($grand_eCode)."',
            '".esc($sub_eCode)."',
            '".esc(isset($returndata[0])?$returndata[0]:'')."',
            '".esc(isset($returndata[1])?$returndata[1]:'')."',
            '".esc($guide_etcamt)."',
            'DONE',
            '".esc($userid)."',
            '".esc($guide_memo)."',
            NOW()
          )";
    mysql_query($q);

    // 1. guide_meal (bf/lunch/dinner)
    foreach ($bfDate as $k=>$v){
        $q = "INSERT INTO guide_meal(
                settle_code, meal_type, meal_date, meal_rest, meal_cnt, meal_price, meal_pricetotal, wdate
              ) VALUES (
                '".esc($guide_code)."','bf','".esc($v)."','".esc(isset($bfStoreName[$k])?$bfStoreName[$k]:'')."',
                '".esc(isset($bfPerson[$k])?$bfPerson[$k]:'0')."','".esc(isset($bfCost[$k])?$bfCost[$k]:'0')."',
                '".esc(isset($bfTotalCost[$k])?$bfTotalCost[$k]:'0')."', NOW()
              )";
        mysql_query($q);
    }

    foreach ($lunchDate as $k=>$v){
        $q = "INSERT INTO guide_meal(
                settle_code, meal_type, meal_date, meal_rest, meal_cnt, meal_price, meal_pricetotal, wdate
              ) VALUES (
                '".esc($guide_code)."','lunch','".esc($v)."','".esc(isset($lunchStoreName[$k])?$lunchStoreName[$k]:'')."',
                '".esc(isset($lunchPerson[$k])?$lunchPerson[$k]:'0')."','".esc(isset($lunchCost[$k])?$lunchCost[$k]:'0')."',
                '".esc(isset($lunchTotalCost[$k])?$lunchTotalCost[$k]:'0')."', NOW()
              )";
        mysql_query($q);
    }

    foreach ($dinnerDate as $k=>$v){
        $q = "INSERT INTO guide_meal(
                settle_code, meal_type, meal_date, meal_rest, meal_cnt, meal_price, meal_pricetotal, wdate
              ) VALUES (
                '".esc($guide_code)."','dinner','".esc($v)."','".esc(isset($dinnerStoreName[$k])?$dinnerStoreName[$k]:'')."',
                '".esc(isset($dinnerPerson[$k])?$dinnerPerson[$k]:'0')."','".esc(isset($dinnerCost[$k])?$dinnerCost[$k]:'0')."',
                '".esc(isset($dinnerTotalCost[$k])?$dinnerTotalCost[$k]:'0')."', NOW()
              )";
        mysql_query($q);
    }

    // 2. guide_admission
    $nameSelect    = arr('nameSelect');
    $person        = arr('person');
    $cost          = arr('cost');
    $totalAmount   = arr('totalAmount');

    foreach($nameSelect as $k=>$v){
        $q = "INSERT INTO guide_admission (
                settle_code, admission_code, e_cnt, e_price, e_pricetot, wdate
              ) VALUES (
                '".esc($guide_code)."',
                '".esc($v)."',
                '".esc(isset($person[$k])?$person[$k]:'0')."',
                '".esc(isset($cost[$k])?$cost[$k]:'0')."',
                '".esc(isset($totalAmount[$k])?$totalAmount[$k]:'0')."',
                NOW()
              )";
        mysql_query($q);
    }

    // 3. guide_option
    $optionName      = arr('optionName');
    $assignGuideLine = arr('assignGuideLine');
    $optPerson       = arr('optPerson');
    $optCost         = arr('optCost');
    $optTotalAmount  = arr('optTotalAmount');
    $optPrice        = arr('optPrice');
    $optTotalPrice   = arr('optTotalPrice');
    $optDiffAmount   = arr('optDiffAmount');
    $optProfit       = arr('optProfit');
    $optGuideProfit  = arr('optGuideProfit');

    foreach($optionName as $k=>$v){
        $q = "INSERT INTO guide_option (
                settle_code, option_code, base_set,
                o_cnt, o_price, o_pricetot, o_cprice, o_cpricetot, o_diffamt, o_cprofit, o_gprofit, wdate
              ) VALUES (
                '".esc($guide_code)."',
                '".esc($v)."',
                '".esc(isset($assignGuideLine[$k])?$assignGuideLine[$k]:'')."',
                '".esc(isset($optPerson[$k])?$optPerson[$k]:'0')."',
                '".esc(isset($optCost[$k])?$optCost[$k]:'0')."',
                '".esc(isset($optTotalAmount[$k])?$optTotalAmount[$k]:'0')."',
                '".esc(isset($optPrice[$k])?$optPrice[$k]:'0')."',
                '".esc(isset($optTotalPrice[$k])?$optTotalPrice[$k]:'0')."',
                '".esc(isset($optDiffAmount[$k])?$optDiffAmount[$k]:'0')."',
                '".esc(isset($optProfit[$k])?$optProfit[$k]:'0')."',
                '".esc(isset($optGuideProfit[$k])?$optGuideProfit[$k]:'0')."',
                NOW()
              )";
        mysql_query($q);
    }

    // 4. guide_etcamt
    $guideCarSelect   = arr('guideCarSelect');
    $guideTotalAmount = arr('guideTotalAmount');
    $guideMemoArr     = arr('guideMemo');

    foreach($guideCarSelect as $k=>$v){
        $q = "INSERT INTO guide_etcamt (
                settle_code, etc_type, etc_pricety, etc_amt, etc_memo, wdate
              ) VALUES (
                '".esc($guide_code)."',
                '".esc($v)."',
                'guide',
                '".esc(isset($guideTotalAmount[$k])?$guideTotalAmount[$k]:'0')."',
                '".esc(isset($guideMemoArr[$k])?$guideMemoArr[$k]:'')."',
                NOW()
              )";
        mysql_query($q);
    }

    $carSelect      = arr('carSelect');
    $carTotalAmount = arr('carTotalAmount');
    $carMemoArr     = arr('carMemo');

    foreach($carSelect as $k=>$v){
        $q = "INSERT INTO guide_etcamt (
                settle_code, etc_type, etc_pricety, etc_amt, etc_memo, wdate
              ) VALUES (
                '".esc($guide_code)."',
                '".esc($v)."',
                'car',
                '".esc(isset($carTotalAmount[$k])?$carTotalAmount[$k]:'0')."',
                '".esc(isset($carMemoArr[$k])?$carMemoArr[$k]:'')."',
                NOW()
              )";
        mysql_query($q);
    }

    $etcCarSelect   = arr('etcCarSelect');
    $etcTotalAmount = arr('etcTotalAmount');
    $etcMemoArr     = arr('etcMemo');

    foreach($etcCarSelect as $k=>$v){
        $q = "INSERT INTO guide_etcamt (
                settle_code, etc_type, etc_pricety, etc_amt, etc_memo, wdate
              ) VALUES (
                '".esc($guide_code)."',
                '".esc($v)."',
                'etc',
                '".esc(isset($etcTotalAmount[$k])?$etcTotalAmount[$k]:'0')."',
                '".esc(isset($etcMemoArr[$k])?$etcMemoArr[$k]:'')."',
                NOW()
              )";
        mysql_query($q);
    }

    // 5. guide_shopping
    $shoppingSelect      = arr('shoppingSelect');
    $saleTotalAmount     = arr('saleTotalAmount');
    $homeshoppingcom     = arr('homeshoppingcom');
    $companyProfit       = arr('companyProfit');
    $shoppingGuideProfit = arr('shoppingGuideProfit');

    foreach($shoppingSelect as $k=>$v){
        $q = "INSERT INTO guide_shopping (
                settle_code, shop_code, tot_amt, home_comamt, c_profit, g_profit, wdate
              ) VALUES (
                '".esc($guide_code)."',
                '".esc($v)."',
                '".esc(isset($saleTotalAmount[$k])?$saleTotalAmount[$k]:'0')."',
                '".esc(isset($homeshoppingcom[$k])?$homeshoppingcom[$k]:'0')."',
                '".esc(isset($companyProfit[$k])?$companyProfit[$k]:'0')."',
                '".esc(isset($shoppingGuideProfit[$k])?$shoppingGuideProfit[$k]:'0')."',
                NOW()
              )";
        mysql_query($q);
    }

    // 6. guide_inputamt
    $ipSelect    = arr('ipSelect');
    $g_inputamt  = arr('g_inputamt');
    $g_inputcnt  = arr('g_inputcnt');
    $g_inputmemo = arr('g_inputmemo');

    foreach($ipSelect as $k=>$v){
        $q = "INSERT INTO guide_inputamt (
                settle_code, inputamt_type, input_amt, input_cnt, input_memo, wdate
              ) VALUES (
                '".esc($guide_code)."',
                '".esc($v)."',
                '".esc(isset($g_inputamt[$k])?$g_inputamt[$k]:'0')."',
                '".esc(isset($g_inputcnt[$k])?$g_inputcnt[$k]:'0')."',
                '".esc(isset($g_inputmemo[$k])?$g_inputmemo[$k]:'')."',
                NOW()
              )";
        mysql_query($q);
    }

    // ✅ save 모드에서도 메모 업데이트(원본 유지하되 mysql_*로 교체)
    $guide_memo_u = isset($_POST['guide_memo']) ? esc($_POST['guide_memo']) : '';
    mysql_query("UPDATE guide_setmaster SET g_memo='".$guide_memo_u."' WHERE settle_code='".esc($settle_code_p)."'");

} else if ($mode == 'delete') {

    mysql_query("DELETE FROM guide_meal      WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_admission WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_option    WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_etcamt    WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_shopping  WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_inputamt  WHERE settle_code = '".esc($settle_code_p)."'");
    mysql_query("DELETE FROM guide_setmaster WHERE settle_code = '".esc($settle_code_p)."'");

} else if ($mode == 'report') {

    mysql_query("UPDATE guide_setmaster
                    SET report_date = NOW(),
                        reg_status = 'COMPLETE',
                        g_memo = '".esc($guide_memo)."'
                  WHERE settle_code = '".esc($settle_code_p)."'");

} else if ($mode == 'repcan') {

    mysql_query("UPDATE guide_setmaster
                    SET report_date='',
                        ceo_st='',
                        reg_status='',
                        finance_date='',
                        finance_st='',
                        g_memo='".esc($guide_memo)."'
                  WHERE settle_code = '".esc($settle_code_p)."'");

} else if ($mode == 'finance') {

    // 회계확인
    $guide_memo_f = isset($_POST['guide_memo']) ? esc($_POST['guide_memo']) : '';
    mysql_query("UPDATE guide_setmaster
                    SET finance_st = 'V',
                        finance_date = NOW(),
                        g_memo = '".$guide_memo_f."'
                  WHERE settle_code = '".esc($settle_code_p)."'");

    /* =======================
       체크(수표) 업데이트 & 추가
       기대 POST 필드(배열):
       - check_id[] / chk_id[]
       - check_no[], bank_name[], used_date[], amount[], note[]
       (선택) check_del[]
       ======================= */

    $check_id  = isset($_POST['check_id']) ? $_POST['check_id'] : (isset($_POST['chk_id']) ? $_POST['chk_id'] : array());
    if (!is_array($check_id)) $check_id = array();

    $check_no  = arr('check_no');
    $bank_name = arr('bank_name');
    $used_date = arr('used_date');
    $amount    = arr('amount');
    $note      = arr('note');

    $rows = max(count($check_no), count($bank_name), count($used_date), count($amount), count($note), count($check_id));

    for ($i=0; $i<$rows; $i++) {
        $id   = isset($check_id[$i]) ? (int)$check_id[$i] : 0;
        $no   = isset($check_no[$i])  ? esc(trim($check_no[$i]))  : '';
        $bank = isset($bank_name[$i]) ? esc(trim($bank_name[$i])) : '';
        $ud   = isset($used_date[$i]) ? trim($used_date[$i]) : '';
        $amtR = isset($amount[$i])    ? $amount[$i] : '0';
        $nt   = isset($note[$i])      ? esc(trim($note[$i]))      : '';

        $amtR = str_replace(array(',', '$'), '', $amtR);
        $amt  = ($amtR === '' || $amtR === '-' || $amtR === '.') ? 0 : (float)$amtR;

        // 완전 빈 행 스킵
        if ($no==='' && $bank==='' && $ud==='' && $amt==0 && $nt==='') continue;

        // 날짜 보정
        if ($ud !== '' && strpos($ud, '/') !== false) $ud = str_replace('/', '-', $ud);
        $ud_sql = ($ud !== '') ? "'".esc($ud)."'" : "NULL";

        if ($id > 0) {
            $sql = "
                UPDATE guide_set_check
                   SET check_no  = '".$no."',
                       bank_name = '".$bank."',
                       used_date = ".$ud_sql.",
                       amount    = ".$amt.",
                       note      = '".$nt."'
                 WHERE id = ".$id."
                   AND settle_code = '".esc($settle_code_p)."'
            ";
        } else {
            $sql = "
                INSERT INTO guide_set_check
                    (settle_code, check_no, bank_name, used_date, amount, note, reg_user, created_at)
                VALUES
                    ('".esc($settle_code_p)."', '".$no."', '".$bank."', ".$ud_sql.", ".$amt.", '".$nt."', '".esc($userid)."', NOW())
            ";
        }
        mysql_query($sql);
    }

    // 삭제 처리
    if (!empty($_POST['check_del']) && is_array($_POST['check_del'])) {
        $delIds = array_map('intval', $_POST['check_del']);
        $delIds = array_filter($delIds);
        if (!empty($delIds)) {
            $idlist = implode(',', $delIds);
            mysql_query("DELETE FROM guide_set_check WHERE id IN (".$idlist.") AND settle_code = '".esc($settle_code_p)."'");
        }
    }

} else if ($mode == 'ceo') {

    mysql_query("UPDATE guide_setmaster
                    SET ceo_st = 'V',
                        g_memo = '".esc($guide_memo)."'
                  WHERE settle_code = '".esc($settle_code_p)."'");
}

// 결과 반환(원본 유지)
echo "1/";
?>
