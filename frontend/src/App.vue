<template>
  <LoginForm v-if="!isLoggedIn" @login-success="onLoginSuccess" />
  <div v-else id="app">
    <!-- 左侧视频区域 -->
    <div class="video-section">
      <!-- 视频上方标题和主播信息 -->
      <div class="video-header">
        <h2 class="section-title">直播间</h2>
        <div class="streamer-bar">
          <div class="streamer-info">
            <img src="/src/assets/avatar.jpg" alt="主播头像" class="avatar" />
            <span class="streamer-name">zsh</span>
            <button 
              class="follow-button"
              :class ="{followed:isFollowed}"
              @click="toggleFollow"  
            >{{isFollowed? '已关注':'关注'}}</button>
          </div>
        </div>
      </div>
      <VideoPlayer />
    </div>

    <!-- 右侧评论区域 -->
    <div class="comment-section">
      <h2 class="comment-title">评论区</h2>
      <div class="divider"></div>
      <div class="comment-list">
        <div v-for="(comment, index) in comments" :key="index" class="comment-item">
          <template v-if="comment.type === 'system'">
            <span style="color:#ffb300">{{ comment.text }}（当前在线：{{ comment.online }}）</span>
          </template>
          <template v-else>
            <b>{{ comment.user }}：</b>{{ comment.text }}
          </template>
        </div>
      </div>
      <div class="comment-input">
        <input 
          v-model="newComment" 
          @keyup.enter="submitComment"
          type="text" 
          placeholder="说点什么..." 
          class="input-box"
        >
        <button @click="submitComment" class="send-button">发送</button>
      </div>
      <div v-if="commentError" style="color:#ff8080; margin-top:4px;">{{ commentError }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue'
import VideoPlayer from './components/VideoPlayer.vue'
import LoginForm from './components/LoginForm.vue'

const comments = ref([])
const newComment = ref('')
const commentError=ref('')


const isFollowed = ref(false)
const toggleFollow= () =>{
  isFollowed.value=!isFollowed.value
}

const isLoggedIn = ref(false)
const username = ref('')
let ws = null

function onLoginSuccess(name) {
  username.value = name
  isLoggedIn.value = true
  connectWebSocket()
}

function connectWebSocket() {
  ws = new window.WebSocket('ws://192.168.61.31:3001')
  ws.onopen = () => {
    commentError.value = ''
    ws.send(JSON.stringify({ type: 'login', user: username.value }))
  }
  ws.onerror = (e) => {
    commentError.value = '评论服务连接失败'
  }
  ws.onclose = () => {
    commentError.value = '评论服务已断开'
  }
  ws.onmessage = (event) => {
    try {
      const msg = JSON.parse(event.data)
      comments.value.push(msg)
      setTimeout(() => {
        const commentList = document.querySelector('.comment-list')
        if (commentList) commentList.scrollTop = commentList.scrollHeight
      }, 50)
    } catch (e) {}
  }
}

const submitComment = () => {
  commentError.value = ''
  if (newComment.value.trim() && ws && ws.readyState === 1) {
    ws.send(JSON.stringify({
      type: 'comment',
      user: username.value,
      text: newComment.value.trim()
    }))
    newComment.value = ''
  } else if (!ws || ws.readyState !== 1) {
    commentError.value = '评论服务未连接，无法发送'
  }
}

onBeforeUnmount(() => {
  if (ws) ws.close()
  username.value = ''
  isLoggedIn.value = false
})
</script>

<style scoped>
#app {
  display: flex;
  width: 100%;
  height: 100vh;
  background-color: #f5f5f5;
}

/* 视频区域整体 */
.video-section {
  flex: 3;
  display: flex;
  flex-direction: column;
  position: relative;
  background: #000;
}

/* 顶部标题和主播信息 */
.video-header {
  background-color: #333;
  color: white;
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.section-title {
  font-size: 1.8rem;
  font-weight: bold;
  margin: 0;
  color: white;
}

.streamer-bar {
  margin-top: 8px;
  width: 100%;
  display: flex;
  justify-content: left;
}
.streamer-info {
  display: flex;
  align-items: center;
  gap: 10px;
}
.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid white;
}
.streamer-name {
  font-size: 1rem;
  color: white;
}

.follow-button {
  background-color: #ff4081;
  border: none;
  color: white;
  padding: 6px 12px;
  border-radius: 4px;
  font-weight: bold;
  cursor: pointer;
}
.follow-button:hover {
  background-color: #ff4081;
}

/* 评论区整体 */
.comment-section {
  flex: 1.2;
  display: flex;
  flex-direction: column;
  background: #1e1e1e; /* 改为深色背景 */
  padding: 15px;
  box-shadow: -2px 0 5px rgba(0,0,0,0.1);
}

.comment-title {
  color: #ffffff; /* 白色文字 */
  padding: 6px;
  text-align: center;
  margin-bottom: 10px;
  font-size: 1.5rem;
}

.divider {
  height: 1px;
  background-color: #444; /* 深色分隔线 */
  margin: 10px 0;
}

.comment-list {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 15px;
  padding-right: 5px;
}

.comment-item {
  padding: 8px 12px;
  margin-bottom: 8px;
  background-color: #2d2d2d; /* 深色评论背景 */
  border-radius: 4px;
  word-break: break-word;
  color: #ffffff; /* 白色文字 */
}

/* 评论输入区域 */
.comment-input {
  display: flex;
  gap: 10px;
  padding: 10px 0;
  border-top: 1px solid #444; /* 深色分隔线 */
}

.input-box {
  flex: 1;
  padding: 12px 15px; /* 增大内边距 */
  border: 1px solid #444;
  border-radius: 4px;
  outline: none;
  background-color: #2d2d2d;
  color: white;
  font-size: 1rem;
  height: 40px; /* 固定高度 */
}

.input-box:focus {
  border-color: #ff4081;
}

.send-button {
  padding: 0 20px;
  background-color: #ff4081;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  height: 40px; /* 与输入框同高 */
  min-width: 80px; /* 最小宽度 */
}

.send-button:hover {
  background-color: #e73370;
}

/* 滚动条样式 */
.comment-list::-webkit-scrollbar {
  width: 6px;
}

.comment-list::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.comment-list::-webkit-scrollbar-track {
  background-color: transparent;
}
@media (max-width: 768px) {
  #app {
    flex-direction: column;
  }
  .video-section {
    flex: none;
    height: 50vh !important;
  }
  .comment-section {
    flex: none;
    height: 50vh !important;
  }
}
</style>