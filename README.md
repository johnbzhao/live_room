# 直播流媒体技术方案

## 技术栈概览

### 前端技术
- **框架**: Vue 3 + Vite
- **流媒体播放**: HLS.js
- **播放器**: video.js

### 后端技术
- PHP (通过 Laragon 运行)

### 流媒体基础设施
- **服务器**: Docker 容器运行的 SRS 流媒体服务器
- **推流工具**: OBS Studio

---

## 快速使用指南

### 1.环境搭建
```bash
# 启动SRS流媒体服务器容器
# 先下载Docker Desktop
docker run -d \
  --name srs-server \
  -p 1935:1935 \
  -p 8080:8080 \
  -p 1985:1985 \
  ossrs/srs:4
npm install 
# 下载Laragon，并且将backend的内容放到laragon/www/live-room/中
# 将frontend的内容下载下来
修改VideoPlayer.vue中的onMounted中的src，按照ip进行修改（ipconfig获取ip）
修改App.vue中的connectWebSocket的ws，按照ip进行修改
修改api.php中的config的路径信息
```