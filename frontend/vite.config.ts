import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',    // 监听所有网络接口
    port: 3000,         // 指定端口号
    strictPort: true,   // 如果端口被占用直接退出
    open: false,        // 不自动打开浏览器
    cors: true,         // 启用CORS
    hmr: {
      overlay: false    // 禁用HMR错误覆盖层(手机调试时有用)
    }
  },
  preview: {
    port: 3000,        // 预览模式也使用相同端口
    host: '0.0.0.0'
  },
  build: {
    assetsDir: 'static', // 静态资源目录
    chunkSizeWarningLimit: 1500 // 加大块大小警告限制
  }
})