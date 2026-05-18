# CLAUDE.md — PRTTOUR MyPRT

## 프로젝트 개요

여행사(투어) 예약 및 ERP 관리 시스템. PHP 절차형 코드 + jQuery + Bootstrap 기반의 레거시 웹 애플리케이션.

- **레거시**: https://myprt.org 
- **도메인**: https://myprt.biz
- **로컬 개발**: http://localhost (Laragon)
- **언어**: 한국어 UI, PHP + HTML + JavaScript
- **DB**: MySQL (`prtadmindb`, 호스트 98.91.65.48)

---

## 아키텍처 & 주요 파일

### 핵심 파일
| 파일 | 역할 |
|------|------|
| `include/dbconn.php` | DB 연결 (mysql_* 레거시 API) |
| `include/inc_base.php` | 앱 부트스트랩 (세션, 상수, 공통 require) |
| `include/func_list.php` | 전역 함수, 메뉴 권한 제어, SMTP 설정 |
| `include/c_misc_inc.php` | 공통 유틸리티 클래스 (`c_misc`) |
| `include/header.php` | HTML 공통 헤더 |

### 주요 모듈 (루트 레벨 PHP 파일들)
- **예약**: `base_reservation*.php`, `base_reservation_m*.php`
- **상품**: `base_product*.php`, `base_product_m*.php`
- **가이드**: `base_guide*.php`
- **상담**: `base_consult*.php`
- **청구서/인보이스**: `invoice_*.php`
- **견적**: `estimate_*.php`
- **정산/결제**: `paysettle.php`, `payment_history.php`
- **직원**: `emp_*.php`, `inout_*.php`
- **뮤지컬/이벤트**: `musical_*.php`, `event_*.php`

### 패턴
- `*_list.php` → 목록 조회 페이지
- `*_m.php` → 등록/수정 폼 (modal or page)
- `*_regi.php` → 저장/처리 로직
- `get_*.php` → AJAX JSON 응답 엔드포인트

---

## 코드 스타일 & 관례

### PHP
- **절차형 PHP** (클래스 최소화, 전역 함수 사용)
- 네이밍: snake_case 우선, 일부 camelCase 혼용
- DB 쿼리: 레거시 `mysql_query()` 사용 (MySQLi/PDO로 마이그레이션 금지 — 전면 수정 불가)
- `extract($_GET)`, `extract($_POST)` 패턴 광범위 사용 중 (변경 주의)
- 에러 출력 켜져 있음 (E_ALL ^ E_NOTICE ^ E_WARNING)

### HTML/JS
- Bootstrap 3.x 기반
- jQuery 사용 (vanilla JS 지양)
- AJAX는 `$.ajax()` 또는 `$.post()` 방식
- PHP 파일 내에 HTML 직접 embed (템플릿 엔진 없음)

### DB 쿼리 작성 시
- 기존 `mysql_query($sql)` 형식 유지
- 한글 데이터 처리 시 charset utf8mb4 확인
- 타임존: America/New_York (쿼리 내 NOW() 사용 시 주의)

---

## 개발 환경

- **로컬 서버**: Laragon (Apache + PHP)
- **PHP 버전**: 7.4+ (호환 레이어 `include/php74_82_compat.php` 존재)
- **패키지 관리**: Composer 미사용 (composer.json 비어 있음), 라이브러리 직접 포함
- **테스트**: 자동화 테스트 없음

### 주요 라이브러리 (`/lib/`)
- DataTables, jQuery UI, Select2, Bootstrap-datepicker
- CKEditor, TinyMCE
- PHPExcel (Excel 내보내기)
- dompdf (`/vendor/`) — PDF 생성

---

## 인증 & 세션

- 쿠키 기반: `MEMLOGIN_ADMIN_PURUN`
- 로그인 후 세션 변수 `$_SESSION['member_*']` 사용
- 비밀번호 만료 26일 주기 강제
- 실패 3회 계정 잠금
- 메뉴 접근 제어: `func_list.php`의 권한 함수로 처리

---

## 작업 시 주의사항

1. **DB API 변경 금지**: `mysql_*` → MySQLi/PDO 전환은 전체 파일에 영향. 요청 없으면 건드리지 않는다.
2. **extract() 패턴 유지**: 변수 오염 위험이 있지만 기존 로직이 이에 의존. 수정 시 변수 충돌 여부 반드시 확인.
3. **파일 크기 주의**: `base_reservation_m.php`는 242KB. 수정 시 해당 섹션만 정확히 타겟.
4. **한글 인코딩**: 파일 저장 시 UTF-8 (BOM 없음) 유지.
5. **테스트 없음**: 변경 후 브라우저에서 직접 확인 필요. 사이드 이펙트 최소화.
6. **보안 이슈 인지**: 하드코딩된 DB/이메일 credentials, CSRF 없음 — 현재 구조상 의도된 것으로 보임. 새 코드 작성 시에도 기존 패턴 따름.
7. **수정시 주의사항** : 있는 기능을 살려주고 함부로 수정하지 않는다.

---

## 자주 쓰는 패턴

### DB 조회
```php
$sql = "SELECT * FROM reserve_info WHERE idx='$idx'";
$result = mysql_query($sql);
$row = mysql_fetch_array($result);
```

### AJAX 엔드포인트 (get_*.php)
```php
header('Content-Type: application/json');
echo json_encode($data);
exit;
```

### 공통 include 순서
```php
require_once("include/inc_base.php");
require_once("include/func_list.php");
```

### 권한 체크
```php
if (!check_auth($auth_code)) {
    header("Location: index.php");
    exit;
}
```
