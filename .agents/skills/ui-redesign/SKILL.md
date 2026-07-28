---
name: ui-redesign
description: 레거시 PHP 페이지(인보이스·예약내역 등)의 UI를 세련된 문서형 스타일로 재디자인한다. PHP 로직과 섹션 내용은 100% 보존하고 표현(HTML/CSS)만 교체. invoice_page.php 재디자인에서 확립한 디자인 시스템을 다른 페이지에 동일하게 적용할 때 사용.
---

# UI 재디자인 스킬 (문서형 시트 스타일)

레거시 PHP 페이지의 인라인 스타일 범벅 UI를 절제된 문서(인쇄물) 스타일로 재작성한다.
기준 구현: `invoice_page.php` (2026-07 재디자인 완료본). 새 페이지 작업 전 반드시 이 파일을 먼저 읽고 동일한 클래스 체계를 따른다.

## 절대 규칙

1. **내용·로직 100% 보존**: PHP 쿼리, 조건분기, 메일발송, 업로드, JS 함수, 모든 한글 문구와 데이터 필드는 그대로 유지. 바꾸는 것은 HTML 구조와 CSS뿐.
2. **공유 CSS 수정 금지**: `css/invoice-f.css` 등 다른 페이지가 쓰는 파일은 건드리지 않는다. 대신 해당 페이지 `<head>` 뒤에 페이지 전용 `<style>` 블록을 넣어 자체 완결시키고, 기존 공유 CSS `<link>`는 제거한다.
3. **AI티 금지**: 그라데이션, 이모지, 보라색, 과한 둥근모서리(border-radius 2px 이하), 큰 그림자 금지. 절제된 선·여백·타이포그래피로만 표현.
4. **주석 처리된 코드도 보존**: 원본의 `<!-- -->`, `<?php// ?>` 블록은 지우지 말고 그대로 유지 (린터가 `<?php//`에 경고를 내지만 실행에는 문제없음 — 무시).
5. **완료 후 검증**: `php -l 파일명` 문법 검사 + CRLF 줄바꿈 정규화 필수.

## 작업 절차

1. 대상 파일 전체 읽기 + 링크된 CSS 파일 읽기
2. `invoice_page.php`의 `<style>` 블록을 복사해 시작점으로 사용
3. 인라인 스타일 테이블을 아래 클래스 체계로 치환 (colspan 등 표현용 속성은 단순화 가능)
4. PHP 함수 내부에서 echo하는 HTML 행(tr/td)도 같은 클래스로 치환
5. Write 후 CRLF 정규화:
   ```
   python -c "
   data = open('파일명','rb').read()
   data = data.replace(b'\r\n', b'\n').replace(b'\n', b'\r\n')
   open('파일명','wb').write(data)"
   ```
6. `php -l 파일명` 통과 확인
7. 수정된 파일 목록 보고

## 디자인 시스템

### 색상 팔레트
| 용도 | 값 |
|------|-----|
| 본문 텍스트 | `#2b3138` |
| 제목/강조 | `#22303e` |
| 포인트(액센트) — 단 하나만 사용 | `#2b5d8c` (딥네이비), hover `#234c73` |
| 보조 텍스트 | `#45505c`, 흐린 텍스트 `#8b95a1` |
| 페이지 배경 | `#eceef1` |
| 표 헤더/라벨 배경 | `#f6f8fa`, 합계행 배경 `#fbfcfd` |
| 선(굵은) | `#3a4a5c` 2px / 선(중간) `#c9cfd6` / 선(얇은) `#e2e6ea` |
| 시트 테두리 | `#d7dbe0` |
| 마이너스/미수금 | `#b03030` |
| 성공 메시지 | 글자 `#1e7a45`, 배경 `#eef8f1`, 테두리 `#bfe3cc` |

### 폰트
- 본문: `'Nanum Gothic', 'Malgun Gothic', sans-serif` 14px (2026-07 최초 재디자인은 13px였으나, 사용자 요청으로 전체 한 단계 확대함 — 아래 폰트 크기 표 참조)
- 금액/영문 타이틀: `'Montserrat', sans-serif` (숫자 가독성)
- 구글 폰트 로드: `Montserrat:400,600,700`, `Nanum+Gothic:400,700,800`

### 폰트 크기 기준값 (2026-07 확대 반영)
| 요소 | 값 |
|------|-----|
| 본문/표(`body`, `table.tbl`) | 14px |
| 섹션 헤더 `.book_header` | 16px |
| 페이지 타이틀 `.page-title h2` | 23px |
| INVOICE 타이틀 `.invoice-title` | 28px |
| 완료 배너 `.confim_book` | 16px |
| 표 영문 부제 `thead th h6` | 11px |
| 합계행 금액 `tr.row-total td.amount` | 16px |
| 인보이스 소제목 `.invoice-to h2.invoice-to` / `.tour-details h2.invoice-to` | 13px / 14px |
| 고객명 `.invoice-to h2.no-color` | 18px |
| 예약번호 배지 `.invoice-details .invoice-id` | 14px |
| 버튼 `.btn-tool` | 13px |

값은 절대 기준이 아니라 **비율**이 핵심이다 — 새 페이지에 적용할 때도 본문:섹션헤더:타이틀 = 14:16:23~28의 위계를 유지하고, 사용자가 "글자를 더 키워달라"고 하면 이 표 전체를 한 단계씩(보통 1px) 같이 올린다.

### 레이아웃 골격   
- **시트 카드** `.sheet`: `max-width:920px; margin:22px auto; background:#fff; border:1px solid #d7dbe0; box-shadow:0 1px 4px rgba(30,40,55,.07); padding:38px 44px 46px;` — 논리적 단위(예약내역/인보이스)마다 시트 하나
- **페이지 타이틀** `.page-title h2`: 중앙, `letter-spacing:10px`, 밑줄 `2px solid #22303e`
- **섹션 헤더** `.book_header`: 16px 800, 왼쪽 `3px solid #2b5d8c` 액센트 바 + 아래 `1px solid #e3e7ec`
- **인보이스 소제목** `.tour-details h2.invoice-to`: 14px 800, 동일한 액센트 바 패턴
- 모바일: `@media(max-width:767px)`에서 시트 패딩·마진 축소

### 공통 테이블 `.tbl`
- `border-collapse:collapse; border-top:2px solid #3a4a5c; border-bottom:1px solid #c9cfd6;` — **좌우 세로선 없음**, 가로선 위주
- 셀: `border:1px solid #e2e6ea; border-left/right:none; padding:8px 12px;`
- 라벨 셀 `td.label`: 배경 `#f6f8fa`, 가운데 정렬, `white-space:nowrap`, 오른쪽 세로선만 유지
- thead th: 배경 `#f6f8fa`, 아래 `1px solid #c9cfd6`
- 영문 부제: `thead th h6` — 11px, `#8b95a1`, `text-transform:uppercase`, `letter-spacing:.5px` (원본의 `<font size=1>` 태그는 제거하고 h6만 남김)
- 유틸: `.cell-c`(중앙) `.cell-r`(우측) `.cell-strong`(굵게) `.cell-num`(회색 번호)
- **금액 셀** `.amount`: 우측 정렬 + Montserrat + `white-space:nowrap`
- **합계행** `tr.row-total`: 위 `2px solid #3a4a5c`, 배경 `#fbfcfd`, 금액 16px
- 소계 `tr.row-sub`, 미수금 `tr.row-due`(빨강 `#b03030`)

### 버튼 `.btn-tool`
- BS4에 없는 `btn-xs btn-default` 사용 금지
- 기본(아웃라인): `border:1px solid #b9c0c9; border-radius:2px; padding:7px 18px; font-weight:700;`
- 주요 동작 `.primary`: `background:#2b5d8c; color:#fff;`

### 기타 요소
- **완료 배너** `.confim_book`: 파랑 채움 밴드 대신 위 `1px solid #2b5d8c` + 아래 얇은 선 + 배경 `#f7fafc`
- **성공 메시지** `.send-ok`: `<font color=red>` 대신 녹색 박스
- **첨부파일 폼** `.attach-box`: 테이블 대신 `.file-row` 리스트 (label + 얇은 구분선)
- **INVOICE 타이틀** `.invoice-title`: Montserrat 28px 700, `letter-spacing:8px`
- **예약번호 배지** `.invoice-details .invoice-id`: 회색 박스(`#f6f8fa` + 테두리)
- **헤더 이미지**: 강제 height 속성 제거, `width:100%; height:auto`. **반드시 `https://`로 로드** — 관리자 사이트는 https로 서빙되는데 이미지가 `http://`면 브라우저가 mixed content로 차단해 깨진 이미지 아이콘이 뜬다. 새 페이지에 이미지 넣을 때마다 프로토콜부터 확인할 것
- **인쇄 용지 크기**: `@media print { @page { size: letter; ... } }` 명시. 지정하지 않으면 브라우저가 시스템 기본 용지(대개 A4)를 그대로 써버려 미국/캐나다 고객 대상 문서인데 A4로 인쇄되는 문제가 생긴다

## 관련 파일

- 기준 구현: `invoice_page.php`(화면), `invoice_p.php`(프린트), `invoice_m.php`(이메일)
- 적용 후보: `invoice_hpage.php`(호텔), `estimate_*.php`

## 매체별 변형

- **화면용**: 회색 배경(`#eceef1`) + `.sheet` 카드, `<style>` 블록 사용
- **프린트용** (`invoice_p.php` 참조): 배경 흰색, 시트 테두리·그림자 없음, `-webkit-print-color-adjust:exact`로 라벨 배경 유지, `page-break-before:always` 유지
  - **용지 크기**: `@page { size: letter; ... }` 반드시 명시 (미지정 시 브라우저 기본 A4로 인쇄됨)
  - 브라우저 머리글/바닥글(URL·날짜) 숨기기: `@page { margin: 0 16mm }`(상하 0) + 본문 전체를 `table.print-frame`으로 감싸 `thead`/`tfoot` 반복 출력으로 페이지마다 상하 여백 확보 (`thead td` 높이 14mm / `tfoot td` 11mm, 화면에서는 0)
  - `page-break-inside: avoid`는 `table.tbl tr`과 취소규정 **마지막 인사말 행(`tr:last-child`)에만** 적용. DB 취소규정(`html_page.in_1`)에는 여러 페이지 분량의 초대형 셀이 있어서 전체 tr/td에 걸면 큰 빈 공간·빈 페이지가 생긴다
  - **취소규정을 감싼 Bootstrap `.row`(`display:flex`) 필수 처리**: 인쇄 시 `.terms-body .row { display: block !important; }`로 전환할 것. flex 컨테이너 안에 여러 페이지 분량의 콘텐츠가 있으면 `print-frame` 테이블과의 페이지 조각화(fragmentation)가 충돌해 **문서 맨 끝에 원인불명의 빈 페이지**가 생긴다. 이 버그는 눈으로 봐서는 원인을 알기 어려우니, 재현이 필요하면 실제 페이지를 CLI로 렌더링 → 헤드리스 크롬으로 PDF 인쇄 → 페이지 수/텍스트 비교하는 방식으로 이분탐색해서 원인 요소를 좁힐 것
  - 취소규정처럼 매체 전체를 요약하기 힘든 대용량 DB 콘텐츠는 `!important`로 인쇄 시에만 살짝 압축(글자 12~13px, 행간 1.55)해 페이지 수를 줄일 수 있다. DB 원본은 건드리지 말고 PHP 출력 직전에 정규식으로 꼬리 공백만 다듬는 정도로 최소 개입
- **이메일용** (`invoice_m.php` 참조): 메일 클라이언트는 `<style>`·flex·구글폰트를 지원하지 않으므로 **모든 스타일을 인라인**으로. PHP 상단에 `$st_tbl`, `$st_label`, `$st_val`, `$st_th`, `$st_h6`, `$st_c`, `$st_amt`, `$st_head`, `$st_total` 스타일 문자열 변수를 정의해 `style="<?=$st_label?>"` 형태로 재사용. 레이아웃은 flex 대신 테이블 사용. `{ADDINFO}` 같은 치환 플레이스홀더는 절대 건드리지 않는다.

## 이미지 체크리스트

새 페이지를 재디자인할 때마다 `<img src>`를 전부 점검한다:
1. `http://`로 박혀있으면 `https://`로 교체 (관리자 사이트가 https로 서빙되는 경우 mixed content 차단으로 깨진 이미지가 뜬다)
2. `curl -s -o /dev/null -w "%{http_code}"`로 실제 200을 반환하는지 확인
3. 주석(`<!-- -->`) 안에 죽어있는 `<img>`는 손대지 않는다 (규칙 4 — 주석 코드 보존)
4. 헤더 로고류는 `width:100%; height:auto; display:block`으로 비율 유지, 강제 `height="64px"` 같은 속성은 CSS로 대체
