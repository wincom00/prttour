<?php
require_once 'include/dbconn2.php';


/* === 무조건 로그/표시 켜기 (개발 중에만) === */
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
ini_set('log_errors','1');
error_reporting(E_ALL);

/* mysqli를 예외로 던지게 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* 어디서든 JSON으로 죽게 하는 핸들러들 */
header('Content-Type: application/json; charset=utf-8');



try {
    $mode = isset($_POST['mode']) ? $_POST['mode'] : '';
    if ($mode !== 'save_estimate') {
        echo json_encode(['result' => 'NOOP']); exit;
    }

    // ---- helper: '' -> NULL (DATE/NULL 허용 칼럼용)
    $nullIfEmpty = function($v) {
        if (!isset($v)) return null;
        $v = trim((string)$v);
        return ($v === '') ? null : $v;
    };

    // ===== 파라미터 =====
    $estimate_no = isset($_POST['estimate_no']) ? (string)$_POST['estimate_no'] : '';
    $to_name     = isset($_POST['to_name'])     ? (string)$_POST['to_name']     : '';
    $pax         = isset($_POST['pax'])         ? (int)$_POST['pax']            : 0;
    $foc         = isset($_POST['foc'])         ? (int)$_POST['foc']            : 0;
    $total_pax   = isset($_POST['total_pax'])   ? (int)$_POST['total_pax']      : 0;
    $group_name  = isset($_POST['group_name'])  ? (string)$_POST['group_name']  : '';

    // DATE 칼럼은 '' -> NULL 로 바꿔줌
    $start_date  = $nullIfEmpty(isset($_POST['start_date']) ? $_POST['start_date'] : null);
    $end_date    = $nullIfEmpty(isset($_POST['end_date'])   ? $_POST['end_date']   : null);
    $wdate       = $nullIfEmpty(isset($_POST['wdate'])      ? $_POST['wdate']      : null);

    $profit      = isset($_POST['profit'])      ? (float)$_POST['profit']      : 0.0;
    $profit_memo = isset($_POST['profit_memo']) ? (string)$_POST['profit_memo'] : '';
    $grand_total = isset($_POST['grand_total']) ? (float)$_POST['grand_total'] : 0.0;
    $per_pax     = isset($_POST['per_pax'])     ? (float)$_POST['per_pax']     : 0.0;

    $items       = (isset($_POST['items']) && is_array($_POST['items'])) ? $_POST['items'] : [];

    $dbConn->begin_transaction();

    // ===== 마스터 저장 =====
    if (empty($_POST['id'])) {
        // INSERT
        $sql = "INSERT INTO estimate_master
                (estimate_no,to_name,pax,foc,total_pax,group_name,start_date,end_date,wdate,profit,profit_memo,grand_total,per_pax)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $dbConn->prepare($sql);
        // s s i i i s s s s d s d d
        $stmt->bind_param(
            "ssiiissssdsdd",
            $estimate_no,$to_name,$pax,$foc,$total_pax,$group_name,
            $start_date,$end_date,$wdate,
            $profit,$profit_memo,$grand_total,$per_pax
        );
        $stmt->execute();
        $estimate_id = (int)$dbConn->insert_id;
    } else {
        // UPDATE
        $estimate_id = (int)$_POST['id'];
        $sql = "UPDATE estimate_master SET
                    estimate_no=?, to_name=?, pax=?, foc=?, total_pax=?, group_name=?, start_date=?, end_date=?, wdate=?,
                    profit=?, profit_memo=?, grand_total=?, per_pax=?
                WHERE id=?";
        $stmt = $dbConn->prepare($sql);
        // s s i i i s s s s d s d d i
        $stmt->bind_param(
            "ssiiissssdsddi",
            $estimate_no,$to_name,$pax,$foc,$total_pax,$group_name,
            $start_date,$end_date,$wdate,
            $profit,$profit_memo,$grand_total,$per_pax,
            $estimate_id
        );
        $stmt->execute();

        // 기존 아이템 삭제
        $dbConn->query("DELETE FROM estimate_items WHERE estimate_id=".$estimate_id);
    }

    // ===== 아이템 저장 =====
    if (!empty($items)) {
        $sql = "INSERT INTO estimate_items
                (estimate_id,section,label,qty,unit,cnt,`sum`,etc_json)
                VALUES (?,?,?,?,?,?,?,?)";
        $stmt = $dbConn->prepare($sql);

        foreach ($items as $it) {
            $section = isset($it['section']) ? (string)$it['section'] : '';
            $label   = isset($it['label'])   ? (string)$it['label']   : '';
            $qty     = isset($it['qty'])     ? (float)$it['qty']      : 0.0;
            $unit    = isset($it['unit'])    ? (float)$it['unit']     : 0.0;
            $cnt     = isset($it['cnt'])     ? (float)$it['cnt']      : 1.0;
            $sum     = isset($it['sum'])     ? (float)$it['sum']      : ($qty * $unit * $cnt);

            $etc = isset($it['etc']) ? $it['etc'] : [];
            if (is_string($etc)) {
                $tmp = json_decode($etc, true);
                $etc = (json_last_error() === JSON_ERROR_NONE) ? $tmp : [];
            }
            $etc_json = json_encode($etc, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

            // i s s d d d d s
            $stmt->bind_param(
                "issdddds",
                $estimate_id,$section,$label,$qty,$unit,$cnt,$sum,$etc_json
            );
            $stmt->execute();
        }
    }

    $dbConn->commit();
    echo json_encode(["result" => "OK", "id" => $estimate_id]);
    exit;

} catch (Exception $e) {
    try { if ($dbConn) $dbConn->rollback(); } catch (Exception $ignore) {}
    http_response_code(500);
    echo json_encode([
        "result" => "ERR",
        "msg"    => "save_estimate failed",
        "error"  => $e->getMessage()
    ]);
    exit;
}
