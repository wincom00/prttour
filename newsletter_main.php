<?php
include "include/header.php";

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

// 지역 필터 처리
$region_filter = isset($_GET['region']) ? $_GET['region'] : '';
$where_clause = "";
if ($region_filter && $region_filter != '전체') {
    $where_clause = "WHERE target_region = '" . mysql_real_escape_string($region_filter) . "'";
}

// 뉴스레터 목록 조회
$qry = "SELECT * FROM newsletter_templates $where_clause ORDER BY seq_no DESC";
$rst = mysql_query($qry, $dbConn);
?>

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="index.php"><i class="glyphicon glyphicon-home"></i></a></li>
                <li>뉴스레터 관리</li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <h4 class="heading"><strong>뉴스레터 관리</strong></h4>
            </div>
        </div>

        <!-- 지역 필터 -->
        <div class="row">
            <div class="col-sm-12">
                <div class="widget">
                    <div class="widget-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group">
                                <label for="region"><i class="fa fa-filter"></i> 지역 필터:</label>
                                <select name="region" id="region" class="form-control" onchange="this.form.submit()">
                                    <option value="">전체</option>
                                    <option value="전지역" <?= $region_filter == '전지역' ? 'selected' : '' ?>>전지역</option>
                                    <option value="본사" <?= $region_filter == '본사' ? 'selected' : '' ?>>본사</option>
                                    <option value="서부" <?= $region_filter == '서부' ? 'selected' : '' ?>>서부</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="widget">
                    <div class="widget-header">
                        <div class="widget-caption">
                            <i class="fa fa-envelope"></i> 뉴스레터 목록
                        </div>
                        <div class="widget-buttons">
                            <a href="email-sys/newsletter_write.php" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> 새 뉴스레터 작성
                            </a>
                            <a href="email-sys/newsletter_queue_status.php" class="btn btn-info btn-sm">
                                <i class="fa fa-tasks"></i> 발송 진행상황
                            </a>
                        </div>
                    </div>
                    <div class="widget-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="newsletterTable">
                                <thead>
                                    <tr>
                                        <th width="50">번호</th>
                                        <th>제목</th>
                                        <th width="100">대상지역</th>
                                        <th width="150">발송상태</th>
                                        <th width="180">발송일</th>
                                        <th width="100">작성자</th>
                                        <th width="150">관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysql_fetch_assoc($rst)): ?>
                                    <tr>
                                        <td><?= $row['seq_no'] ?></td>
                                        <td><?= $row['subject'] ?></td>
                                        <td>
                                            <?php
                                            $region = isset($row['target_region']) ? $row['target_region'] : '전지역';
                                            $region_class = '';
                                            switch($region) {
                                                case '전지역':
                                                    $region_class = 'label-primary';
                                                    break;
                                                case '본사':
                                                    $region_class = 'label-info';
                                                    break;
                                                case '서부':
                                                    $region_class = 'label-warning';
                                                    break;
                                                default:
                                                    $region_class = 'label-default';
                                            }
                                            ?>
                                            <span class="label <?= $region_class ?>"><?= $region ?></span>
                                        </td>
                                        <td>
                                            <?php if($row['send_status'] == 'SENT'): ?>
                                                <span class="label label-success">발송완료</span>
                                            <?php else: ?>
                                                <span class="label label-warning">임시저장</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $row['send_date'] ? date('Y-m-d H:i', strtotime($row['send_date'])) : '-' ?>
                                        </td>
                                        <td><?= $row['created_by'] ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="email-sys/newsletter_view.php?seq_no=<?= $row['seq_no'] ?>" 
                                                   class="btn btn-info" title="미리보기">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" 
                                                        onclick="deleteNewsletter('<?= $row['seq_no'] ?>')" title="삭제">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                                <?php if($row['send_status'] == 'DRAFT'): ?>
                                                <a href="email-sys/newsletter_write.php?seq_no=<?= $row['seq_no'] ?>" 
                                                   class="btn btn-warning" title="수정">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <!-- 테스트 발송 버튼 추가 -->
                                                <button type="button" class="btn btn-default" 
                                                        onclick="testSendNewsletter(<?= $row['seq_no'] ?>,'<?= $row['target_region'] ?>')">
                                                    <i class="fa fa-flask"></i>
                                                </button>
                                                <button type="button" class="btn btn-success"
                                                        onclick="sendNewsletter(<?= $row['seq_no'] ?>,'<?= $row['target_region'] ?>')">
                                                    <i class="fa fa-paper-plane"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 테스트 발송 모달 추가 -->
<div class="modal fade" id="testSendModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="fa fa-flask"></i> 테스트 발송</h4>
            </div>
            <div class="modal-body">
                <form id="testSendForm">
                    <input type="hidden" id="test_seq_no" name="seq_no">
                    <div class="form-group">
                        <label for="test_emails">테스트 발송할 이메일 주소</label>
                        <textarea class="form-control" id="test_emails" name="test_emails" rows="3" 
                                  placeholder="이메일 주소를 입력하세요. 여러 개인 경우 쉼표(,) 또는 줄바꿈으로 구분하세요.&#10;예: test1@example.com, test2@example.com"></textarea>
                        <small class="help-block">최대 10개까지 테스트 발송 가능합니다.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" onclick="executeTestSend()">
                    <i class="fa fa-paper-plane"></i> 테스트 발송
                </button>
            </div>
        </div>
    </div>
</div>

<?php
include 'include/side_m.php';
?>
<script>
$(document).ready(function() {
    $('#newsletterTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Korean.json"
        },
        "order": [[ 0, "desc" ]]
    });
});

// 테스트 발송 함수 추가
function testSendNewsletter(seq_no,region) {
    $('#test_seq_no').val(seq_no);
    $('#test_emails').val('');
    $('#include_self').prop('checked', true);
    $('#testSendModal').modal('show');
}

// 테스트 발송 실행 함수 추가
function executeTestSend() {
    var seq_no = $('#test_seq_no').val();
    var test_emails = $('#test_emails').val().trim();
    var include_self = $('#include_self').is(':checked');
    
    if (!test_emails) {
        alert('테스트 발송할 이메일 주소를 입력해주세요.');
        return;
    }
    
    // 이메일 주소 유효성 검사
    if (test_emails) {
        var emails = test_emails.split(/[,\n]/).map(function(email) {
            return email.trim();
        }).filter(function(email) {
            return email.length > 0;
        });
        
        if (emails.length > 10) {
            alert('테스트 발송은 최대 10개의 이메일까지만 가능합니다.');
            return;
        }
        
        // 간단한 이메일 형식 검증
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        for (var i = 0; i < emails.length; i++) {
            if (!emailRegex.test(emails['i'])) {
                alert('유효하지 않은 이메일 주소가 있습니다: ' + emails['i']);
                return;
            }
        }
    }
    
    if (confirm('테스트 발송을 진행하시겠습니까?')) {
        $.ajax({
            url: 'email-sys/newsletter_test_send.php',
            type: 'POST',
            data: {
                seq_no: seq_no,
                test_emails: test_emails
            },
            dataType: 'json',
            beforeSend: function() {
                $('#testSendModal .btn-primary').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> 발송중...');
            },
            success: function(response) {
                $('#testSendModal').modal('hide');
                if (response.success) {
                    alert('테스트 발송이 완료되었습니다.\n발송된 이메일: ' + response.sent_count + '개');
                } else {
                    alert('테스트 발송 중 오류가 발생했습니다: ' + response.message);
                }
            },
            error: function() {
                $('#testSendModal').modal('hide');
                alert('테스트 발송 중 오류가 발생했습니다.');
            },
            complete: function() {
                $('#testSendModal .btn-primary').prop('disabled', false).html('<i class="fa fa-paper-plane"></i> 테스트 발송');
            }
        });
    }
}

function sendNewsletter(seq_no,region) {
    if(confirm('뉴스레터를 발송하시겠습니까? 발송 후에는 수정이 불가능합니다.')) {
        $.ajax({
            url: 'email-sys/newsletter_send.php?seq_no='+seq_no+'&region='+region,
            type: 'GET',
            data: {seq_no: seq_no,region:region},
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

function deleteNewsletter(seq_no) {
    if(confirm('정말 삭제하시겠습니까?')) {
        $.ajax({
            url: 'email-sys/newsletter_delete.php?seq_no='+seq_no,
            type: 'GET',
            data: {seq_no: seq_no},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    }
}
</script>