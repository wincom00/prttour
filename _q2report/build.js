const pptxgen = require("pptxgenjs");
const React = require("react");
const ReactDOMServer = require("react-dom/server");
const sharp = require("sharp");
const {
  FaGlobeAmericas, FaExchangeAlt, FaBalanceScale, FaChartLine,
  FaFutbol, FaClipboardList, FaCoins
} = require("react-icons/fa");

// ---------- palette ----------
const NAVY   = "0F2A4A";
const NAVY2  = "163A63";
const TEAL   = "1C7293";
const ICE    = "CFE3F2";
const GOLD   = "E0A23B";
const RED     = "C9483F";
const GREEN  = "2E8B6F";
const INK    = "1A2433";
const MUTE   = "6B7A8D";
const PANEL  = "F3F7FB";
const WHITE  = "FFFFFF";
const KF = "맑은 고딕";

const pres = new pptxgen();
pres.layout = "LAYOUT_WIDE";
pres.author = "PARANTOURS";
pres.title = "2026 2분기 경영실적 분석 (예약·수금 양 기준)";
const W = 13.3;

const sh = () => ({ type: "outer", color: "000000", blur: 7, offset: 3, angle: 135, opacity: 0.16 });
async function icon(Comp, color, size = 256) {
  const svg = ReactDOMServer.renderToStaticMarkup(React.createElement(Comp, { color, size: String(size) }));
  const png = await sharp(Buffer.from(svg)).png().toBuffer();
  return "image/png;base64," + png.toString("base64");
}
let PAGE = 0;
function footer(slide) {
  PAGE++;
  slide.addText("PARANTOURS  ·  2026 2분기 경영실적 분석 (예약·수금 양 기준)  ·  기준일 2026-06-08",
    { x: 0.5, y: 7.05, w: 11, h: 0.3, fontFace: KF, fontSize: 8, color: MUTE, align: "left" });
  slide.addText(String(PAGE), { x: 12.4, y: 7.05, w: 0.5, h: 0.3, fontFace: KF, fontSize: 8, color: MUTE, align: "right" });
}
function kicker(slide, txt) {
  slide.addText(txt.toUpperCase(), { x: 0.5, y: 0.42, w: 9, h: 0.3, fontFace: KF, fontSize: 11, color: TEAL, bold: true, charSpacing: 2, margin: 0 });
}
function title(slide, txt) {
  slide.addText(txt, { x: 0.5, y: 0.72, w: 12.3, h: 0.7, fontFace: KF, fontSize: 29, color: INK, bold: true, margin: 0 });
}
// generic content slide with chart + side memo cards
function chartSlide(opts) {
  const s = pres.addSlide(); s.background = { color: WHITE };
  kicker(s, opts.kicker); title(s, opts.title);
  return s;
}
(async () => {
  const ic = {
    globe: await icon(FaGlobeAmericas, "#FFFFFF"),
    fx: await icon(FaExchangeAlt, "#0F2A4A"),
    scale: await icon(FaBalanceScale, "#0F2A4A"),
    line: await icon(FaChartLine, "#0F2A4A"),
    ball: await icon(FaFutbol, "#0F2A4A"),
    clip: await icon(FaClipboardList, "#FFFFFF"),
    coinDark: await icon(FaCoins, "#1A2433"),
  };

  // ---------- reusable: yearly bar + 3 stat cards ----------
  function yearlyChart(s, values, labels, fmt) {
    s.addChart(pres.charts.BAR, [{ name: "값", labels, values }], {
      x: 0.5, y: 1.7, w: 7.6, h: 4.9, barDir: "col", chartColors: [TEAL],
      showValue: true, dataLabelPosition: "outEnd", dataLabelColor: INK, dataLabelFontFace: KF, dataLabelFontSize: 11, dataLabelFontBold: true, dataLabelFormatCode: fmt,
      valAxisHidden: true, valGridLine: { style: "none" },
      catAxisLabelColor: INK, catAxisLabelFontFace: KF, catAxisLabelFontSize: 13, catAxisLabelFontBold: true,
      showLegend: false, showTitle: false, barGapWidthPct: 55, chartArea: { fill: { color: WHITE } },
    });
  }
  function statCards(s, rows) {
    s.addText("연도별 핵심 변화", { x: 8.45, y: 1.75, w: 4.3, h: 0.4, fontFace: KF, fontSize: 15, color: NAVY, bold: true, margin: 0 });
    rows.forEach((r, i) => {
      const yy = 2.35 + i * 1.42;
      s.addShape(pres.shapes.RECTANGLE, { x: 8.45, y: yy, w: 4.35, h: 1.22, fill: { color: PANEL }, line: { color: "E2E9F0", width: 1 } });
      s.addShape(pres.shapes.RECTANGLE, { x: 8.45, y: yy, w: 0.09, h: 1.22, fill: { color: r.c } });
      s.addText(r.k, { x: 8.68, y: yy + 0.14, w: 4, h: 0.3, fontFace: KF, fontSize: 12, color: MUTE, bold: true, margin: 0 });
      s.addText(r.v, { x: 8.66, y: yy + 0.4, w: 2.8, h: 0.5, fontFace: KF, fontSize: 26, color: r.c, bold: true, margin: 0 });
      s.addText(r.d, { x: 8.68, y: yy + 0.9, w: 4, h: 0.3, fontFace: KF, fontSize: 10.5, color: MUTE, margin: 0 });
    });
  }
  function monthCards(s, rows) {
    s.addText("월별 메모", { x: 8.75, y: 1.75, w: 4, h: 0.4, fontFace: KF, fontSize: 15, color: NAVY, bold: true, margin: 0 });
    rows.forEach((r, i) => {
      const yy = 2.3 + i * 1.45;
      s.addShape(pres.shapes.RECTANGLE, { x: 8.75, y: yy, w: 4.05, h: 1.25, fill: { color: PANEL }, line: { color: "E2E9F0", width: 1 } });
      s.addText([{ text: r.m + "  ", options: { color: INK, bold: true, fontSize: 13 } }, { text: r.d, options: { color: r.c, bold: true, fontSize: 16 } }],
        { x: 8.95, y: yy + 0.16, w: 3.7, h: 0.4, fontFace: KF, margin: 0 });
      s.addText(r.t, { x: 8.95, y: yy + 0.6, w: 3.7, h: 0.55, fontFace: KF, fontSize: 10.5, color: MUTE, margin: 0, valign: "top" });
    });
  }
  function typeCards(s, rows) {
    s.addText("유형별 변화", { x: 8.45, y: 1.75, w: 4.3, h: 0.4, fontFace: KF, fontSize: 15, color: NAVY, bold: true, margin: 0 });
    rows.forEach((r, i) => {
      const yy = 2.3 + i * 1.45;
      s.addShape(pres.shapes.RECTANGLE, { x: 8.45, y: yy, w: 4.35, h: 1.27, fill: { color: PANEL }, line: { color: "E2E9F0", width: 1 } });
      s.addShape(pres.shapes.RECTANGLE, { x: 8.45, y: yy, w: 0.09, h: 1.27, fill: { color: r.c } });
      s.addText(r.t, { x: 8.66, y: yy + 0.13, w: 4.05, h: 0.3, fontFace: KF, fontSize: 11.5, color: INK, bold: true, margin: 0 });
      s.addText(r.d, { x: 8.66, y: yy + 0.42, w: 4.05, h: 0.4, fontFace: KF, fontSize: 19, color: r.c, bold: true, margin: 0 });
      s.addText(r.n, { x: 8.66, y: yy + 0.86, w: 4.05, h: 0.35, fontFace: KF, fontSize: 9.5, color: MUTE, margin: 0 });
    });
  }
  function clustered(s, v25, v26, labels, dir) {
    s.addChart(pres.charts.BAR, [
      { name: "2025", labels, values: v25 },
      { name: "2026", labels, values: v26 },
    ], {
      x: 0.5, y: 1.7, w: (dir === "bar" ? 7.7 : 8.0), h: dir === "bar" ? 4.6 : 4.7, barDir: dir,
      chartColors: [ICE, TEAL],
      valAxisHidden: true, valGridLine: { color: "ECF1F6", size: 0.5 },
      catAxisLabelColor: INK, catAxisLabelFontFace: KF, catAxisLabelFontSize: dir === "bar" ? 11.5 : 13, catAxisLabelFontBold: true,
      showLegend: true, legendPos: "t", legendColor: INK, legendFontFace: KF, legendFontSize: 12,
      showTitle: false, barGapWidthPct: dir === "bar" ? 50 : 40, barOverlapPct: -10, chartArea: { fill: { color: WHITE } },
    });
  }
  function note(s, t) {
    s.addText(t, { x: 0.5, y: 6.65, w: 12.3, h: 0.3, fontFace: KF, fontSize: 9, color: MUTE, italic: true, margin: 0 });
  }
  function divider(part, big, sub, iconData) {
    const s = pres.addSlide(); s.background = { color: NAVY };
    s.addShape(pres.shapes.RECTANGLE, { x: 0, y: 0, w: W, h: 0.18, fill: { color: GOLD } });
    s.addText(part, { x: 0.9, y: 2.55, w: 11, h: 0.5, fontFace: KF, fontSize: 16, color: GOLD, bold: true, charSpacing: 3, margin: 0 });
    s.addText(big, { x: 0.85, y: 3.05, w: 11.5, h: 1.1, fontFace: KF, fontSize: 42, color: WHITE, bold: true, margin: 0 });
    s.addText(sub, { x: 0.9, y: 4.25, w: 10.5, h: 0.5, fontFace: KF, fontSize: 16, color: ICE, margin: 0 });
    if (iconData) s.addImage({ data: iconData, x: 11.6, y: 2.55, w: 0.95, h: 0.95 });
    footer(s);
  }

  // ============ 1 · TITLE ============
  let s = pres.addSlide(); s.background = { color: NAVY };
  s.addShape(pres.shapes.RECTANGLE, { x: 0, y: 0, w: W, h: 0.18, fill: { color: GOLD } });
  s.addImage({ data: ic.globe, x: 0.6, y: 0.7, w: 0.85, h: 0.85 });
  s.addText("PARANTOURS  ·  경영분석 보고서", { x: 1.6, y: 0.82, w: 8, h: 0.5, fontFace: KF, fontSize: 14, color: ICE, bold: true, charSpacing: 1, valign: "middle", margin: 0 });
  s.addText("2026년 2분기\n경영실적 분석", { x: 0.6, y: 2.05, w: 11.5, h: 2.0, fontFace: KF, fontSize: 50, color: WHITE, bold: true, lineSpacing: 56, margin: 0 });
  s.addText("예약(선행·실수요) · 수금(후행·현금흐름) 두 기준 동시 진단", { x: 0.62, y: 4.2, w: 11.5, h: 0.5, fontFace: KF, fontSize: 19, color: GOLD, margin: 0 });
  s.addShape(pres.shapes.LINE, { x: 0.62, y: 5.95, w: 4.2, h: 0, line: { color: TEAL, width: 1.5 } });
  s.addText([
    { text: "분석기간  ", options: { color: MUTE, bold: true } },
    { text: "2026.04.01 – 06.08 (전년 동기간 비교)      ", options: { color: ICE } },
    { text: "기준  ", options: { color: MUTE, bold: true } },
    { text: "예약 revDate / 수금 wdate · 취소 제외", options: { color: ICE } },
  ], { x: 0.62, y: 6.15, w: 12.2, h: 0.5, fontFace: KF, fontSize: 12, margin: 0 });
  footer(s);

  // ============ 2 · EXEC SUMMARY (both) ============
  s = pres.addSlide(); s.background = { color: WHITE };
  kicker(s, "Executive Summary"); title(s, "핵심 요약 — 두 기준이 가리키는 서로 다른 신호");
  const cards = [
    { lab: "신규 예약액 (선행)", val: "$3.15M", sub: "$5.67M → −44.5%", c: RED },
    { lab: "결제 수금액 (후행)", val: "$2.98M", sub: "$3.33M → −10.5%", c: RED },
    { lab: "예약 건수 / 인원", val: "963건", sub: "1,496건 −35.6% · 인원 −45%", c: RED },
    { lab: "건당 예약단가", val: "$3,267", sub: "$3,788 → −13.8%", c: RED },
  ];
  const cw = 2.92, gap = 0.22, x0 = 0.5, cy = 1.75, ch = 2.05;
  cards.forEach((c, i) => {
    const x = x0 + i * (cw + gap);
    s.addShape(pres.shapes.RECTANGLE, { x, y: cy, w: cw, h: ch, fill: { color: PANEL }, line: { color: "E2E9F0", width: 1 }, shadow: sh() });
    s.addShape(pres.shapes.RECTANGLE, { x, y: cy, w: cw, h: 0.09, fill: { color: c.c } });
    s.addText(c.lab, { x: x + 0.18, y: cy + 0.22, w: cw - 0.36, h: 0.35, fontFace: KF, fontSize: 12, color: MUTE, bold: true, margin: 0 });
    s.addText(c.val, { x: x + 0.16, y: cy + 0.62, w: cw - 0.3, h: 0.7, fontFace: KF, fontSize: 33, color: NAVY, bold: true, margin: 0 });
    s.addText(c.sub, { x: x + 0.18, y: cy + 1.45, w: cw - 0.32, h: 0.4, fontFace: KF, fontSize: 11, color: c.c, bold: true, margin: 0 });
  });
  s.addShape(pres.shapes.RECTANGLE, { x: 0.5, y: 4.1, w: 12.3, h: 2.55, fill: { color: NAVY } });
  s.addShape(pres.shapes.RECTANGLE, { x: 0.5, y: 4.1, w: 0.12, h: 2.55, fill: { color: GOLD } });
  s.addText("진단 요약", { x: 0.85, y: 4.3, w: 6, h: 0.4, fontFace: KF, fontSize: 16, color: GOLD, bold: true, margin: 0 });
  s.addText([
    { text: "신규 예약(선행): 예약액 −44.5%, 건수 −35.6%, 인원 −45% — 전 부문 동반 급감, 단가도 −13.8%.", options: { bullet: { code: "2022" }, breakLine: true, color: WHITE } },
    { text: "결제 수금(후행): −10.5%로 완만 — 과거 예약분 분할납입이 떠받친 착시, 실수요와 괴리.", options: { bullet: { code: "2022" }, breakLine: true, color: WHITE } },
    { text: "프리미엄(Type 3): 예약 기준 −66.9% 붕괴 vs 수금 기준 +170.8% — 정반대 신호의 대표 사례.", options: { bullet: { code: "2022" }, breakLine: true, color: WHITE } },
    { text: "강달러·관세가 '예약 결정'에서 직접 작용 → 예약 급감은 향후 수금 감소의 예고편.", options: { bullet: { code: "2022" }, color: WHITE } },
  ], { x: 0.85, y: 4.78, w: 11.7, h: 1.75, fontFace: KF, fontSize: 13, color: WHITE, lineSpacingMultiple: 1.18, paraSpaceAfter: 6, margin: 0 });
  footer(s);

  // ============ 3 · DIVIDER PART 1 ============
  divider("PART 1", "예약 기준 — 선행 · 실수요", "고객이 언제 예약했는가 (reserve_info.revDate) · 미래 매출의 씨앗", ic.clip);

  // ============ 4 · 예약액 추이 ============
  s = chartSlide({ kicker: "Booking · Trend", title: "동기간(4/1–6/8) 신규 예약액 추이" });
  yearlyChart(s, [7508603, 6048512, 5666379, 3145739], ["2023", "2024", "2025", "2026"], '$#,##0.00,,"M"');
  statCards(s, [
    { k: "2024 → 2025", v: "-6.3%", c: RED, d: "완만한 조정 국면" },
    { k: "2025 → 2026", v: "-44.5%", c: RED, d: "조정에서 급락으로 전환" },
    { k: "건당 예약단가", v: "-13.8%", c: RED, d: "$3,788 → $3,267" },
  ]);
  note(s, "※ 예약일(revDate) 기준, 예약 단위(MAIN)·취소 제외. 전 연도 동일 구간(4/1–6/8). 2026년은 6/8까지.");
  footer(s);

  // ============ 5 · 월별 예약 ============
  s = chartSlide({ kicker: "Booking · Monthly", title: "월별 신규 예약 — 4·5월 동반 급락" });
  clustered(s, [2635389, 2596571, 434419], [1367092, 1436400, 342247], ["4월", "5월", "6월 (1–8일)"], "col");
  monthCards(s, [
    { m: "4월", d: "-48.1%", c: RED, t: "관세 발표·강달러 충격으로 신규 예약 반토막" },
    { m: "5월", d: "-44.7%", c: RED, t: "반등 없이 위축 지속 — 수요 둔화 고착" },
    { m: "6월(부분)", d: "-21.2%", c: RED, t: "8일까지 집계, 월 전체 미반영" },
  ]);
  footer(s);

  // ============ 6 · 상품유형별 예약 ============
  s = chartSlide({ kicker: "Booking · Product Mix", title: "상품유형별 예약 — 전 부문 감소, 프리미엄 붕괴" });
  clustered(s, [2924516, 71215, 2670648], [2208314, 53340, 884086], ["픽업·출발 패키지", "당일·단기 패키지", "장기 인바운드 프리미엄"], "bar");
  typeCards(s, [
    { t: "Type 3 · 장기 인바운드 프리미엄", d: "-66.9%", c: RED, n: "$2.67M → $0.88M · 선행수요 붕괴, 최대 충격" },
    { t: "Type 1 · 픽업·출발 패키지", d: "-24.5%", c: RED, n: "$2.92M → $2.21M · 예약의 70% 차지" },
    { t: "Type 2 · 당일·단기 패키지", d: "-25.1%", c: RED, n: "$71K → $53K · 소규모, 동반 감소" },
  ]);
  footer(s);

  // ============ 7 · DIVIDER PART 2 ============
  divider("PART 2", "수금 기준 — 후행 · 현금흐름", "실제 결제가 언제 들어왔는가 (payment_history.wdate) · 분할납입 포함", await icon(FaCoins, "#FFFFFF"));

  // ============ 8 · 수금액 추이 ============
  s = chartSlide({ kicker: "Payment · Trend", title: "동기간(4/1–6/8) 확정 수금액 추이" });
  yearlyChart(s, [2975080, 4238403, 3325820, 2977170], ["2023", "2024", "2025", "2026"], '$#,##0.00,,"M"');
  statCards(s, [
    { k: "2024 → 2025", v: "-21.5%", c: RED, d: "코로나 회복 정점 후 조정" },
    { k: "2025 → 2026", v: "-10.5%", c: RED, d: "감소폭 둔화 (후행 착시 주의)" },
    { k: "평균 거래단가", v: "+25.8%", c: GREEN, d: "$1,691 → $2,127" },
  ]);
  note(s, "※ 결제기록일(wdate)·확정결제(DONE) 기준. 분할납입 건이 각각 집계되어 예약 건수와 다름.");
  footer(s);

  // ============ 9 · 월별 수금 ============
  s = chartSlide({ kicker: "Payment · Monthly", title: "월별 수금 — 4월 약세, 5월 반등" });
  clustered(s, [1566250, 1446555, 313015], [1225101, 1473862, 278207], ["4월", "5월", "6월 (1–8일)"], "col");
  monthCards(s, [
    { m: "4월", d: "-21.8%", c: RED, t: "신규 예약 위축이 수금에도 일부 반영" },
    { m: "5월", d: "+1.9%", c: GREEN, t: "기예약분 분할납입 유입으로 전년 수준 회복" },
    { m: "6월(부분)", d: "-11.1%", c: RED, t: "8일까지 집계, 월 전체 미반영" },
  ]);
  footer(s);

  // ============ 10 · 상품유형별 수금 ============
  s = chartSlide({ kicker: "Payment · Product Mix", title: "상품유형별 수금 — 프리미엄 +171% (선결제 유입)" });
  clustered(s, [3052823, 77249, 194096], [2375335, 76187, 525648], ["픽업·출발 패키지", "당일·단기 패키지", "장기 인바운드 프리미엄"], "bar");
  typeCards(s, [
    { t: "Type 3 · 장기 인바운드 프리미엄", d: "+170.8%", c: GREEN, n: "$0.19M → $0.53M · 과거 예약분 선결제 유입" },
    { t: "Type 1 · 픽업·출발 패키지", d: "-22.2%", c: RED, n: "$3.05M → $2.38M · 수금 80% 차지" },
    { t: "Type 2 · 당일·단기 패키지", d: "-1.4%", c: MUTE, n: "$77K → $76K · 보합" },
  ]);
  note(s, "※ Type 3 수금 증가는 예약 증가가 아닌 기예약분 결제 유입 — 9p(예약 기준 −66.9%)와 반드시 함께 해석.");
  footer(s);

  // ============ 11 · 예약 vs 수금 (착시) ============
  s = pres.addSlide(); s.background = { color: WHITE };
  kicker(s, "Leading vs Lagging"); title(s, "예약(선행) vs 수금(후행) — 착시 경계");
  s.addShape(pres.shapes.RECTANGLE, { x: 0.5, y: 1.8, w: 5.9, h: 2.25, fill: { color: NAVY } });
  s.addImage({ data: ic.clip, x: 0.78, y: 2.12, w: 0.5, h: 0.5 });
  s.addText("신규 예약액  (선행·실수요)", { x: 1.45, y: 2.12, w: 4.8, h: 0.5, fontFace: KF, fontSize: 14, color: ICE, bold: true, margin: 0, valign: "middle" });
  s.addText("-44.5%", { x: 1.42, y: 2.55, w: 4.8, h: 0.85, fontFace: KF, fontSize: 48, color: GOLD, bold: true, margin: 0 });
  s.addText("$5.67M → $3.15M · revDate 기준", { x: 0.78, y: 3.52, w: 5.5, h: 0.35, fontFace: KF, fontSize: 11.5, color: ICE, margin: 0 });
  s.addShape(pres.shapes.RECTANGLE, { x: 6.9, y: 1.8, w: 5.9, h: 2.25, fill: { color: PANEL }, line: { color: "E2E9F0", width: 1 } });
  s.addImage({ data: ic.coinDark, x: 7.18, y: 2.12, w: 0.5, h: 0.5 });
  s.addText("결제 수금액  (후행)", { x: 7.85, y: 2.12, w: 4.7, h: 0.5, fontFace: KF, fontSize: 14, color: MUTE, bold: true, margin: 0, valign: "middle" });
  s.addText("-10.5%", { x: 7.82, y: 2.55, w: 4.8, h: 0.85, fontFace: KF, fontSize: 48, color: MUTE, bold: true, margin: 0 });
  s.addText("$3.33M → $2.98M · wdate 기준", { x: 7.18, y: 3.52, w: 5.5, h: 0.35, fontFace: KF, fontSize: 11.5, color: MUTE, margin: 0 });
  s.addShape(pres.shapes.RECTANGLE, { x: 0.5, y: 4.35, w: 12.3, h: 2.25, fill: { color: PANEL }, line: { color: "E2E9F0", width: 1 } });
  s.addShape(pres.shapes.RECTANGLE, { x: 0.5, y: 4.35, w: 0.12, h: 2.25, fill: { color: RED } });
  s.addText("왜 다른가", { x: 0.82, y: 4.55, w: 6, h: 0.4, fontFace: KF, fontSize: 15, color: RED, bold: true, margin: 0 });
  s.addText([
    { text: "수금(결제일)에는 과거에 예약된 건의 분할 납입이 섞여 들어옴 → 신규 수요 둔화를 가림.", options: { bullet: { code: "2022" }, breakLine: true, color: INK } },
    { text: "특히 장기 프리미엄 상품은 선결제 비중이 커, 수금 기준으로는 도리어 +171%로 보이는 착시.", options: { bullet: { code: "2022" }, breakLine: true, color: INK } },
    { text: "경영 판단의 기준은 신규 예약(선행). 예약 −44.5%는 향후 수금·매출 감소의 예고편.", options: { bullet: { code: "2022" }, color: INK } },
  ], { x: 0.82, y: 5.0, w: 11.8, h: 1.5, fontFace: KF, fontSize: 13, color: INK, lineSpacingMultiple: 1.25, paraSpaceAfter: 7, margin: 0 });
  footer(s);

  // ============ 12 · FX ============
  s = pres.addSlide(); s.background = { color: WHITE };
  kicker(s, "FX Environment"); title(s, "환율 환경 — 강달러(USD/KRW)의 압박");
  s.addChart(pres.charts.LINE, [
    { name: "USD/KRW", labels: ["2024 평균", "2026 1월", "2026 4월", "2026 5월", "2026 6월"], values: [1360, 1428, 1505, 1525, 1527] },
  ], {
    x: 0.5, y: 1.75, w: 7.7, h: 4.5, chartColors: [TEAL], lineSize: 3, lineSmooth: true,
    showValue: true, dataLabelColor: NAVY, dataLabelFontFace: KF, dataLabelFontSize: 11, dataLabelFontBold: true, dataLabelPosition: "t", dataLabelFormatCode: "#,##0",
    valAxisMinVal: 1300, valAxisMaxVal: 1600, valAxisHidden: true, valGridLine: { color: "ECF1F6", size: 0.5 },
    catAxisLabelColor: INK, catAxisLabelFontFace: KF, catAxisLabelFontSize: 11.5, catAxisLabelFontBold: true,
    showLegend: false, showTitle: false, chartArea: { fill: { color: WHITE } },
  });
  s.addText("₩1,360 → ₩1,527", { x: 8.45, y: 1.85, w: 4.4, h: 0.55, fontFace: KF, fontSize: 24, color: NAVY, bold: true, margin: 0 });
  s.addText("2024 평균 대비 2026 6월 약 +12% 원화 약세", { x: 8.45, y: 2.45, w: 4.4, h: 0.5, fontFace: KF, fontSize: 11, color: MUTE, margin: 0 });
  const fx = [
    "2026년 2분기 USD/KRW는 1,500~1,548 구간에서 등락 (6월 평균 ≈ 1,527).",
    "원화 약세 → 한국발 미주 여행객의 달러 표시 여행비 부담 상승.",
    "여행 결정(예약) 단계에서 직접 작용 → 신규 예약 급감의 핵심 동인.",
    "달러 표시 매출 기업 특성상 환위험은 낮으나, 수요측 구매력이 핵심 변수.",
  ];
  s.addText(fx.map((t, i) => ({ text: t, options: { bullet: { code: "2022", indent: 14 }, breakLine: i < fx.length - 1, color: INK } })),
    { x: 8.45, y: 3.15, w: 4.4, h: 3.2, fontFace: KF, fontSize: 12, color: INK, lineSpacingMultiple: 1.2, paraSpaceAfter: 8, margin: 0 });
  note(s, "※ 환율 출처: exchangerates.org.uk / Federal Reserve H.10 (2026).");
  footer(s);

  // ============ 13 · POLICY ============
  s = pres.addSlide(); s.background = { color: NAVY };
  s.addText("POLICY ENVIRONMENT", { x: 0.5, y: 0.42, w: 8, h: 0.3, fontFace: KF, fontSize: 11, color: GOLD, bold: true, charSpacing: 2, margin: 0 });
  s.addText("경제정책 환경 — 역풍과 순풍", { x: 0.5, y: 0.72, w: 12.3, h: 0.7, fontFace: KF, fontSize: 29, color: WHITE, bold: true, margin: 0 });
  const head = [
    { ic: ic.scale, t: "관세 정책 충격", d: "4/2 발표 광범위 관세 (실효세율 ≈ 30%). 경제 불확실성 확대.", c: RED, tag: "역풍" },
    { ic: ic.line, t: "성장 둔화·물가", d: "GDP 성장 2.0%→1.4% 둔화, 근원물가 3.9%로 상승 압력.", c: RED, tag: "역풍" },
    { ic: ic.fx, t: "강달러", d: "달러 강세로 미국 여행 가격경쟁력 약화, 해외수요 둔화.", c: RED, tag: "역풍" },
    { ic: ic.ball, t: "월드컵 2026", d: "6–7월 미국 공동개최 — 인바운드 관광 수요 견인 기대.", c: GREEN, tag: "순풍" },
  ];
  const bw = 5.95, bh = 1.95, bx = [0.5, 6.85], by = [1.65, 3.85];
  head.forEach((b, i) => {
    const x = bx[i % 2], y = by[Math.floor(i / 2)];
    s.addShape(pres.shapes.RECTANGLE, { x, y, w: bw, h: bh, fill: { color: NAVY2 }, line: { color: "2B4A6E", width: 1 } });
    s.addShape(pres.shapes.OVAL, { x: x + 0.3, y: y + 0.32, w: 0.95, h: 0.95, fill: { color: WHITE } });
    s.addImage({ data: b.ic, x: x + 0.52, y: y + 0.54, w: 0.5, h: 0.5 });
    s.addText(b.tag, { x: x + bw - 1.25, y: y + 0.28, w: 1.05, h: 0.34, fontFace: KF, fontSize: 11, bold: true, color: WHITE, align: "center", valign: "middle", fill: { color: b.c }, margin: 0 });
    s.addText(b.t, { x: x + 1.45, y: y + 0.32, w: bw - 2.7, h: 0.5, fontFace: KF, fontSize: 17, color: WHITE, bold: true, margin: 0, valign: "middle" });
    s.addText(b.d, { x: x + 1.45, y: y + 0.92, w: bw - 1.7, h: 0.85, fontFace: KF, fontSize: 12, color: ICE, margin: 0, valign: "top", lineSpacingMultiple: 1.1 });
  });
  s.addText("※ 정책·전망 출처: U.S. Travel Association, Oxford/Tourism Economics, congress.gov (2026).", { x: 0.5, y: 6.05, w: 12, h: 0.3, fontFace: KF, fontSize: 9, color: ICE, italic: true, margin: 0 });
  footer(s);

  // ============ 14 · PIPELINE ============
  s = pres.addSlide(); s.background = { color: WHITE };
  kicker(s, "Forward Indicator"); title(s, "선행지표 — 출발일별 예약 잔액");
  s.addChart(pres.charts.BAR, [
    { name: "예약 잔액($)", labels: ["2026 Q2 출발", "2026 Q3 출발", "2026 Q4 출발"], values: [20939166, 8049287, 4914445] },
  ], {
    x: 0.5, y: 1.75, w: 7.7, h: 4.55, barDir: "col", chartColors: [GOLD],
    showValue: true, dataLabelPosition: "outEnd", dataLabelColor: INK, dataLabelFontFace: KF, dataLabelFontSize: 12, dataLabelFontBold: true, dataLabelFormatCode: '$#,##0.0,,"M"',
    valAxisHidden: true, valGridLine: { style: "none" },
    catAxisLabelColor: INK, catAxisLabelFontFace: KF, catAxisLabelFontSize: 12.5, catAxisLabelFontBold: true,
    showLegend: false, showTitle: false, barGapWidthPct: 55, chartArea: { fill: { color: WHITE } },
  });
  const fp = [
    { t: "하반기 잔액 얇음", n: "Q3 $8.0M·Q4 $4.9M — 신규 예약 급감으로 미래 출발 잔액 축적 부진." },
    { t: "월드컵은 상방 변수", n: "6–7월 이벤트가 Q3 출발 예약의 단기 회복 트리거." },
    { t: "누적 진행형 지표", n: "Q3·Q4 잔액은 집계 시점 기준 — 현 예약 속도로는 전년 미달 우려." },
  ];
  s.addText("파이프라인 메모", { x: 8.45, y: 1.8, w: 4.4, h: 0.4, fontFace: KF, fontSize: 15, color: NAVY, bold: true, margin: 0 });
  fp.forEach((r, i) => {
    const yy = 2.35 + i * 1.42;
    s.addShape(pres.shapes.RECTANGLE, { x: 8.45, y: yy, w: 4.35, h: 1.22, fill: { color: PANEL }, line: { color: "E2E9F0", width: 1 } });
    s.addShape(pres.shapes.RECTANGLE, { x: 8.45, y: yy, w: 0.09, h: 1.22, fill: { color: TEAL } });
    s.addText(r.t, { x: 8.68, y: yy + 0.15, w: 4, h: 0.35, fontFace: KF, fontSize: 13, color: INK, bold: true, margin: 0 });
    s.addText(r.n, { x: 8.68, y: yy + 0.52, w: 4, h: 0.62, fontFace: KF, fontSize: 10.5, color: MUTE, margin: 0, valign: "top", lineSpacingMultiple: 1.1 });
  });
  note(s, "※ reserve_info 출발일(stDate)별 예약 총액(취소 제외). 신규 예약 유입으로 변동하는 진행형 지표.");
  footer(s);

  // ============ 15 · CONCLUSIONS ============
  s = pres.addSlide(); s.background = { color: NAVY };
  s.addShape(pres.shapes.RECTANGLE, { x: 0, y: 0, w: W, h: 0.18, fill: { color: GOLD } });
  s.addText("CONCLUSIONS & ACTIONS", { x: 0.6, y: 0.55, w: 8, h: 0.3, fontFace: KF, fontSize: 12, color: GOLD, bold: true, charSpacing: 2, margin: 0 });
  s.addText("종합 진단 및 제언", { x: 0.6, y: 0.9, w: 12, h: 0.7, fontFace: KF, fontSize: 32, color: WHITE, bold: true, margin: 0 });
  s.addText("진단", { x: 0.6, y: 1.95, w: 5.8, h: 0.4, fontFace: KF, fontSize: 17, color: GOLD, bold: true, margin: 0 });
  s.addText([
    { text: "예약(선행) −44.5%, 수금(후행) −10.5% — 두 기준의 괴리가 핵심.", options: { bullet: { code: "2022" }, breakLine: true } },
    { text: "수금은 기예약 분할납입이 방어 중일 뿐, 신규 실수요는 전 부문 급감.", options: { bullet: { code: "2022" }, breakLine: true } },
    { text: "프리미엄(Type 3): 예약 −66.9% vs 수금 +171% → 미래 매출 기반 약화.", options: { bullet: { code: "2022" }, breakLine: true } },
    { text: "강달러·관세가 예약 단계에서 직접 작용, 하반기 출발 잔액도 얇음.", options: { bullet: { code: "2022" } } },
  ], { x: 0.6, y: 2.45, w: 5.85, h: 3.8, fontFace: KF, fontSize: 13.5, color: WHITE, lineSpacingMultiple: 1.25, paraSpaceAfter: 11, margin: 0 });
  s.addShape(pres.shapes.RECTANGLE, { x: 6.85, y: 1.95, w: 5.95, h: 4.55, fill: { color: NAVY2 } });
  s.addShape(pres.shapes.RECTANGLE, { x: 6.85, y: 1.95, w: 0.12, h: 4.55, fill: { color: GOLD } });
  s.addText("제언", { x: 7.15, y: 2.15, w: 5, h: 0.4, fontFace: KF, fontSize: 17, color: GOLD, bold: true, margin: 0 });
  s.addText([
    { text: "1. 예약(선행) KPI 중심 관리 — 수금 착시 배제, 신규 예약을 주간 모니터링.", options: { breakLine: true } },
    { text: "2. 환율 연동 프로모션 — 원화 약세 구간 한국 고객 조기예약·결제 혜택 설계.", options: { breakLine: true } },
    { text: "3. 프리미엄 수요 재점화 — Type 3 붕괴 방어, 월드컵 연계 장기상품 선판매.", options: { breakLine: true } },
    { text: "4. 하반기 잔액 보강 — Q3·Q4 출발 예약 적재 가속으로 매출 공백 최소화.", options: {} },
  ], { x: 7.15, y: 2.7, w: 5.5, h: 3.7, fontFace: KF, fontSize: 13.5, color: WHITE, lineSpacingMultiple: 1.25, paraSpaceAfter: 12, margin: 0 });
  s.addText("PARANTOURS  ·  기준일 2026-06-08  ·  예약(revDate)+수금(wdate) 양 기준 · 데이터: prtadmindb", { x: 0.6, y: 6.95, w: 12.2, h: 0.3, fontFace: KF, fontSize: 9, color: ICE, margin: 0 });
  footer(s);

  await pres.writeFile({ fileName: "2026_2분기_경영실적_분석.pptx" });
  console.log("DONE " + PAGE + " slides");
})();
