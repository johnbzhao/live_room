const WebSocket = require('ws')

const wss = new WebSocket.Server({ port: 3001 })
let onlineCount = 0

// 用于记录每个连接的用户名
const userMap = new Map()

wss.on('connection', ws => {
  let username = null

  ws.on('message', msg => {
    try {
      const data = JSON.parse(msg)
      if (data.type === 'login') {
        // 防止重复登录
        if (!username) {
          username = data.user
          userMap.set(ws, username)
          onlineCount++
          // 广播用户进入
          broadcast({
            type: 'system',
            text: `${username} 进入了直播间`,
            online: onlineCount
          })
        }
      } else if (data.type === 'comment') {
        // 普通评论
        broadcast({
          type: 'comment',
          user: data.user,
          text: data.text
        })
      }
    } catch (e) {
      // 忽略解析错误
    }
  })

  ws.on('close', () => {
    if (username) {
      onlineCount = Math.max(0, onlineCount - 1)
      userMap.delete(ws)
      // 广播用户离开
      broadcast({
        type: 'system',
        text: `${username} 离开了直播间`,
        online: onlineCount
      })
    }
  })
})

function broadcast(obj) {
  const msg = JSON.stringify(obj)
  wss.clients.forEach(client => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(msg)
    }
  })
}

console.log('WebSocket 服务器已启动，端口 3001')