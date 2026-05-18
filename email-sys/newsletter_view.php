<?php
include "../include/header.php";

if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

$seq_no = isset($_GET['seq_no']) ? $_GET['seq_no'] : '';

if(!$seq_no) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

// 뉴스레터 정보 조회
$qry = "SELECT * FROM newsletter_templates WHERE seq_no = '$seq_no'";
$rst = mysql_query($qry, $dbConn);
$newsletter = mysql_fetch_assoc($rst);

if(!$newsletter) {
    echo "<script>alert('뉴스레터를 찾을 수 없습니다.'); history.back();</script>";
    exit;
}
?>

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="index.php"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="../newsletter_main.php">뉴스레터 관리</a></li>
                <li>미리보기</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <h4 class="heading"><strong>뉴스레터 미리보기</strong></h4>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="widget">
                    <div class="widget-header">
                        <div class="widget-caption">
                            <i class="fa fa-eye"></i> <?= htmlspecialchars($newsletter['title']) ?>
                        </div>
                        <div class="widget-buttons">
                            <?php if($newsletter['send_status'] == 'DRAFT'): ?>
                            <a href="/email-sys/newsletter_write.php?seq_no=<?= $seq_no ?>" class="btn btn-warning btn-sm">
                                <i class="fa fa-edit"></i> 수정
                            </a>
                            <button type="button" class="btn btn-success btn-sm" onclick="sendNewsletter(<?= $seq_no ?>,'<?= $newsletter['target_region'] ?>')">
                                <i class="fa fa-paper-plane"></i> 발송
                            </button>
                            <?php endif; ?>
                            <a href="../newsletter_main.php" class="btn btn-default btn-sm">
                                <i class="fa fa-list"></i> 목록
                            </a>
                        </div>
                    </div>
                    <div class="widget-body">
                        <div class="form-group">
                            <label><strong>이메일 제목:</strong></label>
                            <p class="form-control-static"><?= htmlspecialchars($newsletter['subject']) ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label><strong>발송상태:</strong></label>
                            <p class="form-control-static">
                                <?php if($newsletter['send_status'] == 'SENT'): ?>
                                    <span class="label label-success">발송완료</span>
                                    (<?= date('Y-m-d H:i', strtotime($newsletter['send_date'])) ?>)
                                <?php else: ?>
                                    <span class="label label-warning">임시저장</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label><strong>작성자:</strong></label>
                            <p class="form-control-static"><?= $newsletter['created_by'] ?></p>
                        </div>
                        
                        <div class="form-group">
                            <label><strong>작성일:</strong></label>
                            <p class="form-control-static"><?= date('Y-m-d H:i', strtotime($newsletter['created_at'])) ?></p>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group">
                            <label><strong>이메일 미리보기:</strong></label>
                            <div class="well" style="background: white; border: 1px solid #ddd;">
                                <!-- 이메일 템플릿 미리보기 -->
                                <?= $newsletter['content'] ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
  include '../include/side_m.php';
?>
<script>
function sendNewsletter(seq_no,region) {
    if(confirm('뉴스레터를 발송하시겠습니까? 발송 후에는 수정이 불가능합니다.')) {
        $.ajax({
            url: '/email-sys/newsletter_send.php',
            type: 'POST',
            data: {seq_no: seq_no,region: region},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('뉴스레터가 성공적으로 발송되었습니다.');
                    location.reload();
                } else {
                    alert('발송 중 오류가 발생했습니다: ' + response.message);
                }
            },
            error: function() {
                alert('발송 중 오류가 발생했습니다.');
            }
        });
    }
}
</script>

</body>
</html>