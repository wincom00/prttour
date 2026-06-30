@echo off
chcp 65001 >nul
setlocal EnableExtensions

set "PLUGIN_DIR=%~dp0"
for %%I in ("%PLUGIN_DIR%..") do set "PARENT_DIR=%%~fI"
for %%I in ("%PLUGIN_DIR%..\..") do set "ROOT_DIR=%%~fI"

if exist "%PARENT_DIR%\include\side_m.php" (
  set "ADMIN_DIR=%PARENT_DIR%"
) else if exist "%PARENT_DIR%\admin\include\side_m.php" (
  set "ADMIN_DIR=%PARENT_DIR%\admin"
) else if exist "%ROOT_DIR%\admin\include\side_m.php" (
  set "ADMIN_DIR=%ROOT_DIR%\admin"
) else (
  echo Could not find ERP admin directory.
  exit /b 1
)

set "SIDE_PATH=%ADMIN_DIR%\include\side_m.php"

echo.
echo Mailbox plugin rollback script
echo Plugin directory : %PLUGIN_DIR%
echo Admin directory  : %ADMIN_DIR%
echo Side menu file   : %SIDE_PATH%
echo.
echo This script can remove the files changed for the mailbox plugin work.
echo It asks separately before dropping DB tables, restoring side_m.php, and deleting plugin files.
echo.

set /p CONFIRM_START=Type DELETE_MAILBOX_CHANGES to continue: 
if not "%CONFIRM_START%"=="DELETE_MAILBOX_CHANGES" (
  echo Cancelled.
  exit /b 0
)

echo.
set /p CONFIRM_DB=Type DELETE_MAILBOX_DB to drop mailbox_* tables, or press Enter to skip: 
if "%CONFIRM_DB%"=="DELETE_MAILBOX_DB" (
  where php >nul 2>nul
  if errorlevel 1 (
    echo PHP was not found in PATH. Skipping DB table deletion.
  ) else (
    php "%PLUGIN_DIR%install.php" --uninstall --yes
    if errorlevel 1 (
      echo DB table deletion failed. Continuing without deleting files.
      exit /b 1
    )
  )
) else (
  echo DB table deletion skipped.
)

echo.
set "SIDE_BACKUP="
for /f "delims=" %%F in ('dir /b /a-d /o-d "%SIDE_PATH%.mailbox_backup_*" 2^>nul') do if not defined SIDE_BACKUP set "SIDE_BACKUP=%ADMIN_DIR%\include\%%F"
if not defined SIDE_BACKUP (
  for /f "delims=" %%F in ('dir /b /a-d /o-d "%ADMIN_DIR%\include\side_m_backup_*.php" 2^>nul') do if not defined SIDE_BACKUP set "SIDE_BACKUP=%ADMIN_DIR%\include\%%F"
)

if defined SIDE_BACKUP (
  echo Latest side_m.php backup:
  echo %SIDE_BACKUP%
  set /p CONFIRM_SIDE=Type RESTORE_SIDE_M to restore this backup, or press Enter to skip: 
  if "%CONFIRM_SIDE%"=="RESTORE_SIDE_M" (
    copy /y "%SIDE_BACKUP%" "%SIDE_PATH%" >nul
    if errorlevel 1 (
      echo Failed to restore side_m.php.
      exit /b 1
    )
    echo side_m.php restored.
  ) else (
    echo side_m.php restore skipped.
  )
) else (
  echo No side_m.php backup was found. Leaving side_m.php as-is.
  echo The mailbox hook is safe when the plugin files are missing.
)

echo.
set /p CONFIRM_FILES=Type DELETE_MAILBOX_FILES to delete the mailbox plugin directory, or press Enter to skip: 
if not "%CONFIRM_FILES%"=="DELETE_MAILBOX_FILES" (
  echo Plugin file deletion skipped.
  exit /b 0
)

set "HELPER=%TEMP%\delete_mailbox_changes_%RANDOM%%RANDOM%.cmd"
(
  echo @echo off
  echo timeout /t 2 /nobreak ^>nul
  echo rmdir /s /q "%PLUGIN_DIR%"
  echo del "%%~f0"
) > "%HELPER%"

echo Plugin directory deletion has been scheduled in a helper command window.
start "" /min cmd /c "%HELPER%"
exit /b 0
