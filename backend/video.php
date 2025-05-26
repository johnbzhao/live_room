<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['code' => 405, 'msg' => 'Method Not Allowed']);
    exit;
}

// 简单的视频列表
$videos = [
    [
        'id' => 1,
        'title' => '示例直播流',
        'url' => 'http://localhost:8080/live/livestream.m3u8'
    ],
    [
        'id' => 2,
        'title' => '备用直播流',
        'url' => 'http://localhost:8080/live/backup.m3u8'
    ]
];

echo json_encode([
    'code' => 0,
    'msg' => 'success',
    'data' => $videos
]);