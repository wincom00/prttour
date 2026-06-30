# 웹메일 모듈 (메일전용함) 구현 계획

## Context

prttour_myprt 인트라넷은 현재 메일 **발송 전용**이다. 사내에서 메일을 **읽고 쓰는 전용 메일함**을 새 모듈로 만든다.

확정된 요구사항:
- **다중 계정**: 메일 계정을 등록해두고 UI에서 **선택/전환** (기본: online@prttour.com — Google Workspace 호스팅이므로 IMAP `imap.gmail.com:993`, SMTP `smtp.gmail.com:587`. 계정마다 Gmail **앱 비밀번호** 필요)
- **DB 동기화 방식**: IMAP으로 가져와 MySQL에 저장, 목록/검색/읽음표시는 DB에서 처리. 수동 "동기화" 버튼 + cron용 스크립트
- **발송**: 선택된 계정의 Gmail SMTP (보낸편지함에 자동 저장됨 → IMAP 동기화로 반영)
- **별도 모듈**: 새 디렉토리 `mailbox/`, 기존 파일 수정 없음 (마지막에 메뉴 링크 1줄만)
- **리눅스 호환 필수**: php-imap 확장에 의존하지 않음 → `stream_socket_client('ssl://...')` 기반 
- **순수 PHP IMAP 클라이언트** 직접 구현 (openssl만 필요, Windows/Linux 공통)
- **만약에 복잡하지 않다면 외부모듈을 사용해도된다.
- **DB는 lalagon로컬디비를 사용한다.
- **사용자 친화적 UI**: Gmail식 메일함 화면

## 작성 기준 (신규 모듈 코드 스타일)

- **PHP 8.2 모던 스타일**: 클래스 기반(lib/), 타입 선언, `try/catch` 예외 처리
- **DB**: mysqli 직접 사용 — 기존 `include/header.php`(→dbconn.php)가 만든 연결이 `$GLOBALS['mysql_compat_default_link']`에 mysqli 객체로 존재 → 이 링크로 `mysqli_prepare` + `bind_param` **프리페어드 스테이트먼트** 사용
- **인증/레이아웃 재사용**: 페이지는 `include "../include/header.php";` + `$_COOKIE['MEMLOGIN_ADMIN_PURUN']` 쿠키 인증, AJAX는 자체 가드 (참고: `email_hist/news_list.php`, `messenger/get_messages.php`)
- **주의**: `inc_base.php`가 GET/POST를 `extract()`하므로 모든 변수는 `$_GET/$_POST`에서 명시적으로 초기화
- **프론트**: Bootstrap 3.3.1 + jQuery(위임 바인딩 `$(document).on(...)`) + Font Awesome + TinyMCE(`/js/tinymce/tinymce.min.js`)
- **발송**: 동봉 PHPMailer 5.x (`/PHPMailer/class.phpmailer.php`) — `purun_func.php`는 수정하지 않고 모듈 내 자체 발송 함수 작성
- 파일 인코딩: UTF-8, CRLF

## 모듈 구성 — `d:\www\prttour_myprt\mailbox\`

```
mailbox/
├── config.php              # 모듈 상수 (cron 동기화 키, 초기 동기화 건수 300, 첨부 제한 5개×20MB)
├── install.sql             # 스키마 참고용 (실제는 자동 생성)
├── index.php               # 메일 목록 (메인 화면)
├── view.php                # 메일 보기
├── compose.php             # 작성 / 답장 / 전달
├── accounts.php            # 계정 관리 (추가/수정/삭제/연결테스트)
├── test_imap.php           # 0단계 진단 스크립트 (배포 후 비활성화)
├── lib/
│   ├── ImapClient.php      # 순수 PHP IMAP 클라이언트 (ssl 소켓)
│   ├── MimeParser.php      # MIME/헤더/본문/첨부 파싱, 문자셋 변환
│   ├── MailboxSync.php     # 동기화 로직 + 테이블 자동 생성
│   └── common.php          # api/용 부트스트랩: inc_base + 인증 가드 + 현재 계정 결정
├── api/
│   ├── sync.php            # 동기화 (AJAX / CLI / cron ?key=)
│   ├── action.php          # 일괄 작업: 읽음/안읽음/휴지통 (POST 전용)
│   ├── send.php            # 발송 처리 (POST 전용)
│   ├── body.php            # sandbox iframe용 본문 HTML 출력
│   ├── attachment.php      # 첨부 다운로드 프록시 (IMAP 온디맨드)
│   └── account_test.php    # 계정 연결 테스트 (IMAP LOGIN 시도)
└── uploads/                # 발송 첨부 임시 보관 (.htaccess Deny all, 발송 후 삭제)
```

## DB 스키마 (utf8mb4, InnoDB — MailboxSync가 자동 생성)

```sql
mailbox_accounts   -- 계정: id, email(UNIQUE), display_name, imap_host/port,
                   --        smtp_host/port, app_password, is_active, sort_order
mailbox_folders    -- 폴더 상태: account_id, folder_key(inbox/sent/trash), imap_name,
                   --        uidvalidity, last_uid, last_sync
                   --        UNIQUE(account_id, folder_key)
mailbox_messages   -- 메일: account_id, folder_key, uid, message_id, in_reply_to,
                   --        from_name, from_email, to_addr, cc_addr, subject,
                   --        mail_date, snippet, body_html, body_text(MEDIUMTEXT),
                   --        body_fetched, is_read, has_attachment, msg_size, synced_at
                   --        UNIQUE(account_id, folder_key, uid)
                   --        INDEX(account_id, folder_key, mail_date), (account_id, folder_key, is_read)
mailbox_attachments -- 첨부 메타: msg_id, part_no('2','1.2'…), filename, mime_type,
                   --        size_bytes, content_id   ※ 파일 자체는 저장 안 함
```

설계 결정:
- **첨부는 온디맨드**: 메타만 DB에 두고 다운로드 시 IMAP `BODY[part_no]`를 실시간 fetch → 디스크 비대화 없음, Gmail이 원본 보관
- **본문은 lazy**: 동기화 때는 헤더만, 첫 열람 때 본문 fetch 후 DB 캐시 → 초기 동기화 속도/용량 확보
- 계정 비밀번호는 DB 저장 (기존 코드의 하드코딩보다 개선; 추후 암호화 레이어 추가 가능)

## 핵심 클래스 설계

**ImapClient** — 필요한 것만 구현: `connect/login/listFolders/select/uidSearch/uidFetch/uidStore/uidMove(COPY+EXPUNGE 폴백)/logout`. 핵심 난점은 IMAP 리터럴 `{N}\r\n` 응답 처리(정확히 N바이트 읽기) — FETCH 정확성의 관건.

**MimeParser** — 헤더 디코딩(`mb_decode_mimeheader` + iconv 폴백, `ks_c_5601-1987`→CP949 정규화 — 한글 메일 필수), multipart 재귀 파싱(text/html 우선, text/plain은 `nl2br(htmlspecialchars())` 폴백), base64/quoted-printable 디코딩, EUC-KR→UTF-8 변환, 첨부 파트번호 수집, BODYSTRUCTURE 간이 파서(`has_attachment` 판정용).

**MailboxSync** — 계정×폴더(inbox/sent/trash)별:
1. SELECT → UIDVALIDITY 불일치 시 해당 폴더 로컬 리셋
2. 첫 동기화는 최신 300건만, 이후 `UID FETCH last_uid+1:*` 신규분만 — 50건 배치로 헤더 fetch → `INSERT ... ON DUPLICATE KEY UPDATE`
3. 최근 ~500건 범위 `UID FETCH (FLAGS)` → 읽음 상태 반영 + 서버 삭제분 로컬 제거
4. 읽음은 양방향-단순형: 로컬 읽음 → 즉시 `UID STORE +FLAGS \Seen`, 서버 변경 → 다음 동기화 때
5. `GET_LOCK('mbx_sync',0)` 중복 실행 방지, 첫 동기화 `set_time_limit(300)`
6. api/sync.php 호출 경로: 동기화 버튼 AJAX / CLI / cron `?key=` (`hash_equals` 검증)

## UI 설계 (Bootstrap 3, 한글 레이블, Gmail식)

**index.php** — 2단 레이아웃:
- 좌측 `col-sm-2`:
  - **계정 선택 드롭다운** (활성 계정 목록, 선택값은 쿠키 `mbx_account_id` 유지, 변경 시 즉시 reload) + 계정관리(⚙) 링크
  - `편지쓰기` (btn-primary btn-block)
  - 폴더 `nav nav-pills nav-stacked`: 받은편지함(안읽음 수 `badge`) / 보낸편지함 / 휴지통 (fa-inbox / fa-paper-plane / fa-trash)
  - `동기화` 버튼 (클릭 → `fa-refresh fa-spin` → api/sync.php AJAX → 완료 후 reload + "새 메일 N건" 알림)
- 우측 `col-sm-10`:
  - 상단: 검색폼(form-inline, 제목/보낸이 LIKE) + 일괄 버튼(읽음/안읽음/삭제 — 체크 전 disabled)
  - 목록 `table table-hover`: 전체선택 체크박스 / 📎 / 보낸이 / **제목 + 회색 snippet 한 줄** / 날짜(오늘이면 `H:i`, 아니면 `m-d`) / 크기
  - 안읽음 행: `.mbx-unread td{font-weight:bold;background:#f5f8fc}`, 행 클릭으로 열람(체크박스 클릭은 제외)
  - 페이징: 기존 패턴 `$page/$limit=20` + `<ul class="pagination">`, 폴더·검색 파라미터 유지

**view.php** — 제목 + 툴바(답장/전달/삭제/목록), 메타 패널(보낸이·받는이·날짜, 전부 `htmlspecialchars`), 본문은 `<iframe sandbox src="api/body.php?id=N">`(jQuery로 높이 자동 조절), 하단 첨부 목록(파일명+크기 → api/attachment.php).

**compose.php** — 보내는계정 select / 받는사람 / 참조 / 제목 / TinyMCE / 첨부(행 추가 버튼, 5개×20MB) / 보내기·취소. `?reply=N`/`?forward=N`이면 원문 인용(`<hr>` 아래) + `Re:`/`Fwd:` 제목 + In-Reply-To 세팅.

**accounts.php** — 계정 목록 테이블 + 추가/수정 폼 + "연결 테스트" 버튼(api/account_test.php AJAX로 IMAP LOGIN 검증 후 ✓/✗ 표시). 앱 비밀번호 발급 안내 문구 포함.

## 발송 흐름 (api/send.php)

1. 주소 `filter_var(FILTER_VALIDATE_EMAIL)` 검증 (쉼표 구분 다중 수신)
2. 첨부 업로드 → `uploads/Ym/` 랜덤 파일명, `php*` 확장자 차단 + `.htaccess` deny
3. PHPMailer 5.x: `IsSMTP(); SMTPAuth=true; SMTPSecure='tls'; Port=587;` 호스트/계정/앱비밀번호는 선택된 `mailbox_accounts` 행에서. `SetFrom(email, display_name)`, `MsgHTML()`, 답장 시 `AddCustomHeader('In-Reply-To: ...')`
4. 성공: 임시 첨부 삭제 → best-effort sent 동기화 → `index.php?folder=sent` 리다이렉트 + 완료 메시지 / 실패: 입력 보존 재표시 + 오류 표시

## 보안

- 페이지: header.php 쿠키 인증 / api/*: lib/common.php 가드 (JSON 401)
- SQL: 전부 프리페어드 스테이트먼트, id는 `(int)`
- XSS: 표시 필드 전부 `htmlspecialchars`; 메일 HTML은 api/body.php에서만 — `<script>`·`on*` 속성·`javascript:` 제거 + iframe `sandbox` + CSP(`default-src 'none'; img-src http: https: data:; style-src 'unsafe-inline'`)
- 첨부 프록시: 정수 id만 입력받고 파트번호/파일명은 DB에서 → 경로 조작 불가. 파일명 새니타이즈 + `nosniff` + html류는 강제 다운로드
- api/send.php, api/action.php는 POST 전용; cron 키는 `hash_equals`

## 구현 순서 및 검증

1. **(사용자 사전작업)** 대상 Gmail 계정: IMAP 활성화 + 2단계 인증 + 앱 비밀번호 발급
2. `config.php` + `lib/ImapClient.php` + `test_imap.php` — 접속/LOGIN/LIST로 폴더 실명(`[Gmail]/Sent Mail` 등) 확정, 최신 5건 헤더 덤프(한글 메일 포함)로 리터럴 처리 검증
3. `lib/MimeParser.php` — 실제 메일로 제목/본문/multipart/EUC-KR/첨부 파싱 검증
4. `lib/MailboxSync.php` + `api/sync.php` — 첫 동기화 후 Gmail 웹과 건수 대조, 재실행 0건(멱등성), 새 테스트 메일 → 재동기화 반영
5. `accounts.php` + `api/account_test.php` — 계정 등록/연결 테스트
6. `index.php` — 계정 전환/폴더/검색/페이징/안읽음/동기화 버튼
7. `view.php` + `api/body.php` — 열람 시 본문 렌더 + Gmail 웹에서도 읽음 처리 확인, 한글·HTML 메일 렌더, 스크립트 차단
8. `api/attachment.php` — 실제 PDF/이미지 다운로드, 크기 일치
9. `api/action.php` — 일괄 읽음/휴지통 → Gmail 휴지통 반영 확인
10. `compose.php` + `api/send.php` — 첨부 2개 외부 발송 → 수신 확인 → 보낸편지함 동기화 확인
11. **리눅스 배포** — 운영서버 outbound 993/587 확인(test_imap.php), cron `*/10 * * * * php .../mailbox/api/sync.php`, 메뉴 링크 추가, test_imap.php 비활성화

## 핵심 참조 파일 (수정 없음, 패턴 참고만)
- `email_hist/news_list.php` — 페이지/인증/페이징 패턴
- `messenger/get_messages.php` — AJAX JSON 패턴
- `PHPMailer/class.phpmailer.php` — 동봉 PHPMailer 5.x
