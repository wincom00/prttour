# 메일함 플러그인 가이드

`admin/mailbox`는 파란여행사 ERP 관리자 안에서 메일 읽기, 동기화, 작성, 발송을 처리하는 독립형 플러그인입니다. 플러그인 폴더가 `admin/` 아래에 있든 document root 바로 아래에 있든 설치 파일이 위치를 자동 감지합니다.

## 플러그인 경계

- 플러그인 루트: `admin/mailbox/`
- 대체 플러그인 루트: `mailbox/`
- 웹 루트: `/admin/mailbox/`
- 대체 웹 루트: `/mailbox/`
- 메인 진입점: `/admin/mailbox/index.php`
- PHP 훅 파일: `plugin.php`
- 메타데이터 파일: `plugin.json`
- 설치 파일: `install.php`
- 삭제 배치: `delete_mailbox_plugin.cmd`
- 변경분 롤백 배치: `delete_mailbox_changes.cmd`
- DB 스키마 참고 파일: `install.sql`

ERP 본체는 `plugin.php` 훅만 호출하고, 메일함 UI/동기화/SQL 로직은 `mailbox` 플러그인 내부에 둡니다.
`lib/bootstrap.php`가 현재 폴더 위치를 해석해서 ERP `admin/include` 경로와 플러그인 웹 경로를 자동 결정합니다.

## 현재 훅

`admin/include/side_m.php`는 `admin/mailbox/plugin.php`를 로드한 뒤 다음 함수를 호출합니다.

- `mbx_plugin_prepare_sidebar($context)`: 사이드바 렌더링 전 플러그인 상태 준비
- `mbx_plugin_render_sidebar($state)`: `#side_accordion` 안에 메일함 사이드바 출력
- `mbx_plugin_render_sidebar_script($state)`: 기존 jQuery ready 블록 안에 메일함 사이드바 스크립트 출력

플러그인은 `$_SERVER['SCRIPT_NAME']`에 `/admin/mailbox/`가 포함되어 있으면 메일함 페이지로 판단합니다. 메일함 페이지에서는 ERP 기본 메뉴를 숨기고 메일함 전용 사이드바를 출력합니다.

## 파일 역할

- `config.php`: 메일함 상수, 동기화 제한, 제공자 기본값, 폴더 매핑
- `lib/common.php`: 공통 부트스트랩, 인증 확인, 계정 조회, 이스케이프, JSON 응답
- `lib/ImapClient.php`: 순수 PHP IMAP 클라이언트
- `lib/MimeParser.php`: MIME/헤더/본문 파싱
- `lib/MailboxSync.php`: 스키마 생성, 동기화, 본문 가져오기, 읽음/삭제 처리
- `api/*.php`: AJAX/CLI 엔드포인트
- `index.php`, `view.php`, `compose.php`, `accounts.php`, `my_account.php`: 화면 페이지
- `plugin.php`: ERP 본체 연동 훅 전용. 업무 로직은 `lib/`, 화면, API 파일에 둡니다.
- `install.php`: 브라우저/CLI 설치 파일. `MailboxSync::ensureTables()`로 테이블을 생성/보정합니다.
- `delete_mailbox_plugin.cmd`: Windows 삭제 보조 배치. `install.php --uninstall --yes`로 테이블을 삭제하고, 두 번째 확인 후 플러그인 파일을 삭제할 수 있습니다.
- `delete_mailbox_changes.cmd`: 지금까지 메일함 플러그인 작업으로 변경된 내용을 정리하는 롤백 배치. DB 테이블 삭제, `side_m.php` 백업 복원, 플러그인 폴더 삭제를 각각 별도로 확인받습니다.

## 설치

브라우저:

```text
/admin/mailbox/install.php
```

document root 하위에 설치한 경우:

```text
/mailbox/install.php
```

브라우저 설치는 최고 관리자 `admin` 계정만 실행할 수 있습니다.

CLI:

```bat
php admin\mailbox\install.php
```

document root 하위에 설치한 경우:

```bat
php mailbox\install.php
```

설치 파일은 여러 번 실행해도 됩니다. 누락된 테이블을 생성하고, 알려진 메일함 컬럼/인덱스는 `MailboxSync::ensureTables()`를 통해 보정합니다. `uploads/` 디렉터리가 없으면 만들고, `uploads/.htaccess` 보호 파일도 확인합니다. 설치 결과에는 `admin 하위` 또는 `document root 하위`가 표시됩니다.

## 삭제 / 제거

Windows 배치:

```bat
admin\mailbox\delete_mailbox_plugin.cmd
```

지금까지 수정/추가된 메일함 작업분을 되돌리는 배치:

```bat
admin\mailbox\delete_mailbox_changes.cmd
```

`delete_mailbox_changes.cmd`는 다음 확인 문구를 단계별로 요구합니다.

- `DELETE_MAILBOX_CHANGES`: 롤백 절차 시작
- `DELETE_MAILBOX_DB`: `mailbox_*` DB 테이블 삭제
- `RESTORE_SIDE_M`: 최신 `side_m.php` 백업 복원
- `DELETE_MAILBOX_FILES`: `admin\mailbox` 또는 `mailbox` 플러그인 폴더 삭제

삭제 배치는 두 번 확인합니다.

- `DELETE_MAILBOX_PLUGIN` 입력: `mailbox_*` DB 테이블 삭제
- `DELETE_MAILBOX_FILES` 입력: `admin\mailbox` 디렉터리 삭제

DB 테이블만 삭제하려면 다음 명령도 사용할 수 있습니다.

```bat
php admin\mailbox\install.php --uninstall --yes
```

플러그인 파일을 삭제해도 `admin/include/side_m.php`의 훅 로더는 안전하게 동작합니다. `admin/mailbox/plugin.php`가 없으면 아무것도 로드하지 않으므로 일반 ERP 페이지는 계속 렌더링됩니다.

## 개발 가이드라인

1. ERP 공통 파일은 얇게 유지합니다.
   `include/side_m.php` 같은 공통 파일에는 플러그인 로드/훅 호출만 둡니다. 메일함 SQL, HTML, AJAX 로직을 직접 넣지 않습니다.

2. 플러그인 경로를 고정합니다.
   브라우저 URL은 `mbx_plugin_url()`을 사용하고, 파일 시스템 경로는 `mbx_plugin_dir()`, `mbx_admin_path()`를 사용합니다. `/admin/mailbox/...`를 직접 하드코딩하지 않습니다.

3. 인증은 모든 진입점에서 확인합니다.
   페이지는 `mbx_require_page_auth()`, API는 CLI 또는 유효한 `MBX_SYNC_KEY`가 아닌 경우 `mbx_require_api_auth()`를 호출합니다.
   로그인 쿠키(`MEMLOGIN_ADMIN_PURUN`, 파란 ERP는 `MEMLOGIN_ADMIN_PARAN`) 값은 본체에서 `base64_encode(serialize(...))` 형태로 내려옵니다. 플러그인에서 직접 `base64_decode()` 하지 말고, 먼저 `header.php` / `inc_base.php`가 준비한 전역값(`$user_info`, `$user_dbinfo`)을 상속받습니다. 전역값이 비어 있는 경우(예: `install.php`/`index.php` 처럼 inc_base/header 를 함수 스코프에서 require 해 전역이 채워지지 않는 진입점)에만 본체 함수 `getinfo_Member()` / `getinfo_dbMember()`를 통해 fallback 처리합니다.

4. SQL은 prepared statement를 사용합니다.
   메일함 코드는 `mbx_stmt()`를 통해 `mysqli_prepare()`를 사용합니다. 사용자 입력을 섞은 raw SQL을 추가하지 않습니다.

5. 메일 HTML 본문은 격리합니다.
   메일 본문 HTML은 `api/body.php`의 sandbox iframe 안에서만 렌더링합니다. 관리자 페이지에 직접 echo 하지 않습니다.

6. 사이드바 동작은 `plugin.php`에 둡니다.
   계정 전환, 안읽음 badge, 수동 동기화, 자동 동기화 스크립트는 `mbx_plugin_render_sidebar_script()`에서 관리합니다.

7. 메일함 테이블명은 유지합니다.
   새 메일함 테이블은 `mailbox_` 접두사를 사용하고 `plugin.json`, `mbx_plugin_manifest()`, `install.sql`, `MailboxSync::ensureTables()`에 함께 반영합니다.

8. 날짜가 붙은 스냅샷/백업은 수정하지 않습니다.
   복구 목적이 아니라면 `mailbox_backup_*`와 dated snapshot 파일은 건드리지 않습니다.

## 새 훅 추가 절차

1. `plugin.json`에 훅 이름을 추가합니다.
2. `mbx_plugin_manifest()`의 `hooks`에도 같은 이름을 추가합니다.
3. `plugin.php`에 작은 prepare/render 함수를 구현합니다.
4. ERP 본체 파일에서 해당 함수를 호출합니다.
5. DB나 플러그인 파일이 없어도 ERP 페이지가 깨지지 않도록 예외에 강하게 만듭니다.

## 검증 체크리스트

- 변경된 PHP 파일의 문법 검사를 실행합니다.
- 로그인 후 `/admin/mailbox/index.php`를 엽니다.
- 메일함 페이지에서 ERP 기본 메뉴가 숨겨지는지 확인합니다.
- 계정 선택, 폴더, 메일 쓰기, 계정 링크, 동기화 버튼이 사이드바에 보이는지 확인합니다.
- 계정 선택 시 `mbx_account_id`가 설정되고 메일함 목록으로 이동하는지 확인합니다.
- 동기화 버튼 클릭 시 `/admin/mailbox/api/sync.php`가 JSON을 반환하고 화면이 새로고침되는지 확인합니다.
- 일반 ERP 페이지에서는 기존 메뉴가 정상 표시되는지 확인합니다.

## 변경 파일 보고 규칙

메일함 플러그인을 변경한 뒤에는 마지막에 수정/추가 파일 목록을 보고합니다.
