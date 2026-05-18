<?php
    include "include/header.php";
	
	if ($_COOKIE[MEMLOGIN_ADMIN_PARAN] != "") {
	} else {
        echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
		exit;
	}
?>
		<div id="contentwrapper" class="js-mainPage">
			<div class="main_content">
				<div id="jCrumbs" class="breadCrumb module">
					<ul>
						<li>
							<a href="#"><i class="glyphicon glyphicon-home"></i></a>
						</li>
					</ul>
				</div>

				<div class="row">
					<!-- <div class="col-sm-4">
						<h3 class="heading">금일 / <span class="month">5월</span> 예약현황</h3>
						<table class="table table-striped table-hover table-condensed dashBoard dashBoard-currentMonth">
							<tr>
								<td>총예약건수: <span class="number">20/103</span></td>
							</tr>
							<tr>
								<td>총확정예약건수: <span class="number">10/78</span></td>
							</tr>
							<tr>
								<td>총미수금건수: <span class="number">3/10</span></td>
							</tr>
							<tr>
								<td>총취소건수: <span class="number">1/11</span></td>
							</tr>
						</table>
					</div> -->
					<div class="col-sm-4">
						<div class="panel panel-default dashBoard">
							<div class="panel-heading">
								<h3 class="panel-title">금일 / <span class="month">5월</span> 예약현황</h3>
							</div>
							<ul class="list-group">
								<li class="list-group-item">총예약건수: <span class="number">20/103</span></li>
								<li class="list-group-item">총확정예약건수: <span class="number">10/78</span></li>
								<li class="list-group-item">총미수금건수: <span class="number">3/10</span></li>
								<li class="list-group-item">총취소건수: <span class="number">1/11</span></li>
							</ul>
						</div>
					</div>
					<!-- <div class="col-sm-4">
						<h3 class="heading">사내공지사항</h3>
						<table class="table table-striped table-bordered table-hover table-condensed dashBoard">
							<tr>
								<td><span class="board_title">금주 목요일 전체회의 아주 기다란 공지사항 제목은 아주 길고도 길다란 제목 그다음은 계속 이어지는 길다란 제목</span></td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>금주목요일전체회의…</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>금주목요일전체회의…</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>금주목요일전체회의…</td>
								<td>2018-05-04</td>
							</tr>
						</table>
					</div> -->
					<div class="col-sm-4">
						<div class="panel panel-default dashBoard">
							<div class="panel-heading">
								<h3 class="panel-title">사내공지사항</h3>
							</div>
							<div class="list-group">
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">금주 목요일 전체회의 아주 기다란 공지사항 제목은 아주 길고도 길다란 제목 그다음은 계속 이어지는 길다란 제목</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">금주목요일전체회의…</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">금주목요일전체회의…</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">금주목요일전체회의…</span><span class="datetime">2018-05-04</span></div></a>
							</div>
						</div>
					</div>
					<!-- <div class="col-sm-4">
						<h3 class="heading">묻고답하기</h3>
						<table class="table table-striped table-bordered table-hover table-condensed dashBoard">
							<tr>
								<td>미동부캐나다5박6일문의…</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>..re :미동부캐나다5박6일</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>출발장소문의…</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>나이아가라당일투어…</td>
								<td>2018-05-04</td>
							</tr>
						</table>
					</div> -->
					<div class="col-sm-4">
						<div class="panel panel-default dashBoard">
							<div class="panel-heading">
								<h3 class="panel-title">묻고답하기</h3>
							</div>
							<div class="list-group">
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">미동부캐나다5박6일문의…</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">..re :미동부캐나다5박6일</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">출발장소문의…</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">나이아가라당일투어…</span><span class="datetime">2018-05-04</span></div></a>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<!-- <div class="col-sm-4">
						<h3 class="heading">로그인사용자기록</h3>
						<table class="table table-striped table-bordered table-hover table-condensed dashBoard">
							<tr>
								<td>
									<span class="name">이은우</span>
									<span class="datetime">2018-06-02 12:00:22</span>
									<br />
									<small class="ip_address">xxx.xxx.xxx.xxx</small>
								</td>
							</tr>
							<tr>
								<td>
									<span class="name">홀길동</span>
									<span class="datetime">2018-06-02 12:00:22</span>
									<br />
									<small class="ip_address">xxx.xxx.xxx.xxx</small>
								</td>
							</tr>
							<tr>
								<td>
									<span class="name">안주길</span>
									<span class="datetime">2018-06-02 12:00:22</span>
									<br />
									<small class="ip_address">xxx.xxx.xxx.xxx</small>
								</td>
							</tr>
							<tr>
								<td>
									<span class="name">김영희</span>
									<span class="datetime">2018-06-02 12:00:22</span>
									<br />
									<small class="ip_address">xxx.xxx.xxx.xxx</small>
								</td>
							</tr>
						</table>
					</div> -->
					<!-- <div class="col-sm-4">
						<h3 class="heading">로그인사용자기록</h3>
						<table class="table table-striped table-bordered table-hover table-condensed dashBoard">
							<tr>
								<td>
									<span class="name">이은우</span>
								</td>
								<td>
									<span class="datetime">2018-06-02 12:00:22</span>
								</td>
								<td>
									<span class="ip_address">xxx.xxx.xxx.xxx</span>
								</td>
							</tr>
							<tr>
								<td>
									<span class="name">홀길동</span>
								</td>
								<td>
									<span class="datetime">2018-06-02 12:00:22</span>
								</td>
								<td>
									<span class="ip_address">xxx.xxx.xxx.xxx</span>
								</td>
							</tr>
							<tr>
								<td>
									<span class="name">안주길</span>
								</td>
								<td>
									<span class="datetime">2018-06-02 12:00:22</span>
								</td>
								<td>
									<span class="ip_address">xxx.xxx.xxx.xxx</span>
								</td>
							</tr>
							<tr>
								<td>
									<span class="name">김영희</span>
								</td>
								<td>
									<span class="datetime">2018-06-02 12:00:22</span>
								</td>
								<td>
									<span class="ip_address">xxx.xxx.xxx.xxx</span>
								</td>
							</tr>
						</table>
					</div> -->
					<!-- <div class="col-sm-4">
						<h3 class="heading">로그인사용자기록</h3>
						<table class="table table-striped table-bordered table-hover table-condensed dashBoard">
							<tr>
								<td>
									<div class="login_log">
										<span class="name">이은우</span>
										<span class="datetime">2018-06-02 12:00:22</span>
										<small class="ip_address">xxx.xxx.xxx.xxx</small>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="login_log">
										<span class="name">홀길동</span>
										<span class="datetime">2018-06-02 12:00:22</span>
										<small class="ip_address">xxx.xxx.xxx.xxx</small>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="login_log">
										<span class="name">안주길</span>
										<span class="datetime">2018-06-02 12:00:22</span>
										<small class="ip_address">xxx.xxx.xxx.xxx</small>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div class="login_log">
										<span class="name">김영희</span>
										<span class="datetime">2018-06-02 12:00:22</span>
										<small class="ip_address">xxx.xxx.xxx.xxx</small>
									</div>
								</td>
							</tr>
						</table>
					</div> -->
					<div class="col-sm-4">
						<div class="panel panel-default dashBoard">
							<div class="panel-heading">
								<h3 class="panel-title">로그인사용자기록</h3>
							</div>
							<ul class="list-group">
								<li class="list-group-item">
									<div class="login_log">
										<span class="name">이은우</span>
										<span class="datetime">2018-06-02 12:00:22</span>
										<small class="ip_address">xxx.xxx.xxx.xxx</small>
									</div>
								</li>
								<li class="list-group-item">
									<div class="login_log">
										<span class="name">홀길동</span>
										<span class="datetime">2018-06-02 12:00:22</span>
										<small class="ip_address">xxx.xxx.xxx.xxx</small>
									</div>
								</li>
								<li class="list-group-item">
									<div class="login_log">
										<span class="name">안주길</span>
										<span class="datetime">2018-06-02 12:00:22</span>
										<small class="ip_address">xxx.xxx.xxx.xxx</small>
									</div>
								</li>
								<li class="list-group-item">
									<div class="login_log">
										<span class="name">김영희</span>
										<span class="datetime">2018-06-02 12:00:22</span>
										<small class="ip_address">xxx.xxx.xxx.xxx</small>
									</div>
								</li>
							</ul>
						</div>
					</div>
					<!-- <div class="col-sm-4">
						<h3 class="heading">상품공지사항</h3>
						<table class="table table-striped table-bordered table-hover table-condensed dashBoard">
							<tr>
								<td>2018년 메모리얼 최신</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>2018년 메모리얼 최신</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>2018년 메모리얼 최신</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>2018년 메모리얼 최신</td>
								<td>2018-05-04</td>
							</tr>
						</table>
					</div> -->
					<div class="col-sm-4">
						<div class="panel panel-default dashBoard">
							<div class="panel-heading">
								<h3 class="panel-title">상품공지사항</h3>
							</div>
							<div class="list-group">
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">2018년 메모리얼 최신</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">2018년 메모리얼 최신</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">2018년 메모리얼 최신</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">2018년 메모리얼 최신</span><span class="datetime">2018-05-04</span></div></a>
							</div>
						</div>
					</div>
					<!-- <div class="col-sm-4">
						<h3 class="heading">사내자료실</h3>
						<table class="table table-striped table-bordered table-hover table-condensed dashBoard">
							<tr>
								<td>휴가신청서</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>환불신청서</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>부모여행허가동의서</td>
								<td>2018-05-04</td>
							</tr>
							<tr>
								<td>신용카드동의서</td>
								<td>2018-05-04</td>
							</tr>
						</table>
					</div> -->
					<div class="col-sm-4">
						<div class="panel panel-default dashBoard">
							<div class="panel-heading">
								<h3 class="panel-title">사내자료실</h3>
							</div>
							<div class="list-group">
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">휴가신청서</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">환불신청서</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">부모여행허가동의서</span><span class="datetime">2018-05-04</span></div></a>
								<a href="#" class="list-group-item"><div class="boardItem"><span class="board_title">신용카드동의서</span><span class="datetime">2018-05-04</span></div></a>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<!-- <div class="col-sm-12">
						<h3 class="heading">최근예약번경사항</h3>
						<table class="table table-striped table-bordered table-hover table-condensed dashBoard">
							<tr>
								<th>예약번호</th>
								<th>상품명</th>
								<th>예약대표자</th>
								<th>총결제금액</th>
								<th>잔금</th>
								<th>출발일</th>
								<th>예약상태</th>
								<th>변경자</th>
								<th>변경일</th>
							</tr>
							<tr>
								<td>PRT201801</td>
								<td>록키[5박6일](2018년04월)</td>
								<td>이은우(2)</td>
								<td>C$1,000.00</td>
								<td>C$100.00</td>
								<td>2018-06-02</td>
								<td>
									<div class="reservattion_status">
										<div class="circle bg-success"></div>
										<div class="circle bg-success"></div>
										<div class="circle bg-danger"></div>
										<div class="circle"></div>
									</div>
								</td>
								<td>상담원1</td>
								<td>2018-05-02</td>
							</tr>
							<tr>
								<td>PRT201802</td>
								<td>캐나다동부 4박5일 [BEST]</td>
								<td>TEST(3)</td>
								<td>C$1,000.00</td>
								<td>C$0.00</td>
								<td>2018-06-02</td>
								<td>
									<div class="reservattion_status">
										<div class="circle bg-success"></div>
										<div class="circle bg-success"></div>
										<div class="circle bg-success"></div>
										<div class="circle bg-success"></div>
									</div>
								</td>
								<td>상담원1</td>
								<td>2018-04-30</td>
							</tr>
							<tr>
								<td>PRT201803</td>
								<td>토론토 시내 1일</td>
								<td>김승희(3)</td>
								<td>C$1,000.00</td>
								<td>C$100.00</td>
								<td>2018-06-02</td>
								<td>
									<div class="reservattion_status">
										<div class="circle bg-success"></div>
										<div class="circle bg-success"></div>
										<div class="circle bg-primary"></div>
										<div class="circle"></div>
									</div>
								</td>
								<td>상담원1</td>
								<td>2018-04-30</td>
							</tr>
						</table>
					</div> -->
					<div class="col-sm-12">
						<div class="panel panel-default dashBoard dashBoard-rrc">
							<div class="panel-heading">
								<h3 class="panel-title">최근예약번경사항</h3>
							</div>
							<div class="panel-body"></div>
							<table class="table table-striped table-bordered table-hover table-condensed js-recentReservationChangesTable">
								<thead>
									<tr>
										<th>예약번호</th>
										<th>상품명</th>
										<th>예약대표자</th>
										<th>총결제금액</th>
										<th>잔금</th>
										<th>출발일</th>
										<th>예약상태</th>
										<th>변경자</th>
										<th>변경일</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><a href="">PRT201801</a></td>
										<td>록키[5박6일](2018년04월)</td>
										<td>이은우(2)</td>
										<td class="text-right">C$1,000.00</td>
										<td class="text-right">C$100.00</td>
										<td class="text-center">2018-06-02</td>
										<td>
											<div class="reservattion_status">
												<div class="circle bg-success"></div>
												<div class="circle bg-success"></div>
												<div class="circle bg-danger"></div>
												<div class="circle"></div>
											</div>
										</td>
										<td>상담원1</td>
										<td class="text-center">2018-05-02</td>
									</tr>
									<tr>
										<td><a href="">PRT201802</a></td>
										<td>캐나다동부 4박5일 [BEST]</td>
										<td>TEST(3)</td>
										<td class="text-right">C$1,000.00</td>
										<td class="text-right">C$0.00</td>
										<td class="text-center">2018-06-02</td>
										<td>
											<div class="reservattion_status">
												<div class="circle bg-success"></div>
												<div class="circle bg-success"></div>
												<div class="circle bg-success"></div>
												<div class="circle bg-success"></div>
											</div>
										</td>
										<td>상담원1</td>
										<td class="text-center">2018-04-30</td>
									</tr>
									<tr>
										<td><a href="">PRT201803</a></td>
										<td>토론토 시내 1일</td>
										<td>김승희(3)</td>
										<td class="text-right">C$1,000.00</td>
										<td class="text-right">C$100.00</td>
										<td class="text-center">2018-06-02</td>
										<td>
											<div class="reservattion_status">
												<div class="circle bg-success"></div>
												<div class="circle bg-success"></div>
												<div class="circle bg-primary"></div>
												<div class="circle"></div>
											</div>
										</td>
										<td>상담원1</td>
										<td class="text-center">2018-04-30</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- <div class="row">
				</div>

				<div class="row">
				</div>

				<div class="row">
				</div>

				<div class="row">
				</div> -->

		    </div>
		</div>
	    <?php
			include "include/side_m.php"
		?>
		<!-- dashboard functions -->
		<!-- <script src="js/pages/gebo_dashboard.js"></script> -->
		<script>
			$(document).ready(function () {
				pt.initMainPage()
			})
		</script>
    </body>
</html>
