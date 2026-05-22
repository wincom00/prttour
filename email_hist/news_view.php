<?php
include "../include/header.php";

$seq_no = isset($_GET['seq_no']) ? (int)$_GET['seq_no'] : 0;

if (!$seq_no) {
    echo "<script>alert('잘못된 접근입니다.'); location.href='news_list.php';</script>";
    exit;
}

// 조회수 증가
$update_sql = "UPDATE news_hist SET count_n = count_n + 1 WHERE seq_no = " . (int)$seq_no;
mysql_query($update_sql);

// 뉴스 데이터 조회
$sql = "SELECT * FROM news_hist WHERE seq_no = " . (int)$seq_no;
$result = mysql_query($sql);

if (!$result || mysql_num_rows($result) == 0) {
    echo "<script>alert('존재하지 않는 뉴스입니다.'); location.href='news_list.php';</script>";
    exit;
}

$news = mysql_fetch_assoc($result);

// 이전/다음 뉴스 조회
$prev_sql = "SELECT seq_no, subj FROM news_hist WHERE seq_no < " . (int)$seq_no . " ORDER BY seq_no DESC LIMIT 1";
$prev_result = mysql_query($prev_sql);
$prev_news = mysql_num_rows($prev_result) > 0 ? mysql_fetch_assoc($prev_result) : null;

$next_sql = "SELECT seq_no, subj FROM news_hist WHERE seq_no > " . (int)$seq_no . " ORDER BY seq_no ASC LIMIT 1";
$next_result = mysql_query($next_sql);
$next_news = mysql_num_rows($next_result) > 0 ? mysql_fetch_assoc($next_result) : null;
?>
<style>
        .news-view {
            padding: 20px;
        }
        .news-header {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 5px solid #007bff;
        }
        .news-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        .news-meta {
            color: #666;
            font-size: 14px;
        }
        .news-meta .badge {
            margin-left: 10px;
        }
        .news-content {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .news-image {
            text-align: center;
            margin: 20px 0;
        }
        .news-image img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .navigation-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .nav-item {
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .nav-item:last-child {
            border-bottom: none;
        }
        .nav-item strong {
            display: inline-block;
            width: 80px;
        }
        .nav-link {
            color: #007bff;
            text-decoration: none;
        }
        .nav-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .action-buttons1 {
            text-align: center;
            margin: 30px 0;
        }
        .btn-group-custom1 {
            margin: 0 5px;
        }
    </style>
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
    <div class="container-fluid news-view">
        <div class="row">
            <div class="col-md-12">
                <!-- 뉴스 헤더 -->
                <div class="news-header">
                    <h1 class="news-title"><?php echo htmlspecialchars($news['subj']); ?></h1>
                    <div class="news-meta">
                        <i class="fa fa-calendar"></i> 
                        등록일: <?php echo $news['wdate'] ? date('Y년 m월 d일 H:i', strtotime($news['wdate'])) : '-'; ?>
                        
                        <?php if ($news['send_date']): ?>
                        <span class="badge badge-info">
                            <i class="fa fa-paper-plane"></i> 
                            발송일: <?php echo date('Y-m-d H:i', strtotime($news['send_date'])); ?>
                        </span>
                        <?php endif; ?>
                        
                        <span class="badge badge-secondary">
                            <i class="fa fa-eye"></i> 
                            조회수: <?php echo number_format($news['count_n']); ?>
                        </span>
                    </div>
                </div>

                <!-- 대표 이미지 -->
                <?php if (!empty($news['img'])): ?>
                <div class="news-image">
                    <img src="<?php echo htmlspecialchars($news['img']); ?>" 
                         alt="<?php echo htmlspecialchars($news['subj']); ?>" 
                         onerror="this.style.display='none'">
                </div>
                <?php endif; ?>

                <!-- 뉴스 내용 -->
                <div class="news-content">
                    <?php 
                    // HTML 태그가 포함된 내용을 안전하게 출력
                    // 기본적인 HTML 태그만 허용
                   // $allowed_tags = '<p><br><strong><b><em><i><u><a><img><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><div><span>';
                    echo "<img src='{$news['content']}' width='800px'>";
                    ?>
                </div>

                <!-- 액션 버튼 -->
                <div class="action-buttons1">
                    <div class="btn-group-custom1">
                        <a href="news_list.php?division=10&pdx=2&sub=15" class="btn btn-secondary btn-lg">
                            <i class="fa fa-list"></i> 목록으로
                        </a>
                    
                        <a href="news_form.php?division=10&pdx=2&sub=15&seq_no=<?php echo $news['seq_no']; ?>" class="btn btn-warning btn-lg">
                            <i class="fa fa-edit"></i> 수정하기
                        </a>
				        <button type="button" class="btn btn-danger btn-lg" onclick="deleteNews()">
                            <i class="fa fa-trash"></i> 삭제하기
                        </button>
                       <!--<button type="button" class="btn btn-info btn-lg" onclick="shareNews()">
                            <i class="fa fa-share"></i> 공유하기
                        </button>-->
                    </div>
                </div>

                <!-- 이전/다음 네비게이션 -->
                <div class="navigation-section">
                    <h5><i class="fa fa-arrows"></i> 이전/다음 뉴스</h5>
                    
                    <div class="nav-item">
                        <strong>이전글:</strong>
                        <?php if ($prev_news): ?>
                        <a href="news_view.php?seq_no=<?php echo $prev_news['seq_no']; ?>" class="nav-link">
                            <?php echo htmlspecialchars($prev_news['subj']); ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">이전 글이 없습니다.</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="nav-item">
                        <strong>다음글:</strong>
                        <?php if ($next_news): ?>
                        <a href="news_view.php?seq_no=<?php echo $next_news['seq_no']; ?>" class="nav-link">
                            <?php echo htmlspecialchars($next_news['subj']); ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">다음 글이 없습니다.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 뉴스 정보 상세 -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fa fa-info-circle"></i> 뉴스 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="120">뉴스 번호:</th>
                                        <td><?php echo $news['seq_no']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>등록일:</th>
                                        <td><?php echo $news['wdate'] ? date('Y-m-d H:i:s', strtotime($news['wdate'])) : '-'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>발송일:</th>
                                        <td><?php echo $news['send_date'] ? date('Y-m-d H:i:s', strtotime($news['send_date'])) : '즉시 발송'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="120">조회수:</th>
                                        <td><?php echo number_format($news['count_n']); ?>회</td>
                                    </tr>
                                    <tr>
                                        <th>이미지:</th>
                                        <td>
                                            <?php if (!empty($news['img'])): ?>
                                            <a href="<?php echo htmlspecialchars($news['img']); ?>" target="_blank" class="text-primary">
                                                <i class="fa fa-external-link"></i> 이미지 보기
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted">없음</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>내용 길이:</th>
                                        <td><?php echo number_format(strlen($news['content'])); ?>자</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
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
        // 뉴스 삭제
        function deleteNews() {
            if (confirm('정말로 이 뉴스를 삭제하시겠습니까?\n삭제된 뉴스는 복구할 수 없습니다.')) {
                location.href = 'news_delete.php?division=10&pdx=2&sub=15&seq_no=<?php echo $news['seq_no']; ?>';
            }
        }

        // 뉴스 공유
        function shareNews() {
            var url = window.location.href;
            var title = '<?php echo addslashes($news['subj']); ?>';
            
            if (navigator.share) {
                // Web Share API 지원 브라우저
                navigator.share({
                    title: title,
                    url: url
                });
            } else {
                // 클립보드에 URL 복사
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(function() {
                        alert('뉴스 URL이 클립보드에 복사되었습니다.');
                    });
                } else {
                    // 클립보드 API 미지원 시 수동으로 URL 표시
                    prompt('다음 URL을 복사하세요:', url);
                }
            }
        }

        // 키보드 네비게이션
        $(document).keydown(function(e) {
            <?php if ($prev_news): ?>
            if (e.keyCode == 37) { // 왼쪽 화살표
                location.href = 'news_view.php?division=10&pdx=2&sub=15&seq_no=<?php echo $prev_news['seq_no']; ?>';
            }
            <?php endif; ?>
            
            <?php if ($next_news): ?>
            if (e.keyCode == 39) { // 오른쪽 화살표
                location.href = 'news_view.php?division=10&pdx=2&sub=15&seq_no=<?php echo $next_news['seq_no']; ?>';
            }
            <?php endif; ?>
            
            if (e.keyCode == 27) { // ESC 키
                location.href = 'news_list.php?division=10&pdx=2&sub=15';
            }
        });

        // 이미지 에러 처리
        $('img').on('error', function() {
            $(this).hide();
            $(this).after('<div class="alert alert-warning">이미지를 불러올 수 없습니다.</div>');
        });
    </script>
</body>
</html>