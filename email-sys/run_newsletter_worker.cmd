@echo off
cd /d "%~dp0"
"C:\laragon\bin\php\php-8.2.28-nts-Win32-vs16-x64\php.exe" newsletter_cron_worker.php
