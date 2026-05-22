<?php
include "../include/header.php";

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

// 진행중이거나 최근 완료된 큐 조회
$qry = "SELECT q.*, n.title, n.subject,
               (SELECT COUNT(*) FROM newsletter_send_details d WHERE d.queue_id = q.seq_no AND d.send_status = 'PENDING') AS pending_count
        FROM newsletter_queue q
        LEFT JOIN newsletter_templates n ON q.newsletter_id = n.seq_no
        ORDER BY q.created_at DESC
        LIMIT 20";
$rst = mysql_query($qry, $dbConn);
?>

<style>
/* ===== 발송 큐 상태 가시성 개선 ===== */
#queueTable td { vertical-align: middle; }
#queueTable th { vertical-align: middle; text-align: center; background-color: #f5f7fa; }

/* 체크박스 크게 */
#queueTable .queue-check,
#queueTable #checkAllQueue { width: 18px; height: 18px; cursor: pointer; }

/* 진행중(실행중) 워커 행 강조 */
#queueTable tr.active-queue-row > td {
    background-color: #fff8e1 !important;
    box-shadow: inset 4px 0 0 #f0ad4e;
    font-weight: 600;
    animation: queuePulse 2s ease-in-out infinite;
}
@keyframes queuePulse {
    0%   { background-color: #fff8e1 !important; }
    50%  { background-color: #ffecb3 !important; }
    100% { background-color: #fff8e1 !important; }
}

/* 상태 뱃지 크게 */
#queueTable .status-badge {
    display: inline-block;
    font-size: 13px;
    padding: 5px 10px;
    border-radius: 4px;
    min-width: 64px;
}

/* 진행률 바 */
#queueTable .queue-progress {
    margin-bottom: 0;
    height: 22px;
    border-radius: 4px;
}
#queueTable .queue-progress .progress-bar {
    line-height: 22px;
    font-size: 12px;
    font-weight: 700;
    min-width: 3em;
}
#queueTable .queue-progress .progress-bar-stopped { background-color: #9e9e9e; }

/* 발송 성공/실패/남은 메일 숫자 가독성 */
#queueTable .count-cell { font-size: 14px; font-weight: 700; }
#queueTable td.col-num { text-align: center; }
</style>

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="index.php"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="../newsletter_main.php">뉴스레터 관리</a></li>
                <li>발송 진행상황</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <h4 class="heading"><strong>뉴스레터 발송 진행상황</strong></h4>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="widget">
                    <div class="widget-header">
                        <div class="widget-caption">
                            <i class="fa fa-tasks"></i> 발송 큐 상태
                            <span id="systemCronBadge" class="label label-default" style="margin-left: 15px; font-size: 12px; padding: 4px 8px;">시스템 상태 확인중...</span>
                        </div>
                        <div class="widget-buttons">
                            <button type="button" class="btn btn-danger btn-sm" id="btnSysStop" onclick="controlSystem('stop')" style="margin-right: 15px;" title="시스템 전체 실행 차단">
                                <i class="fa fa-power-off"></i> 시스템 중지
                            </button>
                            <button type="button" class="btn btn-success btn-sm" id="btnSysResume" onclick="controlSystem('resume')" style="margin-right: 15px; display: none;" title="시스템 전체 실행 허용">
                                <i class="fa fa-play-circle"></i> 시스템 재개
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" onclick="triggerSelectedWorker()">
                                <i class="fa fa-play"></i> 선택 실행
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="stopSelectedWorker()">
                                <i class="fa fa-stop"></i> 선택 중지
                            </button>
                            <button type="button" class="btn btn-info btn-sm" onclick="refreshStatus()">
                                <i class="fa fa-refresh"></i> 새로고침
                            </button>
                            <a href="../newsletter_main.php" class="btn btn-default btn-sm">
                                <i class="fa fa-arrow-left"></i> 목록으로
                            </a>
                        </div>
                    </div>
                    <div class="widget-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="queueTable">
                                <thead>
                                    <tr>
                                        <th width="40"><input type="checkbox" id="checkAllQueue"></th>
                                        <th width="60">큐 ID</th>
                                        <th>뉴스레터 제목</th>
                                        <th width="70">상태</th>
                                        <th width="170">진행률</th>
                                        <th width="80">발송성공</th>
                                        <th width="80">발송실패</th>
                                        <th width="100">남은메일</th>
                                        <th width="100">시작시간</th>
                                        <th width="100">완료시간</th>
                                        <th width="100">작업자</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysql_fetch_assoc($rst)): ?>
                                    <?php
                                        $pending_count = intval($row['pending_count']);
                                        $is_processing = ($row['status'] == 'PROCESSING');

                                        $status_class = '';
                                        $status_text  = '';
                                        $status_icon  = '';
                                        $bar_class    = 'progress-bar-info';
                                        switch($row['status']) {
                                            case 'WAITING':
                                                $status_class = 'label-warning';
                                                $status_text  = '대기중';
                                                $status_icon  = 'fa-clock-o';
                                                $bar_class    = 'progress-bar-info';
                                                break;
                                            case 'PROCESSING':
                                                $status_class = 'label-info';
                                                $status_text  = '진행중';
                                                $status_icon  = 'fa-spinner fa-spin';
                                                $bar_class    = 'progress-bar-warning progress-bar-striped active';
                                                break;
                                            case 'COMPLETED':
                                                $status_class = 'label-success';
                                                $status_text  = '완료';
                                                $status_icon  = 'fa-check';
                                                $bar_class    = 'progress-bar-success';
                                                break;
                                            case 'FAILED':
                                                $status_class = 'label-danger';
                                                $status_text  = '실패';
                                                $status_icon  = 'fa-times';
                                                $bar_class    = 'progress-bar-danger';
                                                break;
                                            case 'STOPPED':
                                                $status_class = 'label-default';
                                                $status_text  = '중지';
                                                $status_icon  = 'fa-pause';
                                                $bar_class    = 'progress-bar-stopped';
                                                break;
                                        }
                                        $row_class = 'queue-row' . ($is_processing ? ' active-queue-row' : '');
                                    ?>
                                    <tr class="<?= $row_class ?>" data-queue-id="<?= $row['seq_no'] ?>">
                                        <td class="col-num">
                                            <?php if($pending_count > 0): ?>
                                                <input type="checkbox" class="queue-check" value="<?= $row['seq_no'] ?>">
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-num"><strong><?= $row['seq_no'] ?></strong></td>
                                        <td><?= htmlspecialchars($row['subject']) ?></td>
                                        <td class="col-num cell-status">
                                            <span class="label <?= $status_class ?> status-badge">
                                                <i class="fa <?= $status_icon ?>"></i> <?= $status_text ?>
                                            </span>
                                        </td>
                                        <td class="cell-progress">
                                            <div class="progress queue-progress">
                                                <div class="progress-bar <?= $bar_class ?>" role="progressbar"
                                                     style="width: <?= $row['progress_percent'] ?>%">
                                                    <?= $row['progress_percent'] ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="col-num cell-sent">
                                            <span class="text-success count-cell">
                                                <i class="fa fa-check"></i> <?= number_format($row['sent_count']) ?>
                                            </span>
                                        </td>
                                        <td class="col-num cell-failed">
                                            <?php if(intval($row['failed_count']) > 0): ?>
                                                <span class="text-danger count-cell">
                                                    <i class="fa fa-times"></i> <?= number_format($row['failed_count']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted count-cell">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-num cell-pending">
                                            <?php if($pending_count > 0): ?>
                                                <span class="label label-warning count-cell"><?= number_format($pending_count) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted count-cell">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-num cell-started">
                                            <?= $row['started_at'] ? date('m-d H:i', strtotime($row['started_at'])) : '-' ?>
                                        </td>
                                        <td class="col-num cell-completed">
                                            <?= $row['completed_at'] ? date('m-d H:i', strtotime($row['completed_at'])) : '-' ?>
                                        </td>
                                        <td class="col-num"><?= $row['created_by'] ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 처리중인 큐가 있는 경우 백그라운드 실시간 갱신 안내 -->
        <?php
        $processing_qry = "SELECT COUNT(*) as cnt FROM newsletter_queue WHERE status IN ('WAITING', 'PROCESSING')";
        $processing_rst = mysql_query($processing_qry, $dbConn);
        $processing_row = mysql_fetch_assoc($processing_rst);
        $has_processing = $processing_row['cnt'] > 0;
        ?>

        <div class="row<?= $has_processing ? '' : ' hidden' ?>" id="processingAlertRow">
            <div class="col-sm-12">
                <div class="alert alert-info" style="margin-bottom: 0;">
                    <i class="fa fa-info-circle"></i>
                    <strong>백그라운드 작업 진행중!</strong> 새로고침 없이 실시간으로 갱신됩니다.
                    <span class="text-muted" style="margin-left: 8px;">(마지막 갱신: <span id="lastUpdated">-</span>)</span>
                    <button type="button" class="btn btn-sm btn-warning" onclick="triggerSelectedWorker()" style="margin-left: 10px;">
                        <i class="fa fa-play"></i> 워커 실행
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
  include "../include/side_m.php";
 ?>

<script>
// 체크 상태 저장소 키 (탭 세션 동안 유지 -> 수동 새로고침 시에도 보존)
var QUEUE_CHECK_KEY = 'newsletter_checked_queues';
var queueTable;

function getStoredChecks() {
    try {
        return JSON.parse(sessionStorage.getItem(QUEUE_CHECK_KEY)) || [];
    } catch(e) {
        return [];
    }
}

function setCheckState(id, checked) {
    id = String(id);
    var stored = getStoredChecks();
    var idx = stored.indexOf(id);
    if (checked && idx === -1) {
        stored.push(id);
    } else if (!checked && idx !== -1) {
        stored.splice(idx, 1);
    }
    sessionStorage.setItem(QUEUE_CHECK_KEY, JSON.stringify(stored));
}

// 저장된 체크 상태를 현재 표에 복원
function restoreChecks() {
    var stored = getStoredChecks();
    queueTable.$('.queue-check').each(function() {
        $(this).prop('checked', stored.indexOf(String($(this).val())) !== -1);
    });
    syncCheckAll();
}

// 더 이상 존재하지 않는(=발송 완료된) 큐 ID는 저장소에서 정리
function pruneChecks() {
    var stored = getStoredChecks();
    var existing = [];
    queueTable.$('.queue-check').each(function() {
        existing.push(String($(this).val()));
    });
    var pruned = stored.filter(function(id) { return existing.indexOf(id) !== -1; });
    sessionStorage.setItem(QUEUE_CHECK_KEY, JSON.stringify(pruned));
}

// 전체선택 체크박스 상태 동기화
function syncCheckAll() {
    var total = queueTable.$('.queue-check').length;
    var checked = queueTable.$('.queue-check:checked').length;
    $('#checkAllQueue').prop('checked', total > 0 && total === checked);
}

// ===== 새로고침 없이 백그라운드로 상태만 갱신 =====
function statusMeta(status) {
    switch (status) {
        case 'WAITING':    return { cls: 'label-warning', text: '대기중', icon: 'fa-clock-o',        bar: 'progress-bar-info' };
        case 'PROCESSING': return { cls: 'label-info',    text: '진행중', icon: 'fa-spinner fa-spin', bar: 'progress-bar-warning progress-bar-striped active' };
        case 'COMPLETED':  return { cls: 'label-success', text: '완료',   icon: 'fa-check',          bar: 'progress-bar-success' };
        case 'FAILED':     return { cls: 'label-danger',  text: '실패',   icon: 'fa-times',          bar: 'progress-bar-danger' };
        case 'STOPPED':    return { cls: 'label-default',  text: '중지',   icon: 'fa-pause',          bar: 'progress-bar-stopped' };
        default:           return { cls: '',              text: status,   icon: '',                  bar: 'progress-bar-info' };
    }
}

function fmtNum(n) {
    return Number(n || 0).toLocaleString();
}

// 한 행의 동적 셀(상태/진행률/카운트/시간)만 제자리 갱신 — 체크박스 셀은 건드리지 않음
function updateRow(r) {
    var $tr = queueTable.$('tr[data-queue-id="' + r.seq_no + '"]');
    if (!$tr.length) return;

    var m = statusMeta(r.status);
    $tr.toggleClass('active-queue-row', r.status === 'PROCESSING');

    $tr.find('.cell-status').html(
        '<span class="label ' + m.cls + ' status-badge"><i class="fa ' + m.icon + '"></i> ' + m.text + '</span>'
    );

    var p = r.progress_percent;
    $tr.find('.cell-progress').html(
        '<div class="progress queue-progress"><div class="progress-bar ' + m.bar +
        '" role="progressbar" style="width: ' + p + '%">' + p + '%</div></div>'
    );

    $tr.find('.cell-sent').html(
        '<span class="text-success count-cell"><i class="fa fa-check"></i> ' + fmtNum(r.sent_count) + '</span>'
    );

    if (r.failed_count > 0) {
        $tr.find('.cell-failed').html(
            '<span class="text-danger count-cell"><i class="fa fa-times"></i> ' + fmtNum(r.failed_count) + '</span>'
        );
    } else {
        $tr.find('.cell-failed').html('<span class="text-muted count-cell">0</span>');
    }

    if (r.pending_count > 0) {
        $tr.find('.cell-pending').html('<span class="label label-warning count-cell">' + fmtNum(r.pending_count) + '</span>');
    } else {
        $tr.find('.cell-pending').html('<span class="text-muted count-cell">0</span>');
    }

    $tr.find('.cell-started').text(r.started_at);
    $tr.find('.cell-completed').text(r.completed_at);
}

function pollStatus() {
    $.getJSON('newsletter_queue_status_data.php', function(res) {
        if (!res || !res.success) return;
        $.each(res.rows, function(i, r) { updateRow(r); });
        $('#processingAlertRow').toggleClass('hidden', !res.has_processing);
        var now = new Date();
        $('#lastUpdated').text(now.toTimeString().substr(0, 8));
    });
}

$(document).ready(function() {
    queueTable = $('#queueTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Korean.json"
        },
        "order": [[ 1, "desc" ]],
        "pageLength": 10
    });

    // 초기화: 만료된 항목 정리 후 복원
    pruneChecks();
    restoreChecks();

    // 페이지 전환 등으로 표가 다시 그려질 때마다 체크 상태 복원
    queueTable.on('draw', function() {
        restoreChecks();
    });

    // 개별 체크박스 변경 -> 저장소에 반영 (이벤트 위임)
    $('#queueTable').on('change', '.queue-check', function() {
        setCheckState($(this).val(), this.checked);
        syncCheckAll();
    });

    // 전체 선택/해제 (모든 페이지 행 대상)
    $('#checkAllQueue').on('change', function() {
        var checked = this.checked;
        queueTable.$('.queue-check').each(function() {
            $(this).prop('checked', checked);
            setCheckState($(this).val(), checked);
        });
    });

    // 페이지 새로고침 없이 5초마다 백그라운드로 상태만 갱신
    setInterval(pollStatus, 5000);
    setInterval(pollSystemStatus, 5000);
    pollStatus();
    pollSystemStatus();
});

function refreshStatus() {
    location.reload();
}

function getSelectedQueueIds() {
    var ids = [];
    queueTable.$('.queue-check:checked').each(function() {
        ids.push($(this).val());
    });
    return ids;
}

function triggerSelectedWorker() {
    var ids = getSelectedQueueIds();

    if(ids.length == 0) {
        alert('실행할 큐를 선택해 주세요.');
        return;
    }

    triggerWorker(ids);
}

function stopSelectedWorker() {
    var ids = getSelectedQueueIds();

    if(ids.length == 0) {
        alert('중지할 큐를 선택해 주세요.');
        return;
    }

    if(!confirm('선택한 ' + ids.length + '개 큐의 발송을 중지하시겠습니까?')) {
        return;
    }

    $.ajax({
        url: 'newsletter_stop_worker.php',
        type: 'POST',
        data: { queue_ids: ids },
        dataType: 'json',
        success: function(response) {
            alert(response.message);
            setTimeout(pollStatus, 1500);
        },
        error: function(xhr, textStatus) {
            if (xhr.status === 0) {
                alert('서버에 연결할 수 없습니다. 웹 서버(Laragon/Apache)가 실행 중인지 확인해 주세요.');
            } else if (textStatus === 'parsererror') {
                alert('서버 응답을 해석할 수 없습니다(JSON 아님). PHP 오류 로그를 확인해 주세요.');
            } else {
                alert('중지 요청 실패 (HTTP ' + xhr.status + ').');
            }
        }
    });
}

function pollSystemStatus() {
    $.getJSON('newsletter_cron_api.php?action=status', function(res) {
        if(res && res.success) {
            var badge = $('#systemCronBadge');
            if(res.cron_status === 'STOPPED') {
                badge.removeClass('label-default label-success label-warning').addClass('label-danger').text('시스템 중지됨 (Cron 차단)');
                $('#btnSysStop').hide();
                $('#btnSysResume').show();
            } else {
                if(res.worker_status === 'RUNNING') {
                    badge.removeClass('label-default label-danger label-success').addClass('label-warning').text('워커 실행중 (PID: ' + res.pid + ')');
                } else {
                    badge.removeClass('label-default label-danger label-warning').addClass('label-success').text('시스템 정상 (대기중)');
                }
                $('#btnSysResume').hide();
                $('#btnSysStop').show();
            }
        }
    });
}

function controlSystem(action) {
    var msg = action === 'stop' ? '시스템 전체 워커 실행을 즉시 중지하고 크론 자동실행을 차단하시겠습니까?\n\n(현재 발송 중인 모든 작업이 중단됩니다)' : '시스템 자동실행(크론)을 다시 재개하시겠습니까?';
    if(!confirm(msg)) return;
    
    $.post('newsletter_cron_api.php', {action: action}, function(res) {
        alert(res.message);
        pollSystemStatus();
        pollStatus();
    }, 'json');
}

function triggerWorker(queueIds) {
    $.ajax({
        url: 'newsletter_trigger_worker.php',
        type: 'POST',
        data: {
            queue_ids: queueIds || []
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('워커가 실행되었습니다. 상태는 새로고침 없이 자동 갱신됩니다.');
                setTimeout(pollStatus, 2000);
            } else {
                alert(response.message);
            }
        },
        error: function(xhr, textStatus) {
            if (xhr.status === 0) {
                alert('서버에 연결할 수 없습니다. 웹 서버(Laragon/Apache)가 실행 중인지 확인해 주세요.');
            } else if (textStatus === 'parsererror') {
                alert('서버 응답을 해석할 수 없습니다(JSON 아님). PHP 오류 로그를 확인해 주세요.');
            } else {
                alert('워커 실행 요청 실패 (HTTP ' + xhr.status + ').');
            }
        }
    });
}
</script>

</body>
</html>
