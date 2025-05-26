@echo off
setlocal

:: 配置参数 - 必须与start_recording.bat保持一致
set PID_FILE="%~dp0..\temp\recording.pid"
set LOG_FILE="%~dp0..\logs\recording.log"

:: 检查PID文件是否存在
if not exist %PID_FILE% (
    echo [%date% %time%] 错误：未找到录播进程PID文件 >> %LOG_FILE%
    exit /b 1
)

:: 读取PID并终止进程
set /p pid=<%PID_FILE%
taskkill /PID %pid% /F >> %LOG_FILE% 2>&1

:: 清理PID文件
del %PID_FILE%

echo [%date% %time%] 已停止录播进程PID: %pid% >> %LOG_FILE%

endlocal