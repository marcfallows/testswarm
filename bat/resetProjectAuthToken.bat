@echo off
echo.
set /p projectId="Enter Project ID: " %=%
C:\wamp\bin\php\php5.3.13\php.exe %~dp0\..\scripts\refreshProjectToken.php --id="%projectId%" --quick
pause