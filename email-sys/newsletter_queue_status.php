<?php
include "../include/header.php";

if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

// 진행중이거나 최근 완료된 큐 조회
$qry = "SELECT q.*, n.title, n.subject 
        FROM newsletter_queue q 
        LEFT JOIN newsletter_templates n ON q.newsletter_id = n.seq_no 
        ORDER BY q.created_at DESC 
        LIMIT 20";
$rst = mysql_query($qry, $dbConn);
?>

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
                        </div>
                        <div class="widget-buttons">
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
                            <table class="table table-striped table-bordered" id="queueTable">
                                <thead>
                                    <tr>
                                        <th width="60">큐 ID</th>
                                        <th>뉴스레터 제목</th>
                                        <th width="100">상태</th>
                                        <th width="150">진행률</th>
                                        <th width="100">발송성공</th>
                                        <th width="100">발송실패</th>
                                        <th width="150">시작시간</th>
                                        <th width="150">완료시간</th>
                                        <th width="100">작업자</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysql_fetch_assoc($rst)): ?>
                                    <tr class="queue-row" data-queue-id="<?= $row['seq_no'] ?>">
                                        <td><?= $row['seq_no'] ?></td>
                                        <td><?= htmlspecialchars($row['subject']) ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch($row['status']) {
                                                case 'WAITING':
                                                    $status_class = 'label-warning';
                                                    $status_text = '대기중';
                                                    break;
                                                case 'PROCESSING':
                                                    $status_class = 'label-info';
                                                    $status_text = '진행중';
                                                    break;
                                                case 'COMPLETED':
                                                    $status_class = 'label-success';
                                                    $status_text = '완료';
                                                    break;
                                                case 'FAILED':
                                                    $status_class = 'label-danger';
                                                    $status_text = '실패';
                                                    break;
                                            }
                                            ?>
                                            <span class="label <?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td>
                                            <div class="progress" style="margin-bottom: 0;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?= $row['progress_percent'] ?>%">
                                                    <?= $row['progress_percent'] ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-success">
                                                <i class="fa fa-check"></i> <?= $row['sent_count'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-danger">
                                                <i class="fa fa-times"></i> <?= $row['failed_count'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $row['started_at'] ? date('m-d H:i', strtotime($row['started_at'])) : '-' ?>
                                        </td>
                                        <td>
                                            <?= $row['completed_at'] ? date('m-d H:i', strtotime($row['completed_at'])) : '-' ?>
                                        </td>
                                        <td><?= $row['created_by'] ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 처리중인 큐가 있는 경우 실시간 업데이트 -->
        <?php
        $processing_qry = "SELECT COUNT(*) as cnt FROM newsletter_queue WHERE status IN ('WAITING', 'PROCESSING')";
        $processing_rst = mysql_query($processing_qry, $dbConn);
        $processing_row = mysql_fetch_assoc($processing_rst);
        $has_processing = $processing_row['cnt'] > 0;
        ?>

        <?php if($has_processing): ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong>백그라운드 작업 진행중!</strong> 페이지가 자동으로 새로고침됩니다.
                    <button type="button" class="btn btn-sm btn-warning" onclick="triggerWorker()" style="margin-left: 10px;">
                        <i class="fa fa-play"></i> 워커 실행
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
  include "../include/side_m.php";
 ?>

<script>
$(document).ready(function() {
    $('#queueTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Korean.json"
        },
        "order": [[ 0, "desc" ]],
        "pageLength": 10
    });

    <?php if($has_processing): ?>
    // 진행중인 작업이 있으면 5초마다 자동 새로고침
    setInterval(function() {
        refreshStatus();
    }, 5000);
    <?php endif; ?>
});

function refreshStatus() {
    location.reload();
}

function triggerWorker() {
    $.ajax({
        url: 'newsletter_trigger_worker.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                alert('워커가 실행되었습니다.');
                setTimeout(function() {
                    refreshStatus();
                }, 2000);
            } else {
                alert('워커 실행 중 오류가 발생했습니다: ' + response.message);
            }
        },
        error: function() {
            alert('워커 실행 중 오류가 발생했습니다.');
        }
    });
}
</script>

</body>
</html>