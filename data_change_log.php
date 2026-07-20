<?php
include "include/header.php";

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

require_once __DIR__ . '/include/data_change_logger.php';

function dcl_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dcl_param($key, $default = '')
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}

function dcl_valid_date($date)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
}

function dcl_action_label($action)
{
    $labels = array(
        'INSERT' => '신규 등록',
        'UPDATE' => '정보 수정',
        'DELETE' => '삭제',
        'REPLACE' => '대체 저장',
        'ALTER' => '구조 변경',
        'DROP' => '구조 삭제',
        'CREATE' => '구조 생성',
        'TRUNCATE' => '전체 비움',
        'RENAME' => '이름 변경',
    );

    return isset($labels[$action]) ? $labels[$action] : $action;
}

function dcl_business_label($table, $script)
{
    $table = (string)$table;
    $script = basename((string)$script);

    $map = array(
        'member_list' => '직원/사용자 관리',
        'reserve_info' => '예약 관리',
        'reserve_info_total' => '예약 통합 관리',
        'reserve_customer' => '예약 고객 관리',
        'product_info' => '상품 관리',
        'code_base' => '기초 코드 관리',
        'att_log' => '근태 관리',
        'invoice_info' => '인보이스 관리',
        'payment_history' => '결제 이력',
        'hotel_info' => '호텔 관리',
        'guide_info' => '가이드 관리',
        'bus_info' => '차량 관리',
        'memo_info' => '메모 관리',
        'board_info' => '게시판 관리',
    );

    if ($table !== '') {
        foreach ($map as $key => $label) {
            if ($table === $key || strpos($table, $key) !== false) {
                return $label;
            }
        }
    }

    if (strpos($script, 'reservation') !== false || strpos($script, 'reserve') !== false) {
        return '예약 관리';
    }
    if (strpos($script, 'product') !== false || strpos($script, 'prod') !== false) {
        return '상품 관리';
    }
    if (strpos($script, 'emp') !== false || strpos($script, 'employee') !== false) {
        return '직원/사용자 관리';
    }
    if (strpos($script, 'invoice') !== false || strpos($script, 'pay') !== false) {
        return '정산/결제 관리';
    }
    if (strpos($script, 'hotel') !== false) {
        return '호텔 관리';
    }
    if (strpos($script, 'guide') !== false) {
        return '가이드 관리';
    }

    return $table !== '' ? $table : '기타 업무';
}

function dcl_user_options($selectedUser, $canViewAll)
{
    global $dbConn, $user_dbinfo;

    if (!$canViewAll) {
        $uid = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : '';
        $name = isset($user_dbinfo['kor_name']) ? $user_dbinfo['kor_name'] : '';
        return '<option value="' . dcl_h($uid) . '" selected>' . dcl_h($name . ' (' . $uid . ')') . '</option>';
    }

    $html = '<option value="">전체 사용자</option>';
    $sql = "select userid, kor_name from member_list where del_yn = 'N' or del_yn is null order by kor_name, userid";
    $rst = mysql_query($sql, $dbConn);
    if ($rst) {
        while ($row = mysql_fetch_assoc($rst)) {
            $uid = isset($row['userid']) ? $row['userid'] : '';
            $name = isset($row['kor_name']) ? $row['kor_name'] : '';
            $sel = ($selectedUser !== '' && $selectedUser === $uid) ? ' selected' : '';
            $html .= '<option value="' . dcl_h($uid) . '"' . $sel . '>' . dcl_h($name . ' (' . $uid . ')') . '</option>';
        }
    }

    return $html;
}

function dcl_read_entries($startDate, $endDate, $filters, $limit)
{
    $entries = array();
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    if ($start === false || $end === false || $start > $end) {
        return $entries;
    }

    for ($ts = $end; $ts >= $start; $ts = strtotime('-1 day', $ts)) {
        $day = date('Y-m-d', $ts);
        $file = DATA_CHANGE_LOG_DIR . '/' . date('Y-m', $ts) . '/' . $day . '.jsonl';
        if (!is_file($file)) {
            continue;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            continue;
        }

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $row = json_decode($lines[$i], true);
            if (!is_array($row)) {
                continue;
            }
            if ($filters['user_id'] !== '' && (!isset($row['user_id']) || $row['user_id'] !== $filters['user_id'])) {
                continue;
            }
            if ($filters['action'] !== '' && (!isset($row['action']) || $row['action'] !== $filters['action'])) {
                continue;
            }
            if ($filters['table'] !== '' && (!isset($row['table']) || stripos($row['table'], $filters['table']) === false)) {
                continue;
            }
            if ($filters['keyword'] !== '' && (!isset($row['sql']) || stripos($row['sql'], $filters['keyword']) === false)) {
                continue;
            }
            $entries[] = $row;
            if (count($entries) >= $limit) {
                return $entries;
            }
        }
    }

    return $entries;
}

$canViewAll = isset($user_dbinfo['division']) && $user_dbinfo['division'] === 'admin';
$today = date('Y-m-d');
$minDate = date('Y-m-d', strtotime('-' . DATA_CHANGE_LOG_RETENTION_MONTHS . ' months'));
$defaultStart = date('Y-m-d', strtotime('-30 days'));

$startDate = dcl_valid_date(dcl_param('start_date', $defaultStart));
$endDate = dcl_valid_date(dcl_param('end_date', $today));
if ($startDate === '' || $startDate < $minDate) {
    $startDate = $minDate;
}
if ($endDate === '' || $endDate > $today) {
    $endDate = $today;
}
if ($startDate > $endDate) {
    $startDate = $endDate;
}

$selectedUser = dcl_param('user_id');
if (!$canViewAll) {
    $selectedUser = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : '';
}

$filters = array(
    'user_id' => $selectedUser,
    'action' => dcl_param('action'),
    'table' => dcl_param('table'),
    'keyword' => dcl_param('keyword'),
);

$limit = (int)dcl_param('limit', '500');
if ($limit < 50) {
    $limit = 50;
} elseif ($limit > 2000) {
    $limit = 2000;
}

data_change_log_purge_old();
$entries = dcl_read_entries($startDate, $endDate, $filters, $limit);

$summary = array('total' => count($entries), 'success' => 0, 'fail' => 0, 'users' => array(), 'business' => array(), 'days' => array());
foreach ($entries as $entry) {
    $ok = !empty($entry['success']);
    $ok ? $summary['success']++ : $summary['fail']++;

    $uid = isset($entry['user_id']) ? $entry['user_id'] : '';
    if ($uid !== '') {
        $summary['users'][$uid] = true;
    }

    $business = dcl_business_label(isset($entry['table']) ? $entry['table'] : '', isset($entry['script']) ? $entry['script'] : '');
    if (!isset($summary['business'][$business])) {
        $summary['business'][$business] = 0;
    }
    $summary['business'][$business]++;

    $day = isset($entry['ts']) ? substr($entry['ts'], 0, 10) : '';
    if ($day === '') {
        $day = '날짜 없음';
    }
    if (!isset($summary['days'][$day])) {
        $summary['days'][$day] = array();
    }
    $summary['days'][$day][] = $entry;
}
arsort($summary['business']);
$topBusiness = count($summary['business']) > 0 ? key($summary['business']) : '-';
?>

<style>
.audit-filter { background:#fff; border:1px solid #d9dee4; padding:14px; margin-bottom:14px; }
.audit-filter .form-group { margin-bottom:10px; }
.audit-kpis { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
.audit-kpi { flex:1 1 150px; border:1px solid #d9dee4; background:#fff; padding:12px; min-height:74px; }
.audit-kpi strong { display:block; font-size:24px; line-height:28px; color:#2f4050; }
.audit-kpi span { color:#687789; font-size:12px; }
.audit-business { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 14px; padding:0; list-style:none; }
.audit-business li { background:#f7f9fb; border:1px solid #d9dee4; padding:7px 10px; }
.audit-day { margin-bottom:18px; }
.audit-day h4 { margin:0 0 8px; font-size:15px; color:#2f4050; }
.audit-item { background:#fff; border:1px solid #d9dee4; margin-bottom:8px; padding:12px 14px; }
.audit-item.fail { border-left:4px solid #d9534f; }
.audit-item.ok { border-left:4px solid #5cb85c; }
.audit-head { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:8px; }
.audit-time { font-weight:bold; color:#2f4050; }
.audit-badge { display:inline-block; padding:3px 7px; background:#eef3f7; border:1px solid #d9dee4; font-size:12px; }
.audit-badge.ok { color:#2e7d32; background:#eef8ef; border-color:#b9dfbd; }
.audit-badge.fail { color:#b92c28; background:#fbefef; border-color:#e3b8b8; }
.audit-main { font-size:14px; margin-bottom:8px; }
.audit-meta { color:#687789; font-size:12px; display:flex; flex-wrap:wrap; gap:12px; }
.audit-sql { margin-top:8px; }
.audit-sql summary { cursor:pointer; color:#337ab7; }
.audit-sql code { display:block; margin-top:6px; white-space:normal; word-break:break-all; }
</style>

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li>로그</li>
                <li>데이터 변경 이력</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-12">
                <form action="data_change_log.php" method="get" class="audit-filter">
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>조회 시작일</label>
                                <input type="date" name="start_date" value="<?=dcl_h($startDate)?>" min="<?=dcl_h($minDate)?>" max="<?=dcl_h($today)?>" class="form-control input-sm">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>조회 종료일</label>
                                <input type="date" name="end_date" value="<?=dcl_h($endDate)?>" min="<?=dcl_h($minDate)?>" max="<?=dcl_h($today)?>" class="form-control input-sm">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>담당자</label>
                                <select name="user_id" class="form-control input-sm">
                                    <?=dcl_user_options($selectedUser, $canViewAll)?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>업무 처리</label>
                                <select name="action" class="form-control input-sm">
                                    <?php
                                    $actions = array('' => '전체', 'INSERT' => '신규 등록', 'UPDATE' => '정보 수정', 'DELETE' => '삭제', 'REPLACE' => '대체 저장', 'ALTER' => '구조 변경', 'DROP' => '구조 삭제', 'CREATE' => '구조 생성', 'TRUNCATE' => '전체 비움', 'RENAME' => '이름 변경');
                                    foreach ($actions as $value => $label) {
                                        $sel = ($filters['action'] === $value) ? ' selected' : '';
                                        echo '<option value="' . dcl_h($value) . '"' . $sel . '>' . dcl_h($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                <label>업무 테이블</label>
                                <input type="text" name="table" value="<?=dcl_h($filters['table'])?>" class="form-control input-sm" placeholder="예: reserve, member, product">
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="form-group">
                                <label>업무 내용 검색</label>
                                <input type="text" name="keyword" value="<?=dcl_h($filters['keyword'])?>" class="form-control input-sm" placeholder="SQL 내용에서 검색">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>표시 건수</label>
                                <input type="number" name="limit" value="<?=dcl_h($limit)?>" min="50" max="2000" class="form-control input-sm">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-sm btn-block">조회</button>
                        </div>
                    </div>
                </form>

                <div class="audit-kpis">
                    <div class="audit-kpi"><strong><?=dcl_h($summary['total'])?></strong><span>전체 변경 건수</span></div>
                    <div class="audit-kpi"><strong><?=dcl_h($summary['success'])?></strong><span>정상 처리</span></div>
                    <div class="audit-kpi"><strong><?=dcl_h($summary['fail'])?></strong><span>실패 처리</span></div>
                    <div class="audit-kpi"><strong><?=dcl_h(count($summary['users']))?></strong><span>처리 담당자 수</span></div>
                    <div class="audit-kpi"><strong><?=dcl_h($topBusiness)?></strong><span>가장 많은 변경 업무</span></div>
                </div>

                <?php if (!empty($summary['business'])): ?>
                    <ul class="audit-business">
                        <?php foreach ($summary['business'] as $business => $count): ?>
                            <li><strong><?=dcl_h($business)?></strong> <?=dcl_h($count)?>건</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (empty($entries)): ?>
                    <div class="audit-item">조회된 데이터 변경 이력이 없습니다.</div>
                <?php else: ?>
                    <?php foreach ($summary['days'] as $day => $dayEntries): ?>
                        <div class="audit-day">
                            <h4><?=dcl_h($day)?> 변경 이력</h4>
                            <?php foreach ($dayEntries as $entry): ?>
                                <?php
                                $ok = !empty($entry['success']);
                                $action = isset($entry['action']) ? $entry['action'] : '';
                                $table = isset($entry['table']) ? $entry['table'] : '';
                                $script = isset($entry['script']) ? $entry['script'] : '';
                                $business = dcl_business_label($table, $script);
                                $time = isset($entry['ts']) ? date('H:i:s', strtotime($entry['ts'])) : '';
                                $userText = (isset($entry['user_name']) && $entry['user_name'] !== '' ? $entry['user_name'] . ' ' : '') . '(' . (isset($entry['user_id']) ? $entry['user_id'] : '') . ')';
                                $affected = isset($entry['affected_rows']) && $entry['affected_rows'] !== null ? $entry['affected_rows'] . '건 영향' : '영향 건수 없음';
                                ?>
                                <div class="audit-item <?=$ok ? 'ok' : 'fail'?>">
                                    <div class="audit-head">
                                        <span class="audit-time"><?=dcl_h($time)?></span>
                                        <span class="audit-badge"><?=dcl_h($business)?></span>
                                        <span class="audit-badge"><?=dcl_h(dcl_action_label($action))?></span>
                                        <span class="audit-badge <?=$ok ? 'ok' : 'fail'?>"><?=$ok ? '성공' : '실패'?></span>
                                    </div>
                                    <div class="audit-main">
                                        <?=dcl_h($userText)?> 담당자가 <?=dcl_h($business)?>에서 <?=dcl_h(dcl_action_label($action))?> 처리했습니다.
                                    </div>
                                    <div class="audit-meta">
                                        <span>대상: <?=dcl_h($table !== '' ? $table : '-')?></span>
                                        <span>화면: <?=dcl_h($script !== '' ? $script : '-')?></span>
                                        <span><?=dcl_h($affected)?></span>
                                        <?php if (!$ok && !empty($entry['error'])): ?>
                                            <span class="text-danger">오류: <?=dcl_h($entry['error'])?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($entry['sql'])): ?>
                                        <details class="audit-sql">
                                            <summary>상세 SQL 보기</summary>
                                            <code><?=dcl_h($entry['sql'])?></code>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>