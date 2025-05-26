@echo off
setlocal enabledelayedexpansion

:: 配置参数
set FFMPEG_PATH="C:\ffmpeg\bin\ffmpeg.exe"
set RECORDINGS_DIR="C:\live_recordings"
set LOG_FILE="C:\live_recordings\recording.log"
set PID_FILE="C:\Windows\Temp\live_recording.pid"

:menu
cls
echo 直播录播管理系统
echo ----------------------------
echo 1. 启动录播
echo 2. 停止录播
echo 3. 查看录播状态
echo 4. 列出录播文件
echo 5. 清理旧录播
echo 6. 退出
set /p choice=请选择操作: 

if "%choice%"=="1" goto start_recording
if "%choice%"=="2" goto stop_recording
if "%choice%"=="3" goto check_status
if "%choice%"=="4" goto list_recordings
if "%choice%"=="5" goto cleanup
if "%choice%"=="6" exit

:: 启动录播
:start_recording
cls
echo 正在检查是否已有录播进行中...
tasklist /fi "imagename eq ffmpeg.exe" | find "ffmpeg.exe" >nul
if %errorlevel% equ 0 (
    echo 错误：已有录播正在进行
    pause
    goto menu
)

set /p filename=请输入录播文件名(不含扩展名): 
if "%filename%"=="" set filename=recording_%date:~0,4%%date:~5,2%%date:~8,2%_%time:~0,2%%time:~3,2%

echo 开始录播: %filename%.mp4
start /B %FFMPEG_PATH% -i http://localhost:8080/live/livestream.m3u8 -c copy "%RECORDINGS_DIR%\%filename%.mp4" >> %LOG_FILE% 2>&1

:: 获取PID
for /f "tokens=2" %%a in ('tasklist /fi "imagename eq ffmpeg.exe" /fo list ^| find "PID:"') do set pid=%%a
echo %pid% > %PID_FILE%

echo 录播已启动，PID: %pid%
pause
goto menu

:: 停止录播
:stop_recording
cls
if not exist %PID_FILE% (
    echo 错误：没有活动的录播进程
    pause
    goto menu
)

set /p pid=<%PID_FILE%
taskkill /PID %pid% /F
del %PID_FILE%

echo 已停止录播进程: %pid%
pause
goto menu

:: 检查状态
:check_status
cls
tasklist /fi "imagename eq ffmpeg.exe" | find "ffmpeg.exe" >nul
if %errorlevel% equ 0 (
    echo 录播正在运行
) else (
    echo 没有录播进行中
)

if exist %PID_FILE% (
    set /p pid=<%PID_FILE%
    echo 录播PID: %pid%
)
pause
goto menu

:: 列出录播文件
:list_recordings
cls
echo 录播文件列表:
echo ----------------------------
dir %RECORDINGS_DIR%\*.mp4 /b
echo ----------------------------
pause
goto menu

:: 清理旧录播
:cleanup
cls
echo 将删除7天前的录播文件
forfiles /P %RECORDINGS_DIR% /M *.mp4 /D -7 /C "cmd /c echo 删除 @file & del @path"
echo 清理完成
pause
goto menu