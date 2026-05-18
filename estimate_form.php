<?php
/* ========== 상단 공통 include는 DB연결 ========== */
require_once 'include/header.php';
require_once 'include/side_m.php';

/* ====== 초기값 ====== */
$today  = date('Y-m-d');
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$master = null;
$items  = array();

if ($id) {
  $m = mysql_query("SELECT * FROM estimate_master WHERE id=" . (int)$id);
  if ($m && mysql_num_rows($m)) $master = mysql_fetch_assoc($m);
  $q = mysql_query("SELECT * FROM estimate_items WHERE estimate_id=" . (int)$id . " ORDER BY section,id");
  if ($q) {
    while ($row = mysql_fetch_assoc($q)) $items[] = $row;
  }
}

/* ====== 호텔 옵션 ====== */
$hotelOptionsHtml = '';
$q = mysql_query("
  SELECT seq_no, TRIM(h_name) AS hotel_name
  FROM product_hotel
  WHERE 1=1
  ORDER BY h_name
");
if ($q) {
  while ($row = mysql_fetch_assoc($q)) {
    $name  = isset($row['hotel_name']) ? $row['hotel_name'] : '';
    $label = $name . ' 또는 동급호텔';
    $hotelOptionsHtml .= '<option value="' . (int)$row['seq_no'] . '" data-name="' .
                         htmlspecialchars($name, ENT_QUOTES) . '">' .
                         htmlspecialchars($label, ENT_QUOTES) . '</option>';
  }
}

/* ====== 입장권(코드베이스) 옵션 ====== */
$entranceOptionsHtml = '';
$sql = "
  SELECT lvcode1, lvcode2, lvcode3, lvcode4, lvcode5,
         TRIM(NULLIF(comment, '')) AS txt
  FROM code_base
  WHERE lvcode1 = 'G01' AND lvcode2 <> '00' AND active = 'yes'
  ORDER BY txt ASC, lvcode2 ASC, lvcode3 ASC
  LIMIT 2000
";
$rs = mysql_query($sql);
if ($rs) {
  while ($row = mysql_fetch_assoc($rs)) {
    $value = $row['lvcode1'].'-'.$row['lvcode2'].'-'.$row['lvcode3'].'-'.$row['lvcode4'].'-'.$row['lvcode5'];
    $text  = $row['txt'] ? $row['txt'] : $value;
    $entranceOptionsHtml .= '<option value="'.
      htmlspecialchars($value, ENT_QUOTES).'" data-name="'.
      htmlspecialchars($text,  ENT_QUOTES).'">'.
      htmlspecialchars($text,  ENT_QUOTES).'</option>';
  }
}
?>
<script>
/* 서버 데이터 → JS로 전달 */
window.EST_DATA = <?=json_encode(array('master'=>$master,'items'=>$items), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
window.HOTEL_OPTIONS_HTML    = '<?= str_replace(array("\n","\r","'"), array("","","\\'"), $hotelOptionsHtml) ?>';
window.ENTRANCE_OPTIONS_HTML = '<?= str_replace(array("\n","\r","'"), array("","","\\'"), $entranceOptionsHtml) ?>';
</script>

<style>
:root{ --bg:#fff; --ink:#161a1d; --muted:#667085; --line:#e5e7eb; --key:#0b5bd3; --soft:#f5f7fb; --warn:#fff2b2;}
.erp-wrap{max-width:1280px;margin:24px auto 80px;padding:0 16px;color:var(--ink);font:14px/1.6 system-ui,Segoe UI,Apple SD Gothic Neo,Malgun Gothic,sans-serif}
.erp-h1{font-size:22px;font-weight:800;margin:10px 0 18px;letter-spacing:.2px}
.grid{display:grid;gap:8px}
.g2{grid-template-columns:repeat(2,1fr)}
.g4{grid-template-columns:repeat(4,1fr)}
.card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.card .hd{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid var(--line);background:var(--soft);border-radius:12px 12px 0 0}
.card .hd h3{margin:0;font-size:15px;font-weight:700}
.pad{padding:14px}
.tbl{width:100%;border-collapse:collapse}
.tbl th,.tbl td{border:1px solid var(--line);padding:8px 10px;vertical-align:middle}
.tbl th{background:#fafafa;font-weight:700}
.tbl tfoot th,.tbl tfoot td{background:#f7fbff;font-weight:800}
.num{text-align:right}
.ctl{display:flex;gap:6px;align-items:center}
.btn{height:32px;padding:0 10px;border:1px solid var(--line);border-radius:8px;background:#fff;cursor:pointer}
.btn.small{height:28px;font-size:12px}
.btn.key{border-color:var(--key);color:#fff;background:var(--key)}
.btn.warn{background:#ffe37e;border-color:#ffdf6b}
.ipt, .sel, .date{width:100%;height:32px;border:1px solid var(--line);border-radius:8px;padding:0 10px;background:#fff}
.ipt.num{text-align:right}
.sumbar{display:flex;justify-content:flex-end;gap:24px;padding:10px 14px;background:#f0f6ff;border:1px dashed #cfe0ff;border-radius:10px;margin-top:8px}
.pill{padding:6px 10px;border-radius:999px;background:#eef2ff;font-weight:700}
.yellow{background:var(--warn)}
.note{font-size:12px;color:var(--muted)}
.tbl td .chosen-container .chosen-single{height:32px;line-height:30px;border:1px solid var(--line);border-radius:8px;}
.chosen-container{min-width:220px}

/* ★ 오버타임 날짜칸(금액+사유) UI */
.ot-cell{display:flex;flex-direction:column;gap:6px}
.ot-reason{height:32px}

/* ====== PRINT ====== */
@media print {
  html, body { background:#fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  @page { size: A4 portrait; margin: 12mm; }
  #sidebar,.sidebar,.left_nav,.left_menu,.side_m,#side_m,
  header,.header,.navbar,.topbar,#jCrumbs,.breadCrumb,
  .no-print,.ctl,.btn,button,a.btn { display:none !important; }
  [class*="fixed"],[class*="sticky"],header,.navbar,.side_m,#sidebar,.sidebar {
    position:static !important; inset:auto !important; float:none !important;
  }
  #contentwrapper,.reservationDetailForm,.main_content,.erp-wrap { width:100% !important; max-width:100% !important; margin:0 !important; padding:0 !important; }
  .card,.pad,.tbl,.tbl tr { break-inside:avoid !important; page-break-inside:avoid !important; }
  .tbl thead{ display:table-header-group !important; }
  .tbl tfoot{ display:table-footer-group !important; }
  input,select,textarea { background:transparent !important; box-shadow:none !important; }
  .ipt,.date,.sel { border-color:#ddd !important; }
}
</style>

<div id="contentwrapper" class="reservationDetailForm">
  <div class="main_content">
    <div id="jCrumbs" class="breadCrumb module">
      <ul>
        <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
        <li><a href="#">예약관리</a></li>
        <li>업체/맞춤여행견적</li>
      </ul>
    </div>

    <div class="erp-wrap">
      <div class="erp-h1">BREAKDOWN QUOTATION</div>

      <!-- ===== 헤더 ===== -->
      <div class="card">
        <div class="hd"><div class="note">* TO / 인원 / FOC / 총인원 / GROUP NAME / 작성일</div></div>
        <div class="pad grid g4">
          <label>TO <input type="text" class="ipt" id="to_name" placeholder="거래처/담당"></label>
          <label>인원 <input type="number" class="ipt num" id="pax" value="20" min="1"></label>
          <label>FOC <input type="number" class="ipt num" id="foc" value="0" min="0"></label>
          <label>총인원 (자동) <input type="text" class="ipt num" id="total_pax" readonly></label>
          <label>GROUP NAME <input type="text" class="ipt" id="group_name" placeholder="GROUP NAME"></label>
          <label>작성일 <input type="date" class="date" id="wdate" value="<?= $today ?>"></label>
          <label>여행 시작일 <input type="date" class="date" id="start_date"></label>
          <label>여행 종료일 <input type="date" class="date" id="end_date"></label>
        </div>
      </div>

      <!-- ===== 1) HOTEL ===== -->
      <div class="card" id="sec-hotel">
        <div class="hd">
          <h3>1) HOTEL</h3>
          <div class="ctl no-print">
            <button class="btn small key" id="btnHotelAdd">+ 행추가</button>
            <button class="btn small" id="btnHotelCopy">위 행 복제</button>
            <button class="btn small warn" id="btnHotelRemove">행삭제</button>
          </div>
        </div>
        <div class="pad">
          <table class="tbl" id="hotelT">
            <thead>
              <tr>
                <th style="width:70px">지역</th>
                <th style="width:100px">날짜</th>
                <th style="width:60px">요일</th>
                <th>호텔명</th>
                <th style="width:70px">방수</th>
                <th style="width:90px">요금(USD)</th>
                <th style="width:60px">박수</th>
                <th style="width:110px">합계</th>
              </tr>
            </thead>
            <tbody id="hotelBody"></tbody>
            <tfoot><tr><th colspan="7" class="num">HOTEL 소계</th><th class="num" id="hotelSub">0</th></tr></tfoot>
          </table>
          <div class="note" style="margin-top:6px">※ NYC/NIA 등 도시, 호텔명/룸타입, 박수×요금×객실 = 합계</div>
        </div>
      </div>

      <!-- ===== 2) MEAL ===== -->
      <div class="card" id="sec-meal">
        <div class="hd">
          <h3>2) MEAL (일자별/식사별 매트릭스)</h3>
          <div class="ctl no-print"><button class="btn small key" id="btnMealBuild">여행일로 날짜열 생성</button></div>
        </div>
        <div class="pad">
          <div class="note">* 날짜열(시작~종료), 조식/중식/석식 단가입력 → 인당총단가×인원 = 소계</div>
          <table class="tbl" id="mealT">
            <thead id="mealHead">
              <tr>
                <th style="width:100px">구분</th>
                <!-- 날짜열 자동 생성 -->
                <th class="num">일인당 합계단가</th>
                <th class="num">인원수</th>
                <th class="num">합계</th>
              </tr>
            </thead>
            <tbody id="mealBody">
              <tr data-meal="b"><th>조식</th><td><input class="ipt num meal-price" value="0"></td><td><input class="ipt num meal-pax pax-bind" value="0"></td><td class="num meal-sum">0</td></tr>
              <tr data-meal="l"><th>중식</th><td><input class="ipt num meal-price" value="0"></td><td><input class="ipt num meal-pax pax-bind" value="0"></td><td class="num meal-sum">0</td></tr>
              <tr data-meal="d"><th>석식</th><td><input class="ipt num meal-price" value="0"></td><td><input class="ipt num meal-pax pax-bind" value="0"></td><td class="num meal-sum">0</td></tr>
            </tbody>
            <tfoot><tr><th colspan="999" class="num">MEAL 소계 : <span id="mealSub">0</span></th></tr></tfoot>
          </table>
        </div>
      </div>

      <!-- ===== 3) TRANSPORT ===== -->
      <div class="card" id="sec-trans">
        <div class="hd">
          <h3>3) TRANSPORTATION (일자별 차량료)</h3>
          <div class="ctl no-print"><button class="btn small key" id="btnTransBuild">여행일로 날짜열 생성</button></div>
        </div>
        <div class="pad">
          <table class="tbl" id="transT">
            <thead id="transHead">
              <tr>
                <th style="width:220px">차량</th>
                <!-- 날짜열 자동 생성 -->
                <th class="num" style="width:120px">차량수</th>
                <th class="num" style="width:120px">합계</th>
              </tr>
            </thead>
            <tbody id="transBody">
              <tr>
                <td><input class="ipt trans-label" value="대형버스" placeholder="예) 대형버스 / 미니버스 / 밴"></td>
                <td><input class="ipt num trans-cnt" value="1"></td>
                <td class="num trans-sum">0</td>
              </tr>
            </tbody>
            <tfoot><tr><th colspan="999" class="num">TRANSPORTATION 소계 : <span id="transSub">0</span></th></tr></tfoot>
          </table>
        </div>
      </div>

      <!-- ===== 6) OVERTIME ===== -->
      <div class="card" id="sec-over">
        <div class="hd">
          <h3>6) OVERTIME (일자별 오버타임)</h3>
          <div class="ctl no-print"><button class="btn small key" id="btnOverBuild">여행일로 날짜열 생성</button></div>
        </div>
        <div class="pad">
          <table class="tbl" id="overT">
            <thead id="overHead">
              <tr>
                <th style="width:120px">오버타임</th>
                <!-- 날짜열 자동 생성 -->
                <th class="num" style="width:120px">건수</th>
                <th class="num" style="width:120px">합계</th>
              </tr>
            </thead>
            <tbody id="overBody">
              <tr>
                <th>오버타임</th>
                <!-- 날짜셀(자동 생성됨) -->
                <td><input class="ipt num over-cnt" value="1"></td>
                <td class="num over-sum">0</td>
              </tr>
            </tbody>
            <tfoot><tr><th colspan="999" class="num">OVERTIME 소계 : <span id="overSub">0</span></th></tr></tfoot>
          </table>
          <div class="note">* 각 날짜 칸: <b>오버타임 금액</b> + <b>사유</b> 입력 가능 / 합계는 날짜 금액 합 × 건수</div>
        </div>
      </div>

      <!-- ===== 4) 입장권 ===== -->
      <div class="card" id="sec-ticket">
        <div class="hd">
          <h3>4) 입장권</h3>
          <div class="ctl no-print"><button class="btn small key" id="btnTicketAdd">+ 행추가</button></div>
        </div>
        <div class="pad">
          <table class="tbl" id="ticketT">
            <thead>
              <tr><th>입장지</th><th class="num">단가</th><th class="num">인원</th><th class="num">합계</th><th class="no-print" style="width:70px">삭제</th></tr>
            </thead>
            <tbody id="ticketBody"></tbody>
            <tfoot><tr><th colspan="3" class="num">입장권 소계</th><th class="num" id="ticketSub">0</th><th class="no-print"></th></tr></tfoot>
          </table>
        </div>
      </div>

      <!-- ===== 5) 가이드 및 기사 ===== -->
      <div class="card" id="sec-guide">
        <div class="hd">
          <h3>5) 가이드 및 기사</h3>
          <div class="ctl no-print">
            <button class="btn small key" id="btnGuideAdd">+ 행추가</button>
            <button class="btn small warn" data-remove="#guideBody">행삭제</button>
          </div>
        </div>
        <div class="pad">
          <table class="tbl" id="guideT">
            <thead><tr><th>항목</th><th class="num">기간(일/시간)</th><th class="num">인원/대수</th><th class="num">단가</th><th class="num">합계</th></tr></thead>
            <tbody id="guideBody">
              <tr><td><input class="ipt" placeholder="예)가이드비"></td><td><input class="ipt num qty" value="0"></td><td><input class="ipt num cnt" value="0"></td><td><input class="ipt num unit" value="0"></td><td class="num amt">0</td></tr>
              <tr><td><input class="ipt" placeholder="예)드라이버 팁"></td><td><input class="ipt num qty" value="0"></td><td><input class="ipt num cnt" value="0"></td><td><input class="ipt num unit" value="0"></td><td class="num amt">0</td></tr>
            </tbody>
            <tfoot><tr><th colspan="4" class="num">가이드/기사 소계</th><th class="num" id="guideSub">0</th></tr></tfoot>
          </table>
        </div>
      </div>

      <!-- ===== 7) 기타경비 ===== -->
      <div class="card" id="sec-etc">
        <div class="hd">
          <h3>7) 기타경비</h3>
          <div class="ctl no-print">
            <button class="btn small key" id="btnEtcAdd">+ 행추가</button>
            <button class="btn small warn" data-remove="#etcBody">행삭제</button>
          </div>
        </div>
        <div class="pad">
          <table class="tbl" id="etcT">
            <thead><tr><th>항목</th><th class="num">인원/수량</th><th class="num">단가</th><th class="num">합계</th></tr></thead>
            <tbody id="etcBody">
              <tr class="yellow"><td><input class="ipt" placeholder="예)페리/더스트릿 등"></td><td><input class="ipt num qty pax-bind" value="0"></td><td><input class="ipt num unit" value="0"></td><td class="num amt">0</td></tr>
              <tr><td><input class="ipt" placeholder="예)테이블/워터 등"></td><td><input class="ipt num qty pax-bind" value="0"></td><td><input class="ipt num unit" value="0"></td><td class="num amt">0</td></tr>
            </tbody>
            <tfoot><tr><th colspan="3" class="num">기타경비 소계</th><th class="num" id="etcSub">0</th></tr></tfoot>
          </table>
        </div>
      </div>

      <!-- ===== 8/9 팁 & 회사 수익금 ===== -->
      <div class="grid g2">
        <div class="card">
          <div class="hd"><h3>8) 팁 & 매너</h3></div>
          <div class="pad grid g2">
            <label>가이드 팁 (인당/일) <input class="ipt num" id="tipGuide" value="0"></label>
            <label>기사 팁 (인당/일)   <input class="ipt num" id="tipDriver" value="0"></label>
            <label>일수 (자동)         <input class="ipt num" id="tipDays" readonly></label>
            <label>팁 합계 (자동)      <input class="ipt num" id="tipSum" readonly></label>
          </div>
        </div>
        <div class="card">
          <div class="hd"><h3>9) 회사 수익금</h3></div>
          <div class="pad grid g2">
            <label>마진(금액) <input class="ipt num" id="profit" value="0"></label>
            <label>비고       <input class="ipt" id="profit_memo" placeholder="메모"></label>
          </div>
        </div>
      </div>

      <!-- ===== TOTAL / 1인요금 ===== -->
      <div class="card">
        <div class="hd">
          <h3>10) TOTAL TOUR FEE & 11) 1인당 요금</h3>
          <div class="ctl no-print"><button class="btn key" onclick="window.print()">프린트</button></div>
        </div>
        <div class="pad">
          <div class="sumbar">
            <div>HOTEL <span class="pill" id="pHotel">0</span></div>
            <div>MEAL <span class="pill" id="pMeal">0</span></div>
            <div>TRANS <span class="pill" id="pTrans">0</span></div>
            <div>OVERTIME <span class="pill" id="pOver">0</span></div>
            <div>입장권 <span class="pill" id="pTicket">0</span></div>
            <div>가이드/기사 <span class="pill" id="pGuide">0</span></div>
            <div>기타경비 <span class="pill" id="pEtc">0</span></div>
            <div>팁 <span class="pill" id="pTip">0</span></div>
            <div>마진 <span class="pill" id="pProfit">0</span></div>
          </div>
          <table class="tbl" style="margin-top:10px">
            <tr><th style="width:220px">10) TOTAL TOUR FEE</th><td class="num" id="grandTotal">0</td></tr>
            <tr><th>11) 1인당 요금</th><td class="num" id="perPax">0</td></tr>
          </table>
        </div>
      </div>

      <div class="no-print" style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
        <input type="hidden" id="estimate_id" value="">
        <button id="btnSave" class="btn key">저장</button>
        <span id="saveStatus" class="note"></span>
      </div>
    </div><!-- /.erp-wrap -->
  </div><!-- /.main_content -->
</div><!-- /#contentwrapper -->

<script>
/* =========================
 *  BREAKDOWN QUOTATION (정리본)
 * ========================= */
(() => {
  'use strict';

  /* ---------- 공통 유틸 ---------- */
  const $  = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
  const gv = id => document.getElementById(id);

  const num = v => {
    if (v == null) return 0;
    if (v.nodeType === 1) v = v.value;
    const n = +String(v).replace(/,/g, '');
    return Number.isFinite(n) ? n : 0;
  };
  const fmt = v => {
    const n = +v;
    if (!Number.isFinite(n)) return '0';
    return n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  };
  const setNum = (el, v) => { if (el) el.textContent = fmt(v); };
  const WEEK_KR = ['일','월','화','수','목','금','토'];
  const ymd = (d) => { const x = (d instanceof Date) ? d : new Date(d); return isNaN(x) ? '' : x.toISOString().slice(0,10); };
  const getKoreanWeekday = iso => { if(!iso) return ''; const d = new Date(iso+'T00:00:00'); return WEEK_KR[d.getDay()]; };

  /* ---------- 총인원 → pax-bind 일괄 반영 ---------- */
  function applyPaxToAll(){
    const p = num(gv('total_pax'));
    $$('.pax-bind').forEach(inp => {
      inp.value = p;
      inp.dispatchEvent(new Event('input', {bubbles:true}));
    });
  }

  /* ---------- 헤더/여행일 ---------- */
  function refreshTotalPax() {
    const total = Math.max(num(gv('pax')) + num(gv('foc')), 0);
    gv('total_pax').value = fmt(total);
    applyPaxToAll();
    return total;
  }

  /* ---------- HOTEL ---------- */
    function getAutoHotelDate(){
      const base = gv('start_date')?.value || '';
      if (!base) return '';
      const d = new Date(base);
      if (isNaN(d)) return '';
      const idx = gv('hotelBody')?.children.length || 0;
      d.setDate(d.getDate() + idx);
      return ymd(d);
    }
    function setWeekdayFromDate(tr){
      const v = tr.querySelector('.hd-date')?.value || '';
      const out = tr.querySelector('.weekday');
      if (!out) return;
      out.value = v ? getKoreanWeekday(v) : '';
    }
    function calcHotelRow(tr){
      const sum = num(tr.querySelector('.rooms')) * num(tr.querySelector('.rate')) * Math.max(1, num(tr.querySelector('.nights')));
      tr.querySelector('.amt').textContent = fmt(sum);
    }
    function calcHotelSub(){
      let s=0; $$('#hotelBody .amt').forEach(td=> s += num(td.textContent));
      setNum(gv('hotelSub'), s); setNum(gv('pHotel'), s);
      return s;
    }
    function addHotel(initial={}){
      const dateVal = (initial.date !== undefined && initial.date !== null && String(initial.date).trim() !== '')
        ? initial.date
        : getAutoHotelDate();
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><input class="ipt region" placeholder="" value="${initial.region||'NYC'}"></td>
        <td><input class="ipt date hd-date" type="date" value="${dateVal}"></td>
      <td><input class="ipt weekday" type="text" value="${initial.weekday||''}" placeholder="-" readonly></td>
      <td style="position:relative;">
        <input class="ipt hotel-direct" placeholder="직접 입력" value="${initial.hotelDirect||''}" style="margin-bottom:4px;">
        <select class="sel hotelname chosen-select" data-placeholder="또는 호텔 선택(검색)…">
          <option value=""></option>${window.HOTEL_OPTIONS_HTML||''}
        </select>
      </td>
      <td><input class="ipt num rooms"  value="${initial.rooms ?? 0}"></td>
      <td><input class="ipt num rate"   value="${initial.rate  ?? 0}"></td>
      <td><input class="ipt num nights" value="${Math.max(1, initial.nights ?? 1)}"></td>
      <td class="num amt">0</td>
    `;
    gv('hotelBody').appendChild(tr);

    if (window.jQuery && jQuery.fn.chosen){
      jQuery(tr).find('.chosen-select').chosen({ width:'100%', search_contains:true, allow_single_deselect:true });
    }
    setWeekdayFromDate(tr);
    calcHotelRow(tr);
  }
  function copyPrev(){
    const body = gv('hotelBody');
    if (!body.lastElementChild) return addHotel();
    const src = body.lastElementChild;
    const tr = src.cloneNode(true);
    const amt = tr.querySelector('.amt'); if (amt) amt.textContent='0';
    body.appendChild(tr);
    setWeekdayFromDate(tr);
    calcHotelRow(tr);
    if (window.jQuery && jQuery(tr).find('.chosen-select').length && jQuery.fn.chosen){
      jQuery(tr).find('.chosen-select').chosen({width:'100%', search_contains:true, allow_single_deselect:true});
    }
  }
  function removeHotel(){
    const body = gv('hotelBody');
    if (!body || !body.lastElementChild) return;
    body.lastElementChild.remove();
    recalcAll();
  }

  /* YYYY-MM-DD 배열 (오름차순) */
  function getDateRange(){
    const s = new Date(gv('start_date').value), e = new Date(gv('end_date').value);
    if (isNaN(s) || isNaN(e) || s > e) { gv('tipDays').value = 0; return []; }
    const arr = [];
    for (let d = new Date(s); d <= e; d.setDate(d.getDate()+1)) arr.push(ymd(d));
    gv('tipDays').value = arr.length || 0;
    return arr;
  }

  /* 숫자 안전 읽기/쓰기 */
  function getCellNum(el){
    if (!el) return 0;
    const raw = (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA')
      ? el.value : (el.textContent || '');
    const n = Number(String(raw).replace(/,/g, '').trim());
    return isNaN(n) ? 0 : n;
  }
  function setCellNum(el, v){
    const s = (typeof fmt === 'function') ? fmt(v) : String(v);
    if (!el) return;
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.value = s;
    else el.textContent = s;
  }

  /* ---------- MEAL (날짜 매트릭스) ---------- */
  function buildMealColumns(){
    const days = getDateRange();
    if(!days.length){ alert('여행 시작/종료일을 먼저 선택하세요.'); return; }

    const head = gv('mealHead').rows[0];
    while (head.cells.length > 4) head.deleteCell(1);

    let insertIdx = 1;
    days.forEach(d => {
      const th = document.createElement('th');
      th.textContent = d;
      head.insertBefore(th, head.cells[insertIdx] || null);
      insertIdx++;
    });

    $$('#mealBody tr').forEach(tr=>{
      while (tr.cells.length > 4) tr.deleteCell(1);
      let idx = 1;
      days.forEach(() => {
        const td = document.createElement('td');
        td.innerHTML = `<input class="ipt num meal-cnt" value="0">`;
        tr.insertBefore(td, tr.cells[idx] || null);
        idx++;
      });

      tr.querySelectorAll('.meal-cnt').forEach(inp=>{
        inp.addEventListener('input', () => { calcMealRow(tr); calcMealSub(); recalcAll(); });
        inp.addEventListener('change', () => { calcMealRow(tr); calcMealSub(); recalcAll(); });
      });

      calcMealRow(tr);
    });

    recalcAll();
  }
  function calcMealRow(tr){
    let meals = 0;
    tr.querySelectorAll('.meal-cnt').forEach(i => meals += getCellNum(i));
    setCellNum(tr.querySelector('.meal-price'), meals);
    const pax = getCellNum(tr.querySelector('.meal-pax')) || getCellNum(gv('total_pax'));
    setCellNum(tr.querySelector('.meal-sum'), meals * pax);
  }
  function calcMealSub(){
    let s = 0;
    $$('#mealBody .meal-sum').forEach(td=> s += getCellNum(td));
    setCellNum(gv('mealSub'), s); setCellNum(gv('pMeal'), s);
    return s;
  }

  /* ---------- TRANSPORT ---------- */
  function buildTransColumns(){
    const days = getDateRange();
    if(!days.length){ alert('여행 시작/종료일을 먼저 선택'); return; }

    const head = gv('transHead').rows[0];
    while (head.cells.length > 3) head.deleteCell(1);

    days.forEach(d => {
      const th = document.createElement('th');
      th.textContent = d;
      const insertAt = Math.max(1, head.cells.length - 2);
      head.insertBefore(th, head.cells[insertAt]);
    });

    const tr = $('#transBody tr');
    while (tr.cells.length > 3) tr.deleteCell(1);

    days.forEach(() => {
      const td = document.createElement('td');
      td.innerHTML = `<input class="ipt num trans-rate" value="0">`;
      const insertAt = Math.max(1, tr.cells.length - 2);
      tr.insertBefore(td, tr.cells[insertAt]);
    });

    calcTransRow();
  }
  function calcTransRow(){
    const tr = $('#transBody tr');
    let daySum=0; tr.querySelectorAll('.trans-rate').forEach(i=> daySum += num(i));
    const sum = daySum * Math.max(1, num(tr.querySelector('.trans-cnt')));
    tr.querySelector('.trans-sum').textContent = fmt(sum);
    setNum(gv('transSub'), sum); setNum(gv('pTrans'), sum);
    return sum;
  }

  /* ---------- OVERTIME (금액 + 사유) ---------- */
  function buildOverColumns(){
    const days = getDateRange();
    if(!days.length){ alert('여행 시작/종료일을 먼저 선택'); return; }

    const head = gv('overHead').rows[0];
    while (head.cells.length > 3) head.deleteCell(1);

    days.forEach(d => {
      const th = document.createElement('th');
      th.textContent = d;
      const insertAt = Math.max(1, head.cells.length - 2);
      head.insertBefore(th, head.cells[insertAt]);
    });

    const tr = $('#overBody tr');
    while (tr.cells.length > 3) tr.deleteCell(1);

    // ★ 날짜별: 금액(over-rate) + 사유(over-reason)
    days.forEach(() => {
      const td = document.createElement('td');
      td.innerHTML = `
        <div class="ot-cell">
          <input class="ipt num over-rate" value="0" placeholder="금액">
          <input class="ipt ot-reason over-reason" value="" placeholder="사유">
        </div>
      `;
      const insertAt = Math.max(1, tr.cells.length - 2);
      tr.insertBefore(td, tr.cells[insertAt]);
    });

    calcOverRow();
  }
  function calcOverRow(){
    const tr = $('#overBody tr');
    let daySum=0;
    tr.querySelectorAll('.over-rate').forEach(i=> daySum += num(i));
    const sum = daySum * Math.max(1, num(tr.querySelector('.over-cnt')));
    tr.querySelector('.over-sum').textContent = fmt(sum);
    setNum(gv('overSub'), sum); setNum(gv('pOver'), sum);
    return sum;
  }

  /* ---------- 공용 라인(입장권/가이드/기타) ---------- */
  function addRow(bodyId){
    const tr = document.createElement('tr');
    if (bodyId === 'ticketBody'){
      tr.innerHTML = `
        <td style="position:relative;">
          <input class="ipt ticket-direct" placeholder="직접 입력" value="" style="margin-bottom:4px;">
          <select class="sel chosen-select ticketname" data-placeholder="또는 입장지 선택(검색)…">
            <option value=""></option>${window.ENTRANCE_OPTIONS_HTML || ''}
          </select>
        </td>
        <td><input class="ipt num unit" value="0"></td>
        <td><input class="ipt num qty pax-bind"  value="0"></td>
        <td class="num amt">0</td>
        <td class="no-print"><button type="button" class="btn small" data-row-remove>삭제</button></td>
      `;
    } else if (bodyId === 'guideBody'){
      tr.innerHTML = `<td><input class="ipt"></td><td><input class="ipt num qty" value="0"></td><td><input class="ipt num cnt" value="1"></td><td><input class="ipt num unit" value="0"></td><td class="num amt">0</td>`;
    } else {
      tr.innerHTML = `<td><input class="ipt"></td><td><input class="ipt num qty pax-bind" value="0"></td><td><input class="ipt num unit" value="0"></td><td class="num amt">0</td>`;
    }
    gv(bodyId).appendChild(tr);
    if (bodyId === 'ticketBody' && window.jQuery && jQuery.fn.chosen){
      jQuery(tr).find('.chosen-select').chosen({width:'100%', search_contains:true, allow_single_deselect:true});
    }
    if (bodyId !== 'guideBody') applyPaxToAll();
  }
  function calcGeneric(bodyId){
    let s=0;
    $$('#'+bodyId+' tr').forEach(tr=>{
      const unit= tr.querySelector('.unit')? num(tr.querySelector('.unit')):0;
      const qty = tr.querySelector('.qty')?  num(tr.querySelector('.qty')):0;
      const cnt = tr.querySelector('.cnt')?  Math.max(1, num(tr.querySelector('.cnt'))):1;
      const v   = unit*qty*cnt;
      const amt = tr.querySelector('.amt'); if(amt) amt.textContent=fmt(v);
      s+=v;
    });
    if(bodyId==='ticketBody'){ setNum(gv('ticketSub'),s); setNum(gv('pTicket'),s); }
    if(bodyId==='guideBody'){  setNum(gv('guideSub'),s);  setNum(gv('pGuide'),s);  }
    if(bodyId==='etcBody'){    setNum(gv('etcSub'),s);    setNum(gv('pEtc'),s);    }
    return s;
  }

  /* ---------- 팁 & 총계 ---------- */
  function calcTip(){
    const guide = num(gv('tipGuide')), driver = num(gv('tipDriver'));
    const days  = +gv('tipDays').value || getDateRange().length || 0;
    const pax   = num(gv('total_pax')) || 0;
    const sum   = (guide + driver) * days * pax;
    gv('tipSum').value = fmt(sum);
    setNum(gv('pTip'), sum);
    return sum;
  }
  function recalcAll(){
    $$('#hotelBody tr').forEach(calcHotelRow);
    const hotel = calcHotelSub();

    $$('#mealBody tr').forEach(calcMealRow);
    const meal = calcMealSub();

    const trans = calcTransRow();
    const over  = calcOverRow();

    const ticket = calcGeneric('ticketBody');
    const guide  = calcGeneric('guideBody');
    const etc    = calcGeneric('etcBody');

    const tip    = calcTip();
    const profit = num(gv('profit')) || 0;
    setNum(gv('pProfit'), profit);

    const total = hotel+meal+trans+over+ticket+guide+etc+tip+profit;
    setNum(gv('grandTotal'), total);

    const tp = Math.max(num(gv('pax')) - num(gv('foc')), 0);
    setNum(gv('perPax'), tp>0 ? (total/(tp+1)) : 0);
  }

  /* ---------- 직렬화(저장용) ---------- */
  function serializeHotel(){
    return $$('#hotelBody tr').map(tr=>{
      const sel = tr.querySelector('.hotelname');
      const directInput = tr.querySelector('.hotel-direct');
      const opt = sel ? sel.options[sel.selectedIndex] : null;

      const hotelDirect = directInput ? directInput.value.trim() : '';
      const hotelId   = sel ? (sel.value || '') : '';
      const hotelName = hotelDirect || (opt ? (opt.dataset.name || '') : '');

      return {
        section:'HOTEL',
        label: hotelName,
        qty:   num(tr.querySelector('.nights')),
        unit:  num(tr.querySelector('.rate')),
        cnt:   num(tr.querySelector('.rooms')),
        sum:   num(tr.querySelector('.amt').textContent),
        etc:   {
          region:  tr.querySelector('.region')?.value || '',
          date:    tr.querySelector('.hd-date')?.value || '',
          weekday: tr.querySelector('.weekday')?.value || '',
          hotel_id: hotelId,
          hotel_direct: hotelDirect
        }
      };
    });
  }

  /* MEAL/TRANSPORT/OVERTIME 날짜 가변 저장 */
  function serializeMeal(){
    const head = document.getElementById('mealHead').rows[0];
    const dateCount = Math.max(0, head.cells.length - 4);
    const dates = []; for(let c=1;c<=dateCount;c++) dates.push(head.cells[c].textContent.trim());

    return $$('#mealBody tr').map(tr=>{
      const label = tr.cells[0]?.textContent?.trim() || '';
      const pax   = num(tr.querySelector('.meal-pax')) || num(gv('total_pax'));
      const unit  = num(tr.querySelector('.meal-price'));
      const dmap  = {};
      const cells = tr.querySelectorAll('.meal-cnt');

      dates.forEach((d,i)=>{
        const v = num(cells[i]);
        if (d && v!==0) dmap[d] = v;
      });

      const qtyTotal = Object.values(dmap).reduce((a,b)=>a+(+b||0),0);
      const sumUi    = num(tr.querySelector('.meal-sum').textContent);

      return {
        section:'MEAL',
        label,
        qty:   qtyTotal,
        unit:  unit,
        cnt:   pax,
        sum:   sumUi,
        etc:   { dates:dmap, unit_per_pax:unit, pax:pax }
      };
    });
  }

  function serializeTrans(){
    const head = document.getElementById('transHead').rows[0];
    const dateCount = Math.max(0, head.cells.length - 3);
    const dates = []; for(let c=1;c<=dateCount;c++) dates.push(head.cells[c].textContent.trim());

    const tr = $('#transBody tr'); if(!tr) return [];
    const cnt = Math.max(1, num(tr.querySelector('.trans-cnt')));

    const label = (tr.querySelector('.trans-label')?.value || '').trim() || 'Transportation';

    const dmap = {};
    const cells = tr.querySelectorAll('.trans-rate');

    dates.forEach((d,i)=>{
      const v = num(cells[i]);
      if (d && v!==0) dmap[d] = v;
    });

    const qtyTotal = Object.values(dmap).reduce((a,b)=>a+(+b||0),0);
    const sumUi    = num(tr.querySelector('.trans-sum').textContent);

    return [{
      section:'TRANSPORT',
      label: label,
      qty:   qtyTotal,
      unit:  0,
      cnt:   cnt,
      sum:   sumUi,
      etc:   { dates:dmap, unit_per_car: cnt }
    }];
  }

  function serializeOver(){
    const head = document.getElementById('overHead').rows[0];
    const dateCount = Math.max(0, head.cells.length - 3);
    const dates = []; for(let c=1;c<=dateCount;c++) dates.push(head.cells[c].textContent.trim());

    const tr = $('#overBody tr'); if(!tr) return [];
    const cnt = Math.max(1, num(tr.querySelector('.over-cnt')));

    const dmap = {};
    const rmap = {}; // ★ 날짜별 사유 저장
    const rateCells = tr.querySelectorAll('.over-rate');
    const reasonCells = tr.querySelectorAll('.over-reason');

    dates.forEach((d,i)=>{
      const v = num(rateCells[i]);
      const r = (reasonCells[i]?.value || '').trim();
      if (d && v!==0) dmap[d] = v;
      if (d && r) rmap[d] = r;
    });

    const qtyTotal = Object.values(dmap).reduce((a,b)=>a+(+b||0),0);
    const sumUi    = num(tr.querySelector('.over-sum').textContent);

    return [{
      section:'OVERTIME',
      label: tr.cells[0]?.textContent?.trim() || 'Overtime',
      qty:   qtyTotal,
      unit:  0,
      cnt:   cnt,
      sum:   sumUi,
      etc:   { dates:dmap, reasons:rmap, unit_per_target: cnt }
    }];
  }

  function serializeGeneric(bodyId, section){
    return $$('#'+bodyId+' tr').map(tr=>({
      section,
      label: tr.querySelector('td input.ipt')?.value || '',
      qty:   num(tr.querySelector('.qty')),
      unit:  num(tr.querySelector('.unit')),
      cnt:   Math.max(1, num(tr.querySelector('.cnt')) || 1),
      sum:   num(tr.querySelector('.amt').textContent),
      etc:   {}
    }));
  }
  function serializeTip(){
    return [{
      section:'TIP',
      label:'Tip & Manner',
      qty:   +gv('tipDays').value || 0,
      unit:  num(gv('tipGuide')) + num(gv('tipDriver')),
      cnt:   num(gv('total_pax')),
      sum:   num(gv('tipSum').value),
      etc:   { guide:num(gv('tipGuide')), driver:num(gv('tipDriver')) }
    }];
  }
  function serializeProfit(){
    const v = num(gv('profit'));
    return [{ section:'PROFIT', label: gv('profit_memo')?.value || '', qty:1, unit:v, cnt:1, sum:v, etc:{} }];
  }
  function serializeTicket(){
    return $$('#ticketBody tr').map(tr=>{
      const sel = tr.querySelector('.ticketname');
      const directInput = tr.querySelector('.ticket-direct');
      const opt = sel ? sel.options[sel.selectedIndex] : null;

      const ticketDirect = directInput ? directInput.value.trim() : '';
      const code = sel ? (sel.value || '') : '';
      const label = ticketDirect || (opt ? (opt.dataset.name || opt.textContent.trim()) : '');

      const unit = num(tr.querySelector('.unit'));
      const qty  = num(tr.querySelector('.qty'));
      const sum  = unit * qty;
      tr.querySelector('.amt').textContent = fmt(sum);

      return {
        section: 'TICKET',
        label,
        qty,
        unit,
        cnt: 1,
        sum,
        etc: {
          code_base_key: code,
          ticket_direct: ticketDirect
        }
      };
    });
  }

  /* ---------- 저장 ---------- */
  async function saveEstimate(){
    recalcAll();
    const fd = new FormData();
    fd.append('mode','save_estimate');
    fd.append('id',          gv('estimate_id')?.value || '');
    fd.append('estimate_no', 'EST-'+Date.now());
    fd.append('to_name',     gv('to_name')?.value || '');
    fd.append('pax',         String(num(gv('pax'))));
    fd.append('foc',         String(num(gv('foc'))));
    fd.append('total_pax',   String(num(gv('total_pax'))));
    fd.append('group_name',  gv('group_name')?.value || '');
    fd.append('start_date',  gv('start_date')?.value || '');
    fd.append('end_date',    gv('end_date')?.value || '');
    fd.append('wdate',       gv('wdate')?.value || '');
    fd.append('profit',      String(num(gv('profit'))));
    fd.append('profit_memo', gv('profit_memo')?.value || '');
    fd.append('grand_total', String(num(gv('grandTotal').textContent)));
    fd.append('per_pax',     String(num(gv('perPax').textContent)));

    const items = [
      ...serializeHotel(),
      ...serializeMeal(),
      ...serializeTrans(),
      ...serializeOver(),          // ★ 오버타임 사유 포함 저장
      ...serializeTicket(),
      ...serializeGeneric('guideBody','GUIDE'),
      ...serializeGeneric('etcBody','ETC'),
      ...serializeTip(),
      ...serializeProfit()
    ];

    items.forEach((it,i)=>{
      fd.append(`items[${i}][section]`, it.section);
      fd.append(`items[${i}][label]`,   it.label);
      fd.append(`items[${i}][qty]`,     String(it.qty ?? 0));
      fd.append(`items[${i}][unit]`,    String(it.unit ?? 0));
      fd.append(`items[${i}][cnt]`,     String(it.cnt ?? 1));
      fd.append(`items[${i}][sum]`,     String(it.sum ?? (it.qty*it.unit*(it.cnt||1))));
      fd.append(`items[${i}][etc]`,     JSON.stringify(it.etc || {}));
    });

    const btn = gv('btnSave'), stat = gv('saveStatus');
    try{
      btn.disabled = true; stat.textContent = '저장 중...';
      const res = await fetch('estimate_save.php', { method:'POST', body: fd });
      const out = await res.json();
      if (out.result !== 'OK') throw new Error(out.message || '저장 실패');
      stat.textContent = `저장 완료 (ID=${out.id})`;
      if (gv('estimate_id')) gv('estimate_id').value = out.id;
    }catch(err){
      alert('저장 오류: '+err.message); stat.textContent='오류 발생';
    }finally{ btn.disabled = false; }
  }

  /* ---------- 서버 데이터 로드 ---------- */
  function setHotelSelectValue(select, hotelId, label){
    if (!select) return;
    if (hotelId && [...select.options].some(o=> String(o.value) === String(hotelId))) {
      select.value = String(hotelId);
    } else if (label) {
      const m=[...select.options].find(o=> (o.dataset.name||'').trim()===label.trim());
      if (m) select.value = m.value;
    }
    if (window.jQuery && jQuery(select).data('chosen')) { jQuery(select).trigger("chosen:updated"); }
  }

  function loadFromServer(){
    const EST = (window.EST_DATA || {}), M = EST.master || null, IT = EST.items || [];
    if (M){
      gv('estimate_id').value = M.id || '';
      gv('to_name').value     = M.to_name || '';
      gv('pax').value         = M.pax || 0;
      gv('foc').value         = M.foc || 0;
      gv('group_name').value  = M.group_name || '';
      gv('wdate').value       = M.wdate || gv('wdate').value;
      gv('start_date').value  = M.start_date || '';
      gv('end_date').value    = M.end_date   || '';
      gv('profit').value      = M.profit || 0;
      gv('profit_memo').value = M.profit_memo || '';
    }
    refreshTotalPax();

    if (gv('start_date').value && gv('end_date').value){
      buildMealColumns(); buildTransColumns(); buildOverColumns();
    }

    if (!IT.length){
      if(!gv('hotelBody').children.length){
        addHotel({ date: gv('start_date').value || ymd(new Date()), nights: 1 });
      }
      recalcAll(); return;
    }

    const bySec = {HOTEL:[],MEAL:[],TRANSPORT:[],OVERTIME:[],TICKET:[],GUIDE:[],ETC:[],TIP:[],PROFIT:[]};
    IT.forEach(r => (bySec[r.section]||(bySec[r.section]=[])).push(r));

    // HOTEL
    gv('hotelBody').innerHTML = '';
    if (bySec.HOTEL.length){
      bySec.HOTEL.forEach(r=>{
        const etc = r.etc_json ? JSON.parse(r.etc_json) : {};
        addHotel({
          region: etc.region||'',
          date: etc.date||'',
          weekday: etc.weekday||'',
          rooms: r.cnt||0,
          rate: r.unit||0,
          nights: r.qty||1,
          hotelDirect: etc.hotel_direct || ''
        });
        const tr = gv('hotelBody').lastElementChild;
        if (!etc.hotel_direct) setHotelSelectValue(tr.querySelector('.hotelname'), etc.hotel_id, r.label||'');
        calcHotelRow(tr);
      });
    }

    // MEAL
    if (bySec.MEAL.length){
      buildMealColumns();
      const keyMap = {'조식':'b','중식':'l','석식':'d'};
      bySec.MEAL.forEach(r=>{
        const trow = $(`#mealBody tr[data-meal="${keyMap[r.label]||''}"]`); if(!trow) return;
        const etc = r.etc_json ? JSON.parse(r.etc_json) : {};
        trow.querySelector('.meal-price').value = (etc.unit_per_pax ?? r.unit ?? 0);
        trow.querySelector('.meal-pax').value   = (etc.pax ?? r.cnt ?? 0);
        const head = $('#mealHead tr');
        if (etc.dates && typeof etc.dates==='object'){
          for(let i=1;i<head.cells.length-3;i++){
            const d = head.cells[i].textContent.trim();
            if (d in etc.dates){
              const ipt = trow.cells[i].querySelector('.meal-cnt');
              if (ipt) ipt.value = num(ipt) + (etc.dates[d]||0);
            }
          }
        }
        calcMealRow(trow);
      });
    }

    // TRANSPORT
    buildTransColumns();
    if (bySec.TRANSPORT.length){
      const tr = $('#transBody tr'), head = $('#transHead tr');
      bySec.TRANSPORT.forEach(r=>{
        const etc = r.etc_json ? JSON.parse(r.etc_json) : {};

        const label = (r.label || '').trim();
        const labelInput = tr.querySelector('.trans-label');
        if (labelInput && label) labelInput.value = label;

        tr.querySelector('.trans-cnt').value = (etc.unit_per_car ?? r.cnt ?? 1);
        if (etc.dates && typeof etc.dates==='object'){
          for(let i=1;i<head.cells.length-2;i++){
            const d = head.cells[i].textContent.trim();
            if (d in etc.dates){
              const ipt = tr.cells[i].querySelector('.trans-rate');
              if (ipt) ipt.value = num(ipt) + (etc.dates[d]||0);
            }
          }
        }
      });
      calcTransRow();
    }

    // OVERTIME (금액 + 사유 로드)
    buildOverColumns();
    if (bySec.OVERTIME.length){
      const tr = $('#overBody tr'), head = $('#overHead tr');
      bySec.OVERTIME.forEach(r=>{
        const etc = r.etc_json ? JSON.parse(r.etc_json) : {};
        tr.querySelector('.over-cnt').value = (etc.unit_per_target ?? r.cnt ?? 1);

        const datesMap = (etc.dates && typeof etc.dates==='object') ? etc.dates : {};
        const reasonsMap = (etc.reasons && typeof etc.reasons==='object') ? etc.reasons : {};

        for(let i=1;i<head.cells.length-2;i++){
          const d = head.cells[i].textContent.trim();
          if (d in datesMap){
            const ipt = tr.cells[i].querySelector('.over-rate');
            if (ipt) ipt.value = num(ipt) + (datesMap[d]||0);
          }
          if (d in reasonsMap){
            const rip = tr.cells[i].querySelector('.over-reason');
            if (rip) rip.value = String(reasonsMap[d]||'');
          }
        }
      });
      calcOverRow();
    }

    // 간단 라인
    const fillSimple = (bodyId, arr) => {
      const body = gv(bodyId); body.innerHTML='';
      if (!arr.length) return;
      arr.forEach(r=>{
        const tr = document.createElement('tr');
        if (bodyId==='ticketBody'){
          const etc = r.etc_json ? JSON.parse(r.etc_json) : {};
          const ticketDirect = etc.ticket_direct || '';

          tr.innerHTML = `
            <td style="position:relative;">
              <input class="ipt ticket-direct" placeholder="직접 입력" value="${ticketDirect.replace(/"/g,'&quot;')}" style="margin-bottom:4px;">
              <select class="sel chosen-select ticketname" data-placeholder="또는 입장지 선택(검색)…">
                <option value=""></option>${window.ENTRANCE_OPTIONS_HTML||''}
              </select>
            </td>
            <td><input class="ipt num unit" value="${r.unit||0}"></td>
            <td><input class="ipt num qty pax-bind"  value="${r.qty||0}"></td>
            <td class="num amt">0</td>
            <td class="no-print"><button type="button" class="btn small" data-row-remove>삭제</button></td>
          `;
          body.appendChild(tr);

          if (window.jQuery && jQuery.fn.chosen){
            jQuery(tr).find('.chosen-select').chosen({width:'100%', search_contains:true, allow_single_deselect:true});
          }
          if (!ticketDirect && etc.code_base_key) {
            tr.querySelector('.ticketname').value = String(etc.code_base_key);
            if (window.jQuery && jQuery(tr.querySelector('.ticketname')).data('chosen')) {
              jQuery(tr.querySelector('.ticketname')).trigger('chosen:updated');
            }
          }
        } else if (bodyId==='guideBody'){
          tr.innerHTML = `<td><input class="ipt" value="${(r.label||'').replace(/"/g,'&quot;')}"></td>
                          <td><input class="ipt num qty"  value="${r.qty||0}"></td>
                          <td><input class="ipt num cnt"  value="${r.cnt||1}"></td>
                          <td><input class="ipt num unit" value="${r.unit||0}"></td>
                          <td class="num amt">0</td>`;
          body.appendChild(tr);
        } else {
          tr.innerHTML = `<td><input class="ipt" value="${(r.label||'').replace(/"/g,'&quot;')}"></td>
                          <td><input class="ipt num qty pax-bind"  value="${r.qty||0}"></td>
                          <td><input class="ipt num unit" value="${r.unit||0}"></td>
                          <td class="num amt">0</td>`;
          body.appendChild(tr);
        }
      });
      if (bodyId!=='guideBody') applyPaxToAll();
    };
    fillSimple('ticketBody', bySec.TICKET);
    fillSimple('guideBody',  bySec.GUIDE);
    fillSimple('etcBody',    bySec.ETC);

    // TIP/PROFIT
    if (bySec.TIP.length){
      const t = bySec.TIP[0]; const e = t.etc_json?JSON.parse(t.etc_json):{};
      gv('tipGuide').value = e.guide ?? 0; gv('tipDriver').value = e.driver ?? 0;
    }
    if (bySec.PROFIT.length){
      gv('profit').value = bySec.PROFIT[0].sum || 0;
      gv('profit_memo').value = bySec.PROFIT[0].label || '';
    }

    if (!$('#ticketBody tr')) addRow('ticketBody');
    recalcAll();
  }

  /* ---------- Enter 네비, 이벤트 바인딩 ---------- */
  function buildFocusableList(){
    const scope = $('.erp-wrap');
    const nodes = $$('input:not([type=button]):not([type=submit]):not([readonly]):not([disabled]), select:not([disabled])', scope)
      .filter(el=>{
        if (!el.offsetParent) return false;
        if (el.classList.contains('search-field')) return false;
        return true;
      });
    return nodes;
  }
  function focusRelative(current, delta){
    const list = buildFocusableList();
    const idx = Math.max(0, list.indexOf(current));
    let next = idx + delta;
    if (next < 0) next = 0;
    if (next >= list.length) next = list.length - 1;
    const target = list[next];
    if (target && typeof target.focus === 'function') {
      target.focus();
      if (target.select && (target.tagName==='INPUT')) target.select();
    }
  }
  function bindEnterNavigation(){
    $('.erp-wrap').addEventListener('keydown', (e)=>{
      const t = e.target;
      if (!(t instanceof HTMLElement)) return;
      const isInput = t.tagName==='INPUT' || t.tagName==='SELECT' || t.tagName==='TEXTAREA';
      if (!isInput) return;
      if (e.key === 'Enter') {
        e.preventDefault();
        focusRelative(t, e.shiftKey ? -1 : 1);
      }
    });
  }

  function bindEvents(){
    ['pax','foc'].forEach(id => gv(id).addEventListener('input', ()=>{ refreshTotalPax(); recalcAll(); }));
    gv('profit').addEventListener('input', recalcAll);
    ['end_date'].forEach(id => gv(id).addEventListener('change', ()=>{
      buildMealColumns(); buildTransColumns(); buildOverColumns(); recalcAll();
      const tr = gv('hotelBody').rows?.[0];
      if (tr && !(tr.querySelector('.hotelname')?.value)) {
        const ipt = tr.querySelector('.hd-date'); if (ipt) { ipt.value = gv('start_date').value; setWeekdayFromDate(tr); }
      }
    }));

    gv('btnHotelAdd').addEventListener('click', ()=> addHotel());
    gv('btnHotelCopy').addEventListener('click', ()=> copyPrev());
    gv('btnHotelRemove').addEventListener('click', ()=> removeHotel());

    gv('btnMealBuild').addEventListener('click', buildMealColumns);
    gv('btnTransBuild').addEventListener('click', buildTransColumns);
    gv('btnOverBuild').addEventListener('click', buildOverColumns);

    gv('btnTicketAdd').addEventListener('click', ()=> addRow('ticketBody'));
    gv('btnGuideAdd') .addEventListener('click', ()=> addRow('guideBody'));
    gv('btnEtcAdd')   .addEventListener('click', ()=> addRow('etcBody'));

    gv('btnSave').addEventListener('click', (e)=>{ e.preventDefault(); saveEstimate(); });

    gv('hotelBody').addEventListener('input', e=>{
      const tr = e.target.closest('tr'); if (!tr) return;
      if (e.target.matches('.rooms,.rate,.nights')) { calcHotelRow(tr); recalcAll(); }
    });
    gv('hotelBody').addEventListener('change', e=>{
      const tr = e.target.closest('tr'); if (!tr) return;
      if (e.target.matches('.hd-date')) { setWeekdayFromDate(tr); recalcAll(); }
    });

    gv('mealBody').addEventListener('input', e=>{
      const tr = e.target.closest('tr'); if (!tr) return;
      if (e.target.matches('.meal-cnt,.meal-price,.meal-pax')) { calcMealRow(tr); recalcAll(); }
    });

    gv('transBody').addEventListener('input', e=>{
      if (e.target.matches('.trans-rate,.trans-cnt')) { calcTransRow(); recalcAll(); }
      if (e.target.matches('.trans-label')) { /* label만 변경 */ }
    });

    // ★ 오버타임: 금액/사유 입력 시 합계 갱신(사유는 합계에 영향 없음)
    gv('overBody').addEventListener('input', e=>{
      if (e.target.matches('.over-rate,.over-cnt')) { calcOverRow(); recalcAll(); }
      if (e.target.matches('.over-reason')) { /* 저장만 */ }
    });

    $$('button[data-remove]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const sel  = btn.getAttribute('data-remove');
        const body = document.querySelector(sel);
        if (!body) return;
        let target = document.activeElement && body.contains(document.activeElement)
          ? document.activeElement.closest('tr') : null;
        if (!target) target = body.querySelector('tr.row-focus');
        if (!target) target = body.lastElementChild;
        if (target) {
          target.remove();
          const id = body.id;
          if (id === 'guideBody' || id === 'etcBody' || id === 'ticketBody') calcGeneric(id);
          recalcAll();
        }
      });
    });

    ['ticketBody','guideBody','etcBody'].forEach(id=>{
      gv(id).addEventListener('input', e=>{
        if (e.target.matches('.unit,.qty,.cnt')) { calcGeneric(id); recalcAll(); }
      });
      gv(id).addEventListener('click', e=>{
        if (e.target.hasAttribute('data-row-remove')) {
          const tr = e.target.closest('tr'); if (tr) tr.remove();
          calcGeneric(id); recalcAll();
        }
      });
    });

    ['ticketBody','guideBody','etcBody'].forEach(id=>{
      const body = gv(id);
      if (!body) return;
      const mark = (tr) => {
        body.querySelectorAll('tr').forEach(r=> r.classList.remove('row-focus'));
        if (tr) tr.classList.add('row-focus');
      };
      body.addEventListener('focusin', e=>{
        const tr = e.target.closest('tr');
        if (tr) mark(tr);
      });
      body.addEventListener('click', e=>{
        const tr = e.target.closest('tr');
        if (tr) mark(tr);
      });
    });

    gv('tipGuide').addEventListener('input', ()=>{ calcTip(); recalcAll(); });
    gv('tipDriver').addEventListener('input', ()=>{ calcTip(); recalcAll(); });

    bindEnterNavigation();
  }

  function initFirstHotelIfEmpty(){
    if (!gv('hotelBody').children.length){
      addHotel({ date: gv('start_date').value || ymd(new Date()), nights: 1 });
    }
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    bindEvents();
    loadFromServer();
    initFirstHotelIfEmpty();
    if (!$('#ticketBody tr')) addRow('ticketBody');
    applyPaxToAll();
    recalcAll();
  });
})();
</script>
