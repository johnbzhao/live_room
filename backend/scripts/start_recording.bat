@echo off
setlocal

:: 配置参数 - 根据您的实际路径修改
set FFMPEG_PATH="E:\ffmpeg\bin\ffmpeg.exe"
set OUTPUT_DIR="E:\live-recordings"  :: 建议使用独立目录存放录播文件
set LOG_FILE="E:\live-recordings\logs\recording.log"
set STREAM_URL="http://localhost:8080/live/livestream.m3u8"
set PID_FILE="E:\live-recordings\temp\recording.pid"

:: 创建必要目录
if not exist %OUTPUT_DIR% mkdir %OUTPUT_DIR%
if not exist "%~dp0..\logs" mkdir "%~dp0..\logs"
if not exist "%~dp0..\temp" mkdir "%~dp0..\temp"

:: 生成带时间戳的文件名
set filename=live_%date:~0,4%%date:~5,2%%date:~8,2%_%time:~0,2%%time:~3,2%

:: 替换文件名中的冒号（Windows文件名限制）
set filename=%filename::=%

:: 启动录播（静默模式）
echo [%date% %time%] 开始录播 >> %LOG_FILE%
start /B %FFMPEG_PATH% -i %STREAM_URL% -c copy "%OUTPUT_DIR%\%filename%.mp4" >> %LOG_FILE% 2>&1

:: 获取并记录PID
for /f "tokens=2" %%a in ('tasklist /fi "imagename eq ffmpeg.exe" /fo list ^| find "PID:"') do set pid=%%a
echo %pid% > %PID_FILE%
echo [%date% %time%] 录播进程PID: %pid% >> %LOG_FILE%

endlocal