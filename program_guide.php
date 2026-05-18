<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>푸른투어 인트라넷 ERP - 프로그램 가이드</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
  body {
    font-family: 'Malgun Gothic', '맑은 고딕', sans-serif;
    background: #f5f5f5;
    color: #333;
    font-size: 14px;
  }
  /* ── 헤더 ── */
  .guide-header {
    background: linear-gradient(135deg, #1a3a5c 0%, #2e6da4 100%);
    color: #fff;
    padding: 36px 0 28px;
    margin-bottom: 30px;
    box-shadow: 0 3px 8px rgba(0,0,0,.25);
  }
  .guide-header h1 { font-size: 28px; font-weight: 700; margin: 0 0 6px; }
  .guide-header p  { font-size: 14px; margin: 0; opacity: .85; }
  .guide-header .badge-version {
    background: rgba(255,255,255,.2);
    border-radius: 4px;
    padding: 2px 10px;
    font-size: 12px;
    margin-left: 8px;
    vertical-align: middle;
  }

  /* ── 사이드바 ── */
  #sidebar {
    position: sticky;
    top: 15px;
    max-height: calc(100vh - 30px);
    overflow-y: auto;
  }
  #sidebar .nav > li > a {
    padding: 5px 12px;
    font-size: 13px;
    color: #444;
    border-left: 3px solid transparent;
    transition: all .15s;
  }
  #sidebar .nav > li.active > a,
  #sidebar .nav > li > a:hover {
    background: #eaf2fb;
    color: #2e6da4;
    border-left-color: #2e6da4;
  }
  #sidebar .nav-header {
    font-size: 11px;
    font-weight: 700;
    color: #999;
    letter-spacing: .5px;
    text-transform: uppercase;
    padding: 12px 12px 4px;
  }

  /* ── 섹션 카드 ── */
  .section-card {
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 4px rgba(0,0,0,.1);
    margin-bottom: 28px;
    overflow: hidden;
  }
  .section-card .card-header {
    background: #2e6da4;
    color: #fff;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .section-card .card-header h2 {
    font-size: 17px;
    font-weight: 700;
    margin: 0;
    color: #fff;
  }
  .section-card .card-header .num {
    background: rgba(255,255,255,.25);
    border-radius: 50%;
    width: 28px; height: 28px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700;
    flex-shrink: 0;
  }
  .section-card .card-body { padding: 20px 24px; }

  /* ── 서브 섹션 ── */
  .sub-section { margin-bottom: 22px; }
  .sub-section h3 {
    font-size: 15px; font-weight: 700;
    color: #1a3a5c;
    border-bottom: 2px solid #e0eaf5;
    padding-bottom: 6px; margin-bottom: 12px;
  }
  .sub-section h3 .fa { margin-right: 6px; color: #2e6da4; }

  /* ── 기능 테이블 ── */
  .func-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .func-table th {
    background: #e8f0f8;
    color: #1a3a5c;
    padding: 8px 12px;
    text-align: left;
    font-weight: 700;
    border-bottom: 2px solid #c5d8ee;
  }
  .func-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
  }
  .func-table tr:last-child td { border-bottom: none; }
  .func-table tr:hover td { background: #f9fbff; }
  .func-table td code {
    background: #f0f4f8; color: #c0392b;
    padding: 1px 5px; border-radius: 3px;
    font-size: 12px; white-space: nowrap;
  }

  /* ── 역할 배지 ── */
  .role-badge {
    display: inline-block;
    padding: 2px 8px; border-radius: 10px;
    font-size: 11px; font-weight: 700; margin: 1px;
  }
  .role-admin   { background:#d9534f; color:#fff; }
  .role-normal  { background:#5cb85c; color:#fff; }
  .role-guide   { background:#f0ad4e; color:#fff; }
  .role-comp    { background:#5bc0de; color:#fff; }

  /* ── 알림 박스 ── */
  .tip-box {
    background: #eaf7fb;
    border-left: 4px solid #5bc0de;
    border-radius: 0 4px 4px 0;
    padding: 10px 14px;
    margin: 12px 0;
    font-size: 13px;
  }
  .tip-box .fa { color: #5bc0de; margin-right: 6px; }
  .warn-box {
    background: #fff8e1;
    border-left: 4px solid #f0ad4e;
    border-radius: 0 4px 4px 0;
    padding: 10px 14px;
    margin: 12px 0;
    font-size: 13px;
  }
  .warn-box .fa { color: #f0ad4e; margin-right: 6px; }

  /* ── 흐름도 ── */
  .flow-steps {
    display: flex; flex-wrap: wrap;
    align-items: center; gap: 6px;
    margin: 10px 0;
  }
  .flow-step {
    background: #eaf2fb;
    border: 1px solid #c5d8ee;
    border-radius: 4px;
    padding: 5px 12px;
    font-size: 13px;
    font-weight: 600;
    color: #1a3a5c;
  }
  .flow-arrow { color: #2e6da4; font-size: 16px; font-weight: 700; }

  /* ── 반응형 ── */
  @media (max-width: 768px) {
    #sidebar { display: none; }
  }
</style>
</head>
<body data-spy="scroll" data-target="#sidebar" data-offset="60">

<!-- ═══════════════════════════════ 헤더 ═══════════════════════════════ -->
<div class="guide-header">
  <div class="container">
    <h1>
      <i class="fa fa-book"></i>
      푸른투어 인트라넷 ERP
      <span class="badge-version">프로그램 가이드 v1.0</span>
    </h1>
    <p>푸른투어(myprt.biz) 통합 업무 관리 시스템 &mdash; 예약·결제·배정·정산을 한 곳에서 관리합니다.</p>
  </div>
</div>

<div class="container">
<div class="row">

<!-- ═══════════════════════════════ 사이드바 ═══════════════════════════════ -->
<div class="col-md-2 hidden-sm hidden-xs">
  <nav id="sidebar">
    <p class="nav-header">목차</p>
    <ul class="nav nav-pills nav-stacked">
      <li><a href="#sec01">1. 시스템 개요</a></li>
      <li><a href="#sec02">2. 대시보드</a></li>
      <li><a href="#sec03">3. 예약 관리</a></li>
      <li><a href="#sec04">4. 상품 관리</a></li>
      <li><a href="#sec05">5. 인보이스 &amp; 결제</a></li>
      <li><a href="#sec06">6. 견적서</a></li>
      <li><a href="#sec07">7. 배정 관리</a></li>
      <li><a href="#sec08">8. 가이드 관리</a></li>
      <li><a href="#sec09">9. 호텔 관리</a></li>
      <li><a href="#sec10">10. 직원 관리</a></li>
      <li><a href="#sec11">11. 고객 관리</a></li>
      <li><a href="#sec12">12. 스케줄 &amp; 캘린더</a></li>
      <li><a href="#sec13">13. MIS 통계</a></li>
      <li><a href="#sec14">14. 정산 &amp; 보고서</a></li>
      <li><a href="#sec15">15. 게시판 &amp; 소통</a></li>
      <li><a href="#sec16">16. 기초 데이터 설정</a></li>
    </ul>
  </nav>
</div>

<!-- ═══════════════════════════════ 본문 ═══════════════════════════════ -->
<div class="col-md-10">


<!-- ══════════ 1. 시스템 개요 ══════════ -->
<div id="sec01" class="section-card">
  <div class="card-header">
    <span class="num">1</span>
    <h2><i class="fa fa-info-circle"></i> 시스템 개요</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-bullseye"></i> 목적 및 소개</h3>
      <p>푸른투어 인트라넷 ERP는 투어 예약부터 가이드·호텔·차량 배정, 결제·정산, 직원 근태 관리, 사내 커뮤니케이션까지 여행사 업무 전반을 통합하는 웹 기반 시스템입니다.</p>
      <table class="func-table">
        <tr><th>구분</th><th>내용</th></tr>
        <tr><td>운영 환경</td><td>Laragon (로컬), 실서버 <code>myprt.biz</code></td></tr>
        <tr><td>DB</td><td>MySQL &nbsp;|&nbsp; 서버: 98.91.65.48:3306 &nbsp;|&nbsp; DB명: prtadmindb</td></tr>
        <tr><td>타임존</td><td>America/New_York (미동부)</td></tr>
        <tr><td>Frontend</td><td>Bootstrap 3, jQuery, DataTables, CKEditor</td></tr>
        <tr><td>이메일</td><td>PHPMailer + Mailjet SMTP</td></tr>
        <tr><td>결제</td><td>Authorize.NET (신용카드)</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-sign-in"></i> 로그인 방법</h3>
      <div class="flow-steps">
        <span class="flow-step">브라우저에서 ERP URL 접속</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step">아이디·비밀번호 입력</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step">로그인 버튼 클릭</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step">대시보드 진입</span>
      </div>
      <div class="tip-box"><i class="fa fa-lightbulb-o"></i> 쿠키 기반 세션으로 인증합니다. 브라우저를 닫으면 자동 로그아웃됩니다.</div>
      <p>로그아웃은 우측 상단 사이드바의 <strong>로그아웃</strong> 버튼을 클릭합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-desktop"></i> 화면 구성</h3>
      <table class="func-table">
        <tr><th>영역</th><th>설명</th></tr>
        <tr><td><strong>상단 헤더</strong></td><td>시스템명, 빠른 메뉴(스마트등록·메모·예약검색·스케줄), 사용자 정보 표시</td></tr>
        <tr><td><strong>좌측 사이드바</strong></td><td>아코디언 방식 메뉴 트리 — 메인 카테고리 클릭 시 하위 메뉴 펼침</td></tr>
        <tr><td><strong>본문 영역</strong></td><td>각 기능 페이지가 표시되는 메인 콘텐츠 영역</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-users"></i> 사용자 역할(권한)</h3>
      <table class="func-table">
        <tr><th>역할</th><th>배지</th><th>설명 및 접근 범위</th></tr>
        <tr>
          <td>admin</td>
          <td><span class="role-badge role-admin">admin</span></td>
          <td>시스템 전체 접근 — 모든 설정·데이터 열람 및 수정 가능</td>
        </tr>
        <tr>
          <td>normal</td>
          <td><span class="role-badge role-normal">normal</span></td>
          <td>일반 직원 — 예약·배정·인보이스 등 업무 메뉴 접근</td>
        </tr>
        <tr>
          <td>guide</td>
          <td><span class="role-badge role-guide">guide</span></td>
          <td>가이드 전용 — 본인 스케줄·정산 내역 조회</td>
        </tr>
        
      </table>
    </div>

  </div>
</div><!-- /sec01 -->


<!-- ══════════ 2. 대시보드 ══════════ -->
<div id="sec02" class="section-card">
  <div class="card-header">
    <span class="num">2</span>
    <h2><i class="fa fa-home"></i> 대시보드</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-tachometer"></i> 대시보드 구성</h3>
      <p>로그인 후 처음 표시되는 홈 화면(<code>index.php</code>)으로, 주요 업무 현황을 한눈에 확인할 수 있습니다.</p>
      <table class="func-table">
        <tr><th>위젯</th><th>내용</th></tr>
        <tr><td>예약 현황</td><td>일일판매 / 주간판매 / 월간판매 통계 카드</td></tr>
        <tr><td>빠른 바로가기</td><td>예약등록, MY 예약현황, MY 수금현황 직접 이동 버튼</td></tr>
        <tr><td>게시판 바로가기</td><td>사내공지, 문의게시판, 신상품게시판, 단체문의 최신 글 목록</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-bolt"></i> 빠른 메뉴 (상단 헤더)</h3>
      <table class="func-table">
        <tr><th>버튼</th><th>이동 페이지</th><th>설명</th></tr>
        <tr><td>스마트등록</td><td><code>input_batch.php</code></td><td>예약·결제를 한 화면에서 빠르게 일괄 입력</td></tr>
        <tr><td>메모등록</td><td><code>memo_list.php</code></td><td>개인 메모 작성 및 목록 조회</td></tr>
        <tr><td>예약검색</td><td><code>total_reservation.php</code></td><td>전체 예약 통합 검색</td></tr>
        <tr><td>전체스케줄표</td><td><code>sc_local.php</code></td><td>로컬 투어 전체 스케줄 달력/표 보기</td></tr>
        <tr><td>아웃바운드스케줄표</td><td><code>sc_out.php</code></td><td>해외(아웃바운드) 투어 스케줄 보기</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-clock-o"></i> 근태 IN/OUT</h3>
      <p>사이드바 하단의 <strong>IN/OUT</strong> 버튼을 클릭하여 출퇴근을 기록합니다. 기록된 내역은 직원 관리 → 근태 관리 메뉴에서 확인할 수 있습니다.</p>
    </div>

  </div>
</div><!-- /sec02 -->


<!-- ══════════ 3. 예약 관리 ══════════ -->
<div id="sec03" class="section-card">
  <div class="card-header">
    <span class="num">3</span>
    <h2><i class="fa fa-calendar-check-o"></i> 예약 관리</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-info"></i> 개요</h3>
      <p>예약 관리는 ERP의 핵심 모듈로, 투어 예약 생성부터 수정·취소·결제 처리까지 전 과정을 지원합니다.</p>
      <div class="flow-steps">
        <span class="flow-step">상품 선택</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step">고객 정보 입력</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step">금액 계산</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step">결제 처리</span>
        <span class="flow-arrow">→</span>
        <span class="flow-step">인보이스 발송</span>
      </div>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-plus-circle"></i> 예약 등록</h3>
      <p><strong>파일:</strong> <code>base_reservation.php</code></p>
      <table class="func-table">
        <tr><th>항목</th><th>설명</th></tr>
        <tr><td>투어 날짜</td><td>달력으로 투어 출발일 선택</td></tr>
        <tr><td>상품 검색</td><td>상품명·지역·투어 유형으로 검색 후 선택</td></tr>
        <tr><td>고객 정보</td><td>성명, 연락처, 인원수(성인/아동/유아) 입력</td></tr>
        <tr><td>금액 입력</td><td>단가 자동 적용, 수동 조정 가능. C$/U$ 통화 구분</td></tr>
        <tr><td>픽업 장소</td><td>픽업 위치 선택(드롭다운)</td></tr>
        <tr><td>메모</td><td>특이사항, 요청사항 자유 입력</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-list"></i> 예약 목록 조회</h3>
      <p><strong>파일:</strong> <code>base_reservation_list.php</code></p>
      <table class="func-table">
        <tr><th>기능</th><th>설명</th></tr>
        <tr><td>기간 검색</td><td>투어 날짜 범위로 필터링</td></tr>
        <tr><td>상품·지역 필터</td><td>상품명, 지역, 투어 카테고리별 검색</td></tr>
        <tr><td>상태 필터</td><td>활성/취소/미처리 상태 구분 조회</td></tr>
        <tr><td>페이지네이션</td><td>페이지당 50건 표시</td></tr>
        <tr><td>엑셀 내보내기</td><td>검색 결과를 Excel 파일로 다운로드</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-edit"></i> 예약 수정</h3>
      <p><strong>파일:</strong> <code>base_reservation_m.php</code> (시스템 최대 파일 ~215KB)</p>
      <table class="func-table">
        <tr><th>탭/섹션</th><th>설명</th></tr>
        <tr><td>기본 정보</td><td>고객명·인원·상품·날짜·픽업 수정</td></tr>
        <tr><td>결제 처리</td><td>결제방법(신용카드/현금/수표 등) 선택 후 결제 등록</td></tr>
        <tr><td>인보이스</td><td>인보이스 생성 및 이메일 발송</td></tr>
        <tr><td>가이드 배정</td><td>해당 예약에 가이드 지정</td></tr>
        <tr><td>취소 처리</td><td>예약 취소 및 환불 처리</td></tr>
      </table>
      <div class="warn-box"><i class="fa fa-exclamation-triangle"></i> 결제 쿼리 필터: <code>payment_status NOT IN ('RRQUEST','CANCEL')</code> — 취소 내역은 자동 제외됩니다.</div>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-user"></i> MY 예약 현황</h3>
      <p><strong>파일:</strong> <code>base_reservation_mylist.php</code> — 로그인한 담당자가 처리한 예약만 표시하는 개인 현황 뷰입니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-search"></i> 통합 예약 검색</h3>
      <p><strong>파일:</strong> <code>total_reservation.php</code> — 날짜·고객명·예약번호 등 다양한 조건으로 전체 예약 데이터를 검색합니다. 상단 헤더 빠른 메뉴에서도 접근 가능합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-exclamation-circle"></i> 미처리 예약</h3>
      <p><strong>파일:</strong> <code>reserve_mis.php</code>, <code>reserve_misarea.php</code> — 결제 미완료·가이드 미배정 등 처리가 필요한 예약 목록을 확인합니다.</p>
    </div>

  </div>
</div><!-- /sec03 -->


<!-- ══════════ 4. 상품 관리 ══════════ -->
<div id="sec04" class="section-card">
  <div class="card-header">
    <span class="num">4</span>
    <h2><i class="fa fa-tags"></i> 상품 관리</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-list-alt"></i> 상품 목록</h3>
      <p><strong>파일:</strong> <code>base_product.php</code></p>
      <p>등록된 모든 투어 상품을 조회합니다. 상품명·지역·투어 유형으로 검색 가능하며, 클릭하면 수정 화면으로 이동합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-plus"></i> 상품 등록 / 수정</h3>
      <p><strong>파일:</strong> <code>base_product_m.php</code></p>
      <table class="func-table">
        <tr><th>항목</th><th>설명</th></tr>
        <tr><td>상품명 (한/영)</td><td>한국어·영문 상품명 입력</td></tr>
        <tr><td>투어 유형</td><td>로컬/패키지/이벤트 등 코드 분류</td></tr>
        <tr><td>지역</td><td>지역 코드 선택(토론토, 나이아가라 등)</td></tr>
        <tr><td>기본 단가</td><td>성인/아동/유아 기본 요금 설정</td></tr>
        <tr><td>소요 시간</td><td>투어 기간(시간/일)</td></tr>
        <tr><td>상품 이미지</td><td>대표 이미지 업로드</td></tr>
        <tr><td>상품 설명</td><td>CKEditor로 상세 내용 입력</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-sliders"></i> 옵션 관리</h3>
      <p><strong>파일:</strong> <code>base_opt.php</code>, <code>base_opt_m.php</code></p>
      <p>상품에 부가 옵션(선택 서비스, 입장권 등)을 추가합니다. 옵션별 추가 금액을 설정하며 예약 시 선택 가능합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-music"></i> 뮤지컬 / 이벤트 상품</h3>
      <p><strong>파일:</strong> <code>musical_regi.php</code>, <code>search_musical.php</code>, <code>search_musical_detail.php</code></p>
      <p>뮤지컬·공연·스포츠 이벤트 등 특수 상품을 별도 관리합니다. API 연동을 통해 외부 티켓 정보를 가져올 수 있습니다.</p>
    </div>

  </div>
</div><!-- /sec04 -->


<!-- ══════════ 5. 인보이스 & 결제 ══════════ -->
<div id="sec05" class="section-card">
  <div class="card-header">
    <span class="num">5</span>
    <h2><i class="fa fa-file-text-o"></i> 인보이스 &amp; 결제</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-info"></i> 개요</h3>
      <p>예약에 대한 청구서(인보이스)를 생성하고, 다양한 결제 수단을 통해 결제를 처리합니다. 인보이스는 이메일로 고객에게 발송하거나 PDF로 출력할 수 있습니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-file-o"></i> 인보이스 생성 / 수정</h3>
      <table class="func-table">
        <tr><th>파일</th><th>용도</th></tr>
        <tr><td><code>invoice_m.php</code></td><td>일반 투어 인보이스 생성·수정</td></tr>
        <tr><td><code>invoice_m2.php</code></td><td>대체 인보이스 수정 인터페이스</td></tr>
        <tr><td><code>invoice_page.php</code></td><td>일반 투어 인보이스 미리보기·출력</td></tr>
        <tr><td><code>invoice_p.php</code></td><td>패키지 투어 인보이스 처리</td></tr>
        <tr><td><code>invoice_hpage.php</code></td><td>호텔 예약 인보이스 + 이메일 발송</td></tr>
      </table>
      <div class="tip-box"><i class="fa fa-lightbulb-o"></i> 인보이스 파일 계열은 <code>invoice_page.php</code>(일반)를 기준으로 구조가 동기화되어 있습니다.</div>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-credit-card"></i> 결제 처리</h3>
      <table class="func-table">
        <tr><th>결제 수단</th><th>파일</th><th>설명</th></tr>
        <tr><td>신용카드</td><td><code>invoice_cc.php</code></td><td>Authorize.NET 게이트웨이 연동 카드 결제</td></tr>
        <tr><td>현금</td><td><code>pu_cash.php</code></td><td>현금 결제 직접 등록</td></tr>
        <tr><td>기타</td><td><code>base_reservation_m.php</code> 내 결제 모달</td><td>수표·이체·기타 결제 방법 선택</td></tr>
      </table>
      <p><strong>결제 모달 구조:</strong></p>
      <table class="func-table">
        <tr><th>섹션</th><th>표시 조건</th><th>주요 필드</th></tr>
        <tr><td>결제방법 선택</td><td>항상 표시</td><td>결제방법 드롭다운(#paymentmethod)</td></tr>
        <tr><td>신용카드 섹션</td><td>creditcard 선택 시</td><td>카드정보, 환율(buyrate1), 결제금액(lastamt), 환율결제금액(clastpayamt)</td></tr>
        <tr><td>기타 결제 섹션</td><td>그 외 선택 시</td><td>결제통화(currencytype), 환율(buyrate/sellrate), 결제금액(rpay), 환율결제금액(lastpayamt)</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-history"></i> 결제 내역 조회</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>account_pay_list.php</code></td><td>계정별 결제 내역 및 잔액 현황</td></tr>
        <tr><td><code>payment_history.php</code></td><td>전체 결제 이력 조회</td></tr>
        <tr><td><code>pay_hist.php</code></td><td>결제 이력 요약 보기</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-refresh"></i> 결제 정산 / 취소</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>paysettle.php</code></td><td>결제 정산 및 장부 마감 처리</td></tr>
        <tr><td><code>reset_pay.php</code></td><td>결제 취소·환불 처리</td></tr>
        <tr><td><code>miss_totamt.php</code></td><td>미수금·누락 금액 확인</td></tr>
      </table>
    </div>

  </div>
</div><!-- /sec05 -->


<!-- ══════════ 6. 견적서 ══════════ -->
<div id="sec06" class="section-card">
  <div class="card-header">
    <span class="num">6</span>
    <h2><i class="fa fa-calculator"></i> 견적서 관리</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-info"></i> 개요</h3>
      <p>고객 문의에 따른 투어 비용 견적서를 작성하고 이메일로 발송합니다. 견적 승인 시 예약으로 전환합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-pencil"></i> 견적 작성</h3>
      <p><strong>파일:</strong> <code>estimate_form.php</code></p>
      <table class="func-table">
        <tr><th>항목</th><th>설명</th></tr>
        <tr><td>고객 정보</td><td>고객명·연락처·여행 인원</td></tr>
        <tr><td>투어 항목</td><td>상품 선택, 일정, 인원별 단가</td></tr>
        <tr><td>비용 구분</td><td>가이드비·호텔비·교통비·입장료 등 항목별 분류</td></tr>
        <tr><td>할인/쿠폰</td><td>쿠폰 코드 적용, 할인금액 반영</td></tr>
        <tr><td>총액</td><td>C$/U$ 통화 기준 자동 합계</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-list"></i> 견적 목록 / 상세</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>estimate_list.php</code></td><td>작성된 견적 목록 조회·검색</td></tr>
        <tr><td><code>estimate_view.php</code></td><td>견적 상세 내용 보기</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-download"></i> 내보내기</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>estimate_excel.php</code></td><td>견적서 Excel 다운로드</td></tr>
        <tr><td><code>estimate_export_breakdown.php</code></td><td>비용 항목 상세 분류 내보내기</td></tr>
        <tr><td><code>send_breakdown_email.php</code></td><td>비용 분류표 이메일 발송</td></tr>
      </table>
    </div>

  </div>
</div><!-- /sec06 -->


<!-- ══════════ 7. 배정 관리 ══════════ -->
<div id="sec07" class="section-card">
  <div class="card-header">
    <span class="num">7</span>
    <h2><i class="fa fa-sitemap"></i> 배정 관리</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-info"></i> 개요</h3>
      <p>예약된 투어에 가이드·호텔·차량을 배정하는 리소스 관리 모듈입니다. 각 리소스의 가용 상태를 확인 후 배정합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-user-circle"></i> 가이드 배정</h3>
      <p><strong>파일:</strong> <code>guide_assign_m.php</code>, <code>guide_assign_customer*.php</code></p>
      <table class="func-table">
        <tr><th>기능</th><th>설명</th></tr>
        <tr><td>가이드 가용 확인</td><td>선택 날짜의 가이드 차단 여부·기존 배정 현황 확인</td></tr>
        <tr><td>가이드 선택</td><td>적합한 가이드 검색 후 예약에 배정</td></tr>
        <tr><td>실시간 현황</td><td><code>guide_assign_current_state.php</code>로 현재 배정 상태 모니터링</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-building"></i> 호텔 배정</h3>
      <p><strong>파일:</strong> <code>hotel_assign_m.php</code>, <code>hotel_assign_mn.php</code></p>
      <p>투어 숙박에 사용할 호텔·객실을 예약에 연결합니다. 객실 재고 및 잔여 수를 확인하여 배정합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-bus"></i> 차량 배정</h3>
      <p><strong>파일:</strong> <code>car_assign_m.php</code>, <code>vehicle_assignment.php</code></p>
      <p>투어 운행에 필요한 차량(버스/밴)을 예약에 배정합니다. 차량 차단 관리는 <code>car_block_manage.php</code>에서 처리합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-calendar"></i> 이벤트 배정</h3>
      <p><strong>파일:</strong> <code>event_reservation_list.php</code>, <code>event_guide_list.php</code>, <code>event_hotel_list.php</code>, <code>event_car_list.php</code></p>
      <p>특수 이벤트 투어에 대한 가이드·호텔·차량을 통합 배정합니다.</p>
    </div>

  </div>
</div><!-- /sec07 -->


<!-- ══════════ 8. 가이드 관리 ══════════ -->
<div id="sec08" class="section-card">
  <div class="card-header">
    <span class="num">8</span>
    <h2><i class="fa fa-id-badge"></i> 가이드 관리</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-database"></i> 가이드 마스터</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>base_guide.php</code></td><td>전체 가이드 목록 조회</td></tr>
        <tr><td><code>base_guide_m.php</code></td><td>가이드 등록·수정 (이름·연락처·자격증·경력 등)</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-calendar"></i> 가이드 캘린더</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>guide_cal_m.php</code></td><td>가이드 배정 스케줄 달력 보기 (관리자용)</td></tr>
        <tr><td><code>guide_cal_my.php</code></td><td>가이드 본인 스케줄 확인 (가이드용)</td></tr>
        <tr><td><code>guide_cal_m_print.php</code></td><td>가이드 캘린더 인쇄용 출력</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-ban"></i> 차단 날짜 관리</h3>
      <p><strong>파일:</strong> <code>guide_block.php</code></p>
      <p>가이드가 근무 불가능한 날짜를 등록합니다. 차단된 날짜에는 배정 시 경고가 표시됩니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-money"></i> 가이드 정산</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>guide_settle.php</code></td><td>전체 가이드 수당 정산 (관리자용)</td></tr>
        <tr><td><code>guide_mysettle.php</code></td><td>가이드 본인 정산 내역 조회</td></tr>
        <tr><td><code>guide_settle_prt.php</code></td><td>가이드 정산서 인쇄 출력</td></tr>
      </table>
    </div>

  </div>
</div><!-- /sec08 -->


<!-- ══════════ 9. 호텔 관리 ══════════ -->
<div id="sec09" class="section-card">
  <div class="card-header">
    <span class="num">9</span>
    <h2><i class="fa fa-hotel"></i> 호텔 관리</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-database"></i> 호텔 마스터</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>hotel_regi.php</code></td><td>전체 호텔 목록 조회</td></tr>
        <tr><td><code>hotel_regi_m.php</code></td><td>호텔 등록·수정 (이름·위치·객실 유형·계약 단가)</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-calendar"></i> 호텔 캘린더 (재고 관리)</h3>
      <p><strong>파일:</strong> <code>hotel_cal2.php</code></p>
      <p>날짜별 객실 재고 현황을 달력 형태로 관리합니다. 잔여 객실 수를 실시간으로 확인할 수 있습니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-money"></i> 호텔 정산</h3>
      <p><strong>파일:</strong> <code>hotel_settle.php</code></p>
      <p>호텔 이용 건수 및 요금을 집계하여 호텔사에 지급할 정산 내역을 생성합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-bar-chart"></i> 호텔 통계</h3>
      <p><strong>파일:</strong> <code>hotel_stat1.php</code> — 기간별 호텔 이용률, 매출, 예약 건수 통계를 제공합니다.</p>
    </div>

  </div>
</div><!-- /sec09 -->


<!-- ══════════ 10. 직원 관리 ══════════ -->
<div id="sec10" class="section-card">
  <div class="card-header">
    <span class="num">10</span>
    <h2><i class="fa fa-users"></i> 직원 관리</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-address-card"></i> 직원 마스터</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>emp_list.php</code></td><td>전체 직원 목록 조회·검색</td></tr>
        <tr><td><code>emp_m.php</code></td><td>직원 등록·수정 (이름·부서·역할·연락처·계정)</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-umbrella-beach"></i> 휴가 관리</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>emp_vlist.php</code></td><td>전체 직원 휴가 일정 목록</td></tr>
        <tr><td><code>emp_vm.php</code></td><td>휴가 신청·승인·수정</td></tr>
        <tr><td><code>emp_vmlist.php</code></td><td>직원별 휴가 현황 뷰</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-clock-o"></i> 근태 관리</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>inout_regi.php</code></td><td>출퇴근 시간 수동 등록</td></tr>
        <tr><td><code>inout_time.php</code></td><td>출퇴근 기록 조회·통계</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-calendar"></i> 직원 캘린더</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>employee_cal_list.php</code></td><td>전체 직원 업무 일정 달력</td></tr>
        <tr><td><code>employee_cal_mylist.php</code></td><td>본인 업무 일정 및 수금 현황</td></tr>
        <tr><td><code>employee_tour_list.php</code></td><td>직원별 배정된 투어 목록</td></tr>
      </table>
    </div>

  </div>
</div><!-- /sec10 -->


<!-- ══════════ 11. 고객 관리 ══════════ -->
<div id="sec11" class="section-card">
  <div class="card-header">
    <span class="num">11</span>
    <h2><i class="fa fa-address-book"></i> 고객 관리</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-database"></i> 고객 데이터베이스</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>client_list.php</code></td><td>전체 고객 목록 조회·검색</td></tr>
        <tr><td><code>cli_m.php</code></td><td>고객 등록·수정 (이름·연락처·이메일·국적·특이사항)</td></tr>
        <tr><td><code>short_customer.php</code></td><td>고객 빠른 검색 팝업 (예약 등록 시 사용)</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-print"></i> 고객 정보 출력</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>print_customer.php</code></td><td>고객 정보 인쇄</td></tr>
        <tr><td><code>print_customer2.php</code></td><td>대체 인쇄 양식</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-comments"></i> 상담 관리</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>base_consult.php</code></td><td>고객 상담 유형 코드 관리</td></tr>
        <tr><td><code>base_conslut_list.php</code></td><td>상담 내역 목록</td></tr>
        <tr><td><code>base_conslut_m.php</code></td><td>상담 등록·수정</td></tr>
        <tr><td><code>base_conslut_mylist.php</code></td><td>본인 담당 상담 목록</td></tr>
      </table>
    </div>

  </div>
</div><!-- /sec11 -->


<!-- ══════════ 12. 스케줄 & 캘린더 ══════════ -->
<div id="sec12" class="section-card">
  <div class="card-header">
    <span class="num">12</span>
    <h2><i class="fa fa-calendar"></i> 스케줄 &amp; 캘린더</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-table"></i> 투어 스케줄 표</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>sc_local.php</code></td><td>로컬(국내) 투어 전체 스케줄표 — 날짜별 투어·배정 현황</td></tr>
        <tr><td><code>sc_local1.php</code></td><td>로컬 스케줄 대체 뷰</td></tr>
        <tr><td><code>sc_out.php</code></td><td>아웃바운드(해외) 투어 스케줄표</td></tr>
        <tr><td><code>schedule_monthly.php</code></td><td>월별 투어 일정 개요</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-handshake-o"></i> 협력사 캘린더</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>cooperation_cal_list.php</code></td><td>협력사 일정 통합 달력</td></tr>
        <tr><td><code>cooperation_cal_list*.php</code> (4종)</td><td>각 협력사별 캘린더 뷰 변형</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-cogs"></i> 배치 스케줄</h3>
      <p><strong>파일:</strong> <code>batch_cal.php</code> — 여러 예약을 선택하여 가이드·호텔·차량을 일괄 배정하는 배치 작업 화면입니다.</p>
    </div>

  </div>
</div><!-- /sec12 -->


<!-- ══════════ 13. MIS 통계 ══════════ -->
<div id="sec13" class="section-card">
  <div class="card-header">
    <span class="num">13</span>
    <h2><i class="fa fa-line-chart"></i> MIS 통계</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-info"></i> 개요</h3>
      <p>예약 데이터를 다양한 기준(요일·지역)으로 집계·분석하는 경영정보시스템(MIS) 통계 메뉴입니다. 기간을 선택하면 요약 카드, 집계 테이블, 상세 데이터를 한 화면에서 확인하고 Excel·프린트로 내보낼 수 있습니다.</p>
      <div class="tip-box"><i class="fa fa-lightbulb-o"></i> 두 화면 모두 <strong>요약 카드 → 집계 테이블 → 상세 테이블</strong> 3단 구조로 구성되어 있습니다.</div>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-calendar-o"></i> 요일별 예약 매출</h3>
      <p><strong>파일:</strong> <code>reserve_mis.php</code></p>
      <table class="func-table">
        <tr><th>항목</th><th>설명</th></tr>
        <tr><td>검색 조건</td><td>조회 기간(예약일 기준) 선택</td></tr>
        <tr><td>요약 카드</td><td>총 건수 / 총 예약인원 / 총 예약금액 / 잔액(발란스)</td></tr>
        <tr><td>요일별 집계</td><td>일~토 7행 테이블 — 건수·인원·금액·비율 막대 표시. 일요일(빨강)·토요일(파랑) 색상 구분</td></tr>
        <tr><td>상세 테이블</td><td>요일 그룹 헤더 → 예약 행(순번·요일·예약일·예약타입·예약경로·상품·인원·금액·잔액) → 요일 소계 → 총합계</td></tr>
        <tr><td>내보내기</td><td>프린트 / 엑셀(<code>.xls</code>) 버튼</td></tr>
      </table>
      <p><strong>컬럼 설명:</strong></p>
      <table class="func-table">
        <tr><th>컬럼</th><th>데이터 출처</th></tr>
        <tr><td>요일</td><td>MySQL <code>DAYOFWEEK(revDate)</code> → 한국어 변환</td></tr>
        <tr><td>예약타입</td><td><code>tour_type</code> (1=직접예약, 2=인터넷예약, 4=업체예약)</td></tr>
        <tr><td>예약경로</td><td><code>r_path</code> 코드 → <code>codebaseName()</code>으로 명칭 변환</td></tr>
        <tr><td>예약금액</td><td><code>SUM(last_total)</code> per (날짜 + 상품)</td></tr>
        <tr><td>잔액</td><td><code>SUM(last_bal)</code> — 미수금액</td></tr>
      </table>
      <div class="warn-box"><i class="fa fa-exclamation-triangle"></i> <code>rev_status NOT IN ('READY','CANCEL')</code> 조건으로 대기·취소 건은 제외됩니다.</div>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-map-marker"></i> 지역별 예약 매출</h3>
      <p><strong>파일:</strong> <code>reserve_misarea.php</code></p>
      <table class="func-table">
        <tr><th>항목</th><th>설명</th></tr>
        <tr><td>검색 조건</td><td>날짜 기준 선택(<strong>예약일</strong> 또는 <strong>출발일</strong>) + 기간</td></tr>
        <tr><td>요약 카드</td><td>총 건수 / 총 예약인원 / 총 예약금액 / 잔액</td></tr>
        <tr><td>지역별 집계</td><td>지역 코드별 건수·인원·금액·비율 막대 표시</td></tr>
        <tr><td>상세 테이블</td><td>지역 그룹 헤더 → 예약 행(지역·예약타입·경로·날짜·상품·인원·금액·잔액) → 지역 소계 → 총합계</td></tr>
        <tr><td>내보내기</td><td>프린트 / 엑셀(<code>.xls</code>) 버튼</td></tr>
      </table>
      <div class="tip-box"><i class="fa fa-lightbulb-o"></i> <strong>예약일</strong> 기준은 예약이 등록된 날짜, <strong>출발일</strong> 기준은 실제 투어 시작일로 조회합니다. 영업 실적 분석에는 예약일, 운영 준비 현황 확인에는 출발일을 사용하세요.</div>
    </div>

  </div>
</div><!-- /sec13 -->


<!-- ══════════ 14. 정산 & 보고서 ══════════ -->
<div id="sec14" class="section-card">
  <div class="card-header">
    <span class="num">14</span>
    <h2><i class="fa fa-bar-chart"></i> 정산 &amp; 보고서</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-list-ol"></i> 일일 처리 현황</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>allprocess.php</code></td><td>전체 트랜잭션 일별 처리 현황 보고서</td></tr>
        <tr><td><code>alleveprocess.php</code></td><td>저녁 투어 처리 현황</td></tr>
        <tr><td><code>allmyprocess.php</code></td><td>본인 담당 트랜잭션 내역</td></tr>
        <tr><td><code>alltotprocess.php</code></td><td>전체 합계 처리 현황 요약</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-calculator"></i> 정산 통계</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>settle_stats.php</code></td><td>수입·지출 정산 통계 및 기간별 집계</td></tr>
        <tr><td><code>hotel_stat1.php</code></td><td>호텔 이용 통계</td></tr>
        <tr><td><code>guide_assign_current_state.php</code></td><td>가이드 배정 현황 리포트</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-archive"></i> 아카이브 보고서</h3>
      <p><strong>파일:</strong> <code>arc_rpt.php</code> — 과거 정산 데이터 및 기록 보관 보고서를 조회합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-print"></i> 바우처 출력</h3>
      <p><strong>파일:</strong> <code>print_voucher.php</code> — 고객에게 제공할 투어 바우처를 인쇄합니다.</p>
    </div>

  </div>
</div><!-- /sec14 -->


<!-- ══════════ 15. 게시판 & 커뮤니케이션 ══════════ -->
<div id="sec15" class="section-card">
  <div class="card-header">
    <span class="num">15</span>
    <h2><i class="fa fa-comments"></i> 게시판 &amp; 커뮤니케이션</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-bullhorn"></i> 게시판</h3>
      <table class="func-table">
        <tr><th>파일</th><th>게시판 종류</th><th>설명</th></tr>
        <tr><td><code>board_list.php?sub=15</code></td><td>사내 공지</td><td>전 직원 대상 공지사항</td></tr>
        <tr><td><code>board_list.php?sub=10</code></td><td>문의 게시판</td><td>고객·내부 문의 접수</td></tr>
        <tr><td><code>board_list.php?sub=28</code></td><td>신상품 게시판</td><td>신규 투어 상품 공유</td></tr>
        <tr><td><code>board_list.php?sub=35</code></td><td>단체 문의</td><td>단체 예약 요청 접수</td></tr>
      </table>
      <table class="func-table" style="margin-top:8px">
        <tr><th>파일</th><th>기능</th></tr>
        <tr><td><code>board_write.php</code></td><td>게시글 작성 (CKEditor 지원)</td></tr>
        <tr><td><code>board_view.php</code></td><td>게시글 상세 보기</td></tr>
        <tr><td><code>board_modify.php</code></td><td>게시글 수정</td></tr>
        <tr><td><code>board_reply.php</code></td><td>댓글 작성</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-sticky-note"></i> 메모 시스템</h3>
      <p><strong>파일:</strong> <code>memo_list.php</code> — 개인 메모 작성 및 목록 관리. 상단 빠른 메뉴에서 바로 접근 가능합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-envelope"></i> 뉴스레터 / 이메일</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>newsletter_main.php</code></td><td>뉴스레터 작성 및 발송 관리</td></tr>
        <tr><td><code>mailing_list.php</code></td><td>이메일 수신 목록 관리</td></tr>
        <tr><td><code>send_news.php</code></td><td>뉴스 이메일 일괄 발송</td></tr>
        <tr><td><code>mailjet_webhook_receiver.php</code></td><td>Mailjet 발송 상태 웹훅 수신</td></tr>
      </table>
      <div class="tip-box"><i class="fa fa-lightbulb-o"></i> 이메일 발송은 Mailjet SMTP (<code>in-v3.mailjet.com:587</code>)를 사용합니다. 스팸 방지를 위해 단축 URL 사용을 금지합니다.</div>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-comment"></i> 내부 메신저</h3>
      <p><strong>파일:</strong> <code>messenger.php</code> — 직원 간 실시간 메시지 전송 기능을 제공합니다.</p>
    </div>

  </div>
</div><!-- /sec15 -->


<!-- ══════════ 16. 기초 데이터 설정 ══════════ -->
<div id="sec16" class="section-card">
  <div class="card-header">
    <span class="num">16</span>
    <h2><i class="fa fa-cog"></i> 기초 데이터 설정</h2>
  </div>
  <div class="card-body">

    <div class="sub-section">
      <h3><i class="fa fa-code"></i> 코드 관리</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>base_code.php</code></td><td>시스템 분류 코드 목록 (지역·유형·상태 등)</td></tr>
        <tr><td><code>base_code_edit.php</code></td><td>코드 추가·수정·삭제</td></tr>
      </table>
      <p>예약·상품·결제에서 사용하는 모든 분류 코드를 여기서 관리합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-briefcase"></i> 에이전트 / 파트너 관리</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>base_agent.php</code></td><td>여행사 파트너·에이전트 목록</td></tr>
        <tr><td><code>base_agent_m.php</code></td><td>에이전트 등록·수정 (회사명·담당자·수수료율)</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-map-marker"></i> 픽업 장소 관리</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>base_pick.php</code></td><td>픽업 장소 목록</td></tr>
        <tr><td><code>base_pick_m.php</code></td><td>픽업 장소 등록·수정 (장소명·주소·픽업 시간)</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-ticket"></i> 쿠폰 관리</h3>
      <p><strong>파일:</strong> <code>base_coupons.php</code> — 할인 쿠폰 코드 생성 및 관리. 유효 기간·할인율·적용 상품을 설정합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-bus"></i> 버스 / 차량 업체</h3>
      <table class="func-table">
        <tr><th>파일</th><th>설명</th></tr>
        <tr><td><code>base_bus.php</code></td><td>차량 공급 업체 목록</td></tr>
        <tr><td><code>base_bus_m.php</code></td><td>업체 등록·수정 (회사명·차종·단가·연락처)</td></tr>
      </table>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-puzzle-piece"></i> 스케줄 템플릿</h3>
      <p><strong>파일:</strong> <code>base_sc.php</code> — 반복되는 투어 스케줄 패턴을 템플릿으로 저장하여 일괄 생성에 활용합니다.</p>
    </div>

    <div class="sub-section">
      <h3><i class="fa fa-key"></i> 비밀번호 변경</h3>
      <p><strong>파일:</strong> <code>change_pass.php</code> — 로그인 계정의 비밀번호를 변경합니다.</p>
    </div>

  </div>
</div><!-- /sec16 -->


<!-- ══════════ 하단 ══════════ -->
<div class="section-card">
  <div class="card-header" style="background:#555;">
    <h2 style="font-size:15px;"><i class="fa fa-question-circle"></i> 기술 지원 및 참고</h2>
  </div>
  <div class="card-body">
    <table class="func-table">
      <tr><th>항목</th><th>내용</th></tr>
      <tr><td>주요 함수 라이브러리</td><td><code>include/paran_func.php</code>, <code>include/func_list.php</code></td></tr>
      <tr><td>DB 연결</td><td><code>include/dbconn.php</code> (mysql_* 레거시, php_compat.php로 mysqli 래핑)</td></tr>
      <tr><td>공통 헤더</td><td><code>include/header.php</code></td></tr>
      <tr><td>이메일 발송 함수</td><td><code>mailsend_a($to, $subj, $contents, $attach1, $attach2)</code></td></tr>
      <tr><td>통화 구분</td><td><code>$sign</code> <code>U$</code> (USD)</td></tr>
      <tr><td>날짜 선택기</td><td>bootstrap-datepicker 1.6.4 (<code>admin/lib/bootstrap-datepicker-1.6.4-dist/</code>)</td></tr>
      <tr><td><strong>기술 지원</strong></td><td><strong>이은우</strong> &nbsp;<a href="mailto:wincom00@gmail.com">wincom00@gmail.com</a></td></tr>
    </table>
    <p style="margin-top:14px;color:#888;font-size:12px;text-align:right;">
      푸른투어 인트라넷 ERP 프로그램 가이드 &mdash; 작성일: <?php echo date('Y-m-d'); ?> &mdash; v1.0
    </p>
  </div>
</div>

</div><!-- /col-md-10 -->
</div><!-- /row -->
</div><!-- /container -->

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
$(function(){
  // Scrollspy
  $('body').scrollspy({ target: '#sidebar', offset: 80 });

  // Smooth scroll
  $('#sidebar a').on('click', function(e){
    e.preventDefault();
    var target = $(this).attr('href');
    $('html, body').animate({ scrollTop: $(target).offset().top - 20 }, 350);
  });
});
</script>
</body>
</html>