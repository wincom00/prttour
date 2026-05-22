<?php
include "../include/header.php";

if ($_COOKIE[MEMLOGIN_ADMIN_PURUN] !="") {
} else {
	echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
	exit;
}

if (!hasMenuAccess($division, $pdx, $sub)) {
	$goUrl_1 = "/index.php";
	Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인후 사용하세요.!!","");
	echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
	exit;
}
// 페이징 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 검색 조건
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "";

if (!empty($search_keyword)) {
    $search_keyword = mysql_real_escape_string($search_keyword);
    $where_clause = "WHERE subj LIKE '%$search_keyword%' OR content LIKE '%$search_keyword%'";
}

// 전체 개수 조회
$count_sql = "SELECT COUNT(*) as total FROM news_hist $where_clause";
$count_result = mysql_query($count_sql);
$total_rows = mysql_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// 뉴스 목록 조회
$sql = "SELECT seq_no, subj, send_date, count_n, wdate 
        FROM news_hist 
        $where_clause 
        ORDER BY seq_no DESC 
        LIMIT $offset, $limit";

$result = mysql_query($sql);
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
						뉴스레터목록
					</li>
				</ul>
			</div>
			
   <div class="row">  
    <div class="container-fluid news-manager">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="fa fa-newspaper-o"></i> 뉴스 관리 시스템</h2>
                
                <!-- 검색 폼 -->
                <div class="search-form">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-10">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="제목 또는 내용으로 검색..." 
                                       value="<?php echo htmlspecialchars($search_keyword); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-search"></i> 검색
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- 액션 버튼 -->
                <div class="btn-group-actions">
                    <a href="news_form.php?division=10&pdx=2&sub=15" class="btn btn-success">
                        <i class="fa fa-plus"></i> 새 뉴스 작성
                    </a>
                    <button type="button" class="btn btn-danger" onclick="deleteSelected()">
                        <i class="fa fa-trash"></i> 선택 삭제
                    </button>
                </div>

                <!-- 뉴스 목록 테이블 -->
                <div class="table-responsive">
                    <table class="table table-striped table-bordered news-table">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th width="80">번호</th>
                                <th>제목</th>
                                <th width="150">발송일</th>
                                <th width="100">조회수</th>
                                <th width="150">등록일</th>
                                <th width="150">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysql_num_rows($result) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center">등록된 뉴스가 없습니다.</td>
                            </tr>
                            <?php else: ?>
                            <?php while ($news = mysql_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_news[]" 
                                           value="<?php echo $news['seq_no']; ?>" class="news-checkbox">
                                </td>
                                <td><?php echo $news['seq_no']; ?></td>
                                <td>
                                    <a href="news_view.php?division=10&pdx=2&sub=15&seq_no=<?php echo $news['seq_no']; ?>" 
                                       class="text-primary">
                                        <?php echo htmlspecialchars($news['subj']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo $news['send_date'] ? date('Y-m-d', strtotime($news['send_date'])) : '-'; ?>
                                </td>
                                <td class="text-center"><?php echo number_format($news['count_n']); ?></td>
                                <td>
                                    <?php echo $news['wdate'] ? date('Y-m-d H:i', strtotime($news['wdate'])) : '-'; ?>
                                </td>
                                <td class="actions">
                                    <a href="news_view.php?division=10&pdx=2&sub=15&seq_no=<?php echo $news['seq_no']; ?>" 
                                       class="btn btn-info btn-sm" title="상세보기">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="news_form.php?division=10&pdx=2&sub=15&seq_no=<?php echo $news['seq_no']; ?>" 
                                       class="btn btn-warning btn-sm" title="수정">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="deleteNews(<?php echo $news['seq_no']; ?>)" title="삭제">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 페이징 -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="뉴스 페이지네이션">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>">처음</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>">이전</a>
                        </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 5);
                        $end_page = min($total_pages, $page + 5);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>">다음</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>">마지막</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <!-- 통계 정보 -->
                <div class="alert alert-info">
                    <strong>총 <?php echo number_format($total_rows); ?>개</strong>의 뉴스가 있습니다. 
                    (<?php echo $page; ?>/<?php echo $total_pages; ?> 페이지)
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
        // 전체 선택/해제
        function toggleSelectAll() {
            var selectAll = document.getElementById('selectAll');
            var checkboxes = document.querySelectorAll('.news-checkbox');
            
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = selectAll.checked;
            }
        }

        // 단일 뉴스 삭제
        function deleteNews(seq_no) {
            if (confirm('정말로 이 뉴스를 삭제하시겠습니까?')) {
                location.href = 'news_delete.php?division=10&pdx=2&sub=15&seq_no=' + seq_no;
            }
        }

        // 선택된 뉴스들 삭제
        function deleteSelected() {
            var checkboxes = document.querySelectorAll('.news-checkbox:checked');
            
            if (checkboxes.length === 0) {
                alert('삭제할 뉴스를 선택해주세요.');
                return;
            }
            
            if (confirm('선택된 ' + checkboxes.length + '개의 뉴스를 삭제하시겠습니까?')) {
                var seq_nos = [];
                for (var i = 0; i < checkboxes.length; i++) {
                    seq_nos.push(checkboxes[i].value);
                }
                
                location.href = 'news_delete.php?division=10&pdx=2&sub=15&seq_nos=' + seq_nos.join(',');
            }
        }

        // 검색어 하이라이트
        <?php if (!empty($search_keyword)): ?>
        $(document).ready(function() {
            var keyword = '<?php echo addslashes($search_keyword); ?>';
            $('td a').each(function() {
                var text = $(this).html();
                var highlighted = text.replace(new RegExp('(' + keyword + ')', 'gi'), '<mark>$1</mark>');
                $(this).html(highlighted);
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>