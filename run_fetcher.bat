@echo off
title Nepal Election Fetcher - Running every 60s
echo ===============================================
echo  Nepal Election 2082 - Live Data Fetcher
echo  Fetches from Hamro Patro every 60 seconds
echo  Press Ctrl+C to stop
echo ===============================================
echo.

:loop
echo [%time%] Fetching...
"C:\Users\sabin\.config\herd\bin\php.bat" "E:\projects\php\election\fetcher\cron.php" 2>&1
echo.
echo [%time%] Waiting 60 seconds...
timeout /t 60 /nobreak > nul
goto loop
