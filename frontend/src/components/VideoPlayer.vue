<template>
  <div class="video-container">
    <!-- 视频播放器 -->
    <video
      ref="videoRef"
      class="video-js vjs-default-skin"
      controls
      autoplay
      muted
      playsinline
    ></video>

    <!-- 录播控制栏 -->
    <div class="control-bar">
      <button 
        @click="toggleRecording" 
        :class="{ 'recording': isRecording }"
        class="control-btn"
      >
        {{ isRecording ? '● 录制中' : '开始录播' }}
        <span v-if="isRecording" class="recording-duration">
          {{ formatDuration(recordingDuration) }}
        </span>
      </button>
      
      <button 
        @click="showRecordings = !showRecordings"
        class="control-btn list-btn"
      >
        录播列表
      </button>
      
      <div v-if="recordingStatus" class="status-message">
        {{ recordingStatus }}
      </div>
    </div>

    <!-- 录播列表模态框 -->
    <div v-if="showRecordings" class="modal-overlay" @click.self="showRecordings = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>录播文件</h3>
          <button @click="showRecordings = false" class="close-btn">&times;</button>
        </div>
        
        <div v-if="isLoading" class="loading-spinner">
          <div class="spinner"></div>
        </div>
        
        <div v-else-if="recordings.length === 0" class="empty-list">
          暂无录播文件
        </div>
        
        <div v-else class="recordings-list">
          <div 
            v-for="(file, index) in recordings" 
            :key="index"
            class="recording-item"
          >
            <div class="file-info">
              <span class="file-name">{{ file.name }}</span>
              <span class="file-details">
                {{ formatFileSize(file.size) }} | 
                {{ formatDate(file.modified) }}
              </span>
            </div>
            <div class="file-actions">
              <button @click="downloadRecording(file.path)" class="action-btn">
                <i class="icon-download"></i> 下载
              </button>
              <button @click="deleteRecording(file.name)" class="action-btn delete">
                <i class="icon-delete"></i> 删除
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount,watch } from 'vue'
import videojs from 'video.js'
import 'video.js/dist/video-js.css'
import '@videojs/http-streaming'

const videoRef = ref(null)
const player = ref(null)

// 录播状态
const isRecording = ref(false)
const recordingDuration = ref(0)
const recordingStartTime = ref(null)
const recordingStatus = ref('')
let recordingInterval = null

// 录播列表
const recordings = ref([])
const showRecordings = ref(false)
const isLoading = ref(false)

// 初始化播放器
onMounted(() => {
  player.value = videojs(videoRef.value, {
    sources: [{
      src: 'http://192.168.61.31:8080/live/livestream.m3u8',
      type: 'application/x-mpegURL'
    }],
    fluid: true,
    liveui: true,
    autoplay: true
  })

  // 监听播放事件自动开始录播
  //player.value.on('play', () => {
  //  startRecording()
  //})

  // 检查现有录播状态
  checkRecordingStatus()
})

// 清理资源
onBeforeUnmount(() => {
  if (player.value) {
    player.value.dispose()
  }
  clearInterval(recordingInterval)
})

// 格式化时间显示
const formatDuration = (seconds) => {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = Math.floor(seconds % 60)
  return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
}

// 格式化文件大小
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// 格式化日期
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString()
}

// 开始录播
const startRecording = async () => {
  if (isRecording.value) return

  try {
    recordingStatus.value = '正在启动录播...'
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), 5000)

    const response = await fetch('http://localhost/live-room/api.php?action=start_recording', {
      signal: controller.signal
    })
    clearTimeout(timeoutId)

    const data = await response.json()

    if (data.code===0) {
      isRecording.value = true
      recordingStartTime.value = Date.now()
      recordingInterval = setInterval(() => {
        recordingDuration.value = Math.floor((Date.now() - recordingStartTime.value) / 1000)
      }, 1000)
      recordingStatus.value = '录播已开始'
      setTimeout(() => recordingStatus.value = '', 3000)
    } else {
      throw new Error('启动录播失败')
    }
  } catch (error) {
    console.error('录播启动错误:', error)
    recordingStatus.value = '录播启动失败: ' + error.message
  }
}

// 停止录播
const stopRecording = async () => {
  if (!isRecording.value) return

  try {
    recordingStatus.value = '正在停止录播...'
    const response = await fetch('http://localhost/live-room/api.php?action=stop_recording')
    const data = await response.json()

    if (data.code===0) {
      isRecording.value = false
      clearInterval(recordingInterval)
      recordingDuration.value = 0
      recordingStatus.value = '录播已保存'
      setTimeout(() => recordingStatus.value = '', 3000)
      fetchRecordings() // 刷新录播列表
    } else {
      throw new Error('停止录播失败')
    }
  } catch (error) {
    console.error('录播停止错误:', error)
    recordingStatus.value = '录播停止失败: ' + error.message
  }
}

// 切换录播状态
const toggleRecording = () => {
  isRecording.value ? stopRecording() : startRecording()
}

// 检查录播状态
const checkRecordingStatus = async () => {
  try {
    const response = await fetch('http://localhost/live-room/api.php?action=status')
    const data = await response.json()
    
    if (data.code === 0 && data.data && data.data.is_recording) {
      isRecording.value = true
      // 这里没有 duration 字段，建议不设置 recordingStartTime
      // 或者后端返回 start_time
      recordingStatus.value = '录播已在进行中'
    } else {
      isRecording.value = false
    }
  } catch (error) {
    console.error('检查录播状态失败:', error)
  }
}

// 获取录播列表
const fetchRecordings = async () => {
  isLoading.value = true
  try {
    const response = await fetch('http://localhost/live-room/api.php?action=list')
    const data = await response.json()
    recordings.value = data.data || []
  } catch (error) {
    console.error('获取录播列表失败:', error)
    recordingStatus.value = '获取录播列表失败'
  } finally {
    isLoading.value = false
  }
}

// 下载录播文件
const downloadRecording = (path) => {
  window.open(`http://localhost/live-room${path}`, '_blank')
}

// 删除录播文件
const deleteRecording = async (filename) => {
  if (!confirm('确定要删除该录播文件吗？')) return;
  const res = await fetch('http://localhost/live-room/api.php?action=delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `filename=${encodeURIComponent(filename)}`
  });
  const data = await res.json();
  if (data.code === 0) {
    fetchRecordings();
  } else {
    alert(data.msg || '删除失败');
  }
};

// 打开录播列表时自动刷新
watch(showRecordings, (val) => {
  if (val) fetchRecordings()
})
</script>

<style scoped>
.video-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.control-bar {
  position: absolute;
  bottom: 20px;
  left: 20px;
  z-index: 1000;
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(0, 0, 0, 0.7);
  padding: 8px 12px;
  border-radius: 4px;
}

.control-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  display: flex;
  align-items: center;
  gap: 6px;
}

.control-btn.recording {
  background: #ff4444;
  color: white;
}

.control-btn:not(.recording) {
  background: #4CAF50;
  color: white;
}

.list-btn {
  background: #2196F3;
}

.recording-duration {
  margin-left: 6px;
  font-size: 0.9em;
}

.status-message {
  margin-left: 10px;
  color: white;
  font-size: 0.9em;
}

/* 模态框样式 */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  z-index: 2000;
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-content {
  background: #1e1e1e;
  border-radius: 8px;
  width: 80%;
  max-width: 800px;
  max-height: 80vh;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.modal-header {
  padding: 16px 20px;
  background: #333;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: white;
}

.close-btn {
  background: none;
  border: none;
  color: white;
  font-size: 1.5em;
  cursor: pointer;
}

/* 录播列表样式 */
.recordings-list {
  max-height: 60vh;
  overflow-y: auto;
}

.recording-item {
  padding: 12px 16px;
  border-bottom: 1px solid #333;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.recording-item:hover {
  background: #2a2a2a;
}

.file-info {
  flex: 1;
}

.file-name {
  display: block;
  font-weight: bold;
  margin-bottom: 4px;
  color: #eee;
}

.file-details {
  font-size: 0.8em;
  color: #aaa;
}

.file-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.9em;
}

.action-btn i {
  font-size: 1.1em;
}

.action-btn:not(.delete) {
  background: #2196F3;
  color: white;
}

.action-btn.delete {
  background: #f44336;
  color: white;
}

.empty-list {
  padding: 40px;
  text-align: center;
  color: #aaa;
}

.loading-spinner {
  padding: 40px;
  display: flex;
  justify-content: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #2196F3;
  animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* 响应式调整 */
@media (max-width: 768px) {
  .control-bar {
    position: static;
    margin: 10px 0 0 0;
    width: 100%;
    justify-content: left;
    flex-wrap: wrap;
    background: transparent;
    padding: 0;
  }

  
  .modal-content {
    width: 95%;
  }
  
  .recording-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .file-actions {
    align-self: flex-end;
  }
}
</style>