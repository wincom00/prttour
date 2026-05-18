<?php
include "../include/header.php";

if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] == "") {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

$seq_no = isset($_GET['seq_no']) ? $_GET['seq_no'] : '';
$newsletter = null;

// 수정 모드일 경우
if($seq_no) {
    $qry = "SELECT * FROM newsletter_templates WHERE seq_no = '$seq_no'";
    $rst = mysql_query($qry,$dbConn);
    $newsletter = mysql_fetch_assoc($rst);
    
    if(!$newsletter || $newsletter['send_status'] == 'SENT') {
        echo "<script>alert('수정할 수 없는 뉴스레터입니다.'); history.back();</script>";
        exit;
    }
}

// 저장 처리
if($_POST['action'] == 'save') {
    $title = mysql_real_escape_string($_POST['title']);
    $subject = mysql_real_escape_string($_POST['subject']);
    $content = mysql_real_escape_string($_POST['content']);
    $main_image = mysql_real_escape_string($_POST['main_image']); // 메인 이미지 추가
	$target_region = mysql_real_escape_string($_POST['target_region']);
    $userid = $user_dbinfo['userid'];
    
    if($seq_no) {
        // 수정
        $qry = "UPDATE newsletter_templates SET 
                title = '$title', 
                subject = '$subject', 
                content = '$content',
                main_image = '$main_image',
				target_region = '$target_region',
                updated_at = NOW() 
                WHERE seq_no = '$seq_no'";
    } else {
        // 신규 작성
        $qry = "INSERT INTO newsletter_templates (title, subject, content, main_image,target_region, created_by, created_at) 
                VALUES ('$title', '$subject', '$content', '$main_image','$target_region', '$userid', NOW())";
    }
    
    if(mysql_query($qry, $dbConn)) {
        echo "<script>alert('저장되었습니다.'); location.href='/newsletter_main.php';</script>";
        exit;
    } else {
        echo "<script>alert('저장 중 오류가 발생했습니다.');</script>";
    }
}
?>

<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="index.php"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="../newsletter_main.php">뉴스레터 관리</a></li>
                <li><?= $seq_no ? '뉴스레터 수정' : '뉴스레터 작성' ?></li>
            </ul>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <h4 class="heading"><strong><?= $seq_no ? '뉴스레터 수정' : '뉴스레터 작성' ?></strong></h4>
            </div>
        </div>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" onsubmit="return validateForm()" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="main_image" id="main_image_url" value="<?= htmlspecialchars(isset($newsletter['main_image']) ? $newsletter['main_image'] : '') ?>">
            
            <div class="row">
                <div class="col-sm-12">
                    <div class="widget">
                        <div class="widget-header">
                            <div class="widget-caption">
                                <i class="fa fa-edit"></i> 뉴스레터 정보
                            </div>
                        </div>
                        <div class="widget-body">
                            <div class="form-group">
                                <label class="control-label">뉴스레터 제목 *</label>
                                <input type="text" name="title" class="form-control" required 
                                       placeholder="뉴스레터 제목을 입력하세요"
                                       value="<?= htmlspecialchars(isset($newsletter['title']) ? $newsletter['title'] : '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label">이메일 제목 *</label>
                                <input type="text" name="subject" class="form-control" required 
                                       placeholder="이메일 제목을 입력하세요"
                                       value="<?= htmlspecialchars(isset($newsletter['subject']) ? $newsletter['subject'] : '') ?>">
                            </div>
                            <!-- 기존 폼에 지역 선택 필드 추가 -->
							<div class="form-group">
								<label for="target_region">대상 지역 <span class="text-danger">*</span></label>
								<select name="target_region" id="target_region" class="form-control" required>
									<option value="전지역" <?= (isset($newsletter['target_region']) && $newsletter['target_region'] == '전지역') ? 'selected' : '' ?>>전지역</option>
									<option value="본사" <?= (isset($newsletter['target_region']) && $newsletter['target_region'] == '본사') ? 'selected' : '' ?>>본사 (뉴저지/뉴욕)</option>
									<option value="서부" <?= (isset($newsletter['target_region']) && $newsletter['target_region'] == '서부') ? 'selected' : '' ?>>서부 (캘리포니아/LA)</option>
								</select>
								<small class="help-block">선택한 지역의 고객들에게만 뉴스레터가 발송됩니다.</small>
							</div>
                            <!-- ================================
                                 메인 이미지 업로드 섹션 (NEW!)
                                 ================================ -->
                            <div class="form-group">
                                <label class="control-label">메인 이미지</label>
                                <div class="main-image-upload-container">
                                    <!-- 이미지 미리보기 영역 -->
                                    <div class="image-preview-area" id="image-preview-area">
                                        <?php if(isset($newsletter['main_image']) && $newsletter['main_image']): ?>
                                            <img src="<?= htmlspecialchars($newsletter['main_image']) ?>" 
                                                 alt="메인 이미지" class="preview-image" id="preview-image">
                                            <div class="image-overlay">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeMainImage()">
                                                    <i class="fa fa-trash"></i> 삭제
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="upload-placeholder" id="upload-placeholder">
                                                <i class="fa fa-image fa-3x"></i>
                                                <p>클릭하여 메인 이미지를 업로드하세요</p>
                                                <small class="text-muted">권장 크기: 386X370px, 최대 1MB</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- 파일 선택 버튼 -->
                                    <div class="upload-controls">
                                        <input type="file" id="main_image_file" accept="image/*" style="display: none;" onchange="handleMainImageUpload(this)">
                                        <button type="button" class="btn btn-primary" onclick="document.getElementById('main_image_file').click()">
                                            <i class="fa fa-upload"></i> 
                                            <?= isset($newsletter['main_image']) && $newsletter['main_image'] ? '이미지 변경' : '이미지 선택' ?>
                                        </button>
                                        <?php if(isset($newsletter['main_image']) && $newsletter['main_image']): ?>
                                        <button type="button" class="btn btn-default" onclick="removeMainImage()">
                                            <i class="fa fa-times"></i> 제거
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label">내용 *</label>
                                <textarea name="content" id="newsletter_content" class="form-control" 
                                          style="height: 600px;"><?= htmlspecialchars(isset($newsletter['content']) ? $newsletter['content'] : '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa fa-save"></i> 저장하기
                    </button>
                    <a href="/newsletter_main.php" class="btn btn-default btn-lg">
                        <i class="fa fa-arrow-left"></i> 목록으로
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include "../include/side_m.php"; ?>

<!-- 메인 이미지 업로드 스타일 -->
<style>
.main-image-upload-container {
    border: 2px dashed #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: #f9f9f9;
}

.image-preview-area {
    position: relative;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
}

.preview-image {
    max-width: 100%;
    max-height: 300px;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.upload-placeholder {
    text-align: center;
    color: #999;
    padding: 40px 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-placeholder:hover {
    background: #f5f5f5;
    color: #666;
}

.image-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.image-preview-area:hover .image-overlay {
    opacity: 1;
}

.upload-controls {
    padding: 15px;
    background: #f8f8f8;
    border-top: 1px solid #eee;
    text-align: center;
}

.upload-controls .btn {
    margin: 0 5px;
}

.upload-progress {
    margin: 10px 0;
}

.upload-progress .progress {
    height: 6px;
    margin-bottom: 5px;
}
</style>

<!-- TinyMCE 에디터 -->
<script src="/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>

<script>
// TinyMCE 초기화
tinymce.init({
    selector: '#newsletter_content',
    height: 700,
    language: 'ko_KR',
    
    
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons'
    ],
    
    toolbar: 'undo redo | blocks fontfamily fontsize | ' +
             'bold italic underline strikethrough | link image media table | ' +
             'align lineheight | numlist bullist indent outdent | emoticons charmap | ' +
             'removeformat | code fullscreen preview',
    
    font_family_formats: 
        '맑은 고딕=Malgun Gothic,sans-serif;' +
        '돋움=Dotum,sans-serif;' +
        '굴림=Gulim,sans-serif;' +
        '바탕=Batang,serif;' +
        'Arial=arial,helvetica,sans-serif;' +
        'Times New Roman=times new roman,times,serif;' +
        'Courier New=courier new,courier,monospace',
    
    fontsize_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 28pt 30pt 32pt 34pt 36pt',
    
    images_upload_url: '../upload_image.php',
    automatic_uploads: true,
    // 이미지 삽입 시 스타일 제거
    setup: function(editor) {
        editor.on('ExecCommand', function(e) {
            if (e.command === 'mceInsertContent' || e.command === 'mceInsertImage') {
                setTimeout(function() {
                    var imgs = editor.dom.select('img');
                    imgs.forEach(function(img) {
                        var style = editor.dom.getAttrib(img, 'style');
                        if (style && style.includes('width: 100%') && style.includes('height: 100%')) {
                            editor.dom.setAttrib(img, 'style', '');
                        }
                    });
                }, 10);
            }
        });
    },
    
    document_base_url: 'https://www.myprt.com/admin/',
    relative_urls: false,
    remove_script_host: false,
    content_style: 'body { font-family: Malgun Gothic, sans-serif; font-size: 14px; }',
    menubar: 'file edit view insert format tools table help',
    branding: false,
    resize: 'both',
    elementpath: false,
    statusbar: true
});

// ================================
// 메인 이미지 업로드 기능
// ================================

// 메인 이미지 업로드 처리
function handleMainImageUpload(input) {
    const file = input.files[0];
    if (!file) return;
    
    // 파일 타입 검증
    if (!file.type.startsWith('image/')) {
        alert('이미지 파일만 업로드 가능합니다.');
        return;
    }
    
    // 파일 크기 검증 (1MB)
    if (file.size > 1 * 1024 * 1024) {
        alert('파일 크기는 5MB 이하만 가능합니다.');
        return;
    }
    
    // 업로드 진행 상태 표시
    showUploadProgress();
    
    // FormData 생성
    const formData = new FormData();
    formData.append('file', file);
    
    // AJAX 업로드
    fetch('../upload_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideUploadProgress();
        
        if (data.location || data.url) {
            const imageUrl = data.location || data.url;
            displayMainImage(imageUrl);
            document.getElementById('main_image_url').value = imageUrl;
        } else if (data.error) {
            alert('업로드 실패: ' + data.error);
        } else {
            alert('업로드 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        hideUploadProgress();
        console.error('업로드 오류:', error);
        alert('업로드 중 오류가 발생했습니다.');
    });
}

// 메인 이미지 표시
function displayMainImage(imageUrl) {
    const previewArea = document.getElementById('image-preview-area');
    previewArea.innerHTML = `
        <img src="${imageUrl}" alt="메인 이미지" class="preview-image" id="preview-image">
        <div class="image-overlay">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeMainImage()">
                <i class="fa fa-trash"></i> 삭제
            </button>
        </div>
    `;
    
    // 업로드 버튼 텍스트 변경
    const uploadBtn = document.querySelector('.upload-controls .btn-primary');
    uploadBtn.innerHTML = '<i class="fa fa-upload"></i> 이미지 변경';
    
    // 제거 버튼 추가 (없는 경우)
    if (!document.querySelector('.upload-controls .btn-default')) {
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-default';
        removeBtn.onclick = removeMainImage;
        removeBtn.innerHTML = '<i class="fa fa-times"></i> 제거';
        document.querySelector('.upload-controls').appendChild(removeBtn);
    }
}

// 메인 이미지 제거
function removeMainImage() {
    if (confirm('메인 이미지를 제거하시겠습니까?')) {
        const previewArea = document.getElementById('image-preview-area');
        previewArea.innerHTML = `
            <div class="upload-placeholder" id="upload-placeholder" onclick="document.getElementById('main_image_file').click()">
                <i class="fa fa-image fa-3x"></i>
                <p>클릭하여 메인 이미지를 업로드하세요</p>
                <small class="text-muted">권장 크기: 800x400px, 최대 5MB</small>
            </div>
        `;
        
        document.getElementById('main_image_url').value = '';
        
        // 버튼 텍스트 변경
        const uploadBtn = document.querySelector('.upload-controls .btn-primary');
        uploadBtn.innerHTML = '<i class="fa fa-upload"></i> 이미지 선택';
        
        // 제거 버튼 삭제
        const removeBtn = document.querySelector('.upload-controls .btn-default');
        if (removeBtn) {
            removeBtn.remove();
        }
    }
}

// 업로드 진행 상태 표시
function showUploadProgress() {
    const previewArea = document.getElementById('image-preview-area');
    previewArea.innerHTML = `
        <div class="upload-progress">
            <div class="progress">
                <div class="progress-bar progress-bar-striped active" style="width: 100%"></div>
            </div>
            <p class="text-center">이미지 업로드 중...</p>
        </div>
    `;
}

// 업로드 진행 상태 숨기기
function hideUploadProgress() {
    // displayMainImage 또는 오류 처리에서 처리됨
}

// 드래그 앤 드롭 기능
const previewArea = document.getElementById('image-preview-area');

previewArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.background = '#e8f5e8';
});

previewArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.style.background = '';
});

previewArea.addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.background = '';
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const input = document.getElementById('main_image_file');
        input.files = files;
        handleMainImageUpload(input);
    }
});

// 플레이스홀더 클릭 이벤트
document.addEventListener('click', function(e) {
    if (e.target.closest('#upload-placeholder')) {
        document.getElementById('main_image_file').click();
    }
});

// 폼 검증
function validateForm() {
    // TinyMCE 내용을 textarea에 업데이트
    tinymce.triggerSave();
    
    if(!document.querySelector('input[name="title"]').value.trim()) {
        alert('뉴스레터 제목을 입력해주세요.');
        return false;
    }
    
    if(!document.querySelector('input[name="subject"]').value.trim()) {
        alert('이메일 제목을 입력해주세요.');
        return false;
    }
    
    if(!tinymce.get('newsletter_content').getContent().trim()) {
        alert('내용을 입력해주세요.');
        return false;
    }
    
    return true;
}
</script>
</body>
</html>