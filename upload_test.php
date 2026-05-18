<?php
// email-sys/test_upload.php
// 업로드 기능 테스트용 페이지

include "include/inc_base.php";

if ($_COOKIE['MEMLOGIN_ADMIN_PURUN'] == "") {
    echo "<meta http-equiv='refresh' content='0; url=../login.php'>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>업로드 테스트</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>업로드 기능 테스트</h2>
    
    <!-- 1. 파일 존재 여부 확인 -->
    <div id="file-check">
        <h3>1. 파일 존재 여부 확인</h3>
        <button onclick="checkFiles()">파일 확인</button>
        <div id="file-check-result"></div>
    </div>
    
    <!-- 2. 직접 업로드 테스트 -->
    <div id="direct-upload">
        <h3>2. 직접 업로드 테스트</h3>
        <form id="upload-form" enctype="multipart/form-data">
            <input type="file" name="file" accept="image/*" required>
            <button type="submit">업로드 테스트</button>
        </form>
        <div id="upload-result"></div>
    </div>
    
    <!-- 3. 현재 경로 정보 -->
    <div id="path-info">
        <h3>3. 경로 정보</h3>
        <p><strong>현재 PHP 파일:</strong> <?= __FILE__ ?></p>
        <p><strong>현재 디렉토리:</strong> <?= __DIR__ ?></p>
        <p><strong>웹 경로:</strong> <?= $_SERVER['REQUEST_URI'] ?></p>
        <p><strong>업로드 폴더:</strong> <?= realpath('uploads/newsletter/') ?: '존재하지 않음' ?></p>
    </div>

    <script>
    // 파일 존재 여부 확인
    function checkFiles() {
        const files = [
            'upload_image.php',
            'uploads/newsletter/',
            'newsletter_write.php'
        ];
        
        let result = '<ul>';
        let checkCount = 0;
        
        files.forEach(file => {
            fetch(file)
                .then(response => {
                    const status = response.ok ? '✅ 존재' : '❌ 없음 (' + response.status + ')';
                    result += `<li><strong>${file}:</strong> ${status}</li>`;
                    checkCount++;
                    if (checkCount === files.length) {
                        result += '</ul>';
                        document.getElementById('file-check-result').innerHTML = result;
                    }
                })
                .catch(error => {
                    result += `<li><strong>${file}:</strong> ❌ 오류 (${error.message})</li>`;
                    checkCount++;
                    if (checkCount === files.length) {
                        result += '</ul>';
                        document.getElementById('file-check-result').innerHTML = result;
                    }
                });
        });
    }
    
    // 직접 업로드 테스트
    $('#upload-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $('#upload-result').html('업로드 중...');
        
        $.ajax({
            url: 'upload_image.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#upload-result').html(
                    '<div style="color: green;">✅ 업로드 성공!</div>' +
                    '<pre>' + JSON.stringify(response, null, 2) + '</pre>'
                );
            },
            error: function(xhr, status, error) {
                $('#upload-result').html(
                    '<div style="color: red;">❌ 업로드 실패!</div>' +
                    '<p><strong>상태:</strong> ' + xhr.status + '</p>' +
                    '<p><strong>응답:</strong> ' + xhr.responseText + '</p>' +
                    '<p><strong>오류:</strong> ' + error + '</p>'
                );
            }
        });
    });
    
    // 페이지 로드 시 자동 파일 확인
    $(document).ready(function() {
        checkFiles();
    });
    </script>
</body>
</html>