# [Codex 작업 명세] 웹메일 모듈 (메일전용함) 구현

> 이 문서는 단독 실행 가능한 구현 명세다. 아래 내용 외 추가 컨텍스트 없이 이 문서만으로 구현한다.
> 프로젝트 루트: 이 파일이 있는 디렉토리 (PHP 인트라넷, Apache/Laragon 로컬 + Linux 운영).

## 1. 목표

사내 인트라넷에 메일을 **읽고 쓰는 전용 메일함** 모듈을 새 디렉토리 `mailbox/`로 추가한다.

- **다중 계정**: 메일 계정을 DB에 등록해두고 UI에서 선택/전환. 기본 계정 `online@prttour.com` (Google Workspace → IMAP `imap.gmail.com:993 SSL`, SMTP `smtp.gmail.com:587 STARTTLS`, Gmail **앱 비밀번호** 사용)
- **DB 동기화 방식**: IMAP으로 메일을 가져와 MySQL에 저장. 목록/검색/읽음표시/페이징은 DB에서 처리. 수동 "동기화" 버튼 + cron용 엔드포인트
- **발송**: 선택된 계정의 Gmail SMTP (Gmail이 보낸편지함에 자동 저장 → IMAP 동기화로 반영됨)
- **리눅스 호환 필수**: php-imap 확장 사용 금지. `stream_socket_client('ssl://host:993')` 기반 **순수 PHP IMAP 클라이언트**를 직접 구현 (openssl 스트림만 사용)
- **기존 파일 수정 금지** (메뉴 링크 추가도 이번 작업 범위 밖)
- UI는 Gmail식, 한글 레이블, 사용자 친화적

## 2. 코드 작성 기준

- **PHP 8.2 모던 스타일**: lib/는 클래스 기반, 파라미터/리턴 타입 선언, 예외는 try/catch. 레거시 `mysql_*` 함수 **사용 금지**
- **DB 접근**: `include/dbconn.php`가 만든 mysqli 연결을 재사용한다.
  - 페이지(`include "../include/header.php"`) 또는 `include "../include/inc_base.php"` 후 전역 `$dbConn`이 **mysqli 객체**다 (`mysql_compat.php`의 mysql_connect()가 mysqli_connect()를 반환하므로). `$GLOBALS['mysql_compat_default_link']`에도 같은 객체가 있음.
  - 모든 쿼리는 `mysqli_prepare($dbConn, ...)` + `bind_param` 프리페어드 스테이트먼트. id류는 `(int)` 캐스팅.
- **인증**: 관리자 로그인 쿠키 `$_COOKIE['MEMLOGIN_ADMIN_PURUN']` 비어있으면 차단.
  - 페이지: `include "../include/header.php";` 후 쿠키 체크, 미로그인 시 `<meta http-equiv='refresh' content='0; url=./login.php'>` + exit (참고 패턴: `email_hist/news_list.php` 상단)
  - API: `include "../include/inc_base.php";` + `header('Content-Type: application/json')` + 쿠키 체크 후 JSON 에러 반환 (참고 패턴: `messenger/get_messages.php`)
- **주의**: `include/inc_base.php`가 GET/POST를 `extract()`하여 전역 변수를 오염시킨다. 모든 변수는 반드시 `$_GET/$_POST/$_COOKIE`에서 명시적으로 초기화하고, 미리 선언 안 된 변수를 신뢰하지 말 것.
- **프론트**: Bootstrap 3.3.1(`/bootstrap/`), jQuery(`/js/jquery.min.js`), Font Awesome, TinyMCE(`/js/tinymce/tinymce.min.js`). 페이지 본문은 `<div id="contentwrapper"><div class="main_content">` 레이아웃으로 감싸고 마지막에 `include "../include/side_m.php"` + `</body></html>` (참고: `email_hist/news_list.php` 구조 그대로)
- **jQuery만 사용** (vanilla JS 금지), 동적 요소는 `$(document).on('이벤트', '선택자', handler)` 위임 바인딩
- **발송**: 동봉된 PHPMailer **5.x** (`/PHPMailer/class.phpmailer.php`, `class.smtp.php`) 사용. API가 옛날식이다: `new PHPMailer(true); $mail->IsSMTP(); $mail->SMTPAuth=true; $mail->SMTPSecure='tls'; $mail->Host=...; $mail->Port=587; $mail->CharSet='utf-8'; $mail->SetFrom(...); $mail->MsgHTML($html); $mail->AddAddress(...); $mail->AddAttachment($path,$name); $mail->Send();` (기존 사용 예: `include/purun_func.php`의 mailsend_k() — **이 파일은 수정하지 말 것**, 참고만)
- 파일 인코딩: UTF-8, 줄바꿈 CRLF

## 3. 디렉토리/파일 구성 (전부 신규)

```
mailbox/
├── config.php              # 모듈 상수: MBX_SYNC_KEY(cron 키), MBX_INITIAL_SYNC_LIMIT=300,
│                           #   MBX_MAX_ATTACH=5, MBX_MAX_ATTACH_SIZE=20MB, 폴더 매핑
├── install.sql             # 스키마 참고용 (실행은 자동 생성이 담당)
├── index.php               # 메일 목록 (메인)
├── view.php                # 메일 보기
├── compose.php             # 작성/답장/전달
├── accounts.php            # 계정 관리 (목록+추가/수정/삭제+연결테스트)
├── test_imap.php           # 진단: 접속/LOGIN/LIST/최신5건 헤더 덤프 (쿠키 인증 필수)
├── lib/
│   ├── ImapClient.php      # 순수 PHP IMAP 클라이언트
│   ├── MimeParser.php      # MIME 파싱/문자셋 변환
│   ├── MailboxSync.php     # 동기화 + 테이블 자동 생성
│   └── common.php          # api/ 공용 부트스트랩 (inc_base + 인증가드 + 계정 결정)
├── api/
│   ├── sync.php            # 동기화 (AJAX/CLI/cron ?key=)
│   ├── action.php          # 일괄: 읽음/안읽음/휴지통 이동 (POST 전용)
│   ├── send.php            # 발송 (POST 전용)
│   ├── body.php            # iframe용 본문 HTML 출력
│   ├── attachment.php      # 첨부 다운로드 (IMAP 온디맨드)
│   └── account_test.php    # 계정 IMAP LOGIN 테스트
└── uploads/
    └── .htaccess           # "Require all denied" (Apache 2.4) + "Deny from all" (2.2 겸용)
```

폴더 매핑 (config.php):
```php
$MBX_FOLDERS = ['inbox' => 'INBOX', 'sent' => '[Gmail]/Sent Mail', 'trash' => '[Gmail]/Trash'];
```
(실제 폴더명은 test_imap.php의 LIST 결과로 확인 후 필요 시 수정 — 그래서 config에 둔다)

## 4. DB 스키마 (utf8mb4, InnoDB — MailboxSync::ensureTables()가 CREATE TABLE IF NOT EXISTS로 자동 생성, install.sql에도 동일 내용 기록)

```sql
CREATE TABLE IF NOT EXISTS mailbox_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) DEFAULT '',
  imap_host VARCHAR(100) NOT NULL DEFAULT 'imap.gmail.com',
  imap_port INT NOT NULL DEFAULT 993,
  smtp_host VARCHAR(100) NOT NULL DEFAULT 'smtp.gmail.com',
  smtp_port INT NOT NULL DEFAULT 587,
  app_password VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE KEY uk_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mailbox_folders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT NOT NULL,
  folder_key VARCHAR(20) NOT NULL,
  imap_name VARCHAR(100) NOT NULL,
  uidvalidity BIGINT UNSIGNED NOT NULL DEFAULT 0,
  last_uid BIGINT UNSIGNED NOT NULL DEFAULT 0,
  last_sync DATETIME NULL,
  UNIQUE KEY uk_acct_folder (account_id, folder_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mailbox_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT NOT NULL,
  folder_key VARCHAR(20) NOT NULL,
  uid BIGINT UNSIGNED NOT NULL,
  message_id VARCHAR(255) NOT NULL DEFAULT '',
  in_reply_to VARCHAR(255) NOT NULL DEFAULT '',
  from_name VARCHAR(255) NOT NULL DEFAULT '',
  from_email VARCHAR(255) NOT NULL DEFAULT '',
  to_addr TEXT NULL,
  cc_addr TEXT NULL,
  subject VARCHAR(500) NOT NULL DEFAULT '',
  mail_date DATETIME NULL,
  snippet VARCHAR(300) NOT NULL DEFAULT '',
  body_html MEDIUMTEXT NULL,
  body_text MEDIUMTEXT NULL,
  body_fetched TINYINT(1) NOT NULL DEFAULT 0,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  has_attachment TINYINT(1) NOT NULL DEFAULT 0,
  msg_size INT UNSIGNED NOT NULL DEFAULT 0,
  synced_at DATETIME NULL,
  UNIQUE KEY uk_acct_folder_uid (account_id, folder_key, uid),
  KEY idx_list (account_id, folder_key, mail_date),
  KEY idx_read (account_id, folder_key, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mailbox_attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  msg_id INT NOT NULL,
  part_no VARCHAR(20) NOT NULL,
  filename VARCHAR(255) NOT NULL DEFAULT '',
  mime_type VARCHAR(100) NOT NULL DEFAULT '',
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  content_id VARCHAR(255) NOT NULL DEFAULT '',
  KEY idx_msg (msg_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

설계 결정 (변경 금지):
- **첨부 파일 본체는 저장하지 않는다.** 메타(파트번호/파일명/크기)만 DB에 두고, 다운로드 시 `attachment.php`가 IMAP `UID FETCH uid (BODY[part_no])`로 실시간 가져와 스트리밍한다.
- **본문은 lazy fetch.** 동기화는 헤더만 저장(body_fetched=0). view.php 첫 열람 때 `BODY.PEEK[]` 전체를 가져와 파싱·저장하고 `UID STORE +FLAGS \Seen` + is_read=1.
- 계정 비밀번호(앱 비밀번호)는 DB 평문 저장 (사내 인트라넷, 기존 관행 대비 개선. 추후 암호화 가능)

## 5. lib/ImapClient.php 명세

ssl 스트림 소켓 기반. 필요한 명령만 구현:

```php
final class ImapClient {
    public function __construct(string $host, int $port, int $timeout = 30)
    public function connect(): void            // ssl:// 소켓 열기, 그리팅(* OK) 읽기
    public function login(string $user, string $pass): void   // LOGIN, 비밀번호는 quoted-string 이스케이프
    public function listFolders(): array        // LIST "" "*"  → 폴더명 배열
    public function select(string $folder): array // SELECT → ['uidvalidity'=>, 'uidnext'=>, 'exists'=>]
    public function uidSearch(string $criteria): array  // UID SEARCH → UID 배열
    public function uidFetch(string $set, string $items): array // UID별 [항목명 => 값] 맵
    public function uidStore(string $set, string $op, string $flags): void
    public function uidMove(string $set, string $folder): void // UID MOVE 시도, 미지원시 COPY + STORE \Deleted + EXPUNGE
    public function logout(): void
}
```

구현 핵심 (정확성의 관건):
- 태그드 명령: `A001 SELECT "INBOX"\r\n` 식으로 증가하는 태그. 응답은 태그 줄(`A001 OK ...`)이 나올 때까지 누적 수집. `NO`/`BAD`면 RuntimeException.
- **IMAP 리터럴 처리**: 응답 줄이 `{N}\r\n`으로 끝나면 이어서 **정확히 N바이트**를 `fread` 루프로 읽고(부분 읽기 대비) 같은 논리 줄에 이어붙인 뒤 다음 줄 계속. FETCH 본문/헤더가 전부 리터럴로 온다.
- FETCH 응답 파싱: `* 12 FETCH (UID 345 FLAGS (\Seen) RFC822.SIZE 1234 BODY[HEADER.FIELDS (...)] {N}...)` — UID 기준으로 항목 맵 구성. 괄호 중첩과 quoted-string을 고려한 토크나이저로 파싱.
- 타임아웃: `stream_set_timeout`, 끊김 시 예외.

## 6. lib/MimeParser.php 명세

```php
final class MimeParser {
    public static function decodeHeader(string $raw): string
    // =?...?= MIME 인코딩 헤더 디코딩. mb_decode_mimeheader 우선,
    // iconv_mime_decode(..., ICONV_MIME_DECODE_CONTINUE_ON_ERROR) 폴백.
    // 'ks_c_5601-1987'은 CP949로 정규화 후 처리 (한글 메일 필수)

    public static function parseAddressList(string $raw): array
    // "이름 <a@b.c>, x@y.z" → [['name'=>, 'email'=>], ...]

    public static function parseMessage(string $raw): array
    // 원문 전체 → ['headers'=>[], 'body_html'=>, 'body_text'=>, 'attachments'=>[
    //   ['part_no'=>'2'|'1.2', 'filename'=>, 'mime_type'=>, 'size'=>, 'content_id'=>], ...]]
    // - 헤더/본문 분리 후 multipart boundary 재귀 파싱
    // - 표시 본문: text/html 우선, 없으면 text/plain → nl2br(htmlspecialchars()) 변환
    // - Content-Transfer-Encoding: base64 / quoted-printable 디코딩
    // - charset → UTF-8 변환 (mb_convert_encoding, EUC-KR/CP949/ks_c_5601-1987 별칭 처리)
    // - 파트번호는 IMAP BODY[...] 규칙과 동일하게 부여 (최상위부터 1, 중첩은 1.1, 1.2 ...)

    public static function hasAttachmentFromBodyStructure(string $bodystructure): bool
    // BODYSTRUCTURE 문자열에서 attachment disposition 또는 filename 존재 여부 간이 판정

    public static function makeSnippet(string $html_or_text, int $len = 200): string
}
```

## 7. lib/MailboxSync.php 명세

```php
final class MailboxSync {
    public function __construct(mysqli $db, array $account /* mailbox_accounts 행 */, array $folderMap)
    public static function ensureTables(mysqli $db): void   // 위 스키마 CREATE TABLE IF NOT EXISTS
    public function syncAll(): array        // 폴더별 syncFolder, ['inbox'=>신규건수, ...] 반환
    public function syncFolder(string $folderKey): int
    public function fetchBody(int $messageRowId): array     // lazy 본문 fetch + 저장 + \Seen
    public function markRead(array $rowIds, bool $read): void   // DB + UID STORE ±\Seen
    public function moveToTrash(array $rowIds): void        // UID MOVE → folder_key='trash'로 DB 갱신
}
```

syncFolder 알고리즘:
1. SELECT 폴더 → uidvalidity/uidnext 획득. DB의 uidvalidity와 불일치하면 해당 (account_id, folder_key)의 messages/attachments 삭제, last_uid=0 리셋
2. last_uid==0(첫 동기화)이면 `UID SEARCH ALL` 결과의 **최신 MBX_INITIAL_SYNC_LIMIT건만**, 아니면 `last_uid+1:*` 신규분만
3. 50건 단위 배치로 `UID FETCH n1,n2,... (FLAGS RFC822.SIZE BODYSTRUCTURE BODY.PEEK[HEADER.FIELDS (FROM TO CC SUBJECT DATE MESSAGE-ID IN-REPLY-TO)])` → MimeParser로 디코딩 → `INSERT ... ON DUPLICATE KEY UPDATE` (헤더만, body_fetched=0). mail_date는 Date 헤더를 `strtotime` → DB DATETIME (실패 시 NULL)
4. 플래그/삭제 반영: 로컬 최근 500건의 최소 UID부터 `UID FETCH min:* (FLAGS)` → `\Seen`→is_read 갱신, 그 범위에서 응답에 없는 로컬 UID는 서버 삭제분이므로 로컬 행 삭제
5. mailbox_folders의 last_uid/uidvalidity/last_sync 갱신
6. 동시 실행 방지: `SELECT GET_LOCK('mbx_sync_{account_id}', 0)` — 0이면 "이미 동기화 중" 응답. 끝나면 RELEASE_LOCK
7. 첫 동기화 대비 `set_time_limit(300)`

## 8. api/ 명세 (모두 lib/common.php 선두 include)

**lib/common.php**: `session_start()` 없이 `include "../../include/inc_base.php";` (api/는 두 단계 위) → 쿠키 `MEMLOGIN_ADMIN_PURUN` 비면 `http_response_code(401)` + JSON 에러 + exit → config.php, lib/*.php require → 현재 계정 결정 함수 `mbx_current_account(mysqli $db): ?array` (쿠키 `mbx_account_id` 우선, 없거나 무효면 is_active=1 중 sort_order 첫 계정)

- **sync.php**: GET/CLI. ① 웹: 쿠키 인증 또는 `?key=` 가 `hash_equals(MBX_SYNC_KEY, $_GET['key'])` 일치 ② CLI(`php_sapi_name()==='cli'`): 인증 생략, 전 계정 순회. 응답 `{"status":"success","new":{"inbox":3,...}}`
- **action.php**: POST `ids[]`(int 배열) + `op`(read|unread|trash). markRead/moveToTrash 호출. JSON 응답
- **send.php**: POST `account_id, to, cc, subject, body(HTML), attach[](files), in_reply_to(optional), reply_to_id(optional)`. §10 발송 흐름 참조. 성공 시 `{"status":"success"}` (compose.php가 AJAX 제출) 또는 폼 제출이면 리다이렉트 — **폼 일반 제출 + 리다이렉트 방식으로 단순화해도 됨** (구현 단순도 우선)
- **body.php**: GET `id`(int). 해당 메시지 body_fetched==0이면 MailboxSync::fetchBody 먼저. body_html(없으면 body_text 변환본)에서 `<script>` 태그, `on*=` 속성, `javascript:` URL 제거 후 출력. 응답 헤더: `Content-Type: text/html; charset=utf-8`, `Content-Security-Policy: default-src 'none'; img-src http: https: data: cid:; style-src 'unsafe-inline'`, `X-Content-Type-Options: nosniff`
- **attachment.php**: GET `id`(int, mailbox_attachments.id). 메타를 DB에서 읽고 IMAP에서 `UID FETCH uid (BODY[part_no])` → transfer-encoding 디코딩 → `Content-Disposition: attachment; filename="..."` (파일명에서 `\r\n"\\/` 제거), nosniff, text/html류는 application/octet-stream으로 강제
- **account_test.php**: POST `account_id` 또는 `host/port/user/pass` 직접 → ImapClient connect+login 시도 → `{"status":"success"}` / 실패 메시지

## 9. 페이지 명세 (Bootstrap 3, 한글)

공통 골격 — `email_hist/news_list.php`와 동일:
```php
<?php
include "../include/header.php";
$loginCookie = isset($_COOKIE['MEMLOGIN_ADMIN_PURUN']) ? $_COOKIE['MEMLOGIN_ADMIN_PURUN'] : '';
if ($loginCookie === '') { echo "<meta http-equiv='refresh' content='0; url=/login.php'>"; exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/MailboxSync.php'; // 등 필요한 것
$db = $GLOBALS['mysql_compat_default_link'];
MailboxSync::ensureTables($db);
...쿼리...
?>
<div id="contentwrapper"><div class="main_content">
  <div id="jCrumbs" class="breadCrumb module"><ul>
    <li><a href="/admin"><i class="glyphicon glyphicon-home"></i></a></li>
    <li><a href="#">메일</a></li><li>메일함</li>
  </ul></div>
  ...본문...
</div></div>
<?php include "../include/side_m.php" ?>
<script> ...jQuery... </script>
</body></html>
```

**index.php** (`?folder=inbox|sent|trash&page=N&search=...`):
- 좌측 `col-sm-2`: ① 계정 select (변경 시 JS로 쿠키 `mbx_account_id` 설정 후 reload) + `accounts.php` 톱니 링크 ② `편지쓰기` btn-primary btn-block → compose.php ③ `nav nav-pills nav-stacked`: 받은편지함(fa-inbox, 안읽음 badge)/보낸편지함(fa-paper-plane)/휴지통(fa-trash) ④ `동기화` btn-default btn-block (`#btnSync`): 클릭 → 아이콘 `fa-refresh fa-spin` → `$.getJSON('api/sync.php')` → 완료 후 `location.reload()`, 실패 시 alert
- 우측 `col-sm-10`: 검색 form-inline(제목/보낸이 LIKE, 프리페어드 `%...%`) + 일괄 버튼(읽음/안읽음/삭제 — 체크 없으면 disabled, 클릭 시 `$.post('api/action.php', {ids:..., op:...})` → reload) + `table table-hover`: [전체선택 체크박스 | 📎 | 보낸이 | 제목+회색 snippet | 날짜 | 크기]
  - 보낸편지함이면 "보낸이" 대신 "받는이"(to_addr) 표시
  - 안읽음 행 `<tr class="mbx-unread">`, CSS `.mbx-unread td{font-weight:bold;background:#f5f8fc}`
  - 행 클릭(체크박스 td 제외) → view.php?id=
  - 날짜: 오늘이면 `H:i`, 올해면 `m-d`, 이전이면 `Y-m-d`. 크기: KB/MB 사람읽기
  - 페이징: `$limit=20`, `<ul class="pagination">` 처음/이전/번호(±5)/다음/마지막, folder·search 파라미터 유지 (news_list.php 패턴)

**view.php** (`?id=N`):
- 렌더 전에 body_fetched==0이면 fetchBody (try/catch — IMAP 실패해도 헤더는 보여주고 본문 자리에 오류 메시지)
- 제목 + 툴바: 답장(compose.php?reply=N)/전달(?forward=N)/삭제(action trash 후 목록으로)/목록
- 메타 패널: 보낸이/받는이/참조/날짜 — 전부 `htmlspecialchars`
- 본문: `<iframe id="mbxBody" src="api/body.php?id=N" sandbox="" style="width:100%;border:0;min-height:400px"></iframe>` + load 시 jQuery로 높이 자동 조절(sandbox라 접근 불가하므로 고정 높이 + `allow-same-origin` 없이 그냥 min-height 충분히. 높이 조절이 필요하면 `sandbox="allow-same-origin"`으로 contents 높이 읽기 — 스크립트는 여전히 차단됨)
- 첨부: body fetch 후 mailbox_attachments 목록 → `api/attachment.php?id=` 링크 (fa-paperclip, 파일명, 크기)

**compose.php** (`?reply=N|forward=N` 선택):
- 보내는계정 select(활성 계정) / 받는사람 / 참조 / 제목 / `<textarea id="mbxEditor">` + TinyMCE init / 첨부 input file (jQuery로 행 추가, 최대 5개, 클라이언트에서 20MB 체크) / 보내기·취소
- reply: 제목 `Re: ...`, 받는사람=원문 from_email, 본문에 `<br><hr>` 아래 원문 인용(날짜/보낸이 한 줄 + body_html), hidden `in_reply_to`=원문 message_id
- forward: 제목 `Fwd: ...`, 본문 인용 동일, 받는사람 비움
- 폼은 `api/send.php`로 일반 POST(multipart). 실패 시 send.php가 입력값 유지한 채 오류 표시(세션 플래시 또는 쿼리 파라미터로 단순 처리 가능)

**accounts.php**:
- 계정 목록 table(이메일/표시명/호스트/활성/정렬) + 행별 수정·삭제 버튼 + 추가 폼(이메일, 표시명, IMAP host/port 기본 imap.gmail.com:993, SMTP host/port 기본 smtp.gmail.com:587, 앱 비밀번호, 활성)
- 같은 파일에서 POST 처리(mode=add|edit|del) — 프리페어드 스테이트먼트
- 행별 `연결 테스트` 버튼 → `$.post('api/account_test.php', {account_id:...})` → ✓/✗ 표시
- 상단 안내: "Gmail 계정은 2단계 인증 활성화 후 [앱 비밀번호]를 발급받아 입력하세요. (Google 계정 → 보안 → 앱 비밀번호)"

## 10. 발송 흐름 (api/send.php)

1. POST 전용. account_id로 계정 행 조회(활성만)
2. to/cc: 쉼표 분리 → trim → `filter_var($e, FILTER_VALIDATE_EMAIL)` 통과분만, to 0건이면 오류
3. 첨부: `$_FILES['attach']` → 각각 20MB 이하, 확장자 블랙리스트(`php, phtml, php3~8, phps, pht, cgi, pl, exe, sh, bat`) → `uploads/Ym/` 디렉토리(없으면 생성)에 `uniqid('mbx_', true) . '.bin'`으로 move, 원래 파일명은 별도 보관
4. PHPMailer 5.x (§2 패턴): Host/Port/Username(=email)/Password(=app_password)는 계정 행에서. `SetFrom(email, display_name)`, to/cc 루프, `MsgHTML($body)`, `AddAttachment($storedPath, $originalName)`, in_reply_to 있으면 `AddCustomHeader('In-Reply-To: ' . $messageId)` + `AddCustomHeader('References: ' . $messageId)`
5. 성공: 임시 첨부 삭제 → try/catch로 best-effort `syncFolder('sent')` → `index.php?folder=sent&sent=1` 리다이렉트 (index가 `sent=1`이면 "메일을 보냈습니다" alert-success 표시)
6. 실패: 임시 첨부 삭제 → compose로 돌아가 오류 표시 (입력 보존은 세션에 임시 저장)

## 11. 보안 체크리스트 (필수)

- [ ] 모든 페이지/API에 인증 가드 (§2, §8)
- [ ] 모든 SQL은 프리페어드 스테이트먼트, id는 `(int)`
- [ ] 화면에 출력하는 모든 메일 헤더 필드(제목/보낸이/주소/snippet)에 `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`
- [ ] 메일 HTML 본문은 **api/body.php + sandbox iframe에서만** 렌더 (관리자 레이아웃 안에 직접 echo 금지). script/on속성/javascript: 제거 + CSP 헤더
- [ ] attachment.php는 정수 id만 입력받음 (파트번호/파일명은 DB에서) — 사용자 입력 경로 사용 금지
- [ ] uploads/ 에 .htaccess deny, 랜덤 파일명, 발송 후 삭제
- [ ] api/send.php, api/action.php는 POST 외 405
- [ ] cron 키 비교는 `hash_equals`
- [ ] test_imap.php에도 쿠키 인증 가드

## 12. 구현 순서 및 검증

1. `config.php`, `uploads/.htaccess`, `install.sql`
2. `lib/ImapClient.php` + `test_imap.php` — **계정이 DB에 없으므로 test_imap.php는 GET 파라미터(host/user/pass)로도 테스트 가능하게**. 접속/LOGIN/LIST로 실제 폴더명 확인, 최신 5건 헤더 덤프(리터럴 처리 검증)
3. `lib/MimeParser.php` — test_imap.php 확장: 최신 메일 1건 BODY.PEEK[] 파싱 결과(제목/보낸이/본문 앞부분/첨부목록) 출력. 한글 메일 케이스 확인
4. `lib/MailboxSync.php` + `lib/common.php` + `api/sync.php` — 첫 동기화 후 건수 확인, 재실행 시 신규 0건(멱등), 새 메일 수신 후 재동기화 반영
5. `accounts.php` + `api/account_test.php`
6. `index.php` — 계정전환/폴더/검색/페이징/안읽음/동기화버튼
7. `view.php` + `api/body.php` — 본문 렌더, Gmail 웹에서도 읽음 전환 확인, 스크립트 차단 확인
8. `api/attachment.php` — 실제 첨부 다운로드, 바이트 크기 일치
9. `api/action.php` — 일괄 읽음/휴지통 → Gmail 휴지통 반영
10. `compose.php` + `api/send.php` — 첨부 2개 발송 → 수신 확인 → 보낸편지함 동기화 확인
11. 모든 PHP 파일 `php -l` 문법 검사 (PHP 8.2)

> 운영(Linux) 배포 시: outbound 993/587 방화벽 확인, cron `*/10 * * * * php /경로/mailbox/api/sync.php`, test_imap.php 비활성화.

## 13. 참고 파일 (수정 금지, 패턴 참고만)

- `email_hist/news_list.php` — 페이지 골격/인증/페이징/검색 패턴
- `messenger/get_messages.php` — JSON API 패턴
- `include/dbconn.php` — `$dbConn`(mysqli), `esc()` 헬퍼
- `include/mysql_compat.php` — `$GLOBALS['mysql_compat_default_link']`
- `include/purun_func.php` mailsend_k() (라인 ~2065) — PHPMailer 5.x Gmail SMTP 사용 예
- `PHPMailer/class.phpmailer.php`, `class.smtp.php` — 동봉 PHPMailer 5.x
