@echo off
chcp 65001 >nul
setlocal

set "PLUGIN_DIR=%~dp0"
set "ADMIN_DIR=%PLUGIN_DIR%.."

echo.
echo 메일함 플러그인 삭제 배치
echo 플러그인 디렉터리: %PLUGIN_DIR%
echo.
echo 이 배치는 mailbox_* DB 테이블과 admin\mailbox 파일을 삭제할 수 있습니다.
echo 계속하기 전에 DB/파일 백업을 먼저 확인하세요.
echo.

set /p CONFIRM_DB=메일함 DB 테이블을 삭제하려면 DELETE_MAILBOX_PLUGIN 을 입력하세요: 
if not "%CONFIRM_DB%"=="DELETE_MAILBOX_PLUGIN" (
  echo DB 테이블 삭제를 취소했습니다.
  goto files_prompt
)

where php >nul 2>nul
if errorlevel 1 (
  echo PATH에서 PHP를 찾지 못했습니다. DB 테이블 삭제를 건너뜁니다.
  goto files_prompt
)

php "%PLUGIN_DIR%install.php" --uninstall --yes
if errorlevel 1 (
  echo DB 테이블 삭제가 실패했습니다. 파일 삭제는 실행하지 않습니다.
  exit /b 1
)

:files_prompt
echo.
set /p CONFIRM_FILES=admin\mailbox 파일을 삭제하려면 DELETE_MAILBOX_FILES 를 입력하세요: 
if not "%CONFIRM_FILES%"=="DELETE_MAILBOX_FILES" (
  echo 파일 삭제를 취소했습니다.
  exit /b 0
)

set "HELPER=%TEMP%\delete_mailbox_plugin_%RANDOM%%RANDOM%.cmd"
(
  echo @echo off
  echo timeout /t 2 /nobreak ^>nul
  echo rmdir /s /q "%PLUGIN_DIR%"
  echo del "%%~f0"
) > "%HELPER%"

echo 파일 삭제를 예약했습니다. 보조 창이 실행된 뒤 이 창은 닫아도 됩니다.
start "" /min cmd /c "%HELPER%"
exit /b 0
