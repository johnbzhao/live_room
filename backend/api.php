<?php
// 在文件最开头添加
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Windows专用配置
$config = [
    'recordings_dir' => 'C:\\Users\\zsh\\Desktop\\live_room\\backend\\temp',
    'pid_file' => 'C:\\Users\\zsh\\Desktop\\live_room\\backend\\live_recording.pid',
    'ffmpeg_path' => 'E:\\ffmpeg\\bin\\ffmpeg.exe',
    'log_file' => 'C:\\Users\\zsh\\Desktop\\live_room\\backend\\logs\\recording.log'
];

// 创建录播目录
if (!file_exists($config['recordings_dir'])) {
    mkdir($config['recordings_dir'], 0777, true);
}

function isRecordingRunning($pidFile) {
    if (!file_exists($pidFile)) return false;
    
    $pid = trim(file_get_contents($pidFile));
    exec("tasklist /FI \"PID eq $pid\" 2>&1", $output);
    return strpos(implode("\n", $output), 'ffmpeg.exe') !== false;
}

function startRecording($config) {
    if (isRecordingRunning($config['pid_file'])) {
        return ['code' => 1, 'msg' => '录播已在进行中'];
    }

    $filename = date('Ymd-His') . '.ts';
    $outputFile = $config['recordings_dir'] . '\\' . $filename;

    // 每次生成单独的日志文件，方便调试
    $logFile = $config['log_file'];
    $command = sprintf(
        'start /B "" "%s" -i http://localhost:8080/live/livestream.m3u8 -c copy -f mpegts "%s" >> "%s" 2>&1',
        $config['ffmpeg_path'],
        $outputFile,
        $logFile
    );

    // 启动 FFmpeg 进程（后台运行）
    pclose(popen($command, 'r'));

    // 等待进程启动
    sleep(1); // 可根据系统性能延长时间

    // 查找 ffmpeg.exe 的 PID
    exec('tasklist /FI "IMAGENAME eq ffmpeg.exe" /FO CSV', $tasks);
    $pid = null;
    foreach ($tasks as $line) {
        if (stripos($line, 'ffmpeg.exe') !== false) {
            $fields = str_getcsv($line);
            $pid = $fields[1]; // 第二列是 PID
            break;
        }
    }

    if (!$pid) {
        return ['code' => 500, 'msg' => '启动 FFmpeg 失败，未找到进程'];
    }

    file_put_contents($config['pid_file'], $pid);

    return [
        'code' => 0,
        'msg' => '录播已开始',
        'data' => [
            'filename' => $filename,
            'pid' => $pid,
            'start_time' => date('Y-m-d H:i:s')
        ]
    ];
}


function stopRecording($config) {
    if (!isRecordingRunning($config['pid_file'])) {
        return ['code' => 1, 'msg' => '没有正在进行的录播'];
    }
    
    $pid = file_get_contents($config['pid_file']);
    exec("taskkill /PID $pid /F");
    unlink($config['pid_file']);
    
    return ['code' => 0, 'msg' => '录播已停止'];
}

function listRecordings($config) {
    $files = [];
    foreach (glob($config['recordings_dir'] . '\\*.ts') as $file) {
        $files[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'modified' => date('Y-m-d H:i:s', filemtime($file)),
            'path' => '/temp/' . basename($file)
        ];
    }
    return ['code' => 0, 'data' => $files];
}

// 主逻辑
try {
    $action = $_GET['action'] ?? '';
    $response = [];
    
    switch ($action) {
        case 'start_recording':
            $response = startRecording($config);
            break;
        case 'stop_recording':
            $response = stopRecording($config);
            break;
        case 'list':
            $response = listRecordings($config);
            break;
        case 'status':
            $response = [
                'code' => 0,
                'data' => [
                    'is_recording' => isRecordingRunning($config['pid_file']),
                    'pid' => file_exists($config['pid_file']) ? file_get_contents($config['pid_file']) : null
                ]
            ];
            break;
        case 'delete':
            $filename = $_POST['filename'] ?? '';
            $file = $config['recordings_dir'] . '\\' . basename($filename);
            if (file_exists($file)) {
                unlink($file);
                $response = ['code' => 0, 'msg' => '删除成功'];
            } else {
                $response = ['code' => 1, 'msg' => '文件不存在'];
            }
            break;
        default:
            throw new Exception('无效操作');
    }
    
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['code' => 500, 'msg' => $e->getMessage()]);
}