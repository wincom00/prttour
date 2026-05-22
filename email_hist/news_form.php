<?php
include "../include/header.php";
//include "../include/inc_base.php";


$seq_no = isset($_GET['seq_no']) ? (int)$_GET['seq_no'] : 0;
$is_edit = $seq_no > 0;

// 수정 모드일 때 기존 데이터 조회
$news_data = array(
    'subj' => '',
    'content' => '',
    'send_date' => '',
    'img' => '',
    'count_n' => 0
);

if ($is_edit) {
    $sql = "SELECT * FROM news_hist WHERE seq_no = " . (int)$seq_no;
    $result = mysql_query($sql);
    
    if (!$result || mysql_num_rows($result) == 0) {
        echo "<script>alert('존재하지 않는 뉴스입니다.'); location.href='news_list.php';</script>";
        exit;
    }
    
    $news_data = mysql_fetch_assoc($result);
}

// 폼 제출 처리
//echo $_POST['mode'] ;
if ($_POST['mode'] == 'save') {
    $subj = trim($_POST['subj']);
    $content = trim($_POST['content']);
    $send_date = !empty($_POST['send_date']) ? $_POST['send_date'] : NULL;
    $img = trim($_POST['img']);
    
    // 유효성 검사
    $errors = array();
    
    if (empty($subj)) {
        $errors[] = "제목을 입력해주세요.";
    }
    
    if (empty($content)) {
        $errors[] = "내용을 입력해주세요.";
    }
    
    if (empty($errors)) {
        // SQL 이스케이프 처리
        $subj = mysql_real_escape_string($subj);
        $content = mysql_real_escape_string($content);
        $img = mysql_real_escape_string($img);
        $send_date_sql = $send_date ? "'" . mysql_real_escape_string($send_date) . "'" : "NULL";
        
        if ($is_edit) {
            // 수정
            $sql = "UPDATE news_hist SET 
                    subj = '$subj', 
                    content = '$content', 
                    send_date = $send_date_sql, 
                    img = '$img' 
                    WHERE seq_no = " . (int)$seq_no;
        } else {
            // 신규 등록
            $sql = "INSERT INTO news_hist (subj, content, send_date, img, wdate, count_n) 
                    VALUES ('$subj', '$content', $send_date_sql, '$img', NOW(), 0)";
        }
        ///echo $sql;
        $result = mysql_query($sql);
        
        if ($result) {
            $message = $is_edit ? "뉴스가 성공적으로 수정되었습니다." : "뉴스가 성공적으로 등록되었습니다.";
            echo "<script>alert('$message'); location.href='news_list.php?division=10&pdx=2&sub=15&seq_no=$seq_no';</script>";
            exit;
        } else {
            $errors[] = "데이터베이스 오류가 발생했습니다: " . mysql_error();
        }
    }
}
?>

<div id="contentwrapper">
		<div class="main_content">
			<div id="jCrumbs" class="breadCrumb module">
				<ul>
					<li>
						<a href="/admin"><i class="glyphicon glyphicon-home"></i></a>
					</li>
					<li>
						<a href="#">고객관리</a>
					</li>
					<li>
						<a href="#">홈페이지 뉴스레터</a>
					</li>
					<li>
						뉴스레터작성
					</li>
				</ul>
			</div>
			
   <div class="row">  
    <div class="container-fluid news-form">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fa fa-<?php echo $is_edit ? 'edit' : 'plus'; ?>"></i> 
                        <?php echo $is_edit ? '뉴스 수정' : '뉴스 작성'; ?>
                    </h2>
                    <a href="news_list.php" class="btn btn-secondary">
                        <i class="fa fa-list"></i> 목록으로
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="news_form.php?division=10&pdx=2&sub=15&seq_no=<?=$seq_no?>" enctype="multipart/form-data">
				<input type="hidden" name="mode" id="mode"  value="save">
                    <!-- 기본 정보 섹션 -->
                    <div class="form-section">
                        <h4><i class="fa fa-info-circle"></i> 기본 정보</h4>
                        
                        <div class="form-group">
                            <label for="subj">제목 <span class="required">*</span></label>
                            <input type="text" name="subj" id="subj" class="form-control" 
                                   value="<?php echo htmlspecialchars($news_data['subj']); ?>" 
                                   maxlength="200" required>
                            <div class="char-counter">
                                <span id="subj-counter">0</span>/200자
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="send_date">발송일</label>
                            <input type="date" name="send_date" id="send_date" class="form-control"
                                   value="<?php echo $news_data['send_date'] ? date('Y-m-d', strtotime($news_data['send_date'])) : ''; ?>">
                            <small class="form-text text-muted">비워두면 즉시 발송됩니다.</small>
                        </div>
                    </div>

                    <!-- 이미지 섹션 -->
                    <div class="form-section">
                        <h4><i class="fa fa-image"></i> 대표 이미지</h4>
                        
                        <div class="form-group">
                            <label for="img">이미지 URL</label>
                            <input type="url" name="img" id="img" class="form-control" 
                                   value="<?php echo htmlspecialchars($news_data['img']); ?>"
                                   placeholder="https://example.com/image.jpg">
                            <small class="form-text text-muted">이미지의 전체 URL을 입력해주세요.</small>
                        </div>

                        <?php if (!empty($news_data['img'])): ?>
                        <div class="form-group">
                            <label>현재 이미지 미리보기</label><br>
                            <img src="<?php echo htmlspecialchars($news_data['img']); ?>" 
                                 alt="뉴스 이미지" class="image-preview" id="image-preview">
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- 내용 섹션 -->
                    <div class="form-section">
                        <h4><i class="fa fa-file-text"></i> 뉴스 내용</h4>
                        
                        <div class="form-group">
                            <label for="content">내용 <span class="required">*</span></label>
                            <textarea name="content" id="content" class="form-control content-editor" 
                                      placeholder="뉴스 내용을 입력해주세요.https://example.com/image.jpg" 
                                      required><?php echo htmlspecialchars($news_data['content']); ?></textarea>
                            <small class="form-text text-muted">
                                
                            </small>
                        </div>
						<?php if (!empty($news_data['content'])): ?>
                        <div class="form-group">
                            <label>현재 이미지 미리보기</label><br>
							
                            <img src="<?php echo htmlspecialchars($news_data['content']); ?>" 
                                 alt="뉴스 이미지" class="image-preview" id="image-preview2">
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- 통계 정보 (수정 모드일 때만) -->
                    <?php if ($is_edit): ?>
                    <div class="form-section">
                        <h4><i class="fa fa-bar-chart"></i> 통계 정보</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>현재 조회수</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo number_format($news_data['count_n']); ?>회" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>등록일</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $news_data['wdate'] ? date('Y-m-d H:i:s', strtotime($news_data['wdate'])) : '-'; ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 버튼 그룹 -->
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> 
                            <?php echo $is_edit ? '수정하기' : '등록하기'; ?>
                        </button>
                        <a href="news_list.php?division=10&pdx=2&sub=15" class="btn btn-secondary btn-lg">
                            <i class="fa fa-times"></i> 취소
                        </a>
                        <?php if ($is_edit): ?>
                        <a href="news_view.php?division=10&pdx=2&sub=15&seq_no=<?php echo $seq_no; ?>" class="btn btn-info btn-lg">
                            <i class="fa fa-eye"></i> 미리보기
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
   </div>
</div>
<?php
		include "../include/side_m.php"
?>
    <script src="js/jquery.min.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // 제목 글자수 카운터
            function updateCharCounter() {
                var length = $('#subj').val().length;
                $('#subj-counter').text(length);
                
                if (length > 180) {
                    $('#subj-counter').parent().addClass('text-warning');
                } else {
                    $('#subj-counter').parent().removeClass('text-warning');
                }
            }
            
            $('#subj').on('input', updateCharCounter);
            updateCharCounter();

            // 이미지 URL 변경 시 미리보기 업데이트
            $('#image-preview').on('input', function() {
                var imageUrl = $(this).val();
                if (imageUrl) {
                    if ($('#image-preview').length === 0) {
                        $(this).parent().append('<div class="form-group"><label>이미지 미리보기</label><br><img id="image-preview" class="image-preview" alt="뉴스 이미지"></div>');
                    }
                    $('#image-preview').attr('src', imageUrl).show();
                } else {
                    $('#image-preview').hide();
                }
            });
			
			// 이미지 URL 변경 시 미리보기 업데이트
            $('#image-preview2').on('input', function() {
                var imageUrl = $(this).val();
				
                if (imageUrl) {
                    if ($('#image-preview2').length === 0) {
                        $(this).parent().append('<div class="form-group"><label>이미지 미리보기</label><br><img id="image-preview2" class="image-preview" alt="뉴스 이미지"></div>');
                    }
                    $('#image-preview2').attr('src', imageUrl).show();
                } else {
                    $('#image-preview2').hide();
                }
            });

            // 폼 제출 전 확인
            $('form').on('submit', function(e) {
                var subj = $('#subj').val().trim();
                var content = $('#content').val().trim();
                
                if (!subj) {
                    alert('제목을 입력해주세요.');
                    $('#subj').focus();
                    e.preventDefault();
                    return false;
                }
                
                if (!content) {
                    alert('내용을 입력해주세요.');
                    $('#content').focus();
                    e.preventDefault();
                    return false;
                }
                
                return confirm('<?php echo $is_edit ? "뉴스를 수정하시겠습니까?" : "뉴스를 등록하시겠습니까?"; ?>');
            });
        });
    </script>
</body>
</html>