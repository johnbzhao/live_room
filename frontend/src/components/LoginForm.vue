<template>
  <div class="login-wrapper">
    <div class="login-box">
      <h1 class="login-title">直播间</h1>
      <div class="input-group">
        <label class="input-label">用户名</label>
        <input v-model="username" class="input-field" placeholder="请输入用户名" />
      </div>
      <div class="input-group">
        <label class="input-label">密码</label>
        <input v-model="password" type="password" class="input-field" placeholder="请输入密码" />
      </div>
      <button class="login-btn" @click="handleLogin">登录</button>
      <div v-if="error" class="error-msg">{{ error }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const username = ref('')
const password = ref('')
const error = ref('')

const emit = defineEmits(['login-success'])

const handleLogin = async () => {
  error.value = ''
  if (!username.value || !password.value) {
    error.value = '请输入用户名和密码'
    return
  }
  // 允许任意用户名和密码登录，传递用户名给父组件
  emit('login-success', username.value)
}
</script>

<style scoped>
.login-wrapper {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(30,30,30,0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.login-box {
  background: #222;
  padding: 40px 32px 32px 32px;
  border-radius: 10px;
  min-width: 320px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.4);
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.login-title {
  color: #fff;
  font-size: 2rem;
  font-weight: bold;
  text-align: center;
  margin-bottom: 32px;
  letter-spacing: 4px;
}
.input-group {
  display: flex;
  align-items: center;
  margin-bottom: 18px;
}
.input-label {
  color: #fff;
  width: 60px;
  margin-right: 10px;
  text-align: right;
  font-size: 1rem;
}
.input-field {
  flex: 1;
  padding: 10px 12px;
  border-radius: 4px;
  border: 1px solid #444;
  background: #333;
  color: #fff;
  font-size: 1rem;
  outline: none;
}
.input-field:focus {
  border-color: #ff4081;
}
.login-btn {
  margin-top: 10px;
  padding: 12px 0;
  background: #ff4081;
  color: #fff;
  border: none;
  border-radius: 4px;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.2s;
}
.login-btn:hover {
  background: #e73370;
}
.error-msg {
  color: #ff8080;
  margin-top: 12px;
  text-align: center;
  font-size: 0.98rem;
}
</style>